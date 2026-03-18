<?php
/**
 * ══════════════════════════════════════════════════════════════════════
 * MODULE ADS-LAUNCH — Lancement Publicitaire (Méthode BizzBizz.io)
 * /admin/modules/ads-launch/index.php  — v2.0 (pattern pages v1.0)
 * ══════════════════════════════════════════════════════════════════════
 */

// ─── Connexion DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(__DIR__)) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

// ─── Service Ads ───
$accounts   = [];
$serviceFile = __DIR__ . '/AdsLaunchService.php';
if (file_exists($serviceFile)) {
    require_once $serviceFile;
    try {
        $ads      = new AdsLaunchService($db, $_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0);
        $accounts = $ads->getAccounts();
    } catch (Exception $e) { $accounts = []; }
}

// ─── Stats globales ───
$stats = [
    'total'    => count($accounts),
    'active'   => count(array_filter($accounts, fn($a) => ($a['status'] ?? '') === 'active')),
    'paused'   => count(array_filter($accounts, fn($a) => ($a['status'] ?? '') === 'paused')),
    'campaigns'=> 0,
    'budget'   => 0,
];
// Enrichir depuis la DB si les tables existent
try {
    $stats['campaigns'] = (int)$pdo->query("SELECT COUNT(*) FROM ads_campaigns")->fetchColumn();
} catch (PDOException $e) {}
try {
    $stats['budget'] = (float)$pdo->query("SELECT COALESCE(SUM(daily_budget),0) FROM ads_campaigns WHERE status='active'")->fetchColumn();
} catch (PDOException $e) {}

// ─── CSRF ───
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Tab active (persistée en JS, PHP sert de fallback) ───
$activeTab   = $_GET['tab'] ?? 'checklist';
$allowedTabs = ['checklist', 'prerequisites', 'audiences', 'campaigns', 'analytics'];
if (!in_array($activeTab, $allowedTabs)) $activeTab = 'checklist';

// ─── Checklist items (dynamiques selon compte sélectionné) ───
$checklist = [
    ['key' => 'tech',       'title' => '1. Prérequis Techniques',     'desc' => 'Pixel Facebook, GTM, domaine vérifié',     'done' => false],
    ['key' => 'account',    'title' => '2. Structure du Compte',      'desc' => 'Business Manager & Compte Ads configuré',  'done' => false],
    ['key' => 'audiences',  'title' => '3. Audiences Stratégiques',   'desc' => 'CI, LAL 180j, TNT créées',                 'done' => false],
    ['key' => 'campaigns',  'title' => '4. Campagnes & Nomenclature', 'desc' => 'Structure Cold / Warm / Hot en place',     'done' => false],
    ['key' => 'analytics',  'title' => '5. Optimisation & Suivi',     'desc' => 'KPIs définis, alertes budget actives',     'done' => false],
];
$checklistDone  = count(array_filter($checklist, fn($i) => $i['done']));
$checklistTotal = count($checklist);
$checklistPct   = $checklistTotal > 0 ? round($checklistDone / $checklistTotal * 100) : 0;

// ─── Flash ───
$flash = $_GET['msg'] ?? '';
?>

<style>
/* ══════════════════════════════════════════════════════════════
   ADS-LAUNCH MODULE v2.0  — namespace : adm-
   Calqué sur pages v1.0
══════════════════════════════════════════════════════════════ */
.adm-wrap { font-family: var(--font, 'Inter', sans-serif); }

