/**
 * ══════════════════════════════════════════════════════════════════════
 * MODULE ADS-LAUNCH — ads-launch.js  v2.0
 * /admin/modules/ads-launch/assets/js/ads-launch.js
 * Étend l'objet ADM embarqué dans index.php
 * ══════════════════════════════════════════════════════════════════════
 */

(function () {
    'use strict';

    // ══════════════════════════════════════════════════════════════════
    // ÉTAT GLOBAL
    // ══════════════════════════════════════════════════════════════════
    const State = {
        accountId:    null,
        campaigns:    [],
        audiences:    [],
        analytics:    null,
        checklist:    [],
        prerequisites:[],
        loading:      {},          // { [key]: bool }
    };

    // ══════════════════════════════════════════════════════════════════
    // HTTP — wrapper fetch centralisé
    // ══════════════════════════════════════════════════════════════════
    const Http = {
        async post(action, payload = {}) {
            const fd = new FormData();
            fd.append('action', action);
            fd.append('csrf_token', window.ADM?.csrfToken ?? '');
            for (const [k, v] of Object.entries(payload)) {
                fd.append(k, typeof v === 'object' ? JSON.stringify(v) : v);
            }
            const res = await fetch(window.ADM?.apiUrl ?? '/admin/modules/ads-launch/api.php', {
                method: 'POST',
                body:   fd,
            });
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.error ?? 'Erreur serveur');
            return json.data ?? null;
        },
    };

    // ══════════════════════════════════════════════════════════════════
    // LOADING STATE — désactive les boutons pendant les requêtes
    // ══════════════════════════════════════════════════════════════════
    const Loading = {
        set(key, state) {
            State.loading[key] = state;
            const el = document.querySelector(`[data-loading="${key}"]`);
            if (!el) return;
            el.disabled = state;
            el._origHTML = el._origHTML ?? el.innerHTML;
            el.innerHTML = state
                ? '<i class="fas fa-spinner fa-spin"></i> Chargement…'
                : el._origHTML;
        },
    };

    // ══════════════════════════════════════════════════════════════════
    // ACCOUNT MANAGER
    // ══════════════════════════════════════════════════════════════════
    const AccountManager = {

        async onChange(id) {
            State.accountId = id || null;
            if (!id) return;
            try { sessionStorage.setItem('adm_account', id); } catch (e) {}

            // Charger en parallèle
            await Promise.allSettled([
                ChecklistManager.load(id),
                PrereqManager.load(id),
                AudienceManager.load(id),
                CampaignManager.load(id),
            ]);
        },

        restore() {
            try {
                const saved = sessionStorage.getItem('adm_account');
                const sel   = document.getElementById('admAccountSelect');
                if (saved && sel) {
                    sel.value = saved;
                    if (sel.value) this.onChange(saved);
                }
            } catch (e) {}
        },
    };

    // ══════════════════════════════════════════════════════════════════
    // CHECKLIST MANAGER
    // ══════════════════════════════════════════════════════════════════
    const ChecklistManager = {

        async load(accountId) {
            try {
                const data = await Http.post('get_checklist', { account_id: accountId });
                if (!Array.isArray(data)) return;
                State.checklist = data;
                this.render(data);
            } catch (e) { /* silencieux */ }
        },

        render(items) {
            let done  = 0;
            items.forEach(item => {
                const row   = document.querySelector(`.adm-check-row[data-key="${item.key}"]`);
                const badge = document.querySelector(`.adm-check-badge[data-key="${item.key}"]`);
                const icon  = document.querySelector(`.adm-check-icon[data-key="${item.key}"]`);
                if (item.done) done++;
                if (row)   row.classList.toggle('done', item.done);
                if (icon)  {
                    icon.className = `adm-check-icon ${item.done ? 'done' : 'todo'}`;
                    icon.innerHTML = `<i class="fas fa-${item.done ? 'check' : 'circle'}"></i>`;
                }
                if (badge) {
                    badge.className   = `adm-badge-status adm-badge-${item.done ? 'done' : 'todo'}`;
                    badge.textContent = item.done ? 'Fait' : 'À faire';
                }
            });
            const total = items.length;
            const pct   = total > 0 ? Math.round(done / total * 100) : 0;
            const bar   = document.getElementById('adm-global-progress');
            const bdg   = document.getElementById('adm-checklist-badge');
            if (bar) { bar.style.width = pct + '%'; bar.classList.toggle('complete', pct === 100); }
            if (bdg) { bdg.textContent = `${done}/${total} étapes`; bdg.className = `adm-badge-status adm-badge-${pct === 100 ? 'done' : 'todo'}`; }
        },

        async saveStep(key, done) {
            if (!State.accountId) return;
            const updated = State.checklist.map(s => s.key === key ? { ...s, done } : s);
            State.checklist = updated;
            this.render(updated);
            try {
                await Http.post('save_checklist', {
                    account_id: State.accountId,
                    steps:      updated,
                });
            } catch (e) {
                ADM.toast('Erreur sauvegarde checklist', 'error');
            }
        },
    };

    // ══════════════════════════════════════════════════════════════════
    // PREREQUISITES MANAGER
    // ══════════════════════════════════════════════════════════════════
    const PrereqManager = {

        async load(accountId) {
            try {
                const data = await Http.post('get_prerequisites', { account_id: accountId });
                if (!Array.isArray(data)) return;
                State.prerequisites = data;
                data.forEach(item => {
                    const cb = document.querySelector(`.adm-prereq-cb[data-key="${item.key}"]`);
                    if (cb) cb.checked = item.done;
                });
                this.updateProgress();
            } catch (e) { /* silencieux */ }
        },

        updateProgress() {
            const cbs   = document.querySelectorAll('.adm-prereq-cb');
            const done  = [...cbs].filter(cb => cb.checked).length;
            const total = cbs.length;
            const pct   = total > 0 ? Math.round(done / total * 100) : 0;

            const bar   = document.getElementById('adm-prereq-progress');
            const badge = document.getElementById('adm-prereq-badge');
            if (bar)   { bar.style.width = pct + '%'; bar.classList.toggle('complete', pct === 100); }
            if (badge) { badge.textContent = pct + '%'; badge.className = `adm-badge-status adm-badge-${pct === 100 ? 'done' : 'todo'}`; }

            cbs.forEach(cb => {
                const item = cb.closest('.adm-prereq-item');
                if (item) item.classList.toggle('done', cb.checked);
            });
        },

        async saveAll() {
            if (!State.accountId) { ADM.toast('Sélectionnez un compte', 'error'); return; }
            const items = [...document.querySelectorAll('.adm-prereq-cb')].map(cb => ({
                key:  cb.dataset.key,
                done: cb.checked,
            }));
            Loading.set('save-prereqs', true);
            try {
                await Http.post('save_prerequisites', {
                    account_id: State.accountId,
                    items,
                });
                ADM.toast('Prérequis sauvegardés ✓', 'success');
            } catch (e) {
                ADM.toast(e.message, 'error');
            } finally {
                Loading.set('save-prereqs', false);
            }
        },
    };

    // ══════════════════════════════════════════════════════════════════
    // AUDIENCE MANAGER
    // ══════════════════════════════════════════════════════════════════
    const AudienceManager = {

        CONFIGS: {
            CI:  { icon: 'bullseye',  color: '#1877f2', label: 'Custom Intent',  temp: 'Hot',  desc: 'Visiteurs site + interactions' },
            LAL: { icon: 'users',     color: '#10b981', label: 'Lookalike 180j', temp: 'Warm', desc: 'Sosies clients 180 jours' },
            TNT: { icon: 'crosshairs',color: '#f59e0b', label: 'Test & Target',  temp: 'Cold', desc: 'Centres d\'intérêt ciblés' },
        },

        async load(accountId) {
            try {
                const data = await Http.post('get_audiences', { account_id: accountId });
                if (!Array.isArray(data)) return;
                State.audiences = data;
                this.renderStatus(data);
            } catch (e) { /* silencieux */ }
        },

        renderStatus(audiences) {
            audiences.forEach(aud => {
                const card = document.querySelector(`.adm-audience-card[data-type="${aud.audience_type}"]`);
                if (!card) return;
                // Ajouter badge statut
                let badge = card.querySelector('.adm-audience-status');
                if (!badge) {
                    badge = document.createElement('div');
                    badge.className = 'adm-audience-status';
                    badge.style.cssText = 'margin-top:8px;font-size:.63rem;font-weight:700;padding:2px 8px;border-radius:10px;display:inline-block';
                    card.querySelector('.adm-audience-desc')?.after(badge);
                }
                const isActive = aud.status === 'active';
                badge.textContent = isActive ? '✓ Créée' : '⏳ Brouillon';
                badge.style.background = isActive ? '#d1fae5' : '#fef3c7';
                badge.style.color      = isActive ? '#059669' : '#d97706';
            });
        },

        async create(accountId) {
            if (!accountId) { ADM.toast('Sélectionnez un compte', 'error'); return; }
            Loading.set('create-audiences', true);
            try {
                const data = await Http.post('create_audiences', { account_id: accountId });
                ADM.toast('3 audiences créées ✓', 'success');
                await this.load(accountId);
                return data;
            } catch (e) {
                ADM.toast(e.message, 'error');
            } finally {
                Loading.set('create-audiences', false);
            }
        },
    };

    // ══════════════════════════════════════════════════════════════════
    // CAMPAIGN MANAGER
    // ══════════════════════════════════════════════════════════════════
    const CampaignManager = {

        async load(accountId) {
            try {
                const data = await Http.post('get_campaigns', { account_id: accountId });
                if (!Array.isArray(data)) return;
                State.campaigns = data;
                this.renderList(data);
            } catch (e) { /* silencieux */ }
        },

        renderList(campaigns) {
            const container = document.getElementById('adm-campaigns-list');
            if (!container) return;

            if (!campaigns.length) {
                container.innerHTML = `
                    <div class="adm-empty">
                        <i class="fas fa-chart-bar"></i>
                        <h3>Aucune campagne</h3>
                        <p>Générez un nom et sauvegardez votre première campagne.</p>
                    </div>`;
                return;
            }

            const tempColors = { Cold: '#3b82f6', Warm: '#f59e0b', Hot: '#ef4444' };
            const tempIcons  = { Cold: '❄️', Warm: '🌡', Hot: '🔥' };

            container.innerHTML = campaigns.map(c => `
                <div class="adm-campaign-row" data-id="${c.id}">
                    <div class="adm-campaign-temp" style="background:${tempColors[c.temperature] ?? '#6b7280'}18;color:${tempColors[c.temperature] ?? '#6b7280'}">
                        ${tempIcons[c.temperature] ?? ''} ${c.temperature}
                    </div>
                    <div class="adm-campaign-info">
                        <strong>${this._esc(c.campaign_name)}</strong>
                        <span>${c.objective} · ${c.audience_type}</span>
                    </div>
                    <span class="adm-badge-status adm-badge-${c.status === 'active' ? 'done' : 'todo'}" style="font-size:.6rem">
                        ${c.status}
                    </span>
                    <div class="adm-campaign-actions">
                        <button onclick="ADS.campaign.toggleStatus(${c.id}, '${c.status}')" title="${c.status === 'active' ? 'Pause' : 'Activer'}">
                            <i class="fas fa-${c.status === 'active' ? 'pause' : 'play'}"></i>
                        </button>
                        <button class="del" onclick="ADS.campaign.delete(${c.id}, '${this._esc(c.campaign_name)}')" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>`
            ).join('');
        },

        generateName() {
            const order    = String(document.getElementById('adm-camp-order')?.value || '1').padStart(2, '0');
            const temp     = document.getElementById('adm-camp-temp')?.value    || 'Cold';
            const obj      = document.getElementById('adm-camp-obj')?.value     || 'Leads';
            const audience = document.getElementById('adm-camp-audience')?.value || 'CI';
            const date     = new Date().toISOString().slice(0, 10).replace(/-/g, '');
            return `C${order}_${temp}_${obj}_${audience}_${date}`;
        },

        previewName() {
            const el = document.getElementById('adm-generated-name');
            if (el) el.textContent = this.generateName();
        },

        copyName() {
            const name = document.getElementById('adm-generated-name')?.textContent;
            if (!name || name === '—') { ADM.toast('Générez d\'abord un nom', 'error'); return; }
            navigator.clipboard.writeText(name)
                .then(() => ADM.toast('Copié ✓', 'success'))
                .catch(()  => ADM.toast('Copie impossible', 'error'));
        },

        async save() {
            const name = document.getElementById('adm-generated-name')?.textContent;
            if (!name || name === '—') { ADM.toast('Générez d\'abord un nom', 'error'); return; }

            Loading.set('save-campaign', true);
            try {
                await Http.post('save_campaign', {
                    account_id:  State.accountId ?? '',
                    name,
                    temperature: document.getElementById('adm-camp-temp')?.value,
                    objective:   document.getElementById('adm-camp-obj')?.value,
                    audience:    document.getElementById('adm-camp-audience')?.value,
                });
                ADM.toast('Campagne sauvegardée ✓', 'success');
                if (State.accountId) await this.load(State.accountId);
            } catch (e) {
                ADM.toast(e.message, 'error');
            } finally {
                Loading.set('save-campaign', false);
            }
        },

        async toggleStatus(id, current) {
            const next = current === 'active' ? 'paused' : 'active';
            try {
                await Http.post('update_campaign_status', { id, status: next });
                ADM.toast(next === 'active' ? 'Campagne activée ✓' : 'Campagne mise en pause', 'success');
                if (State.accountId) await this.load(State.accountId);
            } catch (e) {
                ADM.toast(e.message, 'error');
            }
        },

        delete(id, name) {
            ADM.modal({
                icon: '<i class="fas fa-trash"></i>', iconBg: '#fef2f2', iconColor: '#dc2626',
                title: 'Supprimer cette campagne ?',
                msg:   `<strong>${name}</strong> sera supprimée définitivement.`,
                confirmLabel: 'Supprimer', confirmColor: '#dc2626',
                onConfirm: async () => {
                    try {
                        await Http.post('delete_campaign', { id });
                        // Retrait immédiat du DOM
                        document.querySelector(`.adm-campaign-row[data-id="${id}"]`)?.remove();
                        State.campaigns = State.campaigns.filter(c => c.id !== id);
                        ADM.toast('Campagne supprimée', 'success');
                    } catch (e) {
                        ADM.toast(e.message, 'error');
                    }
                },
            });
        },

        _esc: str => String(str).replace(/[&<>"']/g, c =>
            ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])
        ),
    };

    // ══════════════════════════════════════════════════════════════════
    // ANALYTICS MANAGER
    // ══════════════════════════════════════════════════════════════════
    const AnalyticsManager = {

        currentPeriod: '30d',

        async load(accountId, period = '30d') {
            this.currentPeriod = period;
            Loading.set('analytics', true);
            try {
                const data = await Http.post('get_analytics', {
                    account_id: accountId ?? '',
                    period,
                });
                State.analytics = data;
                this.renderKpis(data?.kpis ?? {});
                this.renderChart(data?.series ?? []);
                this.hideEmpty(data?.kpis);
            } catch (e) {
                /* silencieux si tables absentes */
            } finally {
                Loading.set('analytics', false);
            }
        },

        renderKpis(kpis) {
            const fmt = (v, decimals = 0) =>
                Number(v || 0).toLocaleString('fr-FR', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });

            const map = {
                'adm-kpi-impressions': fmt(kpis.impressions),
                'adm-kpi-clicks':      fmt(kpis.clicks),
                'adm-kpi-leads':       fmt(kpis.leads),
                'adm-kpi-cpl':         kpis.cpl > 0 ? fmt(kpis.cpl, 2) + ' €' : '—',
                'adm-kpi-ctr':         kpis.avg_ctr > 0 ? fmt(kpis.avg_ctr, 2) + ' %' : '—',
                'adm-kpi-roas':        kpis.avg_roas > 0 ? 'x' + fmt(kpis.avg_roas, 2) : '—',
            };
            for (const [id, val] of Object.entries(map)) {
                const el = document.getElementById(id);
                if (el) el.textContent = val;
            }
        },

        renderChart(series) {
            const canvas = document.getElementById('adm-analytics-chart');
            if (!canvas || !series.length) return;

            // Nettoyage instance Chart.js précédente
            if (canvas._chartInstance) canvas._chartInstance.destroy();

            const labels = series.map(s => {
                const d = new Date(s.date_recorded);
                return d.toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });
            });

            canvas._chartInstance = new Chart(canvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label:           'Leads',
                            data:            series.map(s => s.leads),
                            borderColor:     '#1877f2',
                            backgroundColor: 'rgba(24,119,242,.08)',
                            fill:            true,
                            tension:         .4,
                            pointRadius:     3,
                            pointHoverRadius:5,
                        },
                        {
                            label:           'Dépenses (€)',
                            data:            series.map(s => s.spend),
                            borderColor:     '#f59e0b',
                            backgroundColor: 'rgba(245,158,11,.06)',
                            fill:            true,
                            tension:         .4,
                            pointRadius:     3,
                            pointHoverRadius:5,
                            yAxisID:         'y1',
                        },
                    ],
                },
                options: {
                    responsive:          true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { position: 'top', labels: { font: { size: 11 }, usePointStyle: true } },
                        tooltip: {
                            backgroundColor: '#fff',
                            borderColor:     '#e5e7eb',
                            borderWidth:     1,
                            titleColor:      '#111827',
                            bodyColor:       '#6b7280',
                            padding:         10,
                        },
                    },
                    scales: {
                        x:  { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af' } },
                        y:  { position: 'left',  grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, color: '#9ca3af' } },
                        y1: { position: 'right', grid: { display: false },   ticks: { font: { size: 10 }, color: '#9ca3af' } },
                    },
                },
            });
        },

        hideEmpty(kpis) {
            const empty = document.querySelector('#adm-tab-analytics .adm-empty');
            if (!empty) return;
            const hasData = kpis && Object.values(kpis).some(v => v > 0);
            empty.style.display = hasData ? 'none' : '';
        },

        setPeriod(period) {
            document.querySelectorAll('.adm-period-btn').forEach(b =>
                b.classList.toggle('active', b.dataset.period === period)
            );
            if (State.accountId) this.load(State.accountId, period);
        },
    };

    // ══════════════════════════════════════════════════════════════════
    // EXTENSION DE ADM (défini dans index.php)
    // ══════════════════════════════════════════════════════════════════
    Object.assign(window.ADM ?? {}, {

        // Surcharge onAccountChange pour passer par AccountManager
        onAccountChange(id) {
            AccountManager.onChange(id);
        },

        // Surcharge updatePrereqProgress
        updatePrereqProgress() {
            PrereqManager.updateProgress();
        },

        // Surcharge createAudiences
        createAudiences() {
            AudienceManager.create(State.accountId);
        },

        // Surcharge previewName / generateName / copyName / saveCampaign
        previewName()    { CampaignManager.previewName(); },
        generateName()   { CampaignManager.previewName(); ADM.toast('Nom généré ✓', 'success'); },
        copyName()       { CampaignManager.copyName(); },
        saveCampaign()   { CampaignManager.save(); },

        // Analytics period switcher
        setAnalyticsPeriod(p) { AnalyticsManager.setPeriod(p); },

        // Surcharge init — appelée par DOMContentLoaded dans index.php
        init() {
            this.restoreTab();
            CampaignManager.previewName();
            AccountManager.restore();
            this._bindEvents();
        },

        _bindEvents() {
            // Live preview sur changement de champs campagne
            ['adm-camp-order', 'adm-camp-temp', 'adm-camp-obj', 'adm-camp-audience'].forEach(id => {
                document.getElementById(id)?.addEventListener('input',  () => CampaignManager.previewName());
                document.getElementById(id)?.addEventListener('change', () => CampaignManager.previewName());
            });

            // Sauvegarde auto des prérequis au uncheck/check
            document.querySelectorAll('.adm-prereq-cb').forEach(cb => {
                cb.addEventListener('change', () => {
                    PrereqManager.updateProgress();
                    this._debouncedSavePrereqs();
                });
            });

            // Bouton sauvegarde prérequis explicite
            document.querySelector('[data-loading="save-prereqs"]')
                ?.addEventListener('click', () => PrereqManager.saveAll());

            // Bouton sauvegarde campagne
            document.querySelector('[data-loading="save-campaign"]')
                ?.addEventListener('click', () => CampaignManager.save());

            // Bouton create audiences
            document.querySelector('[data-loading="create-audiences"]')
                ?.addEventListener('click', () => AudienceManager.create(State.accountId));
        },

        // Debounce sauvegarde prérequis (évite 6 requêtes sur un clic rapide)
        _debouncedSavePrereqs: (() => {
            let t;
            return () => { clearTimeout(t); t = setTimeout(() => PrereqManager.saveAll(), 800); };
        })(),
    });

    // ══════════════════════════════════════════════════════════════════
    // EXPOSITION PUBLIQUE (pour les onclick inline du HTML généré)
    // ══════════════════════════════════════════════════════════════════
    window.ADS = {
        campaign:  CampaignManager,
        audience:  AudienceManager,
        analytics: AnalyticsManager,
        prereq:    PrereqManager,
        checklist: ChecklistManager,
        state:     State,
    };

    // ══════════════════════════════════════════════════════════════════
    // CHART.JS — chargement conditionnel
    // ══════════════════════════════════════════════════════════════════
    function loadChartJs(cb) {
        if (window.Chart) { cb(); return; }
        const s    = document.createElement('script');
        s.src      = 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js';
        s.onload   = cb;
        s.onerror  = () => console.warn('[ADS] Chart.js non chargé — graphique analytics désactivé');
        document.head.appendChild(s);
    }

    // ══════════════════════════════════════════════════════════════════
    // BOOT
    // ══════════════════════════════════════════════════════════════════
    document.addEventListener('DOMContentLoaded', () => {
        loadChartJs(() => {
            // Chart.js dispo, si un compte est déjà sélectionné on charge les analytics
            if (State.accountId) {
                AnalyticsManager.load(State.accountId);
            }
        });
    });

})();