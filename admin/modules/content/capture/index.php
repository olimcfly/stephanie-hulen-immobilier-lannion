<?php
/**
 * MODULE ADMIN — Pages de Capture  v2.0
 * /admin/modules/content/pages-capture/index.php
 * Refacto aligné sur articles v2.2 :
 *   - Bulk actions (sélection multiple)
 *   - Sub-filtres en selects (persona, type)
 *   - Score rings uniformisés (vues/leads/taux)
 *   - Modal custom conservé
 *   - Toggle vue liste/grille conservé
 */

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

$routeAction = $_GET['action'] ?? '';
if (in_array($routeAction, ['edit','create','delete'])) {
    $editFile = __DIR__ . '/edit.php';
    if ($routeAction === 'create') $editFile = __DIR__ . '/create.php';
    if (file_exists($editFile)) { require $editFile; return; }
}

// ─── Tables ───
$tableExists   = false;
$hasRessources = false;
$availCols     = [];
try { $pdo->query("SELECT 1 FROM captures LIMIT 1"); $tableExists = true;
      $availCols = $pdo->query("SHOW COLUMNS FROM captures")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}
try { $pdo->query("SELECT 1 FROM ressources LIMIT 1"); $hasRessources = true; } catch (PDOException $e) {}

$hasVues      = in_array('vues',               $availCols);
$hasConv      = in_array('conversions',        $availCols);
$hasTaux      = in_array('taux_conversion',    $availCols);
$hasStatus    = in_array('status',             $availCols);
$hasActive    = in_array('active',             $availCols);
$hasUpdatedAt = in_array('updated_at',         $availCols);
$hasLastConv  = in_array('last_conversion_at', $availCols);
$hasTitre     = in_array('titre',              $availCols);
$hasTitle     = in_array('title',              $availCols);
$hasGuideIds  = in_array('guide_ids',          $availCols);
$hasType      = in_array('type',               $availCols);
$colTitle     = $hasTitre ? 'titre' : ($hasTitle ? 'title' : 'titre');

// ─── Stats globales ───
$stats = ['total'=>0,'active'=>0,'draft'=>0,'total_vues'=>0,'total_leads'=>0];
if ($tableExists) {
    try {
        $stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM captures")->fetchColumn();
        if ($hasStatus) {
            $stats['active'] = (int)$pdo->query("SELECT COUNT(*) FROM captures WHERE status='active'")->fetchColumn();
            $stats['draft']  = (int)$pdo->query("SELECT COUNT(*) FROM captures WHERE status IN('inactive','archived')")->fetchColumn();
        } elseif ($hasActive) {
            $stats['active'] = (int)$pdo->query("SELECT COUNT(*) FROM captures WHERE `active`=1")->fetchColumn();
            $stats['draft']  = (int)$pdo->query("SELECT COUNT(*) FROM captures WHERE `active`=0")->fetchColumn();
        }
        if ($hasVues) $stats['total_vues']  = (int)$pdo->query("SELECT COALESCE(SUM(vues),0) FROM captures")->fetchColumn();
        if ($hasConv) $stats['total_leads'] = (int)$pdo->query("SELECT COALESCE(SUM(conversions),0) FROM captures")->fetchColumn();
    } catch (PDOException $e) {}
}
$hasLeadsTable = false;
try {
    $pdo->query("SELECT 1 FROM leads_captures LIMIT 1"); $hasLeadsTable = true;
    if ($stats['total_leads'] === 0)
        $stats['total_leads'] = (int)$pdo->query("SELECT COUNT(*) FROM leads_captures")->fetchColumn();
} catch (PDOException $e) {}

