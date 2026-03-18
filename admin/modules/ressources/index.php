<?php
/**
 * /admin/modules/content/guides/index.php — RENOMMÉ ressources-index.php
 * Hub Ressources v2.0 — Aligné sur captures v2.0
 * Design identique, intégration BD complète, vue liste/grille
 */

if (!isset($pdo) && !defined('ADMIN_ROUTER')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}

// ─── Vérifier table ───
$tableExists = false;
$availCols   = [];
try {
    $pdo->query("SELECT 1 FROM ressources LIMIT 1");
    $tableExists = true;
    $availCols = $pdo->query("SHOW COLUMNS FROM ressources")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("[Ressources Index] Table check: " . $e->getMessage());
}

// ─── Colonnes optionnelles ───
$hasIcon       = in_array('icon', $availCols);
$hasTag        = in_array('tag', $availCols);
$hasPages      = in_array('pages', $availCols);
$hasFormat     = in_array('format', $availCols);
$hasPopular    = in_array('popular', $availCols);
$hasChapitres  = in_array('chapitres', $availCols);
$hasExtrait    = in_array('extrait', $availCols);
$hasCaptureId  = in_array('capture_id', $availCols);
$hasStatus     = in_array('status', $availCols);
$hasSortOrder  = in_array('sort_order', $availCols);
$hasType       = in_array('type', $availCols);
$hasPersona    = in_array('persona', $availCols);
$hasUpdatedAt  = in_array('updated_at', $availCols);

// ─── Stats globales ───
$stats = ['total' => 0, 'vendeur' => 0, 'acheteur' => 0, 'proprietaire' => 0, 'active' => 0, 'draft' => 0];
if ($tableExists) {
    try {
        $stats['total'] = (int)$pdo->query("SELECT COUNT(*) FROM ressources")->fetchColumn();
        if ($hasPersona) {
            $stats['vendeur']      = (int)$pdo->query("SELECT COUNT(*) FROM ressources WHERE persona='vendeur'")->fetchColumn();
            $stats['acheteur']     = (int)$pdo->query("SELECT COUNT(*) FROM ressources WHERE persona='acheteur'")->fetchColumn();
            $stats['proprietaire'] = (int)$pdo->query("SELECT COUNT(*) FROM ressources WHERE persona='proprietaire'")->fetchColumn();
        }
        if ($hasStatus) {
            $stats['active'] = (int)$pdo->query("SELECT COUNT(*) FROM ressources WHERE status='active'")->fetchColumn();
            $stats['draft']  = (int)$pdo->query("SELECT COUNT(*) FROM ressources WHERE status IN('draft','inactive')")->fetchColumn();
        }
    } catch (PDOException $e) {}
}

