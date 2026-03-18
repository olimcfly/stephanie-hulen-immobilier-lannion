<?php
/**
 * ══════════════════════════════════════════════════════════════════════
 * MODULE ADS-LAUNCH — API AJAX
 * /admin/modules/ads-launch/api.php
 * ══════════════════════════════════════════════════════════════════════
 */

// ─── Bootstrap ───
if (!defined('ADMIN_ROUTER')) {
    require_once dirname(dirname(__DIR__)) . '/includes/init.php';
}

// ─── JSON only ───
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ─── Helpers ───
function json_ok(mixed $data = null, string $msg = 'OK'): never {
    echo json_encode(['success' => true, 'message' => $msg, 'data' => $data]);
    exit;
}
function json_err(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

// ─── Méthode POST uniquement ───
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_err('Méthode non autorisée', 405);
}

// ─── DB ───
if (!isset($pdo) && !isset($db)) json_err('Connexion DB manquante', 500);
if (isset($db) && !isset($pdo)) $pdo = $db;

// ─── Auth session ───
$userId = $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? null;
if (!$userId) json_err('Non authentifié', 401);

// ─── CSRF (actions mutantes uniquement) ───
$action = trim($_POST['action'] ?? '');
$readOnlyActions = ['get_prerequisites', 'get_account', 'get_campaigns', 'get_analytics'];

if (!in_array($action, $readOnlyActions)) {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_err('Token CSRF invalide', 403);
    }
}

// ─── Sanitize helpers ───
function intPost(string $key, int $default = 0): int {
    return (int)($_POST[$key] ?? $default);
}
function strPost(string $key, int $maxLen = 255): string {
    return mb_substr(trim($_POST[$key] ?? ''), 0, $maxLen);
}
function allowedPost(string $key, array $allowed, string $default = ''): string {
    $v = trim($_POST[$key] ?? '');
    return in_array($v, $allowed, true) ? $v : $default;
}

