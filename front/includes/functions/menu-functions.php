<?php
/**
 * ========================================
 * FONCTIONS MENUS - HELPERS FRONT-END
 * ========================================
 * 
 * Fichier: /includes/functions/menu-functions.php
 * 
 * Utilisation dans le header/footer:
 * 
 * <?php include_once 'includes/functions/menu-functions.php'; ?>
 * 
 * Dans le header:
 * <?php echo render_menu('header-main'); ?>
 * 
 * Dans le footer:
 * <?php echo render_footer(); ?>
 * 
 * ========================================
 */

// Connexion BDD si pas déjà fait
function getMenuPDO() {
    global $pdo;
    
    if (isset($pdo) && $pdo instanceof PDO) {
        return $pdo;
    }
    
    // Chercher le fichier config
    $paths = [
        __DIR__ . '/../../config/config.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/config.php',
        dirname(__DIR__, 2) . '/config/config.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            break;
        }
    }
    
    if (!defined('DB_HOST')) {
        return null;
    }
    
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        return $pdo;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Récupère les items d'un menu par son slug
 */
function get_menu_items($menuSlug) {
    $pdo = getMenuPDO();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("
            SELECT mi.* FROM menu_items mi
            JOIN menus m ON mi.menu_id = m.id
            WHERE m.slug = ? AND mi.is_active = 1
            ORDER BY mi.position
        ");
        $stmt->execute([$menuSlug]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Récupère un paramètre du site
 */
function get_setting($key, $default = '') {
    $pdo = getMenuPDO();
    if (!$pdo) return $default;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Récupère plusieurs paramètres par groupe
 */
function get_settings_by_group($group) {
    $pdo = getMenuPDO();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM site_settings WHERE setting_group = ?");
        $stmt->execute([$group]);
        $results = $stmt->fetchAll();
        $settings = [];
        foreach ($results as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Rend un menu HTML
 * 
 * @param string $menuSlug - Slug du menu (ex: 'header-main', 'footer-col1')
 * @param array $options - Options de rendu
 * @return string HTML du menu
 */
function render_menu($menuSlug, $options = []) {
    $defaults = [
        'class' => 'nav-menu',
        'item_class' => 'nav-item',
        'link_class' => 'nav-link',
        'active_class' => 'active',
        'wrapper' => 'ul',
        'item_wrapper' => 'li',
        'show_icons' => true
    ];
    
    $opts = array_merge($defaults, $options);
    $items = get_menu_items($menuSlug);
    
    if (empty($items)) {
        return '';
    }
    
    $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
    $html = "<{$opts['wrapper']} class=\"{$opts['class']}\">";
    
    foreach ($items as $item) {
        $isActive = ($currentUrl === $item['url'] || 
                    ($item['url'] !== '/' && strpos($currentUrl, $item['url']) === 0));
        
        $itemClasses = $opts['item_class'];
        $linkClasses = $opts['link_class'];
        
        if ($isActive) {
            $itemClasses .= ' ' . $opts['active_class'];
            $linkClasses .= ' ' . $opts['active_class'];
        }
        
        if (!empty($item['css_class'])) {
            $linkClasses .= ' ' . htmlspecialchars($item['css_class']);
        }
        
        $target = $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '';
        $icon = ($opts['show_icons'] && !empty($item['icon'])) 
            ? '<i class="' . htmlspecialchars($item['icon']) . '"></i> ' 
            : '';
        
        $html .= "<{$opts['item_wrapper']} class=\"{$itemClasses}\">";
        $html .= "<a href=\"" . htmlspecialchars($item['url']) . "\" class=\"{$linkClasses}\"{$target}>";
        $html .= $icon . htmlspecialchars($item['title']);
        $html .= "</a>";
        $html .= "</{$opts['item_wrapper']}>";
    }
    
    $html .= "</{$opts['wrapper']}>";
    
    return $html;
}

/**
 * Rend le menu header complet avec structure HTML
 */
function render_header_nav($logoUrl = '/', $logoText = 'LOGO') {
    $items = get_menu_items('header-main');
    $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
    
    ob_start();
    ?>
    <nav class="main-nav">
        <div class="nav-container">
            <a href="<?php echo htmlspecialchars($logoUrl); ?>" class="nav-logo">
                <?php echo htmlspecialchars($logoText); ?>
            </a>
            
            <button class="nav-toggle" aria-label="Menu" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <ul class="nav-menu" id="navMenu">
                <?php foreach ($items as $item): 
                    $isActive = ($currentUrl === $item['url']);
                    $target = $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : '';
                    $classes = 'nav-link' . ($isActive ? ' active' : '') . (!empty($item['css_class']) ? ' ' . $item['css_class'] : '');
                ?>
                <li class="nav-item">
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" class="<?php echo $classes; ?>"<?php echo $target; ?>>
                        <?php if (!empty($item['icon'])): ?>
                            <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($item['title']); ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </nav>
    
    <style>
    .main-nav {
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        z-index: 1000;
    }
    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 70px;
    }
    .nav-logo {
        font-size: 24px;
        font-weight: 700;
        color: #6366f1;
        text-decoration: none;
    }
    .nav-menu {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: 8px;
    }
    .nav-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 16px;
        color: #1e293b;
        text-decoration: none;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .nav-link:hover, .nav-link.active {
        background: #eef2ff;
        color: #6366f1;
    }
    .nav-link.btn-cta {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white !important;
    }
    .nav-link.btn-cta:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(99,102,241,0.4);
    }
    .nav-toggle {
        display: none;
        flex-direction: column;
        gap: 5px;
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
    }
    .nav-toggle span {
        width: 25px;
        height: 3px;
        background: #1e293b;
        border-radius: 2px;
        transition: all 0.3s;
    }
    @media (max-width: 768px) {
        .nav-toggle { display: flex; }
        .nav-menu {
            display: none;
            position: absolute;
            top: 70px;
            left: 0;
            right: 0;
            background: white;
            flex-direction: column;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .nav-menu.active { display: flex; }
        .nav-link { padding: 12px 16px; }
    }
    </style>
    
    <script>
    function toggleMobileMenu() {
        document.getElementById('navMenu').classList.toggle('active');
    }
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Rend le footer complet
 */
function render_footer() {
    $settings = get_settings_by_group('footer');
    $social = get_settings_by_group('social');
    
    $col1 = get_menu_items('footer-col1');
    $col2 = get_menu_items('footer-col2');
    $col3 = get_menu_items('footer-col3');
    
    ob_start();
    ?>
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-grid">
                <!-- Colonne Entreprise -->
                <div class="footer-col footer-about">
                    <h4><?php echo htmlspecialchars($settings['footer_company_name'] ?? 'Notre Entreprise'); ?></h4>
                    <?php if (!empty($settings['footer_description'])): ?>
                        <p><?php echo htmlspecialchars($settings['footer_description']); ?></p>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['footer_address']) || !empty($settings['footer_phone']) || !empty($settings['footer_email'])): ?>
                    <div class="footer-contact">
                        <?php if (!empty($settings['footer_address'])): ?>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo nl2br(htmlspecialchars($settings['footer_address'])); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($settings['footer_phone'])): ?>
                            <p><i class="fas fa-phone"></i> <a href="tel:<?php echo preg_replace('/[^0-9+]/', '', $settings['footer_phone']); ?>"><?php echo htmlspecialchars($settings['footer_phone']); ?></a></p>
                        <?php endif; ?>
                        <?php if (!empty($settings['footer_email'])): ?>
                            <p><i class="fas fa-envelope"></i> <a href="mailto:<?php echo htmlspecialchars($settings['footer_email']); ?>"><?php echo htmlspecialchars($settings['footer_email']); ?></a></p>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Réseaux sociaux -->
                    <?php if (!empty(array_filter($social))): ?>
                    <div class="footer-social">
                        <?php if (!empty($social['social_facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($social['social_facebook']); ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_instagram'])): ?>
                            <a href="<?php echo htmlspecialchars($social['social_instagram']); ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_linkedin'])): ?>
                            <a href="<?php echo htmlspecialchars($social['social_linkedin']); ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($social['social_youtube'])): ?>
                            <a href="<?php echo htmlspecialchars($social['social_youtube']); ?>" target="_blank" rel="noopener" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Colonne Services -->
                <?php if (!empty($col1)): ?>
                <div class="footer-col">
                    <h4>Services</h4>
                    <ul>
                        <?php foreach ($col1 as $item): ?>
                        <li><a href="<?php echo htmlspecialchars($item['url']); ?>"<?php echo $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo htmlspecialchars($item['title']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Colonne Ressources -->
                <?php if (!empty($col2)): ?>
                <div class="footer-col">
                    <h4>Ressources</h4>
                    <ul>
                        <?php foreach ($col2 as $item): ?>
                        <li><a href="<?php echo htmlspecialchars($item['url']); ?>"<?php echo $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo htmlspecialchars($item['title']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Colonne Légal -->
                <?php if (!empty($col3)): ?>
                <div class="footer-col">
                    <h4>Informations</h4>
                    <ul>
                        <?php foreach ($col3 as $item): ?>
                        <li><a href="<?php echo htmlspecialchars($item['url']); ?>"<?php echo $item['target'] === '_blank' ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo htmlspecialchars($item['title']); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="footer-bottom">
                <p><?php echo htmlspecialchars($settings['footer_copyright'] ?? '© ' . date('Y') . ' Tous droits réservés'); ?></p>
            </div>
        </div>
    </footer>
    
    <style>
    .site-footer {
        background: linear-gradient(135deg, #1e293b, #0f172a);
        color: #94a3b8;
        padding: 60px 0 0;
        margin-top: 60px;
    }
    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }
    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 40px;
    }
    @media (max-width: 1024px) {
        .footer-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 640px) {
        .footer-grid { grid-template-columns: 1fr; }
    }
    .footer-col h4 {
        color: white;
        font-size: 18px;
        margin-bottom: 20px;
        font-weight: 600;
    }
    .footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .footer-col ul li {
        margin-bottom: 12px;
    }
    .footer-col ul a {
        color: #94a3b8;
        text-decoration: none;
        transition: color 0.2s;
    }
    .footer-col ul a:hover {
        color: #6366f1;
    }
    .footer-about p {
        margin-bottom: 16px;
        line-height: 1.6;
    }
    .footer-contact {
        margin: 20px 0;
    }
    .footer-contact p {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 10px;
    }
    .footer-contact i {
        color: #6366f1;
        width: 16px;
        margin-top: 3px;
    }
    .footer-contact a {
        color: #94a3b8;
        text-decoration: none;
    }
    .footer-contact a:hover {
        color: #6366f1;
    }
    .footer-social {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }
    .footer-social a {
        width: 40px;
        height: 40px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        transition: all 0.2s;
    }
    .footer-social a:hover {
        background: #6366f1;
        transform: translateY(-3px);
    }
    .footer-bottom {
        border-top: 1px solid rgba(255,255,255,0.1);
        margin-top: 40px;
        padding: 20px 0;
        text-align: center;
    }
    .footer-bottom p {
        margin: 0;
        font-size: 14px;
    }
    </style>
    <?php
    return ob_get_clean();
}

/**
 * Raccourci pour juste les liens de menu (sans wrapper)
 */
function get_menu_links($menuSlug) {
    return get_menu_items($menuSlug);
}

/**
 * Vérifie si un menu a des items
 */
function has_menu_items($menuSlug) {
    return !empty(get_menu_items($menuSlug));
}