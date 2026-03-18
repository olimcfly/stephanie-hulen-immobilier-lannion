<?php
// ======================================================
// Module RÉSEAUX SOCIAUX - Hub Central V2
// /admin/modules/social/reseaux-sociaux/index.php
// Chargé via dashboard.php — pas d'include layout ici
// ======================================================

if (!defined('ADMIN_ROUTER')) {
    die("Accès direct interdit.");
}

// ====================================================
// RÉCUPÉRATION DES DONNÉES
// ====================================================

$platforms = [
    'facebook'  => ['name' => 'Facebook',  'icon' => 'fab fa-facebook-f',  'color' => '#1877F2', 'gradient' => 'linear-gradient(135deg, #1877F2, #0d5bbd)', 'module' => 'facebook'],
    'instagram' => ['name' => 'Instagram', 'icon' => 'fab fa-instagram',   'color' => '#E1306C', 'gradient' => 'linear-gradient(135deg, #833AB4, #FD1D1D, #F77737)', 'module' => 'instagram'],
    'linkedin'  => ['name' => 'LinkedIn',  'icon' => 'fab fa-linkedin-in', 'color' => '#0A66C2', 'gradient' => 'linear-gradient(135deg, #0A66C2, #004182)', 'module' => 'linkedin'],
    'tiktok'    => ['name' => 'TikTok',    'icon' => 'fab fa-tiktok',      'color' => '#000000', 'gradient' => 'linear-gradient(135deg, #000000, #25F4EE)', 'module' => 'tiktok'],
    'youtube'   => ['name' => 'YouTube',   'icon' => 'fab fa-youtube',     'color' => '#FF0000', 'gradient' => 'linear-gradient(135deg, #FF0000, #cc0000)', 'module' => 'youtube'],
];

$platformStats = [];
foreach ($platforms as $key => $p) {
    $platformStats[$key] = ['total' => 0, 'published' => 0, 'scheduled' => 0, 'draft' => 0];
}

$globalStats = ['total' => 0, 'published' => 0, 'scheduled' => 0, 'draft' => 0, 'this_week' => 0, 'this_month' => 0];

try {
    $stmt = $pdo->query("
        SELECT platform, status, COUNT(*) as total
        FROM social_posts
        GROUP BY platform, status
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $pf    = strtolower($row['platform']);
        $st    = strtolower($row['status']);
        $count = (int)$row['total'];
        if (isset($platformStats[$pf])) {
            $platformStats[$pf]['total'] += $count;
            if (isset($platformStats[$pf][$st])) $platformStats[$pf][$st] = $count;
        }
        $globalStats['total'] += $count;
        if (isset($globalStats[$st])) $globalStats[$st] += $count;
    }
} catch (Exception $e) {}

