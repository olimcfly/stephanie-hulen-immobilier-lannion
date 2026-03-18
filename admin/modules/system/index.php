<?php
/**
 * ============================================================
 *  MODULE SYSTÈME — Hub principal  v2.1
 *  /admin/modules/system/index.php
 *
 *  Tableau de bord système : santé, paramètres, maintenance,
 *  licences, diagnostics, infos serveur.
 *  Intégré au layout admin (pas de DOCTYPE/html/head).
 *  Design harmonisé modules.php v4.
 * ============================================================
 */

// ── Connexion DB (reprise logique originale) ──────────────
if (!isset($pdo)) {
    $cfgPaths = [
        __DIR__ . '/../../../config/config.php',
        $_SERVER['DOCUMENT_ROOT'] . '/admin/config/config.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/config.php',
    ];
    foreach ($cfgPaths as $p) {
        if (file_exists($p)) { require_once $p; break; }
    }
    try {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (Exception $e) {
        die('<div style="background:#fee2e2;color:#991b1b;padding:20px;margin:20px;border-radius:8px;">❌ '.$e->getMessage().'</div>');
    }
}

// ── Infos PHP / Serveur ───────────────────────────────────
$phpVersion  = PHP_VERSION;
$phpOk       = version_compare($phpVersion, '8.0', '>=');

$mysqlVersion = '—';
try { $mysqlVersion = $pdo->query("SELECT VERSION()")->fetchColumn(); } catch (Throwable) {}

$diskTotal = @disk_total_space('/') ?: 0;
$diskFree  = @disk_free_space('/')  ?: 0;
$diskUsed  = $diskTotal - $diskFree;
$diskPct   = $diskTotal > 0 ? round($diskUsed / $diskTotal * 100) : 0;

$memLimit  = ini_get('memory_limit');
$uploadMax = ini_get('upload_max_filesize');
$postMax   = ini_get('post_max_size');
$maxExec   = ini_get('max_execution_time');

// ── Extensions PHP ────────────────────────────────────────
$requiredExt = ['pdo','pdo_mysql','curl','json','mbstring','gd','zip','openssl'];
$extStatus   = [];
$extMissing  = 0;
foreach ($requiredExt as $ext) {
    $loaded = extension_loaded($ext);
    $extStatus[$ext] = $loaded;
    if (!$loaded) $extMissing++;
}

// ── Stats DB ──────────────────────────────────────────────
$dbTables = 0;
$dbSize   = 0;
try {
    $dbTables = (int) $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '".DB_NAME."'")->fetchColumn();
    $dbSize   = (float) $pdo->query("SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '".DB_NAME."'")->fetchColumn();
} catch (Throwable) {}

// ── Infos IA (reprise logique originale : ai_settings en priorité) ──
$aiConfigured = false;
$aiProvider   = 'Non configuré';
try {
    $aiKey = $pdo->query("SELECT setting_value FROM ai_settings WHERE setting_key='anthropic_api_key'")->fetchColumn();
    if ($aiKey) { $aiConfigured = true; $aiProvider = 'Anthropic Claude'; }
    else {
        $aiKey = $pdo->query("SELECT setting_value FROM ai_settings WHERE setting_key='openai_api_key'")->fetchColumn();
        if ($aiKey) { $aiConfigured = true; $aiProvider = 'OpenAI GPT'; }
    }
} catch (Throwable) {}
if (!$aiConfigured) {
    if (defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY)   { $aiConfigured = true; $aiProvider = 'Anthropic Claude'; }
    elseif (defined('OPENAI_API_KEY') && OPENAI_API_KEY)     { $aiConfigured = true; $aiProvider = 'OpenAI GPT'; }
}

// ── Statut Licence ────────────────────────────────────────
$licStatus     = '';
$licPlan       = '—';
$licHolder     = '';
$licVerifiedAt = '';
try {
    $licRows = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('license_status','license_plan','license_holder','license_verified_at')")->fetchAll();
    foreach ($licRows as $r) $$r['setting_key'] = $r['setting_value'];
    // Alias pour PHP
    $licStatus     = isset($license_status)      ? $license_status      : '';
    $licPlan       = isset($license_plan)        ? $license_plan        : '—';
    $licHolder     = isset($license_holder)      ? $license_holder      : '';
    $licVerifiedAt = isset($license_verified_at) ? $license_verified_at : '';
} catch (Throwable) {}
$licActive     = $licStatus === 'active';
$licNeedsCheck = $licVerifiedAt && (time() - strtotime($licVerifiedAt)) > 86400;

// ── Statut Maintenance ────────────────────────────────────
$maintenanceOn = false;
try {
    $r = $pdo->query("SELECT is_active FROM maintenance WHERE id=1 LIMIT 1")->fetch();
    if ($r) $maintenanceOn = (bool) $r['is_active'];
} catch (Throwable) {}

// ── Score santé global ────────────────────────────────────
$healthChecks = [
    $phpOk, $diskPct < 85, $aiConfigured,
    $licActive, !$maintenanceOn, $extMissing === 0,
];
$healthScore = round(count(array_filter($healthChecks)) / count($healthChecks) * 100);
$healthColor = $healthScore >= 80 ? 'var(--green)' : ($healthScore >= 50 ? 'var(--amber)' : 'var(--red)');

function sysHubFmtBytes(int $b): string {
    if ($b >= 1073741824) return round($b / 1073741824, 1) . ' Go';
    if ($b >= 1048576)    return round($b / 1048576, 1)    . ' Mo';
    return round($b / 1024, 1) . ' Ko';
}
?>

<style>
/* ══ System Hub v2.1 — harmonisé modules.php ══════════════ */

.syshub-status-bar {
    display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px;
}
.syshub-pill {
    display:inline-flex; align-items:center; gap:6px;
    padding:5px 12px; border-radius:99px;
    font-size:11px; font-weight:700; border:1px solid; cursor:default;
}
.syshub-pill .dot { width:6px; height:6px; border-radius:50%; background:currentColor; }
.syshub-pill.ok   { background:var(--green-bg); color:var(--green); border-color:var(--green); }
.syshub-pill.warn { background:var(--amber-bg); color:var(--amber); border-color:var(--amber); }
.syshub-pill.err  { background:var(--red-bg);   color:var(--red);   border-color:var(--red); }

.syshub-scores {
    display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin-bottom:20px;
}
.syshub-score-main {
    background:var(--surface); border:2px solid var(--accent);
    border-radius:var(--radius-lg); padding:16px;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    box-shadow:var(--shadow-sm);
}
.syshub-score-main .pct { font-size:28px; font-weight:900; line-height:1; }
.syshub-score-main .lbl { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-3); margin-top:3px; }

