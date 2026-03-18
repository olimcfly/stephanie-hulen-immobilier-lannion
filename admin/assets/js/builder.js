/**
 * Builder JS — IMMO LOCAL+
 * admin/assets/js/builder.js
 *
 * Ancienne position : modules/content/pages/assets/js/builder.js
 * Chargé dans : editor.php, create.php, edit.php
 */

'use strict';

const Builder = (() => {

    // ── État ─────────────────────────────────────────────────────────
    let state = {
        mode: 'html',          // html | css | js | preview
        unsaved: false,
        saving: false,
        previewDevice: 'desktop',
        autoSaveTimer: null,
        pageId: null,
        apiUrl: '/admin/api/builder/save-content.php',
    };

    // ── Init ──────────────────────────────────────────────────────────
    function init(options = {}) {
        Object.assign(state, options);
        _bindTabs();
        _bindSave();
        _bindPreviewDevice();
        _bindAutoSave();
        _bindVariables();
        _bindShortcuts();
        _updateStatus('saved');
    }

    // ── Tabs ──────────────────────────────────────────────────────────
    function _bindTabs() {
        document.querySelectorAll('.builder-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const mode = tab.dataset.mode;
                switchTab(mode);
            });
        });
    }

    function switchTab(mode) {
        state.mode = mode;
        document.querySelectorAll('.builder-tab').forEach(t => {
            t.classList.toggle('active', t.dataset.mode === mode);
        });
        document.querySelectorAll('[data-editor]').forEach(el => {
            el.style.display = el.dataset.editor === mode ? '' : 'none';
        });
        if (mode === 'preview') _refreshPreview();
    }

    // ── Preview ───────────────────────────────────────────────────────
    function _refreshPreview() {
        const iframe = document.getElementById('builderPreview');
        if (!iframe) return;
        const html = _getEditorValue('html');
        const css  = `<style>${_getEditorValue('css')}</style>`;
        const js   = `<script>${_getEditorValue('js')}<\/script>`;
        const doc  = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(html + css + js);
        doc.close();
    }

    function _bindPreviewDevice() {
        const sel = document.getElementById('previewDevice');
        if (!sel) return;
        sel.addEventListener('change', () => {
            const iframe = document.getElementById('builderPreview');
            if (!iframe) return;
            iframe.className = 'builder-preview ' + sel.value;
            state.previewDevice = sel.value;
        });
    }

    // ── Variables ──────────────────────────────────────────────────────
    function _bindVariables() {
        document.querySelectorAll('.bp-var').forEach(v => {
            v.addEventListener('click', () => {
                insertAtCursor(_getActiveTextarea(), `{{${v.dataset.var}}}`);
            });
        });
    }

    function _getActiveTextarea() {
        return document.querySelector(`[data-editor="${state.mode}"]`);
    }

    function insertAtCursor(el, text) {
        if (!el) return;
        const start = el.selectionStart;
        const end   = el.selectionEnd;
        el.value = el.value.slice(0, start) + text + el.value.slice(end);
        el.selectionStart = el.selectionEnd = start + text.length;
        el.focus();
        _markUnsaved();
    }

    // ── Save ───────────────────────────────────────────────────────────
    function _bindSave() {
        const btn = document.getElementById('builderSave');
        if (btn) btn.addEventListener('click', save);

        // Marquer unsaved sur toute modification
        document.querySelectorAll('[data-editor]').forEach(el => {
            el.addEventListener('input', _markUnsaved);
        });
    }

    function _markUnsaved() {
        if (!state.unsaved) {
            state.unsaved = true;
            _updateStatus('unsaved');
        }
    }

    async function save() {
        if (state.saving) return;
        state.saving = true;
        _updateStatus('saving');

        const payload = {
            id:      state.pageId,
            html:    _getEditorValue('html'),
            css:     _getEditorValue('css'),
            js:      _getEditorValue('js'),
            meta:    _getMeta(),
            csrf:    document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        };

        try {
            const res  = await fetch(state.apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            if (data.success) {
                state.unsaved = false;
                _updateStatus('saved');
                _toast('Sauvegardé ✓', 'success');
                if (data.id && !state.pageId) {
                    state.pageId = data.id;
                    // Mettre à jour l'URL sans rechargement
                    const url = new URL(window.location);
                    url.searchParams.set('id', data.id);
                    history.replaceState({}, '', url);
                }
            } else {
                _updateStatus('unsaved');
                _toast(data.error || 'Erreur de sauvegarde', 'error');
            }
        } catch (e) {
            _updateStatus('unsaved');
            _toast('Erreur réseau', 'error');
        } finally {
            state.saving = false;
        }
    }

    // ── Auto-save ──────────────────────────────────────────────────────
    function _bindAutoSave() {
        document.querySelectorAll('[data-editor]').forEach(el => {
            el.addEventListener('input', () => {
                clearTimeout(state.autoSaveTimer);
                state.autoSaveTimer = setTimeout(() => {
                    if (state.unsaved && state.pageId) save();
                }, 4000);
            });
        });
    }

    // ── Raccourcis clavier ─────────────────────────────────────────────
    function _bindShortcuts() {
        document.addEventListener('keydown', e => {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                save();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                switchTab('preview');
            }
        });
    }

    // ── Helpers ────────────────────────────────────────────────────────
    function _getEditorValue(mode) {
        const el = document.querySelector(`[data-editor="${mode}"]`);
        return el ? el.value : '';
    }

    function _getMeta() {
        const meta = {};
        document.querySelectorAll('[data-meta]').forEach(el => {
            meta[el.dataset.meta] = el.value;
        });
        return meta;
    }

    function _updateStatus(status) {
        const dot   = document.querySelector('.builder-status-dot');
        const label = document.querySelector('.builder-status-label');
        if (!dot) return;
        dot.className  = 'builder-status-dot ' + status;
        if (label) {
            label.textContent = { saved: 'Sauvegardé', unsaved: 'Modifications non sauvegardées', saving: 'Sauvegarde…' }[status] || '';
        }
    }

    function _toast(msg, type = 'info', duration = 2500) {
        let t = document.querySelector('.builder-toast');
        if (!t) {
            t = document.createElement('div');
            t.className = 'builder-toast';
            document.body.appendChild(t);
        }
        t.className = `builder-toast ${type}`;
        t.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check' : type === 'error' ? 'fa-xmark' : 'fa-info'}"></i> ${msg}`;
        t.style.opacity = '1';
        clearTimeout(t._timer);
        t._timer = setTimeout(() => { t.style.opacity = '0'; }, duration);
    }

    // ── API publique ───────────────────────────────────────────────────
    return { init, save, switchTab, insertAtCursor, _toast };

})();

// Auto-init si attribut data-builder-init présent
document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('builderRoot');
    if (root) {
        Builder.init({
            pageId:  root.dataset.pageId  || null,
            apiUrl:  root.dataset.apiUrl  || '/admin/api/builder/save-content.php',
        });
    }
});