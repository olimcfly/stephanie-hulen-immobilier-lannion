/**
 * admin-components.js — Bibliothèque JS globale admin
 * /admin/assets/js/admin-components.js
 * 
 * Chargé UNE FOIS dans dashboard.php via <script src>.
 * Fournit toutes les fonctions réutilisées dans les modules.
 * 
 * DÉPENDANCES : aucune (vanilla JS ES6+)
 * REQUIS : admin-components.css chargé en parallèle
 */

/* ═══════════════════════════════════════════════════════════
   1. NOTIFICATIONS TOAST
   ═══════════════════════════════════════════════════════════ */

/**
 * Affiche une notification toast en haut à droite.
 * @param {string} msg   - Message à afficher
 * @param {string} type  - 'success' | 'error' | 'info' | 'warning'
 * @param {number} delay - Durée affichage en ms (défaut 2500)
 * 
 * Usage : showNotif('Lead ajouté !', 'success');
 */
function showNotif(msg, type = 'info', delay = 2500) {
    const colors = {
        success: 'var(--green, #22c55e)',
        error:   'var(--red, #ef4444)',
        warning: 'var(--amber, #f59e0b)',
        info:    'var(--accent, #6366f1)'
    };
    const icons = {
        success: 'fa-check-circle',
        error:   'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info:    'fa-info-circle'
    };

    const el = document.createElement('div');
    el.className = 'mod-toast';
    el.style.cssText = `
        position:fixed; top:20px; right:20px; z-index:99999;
        padding:14px 20px; border-radius:var(--radius, 8px);
        background:${colors[type] || colors.info}; color:#fff;
        font-size:.85rem; font-weight:500; font-family:var(--font, inherit);
        box-shadow:var(--shadow-lg, 0 10px 30px rgba(0,0,0,.15));
        display:flex; align-items:center; gap:8px;
        transform:translateX(120%); transition:transform .3s ease, opacity .3s ease;
        max-width:400px; pointer-events:auto;
    `;
    el.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i> ${_escHtml(msg)}`;
    document.body.appendChild(el);

    // Slide in
    requestAnimationFrame(() => { el.style.transform = 'translateX(0)'; });

    // Slide out + remove
    setTimeout(() => {
        el.style.transform = 'translateX(120%)';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 300);
    }, delay);
}


/* ═══════════════════════════════════════════════════════════
   2. MODALES (mod-overlay)
   ═══════════════════════════════════════════════════════════ */

/**
 * Ouvre une modale par son ID.
 * @param {string} id - ID de l'élément .mod-overlay
 * 
 * Usage : openModal('leadModal');
 */
function openModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.add('show');
        document.body.style.overflow = 'hidden';
        el.querySelector('input:not([type=hidden]), textarea, select')?.focus();
    }
}

/**
 * Ferme une modale par son ID.
 * @param {string} id - ID de l'élément .mod-overlay
 */
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) {
        el.classList.remove('show');
        document.body.style.overflow = '';
    }
}

/**
 * Ferme TOUTES les modales ouvertes.
 */
function closeAllModals() {
    document.querySelectorAll('.mod-overlay.show').forEach(m => {
        m.classList.remove('show');
    });
    document.body.style.overflow = '';
}

// Auto-bind : clic sur overlay → ferme, Escape → ferme tout
document.addEventListener('DOMContentLoaded', () => {
    // Click outside modal content → close
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('mod-overlay') && e.target.classList.contains('show')) {
            e.target.classList.remove('show');
            document.body.style.overflow = '';
        }
    });

    // Escape key → close all
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAllModals();
    });

    // Auto-bind .mod-modal-close buttons
    document.querySelectorAll('.mod-modal-close').forEach(btn => {
        if (!btn.dataset.bound) {
            btn.addEventListener('click', () => {
                const overlay = btn.closest('.mod-overlay');
                if (overlay) {
                    overlay.classList.remove('show');
                    document.body.style.overflow = '';
                }
            });
            btn.dataset.bound = '1';
        }
    });
});


/* ═══════════════════════════════════════════════════════════
   3. CONFIRM DIALOG
   ═══════════════════════════════════════════════════════════ */

/**
 * Affiche un dialogue de confirmation et retourne une Promise.
 * Utilise l'overlay #confirmDialog s'il existe, sinon en crée un.
 * 
 * @param {Object} opts
 * @param {string} opts.title   - Titre (défaut: "Confirmer")
 * @param {string} opts.message - Message HTML
 * @param {string} opts.confirmText  - Texte bouton (défaut: "Supprimer")
 * @param {string} opts.confirmClass - Classe bouton (défaut: style danger)
 * @param {string} opts.icon    - Icône FontAwesome (défaut: fa-trash-alt)
 * @returns {Promise<boolean>}
 * 
 * Usage :
 *   if (await confirmDialog({ title: 'Supprimer ?', message: 'Irréversible.' })) {
 *       // delete...
 *   }
 */
function confirmDialog(opts = {}) {
    return new Promise((resolve) => {
        const title = opts.title || 'Confirmer';
        const message = opts.message || 'Êtes-vous sûr ?';
        const confirmText = opts.confirmText || 'Supprimer';
        const icon = opts.icon || 'fa-trash-alt';

        // Build or reuse overlay
        let overlay = document.getElementById('_modConfirm');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = '_modConfirm';
            overlay.className = 'mod-overlay';
            overlay.innerHTML = `
                <div class="mod-modal" style="max-width:420px">
                    <div class="mod-modal-body" style="text-align:center;padding:28px">
                        <div id="_mcIcon" style="width:50px;height:50px;border-radius:50%;background:var(--red-bg);display:flex;align-items:center;justify-content:center;margin:0 auto 14px;color:var(--red);font-size:20px"></div>
                        <h3 id="_mcTitle" style="font-size:1.05rem;font-weight:600;color:var(--text);margin-bottom:6px"></h3>
                        <p id="_mcMsg" class="mod-text-sm mod-text-muted" style="margin-bottom:20px"></p>
                        <div class="mod-flex mod-gap" style="justify-content:center">
                            <button class="mod-btn mod-btn-secondary" id="_mcCancel">Annuler</button>
                            <button class="mod-btn" id="_mcOk" style="background:var(--red);color:#fff;border-color:var(--red)"></button>
                        </div>
                    </div>
                </div>`;
            document.body.appendChild(overlay);
        }

        overlay.querySelector('#_mcIcon').innerHTML = `<i class="fas ${icon}"></i>`;
        overlay.querySelector('#_mcTitle').textContent = title;
        overlay.querySelector('#_mcMsg').innerHTML = message;
        overlay.querySelector('#_mcOk').innerHTML = `<i class="fas ${icon}"></i> ${_escHtml(confirmText)}`;

        overlay.classList.add('show');
        document.body.style.overflow = 'hidden';

        const cleanup = (result) => {
            overlay.classList.remove('show');
            document.body.style.overflow = '';
            resolve(result);
        };

        overlay.querySelector('#_mcCancel').onclick = () => cleanup(false);
        overlay.querySelector('#_mcOk').onclick = () => cleanup(true);
        overlay.onclick = (e) => { if (e.target === overlay) cleanup(false); };
    });
}


/* ═══════════════════════════════════════════════════════════
   4. AJAX HELPERS
   ═══════════════════════════════════════════════════════════ */

/**
 * POST FormData vers une URL, retourne JSON.
 * Gère automatiquement les erreurs réseau.
 * 
 * @param {string} url
 * @param {Object|FormData} data
 * @returns {Promise<Object>}
 * 
 * Usage :
 *   const result = await postJSON('/admin/modules/crm/api.php', { action: 'delete', id: 5 });
 */
async function postJSON(url, data = {}) {
    try {
        let body;
        if (data instanceof FormData) {
            body = data;
        } else {
            body = new FormData();
            for (const [k, v] of Object.entries(data)) body.append(k, v);
        }

        const resp = await fetch(url, { method: 'POST', body });

        if (!resp.ok) {
            throw new Error(`HTTP ${resp.status}`);
        }

        const json = await resp.json();
        return json;
    } catch (err) {
        console.error('postJSON error:', err);
        showNotif('Erreur de connexion au serveur', 'error');
        return { success: false, error: err.message };
    }
}

/**
 * GET JSON depuis une URL.
 * @param {string} url
 * @returns {Promise<Object>}
 */
async function getJSON(url) {
    try {
        const resp = await fetch(url);
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
        return await resp.json();
    } catch (err) {
        console.error('getJSON error:', err);
        showNotif('Erreur de connexion', 'error');
        return { success: false, error: err.message };
    }
}


/* ═══════════════════════════════════════════════════════════
   5. FORM HELPERS
   ═══════════════════════════════════════════════════════════ */

/**
 * Reset un formulaire et vide tous les champs hidden sauf CSRF.
 * @param {string|HTMLFormElement} form - ID ou élément
 */
function resetForm(form) {
    const el = typeof form === 'string' ? document.getElementById(form) : form;
    if (!el) return;
    el.reset();
    el.querySelectorAll('input[type=hidden]').forEach(h => {
        if (h.name !== 'csrf_token' && h.name !== '_token') h.value = '';
    });
}

/**
 * Populate un formulaire depuis un objet.
 * @param {string|HTMLFormElement} form
 * @param {Object} data
 */
function populateForm(form, data) {
    const el = typeof form === 'string' ? document.getElementById(form) : form;
    if (!el || !data) return;
    for (const [key, val] of Object.entries(data)) {
        const input = el.querySelector(`[name="${key}"], #${key}`);
        if (input) input.value = val ?? '';
    }
}

