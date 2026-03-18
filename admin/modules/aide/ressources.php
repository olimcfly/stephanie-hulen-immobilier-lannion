<?php
/**
 * /admin/modules/aide/ressources.php — Ressources Stratégie v1.0
 * ════════════════════════════════════════════════════════════
 * Guides stratégiques stockés en BDD (strategy_guides)
 * Parcours : Fondations → Stratégie Client → Communication
 *            → Visibilité → Conversion → Fidélisation
 * ════════════════════════════════════════════════════════════
 */

if (!isset($pdo)) {
    require_once dirname(__DIR__, 3) . '/includes/init.php';
}

$guideSlug = $_GET['guide'] ?? '';
$filterCat = $_GET['cat'] ?? 'all';

// ── Catégories (dans l'ordre du parcours) ──
$categories = [
    'fondations'       => ['label' => 'Fondations',         'icon' => 'fa-mountain',        'color' => '#dc2626', 'desc' => 'Comprendre le marché et votre position'],
    'strategie-client' => ['label' => 'Stratégie Client',   'icon' => 'fa-brain',           'color' => '#8b5cf6', 'desc' => 'Connaître vos clients en profondeur'],
    'communication'    => ['label' => 'Communication',      'icon' => 'fa-pen-fancy',       'color' => '#0891b2', 'desc' => 'Écrire des messages qui convertissent'],
    'visibilite'       => ['label' => 'Visibilité',         'icon' => 'fa-eye',             'color' => '#65a30d', 'desc' => 'SEO, publicité, réseaux sociaux'],
    'conversion'       => ['label' => 'Conversion',         'icon' => 'fa-bolt',            'color' => '#ea580c', 'desc' => 'Estimateur, suivi, séquences email'],
    'fidelisation'     => ['label' => 'Fidélisation',       'icon' => 'fa-heart',           'color' => '#ec4899', 'desc' => 'Témoignages, avis, preuve sociale'],
];

