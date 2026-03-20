<?php
/**
 * ============================================================
 * Middleware Menu Dynamique
 * Chemin : /front/middleware/menu-middleware.php
 *
 * Charge les menus depuis les tables `menus` / `menu_items`
 * et les rend disponibles globalement pour le header et footer.
 *
 * Slugs attendus :
 *   - header-main     : navigation principale du header
 *   - footer-services : colonne services du footer
 *   - footer-col2     : colonne 2 du footer (ressources, etc.)
 *   - footer-col3     : colonne 3 du footer (legal, etc.)
 *
 * Usage dans un template :
 *   $headerLinks = dynamicMenu('header-main');
 *   $footerCols  = dynamicFooterColumns();
 * ============================================================
 */

if (defined('MENU_MIDDLEWARE_LOADED')) return;
define('MENU_MIDDLEWARE_LOADED', true);

/**
 * Cache interne pour eviter les requetes multiples par requete HTTP.
 */
$_MENU_CACHE = [];

/**
 * Charge un menu par slug depuis la table `menus` + `menu_items`.
 * Retourne un tableau de liens : [['label'=>..., 'url'=>..., 'target'=>..., 'icon'=>..., 'children'=>[...]], ...]
 *
 * @param string   $slug  Slug du menu (ex: 'header-main')
 * @param PDO|null $pdo   Connexion PDO (optionnel, utilise getDB() sinon)
 * @return array
 */
function dynamicMenu(string $slug, ?PDO $pdo = null): array
{
    global $_MENU_CACHE;

    if (isset($_MENU_CACHE[$slug])) {
        return $_MENU_CACHE[$slug];
    }

    try {
        if (!$pdo) $pdo = getDB();

        // Chercher le menu actif par slug
        $stmt = $pdo->prepare(
            "SELECT id FROM menus WHERE slug = ? AND (status = 'active' OR status = 'published' OR status = 1) LIMIT 1"
        );
        $stmt->execute([$slug]);
        $menuId = $stmt->fetchColumn();

        if (!$menuId) {
            $_MENU_CACHE[$slug] = [];
            return [];
        }

        // Charger tous les items actifs, tries par position
        $stmt = $pdo->prepare(
            "SELECT id, parent_id, title, url, target, icon, css_class, position
             FROM menu_items
             WHERE menu_id = ? AND is_active = 1
             ORDER BY parent_id ASC, position ASC"
        );
        $stmt->execute([$menuId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Construire l'arborescence (items avec enfants)
        $items = _buildMenuTree($rows, null);

        $_MENU_CACHE[$slug] = $items;
        return $items;

    } catch (Exception $e) {
        error_log("Menu middleware error [{$slug}]: " . $e->getMessage());
        $_MENU_CACHE[$slug] = [];
        return [];
    }
}

/**
 * Construit un arbre hierarchique a partir d'une liste plate.
 *
 * @param array    $rows     Tous les items du menu
 * @param int|null $parentId ID parent (null = racine)
 * @return array
 */
function _buildMenuTree(array $rows, $parentId): array
{
    $branch = [];
    foreach ($rows as $row) {
        $rowParent = $row['parent_id'] ?: null;
        if ($rowParent == $parentId) {
            $children = _buildMenuTree($rows, $row['id']);
            $branch[] = [
                'label'    => $row['title'] ?? '',
                'url'      => $row['url'] ?? '#',
                'target'   => $row['target'] ?? '_self',
                'icon'     => $row['icon'] ?? '',
                'css_class'=> $row['css_class'] ?? '',
                'children' => $children,
            ];
        }
    }
    return $branch;
}

/**
 * Retourne les liens du header au format attendu par renderHeader().
 * Si le menu dynamique 'header-main' existe en DB, il est utilise.
 * Sinon, retourne un tableau vide (le fallback sera gere par le caller).
 *
 * @param PDO|null $pdo
 * @return array  [['label'=>..., 'url'=>..., 'target'=>...], ...]
 */
function dynamicHeaderMenu(?PDO $pdo = null): array
{
    return dynamicMenu('header-main', $pdo);
}

/**
 * Retourne les colonnes du footer au format attendu par renderFooter().
 * Charge les menus footer-services, footer-col2, footer-col3.
 * Retourne un tableau de colonnes : [['title'=>..., 'links'=>[...]], ...]
 *
 * @param PDO|null $pdo
 * @return array
 */
function dynamicFooterColumns(?PDO $pdo = null): array
{
    $slugs = [
        'footer-services' => 'Services',
        'footer-col2'     => 'Ressources',
        'footer-col3'     => 'Informations',
    ];

    $columns = [];
    foreach ($slugs as $slug => $defaultTitle) {
        $items = dynamicMenu($slug, $pdo);
        if (!empty($items)) {
            // Essayer de recuperer le nom du menu depuis la DB
            $title = _getMenuTitle($slug, $pdo) ?: $defaultTitle;
            $links = [];
            foreach ($items as $item) {
                $links[] = [
                    'label'  => $item['label'],
                    'url'    => $item['url'],
                    'target' => $item['target'] ?? '_self',
                ];
            }
            $columns[] = [
                'title' => $title,
                'links' => $links,
            ];
        }
    }

    return $columns;
}

/**
 * Recupere le nom d'un menu par son slug.
 *
 * @param string   $slug
 * @param PDO|null $pdo
 * @return string
 */
function _getMenuTitle(string $slug, ?PDO $pdo = null): string
{
    static $cache = [];
    if (isset($cache[$slug])) return $cache[$slug];

    try {
        if (!$pdo) $pdo = getDB();
        $stmt = $pdo->prepare("SELECT name FROM menus WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $name = $stmt->fetchColumn();
        $cache[$slug] = $name ?: '';
        return $cache[$slug];
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Vide le cache des menus (utile en admin apres modification).
 */
function clearMenuCache(): void
{
    global $_MENU_CACHE;
    $_MENU_CACHE = [];
}
