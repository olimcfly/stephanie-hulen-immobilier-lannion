<?php
/**
 * Header Renderer - Frontend
 * /public/includes/header-render.php
 * 
 * Génère le HTML du header à partir des données du Builder Pro
 * Utilise le logo/favicon depuis les settings
 * 
 * Usage: include ce fichier dans header.php ou directement dans les pages
 */

require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/site-identity.php';

/**
 * Charge les données du header depuis la base de données
 * @return array|null
 */
function loadHeaderData() {
    try {
        $db = Database::getInstance();
        
        // Charger la page header depuis builder_pages
        $stmt = $db->prepare("
            SELECT id, title, slug, type, html_content, css_content, 
                   menu_items, settings, status 
            FROM builder_pages 
            WHERE type = 'header' AND status = 'active' 
            ORDER BY id ASC 
            LIMIT 1
        ");
        $stmt->execute();
        $header = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$header) {
            // Fallback : chercher dans les pages normales
            $stmt = $db->prepare("
                SELECT id, title, slug, content as html_content 
                FROM pages 
                WHERE slug = 'header' AND status = 'published' 
                LIMIT 1
            ");
            $stmt->execute();
            $header = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $header;
    } catch (PDOException $e) {
        error_log("Header load error: " . $e->getMessage());
        return null;
    }
}

/**
 * Génère le HTML du header complet
 * @param array $options Options de rendu
 * @return string HTML
 */
function renderSiteHeader($options = []) {
    $headerData = loadHeaderData();
    $currentPage = basename($_SERVER['REQUEST_URI'], '.php');
    $currentPage = $currentPage ?: 'index';
    
    // ── Récupérer le menu ──
    $menuItems = [];
    if ($headerData && !empty($headerData['menu_items'])) {
        $menuItems = json_decode($headerData['menu_items'], true) ?: [];
    }
    
    // Si pas de menu en DB, menu par défaut
    if (empty($menuItems)) {
        $menuItems = [
            ['label' => 'Accueil',   'url' => '/',              'type' => 'link'],
            ['label' => 'Acheter',   'url' => '/acheter',       'type' => 'link'],
            ['label' => 'Vendre',    'url' => '/vendre',        'type' => 'link'],
            ['label' => 'Estimer',   'url' => '/estimer',       'type' => 'link'],
            ['label' => 'Secteurs',  'url' => '/secteurs',      'type' => 'link'],
            ['label' => 'Contact',   'url' => '/contact',       'type' => 'cta'],
        ];
    }
    
    // ── CSS custom du builder ──
    $customCSS = '';
    if ($headerData && !empty($headerData['css_content'])) {
        $customCSS = $headerData['css_content'];
    }
    
    // ── HTML custom du builder ──
    $hasCustomHTML = $headerData && !empty($headerData['html_content']) && trim($headerData['html_content']) !== '';
    
    // ══════════════════════════════════════════════
    // CONSTRUCTION DU HTML
    // ══════════════════════════════════════════════
    
    ob_start();
    ?>
    
    <!-- ═══ Header CSS ═══ -->
    <link rel="stylesheet" href="/public/assets/css/header.css">
    <?php if ($customCSS): ?>
    <style><?= $customCSS ?></style>
    <?php endif; ?>
    
    <?php if ($hasCustomHTML): ?>
        <!-- ═══ Header Custom (Builder Pro) ═══ -->
        <?= $headerData['html_content'] ?>
    <?php else: ?>
        <!-- ═══ Header Standard ═══ -->
        <header class="site-header" id="siteHeader">
            <div class="header-inner">
                
                <!-- Logo -->
                <div class="header-logo">
                    <?= render_site_logo([
                        'link' => true,
                        'class' => ''
                    ]) ?>
                </div>
                
                <!-- Navigation -->
                <nav class="main-nav" id="mainNav">
                    <?php foreach ($menuItems as $item): 
                        $url = $item['url'] ?? '#';
                        $label = $item['label'] ?? '';
                        $isCta = ($item['type'] ?? '') === 'cta' 
                                 || stripos($label, 'contact') !== false;
                        
                        // Déterminer si c'est la page active
                        $isActive = false;
                        $cleanUrl = rtrim($url, '/');
                        $currentUri = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
                        if ($cleanUrl === $currentUri || ($cleanUrl === '' && $currentUri === '')) {
                            $isActive = true;
                        }
                        
                        $classes = [];
                        if ($isCta) $classes[] = 'nav-cta';
                        if ($isActive) $classes[] = 'active';
                        $classStr = implode(' ', $classes);
                    ?>
                        <a href="<?= htmlspecialchars($url) ?>" 
                           <?= $classStr ? 'class="' . $classStr . '"' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
                
                <!-- Hamburger Mobile -->
                <button class="menu-toggle" id="menuToggle" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </header>
    <?php endif; ?>
    
    <!-- ═══ Header JS ═══ -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // ── Menu mobile toggle ──
        const toggle = document.getElementById('menuToggle');
        const nav = document.getElementById('mainNav');
        
        if (toggle && nav) {
            toggle.addEventListener('click', function() {
                this.classList.toggle('active');
                nav.classList.toggle('open');
                document.body.style.overflow = nav.classList.contains('open') ? 'hidden' : '';
            });
            
            // Fermer au clic sur un lien
            nav.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    toggle.classList.remove('active');
                    nav.classList.remove('open');
                    document.body.style.overflow = '';
                });
            });
        }
        
        // ── Sticky header avec effet scroll ──
        const header = document.getElementById('siteHeader');
        if (header) {
            let lastScroll = 0;
            window.addEventListener('scroll', function() {
                const currentScroll = window.scrollY;
                if (currentScroll > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
                lastScroll = currentScroll;
            }, { passive: true });
        }
    });
    </script>
    
    <?php
    return ob_get_clean();
}