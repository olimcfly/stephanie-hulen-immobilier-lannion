<?php
/**
 * /admin/modules/system/templates/edit.php
 * 
 * Éditeur visuel avancé pour templates avec preview live
 * - Panel gauche : choix template + champs éditables
 * - Panel centre : onglets JSON/PHP/CSS
 * - Panel droit : preview live en temps réel
 * - Sauvegarde AJAX automatique
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/config.php';

// Vérifier authentification
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

$pdo = getDB();
$siteUrl = rtrim(SITE_URL, '/');
// Récupérer le template actif
$templateId = $_GET['template'] ?? 't1-accueil';

// Récupérer le contenu du template depuis design_templates
$stmt = $pdo->prepare("SELECT * FROM `design_templates` WHERE `slug` = ? LIMIT 1");
$stmt->execute([$templateId]);
$pageData = $stmt->fetch(PDO::FETCH_ASSOC);

// Si pas de données, créer un enregistrement vide
if (!$pageData) {
    $pdo->prepare("INSERT INTO `design_templates` (`type`, `slug`, `name`, `html`, `css`, `json_content`) VALUES ('page', ?, ?, '', '', ?)")
        ->execute([$templateId, ucfirst(str_replace('-', ' ', $templateId)), json_encode([])]);
    $stmt = $pdo->prepare("SELECT * FROM `design_templates` WHERE `slug` = ? LIMIT 1");
    $stmt->execute([$templateId]);
    $pageData = $stmt->fetch(PDO::FETCH_ASSOC);
}

$fields = json_decode($pageData['json_content'] ?? '{}', true) ?? [];

// Charger les fichiers PHP et CSS existants
$phpCode = '';
$cssCode = '';
$baseDir = dirname(__DIR__, 4); // /public_html

if (!empty($pageData['php_file'])) {
    $phpPath = $baseDir . '/' . $pageData['php_file'];
    if (file_exists($phpPath)) {
        $phpCode = file_get_contents($phpPath);
    }
}

if (!empty($pageData['css_file'])) {
    $cssPath = $baseDir . '/' . $pageData['css_file'];
    if (file_exists($cssPath)) {
        $cssCode = file_get_contents($cssPath);
    }
}

// Définir les templates disponibles et leurs champs
$templatesConfig = [
    't1-accueil' => [
        'name' => 'Accueil',
        'icon' => '🏠',
        'fields' => [
            'hero_eyebrow' => ['label' => 'Hero Eyebrow', 'type' => 'text'],
            'hero_title' => ['label' => 'Hero Title', 'type' => 'textarea'],
            'hero_subtitle' => ['label' => 'Hero Subtitle', 'type' => 'textarea'],
            'hero_cta_text' => ['label' => 'Hero CTA Text', 'type' => 'text'],
            'hero_cta_url' => ['label' => 'Hero CTA URL', 'type' => 'text'],
            'hero_stat1_num' => ['label' => 'Stat 1 Number', 'type' => 'text'],
            'hero_stat1_lbl' => ['label' => 'Stat 1 Label', 'type' => 'text'],
            'hero_stat2_num' => ['label' => 'Stat 2 Number', 'type' => 'text'],
            'hero_stat2_lbl' => ['label' => 'Stat 2 Label', 'type' => 'text'],
            'ben_title' => ['label' => 'Benefits Title', 'type' => 'text'],
            'ben1_title' => ['label' => 'Benefit 1 Title', 'type' => 'text'],
            'ben1_text' => ['label' => 'Benefit 1 Text', 'type' => 'textarea'],
            'ben2_title' => ['label' => 'Benefit 2 Title', 'type' => 'text'],
            'ben2_text' => ['label' => 'Benefit 2 Text', 'type' => 'textarea'],
            'ben3_title' => ['label' => 'Benefit 3 Title', 'type' => 'text'],
            'ben3_text' => ['label' => 'Benefit 3 Text', 'type' => 'textarea'],
            'method_title' => ['label' => 'Method Title', 'type' => 'text'],
            'step1_title' => ['label' => 'Step 1 Title', 'type' => 'text'],
            'step1_text' => ['label' => 'Step 1 Text', 'type' => 'textarea'],
            'step2_title' => ['label' => 'Step 2 Title', 'type' => 'text'],
            'step2_text' => ['label' => 'Step 2 Text', 'type' => 'textarea'],
            'step3_title' => ['label' => 'Step 3 Title', 'type' => 'text'],
            'step3_text' => ['label' => 'Step 3 Text', 'type' => 'textarea'],
            'guide_title' => ['label' => 'Guide Title', 'type' => 'text'],
            'g1_title' => ['label' => 'Guide 1 Title', 'type' => 'text'],
            'g1_text' => ['label' => 'Guide 1 Text', 'type' => 'richtext'],
            'g2_title' => ['label' => 'Guide 2 Title', 'type' => 'text'],
            'g2_text' => ['label' => 'Guide 2 Text', 'type' => 'richtext'],
            'g3_title' => ['label' => 'Guide 3 Title', 'type' => 'text'],
            'g3_text' => ['label' => 'Guide 3 Text', 'type' => 'richtext'],
            'cta_title' => ['label' => 'CTA Title', 'type' => 'text'],
            'cta_text' => ['label' => 'CTA Text', 'type' => 'textarea'],
            'cta_btn_text' => ['label' => 'CTA Button Text', 'type' => 'text'],
        ]
    ],
    't2-vendre' => [
        'name' => 'Vendre',
        'icon' => '📤',
        'fields' => [
            'hero_eyebrow' => ['label' => 'Hero Eyebrow', 'type' => 'text'],
            'hero_title' => ['label' => 'Hero Title', 'type' => 'textarea'],
            'hero_subtitle' => ['label' => 'Hero Subtitle', 'type' => 'textarea'],
            'hero_cta_text' => ['label' => 'Hero CTA Text', 'type' => 'text'],
            'ben_title' => ['label' => 'Benefits Title', 'type' => 'text'],
            'ben1_title' => ['label' => 'Benefit 1 Title', 'type' => 'text'],
            'ben1_text' => ['label' => 'Benefit 1 Text', 'type' => 'textarea'],
            'ben2_title' => ['label' => 'Benefit 2 Title', 'type' => 'text'],
            'ben2_text' => ['label' => 'Benefit 2 Text', 'type' => 'textarea'],
            'ben3_title' => ['label' => 'Benefit 3 Title', 'type' => 'text'],
            'ben3_text' => ['label' => 'Benefit 3 Text', 'type' => 'textarea'],
            'method_title' => ['label' => 'Method Title', 'type' => 'text'],
            'step1_title' => ['label' => 'Step 1 Title', 'type' => 'text'],
            'step1_text' => ['label' => 'Step 1 Text', 'type' => 'textarea'],
            'step2_title' => ['label' => 'Step 2 Title', 'type' => 'text'],
            'step2_text' => ['label' => 'Step 2 Text', 'type' => 'textarea'],
            'step3_title' => ['label' => 'Step 3 Title', 'type' => 'text'],
            'step3_text' => ['label' => 'Step 3 Text', 'type' => 'textarea'],
            'guide_title' => ['label' => 'Guide Title', 'type' => 'text'],
            'g1_title' => ['label' => 'Guide 1 Title', 'type' => 'text'],
            'g1_text' => ['label' => 'Guide 1 Text', 'type' => 'richtext'],
            'g2_title' => ['label' => 'Guide 2 Title', 'type' => 'text'],
            'g2_text' => ['label' => 'Guide 2 Text', 'type' => 'richtext'],
            'g3_title' => ['label' => 'Guide 3 Title', 'type' => 'text'],
            'g3_text' => ['label' => 'Guide 3 Text', 'type' => 'richtext'],
            'cta_title' => ['label' => 'CTA Title', 'type' => 'text'],
            'cta_text' => ['label' => 'CTA Text', 'type' => 'textarea'],
            'cta_btn_text' => ['label' => 'CTA Button Text', 'type' => 'text'],
        ]
    ],
    't3-acheter' => [
        'name' => 'Acheter',
        'icon' => '🔑',
        'fields' => [
            'hero_eyebrow' => ['label' => 'Hero Eyebrow', 'type' => 'text'],
            'hero_title' => ['label' => 'Hero Title', 'type' => 'textarea'],
            'hero_subtitle' => ['label' => 'Hero Subtitle', 'type' => 'textarea'],
            'hero_cta_text' => ['label' => 'Hero CTA Text', 'type' => 'text'],
            'ben_title' => ['label' => 'Benefits Title', 'type' => 'text'],
            'ben1_title' => ['label' => 'Benefit 1 Title', 'type' => 'text'],
            'ben1_text' => ['label' => 'Benefit 1 Text', 'type' => 'textarea'],
            'ben2_title' => ['label' => 'Benefit 2 Title', 'type' => 'text'],
            'ben2_text' => ['label' => 'Benefit 2 Text', 'type' => 'textarea'],
            'ben3_title' => ['label' => 'Benefit 3 Title', 'type' => 'text'],
            'ben3_text' => ['label' => 'Benefit 3 Text', 'type' => 'textarea'],
            'method_title' => ['label' => 'Method Title', 'type' => 'text'],
            'step1_title' => ['label' => 'Step 1 Title', 'type' => 'text'],
            'step1_text' => ['label' => 'Step 1 Text', 'type' => 'textarea'],
            'step2_title' => ['label' => 'Step 2 Title', 'type' => 'text'],
            'step2_text' => ['label' => 'Step 2 Text', 'type' => 'textarea'],
            'step3_title' => ['label' => 'Step 3 Title', 'type' => 'text'],
            'step3_text' => ['label' => 'Step 3 Text', 'type' => 'textarea'],
            'guide_title' => ['label' => 'Guide Title', 'type' => 'text'],
            'g1_title' => ['label' => 'Guide 1 Title', 'type' => 'text'],
            'g1_text' => ['label' => 'Guide 1 Text', 'type' => 'richtext'],
            'g2_title' => ['label' => 'Guide 2 Title', 'type' => 'text'],
            'g2_text' => ['label' => 'Guide 2 Text', 'type' => 'richtext'],
            'g3_title' => ['label' => 'Guide 3 Title', 'type' => 'text'],
            'g3_text' => ['label' => 'Guide 3 Text', 'type' => 'richtext'],
            'cta_title' => ['label' => 'CTA Title', 'type' => 'text'],
            'cta_text' => ['label' => 'CTA Text', 'type' => 'textarea'],
            'cta_btn_text' => ['label' => 'CTA Button Text', 'type' => 'text'],
        ]
    ],
    't4-investir' => [
        'name' => 'Investir',
        'icon' => '📈',
        'fields' => [
            'hero_eyebrow' => ['label' => 'Hero Eyebrow', 'type' => 'text'],
            'hero_title' => ['label' => 'Hero Title', 'type' => 'textarea'],
            'hero_subtitle' => ['label' => 'Hero Subtitle', 'type' => 'textarea'],
            'hero_cta_text' => ['label' => 'Hero CTA Text', 'type' => 'text'],
            'ben_title' => ['label' => 'Benefits Title', 'type' => 'text'],
            'ben1_title' => ['label' => 'Benefit 1 Title', 'type' => 'text'],
            'ben1_text' => ['label' => 'Benefit 1 Text', 'type' => 'textarea'],
            'ben2_title' => ['label' => 'Benefit 2 Title', 'type' => 'text'],
            'ben2_text' => ['label' => 'Benefit 2 Text', 'type' => 'textarea'],
            'ben3_title' => ['label' => 'Benefit 3 Title', 'type' => 'text'],
            'ben3_text' => ['label' => 'Benefit 3 Text', 'type' => 'textarea'],
            'method_title' => ['label' => 'Method Title', 'type' => 'text'],
            'step1_title' => ['label' => 'Step 1 Title', 'type' => 'text'],
            'step1_text' => ['label' => 'Step 1 Text', 'type' => 'textarea'],
            'step2_title' => ['label' => 'Step 2 Title', 'type' => 'text'],
            'step2_text' => ['label' => 'Step 2 Text', 'type' => 'textarea'],
            'step3_title' => ['label' => 'Step 3 Title', 'type' => 'text'],
            'step3_text' => ['label' => 'Step 3 Text', 'type' => 'textarea'],
            'guide_title' => ['label' => 'Guide Title', 'type' => 'text'],
            'g1_title' => ['label' => 'Guide 1 Title', 'type' => 'text'],
            'g1_text' => ['label' => 'Guide 1 Text', 'type' => 'richtext'],
            'g2_title' => ['label' => 'Guide 2 Title', 'type' => 'text'],
            'g2_text' => ['label' => 'Guide 2 Text', 'type' => 'richtext'],
            'g3_title' => ['label' => 'Guide 3 Title', 'type' => 'text'],
            'g3_text' => ['label' => 'Guide 3 Text', 'type' => 'richtext'],
            'cta_title' => ['label' => 'CTA Title', 'type' => 'text'],
            'cta_text' => ['label' => 'CTA Text', 'type' => 'textarea'],
            'cta_btn_text' => ['label' => 'CTA Button Text', 'type' => 'text'],
        ]
    ]
];

$currentTemplate = $templatesConfig[$templateId] ?? $templatesConfig['t1-accueil'];
$currentFields = $currentTemplate['fields'];
?>

<style>
.templates-editor-container {
    display: grid;
    grid-template-columns: 350px 1fr 1fr;
    gap: 20px;
    min-height: calc(100vh - 100px);
}

.editor-sidebar {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow-y: auto;
    max-height: calc(100vh - 120px);
}

.template-selector {
    margin-bottom: 30px;
}

.template-selector h3 {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #999;
    margin-bottom: 12px;
}

.template-list {
    display: grid;
    grid-template-columns: 1fr;
    gap: 8px;
}

.template-btn {
    padding: 12px 16px;
    border: 2px solid #eee;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 13px;
    font-weight: 500;
    text-align: left;
}

.template-btn:hover {
    border-color: #1B3A4B;
    background: #f9f9f9;
}

.template-btn.active {
    border-color: #1B3A4B;
    background: #1B3A4B;
    color: #fff;
}

.template-btn-icon {
    margin-right: 8px;
}

.fields-editor h3 {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #999;
    margin-bottom: 16px;
    margin-top: 20px;
}

.field-group {
    margin-bottom: 14px;
}

.field-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.field-input,
.field-textarea,
.field-richtext {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-family: inherit;
    font-size: 12px;
    transition: border 0.3s;
}

.field-input:focus,
.field-textarea:focus,
.field-richtext:focus {
    outline: none;
    border-color: #1B3A4B;
    box-shadow: 0 0 0 3px rgba(27, 58, 75, 0.1);
}

.field-textarea {
    min-height: 70px;
    resize: vertical;
}

.field-richtext {
    min-height: 80px;
}

.editor-code {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
}

.editor-code h2 {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #999;
    margin-bottom: 12px;
}

.code-editor {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    font-family: 'Courier New', monospace;
    font-size: 11px;
    background: #f9f9f9;
    color: #333;
    line-height: 1.5;
    overflow-y: auto;
    resize: none;
}

.code-editor:focus {
    outline: none;
    border-color: #1B3A4B;
}

.code-stats {
    font-size: 10px;
    color: #999;
    margin-top: 8px;
}

.editor-preview {
    background: #fff;
    border-radius: 8px;
    padding: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.preview-header {
    padding: 16px 20px;
    border-bottom: 1px solid #eee;
    background: #f9f9f9;
}

.preview-header h2 {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    color: #999;
}

.preview-frame {
    flex: 1;
    overflow-y: auto;
    background: #f5f5f5;
}

.preview-iframe {
    width: 100%;
    height: 100%;
    border: none;
    background: #fff;
}

.save-status {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 12px 16px;
    background: #4CAF50;
    color: #fff;
    border-radius: 4px;
    font-size: 12px;
    opacity: 0;
    transition: opacity 0.3s;
    pointer-events: none;
    z-index: 1000;
}

.save-status.show {
    opacity: 1;
}

.save-status.error {
    background: #f44336;
}

.tab-btn {
    padding: 10px 16px;
    border: none;
    background: #f5f5f5;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s;
}

.tab-btn:hover {
    background: #eee;
}

.tab-btn.active {
    background: #fff;
    border-bottom-color: #1B3A4B;
    color: #1B3A4B;
}

@media (max-width: 1400px) {
    .templates-editor-container {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="templates-editor-container">
    
    <!-- PANEL GAUCHE -->
    <div class="editor-sidebar">
        <div class="template-selector">
            <h3>📄 Template</h3>
            <div class="template-list">
                <?php foreach ($templatesConfig as $tplId => $tplConfig): ?>
                    <button class="template-btn <?= $tplId === $templateId ? 'active' : '' ?>" 
                            onclick="switchTemplate('<?= $tplId ?>')">
                        <span class="template-btn-icon"><?= $tplConfig['icon'] ?></span>
                        <?= $tplConfig['name'] ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="fields-editor">
            <h3>✏️ Champs</h3>
            <?php foreach ($currentFields as $fieldKey => $fieldConfig): ?>
                <div class="field-group">
                    <label class="field-label"><?= htmlspecialchars($fieldConfig['label']) ?></label>
                    <?php if ($fieldConfig['type'] === 'text'): ?>
                        <input type="text" class="field-input" data-field="<?= $fieldKey ?>" 
                               value="<?= htmlspecialchars($fields[$fieldKey] ?? '') ?>"
                               placeholder="Texte">
                    <?php elseif ($fieldConfig['type'] === 'textarea'): ?>
                        <textarea class="field-textarea" data-field="<?= $fieldKey ?>" 
                                  placeholder="Texte"><?= htmlspecialchars($fields[$fieldKey] ?? '') ?></textarea>
                    <?php elseif ($fieldConfig['type'] === 'richtext'): ?>
                        <textarea class="field-richtext" data-field="<?= $fieldKey ?>" 
                                  placeholder="HTML"><?= htmlspecialchars($fields[$fieldKey] ?? '') ?></textarea>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- PANEL CENTRAL - Onglets Code -->
    <div style="display: flex; flex-direction: column;">
        <!-- Onglets -->
        <div style="display: flex; gap: 10px; border-bottom: 1px solid #eee; background: #fff; border-radius: 8px 8px 0 0; padding: 0 20px;">
            <button id="tabJson" class="tab-btn active" onclick="switchTab('json')">📝 JSON</button>
            <button id="tabPhp" class="tab-btn" onclick="switchTab('php')">🐘 PHP</button>
            <button id="tabCss" class="tab-btn" onclick="switchTab('css')">🎨 CSS</button>
        </div>

        <!-- Panel JSON (défaut) -->
        <div id="panelJson" class="editor-code" style="display: block;">
            <h2>📝 Données</h2>
            <textarea class="code-editor" id="codeEditor" readonly><?= json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></textarea>
            <div class="code-stats" id="codeStats"></div>
        </div>

        <!-- Panel PHP -->
        <div id="panelPhp" class="editor-code" style="display: none;">
            <h2>🐘 PHP</h2>
            <textarea class="code-editor" id="phpEditor" placeholder="<?php echo htmlspecialchars('<?php' . "\n" . '// Code PHP du template' . "\n" . '?>'); ?>"><?= htmlspecialchars($phpCode) ?></textarea>
        </div>

        <!-- Panel CSS -->
        <div id="panelCss" class="editor-code" style="display: none;">
            <h2>🎨 CSS</h2>
            <textarea class="code-editor" id="cssEditor" placeholder="/* Styles CSS */"><?= htmlspecialchars($cssCode) ?></textarea>
        </div>
    </div>
    
    <!-- PANEL DROIT - Preview -->
    <div class="editor-preview">
        <div class="preview-header">
            <h2>👁️ Aperçu live</h2>
        </div>
        <div class="preview-frame">
            <iframe class="preview-iframe" id="previewFrame"></iframe>
        </div>
    </div>