/**
 * Désactive/active un bouton submit avec spinner.
 * @param {HTMLButtonElement} btn
 * @param {boolean} loading
 */
function toggleBtnLoading(btn, loading) {
    if (!btn) return;
    btn.disabled = loading;
    if (loading) {
        btn.dataset.origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    } else if (btn.dataset.origHtml) {
        btn.innerHTML = btn.dataset.origHtml;
    }
}


/* ═══════════════════════════════════════════════════════════
   6. TABLE HELPERS (tri, pagination)
   ═══════════════════════════════════════════════════════════ */

/**
 * Génère l'URL de tri pour une colonne.
 * Conserve tous les paramètres GET actuels.
 * 
 * @param {string} col - Nom de la colonne
 * @returns {string} URL avec ?sort=col&dir=asc|desc
 * 
 * Usage dans PHP : onclick="location.href=sortUrl('title')"
 */
function sortUrl(col) {
    const url = new URL(window.location);
    const currentSort = url.searchParams.get('sort');
    const currentDir = url.searchParams.get('dir') || 'asc';

    url.searchParams.set('sort', col);
    url.searchParams.set('dir', (currentSort === col && currentDir === 'asc') ? 'desc' : 'asc');
    url.searchParams.delete('pg'); // reset pagination on sort
    return url.toString();
}

