<?php
/**
 * /front/templates/pages/t7-estimation.php
 * Template Estimation — CONVERTI pour layout-page.php
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

$advisorName  = $advisor['name']  ?? ($site['name']  ?? 'Conseiller');
$advisorPhone = $advisor['phone'] ?? '';
$advisorEmail = $advisor['email'] ?? '';
require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

// ════════════════════════════════════════════════
// CHAMPS SPÉCIFIQUES
// ════════════════════════════════════════════════

$heroTitle      = $fields['hero_title']      ?? 'Estimez votre bien gratuitement';
$heroSubtitle   = $fields['hero_subtitle']   ?? 'Obtenez une estimation précise en quelques minutes';
$heroCtaUrl     = $fields['hero_cta_url']    ?? '/estimation';
$heroCtaText    = $fields['hero_cta_text']   ?? 'Estimer mon bien';

$formTitle      = $fields['form_title']      ?? 'Formulaire d\'estimation';
$formText       = $fields['form_text']       ?? 'Remplissez les informations de votre bien pour obtenir une estimation gratuite et confidentielle en quelques minutes.';
$formNote       = $fields['form_note']       ?? 'Vos données restent confidentielles et ne seront pas partagées.';

$benefitTitle   = $fields['benefit_title']   ?? 'Pourquoi nous faire confiance ?';
$benefit1       = $fields['benefit1']        ?? '✓ Estimation gratuite et sans engagement';
$benefit2       = $fields['benefit2']        ?? '✓ Résultats immédiats en ligne';
$benefit3       = $fields['benefit3']        ?? '✓ Données confidentielles et sécurisées';
$benefit4       = $fields['benefit4']        ?? '✓ Expertise de ' . htmlspecialchars($advisorName);

// ════════════════════════════════════════════════
// CONTENU HTML
// ════════════════════════════════════════════════

ob_start();
?>

<!-- HERO -->
<section class="tp-section-hero" style="background: linear-gradient(135deg, #1a4d7a 0%, #0f3a5a 100%); color: white; padding: 80px 20px; text-align: center;">
    <div class="tp-container">
        <h1 <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?> style="font-size: 3rem; margin-bottom: 20px; font-weight: bold;">
            <?= htmlspecialchars($heroTitle) ?>
        </h1>
        <p <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?> style="font-size: 1.3rem; margin-bottom: 30px; opacity: 0.95;">
            <?= htmlspecialchars($heroSubtitle) ?>
        </p>
        <a href="<?= htmlspecialchars($heroCtaUrl) ?>" class="tp-btn-primary" style="background: #d4a574; color: white; padding: 15px 40px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-block;">
            <?= htmlspecialchars($heroCtaText) ?>
        </a>
    </div>
</section>

<!-- FORMULAIRE -->
<section class="tp-section-white" style="background: white; padding: 80px 20px;">
    <div class="tp-container" style="max-width: 700px; margin: 0 auto;">
        <h2 <?= $editMode ? 'data-field="form_title" class="ef-zone"' : '' ?> style="font-size: 2rem; color: #1a4d7a; margin-bottom: 15px; text-align: center;">
            <?= htmlspecialchars($formTitle) ?>
        </h2>
        <p <?= $editMode ? 'data-field="form_text" class="ef-zone"' : '' ?> style="color: #666; margin-bottom: 30px; text-align: center; line-height: 1.6;">
            <?= htmlspecialchars($formText) ?>
        </p>
        
        <!-- FORMULAIRE PLACEHOLDER -->
        <div style="background: #f9f6f3; padding: 40px; border-radius: 8px; border: 2px dashed #d4a574; text-align: center; margin-bottom: 20px;">
            <p style="color: #999; margin: 0;">
                [Formulaire d\'estimation interactif sera affiché ici]
            </p>
        </div>
        
        <!-- NOTE -->
        <p <?= $editMode ? 'data-field="form_note" class="ef-zone"' : '' ?> style="color: #999; font-size: 0.85rem; text-align: center;">
            <?= htmlspecialchars($formNote) ?>
        </p>
    </div>
</section>

<!-- BENEFITS -->
<section class="tp-section-light" style="background: #f9f6f3; padding: 60px 20px;">
    <div class="tp-container" style="max-width: 800px; margin: 0 auto;">
        <h2 <?= $editMode ? 'data-field="benefit_title" class="ef-zone"' : '' ?> style="font-size: 2rem; color: #1a4d7a; margin-bottom: 40px; text-align: center;">
            <?= htmlspecialchars($benefitTitle) ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            <div style="padding: 20px; background: white; border-radius: 8px; border-left: 4px solid #d4a574;">
                <p <?= $editMode ? 'data-field="benefit1" class="ef-zone"' : '' ?> style="color: #1a4d7a; font-size: 1rem; margin: 0;">
                    <?= htmlspecialchars($benefit1) ?>
                </p>
            </div>
            <div style="padding: 20px; background: white; border-radius: 8px; border-left: 4px solid #d4a574;">
                <p <?= $editMode ? 'data-field="benefit2" class="ef-zone"' : '' ?> style="color: #1a4d7a; font-size: 1rem; margin: 0;">
                    <?= htmlspecialchars($benefit2) ?>
                </p>
            </div>
            <div style="padding: 20px; background: white; border-radius: 8px; border-left: 4px solid #d4a574;">
                <p <?= $editMode ? 'data-field="benefit3" class="ef-zone"' : '' ?> style="color: #1a4d7a; font-size: 1rem; margin: 0;">
                    <?= htmlspecialchars($benefit3) ?>
                </p>
            </div>
            <div style="padding: 20px; background: white; border-radius: 8px; border-left: 4px solid #d4a574;">
                <p <?= $editMode ? 'data-field="benefit4" class="ef-zone"' : '' ?> style="color: #1a4d7a; font-size: 1rem; margin: 0;">
                    <?= htmlspecialchars($benefit4) ?>
                </p>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>