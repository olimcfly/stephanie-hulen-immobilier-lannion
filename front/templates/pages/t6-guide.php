<?php
/**
 * /front/templates/pages/t6-guide.php
 * Template Guide Local — CONVERTI pour layout-page.php
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

$advisorName = $advisor['name'] ?? ($site['name'] ?? 'Conseiller');
$advisorCity = $advisor['city'] ?? ($site['city'] ?? 'Ville');
require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

// ════════════════════════════════════════════════
// CHAMPS SPÉCIFIQUES
// ════════════════════════════════════════════════

$guideTitle   = $fields['guide_title']   ?? 'Guide local de ' . htmlspecialchars($advisorCity);
$guideIntro   = $fields['guide_intro']   ?? 'Découvrez ' . htmlspecialchars($advisorCity) . ' comme jamais auparavant';
$guideContent = $fields['guide_content'] ?? 'Contenu du guide local';

$section1Title = $fields['section1_title'] ?? 'Informations pratiques';
$section1Text  = $fields['section1_text']  ?? 'Tout ce que vous devez savoir sur la ville.';

$section2Title = $fields['section2_title'] ?? 'Vivre à ' . htmlspecialchars($advisorCity);
$section2Text  = $fields['section2_text']  ?? 'Découvrez la qualité de vie et les avantages de résider dans notre belle région.';

// ════════════════════════════════════════════════
// CONTENU HTML
// ════════════════════════════════════════════════

ob_start();
?>

<!-- HERO -->
<section class="tp-section-hero" style="background: linear-gradient(135deg, #1a4d7a 0%, #0f3a5a 100%); color: white; padding: 80px 20px; text-align: center;">
    <div class="tp-container">
        <h1 <?= $editMode ? 'data-field="guide_title" class="ef-zone"' : '' ?> style="font-size: 3rem; margin-bottom: 20px; font-weight: bold;">
            <?= htmlspecialchars($guideTitle) ?>
        </h1>
        <p <?= $editMode ? 'data-field="guide_intro" class="ef-zone"' : '' ?> style="font-size: 1.3rem; margin-bottom: 30px; opacity: 0.95;">
            <?= htmlspecialchars($guideIntro) ?>
        </p>
    </div>
</section>

<!-- CONTENU PRINCIPAL -->
<section class="tp-section-white" style="background: white; padding: 80px 20px;">
    <div class="tp-container" style="max-width: 900px; margin: 0 auto;">
        <div <?= $editMode ? 'data-field="guide_content" class="ef-zone"' : '' ?> style="line-height: 1.8; color: #666; font-size: 1.05rem; margin-bottom: 60px;">
            <?= nl2br(htmlspecialchars($guideContent)) ?>
        </div>
    </div>
</section>

<!-- SECTION 1 -->
<section class="tp-section-light" style="background: #f9f6f3; padding: 60px 20px;">
    <div class="tp-container" style="max-width: 900px; margin: 0 auto;">
        <h2 <?= $editMode ? 'data-field="section1_title" class="ef-zone"' : '' ?> style="font-size: 2rem; color: #1a4d7a; margin-bottom: 15px;">
            <?= htmlspecialchars($section1Title) ?>
        </h2>
        <p <?= $editMode ? 'data-field="section1_text" class="ef-zone"' : '' ?> style="color: #666; line-height: 1.8;">
            <?= htmlspecialchars($section1Text) ?>
        </p>
    </div>
</section>

<!-- SECTION 2 -->
<section class="tp-section-white" style="background: white; padding: 60px 20px;">
    <div class="tp-container" style="max-width: 900px; margin: 0 auto;">
        <h2 <?= $editMode ? 'data-field="section2_title" class="ef-zone"' : '' ?> style="font-size: 2rem; color: #1a4d7a; margin-bottom: 15px;">
            <?= htmlspecialchars($section2Title) ?>
        </h2>
        <p <?= $editMode ? 'data-field="section2_text" class="ef-zone"' : '' ?> style="color: #666; line-height: 1.8;">
            <?= htmlspecialchars($section2Text) ?>
        </p>
    </div>
</section>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>