.syshub-section-title {
    font-size:10px; font-weight:800; text-transform:uppercase;
    letter-spacing:.1em; color:var(--text-3); margin-bottom:10px;
    display:flex; align-items:center; gap:7px; padding-left:2px;
}

.syshub-modules-grid {
    display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr));
    gap:12px; margin-bottom:20px;
}
.syshub-mod-card {
    background:var(--surface); border:1px solid var(--border);
    border-radius:var(--radius-lg); padding:18px 16px;
    display:flex; align-items:flex-start; gap:14px;
    text-decoration:none; color:inherit;
    transition:all .18s; position:relative; overflow:hidden;
    box-shadow:var(--shadow-sm);
}
.syshub-mod-card:hover {
    border-color:var(--card-accent, var(--accent));
    box-shadow:var(--shadow); transform:translateY(-2px);
}
.syshub-mod-card::after {
    content:''; position:absolute; inset:0; opacity:0; transition:opacity .18s;
    background:var(--card-accent, var(--accent));
}
.syshub-mod-card:hover::after { opacity:.03; }
.syshub-mod-card.featured { border-color:var(--card-accent, var(--accent)); }

.syshub-mod-icon {
    width:40px; height:40px; border-radius:var(--radius);
    display:flex; align-items:center; justify-content:center;
    font-size:16px; color:#fff; flex-shrink:0;
}
.syshub-mod-body { flex:1; min-width:0; }
.syshub-mod-name {
    font-size:13px; font-weight:700; color:var(--text);
    display:flex; align-items:center; gap:7px; margin-bottom:4px; flex-wrap:wrap;
}
.syshub-mod-desc { font-size:11px; color:var(--text-2); line-height:1.55; }
.syshub-mod-arrow {
    position:absolute; right:14px; top:50%; transform:translateY(-50%);
    color:var(--text-3); font-size:11px; transition:all .18s;
}
.syshub-mod-card:hover .syshub-mod-arrow {
    color:var(--card-accent, var(--accent));
    transform:translateY(-50%) translateX(3px);
}

