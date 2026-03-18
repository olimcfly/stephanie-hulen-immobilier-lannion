<?php
/**
 * Module Blog — /admin/modules/blog/index.php
 */

if (!isset($pdo) && !isset($db)) {
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (PDOException $e) {
        echo '<div class="mod-flash mod-flash-error"><i class="fas fa-exclamation-circle"></i> '.$e->getMessage().'</div>';
        return;
    }
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db = $pdo;

// --- Config dynamique (multi-client) ---
$siteConfig = ['site_name' => 'Mon Site', 'advisor_name' => 'Conseiller', 'city' => 'Bordeaux'];
try {
    $rows = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE category='site'")->fetchAll();
    foreach ($rows as $r) $siteConfig[$r['setting_key']] = $r['setting_value'];
} catch(Exception $e) {}

$pdo->exec("CREATE TABLE IF NOT EXISTS blog_articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) DEFAULT NULL,
    excerpt TEXT DEFAULT NULL,
    content LONGTEXT DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    tags VARCHAR(500) DEFAULT NULL,
    image VARCHAR(500) DEFAULT NULL,
    author VARCHAR(100) DEFAULT NULL,
    main_keyword VARCHAR(100) DEFAULT NULL,
    seo_title VARCHAR(160) DEFAULT NULL,
    seo_description VARCHAR(320) DEFAULT NULL,
    seo_score INT DEFAULT 0,
    is_indexed TINYINT(1) DEFAULT 0,
    views INT DEFAULT 0,
    status ENUM('published','draft','archived') DEFAULT 'draft',
    published_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_slug (slug),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_published (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$flash = ''; $flashType = 'success';
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $tk = $_POST['csrf_token'] ?? '';
    if (!isset($_SESSION['csrf_token']) || $tk !== $_SESSION['csrf_token']) {
        $flash = 'Erreur CSRF.'; $flashType = 'error';
    } else {
        try {
            switch ($_POST['action']) {
                case 'delete':
                    $id = (int)($_POST['id'] ?? 0);
                    $r = $pdo->prepare("SELECT title FROM blog_articles WHERE id=?"); $r->execute([$id]); $t = $r->fetchColumn();
                    if (!$t) throw new Exception('Article introuvable.');
                    $pdo->prepare("DELETE FROM blog_articles WHERE id=?")->execute([$id]);
                    $flash = "Article « {$t} » supprimé.";
                    break;
                case 'toggle_status':
                    $id = (int)($_POST['id'] ?? 0);
                    $r = $pdo->prepare("SELECT status, title FROM blog_articles WHERE id=?"); $r->execute([$id]); $row = $r->fetch();
                    if (!$row) throw new Exception('Article introuvable.');
                    $ns = $row['status'] === 'published' ? 'draft' : 'published';
                    $upd = $pdo->prepare("UPDATE blog_articles SET status=?, published_at=IF(?='published' AND published_at IS NULL, NOW(), published_at) WHERE id=?");
                    $upd->execute([$ns, $ns, $id]);
                    $flash = "« {$row['title']} » → " . ($ns === 'published' ? 'Publié' : 'Brouillon');
                    break;
            }
        } catch (Exception $e) { $flash = $e->getMessage(); $flashType = 'error'; }
    }
}

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$catFilter = $_GET['cat'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page = max(1, (int)($_GET['pg'] ?? 1));
$perPage = 20;

$counts = ['all'=>0,'published'=>0,'draft'=>0,'archived'=>0];
try {
    $counts['all'] = (int)$pdo->query("SELECT COUNT(*) FROM blog_articles")->fetchColumn();
    foreach (['published','draft','archived'] as $s)
        $counts[$s] = (int)$pdo->query("SELECT COUNT(*) FROM blog_articles WHERE status='{$s}'")->fetchColumn();
    $totalViews = (int)$pdo->query("SELECT COALESCE(SUM(views),0) FROM blog_articles")->fetchColumn();
} catch(Exception $e) { $totalViews = 0; }

$where = "WHERE 1=1"; $params = [];
if ($filter !== 'all' && isset($counts[$filter])) { $where .= " AND status=?"; $params[] = $filter; }
if ($search) { $where .= " AND (title LIKE ? OR slug LIKE ? OR category LIKE ? OR main_keyword LIKE ?)"; $params = array_merge($params, array_fill(0,4,"%{$search}%")); }
if ($catFilter) { $where .= " AND category=?"; $params[] = $catFilter; }

$totalFiltered = 0;
try { $cs = $pdo->prepare("SELECT COUNT(*) FROM blog_articles {$where}"); $cs->execute($params); $totalFiltered = (int)$cs->fetchColumn(); } catch(Exception $e) {}
$totalPages = max(1, ceil($totalFiltered / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$allowedSort = ['title','category','status','views','seo_score','published_at','created_at'];
if (!in_array($sort, $allowedSort)) $sort = 'created_at';

$articles = [];
try {
    $st = $pdo->prepare("SELECT * FROM blog_articles {$where} ORDER BY {$sort} {$order} LIMIT {$perPage} OFFSET {$offset}");
    $st->execute($params); $articles = $st->fetchAll();
} catch(Exception $e) {}

$categories = [];
try { $categories = $pdo->query("SELECT DISTINCT category FROM blog_articles WHERE category IS NOT NULL AND category != '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN); } catch(Exception $e) {}

function blogSortUrl($col) { global $sort, $order; $nO = ($sort===$col && $order==='DESC') ? 'ASC' : 'DESC'; $p=$_GET; $p['sort']=$col; $p['order']=$nO; unset($p['pg']); return '?'.http_build_query($p); }
function blogSortIcon($col) { global $sort, $order; if ($sort!==$col) return '<i class="fas fa-sort" style="opacity:.3"></i>'; return $order==='ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; }
?>

<div class="mod-hero">
    <div class="mod-hero-content">
        <h1><i class="fas fa-pen-fancy"></i> Blog</h1>
        <p>Articles, guides et contenus SEO — <?= htmlspecialchars($siteConfig['site_name']) ?></p>
    </div>
    <div class="mod-stats">
        <div class="mod-stat"><div class="mod-stat-value"><?= $counts['all'] ?></div><div class="mod-stat-label">Articles</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= $counts['published'] ?></div><div class="mod-stat-label">Publiés</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= number_format($totalViews) ?></div><div class="mod-stat-label">Vues totales</div></div>
    </div>
</div>

<?php if ($flash): ?>
<div class="mod-flash mod-flash-<?= $flashType ?>"><i class="fas fa-<?= $flashType==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= htmlspecialchars($flash) ?></div>
<?php endif; ?>

<div class="mod-toolbar">
    <div class="mod-toolbar-left">
        <div class="mod-filters">
            <a href="?page=blog&filter=all" class="mod-filter <?= $filter==='all'?'active':'' ?>"><i class="fas fa-layer-group"></i> Tous <span class="mod-badge mod-badge-inactive"><?= $counts['all'] ?></span></a>
            <a href="?page=blog&filter=published" class="mod-filter <?= $filter==='published'?'active':'' ?>"><i class="fas fa-check-circle"></i> Publiés <span class="mod-badge mod-badge-inactive"><?= $counts['published'] ?></span></a>
            <a href="?page=blog&filter=draft" class="mod-filter <?= $filter==='draft'?'active':'' ?>"><i class="fas fa-pencil-alt"></i> Brouillons <span class="mod-badge mod-badge-inactive"><?= $counts['draft'] ?></span></a>
            <a href="?page=blog&filter=archived" class="mod-filter <?= $filter==='archived'?'active':'' ?>"><i class="fas fa-archive"></i> Archivés <span class="mod-badge mod-badge-inactive"><?= $counts['archived'] ?></span></a>
        </div>
    </div>
    <div class="mod-toolbar-right">
        <form class="mod-search" method="GET">
            <input type="hidden" name="page" value="blog">
            <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
            <i class="fas fa-search"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Titre, mot-clé...">
        </form>
        <?php if (!empty($categories)): ?>
        <select onchange="location.href='?page=blog&filter=<?= $filter ?>&cat='+this.value" style="padding:7px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:.78rem;font-family:var(--font);background:var(--surface)">
            <option value="">Toutes catégories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $catFilter===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <a href="?page=blog&action=create" class="mod-btn mod-btn-primary"><i class="fas fa-plus"></i> Nouvel article</a>
    </div>
</div>

<?php if (empty($articles)): ?>
<div class="mod-empty"><i class="fas fa-pen-fancy"></i><h3>Aucun article trouvé</h3><p><?= $search ? "Aucun résultat pour « {$search} »." : 'Rédigez votre premier article de blog.' ?></p><a href="?page=blog&action=create" class="mod-btn mod-btn-primary mod-mt"><i class="fas fa-plus"></i> Écrire un article</a></div>
<?php else: ?>

<div class="mod-table-wrap">
    <table class="mod-table">
        <thead>
            <tr>
                <th><a href="<?= blogSortUrl('title') ?>">Article <?= blogSortIcon('title') ?></a></th>
                <th><a href="<?= blogSortUrl('category') ?>">Catégorie <?= blogSortIcon('category') ?></a></th>
                <th><a href="<?= blogSortUrl('status') ?>">Statut <?= blogSortIcon('status') ?></a></th>
                <th><a href="<?= blogSortUrl('seo_score') ?>">SEO <?= blogSortIcon('seo_score') ?></a></th>
                <th><a href="<?= blogSortUrl('views') ?>">Vues <?= blogSortIcon('views') ?></a></th>
                <th><a href="<?= blogSortUrl('published_at') ?>">Date <?= blogSortIcon('published_at') ?></a></th>
                <th class="col-actions">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($articles as $a):
            $seo = (int)($a['seo_score'] ?? 0);
            $seoClass = $seo >= 70 ? 'active' : ($seo >= 40 ? 'warning' : ($seo > 0 ? 'error' : 'inactive'));
            $kw = $a['main_keyword'] ?? '';
            $pubDate = $a['published_at'] ? date('d/m/Y', strtotime($a['published_at'])) : ($a['created_at'] ? date('d/m/Y', strtotime($a['created_at'])) : '—');
        ?>
            <tr>
                <td>
                    <strong style="font-weight:600;color:var(--text)"><?= htmlspecialchars($a['title'] ?? 'Sans titre') ?></strong>
                    <div class="mod-text-xs mod-text-muted">/blog/<?= htmlspecialchars($a['slug'] ?? '') ?><?php if ($kw): ?> — <span class="mod-tag" style="font-size:.6rem"><?= htmlspecialchars($kw) ?></span><?php endif; ?></div>
                </td>
                <td><?php if ($a['category'] ?? ''): ?><span class="mod-tag"><?= htmlspecialchars($a['category']) ?></span><?php else: ?>—<?php endif; ?></td>
                <td><span class="mod-badge mod-badge-<?= ($a['status'] ?? 'draft')==='published'?'active':(($a['status'] ?? '')==='archived'?'inactive':'draft') ?>"><?= ($a['status'] ?? 'draft')==='published'?'Publié':(($a['status'] ?? '')==='archived'?'Archivé':'Brouillon') ?></span></td>
                <td><span class="mod-badge mod-badge-<?= $seoClass ?>"><?= $seo ?>%</span></td>
                <td><span class="mod-text-sm"><?= number_format($a['views'] ?? 0) ?></span></td>
                <td><span class="mod-date"><?= $pubDate ?></span></td>
                <td class="col-actions">
                    <div class="mod-actions">
                        <a href="?page=blog&action=edit&id=<?= $a['id'] ?>" class="mod-btn-icon" title="Modifier"><i class="fas fa-edit"></i></a>
                        <?php if ($a['slug'] ?? ''): ?>
                        <a href="/blog/<?= htmlspecialchars($a['slug']) ?>" target="_blank" class="mod-btn-icon" title="Voir"><i class="fas fa-external-link-alt"></i></a>
                        <?php endif; ?>
                        <form method="POST" class="mod-inline-form">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button type="submit" class="mod-btn-icon success" title="Toggle"><i class="fas fa-<?= ($a['status'] ?? 'draft')==='published'?'toggle-on':'toggle-off' ?>"></i></button>
                        </form>
                        <form method="POST" class="mod-inline-form" onsubmit="return confirm('Supprimer « <?= htmlspecialchars(addslashes($a['title'] ?? '')) ?> » ?')">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $a['id'] ?>">
                            <button type="submit" class="mod-btn-icon danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="mod-flex mod-items-center" style="justify-content:space-between;margin-top:16px">
    <span class="mod-text-xs mod-text-muted"><?= $totalFiltered ?> article(s) — page <?= $page ?>/<?= $totalPages ?></span>
    <div class="mod-pagination">
        <?php if ($page > 1): $p=$_GET; $p['pg']=$page-1; ?>
        <a href="?<?= http_build_query($p) ?>" class="mod-page-btn"><i class="fas fa-chevron-left"></i></a>
        <?php endif; ?>
        <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): $p=$_GET; $p['pg']=$i; ?>
        <a href="?<?= http_build_query($p) ?>" class="mod-page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): $p=$_GET; $p['pg']=$page+1; ?>
        <a href="?<?= http_build_query($p) ?>" class="mod-page-btn"><i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>