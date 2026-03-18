<?php
/**
 * /admin/api/templates/templates.php
 * 
 * API pour sauvegarder le contenu JSON des templates
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config/config.php';

// Vérifier authentification
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'error' => 'Non authentifié']));
}

$pdo = getDB();
$action = $_POST['action'] ?? 'update';

if ($action === 'update') {
    $templateId = $_POST['template_id'] ?? null;
    $jsonContent = $_POST['json_content'] ?? '{}';
    
    if (!$templateId) {
        die(json_encode(['success' => false, 'error' => 'template_id manquant']));
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE `design_templates` SET `json_content` = ? WHERE `slug` = ?");
        $result = $stmt->execute([$jsonContent, $templateId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Template sauvegardé'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Action inconnue']);
}

} elseif ($action === 'delete') {
    $id = $_POST['id'] ?? null;
    
    if (!$id) {
        die(json_encode(['success' => false, 'error' => 'ID manquant']));
    }
    
    try {
        // Récupérer le template pour supprimer les fichiers
        $stmt = $pdo->prepare("SELECT `php_file`, `css_file` FROM `design_templates` WHERE `id` = ? LIMIT 1");
        $stmt->execute([$id]);
        $template = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($template) {
            $baseDir = dirname(__DIR__, 2);
            
            // Supprimer les fichiers physiques
            if (!empty($template['php_file']) && file_exists($baseDir . '/' . $template['php_file'])) {
                unlink($baseDir . '/' . $template['php_file']);
            }
            if (!empty($template['css_file']) && file_exists($baseDir . '/' . $template['css_file'])) {
                unlink($baseDir . '/' . $template['css_file']);
            }
        }
        
        // Supprimer de la DB
        $stmt = $pdo->prepare("DELETE FROM `design_templates` WHERE `id` = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'Template supprimé']);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }