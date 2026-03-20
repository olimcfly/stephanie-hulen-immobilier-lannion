<?php
/**
 * ============================================================
 * CORE API ROUTER - Point d'entrée central AJAX
 * /admin/modules/system/api/router.php
 *
 * Tous les modules sont désormais enregistrés ici.
 * Les handlers se trouvent dans /admin/core/handlers/{module}.php
 * ============================================================
 */

// Sécurité : accès direct interdit (AJAX requis en prod)
if (!defined('ADMIN_ACCESS')) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        if (defined('APP_ENV') && APP_ENV === 'production') {
            http_response_code(403);
            exit(json_encode(['success' => false, 'message' => 'Accès interdit']));
        }
    }
}

// Headers JSON + sécurité
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Inclure l'init admin (session, DB, auth)
require_once dirname(__DIR__) . '/includes/init.php';

// ============================================================
// FONCTION CSRF — Validation du token
// ============================================================
function validateCsrfToken(string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

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
    'blog'      => $handlersDir . 'blog.php',

    // ── IMMOBILIER ───────────────────────
    'biens'     => $handlersDir . 'biens.php',
    'estimation' => $handlersDir . 'estimation.php',
    'financement' => $handlersDir . 'financement.php',
    'rdv'       => $handlersDir . 'rdv.php',

    // ── MARKETING / CRM ──────────────────
    'leads'     => $handlersDir . 'leads.php',
    'crm'       => $handlersDir . 'crm.php',
    'contact'   => $handlersDir . 'contact.php',
    'scoring'   => $handlersDir . 'scoring.php',
    'sequences' => $handlersDir . 'sequences.php',
    'emails'    => $handlersDir . 'emails.php',

    // ── SEO ──────────────────────────────
    'seo'       => $handlersDir . 'seo.php',
    'seo-semantic' => $handlersDir . 'seo-semantic.php',
    'local-seo' => $handlersDir . 'local-seo.php',
    'analytics' => $handlersDir . 'analytics.php',

    // ── SOCIAL / GMB ─────────────────────
    'gmb'       => $handlersDir . 'gmb.php',
    'facebook'  => $handlersDir . 'facebook.php',
    'instagram' => $handlersDir . 'instagram.php',
    'linkedin'  => $handlersDir . 'linkedin.php',
    'tiktok'    => $handlersDir . 'tiktok.php',
    'social'    => $handlersDir . 'social.php',
    'reseaux-sociaux' => $handlersDir . 'reseaux-sociaux.php',

    // ── SYSTEM ───────────────────────────
    'media'     => $handlersDir . 'media.php',
    'settings'  => $handlersDir . 'settings.php',
    'templates' => $handlersDir . 'templates.php',
    'menus'     => $handlersDir . 'menus.php',
    'maintenance' => $handlersDir . 'maintenance.php',
    'modules'   => $handlersDir . 'modules.php',

    // ── IA ────────────────────────────────
    'ai'        => $handlersDir . 'ai.php',
    'ai-prompts' => $handlersDir . 'ai-prompts.php',
    'agents'    => $handlersDir . 'agents.php',
    'neuropersona' => $handlersDir . 'neuropersona.php',
    'journal'   => $handlersDir . 'journal.php',

    // ── STRATEGY ─────────────────────────
    'strategy'  => $handlersDir . 'strategy.php',
    'ressources' => $handlersDir . 'ressources.php',
    'launchpad' => $handlersDir . 'launchpad.php',

    // ── NETWORK ──────────────────────────
    'scraper-gmb' => $handlersDir . 'scraper-gmb.php',
    'websites'  => $handlersDir . 'websites.php',

    // ── IMMOBILIER EXTENDED ──────────────
    'design'    => $handlersDir . 'design.php',
    'builder'   => $handlersDir . 'builder.php',
    'license'   => $handlersDir . 'license.php',
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
