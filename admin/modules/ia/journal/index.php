<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE JOURNAL ÉDITORIAL v4.2
 *  /admin/modules/ai/journal/index.php
 *
 *  Refacto UX aligné articles v2.3 / captures v2.0 / pages v1.0 :
 *  - Modal custom animé (suppression, bulk delete)
 *  - Toast unifié avec icône colorée
 *  - Modal édition redessiné (même pattern que le modal de confirmation)
 *  - CSS nettoyé : plus de style inline dans le JS
 *  - _post() URL propre
 *  - display:none/flex géré via CSS .jnl-modal-active
 * ══════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/JournalController.php';

if (!isset($pdo)) {
    echo '<div style="padding:40px;text-align:center;color:#dc2626"><i class="fas fa-exclamation-circle"></i> Erreur : connexion $pdo non disponible</div>';
    return;
}

$jCtrl = new JournalController($pdo);

if (!$jCtrl->tableExists()) { ?>
    <div style="background:var(--surface);border:2px dashed var(--border);border-radius:var(--radius-xl);padding:60px 30px;text-align:center">
        <i class="fas fa-database" style="font-size:3rem;opacity:.2;color:#8b5cf6;margin-bottom:16px;display:block"></i>
        <h3 style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;margin-bottom:8px">Table <code>editorial_journal</code> introuvable</h3>
        <p style="font-size:0.85rem;color:var(--text-2)">Importez <code>modules/ai/journal/sql/journal.sql</code> dans phpMyAdmin.</p>
    </div>
<?php return; }

// ─── Données ───
$statsGlobal    = $jCtrl->getStatsGlobal();
$statsByChannel = $jCtrl->getStatsByChannel();
$matrixData     = $jCtrl->getMatrixData();
$config         = $jCtrl->getConfig();
$secteurs       = $jCtrl->getSecteurs();
$currentWeek    = JournalController::getCurrentWeek();
$csrfToken      = $_SESSION['csrf_token'] ?? '';

$channelStatsMap = [];
foreach ($statsByChannel as $cs) $channelStatsMap[$cs['channel_id']] = $cs;

$tab = $_GET['tab'] ?? 'global';

$filterCanal  = $_GET['canal']  ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';
$filterSem    = (int)($_GET['sem'] ?? 0);
$searchQ      = trim($_GET['q'] ?? '');
$currentPage  = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 40;
$offset       = ($currentPage - 1) * $perPage;

$canaux  = JournalController::CHANNELS;
$statuts = JournalController::STATUSES;

$allItems = []; $totalItems = 0; $totalPages = 1;
if ($tab === 'global') {
    $filters = [];
    if ($filterCanal  !== 'all') $filters['channel_id'] = $filterCanal;
    if ($filterStatus !== 'all') $filters['status']     = $filterStatus;
    if ($filterSem    > 0)       $filters['week_number']= $filterSem;
    if ($searchQ !== '')         $filters['search']     = $searchQ;
    $allItems   = $jCtrl->getList($filters, $perPage, $offset);
    $totalItems = $jCtrl->countList($filters);
    $totalPages = max(1, ceil($totalItems / $perPage));
}

$total   = (int)($statsGlobal['total']     ?? 0);
$idees   = (int)($statsGlobal['ideas']     ?? 0) + (int)($statsGlobal['planned']   ?? 0);
$enCours = (int)($statsGlobal['validated'] ?? 0) + (int)($statsGlobal['writing']   ?? 0);
$prets   = (int)($statsGlobal['ready']     ?? 0);
$publies = (int)($statsGlobal['published'] ?? 0);

function jUrl(array $overrides = []): string {
    $base = ['page' => 'journal'];
    foreach (['tab','canal','status','sem','q'] as $k) if (isset($_GET[$k])) $base[$k] = $_GET[$k];
    unset($base['p']);
    foreach ($overrides as $k => $v) {
        if ($v === null || $v === 'all' || $v === '') unset($base[$k]);
        else $base[$k] = $v;
    }
    return '?' . http_build_query($base);
}

// Prépare données JS
$chColors = array_combine(array_keys($canaux), array_column($canaux, 'color'));
$chIcons  = array_combine(array_keys($canaux), array_column($canaux, 'icon'));
$chLabels = array_combine(array_keys($canaux), array_column($canaux, 'label'));
?>

<style>
/* ══════════════════════════════════════════════════════════════
   JOURNAL ÉDITORIAL v4.1
   Refacto : modal custom, toast unifié, CSS propre
══════════════════════════════════════════════════════════════ */
.jnl { font-family: var(--font); }

