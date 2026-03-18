<?php
/**
 * Menu Helper - Charger les menus dynamiquement depuis la DB
 * Chemin : /front/helpers/menu-helper.php
 * 
 * Usage:
 *   $headerMenu = getMenu('header-main');
 *   buildMenuHtml($headerMenu['items']);
 *   _findMenuUrl($items, 'Estimation', '/estimation'); // chercher URL par titre
 */

if (!function_exists('getMenu')) {
    function getMenu($slug, $pdo = null) {
        global $pdo;
        if (!$pdo) return [];
        
        try {
            // Récupérer le menu par slug
            $stmt = $pdo->prepare("SELECT * FROM menus WHERE slug = ? LIMIT 1");
            $stmt->execute([$slug]);
            $menu = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$menu) return [];
            
            // Récupérer les items du menu
            $itemsStmt = $pdo->prepare("
                SELECT * FROM menu_items 
                WHERE menu_id = ? AND is_active = 1 
                ORDER BY parent_id ASC, position ASC
            ");
            $itemsStmt->execute([$menu['id']]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $menu['items'] = $items;
            return $menu;
        } catch (Exception $e) {
            error_log("Menu Helper Error: " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('buildMenuHtml')) {
    /**
     * Générer HTML pour un menu
     * @param array $items - items du menu
     * @param int|null $parentId - parent_id pour filtrer (null = root items)
     * @param string $listClass - classe CSS pour <ul>
     * @return string HTML
     */
    function buildMenuHtml($items, $parentId = null, $listClass = 'navbar-nav') {
        $filtered = array_filter($items, fn($item) => ($item['parent_id'] ?? null) === $parentId);
        
        if (empty($filtered)) return '';
        
        $html = '<ul class="' . htmlspecialchars($listClass) . '">';
        
        foreach ($filtered as $item) {
            $hasChildren = count(array_filter($items, fn($i) => ($i['parent_id'] ?? null) === $item['id'])) > 0;
            
            $html .= '<li class="nav-item' . ($hasChildren ? ' dropdown' : '') . '">';
            
            // Lien principal
            $html .= '<a class="nav-link' . ($hasChildren ? ' dropdown-toggle' : '') . '" ';
            $html .= 'href="' . htmlspecialchars($item['url'] ?? '#') . '" ';
            if ($hasChildren) $html .= 'role="button" data-bs-toggle="dropdown" ';
            if ($item['target'] !== '_self') $html .= 'target="' . htmlspecialchars($item['target']) . '" ';
            $html .= '>';
            
            if ($item['icon']) {
                $html .= '<i class="' . htmlspecialchars($item['icon']) . '"></i> ';
            }
            $html .= htmlspecialchars($item['title']);
            $html .= '</a>';
            
            // Sous-menu si enfants
            if ($hasChildren) {
                $html .= buildMenuHtml($items, $item['id'], 'dropdown-menu');
            }
            
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        return $html;
    }
}

if (!function_exists('buildFooterMenuHtml')) {
    /**
     * Générer HTML pour menu footer (liste simple)
     * @param array $items - items du menu
     * @return string HTML
     */
    function buildFooterMenuHtml($items) {
        if (empty($items)) return '';
        
        $html = '<ul class="footer-links">';
        foreach ($items as $item) {
            $html .= '<li>';
            $html .= '<a href="' . htmlspecialchars($item['url'] ?? '#') . '" ';
            if ($item['target'] !== '_self') $html .= 'target="' . htmlspecialchars($item['target']) . '" ';
            $html .= '>';
            if ($item['icon']) {
                $html .= '<i class="' . htmlspecialchars($item['icon']) . '"></i> ';
            }
            $html .= htmlspecialchars($item['title']);
            $html .= '</a></li>';
        }
        $html .= '</ul>';
        return $html;
    }
}

if (!function_exists('_findMenuUrl')) {
    /**
     * Chercher une URL dans les items d'un menu par titre
     * Utile pour les templates qui ont besoin d'une URL sans la stocker en DB
     * 
     * @param array $items - menu items
     * @param string $title - titre du lien à chercher (search insensitive)
     * @param string $fallback - URL par défaut si non trouvé
     * @return string URL du lien ou fallback
     * 
     * Exemple :
     *   $estimationUrl = _findMenuUrl($items, 'Estimation', '/estimation');
     */
    function _findMenuUrl($items, $title, $fallback = '#') {
        if (empty($items)) return $fallback;
        
        $titleLower = strtolower($title);
        
        foreach ($items as $item) {
            $itemTitleLower = strtolower($item['title'] ?? '');
            // Cherche si le titre du menu contient le mot-clé
            if (strpos($itemTitleLower, $titleLower) !== false) {
                return $item['url'] ?? $fallback;
            }
        }
        
        return $fallback;
    }
}