.syshub-badge {
    font-size:9px; font-weight:800; padding:2px 7px;
    border-radius:4px; text-transform:uppercase; letter-spacing:.04em;
}

.syshub-info-grid {
    display:grid; grid-template-columns:repeat(auto-fill,minmax(190px,1fr));
    gap:10px; margin-bottom:20px;
}
.syshub-info-card {
    background:var(--surface); border:1px solid var(--border);
    border-radius:var(--radius-lg); padding:14px 16px; box-shadow:var(--shadow-sm);
}
.syshub-info-label {
    font-size:9px; font-weight:700; text-transform:uppercase;
    letter-spacing:.08em; color:var(--text-3); margin-bottom:6px;
}
.syshub-info-val  { font-size:20px; font-weight:900; color:var(--text); line-height:1; }
.syshub-info-sub  { font-size:10px; color:var(--text-3); margin-top:4px; }
.syshub-prog      { height:5px; background:var(--surface-3); border-radius:99px; margin-top:8px; overflow:hidden; }
.syshub-prog-bar  { height:100%; border-radius:99px; transition:width .6s ease; }

.syshub-ext-grid  { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:20px; }
.syshub-ext-pill  {
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 10px; border-radius:99px;
    font-size:11px; font-weight:600; border:1px solid;
}
.syshub-ext-pill.ok   { background:var(--green-bg); color:var(--green); border-color:var(--green); }
.syshub-ext-pill.miss { background:var(--red-bg);   color:var(--red);   border-color:var(--red); }

.syshub-maint-alert {
    display:flex; align-items:center; gap:10px;
    background:var(--red-bg); border:1px solid var(--red);
    border-radius:var(--radius); padding:10px 16px; margin-bottom:16px;
    font-size:12px; color:var(--red); font-weight:700;
}

@media(max-width:760px) {
    .syshub-scores     { grid-template-columns:1fr 1fr; }
    .syshub-info-grid  { grid-template-columns:1fr 1fr; }
    .syshub-modules-grid { grid-template-columns:1fr; }
}
</style>

<!-- ════════ PAGE HEADER ════════ -->
<div class="page-hd">
    <div>
        <h1>Système</h1>
        <div class="page-hd-sub">
            Administration et santé de la plateforme ·
            PHP <?= $phpVersion ?> · MySQL <?= htmlspecialchars(explode('-', $mysqlVersion)[0]) ?>
        </div>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
        <button class="btn btn-s btn-sm" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Actualiser
        </button>
        <a href="?page=system/modules" class="btn btn-p btn-sm">
            <i class="fas fa-stethoscope"></i> Diagnostic modules
        </a>
    </div>
</div>

<!-- ── Alerte maintenance active ── -->
<?php if ($maintenanceOn): ?>
<div class="syshub-maint-alert anim">
    <i class="fas fa-wrench" style="font-size:14px"></i>
    <span>MODE MAINTENANCE ACTIF — Le site est inaccessible aux visiteurs</span>
    <a href="?page=system/maintenance" class="btn btn-s btn-sm"
       style="margin-left:auto;border-color:var(--red);color:var(--red)">
        Gérer
    </a>
</div>
<?php endif; ?>

