<?php
/**
 * /front/templates/pages/t3-secteur.php
 * Template Secteur — Page quartier / secteur geographique
 * Affiche les donnees marche, atouts et infos pratiques d'un secteur
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
$heroTitle    = $fields['hero_title']    ?? ($page['title'] ?? 'Secteur');
$heroSubtitle = $fields['hero_subtitle'] ?? '';
$heroImage    = $fields['hero_image']    ?? '';

// ── Donnees marche ──
$prixMoyen  = $fields['prix_moyen']  ?? '';
$prixMaison = $fields['prix_maison'] ?? '';
$prixAppart = $fields['prix_appart'] ?? '';
$evolution  = $fields['evolution']   ?? '';
$nbVentes   = $fields['nb_ventes']   ?? '';

// ── Contenu ──
$bodyIntro   = $fields['body_intro']   ?? '';
$bodyContent = $fields['body_content'] ?? '';
$atout1      = $fields['atout_1']      ?? '';
$atout2      = $fields['atout_2']      ?? '';
$atout3      = $fields['atout_3']      ?? '';

// ── Infos pratiques ──
$transport = $fields['transport'] ?? '';
$ecoles    = $fields['ecoles']    ?? '';
$commerces = $fields['commerces'] ?? '';
$cadreVie  = $fields['cadre_vie'] ?? '';

// ── CTA ──
$ctaBtnText = $fields['cta_btn_text'] ?? '';
$ctaBtnUrl  = $fields['cta_btn_url']  ?? '/contact';

// ── SEO ──
$metaTitle = $page['meta_title'] ?? $fields['seo_title'] ?? $heroTitle;
$metaDesc  = $page['meta_description'] ?? $fields['seo_description'] ?? $heroSubtitle;
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
    /* ── Secteur-specific styles ── */
    .t3-hero-bg {
        background:linear-gradient(145deg,var(--tp-primary-d) 0%,var(--tp-primary) 55%,var(--tp-primary-l) 100%);
        padding:90px 0 70px; position:relative; overflow:hidden;
    }
    .t3-hero-bg.has-image {
        background-size:cover; background-position:center; background-repeat:no-repeat;
    }
    .t3-hero-bg.has-image::after {
        content:''; position:absolute; inset:0;
        background:linear-gradient(145deg,rgba(18,42,55,.88) 0%,rgba(27,58,75,.75) 55%,rgba(44,95,124,.65) 100%);
    }
    .t3-hero-bg .tp-hero-inner { position:relative; z-index:1; text-align:center; }

    .t3-market-grid {
        display:grid; grid-template-columns:repeat(5,1fr); gap:0;
        background:var(--tp-white); border-bottom:1px solid var(--tp-border);
    }
    .t3-market-item {
        text-align:center; padding:28px 16px;
        border-right:1px solid var(--tp-border);
    }
    .t3-market-item:last-child { border-right:none; }
    .t3-market-num {
        font-family:var(--tp-ff-display); font-size:1.8rem; font-weight:900;
        color:var(--tp-primary); line-height:1; margin-bottom:6px;
    }
    .t3-market-lbl {
        font-size:.72rem; color:var(--tp-text3); text-transform:uppercase;
        letter-spacing:.05em; font-weight:600;
    }

    .t3-atouts-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }
    .t3-atout-card {
        background:var(--tp-white); border-radius:var(--tp-radius);
        border:1px solid var(--tp-border); padding:28px 24px;
        box-shadow:var(--tp-shadow); transition:transform .2s,box-shadow .2s;
        text-align:center;
    }
    .t3-atout-card:hover { transform:translateY(-3px); box-shadow:var(--tp-shadow-lg); }

    .t3-info-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:24px; }
    .t3-info-card {
        background:var(--tp-white); border-radius:var(--tp-radius);
        border:1px solid var(--tp-border); padding:28px 24px;
        box-shadow:var(--tp-shadow); transition:transform .2s,box-shadow .2s;
    }
    .t3-info-card:hover { transform:translateY(-3px); box-shadow:var(--tp-shadow-lg); }
    .t3-info-icon {
        width:48px; height:48px; border-radius:12px;
        background:rgba(200,169,110,.1); display:flex; align-items:center;
        justify-content:center; margin-bottom:16px; font-size:1.3rem; color:var(--tp-accent-d);
    }
    .t3-info-title {
        font-family:var(--tp-ff-display); font-size:1.05rem; font-weight:800;
        color:var(--tp-primary); margin-bottom:10px;
    }
    .t3-info-text { font-size:.88rem; color:var(--tp-text2); line-height:1.7; }

    @media (max-width:960px) {
        .t3-market-grid { grid-template-columns:repeat(2,1fr); }
        .t3-market-item { border-bottom:1px solid var(--tp-border); }
        .t3-atouts-grid { grid-template-columns:1fr; }
        .t3-info-grid   { grid-template-columns:1fr; }
    }
    @media (max-width:600px) {
        .t3-market-grid { grid-template-columns:1fr; }
        .t3-hero-bg { padding:60px 0 50px; }
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
     HERO — SECTEUR
     ═══════════════════════════════════════════════════ -->
<section class="t3-hero-bg<?= $heroImage ? ' has-image' : '' ?>"<?= $heroImage ? ' style="background-image:url(\'' . htmlspecialchars($heroImage) . '\')"' : '' ?>>
    <div class="tp-hero-inner">
        <div class="tp-eyebrow">Secteur</div>
        <h1 class="tp-hero-h1" style="margin-left:auto;margin-right:auto"><?= htmlspecialchars($heroTitle) ?></h1>
        <?php if ($heroSubtitle): ?>
        <p class="tp-hero-sub" style="margin-left:auto;margin-right:auto"><?= $heroSubtitle ?></p>
        <?php endif; ?>
    </div>
</section>

<?php
// ── Donnees marche ──
$marketData = [];
if ($prixMoyen)  $marketData[] = ['num' => $prixMoyen,  'lbl' => 'Prix moyen / m²'];
if ($prixMaison) $marketData[] = ['num' => $prixMaison, 'lbl' => 'Prix maison'];
if ($prixAppart) $marketData[] = ['num' => $prixAppart, 'lbl' => 'Prix appartement'];
if ($evolution)  $marketData[] = ['num' => $evolution,  'lbl' => 'Evolution annuelle'];
if ($nbVentes)   $marketData[] = ['num' => $nbVentes,   'lbl' => 'Ventes / an'];
if ($marketData):
?>
<!-- ═══════════════════════════════════════════════════
     DONNEES MARCHE
     ═══════════════════════════════════════════════════ -->
<div class="t3-market-grid" style="grid-template-columns:repeat(<?= count($marketData) ?>,1fr)">
    <?php foreach ($marketData as $m): ?>
    <div class="t3-market-item">
        <div class="t3-market-num"><?= htmlspecialchars($m['num']) ?></div>
        <div class="t3-market-lbl"><?= htmlspecialchars($m['lbl']) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
// ── Introduction ──
if ($bodyIntro):
?>
<!-- ═══════════════════════════════════════════════════
     INTRODUCTION
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-white">
    <div class="tp-container-sm">
        <div class="tp-rich-body"><?= $bodyIntro ?></div>
    </div>
</section>
<?php endif; ?>

<?php
// ── Atouts ──
$atouts = [];
if ($atout1) $atouts[] = $atout1;
if ($atout2) $atouts[] = $atout2;
if ($atout3) $atouts[] = $atout3;
if ($atouts):
?>
<!-- ═══════════════════════════════════════════════════
     ATOUTS DU SECTEUR
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-light">
    <div class="tp-container">
        <h2 class="tp-section-title">Les atouts du secteur</h2>
        <div class="t3-atouts-grid">
            <?php foreach ($atouts as $i => $atout): ?>
            <div class="t3-atout-card">
                <div style="font-size:2rem;margin-bottom:14px;color:var(--tp-accent)">
                    <i class="fas fa-<?= $i === 0 ? 'star' : ($i === 1 ? 'map-marker-alt' : 'gem') ?>"></i>
                </div>
                <p style="color:var(--tp-text2);font-size:.9rem;line-height:1.7"><?= htmlspecialchars($atout) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// ── Contenu principal ──
if ($bodyContent):
?>
<!-- ═══════════════════════════════════════════════════
     CONTENU PRINCIPAL
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-white">
    <div class="tp-container-sm">
        <div class="tp-rich-body"><?= $bodyContent ?></div>
    </div>
</section>
<?php endif; ?>

<?php
// ── Infos pratiques ──
$infos = [];
if ($transport) $infos[] = ['icon' => 'fa-bus',           'title' => 'Transports',  'text' => $transport];
if ($ecoles)    $infos[] = ['icon' => 'fa-graduation-cap','title' => 'Ecoles',      'text' => $ecoles];
if ($commerces) $infos[] = ['icon' => 'fa-shopping-bag',  'title' => 'Commerces',   'text' => $commerces];
if ($cadreVie)  $infos[] = ['icon' => 'fa-tree',          'title' => 'Cadre de vie', 'text' => $cadreVie];
if ($infos):
?>
<!-- ═══════════════════════════════════════════════════
     INFOS PRATIQUES
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-light">
    <div class="tp-container">
        <h2 class="tp-section-title">Informations pratiques</h2>
        <div class="t3-info-grid">
            <?php foreach ($infos as $info): ?>
            <div class="t3-info-card">
                <div class="t3-info-icon"><i class="fas <?= $info['icon'] ?>"></i></div>
                <div class="t3-info-title"><?= htmlspecialchars($info['title']) ?></div>
                <div class="t3-info-text"><?= htmlspecialchars($info['text']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// ── CTA ──
if ($ctaBtnText):
?>
<!-- ═══════════════════════════════════════════════════
     CTA FINALE
     ═══════════════════════════════════════════════════ -->
<section class="tp-cta-section">
    <div class="tp-container" style="text-align:center">
        <div class="tp-cta-title">Vous cherchez un bien dans ce secteur ?</div>
        <div class="tp-cta-text">Contactez-nous pour decouvrir les opportunites disponibles.</div>
        <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="tp-cta-btn"><?= htmlspecialchars($ctaBtnText) ?></a>
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
