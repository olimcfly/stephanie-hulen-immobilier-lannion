<?php
// ============================================================
// admin/layout/header.php — IMMO LOCAL+ v8.6
// Mobile-first — s'arrête après avoir ouvert .admin-layout
// ============================================================

$advisorName = $advisorName ?? 'Mon espace';
$advisorCity = $advisorCity ?? '';

$headerLinks = [
    'api-keys'        => ['fa-key',            'Clés API'],
    'ai-settings'     => ['fa-robot',          'Paramètres IA'],
    'settings'        => ['fa-sliders',        'Réglages'],
    'advisor-context' => ['fa-circle-user',    'Mon profil'],
    'ressources'      => ['fa-circle-question','Aide'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= htmlspecialchars(html_entity_decode($pageTitle ?? 'Dashboard')) ?> — <?= htmlspecialchars($advisorName) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/admin/assets/css/admin-components.css">
<link rel="stylesheet" href="/admin/assets/css/modules.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script src="/admin/assets/js/admin-components.js" defer></script>

<style>
/* ============================================================
   RESET + TOKENS
   ============================================================ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    /* Layout */
    --hd-h:       52px;

    /* Couleurs */
    --bg:         #f1f5f9;
    --surface:    #ffffff;
    --surface-2:  #f8fafc;
    --surface-3:  #f1f5f9;
    --border:     #e2e8f0;
    --text:       #0f172a;
    --text-2:     #475569;
    --text-3:     #94a3b8;
    --accent:     #6366f1;
    --accent-2:   #818cf8;
    --accent-bg:  #eef2ff;
    --green:      #10b981;
    --green-bg:   #f0fdf4;
    --red:        #ef4444;
    --red-bg:     #fff1f2;
    --amber:      #f59e0b;
    --amber-bg:   #fffbeb;
    --gold:       #eab308;

    /* Ombres */
    --shadow-sm:  0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
    --shadow:     0 4px 16px rgba(0,0,0,.09);

    /* Rayons */
    --radius:     8px;
    --radius-lg:  14px;

    /* Typo */
    --font:       'DM Sans', system-ui, sans-serif;
    --mono:       'JetBrains Mono', monospace;
}

html, body {
    height: 100%;
    overflow: hidden;
    font-family: var(--font);
    background: var(--bg);
    color: var(--text);
    font-size: 14px;
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
}

/* ============================================================
   LAYOUT GLOBAL
   ============================================================ */
.admin-layout {
    display: flex;
    height: 100vh;
    width: 100vw;
    overflow: hidden;
}

/* ── Colonne droite ────────────────────────────────────────── */
.admin-right {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    height: 100vh;
    overflow: hidden;
}

/* ── Main ──────────────────────────────────────────────────── */
.admin-main {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    background: var(--bg);
    min-height: 0;
    /* Safe area mobile (notch bas) */
    padding-bottom: env(safe-area-inset-bottom, 0);
}
.admin-main::-webkit-scrollbar { width: 4px; }
.admin-main::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }

/* ── Module wrapper ────────────────────────────────────────── */
.module-wrap { padding: 24px 28px 60px; }

/* ============================================================
   TOPBAR
   ============================================================ */
.admin-topbar {
    height: var(--hd-h);
    flex-shrink: 0;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 0 14px 0 16px;
    box-shadow: var(--shadow-sm);
    z-index: 50;
    /* Safe area mobile (notch haut) */
    padding-top: env(safe-area-inset-top, 0);
}

/* Bouton hamburger */
.tb-menu-btn {
    display: none;
    width: 36px; height: 36px;
    align-items: center; justify-content: center;
    background: none; border: none; cursor: pointer;
    border-radius: var(--radius);
    color: var(--text-2);
    font-size: 16px;
    transition: background .14s;
    flex-shrink: 0;
    -webkit-tap-highlight-color: transparent;
}
.tb-menu-btn:hover { background: var(--surface-3); }
.tb-menu-btn:active { background: var(--border); }

/* Breadcrumb */
.tb-breadcrumb {
    display: flex; align-items: center; gap: 5px;
    font-size: 13px; color: var(--text-3);
    flex-shrink: 0; min-width: 0;
}
.tb-breadcrumb a {
    color: var(--text-2); text-decoration: none;
    font-weight: 600; transition: color .14s;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 160px;
}
.tb-breadcrumb a:hover { color: var(--accent); }
.tb-breadcrumb .tb-sep  { color: var(--border); font-size: 18px; line-height: 1; }
.tb-breadcrumb strong   { color: var(--text); font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 180px; display: block; }

/* Spacer */
.tb-spacer { flex: 1; min-width: 0; }

/* Barre de recherche */
.tb-search {
    display: flex; align-items: center; gap: 8px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 6px 12px;
    width: 200px;
    transition: border-color .15s, width .22s ease, box-shadow .15s;
    flex-shrink: 0;
}
.tb-search:focus-within {
    border-color: var(--accent);
    width: 260px;
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}
.tb-search i   { font-size: 11px; color: var(--text-3); flex-shrink: 0; }
.tb-search input {
    background: none; border: none; outline: none;
    font-size: 12.5px; color: var(--text);
    width: 100%; font-family: var(--font);
}
.tb-search input::placeholder { color: var(--text-3); }

/* Icônes nav */
.tb-icons { display: flex; align-items: center; gap: 1px; flex-shrink: 0; }
.tb-icon {
    width: 34px; height: 34px;
    border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: var(--text-3);
    text-decoration: none;
    transition: background .14s, color .14s;
    position: relative;
    -webkit-tap-highlight-color: transparent;
}
.tb-icon:hover  { background: var(--surface-3); color: var(--text); }
.tb-icon.active { background: var(--accent-bg); color: var(--accent); }

