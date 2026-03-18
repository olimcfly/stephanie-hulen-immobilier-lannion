/**
 * ADMIN_UI — Composants UI partagés
 * Modal de confirmation + Toast notifications
 * Utilisé par BIM (properties) et PGM (pages)
 */
const ADMIN_UI = {

    // ── Modal de confirmation ────────────────────────────────
    modal({ icon, iconBg, iconColor, title, msg, confirmLabel, confirmColor, onConfirm }) {
        let el = document.getElementById('adminUiModal');
        if (!el) {
            el = document.createElement('div');
            el.id = 'adminUiModal';
            el.innerHTML = `
<div onclick="ADMIN_UI.modalClose()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px);"></div>
<div id="adminUiModalBox" style="position:relative;z-index:1;background:var(--surface,#fff);border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:420px;margin:16px;overflow:hidden;transform:scale(.94) translateY(8px);transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .15s;opacity:0;">
    <div id="adminUiModalHeader" style="padding:20px 22px 16px;display:flex;align-items:flex-start;gap:14px;">
        <div id="adminUiModalIcon" style="width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;"></div>
        <div style="flex:1;min-width:0;">
            <div id="adminUiModalTitle" style="font-size:.95rem;font-weight:700;color:var(--text,#111827);margin-bottom:5px;"></div>
            <div id="adminUiModalMsg"   style="font-size:.82rem;color:var(--text-2,#6b7280);line-height:1.5;"></div>
        </div>
    </div>
    <div style="display:flex;gap:8px;justify-content:flex-end;padding:12px 20px 18px;border-top:1px solid var(--border,#f3f4f6);">
        <button onclick="ADMIN_UI.modalClose()" class="adminui-btn-cancel">Annuler</button>
        <button id="adminUiModalConfirm" class="adminui-btn-confirm"></button>
    </div>
</div>`;
            el.style.cssText = 'display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;';
            document.body.appendChild(el);

            // Styles inline partagés
            const style = document.createElement('style');
            style.textContent = `
.adminui-btn-cancel{padding:9px 20px;border-radius:10px;border:1px solid var(--border,#e5e7eb);background:var(--surface,#fff);color:var(--text,#374151);font-size:.83rem;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s}
.adminui-btn-cancel:hover{border-color:#6366f1;color:#6366f1}
.adminui-btn-confirm{padding:9px 20px;border-radius:10px;border:none;font-size:.83rem;font-weight:700;cursor:pointer;font-family:inherit;color:#fff;transition:filter .15s}
.adminui-btn-confirm:hover{filter:brightness(.88)}`;
            document.head.appendChild(style);
        }

        document.getElementById('adminUiModalIcon').innerHTML      = icon;
        document.getElementById('adminUiModalIcon').style.background  = iconBg;
        document.getElementById('adminUiModalIcon').style.color       = iconColor;
        document.getElementById('adminUiModalHeader').style.background = iconBg + '33';
        document.getElementById('adminUiModalTitle').textContent    = title;
        document.getElementById('adminUiModalMsg').innerHTML        = msg;

        const btn = document.getElementById('adminUiModalConfirm');
        btn.textContent      = confirmLabel || 'Confirmer';
        btn.style.background = confirmColor || '#6366f1';
        btn.onclick = () => { this.modalClose(); if (onConfirm) onConfirm(); };

        el.style.display = 'flex';
        requestAnimationFrame(() => {
            const box = document.getElementById('adminUiModalBox');
            box.style.opacity = '1';
            box.style.transform = 'scale(1) translateY(0)';
        });
        document.addEventListener('keydown', this._escHandler);
    },

    modalClose() {
        const el  = document.getElementById('adminUiModal');
        const box = document.getElementById('adminUiModalBox');
        if (!el || !box) return;
        box.style.opacity = '0';
        box.style.transform = 'scale(.94) translateY(8px)';
        setTimeout(() => { el.style.display = 'none'; }, 160);
        document.removeEventListener('keydown', this._escHandler);
    },

    _escHandler(e) { if (e.key === 'Escape') ADMIN_UI.modalClose(); },

    // ── Toast notifications ──────────────────────────────────
    toast(msg, type = 'success') {
        const colors = { success: '#059669', error: '#dc2626', info: '#3b82f6', warning: '#d97706' };
        const icons  = { success: '✓', error: '✕', info: 'ℹ', warning: '!' };
        const t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:10000;background:var(--surface,#fff);border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:10px;font-size:.83rem;font-weight:600;color:var(--text,#111827);box-shadow:0 8px 24px rgba(0,0,0,.12);transform:translateY(20px);opacity:0;transition:all .25s;max-width:320px;';
        t.innerHTML = `<span style="width:22px;height:22px;border-radius:50%;background:${colors[type]}22;color:${colors[type]};display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;flex-shrink:0">${icons[type]}</span>${msg}`;
        document.body.appendChild(t);
        requestAnimationFrame(() => { t.style.opacity = '1'; t.style.transform = 'translateY(0)'; });
        setTimeout(() => {
            t.style.opacity = '0';
            t.style.transform = 'translateY(10px)';
            setTimeout(() => t.remove(), 250);
        }, 3500);
    },

    // ── Helpers ──────────────────────────────────────────────
    buildPageUrl(base, filters) {
        const url = new URL(window.location.href);
        url.searchParams.set('page', base);
        url.searchParams.delete('p');
        Object.entries(filters).forEach(([k, v]) => {
            v && v !== 'all' ? url.searchParams.set(k, v) : url.searchParams.delete(k);
        });
        return url.toString();
    },

    filterBy(key, value) {
        const url = new URL(window.location.href);
        value === 'all' ? url.searchParams.delete(key) : url.searchParams.set(key, value);
        url.searchParams.delete('p');
        window.location.href = url.toString();
    },

    removeRow(id, delay = 300) {
        document.querySelectorAll(`[data-id="${id}"]`).forEach(el => {
            el.style.cssText = 'opacity:0;transform:scale(.95);transition:all .3s';
            setTimeout(() => el.remove(), delay);
        });
    }
};