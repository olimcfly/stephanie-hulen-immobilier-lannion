<?php
/**
 * MODULE ADMIN — Guides & Ressources — EDIT (create + update)
 * /admin/modules/content/guides/edit.php
 * Formulaire unifié : création (sans id) et édition (avec id)
 */

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

// ─── Déterminer le mode ───
$id    = (int)($_GET['id'] ?? 0);
$isNew = ($id === 0);

if ($isNew) {
    $item = [
        'title'       => '',
        'slug'        => '',
        'description' => '',
        'type'        => 'ebook',
        'format'      => 'PDF',
        'niveau'      => 'débutant',
        'content'     => '',
        'headline'    => '',
        'file_url'    => '',
        'file_size'   => 0,
        'status'      => 'inactive',
    ];
} else {
    try {
        $stmt = $pdo->prepare("SELECT * FROM guides WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$item) {
            echo '<div style="padding:40px;text-align:center;color:#dc2626">Guide introuvable (ID: ' . $id . '). <a href="?page=guides">Retour</a></div>';
            return;
        }
    } catch (Exception $e) {
        error_log("[Guides Edit] " . $e->getMessage());
        echo '<div style="padding:40px;text-align:center;color:#dc2626">Erreur chargement. <a href="?page=guides">Retour</a></div>';
        return;
    }
}

// ─── CSRF ───
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Message flash ───
$flashMsg = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'created') $flashMsg = 'Guide créé avec succès !';
}
?>

<style>
/* ══ GUIDES EDIT ══════════════════════════════════════════════ */
.gui-edit-wrap { font-family:var(--font,'Inter',sans-serif); max-width:920px; margin:0 auto; }

