<?php
/**
 * ════════════════════════════════════════════════════════════
 * admin/layout/wrapper.php — IMMO LOCAL+ v9.0
 * ════════════════════════════════════════════════════════════
 * 
 * LAYOUT PRINCIPAL - Simplifié
 * Charge : sidebar.php + contenu module
 * 
 * Variables attendues :
 * - $pageTitle     : Titre page
 * - $activeModule  : Module actif
 * - $advisorName   : Nom conseiller
 * - $advisorCity   : Ville
 * - $pdo           : Connexion DB
 * - $moduleContent : HTML du module (déjà bufferisé)
 * - $dashStats     : Stats pour le header (optionnel)
 */

if (defined('LAYOUT_WRAPPER_INCLUDED')) return;
define('LAYOUT_WRAPPER_INCLUDED', true);

// Valeurs par défaut
$pageTitle     = $pageTitle ?? 'IMMO LOCAL+';
$activeModule  = $activeModule ?? 'dashboard';
$advisorName   = $advisorName ?? 'Mon espace';
$advisorCity   = $advisorCity ?? '';
$advisorAvatar = $advisorAvatar ?? '';
$moduleContent = $moduleContent ?? '';
$dashStats     = $dashStats ?? [];
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
<link rel="stylesheet" href="/admin/assets/css/main.css">
<link rel="stylesheet" href="/admin/assets/css/sidebar.css">
<link rel="stylesheet" href="/admin/assets/css/admin-components.css">
<link rel="stylesheet" href="/admin/assets/css/modules.css">
<link rel="stylesheet" href="/admin/assets/css/dashboard.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script src="/admin/assets/js/admin-components.js" defer></script>
<script src="/admin/assets/js/admin_ui.js" defer></script>

<style>
/* ════════════════════════════════════════════════════════════
   RESET + TOKENS (depuis header.php)
   ════════════════════════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --hd-h:       52px;
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
    --shadow-sm:  0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
    --shadow:     0 4px 16px rgba(0,0,0,.09);
    --radius:     8px;
    --radius-lg:  14px;
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

.admin-layout { display: flex; height: 100vh; width: 100vw; overflow: hidden; }
.admin-right {
    flex: 1; min-width: 0;
    display: flex; flex-direction: column;
    height: 100vh; overflow: hidden;
}
.admin-main {
    flex: 1;
    overflow-y: auto; overflow-x: hidden;
    background: var(--bg); min-height: 0;
    padding-bottom: env(safe-area-inset-bottom, 0);
}
.admin-main::-webkit-scrollbar { width: 4px; }
.admin-main::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.module-wrap { padding: 24px 28px 60px; }

/* ════════════════════════════════════════════════════════════
   TOPBAR (depuis header.php, simplifié)
   ════════════════════════════════════════════════════════════ */
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
    padding-top: env(safe-area-inset-top, 0);
}

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
.tb-breadcrumb .tb-sep { color: var(--border); font-size: 18px; line-height: 1; }
.tb-breadcrumb strong {
    color: var(--text); font-weight: 700;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 180px; display: block;
}

.tb-spacer { flex: 1; min-width: 0; }

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
.tb-icon:hover { background: var(--surface-3); color: var(--text); }
.tb-icon.active { background: var(--accent-bg); color: var(--accent); }

.tb-icon[data-tip]:hover::after {
    content: attr(data-tip);
    position: absolute; bottom: -30px; left: 50%;
    transform: translateX(-50%);
    background: #1e293b; color: #fff;
    font-size: 10px; font-weight: 600;
    padding: 3px 8px; border-radius: 5px;
    white-space: nowrap; z-index: 9999; pointer-events: none;
}

.tb-sep-v { width: 1px; height: 20px; background: var(--border); margin: 0 3px; flex-shrink: 0; }
.tb-icon-logout:hover { background: var(--red-bg); color: var(--red); }
.tb-badge {
    position: absolute; top: -4px; right: -4px;
    background: var(--red); color: #fff;
    border-radius: 20px; padding: 1px 5px;
    font-size: 9px; font-weight: 800; line-height: 1.4;
    min-width: 14px; text-align: center;
    border: 1.5px solid var(--surface);
}

/* ════════════════════════════════════════════════════════════
   FOOTER
   ════════════════════════════════════════════════════════════ */
.admin-footer {
    padding: 1rem 2rem;
    background: var(--surface-2);
    border-top: 1px solid var(--border);
    text-align: center;
    font-size: 0.85rem;
    color: var(--text-3);
}

.footer-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.footer-links a {
    color: var(--accent);
    text-decoration: none;
    margin: 0 0.5rem;
}

