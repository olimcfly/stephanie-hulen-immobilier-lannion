<?php
/**
 * /front/templates/pages/t3-acheter.php
 * Template Acheter — Contenu adapté à l'achat immobilier
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
// CHAMPS ACHETER
// ────────────────────────────────────────────────────
$heroEyebrow   = $fields['hero_eyebrow']   ?? 'Acheter un bien immobilier à ' . $advisorCity;
$heroTitle     = $fields['hero_title']     ?? 'Votre projet d\'achat à Lannion entre de bonnes mains';
$heroSubtitle  = $fields['hero_subtitle']  ?? 'Conseillère immobilière à Lannion, je vous accompagne de la recherche du bien idéal jusqu\'à la remise des clés.';
$heroCtaText   = $fields['hero_cta_text']  ?? 'Voir les biens disponibles à Lannion';
$heroCtaUrl    = $fields['hero_cta_url']   ?? _findMenuUrl($headerMenu['items'] ?? [], 'Biens', $siteUrl . '/biens');
$heroCta2Text  = $fields['hero_cta2_text'] ?? 'Me contacter';
$heroCta2Url   = $fields['hero_cta2_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');

$heroStat1Num  = $fields['hero_stat1_num'] ?? '150+';
$heroStat1Lbl  = $fields['hero_stat1_lbl'] ?? 'biens vendus sur le Trégor';
$heroStat2Num  = $fields['hero_stat2_num'] ?? '98%';
$heroStat2Lbl  = $fields['hero_stat2_lbl'] ?? 'clients satisfaits';

$benTitle  = $fields['ben_title']  ?? 'Pourquoi me confier votre projet d\'achat à Lannion ?';
$ben1Icon  = $fields['ben1_icon']  ?? '🏡';
$ben1Title = $fields['ben1_title'] ?? 'Connaissance du marché lannionnais';
$ben1Text  = $fields['ben1_text']  ?? 'Quartiers prisés, prix au m², projets urbains : je connais Lannion et le Trégor pour vous orienter vers le bon bien.';
$ben2Icon  = $fields['ben2_icon']  ?? '💰';
$ben2Title = $fields['ben2_title'] ?? 'Négociation au juste prix';
$ben2Text  = $fields['ben2_text']  ?? 'Grâce à ma connaissance des prix pratiqués sur Lannion et ses environs, je négocie pour vous les meilleures conditions.';
$ben3Icon  = $fields['ben3_icon']  ?? '🤝';
$ben3Title = $fields['ben3_title'] ?? 'Accompagnement personnalisé';
$ben3Text  = $fields['ben3_text']  ?? 'De la première visite à la signature chez le notaire, je suis votre interlocutrice unique à chaque étape.';

$methodTitle   = $fields['method_title']    ?? 'Votre achat à Lannion en 3 étapes';
$step1Num      = $fields['step1_num']       ?? '01';
$step1Title    = $fields['step1_title']     ?? 'Définir votre projet';
$step1Text     = $fields['step1_text']      ?? 'Centre-ville, bord de mer, campagne trégorroise ? Définissons ensemble vos critères, votre budget et le secteur idéal.';
$step2Num      = $fields['step2_num']       ?? '02';
$step2Title    = $fields['step2_title']     ?? 'Visites et sélection';
$step2Text     = $fields['step2_text']      ?? 'Je présélectionne les biens qui correspondent à vos attentes et organise les visites sur Lannion et alentours.';
$step3Num      = $fields['step3_num']       ?? '03';
$step3Title    = $fields['step3_title']     ?? 'Offre et signature';
$step3Text     = $fields['step3_text']      ?? 'Négociation du prix, rédaction de l\'offre, coordination avec le notaire : je vous accompagne jusqu\'à la remise des clés.';
$methodCtaText = $fields['method_cta_text'] ?? 'Démarrer votre recherche';
$methodCtaUrl  = $fields['method_cta_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');

$guideTitle = $fields['guide_title'] ?? 'Guide : acheter un bien immobilier à Lannion';
$g1Num      = $fields['g1_num']   ?? '01';
$g1Title    = $fields['g1_title'] ?? 'Le marché immobilier à Lannion : ce qu\'il faut savoir';
$g1Text     = $fields['g1_text']  ?? '<p>Lannion offre un cadre de vie attractif entre mer et campagne, avec des prix encore accessibles par rapport aux grandes métropoles bretonnes. Le bassin d\'emploi porté par le pôle télécom et numérique attire de nouveaux habitants, ce qui dynamise le marché. Bien se renseigner sur les quartiers (centre historique, Brélévenez, Servel) et les projets d\'aménagement est essentiel pour acheter au bon endroit.</p>';
$g2Num      = $fields['g2_num']   ?? '02';
$g2Title    = $fields['g2_title'] ?? 'Financement et diagnostics : bien préparer son achat';
$g2Text     = $fields['g2_text']  ?? '<p>Avant de visiter, obtenez un accord de principe auprès de votre banque pour connaître votre capacité d\'emprunt. Côté vendeur, un dossier de diagnostics complet est obligatoire : DPE, amiante, plomb, électricité, gaz, ERNMT. En Bretagne, le diagnostic termites est rarement requis, mais le DPE prend une importance particulière dans les maisons anciennes du Trégor.</p>';
$g3Num      = $fields['g3_num']   ?? '03';
$g3Title    = $fields['g3_title'] ?? 'Du compromis à la remise des clés';
$g3Text     = $fields['g3_text']  ?? '<p>Après la signature du compromis, vous disposez de 10 jours de rétractation. Comptez ensuite 45 à 60 jours pour finaliser votre prêt. La signature de l\'acte authentique se fait chez un notaire, souvent à Lannion ou Guingamp. Je coordonne l\'ensemble des intervenants pour que votre achat se déroule sereinement jusqu\'à la remise des clés.</p>';

$ctaTitle     = $fields['cta_title']      ?? 'Prêt à acheter à Lannion ?';
$ctaText      = $fields['cta_text']       ?? 'Parlons de votre projet. Je vous aide à trouver le bien idéal sur Lannion et le Trégor.';
$ctaBtnText   = $fields['cta_btn_text']   ?? 'Me contacter';
$ctaBtnUrl    = $fields['cta_btn_url']    ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');
$ctaPhoneText = $fields['cta_phone_text'] ?? 'Ou appelez-moi directement';

ob_start();
require_once __DIR__ . '/_tpl-common.php';
?>

<!-- HERO -->
<section class="hero-landing" aria-label="Section héro acheter">
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
                <div class="landing-box-icon">★</div>
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

<!-- BÉNÉFICES ACHETER -->
<section class="section-white" aria-label="Avantages acheter">
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

<!-- MÉTHODE ACHETER -->
<section class="section-white" aria-label="Processus d'achat">
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

<!-- GUIDE ACHETER -->
<section class="section-guide" aria-label="Guide d'achat">
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