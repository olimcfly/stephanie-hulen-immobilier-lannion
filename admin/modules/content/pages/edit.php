<?php
/**
 * /admin/modules/content/pages/edit.php
 * Page Builder — Éditeur par sections dynamique via $TPL
 * Preview responsive temps réel (Desktop / Tablette / Mobile)
 */

if (!defined('ADMIN_ROUTER')) {
    define('ADMIN_ROUTER', true);
}
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/includes/init.php';

try {
    $pdo = getDB();
} catch (Exception $e) {
    die('Erreur BD');
}

// ── Charger $TPL ────────────────────────────────────────────
require_once __DIR__ . '/tpl.php'; // Fichier contenant le tableau $TPL complet

// ── Charger la page si édition ──────────────────────────────
$pageId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$page = null;
$pageTitle = '';
$pageSlug = '';
$pageTemplate = 't1-accueil';
$pageStatus = 'draft';
$metaTitle = '';
$metaDesc = '';
$sectionsData = [];

if ($pageId) {
    $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([$pageId]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($page) {
        $pageTitle    = $page['title'] ?? '';
        $pageSlug     = $page['slug'] ?? '';
        $pageTemplate = $page['template'] ?? 't1-accueil';
        $pageStatus   = $page['status'] ?? 'draft';
        $metaTitle    = $page['meta_title'] ?? '';
        $metaDesc     = $page['meta_description'] ?? '';
        if (!empty($page['sections_json'])) {
            $sectionsData = json_decode($page['sections_json'], true) ?: [];
        }
    }
}

// ── Résoudre le template actif ──────────────────────────────
$activeTpl = $pageTemplate;
if (!isset($TPL[$activeTpl])) {
    $activeTpl = 'standard';
}

// ── Préparer les sections pour JS ───────────────────────────
$tplJson = json_encode($TPL, JSON_UNESCAPED_UNICODE);
$dataJson = json_encode($sectionsData, JSON_UNESCAPED_UNICODE);

// ── Liste des templates pour le sélecteur ───────────────────
$tplList = [
    't1-accueil'          => '🏠 Accueil',
    't2-edito'            => '📝 Edito (Vendre/Acheter)',
    't3-secteur'          => '📍 Secteur',
    't4-blog-hub'         => '📰 Blog Hub',
    't5-article'          => '✍️ Article',
    't6-guide'            => '📖 Guide Local',
    't7-estimation'       => '📊 Estimation',
    't8-contact'          => '📧 Contact',
    't9-honoraires'       => '💰 Honoraires',
    't10-biens-listing'   => '🏘️ Biens Listing',
    't11-bien-single'     => '🏡 Bien Single',
    't12-legal'           => '⚖️ Legal',
    't13-merci'           => '✅ Merci',
    't14-apropos'         => '👤 À Propos',
    't15-secteurs-listing'=> '🗺️ Secteurs Listing',
    't16-rapport-marche'  => '📈 Rapport Marché',
    'standard'            => '📄 Standard',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageId ? 'Éditer' : 'Créer' ?> — Page Builder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap');

        /* ════════════════════════════════════════════════
           RESET & VARS
           ════════════════════════════════════════════════ */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bg-deep:    #08090f;
            --bg-base:    #0d0f1a;
            --bg-raised:  #131627;
            --bg-surface: #181c32;
            --bg-hover:   #1e2340;
            --border:     #1e2240;
            --border-lit: #2a2f52;
            --text:       #c8cce0;
            --text-dim:   #6b71a0;
            --text-bright:#eef0fa;
            --accent:     #6366f1;
            --accent-dim: rgba(99,102,241,0.12);
            --accent-glow:rgba(99,102,241,0.25);
            --green:      #22c55e;
            --red:        #ef4444;
            --yellow:     #f59e0b;
            --radius:     10px;
            --radius-lg:  14px;
            --font:       'Outfit', system-ui, sans-serif;
            --mono:       'JetBrains Mono', monospace;
            --transition: .2s cubic-bezier(.4,0,.2,1);
        }
        html, body { height: 100%; overflow: hidden; }
        body { font-family: var(--font); background: var(--bg-deep); color: var(--text); font-size: 14px; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 5px; }

        /* ════════════════════════════════════════════════
           APP LAYOUT
           ════════════════════════════════════════════════ */
        .app { display: flex; flex-direction: column; height: 100vh; }

        /* ═══ TOPBAR ═══ */
        .topbar {
            height: 52px;
            background: var(--bg-raised);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 16px;
            gap: 12px;
            flex-shrink: 0;
            z-index: 200;
        }
        .topbar-brand {
            display: flex; align-items: center; gap: 10px;
            padding-right: 16px;
            border-right: 1px solid var(--border);
        }
        .topbar-brand .icon {
            width: 30px; height: 30px;
            background: linear-gradient(135deg, #6366f1, #a78bfa);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 800; color: #fff;
        }
        .topbar-brand span { font-size: 13px; font-weight: 700; color: var(--text-bright); }

        .topbar-meta {
            display: flex; align-items: center; gap: 8px; flex: 1;
        }
        .topbar-meta .page-name {
            font-size: 13px; font-weight: 600; color: var(--text-bright);
            max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .topbar-meta .tpl-badge {
            font-size: 10px; font-weight: 700;
            padding: 3px 10px; border-radius: 20px;
            background: var(--accent-dim); color: #818cf8;
            text-transform: uppercase; letter-spacing: .5px;
        }

        .topbar-devices {
            display: flex; gap: 2px;
            background: var(--bg-base); border-radius: 8px; padding: 3px;
        }
        .dev-btn {
            width: 34px; height: 30px; border: none; border-radius: 6px;
            background: transparent; color: var(--text-dim);
            cursor: pointer; font-size: 13px;
            display: flex; align-items: center; justify-content: center;
            transition: all var(--transition);
        }
        .dev-btn:hover { color: var(--text); background: var(--bg-hover); }
        .dev-btn.active { color: #fff; background: var(--accent); }

        .topbar-actions { display: flex; gap: 6px; margin-left: auto; }
        .btn {
            padding: 7px 16px; border-radius: 8px; border: none;
            font-family: var(--font); font-size: 12px; font-weight: 700;
            cursor: pointer; display: flex; align-items: center; gap: 6px;
            transition: all var(--transition);
        }
        .btn-ghost { background: var(--bg-surface); color: var(--text-dim); border: 1px solid var(--border); }
        .btn-ghost:hover { background: var(--bg-hover); color: var(--text); }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-primary:hover { background: #5558e3; transform: translateY(-1px); box-shadow: 0 4px 16px var(--accent-glow); }

        /* ═══ WORKSPACE ═══ */
        .workspace { flex: 1; display: flex; overflow: hidden; }

        /* ═══ SIDEBAR ═══ */
        .sidebar {
            width: 300px; flex-shrink: 0;
            background: var(--bg-base);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            overflow: hidden;
        }
        .sidebar-top {
            padding: 14px 14px 10px;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-top label {
            display: block; font-size: 10px; font-weight: 700;
            color: var(--text-dim); text-transform: uppercase;
            letter-spacing: .8px; margin-bottom: 6px;
        }
        .sidebar-top select {
            width: 100%; padding: 8px 10px;
            background: var(--bg-surface); border: 1px solid var(--border);
            border-radius: 8px; color: var(--text-bright);
            font-family: var(--font); font-size: 13px;
            cursor: pointer; outline: none;
        }
        .sidebar-top select:focus { border-color: var(--accent); }

        .section-list { flex: 1; overflow-y: auto; padding: 6px 0; }

        .sec-item {
            display: flex; align-items: center; gap: 10px;
            padding: 11px 14px;
            cursor: pointer;
            transition: all .12s;
            border-left: 3px solid transparent;
            position: relative;
        }
        .sec-item:hover { background: var(--bg-raised); }
        .sec-item.active {
            background: var(--bg-raised);
            border-left-color: var(--accent);
        }
        .sec-item .sec-icon {
            width: 30px; height: 30px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 12px; flex-shrink: 0;
        }
        .sec-item .sec-label { font-size: 12.5px; font-weight: 600; color: var(--text); }
        .sec-item .sec-count {
            margin-left: auto; font-size: 10px; font-weight: 600;
            color: var(--text-dim); background: var(--bg-surface);
            padding: 2px 7px; border-radius: 10px;
        }

        /* ═══ EDIT PANEL ═══ */
        .edit-panel {
            width: 380px; flex-shrink: 0;
            background: var(--bg-base);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            overflow: hidden;
            transition: width .25s ease;
        }
        .edit-panel.collapsed { width: 0; border: none; overflow: hidden; }

        .edit-header {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }
        .edit-header h2 {
            font-size: 13px; font-weight: 700; color: var(--text-bright);
            display: flex; align-items: center; gap: 8px;
        }
        .edit-header .close-edit {
            width: 26px; height: 26px; border-radius: 6px; border: none;
            background: var(--bg-surface); color: var(--text-dim);
            cursor: pointer; font-size: 11px;
            display: flex; align-items: center; justify-content: center;
            transition: all var(--transition);
        }
        .edit-header .close-edit:hover { background: var(--bg-hover); color: var(--text); }

        .edit-body { flex: 1; overflow-y: auto; padding: 16px; }

        .fg { margin-bottom: 18px; }
        .fg label {
            display: block; font-size: 10.5px; font-weight: 700;
            color: var(--text-dim); text-transform: uppercase;
            letter-spacing: .6px; margin-bottom: 6px;
        }
        .fg input, .fg textarea, .fg select {
            width: 100%;
            background: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 9px 12px;
            color: var(--text-bright);
            font-family: var(--font);
            font-size: 13px;
            line-height: 1.5;
            transition: border-color var(--transition);
            resize: vertical;
        }
        .fg input:focus, .fg textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px var(--accent-dim);
        }
        .fg textarea { min-height: 72px; }
        .fg textarea.rich-area { min-height: 140px; font-size: 12.5px; line-height: 1.6; }
        .fg .hint {
            font-size: 10.5px; color: #3d4170; margin-top: 4px;
            font-style: italic;
        }

        .field-sep {
            height: 1px; background: var(--border); margin: 22px 0;
        }

        /* ── Page Meta (collapsible) ── */
        .meta-toggle {
            padding: 10px 14px;
            background: var(--bg-raised);
            border-bottom: 1px solid var(--border);
            cursor: pointer;
            display: flex; align-items: center; justify-content: space-between;
            font-size: 11px; font-weight: 700; color: var(--text-dim);
            text-transform: uppercase; letter-spacing: .6px;
        }
        .meta-toggle i { transition: transform .2s; }
        .meta-toggle.open i { transform: rotate(180deg); }
        .meta-body {
            max-height: 0; overflow: hidden; transition: max-height .3s ease;
            background: var(--bg-raised);
        }
        .meta-body.open { max-height: 500px; }
        .meta-body-inner { padding: 14px; }

        /* ═══ PREVIEW ═══ */
        .preview-area {
            flex: 1;
            background: var(--bg-deep);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 20px;
            overflow: auto;
        }

        .preview-shell {
            background: var(--bg-surface);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 80px rgba(0,0,0,0.5), 0 0 0 1px var(--border);
            transition: width .4s cubic-bezier(.4,0,.2,1);
        }
        .preview-shell--desktop { width: 100%; max-width: 1200px; }
        .preview-shell--tablet  { width: 768px; }
        .preview-shell--mobile  { width: 375px; }

        .browser-chrome {
            display: flex; align-items: center; gap: 8px;
            padding: 10px 14px;
            background: var(--bg-raised);
            border-bottom: 1px solid var(--border);
        }
        .bc-dot { width: 10px; height: 10px; border-radius: 50%; }
        .bc-r { background: #ef4444; }
        .bc-y { background: #f59e0b; }
        .bc-g { background: #22c55e; }
        .bc-url {
            flex: 1; margin-left: 8px;
            background: var(--bg-base); border-radius: 6px;
            padding: 5px 12px; font-size: 11px;
            color: var(--text-dim); font-family: var(--mono);
        }

        .preview-shell iframe {
            width: 100%; border: none; background: #fff; display: block;
        }
        .preview-shell--desktop iframe { height: calc(100vh - 140px); }
        .preview-shell--tablet  iframe { height: 640px; }
        .preview-shell--mobile  iframe { height: 680px; }

        /* ═══ TOAST ═══ */
        .toast-box { position: fixed; top: 14px; right: 14px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
        .toast {
            padding: 12px 20px; border-radius: var(--radius);
            font-size: 13px; font-weight: 600; color: #fff;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
            display: flex; align-items: center; gap: 8px;
            animation: tIn .3s ease-out, tOut .3s 2.6s forwards;
        }
        .toast-ok  { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .toast-err { background: linear-gradient(135deg, #ef4444, #dc2626); }
        @keyframes tIn  { from { opacity:0; transform:translateX(30px); } to { opacity:1; transform:translateX(0); } }
        @keyframes tOut { to { opacity:0; transform:translateX(30px); } }

        /* ═══ EMPTY STATE ═══ */
        .empty-edit {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            height: 100%; color: var(--text-dim); text-align: center;
            padding: 40px;
        }
        .empty-edit i { font-size: 32px; margin-bottom: 12px; opacity: .3; }
        .empty-edit p { font-size: 13px; line-height: 1.6; }
    </style>
</head>
<body>

<div class="toast-box" id="toastBox"></div>

<div class="app">

    <!-- ════════════════ TOPBAR ════════════════ -->
    <div class="topbar">
        <div class="topbar-brand">
            <div class="icon">P</div>
            <span>Builder</span>
        </div>

        <div class="topbar-meta">
            <span class="page-name" id="topPageName"><?= htmlspecialchars($pageTitle ?: 'Nouvelle page') ?></span>
            <span class="tpl-badge" id="topTplBadge"><?= htmlspecialchars($tplList[$activeTpl] ?? $activeTpl) ?></span>
        </div>

        <div class="topbar-devices">
            <button class="dev-btn active" data-device="desktop" title="Desktop"><i class="fas fa-desktop"></i></button>
            <button class="dev-btn" data-device="tablet" title="Tablette"><i class="fas fa-tablet-alt"></i></button>
            <button class="dev-btn" data-device="mobile" title="Mobile"><i class="fas fa-mobile-alt"></i></button>
        </div>

        <div class="topbar-actions">
            <button class="btn btn-ghost" onclick="history.back()"><i class="fas fa-arrow-left"></i> Retour</button>
            <button class="btn btn-ghost" id="btnPreview" onclick="window.open('/<?= htmlspecialchars($pageSlug) ?>', '_blank')">
                <i class="fas fa-external-link-alt"></i> Voir
            </button>
            <button class="btn btn-primary" onclick="savePage()"><i class="fas fa-save"></i> Sauvegarder</button>
        </div>
    </div>

    <!-- ════════════════ WORKSPACE ════════════════ -->
    <div class="workspace">

        <!-- ═══ SIDEBAR : sections list ═══ -->
        <div class="sidebar">
            <div class="sidebar-top">
                <label>Template</label>
                <select id="tplSelect" onchange="changeTemplate(this.value)">
                    <?php foreach ($tplList as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $key === $activeTpl ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Meta page (collapsible) -->
            <div class="meta-toggle" onclick="toggleMeta()">
                <span><i class="fas fa-cog"></i> Paramètres page</span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="meta-body" id="metaBody">
                <div class="meta-body-inner">
                    <div class="fg">
                        <label>Titre de la page</label>
                        <input type="text" id="metaPageTitle" value="<?= htmlspecialchars($pageTitle) ?>" placeholder="Mon titre de page" oninput="document.getElementById('topPageName').textContent = this.value || 'Nouvelle page'">
                    </div>
                    <div class="fg">
                        <label>Slug (URL)</label>
                        <input type="text" id="metaSlug" value="<?= htmlspecialchars($pageSlug) ?>" placeholder="mon-slug">
                    </div>
                    <div class="fg">
                        <label>Meta Title (SEO)</label>
                        <input type="text" id="metaSeoTitle" value="<?= htmlspecialchars($metaTitle) ?>" placeholder="Titre SEO">
                    </div>
                    <div class="fg">
                        <label>Meta Description</label>
                        <textarea id="metaSeoDesc" rows="2" placeholder="Description pour Google"><?= htmlspecialchars($metaDesc) ?></textarea>
                    </div>
                    <div class="fg">
                        <label>Statut</label>
                        <select id="metaStatus">
                            <option value="draft" <?= $pageStatus === 'draft' ? 'selected' : '' ?>>🔴 Brouillon</option>
                            <option value="published" <?= $pageStatus === 'published' ? 'selected' : '' ?>>🟢 Publié</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="section-list" id="sectionList">
                <!-- Rempli par JS -->
            </div>
        </div>

        <!-- ═══ EDIT PANEL : champs de la section sélectionnée ═══ -->
        <div class="edit-panel" id="editPanel">
            <div class="edit-header" id="editHeader">
                <h2 id="editTitle"><i class="fas fa-pen"></i> Sélectionnez une section</h2>
                <button class="close-edit" onclick="closeEdit()"><i class="fas fa-times"></i></button>
            </div>
            <div class="edit-body" id="editBody">
                <div class="empty-edit">
                    <i class="fas fa-mouse-pointer"></i>
                    <p>Cliquez sur une section<br>dans le panneau de gauche</p>
                </div>
            </div>
        </div>

        <!-- ═══ PREVIEW ═══ -->
        <div class="preview-area">
            <div class="preview-shell preview-shell--desktop" id="previewShell">
                <div class="browser-chrome">
                    <div class="bc-dot bc-r"></div>
                    <div class="bc-dot bc-y"></div>
                    <div class="bc-dot bc-g"></div>
                    <div class="bc-url" id="browserUrl"><?= htmlspecialchars($pageSlug ?: 'nouvelle-page') ?></div>
                </div>
                <iframe id="previewFrame" title="Aperçu"></iframe>
            </div>
        </div>

    </div>
</div>

<script>
// ════════════════════════════════════════════════════════════
// DATA
// ════════════════════════════════════════════════════════════
const PAGE_ID = <?= $pageId ? $pageId : 'null' ?>;
const TPL_ALL = <?= $tplJson ?>;
const TPL_LABELS = <?= json_encode($tplList, JSON_UNESCAPED_UNICODE) ?>;

let currentTemplate = '<?= addslashes($activeTpl) ?>';
let currentSections = TPL_ALL[currentTemplate] || TPL_ALL['standard'];
let fieldsData = <?= $dataJson ?>;
let activeSection = null;

// ════════════════════════════════════════════════════════════
// INIT
// ════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    renderSectionList();
    updatePreview();
    initDeviceButtons();
});

// ════════════════════════════════════════════════════════════
// TEMPLATE CHANGE
// ════════════════════════════════════════════════════════════
function changeTemplate(tplKey) {
    if (!TPL_ALL[tplKey]) tplKey = 'standard';
    currentTemplate = tplKey;
    currentSections = TPL_ALL[tplKey];
    document.getElementById('topTplBadge').textContent = TPL_LABELS[tplKey] || tplKey;
    activeSection = null;
    renderSectionList();
    renderEditEmpty();
    updatePreview();
}

// ════════════════════════════════════════════════════════════
// SECTION LIST (left sidebar)
// ════════════════════════════════════════════════════════════
function renderSectionList() {
    const list = document.getElementById('sectionList');
    list.innerHTML = '';

    currentSections.forEach((sec, idx) => {
        const item = document.createElement('div');
        item.className = 'sec-item';
        item.dataset.idx = idx;
        item.onclick = () => openSection(idx);

        const iconBg = sec.color + '18';
        item.innerHTML = `
            <div class="sec-icon" style="background:${iconBg}; color:${sec.color}">
                <i class="fas ${sec.icon}"></i>
            </div>
            <span class="sec-label">${sec.section}</span>
            <span class="sec-count">${sec.fields.length}</span>
        `;
        list.appendChild(item);
    });
}

// ════════════════════════════════════════════════════════════
// OPEN SECTION (edit panel)
// ════════════════════════════════════════════════════════════
function openSection(idx) {
    activeSection = idx;
    const sec = currentSections[idx];

    // Active state in sidebar
    document.querySelectorAll('.sec-item').forEach(el => el.classList.remove('active'));
    document.querySelector(`.sec-item[data-idx="${idx}"]`)?.classList.add('active');

    // Edit panel
    const panel = document.getElementById('editPanel');
    panel.classList.remove('collapsed');

    document.getElementById('editTitle').innerHTML = `
        <i class="fas ${sec.icon}" style="color:${sec.color}"></i> ${sec.section}
    `;

    const body = document.getElementById('editBody');
    body.innerHTML = '';

    sec.fields.forEach(field => {
        const savedVal = fieldsData[field.key] || '';
        const fg = document.createElement('div');
        fg.className = 'fg';

        let input = '';
        if (field.type === 'textarea' || field.type === 'rich') {
            const cls = field.type === 'rich' ? ' rich-area' : '';
            input = `<textarea class="${cls}" data-key="${field.key}" 
                      placeholder="${field.hint || ''}" 
                      oninput="onFieldChange('${field.key}', this.value)">${escHtml(savedVal)}</textarea>`;
        } else if (field.type === 'image') {
            input = `<input type="text" data-key="${field.key}" value="${escAttr(savedVal)}" 
                      placeholder="${field.hint || 'URL de l\'image'}" 
                      oninput="onFieldChange('${field.key}', this.value)">
                     <div class="hint">Collez l'URL de l'image ou utilisez le gestionnaire de médias</div>`;
        } else {
            input = `<input type="${field.type === 'url' ? 'url' : 'text'}" data-key="${field.key}" 
                      value="${escAttr(savedVal)}" 
                      placeholder="${field.hint || ''}" 
                      oninput="onFieldChange('${field.key}', this.value)">`;
        }

        fg.innerHTML = `<label>${field.label}</label>${input}`;
        if (field.hint && field.type !== 'image') {
            fg.innerHTML += `<div class="hint">${field.hint}</div>`;
        }
        body.appendChild(fg);
    });
}

function renderEditEmpty() {
    document.getElementById('editBody').innerHTML = `
        <div class="empty-edit">
            <i class="fas fa-mouse-pointer"></i>
            <p>Cliquez sur une section<br>dans le panneau de gauche</p>
        </div>
    `;
    document.getElementById('editTitle').innerHTML = '<i class="fas fa-pen"></i> Sélectionnez une section';
}

function closeEdit() {
    activeSection = null;
    document.querySelectorAll('.sec-item').forEach(el => el.classList.remove('active'));
    renderEditEmpty();
}

// ════════════════════════════════════════════════════════════
// FIELD CHANGE → update data + preview
// ════════════════════════════════════════════════════════════
let previewTimer = null;
function onFieldChange(key, value) {
    fieldsData[key] = value;
    clearTimeout(previewTimer);
    previewTimer = setTimeout(updatePreview, 300);
}

// ════════════════════════════════════════════════════════════
// PREVIEW — generates full HTML from fieldsData
// ════════════════════════════════════════════════════════════
function updatePreview() {
    const iframe = document.getElementById('previewFrame');
    const doc = iframe.contentDocument || iframe.contentWindow.document;
    const html = generatePreviewHTML();
    doc.open();
    doc.write(html);
    doc.close();
}

function generatePreviewHTML() {
    const d = fieldsData;
    const v = (key, fallback = '') => d[key] || fallback;

    // Génération basée sur le template actif
    // On construit le HTML section par section
    let body = '';

    currentSections.forEach(sec => {
        const sectionName = sec.section.toLowerCase();

        if (sectionName.includes('hero')) {
            body += buildHeroSection(sec, d);
        } else if (sectionName.includes('stat')) {
            body += buildStatsSection(sec, d);
        } else if (sectionName.includes('bénéfice') || sectionName.includes('benefice')) {
            body += buildCardsSection(sec, d, 'ben');
        } else if (sectionName.includes('problème') || sectionName.includes('probleme') || sectionName.includes('douleur')) {
            body += buildCardsSection(sec, d, 'pb');
        } else if (sectionName.includes('autorité') || sectionName.includes('autorite') || sectionName.includes('preuve') || sectionName.includes('chiffre')) {
            body += buildCardsSection(sec, d, 'auth');
        } else if (sectionName.includes('méthode') || sectionName.includes('methode') || sectionName.includes('étape') || sectionName.includes('process')) {
            body += buildStepsSection(sec, d);
        } else if (sectionName.includes('expertise') || sectionName.includes('pilier')) {
            body += buildCardsSection(sec, d, 'exp');
        } else if (sectionName.includes('présentation') || sectionName.includes('presentation') || sectionName.includes('conseiller')) {
            body += buildPresSection(sec, d);
        } else if (sectionName.includes('guide') || sectionName.includes('article') || sectionName.includes('contenu')) {
            body += buildGuideSection(sec, d);
        } else if (sectionName.includes('argument') || sectionName.includes('confiance')) {
            body += buildCardsSection(sec, d, 'trust');
        } else if (sectionName.includes('cta') || sectionName.includes('finale') || sectionName.includes('retour')) {
            body += buildCtaSection(sec, d);
        } else if (sectionName.includes('valeur')) {
            body += buildCardsSection(sec, d, 'val');
        } else if (sectionName.includes('merci') || sectionName.includes('confirmation')) {
            body += buildMerciSection(sec, d);
        } else if (sectionName.includes('tarif') || sectionName.includes('grille')) {
            body += buildTarifSection(sec, d);
        } else if (sectionName.includes('marché') || sectionName.includes('marche')) {
            body += buildStatsSection(sec, d);
        } else if (sectionName.includes('tendance')) {
            body += buildCardsSection(sec, d, 'trend');
        } else {
            body += buildGenericSection(sec, d);
        }
    });

    return `<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root{--primary:#1B3A4B;--primary-light:#2C5F7C;--primary-dark:#122A37;--accent:#C8A96E;--accent-light:#E8D5A8;--text:#2D2D2D;--text-light:#5A5A5A;--bg:#FAFAF8;--bg-warm:#F5F0E8;--border:#E8E4DC;--danger:#C0392B;--danger-light:#FDF0EF;--radius:12px;--font-display:'Playfair Display',Georgia,serif;--font-body:'DM Sans',sans-serif;--transition:.3s ease}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
body{font-family:var(--font-body);font-size:17px;line-height:1.75;color:var(--text);background:var(--bg)}
h1,h2,h3{font-family:var(--font-display);color:var(--primary);line-height:1.25}
.container{max-width:1140px;margin:0 auto;padding:0 24px}
.text-center{text-align:center}
.section-w{background:var(--bg);padding:80px 0}
.section-b{background:var(--bg-warm);padding:80px 0}
.s-badge{display:inline-block;background:rgba(200,169,110,.15);color:#A68B4B;font-size:.78rem;font-weight:700;letter-spacing:2px;text-transform:uppercase;padding:6px 20px;border-radius:30px;margin-bottom:16px}
.s-title{font-size:clamp(1.6rem,3.5vw,2.2rem);font-weight:700;margin-bottom:14px;max-width:720px;margin-left:auto;margin-right:auto}
.s-sub{font-size:1.05rem;color:var(--text-light);max-width:640px;margin:0 auto;line-height:1.7}
.s-sub strong{color:var(--text)}
.hero{background:linear-gradient(160deg,var(--primary-dark),var(--primary),var(--primary-light));padding:90px 24px 70px;text-align:center;position:relative;overflow:hidden}
.hero::before{content:'';position:absolute;top:-30%;right:-15%;width:550px;height:550px;background:radial-gradient(circle,rgba(200,169,110,.1),transparent 70%);border-radius:50%;pointer-events:none}
.hero-ey{color:var(--accent-light);font-size:.85rem;font-weight:600;letter-spacing:2.5px;text-transform:uppercase;margin-bottom:24px;opacity:.85}
.hero h1{color:#fff;font-size:clamp(1.8rem,4.5vw,2.8rem);max-width:800px;margin:0 auto 20px;line-height:1.2}
.hero-desc{color:rgba(255,255,255,.75);font-size:1.1rem;font-weight:300;line-height:1.7;max-width:600px;margin:0 auto 40px}
.hero-boxes{display:grid;grid-template-columns:repeat(2,1fr);gap:18px;max-width:700px;margin:0 auto 40px}
.hbox{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);border-radius:var(--radius);padding:24px 20px;text-align:left;backdrop-filter:blur(8px)}
.hbox-icon{font-size:1.6rem;margin-bottom:10px}
.hbox h3{color:#fff;font-family:var(--font-body);font-size:1rem;font-weight:600;margin-bottom:6px}
.hbox p{color:rgba(255,255,255,.6);font-size:.88rem;line-height:1.5}
.cta-btn{display:inline-flex;align-items:center;gap:10px;background:var(--accent);color:var(--primary-dark);font-family:var(--font-body);font-size:1.05rem;font-weight:700;padding:16px 40px;border-radius:10px;text-decoration:none;box-shadow:0 4px 20px rgba(200,169,110,.3);transition:all var(--transition)}
.cta-btn:hover{background:var(--accent-light);transform:translateY(-2px);box-shadow:0 8px 30px rgba(200,169,110,.45)}
.cards{display:grid;gap:24px}
.cards-3{grid-template-columns:repeat(3,1fr)}
.cards-2{grid-template-columns:repeat(2,1fr)}
.card{background:#fff;border-radius:16px;padding:32px 28px;border:1px solid var(--border);transition:all var(--transition)}
.card:hover{box-shadow:0 4px 20px rgba(27,58,75,.08);transform:translateY(-3px)}
.card h3{font-size:1.1rem;margin-bottom:12px}
.card p{color:var(--text-light);font-size:.95rem;line-height:1.7}
.card-icon{font-size:2rem;margin-bottom:14px;display:block}
.steps{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px}
.step{background:#fff;border:1px solid var(--border);border-radius:16px;padding:36px 24px;text-align:center}
.step-n{width:50px;height:50px;margin:0 auto 18px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;font-family:var(--font-display);font-size:1.3rem;font-weight:700;border-radius:14px;display:flex;align-items:center;justify-content:center}
.step h3{font-size:1rem;margin-bottom:8px}
.step p{color:var(--text-light);font-size:.9rem;line-height:1.6}
.guide-card{display:flex;gap:24px;background:#fff;border-radius:16px;padding:32px;border:1px solid var(--border);margin-bottom:20px}
.guide-num{flex-shrink:0;width:50px;height:50px;background:linear-gradient(135deg,var(--accent),#A68B4B);color:#fff;font-family:var(--font-display);font-size:1.2rem;font-weight:700;border-radius:14px;display:flex;align-items:center;justify-content:center}
.guide-card h3{font-size:1.15rem;margin-bottom:10px;color:var(--primary)}
.guide-card p{color:var(--text-light);font-size:.95rem;line-height:1.7}
.cta-final{background:linear-gradient(160deg,var(--primary-dark),var(--primary),var(--primary-light));padding:80px 0;text-align:center;position:relative;overflow:hidden}
.cta-final h2{color:#fff;font-size:clamp(1.6rem,3.5vw,2.2rem);margin-bottom:16px}
.cta-final p{color:rgba(255,255,255,.75);font-size:1.05rem;line-height:1.7;margin-bottom:32px;max-width:600px;margin-left:auto;margin-right:auto}
.cta-final .urgency{margin-top:18px;color:rgba(255,255,255,.6);font-size:.88rem}
.pres-section{display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center}
.pres-tags{display:flex;flex-wrap:wrap;gap:8px;margin:16px 0}
.pres-tag{padding:6px 14px;background:rgba(200,169,110,.12);color:#A68B4B;border-radius:20px;font-size:.82rem;font-weight:600}
.tarif-table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;border:1px solid var(--border)}
.tarif-table th,.tarif-table td{padding:14px 20px;text-align:left;border-bottom:1px solid var(--border)}
.tarif-table th{background:var(--primary);color:#fff;font-size:.9rem;font-weight:600}
.tarif-table td{font-size:.95rem;color:var(--text)}
@media(max-width:768px){.cards-3,.cards-2{grid-template-columns:1fr}.hero-boxes{grid-template-columns:1fr}.pres-section{grid-template-columns:1fr}.guide-card{flex-direction:column;gap:12px}}
</style>
</head>
<body>${body}</body>
</html>`;
}

// ════════════════════════════════════════════════════════════
// SECTION BUILDERS
// ════════════════════════════════════════════════════════════
function v(key, fb='') { return fieldsData[key] || fb; }

function buildHeroSection(sec, d) {
    const eyebrow = v('hero_eyebrow');
    const title = v('hero_title', 'Titre principal');
    const sub = v('hero_subtitle', v('hero_subtitle'));
    const desc = v('hero_subtitle', '');
    const cta1 = v('hero_cta_text');
    const cta1u = v('hero_cta_url', '#');
    const cta2 = v('hero_cta2_text');

    // Check for boxes (box1, box2)
    const box1t = v('box1_title', v('hero_stat1_num',''));
    const box2t = v('box2_title', v('hero_stat2_num',''));

    let boxes = '';
    if (box1t || box2t) {
        boxes = '<div class="hero-boxes">';
        if (box1t) {
            boxes += `<div class="hbox">
                <div class="hbox-icon">${v('box1_icon', v('hero_stat1_num','📊'))}</div>
                <h3>${box1t}</h3>
                <p>${v('box1_text', v('hero_stat1_lbl',''))}</p>
            </div>`;
        }
        if (box2t) {
            boxes += `<div class="hbox">
                <div class="hbox-icon">${v('box2_icon', v('hero_stat2_num','🎯'))}</div>
                <h3>${box2t}</h3>
                <p>${v('box2_text', v('hero_stat2_lbl',''))}</p>
            </div>`;
        }
        // box3 if exists
        const box3t = v('box3_title', v('hero_stat3_num',''));
        if (box3t) {
            boxes += `<div class="hbox">
                <div class="hbox-icon">${v('box3_icon','✨')}</div>
                <h3>${box3t}</h3>
                <p>${v('box3_text', v('hero_stat3_lbl',''))}</p>
            </div>`;
        }
        boxes += '</div>';
    }

    return `<section class="hero">
        <div class="container">
            ${eyebrow ? `<p class="hero-ey">${eyebrow}</p>` : ''}
            <h1>${title}</h1>
            ${desc ? `<p class="hero-desc">${desc}</p>` : ''}
            ${boxes}
            ${cta1 ? `<a href="${cta1u}" class="cta-btn">${cta1}</a>` : ''}
            ${cta2 ? `<a href="${v('hero_cta2_url','#')}" class="cta-btn" style="margin-left:12px;background:transparent;border:2px solid var(--accent);color:var(--accent-light)">${cta2}</a>` : ''}
        </div>
    </section>`;
}

function buildStatsSection(sec, d) {
    let stats = '';
    for (let i = 1; i <= 4; i++) {
        const num = v(`stat${i}_num`, v(`hero_stat${i}_num`, ''));
        const lbl = v(`stat${i}_lbl`, v(`hero_stat${i}_lbl`, ''));
        if (num) {
            stats += `<div class="card" style="text-align:center">
                <div style="font-size:2rem;font-weight:800;color:var(--primary);font-family:var(--font-display)">${num}</div>
                <p>${lbl}</p>
            </div>`;
        }
    }
    if (!stats) return '';
    const title = v('hero_stat1_num') ? '' : v(sec.fields[0]?.key,'');
    return `<section class="section-b">
        <div class="container text-center">
            ${title ? `<h2 class="s-title">${title}</h2>` : ''}
            <div class="cards cards-${Math.min(4,sec.fields.filter(f=>f.key.includes('num')).length)}" style="max-width:900px;margin:0 auto">${stats}</div>
        </div>
    </section>`;
}

function buildCardsSection(sec, d, prefix) {
    const titleKey = sec.fields.find(f => f.key.includes('title') && !f.key.includes('1') && !f.key.includes('2') && !f.key.includes('3'));
    const badgeKey = sec.fields.find(f => f.key.includes('badge'));
    const subKey = sec.fields.find(f => f.key.includes('sub'));
    const sTitle = titleKey ? v(titleKey.key) : '';
    const badge = badgeKey ? v(badgeKey.key) : '';
    const sub = subKey ? v(subKey.key) : '';

    let cards = '';
    for (let i = 1; i <= 4; i++) {
        const ct = v(`${prefix}${i}_title`, v(`${prefix}_${i}_title`, ''));
        const ctxt = v(`${prefix}${i}_text`, v(`${prefix}_${i}_text`, ''));
        const cicon = v(`${prefix}${i}_icon`, '');
        if (ct || ctxt) {
            cards += `<div class="card">
                ${cicon ? `<div class="card-icon">${cicon}</div>` : ''}
                ${ct ? `<h3>${ct}</h3>` : ''}
                ${ctxt ? `<p>${ctxt}</p>` : ''}
            </div>`;
        }
    }
    if (!cards && !sTitle) return '';

    const count = (cards.match(/class="card"/g) || []).length;
    const bg = sec.color === '#ef4444' ? 'section-w' : 'section-b';
    return `<section class="${bg}">
        <div class="container text-center">
            ${badge ? `<span class="s-badge">${badge}</span>` : ''}
            ${sTitle ? `<h2 class="s-title">${sTitle}</h2>` : ''}
            ${sub ? `<p class="s-sub">${sub}</p>` : ''}
            <div class="cards cards-${Math.min(count,3)}" style="margin-top:40px">${cards}</div>
        </div>
    </section>`;
}

function buildStepsSection(sec, d) {
    const title = v('method_title', sec.section);
    const sub = v('method_sub', '');
    let steps = '';
    for (let i = 1; i <= 5; i++) {
        const st = v(`step${i}_title`, '');
        const stxt = v(`step${i}_text`, '');
        const sn = v(`step${i}_num`, String(i));
        if (st) {
            steps += `<div class="step">
                <div class="step-n">${sn}</div>
                <h3>${st}</h3>
                <p>${stxt}</p>
            </div>`;
        }
    }
    const cta = v('method_cta_text', '');
    const result = v('result', '');
    return `<section class="section-w">
        <div class="container text-center">
            <span class="s-badge">${v('method_badge', v('badge','La méthode'))}</span>
            <h2 class="s-title">${title}</h2>
            ${sub ? `<p class="s-sub">${sub}</p>` : ''}
            <div class="steps" style="margin-top:40px">${steps}</div>
            ${result ? `<p style="margin-top:40px;font-size:1.1rem"><strong>${result}</strong></p>` : ''}
            ${cta ? `<a href="${v('method_cta_url','#')}" class="cta-btn" style="margin-top:24px">${cta}</a>` : ''}
        </div>
    </section>`;
}

function buildPresSection(sec, d) {
    const tags = [v('pres_tag1'), v('pres_tag2'), v('pres_tag3')].filter(Boolean);
    return `<section class="section-b">
        <div class="container">
            <div class="pres-section">
                <div>
                    ${v('pres_title') ? `<h2 class="s-title" style="text-align:left">${v('pres_title')}</h2>` : ''}
                    ${v('pres_sub') ? `<p class="s-sub" style="text-align:left;margin:0 0 16px">${v('pres_sub')}</p>` : ''}
                    <div style="color:var(--text-light);line-height:1.8">${v('pres_text','')}</div>
                    ${tags.length ? `<div class="pres-tags">${tags.map(t=>`<span class="pres-tag">${t}</span>`).join('')}</div>` : ''}
                    ${v('pres_cta_text') ? `<a href="${v('pres_cta_url','#')}" class="cta-btn" style="margin-top:16px">${v('pres_cta_text')}</a>` : ''}
                </div>
                <div style="background:var(--border);border-radius:16px;min-height:300px;display:flex;align-items:center;justify-content:center;color:var(--text-light);font-size:.9rem">
                    📷 Photo conseiller
                </div>
            </div>
        </div>
    </section>`;
}

function buildGuideSection(sec, d) {
    const title = v('guide_title', sec.section);
    let guides = '';
    for (let i = 1; i <= 5; i++) {
        const gt = v(`g${i}_title`, '');
        const gtxt = v(`g${i}_text`, '');
        const gn = v(`g${i}_num`, `0${i}`);
        if (gt || gtxt) {
            guides += `<div class="guide-card">
                <div class="guide-num">${gn}</div>
                <div>
                    ${gt ? `<h3>${gt}</h3>` : ''}
                    ${gtxt ? `<p>${gtxt}</p>` : ''}
                </div>
            </div>`;
        }
    }
    if (!guides) return '';
    return `<section class="section-b">
        <div class="container">
            <div class="text-center" style="margin-bottom:40px">
                <span class="s-badge">${v('guide_badge','Guide pratique')}</span>
                <h2 class="s-title">${title}</h2>
            </div>
            ${guides}
        </div>
    </section>`;
}

function buildCtaSection(sec, d) {
    return `<section class="cta-final">
        <div class="container">
            <h2>${v('cta_title', 'Prêt à passer à l\'action ?')}</h2>
            <p>${v('cta_text', v('cta_desc', ''))}</p>
            ${v('cta_btn_text') ? `<a href="${v('cta_btn_url','#')}" class="cta-btn" style="font-size:1.1rem;padding:18px 48px">${v('cta_btn_text')}</a>` : ''}
            ${v('cta_phone_text') ? `<p style="margin-top:16px;color:rgba(255,255,255,.7);font-size:.95rem">${v('cta_phone_text')}</p>` : ''}
            ${v('urgency') ? `<p class="urgency">${v('urgency')}</p>` : ''}
        </div>
    </section>`;
}

function buildMerciSection(sec, d) {
    return `<section class="section-w" style="min-height:60vh;display:flex;align-items:center">
        <div class="container text-center">
            <div style="font-size:4rem;margin-bottom:20px">✅</div>
            <h1 class="s-title">${v('merci_title','Merci !')}</h1>
            <p class="s-sub">${v('merci_text','')}</p>
            ${v('cta_btn_primary') ? `<a href="${v('cta_btn_url','/')}" class="cta-btn" style="margin-top:30px">${v('cta_btn_primary')}</a>` : ''}
        </div>
    </section>`;
}

function buildTarifSection(sec, d) {
    let rows = '';
    for (let i = 1; i <= 5; i++) {
        const label = v(`tarif${i}_label`, '');
        const val = v(`tarif${i}_value`, '');
        if (label) rows += `<tr><td>${label}</td><td><strong>${val}</strong></td></tr>`;
    }
    return `<section class="section-w">
        <div class="container" style="max-width:700px">
            ${v('tarif_intro') ? `<div style="margin-bottom:30px;color:var(--text-light);line-height:1.8">${v('tarif_intro')}</div>` : ''}
            ${rows ? `<table class="tarif-table"><thead><tr><th>Prestation</th><th>Tarif</th></tr></thead><tbody>${rows}</tbody></table>` : ''}
            ${v('tarif_note') ? `<p style="margin-top:16px;font-size:.85rem;color:var(--text-light);font-style:italic">${v('tarif_note')}</p>` : ''}
        </div>
    </section>`;
}

function buildGenericSection(sec, d) {
    let content = '';
    sec.fields.forEach(f => {
        const val = v(f.key, '');
        if (!val) return;
        if (f.type === 'rich' || f.key.includes('content') || f.key.includes('text')) {
            content += `<div style="color:var(--text-light);line-height:1.8;margin-bottom:16px">${val}</div>`;
        } else if (f.key.includes('title') || f.key.includes('titre')) {
            content += `<h2 class="s-title" style="text-align:left">${val}</h2>`;
        } else if (f.key.includes('subtitle') || f.key.includes('sub')) {
            content += `<p class="s-sub" style="text-align:left;margin:0 0 16px">${val}</p>`;
        }
    });
    if (!content) return '';
    return `<section class="section-w"><div class="container">${content}</div></section>`;
}

// ════════════════════════════════════════════════════════════
// DEVICES
// ════════════════════════════════════════════════════════════
function initDeviceButtons() {
    document.querySelectorAll('.dev-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.dev-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const device = btn.dataset.device;
            const shell = document.getElementById('previewShell');
            shell.className = `preview-shell preview-shell--${device}`;
        });
    });
}

// ════════════════════════════════════════════════════════════
// META TOGGLE
// ════════════════════════════════════════════════════════════
function toggleMeta() {
    const body = document.getElementById('metaBody');
    const toggle = body.previousElementSibling;
    body.classList.toggle('open');
    toggle.classList.toggle('open');
}

// ════════════════════════════════════════════════════════════
// SAVE
// ════════════════════════════════════════════════════════════
function savePage() {
    const payload = {
        action: 'save_page',
        id: PAGE_ID,
        title: document.getElementById('metaPageTitle').value,
        slug: document.getElementById('metaSlug').value,
        template: currentTemplate,
        status: document.getElementById('metaStatus').value,
        meta_title: document.getElementById('metaSeoTitle').value,
        meta_description: document.getElementById('metaSeoDesc').value,
        sections_json: JSON.stringify(fieldsData),
    };

    fetch('/admin/api/content/pages.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            showToast('Page sauvegardée avec succès');
            if (!PAGE_ID && d.id) {
                window.history.replaceState({}, '', `?page=content/pages/edit&id=${d.id}`);
            }
        } else {
            showToast('Erreur : ' + (d.error || 'inconnue'), 'err');
        }
    })
    .catch(() => showToast('Erreur de connexion', 'err'));
}

// ════════════════════════════════════════════════════════════
// TOAST
// ════════════════════════════════════════════════════════════
function showToast(msg, type = 'ok') {
    const box = document.getElementById('toastBox');
    const t = document.createElement('div');
    t.className = `toast toast-${type}`;
    t.innerHTML = `<span>${type === 'ok' ? '✅' : '❌'}</span><span>${msg}</span>`;
    box.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

// ════════════════════════════════════════════════════════════
// UTILS
// ════════════════════════════════════════════════════════════
function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
function escAttr(s) { return String(s).replace(/"/g, '&quot;').replace(/</g, '&lt;'); }

// ════════════════════════════════════════════════════════════
// KEYBOARD
// ════════════════════════════════════════════════════════════
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        savePage();
    }
});
</script>

</body>
</html>