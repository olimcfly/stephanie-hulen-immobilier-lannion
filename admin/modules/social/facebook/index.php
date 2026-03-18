<?php
/**
 * MODULE: Communication Facebook Organique
 * /admin/modules/social/facebook/index.php
 * Chargé via dashboard.php — pas de session/connexion ici
 */

if (!defined('ADMIN_ROUTER')) {
    die("Accès direct interdit.");
}

// $pdo est déjà disponible via dashboard.php

$tab = $_GET['tab'] ?? 'strategie';

// Personas actifs
$personas = [];
try {
    $personas = $pdo->query("SELECT * FROM neuropersonas WHERE is_active = 1 ORDER BY type, name")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Stats publications
$stats = ['total' => 0, 'this_month' => 0, 'planned' => 0];
try {
    $stats['total']      = (int)$pdo->query("SELECT COUNT(*) FROM facebook_posts")->fetchColumn();
    $stats['this_month'] = (int)$pdo->query("SELECT COUNT(*) FROM facebook_posts WHERE MONTH(created_at) = MONTH(CURRENT_DATE())")->fetchColumn();
    $stats['planned']    = (int)$pdo->query("SELECT COUNT(*) FROM facebook_posts WHERE status = 'planned'")->fetchColumn();
} catch (Exception $e) {}
?>

<style>
/* ========== Module Facebook ========== */
.fb-module {
    --fb-blue: #1877f2;
    --fb-dark: #1e293b;
}

.fb-header {
    background: linear-gradient(135deg, #1877f2, #0d65d9);
    border-radius: var(--radius-lg);
    padding: 32px;
    color: white;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 24px;
}
.fb-header .fb-hd-icon {
    width: 72px; height: 72px;
    background: rgba(255,255,255,0.2);
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    font-size: 36px; flex-shrink: 0;
}
.fb-header h1 { font-size: 22px; font-weight: 800; margin: 0 0 6px; }
.fb-header p  { margin: 0; opacity: 0.85; font-size: 13px; }

.fb-stats-row {
    display: flex; gap: 14px; margin-bottom: 24px; flex-wrap: wrap;
}
.fb-stat-mini {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); padding: 16px 22px;
    display: flex; align-items: center; gap: 14px; flex: 1; min-width: 140px;
}
.fb-stat-mini .fb-stat-icon {
    width: 44px; height: 44px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.fb-stat-mini .fb-stat-val   { font-size: 24px; font-weight: 800; color: var(--text); line-height: 1; }
.fb-stat-mini .fb-stat-label { font-size: 12px; color: var(--text-3); margin-top: 3px; }

.fb-tabs {
    display: flex; gap: 4px;
    background: var(--surface-2); padding: 5px;
    border-radius: var(--radius-lg); margin-bottom: 24px;
    border: 1px solid var(--border);
}
.fb-tab {
    padding: 10px 20px; border-radius: var(--radius);
    font-size: 13px; font-weight: 600; color: var(--text-2);
    text-decoration: none; display: flex; align-items: center; gap: 8px;
    transition: all 0.15s;
}
.fb-tab:hover { background: var(--surface); color: var(--text); }
.fb-tab.active {
    background: var(--surface); color: var(--fb-blue, #1877f2);
    box-shadow: var(--shadow-sm);
}
.fb-tab .fb-tab-badge {
    background: #1877f2; color: white;
    font-size: 10px; padding: 2px 7px; border-radius: 10px;
    font-weight: 700;
}

/* Composants internes réutilisés par les tabs */
.fb-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 20px;
}
.fb-card-header {
    padding: 15px 20px; border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center;
    background: var(--surface-2);
}
.fb-card-header h3 {
    font-size: 14px; font-weight: 700; margin: 0;
    display: flex; align-items: center; gap: 10px; color: var(--text);
}
.fb-card-body { padding: 22px; }

.fb-alert {
    padding: 14px 18px; border-radius: var(--radius);
    font-size: 13px; margin-bottom: 18px;
    display: flex; align-items: flex-start; gap: 12px; line-height: 1.5;
}
.fb-alert-info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
.fb-alert-warning { background: var(--amber-bg); border: 1px solid #fde68a; color: #92400e; }
.fb-alert-success { background: var(--green-bg);  border: 1px solid #a7f3d0; color: #065f46; }

.fb-form-group { margin-bottom: 18px; }
.fb-form-label {
    display: block; font-weight: 600; font-size: 12.5px;
    margin-bottom: 7px; color: var(--text);
}
.fb-form-control {
    width: 100%; padding: 10px 13px;
    border: 1px solid var(--border); border-radius: var(--radius);
    font-size: 13.5px; font-family: var(--font);
    background: var(--surface); color: var(--text);
    transition: border-color .15s, box-shadow .15s;
}
.fb-form-control:focus {
    outline: none; border-color: #1877f2;
    box-shadow: 0 0 0 3px rgba(24,119,242,0.1);
}
textarea.fb-form-control { min-height: 100px; resize: vertical; }
.fb-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.fb-form-help { font-size: 11.5px; color: var(--text-3); margin-top: 5px; }

.fb-btn {
    padding: 9px 17px; border-radius: var(--radius);
    font-weight: 600; font-size: 13px; cursor: pointer;
    border: none; display: inline-flex; align-items: center; gap: 7px;
    transition: all 0.15s; text-decoration: none; font-family: var(--font);
}
.fb-btn-primary { background: var(--accent); color: #fff; }
.fb-btn-primary:hover { background: #4f46e5; }
.fb-btn-fb { background: #1877f2; color: #fff; }
.fb-btn-fb:hover { background: #0d65d9; }
.fb-btn-secondary { background: var(--surface-2); color: var(--text-2); border: 1px solid var(--border); }
.fb-btn-secondary:hover { border-color: var(--accent); color: var(--accent); }
.fb-btn-success { background: var(--green); color: #fff; }
.fb-btn-sm { padding: 6px 12px; font-size: 12px; }

.fb-table { width: 100%; border-collapse: collapse; }
.fb-table th {
    text-align: left; padding: 11px 14px;
    font-size: 10.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .05em; color: var(--text-3);
    background: var(--surface-2); border-bottom: 2px solid var(--border);
}
.fb-table td {
    padding: 13px 14px; border-bottom: 1px solid var(--border);
    vertical-align: middle; font-size: 13px; color: var(--text);
}
.fb-table tr:last-child td { border-bottom: none; }
.fb-table tr:hover td { background: rgba(24,119,242,0.02); }

.fb-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700;
}
.fb-badge-acheteur { background: #dbeafe; color: #1e40af; }
.fb-badge-vendeur  { background: #fce7f3; color: #9d174d; }
.fb-badge-attirer  { background: var(--green-bg);  color: var(--green); }
.fb-badge-connecter{ background: var(--amber-bg);  color: var(--amber); }
.fb-badge-convertir{ background: var(--accent-bg); color: var(--accent); }
.fb-badge-planned  { background: #dbeafe; color: #1e40af; }
.fb-badge-published{ background: var(--green-bg);  color: var(--green); }
.fb-badge-draft    { background: var(--surface-3); color: var(--text-3); }

@media (max-width: 768px) {
    .fb-form-row { grid-template-columns: 1fr; }
    .fb-stats-row { flex-wrap: wrap; }
    .fb-header { flex-direction: column; text-align: center; }
    .fb-tabs { flex-wrap: wrap; }
}
</style>

<div class="fb-module">

    <!-- Header -->
    <div class="fb-header">
        <div class="fb-hd-icon"><i class="fab fa-facebook-f"></i></div>
        <div>
            <h1>Communication Facebook Organique</h1>
            <p>Stratégie d'attraction sur votre profil personnel &bull; Méthode MERE &bull; Zéro publicité</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="fb-stats-row">
        <?php
        $statItems = [
            ['icon' => 'fa-pen',           'bg' => '#dbeafe', 'color' => '#1877f2', 'val' => $stats['total'],      'label' => 'Publications créées'],
            ['icon' => 'fa-calendar-check','bg' => '#dcfce7', 'color' => '#16a34a', 'val' => $stats['this_month'], 'label' => 'Ce mois-ci'],
            ['icon' => 'fa-clock',         'bg' => '#fef3c7', 'color' => '#d97706', 'val' => $stats['planned'],    'label' => 'Planifiées'],
            ['icon' => 'fa-users',         'bg' => '#f3e8ff', 'color' => '#7c3aed', 'val' => count($personas),     'label' => 'Personas actifs'],
        ];
        foreach ($statItems as $s): ?>
        <div class="fb-stat-mini">
            <div class="fb-stat-icon" style="background:<?= $s['bg'] ?>;color:<?= $s['color'] ?>">
                <i class="fas <?= $s['icon'] ?>"></i>
            </div>
            <div>
                <div class="fb-stat-val"><?= (int)$s['val'] ?></div>
                <div class="fb-stat-label"><?= $s['label'] ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Tabs -->
    <div class="fb-tabs">
        <?php
        $tabs = [
            'strategie' => ['fa-graduation-cap', 'Comprendre la stratégie'],
            'rediger'   => ['fa-pen-fancy',       'Rédiger un post'],
            'journal'   => ['fa-book',            'Journal'],
            'idees'     => ['fa-lightbulb',       'Banque d\'idées'],
        ];
        foreach ($tabs as $slug => [$icon, $label]):
            $active = $tab === $slug ? ' active' : '';
        ?>
        <a href="?page=facebook&tab=<?= $slug ?>" class="fb-tab<?= $active ?>">
            <i class="fas <?= $icon ?>"></i> <?= $label ?>
            <?php if ($slug === 'journal' && $stats['planned'] > 0): ?>
            <span class="fb-tab-badge"><?= $stats['planned'] ?></span>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Contenu tabs -->
    <?php
    $allowedTabs = ['strategie', 'rediger', 'journal', 'idees'];
    $tab = in_array($tab, $allowedTabs, true) ? $tab : 'strategie';
    $tabFile = __DIR__ . '/tabs/' . $tab . '.php';
    if (file_exists($tabFile)):
        include $tabFile;
    else: ?>
    <div style="text-align:center;padding:60px 20px;color:var(--text-3)">
        <i class="fas fa-tools" style="font-size:2rem;opacity:.3;display:block;margin-bottom:14px"></i>
        <h3 style="font-size:15px;font-weight:700;color:var(--text-2);margin-bottom:8px">
            Onglet en préparation
        </h3>
        <p style="font-size:13px">Le fichier <code>tabs/<?= htmlspecialchars($tab) ?>.php</code> n'existe pas encore.</p>
    </div>
    <?php endif; ?>

</div>