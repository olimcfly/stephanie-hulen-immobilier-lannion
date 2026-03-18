<?php
/**
 * ══════════════════════════════════════════════════════════════
 * CRM CONTACTS EDIT  v1.0
 * /admin/modules/crm/contacts/edit.php
 * Actions : create / edit
 * Sauvegarde via POST → redirect avec msg=created|updated|error
 * ══════════════════════════════════════════════════════════════
 */

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

$contactId = (int)($_GET['id'] ?? 0);
$isCreate  = ($contactId === 0);
$contact   = [];
$errors    = [];

// ─── Chargement si édition ───────────────────────────────────
if (!$isCreate) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->execute([$contactId]);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$contact) {
            header('Location: ?page=crm/contacts&msg=error'); exit;
        }
    } catch (PDOException $e) {
        header('Location: ?page=crm/contacts&msg=error'); exit;
    }
}

// ─── Traitement POST ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation CSRF
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide. Rechargez la page.';
    }

    $data = [
        'nom'          => trim($_POST['nom']          ?? ''),
        'prenom'       => trim($_POST['prenom']        ?? ''),
        'civility'     => trim($_POST['civility']      ?? ''),
        'firstname'    => trim($_POST['firstname']     ?? ''),
        'lastname'     => trim($_POST['lastname']      ?? ''),
        'email'        => trim($_POST['email']         ?? ''),
        'telephone'    => trim($_POST['telephone']     ?? ''),
        'phone'        => trim($_POST['phone']         ?? ''),
        'mobile'       => trim($_POST['mobile']        ?? ''),
        'source'       => trim($_POST['source']        ?? ''),
        'notes'        => trim($_POST['notes']         ?? ''),
        'category'     => trim($_POST['category']      ?? 'prospect'),
        'status'       => trim($_POST['status']        ?? 'active'),
        'address'      => trim($_POST['address']       ?? ''),
        'address2'     => trim($_POST['address2']      ?? ''),
        'city'         => trim($_POST['city']          ?? ''),
        'postal_code'  => trim($_POST['postal_code']   ?? ''),
        'country'      => trim($_POST['country']       ?? 'France'),
        'company'      => trim($_POST['company']       ?? ''),
        'job_title'    => trim($_POST['job_title']     ?? ''),
        'birthday'     => trim($_POST['birthday']      ?? '') ?: null,
        'website'      => trim($_POST['website']       ?? ''),
        'linkedin'     => trim($_POST['linkedin']      ?? ''),
        'facebook'     => trim($_POST['facebook']      ?? ''),
        'instagram'    => trim($_POST['instagram']     ?? ''),
        'tags'         => trim($_POST['tags']          ?? ''),
        'rating'       => max(0, min(5, (int)($_POST['rating'] ?? 0))),
        'last_contact' => trim($_POST['last_contact']  ?? '') ?: null,
        'next_followup'=> trim($_POST['next_followup'] ?? '') ?: null,
    ];

    // Validation
    if (empty($data['nom']) && empty($data['email']) && empty($data['lastname'])) {
        $errors[] = 'Un nom ou un email est requis.';
    }
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide.';
    }
    $allowedCategories = ['client','prospect','partenaire','notaire','autre'];
    $allowedStatuses   = ['active','inactive','vip','blacklist'];
    if (!in_array($data['category'], $allowedCategories)) $data['category'] = 'prospect';
    if (!in_array($data['status'],   $allowedStatuses))   $data['status']   = 'active';

    if (empty($errors)) {
        try {
            if ($isCreate) {
                $cols = implode(', ', array_map(fn($k) => "`{$k}`", array_keys($data)));
                $ph   = implode(', ', array_fill(0, count($data), '?'));
                $stmt = $pdo->prepare("INSERT INTO contacts ({$cols}, created_at) VALUES ({$ph}, NOW())");
                $stmt->execute(array_values($data));
                $newId = $pdo->lastInsertId();
                header("Location: ?page=crm/contacts&action=edit&id={$newId}&msg=created"); exit;
            } else {
                $sets = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
                $vals = array_values($data);
                $vals[] = $contactId;
                $stmt = $pdo->prepare("UPDATE contacts SET {$sets}, updated_at = NOW() WHERE id = ?");
                $stmt->execute($vals);
                header("Location: ?page=crm/contacts&action=edit&id={$contactId}&msg=updated"); exit;
            }
        } catch (PDOException $e) {
            error_log('[CRM Contacts Edit] ' . $e->getMessage());
            $errors[] = 'Erreur base de données : ' . $e->getMessage();
        }
    }

    // En cas d'erreur, préremplir avec les données postées
    $contact = array_merge($contact, $data);
}

