<?php
/**
 * /front/templates/pages/t7-estimation.php
 * Template Estimation — CONVERTI pour layout-page.php
 * Formulaire interactif multi-étapes connecté à la table estimations
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

$advisorName  = $advisor['name']  ?? ($site['name']  ?? 'Stéphanie Hulen');
$advisorPhone = $advisor['phone'] ?? '';
$advisorEmail = $advisor['email'] ?? '';
require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

// CSRF token
$csrfToken = $_SESSION['csrf_token'] ?? '';

// ════════════════════════════════════════════════
// CHAMPS SPÉCIFIQUES — Personnalisés pour Lannion
// ════════════════════════════════════════════════

$heroTitle      = $fields['hero_title']      ?? 'Estimez votre bien à Lannion';
$heroSubtitle   = $fields['hero_subtitle']   ?? 'Obtenez une estimation gratuite et précise de votre bien immobilier à Lannion et ses environs';
$heroCtaUrl     = $fields['hero_cta_url']    ?? '#estimation-form';
$heroCtaText    = $fields['hero_cta_text']   ?? 'Estimer mon bien';

$formTitle      = $fields['form_title']      ?? 'Estimez votre bien à Lannion';
$formText       = $fields['form_text']       ?? 'Remplissez les informations de votre bien pour recevoir une estimation gratuite et confidentielle. Notre connaissance du marché lannionnais nous permet de vous apporter une réponse fiable et rapide.';
$formNote       = $fields['form_note']       ?? 'Vos données restent confidentielles et ne seront jamais partagées avec des tiers.';

$benefitTitle   = $fields['benefit_title']   ?? 'Pourquoi nous faire confiance à Lannion ?';
$benefit1       = $fields['benefit1']        ?? '✓ Estimation gratuite, sans engagement et confidentielle';
$benefit2       = $fields['benefit2']        ?? '✓ Connaissance approfondie du marché immobilier lannionnais';
$benefit3       = $fields['benefit3']        ?? '✓ Réponse rapide sous 48h avec analyse personnalisée';
$benefit4       = $fields['benefit4']        ?? '✓ Accompagnement sur-mesure par ' . htmlspecialchars($advisorName);

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

<!-- FORMULAIRE D'ESTIMATION -->
<section id="estimation-form" class="tp-section-white" style="background: white; padding: 80px 20px;">
    <div class="tp-container" style="max-width: 700px; margin: 0 auto;">
        <h2 <?= $editMode ? 'data-field="form_title" class="ef-zone"' : '' ?> style="font-size: 2rem; color: #1a4d7a; margin-bottom: 15px; text-align: center;">
            <?= htmlspecialchars($formTitle) ?>
        </h2>
        <p <?= $editMode ? 'data-field="form_text" class="ef-zone"' : '' ?> style="color: #666; margin-bottom: 30px; text-align: center; line-height: 1.6;">
            <?= htmlspecialchars($formText) ?>
        </p>

        <!-- Barre de progression -->
        <div id="estimation-progress" style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 30px;">
            <div class="est-progress-step est-progress-active" data-step="1" style="display: flex; align-items: center; gap: 8px;">
                <span style="width: 32px; height: 32px; border-radius: 50%; background: #1a4d7a; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem;">1</span>
                <span style="font-size: 0.85rem; color: #1a4d7a; font-weight: 600;">Votre bien</span>
            </div>
            <div style="width: 40px; height: 2px; background: #ddd;"></div>
            <div class="est-progress-step" data-step="2" style="display: flex; align-items: center; gap: 8px;">
                <span style="width: 32px; height: 32px; border-radius: 50%; background: #ddd; color: #999; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem;">2</span>
                <span style="font-size: 0.85rem; color: #999; font-weight: 600;">Vos coordonnées</span>
            </div>
        </div>

        <!-- ÉTAPE 1 : Informations sur le bien -->
        <div id="est-step-1" class="est-step">
            <div style="background: #f9f6f3; padding: 30px; border-radius: 12px; margin-bottom: 20px;">

                <div style="margin-bottom: 20px;">
                    <label for="est-adresse" style="display: block; font-weight: 600; color: #1a4d7a; margin-bottom: 6px; font-size: 0.95rem;">Adresse du bien *</label>
                    <input type="text" id="est-adresse" name="adresse" placeholder="Ex : 12 Rue des Chapeliers, Lannion" required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; transition: border-color 0.3s;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div>
                        <label for="est-ville" style="display: block; font-weight: 600; color: #1a4d7a; margin-bottom: 6px; font-size: 0.95rem;">Ville *</label>
                        <input type="text" id="est-ville" name="ville" value="Lannion" required
                            style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label for="est-code-postal" style="display: block; font-weight: 600; color: #1a4d7a; margin-bottom: 6px; font-size: 0.95rem;">Code postal *</label>
                        <input type="text" id="est-code-postal" name="code_postal" value="22300" maxlength="5" required
                            style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="est-type-bien" style="display: block; font-weight: 600; color: #1a4d7a; margin-bottom: 6px; font-size: 0.95rem;">Type de bien *</label>
                    <select id="est-type-bien" name="type_bien" required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; background: white;">
                        <option value="">-- Sélectionnez --</option>
                        <option value="maison">Maison</option>
                        <option value="appartement">Appartement</option>
                        <option value="terrain">Terrain</option>
                        <option value="commerce">Local commercial</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div>
                        <label for="est-surface" style="display: block; font-weight: 600; color: #1a4d7a; margin-bottom: 6px; font-size: 0.95rem;">Surface (m²) *</label>
                        <input type="number" id="est-surface" name="surface" placeholder="Ex : 85" min="1" required
                            style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                    </div>
                    <div>
                        <label for="est-pieces" style="display: block; font-weight: 600; color: #1a4d7a; margin-bottom: 6px; font-size: 0.95rem;">Nombre de pièces *</label>
                        <input type="number" id="est-pieces" name="pieces" placeholder="Ex : 4" min="1" required
                            style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                    </div>
                </div>

                <div style="margin-bottom: 0;">
                    <label for="est-etat-bien" style="display: block; font-weight: 600; color: #1a4d7a; margin-bottom: 6px; font-size: 0.95rem;">État du bien *</label>
                    <select id="est-etat-bien" name="etat_bien" required
                        style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box; background: white;">
                        <option value="">-- Sélectionnez --</option>
                        <option value="neuf">Neuf / Très bon état</option>
                        <option value="bon">Bon état</option>
                        <option value="moyen">État moyen</option>
                        <option value="renovation">À rénover</option>
                    </select>
                </div>
            </div>

            <div style="text-align: center;">
                <button type="button" id="est-btn-next" onclick="estimationNextStep()"
                    style="background: #d4a574; color: white; padding: 14px 50px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s;">
                    Continuer
                </button>
            </div>
        </div>

        <!-- ÉTAPE 2 : Coordonnées -->
        <div id="est-step-2" class="est-step" style="display: none;">
            <form id="estimationForm" action="/front/api/estimation-submit.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <!-- Champs cachés pour les données de l'étape 1 -->
                <input type="hidden" id="est-h-adresse" name="adresse">
                <input type="hidden" id="est-h-ville" name="ville">
                <input type="hidden" id="est-h-code-postal" name="code_postal">
                <input type="hidden" id="est-h-type-bien" name="type_bien">
                <input type="hidden" id="est-h-surface" name="surface">
                <input type="hidden" id="est-h-pieces" name="pieces">
                <input type="hidden" id="est-h-etat-bien" name="etat_bien">

                <div style="background: #f9f6f3; padding: 30px; border-radius: 12px; margin-bottom: 20px;">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                        <div>
                            <label for="est-nom" style="display: block; font-weight: 600; color: #1a4d7a; margin-bottom: 6px; font-size: 0.95rem;">Nom *</label>
                            <input type="text" id="est-nom" name="nom" placeholder="Votre nom" required
                                style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                        <div>
                            <label for="est-prenom" style="display: block; font-weight: 600; color: #1a4d7a; margin-bottom: 6px; font-size: 0.95rem;">Prénom *</label>
                            <input type="text" id="est-prenom" name="prenom" placeholder="Votre prénom" required
                                style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label for="est-email" style="display: block; font-weight: 600; color: #1a4d7a; margin-bottom: 6px; font-size: 0.95rem;">Email *</label>
                        <input type="email" id="est-email" name="email" placeholder="votre@email.com" required
                            style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 20px;">
                        <label for="est-telephone" style="display: block; font-weight: 600; color: #1a4d7a; margin-bottom: 6px; font-size: 0.95rem;">Téléphone *</label>
                        <input type="tel" id="est-telephone" name="telephone" placeholder="06 12 34 56 78" required
                            style="width: 100%; padding: 12px 16px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.95rem; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 0;">
                        <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; font-size: 0.85rem; color: #666; line-height: 1.5;">
                            <input type="checkbox" id="est-rgpd" name="rgpd_consent" value="1" required style="margin-top: 3px; flex-shrink: 0;">
                            J'accepte que mes données soient utilisées pour traiter ma demande d'estimation conformément à la politique de confidentialité. Elles ne seront jamais partagées avec des tiers.
                        </label>
                    </div>
                </div>

                <!-- Erreur -->
                <div id="est-error" style="display: none; background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem;"></div>

                <div style="display: flex; gap: 12px; justify-content: center;">
                    <button type="button" onclick="estimationPrevStep()"
                        style="background: white; color: #1a4d7a; padding: 14px 30px; border: 2px solid #1a4d7a; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s;">
                        Retour
                    </button>
                    <button type="submit" id="est-btn-submit"
                        style="background: #d4a574; color: white; padding: 14px 40px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.3s;">
                        Envoyer ma demande
                    </button>
                </div>
            </form>
        </div>

        <!-- MESSAGE DE SUCCÈS -->
        <div id="est-success" style="display: none; text-align: center; padding: 40px 20px;">
            <div style="width: 64px; height: 64px; background: #d4edda; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 2rem;">
                &#10003;
            </div>
            <h3 style="color: #1a4d7a; font-size: 1.5rem; margin-bottom: 12px;">Demande envoyée avec succès !</h3>
            <p style="color: #666; line-height: 1.6; max-width: 500px; margin: 0 auto;">
                Merci pour votre demande d'estimation. Nous vous recontacterons rapidement avec une analyse personnalisée de votre bien à Lannion.
            </p>
        </div>

        <!-- NOTE -->
        <p <?= $editMode ? 'data-field="form_note" class="ef-zone"' : '' ?> style="color: #999; font-size: 0.85rem; text-align: center; margin-top: 20px;">
            <?= htmlspecialchars($formNote) ?>
        </p>
    </div>
</section>

<!-- BENEFITS — Adaptés pour Lannion -->
<section class="tp-section-light" style="background: #f9f6f3; padding: 60px 20px;">
    <div class="tp-container" style="max-width: 900px; margin: 0 auto;">
        <h2 <?= $editMode ? 'data-field="benefit_title" class="ef-zone"' : '' ?> style="font-size: 2rem; color: #1a4d7a; margin-bottom: 40px; text-align: center;">
            <?= htmlspecialchars($benefitTitle) ?>
        </h2>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px;">
            <div style="padding: 24px; background: white; border-radius: 8px; border-left: 4px solid #d4a574;">
                <p <?= $editMode ? 'data-field="benefit1" class="ef-zone"' : '' ?> style="color: #1a4d7a; font-size: 1rem; margin: 0; font-weight: 600;">
                    <?= htmlspecialchars($benefit1) ?>
                </p>
                <p style="color: #666; font-size: 0.85rem; margin: 8px 0 0; line-height: 1.5;">
                    Aucun frais, aucune obligation. Votre estimation est totalement gratuite.
                </p>
            </div>
            <div style="padding: 24px; background: white; border-radius: 8px; border-left: 4px solid #d4a574;">
                <p <?= $editMode ? 'data-field="benefit2" class="ef-zone"' : '' ?> style="color: #1a4d7a; font-size: 1rem; margin: 0; font-weight: 600;">
                    <?= htmlspecialchars($benefit2) ?>
                </p>
                <p style="color: #666; font-size: 0.85rem; margin: 8px 0 0; line-height: 1.5;">
                    Maisons, appartements, terrains : nous connaissons chaque quartier de Lannion et du Trégor.
                </p>
            </div>
            <div style="padding: 24px; background: white; border-radius: 8px; border-left: 4px solid #d4a574;">
                <p <?= $editMode ? 'data-field="benefit3" class="ef-zone"' : '' ?> style="color: #1a4d7a; font-size: 1rem; margin: 0; font-weight: 600;">
                    <?= htmlspecialchars($benefit3) ?>
                </p>
                <p style="color: #666; font-size: 0.85rem; margin: 8px 0 0; line-height: 1.5;">
                    Recevez une estimation détaillée avec une analyse du marché local.
                </p>
            </div>
            <div style="padding: 24px; background: white; border-radius: 8px; border-left: 4px solid #d4a574;">
                <p <?= $editMode ? 'data-field="benefit4" class="ef-zone"' : '' ?> style="color: #1a4d7a; font-size: 1rem; margin: 0; font-weight: 600;">
                    <?= htmlspecialchars($benefit4) ?>
                </p>
                <p style="color: #666; font-size: 0.85rem; margin: 8px 0 0; line-height: 1.5;">
                    Un interlocuteur dédié pour vous accompagner dans votre projet immobilier.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- JAVASCRIPT — Logique du formulaire multi-étapes -->
<script>
(function() {
    // Navigation multi-étapes
    function estimationNextStep() {
        // Valider l'étape 1
        var adresse = document.getElementById('est-adresse').value.trim();
        var ville = document.getElementById('est-ville').value.trim();
        var codePostal = document.getElementById('est-code-postal').value.trim();
        var typeBien = document.getElementById('est-type-bien').value;
        var surface = document.getElementById('est-surface').value;
        var pieces = document.getElementById('est-pieces').value;
        var etatBien = document.getElementById('est-etat-bien').value;

        if (!adresse || !ville || !codePostal || !typeBien || !surface || !pieces || !etatBien) {
            alert('Veuillez remplir tous les champs obligatoires.');
            return;
        }

        if (parseFloat(surface) <= 0 || parseInt(pieces) <= 0) {
            alert('La surface et le nombre de pièces doivent être supérieurs à 0.');
            return;
        }

        // Copier les données dans les champs cachés du formulaire
        document.getElementById('est-h-adresse').value = adresse;
        document.getElementById('est-h-ville').value = ville;
        document.getElementById('est-h-code-postal').value = codePostal;
        document.getElementById('est-h-type-bien').value = typeBien;
        document.getElementById('est-h-surface').value = surface;
        document.getElementById('est-h-pieces').value = pieces;
        document.getElementById('est-h-etat-bien').value = etatBien;

        // Afficher l'étape 2
        document.getElementById('est-step-1').style.display = 'none';
        document.getElementById('est-step-2').style.display = 'block';
        updateProgress(2);
        window.scrollTo({ top: document.getElementById('estimation-form').offsetTop - 20, behavior: 'smooth' });
    }

    function estimationPrevStep() {
        document.getElementById('est-step-2').style.display = 'none';
        document.getElementById('est-step-1').style.display = 'block';
        updateProgress(1);
        window.scrollTo({ top: document.getElementById('estimation-form').offsetTop - 20, behavior: 'smooth' });
    }

    function updateProgress(step) {
        var steps = document.querySelectorAll('.est-progress-step');
        steps.forEach(function(el) {
            var s = parseInt(el.getAttribute('data-step'));
            var circle = el.querySelector('span:first-child');
            var label = el.querySelector('span:last-child');
            if (s <= step) {
                circle.style.background = '#1a4d7a';
                circle.style.color = 'white';
                label.style.color = '#1a4d7a';
            } else {
                circle.style.background = '#ddd';
                circle.style.color = '#999';
                label.style.color = '#999';
            }
        });
        // Mettre à jour la ligne de connexion
        var connector = document.querySelector('#estimation-progress > div:nth-child(2)');
        if (connector) {
            connector.style.background = step >= 2 ? '#1a4d7a' : '#ddd';
        }
    }

    // Soumission AJAX
    var form = document.getElementById('estimationForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            var errorDiv = document.getElementById('est-error');
            errorDiv.style.display = 'none';

            var submitBtn = document.getElementById('est-btn-submit');
            var originalText = submitBtn.textContent;
            submitBtn.textContent = 'Envoi en cours...';
            submitBtn.disabled = true;

            var formData = new FormData(form);

            fetch(form.action, {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Masquer le formulaire et afficher le succès
                    document.getElementById('est-step-2').style.display = 'none';
                    document.getElementById('estimation-progress').style.display = 'none';
                    document.getElementById('est-success').style.display = 'block';
                    window.scrollTo({ top: document.getElementById('estimation-form').offsetTop - 20, behavior: 'smooth' });

                    // Tracking
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'generate_lead', {
                            'event_category': 'Estimation',
                            'event_label': 'Formulaire Estimation Lannion'
                        });
                    }
                    if (typeof fbq !== 'undefined') {
                        fbq('track', 'Lead');
                    }
                } else {
                    errorDiv.textContent = data.message || 'Une erreur est survenue. Veuillez réessayer.';
                    errorDiv.style.display = 'block';
                }
            })
            .catch(function() {
                errorDiv.textContent = 'Une erreur réseau est survenue. Veuillez réessayer.';
                errorDiv.style.display = 'block';
            })
            .finally(function() {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    }

    // Auto-format téléphone
    var phoneInput = document.getElementById('est-telephone');
    if (phoneInput) {
        phoneInput.addEventListener('input', function(e) {
            var value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.match(/.{1,2}/g).join(' ');
                e.target.value = value.substr(0, 14);
            }
        });
    }

    // Exposer les fonctions globalement
    window.estimationNextStep = estimationNextStep;
    window.estimationPrevStep = estimationPrevStep;
})();
</script>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>