<!-- ── Barre statuts ── -->
<div class="syshub-status-bar anim">
    <span class="syshub-pill <?= $phpOk ? 'ok' : 'warn' ?>">
        <span class="dot"></span> PHP <?= $phpVersion ?>
    </span>
    <span class="syshub-pill ok">
        <span class="dot"></span> MySQL <?= htmlspecialchars(explode('-', $mysqlVersion)[0]) ?>
    </span>
    <span class="syshub-pill <?= $diskPct < 80 ? 'ok' : ($diskPct < 90 ? 'warn' : 'err') ?>">
        <span class="dot"></span> Disque <?= $diskPct ?>%
    </span>
    <span class="syshub-pill <?= $aiConfigured ? 'ok' : 'warn' ?>">
        <span class="dot"></span> IA : <?= htmlspecialchars($aiProvider) ?>
    </span>
    <span class="syshub-pill <?= $licActive ? 'ok' : 'warn' ?>">
        <span class="dot"></span>
        Licence : <?= $licActive ? strtoupper($licPlan) : 'Non active' ?>
        <?php if ($licNeedsCheck): ?>
            <i class="fas fa-triangle-exclamation" style="font-size:9px"></i>
        <?php endif; ?>
    </span>
    <span class="syshub-pill <?= $extMissing === 0 ? 'ok' : 'err' ?>">
        <span class="dot"></span>
        Extensions : <?= $extMissing === 0 ? 'Toutes OK' : $extMissing . ' manquante(s)' ?>
    </span>
</div>

<!-- ── Score cards ── -->
<div class="syshub-scores anim">
    <div class="syshub-score-main">
        <div class="pct" style="color:<?= $healthColor ?>"><?= $healthScore ?>%</div>
        <div class="lbl">Santé globale</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--accent-bg);color:var(--accent)">
            <i class="fas fa-database"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val"><?= $dbTables ?></div>
            <div class="stat-label">Tables · <?= $dbSize ?> Mo</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"
             style="background:<?= $licActive ? 'var(--green-bg)' : 'var(--amber-bg)' ?>;
                    color:<?= $licActive ? 'var(--green)' : 'var(--amber)' ?>">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val" style="font-size:13px"><?= $licActive ? strtoupper($licPlan) : 'Inactive' ?></div>
            <div class="stat-label">Licence<?= $licHolder ? ' · ' . htmlspecialchars($licHolder) : '' ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"
             style="background:<?= $aiConfigured ? 'var(--accent-bg)' : 'var(--red-bg)' ?>;
                    color:<?= $aiConfigured ? 'var(--accent)' : 'var(--red)' ?>">
            <i class="fas fa-robot"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val" style="font-size:12px"><?= $aiConfigured ? '✓ Actif' : '✗ Non config.' ?></div>
            <div class="stat-label"><?= htmlspecialchars($aiProvider) ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"
             style="background:<?= $extMissing === 0 ? 'var(--green-bg)' : 'var(--red-bg)' ?>;
                    color:<?= $extMissing === 0 ? 'var(--green)' : 'var(--red)' ?>">
            <i class="fas fa-puzzle-piece"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val"><?= count($requiredExt) - $extMissing ?>/<?= count($requiredExt) ?></div>
            <div class="stat-label">Extensions PHP</div>
        </div>
    </div>
</div>

<!-- ════════ MODULES SYSTÈME ════════ -->
<div class="syshub-section-title anim">
    <i class="fas fa-server" style="color:var(--accent)"></i>
    Modules système
</div>

