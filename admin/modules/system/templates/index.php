<?php
/**
 * /admin/modules/system/templates/index.php  v3.0
 * ════════════════════════════════════════════════════════════
 * Gestion des templates avec design articles v2.3
 * ✅ Vue liste pro avec colonnes complètes
 * ✅ Vue grille (toggle list/grid)
 * ✅ Filtres avancés (type, recherche)
 * ✅ Stats dashboard
 * ✅ Modales custom (suppression, duplication)
 * ✅ Toast notifications
 * ✅ Bulk actions
 * ✅ Onglet "Guide" séparé
 * ════════════════════════════════════════════════════════════
 */

if (!isset($pdo)) {
    require_once dirname(__DIR__, 4) . '/includes/init.php';
}

// ─── Routing ───
$action = $_GET['action'] ?? '';
$tab    = $_GET['tab'] ?? 'templates';

if ($action === 'edit' && isset($_GET['id'])) {
    if (file_exists(__DIR__ . '/edit.php')) {
        require_once __DIR__ . '/edit.php';
        return;
    }
}

if ($tab === 'guide') {
    // Afficher seulement le guide
    require_once __DIR__ . '/guide.php';
    return;
}

// ─── CSRF ───
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

// ─── Récupérer tous les templates ───
$allTemplates = [];
try {
    $allTemplates = $pdo->query("SELECT * FROM design_templates ORDER BY type ASC, sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[system/templates] ' . $e->getMessage());
}

// ─── Filtres URL ───
$filterType  = $_GET['type']  ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');
$currentPage = max(1, (int)($_GET['p'] ?? 1));
$perPage     = 25;
$offset      = ($currentPage - 1) * $perPage;

// ─── Appliquer filtres ───
$filtered = array_filter($allTemplates, function($t) use ($filterType, $searchQuery) {
    if ($filterType !== 'all' && $t['type'] !== $filterType) return false;
    if ($searchQuery !== '' && stripos($t['name'], $searchQuery) === false && stripos($t['slug'], $searchQuery) === false) return false;
    return true;
});

$totalFiltered = count($filtered);
$totalPages    = max(1, ceil($totalFiltered / $perPage));
$templates     = array_slice($filtered, $offset, $perPage);

// ─── Stats ───
$stats = ['total' => 0, 'pages' => 0, 'headers' => 0, 'footers' => 0];
$stats['total'] = count($allTemplates);
foreach ($allTemplates as $t) {
    $type = $t['type'] ?? 'page';
    if ($type === 'page')   $stats['pages']++;
    elseif ($type === 'header') $stats['headers']++;
    elseif ($type === 'footer') $stats['footers']++;
}

$flash = $_GET['msg'] ?? '';
?>

<style>
/* ══════════════════════════════════════════════════════════════
   SYSTEM TEMPLATES v3.0 — Design articles
══════════════════════════════════════════════════════════════ */
.stpl-wrap { font-family: 'DM Sans', sans-serif; }

/* ─── Onglets ─── */
.stpl-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--border, #e5e7eb); margin-bottom: 24px; }
.stpl-tab { padding: 12px 20px; font-size: .85rem; font-weight: 600; color: var(--text-2, #6b7280); cursor: pointer; border-bottom: 3px solid transparent; transition: all .2s; text-decoration: none; display: flex; align-items: center; gap: 6px; }
.stpl-tab:hover { color: var(--text, #111827); }
.stpl-tab.active { color: #f59e0b; border-bottom-color: #f59e0b; }

/* ─── Banner ─── */
.stpl-banner {
    background: var(--surface, #fff);
    border-radius: 16px; padding: 26px 30px; margin-bottom: 22px;
    display: flex; align-items: center; justify-content: space-between;
    border: 1px solid var(--border, #e5e7eb); position: relative; overflow: hidden;
}
.stpl-banner::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #8b5cf6, #6366f1, #0ea5e9); opacity: .75;
}
.stpl-banner::after {
    content: ''; position: absolute; top: -40%; right: -5%;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(139,92,246,.05), transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.stpl-banner-left { position: relative; z-index: 1; }
.stpl-banner-left h2 { font-size: 1.35rem; font-weight: 700; color: var(--text, #111827); margin: 0 0 4px; display: flex; align-items: center; gap: 10px; letter-spacing: -.02em; }
.stpl-banner-left h2 i { font-size: 16px; color: #8b5cf6; }
.stpl-banner-left p { color: var(--text-2, #6b7280); font-size: .85rem; margin: 0; }

.stpl-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.stpl-stat { text-align: center; padding: 10px 16px; background: var(--surface-2, #f9fafb); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); min-width: 72px; transition: all .2s; }
.stpl-stat:hover { border-color: var(--border-h, #d1d5db); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.stpl-stat .num { font-size: 1.45rem; font-weight: 800; line-height: 1; color: var(--text, #111827); letter-spacing: -.03em; }
.stpl-stat .num.violet { color: #7c3aed; }
.stpl-stat .num.blue   { color: #3b82f6; }
.stpl-stat .num.pink   { color: #db2777; }
.stpl-stat .lbl { font-size: .58rem; color: var(--text-3, #9ca3af); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }

/* ─── Toolbar ─── */
.stpl-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.stpl-filters { display: flex; gap: 3px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; padding: 3px; flex-wrap: wrap; }
.stpl-fbtn { padding: 7px 15px; border: none; background: transparent; color: var(--text-2, #6b7280); font-size: .78rem; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all .15s; font-family: inherit; display: flex; align-items: center; gap: 5px; text-decoration: none; }
.stpl-fbtn:hover { color: var(--text, #111827); background: var(--surface-2, #f9fafb); }
.stpl-fbtn.active { background: #8b5cf6; color: #fff; box-shadow: 0 1px 4px rgba(139,92,246,.25); }
.stpl-fbtn .badge { font-size: .68rem; padding: 1px 7px; border-radius: 10px; background: var(--surface-2, #f3f4f6); font-weight: 700; color: var(--text-3, #9ca3af); }
.stpl-fbtn.active .badge { background: rgba(255,255,255,.22); color: #fff; }

.stpl-toolbar-r { display: flex; align-items: center; gap: 10px; }
.stpl-view-toggle { display: flex; gap: 2px; background: var(--surface-2, #f9fafb); border: 1px solid var(--border, #e5e7eb); border-radius: 8px; padding: 3px; }
.stpl-view-btn { width: 30px; height: 28px; border: none; background: transparent; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-3, #9ca3af); transition: all .15s; font-size: .78rem; }
.stpl-view-btn:hover { color: var(--text, #111827); }
.stpl-view-btn.active { background: white; color: #8b5cf6; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.stpl-search { position: relative; }
.stpl-search input { padding: 8px 12px 8px 34px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; color: var(--text, #111827); font-size: .82rem; width: 220px; font-family: inherit; transition: all .2s; }
.stpl-search input:focus { outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,.1); width: 250px; }
.stpl-search input::placeholder { color: var(--text-3, #9ca3af); }
.stpl-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-3, #9ca3af); font-size: .75rem; }
.stpl-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 10px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; font-family: inherit; text-decoration: none; line-height: 1.3; }
.stpl-btn-primary { background: #8b5cf6; color: #fff; box-shadow: 0 1px 4px rgba(139,92,246,.22); }
.stpl-btn-primary:hover { background: #7c3aed; transform: translateY(-1px); color: #fff; }
.stpl-btn-outline { background: var(--surface, #fff); color: var(--text-2, #6b7280); border: 1px solid var(--border, #e5e7eb); }
.stpl-btn-outline:hover { border-color: #8b5cf6; color: #8b5cf6; }

/* ─── Bulk ─── */
.stpl-bulk { display: none; align-items: center; gap: 12px; padding: 10px 16px; background: rgba(139,92,246,.06); border: 1px solid rgba(139,92,246,.15); border-radius: 10px; margin-bottom: 12px; font-size: .78rem; color: #7c3aed; font-weight: 600; }
.stpl-bulk.active { display: flex; }
.stpl-bulk select { padding: 5px 10px; border: 1px solid var(--border, #e5e7eb); border-radius: 6px; background: var(--surface, #fff); color: var(--text, #111827); font-size: .75rem; }
.stpl-table input[type="checkbox"] { accent-color: #8b5cf6; width: 14px; height: 14px; cursor: pointer; }

/* ─── Table ─── */
.stpl-table-wrap { background: var(--surface, #fff); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; }
.stpl-table { width: 100%; border-collapse: collapse; }
.stpl-table thead th { padding: 11px 14px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-3, #9ca3af); background: var(--surface-2, #f9fafb); border-bottom: 1px solid var(--border, #e5e7eb); text-align: left; white-space: nowrap; }
.stpl-table thead th.center { text-align: center; }
.stpl-table tbody tr { border-bottom: 1px solid var(--border, #f3f4f6); transition: background .1s; }
.stpl-table tbody tr:hover { background: rgba(139,92,246,.02); }
.stpl-table tbody tr:last-child { border-bottom: none; }
.stpl-table td { padding: 11px 14px; font-size: .83rem; color: var(--text, #111827); vertical-align: middle; }
.stpl-table td.center { text-align: center; }

/* ─── Cellules ─── */
.stpl-tpl-name { font-weight: 600; color: var(--text, #111827); display: flex; align-items: center; gap: 8px; line-height: 1.3; }
.stpl-tpl-name a { color: var(--text, #111827); text-decoration: none; transition: color .15s; }
.stpl-tpl-name a:hover { color: #8b5cf6; }
.stpl-slug { font-family: monospace; font-size: .72rem; color: var(--text-3, #9ca3af); margin-top: 2px; }
.stpl-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 6px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.stpl-badge.page   { background: rgba(124,58,237,.12); color: #7c3aed; }
.stpl-badge.header { background: rgba(59,130,246,.12); color: #3b82f6; }
.stpl-badge.footer { background: rgba(219,39,119,.12); color: #db2777; }
.stpl-desc { color: var(--text-light, #5A5A5A); font-size: .75rem; line-height: 1.4; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.stpl-date { font-size: .73rem; color: var(--text-3, #9ca3af); white-space: nowrap; }

/* ─── Actions ─── */
.stpl-actions { display: flex; gap: 3px; justify-content: flex-end; }
.stpl-actions a, .stpl-actions button { width: 30px; height: 30px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--text-3, #9ca3af); background: transparent; border: 1px solid transparent; cursor: pointer; transition: all .12s; text-decoration: none; font-size: .78rem; }
.stpl-actions a:hover, .stpl-actions button:hover { color: #8b5cf6; border-color: var(--border, #e5e7eb); background: rgba(139,92,246,.07); }
.stpl-actions button.del:hover { color: #dc2626; border-color: rgba(220,38,38,.2); background: #fef2f2; }

/* ══ VUE GRILLE ══════════════════════════════════════════════ */
.stpl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
.stpl-card { background: var(--surface, #fff); border-radius: 14px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; transition: all .2s; display: flex; flex-direction: column; position: relative; }
.stpl-card:hover { border-color: #8b5cf6; box-shadow: 0 4px 20px rgba(139,92,246,.1); transform: translateY(-2px); }
.stpl-card-top { padding: 16px 16px 12px; flex: 1; }
.stpl-card-title { font-size: .88rem; font-weight: 700; color: var(--text, #111827); text-decoration: none; display: block; line-height: 1.35; }
.stpl-card-title:hover { color: #8b5cf6; }
.stpl-card-badges { display: flex; gap: 4px; flex-wrap: wrap; align-items: center; margin-top: 5px; }
.stpl-card-slug { font-family: monospace; font-size: .65rem; color: var(--text-3, #9ca3af); margin-top: 5px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.stpl-card-desc { font-size: .7rem; color: var(--text-2, #6b7280); margin-top: 6px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.stpl-card-footer { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-top: 1px solid var(--border, #f3f4f6); }
.stpl-card-footer .stpl-actions { justify-content: flex-start; }
.stpl-card-type-dot { position: absolute; top: 12px; right: 12px; width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; }
.stpl-card-type-dot.page   { background: linear-gradient(135deg, #7c3aed, #6d28d9); }
.stpl-card-type-dot.header { background: linear-gradient(135deg, #3b82f6, #1d4ed8); }
.stpl-card-type-dot.footer { background: linear-gradient(135deg, #db2777, #be185d); }

/* ─── Masquage vues ─── */
.stpl-list-view .stpl-grid-wrap { display: none !important; }
.stpl-grid-view .stpl-list-wrap { display: none !important; }

/* ─── Pagination ─── */
.stpl-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-top: 1px solid var(--border, #e5e7eb); font-size: .78rem; color: var(--text-3, #9ca3af); }
.stpl-pagination a { padding: 6px 12px; border: 1px solid var(--border, #e5e7eb); border-radius: 10px; color: var(--text-2, #6b7280); text-decoration: none; font-weight: 600; transition: all .15s; font-size: .78rem; }
.stpl-pagination a:hover { border-color: #8b5cf6; color: #8b5cf6; }
.stpl-pagination a.active { background: #8b5cf6; color: #fff; border-color: #8b5cf6; }

/* ─── Flash ─── */
.stpl-flash { padding: 12px 18px; border-radius: 10px; font-size: .85rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; animation: stplFlashIn .3s; }
.stpl-flash.success { background: #d1fae5; color: #059669; border: 1px solid rgba(5,150,105,.12); }
.stpl-flash.error   { background: #fef2f2; color: #dc2626; border: 1px solid rgba(220,38,38,.12); }
@keyframes stplFlashIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }

.stpl-empty { text-align: center; padding: 60px 20px; color: var(--text-3, #9ca3af); }
.stpl-empty i { font-size: 2.5rem; opacity: .2; margin-bottom: 12px; display: block; }
.stpl-empty h3 { color: var(--text-2, #6b7280); font-size: 1rem; font-weight: 600; margin-bottom: 6px; }

@media (max-width: 1200px) { .stpl-table .col-desc, .stpl-table .col-date { display: none; } }
@media (max-width: 960px) {
    .stpl-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .stpl-toolbar { flex-direction: column; align-items: flex-start; }
    .stpl-table-wrap { overflow-x: auto; }
    .stpl-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
}

/* ══════════════════════════════════════════════════════════ */
/* MODALE CUSTOM                                              */
/* ══════════════════════════════════════════════════════════ */
#stplModal { position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center; display: none; }
#stplModal > div:first-child { position: absolute; inset: 0; background: rgba(0,0,0,.45); backdrop-filter: blur(3px); }
#stplModalBox { position: relative; z-index: 1; background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,.18); width: 100%; max-width: 420px; margin: 16px; overflow: hidden; transform: scale(.94) translateY(8px); transition: transform .2s cubic-bezier(.34,1.56,.64,1), opacity .15s; opacity: 0; }
#stplModalBox.show { opacity: 1; transform: scale(1) translateY(0); }
#stplModalHeader { padding: 20px 22px 16px; display: flex; align-items: flex-start; gap: 14px; }
#stplModalIcon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem; }
#stplModalTitle { font-size: .95rem; font-weight: 700; color: #111827; margin-bottom: 5px; }
#stplModalMsg { font-size: .82rem; color: #6b7280; line-height: 1.5; }
#stplModalFooter { display: flex; gap: 8px; justify-content: flex-end; padding: 12px 20px 18px; border-top: 1px solid #f3f4f6; }
#stplModalCancel { padding: 9px 20px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff; color: #374151; font-size: .83rem; font-weight: 600; cursor: pointer; font-family: inherit; }
#stplModalConfirm { padding: 9px 20px; border-radius: 10px; border: none; font-size: .83rem; font-weight: 700; cursor: pointer; font-family: inherit; color: #fff; }

/* ══════════════════════════════════════════════════════════ */
/* TOAST NOTIFICATIONS                                        */
/* ══════════════════════════════════════════════════════════ */
.stpl-toast { position: fixed; bottom: 24px; right: 24px; z-index: 10000; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 18px; display: flex; align-items: center; gap: 10px; font-size: .83rem; font-weight: 600; color: #111827; box-shadow: 0 8px 24px rgba(0,0,0,.12); transform: translateY(20px); opacity: 0; transition: all .25s; }
.stpl-toast.show { opacity: 1; transform: translateY(0); }
.stpl-toast.success { border-color: #d1fae5; background: #f0fdf4; }
.stpl-toast.error   { border-color: #fecaca; background: #fef2f2; }
.stpl-toast i { font-size: .75rem; }
.stpl-toast i.success { color: #10b981; }
.stpl-toast i.error   { color: #dc2626; }
</style>

<div class="stpl-wrap" id="stplWrap">

<!-- ─── Onglets ─── -->
<div class="stpl-tabs">
    <a href="?page=system/templates&tab=templates" class="stpl-tab active"><i class="fas fa-palette"></i> Templates</a>
    <a href="?page=system/templates&tab=guide" class="stpl-tab"><i class="fas fa-lightbulb"></i> Guide</a>
</div>

<?php if ($flash === 'deleted'): ?>
    <div class="stpl-flash success"><i class="fas fa-check-circle"></i> Template supprimé avec succès</div>
<?php elseif ($flash === 'created'): ?>
    <div class="stpl-flash success"><i class="fas fa-check-circle"></i> Template créé avec succès</div>
<?php elseif ($flash === 'updated'): ?>
    <div class="stpl-flash success"><i class="fas fa-check-circle"></i> Template mis à jour</div>
<?php endif; ?>

<!-- ─── Banner ─── -->
<div class="stpl-banner">
    <div class="stpl-banner-left">
        <h2><i class="fas fa-palette"></i> Design & Templates</h2>
        <p>Gérez les templates de votre site (pages, headers, footers)</p>
    </div>
    <div class="stpl-stats">
        <div class="stpl-stat"><div class="num"><?= $stats['total'] ?></div><div class="lbl">Total</div></div>
        <div class="stpl-stat"><div class="num violet"><?= $stats['pages'] ?></div><div class="lbl">Pages</div></div>
        <div class="stpl-stat"><div class="num blue"><?= $stats['headers'] ?></div><div class="lbl">Headers</div></div>
        <div class="stpl-stat"><div class="num pink"><?= $stats['footers'] ?></div><div class="lbl">Footers</div></div>
    </div>
</div>

<!-- ─── Toolbar ─── -->
<div class="stpl-toolbar">
    <div class="stpl-filters">
        <?php
        $typeFilters = [
            'all'    => ['icon' => 'fa-layer-group', 'label' => 'Tous',   'count' => $stats['total']],
            'page'   => ['icon' => 'fa-file-lines',  'label' => 'Pages',  'count' => $stats['pages']],
            'header' => ['icon' => 'fa-window-maximize', 'label' => 'Headers', 'count' => $stats['headers']],
            'footer' => ['icon' => 'fa-window-minimize', 'label' => 'Footers', 'count' => $stats['footers']],
        ];
        foreach ($typeFilters as $key => $f):
            $active = ($filterType === $key) ? ' active' : '';
            $url = '?page=system/templates' . ($key !== 'all' ? '&type=' . $key : '');
            if ($searchQuery) $url .= '&q=' . urlencode($searchQuery);
        ?>
            <a href="<?= $url ?>" class="stpl-fbtn<?= $active ?>">
                <i class="fas <?= $f['icon'] ?>"></i> <?= $f['label'] ?>
                <span class="badge"><?= (int)$f['count'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="stpl-toolbar-r">
        <div class="stpl-view-toggle">
            <button class="stpl-view-btn active" id="btnList" onclick="STPL.setView('list')" title="Vue liste"><i class="fas fa-list"></i></button>
            <button class="stpl-view-btn" id="btnGrid" onclick="STPL.setView('grid')" title="Vue grille"><i class="fas fa-th-large"></i></button>
        </div>
        <form class="stpl-search" method="GET">
            <input type="hidden" name="page" value="system/templates">
            <?php if ($filterType !== 'all'): ?><input type="hidden" name="type" value="<?= htmlspecialchars($filterType) ?>"><?php endif; ?>
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Nom, slug..." value="<?= htmlspecialchars($searchQuery) ?>">
        </form>
        <button onclick="STPL.createTemplate()" class="stpl-btn stpl-btn-primary"><i class="fas fa-plus"></i> Nouveau</button>
    </div>
</div>

<?php if (empty($templates)): ?>
    <div class="stpl-empty">
        <i class="fas fa-palette"></i>
        <h3>Aucun template trouvé</h3>
        <p>
            <?php if ($searchQuery): ?>
                Aucun résultat pour « <?= htmlspecialchars($searchQuery) ?> ». <a href="?page=system/templates">Effacer</a>
            <?php else: ?>
                Créez votre premier template.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>

<!-- ══ VUE LISTE ══════════════════════════════════════════════ -->
<div class="stpl-list-wrap">
    <div class="stpl-table-wrap">
        <table class="stpl-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Type</th>
                    <th class="col-desc">Description</th>
                    <th>Slug</th>
                    <th class="col-date">Créé le</th>
                    <th class="center">Sort</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($templates as $t):
                $type = $t['type'] ?? 'page';
                $typeLabels = ['page' => 'Page', 'header' => 'Header', 'footer' => 'Footer'];
                $typeClasses = ['page' => 'page', 'header' => 'header', 'footer' => 'footer'];
                $date = !empty($t['created_at']) ? date('d/m/Y', strtotime($t['created_at'])) : '—';
                $editUrl = "?page=system/templates&action=edit&id={$t['id']}";
            ?>
            <tr data-id="<?= (int)$t['id'] ?>">
                <td>
                    <div class="stpl-tpl-name">
                        <a href="<?= htmlspecialchars($editUrl) ?>"><?= htmlspecialchars($t['name']) ?></a>
                    </div>
                    <?php if (!empty($t['slug'])): ?>
                    <div class="stpl-slug"><?= htmlspecialchars($t['slug']) ?></div>
                    <?php endif; ?>
                </td>

                <td>
                    <span class="stpl-badge <?= $typeClasses[$type] ?? 'page' ?>">
                        <?= $typeLabels[$type] ?? 'Page' ?>
                    </span>
                </td>

                <td class="col-desc">
                    <?php if (!empty($t['description'])): ?>
                        <span class="stpl-desc"><?= htmlspecialchars($t['description']) ?></span>
                    <?php else: ?>
                        <span style="color:var(--text-3,#9ca3af)">—</span>
                    <?php endif; ?>
                </td>

                <td>
                    <code style="font-size:.7rem;color:#9ca3af;background:var(--surface-2,#f9fafb);padding:2px 6px;border-radius:4px"><?= htmlspecialchars($t['slug'] ?? '—') ?></code>
                </td>

                <td class="col-date"><span class="stpl-date"><?= $date ?></span></td>

                <td class="center">
                    <input type="number" value="<?= (int)($t['sort_order'] ?? 0) ?>" min="0" style="width:50px;padding:5px;border:1px solid var(--border,#e5e7eb);border-radius:6px;font-size:.75rem" onchange="STPL.updateSort(<?= (int)$t['id'] ?>, this.value)">
                </td>

                <td>
                    <div class="stpl-actions">
                        <a href="<?= htmlspecialchars($editUrl) ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                        <button onclick="STPL.duplicate(<?= (int)$t['id'] ?>, '<?= addslashes(htmlspecialchars($t['name'])) ?>')" title="Dupliquer"><i class="fas fa-copy"></i></button>
                        <button class="del" onclick="STPL.deleteTemplate(<?= (int)$t['id'] ?>, '<?= addslashes(htmlspecialchars($t['name'])) ?>')" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="stpl-pagination">
            <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?> templates</span>
            <div style="display:flex;gap:4px">
                <?php for ($i=1; $i<=$totalPages; $i++):
                    $pUrl = '?page=system/templates&p='.$i;
                    if ($filterType!=='all')  $pUrl .= '&type='.$filterType;
                    if ($searchQuery)          $pUrl .= '&q='.urlencode($searchQuery);
                ?>
                    <a href="<?= $pUrl ?>" class="<?= $i===$currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══ VUE GRILLE ══════════════════════════════════════════════ -->
<div class="stpl-grid-wrap">
    <div class="stpl-grid">
    <?php foreach ($templates as $t):
        $type = $t['type'] ?? 'page';
        $typeLabels = ['page' => 'Page', 'header' => 'Header', 'footer' => 'Footer'];
        $typeClasses = ['page' => 'page', 'header' => 'header', 'footer' => 'footer'];
        $typeIcons = ['page' => 'fa-file-lines', 'header' => 'fa-window-maximize', 'footer' => 'fa-window-minimize'];
        $date = !empty($t['created_at']) ? date('d/m/Y', strtotime($t['created_at'])) : '—';
        $editUrl = "?page=system/templates&action=edit&id={$t['id']}";
    ?>
    <div class="stpl-card" data-id="<?= (int)$t['id'] ?>">
        <div class="stpl-card-type-dot <?= $typeClasses[$type] ?? 'page' ?>" title="<?= $typeLabels[$type] ?? 'Page' ?>">
            <i class="fas <?= $typeIcons[$type] ?? 'fa-file-lines' ?>"></i>
        </div>
        <div class="stpl-card-top">
            <a href="<?= htmlspecialchars($editUrl) ?>" class="stpl-card-title"><?= htmlspecialchars($t['name']) ?></a>
            <div class="stpl-card-badges">
                <span class="stpl-badge <?= $typeClasses[$type] ?? 'page' ?>" style="font-size:.6rem;padding:2px 7px"><?= $typeLabels[$type] ?? 'Page' ?></span>
            </div>
            <?php if (!empty($t['slug'])): ?>
            <span class="stpl-card-slug"><?= htmlspecialchars($t['slug']) ?></span>
            <?php endif; ?>
            <?php if (!empty($t['description'])): ?>
            <span class="stpl-card-desc"><?= htmlspecialchars($t['description']) ?></span>
            <?php endif; ?>
        </div>
        <div class="stpl-card-footer">
            <div class="stpl-actions" style="justify-content:flex-start">
                <a href="<?= htmlspecialchars($editUrl) ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                <button onclick="STPL.duplicate(<?= (int)$t['id'] ?>, '<?= addslashes(htmlspecialchars($t['name'])) ?>')" title="Dupliquer"><i class="fas fa-copy"></i></button>
                <button class="del" onclick="STPL.deleteTemplate(<?= (int)$t['id'] ?>, '<?= addslashes(htmlspecialchars($t['name'])) ?>')" title="Supprimer"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="stpl-pagination" style="background:var(--surface,#fff);border-radius:12px;border:1px solid var(--border,#e5e7eb);margin-top:12px">
        <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?> templates</span>
        <div style="display:flex;gap:4px">
            <?php for ($i=1; $i<=$totalPages; $i++):
                $pUrl = '?page=system/templates&p='.$i;
                if ($filterType!=='all')  $pUrl .= '&type='.$filterType;
                if ($searchQuery)          $pUrl .= '&q='.urlencode($searchQuery);
            ?>
                <a href="<?= $pUrl ?>" class="<?= $i===$currentPage ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>
</div><!-- /stpl-wrap -->

<!-- ══ MODALE CUSTOM ════════════════════════════════════════ -->
<div id="stplModal">
    <div onclick="STPL.modalClose()"></div>
    <div id="stplModalBox">
        <div id="stplModalHeader">
            <div id="stplModalIcon"></div>
            <div style="flex:1;min-width:0">
                <div id="stplModalTitle"></div>
                <div id="stplModalMsg"></div>
            </div>
        </div>
        <div id="stplModalFooter">
            <button id="stplModalCancel" onclick="STPL.modalClose()">Annuler</button>
            <button id="stplModalConfirm"></button>
        </div>
    </div>
</div>

<div id="stplToast" class="stpl-toast"></div>

<script>
const STPL = {
    apiUrl: '/admin/api/templates/templates.php',
    csrf: <?= json_encode($csrfToken) ?>,
    _modalCb: null,

    // ── Vue liste/grille ────────────────────────────────────
    setView(v) {
        const wrap = document.getElementById('stplWrap');
        wrap.classList.remove('stpl-list-view', 'stpl-grid-view');
        wrap.classList.add(v === 'grid' ? 'stpl-grid-view' : 'stpl-list-view');
        document.getElementById('btnList').classList.toggle('active', v !== 'grid');
        document.getElementById('btnGrid').classList.toggle('active', v === 'grid');
        try { sessionStorage.setItem('stpl_view', v); } catch(e) {}
    },
    initView() {
        let v = 'list';
        try { v = sessionStorage.getItem('stpl_view') || 'list'; } catch(e) {}
        this.setView(v);
    },

    // ── Créer ───────────────────────────────────────────────
    createTemplate() {
        const name = prompt('Nom du template :');
        if (!name || !name.trim()) return;
        const fd = new FormData();
        fd.append('action', 'create'); fd.append('name', name.trim()); fd.append('csrf_token', this.csrf);
        fetch(this.apiUrl, {method:'POST', body:fd})
            .then(r => r.json())
            .then(d => {
                if (d.success && d.id) {
                    this.toast('Template créé !', 'success');
                    setTimeout(() => { window.location.href = '?page=system/templates&action=edit&id=' + d.id; }, 600);
                } else { this.toast(d.error || 'Erreur', 'error'); }
            })
            .catch(e => this.toast('Erreur: ' + e.message, 'error'));
    },

    // ── Dupliquer ───────────────────────────────────────────
    duplicate(id, name) {
        this.modal({
            icon: '<i class="fas fa-copy"></i>', iconBg: '#eff6ff', iconColor: '#3b82f6',
            title: 'Dupliquer ce template ?',
            msg: `Une copie de <strong>${name}</strong> sera créée.`,
            confirmLabel: 'Dupliquer', confirmColor: '#3b82f6',
            onConfirm: async () => {
                const fd = new FormData();
                fd.append('action', 'duplicate'); fd.append('id', id); fd.append('csrf_token', this.csrf);
                try {
                    const r = await fetch(this.apiUrl, {method:'POST', body:fd});
                    const d = await r.json();
                    if (d.success) { this.toast('Template dupliqué ✓', 'success'); setTimeout(() => location.reload(), 800); }
                    else { this.toast(d.error || 'Erreur', 'error'); }
                } catch(e) { this.toast('Erreur réseau', 'error'); }
            }
        });
    },

    // ── Supprimer ───────────────────────────────────────────
    deleteTemplate(id, name) {
        this.modal({
            icon: '<i class="fas fa-trash"></i>', iconBg: '#fef2f2', iconColor: '#dc2626',
            title: 'Supprimer ce template ?',
            msg: `Le template <strong>${name}</strong> sera supprimé définitivement.<br><span style="font-size:.75rem;color:#9ca3af">Cette action est irréversible.</span>`,
            confirmLabel: 'Supprimer', confirmColor: '#dc2626',
            onConfirm: async () => {
                const fd = new FormData();
                fd.append('action', 'delete'); fd.append('id', id); fd.append('csrf_token', this.csrf);
                try {
                    const r = await fetch(this.apiUrl, {method:'POST', body:fd});
                    const d = await r.json();
                    if (d.success) {
                        document.querySelectorAll(`[data-id="${id}"]`).forEach(el => {
                            el.style.cssText = 'opacity:0;transform:scale(.95);transition:all .3s';
                            setTimeout(() => el.remove(), 300);
                        });
                        this.toast('Template supprimé', 'success');
                    } else { this.toast(d.error || 'Erreur', 'error'); }
                } catch(e) { this.toast('Erreur réseau: ' + e.message, 'error'); }
            }
        });
    },

    // ── Trier ───────────────────────────────────────────────
    async updateSort(id, order) {
        const fd = new FormData();
        fd.append('action', 'update_sort'); fd.append('id', id); fd.append('order', order); fd.append('csrf_token', this.csrf);
        try {
            const r = await fetch(this.apiUrl, {method:'POST', body:fd});
            const d = await r.json();
            if (d.success) { this.toast('Ordre mis à jour', 'success'); }
            else { this.toast(d.error || 'Erreur', 'error'); }
        } catch(e) { this.toast('Erreur réseau', 'error'); }
    },

    // ── Modal ────────────────────────────────────────────────
    modal({ icon, iconBg, iconColor, title, msg, confirmLabel, confirmColor, onConfirm }) {
        const el  = document.getElementById('stplModal');
        const box = document.getElementById('stplModalBox');
        document.getElementById('stplModalIcon').innerHTML    = icon;
        document.getElementById('stplModalIcon').style.background = iconBg;
        document.getElementById('stplModalIcon').style.color      = iconColor;
        document.getElementById('stplModalHeader').style.background = iconBg + '33';
        document.getElementById('stplModalTitle').textContent = title;
        document.getElementById('stplModalMsg').innerHTML     = msg;
        const btn = document.getElementById('stplModalConfirm');
        btn.textContent      = confirmLabel || 'Confirmer';
        btn.style.background = confirmColor || '#8b5cf6';
        this._modalCb = onConfirm;
        btn.onclick = () => { this.modalClose(); if (this._modalCb) this._modalCb(); };
        el.style.display = 'flex';
        requestAnimationFrame(() => { box.classList.add('show'); });
        document.addEventListener('keydown', this._escHandler);
    },
    modalClose() {
        const el  = document.getElementById('stplModal');
        const box = document.getElementById('stplModalBox');
        box.classList.remove('show');
        setTimeout(() => el.style.display='none', 160);
        document.removeEventListener('keydown', this._escHandler);
    },
    _escHandler(e) { if (e.key === 'Escape') STPL.modalClose(); },

    // ── Toast ────────────────────────────────────────────────
    toast(msg, type = 'success') {
        const t = document.getElementById('stplToast');
        t.textContent = msg;
        t.className = 'stpl-toast show ' + type;
        const icon = t.querySelector('i') || document.createElement('i');
        icon.className = 'fas ' + (type === 'success' ? 'fa-check-circle success' : 'fa-exclamation-circle error');
        if (!t.querySelector('i')) t.insertAdjacentElement('afterbegin', icon);
        setTimeout(() => t.classList.remove('show'), 3500);
    }
};

document.addEventListener('DOMContentLoaded', () => STPL.initView());
</script>