<?php
/**
 * ============================================================
 * RENDERER — Secteur Single (/secteur-slug)
 * Affiche un secteur individuel
 * ============================================================
 */

if (!defined('FRONT_ROUTER')) { header('Location: /'); exit; }

$root = dirname(__DIR__, 2);

require_once $root . '/includes/classes/SecteurRenderer.php';

$siteConfig = function_exists('getSiteConfig') ? getSiteConfig($db) : [];
$hf         = getHeaderFooter($db);
$headerData = $hf['header'];
$footerData = $hf['footer'];

$renderer = new SecteurRenderer($db);

// Déterminer le slug
$slug = $slug ?? $_GET['slug'] ?? trim($uri ?? '', '/');

// Rendu via SecteurRenderer
$content = $renderer->renderSingle($slug);

if (!$content) {
    http_response_code(404);
    if (file_exists(__DIR__ . '/404.php')) {
        require_once __DIR__ . '/404.php';
    } else {
        echo '<h1>404 - Secteur non trouvé</h1>';
    }
    exit;
}

// SEO
$seo = $renderer->getSeoData($slug);

$pageTitle = $seo['title'] ?? 'Secteur';
$pageDesc  = $seo['description'] ?? '';

echo '<!DOCTYPE html><html lang="fr"><head>';
echo '<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<title>' . htmlspecialchars($pageTitle) . '</title>';
echo '<meta name="description" content="' . htmlspecialchars($pageDesc) . '">';
if (!empty($seo['robots'])) echo '<meta name="robots" content="' . htmlspecialchars($seo['robots']) . '">';
if (!empty($seo['canonical'])) echo '<link rel="canonical" href="' . htmlspecialchars(($siteConfig['site_url'] ?? '') . $seo['canonical']) . '">';
echo eduardoHead();
echo '<link rel="stylesheet" href="/front/assets/css/secteurs.css">';
$customCSS = $renderer->getCustomCSS('single');
if ($customCSS) echo '<style>' . $customCSS . '</style>';
echo '</head><body>';

echo renderHeader($headerData);
echo $content;
echo renderFooter($footerData);

echo '</body></html>';
