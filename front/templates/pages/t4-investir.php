<?php
/**
 * /front/templates/pages/t4-investir.php
 * Template Investir — Contenu adapté à l'investissement immobilier
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
// CHAMPS INVESTIR
// ────────────────────────────────────────────────────
$heroEyebrow   = $fields['hero_eyebrow']   ?? 'Investir dans l\'immobilier à ' . $advisorCity;
$heroTitle     = $fields['hero_title']     ?? 'Constituez votre patrimoine avec des investissements immobiliers rentables';
$heroSubtitle  = $fields['hero_subtitle']  ?? 'Analyse de rentabilité, sélection de biens à potentiel, montage complet du dossier.';
$heroCtaText   = $fields['hero_cta_text']  ?? 'Consulter nos biens d\'investissement';
$heroCtaUrl    = $fields['hero_cta_url']   ?? _findMenuUrl($headerMenu['items'] ?? [], 'Investir', $siteUrl . '/investir');
$heroCta2Text  = $fields['hero_cta2_text'] ?? 'Discuter de votre stratégie';
$heroCta2Url   = $fields['hero_cta2_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');

$heroStat1Num  = $fields['hero_stat1_num'] ?? '5.2%';
$heroStat1Lbl  = $fields['hero_stat1_lbl'] ?? 'rentabilité moyenne';
$heroStat2Num  = $fields['hero_stat2_num'] ?? '180+';
$heroStat2Lbl  = $fields['hero_stat2_lbl'] ?? 'investisseurs conseillés';

$benTitle  = $fields['ben_title']  ?? 'Pourquoi investir dans l\'immobilier avec un conseiller ?';
$ben1Icon  = $fields['ben1_icon']  ?? '📊';
$ben1Title = $fields['ben1_title'] ?? 'Analyse de rentabilité';
$ben1Text  = $fields['ben1_text']  ?? 'Calcul précis des rendements locatifs bruts et nets, étude de la fiscalité à ' . $advisorCity . '.';
$ben2Icon  = $fields['ben2_icon']  ?? '🎯';
$ben2Title = $fields['ben2_title'] ?? 'Sélection stratégique';
$ben2Text  = $fields['ben2_text']  ?? 'Identification des biens avec fort potentiel de plus-value et rendement régulier.';
$ben3Icon  = $fields['ben3_icon']  ?? '⚙️';
$ben3Title = $fields['ben3_title'] ?? 'Montage complet';
$ben3Text  = $fields['ben3_text']  ?? 'Aide sur le financement, la fiscalité, la gestion locative et le suivi de l\'investissement.';

$methodTitle   = $fields['method_title']    ?? 'Mon processus d\'investissement en 3 étapes';
$step1Num      = $fields['step1_num']       ?? '01';
$step1Title    = $fields['step1_title']     ?? 'Définir votre stratégie';
$step1Text     = $fields['step1_text']      ?? 'Budget, objectifs de rendement, préférences géographiques et types de biens.';
$step2Num      = $fields['step2_num']       ?? '02';
$step2Title    = $fields['step2_title']     ?? 'Analyser les opportunités';
$step2Text     = $fields['step2_text']      ?? 'Évaluation de rentabilité, analyse du marché locatif et potentiel de plus-value.';
$step3Num      = $fields['step3_num']       ?? '03';
$step3Title    = $fields['step3_title']     ?? 'Finaliser l\'investissement';
$step3Text     = $fields['step3_text']      ?? 'Montage du dossier, financement, optimisation fiscale et signature notariale.';
$methodCtaText = $fields['method_cta_text'] ?? 'Étudier une opportunité';
$methodCtaUrl  = $fields['method_cta_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');

$guideTitle = $fields['guide_title'] ?? 'Guide de l\'investissement immobilier';
$g1Num      = $fields['g1_num']   ?? '01';
$g1Title    = $fields['g1_title'] ?? 'Comprendre la rentabilité brute et nette';
$g1Text     = $fields['g1_text']  ?? '<p><strong>Rentabilité brute</strong> = loyer annuel / prix d\'achat. Exemple : 6000€ loyer / 100 000€ bien = 6%.<br><strong>Rentabilité nette</strong> = après impôts, charges et frais de gestion. Généralement 3-4% pour les petits propriétaires.</p>';
$g2Num      = $fields['g2_num']   ?? '02';
$g2Title    = $fields['g2_title'] ?? 'Optimiser sa fiscalité : régime classique ou micro-foncier ?';
$g2Text     = $fields['g2_text']  ?? '<p>Régime classique : déduction de toutes les charges (intérêts d\'emprunt, travaux, assurances). Meilleur pour les gros investisseurs.<br>Micro-foncier : abattement forfaitaire de 30%. Plus simple, idéal si peu de charges.</p>';
$g3Num      = $fields['g3_num']   ?? '03';
$g3Title    = $fields['g3_title'] ?? 'Bien sélectionner votre bien : critères clés';
$g3Text     = $fields['g3_text']  ?? '<p>Zone à forte demande locative, bonne accessibilité, état du bien, potentiel de plus-value. Éviter les zones de suroffre locative.</p>';

$ctaTitle     = $fields['cta_title']      ?? 'Prêt à lancer votre investissement ?';
$ctaText      = $fields['cta_text']       ?? 'Je vous aide à définir votre stratégie et à identifier les meilleures opportunités.';
$ctaBtnText   = $fields['cta_btn_text']   ?? 'Prendre rendez-vous';
$ctaBtnUrl    = $fields['cta_btn_url']    ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');
$ctaPhoneText = $fields['cta_phone_text'] ?? 'Ou appelez-moi directement';

ob_start();
require_once __DIR__ . '/_tpl-common.php';
?>

<!-- HERO -->
<section class="hero-landing" aria-label="Section héro investir">
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
                <div class="landing-box-icon">📈</div>
                <h3><?= htmlspecialchars($heroStat1Num) ?></h3>
                <p <?= $editMode ? 'data-field="hero_stat1_lbl" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($heroStat1Lbl) ?>
                </p>
            </div>
            <div class="landing-box">
                <div class="landing-box-icon">👥</div>
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

<!-- BÉNÉFICES INVESTIR -->
<section class="section-white" aria-label="Avantages investir">
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

<!-- MÉTHODE INVESTIR -->
<section class="section-white" aria-label="Processus d'investissement">
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

<!-- GUIDE INVESTIR -->
<section class="section-guide" aria-label="Guide d'investissement">
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