/* ── Banner ── */
.jnl-banner {
    background: var(--surface); border-radius: var(--radius-xl);
    padding: 26px 30px; margin-bottom: 22px;
    display: flex; align-items: center; justify-content: space-between;
    border: 1px solid var(--border); position: relative; overflow: hidden;
}
.jnl-banner::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg,#8b5cf6,#6366f1,#3b82f6,#0ea5e9,#10b981,#f59e0b,#ef4444);
}
.jnl-banner::after {
    content: ''; position: absolute; top: -40%; right: -5%; width: 240px; height: 240px;
    background: radial-gradient(circle, rgba(139,92,246,.05), transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.jnl-banner-l { position: relative; z-index: 1; }
.jnl-banner-l h2 { font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; color: var(--text); margin: 0 0 4px; display: flex; align-items: center; gap: 10px; letter-spacing: -.02em; }
.jnl-banner-l h2 i { color: #8b5cf6; font-size: 16px; }
.jnl-banner-l p { color: var(--text-2); font-size: .85rem; margin: 0 0 12px; }
.jnl-canal-pills { display: flex; gap: 5px; flex-wrap: wrap; }
.jnl-canal-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: .67rem; font-weight: 600; border: 1px solid transparent; }

/* ── Stats ── */
.jnl-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.jnl-stat { text-align: center; padding: 10px 16px; background: var(--surface-2); border-radius: var(--radius-lg); border: 1px solid var(--border); min-width: 72px; transition: all .2s; }
.jnl-stat:hover { border-color: var(--border-h); box-shadow: var(--shadow-xs); }
.jnl-stat .num { font-family: var(--font-display); font-size: 1.45rem; font-weight: 800; line-height: 1; letter-spacing: -.03em; }
.jnl-stat .num.violet { color: #8b5cf6; }
.jnl-stat .num.blue   { color: var(--accent); }
.jnl-stat .num.green  { color: var(--green); }
.jnl-stat .num.amber  { color: #f59e0b; }
.jnl-stat .lbl { font-size: .58rem; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }

/* ── Onglets ── */
.jnl-tabs { display: flex; gap: 2px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 4px; margin-bottom: 20px; width: fit-content; flex-wrap: wrap; }
.jnl-tab { padding: 8px 18px; border: none; background: transparent; color: var(--text-2); font-size: .82rem; font-weight: 600; border-radius: var(--radius); cursor: pointer; transition: all .15s; font-family: var(--font); display: flex; align-items: center; gap: 6px; }
.jnl-tab:hover { color: var(--text); background: var(--surface-2); }
.jnl-tab.active { background: #8b5cf6; color: #fff; box-shadow: 0 2px 8px rgba(139,92,246,.25); }
.jnl-tab .cnt { font-size: .65rem; padding: 1px 7px; border-radius: 10px; font-weight: 700; }
.jnl-tab.active .cnt { background: rgba(255,255,255,.2); }
.jnl-tab:not(.active) .cnt { background: var(--surface-2); color: var(--text-3); }

/* ── Panels ── */
.jnl-panel { display: none; }
.jnl-panel.active { display: block; }

/* ── Canal filters ── */
.jnl-canal-filters { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 16px; }
.jnl-cf-pill { display: inline-flex; align-items: center; gap: 5px; padding: 5px 13px; border-radius: 20px; font-size: .72rem; font-weight: 600; border: 1px solid var(--border); background: var(--surface); color: var(--text-2); text-decoration: none; transition: all .15s; white-space: nowrap; }
.jnl-cf-pill:hover { border-color: var(--border-h); color: var(--text); box-shadow: var(--shadow-xs); }
.jnl-cf-pill.active { color: #fff !important; border-color: transparent !important; }
.jnl-cf-pill .cnt { font-size: .6rem; padding: 1px 6px; border-radius: 10px; font-weight: 700; }
.jnl-cf-pill.active .cnt { background: rgba(255,255,255,.25); }
.jnl-cf-pill:not(.active) .cnt { background: var(--surface-2); color: var(--text-3); }

/* ── Toolbar ── */
.jnl-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 10px; }
.jnl-toolbar-l { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
.jnl-filters-bar { display: flex; gap: 3px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 3px; }
.jnl-fbtn { padding: 7px 14px; border: none; background: transparent; color: var(--text-2); font-size: .78rem; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all .15s; font-family: var(--font); display: flex; align-items: center; gap: 5px; text-decoration: none; }
.jnl-fbtn:hover { color: var(--text); background: var(--surface-2); }
.jnl-fbtn.active { background: #8b5cf6; color: #fff; box-shadow: 0 1px 4px rgba(139,92,246,.25); }
.jnl-fbtn .badge { font-size: .68rem; padding: 1px 7px; border-radius: 10px; background: var(--surface-2); font-weight: 700; color: var(--text-3); }
.jnl-fbtn.active .badge { background: rgba(255,255,255,.22); color: #fff; }
.jnl-reset { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; background: rgba(139,92,246,.05); border: 1px solid rgba(139,92,246,.15); border-radius: 6px; font-size: .72rem; font-weight: 600; color: #8b5cf6; text-decoration: none; transition: all .15s; }
.jnl-reset:hover { background: rgba(139,92,246,.1); }
.jnl-sem-select { padding: 7px 10px; border: 1px solid var(--border); border-radius: var(--radius); font-size: .78rem; font-family: var(--font); background: var(--surface); color: var(--text); cursor: pointer; }
.jnl-sem-select:focus { outline: none; border-color: #8b5cf6; }

/* ── Boutons ── */
.jnl-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: var(--radius); font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; font-family: var(--font); text-decoration: none; line-height: 1.3; }
.jnl-btn-primary { background: #8b5cf6; color: #fff; box-shadow: 0 1px 4px rgba(139,92,246,.22); }
.jnl-btn-primary:hover { background: #7c3aed; transform: translateY(-1px); color: #fff; }
.jnl-btn-outline { background: var(--surface); color: var(--text-2); border: 1px solid var(--border); }
.jnl-btn-outline:hover { border-color: var(--border-h); background: var(--surface-2); color: var(--text); }
.jnl-btn-sm { padding: 5px 12px; font-size: .75rem; }
.jnl-btn-green { background: var(--green); color: #fff; }
.jnl-btn-green:hover { background: #047857; color: #fff; }

/* ── Search ── */
.jnl-toolbar-r { display: flex; align-items: center; gap: 10px; }
.jnl-search { position: relative; }
.jnl-search input { padding: 8px 12px 8px 34px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); color: var(--text); font-size: .82rem; width: 220px; font-family: var(--font); transition: all .2s; }
.jnl-search input:focus { outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,.1); width: 250px; }
.jnl-search input::placeholder { color: var(--text-3); }
.jnl-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-3); font-size: .75rem; }

/* ── Bulk bar ── */
.jnl-bulk { display: none; align-items: center; gap: 12px; padding: 10px 16px; background: rgba(139,92,246,.04); border: 1px solid rgba(139,92,246,.15); border-radius: var(--radius); margin-bottom: 12px; font-size: .78rem; color: #8b5cf6; font-weight: 600; }
.jnl-bulk.active { display: flex; }
.jnl-bulk select { padding: 5px 10px; border: 1px solid var(--border); border-radius: 6px; background: var(--surface); color: var(--text); font-size: .75rem; font-family: var(--font); }
.jnl-table input[type="checkbox"] { accent-color: #8b5cf6; width: 14px; height: 14px; cursor: pointer; }

/* ── Table ── */
.jnl-table-wrap { background: var(--surface); border-radius: var(--radius-lg); border: 1px solid var(--border); overflow: hidden; }
.jnl-table { width: 100%; border-collapse: collapse; }
.jnl-table thead th { padding: 11px 14px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-3); background: var(--surface-2); border-bottom: 1px solid var(--border); text-align: left; white-space: nowrap; }
.jnl-table tbody tr { border-bottom: 1px solid var(--border); transition: background .1s; }
.jnl-table tbody tr:hover { background: rgba(139,92,246,.02); }
.jnl-table tbody tr.jnl-row-curweek { background: rgba(139,92,246,.03); }
.jnl-table tbody tr:last-child { border-bottom: none; }
.jnl-table td { padding: 10px 14px; font-size: .83rem; color: var(--text); vertical-align: middle; }

/* ── Cellule titre ── */
.jnl-titre { font-weight: 600; max-width: 300px; }
.jnl-titre a { color: var(--text); text-decoration: none; transition: color .15s; }
.jnl-titre a:hover { color: #8b5cf6; }
.jnl-meta { font-size: .68rem; color: var(--text-3); margin-top: 2px; display: flex; align-items: center; gap: 4px; }

/* ── Badges ── */
.jnl-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: .67rem; font-weight: 600; white-space: nowrap; color: #fff; }
.jnl-badge-outline { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 20px; font-size: .67rem; font-weight: 600; white-space: nowrap; border: 1px solid; background: transparent; }
.jnl-badge-week { font-size: .7rem; font-weight: 700; padding: 2px 8px; border-radius: 5px; background: var(--surface-2); color: var(--text-3); }
.jnl-badge-week.cur { background: rgba(139,92,246,.12); color: #8b5cf6; }
.jnl-badge-type { font-size: .63rem; font-weight: 600; padding: 2px 7px; border-radius: 5px; background: var(--surface-2); color: var(--text-2); }
.jnl-canal-icon { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 6px; font-size: .75rem; }

/* ── Actions ── */
.jnl-actions { display: flex; gap: 3px; }
.jnl-actions a, .jnl-actions button { width: 30px; height: 30px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; color: var(--text-3); background: transparent; border: 1px solid transparent; cursor: pointer; transition: all .12s; text-decoration: none; font-size: .78rem; }
.jnl-actions a:hover, .jnl-actions button:hover { color: #8b5cf6; border-color: var(--border); background: rgba(139,92,246,.07); }
.jnl-actions .btn-ok:hover  { color: #059669; border-color: rgba(5,150,105,.2);  background: rgba(5,150,105,.06); }
.jnl-actions .btn-go:hover  { color: #2563eb; border-color: rgba(37,99,235,.2);  background: rgba(37,99,235,.06); }
.jnl-actions .btn-del:hover { color: #dc2626; border-color: rgba(220,38,38,.2);  background: rgba(220,38,38,.06); }

/* ── Canal cards ── */
.jnl-ch-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-bottom: 22px; }
.jnl-ch-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 16px; cursor: pointer; transition: all .2s; position: relative; overflow: hidden; text-decoration: none; color: inherit; display: block; }
.jnl-ch-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
.jnl-ch-card-bar { position: absolute; top: 0; left: 0; right: 0; height: 3px; }
.jnl-ch-card-head { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.jnl-ch-card-head span { font-size: .88rem; font-weight: 700; }
.jnl-ch-nums { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 4px; text-align: center; }
.jnl-ch-num .v { font-family: var(--font-display); font-size: 1.1rem; font-weight: 800; }
.jnl-ch-num .l { font-size: .6rem; color: var(--text-3); text-transform: uppercase; letter-spacing: .04em; }
.jnl-ch-track { height: 3px; background: var(--surface-2); border-radius: 2px; margin-top: 10px; overflow: hidden; }
.jnl-ch-fill  { height: 100%; border-radius: 2px; transition: width .5s; }

/* ── Matrice ── */
.jnl-matrix-wrap { overflow-x: auto; }
.jnl-matrix { width: 100%; border-collapse: collapse; background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; font-size: .82rem; }
.jnl-matrix th { padding: 10px 12px; font-size: .68rem; text-transform: uppercase; font-weight: 700; background: var(--surface-2); color: var(--text-3); border-bottom: 1px solid var(--border); text-align: center; white-space: nowrap; }
.jnl-matrix th.left { text-align: left; font-size: .82rem; color: var(--text-2); font-weight: 700; }
.jnl-matrix td { padding: 12px; text-align: center; border-bottom: 1px solid var(--border); border-right: 1px solid var(--border); }
.jnl-matrix td.profile { text-align: left; font-weight: 600; padding-left: 16px; background: var(--surface-2); white-space: nowrap; }
.jnl-matrix-cell { display: flex; flex-direction: column; align-items: center; gap: 2px; }
.jnl-matrix-cnt { font-family: var(--font-display); font-size: 1.2rem; font-weight: 800; }
.jnl-matrix-cnt.empty  { color: var(--text-3); opacity: .4; }
.jnl-matrix-cnt.low    { color: #f59e0b; }
.jnl-matrix-cnt.ok     { color: #3b82f6; }
.jnl-matrix-cnt.good   { color: #10b981; }
.jnl-matrix-pub { font-size: .62rem; color: var(--text-3); }
.jnl-matrix-legend { display: flex; gap: 16px; margin-top: 12px; font-size: .75rem; color: var(--text-3); flex-wrap: wrap; }

/* ── Générateur ── */
.jnl-gen-wrap { display: grid; grid-template-columns: 380px 1fr; gap: 20px; align-items: start; }
.jnl-gen-form { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 24px; position: relative; overflow: hidden; }
.jnl-gen-form::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg,#8b5cf6,#6366f1,#3b82f6); }
.jnl-gen-form h3 { font-family: var(--font-display); font-size: 1rem; font-weight: 700; margin: 0 0 18px; display: flex; align-items: center; gap: 8px; }
.jnl-gen-form h3 i { color: #8b5cf6; }
.jnl-gen-row { margin-bottom: 14px; }
.jnl-gen-row label { display: block; font-size: .75rem; font-weight: 700; color: var(--text-2); margin-bottom: 5px; text-transform: uppercase; letter-spacing: .04em; }
.jnl-gen-row select, .jnl-gen-row input { width: 100%; padding: 9px 12px; border: 1px solid var(--border); border-radius: var(--radius); font-size: .85rem; font-family: var(--font); background: var(--surface); color: var(--text); transition: border-color .2s; }
.jnl-gen-row select:focus, .jnl-gen-row input:focus { outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,.1); }
.jnl-gen-personas { display: flex; flex-direction: column; gap: 8px; margin-top: 4px; }
.jnl-gen-persona { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid var(--border); border-radius: var(--radius); cursor: pointer; transition: all .15s; background: var(--surface); user-select: none; }
.jnl-gen-persona:hover { border-color: #8b5cf6; background: rgba(139,92,246,.04); }
.jnl-gen-persona.selected { border-color: #8b5cf6; background: rgba(139,92,246,.07); }
.jnl-gen-persona input[type="checkbox"] { accent-color: #8b5cf6; }
.jnl-gen-persona .p-avatar { width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .8rem; color: #fff; flex-shrink: 0; }
.jnl-gen-persona .p-name { font-size: .82rem; font-weight: 600; }
.jnl-gen-persona .p-sub  { font-size: .7rem; color: var(--text-3); }
.jnl-gen-result-area { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-xl); padding: 24px; }
.jnl-gen-result-area h3 { font-family: var(--font-display); font-size: 1rem; font-weight: 700; margin: 0 0 16px; display: flex; align-items: center; gap: 8px; }

/* Résultat génération - items */
.jnl-preview-item { background: var(--surface-2); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 14px 16px; margin-bottom: 8px; transition: border-color .15s; }
.jnl-preview-item:hover { border-color: var(--border-h); }
.jnl-preview-item-head { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.jnl-preview-item-icon { width: 24px; height: 24px; border-radius: 5px; display: flex; align-items: center; justify-content: center; font-size: .7rem; flex-shrink: 0; }
.jnl-preview-item-title { font-size: .88rem; font-weight: 600; flex: 1; color: var(--text); }
.jnl-preview-item-ch { font-size: .65rem; padding: 2px 8px; border-radius: 10px; font-weight: 700; }
.jnl-preview-item-meta { font-size: .7rem; color: var(--text-3); }
.jnl-preview-item-actions { display: flex; gap: 6px; margin-top: 8px; }
.jnl-preview-item.validated { opacity: .45; }

/* Status bar générateur */
.jnl-gen-status { padding: 12px 16px; border-radius: var(--radius); font-size: .85rem; font-weight: 600; display: none; margin-top: 14px; }
.jnl-gen-status.ok      { display: flex; align-items: center; gap: 8px; background: var(--green-bg,#d1fae5); color: var(--green,#059669); border: 1px solid rgba(5,150,105,.12); }
.jnl-gen-status.err     { display: flex; align-items: center; gap: 8px; background: rgba(220,38,38,.06); color: #dc2626; border: 1px solid rgba(220,38,38,.12); }
.jnl-gen-status.loading { display: flex; align-items: center; gap: 8px; background: rgba(139,92,246,.06); color: #8b5cf6; border: 1px solid rgba(139,92,246,.12); }

/* ── Performance ── */
.jnl-perf-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; margin-bottom: 20px; }
.jnl-perf-stat { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 20px; text-align: center; }
.jnl-perf-stat .v { font-family: var(--font-display); font-size: 2rem; font-weight: 800; }
.jnl-perf-stat .l { font-size: .75rem; color: var(--text-3); margin-top: 4px; font-weight: 500; }
.jnl-pipeline { display: flex; height: 32px; border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 8px; }
.jnl-pipeline-seg { display: flex; align-items: center; justify-content: center; font-size: .7rem; font-weight: 700; color: #fff; transition: width .5s; min-width: 0; overflow: hidden; }
.jnl-pipeline-legend { display: flex; gap: 14px; font-size: .75rem; color: var(--text-3); flex-wrap: wrap; }
.jnl-perf-ch-table { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); overflow: hidden; }
.jnl-perf-ch-table table { width: 100%; border-collapse: collapse; }
.jnl-perf-ch-table thead th { padding: 10px 14px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-3); background: var(--surface-2); border-bottom: 1px solid var(--border); }
.jnl-perf-ch-table tbody td { padding: 12px 14px; border-bottom: 1px solid var(--border); font-size: .83rem; }
.jnl-perf-ch-table tbody tr:last-child td { border-bottom: none; }
.jnl-perf-ch-table tbody tr:hover td { background: rgba(139,92,246,.02); }
.jnl-bar-inline { display: flex; align-items: center; gap: 8px; }
.jnl-bar-track  { flex: 1; height: 5px; background: var(--surface-2); border-radius: 3px; overflow: hidden; }
.jnl-bar-fill   { height: 100%; border-radius: 3px; }

/* ── Pagination ── */
.jnl-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-top: 1px solid var(--border); font-size: .78rem; color: var(--text-3); }
.jnl-pagination a { padding: 6px 12px; border: 1px solid var(--border); border-radius: var(--radius); color: var(--text-2); text-decoration: none; font-weight: 600; transition: all .15s; }
.jnl-pagination a:hover { border-color: #8b5cf6; color: #8b5cf6; background: rgba(139,92,246,.05); }
.jnl-pagination a.active { background: #8b5cf6; color: #fff; border-color: #8b5cf6; }

/* ── Empty ── */
.jnl-empty { text-align: center; padding: 60px 20px; color: var(--text-3); }
.jnl-empty i { font-size: 2.5rem; opacity: .2; margin-bottom: 12px; display: block; }
.jnl-empty h3 { font-family: var(--font-display); color: var(--text-2); font-size: 1rem; font-weight: 600; margin-bottom: 8px; }
.jnl-empty .jnl-link-btn { background: none; border: none; color: #8b5cf6; font-weight: 700; cursor: pointer; font-size: inherit; padding: 0; }

/* ── Flash ── */
.jnl-flash { padding: 12px 18px; border-radius: var(--radius); font-size: .85rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; animation: jnlFI .3s var(--ease,ease); }
.jnl-flash.s { background: var(--green-bg,#d1fae5); color: var(--green,#059669); border: 1px solid rgba(5,150,105,.12); }
.jnl-flash.e { background: rgba(220,38,38,.06); color: #dc2626; border: 1px solid rgba(220,38,38,.12); }
@keyframes jnlFI { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:none} }

/* ══ MODAL CUSTOM (pattern unifié) ══════════════════════════ */
.jnl-modal-overlay {
    display: none; position: fixed; inset: 0; z-index: 9998;
    align-items: center; justify-content: center;
    background: rgba(0,0,0,.45); backdrop-filter: blur(3px);
}
.jnl-modal-overlay.active { display: flex; }
.jnl-modal-box {
    position: relative; background: #fff; border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.18);
    width: 100%; max-width: 420px; margin: 16px; overflow: hidden;
    transform: scale(.94) translateY(8px);
    transition: transform .2s cubic-bezier(.34,1.56,.64,1), opacity .15s;
    opacity: 0;
}
.jnl-modal-overlay.active .jnl-modal-box { transform: scale(1) translateY(0); opacity: 1; }
.jnl-modal-header { padding: 20px 22px 16px; display: flex; align-items: flex-start; gap: 14px; }
.jnl-modal-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 1.1rem; }
.jnl-modal-title { font-size: .95rem; font-weight: 700; color: #111827; margin-bottom: 5px; }
.jnl-modal-msg   { font-size: .82rem; color: #6b7280; line-height: 1.5; }
.jnl-modal-footer { display: flex; gap: 8px; justify-content: flex-end; padding: 12px 20px 18px; border-top: 1px solid #f3f4f6; }
.jnl-modal-cancel  { padding: 9px 20px; border-radius: 10px; border: 1px solid #e5e7eb; background: #fff; color: #374151; font-size: .83rem; font-weight: 600; cursor: pointer; font-family: inherit; transition: all .15s; }
.jnl-modal-cancel:hover  { border-color: #8b5cf6; color: #8b5cf6; }
.jnl-modal-confirm { padding: 9px 20px; border-radius: 10px; border: none; font-size: .83rem; font-weight: 700; cursor: pointer; font-family: inherit; color: #fff; transition: filter .15s; }
.jnl-modal-confirm:hover { filter: brightness(.88); }

/* ══ MODAL ÉDITION (plus grand) ═══════════════════════════ */
.jnl-edit-overlay {
    display: none; position: fixed; inset: 0; z-index: 9999;
    align-items: flex-start; justify-content: center;
    background: rgba(0,0,0,.5); backdrop-filter: blur(3px);
    overflow-y: auto; padding: 24px 16px;
}
.jnl-edit-overlay.active { display: flex; }
.jnl-edit-box {
    background: var(--surface,#fff); border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.2);
    width: 100%; max-width: 580px; overflow: hidden;
    transform: scale(.96) translateY(10px);
    transition: transform .2s cubic-bezier(.34,1.56,.64,1), opacity .15s;
    opacity: 0; margin: auto;
}
.jnl-edit-overlay.active .jnl-edit-box { transform: scale(1) translateY(0); opacity: 1; }
.jnl-edit-head {
    padding: 20px 24px 18px; border-bottom: 1px solid var(--border,#e5e7eb);
    display: flex; align-items: center; justify-content: space-between;
    background: linear-gradient(135deg, rgba(139,92,246,.06), rgba(99,102,241,.03));
    position: relative; overflow: hidden;
}
.jnl-edit-head::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg,#8b5cf6,#6366f1,#3b82f6); }
.jnl-edit-head h3 { font-family: var(--font-display,inherit); font-size: 1rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; color: var(--text,#111827); }
.jnl-edit-head h3 i { color: #8b5cf6; }
.jnl-edit-close { background: none; border: none; font-size: 1rem; cursor: pointer; color: var(--text-3,#9ca3af); width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: all .15s; }
.jnl-edit-close:hover { background: var(--surface-2,#f9fafb); color: var(--text,#111827); }
.jnl-edit-body { padding: 22px 24px; }
.jnl-edit-row { margin-bottom: 14px; }
.jnl-edit-row label { display: block; font-size: .72rem; font-weight: 700; color: var(--text-2,#6b7280); margin-bottom: 4px; text-transform: uppercase; letter-spacing: .04em; }
.jnl-edit-row input, .jnl-edit-row select, .jnl-edit-row textarea {
    width: 100%; padding: 9px 12px; border: 1px solid var(--border,#e5e7eb);
    border-radius: var(--radius,10px); font-size: .88rem; font-family: var(--font,inherit);
    background: var(--surface,#fff); color: var(--text,#111827);
    transition: border-color .2s; box-sizing: border-box;
}
.jnl-edit-row input:focus, .jnl-edit-row select:focus, .jnl-edit-row textarea:focus {
    outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,.1);
}
.jnl-edit-row textarea { resize: vertical; min-height: 70px; }
.jnl-edit-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.jnl-edit-foot { display: flex; gap: 8px; justify-content: flex-end; padding: 16px 24px 20px; border-top: 1px solid var(--border,#e5e7eb); }

/* ══ TOAST (unifié) ═══════════════════════════════════════ */
.jnl-toast-wrap {
    position: fixed; bottom: 24px; right: 24px; z-index: 10000;
    display: flex; flex-direction: column; gap: 8px; pointer-events: none;
}
.jnl-toast-item {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 18px; border-radius: 12px; font-size: .83rem; font-weight: 600;
    background: #fff; border: 1px solid #e5e7eb; color: #111827;
    box-shadow: 0 8px 24px rgba(0,0,0,.12);
    transform: translateY(20px); opacity: 0;
    transition: all .25s; pointer-events: auto;
}
.jnl-toast-item.visible { transform: translateY(0); opacity: 1; }
.jnl-toast-icon { width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 800; flex-shrink: 0; }

@media(max-width:1100px) { .jnl-gen-wrap { grid-template-columns: 1fr; } .jnl-perf-stats { grid-template-columns: 1fr 1fr; } }
@media(max-width:900px)  { .jnl-ch-grid { grid-template-columns: repeat(3,1fr); } }
@media(max-width:768px)  { .jnl-banner { flex-direction: column; gap: 18px; align-items: flex-start; } .jnl-ch-grid { grid-template-columns: 1fr 1fr; } .jnl-edit-grid { grid-template-columns: 1fr; } }
</style>

<div class="jnl" id="jnlRoot">

<!-- ══ BANNER ══ -->
<div class="jnl-banner">
    <div class="jnl-banner-l">
        <h2><i class="fas fa-newspaper"></i> Stratégie Contenu</h2>
        <p>Journal éditorial multi-canal — Semaine <strong><?= $currentWeek['week'] ?></strong> · <?= $currentWeek['year'] ?></p>
        <div class="jnl-canal-pills">
            <?php foreach ($canaux as $id => $ch): ?>
            <span class="jnl-canal-pill" style="background:<?= $ch['color'] ?>20;color:<?= $ch['color'] ?>;border-color:<?= $ch['color'] ?>30">
                <i class="<?= $ch['icon'] ?>" style="font-size:.6rem"></i> <?= $ch['label'] ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="jnl-stats">
        <div class="jnl-stat"><div class="num violet"><?= $total ?></div><div class="lbl">Total</div></div>
        <div class="jnl-stat"><div class="num amber"><?= $idees ?></div><div class="lbl">Idées</div></div>
        <div class="jnl-stat"><div class="num blue"><?= $enCours ?></div><div class="lbl">En cours</div></div>
        <div class="jnl-stat"><div class="num blue"><?= $prets ?></div><div class="lbl">Prêts</div></div>
        <div class="jnl-stat"><div class="num green"><?= $publies ?></div><div class="lbl">Publiés</div></div>
    </div>
</div>

<!-- ══ ONGLETS ══ -->
<div class="jnl-tabs">
    <button class="jnl-tab <?= $tab==='global'     ?'active':'' ?>" onclick="JNL.switchTab('global')">
        <i class="fas fa-list-ul"></i> Vue Globale <span class="cnt"><?= $total ?></span>
    </button>
    <button class="jnl-tab <?= $tab==='matrice'    ?'active':'' ?>" onclick="JNL.switchTab('matrice')">
        <i class="fas fa-border-all"></i> Matrice Stratégique
    </button>
    <button class="jnl-tab <?= $tab==='generate'   ?'active':'' ?>" onclick="JNL.switchTab('generate')">
        <i class="fas fa-wand-magic-sparkles"></i> Générateur IA
    </button>
    <button class="jnl-tab <?= $tab==='performance'?'active':'' ?>" onclick="JNL.switchTab('performance')">
        <i class="fas fa-chart-bar"></i> Performance
    </button>
</div>

<!-- ══════════════════════════════════════════════════
     ONGLET 1 — VUE GLOBALE
══════════════════════════════════════════════════ -->
<div class="jnl-panel <?= $tab==='global'?'active':'' ?>" id="jnl-panel-global">

    <!-- Cards canal -->
    <div class="jnl-ch-grid">
        <?php foreach ($canaux as $chId => $ch):
            $cs      = $channelStatsMap[$chId] ?? [];
            $chTotal = max((int)($cs['total'] ?? 0), 1);
            $chPub   = (int)($cs['published'] ?? 0);
            $chPct   = round($chPub / $chTotal * 100);
            $chActifs= (int)($cs['ideas']??0) + (int)($cs['planned']??0);
            $chWIP   = (int)($cs['validated']??0) + (int)($cs['writing']??0) + (int)($cs['ready']??0);
            $journalLinks = ['blog'=>'articles-journal','gmb'=>'local-gmb-journal','facebook'=>'facebook-journal','instagram'=>'instagram-journal','tiktok'=>'tiktok-journal','linkedin'=>'linkedin-journal','email'=>'emails-journal'];
        ?>
        <a class="jnl-ch-card" href="?page=<?= $journalLinks[$chId] ?? 'journal' ?>"
           style="border-color: var(--border); --ch:<?= $ch['color'] ?>"
           onmouseover="this.style.borderColor='<?= $ch['color'] ?>'"
           onmouseout="this.style.borderColor=''">
            <div class="jnl-ch-card-bar" style="background:<?= $ch['color'] ?>"></div>
            <div class="jnl-ch-card-head">
                <i class="<?= $ch['icon'] ?>" style="color:<?= $ch['color'] ?>"></i>
                <span><?= $ch['label'] ?></span>
            </div>
            <div class="jnl-ch-nums">
                <div class="jnl-ch-num"><div class="v" style="color:<?= $ch['color'] ?>"><?= $chActifs ?></div><div class="l">Idées</div></div>
                <div class="jnl-ch-num"><div class="v" style="color:#6366f1"><?= $chWIP ?></div><div class="l">WIP</div></div>
                <div class="jnl-ch-num"><div class="v" style="color:#059669"><?= $chPub ?></div><div class="l">Pub.</div></div>
            </div>
            <div class="jnl-ch-track"><div class="jnl-ch-fill" style="width:<?= $chPct ?>%;background:<?= $ch['color'] ?>"></div></div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Filtres canaux -->
    <div class="jnl-canal-filters">
        <a href="<?= jUrl(['canal'=>null,'p'=>null]) ?>" class="jnl-cf-pill <?= $filterCanal==='all'?'active':'' ?>"
           style="<?= $filterCanal==='all'?'background:#374151;':'' ?>">
            <i class="fas fa-layer-group"></i> Tous <span class="cnt"><?= $total ?></span>
        </a>
        <?php foreach ($canaux as $chId => $ch):
            $cnt = (int)($channelStatsMap[$chId]['total'] ?? 0);
            $isA = $filterCanal === $chId;
        ?>
        <a href="<?= jUrl(['canal'=>$chId,'p'=>null]) ?>" class="jnl-cf-pill <?= $isA?'active':'' ?>"
           style="<?= $isA?"background:{$ch['color']};":'' ?>">
            <i class="<?= $ch['icon'] ?>"></i> <?= $ch['label'] ?> <span class="cnt"><?= $cnt ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Toolbar -->
    <div class="jnl-toolbar">
        <div class="jnl-toolbar-l">
            <div class="jnl-filters-bar">
                <?php foreach ([
                    'all'       => ['fa-layer-group', 'Tous',    $total],
                    'idea'      => ['fa-lightbulb',   'Idées',   $idees],
                    'validated' => ['fa-check-circle','Validés', $enCours],
                    'published' => ['fa-rocket',      'Publiés', $publies],
                ] as $k => [$icon,$label,$cnt]):
                    $url = jUrl(['status'=>$k==='all'?null:$k,'p'=>null]);
                ?>
                <a href="<?= $url ?>" class="jnl-fbtn <?= $filterStatus===$k?'active':'' ?>">
                    <i class="fas <?= $icon ?>"></i> <?= $label ?> <span class="badge"><?= (int)$cnt ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if ($filterCanal!=='all' || $filterStatus!=='all' || $filterSem || $searchQ): ?>
            <a href="?page=journal" class="jnl-reset"><i class="fas fa-times"></i> Réinitialiser</a>
            <?php endif; ?>

            <form method="GET" style="display:flex;gap:6px;align-items:center">
                <input type="hidden" name="page" value="journal">
                <input type="hidden" name="tab"  value="global">
                <?php if ($filterCanal!=='all'):  ?><input type="hidden" name="canal"  value="<?= htmlspecialchars($filterCanal) ?>"><?php endif; ?>
                <?php if ($filterStatus!=='all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
                <select name="sem" class="jnl-sem-select" onchange="this.form.submit()">
                    <option value="0">Toutes semaines</option>
                    <?php for ($w=1;$w<=52;$w++): ?>
                    <option value="<?= $w ?>" <?= $filterSem===$w?'selected':'' ?>>S<?= $w ?><?= $w===$currentWeek['week']?' ★':'' ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>

        <div class="jnl-toolbar-r">
            <form class="jnl-search" method="GET">
                <input type="hidden" name="page" value="journal">
                <input type="hidden" name="tab"  value="global">
                <?php if ($filterCanal!=='all'): ?><input type="hidden" name="canal" value="<?= htmlspecialchars($filterCanal) ?>"><?php endif; ?>
                <i class="fas fa-search"></i>
                <input type="text" name="q" placeholder="Rechercher une idée…" value="<?= htmlspecialchars($searchQ) ?>">
            </form>
            <button class="jnl-btn jnl-btn-primary" onclick="JNL.openEditModal(0)">
                <i class="fas fa-plus"></i> Nouvelle idée
            </button>
        </div>
    </div>

    <!-- Bulk bar -->
    <div class="jnl-bulk" id="jnlBulkBar">
        <input type="checkbox" id="jnlSelAll" onchange="JNL.toggleAll(this.checked)">
        <span id="jnlBulkCnt">0</span> sélectionnée(s)
        <select id="jnlBulkAct">
            <option value="">— Action —</option>
            <option value="validate">Valider</option>
            <option value="publish">Publier</option>
            <option value="reject">Rejeter</option>
            <option value="delete">Supprimer</option>
        </select>
        <button class="jnl-btn jnl-btn-sm jnl-btn-outline" onclick="JNL.bulkExec()"><i class="fas fa-check"></i> Appliquer</button>
    </div>

    <!-- Table -->
    <div class="jnl-table-wrap">
    <?php if (empty($allItems)): ?>
        <div class="jnl-empty">
            <i class="fas fa-newspaper"></i>
            <h3><?= $searchQ||$filterCanal!=='all'||$filterStatus!=='all' ? 'Aucun résultat' : 'Aucune idée dans le journal' ?></h3>
            <?php if ($searchQ): ?>
            <p>Aucun résultat pour «&nbsp;<?= htmlspecialchars($searchQ) ?>&nbsp;». <a href="?page=journal" style="color:#8b5cf6">Effacer</a></p>
            <?php else: ?>
            <p>Utilisez le <button class="jnl-link-btn" onclick="JNL.switchTab('generate')">Générateur IA</button> pour démarrer votre stratégie.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table class="jnl-table">
            <thead><tr>
                <th style="width:32px"><input type="checkbox" onchange="JNL.toggleAll(this.checked)"></th>
                <th style="width:46px">Sem.</th>
                <th style="width:36px">Canal</th>
                <th>Titre / Contenu</th>
                <th style="width:90px">Profil</th>
                <th style="width:90px">Conscience</th>
                <th style="width:80px">Type</th>
                <th style="width:80px">Objectif</th>
                <th style="width:90px">Statut</th>
                <th style="text-align:right">Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($allItems as $item):
                $chInfo   = $canaux[$item['channel_id']] ?? ['icon'=>'fa-file','color'=>'#999','label'=>'?'];
                $profile  = JournalController::PROFILES[$item['profile_id']]         ?? ['label'=>$item['profile_id']??'?','color'=>'#999'];
                $awareness= JournalController::AWARENESS[$item['awareness_level']]   ?? ['short'=>'?','color'=>'#999'];
                $statusI  = JournalController::STATUSES[$item['status']]             ?? ['label'=>'?','color'=>'#999','icon'=>'fa-circle'];
                $typeL    = JournalController::CONTENT_TYPES[$item['content_type']]  ?? $item['content_type'] ?? '—';
                $objI     = JournalController::OBJECTIVES[$item['objective_id']]     ?? ['label'=>$item['objective_id']??'—'];
                $isCurWeek= (int)$item['week_number'] === $currentWeek['week'] && (int)$item['year'] === $currentWeek['year'];
                $createUrl= $jCtrl->getCreateContentUrl($item);
            ?>
            <tr class="<?= $isCurWeek?'jnl-row-curweek':'' ?>" data-id="<?= (int)$item['id'] ?>">
                <td><input type="checkbox" class="jnl-cb" value="<?= (int)$item['id'] ?>" onchange="JNL.updateBulk()"></td>
                <td><span class="jnl-badge-week <?= $isCurWeek?'cur':'' ?>">S<?= (int)$item['week_number'] ?></span></td>
                <td>
                    <div class="jnl-canal-icon" style="background:<?= $chInfo['color'] ?>20;color:<?= $chInfo['color'] ?>" title="<?= htmlspecialchars($chInfo['label']) ?>">
                        <i class="<?= $chInfo['icon'] ?>"></i>
                    </div>
                </td>
                <td class="jnl-titre">
                    <a href="javascript:void(0)" onclick="JNL.openEditModal(<?= (int)$item['id'] ?>)">
                        <?= htmlspecialchars($item['title'] ?? '—') ?>
                    </a>
                    <?php if (!empty($item['sector_id'])): ?>
                    <div class="jnl-meta"><i class="fas fa-map-pin" style="font-size:.55rem"></i> <?= htmlspecialchars($item['sector_id']) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="jnl-badge" style="background:<?= $profile['color'] ?>;font-size:.62rem">
                        <?= htmlspecialchars($profile['label']) ?>
                    </span>
                </td>
                <td>
                    <span class="jnl-badge-outline" style="color:<?= $awareness['color'] ?>;border-color:<?= $awareness['color'] ?>">
                        <?= htmlspecialchars($awareness['short'] ?? '?') ?>
                    </span>
                </td>
                <td><span class="jnl-badge-type"><?= htmlspecialchars($typeL) ?></span></td>
                <td style="font-size:.72rem;color:var(--text-2)"><?= htmlspecialchars($objI['label'] ?? '—') ?></td>
                <td>
                    <span class="jnl-badge" style="background:<?= $statusI['color'] ?>">
                        <i class="fas <?= $statusI['icon'] ?>" style="font-size:.6rem"></i> <?= $statusI['label'] ?>
                    </span>
                </td>
                <td>
                    <div class="jnl-actions">
                        <?php if (in_array($item['status'], ['idea','planned'])): ?>
                        <button class="btn-ok" onclick="JNL.setStatus(<?= (int)$item['id'] ?>,'validated')" title="Valider"><i class="fas fa-check"></i></button>
                        <?php endif; ?>
                        <?php if (in_array($item['status'],['validated','writing','ready']) && $createUrl): ?>
                        <a href="<?= htmlspecialchars($createUrl) ?>" class="btn-go" title="Créer le contenu"><i class="fas fa-arrow-right"></i></a>
                        <?php endif; ?>
                        <?php if ($item['status']==='ready'): ?>
                        <button class="btn-ok" onclick="JNL.setStatus(<?= (int)$item['id'] ?>,'published')" title="Marquer publié"><i class="fas fa-rocket"></i></button>
                        <?php endif; ?>
                        <button onclick="JNL.openEditModal(<?= (int)$item['id'] ?>)" title="Modifier"><i class="fas fa-pen"></i></button>
                        <button class="btn-del" onclick="JNL.deleteItem(<?= (int)$item['id'] ?>, '<?= addslashes(htmlspecialchars($item['title'] ?? '')) ?>')" title="Supprimer"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($totalPages > 1): ?>
        <div class="jnl-pagination">
            <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalItems) ?> sur <strong><?= $totalItems ?></strong></span>
            <div style="display:flex;gap:4px">
                <?php for ($i=1;$i<=$totalPages;$i++): ?>
                <a href="<?= jUrl(['p'=>$i]) ?>" class="<?= $i===$currentPage?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     ONGLET 2 — MATRICE STRATÉGIQUE
══════════════════════════════════════════════════ -->
<div class="jnl-panel <?= $tab==='matrice'?'active':'' ?>" id="jnl-panel-matrice">
    <p style="font-size:.85rem;color:var(--text-2);margin-bottom:18px">
        <i class="fas fa-info-circle" style="color:#8b5cf6"></i>
        Identifiez les zones non couvertes de votre stratégie. Cases vides = opportunités manquées.
    </p>
    <div class="jnl-matrix-wrap">
        <table class="jnl-matrix">
            <thead><tr>
                <th class="left">Profil</th>
                <?php foreach (JournalController::AWARENESS as $aKey => $aInfo): ?>
                <th>
                    <span style="color:<?= $aInfo['color'] ?>"><?= $aInfo['short'] ?></span><br>
                    <span style="font-size:.6rem;font-weight:400;opacity:.7">Niv.<?= $aInfo['step'] ?></span>
                </th>
                <?php endforeach; ?>
                <th>Total</th>
            </tr></thead>
            <tbody>
            <?php foreach (JournalController::PROFILES as $pKey => $pInfo):
                $rowTotal = 0;
            ?>
            <tr>
                <td class="profile"><span style="color:<?= $pInfo['color'] ?>">●</span> <?= $pInfo['label'] ?></td>
                <?php foreach (JournalController::AWARENESS as $aKey => $aInfo):
                    $cell = $matrixData[$pKey][$aKey] ?? ['cnt'=>0,'published'=>0];
                    $cnt  = (int)$cell['cnt'];
                    $pub  = (int)$cell['published'];
                    $rowTotal += $cnt;
                    $cls  = $cnt===0?'empty':($cnt<=2?'low':($cnt<=5?'ok':'good'));
                ?>
                <td>
                    <div class="jnl-matrix-cell">
                        <span class="jnl-matrix-cnt <?= $cls ?>"><?= $cnt===0?'—':$cnt ?></span>
                        <?php if ($pub > 0): ?><span class="jnl-matrix-pub"><?= $pub ?> pub.</span><?php endif; ?>
                    </div>
                </td>
                <?php endforeach; ?>
                <td style="font-weight:800;font-family:var(--font-display)"><?= $rowTotal ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="jnl-matrix-legend">
        <span>— = manquant</span>
        <span style="color:#f59e0b">1-2 = à renforcer</span>
        <span style="color:#3b82f6">3-5 = correct</span>
        <span style="color:#10b981">6+ = bien couvert</span>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     ONGLET 3 — GÉNÉRATEUR IA
══════════════════════════════════════════════════ -->
<div class="jnl-panel <?= $tab==='generate'?'active':'' ?>" id="jnl-panel-generate">
    <div class="jnl-gen-wrap">
        <div class="jnl-gen-form">
            <h3><i class="fas fa-wand-magic-sparkles"></i> Paramètres de génération</h3>
            <div class="jnl-gen-row">
                <label>Canal</label>
                <select id="jnlGenCanal">
                    <option value="">Tous les canaux</option>
                    <?php foreach ($canaux as $chId => $ch): ?>
                    <option value="<?= $chId ?>"><?= $ch['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="jnl-gen-row">
                <label>Durée</label>
                <select id="jnlGenWeeks">
                    <option value="2">2 semaines</option>
                    <option value="4" selected>4 semaines (recommandé)</option>
                    <option value="8">8 semaines</option>
                    <option value="12">3 mois</option>
                </select>
            </div>
            <div class="jnl-gen-row">
                <label>Personas cibles</label>
                <div class="jnl-gen-personas" id="jnlGenPersonas">
                    <?php foreach (JournalController::PROFILES as $pKey => $pInfo): ?>
                    <label class="jnl-gen-persona selected">
                        <input type="checkbox" value="<?= $pKey ?>" checked>
                        <div class="p-avatar" style="background:<?= $pInfo['color'] ?? '#8b5cf6' ?>"><i class="fas fa-user" style="font-size:.7rem"></i></div>
                        <div>
                            <div class="p-name"><?= $pInfo['label'] ?></div>
                            <div class="p-sub"><?= $pInfo['desc'] ?? '' ?></div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="margin-top:20px">
                <button class="jnl-btn jnl-btn-primary" style="width:100%;justify-content:center" id="jnlGenBtn" onclick="JNL.generate()">
                    <i class="fas fa-wand-magic-sparkles"></i> Générer les idées
                </button>
            </div>
            <div id="jnlGenStatus" class="jnl-gen-status"></div>
        </div>

        <div class="jnl-gen-result-area">
            <h3><i class="fas fa-lightbulb" style="color:#f59e0b"></i> Idées générées</h3>
            <div id="jnlGenResults">
                <div class="jnl-empty">
                    <i class="fas fa-wand-magic-sparkles"></i>
                    <h3>Prêt à générer</h3>
                    <p>Sélectionnez vos paramètres et lancez la génération.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════
     ONGLET 4 — PERFORMANCE
══════════════════════════════════════════════════ -->
<div class="jnl-panel <?= $tab==='performance'?'active':'' ?>" id="jnl-panel-performance">
    <?php
    $totalAll = max($total, 1);
    $pctPub   = round($publies / $totalAll * 100);
    $pctRdy   = round($prets   / $totalAll * 100);
    $pctWIP   = round($enCours / $totalAll * 100);
    $pctIdee  = max(0, 100 - $pctPub - $pctRdy - $pctWIP);
    ?>
    <div class="jnl-perf-stats">
        <div class="jnl-perf-stat"><div class="v" style="color:#8b5cf6"><?= $total ?></div><div class="l">Total idées</div></div>
        <div class="jnl-perf-stat"><div class="v" style="color:#059669"><?= $publies ?></div><div class="l">Publiés</div></div>
        <div class="jnl-perf-stat"><div class="v" style="color:#3b82f6"><?= $pctPub ?>%</div><div class="l">Taux publication</div></div>
        <div class="jnl-perf-stat"><div class="v" style="color:#f59e0b"><?= $prets ?></div><div class="l">Prêts à publier</div></div>
    </div>
    <div style="margin-bottom:20px">
        <div style="font-size:.88rem;font-weight:700;margin-bottom:8px;display:flex;align-items:center;gap:6px"><i class="fas fa-stream" style="color:#8b5cf6;font-size:.8rem"></i> Pipeline éditorial</div>
        <div class="jnl-pipeline">
            <?php if ($pctIdee>0): ?><div class="jnl-pipeline-seg" style="width:<?= $pctIdee ?>%;background:#94a3b8"><?= $pctIdee>8?$pctIdee.'% Idées':'' ?></div><?php endif; ?>
            <?php if ($pctWIP >0): ?><div class="jnl-pipeline-seg" style="width:<?= $pctWIP ?>%;background:#8b5cf6"><?= $pctWIP >8?$pctWIP.'% WIP':'' ?></div><?php endif; ?>
            <?php if ($pctRdy >0): ?><div class="jnl-pipeline-seg" style="width:<?= $pctRdy ?>%;background:#3b82f6"><?= $pctRdy >8?$pctRdy.'% Prêts':'' ?></div><?php endif; ?>
            <?php if ($pctPub >0): ?><div class="jnl-pipeline-seg" style="width:<?= $pctPub ?>%;background:#059669"><?= $pctPub >8?$pctPub.'% Pub.':'' ?></div><?php endif; ?>
        </div>
        <div class="jnl-pipeline-legend">
            <span style="color:#94a3b8">● Idées</span>
            <span style="color:#8b5cf6">● En cours</span>
            <span style="color:#3b82f6">● Prêts</span>
            <span style="color:#059669">● Publiés</span>
        </div>
    </div>
    <div style="font-size:.88rem;font-weight:700;margin-bottom:10px;display:flex;align-items:center;gap:6px"><i class="fas fa-chart-bar" style="color:#8b5cf6;font-size:.8rem"></i> Détail par canal</div>
    <div class="jnl-perf-ch-table">
        <table>
            <thead><tr>
                <th>Canal</th><th>Idées</th><th>En cours</th><th>Prêts</th><th>Publiés</th><th>Total</th><th>Taux</th>
            </tr></thead>
            <tbody>
            <?php foreach ($canaux as $chId => $ch):
                $cs    = $channelStatsMap[$chId] ?? [];
                $chTot = max((int)($cs['total'] ?? 0), 1);
                $chPub = (int)($cs['published'] ?? 0);
                $pct2  = round($chPub / $chTot * 100);
            ?>
            <tr>
                <td><i class="<?= $ch['icon'] ?>" style="color:<?= $ch['color'] ?>;margin-right:6px"></i><?= $ch['label'] ?></td>
                <td><?= (int)($cs['ideas']??0)+(int)($cs['planned']??0) ?></td>
                <td><?= (int)($cs['validated']??0)+(int)($cs['writing']??0) ?></td>
                <td><?= (int)($cs['ready']??0) ?></td>
                <td style="font-weight:700;color:#059669"><?= $chPub ?></td>
                <td><?= (int)($cs['total']??0) ?></td>
                <td>
                    <div class="jnl-bar-inline">
                        <div class="jnl-bar-track"><div class="jnl-bar-fill" style="width:<?= $pct2 ?>%;background:<?= $ch['color'] ?>"></div></div>
                        <span style="font-size:.72rem;font-weight:700"><?= $pct2 ?>%</span>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</div><!-- /jnlRoot -->

<!-- ══ MODAL CONFIRMATION (suppression) ══════════════════ -->
<div class="jnl-modal-overlay" id="jnlConfirmOverlay" onclick="if(event.target===this)JNL.closeConfirm()">
    <div class="jnl-modal-box">
        <div class="jnl-modal-header" id="jnlConfirmHeader">
            <div class="jnl-modal-icon" id="jnlConfirmIcon"></div>
            <div>
                <div class="jnl-modal-title" id="jnlConfirmTitle"></div>
                <div class="jnl-modal-msg"   id="jnlConfirmMsg"></div>
            </div>
        </div>
        <div class="jnl-modal-footer">
            <button class="jnl-modal-cancel"  onclick="JNL.closeConfirm()">Annuler</button>
            <button class="jnl-modal-confirm" id="jnlConfirmBtn"></button>
        </div>
    </div>
</div>

<!-- ══ MODAL ÉDITION IDÉE ════════════════════════════════ -->
<div class="jnl-edit-overlay" id="jnlEditOverlay" onclick="if(event.target===this)JNL.closeEditModal()">
    <div class="jnl-edit-box">
        <div class="jnl-edit-head">
            <h3><i class="fas fa-newspaper"></i> <span id="jnlEditModalTitle">Nouvelle idée</span></h3>
            <button class="jnl-edit-close" onclick="JNL.closeEditModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="jnl-edit-body">
            <input type="hidden" id="jnlEditId" value="0">

            <div class="jnl-edit-row">
                <label>Titre *</label>
                <input type="text" id="jnlEf_titre" placeholder="Titre de l'idée ou du contenu…">
            </div>
            <div class="jnl-edit-row">
                <label>Description</label>
                <textarea id="jnlEf_description" rows="2" placeholder="Brief, angle éditorial, idées clés…"></textarea>
            </div>

            <div class="jnl-edit-grid">
                <div class="jnl-edit-row">
                    <label>Canal</label>
                    <select id="jnlEf_canal">
                        <?php foreach ($canaux as $chId => $ch): ?>
                        <option value="<?= $chId ?>"><?= $ch['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="jnl-edit-row">
                    <label>Statut</label>
                    <select id="jnlEf_status">
                        <?php foreach ($statuts as $sKey => $sInfo): ?>
                        <option value="<?= $sKey ?>"><?= $sInfo['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="jnl-edit-row">
                    <label>Persona</label>
                    <select id="jnlEf_persona">
                        <?php foreach (JournalController::PROFILES as $pKey => $pInfo): ?>
                        <option value="<?= $pKey ?>"><?= $pInfo['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="jnl-edit-row">
                    <label>Niveau conscience</label>
                    <select id="jnlEf_conscience">
                        <?php foreach (JournalController::AWARENESS as $aKey => $aInfo): ?>
                        <option value="<?= $aKey ?>"><?= $aInfo['short'] ?> — <?= $aInfo['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="jnl-edit-row">
                    <label>Type contenu</label>
                    <select id="jnlEf_type">
                        <?php foreach (JournalController::CONTENT_TYPES as $tKey => $tLabel): ?>
                        <option value="<?= $tKey ?>"><?= $tLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="jnl-edit-row">
                    <label>Objectif</label>
                    <select id="jnlEf_objectif">
                        <?php foreach (JournalController::OBJECTIVES as $oKey => $oInfo): ?>
                        <option value="<?= $oKey ?>"><?= $oInfo['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="jnl-edit-row">
                <label>Date planifiée</label>
                <input type="date" id="jnlEf_date">
            </div>
            <div class="jnl-edit-row">
                <label>Notes internes</label>
                <textarea id="jnlEf_notes" rows="2" placeholder="Notes, sources, inspirations…"></textarea>
            </div>
        </div>
        <div class="jnl-edit-foot">
            <button class="jnl-btn jnl-btn-outline" onclick="JNL.closeEditModal()">Annuler</button>
            <button class="jnl-btn jnl-btn-primary" onclick="JNL.saveEdit()"><i class="fas fa-save"></i> Enregistrer</button>
        </div>
    </div>
</div>

<!-- ══ TOAST CONTAINER ══ -->
<div class="jnl-toast-wrap" id="jnlToastWrap"></div>

<script>
const JNL = {
    API: '?page=journal&_ajax=1',
    _confirmCb: null,

    // ── POST ────────────────────────────────────────────────
    async _post(data) {
        const fd = new FormData();
        for (const [k,v] of Object.entries(data)) fd.append(k, typeof v === 'object' ? JSON.stringify(v) : v);
        fd.append('_ajax','1');
        const r = await fetch(window.location.href, {
            method: 'POST',
            headers: {'X-Requested-With':'XMLHttpRequest'},
            body: fd
        });
        return r.json();
    },

    // ── Toast ────────────────────────────────────────────────
    toast(msg, type = 'success') {
        const colors = {success:'#059669', error:'#dc2626', info:'#3b82f6', warning:'#d97706'};
        const icons  = {success:'✓', error:'✕', info:'ℹ', warning:'!'};
        const wrap   = document.getElementById('jnlToastWrap');
        const t      = document.createElement('div');
        t.className  = 'jnl-toast-item';
        t.innerHTML  = `<span class="jnl-toast-icon" style="background:${colors[type]}22;color:${colors[type]}">${icons[type]}</span>${msg}`;
        wrap.appendChild(t);
        requestAnimationFrame(() => t.classList.add('visible'));
        setTimeout(() => {
            t.classList.remove('visible');
            setTimeout(() => t.remove(), 280);
        }, 3500);
    },

    // ── Modal confirmation ───────────────────────────────────
    confirm({ icon, iconBg, iconColor, title, msg, label, color, onConfirm }) {
        const ovl  = document.getElementById('jnlConfirmOverlay');
        const box  = ovl.querySelector('.jnl-modal-box');
        const head = document.getElementById('jnlConfirmHeader');
        document.getElementById('jnlConfirmIcon').innerHTML = icon;
        document.getElementById('jnlConfirmIcon').style.background = iconBg;
        document.getElementById('jnlConfirmIcon').style.color = iconColor;
        head.style.background = iconBg + '33';
        document.getElementById('jnlConfirmTitle').textContent = title;
        document.getElementById('jnlConfirmMsg').innerHTML = msg;
        const btn = document.getElementById('jnlConfirmBtn');
        btn.textContent      = label || 'Confirmer';
        btn.style.background = color || '#8b5cf6';
        btn.onmouseover = () => btn.style.filter = 'brightness(.88)';
        btn.onmouseout  = () => btn.style.filter = '';
        this._confirmCb = onConfirm;
        btn.onclick = () => { this.closeConfirm(); if (this._confirmCb) this._confirmCb(); };
        ovl.classList.add('active');
        document.addEventListener('keydown', this._escConfirm);
    },
    closeConfirm() {
        document.getElementById('jnlConfirmOverlay').classList.remove('active');
        document.removeEventListener('keydown', this._escConfirm);
    },
    _escConfirm(e) { if (e.key === 'Escape') JNL.closeConfirm(); },

    // ── Onglets ──────────────────────────────────────────────
    switchTab(tab) {
        document.querySelectorAll('.jnl-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.jnl-tab').forEach(b => b.classList.remove('active'));
        document.getElementById('jnl-panel-' + tab)?.classList.add('active');
        document.querySelector(`.jnl-tab[onclick*="'${tab}'"]`)?.classList.add('active');
        const url = new URL(window.location);
        url.searchParams.set('tab', tab); url.searchParams.delete('p');
        history.replaceState(null, '', url);
    },

    // ── Bulk ─────────────────────────────────────────────────
    toggleAll(c) {
        document.querySelectorAll('.jnl-cb').forEach(cb => cb.checked = c);
        this.updateBulk();
    },
    updateBulk() {
        const n = document.querySelectorAll('.jnl-cb:checked').length;
        document.getElementById('jnlBulkCnt').textContent = n;
        document.getElementById('jnlBulkBar').classList.toggle('active', n > 0);
    },
    async bulkExec() {
        const act = document.getElementById('jnlBulkAct').value;
        if (!act) return;
        const ids = [...document.querySelectorAll('.jnl-cb:checked')].map(c => +c.value);
        if (!ids.length) return;
        if (act === 'delete') {
            this.confirm({
                icon: '<i class="fas fa-trash"></i>', iconBg: '#fef2f2', iconColor: '#dc2626',
                title: `Supprimer ${ids.length} idée(s) ?`,
                msg: 'Cette action est irréversible.',
                label: 'Supprimer', color: '#dc2626',
                onConfirm: async () => {
                    const d = await this._post({action:'bulk', op:'delete', ids});
                    d.success ? location.reload() : this.toast(d.error||'Erreur','error');
                }
            });
            return;
        }
        const opMap = {validate:'validated', publish:'published', reject:'rejete'};
        const d = await this._post({action:'bulk', op:'status_'+opMap[act], ids});
        d.success ? location.reload() : this.toast(d.error||'Erreur','error');
    },

    // ── Statut rapide ─────────────────────────────────────────
    async setStatus(id, status) {
        const d = await this._post({action:'change_status', id, status});
        if (d.success) {
            this.toast('Statut mis à jour', 'success');
            setTimeout(() => location.reload(), 800);
        } else { this.toast(d.error||'Erreur','error'); }
    },

    // ── Suppression ──────────────────────────────────────────
    deleteItem(id, title) {
        this.confirm({
            icon: '<i class="fas fa-trash"></i>', iconBg: '#fef2f2', iconColor: '#dc2626',
            title: 'Supprimer cette idée ?',
            msg: `<strong>${title}</strong> sera supprimée définitivement.<br><span style="font-size:.78rem;color:#9ca3af">Cette action est irréversible.</span>`,
            label: 'Supprimer', color: '#dc2626',
            onConfirm: async () => {
                const d = await this._post({action:'delete', id});
                if (d.success) {
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) { row.style.cssText='opacity:0;transform:scale(.98);transition:all .3s'; setTimeout(()=>row.remove(),300); }
                    this.toast('Idée supprimée','success');
                } else { this.toast(d.error||'Erreur','error'); }
            }
        });
    },

    // ── Modal édition ────────────────────────────────────────
    openEditModal(id) {
        const ovl = document.getElementById('jnlEditOverlay');
        document.getElementById('jnlEditId').value = id;
        document.getElementById('jnlEditModalTitle').textContent = id > 0 ? 'Modifier l\'idée' : 'Nouvelle idée';
        if (id === 0) {
            ['jnlEf_titre','jnlEf_description','jnlEf_notes'].forEach(i => { const el=document.getElementById(i); if(el) el.value=''; });
            const dateEl = document.getElementById('jnlEf_date');
            if (dateEl) dateEl.value = new Date().toISOString().split('T')[0];
        } else {
            this._post({action:'get_item', id}).then(d => {
                if (!d.success) return;
                const it = d.data;
                const m = {
                    jnlEf_titre:       it.titre || it.title,
                    jnlEf_description: it.description,
                    jnlEf_notes:       it.notes,
                    jnlEf_canal:       it.channel_id,
                    jnlEf_status:      it.status,
                    jnlEf_persona:     it.profile_id,
                    jnlEf_conscience:  it.awareness_level,
                    jnlEf_type:        it.content_type,
                    jnlEf_objectif:    it.objective_id,
                    jnlEf_date:        it.date_planifiee,
                };
                for (const [elId, val] of Object.entries(m)) {
                    const el = document.getElementById(elId);
                    if (el && val !== undefined && val !== null) el.value = val;
                }
            });
        }
        ovl.classList.add('active');
        document.addEventListener('keydown', this._escEdit);
    },
    closeEditModal() {
        document.getElementById('jnlEditOverlay').classList.remove('active');
        document.removeEventListener('keydown', this._escEdit);
    },
    _escEdit(e) { if (e.key === 'Escape') JNL.closeEditModal(); },

    async saveEdit() {
        const id = +(document.getElementById('jnlEditId').value || 0);
        const data = {
            action:             'save_idea',
            id:                 id || '',
            canal:              document.getElementById('jnlEf_canal')?.value,
            titre:              document.getElementById('jnlEf_titre')?.value,
            description:        document.getElementById('jnlEf_description')?.value,
            status:             document.getElementById('jnlEf_status')?.value,
            persona_cible:      document.getElementById('jnlEf_persona')?.value,
            niveau_conscience:  document.getElementById('jnlEf_conscience')?.value,
            type_contenu:       document.getElementById('jnlEf_type')?.value,
            objectif:           document.getElementById('jnlEf_objectif')?.value,
            notes:              document.getElementById('jnlEf_notes')?.value,
            date_planifiee:     document.getElementById('jnlEf_date')?.value,
        };
        const d = await this._post(data);
        if (d.success) {
            this.toast(id > 0 ? 'Idée mise à jour ✓' : 'Idée créée ✓', 'success');
            this.closeEditModal();
            setTimeout(() => location.reload(), 800);
        } else { this.toast(d.error||'Erreur','error'); }
    },

    // ── Générateur ────────────────────────────────────────────
    async generate() {
        const canal   = document.getElementById('jnlGenCanal').value;
        const weeks   = +document.getElementById('jnlGenWeeks').value;
        const selP    = [...document.querySelectorAll('#jnlGenPersonas input:checked')].map(i => i.value);
        const btn     = document.getElementById('jnlGenBtn');
        const status  = document.getElementById('jnlGenStatus');
        const results = document.getElementById('jnlGenResults');
        const chColors = <?= json_encode($chColors) ?>;
        const chIcons  = <?= json_encode($chIcons) ?>;
        const chLabels = <?= json_encode($chLabels) ?>;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération en cours…';
        status.className = 'jnl-gen-status loading';
        status.innerHTML = `<i class="fas fa-spinner fa-spin"></i> Génération pour ${weeks} semaines…`;
        results.innerHTML = '';

        const d = await this._post({action:'generate_ideas', canal: canal||'', weeks, personas: JSON.stringify(selP)});

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-wand-magic-sparkles"></i> Générer les idées';

        if (d.success) {
            status.className = 'jnl-gen-status ok';
            status.innerHTML = `<i class="fas fa-check-circle"></i> ${d.message || d.count + ' idée(s) créées !'}`;
            if (d.items?.length) {
                const html = d.items.slice(0,20).map(item => {
                    const color = chColors[item.channel_id] || '#8b5cf6';
                    const icon  = chIcons[item.channel_id]  || 'fa-file';
                    const label = chLabels[item.channel_id] || item.channel_id;
                    return `<div class="jnl-preview-item" id="jnl-prev-${item.id}">
                        <div class="jnl-preview-item-head">
                            <div class="jnl-preview-item-icon" style="background:${color}20;color:${color}"><i class="fas ${icon}"></i></div>
                            <span class="jnl-preview-item-title">${item.title}</span>
                            <span class="jnl-preview-item-ch" style="background:${color}20;color:${color}">${label}</span>
                        </div>
                        <div class="jnl-preview-item-meta">S${item.week_number} · ${item.profile_id||'—'} · ${item.awareness_level||'—'}</div>
                        <div class="jnl-preview-item-actions">
                            <button class="jnl-btn jnl-btn-sm jnl-btn-outline" onclick="JNL.setStatus(${item.id},'validated');document.getElementById('jnl-prev-${item.id}').classList.add('validated')">
                                <i class="fas fa-check"></i> Valider
                            </button>
                        </div>
                    </div>`;
                }).join('');
                results.innerHTML = html + (d.items.length > 20
                    ? `<p style="text-align:center;color:var(--text-3);font-size:.8rem;padding:10px">… et ${d.items.length-20} autres dans la Vue Globale.</p>`
                    : '');
            } else {
                results.innerHTML = `<div class="jnl-empty"><i class="fas fa-check-circle" style="color:#059669;opacity:1"></i><h3>Idées créées</h3><p>Retrouvez-les dans <button class="jnl-link-btn" onclick="JNL.switchTab('global')">Vue Globale</button></p></div>`;
            }
        } else {
            status.className = 'jnl-gen-status err';
            status.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${d.error||'Erreur lors de la génération'}`;
        }
    },
};

// ── Toggle persona checkbox (sans onclick dans le label) ──
document.querySelectorAll('.jnl-gen-persona').forEach(label => {
    label.addEventListener('click', e => {
        if (e.target.tagName === 'INPUT') return;
        const cb = label.querySelector('input[type="checkbox"]');
        cb.checked = !cb.checked;
        label.classList.toggle('selected', cb.checked);
    });
});

// ── Flash auto-dismiss ────────────────────────────────────
document.querySelectorAll('.jnl-flash').forEach(el => {
    setTimeout(() => { el.style.transition='opacity .4s'; el.style.opacity='0'; setTimeout(()=>el.remove(),400); }, 4000);
});
</script>