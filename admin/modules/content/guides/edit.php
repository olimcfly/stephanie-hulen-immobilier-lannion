<?php
/**
 * /admin/modules/content/guides/edit.php
 * Éditeur de guides — Même design que l'éditeur d'articles
 */

session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/init.php';

$pdo = Database::getInstance()->getConnection();
$guideId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$guide = null;
$isNew = $guideId === 0;

// Récupérer le guide existant
if (!$isNew) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM ressources WHERE id = ?");
        $stmt->execute([$guideId]);
        $guide = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$guide) {
            header('Location: ?page=guides');
            exit;
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        header('Location: ?page=guides');
        exit;
    }
}

// Traiter les soumissions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'save') {
        $name = $_POST['name'] ?? '';
        $slug = $_POST['slug'] ?? '';
        $description = $_POST['description'] ?? '';
        $extrait = $_POST['extrait'] ?? '';
        $persona = $_POST['persona'] ?? '';
        $format = $_POST['format'] ?? 'PDF';
        $pages = $_POST['pages'] ?? '0';
        $chapitres = isset($_POST['chapitres']) ? json_encode($_POST['chapitres']) : '[]';
        $status = $_POST['status'] ?? 'draft';

        // Validation
        if (empty($name) || empty($slug)) {
            echo json_encode(['success' => false, 'message' => 'Nom et slug requis']);
            exit;
        }

        try {
            if ($isNew) {
                $stmt = $pdo->prepare("
                    INSERT INTO ressources 
                    (name, slug, description, extrait, persona, format, pages, chapitres, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$name, $slug, $description, $extrait, $persona, $format, $pages, $chapitres, $status]);
                $guideId = $pdo->lastInsertId();
                echo json_encode(['success' => true, 'message' => 'Guide créé', 'id' => $guideId]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE ressources 
                    SET name = ?, slug = ?, description = ?, extrait = ?, persona = ?, format = ?, pages = ?, chapitres = ?, status = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$name, $slug, $description, $extrait, $persona, $format, $pages, $chapitres, $status, $guideId]);
                echo json_encode(['success' => true, 'message' => 'Guide mis à jour', 'id' => $guideId]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}

$name = $guide['name'] ?? '';
$slug = $guide['slug'] ?? '';
$description = $guide['description'] ?? '';
$extrait = $guide['extrait'] ?? '';
$persona = $guide['persona'] ?? 'vendeur';
$format = $guide['format'] ?? 'PDF';
$pages = $guide['pages'] ?? '0';
$chapitres = $guide['chapitres'] ? json_decode($guide['chapitres'], true) : [];
$status = $guide['status'] ?? 'draft';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $isNew ? 'Nouveau guide' : 'Éditer guide' ?> | Admin</title>
<link rel="stylesheet" href="/admin/assets/css/admin-components.css">
<style>
.editor-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:32px; }
.editor-h1 { font-size:1.8rem; font-weight:800; color:#1a1a2e; margin:0; }
.editor-actions { display:flex; gap:12px; }
.btn { padding:12px 24px; border:none; border-radius:8px; font-weight:600; cursor:pointer; transition:all .2s; }
.btn-primary { background:#1B3A4B; color:white; }
.btn-primary:hover { background:#122A37; }
.btn-secondary { background:#f5f5f5; color:#1a1a2e; border:1px solid #ddd; }
.btn-secondary:hover { background:#eff0f1; }

.editor-layout { display:grid; grid-template-columns:1fr 380px; gap:32px; }
.editor-main { }
.editor-sidebar { }

.section-block { background:white; border:1px solid #e2d9cc; border-radius:12px; padding:28px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,.04); }
.section-header { display:flex; align-items:center; gap:12px; margin-bottom:20px; padding-bottom:16px; border-bottom:2px solid #f0ede8; }
.section-icon { font-size:1.3rem; }
.section-title { font-size:1.1rem; font-weight:800; color:#1B3A4B; margin:0; }

.field-group { margin-bottom:24px; }
.field-group:last-child { margin-bottom:0; }
.field-label { display:block; font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#4a5568; margin-bottom:8px; }
.field-input, .field-textarea, .field-select { width:100%; padding:12px 14px; border:1px solid #e2d9cc; border-radius:8px; font-family:inherit; font-size:.9rem; color:#1a1a2e; }
.field-input:focus, .field-textarea:focus, .field-select:focus { outline:none; border-color:#C8A96E; box-shadow:0 0 0 3px rgba(200,169,110,.1); }
.field-textarea { resize:vertical; min-height:120px; font-family:'DM Sans', sans-serif; }

.field-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.field-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }

.sidebar-card { background:white; border:1px solid #e2d9cc; border-radius:12px; padding:20px; margin-bottom:20px; }
.sidebar-title { font-size:.9rem; font-weight:700; color:#1B3A4B; margin-bottom:16px; text-transform:uppercase; letter-spacing:.03em; }

.status-badge { display:inline-block; padding:6px 12px; border-radius:20px; font-size:.75rem; font-weight:700; text-transform:uppercase; }
.status-draft { background:#fef3c7; color:#92400e; }
.status-published { background:#d1fae5; color:#065f46; }

.remplissage { background:#f8f6f3; border-radius:12px; padding:24px; text-align:center; }
.remplissage-num { font-size:3rem; font-weight:900; color:#C8A96E; }
.remplissage-label { font-size:.75rem; color:#4a5568; text-transform:uppercase; letter-spacing:.05em; margin-top:8px; }

.chapitres-list { list-style:none; padding:0; margin:0; }
.chapitres-list li { display:flex; justify-content:space-between; align-items:center; padding:12px 0; border-bottom:1px solid #f0ede8; }
.chapitres-list li:last-child { border-bottom:none; }
.chapitres-add { width:100%; padding:10px; margin-top:12px; background:#f8f6f3; border:1px dashed #C8A96E; border-radius:8px; color:#1B3A4B; cursor:pointer; font-weight:600; transition:all .2s; }
.chapitres-add:hover { background:#f0ede8; }

@media(max-width:1200px) { .editor-layout { grid-template-columns:1fr; } }
</style>
</head>
<body>

<!-- HEADER -->
<div style="background:white; border-bottom:1px solid #e2d9cc; padding:20px 0; sticky top:0; z-index:100;">
    <div style="max-width:1400px; margin:0 auto; padding:0 24px;">
        <div class="editor-header">
            <h1 class="editor-h1">
                📖 <?= $isNew ? 'Nouveau guide' : 'Éditer guide' ?> #<?= $guideId ?: 'nouveau' ?>
            </h1>
            <div class="editor-actions">
                <a href="?page=guides" class="btn btn-secondary">← Retour</a>
                <button onclick="saveGuide()" class="btn btn-primary">✓ Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- MAIN LAYOUT -->
<div style="max-width:1400px; margin:0 auto; padding:24px; min-height:calc(100vh - 100px);">
    <div class="editor-layout">

        <!-- COLONNE PRINCIPALE -->
        <div class="editor-main">

            <!-- INFORMATIONS GÉNÉRALES -->
            <div class="section-block">
                <div class="section-header">
                    <span class="section-icon">📋</span>
                    <h2 class="section-title">Informations générales</h2>
                </div>

                <div class="field-group">
                    <label class="field-label">Nom du guide</label>
                    <input type="text" id="name" class="field-input" placeholder="Ex: Guide de l'acheteur immobilier" value="<?= htmlspecialchars($name) ?>">
                </div>

                <div class="field-group">
                    <label class="field-label">URL (slug)</label>
                    <input type="text" id="slug" class="field-input" placeholder="guide-acheteur-immobilier" value="<?= htmlspecialchars($slug) ?>">
                </div>

                <div class="field-row">
                    <div class="field-group">
                        <label class="field-label">Persona</label>
                        <select id="persona" class="field-select">
                            <option value="vendeur" <?= $persona === 'vendeur' ? 'selected' : '' ?>>🏷️ Vendeur</option>
                            <option value="acheteur" <?= $persona === 'acheteur' ? 'selected' : '' ?>>🛒 Acheteur</option>
                            <option value="proprietaire" <?= $persona === 'proprietaire' ? 'selected' : '' ?>>🏠 Propriétaire</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Format</label>
                        <select id="format" class="field-select">
                            <option value="PDF" <?= $format === 'PDF' ? 'selected' : '' ?>>PDF</option>
                            <option value="Document" <?= $format === 'Document' ? 'selected' : '' ?>>Document</option>
                            <option value="Infographie" <?= $format === 'Infographie' ? 'selected' : '' ?>>Infographie</option>
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label class="field-label">Nombre de pages</label>
                    <input type="number" id="pages" class="field-input" placeholder="20" value="<?= htmlspecialchars($pages) ?>">
                </div>
            </div>

            <!-- DESCRIPTION -->
            <div class="section-block">
                <div class="section-header">
                    <span class="section-icon">✍️</span>
                    <h2 class="section-title">Description</h2>
                </div>

                <div class="field-group">
                    <label class="field-label">Description courte (pour listing)</label>
                    <textarea id="description" class="field-textarea" placeholder="Décrivez le contenu du guide en 2-3 lignes...<?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="field-group">
                    <label class="field-label">Extrait (pour page détail)</label>
                    <textarea id="extrait" class="field-textarea" placeholder="Texte plus détaillé sur le guide..." style="min-height:150px;"><?= htmlspecialchars($extrait) ?></textarea>
                </div>
            </div>

            <!-- CHAPITRES -->
            <div class="section-block">
                <div class="section-header">
                    <span class="section-icon">📚</span>
                    <h2 class="section-title">Chapitres inclus</h2>
                </div>

                <ul class="chapitres-list" id="chapitresList">
                    <?php foreach ($chapitres as $index => $ch): ?>
                    <li>
                        <input type="text" class="field-input" value="<?= htmlspecialchars($ch) ?>" style="flex:1; margin-right:10px;" onchange="updateChapitres()">
                        <button onclick="removeChap(<?= $index ?>)" style="background:none; border:none; color:#ef4444; cursor:pointer; font-weight:600;">✕</button>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <button class="chapitres-add" onclick="addChap()">+ Ajouter un chapitre</button>
            </div>

        </div>

        <!-- SIDEBAR -->
        <div class="editor-sidebar">

            <!-- PUBLICATION -->
            <div class="sidebar-card">
                <div class="sidebar-title">Publication</div>
                <div style="margin-bottom:16px;">
                    <select id="status" class="field-select" style="margin-bottom:12px;">
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Publié</option>
                    </select>
                </div>
                <div id="statusBadge">
                    <span class="status-badge status-<?= $status ?>">
                        <?= $status === 'draft' ? '📝 Brouillon' : '✓ Publié' ?>
                    </span>
                </div>
            </div>

            <!-- REMPLISSAGE -->
            <div class="sidebar-card">
                <div class="sidebar-title">Remplissage</div>
                <div class="remplissage">
                    <div class="remplissage-num" id="fillPercent">0%</div>
                    <div class="remplissage-label">Champs complétés</div>
                </div>
            </div>

            <!-- MÉTADONNÉES -->
            <div class="sidebar-card">
                <div class="sidebar-title">Métadonnées</div>
                <table style="width:100%; font-size:.8rem; color:#4a5568;">
                    <tr>
                        <td style="padding:8px 0; border-bottom:1px solid #f0ede8;">Créé</td>
                        <td style="text-align:right; padding:8px 0; border-bottom:1px solid #f0ede8; font-weight:600;">
                            <?= $guide ? date('d/m/Y H:i', strtotime($guide['created_at'])) : 'Maintenant' ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0; border-bottom:1px solid #f0ede8;">Modifié</td>
                        <td style="text-align:right; padding:8px 0; border-bottom:1px solid #f0ede8; font-weight:600;">
                            <?= $guide ? date('d/m/Y H:i', strtotime($guide['updated_at'] ?? $guide['created_at'])) : '—' ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;">ID</td>
                        <td style="text-align:right; padding:8px 0; font-weight:600;"><?= $guideId ?: '—' ?></td>
                    </tr>
                </table>
            </div>

        </div>

    </div>
</div>

<script>
let guideId = <?= $guideId ?>;
let chapitres = <?= json_encode($chapitres) ?>;

function updateChapitres() {
    const inputs = document.querySelectorAll('#chapitresList input');
    chapitres = Array.from(inputs).map(i => i.value).filter(v => v.trim());
    updateFill();
}

function addChap() {
    const li = document.createElement('li');
    li.innerHTML = `<input type="text" class="field-input" placeholder="Chapitre..." style="flex:1; margin-right:10px;" onchange="updateChapitres()">
        <button onclick="removeChap(this)" style="background:none; border:none; color:#ef4444; cursor:pointer; font-weight:600;">✕</button>`;
    document.getElementById('chapitresList').appendChild(li);
}

function removeChap(el) {
    if (typeof el === 'number') {
        document.querySelectorAll('#chapitresList li')[el].remove();
    } else {
        el.closest('li').remove();
    }
    updateChapitres();
}

function updateFill() {
    const fields = [
        document.getElementById('name').value,
        document.getElementById('slug').value,
        document.getElementById('description').value,
        document.getElementById('extrait').value,
        document.getElementById('persona').value,
    ];
    const filled = fields.filter(f => f && f.trim()).length;
    const percent = Math.round((filled / fields.length) * 100);
    document.getElementById('fillPercent').textContent = percent + '%';
}

function saveGuide() {
    updateChapitres();
    const data = new FormData();
    data.append('action', 'save');
    data.append('name', document.getElementById('name').value);
    data.append('slug', document.getElementById('slug').value);
    data.append('description', document.getElementById('description').value);
    data.append('extrait', document.getElementById('extrait').value);
    data.append('persona', document.getElementById('persona').value);
    data.append('format', document.getElementById('format').value);
    data.append('pages', document.getElementById('pages').value);
    data.append('status', document.getElementById('status').value);
    chapitres.forEach(ch => data.append('chapitres[]', ch));

    fetch('', { method: 'POST', body: data })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert(d.message);
                if (guideId === 0) window.location.href = '?page=guides&edit=' + d.id;
            } else {
                alert('Erreur: ' + d.message);
            }
        });
}

// MAJ au chargement
updateFill();
document.getElementById('status').addEventListener('change', function() {
    const badge = document.getElementById('statusBadge');
    badge.innerHTML = this.value === 'draft' 
        ? '<span class="status-badge status-draft">📝 Brouillon</span>'
        : '<span class="status-badge status-published">✓ Publié</span>';
});
</script>

</body>
</html>