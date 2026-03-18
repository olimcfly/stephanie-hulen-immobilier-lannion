<?php
// ======================================================
// Module INSTAGRAM - Gestion des publications
// /admin/modules/social/instagram/index.php
// Chargé via dashboard.php — pas de session/connexion ici
// ======================================================

if (!defined('ADMIN_ROUTER')) {
    die("Accès direct interdit.");
}

// $pdo et $_SESSION['csrf_token'] sont déjà disponibles via dashboard.php

// ====================================================
// ACTIONS POST
// ====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Token CSRF invalide.";
        header("Location: ?page=instagram");
        exit;
    }

    $postId = (int)($_POST['post_id'] ?? 0);

    switch ($_POST['action']) {
        case 'delete':
            try {
                $stmt = $pdo->prepare("DELETE FROM social_posts WHERE id = ? AND platform = 'instagram'");
                $stmt->execute([$postId]);
                $_SESSION['success_message'] = "Publication supprimée.";
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Erreur suppression : " . $e->getMessage();
            }
            break;

        case 'publish':
            try {
                $stmt = $pdo->prepare("UPDATE social_posts SET status = 'published', published_at = NOW() WHERE id = ? AND platform = 'instagram'");
                $stmt->execute([$postId]);
                $_SESSION['success_message'] = "Publication marquée comme publiée.";
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
            }
            break;

        case 'schedule':
            $scheduledAt = $_POST['scheduled_at'] ?? '';
            if ($scheduledAt) {
                try {
                    $stmt = $pdo->prepare("UPDATE social_posts SET status = 'scheduled', scheduled_at = ? WHERE id = ? AND platform = 'instagram'");
                    $stmt->execute([$scheduledAt, $postId]);
                    $_SESSION['success_message'] = "Publication planifiée.";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Erreur planification : " . $e->getMessage();
                }
            }
            break;
    }

    header("Location: ?page=instagram");
    exit;
}

// ====================================================
// DONNÉES
// ====================================================

$activeTab = $_GET['tab'] ?? 'all';

$igStats = [
    'total' => 0, 'published' => 0, 'scheduled' => 0, 'draft' => 0,
    'reels' => 0, 'stories' => 0, 'carousels' => 0, 'posts' => 0,
];

try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM social_posts WHERE platform = 'instagram' GROUP BY status");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $igStats['total'] += (int)$row['total'];
        $st = strtolower($row['status']);
        if (isset($igStats[$st])) $igStats[$st] = (int)$row['total'];
    }
} catch (Exception $e) {}

try {
    $stmt = $pdo->query("
        SELECT COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')), 'post') as content_type,
               COUNT(*) as total
        FROM social_posts WHERE platform = 'instagram' GROUP BY content_type
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $type = strtolower($row['content_type']);
        if (in_array($type, ['reel','reels']))           $igStats['reels']    = (int)$row['total'];
        elseif (in_array($type, ['story','stories']))    $igStats['stories']  = (int)$row['total'];
        elseif (in_array($type, ['carousel','carrousel']))$igStats['carousels']= (int)$row['total'];
        else                                              $igStats['posts']    = (int)$row['total'];
    }
} catch (Exception $e) {}

$igAccount = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM social_accounts WHERE platform = 'instagram' AND is_active = 1 LIMIT 1");
    $stmt->execute();
    $igAccount = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// WHERE clause
$allowedTabs = ['all','published','scheduled','draft','reels','stories','carousels'];
$activeTab   = in_array($activeTab, $allowedTabs, true) ? $activeTab : 'all';

$whereClause = "WHERE platform = 'instagram'";
$params = [];

switch ($activeTab) {
    case 'published': $whereClause .= " AND status = 'published'"; break;
    case 'scheduled': $whereClause .= " AND status = 'scheduled'"; break;
    case 'draft':     $whereClause .= " AND status = 'draft'";     break;
    case 'reels':     $whereClause .= " AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')) IN ('reel','reels')"; break;
    case 'stories':   $whereClause .= " AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')) IN ('story','stories')"; break;
    case 'carousels': $whereClause .= " AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')) IN ('carousel','carrousel')"; break;
}

