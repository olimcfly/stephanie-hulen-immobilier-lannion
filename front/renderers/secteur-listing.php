<?php
/**
 * ============================================================
 * RENDERER — Secteurs Listing (/secteurs)
 * Affiche tous les secteurs publiés + carte + intro Trégor
 * ============================================================
 */

if (!defined('FRONT_ROUTER')) { header('Location: /'); exit; }

$root = dirname(__DIR__, 2);

// ── Charger le SecteurRenderer ──
require_once $root . '/includes/classes/SecteurRenderer.php';

// ── Config site ──
$siteConfig = function_exists('getSiteConfig') ? getSiteConfig($db) : [];
$advisor    = $siteConfig;
$site       = $siteConfig;

// ── Header / Footer ──
$hf         = getHeaderFooter($db);
$headerData = $hf['header'];
$footerData = $hf['footer'];

// ── Filtres depuis GET ──
$typeSecteur = $_GET['type']   ?? '';
$ville       = $_GET['ville']  ?? '';
$search      = $_GET['q']      ?? '';

// ── Tentative de rendu via SecteurRenderer (template Builder) ──
$renderer    = new SecteurRenderer($db);
$builderHTML = $renderer->renderListing([
    'type_secteur' => $typeSecteur,
    'ville'        => $ville,
    'search'       => $search,
    'page'         => max(1, intval($_GET['page'] ?? 1)),
]);

// Si le Builder a retourné du contenu (template DB trouvé), l'afficher dans le layout
if ($builderHTML && strpos($builderHTML, 'secteurs-listing') !== false) {
    $content     = $builderHTML;
    $pageTitle   = 'Nos secteurs d\'intervention — ' . ($siteConfig['site_name'] ?? 'Immobilier');
    $pageDesc    = 'Découvrez les secteurs du Trégor où nous intervenons : Lannion, Perros-Guirec, Trébeurden, Pleumeur-Bodou et toute la Côte de Granit Rose.';

    echo '<!DOCTYPE html><html lang="fr"><head>';
    echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . htmlspecialchars($pageTitle) . '</title>';
    echo '<meta name="description" content="' . htmlspecialchars($pageDesc) . '">';
    echo eduardoHead();
    echo '<link rel="stylesheet" href="/front/assets/css/secteurs.css">';
    $customCSS = $renderer->getCustomCSS('listing');
    if ($customCSS) echo '<style>' . $customCSS . '</style>';
    echo '</head><body>';
    echo renderHeader($headerData);
    echo $content;
    echo renderFooter($footerData);
    echo '</body></html>';
    exit;
}

// ── Fallback : template PHP ──────────────────────────────
// Charger les secteurs directement
try {
    $sql = "SELECT * FROM secteurs WHERE status = 'published'";
    $params = [];

    if ($typeSecteur) {
        $sql .= " AND type_secteur = ?";
        $params[] = $typeSecteur;
    }
    if ($ville) {
        $sql .= " AND ville = ?";
        $params[] = $ville;
    }
    if ($search) {
        $sql .= " AND (nom LIKE ? OR description LIKE ? OR ville LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $sql .= " ORDER BY ville ASC, nom ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $secteurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $secteurs = [];
}

// Récupérer les villes distinctes pour les filtres
try {
    $villesStmt = $db->query("SELECT DISTINCT ville FROM secteurs WHERE status = 'published' ORDER BY ville ASC");
    $villes = $villesStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $villes = [];
}

// Récupérer les types distincts pour les filtres
try {
    $typesStmt = $db->query("SELECT DISTINCT type_secteur, COUNT(*) as cnt FROM secteurs WHERE status = 'published' GROUP BY type_secteur ORDER BY cnt DESC");
    $types = $typesStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $types = [];
}

$totalSecteurs = count($secteurs);

// ── Variables pour le template ──
$pdo        = $db;
$fields     = [
    'hero_title'    => 'Nos secteurs d\'intervention',
    'hero_subtitle' => 'Le Trégor et la Côte de Granit Rose',
    'intro_text'    => '',
];
$editMode   = false;

// ── Rendu ──
$pageTitle = 'Nos secteurs d\'intervention — ' . ($siteConfig['site_name'] ?? 'Immobilier');
$pageDesc  = 'Découvrez les secteurs du Trégor où nous intervenons : Lannion, Perros-Guirec, Trébeurden, Pleumeur-Bodou et toute la Côte de Granit Rose.';

echo '<!DOCTYPE html><html lang="fr"><head>';
echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<title>' . htmlspecialchars($pageTitle) . '</title>';
echo '<meta name="description" content="' . htmlspecialchars($pageDesc) . '">';
echo eduardoHead();
echo '<link rel="stylesheet" href="/front/assets/css/secteurs.css">';
echo '<link rel="stylesheet" href="/front/assets/css/secteurs-listing.css">';
echo '</head><body>';

echo renderHeader($headerData);

// ── Inclure le template PHP ──
require_once __DIR__ . '/../templates/pages/t15-secteurs-listing.php';

echo renderFooter($footerData);

echo '</body></html>';
