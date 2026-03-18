/**
 * ══════════════════════════════════════════════════════════════
 * GUIDE LOCAL — JavaScript module complet
 * /admin/modules/content/guide-local/assets/js/guide-local.js
 *
 * Classes :
 *   GuideLocalAdmin  — gestion de la liste admin (index.php)
 *   GuideLocalEdit   — formulaire edit (edit.php)
 *   GuideLocalWidget — widget front-end public
 * ══════════════════════════════════════════════════════════════
 */

'use strict';

/* ════════════════════════════════════════════════════════
   GuideLocalAdmin
   Gère la liste admin : filtres, bulk, AJAX actions
   ════════════════════════════════════════════════════════ */
class GuideLocalAdmin {
    constructor(options = {}) {
        this.apiUrl  = options.apiUrl  || '/admin/api/content/guide-local.php';
        this.pageKey = options.pageKey || 'guide-local';
        this._bindEvents();
    }

    _bindEvents() {
        // Délégation sur les checkboxes bulk
        document.addEventListener('change', e => {
            if (e.target.classList.contains('glm-cb')) this.updateBulk();
        });
    }

    // ─── Filtres URL ───
    filterBy(key, value) {
        const url = new URL(window.location.href);
        if (!value || value === 'all') url.searchParams.delete(key);
        else url.searchParams.set(key, value);
        url.searchParams.delete('p');
        window.location.href = url.toString();
    }

    // ─── Bulk ───
    toggleAll(checked) {
        document.querySelectorAll('.glm-cb').forEach(cb => cb.checked = checked);
        this.updateBulk();
    }

    updateBulk() {
        const checked = document.querySelectorAll('.glm-cb:checked');
        const cnt   = document.getElementById('glmBulkCnt');
        const bar   = document.getElementById('glmBulkBar');
        if (cnt) cnt.textContent = checked.length;
        if (bar) bar.classList.toggle('active', checked.length > 0);
    }

    async bulkExecute() {
        const actionEl = document.getElementById('glmBulkAct');
        const action   = actionEl?.value;
        if (!action) return;
        const ids = [...document.querySelectorAll('.glm-cb:checked')].map(cb => parseInt(cb.value));
        if (!ids.length) return;
        if (action === 'delete' && !confirm(`Supprimer ${ids.length} partenaire(s) ?`)) return;

        const payload = { ids: JSON.stringify(ids) };
        if (action === 'delete')   payload.action = 'bulk_delete';
        else if (action === 'feature') payload.action = 'bulk_feature';
        else { payload.action = 'bulk_status'; payload.status = { publish: 'published', draft: 'draft' }[action] || action; }

        const d = await this._post(payload);
        d.success ? location.reload() : alert(d.error || 'Erreur');
    }

    // ─── Actions individuelles ───
    async deletePartner(id, nom) {
        if (!confirm(`Supprimer « ${nom} » du guide local ?`)) return;
        const d = await this._post({ action: 'delete', id });
        if (d.success) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) {
                row.style.cssText = 'opacity:0;transform:translateX(20px);transition:all .3s';
                setTimeout(() => row.remove(), 300);
            }
        } else { alert(d.error || 'Erreur'); }
    }

    async toggleStatus(id) {
        const d = await this._post({ action: 'toggle_status', id });
        d.success ? location.reload() : alert(d.error || 'Erreur');
    }

    async toggleFeatured(id) {
        const d = await this._post({ action: 'toggle_featured', id });
        if (d.success) {
            const btn = document.querySelector(`[data-toggle-featured="${id}"] i`);
            if (btn) btn.style.color = d.is_featured ? '#f59e0b' : '#d1d5db';
        } else { alert(d.error || 'Erreur'); }
    }

    async duplicate(id) {
        if (!confirm('Dupliquer ce partenaire en brouillon ?')) return;
        const d = await this._post({ action: 'duplicate', id });
        d.success ? location.reload() : alert(d.error || 'Erreur');
    }

    // ─── HTTP Helper ───
    async _post(data) {
        try {
            const fd = new FormData();
            for (const [k, v] of Object.entries(data)) fd.append(k, v);
            const r = await fetch(this.apiUrl, { method: 'POST', body: fd });
            return await r.json();
        } catch (e) {
            return { success: false, error: 'Erreur réseau' };
        }
    }
}

