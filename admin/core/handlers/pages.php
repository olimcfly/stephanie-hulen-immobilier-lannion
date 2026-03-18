<?php
/**
 * API Handler: pages
 * Called via: /admin/api/router.php?module=pages&action=...
 * Table: pages
 *
 * Supported actions:
 *   list           - Liste paginee + stats (GET)
 *   get            - Detail page par ID (GET)
 *   create         - Creer une page (POST)
 *   update         - Modifier une page (POST)
 *   delete         - Supprimer une page (POST)
 *   toggle_status  - Changer statut draft/published/archived (POST)
 *   duplicate      - Dupliquer une page (POST)
 *   bulk_delete    - Suppression groupee (POST)
 *   bulk_status    - Changement statut groupe (POST)
 *   bulk_visibility - Changement visibilite groupee (POST)
 *   check_slug     - Verifier disponibilite slug (GET)
 *   autosave       - Sauvegarde automatique (POST)
 *   reorder        - Modifier l'ordre (POST)
 */

$action = CURRENT_ACTION;

// Merge input sources: JSON body, $_POST, $_GET
$input = $_POST;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['action'])) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $input = array_merge($input, $json);
    }
}

// --- Table detection ---
$tableName = 'pages';
$tableFound = false;
foreach (['pages', 'cms_pages'] as $candidate) {
    try {
        $pdo->query("SELECT 1 FROM `{$candidate}` LIMIT 1");
        $tableName = $candidate;
        $tableFound = true;
        break;
    } catch (PDOException $e) { continue; }
}

if (!$tableFound) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Table pages introuvable']);
    exit;
}

// --- Available columns ---
$existingCols = [];
try {
    $existingCols = array_map('strtolower', $pdo->query("SHOW COLUMNS FROM `{$tableName}`")->fetchAll(PDO::FETCH_COLUMN));
} catch (PDOException $e) {}

// --- Helper functions ---

function pagesSlug($title) {
    $slug = mb_strtolower($title, 'UTF-8');
    $tr = ['a'=>'a','a'=>'a','a'=>'a','e'=>'e','e'=>'e','e'=>'e','e'=>'e',
           'i'=>'i','i'=>'i','o'=>'o','o'=>'o','u'=>'u','u'=>'u','u'=>'u',
           'y'=>'y','c'=>'c','n'=>'n','oe'=>'oe','ae'=>'ae',"'"=>'-'];
    $slug = strtr($slug, $tr);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    return trim($slug, '-');
}

function pagesUniqueSlug($pdo, $table, $slug, $excludeId = null) {
    $base = $slug;
    $n = 1;
    while (true) {
        $sql = "SELECT id FROM `{$table}` WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) return $slug;
        $slug = $base . '-' . $n++;
        if ($n > 50) return $base . '-' . bin2hex(random_bytes(3));
    }
}

function pagesWordCount($html) {
    $text = strip_tags($html ?? '');
    $text = preg_replace('/\s+/', ' ', trim($text));
    return $text ? str_word_count($text) : 0;
}

