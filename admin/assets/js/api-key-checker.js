/**
 * API Key Checker JS — IMMO LOCAL+
 * admin/assets/js/api-key-checker.js
 *
 * Ancienne position : modules/system/settings/assets/js/api-key-checker.js
 * Chargé dans : modules/system/settings/api/api-keys.php
 */

'use strict';

const ApiKeyChecker = (() => {

    const API = '/admin/api/system/settings.php';

    // ── Test de clé ───────────────────────────────────────────────────
    async function testKey(provider, keyValue) {
        const btn = document.querySelector(`[data-test-provider="${provider}"]`);
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Test…';
        }

        const statusEl = document.querySelector(`[data-status="${provider}"]`);

        try {
            const res  = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'test_api_key', provider, key: keyValue }),
            });
            const data = await res.json();

            _setStatus(statusEl, data.success ? 'valid' : 'invalid', data.message || '');

        } catch (e) {
            _setStatus(statusEl, 'error', 'Erreur réseau');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-bolt"></i> Tester';
            }
        }
    }

    // ── Sauvegarde clé ────────────────────────────────────────────────
    async function saveKey(provider, keyValue) {
        const btn = document.querySelector(`[data-save-provider="${provider}"]`);
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }

        try {
            const res  = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'save_api_key', provider, key: keyValue }),
            });
            const data = await res.json();

            if (data.success) {
                showFlash(`Clé ${provider} sauvegardée`, 'success');
                // Marquer comme sauvegardée dans l'UI
                const field = document.querySelector(`[data-key-provider="${provider}"]`);
                if (field) field.dataset.saved = '1';
                _setStatus(document.querySelector(`[data-status="${provider}"]`), 'saved', 'Clé enregistrée');
            } else {
                showFlash(data.error || 'Erreur de sauvegarde', 'error');
            }
        } catch (e) {
            showFlash('Erreur réseau', 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-floppy-disk"></i>';
            }
        }
    }

    // ── Suppression clé ───────────────────────────────────────────────
    async function deleteKey(provider) {
        if (!confirm(`Supprimer la clé ${provider} ?`)) return;

        try {
            const res  = await fetch(API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete_api_key', provider }),
            });
            const data = await res.json();

            if (data.success) {
                const field = document.querySelector(`[data-key-provider="${provider}"]`);
                if (field) { field.value = ''; field.dataset.saved = '0'; }
                _setStatus(document.querySelector(`[data-status="${provider}"]`), 'empty', '');
                showFlash(`Clé ${provider} supprimée`, 'success');
            } else {
                showFlash(data.error || 'Erreur', 'error');
            }
        } catch (e) {
            showFlash('Erreur réseau', 'error');
        }
    }

    // ── Toggle visibilité clé ─────────────────────────────────────────
    function toggleVisibility(provider) {
        const field = document.querySelector(`[data-key-provider="${provider}"]`);
        const btn   = document.querySelector(`[data-toggle-provider="${provider}"]`);
        if (!field) return;
        const isHidden = field.type === 'password';
        field.type = isHidden ? 'text' : 'password';
        if (btn) btn.innerHTML = `<i class="fas ${isHidden ? 'fa-eye-slash' : 'fa-eye'}"></i>`;
    }

    // ── Status badge ──────────────────────────────────────────────────
    function _setStatus(el, status, msg) {
        if (!el) return;
        const cfg = {
            valid:   { cls: 'badge-green', icon: 'fa-circle-check',  label: 'Valide' },
            invalid: { cls: 'badge-red',   icon: 'fa-circle-xmark',  label: 'Invalide' },
            saved:   { cls: 'badge-blue',  icon: 'fa-floppy-disk',   label: 'Sauvegardée' },
            error:   { cls: 'badge-red',   icon: 'fa-triangle-exclamation', label: 'Erreur' },
            empty:   { cls: '',            icon: 'fa-minus',          label: 'Non configurée' },
        };
        const c = cfg[status] || cfg.empty;
        el.className = `badge ${c.cls}`;
        el.innerHTML = `<i class="fas ${c.icon}"></i> ${msg || c.label}`;
    }

    // ── Flash ─────────────────────────────────────────────────────────
    function showFlash(msg, type = 'info') {
        if (typeof window.showAdminFlash === 'function') {
            window.showAdminFlash(msg, type);
            return;
        }
        const f = document.createElement('div');
        f.style.cssText = 'position:fixed;top:70px;right:20px;z-index:9999;padding:10px 16px;border-radius:8px;font-size:12px;font-weight:600;color:#fff;background:' + (type === 'success' ? '#0da271' : type === 'error' ? '#e53e3e' : '#6366f1');
        f.textContent = msg;
        document.body.appendChild(f);
        setTimeout(() => f.remove(), 3000);
    }

    // ── Bind auto sur les boutons ──────────────────────────────────────
    function init() {
        // Boutons test
        document.querySelectorAll('[data-test-provider]').forEach(btn => {
            btn.addEventListener('click', () => {
                const provider = btn.dataset.testProvider;
                const field    = document.querySelector(`[data-key-provider="${provider}"]`);
                testKey(provider, field?.value ?? '');
            });
        });

        // Boutons save
        document.querySelectorAll('[data-save-provider]').forEach(btn => {
            btn.addEventListener('click', () => {
                const provider = btn.dataset.saveProvider;
                const field    = document.querySelector(`[data-key-provider="${provider}"]`);
                saveKey(provider, field?.value ?? '');
            });
        });

        // Boutons delete
        document.querySelectorAll('[data-delete-provider]').forEach(btn => {
            btn.addEventListener('click', () => deleteKey(btn.dataset.deleteProvider));
        });

        // Boutons toggle visibility
        document.querySelectorAll('[data-toggle-provider]').forEach(btn => {
            btn.addEventListener('click', () => toggleVisibility(btn.dataset.toggleProvider));
        });

        // Marquer unsaved à la saisie
        document.querySelectorAll('[data-key-provider]').forEach(field => {
            field.addEventListener('input', () => {
                field.dataset.saved = '0';
                _setStatus(document.querySelector(`[data-status="${field.dataset.keyProvider}"]`), 'empty', 'Non sauvegardée');
            });
        });
    }

    return { init, testKey, saveKey, deleteKey, toggleVisibility };

})();

document.addEventListener('DOMContentLoaded', ApiKeyChecker.init);