$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $whereClause .= " AND (title LIKE ? OR content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$currentPage = max(1, (int)($_GET['p'] ?? 1));
$perPage     = 15;
$offset      = ($currentPage - 1) * $perPage;

$publications = [];
$totalItems   = 0;

try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM social_posts {$whereClause}");
    $countStmt->execute($params);
    $totalItems = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT * FROM social_posts {$whereClause}
        ORDER BY
            CASE WHEN status = 'scheduled' THEN 0 WHEN status = 'draft' THEN 1 ELSE 2 END,
            COALESCE(scheduled_at, created_at) DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$totalPages = $totalItems > 0 ? (int)ceil($totalItems / $perPage) : 1;

// Flash messages
$successMsg = $_SESSION['success_message'] ?? null;
$errorMsg   = $_SESSION['error_message']   ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Helpers
$typeLabels = [
    'reel'=>'🎬 Reel','reels'=>'🎬 Reel',
    'story'=>'⏱️ Story','stories'=>'⏱️ Story',
    'carousel'=>'🎠 Carrousel','carrousel'=>'🎠 Carrousel',
];
$statusLabels = [
    'published' => '✅ Publiée',
    'scheduled' => '📅 Planifiée',
    'draft'     => '📝 Brouillon',
];
$typeIcons = [
    'reel'=>'fa-film','reels'=>'fa-film',
    'story'=>'fa-circle-notch','stories'=>'fa-circle-notch',
    'carousel'=>'fa-images','carrousel'=>'fa-images',
];
?>

