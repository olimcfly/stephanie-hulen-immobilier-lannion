<?php
/**
 * MODULE ADMIN — Secteurs — API locale
 * /admin/modules/content/secteurs/api.php
 *
 * Proxy vers le handler global pour coherence avec les autres modules.
 * Le handler global (admin/core/handlers/secteurs.php) gere deja :
 *   list, get, create, update, delete, toggle_status, duplicate, bulk_delete
 */

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) {
        require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
    }
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

header('Content-Type: application/json; charset=utf-8');

// ─── CSRF pour POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
        exit;
    }
}

// ─── Action ───
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if (!defined('CURRENT_ACTION')) define('CURRENT_ACTION', $action);
if (!defined('CURRENT_MODULE')) define('CURRENT_MODULE', 'secteurs');

// ─── Deleguer au handler global ───
require_once dirname(dirname(dirname(__DIR__))) . '/core/handlers/secteurs.php';
exit;