/* ════════════════════════════════════════════════════════
   GuideLocalEdit
   Gère le formulaire d'édition
   ════════════════════════════════════════════════════════ */
class GuideLocalEdit {
    constructor(options = {}) {
        this.aiUrl     = options.aiUrl || '/admin/modules/content/guide-local/ai/generate.php';
        this.slugTimer = null;
        this._init();
    }

    _init() {
        // Slug manuel
        document.getElementById('gleSlug')?.addEventListener('input', function() {
            this.dataset.manual = '1';
        });
        // Init score SEO
        this.updateSeoScore();
        // Live SEO
        ['gleMetaTitle', 'gleMetaDesc'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', () => this.updateSeoScore());
        });
    }

    // ─── Slug auto ───
    autoSlug(nom) {
        clearTimeout(this.slugTimer);
        this.slugTimer = setTimeout(() => {
            const el = document.getElementById('gleSlug');
            if (!el || el.dataset.manual) return;
            el.value = this._toSlug(nom);
        }, 350);
    }

    _toSlug(s) {
        return s.toLowerCase()
            .replace(/[àáâã]/g,'a').replace(/[èéêë]/g,'e').replace(/[ìíîï]/g,'i')
            .replace(/[òóôõ]/g,'o').replace(/[ùúûü]/g,'u').replace(/ç/g,'c').replace(/ñ/g,'n')
            .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
    }

    // ─── Score SEO ───
    updateSeoScore() {
        const titleEl = document.getElementById('gleMetaTitle');
        const descEl  = document.getElementById('gleMetaDesc');
        if (!titleEl) return;

        const tLen = titleEl.value.length;
        const dLen = descEl ? descEl.value.length : 0;

        this._updateBar('gleMetaBar', 'gleMetaHint', tLen, 40, 65, '/ 65 caractères');
        this._updateBar('gleDescBar', 'gleDescHint', dLen, 120, 165, '/ 165 caractères');
        this._renderChecklist();
    }

    _updateBar(barId, hintId, len, min, max, suffix) {
        const bar  = document.getElementById(barId);
        const hint = document.getElementById(hintId);
        if (!bar) return;
        const pct = Math.min(100, (len / max) * 100);
        bar.style.width = pct + '%';
        bar.style.background = len >= min && len <= max ? '#10b981' : len < min ? '#f59e0b' : '#ef4444';
        if (hint) hint.textContent = `${len} ${suffix}${len < min ? ' — trop court' : len > max ? ' — trop long' : ' ✓'}`;
    }

    _renderChecklist() {
        const checks = {
            'Nom renseigné'       : (document.querySelector('[name=nom]')?.value.length    || 0) > 2,
            'Catégorie choisie'   : !!document.querySelector('[name=categorie]:checked'),
            'Adresse renseignée'  : (document.querySelector('[name=adresse]')?.value.length || 0) > 5,
            'Ville renseignée'    : (document.querySelector('[name=ville]')?.value.length   || 0) > 1,
            'Secteur associé'     : !!document.querySelector('[name=secteur_id]')?.value,
            'Meta title optimisé' : (() => { const t = document.getElementById('gleMetaTitle')?.value.length || 0; return t >= 40 && t <= 65; })(),
            'Meta description OK' : (() => { const d = document.getElementById('gleMetaDesc')?.value.length  || 0; return d >= 120 && d <= 165; })(),
            'Fiche GMB liée'      : (document.querySelector('[name=gmb_url]')?.value || '').startsWith('http'),
            'Coordonnées GPS'     : (document.querySelector('[name=latitude]')?.value || '') !== '',
        };
        const total = Object.keys(checks).length;
        const done  = Object.values(checks).filter(Boolean).length;
        const score = Math.round((done / total) * 100);
        const color = score >= 75 ? '#10b981' : score >= 50 ? '#f59e0b' : '#ef4444';

        const cl = document.getElementById('gleSeoChecklist');
        if (!cl) return;
        cl.innerHTML = `
            <div style="text-align:center;margin-bottom:12px">
                <div style="font-family:var(--font-display,sans-serif);font-size:2rem;font-weight:800;color:${color};line-height:1">${score}</div>
                <div style="font-size:.62rem;color:var(--text-3,#94a3b8);text-transform:uppercase;letter-spacing:.06em;font-weight:600">Score SEO</div>
                <div style="height:4px;background:var(--surface-2,#f1f5f9);border-radius:2px;margin-top:8px;overflow:hidden">
                    <div style="width:${score}%;height:100%;background:${color};border-radius:2px;transition:width .4s"></div>
                </div>
            </div>
            ${Object.entries(checks).map(([lbl, ok]) => `
            <div style="display:flex;align-items:center;gap:7px;font-size:.74rem;padding:4px 0;color:${ok ? 'var(--text-2,#64748b)' : 'var(--text-3,#94a3b8)'}">
                <i class="fas fa-${ok ? 'check-circle' : 'circle'}" style="color:${ok ? '#10b981' : 'var(--border,#e2e8f0)'};font-size:.72rem;flex-shrink:0"></i>
                ${lbl}
            </div>`).join('')}`;
    }

    // ─── Géocodage Nominatim ───
    async geocode() {
        const addr  = document.querySelector('[name=adresse]')?.value || '';
        const ville = document.querySelector('[name=ville]')?.value   || '';
        if (!addr) { alert('Saisissez une adresse d\'abord'); return; }
        const q = encodeURIComponent(`${addr} ${ville}`.trim());
        try {
            const r = await fetch(`https://nominatim.openstreetmap.org/search?q=${q}&format=json&limit=1`);
            const d = await r.json();
            if (d && d[0]) {
                document.querySelector('[name=latitude]').value  = parseFloat(d[0].lat).toFixed(7);
                document.querySelector('[name=longitude]').value = parseFloat(d[0].lon).toFixed(7);
                this.updateSeoScore();
            } else { alert('Adresse introuvable'); }
        } catch(e) { alert('Erreur de géocodage'); }
    }

    // ─── GMB preview ───
    checkGmb(url) {
        const p = document.querySelector('.gle-gmb-preview');
        if (p) p.style.display = url.startsWith('http') ? 'flex' : 'none';
        this.updateSeoScore();
    }

    // ─── IA : générer métas ───
    async generateMeta() {
        const nom  = document.querySelector('[name=nom]')?.value;
        const ville= document.querySelector('[name=ville]')?.value || '';
        const cat  = document.querySelector('[name=categorie]:checked')?.value || 'autre';
        const desc = document.querySelector('[name=description]')?.value || '';
        if (!nom) { alert('Saisissez le nom du partenaire d\'abord'); return; }

        const btn = document.querySelector('[onclick*="generateMeta"]');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Génération…'; }

        try {
            const r = await fetch(this.aiUrl, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ action: 'meta', nom, ville, categorie: cat, description: desc }),
            });
            const d = await r.json();
            if (d.success && d.meta_title) {
                document.getElementById('gleMetaTitle').value = d.meta_title;
                document.getElementById('gleMetaDesc').value  = d.meta_desc || '';
                this.updateSeoScore();
            } else { alert(d.error || 'Erreur IA'); }
        } catch(e) { alert('Erreur réseau'); }
        finally {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-robot"></i> Générer les métas avec IA'; }
        }
    }

    // ─── IA : enrichir fiche ───
    async generateAI(id) {
        const panel = document.getElementById('gleAiPanel');
        if (panel) { panel.style.display = 'block'; panel.scrollIntoView({ behavior: 'smooth', block: 'center' }); }

        try {
            const r = await fetch(this.aiUrl, {
                method : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body   : JSON.stringify({ action: 'enrich', id }),
            });
            const d = await r.json();
            const content = document.getElementById('gleAiContent');
            if (!content) return;
            if (d.success && d.suggestions) {
                content.innerHTML = d.suggestions.map(s => `
                    <div style="padding:8px 10px;background:var(--surface-2);border:1px solid var(--border);border-radius:var(--radius,6px);margin-bottom:6px;font-size:.76rem">
                        <strong>${s.label}</strong><br>
                        <span style="color:var(--text-2)">${s.value}</span>
                        <button onclick="window.GLE.applySuggestion('${s.field}','${(s.value||'').replace(/'/g,"\\'")}')"
                                style="float:right;background:#10b981;color:#fff;border:none;border-radius:4px;padding:2px 8px;font-size:.65rem;cursor:pointer;margin-top:2px">
                            Appliquer
                        </button>
                    </div>`).join('');
            } else {
                content.innerHTML = `<p style="color:var(--red,#dc2626);font-size:.8rem">${d.error || 'Erreur'}</p>`;
            }
        } catch(e) {
            const c = document.getElementById('gleAiContent');
            if (c) c.innerHTML = '<p style="color:var(--red);font-size:.8rem">Erreur réseau</p>';
        }
    }

    applySuggestion(field, value) {
        const el = document.querySelector(`[name="${field}"]`);
        if (el) { el.value = value; this.updateSeoScore(); }
    }
}

