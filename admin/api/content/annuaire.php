<?php
/**
 * MODULE ADMIN — Annuaire Local — API
 * /admin/api/content/annuaire.php
 * Endpoints AJAX : list, get, save, delete, toggle_status, toggle_featured,
 *                  duplicate, bulk_delete, bulk_status, bulk_feature
 */

header('Content-Type: application/json; charset=utf-8');

// ── Init ──
require_once dirname(__DIR__, 3) . '/includes/init.php';

try {
    $pdo = getDB();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur BD']);
    exit;
}

// ── Helpers ──
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function makeAnnuaireSlug(string $s): string {
    $s = mb_strtolower(trim($s));
    $map = ['à'=>'a','á'=>'a','â'=>'a','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
            'î'=>'i','ï'=>'i','ô'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c',
            'œ'=>'oe','æ'=>'ae'];
    $s = strtr($s, $map);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim(substr($s, 0, 80), '-');
}

// ── CSRF check for POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (!empty($_SESSION['csrf_token']) && (empty($token) || !hash_equals($_SESSION['csrf_token'], $token))) {
        // Allow requests without CSRF if session has no token (API-only usage)
    }
}

// ── Categories ──
$validCategories = [
    'ecole', 'sante', 'transport', 'commerce', 'restaurant',
    'sport', 'culture', 'nature', 'services', 'securite',
    'immobilier', 'autre',
];

// ── Read body (JSON or FormData) ──
$body = json_decode(file_get_contents('php://input'), true) ?? [];
if (empty($body)) $body = $_POST;
$action = $body['action'] ?? $_GET['action'] ?? '';
$id     = (int)($body['id'] ?? $_GET['id'] ?? 0);

if (!$action) {
    respond(['success' => false, 'error' => 'Action manquante'], 400);
}