<style>
/* ========== Module Instagram ========== */
.ig-hero {
    background: linear-gradient(135deg, #833AB4 0%, #FD1D1D 50%, #F77737 100%);
    border-radius: var(--radius-lg); padding: 32px; color: #fff;
    margin-bottom: 24px; display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap; gap: 20px;
}
.ig-hero-left h1 {
    font-size: 1.7rem; font-weight: 800; margin: 0 0 6px;
    display: flex; align-items: center; gap: 12px;
}
.ig-hero-left h1 i { font-size: 1.9rem; }
.ig-hero-left p { margin: 0; opacity: 0.88; font-size: 13px; }
.ig-hero-right { display: flex; gap: 10px; }
.ig-hero-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: var(--radius); font-weight: 600;
    font-size: 13px; text-decoration: none; transition: all 0.2s;
    border: none; cursor: pointer;
}
.ig-hero-btn.primary { background: rgba(255,255,255,0.95); color: #833AB4; }
.ig-hero-btn.primary:hover { background: #fff; transform: translateY(-2px); }
.ig-hero-btn.secondary { background: rgba(255,255,255,0.18); color: #fff; border: 1px solid rgba(255,255,255,0.35); }
.ig-hero-btn.secondary:hover { background: rgba(255,255,255,0.28); }

.ig-stats-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 14px; margin-bottom: 24px;
}
.ig-stat-card {
    background: var(--surface); border-radius: var(--radius-lg);
    padding: 18px; text-align: center; border: 1px solid var(--border);
    transition: all 0.2s; box-shadow: var(--shadow-sm);
}
.ig-stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
.ig-stat-icon  { font-size: 1.4rem; margin-bottom: 6px; }
.ig-stat-value { font-size: 1.7rem; font-weight: 900; color: var(--text); line-height: 1; }
.ig-stat-label { font-size: 0.72rem; color: var(--text-3); margin-top: 4px; text-transform: uppercase; letter-spacing: 0.5px; }

.ig-account-bar {
    background: var(--surface); border-radius: var(--radius-lg);
    padding: 16px 22px; margin-bottom: 22px;
    display: flex; align-items: center; justify-content: space-between;
    border: 1px solid var(--border); flex-wrap: wrap; gap: 12px;
    box-shadow: var(--shadow-sm);
}
.ig-account-info { display: flex; align-items: center; gap: 12px; }
.ig-account-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, #833AB4, #FD1D1D, #F77737);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: 1.1rem; flex-shrink: 0;
}
.ig-account-name   { font-weight: 700; color: var(--text); font-size: 14px; }
.ig-account-handle { font-size: 12px; color: var(--text-3); }

.ig-connect-box {
    background: var(--surface); border-radius: var(--radius-lg);
    padding: 48px; text-align: center;
    border: 2px dashed var(--border); margin-bottom: 22px;
}
.ig-connect-box .ig-connect-icon {
    font-size: 3.5rem; margin-bottom: 16px; display: block;
    background: linear-gradient(135deg, #833AB4, #FD1D1D, #F77737);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.ig-connect-box h3 { font-size: 1.2rem; color: var(--text); margin: 0 0 8px; }
.ig-connect-box p  { color: var(--text-3); margin: 0 auto 20px; max-width: 500px; font-size: 13px; }
.ig-connect-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 28px;
    background: linear-gradient(135deg, #833AB4, #FD1D1D, #F77737);
    color: #fff; border: none; border-radius: var(--radius);
    font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.2s;
}
.ig-connect-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(131,58,180,.35); }

.ig-quick-create {
    background: var(--surface); border-radius: var(--radius-lg);
    padding: 22px; margin-bottom: 22px; border: 1px solid var(--border);
    box-shadow: var(--shadow-sm);
}
.ig-quick-create h3 { font-size: 13px; font-weight: 700; color: var(--text); margin: 0 0 14px; }
.ig-quick-types {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px;
}
.ig-quick-type {
    display: flex; flex-direction: column; align-items: center; gap: 7px;
    padding: 18px 14px; border-radius: var(--radius-lg);
    border: 2px solid var(--border); text-decoration: none; color: var(--text-2);
    transition: all 0.2s; background: var(--surface-2); font-size: 13px; font-weight: 600;
}
.ig-quick-type:hover { border-color: #833AB4; background: #f9f5ff; color: #833AB4; transform: translateY(-2px); }
.ig-quick-type i { font-size: 1.4rem; }

.ig-tabs {
    display: flex; gap: 3px; background: var(--surface-2);
    padding: 5px; border-radius: var(--radius-lg); margin-bottom: 18px;
    border: 1px solid var(--border); overflow-x: auto; flex-wrap: wrap;
}
.ig-tab {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 15px; border-radius: var(--radius);
    font-size: 12.5px; font-weight: 600; color: var(--text-2);
    text-decoration: none; transition: all 0.15s; white-space: nowrap;
}
.ig-tab:hover { background: var(--surface); color: var(--text); }
.ig-tab.active { background: var(--surface); color: #833AB4; box-shadow: var(--shadow-sm); }
.ig-tab .ig-tab-count {
    background: var(--surface-3); color: var(--text-3);
    padding: 2px 7px; border-radius: 10px; font-size: 10px; font-weight: 700;
}
.ig-tab.active .ig-tab-count { background: rgba(131,58,180,.1); color: #833AB4; }

.ig-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; margin-bottom: 18px; flex-wrap: wrap;
}
.ig-search {
    display: flex; align-items: center; gap: 8px;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 8px 14px;
    flex: 1; max-width: 380px; transition: border-color .15s;
}
.ig-search:focus-within { border-color: #833AB4; }
.ig-search input { border: none; outline: none; font-size: 13px; width: 100%; background: transparent; color: var(--text); font-family: var(--font); }
.ig-search input::placeholder { color: var(--text-3); }
.ig-search i { color: var(--text-3); font-size: 12px; flex-shrink: 0; }

/* Publications */
.ig-pub-list { display: flex; flex-direction: column; gap: 10px; }
.ig-pub-card {
    background: var(--surface); border-radius: var(--radius-lg);
    border: 1px solid var(--border); padding: 18px;
    display: flex; align-items: flex-start; gap: 14px; transition: all 0.2s;
    box-shadow: var(--shadow-sm);
}
.ig-pub-card:hover { box-shadow: var(--shadow); border-color: #ccc; }
.ig-pub-thumb {
    width: 68px; height: 68px; border-radius: var(--radius);
    background: linear-gradient(135deg, #f5f0ff, #fff0f0);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden; color: var(--text-3); font-size: 1.4rem;
}
.ig-pub-thumb img { width: 100%; height: 100%; object-fit: cover; }
.ig-pub-body { flex: 1; min-width: 0; }
.ig-pub-header { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; flex-wrap: wrap; }
.ig-pub-title  { font-weight: 700; color: var(--text); font-size: 13.5px; }
.ig-pub-excerpt { color: var(--text-2); font-size: 12.5px; line-height: 1.5; margin-bottom: 7px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.ig-pub-meta { display: flex; align-items: center; gap: 12px; font-size: 11.5px; color: var(--text-3); flex-wrap: wrap; }
.ig-pub-actions { display: flex; gap: 5px; align-items: flex-start; flex-shrink: 0; }
.ig-pub-action {
    width: 32px; height: 32px; border-radius: var(--radius);
    border: 1px solid var(--border); background: var(--surface-2);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-3); cursor: pointer; transition: all 0.15s; font-size: 12px;
    text-decoration: none;
}
.ig-pub-action:hover        { background: var(--surface-3); color: var(--text); }
.ig-pub-action.danger:hover { background: var(--red-bg); color: var(--red); border-color: #fca5a5; }
.ig-pub-action.success:hover{ background: var(--green-bg); color: var(--green); border-color: #a7f3d0; }

.ig-type-badge {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 2px 9px; border-radius: 6px; font-size: 11px; font-weight: 700;
}
.ig-type-badge.reel,.ig-type-badge.reels         { background: #f0e6ff; color: #833AB4; }
.ig-type-badge.story,.ig-type-badge.stories       { background: var(--amber-bg); color: #F77737; }
.ig-type-badge.carousel,.ig-type-badge.carrousel  { background: var(--accent-bg); color: var(--accent); }
.ig-type-badge.post                               { background: var(--surface-3); color: var(--text-2); }

.ig-status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; }
.ig-status-badge.published { background: var(--green-bg);   color: var(--green); }
.ig-status-badge.scheduled { background: var(--accent-bg);  color: var(--accent); }
.ig-status-badge.draft     { background: var(--surface-3);  color: var(--text-3); }

.ig-empty { text-align: center; padding: 60px 20px; color: var(--text-3); }
.ig-empty i { font-size: 3rem; margin-bottom: 14px; display: block; opacity: .2; }
.ig-empty h3 { color: var(--text-2); margin: 0 0 8px; font-size: 15px; }

.ig-pagination { display: flex; justify-content: center; gap: 5px; margin-top: 22px; }
.ig-pagination a, .ig-pagination span {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 34px; height: 34px; padding: 0 9px; border-radius: var(--radius);
    font-size: 12.5px; font-weight: 600; text-decoration: none; transition: all 0.15s;
}
.ig-pagination a { background: var(--surface); border: 1px solid var(--border); color: var(--text-2); }
.ig-pagination a:hover { border-color: #833AB4; color: #833AB4; }
.ig-pagination span.current { background: #833AB4; color: #fff; border: 1px solid #833AB4; }

.ig-flash { padding: 12px 18px; border-radius: var(--radius); margin-bottom: 18px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 10px; }
.ig-flash.success { background: var(--green-bg);  color: var(--green); border: 1px solid #a7f3d0; }
.ig-flash.error   { background: var(--red-bg);    color: var(--red);   border: 1px solid #fca5a5; }

.ig-tips {
    background: var(--surface-2); border-radius: var(--radius-lg);
    padding: 22px; margin-top: 22px; border: 1px solid var(--border);
}
.ig-tips h3 { font-size: 13px; font-weight: 700; color: #833AB4; margin: 0 0 12px; display: flex; align-items: center; gap: 8px; }
.ig-tips ul { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); gap: 8px; }
.ig-tips li { padding: 8px 12px; background: var(--surface); border-radius: var(--radius); font-size: 12.5px; color: var(--text-2); line-height: 1.5; }
.ig-tips li strong { color: #833AB4; }

@media (max-width: 768px) {
    .ig-hero { padding: 22px; flex-direction: column; text-align: center; }
    .ig-hero-right { width: 100%; justify-content: center; }
    .ig-pub-card { flex-direction: column; }
    .ig-pub-thumb { width: 100%; height: 140px; }
    .ig-pub-actions { width: 100%; justify-content: flex-end; }
    .ig-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<!-- Flash messages -->
<?php if ($successMsg): ?>
<div class="ig-flash success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="ig-flash error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<!-- Hero -->
<div class="ig-hero">
    <div class="ig-hero-left">
        <h1><i class="fab fa-instagram"></i> Instagram</h1>
        <p>Gérez vos publications, Reels, Stories et carrousels</p>
    </div>
    <div class="ig-hero-right">
        <a href="?page=instagram&action=create" class="ig-hero-btn primary">
            <i class="fas fa-plus"></i> Nouvelle publication
        </a>
        <a href="?page=reseaux-sociaux" class="ig-hero-btn secondary">
            <i class="fas fa-th-large"></i> Hub Réseaux
        </a>
    </div>
</div>

<!-- Compte -->
<?php if ($igAccount): ?>
<div class="ig-account-bar">
    <div class="ig-account-info">
        <div class="ig-account-avatar">
            <?= strtoupper(substr($igAccount['account_name'] ?? 'IG', 0, 1)) ?>
        </div>
        <div>
            <div class="ig-account-name"><?= htmlspecialchars($igAccount['account_name'] ?? 'Instagram') ?></div>
            <div class="ig-account-handle">@<?= htmlspecialchars($igAccount['username'] ?? '') ?></div>
        </div>
    </div>
    <span class="badge badge-green"><i class="fas fa-check-circle"></i> Connecté</span>
</div>
<?php else: ?>
<div class="ig-connect-box">
    <i class="fab fa-instagram ig-connect-icon"></i>
    <h3>Connectez votre compte Instagram Business</h3>
    <p>Liez votre compte pour publier directement, suivre vos statistiques et planifier vos publications.</p>
    <button class="ig-connect-btn" onclick="alert('Connexion Instagram via Facebook Graph API — disponible prochainement.\n\nVous pouvez déjà créer et planifier vos publications ici.')">
        <i class="fab fa-instagram"></i> Connecter Instagram
    </button>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="ig-stats-grid">
    <?php
    $statItems = [
        ['📊','total',     'Total'],
        ['✅','published', 'Publiées'],
        ['📅','scheduled', 'Planifiées'],
        ['📝','draft',     'Brouillons'],
        ['🎬','reels',     'Reels'],
        ['⏱️','stories',   'Stories'],
        ['🎠','carousels', 'Carrousels'],
        ['📸','posts',     'Posts photo'],
    ];
    foreach ($statItems as [$icon, $key, $label]): ?>
    <div class="ig-stat-card">
        <div class="ig-stat-icon"><?= $icon ?></div>
        <div class="ig-stat-value"><?= $igStats[$key] ?></div>
        <div class="ig-stat-label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Création rapide -->
<div class="ig-quick-create">
    <h3><i class="fas fa-bolt" style="color:#F77737"></i> Création rapide</h3>
    <div class="ig-quick-types">
        <?php
        $quickTypes = [
            ['post',     'fa-image',       '#833AB4', 'Post photo'],
            ['carousel', 'fa-images',      '#1877F2', 'Carrousel'],
            ['reel',     'fa-film',        '#833AB4', 'Reel'],
            ['story',    'fa-circle-notch','#F77737', 'Story'],
        ];
        foreach ($quickTypes as [$type, $icon, $color, $label]): ?>
        <a class="ig-quick-type" href="?page=instagram&action=create&type=<?= $type ?>">
            <i class="fas <?= $icon ?>" style="color:<?= $color ?>"></i>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Onglets -->
<div class="ig-tabs">
    <?php
    $tabs = [
        'all'       => ['fas fa-th',           'Tout',       $igStats['total']],
        'published' => ['fas fa-check',        'Publiées',   $igStats['published']],
        'scheduled' => ['fas fa-clock',        'Planifiées', $igStats['scheduled']],
        'draft'     => ['fas fa-pencil-alt',   'Brouillons', $igStats['draft']],
        'reels'     => ['fas fa-film',         'Reels',      $igStats['reels']],
        'stories'   => ['fas fa-circle-notch', 'Stories',    $igStats['stories']],
        'carousels' => ['fas fa-images',       'Carrousels', $igStats['carousels']],
    ];
    foreach ($tabs as $key => [$icon, $label, $count]): ?>
    <a href="?page=instagram&tab=<?= $key ?>" class="ig-tab <?= $activeTab === $key ? 'active' : '' ?>">
        <i class="<?= $icon ?>"></i> <?= $label ?>
        <span class="ig-tab-count"><?= $count ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Toolbar -->
<div class="ig-toolbar">
    <form class="ig-search" method="get">
        <input type="hidden" name="page" value="instagram">
        <input type="hidden" name="tab"  value="<?= htmlspecialchars($activeTab) ?>">
        <i class="fas fa-search"></i>
        <input type="text" name="search" placeholder="Rechercher une publication…"
               value="<?= htmlspecialchars($search) ?>">
    </form>
    <span style="font-size:12px;color:var(--text-3)">
        <?= $totalItems ?> publication<?= $totalItems > 1 ? 's' : '' ?>
    </span>
</div>

<!-- Publications -->
<?php if (empty($publications)): ?>
<div class="ig-empty">
    <i class="fab fa-instagram"></i>
    <h3>Aucune publication Instagram</h3>
    <p>Commencez par créer votre première publication.</p>
    <a href="?page=instagram&action=create" class="ig-hero-btn primary" style="display:inline-flex;background:#833AB4;color:#fff">
        <i class="fas fa-plus"></i> Créer ma première publication
    </a>
</div>
<?php else: ?>
<div class="ig-pub-list">
    <?php foreach ($publications as $pub):
        $metadata    = json_decode($pub['metadata'] ?? '{}', true) ?: [];
        $contentType = $metadata['content_type'] ?? 'post';
        $hashtags    = $metadata['hashtags'] ?? '';
        $thumbnail   = $metadata['thumbnail'] ?? ($pub['image_url'] ?? '');
        $statusCls   = $pub['status'] ?? 'draft';
        $title       = $pub['title'] ?? 'Sans titre';
        $excerpt     = mb_substr(strip_tags($pub['content'] ?? ''), 0, 120);
        $createdDate  = !empty($pub['created_at'])   ? date('d/m/Y', strtotime($pub['created_at']))      : '-';
        $scheduledDate= !empty($pub['scheduled_at']) ? date('d/m/Y H:i', strtotime($pub['scheduled_at'])): '';
        $publishedDate= !empty($pub['published_at']) ? date('d/m/Y H:i', strtotime($pub['published_at'])): '';
        $thumbIcon    = $typeIcons[$contentType] ?? 'fa-image';
    ?>
    <div class="ig-pub-card">
        <div class="ig-pub-thumb">
            <?php if ($thumbnail): ?>
                <img src="<?= htmlspecialchars($thumbnail) ?>" alt="" loading="lazy">
            <?php else: ?>
                <i class="fas <?= $thumbIcon ?>"></i>
            <?php endif; ?>
        </div>

        <div class="ig-pub-body">
            <div class="ig-pub-header">
                <span class="ig-pub-title"><?= htmlspecialchars($title) ?></span>
                <span class="ig-type-badge <?= htmlspecialchars($contentType) ?>">
                    <?= $typeLabels[$contentType] ?? '📸 Post' ?>
                </span>
                <span class="ig-status-badge <?= htmlspecialchars($statusCls) ?>">
                    <?= $statusLabels[$statusCls] ?? ucfirst($statusCls) ?>
                </span>
            </div>

            <?php if ($excerpt): ?>
            <div class="ig-pub-excerpt"><?= htmlspecialchars($excerpt) ?></div>
            <?php endif; ?>

            <div class="ig-pub-meta">
                <span><i class="fas fa-calendar"></i> <?= $createdDate ?></span>
                <?php if ($scheduledDate && $statusCls === 'scheduled'): ?>
                <span><i class="fas fa-clock"></i> Planifié : <?= $scheduledDate ?></span>
                <?php endif; ?>
                <?php if ($publishedDate && $statusCls === 'published'): ?>
                <span><i class="fas fa-check-circle"></i> Publié : <?= $publishedDate ?></span>
                <?php endif; ?>
                <?php if ($hashtags): ?>
                <span><i class="fas fa-hashtag"></i> <?= htmlspecialchars(mb_substr($hashtags, 0, 60)) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="ig-pub-actions">
            <a href="?page=instagram&action=edit&id=<?= (int)$pub['id'] ?>"
               class="ig-pub-action" title="Modifier">
                <i class="fas fa-pen"></i>
            </a>

            <?php if ($statusCls === 'draft'): ?>
            <form method="post" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action"     value="publish">
                <input type="hidden" name="post_id"    value="<?= (int)$pub['id'] ?>">
                <button type="submit" class="ig-pub-action success" title="Marquer comme publiée"
                        onclick="return confirm('Marquer comme publiée ?')">
                    <i class="fas fa-check"></i>
                </button>
            </form>
            <?php endif; ?>

            <form method="post" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action"     value="delete">
                <input type="hidden" name="post_id"    value="<?= (int)$pub['id'] ?>">
                <button type="submit" class="ig-pub-action danger" title="Supprimer"
                        onclick="return confirm('Supprimer cette publication ?')">
                    <i class="fas fa-trash"></i>
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="ig-pagination">
    <?php if ($currentPage > 1): ?>
    <a href="?page=instagram&tab=<?= $activeTab ?>&p=<?= $currentPage - 1 ?><?= $search ? '&search='.urlencode($search) : '' ?>">
        <i class="fas fa-chevron-left"></i>
    </a>
    <?php endif; ?>

    <?php
    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);
    for ($i = $start; $i <= $end; $i++):
        $url = "?page=instagram&tab={$activeTab}&p={$i}" . ($search ? '&search='.urlencode($search) : '');
    ?>
    <?php if ($i === $currentPage): ?>
        <span class="current"><?= $i ?></span>
    <?php else: ?>
        <a href="<?= $url ?>"><?= $i ?></a>
    <?php endif; ?>
    <?php endfor; ?>

    <?php if ($currentPage < $totalPages): ?>
    <a href="?page=instagram&tab=<?= $activeTab ?>&p=<?= $currentPage + 1 ?><?= $search ? '&search='.urlencode($search) : '' ?>">
        <i class="fas fa-chevron-right"></i>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Tips -->
<div class="ig-tips">
    <h3><i class="fas fa-lightbulb"></i> Bonnes pratiques Instagram Immobilier</h3>
    <ul>
        <?php
        $tips = [
            ['Reels',       'Visites virtuelles 30-60s, avant/après rénovation, coulisses — algorithme favorise les Reels'],
            ['Carrousels',  'Top 5 quartiers, checklist acheteur, comparatifs — très bon taux d\'enregistrement'],
            ['Stories',     'Sondages, questions/réponses, coulisses — crée du lien avec votre audience'],
            ['Posts',       'Nouveau bien, témoignage client, conseil expert — soigner la première ligne'],
            ['Hashtags',    'Mix local + général — 15 à 20 hashtags maximum par publication'],
            ['Fréquence',   '4-5 posts/semaine minimum, 3+ stories/jour pour rester visible'],
            ['Méthode MERE','Miroir → Émotion → Réassurance → Exclusivité dans chaque légende'],
            ['Heures',      'Publier entre 7h-9h ou 18h-21h pour maximiser l\'engagement'],
        ];
        foreach ($tips as [$bold, $text]): ?>
        <li><strong><?= $bold ?> :</strong> <?= $text ?></li>
        <?php endforeach; ?>
    </ul>
</div>