/* ════════════════════════════════════════════════════════
   GuideLocalWidget
   Widget front-end public pour afficher le guide local
   Usage : new GuideLocalWidget('#guide-local', { secteurId: 3 })
   ════════════════════════════════════════════════════════ */
class GuideLocalWidget {
    constructor(selector, options = {}) {
        this.el         = document.querySelector(selector);
        this.apiUrl     = options.apiUrl     || '/api/guide-local';
        this.secteurId  = options.secteurId  || null;
        this.ville      = options.ville      || '';
        this.audience   = options.audience   || 'tous';
        this.perPage    = options.perPage    || 12;
        this.activecat  = 'all';
        this.partners   = [];

        if (this.el) this._load();
    }

    async _load() {
        this.el.innerHTML = this._skeleton();
        try {
            const params = new URLSearchParams({ status: 'published', limit: 50 });
            if (this.secteurId) params.set('secteur_id', this.secteurId);
            if (this.ville)     params.set('ville', this.ville);
            if (this.audience !== 'tous') params.set('audience', this.audience);

            const r = await fetch(`${this.apiUrl}?action=list&${params}`);
            const d = await r.json();
            this.partners = d.partners || [];
            this._render();
        } catch(e) {
            this.el.innerHTML = `<p style="color:#ef4444;text-align:center">Erreur de chargement</p>`;
        }
    }

