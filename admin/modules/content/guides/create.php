<?php
/**
 * MODULE ADMIN — Guides & Ressources — CREATE
 * /admin/modules/content/guides/create.php
 * Formulaire de création pour nouveaux guides
 */

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

// ─── Traitement du formulaire ───
$errors = [];
$isPosted = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPosted) {
    $title       = trim($_POST['title'] ?? '');
    $slug        = trim($_POST['slug'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type        = trim($_POST['type'] ?? 'ebook');
    $format      = trim($_POST['format'] ?? 'PDF');
    $niveau      = trim($_POST['niveau'] ?? 'débutant');
    $content     = trim($_POST['content'] ?? '');
    $headline    = trim($_POST['headline'] ?? '');
    $fileUrl     = trim($_POST['file_url'] ?? '');
    $fileSize    = (int)($_POST['file_size'] ?? 0);
    $status      = trim($_POST['status'] ?? 'inactive');

    // Validation
    if (empty($title))       $errors[] = 'Le titre est requis';
    if (empty($slug))        $errors[] = 'Le slug est requis';
    if (empty($description)) $errors[] = 'La description est requise';
    if ($status !== 'active' && $status !== 'inactive') $status = 'inactive';

    // Vérifier l'unicité du slug
    if (!empty($slug)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM guides WHERE slug = ?");
            $stmt->execute([$slug]);
            if ((int)$stmt->fetchColumn() > 0) {
                $errors[] = 'Ce slug est déjà utilisé';
            }
        } catch (PDOException $e) {}
    }

    // Créer si pas d'erreurs
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO guides 
                (title, slug, description, type, format, niveau, content, headline,
                 file_url, file_size, status, rating, downloads, vues, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 0, NOW(), NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $title, $slug, $description, $type, $format, $niveau, $content, $headline,
                $fileUrl, $fileSize, $status
            ]);

            $newId = $pdo->lastInsertId();
            writeLog('guides', "Guide créé: {$title}", 'create', ['guide_id' => $newId]);
            
            header('Location: ?page=guides&action=edit&id=' . $newId . '&msg=created');
            exit;
        } catch (PDOException $e) {
            error_log("[Guides Create Save] " . $e->getMessage());
            $errors[] = 'Erreur lors de la création';
        }
    }
}

// ─── Préparer les données pour le formulaire ───
$title       = $isPosted ? $title       : '';
$slug        = $isPosted ? $slug        : '';
$description = $isPosted ? $description : '';
$type        = $isPosted ? $type        : 'ebook';
$format      = $isPosted ? $format      : 'PDF';
$niveau      = $isPosted ? $niveau      : 'débutant';
$content     = $isPosted ? $content     : '';
$headline    = $isPosted ? $headline    : '';
$fileUrl     = $isPosted ? $fileUrl     : '';
$fileSize    = $isPosted ? $fileSize    : 0;
$status      = $isPosted ? $status      : 'inactive';

// ─── CSRF ───
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<style>
/* ══ GUIDES CREATE ══════════════════════════════════════════════ */
.gui-create-wrap { font-family:var(--font,'Inter',sans-serif); max-width:920px; margin:0 auto; }

