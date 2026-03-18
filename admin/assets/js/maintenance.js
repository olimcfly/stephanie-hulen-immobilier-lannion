/**
 * Maintenance JS — IMMO LOCAL+
 * admin/assets/js/maintenance.js
 *
 * Ancienne position : modules/system/maintenance/assets/js/maintenance.js
 * Chargé dans : modules/system/maintenance/index.php
 */

'use strict';

const MaintenanceModule = (() => {

    const API = '/admin/api/system/maintenance-save.php';

    // ── Toggle on/off ─────────────────────────────────────────────────
    function bindToggle() {
        const toggle = document.getElementById('maintenanceToggle');
        if (!toggle) return;

        toggle.addEventListener('change', async function () {
            const isActive = this.checked ? 1 : 0;
            const wrap     = document.querySelector('.maintenance-toggle-wrap');
            const banner   = document.querySelector('.maintenance-status-banner');

            try {
                const res  = await fetch(API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'toggle', is_active: isActive }),
                });
                const data = await res.json();

                if (data.success) {
                    // UI
                    wrap?.classList.toggle('is-active', !!isActive);
                    if (banner) {
                        banner.classList.remove('active', 'inactive', 'visible');
                        banner.classList.add(isActive ? 'active' : 'inactive', 'visible');
                        banner.innerHTML = isActive
                            ? '<i class="fas fa-triangle-exclamation"></i> Site en maintenance — visiteurs bloqués'
                            : '<i class="fas fa-circle-check"></i> Site en ligne — accessible normalement';
                    }
                    showFlash(isActive ? 'Mode maintenance activé' : 'Mode maintenance désactivé', isActive ? 'warn' : 'success');
                } else {
                    this.checked = !this.checked; // rollback
                    showFlash('Erreur lors du changement', 'error');
                }
            } catch (e) {
                this.checked = !this.checked;
                showFlash('Erreur réseau', 'error');
            }
        });
    }

    // ── Sauvegarde paramètres ─────────────────────────────────────────
    function bindSave() {
        const btn = document.getElementById('maintenanceSaveBtn');
        if (!btn) return;

        btn.addEventListener('click', async () => {
            const message     = document.getElementById('maintenanceMessage')?.value ?? '';
            const allowed_ips = document.getElementById('maintenanceIps')?.value ?? '';
            const end_date    = document.getElementById('maintenanceEndDate')?.value ?? '';

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sauvegarde…';

            try {
                const res  = await fetch(API, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'save', message, allowed_ips, end_date }),
                });
                const data = await res.json();
                showFlash(data.success ? 'Paramètres sauvegardés' : (data.error || 'Erreur'), data.success ? 'success' : 'error');
            } catch (e) {
                showFlash('Erreur réseau', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Sauvegarder';
            }
        });
    }

    // ── Gestion IPs ───────────────────────────────────────────────────
    function bindIpManager() {
        const addBtn = document.getElementById('addIpBtn');
        const list   = document.getElementById('ipList');
        if (!addBtn || !list) return;

        addBtn.addEventListener('click', () => {
            const input = document.getElementById('newIpInput');
            const ip    = input?.value.trim();
            if (!ip) return;

            const item = document.createElement('div');
            item.className = 'maintenance-ip-item';
            item.innerHTML = `
                <i class="fas fa-circle-check"></i>
                <span>${escHtml(ip)}</span>
                <button class="maintenance-ip-remove" onclick="this.closest('.maintenance-ip-item').remove(); MaintenanceModule.syncIps()">
                    <i class="fas fa-xmark"></i>
                </button>`;
            list.appendChild(item);
            if (input) input.value = '';
            syncIps();
        });
    }

    function syncIps() {
        const field = document.getElementById('maintenanceIps');
        if (!field) return;
        const ips = [...document.querySelectorAll('.maintenance-ip-item span')].map(s => s.textContent.trim());
        field.value = ips.join('\n');
    }

    // ── Countdown ─────────────────────────────────────────────────────
    function initCountdown() {
        const endInput = document.getElementById('maintenanceEndDate');
        if (!endInput) return;

        function update() {
            const val = endInput.value;
            if (!val) return;
            const diff = new Date(val) - new Date();
            const el   = document.getElementById('countdownDisplay');
            if (!el) return;
            if (diff <= 0) { el.textContent = 'Terminé'; return; }
            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            el.textContent = `${h}h ${m}min`;
        }

        update();
        setInterval(update, 60000);
        endInput.addEventListener('change', update);
    }

    // ── Flash message ─────────────────────────────────────────────────
    function showFlash(msg, type = 'info') {
        if (typeof window.showAdminFlash === 'function') {
            window.showAdminFlash(msg, type);
            return;
        }
        // Fallback natif
        const f = document.createElement('div');
        f.className = `mod-flash mod-flash-${type}`;
        f.style.cssText = 'position:fixed;top:70px;right:20px;z-index:9999;padding:10px 16px;border-radius:8px;font-size:12px;font-weight:600';
        f.textContent = msg;
        document.body.appendChild(f);
        setTimeout(() => f.remove(), 3000);
    }

    function escHtml(str) {
        return str.replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    }

    // ── Init ──────────────────────────────────────────────────────────
    function init() {
        bindToggle();
        bindSave();
        bindIpManager();
        initCountdown();
    }

    return { init, syncIps, showFlash };

})();

document.addEventListener('DOMContentLoaded', MaintenanceModule.init);