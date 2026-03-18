<?php
/**
 * Site Identity Helpers
 * /includes/site-identity.php
 * 
 * Table settings : colonnes key_name (varchar 100) et value (text)
 */

/**
 * Récupère une valeur de la table settings
 */
function get_site_setting($key, $default = '') {
    static $cache = [];
    
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT value FROM settings WHERE key_name = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $value = ($result && $result['value'] !== null && $result['value'] !== '') 
                 ? $result['value'] 
                 : $default;
        $cache[$key] = $value;
        return $value;
    } catch (PDOException $e) {
        return $default;
    }
}

/**
 * Met à jour une valeur dans settings
 */
function update_site_setting($key, $value) {
    try {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "INSERT INTO settings (key_name, value) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE value = ?"
        );
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (PDOException $e) {
        error_log("update_site_setting error: " . $e->getMessage());
        return false;
    }
}

/**
 * Retourne le HTML du logo
 * Image si uploadée, texte si pas d'image, jamais d'icône cassée
 */
function render_site_logo($options = []) {
    $link     = $options['link'] ?? true;
    $class    = $options['class'] ?? '';
    $maxWidth = $options['max_width'] ?? null;
    
    $logo      = get_site_setting('site_logo');
    $siteName  = get_site_setting('site_name', 'Eduardo De Sul');
    $logoWidth = $maxWidth ?: get_site_setting('site_logo_width', '180');
    
    $html = '';
    
    if ($link) {
        $html .= '<a href="/" class="site-logo-link ' . htmlspecialchars($class) . '">';
    }
    
    if ($logo && file_exists($_SERVER['DOCUMENT_ROOT'] . $logo)) {
        $html .= '<img src="' . htmlspecialchars($logo) . '" '
               . 'alt="' . htmlspecialchars($siteName) . '" '
               . 'class="site-logo-img" '
               . 'style="max-width: ' . intval($logoWidth) . 'px; height: auto;" '
               . 'loading="eager">';
    } else {
        $html .= '<span class="site-logo-text">' . htmlspecialchars($siteName) . '</span>';
    }
    
    if ($link) {
        $html .= '</a>';
    }
    
    return $html;
}

/**
 * Balises favicon ou rien
 */
function render_favicon_tags() {
    $favicon = get_site_setting('site_favicon');
    
    if (!$favicon || !file_exists($_SERVER['DOCUMENT_ROOT'] . $favicon)) {
        return '';
    }
    
    $ext = strtolower(pathinfo($favicon, PATHINFO_EXTENSION));
    $mimeTypes = [
        'ico' => 'image/x-icon',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
    ];
    $mime = $mimeTypes[$ext] ?? 'image/x-icon';
    $escaped = htmlspecialchars($favicon);
    
    $html  = '<link rel="icon" type="' . $mime . '" href="' . $escaped . '">' . "\n";
    $html .= '    <link rel="shortcut icon" href="' . $escaped . '">' . "\n";
    if ($ext === 'png') {
        $html .= '    <link rel="apple-touch-icon" href="' . $escaped . '">' . "\n";
    }
    
    return $html;
}

function get_logo_url() {
    $logo = get_site_setting('site_logo');
    if ($logo && file_exists($_SERVER['DOCUMENT_ROOT'] . $logo)) {
        return $logo;
    }
    return null;
}

function get_site_name() {
    return get_site_setting('site_name', 'Eduardo De Sul');
}