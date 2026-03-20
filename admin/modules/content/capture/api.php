<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE CAPTURES — API unifiée  v1.0
 *  /admin/modules/content/capture/api.php
 *
 *  Endpoint POST centralisé pour toutes les opérations CRUD
 *  sur les pages de capture.
 *
 *  Actions supportées (POST : action=xxx) :
 *    save           → INSERT (id=0) ou UPDATE (id>0)
 *    delete         → Suppression + stats associées
 *    get            → SELECT * WHERE id = ?
 *    list           → SELECT avec pagination + filtres
 *    toggle_status  → Activer / désactiver une capture
 *    duplicate      → Dupliquer une page de capture
 *    stats          → Récupérer les stats (vues, conversions, taux)
 *
 *  Retourne TOUJOURS du JSON :
 *    { success: bool, message: string, data?: mixed, errors?: object }
 * ══════════════════════════════════════════════════════════════
 */

// ─── Initialisation ───
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_id'])) {
    _jsonResponse(false, 'Non authentifié', null, 401);
}

// ─── DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) {
        require_once __DIR__ . '/../../../config/config.php';
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            _jsonResponse(false, 'DB: ' . $e->getMessage(), null, 500);
        }
    }
}
if (isset($db)  && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db))  $db  = $pdo;

// ─── Lire les données (POST form ou JSON body) ───
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $body = file_get_contents('php://input');
    $input = json_decode($body, true) ?: [];
}

$action = $input['action'] ?? $_GET['action'] ?? '';