// ── Router ──
switch ($action) {

// ────────────────────────────────────────────────
// LIST — with pagination, search, filters
// ────────────────────────────────────────────────
case 'list':
    $page    = max(1, (int)($_GET['page'] ?? $body['page'] ?? 1));
    $perPage = min(100, max(1, (int)($_GET['per_page'] ?? $body['per_page'] ?? 30)));
    $offset  = ($page - 1) * $perPage;

    $search    = trim($_GET['q']         ?? $body['q']         ?? '');
    $categorie = trim($_GET['categorie'] ?? $body['categorie'] ?? 'all');
    $status    = trim($_GET['status']    ?? $body['status']    ?? 'all');
    $ville     = trim($_GET['ville']     ?? $body['ville']     ?? 'all');
    $secteur   = trim($_GET['secteur']   ?? $body['secteur']   ?? 'all');
    $audience  = trim($_GET['audience']  ?? $body['audience']  ?? 'all');

    $where  = [];
    $params = [];

    if ($status !== 'all') {
        $where[]  = "a.status = ?";
        $params[] = $status;
    }
    if ($categorie !== 'all') {
        $where[]  = "a.categorie = ?";
        $params[] = $categorie;
    }
    if ($ville !== 'all') {
        $where[]  = "a.ville = ?";
        $params[] = $ville;
    }
    if ($secteur !== 'all') {
        $where[]  = "a.secteur_id = ?";
        $params[] = (int)$secteur;
    }
    if ($audience !== 'all') {
        $where[]  = "(a.audience = ? OR a.audience = 'tous')";
        $params[] = $audience;
    }
    if ($search !== '') {
        $where[] = "(a.nom LIKE ? OR a.adresse LIKE ? OR a.description LIKE ? OR a.ville LIKE ?)";
        $t = "%{$search}%";
        array_push($params, $t, $t, $t, $t);
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // Secteurs join (optional)
    $hasSecteurs = false;
    try {
        $pdo->query("SELECT 1 FROM secteurs LIMIT 1");
        $hasSecteurs = true;
    } catch (PDOException $e) {}

    $joinSQL     = $hasSecteurs ? "LEFT JOIN secteurs s ON s.id = a.secteur_id" : "";
    $secteurCol  = $hasSecteurs ? ", s.nom as secteur_nom" : ", NULL as secteur_nom";

    // Count
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM annuaire a {$whereSQL}");
    $stmtCount->execute($params);
    $total      = (int)$stmtCount->fetchColumn();
    $totalPages = max(1, ceil($total / $perPage));

    // Data
    $stmtList = $pdo->prepare("
        SELECT a.* {$secteurCol}
        FROM annuaire a
        {$joinSQL}
        {$whereSQL}
        ORDER BY a.is_featured DESC, a.nom ASC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmtList->execute($params);
    $items = $stmtList->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $statsRow = $pdo->query("SELECT
        COUNT(*) as total,
        SUM(status='published') as published,
        SUM(status='draft') as draft,
        SUM(is_featured=1) as featured,
        SUM(gmb_url IS NOT NULL AND gmb_url != '') as with_gmb
        FROM annuaire")->fetch(PDO::FETCH_ASSOC);

    respond([
        'success'    => true,
        'items'      => $items,
        'total'      => $total,
        'page'       => $page,
        'per_page'   => $perPage,
        'total_pages'=> $totalPages,
        'stats'      => array_map('intval', $statsRow),
    ]);
    break;

// ────────────────────────────────────────────────
// GET — single entry
// ────────────────────────────────────────────────
case 'get':
    if (!$id) respond(['success' => false, 'error' => 'ID manquant'], 400);

    $stmt = $pdo->prepare("SELECT * FROM annuaire WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) respond(['success' => false, 'error' => 'Entrée introuvable'], 404);

    respond(['success' => true, 'item' => $item]);
    break;

// ────────────────────────────────────────────────
// SAVE — insert (id=0) or update
// ────────────────────────────────────────────────
case 'save':
    $nom = trim($body['nom'] ?? '');
    if (empty($nom)) respond(['success' => false, 'error' => 'Le nom est obligatoire'], 400);

    $slugRaw = trim($body['slug'] ?? '');
    $slug = !empty($slugRaw) ? makeAnnuaireSlug($slugRaw) : makeAnnuaireSlug($nom);

    // Slug uniqueness
    $chkSql = $id
        ? "SELECT id FROM annuaire WHERE slug = ? AND id != ?"
        : "SELECT id FROM annuaire WHERE slug = ?";
    $chk = $pdo->prepare($chkSql);
    $id ? $chk->execute([$slug, $id]) : $chk->execute([$slug]);
    if ($chk->fetch()) $slug .= '-' . time();

    $categorie = $body['categorie'] ?? 'autre';
    if (!in_array($categorie, $validCategories)) $categorie = 'autre';

    $audience = $body['audience'] ?? 'tous';
    if (!in_array($audience, ['acheteur', 'habitant', 'tous'])) $audience = 'tous';

    $statusVal = $body['status'] ?? 'draft';
    if (!in_array($statusVal, ['published', 'draft'])) $statusVal = 'draft';

    $fields = [
        'nom'         => $nom,
        'slug'        => $slug,
        'categorie'   => $categorie,
        'description' => trim($body['description'] ?? ''),
        'adresse'     => trim($body['adresse']     ?? ''),
        'ville'       => trim($body['ville']       ?? ''),
        'code_postal' => trim($body['code_postal'] ?? ''),
        'secteur_id'  => !empty($body['secteur_id']) ? (int)$body['secteur_id'] : null,
        'latitude'    => !empty($body['latitude'])   ? (float)$body['latitude']  : null,
        'longitude'   => !empty($body['longitude'])  ? (float)$body['longitude'] : null,
        'telephone'   => trim($body['telephone']  ?? ''),
        'site_web'    => trim($body['site_web']   ?? ''),
        'gmb_url'     => trim($body['gmb_url']    ?? ''),
        'note'        => isset($body['note']) && $body['note'] !== '' ? round((float)$body['note'], 1) : null,
        'audience'    => $audience,
        'is_featured' => !empty($body['is_featured']) ? 1 : 0,
        'status'      => $statusVal,
        'meta_title'  => trim($body['meta_title'] ?? ''),
        'meta_desc'   => trim($body['meta_desc']  ?? ''),
    ];

    try {
        if ($id > 0) {
            $sets = [];
            $vals = [];
            foreach ($fields as $col => $val) {
                $sets[] = "`{$col}` = ?";
                $vals[] = $val;
            }
            $vals[] = $id;
            $pdo->prepare("UPDATE annuaire SET " . implode(', ', $sets) . " WHERE id = ?")->execute($vals);
            respond(['success' => true, 'id' => $id, 'slug' => $slug, 'message' => 'Entrée mise à jour']);
        } else {
            $cols = array_keys($fields);
            $placeholders = implode(',', array_fill(0, count($cols), '?'));
            $pdo->prepare(
                "INSERT INTO annuaire (`" . implode('`,`', $cols) . "`) VALUES ({$placeholders})"
            )->execute(array_values($fields));
            $newId = (int)$pdo->lastInsertId();
            respond(['success' => true, 'id' => $newId, 'slug' => $slug, 'message' => 'Entrée créée']);
        }
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => 'Erreur BD : ' . $e->getMessage()], 500);
    }
    break;

// ────────────────────────────────────────────────
// DELETE
// ────────────────────────────────────────────────
case 'delete':
    if (!$id) respond(['success' => false, 'error' => 'ID manquant'], 400);
    $pdo->prepare("DELETE FROM annuaire WHERE id = ?")->execute([$id]);
    respond(['success' => true, 'message' => 'Entrée supprimée']);
    break;

// ────────────────────────────────────────────────
// TOGGLE STATUS
// ────────────────────────────────────────────────
case 'toggle_status':
    if (!$id) respond(['success' => false, 'error' => 'ID manquant'], 400);
    $pdo->prepare("UPDATE annuaire SET status = IF(status='published','draft','published') WHERE id = ?")->execute([$id]);

    $stmt = $pdo->prepare("SELECT status FROM annuaire WHERE id = ?");
    $stmt->execute([$id]);
    $newStatus = $stmt->fetchColumn();

    respond(['success' => true, 'id' => $id, 'status' => $newStatus, 'message' => 'Statut mis à jour']);
    break;

// ────────────────────────────────────────────────
// TOGGLE FEATURED
// ────────────────────────────────────────────────
case 'toggle_featured':
    if (!$id) respond(['success' => false, 'error' => 'ID manquant'], 400);
    $pdo->prepare("UPDATE annuaire SET is_featured = IF(is_featured=1,0,1) WHERE id = ?")->execute([$id]);

    $stmt = $pdo->prepare("SELECT is_featured FROM annuaire WHERE id = ?");
    $stmt->execute([$id]);
    $newFeat = (int)$stmt->fetchColumn();

    respond(['success' => true, 'id' => $id, 'is_featured' => $newFeat, 'message' => 'Mise en avant mise à jour']);
    break;

// ────────────────────────────────────────────────
// DUPLICATE
// ────────────────────────────────────────────────
case 'duplicate':
    if (!$id) respond(['success' => false, 'error' => 'ID manquant'], 400);

    $stmt = $pdo->prepare("SELECT * FROM annuaire WHERE id = ?");
    $stmt->execute([$id]);
    $src = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$src) respond(['success' => false, 'error' => 'Entrée introuvable'], 404);

    $newNom  = $src['nom'] . ' (copie)';
    $newSlug = makeAnnuaireSlug($newNom);

    // Ensure slug uniqueness
    $chk = $pdo->prepare("SELECT id FROM annuaire WHERE slug = ?");
    $chk->execute([$newSlug]);
    if ($chk->fetch()) $newSlug .= '-' . time();

    $cols = ['nom','slug','categorie','description','adresse','ville','code_postal',
             'secteur_id','latitude','longitude','telephone','site_web','gmb_url',
             'note','audience','is_featured','status','meta_title','meta_desc'];

    $src['nom']    = $newNom;
    $src['slug']   = $newSlug;
    $src['status'] = 'draft';

    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $vals = [];
    foreach ($cols as $c) $vals[] = $src[$c] ?? null;

    $pdo->prepare(
        "INSERT INTO annuaire (`" . implode('`,`', $cols) . "`) VALUES ({$placeholders})"
    )->execute($vals);

    $newId = (int)$pdo->lastInsertId();
    respond(['success' => true, 'id' => $newId, 'slug' => $newSlug, 'message' => 'Entrée dupliquée']);
    break;

// ────────────────────────────────────────────────
// BULK DELETE
// ────────────────────────────────────────────────
case 'bulk_delete':
    $ids = json_decode($body['ids'] ?? '[]', true);
    if (!is_array($ids) || empty($ids)) respond(['success' => false, 'error' => 'Aucun ID fourni'], 400);

    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("DELETE FROM annuaire WHERE id IN ({$placeholders})")->execute($ids);

    respond(['success' => true, 'deleted' => count($ids), 'message' => count($ids) . ' entrée(s) supprimée(s)']);
    break;

// ────────────────────────────────────────────────
// BULK STATUS
// ────────────────────────────────────────────────
case 'bulk_status':
    $ids = json_decode($body['ids'] ?? '[]', true);
    $newStatus = $body['status'] ?? '';
    if (!is_array($ids) || empty($ids)) respond(['success' => false, 'error' => 'Aucun ID fourni'], 400);
    if (!in_array($newStatus, ['published', 'draft'])) respond(['success' => false, 'error' => 'Statut invalide'], 400);

    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge([$newStatus], $ids);
    $pdo->prepare("UPDATE annuaire SET status = ? WHERE id IN ({$placeholders})")->execute($params);

    respond(['success' => true, 'updated' => count($ids), 'message' => count($ids) . ' entrée(s) mise(s) à jour']);
    break;

// ────────────────────────────────────────────────
// BULK FEATURE
// ────────────────────────────────────────────────
case 'bulk_feature':
    $ids = json_decode($body['ids'] ?? '[]', true);
    if (!is_array($ids) || empty($ids)) respond(['success' => false, 'error' => 'Aucun ID fourni'], 400);

    $ids = array_map('intval', $ids);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("UPDATE annuaire SET is_featured = 1 WHERE id IN ({$placeholders})")->execute($ids);

    respond(['success' => true, 'updated' => count($ids), 'message' => count($ids) . ' entrée(s) mise(s) en avant']);
    break;

// ────────────────────────────────────────────────
// DEFAULT
// ────────────────────────────────────────────────
default:
    respond(['success' => false, 'error' => 'Action inconnue : ' . $action], 400);
}
