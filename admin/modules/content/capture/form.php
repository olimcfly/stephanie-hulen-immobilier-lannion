<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE CAPTURES — Composant Formulaire public  v1.0
 *  /admin/modules/content/captures/form.php
 *
 *  Rendu du formulaire de capture sur la page publique.
 *  Inclus par le template de la page publique.
 *
 *  Variables attendues en entrée :
 *    $capture  array  — ligne de la table captures
 *    $mode     string — 'inline' | 'modal' | 'full' (défaut: inline)
 *
 *  Output :
 *    HTML du formulaire + gestion POST soumission lead
 *    → INSERT dans table `leads` ou `contacts`
 *    → Incrément conversions + taux via api.php
 *    → Redirect vers page_merci_url
 *
 *  Champs formulaire :
 *    Basés sur champs_formulaire (JSON) si défini,
 *    sinon champs par défaut : prénom, email, téléphone
 * ══════════════════════════════════════════════════════════════
 */

if (!isset($capture) || empty($capture)) {
    echo '<p style="color:red">Erreur : variable $capture manquante.</p>';
    return;
}

$mode       = $mode ?? 'inline';
$captureId  = (int)($capture['id'] ?? 0);
$captureSlug = $capture['slug'] ?? '';
$ctaText    = $capture['cta_text']      ?? 'Envoyer ma demande';
$merciUrl   = $capture['page_merci_url'] ?? '';
$type       = $capture['type']          ?? 'contact';

// ─── DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER') && file_exists(__DIR__ . '/../../../config/config.php')) {
        require_once __DIR__ . '/../../../config/config.php';
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {}
    }
}
if (isset($db) && !isset($pdo)) $pdo = $db;

// ─── Champs dynamiques depuis JSON ───
$champsConfig = [];
if (!empty($capture['champs_formulaire'])) {
    $decoded = json_decode($capture['champs_formulaire'], true);
    if (is_array($decoded)) $champsConfig = $decoded;
}

// Champs par défaut si non configurés
if (empty($champsConfig)) {
    $champsConfig = [
        ['name' => 'prenom',    'label' => 'Prénom',    'type' => 'text',  'required' => true,  'placeholder' => 'Votre prénom'],
        ['name' => 'email',     'label' => 'Email',     'type' => 'email', 'required' => true,  'placeholder' => 'votre@email.fr'],
        ['name' => 'telephone', 'label' => 'Téléphone', 'type' => 'tel',   'required' => false, 'placeholder' => '06 00 00 00 00'],
    ];
    // Ajouter champs spécifiques selon le type
    if ($type === 'estimation') {
        array_splice($champsConfig, 2, 0, [[
            'name' => 'adresse', 'label' => 'Adresse du bien', 'type' => 'text',
            'required' => true, 'placeholder' => 'Ex: 12 rue des Lilas, 33000 Bordeaux'
        ]]);
    }
    if ($type === 'guide') {
        // Newsletter + Guide : pas de téléphone par défaut
        $champsConfig = array_filter($champsConfig, fn($c) => $c['name'] !== 'telephone');
        $champsConfig = array_values($champsConfig);
    }
}