/* ════════════════════════════════════════════════════════════
   RESPONSIVE
   ════════════════════════════════════════════════════════════ */
@media(max-width: 767px) {
    .tb-menu-btn { display: flex; }
    .admin-topbar { padding: 0 10px; gap: 4px; }
    .tb-breadcrumb a { max-width: 120px; }
    .tb-breadcrumb strong { max-width: 130px; }
    .tb-icon { width: 38px; height: 38px; font-size: 15px; }
    .tb-menu-btn { width: 38px; height: 38px; }
    .module-wrap { padding: 14px 14px 40px; }
}

@media(max-width: 479px) {
    .tb-sep-v { display: none; }
    .tb-breadcrumb a { max-width: 90px; font-size: 12px; }
    .tb-breadcrumb strong { max-width: 100px; font-size: 12px; }
}
</style>

</head>
<body>
<div class="admin-layout">

    <!-- ════════════════════════════════════════════════════════
         SIDEBAR
         ════════════════════════════════════════════════════════ -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- ════════════════════════════════════════════════════════
         COLONNE DROITE — Header + Main + Footer
         ════════════════════════════════════════════════════════ -->
    <div class="admin-right">

        <!-- TOPBAR ────────────────────────────────────────────── -->
        <header class="admin-topbar">
            <button class="tb-menu-btn" id="tbMenuBtn"><i class="fas fa-bars"></i></button>
            
            <div class="tb-breadcrumb">
                <a href="?page=dashboard"><?= htmlspecialchars($advisorName) ?><?= $advisorCity ? ' &middot; '.htmlspecialchars($advisorCity) : '' ?></a>
                <?php if ($activeModule !== 'dashboard'): ?>
                <span class="tb-sep">›</span>
                <strong><?= htmlspecialchars($pageTitle) ?></strong>
                <?php endif; ?>
            </div>
            
            <div class="tb-spacer"></div>
            
            <!-- Icônes nav ──────────────────────────────────────── -->
            <nav class="tb-icons">
                <?php
                $headerLinks = [
                    'messenger'       => ['fa-envelope',    'Messagerie'],
                    'leads'           => ['fa-user-plus',   'Leads'],
                    'settings'        => ['fa-sliders',     'Réglages'],
                    'advisor-context' => ['fa-circle-user', 'Mon profil'],
                ];
                foreach ($headerLinks as $page => [$icon, $tip]):
                    $badge = '';
                    if ($page === 'messenger' && !empty($dashStats['unread_msgs']))
                        $badge = '<span class="tb-badge">'.$dashStats['unread_msgs'].'</span>';
                    if ($page === 'leads' && !empty($dashStats['new_leads']))
                        $badge = '<span class="tb-badge">'.$dashStats['new_leads'].'</span>';
                ?>
                <a href="?page=<?= $page ?>" class="tb-icon<?= $activeModule === $page ? ' active' : '' ?>" data-tip="<?= htmlspecialchars($tip) ?>" style="position:relative">
                    <i class="fas <?= $icon ?>"></i><?= $badge ?>
                </a>
                <?php endforeach; ?>
                <span class="tb-sep-v"></span>
                <a href="/" target="_blank" class="tb-icon" data-tip="Voir le site"><i class="fas fa-arrow-up-right-from-square"></i></a>
                <a href="/admin/logout.php" class="tb-icon tb-icon-logout" data-tip="Déconnexion"><i class="fas fa-right-from-bracket"></i></a>
            </nav>
        </header>

        <!-- MAIN CONTENT ────────────────────────────────────────── -->
        <main class="admin-main">
            <div class="module-wrap" id="main-content">
                <?= $moduleContent ?>
            </div>
        </main>

        <!-- FOOTER ──────────────────────────────────────────────── -->
        <footer class="admin-footer">
            <div class="footer-content">
                <p>&copy; 2026 IMMO LOCAL+ • Gestion CRM immobilier</p>
                <div class="footer-links">
                    <a href="#">Aide</a> • 
                    <a href="#">Support</a> • 
                    <a href="#">Mentions légales</a>
                </div>
            </div>
        </footer>

    </div>

</div>

<!-- ════════════════════════════════════════════════════════
     SCRIPTS
     ════════════════════════════════════════════════════════ -->
<script>
// Gestion du menu hamburger
document.addEventListener('click', function (e) {
    const sb  = document.getElementById('sidebar');
    const btn = document.getElementById('tbMenuBtn');
    if (!sb?.classList.contains('open')) return;
    if (!sb.contains(e.target) && e.target !== btn && !btn?.contains(e.target)) {
        sbClose();
    }
});
</script>

</body>
</html>