/**
 * Retourne l'icône FontAwesome pour une colonne de tri.
 * @param {string} col
 * @returns {string} 'fa-sort' | 'fa-sort-up' | 'fa-sort-down'
 */
function sortIcon(col) {
    const url = new URL(window.location);
    const currentSort = url.searchParams.get('sort');
    const currentDir = url.searchParams.get('dir') || 'asc';

    if (currentSort !== col) return 'fa-sort';
    return currentDir === 'asc' ? 'fa-sort-up' : 'fa-sort-down';
}

/**
 * Toggle checkbox "Select All" dans un tableau.
 * @param {HTMLInputElement} master - Checkbox header
 * @param {string} selector - Sélecteur CSS des checkboxes enfants
 */
function toggleSelectAll(master, selector = '.row-checkbox') {
    document.querySelectorAll(selector).forEach(cb => {
        cb.checked = master.checked;
    });
}


/* ═══════════════════════════════════════════════════════════
   7. URL & NAVIGATION
   ═══════════════════════════════════════════════════════════ */

/**
 * Met à jour un paramètre GET sans recharger.
 * @param {string} key
 * @param {string} value
 */
function setUrlParam(key, value) {
    const url = new URL(window.location);
    if (value === null || value === '') {
        url.searchParams.delete(key);
    } else {
        url.searchParams.set(key, value);
    }
    window.history.replaceState({}, '', url);
}

/**
 * Recharge la page avec un paramètre modifié.
 * @param {string} key
 * @param {string} value
 */
function reloadWith(key, value) {
    const url = new URL(window.location);
    url.searchParams.set(key, value);
    window.location.href = url.toString();
}


/* ═══════════════════════════════════════════════════════════
   8. FORMATAGE
   ═══════════════════════════════════════════════════════════ */

/**
 * Formate un nombre en format FR (espace milliers, virgule déc).
 * @param {number} n
 * @param {number} decimals
 * @returns {string}
 */
function fmtNumber(n, decimals = 0) {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(n || 0);
}

/**
 * Formate un prix en euros.
 * @param {number} n
 * @returns {string} "1 234 €" ou "1 234 €/mois"
 */
function fmtPrice(n, perMonth = false) {
    return fmtNumber(n) + ' €' + (perMonth ? '/mois' : '');
}

/**
 * Formate une date ISO en "dd/mm/yyyy".
 * @param {string} iso
 * @returns {string}
 */
function fmtDate(iso) {
    if (!iso) return '';
    const d = new Date(iso);
    return d.toLocaleDateString('fr-FR');
}

/**
 * Formate une date en "il y a X".
 * @param {string} iso
 * @returns {string}
 */
function timeAgo(iso) {
    if (!iso) return '';
    const diff = (Date.now() - new Date(iso).getTime()) / 1000;
    if (diff < 60) return "À l'instant";
    if (diff < 3600) return Math.floor(diff / 60) + ' min';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    if (diff < 172800) return 'Hier';
    if (diff < 604800) return Math.floor(diff / 86400) + 'j';
    return fmtDate(iso);
}


