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
$heroEyebrow   = $fields['hero_eyebrow']   ?? 'Investir dans l\'immobilier dans le Trégor — Côtes-d\'Armor';
$heroTitle     = $fields['hero_title']     ?? 'Investissement locatif à Lannion et dans le Trégor : des rendements attractifs en Côtes-d\'Armor';
$heroSubtitle  = $fields['hero_subtitle']  ?? 'Marché accessible, rendements locatifs parmi les plus intéressants de Bretagne, forte demande étudiante et professionnelle. Analyse de rentabilité et accompagnement complet.';
$heroCtaText   = $fields['hero_cta_text']  ?? 'Découvrir les opportunités dans le Trégor';
$heroCtaUrl    = $fields['hero_cta_url']   ?? _findMenuUrl($headerMenu['items'] ?? [], 'Investir', $siteUrl . '/investir');
$heroCta2Text  = $fields['hero_cta2_text'] ?? 'Étudier votre projet d\'investissement';
$heroCta2Url   = $fields['hero_cta2_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');

$heroStat1Num  = $fields['hero_stat1_num'] ?? '6 à 9%';
$heroStat1Lbl  = $fields['hero_stat1_lbl'] ?? 'rendement brut moyen à Lannion';
$heroStat2Num  = $fields['hero_stat2_num'] ?? '1 400 €/m²';
$heroStat2Lbl  = $fields['hero_stat2_lbl'] ?? 'prix médian — un des plus accessibles de Bretagne';

$benTitle  = $fields['ben_title']  ?? 'Pourquoi investir dans le Trégor et les Côtes-d\'Armor ?';
$ben1Icon  = $fields['ben1_icon']  ?? '📊';
$ben1Title = $fields['ben1_title'] ?? 'Rendements locatifs élevés';
$ben1Text  = $fields['ben1_text']  ?? 'À Lannion, le rendement brut moyen se situe entre 6 et 9 % grâce à des prix d\'achat contenus (environ 1 400 €/m²) et une demande locative soutenue par le bassin technologique (Lannion Trégor Communauté, pôle télécoms).';
$ben2Icon  = $fields['ben2_icon']  ?? '🎯';
$ben2Title = $fields['ben2_title'] ?? 'Marché accessible et porteur';
$ben2Text  = $fields['ben2_text']  ?? 'Les prix immobiliers dans le Trégor restent parmi les plus abordables de Bretagne, offrant une porte d\'entrée idéale pour les primo-investisseurs. La côte de Granit Rose et la proximité de Brest et Saint-Brieuc renforcent l\'attractivité.';
$ben3Icon  = $fields['ben3_icon']  ?? '⚙️';
$ben3Title = $fields['ben3_title'] ?? 'Accompagnement fiscal et montage';
$ben3Text  = $fields['ben3_text']  ?? 'Optimisation via le régime réel, le dispositif Denormandie dans l\'ancien (applicable à Lannion en zone éligible), ou le statut LMNP. Montage financier et gestion locative adaptés à votre projet.';

$methodTitle   = $fields['method_title']    ?? 'Investir dans le Trégor en 3 étapes';
$step1Num      = $fields['step1_num']       ?? '01';
$step1Title    = $fields['step1_title']     ?? 'Définir votre stratégie locale';
$step1Text     = $fields['step1_text']      ?? 'Budget, objectifs de rendement, choix du secteur (Lannion centre, Perros-Guirec, Tréguier, Paimpol) et type de location (longue durée, saisonnière, meublée LMNP).';
$step2Num      = $fields['step2_num']       ?? '02';
$step2Title    = $fields['step2_title']     ?? 'Analyser le marché trégorois';
$step2Text     = $fields['step2_text']      ?? 'Étude de la demande locative locale (étudiants IUT/ENSSAT, salariés du pôle télécoms, tourisme côte de Granit Rose), calcul du rendement net et simulation fiscale.';
$step3Num      = $fields['step3_num']       ?? '03';
$step3Title    = $fields['step3_title']     ?? 'Concrétiser votre investissement';
$step3Text     = $fields['step3_text']      ?? 'Négociation, montage du financement, choix du dispositif fiscal adapté (Denormandie, LMNP, régime réel), mise en gestion locative et suivi de la rentabilité.';
$methodCtaText = $fields['method_cta_text'] ?? 'Étudier une opportunité dans le Trégor';
$methodCtaUrl  = $fields['method_cta_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');

