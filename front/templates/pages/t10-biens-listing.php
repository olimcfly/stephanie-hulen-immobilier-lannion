<?php
/**
 * /front/templates/pages/t8-contact.php
 * Template Contact — CONVERTI pour layout-page.php
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

$heroTitle      = $fields['hero_title']      ?? 'Contactez-moi';
$heroSubtitle   = $fields['hero_subtitle']   ?? 'Je suis à votre écoute pour discuter de vos projets immobiliers';

$formTitle      = $fields['form_title']      ?? 'Formulaire de contact';
$formText       = $fields['form_text']       ?? 'Remplissez le formulaire ci-dessous et je vous recontacterai dans les plus brefs délais.';

$contactMethod1 = $fields['contact_method1'] ?? '📞 Par téléphone';
$contactPhone   = $fields['contact_phone']   ?? $advisorPhone;

$contactMethod2 = $fields['contact_method2'] ?? '📧 Par email';
$contactEmail   = $fields['contact_email']   ?? $advisorEmail;

$contactMethod3 = $fields['contact_method3'] ?? '📍 En personne';
$contactAddress = $fields['contact_address'] ?? 'Bureau local';

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
    </div>
</section>

<!-- FORMULAIRE + INFOS -->
<section class="tp-section-white" style="background: white; padding: 80px 20px;">
    <div class="tp-container" style="max-width: 1100px; margin: 0 auto;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: start;">
            
            <!-- FORMULAIRE -->
            <div>
                <h2 <?= $editMode ? 'data-field="form_title" class="ef-zone"' : '' ?> style="font-size: 1.8rem; color: #1a4d7a; margin-bottom: 15px;">
                    <?= htmlspecialchars($formTitle) ?>
                </h2>
                <p <?= $editMode ? 'data-field="form_text" class="ef-zone"' : '' ?> style="color: #666; margin-bottom: 30px; line-height: 1.6;">
                    <?= htmlspecialchars($formText) ?>
                </p>
                
                <!-- FORMULAIRE PLACEHOLDER -->
                <div style="background: #f9f6f3; padding: 40px; border-radius: 8px; border: 2px dashed #d4a574;">
                    <p style="color: #999; margin: 0; text-align: center;">
                        [Formulaire de contact sera affiché ici]
                    </p>
                </div>
            </div>
            
            <!-- INFOS CONTACT -->
            <div>
                <h3 style="font-size: 1.8rem; color: #1a4d7a; margin-bottom: 30px;">Autres moyens de me contacter</h3>
                
                <!-- MÉTHODE 1 : TÉLÉPHONE -->
                <div style="padding: 20px; margin-bottom: 20px; background: #f9f6f3; border-radius: 8px; border-left: 4px solid #d4a574;">
                    <h4 <?= $editMode ? 'data-field="contact_method1" class="ef-zone"' : '' ?> style="color: #1a4d7a; margin-bottom: 10px; font-size: 1.1rem;">
                        <?= htmlspecialchars($contactMethod1) ?>
                    </h4>
                    <p style="color: #666; margin: 0;">
                        <a href="tel:<?= htmlspecialchars(str_replace(' ', '', $contactPhone)) ?>" style="color: #d4a574; text-decoration: none; font-weight: 600;">
                            <?php if ($editMode): ?>
                                <span data-field="contact_phone" class="ef-zone"><?= htmlspecialchars($contactPhone) ?></span>
                            <?php else: ?>
                                <?= htmlspecialchars($contactPhone) ?>
                            <?php endif; ?>
                        </a>
                    </p>
                </div>
                
                <!-- MÉTHODE 2 : EMAIL -->
                <div style="padding: 20px; margin-bottom: 20px; background: #f9f6f3; border-radius: 8px; border-left: 4px solid #d4a574;">
                    <h4 <?= $editMode ? 'data-field="contact_method2" class="ef-zone"' : '' ?> style="color: #1a4d7a; margin-bottom: 10px; font-size: 1.1rem;">
                        <?= htmlspecialchars($contactMethod2) ?>
                    </h4>
                    <p style="color: #666; margin: 0;">
                        <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" style="color: #d4a574; text-decoration: none; font-weight: 600;">
                            <?php if ($editMode): ?>
                                <span data-field="contact_email" class="ef-zone"><?= htmlspecialchars($contactEmail) ?></span>
                            <?php else: ?>
                                <?= htmlspecialchars($contactEmail) ?>
                            <?php endif; ?>
                        </a>
                    </p>
                </div>
                
                <!-- MÉTHODE 3 : EN PERSONNE -->
                <div style="padding: 20px; background: #f9f6f3; border-radius: 8px; border-left: 4px solid #d4a574;">
                    <h4 <?= $editMode ? 'data-field="contact_method3" class="ef-zone"' : '' ?> style="color: #1a4d7a; margin-bottom: 10px; font-size: 1.1rem;">
                        <?= htmlspecialchars($contactMethod3) ?>
                    </h4>
                    <p <?= $editMode ? 'data-field="contact_address" class="ef-zone"' : '' ?> style="color: #666; margin: 0;">
                        <?= htmlspecialchars($contactAddress) ?>
                    </p>
                </div>
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