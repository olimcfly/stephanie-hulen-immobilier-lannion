<?php
/**
 * ============================================================
 * CORE API ROUTER - Point d'entrée central AJAX
 * /core/api/router.php
 * 
 * Usage : Tous les appels AJAX passent par ici
 * URL   : /admin/api/router.php?module=articles&action=list
 * ============================================================
 */

// Sécurité : accès direct interdit
if (!defined('ADMIN_ACCESS')) {
    // Autoriser si appelé via AJAX avec header approprié
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        // Tolérer l'accès direct en dev, bloquer en prod
        if (defined('APP_ENV') && APP_ENV === 'production') {
            http_response_code(403);
            exit(json_encode(['success' => false, 'message' => 'Accès interdit']));
        }
    }
}

// Headers JSON + CORS admin
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Inclure l'init admin (session, DB, auth)
require_once dirname(__DIR__, 2) . '/admin/includes/init.php';

// ============================================================
// VÉRIFICATION CSRF (sauf GET)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $csrfToken = $_POST['csrf_token'] 
                 ?? $_SERVER['HTTP_X_CSRF_TOKEN'] 
                 ?? '';
    
    if (!validateCsrfToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
        exit;
    }
}

// ============================================================
// ROUTING
// ============================================================
$module = $_GET['module'] ?? $_POST['module'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Modules autorisés et leurs fichiers handlers
$moduleMap = [
    'articles' => __DIR__ . '/handlers/articles.php',
    'biens'    => __DIR__ . '/handlers/biens.php',
    'leads'    => __DIR__ . '/handlers/leads.php',
    'captures' => __DIR__ . '/handlers/captures.php',
    'pages'    => __DIR__ . '/handlers/pages.php',
    'seo'      => __DIR__ . '/handlers/seo.php',
    'media'    => __DIR__ . '/handlers/media.php',
    'settings' => __DIR__ . '/handlers/settings.php',
    'ai'       => __DIR__ . '/handlers/ai.php',
];

if (empty($module) || !array_key_exists($module, $moduleMap)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Module invalide ou manquant',
        'available_modules' => array_keys($moduleMap)
    ]);
    exit;
}

// Charger le handler du module
$handlerFile = $moduleMap[$module];

if (!file_exists($handlerFile)) {
    http_response_code(501);
    echo json_encode(['success' => false, 'message' => "Handler '{$module}' non implémenté"]);
    exit;
}

// Passer $action au handler
define('CURRENT_MODULE', $module);
define('CURRENT_ACTION', $action);

require_once $handlerFile;<?php
/**
 * ============================================================
 * CORE API ROUTER - Point d'entrée central AJAX
 * /admin/api/router.php  (version mise à jour)
 *
 * Tous les modules sont désormais enregistrés ici.
 * ============================================================
 */

if (!defined('ADMIN_ACCESS')) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        if (defined('APP_ENV') && APP_ENV === 'production') {
            http_response_code(403);
            exit(json_encode(['success' => false, 'message' => 'Accès interdit']));
        }
    }
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once dirname(__DIR__) . '/includes/init.php';

// ── CSRF (sauf GET) ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $csrfToken = $_POST['csrf_token']
                 ?? $_SERVER['HTTP_X_CSRF_TOKEN']
                 ?? '';
    if (!validateCsrfToken($csrfToken)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
        exit;
    }
}

// ── ROUTING ──────────────────────────────────────────────────
$module = $_GET['module'] ?? $_POST['module'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

/**
 * Carte des modules → fichiers handlers
 * Chemin : /admin/core/handlers/{module}.php
 */
$handlersDir = dirname(__DIR__) . '/core/handlers/';

$moduleMap = [
    // ── CONTENT ──────────────────────────
    'articles'  => $handlersDir . 'articles.php',
    'pages'     => $handlersDir . 'pages.php',
    'captures'  => $handlersDir . 'captures.php',
    'secteurs'  => $handlersDir . 'secteurs.php',

    // ── IMMOBILIER ───────────────────────
    'biens'     => $handlersDir . 'biens.php',

    // ── MARKETING / CRM ──────────────────
    'leads'     => $handlersDir . 'leads.php',

    // ── SEO ──────────────────────────────
    'seo'       => $handlersDir . 'seo.php',

    // ── SOCIAL / GMB ─────────────────────
    'gmb'       => $handlersDir . 'gmb.php',

    // ── SYSTEM ───────────────────────────
    'media'     => $handlersDir . 'media.php',
    'settings'  => $handlersDir . 'settings.php',

    // ── IA (dispatcher vers /core/ai/) ───
    'ai'        => $handlersDir . 'ai.php',
];

// ── VALIDATION ───────────────────────────────────────────────
if (empty($module) || !array_key_exists($module, $moduleMap)) {
    http_response_code(400);
    echo json_encode([
        'success'           => false,
        'message'           => 'Module invalide ou manquant : ' . htmlspecialchars($module),
        'available_modules' => array_keys($moduleMap),
    ]);
    exit;
}

$handlerFile = $moduleMap[$module];

if (!file_exists($handlerFile)) {
    http_response_code(501);
    echo json_encode([
        'success' => false,
        'message' => "Handler '{$module}' non encore implémenté",
    ]);
    exit;
}

// ── DISPATCH ─────────────────────────────────────────────────
define('CURRENT_MODULE', $module);
define('CURRENT_ACTION', $action);

require_once $handlerFile;