function pagesResponse($ok, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(['success' => $ok], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function pagesSafeUpdate($pdo, $table, $id, $data, $existingCols) {
    $sets = []; $vals = [];
    foreach ($data as $col => $val) {
        if (in_array(strtolower($col), $existingCols) && strtolower($col) !== 'id') {
            $sets[] = "`{$col}` = ?";
            $vals[] = $val;
        }
    }
    if (empty($sets)) return false;
    $vals[] = $id;
    $sql = "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($vals);
}

function pagesSafeInsert($pdo, $table, $data, $existingCols) {
    $cols = []; $vals = []; $phs = [];
    foreach ($data as $col => $val) {
        if (in_array(strtolower($col), $existingCols)) {
            $cols[] = "`{$col}`";
            $vals[] = $val;
            $phs[] = '?';
        }
    }
    if (empty($cols)) return false;
    $sql = "INSERT INTO `{$table}` (" . implode(',', $cols) . ") VALUES (" . implode(',', $phs) . ")";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($vals);
    return (int)$pdo->lastInsertId();
}

/**
 * Parse IDs from input: accepts JSON string or array.
 */
function parseIds($raw) {
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return array_filter(array_map('intval', $decoded), fn($id) => $id > 0);
    }
    if (is_array($raw)) return array_filter(array_map('intval', $raw), fn($id) => $id > 0);
    return [];
}

// ============================================================
// DISPATCH
// ============================================================

try {

switch ($action) {

    // ─── LIST ───
    case 'list':
        $status  = $_GET['status'] ?? 'all';
        $search  = $_GET['search'] ?? $_GET['q'] ?? '';
        $pg      = max(1, (int)($_GET['pg'] ?? $_GET['page_num'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 50)));

        $where = []; $params = [];
        if ($status !== 'all' && in_array($status, ['draft','published','archived'])) {
            $where[] = "status = ?"; $params[] = $status;
        }
        if ($search) {
            $where[] = "(title LIKE ? OR slug LIKE ?)";
            $params = array_merge($params, ["%{$search}%", "%{$search}%"]);
        }
        $wClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$tableName}` {$wClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($pg - 1) * $perPage;
        $selectCols = ['id','title','slug','status','created_at','updated_at'];
        foreach (['seo_score','semantic_score','is_file_based','file_path','template','published_at','word_count','visibility','google_indexed'] as $oc) {
            if (in_array($oc, $existingCols)) $selectCols[] = $oc;
        }
        $selStr = implode(',', array_map(fn($c) => "`{$c}`", $selectCols));

        $sql = "SELECT {$selStr} FROM `{$tableName}` {$wClause} ORDER BY updated_at DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        pagesResponse(true, [
            'pages' => $pages,
            'pagination' => ['total' => $total, 'page' => $pg, 'per_page' => $perPage, 'total_pages' => max(1, ceil($total / $perPage))],
        ]);
        break;

    // ─── GET ───
    case 'get':
        $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) pagesResponse(false, ['error' => 'ID invalide'], 400);

        $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE id = ?");
        $stmt->execute([$id]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$page) pagesResponse(false, ['error' => 'Page introuvable'], 404);

        pagesResponse(true, ['page' => $page]);
        break;

    // ─── DELETE ───
    case 'delete':
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) pagesResponse(false, ['error' => 'ID invalide'], 400);

        $check = $pdo->prepare("SELECT id, title FROM `{$tableName}` WHERE id = ?");
        $check->execute([$id]);
        $page = $check->fetch(PDO::FETCH_ASSOC);
        if (!$page) pagesResponse(false, ['error' => 'Page introuvable'], 404);

        $pdo->prepare("DELETE FROM `{$tableName}` WHERE id = ?")->execute([$id]);

        try {
            $pdo->prepare("DELETE FROM seo_scores WHERE context = 'landing' AND entity_id = ?")->execute([$id]);
        } catch (PDOException $e) {}

        pagesResponse(true, ['message' => "Page supprimee", 'deleted_id' => $id]);
        break;

    // ─── TOGGLE STATUS ───
    case 'toggle_status':
        $id = (int)($input['id'] ?? 0);
        $newStatus = $input['status'] ?? '';
        if ($id <= 0 || !in_array($newStatus, ['draft','published','archived'])) {
            pagesResponse(false, ['error' => 'Parametres invalides'], 400);
        }

        $sql = "UPDATE `{$tableName}` SET status = ?";
        $params = [$newStatus];
        if ($newStatus === 'published' && in_array('published_at', $existingCols)) {
            $sql .= ", published_at = COALESCE(published_at, NOW())";
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $pdo->prepare($sql)->execute($params);

        pagesResponse(true, ['message' => 'Statut mis a jour', 'status' => $newStatus]);
        break;

    // ─── DUPLICATE ───
    case 'duplicate':
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) pagesResponse(false, ['error' => 'ID invalide'], 400);

        $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE id = ?");
        $stmt->execute([$id]);
        $orig = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orig) pagesResponse(false, ['error' => 'Page introuvable'], 404);

        $newTitle = $orig['title'] . ' (copie)';
        $newSlug = pagesUniqueSlug($pdo, $tableName, ($orig['slug'] ?? '') . '-copie');

        $skip = ['id','created_at','updated_at','published_at'];
        $copyData = [];
        foreach ($orig as $col => $val) {
            if (in_array(strtolower($col), $skip)) continue;
            $copyData[$col] = $val;
        }
        $copyData['title'] = $newTitle;
        $copyData['slug'] = $newSlug;
        $copyData['status'] = 'draft';

        $newId = pagesSafeInsert($pdo, $tableName, $copyData, $existingCols);

        pagesResponse(true, ['message' => 'Page dupliquee', 'new_id' => $newId], 201);
        break;

    // ─── BULK DELETE ───
    case 'bulk_delete':
        $ids = parseIds($input['ids'] ?? []);
        if (empty($ids)) pagesResponse(false, ['error' => 'Aucune selection'], 400);

        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM `{$tableName}` WHERE id IN ({$ph})");
        $stmt->execute($ids);
        $affected = $stmt->rowCount();

        try {
            $pdo->prepare("DELETE FROM seo_scores WHERE context = 'landing' AND entity_id IN ({$ph})")->execute($ids);
        } catch (PDOException $e) {}

        pagesResponse(true, ['message' => "{$affected} page(s) supprimee(s)", 'affected' => $affected]);
        break;

    // ─── BULK STATUS ───
    case 'bulk_status':
        $ids = parseIds($input['ids'] ?? []);
        $newStatus = $input['status'] ?? '';
        if (empty($ids)) pagesResponse(false, ['error' => 'Aucune selection'], 400);
        if (!in_array($newStatus, ['draft','published','archived'])) {
            pagesResponse(false, ['error' => 'Statut invalide'], 400);
        }

        $ph = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE `{$tableName}` SET status = ?";
        $params = [$newStatus];
        if ($newStatus === 'published' && in_array('published_at', $existingCols)) {
            $sql .= ", published_at = COALESCE(published_at, NOW())";
        }
        $sql .= " WHERE id IN ({$ph})";
        $params = array_merge($params, $ids);
        $pdo->prepare($sql)->execute($params);

        pagesResponse(true, ['message' => count($ids) . ' page(s) mise(s) a jour', 'affected' => count($ids)]);
        break;

    // ─── BULK VISIBILITY ───
    case 'bulk_visibility':
        $ids = parseIds($input['ids'] ?? []);
        $visibility = $input['visibility'] ?? '';
        if (empty($ids)) pagesResponse(false, ['error' => 'Aucune selection'], 400);
        if (!in_array($visibility, ['public','private'])) {
            pagesResponse(false, ['error' => 'Visibilite invalide'], 400);
        }
        if (!in_array('visibility', $existingCols)) {
            pagesResponse(false, ['error' => 'Colonne visibility non disponible'], 400);
        }

        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE `{$tableName}` SET visibility = ? WHERE id IN ({$ph})")->execute(array_merge([$visibility], $ids));

        pagesResponse(true, ['message' => count($ids) . ' page(s) mise(s) a jour', 'affected' => count($ids)]);
        break;

    // ─── CHECK SLUG (GET) ───
    case 'check_slug':
        $slug = pagesSlug($_GET['slug'] ?? $input['slug'] ?? '');
        $excludeId = (int)($_GET['exclude_id'] ?? $input['exclude_id'] ?? 0);
        if (!$slug) pagesResponse(false, ['error' => 'Slug vide'], 400);

        $sql = "SELECT id FROM `{$tableName}` WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $exists = (bool)$stmt->fetch();

        pagesResponse(true, [
            'slug' => $slug,
            'available' => !$exists,
            'suggestion' => $exists ? pagesUniqueSlug($pdo, $tableName, $slug, $excludeId) : $slug
        ]);
        break;

    // ─── AUTOSAVE ───
    case 'autosave':
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) pagesResponse(false, ['error' => 'ID invalide'], 400);

        $content = $input['content'] ?? $input['html_content'] ?? '';
        $title   = trim(strip_tags($input['title'] ?? ''));
        $wc = pagesWordCount($content);

        $data = ['word_count' => $wc];
        if ($title) $data['title'] = $title;
        if (in_array('content', $existingCols)) $data['content'] = $content;
        if (in_array('html_content', $existingCols)) $data['html_content'] = $content;

        pagesSafeUpdate($pdo, $tableName, $id, $data, $existingCols);
        pagesResponse(true, ['message' => 'Autosave OK', 'saved_at' => date('H:i:s')]);
        break;

    // ─── REORDER ───
    case 'reorder':
        $order = $input['order'] ?? [];
        if (!is_array($order)) pagesResponse(false, ['error' => 'Format invalide'], 400);
        $stmt = $pdo->prepare("UPDATE `{$tableName}` SET sort_order = ? WHERE id = ?");
        foreach ($order as $pos => $id) { $stmt->execute([(int)$pos, (int)$id]); }
        pagesResponse(true, ['message' => 'Ordre mis a jour']);
        break;

    // ─── CREATE ───
    case 'create':
        $title = trim(strip_tags($input['title'] ?? ''));
        if (!$title) pagesResponse(false, ['error' => 'Titre obligatoire'], 400);

        $slug = $input['slug'] ?? '';
        $slug = $slug ? pagesSlug($slug) : pagesSlug($title);
        $slug = pagesUniqueSlug($pdo, $tableName, $slug);

        $content = $input['content'] ?? $input['html_content'] ?? '';
        $status  = in_array($input['status'] ?? '', ['draft','published','archived']) ? $input['status'] : 'draft';

        $data = [
            'title'        => $title,
            'slug'         => $slug,
            'content'      => $content,
            'html_content' => $content,
            'status'       => $status,
            'word_count'   => pagesWordCount($content),
        ];

        if (isset($input['meta_title']))       $data['meta_title']       = trim(strip_tags($input['meta_title']));
        if (isset($input['meta_description'])) $data['meta_description'] = trim(strip_tags($input['meta_description']));
        if (isset($input['template']))         $data['template']         = trim(strip_tags($input['template']));
        if (isset($input['visibility']))       $data['visibility']       = in_array($input['visibility'], ['public','private']) ? $input['visibility'] : 'public';
        if ($status === 'published')           $data['published_at']     = date('Y-m-d H:i:s');

        $newId = pagesSafeInsert($pdo, $tableName, $data, $existingCols);
        pagesResponse(true, ['message' => 'Page creee', 'id' => $newId], 201);
        break;

    // ─── UPDATE ───
    case 'update':
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) pagesResponse(false, ['error' => 'ID invalide'], 400);

        $check = $pdo->prepare("SELECT id, status FROM `{$tableName}` WHERE id = ?");
        $check->execute([$id]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);
        if (!$existing) pagesResponse(false, ['error' => 'Page introuvable'], 404);

        $title = trim(strip_tags($input['title'] ?? ''));
        if (!$title) pagesResponse(false, ['error' => 'Titre obligatoire'], 400);

        $slug = $input['slug'] ?? '';
        $slug = $slug ? pagesSlug($slug) : pagesSlug($title);
        $slug = pagesUniqueSlug($pdo, $tableName, $slug, $id);

        $content = $input['content'] ?? $input['html_content'] ?? '';
        $status  = in_array($input['status'] ?? '', ['draft','published','archived']) ? $input['status'] : $existing['status'];

        $data = [
            'title'        => $title,
            'slug'         => $slug,
            'content'      => $content,
            'html_content' => $content,
            'status'       => $status,
            'word_count'   => pagesWordCount($content),
        ];

        if (isset($input['meta_title']))       $data['meta_title']       = trim(strip_tags($input['meta_title']));
        if (isset($input['meta_description'])) $data['meta_description'] = trim(strip_tags($input['meta_description']));
        if (isset($input['template']))         $data['template']         = trim(strip_tags($input['template']));
        if (isset($input['visibility']))       $data['visibility']       = in_array($input['visibility'], ['public','private']) ? $input['visibility'] : null;
        if (isset($data['visibility']) && $data['visibility'] === null) unset($data['visibility']);

        if ($status === 'published' && $existing['status'] !== 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        pagesSafeUpdate($pdo, $tableName, $id, $data, $existingCols);
        pagesResponse(true, ['message' => 'Page mise a jour']);
        break;

    // ─── DEFAULT ───
    default:
        pagesResponse(false, ['error' => "Action '{$action}' non supportee"], 400);
}

} catch (PDOException $e) {
    error_log("Handler pages [PDO]: " . $e->getMessage());
    pagesResponse(false, ['error' => 'Erreur base de donnees'], 500);
} catch (Exception $e) {
    error_log("Handler pages: " . $e->getMessage());
    pagesResponse(false, ['error' => 'Erreur serveur'], 500);
}
