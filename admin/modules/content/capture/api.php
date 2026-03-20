<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE CAPTURES — API unifiée  v1.0
 *  /admin/modules/content/capture/api.php
 *
 *  Point d'entrée AJAX unique pour toutes les actions CRUD
 *  sur les pages de capture.
 *
 *  Actions supportées :
 *    save       — Créer ou mettre à jour une capture
 *    delete     — Supprimer une capture (+ stats associées)
 *    get        — Récupérer une capture par ID
 *    list       — Liste avec pagination
 *    toggle     — Activer/désactiver une capture
 *    duplicate  — Dupliquer une capture
 *    stats      — Récupérer les stats d'une capture
 *
 *  Input  : POST (FormData ou JSON)  |  GET pour actions de lecture
 *  Output : JSON { success, id?, message?, error?, errors? }
 * ══════════════════════════════════════════════════════════════
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    _capApiRespond(false, null, 'Non authentifié', 401);
}

// ─── DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) {
        $initFile = __DIR__ . '/../../../includes/init.php';
        $configFile = __DIR__ . '/../../../config/config.php';
        if (file_exists($initFile)) {
            require_once $initFile;
            try { $pdo = getDB(); } catch (Exception $e) {
                _capApiRespond(false, null, 'Erreur BD', 500);
            }
        } elseif (file_exists($configFile)) {
            require_once $configFile;
            try {
                $pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER, DB_PASS,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
            } catch (PDOException $e) {
                _capApiRespond(false, null, 'DB: ' . $e->getMessage(), 500);
            }
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

if (!$action) {
    _capApiRespond(false, null, 'Action manquante', 400);
}

// ════════════════════════════════════════════════════════════
// DISPATCH
// ════════════════════════════════════════════════════════════

try {

switch ($action) {

    // ─── SAVE (Create / Update) ───────────────────────────
    case 'save':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            _capApiRespond(false, null, 'Méthode non autorisée', 405);
        }

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
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower($slug));

        if (!in_array($type, ['estimation', 'contact', 'newsletter', 'guide'])) {
            $errors['type'] = 'Type invalide.';
        }
        if (!in_array($status_val, ['active', 'inactive', 'archived'])) {
            $errors['status'] = 'Statut invalide.';
        }
        if (!empty($errors)) {
            _capApiRespond(false, null, 'Données invalides', 422, $errors);
        }

        // Unicité du slug
        $slugCheck = $pdo->prepare("SELECT id FROM captures WHERE slug = ? AND id != ?");
        $slugCheck->execute([$slug, $id]);
        if ($slugCheck->fetch()) {
            $slug = $slug . '-' . time();
        }

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

        if ($id > 0) {
            // UPDATE
            $exists = $pdo->prepare("SELECT id FROM captures WHERE id = ?");
            $exists->execute([$id]);
            if (!$exists->fetch()) {
                _capApiRespond(false, null, 'Capture introuvable', 404);
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

        _capApiRespond(true, $resultId, $message);
        break;

    // ─── DELETE ───────────────────────────────────────────
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            _capApiRespond(false, null, 'Méthode non autorisée', 405);
        }

        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            _capApiRespond(false, null, 'ID invalide', 400);
        }

        // Vérifier que la capture existe
        $check = $pdo->prepare("SELECT id, titre FROM captures WHERE id = ?");
        $check->execute([$id]);
        $rec = $check->fetch(PDO::FETCH_ASSOC);
        if (!$rec) {
            _capApiRespond(false, null, 'Capture introuvable', 404);
        }

        $pdo->beginTransaction();
        try {
            // Supprimer les stats journalières
            try {
                $pdo->prepare("DELETE FROM captures_stats WHERE capture_id = ?")->execute([$id]);
            } catch (PDOException $e) {} // table optionnelle

            // Supprimer la capture
            $pdo->prepare("DELETE FROM captures WHERE id = ?")->execute([$id]);
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            _capApiRespond(false, null, 'Erreur SQL : ' . $e->getMessage(), 500);
        }

        _capApiRespond(true, $id, 'Page de capture supprimée avec succès.');
        break;

    // ─── GET ──────────────────────────────────────────────
    case 'get':
        $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            _capApiRespond(false, null, 'ID invalide', 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM captures WHERE id = ?");
        $stmt->execute([$id]);
        $capture = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$capture) {
            _capApiRespond(false, null, 'Capture introuvable', 404);
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'capture' => $capture], JSON_UNESCAPED_UNICODE);
        exit;

    // ─── LIST ─────────────────────────────────────────────
    case 'list':
        $status  = $_GET['status'] ?? $input['status'] ?? 'all';
        $type    = $_GET['type']   ?? $input['type_filter'] ?? 'all';
        $search  = $_GET['q']      ?? $input['q'] ?? '';
        $pg      = max(1, (int)($_GET['pg'] ?? $input['pg'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? $input['per_page'] ?? 20)));

        $where = []; $params = [];
        if ($status !== 'all' && in_array($status, ['active', 'inactive', 'archived'])) {
            $where[] = "status = ?"; $params[] = $status;
        }
        if ($type !== 'all' && in_array($type, ['estimation', 'contact', 'newsletter', 'guide'])) {
            $where[] = "type = ?"; $params[] = $type;
        }
        if ($search) {
            $where[] = "(titre LIKE ? OR slug LIKE ? OR description LIKE ?)";
            $params = array_merge($params, ["%{$search}%", "%{$search}%", "%{$search}%"]);
        }
        $wClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM captures {$wClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($pg - 1) * $perPage;
        $stmt = $pdo->prepare("SELECT * FROM captures {$wClause} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        $captures = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode([
            'success'    => true,
            'captures'   => $captures,
            'pagination' => [
                'total'       => $total,
                'page'        => $pg,
                'per_page'    => $perPage,
                'total_pages' => max(1, ceil($total / $perPage)),
            ],
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // ─── TOGGLE (activer/désactiver) ──────────────────────
    case 'toggle':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            _capApiRespond(false, null, 'Méthode non autorisée', 405);
        }

        $id = (int)($input['id'] ?? 0);
        $newStatus = $input['status'] ?? '';
        if ($id <= 0 || !in_array($newStatus, ['active', 'inactive'])) {
            _capApiRespond(false, null, 'Paramètres invalides', 400);
        }

        $active = ($newStatus === 'active') ? 1 : 0;
        $pdo->prepare("UPDATE captures SET status = ?, active = ?, actif = ? WHERE id = ?")
            ->execute([$newStatus, $active, $active, $id]);

        _capApiRespond(true, $id, 'Statut mis à jour.');
        break;

    // ─── DUPLICATE ────────────────────────────────────────
    case 'duplicate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            _capApiRespond(false, null, 'Méthode non autorisée', 405);
        }

        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            _capApiRespond(false, null, 'ID invalide', 400);
        }

        $stmt = $pdo->prepare("SELECT * FROM captures WHERE id = ?");
        $stmt->execute([$id]);
        $orig = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orig) {
            _capApiRespond(false, null, 'Capture introuvable', 404);
        }

        $newTitre = $orig['titre'] . ' (copie)';
        $newSlug  = $orig['slug'] . '-copie-' . time();

        $skip = ['id', 'created_at', 'updated_at', 'vues', 'conversions', 'taux_conversion', 'last_conversion_at'];
        $copyData = [];
        foreach ($orig as $col => $val) {
            if (in_array($col, $skip)) continue;
            $copyData[$col] = $val;
        }
        $copyData['titre']  = $newTitre;
        $copyData['slug']   = $newSlug;
        $copyData['status'] = 'inactive';
        $copyData['active'] = 0;
        $copyData['actif']  = 0;

        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($copyData)));
        $ph   = implode(', ', array_fill(0, count($copyData), '?'));
        $pdo->prepare("INSERT INTO captures ($cols, created_at) VALUES ($ph, NOW())")
            ->execute(array_values($copyData));
        $newId = (int)$pdo->lastInsertId();

        _capApiRespond(true, $newId, 'Capture dupliquée avec succès.');
        break;

    // ─── STATS ────────────────────────────────────────────
    case 'stats':
        $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) {
            _capApiRespond(false, null, 'ID invalide', 400);
        }

        $capture = $pdo->prepare("SELECT id, titre, vues, conversions, taux_conversion FROM captures WHERE id = ?");
        $capture->execute([$id]);
        $cap = $capture->fetch(PDO::FETCH_ASSOC);
        if (!$cap) {
            _capApiRespond(false, null, 'Capture introuvable', 404);
        }

        $daily = [];
        try {
            $stmt = $pdo->prepare("SELECT date, vues, conversions FROM captures_stats WHERE capture_id = ? ORDER BY date DESC LIMIT 30");
            $stmt->execute([$id]);
            $daily = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'capture' => $cap,
            'daily'   => $daily,
        ], JSON_UNESCAPED_UNICODE);
        exit;

    // ─── DEFAULT ──────────────────────────────────────────
    default:
        _capApiRespond(false, null, 'Action inconnue : ' . $action, 400);
}

} catch (PDOException $e) {
    error_log("Capture API [PDO]: " . $e->getMessage());
    _capApiRespond(false, null, 'Erreur base de données', 500);
} catch (Exception $e) {
    error_log("Capture API: " . $e->getMessage());
    _capApiRespond(false, null, 'Erreur serveur', 500);
}

// ─── Helper réponse ───────────────────────────────────────
function _capApiRespond(bool $success, ?int $id = null, string $message = '', int $code = 200, array $errors = []): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    $out = ['success' => $success, 'message' => $message];
    if ($id !== null) $out['id'] = $id;
    if ($errors)      $out['errors'] = $errors;
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}