// ─── Traitement soumission ───
$formError   = '';
$formSuccess = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_capture_id']) &&
    (int)$_POST['_capture_id'] === $captureId) {

    // Validation CSRF
    if (empty($_POST['_csrf']) || $_POST['_csrf'] !== ($_SESSION['csrf_form_' . $captureId] ?? '')) {
        $formError = 'Token de sécurité invalide. Rechargez la page.';
    } else {
        // Collecter les données soumises
        $leadData = [];
        $validationErrors = [];
        foreach ($champsConfig as $champ) {
            $fieldName  = $champ['name'] ?? '';
            $fieldValue = trim($_POST[$fieldName] ?? '');
            $required   = (bool)($champ['required'] ?? false);
            if ($required && empty($fieldValue)) {
                $validationErrors[] = ($champ['label'] ?? $fieldName) . ' est obligatoire.';
            }
            if ($fieldName === 'email' && !empty($fieldValue) && !filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
                $validationErrors[] = 'Adresse email invalide.';
            }
            $leadData[$fieldName] = $fieldValue;
        }

        if (!empty($validationErrors)) {
            $formError = implode(' ', $validationErrors);
        } else {
            // ─── Enregistrer le lead ───
            $leadSaved = false;
            if (isset($pdo)) {
                try {
                    // Essayer table `leads` (structure type CRM)
                    $leadInserted = false;
                    try {
                        $pdo->prepare("INSERT INTO leads
                            (prenom, email, telephone, source, capture_id, donnees, created_at)
                            VALUES (?, ?, ?, 'capture', ?, ?, NOW())")
                            ->execute([
                                $leadData['prenom']    ?? '',
                                $leadData['email']     ?? '',
                                $leadData['telephone'] ?? '',
                                $captureId,
                                json_encode($leadData),
                            ]);
                        $leadInserted = true;
                    } catch (PDOException $e) {}

                    // Fallback : table `contacts`
                    if (!$leadInserted) {
                        try {
                            $pdo->prepare("INSERT INTO contacts
                                (prenom, email, telephone, source, capture_id, created_at)
                                VALUES (?, ?, ?, 'capture', ?, NOW())")
                                ->execute([
                                    $leadData['prenom']    ?? '',
                                    $leadData['email']     ?? '',
                                    $leadData['telephone'] ?? '',
                                    $captureId,
                                ]);
                            $leadInserted = true;
                        } catch (PDOException $e) {}
                    }

                    if ($leadInserted) {
                        // Incrémenter conversions
                        $pdo->prepare("UPDATE captures SET
                            conversions = conversions + 1,
                            taux_conversion = IF(vues > 0, ROUND((conversions + 1) / vues * 100, 2), 0),
                            last_conversion_at = NOW()
                            WHERE id = ?")->execute([$captureId]);

                        // Stats journalières
                        try {
                            $today = date('Y-m-d');
                            $pdo->prepare("INSERT INTO captures_stats (capture_id, date, vues, conversions)
                                VALUES (?, ?, 0, 1)
                                ON DUPLICATE KEY UPDATE
                                    conversions = conversions + 1,
                                    updated_at = NOW()")
                                ->execute([$captureId, $today]);
                        } catch (PDOException $e) {}
                        $leadSaved = true;
                    }
                } catch (PDOException $e) {}
            }

            $formSuccess = true;

            // Redirect vers page de remerciement
            if (!empty($merciUrl)) {
                $redirectUrl = $merciUrl;
                if (!str_contains($redirectUrl, '?')) $redirectUrl .= '?';
                else $redirectUrl .= '&';
                $redirectUrl .= 'capture=' . urlencode($captureSlug);
                if (!empty($leadData['prenom'])) $redirectUrl .= '&prenom=' . urlencode($leadData['prenom']);
                header('Location: ' . $redirectUrl); exit;
            }
        }
    }
}

// ─── CSRF token pour ce formulaire ───
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_form_' . $captureId])) {
    $_SESSION['csrf_form_' . $captureId] = bin2hex(random_bytes(24));
}
$csrfToken = $_SESSION['csrf_form_' . $captureId];

// ─── CSS unique par mode ───
$formId = 'capForm_' . $captureId . '_' . $mode;
$accentColor = '#ef4444'; // Peut être étendu avec $capture['accent_color'] si ajouté
?>

<style>
/* ══ CAPTURE FORM — Composant public ══ */
.cap-form-wrap {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}
.cap-form-wrap * { box-sizing: border-box; }

.cap-form {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
}
.cap-form.mode-full {
    max-width: 460px; margin: 0 auto;
    padding: 32px; box-shadow: 0 8px 32px rgba(0,0,0,.1);
    border-radius: 16px; background: #fff;
}

.cap-form-field { margin-bottom: 14px; }
.cap-form-field label {
    display: block; font-size: 13px; font-weight: 600;
    color: #374151; margin-bottom: 6px;
}
.cap-form-field label .req { color: #ef4444; margin-left: 2px; }
.cap-form-field input,
.cap-form-field select,
.cap-form-field textarea {
    width: 100%; padding: 11px 14px;
    border: 1px solid #e5e7eb; border-radius: 8px;
    font-size: 14px; font-family: inherit; color: #111;
    background: #fff; transition: border-color .15s, box-shadow .15s;
}
.cap-form-field input:focus,
.cap-form-field select:focus,
.cap-form-field textarea:focus {
    outline: none;
    border-color: <?= $accentColor ?>;
    box-shadow: 0 0 0 3px <?= $accentColor ?>22;
}
.cap-form-field textarea { min-height: 90px; resize: vertical; }

.cap-form-cta {
    width: 100%; padding: 14px 20px;
    background: <?= $accentColor ?>;
    color: #fff; border: none; border-radius: 8px;
    font-size: 15px; font-weight: 700; cursor: pointer;
    font-family: inherit; transition: all .15s;
    display: flex; align-items: center; justify-content: center; gap: 8px;
    box-shadow: 0 4px 12px <?= $accentColor ?>44;
    margin-top: 6px;
}
.cap-form-cta:hover { opacity: .9; transform: translateY(-1px); }
.cap-form-cta:disabled { opacity: .6; cursor: not-allowed; transform: none; }
.cap-form-cta .spinner { display: none; }
.cap-form-cta.loading .spinner { display: inline-block; }
.cap-form-cta.loading .btn-text { display: none; }

.cap-form-error {
    background: #fef2f2; border: 1px solid #fecaca;
    border-radius: 8px; padding: 12px 14px;
    font-size: 13px; color: #dc2626; font-weight: 600;
    margin-bottom: 14px; display: flex; align-items: flex-start; gap: 8px;
}
.cap-form-success {
    background: #f0fdf4; border: 1px solid #bbf7d0;
    border-radius: 8px; padding: 20px 18px;
    text-align: center;
}
.cap-form-success .success-icon {
    width: 52px; height: 52px; background: #22c55e;
    border-radius: 50%; display: flex; align-items: center;
    justify-content: center; margin: 0 auto 12px; font-size: 22px; color: #fff;
}
.cap-form-success h3 { font-size: 16px; font-weight: 700; color: #166534; margin: 0 0 6px; }
.cap-form-success p  { font-size: 13px; color: #15803d; margin: 0; }

.cap-form-rgpd {
    font-size: 11px; color: #9ca3af; margin-top: 10px;
    display: flex; align-items: flex-start; gap: 6px; line-height: 1.4;
}
.cap-form-rgpd i { font-size: 10px; margin-top: 1px; flex-shrink: 0; }

/* Honeypot invisible */
.cap-hp { display: none !important; position: absolute; left: -9999px; }
</style>

<div class="cap-form-wrap" id="<?= htmlspecialchars($formId) ?>">

    <?php if ($formSuccess): ?>
    <!-- ══ SUCCÈS ══ -->
    <div class="cap-form-success">
        <div class="success-icon">✓</div>
        <h3>Votre demande a bien été envoyée !</h3>
        <p>Nous vous recontacterons dans les plus brefs délais.</p>
    </div>

    <?php else: ?>

    <?php if ($formError): ?>
    <div class="cap-form-error">
        <span>⚠</span> <?= htmlspecialchars($formError) ?>
    </div>
    <?php endif; ?>

    <form method="POST" id="<?= htmlspecialchars($formId) ?>_form"
          class="cap-form mode-<?= htmlspecialchars($mode) ?>"
          onsubmit="return capFormSubmit(this)">

        <input type="hidden" name="_capture_id" value="<?= $captureId ?>">
        <input type="hidden" name="_csrf"        value="<?= htmlspecialchars($csrfToken) ?>">

        <!-- Honeypot anti-spam -->
        <div class="cap-hp" aria-hidden="true">
            <input type="text" name="_hp_name"  tabindex="-1" autocomplete="off">
            <input type="email" name="_hp_email" tabindex="-1" autocomplete="off">
        </div>

        <?php foreach ($champsConfig as $champ):
            $fname    = htmlspecialchars($champ['name']        ?? 'champ');
            $flabel   = htmlspecialchars($champ['label']       ?? '');
            $ftype    = htmlspecialchars($champ['type']        ?? 'text');
            $fph      = htmlspecialchars($champ['placeholder'] ?? '');
            $freq     = !empty($champ['required']);
            $fOptions = $champ['options'] ?? [];
            $fValue   = htmlspecialchars($_POST[$champ['name'] ?? ''] ?? '');
        ?>
        <div class="cap-form-field">
            <?php if ($flabel): ?>
            <label for="<?= $fname ?>_<?= $captureId ?>">
                <?= $flabel ?>
                <?php if ($freq): ?><span class="req">*</span><?php endif; ?>
            </label>
            <?php endif; ?>

            <?php if ($ftype === 'select' && !empty($fOptions)): ?>
            <select name="<?= $fname ?>" id="<?= $fname ?>_<?= $captureId ?>"
                    <?= $freq ? 'required' : '' ?>>
                <option value="">— Choisir —</option>
                <?php foreach ($fOptions as $opt): ?>
                <option value="<?= htmlspecialchars($opt) ?>"
                        <?= $fValue === htmlspecialchars($opt) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($opt) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <?php elseif ($ftype === 'textarea'): ?>
            <textarea name="<?= $fname ?>" id="<?= $fname ?>_<?= $captureId ?>"
                      placeholder="<?= $fph ?>"
                      <?= $freq ? 'required' : '' ?>><?= $fValue ?></textarea>

            <?php else: ?>
            <input type="<?= $ftype ?>" name="<?= $fname ?>" id="<?= $fname ?>_<?= $captureId ?>"
                   placeholder="<?= $fph ?>" value="<?= $fValue ?>"
                   <?= $freq ? 'required' : '' ?>
                   <?= $ftype === 'tel' ? 'pattern="[0-9\s\+\-\(\)\.]{6,20}"' : '' ?>>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <button type="submit" class="cap-form-cta" id="<?= htmlspecialchars($formId) ?>_btn">
            <span class="btn-text"><?= htmlspecialchars($ctaText) ?></span>
            <span class="spinner">⏳</span>
        </button>

        <p class="cap-form-rgpd">
            <i>🔒</i>
            Vos informations restent confidentielles et ne seront jamais partagées.
            <?php if (!empty($capture['page_merci_url'])): ?>
            Vous serez redirigé après soumission.
            <?php endif; ?>
        </p>

    </form>
    <?php endif; ?>
</div>

<script>
function capFormSubmit(form) {
    // Honeypot check
    const hp = form.querySelector('[name="_hp_email"]');
    if (hp && hp.value.trim() !== '') return false;

    const btn = form.querySelector('.cap-form-cta');
    if (btn) btn.classList.add('loading');

    // Incrémenter la vue si pas déjà fait (idempotent via sessionStorage)
    const capId = form.querySelector('[name="_capture_id"]')?.value;
    if (capId) {
        const vueKey = 'cap_vue_' + capId;
        if (!sessionStorage.getItem(vueKey)) {
            sessionStorage.setItem(vueKey, '1');
        }
    }
    return true; // Soumettre normalement
}
</script>