// ═══════════════════════════════════════════════════════════
// VUE GUIDE UNIQUE
// ═══════════════════════════════════════════════════════════
if ($guideSlug) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM strategy_guides WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$guideSlug]);
        $guide = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $guide = null; }

    if (!$guide) {
        echo '<div style="text-align:center;padding:60px 20px;color:#9ca3af">
            <i class="fas fa-book-open" style="font-size:2rem;opacity:.3;margin-bottom:12px;display:block"></i>
            <h3 style="color:#6b7280">Guide non trouvé</h3>
            <p><a href="?page=ressources-clients" style="color:#6366f1;font-weight:600">← Retour aux guides</a></p>
        </div>';
        return;
    }

    $cat = $categories[$guide['category']] ?? ['label'=>'Guide','icon'=>'fa-book','color'=>'#6366f1'];

    // Guides connexes
    $related = [];
    try {
        $stmtR = $pdo->prepare("SELECT slug, title, icon, reading_time FROM strategy_guides WHERE category = ? AND slug != ? AND status = 'published' ORDER BY sort_order ASC LIMIT 3");
        $stmtR->execute([$guide['category'], $guideSlug]);
        $related = $stmtR->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    // Guide suivant (toutes catégories)
    $nextGuide = null;
    try {
        $stmtN = $pdo->prepare("SELECT slug, title, icon, category FROM strategy_guides WHERE status = 'published' AND (category > ? OR (category = ? AND sort_order > ?)) ORDER BY category ASC, sort_order ASC LIMIT 1");
        $stmtN->execute([$guide['category'], $guide['category'], $guide['sort_order']]);
        $nextGuide = $stmtN->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
    ?>

<style>
.sg-article { font-family:'Inter',-apple-system,sans-serif; max-width:820px; margin:0 auto; padding:20px 0; }
.sg-back { display:inline-flex; align-items:center; gap:6px; color:#6b7280; font-size:12px; font-weight:600; text-decoration:none; margin-bottom:16px; transition:color .15s; }
.sg-back:hover { color:#6366f1; }
.sg-article-cat { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:8px; font-size:11px; font-weight:700; text-transform:uppercase; margin-bottom:10px; }
.sg-article-title { font-size:1.6rem; font-weight:800; color:#111827; margin:0 0 8px; line-height:1.3; }
.sg-article-meta { display:flex; gap:14px; font-size:12px; color:#9ca3af; margin-bottom:24px; align-items:center; }
.sg-article-meta i { font-size:11px; }
.sg-article-body { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:32px 36px; margin-bottom:24px; }
.sg-article-body h3 { font-size:1.1rem; font-weight:700; color:#111827; margin:28px 0 12px; padding-bottom:8px; border-bottom:2px solid #f3f4f6; }
.sg-article-body h3:first-child { margin-top:0; }
.sg-article-body h4 { font-size:.95rem; font-weight:700; color:#374151; margin:20px 0 8px; }
.sg-article-body p { font-size:.88rem; color:#374151; line-height:1.8; margin:0 0 14px; }
.sg-article-body ul, .sg-article-body ol { padding-left:20px; margin:0 0 14px; }
.sg-article-body li { font-size:.86rem; color:#374151; line-height:1.8; margin-bottom:6px; }
.sg-article-body strong { color:#111827; }
.sg-article-body table { width:100%; border-collapse:collapse; margin:16px 0; font-size:13px; }
.sg-article-body table th, .sg-article-body table td { padding:10px 14px; border:1px solid #e5e7eb; text-align:left; }
.sg-article-body table th { background:#f9fafb; font-weight:700; color:#374151; }

/* Next guide CTA */
.sg-next { background:linear-gradient(135deg,#6366f1,#4f46e5); border-radius:14px; padding:22px 28px; display:flex; align-items:center; gap:16px; margin-bottom:24px; }
.sg-next-text { flex:1; color:#fff; }
.sg-next-text h4 { font-size:14px; font-weight:700; margin:0 0 4px; opacity:.8; }
.sg-next-text p { font-size:15px; font-weight:700; margin:0; }
.sg-next-btn { display:inline-flex; align-items:center; gap:6px; padding:10px 22px; background:#fff; color:#6366f1; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; transition:all .15s; flex-shrink:0; }
.sg-next-btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.2); }

/* Related */
.sg-related { background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:18px 22px; }
.sg-related h4 { font-size:13px; font-weight:700; color:#6b7280; margin:0 0 12px; text-transform:uppercase; letter-spacing:.03em; }
.sg-related-list { display:flex; flex-direction:column; gap:6px; }
.sg-related-link { display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; text-decoration:none; color:#374151; font-size:.85rem; font-weight:500; transition:all .12s; }
.sg-related-link:hover { background:#fff; color:#6366f1; }
.sg-related-link i { font-size:11px; color:#9ca3af; width:16px; text-align:center; }
.sg-related-link .dur { margin-left:auto; font-size:10px; color:#9ca3af; }

@media(max-width:640px) {
    .sg-article-body { padding:20px; }
    .sg-next { flex-direction:column; text-align:center; }
}
</style>

<div class="sg-article">
    <a href="?page=ressources-clients&cat=<?= htmlspecialchars($guide['category']) ?>" class="sg-back"><i class="fas fa-arrow-left"></i> <?= htmlspecialchars($cat['label']) ?></a>

    <div class="sg-article-cat" style="background:<?= $cat['color'] ?>15;color:<?= $cat['color'] ?>">
        <i class="fas <?= $cat['icon'] ?>"></i> <?= htmlspecialchars($cat['label']) ?>
    </div>
    <h1 class="sg-article-title"><?= htmlspecialchars($guide['title']) ?></h1>
    <div class="sg-article-meta">
        <span><i class="fas fa-user"></i> <?= htmlspecialchars($guide['author'] ?? 'Olivier Colas') ?></span>
        <span><i class="fas fa-clock"></i> <?= htmlspecialchars($guide['reading_time'] ?? '10 min') ?></span>
    </div>

    <?php if (!empty($guide['summary'])): ?>
    <p style="font-size:.95rem;color:#6b7280;line-height:1.6;margin-bottom:24px;padding:16px 20px;background:#f9fafb;border-radius:12px;border-left:4px solid <?= $cat['color'] ?>"><?= htmlspecialchars($guide['summary']) ?></p>
    <?php endif; ?>

    <div class="sg-article-body">
        <?= $guide['content'] ?>
    </div>

    <?php if ($nextGuide): 
        $nextCat = $categories[$nextGuide['category']] ?? ['label'=>'Suite','color'=>'#6366f1'];
    ?>
    <div class="sg-next">
        <div class="sg-next-text">
            <h4>Prochaine étape →</h4>
            <p><?= htmlspecialchars($nextGuide['title']) ?></p>
        </div>
        <a href="?page=ressources-clients&guide=<?= htmlspecialchars($nextGuide['slug']) ?>" class="sg-next-btn">
            <i class="fas fa-arrow-right"></i> Lire le guide
        </a>
    </div>
    <?php endif; ?>

    <?php if (!empty($related)): ?>
    <div class="sg-related">
        <h4>Dans la même catégorie</h4>
        <div class="sg-related-list">
            <?php foreach ($related as $rel): ?>
            <a href="?page=ressources-clients&guide=<?= htmlspecialchars($rel['slug']) ?>" class="sg-related-link">
                <i class="fas <?= htmlspecialchars($rel['icon'] ?? 'fa-book') ?>"></i>
                <?= htmlspecialchars($rel['title']) ?>
                <span class="dur"><i class="fas fa-clock"></i> <?= htmlspecialchars($rel['reading_time'] ?? '') ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

    <?php
    return;
}

// ═══════════════════════════════════════════════════════════
// VUE LISTE
// ═══════════════════════════════════════════════════════════
$guides = [];
$counts = [];
try {
    $where = ["status = 'published'"];
    $params = [];
    if ($filterCat !== 'all' && isset($categories[$filterCat])) {
        $where[] = "category = ?";
        $params[] = $filterCat;
    }
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
    $stmt = $pdo->prepare("SELECT id, category, title, slug, icon, reading_time, summary, sort_order FROM strategy_guides {$whereSQL} ORDER BY FIELD(category,'fondations','strategie-client','communication','visibilite','conversion','fidelisation'), sort_order ASC");
    $stmt->execute($params);
    $guides = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtC = $pdo->query("SELECT category, COUNT(*) as cnt FROM strategy_guides WHERE status='published' GROUP BY category");
    while ($row = $stmtC->fetch()) { $counts[$row['category']] = (int)$row['cnt']; }
} catch (Throwable $e) {
    error_log('[ressources] ' . $e->getMessage());
}
$totalGuides = array_sum($counts);
$grouped = [];
foreach ($guides as $g) { $grouped[$g['category']][] = $g; }
?>

<style>
.sg-wrap { font-family:'Inter',-apple-system,sans-serif; }

/* Banner */
.sg-banner { background:#fff; border-radius:16px; padding:28px 32px; margin-bottom:22px; border:1px solid #e5e7eb; position:relative; overflow:hidden; }
.sg-banner::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,#dc2626,#8b5cf6,#0891b2,#65a30d,#ea580c,#ec4899); opacity:.75; }
.sg-banner-top { display:flex; align-items:center; justify-content:space-between; margin-bottom:8px; flex-wrap:wrap; gap:12px; }
.sg-banner h2 { font-size:1.4rem; font-weight:800; color:#111827; margin:0; display:flex; align-items:center; gap:10px; }
.sg-banner h2 i { color:#c9913b; }
.sg-banner p { color:#6b7280; font-size:.85rem; margin:0; }
.sg-banner .sg-count { font-size:12px; font-weight:700; padding:4px 12px; background:#f3f4f6; border-radius:8px; color:#6b7280; }

/* Parcours indicator */
.sg-parcours { display:flex; align-items:center; gap:0; margin-bottom:24px; overflow-x:auto; padding:4px 0; }
.sg-parcours-step { display:flex; align-items:center; gap:0; flex-shrink:0; }
.sg-parcours-dot { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; color:#fff; flex-shrink:0; cursor:pointer; transition:all .15s; text-decoration:none; position:relative; }
.sg-parcours-dot:hover { transform:scale(1.15); }
.sg-parcours-dot.active { box-shadow:0 0 0 3px #fff, 0 0 0 5px currentColor; }
.sg-parcours-dot .sg-tip { display:none; position:absolute; bottom:calc(100% + 8px); left:50%; transform:translateX(-50%); background:#1e293b; color:#e2e8f0; padding:6px 10px; border-radius:8px; font-size:10px; font-weight:600; white-space:nowrap; z-index:10; }
.sg-parcours-dot .sg-tip::after { content:''; position:absolute; top:100%; left:50%; transform:translateX(-50%); border:5px solid transparent; border-top-color:#1e293b; }
.sg-parcours-dot:hover .sg-tip { display:block; }
.sg-parcours-line { width:24px; height:2px; background:#e5e7eb; flex-shrink:0; }

/* Category filter cards */
.sg-cats { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:10px; margin-bottom:28px; }
.sg-cat { display:flex; align-items:center; gap:10px; padding:12px 14px; background:#fff; border:1px solid #e5e7eb; border-radius:12px; text-decoration:none; color:inherit; transition:all .15s; }
.sg-cat:hover { border-color:var(--sc-c,#6366f1); box-shadow:0 2px 10px rgba(0,0,0,.05); transform:translateY(-1px); }
.sg-cat.active { border-color:var(--sc-c,#6366f1); background:var(--sc-bg,#f0f4ff); }
.sg-cat-icon { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; }
.sg-cat-label { font-size:12px; font-weight:700; color:#111827; }
.sg-cat-desc { font-size:10px; color:#9ca3af; margin-top:1px; }
.sg-cat-count { margin-left:auto; font-size:10px; font-weight:700; padding:2px 7px; border-radius:6px; background:#f3f4f6; color:#9ca3af; flex-shrink:0; }

/* Section */
.sg-section { margin-bottom:24px; }
.sg-section-head { display:flex; align-items:center; gap:8px; margin-bottom:12px; }
.sg-section-head h3 { font-size:.95rem; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px; }
.sg-section-head .count { font-size:.65rem; font-weight:600; padding:2px 8px; border-radius:8px; background:#f3f4f6; color:#9ca3af; }
.sg-section-head .step-num { font-size:10px; font-weight:800; width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; flex-shrink:0; }

/* Guide card */
.sg-guides { display:flex; flex-direction:column; gap:10px; }
.sg-guide { display:flex; gap:16px; padding:18px 20px; background:#fff; border:1px solid #e5e7eb; border-radius:14px; text-decoration:none; color:inherit; transition:all .15s; }
.sg-guide:hover { border-color:#6366f1; box-shadow:0 4px 16px rgba(99,102,241,.06); transform:translateY(-2px); }
.sg-guide-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.sg-guide-info { flex:1; min-width:0; }
.sg-guide-title { font-size:.95rem; font-weight:700; color:#111827; margin-bottom:4px; }
.sg-guide-summary { font-size:.78rem; color:#6b7280; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.sg-guide-meta { display:flex; gap:10px; margin-top:6px; }
.sg-guide-badge { font-size:10px; padding:2px 8px; border-radius:6px; font-weight:600; }
.sg-guide-arrow { display:flex; align-items:center; color:#d1d5db; font-size:12px; flex-shrink:0; transition:all .13s; }
.sg-guide:hover .sg-guide-arrow { color:#6366f1; transform:translateX(3px); }

.sg-empty { text-align:center; padding:50px 20px; color:#9ca3af; }
.sg-empty i { font-size:2rem; opacity:.2; margin-bottom:10px; display:block; }

@media(max-width:640px) {
    .sg-cats { grid-template-columns:1fr 1fr; }
    .sg-guide { flex-direction:column; gap:10px; }
    .sg-guide-icon { width:40px; height:40px; font-size:16px; }
}
</style>

<div class="sg-wrap">

<!-- Banner -->
<div class="sg-banner">
    <div class="sg-banner-top">
        <h2><i class="fas fa-graduation-cap"></i> Ressources Stratégie</h2>
        <span class="sg-count"><?= $totalGuides ?> guides disponibles</span>
    </div>
    <p>Votre parcours de formation complet — de la compréhension du marché à la fidélisation de vos clients</p>
</div>

<!-- Parcours visuel -->
<?php
$stepNum = 0;
$catKeys = array_keys($categories);
?>
<div class="sg-parcours">
    <?php foreach ($categories as $catKey => $cat):
        $stepNum++;
        $isActive = ($filterCat === $catKey);
        $hasCnt = isset($counts[$catKey]) && $counts[$catKey] > 0;
        $url = $isActive ? '?page=ressources-clients' : '?page=ressources-clients&cat=' . $catKey;
    ?>
    <?php if ($stepNum > 1): ?><div class="sg-parcours-line"></div><?php endif; ?>
    <div class="sg-parcours-step">
        <a href="<?= $url ?>" class="sg-parcours-dot<?= $isActive ? ' active' : '' ?>" style="background:<?= $cat['color'] ?>;<?= $isActive ? 'color:'.$cat['color'] : '' ?>">
            <i class="fas <?= $cat['icon'] ?>"></i>
            <span class="sg-tip"><?= $stepNum ?>. <?= $cat['label'] ?><?= $hasCnt ? ' ('.$counts[$catKey].')' : '' ?></span>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- Category filter -->
<div class="sg-cats">
    <?php foreach ($categories as $catKey => $cat):
        $isActive = ($filterCat === $catKey);
        $cnt = $counts[$catKey] ?? 0;
        $url = $isActive ? '?page=ressources-clients' : '?page=ressources-clients&cat=' . $catKey;
    ?>
    <a href="<?= $url ?>" class="sg-cat<?= $isActive ? ' active' : '' ?>" style="--sc-c:<?= $cat['color'] ?>;--sc-bg:<?= $cat['color'] ?>0a">
        <div class="sg-cat-icon" style="background:<?= $cat['color'] ?>15;color:<?= $cat['color'] ?>">
            <i class="fas <?= $cat['icon'] ?>"></i>
        </div>
        <div>
            <div class="sg-cat-label"><?= $cat['label'] ?></div>
            <div class="sg-cat-desc"><?= $cat['desc'] ?></div>
        </div>
        <?php if ($cnt > 0): ?><span class="sg-cat-count"><?= $cnt ?></span><?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<!-- Guides par catégorie -->
<?php if (empty($guides)): ?>
<div class="sg-empty">
    <i class="fas fa-book-open"></i>
    <h3 style="color:#6b7280;font-size:1rem;font-weight:600">Aucun guide disponible</h3>
    <p>Les guides stratégiques seront bientôt publiés.</p>
</div>
<?php else: ?>

<?php
$stepNum = 0;
foreach ($categories as $catKey => $cat):
    if (!isset($grouped[$catKey])) continue;
    if ($filterCat !== 'all' && $filterCat !== $catKey) continue;
    $stepNum++;
?>
<div class="sg-section">
    <div class="sg-section-head">
        <span class="step-num" style="background:<?= $cat['color'] ?>"><?= $stepNum ?></span>
        <h3>
            <i class="fas <?= $cat['icon'] ?>" style="color:<?= $cat['color'] ?>;font-size:14px"></i>
            <?= $cat['label'] ?>
            <span class="count"><?= count($grouped[$catKey]) ?> guide<?= count($grouped[$catKey]) > 1 ? 's' : '' ?></span>
        </h3>
    </div>
    <div class="sg-guides">
        <?php foreach ($grouped[$catKey] as $g): ?>
        <a href="?page=ressources-clients&guide=<?= htmlspecialchars($g['slug']) ?>" class="sg-guide">
            <div class="sg-guide-icon" style="background:<?= $cat['color'] ?>12;color:<?= $cat['color'] ?>">
                <i class="fas <?= htmlspecialchars($g['icon'] ?? 'fa-book') ?>"></i>
            </div>
            <div class="sg-guide-info">
                <div class="sg-guide-title"><?= htmlspecialchars($g['title']) ?></div>
                <?php if (!empty($g['summary'])): ?>
                <div class="sg-guide-summary"><?= htmlspecialchars($g['summary']) ?></div>
                <?php endif; ?>
                <div class="sg-guide-meta">
                    <?php if (!empty($g['reading_time'])): ?>
                    <span class="sg-guide-badge" style="background:<?= $cat['color'] ?>10;color:<?= $cat['color'] ?>"><i class="fas fa-clock" style="font-size:9px;margin-right:3px"></i> <?= htmlspecialchars($g['reading_time']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="sg-guide-arrow"><i class="fas fa-chevron-right"></i></div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

</div>