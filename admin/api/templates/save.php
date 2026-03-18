<?php
/**
 * /admin/api/templates/save.php
 * 
 * Sauvegarde les modifications de champs et CSS personnalisé en base de données.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

$input = json_decode(file_get_contents('php://input'), true);
$templateId = $input['template'] ?? '';
$fields = $input['fields'] ?? [];
$customCSS = $input['customCSS'] ?? '';

// Validation
$validTemplates = ['t1-accueil', 't2-vendre', 't3-acheter', 't4-investir'];
if (!in_array($templateId, $validTemplates)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid template']));
}

try {
    // Déterminer le chemin racine
    $rootPath = dirname(__DIR__, 2); // /home/cool1933/public_html/
    require_once $rootPath . '/getDB.php';
    
    $pdo = getDB();
    
    // Vérifier si la page existe
    $stmt = $pdo->prepare("SELECT id FROM cms_pages WHERE template = ? LIMIT 1");
    $stmt->execute([$templateId]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $fieldsJson = json_encode($fields, JSON_UNESCAPED_UNICODE);
    
    if ($page) {
        // UPDATE
        $stmt = $pdo->prepare("
            UPDATE `cms_pages` 
            SET `fields_json` = :fields, `custom_css` = :css, `updated_at` = NOW()
            WHERE `template` = :template
        ");
        $stmt->execute([
            ':fields' => $fieldsJson,
            ':css' => $customCSS,
            ':template' => $templateId
        ]);
    } else {
        // INSERT
        $stmt = $pdo->prepare("
            INSERT INTO `cms_pages` (`template`, `fields_json`, `custom_css`, `created_at`, `updated_at`)
            VALUES (:template, :fields, :css, NOW(), NOW())
        ");
        $stmt->execute([
            ':template' => $templateId,
            ':fields' => $fieldsJson,
            ':css' => $customCSS
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Modifications enregistrées',
        'template' => $templateId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Save failed',
        'message' => $e->getMessage()
    ]);
}
?>