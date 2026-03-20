<?php
/**
 * API Handler: courtiers
 * Called via: /admin/api/router.php?module=courtiers&action=...
 * Delegates to: /admin/modules/immobilier/courtiers/api.php
 */

$action = CURRENT_ACTION;
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$moduleDir = dirname(__DIR__, 2) . '/modules/immobilier/courtiers/';

if (file_exists($moduleDir . 'api.php')) {
    require_once $moduleDir . 'api.php';
    return;
}

// Fallback minimal
switch ($action) {
    case 'list':
        echo json_encode(['success' => false, 'message' => 'Module courtiers: api.php non trouve']);
        break;
    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee pour courtiers"]);
}
