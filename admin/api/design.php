<?php
/**
 * /admin/api/design.php
 * API centralisée pour Header/Footer (contenu)
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

$table = ($type === 'header') ? 'headers' : 'footers';

try {
    
    if ($action === 'save') {
        $id = $_POST['id'] ?? null;
        $name = sanitize($_POST['name'] ?? 'Default');
        $logo_url = sanitize($_POST['logo_url'] ?? '');
        $logo_width = (int)($_POST['logo_width'] ?? 160);
        $bg_color = sanitize($_POST['bg_color'] ?? '#ffffff');
        $text_color = sanitize($_POST['text_color'] ?? '#000000');
        
        if ($id === 'new' || empty($id)) {
            $stmt = $pdo->prepare(
                "INSERT INTO $table (name, logo_url, logo_width, bg_color, text_color, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, 'active', NOW())"
            );
            $stmt->execute([$name, $logo_url, $logo_width, $bg_color, $text_color]);
            $id = $pdo->lastInsertId();
            $msg = ucfirst($type) . ' créé ✅';
        } else {
            $id = (int)$id;
            $stmt = $pdo->prepare(
                "UPDATE $table SET name=?, logo_url=?, logo_width=?, bg_color=?, text_color=?, updated_at=NOW() WHERE id=?"
            );
            $stmt->execute([$name, $logo_url, $logo_width, $bg_color, $text_color, $id]);
            $msg = ucfirst($type) . ' mis à jour ✅';
        }
        
        if ($type === 'header') {
            $hover_color = sanitize($_POST['hover_color'] ?? '#6366f1');
            $phone_number = sanitize($_POST['phone_number'] ?? '');
            $cta_text = sanitize($_POST['cta_text'] ?? '');
            $cta_link = sanitize($_POST['cta_link'] ?? '');
            
            $stmt = $pdo->prepare(
                "UPDATE headers SET hover_color=?, phone_number=?, cta_text=?, cta_link=? WHERE id=?"
            );
            $stmt->execute([$hover_color, $phone_number, $cta_text, $cta_link, $id]);
        }
        
        if ($type === 'footer') {
            $phone = sanitize($_POST['phone'] ?? '');
            $email = sanitize($_POST['email'] ?? '', 'email');
            $address = sanitize($_POST['address'] ?? '');
            
            $stmt = $pdo->prepare(
                "UPDATE footers SET phone=?, email=?, address=? WHERE id=?"
            );
            $stmt->execute([$phone, $email, $address, $id]);
        }
        
        http_response_code(200);
        die(json_encode(['success' => true, 'message' => $msg, 'id' => $id, 'timestamp' => date('Y-m-d H:i:s')]));
    }
    
    if ($action === 'get') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT * FROM $table WHERE id=?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        http_response_code($data ? 200 : 404);
        die(json_encode(['success' => !!$data, 'data' => $data]));
    }
    
    if ($action === 'list') {
        $stmt = $pdo->query("SELECT id, name, status, created_at FROM $table ORDER BY created_at DESC");
        http_response_code(200);
        die(json_encode(['success' => true, 'data' => $stmt->fetchAll()]));
    }
    
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id=?");
        $stmt->execute([$id]);
        
        http_response_code(200);
        die(json_encode(['success' => true, 'message' => ucfirst($type) . ' supprimé ✅']));
    }
    
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => 'Action inconnue']));

} catch (Exception $e) {
    http_response_code(400);
    die(json_encode(['success' => false, 'error' => $e->getMessage()]));
}
?>