try {
    $stmt = $pdo->query("
        SELECT
            SUM(CASE WHEN scheduled_at BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as this_week,
            SUM(CASE WHEN MONTH(scheduled_at) = MONTH(CURDATE()) AND YEAR(scheduled_at) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as this_month
        FROM social_posts WHERE status = 'scheduled'
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $globalStats['this_week']  = (int)($row['this_week']  ?? 0);
    $globalStats['this_month'] = (int)($row['this_month'] ?? 0);
} catch (Exception $e) {}

$tiktokScripts = 0;
try {
    $tiktokScripts = (int)$pdo->query("SELECT COUNT(*) FROM tiktok_scripts")->fetchColumn();
} catch (Exception $e) {}

$connectedAccounts = [];
try {
    $connectedAccounts = $pdo->query("SELECT * FROM social_accounts WHERE is_active = 1 ORDER BY platform")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$connectedMap = [];
foreach ($connectedAccounts as $acc) {
    $connectedMap[strtolower($acc['platform'])] = $acc;
}

$upcomingPosts = [];
try {
    $upcomingPosts = $pdo->query("
        SELECT * FROM social_posts
        WHERE status = 'scheduled' AND scheduled_at >= NOW()
        ORDER BY scheduled_at ASC LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$recentPosts = [];
try {
    $recentPosts = $pdo->query("
        SELECT * FROM social_posts
        WHERE status = 'published'
        ORDER BY published_at DESC LIMIT 8
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$journalStats = ['total' => 0, 'pending' => 0];
try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM editorial_journal GROUP BY status");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $journalStats['total'] += (int)$row['total'];
        if (in_array($row['status'], ['pending', 'planned'])) $journalStats['pending'] += (int)$row['total'];
    }
} catch (Exception $e) {}

// Conseiller (pour les tips personnalisés)
$advisorName = $advisorName ?? 'votre conseiller';
$advisorCity = $advisorCity ?? '';
?>

<style>
/* ========== Hub Réseaux Sociaux V2 ========== */

.hub-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-radius: 16px;
    padding: 36px;
    color: #fff;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}
.hub-hero::before {
    content: '';
    position: absolute; top: -80px; right: -60px;
    width: 260px; height: 260px;
    background: rgba(255,255,255,0.03);
    border-radius: 50%;
}
.hub-hero::after {
    content: '';
    position: absolute; bottom: -40px; left: 30%;
    width: 180px; height: 180px;
    background: rgba(255,255,255,0.02);
    border-radius: 50%;
}
.hub-hero-top {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 16px; margin-bottom: 24px;
    position: relative; z-index: 1;
}
.hub-hero h1 {
    font-size: 1.8rem; font-weight: 800; margin: 0 0 4px 0;
    display: flex; align-items: center; gap: 12px;
}
.hub-hero h1 i { font-size: 1.6rem; opacity: 0.8; }
.hub-hero p    { margin: 0; opacity: 0.7; font-size: 0.92rem; }

.hub-hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.hub-hero-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: 10px;
    font-weight: 600; font-size: 0.88rem;
    text-decoration: none; transition: all 0.2s;
    border: none; cursor: pointer;
}
.hub-hero-btn.primary { background: rgba(255,255,255,0.95); color: #1a1a2e; }
.hub-hero-btn.primary:hover { background: #fff; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
.hub-hero-btn.ghost { background: rgba(255,255,255,0.12); color: #fff; border: 1px solid rgba(255,255,255,0.25); }
.hub-hero-btn.ghost:hover { background: rgba(255,255,255,0.2); }

.hub-global-stats {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px; position: relative; z-index: 1;
}
.hub-gstat {
    background: rgba(255,255,255,0.08); border-radius: 10px;
    padding: 14px 16px; text-align: center;
    border: 1px solid rgba(255,255,255,0.08);
}
.hub-gstat-value { font-size: 1.6rem; font-weight: 800; color: #fff; line-height: 1; }
.hub-gstat-label { font-size: 0.72rem; color: rgba(255,255,255,0.55); margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

/* Plateformes */
.hub-platforms {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px; margin-bottom: 28px;
}
.hub-platform-card {
    background: var(--surface); border-radius: 14px;
    overflow: hidden; border: 1px solid var(--border);
    transition: all 0.25s;
}
.hub-platform-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
.hub-pcard-header {
    padding: 20px 20px 16px; color: #fff;
    display: flex; align-items: center; justify-content: space-between;
}
.hub-pcard-name { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1.1rem; }
.hub-pcard-name i { font-size: 1.3rem; }
.hub-pcard-status {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 16px;
    font-size: 0.72rem; font-weight: 600;
}
.hub-pcard-status.connected    { background: rgba(255,255,255,0.25); color: #fff; }
.hub-pcard-status.disconnected { background: rgba(0,0,0,0.15); color: rgba(255,255,255,0.7); }
.hub-pcard-body { padding: 16px 20px; }
.hub-pcard-stats {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 8px; margin-bottom: 14px;
}
.hub-pcard-stat { text-align: center; padding: 8px 4px; background: var(--surface-2); border-radius: 8px; }
.hub-pcard-stat-val { font-size: 1.2rem; font-weight: 800; color: var(--text); line-height: 1; }
.hub-pcard-stat-lbl { font-size: 0.68rem; color: var(--text-3); margin-top: 3px; text-transform: uppercase; letter-spacing: 0.3px; }
.hub-pcard-actions { display: flex; gap: 8px; }
.hub-pcard-btn {
    flex: 1; display: inline-flex; align-items: center; justify-content: center;
    gap: 6px; padding: 9px 14px; border-radius: 8px;
    font-weight: 600; font-size: 0.82rem;
    text-decoration: none; transition: all 0.2s; border: none; cursor: pointer;
}
.hub-pcard-btn.view { background: var(--surface-3); color: var(--text-2); }
.hub-pcard-btn.view:hover { background: var(--border); color: var(--text); }
.hub-pcard-btn.create { color: #fff; }
.hub-pcard-btn.create:hover { opacity: 0.9; transform: translateY(-1px); }

/* Colonnes */
.hub-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; }
.hub-col-card { background: var(--surface); border-radius: 14px; border: 1px solid var(--border); overflow: hidden; }
.hub-col-header {
    padding: 18px 22px; border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.hub-col-title { font-size: 1rem; font-weight: 700; color: var(--text); display: flex; align-items: center; gap: 8px; margin: 0; }
.hub-col-badge { background: var(--surface-2); color: var(--text-2); padding: 3px 10px; border-radius: 10px; font-size: 0.75rem; font-weight: 700; }
.hub-col-body { padding: 16px 22px; }

.hub-post-item { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--border); }
.hub-post-item:last-child { border-bottom: none; }
.hub-post-platform { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 0.85rem; flex-shrink: 0; }
.hub-post-info { flex: 1; min-width: 0; }
.hub-post-title { font-weight: 600; font-size: 0.87rem; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.hub-post-date  { font-size: 0.75rem; color: var(--text-3); margin-top: 2px; }
.hub-post-status { padding: 3px 10px; border-radius: 6px; font-size: 0.72rem; font-weight: 600; flex-shrink: 0; }
.hub-post-status.scheduled { background: var(--accent-bg); color: var(--accent); }
.hub-post-status.published { background: var(--green-bg);  color: var(--green); }
.hub-post-empty { text-align: center; padding: 30px 16px; color: var(--text-3); }
.hub-post-empty i { font-size: 2rem; margin-bottom: 10px; display: block; opacity: .3; }

/* Accès rapides */
.hub-quick-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 12px; margin-bottom: 28px;
}
.hub-quick-card {
    background: var(--surface); border-radius: 12px; padding: 20px;
    text-align: center; border: 1px solid var(--border);
    text-decoration: none; color: var(--text-2); transition: all 0.2s;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
}
.hub-quick-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); border-color: var(--accent); }
.hub-quick-card i    { font-size: 1.4rem; }
.hub-quick-card span { font-weight: 600; font-size: 0.85rem; color: var(--text); }
.hub-quick-card small { font-size: 0.72rem; color: var(--text-3); }

/* Tips stratégie */
.hub-tips {
    background: var(--surface-2); border-radius: 14px;
    padding: 28px; border: 1px solid var(--border); margin-bottom: 28px;
}
.hub-tips h3 { font-size: 1.1rem; color: var(--text); margin: 0 0 16px 0; display: flex; align-items: center; gap: 10px; }
.hub-tips-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; }
.hub-tip {
    padding: 14px 16px; background: var(--surface);
    border-radius: 10px; font-size: 0.85rem; color: var(--text-2);
    line-height: 1.5; border-left: 3px solid #0A66C2;
}
.hub-tip strong { color: var(--text); }
.hub-tip .tip-platform {
    display: inline-block; font-size: 0.7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.5px;
    padding: 2px 8px; border-radius: 4px; margin-bottom: 6px;
}
.hub-tip .tip-platform.fb  { background: #e8f0fe; color: #1877F2; }
.hub-tip .tip-platform.ig  { background: #fce4ec; color: #E1306C; }
.hub-tip .tip-platform.li  { background: #e3f0fa; color: #0A66C2; }
.hub-tip .tip-platform.tk  { background: #e0f7fa; color: #000; }
.hub-tip .tip-platform.all { background: var(--accent-bg); color: var(--accent); }

@media (max-width: 900px) { .hub-columns { grid-template-columns: 1fr; } }
@media (max-width: 768px) {
    .hub-hero { padding: 24px; }
    .hub-hero-top { flex-direction: column; text-align: center; }
    .hub-hero-actions { width: 100%; justify-content: center; }
    .hub-platforms { grid-template-columns: 1fr; }
    .hub-global-stats { grid-template-columns: repeat(3, 1fr); }
    .hub-quick-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- ===== Hero ===== -->
<div class="hub-hero">
    <div class="hub-hero-top">
        <div>
            <h1><i class="fas fa-share-alt"></i> Réseaux Sociaux</h1>
            <p>Tableau de bord centralisé — gérez toutes vos plateformes depuis un seul endroit</p>
        </div>
        <div class="hub-hero-actions">
            <a href="?page=journal" class="hub-hero-btn primary">
                <i class="fas fa-calendar-alt"></i> Journal Éditorial
            </a>
            <a href="?page=strategy-module" class="hub-hero-btn ghost">
                <i class="fas fa-chess"></i> Stratégie Contenu
            </a>
        </div>
    </div>

    <div class="hub-global-stats">
        <?php
        $gstats = [
            [$globalStats['total'],           'Publications'],
            [$globalStats['published'],        'Publiées'],
            [$globalStats['scheduled'],        'Planifiées'],
            [$globalStats['draft'],            'Brouillons'],
            [$globalStats['this_week'],        'Cette semaine'],
            [$globalStats['this_month'],       'Ce mois'],
            [$tiktokScripts,                   'Scripts TikTok'],
            [$journalStats['pending'],         'À rédiger'],
        ];
        foreach ($gstats as [$val, $lbl]): ?>
        <div class="hub-gstat">
            <div class="hub-gstat-value"><?= (int)$val ?></div>
            <div class="hub-gstat-label"><?= $lbl ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ===== Cartes Plateformes ===== -->
<div class="hub-platforms">
    <?php foreach ($platforms as $key => $pf):
        $stats       = $platformStats[$key];
        $isConnected = !empty($connectedMap[$key]);
    ?>
    <div class="hub-platform-card">
        <div class="hub-pcard-header" style="background: <?= $pf['gradient'] ?>;">
            <div class="hub-pcard-name">
                <i class="<?= $pf['icon'] ?>"></i>
                <?= $pf['name'] ?>
            </div>
            <span class="hub-pcard-status <?= $isConnected ? 'connected' : 'disconnected' ?>">
                <i class="fas fa-<?= $isConnected ? 'check-circle' : 'unlink' ?>"></i>
                <?= $isConnected ? 'Connecté' : 'Non connecté' ?>
            </span>
        </div>
        <div class="hub-pcard-body">
            <div class="hub-pcard-stats">
                <?php foreach (['total' => 'Posts', 'published' => 'Publiés', 'scheduled' => 'Planifiés'] as $k => $lbl): ?>
                <div class="hub-pcard-stat">
                    <div class="hub-pcard-stat-val"><?= $stats[$k] ?></div>
                    <div class="hub-pcard-stat-lbl"><?= $lbl ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="hub-pcard-actions">
                <a href="?page=<?= $pf['module'] ?>" class="hub-pcard-btn view">
                    <i class="fas fa-list"></i> Voir
                </a>
                <a href="?page=<?= $pf['module'] ?>&action=create"
                   class="hub-pcard-btn create" style="background: <?= $pf['color'] ?>;">
                    <i class="fas fa-plus"></i> Créer
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ===== Accès rapides ===== -->
<div class="hub-quick-grid">
    <?php
    $quickLinks = [
        ['journal',          'fa-calendar-alt',   '#7c3aed', 'Journal Éditorial',  $journalStats['total'].' idées de contenu'],
        ['strategy-module',  'fa-chess',          '#e67e22', 'Matrice Stratégique','Planification par cible'],
        ['tiktok',           'fa-scroll',         '#000000', 'Scripts TikTok',     $tiktokScripts.' scripts créés'],
        ['articles',         'fa-blog',           '#16a34a', 'Blog / Articles',    'Contenu SEO'],
        ['local-seo',        'fa-map-marker-alt', '#4285f4', 'Google My Business', 'Posts GMB locaux'],
        ['ai',               'fa-robot',          '#833AB4', 'Générateur IA',      'Créer avec l\'IA'],
    ];
    foreach ($quickLinks as [$slug, $icon, $color, $label, $sub]): ?>
    <a href="?page=<?= $slug ?>" class="hub-quick-card">
        <i class="fas <?= $icon ?>" style="color:<?= $color ?>"></i>
        <span><?= $label ?></span>
        <small><?= $sub ?></small>
    </a>
    <?php endforeach; ?>
</div>

<!-- ===== Deux colonnes ===== -->
<div class="hub-columns">

    <!-- Prochaines publications -->
    <div class="hub-col-card">
        <div class="hub-col-header">
            <h3 class="hub-col-title">
                <i class="fas fa-clock" style="color: var(--accent)"></i> Prochaines publications
            </h3>
            <span class="hub-col-badge"><?= count($upcomingPosts) ?></span>
        </div>
        <div class="hub-col-body">
            <?php if (empty($upcomingPosts)): ?>
                <div class="hub-post-empty">
                    <i class="fas fa-calendar-plus"></i>
                    Aucune publication planifiée.<br>
                    <small>Planifiez vos prochains posts depuis chaque plateforme.</small>
                </div>
            <?php else: ?>
                <?php foreach ($upcomingPosts as $post):
                    $pf       = strtolower($post['platform'] ?? '');
                    $pfInfo   = $platforms[$pf] ?? ['color' => '#888', 'icon' => 'fas fa-share-alt'];
                    $postTitle = mb_substr(strip_tags($post['content'] ?? $post['title'] ?? 'Sans titre'), 0, 50);
                    $schedDate = !empty($post['scheduled_at']) ? date('d/m H:i', strtotime($post['scheduled_at'])) : '-';
                ?>
                <div class="hub-post-item">
                    <div class="hub-post-platform" style="background: <?= $pfInfo['color'] ?>;">
                        <i class="<?= $pfInfo['icon'] ?>"></i>
                    </div>
                    <div class="hub-post-info">
                        <div class="hub-post-title"><?= htmlspecialchars($postTitle) ?></div>
                        <div class="hub-post-date"><i class="fas fa-clock"></i> <?= $schedDate ?></div>
                    </div>
                    <span class="hub-post-status scheduled">Planifié</span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Dernières publications -->
    <div class="hub-col-card">
        <div class="hub-col-header">
            <h3 class="hub-col-title">
                <i class="fas fa-check-circle" style="color: var(--green)"></i> Dernières publications
            </h3>
            <span class="hub-col-badge"><?= count($recentPosts) ?></span>
        </div>
        <div class="hub-col-body">
            <?php if (empty($recentPosts)): ?>
                <div class="hub-post-empty">
                    <i class="fas fa-inbox"></i>
                    Aucune publication récente.<br>
                    <small>Vos dernières publications apparaîtront ici.</small>
                </div>
            <?php else: ?>
                <?php foreach ($recentPosts as $post):
                    $pf       = strtolower($post['platform'] ?? '');
                    $pfInfo   = $platforms[$pf] ?? ['color' => '#888', 'icon' => 'fas fa-share-alt'];
                    $postTitle = mb_substr(strip_tags($post['content'] ?? $post['title'] ?? 'Sans titre'), 0, 50);
                    $pubDate   = !empty($post['published_at']) ? date('d/m H:i', strtotime($post['published_at'])) : '-';
                ?>
                <div class="hub-post-item">
                    <div class="hub-post-platform" style="background: <?= $pfInfo['color'] ?>;">
                        <i class="<?= $pfInfo['icon'] ?>"></i>
                    </div>
                    <div class="hub-post-info">
                        <div class="hub-post-title"><?= htmlspecialchars($postTitle) ?></div>
                        <div class="hub-post-date">
                            <i class="fas fa-check-circle" style="color:var(--green)"></i> <?= $pubDate ?>
                        </div>
                    </div>
                    <span class="hub-post-status published">Publié</span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- ===== Conseils Stratégiques ===== -->
<div class="hub-tips">
    <h3>
        <i class="fas fa-lightbulb" style="color: var(--amber)"></i>
        Stratégie Réseaux Sociaux
        <?= $advisorName !== 'votre conseiller' ? '— '.htmlspecialchars($advisorName).($advisorCity ? ', '.htmlspecialchars($advisorCity) : '') : '' ?>
    </h3>
    <div class="hub-tips-grid">
        <?php
        $tips = [
            ['all', 'Stratégie globale', 'Régularité :', 'Publier minimum 3-5 fois/semaine sur chaque plateforme active. La constance bat la perfection.'],
            ['fb',  'Facebook',          'Communauté locale :', 'Groupes immobilier locaux, événements quartier, témoignages clients — format vidéo natif privilégié.'],
            ['ig',  'Instagram',         'Visuel premium :', 'Reels visites virtuelles, carrousels quartiers, stories coulisses — publier entre 7h-9h ou 18h-21h.'],
            ['li',  'LinkedIn',          'Expert immobilier :', 'Articles marché local, carrousels PDF, posts d\'opinion — ne jamais mettre de lien dans le post.'],
            ['tk',  'TikTok',            'Authenticité :', 'Coulisses métier, réponses aux questions, visites express 30s — hook puissant dans les 2 premières secondes.'],
            ['all', 'Méthode MERE',      'Sur toutes les plateformes :', 'Miroir (montrer le problème) → Émotion (créer l\'envie) → Réassurance (prouver) → Exclusivité (appel à l\'action).'],
        ];
        foreach ($tips as [$cls, $platform, $bold, $text]): ?>
        <div class="hub-tip">
            <div class="tip-platform <?= $cls ?>"><?= $platform ?></div>
            <strong><?= $bold ?></strong> <?= $text ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>