// ══════════════════════════════════════════════════════════════════════
// ROUTING
// ══════════════════════════════════════════════════════════════════════
switch ($action) {

    // ────────────────────────────────────────────────────────────────
    // COMPTES
    // ────────────────────────────────────────────────────────────────

    case 'create_account':
        $name     = strPost('name');
        $platform = allowedPost('platform', ['facebook', 'google', 'tiktok'], 'facebook');
        $pixel    = strPost('pixel_id', 64);
        $budget   = max(0, (float)($_POST['daily_budget'] ?? 0));

        if ($name === '') json_err('Le nom du compte est obligatoire');

        try {
            // Vérifier doublon
            $exists = $pdo->prepare("SELECT id FROM ads_accounts WHERE account_name = ? AND user_id = ?");
            $exists->execute([$name, $userId]);
            if ($exists->fetch()) json_err('Un compte avec ce nom existe déjà');

            $stmt = $pdo->prepare("
                INSERT INTO ads_accounts (user_id, account_name, platform, pixel_id, daily_budget, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([$userId, $name, $platform, $pixel ?: null, $budget ?: null]);
            $id = (int)$pdo->lastInsertId();

            json_ok(['id' => $id, 'name' => $name], 'Compte créé');
        } catch (PDOException $e) {
            error_log('[AdsLaunch] create_account: ' . $e->getMessage());
            json_err('Erreur base de données', 500);
        }

    case 'get_account':
        $id = intPost('account_id');
        if (!$id) json_err('ID compte manquant');

        try {
            $stmt = $pdo->prepare("SELECT * FROM ads_accounts WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$account) json_err('Compte introuvable', 404);
            json_ok($account);
        } catch (PDOException $e) {
            json_err('Erreur base de données', 500);
        }

    case 'update_account':
        $id     = intPost('account_id');
        $name   = strPost('name');
        $status = allowedPost('status', ['active', 'paused', 'archived'], 'active');
        $pixel  = strPost('pixel_id', 64);
        $budget = max(0, (float)($_POST['daily_budget'] ?? 0));

        if (!$id) json_err('ID compte manquant');

        try {
            $stmt = $pdo->prepare("
                UPDATE ads_accounts
                SET account_name = ?, status = ?, pixel_id = ?, daily_budget = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$name, $status, $pixel ?: null, $budget ?: null, $id, $userId]);
            if ($stmt->rowCount() === 0) json_err('Compte introuvable ou non modifié', 404);
            json_ok(null, 'Compte mis à jour');
        } catch (PDOException $e) {
            error_log('[AdsLaunch] update_account: ' . $e->getMessage());
            json_err('Erreur base de données', 500);
        }

    case 'delete_account':
        $id = intPost('account_id');
        if (!$id) json_err('ID compte manquant');

        try {
            $pdo->beginTransaction();
            // Supprimer en cascade les données liées
            foreach (['ads_campaigns', 'ads_audiences', 'ads_prerequisites'] as $table) {
                try {
                    $pdo->prepare("DELETE FROM `{$table}` WHERE account_id = ?")->execute([$id]);
                } catch (PDOException $e) { /* table optionnelle */ }
            }
            $stmt = $pdo->prepare("DELETE FROM ads_accounts WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                json_err('Compte introuvable', 404);
            }
            $pdo->commit();
            json_ok(null, 'Compte supprimé');
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('[AdsLaunch] delete_account: ' . $e->getMessage());
            json_err('Erreur base de données', 500);
        }

    // ────────────────────────────────────────────────────────────────
    // PRÉREQUIS
    // ────────────────────────────────────────────────────────────────

    case 'get_prerequisites':
        $accountId = intPost('account_id');
        if (!$accountId) json_err('ID compte manquant');

        $defaults = [
            ['key' => 'pixel',   'done' => false],
            ['key' => 'gtm',     'done' => false],
            ['key' => 'domain',  'done' => false],
            ['key' => 'bm',      'done' => false],
            ['key' => 'payment', 'done' => false],
            ['key' => 'catalog', 'done' => false],
        ];

        try {
            $stmt = $pdo->prepare("
                SELECT prereq_key, is_done FROM ads_prerequisites
                WHERE account_id = ? AND user_id = ?
            ");
            $stmt->execute([$accountId, $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // key => is_done

            $result = array_map(fn($p) => [
                'key'  => $p['key'],
                'done' => (bool)($rows[$p['key']] ?? false),
            ], $defaults);

            json_ok($result);
        } catch (PDOException $e) {
            // Table absente → retourner les défauts silencieusement
            json_ok($defaults);
        }

    case 'save_prerequisites':
        $accountId = intPost('account_id');
        $rawItems  = $_POST['items'] ?? '[]';

        if (!$accountId) json_err('ID compte manquant');

        $items = json_decode($rawItems, true);
        if (!is_array($items)) json_err('Format items invalide');

        $allowedKeys = ['pixel', 'gtm', 'domain', 'bm', 'payment', 'catalog'];

        try {
            $pdo->beginTransaction();
            // Upsert par item
            $stmt = $pdo->prepare("
                INSERT INTO ads_prerequisites (account_id, user_id, prereq_key, is_done, updated_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE is_done = VALUES(is_done), updated_at = NOW()
            ");
            foreach ($items as $item) {
                $key  = $item['key']  ?? '';
                $done = (int)(bool)($item['done'] ?? false);
                if (!in_array($key, $allowedKeys, true)) continue;
                $stmt->execute([$accountId, $userId, $key, $done]);
            }
            $pdo->commit();
            json_ok(null, 'Prérequis sauvegardés');
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('[AdsLaunch] save_prerequisites: ' . $e->getMessage());
            json_err('Erreur base de données', 500);
        }

    // ────────────────────────────────────────────────────────────────
    // AUDIENCES
    // ────────────────────────────────────────────────────────────────

    case 'create_audiences':
        $accountId = intPost('account_id');
        if (!$accountId) json_err('ID compte manquant');

        // Vérifier que le compte appartient à l'utilisateur
        try {
            $check = $pdo->prepare("SELECT id FROM ads_accounts WHERE id = ? AND user_id = ?");
            $check->execute([$accountId, $userId]);
            if (!$check->fetch()) json_err('Compte introuvable', 404);
        } catch (PDOException $e) {
            json_err('Erreur base de données', 500);
        }

        $audienceTypes = [
            ['type' => 'CI',  'name' => 'Custom Intent',   'temperature' => 'Hot',  'description' => 'Visiteurs site + interactions'],
            ['type' => 'LAL', 'name' => 'Lookalike 180j',  'temperature' => 'Warm', 'description' => 'Sosies clients 180 jours'],
            ['type' => 'TNT', 'name' => 'Test & Target',   'temperature' => 'Cold', 'description' => 'Centres d\'intérêt ciblés'],
        ];

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                INSERT INTO ads_audiences (account_id, user_id, audience_type, audience_name, temperature, description, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'draft', NOW())
                ON DUPLICATE KEY UPDATE audience_name = VALUES(audience_name), updated_at = NOW()
            ");
            $created = [];
            foreach ($audienceTypes as $aud) {
                $stmt->execute([
                    $accountId, $userId,
                    $aud['type'], $aud['name'],
                    $aud['temperature'], $aud['description'],
                ]);
                $created[] = ['type' => $aud['type'], 'id' => (int)$pdo->lastInsertId()];
            }
            $pdo->commit();
            json_ok($created, '3 audiences créées');
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('[AdsLaunch] create_audiences: ' . $e->getMessage());
            json_err('Erreur base de données', 500);
        }

    case 'get_audiences':
        $accountId = intPost('account_id');
        if (!$accountId) json_err('ID compte manquant');

        try {
            $stmt = $pdo->prepare("
                SELECT id, audience_type, audience_name, temperature, status, created_at
                FROM ads_audiences
                WHERE account_id = ? AND user_id = ?
                ORDER BY FIELD(temperature, 'Hot', 'Warm', 'Cold')
            ");
            $stmt->execute([$accountId, $userId]);
            json_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            json_err('Erreur base de données', 500);
        }

    // ────────────────────────────────────────────────────────────────
    // CAMPAGNES
    // ────────────────────────────────────────────────────────────────

    case 'save_campaign':
        $accountId   = intPost('account_id');
        $name        = strPost('name');
        $temperature = allowedPost('temperature', ['Cold', 'Warm', 'Hot'], 'Cold');
        $objective   = allowedPost('objective', ['Leads', 'Traffic', 'Conversions', 'Awareness', 'Retargeting'], 'Leads');
        $audience    = allowedPost('audience', ['CI', 'LAL', 'TNT'], 'CI');
        $budget      = max(0, (float)($_POST['budget'] ?? 0));

        if ($name === '') json_err('Le nom de la campagne est obligatoire');

        try {
            $stmt = $pdo->prepare("
                INSERT INTO ads_campaigns
                    (account_id, user_id, campaign_name, temperature, objective, audience_type, daily_budget, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', NOW())
            ");
            $stmt->execute([
                $accountId ?: null, $userId,
                $name, $temperature, $objective, $audience,
                $budget ?: null,
            ]);
            json_ok(['id' => (int)$pdo->lastInsertId(), 'name' => $name], 'Campagne sauvegardée');
        } catch (PDOException $e) {
            error_log('[AdsLaunch] save_campaign: ' . $e->getMessage());
            json_err('Erreur base de données', 500);
        }

    case 'get_campaigns':
        $accountId = intPost('account_id');

        try {
            if ($accountId) {
                $stmt = $pdo->prepare("
                    SELECT id, campaign_name, temperature, objective, audience_type,
                           daily_budget, status, created_at
                    FROM ads_campaigns
                    WHERE account_id = ? AND user_id = ?
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$accountId, $userId]);
            } else {
                $stmt = $pdo->prepare("
                    SELECT id, campaign_name, temperature, objective, audience_type,
                           daily_budget, status, created_at
                    FROM ads_campaigns
                    WHERE user_id = ?
                    ORDER BY created_at DESC
                    LIMIT 50
                ");
                $stmt->execute([$userId]);
            }
            json_ok($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            json_err('Erreur base de données', 500);
        }

    case 'update_campaign_status':
        $id     = intPost('id');
        $status = allowedPost('status', ['draft', 'active', 'paused', 'archived'], 'draft');
        if (!$id) json_err('ID campagne manquant');

        try {
            $stmt = $pdo->prepare("
                UPDATE ads_campaigns SET status = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$status, $id, $userId]);
            if ($stmt->rowCount() === 0) json_err('Campagne introuvable', 404);
            json_ok(null, 'Statut mis à jour');
        } catch (PDOException $e) {
            json_err('Erreur base de données', 500);
        }

    case 'delete_campaign':
        $id = intPost('id');
        if (!$id) json_err('ID campagne manquant');

        try {
            $stmt = $pdo->prepare("DELETE FROM ads_campaigns WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
            if ($stmt->rowCount() === 0) json_err('Campagne introuvable', 404);
            json_ok(null, 'Campagne supprimée');
        } catch (PDOException $e) {
            json_err('Erreur base de données', 500);
        }

    // ────────────────────────────────────────────────────────────────
    // ANALYTICS
    // ────────────────────────────────────────────────────────────────

    case 'get_analytics':
        $accountId = intPost('account_id');
        $period    = allowedPost('period', ['7d', '14d', '30d', '90d'], '30d');

        $days = match($period) {
            '7d'  => 7,
            '14d' => 14,
            '90d' => 90,
            default => 30,
        };

        try {
            // KPIs agrégés
            $stmt = $pdo->prepare("
                SELECT
                    COALESCE(SUM(impressions), 0)   AS impressions,
                    COALESCE(SUM(clicks), 0)         AS clicks,
                    COALESCE(SUM(leads), 0)          AS leads,
                    COALESCE(SUM(spend), 0)          AS spend,
                    COALESCE(AVG(NULLIF(ctr, 0)), 0) AS avg_ctr,
                    COALESCE(AVG(NULLIF(roas, 0)), 0) AS avg_roas
                FROM ads_analytics
                WHERE account_id = ? AND user_id = ?
                  AND date_recorded >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            ");
            $stmt->execute([$accountId ?: 0, $userId, $days]);
            $kpis = $stmt->fetch(PDO::FETCH_ASSOC);

            // CPL calculé
            $leads = (float)($kpis['leads'] ?? 0);
            $spend = (float)($kpis['spend'] ?? 0);
            $kpis['cpl'] = $leads > 0 ? round($spend / $leads, 2) : 0;

            // Séries temporelles pour graphique (7 derniers jours)
            $seriesStmt = $pdo->prepare("
                SELECT date_recorded, SUM(leads) AS leads, SUM(spend) AS spend
                FROM ads_analytics
                WHERE account_id = ? AND user_id = ?
                  AND date_recorded >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY date_recorded
                ORDER BY date_recorded ASC
            ");
            $seriesStmt->execute([$accountId ?: 0, $userId, $days]);
            $series = $seriesStmt->fetchAll(PDO::FETCH_ASSOC);

            json_ok(['kpis' => $kpis, 'series' => $series]);
        } catch (PDOException $e) {
            // Table absente → retourner zéros proprement
            json_ok([
                'kpis'   => ['impressions'=>0,'clicks'=>0,'leads'=>0,'spend'=>0,'avg_ctr'=>0,'avg_roas'=>0,'cpl'=>0],
                'series' => [],
            ]);
        }

    // ────────────────────────────────────────────────────────────────
    // CHECKLIST GLOBALE
    // ────────────────────────────────────────────────────────────────

    case 'save_checklist':
        $accountId = intPost('account_id');
        $rawSteps  = $_POST['steps'] ?? '[]';
        $steps     = json_decode($rawSteps, true);

        if (!is_array($steps)) json_err('Format steps invalide');

        $allowedSteps = ['tech', 'account', 'audiences', 'campaigns', 'analytics'];

        try {
            $stmt = $pdo->prepare("
                INSERT INTO ads_checklist (account_id, user_id, step_key, is_done, updated_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE is_done = VALUES(is_done), updated_at = NOW()
            ");
            foreach ($steps as $step) {
                $key  = $step['key']  ?? '';
                $done = (int)(bool)($step['done'] ?? false);
                if (!in_array($key, $allowedSteps, true)) continue;
                $stmt->execute([$accountId ?: null, $userId, $key, $done]);
            }
            json_ok(null, 'Checklist sauvegardée');
        } catch (PDOException $e) {
            error_log('[AdsLaunch] save_checklist: ' . $e->getMessage());
            json_err('Erreur base de données', 500);
        }

    case 'get_checklist':
        $accountId = intPost('account_id');

        $defaults = array_map(fn($k) => ['key' => $k, 'done' => false],
            ['tech', 'account', 'audiences', 'campaigns', 'analytics']
        );

        try {
            $stmt = $pdo->prepare("
                SELECT step_key, is_done FROM ads_checklist
                WHERE account_id = ? AND user_id = ?
            ");
            $stmt->execute([$accountId ?: null, $userId]);
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            $result = array_map(fn($s) => [
                'key'  => $s['key'],
                'done' => (bool)($rows[$s['key']] ?? false),
            ], $defaults);

            json_ok($result);
        } catch (PDOException $e) {
            json_ok($defaults);
        }

    // ────────────────────────────────────────────────────────────────
    // DEFAULT
    // ────────────────────────────────────────────────────────────────

    default:
        json_err("Action inconnue : {$action}", 404);
}