<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE CRM CONTACTS  v1.0
 * /admin/modules/crm/contacts/index.php
 * Pattern aligné pages v1.0 / articles v2.3 :
 *   - Détection dynamique des colonnes
 *   - Toggle vue liste / grille
 *   - Modal custom + Toast notifications
 *   - Bulk actions
 *   - Sub-filtres (category, status, source)
 * ══════════════════════════════════════════════════════════════
 */

// ─── Connexion DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

// ─── Détecter table ───
$tableName   = 'contacts';
$tableExists = true;
try {
    $pdo->query("SELECT 1 FROM contacts LIMIT 1");
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
$hasNom        = in_array('nom',          $availCols);
$hasFirstname  = in_array('firstname',    $availCols);
$hasLastname   = in_array('lastname',     $availCols);
$hasPrenom     = in_array('prenom',       $availCols);
$hasEmail      = in_array('email',        $availCols);
$hasTelephone  = in_array('telephone',    $availCols);
$hasPhone      = in_array('phone',        $availCols);
$hasMobile     = in_array('mobile',       $availCols);
$hasSource     = in_array('source',       $availCols);
$hasNotes      = in_array('notes',        $availCols);
$hasCategory   = in_array('category',     $availCols);
$hasStatus     = in_array('status',       $availCols);
$hasCivility   = in_array('civility',     $availCols);
$hasCity       = in_array('city',         $availCols);
$hasCompany    = in_array('company',      $availCols);
$hasJobTitle   = in_array('job_title',    $availCols);
$hasRating     = in_array('rating',       $availCols);
$hasTags       = in_array('tags',         $availCols);
$hasLastContact= in_array('last_contact', $availCols);
$hasNextFollowup=in_array('next_followup',$availCols);
$hasAssignedTo = in_array('assigned_to',  $availCols);
$hasUpdatedAt  = in_array('updated_at',   $availCols);
$hasCreatedAt  = in_array('created_at',   $availCols);
$hasLinkedin   = in_array('linkedin',     $availCols);
$hasFacebook   = in_array('facebook',     $availCols);
$hasInstagram  = in_array('instagram',    $availCols);
$hasWebsite    = in_array('website',      $availCols);
$hasBirthday   = in_array('birthday',     $availCols);

// ─── Col nom principal ───
$colDisplayName = $hasNom ? 'nom' : ($hasLastname ? 'lastname' : 'id');

// ─── ROUTING ───
$routeAction = $_GET['action'] ?? '';
if (in_array($routeAction, ['edit', 'create', 'delete'])) {
    $editFile = __DIR__ . '/edit.php';
    if (file_exists($editFile)) { require $editFile; return; }
    else {
        echo '<div style="background:#fee2e2;color:#991b1b;padding:20px;border-radius:10px;margin:20px;">
            <strong>⚠️ Fichier manquant :</strong> <code>/admin/modules/crm/contacts/edit.php</code></div>';
        return;
    }
}

// ─── Listes pour sub-filtres ───
$categoriesList = [];
$sourcesList    = [];
if ($tableExists) {
    try {
        if ($hasCategory)
            $categoriesList = $pdo->query("SELECT DISTINCT category FROM contacts WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
        if ($hasSource)
            $sourcesList = $pdo->query("SELECT DISTINCT source FROM contacts WHERE source IS NOT NULL AND source != '' ORDER BY source")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}
}

// ─── Filtres URL ───
$filterStatus   = $_GET['status']   ?? 'all';
$filterCategory = $_GET['category'] ?? 'all';
$filterSource   = $_GET['source']   ?? 'all';
$searchQuery    = trim($_GET['q']   ?? '');
$currentPage    = max(1, (int)($_GET['p'] ?? 1));
$perPage        = 25;
$offset         = ($currentPage - 1) * $perPage;

// ─── Stats globales ───
$stats = ['total' => 0, 'active' => 0, 'prospect' => 0, 'client' => 0, 'vip' => 0, 'followup_today' => 0];
if ($tableExists) {
    try {
        $stats['total']   = (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
        if ($hasStatus)   $stats['active']  = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE status = 'active'")->fetchColumn();
        if ($hasStatus)   $stats['vip']     = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE status = 'vip'")->fetchColumn();
        if ($hasCategory) $stats['prospect']= (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE category = 'prospect'")->fetchColumn();
        if ($hasCategory) $stats['client']  = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE category = 'client'")->fetchColumn();
        if ($hasNextFollowup) $stats['followup_today'] = (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE next_followup <= CURDATE() AND next_followup IS NOT NULL")->fetchColumn();
    } catch (PDOException $e) {}
}

// ─── WHERE ───
$where  = [];
$params = [];

if ($filterStatus !== 'all' && $hasStatus) {
    $where[] = "c.status = ?"; $params[] = $filterStatus;
}
if ($filterCategory !== 'all' && $hasCategory) {
    $where[] = "c.category = ?"; $params[] = $filterCategory;
}
if ($filterSource !== 'all' && $hasSource) {
    $where[] = "c.source = ?"; $params[] = $filterSource;
}
if ($searchQuery !== '') {
    $parts = ["c.nom LIKE ?", "c.email LIKE ?"];
    $params[] = "%{$searchQuery}%"; $params[] = "%{$searchQuery}%";
    if ($hasFirstname) { $parts[] = "c.firstname LIKE ?"; $params[] = "%{$searchQuery}%"; }
    if ($hasLastname)  { $parts[] = "c.lastname LIKE ?";  $params[] = "%{$searchQuery}%"; }
    if ($hasPhone)     { $parts[] = "c.phone LIKE ?";     $params[] = "%{$searchQuery}%"; }
    if ($hasTelephone) { $parts[] = "c.telephone LIKE ?"; $params[] = "%{$searchQuery}%"; }
    if ($hasCity)      { $parts[] = "c.city LIKE ?";      $params[] = "%{$searchQuery}%"; }
    if ($hasTags)      { $parts[] = "c.tags LIKE ?";      $params[] = "%{$searchQuery}%"; }
    $where[] = '(' . implode(' OR ', $parts) . ')';
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ─── Total filtré + données ───
$totalFiltered = 0;
$contacts      = [];
$totalPages    = 1;

if ($tableExists) {
    try {
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM `{$tableName}` c {$whereSQL}");
        $stmtCount->execute($params);
        $totalFiltered = (int)$stmtCount->fetchColumn();
        $totalPages    = max(1, ceil($totalFiltered / $perPage));

        $sel = ["c.id", "c.nom", "c.email", "c.created_at"];
        if ($hasFirstname)  $sel[] = "c.firstname";
        if ($hasLastname)   $sel[] = "c.lastname";
        if ($hasPrenom)     $sel[] = "c.prenom";
        if ($hasCivility)   $sel[] = "c.civility";
        if ($hasTelephone)  $sel[] = "c.telephone";
        if ($hasPhone)      $sel[] = "c.phone";
        if ($hasMobile)     $sel[] = "c.mobile";
        if ($hasSource)     $sel[] = "c.source";
        if ($hasCategory)   $sel[] = "c.category";
        if ($hasStatus)     $sel[] = "c.status";
        if ($hasCity)       $sel[] = "c.city";
        if ($hasCompany)    $sel[] = "c.company";
        if ($hasJobTitle)   $sel[] = "c.job_title";
        if ($hasRating)     $sel[] = "c.rating";
        if ($hasTags)       $sel[] = "c.tags";
        if ($hasLastContact)$sel[] = "c.last_contact";
        if ($hasNextFollowup)$sel[]= "c.next_followup";
        if ($hasUpdatedAt)  $sel[] = "c.updated_at";
        if ($hasLinkedin)   $sel[] = "c.linkedin";
        if ($hasFacebook)   $sel[] = "c.facebook";
        if ($hasInstagram)  $sel[] = "c.instagram";
        if ($hasWebsite)    $sel[] = "c.website";

        $colsSQL = implode(', ', $sel);
        $stmt = $pdo->prepare("SELECT {$colsSQL} FROM `{$tableName}` c {$whereSQL} ORDER BY c.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("[CRM Contacts] SQL Error: " . $e->getMessage());
    }
}

// ─── CSRF ───
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Helpers ───
function getContactDisplayName(array $c): string {
    $nom = trim(($c['nom'] ?? '') . ' ' . ($c['prenom'] ?? ''));
    if ($nom !== '') return $nom;
    $fn = trim(($c['civility'] ?? '') . ' ' . ($c['firstname'] ?? '') . ' ' . ($c['lastname'] ?? ''));
    if (trim($fn) !== '') return trim($fn);
    return $c['email'] ?? ('Contact #' . $c['id']);
}

function getContactPhone(array $c): string {
    return $c['telephone'] ?? $c['phone'] ?? $c['mobile'] ?? '';
}

function getCategoryInfo(string $cat): array {
    $map = [
        'client'     => ['icon' => 'fa-user-check',   'label' => 'Client',     'color' => '#10b981', 'bg' => '#d1fae5'],
        'prospect'   => ['icon' => 'fa-user-clock',   'label' => 'Prospect',   'color' => '#3b82f6', 'bg' => '#dbeafe'],
        'partenaire' => ['icon' => 'fa-handshake',    'label' => 'Partenaire', 'color' => '#8b5cf6', 'bg' => '#ede9fe'],
        'notaire'    => ['icon' => 'fa-gavel',         'label' => 'Notaire',    'color' => '#d97706', 'bg' => '#fef3c7'],
        'autre'      => ['icon' => 'fa-user',          'label' => 'Autre',      'color' => '#6b7280', 'bg' => '#f3f4f6'],
    ];
    return $map[$cat] ?? ['icon' => 'fa-user', 'label' => ucfirst($cat), 'color' => '#6b7280', 'bg' => '#f3f4f6'];
}

function getStatusInfo(string $s): array {
    $map = [
        'active'    => ['label' => 'Actif',     'color' => '#059669', 'bg' => '#d1fae5'],
        'inactive'  => ['label' => 'Inactif',   'color' => '#6b7280', 'bg' => '#f3f4f6'],
        'vip'       => ['label' => 'VIP',        'color' => '#d97706', 'bg' => '#fef3c7'],
        'blacklist' => ['label' => 'Blacklist',  'color' => '#dc2626', 'bg' => '#fef2f2'],
    ];
    return $map[$s] ?? ['label' => ucfirst($s), 'color' => '#6b7280', 'bg' => '#f3f4f6'];
}

function getSourceIcon(string $src): string {
    $map = [
        'formulaire' => 'fa-wpforms', 'form' => 'fa-wpforms',
        'estimation' => 'fa-calculator',
        'landing'    => 'fa-rocket',
        'facebook'   => 'fa-facebook',
        'instagram'  => 'fa-instagram',
        'linkedin'   => 'fa-linkedin',
        'telephone'  => 'fa-phone', 'phone' => 'fa-phone',
        'referral'   => 'fa-user-friends',
        'manuel'     => 'fa-hand-pointer', 'manual' => 'fa-hand-pointer',
        'email'      => 'fa-envelope',
        'google'     => 'fa-google',
    ];
    foreach ($map as $k => $v) {
        if (stripos($src, $k) !== false) return $v;
    }
    return 'fa-plug';
}

function renderStars(int $rating): string {
    if ($rating <= 0) return '<span style="color:var(--text-3,#9ca3af);font-size:.7rem">—</span>';
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= '<i class="fas fa-star" style="font-size:.6rem;color:' . ($i <= $rating ? '#f59e0b' : '#e5e7eb') . '"></i>';
    }
    return $out;
}

$flash = $_GET['msg'] ?? '';
?>

<style>
/* ══════════════════════════════════════════════════════════════
   CRM CONTACTS MODULE v1.0
   Pattern identique pages v1.0 / articles v2.3
══════════════════════════════════════════════════════════════ */
.crmc-wrap { font-family: var(--font, 'Inter', sans-serif); }

/* ─── Banner ─── */
.crmc-banner {
    background: var(--surface, #fff);
    border-radius: 16px; padding: 26px 30px; margin-bottom: 22px;
    display: flex; align-items: center; justify-content: space-between;
    border: 1px solid var(--border, #e5e7eb); position: relative; overflow: hidden;
}
.crmc-banner::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #10b981, #3b82f6, #8b5cf6);
}
.crmc-banner::after {
    content: ''; position: absolute; top: -40%; right: -5%;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(16,185,129,.05), transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.crmc-banner-left { position: relative; z-index: 1; }
.crmc-banner-left h2 { font-size: 1.35rem; font-weight: 700; color: var(--text, #111827); margin: 0 0 4px; display: flex; align-items: center; gap: 10px; letter-spacing: -.02em; }
.crmc-banner-left h2 i { font-size: 16px; color: #10b981; }
.crmc-banner-left p { color: var(--text-2, #6b7280); font-size: .85rem; margin: 0; }
.crmc-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.crmc-stat { text-align: center; padding: 10px 16px; background: var(--surface-2, #f9fafb); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); min-width: 72px; transition: all .2s; cursor: default; }
.crmc-stat:hover { border-color: var(--border-h, #d1d5db); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.crmc-stat .num { font-size: 1.45rem; font-weight: 800; line-height: 1; letter-spacing: -.03em; }
.crmc-stat .num.green  { color: #10b981; }
.crmc-stat .num.blue   { color: #3b82f6; }
.crmc-stat .num.indigo { color: #6366f1; }
.crmc-stat .num.amber  { color: #f59e0b; }
.crmc-stat .num.violet { color: #8b5cf6; }
.crmc-stat .num.red    { color: #ef4444; }
.crmc-stat .num.gray   { color: var(--text, #111827); }
.crmc-stat .lbl { font-size: .58rem; color: var(--text-3, #9ca3af); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }

/* ─── Toolbar ─── */
.crmc-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.crmc-filters { display: flex; gap: 3px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; padding: 3px; flex-wrap: wrap; }
.crmc-fbtn { padding: 7px 15px; border: none; background: transparent; color: var(--text-2, #6b7280); font-size: .78rem; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all .15s; font-family: inherit; display: flex; align-items: center; gap: 5px; text-decoration: none; }
.crmc-fbtn:hover { color: var(--text, #111827); background: var(--surface-2, #f9fafb); }
.crmc-fbtn.active { background: #10b981; color: #fff; box-shadow: 0 1px 4px rgba(16,185,129,.25); }
.crmc-fbtn .badge { font-size: .68rem; padding: 1px 7px; border-radius: 10px; background: var(--surface-2, #f3f4f6); font-weight: 700; color: var(--text-3, #9ca3af); }
.crmc-fbtn.active .badge { background: rgba(255,255,255,.22); color: #fff; }

/* ─── Sub-filtres ─── */
.crmc-subfilters { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
.crmc-subfilter { display: flex; align-items: center; gap: 5px; font-size: .75rem; color: var(--text-2, #6b7280); }
.crmc-subfilter select { padding: 5px 10px; border: 1px solid var(--border, #e5e7eb); border-radius: 6px; background: var(--surface, #fff); color: var(--text, #111827); font-size: .75rem; font-family: inherit; cursor: pointer; }
.crmc-subfilter select:focus { outline: none; border-color: #10b981; }

/* ─── Toolbar right ─── */
.crmc-toolbar-r { display: flex; align-items: center; gap: 10px; }
.crmc-view-toggle { display: flex; gap: 2px; background: var(--surface-2, #f9fafb); border: 1px solid var(--border, #e5e7eb); border-radius: 8px; padding: 3px; }
.crmc-view-btn { width: 30px; height: 28px; border: none; background: transparent; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-3, #9ca3af); transition: all .15s; font-size: .78rem; }
.crmc-view-btn:hover { color: var(--text, #111827); }
.crmc-view-btn.active { background: white; color: #10b981; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.crmc-search { position: relative; }
.crmc-search input { padding: 8px 12px 8px 34px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; color: var(--text, #111827); font-size: .82rem; width: 220px; font-family: inherit; transition: all .2s; }
.crmc-search input:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.1); width: 260px; }
.crmc-search input::placeholder { color: var(--text-3, #9ca3af); }
.crmc-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-3, #9ca3af); font-size: .75rem; }
.crmc-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 10px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; font-family: inherit; text-decoration: none; line-height: 1.3; }
.crmc-btn-primary { background: #10b981; color: #fff; box-shadow: 0 1px 4px rgba(16,185,129,.22); }
.crmc-btn-primary:hover { background: #059669; transform: translateY(-1px); color: #fff; }
.crmc-btn-outline { background: var(--surface, #fff); color: var(--text-2, #6b7280); border: 1px solid var(--border, #e5e7eb); }
.crmc-btn-outline:hover { border-color: #10b981; color: #10b981; }
.crmc-btn-sm { padding: 5px 12px; font-size: .75rem; }
.crmc-btn-export { background: var(--surface, #fff); color: #6b7280; border: 1px solid var(--border, #e5e7eb); }
.crmc-btn-export:hover { border-color: #3b82f6; color: #3b82f6; }

/* ─── Bulk ─── */
.crmc-bulk { display: none; align-items: center; gap: 12px; padding: 10px 16px; background: rgba(16,185,129,.06); border: 1px solid rgba(16,185,129,.15); border-radius: 10px; margin-bottom: 12px; font-size: .78rem; color: #10b981; font-weight: 600; }
.crmc-bulk.active { display: flex; }
.crmc-bulk select { padding: 5px 10px; border: 1px solid var(--border, #e5e7eb); border-radius: 6px; background: var(--surface, #fff); color: var(--text, #111827); font-size: .75rem; }
.crmc-table input[type="checkbox"] { accent-color: #10b981; width: 14px; height: 14px; cursor: pointer; }

/* ─── Alerte followup ─── */
.crmc-followup-alert { display: flex; align-items: center; gap: 10px; padding: 12px 18px; background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; margin-bottom: 16px; font-size: .82rem; font-weight: 600; color: #92400e; animation: crmcFlashIn .3s; }
.crmc-followup-alert a { color: #d97706; }

/* ─── Table ─── */
.crmc-table-wrap { background: var(--surface, #fff); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; }
.crmc-table { width: 100%; border-collapse: collapse; }
.crmc-table thead th { padding: 11px 14px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-3, #9ca3af); background: var(--surface-2, #f9fafb); border-bottom: 1px solid var(--border, #e5e7eb); text-align: left; white-space: nowrap; }
.crmc-table thead th.center { text-align: center; }
.crmc-table tbody tr { border-bottom: 1px solid var(--border, #f3f4f6); transition: background .1s; }
.crmc-table tbody tr:hover { background: rgba(16,185,129,.02); }
.crmc-table tbody tr:last-child { border-bottom: none; }
.crmc-table td { padding: 11px 14px; font-size: .83rem; color: var(--text, #111827); vertical-align: middle; }
.crmc-table td.center { text-align: center; }

/* ─── Avatar ─── */
.crmc-contact-cell { display: flex; align-items: center; gap: 10px; }
.crmc-avatar { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 800; flex-shrink: 0; color: #fff; }
.crmc-contact-name { font-weight: 600; color: var(--text, #111827); text-decoration: none; transition: color .15s; font-size: .85rem; display: block; }
.crmc-contact-name:hover { color: #10b981; }
.crmc-contact-sub { font-size: .72rem; color: var(--text-3, #9ca3af); margin-top: 1px; display: flex; align-items: center; gap: 6px; }

/* ─── Category / Status badges ─── */
.crmc-cat { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
.crmc-status { padding: 3px 10px; border-radius: 12px; font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; display: inline-block; white-space: nowrap; }

/* ─── Source ─── */
.crmc-source { display: inline-flex; align-items: center; gap: 5px; font-size: .72rem; color: var(--text-2, #6b7280); }
.crmc-source i { font-size: .68rem; color: var(--text-3, #9ca3af); }

/* ─── Téléphone ─── */
.crmc-phone { font-size: .78rem; color: var(--text-2, #6b7280); white-space: nowrap; display: flex; align-items: center; gap: 4px; }
.crmc-phone i { font-size: .68rem; color: #10b981; }

/* ─── Tags ─── */
.crmc-tags { display: flex; gap: 3px; flex-wrap: wrap; }
.crmc-tag { padding: 2px 7px; background: rgba(99,102,241,.08); border-radius: 4px; font-size: .6rem; color: #6366f1; font-weight: 600; }

/* ─── Followup ─── */
.crmc-followup { font-size: .72rem; white-space: nowrap; }
.crmc-followup.overdue { color: #dc2626; font-weight: 700; }
.crmc-followup.today   { color: #d97706; font-weight: 700; }
.crmc-followup.future  { color: var(--text-3, #9ca3af); }

/* ─── Social ─── */
.crmc-social { display: flex; gap: 4px; }
.crmc-social a { width: 22px; height: 22px; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: var(--text-3, #9ca3af); border: 1px solid var(--border, #e5e7eb); font-size: .6rem; transition: all .12s; text-decoration: none; }
.crmc-social a:hover { color: #10b981; border-color: #10b981; background: rgba(16,185,129,.06); }

/* ─── Date ─── */
.crmc-date { font-size: .73rem; color: var(--text-3, #9ca3af); white-space: nowrap; }

/* ─── Actions ─── */
.crmc-actions { display: flex; gap: 3px; justify-content: flex-end; }
.crmc-actions a, .crmc-actions button { width: 30px; height: 30px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--text-3, #9ca3af); background: transparent; border: 1px solid transparent; cursor: pointer; transition: all .12s; text-decoration: none; font-size: .78rem; }
.crmc-actions a:hover, .crmc-actions button:hover { color: #10b981; border-color: var(--border, #e5e7eb); background: rgba(16,185,129,.07); }
.crmc-actions button.del:hover { color: #dc2626; border-color: rgba(220,38,38,.2); background: #fef2f2; }

/* ══ VUE GRILLE ══════════════════════════════════════════════ */
.crmc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 14px; }
.crmc-card { background: var(--surface, #fff); border-radius: 14px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; transition: all .2s; display: flex; flex-direction: column; }
.crmc-card:hover { border-color: #10b981; box-shadow: 0 4px 20px rgba(16,185,129,.1); transform: translateY(-2px); }
.crmc-card-header { padding: 16px 16px 12px; display: flex; align-items: flex-start; gap: 12px; }
.crmc-card-avatar { width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .85rem; font-weight: 800; flex-shrink: 0; color: #fff; }
.crmc-card-info { flex: 1; min-width: 0; }
.crmc-card-name { font-size: .9rem; font-weight: 700; color: var(--text, #111827); text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.crmc-card-name:hover { color: #10b981; }
.crmc-card-email { font-size: .72rem; color: var(--text-3, #9ca3af); display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin-top: 2px; }
.crmc-card-badges { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 6px; }
.crmc-card-body { padding: 0 16px 12px; font-size: .75rem; color: var(--text-2, #6b7280); display: flex; flex-direction: column; gap: 5px; flex: 1; }
.crmc-card-row { display: flex; align-items: center; gap: 6px; }
.crmc-card-row i { font-size: .65rem; color: var(--text-3, #9ca3af); width: 12px; flex-shrink: 0; }
.crmc-card-footer { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-top: 1px solid var(--border, #f3f4f6); }

/* ─── Masquage vues ─── */
.crmc-list-view .crmc-grid-wrap { display: none !important; }
.crmc-grid-view .crmc-list-wrap { display: none !important; }

/* ─── Pagination ─── */
.crmc-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-top: 1px solid var(--border, #e5e7eb); font-size: .78rem; color: var(--text-3, #9ca3af); }
.crmc-pagination a { padding: 6px 12px; border: 1px solid var(--border, #e5e7eb); border-radius: 10px; color: var(--text-2, #6b7280); text-decoration: none; font-weight: 600; transition: all .15s; font-size: .78rem; }
.crmc-pagination a:hover { border-color: #10b981; color: #10b981; }
.crmc-pagination a.active { background: #10b981; color: #fff; border-color: #10b981; }

/* ─── Flash / Empty ─── */
.crmc-flash { padding: 12px 18px; border-radius: 10px; font-size: .85rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; animation: crmcFlashIn .3s; }
.crmc-flash.success { background: #d1fae5; color: #059669; border: 1px solid rgba(5,150,105,.12); }
.crmc-flash.error   { background: #fef2f2; color: #dc2626; border: 1px solid rgba(220,38,38,.12); }
@keyframes crmcFlashIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }
.crmc-empty { text-align: center; padding: 60px 20px; color: var(--text-3, #9ca3af); }
.crmc-empty i { font-size: 2.5rem; opacity: .2; margin-bottom: 12px; display: block; }
.crmc-empty h3 { color: var(--text-2, #6b7280); font-size: 1rem; font-weight: 600; margin-bottom: 6px; }
.crmc-empty a { color: #10b981; }

@media (max-width: 1200px) { .crmc-table .col-hide { display: none; } }
@media (max-width: 960px) {
    .crmc-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .crmc-toolbar { flex-direction: column; align-items: flex-start; }
    .crmc-table-wrap { overflow-x: auto; }
    .crmc-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
}
</style>

<div class="crmc-wrap" id="crmcWrap">

<?php if ($flash === 'deleted'): ?>
    <div class="crmc-flash success"><i class="fas fa-check-circle"></i> Contact supprimé avec succès</div>
<?php elseif ($flash === 'created'): ?>
    <div class="crmc-flash success"><i class="fas fa-check-circle"></i> Contact créé avec succès</div>
<?php elseif ($flash === 'updated'): ?>
    <div class="crmc-flash success"><i class="fas fa-check-circle"></i> Contact mis à jour</div>
<?php elseif ($flash === 'error'): ?>
    <div class="crmc-flash error"><i class="fas fa-exclamation-circle"></i> Une erreur est survenue</div>
<?php endif; ?>

<?php if (!$tableExists): ?>
<div style="background:#fef2f2;border:1px solid rgba(220,38,38,.12);border-radius:12px;padding:28px;text-align:center;color:#dc2626">
    <i class="fas fa-database" style="font-size:2rem;margin-bottom:10px;display:block"></i>
    <h3 style="font-size:1rem;font-weight:700;margin-bottom:6px">Table contacts introuvable</h3>
    <p style="font-size:.83rem;opacity:.75">Vérifiez que la table <code>contacts</code> existe dans votre base de données.</p>
</div>
<?php else: ?>

<!-- ─── Alerte followup ─── -->
<?php if ($stats['followup_today'] > 0): ?>
<div class="crmc-followup-alert">
    <i class="fas fa-bell"></i>
    <span><?= $stats['followup_today'] ?> contact(s) à relancer aujourd'hui</span>
    <a href="?page=crm/contacts&followup=today" style="margin-left:auto;font-size:.75rem">Voir →</a>
</div>
<?php endif; ?>

<!-- ─── Banner ─── -->
<div class="crmc-banner">
    <div class="crmc-banner-left">
        <h2><i class="fas fa-address-book"></i> Contacts & Prospects</h2>
        <p>Contacts et prospects capturés via formulaires, estimation ou landing pages</p>
    </div>
    <div class="crmc-stats">
        <div class="crmc-stat"><div class="num gray"><?= $stats['total'] ?></div><div class="lbl">Total</div></div>
        <div class="crmc-stat"><div class="num green"><?= $stats['active'] ?></div><div class="lbl">Actifs</div></div>
        <div class="crmc-stat"><div class="num blue"><?= $stats['prospect'] ?></div><div class="lbl">Prospects</div></div>
        <div class="crmc-stat"><div class="num indigo"><?= $stats['client'] ?></div><div class="lbl">Clients</div></div>
        <?php if ($stats['vip'] > 0): ?>
        <div class="crmc-stat"><div class="num amber"><?= $stats['vip'] ?></div><div class="lbl">VIP</div></div>
        <?php endif; ?>
    </div>
</div>

<!-- ─── Toolbar ─── -->
<div class="crmc-toolbar">
    <div class="crmc-filters">
        <?php
        $filters = [
            'all'      => ['icon' => 'fa-users',        'label' => 'Tous',      'count' => $stats['total']],
            'active'   => ['icon' => 'fa-user-check',   'label' => 'Actifs',    'count' => $stats['active']],
            'vip'      => ['icon' => 'fa-star',         'label' => 'VIP',       'count' => $stats['vip']],
            'inactive' => ['icon' => 'fa-user-times',   'label' => 'Inactifs',  'count' => 0],
            'blacklist'=> ['icon' => 'fa-ban',          'label' => 'Blacklist', 'count' => 0],
        ];
        foreach ($filters as $key => $f):
            $active = ($filterStatus === $key) ? ' active' : '';
            $url = '?page=crm/contacts' . ($key !== 'all' ? '&status='.$key : '');
            if ($searchQuery)              $url .= '&q='.urlencode($searchQuery);
            if ($filterCategory !== 'all') $url .= '&category='.urlencode($filterCategory);
            if ($filterSource !== 'all')   $url .= '&source='.urlencode($filterSource);
        ?>
        <a href="<?= $url ?>" class="crmc-fbtn<?= $active ?>">
            <i class="fas <?= $f['icon'] ?>"></i> <?= $f['label'] ?>
            <?php if ($key === 'all' || $f['count'] > 0): ?>
            <span class="badge"><?= (int)$f['count'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="crmc-toolbar-r">
        <div class="crmc-view-toggle">
            <button class="crmc-view-btn active" id="crmcBtnList" onclick="CRMC.setView('list')" title="Vue liste"><i class="fas fa-list"></i></button>
            <button class="crmc-view-btn"         id="crmcBtnGrid" onclick="CRMC.setView('grid')" title="Vue grille"><i class="fas fa-th-large"></i></button>
        </div>
        <form class="crmc-search" method="GET">
            <input type="hidden" name="page" value="crm/contacts">
            <?php if ($filterStatus !== 'all'):   ?><input type="hidden" name="status"   value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
            <?php if ($filterCategory !== 'all'): ?><input type="hidden" name="category" value="<?= htmlspecialchars($filterCategory) ?>"><?php endif; ?>
            <?php if ($filterSource !== 'all'):   ?><input type="hidden" name="source"   value="<?= htmlspecialchars($filterSource) ?>"><?php endif; ?>
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Nom, email, ville, tag…" value="<?= htmlspecialchars($searchQuery) ?>">
        </form>
        <a href="?page=crm/contacts&action=export" class="crmc-btn crmc-btn-export" title="Exporter CSV"><i class="fas fa-download"></i> Export</a>
        <a href="?page=crm/contacts&action=create" class="crmc-btn crmc-btn-primary"><i class="fas fa-user-plus"></i> Nouveau contact</a>
    </div>
</div>

<!-- ─── Sub-filtres ─── -->
<?php if (!empty($categoriesList) || !empty($sourcesList)): ?>
<div class="crmc-subfilters">
    <?php if (!empty($categoriesList) && $hasCategory): ?>
    <div class="crmc-subfilter">
        <i class="fas fa-tag"></i>
        <select onchange="CRMC.filterBy('category', this.value)">
            <option value="all" <?= $filterCategory==='all'?'selected':'' ?>>Toutes catégories</option>
            <?php foreach ($categoriesList as $cat):
                $ci = getCategoryInfo($cat);
            ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= $filterCategory===$cat?'selected':'' ?>>
                <?= $ci['label'] ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <?php if (!empty($sourcesList) && $hasSource): ?>
    <div class="crmc-subfilter">
        <i class="fas fa-plug"></i>
        <select onchange="CRMC.filterBy('source', this.value)">
            <option value="all" <?= $filterSource==='all'?'selected':'' ?>>Toutes sources</option>
            <?php foreach ($sourcesList as $src): ?>
            <option value="<?= htmlspecialchars($src) ?>" <?= $filterSource===$src?'selected':'' ?>>
                <?= htmlspecialchars($src) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ─── Bulk actions ─── -->
<div class="crmc-bulk" id="crmcBulkBar">
    <input type="checkbox" id="crmcSelectAll" onchange="CRMC.toggleAll(this.checked)">
    <span id="crmcBulkCount">0</span> sélectionné(s)
    <select id="crmcBulkAction">
        <option value="">— Action groupée —</option>
        <option value="active">Activer</option>
        <option value="inactive">Désactiver</option>
        <option value="vip">Passer en VIP</option>
        <option value="blacklist">Blacklister</option>
        <option value="export">Exporter sélection</option>
        <option value="delete">Supprimer</option>
    </select>
    <button class="crmc-btn crmc-btn-sm crmc-btn-outline" onclick="CRMC.bulkExecute()"><i class="fas fa-check"></i> Appliquer</button>
</div>

<?php if (empty($contacts)): ?>
<div class="crmc-empty">
    <i class="fas fa-address-book"></i>
    <h3>Aucun contact trouvé</h3>
    <p>
        <?php if ($searchQuery || $filterCategory !== 'all' || $filterSource !== 'all'): ?>
            Aucun résultat. <a href="?page=crm/contacts">Effacer les filtres</a>
        <?php else: ?>
            Ajoutez votre premier contact ou attendez les captures de vos formulaires.
        <?php endif; ?>
    </p>
</div>
<?php else: ?>

<!-- ══ VUE LISTE ══════════════════════════════════════════════ -->
<div class="crmc-list-wrap">
    <div class="crmc-table-wrap">
        <table class="crmc-table">
            <thead>
                <tr>
                    <th style="width:32px"><input type="checkbox" onchange="CRMC.toggleAll(this.checked)"></th>
                    <th>Contact</th>
                    <?php if ($hasCategory): ?><th>Catégorie</th><?php endif; ?>
                    <?php if ($hasStatus):   ?><th>Statut</th><?php endif; ?>
                    <?php if ($hasTelephone || $hasPhone || $hasMobile): ?><th class="col-hide">Téléphone</th><?php endif; ?>
                    <?php if ($hasSource):  ?><th class="col-hide">Source</th><?php endif; ?>
                    <?php if ($hasRating):  ?><th class="center col-hide">Note</th><?php endif; ?>
                    <?php if ($hasNextFollowup): ?><th class="col-hide">Relance</th><?php endif; ?>
                    <?php if ($hasTags):    ?><th class="col-hide">Tags</th><?php endif; ?>
                    <th>Date</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($contacts as $c):
                $displayName = getContactDisplayName($c);
                $initials    = mb_strtoupper(mb_substr($displayName, 0, 1)) . (mb_substr($displayName, strpos($displayName, ' ') ?: 0, 2) !== '' ? mb_strtoupper(mb_substr(trim(strstr($displayName, ' ')), 0, 1)) : '');
                $colors      = ['#10b981','#3b82f6','#8b5cf6','#f59e0b','#ef4444','#0d9488','#6366f1'];
                $avatarColor = $colors[crc32($displayName) % count($colors)];
                $phone       = getContactPhone($c);
                $cat         = $c['category'] ?? '';
                $catInfo     = $cat ? getCategoryInfo($cat) : null;
                $status      = $c['status'] ?? '';
                $statusInfo  = $status ? getStatusInfo($status) : null;
                $source      = $c['source'] ?? '';
                $rating      = (int)($c['rating'] ?? 0);
                $tags        = $c['tags'] ?? '';
                $tagList     = $tags ? array_filter(array_map('trim', explode(',', $tags))) : [];
                $nextFollowup= $c['next_followup'] ?? null;
                $followupClass = '';
                $followupLabel = '';
                if ($nextFollowup) {
                    $diff = (int)((strtotime($nextFollowup) - time()) / 86400);
                    if ($diff < 0)     { $followupClass = 'overdue'; $followupLabel = 'En retard (' . abs($diff) . 'j)'; }
                    elseif ($diff === 0){ $followupClass = 'today';   $followupLabel = "Aujourd'hui"; }
                    else               { $followupClass = 'future';  $followupLabel = date('d/m/Y', strtotime($nextFollowup)); }
                }
                $date     = !empty($c['created_at']) ? date('d/m/Y', strtotime($c['created_at'])) : '—';
                $editUrl  = "?page=crm/contacts&action=edit&id={$c['id']}";
                $hasSocial= !empty($c['linkedin']) || !empty($c['facebook']) || !empty($c['instagram']) || !empty($c['website']);
            ?>
            <tr data-id="<?= (int)$c['id'] ?>">
                <td><input type="checkbox" class="crmc-cb" value="<?= (int)$c['id'] ?>" onchange="CRMC.updateBulk()"></td>

                <!-- Contact (avatar + nom + email) -->
                <td>
                    <div class="crmc-contact-cell">
                        <div class="crmc-avatar" style="background:<?= $avatarColor ?>">
                            <?= htmlspecialchars($initials ?: '?') ?>
                        </div>
                        <div>
                            <a href="<?= htmlspecialchars($editUrl) ?>" class="crmc-contact-name"><?= htmlspecialchars($displayName) ?></a>
                            <div class="crmc-contact-sub">
                                <?php if (!empty($c['email'])): ?>
                                    <span><?= htmlspecialchars($c['email']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($c['city'])): ?>
                                    <span>· <?= htmlspecialchars($c['city']) ?></span>
                                <?php endif; ?>
                                <?php if ($c['company'] ?? ''): ?>
                                    <span>· <?= htmlspecialchars($c['company']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>

                <!-- Catégorie -->
                <?php if ($hasCategory): ?>
                <td>
                    <?php if ($catInfo): ?>
                    <span class="crmc-cat" style="background:<?= $catInfo['bg'] ?>;color:<?= $catInfo['color'] ?>">
                        <i class="fas <?= $catInfo['icon'] ?>" style="font-size:.58rem"></i>
                        <?= $catInfo['label'] ?>
                    </span>
                    <?php else: ?>
                    <span style="color:var(--text-3,#9ca3af);font-size:.75rem">—</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>

                <!-- Statut -->
                <?php if ($hasStatus): ?>
                <td>
                    <?php if ($statusInfo): ?>
                    <span class="crmc-status" style="background:<?= $statusInfo['bg'] ?>;color:<?= $statusInfo['color'] ?>">
                        <?= $statusInfo['label'] ?>
                    </span>
                    <?php else: ?>
                    <span style="color:var(--text-3,#9ca3af);font-size:.75rem">—</span>
                    <?php endif; ?>
                </td>
                <?php endif; ?>

                <!-- Téléphone -->
                <?php if ($hasTelephone || $hasPhone || $hasMobile): ?>
                <td class="col-hide">
                    <?php if ($phone): ?>
                    <div class="crmc-phone">
                        <i class="fas fa-phone"></i>
                        <a href="tel:<?= htmlspecialchars($phone) ?>" style="color:inherit;text-decoration:none"><?= htmlspecialchars($phone) ?></a>
                    </div>
                    <?php else: ?><span style="color:var(--text-3,#9ca3af);font-size:.75rem">—</span><?php endif; ?>
                </td>
                <?php endif; ?>

                <!-- Source -->
                <?php if ($hasSource): ?>
                <td class="col-hide">
                    <?php if ($source): ?>
                    <div class="crmc-source">
                        <i class="fab <?= getSourceIcon($source) ?>"></i>
                        <span><?= htmlspecialchars($source) ?></span>
                    </div>
                    <?php else: ?><span style="color:var(--text-3,#9ca3af);font-size:.75rem">—</span><?php endif; ?>
                </td>
                <?php endif; ?>

                <!-- Note étoiles -->
                <?php if ($hasRating): ?>
                <td class="center col-hide"><?= renderStars($rating) ?></td>
                <?php endif; ?>

                <!-- Prochaine relance -->
                <?php if ($hasNextFollowup): ?>
                <td class="col-hide">
                    <?php if ($followupLabel): ?>
                    <span class="crmc-followup <?= $followupClass ?>">
                        <?php if ($followupClass === 'overdue'): ?><i class="fas fa-exclamation-circle"></i> <?php endif; ?>
                        <?= $followupLabel ?>
                    </span>
                    <?php else: ?><span style="color:var(--text-3,#9ca3af);font-size:.75rem">—</span><?php endif; ?>
                </td>
                <?php endif; ?>

                <!-- Tags -->
                <?php if ($hasTags): ?>
                <td class="col-hide">
                    <?php if ($tagList): ?>
                    <div class="crmc-tags">
                        <?php foreach (array_slice($tagList, 0, 3) as $tag): ?>
                        <span class="crmc-tag"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($tagList) > 3): ?>
                        <span class="crmc-tag" style="background:rgba(107,114,128,.1);color:#6b7280">+<?= count($tagList)-3 ?></span>
                        <?php endif; ?>
                    </div>
                    <?php else: ?><span style="color:var(--text-3,#9ca3af);font-size:.75rem">—</span><?php endif; ?>
                </td>
                <?php endif; ?>

                <!-- Date -->
                <td><span class="crmc-date"><?= $date ?></span></td>

                <!-- Actions -->
                <td>
                    <div class="crmc-actions">
                        <a href="<?= htmlspecialchars($editUrl) ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                        <?php if (!empty($c['email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($c['email']) ?>" title="Envoyer un email"><i class="fas fa-envelope"></i></a>
                        <?php endif; ?>
                        <?php if ($phone): ?>
                        <a href="tel:<?= htmlspecialchars($phone) ?>" title="Appeler"><i class="fas fa-phone"></i></a>
                        <?php endif; ?>
                        <button onclick="CRMC.deleteContact(<?= (int)$c['id'] ?>, '<?= addslashes(htmlspecialchars($displayName)) ?>')" class="del" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
        <div class="crmc-pagination">
            <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?> contacts</span>
            <div style="display:flex;gap:4px">
                <?php for ($i=1; $i<=$totalPages; $i++):
                    $pUrl = '?page=crm/contacts&p='.$i;
                    if ($filterStatus!=='all')   $pUrl .= '&status='.$filterStatus;
                    if ($filterCategory!=='all') $pUrl .= '&category='.urlencode($filterCategory);
                    if ($filterSource!=='all')   $pUrl .= '&source='.urlencode($filterSource);
                    if ($searchQuery)             $pUrl .= '&q='.urlencode($searchQuery);
                ?>
                <a href="<?= $pUrl ?>" class="<?= $i===$currentPage?'active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ══ VUE GRILLE ══════════════════════════════════════════════ -->
<div class="crmc-grid-wrap">
    <div class="crmc-grid">
    <?php foreach ($contacts as $c):
        $displayName = getContactDisplayName($c);
        $initials    = mb_strtoupper(mb_substr($displayName, 0, 1)) . (strpos($displayName, ' ') !== false ? mb_strtoupper(mb_substr(trim(strstr($displayName, ' ')), 0, 1)) : '');
        $colors      = ['#10b981','#3b82f6','#8b5cf6','#f59e0b','#ef4444','#0d9488','#6366f1'];
        $avatarColor = $colors[crc32($displayName) % count($colors)];
        $phone       = getContactPhone($c);
        $cat         = $c['category'] ?? '';
        $catInfo     = $cat ? getCategoryInfo($cat) : null;
        $status      = $c['status'] ?? '';
        $statusInfo  = $status ? getStatusInfo($status) : null;
        $source      = $c['source'] ?? '';
        $rating      = (int)($c['rating'] ?? 0);
        $tags        = $c['tags'] ?? '';
        $tagList     = $tags ? array_filter(array_map('trim', explode(',', $tags))) : [];
        $date        = !empty($c['created_at']) ? date('d/m/Y', strtotime($c['created_at'])) : '—';
        $editUrl     = "?page=crm/contacts&action=edit&id={$c['id']}";
    ?>
    <div class="crmc-card" data-id="<?= (int)$c['id'] ?>">
        <div class="crmc-card-header">
            <div class="crmc-card-avatar" style="background:<?= $avatarColor ?>"><?= htmlspecialchars($initials ?: '?') ?></div>
            <div class="crmc-card-info">
                <a href="<?= htmlspecialchars($editUrl) ?>" class="crmc-card-name"><?= htmlspecialchars($displayName) ?></a>
                <span class="crmc-card-email"><?= htmlspecialchars($c['email'] ?? '') ?></span>
                <div class="crmc-card-badges">
                    <?php if ($catInfo): ?>
                    <span class="crmc-cat" style="background:<?= $catInfo['bg'] ?>;color:<?= $catInfo['color'] ?>;font-size:.58rem;padding:2px 7px">
                        <?= $catInfo['label'] ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($statusInfo): ?>
                    <span class="crmc-status" style="background:<?= $statusInfo['bg'] ?>;color:<?= $statusInfo['color'] ?>;font-size:.58rem;padding:2px 7px">
                        <?= $statusInfo['label'] ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="crmc-card-body">
            <?php if ($phone): ?>
            <div class="crmc-card-row"><i class="fas fa-phone"></i><a href="tel:<?= htmlspecialchars($phone) ?>" style="color:inherit;text-decoration:none"><?= htmlspecialchars($phone) ?></a></div>
            <?php endif; ?>
            <?php if (!empty($c['city'])): ?>
            <div class="crmc-card-row"><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($c['city']) ?></div>
            <?php endif; ?>
            <?php if (!empty($c['company'])): ?>
            <div class="crmc-card-row"><i class="fas fa-building"></i><?= htmlspecialchars($c['company']) ?></div>
            <?php endif; ?>
            <?php if ($source): ?>
            <div class="crmc-card-row"><i class="fab <?= getSourceIcon($source) ?>"></i><?= htmlspecialchars($source) ?></div>
            <?php endif; ?>
            <?php if ($tagList): ?>
            <div class="crmc-tags" style="margin-top:2px">
                <?php foreach (array_slice($tagList, 0, 3) as $tag): ?>
                <span class="crmc-tag"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if ($hasRating && $rating > 0): ?>
            <div style="margin-top:2px"><?= renderStars($rating) ?></div>
            <?php endif; ?>
        </div>
        <div class="crmc-card-footer">
            <span class="crmc-date" style="font-size:.68rem"><?= $date ?></span>
            <div class="crmc-actions" style="justify-content:flex-end">
                <a href="<?= htmlspecialchars($editUrl) ?>" title="Modifier"><i class="fas fa-edit"></i></a>
                <?php if (!empty($c['email'])): ?>
                <a href="mailto:<?= htmlspecialchars($c['email']) ?>" title="Email"><i class="fas fa-envelope"></i></a>
                <?php endif; ?>
                <?php if ($phone): ?>
                <a href="tel:<?= htmlspecialchars($phone) ?>" title="Appeler"><i class="fas fa-phone"></i></a>
                <?php endif; ?>
                <button onclick="CRMC.deleteContact(<?= (int)$c['id'] ?>, '<?= addslashes(htmlspecialchars($displayName)) ?>')" class="del" title="Supprimer"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="crmc-pagination" style="background:var(--surface,#fff);border-radius:12px;border:1px solid var(--border,#e5e7eb);margin-top:12px">
        <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?> contacts</span>
        <div style="display:flex;gap:4px">
            <?php for ($i=1; $i<=$totalPages; $i++):
                $pUrl = '?page=crm/contacts&p='.$i;
                if ($filterStatus!=='all')   $pUrl .= '&status='.$filterStatus;
                if ($filterCategory!=='all') $pUrl .= '&category='.urlencode($filterCategory);
                if ($filterSource!=='all')   $pUrl .= '&source='.urlencode($filterSource);
                if ($searchQuery)             $pUrl .= '&q='.urlencode($searchQuery);
            ?>
            <a href="<?= $pUrl ?>" class="<?= $i===$currentPage?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>
<?php endif; ?>
</div><!-- /crmc-wrap -->

<!-- ══ MODAL CUSTOM ══════════════════════════════════════════ -->
<div id="crmcModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    <div onclick="CRMC.modalClose()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px)"></div>
    <div id="crmcModalBox" style="position:relative;z-index:1;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:420px;margin:16px;overflow:hidden;transform:scale(.94) translateY(8px);transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .15s;opacity:0;">
        <div id="crmcModalHeader" style="padding:20px 22px 16px;display:flex;align-items:flex-start;gap:14px;">
            <div id="crmcModalIcon" style="width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;"></div>
            <div style="flex:1;min-width:0;">
                <div id="crmcModalTitle" style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:5px;"></div>
                <div id="crmcModalMsg"   style="font-size:.82rem;color:#6b7280;line-height:1.5;"></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding:12px 20px 18px;border-top:1px solid #f3f4f6;">
            <button onclick="CRMC.modalClose()" style="padding:9px 20px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:.83rem;font-weight:600;cursor:pointer;font-family:inherit;" onmouseover="this.style.borderColor='#10b981';this.style.color='#10b981'" onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#374151'">Annuler</button>
            <button id="crmcModalConfirm" style="padding:9px 20px;border-radius:10px;border:none;font-size:.83rem;font-weight:700;cursor:pointer;font-family:inherit;color:#fff;"></button>
        </div>
    </div>
</div>

<script>
const CRMC = {
    apiUrl: '/admin/modules/crm/contacts/api.php',
    _modalCb: null,

    filterBy(key, value) {
        const url = new URL(window.location.href);
        value === 'all' ? url.searchParams.delete(key) : url.searchParams.set(key, value);
        url.searchParams.delete('p');
        window.location.href = url.toString();
    },

    setView(v) {
        const wrap = document.getElementById('crmcWrap');
        wrap.classList.remove('crmc-list-view', 'crmc-grid-view');
        wrap.classList.add(v === 'grid' ? 'crmc-grid-view' : 'crmc-list-view');
        document.getElementById('crmcBtnList').classList.toggle('active', v !== 'grid');
        document.getElementById('crmcBtnGrid').classList.toggle('active', v === 'grid');
        try { sessionStorage.setItem('crmc_view', v); } catch(e) {}
    },
    initView() {
        let v = 'list';
        try { v = sessionStorage.getItem('crmc_view') || 'list'; } catch(e) {}
        this.setView(v);
    },

    toggleAll(checked) {
        document.querySelectorAll('.crmc-cb').forEach(cb => cb.checked = checked);
        this.updateBulk();
    },
    updateBulk() {
        const checked = document.querySelectorAll('.crmc-cb:checked');
        document.getElementById('crmcBulkCount').textContent = checked.length;
        document.getElementById('crmcBulkBar').classList.toggle('active', checked.length > 0);
    },
    async bulkExecute() {
        const action = document.getElementById('crmcBulkAction').value;
        if (!action) return;
        const ids = [...document.querySelectorAll('.crmc-cb:checked')].map(cb => parseInt(cb.value));
        if (!ids.length) return;
        if (action === 'delete') {
            this.modal({
                icon: '<i class="fas fa-trash"></i>', iconBg: '#fef2f2', iconColor: '#dc2626',
                title: `Supprimer ${ids.length} contact(s) ?`,
                msg: 'Cette action est irréversible.',
                confirmLabel: 'Supprimer', confirmColor: '#dc2626',
                onConfirm: async () => {
                    const fd = new FormData();
                    fd.append('action', 'bulk_delete'); fd.append('ids', JSON.stringify(ids));
                    const r = await fetch(this.apiUrl, {method:'POST',body:fd});
                    const d = await r.json();
                    d.success ? location.reload() : this.toast(d.error||'Erreur','error');
                }
            });
            return;
        }
        if (action === 'export') {
            window.location.href = this.apiUrl + '?action=export&ids=' + ids.join(',');
            return;
        }
        const fd = new FormData();
        fd.append('action', 'bulk_status'); fd.append('status', action); fd.append('ids', JSON.stringify(ids));
        const r = await fetch(this.apiUrl, {method:'POST',body:fd});
        const d = await r.json();
        d.success ? location.reload() : this.toast(d.error||'Erreur','error');
    },

    modal({ icon, iconBg, iconColor, title, msg, confirmLabel, confirmColor, onConfirm }) {
        const el  = document.getElementById('crmcModal');
        const box = document.getElementById('crmcModalBox');
        document.getElementById('crmcModalIcon').innerHTML      = icon;
        document.getElementById('crmcModalIcon').style.background = iconBg;
        document.getElementById('crmcModalIcon').style.color      = iconColor;
        document.getElementById('crmcModalHeader').style.background = iconBg + '33';
        document.getElementById('crmcModalTitle').textContent   = title;
        document.getElementById('crmcModalMsg').innerHTML       = msg;
        const btn = document.getElementById('crmcModalConfirm');
        btn.textContent      = confirmLabel || 'Confirmer';
        btn.style.background = confirmColor || '#10b981';
        btn.onmouseover = () => btn.style.filter = 'brightness(.88)';
        btn.onmouseout  = () => btn.style.filter = '';
        this._modalCb = onConfirm;
        btn.onclick = () => { this.modalClose(); if (this._modalCb) this._modalCb(); };
        el.style.display = 'flex';
        requestAnimationFrame(() => { box.style.opacity='1'; box.style.transform='scale(1) translateY(0)'; });
        document.addEventListener('keydown', this._escHandler);
    },
    modalClose() {
        const el  = document.getElementById('crmcModal');
        const box = document.getElementById('crmcModalBox');
        box.style.opacity='0'; box.style.transform='scale(.94) translateY(8px)';
        setTimeout(() => el.style.display='none', 160);
        document.removeEventListener('keydown', this._escHandler);
    },
    _escHandler(e) { if (e.key === 'Escape') CRMC.modalClose(); },

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

    deleteContact(id, name) {
        this.modal({
            icon: '<i class="fas fa-trash"></i>', iconBg: '#fef2f2', iconColor: '#dc2626',
            title: 'Supprimer ce contact ?',
            msg: `<strong>${name}</strong> sera supprimé définitivement.<br><span style="font-size:.78rem;color:#9ca3af">Cette action est irréversible.</span>`,
            confirmLabel: 'Supprimer', confirmColor: '#dc2626',
            onConfirm: async () => {
                const fd = new FormData();
                fd.append('action', 'delete'); fd.append('id', id);
                try {
                    const r = await fetch(this.apiUrl, {method:'POST',body:fd});
                    const d = await r.json();
                    if (d.success) {
                        document.querySelectorAll(`[data-id="${id}"]`).forEach(el => {
                            el.style.cssText = 'opacity:0;transform:scale(.95);transition:all .3s';
                            setTimeout(() => el.remove(), 300);
                        });
                        this.toast('Contact supprimé', 'success');
                    } else { this.toast(d.error||'Erreur','error'); }
                } catch(e) { this.toast('Erreur réseau','error'); }
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => CRMC.initView());
</script>