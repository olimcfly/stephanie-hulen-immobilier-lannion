<?php
// ======================================================
// Module LINKEDIN - Gestion des publications
// /admin/modules/social/linkedin/index.php
// Chargé via dashboard.php — pas de session/connexion ici
// ======================================================

if (!defined('ADMIN_ROUTER')) {
    die("Accès direct interdit.");
}

// ====================================================
// ACTIONS POST
// ====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Token CSRF invalide.";
        header("Location: ?page=linkedin");
        exit;
    }

    $postId = (int)($_POST['post_id'] ?? 0);

    switch ($_POST['action']) {
        case 'delete':
            try {
                $stmt = $pdo->prepare("DELETE FROM social_posts WHERE id = ? AND platform = 'linkedin'");
                $stmt->execute([$postId]);
                $_SESSION['success_message'] = "Publication supprimée.";
            } catch (Exception $e) {
                $_SESSION['error_message'] = "Erreur suppression : " . $e->getMessage();
            }
            break;

        case 'publish':
            try {
                $stmt = $pdo->prepare("UPDATE social_posts SET status = 'published', published_at = NOW() WHERE id = ? AND platform = 'linkedin'");
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
                    $stmt = $pdo->prepare("UPDATE social_posts SET status = 'scheduled', scheduled_at = ? WHERE id = ? AND platform = 'linkedin'");
                    $stmt->execute([$scheduledAt, $postId]);
                    $_SESSION['success_message'] = "Publication planifiée.";
                } catch (Exception $e) {
                    $_SESSION['error_message'] = "Erreur planification : " . $e->getMessage();
                }
            }
            break;
    }

    header("Location: ?page=linkedin");
    exit;
}

// ====================================================
// DONNÉES
// ====================================================

$allowedTabs = ['all','published','scheduled','draft','articles','documents','videos'];
$activeTab   = in_array($_GET['tab'] ?? 'all', $allowedTabs, true) ? ($_GET['tab'] ?? 'all') : 'all';

$liStats = [
    'total' => 0, 'published' => 0, 'scheduled' => 0, 'draft' => 0,
    'articles' => 0, 'posts' => 0, 'documents' => 0, 'videos' => 0,
];

try {
    $stmt = $pdo->query("SELECT status, COUNT(*) as total FROM social_posts WHERE platform = 'linkedin' GROUP BY status");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $liStats['total'] += (int)$row['total'];
        $st = strtolower($row['status']);
        if (isset($liStats[$st])) $liStats[$st] = (int)$row['total'];
    }
} catch (Exception $e) {}