// ══════════════════════════════════════════════════════════════
//  ROUTAGE
// ══════════════════════════════════════════════════════════════
switch ($action) {

    // ──────────────────────────────────────────────────────────
    //  SAVE — INSERT (id=0) ou UPDATE (id>0)
    // ──────────────────────────────────────────────────────────
    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            _jsonResponse(false, 'Méthode non autorisée', null, 405);
        }

        // Récupérer les champs
        $id             = (int)($input['id']             ?? 0);
        $titre          = trim($input['titre']           ?? '');
        $slug           = trim($input['slug']            ?? '');
        $description    = trim($input['description']     ?? '');
        $headline       = trim($input['headline']        ?? '');
        $sous_titre     = trim($input['sous_titre']      ?? '');
        $contenu        = trim($input['contenu']         ?? '');
        $image_url      = trim($input['image_url']       ?? '');
        $cta_text       = trim($input['cta_text']        ?? '');
        $page_merci_url = trim($input['page_merci_url']  ?? '');
        $type           = $input['type']                 ?? 'contact';
        $template       = $input['template']             ?? 'simple';
        $status_val     = $input['status']               ?? 'active';
        $active         = ($status_val === 'active') ? 1 : 0;
        $actif          = $active;

        // Guide IDs (JSON ou tableau)
        $guide_ids_raw = $input['guide_ids'] ?? null;
        $guide_ids_json = null;
        if (!empty($guide_ids_raw)) {
            if (is_array($guide_ids_raw)) {
                $guide_ids_json = json_encode($guide_ids_raw);
            } elseif (is_string($guide_ids_raw)) {
                $decoded = json_decode($guide_ids_raw, true);
                $guide_ids_json = ($decoded !== null) ? $guide_ids_raw : null;
            }
        }

        // Champs formulaire (JSON ou tableau)
        $champs_raw = $input['champs_formulaire'] ?? null;
        $champs_json = null;
        if (!empty($champs_raw)) {
            if (is_array($champs_raw)) {
                $champs_json = json_encode($champs_raw);
            } elseif (is_string($champs_raw)) {
                $decoded = json_decode($champs_raw, true);
                $champs_json = ($decoded !== null) ? $champs_raw : json_encode(['raw' => $champs_raw]);
            }
        }

        // Validation
        $errors = [];
        if (empty($titre)) $errors['titre'] = 'Le titre est obligatoire.';
        if (empty($slug)) {
            if (!empty($titre)) {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-',
                    iconv('UTF-8', 'ASCII//TRANSLIT', $titre)));
                $slug = trim($slug, '-');
            } else {
                $errors['slug'] = 'Le slug est obligatoire.';
            }
        }
        if (!in_array($type, ['estimation', 'contact', 'newsletter', 'guide'])) {
            $errors['type'] = 'Type invalide.';
        }
        if (!in_array($status_val, ['active', 'inactive', 'archived'])) {
            $errors['status'] = 'Statut invalide.';
        }
        if (!empty($errors)) {
            _jsonResponse(false, 'Données invalides', null, 422, $errors);
        }

        // Vérifier unicité du slug
        try {
            $slugCheck = $pdo->prepare("SELECT id FROM captures WHERE slug = ? AND id != ?");
            $slugCheck->execute([$slug, $id]);
            if ($slugCheck->fetch()) {
                $slug = $slug . '-' . time();
            }
        } catch (PDOException $e) {}

        // Données à persister
        $data = [
            'titre'             => $titre,
            'slug'              => $slug,
            'description'       => $description ?: null,
            'headline'          => $headline     ?: null,
            'sous_titre'        => $sous_titre   ?: null,
            'contenu'           => $contenu      ?: null,
            'image_url'         => $image_url    ?: null,
            'cta_text'          => $cta_text     ?: null,
            'page_merci_url'    => $page_merci_url ?: null,
            'guide_ids'         => $guide_ids_json,
            'champs_formulaire' => $champs_json,
            'type'              => $type,
            'template'          => $template,
            'status'            => $status_val,
            'active'            => $active,
            'actif'             => $actif,
        ];

        try {
            if ($id > 0) {
                // UPDATE
                $exists = $pdo->prepare("SELECT id FROM captures WHERE id = ?");
                $exists->execute([$id]);
                if (!$exists->fetch()) {
                    _jsonResponse(false, 'Capture introuvable', null, 404);
                }
                $sets = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
                $pdo->prepare("UPDATE captures SET $sets, updated_at = NOW() WHERE id = ?")
                    ->execute([...array_values($data), $id]);
                $resultId = $id;
                $message  = 'Page de capture mise à jour avec succès.';
            } else {
                // INSERT
                $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
                $ph   = implode(', ', array_fill(0, count($data), '?'));
                $pdo->prepare("INSERT INTO captures ($cols, created_at) VALUES ($ph, NOW())")
                    ->execute(array_values($data));
                $resultId = (int)$pdo->lastInsertId();
                $message  = 'Page de capture créée avec succès.';
            }
            _jsonResponse(true, $message, ['id' => $resultId]);
        } catch (PDOException $e) {
            _jsonResponse(false, 'Erreur SQL : ' . $e->getMessage(), null, 500);
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  DELETE — Suppression + stats associées
    // ──────────────────────────────────────────────────────────
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            _jsonResponse(false, 'Méthode non autorisée', null, 405);
        }

        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            _jsonResponse(false, 'ID invalide', null, 400);
        }

        // Vérifier que la capture existe
        try {
            $stmt = $pdo->prepare("SELECT id, titre FROM captures WHERE id = ?");
            $stmt->execute([$id]);
            $rec = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            _jsonResponse(false, 'Erreur SQL : ' . $e->getMessage(), null, 500);
        }

        if (!$rec) {
            _jsonResponse(false, 'Capture introuvable', null, 404);
        }

        try {
            $pdo->beginTransaction();
            // Supprimer les stats journalières (table optionnelle)
            try {
                $pdo->prepare("DELETE FROM captures_stats WHERE capture_id = ?")->execute([$id]);
            } catch (PDOException $e) {}
            // Supprimer la capture
            $pdo->prepare("DELETE FROM captures WHERE id = ?")->execute([$id]);
            $pdo->commit();
            _jsonResponse(true, 'Page de capture supprimée avec succès.', ['id' => $id]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            _jsonResponse(false, 'Erreur SQL : ' . $e->getMessage(), null, 500);
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  GET — Récupérer une capture par ID
    // ──────────────────────────────────────────────────────────
    case 'get':
        $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            _jsonResponse(false, 'ID invalide', null, 400);
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM captures WHERE id = ?");
            $stmt->execute([$id]);
            $capture = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            _jsonResponse(false, 'Erreur SQL : ' . $e->getMessage(), null, 500);
        }

        if (!$capture) {
            _jsonResponse(false, 'Capture introuvable', null, 404);
        }

        // Décoder les champs JSON
        if (!empty($capture['guide_ids'])) {
            $capture['guide_ids'] = json_decode($capture['guide_ids'], true);
        }
        if (!empty($capture['champs_formulaire'])) {
            $capture['champs_formulaire'] = json_decode($capture['champs_formulaire'], true);
        }

        _jsonResponse(true, 'OK', $capture);
        break;

    // ──────────────────────────────────────────────────────────
    //  LIST — Liste avec pagination et filtres
    // ──────────────────────────────────────────────────────────
    case 'list':
        $page    = max(1, (int)($input['page'] ?? $_GET['page_num'] ?? 1));
        $perPage = min(100, max(1, (int)($input['per_page'] ?? $_GET['per_page'] ?? 25)));
        $offset  = ($page - 1) * $perPage;

        $filterStatus = $input['status'] ?? $_GET['status'] ?? 'all';
        $filterType   = $input['type']   ?? $_GET['type']   ?? 'all';
        $search       = trim($input['q'] ?? $_GET['q'] ?? '');

        $where = [];
        $params = [];

        if ($filterStatus !== 'all') {
            if ($filterStatus === 'active') {
                $where[] = "status = 'active'";
            } elseif ($filterStatus === 'inactive') {
                $where[] = "status IN('inactive','archived')";
            }
        }
        if ($filterType !== 'all') {
            $where[] = "type = ?";
            $params[] = $filterType;
        }
        if ($search !== '') {
            $where[] = "(titre LIKE ? OR slug LIKE ? OR description LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        try {
            // Total
            $stmtC = $pdo->prepare("SELECT COUNT(*) FROM captures {$whereSQL}");
            $stmtC->execute($params);
            $total = (int)$stmtC->fetchColumn();
            $totalPages = max(1, ceil($total / $perPage));

            // Données
            $stmt = $pdo->prepare("SELECT * FROM captures {$whereSQL} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
            $stmt->execute($params);
            $captures = $stmt->fetchAll(PDO::FETCH_ASSOC);

            _jsonResponse(true, 'OK', [
                'captures'    => $captures,
                'total'       => $total,
                'page'        => $page,
                'per_page'    => $perPage,
                'total_pages' => $totalPages,
            ]);
        } catch (PDOException $e) {
            _jsonResponse(false, 'Erreur SQL : ' . $e->getMessage(), null, 500);
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  TOGGLE_STATUS — Activer / désactiver
    // ──────────────────────────────────────────────────────────
    case 'toggle_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            _jsonResponse(false, 'Méthode non autorisée', null, 405);
        }

        $id     = (int)($input['id'] ?? 0);
        $status = $input['status'] ?? '';

        if ($id <= 0) {
            _jsonResponse(false, 'ID invalide', null, 400);
        }
        if (!in_array($status, ['active', 'inactive', 'archived'])) {
            _jsonResponse(false, 'Statut invalide', null, 400);
        }

        $active = ($status === 'active') ? 1 : 0;

        try {
            $exists = $pdo->prepare("SELECT id FROM captures WHERE id = ?");
            $exists->execute([$id]);
            if (!$exists->fetch()) {
                _jsonResponse(false, 'Capture introuvable', null, 404);
            }

            $pdo->prepare("UPDATE captures SET status = ?, active = ?, actif = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$status, $active, $active, $id]);

            _jsonResponse(true, 'Statut mis à jour.', ['id' => $id, 'status' => $status]);
        } catch (PDOException $e) {
            _jsonResponse(false, 'Erreur SQL : ' . $e->getMessage(), null, 500);
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  DUPLICATE — Dupliquer une page de capture
    // ──────────────────────────────────────────────────────────
    case 'duplicate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            _jsonResponse(false, 'Méthode non autorisée', null, 405);
        }

        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            _jsonResponse(false, 'ID invalide', null, 400);
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM captures WHERE id = ?");
            $stmt->execute([$id]);
            $original = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            _jsonResponse(false, 'Erreur SQL : ' . $e->getMessage(), null, 500);
        }

        if (!$original) {
            _jsonResponse(false, 'Capture introuvable', null, 404);
        }

        // Préparer la copie
        $newTitre = ($original['titre'] ?? 'Sans titre') . ' (copie)';
        $newSlug  = ($original['slug'] ?? 'copie') . '-copie-' . time();

        // Colonnes à copier (exclure id, created_at, updated_at, vues, conversions, taux)
        $copyFields = [
            'titre', 'slug', 'description', 'headline', 'sous_titre',
            'contenu', 'image_url', 'cta_text', 'page_merci_url',
            'guide_ids', 'champs_formulaire', 'type', 'template',
        ];

        $values = [];
        $colsList = [];
        $placeholders = [];

        foreach ($copyFields as $field) {
            if (!array_key_exists($field, $original)) continue;
            $colsList[] = "`{$field}`";
            $placeholders[] = '?';
            if ($field === 'titre') {
                $values[] = $newTitre;
            } elseif ($field === 'slug') {
                $values[] = $newSlug;
            } else {
                $values[] = $original[$field];
            }
        }

        // Ajouter les champs de statut (toujours inactif pour la copie)
        $colsList[]     = '`status`';
        $placeholders[] = '?';
        $values[]       = 'inactive';

        $colsList[]     = '`active`';
        $placeholders[] = '?';
        $values[]       = 0;

        $colsList[]     = '`actif`';
        $placeholders[] = '?';
        $values[]       = 0;

        $colsList[]     = '`vues`';
        $placeholders[] = '?';
        $values[]       = 0;

        $colsList[]     = '`conversions`';
        $placeholders[] = '?';
        $values[]       = 0;

        $colsList[]     = '`taux_conversion`';
        $placeholders[] = '?';
        $values[]       = 0;

        $colsList[]     = '`created_at`';
        $placeholders[] = 'NOW()';

        try {
            $cols = implode(', ', $colsList);
            $ph   = implode(', ', $placeholders);
            $pdo->prepare("INSERT INTO captures ({$cols}) VALUES ({$ph})")
                ->execute($values);
            $newId = (int)$pdo->lastInsertId();
            _jsonResponse(true, 'Page de capture dupliquée avec succès.', ['id' => $newId]);
        } catch (PDOException $e) {
            _jsonResponse(false, 'Erreur SQL : ' . $e->getMessage(), null, 500);
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  STATS — Récupérer les stats (vues, conversions, taux)
    // ──────────────────────────────────────────────────────────
    case 'stats':
        $id     = (int)($input['id'] ?? $_GET['id'] ?? 0);
        $period = in_array($input['period'] ?? $_GET['period'] ?? '30', ['7', '30', '90'])
                  ? (int)($input['period'] ?? $_GET['period'] ?? 30)
                  : 30;

        if ($id <= 0) {
            _jsonResponse(false, 'ID invalide', null, 400);
        }

        // Charger la capture
        try {
            $stmt = $pdo->prepare("SELECT * FROM captures WHERE id = ?");
            $stmt->execute([$id]);
            $capture = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            _jsonResponse(false, 'Erreur SQL : ' . $e->getMessage(), null, 500);
        }

        if (!$capture) {
            _jsonResponse(false, 'Capture introuvable', null, 404);
        }

        // Stats cumulées
        $totalVues = (int)($capture['vues']              ?? 0);
        $totalConv = (int)($capture['conversions']        ?? 0);
        $tauxMoyen = (float)($capture['taux_conversion']  ?? 0);

        // Stats journalières
        $dailyStats = [];
        $statsAvail = false;
        try {
            $pdo->query("SELECT 1 FROM captures_stats LIMIT 1");
            $statsAvail = true;
        } catch (PDOException $e) {}

        if ($statsAvail) {
            try {
                $rows = $pdo->prepare("
                    SELECT
                        date,
                        COALESCE(SUM(vues), 0)          AS vues,
                        COALESCE(SUM(conversions), 0)   AS conversions,
                        COALESCE(MAX(taux_conversion),0) AS taux
                    FROM captures_stats
                    WHERE capture_id = ?
                      AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                    GROUP BY date
                    ORDER BY date ASC
                ");
                $rows->execute([$id, $period]);
                $rawStats = $rows->fetchAll(PDO::FETCH_ASSOC);

                $dateMap = [];
                foreach ($rawStats as $r) $dateMap[$r['date']] = $r;

                for ($i = $period - 1; $i >= 0; $i--) {
                    $d = date('Y-m-d', strtotime("-$i days"));
                    $dailyStats[] = [
                        'date'        => $d,
                        'vues'        => (int)($dateMap[$d]['vues']        ?? 0),
                        'conversions' => (int)($dateMap[$d]['conversions'] ?? 0),
                        'taux'        => (float)($dateMap[$d]['taux']      ?? 0),
                    ];
                }
            } catch (PDOException $e) {}
        }

        // Meilleur jour
        $bestDay = null;
        if (!empty($dailyStats)) {
            $sorted = $dailyStats;
            usort($sorted, fn($a, $b) => $b['conversions'] <=> $a['conversions']);
            $bestDay = $sorted[0]['conversions'] > 0 ? $sorted[0] : null;
        }

        // Jours actif
        $joursActif = 0;
        if (!empty($capture['created_at'])) {
            $diff = (new DateTime())->diff(new DateTime($capture['created_at']));
            $joursActif = max(1, $diff->days);
        }

        _jsonResponse(true, 'OK', [
            'capture'      => [
                'id'     => (int)$capture['id'],
                'titre'  => $capture['titre'] ?? '',
                'slug'   => $capture['slug']  ?? '',
                'type'   => $capture['type']  ?? '',
                'status' => $capture['status'] ?? '',
            ],
            'totals'       => [
                'vues'            => $totalVues,
                'conversions'     => $totalConv,
                'taux_conversion' => $tauxMoyen,
                'vues_par_jour'   => $joursActif > 0 ? round($totalVues / $joursActif, 1) : 0,
                'jours_actif'     => $joursActif,
            ],
            'best_day'     => $bestDay,
            'daily_stats'  => $dailyStats,
            'period'       => $period,
            'stats_available' => $statsAvail,
        ]);
        break;

    // ──────────────────────────────────────────────────────────
    //  BULK_DELETE — Suppression groupée
    // ──────────────────────────────────────────────────────────
    case 'bulk_delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            _jsonResponse(false, 'Méthode non autorisée', null, 405);
        }

        $idsRaw = $input['ids'] ?? '';
        $ids = is_array($idsRaw) ? $idsRaw : (json_decode($idsRaw, true) ?: []);
        $ids = array_filter(array_map('intval', $ids), fn($v) => $v > 0);

        if (empty($ids)) {
            _jsonResponse(false, 'Aucun ID fourni', null, 400);
        }

        try {
            $pdo->beginTransaction();
            $ph = implode(',', array_fill(0, count($ids), '?'));
            try {
                $pdo->prepare("DELETE FROM captures_stats WHERE capture_id IN ({$ph})")->execute($ids);
            } catch (PDOException $e) {}
            $pdo->prepare("DELETE FROM captures WHERE id IN ({$ph})")->execute($ids);
            $pdo->commit();
            _jsonResponse(true, count($ids) . ' capture(s) supprimée(s).', ['count' => count($ids)]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            _jsonResponse(false, 'Erreur SQL : ' . $e->getMessage(), null, 500);
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  BULK_STATUS — Changement de statut groupé
    // ──────────────────────────────────────────────────────────
    case 'bulk_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            _jsonResponse(false, 'Méthode non autorisée', null, 405);
        }

        $idsRaw = $input['ids'] ?? '';
        $ids = is_array($idsRaw) ? $idsRaw : (json_decode($idsRaw, true) ?: []);
        $ids = array_filter(array_map('intval', $ids), fn($v) => $v > 0);
        $status = $input['status'] ?? '';

        if (empty($ids)) {
            _jsonResponse(false, 'Aucun ID fourni', null, 400);
        }
        if (!in_array($status, ['active', 'inactive', 'archived'])) {
            _jsonResponse(false, 'Statut invalide', null, 400);
        }

        $active = ($status === 'active') ? 1 : 0;

        try {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("UPDATE captures SET status = ?, active = ?, actif = ?, updated_at = NOW() WHERE id IN ({$ph})")
                ->execute([$status, $active, $active, ...$ids]);
            _jsonResponse(true, count($ids) . ' capture(s) mise(s) à jour.', ['count' => count($ids)]);
        } catch (PDOException $e) {
            _jsonResponse(false, 'Erreur SQL : ' . $e->getMessage(), null, 500);
        }
        break;

    // ──────────────────────────────────────────────────────────
    //  ACTION INCONNUE
    // ──────────────────────────────────────────────────────────
    default:
        _jsonResponse(false, 'Action non reconnue : ' . $action, null, 400);
        break;
}

// ══════════════════════════════════════════════════════════════
//  Helper — Réponse JSON unifiée
// ══════════════════════════════════════════════════════════════
function _jsonResponse(bool $success, string $message = '', $data = null, int $code = 200, array $errors = []): void
{
    http_response_code($code);
    $out = ['success' => $success, 'message' => $message];
    if ($data !== null) $out['data'] = $data;
    if ($errors)        $out['errors'] = $errors;
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}
