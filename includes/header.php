<?php
/**
 * header.php - Header réutilisable pour PUBLIC (PATCHED - MENUS DYNAMIQUES)
 * Chemin : /front/includes/header.php
 * 
 * Variables attendues (optionnelles - fallback vers admin_settings) :
 * - $pageTitle, $pageDescription, $pageKeywords, $pageOgImage, $canonicalUrl
 */

// ── Charger le helper menus ──
if (file_exists(__DIR__ . '/menu-helper.php')) {
    require_once __DIR__ . '/menu-helper.php';
}

// ── S'assurer que SiteSettings est disponible ──
if (!class_exists('SiteSettings')) {
    $ssPath = __DIR__ . '/../../includes/SiteSettings.php';
    if (!file_exists($ssPath)) $ssPath = __DIR__ . '/../includes/SiteSettings.php';
    if (!file_exists($ssPath)) $ssPath = __DIR__ . '/SiteSettings.php';
    if (file_exists($ssPath) && isset($pdo)) {
        require_once $ssPath;
        SiteSettings::init($pdo);
    }
}

// ── Helper local ──
function _hs(string $key, string $fallback = ''): string {
    if (class_exists('SiteSettings')) {
        return SiteSettings::get($key, $fallback);
    }
    return $fallback;
}

// ── Valeurs dynamiques ──
$agentName  = _hs('agent_name',  'Eduardo De Sul');
$agentPhone = _hs('agent_phone', '');
$agentEmail = _hs('agent_email', '');
$siteName   = _hs('site_name',   $agentName . ' Immobilier');
$siteUrl    = _hs('site_url',    defined('SITE_URL') ? SITE_URL : 'https://eduardo-desul-immobilier.fr');
$logoUrl    = _hs('agent_logo_url', '');
$logoAlt    = $siteName;

// Téléphone
$phoneClean = preg_replace('/[^0-9]/', '', $agentPhone);
$phoneFormatted = $agentPhone;
if (strlen($phoneClean) === 10 && $phoneClean[0] === '0') {
    $phoneFormatted = preg_replace('/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1 $2 $3 $4 $5', $phoneClean);
    $phoneLink = 'tel:+33' . substr($phoneClean, 1);
} else {
    $phoneLink = 'tel:' . $phoneClean;
}

// SEO (variables de page > settings par défaut)
$pageTitle       = isset($pageTitle) ? $pageTitle : _hs('meta_default_title', $siteName);
$pageDescription = isset($pageDescription) ? $pageDescription : _hs('meta_default_description', 'Conseiller immobilier professionnel à ' . _hs('agent_city', 'Bordeaux') . '.');
$pageKeywords    = isset($pageKeywords) ? $pageKeywords : 'immobilier, ' . strtolower(_hs('agent_city', 'bordeaux')) . ', vente, location, conseil';
$pageOgImage     = isset($pageOgImage) ? $pageOgImage : _hs('agent_photo_url', '');
$canonicalUrl    = isset($canonicalUrl) ? $canonicalUrl : (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : $siteUrl);

// ── Charger les menus ──
$headerMenu = getMenu('header-main', $pdo ?? null);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(_hs('site_language', 'fr')) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($pageKeywords) ?>">
    <meta name="author" content="<?= htmlspecialchars($agentName) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName) ?>">
    <?php if ($pageOgImage): ?>
        <meta property="og:image" content="<?= htmlspecialchars($pageOgImage) ?>">
    <?php endif; ?>
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <?php if ($pageOgImage): ?>
        <meta name="twitter:image" content="<?= htmlspecialchars($pageOgImage) ?>">
    <?php endif; ?>
    
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- CSS Variables dynamiques (couleurs du client) -->
    <?php if (class_exists('SiteSettings')): ?>
        <?= SiteSettings::cssVars() ?>
        <?= SiteSettings::googleFonts() ?>
    <?php endif; ?>
    
    <!-- CSS Global -->
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/page.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <!-- Favicon -->
    <?php if ($logoUrl): ?>
        <link rel="icon" type="image/x-icon" href="<?= htmlspecialchars($logoUrl) ?>">
    <?php else: ?>
        <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    <?php endif; ?>
    
    <!-- Schema.org JSON-LD -->
    <?php if (class_exists('SiteSettings')): ?>
        <?= SiteSettings::schemaOrg() ?>
    <?php endif; ?>
    
    <!-- Tracking (GA4, GTM, Facebook Pixel) -->
    <?php if (class_exists('SiteSettings')): ?>
        <?= SiteSettings::trackingHead() ?>
    <?php endif; ?>
