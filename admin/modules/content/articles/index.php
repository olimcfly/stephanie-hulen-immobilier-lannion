<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE ARTICLES — Mon Blog  v2.3
 * /admin/modules/articles/index.php
 * Refacto UX aligné sur captures v2.0 :
 *   - Modal custom (suppression, duplication)
 *   - Toast notifications
 *   - Toggle vue liste / grille
 *   - Bulk actions
 * ══════════════════════════════════════════════════════════════
 */

// ─── Connexion DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(__DIR__)) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

// ─── Détecter table ───
$tableName   = 'articles';
$tableExists = true;
try {
    $pdo->query("SELECT 1 FROM articles LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->query("SELECT 1 FROM blog_articles LIMIT 1");
        $tableName = 'blog_articles';
    } catch (PDOException $e2) {
        $tableExists = false;
    }
}

// ─── Colonnes disponibles ───
$availCols = [];
if ($tableExists) {
    try {
        $availCols = $pdo->query("SHOW COLUMNS FROM `{$tableName}`")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

// ─── Mapping colonnes réelles ───
$colTitle    = in_array('titre',   $availCols) ? 'titre'   : (in_array('title',   $availCols) ? 'title'   : 'titre');
$colContent  = in_array('contenu', $availCols) ? 'contenu' : (in_array('content', $availCols) ? 'content' : 'contenu');
$hasStatut   = in_array('statut',  $availCols);
$hasStatus   = in_array('status',  $availCols);
$colKeyword  = in_array('focus_keyword', $availCols) ? 'focus_keyword'
             : (in_array('main_keyword', $availCols) ? 'main_keyword' : null);

$colSeoScore = null;
if      (in_array('seo_score',       $availCols)) $colSeoScore = 'seo_score';
elseif  (in_array('score_technique', $availCols)) $colSeoScore = 'score_technique';

$colSemantic = null;
if      (in_array('score_semantique', $availCols)) $colSemantic = 'score_semantique';
elseif  (in_array('semantic_score',   $availCols)) $colSemantic = 'semantic_score';

$hasWordCount     = in_array('word_count',     $availCols);
$hasGoogleIndexed = in_array('google_indexed', $availCols);
$hasIsIndexed     = in_array('is_indexed',     $availCols);
$hasCategory      = in_array('category',       $availCols);
$hasIsFeatured    = in_array('is_featured',    $availCols);
$hasUpdatedAt     = in_array('updated_at',     $availCols);

// ─── Table seo_scores externe ───
$hasSeoScoresTable  = false;
$seoScoresHasSeo    = false;
$seoScoresHasSemant = false;
try {
    $pdo->query("SELECT 1 FROM seo_scores LIMIT 1");
    $hasSeoScoresTable = true;
    $ssCols = $pdo->query("SHOW COLUMNS FROM seo_scores")->fetchAll(PDO::FETCH_COLUMN);
    $seoScoresHasSeo    = in_array('seo_score',        $ssCols);
    $seoScoresHasSemant = in_array('score_semantique', $ssCols);
    if (!$seoScoresHasSeo && !$seoScoresHasSemant) $hasSeoScoresTable = false;
} catch (PDOException $e) {}

// ──────────────────────────────────────────────────────────────
// ROUTING
// ──────────────────────────────────────────────────────────────
$routeAction = $_GET['action'] ?? '';
if (in_array($routeAction, ['edit', 'create', 'delete'])) {
    $editFile = __DIR__ . '/edit.php';
    if (file_exists($editFile)) { require $editFile; return; }
    else {
        echo '<div style="background:#fee2e2;color:#991b1b;padding:20px;border-radius:10px;margin:20px;">
            <strong>⚠️ Fichier manquant :</strong> <code>/admin/modules/articles/edit.php</code></div>';
        return;
    }
}

// ─── Filtres URL ───
$filterStatus  = $_GET['status']   ?? 'all';
$filterIndexed = $_GET['indexed']  ?? 'all';
$filterCat     = $_GET['category'] ?? 'all';
$searchQuery   = trim($_GET['q']   ?? '');
$currentPage   = max(1, (int)($_GET['p'] ?? 1));
$perPage       = 25;
$offset        = ($currentPage - 1) * $perPage;

// ─── Catégories ───
$categories = [];
if ($tableExists && $hasCategory) {
    try {
        $categories = $pdo->query(
            "SELECT DISTINCT category FROM `{$tableName}` WHERE category IS NOT NULL AND category != '' ORDER BY category"
        )->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

// ─── WHERE ───
$where  = [];
$params = [];

if ($filterStatus !== 'all') {
    if ($filterStatus === 'published') {
        $cond = [];
        if ($hasStatus)  { $cond[] = "a.status = ?";  $params[] = 'published'; }
        if ($hasStatut)  { $cond[] = "a.statut = ?";  $params[] = 'publie'; }
        if ($cond) $where[] = '(' . implode(' OR ', $cond) . ')';
    } elseif ($filterStatus === 'draft') {
        $cond = [];
        if ($hasStatus)  { $cond[] = "a.status = ?";  $params[] = 'draft'; }
        if ($hasStatut)  { $cond[] = "a.statut = ?";  $params[] = 'brouillon'; }
        if ($cond) $where[] = '(' . implode(' OR ', $cond) . ')';
    } elseif ($filterStatus === 'archived') {
        if ($hasStatus)  { $where[] = "a.status = ?"; $params[] = 'archived'; }
    }
}
if ($filterIndexed !== 'all' && $hasGoogleIndexed && in_array($filterIndexed, ['yes','no','pending','unknown'])) {
    $where[] = "a.google_indexed = ?"; $params[] = $filterIndexed;
} elseif ($filterIndexed === 'yes' && $hasIsIndexed && !$hasGoogleIndexed) {
    $where[] = "a.is_indexed = 1";
}
if ($filterCat !== 'all' && $hasCategory) {
    $where[] = "a.category = ?"; $params[] = $filterCat;
}
if ($searchQuery !== '') {
    $w  = "(a.`{$colTitle}` LIKE ?";  $params[] = "%{$searchQuery}%";
    $w .= " OR a.slug LIKE ?";        $params[] = "%{$searchQuery}%";
    if ($colKeyword) { $w .= " OR a.`{$colKeyword}` LIKE ?"; $params[] = "%{$searchQuery}%"; }
    $w .= ")";
    $where[] = $w;
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ─── Stats globales ───
$stats = [
    'total' => 0, 'published' => 0, 'draft' => 0, 'archived' => 0,
    'avg_seo' => 0, 'avg_semantic' => 0, 'indexed_count' => 0,
];
if ($tableExists) {
    try {
        $stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}`")->fetchColumn();
        $pubCond = [];
        if ($hasStatus) $pubCond[] = "status = 'published'";
        if ($hasStatut) $pubCond[] = "statut = 'publie'";
        if ($pubCond) $stats['published'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}` WHERE " . implode(' OR ', $pubCond))->fetchColumn();
        $draftCond = [];
        if ($hasStatus) $draftCond[] = "status = 'draft'";
        if ($hasStatut) $draftCond[] = "statut = 'brouillon'";
        if ($draftCond) $stats['draft'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}` WHERE " . implode(' OR ', $draftCond))->fetchColumn();
        if ($hasStatus) $stats['archived'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}` WHERE status = 'archived'")->fetchColumn();
        if ($colSeoScore)
            $stats['avg_seo'] = (int)$pdo->query("SELECT ROUND(AVG(NULLIF(`{$colSeoScore}`, 0)), 0) FROM `{$tableName}`")->fetchColumn();
        if ($colSemantic)
            $stats['avg_semantic'] = (int)$pdo->query("SELECT ROUND(AVG(NULLIF(`{$colSemantic}`, 0)), 0) FROM `{$tableName}`")->fetchColumn();
        if ($hasGoogleIndexed)
            $stats['indexed_count'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}` WHERE google_indexed = 'yes'")->fetchColumn();
        elseif ($hasIsIndexed)
            $stats['indexed_count'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$tableName}` WHERE is_indexed = 1")->fetchColumn();
    } catch (PDOException $e) {}
}

// ─── Total filtré + articles ───
$totalFiltered = 0;
$articles      = [];
$totalPages    = 1;

if ($tableExists) {
    try {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM `{$tableName}` a {$whereSQL}");
        $stmtCount->execute($params);
        $totalFiltered = (int) $stmtCount->fetchColumn();
        $totalPages    = max(1, ceil($totalFiltered / $perPage));

        $selectParts = [
            "a.id",
            "a.`{$colTitle}` AS display_title",
            "a.slug",
            "a.created_at",
        ];
        if ($hasUpdatedAt)     $selectParts[] = "a.updated_at";
        if ($hasStatus)        $selectParts[] = "a.status";
        if ($hasStatut)        $selectParts[] = "a.statut";
        if ($colSeoScore)      $selectParts[] = "a.`{$colSeoScore}` AS col_seo";
        if ($colSemantic)      $selectParts[] = "a.`{$colSemantic}` AS col_semantic";
        if ($hasWordCount)     $selectParts[] = "a.word_count";
        if ($hasIsIndexed)     $selectParts[] = "a.is_indexed";
        if ($hasGoogleIndexed) $selectParts[] = "a.google_indexed";
        if ($hasCategory)      $selectParts[] = "a.category";
        if ($hasIsFeatured)    $selectParts[] = "a.is_featured";
        if ($colKeyword)       $selectParts[] = "a.`{$colKeyword}` AS main_keyword";
        if ($hasSeoScoresTable) {
            if ($seoScoresHasSeo)    $selectParts[] = "ss.seo_score        AS ext_seo_score";
            if ($seoScoresHasSemant) $selectParts[] = "ss.score_semantique AS ext_semantic";
        }

        $colsSQL  = implode(', ', $selectParts);
        $joinSQL  = $hasSeoScoresTable
            ? "LEFT JOIN seo_scores ss ON ss.context = 'article' AND ss.entity_id = a.id"
            : "";

        $stmt = $pdo->prepare("SELECT {$colsSQL} FROM `{$tableName}` a {$joinSQL} {$whereSQL} ORDER BY a.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("[Articles Index] SQL Error: " . $e->getMessage());
    }
}

// ─── CSRF ───
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Helpers PHP ───
function normalizeArticleStatus(array $a): string {
    $s  = $a['status']  ?? '';
    $st = $a['statut']  ?? '';
    if ($s === 'published') return 'published';
    if ($s === 'archived')  return 'archived';
    if ($st === 'publie')   return 'published';
    if ($st === 'brouillon') return 'draft';
    return 'draft';
}

function getScore(array $a, string $colAlias, string $extAlias): int {
    $v = (int)($a[$colAlias] ?? 0);
    if ($v === 0 && isset($a[$extAlias])) $v = (int)$a[$extAlias];
    return $v;
}

$flash = $_GET['msg'] ?? '';
?>

<style>
/* ══════════════════════════════════════════════════════════════
   ARTICLES MODULE v2.3 — Light Theme
   UX aligné captures v2.0 : modal, toast, grille, bulk
══════════════════════════════════════════════════════════════ */
.arm-wrap { font-family: var(--font, 'Inter', sans-serif); }

/* ─── Banner ─── */
.arm-banner {
    background: var(--surface, #fff);
    border-radius: 16px; padding: 26px 30px; margin-bottom: 22px;
    display: flex; align-items: center; justify-content: space-between;
    border: 1px solid var(--border, #e5e7eb); position: relative; overflow: hidden;
}
.arm-banner::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #f59e0b, #ef4444, #8b5cf6); opacity: .75;
}
.arm-banner::after {
    content: ''; position: absolute; top: -40%; right: -5%;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(245,158,11,.05), transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.arm-banner-left { position: relative; z-index: 1; }
.arm-banner-left h2 { font-size: 1.35rem; font-weight: 700; color: var(--text, #111827); margin: 0 0 4px; display: flex; align-items: center; gap: 10px; letter-spacing: -.02em; }
.arm-banner-left h2 i { font-size: 16px; color: #f59e0b; }
.arm-banner-left p { color: var(--text-2, #6b7280); font-size: .85rem; margin: 0; }

.arm-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.arm-stat { text-align: center; padding: 10px 16px; background: var(--surface-2, #f9fafb); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); min-width: 72px; transition: all .2s; }
.arm-stat:hover { border-color: var(--border-h, #d1d5db); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.arm-stat .num { font-size: 1.45rem; font-weight: 800; line-height: 1; color: var(--text, #111827); letter-spacing: -.03em; }
.arm-stat .num.blue   { color: #3b82f6; }
.arm-stat .num.green  { color: #10b981; }
.arm-stat .num.amber  { color: #f59e0b; }
.arm-stat .num.teal   { color: #0d9488; }
.arm-stat .num.violet { color: #7c3aed; }
.arm-stat .lbl { font-size: .58rem; color: var(--text-3, #9ca3af); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }

/* ─── Toolbar ─── */
.arm-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.arm-filters { display: flex; gap: 3px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; padding: 3px; flex-wrap: wrap; }
.arm-fbtn { padding: 7px 15px; border: none; background: transparent; color: var(--text-2, #6b7280); font-size: .78rem; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all .15s; font-family: inherit; display: flex; align-items: center; gap: 5px; text-decoration: none; }
.arm-fbtn:hover { color: var(--text, #111827); background: var(--surface-2, #f9fafb); }
.arm-fbtn.active { background: #f59e0b; color: #fff; box-shadow: 0 1px 4px rgba(245,158,11,.25); }
.arm-fbtn .badge { font-size: .68rem; padding: 1px 7px; border-radius: 10px; background: var(--surface-2, #f3f4f6); font-weight: 700; color: var(--text-3, #9ca3af); }
.arm-fbtn.active .badge { background: rgba(255,255,255,.22); color: #fff; }

/* ─── Sub-filtres ─── */
.arm-subfilters { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
.arm-subfilter { display: flex; align-items: center; gap: 5px; font-size: .75rem; color: var(--text-2, #6b7280); }
.arm-subfilter select { padding: 5px 10px; border: 1px solid var(--border, #e5e7eb); border-radius: 6px; background: var(--surface, #fff); color: var(--text, #111827); font-size: .75rem; font-family: inherit; cursor: pointer; }
.arm-subfilter select:focus { outline: none; border-color: #f59e0b; }

/* ─── Toolbar right ─── */
.arm-toolbar-r { display: flex; align-items: center; gap: 10px; }
.arm-view-toggle { display: flex; gap: 2px; background: var(--surface-2, #f9fafb); border: 1px solid var(--border, #e5e7eb); border-radius: 8px; padding: 3px; }
.arm-view-btn { width: 30px; height: 28px; border: none; background: transparent; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-3, #9ca3af); transition: all .15s; font-size: .78rem; }
.arm-view-btn:hover { color: var(--text, #111827); }
.arm-view-btn.active { background: white; color: #f59e0b; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.arm-search { position: relative; }
.arm-search input { padding: 8px 12px 8px 34px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; color: var(--text, #111827); font-size: .82rem; width: 220px; font-family: inherit; transition: all .2s; }
.arm-search input:focus { outline: none; border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.1); width: 250px; }
.arm-search input::placeholder { color: var(--text-3, #9ca3af); }
.arm-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-3, #9ca3af); font-size: .75rem; }
.arm-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 10px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; font-family: inherit; text-decoration: none; line-height: 1.3; }
.arm-btn-primary { background: #f59e0b; color: #fff; box-shadow: 0 1px 4px rgba(245,158,11,.22); }
.arm-btn-primary:hover { background: #d97706; transform: translateY(-1px); color: #fff; }
.arm-btn-outline { background: var(--surface, #fff); color: var(--text-2, #6b7280); border: 1px solid var(--border, #e5e7eb); }
.arm-btn-outline:hover { border-color: #f59e0b; color: #f59e0b; }
.arm-btn-sm { padding: 5px 12px; font-size: .75rem; }

/* ─── Bulk ─── */
.arm-bulk { display: none; align-items: center; gap: 12px; padding: 10px 16px; background: rgba(245,158,11,.06); border: 1px solid rgba(245,158,11,.15); border-radius: 10px; margin-bottom: 12px; font-size: .78rem; color: #d97706; font-weight: 600; }
.arm-bulk.active { display: flex; }
.arm-bulk select { padding: 5px 10px; border: 1px solid var(--border, #e5e7eb); border-radius: 6px; background: var(--surface, #fff); color: var(--text, #111827); font-size: .75rem; }
.arm-table input[type="checkbox"] { accent-color: #f59e0b; width: 14px; height: 14px; cursor: pointer; }

/* ─── Table ─── */
.arm-table-wrap { background: var(--surface, #fff); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; }
.arm-table { width: 100%; border-collapse: collapse; }
.arm-table thead th { padding: 11px 14px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-3, #9ca3af); background: var(--surface-2, #f9fafb); border-bottom: 1px solid var(--border, #e5e7eb); text-align: left; white-space: nowrap; }
.arm-table thead th.center { text-align: center; }
.arm-table tbody tr { border-bottom: 1px solid var(--border, #f3f4f6); transition: background .1s; }
.arm-table tbody tr:hover { background: rgba(245,158,11,.02); }
.arm-table tbody tr:last-child { border-bottom: none; }
.arm-table td { padding: 11px 14px; font-size: .83rem; color: var(--text, #111827); vertical-align: middle; }
.arm-table td.center { text-align: center; }

/* ─── Cellule titre ─── */
.arm-article-title { font-weight: 600; color: var(--text, #111827); display: flex; align-items: center; gap: 8px; line-height: 1.3; }
.arm-article-title a { color: var(--text, #111827); text-decoration: none; transition: color .15s; }
.arm-article-title a:hover { color: #f59e0b; }
.arm-slug { font-family: monospace; font-size: .72rem; color: var(--text-3, #9ca3af); margin-top: 2px; }
.arm-keyword { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; background: var(--surface-2, #f9fafb); border: 1px solid var(--border, #e5e7eb); border-radius: 20px; font-size: .7rem; font-weight: 600; color: var(--text-2, #6b7280); max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.arm-featured { display: inline-flex; align-items: center; gap: 3px; padding: 2px 7px; background: #fef9c3; border: 1px solid #fde047; border-radius: 4px; font-size: .58rem; font-weight: 700; color: #a16207; text-transform: uppercase; letter-spacing: .04em; }
.arm-category { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; background: rgba(99,102,241,.07); color: #6366f1; border-radius: 5px; font-size: .65rem; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

.arm-status { padding: 3px 10px; border-radius: 12px; font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; display: inline-block; }
.arm-status.published { background: #d1fae5; color: #059669; }
.arm-status.draft     { background: #fef3c7; color: #d97706; }
.arm-status.archived  { background: var(--surface-2, #f3f4f6); color: var(--text-3, #9ca3af); }

/* ─── Scores ─── */
.arm-score-wrap { display: flex; flex-direction: column; align-items: center; gap: 3px; min-width: 54px; }
.arm-score-ring { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 800; border: 3px solid transparent; transition: transform .2s; }
.arm-score-ring:hover { transform: scale(1.08); }
.arm-score-ring.excellent { background: #ecfdf5; border-color: #10b981; color: #059669; }
.arm-score-ring.good      { background: #eff6ff; border-color: #3b82f6; color: #2563eb; }
.arm-score-ring.ok        { background: #fefce8; border-color: #f59e0b; color: #d97706; }
.arm-score-ring.bad       { background: #fef2f2; border-color: #ef4444; color: #dc2626; }
.arm-score-ring.none      { background: var(--surface-2, #f9fafb); border-color: var(--border, #e5e7eb); border-style: dashed; color: var(--text-3, #9ca3af); }
.arm-score-bar  { width: 38px; height: 3px; background: var(--border, #e5e7eb); border-radius: 2px; overflow: hidden; }
.arm-score-bar-fill { height: 100%; border-radius: 2px; transition: width .5s cubic-bezier(.4,0,.2,1); }
.arm-score-bar-fill.excellent { background: #10b981; }
.arm-score-bar-fill.good      { background: #3b82f6; }
.arm-score-bar-fill.ok        { background: #f59e0b; }
.arm-score-bar-fill.bad       { background: #ef4444; }

/* Sémantique inline */
.arm-semantic-row { display: flex; align-items: center; gap: 6px; }
.arm-semantic-bar { width: 44px; height: 5px; background: var(--border, #e5e7eb); border-radius: 3px; overflow: hidden; flex-shrink: 0; }
.arm-semantic-fill { height: 100%; border-radius: 3px; }
.arm-semantic-fill.excellent { background: #10b981; }
.arm-semantic-fill.good      { background: #3b82f6; }
.arm-semantic-fill.ok        { background: #f59e0b; }
.arm-semantic-fill.bad       { background: #ef4444; }
.arm-semantic-val { font-size: .75rem; font-weight: 700; min-width: 28px; font-variant-numeric: tabular-nums; }
.arm-semantic-val.excellent { color: #10b981; }
.arm-semantic-val.good      { color: #3b82f6; }
.arm-semantic-val.ok        { color: #d97706; }
.arm-semantic-val.bad       { color: #dc2626; }
.arm-semantic-val.none      { color: var(--text-3, #9ca3af); }

/* Mots */
.arm-words-cell { display: flex; flex-direction: column; align-items: flex-start; gap: 2px; min-width: 68px; }
.arm-words-val { font-size: .78rem; font-weight: 700; font-variant-numeric: tabular-nums; white-space: nowrap; }
.arm-words-val.excellent { color: #10b981; }
.arm-words-val.good      { color: #3b82f6; }
.arm-words-val.ok        { color: #d97706; }
.arm-words-val.bad       { color: #dc2626; }
.arm-words-val.none      { color: var(--text-3, #9ca3af); }
.arm-words-prog { width: 100%; height: 3px; background: var(--border, #e5e7eb); border-radius: 2px; overflow: hidden; }
.arm-words-prog-fill { height: 100%; border-radius: 2px; transition: width .5s; }
.arm-words-prog-fill.excellent { background: #10b981; }
.arm-words-prog-fill.good      { background: #3b82f6; }
.arm-words-prog-fill.ok        { background: #f59e0b; }
.arm-words-prog-fill.bad       { background: #ef4444; }

/* Indexation */
.arm-indexed { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 10px; font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; }
.arm-indexed.yes     { background: #ecfdf5; color: #059669; }
.arm-indexed.no      { background: #fef2f2; color: #dc2626; }
.arm-indexed.pending { background: #fff7ed; color: #ea580c; }
.arm-indexed.unknown { background: var(--surface-2, #f3f4f6); color: var(--text-3, #9ca3af); }

.arm-date { font-size: .73rem; color: var(--text-3, #9ca3af); white-space: nowrap; }

/* Actions */
.arm-actions { display: flex; gap: 3px; justify-content: flex-end; }
.arm-actions a, .arm-actions button { width: 30px; height: 30px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--text-3, #9ca3af); background: transparent; border: 1px solid transparent; cursor: pointer; transition: all .12s; text-decoration: none; font-size: .78rem; }
.arm-actions a:hover, .arm-actions button:hover { color: #f59e0b; border-color: var(--border, #e5e7eb); background: rgba(245,158,11,.07); }
.arm-actions button.del:hover { color: #dc2626; border-color: rgba(220,38,38,.2); background: #fef2f2; }

/* ══ VUE GRILLE ══════════════════════════════════════════════ */
.arm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
.arm-card { background: var(--surface, #fff); border-radius: 14px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; transition: all .2s; display: flex; flex-direction: column; position: relative; }
.arm-card:hover { border-color: #f59e0b; box-shadow: 0 4px 20px rgba(245,158,11,.1); transform: translateY(-2px); }
.arm-card-top { padding: 16px 16px 12px; flex: 1; }
.arm-card-title { font-size: .88rem; font-weight: 700; color: var(--text, #111827); text-decoration: none; display: block; line-height: 1.35; }
.arm-card-title:hover { color: #f59e0b; }
.arm-card-badges { display: flex; gap: 4px; flex-wrap: wrap; align-items: center; margin-top: 5px; }
.arm-card-slug { font-family: monospace; font-size: .65rem; color: var(--text-3, #9ca3af); margin-top: 5px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.arm-card-kw { font-size: .7rem; color: var(--text-2, #6b7280); margin-top: 6px; display: flex; align-items: center; gap: 4px; }
.arm-card-kw i { color: #9ca3af; font-size: .6rem; }
.arm-card-stats { display: flex; gap: 0; border-top: 1px solid var(--border, #f3f4f6); }
.arm-card-stat { flex: 1; text-align: center; padding: 9px 6px; border-right: 1px solid var(--border, #f3f4f6); }
.arm-card-stat:last-child { border-right: none; }
.arm-card-stat-val { font-size: .82rem; font-weight: 800; color: var(--text, #111827); display: block; }
.arm-card-stat-val.teal   { color: #0d9488; }
.arm-card-stat-val.violet { color: #7c3aed; }
.arm-card-stat-val.green  { color: #10b981; }
.arm-card-stat-val.amber  { color: #d97706; }
.arm-card-stat-lbl { font-size: .55rem; color: var(--text-3, #9ca3af); text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }
.arm-card-footer { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-top: 1px solid var(--border, #f3f4f6); }
.arm-card-footer .arm-actions { justify-content: flex-start; }
.arm-card-status-dot { position: absolute; top: 12px; right: 12px; width: 8px; height: 8px; border-radius: 50%; }
.arm-card-status-dot.published { background: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.15); }
.arm-card-status-dot.draft     { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.15); }
.arm-card-status-dot.archived  { background: #d1d5db; }

/* ─── Masquage vues selon mode ─── */
.arm-list-view .arm-grid-wrap { display: none !important; }
.arm-grid-view .arm-list-wrap { display: none !important; }

/* ─── Pagination ─── */
.arm-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-top: 1px solid var(--border, #e5e7eb); font-size: .78rem; color: var(--text-3, #9ca3af); }
.arm-pagination a { padding: 6px 12px; border: 1px solid var(--border, #e5e7eb); border-radius: 10px; color: var(--text-2, #6b7280); text-decoration: none; font-weight: 600; transition: all .15s; font-size: .78rem; }
.arm-pagination a:hover { border-color: #f59e0b; color: #f59e0b; }
.arm-pagination a.active { background: #f59e0b; color: #fff; border-color: #f59e0b; }

/* ─── Flash ─── */
.arm-flash { padding: 12px 18px; border-radius: 10px; font-size: .85rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; animation: armFlashIn .3s; }
.arm-flash.success { background: #d1fae5; color: #059669; border: 1px solid rgba(5,150,105,.12); }
.arm-flash.error   { background: #fef2f2; color: #dc2626; border: 1px solid rgba(220,38,38,.12); }
@keyframes armFlashIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }

.arm-empty { text-align: center; padding: 60px 20px; color: var(--text-3, #9ca3af); }
.arm-empty i { font-size: 2.5rem; opacity: .2; margin-bottom: 12px; display: block; }
.arm-empty h3 { color: var(--text-2, #6b7280); font-size: 1rem; font-weight: 600; margin-bottom: 6px; }
.arm-empty a { color: #f59e0b; }

@media (max-width: 1200px) { .arm-table .col-indexed, .arm-table .col-date-upd { display: none; } }
@media (max-width: 960px) {
    .arm-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .arm-toolbar { flex-direction: column; align-items: flex-start; }
    .arm-table-wrap { overflow-x: auto; }
    .arm-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
}
</style>

<div class="arm-wrap" id="armWrap">

<?php if ($flash === 'deleted'): ?>
    <div class="arm-flash success"><i class="fas fa-check-circle"></i> Article supprimé avec succès</div>
<?php elseif ($flash === 'created'): ?>
    <div class="arm-flash success"><i class="fas fa-check-circle"></i> Article créé avec succès</div>
<?php elseif ($flash === 'updated'): ?>
    <div class="arm-flash success"><i class="fas fa-check-circle"></i> Article mis à jour</div>
<?php elseif ($flash === 'error'): ?>
    <div class="arm-flash error"><i class="fas fa-exclamation-circle"></i> Une erreur est survenue</div>
<?php endif; ?>

<?php if (!$tableExists): ?>
<div style="background:#fef2f2;border:1px solid rgba(220,38,38,.12);border-radius:12px;padding:28px;text-align:center;color:#dc2626">
    <i class="fas fa-database" style="font-size:2rem;margin-bottom:10px;display:block"></i>
    <h3 style="font-size:1rem;font-weight:700;margin-bottom:6px">Table articles introuvable</h3>
    <p style="font-size:.83rem;opacity:.75">Vérifiez que la table <code>articles</code> existe dans votre base de données.</p>
</div>
<?php else: ?>

<!-- ─── Banner ─── -->
<div class="arm-banner">
    <div class="arm-banner-left">
        <h2><i class="fas fa-pen-fancy"></i> Mon Blog</h2>
        <p>Articles, contenus SEO et stratégie de contenu pour votre site immobilier</p>
    </div>
    <div class="arm-stats">
        <div class="arm-stat"><div class="num blue"><?= $stats['total'] ?></div><div class="lbl">Total</div></div>
        <div class="arm-stat"><div class="num green"><?= $stats['published'] ?></div><div class="lbl">Publiés</div></div>
        <div class="arm-stat"><div class="num amber"><?= $stats['draft'] ?></div><div class="lbl">Brouillons</div></div>
        <?php if ($colSeoScore): ?>
        <div class="arm-stat" title="Score SEO moyen">
            <div class="num teal"><?= $stats['avg_seo'] ?><span style="font-size:.6em;opacity:.6">%</span></div>
            <div class="lbl">SEO Moy.</div>
        </div>
        <?php endif; ?>
        <?php if ($colSemantic): ?>
        <div class="arm-stat" title="Score sémantique moyen">
            <div class="num violet"><?= $stats['avg_semantic'] ?><span style="font-size:.6em;opacity:.6">%</span></div>
            <div class="lbl">Séma. Moy.</div>
        </div>
        <?php endif; ?>
        <?php if ($stats['indexed_count'] > 0): ?>
        <div class="arm-stat"><div class="num teal"><?= $stats['indexed_count'] ?></div><div class="lbl">Indexés</div></div>
        <?php endif; ?>
    </div>
</div>

<!-- ─── Toolbar ─── -->
<div class="arm-toolbar">
    <div class="arm-filters">
        <?php
        $filters = [
            'all'       => ['icon' => 'fa-layer-group', 'label' => 'Tous',       'count' => $stats['total']],
            'published' => ['icon' => 'fa-check-circle','label' => 'Publiés',    'count' => $stats['published']],
            'draft'     => ['icon' => 'fa-pencil-alt',  'label' => 'Brouillons', 'count' => $stats['draft']],
            'archived'  => ['icon' => 'fa-archive',     'label' => 'Archivés',   'count' => $stats['archived']],
        ];
        foreach ($filters as $key => $f):
            $active = ($filterStatus === $key) ? ' active' : '';
            $url = '?page=articles' . ($key !== 'all' ? '&status=' . $key : '');
            if ($searchQuery)             $url .= '&q='        . urlencode($searchQuery);
            if ($filterIndexed !== 'all') $url .= '&indexed='  . $filterIndexed;
            if ($filterCat !== 'all')     $url .= '&category=' . urlencode($filterCat);
        ?>
            <a href="<?= $url ?>" class="arm-fbtn<?= $active ?>">
                <i class="fas <?= $f['icon'] ?>"></i> <?= $f['label'] ?>
                <span class="badge"><?= (int)$f['count'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="arm-toolbar-r">
        <!-- Toggle vue liste / grille -->
        <div class="arm-view-toggle">
            <button class="arm-view-btn active" id="btnList" onclick="ARM.setView('list')" title="Vue liste"><i class="fas fa-list"></i></button>
            <button class="arm-view-btn"         id="btnGrid" onclick="ARM.setView('grid')" title="Vue grille"><i class="fas fa-th-large"></i></button>
        </div>
        <form class="arm-search" method="GET">
            <input type="hidden" name="page" value="articles">
            <?php if ($filterStatus !== 'all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Titre, slug, mot-clé..." value="<?= htmlspecialchars($searchQuery) ?>">
        </form>
        <a href="?page=articles&action=create" class="arm-btn arm-btn-primary"><i class="fas fa-plus"></i> Nouvel article</a>
        <a href="?page=system/settings/ai" class="arm-btn arm-btn-outline" title="Paramètres IA" style="padding:9px 13px;">
            <i class="fas fa-robot"></i>
        </a>
    </div>
</div>

<!-- ─── Sub-filtres ─── -->
<?php if ($hasGoogleIndexed || ($hasCategory && !empty($categories))): ?>
<div class="arm-subfilters">
    <?php if ($hasGoogleIndexed): ?>
    <div class="arm-subfilter">
        <i class="fab fa-google"></i>
        <select onchange="ARM.filterBy('indexed', this.value)">
            <option value="all"     <?= $filterIndexed==='all'     ? 'selected':'' ?>>Toutes indexations</option>
            <option value="yes"     <?= $filterIndexed==='yes'     ? 'selected':'' ?>>✅ Indexé</option>
            <option value="no"      <?= $filterIndexed==='no'      ? 'selected':'' ?>>❌ Non indexé</option>
            <option value="pending" <?= $filterIndexed==='pending' ? 'selected':'' ?>>⏳ En attente</option>
            <option value="unknown" <?= $filterIndexed==='unknown' ? 'selected':'' ?>>❓ Inconnu</option>
        </select>
    </div>
    <?php endif; ?>
    <?php if ($hasCategory && !empty($categories)): ?>
    <div class="arm-subfilter">
        <i class="fas fa-tag"></i>
        <select onchange="ARM.filterBy('category', this.value)">
            <option value="all" <?= $filterCat==='all' ? 'selected':'' ?>>Toutes catégories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= $filterCat===$cat ? 'selected':'' ?>><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ─── Bulk actions ─── -->
<div class="arm-bulk" id="armBulkBar">
    <input type="checkbox" id="armSelectAll" onchange="ARM.toggleAll(this.checked)">
    <span id="armBulkCount">0</span> sélectionné(s)
    <select id="armBulkAction">
        <option value="">— Action groupée —</option>
        <option value="publish">Publier</option>
        <option value="draft">Brouillon</option>
        <option value="archive">Archiver</option>
        <option value="delete">Supprimer</option>
    </select>
    <button class="arm-btn arm-btn-sm arm-btn-outline" onclick="ARM.bulkExecute()"><i class="fas fa-check"></i> Appliquer</button>
</div>

<?php if (empty($articles)): ?>
    <div class="arm-empty">
        <i class="fas fa-pen-fancy"></i>
        <h3>Aucun article trouvé</h3>
        <p>
            <?php if ($searchQuery): ?>
                Aucun résultat pour « <?= htmlspecialchars($searchQuery) ?> ». <a href="?page=articles">Effacer</a>
            <?php else: ?>
                Rédigez votre premier article de blog.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>

<!-- ══ VUE LISTE ══════════════════════════════════════════════ -->
<div class="arm-list-wrap">
    <div class="arm-table-wrap">
        <table class="arm-table">
            <thead>
                <tr>
                    <th style="width:32px"><input type="checkbox" onchange="ARM.toggleAll(this.checked)"></th>
                    <th>Article</th>
                    <th>Mot-clé</th>
                    <th>Statut</th>
                    <th class="center" title="Score SEO technique">SEO</th>
                    <th class="center" title="Score sémantique">Sémantique</th>
                    <th title="Nombre de mots">Mots</th>
                    <?php if ($hasGoogleIndexed || $hasIsIndexed): ?>
                    <th class="col-indexed">Google</th>
                    <?php endif; ?>
                    <th>Date</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($articles as $a):
                $statusNorm = normalizeArticleStatus($a);
                $seo        = getScore($a, 'col_seo', 'ext_seo_score');
                $seoClass   = $seo >= 80 ? 'excellent' : ($seo >= 60 ? 'good' : ($seo >= 40 ? 'ok' : ($seo > 0 ? 'bad' : 'none')));
                $semantic   = getScore($a, 'col_semantic', 'ext_semantic');
                $semClass   = $semantic >= 70 ? 'excellent' : ($semantic >= 50 ? 'good' : ($semantic >= 30 ? 'ok' : ($semantic > 0 ? 'bad' : 'none')));
                $words      = (int)($a['word_count'] ?? 0);
                $wordsPct   = min(100, round($words / 1500 * 100));
                $wordsClass = $words >= 1000 ? 'excellent' : ($words >= 800 ? 'good' : ($words >= 400 ? 'ok' : ($words > 0 ? 'bad' : 'none')));
                $indexed    = $a['google_indexed'] ?? (($a['is_indexed'] ?? false) ? 'yes' : 'unknown');
                $idxLabels  = [
                    'yes'     => ['icon'=>'fa-check-circle',    'label'=>'Indexé',     'cls'=>'yes'],
                    'no'      => ['icon'=>'fa-times-circle',    'label'=>'Non indexé', 'cls'=>'no'],
                    'pending' => ['icon'=>'fa-clock',           'label'=>'En attente', 'cls'=>'pending'],
                    'unknown' => ['icon'=>'fa-question-circle', 'label'=>'Inconnu',    'cls'=>'unknown'],
                ];
                $idxInfo    = $idxLabels[$indexed] ?? $idxLabels['unknown'];
                $keyword    = $a['main_keyword'] ?? '';
                $category   = $a['category']     ?? '';
                $featured   = !empty($a['is_featured']);
                $date       = !empty($a['created_at']) ? date('d/m/Y', strtotime($a['created_at'])) : '—';
                $title      = $a['display_title'] ?? 'Sans titre';
                $editUrl    = "?page=articles&action=edit&id={$a['id']}";
                $viewUrl    = "/blog/" . htmlspecialchars($a['slug'] ?? '');
                $statusLabels = ['published'=>'Publié','draft'=>'Brouillon','archived'=>'Archivé'];
            ?>
            <tr data-id="<?= (int)$a['id'] ?>">
                <td><input type="checkbox" class="arm-cb" value="<?= (int)$a['id'] ?>" onchange="ARM.updateBulk()"></td>

                <td>
                    <div class="arm-article-title">
                        <a href="<?= htmlspecialchars($editUrl) ?>"><?= htmlspecialchars($title) ?></a>
                        <?php if ($featured): ?><span class="arm-featured"><i class="fas fa-star"></i> Top</span><?php endif; ?>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:3px">
                        <span class="arm-slug">/blog/<?= htmlspecialchars($a['slug'] ?? '') ?></span>
                        <?php if ($category): ?>
                            <span class="arm-category"><i class="fas fa-tag"></i> <?= htmlspecialchars($category) ?></span>
                        <?php endif; ?>
                    </div>
                </td>

                <td>
                    <?php if ($keyword): ?>
                        <span class="arm-keyword"><i class="fas fa-key" style="font-size:.6rem;color:#9ca3af;"></i><?= htmlspecialchars($keyword) ?></span>
                    <?php else: ?>
                        <span style="color:var(--text-3,#9ca3af);font-size:.75rem;">—</span>
                    <?php endif; ?>
                </td>

                <td><span class="arm-status <?= $statusNorm ?>"><?= $statusLabels[$statusNorm] ?? $statusNorm ?></span></td>

                <td class="center">
                    <div class="arm-score-wrap">
                        <div class="arm-score-ring <?= $seoClass ?>" title="Score SEO : <?= $seo > 0 ? $seo.'%' : 'Non calculé' ?>">
                            <?= $seo > 0 ? $seo : '—' ?>
                        </div>
                        <div class="arm-score-bar">
                            <div class="arm-score-bar-fill <?= $seoClass ?>" style="width:<?= min(100,$seo) ?>%"></div>
                        </div>
                    </div>
                </td>

                <td class="center">
                    <div class="arm-score-wrap">
                        <div class="arm-semantic-row">
                            <div class="arm-semantic-bar">
                                <div class="arm-semantic-fill <?= $semClass ?>" style="width:<?= min(100,$semantic) ?>%"></div>
                            </div>
                            <span class="arm-semantic-val <?= $semClass ?>"><?= $semantic > 0 ? $semantic.'%' : '—' ?></span>
                        </div>
                    </div>
                </td>

                <td>
                    <div class="arm-words-cell">
                        <span class="arm-words-val <?= $wordsClass ?>">
                            <?= $words > 0 ? number_format($words, 0, ',', "\u{202F}") . ' mots' : '—' ?>
                        </span>
                        <?php if ($words > 0): ?>
                        <div class="arm-words-prog">
                            <div class="arm-words-prog-fill <?= $wordsClass ?>" style="width:<?= $wordsPct ?>%"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </td>

                <?php if ($hasGoogleIndexed || $hasIsIndexed): ?>
                <td class="col-indexed">
                    <span class="arm-indexed <?= $idxInfo['cls'] ?>">
                        <i class="fas <?= $idxInfo['icon'] ?>"></i> <?= $idxInfo['label'] ?>
                    </span>
                </td>
                <?php endif; ?>

                <td><span class="arm-date"><?= $date ?></span></td>

                <td>
                    <div class="arm-actions">
                        <a href="<?= htmlspecialchars($editUrl) ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                        <button onclick="ARM.duplicate(<?= (int)$a['id'] ?>, '<?= addslashes(htmlspecialchars($title)) ?>')" title="Dupliquer"><i class="fas fa-copy"></i></button>
                        <button onclick="ARM.toggleStatus(<?= (int)$a['id'] ?>, '<?= $statusNorm ?>')"
                                title="<?= $statusNorm==='published' ? 'Dépublier' : 'Publier' ?>">
                            <i class="fas <?= $statusNorm==='published' ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                        </button>
                        <?php if (!empty($a['slug'])): ?>
                        <a href="<?= $viewUrl ?>" target="_blank" title="Voir"><i class="fas fa-external-link-alt"></i></a>
                        <?php endif; ?>
                        <button class="del" onclick="ARM.deleteArticle(<?= (int)$a['id'] ?>, '<?= addslashes(htmlspecialchars($title)) ?>')" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="arm-pagination">
            <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?> articles</span>
            <div style="display:flex;gap:4px">
                <?php for ($i=1; $i<=$totalPages; $i++):
                    $pUrl = '?page=articles&p='.$i;
                    if ($filterStatus!=='all')  $pUrl .= '&status='.$filterStatus;
                    if ($filterIndexed!=='all') $pUrl .= '&indexed='.$filterIndexed;
                    if ($filterCat!=='all')     $pUrl .= '&category='.urlencode($filterCat);
                    if ($searchQuery)            $pUrl .= '&q='.urlencode($searchQuery);
                ?>
                    <a href="<?= $pUrl ?>" class="<?= $i===$currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══ VUE GRILLE ══════════════════════════════════════════════ -->
<div class="arm-grid-wrap">
    <div class="arm-grid">
    <?php foreach ($articles as $a):
        $statusNorm = normalizeArticleStatus($a);
        $seo        = getScore($a, 'col_seo', 'ext_seo_score');
        $seoClass   = $seo >= 80 ? 'excellent' : ($seo >= 60 ? 'good' : ($seo >= 40 ? 'ok' : ($seo > 0 ? 'bad' : 'none')));
        $semantic   = getScore($a, 'col_semantic', 'ext_semantic');
        $words      = (int)($a['word_count'] ?? 0);
        $keyword    = $a['main_keyword'] ?? '';
        $category   = $a['category']     ?? '';
        $featured   = !empty($a['is_featured']);
        $date       = !empty($a['created_at']) ? date('d/m/Y', strtotime($a['created_at'])) : '—';
        $title      = $a['display_title'] ?? 'Sans titre';
        $editUrl    = "?page=articles&action=edit&id={$a['id']}";
        $viewUrl    = "/blog/" . htmlspecialchars($a['slug'] ?? '');
        $statusLabels = ['published'=>'Publié','draft'=>'Brouillon','archived'=>'Archivé'];
    ?>
    <div class="arm-card" data-id="<?= (int)$a['id'] ?>">
        <div class="arm-card-status-dot <?= $statusNorm ?>" title="<?= $statusLabels[$statusNorm] ?? $statusNorm ?>"></div>
        <div class="arm-card-top">
            <a href="<?= htmlspecialchars($editUrl) ?>" class="arm-card-title"><?= htmlspecialchars($title) ?></a>
            <div class="arm-card-badges">
                <span class="arm-status <?= $statusNorm ?>" style="font-size:.55rem;padding:2px 7px"><?= $statusLabels[$statusNorm] ?? $statusNorm ?></span>
                <?php if ($featured): ?><span class="arm-featured"><i class="fas fa-star"></i> Top</span><?php endif; ?>
                <?php if ($category): ?><span class="arm-category" style="font-size:.6rem"><?= htmlspecialchars($category) ?></span><?php endif; ?>
            </div>
            <span class="arm-card-slug">/blog/<?= htmlspecialchars($a['slug'] ?? '') ?></span>
            <?php if ($keyword): ?>
            <div class="arm-card-kw"><i class="fas fa-key"></i><?= htmlspecialchars($keyword) ?></div>
            <?php endif; ?>
        </div>
        <div class="arm-card-stats">
            <div class="arm-card-stat">
                <span class="arm-card-stat-val teal"><?= $seo > 0 ? $seo.'%' : '—' ?></span>
                <span class="arm-card-stat-lbl">SEO</span>
            </div>
            <div class="arm-card-stat">
                <span class="arm-card-stat-val violet"><?= $semantic > 0 ? $semantic.'%' : '—' ?></span>
                <span class="arm-card-stat-lbl">Séma.</span>
            </div>
            <div class="arm-card-stat">
                <span class="arm-card-stat-val <?= $words >= 1000 ? 'green' : ($words >= 400 ? 'amber' : '') ?>"><?= $words > 0 ? $words : '—' ?></span>
                <span class="arm-card-stat-lbl">Mots</span>
            </div>
            <div class="arm-card-stat">
                <span class="arm-card-stat-val" style="font-size:.72rem;color:var(--text-3)"><?= $date ?></span>
                <span class="arm-card-stat-lbl">Date</span>
            </div>
        </div>
        <div class="arm-card-footer">
            <div class="arm-actions" style="justify-content:flex-start">
                <a href="<?= htmlspecialchars($editUrl) ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                <button onclick="ARM.duplicate(<?= (int)$a['id'] ?>, '<?= addslashes(htmlspecialchars($title)) ?>')" title="Dupliquer"><i class="fas fa-copy"></i></button>
                <button onclick="ARM.toggleStatus(<?= (int)$a['id'] ?>, '<?= $statusNorm ?>')" title="<?= $statusNorm==='published' ? 'Dépublier' : 'Publier' ?>">
                    <i class="fas <?= $statusNorm==='published' ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                </button>
                <?php if (!empty($a['slug'])): ?>
                <a href="<?= $viewUrl ?>" target="_blank" title="Voir"><i class="fas fa-external-link-alt"></i></a>
                <?php endif; ?>
                <button class="del" onclick="ARM.deleteArticle(<?= (int)$a['id'] ?>, '<?= addslashes(htmlspecialchars($title)) ?>')" title="Supprimer"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="arm-pagination" style="background:var(--surface,#fff);border-radius:12px;border:1px solid var(--border,#e5e7eb);margin-top:12px">
        <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?> articles</span>
        <div style="display:flex;gap:4px">
            <?php for ($i=1; $i<=$totalPages; $i++):
                $pUrl = '?page=articles&p='.$i;
                if ($filterStatus!=='all')  $pUrl .= '&status='.$filterStatus;
                if ($filterIndexed!=='all') $pUrl .= '&indexed='.$filterIndexed;
                if ($filterCat!=='all')     $pUrl .= '&category='.urlencode($filterCat);
                if ($searchQuery)            $pUrl .= '&q='.urlencode($searchQuery);
            ?>
                <a href="<?= $pUrl ?>" class="<?= $i===$currentPage ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php endif; ?>
</div><!-- /arm-wrap -->

<!-- ══ MODAL CUSTOM ══════════════════════════════════════════ -->
<div id="armModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    <div onclick="ARM.modalClose()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px);"></div>
    <div id="armModalBox" style="position:relative;z-index:1;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:420px;margin:16px;overflow:hidden;transform:scale(.94) translateY(8px);transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .15s;opacity:0;">
        <div id="armModalHeader" style="padding:20px 22px 16px;display:flex;align-items:flex-start;gap:14px;">
            <div id="armModalIcon" style="width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;"></div>
            <div style="flex:1;min-width:0;">
                <div id="armModalTitle" style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:5px;"></div>
                <div id="armModalMsg"   style="font-size:.82rem;color:#6b7280;line-height:1.5;"></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding:12px 20px 18px;border-top:1px solid #f3f4f6;">
            <button onclick="ARM.modalClose()" style="padding:9px 20px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:.83rem;font-weight:600;cursor:pointer;font-family:inherit;" onmouseover="this.style.borderColor='#f59e0b';this.style.color='#f59e0b'" onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#374151'">Annuler</button>
            <button id="armModalConfirm" style="padding:9px 20px;border-radius:10px;border:none;font-size:.83rem;font-weight:700;cursor:pointer;font-family:inherit;color:#fff;"></button>
        </div>
    </div>
</div>

<script>
const ARM = {
    apiUrl: '/admin/modules/articles/api/articles.php',
    _modalCb: null,

    // ── Filtres selects ────────────────────────────────────
    filterBy(key, value) {
        const url = new URL(window.location.href);
        value === 'all' ? url.searchParams.delete(key) : url.searchParams.set(key, value);
        url.searchParams.delete('p');
        window.location.href = url.toString();
    },

    // ── Toggle vue liste / grille ──────────────────────────
    setView(v) {
        const wrap = document.getElementById('armWrap');
        wrap.classList.remove('arm-list-view', 'arm-grid-view');
        wrap.classList.add(v === 'grid' ? 'arm-grid-view' : 'arm-list-view');
        document.getElementById('btnList').classList.toggle('active', v !== 'grid');
        document.getElementById('btnGrid').classList.toggle('active', v === 'grid');
        try { sessionStorage.setItem('arm_view', v); } catch(e) {}
    },
    initView() {
        let v = 'list';
        try { v = sessionStorage.getItem('arm_view') || 'list'; } catch(e) {}
        this.setView(v);
    },

    // ── Bulk ───────────────────────────────────────────────
    toggleAll(checked) {
        document.querySelectorAll('.arm-cb').forEach(cb => cb.checked = checked);
        this.updateBulk();
    },
    updateBulk() {
        const checked = document.querySelectorAll('.arm-cb:checked');
        document.getElementById('armBulkCount').textContent = checked.length;
        document.getElementById('armBulkBar').classList.toggle('active', checked.length > 0);
    },
    async bulkExecute() {
        const action = document.getElementById('armBulkAction').value;
        if (!action) return;
        const ids = [...document.querySelectorAll('.arm-cb:checked')].map(cb => parseInt(cb.value));
        if (!ids.length) return;
        if (action === 'delete') {
            this.modal({
                icon: '<i class="fas fa-trash"></i>', iconBg: '#fef2f2', iconColor: '#dc2626',
                title: `Supprimer ${ids.length} article(s) ?`,
                msg: 'Cette action est irréversible.',
                confirmLabel: 'Supprimer', confirmColor: '#dc2626',
                onConfirm: async () => {
                    const fd = new FormData();
                    fd.append('action', 'bulk_delete'); fd.append('ids', JSON.stringify(ids));
                    const r = await fetch(this.apiUrl, {method:'POST', body:fd});
                    const d = await r.json();
                    d.success ? location.reload() : this.toast(d.error || 'Erreur', 'error');
                }
            });
            return;
        }
        const fd = new FormData();
        fd.append('action', 'bulk_status');
        fd.append('status', {publish:'published', draft:'draft', archive:'archived'}[action]);
        fd.append('ids', JSON.stringify(ids));
        const r = await fetch(this.apiUrl, {method:'POST', body:fd});
        const d = await r.json();
        d.success ? location.reload() : this.toast(d.error || 'Erreur', 'error');
    },

    // ── Modal ──────────────────────────────────────────────
    modal({ icon, iconBg, iconColor, title, msg, confirmLabel, confirmColor, onConfirm }) {
        const el  = document.getElementById('armModal');
        const box = document.getElementById('armModalBox');
        document.getElementById('armModalIcon').innerHTML    = icon;
        document.getElementById('armModalIcon').style.background = iconBg;
        document.getElementById('armModalIcon').style.color      = iconColor;
        document.getElementById('armModalHeader').style.background = iconBg + '33';
        document.getElementById('armModalTitle').textContent = title;
        document.getElementById('armModalMsg').innerHTML     = msg;
        const btn = document.getElementById('armModalConfirm');
        btn.textContent      = confirmLabel || 'Confirmer';
        btn.style.background = confirmColor || '#f59e0b';
        btn.onmouseover = () => btn.style.filter = 'brightness(.88)';
        btn.onmouseout  = () => btn.style.filter = '';
        this._modalCb = onConfirm;
        btn.onclick = () => { this.modalClose(); if (this._modalCb) this._modalCb(); };
        el.style.display = 'flex';
        requestAnimationFrame(() => { box.style.opacity='1'; box.style.transform='scale(1) translateY(0)'; });
        document.addEventListener('keydown', this._escHandler);
    },
    modalClose() {
        const el  = document.getElementById('armModal');
        const box = document.getElementById('armModalBox');
        box.style.opacity='0'; box.style.transform='scale(.94) translateY(8px)';
        setTimeout(() => el.style.display='none', 160);
        document.removeEventListener('keydown', this._escHandler);
    },
    _escHandler(e) { if (e.key === 'Escape') ARM.modalClose(); },

    // ── Toast ──────────────────────────────────────────────
    toast(msg, type = 'success') {
        const colors = {success:'#059669', error:'#dc2626', info:'#3b82f6'};
        const icons  = {success:'✓', error:'✕', info:'ℹ'};
        const t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:10000;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:10px;font-size:.83rem;font-weight:600;color:#111827;box-shadow:0 8px 24px rgba(0,0,0,.12);transform:translateY(20px);opacity:0;transition:all .25s;';
        t.innerHTML = `<span style="width:22px;height:22px;border-radius:50%;background:${colors[type]}22;color:${colors[type]};display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800">${icons[type]}</span>${msg}`;
        document.body.appendChild(t);
        requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; });
        setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(10px)'; setTimeout(()=>t.remove(),250); }, 3500);
    },

    // ── Delete ─────────────────────────────────────────────
    deleteArticle(id, title) {
        this.modal({
            icon: '<i class="fas fa-trash"></i>', iconBg: '#fef2f2', iconColor: '#dc2626',
            title: 'Supprimer cet article ?',
            msg: `L'article <strong>${title}</strong> sera supprimé définitivement.<br><span style="font-size:.78rem;color:#9ca3af">Cette action est irréversible.</span>`,
            confirmLabel: 'Supprimer', confirmColor: '#dc2626',
            onConfirm: async () => {
                const fd = new FormData();
                fd.append('action', 'delete'); fd.append('id', id);
                try {
                    const r = await fetch(this.apiUrl, {method:'POST', body:fd});
                    const d = await r.json();
                    if (d.success) {
                        document.querySelectorAll(`[data-id="${id}"]`).forEach(el => {
                            el.style.cssText = 'opacity:0;transform:scale(.95);transition:all .3s';
                            setTimeout(() => el.remove(), 300);
                        });
                        this.toast('Article supprimé', 'success');
                    } else { this.toast(d.error || 'Erreur', 'error'); }
                } catch(e) { this.toast('Erreur réseau : ' + e.message, 'error'); }
            }
        });
    },

    // ── Toggle status ──────────────────────────────────────
    async toggleStatus(id, current) {
        const newStatus = current === 'published' ? 'draft' : 'published';
        const fd = new FormData();
        fd.append('action', 'toggle_status'); fd.append('id', id); fd.append('status', newStatus);
        try {
            const r = await fetch(this.apiUrl, {method:'POST', body:fd});
            const d = await r.json();
            if (d.success) {
                this.toast(newStatus === 'published' ? 'Article publié ✓' : 'Article dépublié', 'success');
                setTimeout(() => location.reload(), 800);
            } else { this.toast(d.error || 'Erreur', 'error'); }
        } catch(e) { this.toast('Erreur réseau', 'error'); }
    },

    // ── Duplicate ──────────────────────────────────────────
    duplicate(id, title) {
        this.modal({
            icon: '<i class="fas fa-copy"></i>', iconBg: '#eff6ff', iconColor: '#3b82f6',
            title: 'Dupliquer cet article ?',
            msg: `Une copie brouillon de <strong>${title}</strong> sera créée.<br><span style="font-size:.78rem;color:#9ca3af">Vous pourrez la modifier avant de la publier.</span>`,
            confirmLabel: 'Dupliquer', confirmColor: '#3b82f6',
            onConfirm: async () => {
                const fd = new FormData();
                fd.append('action', 'duplicate'); fd.append('id', id);
                try {
                    const r = await fetch(this.apiUrl, {method:'POST', body:fd});
                    const d = await r.json();
                    if (d.success) { this.toast('Article dupliqué ✓', 'success'); setTimeout(() => location.reload(), 800); }
                    else { this.toast(d.error || 'Erreur', 'error'); }
                } catch(e) { this.toast('Erreur réseau', 'error'); }
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => ARM.initView());
</script>