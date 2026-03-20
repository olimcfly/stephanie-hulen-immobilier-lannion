<?php
/**
 * /front/templates/pages/t2-edito.php
 * Template Edito — Page editoriale generique
 * Sert aussi de fallback pour le template "standard"
 * Lit les champs depuis $fields (JSON du CMS admin)
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
$advisorPhone   = $advisor['phone']   ?? '';

// ────────────────────────────────────────────────────
// CHAMPS HERO
// ────────────────────────────────────────────────────
$heroEyebrow  = $fields['hero_eyebrow']  ?? '';
$heroTitle    = $fields['hero_title']     ?? ($page['title'] ?? 'Page');
$heroSubtitle = $fields['hero_subtitle']  ?? '';
$heroCtaText  = $fields['hero_cta_text']  ?? '';
$heroCtaUrl   = $fields['hero_cta_url']   ?? '/contact';
$heroCta2Text = $fields['hero_cta2_text'] ?? '';
$heroCta2Url  = $fields['hero_cta2_url']  ?? '#';

// ── Stats ──
$heroStat1Num = $fields['hero_stat1_num'] ?? '';
$heroStat1Lbl = $fields['hero_stat1_lbl'] ?? '';
$heroStat2Num = $fields['hero_stat2_num'] ?? '';
$heroStat2Lbl = $fields['hero_stat2_lbl'] ?? '';
$heroStat3Num = $fields['hero_stat3_num'] ?? '';
$heroStat3Lbl = $fields['hero_stat3_lbl'] ?? '';

// ── Arguments / Boxes ──
$box1Icon  = $fields['box1_icon']  ?? '';
$box1Title = $fields['box1_title'] ?? '';
$box1Text  = $fields['box1_text']  ?? '';
$box2Icon  = $fields['box2_icon']  ?? '';
$box2Title = $fields['box2_title'] ?? '';
$box2Text  = $fields['box2_text']  ?? '';
$box3Icon  = $fields['box3_icon']  ?? '';
$box3Title = $fields['box3_title'] ?? '';
$box3Text  = $fields['box3_text']  ?? '';

// ── Benefices ──
$benTitle  = $fields['ben_title']  ?? '';
$ben1Icon  = $fields['ben1_icon']  ?? '';
$ben1Title = $fields['ben1_title'] ?? '';
$ben1Text  = $fields['ben1_text']  ?? '';
$ben2Icon  = $fields['ben2_icon']  ?? '';
$ben2Title = $fields['ben2_title'] ?? '';
$ben2Text  = $fields['ben2_text']  ?? '';
$ben3Icon  = $fields['ben3_icon']  ?? '';
$ben3Title = $fields['ben3_title'] ?? '';
$ben3Text  = $fields['ben3_text']  ?? '';

// ── Problemes ──
$pbTitle  = $fields['pb_title']  ?? '';
$pb1Title = $fields['pb1_title'] ?? '';
$pb1Text  = $fields['pb1_text']  ?? '';
$pb2Title = $fields['pb2_title'] ?? '';
$pb2Text  = $fields['pb2_text']  ?? '';
$pb3Title = $fields['pb3_title'] ?? '';
$pb3Text  = $fields['pb3_text']  ?? '';

// ── Autorite ──
$authBadge  = $fields['auth_badge']  ?? '';
$authTitle  = $fields['auth_title']  ?? '';
$authSub    = $fields['auth_sub']    ?? '';
$auth1Icon  = $fields['auth1_icon']  ?? '';
$auth1Title = $fields['auth1_title'] ?? '';
$auth1Text  = $fields['auth1_text']  ?? '';
$auth2Icon  = $fields['auth2_icon']  ?? '';
$auth2Title = $fields['auth2_title'] ?? '';
$auth2Text  = $fields['auth2_text']  ?? '';
$auth3Icon  = $fields['auth3_icon']  ?? '';
$auth3Title = $fields['auth3_title'] ?? '';
$auth3Text  = $fields['auth3_text']  ?? '';

// ── Methode ──
$methodTitle   = $fields['method_title']    ?? '';
$step1Num      = $fields['step1_num']       ?? '01';
$step1Title    = $fields['step1_title']     ?? '';
$step1Text     = $fields['step1_text']      ?? '';
$step2Num      = $fields['step2_num']       ?? '02';
$step2Title    = $fields['step2_title']     ?? '';
$step2Text     = $fields['step2_text']      ?? '';
$step3Num      = $fields['step3_num']       ?? '03';
$step3Title    = $fields['step3_title']     ?? '';
$step3Text     = $fields['step3_text']      ?? '';
$methodCtaText = $fields['method_cta_text'] ?? '';
$methodCtaUrl  = $fields['method_cta_url']  ?? '/contact';

// ── Presentation ──
$presTitle   = $fields['pres_title']    ?? '';
$presSub     = $fields['pres_sub']      ?? '';
$presText    = $fields['pres_text']     ?? '';
$presTag1    = $fields['pres_tag1']     ?? '';
$presTag2    = $fields['pres_tag2']     ?? '';
$presTag3    = $fields['pres_tag3']     ?? '';
$presCtaText = $fields['pres_cta_text'] ?? '';
$presCtaUrl  = $fields['pres_cta_url']  ?? '#';

// ── Guide SEO ──
$guideTitle = $fields['guide_title'] ?? '';
$g1Num   = $fields['g1_num']   ?? '01';
$g1Title = $fields['g1_title'] ?? '';
$g1Text  = $fields['g1_text']  ?? '';
$g2Num   = $fields['g2_num']   ?? '02';
$g2Title = $fields['g2_title'] ?? '';
$g2Text  = $fields['g2_text']  ?? '';
$g3Num   = $fields['g3_num']   ?? '03';
$g3Title = $fields['g3_title'] ?? '';
$g3Text  = $fields['g3_text']  ?? '';

// ── Body content (standard template) ──
$bodyContent = $fields['body_content'] ?? '';

// ── CTA Finale ──
$ctaTitle     = $fields['cta_title']      ?? '';
$ctaText      = $fields['cta_text']       ?? $fields['cta_desc'] ?? '';
$ctaBtnText   = $fields['cta_btn_text']   ?? '';
$ctaBtnUrl    = $fields['cta_btn_url']    ?? '/contact';
$ctaPhoneText = $fields['cta_phone_text'] ?? '';

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

<!-- HERO -->
<section class="tp-hero">
    <div class="tp-hero-inner" style="text-align:center">
        <?php if ($heroEyebrow): ?><div class="tp-eyebrow"><?= htmlspecialchars($heroEyebrow) ?></div><?php endif; ?>
        <h1 class="tp-hero-h1" style="margin-left:auto;margin-right:auto"><?= htmlspecialchars($heroTitle) ?></h1>
        <?php if ($heroSubtitle): ?><p class="tp-hero-sub" style="margin-left:auto;margin-right:auto"><?= $heroSubtitle ?></p><?php endif; ?>
        <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap;margin-top:32px">
            <?php if ($heroCtaText): ?><a href="<?= htmlspecialchars($heroCtaUrl) ?>" class="tp-hero-cta"><?= htmlspecialchars($heroCtaText) ?></a><?php endif; ?>
            <?php if ($heroCta2Text): ?><a href="<?= htmlspecialchars($heroCta2Url) ?>" class="tp-cta-btn-outline"><?= htmlspecialchars($heroCta2Text) ?></a><?php endif; ?>
        </div>
    </div>
</section>

<?php
// Stats row
$stats = [];
if ($heroStat1Num) $stats[] = ['num' => $heroStat1Num, 'lbl' => $heroStat1Lbl];
if ($heroStat2Num) $stats[] = ['num' => $heroStat2Num, 'lbl' => $heroStat2Lbl];
if ($heroStat3Num) $stats[] = ['num' => $heroStat3Num, 'lbl' => $heroStat3Lbl];
if ($stats):
?>
<div class="tp-stats-row" style="grid-template-columns:repeat(<?= count($stats) ?>,1fr)">
    <?php foreach ($stats as $s): ?>
    <div class="tp-stat">
        <div class="tp-stat-num"><?= htmlspecialchars($s['num']) ?></div>
        <div class="tp-stat-lbl"><?= htmlspecialchars($s['lbl']) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
// 3 Boxes / Arguments
$boxes = [];
if ($box1Title) $boxes[] = ['icon' => $box1Icon, 'title' => $box1Title, 'text' => $box1Text];
if ($box2Title) $boxes[] = ['icon' => $box2Icon, 'title' => $box2Title, 'text' => $box2Text];
if ($box3Title) $boxes[] = ['icon' => $box3Icon, 'title' => $box3Title, 'text' => $box3Text];
if ($boxes):
?>
<section class="tp-section-light">
    <div class="tp-container">
        <div class="tp-grid-3">
            <?php foreach ($boxes as $b): ?>
            <div class="tp-card" style="text-align:center">
                <?php if ($b['icon']): ?><div style="font-size:2rem;margin-bottom:14px"><?= $b['icon'] ?></div><?php endif; ?>
                <h3 style="font-family:var(--tp-ff-display);font-size:1.1rem;font-weight:800;color:var(--tp-primary);margin-bottom:10px"><?= htmlspecialchars($b['title']) ?></h3>
                <p style="color:var(--tp-text2);font-size:.9rem;line-height:1.7"><?= $b['text'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Problemes / Douleurs
$pbs = [];
if ($pb1Title) $pbs[] = ['title' => $pb1Title, 'text' => $pb1Text];
if ($pb2Title) $pbs[] = ['title' => $pb2Title, 'text' => $pb2Text];
if ($pb3Title) $pbs[] = ['title' => $pb3Title, 'text' => $pb3Text];
if ($pbTitle || $pbs):
?>
<section class="tp-section-white">
    <div class="tp-container">
        <?php if ($pbTitle): ?><h2 class="tp-section-title"><?= htmlspecialchars($pbTitle) ?></h2><?php endif; ?>
        <div class="tp-grid-3">
            <?php foreach ($pbs as $pb): ?>
            <div class="tp-card" style="border-left:3px solid var(--tp-red)">
                <h3 style="font-family:var(--tp-ff-display);font-size:1.05rem;font-weight:800;color:var(--tp-primary);margin-bottom:10px"><?= htmlspecialchars($pb['title']) ?></h3>
                <p style="color:var(--tp-text2);font-size:.88rem;line-height:1.7"><?= $pb['text'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Benefices
$bens = [];
if ($ben1Title) $bens[] = ['icon' => $ben1Icon, 'title' => $ben1Title, 'text' => $ben1Text];
if ($ben2Title) $bens[] = ['icon' => $ben2Icon, 'title' => $ben2Title, 'text' => $ben2Text];
if ($ben3Title) $bens[] = ['icon' => $ben3Icon, 'title' => $ben3Title, 'text' => $ben3Text];
if ($benTitle || $bens):
?>
<section class="tp-section-light">
    <div class="tp-container">
        <?php if ($benTitle): ?><h2 class="tp-section-title"><?= htmlspecialchars($benTitle) ?></h2><?php endif; ?>
        <div class="tp-grid-3">
            <?php foreach ($bens as $ben): ?>
            <div class="tp-card" style="text-align:center">
                <?php if ($ben['icon']): ?><div style="font-size:2rem;margin-bottom:14px"><?= $ben['icon'] ?></div><?php endif; ?>
                <h3 style="font-family:var(--tp-ff-display);font-size:1.1rem;font-weight:800;color:var(--tp-primary);margin-bottom:10px"><?= htmlspecialchars($ben['title']) ?></h3>
                <p style="color:var(--tp-text2);font-size:.88rem;line-height:1.7"><?= $ben['text'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Autorite / Chiffres
$auths = [];
if ($auth1Title) $auths[] = ['icon' => $auth1Icon, 'title' => $auth1Title, 'text' => $auth1Text];
if ($auth2Title) $auths[] = ['icon' => $auth2Icon, 'title' => $auth2Title, 'text' => $auth2Text];
if ($auth3Title) $auths[] = ['icon' => $auth3Icon, 'title' => $auth3Title, 'text' => $auth3Text];
if ($authTitle || $auths):
?>
<section class="tp-section-white">
    <div class="tp-container" style="text-align:center">
        <?php if ($authBadge): ?><div class="tp-section-badge"><?= htmlspecialchars($authBadge) ?></div><?php endif; ?>
        <?php if ($authTitle): ?><h2 class="tp-section-title"><?= htmlspecialchars($authTitle) ?></h2><?php endif; ?>
        <?php if ($authSub): ?><p style="color:var(--tp-text2);max-width:600px;margin:0 auto 40px;line-height:1.7"><?= $authSub ?></p><?php endif; ?>
        <div class="tp-grid-3">
            <?php foreach ($auths as $a): ?>
            <div class="tp-card" style="text-align:center">
                <?php if ($a['icon']): ?><div style="font-size:2rem;margin-bottom:14px"><?= $a['icon'] ?></div><?php endif; ?>
                <h3 style="font-family:var(--tp-ff-display);font-size:1.1rem;font-weight:800;color:var(--tp-primary);margin-bottom:10px"><?= htmlspecialchars($a['title']) ?></h3>
                <p style="color:var(--tp-text2);font-size:.88rem;line-height:1.7"><?= $a['text'] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Presentation conseiller
if ($presTitle || $presText):
?>
<section class="tp-section-light">
    <div class="tp-container">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center">
            <div>
                <?php if ($presTitle): ?><h2 style="font-family:var(--tp-ff-display);font-size:clamp(1.5rem,3vw,2rem);font-weight:800;color:var(--tp-primary);margin-bottom:12px"><?= htmlspecialchars($presTitle) ?></h2><?php endif; ?>
                <?php if ($presSub): ?><p style="color:var(--tp-text2);margin-bottom:16px;font-size:.95rem"><?= htmlspecialchars($presSub) ?></p><?php endif; ?>
                <?php if ($presText): ?><div class="tp-rich-body"><?= $presText ?></div><?php endif; ?>
                <?php
                $tags = array_filter([$presTag1, $presTag2, $presTag3]);
                if ($tags):
                ?>
                <div class="tp-tags-row">
                    <?php foreach ($tags as $tag): ?><span class="tp-tag-chip"><?= htmlspecialchars($tag) ?></span><?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if ($presCtaText): ?><a href="<?= htmlspecialchars($presCtaUrl) ?>" class="tp-btn-primary"><?= htmlspecialchars($presCtaText) ?></a><?php endif; ?>
            </div>
            <div style="background:var(--tp-bg2);border-radius:var(--tp-radius);min-height:320px;display:flex;align-items:center;justify-content:center;color:var(--tp-text3);font-size:.9rem">
                <i class="fas fa-user-tie" style="font-size:4rem;opacity:.15"></i>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Methode / Process
$steps = [];
if ($step1Title) $steps[] = ['num' => $step1Num, 'title' => $step1Title, 'text' => $step1Text];
if ($step2Title) $steps[] = ['num' => $step2Num, 'title' => $step2Title, 'text' => $step2Text];
if ($step3Title) $steps[] = ['num' => $step3Num, 'title' => $step3Title, 'text' => $step3Text];
if ($methodTitle || $steps):
?>
<section class="tp-section-white">
    <div class="tp-container" style="text-align:center">
        <div class="tp-section-badge">La methode</div>
        <?php if ($methodTitle): ?><h2 class="tp-section-title"><?= htmlspecialchars($methodTitle) ?></h2><?php endif; ?>
        <div class="tp-steps">
            <?php foreach ($steps as $st): ?>
            <div class="tp-step">
                <div class="tp-step-num"><?= htmlspecialchars($st['num']) ?></div>
                <div class="tp-step-title"><?= htmlspecialchars($st['title']) ?></div>
                <div class="tp-step-text"><?= $st['text'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if ($methodCtaText): ?><a href="<?= htmlspecialchars($methodCtaUrl) ?>" class="tp-btn-gold"><?= htmlspecialchars($methodCtaText) ?></a><?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php
// Guide SEO
$guides = [];
if ($g1Title) $guides[] = ['num' => $g1Num, 'title' => $g1Title, 'text' => $g1Text];
if ($g2Title) $guides[] = ['num' => $g2Num, 'title' => $g2Title, 'text' => $g2Text];
if ($g3Title) $guides[] = ['num' => $g3Num, 'title' => $g3Title, 'text' => $g3Text];
if ($guideTitle || $guides):
?>
<section class="tp-section-light">
    <div class="tp-container-sm">
        <?php if ($guideTitle): ?><h2 class="tp-section-title"><?= htmlspecialchars($guideTitle) ?></h2><?php endif; ?>
        <div style="display:flex;flex-direction:column;gap:24px">
            <?php foreach ($guides as $g): ?>
            <div class="tp-guide-item">
                <div class="tp-guide-num"><?= htmlspecialchars($g['num']) ?></div>
                <div>
                    <div class="tp-guide-h3"><?= htmlspecialchars($g['title']) ?></div>
                    <div class="tp-guide-body"><?= $g['text'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Body content (standard / generic)
if ($bodyContent):
?>
<section class="tp-section-white">
    <div class="tp-container-sm">
        <div class="tp-rich-body"><?= $bodyContent ?></div>
    </div>
</section>
<?php endif; ?>

<?php
// CTA Finale
if ($ctaTitle || $ctaBtnText):
?>
<section class="tp-cta-section">
    <div class="tp-container" style="text-align:center">
        <?php if ($ctaTitle): ?><div class="tp-cta-title"><?= htmlspecialchars($ctaTitle) ?></div><?php endif; ?>
        <?php if ($ctaText): ?><div class="tp-cta-text"><?= $ctaText ?></div><?php endif; ?>
        <?php if ($ctaBtnText): ?><a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="tp-cta-btn"><?= htmlspecialchars($ctaBtnText) ?></a><?php endif; ?>
        <?php if ($ctaPhoneText): ?><p style="margin-top:16px;color:rgba(255,255,255,.6);font-size:.88rem"><?= htmlspecialchars($ctaPhoneText) ?></p><?php endif; ?>
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
