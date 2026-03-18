<?php
/**
 * /admin/api/seo/seo-api.php
 * API REST — Module SEO IMMO LOCAL+
 *
 * Actions disponibles :
 *   stats          → KPIs globaux SEO
 *   list           → Liste paginée pages + articles avec scores SEO
 *   get            → Détail SEO d'un contenu (page ou article)
 *   save           → Sauvegarde les meta SEO d'un contenu
 *   bulk-save      → Sauvegarde meta SEO en masse
 *   analyze        → Lance l'analyse sémantique IA sur un contenu
 *   sitemap        → Génère / retourne la liste des URLs indexables
 *   check-slug     → Vérifie l'unicité d'un slug
 *   missing        → Liste les contenus sans meta title / description
 *   duplicates     → Détecte les doublons de meta title / slug
 */

session_name(defined('SESSION_NAME') ? SESSION_NAME : 'ECOSYSTEM_ADMIN');
if (session_status() === PHP_SESSION_NONE) session_start();

// ── Auth ──────────────────────────────────────────────────────────────────────
if (empty($_SESSION['admin_id']) && empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'Non authentifié']));
}

// ── Bootstrap ─────────────────────────────────────────────────────────────────
$rootPath = dirname(__DIR__, 3); // /public_html
if (!defined('DB_HOST'))    require_once $rootPath . '/config/config.php';
if (!class_exists('Database')) require_once $rootPath . '/includes/classes/Database.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'error' => 'DB: ' . $e->getMessage()]));
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function apiOk(mixed $data = null, string $msg = ''): void {
    echo json_encode(['success' => true, 'data' => $data, 'message' => $msg]);
    exit;
}
function apiErr(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}
function seoScore(string $title, string $desc, string $content, string $slug): int {
    $score = 0;
    if (!empty($title))                          $score += 20;
    if (mb_strlen($title) >= 40 && mb_strlen($title) <= 70) $score += 10;
    if (!empty($desc))                           $score += 20;
    if (mb_strlen($desc) >= 100 && mb_strlen($desc) <= 160) $score += 10;
    if (!empty($slug) && preg_match('/^[a-z0-9-]+$/', $slug)) $score += 10;
    if (!empty($content) && mb_strlen($content) >= 300) $score += 15;
    if (!empty($content) && mb_strlen($content) >= 800) $score += 15;
    return min(100, $score);
}

function tableHasCol(PDO $db, string $table, string $col): bool {
    try {
        $rows = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'")->fetchAll();
        return count($rows) > 0;
    } catch (Exception $e) { return false; }
}

function tableExists(PDO $db, string $table): bool {
    try {
        return $db->query("SHOW TABLES LIKE " . $db->quote($table))->rowCount() > 0;
    } catch (Exception $e) { return false; }
}

// ── Router ────────────────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$raw    = file_get_contents('php://input');
$body   = json_decode($raw, true) ?? [];
$action = $_GET['action'] ?? $_POST['action'] ?? $body['action'] ?? '';

// ── Tables disponibles ────────────────────────────────────────────────────────
$hasPagesTable    = tableExists($db, 'pages');
$hasArticlesTable = tableExists($db, 'articles');
$hasSecteursTable = tableExists($db, 'secteurs');

// Colonnes SEO dans pages
$pagesHasSeoScore   = $hasPagesTable   && tableHasCol($db, 'pages',    'seo_score');
$pagesHasMetaTitle  = $hasPagesTable   && tableHasCol($db, 'pages',    'meta_title');
$pagesHasMetaDesc   = $hasPagesTable   && tableHasCol($db, 'pages',    'meta_description');
$articlesHasSeoScore= $hasArticlesTable && tableHasCol($db, 'articles', 'seo_score');
$articlesHasMetaTitle=$hasArticlesTable && tableHasCol($db, 'articles', 'meta_title');
$articlesHasMetaDesc= $hasArticlesTable && tableHasCol($db, 'articles', 'meta_description');

