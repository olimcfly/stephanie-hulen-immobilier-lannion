<?php
/**
 * API Handler: settings
 * Called via: /admin/api/router.php?module=settings&action=...
 * Table: settings
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $category = $input['category'] ?? $_GET['category'] ?? '';
            $where = ''; $params = [];
            if ($category) { $where = 'WHERE category = ?'; $params[] = $category; }
            $stmt = $pdo->prepare("SELECT * FROM settings {$where} ORDER BY category, setting_key");
            $stmt->execute($params);
            $settings = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            echo json_encode(['success' => true, 'data' => $settings]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $key = $input['key'] ?? $_GET['key'] ?? '';
            if (!$key) { echo json_encode(['success' => false, 'message' => 'Cle requise']); break; }
            $stmt = $pdo->prepare("SELECT * FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Parametre non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
    case 'save':
        try {
            $updated = 0;
            $category = $input['category'] ?? 'general';
            foreach ($input as $key => $value) {
                if (in_array($key, ['action', 'csrf_token', 'category'])) continue;
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, category) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, category = ?");
                $stmt->execute([$key, $value, $category, $value, $category]);
                $updated++;
            }
            echo json_encode(['success' => true, 'message' => "{$updated} parametres sauvegardes"]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $key = $input['key'] ?? '';
            if (!$key) { echo json_encode(['success' => false, 'message' => 'Cle requise']); break; }
            $pdo->prepare("DELETE FROM settings WHERE setting_key = ?")->execute([$key]);
            echo json_encode(['success' => true, 'message' => 'Parametre supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'categories':
        try {
            $stmt = $pdo->query("SELECT DISTINCT category FROM settings WHERE category IS NOT NULL ORDER BY category");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'all':
        try {
            $stmt = $pdo->query("SELECT * FROM settings ORDER BY category, setting_key");
            $grouped = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $cat = $row['category'] ?? 'general';
                $grouped[$cat][$row['setting_key']] = $row['setting_value'];
            }
            echo json_encode(['success' => true, 'data' => $grouped]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'export':
        try {
            $stmt = $pdo->query("SELECT * FROM settings ORDER BY category, setting_key");
            $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $all]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'import':
        try {
            $settings = $input['settings'] ?? [];
            $imported = 0;
            foreach ($settings as $s) {
                $key = $s['setting_key'] ?? '';
                if (!$key) continue;
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, category) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, category = ?");
                $stmt->execute([$key, $s['setting_value'] ?? '', $s['category'] ?? 'general', $s['setting_value'] ?? '', $s['category'] ?? 'general']);
                $imported++;
            }
            echo json_encode(['success' => true, 'message' => "{$imported} parametres importes"]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