<div class="syshub-modules-grid anim">

    <a href="?page=system/settings" class="syshub-mod-card" style="--card-accent:#6366f1">
        <div class="syshub-mod-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5)">
            <i class="fas fa-sliders-h"></i>
        </div>
        <div class="syshub-mod-body">
            <div class="syshub-mod-name">Paramètres généraux</div>
            <div class="syshub-mod-desc">Site, branding, URL, langue, fuseau horaire, SMTP</div>
        </div>
        <i class="fas fa-chevron-right syshub-mod-arrow"></i>
    </a>

    <a href="?page=system/settings/ai" class="syshub-mod-card <?= $aiConfigured ? '' : 'featured' ?>"
       style="--card-accent:#7c3aed">
        <div class="syshub-mod-icon" style="background:linear-gradient(135deg,#7c3aed,#6d28d9)">
            <i class="fas fa-robot"></i>
        </div>
        <div class="syshub-mod-body">
            <div class="syshub-mod-name">
                Intelligence Artificielle
                <?php if ($aiConfigured): ?>
                <span class="syshub-badge" style="background:var(--green-bg);color:var(--green)">Actif</span>
                <?php else: ?>
                <span class="syshub-badge" style="background:var(--red-bg);color:var(--red)">Config requise</span>
                <?php endif; ?>
            </div>
            <div class="syshub-mod-desc">Clés API Anthropic/OpenAI, modèles, prompts système</div>
        </div>
        <i class="fas fa-chevron-right syshub-mod-arrow"></i>
    </a>

    <a href="?page=system/settings/api" class="syshub-mod-card" style="--card-accent:#0891b2">
        <div class="syshub-mod-icon" style="background:linear-gradient(135deg,#0891b2,#0e7490)">
            <i class="fas fa-plug"></i>
        </div>
        <div class="syshub-mod-body">
            <div class="syshub-mod-name">Intégrations API</div>
            <div class="syshub-mod-desc">Google, Facebook, webhooks, services tiers connectés</div>
        </div>
        <i class="fas fa-chevron-right syshub-mod-arrow"></i>
    </a>

    <a href="?page=system/maintenance" class="syshub-mod-card <?= $maintenanceOn ? 'featured' : '' ?>"
       style="--card-accent:#d97706">
        <div class="syshub-mod-icon" style="background:linear-gradient(135deg,#d97706,#b45309)">
            <i class="fas fa-tools"></i>
        </div>
        <div class="syshub-mod-body">
            <div class="syshub-mod-name">
                Maintenance
                <span class="syshub-badge"
                      style="background:<?= $maintenanceOn ? 'var(--red-bg)' : 'var(--green-bg)' ?>;
                             color:<?= $maintenanceOn ? 'var(--red)' : 'var(--green)' ?>">
                    <?= $maintenanceOn ? 'ON' : 'OFF' ?>
                </span>
            </div>
            <div class="syshub-mod-desc">Cache, logs, sauvegardes, mode maintenance, nettoyage DB</div>
        </div>
        <i class="fas fa-chevron-right syshub-mod-arrow"></i>
    </a>

    <a href="?page=system/modules" class="syshub-mod-card" style="--card-accent:#059669">
        <div class="syshub-mod-icon" style="background:linear-gradient(135deg,#059669,#047857)">
            <i class="fas fa-stethoscope"></i>
        </div>
        <div class="syshub-mod-body">
            <div class="syshub-mod-name">Diagnostic modules</div>
            <div class="syshub-mod-desc">Santé des modules, inventaire fichiers, structure, assistant IA</div>
        </div>
        <i class="fas fa-chevron-right syshub-mod-arrow"></i>
    </a>

    <a href="?page=system/checklist" class="syshub-mod-card" style="--card-accent:#f59e0b">
        <div class="syshub-mod-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="syshub-mod-body">
            <div class="syshub-mod-name">Checklist pages</div>
            <div class="syshub-mod-desc">Audit des pages admin et front — fichiers, routes, tables DB</div>
        </div>
        <i class="fas fa-chevron-right syshub-mod-arrow"></i>
    </a>

    <a href="?page=system/license" class="syshub-mod-card <?= !$licActive ? 'featured' : '' ?>"
       style="--card-accent:#dc2626">
        <div class="syshub-mod-icon" style="background:linear-gradient(135deg,#dc2626,#b91c1c)">
            <i class="fas fa-id-card"></i>
        </div>
        <div class="syshub-mod-body">
            <div class="syshub-mod-name">
                Licence
                <span class="syshub-badge"
                      style="background:<?= $licActive ? 'var(--green-bg)' : 'var(--red-bg)' ?>;
                             color:<?= $licActive ? 'var(--green)' : 'var(--red)' ?>">
                    <?= $licActive ? strtoupper($licPlan) : 'Inactive' ?>
                </span>
                <?php if ($licNeedsCheck): ?>
                <span class="syshub-badge" style="background:var(--amber-bg);color:var(--amber)">
                    <i class="fas fa-clock"></i> À vérifier
                </span>
                <?php endif; ?>
            </div>
            <div class="syshub-mod-desc">
                Clé de licence, activations, plan actif
                <?php if ($licHolder): ?>· <?= htmlspecialchars($licHolder) ?><?php endif; ?>
            </div>
        </div>
        <i class="fas fa-chevron-right syshub-mod-arrow"></i>
    </a>

