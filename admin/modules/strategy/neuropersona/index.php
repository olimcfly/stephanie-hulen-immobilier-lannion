<?php
/**
 * ÉCOSYSTÈME IMMO LOCAL+ — Module NeuroPersona
 * admin/modules/strategy/neuropersona/index.php
 * v1.0 — Catalogue 30 personas + fiche AI
 * Chargé via dashboard.php?page=neuropersona
 */

// Sécurité : accès uniquement via dashboard
if (!defined('DASHBOARD_LOADED') && !isset($_SESSION['user_id'])) {
    // Fallback si pas de constante définie
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

// --- DONNÉES PERSONAS (référentiel statique) ---
$personas = [
    // Acheteurs Résidence Principale
    ['id'=>1,'name'=>'Primo-Accédant Jeune Couple','family'=>'acheteurs','age'=>'25-35 ans','desc'=>'CDI récent, locataire, veut arrêter de "jeter l\'argent par les fenêtres"','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>2],
    ['id'=>2,'name'=>'Primo-Accédant Solo','family'=>'acheteurs','age'=>'28-40 ans','desc'=>'Célibataire ou divorcé, veut son indépendance, budget serré','m1'=>'Liberté','m2'=>'Sécurité','conscience'=>2],
    ['id'=>3,'name'=>'Famille en Expansion','family'=>'acheteurs','age'=>'30-45 ans','desc'=>'Appart trop petit, enfants grandissent, veut maison avec jardin','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>3],
    ['id'=>4,'name'=>'Muté Professionnel','family'=>'acheteurs','age'=>'30-50 ans','desc'=>'Mutation imposée, ne connaît pas la ville, urgence','m1'=>'Contrôle','m2'=>'Sécurité','conscience'=>3],
    ['id'=>5,'name'=>'Retraité Actif — Downsizer','family'=>'acheteurs','age'=>'60-75 ans','desc'=>'Vend la grande maison, cherche plus petit/pratique, proche famille','m1'=>'Liberté','m2'=>'Sécurité','conscience'=>3],
    ['id'=>6,'name'=>'Expatrié de Retour','family'=>'acheteurs','age'=>'35-55 ans','desc'=>'Revient en France, ne connaît plus le marché local, achète à distance','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>2],
    ['id'=>7,'name'=>'Divorcé en Reconstruction','family'=>'acheteurs','age'=>'35-55 ans','desc'=>'Séparation récente, doit racheter seul, émotionnellement fragile','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>2],
    ['id'=>8,'name'=>'Acheteur Résidence Secondaire','family'=>'acheteurs','age'=>'45-65 ans','desc'=>'Aisé, cherche maison de vacances ou pied-à-terre, plaisir','m1'=>'Reconnaissance','m2'=>'Liberté','conscience'=>4],
    // Vendeurs
    ['id'=>9,'name'=>'Senior Simplificateur','family'=>'vendeurs','age'=>'65-80 ans','desc'=>'Maison trop grande, veut se rapprocher famille/services, peur du changement','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>2],
    ['id'=>10,'name'=>'Héritier — Succession','family'=>'vendeurs','age'=>'40-60 ans','desc'=>'Bien hérité, indivision possible, charge émotionnelle, veut vendre vite','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>3],
    ['id'=>11,'name'=>'Vendeur Divorce / Séparation','family'=>'vendeurs','age'=>'30-55 ans','desc'=>'Vente imposée, tension entre ex-conjoints, besoin de neutralité','m1'=>'Contrôle','m2'=>'Sécurité','conscience'=>3],
    ['id'=>12,'name'=>'Muté — Vente Urgente','family'=>'vendeurs','age'=>'30-50 ans','desc'=>'Mutation pro, deadline serrée, peur de vendre sous le prix','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>3],
    ['id'=>13,'name'=>'Propriétaire qui Monte en Gamme','family'=>'vendeurs','age'=>'35-50 ans','desc'=>'Vend pour acheter plus grand/mieux, crédit-relais, timing crucial','m1'=>'Reconnaissance','m2'=>'Contrôle','conscience'=>4],
    ['id'=>14,'name'=>'Expatrié — Vente à Distance','family'=>'vendeurs','age'=>'35-60 ans','desc'=>'Vit à l\'étranger, bien en France, veut 0 déplacement, procuration','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>3],
    ['id'=>15,'name'=>'Investisseur qui Revend','family'=>'vendeurs','age'=>'40-65 ans','desc'=>'Arbitrage patrimonial, veut maximiser la plus-value, fiscalité','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>4],
    ['id'=>16,'name'=>'Vendeur Première Fois','family'=>'vendeurs','age'=>'30-50 ans','desc'=>'N\'a jamais vendu, peur de se faire arnaquer, besoin d\'être rassuré','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>1],
    // Investisseurs
    ['id'=>17,'name'=>'Locatif Rentabilité Pure','family'=>'investisseurs','age'=>'35-55 ans','desc'=>'Cherche rendement max, sensible aux chiffres, compare tout','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>4],
    ['id'=>18,'name'=>'Défiscalisation / Patrimoine','family'=>'investisseurs','age'=>'40-60 ans','desc'=>'TMI élevée, veut réduire impôts, Pinel/LMNP/déficit foncier','m1'=>'Contrôle','m2'=>'Sécurité','conscience'=>3],
    ['id'=>19,'name'=>'Colocation / Étudiant','family'=>'investisseurs','age'=>'30-50 ans','desc'=>'Cible villes universitaires, cherche multi-locataires, rentabilité 6-10%','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>4],
    ['id'=>20,'name'=>'Location Courte Durée / Airbnb','family'=>'investisseurs','age'=>'30-50 ans','desc'=>'Zone touristique, veut revenus élevés, prêt à gérer activement','m1'=>'Liberté','m2'=>'Reconnaissance','conscience'=>4],
    ['id'=>21,'name'=>'Immeuble de Rapport','family'=>'investisseurs','age'=>'40-60 ans','desc'=>'Expérimenté, achète en bloc, négocie dur, veut cash-flow positif','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>5],
    ['id'=>22,'name'=>'Primo-Investisseur Prudent','family'=>'investisseurs','age'=>'30-40 ans','desc'=>'Premier investissement, budget modeste, a peur de se tromper','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>2],
    ['id'=>23,'name'=>'Prépare sa Retraite','family'=>'investisseurs','age'=>'45-58 ans','desc'=>'Constitue un patrimoine pour compléter sa retraite, horizon 10-15 ans','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>3],
    // Profils Spécifiques / Niches
    ['id'=>24,'name'=>'Nouveau Résident — Découvre la Région','family'=>'niches','age'=>'30-55 ans','desc'=>'Choix de vie (télétravail, qualité de vie), ne connaît rien au local','m1'=>'Liberté','m2'=>'Sécurité','conscience'=>2],
    ['id'=>25,'name'=>'Bailleur en Difficulté','family'=>'niches','age'=>'40-65 ans','desc'=>'Locataire problématique, impayés, DPE F/G, veut sortir du locatif','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>3],
    ['id'=>26,'name'=>'Propriétaire DPE F/G — Passoire','family'=>'niches','age'=>'Tout âge','desc'=>'Bien interdit à la location 2025+, doit rénover ou vendre, anxieux','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>2],
    ['id'=>27,'name'=>'Professionnel Libéral — Achat Pro + Perso','family'=>'niches','age'=>'30-55 ans','desc'=>'Médecin/avocat/archi, cherche local pro + logement, SCI possible','m1'=>'Reconnaissance','m2'=>'Contrôle','conscience'=>4],
    ['id'=>28,'name'=>'Vendeur en Viager','family'=>'niches','age'=>'70-85 ans','desc'=>'Veut rester chez soi, compléter retraite, pas d\'héritier ou conflit','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>2],
    ['id'=>29,'name'=>'Acheteur Luxe / Prestige','family'=>'niches','age'=>'40-65 ans','desc'=>'Budget 500K+, exigeant, veut discrétion et service sur-mesure','m1'=>'Reconnaissance','m2'=>'Contrôle','conscience'=>5],
    ['id'=>30,'name'=>'Marchand de Biens','family'=>'niches','age'=>'35-55 ans','desc'=>'Pro, achète pour revendre, cherche décote, négocie tout, volume','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>5],
];

$families = [
    'acheteurs'      => ['label'=>'Acheteurs RP','color'=>'#e74c3c','bg'=>'#fdf2f2','icon'=>'🏠'],
    'vendeurs'       => ['label'=>'Vendeurs','color'=>'#d4880f','bg'=>'#fef9f0','icon'=>'🔑'],
    'investisseurs'  => ['label'=>'Investisseurs','color'=>'#8b5cf6','bg'=>'#f5f3ff','icon'=>'📈'],
    'niches'         => ['label'=>'Niches','color'=>'#10b981','bg'=>'#f0fdf4','icon'=>'🎯'],
];

$motivations = [
    'Sécurité'       => ['color'=>'#1e40af','bg'=>'#dbeafe'],
    'Liberté'        => ['color'=>'#065f46','bg'=>'#d1fae5'],
    'Reconnaissance' => ['color'=>'#92400e','bg'=>'#fef3c7'],
    'Contrôle'       => ['color'=>'#5b21b6','bg'=>'#ede9fe'],
];

$conscienceLabels = ['','Non conscient','Conscient du problème','Cherche activement','Compare les solutions','Prêt à agir'];

// Encode pour JS
$personasJson = json_encode($personas, JSON_UNESCAPED_UNICODE);
$familiesJson = json_encode($families, JSON_UNESCAPED_UNICODE);
$motivationsJson = json_encode($motivations, JSON_UNESCAPED_UNICODE);
$conscienceJson = json_encode($conscienceLabels, JSON_UNESCAPED_UNICODE);
?>

<!-- ============================================= -->
<!-- CSS — Namespace np- -->
<!-- ============================================= -->
<style>
/* --- Module Layout --- */
.np-module { display: flex; gap: 0; height: calc(100vh - 70px); overflow: hidden; }
.np-main { flex: 1; overflow-y: auto; padding: 24px 28px; }
.np-panel { width: 0; overflow: hidden; transition: width .3s ease, opacity .3s ease; opacity: 0; border-left: 0 solid var(--border, #e2e8f0); background: var(--surface, #fff); display: flex; flex-direction: column; }
.np-panel.np-panel--open { width: 440px; opacity: 1; border-left-width: 1px; }

/* --- Header --- */
.np-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 2px solid #1a1a2e; }
.np-header-left { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
.np-header h1 { font-size: 22px; font-weight: 800; color: #1a1a2e; margin: 0; letter-spacing: -0.02em; }
.np-header .np-badge { font-size: 11px; font-weight: 700; color: #8b5cf6; background: #ede9fe; padding: 2px 9px; border-radius: 5px; }
.np-header .np-sub { font-size: 11px; color: #94a3b8; font-weight: 500; }

/* --- Controls --- */
.np-controls { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 14px; }
.np-search { position: relative; flex: 1 1 200px; min-width: 180px; }
.np-search input { width: 100%; padding: 8px 12px 8px 32px; font-size: 12px; border: 1px solid var(--border, #e2e8f0); border-radius: 8px; outline: none; background: var(--surface, #fafafa); box-sizing: border-box; font-family: inherit; transition: border-color .2s; }
.np-search input:focus { border-color: var(--accent, #8b5cf6); }
.np-search .np-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 13px; opacity: 0.4; pointer-events: none; }
.np-toggle { display: flex; border: 1px solid var(--border, #e2e8f0); border-radius: 8px; overflow: hidden; }
.np-toggle button { padding: 7px 14px; font-size: 11px; font-weight: 600; border: none; cursor: pointer; background: var(--surface, #fff); color: #64748b; font-family: inherit; transition: all .15s; }
.np-toggle button.np-active { background: #1a1a2e; color: #fff; }

/* --- Filters --- */
.np-filters { display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 16px; padding: 10px 14px; background: var(--surface-alt, #f8fafc); border-radius: 10px; align-items: center; }
.np-filter-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.np-filter-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.np-filter-btn { padding: 3px 10px; font-size: 10px; font-weight: 600; border-radius: 5px; cursor: pointer; border: 2px solid transparent; background: var(--surface, #fff); font-family: inherit; transition: all .15s; }
.np-filter-btn:hover { transform: translateY(-1px); }
.np-filter-btn.np-filter-active { border-width: 2px; }
.np-reset-btn { padding: 3px 10px; font-size: 10px; font-weight: 600; border-radius: 5px; background: #fee2e2; color: #dc2626; border: none; cursor: pointer; font-family: inherit; }

/* --- Cards View --- */
.np-cat-head { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; padding: 8px 14px; border-radius: 8px; }
.np-cat-head h3 { font-size: 14px; font-weight: 700; margin: 0; }
.np-cat-head .np-cnt { font-size: 10px; font-weight: 700; padding: 1px 7px; border-radius: 5px; }
.np-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 8px; margin-bottom: 22px; }
.np-card { padding: 14px 16px; border: 1px solid var(--border, #e5e7eb); border-radius: 10px; background: var(--surface, #fff); cursor: pointer; transition: all .18s; position: relative; }
.np-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); transform: translateY(-1px); }
.np-card-top { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.np-card-num { font-size: 10px; font-weight: 700; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; }
.np-card-name { font-size: 13px; font-weight: 700; color: #1a1a2e; line-height: 1.3; }
.np-card-desc { font-size: 11px; color: #64748b; line-height: 1.55; margin-bottom: 8px; }
.np-card-desc strong { color: #475569; font-weight: 600; }
.np-card-bottom { display: flex; align-items: center; gap: 6px; justify-content: space-between; }
.np-tags { display: flex; gap: 4px; }
.np-tag { font-size: 9px; font-weight: 600; padding: 2px 6px; border-radius: 4px; white-space: nowrap; }
.np-dots { display: inline-flex; gap: 3px; align-items: center; }
.np-dot { width: 7px; height: 7px; border-radius: 50%; background: #e5e7eb; transition: background .2s; }
.np-dot.np-dot--on { background: #f59e0b; }

/* --- Table View --- */
.np-table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid var(--border, #e2e8f0); }
.np-table { width: 100%; border-collapse: collapse; background: var(--surface, #fff); }
.np-table thead th { padding: 10px 12px; text-align: left; font-size: 11px; font-weight: 700; color: #475569; cursor: pointer; user-select: none; border-bottom: 2px solid #e2e8f0; white-space: nowrap; background: var(--surface-alt, #f8fafc); position: sticky; top: 0; z-index: 2; transition: background .15s; }
.np-table thead th:hover { background: #eef2ff; }
.np-table thead th.np-th-sorted { background: #f1f5f9; }
.np-table thead th.np-th-nosort { cursor: default; }
.np-table thead th.np-th-nosort:hover { background: var(--surface-alt, #f8fafc); }
.np-table tbody tr { cursor: pointer; transition: background .1s; }
.np-table tbody tr:nth-child(even) { background: #fafbfc; }
.np-table tbody tr:hover { background: #f0f4ff; }
.np-table td { padding: 10px 12px; font-size: 11.5px; border-bottom: 1px solid #f1f5f9; color: #334155; }
.np-table .np-td-name { font-weight: 600; color: #1a1a2e; }
.np-sort-icon { margin-left: 4px; font-size: 10px; opacity: 0.25; }
.np-sort-icon.np-sort-active { opacity: 1; }

/* --- Stats Footer --- */
.np-stats { margin-top: 20px; padding: 14px 18px; border-radius: 10px; background: linear-gradient(135deg, #1a1a2e 0%, #2d2b55 100%); color: #fff; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between; }
.np-stats-count { font-size: 22px; font-weight: 800; }
.np-stats-count span { font-size: 11px; opacity: 0.7; margin-left: 6px; font-weight: 400; }
.np-stats-motiv { display: flex; gap: 12px; flex-wrap: wrap; }
.np-stats-motiv-item { display: flex; align-items: center; gap: 5px; }
.np-stats-motiv-item .np-sm-dot { width: 8px; height: 8px; border-radius: 2px; }
.np-stats-motiv-item .np-sm-label { font-size: 10px; opacity: 0.8; }
.np-stats-motiv-item .np-sm-val { font-size: 12px; font-weight: 700; }
.np-stats-hint { font-size: 10px; opacity: 0.5; }

/* --- AI Panel --- */
.np-panel-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 18px; border-bottom: 1px solid var(--border, #e2e8f0); flex-shrink: 0; }
.np-panel-header h3 { font-size: 14px; font-weight: 700; margin: 0; color: #1a1a2e; }
.np-panel-close { width: 28px; height: 28px; border-radius: 6px; border: none; background: #f1f5f9; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; transition: background .15s; }
.np-panel-close:hover { background: #e2e8f0; }
.np-panel-body { flex: 1; overflow-y: auto; padding: 18px; }
.np-panel-loading { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px; gap: 12px; color: #64748b; }
.np-panel-loading .np-spinner { width: 28px; height: 28px; border: 3px solid #e2e8f0; border-top-color: #8b5cf6; border-radius: 50%; animation: np-spin 0.7s linear infinite; }
@keyframes np-spin { to { transform: rotate(360deg); } }
.np-panel-content { font-size: 13px; line-height: 1.7; color: #334155; }
.np-panel-content h2 { font-size: 15px; font-weight: 700; color: #1a1a2e; margin: 18px 0 8px; }
.np-panel-content h3 { font-size: 13px; font-weight: 700; color: #475569; margin: 14px 0 6px; }
.np-panel-content ul { margin: 4px 0 8px 18px; padding: 0; }
.np-panel-content li { margin-bottom: 3px; }
.np-panel-content strong { color: #1a1a2e; }
.np-panel-content em { color: #8b5cf6; font-style: normal; font-weight: 600; }
.np-panel-persona-header { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid var(--border, #e2e8f0); }
.np-panel-persona-header .np-card-num { width: 32px; height: 32px; font-size: 13px; }

/* --- Responsive --- */
@media (max-width: 900px) {
    .np-panel.np-panel--open { width: 100%; position: fixed; top: 0; left: 0; z-index: 100; height: 100vh; }
    .np-grid { grid-template-columns: 1fr; }
}
</style>

<!-- ============================================= -->
<!-- HTML -->
<!-- ============================================= -->
<div class="np-module" id="npModule">
    <!-- Main Content -->
    <div class="np-main" id="npMain">

        <!-- Header -->
        <div class="np-header">
            <div class="np-header-left">
                <h1>NeuroPersona</h1>
                <span class="np-badge">30 PERSONAS</span>
                <span class="np-sub">Catalogue stratégique immobilier</span>
            </div>
        </div>

        <!-- Controls -->
        <div class="np-controls">
            <div class="np-search">
                <span class="np-search-icon">⌕</span>
                <input type="text" id="npSearch" placeholder="Rechercher un persona…">
            </div>
            <div class="np-toggle">
                <button class="np-active" data-view="cards" onclick="NP.setView('cards')">▦ Cartes</button>
                <button data-view="table" onclick="NP.setView('table')">☰ Tableau</button>
            </div>
        </div>

        <!-- Filters -->
        <div class="np-filters" id="npFilters">
            <div class="np-filter-group">
                <span class="np-filter-label">Famille</span>
                <?php foreach ($families as $key => $fam): ?>
                <button class="np-filter-btn" data-filter-type="family" data-filter-value="<?= $key ?>"
                    style="color:<?= $fam['color'] ?>"
                    onclick="NP.toggleFilter('family','<?= $key ?>')">
                    <?= $fam['icon'] ?> <?= $fam['label'] ?>
                </button>
                <?php endforeach; ?>
            </div>
            <div class="np-filter-group">
                <span class="np-filter-label">Motivation</span>
                <?php foreach ($motivations as $mot => $m): ?>
                <button class="np-filter-btn" data-filter-type="motiv" data-filter-value="<?= $mot ?>"
                    style="color:<?= $m['color'] ?>"
                    onclick="NP.toggleFilter('motiv','<?= htmlspecialchars($mot) ?>')">
                    <?= $mot ?>
                </button>
                <?php endforeach; ?>
            </div>
            <button class="np-reset-btn" id="npResetBtn" style="display:none" onclick="NP.resetFilters()">✕ Reset</button>
        </div>

        <!-- Content container -->
        <div id="npContent"></div>

        <!-- Stats -->
        <div class="np-stats" id="npStats"></div>
    </div>

    <!-- AI Panel (sidebar) -->
    <div class="np-panel" id="npPanel">
        <div class="np-panel-header">
            <h3 id="npPanelTitle">Fiche NeuroPersona</h3>
            <button class="np-panel-close" onclick="NP.closePanel()">✕</button>
        </div>
        <div class="np-panel-body" id="npPanelBody">
            <!-- Filled by JS -->
        </div>
    </div>
</div>

<!-- ============================================= -->
<!-- JavaScript -->
<!-- ============================================= -->
<script>
const NP = (() => {
    // --- Data ---
    const personas = <?= $personasJson ?>;
    const families = <?= $familiesJson ?>;
    const motivations = <?= $motivationsJson ?>;
    const conscienceLabels = <?= $conscienceJson ?>;

    // --- State ---
    let state = {
        view: 'cards',
        search: '',
        familyFilters: [],
        motivFilters: [],
        sortCol: null,
        sortDir: 'asc',
    };

    // --- DOM refs ---
    const $content = document.getElementById('npContent');
    const $stats = document.getElementById('npStats');
    const $search = document.getElementById('npSearch');
    const $resetBtn = document.getElementById('npResetBtn');
    const $panel = document.getElementById('npPanel');
    const $panelBody = document.getElementById('npPanelBody');
    const $panelTitle = document.getElementById('npPanelTitle');

    // --- Helpers ---
    function tag(mot, small = false) {
        const m = motivations[mot];
        if (!m) return '';
        const s = small ? 'font-size:9px;padding:1px 5px' : 'font-size:10px;padding:2px 7px';
        return `<span class="np-tag" style="${s};background:${m.bg};color:${m.color}">${mot}</span>`;
    }

    function familyPill(fam, small = false) {
        const f = families[fam];
        const s = small ? 'font-size:9px;padding:1px 5px' : 'font-size:10px;padding:2px 7px';
        return `<span class="np-tag" style="${s};background:${f.bg};color:${f.color}">${f.label}</span>`;
    }

    function dots(level) {
        let html = '<span class="np-dots" title="Schwartz: ' + conscienceLabels[level] + '">';
        for (let i = 1; i <= 5; i++) {
            html += `<span class="np-dot${i <= level ? ' np-dot--on' : ''}"></span>`;
        }
        html += '</span>';
        return html;
    }

    function sortIcon(col) {
        const active = state.sortCol === col;
        const arrow = active ? (state.sortDir === 'asc' ? '▲' : '▼') : '⇅';
        return `<span class="np-sort-icon${active ? ' np-sort-active' : ''}">${arrow}</span>`;
    }

    // --- Filter logic ---
    function getFiltered() {
        let list = personas;
        const s = state.search.toLowerCase().trim();
        if (s) {
            list = list.filter(p =>
                p.name.toLowerCase().includes(s) ||
                p.desc.toLowerCase().includes(s) ||
                p.age.toLowerCase().includes(s)
            );
        }
        if (state.familyFilters.length > 0) {
            list = list.filter(p => state.familyFilters.includes(p.family));
        }
        if (state.motivFilters.length > 0) {
            list = list.filter(p => state.motivFilters.includes(p.m1) || state.motivFilters.includes(p.m2));
        }
        if (state.sortCol) {
            list = [...list].sort((a, b) => {
                let va = a[state.sortCol], vb = b[state.sortCol];
                if (typeof va === 'string') { va = va.toLowerCase(); vb = vb.toLowerCase(); }
                if (va < vb) return state.sortDir === 'asc' ? -1 : 1;
                if (va > vb) return state.sortDir === 'asc' ? 1 : -1;
                return 0;
            });
        }
        return list;
    }

    // --- Render Cards ---
    function renderCards(list) {
        let html = '';
        const shownFamilies = state.familyFilters.length > 0 ? state.familyFilters : Object.keys(families);
        shownFamilies.forEach(fKey => {
            const fam = families[fKey];
            const items = list.filter(p => p.family === fKey);
            if (items.length === 0) return;
            html += `<div class="np-cat-head" style="background:${fam.bg}">
                <h3 style="color:${fam.color}">${fam.icon} ${fam.label}</h3>
                <span class="np-cnt" style="background:${fam.color}20;color:${fam.color}">${items.length}</span>
            </div>`;
            html += '<div class="np-grid">';
            items.forEach(p => {
                html += `<div class="np-card" style="border-left:3px solid ${fam.color}" onclick="NP.openPersona(${p.id})">
                    <div class="np-card-top">
                        <span class="np-card-num" style="background:${fam.color}">${p.id}</span>
                        <span class="np-card-name">${p.name}</span>
                    </div>
                    <div class="np-card-desc"><strong>${p.age}</strong> — ${p.desc}</div>
                    <div class="np-card-bottom">
                        <div class="np-tags">${tag(p.m1)}${tag(p.m2)}</div>
                        ${dots(p.conscience)}
                    </div>
                </div>`;
            });
            html += '</div>';
        });
        return html;
    }

    // --- Render Table ---
    function renderTable(list) {
        let html = '<div class="np-table-wrap"><table class="np-table"><thead><tr>';
        const cols = [
            { key: 'id', label: '#', sortable: true },
            { key: 'name', label: 'Persona', sortable: true },
            { key: 'family', label: 'Famille', sortable: true },
            { key: 'age', label: 'Âge', sortable: true },
            { key: 'motiv', label: 'Motivations', sortable: false },
            { key: 'conscience', label: 'Conscience', sortable: true },
            { key: 'desc', label: 'Description', sortable: false },
        ];
        cols.forEach(c => {
            const cls = c.sortable
                ? (state.sortCol === c.key ? 'np-th-sorted' : '')
                : 'np-th-nosort';
            const onclick = c.sortable ? `onclick="NP.sort('${c.key}')"` : '';
            const icon = c.sortable ? sortIcon(c.key) : '';
            html += `<th class="${cls}" ${onclick}>${c.label}${icon}</th>`;
        });
        html += '</tr></thead><tbody>';
        list.forEach(p => {
            const fam = families[p.family];
            html += `<tr onclick="NP.openPersona(${p.id})">
                <td><span class="np-card-num" style="background:${fam.color};width:22px;height:22px;font-size:10px">${p.id}</span></td>
                <td class="np-td-name">${p.name}</td>
                <td>${familyPill(p.family, true)}</td>
                <td style="white-space:nowrap;font-size:11px">${p.age}</td>
                <td><div class="np-tags">${tag(p.m1, true)}${tag(p.m2, true)}</div></td>
                <td><div style="display:flex;align-items:center;gap:6px">${dots(p.conscience)}<span style="font-size:9px;color:#94a3b8">${p.conscience}/5</span></div></td>
                <td style="font-size:11px;color:#64748b;max-width:260px">${p.desc}</td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        return html;
    }

    // --- Render Stats ---
    function renderStats(list) {
        const motCounts = {};
        list.forEach(p => {
            motCounts[p.m1] = (motCounts[p.m1] || 0) + 1;
            motCounts[p.m2] = (motCounts[p.m2] || 0) + 1;
        });
        let motivHtml = '';
        Object.entries(motCounts).sort((a, b) => b[1] - a[1]).forEach(([mot, count]) => {
            const m = motivations[mot] || { bg: '#ddd', color: '#999' };
            motivHtml += `<span class="np-stats-motiv-item">
                <span class="np-sm-dot" style="background:${m.bg};border:1px solid ${m.color}"></span>
                <span class="np-sm-label">${mot}</span>
                <span class="np-sm-val">${count}</span>
            </span>`;
        });
        $stats.innerHTML = `
            <div><span class="np-stats-count">${list.length}<span>persona${list.length > 1 ? 's' : ''} affichés</span></span></div>
            <div class="np-stats-motiv">${motivHtml}</div>
            <div class="np-stats-hint">Cliquez sur un persona → fiche complète NeuroPersona + stratégie ANCRE</div>
        `;
    }

    // --- Master render ---
    function render() {
        const list = getFiltered();
        $content.innerHTML = state.view === 'cards' ? renderCards(list) : renderTable(list);
        renderStats(list);

        // Update filter button states
        document.querySelectorAll('.np-filter-btn').forEach(btn => {
            const type = btn.dataset.filterType;
            const val = btn.dataset.filterValue;
            const active = type === 'family'
                ? state.familyFilters.includes(val)
                : state.motivFilters.includes(val);
            if (active) {
                btn.classList.add('np-filter-active');
                const color = type === 'family' ? families[val].color : motivations[val]?.color || '#999';
                const bg = type === 'family' ? families[val].bg : motivations[val]?.bg || '#f0f0f0';
                btn.style.borderColor = color;
                btn.style.background = bg;
            } else {
                btn.classList.remove('np-filter-active');
                btn.style.borderColor = 'transparent';
                btn.style.background = '';
            }
        });

        // Toggle buttons
        document.querySelectorAll('.np-toggle button').forEach(btn => {
            btn.classList.toggle('np-active', btn.dataset.view === state.view);
        });

        // Reset button
        const hasFilters = state.familyFilters.length > 0 || state.motivFilters.length > 0 || state.search;
        $resetBtn.style.display = hasFilters ? '' : 'none';
    }

    // --- Public methods ---
    function setView(v) {
        state.view = v;
        render();
    }

    function toggleFilter(type, val) {
        const arr = type === 'family' ? 'familyFilters' : 'motivFilters';
        const idx = state[arr].indexOf(val);
        if (idx >= 0) state[arr].splice(idx, 1);
        else state[arr].push(val);
        render();
    }

    function resetFilters() {
        state.familyFilters = [];
        state.motivFilters = [];
        state.search = '';
        $search.value = '';
        render();
    }

    function sort(col) {
        if (state.sortCol === col) {
            state.sortDir = state.sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            state.sortCol = col;
            state.sortDir = 'asc';
        }
        render();
    }

    function openPersona(id) {
        const p = personas.find(x => x.id === id);
        if (!p) return;
        const fam = families[p.family];

        $panelTitle.textContent = 'Fiche NeuroPersona';
        $panelBody.innerHTML = `
            <div class="np-panel-persona-header">
                <span class="np-card-num" style="background:${fam.color}">${p.id}</span>
                <div>
                    <div style="font-size:15px;font-weight:700;color:#1a1a2e">${p.name}</div>
                    <div style="font-size:11px;color:#64748b;margin-top:2px">${fam.icon} ${fam.label} · ${p.age}</div>
                </div>
            </div>
            <div class="np-panel-loading">
                <div class="np-spinner"></div>
                <div style="font-size:12px">Génération de la fiche IA…</div>
            </div>
        `;
        $panel.classList.add('np-panel--open');

        // Appel API Anthropic
        const prompt = buildPrompt(p);
        fetchAIFiche(p, prompt);
    }

    function closePanel() {
        $panel.classList.remove('np-panel--open');
    }

    function buildPrompt(p) {
        const fam = families[p.family];
        return `Tu es un expert en neuromarketing immobilier. Génère la fiche complète du NeuroPersona suivant en HTML formaté (balises h2, h3, ul, li, strong, em).

PERSONA: ${p.name} (#${p.id})
FAMILLE: ${fam.label}
ÂGE: ${p.age}
DESCRIPTION: ${p.desc}
MOTIVATION PRIMAIRE: ${p.m1}
MOTIVATION SECONDAIRE: ${p.m2}
NIVEAU DE CONSCIENCE (Schwartz): ${p.conscience}/5 — ${conscienceLabels[p.conscience]}

Structure ta réponse avec ces sections :
1. **Profil détaillé** — Situation, contexte de vie, déclencheur d'action
2. **Motivations profondes** — Analyse ${p.m1} + ${p.m2}, ce qui drive vraiment la décision
3. **Peurs et freins** — Les 4-5 peurs principales qui bloquent le passage à l'action
4. **Objections courantes** — Les phrases exactes que ce persona dit pour repousser
5. **Messages clés / Accroches** — 5 accroches copywriting qui parlent à ce persona
6. **Contenus recommandés** — Articles, vidéos, emails, posts réseaux sociaux adaptés
7. **Stratégie ANCRE** — Approche en 6 étapes (Ancrage, Narration, Connexion, Résolution, Engagement, Suivi)
8. **Scoring CRM** — Critères pour identifier ce persona dans le CRM

Réponds directement en HTML, pas de markdown. Sois concret et actionnable.`;
    }

    async function fetchAIFiche(p, prompt) {
        const fam = families[p.family];
        try {
            // Appel vers l'API interne du panel admin
            const response = await fetch('/admin/api/neuropersona/neuropersona.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    persona_id: p.id,
                    prompt: prompt,
                })
            });

            if (!response.ok) {
                throw new Error(`Erreur ${response.status}`);
            }

            const data = await response.json();

            if (data.success && data.html) {
                document.querySelector('.np-panel-loading').outerHTML = `
                    <div class="np-panel-content">${data.html}</div>
                `;
            } else {
                throw new Error(data.error || 'Réponse invalide');
            }
        } catch (err) {
            console.error('NP AI Error:', err);
            // Fallback : affiche les infos statiques
            const fallback = `
                <div class="np-panel-content">
                    <p style="color:#dc2626;font-size:12px;margin-bottom:14px">⚠ Impossible de contacter l'IA. Fiche statique affichée.</p>
                    <h2>Profil</h2>
                    <p><strong>${p.age}</strong> — ${p.desc}</p>
                    <h2>Motivations</h2>
                    <p>Primaire : <em>${p.m1}</em> · Secondaire : <em>${p.m2}</em></p>
                    <h2>Niveau de conscience</h2>
                    <p>${p.conscience}/5 — ${conscienceLabels[p.conscience]}</p>
                    <h3>Ce que ça signifie</h3>
                    <ul>
                        <li><strong>1</strong> — Ne sait pas qu'il a un problème</li>
                        <li><strong>2</strong> — Conscient du problème, pas de la solution</li>
                        <li><strong>3</strong> — Cherche activement des solutions</li>
                        <li><strong>4</strong> — Compare les options disponibles</li>
                        <li><strong>5</strong> — Prêt à passer à l'action</li>
                    </ul>
                </div>
            `;
            const loader = document.querySelector('.np-panel-loading');
            if (loader) loader.outerHTML = fallback;
        }
    }

    // --- Init ---
    $search.addEventListener('input', (e) => {
        state.search = e.target.value;
        render();
    });

    // Initial render
    render();

    // Public API
    return { setView, toggleFilter, resetFilters, sort, openPersona, closePanel };
})();
</script>