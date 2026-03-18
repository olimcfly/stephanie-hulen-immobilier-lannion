<?php
/**
 * ══════════════════════════════════════════════════════════════
 * GUIDE LOCAL — Formulaire création / édition partenaire
 * /admin/modules/content/guide-local/edit.php
 * ══════════════════════════════════════════════════════════════
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

$itemId  = (int)($_GET['id'] ?? 0);
$action  = $_GET['action'] ?? ($itemId ? 'edit' : 'create');
$isNew   = ($action === 'create' || $itemId === 0);
$error   = null;
$success = null;

// ─── Catégories ───
$partnerCategories = [
    'ecole'      => ['icon' => 'fa-school',         'label' => 'Écoles & Crèches'],
    'sante'      => ['icon' => 'fa-heartbeat',      'label' => 'Santé & Médecins'],
    'transport'  => ['icon' => 'fa-bus',            'label' => 'Transports'],
    'commerce'   => ['icon' => 'fa-shopping-bag',   'label' => 'Commerces & Marchés'],
    'restaurant' => ['icon' => 'fa-utensils',       'label' => 'Restaurants & Cafés'],
    'sport'      => ['icon' => 'fa-dumbbell',       'label' => 'Sport & Loisirs'],
    'culture'    => ['icon' => 'fa-landmark',       'label' => 'Culture & Patrimoine'],
    'nature'     => ['icon' => 'fa-tree',           'label' => 'Parcs & Nature'],
    'services'   => ['icon' => 'fa-concierge-bell', 'label' => 'Services de proximité'],
    'securite'   => ['icon' => 'fa-shield-alt',     'label' => 'Sécurité & Mairie'],
    'autre'      => ['icon' => 'fa-map-pin',        'label' => 'Autre'],
];

// ─── Charger les secteurs ───
$secteurs = [];
try { $secteurs = $pdo->query("SELECT id, nom, ville FROM secteurs ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC); }
catch (PDOException $e) {}

// ─── Charger la fiche existante ───
$item = [
    'id' => 0, 'nom' => '', 'slug' => '', 'categorie' => 'commerce',
    'description' => '', 'adresse' => '', 'ville' => '', 'code_postal' => '',
    'secteur_id' => null, 'latitude' => '', 'longitude' => '',
    'telephone' => '', 'site_web' => '', 'gmb_url' => '', 'note' => '',
    'audience' => 'tous', 'is_featured' => 0, 'status' => 'draft',
    'meta_title' => '', 'meta_desc' => '',
];

if (!$isNew) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM guide_local WHERE id = ?");
        $stmt->execute([$itemId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) { header('Location: /admin/dashboard.php?page=guide-local&msg=notfound'); exit; }
        $item = array_merge($item, $row);
    } catch (PDOException $e) { $error = $e->getMessage(); }
}

// ─── Traitement POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom       = trim($_POST['nom'] ?? '');
    $slugRaw   = trim($_POST['slug'] ?? '');

    if (empty($nom)) {
        $error = 'Le nom du partenaire est obligatoire.';
    } else {
        // Slug
        function glEditSlug(string $s): string {
            $s = mb_strtolower(trim($s));
            foreach (['à'=>'a','á'=>'a','â'=>'a','è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
                      'î'=>'i','ï'=>'i','ô'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c'] as $k => $v) $s = str_replace($k, $v, $s);
            return trim(preg_replace('/[^a-z0-9]+/', '-', $s), '-');
        }
        $slug = !empty($slugRaw) ? glEditSlug($slugRaw) : glEditSlug($nom);

        // Unicité slug
        $existing = $pdo->prepare("SELECT id FROM guide_local WHERE slug = ? AND id != ?");
        $existing->execute([$slug, $itemId]);
        if ($existing->fetch()) $slug .= '-' . time();

        $fields = [
            'nom'         => $nom,
            'slug'        => $slug,
            'categorie'   => $_POST['categorie']   ?? 'autre',
            'description' => trim($_POST['description'] ?? ''),
            'adresse'     => trim($_POST['adresse']     ?? ''),
            'ville'       => trim($_POST['ville']       ?? ''),
            'code_postal' => trim($_POST['code_postal'] ?? ''),
            'secteur_id'  => !empty($_POST['secteur_id']) ? (int)$_POST['secteur_id'] : null,
            'latitude'    => !empty($_POST['latitude'])   ? (float)$_POST['latitude']  : null,
            'longitude'   => !empty($_POST['longitude'])  ? (float)$_POST['longitude'] : null,
            'telephone'   => trim($_POST['telephone']  ?? ''),
            'site_web'    => trim($_POST['site_web']   ?? ''),
            'gmb_url'     => trim($_POST['gmb_url']    ?? ''),
            'note'        => isset($_POST['note']) && $_POST['note'] !== '' ? round((float)$_POST['note'], 1) : null,
            'audience'    => in_array($_POST['audience'] ?? '', ['acheteur','habitant','tous']) ? $_POST['audience'] : 'tous',
            'is_featured' => !empty($_POST['is_featured']) ? 1 : 0,
            'status'      => in_array($_POST['status'] ?? '', ['published','draft']) ? $_POST['status'] : 'draft',
            'meta_title'  => trim($_POST['meta_title'] ?? ''),
            'meta_desc'   => trim($_POST['meta_desc']  ?? ''),
        ];

        try {
            if ($itemId > 0) {
                $sets = []; $vals = [];
                foreach ($fields as $c => $v) { $sets[] = "`{$c}` = ?"; $vals[] = $v; }
                $vals[] = $itemId;
                $pdo->prepare("UPDATE guide_local SET " . implode(', ', $sets) . " WHERE id = ?")->execute($vals);
                $msg = 'updated';
            } else {
                $cols = array_keys($fields);
                $pdo->prepare("INSERT INTO guide_local (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")")->execute(array_values($fields));
                $itemId = (int)$pdo->lastInsertId();
                $msg = 'created';
            }
            header("Location: /admin/dashboard.php?page=guide-local&msg={$msg}"); exit;
        } catch (PDOException $e) { $error = $e->getMessage(); }
    }
    // Repopuler item avec les valeurs saisies
    $item = array_merge($item, $_POST, ['id' => $itemId]);
}

$pageTitle = $isNew ? 'Nouveau partenaire' : 'Modifier : ' . htmlspecialchars($item['nom']);
?>
<style>
/* ─── GUIDE LOCAL EDIT ─── */
.gle-wrap { font-family: var(--font); max-width: 960px; }
.gle-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; gap: 12px; flex-wrap: wrap;
}
.gle-header h2 {
    font-family: var(--font-display); font-size: 1.25rem; font-weight: 700;
    color: var(--text); margin: 0; display: flex; align-items: center; gap: 10px; letter-spacing:-.02em;
}
.gle-header h2 i { color: #10b981; font-size: 15px; }
.gle-back {
    display: inline-flex; align-items: center; gap: 6px;
    color: var(--text-2); text-decoration: none; font-size: 0.78rem; font-weight: 600;
    padding: 7px 14px; border: 1px solid var(--border); border-radius: var(--radius);
    background: var(--surface); transition: all .15s;
}
.gle-back:hover { border-color: #10b981; color: #10b981; }

.gle-grid { display: grid; grid-template-columns: 1fr 320px; gap: 18px; }
@media (max-width: 860px) { .gle-grid { grid-template-columns: 1fr; } }

.gle-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden;
}
.gle-card-head {
    padding: 14px 18px; background: var(--surface-2);
    border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 8px;
}
.gle-card-head h3 { font-size: 0.8rem; font-weight: 700; color: var(--text); margin: 0; text-transform: uppercase; letter-spacing: .04em; }
.gle-card-head i { font-size: 0.75rem; color: #10b981; }
.gle-card-body { padding: 18px; display: flex; flex-direction: column; gap: 16px; }

/* Field groups */
.gle-field { display: flex; flex-direction: column; gap: 5px; }
.gle-label {
    font-size: 0.72rem; font-weight: 700; color: var(--text-2);
    text-transform: uppercase; letter-spacing: .04em; display: flex; align-items: center; gap: 5px;
}
.gle-label .req { color: var(--red,#ef4444); }
.gle-label .hint { font-size: 0.65rem; color: var(--text-3); text-transform: none; letter-spacing: 0; font-weight: 400; margin-left: 4px; }
.gle-input, .gle-select, .gle-textarea {
    padding: 9px 12px; background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); color: var(--text); font-family: var(--font);
    font-size: 0.85rem; transition: all .2s var(--ease); width: 100%; box-sizing: border-box;
}
.gle-input:focus, .gle-select:focus, .gle-textarea:focus {
    outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.1);
}
.gle-textarea { resize: vertical; min-height: 90px; }
.gle-row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.gle-row3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
@media (max-width: 600px) { .gle-row2, .gle-row3 { grid-template-columns: 1fr; } }

/* Cat selector */
.gle-cat-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 6px; }
.gle-cat-option { position: relative; }
.gle-cat-option input[type="radio"] { position: absolute; opacity: 0; }
.gle-cat-label {
    display: flex; flex-direction: column; align-items: center; gap: 4px;
    padding: 10px 6px; border: 1px solid var(--border); border-radius: var(--radius);
    cursor: pointer; transition: all .15s; background: var(--surface-2);
    font-size: 0.65rem; font-weight: 600; color: var(--text-2); text-align: center;
}
.gle-cat-label i { font-size: 1.1rem; }
.gle-cat-label:hover { border-color: #10b981; color: #10b981; }
.gle-cat-option input:checked + .gle-cat-label {
    border-color: #10b981; background: rgba(16,185,129,.08); color: #059669;
    box-shadow: 0 0 0 2px rgba(16,185,129,.2);
}

/* Audience toggle */
.gle-audience { display: flex; gap: 6px; }
.gle-aud-option { flex: 1; position: relative; }
.gle-aud-option input[type="radio"] { position: absolute; opacity: 0; }
.gle-aud-label {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    padding: 9px; border: 1px solid var(--border); border-radius: var(--radius);
    cursor: pointer; transition: all .15s; background: var(--surface-2);
    font-size: 0.75rem; font-weight: 600; color: var(--text-2);
}
.gle-aud-label:hover { border-color: var(--border-h); }
.gle-aud-option.acheteur input:checked + .gle-aud-label { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }
.gle-aud-option.habitant input:checked + .gle-aud-label { border-color: #8b5cf6; background: #f5f3ff; color: #6d28d9; }
.gle-aud-option.tous    input:checked + .gle-aud-label  { border-color: #10b981; background: #ecfdf5; color: #059669; }

/* Note étoiles */
.gle-stars { display: flex; gap: 4px; flex-direction: row-reverse; justify-content: flex-end; }
.gle-stars input { display: none; }
.gle-stars label {
    font-size: 1.4rem; color: #d1d5db; cursor: pointer;
    transition: color .1s; line-height: 1;
}
.gle-stars input:checked ~ label,
.gle-stars label:hover,
.gle-stars label:hover ~ label { color: #f59e0b; }
.gle-note-input { width: 70px; text-align: center; }

/* Status toggle */
.gle-status-toggle { display: flex; gap: 8px; }
.gle-status-btn {
    flex: 1; padding: 10px; border: 1px solid var(--border); border-radius: var(--radius);
    text-align: center; cursor: pointer; font-size: 0.78rem; font-weight: 600;
    color: var(--text-2); background: var(--surface-2); transition: all .15s;
    position: relative;
}
.gle-status-btn input { position: absolute; opacity: 0; }
.gle-status-btn.draft-btn:has(input:checked)  { border-color: #d97706; background: #fffbeb; color: #92400e; }
.gle-status-btn.pub-btn:has(input:checked)    { border-color: #10b981; background: #ecfdf5; color: #065f46; }

/* SEO meter */
.gle-seo-meter { margin-top: 4px; }
.gle-seo-bar { height: 4px; background: var(--surface-2); border-radius: 2px; overflow: hidden; margin: 4px 0; }
.gle-seo-fill { height: 100%; border-radius: 2px; transition: width .3s; background: #10b981; }
.gle-seo-hint { font-size: 0.68rem; color: var(--text-3); }

/* GMB preview */
.gle-gmb-preview {
    display: none; padding: 10px 12px; margin-top: 6px;
    background: rgba(66,133,244,.06); border: 1px solid rgba(66,133,244,.2);
    border-radius: var(--radius); font-size: 0.73rem; color: #1d4ed8;
    display: flex; align-items: center; gap: 8px;
}
.gle-gmb-preview i { color: #4285f4; }

/* Buttons */
.gle-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 22px; border-radius: var(--radius);
    font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none;
    font-family: var(--font); text-decoration: none; transition: all .15s; line-height: 1.3;
}
.gle-btn-primary { background: #10b981; color: #fff; box-shadow: 0 1px 6px rgba(16,185,129,.2); }
.gle-btn-primary:hover { background: #059669; transform: translateY(-1px); }
.gle-btn-outline { background: var(--surface); color: var(--text-2); border: 1px solid var(--border); }
.gle-btn-outline:hover { border-color: #10b981; color: #10b981; }
.gle-btn-ai { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; }
.gle-btn-ai:hover { opacity: .9; transform: translateY(-1px); }

/* Switch */
.gle-switch { display: flex; align-items: center; gap: 8px; cursor: pointer; user-select: none; }
.gle-switch input { display: none; }
.gle-switch-track {
    width: 38px; height: 22px; background: var(--surface-2);
    border: 1px solid var(--border); border-radius: 11px;
    position: relative; transition: all .2s; flex-shrink: 0;
}
.gle-switch-track::after {
    content: ''; position: absolute; top: 3px; left: 3px;
    width: 14px; height: 14px; border-radius: 50%;
    background: var(--text-3); transition: all .2s;
}
.gle-switch input:checked + .gle-switch-track { background: #10b981; border-color: #10b981; }
.gle-switch input:checked + .gle-switch-track::after { transform: translateX(16px); background: #fff; }
.gle-switch-label { font-size: 0.8rem; font-weight: 600; color: var(--text-2); }

/* Flash */
.gle-alert {
    padding: 12px 16px; border-radius: var(--radius); margin-bottom: 16px;
    font-size: 0.85rem; font-weight: 600; display: flex; align-items: center; gap: 8px;
}
.gle-alert.error { background: rgba(220,38,38,.06); color: var(--red,#dc2626); border: 1px solid rgba(220,38,38,.15); }
</style>

<div class="gle-wrap">

<?php if ($error): ?>
<div class="gle-alert error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="gle-header">
    <h2>
        <i class="fas fa-<?= $isNew ? 'plus-circle' : 'edit' ?>"></i>
        <?= $pageTitle ?>
    </h2>
    <div style="display:flex;gap:8px;">
        <?php if (!$isNew): ?>
        <button type="button" onclick="GLE.generateAI(<?= $itemId ?>)" class="gle-btn gle-btn-ai">
            <i class="fas fa-robot"></i> Enrichir avec IA
        </button>
        <?php endif; ?>
        <a href="/admin/dashboard.php?page=guide-local" class="gle-back">
            <i class="fas fa-arrow-left"></i> Retour au guide
        </a>
    </div>
</div>

<form method="POST" id="gleForm" action="">
<input type="hidden" name="id" value="<?= $itemId ?>">

<div class="gle-grid">

    <!-- ── COLONNE PRINCIPALE ── -->
    <div style="display:flex;flex-direction:column;gap:18px;">

        <!-- Infos essentielles -->
        <div class="gle-card">
            <div class="gle-card-head">
                <i class="fas fa-store"></i>
                <h3>Informations du partenaire</h3>
            </div>
            <div class="gle-card-body">
                <div class="gle-field">
                    <label class="gle-label">Nom <span class="req">*</span></label>
                    <input type="text" name="nom" class="gle-input" required
                           placeholder="Ex : Boulangerie La Mie Dorée"
                           value="<?= htmlspecialchars($item['nom']) ?>"
                           oninput="GLE.autoSlug(this.value)">
                </div>

                <div class="gle-field">
                    <label class="gle-label">Slug URL <span class="hint">auto-généré</span></label>
                    <input type="text" name="slug" id="gleSlug" class="gle-input"
                           placeholder="boulangerie-la-mie-doree"
                           value="<?= htmlspecialchars($item['slug']) ?>">
                </div>

                <div class="gle-field">
                    <label class="gle-label">Description</label>
                    <textarea name="description" class="gle-textarea"
                              placeholder="Décrivez ce partenaire et son intérêt pour les habitants/acheteurs…" rows="3"><?= htmlspecialchars($item['description']) ?></textarea>
                </div>

                <!-- Catégorie -->
                <div class="gle-field">
                    <label class="gle-label">Catégorie <span class="req">*</span></label>
                    <div class="gle-cat-grid">
                        <?php foreach ($partnerCategories as $key => $cat): ?>
                        <div class="gle-cat-option">
                            <input type="radio" name="categorie" id="cat_<?= $key ?>" value="<?= $key ?>"
                                   <?= ($item['categorie'] === $key) ? 'checked' : '' ?>>
                            <label class="gle-cat-label" for="cat_<?= $key ?>">
                                <i class="fas <?= $cat['icon'] ?>"></i>
                                <?= $cat['label'] ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Audience -->
                <div class="gle-field">
                    <label class="gle-label">Audience cible
                        <span class="hint">— définit dans quel contexte afficher ce lieu</span>
                    </label>
                    <div class="gle-audience">
                        <div class="gle-aud-option acheteur">
                            <input type="radio" name="audience" id="aud_ach" value="acheteur" <?= $item['audience']==='acheteur'?'checked':'' ?>>
                            <label class="gle-aud-label" for="aud_ach"><i class="fas fa-home"></i> Acheteurs</label>
                        </div>
                        <div class="gle-aud-option habitant">
                            <input type="radio" name="audience" id="aud_hab" value="habitant" <?= $item['audience']==='habitant'?'checked':'' ?>>
                            <label class="gle-aud-label" for="aud_hab"><i class="fas fa-map-marker-alt"></i> Résidents</label>
                        </div>
                        <div class="gle-aud-option tous">
                            <input type="radio" name="audience" id="aud_tous" value="tous" <?= ($item['audience']==='tous'||empty($item['audience']))?'checked':'' ?>>
                            <label class="gle-aud-label" for="aud_tous"><i class="fas fa-users"></i> Universelle</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Localisation -->
        <div class="gle-card">
            <div class="gle-card-head">
                <i class="fas fa-map-marker-alt"></i>
                <h3>Localisation</h3>
            </div>
            <div class="gle-card-body">
                <div class="gle-field">
                    <label class="gle-label">Adresse complète</label>
                    <input type="text" name="adresse" class="gle-input"
                           placeholder="12 Rue du Commerce"
                           value="<?= htmlspecialchars($item['adresse']) ?>"
                           oninput="GLE.updateSeoMeta()">
                </div>
                <div class="gle-row3">
                    <div class="gle-field" style="grid-column:span 2">
                        <label class="gle-label">Ville</label>
                        <input type="text" name="ville" class="gle-input"
                               placeholder="Bordeaux"
                               value="<?= htmlspecialchars($item['ville']) ?>"
                               oninput="GLE.updateSeoMeta()">
                    </div>
                    <div class="gle-field">
                        <label class="gle-label">Code postal</label>
                        <input type="text" name="code_postal" class="gle-input"
                               placeholder="33000"
                               value="<?= htmlspecialchars($item['code_postal']) ?>">
                    </div>
                </div>
                <div class="gle-field">
                    <label class="gle-label">Secteur associé</label>
                    <select name="secteur_id" class="gle-select">
                        <option value="">— Aucun secteur —</option>
                        <?php foreach ($secteurs as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"
                                <?= ((int)$item['secteur_id'] === (int)$s['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nom']) ?> (<?= htmlspecialchars($s['ville']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="gle-row2">
                    <div class="gle-field">
                        <label class="gle-label">Latitude <span class="hint">GPS optionnel</span></label>
                        <input type="number" step="0.0000001" name="latitude" class="gle-input"
                               placeholder="44.8378" value="<?= htmlspecialchars((string)$item['latitude']) ?>">
                    </div>
                    <div class="gle-field">
                        <label class="gle-label">Longitude</label>
                        <input type="number" step="0.0000001" name="longitude" class="gle-input"
                               placeholder="-0.5792" value="<?= htmlspecialchars((string)$item['longitude']) ?>">
                    </div>
                </div>
                <button type="button" onclick="GLE.geocode()" class="gle-btn gle-btn-outline" style="width:fit-content">
                    <i class="fas fa-crosshairs"></i> Géocoder l'adresse
                </button>
            </div>
        </div>

        <!-- Contact & liens -->
        <div class="gle-card">
            <div class="gle-card-head">
                <i class="fas fa-link"></i>
                <h3>Contact & Liens</h3>
            </div>
            <div class="gle-card-body">
                <div class="gle-row2">
                    <div class="gle-field">
                        <label class="gle-label">Téléphone</label>
                        <input type="tel" name="telephone" class="gle-input"
                               placeholder="05 56 00 00 00"
                               value="<?= htmlspecialchars($item['telephone']) ?>">
                    </div>
                    <div class="gle-field">
                        <label class="gle-label">Note / avis <span class="hint">sur 5</span></label>
                        <input type="number" name="note" min="0" max="5" step="0.1"
                               class="gle-input gle-note-input"
                               placeholder="4.5" value="<?= htmlspecialchars((string)$item['note']) ?>">
                    </div>
                </div>
                <div class="gle-field">
                    <label class="gle-label">Site web</label>
                    <input type="url" name="site_web" class="gle-input"
                           placeholder="https://www.exemple.fr"
                           value="<?= htmlspecialchars($item['site_web']) ?>">
                </div>
                <div class="gle-field">
                    <label class="gle-label">URL Fiche Google My Business
                        <span class="hint">— signal SEO local fort</span>
                    </label>
                    <input type="url" name="gmb_url" id="gleGmbUrl" class="gle-input"
                           placeholder="https://maps.google.com/…"
                           value="<?= htmlspecialchars($item['gmb_url']) ?>"
                           oninput="GLE.checkGmb(this.value)">
                    <?php if (!empty($item['gmb_url'])): ?>
                    <div class="gle-gmb-preview">
                        <i class="fab fa-google"></i>
                        Fiche GMB liée — citation locale active
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SEO -->
        <div class="gle-card">
            <div class="gle-card-head">
                <i class="fas fa-search"></i>
                <h3>SEO — Référencement local</h3>
            </div>
            <div class="gle-card-body">
                <div class="gle-field">
                    <label class="gle-label">Meta title
                        <span class="hint">— idéalement "NomLieu + Ville | NomConseil"</span>
                    </label>
                    <input type="text" name="meta_title" id="gleMetaTitle" class="gle-input"
                           maxlength="65"
                           placeholder="Boulangerie La Mie Dorée — Bordeaux Chartrons"
                           value="<?= htmlspecialchars($item['meta_title']) ?>"
                           oninput="GLE.updateSeoScore()">
                    <div class="gle-seo-meter">
                        <div class="gle-seo-bar"><div class="gle-seo-fill" id="gleMetaBar" style="width:0%"></div></div>
                        <span class="gle-seo-hint" id="gleMetaHint">0 / 65 caractères</span>
                    </div>
                </div>
                <div class="gle-field">
                    <label class="gle-label">Meta description
                        <span class="hint">— 150–160 car. avec contexte local</span>
                    </label>
                    <textarea name="meta_desc" id="gleMetaDesc" class="gle-textarea"
                              maxlength="165" rows="3"
                              placeholder="Découvrez la Boulangerie La Mie Dorée, incontournable du quartier Chartrons à Bordeaux. Pains, viennoiseries artisanales à 2 min à pied…"
                              oninput="GLE.updateSeoScore()"><?= htmlspecialchars($item['meta_desc']) ?></textarea>
                    <div class="gle-seo-meter">
                        <div class="gle-seo-bar"><div class="gle-seo-fill" id="gleDescBar" style="width:0%"></div></div>
                        <span class="gle-seo-hint" id="gleDescHint">0 / 165 caractères</span>
                    </div>
                </div>
                <button type="button" onclick="GLE.generateMeta()" class="gle-btn gle-btn-ai" style="width:fit-content">
                    <i class="fas fa-robot"></i> Générer les métas avec IA
                </button>
            </div>
        </div>

    </div><!-- /colonne principale -->

    <!-- ── SIDEBAR ── -->
    <div style="display:flex;flex-direction:column;gap:18px;">

        <!-- Publication -->
        <div class="gle-card">
            <div class="gle-card-head">
                <i class="fas fa-rocket"></i>
                <h3>Publication</h3>
            </div>
            <div class="gle-card-body">
                <div class="gle-status-toggle">
                    <label class="gle-status-btn draft-btn">
                        <input type="radio" name="status" value="draft" <?= $item['status']==='draft'?'checked':'' ?>>
                        <i class="fas fa-pencil-alt"></i> Brouillon
                    </label>
                    <label class="gle-status-btn pub-btn">
                        <input type="radio" name="status" value="published" <?= $item['status']==='published'?'checked':'' ?>>
                        <i class="fas fa-check"></i> Publié
                    </label>
                </div>

                <label class="gle-switch">
                    <input type="checkbox" name="is_featured" value="1" <?= !empty($item['is_featured'])?'checked':'' ?>>
                    <span class="gle-switch-track"></span>
                    <span class="gle-switch-label">⭐ Mettre en avant</span>
                </label>

                <div style="display:flex;flex-direction:column;gap:8px;margin-top:4px;">
                    <button type="submit" class="gle-btn gle-btn-primary">
                        <i class="fas fa-save"></i>
                        <?= $isNew ? 'Créer le partenaire' : 'Enregistrer' ?>
                    </button>
                    <?php if (!$isNew): ?>
                    <a href="?page=guide-local&action=create" class="gle-btn gle-btn-outline" style="justify-content:center">
                        <i class="fas fa-plus"></i> Nouveau partenaire
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Score SEO sidebar -->
        <div class="gle-card" id="gleSeoCard">
            <div class="gle-card-head">
                <i class="fas fa-chart-line"></i>
                <h3>Score SEO local</h3>
            </div>
            <div class="gle-card-body" id="gleSeoChecklist">
                <!-- Rempli par JS -->
            </div>
        </div>

        <!-- IA Suggestions -->
        <div class="gle-card" id="gleAiPanel" style="display:none">
            <div class="gle-card-head">
                <i class="fas fa-robot"></i>
                <h3>Suggestions IA</h3>
            </div>
            <div class="gle-card-body" id="gleAiContent">
                <div style="text-align:center;padding:20px;color:var(--text-3);font-size:.8rem">
                    <i class="fas fa-spinner fa-spin" style="font-size:1.5rem;margin-bottom:10px;display:block"></i>
                    Génération en cours…
                </div>
            </div>
        </div>

    </div><!-- /sidebar -->

</div><!-- .gle-grid -->
</form>
</div><!-- .gle-wrap -->

<script>
const GLE = {
    slugTimer: null,

    autoSlug(nom) {
        clearTimeout(this.slugTimer);
        this.slugTimer = setTimeout(() => {
            const slug = nom.toLowerCase()
                .replace(/[àáâã]/g,'a').replace(/[èéêë]/g,'e').replace(/[ìíîï]/g,'i')
                .replace(/[òóôõ]/g,'o').replace(/[ùúûü]/g,'u').replace(/ç/g,'c')
                .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
            const el = document.getElementById('gleSlug');
            if (!el.dataset.manual) el.value = slug;
        }, 350);
    },

    updateSeoScore() {
        const title = document.getElementById('gleMetaTitle');
        const desc  = document.getElementById('gleMetaDesc');
        if (!title) return;

        const tLen = title.value.length;
        const dLen = desc ? desc.value.length : 0;

        // Title bar
        const tPct = Math.min(100, (tLen / 65) * 100);
        const tBar = document.getElementById('gleMetaBar');
        if (tBar) {
            tBar.style.width = tPct + '%';
            tBar.style.background = tLen >= 40 && tLen <= 65 ? '#10b981' : tLen < 40 ? '#f59e0b' : '#ef4444';
        }
        const tHint = document.getElementById('gleMetaHint');
        if (tHint) tHint.textContent = `${tLen} / 65 caractères${tLen < 40 ? ' — trop court' : tLen > 65 ? ' — trop long' : ' ✓'}`;

        // Desc bar
        const dPct = Math.min(100, (dLen / 165) * 100);
        const dBar = document.getElementById('gleDescBar');
        if (dBar) {
            dBar.style.width = dPct + '%';
            dBar.style.background = dLen >= 120 && dLen <= 160 ? '#10b981' : dLen < 120 ? '#f59e0b' : '#ef4444';
        }
        const dHint = document.getElementById('gleDescHint');
        if (dHint) dHint.textContent = `${dLen} / 165 caractères${dLen < 120 ? ' — enrichir' : dLen > 165 ? ' — trop long' : ' ✓'}`;

        this.updateChecklist();
    },

    updateSeoMeta() { this.updateChecklist(); },

    updateChecklist() {
        const checks = {
            'Nom renseigné'        : document.querySelector('[name=nom]')?.value.length > 2,
            'Catégorie choisie'    : !!document.querySelector('[name=categorie]:checked'),
            'Adresse complète'     : document.querySelector('[name=adresse]')?.value.length > 5,
            'Ville renseignée'     : document.querySelector('[name=ville]')?.value.length > 1,
            'Secteur associé'      : !!document.querySelector('[name=secteur_id]')?.value,
            'Meta title optimisé'  : (() => { const t = document.getElementById('gleMetaTitle')?.value?.length || 0; return t >= 40 && t <= 65; })(),
            'Meta description OK'  : (() => { const d = document.getElementById('gleMetaDesc')?.value?.length || 0; return d >= 120 && d <= 165; })(),
            'Fiche GMB liée'       : document.querySelector('[name=gmb_url]')?.value?.startsWith('http'),
            'Coordonnées GPS'      : document.querySelector('[name=latitude]')?.value !== '',
        };
        const total = Object.keys(checks).length;
        const done  = Object.values(checks).filter(Boolean).length;
        const score = Math.round((done / total) * 100);

        const cl = document.getElementById('gleSeoChecklist');
        if (!cl) return;
        const color = score >= 75 ? '#10b981' : score >= 50 ? '#f59e0b' : '#ef4444';

        cl.innerHTML = `
            <div style="text-align:center;margin-bottom:12px">
                <div style="font-family:var(--font-display);font-size:2rem;font-weight:800;color:${color};line-height:1">${score}</div>
                <div style="font-size:0.62rem;color:var(--text-3);text-transform:uppercase;letter-spacing:.06em;font-weight:600">Score SEO</div>
                <div style="height:4px;background:var(--surface-2);border-radius:2px;margin-top:8px;overflow:hidden">
                    <div style="width:${score}%;height:100%;background:${color};border-radius:2px;transition:width .4s"></div>
                </div>
            </div>
            ${Object.entries(checks).map(([label, ok]) => `
            <div style="display:flex;align-items:center;gap:7px;font-size:0.74rem;padding:4px 0;color:${ok?'var(--text-2)':'var(--text-3)'}">
                <i class="fas fa-${ok?'check-circle':'circle'}" style="color:${ok?'#10b981':'var(--border)'};font-size:0.72rem;flex-shrink:0"></i>
                ${label}
            </div>`).join('')}
        `;
    },

    checkGmb(url) {
        const preview = document.querySelector('.gle-gmb-preview');
        if (!preview) return;
        preview.style.display = url.startsWith('http') ? 'flex' : 'none';
        this.updateChecklist();
    },

    geocode() {
        const addr  = document.querySelector('[name=adresse]').value;
        const ville = document.querySelector('[name=ville]').value;
        if (!addr) return alert('Saisissez une adresse d\'abord');
        const query = encodeURIComponent(addr + ' ' + ville);
        fetch(`https://nominatim.openstreetmap.org/search?q=${query}&format=json&limit=1`)
            .then(r => r.json())
            .then(d => {
                if (d && d[0]) {
                    document.querySelector('[name=latitude]').value  = parseFloat(d[0].lat).toFixed(7);
                    document.querySelector('[name=longitude]').value = parseFloat(d[0].lon).toFixed(7);
                    this.updateChecklist();
                } else { alert('Adresse introuvable — vérifiez le format'); }
            }).catch(() => alert('Erreur de géocodage'));
    },

    async generateMeta() {
        const nom     = document.querySelector('[name=nom]').value;
        const ville   = document.querySelector('[name=ville]').value;
        const cat     = document.querySelector('[name=categorie]:checked')?.value || 'autre';
        const desc    = document.querySelector('[name=description]').value;
        if (!nom) return alert('Saisissez le nom du partenaire d\'abord');

        const btn = document.querySelector('[onclick*="generateMeta"]');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération…';
        btn.disabled = true;

        try {
            const r = await fetch('/admin/modules/content/guide-local/ai/generate.php', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ action:'meta', nom, ville, categorie:cat, description:desc })
            });
            const d = await r.json();
            if (d.success && d.meta_title) {
                document.getElementById('gleMetaTitle').value = d.meta_title;
                document.getElementById('gleMetaDesc').value  = d.meta_desc;
                this.updateSeoScore();
            } else { alert(d.error || 'Erreur de génération'); }
        } catch(e) { alert('Erreur réseau'); }
        finally {
            btn.innerHTML = '<i class="fas fa-robot"></i> Générer les métas avec IA';
            btn.disabled  = false;
        }
    },

    async generateAI(id) {
        const panel = document.getElementById('gleAiPanel');
        panel.style.display = 'block';
        panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
        try {
            const r = await fetch('/admin/modules/content/guide-local/ai/generate.php', {
                method:'POST',
                headers:{'Content-Type':'application/json'},
                body:JSON.stringify({ action:'enrich', id })
            });
            const d = await r.json();
            const content = document.getElementById('gleAiContent');
            if (d.success) {
                content.innerHTML = `
                    <p style="font-size:.78rem;color:var(--text-2);margin:0 0 10px">Suggestions basées sur la localisation :</p>
                    ${d.suggestions.map(s=>`
                    <div style="padding:8px 10px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius);margin-bottom:6px;font-size:.76rem">
                        <strong style="color:var(--text)">${s.label}</strong><br>
                        <span style="color:var(--text-2)">${s.value}</span>
                        <button onclick="GLE.applySuggestion('${s.field}','${s.value.replace(/'/g,"\\'")}')"
                                style="float:right;background:#10b981;color:#fff;border:none;border-radius:4px;padding:2px 8px;font-size:.65rem;cursor:pointer;margin-top:2px">
                            Appliquer
                        </button>
                    </div>`).join('')}`;
            } else {
                content.innerHTML = `<p style="color:var(--red,#dc2626);font-size:.8rem">${d.error || 'Erreur'}</p>`;
            }
        } catch(e) {
            document.getElementById('gleAiContent').innerHTML = '<p style="color:var(--red);font-size:.8rem">Erreur réseau</p>';
        }
    },

    applySuggestion(field, value) {
        const el = document.querySelector(`[name="${field}"]`);
        if (el) { el.value = value; this.updateSeoScore(); }
    }
};

// Init
document.getElementById('gleSlug')?.addEventListener('input', function() {
    this.dataset.manual = '1';
});
document.addEventListener('DOMContentLoaded', () => GLE.updateSeoScore());
</script>