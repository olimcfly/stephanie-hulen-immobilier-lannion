<?php
/**
 * /admin/modules/system/templates/save.php
 * 
 * Sauvegarde les champs du template en temps réel
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

// Vérifier authentification
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'Unauthorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$websiteId = $_POST['website_id'] ?? null;
$templateId = $_POST['template_id'] ?? null;
$fields = $_POST['fields'] ?? '{}';

// Validation
if (!$websiteId || !$templateId) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Missing parameters']));
}

try {
    $pdo = getDB();
    
    // Vérifier si la page existe
    $stmt = $pdo->prepare("SELECT `id` FROM `pages` WHERE `website_id` = ? AND `template_id` = ? LIMIT 1");
    $stmt->execute([$websiteId, $templateId]);
    $page = $stmt->fetch();
    
    if ($page) {
        // Mettre à jour
        $stmt = $pdo->prepare("UPDATE `pages` SET `fields` = ?, `updated_at` = NOW() WHERE `website_id` = ? AND `template_id` = ?");
        $stmt->execute([$fields, $websiteId, $templateId]);
    } else {
        // Créer
        $stmt = $pdo->prepare("INSERT INTO `pages` (`website_id`, `template_id`, `fields`, `created_at`, `updated_at`) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$websiteId, $templateId, $fields]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Sauvegardé avec succès',
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>