</div>

<div class="save-status" id="saveStatus"></div>

<script>
const templateId = '<?= $templateId ?>';
const siteUrl = '<?= $siteUrl ?>';

let saveTimeout;
let currentFields = <?= json_encode($fields) ?>;

// Écouter les changements des champs
document.querySelectorAll('[data-field]').forEach(input => {
    input.addEventListener('input', () => {
        updateField(input.dataset.field, input.value);
    });
});

function updateField(fieldKey, value) {
    currentFields[fieldKey] = value;
    updateCodeEditor();
    updatePreview();
    autoSave();
}

function updateCodeEditor() {
    document.getElementById('codeEditor').value = JSON.stringify(currentFields, null, 2);
    updateCodeStats();
}

function updateCodeStats() {
    const charCount = JSON.stringify(currentFields).length;
    const fieldCount = Object.keys(currentFields).length;
    document.getElementById('codeStats').textContent = `${fieldCount} champs • ${charCount} caractères`;
}

function updatePreview() {
    const previewFrame = document.getElementById('previewFrame');
    const formData = new FormData();
    formData.append('template', templateId);
    formData.append('fields', JSON.stringify(currentFields));
    
    fetch(`${siteUrl}/admin/api/templates/preview.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const htmlContent = `
                <!DOCTYPE html>
                <html lang="fr">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@400;600&display=swap" rel="stylesheet">
                    <style>
                        ${data.css || ''}
                    </style>
                </head>
                <body>
                    ${data.html || ''}
                </body>
                </html>
            `;
            
            previewFrame.contentDocument.open();
            previewFrame.contentDocument.write(htmlContent);
            previewFrame.contentDocument.close();
        }
    })
    .catch(err => console.error('Preview error:', err));
}

function autoSave() {
    clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
        saveFields();
    }, 1000);
}

function saveFields() {
    const formData = new FormData();
    formData.append('template_id', templateId);
    formData.append('json_content', JSON.stringify(currentFields));
    formData.append('php_code', document.getElementById('phpEditor')?.value || '');
    formData.append('css_code', document.getElementById('cssEditor')?.value || '');
    
    fetch(`${siteUrl}/admin/api/templates.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showStatus('✓ Sauvegardé', false);
        } else {
            showStatus('✗ Erreur', true);
        }
    })
    .catch(err => {
        console.error('Save error:', err);
        showStatus('✗ Erreur réseau', true);
    });
}

function showStatus(message, isError = false) {
    const status = document.getElementById('saveStatus');
    status.textContent = message;
    status.classList.add('show');
    if (isError) status.classList.add('error');
    else status.classList.remove('error');
    
    setTimeout(() => {
        status.classList.remove('show');
    }, 3000);
}

function switchTemplate(newTemplateId) {
    window.location.href = `?section=system&module=templates&action=edit&template=${newTemplateId}`;
}

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('[id^="panel"]').forEach(panel => panel.style.display = 'none');
    
    const tabKey = tab.charAt(0).toUpperCase() + tab.slice(1);
    document.getElementById('tab' + tabKey).classList.add('active');
    document.getElementById('panel' + tabKey).style.display = 'block';
}

// Initialiser
updateCodeStats();
updatePreview();
</script>