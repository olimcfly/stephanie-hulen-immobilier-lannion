<?php
/**
 * /admin/api/templates.php
 * API centralisée pour les templates Header/Footer
 */

define('ADMIN_ROUTER', true);
require_once dirname(dirname(__DIR__)) . '/includes/init.php';

header('Content-Type: application/json');

try {
    $pdo = getDB();
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Erreur BD']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'POST requis']));
}

$action = $_POST['action'] ?? null;
$type = $_POST['type'] ?? null;

if (!in_array($type, ['header', 'footer'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Type invalide']));
}

try {
    
    // ══════════════════════════════════════════════════════════
    // SAVE (insert ou update)
    // ══════════════════════════════════════════════════════════
    if ($action === 'save') {
        $id = $_POST['id'] ?? null;
        $html_code = $_POST['html_code'] ?? '';
        $css_code = $_POST['css_code'] ?? '';
        $name = $_POST['name'] ?? ucfirst($type) . ' Template';
        $description = $_POST['description'] ?? '';
        
        if (!$id || $id === 'new') {
            // INSERT
            $stmt = $pdo->prepare(
                "INSERT INTO design_templates (type, name, description, html_code, css_code, created_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$type, $name, $description, $html_code, $css_code]);
            $id = $pdo->lastInsertId();
            $msg = 'Template créé ✅';
        } else {
            // UPDATE
            $id = (int)$id;
            $stmt = $pdo->prepare(
                "UPDATE design_templates SET html_code=?, css_code=?, name=?, description=?, updated_at=NOW() WHERE id=?"
            );
            $stmt->execute([$html_code, $css_code, $name, $description, $id]);
            $msg = 'Template sauvegardé ✅';
        }
        
        http_response_code(200);
        die(json_encode([
            'success' => true,
            'message' => $msg,
            'id' => $id,
            'timestamp' => date('Y-m-d H:i:s')
        ]));
    }
    
    // ══════════════════════════════════════════════════════════
    // DELETE
    // ══════════════════════════════════════════════════════════
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'ID manquant']));
        }
        
        $stmt = $pdo->prepare("DELETE FROM design_templates WHERE id=? AND type=?");
        $stmt->execute([$id, $type]);
        
        http_response_code(200);
        die(json_encode([
            'success' => true,
            'message' => 'Template supprimé ✅'
        ]));
    }
    
    // ══════════════════════════════════════════════════════════
    // GET
    // ══════════════════════════════════════════════════════════
    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            die(json_encode(['success' => false, 'error' => 'ID manquant']));
        }
        
        $stmt = $pdo->prepare("SELECT * FROM design_templates WHERE id=? AND type=?");
        $stmt->execute([$id, $type]);
        $data = $stmt->fetch();
        
        if (!$data) {
            http_response_code(404);
            die(json_encode(['success' => false, 'error' => 'Non trouvé']));
        }
        
        http_response_code(200);
        die(json_encode(['success' => true, 'data' => $data]));
    }
    
    // ══════════════════════════════════════════════════════════
    // LIST
    // ══════════════════════════════════════════════════════════
    if ($action === 'list') {
        $stmt = $pdo->prepare("SELECT id, name, description, created_at FROM design_templates WHERE type=? ORDER BY created_at DESC");
        $stmt->execute([$type]);
        $items = $stmt->fetchAll();
        
        http_response_code(200);
        die(json_encode(['success' => true, 'data' => $items]));
    }
    
    // Action inconnue
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Action inconnue: ' . $action]));

} catch (Exception $e) {
    http_response_code(400);
    die(json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]));
}
?>
