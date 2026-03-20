<?php
/**
 * /front/templates/pages/t4-blog-hub.php
 * Template Blog Hub — Page listing des articles du blog
 * Charge dynamiquement les articles depuis la table `articles`
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
$siteUrl    = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

$advisorName    = $advisor['name']    ?? ($site['name']    ?? 'Votre conseiller');
$advisorCity    = $advisor['city']    ?? ($site['city']    ?? 'votre ville');
$advisorNetwork = $advisor['network'] ?? '';

// ────────────────────────────────────────────────────
// CHAMPS HERO
// ────────────────────────────────────────────────────
$heroTitle    = $fields['hero_title']    ?? ($page['title'] ?? 'Blog');
$heroSubtitle = $fields['hero_subtitle'] ?? '';

// ── CTA ──
$ctaTitle   = $fields['cta_title']    ?? '';
$ctaBtnText = $fields['cta_btn_text'] ?? '';
$ctaBtnUrl  = $fields['cta_btn_url']  ?? '/contact';

// ── SEO ──
$metaTitle = $page['meta_title'] ?? $fields['seo_title'] ?? $heroTitle;
$metaDesc  = $page['meta_description'] ?? $fields['seo_description'] ?? $heroSubtitle;

// ────────────────────────────────────────────────────
// CHARGEMENT DES ARTICLES
// ────────────────────────────────────────────────────
$articles = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, title, slug, excerpt, image, created_at FROM articles WHERE status='published' ORDER BY created_at DESC");
        $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silently fail — show empty state
        $articles = [];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <?php if (!empty($page['og_image'])): ?><meta property="og:image" content="<?= htmlspecialchars($page['og_image']) ?>"><?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php require_once __DIR__ . '/_tpl-common.php'; ?>
    <style>
    /* ── Blog Hub specific styles ── */
    .t4-articles-grid {
        display:grid;
        grid-template-columns:repeat(auto-fill, minmax(320px, 1fr));
        gap:32px;
    }
    .t4-article-card {
        background:var(--tp-white); border:1px solid var(--tp-border);
        border-radius:var(--tp-radius); overflow:hidden;
        box-shadow:var(--tp-shadow); transition:all .3s;
        display:flex; flex-direction:column;
    }
    .t4-article-card:hover {
        box-shadow:var(--tp-shadow-lg); border-color:var(--tp-accent);
        transform:translateY(-4px);
    }
    .t4-article-card a { text-decoration:none; color:inherit; display:flex; flex-direction:column; height:100%; }
    .t4-article-img {
        width:100%; height:200px; object-fit:cover; display:block;
    }
    .t4-article-img-placeholder {
        width:100%; height:200px;
        background:linear-gradient(135deg, var(--tp-primary) 0%, var(--tp-primary-d) 100%);
        display:flex; align-items:center; justify-content:center;
        color:rgba(255,255,255,.2); font-size:3rem;
    }
    .t4-article-body {
        padding:24px; flex:1; display:flex; flex-direction:column;
    }
    .t4-article-date {
        font-size:.75rem; color:var(--tp-text3); text-transform:uppercase;
        letter-spacing:.05em; font-weight:600; margin-bottom:10px;
    }
    .t4-article-title {
        font-family:var(--tp-ff-display); font-size:1.15rem; font-weight:800;
        color:var(--tp-primary); margin-bottom:12px; line-height:1.3;
    }
    .t4-article-excerpt {
        font-size:.88rem; color:var(--tp-text2); line-height:1.7;
        flex:1; margin-bottom:16px;
    }
    .t4-article-link {
        font-size:.82rem; font-weight:700; color:var(--tp-accent-d);
        display:inline-flex; align-items:center; gap:6px;
    }
    .t4-article-link::after { content:'\2192'; }

    .t4-empty {
        text-align:center; padding:80px 20px;
    }
    .t4-empty-icon {
        font-size:4rem; color:var(--tp-border); margin-bottom:20px;
    }
    .t4-empty h3 {
        font-family:var(--tp-ff-display); font-size:1.4rem; font-weight:800;
        color:var(--tp-primary); margin-bottom:12px;
    }
    .t4-empty p {
        color:var(--tp-text2); font-size:.95rem; max-width:400px;
        margin:0 auto; line-height:1.7;
    }

    @media (max-width:960px) {
        .t4-articles-grid { grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); }
    }
    @media (max-width:600px) {
        .t4-articles-grid { grid-template-columns:1fr; }
        .t4-article-img, .t4-article-img-placeholder { height:160px; }
    }
    </style>
