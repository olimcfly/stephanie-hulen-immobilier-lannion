<?php
/**
 * /front/templates/pages/t22-annuaire-partenaires.php
 * Template Annuaire Partenaires — artisans, notaires, diagnostiqueurs locaux
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

$heroEyebrow  = $fields['hero_eyebrow']  ?? 'Annuaire partenaires';
$heroTitle    = $fields['hero_title']     ?? 'Nos partenaires de confiance à Lannion';
$heroSubtitle = $fields['hero_subtitle']  ?? 'Des professionnels locaux sélectionnés pour leur sérieux et leur expertise, pour vous accompagner dans toutes les étapes de votre projet.';

// Intro
$introTitle   = $fields['intro_title']   ?? 'Un réseau de professionnels à votre service';
$introText    = $fields['intro_text']    ?? 'Tout au long de votre projet immobilier, vous aurez besoin de professionnels compétents. Je travaille régulièrement avec ces partenaires et vous les recommande en toute confiance.';

// Notaires
$notTitle     = $fields['not_title']     ?? 'Notaires';
$notIcon      = $fields['not_icon']      ?? '⚖️';
$not1Name     = $fields['not1_name']     ?? 'Étude notariale 1';
$not1Desc     = $fields['not1_desc']     ?? 'Spécialiste en droit immobilier et successions.';
$not1Phone    = $fields['not1_phone']    ?? '';
$not1Addr     = $fields['not1_addr']     ?? '';
$not2Name     = $fields['not2_name']     ?? 'Étude notariale 2';
$not2Desc     = $fields['not2_desc']     ?? 'Accompagnement personnalisé pour toutes vos transactions.';
$not2Phone    = $fields['not2_phone']    ?? '';
$not2Addr     = $fields['not2_addr']     ?? '';

// Diagnostiqueurs
$diagTitle    = $fields['diag_title']    ?? 'Diagnostiqueurs immobiliers';
$diagIcon     = $fields['diag_icon']     ?? '🔬';
$diag1Name    = $fields['diag1_name']    ?? 'Diagnostiqueur 1';
$diag1Desc    = $fields['diag1_desc']    ?? 'Diagnostics immobiliers complets : DPE, amiante, plomb, électricité, gaz.';
$diag1Phone   = $fields['diag1_phone']   ?? '';
$diag2Name    = $fields['diag2_name']    ?? 'Diagnostiqueur 2';
$diag2Desc    = $fields['diag2_desc']    ?? 'Certifié et réactif. Rapports sous 48h.';
$diag2Phone   = $fields['diag2_phone']   ?? '';

// Artisans
$artTitle     = $fields['art_title']     ?? 'Artisans & travaux';
$artIcon      = $fields['art_icon']      ?? '🔨';
$art1Name     = $fields['art1_name']     ?? 'Artisan 1';
$art1Desc     = $fields['art1_desc']     ?? 'Rénovation intérieure, peinture, sols et aménagements.';
$art1Metier   = $fields['art1_metier']   ?? 'Rénovation générale';
$art1Phone    = $fields['art1_phone']    ?? '';
$art2Name     = $fields['art2_name']     ?? 'Artisan 2';
$art2Desc     = $fields['art2_desc']     ?? 'Plomberie, chauffage et installation sanitaire.';
$art2Metier   = $fields['art2_metier']   ?? 'Plomberie / Chauffage';
$art2Phone    = $fields['art2_phone']    ?? '';
$art3Name     = $fields['art3_name']     ?? 'Artisan 3';
$art3Desc     = $fields['art3_desc']     ?? 'Électricité, mise aux normes et domotique.';
$art3Metier   = $fields['art3_metier']   ?? 'Électricité';
$art3Phone    = $fields['art3_phone']    ?? '';

// Autres partenaires
$autresTitle  = $fields['autres_title']  ?? 'Autres professionnels';
$autresIcon   = $fields['autres_icon']   ?? '🤝';
$autre1Name   = $fields['autre1_name']   ?? 'Professionnel 1';
$autre1Desc   = $fields['autre1_desc']   ?? 'Architecte d\'intérieur, home staging et valorisation immobilière.';
$autre1Metier = $fields['autre1_metier'] ?? 'Home staging';
$autre1Phone  = $fields['autre1_phone']  ?? '';
$autre2Name   = $fields['autre2_name']   ?? 'Professionnel 2';
$autre2Desc   = $fields['autre2_desc']   ?? 'Déménagement, nettoyage et services annexes.';
$autre2Metier = $fields['autre2_metier'] ?? 'Déménagement';
$autre2Phone  = $fields['autre2_phone']  ?? '';

// CTA
$ctaTitle     = $fields['cta_title']     ?? 'Besoin d\'une recommandation ?';
$ctaText      = $fields['cta_text']      ?? 'Contactez-moi pour une mise en relation personnalisée avec le bon professionnel pour votre projet.';
$ctaBtnText   = $fields['cta_btn_text']  ?? 'Me contacter';
$ctaBtnUrl    = $fields['cta_btn_url']   ?? '/contact';

// ════════════════════════════════════════════════
// CONTENU HTML
// ════════════════════════════════════════════════

ob_start();
require_once __DIR__ . '/_tpl-common.php';
?>

<style>
.t22-cat-header { display:flex; align-items:center; gap:14px; margin-bottom:28px; }
.t22-cat-icon { font-size:2rem; }
.t22-cat-title { font-family:var(--tp-ff-display); font-size:1.5rem; font-weight:800; color:var(--tp-primary); margin:0; }
.t22-partner { display:flex; gap:20px; align-items:start; }
.t22-partner-info { flex:1; }
.t22-partner-name { font-weight:800; font-size:1.05rem; color:var(--tp-primary); margin:0 0 6px; }
.t22-partner-desc { color:var(--tp-text2); font-size:.88rem; line-height:1.7; margin:0 0 8px; }
.t22-partner-meta { display:flex; flex-wrap:wrap; gap:12px; font-size:.82rem; color:var(--tp-text3); }
.t22-partner-meta a { color:var(--tp-accent-d); font-weight:700; }
.t22-metier-tag { display:inline-block; background:rgba(200,169,110,.12); color:var(--tp-accent-d); font-size:.75rem; font-weight:700; padding:3px 12px; border-radius:50px; text-transform:uppercase; letter-spacing:.04em; }
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

<!-- INTRO -->
<section class="tp-section-white">
    <div class="tp-container" style="max-width:760px; text-align:center;">
        <h2 <?= $editMode ? 'data-field="intro_title" class="ef-zone"' : '' ?>
            class="tp-section-title"><?= htmlspecialchars($introTitle) ?></h2>
        <p <?= $editMode ? 'data-field="intro_text" class="ef-zone"' : '' ?>
           style="color:var(--tp-text2); line-height:1.8; font-size:1rem;"><?= htmlspecialchars($introText) ?></p>
    </div>
</section>

<!-- NOTAIRES -->
<section class="tp-section-light">
    <div class="tp-container">
        <div class="t22-cat-header">
            <div class="t22-cat-icon" <?= $editMode ? 'data-field="not_icon" class="ef-zone"' : '' ?>><?= htmlspecialchars($notIcon) ?></div>
            <h2 <?= $editMode ? 'data-field="not_title" class="ef-zone"' : '' ?>
                class="t22-cat-title"><?= htmlspecialchars($notTitle) ?></h2>
        </div>
        <div class="tp-grid-2">
            <?php for ($i = 1; $i <= 2; $i++):
                $nName  = ${'not'.$i.'Name'};
                $nDesc  = ${'not'.$i.'Desc'};
                $nPhone = ${'not'.$i.'Phone'};
                $nAddr  = ${'not'.$i.'Addr'};
            ?>
            <div class="tp-card">
                <div class="t22-partner">
                    <div class="t22-partner-info">
                        <h3 <?= $editMode ? 'data-field="not'.$i.'_name" class="ef-zone"' : '' ?>
                            class="t22-partner-name"><?= htmlspecialchars($nName) ?></h3>
                        <p <?= $editMode ? 'data-field="not'.$i.'_desc" class="ef-zone"' : '' ?>
                           class="t22-partner-desc"><?= htmlspecialchars($nDesc) ?></p>
                        <div class="t22-partner-meta">
                            <?php if ($nPhone): ?>
                            <a href="tel:<?= htmlspecialchars(str_replace(' ', '', $nPhone)) ?>"
                               <?= $editMode ? 'data-field="not'.$i.'_phone"' : '' ?>>📞 <?= htmlspecialchars($nPhone) ?></a>
                            <?php endif; ?>
                            <?php if ($nAddr): ?>
                            <span <?= $editMode ? 'data-field="not'.$i.'_addr" class="ef-zone"' : '' ?>>📍 <?= htmlspecialchars($nAddr) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- DIAGNOSTIQUEURS -->
<section class="tp-section-white">
    <div class="tp-container">
        <div class="t22-cat-header">
            <div class="t22-cat-icon" <?= $editMode ? 'data-field="diag_icon" class="ef-zone"' : '' ?>><?= htmlspecialchars($diagIcon) ?></div>
            <h2 <?= $editMode ? 'data-field="diag_title" class="ef-zone"' : '' ?>
                class="t22-cat-title"><?= htmlspecialchars($diagTitle) ?></h2>
        </div>
        <div class="tp-grid-2">
            <?php for ($i = 1; $i <= 2; $i++):
                $dName  = ${'diag'.$i.'Name'};
                $dDesc  = ${'diag'.$i.'Desc'};
                $dPhone = ${'diag'.$i.'Phone'};
            ?>
            <div class="tp-card">
                <div class="t22-partner">
                    <div class="t22-partner-info">
                        <h3 <?= $editMode ? 'data-field="diag'.$i.'_name" class="ef-zone"' : '' ?>
                            class="t22-partner-name"><?= htmlspecialchars($dName) ?></h3>
                        <p <?= $editMode ? 'data-field="diag'.$i.'_desc" class="ef-zone"' : '' ?>
                           class="t22-partner-desc"><?= htmlspecialchars($dDesc) ?></p>
                        <?php if ($dPhone): ?>
                        <div class="t22-partner-meta">
                            <a href="tel:<?= htmlspecialchars(str_replace(' ', '', $dPhone)) ?>"
                               <?= $editMode ? 'data-field="diag'.$i.'_phone"' : '' ?>>📞 <?= htmlspecialchars($dPhone) ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- ARTISANS -->
<section class="tp-section-light">
    <div class="tp-container">
        <div class="t22-cat-header">
            <div class="t22-cat-icon" <?= $editMode ? 'data-field="art_icon" class="ef-zone"' : '' ?>><?= htmlspecialchars($artIcon) ?></div>
            <h2 <?= $editMode ? 'data-field="art_title" class="ef-zone"' : '' ?>
                class="t22-cat-title"><?= htmlspecialchars($artTitle) ?></h2>
        </div>
        <div class="tp-grid-3">
            <?php for ($i = 1; $i <= 3; $i++):
                $aName   = ${'art'.$i.'Name'};
                $aDesc   = ${'art'.$i.'Desc'};
                $aMetier = ${'art'.$i.'Metier'};
                $aPhone  = ${'art'.$i.'Phone'};
            ?>
            <div class="tp-card">
                <div class="t22-metier-tag" <?= $editMode ? 'data-field="art'.$i.'_metier" class="ef-zone"' : '' ?>><?= htmlspecialchars($aMetier) ?></div>
                <h3 <?= $editMode ? 'data-field="art'.$i.'_name" class="ef-zone"' : '' ?>
                    class="t22-partner-name" style="margin-top:12px;"><?= htmlspecialchars($aName) ?></h3>
                <p <?= $editMode ? 'data-field="art'.$i.'_desc" class="ef-zone"' : '' ?>
                   class="t22-partner-desc"><?= htmlspecialchars($aDesc) ?></p>
                <?php if ($aPhone): ?>
                <div class="t22-partner-meta">
                    <a href="tel:<?= htmlspecialchars(str_replace(' ', '', $aPhone)) ?>"
                       <?= $editMode ? 'data-field="art'.$i.'_phone"' : '' ?>>📞 <?= htmlspecialchars($aPhone) ?></a>
                </div>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- AUTRES PARTENAIRES -->
<section class="tp-section-white">
    <div class="tp-container">
        <div class="t22-cat-header">
            <div class="t22-cat-icon" <?= $editMode ? 'data-field="autres_icon" class="ef-zone"' : '' ?>><?= htmlspecialchars($autresIcon) ?></div>
            <h2 <?= $editMode ? 'data-field="autres_title" class="ef-zone"' : '' ?>
                class="t22-cat-title"><?= htmlspecialchars($autresTitle) ?></h2>
        </div>
        <div class="tp-grid-2">
            <?php for ($i = 1; $i <= 2; $i++):
                $oName   = ${'autre'.$i.'Name'};
                $oDesc   = ${'autre'.$i.'Desc'};
                $oMetier = ${'autre'.$i.'Metier'};
                $oPhone  = ${'autre'.$i.'Phone'};
            ?>
            <div class="tp-card">
                <div class="t22-metier-tag" <?= $editMode ? 'data-field="autre'.$i.'_metier" class="ef-zone"' : '' ?>><?= htmlspecialchars($oMetier) ?></div>
                <h3 <?= $editMode ? 'data-field="autre'.$i.'_name" class="ef-zone"' : '' ?>
                    class="t22-partner-name" style="margin-top:12px;"><?= htmlspecialchars($oName) ?></h3>
                <p <?= $editMode ? 'data-field="autre'.$i.'_desc" class="ef-zone"' : '' ?>
                   class="t22-partner-desc"><?= htmlspecialchars($oDesc) ?></p>
                <?php if ($oPhone): ?>
                <div class="t22-partner-meta">
                    <a href="tel:<?= htmlspecialchars(str_replace(' ', '', $oPhone)) ?>"
                       <?= $editMode ? 'data-field="autre'.$i.'_phone"' : '' ?>>📞 <?= htmlspecialchars($oPhone) ?></a>
                </div>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

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