// ─── Types ───
$typesList = [];
if ($tableExists && $hasType) {
    try {
        $typesList = $pdo->query(
            "SELECT DISTINCT type FROM ressources WHERE type IS NOT NULL AND type != '' ORDER BY type"
        )->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

// ─── Filtres URL ───
$filterPersona = $_GET['persona'] ?? 'all';
$filterStatus  = $_GET['status']  ?? 'all';
$filterType    = $_GET['type']    ?? 'all';
$searchQuery   = trim($_GET['q']  ?? '');
$currentPage   = max(1, (int)($_GET['p'] ?? 1));
$perPage       = 25;
$offset        = ($currentPage - 1) * $perPage;

// ─── WHERE ───
$where = [];
$params = [];

if ($filterPersona !== 'all' && $hasPersona) {
    $where[] = "persona = ?";
    $params[] = $filterPersona;
}
if ($filterStatus !== 'all' && $hasStatus) {
    if ($filterStatus === 'active') {
        $where[] = "status = 'active'";
    } else {
        $where[] = "status IN('draft','inactive')";
    }
}
if ($filterType !== 'all' && $hasType) {
    $where[] = "type = ?";
    $params[] = $filterType;
}
if ($searchQuery !== '') {
    $where[] = "(name LIKE ? OR slug LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ─── Requête ───
$ressources = [];
$totalCount = 0;
$totalPages = 1;

if ($tableExists) {
    try {
        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM ressources {$whereSQL}");
        $stmtC->execute($params);
        $totalCount = (int)$stmtC->fetchColumn();
        $totalPages = max(1, ceil($totalCount / $perPage));

        $sel = "id, name, slug, persona, description";
        if ($hasIcon)      $sel .= ", icon";
        if ($hasTag)       $sel .= ", tag";
        if ($hasPages)     $sel .= ", pages";
        if ($hasFormat)    $sel .= ", format";
        if ($hasPopular)   $sel .= ", popular";
        if ($hasStatus)    $sel .= ", status";
        if ($hasSortOrder) $sel .= ", sort_order";
        if ($hasType)      $sel .= ", type";
        if ($hasExtrait)   $sel .= ", extrait";
        if ($hasUpdatedAt) $sel .= ", updated_at";
        if ($hasCaptureId) $sel .= ", capture_id";

        $stmt = $pdo->prepare(
            "SELECT {$sel} FROM ressources {$whereSQL} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);
        $ressources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("[Ressources Index] Query: " . $e->getMessage());
    }
}

// ─── Helpers ───
function resStatus(array $r): string {
    $s = $r['status'] ?? 'draft';
    return ($s === 'active') ? 'active' : 'draft';
}

function resStatusLabel(string $s): string {
    return ($s === 'active') ? 'Active' : 'Draft';
}

$personaLabels = [
    'vendeur'      => ['label' => '🏷️ Vendeurs',      'color' => '#d4a574'],
    'acheteur'     => ['label' => '🛒 Acheteurs',     'color' => '#1a4d7a'],
    'proprietaire' => ['label' => '🏠 Propriétaires', 'color' => '#059669'],
];

$flash = $_GET['msg'] ?? '';
?>

<style>
/* ══ RESSOURCES v2.0 — Aligné captures v2.0 ═════════════════ */
.res-wrap { font-family: var(--font, 'Inter', sans-serif); }

/* ─── Banner ─── */
.res-banner {
    background: var(--surface, #fff);
    border-radius: 16px;
    padding: 26px 30px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border: 1px solid var(--border, #e5e7eb);
    position: relative;
    overflow: hidden;
}
.res-banner::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #7c3aed, #a78bfa, #ddd6fe);
}
.res-banner::after {
    content: '';
    position: absolute;
    top: -40%;
    right: -5%;
    width: 220px;
    height: 220px;
    background: radial-gradient(circle, rgba(124, 58, 237, .05), transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.res-banner-left { position: relative; z-index: 1; }
.res-banner-left h2 { font-size: 1.35rem; font-weight: 700; color: var(--text, #111827); margin: 0 0 4px; display: flex; align-items: center; gap: 10px; letter-spacing: -.02em; }
.res-banner-left h2 i { font-size: 16px; color: #7c3aed; }
.res-banner-left p { color: var(--text-2, #6b7280); font-size: .85rem; margin: 0; }
.res-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.res-stat { text-align: center; padding: 10px 16px; background: var(--surface-2, #f9fafb); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); min-width: 72px; transition: all .2s; }
.res-stat:hover { border-color: var(--border-h, #d1d5db); box-shadow: 0 2px 8px rgba(0, 0, 0, .06); }
.res-stat .num { font-size: 1.45rem; font-weight: 800; line-height: 1; color: var(--text, #111827); letter-spacing: -.03em; }
.res-stat .num.purple { color: #7c3aed; }
.res-stat .num.amber  { color: #d4a574; }
.res-stat .num.blue   { color: #1a4d7a; }
.res-stat .num.green  { color: #059669; }
.res-stat .lbl { font-size: .58rem; color: var(--text-3, #9ca3af); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }

/* ─── Toolbar ─── */
.res-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.res-filters { display: flex; gap: 3px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; padding: 3px; flex-wrap: wrap; }
.res-fbtn { padding: 7px 15px; border: none; background: transparent; color: var(--text-2, #6b7280); font-size: .78rem; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all .15s; font-family: inherit; display: flex; align-items: center; gap: 5px; text-decoration: none; }
.res-fbtn:hover { color: var(--text, #111827); background: var(--surface-2, #f9fafb); }
.res-fbtn.active { background: #7c3aed; color: #fff; box-shadow: 0 1px 4px rgba(124, 58, 237, .25); }
.res-fbtn .badge { font-size: .68rem; padding: 1px 7px; border-radius: 10px; background: var(--surface-2, #f3f4f6); font-weight: 700; color: var(--text-3, #9ca3af); }
.res-fbtn.active .badge { background: rgba(255, 255, 255, .22); color: #fff; }

/* ─── Sub-filtres ─── */
.res-subfilters { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
.res-subfilter { display: flex; align-items: center; gap: 5px; font-size: .75rem; color: var(--text-2, #6b7280); }
.res-subfilter select { padding: 5px 10px; border: 1px solid var(--border, #e5e7eb); border-radius: 6px; background: var(--surface, #fff); color: var(--text, #111827); font-size: .75rem; font-family: inherit; cursor: pointer; }
.res-subfilter select:focus { outline: none; border-color: #7c3aed; }

/* ─── Toolbar right ─── */
.res-toolbar-r { display: flex; align-items: center; gap: 10px; }
.res-search { position: relative; }
.res-search input { padding: 8px 12px 8px 34px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; color: var(--text, #111827); font-size: .82rem; width: 220px; font-family: inherit; transition: all .2s; }
.res-search input:focus { outline: none; border-color: #7c3aed; box-shadow: 0 0 0 3px rgba(124, 58, 237, .1); width: 250px; }
.res-search input::placeholder { color: var(--text-3, #9ca3af); }
.res-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-3, #9ca3af); font-size: .75rem; }
.res-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 10px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; font-family: inherit; text-decoration: none; line-height: 1.3; }
.res-btn-primary { background: #7c3aed; color: #fff; box-shadow: 0 1px 4px rgba(124, 58, 237, .22); }
.res-btn-primary:hover { background: #6d28d9; transform: translateY(-1px); color: #fff; }
.res-btn-outline { background: var(--surface, #fff); color: var(--text-2, #6b7280); border: 1px solid var(--border, #e5e7eb); }
.res-btn-outline:hover { border-color: #7c3aed; color: #7c3aed; }

/* ─── Vue toggle ─── */
.res-view-toggle { display: flex; gap: 2px; background: var(--surface-2, #f9fafb); border: 1px solid var(--border, #e5e7eb); border-radius: 8px; padding: 3px; }
.res-view-btn { width: 30px; height: 28px; border: none; background: transparent; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-3, #9ca3af); transition: all .15s; font-size: .78rem; }
.res-view-btn:hover { color: var(--text, #111827); }
.res-view-btn.active { background: white; color: #7c3aed; box-shadow: 0 1px 3px rgba(0, 0, 0, .08); }

/* ─── Bulk ─── */
.res-bulk { display: none; align-items: center; gap: 12px; padding: 10px 16px; background: rgba(124, 58, 237, .06); border: 1px solid rgba(124, 58, 237, .15); border-radius: 10px; margin-bottom: 12px; font-size: .78rem; color: #7c3aed; font-weight: 600; }
.res-bulk.active { display: flex; }
.res-bulk select { padding: 5px 10px; border: 1px solid var(--border, #e5e7eb); border-radius: 6px; background: var(--surface, #fff); color: var(--text, #111827); font-size: .75rem; }

/* ─── Table ─── */
.res-table-wrap { background: var(--surface, #fff); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; }
.res-table { width: 100%; border-collapse: collapse; }
.res-table thead th { padding: 11px 14px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-3, #9ca3af); background: var(--surface-2, #f9fafb); border-bottom: 1px solid var(--border, #e5e7eb); text-align: left; white-space: nowrap; }
.res-table thead th.center { text-align: center; }
.res-table tbody tr { border-bottom: 1px solid var(--border, #f3f4f6); transition: background .1s; }
.res-table tbody tr:hover { background: rgba(124, 58, 237, .02); }
.res-table tbody tr:last-child { border-bottom: none; }
.res-table td { padding: 11px 14px; font-size: .83rem; color: var(--text, #111827); vertical-align: middle; }
.res-table td.center { text-align: center; }
.res-table input[type="checkbox"] { accent-color: #7c3aed; width: 14px; height: 14px; cursor: pointer; }

/* ─── Cellule titre ─── */
.res-title-cell a { font-weight: 600; color: var(--text, #111827); text-decoration: none; transition: color .15s; display: flex; align-items: center; gap: 8px; }
.res-title-cell a:hover { color: #7c3aed; }
.res-slug { font-family: monospace; font-size: .72rem; color: var(--text-3, #9ca3af); margin-top: 2px; }
.res-desc { font-size: .7rem; color: var(--text-3, #9ca3af); margin-top: 1px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.res-icon { font-size: 15px; width: 28px; height: 28px; border-radius: 7px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }

/* ─── Persona badge ─── */
.res-persona-badge { display: inline-block; font-size: .6rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; text-transform: capitalize; }

/* ─── Statut ─── */
.res-status { padding: 3px 10px; border-radius: 12px; font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; display: inline-block; }
.res-status.active { background: #d1fae5; color: #059669; }
.res-status.draft { background: #f3f4f6; color: #9ca3af; }

/* ─── Meta (pages, format) ─── */
.res-meta { font-size: .7rem; color: var(--text-3, #9ca3af); }

/* ─── Actions ─── */
.res-actions { display: flex; gap: 3px; justify-content: flex-end; }
.res-actions a, .res-actions button { width: 30px; height: 30px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--text-3, #9ca3af); background: transparent; border: 1px solid transparent; cursor: pointer; transition: all .12s; text-decoration: none; font-size: .78rem; }
.res-actions a:hover, .res-actions button:hover { color: #7c3aed; border-color: var(--border, #e5e7eb); background: rgba(124, 58, 237, .07); }
.res-actions button.del:hover { color: #dc2626; border-color: rgba(220, 38, 38, .2); background: #fef2f2; }

/* ══ VUE GRILLE ══════════════════════════════════════════════ */
.res-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
.res-card { background: var(--surface, #fff); border-radius: 14px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; transition: all .2s; display: flex; flex-direction: column; position: relative; }
.res-card:hover { border-color: #7c3aed; box-shadow: 0 4px 20px rgba(124, 58, 237, .1); transform: translateY(-2px); }
.res-card-top { padding: 16px 16px 12px; flex: 1; }
.res-card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
.res-card-icon { font-size: 24px; flex-shrink: 0; }
.res-card-title { font-size: .88rem; font-weight: 700; color: var(--text, #111827); text-decoration: none; display: block; line-height: 1.35; }
.res-card-title:hover { color: #7c3aed; }
.res-card-badges { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 5px; }
.res-card-badge { font-size: .6rem; font-weight: 700; padding: 2px 8px; border-radius: 8px; text-transform: uppercase; }
.res-card-slug { font-family: monospace; font-size: .65rem; color: var(--text-3, #9ca3af); margin-top: 5px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.res-card-desc { font-size: .72rem; color: var(--text-2, #6b7280); margin-top: 7px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.res-card-footer { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-top: 1px solid var(--border, #f3f4f6); background: var(--surface-2, #fafafa); }
.res-card-meta { font-size: .65rem; color: var(--text-3, #9ca3af); }
.res-card-status { font-size: .6rem; font-weight: 700; padding: 2px 8px; border-radius: 10px; }

/* ─── Pagination ─── */
.res-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-top: 1px solid var(--border, #e5e7eb); font-size: .78rem; color: var(--text-3, #9ca3af); }
.res-pagination a { padding: 6px 12px; border: 1px solid var(--border, #e5e7eb); border-radius: 10px; color: var(--text-2, #6b7280); text-decoration: none; font-weight: 600; transition: all .15s; font-size: .78rem; }
.res-pagination a:hover { border-color: #7c3aed; color: #7c3aed; }
.res-pagination a.active { background: #7c3aed; color: #fff; border-color: #7c3aed; }

/* ─── Flash / Empty ─── */
.res-flash { padding: 12px 18px; border-radius: 10px; font-size: .85rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; animation: resFlashIn .3s; }
.res-flash.success { background: #d1fae5; color: #059669; border: 1px solid rgba(5, 150, 105, .12); }
.res-flash.error { background: #fef2f2; color: #dc2626; border: 1px solid rgba(220, 38, 38, .12); }
@keyframes resFlashIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }
.res-empty { text-align: center; padding: 60px 20px; color: var(--text-3, #9ca3af); }
.res-empty i { font-size: 2.5rem; opacity: .2; margin-bottom: 12px; display: block; }
.res-empty h3 { color: var(--text-2, #6b7280); font-size: 1rem; font-weight: 600; margin-bottom: 6px; }

/* ─── Masquage vues ─── */
.res-list-view .res-grid-wrap { display: none !important; }
.res-grid-view .res-list-wrap { display: none !important; }

@media (max-width: 1200px) { .res-table .col-date-upd { display: none; } }
@media (max-width: 960px) {
    .res-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .res-toolbar { flex-direction: column; align-items: flex-start; }
    .res-table-wrap { overflow-x: auto; }
    .res-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
}
</style>

<div class="res-wrap" id="resWrap">

<?php if ($flash === 'deleted'): ?>
    <div class="res-flash success"><i class="fas fa-check-circle"></i> Ressource supprimée</div>
<?php elseif ($flash === 'created'): ?>
    <div class="res-flash success"><i class="fas fa-check-circle"></i> Ressource créée</div>
<?php elseif ($flash === 'updated'): ?>
    <div class="res-flash success"><i class="fas fa-check-circle"></i> Ressource mise à jour</div>
<?php endif; ?>

<?php if (!$tableExists): ?>
<div style="background:#fef2f2;border:1px solid rgba(220,38,38,.12);border-radius:12px;padding:28px;text-align:center;color:#dc2626">
    <i class="fas fa-database" style="font-size:2rem;margin-bottom:10px;display:block"></i>
    <h3>Table ressources introuvable</h3>
</div>
<?php else: ?>

<!-- ─── Banner ─── -->
<div class="res-banner">
    <div class="res-banner-left">
        <h2><i class="fas fa-book-bookmark"></i> Hub des Ressources</h2>
        <p>Guides PDF, articles, guides locaux — Documents téléchargeables par persona</p>
    </div>
    <div class="res-stats">
        <div class="res-stat"><div class="num purple"><?= $stats['total'] ?></div><div class="lbl">Total</div></div>
        <div class="res-stat"><div class="num amber"><?= $stats['vendeur'] ?></div><div class="lbl">Vendeurs</div></div>
        <div class="res-stat"><div class="num blue"><?= $stats['acheteur'] ?></div><div class="lbl">Acheteurs</div></div>
        <div class="res-stat"><div class="num green"><?= $stats['proprietaire'] ?></div><div class="lbl">Propriétaires</div></div>
        <div class="res-stat"><div class="num purple"><?= $stats['active'] ?></div><div class="lbl">Active</div></div>
    </div>
</div>

<!-- ─── Toolbar ─── -->
<div class="res-toolbar">
    <div class="res-filters">
        <?php
        $filters = [
            'all' => ['icon' => 'fa-layer-group', 'label' => 'Toutes', 'count' => $stats['total']],
            'active' => ['icon' => 'fa-check-circle', 'label' => 'Actives', 'count' => $stats['active']],
            'draft' => ['icon' => 'fa-file', 'label' => 'Brouillons', 'count' => $stats['draft']],
        ];
        foreach ($filters as $key => $f):
            $isA = ($filterStatus === $key);
            $url = '?page=ressources' . ($key !== 'all' ? '&status=' . $key : '');
            if ($filterPersona !== 'all') $url .= '&persona=' . $filterPersona;
            if ($filterType !== 'all') $url .= '&type=' . $filterType;
            if ($searchQuery) $url .= '&q=' . urlencode($searchQuery);
        ?>
        <a href="<?= $url ?>" class="res-fbtn<?= $isA ? ' active' : '' ?>">
            <i class="fas <?= $f['icon'] ?>"></i> <?= $f['label'] ?>
            <span class="badge"><?= (int)$f['count'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="res-toolbar-r">
        <!-- Toggle vue -->
        <div class="res-view-toggle">
            <button class="res-view-btn active" id="btnList" onclick="RES.setView('list')" title="Vue liste"><i class="fas fa-list"></i></button>
            <button class="res-view-btn" id="btnGrid" onclick="RES.setView('grid')" title="Vue grille"><i class="fas fa-th-large"></i></button>
        </div>
        <form class="res-search" method="GET">
            <input type="hidden" name="page" value="ressources">
            <?php if ($filterStatus !== 'all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
            <?php if ($filterPersona !== 'all'): ?><input type="hidden" name="persona" value="<?= htmlspecialchars($filterPersona) ?>"><?php endif; ?>
            <?php if ($filterType !== 'all'): ?><input type="hidden" name="type" value="<?= htmlspecialchars($filterType) ?>"><?php endif; ?>
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Titre, slug…" value="<?= htmlspecialchars($searchQuery) ?>">
        </form>
        <a href="?page=ressources&action=create" class="res-btn res-btn-primary"><i class="fas fa-plus"></i> Nouveau guide</a>
    </div>
</div>

<!-- ─── Sub-filtres ─── -->
<?php if ($hasPersona || !empty($typesList)): ?>
<div class="res-subfilters">
    <?php if ($hasPersona): ?>
    <div class="res-subfilter">
        <i class="fas fa-user-tag"></i>
        <select onchange="RES.filterBy('persona', this.value)">
            <option value="all" <?= $filterPersona === 'all' ? 'selected' : '' ?>>Tous les personas</option>
            <?php foreach ($personaLabels as $key => $pl): ?>
            <option value="<?= $key ?>" <?= $filterPersona === $key ? 'selected' : '' ?>><?= $pl['label'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <?php if (!empty($typesList)): ?>
    <div class="res-subfilter">
        <i class="fas fa-tag"></i>
        <select onchange="RES.filterBy('type', this.value)">
            <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Tous les types</option>
            <?php foreach ($typesList as $t): ?>
            <option value="<?= htmlspecialchars($t) ?>" <?= $filterType === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (empty($ressources)): ?>
<div class="res-empty">
    <i class="fas fa-book-bookmark"></i>
    <h3>Aucune ressource</h3>
    <p><?= ($searchQuery || $filterPersona !== 'all' || $filterType !== 'all') ? 'Aucun résultat. <a href="?page=ressources" style="color:#7c3aed">Effacer</a>' : 'Créez votre première ressource.' ?></p>
</div>
<?php else: ?>

<!-- ══ VUE LISTE ══════════════════════════════════════════════ -->
<div class="res-list-wrap">
    <div class="res-table-wrap">
        <table class="res-table">
            <thead>
                <tr>
                    <th>Ressource</th>
                    <th>Persona</th>
                    <th>Format</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Créée</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ressources as $res):
                $pIcon = $res['icon'] ?? '📄';
                $pColor = $personaLabels[$res['persona']]['color'] ?? '#94a3b8';
                $pLabel = $personaLabels[$res['persona']]['label'] ?? 'Autre';
                $status = resStatus($res);
                $dC = $res['created_at'] ? date('d/m/Y', strtotime($res['created_at'])) : '—';
                $eUrl = "?page=ressources&action=edit&id={$res['id']}";
            ?>
            <tr>
                <td class="res-title-cell">
                    <a href="<?= htmlspecialchars($eUrl) ?>">
                        <span class="res-icon" style="background:<?= $pColor ?>22;"><?= htmlspecialchars($pIcon) ?></span>
                        <div>
                            <div><?= htmlspecialchars($res['name']) ?></div>
                            <div class="res-slug">/<?= htmlspecialchars($res['slug']) ?></div>
                        </div>
                    </a>
                </td>

                <td>
                    <span class="res-persona-badge" style="background:<?= $pColor ?>22;color:<?= $pColor ?>;"><?= $pLabel ?></span>
                </td>

                <td class="res-meta">
                    <?php if ($hasFormat): ?>
                    <?= htmlspecialchars($res['format'] ?? 'PDF') ?>
                    <?php endif; ?>
                </td>

                <td class="res-meta">
                    <?php if ($hasType && $res['type']): ?>
                    <span style="display:inline-block;font-size:.6rem;font-weight:700;padding:2px 8px;border-radius:8px;background:#f0f0f0;color:#666;text-transform:uppercase;"><?= htmlspecialchars($res['type']) ?></span>
                    <?php endif; ?>
                </td>

                <td>
                    <span class="res-status <?= $status ?>"><?= resStatusLabel($status) ?></span>
                </td>

                <td class="res-meta"><?= $dC ?></td>

                <td>
                    <div class="res-actions">
                        <a href="<?= htmlspecialchars($eUrl) ?>" title="Éditer"><i class="fas fa-edit"></i></a>
                        <button onclick="RES.delete(<?= (int)$res['id'] ?>,'<?= addslashes(htmlspecialchars($res['name'])) ?>')" class="del" title="Supprimer"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="res-pagination">
            <span>Affichage <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalCount) ?> sur <?= $totalCount ?></span>
            <div style="display:flex;gap:4px">
                <?php for ($i = 1; $i <= $totalPages; $i++):
                    $pU = '?page=ressources&p=' . $i;
                    if ($filterStatus !== 'all') $pU .= '&status=' . $filterStatus;
                    if ($filterPersona !== 'all') $pU .= '&persona=' . $filterPersona;
                    if ($filterType !== 'all') $pU .= '&type=' . $filterType;
                    if ($searchQuery) $pU .= '&q=' . urlencode($searchQuery);
                ?>
                <a href="<?= $pU ?>" class="<?= $i === $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══ VUE GRILLE ══════════════════════════════════════════════ -->
<div class="res-grid-wrap">
    <div class="res-grid">
    <?php foreach ($ressources as $res):
        $pIcon = $res['icon'] ?? '📄';
        $pColor = $personaLabels[$res['persona']]['color'] ?? '#94a3b8';
        $pLabel = $personaLabels[$res['persona']]['label'] ?? 'Autre';
        $status = resStatus($res);
        $dC = $res['created_at'] ? date('d/m/Y', strtotime($res['created_at'])) : '—';
        $eUrl = "?page=ressources&action=edit&id={$res['id']}";
    ?>
    <div class="res-card">
        <div class="res-card-top">
            <div class="res-card-header">
                <div class="res-card-icon" style="background:<?= $pColor ?>22;border-radius:10px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;"><?= htmlspecialchars($pIcon) ?></div>
                <div style="flex:1;min-width:0">
                    <a href="<?= htmlspecialchars($eUrl) ?>" class="res-card-title"><?= htmlspecialchars($res['name']) ?></a>
                    <div class="res-card-badges">
                        <span class="res-persona-badge" style="background:<?= $pColor ?>22;color:<?= $pColor ?>;"><?= $pLabel ?></span>
                        <span class="res-card-status <?= $status ?>"><?= resStatusLabel($status) ?></span>
                    </div>
                </div>
            </div>
            <?php if ($res['description']): ?>
            <div class="res-card-desc"><?= htmlspecialchars($res['description']) ?></div>
            <?php endif; ?>
            <span class="res-card-slug">/<?= htmlspecialchars($res['slug']) ?></span>
        </div>
        <div class="res-card-footer">
            <div class="res-card-meta">
                <?php if ($hasFormat): ?><?= htmlspecialchars($res['format'] ?? 'PDF') ?><?php endif; ?>
                <?php if ($hasPages && $res['pages']): ?> • <?= htmlspecialchars($res['pages']) ?><?php endif; ?>
            </div>
            <div class="res-actions">
                <a href="<?= htmlspecialchars($eUrl) ?>" title="Éditer"><i class="fas fa-edit"></i></a>
                <button onclick="RES.delete(<?= (int)$res['id'] ?>,'<?= addslashes(htmlspecialchars($res['name'])) ?>')" class="del" title="Supprimer"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>
<?php endif; ?>
</div>

<script>
const RES = {
    apiUrl: '/admin/modules/content/guides/api.php',

    filterBy(key, value) {
        const url = new URL(window.location.href);
        value === 'all' ? url.searchParams.delete(key) : url.searchParams.set(key, value);
        url.searchParams.delete('p');
        window.location.href = url.toString();
    },

    setView(v) {
        const wrap = document.getElementById('resWrap');
        wrap.classList.remove('res-list-view', 'res-grid-view');
        wrap.classList.add(v === 'grid' ? 'res-grid-view' : 'res-list-view');
        document.getElementById('btnList').classList.toggle('active', v !== 'grid');
        document.getElementById('btnGrid').classList.toggle('active', v === 'grid');
        try { sessionStorage.setItem('res_view', v); } catch (e) { }
    },

    initView() {
        let v = 'list';
        try { v = sessionStorage.getItem('res_view') || 'list'; } catch (e) { }
        this.setView(v);
    },

    delete(id, title) {
        if (confirm(`Supprimer "${title}" ?\nCette action est irréversible.`)) {
            const fd = new FormData();
            fd.append('action', 'delete');
            fd.append('id', id);
            fetch(this.apiUrl, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        location.reload();
                    } else {
                        alert(d.error || 'Erreur');
                    }
                })
                .catch(e => alert('Erreur réseau: ' + e.message));
        }
    }
};

document.addEventListener('DOMContentLoaded', () => RES.initView());
</script>