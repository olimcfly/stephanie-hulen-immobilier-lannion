<?php
/**
 * INDEX.PHP - PAGE D'ACCUEIL
 */

// 1. Charger le config EN PREMIER
require_once __DIR__ . '/config/config.php';

// 2. Définir ROOT_PATH
define('ROOT_PATH', __DIR__);
define('FRONT_ROUTER', true);

// 3. Maintenance check
if (file_exists(__DIR__ . '/includes/maintenance-check.php')) {
    require_once __DIR__ . '/includes/maintenance-check.php';
}

// 4. Router
$_GET['type'] = 'cms';
$_GET['slug'] = 'accueil';
$routerPath = __DIR__ . '/front/page.php';

if (file_exists($routerPath)) {
    require $routerPath;
} else {
    header('Location: /accueil');
    exit;
}
?&gt;