.gui-create-head { display:flex; align-items:center; gap:14px; margin-bottom:28px; }
.gui-create-back { width:36px; height:36px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:var(--text-3,#9ca3af); text-decoration:none; transition:all .15s; border:1px solid var(--border,#e5e7eb); }
.gui-create-back:hover { color:#7c3aed; border-color:var(--border,#e5e7eb); background:rgba(124,58,237,.07); }
.gui-create-title { font-size:1.65rem; font-weight:700; color:var(--text,#111827); margin:0; }
.gui-create-badge { display:inline-flex; align-items:center; gap:6px; font-size:.72rem; font-weight:700; padding:4px 11px; border-radius:10px; background:rgba(124,58,237,.1); color:#7c3aed; }

.gui-create-alerts { margin-bottom:20px; }
.gui-create-alert { padding:12px 16px; border-radius:10px; font-size:.85rem; font-weight:600; margin-bottom:8px; display:flex; align-items:center; gap:8px; }
.gui-create-alert.error { background:#fef2f2; color:#dc2626; border:1px solid rgba(220,38,38,.12); }
.gui-create-alert.info { background:#eff6ff; color:#0369a1; border:1px solid rgba(3,105,161,.12); }
.gui-create-alert i { font-size:.9rem; }

.gui-create-form { background:var(--surface,#fff); border:1px solid var(--border,#e5e7eb); border-radius:14px; padding:26px; }

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
.gui-form-row.full { grid-template-columns:1fr; }
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

.gui-template-selector { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; }
.gui-template-card { padding:12px; border:2px solid var(--border,#e5e7eb); border-radius:10px; cursor:pointer; transition:all .15s; text-align:center; }
.gui-template-card:hover { border-color:#7c3aed; background:rgba(124,58,237,.03); }
.gui-template-card.selected { border-color:#7c3aed; background:rgba(124,58,237,.08); }
.gui-template-icon { font-size:1.8rem; margin-bottom:6px; display:block; }
.gui-template-label { font-size:.78rem; font-weight:700; color:var(--text,#111827); }
.gui-template-desc { font-size:.65rem; color:var(--text-3,#9ca3af); margin-top:3px; }

.gui-quick-tips { background:rgba(124,58,237,.05); border-left:3px solid #7c3aed; padding:12px 14px; border-radius:6px; margin-bottom:16px; }
.gui-quick-tips-title { font-size:.72rem; font-weight:700; color:#7c3aed; text-transform:uppercase; letter-spacing:.05em; margin-bottom:6px; }
.gui-quick-tips-list { font-size:.78rem; color:var(--text-2,#6b7280); line-height:1.6; }
.gui-quick-tips-list li { margin-bottom:4px; }
</style>

<div class="gui-create-wrap">

<!-- Header -->
<div class="gui-create-head">
    <a href="?page=guides" class="gui-create-back" title="Retour"><i class="fas fa-arrow-left"></i></a>
    <div style="flex:1">
        <h1 class="gui-create-title">Nouveau guide</h1>
        <span class="gui-create-badge">
            <i class="fas fa-plus"></i>
            Création
        </span>
    </div>
</div>

<!-- Info -->
<div class="gui-create-alerts">
    <div class="gui-create-alert info">
        <i class="fas fa-info-circle"></i>
        Créez un nouveau guide (eBook, checklist, template...) pour votre catalogue de ressources
    </div>
</div>

<!-- Alerts d'erreur -->
<?php if (!empty($errors)): ?>
<div class="gui-create-alerts">
    <?php foreach ($errors as $err): ?>
    <div class="gui-create-alert error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Form -->
<form method="POST" class="gui-create-form" id="guideForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <!-- Quick Tips -->
    <div class="gui-quick-tips">
        <div class="gui-quick-tips-title">💡 Conseil rapide</div>
        <ul class="gui-quick-tips-list">
            <li><strong>Titre :</strong> Clair et bénéfice-orienté (ex: "Guide complet de l'achat immobilier")</li>
            <li><strong>Slug :</strong> URL-friendly, sans accents (ex: guide-achat-immobilier)</li>
            <li><strong>Type :</strong> Choisissez le format de votre ressource</li>
            <li><strong>Description :</strong> Résumé court pour les listings et moteurs de recherche</li>
        </ul>
    </div>

    <!-- ──── SECTION: Infos principales ──── -->
    <div class="gui-form-section">
        <div class="gui-form-section-title"><i class="fas fa-file-lines"></i> Infos principales</div>

        <!-- Titre -->
        <div class="gui-form-group">
            <label class="gui-form-label">
                Titre <span class="req">*</span>
                <span class="hint">Titre principal du guide</span>
            </label>
            <input type="text" name="title" class="gui-form-input" value="<?= htmlspecialchars($title) ?>" maxlength="200" onchange="updateSlug()" required autofocus>
        </div>

        <!-- Slug -->
        <div class="gui-form-group">
            <label class="gui-form-label">
                Slug <span class="req">*</span>
                <span class="hint">URL-friendly identifier</span>
            </label>
            <input type="text" name="slug" class="gui-form-input" id="guideSlug" value="<?= htmlspecialchars($slug) ?>" required>
            <div class="gui-slug-preview">/guide/<?= htmlspecialchars($slug ?: 'mon-guide') ?></div>
        </div>

        <!-- Description -->
        <div class="gui-form-group">
            <label class="gui-form-label">
                Description <span class="req">*</span>
                <span class="hint">Résumé court du guide (moteurs de recherche)</span>
            </label>
            <textarea name="description" class="gui-form-textarea" maxlength="500" required><?= htmlspecialchars($description) ?></textarea>
            <div class="gui-char-count"><span id="descCount">0</span>/500</div>
        </div>

        <!-- Headline (optionnel) -->
        <div class="gui-form-group">
            <label class="gui-form-label">
                Headline (optionnel)
                <span class="hint">Titre alternatif pour les réseaux sociaux</span>
            </label>
            <input type="text" name="headline" class="gui-form-input" value="<?= htmlspecialchars($headline) ?>" maxlength="120">
        </div>

        <!-- Content (optionnel) -->
        <div class="gui-form-group">
            <label class="gui-form-label">
                Contenu (optionnel)
                <span class="hint">Contenu long-form du guide</span>
            </label>
            <textarea name="content" class="gui-form-textarea" style="min-height:200px"><?= htmlspecialchars($content) ?></textarea>
        </div>
    </div>

    <!-- ──── SECTION: Type & Format ──── -->
    <div class="gui-form-section">
        <div class="gui-form-section-title"><i class="fas fa-cube"></i> Type & Format</div>

        <div class="gui-form-row">
            <div class="gui-form-group">
                <label class="gui-form-label">Type <span class="req">*</span></label>
                <select name="type" class="gui-form-select" required>
                    <option value="ebook" <?= $type==='ebook'?'selected':'' ?>>📚 eBook / Guide PDF</option>
                    <option value="checklist" <?= $type==='checklist'?'selected':'' ?>>✅ Checklist</option>
                    <option value="template" <?= $type==='template'?'selected':'' ?>>📋 Template / Modèle</option>
                    <option value="webinaire" <?= $type==='webinaire'?'selected':'' ?>>🎥 Webinaire / Vidéo</option>
                    <option value="tool" <?= $type==='tool'?'selected':'' ?>>🛠️ Outil / Calculatrice</option>
                    <option value="guide" <?= $type==='guide'?'selected':'' ?>>📖 Guide / Tutoriel</option>
                    <option value="other" <?= $type==='other'?'selected':'' ?>>📦 Autre</option>
                </select>
            </div>

            <div class="gui-form-group">
                <label class="gui-form-label">Format <span class="req">*</span></label>
                <select name="format" class="gui-form-select" required>
                    <option value="PDF" <?= $format==='PDF'?'selected':'' ?>>📄 PDF</option>
                    <option value="Google Sheets" <?= $format==='Google Sheets'?'selected':'' ?>>📊 Google Sheets</option>
                    <option value="Excel" <?= $format==='Excel'?'selected':'' ?>>📈 Excel</option>
                    <option value="HTML" <?= $format==='HTML'?'selected':'' ?>>🌐 HTML</option>
                    <option value="Video" <?= $format==='Video'?'selected':'' ?>>🎬 Vidéo</option>
                    <option value="Audio" <?= $format==='Audio'?'selected':'' ?>>🎙️ Audio</option>
                    <option value="Archive" <?= $format==='Archive'?'selected':'' ?>>📦 Archive (ZIP)</option>
                </select>
            </div>
        </div>

        <div class="gui-form-row">
            <div class="gui-form-group">
                <label class="gui-form-label">Niveau <span class="req">*</span></label>
                <select name="niveau" class="gui-form-select" required>
                    <option value="débutant" <?= $niveau==='débutant'?'selected':'' ?>>🌱 Débutant</option>
                    <option value="intermédiaire" <?= $niveau==='intermédiaire'?'selected':'' ?>>🌿 Intermédiaire</option>
                    <option value="avancé" <?= $niveau==='avancé'?'selected':'' ?>>🌳 Avancé</option>
                    <option value="expert" <?= $niveau==='expert'?'selected':'' ?>>⭐ Expert</option>
                </select>
            </div>

            <div class="gui-form-group">
                <label class="gui-form-label">Note initiale (optionnel)</label>
                <input type="number" name="rating" class="gui-form-input" value="0" min="0" max="5" step="0.5" placeholder="0 à 5 étoiles">
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
            <input type="url" name="file_url" class="gui-form-input" value="<?= htmlspecialchars($fileUrl) ?>" placeholder="https://example.com/file.pdf">
        </div>

        <div class="gui-form-group">
            <label class="gui-form-label">
                Taille du fichier (octets)
                <span class="hint">Taille en octets (ex: 1048576 = 1 MB)</span>
            </label>
            <input type="number" name="file_size" class="gui-form-input" value="0" min="0" placeholder="0">
        </div>
    </div>

    <!-- ──── SECTION: Statut ──── -->
    <div class="gui-form-section">
        <div class="gui-form-section-title"><i class="fas fa-toggle-on"></i> Statut de publication</div>

        <div style="display:flex; gap:20px">
            <label style="display:flex; align-items:center; gap:10px; font-size:.85rem; cursor:pointer">
                <input type="radio" name="status" value="inactive" <?= $status==='inactive'?'checked':'' ?> style="cursor:pointer; width:16px; height:16px" checked>
                <span style="color:var(--text,#111827); font-weight:600">📝 Brouillon (inactif)</span>
            </label>
            <label style="display:flex; align-items:center; gap:10px; font-size:.85rem; cursor:pointer">
                <input type="radio" name="status" value="active" <?= $status==='active'?'checked':'' ?> style="cursor:pointer; width:16px; height:16px">
                <span style="color:var(--text,#111827); font-weight:600">✅ Publié (actif)</span>
            </label>
        </div>
    </div>

    <!-- Actions -->
    <div class="gui-form-footer">
        <div class="gui-form-actions">
            <a href="?page=guides" class="gui-btn gui-btn-outline"><i class="fas fa-times"></i> Annuler</a>
            <button type="submit" class="gui-btn gui-btn-primary"><i class="fas fa-plus"></i> Créer le guide</button>
        </div>
    </div>
</form>

</div><!-- /gui-create-wrap -->

<script>
// Actualiser le slug depuis le titre
function updateSlug() {
    const title = document.querySelector('input[name="title"]').value;
    if (!title) return;
    
    let slug = title
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Supprimer les accents
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
    
    // Ne pas écraser si déjà modifié par l'utilisateur
    if (!document.querySelector('#guideSlug').dataset.custom) {
        document.querySelector('#guideSlug').value = slug;
        updateSlugPreview();
    }
}

// Mettre à jour preview du slug
function updateSlugPreview() {
    const slug = document.querySelector('#guideSlug').value;
    document.querySelector('.gui-slug-preview').textContent = '/guide/' + (slug || 'mon-guide');
}

// Marquer le slug comme modifié manuellement
document.querySelector('#guideSlug').addEventListener('input', function() {
    this.dataset.custom = 'true';
    updateSlugPreview();
});

// Compteur de caractères
const descTA = document.querySelector('textarea[name="description"]');
descTA.addEventListener('input', function() {
    document.getElementById('descCount').textContent = this.value.length;
});

// Initialiser
document.getElementById('descCount').textContent = descTA.value.length;
</script>