// ─── Valeurs par défaut ──────────────────────────────────────
$v = function(string $key, string $default = '') use ($contact): string {
    return htmlspecialchars((string)($contact[$key] ?? $default));
};

$flash = $_GET['msg'] ?? '';

// ─── CSRF token ──────────────────────────────────────────────
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<style>
/* ══════════════════════════════════════════════════════════════
   CRM CONTACTS EDIT v1.0
══════════════════════════════════════════════════════════════ */
.crmce-wrap { font-family: var(--font, 'Inter', sans-serif); max-width: 960px; }

.crmce-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; flex-wrap: wrap; gap: 12px; }
.crmce-header-left { display: flex; align-items: center; gap: 12px; }
.crmce-back { width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--border,#e5e7eb); background: var(--surface,#fff); display: flex; align-items: center; justify-content: center; color: var(--text-2,#6b7280); text-decoration: none; transition: all .15s; font-size: .78rem; }
.crmce-back:hover { border-color: #10b981; color: #10b981; }
.crmce-header h2 { font-size: 1.25rem; font-weight: 700; color: var(--text,#111827); margin: 0; letter-spacing: -.02em; }
.crmce-header p { font-size: .82rem; color: var(--text-3,#9ca3af); margin: 2px 0 0; }
.crmce-header-actions { display: flex; gap: 8px; }

.crmce-btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 20px; border-radius: 10px; font-size: .83rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; font-family: inherit; text-decoration: none; }
.crmce-btn-primary { background: #10b981; color: #fff; box-shadow: 0 1px 4px rgba(16,185,129,.22); }
.crmce-btn-primary:hover { background: #059669; transform: translateY(-1px); color: #fff; }
.crmce-btn-outline { background: var(--surface,#fff); color: var(--text-2,#6b7280); border: 1px solid var(--border,#e5e7eb); }
.crmce-btn-outline:hover { border-color: #10b981; color: #10b981; }
.crmce-btn-danger { background: #fef2f2; color: #dc2626; border: 1px solid rgba(220,38,38,.15); }
.crmce-btn-danger:hover { background: #fee2e2; }

/* ─── Errors ─── */
.crmce-errors { background: #fef2f2; border: 1px solid rgba(220,38,38,.15); border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; }
.crmce-errors ul { margin: 0; padding: 0 0 0 18px; }
.crmce-errors li { color: #dc2626; font-size: .83rem; margin: 3px 0; }

/* ─── Flash ─── */
.crmce-flash { padding: 12px 18px; border-radius: 10px; font-size: .85rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; animation: crmceFlashIn .3s; }
.crmce-flash.success { background: #d1fae5; color: #059669; border: 1px solid rgba(5,150,105,.12); }
@keyframes crmceFlashIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }

/* ─── Layout 2 colonnes ─── */
.crmce-layout { display: grid; grid-template-columns: 1fr 300px; gap: 16px; align-items: start; }

/* ─── Card section ─── */
.crmce-card { background: var(--surface,#fff); border-radius: 14px; border: 1px solid var(--border,#e5e7eb); overflow: hidden; margin-bottom: 16px; }
.crmce-card-head { padding: 14px 20px; border-bottom: 1px solid var(--border,#f3f4f6); display: flex; align-items: center; gap: 8px; }
.crmce-card-head h3 { font-size: .83rem; font-weight: 700; color: var(--text,#111827); margin: 0; text-transform: uppercase; letter-spacing: .04em; }
.crmce-card-head i { font-size: .75rem; color: #10b981; }
.crmce-card-body { padding: 18px 20px; }

/* ─── Grille champs ─── */
.crmce-grid { display: grid; gap: 14px; }
.crmce-grid-2 { grid-template-columns: 1fr 1fr; }
.crmce-grid-3 { grid-template-columns: 1fr 1fr 1fr; }

/* ─── Field ─── */
.crmce-field { display: flex; flex-direction: column; gap: 5px; }
.crmce-field label { font-size: .73rem; font-weight: 700; color: var(--text-2,#374151); text-transform: uppercase; letter-spacing: .04em; display: flex; align-items: center; gap: 5px; }
.crmce-field label .req { color: #ef4444; font-size: .65rem; }
.crmce-input, .crmce-select, .crmce-textarea {
    padding: 9px 12px; background: var(--surface,#fff); border: 1px solid var(--border,#e5e7eb);
    border-radius: 8px; color: var(--text,#111827); font-size: .85rem; font-family: inherit;
    transition: all .15s; width: 100%; box-sizing: border-box;
}
.crmce-input:focus, .crmce-select:focus, .crmce-textarea:focus {
    outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.1);
}
.crmce-input::placeholder, .crmce-textarea::placeholder { color: var(--text-3,#9ca3af); }
.crmce-textarea { resize: vertical; min-height: 90px; }
.crmce-input-icon { position: relative; }
.crmce-input-icon i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-3,#9ca3af); font-size: .72rem; }
.crmce-input-icon .crmce-input { padding-left: 30px; }

/* ─── Stars rating ─── */
.crmce-stars { display: flex; gap: 4px; align-items: center; }
.crmce-stars input[type="radio"] { display: none; }
.crmce-stars label { font-size: 1.3rem; color: #e5e7eb; cursor: pointer; transition: color .1s; line-height: 1; }
.crmce-stars input:checked ~ label,
.crmce-stars label:hover,
.crmce-stars label:hover ~ label { color: #f59e0b !important; }
.crmce-stars { flex-direction: row-reverse; }
.crmce-stars label:hover,
.crmce-stars label:hover ~ label,
.crmce-stars input:checked ~ label { color: #f59e0b; }

/* ─── Avatar preview sidebar ─── */
.crmce-avatar-preview { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800; color: #fff; margin: 0 auto 12px; transition: background .3s; }

/* ─── Tags input ─── */
.crmce-tags-hint { font-size: .68rem; color: var(--text-3,#9ca3af); margin-top: 3px; }

@media (max-width: 860px) {
    .crmce-layout { grid-template-columns: 1fr; }
    .crmce-grid-2, .crmce-grid-3 { grid-template-columns: 1fr; }
}
</style>

<div class="crmce-wrap">

<?php if ($flash === 'created'): ?>
<div class="crmce-flash success"><i class="fas fa-check-circle"></i> Contact créé avec succès</div>
<?php elseif ($flash === 'updated'): ?>
<div class="crmce-flash success"><i class="fas fa-check-circle"></i> Contact mis à jour</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="crmce-errors">
    <ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<!-- ─── Header ─── -->
<div class="crmce-header">
    <div class="crmce-header-left">
        <a href="?page=crm/contacts" class="crmce-back" title="Retour à la liste"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h2><?= $isCreate ? 'Nouveau contact' : 'Modifier le contact' ?></h2>
            <p><?= $isCreate ? 'Créez un nouveau contact dans le CRM' : htmlspecialchars(($contact['nom'] ?? '') . ' ' . ($contact['prenom'] ?? '')) ?></p>
        </div>
    </div>
    <div class="crmce-header-actions">
        <?php if (!$isCreate): ?>
        <button type="button" onclick="CRMCEdit.deleteContact(<?= $contactId ?>, '<?= addslashes(htmlspecialchars(($contact['nom']??'').' '.($contact['prenom']??''))) ?>')"
                class="crmce-btn crmce-btn-danger"><i class="fas fa-trash"></i> Supprimer</button>
        <?php endif; ?>
        <a href="?page=crm/contacts" class="crmce-btn crmce-btn-outline"><i class="fas fa-times"></i> Annuler</a>
        <button type="submit" form="crmceForm" class="crmce-btn crmce-btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
    </div>
</div>

<form id="crmceForm" method="POST" action="?page=crm/contacts&action=<?= $isCreate?'create':'edit' ?><?= !$isCreate?'&id='.$contactId:'' ?>">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

<div class="crmce-layout">

    <!-- ── Colonne principale ── -->
    <div>

        <!-- Identité -->
        <div class="crmce-card">
            <div class="crmce-card-head"><i class="fas fa-user"></i><h3>Identité</h3></div>
            <div class="crmce-card-body">
                <div class="crmce-grid crmce-grid-3" style="margin-bottom:14px">
                    <div class="crmce-field">
                        <label>Civilité</label>
                        <select name="civility" class="crmce-select">
                            <option value="">—</option>
                            <?php foreach (['M.' => 'M.', 'Mme' => 'Mme', 'Dr' => 'Dr', 'Me' => 'Me'] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($v('civility')===$val)?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="crmce-field">
                        <label>Nom <span class="req">*</span></label>
                        <input type="text" name="nom" class="crmce-input" value="<?= $v('nom') ?>" placeholder="Dupont" required>
                    </div>
                    <div class="crmce-field">
                        <label>Prénom</label>
                        <input type="text" name="prenom" class="crmce-input" value="<?= $v('prenom') ?>" placeholder="Jean">
                    </div>
                </div>
                <div class="crmce-grid crmce-grid-2">
                    <div class="crmce-field">
                        <label>Firstname (alt)</label>
                        <input type="text" name="firstname" class="crmce-input" value="<?= $v('firstname') ?>" placeholder="Jean">
                    </div>
                    <div class="crmce-field">
                        <label>Lastname (alt)</label>
                        <input type="text" name="lastname" class="crmce-input" value="<?= $v('lastname') ?>" placeholder="Dupont">
                    </div>
                </div>
            </div>
        </div>

        <!-- Coordonnées -->
        <div class="crmce-card">
            <div class="crmce-card-head"><i class="fas fa-address-card"></i><h3>Coordonnées</h3></div>
            <div class="crmce-card-body">
                <div class="crmce-grid crmce-grid-2" style="margin-bottom:14px">
                    <div class="crmce-field">
                        <label>Email</label>
                        <div class="crmce-input-icon">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" class="crmce-input" value="<?= $v('email') ?>" placeholder="jean@exemple.fr">
                        </div>
                    </div>
                    <div class="crmce-field">
                        <label>Téléphone</label>
                        <div class="crmce-input-icon">
                            <i class="fas fa-phone"></i>
                            <input type="tel" name="telephone" class="crmce-input" value="<?= $v('telephone') ?>" placeholder="06 12 34 56 78">
                        </div>
                    </div>
                    <div class="crmce-field">
                        <label>Téléphone fixe</label>
                        <div class="crmce-input-icon">
                            <i class="fas fa-phone-alt"></i>
                            <input type="tel" name="phone" class="crmce-input" value="<?= $v('phone') ?>" placeholder="04 12 34 56 78">
                        </div>
                    </div>
                    <div class="crmce-field">
                        <label>Mobile</label>
                        <div class="crmce-input-icon">
                            <i class="fas fa-mobile-alt"></i>
                            <input type="tel" name="mobile" class="crmce-input" value="<?= $v('mobile') ?>" placeholder="07 12 34 56 78">
                        </div>
                    </div>
                </div>
                <div class="crmce-grid" style="margin-bottom:14px">
                    <div class="crmce-field">
                        <label>Adresse</label>
                        <input type="text" name="address" class="crmce-input" value="<?= $v('address') ?>" placeholder="12 rue de la Paix">
                    </div>
                    <div class="crmce-field">
                        <label>Adresse 2</label>
                        <input type="text" name="address2" class="crmce-input" value="<?= $v('address2') ?>" placeholder="Appartement, bâtiment…">
                    </div>
                </div>
                <div class="crmce-grid crmce-grid-3">
                    <div class="crmce-field">
                        <label>Ville</label>
                        <input type="text" name="city" class="crmce-input" value="<?= $v('city') ?>" placeholder="Paris">
                    </div>
                    <div class="crmce-field">
                        <label>Code postal</label>
                        <input type="text" name="postal_code" class="crmce-input" value="<?= $v('postal_code') ?>" placeholder="75001">
                    </div>
                    <div class="crmce-field">
                        <label>Pays</label>
                        <input type="text" name="country" class="crmce-input" value="<?= $v('country', 'France') ?>" placeholder="France">
                    </div>
                </div>
            </div>
        </div>

        <!-- Professionnel -->
        <div class="crmce-card">
            <div class="crmce-card-head"><i class="fas fa-briefcase"></i><h3>Informations professionnelles</h3></div>
            <div class="crmce-card-body">
                <div class="crmce-grid crmce-grid-2">
                    <div class="crmce-field">
                        <label>Entreprise</label>
                        <input type="text" name="company" class="crmce-input" value="<?= $v('company') ?>" placeholder="Nom de l'entreprise">
                    </div>
                    <div class="crmce-field">
                        <label>Poste</label>
                        <input type="text" name="job_title" class="crmce-input" value="<?= $v('job_title') ?>" placeholder="Directeur commercial">
                    </div>
                    <div class="crmce-field">
                        <label>Date de naissance</label>
                        <input type="date" name="birthday" class="crmce-input" value="<?= $v('birthday') ?>">
                    </div>
                    <div class="crmce-field">
                        <label>Source</label>
                        <select name="source" class="crmce-select">
                            <option value="">— Inconnue —</option>
                            <?php foreach (['Formulaire','Estimation','Landing page','Facebook','Instagram','LinkedIn','Google','Téléphone','Recommandation','Autre'] as $src): ?>
                            <option value="<?= $src ?>" <?= ($v('source')===$src)?'selected':'' ?>><?= $src ?></option>
                            <?php endforeach; ?>
                            <?php if ($v('source') && !in_array($v('source'), ['Formulaire','Estimation','Landing page','Facebook','Instagram','LinkedIn','Google','Téléphone','Recommandation','Autre'])): ?>
                            <option value="<?= $v('source') ?>" selected><?= $v('source') ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Réseaux sociaux -->
        <div class="crmce-card">
            <div class="crmce-card-head"><i class="fas fa-share-alt"></i><h3>Réseaux sociaux & Web</h3></div>
            <div class="crmce-card-body">
                <div class="crmce-grid crmce-grid-2">
                    <div class="crmce-field">
                        <label><i class="fab fa-linkedin" style="color:#0a66c2"></i> LinkedIn</label>
                        <input type="url" name="linkedin" class="crmce-input" value="<?= $v('linkedin') ?>" placeholder="https://linkedin.com/in/...">
                    </div>
                    <div class="crmce-field">
                        <label><i class="fab fa-facebook" style="color:#1877f2"></i> Facebook</label>
                        <input type="url" name="facebook" class="crmce-input" value="<?= $v('facebook') ?>" placeholder="https://facebook.com/...">
                    </div>
                    <div class="crmce-field">
                        <label><i class="fab fa-instagram" style="color:#e1306c"></i> Instagram</label>
                        <input type="url" name="instagram" class="crmce-input" value="<?= $v('instagram') ?>" placeholder="https://instagram.com/...">
                    </div>
                    <div class="crmce-field">
                        <label><i class="fas fa-globe" style="color:#6b7280"></i> Site web</label>
                        <input type="url" name="website" class="crmce-input" value="<?= $v('website') ?>" placeholder="https://...">
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="crmce-card">
            <div class="crmce-card-head"><i class="fas fa-sticky-note"></i><h3>Notes</h3></div>
            <div class="crmce-card-body">
                <div class="crmce-field">
                    <textarea name="notes" class="crmce-textarea" placeholder="Notes libres sur ce contact, historique des échanges, besoins identifiés…"><?= $v('notes') ?></textarea>
                </div>
            </div>
        </div>

    </div><!-- /col principale -->

    <!-- ── Sidebar ── -->
    <div>

        <!-- Avatar preview -->
        <div class="crmce-card" style="margin-bottom:16px">
            <div class="crmce-card-body" style="text-align:center;padding:20px">
                <div class="crmce-avatar-preview" id="crmceAvatarPrev" style="background:#10b981">?</div>
                <div style="font-size:.72rem;color:var(--text-3,#9ca3af)">Aperçu avatar</div>
            </div>
        </div>

        <!-- Classification -->
        <div class="crmce-card">
            <div class="crmce-card-head"><i class="fas fa-tag"></i><h3>Classification</h3></div>
            <div class="crmce-card-body">
                <div class="crmce-grid" style="gap:12px">
                    <div class="crmce-field">
                        <label>Catégorie</label>
                        <select name="category" class="crmce-select" id="crmceCat">
                            <?php foreach (['client'=>'Client','prospect'=>'Prospect','partenaire'=>'Partenaire','notaire'=>'Notaire','autre'=>'Autre'] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($v('category',$isCreate?'prospect':'') === $val)?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="crmce-field">
                        <label>Statut</label>
                        <select name="status" class="crmce-select">
                            <?php foreach (['active'=>'Actif','inactive'=>'Inactif','vip'=>'VIP ⭐','blacklist'=>'Blacklist 🚫'] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= ($v('status',$isCreate?'active':'') === $val)?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="crmce-field">
                        <label>Note (étoiles)</label>
                        <div class="crmce-stars" id="crmceStars">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= ((int)($contact['rating']??0)===$i)?'checked':'' ?>>
                            <label for="star<?= $i ?>">★</label>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="crmceRatingHidden" value="<?= (int)($contact['rating']??0) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tags -->
        <div class="crmce-card">
            <div class="crmce-card-head"><i class="fas fa-hashtag"></i><h3>Tags</h3></div>
            <div class="crmce-card-body">
                <div class="crmce-field">
                    <textarea name="tags" class="crmce-textarea" style="min-height:60px" placeholder="acheteur, urgent, 3 pièces, budget-300k"><?= $v('tags') ?></textarea>
                    <div class="crmce-tags-hint">Séparez les tags par des virgules</div>
                </div>
            </div>
        </div>

        <!-- Suivi -->
        <div class="crmce-card">
            <div class="crmce-card-head"><i class="fas fa-calendar-check"></i><h3>Suivi & Relance</h3></div>
            <div class="crmce-card-body">
                <div class="crmce-grid" style="gap:12px">
                    <div class="crmce-field">
                        <label>Dernier contact</label>
                        <input type="date" name="last_contact" class="crmce-input" value="<?= $v('last_contact') ?>">
                    </div>
                    <div class="crmce-field">
                        <label>Prochaine relance</label>
                        <input type="date" name="next_followup" class="crmce-input" value="<?= $v('next_followup') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Bouton save bas -->
        <button type="submit" form="crmceForm" class="crmce-btn crmce-btn-primary" style="width:100%;justify-content:center;margin-top:4px">
            <i class="fas fa-save"></i> Enregistrer
        </button>

    </div><!-- /sidebar -->

</div><!-- /layout -->
</form>

</div><!-- /crmce-wrap -->

<!-- ── Modal suppression ─────────────────────────────────── -->
<div id="crmceModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    <div onclick="CRMCEdit.modalClose()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px)"></div>
    <div id="crmceModalBox" style="position:relative;z-index:1;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:400px;margin:16px;overflow:hidden;transform:scale(.94) translateY(8px);transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .15s;opacity:0;">
        <div style="padding:20px 22px 16px;display:flex;align-items:flex-start;gap:14px;background:#fef2f233;">
            <div style="width:42px;height:42px;border-radius:12px;background:#fef2f2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0"><i class="fas fa-trash"></i></div>
            <div>
                <div style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:5px">Supprimer ce contact ?</div>
                <div id="crmceModalMsg" style="font-size:.82rem;color:#6b7280;line-height:1.5"></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding:12px 20px 18px;border-top:1px solid #f3f4f6">
            <button onclick="CRMCEdit.modalClose()" style="padding:9px 20px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:.83rem;font-weight:600;cursor:pointer;font-family:inherit;">Annuler</button>
            <button id="crmceModalConfirm" style="padding:9px 20px;border-radius:10px;border:none;background:#dc2626;color:#fff;font-size:.83rem;font-weight:700;cursor:pointer;font-family:inherit;">Supprimer</button>
        </div>
    </div>
</div>

<script>
const CRMCEdit = {
    apiUrl: '/admin/modules/crm/contacts/api.php',

    // ── Avatar preview dynamique ───────────────────────────
    initAvatar() {
        const nomInput    = document.querySelector('input[name="nom"]');
        const prenomInput = document.querySelector('input[name="prenom"]');
        const preview     = document.getElementById('crmceAvatarPrev');
        const colors      = ['#10b981','#3b82f6','#8b5cf6','#f59e0b','#ef4444','#0d9488','#6366f1'];
        if (!nomInput || !preview) return;
        const update = () => {
            const nom    = nomInput.value.trim();
            const prenom = prenomInput ? prenomInput.value.trim() : '';
            const full   = (nom + ' ' + prenom).trim() || '?';
            const init   = full[0].toUpperCase() + (full.indexOf(' ') > -1 ? full.split(' ').filter(Boolean).pop()[0].toUpperCase() : '');
            preview.textContent = init;
            preview.style.background = colors[full.split('').reduce((a,c) => a+c.charCodeAt(0),0) % colors.length];
        };
        nomInput.addEventListener('input', update);
        if (prenomInput) prenomInput.addEventListener('input', update);
        update();
    },

    // ── Modal suppression ──────────────────────────────────
    deleteContact(id, name) {
        document.getElementById('crmceModalMsg').innerHTML = `<strong>${name}</strong> sera supprimé définitivement.`;
        const el  = document.getElementById('crmceModal');
        const box = document.getElementById('crmceModalBox');
        el.style.display = 'flex';
        requestAnimationFrame(() => { box.style.opacity='1'; box.style.transform='scale(1) translateY(0)'; });
        document.getElementById('crmceModalConfirm').onclick = async () => {
            const fd = new FormData();
            fd.append('action','delete'); fd.append('id', id);
            const r = await fetch(this.apiUrl, {method:'POST',body:fd});
            const d = await r.json();
            if (d.success) { window.location.href = '?page=crm/contacts&msg=deleted'; }
        };
    },
    modalClose() {
        const el  = document.getElementById('crmceModal');
        const box = document.getElementById('crmceModalBox');
        box.style.opacity='0'; box.style.transform='scale(.94) translateY(8px)';
        setTimeout(() => el.style.display='none', 160);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    CRMCEdit.initAvatar();

    // ── Stars rating fix ───────────────────────────────────
    // La direction row-reverse nécessite un recalcul de la valeur
    document.querySelectorAll('input[name="rating"]').forEach(radio => {
        radio.addEventListener('change', () => {
            document.getElementById('crmceRatingHidden').value = radio.value;
        });
    });
});
</script>