<?php
/**
 * /front/templates/pages/t2-vendre.php
 * Template Vendre — Contenu adapté à la vente immobilière
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
// CHAMPS VENDRE
// ────────────────────────────────────────────────────
$heroEyebrow   = $fields['hero_eyebrow']   ?? 'Vendre votre bien à ' . $advisorCity;
$heroTitle     = $fields['hero_title']     ?? 'Vendez au meilleur prix avec un conseiller de confiance';
$heroSubtitle  = $fields['hero_subtitle']  ?? 'Estimation gratuite, acheteurs qualifiés, accompagnement sécurisé jusqu\'à la signature.';
$heroCtaText   = $fields['hero_cta_text']  ?? 'Demander mon estimation gratuite';
$heroCtaUrl    = $fields['hero_cta_url']   ?? _findMenuUrl($headerMenu['items'] ?? [], 'Estimation', $siteUrl . '/estimation');
$heroCta2Text  = $fields['hero_cta2_text'] ?? 'Me contacter';
$heroCta2Url   = $fields['hero_cta2_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');

$heroStat1Num  = $fields['hero_stat1_num'] ?? '98%';
$heroStat1Lbl  = $fields['hero_stat1_lbl'] ?? 'clients satisfaits';
$heroStat2Num  = $fields['hero_stat2_num'] ?? '45j';
$heroStat2Lbl  = $fields['hero_stat2_lbl'] ?? 'délai moyen de vente';

$benTitle  = $fields['ben_title']  ?? 'Pourquoi confier votre vente à un conseiller ?';
$ben1Icon  = $fields['ben1_icon']  ?? '💰';
$ben1Title = $fields['ben1_title'] ?? 'Estimation précise';
$ben1Text  = $fields['ben1_text']  ?? 'Prix juste basé sur les données réelles du marché à ' . $advisorCity . '.';
$ben2Icon  = $fields['ben2_icon']  ?? '🎯';
$ben2Title = $fields['ben2_title'] ?? 'Acheteurs qualifiés';
$ben2Text  = $fields['ben2_text']  ?? 'Vérification du financement avant chaque visite. Aucune perte de temps.';
$ben3Icon  = $fields['ben3_icon']  ?? '🛡️';
$ben3Title = $fields['ben3_title'] ?? 'Sécurité juridique';
$ben3Text  = $fields['ben3_text']  ?? 'Suivi complet du compromis à l\'acte authentique chez le notaire.';

$methodTitle   = $fields['method_title']    ?? 'Mon processus de vente en 3 étapes';
$step1Num      = $fields['step1_num']       ?? '01';
$step1Title    = $fields['step1_title']     ?? 'Estimation gratuite';
$step1Text     = $fields['step1_text']      ?? 'Analyse du marché et avis de valeur sans engagement.';
$step2Num      = $fields['step2_num']       ?? '02';
$step2Title    = $fields['step2_title']     ?? 'Mise en valeur & diffusion';
$step2Text     = $fields['step2_text']      ?? 'Photos pro, annonce optimisée, diffusion tous portails et réseau eXp.';
$step3Num      = $fields['step3_num']       ?? '03';
$step3Title    = $fields['step3_title']     ?? 'Négociation & signature';
$step3Text     = $fields['step3_text']      ?? 'Qualification acheteurs, négociation du meilleur prix, suivi jusqu\'à la signature.';
$methodCtaText = $fields['method_cta_text'] ?? 'Prendre rendez-vous';
$methodCtaUrl  = $fields['method_cta_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');

$guideTitle = $fields['guide_title'] ?? 'Guide de vente immobilière à ' . $advisorCity;
$g1Num      = $fields['g1_num']   ?? '01';
$g1Title    = $fields['g1_title'] ?? 'Les diagnostics obligatoires avant la vente';
$g1Text     = $fields['g1_text']  ?? '<p>DPE, amiante, plomb, termites, gaz, électricité, ERNMT. Un dossier complet est essentiel pour sécuriser la vente et éviter les blocages.</p>';
$g2Num      = $fields['g2_num']   ?? '02';
$g2Title    = $fields['g2_title'] ?? 'Mandat simple ou exclusif : quelle différence ?';
$g2Text     = $fields['g2_text']  ?? '<p>Le mandat exclusif concentre tous les efforts sur votre bien et accélère généralement la vente. C\'est ce que je recommande pour maximiser le résultat.</p>';
$g3Num      = $fields['g3_num']   ?? '03';
$g3Title    = $fields['g3_title'] ?? 'Du compromis à l\'acte authentique';
$g3Text     = $fields['g3_text']  ?? '<p>10 jours de rétractation pour l\'acheteur, puis 45-60 jours pour obtenir le prêt. Je vous accompagne jusqu\'à la signature chez le notaire.</p>';

$ctaTitle     = $fields['cta_title']      ?? 'Prêt à vendre votre bien ?';
$ctaText      = $fields['cta_text']       ?? 'Tout commence par une estimation gratuite et sans engagement.';
$ctaBtnText   = $fields['cta_btn_text']   ?? 'Demander mon estimation gratuite';
$ctaBtnUrl    = $fields['cta_btn_url']    ?? _findMenuUrl($headerMenu['items'] ?? [], 'Estimation', $siteUrl . '/estimation');
$ctaPhoneText = $fields['cta_phone_text'] ?? 'Ou appelez-moi directement';

ob_start();
require_once __DIR__ . '/_tpl-common.php';
?>

<!-- HERO -->
<section class="hero-landing" aria-label="Section héro vendre">
    <div class="hero-landing-inner">
        <p class="hero-subtitle" <?= $editMode ? 'data-field="hero_eyebrow" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroEyebrow) ?>
        </p>
        <h1 class="hero-landing-title" <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroTitle) ?>
        </h1>
        <p class="hero-landing-description" <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroSubtitle) ?>
        </p>
        <div class="hero-landing-boxes">
            <div class="landing-box">
                <div class="landing-box-icon">✓</div>
                <h3><?= htmlspecialchars($heroStat1Num) ?></h3>
                <p <?= $editMode ? 'data-field="hero_stat1_lbl" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($heroStat1Lbl) ?>
                </p>
            </div>
            <div class="landing-box">
                <div class="landing-box-icon">⏱️</div>
                <h3><?= htmlspecialchars($heroStat2Num) ?></h3>
                <p <?= $editMode ? 'data-field="hero_stat2_lbl" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($heroStat2Lbl) ?>
                </p>
            </div>
        </div>
        <div style="text-align: center; margin-bottom: 30px;">
            <a href="<?= htmlspecialchars($heroCtaUrl) ?>" class="hero-landing-cta" <?= $editMode ? 'data-field="hero_cta_text" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($heroCtaText) ?> →
            </a>
        </div>
    </div>
</section>

<!-- BÉNÉFICES VENDRE -->
<section class="section-white" aria-label="Avantages vendre">
    <div class="container">
        <div class="text-center section-header">
            <h2 class="section-title" <?= $editMode ? 'data-field="ben_title" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($benTitle) ?>
            </h2>
        </div>
        <div class="cards-wrapper cards-3">
            <div class="card">
                <div class="card-icon" <?= $editMode ? 'data-field="ben1_icon" class="ef-zone"' : '' ?>>
                    <?= $ben1Icon ?>
                </div>
                <h3 <?= $editMode ? 'data-field="ben1_title" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($ben1Title) ?>
                </h3>
                <p <?= $editMode ? 'data-field="ben1_text" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($ben1Text) ?>
                </p>
            </div>

            <div class="card">
                <div class="card-icon" <?= $editMode ? 'data-field="ben2_icon" class="ef-zone"' : '' ?>>
                    <?= $ben2Icon ?>
                </div>
                <h3 <?= $editMode ? 'data-field="ben2_title" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($ben2Title) ?>
                </h3>
                <p <?= $editMode ? 'data-field="ben2_text" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($ben2Text) ?>
                </p>
            </div>

            <div class="card">
                <div class="card-icon" <?= $editMode ? 'data-field="ben3_icon" class="ef-zone"' : '' ?>>
                    <?= $ben3Icon ?>
                </div>
                <h3 <?= $editMode ? 'data-field="ben3_title" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($ben3Title) ?>
                </h3>
                <p <?= $editMode ? 'data-field="ben3_text" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($ben3Text) ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- MÉTHODE VENDRE -->
<section class="section-white" aria-label="Processus de vente">
    <div class="container">
        <div class="text-center section-header">
            <span class="section-badge">Processus</span>
            <h2 class="section-title" <?= $editMode ? 'data-field="method_title" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($methodTitle) ?>
            </h2>
        </div>
        <div class="steps-wrapper">
            <div class="step-card">
                <div class="step-number" <?= $editMode ? 'data-field="step1_num" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step1Num) ?>
                </div>
                <h3 <?= $editMode ? 'data-field="step1_title" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step1Title) ?>
                </h3>
                <p <?= $editMode ? 'data-field="step1_text" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step1Text) ?>
                </p>
            </div>

            <div class="step-card">
                <div class="step-number" <?= $editMode ? 'data-field="step2_num" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step2Num) ?>
                </div>
                <h3 <?= $editMode ? 'data-field="step2_title" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step2Title) ?>
                </h3>
                <p <?= $editMode ? 'data-field="step2_text" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step2Text) ?>
                </p>
            </div>

            <div class="step-card">
                <div class="step-number" <?= $editMode ? 'data-field="step3_num" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step3Num) ?>
                </div>
                <h3 <?= $editMode ? 'data-field="step3_title" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step3Title) ?>
                </h3>
                <p <?= $editMode ? 'data-field="step3_text" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step3Text) ?>
                </p>
            </div>
        </div>

        <div class="text-center methodology-footer">
            <a href="<?= htmlspecialchars($methodCtaUrl) ?>" class="cta-btn" <?= $editMode ? 'data-field="method_cta_text" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($methodCtaText) ?>
            </a>
        </div>
    </div>
</section>

<!-- GUIDE VENDRE -->
<section class="section-guide" aria-label="Guide de vente">
    <div class="container">
        <div class="text-center section-header">
            <span class="section-badge">Guide pratique</span>
            <h2 class="section-title" <?= $editMode ? 'data-field="guide_title" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($guideTitle) ?>
            </h2>
        </div>

        <div class="guide-cards">
            <article class="guide-card">
                <div class="guide-card-number" <?= $editMode ? 'data-field="g1_num" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($g1Num) ?>
                </div>
                <div class="guide-card-content">
                    <h3 <?= $editMode ? 'data-field="g1_title" class="ef-zone"' : '' ?>>
                        <?= htmlspecialchars($g1Title) ?>
                    </h3>
                    <div <?= $editMode ? 'data-field="g1_text" class="ef-zone ef-rich"' : '' ?>>
                        <?= $g1Text ?>
                    </div>
                </div>
            </article>

            <article class="guide-card">
                <div class="guide-card-number" <?= $editMode ? 'data-field="g2_num" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($g2Num) ?>
                </div>
                <div class="guide-card-content">
                    <h3 <?= $editMode ? 'data-field="g2_title" class="ef-zone"' : '' ?>>
                        <?= htmlspecialchars($g2Title) ?>
                    </h3>
                    <div <?= $editMode ? 'data-field="g2_text" class="ef-zone ef-rich"' : '' ?>>
                        <?= $g2Text ?>
                    </div>
                </div>
            </article>

            <article class="guide-card">
                <div class="guide-card-number" <?= $editMode ? 'data-field="g3_num" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($g3Num) ?>
                </div>
                <div class="guide-card-content">
                    <h3 <?= $editMode ? 'data-field="g3_title" class="ef-zone"' : '' ?>>
                        <?= htmlspecialchars($g3Title) ?>
                    </h3>
                    <div <?= $editMode ? 'data-field="g3_text" class="ef-zone ef-rich"' : '' ?>>
                        <?= $g3Text ?>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- CTA FINALE -->
<section class="cta-final" aria-label="Appel à l'action">
    <div class="container">
        <div class="cta-final-inner">
            <h2 style="color: #FFFFFF;" <?= $editMode ? 'data-field="cta_title" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($ctaTitle) ?>
            </h2>
            <p class="cta-description" <?= $editMode ? 'data-field="cta_text" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($ctaText) ?>
            </p>
            <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="cta-btn cta-btn-large" <?= $editMode ? 'data-field="cta_btn_text" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($ctaBtnText) ?>
            </a>
            <?php if ($advisorPhone): ?>
            <p class="urgency-note" <?= $editMode ? 'data-field="cta_phone_text" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($ctaPhoneText) ?> :
                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $advisorPhone)) ?>" style="color: #FFFFFF; text-decoration: none; font-weight: 600;">
                    <?= htmlspecialchars($advisorPhone) ?>
                </a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>