.gui-edit-head { display:flex; align-items:center; gap:14px; margin-bottom:28px; }
.gui-edit-back { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:var(--text-3,#9ca3af); text-decoration:none; transition:all .15s; border:1px solid var(--border,#e5e7eb); }
.gui-edit-back:hover { color:#7c3aed; border-color:var(--border,#e5e7eb); background:rgba(124,58,237,.07); }
.gui-edit-title { font-size:1.65rem; font-weight:700; color:var(--text,#111827); margin:0; }
.gui-edit-badge { display:inline-flex; align-items:center; gap:6px; font-size:.72rem; font-weight:700; padding:4px 11px; border-radius:10px; }
.gui-edit-badge.create { background:rgba(124,58,237,.1); color:#7c3aed; }
.gui-edit-badge.edit { background:rgba(16,185,129,.1); color:#059669; }

.gui-edit-alerts { margin-bottom:20px; }
.gui-edit-alert { padding:12px 16px; border-radius:10px; font-size:.85rem; font-weight:600; margin-bottom:8px; display:flex; align-items:center; gap:8px; }
.gui-edit-alert.error { background:#fef2f2; color:#dc2626; border:1px solid rgba(220,38,38,.12); }
.gui-edit-alert.success { background:#f0fdf4; color:#059669; border:1px solid rgba(5,150,105,.12); }
.gui-edit-alert.info { background:#eff6ff; color:#0369a1; border:1px solid rgba(3,105,161,.12); }
.gui-edit-alert i { font-size:.9rem; }

.gui-edit-form { background:var(--surface,#fff); border:1px solid var(--border,#e5e7eb); border-radius:14px; padding:26px; }

.gui-form-group { margin-bottom:22px; }
.gui-form-group:last-child { margin-bottom:0; }
.gui-form-label { display:block; font-size:.82rem; font-weight:700; color:var(--text,#111827); margin-bottom:8px; letter-spacing:-.02em; }
.gui-form-label .req { color:#dc2626; }
.gui-form-label .hint { font-size:.72rem; font-weight:500; color:var(--text-3,#9ca3af); display:block; margin-top:3px; }
.gui-form-input,.gui-form-select,.gui-form-textarea {
    width:100%; padding:11px 14px; border:1px solid var(--border,#e5e7eb); border-radius:10px;
    background:var(--surface,#fff); color:var(--text,#111827); font-size:.85rem; font-family:inherit;
    transition:all .15s;
}
.gui-form-input:focus,.gui-form-select:focus,.gui-form-textarea:focus {
    outline:none; border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.1);
}
.gui-form-textarea { resize:vertical; min-height:100px; }

.gui-form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:768px) { .gui-form-row { grid-template-columns:1fr; } }

.gui-form-section { background:var(--surface-2,#f9fafb); border-radius:12px; padding:16px; margin-bottom:16px; border:1px solid var(--border,#f3f4f6); }
.gui-form-section-title { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-3,#9ca3af); margin-bottom:12px; display:flex; align-items:center; gap:8px; }
.gui-form-section-title i { font-size:.85rem; }

.gui-form-footer { display:flex; align-items:center; justify-content:flex-end; padding-top:22px; border-top:1px solid var(--border,#e5e7eb); gap:10px; }
.gui-form-actions { display:flex; gap:10px; }
.gui-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; border-radius:10px; font-size:.83rem; font-weight:700; cursor:pointer; border:none; transition:all .15s; font-family:inherit; text-decoration:none; line-height:1.3; }
.gui-btn-primary { background:#7c3aed; color:#fff; box-shadow:0 1px 4px rgba(124,58,237,.22); }
.gui-btn-primary:hover { background:#6d28d9; transform:translateY(-1px); }
.gui-btn-primary:disabled { opacity:.5; cursor:not-allowed; transform:none; }
.gui-btn-outline { background:var(--surface,#fff); color:var(--text-2,#6b7280); border:1px solid var(--border,#e5e7eb); }
.gui-btn-outline:hover { border-color:#7c3aed; color:#7c3aed; }

.gui-char-count { font-size:.7rem; color:var(--text-3,#9ca3af); margin-top:4px; }
.gui-slug-preview { font-size:.72rem; color:var(--text-3,#9ca3af); font-family:monospace; margin-top:6px; padding:6px 10px; background:var(--surface-2,#f9fafb); border-radius:6px; border-left:2px solid #7c3aed; }
</style>

<div class="gui-edit-wrap">

<!-- Header -->
<div class="gui-edit-head">
    <a href="?page=guides" class="gui-edit-back" title="Retour"><i class="fas fa-arrow-left"></i></a>
    <div style="flex:1">
        <h1 class="gui-edit-title"><?= $isNew ? 'Nouveau guide' : 'Modifier : ' . htmlspecialchars($item['title']) ?></h1>
        <span class="gui-edit-badge <?= $isNew ? 'create' : 'edit' ?>">
            <i class="fas fa-<?= $isNew ? 'plus' : 'pen' ?>"></i>
            <?= $isNew ? 'Création' : 'Édition #' . $id ?>
        </span>
    </div>
</div>

<!-- Flash message -->
<?php if ($flashMsg): ?>
<div class="gui-edit-alerts">
    <div class="gui-edit-alert success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($flashMsg) ?></div>
</div>
<?php endif; ?>

<!-- Alerts zone (JS) -->
<div class="gui-edit-alerts" id="guideAlerts"></div>

<!-- Form -->
<form id="guideForm" class="gui-edit-form" onsubmit="return saveGuide(event)">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <input type="hidden" name="id" id="guideId" value="<?= $id ?>">

    <!-- ──── SECTION: Infos principales ──── -->
    <div class="gui-form-section">
        <div class="gui-form-section-title"><i class="fas fa-file-lines"></i> Infos principales</div>

        <!-- Titre -->
        <div class="gui-form-group">
            <label class="gui-form-label">
                Titre <span class="req">*</span>
                <span class="hint">Titre principal du guide</span>
            </label>
            <input type="text" name="title" id="guideTitle" class="gui-form-input" value="<?= htmlspecialchars($item['title']) ?>" maxlength="200" required autofocus>
        </div>

        <!-- Slug -->
        <div class="gui-form-group">
            <label class="gui-form-label">
                Slug <span class="req">*</span>
                <span class="hint">URL-friendly identifier</span>
            </label>
            <input type="text" name="slug" class="gui-form-input" id="guideSlug" value="<?= htmlspecialchars($item['slug']) ?>" required>
            <div class="gui-slug-preview" id="slugPreview">/guide/<?= htmlspecialchars($item['slug'] ?: 'mon-guide') ?></div>
        </div>

        <!-- Description -->
        <div class="gui-form-group">
            <label class="gui-form-label">
                Description <span class="req">*</span>
                <span class="hint">Résumé court du guide (moteurs de recherche)</span>
            </label>
            <textarea name="description" id="guideDescription" class="gui-form-textarea" maxlength="500" required><?= htmlspecialchars($item['description']) ?></textarea>
            <div class="gui-char-count"><span id="descCount">0</span>/500</div>
        </div>

        <!-- Headline (optionnel) -->
        <div class="gui-form-group">
            <label class="gui-form-label">
                Headline (optionnel)
                <span class="hint">Titre alternatif pour les réseaux sociaux</span>
            </label>
            <input type="text" name="headline" id="guideHeadline" class="gui-form-input" value="<?= htmlspecialchars($item['headline']) ?>" maxlength="120">
        </div>

        <!-- Content (optionnel) -->
        <div class="gui-form-group">
            <label class="gui-form-label">
                Contenu (optionnel)
                <span class="hint">Contenu long-form du guide</span>
            </label>
            <textarea name="content" id="guideContent" class="gui-form-textarea" style="min-height:200px"><?= htmlspecialchars($item['content']) ?></textarea>
        </div>
    </div>

    <!-- ──── SECTION: Type & Format ──── -->
    <div class="gui-form-section">
        <div class="gui-form-section-title"><i class="fas fa-cube"></i> Type & Format</div>

        <div class="gui-form-row">
            <div class="gui-form-group">
                <label class="gui-form-label">Type <span class="req">*</span></label>
                <select name="type" id="guideType" class="gui-form-select" required>
                    <?php
                    $types = ['ebook'=>'eBook / Guide PDF','checklist'=>'Checklist','template'=>'Template / Modèle','webinaire'=>'Webinaire / Vidéo','tool'=>'Outil / Calculatrice','guide'=>'Guide / Tutoriel','other'=>'Autre'];
                    foreach ($types as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= ($item['type'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="gui-form-group">
                <label class="gui-form-label">Format <span class="req">*</span></label>
                <select name="format" id="guideFormat" class="gui-form-select" required>
                    <?php
                    $formats = ['PDF'=>'PDF','Google Sheets'=>'Google Sheets','Excel'=>'Excel','HTML'=>'HTML','Video'=>'Vidéo','Audio'=>'Audio','Archive'=>'Archive (ZIP)'];
                    foreach ($formats as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= ($item['format'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="gui-form-row">
            <div class="gui-form-group">
                <label class="gui-form-label">Niveau <span class="req">*</span></label>
                <select name="niveau" id="guideNiveau" class="gui-form-select" required>
                    <?php
                    $niveaux = ['débutant'=>'Débutant','intermédiaire'=>'Intermédiaire','avancé'=>'Avancé','expert'=>'Expert'];
                    foreach ($niveaux as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= ($item['niveau'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="gui-form-group">
                <label class="gui-form-label">Note initiale (optionnel)</label>
                <input type="number" name="rating" class="gui-form-input" value="<?= htmlspecialchars($item['rating'] ?? 0) ?>" min="0" max="5" step="0.5" placeholder="0 à 5 étoiles">
            </div>
        </div>
    </div>

    <!-- ──── SECTION: Téléchargement ──── -->
    <div class="gui-form-section">
        <div class="gui-form-section-title"><i class="fas fa-file-download"></i> Téléchargement (optionnel)</div>

        <div class="gui-form-group">
            <label class="gui-form-label">
                URL du fichier
                <span class="hint">Lien complet vers le fichier à télécharger</span>
            </label>
            <input type="url" name="file_url" id="guideFileUrl" class="gui-form-input" value="<?= htmlspecialchars($item['file_url']) ?>" placeholder="https://example.com/file.pdf">
        </div>

        <div class="gui-form-group">
            <label class="gui-form-label">
                Taille du fichier (octets)
                <span class="hint">Taille en octets (ex: 1048576 = 1 MB)</span>
            </label>
            <input type="number" name="file_size" id="guideFileSize" class="gui-form-input" value="<?= (int)($item['file_size'] ?? 0) ?>" min="0" placeholder="0">
        </div>
    </div>

    <!-- ──── SECTION: Statut ──── -->
    <div class="gui-form-section">
        <div class="gui-form-section-title"><i class="fas fa-toggle-on"></i> Statut de publication</div>

        <div style="display:flex; gap:20px">
            <label style="display:flex; align-items:center; gap:10px; font-size:.85rem; cursor:pointer">
                <input type="radio" name="status" value="inactive" <?= ($item['status'] ?? 'inactive') !== 'active' ? 'checked' : '' ?> style="cursor:pointer; width:16px; height:16px">
                <span style="color:var(--text,#111827); font-weight:600">Brouillon (inactif)</span>
            </label>
            <label style="display:flex; align-items:center; gap:10px; font-size:.85rem; cursor:pointer">
                <input type="radio" name="status" value="active" <?= ($item['status'] ?? '') === 'active' ? 'checked' : '' ?> style="cursor:pointer; width:16px; height:16px">
                <span style="color:var(--text,#111827); font-weight:600">Publié (actif)</span>
            </label>
        </div>
    </div>

    <!-- Actions -->
    <div class="gui-form-footer">
        <div class="gui-form-actions">
            <a href="?page=guides" class="gui-btn gui-btn-outline"><i class="fas fa-times"></i> Annuler</a>
            <button type="submit" class="gui-btn gui-btn-primary" id="guideSaveBtn">
                <i class="fas fa-<?= $isNew ? 'plus' : 'save' ?>"></i>
                <?= $isNew ? 'Créer le guide' : 'Enregistrer' ?>
            </button>
        </div>
    </div>
</form>

</div><!-- /gui-edit-wrap -->

<script>
const guideIsNew = <?= $isNew ? 'true' : 'false' ?>;

// Actualiser le slug depuis le titre
document.getElementById('guideTitle').addEventListener('input', function() {
    if (document.getElementById('guideSlug').dataset.custom) return;
    let slug = this.value
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
    document.getElementById('guideSlug').value = slug;
    updateSlugPreview();
});

// Marquer le slug comme modifié manuellement
document.getElementById('guideSlug').addEventListener('input', function() {
    this.dataset.custom = 'true';
    updateSlugPreview();
});

function updateSlugPreview() {
    const slug = document.getElementById('guideSlug').value;
    document.getElementById('slugPreview').textContent = '/guide/' + (slug || 'mon-guide');
}

// Compteur de caractères
const descTA = document.getElementById('guideDescription');
descTA.addEventListener('input', function() {
    document.getElementById('descCount').textContent = this.value.length;
});
document.getElementById('descCount').textContent = descTA.value.length;

// Sauvegarder via API
function saveGuide(e) {
    e.preventDefault();

    const btn = document.getElementById('guideSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';

    const form = document.getElementById('guideForm');
    const data = new FormData(form);
    data.append('action', 'save');

    fetch('?page=guides&action=api&ajax=1', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: data
    })
    .then(r => r.json())
    .then(d => {
        const alerts = document.getElementById('guideAlerts');
        if (d.success) {
            alerts.innerHTML = '<div class="gui-edit-alert success"><i class="fas fa-check-circle"></i> ' + d.message + '</div>';
            if (guideIsNew && d.id) {
                // Redirect to edit mode
                window.location.href = '?page=guides&action=edit&id=' + d.id + '&msg=created';
            }
        } else {
            alerts.innerHTML = '<div class="gui-edit-alert error"><i class="fas fa-exclamation-circle"></i> ' + (d.error || d.message || 'Erreur inconnue') + '</div>';
        }
    })
    .catch(err => {
        document.getElementById('guideAlerts').innerHTML = '<div class="gui-edit-alert error"><i class="fas fa-exclamation-circle"></i> Erreur réseau : ' + err.message + '</div>';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = guideIsNew
            ? '<i class="fas fa-plus"></i> Créer le guide'
            : '<i class="fas fa-save"></i> Enregistrer';
    });

    return false;
}
</script>
