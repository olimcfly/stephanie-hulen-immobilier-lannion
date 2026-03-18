<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE TEMPLATE EDITOR — Édition des templates frontend
 *  /admin/modules/system/settings/templates/index.php
 *  
 *  Accessible via : dashboard.php?page=settings&tab=templates
 *  ou directement : dashboard.php?page=templates-editor
 * ══════════════════════════════════════════════════════════════
 */
if (!defined('ADMIN_ROUTER')) { header('Location: /admin/dashboard.php?page=settings'); exit; }

$page_title = 'Éditeur de Templates';
?>

<style>
/* ─── Template Editor Styles ─── */
.tpl-editor-wrap {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 0;
    height: calc(100vh - 80px);
    background: #1e1e2e;
    border-radius: 12px;
    overflow: hidden;
}

/* Sidebar fichiers */
.tpl-sidebar {
    background: #181825;
    border-right: 1px solid rgba(255,255,255,.06);
    display: flex; flex-direction: column;
    overflow: hidden;
}
.tpl-sidebar-header {
    padding: 16px;
    border-bottom: 1px solid rgba(255,255,255,.06);
}
.tpl-sidebar-header h3 {
    color: #cdd6f4;
    font-size: 14px;
    font-weight: 600;
    margin: 0 0 10px 0;
    font-family: 'DM Sans', sans-serif;
}
.tpl-search {
    width: 100%;
    padding: 8px 10px 8px 32px;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 8px;
    color: #cdd6f4;
    font-size: 13px;
    outline: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' fill='%236c7086' viewBox='0 0 24 24'%3E%3Cpath d='M15.5 14h-.79l-.28-.27A6.47 6.47 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: 10px center;
}
.tpl-search::placeholder { color: #585b70; }

/* Liste fichiers */
.tpl-file-list {
    flex: 1;
    overflow-y: auto;
    padding: 8px 0;
}
.tpl-category {
    padding: 6px 16px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #585b70;
    margin-top: 8px;
}
.tpl-file-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 16px;
    cursor: pointer;
    color: #a6adc8;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    transition: background .15s, color .15s;
    border-left: 3px solid transparent;
}
.tpl-file-item:hover {
    background: rgba(255,255,255,.04);
    color: #cdd6f4;
}
.tpl-file-item.active {
    background: rgba(26,77,122,.2);
    color: #89b4fa;
    border-left-color: #d4a574;
}
.tpl-file-item .fa-file-code { font-size: 12px; opacity: .6; }
.tpl-file-item .tpl-file-meta {
    margin-left: auto;
    font-size: 10px;
    color: #585b70;
}

/* Zone principale */
.tpl-main {
    display: flex; flex-direction: column;
    overflow: hidden;
}

