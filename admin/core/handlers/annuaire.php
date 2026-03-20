<?php
/**
 * API Handler: annuaire
 * Called via: /admin/api/router.php?module=annuaire&action=...
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

// Déléguer vers le api.php du module
$apiFile = dirname(__DIR__, 2) . '/modules/content/annuaire/api.php';
if (file_exists($apiFile)) {
    $_POST = array_merge($_POST, $input);
    $_GET['action'] = $action;
    require $apiFile;
} else {
    echo json_encode(['success' => false, 'message' => 'API annuaire non trouvée']);
}
