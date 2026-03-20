<?php
/**
 * API Handler: guides
 * Called via: /admin/api/router.php?module=guides&action=...
 * Delegates to: /admin/modules/content/guides/api.php
 */

$action = CURRENT_ACTION;
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$moduleDir = dirname(__DIR__, 2) . '/modules/content/guides/';

if (file_exists($moduleDir . 'api.php')) {
    require_once $moduleDir . 'api.php';
    return;
}

// Fallback minimal
switch ($action) {
    case 'list':
        echo json_encode(['success' => false, 'message' => 'Module guides: api.php non trouve']);
        break;
    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee pour guides"]);
}