    _skeleton() {
        return `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
            ${Array(6).fill(`<div style="height:140px;background:#f1f5f9;border-radius:10px;animation:glPulse 1.5s ease-in-out infinite"></div>`).join('')}
        </div>
        <style>@keyframes glPulse{0%,100%{opacity:1}50%{opacity:.5}}</style>`;
    }

    _getCats() {
        const cats = {};
        this.partners.forEach(p => {
            if (!cats[p.categorie]) cats[p.categorie] = 0;
            cats[p.categorie]++;
        });
        return cats;
    }

    _render() {
        const cats    = this._getCats();
        const visible = this.activecat === 'all' ? this.partners
                      : this.partners.filter(p => p.categorie === this.activecat);

        const catIcons = {
            ecole:'fa-school',sante:'fa-heartbeat',transport:'fa-bus',
            commerce:'fa-shopping-bag',restaurant:'fa-utensils',sport:'fa-dumbbell',
            culture:'fa-landmark',nature:'fa-tree',services:'fa-concierge-bell',
            securite:'fa-shield-alt',autre:'fa-map-pin'
        };
        const catLabels = {
            ecole:'Écoles',sante:'Santé',transport:'Transports',
            commerce:'Commerces',restaurant:'Restaurants',sport:'Sport',
            culture:'Culture',nature:'Nature',services:'Services',
            securite:'Sécurité',autre:'Autres'
        };

        this.el.innerHTML = `
            <!-- Filtres catégories -->
            <div class="gl-cats" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px">
                <button class="gl-cat-btn ${this.activecat==='all'?'active':''}" data-cat="all"
                        style="padding:5px 14px;border-radius:20px;border:1px solid ${this.activecat==='all'?'#10b981':'#e2e8f0'};
                               background:${this.activecat==='all'?'#10b981':'#fff'};color:${this.activecat==='all'?'#fff':'#64748b'};
                               font-size:.75rem;font-weight:600;cursor:pointer">
                    Tous (${this.partners.length})
                </button>
                ${Object.entries(cats).map(([cat, cnt]) => `
                <button class="gl-cat-btn ${this.activecat===cat?'active':''}" data-cat="${cat}"
                        style="padding:5px 14px;border-radius:20px;border:1px solid ${this.activecat===cat?'#10b981':'#e2e8f0'};
                               background:${this.activecat===cat?'#10b981':'#fff'};color:${this.activecat===cat?'#fff':'#64748b'};
                               font-size:.75rem;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px">
                    <i class="fas ${catIcons[cat]||'fa-map-pin'}" style="font-size:.65rem"></i>
                    ${catLabels[cat]||cat} (${cnt})
                </button>`).join('')}
            </div>

            <!-- Grille partenaires -->
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
                ${visible.map(p => this._card(p, catIcons, catLabels)).join('')}
            </div>

            ${visible.length === 0 ? `<p style="text-align:center;color:#94a3b8;padding:40px">Aucun partenaire dans cette catégorie</p>` : ''}
        `;

        // Bind cat buttons
        this.el.querySelectorAll('.gl-cat-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.activecat = btn.dataset.cat;
                this._render();
            });
        });
    }

    _card(p, catIcons, catLabels) {
        const stars = p.note > 0 ? '★'.repeat(Math.floor(p.note)) + (p.note % 1 >= 0.5 ? '½' : '') : '';
        return `
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:16px;
                    transition:all .2s;cursor:default;${p.is_featured?'border-color:#10b981;box-shadow:0 0 0 2px rgba(16,185,129,.1)':''}">
            ${p.is_featured ? `<div style="font-size:.6rem;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">⭐ Recommandé</div>` : ''}
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px">
                <div>
                    <div style="font-weight:700;font-size:.9rem;color:#1e293b;line-height:1.3">${p.nom}</div>
                    ${p.adresse ? `<div style="font-size:.72rem;color:#94a3b8;margin-top:2px;display:flex;align-items:center;gap:4px"><i class="fas fa-map-pin" style="font-size:.6rem"></i>${p.adresse}</div>` : ''}
                </div>
                <span style="padding:3px 8px;background:#f0fdf4;border-radius:20px;font-size:.62rem;font-weight:700;color:#059669;white-space:nowrap;flex-shrink:0">
                    <i class="fas ${catIcons[p.categorie]||'fa-map-pin'}" style="font-size:.55rem"></i>
                    ${catLabels[p.categorie]||p.categorie}
                </span>
            </div>
            ${p.description ? `<p style="font-size:.78rem;color:#475569;margin:0 0 10px;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">${p.description}</p>` : ''}
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px">
                ${stars ? `<span style="color:#f59e0b;font-size:.7rem">${stars} <span style="color:#64748b;font-size:.68rem">${parseFloat(p.note).toFixed(1)}</span></span>` : '<span></span>'}
                <div style="display:flex;gap:5px">
                    ${p.telephone ? `<a href="tel:${p.telephone}" style="padding:4px 10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;font-size:.68rem;color:#475569;text-decoration:none;display:flex;align-items:center;gap:4px"><i class="fas fa-phone" style="font-size:.6rem"></i>${p.telephone}</a>` : ''}
                    ${p.site_web ? `<a href="${p.site_web}" target="_blank" style="padding:4px 10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;font-size:.68rem;color:#059669;text-decoration:none;display:flex;align-items:center;gap:4px"><i class="fas fa-external-link-alt" style="font-size:.55rem"></i>Site</a>` : ''}
                    ${p.gmb_url ? `<a href="${p.gmb_url}" target="_blank" style="padding:4px 10px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:6px;font-size:.68rem;color:#2563eb;text-decoration:none;display:flex;align-items:center;gap:4px"><i class="fab fa-google" style="font-size:.6rem"></i></a>` : ''}
                </div>
            </div>
        </div>`;
    }
}

// ─── Auto-init si data-attributes présents ───
document.addEventListener('DOMContentLoaded', () => {
    // Admin list
    if (document.getElementById('glmBulkBar')) {
        window.GLM = new GuideLocalAdmin();
    }
    // Admin edit
    if (document.getElementById('gleForm')) {
        window.GLE = new GuideLocalEdit();
    }
    // Widget front
    document.querySelectorAll('[data-guide-local]').forEach(el => {
        const opts = {
            secteurId: el.dataset.secteurId || null,
            ville    : el.dataset.ville     || '',
            audience : el.dataset.audience  || 'tous',
        };
        new GuideLocalWidget(`#${el.id}`, opts);
    });
});