/* Tooltip */
.tb-icon[data-tip]:hover::after {
    content: attr(data-tip);
    position: absolute; bottom: -30px; left: 50%;
    transform: translateX(-50%);
    background: #1e293b; color: #fff;
    font-size: 10px; font-weight: 600;
    padding: 3px 8px; border-radius: 5px;
    white-space: nowrap; z-index: 9999; pointer-events: none;
}

/* Séparateur vertical */
.tb-sep-v { width: 1px; height: 20px; background: var(--border); margin: 0 3px; flex-shrink: 0; }

/* Logout */
.tb-icon-logout:hover { background: var(--red-bg); color: var(--red); }

/* Badge topbar */
.tb-badge {
    position: absolute; top: -4px; right: -4px;
    background: var(--red); color: #fff;
    border-radius: 20px; padding: 1px 5px;
    font-size: 9px; font-weight: 800; line-height: 1.4;
    min-width: 14px; text-align: center;
    border: 1.5px solid var(--surface);
}

/* ============================================================
   COMPOSANTS UTILITAIRES
   ============================================================ */

/* ── Page header ───────────────────────────────────────────── */
.page-hd {
    display: flex; align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.page-hd h1 { font-size: 20px; font-weight: 800; color: var(--text); letter-spacing: -.02em; }
.page-hd-sub { font-size: 12px; color: var(--text-3); margin-top: 3px; }

/* ── Boutons (set-btn compatibilité + btn nouveau) ─────────── */
.set-btn, .btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px; border-radius: var(--radius);
    font-size: 13px; font-weight: 600;
    cursor: pointer; border: 1px solid transparent;
    text-decoration: none; font-family: var(--font);
    transition: all .14s; white-space: nowrap;
    -webkit-tap-highlight-color: transparent;
}
.set-btn-sm, .btn-sm { padding: 6px 12px; font-size: 12px; }

.set-btn-p, .btn-p  { background: var(--accent); color: #fff; border-color: var(--accent); }
.set-btn-p:hover, .btn-p:hover { background: #4f46e5; color: #fff; }

.set-btn-s, .btn-s  { background: var(--surface); color: var(--text-2); border-color: var(--border); }
.set-btn-s:hover, .btn-s:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-bg); }

.btn-danger { background: var(--red-bg); color: var(--red); border-color: #fca5a5; }
.btn-danger:hover { background: var(--red); color: #fff; }

/* ── Badges ────────────────────────────────────────────────── */
.badge { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 700; }
.badge-green  { background: var(--green-bg);  color: var(--green); }
.badge-red    { background: var(--red-bg);    color: var(--red); }
.badge-amber  { background: var(--amber-bg);  color: var(--amber); }
.badge-accent { background: var(--accent-bg); color: var(--accent); }
.badge-gray   { background: var(--surface-3); color: var(--text-3); }

/* ── Stat card ─────────────────────────────────────────────── */
.stat-card  { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius-lg); padding: 16px 18px; display: flex; align-items: center; gap: 14px; box-shadow: var(--shadow-sm); }
.stat-icon  { width: 40px; height: 40px; border-radius: var(--radius); display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.stat-val   { font-size: 22px; font-weight: 900; line-height: 1; }
.stat-label { font-size: 11px; color: var(--text-3); margin-top: 3px; font-weight: 600; }

/* ── Tableau ───────────────────────────────────────────────── */
.tbl { width: 100%; border-collapse: collapse; font-size: 12.5px; }
.tbl th { padding: 9px 12px; text-align: left; font-size: 11px; font-weight: 700; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; border-bottom: 2px solid var(--border); background: var(--surface-2); }
.tbl td { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: middle; }
.tbl tr:last-child td { border-bottom: none; }
.tbl tr:hover td { background: var(--surface-2); }

/* ── Animations ────────────────────────────────────────────── */
.anim { animation: fadeUp .28s ease both; }
@keyframes fadeUp {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: none; }
}

/* ============================================================
   RESPONSIVE TOPBAR
   ============================================================ */

/* Tablet */
@media(max-width: 1024px) {
    .tb-search { width: 160px; }
    .tb-search:focus-within { width: 210px; }
}

/* Mobile < 768px */
@media(max-width: 767px) {
    /* Afficher le hamburger */
    .tb-menu-btn { display: flex; }

    /* Réduire le padding topbar */
    .admin-topbar { padding: 0 10px; gap: 4px; }

    /* Breadcrumb raccourci */
    .tb-breadcrumb a    { max-width: 120px; }
    .tb-breadcrumb strong { max-width: 130px; }

    /* Cacher la recherche sur très petits écrans, garder les icônes essentielles */
    .tb-search { display: none; }

    /* Tooltips désactivés sur mobile (pas de hover) */
    .tb-icon[data-tip]:hover::after { display: none; }

    /* Icônes légèrement plus grandes (zones tactiles) */
    .tb-icon { width: 38px; height: 38px; font-size: 15px; }
    .tb-menu-btn { width: 38px; height: 38px; }

    /* Module wrap */
    .module-wrap { padding: 14px 14px 40px; }
}

/* Mobile < 480px — masquer certaines icônes topbar non critiques */
@media(max-width: 479px) {
    /* Garder : messenger (badge), settings, profil, logout */
    /* Masquer : api-keys, ai-settings si trop chargé */
    .tb-icon-hide-xs { display: none; }
    .tb-sep-v        { display: none; }
    .tb-breadcrumb a { max-width: 90px; font-size: 12px; }
    .tb-breadcrumb strong { max-width: 100px; font-size: 12px; }
}
</style>
</head>
<body>
<div class="admin-layout">