</head>
<body>
<div class="tp-page">

<?php
// Header
if (file_exists(__DIR__ . '/../../page.php') && function_exists('renderHeader')) {
    echo renderHeader($headerData);
} elseif (file_exists(__DIR__ . '/../../helpers/layout.php')) {
    require_once __DIR__ . '/../../helpers/layout.php';
    if (function_exists('renderHeader')) echo renderHeader($headerData);
}
?>

<!-- ═══════════════════════════════════════════════════
     HERO — BLOG
     ═══════════════════════════════════════════════════ -->
<section class="tp-hero">
    <div class="tp-hero-inner" style="text-align:center">
        <div class="tp-eyebrow">Blog</div>
        <h1 class="tp-hero-h1" style="margin-left:auto;margin-right:auto"><?= htmlspecialchars($heroTitle) ?></h1>
        <?php if ($heroSubtitle): ?>
        <p class="tp-hero-sub" style="margin-left:auto;margin-right:auto"><?= $heroSubtitle ?></p>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     ARTICLES
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-light">
    <div class="tp-container">
        <?php if ($articles): ?>
        <div class="t4-articles-grid">
            <?php foreach ($articles as $article): ?>
            <div class="t4-article-card">
                <a href="<?= htmlspecialchars($siteUrl . '/blog/' . ($article['slug'] ?? $article['id'])) ?>">
                    <?php if (!empty($article['image'])): ?>
                    <img class="t4-article-img" src="<?= htmlspecialchars($article['image']) ?>" alt="<?= htmlspecialchars($article['title'] ?? '') ?>" loading="lazy">
                    <?php else: ?>
                    <div class="t4-article-img-placeholder"><i class="fas fa-newspaper"></i></div>
                    <?php endif; ?>
                    <div class="t4-article-body">
                        <?php if (!empty($article['created_at'])): ?>
                        <div class="t4-article-date">
                            <?= date('d/m/Y', strtotime($article['created_at'])) ?>
                        </div>
                        <?php endif; ?>
                        <div class="t4-article-title"><?= htmlspecialchars($article['title'] ?? 'Article') ?></div>
                        <?php if (!empty($article['excerpt'])): ?>
                        <div class="t4-article-excerpt"><?= htmlspecialchars($article['excerpt']) ?></div>
                        <?php endif; ?>
                        <span class="t4-article-link">Lire la suite</span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="t4-empty">
            <div class="t4-empty-icon"><i class="fas fa-pen-nib"></i></div>
            <h3>Aucun article pour le moment</h3>
            <p>De nouveaux contenus seront bientot disponibles. Revenez nous voir prochainement !</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php
// ── CTA ──
if ($ctaTitle || $ctaBtnText):
?>
<!-- ═══════════════════════════════════════════════════
     CTA FINALE
     ═══════════════════════════════════════════════════ -->
<section class="tp-cta-section">
    <div class="tp-container" style="text-align:center">
        <?php if ($ctaTitle): ?><div class="tp-cta-title"><?= htmlspecialchars($ctaTitle) ?></div><?php endif; ?>
        <?php if ($ctaBtnText): ?><a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="tp-cta-btn"><?= htmlspecialchars($ctaBtnText) ?></a><?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php
// Footer
if (function_exists('renderFooter')) {
    echo renderFooter($footerData);
}
?>

</div>
</body>
</html>
