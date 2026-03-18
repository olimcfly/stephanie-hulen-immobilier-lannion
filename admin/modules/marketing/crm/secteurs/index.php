<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE CMS SECTEURS  v1.0
 * /admin/modules/cms/secteurs/index.php
 * Pattern aligné pages v1.0 / contacts v1.0 :
 *   - Détection dynamique des colonnes
 *   - Toggle vue liste / grille (carte avec hero_image)
 *   - Modal custom + Toast notifications
 *   - Bulk actions
 *   - Sub-filtres (type_secteur, ville, status)
 * ══════════════════════════════════════════════════════════════
 */

// ─── Connexion DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

// ─── Détecter table ───
$tableName   = 'secteurs';
$tableExists = true;
try {
    $pdo->query("SELECT 1 FROM secteurs LIMIT 1");
} catch (PDOException $e) {
    $tableExists = false;
}

// ─── Colonnes disponibles ───
$availCols = [];
if ($tableExists) {
    try {
        $availCols = $pdo->query("SHOW COLUMNS FROM `{$tableName}`")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

// ─── Mapping colonnes ───
$hasNom          = in_array('nom',            $availCols);
$hasSlug         = in_array('slug',           $availCols);
$hasVille        = in_array('ville',          $availCols);
$hasTypeSecteur  = in_array('type_secteur',   $availCols);
$hasType         = in_array('type',           $availCols);
$hasStatus       = in_array('status',         $availCols);
$hasActif        = in_array('actif',          $availCols);
$hasMetaTitle    = in_array('meta_title',     $availCols);
$hasMetaDesc     = in_array('meta_description',$availCols);
$hasHeroImage    = in_array('hero_image',     $availCols);
$hasOgImage      = in_array('og_image',       $availCols);
$hasImage        = in_array('image',          $availCols);
$hasPrixMin      = in_array('prix_min',       $availCols);
$hasPrixMax      = in_array('prix_max',       $availCols);
$hasPrixMoyen    = in_array('prix_moyen_m2',  $availCols) || in_array('prix_moyen', $availCols);
$colPrixMoyen    = in_array('prix_moyen_m2',  $availCols) ? 'prix_moyen_m2' : (in_array('prix_moyen', $availCols) ? 'prix_moyen' : null);
$hasRendMin      = in_array('rendement_min',  $availCols);
$hasRendMax      = in_array('rendement_max',  $availCols);
$hasEvoPrix      = in_array('evolution_prix', $availCols);
$hasDelaiVente   = in_array('delai_vente',    $availCols);
$hasLatitude     = in_array('latitude',       $availCols);
$hasLongitude    = in_array('longitude',      $availCols);
$hasCodePostal   = in_array('code_postal',    $availCols);
$hasSeoScore     = in_array('seo_score',      $availCols);
$hasMetaRobots   = in_array('meta_robots',    $availCols);
$hasUpdatedAt    = in_array('updated_at',     $availCols);
$hasCreatedAt    = in_array('created_at',     $availCols);
$hasSiteId       = in_array('site_id',        $availCols);
$hasTemplateId   = in_array('template_id',    $availCols);
$colTypeSecteur  = $hasTypeSecteur ? 'type_secteur' : ($hasType ? 'type' : null);
$hasPresentation = in_array('presentation',   $availCols);
$hasAtouts       = in_array('atouts',         $availCols);
$hasFaq          = in_array('faq',            $availCols);

// ─── ROUTING ───
$routeAction = $_GET['action'] ?? '';
if (in_array($routeAction, ['edit', 'create', 'delete'])) {
    $editFile = __DIR__ . '/edit.php';
    if (file_exists($editFile)) { require $editFile; return; }
    else {
        echo '<div style="background:#fee2e2;color:#991b1b;padding:20px;border-radius:10px;margin:20px;">
            <strong>⚠️ Fichier manquant :</strong> <code>/admin/modules/cms/secteurs/edit.php</code></div>';
        return;
    }
}

// ─── Listes pour sub-filtres ───
$villesList = [];
$typesList  = [];
if ($tableExists) {
    try {
        if ($hasVille)
            $villesList = $pdo->query("SELECT DISTINCT ville FROM secteurs WHERE ville IS NOT NULL AND ville != '' ORDER BY ville")->fetchAll(PDO::FETCH_COLUMN);
        if ($colTypeSecteur)
            $typesList = $pdo->query("SELECT DISTINCT `{$colTypeSecteur}` FROM secteurs WHERE `{$colTypeSecteur}` IS NOT NULL ORDER BY `{$colTypeSecteur}`")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

// ─── Filtres URL ───
$filterStatus = $_GET['status']   ?? 'all';
$filterVille  = $_GET['ville']    ?? 'all';
$filterType   = $_GET['type']     ?? 'all';
$searchQuery  = trim($_GET['q']   ?? '');
$currentPage  = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 25;
$offset       = ($currentPage - 1) * $perPage;

// ─── Stats globales ───
$stats = ['total' => 0, 'published' => 0, 'draft' => 0, 'archived' => 0, 'quartier' => 0, 'commune' => 0, 'avg_prix' => 0];
if ($tableExists) {
    try {
        $stats['total']     = (int)$pdo->query("SELECT COUNT(*) FROM secteurs")->fetchColumn();
        if ($hasStatus) {
            $stats['published'] = (int)$pdo->query("SELECT COUNT(*) FROM secteurs WHERE status = 'published'")->fetchColumn();
            $stats['draft']     = (int)$pdo->query("SELECT COUNT(*) FROM secteurs WHERE status = 'draft'")->fetchColumn();
            $stats['archived']  = (int)$pdo->query("SELECT COUNT(*) FROM secteurs WHERE status = 'archived'")->fetchColumn();
        }
        if ($colTypeSecteur) {
            $stats['quartier'] = (int)$pdo->query("SELECT COUNT(*) FROM secteurs WHERE `{$colTypeSecteur}` = 'quartier'")->fetchColumn();
            $stats['commune']  = (int)$pdo->query("SELECT COUNT(*) FROM secteurs WHERE `{$colTypeSecteur}` = 'commune'")->fetchColumn();
        }
        if ($hasPrixMin && $hasPrixMax)
            $stats['avg_prix'] = (int)$pdo->query("SELECT ROUND(AVG((prix_min + prix_max) / 2)) FROM secteurs WHERE prix_min > 0")->fetchColumn();
    } catch (PDOException $e) {}
}

// ─── WHERE ───
$where  = [];
$params = [];

if ($filterStatus !== 'all' && $hasStatus) {
    $where[] = "s.status = ?"; $params[] = $filterStatus;
}
if ($filterVille !== 'all' && $hasVille) {
    $where[] = "s.ville = ?"; $params[] = $filterVille;
}
if ($filterType !== 'all' && $colTypeSecteur) {
    $where[] = "s.`{$colTypeSecteur}` = ?"; $params[] = $filterType;
}
if ($searchQuery !== '') {
    $parts = ["s.nom LIKE ?", "s.slug LIKE ?"];
    $params[] = "%{$searchQuery}%"; $params[] = "%{$searchQuery}%";
    if ($hasVille)      { $parts[] = "s.ville LIKE ?";       $params[] = "%{$searchQuery}%"; }
    if ($hasCodePostal) { $parts[] = "s.code_postal LIKE ?"; $params[] = "%{$searchQuery}%"; }
    if ($hasMetaTitle)  { $parts[] = "s.meta_title LIKE ?";  $params[] = "%{$searchQuery}%"; }
    $where[] = '(' . implode(' OR ', $parts) . ')';
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ─── Total filtré + données ───
$totalFiltered = 0;
$secteurs      = [];
$totalPages    = 1;

if ($tableExists) {
    try {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM `{$tableName}` s {$whereSQL}");
        $stmtCount->execute($params);
        $totalFiltered = (int)$stmtCount->fetchColumn();
        $totalPages    = max(1, ceil($totalFiltered / $perPage));

        $sel = ["s.id", "s.nom", "s.slug", "s.created_at"];
        if ($hasVille)       $sel[] = "s.ville";
        if ($colTypeSecteur) $sel[] = "s.`{$colTypeSecteur}` AS col_type";
        if ($hasStatus)      $sel[] = "s.status";
        if ($hasActif)       $sel[] = "s.actif";
        if ($hasHeroImage)   $sel[] = "s.hero_image";
        if ($hasOgImage)     $sel[] = "s.og_image";
        if ($hasImage)       $sel[] = "s.image";
        if ($hasMetaTitle)   $sel[] = "s.meta_title";
        if ($hasMetaDesc)    $sel[] = "s.meta_description";
        if ($hasPrixMin)     $sel[] = "s.prix_min";
        if ($hasPrixMax)     $sel[] = "s.prix_max";
        if ($colPrixMoyen)   $sel[] = "s.`{$colPrixMoyen}` AS col_prix_moyen";
        if ($hasRendMin)     $sel[] = "s.rendement_min";
        if ($hasRendMax)     $sel[] = "s.rendement_max";
        if ($hasEvoPrix)     $sel[] = "s.evolution_prix";
        if ($hasDelaiVente)  $sel[] = "s.delai_vente";
        if ($hasCodePostal)  $sel[] = "s.code_postal";
        if ($hasMetaRobots)  $sel[] = "s.meta_robots";
        if ($hasUpdatedAt)   $sel[] = "s.updated_at";

        $colsSQL = implode(', ', $sel);
        $stmt = $pdo->prepare("SELECT {$colsSQL} FROM `{$tableName}` s {$whereSQL} ORDER BY s.nom ASC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        $secteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("[CMS Secteurs] SQL Error: " . $e->getMessage());
    }
}

// ─── CSRF ───
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Helpers ───
function getSecteurTypeInfo(string $t): array {
    $map = [
        'quartier' => ['icon' => 'fa-map-marker-alt', 'label' => 'Quartier', 'color' => '#6366f1', 'bg' => '#ede9fe'],
        'commune'  => ['icon' => 'fa-city',           'label' => 'Commune',  'color' => '#0d9488', 'bg' => '#ccfbf1'],
        'zone'     => ['icon' => 'fa-draw-polygon',   'label' => 'Zone',     'color' => '#d97706', 'bg' => '#fef3c7'],
    ];
    return $map[$t] ?? ['icon' => 'fa-map', 'label' => ucfirst($t), 'color' => '#6b7280', 'bg' => '#f3f4f6'];
}

function getSecteurImage(array $s): string {
    return $s['hero_image'] ?? $s['og_image'] ?? $s['image'] ?? '';
}

function formatPrix(int $min, int $max): string {
    if ($min <= 0 && $max <= 0) return '—';
    if ($min > 0 && $max > 0) return number_format($min, 0, ',', ' ') . ' – ' . number_format($max, 0, ',', ' ') . ' €/m²';
    $val = $min > 0 ? $min : $max;
    return number_format($val, 0, ',', ' ') . ' €/m²';
}

$flash = $_GET['msg'] ?? '';
?>

<style>
/* ══════════════════════════════════════════════════════════════
   CMS SECTEURS MODULE v1.0
   Pattern identique pages v1.0 / contacts v1.0
══════════════════════════════════════════════════════════════ */
.sect-wrap { font-family: var(--font, 'Inter', sans-serif); }

/* ─── Banner ─── */
.sect-banner {
    background: var(--surface,#fff);
    border-radius: 16px; padding: 26px 30px; margin-bottom: 22px;
    display: flex; align-items: center; justify-content: space-between;
    border: 1px solid var(--border,#e5e7eb); position: relative; overflow: hidden;
}
.sect-banner::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #6366f1, #0d9488, #d97706);
}
.sect-banner::after {
    content: ''; position: absolute; top: -40%; right: -5%;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(99,102,241,.05), transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.sect-banner-left { position: relative; z-index: 1; }
.sect-banner-left h2 { font-size: 1.35rem; font-weight: 700; color: var(--text,#111827); margin: 0 0 4px; display: flex; align-items: center; gap: 10px; letter-spacing: -.02em; }
.sect-banner-left h2 i { font-size: 16px; color: #6366f1; }
.sect-banner-left p { color: var(--text-2,#6b7280); font-size: .85rem; margin: 0; }
.sect-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.sect-stat { text-align: center; padding: 10px 16px; background: var(--surface-2,#f9fafb); border-radius: 12px; border: 1px solid var(--border,#e5e7eb); min-width: 72px; transition: all .2s; }
.sect-stat:hover { border-color: var(--border-h,#d1d5db); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.sect-stat .num { font-size: 1.45rem; font-weight: 800; line-height: 1; letter-spacing: -.03em; }
.sect-stat .num.indigo { color: #6366f1; }
.sect-stat .num.green  { color: #10b981; }
.sect-stat .num.amber  { color: #f59e0b; }
.sect-stat .num.teal   { color: #0d9488; }
.sect-stat .num.gray   { color: var(--text,#111827); }
.sect-stat .lbl { font-size: .58rem; color: var(--text-3,#9ca3af); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }

/* ─── Toolbar ─── */
.sect-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.sect-filters { display: flex; gap: 3px; background: var(--surface,#fff); border: 1px solid var(--border,#e5e7eb); border-radius: 10px; padding: 3px; flex-wrap: wrap; }
.sect-fbtn { padding: 7px 15px; border: none; background: transparent; color: var(--text-2,#6b7280); font-size: .78rem; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all .15s; font-family: inherit; display: flex; align-items: center; gap: 5px; text-decoration: none; }
.sect-fbtn:hover { color: var(--text,#111827); background: var(--surface-2,#f9fafb); }
.sect-fbtn.active { background: #6366f1; color: #fff; box-shadow: 0 1px 4px rgba(99,102,241,.25); }
.sect-fbtn .badge { font-size: .68rem; padding: 1px 7px; border-radius: 10px; background: var(--surface-2,#f3f4f6); font-weight: 700; color: var(--text-3,#9ca3af); }
.sect-fbtn.active .badge { background: rgba(255,255,255,.22); color: #fff; }

/* ─── Sub-filtres ─── */
.sect-subfilters { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
.sect-subfilter { display: flex; align-items: center; gap: 5px; font-size: .75rem; color: var(--text-2,#6b7280); }
.sect-subfilter select { padding: 5px 10px; border: 1px solid var(--border,#e5e7eb); border-radius: 6px; background: var(--surface,#fff); color: var(--text,#111827); font-size: .75rem; font-family: inherit; cursor: pointer; }
.sect-subfilter select:focus { outline: none; border-color: #6366f1; }

/* ─── Toolbar right ─── */
.sect-toolbar-r { display: flex; align-items: center; gap: 10px; }
.sect-view-toggle { display: flex; gap: 2px; background: var(--surface-2,#f9fafb); border: 1px solid var(--border,#e5e7eb); border-radius: 8px; padding: 3px; }
.sect-view-btn { width: 30px; height: 28px; border: none; background: transparent; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-3,#9ca3af); transition: all .15s; font-size: .78rem; }
.sect-view-btn:hover { color: var(--text,#111827); }
.sect-view-btn.active { background: white; color: #6366f1; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.sect-search { position: relative; }
.sect-search input { padding: 8px 12px 8px 34px; background: var(--surface,#fff); border: 1px solid var(--border,#e5e7eb); border-radius: 10px; color: var(--text,#111827); font-size: .82rem; width: 220px; font-family: inherit; transition: all .2s; }
.sect-search input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); width: 260px; }
.sect-search input::placeholder { color: var(--text-3,#9ca3af); }
.sect-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-3,#9ca3af); font-size: .75rem; }
.sect-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 10px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; font-family: inherit; text-decoration: none; }
.sect-btn-primary { background: #6366f1; color: #fff; box-shadow: 0 1px 4px rgba(99,102,241,.22); }
.sect-btn-primary:hover { background: #4f46e5; transform: translateY(-1px); color: #fff; }
.sect-btn-outline { background: var(--surface,#fff); color: var(--text-2,#6b7280); border: 1px solid var(--border,#e5e7eb); }
.sect-btn-outline:hover { border-color: #6366f1; color: #6366f1; }
.sect-btn-sm { padding: 5px 12px; font-size: .75rem; }

/* ─── Bulk ─── */
.sect-bulk { display: none; align-items: center; gap: 12px; padding: 10px 16px; background: rgba(99,102,241,.06); border: 1px solid rgba(99,102,241,.15); border-radius: 10px; margin-bottom: 12px; font-size: .78rem; color: #6366f1; font-weight: 600; }
.sect-bulk.active { display: flex; }
.sect-bulk select { padding: 5px 10px; border: 1px solid var(--border,#e5e7eb); border-radius: 6px; background: var(--surface,#fff); color: var(--text,#111827); font-size: .75rem; }
.sect-table input[type="checkbox"] { accent-color: #6366f1; width: 14px; height: 14px; cursor: pointer; }

/* ─── Table ─── */
.sect-table-wrap { background: var(--surface,#fff); border-radius: 12px; border: 1px solid var(--border,#e5e7eb); overflow: hidden; }
.sect-table { width: 100%; border-collapse: collapse; }
.sect-table thead th { padding: 11px 14px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-3,#9ca3af); background: var(--surface-2,#f9fafb); border-bottom: 1px solid var(--border,#e5e7eb); text-align: left; white-space: nowrap; }
.sect-table thead th.center { text-align: center; }
.sect-table tbody tr { border-bottom: 1px solid var(--border,#f3f4f6); transition: background .1s; }
.sect-table tbody tr:hover { background: rgba(99,102,241,.02); }
.sect-table tbody tr:last-child { border-bottom: none; }
.sect-table td { padding: 11px 14px; font-size: .83rem; color: var(--text,#111827); vertical-align: middle; }
.sect-table td.center { text-align: center; }

/* ─── Miniature hero ─── */
.sect-thumb { width: 52px; height: 38px; border-radius: 6px; object-fit: cover; background: var(--surface-2,#f3f4f6); display: block; flex-shrink: 0; }
.sect-thumb-placeholder { width: 52px; height: 38px; border-radius: 6px; background: var(--surface-2,#f3f4f6); display: flex; align-items: center; justify-content: center; color: var(--text-3,#9ca3af); font-size: .72rem; flex-shrink: 0; }
.sect-nom-cell { display: flex; align-items: center; gap: 10px; }
.sect-nom-link { font-weight: 600; color: var(--text,#111827); text-decoration: none; transition: color .15s; }
.sect-nom-link:hover { color: #6366f1; }
.sect-slug { font-family: monospace; font-size: .68rem; color: var(--text-3,#9ca3af); margin-top: 2px; }

/* ─── Type badge ─── */
.sect-type { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }

/* ─── Statut ─── */
.sect-status { padding: 3px 10px; border-radius: 12px; font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; display: inline-block; }
.sect-status.published { background: #d1fae5; color: #059669; }
.sect-status.draft     { background: #fef3c7; color: #d97706; }
.sect-status.archived  { background: var(--surface-2,#f3f4f6); color: var(--text-3,#9ca3af); }

/* ─── Prix ─── */
.sect-prix { font-size: .78rem; font-weight: 700; color: #6366f1; white-space: nowrap; }
.sect-prix-evo { font-size: .65rem; color: #10b981; font-weight: 600; margin-top: 1px; }

/* ─── Meta dots ─── */
.sect-meta-check { display: flex; gap: 4px; flex-wrap: wrap; }
.sect-meta-dot { width: 7px; height: 7px; border-radius: 50%; }
.sect-meta-dot.ok  { background: #10b981; }
.sect-meta-dot.bad { background: #e5e7eb; }
.sect-meta-label { font-size: .65rem; color: var(--text-3,#9ca3af); margin-top: 2px; }

/* ─── Date ─── */
.sect-date { font-size: .73rem; color: var(--text-3,#9ca3af); white-space: nowrap; }

/* ─── Actions ─── */
.sect-actions { display: flex; gap: 3px; justify-content: flex-end; }
.sect-actions a, .sect-actions button { width: 30px; height: 30px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--text-3,#9ca3af); background: transparent; border: 1px solid transparent; cursor: pointer; transition: all .12s; text-decoration: none; font-size: .78rem; }
.sect-actions a:hover, .sect-actions button:hover { color: #6366f1; border-color: var(--border,#e5e7eb); background: rgba(99,102,241,.07); }
.sect-actions button.del:hover { color: #dc2626; border-color: rgba(220,38,38,.2); background: #fef2f2; }

/* ══ VUE GRILLE ══════════════════════════════════════════════ */
.sect-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
.sect-card { background: var(--surface,#fff); border-radius: 14px; border: 1px solid var(--border,#e5e7eb); overflow: hidden; transition: all .2s; display: flex; flex-direction: column; position: relative; }
.sect-card:hover { border-color: #6366f1; box-shadow: 0 4px 20px rgba(99,102,241,.1); transform: translateY(-2px); }
.sect-card-img { height: 140px; background: var(--surface-2,#f3f4f6); overflow: hidden; position: relative; }
.sect-card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .3s; }
.sect-card:hover .sect-card-img img { transform: scale(1.04); }
.sect-card-img-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--text-3,#9ca3af); font-size: 2rem; }
.sect-card-img-overlay { position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.5) 0%, transparent 60%); }
.sect-card-img-badges { position: absolute; bottom: 8px; left: 10px; display: flex; gap: 5px; }
.sect-card-status-dot { position: absolute; top: 10px; right: 10px; width: 8px; height: 8px; border-radius: 50%; }
.sect-card-status-dot.published { background: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.3); }
.sect-card-status-dot.draft     { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.3); }
.sect-card-status-dot.archived  { background: #d1d5db; }
.sect-card-body { padding: 14px 16px; flex: 1; }
.sect-card-title { font-size: .92rem; font-weight: 700; color: var(--text,#111827); text-decoration: none; display: block; }
.sect-card-title:hover { color: #6366f1; }
.sect-card-ville { font-size: .72rem; color: var(--text-3,#9ca3af); margin-top: 3px; display: flex; align-items: center; gap: 4px; }
.sect-card-prix { margin-top: 8px; font-size: .8rem; font-weight: 700; color: #6366f1; }
.sect-card-evo { font-size: .65rem; color: #10b981; font-weight: 600; }
.sect-card-footer { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-top: 1px solid var(--border,#f3f4f6); }

/* ─── Masquage vues ─── */
.sect-list-view .sect-grid-wrap { display: none !important; }
.sect-grid-view .sect-list-wrap { display: none !important; }

/* ─── Pagination ─── */
.sect-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-top: 1px solid var(--border,#e5e7eb); font-size: .78rem; color: var(--text-3,#9ca3af); }
.sect-pagination a { padding: 6px 12px; border: 1px solid var(--border,#e5e7eb); border-radius: 10px; color: var(--text-2,#6b7280); text-decoration: none; font-weight: 600; transition: all .15s; font-size: .78rem; }
.sect-pagination a:hover { border-color: #6366f1; color: #6366f1; }
.sect-pagination a.active { background: #6366f1; color: #fff; border-color: #6366f1; }

/* ─── Flash / Empty ─── */
.sect-flash { padding: 12px 18px; border-radius: 10px; font-size: .85rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; animation: sectFlashIn .3s; }
.sect-flash.success { background: #d1fae5; color: #059669; border: 1px solid rgba(5,150,105,.12); }
.sect-flash.error   { background: #fef2f2; color: #dc2626; border: 1px solid rgba(220,38,38,.12); }
@keyframes sectFlashIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }
.sect-empty { text-align: center; padding: 60px 20px; color: var(--text-3,#9ca3af); }
.sect-empty i { font-size: 2.5rem; opacity: .2; margin-bottom: 12px; display: block; }
.sect-empty h3 { color: var(--text-2,#6b7280); font-size: 1rem; font-weight: 600; margin-bottom: 6px; }
.sect-empty a { color: #6366f1; }

@media (max-width: 1200px) { .sect-table .col-hide { display: none; } }
@media (max-width: 960px) {
    .sect-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .sect-toolbar { flex-direction: column; align-items: flex-start; }
    .sect-table-wrap { overflow-x: auto; }
    .sect-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
}
</style>

<div class="sect-wrap" id="sectWrap">

<?php if ($flash === 'deleted'): ?>
    <div class="sect-flash success"><i class="fas fa-check-circle"></i> Secteur supprimé avec succès</div>
<?php elseif ($flash === 'created'): ?>
    <div class="sect-flash success"><i class="fas fa-check-circle"></i> Secteur créé avec succès</div>
<?php elseif ($flash === 'updated'): ?>
    <div class="sect-flash success"><i class="fas fa-check-circle"></i> Secteur mis à jour</div>
<?php elseif ($flash === 'error'): ?>
    <div class="sect-flash error"><i class="fas fa-exclamation-circle"></i> Une erreur est survenue</div>
<?php endif; ?>

<?php if (!$tableExists): ?>
<div style="background:#fef2f2;border:1px solid rgba(220,38,38,.12);border-radius:12px;padding:28px;text-align:center;color:#dc2626">
    <i class="fas fa-database" style="font-size:2rem;margin-bottom:10px;display:block"></i>
    <h3 style="font-size:1rem;font-weight:700;margin-bottom:6px">Table secteurs introuvable</h3>
    <p style="font-size:.83rem;opacity:.75">Le dossier <code>cms/secteurs</code> existe mais la table <code>secteurs</code> est manquante.</p>
</div>
<?php else: ?>

<!-- ─── Banner ─── -->
<div class="sect-banner">
    <div class="sect-banner-left">
        <h2><i class="fas fa-map-marked-alt"></i> Quartiers & Secteurs</h2>
        <p>Quartiers et secteurs géographiques avec contenu local SEO</p>
    </div>
    <div class="sect-stats">
        <div class="sect-stat"><div class="num gray"><?= $stats['total'] ?></div><div class="lbl">Total</div></div>
        <div class="sect-stat"><div class="num green"><?= $stats['published'] ?></div><div class="lbl">Publiés</div></div>
        <div class="sect-stat"><div class="num amber"><?= $stats['draft'] ?></div><div class="lbl">Brouillons</div></div>
        <div class="sect-stat"><div class="num indigo"><?= $stats['quartier'] ?></div><div class="lbl">Quartiers</div></div>
        <?php if ($stats['commune'] > 0): ?>
        <div class="sect-stat"><div class="num teal"><?= $stats['commune'] ?></div><div class="lbl">Communes</div></div>
        <?php endif; ?>
        <?php if ($stats['avg_prix'] > 0): ?>
        <div class="sect-stat" title="Prix moyen €/m²">
            <div class="num indigo" style="font-size:1.1rem"><?= number_format($stats['avg_prix'], 0, ',', ' ') ?></div>
            <div class="lbl">Moy. €/m²</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ─── Toolbar ─── -->
<div class="sect-toolbar">
    <div class="sect-filters">
        <?php
        $filters = [
            'all'      => ['icon' => 'fa-layer-group', 'label' => 'Tous',        'count' => $stats['total']],
            'published'=> ['icon' => 'fa-check-circle','label' => 'Publiés',     'count' => $stats['published']],
            'draft'    => ['icon' => 'fa-pencil-alt',  'label' => 'Brouillons',  'count' => $stats['draft']],
            'archived' => ['icon' => 'fa-archive',     'label' => 'Archivés',    'count' => $stats['archived']],
        ];
        foreach ($filters as $key => $f):
            $active = ($filterStatus === $key) ? ' active' : '';
            $url = '?page=cms/secteurs' . ($key !== 'all' ? '&status='.$key : '');
            if ($searchQuery)           $url .= '&q='.urlencode($searchQuery);
            if ($filterVille !== 'all') $url .= '&ville='.urlencode($filterVille);
            if ($filterType !== 'all')  $url .= '&type='.urlencode($filterType);
        ?>
        <a href="<?= $url ?>" class="sect-fbtn<?= $active ?>">
            <i class="fas <?= $f['icon'] ?>"></i> <?= $f['label'] ?>
            <span class="badge"><?= (int)$f['count'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="sect-toolbar-r">
        <div class="sect-view-toggle">
            <button class="sect-view-btn active" id="sectBtnList" onclick="SECT.setView('list')" title="Vue liste"><i class="fas fa-list"></i></button>
            <button class="sect-view-btn"         id="sectBtnGrid" onclick="SECT.setView('grid')" title="Vue grille"><i class="fas fa-th-large"></i></button>
        </div>
        <form class="sect-search" method="GET">
            <input type="hidden" name="page" value="cms/secteurs">
            <?php if ($filterStatus !== 'all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
            <?php if ($filterVille !== 'all'):  ?><input type="hidden" name="ville"  value="<?= htmlspecialchars($filterVille) ?>"><?php endif; ?>
            <?php if ($filterType !== 'all'):   ?><input type="hidden" name="type"   value="<?= htmlspecialchars($filterType) ?>"><?php endif; ?>
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Nom, slug, code postal…" value="<?= htmlspecialchars($searchQuery) ?>">
        </form>
        <a href="?page=cms/secteurs&action=create" class="sect-btn sect-btn-primary"><i class="fas fa-plus"></i> Nouveau secteur</a>
    </div>
</div>

<!-- ─── Sub-filtres ─── -->
<?php if (!empty($villesList) || !empty($typesList)): ?>
<div class="sect-subfilters">
    <?php if (!empty($villesList) && $hasVille): ?>
    <div class="sect-subfilter">
        <i class="fas fa-city"></i>
        <select onchange="SECT.filterBy('ville', this.value)">
            <option value="all" <?= $filterVille==='all'?'selected':'' ?>>Toutes les villes</option>
            <?php foreach ($villesList as $ville): ?>
            <option value="<?= htmlspecialchars($ville) ?>" <?= $filterVille===$ville?'selected':'' ?>><?= htmlspecialchars($ville) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <?php if (!empty($typesList) && $colTypeSecteur): ?>
    <div class="sect-subfilter">
        <i class="fas fa-map"></i>
        <select onchange="SECT.filterBy('type', this.value)">
            <option value="all" <?= $filterType==='all'?'selected':'' ?>>Tous les types</option>
            <?php foreach ($typesList as $t):
                $ti = getSecteurTypeInfo($t);
            ?>
            <option value="<?= htmlspecialchars($t) ?>" <?= $filterType===$t?'selected':'' ?>><?= $ti['label'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ─── Bulk actions ─── -->
<div class="sect-bulk" id="sectBulkBar">
    <input type="checkbox" id="sectSelectAll" onchange="SECT.toggleAll(this.checked)">
    <span id="sectBulkCount">0</span> sélectionné(s)
    <select id="sectBulkAction">
        <option value="">— Action groupée —</option>
        <option value="published">Publier</option>
        <option value="draft">Brouillon</option>
        <option value="archived">Archiver</option>
        <option value="delete">Supprimer</option>
    </select>
    <button class="sect-btn sect-btn-sm sect-btn-outline" onclick="SECT.bulkExecute()"><i class="fas fa-check"></i> Appliquer</button>
</div>

<?php if (empty($secteurs)): ?>
<div class="sect-empty">
    <i class="fas fa-map-marked-alt"></i>
    <h3>Aucun secteur trouvé</h3>
    <p>
        <?php if ($searchQuery || $filterVille !== 'all' || $filterType !== 'all'): ?>
            Aucun résultat. <a href="?page=cms/secteurs">Effacer les filtres</a>
        <?php else: ?>
            Créez votre premier secteur géographique.
        <?php endif; ?>
    </p>
</div>
<?php else: ?>

<!-- ══ VUE LISTE ══════════════════════════════════════════════ -->
<div class="sect-list-wrap">
    <div class="sect-table-wrap">
        <table class="sect-table">
            <thead>
                <tr>
                    <th style="width:32px"><input type="checkbox" onchange="SECT.toggleAll(this.checked)"></th>
                    <th>Secteur</th>
                    <?php if ($colTypeSecteur): ?><th>Type</th><?php endif; ?>
                    <th>Statut</th>
                    <?php if ($hasPrixMin || $hasPrixMax): ?><th class="col-hide">Prix €/m²</th><?php endif; ?>
                    <?php if ($hasMetaTitle || $hasMetaDesc): ?><th class="col-hide center" title="SEO">SEO</th><?php endif; ?>
                    <?php if ($hasVille): ?><th class="col-hide">Ville</th><?php endif; ?>
                    <th>Date</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($secteurs as $s):
                $img       = getSecteurImage($s);
                $typeVal   = $s['col_type'] ?? '';
                $typeInfo  = $typeVal ? getSecteurTypeInfo($typeVal) : null;
                $status    = $s['status'] ?? 'draft';
                $statusLabels = ['published' => 'Publié', 'draft' => 'Brouillon', 'archived' => 'Archivé'];
                $prixMin   = (int)($s['prix_min'] ?? 0);
                $prixMax   = (int)($s['prix_max'] ?? 0);
                $prixLabel = formatPrix($prixMin, $prixMax);
                $evo       = $s['evolution_prix'] ?? '';
                $hasMT     = !empty($s['meta_title']);
                $hasMD     = !empty($s['meta_description']);
                $date      = !empty($s['created_at']) ? date('d/m/Y', strtotime($s['created_at'])) : '—';
                $editUrl   = "?page=cms/secteurs&action=edit&id={$s['id']}";
                $viewUrl   = '/' . ltrim($s['slug'] ?? '', '/');
                $nom       = $s['nom'] ?? 'Sans nom';
            ?>
            <tr data-id="<?= (int)$s['id'] ?>">
                <td><input type="checkbox" class="sect-cb" value="<?= (int)$s['id'] ?>" onchange="SECT.updateBulk()"></td>

                <!-- Nom + thumb + slug -->
                <td>
                    <div class="sect-nom-cell">
                        <?php if ($img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" class="sect-thumb" alt="<?= htmlspecialchars($nom) ?>" loading="lazy">
                        <?php else: ?>
                        <div class="sect-thumb-placeholder"><i class="fas fa-map"></i></div>
                        <?php endif; ?>
                        <div>
                            <a href="<?= htmlspecialchars($editUrl) ?>" class="sect-nom-link"><?= htmlspecialchars($nom) ?></a>
                            <div class="sect-slug">/<?= htmlspecialchars($s['slug'] ?? '') ?></div>
                        </div>
                    </div>
                </td>

                <!-- Type -->
                <?php if ($colTypeSecteur): ?>
                <td>
                    <?php if ($typeInfo): ?>
                    <span class="sect-type" style="background:<?= $typeInfo['bg'] ?>;color:<?= $typeInfo['color'] ?>">
                        <i class="fas <?= $typeInfo['icon'] ?>" style="font-size:.58rem"></i>
                        <?= $typeInfo['label'] ?>
                    </span>
                    <?php else: ?>
                    <span style="color:var(--text-3,#9ca3af);font-size:.75rem">—</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>

                <!-- Statut -->
                <td><span class="sect-status <?= $status ?>"><?= $statusLabels[$status] ?? ucfirst($status) ?></span></td>

                <!-- Prix -->
                <?php if ($hasPrixMin || $hasPrixMax): ?>
                <td class="col-hide">
                    <?php if ($prixMin > 0 || $prixMax > 0): ?>
                    <div class="sect-prix"><?= $prixLabel ?></div>
                    <?php if ($evo): ?><div class="sect-prix-evo"><i class="fas fa-arrow-up" style="font-size:.55rem"></i> <?= htmlspecialchars($evo) ?></div><?php endif; ?>
                    <?php else: ?>
                    <span style="color:var(--text-3,#9ca3af);font-size:.75rem">—</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>

                <!-- SEO meta -->
                <?php if ($hasMetaTitle || $hasMetaDesc): ?>
                <td class="col-hide center">
                    <div class="sect-meta-check" style="justify-content:center">
                        <?php if ($hasMetaTitle): ?>
                        <div style="display:flex;align-items:center;gap:3px" title="<?= $hasMT?'Meta title':'Meta title manquant' ?>">
                            <div class="sect-meta-dot <?= $hasMT?'ok':'bad' ?>"></div>
                            <span class="sect-meta-label">T</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($hasMetaDesc): ?>
                        <div style="display:flex;align-items:center;gap:3px" title="<?= $hasMD?'Meta desc.':'Meta desc. manquante' ?>">
                            <div class="sect-meta-dot <?= $hasMD?'ok':'bad' ?>"></div>
                            <span class="sect-meta-label">D</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </td>
                <?php endif; ?>

                <!-- Ville -->
                <?php if ($hasVille): ?>
                <td class="col-hide">
                    <span style="font-size:.78rem;color:var(--text-2,#6b7280)"><?= htmlspecialchars($s['ville'] ?? '—') ?></span>
                </td>
                <?php endif; ?>

                <!-- Date -->
                <td><span class="sect-date"><?= $date ?></span></td>

                <!-- Actions -->
                <td>
                    <div class="sect-actions">
                        <a href="<?= htmlspecialchars($editUrl) ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                        <button onclick="SECT.duplicate(<?= (int)$s['id'] ?>, '<?= addslashes(htmlspecialchars($nom)) ?>')" title="Dupliquer"><i class="fas fa-copy"></i></button>
                        <button onclick="SECT.toggleStatus(<?= (int)$s['id'] ?>, '<?= $status ?>')"
                                title="<?= $status==='published'?'Dépublier':'Publier' ?>">
                            <i class="fas <?= $status==='published'?'fa-eye-slash':'fa-eye' ?>"></i>
                        </button>
                        <?php if ($s['slug'] ?? ''): ?>
                        <a href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" title="Voir en ligne"><i class="fas fa-external-link-alt"></i></a>
                        <?php endif; ?>
                        <button class="del" onclick="SECT.deleteSecteur(<?= (int)$s['id'] ?>, '<?= addslashes(htmlspecialchars($nom)) ?>')" title="Supprimer"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="sect-pagination">
            <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?> secteurs</span>
            <div style="display:flex;gap:4px">
                <?php for ($i=1; $i<=$totalPages; $i++):
                    $pUrl = '?page=cms/secteurs&p='.$i;
                    if ($filterStatus!=='all') $pUrl .= '&status='.$filterStatus;
                    if ($filterVille!=='all')  $pUrl .= '&ville='.urlencode($filterVille);
                    if ($filterType!=='all')   $pUrl .= '&type='.urlencode($filterType);
                    if ($searchQuery)          $pUrl .= '&q='.urlencode($searchQuery);
                ?>
                <a href="<?= $pUrl ?>" class="<?= $i===$currentPage?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══ VUE GRILLE ══════════════════════════════════════════════ -->
<div class="sect-grid-wrap">
    <div class="sect-grid">
    <?php foreach ($secteurs as $s):
        $img      = getSecteurImage($s);
        $typeVal  = $s['col_type'] ?? '';
        $typeInfo = $typeVal ? getSecteurTypeInfo($typeVal) : null;
        $status   = $s['status'] ?? 'draft';
        $prixMin  = (int)($s['prix_min'] ?? 0);
        $prixMax  = (int)($s['prix_max'] ?? 0);
        $evo      = $s['evolution_prix'] ?? '';
        $date     = !empty($s['created_at']) ? date('d/m/Y', strtotime($s['created_at'])) : '—';
        $editUrl  = "?page=cms/secteurs&action=edit&id={$s['id']}";
        $viewUrl  = '/' . ltrim($s['slug'] ?? '', '/');
        $nom      = $s['nom'] ?? 'Sans nom';
        $statusLabels = ['published' => 'Publié', 'draft' => 'Brouillon', 'archived' => 'Archivé'];
    ?>
    <div class="sect-card" data-id="<?= (int)$s['id'] ?>">
        <div class="sect-card-img">
            <?php if ($img): ?>
            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($nom) ?>" loading="lazy">
            <div class="sect-card-img-overlay"></div>
            <?php else: ?>
            <div class="sect-card-img-placeholder"><i class="fas fa-map-marked-alt"></i></div>
            <?php endif; ?>
            <div class="sect-card-status-dot <?= $status ?>"></div>
            <div class="sect-card-img-badges">
                <?php if ($typeInfo): ?>
                <span class="sect-type" style="background:<?= $typeInfo['bg'] ?>;color:<?= $typeInfo['color'] ?>;font-size:.55rem;padding:2px 7px">
                    <?= $typeInfo['label'] ?>
                </span>
                <?php endif; ?>
                <span class="sect-status <?= $status ?>" style="font-size:.55rem;padding:2px 7px"><?= $statusLabels[$status] ?? $status ?></span>
            </div>
        </div>
        <div class="sect-card-body">
            <a href="<?= htmlspecialchars($editUrl) ?>" class="sect-card-title"><?= htmlspecialchars($nom) ?></a>
            <div class="sect-card-ville">
                <i class="fas fa-map-marker-alt" style="font-size:.6rem"></i>
                <?= htmlspecialchars($s['ville'] ?? '') ?>
                <?php if ($s['code_postal'] ?? ''): ?>
                <span style="opacity:.7">· <?= htmlspecialchars($s['code_postal']) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($prixMin > 0 || $prixMax > 0): ?>
            <div class="sect-card-prix"><?= formatPrix($prixMin, $prixMax) ?></div>
            <?php if ($evo): ?><div class="sect-card-evo">↑ <?= htmlspecialchars($evo) ?></div><?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="sect-card-footer">
            <span class="sect-date" style="font-size:.68rem"><?= $date ?></span>
            <div class="sect-actions" style="justify-content:flex-end">
                <a href="<?= htmlspecialchars($editUrl) ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                <button onclick="SECT.duplicate(<?= (int)$s['id'] ?>, '<?= addslashes(htmlspecialchars($nom)) ?>')" title="Dupliquer"><i class="fas fa-copy"></i></button>
                <button onclick="SECT.toggleStatus(<?= (int)$s['id'] ?>, '<?= $status ?>')" title="<?= $status==='published'?'Dépublier':'Publier' ?>">
                    <i class="fas <?= $status==='published'?'fa-eye-slash':'fa-eye' ?>"></i>
                </button>
                <?php if ($s['slug'] ?? ''): ?>
                <a href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" title="Voir"><i class="fas fa-external-link-alt"></i></a>
                <?php endif; ?>
                <button class="del" onclick="SECT.deleteSecteur(<?= (int)$s['id'] ?>, '<?= addslashes(htmlspecialchars($nom)) ?>')" title="Supprimer"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="sect-pagination" style="background:var(--surface,#fff);border-radius:12px;border:1px solid var(--border,#e5e7eb);margin-top:12px">
        <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?> secteurs</span>
        <div style="display:flex;gap:4px">
            <?php for ($i=1; $i<=$totalPages; $i++):
                $pUrl = '?page=cms/secteurs&p='.$i;
                if ($filterStatus!=='all') $pUrl .= '&status='.$filterStatus;
                if ($filterVille!=='all')  $pUrl .= '&ville='.urlencode($filterVille);
                if ($filterType!=='all')   $pUrl .= '&type='.urlencode($filterType);
                if ($searchQuery)          $pUrl .= '&q='.urlencode($searchQuery);
            ?>
            <a href="<?= $pUrl ?>" class="<?= $i===$currentPage?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php endif; ?>
</div><!-- /sect-wrap -->

<!-- ══ MODAL CUSTOM ══════════════════════════════════════════ -->
<div id="sectModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    <div onclick="SECT.modalClose()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px)"></div>
    <div id="sectModalBox" style="position:relative;z-index:1;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:420px;margin:16px;overflow:hidden;transform:scale(.94) translateY(8px);transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .15s;opacity:0;">
        <div id="sectModalHeader" style="padding:20px 22px 16px;display:flex;align-items:flex-start;gap:14px;">
            <div id="sectModalIcon" style="width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;"></div>
            <div style="flex:1;min-width:0;">
                <div id="sectModalTitle" style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:5px;"></div>
                <div id="sectModalMsg"   style="font-size:.82rem;color:#6b7280;line-height:1.5;"></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding:12px 20px 18px;border-top:1px solid #f3f4f6;">
            <button onclick="SECT.modalClose()" style="padding:9px 20px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:.83rem;font-weight:600;cursor:pointer;font-family:inherit;" onmouseover="this.style.borderColor='#6366f1';this.style.color='#6366f1'" onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#374151'">Annuler</button>
            <button id="sectModalConfirm" style="padding:9px 20px;border-radius:10px;border:none;font-size:.83rem;font-weight:700;cursor:pointer;font-family:inherit;color:#fff;"></button>
        </div>
    </div>
</div>

<script>
const SECT = {
    apiUrl: '/admin/modules/cms/secteurs/api.php',
    _modalCb: null,

    filterBy(key, value) {
        const url = new URL(window.location.href);
        value === 'all' ? url.searchParams.delete(key) : url.searchParams.set(key, value);
        url.searchParams.delete('p');
        window.location.href = url.toString();
    },

    setView(v) {
        const wrap = document.getElementById('sectWrap');
        wrap.classList.remove('sect-list-view', 'sect-grid-view');
        wrap.classList.add(v === 'grid' ? 'sect-grid-view' : 'sect-list-view');
        document.getElementById('sectBtnList').classList.toggle('active', v !== 'grid');
        document.getElementById('sectBtnGrid').classList.toggle('active', v === 'grid');
        try { sessionStorage.setItem('sect_view', v); } catch(e) {}
    },
    initView() {
        let v = 'list';
        try { v = sessionStorage.getItem('sect_view') || 'list'; } catch(e) {}
        this.setView(v);
    },

    toggleAll(checked) {
        document.querySelectorAll('.sect-cb').forEach(cb => cb.checked = checked);
        this.updateBulk();
    },
    updateBulk() {
        const checked = document.querySelectorAll('.sect-cb:checked');
        document.getElementById('sectBulkCount').textContent = checked.length;
        document.getElementById('sectBulkBar').classList.toggle('active', checked.length > 0);
    },
    async bulkExecute() {
        const action = document.getElementById('sectBulkAction').value;
        if (!action) return;
        const ids = [...document.querySelectorAll('.sect-cb:checked')].map(cb => parseInt(cb.value));
        if (!ids.length) return;
        if (action === 'delete') {
            this.modal({
                icon: '<i class="fas fa-trash"></i>', iconBg: '#fef2f2', iconColor: '#dc2626',
                title: `Supprimer ${ids.length} secteur(s) ?`,
                msg: 'Cette action est irréversible.',
                confirmLabel: 'Supprimer', confirmColor: '#dc2626',
                onConfirm: async () => {
                    const fd = new FormData();
                    fd.append('action','bulk_delete'); fd.append('ids', JSON.stringify(ids));
                    const r = await fetch(this.apiUrl, {method:'POST',body:fd});
                    const d = await r.json();
                    d.success ? location.reload() : this.toast(d.error||'Erreur','error');
                }
            });
            return;
        }
        const fd = new FormData();
        fd.append('action','bulk_status'); fd.append('status', action); fd.append('ids', JSON.stringify(ids));
        const r = await fetch(this.apiUrl, {method:'POST',body:fd});
        const d = await r.json();
        d.success ? location.reload() : this.toast(d.error||'Erreur','error');
    },

    modal({ icon, iconBg, iconColor, title, msg, confirmLabel, confirmColor, onConfirm }) {
        const el  = document.getElementById('sectModal');
        const box = document.getElementById('sectModalBox');
        document.getElementById('sectModalIcon').innerHTML       = icon;
        document.getElementById('sectModalIcon').style.background  = iconBg;
        document.getElementById('sectModalIcon').style.color       = iconColor;
        document.getElementById('sectModalHeader').style.background = iconBg + '33';
        document.getElementById('sectModalTitle').textContent    = title;
        document.getElementById('sectModalMsg').innerHTML        = msg;
        const btn = document.getElementById('sectModalConfirm');
        btn.textContent      = confirmLabel || 'Confirmer';
        btn.style.background = confirmColor || '#6366f1';
        btn.onmouseover = () => btn.style.filter = 'brightness(.88)';
        btn.onmouseout  = () => btn.style.filter = '';
        this._modalCb = onConfirm;
        btn.onclick = () => { this.modalClose(); if (this._modalCb) this._modalCb(); };
        el.style.display = 'flex';
        requestAnimationFrame(() => { box.style.opacity='1'; box.style.transform='scale(1) translateY(0)'; });
        document.addEventListener('keydown', this._escHandler);
    },
    modalClose() {
        const box = document.getElementById('sectModalBox');
        box.style.opacity='0'; box.style.transform='scale(.94) translateY(8px)';
        setTimeout(() => document.getElementById('sectModal').style.display='none', 160);
        document.removeEventListener('keydown', this._escHandler);
    },
    _escHandler(e) { if (e.key==='Escape') SECT.modalClose(); },

    toast(msg, type='success') {
        const colors = {success:'#059669',error:'#dc2626',info:'#3b82f6'};
        const icons  = {success:'✓',error:'✕',info:'ℹ'};
        const t = document.createElement('div');
        t.style.cssText='position:fixed;bottom:24px;right:24px;z-index:10000;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:10px;font-size:.83rem;font-weight:600;color:#111827;box-shadow:0 8px 24px rgba(0,0,0,.12);transform:translateY(20px);opacity:0;transition:all .25s;';
        t.innerHTML=`<span style="width:22px;height:22px;border-radius:50%;background:${colors[type]}22;color:${colors[type]};display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800">${icons[type]}</span>${msg}`;
        document.body.appendChild(t);
        requestAnimationFrame(()=>{t.style.opacity='1';t.style.transform='translateY(0)';});
        setTimeout(()=>{t.style.opacity='0';t.style.transform='translateY(10px)';setTimeout(()=>t.remove(),250);},3500);
    },

    deleteSecteur(id, nom) {
        this.modal({
            icon:'<i class="fas fa-trash"></i>', iconBg:'#fef2f2', iconColor:'#dc2626',
            title:'Supprimer ce secteur ?',
            msg:`<strong>${nom}</strong> sera supprimé définitivement.<br><span style="font-size:.78rem;color:#9ca3af">Cette action est irréversible.</span>`,
            confirmLabel:'Supprimer', confirmColor:'#dc2626',
            onConfirm: async () => {
                const fd = new FormData();
                fd.append('action','delete'); fd.append('id', id);
                try {
                    const r = await fetch(this.apiUrl, {method:'POST',body:fd});
                    const d = await r.json();
                    if (d.success) {
                        document.querySelectorAll(`[data-id="${id}"]`).forEach(el => {
                            el.style.cssText='opacity:0;transform:scale(.95);transition:all .3s';
                            setTimeout(()=>el.remove(),300);
                        });
                        this.toast('Secteur supprimé','success');
                    } else { this.toast(d.error||'Erreur','error'); }
                } catch(e) { this.toast('Erreur réseau','error'); }
            }
        });
    },

    async toggleStatus(id, current) {
        const newStatus = current === 'published' ? 'draft' : 'published';
        const fd = new FormData();
        fd.append('action','toggle_status'); fd.append('id', id); fd.append('status', newStatus);
        try {
            const r = await fetch(this.apiUrl, {method:'POST',body:fd});
            const d = await r.json();
            if (d.success) { this.toast(newStatus==='published'?'Secteur publié ✓':'Secteur dépublié','success'); setTimeout(()=>location.reload(),800); }
            else { this.toast(d.error||'Erreur','error'); }
        } catch(e) { this.toast('Erreur réseau','error'); }
    },

    duplicate(id, nom) {
        this.modal({
            icon:'<i class="fas fa-copy"></i>', iconBg:'#eff6ff', iconColor:'#3b82f6',
            title:'Dupliquer ce secteur ?',
            msg:`Une copie brouillon de <strong>${nom}</strong> sera créée.`,
            confirmLabel:'Dupliquer', confirmColor:'#3b82f6',
            onConfirm: async () => {
                const fd = new FormData();
                fd.append('action','duplicate'); fd.append('id', id);
                try {
                    const r = await fetch(this.apiUrl, {method:'POST',body:fd});
                    const d = await r.json();
                    if (d.success) { this.toast('Secteur dupliqué ✓','success'); setTimeout(()=>location.reload(),800); }
                    else { this.toast(d.error||'Erreur','error'); }
                } catch(e) { this.toast('Erreur réseau','error'); }
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => SECT.initView());
</script>