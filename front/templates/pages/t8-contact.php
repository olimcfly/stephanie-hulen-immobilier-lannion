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
$advisorPhone = $advisor['phone'] ?? '02 96 00 00 00';
$advisorEmail = $advisor['email'] ?? 'contact@stephanie-hulen-immobilier-lannion.fr';
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
$contactAddress = $fields['contact_address'] ?? '2 rue Saint-Nicolas, 22300 Lannion';

// ════════════════════════════════════════════════
// TRAITEMENT DU FORMULAIRE DE CONTACT
// ════════════════════════════════════════════════
$contactFormError   = '';
$contactFormSuccess = false;

if (session_status() === PHP_SESSION_NONE) session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_contact_form'])) {
    // CSRF check
    if (empty($_POST['_csrf']) || $_POST['_csrf'] !== ($_SESSION['csrf_contact'] ?? '')) {
        $contactFormError = 'Token de sécurité invalide. Rechargez la page.';
    }
    // Honeypot check
    elseif (!empty($_POST['_hp_website'])) {
        $contactFormError = 'Soumission invalide.';
    } else {
        $prenom   = trim($_POST['prenom']   ?? '');
        $nom      = trim($_POST['nom']      ?? '');
        $email    = trim($_POST['email']    ?? '');
        $phone    = trim($_POST['phone']    ?? '');
        $message  = trim($_POST['message']  ?? '');
        $consent  = !empty($_POST['gdpr_consent']);

        $errors = [];
        if (empty($prenom))  $errors[] = 'Le prénom est obligatoire.';
        if (empty($nom))     $errors[] = 'Le nom est obligatoire.';
        if (empty($email))   $errors[] = "L'email est obligatoire.";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Adresse email invalide.';
        if (empty($message)) $errors[] = 'Le message est obligatoire.';
        if (!$consent)       $errors[] = 'Vous devez accepter la politique de confidentialité.';

        if (!empty($errors)) {
            $contactFormError = implode(' ', $errors);
        } else {
            // Insert into leads table
            if (isset($pdo)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO leads (firstname, lastname, email, phone, notes, source, type, temperature, gdpr_consent, created_at) VALUES (?, ?, ?, ?, ?, 'site_web', 'contact', 'warm', ?, NOW())");
                    $stmt->execute([$prenom, $nom, $email, $phone ?: null, $message, $consent ? 1 : 0]);
                    $contactFormSuccess = true;
                } catch (PDOException $e) {
                    $contactFormError = 'Une erreur est survenue. Veuillez réessayer plus tard.';
                }
            } else {
                $contactFormError = 'Service temporairement indisponible.';
            }
        }
    }
}

// Generate CSRF token
if (empty($_SESSION['csrf_contact'])) {
    $_SESSION['csrf_contact'] = bin2hex(random_bytes(24));
}
$csrfToken = $_SESSION['csrf_contact'];
// Reset token on successful submission so next form gets a fresh one
if ($contactFormSuccess) {
    unset($_SESSION['csrf_contact']);
}

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
                
                <?php if ($contactFormSuccess): ?>
                <!-- SUCCÈS -->
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 30px; text-align: center;">
                    <div style="width: 52px; height: 52px; background: #22c55e; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 22px; color: #fff;">✓</div>
                    <h3 style="font-size: 1.2rem; font-weight: 700; color: #166534; margin: 0 0 8px;">Votre message a bien été envoyé !</h3>
                    <p style="font-size: 0.95rem; color: #15803d; margin: 0;">Je vous recontacterai dans les plus brefs délais.</p>
                </div>
                <?php else: ?>

                <?php if ($contactFormError): ?>
                <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 12px 14px; font-size: 0.9rem; color: #dc2626; font-weight: 600; margin-bottom: 20px;">
                    ⚠ <?= htmlspecialchars($contactFormError) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="" style="background: #f9f6f3; padding: 30px; border-radius: 8px;">
                    <input type="hidden" name="_contact_form" value="1">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                    <!-- Honeypot -->
                    <div style="display: none !important;" aria-hidden="true">
                        <input type="text" name="_hp_website" tabindex="-1" autocomplete="off">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label for="contact_prenom" style="display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                Prénom <span style="color: #dc2626;">*</span>
                            </label>
                            <input type="text" id="contact_prenom" name="prenom" required
                                   value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
                                   placeholder="Votre prénom"
                                   style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; background: #fff;">
                        </div>
                        <div>
                            <label for="contact_nom" style="display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                Nom <span style="color: #dc2626;">*</span>
                            </label>
                            <input type="text" id="contact_nom" name="nom" required
                                   value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
                                   placeholder="Votre nom"
                                   style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; background: #fff;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                        <div>
                            <label for="contact_email" style="display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                Email <span style="color: #dc2626;">*</span>
                            </label>
                            <input type="email" id="contact_email" name="email" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   placeholder="votre@email.fr"
                                   style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; background: #fff;">
                        </div>
                        <div>
                            <label for="contact_phone" style="display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                Téléphone
                            </label>
                            <input type="tel" id="contact_phone" name="phone"
                                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                                   placeholder="06 00 00 00 00"
                                   pattern="[0-9\s\+\-\(\)\.]{6,20}"
                                   style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; background: #fff;">
                        </div>
                    </div>

                    <div style="margin-bottom: 15px;">
                        <label for="contact_message" style="display: block; font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 6px;">
                            Votre message <span style="color: #dc2626;">*</span>
                        </label>
                        <textarea id="contact_message" name="message" required rows="5"
                                  placeholder="Décrivez votre projet immobilier ou posez-moi vos questions..."
                                  style="width: 100%; padding: 11px 14px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.95rem; background: #fff; resize: vertical; font-family: inherit;"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 0.8rem; color: #6b7280; cursor: pointer;">
                            <input type="checkbox" name="gdpr_consent" value="1" required
                                   <?= !empty($_POST['gdpr_consent']) ? 'checked' : '' ?>
                                   style="margin-top: 3px;">
                            <span>J'accepte que mes données soient traitées dans le cadre de ma demande de contact. <span style="color: #dc2626;">*</span></span>
                        </label>
                    </div>

                    <button type="submit"
                            style="width: 100%; padding: 14px 20px; background: #d4a574; color: #fff; border: none; border-radius: 8px; font-size: 1rem; font-weight: 700; cursor: pointer; font-family: inherit; transition: all 0.15s; box-shadow: 0 4px 12px rgba(212,165,116,0.3);"
                            onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        Envoyer mon message
                    </button>

                    <p style="font-size: 0.7rem; color: #9ca3af; margin-top: 12px; display: flex; align-items: flex-start; gap: 6px; line-height: 1.4;">
                        🔒 Vos informations restent confidentielles et ne seront jamais partagées.
                    </p>
                </form>
                <?php endif; ?>
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