/* Toolbar */
.tpl-toolbar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    background: #1e1e2e;
    border-bottom: 1px solid rgba(255,255,255,.06);
    flex-shrink: 0;
}
.tpl-toolbar .tpl-filename {
    color: #d4a574;
    font-weight: 600;
    font-size: 14px;
    font-family: 'DM Sans', sans-serif;
    margin-right: auto;
}
.tpl-toolbar .tpl-modified {
    color: #585b70;
    font-size: 11px;
    margin-right: 12px;
}
.tpl-btn {
    padding: 6px 14px;
    border-radius: 8px;
    border: none;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .15s;
    font-family: 'DM Sans', sans-serif;
}
.tpl-btn-primary {
    background: #1a4d7a;
    color: #fff;
}
.tpl-btn-primary:hover { background: #236294; }
.tpl-btn-secondary {
    background: rgba(255,255,255,.06);
    color: #a6adc8;
}
.tpl-btn-secondary:hover { background: rgba(255,255,255,.1); color: #cdd6f4; }
.tpl-btn-gold {
    background: #d4a574;
    color: #1a1a2e;
}
.tpl-btn-gold:hover { background: #deb68a; }
.tpl-btn-danger {
    background: rgba(243,139,168,.1);
    color: #f38ba8;
}
.tpl-btn-danger:hover { background: rgba(243,139,168,.2); }

/* Editor + Preview split */
.tpl-content {
    flex: 1;
    display: flex;
    overflow: hidden;
}
.tpl-editor-pane {
    flex: 1;
    position: relative;
    min-width: 0;
}
#tpl-ace-editor {
    position: absolute;
    inset: 0;
    font-size: 13px !important;
}
.tpl-preview-pane {
    width: 50%;
    border-left: 1px solid rgba(255,255,255,.06);
    background: #fff;
    display: none;
    position: relative;
}
.tpl-preview-pane.visible { display: block; }
.tpl-preview-pane iframe {
    width: 100%; height: 100%;
    border: none;
}

/* Panneau backups */
.tpl-backups-panel {
    position: absolute;
    top: 0; right: 0; bottom: 0;
    width: 300px;
    background: #181825;
    border-left: 1px solid rgba(255,255,255,.06);
    z-index: 10;
    display: none;
    flex-direction: column;
    overflow: hidden;
}
.tpl-backups-panel.visible { display: flex; }
.tpl-backups-header {
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255,255,255,.06);
    display: flex; align-items: center; justify-content: space-between;
}
.tpl-backups-header h4 { color: #cdd6f4; font-size: 13px; margin: 0; }
.tpl-backups-list {
    flex: 1; overflow-y: auto; padding: 8px;
}
.tpl-backup-item {
    padding: 8px 10px;
    border-radius: 6px;
    margin-bottom: 4px;
    cursor: pointer;
    transition: background .15s;
}
.tpl-backup-item:hover { background: rgba(255,255,255,.04); }
.tpl-backup-item .bk-date { color: #a6adc8; font-size: 12px; display: block; }
.tpl-backup-item .bk-size { color: #585b70; font-size: 11px; }

/* Toast */
.tpl-toast {
    position: fixed; bottom: 20px; right: 20px;
    padding: 12px 20px; border-radius: 10px;
    color: #fff; font-size: 13px; font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    z-index: 99999;
    opacity: 0; transform: translateY(10px);
    transition: all .3s;
    pointer-events: none;
}
.tpl-toast.show { opacity: 1; transform: translateY(0); }
.tpl-toast.success { background: #059669; }
.tpl-toast.error { background: #dc2626; }
.tpl-toast.info { background: #1a4d7a; }

/* Empty state */
.tpl-empty {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    height: 100%; color: #585b70;
    font-family: 'DM Sans', sans-serif;
}
.tpl-empty i { font-size: 48px; margin-bottom: 16px; opacity: .4; }
.tpl-empty p { font-size: 14px; }

/* Indicateur non-sauvegardé */
.tpl-unsaved { color: #f38ba8 !important; }
.tpl-unsaved::after { content: ' •'; }

/* Scrollbar */
.tpl-file-list::-webkit-scrollbar,
.tpl-backups-list::-webkit-scrollbar { width: 4px; }
.tpl-file-list::-webkit-scrollbar-thumb,
.tpl-backups-list::-webkit-scrollbar-thumb { background: rgba(255,255,255,.08); border-radius: 4px; }
</style>

<div class="tpl-editor-wrap">
    <!-- Sidebar fichiers -->
    <div class="tpl-sidebar">
        <div class="tpl-sidebar-header">
            <h3><i class="fas fa-code"></i> Templates Frontend</h3>
            <input type="text" class="tpl-search" id="tplSearch" placeholder="Rechercher un template...">
        </div>
        <div class="tpl-file-list" id="tplFileList">
            <div style="padding:20px;color:#585b70;text-align:center;">
                <i class="fas fa-spinner fa-spin"></i> Chargement...
            </div>
        </div>
    </div>

    <!-- Zone principale -->
    <div class="tpl-main">
        <!-- Toolbar -->
        <div class="tpl-toolbar">
            <span class="tpl-filename" id="tplFilename">Sélectionnez un template</span>
            <span class="tpl-modified" id="tplModified"></span>
            <button class="tpl-btn tpl-btn-secondary" onclick="TplEditor.togglePreview()" id="btnPreview" disabled>
                <i class="fas fa-eye"></i> Preview
            </button>
            <button class="tpl-btn tpl-btn-secondary" onclick="TplEditor.showBackups()" id="btnBackups" disabled>
                <i class="fas fa-history"></i> Backups
            </button>
            <button class="tpl-btn tpl-btn-gold" onclick="TplEditor.save()" id="btnSave" disabled>
                <i class="fas fa-save"></i> Sauvegarder
            </button>
        </div>

        <!-- Editor + Preview -->
        <div class="tpl-content">
            <div class="tpl-editor-pane" id="tplEditorPane">
                <div id="tpl-ace-editor"></div>
                <!-- Empty state -->
                <div class="tpl-empty" id="tplEmpty">
                    <i class="fas fa-file-code"></i>
                    <p>Sélectionnez un template à modifier</p>
                </div>
            </div>
            <div class="tpl-preview-pane" id="tplPreviewPane">
                <iframe id="tplPreviewFrame" sandbox="allow-same-origin allow-scripts"></iframe>
            </div>
            <!-- Panneau backups -->
            <div class="tpl-backups-panel" id="tplBackupsPanel">
                <div class="tpl-backups-header">
                    <h4><i class="fas fa-history"></i> Historique</h4>
                    <button class="tpl-btn tpl-btn-secondary" onclick="TplEditor.hideBackups()" style="padding:4px 8px;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="tpl-backups-list" id="tplBackupsList"></div>
            </div>
        </div>
    </div>
</div>

<div class="tpl-toast" id="tplToast"></div>

<!-- Ace Editor CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.6/ace.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.6/mode-php.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.6/theme-one_dark.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.32.6/ext-searchbox.js"></script>

<script>
const TplEditor = (() => {
    const API = '/admin/api/system/templates.php';
    const PREVIEW_URL = '/front/preview-template.php';
    
    let editor = null;
    let currentFile = null;
    let templates = [];
    let hasUnsaved = false;
    let previewVisible = false;
    let backupsVisible = false;
    let originalContent = '';
    
    // ─── Init ───
    function init() {
        // Init Ace
        editor = ace.edit('tpl-ace-editor');
        editor.setTheme('ace/theme/one_dark');
        editor.session.setMode('ace/mode/php');
        editor.setOptions({
            fontSize: 13,
            showPrintMargin: false,
            tabSize: 4,
            useSoftTabs: true,
            wrap: true,
            enableBasicAutocompletion: true,
            showGutter: true,
            highlightActiveLine: true,
        });
        editor.renderer.setScrollMargin(8, 8);
        editor.setReadOnly(true); // Jusqu'à qu'on charge un fichier
        
        // Détecter les changements
        editor.on('change', () => {
            if (!currentFile) return;
            const changed = editor.getValue() !== originalContent;
            if (changed !== hasUnsaved) {
                hasUnsaved = changed;
                updateUnsavedState();
            }
        });
        
        // Raccourcis clavier
        editor.commands.addCommand({
            name: 'save',
            bindKey: { win: 'Ctrl-S', mac: 'Cmd-S' },
            exec: () => save()
        });
        
        // Chercher les templates
        loadTemplates();
        
        // Filtre recherche
        document.getElementById('tplSearch').addEventListener('input', (e) => {
            renderFileList(e.target.value.toLowerCase());
        });
        
        // Message postMessage de la preview
        window.addEventListener('message', (e) => {
            if (e.data === 'close-preview') togglePreview();
        });
    }
    
    // ─── Load Templates ───
    async function loadTemplates() {
        try {
            const res = await fetch(`${API}?action=list`);
            const data = await res.json();
            if (data.success) {
                templates = data.templates;
                renderFileList();
            } else {
                toast('Erreur chargement templates', 'error');
            }
        } catch (err) {
            toast('Erreur réseau : ' + err.message, 'error');
        }
    }
    
    // ─── Render File List ───
    function renderFileList(filter = '') {
        const container = document.getElementById('tplFileList');
        const categoryLabels = {
            pages: '📄 Pages',
            captures: '🎯 Captures',
            ressources: '📚 Ressources',
        };
        
        let html = '';
        let lastCat = '';
        
        const filtered = templates.filter(t =>
            !filter || t.filename.toLowerCase().includes(filter) || t.title.toLowerCase().includes(filter)
        );
        
        for (const tpl of filtered) {
            if (tpl.category !== lastCat) {
                lastCat = tpl.category;
                html += `<div class="tpl-category">${categoryLabels[tpl.category] || tpl.category}</div>`;
            }
            const isActive = currentFile && currentFile.path === tpl.path;
            html += `
                <div class="tpl-file-item ${isActive ? 'active' : ''}" 
                     onclick="TplEditor.openFile('${tpl.path}')" 
                     data-path="${tpl.path}">
                    <i class="fas fa-file-code"></i>
                    <span>${tpl.filename}</span>
                    <span class="tpl-file-meta">${tpl.lines}L</span>
                </div>`;
        }
        
        container.innerHTML = html || '<div style="padding:20px;color:#585b70;text-align:center;">Aucun template trouvé</div>';
    }
    
    // ─── Open File ───
    async function openFile(path) {
        // Vérifier changements non sauvegardés
        if (hasUnsaved && !confirm('Modifications non sauvegardées. Continuer ?')) return;
        
        try {
            const res = await fetch(`${API}?action=read&path=${encodeURIComponent(path)}`);
            const data = await res.json();
            
            if (!data.success) {
                toast(data.error || 'Erreur', 'error');
                return;
            }
            
            currentFile = { path, filename: data.filename, modified: data.modified };
            originalContent = data.content;
            
            // Mettre à jour l'éditeur
            editor.setReadOnly(false);
            editor.setValue(data.content, -1);
            editor.clearSelection();
            editor.gotoLine(1);
            hasUnsaved = false;
            
            // UI
            document.getElementById('tplFilename').textContent = data.filename;
            document.getElementById('tplFilename').classList.remove('tpl-unsaved');
            document.getElementById('tplModified').textContent = 'Modifié : ' + data.modified;
            document.getElementById('tplEmpty').style.display = 'none';
            document.getElementById('btnSave').disabled = false;
            document.getElementById('btnPreview').disabled = false;
            document.getElementById('btnBackups').disabled = false;
            
            // Mettre à jour la liste
            renderFileList(document.getElementById('tplSearch').value.toLowerCase());
            
            // Rafraîchir la preview si visible
            if (previewVisible) refreshPreview();
            
        } catch (err) {
            toast('Erreur réseau : ' + err.message, 'error');
        }
    }
    
    // ─── Save ───
    async function save() {
        if (!currentFile) return;
        
        const content = editor.getValue();
        
        try {
            const fd = new FormData();
            fd.append('action', 'save');
            fd.append('path', currentFile.path);
            fd.append('content', content);
            
            const res = await fetch(API, { method: 'POST', body: fd });
            const data = await res.json();
            
            if (data.success) {
                originalContent = content;
                hasUnsaved = false;
                updateUnsavedState();
                document.getElementById('tplModified').textContent = 'Modifié : ' + data.modified;
                toast(`Sauvegardé (${data.bytes} octets) — Backup : ${data.backup}`, 'success');
                
                // Rafraîchir la preview
                if (previewVisible) refreshPreview();
            } else {
                toast(data.error || 'Erreur de sauvegarde', 'error');
            }
        } catch (err) {
            toast('Erreur réseau : ' + err.message, 'error');
        }
    }
    
    // ─── Preview ───
    function togglePreview() {
        previewVisible = !previewVisible;
        const pane = document.getElementById('tplPreviewPane');
        const btn = document.getElementById('btnPreview');
        
        if (previewVisible) {
            pane.classList.add('visible');
            btn.classList.remove('tpl-btn-secondary');
            btn.classList.add('tpl-btn-primary');
            refreshPreview();
        } else {
            pane.classList.remove('visible');
            btn.classList.remove('tpl-btn-primary');
            btn.classList.add('tpl-btn-secondary');
        }
    }
    
    function refreshPreview() {
        if (!currentFile) return;
        const frame = document.getElementById('tplPreviewFrame');
        frame.src = `${PREVIEW_URL}?tpl=${encodeURIComponent(currentFile.path)}&t=${Date.now()}`;
    }
    
    // ─── Backups ───
    async function showBackups() {
        if (!currentFile) return;
        
        const panel = document.getElementById('tplBackupsPanel');
        const list = document.getElementById('tplBackupsList');
        
        backupsVisible = true;
        panel.classList.add('visible');
        list.innerHTML = '<div style="padding:16px;color:#585b70;text-align:center;"><i class="fas fa-spinner fa-spin"></i></div>';
        
        try {
            const res = await fetch(`${API}?action=backups&path=${encodeURIComponent(currentFile.path)}`);
            const data = await res.json();
            
            if (!data.success || data.backups.length === 0) {
                list.innerHTML = '<div style="padding:20px;color:#585b70;text-align:center;font-size:12px;">Aucun backup</div>';
                return;
            }
            
            list.innerHTML = data.backups.map(bk => `
                <div class="tpl-backup-item" onclick="TplEditor.restoreBackup('${bk.name}')">
                    <span class="bk-date">${bk.modified}</span>
                    <span class="bk-size">${formatBytes(bk.size)}</span>
                </div>
            `).join('');
        } catch (err) {
            list.innerHTML = `<div style="padding:16px;color:#f38ba8;font-size:12px;">${err.message}</div>`;
        }
    }
    
    function hideBackups() {
        backupsVisible = false;
        document.getElementById('tplBackupsPanel').classList.remove('visible');
    }
    
    async function restoreBackup(backupName) {
        if (!currentFile) return;
        if (!confirm('Restaurer ce backup ? Le fichier actuel sera sauvegardé avant.')) return;
        
        try {
            const fd = new FormData();
            fd.append('action', 'restore');
            fd.append('path', currentFile.path);
            fd.append('backup', backupName);
            
            const res = await fetch(API, { method: 'POST', body: fd });
            const data = await res.json();
            
            if (data.success) {
                originalContent = data.content;
                editor.setValue(data.content, -1);
                hasUnsaved = false;
                updateUnsavedState();
                toast('Backup restauré avec succès', 'success');
                if (previewVisible) refreshPreview();
            } else {
                toast(data.error || 'Erreur restauration', 'error');
            }
        } catch (err) {
            toast('Erreur : ' + err.message, 'error');
        }
    }
    
    // ─── Helpers ───
    function updateUnsavedState() {
        const el = document.getElementById('tplFilename');
        if (hasUnsaved) {
            el.classList.add('tpl-unsaved');
        } else {
            el.classList.remove('tpl-unsaved');
        }
    }
    
    function toast(msg, type = 'info') {
        const el = document.getElementById('tplToast');
        el.textContent = msg;
        el.className = 'tpl-toast ' + type + ' show';
        setTimeout(() => el.classList.remove('show'), 3500);
    }
    
    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' o';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' Ko';
        return (bytes / 1048576).toFixed(1) + ' Mo';
    }
    
    // ─── Startup ───
    document.addEventListener('DOMContentLoaded', init);
    
    return { openFile, save, togglePreview, showBackups, hideBackups, restoreBackup };
})();
</script>