<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE COURTIERS PARTENAIRES v1.0
 * /admin/modules/immobilier/courtiers/index.php
 * Réseau de courtiers affiliés — recommandation & affiliation
 * Pattern identique pages v1.0 / articles v2.3
 * ══════════════════════════════════════════════════════════════
 */
if (!defined('ADMIN_ROUTER')) { http_response_code(403); exit('Accès refusé'); }
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

// ─── Install ───
if (($_GET['run'] ?? '') === 'install') {
    require __DIR__ . '/install.php';
    return;
}

// ─── Routing interne ───
$routeAction = $_GET['action'] ?? '';
if (in_array($routeAction, ['edit', 'create'])) {
    $editFile = __DIR__ . '/edit.php';
    if (file_exists($editFile)) { require $editFile; return; }
}

// ─── Vérif tables ───
$tableExists = false;
$contactsExists = false;
try {
    $pdo->query("SELECT 1 FROM courtiers LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {}
try {
    $pdo->query("SELECT 1 FROM leads LIMIT 1");
    $contactsExists = true;
} catch (PDOException $e) {}

// ─── Colonnes courtiers ───
$availCols = [];
if ($tableExists) {
    try {
        $availCols = $pdo->query("SHOW COLUMNS FROM courtiers")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}
$hasPhone       = in_array('phone',           $availCols);
$hasEmail       = in_array('email',           $availCols);
$hasCompany     = in_array('company',         $availCols);
$hasCity        = in_array('city',            $availCols);
$hasZone        = in_array('zone_geo',        $availCols);
$hasCommission  = in_array('commission_rate', $availCols);
$hasStatus      = in_array('status',          $availCols);
$hasType        = in_array('type',            $availCols);
$hasLeadId      = in_array('lead_id',         $availCols);
$hasNotes       = in_array('notes',           $availCols);
$hasReco        = in_array('reco_count',      $availCols);
$hasRevenu      = in_array('revenu_total',    $availCols);
$hasUpdatedAt   = in_array('updated_at',      $availCols);

// ─── Stats ───
$stats = ['total'=>0,'actif'=>0,'prospect'=>0,'inactif'=>0,'reco_total'=>0,'ca_total'=>0];
if ($tableExists) {
    try {
        $stats['total']   = (int)$pdo->query("SELECT COUNT(*) FROM courtiers")->fetchColumn();
        if ($hasStatus) {
            $stats['actif']    = (int)$pdo->query("SELECT COUNT(*) FROM courtiers WHERE status='actif'")->fetchColumn();
            $stats['prospect'] = (int)$pdo->query("SELECT COUNT(*) FROM courtiers WHERE status='prospect'")->fetchColumn();
            $stats['inactif']  = (int)$pdo->query("SELECT COUNT(*) FROM courtiers WHERE status='inactif'")->fetchColumn();
        }
        if ($hasReco)   $stats['reco_total'] = (int)$pdo->query("SELECT SUM(reco_count) FROM courtiers")->fetchColumn();
        if ($hasRevenu) $stats['ca_total']   = (float)$pdo->query("SELECT SUM(revenu_total) FROM courtiers")->fetchColumn();
    } catch (PDOException $e) {}
}

// ─── Filtres URL ───
$filterStatus = $_GET['status']   ?? 'all';
$filterType   = $_GET['type']     ?? 'all';
$searchQuery  = trim($_GET['q']   ?? '');
$currentPage  = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 20;
$offset       = ($currentPage - 1) * $perPage;

// ─── Types disponibles ───
$typesList = [];
if ($tableExists && $hasType) {
    try {
        $typesList = $pdo->query("SELECT DISTINCT type FROM courtiers WHERE type IS NOT NULL AND type != '' ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

// ─── WHERE ───
$where  = [];
$params = [];
if ($filterStatus !== 'all' && $hasStatus) {
    $where[] = "c.status = ?"; $params[] = $filterStatus;
}
if ($filterType !== 'all' && $hasType) {
    $where[] = "c.type = ?"; $params[] = $filterType;
}
if ($searchQuery !== '') {
    $conds = ["c.nom LIKE ?", "c.prenom LIKE ?"];
    $params[] = "%$searchQuery%"; $params[] = "%$searchQuery%";
    if ($hasEmail)   { $conds[] = "c.email LIKE ?";   $params[] = "%$searchQuery%"; }
    if ($hasCompany) { $conds[] = "c.company LIKE ?";  $params[] = "%$searchQuery%"; }
    if ($hasCity)    { $conds[] = "c.city LIKE ?";     $params[] = "%$searchQuery%"; }
    $where[] = '(' . implode(' OR ', $conds) . ')';
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ─── Requête ───
$totalFiltered = 0;
$courtiers     = [];
$totalPages    = 1;
if ($tableExists) {
    try {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM courtiers c $whereSQL");
        $stmtCount->execute($params);
        $totalFiltered = (int)$stmtCount->fetchColumn();
        $totalPages    = max(1, ceil($totalFiltered / $perPage));

        $sel = ["c.id", "c.nom", "c.prenom", "c.created_at"];
        if ($hasEmail)      $sel[] = "c.email";
        if ($hasPhone)      $sel[] = "c.phone";
        if ($hasCompany)    $sel[] = "c.company";
        if ($hasCity)       $sel[] = "c.city";
        if ($hasZone)       $sel[] = "c.zone_geo";
        if ($hasStatus)     $sel[] = "c.status";
        if ($hasType)       $sel[] = "c.type";
        if ($hasCommission) $sel[] = "c.commission_rate";
        if ($hasReco)       $sel[] = "c.reco_count";
        if ($hasRevenu)     $sel[] = "c.revenu_total";
        if ($hasNotes)      $sel[] = "c.notes";
        if ($hasLeadId)     $sel[] = "c.lead_id";
        if ($hasUpdatedAt)  $sel[] = "c.updated_at";

        // JOIN leads si lié
        $joinSQL = ($hasLeadId && $contactsExists)
            ? "LEFT JOIN leads l ON l.id = c.lead_id" : "";
        if ($hasLeadId && $contactsExists) {
            $sel[] = "l.email AS lead_email";
            $sel[] = "CONCAT(l.firstname,' ',l.lastname) AS lead_nom";
        }

        $colsSQL = implode(', ', $sel);
        $stmt = $pdo->prepare("SELECT $colsSQL FROM courtiers c $joinSQL $whereSQL ORDER BY c.created_at DESC LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        $courtiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("[Courtiers] SQL Error: " . $e->getMessage());
    }
}

// ─── CSRF ───
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ─── Helpers ───
$typeLabels = [
    'courtier'      => ['label'=>'Courtier',        'icon'=>'fa-university',    'color'=>'#3b82f6'],
    'mandataire'    => ['label'=>'Mandataire',       'icon'=>'fa-id-badge',      'color'=>'#8b5cf6'],
    'apporteur'     => ['label'=>"Apporteur d'affaire", 'icon'=>'fa-handshake', 'color'=>'#10b981'],
    'partenaire'    => ['label'=>'Partenaire',       'icon'=>'fa-link',          'color'=>'#f59e0b'],
    'notaire'       => ['label'=>'Notaire',          'icon'=>'fa-gavel',         'color'=>'#6b7280'],
];

$statusLabels = [
    'actif'    => ['label'=>'Actif',    'class'=>'crt-s-actif'],
    'prospect' => ['label'=>'Prospect', 'class'=>'crt-s-prospect'],
    'inactif'  => ['label'=>'Inactif',  'class'=>'crt-s-inactif'],
    'pause'    => ['label'=>'Pause',    'class'=>'crt-s-pause'],
];

$flash = $_GET['msg'] ?? '';
?>
<style>
/* ══════════════════════════════════════════════════════════════
   COURTIERS MODULE v1.0 — pattern pages/articles
══════════════════════════════════════════════════════════════ */
.crt-wrap { font-family: var(--font,'Inter',sans-serif); }

/* ─── Banner ─── */
.crt-banner {
    background: var(--surface,#fff); border-radius:16px; padding:26px 30px; margin-bottom:22px;
    display:flex; align-items:center; justify-content:space-between;
    border:1px solid var(--border,#e5e7eb); position:relative; overflow:hidden;
}
.crt-banner::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,#14b8a6,#3b82f6,#8b5cf6);
}
.crt-banner::after {
    content:''; position:absolute; top:-40%; right:-5%; width:220px; height:220px;
    background:radial-gradient(circle,rgba(20,184,166,.05),transparent 70%);
    border-radius:50%; pointer-events:none;
}
.crt-banner-left { position:relative; z-index:1; }
.crt-banner-left h2 { font-size:1.35rem; font-weight:700; color:var(--text,#111827); margin:0 0 4px; display:flex; align-items:center; gap:10px; letter-spacing:-.02em; }
.crt-banner-left h2 i { font-size:16px; color:#14b8a6; }
.crt-banner-left p { color:var(--text-2,#6b7280); font-size:.85rem; margin:0; }
.crt-stats { display:flex; gap:8px; position:relative; z-index:1; flex-wrap:wrap; }
.crt-stat { text-align:center; padding:10px 16px; background:var(--surface-2,#f9fafb); border-radius:12px; border:1px solid var(--border,#e5e7eb); min-width:72px; transition:all .2s; cursor:pointer; }
.crt-stat:hover { border-color:var(--border-h,#d1d5db); box-shadow:0 2px 8px rgba(0,0,0,.06); }
.crt-stat .num { font-size:1.45rem; font-weight:800; line-height:1; color:var(--text,#111827); letter-spacing:-.03em; }
.crt-stat .num.teal   { color:#14b8a6; }
.crt-stat .num.green  { color:#10b981; }
.crt-stat .num.amber  { color:#f59e0b; }
.crt-stat .num.blue   { color:#3b82f6; }
.crt-stat .num.violet { color:#8b5cf6; }
.crt-stat .num.gray   { color:#6b7280; }
.crt-stat .lbl { font-size:.58rem; color:var(--text-3,#9ca3af); text-transform:uppercase; letter-spacing:.06em; font-weight:600; margin-top:3px; }

/* ─── Toolbar ─── */
.crt-toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-wrap:wrap; gap:10px; }
.crt-filters { display:flex; gap:3px; background:var(--surface,#fff); border:1px solid var(--border,#e5e7eb); border-radius:10px; padding:3px; flex-wrap:wrap; }
.crt-fbtn { padding:7px 15px; border:none; background:transparent; color:var(--text-2,#6b7280); font-size:.78rem; font-weight:600; border-radius:6px; cursor:pointer; transition:all .15s; font-family:inherit; display:flex; align-items:center; gap:5px; text-decoration:none; }
.crt-fbtn:hover { color:var(--text,#111827); background:var(--surface-2,#f9fafb); }
.crt-fbtn.active { background:#14b8a6; color:#fff; box-shadow:0 1px 4px rgba(20,184,166,.25); }
.crt-fbtn .badge { font-size:.68rem; padding:1px 7px; border-radius:10px; background:var(--surface-2,#f3f4f6); font-weight:700; color:var(--text-3,#9ca3af); }
.crt-fbtn.active .badge { background:rgba(255,255,255,.22); color:#fff; }

/* ─── Toolbar right ─── */
.crt-toolbar-r { display:flex; align-items:center; gap:10px; }
.crt-search { position:relative; }
.crt-search input { padding:8px 12px 8px 34px; background:var(--surface,#fff); border:1px solid var(--border,#e5e7eb); border-radius:10px; color:var(--text,#111827); font-size:.82rem; width:220px; font-family:inherit; transition:all .2s; }
.crt-search input:focus { outline:none; border-color:#14b8a6; box-shadow:0 0 0 3px rgba(20,184,166,.1); width:250px; }
.crt-search input::placeholder { color:var(--text-3,#9ca3af); }
.crt-search i { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--text-3,#9ca3af); font-size:.75rem; }
.crt-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:10px; font-size:.82rem; font-weight:600; cursor:pointer; border:none; transition:all .15s; font-family:inherit; text-decoration:none; line-height:1.3; }
.crt-btn-primary { background:#14b8a6; color:#fff; box-shadow:0 1px 4px rgba(20,184,166,.22); }
.crt-btn-primary:hover { background:#0d9488; transform:translateY(-1px); color:#fff; }
.crt-btn-outline { background:var(--surface,#fff); color:var(--text-2,#6b7280); border:1px solid var(--border,#e5e7eb); }
.crt-btn-outline:hover { border-color:#14b8a6; color:#14b8a6; }
.crt-btn-sm { padding:5px 12px; font-size:.75rem; }

/* ─── Sub-filtre type ─── */
.crt-subfilters { display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap; align-items:center; }
.crt-subfilter { display:flex; align-items:center; gap:5px; font-size:.75rem; color:var(--text-2,#6b7280); }
.crt-subfilter select { padding:5px 10px; border:1px solid var(--border,#e5e7eb); border-radius:6px; background:var(--surface,#fff); color:var(--text,#111827); font-size:.75rem; font-family:inherit; cursor:pointer; }
.crt-subfilter select:focus { outline:none; border-color:#14b8a6; }

/* ─── Bulk ─── */
.crt-bulk { display:none; align-items:center; gap:12px; padding:10px 16px; background:rgba(20,184,166,.06); border:1px solid rgba(20,184,166,.15); border-radius:10px; margin-bottom:12px; font-size:.78rem; color:#14b8a6; font-weight:600; }
.crt-bulk.active { display:flex; }
.crt-bulk select { padding:5px 10px; border:1px solid var(--border,#e5e7eb); border-radius:6px; background:var(--surface,#fff); color:var(--text,#111827); font-size:.75rem; }
.crt-table input[type="checkbox"] { accent-color:#14b8a6; width:14px; height:14px; cursor:pointer; }

/* ─── Table ─── */
.crt-table-wrap { background:var(--surface,#fff); border-radius:12px; border:1px solid var(--border,#e5e7eb); overflow:hidden; }
.crt-table { width:100%; border-collapse:collapse; }
.crt-table thead th { padding:11px 14px; font-size:.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-3,#9ca3af); background:var(--surface-2,#f9fafb); border-bottom:1px solid var(--border,#e5e7eb); text-align:left; white-space:nowrap; }
.crt-table thead th.center { text-align:center; }
.crt-table tbody tr { border-bottom:1px solid var(--border,#f3f4f6); transition:background .1s; }
.crt-table tbody tr:hover { background:rgba(20,184,166,.02); }
.crt-table tbody tr:last-child { border-bottom:none; }
.crt-table td { padding:11px 14px; font-size:.83rem; color:var(--text,#111827); vertical-align:middle; }
.crt-table td.center { text-align:center; }

/* ─── Cellule nom ─── */
.crt-name-cell a { font-weight:600; color:var(--text,#111827); text-decoration:none; transition:color .15s; display:flex; align-items:center; gap:8px; }
.crt-name-cell a:hover { color:#14b8a6; }
.crt-avatar { width:34px; height:34px; border-radius:10px; background:linear-gradient(135deg,#14b8a6,#3b82f6); display:flex; align-items:center; justify-content:center; color:white; font-size:.75rem; font-weight:800; flex-shrink:0; }
.crt-company { font-size:.72rem; color:var(--text-3,#9ca3af); margin-top:2px; }
.crt-contact-link { display:inline-flex; align-items:center; gap:3px; font-size:.7rem; color:#3b82f6; text-decoration:none; margin-top:2px; }
.crt-contact-link:hover { text-decoration:underline; }

/* ─── Type badge ─── */
.crt-type { display:inline-flex; align-items:center; gap:5px; padding:4px 10px; border-radius:8px; font-size:.68rem; font-weight:700; white-space:nowrap; }

/* ─── Status badge ─── */
.crt-status { padding:3px 10px; border-radius:12px; font-size:.63rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; display:inline-block; }
.crt-s-actif    { background:#d1fae5; color:#059669; }
.crt-s-prospect { background:#dbeafe; color:#2563eb; }
.crt-s-inactif  { background:#f3f4f6; color:#9ca3af; }
.crt-s-pause    { background:#fef3c7; color:#d97706; }

/* ─── Commission ─── */
.crt-commission { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; background:rgba(20,184,166,.08); border-radius:8px; font-size:.75rem; font-weight:700; color:#14b8a6; }

/* ─── Zone ─── */
.crt-zone { font-size:.72rem; color:var(--text-2,#6b7280); display:flex; align-items:center; gap:4px; }

/* ─── Reco count ─── */
.crt-reco { font-size:.82rem; font-weight:700; color:#8b5cf6; }

/* ─── Date ─── */
.crt-date { font-size:.73rem; color:var(--text-3,#9ca3af); white-space:nowrap; }

/* ─── Actions ─── */
.crt-actions { display:flex; gap:3px; justify-content:flex-end; }
.crt-actions a, .crt-actions button { width:30px; height:30px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:var(--text-3,#9ca3af); background:transparent; border:1px solid transparent; cursor:pointer; transition:all .12s; text-decoration:none; font-size:.78rem; }
.crt-actions a:hover, .crt-actions button:hover { color:#14b8a6; border-color:var(--border,#e5e7eb); background:rgba(20,184,166,.07); }
.crt-actions button.del:hover { color:#dc2626; border-color:rgba(220,38,38,.2); background:#fef2f2; }

/* ─── Pagination ─── */
.crt-pagination { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-top:1px solid var(--border,#e5e7eb); font-size:.78rem; color:var(--text-3,#9ca3af); }
.crt-pagination a { padding:6px 12px; border:1px solid var(--border,#e5e7eb); border-radius:10px; color:var(--text-2,#6b7280); text-decoration:none; font-weight:600; transition:all .15s; font-size:.78rem; }
.crt-pagination a:hover { border-color:#14b8a6; color:#14b8a6; }
.crt-pagination a.active { background:#14b8a6; color:#fff; border-color:#14b8a6; }

/* ─── Flash / Empty ─── */
.crt-flash { padding:12px 18px; border-radius:10px; font-size:.85rem; font-weight:600; margin-bottom:16px; display:flex; align-items:center; gap:8px; animation:crtFlashIn .3s; }
.crt-flash.success { background:#d1fae5; color:#059669; border:1px solid rgba(5,150,105,.12); }
.crt-flash.error   { background:#fef2f2; color:#dc2626; border:1px solid rgba(220,38,38,.12); }
@keyframes crtFlashIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:none} }
.crt-empty { text-align:center; padding:60px 20px; color:var(--text-3,#9ca3af); }
.crt-empty i { font-size:2.5rem; opacity:.2; margin-bottom:12px; display:block; }
.crt-empty h3 { color:var(--text-2,#6b7280); font-size:1rem; font-weight:600; margin-bottom:6px; }

/* ─── SQL Setup ─── */
.crt-setup { background:#fff; border:1px solid var(--border,#e5e7eb); border-radius:16px; padding:32px; }
.crt-setup h3 { font-size:1rem; font-weight:700; margin-bottom:12px; display:flex; align-items:center; gap:8px; }
.crt-setup pre { background:#1e293b; color:#e2e8f0; border-radius:10px; padding:20px; font-size:.75rem; overflow-x:auto; line-height:1.7; }

@media(max-width:1200px) { .crt-table .col-zone, .crt-table .col-revenu { display:none; } }
@media(max-width:960px)  { .crt-table-wrap { overflow-x:auto; } }
</style>

<div class="crt-wrap" id="crtWrap">

<?php if ($flash === 'created'): ?>
    <div class="crt-flash success"><i class="fas fa-check-circle"></i> Courtier ajouté avec succès</div>
<?php elseif ($flash === 'updated'): ?>
    <div class="crt-flash success"><i class="fas fa-check-circle"></i> Courtier mis à jour</div>
<?php elseif ($flash === 'deleted'): ?>
    <div class="crt-flash success"><i class="fas fa-check-circle"></i> Courtier supprimé</div>
<?php elseif ($flash === 'error'): ?>
    <div class="crt-flash error"><i class="fas fa-exclamation-circle"></i> Une erreur est survenue</div>
<?php endif; ?>

<?php if (!$tableExists): ?>
<div class="crt-setup">
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
        <div style="width:56px;height:56px;border-radius:14px;background:#14b8a618;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#14b8a6;flex-shrink:0">
            <i class="fas fa-magic"></i>
        </div>
        <div>
            <h3 style="margin:0;font-size:1rem;font-weight:700">Module non installé</h3>
            <p style="margin:4px 0 0;font-size:.82rem;color:var(--text-2,#6b7280)">
                La table <code>courtiers</code> n'existe pas encore en base de données.
            </p>
        </div>
    </div>
    <a href="?page=courtiers&run=install" class="crt-btn crt-btn-primary">
        <i class="fas fa-magic"></i> Installer le module automatiquement
    </a>
</div>
<?php else: ?>

<!-- ─── Banner ─── -->
<div class="crt-banner">
    <div class="crt-banner-left">
        <h2><i class="fas fa-briefcase"></i> Courtiers Partenaires</h2>
        <p>Réseau de courtiers affiliés — recommandation, commission & affiliation</p>
    </div>
    <div class="crt-stats">
        <div class="crt-stat">
            <div class="num teal"><?= $stats['total'] ?></div>
            <div class="lbl">Total</div>
        </div>
        <div class="crt-stat">
            <div class="num green"><?= $stats['actif'] ?></div>
            <div class="lbl">Actifs</div>
        </div>
        <div class="crt-stat">
            <div class="num blue"><?= $stats['prospect'] ?></div>
            <div class="lbl">Prospects</div>
        </div>
        <div class="crt-stat">
            <div class="num gray"><?= $stats['inactif'] ?></div>
            <div class="lbl">Inactifs</div>
        </div>
        <?php if ($hasReco): ?>
        <div class="crt-stat">
            <div class="num violet"><?= $stats['reco_total'] ?></div>
            <div class="lbl">Recos</div>
        </div>
        <?php endif; ?>
        <?php if ($hasRevenu && $stats['ca_total'] > 0): ?>
        <div class="crt-stat">
            <div class="num amber"><?= number_format($stats['ca_total'], 0, ',', ' ') ?>€</div>
            <div class="lbl">CA Total</div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ─── Toolbar ─── -->
<div class="crt-toolbar">
    <div class="crt-filters">
        <?php
        $filters = [
            'all'     => ['icon'=>'fa-layer-group', 'label'=>'Tous',      'count'=>$stats['total']],
            'actif'   => ['icon'=>'fa-check-circle','label'=>'Actifs',    'count'=>$stats['actif']],
            'prospect'=> ['icon'=>'fa-user-clock',  'label'=>'Prospects', 'count'=>$stats['prospect']],
            'inactif' => ['icon'=>'fa-pause-circle','label'=>'Inactifs',  'count'=>$stats['inactif']],
        ];
        foreach ($filters as $key => $f):
            $active = ($filterStatus === $key) ? ' active' : '';
            $url = '?page=courtiers' . ($key !== 'all' ? '&status='.$key : '');
            if ($searchQuery)           $url .= '&q='.urlencode($searchQuery);
            if ($filterType !== 'all')  $url .= '&type='.urlencode($filterType);
        ?>
        <a href="<?= $url ?>" class="crt-fbtn<?= $active ?>">
            <i class="fas <?= $f['icon'] ?>"></i> <?= $f['label'] ?>
            <span class="badge"><?= (int)$f['count'] ?></span>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="crt-toolbar-r">
        <form class="crt-search" method="GET">
            <input type="hidden" name="page" value="courtiers">
            <?php if ($filterStatus!=='all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
            <?php if ($filterType!=='all'):   ?><input type="hidden" name="type"   value="<?= htmlspecialchars($filterType) ?>"><?php endif; ?>
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Nom, email, ville…" value="<?= htmlspecialchars($searchQuery) ?>">
        </form>
        <a href="?page=courtiers&action=create" class="crt-btn crt-btn-primary">
            <i class="fas fa-plus"></i> Nouveau courtier
        </a>
    </div>
</div>

<!-- ─── Sub-filtres ─── -->
<?php if (!empty($typesList)): ?>
<div class="crt-subfilters">
    <div class="crt-subfilter">
        <i class="fas fa-tags"></i>
        <select onchange="CRT.filterBy('type', this.value)">
            <option value="all" <?= $filterType==='all'?'selected':'' ?>>Tous les types</option>
            <?php foreach ($typesList as $t):
                $tInfo = $typeLabels[$t] ?? ['label'=>ucfirst($t),'icon'=>'fa-user','color'=>'#6b7280'];
            ?>
            <option value="<?= htmlspecialchars($t) ?>" <?= $filterType===$t?'selected':'' ?>>
                <?= $tInfo['label'] ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<?php endif; ?>

<!-- ─── Bulk ─── -->
<div class="crt-bulk" id="crtBulkBar">
    <input type="checkbox" id="crtSelectAll" onchange="CRT.toggleAll(this.checked)">
    <span id="crtBulkCount">0</span> sélectionné(s)
    <select id="crtBulkAction">
        <option value="">— Action groupée —</option>
        <option value="actif">Marquer actif</option>
        <option value="inactif">Marquer inactif</option>
        <option value="delete">Supprimer</option>
    </select>
    <button class="crt-btn crt-btn-sm crt-btn-outline" onclick="CRT.bulkExecute()">
        <i class="fas fa-check"></i> Appliquer
    </button>
</div>

<?php if (empty($courtiers)): ?>
<div class="crt-empty">
    <i class="fas fa-briefcase"></i>
    <h3>Aucun courtier trouvé</h3>
    <p>
        <?php if ($searchQuery || $filterType !== 'all' || $filterStatus !== 'all'): ?>
            Aucun résultat. <a href="?page=courtiers">Effacer les filtres</a>
        <?php else: ?>
            Ajoutez votre premier courtier partenaire.
        <?php endif; ?>
    </p>
    <a href="?page=courtiers&action=create" class="crt-btn crt-btn-primary" style="margin-top:12px;display:inline-flex">
        <i class="fas fa-plus"></i> Ajouter un courtier
    </a>
</div>
<?php else: ?>

<!-- ─── Table ─── -->
<div class="crt-table-wrap">
    <table class="crt-table">
        <thead>
            <tr>
                <th style="width:32px"><input type="checkbox" onchange="CRT.toggleAll(this.checked)"></th>
                <th>Courtier</th>
                <th>Type</th>
                <th>Statut</th>
                <?php if ($hasZone):       ?><th class="col-zone">Zone</th><?php endif; ?>
                <?php if ($hasCommission): ?><th class="center">Commission</th><?php endif; ?>
                <?php if ($hasReco):       ?><th class="center">Recos</th><?php endif; ?>
                <?php if ($hasRevenu):     ?><th class="col-revenu center">CA</th><?php endif; ?>
                <th>Date</th>
                <th style="text-align:right">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($courtiers as $c):
            $initials = strtoupper(substr($c['prenom'] ?? '', 0, 1) . substr($c['nom'] ?? '', 0, 1));
            $tType    = $c['type'] ?? 'courtier';
            $tInfo    = $typeLabels[$tType] ?? ['label'=>ucfirst($tType),'icon'=>'fa-user','color'=>'#6b7280'];
            $tStatus  = $c['status'] ?? 'prospect';
            $sInfo    = $statusLabels[$tStatus] ?? ['label'=>ucfirst($tStatus),'class'=>'crt-s-prospect'];
            $date     = !empty($c['created_at']) ? date('d/m/Y', strtotime($c['created_at'])) : '—';
            $editUrl  = "?page=courtiers&action=edit&id={$c['id']}";
            $fullName = trim(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? ''));
        ?>
        <tr data-id="<?= (int)$c['id'] ?>">
            <td><input type="checkbox" class="crt-cb" value="<?= (int)$c['id'] ?>" onchange="CRT.updateBulk()"></td>

            <!-- Nom + infos -->
            <td class="crt-name-cell">
                <a href="<?= htmlspecialchars($editUrl) ?>">
                    <div class="crt-avatar"><?= $initials ?></div>
                    <div>
                        <div><?= htmlspecialchars($fullName) ?></div>
                        <?php if (!empty($c['company'])): ?>
                        <div class="crt-company"><i class="fas fa-building" style="font-size:.6rem"></i> <?= htmlspecialchars($c['company']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($c['email'])): ?>
                        <div class="crt-company"><?= htmlspecialchars($c['email']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($c['lead_id']) && !empty($c['lead_nom'])): ?>
                        <a href="?page=crm&contact=<?= (int)$c['lead_id'] ?>" class="crt-contact-link">
                            <i class="fas fa-user-circle"></i> Voir contact CRM
                        </a>
                        <?php endif; ?>
                    </div>
                </a>
            </td>

            <!-- Type -->
            <td>
                <span class="crt-type" style="background:<?= $tInfo['color'] ?>18;color:<?= $tInfo['color'] ?>">
                    <i class="fas <?= $tInfo['icon'] ?>" style="font-size:.6rem"></i>
                    <?= $tInfo['label'] ?>
                </span>
            </td>

            <!-- Statut -->
            <td><span class="crt-status <?= $sInfo['class'] ?>"><?= $sInfo['label'] ?></span></td>

            <!-- Zone -->
            <?php if ($hasZone): ?>
            <td class="col-zone">
                <?php if (!empty($c['zone_geo'])): ?>
                <span class="crt-zone"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($c['zone_geo']) ?></span>
                <?php else: ?><span style="color:var(--text-3);font-size:.75rem">—</span><?php endif; ?>
            </td>
            <?php endif; ?>

            <!-- Commission -->
            <?php if ($hasCommission): ?>
            <td class="center">
                <?php if ($c['commission_rate'] > 0): ?>
                <span class="crt-commission"><i class="fas fa-percent" style="font-size:.6rem"></i> <?= number_format((float)$c['commission_rate'], 1) ?>%</span>
                <?php else: ?><span style="color:var(--text-3);font-size:.75rem">—</span><?php endif; ?>
            </td>
            <?php endif; ?>

            <!-- Recos -->
            <?php if ($hasReco): ?>
            <td class="center">
                <span class="crt-reco"><?= (int)($c['reco_count'] ?? 0) ?></span>
            </td>
            <?php endif; ?>

            <!-- CA -->
            <?php if ($hasRevenu): ?>
            <td class="col-revenu center">
                <span style="font-size:.78rem;font-weight:700;color:<?= ($c['revenu_total']??0)>0?'#10b981':'var(--text-3)' ?>">
                    <?= ($c['revenu_total']??0)>0 ? number_format((float)$c['revenu_total'],0,',',' ').'€' : '—' ?>
                </span>
            </td>
            <?php endif; ?>

            <!-- Date -->
            <td><span class="crt-date"><?= $date ?></span></td>

            <!-- Actions -->
            <td>
                <div class="crt-actions">
                    <a href="<?= htmlspecialchars($editUrl) ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                    <?php if (!empty($c['phone'])): ?>
                    <a href="tel:<?= htmlspecialchars($c['phone']) ?>" title="Appeler"><i class="fas fa-phone"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($c['email'])): ?>
                    <a href="mailto:<?= htmlspecialchars($c['email']) ?>" title="Email"><i class="fas fa-envelope"></i></a>
                    <?php endif; ?>
                    <button onclick="CRT.toggleStatus(<?= (int)$c['id'] ?>, '<?= $tStatus ?>')"
                            title="<?= $tStatus==='actif'?'Désactiver':'Activer' ?>">
                        <i class="fas <?= $tStatus==='actif'?'fa-pause':'fa-play' ?>"></i>
                    </button>
                    <button class="del" onclick="CRT.deleteCrt(<?= (int)$c['id'] ?>, '<?= addslashes(htmlspecialchars($fullName)) ?>')" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="crt-pagination">
        <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?> courtiers</span>
        <div style="display:flex;gap:4px">
            <?php for ($i=1; $i<=$totalPages; $i++):
                $pUrl = '?page=courtiers&p='.$i;
                if ($filterStatus!=='all') $pUrl .= '&status='.$filterStatus;
                if ($filterType!=='all')   $pUrl .= '&type='.$filterType;
                if ($searchQuery)           $pUrl .= '&q='.urlencode($searchQuery);
            ?>
            <a href="<?= $pUrl ?>" class="<?= $i===$currentPage?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php endif; ?>
</div>

<!-- ─── Modal ─── -->
<div id="crtModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    <div onclick="CRT.modalClose()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px)"></div>
    <div id="crtModalBox" style="position:relative;z-index:1;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:420px;margin:16px;overflow:hidden;transform:scale(.94) translateY(8px);transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .15s;opacity:0">
        <div id="crtModalHeader" style="padding:20px 22px 16px;display:flex;align-items:flex-start;gap:14px">
            <div id="crtModalIcon" style="width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem"></div>
            <div style="flex:1;min-width:0">
                <div id="crtModalTitle" style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:5px"></div>
                <div id="crtModalMsg"   style="font-size:.82rem;color:#6b7280;line-height:1.5"></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding:12px 20px 18px;border-top:1px solid #f3f4f6">
            <button onclick="CRT.modalClose()" style="padding:9px 20px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:.83rem;font-weight:600;cursor:pointer;font-family:inherit">Annuler</button>
            <button id="crtModalConfirm" style="padding:9px 20px;border-radius:10px;border:none;font-size:.83rem;font-weight:700;cursor:pointer;font-family:inherit;color:#fff"></button>
        </div>
    </div>
</div>

<script>
const CRT = {
    apiUrl: '/admin/modules/courtiers/api.php',
    _modalCb: null,

    filterBy(key, val) {
        const url = new URL(window.location.href);
        val === 'all' ? url.searchParams.delete(key) : url.searchParams.set(key, val);
        url.searchParams.delete('p');
        window.location.href = url.toString();
    },

    toggleAll(checked) {
        document.querySelectorAll('.crt-cb').forEach(cb => cb.checked = checked);
        this.updateBulk();
    },
    updateBulk() {
        const n = document.querySelectorAll('.crt-cb:checked').length;
        document.getElementById('crtBulkCount').textContent = n;
        document.getElementById('crtBulkBar').classList.toggle('active', n > 0);
    },
    async bulkExecute() {
        const action = document.getElementById('crtBulkAction').value;
        if (!action) return;
        const ids = [...document.querySelectorAll('.crt-cb:checked')].map(cb => parseInt(cb.value));
        if (!ids.length) return;
        if (action === 'delete') {
            this.modal({
                icon:'<i class="fas fa-trash"></i>', iconBg:'#fef2f2', iconColor:'#dc2626',
                title:`Supprimer ${ids.length} courtier(s) ?`, msg:'Action irréversible.',
                confirmLabel:'Supprimer', confirmColor:'#dc2626',
                onConfirm: async () => {
                    const fd = new FormData(); fd.append('action','bulk_delete'); fd.append('ids',JSON.stringify(ids));
                    const r = await fetch(this.apiUrl,{method:'POST',body:fd});
                    const d = await r.json();
                    d.success ? location.reload() : this.toast(d.error||'Erreur','error');
                }
            });
            return;
        }
        const fd = new FormData(); fd.append('action','bulk_status'); fd.append('status',action); fd.append('ids',JSON.stringify(ids));
        const r = await fetch(this.apiUrl,{method:'POST',body:fd});
        const d = await r.json();
        d.success ? location.reload() : this.toast(d.error||'Erreur','error');
    },

    modal({icon,iconBg,iconColor,title,msg,confirmLabel,confirmColor,onConfirm}) {
        const el = document.getElementById('crtModal');
        const box = document.getElementById('crtModalBox');
        document.getElementById('crtModalIcon').innerHTML   = icon;
        document.getElementById('crtModalIcon').style.background = iconBg;
        document.getElementById('crtModalIcon').style.color      = iconColor;
        document.getElementById('crtModalHeader').style.background = iconBg+'33';
        document.getElementById('crtModalTitle').textContent = title;
        document.getElementById('crtModalMsg').innerHTML    = msg;
        const btn = document.getElementById('crtModalConfirm');
        btn.textContent = confirmLabel||'Confirmer'; btn.style.background = confirmColor||'#14b8a6';
        this._modalCb = onConfirm;
        btn.onclick = () => { this.modalClose(); if(this._modalCb) this._modalCb(); };
        el.style.display='flex';
        requestAnimationFrame(() => { box.style.opacity='1'; box.style.transform='scale(1) translateY(0)'; });
        document.addEventListener('keydown', this._esc);
    },
    modalClose() {
        const el=document.getElementById('crtModal'), box=document.getElementById('crtModalBox');
        box.style.opacity='0'; box.style.transform='scale(.94) translateY(8px)';
        setTimeout(()=>el.style.display='none',160);
        document.removeEventListener('keydown',this._esc);
    },
    _esc(e){ if(e.key==='Escape') CRT.modalClose(); },

    toast(msg, type='success') {
        const c={success:'#059669',error:'#dc2626',info:'#3b82f6'};
        const ic={success:'✓',error:'✕',info:'ℹ'};
        const t=document.createElement('div');
        t.style.cssText='position:fixed;bottom:24px;right:24px;z-index:10000;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:10px;font-size:.83rem;font-weight:600;color:#111827;box-shadow:0 8px 24px rgba(0,0,0,.12);transform:translateY(20px);opacity:0;transition:all .25s;';
        t.innerHTML=`<span style="width:22px;height:22px;border-radius:50%;background:${c[type]}22;color:${c[type]};display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800">${ic[type]}</span>${msg}`;
        document.body.appendChild(t);
        requestAnimationFrame(()=>{ t.style.opacity='1'; t.style.transform='translateY(0)'; });
        setTimeout(()=>{ t.style.opacity='0'; t.style.transform='translateY(10px)'; setTimeout(()=>t.remove(),250); },3500);
    },

    deleteCrt(id, name) {
        this.modal({
            icon:'<i class="fas fa-trash"></i>', iconBg:'#fef2f2', iconColor:'#dc2626',
            title:'Supprimer ce courtier ?',
            msg:`<strong>${name}</strong> sera supprimé définitivement.`,
            confirmLabel:'Supprimer', confirmColor:'#dc2626',
            onConfirm: async () => {
                const fd=new FormData(); fd.append('action','delete'); fd.append('id',id);
                const r=await fetch(this.apiUrl,{method:'POST',body:fd});
                const d=await r.json();
                if(d.success){
                    document.querySelectorAll(`[data-id="${id}"]`).forEach(el=>{ el.style.cssText='opacity:0;transform:scale(.95);transition:all .3s'; setTimeout(()=>el.remove(),300); });
                    this.toast('Courtier supprimé','success');
                } else this.toast(d.error||'Erreur','error');
            }
        });
    },

    async toggleStatus(id, current) {
        const newS = current==='actif' ? 'inactif' : 'actif';
        const fd=new FormData(); fd.append('action','toggle_status'); fd.append('id',id); fd.append('status',newS);
        const r=await fetch(this.apiUrl,{method:'POST',body:fd});
        const d=await r.json();
        if(d.success){ this.toast(newS==='actif'?'Courtier activé ✓':'Courtier désactivé','success'); setTimeout(()=>location.reload(),800); }
        else this.toast(d.error||'Erreur','error');
    }
};
</script>