</div>

<!-- ════════ RESSOURCES SERVEUR ════════ -->
<div class="syshub-section-title anim">
    <i class="fas fa-microchip" style="color:var(--accent)"></i>
    Ressources serveur
</div>

<div class="syshub-info-grid anim">
    <div class="syshub-info-card">
        <div class="syshub-info-label">PHP</div>
        <div class="syshub-info-val" style="font-size:16px;color:<?= $phpOk ? 'var(--green)' : 'var(--amber)' ?>">
            <?= $phpVersion ?>
        </div>
        <div class="syshub-info-sub"><?= $phpOk ? '✓ Compatible' : '⚠ Upgrade recommandé' ?></div>
    </div>

    <div class="syshub-info-card">
        <div class="syshub-info-label">Base de données</div>
        <div class="syshub-info-val"><?= $dbTables ?></div>
        <div class="syshub-info-sub"><?= $dbSize ?> Mo · MySQL <?= htmlspecialchars(explode('-', $mysqlVersion)[0]) ?></div>
    </div>

    <div class="syshub-info-card">
        <div class="syshub-info-label">Espace disque</div>
        <div class="syshub-info-val"
             style="color:<?= $diskPct < 70 ? 'var(--green)' : ($diskPct < 85 ? 'var(--amber)' : 'var(--red)') ?>">
            <?= $diskPct ?>%
        </div>
        <div class="syshub-info-sub"><?= sysHubFmtBytes($diskFree) ?> libre / <?= sysHubFmtBytes($diskTotal) ?></div>
        <div class="syshub-prog">
            <div class="syshub-prog-bar"
                 style="width:<?= $diskPct ?>%;
                        background:<?= $diskPct < 70 ? 'var(--green)' : ($diskPct < 85 ? 'var(--amber)' : 'var(--red)') ?>">
            </div>
        </div>
    </div>

    <div class="syshub-info-card">
        <div class="syshub-info-label">Mémoire PHP</div>
        <div class="syshub-info-val"><?= $memLimit ?></div>
        <div class="syshub-info-sub">Upload <?= $uploadMax ?> · POST <?= $postMax ?></div>
    </div>

    <div class="syshub-info-card">
        <div class="syshub-info-label">Exécution max</div>
        <div class="syshub-info-val"><?= $maxExec ?>s</div>
        <div class="syshub-info-sub">TZ : <?= date_default_timezone_get() ?></div>
    </div>

    <div class="syshub-info-card">
        <div class="syshub-info-label">Intelligence IA</div>
        <div class="syshub-info-val"
             style="font-size:13px;color:<?= $aiConfigured ? 'var(--accent)' : 'var(--red)' ?>">
            <?= $aiConfigured ? '✓ Opérationnel' : '✗ Non configuré' ?>
        </div>
        <div class="syshub-info-sub"><?= htmlspecialchars($aiProvider) ?></div>
    </div>
</div>

<!-- ════════ EXTENSIONS PHP ════════ -->
<div class="syshub-section-title anim">
    <i class="fas fa-puzzle-piece" style="color:var(--accent)"></i>
    Extensions PHP requises
    <?php if ($extMissing > 0): ?>
    <span style="background:var(--red-bg);color:var(--red);padding:1px 7px;border-radius:99px;font-size:9px">
        <?= $extMissing ?> manquante(s)
    </span>
    <?php endif; ?>
</div>

<div class="syshub-ext-grid anim">
    <?php foreach ($extStatus as $ext => $loaded): ?>
    <span class="syshub-ext-pill <?= $loaded ? 'ok' : 'miss' ?>">
        <i class="fas fa-<?= $loaded ? 'check' : 'times' ?>" style="font-size:9px"></i>
        <?= $ext ?>
    </span>
    <?php endforeach; ?>
</div>