try {
    $stmt = $pdo->query("
        SELECT COALESCE(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')), 'post') as content_type,
               COUNT(*) as total
        FROM social_posts WHERE platform = 'linkedin' GROUP BY content_type
    ");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $type = strtolower($row['content_type']);
        if ($type === 'article')                              $liStats['articles']  = (int)$row['total'];
        elseif (in_array($type, ['document','pdf','carousel'])) $liStats['documents'] = (int)$row['total'];
        elseif ($type === 'video')                            $liStats['videos']    = (int)$row['total'];
        else                                                  $liStats['posts']     = (int)$row['total'];
    }
} catch (Exception $e) {}

$liAccount = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM social_accounts WHERE platform = 'linkedin' AND is_active = 1 LIMIT 1");
    $stmt->execute();
    $liAccount = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// WHERE clause
$whereClause = "WHERE platform = 'linkedin'";
$params = [];

switch ($activeTab) {
    case 'published': $whereClause .= " AND status = 'published'"; break;
    case 'scheduled': $whereClause .= " AND status = 'scheduled'"; break;
    case 'draft':     $whereClause .= " AND status = 'draft'";     break;
    case 'articles':  $whereClause .= " AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')) = 'article'"; break;
    case 'documents': $whereClause .= " AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')) IN ('document','pdf','carousel')"; break;
    case 'videos':    $whereClause .= " AND JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.content_type')) = 'video'"; break;
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

$successMsg = $_SESSION['success_message'] ?? null;
$errorMsg   = $_SESSION['error_message']   ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Helpers
$typeIcons = [
    'post'     => 'fas fa-comment-dots',
    'article'  => 'fas fa-newspaper',
    'document' => 'fas fa-file-pdf',
    'pdf'      => 'fas fa-file-pdf',
    'carousel' => 'fas fa-file-pdf',
    'video'    => 'fas fa-video',
];
$typeLabels = [
    'post'     => '💬 Post',
    'article'  => '📰 Article',
    'document' => '📑 Document',
    'pdf'      => '📑 PDF',
    'carousel' => '📑 Carrousel',
    'video'    => '🎥 Vidéo',
];
$statusLabels = [
    'published' => '✅ Publiée',
    'scheduled' => '📅 Planifiée',
    'draft'     => '📝 Brouillon',
];
?>

<style>
/* ========== Module LinkedIn ========== */
.li-hero {
    background: linear-gradient(135deg, #0A66C2 0%, #004182 60%, #002D5A 100%);
    border-radius: var(--radius-lg); padding: 32px; color: #fff;
    margin-bottom: 24px; display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap; gap: 20px;
    position: relative; overflow: hidden;
}
.li-hero::before {
    content: ''; position: absolute; top: -40px; right: -40px;
    width: 200px; height: 200px; background: rgba(255,255,255,.04); border-radius: 50%;
}
.li-hero-left h1 {
    font-size: 1.7rem; font-weight: 800; margin: 0 0 6px;
    display: flex; align-items: center; gap: 12px; position: relative; z-index: 1;
}
.li-hero-left p { margin: 0; opacity: .88; font-size: 13px; position: relative; z-index: 1; }
.li-hero-right { display: flex; gap: 10px; position: relative; z-index: 1; }
.li-hero-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px; border-radius: var(--radius); font-weight: 600;
    font-size: 13px; text-decoration: none; transition: all 0.2s; border: none; cursor: pointer;
}
.li-hero-btn.primary { background: rgba(255,255,255,.95); color: #0A66C2; }
.li-hero-btn.primary:hover { background: #fff; transform: translateY(-2px); }
.li-hero-btn.secondary { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.3); }
.li-hero-btn.secondary:hover { background: rgba(255,255,255,.25); }

.li-stats-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 14px; margin-bottom: 24px;
}
.li-stat-card {
    background: var(--surface); border-radius: var(--radius-lg);
    padding: 18px; text-align: center; border: 1px solid var(--border);
    transition: all .2s; box-shadow: var(--shadow-sm);
}
.li-stat-card:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
.li-stat-icon  { font-size: 1.4rem; margin-bottom: 6px; }
.li-stat-value { font-size: 1.7rem; font-weight: 900; color: var(--text); line-height: 1; }
.li-stat-label { font-size: .72rem; color: var(--text-3); margin-top: 4px; text-transform: uppercase; letter-spacing: .5px; }

.li-account-bar {
    background: var(--surface); border-radius: var(--radius-lg);
    padding: 16px 22px; margin-bottom: 22px;
    display: flex; align-items: center; justify-content: space-between;
    border: 1px solid var(--border); flex-wrap: wrap; gap: 12px; box-shadow: var(--shadow-sm);
}
.li-account-info { display: flex; align-items: center; gap: 12px; }
.li-account-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    background: linear-gradient(135deg, #0A66C2, #004182);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 800; font-size: 1.1rem; flex-shrink: 0;
}
.li-account-name   { font-weight: 700; color: var(--text); font-size: 14px; }
.li-account-handle { font-size: 12px; color: var(--text-3); }

.li-connect-box {
    background: var(--surface); border-radius: var(--radius-lg);
    padding: 48px; text-align: center;
    border: 2px dashed var(--border); margin-bottom: 22px;
}
.li-connect-box i.main-icon { font-size: 3.5rem; color: #0A66C2; margin-bottom: 16px; display: block; }
.li-connect-box h3 { font-size: 1.2rem; color: var(--text); margin: 0 0 8px; }
.li-connect-box p  { color: var(--text-3); margin: 0 auto 20px; max-width: 520px; font-size: 13px; line-height: 1.5; }
.li-connect-btn {
    display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px;
    background: #0A66C2; color: #fff; border: none; border-radius: var(--radius);
    font-weight: 700; font-size: 14px; cursor: pointer; transition: all .2s;
}
.li-connect-btn:hover { background: #004182; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(10,102,194,.35); }

.li-quick-create {
    background: var(--surface); border-radius: var(--radius-lg);
    padding: 22px; margin-bottom: 22px; border: 1px solid var(--border); box-shadow: var(--shadow-sm);
}
.li-quick-create h3 { font-size: 13px; font-weight: 700; color: var(--text); margin: 0 0 14px; }
.li-quick-types { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; }
.li-quick-type {
    display: flex; flex-direction: column; align-items: center; gap: 7px;
    padding: 18px 14px; border-radius: var(--radius-lg); border: 2px solid var(--border);
    text-decoration: none; color: var(--text-2); transition: all .2s; background: var(--surface-2);
}
.li-quick-type:hover { border-color: #0A66C2; background: #f0f7ff; color: #0A66C2; transform: translateY(-2px); }
.li-quick-type i { font-size: 1.4rem; }
.li-quick-type span { font-weight: 600; font-size: 13px; }
.li-quick-type small { font-size: 11px; color: var(--text-3); text-align: center; line-height: 1.3; }
.li-quick-type:hover small { color: #0A66C2; }

.li-tabs {
    display: flex; gap: 3px; background: var(--surface-2);
    padding: 5px; border-radius: var(--radius-lg); margin-bottom: 18px;
    border: 1px solid var(--border); overflow-x: auto; flex-wrap: wrap;
}
.li-tab {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 9px 15px; border-radius: var(--radius); font-size: 12.5px;
    font-weight: 600; color: var(--text-2); text-decoration: none; transition: all .15s; white-space: nowrap;
}
.li-tab:hover { background: var(--surface); color: var(--text); }
.li-tab.active { background: var(--surface); color: #0A66C2; box-shadow: var(--shadow-sm); }
.li-tab .li-count {
    background: var(--surface-3); color: var(--text-3);
    padding: 2px 7px; border-radius: 10px; font-size: 10px; font-weight: 700;
}
.li-tab.active .li-count { background: rgba(10,102,194,.1); color: #0A66C2; }

.li-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    gap: 12px; margin-bottom: 18px; flex-wrap: wrap;
}
.li-search {
    display: flex; align-items: center; gap: 8px;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 8px 14px; flex: 1; max-width: 380px; transition: border-color .15s;
}
.li-search:focus-within { border-color: #0A66C2; }
.li-search input { border: none; outline: none; font-size: 13px; width: 100%; background: transparent; color: var(--text); font-family: var(--font); }
.li-search input::placeholder { color: var(--text-3); }
.li-search i { color: var(--text-3); font-size: 12px; flex-shrink: 0; }

.li-pub-list { display: flex; flex-direction: column; gap: 10px; }
.li-pub-card {
    background: var(--surface); border-radius: var(--radius-lg);
    border: 1px solid var(--border); padding: 18px;
    display: flex; align-items: flex-start; gap: 14px; transition: all .2s; box-shadow: var(--shadow-sm);
}
.li-pub-card:hover { box-shadow: var(--shadow); border-color: #ccc; }

.li-pub-icon-col {
    width: 50px; height: 50px; border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; font-size: 1.2rem;
}
.li-pub-icon-col.type-post     { background: #f0f7ff; color: #0A66C2; }
.li-pub-icon-col.type-article  { background: var(--amber-bg); color: #e67e22; }
.li-pub-icon-col.type-document { background: var(--accent-bg); color: var(--accent); }
.li-pub-icon-col.type-video    { background: #f5f0ff; color: #7c3aed; }

.li-pub-body { flex: 1; min-width: 0; }
.li-pub-header { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; flex-wrap: wrap; }
.li-pub-title  { font-weight: 700; color: var(--text); font-size: 13.5px; }
.li-pub-excerpt { color: var(--text-2); font-size: 12.5px; line-height: 1.5; margin-bottom: 7px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.li-pub-meta { display: flex; align-items: center; gap: 12px; font-size: 11.5px; color: var(--text-3); flex-wrap: wrap; }

.li-pub-actions { display: flex; gap: 5px; align-items: flex-start; flex-shrink: 0; }
.li-pub-action {
    width: 32px; height: 32px; border-radius: var(--radius);
    border: 1px solid var(--border); background: var(--surface-2);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-3); cursor: pointer; transition: all .15s; font-size: 12px; text-decoration: none;
}
.li-pub-action:hover        { background: var(--surface-3); color: var(--text); }
.li-pub-action.danger:hover { background: var(--red-bg); color: var(--red); border-color: #fca5a5; }
.li-pub-action.success:hover{ background: var(--green-bg); color: var(--green); border-color: #a7f3d0; }

.li-type-badge {
    display: inline-flex; align-items: center; gap: 3px;
    padding: 2px 9px; border-radius: 6px; font-size: 11px; font-weight: 700;
}
.li-type-badge.article  { background: var(--amber-bg); color: #e67e22; }
.li-type-badge.document { background: var(--accent-bg); color: var(--accent); }
.li-type-badge.video    { background: #f5f0ff; color: #7c3aed; }
.li-type-badge.post     { background: var(--surface-3); color: var(--text-2); }

.li-status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 6px; font-size: 11px; font-weight: 700; }
.li-status-badge.published { background: var(--green-bg);  color: var(--green); }
.li-status-badge.scheduled { background: var(--accent-bg); color: var(--accent); }
.li-status-badge.draft     { background: var(--surface-3); color: var(--text-3); }

.li-empty { text-align: center; padding: 60px 20px; color: var(--text-3); }
.li-empty i { font-size: 3rem; margin-bottom: 14px; display: block; color: #cddaeb; }
.li-empty h3 { color: var(--text-2); margin: 0 0 8px; font-size: 15px; }

.li-pagination { display: flex; justify-content: center; gap: 5px; margin-top: 22px; }
.li-pagination a, .li-pagination span {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 34px; height: 34px; padding: 0 9px; border-radius: var(--radius);
    font-size: 12.5px; font-weight: 600; text-decoration: none; transition: all .15s;
}
.li-pagination a { background: var(--surface); border: 1px solid var(--border); color: var(--text-2); }
.li-pagination a:hover { border-color: #0A66C2; color: #0A66C2; }
.li-pagination span.current { background: #0A66C2; color: #fff; border: 1px solid #0A66C2; }

.li-flash { padding: 12px 18px; border-radius: var(--radius); margin-bottom: 18px; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 10px; }
.li-flash.success { background: var(--green-bg); color: var(--green); border: 1px solid #a7f3d0; }
.li-flash.error   { background: var(--red-bg);   color: var(--red);   border: 1px solid #fca5a5; }

.li-strategy {
    display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 22px;
}
.li-strategy-card {
    background: var(--surface); border-radius: var(--radius-lg);
    padding: 22px; border: 1px solid var(--border); box-shadow: var(--shadow-sm);
}
.li-strategy-card h3 { font-size: 13px; font-weight: 700; color: #0A66C2; margin: 0 0 12px; display: flex; align-items: center; gap: 8px; }
.li-strategy-card ul { list-style: none; padding: 0; margin: 0; }
.li-strategy-card li { padding: 7px 11px; background: var(--surface-2); border-radius: var(--radius); font-size: 12.5px; color: var(--text-2); line-height: 1.5; margin-bottom: 5px; }
.li-strategy-card li strong { color: #0A66C2; }

.li-audience-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-top: 10px; }
.li-audience-card { background: var(--surface-2); border-radius: var(--radius); padding: 14px; text-align: center; border: 1px solid var(--border); }
.li-audience-card .emoji { font-size: 1.6rem; margin-bottom: 5px; display: block; }
.li-audience-card h4 { font-size: 12.5px; color: var(--text); margin: 0 0 3px; }
.li-audience-card p  { font-size: 11px; color: var(--text-3); margin: 0; line-height: 1.4; }

@media (max-width: 768px) {
    .li-hero { padding: 22px; flex-direction: column; text-align: center; }
    .li-hero-right { width: 100%; justify-content: center; }
    .li-pub-card { flex-direction: column; }
    .li-pub-icon-col { width: 100%; height: 44px; border-radius: var(--radius); }
    .li-pub-actions { width: 100%; justify-content: flex-end; }
    .li-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .li-strategy { grid-template-columns: 1fr; }
}
</style>

<!-- Flash messages -->
<?php if ($successMsg): ?>
<div class="li-flash success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="li-flash error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<!-- Hero -->
<div class="li-hero">
    <div class="li-hero-left">
        <h1><i class="fab fa-linkedin"></i> LinkedIn</h1>
        <p>Développez votre réseau professionnel et votre crédibilité d'expert immobilier</p>
    </div>
    <div class="li-hero-right">
        <a href="?page=linkedin&action=create" class="li-hero-btn primary">
            <i class="fas fa-plus"></i> Nouvelle publication
        </a>
        <a href="?page=reseaux-sociaux" class="li-hero-btn secondary">
            <i class="fas fa-th-large"></i> Hub Réseaux
        </a>
    </div>
</div>

<!-- Compte -->
<?php if ($liAccount): ?>
<div class="li-account-bar">
    <div class="li-account-info">
        <div class="li-account-avatar">
            <?= strtoupper(substr($liAccount['account_name'] ?? 'LI', 0, 1)) ?>
        </div>
        <div>
            <div class="li-account-name"><?= htmlspecialchars($liAccount['account_name'] ?? 'LinkedIn') ?></div>
            <div class="li-account-handle"><?= htmlspecialchars($liAccount['username'] ?? '') ?></div>
        </div>
    </div>
    <span class="badge badge-green"><i class="fas fa-check-circle"></i> Connecté</span>
</div>
<?php else: ?>
<div class="li-connect-box">
    <i class="fab fa-linkedin main-icon"></i>
    <h3>Connectez votre profil LinkedIn</h3>
    <p>Liez votre profil pour publier directement, suivre vos statistiques et développer votre réseau. En attendant, créez vos publications ici et copiez-les sur LinkedIn.</p>
    <button class="li-connect-btn" onclick="alert('Connexion LinkedIn API — disponible prochainement.\n\nCréez et planifiez vos publications ici, puis copiez-les sur LinkedIn.')">
        <i class="fab fa-linkedin"></i> Connecter LinkedIn
    </button>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="li-stats-grid">
    <?php
    $statItems = [
        ['📊','total',     'Total'],
        ['✅','published', 'Publiées'],
        ['📅','scheduled', 'Planifiées'],
        ['📝','draft',     'Brouillons'],
        ['📰','articles',  'Articles'],
        ['💬','posts',     'Posts'],
        ['📑','documents', 'Documents'],
        ['🎥','videos',    'Vidéos'],
    ];
    foreach ($statItems as [$icon, $key, $label]): ?>
    <div class="li-stat-card">
        <div class="li-stat-icon"><?= $icon ?></div>
        <div class="li-stat-value"><?= $liStats[$key] ?></div>
        <div class="li-stat-label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Création rapide -->
<div class="li-quick-create">
    <h3><i class="fas fa-bolt" style="color:#0A66C2"></i> Création rapide</h3>
    <div class="li-quick-types">
        <?php
        $quickTypes = [
            ['post',     'fas fa-comment-dots', '#0A66C2', 'Post texte',    'Partage d\'expertise, avis, conseil'],
            ['article',  'fas fa-newspaper',    '#e67e22', 'Article',       'Analyse marché, guide complet'],
            ['document', 'fas fa-file-pdf',     '#0A66C2', 'Document PDF',  'Carrousel LinkedIn, infographie'],
            ['video',    'fas fa-video',        '#7c3aed', 'Vidéo',         'Témoignage, visite, coulisses'],
        ];
        foreach ($quickTypes as [$type, $icon, $color, $label, $sub]): ?>
        <a class="li-quick-type" href="?page=linkedin&action=create&type=<?= $type ?>">
            <i class="<?= $icon ?>" style="color:<?= $color ?>"></i>
            <span><?= $label ?></span>
            <small><?= $sub ?></small>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Onglets -->
<div class="li-tabs">
    <?php
    $tabs = [
        'all'       => ['fas fa-th',          'Tout',       $liStats['total']],
        'published' => ['fas fa-check',       'Publiées',   $liStats['published']],
        'scheduled' => ['fas fa-clock',       'Planifiées', $liStats['scheduled']],
        'draft'     => ['fas fa-pencil-alt',  'Brouillons', $liStats['draft']],
        'articles'  => ['fas fa-newspaper',   'Articles',   $liStats['articles']],
        'documents' => ['fas fa-file-pdf',    'Documents',  $liStats['documents']],
        'videos'    => ['fas fa-video',       'Vidéos',     $liStats['videos']],
    ];
    foreach ($tabs as $key => [$icon, $label, $count]): ?>
    <a href="?page=linkedin&tab=<?= $key ?>" class="li-tab <?= $activeTab === $key ? 'active' : '' ?>">
        <i class="<?= $icon ?>"></i> <?= $label ?>
        <span class="li-count"><?= $count ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Toolbar -->
<div class="li-toolbar">
    <form class="li-search" method="get">
        <input type="hidden" name="page" value="linkedin">
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
<div class="li-empty">
    <i class="fab fa-linkedin"></i>
    <h3>Aucune publication LinkedIn</h3>
    <p>Commencez par créer votre première publication.</p>
    <a href="?page=linkedin&action=create" class="li-hero-btn primary" style="display:inline-flex;background:#0A66C2;color:#fff">
        <i class="fas fa-plus"></i> Créer ma première publication
    </a>
</div>
<?php else: ?>
<div class="li-pub-list">
    <?php foreach ($publications as $pub):
        $metadata    = json_decode($pub['metadata'] ?? '{}', true) ?: [];
        $contentType = $metadata['content_type'] ?? 'post';
        $typeIcon    = $typeIcons[$contentType] ?? 'fas fa-comment-dots';
        $typeLabel   = $typeLabels[$contentType] ?? '💬 Post';

        $typeClass = 'post';
        if (in_array($contentType, ['document','pdf','carousel'])) $typeClass = 'document';
        elseif ($contentType === 'article') $typeClass = 'article';
        elseif ($contentType === 'video')   $typeClass = 'video';

        $statusCls    = $pub['status'] ?? 'draft';
        $title        = $pub['title'] ?? 'Sans titre';
        $excerpt      = mb_substr(strip_tags($pub['content'] ?? ''), 0, 140);
        $createdDate  = !empty($pub['created_at'])   ? date('d/m/Y', strtotime($pub['created_at']))      : '-';
        $scheduledDate= !empty($pub['scheduled_at']) ? date('d/m/Y H:i', strtotime($pub['scheduled_at'])): '';
        $publishedDate= !empty($pub['published_at']) ? date('d/m/Y H:i', strtotime($pub['published_at'])): '';
    ?>
    <div class="li-pub-card">
        <div class="li-pub-icon-col type-<?= $typeClass ?>">
            <i class="<?= $typeIcon ?>"></i>
        </div>

        <div class="li-pub-body">
            <div class="li-pub-header">
                <span class="li-pub-title"><?= htmlspecialchars($title) ?></span>
                <span class="li-type-badge <?= $typeClass ?>"><?= $typeLabel ?></span>
                <span class="li-status-badge <?= htmlspecialchars($statusCls) ?>">
                    <?= $statusLabels[$statusCls] ?? ucfirst($statusCls) ?>
                </span>
            </div>

            <?php if ($excerpt): ?>
            <div class="li-pub-excerpt"><?= htmlspecialchars($excerpt) ?></div>
            <?php endif; ?>

            <div class="li-pub-meta">
                <span><i class="fas fa-calendar"></i> <?= $createdDate ?></span>
                <?php if ($scheduledDate && $statusCls === 'scheduled'): ?>
                <span><i class="fas fa-clock"></i> Planifié : <?= $scheduledDate ?></span>
                <?php endif; ?>
                <?php if ($publishedDate && $statusCls === 'published'): ?>
                <span><i class="fas fa-check-circle"></i> Publié : <?= $publishedDate ?></span>
                <?php endif; ?>
                <?php if (!empty($metadata['audience'])): ?>
                <span><i class="fas fa-users"></i> <?= htmlspecialchars($metadata['audience']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="li-pub-actions">
            <a href="?page=linkedin&action=edit&id=<?= (int)$pub['id'] ?>"
               class="li-pub-action" title="Modifier">
                <i class="fas fa-pen"></i>
            </a>

            <?php if ($statusCls === 'draft'): ?>
            <form method="post" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action"     value="publish">
                <input type="hidden" name="post_id"    value="<?= (int)$pub['id'] ?>">
                <button type="submit" class="li-pub-action success" title="Marquer comme publiée"
                        onclick="return confirm('Marquer comme publiée ?')">
                    <i class="fas fa-check"></i>
                </button>
            </form>
            <?php endif; ?>

            <form method="post" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action"     value="delete">
                <input type="hidden" name="post_id"    value="<?= (int)$pub['id'] ?>">
                <button type="submit" class="li-pub-action danger" title="Supprimer"
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
<div class="li-pagination">
    <?php if ($currentPage > 1): ?>
    <a href="?page=linkedin&tab=<?= $activeTab ?>&p=<?= $currentPage - 1 ?><?= $search ? '&search='.urlencode($search) : '' ?>">
        <i class="fas fa-chevron-left"></i>
    </a>
    <?php endif; ?>

    <?php
    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);
    for ($i = $start; $i <= $end; $i++):
        $url = "?page=linkedin&tab={$activeTab}&p={$i}" . ($search ? '&search='.urlencode($search) : '');
    ?>
    <?php if ($i === $currentPage): ?>
        <span class="current"><?= $i ?></span>
    <?php else: ?>
        <a href="<?= $url ?>"><?= $i ?></a>
    <?php endif; ?>
    <?php endfor; ?>

    <?php if ($currentPage < $totalPages): ?>
    <a href="?page=linkedin&tab=<?= $activeTab ?>&p=<?= $currentPage + 1 ?><?= $search ? '&search='.urlencode($search) : '' ?>">
        <i class="fas fa-chevron-right"></i>
    </a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Stratégie -->
<div class="li-strategy">
    <div class="li-strategy-card">
        <h3><i class="fas fa-lightbulb"></i> Bonnes pratiques LinkedIn</h3>
        <ul>
            <?php
            $tips = [
                ['Hook',       'Les 3 premières lignes sont visibles avant "Voir plus" — accrochez immédiatement'],
                ['Posts texte','Les posts sans lien obtiennent 2× plus de portée — mettez le lien en commentaire'],
                ['Documents',  'Les carrousels PDF ont le meilleur taux d\'engagement — format roi'],
                ['Articles',   'Positionnement d\'expert long format — analyse marché, retour d\'expérience'],
                ['Fréquence',  '3-5 posts/semaine, publiez entre 7h30-8h30 ou 17h-18h en semaine'],
                ['Engagement', 'Répondez à TOUS les commentaires dans l\'heure — l\'algorithme récompense'],
                ['Méthode MERE','Miroir → Émotion → Réassurance → Exclusivité dans chaque publication'],
            ];
            foreach ($tips as [$bold, $text]): ?>
            <li><strong><?= $bold ?> :</strong> <?= $text ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="li-strategy-card">
        <h3><i class="fas fa-bullseye"></i> Audiences cibles</h3>
        <div class="li-audience-grid">
            <?php
            $audiences = [
                ['🏢','Cadres & dirigeants', 'Mutation pro, investissement locatif, résidence principale premium'],
                ['💼','Entrepreneurs',       'Locaux commerciaux, investissement patrimonial, défiscalisation'],
                ['🏗️','Pros de l\'immo',    'Partenariats, co-mandats, networking local, partage d\'expertise'],
                ['🎓','Jeunes actifs',       'Premier achat, PTZ, quartiers dynamiques'],
            ];
            foreach ($audiences as [$emoji, $title, $desc]): ?>
            <div class="li-audience-card">
                <span class="emoji"><?= $emoji ?></span>
                <h4><?= $title ?></h4>
                <p><?= $desc ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>