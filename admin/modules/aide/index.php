<?php
/**
 * /admin/modules/aide/index.php — Centre d'aide v1.0
 * ════════════════════════════════════════════════════════════
 * Articles d'aide stockés en BDD (help_articles)
 * 4 catégories : premiers-pas, mon-site, mes-leads, ma-visibilite
 * Vue liste par catégorie + vue article
 * ════════════════════════════════════════════════════════════
 */

if (!isset($pdo)) {
    require_once dirname(__DIR__, 3) . '/includes/init.php';
}

// ── Routing : article spécifique ──
$articleSlug = $_GET['article'] ?? '';
$filterCat   = $_GET['cat'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');

// ── Catégories ──
$categories = [
    'premiers-pas'   => ['label' => 'Premiers pas',   'icon' => 'fa-rocket',          'color' => '#c9913b', 'desc' => 'Démarrer avec la plateforme'],
    'mon-site'        => ['label' => 'Mon site',        'icon' => 'fa-globe',           'color' => '#6366f1', 'desc' => 'Pages, templates, contenu'],
    'mes-leads'       => ['label' => 'Mes leads',       'icon' => 'fa-user-plus',       'color' => '#dc2626', 'desc' => 'Estimations, messagerie, CRM'],
    'ma-visibilite'   => ['label' => 'Ma visibilité',   'icon' => 'fa-eye',             'color' => '#65a30d', 'desc' => 'SEO, publicité, réseaux sociaux'],
];

// ── Vue article unique ──
if ($articleSlug) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM help_articles WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$articleSlug]);
        $article = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $article = null; }

    if (!$article) {
        echo '<div style="text-align:center;padding:60px 20px;color:#9ca3af">
            <i class="fas fa-circle-question" style="font-size:2rem;opacity:.3;margin-bottom:12px;display:block"></i>
            <h3 style="color:#6b7280;font-size:1rem">Article non trouvé</h3>
            <p><a href="?page=aide" style="color:#6366f1;font-weight:600">← Retour au centre d\'aide</a></p>
        </div>';
        return;
    }

    $cat = $categories[$article['category']] ?? ['label'=>'Aide','icon'=>'fa-circle-question','color'=>'#6366f1'];

    // Articles connexes (même catégorie)
    $related = [];
    try {
        $stmtR = $pdo->prepare("SELECT slug, title, icon FROM help_articles WHERE category = ? AND slug != ? AND status = 'published' ORDER BY sort_order ASC LIMIT 4");
        $stmtR->execute([$article['category'], $articleSlug]);
        $related = $stmtR->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    ?>
    <style>
    .ha-article-wrap { font-family: 'Inter', -apple-system, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px 0; }
    .ha-back { display: inline-flex; align-items: center; gap: 6px; color: #6b7280; font-size: 12px; font-weight: 600; text-decoration: none; margin-bottom: 16px; transition: color .15s; }
    .ha-back:hover { color: #6366f1; }
    .ha-article-cat { display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; margin-bottom: 12px; }
    .ha-article-title { font-size: 1.5rem; font-weight: 800; color: #111827; margin: 0 0 8px; line-height: 1.3; }
    .ha-article-summary { font-size: .9rem; color: #6b7280; margin-bottom: 24px; line-height: 1.5; }
    .ha-article-body { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 28px 32px; margin-bottom: 24px; }
    .ha-article-body h3 { font-size: 1.05rem; font-weight: 700; color: #111827; margin: 24px 0 10px; }
    .ha-article-body h3:first-child { margin-top: 0; }
    .ha-article-body p { font-size: .88rem; color: #374151; line-height: 1.7; margin: 0 0 12px; }
    .ha-article-body ul, .ha-article-body ol { padding-left: 20px; margin: 0 0 14px; }
    .ha-article-body li { font-size: .86rem; color: #374151; line-height: 1.7; margin-bottom: 4px; }
    .ha-article-body strong { color: #111827; }
    .ha-related { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 18px 22px; }
    .ha-related h4 { font-size: 13px; font-weight: 700; color: #6b7280; margin: 0 0 12px; text-transform: uppercase; letter-spacing: .03em; }
    .ha-related-list { display: flex; flex-direction: column; gap: 6px; }
    .ha-related-link { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; text-decoration: none; color: #374151; font-size: .85rem; font-weight: 500; transition: all .12s; }
    .ha-related-link:hover { background: #fff; color: #6366f1; }
    .ha-related-link i { font-size: 11px; color: #9ca3af; width: 16px; text-align: center; }
    </style>

    <div class="ha-article-wrap">
        <a href="?page=aide&cat=<?= htmlspecialchars($article['category']) ?>" class="ha-back"><i class="fas fa-arrow-left"></i> <?= htmlspecialchars($cat['label']) ?></a>
        <div class="ha-article-cat" style="background:<?= $cat['color'] ?>15;color:<?= $cat['color'] ?>">
            <i class="fas <?= $cat['icon'] ?>"></i> <?= htmlspecialchars($cat['label']) ?>
        </div>
        <h1 class="ha-article-title"><?= htmlspecialchars($article['title']) ?></h1>
        <?php if (!empty($article['summary'])): ?>
        <p class="ha-article-summary"><?= htmlspecialchars($article['summary']) ?></p>
        <?php endif; ?>

        <div class="ha-article-body">
            <?= $article['content'] ?>
        </div>

        <?php if (!empty($related)): ?>
        <div class="ha-related">
            <h4>Articles connexes</h4>
            <div class="ha-related-list">
                <?php foreach ($related as $rel): ?>
                <a href="?page=aide&article=<?= htmlspecialchars($rel['slug']) ?>" class="ha-related-link">
                    <i class="fas <?= htmlspecialchars($rel['icon'] ?? 'fa-circle-question') ?>"></i>
                    <?= htmlspecialchars($rel['title']) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return;
}

// ── Vue liste : récupérer articles ──
$articles = [];
$counts = [];
try {
    $where = ["status = 'published'"];
    $params = [];

    if ($filterCat !== 'all' && isset($categories[$filterCat])) {
        $where[] = "category = ?";
        $params[] = $filterCat;
    }
    if ($searchQuery !== '') {
        $where[] = "(title LIKE ? OR summary LIKE ? OR content LIKE ?)";
        $params[] = "%{$searchQuery}%";
        $params[] = "%{$searchQuery}%";
        $params[] = "%{$searchQuery}%";
    }

    $whereSQL = 'WHERE ' . implode(' AND ', $where);
    $stmt = $pdo->prepare("SELECT id, category, title, slug, icon, summary, sort_order FROM help_articles {$whereSQL} ORDER BY category ASC, sort_order ASC");
    $stmt->execute($params);
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Comptages par catégorie
    $stmtC = $pdo->query("SELECT category, COUNT(*) as cnt FROM help_articles WHERE status='published' GROUP BY category");
    while ($row = $stmtC->fetch()) {
        $counts[$row['category']] = (int)$row['cnt'];
    }
} catch (Throwable $e) {
    error_log('[aide] ' . $e->getMessage());
}
$totalArticles = array_sum($counts);

// Grouper par catégorie pour affichage
$grouped = [];
foreach ($articles as $a) {
    $grouped[$a['category']][] = $a;
}
?>

<style>
.ha-wrap { font-family: 'Inter', -apple-system, sans-serif; }

/* ── Banner ── */
.ha-banner { background: #fff; border-radius: 16px; padding: 28px 32px; margin-bottom: 22px; border: 1px solid #e5e7eb; position: relative; overflow: hidden; text-align: center; }
.ha-banner::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #0ea5e9, #6366f1, #8b5cf6); opacity: .75; }
.ha-banner h2 { font-size: 1.4rem; font-weight: 800; color: #111827; margin: 0 0 6px; display: flex; align-items: center; justify-content: center; gap: 10px; }
.ha-banner h2 i { color: #0ea5e9; }
.ha-banner p { color: #6b7280; font-size: .85rem; margin: 0 0 18px; }

/* ── Search ── */
.ha-search { max-width: 440px; margin: 0 auto; position: relative; }
.ha-search input { width: 100%; padding: 11px 16px 11px 40px; border: 1px solid #e5e7eb; border-radius: 12px; font-size: .88rem; font-family: inherit; color: #111827; transition: all .2s; box-sizing: border-box; }
.ha-search input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.ha-search i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: .8rem; }

/* ── Category cards ── */
.ha-cats { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; margin-bottom: 28px; }
.ha-cat { display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; text-decoration: none; color: inherit; transition: all .15s; }
.ha-cat:hover { border-color: var(--hc-c, #6366f1); box-shadow: 0 2px 12px rgba(0,0,0,.06); transform: translateY(-1px); }
.ha-cat.active { border-color: var(--hc-c, #6366f1); background: var(--hc-bg, #f0f4ff); }
.ha-cat-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.ha-cat-label { font-size: 13px; font-weight: 700; color: #111827; }
.ha-cat-desc { font-size: 11px; color: #9ca3af; margin-top: 1px; }
.ha-cat-count { margin-left: auto; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 8px; background: #f3f4f6; color: #9ca3af; flex-shrink: 0; }

/* ── Section ── */
.ha-section { margin-bottom: 24px; }
.ha-section-head { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
.ha-section-head h3 { font-size: .95rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.ha-section-head .count { font-size: .65rem; font-weight: 600; padding: 2px 8px; border-radius: 8px; background: #f3f4f6; color: #9ca3af; }

/* ── Article cards ── */
.ha-articles { display: flex; flex-direction: column; gap: 8px; }
.ha-article { display: flex; align-items: center; gap: 14px; padding: 14px 18px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; text-decoration: none; color: inherit; transition: all .15s; }
.ha-article:hover { border-color: #6366f1; box-shadow: 0 2px 12px rgba(99,102,241,.06); transform: translateY(-1px); }
.ha-article-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
.ha-article-info { flex: 1; min-width: 0; }
.ha-article-name { font-size: .88rem; font-weight: 700; color: #111827; margin-bottom: 2px; }
.ha-article-desc { font-size: .78rem; color: #6b7280; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.ha-article-arrow { color: #d1d5db; font-size: 10px; flex-shrink: 0; transition: all .13s; }
.ha-article:hover .ha-article-arrow { color: #6366f1; transform: translateX(2px); }

/* ── Empty ── */
.ha-empty { text-align: center; padding: 50px 20px; color: #9ca3af; }
.ha-empty i { font-size: 2rem; opacity: .2; margin-bottom: 10px; display: block; }

@media (max-width: 640px) {
    .ha-cats { grid-template-columns: 1fr; }
    .ha-banner { border-radius: 0; border-left: none; border-right: none; }
}
</style>

<div class="ha-wrap">

<!-- Banner -->
<div class="ha-banner">
    <h2><i class="fas fa-life-ring"></i> Centre d'aide</h2>
    <p>Trouvez des réponses à vos questions — guides, tutoriels et bonnes pratiques</p>
    <form class="ha-search" method="GET">
        <input type="hidden" name="page" value="aide">
        <?php if ($filterCat !== 'all'): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($filterCat) ?>"><?php endif; ?>
        <i class="fas fa-magnifying-glass"></i>
        <input type="text" name="q" placeholder="Rechercher un sujet…" value="<?= htmlspecialchars($searchQuery) ?>">
    </form>
</div>

<!-- Catégories -->
<div class="ha-cats">
    <?php foreach ($categories as $catKey => $cat):
        $isActive = ($filterCat === $catKey);
        $cnt = $counts[$catKey] ?? 0;
        $url = $isActive ? '?page=aide' : '?page=aide&cat=' . $catKey;
        if ($searchQuery) $url .= '&q=' . urlencode($searchQuery);
    ?>
    <a href="<?= $url ?>" class="ha-cat<?= $isActive ? ' active' : '' ?>" style="--hc-c:<?= $cat['color'] ?>;--hc-bg:<?= $cat['color'] ?>0a">
        <div class="ha-cat-icon" style="background:<?= $cat['color'] ?>15;color:<?= $cat['color'] ?>">
            <i class="fas <?= $cat['icon'] ?>"></i>
        </div>
        <div>
            <div class="ha-cat-label"><?= $cat['label'] ?></div>
            <div class="ha-cat-desc"><?= $cat['desc'] ?></div>
        </div>
        <span class="ha-cat-count"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Articles par catégorie -->
<?php if (empty($articles)): ?>
<div class="ha-empty">
    <i class="fas fa-<?= $searchQuery ? 'magnifying-glass' : 'book-open' ?>"></i>
    <h3 style="color:#6b7280;font-size:1rem;font-weight:600"><?= $searchQuery ? 'Aucun résultat' : 'Aucun article d\'aide' ?></h3>
    <p><?= $searchQuery ? 'Aucun article pour « ' . htmlspecialchars($searchQuery) . ' ». <a href="?page=aide" style="color:#6366f1">Effacer</a>' : 'Les articles d\'aide seront bientôt disponibles.' ?></p>
</div>
<?php else: ?>

<?php foreach ($categories as $catKey => $cat):
    if (!isset($grouped[$catKey])) continue;
    if ($filterCat !== 'all' && $filterCat !== $catKey) continue;
?>
<div class="ha-section">
    <div class="ha-section-head">
        <h3>
            <i class="fas <?= $cat['icon'] ?>" style="color:<?= $cat['color'] ?>;font-size:14px"></i>
            <?= $cat['label'] ?>
            <span class="count"><?= count($grouped[$catKey]) ?></span>
        </h3>
    </div>
    <div class="ha-articles">
        <?php foreach ($grouped[$catKey] as $a): ?>
        <a href="?page=aide&article=<?= htmlspecialchars($a['slug']) ?>" class="ha-article">
            <div class="ha-article-icon" style="background:<?= $cat['color'] ?>12;color:<?= $cat['color'] ?>">
                <i class="fas <?= htmlspecialchars($a['icon'] ?? 'fa-circle-question') ?>"></i>
            </div>
            <div class="ha-article-info">
                <div class="ha-article-name"><?= htmlspecialchars($a['title']) ?></div>
                <?php if (!empty($a['summary'])): ?>
                <div class="ha-article-desc"><?= htmlspecialchars($a['summary']) ?></div>
                <?php endif; ?>
            </div>
            <i class="fas fa-chevron-right ha-article-arrow"></i>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

</div>