<?php
// /includes/builder-display.php - HELPER POUR AFFICHER HEADER/FOOTER/MENU

/**
 * Récupère la configuration du builder depuis la BD
 */
function getBuilderConfig() {
    global $pdo;
    
    try {
        $config = $pdo->query("SELECT * FROM builder_config LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if (!$config) {
            return [
                'header' => '',
                'footer' => '',
                'menu_items' => [],
                'theme_primary' => '#6366f1',
                'theme_secondary' => '#8b5cf6'
            ];
        }
        
        // Décoder les menu_items si c'est du JSON
        if (is_string($config['menu_items'])) {
            $config['menu_items'] = json_decode($config['menu_items'], true) ?: [];
        }
        
        return $config;
    } catch (Exception $e) {
        return [
            'header' => '',
            'footer' => '',
            'menu_items' => [],
            'theme_primary' => '#6366f1',
            'theme_secondary' => '#8b5cf6'
        ];
    }
}

/**
 * Affiche le header du site
 */
function displayHeader() {
    $config = getBuilderConfig();
    
    echo '<header class="site-header" style="background: linear-gradient(135deg, ' . htmlspecialchars($config['theme_primary']) . ' 0%, ' . htmlspecialchars($config['theme_secondary']) . ' 100%); color: white; padding: 20px 0;">';
    echo '<div class="header-container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center;">';
    
    // Logo/Titre
    echo '<div class="header-brand" style="font-size: 24px; font-weight: 800; letter-spacing: -0.5px;">';
    if ($config['header']) {
        echo $config['header'];
    } else {
        echo '🌍 IMMO LOCAL+';
    }
    echo '</div>';
    
    // Menu
    echo '<nav class="header-nav">';
    if (!empty($config['menu_items'])) {
        foreach ($config['menu_items'] as $item) {
            echo '<a href="' . htmlspecialchars($item['url'] ?? '#') . '" style="color: white; text-decoration: none; margin: 0 20px; font-weight: 500; transition: opacity 0.3s; opacity: 0.9;" onmouseover="this.style.opacity=\'1\'" onmouseout="this.style.opacity=\'0.9\'">';
            echo htmlspecialchars($item['label'] ?? 'Menu');
            echo '</a>';
        }
    }
    echo '</nav>';
    
    echo '</div>';
    echo '</header>';
}

/**
 * Affiche le footer du site
 */
function displayFooter() {
    $config = getBuilderConfig();
    
    echo '<footer class="site-footer" style="background: #1e293b; color: white; padding: 40px 0; margin-top: 80px;">';
    echo '<div class="footer-container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">';
    
    if ($config['footer']) {
        echo '<div style="margin-bottom: 20px;">';
        echo $config['footer'];
        echo '</div>';
    }
    
    echo '<div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; margin-top: 20px; text-align: center; font-size: 12px; color: rgba(255,255,255,0.6);">';
    echo '© ' . date('Y') . ' ÉCOSYSTÈME IMMO LOCAL+ - Tous droits réservés';
    echo '</div>';
    
    echo '</div>';
    echo '</footer>';
}

/**
 * Retourne uniquement le menu sous forme de tableau
 */
function getMenuItems() {
    $config = getBuilderConfig();
    return $config['menu_items'] ?? [];
}

/**
 * Retourne les couleurs du thème
 */
function getThemeColors() {
    $config = getBuilderConfig();
    return [
        'primary' => $config['theme_primary'] ?? '#6366f1',
        'secondary' => $config['theme_secondary'] ?? '#8b5cf6'
    ];
}

/**
 * Génère un CSS personnalisé basé sur les couleurs du builder
 */
function getThemeCSS() {
    $colors = getThemeColors();
    
    $css = ":root {
        --theme-primary: " . htmlspecialchars($colors['primary']) . ";
        --theme-secondary: " . htmlspecialchars($colors['secondary']) . ";
    }
    
    .btn-primary {
        background: linear-gradient(135deg, var(--theme-primary) 0%, var(--theme-secondary) 100%);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(99,102,241,0.3);
    }
    
    a {
        color: var(--theme-primary);
    }
    
    a:hover {
        opacity: 0.8;
    }";
    
    return $css;
}

/**
 * Injecte automatiquement le header, footer et CSS au page
 */
function initializeBuilder() {
    echo '<style>' . getThemeCSS() . '</style>';
}