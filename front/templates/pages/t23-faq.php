<?php
/**
 * /front/templates/pages/t23-faq.php
 * Template FAQ — questions fréquentes achat/vente à Lannion
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

$advisorName = $advisor['name'] ?? ($site['name'] ?? 'Conseiller');
require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

// ════════════════════════════════════════════════
// CHAMPS ÉDITABLES
// ════════════════════════════════════════════════

$heroEyebrow  = $fields['hero_eyebrow']  ?? 'FAQ';
$heroTitle    = $fields['hero_title']     ?? 'Questions fréquentes sur l\'immobilier à Lannion';
$heroSubtitle = $fields['hero_subtitle']  ?? 'Retrouvez les réponses aux questions les plus courantes sur l\'achat, la vente et l\'estimation immobilière.';

// Catégorie Achat
$catAchatTitle = $fields['cat_achat_title'] ?? 'Questions sur l\'achat immobilier';
$faq1Q = $fields['faq1_q'] ?? 'Quelles sont les étapes pour acheter un bien à Lannion ?';
$faq1A = $fields['faq1_a'] ?? 'L\'achat immobilier suit plusieurs étapes : définition du budget, recherche du bien, visites, offre d\'achat, compromis de vente, obtention du prêt, puis signature de l\'acte authentique chez le notaire. Je vous accompagne à chaque étape.';
$faq2Q = $fields['faq2_q'] ?? 'Quel budget prévoir pour un achat immobilier ?';
$faq2A = $fields['faq2_a'] ?? 'Au-delà du prix du bien, prévoyez les frais de notaire (7-8% dans l\'ancien, 2-3% dans le neuf), les frais de garantie du prêt, et éventuellement les travaux. Un apport de 10% du prix est généralement recommandé.';
$faq3Q = $fields['faq3_q'] ?? 'Peut-on acheter sans apport à Lannion ?';
$faq3A = $fields['faq3_a'] ?? 'C\'est possible mais plus difficile. Certaines banques acceptent de financer 110% du projet (prix + frais). Un dossier solide avec des revenus stables et une bonne gestion financière est indispensable.';

// Catégorie Vente
$catVenteTitle = $fields['cat_vente_title'] ?? 'Questions sur la vente immobilière';
$faq4Q = $fields['faq4_q'] ?? 'Comment estimer le prix de mon bien ?';
$faq4A = $fields['faq4_a'] ?? 'L\'estimation repose sur l\'analyse comparative du marché local, les caractéristiques du bien (surface, état, exposition, prestations) et sa localisation. Je réalise des estimations gratuites et personnalisées.';
$faq5Q = $fields['faq5_q'] ?? 'Quels diagnostics sont obligatoires pour vendre ?';
$faq5A = $fields['faq5_a'] ?? 'Les diagnostics obligatoires incluent : DPE, amiante, plomb, électricité, gaz, termites (selon zone), ERP, assainissement. Je vous mets en relation avec des diagnostiqueurs certifiés.';
$faq6Q = $fields['faq6_q'] ?? 'Combien de temps faut-il pour vendre un bien à Lannion ?';
$faq6A = $fields['faq6_a'] ?? 'Le délai moyen de vente à Lannion est de 2 à 4 mois pour un bien correctement estimé. La commercialisation, les visites, le compromis et le délai légal de rétractation prennent environ 3 mois au total.';

// Catégorie Financement
$catFinTitle   = $fields['cat_fin_title'] ?? 'Questions sur le financement';
$faq7Q = $fields['faq7_q'] ?? 'Quel est le taux immobilier actuel ?';
$faq7A = $fields['faq7_a'] ?? 'Les taux varient selon la durée du prêt et votre profil emprunteur. Je travaille avec des courtiers partenaires qui négocient les meilleures conditions pour vous. Contactez-moi pour une simulation personnalisée.';
$faq8Q = $fields['faq8_q'] ?? 'Quelles aides existent pour les primo-accédants ?';
$faq8A = $fields['faq8_a'] ?? 'Plusieurs dispositifs existent : le Prêt à Taux Zéro (PTZ), le prêt Action Logement, les aides locales de Lannion Trégor Communauté. Je vous aide à identifier les aides auxquelles vous avez droit.';

// Catégorie Générale
$catGenTitle   = $fields['cat_gen_title'] ?? 'Questions générales';
$faq9Q  = $fields['faq9_q']  ?? 'Pourquoi passer par un conseiller immobilier ?';
$faq9A  = $fields['faq9_a']  ?? 'Un conseiller vous apporte son expertise du marché local, vous fait gagner du temps, sécurise juridiquement la transaction et négocie dans votre intérêt. Mon accompagnement est personnalisé et transparent.';
$faq10Q = $fields['faq10_q'] ?? 'Quels sont vos honoraires ?';
$faq10A = $fields['faq10_a'] ?? 'Mes honoraires sont affichés en toute transparence sur la page dédiée. Ils sont uniquement dus en cas de succès, c\'est-à-dire à la signature de l\'acte authentique.';

// CTA
$ctaTitle     = $fields['cta_title']     ?? 'Vous avez une autre question ?';
$ctaText      = $fields['cta_text']      ?? 'N\'hésitez pas à me contacter, je réponds à toutes vos interrogations sur votre projet immobilier.';
$ctaBtnText   = $fields['cta_btn_text']  ?? 'Me poser une question';
$ctaBtnUrl    = $fields['cta_btn_url']   ?? '/contact';

$contactUrl   = _findMenuUrl($headerMenu['items'] ?? [], 'Contact', '/contact');

// ════════════════════════════════════════════════
// CONTENU HTML
// ════════════════════════════════════════════════

ob_start();
require_once __DIR__ . '/_tpl-common.php';
?>

<style>
.t23-faq-item { background:var(--tp-white); border:1px solid var(--tp-border); border-radius:var(--tp-radius); margin-bottom:12px; overflow:hidden; transition:border-color .2s; }
.t23-faq-item:hover { border-color:var(--tp-accent); }
.t23-faq-q { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:20px 24px; cursor:pointer; font-weight:700; font-size:.95rem; color:var(--tp-primary); background:none; border:none; width:100%; text-align:left; font-family:inherit; line-height:1.5; }
.t23-faq-q:hover { background:var(--tp-bg); }
.t23-faq-chevron { flex-shrink:0; width:24px; height:24px; display:flex; align-items:center; justify-content:center; background:var(--tp-bg); border-radius:50%; font-size:.7rem; color:var(--tp-accent-d); transition:transform .2s; }
.t23-faq-item.open .t23-faq-chevron { transform:rotate(180deg); }
.t23-faq-a { padding:0 24px 20px; color:var(--tp-text2); font-size:.9rem; line-height:1.8; display:none; }
.t23-faq-item.open .t23-faq-a { display:block; }
.t23-cat-header { display:flex; align-items:center; gap:12px; margin:48px 0 24px; }
.t23-cat-header:first-child { margin-top:0; }
.t23-cat-icon { font-size:1.6rem; }
.t23-cat-title { font-family:var(--tp-ff-display); font-size:1.3rem; font-weight:800; color:var(--tp-primary); margin:0; }
</style>

<!-- HERO -->
<section class="tp-hero">
    <div class="tp-hero-inner">
        <div <?= $editMode ? 'data-field="hero_eyebrow" class="ef-zone"' : '' ?>
             class="tp-eyebrow"><?= htmlspecialchars($heroEyebrow) ?></div>
        <h1 <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?>
            class="tp-hero-h1"><?= htmlspecialchars($heroTitle) ?></h1>
        <p <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?>
           class="tp-hero-sub"><?= htmlspecialchars($heroSubtitle) ?></p>
    </div>
</section>

<!-- FAQ -->
<section class="tp-section-white">
    <div class="tp-container" style="max-width:820px;">

        <!-- ACHAT -->
        <div class="t23-cat-header">
            <div class="t23-cat-icon">🏠</div>
            <h2 <?= $editMode ? 'data-field="cat_achat_title" class="ef-zone"' : '' ?>
                class="t23-cat-title"><?= htmlspecialchars($catAchatTitle) ?></h2>
        </div>
        <?php for ($i = 1; $i <= 3; $i++):
            $q = ${'faq'.$i.'Q'};
            $a = ${'faq'.$i.'A'};
        ?>
        <div class="t23-faq-item">
            <button class="t23-faq-q" onclick="this.parentElement.classList.toggle('open')"
                    <?= $editMode ? 'data-field="faq'.$i.'_q" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($q) ?>
                <span class="t23-faq-chevron">▼</span>
            </button>
            <div class="t23-faq-a" <?= $editMode ? 'data-field="faq'.$i.'_a" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($a) ?>
            </div>
        </div>
        <?php endfor; ?>

        <!-- VENTE -->
        <div class="t23-cat-header">
            <div class="t23-cat-icon">💰</div>
            <h2 <?= $editMode ? 'data-field="cat_vente_title" class="ef-zone"' : '' ?>
                class="t23-cat-title"><?= htmlspecialchars($catVenteTitle) ?></h2>
        </div>
        <?php for ($i = 4; $i <= 6; $i++):
            $q = ${'faq'.$i.'Q'};
            $a = ${'faq'.$i.'A'};
        ?>
        <div class="t23-faq-item">
            <button class="t23-faq-q" onclick="this.parentElement.classList.toggle('open')"
                    <?= $editMode ? 'data-field="faq'.$i.'_q" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($q) ?>
                <span class="t23-faq-chevron">▼</span>
            </button>
            <div class="t23-faq-a" <?= $editMode ? 'data-field="faq'.$i.'_a" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($a) ?>
            </div>
        </div>
        <?php endfor; ?>

        <!-- FINANCEMENT -->
        <div class="t23-cat-header">
            <div class="t23-cat-icon">🏦</div>
            <h2 <?= $editMode ? 'data-field="cat_fin_title" class="ef-zone"' : '' ?>
                class="t23-cat-title"><?= htmlspecialchars($catFinTitle) ?></h2>
        </div>
        <?php for ($i = 7; $i <= 8; $i++):
            $q = ${'faq'.$i.'Q'};
            $a = ${'faq'.$i.'A'};
        ?>
        <div class="t23-faq-item">
            <button class="t23-faq-q" onclick="this.parentElement.classList.toggle('open')"
                    <?= $editMode ? 'data-field="faq'.$i.'_q" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($q) ?>
                <span class="t23-faq-chevron">▼</span>
            </button>
            <div class="t23-faq-a" <?= $editMode ? 'data-field="faq'.$i.'_a" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($a) ?>
            </div>
        </div>
        <?php endfor; ?>

        <!-- GÉNÉRAL -->
        <div class="t23-cat-header">
            <div class="t23-cat-icon">❓</div>
            <h2 <?= $editMode ? 'data-field="cat_gen_title" class="ef-zone"' : '' ?>
                class="t23-cat-title"><?= htmlspecialchars($catGenTitle) ?></h2>
        </div>
        <?php for ($i = 9; $i <= 10; $i++):
            $q = ${'faq'.$i.'Q'};
            $a = ${'faq'.$i.'A'};
        ?>
        <div class="t23-faq-item">
            <button class="t23-faq-q" onclick="this.parentElement.classList.toggle('open')"
                    <?= $editMode ? 'data-field="faq'.$i.'_q" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($q) ?>
                <span class="t23-faq-chevron">▼</span>
            </button>
            <div class="t23-faq-a" <?= $editMode ? 'data-field="faq'.$i.'_a" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($a) ?>
            </div>
        </div>
        <?php endfor; ?>

    </div>
</section>

<!-- SCHEMA FAQ (SEO) -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        <?php
        $faqItems = [];
        for ($i = 1; $i <= 10; $i++) {
            $q = ${'faq'.$i.'Q'};
            $a = ${'faq'.$i.'A'};
            $faqItems[] = '{
                "@type": "Question",
                "name": ' . json_encode($q, JSON_UNESCAPED_UNICODE) . ',
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": ' . json_encode($a, JSON_UNESCAPED_UNICODE) . '
                }
            }';
        }
        echo implode(",\n        ", $faqItems);
        ?>
    ]
}
</script>

<!-- CTA FINALE -->
<section class="tp-cta-section">
    <div class="tp-container" style="max-width:700px;">
        <h2 <?= $editMode ? 'data-field="cta_title" class="ef-zone"' : '' ?>
            class="tp-cta-title"><?= htmlspecialchars($ctaTitle) ?></h2>
        <p <?= $editMode ? 'data-field="cta_text" class="ef-zone"' : '' ?>
           class="tp-cta-text"><?= htmlspecialchars($ctaText) ?></p>
        <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="tp-cta-btn"
           <?= $editMode ? 'data-field="cta_btn_text"' : '' ?>><?= htmlspecialchars($ctaBtnText) ?></a>
    </div>
</section>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>