</head>
<body>

<!-- GTM noscript -->
<?php if (class_exists('SiteSettings')): ?>
    <?= SiteSettings::trackingBody() ?>
<?php endif; ?>

<!-- HEADER NAVIGATION -->
<header class="main-header">

    <!-- Top Bar (optionnelle - affichée si téléphone ou email configuré) -->
    <?php if ($agentPhone || $agentEmail): ?>
    <div class="header-topbar" style="background: var(--color-primary, #1a365d); color: #fff; padding: 6px 0; font-size: 13px;">
        <div class="container d-flex justify-content-between align-items-center flex-wrap">
            <div class="d-flex gap-3">
                <?php if ($agentPhone): ?>
                    <a href="<?= $phoneLink ?>" style="color: #fff; text-decoration: none;">
                        <i class="fas fa-phone-alt"></i> <?= htmlspecialchars($phoneFormatted) ?>
                    </a>
                <?php endif; ?>
                <?php if ($agentEmail): ?>
                    <a href="mailto:<?= htmlspecialchars($agentEmail) ?>" style="color: #fff; text-decoration: none;">
                        <i class="fas fa-envelope"></i> <?= htmlspecialchars($agentEmail) ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php 
            // Mini réseaux sociaux dans la topbar
            if (class_exists('SiteSettings')) {
                $socials = SiteSettings::socials();
                if (!empty($socials)): ?>
                    <div class="d-flex gap-2">
                        <?php foreach ($socials as $social): ?>
                            <a href="<?= htmlspecialchars($social['url']) ?>" target="_blank" rel="noopener noreferrer"
                               style="color: #fff; opacity: 0.8;" title="<?= htmlspecialchars($social['label']) ?>">
                                <i class="<?= $social['icon'] ?>"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif;
            } ?>
        </div>
    </div>
    <?php endif; ?>

    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <!-- Logo/Branding -->
            <a class="navbar-brand" href="/">
                <?php if ($logoUrl): ?>
                    <img src="<?= htmlspecialchars($logoUrl) ?>" 
                         alt="<?= htmlspecialchars($logoAlt) ?>" 
                         style="max-height: 50px; width: auto;"
                         loading="lazy">
                <?php else: ?>
                    <span class="brand-name"><?= htmlspecialchars($agentName) ?></span>
                    <span class="brand-subtitle">Immobilier</span>
                <?php endif; ?>
            </a>
            
            <!-- Menu Toggle (Mobile) -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                    aria-controls="navbarNav" aria-expanded="false" aria-label="Menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Menu Principal DYNAMIQUE -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php 
                if (!empty($headerMenu['items'])) {
                    echo buildMenuHtml($headerMenu['items'], null, 'navbar-nav ms-auto');
                } else {
                    // Fallback si menu vide
                    ?>
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/">🏠 Accueil</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/a-propos">À propos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/contact">📞 Contact</a>
                        </li>
                    </ul>
                    <?php
                }
                ?>
                
                <!-- CTA Téléphone (affiché à droite sur desktop) -->
                <?php if ($agentPhone): ?>
                <div class="d-none d-lg-block ms-2">
                    <a class="btn btn-outline-primary btn-sm" href="<?= $phoneLink ?>" style="border-color: var(--color-secondary, #c9a84c); color: var(--color-secondary, #c9a84c);">
                        <i class="fas fa-phone-alt"></i> <?= htmlspecialchars($phoneFormatted) ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
</header>

<!-- MAIN CONTENT -->
<div class="main-content">