// ─── Listes pour sub-filtres ───
$typesList = [];
if ($tableExists && $hasType) {
    try {
        $typesList = $pdo->query(
            "SELECT DISTINCT type FROM captures WHERE type IS NOT NULL AND type != '' ORDER BY type"
        )->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

// ─── Filtres URL ───
$filterStatus  = $_GET['status']  ?? 'all';
$filterPersona = $_GET['persona'] ?? 'all';
$filterType    = $_GET['type']    ?? 'all';
$searchQuery   = trim($_GET['q']  ?? '');
$currentPage   = max(1, (int)($_GET['p'] ?? 1));
$perPage       = 25;
$offset        = ($currentPage - 1) * $perPage;

// ─── WHERE ───
$where = []; $params = [];
if ($filterStatus !== 'all') {
    if ($filterStatus === 'active')
        $where[] = $hasStatus ? "c.status='active'" : "c.`active`=1";
    elseif ($filterStatus === 'draft')
        $where[] = $hasStatus ? "c.status IN('inactive','archived')" : "c.`active`=0";
}
if ($filterPersona !== 'all' && $hasRessources) { $where[] = "r.persona = ?"; $params[] = $filterPersona; }
if ($filterType    !== 'all' && $hasType)        { $where[] = "c.type = ?";    $params[] = $filterType; }
if ($searchQuery !== '') {
    $where[] = "(c.`{$colTitle}` LIKE ? OR c.slug LIKE ?)";
    $params[] = "%{$searchQuery}%"; $params[] = "%{$searchQuery}%";
}
$whereSQL = $where ? 'WHERE '.implode(' AND ',$where) : '';

// ─── Requête ───
$captures = []; $totalCount = 0; $totalPages = 1;
if ($tableExists) {
    try {
        $joinRes  = $hasRessources ? "LEFT JOIN ressources r ON r.capture_id = c.id" : "";
        $selRes   = $hasRessources ? ", r.id AS res_id, r.name AS res_name, r.icon AS res_icon, r.persona AS res_persona" : "";
        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM captures c {$joinRes} {$whereSQL}");
        $stmtC->execute($params);
        $totalCount = (int)$stmtC->fetchColumn();
        $totalPages = max(1, ceil($totalCount / $perPage));

        $sel = "c.id, c.`{$colTitle}` AS display_titre, c.slug, c.created_at";
        if ($hasUpdatedAt)                       $sel .= ", c.updated_at";
        if ($hasStatus)                          $sel .= ", c.status";
        if ($hasActive)                          $sel .= ", c.`active`";
        if ($hasVues)                            $sel .= ", c.vues";
        if ($hasConv)                            $sel .= ", c.conversions";
        if ($hasTaux)                            $sel .= ", c.taux_conversion";
        if ($hasLastConv)                        $sel .= ", c.last_conversion_at";
        if ($hasGuideIds)                        $sel .= ", c.guide_ids";
        if (in_array('description',$availCols))  $sel .= ", c.description";
        if ($hasType)                            $sel .= ", c.type";
        if (in_array('headline',$availCols))     $sel .= ", c.headline";
        $sel .= $selRes;
        $leadsJoin = '';
        if ($hasLeadsTable) { $sel .= ", COUNT(lc.id) AS nb_leads"; $leadsJoin = "LEFT JOIN leads_captures lc ON lc.capture_id = c.id"; }

        $stmt = $pdo->prepare("SELECT {$sel} FROM captures c {$joinRes} {$leadsJoin} {$whereSQL} GROUP BY c.id ORDER BY c.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        $captures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { error_log("[Captures Index] ".$e->getMessage()); }
}

// ─── Helpers ───
function capStatus(array $c): string {
    $s = $c['status'] ?? '';
    if ($s === 'active') return 'active';
    if (in_array($s,['inactive','archived'])) return 'inactive';
    if (isset($c['active'])) return $c['active'] ? 'active' : 'inactive';
    return 'inactive';
}
function capStatusLabel(string $s): string { return $s === 'active' ? 'Active' : 'Inactive'; }
function tauxClass(float $t): string {
    if ($t >= 30) return 'excellent';
    if ($t >= 15) return 'good';
    if ($t >= 5)  return 'ok';
    if ($t > 0)   return 'bad';
    return 'none';
}

$personaLabels = [
    'vendeur'      => ['label'=>'🏷️ Vendeurs',      'color'=>'#d4a574'],
    'acheteur'     => ['label'=>'🛒 Acheteurs',     'color'=>'#1a4d7a'],
    'proprietaire' => ['label'=>'🏠 Propriétaires', 'color'=>'#059669'],
];
$flash = $_GET['msg'] ?? '';

// ─── CSRF ───
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<style>
/* ══ CAPTURES v2.0 — Aligné articles v2.2 ══════════════════════ */
.cap-wrap { font-family:var(--font,'Inter',sans-serif); }

/* ─── Banner ─── */
.cap-banner {
    background:var(--surface,#fff); border-radius:16px; padding:26px 30px;
    margin-bottom:22px; display:flex; align-items:center; justify-content:space-between;
    border:1px solid var(--border,#e5e7eb); position:relative; overflow:hidden;
}
.cap-banner::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,#d97706,#f59e0b,#fbbf24);
}
.cap-banner::after {
    content:''; position:absolute; top:-40%; right:-5%;
    width:220px; height:220px;
    background:radial-gradient(circle,rgba(217,119,6,.05),transparent 70%);
    border-radius:50%; pointer-events:none;
}
.cap-banner-left { position:relative; z-index:1; }
.cap-banner-left h2 { font-size:1.35rem; font-weight:700; color:var(--text,#111827); margin:0 0 4px; display:flex; align-items:center; gap:10px; letter-spacing:-.02em; }
.cap-banner-left h2 i { font-size:16px; color:#d97706; }
.cap-banner-left p { color:var(--text-2,#6b7280); font-size:.85rem; margin:0; }
.cap-stats { display:flex; gap:8px; position:relative; z-index:1; flex-wrap:wrap; }
.cap-stat { text-align:center; padding:10px 16px; background:var(--surface-2,#f9fafb); border-radius:12px; border:1px solid var(--border,#e5e7eb); min-width:72px; transition:all .2s; }
.cap-stat:hover { border-color:var(--border-h,#d1d5db); box-shadow:0 2px 8px rgba(0,0,0,.06); }
.cap-stat .num { font-size:1.45rem; font-weight:800; line-height:1; color:var(--text,#111827); letter-spacing:-.03em; }
.cap-stat .num.amber  { color:#d97706; }
.cap-stat .num.green  { color:#10b981; }
.cap-stat .num.blue   { color:#3b82f6; }
.cap-stat .num.violet { color:#7c3aed; }
.cap-stat .num.teal   { color:#0d9488; }
.cap-stat .lbl { font-size:.58rem; color:var(--text-3,#9ca3af); text-transform:uppercase; letter-spacing:.06em; font-weight:600; margin-top:3px; }

/* ─── Toolbar ─── */
.cap-toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px; }
.cap-filters { display:flex; gap:3px; background:var(--surface,#fff); border:1px solid var(--border,#e5e7eb); border-radius:10px; padding:3px; flex-wrap:wrap; }
.cap-fbtn { padding:7px 15px; border:none; background:transparent; color:var(--text-2,#6b7280); font-size:.78rem; font-weight:600; border-radius:6px; cursor:pointer; transition:all .15s; font-family:inherit; display:flex; align-items:center; gap:5px; text-decoration:none; }
.cap-fbtn:hover { color:var(--text,#111827); background:var(--surface-2,#f9fafb); }
.cap-fbtn.active { background:#d97706; color:#fff; box-shadow:0 1px 4px rgba(217,119,6,.25); }
.cap-fbtn .badge { font-size:.68rem; padding:1px 7px; border-radius:10px; background:var(--surface-2,#f3f4f6); font-weight:700; color:var(--text-3,#9ca3af); }
.cap-fbtn.active .badge { background:rgba(255,255,255,.22); color:#fff; }

/* ─── Sub-filtres ─── */
.cap-subfilters { display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap; }
.cap-subfilter { display:flex; align-items:center; gap:5px; font-size:.75rem; color:var(--text-2,#6b7280); }
.cap-subfilter select { padding:5px 10px; border:1px solid var(--border,#e5e7eb); border-radius:6px; background:var(--surface,#fff); color:var(--text,#111827); font-size:.75rem; font-family:inherit; cursor:pointer; }
.cap-subfilter select:focus { outline:none; border-color:#d97706; }

/* ─── Toolbar right ─── */
.cap-toolbar-r { display:flex; align-items:center; gap:10px; }
.cap-search { position:relative; }
.cap-search input { padding:8px 12px 8px 34px; background:var(--surface,#fff); border:1px solid var(--border,#e5e7eb); border-radius:10px; color:var(--text,#111827); font-size:.82rem; width:220px; font-family:inherit; transition:all .2s; }
.cap-search input:focus { outline:none; border-color:#d97706; box-shadow:0 0 0 3px rgba(217,119,6,.1); width:250px; }
.cap-search input::placeholder { color:var(--text-3,#9ca3af); }
.cap-search i { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--text-3,#9ca3af); font-size:.75rem; }
.cap-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:10px; font-size:.82rem; font-weight:600; cursor:pointer; border:none; transition:all .15s; font-family:inherit; text-decoration:none; line-height:1.3; }
.cap-btn-primary { background:#d97706; color:#fff; box-shadow:0 1px 4px rgba(217,119,6,.22); }
.cap-btn-primary:hover { background:#b45309; transform:translateY(-1px); color:#fff; }
.cap-btn-outline { background:var(--surface,#fff); color:var(--text-2,#6b7280); border:1px solid var(--border,#e5e7eb); }
.cap-btn-outline:hover { border-color:#d97706; color:#d97706; }
.cap-btn-sm { padding:5px 12px; font-size:.75rem; }

/* ─── Vue toggle ─── */
.cap-view-toggle { display:flex; gap:2px; background:var(--surface-2,#f9fafb); border:1px solid var(--border,#e5e7eb); border-radius:8px; padding:3px; }
.cap-view-btn { width:30px; height:28px; border:none; background:transparent; border-radius:6px; cursor:pointer; display:flex; align-items:center; justify-content:center; color:var(--text-3,#9ca3af); transition:all .15s; font-size:.78rem; }
.cap-view-btn:hover { color:var(--text,#111827); }
.cap-view-btn.active { background:white; color:#d97706; box-shadow:0 1px 3px rgba(0,0,0,.08); }

/* ─── Bulk ─── */
.cap-bulk { display:none; align-items:center; gap:12px; padding:10px 16px; background:rgba(217,119,6,.06); border:1px solid rgba(217,119,6,.15); border-radius:10px; margin-bottom:12px; font-size:.78rem; color:#d97706; font-weight:600; }
.cap-bulk.active { display:flex; }
.cap-bulk select { padding:5px 10px; border:1px solid var(--border,#e5e7eb); border-radius:6px; background:var(--surface,#fff); color:var(--text,#111827); font-size:.75rem; }

/* ─── Table ─── */
.cap-table-wrap { background:var(--surface,#fff); border-radius:12px; border:1px solid var(--border,#e5e7eb); overflow:hidden; }
.cap-table { width:100%; border-collapse:collapse; }
.cap-table thead th { padding:11px 14px; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-3,#9ca3af); background:var(--surface-2,#f9fafb); border-bottom:1px solid var(--border,#e5e7eb); text-align:left; white-space:nowrap; }
.cap-table thead th.center { text-align:center; }
.cap-table tbody tr { border-bottom:1px solid var(--border,#f3f4f6); transition:background .1s; }
.cap-table tbody tr:hover { background:rgba(217,119,6,.02); }
.cap-table tbody tr:last-child { border-bottom:none; }
.cap-table td { padding:11px 14px; font-size:.83rem; color:var(--text,#111827); vertical-align:middle; }
.cap-table td.center { text-align:center; }
.cap-table input[type="checkbox"] { accent-color:#d97706; width:14px; height:14px; cursor:pointer; }

/* ─── Cellule titre ─── */
.cap-title-cell a { font-weight:600; color:var(--text,#111827); text-decoration:none; transition:color .15s; display:flex; align-items:center; gap:8px; }
.cap-title-cell a:hover { color:#d97706; }
.cap-slug { font-family:monospace; font-size:.72rem; color:var(--text-3,#9ca3af); margin-top:2px; }
.cap-desc { font-size:.7rem; color:var(--text-3,#9ca3af); margin-top:1px; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cap-type-badge { display:inline-flex; align-items:center; gap:3px; font-size:.6rem; font-weight:700; padding:2px 7px; border-radius:8px; text-transform:uppercase; letter-spacing:.04em; }
.cap-type-badge.guide       { background:#fdf4ff; color:#7c3aed; }
.cap-type-badge.estimation  { background:#fff7ed; color:#d97706; }
.cap-type-badge.newsletter  { background:#f0fdf4; color:#059669; }
.cap-type-badge.contact     { background:#eff6ff; color:#3b82f6; }

/* ─── Ressource ─── */
.cap-res-cell { display:flex; align-items:center; gap:7px; }
.cap-res-icon { font-size:15px; width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cap-res-name { font-size:.73rem; font-weight:700; color:var(--text,#111827); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:130px; }
.cap-res-persona { font-size:.6rem; font-weight:700; padding:1px 6px; border-radius:20px; display:inline-block; margin-top:2px; }
.cap-res-none { font-size:.72rem; color:var(--text-3,#9ca3af); font-style:italic; }

/* ─── Statut ─── */
.cap-status { padding:3px 10px; border-radius:12px; font-size:.63rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; display:inline-block; }
.cap-status.active   { background:#d1fae5; color:#059669; }
.cap-status.inactive { background:#f3f4f6; color:#9ca3af; }

/* ─── Score rings (vues/leads/taux) ─── */
.cap-score-wrap { display:flex; flex-direction:column; align-items:center; gap:3px; min-width:54px; }
.cap-score-ring { width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.68rem; font-weight:800; border:3px solid transparent; transition:transform .2s; }
.cap-score-ring:hover { transform:scale(1.08); }
.cap-score-ring.excellent { background:#ecfdf5; border-color:#10b981; color:#059669; }
.cap-score-ring.good      { background:#eff6ff; border-color:#3b82f6; color:#2563eb; }
.cap-score-ring.ok        { background:#fefce8; border-color:#f59e0b; color:#d97706; }
.cap-score-ring.bad       { background:#fef2f2; border-color:#ef4444; color:#dc2626; }
.cap-score-ring.none      { background:var(--surface-2,#f9fafb); border-color:var(--border,#e5e7eb); border-style:dashed; color:var(--text-3,#9ca3af); }
.cap-score-bar  { width:38px; height:3px; background:var(--border,#e5e7eb); border-radius:2px; overflow:hidden; }
.cap-score-fill { height:100%; border-radius:2px; transition:width .5s cubic-bezier(.4,0,.2,1); }
.cap-score-fill.excellent { background:#10b981; }
.cap-score-fill.good      { background:#3b82f6; }
.cap-score-fill.ok        { background:#f59e0b; }
.cap-score-fill.bad       { background:#ef4444; }

/* Taux conv. inline (barre horizontale) */
.cap-taux-row { display:flex; align-items:center; gap:6px; }
.cap-taux-bar-h { width:44px; height:5px; background:var(--border,#e5e7eb); border-radius:3px; overflow:hidden; flex-shrink:0; }
.cap-taux-fill-h { height:100%; border-radius:3px; }
.cap-taux-fill-h.excellent { background:#10b981; }
.cap-taux-fill-h.good      { background:#3b82f6; }
.cap-taux-fill-h.ok        { background:#f59e0b; }
.cap-taux-fill-h.bad       { background:#ef4444; }
.cap-taux-fill-h.none      { background:var(--border,#e5e7eb); }
.cap-taux-val { font-size:.75rem; font-weight:700; min-width:32px; }
.cap-taux-val.excellent { color:#10b981; }
.cap-taux-val.good      { color:#3b82f6; }
.cap-taux-val.ok        { color:#d97706; }
.cap-taux-val.bad       { color:#dc2626; }
.cap-taux-val.none      { color:var(--text-3,#9ca3af); }

/* ─── Date ─── */
.cap-date { font-size:.73rem; color:var(--text-3,#9ca3af); white-space:nowrap; }

/* ─── Actions ─── */
.cap-actions { display:flex; gap:3px; justify-content:flex-end; }
.cap-actions a,.cap-actions button { width:30px; height:30px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:var(--text-3,#9ca3af); background:transparent; border:1px solid transparent; cursor:pointer; transition:all .12s; text-decoration:none; font-size:.78rem; }
.cap-actions a:hover,.cap-actions button:hover { color:#d97706; border-color:var(--border,#e5e7eb); background:rgba(217,119,6,.07); }
.cap-actions button.del:hover { color:#dc2626; border-color:rgba(220,38,38,.2); background:#fef2f2; }
.cap-actions a.res-link:hover { color:#7c3aed; border-color:rgba(124,58,237,.2); background:#faf5ff; }

/* ══ VUE GRILLE ══════════════════════════════════════════════ */
.cap-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; }
.cap-card { background:var(--surface,#fff); border-radius:14px; border:1px solid var(--border,#e5e7eb); overflow:hidden; transition:all .2s; display:flex; flex-direction:column; position:relative; }
.cap-card:hover { border-color:#d97706; box-shadow:0 4px 20px rgba(217,119,6,.1); transform:translateY(-2px); }
.cap-card-top { padding:16px 16px 12px; flex:1; }
.cap-card-header { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; margin-bottom:10px; }
.cap-card-title { font-size:.88rem; font-weight:700; color:var(--text,#111827); text-decoration:none; display:block; line-height:1.35; }
.cap-card-title:hover { color:#d97706; }
.cap-card-badges { display:flex; gap:4px; flex-wrap:wrap; align-items:center; margin-top:5px; }
.cap-card-slug { font-family:monospace; font-size:.65rem; color:var(--text-3,#9ca3af); margin-top:5px; display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cap-card-desc { font-size:.72rem; color:var(--text-2,#6b7280); margin-top:7px; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.cap-card-res { display:flex; align-items:center; gap:7px; padding:10px 16px; border-top:1px solid var(--border,#f3f4f6); background:var(--surface-2,#fafafa); }
.cap-card-res-icon { font-size:15px; width:26px; height:26px; border-radius:6px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cap-card-res-name { font-size:.72rem; font-weight:700; color:var(--text,#111827); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; flex:1; }
.cap-card-res-persona { font-size:.6rem; font-weight:700; padding:1px 6px; border-radius:10px; flex-shrink:0; }
.cap-card-stats { display:flex; gap:0; border-top:1px solid var(--border,#f3f4f6); }
.cap-card-stat { flex:1; text-align:center; padding:9px 6px; border-right:1px solid var(--border,#f3f4f6); }
.cap-card-stat:last-child { border-right:none; }
.cap-card-stat-val { font-size:.85rem; font-weight:800; color:var(--text,#111827); display:block; }
.cap-card-stat-val.amber { color:#d97706; }
.cap-card-stat-val.blue  { color:#3b82f6; }
.cap-card-stat-val.green { color:#10b981; }
.cap-card-stat-lbl { font-size:.55rem; color:var(--text-3,#9ca3af); text-transform:uppercase; letter-spacing:.05em; font-weight:600; }
.cap-card-footer { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; border-top:1px solid var(--border,#f3f4f6); background:var(--surface,#fff); }
.cap-card-footer .cap-actions { flex:1; justify-content:flex-start; }
.cap-card-status-dot { position:absolute; top:12px; right:12px; width:8px; height:8px; border-radius:50%; }
.cap-card-status-dot.active   { background:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.15); }
.cap-card-status-dot.inactive { background:#d1d5db; }

/* ─── Pagination ─── */
.cap-pagination { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-top:1px solid var(--border,#e5e7eb); font-size:.78rem; color:var(--text-3,#9ca3af); }
.cap-pagination a { padding:6px 12px; border:1px solid var(--border,#e5e7eb); border-radius:10px; color:var(--text-2,#6b7280); text-decoration:none; font-weight:600; transition:all .15s; font-size:.78rem; }
.cap-pagination a:hover { border-color:#d97706; color:#d97706; }
.cap-pagination a.active { background:#d97706; color:#fff; border-color:#d97706; }

/* ─── Flash / Empty ─── */
.cap-flash { padding:12px 18px; border-radius:10px; font-size:.85rem; font-weight:600; margin-bottom:16px; display:flex; align-items:center; gap:8px; animation:capFlashIn .3s; }
.cap-flash.success { background:#d1fae5; color:#059669; border:1px solid rgba(5,150,105,.12); }
.cap-flash.error   { background:#fef2f2; color:#dc2626; border:1px solid rgba(220,38,38,.12); }
@keyframes capFlashIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }
.cap-empty { text-align:center; padding:60px 20px; color:var(--text-3,#9ca3af); }
.cap-empty i { font-size:2.5rem; opacity:.2; margin-bottom:12px; display:block; }
.cap-empty h3 { color:var(--text-2,#6b7280); font-size:1rem; font-weight:600; margin-bottom:6px; }

/* ─── Masquage vues ─── */
.cap-list-view .cap-grid-wrap { display:none !important; }
.cap-grid-view .cap-list-wrap { display:none !important; }

@media(max-width:1200px){ .cap-table .col-date-upd { display:none; } }
@media(max-width:960px){
    .cap-banner { flex-direction:column; gap:16px; align-items:flex-start; }
    .cap-toolbar { flex-direction:column; align-items:flex-start; }
    .cap-table-wrap { overflow-x:auto; }
    .cap-grid { grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); }
}
</style>

<div class="cap-wrap" id="capWrap">

<?php if ($flash === 'deleted'): ?>
    <div class="cap-flash success"><i class="fas fa-check-circle"></i> Page de capture supprimée</div>
<?php elseif ($flash === 'created'): ?>
    <div class="cap-flash success"><i class="fas fa-check-circle"></i> Page de capture créée avec succès</div>
<?php elseif ($flash === 'updated'): ?>
    <div class="cap-flash success"><i class="fas fa-check-circle"></i> Page de capture mise à jour</div>
<?php elseif ($flash === 'error'): ?>
    <div class="cap-flash error"><i class="fas fa-exclamation-circle"></i> Une erreur est survenue</div>
<?php endif; ?>

<?php if (!$tableExists): ?>
<div style="background:#fef2f2;border:1px solid rgba(220,38,38,.12);border-radius:12px;padding:28px;text-align:center;color:#dc2626">
    <i class="fas fa-database" style="font-size:2rem;margin-bottom:10px;display:block"></i>
    <h3>Table captures introuvable</h3>
</div>
<?php else: ?>

<!-- ─── Banner ─── -->
<div class="cap-banner">
    <div class="cap-banner-left">
        <h2><i class="fas fa-bolt"></i> Pages de Capture</h2>
        <p>Guides PDF, lead magnets et formulaires<?= $hasRessources ? ' — liées au catalogue Ressources' : '' ?></p>
    </div>
    <div class="cap-stats">
        <div class="cap-stat"><div class="num amber"><?= $stats['total'] ?></div><div class="lbl">Total</div></div>
        <div class="cap-stat"><div class="num green"><?= $stats['active'] ?></div><div class="lbl">Actives</div></div>
        <div class="cap-stat"><div class="num amber"><?= $stats['draft'] ?></div><div class="lbl">Inactives</div></div>
        <div class="cap-stat"><div class="num blue"><?= number_format($stats['total_vues'],0,',',' ') ?></div><div class="lbl">Vues</div></div>
        <div class="cap-stat"><div class="num violet"><?= $stats['total_leads'] ?></div><div class="lbl">Leads</div></div>
        <?php if ($stats['total_vues'] > 0): ?>
        <div class="cap-stat"><div class="num teal"><?= round($stats['total_leads']/$stats['total_vues']*100,1) ?><span style="font-size:.6em;opacity:.6">%</span></div><div class="lbl">Conv. Moy.</div></div>
        <?php endif; ?>
    </div>
</div>

<!-- ─── Toolbar ─── -->
<div class="cap-toolbar">
    <div class="cap-filters">
        <?php
        $filters = [
            'all'    => ['icon'=>'fa-layer-group', 'label'=>'Toutes',    'count'=>$stats['total']],
            'active' => ['icon'=>'fa-check-circle','label'=>'Actives',   'count'=>$stats['active']],
            'draft'  => ['icon'=>'fa-eye-slash',   'label'=>'Inactives', 'count'=>$stats['draft']],
        ];
        foreach ($filters as $key => $f):
            $isA = ($filterStatus === $key);
            $url = '?page=captures'.($key!=='all'?'&status='.$key:'');
            if ($filterPersona!=='all') $url .= '&persona='.$filterPersona;
            if ($filterType!=='all')    $url .= '&type='.$filterType;
            if ($searchQuery)           $url .= '&q='.urlencode($searchQuery);
        ?>
        <a href="<?= $url ?>" class="cap-fbtn<?= $isA?' active':'' ?>">
            <i class="fas <?= $f['icon'] ?>"></i> <?= $f['label'] ?>
            <span class="badge"><?= (int)$f['count'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="cap-toolbar-r">
        <!-- Toggle vue liste/grille -->
        <div class="cap-view-toggle">
            <button class="cap-view-btn active" id="btnList" onclick="CAP.setView('list')" title="Vue liste"><i class="fas fa-list"></i></button>
            <button class="cap-view-btn"         id="btnGrid" onclick="CAP.setView('grid')" title="Vue grille"><i class="fas fa-th-large"></i></button>
        </div>
        <form class="cap-search" method="GET">
            <input type="hidden" name="page" value="captures">
            <?php if ($filterStatus!=='all'):  ?><input type="hidden" name="status"  value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
            <?php if ($filterPersona!=='all'): ?><input type="hidden" name="persona" value="<?= htmlspecialchars($filterPersona) ?>"><?php endif; ?>
            <?php if ($filterType!=='all'):    ?><input type="hidden" name="type"    value="<?= htmlspecialchars($filterType) ?>"><?php endif; ?>
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Titre, slug…" value="<?= htmlspecialchars($searchQuery) ?>">
        </form>
        <a href="?page=captures&action=create" class="cap-btn cap-btn-primary"><i class="fas fa-plus"></i> Nouvelle capture</a>
    </div>
</div>

<!-- ─── Sub-filtres ─── -->
<?php if ($hasRessources || !empty($typesList)): ?>
<div class="cap-subfilters">
    <?php if ($hasRessources): ?>
    <div class="cap-subfilter">
        <i class="fas fa-user-tag"></i>
        <select onchange="CAP.filterBy('persona', this.value)">
            <option value="all" <?= $filterPersona==='all'?'selected':'' ?>>Tous les personas</option>
            <?php foreach ($personaLabels as $key => $pl): ?>
            <option value="<?= $key ?>" <?= $filterPersona===$key?'selected':'' ?>><?= $pl['label'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <?php if (!empty($typesList)): ?>
    <div class="cap-subfilter">
        <i class="fas fa-tag"></i>
        <select onchange="CAP.filterBy('type', this.value)">
            <option value="all" <?= $filterType==='all'?'selected':'' ?>>Tous les types</option>
            <?php foreach ($typesList as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>" <?= $filterType===$t?'selected':'' ?>><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ─── Bulk actions ─── -->
<div class="cap-bulk" id="capBulkBar">
    <input type="checkbox" id="capSelectAll" onchange="CAP.toggleAll(this.checked)">
    <span id="capBulkCount">0</span> sélectionnée(s)
    <select id="capBulkAction">
        <option value="">— Action groupée —</option>
        <option value="activate">Activer</option>
        <option value="deactivate">Désactiver</option>
        <option value="delete">Supprimer</option>
    </select>
    <button class="cap-btn cap-btn-sm cap-btn-outline" onclick="CAP.bulkExecute()"><i class="fas fa-check"></i> Appliquer</button>
</div>

<?php if (empty($captures)): ?>
<div class="cap-empty">
    <i class="fas fa-bolt"></i>
    <h3>Aucune page de capture</h3>
    <p><?= ($searchQuery||$filterPersona!=='all'||$filterType!=='all') ? 'Aucun résultat. <a href="?page=captures" style="color:#d97706">Effacer</a>' : 'Créez votre première page de capture.' ?></p>
</div>
<?php else: ?>

<!-- ══ VUE LISTE ══════════════════════════════════════════════ -->
<div class="cap-list-wrap">
    <div class="cap-table-wrap">
        <table class="cap-table">
            <thead>
                <tr>
                    <th style="width:32px"><input type="checkbox" onchange="CAP.toggleAll(this.checked)"></th>
                    <th>Page de capture</th>
                    <th>Ressource liée</th>
                    <th>Statut</th>
                    <th class="center" title="Vues">Vues</th>
                    <th class="center" title="Leads captés">Leads</th>
                    <th title="Taux de conversion">Taux conv.</th>
                    <th>Créée le</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($captures as $cap):
                $sN    = capStatus($cap);
                $vues  = (int)($cap['vues']??0);
                $leads = (int)($cap['nb_leads']??$cap['conversions']??0);
                $taux  = $vues>0 ? round($leads/$vues*100,1) : (float)($cap['taux_conversion']??0);
                $tCls  = tauxClass($taux);
                $titre = $cap['display_titre']??'Sans titre';
                $slug  = $cap['slug']??'';
                $desc  = $cap['description']??'';
                $type  = $cap['type']??'contact';
                $dC    = !empty($cap['created_at']) ? date('d/m/Y',strtotime($cap['created_at'])) : '—';
                $eUrl  = "?page=captures&action=edit&id={$cap['id']}";
                $resId = $cap['res_id']??null; $resName=$cap['res_name']??null; $resIcon=$cap['res_icon']??'📄';
                $resP  = $cap['res_persona']??null; $pColor=$personaLabels[$resP]['color']??'#94a3b8'; $pLabel=$personaLabels[$resP]['label']??'';
                // Score rings
                $vuesMax = max(1, $stats['total_vues'] ?: 1000);
                $vuesCls = $vues>=500?'excellent':($vues>=200?'good':($vues>=50?'ok':($vues>0?'bad':'none')));
                $leadsCls = $leads>=50?'excellent':($leads>=20?'good':($leads>=5?'ok':($leads>0?'bad':'none')));
            ?>
            <tr data-id="<?= (int)$cap['id'] ?>">
                <td><input type="checkbox" class="cap-cb" value="<?= (int)$cap['id'] ?>" onchange="CAP.updateBulk()"></td>

                <!-- Titre -->
                <td class="cap-title-cell">
                    <a href="<?= htmlspecialchars($eUrl) ?>"><?= htmlspecialchars($titre) ?></a>
                    <?php if ($desc): ?><div class="cap-desc"><?= htmlspecialchars($desc) ?></div><?php endif; ?>
                    <div style="display:flex;align-items:center;gap:6px;margin-top:3px">
                        <span class="cap-slug">/capture/<?= htmlspecialchars($slug) ?></span>
                        <span class="cap-type-badge <?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></span>
                    </div>
                </td>

                <!-- Ressource -->
                <td>
                    <?php if ($resId && $resName): ?>
                    <div class="cap-res-cell">
                        <div class="cap-res-icon" style="background:<?= $pColor ?>22;"><?= htmlspecialchars($resIcon) ?></div>
                        <div>
                            <div class="cap-res-name"><?= htmlspecialchars($resName) ?></div>
                            <span class="cap-res-persona" style="background:<?= $pColor ?>22;color:<?= $pColor ?>;"><?= $pLabel ?></span>
                        </div>
                    </div>
                    <?php else: ?><span class="cap-res-none">—</span><?php endif; ?>
                </td>

                <!-- Statut -->
                <td><span class="cap-status <?= $sN ?>"><?= capStatusLabel($sN) ?></span></td>

                <!-- Vues (score ring) -->
                <td class="center">
                    <div class="cap-score-wrap">
                        <div class="cap-score-ring <?= $vuesCls ?>" title="<?= $vues ?> vues">
                            <?= $vues>0?($vues>=1000?round($vues/1000,1).'k':$vues):'—' ?>
                        </div>
                        <div class="cap-score-bar">
                            <div class="cap-score-fill <?= $vuesCls ?>" style="width:<?= min(100,round($vues/max(1,$vuesMax)*100)) ?>%"></div>
                        </div>
                    </div>
                </td>

                <!-- Leads (score ring) -->
                <td class="center">
                    <div class="cap-score-wrap">
                        <div class="cap-score-ring <?= $leadsCls ?>" title="<?= $leads ?> leads">
                            <?= $leads>0?$leads:'—' ?>
                        </div>
                        <div class="cap-score-bar">
                            <div class="cap-score-fill <?= $leadsCls ?>" style="width:<?= min(100,$leads>=50?100:$leads*2) ?>%"></div>
                        </div>
                    </div>
                </td>

                <!-- Taux conv. (inline bar) -->
                <td>
                    <div class="cap-taux-row">
                        <div class="cap-taux-bar-h">
                            <div class="cap-taux-fill-h <?= $tCls ?>" style="width:<?= min(100,$taux*2) ?>%"></div>
                        </div>
                        <span class="cap-taux-val <?= $tCls ?>"><?= $taux>0?$taux.'%':'—' ?></span>
                    </div>
                </td>

                <!-- Date -->
                <td><span class="cap-date"><?= $dC ?></span></td>

                <!-- Actions -->
                <td>
                    <div class="cap-actions">
                        <a href="<?= htmlspecialchars($eUrl) ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                        <button onclick="CAP.toggleStatus(<?= (int)$cap['id'] ?>,'<?= $sN ?>')" title="<?= $sN==='active'?'Désactiver':'Activer' ?>"><i class="fas <?= $sN==='active'?'fa-eye-slash':'fa-eye' ?>"></i></button>
                        <?php if ($slug): ?><a href="/capture/<?= htmlspecialchars($slug) ?>" target="_blank" title="Voir public"><i class="fas fa-external-link-alt"></i></a><?php endif; ?>
                        <?php if ($resId): ?><a href="?page=ressources&action=edit&id=<?= (int)$resId ?>" class="res-link" title="Ressource liée"><i class="fas fa-book"></i></a><?php endif; ?>
                        <button onclick="CAP.duplicate(<?= (int)$cap['id'] ?>)" title="Dupliquer"><i class="fas fa-copy"></i></button>
                        <button class="del" onclick="CAP.delete(<?= (int)$cap['id'] ?>,'<?= addslashes(htmlspecialchars($titre)) ?>')" title="Supprimer"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="cap-pagination">
            <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalCount) ?> sur <?= $totalCount ?></span>
            <div style="display:flex;gap:4px">
                <?php for ($i=1;$i<=$totalPages;$i++):
                    $pU='?page=captures&p='.$i;
                    if($filterStatus!=='all')  $pU.='&status='.$filterStatus;
                    if($filterPersona!=='all') $pU.='&persona='.$filterPersona;
                    if($filterType!=='all')    $pU.='&type='.$filterType;
                    if($searchQuery)           $pU.='&q='.urlencode($searchQuery);
                ?>
                <a href="<?= $pU ?>" class="<?= $i===$currentPage?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══ VUE GRILLE ══════════════════════════════════════════════ -->
<div class="cap-grid-wrap">
    <div class="cap-grid">
    <?php foreach ($captures as $cap):
        $sN    = capStatus($cap);
        $vues  = (int)($cap['vues']??0);
        $leads = (int)($cap['nb_leads']??$cap['conversions']??0);
        $taux  = $vues>0 ? round($leads/$vues*100,1) : (float)($cap['taux_conversion']??0);
        $tCls  = tauxClass($taux);
        $titre = $cap['display_titre']??'Sans titre';
        $slug  = $cap['slug']??'';
        $desc  = $cap['description']??'';
        $type  = $cap['type']??'contact';
        $head  = $cap['headline']??'';
        $dC    = !empty($cap['created_at']) ? date('d/m/Y',strtotime($cap['created_at'])) : '—';
        $eUrl  = "?page=captures&action=edit&id={$cap['id']}";
        $resId = $cap['res_id']??null; $resName=$cap['res_name']??null; $resIcon=$cap['res_icon']??'📄';
        $resP  = $cap['res_persona']??null; $pColor=$personaLabels[$resP]['color']??'#94a3b8'; $pLabel=$personaLabels[$resP]['label']??'';
    ?>
    <div class="cap-card" data-id="<?= (int)$cap['id'] ?>">
        <div class="cap-card-status-dot <?= $sN ?>" title="<?= capStatusLabel($sN) ?>"></div>
        <div class="cap-card-top">
            <div class="cap-card-header">
                <div style="flex:1;min-width:0;padding-right:14px">
                    <a href="<?= htmlspecialchars($eUrl) ?>" class="cap-card-title"><?= htmlspecialchars($titre) ?></a>
                    <div class="cap-card-badges">
                        <span class="cap-type-badge <?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></span>
                        <span class="cap-status <?= $sN ?>" style="font-size:.55rem;padding:2px 7px"><?= capStatusLabel($sN) ?></span>
                    </div>
                </div>
            </div>
            <?php if ($desc || $head): ?>
            <div class="cap-card-desc"><?= htmlspecialchars($desc ?: $head) ?></div>
            <?php endif; ?>
            <span class="cap-card-slug">/capture/<?= htmlspecialchars($slug) ?></span>
        </div>
        <?php if ($resId && $resName): ?>
        <div class="cap-card-res">
            <div class="cap-card-res-icon" style="background:<?= $pColor ?>22;"><?= htmlspecialchars($resIcon) ?></div>
            <span class="cap-card-res-name"><?= htmlspecialchars($resName) ?></span>
            <span class="cap-card-res-persona" style="background:<?= $pColor ?>22;color:<?= $pColor ?>;"><?= $pLabel ?></span>
        </div>
        <?php endif; ?>
        <div class="cap-card-stats">
            <div class="cap-card-stat">
                <span class="cap-card-stat-val blue"><?= $vues>0?number_format($vues,0,',',' '):'—' ?></span>
                <span class="cap-card-stat-lbl">Vues</span>
            </div>
            <div class="cap-card-stat">
                <span class="cap-card-stat-val green"><?= $leads>0?$leads:'—' ?></span>
                <span class="cap-card-stat-lbl">Leads</span>
            </div>
            <div class="cap-card-stat">
                <span class="cap-card-stat-val <?= $taux>0?($taux>=15?'green':'amber'):'none' ?>"><?= $taux>0?$taux.'%':'—' ?></span>
                <span class="cap-card-stat-lbl">Conv.</span>
            </div>
            <div class="cap-card-stat">
                <span class="cap-card-stat-val" style="font-size:.72rem;color:var(--text-3)"><?= $dC ?></span>
                <span class="cap-card-stat-lbl">Créée</span>
            </div>
        </div>
        <div class="cap-card-footer">
            <div class="cap-actions">
                <a href="<?= htmlspecialchars($eUrl) ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                <button onclick="CAP.toggleStatus(<?= (int)$cap['id'] ?>,'<?= $sN ?>')" title="<?= $sN==='active'?'Désactiver':'Activer' ?>"><i class="fas <?= $sN==='active'?'fa-eye-slash':'fa-eye' ?>"></i></button>
                <?php if ($slug): ?><a href="/capture/<?= htmlspecialchars($slug) ?>" target="_blank" title="Voir"><i class="fas fa-external-link-alt"></i></a><?php endif; ?>
                <?php if ($resId): ?><a href="?page=ressources&action=edit&id=<?= (int)$resId ?>" class="res-link" title="Ressource"><i class="fas fa-book"></i></a><?php endif; ?>
                <button onclick="CAP.duplicate(<?= (int)$cap['id'] ?>)" title="Dupliquer"><i class="fas fa-copy"></i></button>
                <button class="del" onclick="CAP.delete(<?= (int)$cap['id'] ?>,'<?= addslashes(htmlspecialchars($titre)) ?>')" title="Supprimer"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="cap-pagination" style="background:var(--surface,#fff);border-radius:12px;border:1px solid var(--border,#e5e7eb);margin-top:12px">
        <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalCount) ?> sur <?= $totalCount ?></span>
        <div style="display:flex;gap:4px">
            <?php for ($i=1;$i<=$totalPages;$i++):
                $pU='?page=captures&p='.$i;
                if($filterStatus!=='all')  $pU.='&status='.$filterStatus;
                if($filterPersona!=='all') $pU.='&persona='.$filterPersona;
                if($filterType!=='all')    $pU.='&type='.$filterType;
                if($searchQuery)           $pU.='&q='.urlencode($searchQuery);
            ?>
            <a href="<?= $pU ?>" class="<?= $i===$currentPage?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php endif; ?>
</div><!-- /cap-wrap -->

<!-- ══ MODAL CUSTOM ══════════════════════════════════════════ -->
<div id="capModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    <div id="capModalBd" onclick="CAP.modalClose()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px);"></div>
    <div id="capModalBox" style="position:relative;z-index:1;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:420px;margin:16px;overflow:hidden;transform:scale(.94) translateY(8px);transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .15s;opacity:0;">
        <div id="capModalHeader" style="padding:20px 22px 16px;display:flex;align-items:flex-start;gap:14px;">
            <div id="capModalIconWrap" style="width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;"></div>
            <div style="flex:1;min-width:0;">
                <div id="capModalTitle" style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:5px;"></div>
                <div id="capModalMsg" style="font-size:.82rem;color:#6b7280;line-height:1.5;"></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding:12px 20px 18px;border-top:1px solid #f3f4f6;">
            <button onclick="CAP.modalClose()" style="padding:9px 20px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:.83rem;font-weight:600;cursor:pointer;font-family:inherit;" onmouseover="this.style.borderColor='#d97706';this.style.color='#d97706'" onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#374151'">Annuler</button>
            <button id="capModalConfirm" style="padding:9px 20px;border-radius:10px;border:none;font-size:.83rem;font-weight:700;cursor:pointer;font-family:inherit;color:#fff;">Confirmer</button>
        </div>
    </div>
</div>

<script>
const CAP = {
    apiUrl: '/admin/modules/content/pages-capture/api.php',
    _modalCb: null,

    // ── filterBy (sub-filtres selects) ─────────────────────
    filterBy(key, value) {
        const url = new URL(window.location.href);
        value === 'all' ? url.searchParams.delete(key) : url.searchParams.set(key, value);
        url.searchParams.delete('p');
        window.location.href = url.toString();
    },

    // ── Bulk ───────────────────────────────────────────────
    toggleAll(checked) {
        document.querySelectorAll('.cap-cb').forEach(cb => cb.checked = checked);
        this.updateBulk();
    },
    updateBulk() {
        const checked = document.querySelectorAll('.cap-cb:checked');
        const bar = document.getElementById('capBulkBar');
        document.getElementById('capBulkCount').textContent = checked.length;
        bar.classList.toggle('active', checked.length > 0);
    },
    async bulkExecute() {
        const action = document.getElementById('capBulkAction').value;
        if (!action) return;
        const ids = [...document.querySelectorAll('.cap-cb:checked')].map(cb => parseInt(cb.value));
        if (!ids.length) return;
        if (action === 'delete') {
            this.modal({
                icon: '<i class="fas fa-trash"></i>', iconBg:'#fef2f2', iconColor:'#dc2626',
                title: `Supprimer ${ids.length} capture(s) ?`,
                msg: 'Cette action est irréversible.',
                confirmLabel: 'Supprimer', confirmColor:'#dc2626',
                onConfirm: async () => {
                    const fd = new FormData();
                    fd.append('action','bulk_delete'); fd.append('ids', JSON.stringify(ids));
                    const r = await fetch(this.apiUrl,{method:'POST',body:fd});
                    const d = await r.json();
                    d.success ? location.reload() : this.toast(d.error||'Erreur','error');
                }
            });
            return;
        }
        const fd = new FormData();
        fd.append('action','bulk_status');
        fd.append('status', action === 'activate' ? 'active' : 'inactive');
        fd.append('ids', JSON.stringify(ids));
        const r = await fetch(this.apiUrl,{method:'POST',body:fd});
        const d = await r.json();
        d.success ? location.reload() : this.toast(d.error||'Erreur','error');
    },

    // ── Modal ──────────────────────────────────────────────
    modal({ icon, iconBg, iconColor, title, msg, confirmLabel, confirmColor, onConfirm }) {
        const el  = document.getElementById('capModal');
        const box = document.getElementById('capModalBox');
        document.getElementById('capModalIconWrap').innerHTML  = icon;
        document.getElementById('capModalIconWrap').style.background  = iconBg;
        document.getElementById('capModalIconWrap').style.color       = iconColor;
        document.getElementById('capModalHeader').style.background    = iconBg + '33';
        document.getElementById('capModalTitle').textContent  = title;
        document.getElementById('capModalMsg').innerHTML      = msg;
        const btnOk = document.getElementById('capModalConfirm');
        btnOk.textContent      = confirmLabel || 'Confirmer';
        btnOk.style.background = confirmColor || '#d97706';
        btnOk.onmouseover = () => btnOk.style.filter = 'brightness(.88)';
        btnOk.onmouseout  = () => btnOk.style.filter = '';
        this._modalCb = onConfirm;
        btnOk.onclick = () => { this.modalClose(); if (this._modalCb) this._modalCb(); };
        el.style.display = 'flex';
        requestAnimationFrame(() => { box.style.opacity='1'; box.style.transform='scale(1) translateY(0)'; });
        document.addEventListener('keydown', this._escHandler);
    },
    modalClose() {
        const el  = document.getElementById('capModal');
        const box = document.getElementById('capModalBox');
        box.style.opacity='0'; box.style.transform='scale(.94) translateY(8px)';
        setTimeout(() => el.style.display='none', 160);
        document.removeEventListener('keydown', this._escHandler);
    },
    _escHandler(e) { if (e.key==='Escape') CAP.modalClose(); },

    // ── Toast ──────────────────────────────────────────────
    toast(msg, type = 'success') {
        const t = document.createElement('div');
        const colors = {success:'#059669',error:'#dc2626',info:'#3b82f6'};
        const icons  = {success:'✓',error:'✕',info:'ℹ'};
        t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:10000;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:10px;font-size:.83rem;font-weight:600;color:#111827;box-shadow:0 8px 24px rgba(0,0,0,.12);transform:translateY(20px);opacity:0;transition:all .25s;';
        t.innerHTML = `<span style="width:22px;height:22px;border-radius:50%;background:${colors[type]}22;color:${colors[type]};display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800">${icons[type]}</span>${msg}`;
        document.body.appendChild(t);
        requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; });
        setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(10px)'; setTimeout(()=>t.remove(),250); }, 3000);
    },

    // ── Vue liste / grille ─────────────────────────────────
    setView(v) {
        const wrap = document.getElementById('capWrap');
        wrap.classList.remove('cap-list-view','cap-grid-view');
        wrap.classList.add(v==='grid'?'cap-grid-view':'cap-list-view');
        document.getElementById('btnList').classList.toggle('active', v!=='grid');
        document.getElementById('btnGrid').classList.toggle('active', v==='grid');
        try { sessionStorage.setItem('cap_view', v); } catch(e) {}
    },
    initView() {
        let v = 'list';
        try { v = sessionStorage.getItem('cap_view')||'list'; } catch(e) {}
        this.setView(v);
    },

    // ── Delete ─────────────────────────────────────────────
    delete(id, title) {
        this.modal({
            icon:'<i class="fas fa-trash"></i>', iconBg:'#fef2f2', iconColor:'#dc2626',
            title:'Supprimer cette capture ?',
            msg:`La page <strong>${title}</strong> sera supprimée définitivement.<br><span style="font-size:.78rem;color:#9ca3af">Cette action est irréversible.</span>`,
            confirmLabel:'Supprimer', confirmColor:'#dc2626',
            onConfirm: async () => {
                const fd = new FormData();
                fd.append('action','delete'); fd.append('id',id);
                try {
                    const r = await fetch(this.apiUrl,{method:'POST',body:fd});
                    const d = await r.json();
                    if (d.success) {
                        document.querySelectorAll(`[data-id="${id}"]`).forEach(el => {
                            el.style.cssText='opacity:0;transform:scale(.95);transition:all .3s';
                            setTimeout(()=>el.remove(),300);
                        });
                        this.toast('Page supprimée','success');
                    } else { this.toast(d.error||'Erreur lors de la suppression','error'); }
                } catch(e) { this.toast('Erreur réseau : '+e.message,'error'); }
            }
        });
    },

    // ── Toggle status ──────────────────────────────────────
    async toggleStatus(id, current) {
        const newStatus = current==='active'?'inactive':'active';
        const fd = new FormData();
        fd.append('action','toggle_status'); fd.append('id',id); fd.append('status',newStatus);
        try {
            const r = await fetch(this.apiUrl,{method:'POST',body:fd});
            const d = await r.json();
            d.success ? location.reload() : this.toast(d.error||'Erreur','error');
        } catch(e) { this.toast('Erreur réseau','error'); }
    },

    // ── Duplicate ──────────────────────────────────────────
    duplicate(id) {
        this.modal({
            icon:'<i class="fas fa-copy"></i>', iconBg:'#eff6ff', iconColor:'#3b82f6',
            title:'Dupliquer cette capture ?',
            msg:'Une copie inactive sera créée. Vous pourrez la modifier avant de la publier.',
            confirmLabel:'Dupliquer', confirmColor:'#3b82f6',
            onConfirm: async () => {
                const fd = new FormData();
                fd.append('action','duplicate'); fd.append('id',id);
                try {
                    const r = await fetch(this.apiUrl,{method:'POST',body:fd});
                    const d = await r.json();
                    if (d.success) { this.toast('Page dupliquée','success'); setTimeout(()=>location.reload(),800); }
                    else { this.toast(d.error||'Erreur','error'); }
                } catch(e) { this.toast('Erreur réseau','error'); }
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => CAP.initView());
</script>