$guideTitle = $fields['guide_title'] ?? 'Guide de l\'investissement immobilier dans le Trégor';
$g1Num      = $fields['g1_num']   ?? '01';
$g1Title    = $fields['g1_title'] ?? 'Rendements locatifs à Lannion et dans le Trégor';
$g1Text     = $fields['g1_text']  ?? '<p><strong>Rentabilité brute à Lannion</strong> : entre 6 et 9 % selon le type de bien. Exemple concret : un T2 acheté 65 000 € et loué 450 €/mois génère un rendement brut de 8,3 %.<br><strong>Rentabilité nette</strong> : après charges, taxe foncière et fiscalité, comptez généralement 4 à 6 % net à Lannion — bien au-dessus de la moyenne nationale (3-4 %). Les prix contenus dans le Trégor (1 400 €/m² en médiane) sont le principal levier de ces rendements élevés.</p>';
$g2Num      = $fields['g2_num']   ?? '02';
$g2Title    = $fields['g2_title'] ?? 'Fiscalité : les dispositifs applicables dans le Trégor';
$g2Text     = $fields['g2_text']  ?? '<p><strong>Denormandie dans l\'ancien</strong> : Lannion est éligible à ce dispositif de défiscalisation qui offre une réduction d\'impôt de 12 à 21 % du prix du bien (selon la durée de location : 6, 9 ou 12 ans) pour l\'achat d\'un logement ancien à rénover.<br><strong>LMNP (Loueur Meublé Non Professionnel)</strong> : amortissement du bien et des meubles, permettant de réduire voire annuler l\'imposition sur les loyers. Particulièrement adapté à la location étudiante à Lannion.<br><strong>Régime réel</strong> : déduction des charges réelles (intérêts d\'emprunt, travaux, assurance, frais de gestion). Recommandé si vos charges dépassent 30 % des loyers.<br><strong>Micro-foncier</strong> : abattement forfaitaire de 30 %, simple et adapté si peu de charges. Plafonné à 15 000 € de revenus fonciers annuels.<br><strong>Déficit foncier</strong> : les travaux de rénovation dans l\'ancien permettent de créer un déficit imputable sur le revenu global (jusqu\'à 10 700 €/an), idéal pour les biens à rénover dans le centre historique de Lannion ou de Tréguier.</p>';
$g3Num      = $fields['g3_num']   ?? '03';
$g3Title    = $fields['g3_title'] ?? 'Les meilleurs secteurs d\'investissement dans le Trégor';
$g3Text     = $fields['g3_text']  ?? '<p><strong>Lannion centre-ville</strong> : forte demande locative (étudiants IUT et ENSSAT, salariés du pôle télécoms Nokia/Orange), rendements de 7 à 9 % sur les petites surfaces.<br><strong>Perros-Guirec / Trébeurden</strong> : potentiel en location saisonnière (côte de Granit Rose), rendements bruts de 5 à 7 % avec une forte valorisation patrimoniale.<br><strong>Tréguier / Paimpol</strong> : petits prix, biens de caractère à rénover (éligibles Denormandie), rendements bruts de 6 à 8 %.<br><strong>Critères clés</strong> : proximité des transports, état du bien et coût des travaux, tension locative du secteur, potentiel de plus-value à moyen terme.</p>';

$ctaTitle     = $fields['cta_title']      ?? 'Prêt à investir dans le Trégor ?';
$ctaText      = $fields['cta_text']       ?? 'Analyse de rentabilité gratuite, sélection de biens à potentiel, simulation fiscale personnalisée. Parlons de votre projet d\'investissement à Lannion et dans les Côtes-d\'Armor.';
$ctaBtnText   = $fields['cta_btn_text']   ?? 'Étudier mon projet d\'investissement';
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