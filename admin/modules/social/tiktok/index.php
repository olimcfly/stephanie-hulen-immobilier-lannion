<?php
// ======================================================
// Module TIKTOK - Scripts Vidéo
// /admin/modules/social/tiktok/index.php
// Chargé via dashboard.php — pas de session/connexion ici
// ======================================================

if (!defined('ADMIN_ROUTER')) {
    die("Accès direct interdit.");
}

// ── Onglet actif ────────────────────────────────────────
$allowedTabs = ['strategie','scripts','bibliotheque','clonage'];
$tab = in_array($_GET['tab'] ?? 'strategie', $allowedTabs, true)
    ? ($_GET['tab'] ?? 'strategie')
    : 'strategie';

// ── Personas depuis la DB ────────────────────────────────
$personas = [];
try {
    $personas = $pdo->query("SELECT * FROM neuropersonas WHERE is_active = 1 ORDER BY type, name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Stats scripts ────────────────────────────────────────
$stats = ['total' => 0, 'this_month' => 0, 'filmed' => 0];
try {
    $stats['total']      = (int)($pdo->query("SELECT COUNT(*) FROM tiktok_scripts")->fetchColumn() ?: 0);
    $stats['filmed']     = (int)($pdo->query("SELECT COUNT(*) FROM tiktok_scripts WHERE status = 'filmed'")->fetchColumn() ?: 0);
    $stats['this_month'] = (int)($pdo->query("SELECT COUNT(*) FROM tiktok_scripts WHERE MONTH(created_at) = MONTH(CURRENT_DATE())")->fetchColumn() ?: 0);
} catch (Exception $e) {}
?>

<style>
/* ========== Module TikTok ========== */
.tt-header {
    background: linear-gradient(135deg, #161823, #2d2d3a);
    border-radius: var(--radius-lg); padding: 32px; color: #fff;
    margin-bottom: 24px; display: flex; align-items: center; gap: 24px;
    position: relative; overflow: hidden;
}
.tt-header::before {
    content: ''; position: absolute; top: -50%; right: -10%;
    width: 300px; height: 300px;
    background: linear-gradient(135deg, #fe2c55, #25f4ee);
    border-radius: 50%; opacity: .08;
}
.tt-header .tt-icon {
    width: 70px; height: 70px; flex-shrink: 0;
    background: linear-gradient(135deg, #fe2c55, #25f4ee);
    border-radius: var(--radius-lg);
    display: flex; align-items: center; justify-content: center;
    font-size: 30px; position: relative; z-index: 1;
}
.tt-header h1 { font-size: 22px; margin: 0 0 7px; position: relative; z-index: 1; }
.tt-header p  { margin: 0; opacity: .8; font-size: 13px; position: relative; z-index: 1; }

.tt-stats {
    display: flex; gap: 14px; margin-bottom: 22px; flex-wrap: wrap;
}
.tt-stat {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); padding: 16px 22px;
    display: flex; align-items: center; gap: 14px; box-shadow: var(--shadow-sm);
    flex: 1; min-width: 140px;
}
.tt-stat-icon {
    width: 42px; height: 42px; border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center; font-size: 17px; flex-shrink: 0;
}
.tt-stat-value { font-size: 22px; font-weight: 900; color: var(--text); }
.tt-stat-label { font-size: 11.5px; color: var(--text-3); }

.tt-tabs {
    display: flex; gap: 3px; background: var(--surface-2);
    padding: 5px; border-radius: var(--radius-lg); margin-bottom: 22px;
    border: 1px solid var(--border); overflow-x: auto;
}
.tt-tab {
    padding: 11px 22px; border-radius: var(--radius);
    font-size: 13px; font-weight: 600; color: var(--text-2);
    text-decoration: none; display: inline-flex; align-items: center; gap: 7px;
    transition: all .15s; white-space: nowrap;
}
.tt-tab:hover { background: var(--surface); color: var(--text); }
.tt-tab.active {
    background: linear-gradient(135deg, #161823, #2d2d3a);
    color: #fff; box-shadow: var(--shadow);
}
.tt-tab .tt-badge {
    background: #fe2c55; color: #fff;
    font-size: 10px; padding: 2px 7px; border-radius: 10px;
}
</style>

<!-- Header -->
<div class="tt-header">
    <div class="tt-icon"><i class="fab fa-tiktok"></i></div>
    <div>
        <h1>TikTok — Scripts Vidéo</h1>
        <p>Créez des scripts percutants · Filmez-vous ou clonez votre voix · Touchez une nouvelle audience</p>
    </div>
</div>

<!-- Stats -->
<div class="tt-stats">
    <div class="tt-stat">
        <div class="tt-stat-icon" style="background:linear-gradient(135deg,rgba(254,44,85,.15),rgba(37,244,238,.15));color:#161823">
            <i class="fas fa-scroll"></i>
        </div>
        <div>
            <div class="tt-stat-value"><?= $stats['total'] ?></div>
            <div class="tt-stat-label">Scripts créés</div>
        </div>
    </div>
    <div class="tt-stat">
        <div class="tt-stat-icon" style="background:var(--green-bg);color:var(--green)">
            <i class="fas fa-video"></i>
        </div>
        <div>
            <div class="tt-stat-value"><?= $stats['filmed'] ?></div>
            <div class="tt-stat-label">Vidéos filmées</div>
        </div>
    </div>
    <div class="tt-stat">
        <div class="tt-stat-icon" style="background:var(--amber-bg);color:var(--amber)">
            <i class="fas fa-calendar"></i>
        </div>
        <div>
            <div class="tt-stat-value"><?= $stats['this_month'] ?></div>
            <div class="tt-stat-label">Ce mois-ci</div>
        </div>
    </div>
    <div class="tt-stat">
        <div class="tt-stat-icon" style="background:#f5f0ff;color:#7c3aed">
            <i class="fas fa-users"></i>
        </div>
        <div>
            <div class="tt-stat-value"><?= count($personas) ?></div>
            <div class="tt-stat-label">Personas</div>
        </div>
    </div>
</div>

<!-- Tabs -->
<div class="tt-tabs">
    <?php
    $tabItems = [
        'strategie'   => ['fas fa-graduation-cap', 'Stratégie & Réseaux', null],
        'scripts'     => ['fas fa-pen-fancy',       'Créer un script',     null],
        'bibliotheque'=> ['fas fa-book',            'Bibliothèque',        $stats['total'] ?: null],
        'clonage'     => ['fas fa-microphone-alt',  'Clonage vocal',       null],
    ];
    foreach ($tabItems as $key => [$icon, $label, $badge]): ?>
    <a href="?page=tiktok&tab=<?= $key ?>" class="tt-tab <?= $tab === $key ? 'active' : '' ?>">
        <i class="<?= $icon ?>"></i> <?= $label ?>
        <?php if ($badge): ?><span class="tt-badge"><?= $badge ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Contenu des tabs -->
<?php
$allowedTabFiles = ['strategie','scripts','bibliotheque','clonage'];
$tabFile = __DIR__ . '/tabs/' . $tab . '.php';
if (in_array($tab, $allowedTabFiles, true) && file_exists($tabFile)):
    include $tabFile;
else: ?>
<div style="text-align:center;padding:60px 20px;color:var(--text-3)">
    <i class="fas fa-tools" style="font-size:2.5rem;opacity:.2;display:block;margin-bottom:14px"></i>
    <h3 style="font-size:15px;font-weight:700;color:var(--text-2);margin-bottom:8px">Onglet en préparation</h3>
    <p style="font-size:13px">Le fichier <code>tabs/<?= htmlspecialchars($tab) ?>.php</code> n'existe pas encore.</p>
</div>
<?php endif; ?>