/* ─── Banner ─── */
.adm-banner {
    background: var(--surface, #fff);
    border-radius: 16px; padding: 26px 30px; margin-bottom: 22px;
    display: flex; align-items: center; justify-content: space-between;
    border: 1px solid var(--border, #e5e7eb); position: relative; overflow: hidden;
}
.adm-banner::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #1877f2, #e91e63, #ff9800);
}
.adm-banner::after {
    content: ''; position: absolute; top: -40%; right: -5%;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(24,119,242,.05), transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.adm-banner-left { position: relative; z-index: 1; }
.adm-banner-left h2 { font-size: 1.35rem; font-weight: 700; color: var(--text, #111827); margin: 0 0 4px; display: flex; align-items: center; gap: 10px; letter-spacing: -.02em; }
.adm-banner-left h2 i { font-size: 16px; color: #1877f2; }
.adm-banner-left p { color: var(--text-2, #6b7280); font-size: .85rem; margin: 0; }
.adm-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.adm-stat { text-align: center; padding: 10px 16px; background: var(--surface-2, #f9fafb); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); min-width: 72px; transition: all .2s; }
.adm-stat:hover { border-color: var(--border-h, #d1d5db); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.adm-stat .num { font-size: 1.45rem; font-weight: 800; line-height: 1; color: var(--text, #111827); letter-spacing: -.03em; }
.adm-stat .num.blue   { color: #1877f2; }
.adm-stat .num.green  { color: #10b981; }
.adm-stat .num.amber  { color: #f59e0b; }
.adm-stat .num.violet { color: #7c3aed; }
.adm-stat .lbl { font-size: .58rem; color: var(--text-3, #9ca3af); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }

/* ─── Toolbar ─── */
.adm-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.adm-toolbar-l { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.adm-toolbar-r { display: flex; align-items: center; gap: 10px; }

/* ─── Select compte ─── */
.adm-account-select {
    padding: 8px 14px; border: 1px solid var(--border, #e5e7eb);
    border-radius: 10px; font-size: .82rem; font-family: inherit;
    min-width: 260px; background: var(--surface, #fff);
    color: var(--text, #111827); cursor: pointer; transition: all .2s;
}
.adm-account-select:focus { outline: none; border-color: #1877f2; box-shadow: 0 0 0 3px rgba(24,119,242,.1); }

/* ─── Tabs ─── */
.adm-tabs { display: flex; gap: 3px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 12px; padding: 4px; margin-bottom: 20px; flex-wrap: wrap; }
.adm-tab { padding: 8px 18px; border: none; background: transparent; color: var(--text-2, #6b7280); font-size: .8rem; font-weight: 600; border-radius: 8px; cursor: pointer; transition: all .15s; font-family: inherit; display: flex; align-items: center; gap: 6px; }
.adm-tab:hover { color: var(--text, #111827); background: var(--surface-2, #f9fafb); }
.adm-tab.active { background: #1877f2; color: #fff; box-shadow: 0 1px 4px rgba(24,119,242,.25); }

/* ─── Sections ─── */
.adm-section { display: none; }
.adm-section.active { display: block; }

/* ─── Cards ─── */
.adm-card { background: var(--surface, #fff); border-radius: 14px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; margin-bottom: 16px; }
.adm-card-header { padding: 18px 22px 14px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border, #f3f4f6); }
.adm-card-header h3 { font-size: .95rem; font-weight: 700; color: var(--text, #111827); margin: 0; display: flex; align-items: center; gap: 8px; }
.adm-card-header h3 i { color: #1877f2; font-size: .85rem; }
.adm-card-body { padding: 20px 22px; }

/* ─── Progress bar ─── */
.adm-progress-wrap { margin-bottom: 20px; }
.adm-progress-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; font-size: .78rem; font-weight: 600; color: var(--text-2, #6b7280); }
.adm-progress-bar { height: 6px; background: var(--surface-2, #f3f4f6); border-radius: 3px; overflow: hidden; }
.adm-progress-fill { height: 100%; border-radius: 3px; background: #1877f2; transition: width .5s cubic-bezier(.4,0,.2,1); }
.adm-progress-fill.complete { background: #10b981; }

/* ─── Checklist row ─── */
.adm-check-row { display: flex; align-items: center; gap: 14px; padding: 14px 0; border-bottom: 1px solid var(--border, #f3f4f6); transition: background .1s; }
.adm-check-row:last-child { border-bottom: none; padding-bottom: 0; }
.adm-check-row:hover { background: rgba(24,119,242,.02); margin: 0 -22px; padding-left: 22px; padding-right: 22px; }
.adm-check-icon { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: .75rem; transition: all .2s; }
.adm-check-icon.done { background: #d1fae5; color: #059669; }
.adm-check-icon.todo { background: var(--surface-2, #f3f4f6); color: var(--text-3, #9ca3af); }
.adm-check-text { flex: 1; }
.adm-check-text strong { font-size: .88rem; font-weight: 600; color: var(--text, #111827); display: block; }
.adm-check-text span { font-size: .75rem; color: var(--text-3, #9ca3af); margin-top: 2px; display: block; }
.adm-badge-status { padding: 3px 10px; border-radius: 12px; font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; white-space: nowrap; }
.adm-badge-done { background: #d1fae5; color: #059669; }
.adm-badge-todo { background: var(--surface-2, #f3f4f6); color: var(--text-3, #9ca3af); }

/* ─── Grid 3 cols ─── */
.adm-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
.adm-audience-card { background: var(--surface-2, #f9fafb); border: 1px solid var(--border, #e5e7eb); border-radius: 14px; padding: 24px 16px; text-align: center; transition: all .2s; }
.adm-audience-card:hover { border-color: #1877f2; background: rgba(24,119,242,.02); transform: translateY(-2px); box-shadow: 0 4px 16px rgba(24,119,242,.1); }
.adm-audience-icon { width: 52px; height: 52px; border-radius: 14px; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
.adm-audience-name { font-size: .88rem; font-weight: 700; color: var(--text, #111827); display: block; margin-bottom: 4px; }
.adm-audience-desc { font-size: .73rem; color: var(--text-3, #9ca3af); }

/* ─── Form elements ─── */
.adm-form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 14px; margin-bottom: 16px; }
.adm-form-group { display: flex; flex-direction: column; gap: 5px; }
.adm-form-group label { font-size: .73rem; font-weight: 600; color: var(--text-2, #6b7280); text-transform: uppercase; letter-spacing: .04em; }
.adm-form-group input, .adm-form-group select { padding: 9px 12px; border: 1px solid var(--border, #e5e7eb); border-radius: 10px; font-size: .83rem; font-family: inherit; background: var(--surface, #fff); color: var(--text, #111827); transition: all .2s; }
.adm-form-group input:focus, .adm-form-group select:focus { outline: none; border-color: #1877f2; box-shadow: 0 0 0 3px rgba(24,119,242,.1); }
.adm-form-group input[readonly] { background: var(--surface-2, #f9fafb); color: var(--text-2, #6b7280); cursor: default; font-family: monospace; font-size: .8rem; }

/* ─── Nomenclature preview ─── */
.adm-nomenclature-preview { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 10px; padding: 14px 18px; margin-top: 14px; display: none; }
.adm-nomenclature-preview.show { display: block; }
.adm-nomenclature-preview .label { font-size: .65rem; font-weight: 700; color: #3b82f6; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 4px; }
.adm-nomenclature-preview .name { font-family: monospace; font-size: .95rem; font-weight: 700; color: #1e40af; word-break: break-all; }

/* ─── KPIs analytics ─── */
.adm-kpi-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; margin-bottom: 20px; }
.adm-kpi { background: var(--surface-2, #f9fafb); border: 1px solid var(--border, #e5e7eb); border-radius: 12px; padding: 16px; text-align: center; }
.adm-kpi .kpi-val { font-size: 1.4rem; font-weight: 800; color: var(--text, #111827); letter-spacing: -.03em; }
.adm-kpi .kpi-val.blue  { color: #1877f2; }
.adm-kpi .kpi-val.green { color: #10b981; }
.adm-kpi .kpi-val.amber { color: #f59e0b; }
.adm-kpi .kpi-lbl { font-size: .63rem; color: var(--text-3, #9ca3af); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }

/* ─── Btns ─── */
.adm-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 10px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; font-family: inherit; }
.adm-btn-primary { background: #1877f2; color: #fff; box-shadow: 0 1px 4px rgba(24,119,242,.22); }
.adm-btn-primary:hover { background: #1462c8; transform: translateY(-1px); }
.adm-btn-secondary { background: var(--surface, #fff); color: var(--text-2, #6b7280); border: 1px solid var(--border, #e5e7eb); }
.adm-btn-secondary:hover { border-color: #1877f2; color: #1877f2; }
.adm-btn-sm { padding: 5px 12px; font-size: .75rem; }
.adm-btn-success { background: #10b981; color: #fff; }
.adm-btn-success:hover { background: #059669; transform: translateY(-1px); }

/* ─── Empty state ─── */
.adm-empty { text-align: center; padding: 50px 20px; color: var(--text-3, #9ca3af); }
.adm-empty i { font-size: 2.2rem; opacity: .2; margin-bottom: 12px; display: block; }
.adm-empty h3 { color: var(--text-2, #6b7280); font-size: .95rem; font-weight: 600; margin-bottom: 6px; }

/* ─── Flash ─── */
.adm-flash { padding: 12px 18px; border-radius: 10px; font-size: .85rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; animation: admFlashIn .3s; }
.adm-flash.success { background: #d1fae5; color: #059669; border: 1px solid rgba(5,150,105,.12); }
.adm-flash.error   { background: #fef2f2; color: #dc2626; border: 1px solid rgba(220,38,38,.12); }
@keyframes admFlashIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }

/* ─── Prérequis items ─── */
.adm-prereq-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px; border-radius: 10px; border: 1px solid var(--border, #e5e7eb); margin-bottom: 8px; transition: all .2s; }
.adm-prereq-item:hover { border-color: #1877f2; background: rgba(24,119,242,.02); }
.adm-prereq-item.done { border-color: rgba(16,185,129,.2); background: rgba(16,185,129,.02); }
.adm-prereq-cb { width: 18px; height: 18px; accent-color: #1877f2; cursor: pointer; flex-shrink: 0; margin-top: 1px; }
.adm-prereq-content { flex: 1; }
.adm-prereq-content strong { font-size: .85rem; font-weight: 600; color: var(--text, #111827); display: block; }
.adm-prereq-content span { font-size: .73rem; color: var(--text-3, #9ca3af); margin-top: 2px; display: block; }

@media (max-width: 960px) {
    .adm-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .adm-toolbar { flex-direction: column; align-items: flex-start; }
    .adm-grid-3 { grid-template-columns: 1fr 1fr; }
}
@media (max-width: 640px) {
    .adm-grid-3 { grid-template-columns: 1fr; }
    .adm-tabs { gap: 2px; }
    .adm-tab { padding: 7px 12px; font-size: .73rem; }
}
</style>

<div class="adm-wrap" id="admWrap">

<?php if ($flash === 'account_created'): ?>
    <div class="adm-flash success"><i class="fas fa-check-circle"></i> Compte publicitaire créé avec succès</div>
<?php elseif ($flash === 'error'): ?>
    <div class="adm-flash error"><i class="fas fa-exclamation-circle"></i> Une erreur est survenue</div>
<?php endif; ?>

<!-- ─── Banner ─── -->
<div class="adm-banner">
    <div class="adm-banner-left">
        <h2><i class="fab fa-facebook"></i> Lancement Publicitaire</h2>
        <p>Structure, configure et pilote tes campagnes — Méthode BizzBizz.io</p>
    </div>
    <div class="adm-stats">
        <div class="adm-stat"><div class="num blue"><?= $stats['total'] ?></div><div class="lbl">Comptes</div></div>
        <div class="adm-stat"><div class="num green"><?= $stats['active'] ?></div><div class="lbl">Actifs</div></div>
        <div class="adm-stat"><div class="num violet"><?= $stats['campaigns'] ?></div><div class="lbl">Campagnes</div></div>
        <?php if ($stats['budget'] > 0): ?>
        <div class="adm-stat">
            <div class="num amber"><?= number_format($stats['budget'], 0, ',', ' ') ?>€</div>
            <div class="lbl">Budget/j</div>
        </div>
        <?php endif; ?>
        <div class="adm-stat">
            <div class="num <?= $checklistPct === 100 ? 'green' : 'amber' ?>"><?= $checklistPct ?>%</div>
            <div class="lbl">Setup</div>
        </div>
    </div>
</div>

<!-- ─── Toolbar compte + action ─── -->
<div class="adm-toolbar">
    <div class="adm-toolbar-l">
        <select class="adm-account-select" id="admAccountSelect" onchange="ADM.onAccountChange(this.value)">
            <option value="">— Sélectionner un compte —</option>
            <?php foreach ($accounts as $acc): ?>
            <option value="<?= (int)$acc['id'] ?>"><?= htmlspecialchars($acc['account_name']) ?>
                <?= !empty($acc['platform']) ? ' · ' . htmlspecialchars($acc['platform']) : '' ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="adm-toolbar-r">
        <button class="adm-btn adm-btn-secondary" onclick="ADM.modal({
            icon: '<i class=\'fas fa-plus\'></i>', iconBg: '#eff6ff', iconColor: '#1877f2',
            title: 'Nouveau compte publicitaire',
            msg: '<p style=\'font-size:.83rem;color:#6b7280;margin:0 0 12px\'>Redirige vers le formulaire de création.</p>',
            confirmLabel: 'Continuer', confirmColor: '#1877f2',
            onConfirm: () => window.location.href=\'?page=ads-overview&action=create\'
        })">
            <i class="fas fa-plus"></i> Nouveau compte
        </button>
    </div>
</div>

<!-- ─── Tabs ─── -->
<div class="adm-tabs" role="tablist">
    <button class="adm-tab" data-tab="checklist"     onclick="ADM.switchTab('checklist')"><i class="fas fa-check-circle"></i> Checklist</button>
    <button class="adm-tab" data-tab="prerequisites" onclick="ADM.switchTab('prerequisites')"><i class="fas fa-wrench"></i> Prérequis</button>
    <button class="adm-tab" data-tab="audiences"     onclick="ADM.switchTab('audiences')"><i class="fas fa-users"></i> Audiences</button>
    <button class="adm-tab" data-tab="campaigns"     onclick="ADM.switchTab('campaigns')"><i class="fas fa-chart-bar"></i> Campagnes</button>
    <button class="adm-tab" data-tab="analytics"     onclick="ADM.switchTab('analytics')"><i class="fas fa-chart-line"></i> Analytics</button>
</div>

<!-- ══ CHECKLIST ══════════════════════════════════════════════ -->
<div class="adm-section" id="adm-tab-checklist">
    <div class="adm-card">
        <div class="adm-card-header">
            <h3><i class="fas fa-check-circle"></i> Checklist de lancement</h3>
            <span id="adm-checklist-badge" class="adm-badge-status adm-badge-<?= $checklistPct === 100 ? 'done' : 'todo' ?>">
                <?= $checklistDone ?>/<?= $checklistTotal ?> étapes
            </span>
        </div>
        <div class="adm-card-body">
            <div class="adm-progress-wrap">
                <div class="adm-progress-header">
                    <span>Progression du setup</span>
                    <strong><?= $checklistPct ?>%</strong>
                </div>
                <div class="adm-progress-bar">
                    <div class="adm-progress-fill <?= $checklistPct === 100 ? 'complete' : '' ?>"
                         style="width:<?= $checklistPct ?>%" id="adm-global-progress"></div>
                </div>
            </div>

            <?php foreach ($checklist as $item): ?>
            <div class="adm-check-row">
                <div class="adm-check-icon <?= $item['done'] ? 'done' : 'todo' ?>">
                    <i class="fas fa-<?= $item['done'] ? 'check' : 'circle' ?>"></i>
                </div>
                <div class="adm-check-text">
                    <strong><?= htmlspecialchars($item['title']) ?></strong>
                    <span><?= htmlspecialchars($item['desc']) ?></span>
                </div>
                <span class="adm-badge-status adm-badge-<?= $item['done'] ? 'done' : 'todo' ?>">
                    <?= $item['done'] ? 'Fait' : 'À faire' ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ══ PRÉREQUIS ══════════════════════════════════════════════ -->
<div class="adm-section" id="adm-tab-prerequisites">
    <div class="adm-card">
        <div class="adm-card-header">
            <h3><i class="fas fa-wrench"></i> Prérequis Techniques</h3>
            <span class="adm-badge-status adm-badge-todo" id="adm-prereq-badge">0%</span>
        </div>
        <div class="adm-card-body">
            <div class="adm-progress-wrap">
                <div class="adm-progress-bar">
                    <div class="adm-progress-fill" id="adm-prereq-progress" style="width:0%"></div>
                </div>
            </div>
            <div id="adm-prereq-list">
                <?php
                $prereqs = [
                    ['key' => 'pixel',    'label' => 'Pixel Facebook installé',         'detail' => 'ID Pixel renseigné et vérifié sur le site'],
                    ['key' => 'gtm',      'label' => 'Google Tag Manager actif',         'detail' => 'Conteneur GTM publié avec événements standards'],
                    ['key' => 'domain',   'label' => 'Domaine vérifié',                  'detail' => 'Vérification DNS dans Business Manager'],
                    ['key' => 'bm',       'label' => 'Business Manager configuré',       'detail' => 'Rôles, pages et comptes pub liés'],
                    ['key' => 'payment',  'label' => 'Mode de paiement actif',           'detail' => 'Carte ou PayPal validé sans restriction'],
                    ['key' => 'catalog',  'label' => 'Catalogue produits (optionnel)',   'detail' => 'Flux catalogue si retargeting dynamique'],
                ];
                foreach ($prereqs as $pr): ?>
                <div class="adm-prereq-item" id="prereq-<?= $pr['key'] ?>">
                    <input type="checkbox" class="adm-prereq-cb" data-key="<?= $pr['key'] ?>"
                           onchange="ADM.updatePrereqProgress()">
                    <div class="adm-prereq-content">
                        <strong><?= htmlspecialchars($pr['label']) ?></strong>
                        <span><?= htmlspecialchars($pr['detail']) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ══ AUDIENCES ══════════════════════════════════════════════ -->
<div class="adm-section" id="adm-tab-audiences">
    <div class="adm-card">
        <div class="adm-card-header">
            <h3><i class="fas fa-users"></i> Audiences Stratégiques</h3>
            <button class="adm-btn adm-btn-primary adm-btn-sm" onclick="ADM.createAudiences()">
                <i class="fas fa-magic"></i> Créer les audiences
            </button>
        </div>
        <div class="adm-card-body">
            <?php
            $audiences = [
                ['name' => 'Custom Intent (CI)',   'desc' => 'Visiteurs site + interactions pages & vidéos',     'icon' => 'bullseye',  'color' => '#1877f2', 'temp' => 'Hot'],
                ['name' => 'Lookalike 180j (LAL)', 'desc' => 'Sosies de vos clients des 180 derniers jours',     'icon' => 'users',     'color' => '#10b981', 'temp' => 'Warm'],
                ['name' => 'TNT — Intérêts',       'desc' => 'Centres d\'intérêt immobilier ciblés précisément', 'icon' => 'crosshairs','color' => '#f59e0b', 'temp' => 'Cold'],
            ];
            ?>
            <div class="adm-grid-3">
                <?php foreach ($audiences as $aud): ?>
                <div class="adm-audience-card">
                    <div class="adm-audience-icon"
                         style="background:<?= $aud['color'] ?>18;color:<?= $aud['color'] ?>">
                        <i class="fas fa-<?= $aud['icon'] ?>"></i>
                    </div>
                    <strong class="adm-audience-name"><?= htmlspecialchars($aud['name']) ?></strong>
                    <span class="adm-audience-desc"><?= htmlspecialchars($aud['desc']) ?></span>
                    <div style="margin-top:10px">
                        <span style="font-size:.65rem;font-weight:700;padding:3px 10px;border-radius:10px;
                            background:<?= $aud['color'] ?>18;color:<?= $aud['color'] ?>">
                            <?= $aud['temp'] ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ══ CAMPAGNES ══════════════════════════════════════════════ -->
<div class="adm-section" id="adm-tab-campaigns">
    <div class="adm-card">
        <div class="adm-card-header">
            <h3><i class="fas fa-chart-bar"></i> Campagnes & Nomenclature</h3>
        </div>
        <div class="adm-card-body">
            <div class="adm-form-grid">
                <div class="adm-form-group">
                    <label>N° d'ordre</label>
                    <input type="number" id="adm-camp-order" value="1" min="1" oninput="ADM.previewName()">
                </div>
                <div class="adm-form-group">
                    <label>Température</label>
                    <select id="adm-camp-temp" onchange="ADM.previewName()">
                        <option value="Cold">Cold ❄️</option>
                        <option value="Warm">Warm 🌡</option>
                        <option value="Hot">Hot 🔥</option>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label>Objectif</label>
                    <select id="adm-camp-obj" onchange="ADM.previewName()">
                        <option value="Leads">Leads</option>
                        <option value="Traffic">Traffic</option>
                        <option value="Conversions">Conversions</option>
                        <option value="Awareness">Awareness</option>
                        <option value="Retargeting">Retargeting</option>
                    </select>
                </div>
                <div class="adm-form-group">
                    <label>Audience</label>
                    <select id="adm-camp-audience" onchange="ADM.previewName()">
                        <option value="CI">CI — Custom Intent</option>
                        <option value="LAL">LAL — Lookalike 180j</option>
                        <option value="TNT">TNT — Intérêts</option>
                    </select>
                </div>
            </div>

            <div class="adm-nomenclature-preview show" id="adm-name-preview">
                <div class="label"><i class="fas fa-tag"></i> Nom généré</div>
                <div class="name" id="adm-generated-name">—</div>
            </div>

            <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap">
                <button class="adm-btn adm-btn-primary" onclick="ADM.generateName()">
                    <i class="fas fa-magic"></i> Générer
                </button>
                <button class="adm-btn adm-btn-secondary" onclick="ADM.copyName()">
                    <i class="fas fa-copy"></i> Copier
                </button>
                <button class="adm-btn adm-btn-secondary" onclick="ADM.saveCampaign()">
                    <i class="fas fa-save"></i> Sauvegarder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ══ ANALYTICS ══════════════════════════════════════════════ -->
<div class="adm-section" id="adm-tab-analytics">
    <div class="adm-card">
        <div class="adm-card-header">
            <h3><i class="fas fa-chart-line"></i> Analytics & KPIs</h3>
        </div>
        <div class="adm-card-body">
            <div class="adm-kpi-grid" id="adm-kpi-grid">
                <?php
                $kpis = [
                    ['val' => '—', 'lbl' => 'Impressions', 'color' => 'blue'],
                    ['val' => '—', 'lbl' => 'Clics',       'color' => 'green'],
                    ['val' => '—', 'lbl' => 'Leads',       'color' => 'amber'],
                    ['val' => '—', 'lbl' => 'CPL Moy.',    'color' => ''],
                    ['val' => '—', 'lbl' => 'CTR',         'color' => ''],
                    ['val' => '—', 'lbl' => 'ROAS',        'color' => 'green'],
                ];
                foreach ($kpis as $kpi): ?>
                <div class="adm-kpi">
                    <div class="kpi-val <?= $kpi['color'] ?>"><?= $kpi['val'] ?></div>
                    <div class="kpi-lbl"><?= $kpi['lbl'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="adm-empty">
                <i class="fas fa-chart-line"></i>
                <h3>Données non disponibles</h3>
                <p>Connectez un compte actif pour afficher les analytics en temps réel.</p>
            </div>
        </div>
    </div>
</div>

</div><!-- /adm-wrap -->

<!-- ══ MODAL ══════════════════════════════════════════════════ -->
<div id="admModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    <div onclick="ADM.modalClose()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px)"></div>
    <div id="admModalBox" style="position:relative;z-index:1;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:420px;margin:16px;overflow:hidden;transform:scale(.94) translateY(8px);transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .15s;opacity:0">
        <div id="admModalHeader" style="padding:20px 22px 16px;display:flex;align-items:flex-start;gap:14px">
            <div id="admModalIcon" style="width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem"></div>
            <div style="flex:1">
                <div id="admModalTitle" style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:5px"></div>
                <div id="admModalMsg"   style="font-size:.82rem;color:#6b7280;line-height:1.5"></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding:12px 20px 18px;border-top:1px solid #f3f4f6">
            <button onclick="ADM.modalClose()" style="padding:9px 20px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:.83rem;font-weight:600;cursor:pointer;font-family:inherit">Annuler</button>
            <button id="admModalConfirm" style="padding:9px 20px;border-radius:10px;border:none;font-size:.83rem;font-weight:700;cursor:pointer;font-family:inherit;color:#fff"></button>
        </div>
    </div>
</div>

<script>
const ADM = {
    apiUrl:    '/admin/modules/ads-launch/api.php',
    csrfToken: '<?= $_SESSION['csrf_token'] ?>',
    _modalCb:  null,

    // ── Init ──────────────────────────────────────────────
    init() {
        this.restoreTab();
        this.previewName();
        this.restoreAccount();
    },

    // ── Tabs ──────────────────────────────────────────────
    switchTab(tab) {
        document.querySelectorAll('.adm-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
        document.querySelectorAll('.adm-section').forEach(s => s.classList.remove('active'));
        const section = document.getElementById('adm-tab-' + tab);
        if (section) section.classList.add('active');
        try { sessionStorage.setItem('adm_tab', tab); } catch(e) {}
    },
    restoreTab() {
        let tab = '<?= $activeTab ?>';
        try { tab = sessionStorage.getItem('adm_tab') || tab; } catch(e) {}
        this.switchTab(tab);
    },

    // ── Compte ────────────────────────────────────────────
    onAccountChange(id) {
        try { sessionStorage.setItem('adm_account', id); } catch(e) {}
        if (!id) return;
        this.loadPrerequisites(id);
    },
    restoreAccount() {
        try {
            const saved = sessionStorage.getItem('adm_account');
            const sel   = document.getElementById('admAccountSelect');
            if (saved && sel) {
                sel.value = saved;
                if (sel.value) this.loadPrerequisites(saved);
            }
        } catch(e) {}
    },

    // ── Prérequis ─────────────────────────────────────────
    async loadPrerequisites(accountId) {
        try {
            const fd = new FormData();
            fd.append('action', 'get_prerequisites');
            fd.append('account_id', accountId);
            const r = await fetch(this.apiUrl, { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success && d.data) {
                d.data.forEach(item => {
                    const cb = document.querySelector(`.adm-prereq-cb[data-key="${item.key}"]`);
                    if (cb) cb.checked = !!item.done;
                });
                this.updatePrereqProgress();
            }
        } catch(e) { /* api.php pas encore créé — silencieux */ }
    },
    updatePrereqProgress() {
        const cbs    = document.querySelectorAll('.adm-prereq-cb');
        const done   = [...cbs].filter(cb => cb.checked).length;
        const total  = cbs.length;
        const pct    = total > 0 ? Math.round(done / total * 100) : 0;
        const bar    = document.getElementById('adm-prereq-progress');
        const badge  = document.getElementById('adm-prereq-badge');
        if (bar) { bar.style.width = pct + '%'; bar.classList.toggle('complete', pct === 100); }
        if (badge) {
            badge.textContent = pct + '%';
            badge.className = 'adm-badge-status adm-badge-' + (pct === 100 ? 'done' : 'todo');
        }
        // Mettre à jour les items visuellement
        document.querySelectorAll('.adm-prereq-cb').forEach(cb => {
            cb.closest('.adm-prereq-item').classList.toggle('done', cb.checked);
        });
    },

    // ── Générateur de nom ─────────────────────────────────
    previewName() {
        const order    = String(document.getElementById('adm-camp-order')?.value || '1').padStart(2,'0');
        const temp     = document.getElementById('adm-camp-temp')?.value || 'Cold';
        const obj      = document.getElementById('adm-camp-obj')?.value || 'Leads';
        const audience = document.getElementById('adm-camp-audience')?.value || 'CI';
        const date     = new Date().toISOString().slice(0,10).replace(/-/g,'');
        const name     = `C${order}_${temp}_${obj}_${audience}_${date}`;
        const el       = document.getElementById('adm-generated-name');
        if (el) el.textContent = name;
        return name;
    },
    generateName() {
        const name = this.previewName();
        this.toast(`Nom généré : ${name}`, 'success');
    },
    copyName() {
        const name = document.getElementById('adm-generated-name')?.textContent;
        if (!name || name === '—') { this.toast('Générez d\'abord un nom', 'error'); return; }
        navigator.clipboard.writeText(name).then(() => this.toast('Copié dans le presse-papier ✓', 'success'));
    },
    async saveCampaign() {
        const name = document.getElementById('adm-generated-name')?.textContent;
        if (!name || name === '—') { this.toast('Générez d\'abord un nom', 'error'); return; }
        const fd = new FormData();
        fd.append('action',      'save_campaign');
        fd.append('name',        name);
        fd.append('temperature', document.getElementById('adm-camp-temp')?.value);
        fd.append('objective',   document.getElementById('adm-camp-obj')?.value);
        fd.append('csrf_token',  this.csrfToken);
        try {
            const r = await fetch(this.apiUrl, { method: 'POST', body: fd });
            const d = await r.json();
            d.success ? this.toast('Campagne sauvegardée ✓', 'success') : this.toast(d.error || 'Erreur', 'error');
        } catch(e) { this.toast('Erreur réseau', 'error'); }
    },

    // ── Audiences ─────────────────────────────────────────
    createAudiences() {
        const accountId = document.getElementById('admAccountSelect')?.value;
        if (!accountId) { this.toast('Sélectionnez d\'abord un compte', 'error'); return; }
        this.modal({
            icon: '<i class="fas fa-users"></i>', iconBg: '#eff6ff', iconColor: '#1877f2',
            title: 'Créer les 3 audiences ?',
            msg: 'Les audiences CI, LAL 180j et TNT seront créées pour ce compte.',
            confirmLabel: 'Créer', confirmColor: '#1877f2',
            onConfirm: async () => {
                const fd = new FormData();
                fd.append('action', 'create_audiences');
                fd.append('account_id', accountId);
                fd.append('csrf_token', this.csrfToken);
                try {
                    const r = await fetch(this.apiUrl, { method: 'POST', body: fd });
                    const d = await r.json();
                    d.success ? this.toast('Audiences créées ✓', 'success') : this.toast(d.error || 'Erreur', 'error');
                } catch(e) { this.toast('Erreur réseau', 'error'); }
            }
        });
    },

    // ── Modal ─────────────────────────────────────────────
    modal({ icon, iconBg, iconColor, title, msg, confirmLabel, confirmColor, onConfirm }) {
        const el  = document.getElementById('admModal');
        const box = document.getElementById('admModalBox');
        document.getElementById('admModalIcon').innerHTML      = icon;
        document.getElementById('admModalIcon').style.background = iconBg;
        document.getElementById('admModalIcon').style.color     = iconColor;
        document.getElementById('admModalHeader').style.background = iconBg + '33';
        document.getElementById('admModalTitle').textContent   = title;
        document.getElementById('admModalMsg').innerHTML       = msg;
        const btn = document.getElementById('admModalConfirm');
        btn.textContent      = confirmLabel || 'Confirmer';
        btn.style.background = confirmColor || '#1877f2';
        this._modalCb = onConfirm;
        btn.onclick = () => { this.modalClose(); if (this._modalCb) this._modalCb(); };
        el.style.display = 'flex';
        requestAnimationFrame(() => { box.style.opacity='1'; box.style.transform='scale(1) translateY(0)'; });
        document.addEventListener('keydown', this._escHandler);
    },
    modalClose() {
        const box = document.getElementById('admModalBox');
        box.style.opacity='0'; box.style.transform='scale(.94) translateY(8px)';
        setTimeout(() => document.getElementById('admModal').style.display='none', 160);
        document.removeEventListener('keydown', this._escHandler);
    },
    _escHandler(e) { if (e.key === 'Escape') ADM.modalClose(); },

    // ── Toast ─────────────────────────────────────────────
    toast(msg, type = 'success') {
        const colors = { success:'#059669', error:'#dc2626', info:'#1877f2' };
        const icons  = { success:'✓', error:'✕', info:'ℹ' };
        const t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:10000;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:10px;font-size:.83rem;font-weight:600;color:#111827;box-shadow:0 8px 24px rgba(0,0,0,.12);transform:translateY(20px);opacity:0;transition:all .25s;';
        t.innerHTML = `<span style="width:22px;height:22px;border-radius:50%;background:${colors[type]}22;color:${colors[type]};display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800">${icons[type]}</span>${msg}`;
        document.body.appendChild(t);
        requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; });
        setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(10px)'; setTimeout(()=>t.remove(),250); }, 3500);
    }
};

document.addEventListener('DOMContentLoaded', () => ADM.init());
</script>