/* ═══════════════════════════════════════════════════════════
   9. DRAG & DROP (réutilisable)
   ═══════════════════════════════════════════════════════════ */

/**
 * Initialise le drag & drop sur des éléments.
 * 
 * @param {Object} opts
 * @param {string} opts.draggable  - Sélecteur des éléments draggables
 * @param {string} opts.dropzone   - Sélecteur des zones de drop
 * @param {string} opts.dataAttr   - Attribut data- contenant l'ID (défaut: 'data-id')
 * @param {string} opts.zoneAttr   - Attribut data- de la zone (défaut: 'data-stage-id')
 * @param {string} opts.dragClass  - Classe ajoutée pendant le drag (défaut: 'dragging')
 * @param {string} opts.overClass  - Classe ajoutée sur la zone (défaut: 'drag-over')
 * @param {Function} opts.onDrop   - Callback(itemId, zoneId, element)
 */
function initDragDrop(opts = {}) {
    const {
        draggable = '[draggable=true]',
        dropzone = '[data-drop-zone]',
        dataAttr = 'data-id',
        zoneAttr = 'data-stage-id',
        dragClass = 'dragging',
        overClass = 'drag-over',
        onDrop = null
    } = opts;

    let draggedEl = null;

    document.querySelectorAll(draggable).forEach(el => {
        el.addEventListener('dragstart', (e) => {
            draggedEl = el;
            el.classList.add(dragClass);
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', el.getAttribute(dataAttr));
        });
        el.addEventListener('dragend', () => {
            el.classList.remove(dragClass);
            document.querySelectorAll(`.${overClass}`).forEach(z => z.classList.remove(overClass));
        });
    });

    document.querySelectorAll(dropzone).forEach(zone => {
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add(overClass);
        });
        zone.addEventListener('dragleave', () => {
            zone.classList.remove(overClass);
        });
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove(overClass);
            if (!draggedEl) return;

            const itemId = e.dataTransfer.getData('text/plain');
            const zoneId = zone.getAttribute(zoneAttr);

            // Move element visually
            const empty = zone.querySelector('.mod-empty');
            if (empty) empty.remove();
            zone.appendChild(draggedEl);

            // Callback
            if (typeof onDrop === 'function') {
                onDrop(itemId, zoneId, draggedEl);
            }
        });
    });
}


/* ═══════════════════════════════════════════════════════════
   10. DEBOUNCE & THROTTLE
   ═══════════════════════════════════════════════════════════ */

/**
 * Debounce — retarde l'exécution jusqu'à la fin des appels.
 * @param {Function} fn
 * @param {number} ms
 * @returns {Function}
 * 
 * Usage : input.addEventListener('keyup', debounce(filterLeads, 300));
 */
function debounce(fn, ms = 300) {
    let timer;
    return function(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), ms);
    };
}

/**
 * Throttle — exécute au max 1 fois par intervalle.
 * @param {Function} fn
 * @param {number} ms
 * @returns {Function}
 */
function throttle(fn, ms = 200) {
    let last = 0;
    return function(...args) {
        const now = Date.now();
        if (now - last >= ms) {
            last = now;
            fn.apply(this, args);
        }
    };
}


/* ═══════════════════════════════════════════════════════════
   11. CLIPBOARD
   ═══════════════════════════════════════════════════════════ */

/**
 * Copie du texte dans le presse-papier.
 * @param {string} text
 * @param {string} successMsg - Message notification (optionnel)
 */
async function copyToClipboard(text, successMsg = 'Copié !') {
    try {
        await navigator.clipboard.writeText(text);
        showNotif(successMsg, 'success', 1500);
    } catch {
        // Fallback
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;opacity:0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
        showNotif(successMsg, 'success', 1500);
    }
}


/* ═══════════════════════════════════════════════════════════
   12. EXPORT CSV
   ═══════════════════════════════════════════════════════════ */

/**
 * Exporte un tableau HTML en CSV.
 * @param {string} tableSelector - Sélecteur CSS du <table>
 * @param {string} filename - Nom du fichier
 */
function exportTableCSV(tableSelector, filename = 'export.csv') {
    const table = document.querySelector(tableSelector);
    if (!table) return;

    const rows = [];
    table.querySelectorAll('tr').forEach(tr => {
        const cols = [];
        tr.querySelectorAll('th, td').forEach(td => {
            let text = td.textContent.trim().replace(/"/g, '""');
            cols.push(`"${text}"`);
        });
        rows.push(cols.join(';'));
    });

    const bom = '\uFEFF'; // UTF-8 BOM for Excel FR
    const blob = new Blob([bom + rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    a.click();
}


/* ═══════════════════════════════════════════════════════════
   INTERNAL HELPERS
   ═══════════════════════════════════════════════════════════ */

function _escHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}