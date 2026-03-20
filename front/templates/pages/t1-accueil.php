<?php
/**
 * /front/templates/pages/t1-accueil.php
 * Template Accueil — heroPositionnement + offre signature
 * Géré par CMS admin (contenu éditable via $fields)
 * Design créé dans systeme/template Accueil/t1
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;
$siteUrl    = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

$advisorName    = $advisor['name']    ?? ($site['name']    ?? 'Votre conseiller');
$advisorCity    = $advisor['city']    ?? ($site['city']    ?? 'votre ville');
$advisorNetwork = $advisor['network'] ?? 'eXp France';
$advisorPhone   = $advisor['phone']   ?? '';

require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

// ────────────────────────────────────────────────────
// CHAMPS HERO
// ────────────────────────────────────────────────────
$heroEyebrow  = $fields['hero_eyebrow']  ?? 'Conseiller immobilier à ' . $advisorCity;
$heroTitle    = $fields['hero_title']     ?? 'Votre projet immobilier à ' . $advisorCity . ', accompagné par ' . $advisorName;
$heroSubtitle = $fields['hero_subtitle']  ?? 'Estimation gratuite, accompagnement personnalisé et résultat garanti avec le réseau ' . $advisorNetwork . '.';
$heroCtaText  = $fields['hero_cta_text']  ?? 'Demander mon estimation gratuite';
$heroCtaUrl   = $fields['hero_cta_url']   ?? _findMenuUrl($headerMenu['items'] ?? [], 'Estimation', $siteUrl . '/estimation');
$heroCta2Text = $fields['hero_cta2_text'] ?? 'Me contacter';
$heroCta2Url  = $fields['hero_cta2_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');

// ── Stats ──
$heroStat1Num = $fields['hero_stat1_num'] ?? '98%';
$heroStat1Lbl = $fields['hero_stat1_lbl'] ?? 'clients satisfaits';
$heroStat2Num = $fields['hero_stat2_num'] ?? '45j';
$heroStat2Lbl = $fields['hero_stat2_lbl'] ?? 'délai moyen de vente';
$heroStat3Num = $fields['hero_stat3_num'] ?? '15+';
$heroStat3Lbl = $fields['hero_stat3_lbl'] ?? 'ans d\'expérience';

// ── Bénéfices ──
$benTitle  = $fields['ben_title']  ?? 'Pourquoi me faire confiance ?';
$ben1Icon  = $fields['ben1_icon']  ?? '📍';
$ben1Title = $fields['ben1_title'] ?? 'Expertise locale';
$ben1Text  = $fields['ben1_text']  ?? 'Connaissance approfondie du marché immobilier à ' . $advisorCity . ' et ses environs.';
$ben2Icon  = $fields['ben2_icon']  ?? '🎯';
$ben2Title = $fields['ben2_title'] ?? 'Accompagnement sur-mesure';
$ben2Text  = $fields['ben2_text']  ?? 'Un interlocuteur unique qui vous guide à chaque étape de votre projet.';
$ben3Icon  = $fields['ben3_icon']  ?? '🛡️';
$ben3Title = $fields['ben3_title'] ?? 'Réseau ' . $advisorNetwork;
$ben3Text  = $fields['ben3_text']  ?? 'La force d\'un réseau international au service de votre projet local.';

// ── Présentation conseiller ──
$presTitle   = $fields['pres_title']    ?? 'Votre conseiller à ' . $advisorCity;
$presSub     = $fields['pres_sub']      ?? $advisorName . ' — ' . $advisorNetwork;
$presText    = $fields['pres_text']     ?? '<p>Passionné(e) par l\'immobilier et ancré(e) sur le territoire, je mets mon expertise et mon énergie au service de votre projet. Vente, achat ou investissement : je vous accompagne avec transparence et engagement.</p>';
$presTag1    = $fields['pres_tag1']     ?? '✓ Conseiller certifié';
$presTag2    = $fields['pres_tag2']     ?? '✓ Réseau ' . $advisorNetwork;
$presTag3    = $fields['pres_tag3']     ?? '✓ Avis clients 5/5';
$presCtaText = $fields['pres_cta_text'] ?? 'En savoir plus';
$presCtaUrl  = $fields['pres_cta_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'propos', $siteUrl . '/a-propos');

// ── Expertise ──
$expTitle  = $fields['exp_title']  ?? 'Mon expertise à votre service';
$exp1Icon  = $fields['exp1_icon']  ?? '🏡';
$exp1Title = $fields['exp1_title'] ?? 'Vendre';
$exp1Text  = $fields['exp1_text']  ?? 'Estimation précise, stratégie de diffusion optimale et négociation au meilleur prix.';
$exp1Link  = $fields['exp1_link']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Vendre', $siteUrl . '/vendre');
$exp2Icon  = $fields['exp2_icon']  ?? '🔑';
$exp2Title = $fields['exp2_title'] ?? 'Acheter';
$exp2Text  = $fields['exp2_text']  ?? 'Recherche ciblée, visites qualifiées et accompagnement jusqu\'à la signature.';
$exp2Link  = $fields['exp2_link']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Acheter', $siteUrl . '/acheter');
$exp3Icon  = $fields['exp3_icon']  ?? '📈';
$exp3Title = $fields['exp3_title'] ?? 'Investir';
$exp3Text  = $fields['exp3_text']  ?? 'Analyse de rentabilité, sélection des meilleurs biens et optimisation fiscale.';
$exp3Link  = $fields['exp3_link']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Investir', $siteUrl . '/investir');

// ── Méthode ──
$methodTitle   = $fields['method_title']    ?? 'Mon accompagnement en 3 étapes';
$step1Num      = $fields['step1_num']       ?? '01';
$step1Title    = $fields['step1_title']     ?? 'Écoute & conseil';
$step1Text     = $fields['step1_text']      ?? 'Je prends le temps de comprendre votre projet, vos attentes et vos contraintes.';
$step2Num      = $fields['step2_num']       ?? '02';
$step2Title    = $fields['step2_title']     ?? 'Stratégie & action';
$step2Text     = $fields['step2_text']      ?? 'Plan d\'action personnalisé, mise en valeur de votre bien et recherche active.';
$step3Num      = $fields['step3_num']       ?? '03';
$step3Title    = $fields['step3_title']     ?? 'Négociation & signature';
$step3Text     = $fields['step3_text']      ?? 'Négociation du meilleur prix et accompagnement sécurisé jusqu\'à la signature.';
$methodCtaText = $fields['method_cta_text'] ?? 'Prendre rendez-vous';
$methodCtaUrl  = $fields['method_cta_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');

// ── Guide SEO ──
$guideTitle = $fields['guide_title'] ?? 'Guide immobilier à ' . $advisorCity;
$g1Num      = $fields['g1_num']   ?? '01';
$g1Title    = $fields['g1_title'] ?? 'Le marché immobilier à ' . $advisorCity;
$g1Text     = $fields['g1_text']  ?? '<p>Découvrez les tendances du marché, les prix au m² et les quartiers les plus recherchés de ' . htmlspecialchars($advisorCity) . '.</p>';
$g2Num      = $fields['g2_num']   ?? '02';
$g2Title    = $fields['g2_title'] ?? 'Comment vendre au meilleur prix ?';
$g2Text     = $fields['g2_text']  ?? '<p>Les étapes clés pour maximiser la valeur de votre bien : diagnostics, home staging, stratégie de diffusion.</p>';
$g3Num      = $fields['g3_num']   ?? '03';
$g3Title    = $fields['g3_title'] ?? 'Acheter sereinement';
$g3Text     = $fields['g3_text']  ?? '<p>Financement, compromis, acte authentique : tout ce qu\'il faut savoir pour sécuriser votre achat immobilier.</p>';

// ── CTA Finale ──
$ctaTitle     = $fields['cta_title']      ?? 'Parlons de votre projet';
$ctaText      = $fields['cta_text']       ?? 'Estimation gratuite, conseil personnalisé, accompagnement complet — tout commence par un échange.';
$ctaBtnText   = $fields['cta_btn_text']   ?? 'Demander mon estimation gratuite';
$ctaBtnUrl    = $fields['cta_btn_url']    ?? _findMenuUrl($headerMenu['items'] ?? [], 'Estimation', $siteUrl . '/estimation');
$ctaPhoneText = $fields['cta_phone_text'] ?? 'Ou appelez-moi directement';

// ── Meta ──
$pageTitle       = $fields['hero_title'] ?? 'Immobilier à ' . $advisorCity . ' | ' . $advisorName;
$pageDescription = $fields['hero_subtitle'] ?? '';

ob_start();
require_once __DIR__ . '/_tpl-common.php';
?>

<!-- ═══════════════════════════════════════════════════
     HERO — POSITIONNEMENT
     ═══════════════════════════════════════════════════ -->
<section class="tp-hero" aria-label="Présentation">
    <div class="tp-hero-inner">
        <span class="tp-eyebrow" <?= $editMode ? 'data-field="hero_eyebrow" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroEyebrow) ?>
        </span>

        <h1 class="tp-hero-h1" <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroTitle) ?>
        </h1>

        <p class="tp-hero-sub" <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroSubtitle) ?>
        </p>

        <div style="display:flex;gap:14px;flex-wrap:wrap;margin-bottom:44px">
            <a href="<?= htmlspecialchars($heroCtaUrl) ?>" class="tp-hero-cta" <?= $editMode ? 'data-field="hero_cta_text" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($heroCtaText) ?>
            </a>
            <?php if ($heroCta2Text): ?>
            <a href="<?= htmlspecialchars($heroCta2Url) ?>" class="tp-cta-btn-outline" <?= $editMode ? 'data-field="hero_cta2_text" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($heroCta2Text) ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     STATS (3 chiffres)
     ═══════════════════════════════════════════════════ -->
<div class="tp-stats-row" style="grid-template-columns:repeat(3,1fr)">
    <div class="tp-stat">
        <div class="tp-stat-num" <?= $editMode ? 'data-field="hero_stat1_num" class="ef-zone"' : '' ?>><?= htmlspecialchars($heroStat1Num) ?></div>
        <div class="tp-stat-lbl" <?= $editMode ? 'data-field="hero_stat1_lbl" class="ef-zone"' : '' ?>><?= htmlspecialchars($heroStat1Lbl) ?></div>
    </div>
    <div class="tp-stat">
        <div class="tp-stat-num" <?= $editMode ? 'data-field="hero_stat2_num" class="ef-zone"' : '' ?>><?= htmlspecialchars($heroStat2Num) ?></div>
        <div class="tp-stat-lbl" <?= $editMode ? 'data-field="hero_stat2_lbl" class="ef-zone"' : '' ?>><?= htmlspecialchars($heroStat2Lbl) ?></div>
    </div>
    <div class="tp-stat">
        <div class="tp-stat-num" <?= $editMode ? 'data-field="hero_stat3_num" class="ef-zone"' : '' ?>><?= htmlspecialchars($heroStat3Num) ?></div>
        <div class="tp-stat-lbl" <?= $editMode ? 'data-field="hero_stat3_lbl" class="ef-zone"' : '' ?>><?= htmlspecialchars($heroStat3Lbl) ?></div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════
     BÉNÉFICES (3 colonnes)
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-light" aria-label="Bénéfices">
    <div class="tp-container">
        <h2 class="tp-section-title" <?= $editMode ? 'data-field="ben_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($benTitle) ?>
        </h2>
        <div class="tp-grid-3">
            <div class="tp-card" style="text-align:center">
                <div style="font-size:2.2rem;margin-bottom:16px" <?= $editMode ? 'data-field="ben1_icon" class="ef-zone"' : '' ?>><?= $ben1Icon ?></div>
                <h3 style="font-weight:800;color:var(--tp-primary);margin-bottom:10px" <?= $editMode ? 'data-field="ben1_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($ben1Title) ?></h3>
                <p style="font-size:.88rem;color:var(--tp-text2);line-height:1.7" <?= $editMode ? 'data-field="ben1_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($ben1Text) ?></p>
            </div>
            <div class="tp-card" style="text-align:center">
                <div style="font-size:2.2rem;margin-bottom:16px" <?= $editMode ? 'data-field="ben2_icon" class="ef-zone"' : '' ?>><?= $ben2Icon ?></div>
                <h3 style="font-weight:800;color:var(--tp-primary);margin-bottom:10px" <?= $editMode ? 'data-field="ben2_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($ben2Title) ?></h3>
                <p style="font-size:.88rem;color:var(--tp-text2);line-height:1.7" <?= $editMode ? 'data-field="ben2_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($ben2Text) ?></p>
            </div>
            <div class="tp-card" style="text-align:center">
                <div style="font-size:2.2rem;margin-bottom:16px" <?= $editMode ? 'data-field="ben3_icon" class="ef-zone"' : '' ?>><?= $ben3Icon ?></div>
                <h3 style="font-weight:800;color:var(--tp-primary);margin-bottom:10px" <?= $editMode ? 'data-field="ben3_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($ben3Title) ?></h3>
                <p style="font-size:.88rem;color:var(--tp-text2);line-height:1.7" <?= $editMode ? 'data-field="ben3_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($ben3Text) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     PRÉSENTATION CONSEILLER — OFFRE SIGNATURE
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-white" aria-label="Présentation conseiller">
    <div class="tp-container">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center">
            <div>
                <span class="tp-section-badge" <?= $editMode ? 'data-field="pres_sub" class="ef-zone"' : '' ?>><?= htmlspecialchars($presSub) ?></span>
                <h2 style="font-family:var(--tp-ff-display);font-size:clamp(1.5rem,3vw,2.2rem);font-weight:800;color:var(--tp-primary);margin:0 0 20px;letter-spacing:-.02em" <?= $editMode ? 'data-field="pres_title" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($presTitle) ?>
                </h2>
                <div class="tp-rich-body" <?= $editMode ? 'data-field="pres_text" class="ef-zone ef-rich"' : '' ?>>
                    <?= $presText ?>
                </div>
                <div class="tp-tags-row">
                    <?php if ($presTag1): ?><span class="tp-tag-chip" <?= $editMode ? 'data-field="pres_tag1" class="ef-zone"' : '' ?>><?= htmlspecialchars($presTag1) ?></span><?php endif; ?>
                    <?php if ($presTag2): ?><span class="tp-tag-chip" <?= $editMode ? 'data-field="pres_tag2" class="ef-zone"' : '' ?>><?= htmlspecialchars($presTag2) ?></span><?php endif; ?>
                    <?php if ($presTag3): ?><span class="tp-tag-chip" <?= $editMode ? 'data-field="pres_tag3" class="ef-zone"' : '' ?>><?= htmlspecialchars($presTag3) ?></span><?php endif; ?>
                </div>
                <a href="<?= htmlspecialchars($presCtaUrl) ?>" class="tp-btn-primary" <?= $editMode ? 'data-field="pres_cta_text" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($presCtaText) ?>
                </a>
            </div>
            <div style="background:var(--tp-bg);border-radius:var(--tp-radius);padding:48px;text-align:center;border:1px solid var(--tp-border)">
                <div style="width:120px;height:120px;border-radius:50%;background:var(--tp-primary);margin:0 auto 24px;display:flex;align-items:center;justify-content:center">
                    <span style="font-size:3rem;color:var(--tp-white);font-family:var(--tp-ff-display);font-weight:800"><?= mb_substr($advisorName, 0, 1) ?></span>
                </div>
                <h3 style="font-family:var(--tp-ff-display);font-size:1.3rem;font-weight:800;color:var(--tp-primary);margin-bottom:8px"><?= htmlspecialchars($advisorName) ?></h3>
                <p style="font-size:.85rem;color:var(--tp-text2);margin-bottom:16px"><?= htmlspecialchars($advisorNetwork) ?> &mdash; <?= htmlspecialchars($advisorCity) ?></p>
                <?php if ($advisorPhone): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $advisorPhone)) ?>" class="tp-btn-gold" style="font-size:.85rem">
                    <?= htmlspecialchars($advisorPhone) ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     EXPERTISE (3 piliers) — OFFRE SIGNATURE
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-light" aria-label="Expertise">
    <div class="tp-container">
        <h2 class="tp-section-title" <?= $editMode ? 'data-field="exp_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($expTitle) ?>
        </h2>
        <div class="tp-grid-3">
            <a href="<?= htmlspecialchars($exp1Link) ?>" class="tp-card" style="text-align:center;text-decoration:none">
                <div style="font-size:2.4rem;margin-bottom:16px" <?= $editMode ? 'data-field="exp1_icon" class="ef-zone"' : '' ?>><?= $exp1Icon ?></div>
                <h3 style="font-family:var(--tp-ff-display);font-weight:800;color:var(--tp-primary);margin-bottom:10px;font-size:1.15rem" <?= $editMode ? 'data-field="exp1_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($exp1Title) ?></h3>
                <p style="font-size:.85rem;color:var(--tp-text2);line-height:1.7;margin-bottom:16px" <?= $editMode ? 'data-field="exp1_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($exp1Text) ?></p>
                <span style="font-size:.8rem;font-weight:700;color:var(--tp-accent-d)">En savoir plus &rarr;</span>
            </a>
            <a href="<?= htmlspecialchars($exp2Link) ?>" class="tp-card" style="text-align:center;text-decoration:none">
                <div style="font-size:2.4rem;margin-bottom:16px" <?= $editMode ? 'data-field="exp2_icon" class="ef-zone"' : '' ?>><?= $exp2Icon ?></div>
                <h3 style="font-family:var(--tp-ff-display);font-weight:800;color:var(--tp-primary);margin-bottom:10px;font-size:1.15rem" <?= $editMode ? 'data-field="exp2_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($exp2Title) ?></h3>
                <p style="font-size:.85rem;color:var(--tp-text2);line-height:1.7;margin-bottom:16px" <?= $editMode ? 'data-field="exp2_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($exp2Text) ?></p>
                <span style="font-size:.8rem;font-weight:700;color:var(--tp-accent-d)">En savoir plus &rarr;</span>
            </a>
            <a href="<?= htmlspecialchars($exp3Link) ?>" class="tp-card" style="text-align:center;text-decoration:none">
                <div style="font-size:2.4rem;margin-bottom:16px" <?= $editMode ? 'data-field="exp3_icon" class="ef-zone"' : '' ?>><?= $exp3Icon ?></div>
                <h3 style="font-family:var(--tp-ff-display);font-weight:800;color:var(--tp-primary);margin-bottom:10px;font-size:1.15rem" <?= $editMode ? 'data-field="exp3_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($exp3Title) ?></h3>
                <p style="font-size:.85rem;color:var(--tp-text2);line-height:1.7;margin-bottom:16px" <?= $editMode ? 'data-field="exp3_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($exp3Text) ?></p>
                <span style="font-size:.8rem;font-weight:700;color:var(--tp-accent-d)">En savoir plus &rarr;</span>
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     MÉTHODE EN 3 ÉTAPES
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-white" aria-label="Méthode">
    <div class="tp-container">
        <span class="tp-section-badge" style="display:block;text-align:center">La méthode</span>
        <h2 class="tp-section-title" <?= $editMode ? 'data-field="method_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($methodTitle) ?>
        </h2>
        <div class="tp-steps">
            <div class="tp-step">
                <div class="tp-step-num" <?= $editMode ? 'data-field="step1_num" class="ef-zone"' : '' ?>><?= htmlspecialchars($step1Num) ?></div>
                <div class="tp-step-title" <?= $editMode ? 'data-field="step1_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($step1Title) ?></div>
                <div class="tp-step-text" <?= $editMode ? 'data-field="step1_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($step1Text) ?></div>
            </div>
            <div class="tp-step">
                <div class="tp-step-num" <?= $editMode ? 'data-field="step2_num" class="ef-zone"' : '' ?>><?= htmlspecialchars($step2Num) ?></div>
                <div class="tp-step-title" <?= $editMode ? 'data-field="step2_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($step2Title) ?></div>
                <div class="tp-step-text" <?= $editMode ? 'data-field="step2_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($step2Text) ?></div>
            </div>
            <div class="tp-step">
                <div class="tp-step-num" <?= $editMode ? 'data-field="step3_num" class="ef-zone"' : '' ?>><?= htmlspecialchars($step3Num) ?></div>
                <div class="tp-step-title" <?= $editMode ? 'data-field="step3_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($step3Title) ?></div>
                <div class="tp-step-text" <?= $editMode ? 'data-field="step3_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($step3Text) ?></div>
            </div>
        </div>
        <div style="text-align:center">
            <a href="<?= htmlspecialchars($methodCtaUrl) ?>" class="tp-btn-gold" <?= $editMode ? 'data-field="method_cta_text" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($methodCtaText) ?>
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     GUIDE SEO
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-light" aria-label="Guide immobilier">
    <div class="tp-container">
        <span class="tp-section-badge" style="display:block;text-align:center">Guide pratique</span>
        <h2 class="tp-section-title" <?= $editMode ? 'data-field="guide_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($guideTitle) ?>
        </h2>
        <div style="display:flex;flex-direction:column;gap:24px">
            <div class="tp-guide-item">
                <div class="tp-guide-num" <?= $editMode ? 'data-field="g1_num" class="ef-zone"' : '' ?>><?= htmlspecialchars($g1Num) ?></div>
                <div>
                    <h3 class="tp-guide-h3" <?= $editMode ? 'data-field="g1_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($g1Title) ?></h3>
                    <div class="tp-guide-body" <?= $editMode ? 'data-field="g1_text" class="ef-zone ef-rich"' : '' ?>><?= $g1Text ?></div>
                </div>
            </div>
            <div class="tp-guide-item">
                <div class="tp-guide-num" <?= $editMode ? 'data-field="g2_num" class="ef-zone"' : '' ?>><?= htmlspecialchars($g2Num) ?></div>
                <div>
                    <h3 class="tp-guide-h3" <?= $editMode ? 'data-field="g2_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($g2Title) ?></h3>
                    <div class="tp-guide-body" <?= $editMode ? 'data-field="g2_text" class="ef-zone ef-rich"' : '' ?>><?= $g2Text ?></div>
                </div>
            </div>
            <div class="tp-guide-item">
                <div class="tp-guide-num" <?= $editMode ? 'data-field="g3_num" class="ef-zone"' : '' ?>><?= htmlspecialchars($g3Num) ?></div>
                <div>
                    <h3 class="tp-guide-h3" <?= $editMode ? 'data-field="g3_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($g3Title) ?></h3>
                    <div class="tp-guide-body" <?= $editMode ? 'data-field="g3_text" class="ef-zone ef-rich"' : '' ?>><?= $g3Text ?></div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     CTA FINALE
     ═══════════════════════════════════════════════════ -->
<section class="tp-cta-section" aria-label="Appel à l'action">
    <div class="tp-container" style="position:relative">
        <h2 class="tp-cta-title" <?= $editMode ? 'data-field="cta_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($ctaTitle) ?>
        </h2>
        <p class="tp-cta-text" <?= $editMode ? 'data-field="cta_text" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($ctaText) ?>
        </p>
        <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="tp-cta-btn" <?= $editMode ? 'data-field="cta_btn_text" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($ctaBtnText) ?>
        </a>
        <?php if ($advisorPhone): ?>
        <p style="color:rgba(255,255,255,.6);font-size:.85rem;margin-top:20px;position:relative" <?= $editMode ? 'data-field="cta_phone_text" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($ctaPhoneText) ?> :
            <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $advisorPhone)) ?>" style="color:var(--tp-white);text-decoration:none;font-weight:700">
                <?= htmlspecialchars($advisorPhone) ?>
            </a>
        </p>
        <?php endif; ?>
    </div>
</section>

<!-- Schema.org -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "RealEstateAgent",
    "name": <?= json_encode($advisorName, JSON_UNESCAPED_UNICODE) ?>,
    "areaServed": {
        "@type": "City",
        "name": <?= json_encode($advisorCity, JSON_UNESCAPED_UNICODE) ?>
    }<?php if ($advisorNetwork): ?>,
    "memberOf": {
        "@type": "Organization",
        "name": <?= json_encode($advisorNetwork, JSON_UNESCAPED_UNICODE) ?>
    }<?php endif; ?><?php if ($advisorPhone): ?>,
    "telephone": <?= json_encode($advisorPhone, JSON_UNESCAPED_UNICODE) ?><?php endif; ?>
}
</script>

<!-- Responsive override pour présentation 2 colonnes -->
<style>
@media (max-width:960px) {
    .tp-section-white [style*="grid-template-columns:1fr 1fr"] {
        grid-template-columns:1fr !important;
        gap:32px !important;
    }
}
</style>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>