// ══════════════════════════════════════════════════════════════════════════════
// ACTION : stats
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'stats') {
    $data = [
        'pages'    => ['total' => 0, 'indexed' => 0, 'with_meta' => 0, 'avg_score' => 0, 'score_80plus' => 0],
        'articles' => ['total' => 0, 'published' => 0, 'with_meta' => 0, 'avg_score' => 0, 'score_80plus' => 0],
        'secteurs' => ['total' => 0, 'published' => 0],
        'issues'   => ['no_meta_title' => 0, 'no_meta_desc' => 0, 'no_h1' => 0, 'short_content' => 0],
        'global_score' => 0,
    ];

    try {
        // Pages
        if ($hasPagesTable) {
            $data['pages']['total']   = (int) $db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
            $published = $db->query("SELECT COUNT(*) FROM pages WHERE status='published'")->fetchColumn();
            $data['pages']['indexed'] = (int) $published;

            if ($pagesHasMetaTitle) {
                $data['pages']['with_meta']    = (int) $db->query("SELECT COUNT(*) FROM pages WHERE meta_title IS NOT NULL AND meta_title != ''")->fetchColumn();
                $data['issues']['no_meta_title'] += $data['pages']['total'] - $data['pages']['with_meta'];
            }
            if ($pagesHasMetaDesc) {
                $noDesc = (int) $db->query("SELECT COUNT(*) FROM pages WHERE meta_description IS NULL OR meta_description = ''")->fetchColumn();
                $data['issues']['no_meta_desc'] += $noDesc;
            }
            if ($pagesHasSeoScore) {
                $data['pages']['avg_score']  = (int) $db->query("SELECT COALESCE(AVG(seo_score),0) FROM pages")->fetchColumn();
                $data['pages']['score_80plus']= (int) $db->query("SELECT COUNT(*) FROM pages WHERE seo_score >= 80")->fetchColumn();
            }
        }

        // Articles
        if ($hasArticlesTable) {
            $data['articles']['total']     = (int) $db->query("SELECT COUNT(*) FROM articles")->fetchColumn();
            $colStatus = tableHasCol($db, 'articles', 'status') ? 'status' : (tableHasCol($db, 'articles', 'statut') ? 'statut' : null);
            if ($colStatus) {
                $data['articles']['published'] = (int) $db->query("SELECT COUNT(*) FROM articles WHERE {$colStatus}='published'")->fetchColumn();
            }
            if ($articlesHasMetaTitle) {
                $data['articles']['with_meta']  = (int) $db->query("SELECT COUNT(*) FROM articles WHERE meta_title IS NOT NULL AND meta_title != ''")->fetchColumn();
                $data['issues']['no_meta_title']+= $data['articles']['total'] - $data['articles']['with_meta'];
            }
            if ($articlesHasMetaDesc) {
                $noDesc = (int) $db->query("SELECT COUNT(*) FROM articles WHERE meta_description IS NULL OR meta_description = ''")->fetchColumn();
                $data['issues']['no_meta_desc'] += $noDesc;
            }
            if ($articlesHasSeoScore) {
                $data['articles']['avg_score']   = (int) $db->query("SELECT COALESCE(AVG(seo_score),0) FROM articles")->fetchColumn();
                $data['articles']['score_80plus']= (int) $db->query("SELECT COUNT(*) FROM articles WHERE seo_score >= 80")->fetchColumn();
            }
        }

        // Secteurs
        if ($hasSecteursTable) {
            $data['secteurs']['total'] = (int) $db->query("SELECT COUNT(*) FROM secteurs")->fetchColumn();
            if (tableHasCol($db, 'secteurs', 'status')) {
                $data['secteurs']['published'] = (int) $db->query("SELECT COUNT(*) FROM secteurs WHERE status='published'")->fetchColumn();
            }
        }

        // Score global estimé
        $totals = $data['pages']['total'] + $data['articles']['total'];
        if ($totals > 0) {
            $data['global_score'] = (int) round(
                ($data['pages']['avg_score'] * $data['pages']['total']
               + $data['articles']['avg_score'] * $data['articles']['total'])
                / $totals
            );
        }

    } catch (Exception $e) {
        apiErr('Erreur stats: ' . $e->getMessage(), 500);
    }

    apiOk($data);
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTION : list
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'list') {
    $type    = $_GET['type']    ?? 'all';   // all | page | article | secteur
    $filter  = $_GET['filter']  ?? 'all';  // all | issues | good | no_meta
    $search  = trim($_GET['search'] ?? '');
    $page    = max(1, (int) ($_GET['page'] ?? 1));
    $limit   = min(50, max(10, (int) ($_GET['limit'] ?? 25)));
    $offset  = ($page - 1) * $limit;
    $sort    = $_GET['sort'] ?? 'updated_desc';

    $rows  = [];
    $total = 0;

    try {
        // ── Pages ──────────────────────────────────────────────────────────
        if (($type === 'all' || $type === 'page') && $hasPagesTable) {
            $titleCol  = tableHasCol($db, 'pages', 'title')       ? 'title'        : (tableHasCol($db, 'pages', 'titre') ? 'titre' : 'id');
            $slugCol   = tableHasCol($db, 'pages', 'slug')         ? 'slug'         : 'id';
            $statusCol = tableHasCol($db, 'pages', 'status')       ? 'status'       : (tableHasCol($db, 'pages', 'statut') ? 'statut' : null);
            $contentCol= tableHasCol($db, 'pages', 'content')      ? 'content'      : (tableHasCol($db, 'pages', 'contenu') ? 'contenu' : null);
            $updatedCol= tableHasCol($db, 'pages', 'updated_at')   ? 'updated_at'   : (tableHasCol($db, 'pages', 'created_at') ? 'created_at' : null);

            $selSeo = '';
            if ($pagesHasSeoScore)  $selSeo .= ', seo_score';
            if ($pagesHasMetaTitle) $selSeo .= ', meta_title';
            if ($pagesHasMetaDesc)  $selSeo .= ', meta_description';
            $selStatus  = $statusCol  ? ", {$statusCol} AS status"   : ", 'unknown' AS status";
            $selContent = $contentCol ? ", LEFT({$contentCol},200) AS content_preview, CHAR_LENGTH({$contentCol}) AS content_length" : ', NULL AS content_preview, 0 AS content_length';
            $selUpdated = $updatedCol ? ", {$updatedCol} AS updated_at" : ', NULL AS updated_at';

            $where = "1=1";
            $params = [];

            if (!empty($search)) {
                $where .= " AND ({$titleCol} LIKE :s OR {$slugCol} LIKE :s2)";
                $params[':s']  = '%' . $search . '%';
                $params[':s2'] = '%' . $search . '%';
            }
            if ($filter === 'no_meta' && $pagesHasMetaTitle) {
                $where .= " AND (meta_title IS NULL OR meta_title = '')";
            }
            if ($filter === 'good' && $pagesHasSeoScore) {
                $where .= " AND seo_score >= 70";
            }
            if ($filter === 'issues' && $pagesHasSeoScore) {
                $where .= " AND seo_score < 50";
            }

            $stmt = $db->prepare("SELECT id, {$titleCol} AS title, {$slugCol} AS slug {$selSeo} {$selStatus} {$selContent} {$selUpdated} FROM pages WHERE {$where}");
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $pgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($pgs as $p) {
                $sc = isset($p['seo_score']) ? (int)$p['seo_score']
                    : seoScore($p['meta_title'] ?? '', $p['meta_description'] ?? '', $p['content_preview'] ?? '', $p['slug'] ?? '');
                $rows[] = [
                    'id'               => $p['id'],
                    'type'             => 'page',
                    'type_label'       => 'Page',
                    'title'            => $p['title'] ?? '',
                    'slug'             => $p['slug'] ?? '',
                    'status'           => $p['status'] ?? 'unknown',
                    'meta_title'       => $p['meta_title'] ?? '',
                    'meta_description' => $p['meta_description'] ?? '',
                    'seo_score'        => $sc,
                    'content_length'   => (int) ($p['content_length'] ?? 0),
                    'updated_at'       => $p['updated_at'] ?? null,
                    'url'              => '/' . ($p['slug'] ?? $p['id']),
                ];
            }
        }

        // ── Articles ───────────────────────────────────────────────────────
        if (($type === 'all' || $type === 'article') && $hasArticlesTable) {
            $titleCol  = tableHasCol($db, 'articles', 'title')      ? 'title'      : (tableHasCol($db, 'articles', 'titre') ? 'titre' : 'id');
            $slugCol   = tableHasCol($db, 'articles', 'slug')         ? 'slug'       : 'id';
            $statusCol = tableHasCol($db, 'articles', 'status')       ? 'status'     : (tableHasCol($db, 'articles', 'statut') ? 'statut' : null);
            $contentCol= tableHasCol($db, 'articles', 'content')      ? 'content'    : (tableHasCol($db, 'articles', 'contenu') ? 'contenu' : null);
            $updatedCol= tableHasCol($db, 'articles', 'updated_at')   ? 'updated_at' : (tableHasCol($db, 'articles', 'created_at') ? 'created_at' : null);

            $selSeo = '';
            if ($articlesHasSeoScore)  $selSeo .= ', seo_score';
            if ($articlesHasMetaTitle) $selSeo .= ', meta_title';
            if ($articlesHasMetaDesc)  $selSeo .= ', meta_description';
            $selStatus  = $statusCol  ? ", {$statusCol} AS status"   : ", 'unknown' AS status";
            $selContent = $contentCol ? ", LEFT({$contentCol},200) AS content_preview, CHAR_LENGTH({$contentCol}) AS content_length" : ', NULL AS content_preview, 0 AS content_length';
            $selUpdated = $updatedCol ? ", {$updatedCol} AS updated_at" : ', NULL AS updated_at';

            $where  = "1=1";
            $params = [];

            if (!empty($search)) {
                $where .= " AND ({$titleCol} LIKE :s OR {$slugCol} LIKE :s2)";
                $params[':s']  = '%' . $search . '%';
                $params[':s2'] = '%' . $search . '%';
            }
            if ($filter === 'no_meta' && $articlesHasMetaTitle) {
                $where .= " AND (meta_title IS NULL OR meta_title = '')";
            }
            if ($filter === 'good' && $articlesHasSeoScore) {
                $where .= " AND seo_score >= 70";
            }
            if ($filter === 'issues' && $articlesHasSeoScore) {
                $where .= " AND seo_score < 50";
            }

            $stmt = $db->prepare("SELECT id, {$titleCol} AS title, {$slugCol} AS slug {$selSeo} {$selStatus} {$selContent} {$selUpdated} FROM articles WHERE {$where}");
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $arts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($arts as $a) {
                $sc = isset($a['seo_score']) ? (int)$a['seo_score']
                    : seoScore($a['meta_title'] ?? '', $a['meta_description'] ?? '', $a['content_preview'] ?? '', $a['slug'] ?? '');
                $rows[] = [
                    'id'               => $a['id'],
                    'type'             => 'article',
                    'type_label'       => 'Article',
                    'title'            => $a['title'] ?? '',
                    'slug'             => $a['slug'] ?? '',
                    'status'           => $a['status'] ?? 'unknown',
                    'meta_title'       => $a['meta_title'] ?? '',
                    'meta_description' => $a['meta_description'] ?? '',
                    'seo_score'        => $sc,
                    'content_length'   => (int) ($a['content_length'] ?? 0),
                    'updated_at'       => $a['updated_at'] ?? null,
                    'url'              => '/blog/' . ($a['slug'] ?? $a['id']),
                ];
            }
        }

        // ── Secteurs ───────────────────────────────────────────────────────
        if (($type === 'all' || $type === 'secteur') && $hasSecteursTable) {
            $nameCol   = tableHasCol($db, 'secteurs', 'name')      ? 'name'       : (tableHasCol($db, 'secteurs', 'nom') ? 'nom' : 'id');
            $slugCol   = tableHasCol($db, 'secteurs', 'slug')       ? 'slug'       : 'id';
            $statusCol = tableHasCol($db, 'secteurs', 'status')     ? 'status'     : null;
            $updatedCol= tableHasCol($db, 'secteurs', 'updated_at') ? 'updated_at' : null;

            $selStatus  = $statusCol  ? ", {$statusCol} AS status"     : ", 'published' AS status";
            $selUpdated = $updatedCol ? ", {$updatedCol} AS updated_at" : ', NULL AS updated_at';

            $where = "1=1"; $params = [];
            if (!empty($search)) {
                $where .= " AND ({$nameCol} LIKE :s OR {$slugCol} LIKE :s2)";
                $params[':s']  = '%' . $search . '%';
                $params[':s2'] = '%' . $search . '%';
            }

            $stmt = $db->prepare("SELECT id, {$nameCol} AS title, {$slugCol} AS slug {$selStatus} {$selUpdated} FROM secteurs WHERE {$where}");
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $sects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($sects as $s) {
                $rows[] = [
                    'id'               => $s['id'],
                    'type'             => 'secteur',
                    'type_label'       => 'Secteur',
                    'title'            => $s['title'] ?? '',
                    'slug'             => $s['slug'] ?? '',
                    'status'           => $s['status'] ?? 'published',
                    'meta_title'       => '',
                    'meta_description' => '',
                    'seo_score'        => 0,
                    'content_length'   => 0,
                    'updated_at'       => $s['updated_at'] ?? null,
                    'url'              => '/' . ($s['slug'] ?? $s['id']),
                ];
            }
        }

        // ── Tri ────────────────────────────────────────────────────────────
        usort($rows, function($a, $b) use ($sort) {
            return match($sort) {
                'score_desc' => $b['seo_score'] <=> $a['seo_score'],
                'score_asc'  => $a['seo_score'] <=> $b['seo_score'],
                'title_asc'  => strcmp($a['title'], $b['title']),
                'title_desc' => strcmp($b['title'], $a['title']),
                default      => strcmp($b['updated_at'] ?? '', $a['updated_at'] ?? ''),
            };
        });

        $total = count($rows);
        $rows  = array_slice($rows, $offset, $limit);

    } catch (Exception $e) {
        apiErr('Erreur list: ' . $e->getMessage(), 500);
    }

    apiOk([
        'items'      => $rows,
        'total'      => $total,
        'page'       => $page,
        'limit'      => $limit,
        'pages'      => (int) ceil($total / $limit),
    ]);
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTION : get
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'get') {
    $id   = (int) ($_GET['id'] ?? $body['id'] ?? 0);
    $type = $_GET['type'] ?? $body['type'] ?? 'page';

    if (!$id) apiErr('ID manquant');

    $table = match($type) {
        'article' => 'articles',
        'secteur' => 'secteurs',
        default   => 'pages',
    };

    if (!tableExists($db, $table)) apiErr("Table {$table} introuvable");

    try {
        $row = $db->query("SELECT * FROM `{$table}` WHERE id = {$id} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$row) apiErr('Contenu introuvable', 404);

        // Calcul score si absent
        if (!isset($row['seo_score'])) {
            $row['seo_score'] = seoScore(
                $row['meta_title'] ?? '',
                $row['meta_description'] ?? '',
                $row['content'] ?? $row['contenu'] ?? '',
                $row['slug'] ?? ''
            );
        }

        // Retirer le contenu complet pour alléger (garder longueur)
        $contentRaw = $row['content'] ?? $row['contenu'] ?? '';
        $row['content_length'] = mb_strlen($contentRaw);
        unset($row['content'], $row['contenu']);

        apiOk($row);
    } catch (Exception $e) {
        apiErr('Erreur get: ' . $e->getMessage(), 500);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTION : save — sauvegarde meta SEO d'un contenu
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'save' && $method === 'POST') {
    $id   = (int) ($body['id'] ?? 0);
    $type = $body['type'] ?? 'page';

    if (!$id) apiErr('ID manquant');

    $table = match($type) {
        'article' => 'articles',
        'secteur' => 'secteurs',
        default   => 'pages',
    };

    if (!tableExists($db, $table)) apiErr("Table {$table} introuvable");

    $fields  = [];
    $params  = [':id' => $id];
    $allowed = ['meta_title','meta_description','meta_keywords','seo_title','seo_description','og_title','og_description','og_image','canonical_url','noindex','seo_score'];

    foreach ($allowed as $f) {
        if (!isset($body[$f])) continue;
        if (!tableHasCol($db, $table, $f)) continue;
        $fields[]      = "`{$f}` = :{$f}";
        $params[":{$f}"] = $body[$f];
    }

    // updated_at
    if (tableHasCol($db, $table, 'updated_at')) {
        $fields[]             = '`updated_at` = :ua';
        $params[':ua']        = date('Y-m-d H:i:s');
    }

    if (empty($fields)) apiErr('Aucun champ valide à mettre à jour');

    try {
        $stmt = $db->prepare("UPDATE `{$table}` SET " . implode(', ', $fields) . " WHERE id = :id");
        $stmt->execute($params);
        apiOk(['id' => $id, 'type' => $type, 'updated' => count($fields)], 'Meta SEO sauvegardées');
    } catch (Exception $e) {
        apiErr('Erreur save: ' . $e->getMessage(), 500);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTION : bulk-save
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'bulk-save' && $method === 'POST') {
    $items = $body['items'] ?? [];
    if (empty($items) || !is_array($items)) apiErr('items[] manquant');

    $updated = 0;
    $errors  = [];

    foreach ($items as $item) {
        $id    = (int) ($item['id'] ?? 0);
        $type  = $item['type'] ?? 'page';
        $table = match($type) { 'article' => 'articles', 'secteur' => 'secteurs', default => 'pages' };

        if (!$id || !tableExists($db, $table)) continue;

        $fields = []; $params = [':id' => $id];
        foreach (['meta_title','meta_description','meta_keywords','seo_score'] as $f) {
            if (!isset($item[$f]) || !tableHasCol($db, $table, $f)) continue;
            $fields[] = "`{$f}` = :{$f}"; $params[":{$f}"] = $item[$f];
        }
        if (tableHasCol($db, $table, 'updated_at')) {
            $fields[] = '`updated_at` = NOW()';
        }
        if (empty($fields)) continue;

        try {
            $db->prepare("UPDATE `{$table}` SET " . implode(', ', $fields) . " WHERE id = :id")->execute($params);
            $updated++;
        } catch (Exception $e) {
            $errors[] = "id={$id}: " . $e->getMessage();
        }
    }

    apiOk(['updated' => $updated, 'errors' => $errors], "{$updated} contenu(s) mis à jour");
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTION : analyze — recalcule le score SEO d'un contenu (sans IA)
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'analyze' && $method === 'POST') {
    $id   = (int) ($body['id'] ?? 0);
    $type = $body['type'] ?? 'page';

    if (!$id) apiErr('ID manquant');

    $table = match($type) { 'article' => 'articles', 'secteur' => 'secteurs', default => 'pages' };
    if (!tableExists($db, $table)) apiErr("Table {$table} introuvable");

    try {
        $row = $db->query("SELECT * FROM `{$table}` WHERE id = {$id} LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$row) apiErr('Contenu introuvable', 404);

        $title   = $row['meta_title']       ?? $row['title']  ?? $row['titre'] ?? '';
        $desc    = $row['meta_description'] ?? '';
        $content = $row['content']          ?? $row['contenu'] ?? '';
        $slug    = $row['slug']             ?? '';
        $score   = seoScore($title, $desc, $content, $slug);

        // Détail des checks
        $checks = [
            ['label' => 'Meta title présent',      'ok' => !empty($title)],
            ['label' => 'Meta title longueur (40-70 car.)', 'ok' => mb_strlen($title) >= 40 && mb_strlen($title) <= 70],
            ['label' => 'Meta description présente', 'ok' => !empty($desc)],
            ['label' => 'Meta desc longueur (100-160)', 'ok' => mb_strlen($desc) >= 100 && mb_strlen($desc) <= 160],
            ['label' => 'Slug propre (minuscules, tirets)', 'ok' => !empty($slug) && preg_match('/^[a-z0-9-]+$/', $slug)],
            ['label' => 'Contenu ≥ 300 mots',       'ok' => mb_strlen(strip_tags($content)) >= 300],
            ['label' => 'Contenu ≥ 800 mots',       'ok' => mb_strlen(strip_tags($content)) >= 800],
        ];

        // Sauvegarder le score
        if (tableHasCol($db, $table, 'seo_score')) {
            $upd = ['`seo_score` = :sc'];
            $prm = [':sc' => $score, ':id' => $id];
            if (tableHasCol($db, $table, 'updated_at')) { $upd[] = '`updated_at` = NOW()'; }
            $db->prepare("UPDATE `{$table}` SET " . implode(', ', $upd) . " WHERE id = :id")->execute($prm);
        }

        apiOk([
            'id'     => $id,
            'type'   => $type,
            'score'  => $score,
            'label'  => $score >= 80 ? 'Excellent' : ($score >= 60 ? 'Bon' : ($score >= 40 ? 'Moyen' : 'Insuffisant')),
            'checks' => $checks,
            'title_length' => mb_strlen($title),
            'desc_length'  => mb_strlen($desc),
            'content_words'=> str_word_count(strip_tags($content)),
        ], 'Analyse terminée');

    } catch (Exception $e) {
        apiErr('Erreur analyze: ' . $e->getMessage(), 500);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTION : missing — contenus sans meta title OU sans meta description
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'missing') {
    $field = $_GET['field'] ?? 'meta_title'; // meta_title | meta_description
    $rows  = [];

    try {
        if ($hasPagesTable && tableHasCol($db, 'pages', $field)) {
            $tc = tableHasCol($db, 'pages', 'title') ? 'title' : 'id';
            $sc = tableHasCol($db, 'pages', 'slug')  ? 'slug'  : 'id';
            $res = $db->query("SELECT id, {$tc} AS title, {$sc} AS slug FROM pages WHERE {$field} IS NULL OR {$field} = '' ORDER BY id LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($res as $r) { $r['type'] = 'page'; $rows[] = $r; }
        }
        if ($hasArticlesTable && tableHasCol($db, 'articles', $field)) {
            $tc = tableHasCol($db, 'articles', 'title') ? 'title' : 'id';
            $sc = tableHasCol($db, 'articles', 'slug')  ? 'slug'  : 'id';
            $res = $db->query("SELECT id, {$tc} AS title, {$sc} AS slug FROM articles WHERE {$field} IS NULL OR {$field} = '' ORDER BY id LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($res as $r) { $r['type'] = 'article'; $rows[] = $r; }
        }
    } catch (Exception $e) {
        apiErr('Erreur missing: ' . $e->getMessage(), 500);
    }

    apiOk(['items' => $rows, 'total' => count($rows), 'field' => $field]);
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTION : duplicates — doublons de meta_title ou slug
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'duplicates') {
    $field = $_GET['field'] ?? 'meta_title';
    $allowed = ['meta_title','slug','meta_description'];
    if (!in_array($field, $allowed)) apiErr('Champ non autorisé');

    $dupes = [];

    try {
        foreach (['pages' => 'page', 'articles' => 'article'] as $table => $type) {
            if (!tableExists($db, $table) || !tableHasCol($db, $table, $field)) continue;
            $tc = tableHasCol($db, $table, 'title') ? 'title' : 'id';
            $res = $db->query(
                "SELECT {$field} AS dupe_value, COUNT(*) AS cnt, GROUP_CONCAT(id) AS ids, GROUP_CONCAT({$tc}) AS titles
                 FROM `{$table}`
                 WHERE {$field} IS NOT NULL AND {$field} != ''
                 GROUP BY {$field}
                 HAVING COUNT(*) > 1
                 ORDER BY cnt DESC
                 LIMIT 50"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($res as $r) {
                $r['type']  = $type;
                $r['table'] = $table;
                $dupes[] = $r;
            }
        }
    } catch (Exception $e) {
        apiErr('Erreur duplicates: ' . $e->getMessage(), 500);
    }

    apiOk(['items' => $dupes, 'total' => count($dupes), 'field' => $field]);
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTION : sitemap — liste des URLs indexables
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'sitemap') {
    $urls = [];
    $siteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://eduardo-desul-immobilier.fr';

    try {
        if ($hasPagesTable) {
            $sc = tableHasCol($db, 'pages', 'status') ? 'status' : null;
            $slugCol   = tableHasCol($db, 'pages', 'slug')       ? 'slug'       : null;
            $updatedCol= tableHasCol($db, 'pages', 'updated_at') ? 'updated_at' : null;
            if ($slugCol) {
                $where = $sc ? "WHERE {$sc} = 'published'" : '';
                $res   = $db->query("SELECT id, {$slugCol} AS slug " . ($updatedCol ? ", {$updatedCol} AS updated_at" : '') . " FROM pages {$where}")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($res as $r) {
                    $urls[] = ['url' => $siteUrl . '/' . $r['slug'], 'type' => 'page', 'updated_at' => $r['updated_at'] ?? null, 'priority' => '0.8'];
                }
            }
        }
        if ($hasArticlesTable) {
            $sc = tableHasCol($db, 'articles', 'status') ? 'status' : (tableHasCol($db, 'articles', 'statut') ? 'statut' : null);
            $slugCol   = tableHasCol($db, 'articles', 'slug')       ? 'slug'       : null;
            $updatedCol= tableHasCol($db, 'articles', 'updated_at') ? 'updated_at' : null;
            if ($slugCol) {
                $where = $sc ? "WHERE {$sc} = 'published'" : '';
                $res   = $db->query("SELECT id, {$slugCol} AS slug " . ($updatedCol ? ", {$updatedCol} AS updated_at" : '') . " FROM articles {$where}")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($res as $r) {
                    $urls[] = ['url' => $siteUrl . '/blog/' . $r['slug'], 'type' => 'article', 'updated_at' => $r['updated_at'] ?? null, 'priority' => '0.7'];
                }
            }
        }
        if ($hasSecteursTable) {
            $slugCol   = tableHasCol($db, 'secteurs', 'slug')       ? 'slug'       : null;
            $updatedCol= tableHasCol($db, 'secteurs', 'updated_at') ? 'updated_at' : null;
            if ($slugCol) {
                $res = $db->query("SELECT id, {$slugCol} AS slug " . ($updatedCol ? ", {$updatedCol} AS updated_at" : '') . " FROM secteurs")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($res as $r) {
                    $urls[] = ['url' => $siteUrl . '/' . $r['slug'], 'type' => 'secteur', 'updated_at' => $r['updated_at'] ?? null, 'priority' => '0.9'];
                }
            }
        }
    } catch (Exception $e) {
        apiErr('Erreur sitemap: ' . $e->getMessage(), 500);
    }

    apiOk(['urls' => $urls, 'total' => count($urls), 'site_url' => $siteUrl]);
}

// ══════════════════════════════════════════════════════════════════════════════
// ACTION : check-slug — vérifie l'unicité d'un slug
// ══════════════════════════════════════════════════════════════════════════════
if ($action === 'check-slug') {
    $slug      = trim($_GET['slug'] ?? $body['slug'] ?? '');
    $type      = $_GET['type'] ?? $body['type'] ?? 'page';
    $excludeId = (int) ($_GET['exclude_id'] ?? $body['exclude_id'] ?? 0);

    if (empty($slug)) apiErr('Slug manquant');

    $table = match($type) { 'article' => 'articles', 'secteur' => 'secteurs', default => 'pages' };

    if (!tableExists($db, $table) || !tableHasCol($db, $table, 'slug')) {
        apiOk(['available' => true, 'slug' => $slug]);
    }

    try {
        $where  = "slug = :slug" . ($excludeId ? " AND id != :eid" : '');
        $params = [':slug' => $slug];
        if ($excludeId) $params[':eid'] = $excludeId;
        $count  = (int) $db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE {$where}")->execute($params)
                  + 0; // exécuté dessous
        $stmt = $db->prepare("SELECT COUNT(*) FROM `{$table}` WHERE {$where}");
        $stmt->execute($params);
        $count = (int) $stmt->fetchColumn();
        apiOk(['available' => $count === 0, 'slug' => $slug, 'conflicts' => $count]);
    } catch (Exception $e) {
        apiErr('Erreur check-slug: ' . $e->getMessage(), 500);
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// Action non reconnue
// ══════════════════════════════════════════════════════════════════════════════
$validActions = ['stats','list','get','save','bulk-save','analyze','missing','duplicates','sitemap','check-slug'];
apiErr('Action inconnue. Actions disponibles : ' . implode(', ', $validActions));