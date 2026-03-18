<?php
/**
 * API Handler: maintenance
 * Called via: /admin/api/router.php?module=maintenance&action=...
 * Table: maintenance (or settings-based)
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

// Check if maintenance table exists
$maintenanceTableExists = false;
try { $pdo->query("SELECT 1 FROM maintenance LIMIT 1"); $maintenanceTableExists = true; } catch (PDOException $e) {}

switch ($action) {
    case 'status':
    case 'list':
        try {
            if ($maintenanceTableExists) {
                $stmt = $pdo->query("SELECT * FROM maintenance ORDER BY id DESC LIMIT 1");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $row ?: ['is_active' => 0, 'message' => '', 'allowed_ips' => '']]);
            } else {
                $data = [
                    'is_active' => false,
                    'message' => '',
                    'allowed_ips' => '',
                ];
                $keys = ['maintenance_active', 'maintenance_message', 'maintenance_allowed_ips'];
                $placeholders = implode(',', array_fill(0, count($keys), '?'));
                $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ({$placeholders})");
                $stmt->execute($keys);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ($row['setting_key'] === 'maintenance_active') $data['is_active'] = (bool)$row['setting_value'];
                    if ($row['setting_key'] === 'maintenance_message') $data['message'] = $row['setting_value'];
                    if ($row['setting_key'] === 'maintenance_allowed_ips') $data['allowed_ips'] = $row['setting_value'];
                }
                echo json_encode(['success' => true, 'data' => $data]);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'enable':
        try {
            $message = $input['message'] ?? 'Site en maintenance. Revenez bientot.';
            $allowedIps = $input['allowed_ips'] ?? '';
            if ($maintenanceTableExists) {
                $pdo->query("UPDATE maintenance SET is_active = 1, message = " . $pdo->quote($message) . ", allowed_ips = " . $pdo->quote($allowedIps));
                if ($pdo->query("SELECT COUNT(*) FROM maintenance")->fetchColumn() == 0) {
                    $pdo->prepare("INSERT INTO maintenance (is_active, message, allowed_ips) VALUES (1, ?, ?)")->execute([$message, $allowedIps]);
                } else {
                    $pdo->prepare("UPDATE maintenance SET is_active = 1, message = ?, allowed_ips = ? ORDER BY id DESC LIMIT 1")->execute([$message, $allowedIps]);
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, category) VALUES (?, ?, 'system') ON DUPLICATE KEY UPDATE setting_value = ?");
                $stmt->execute(['maintenance_active', '1', '1']);
                $stmt->execute(['maintenance_message', $message, $message]);
                $stmt->execute(['maintenance_allowed_ips', $allowedIps, $allowedIps]);
            }
            echo json_encode(['success' => true, 'message' => 'Mode maintenance active']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'disable':
        try {
            if ($maintenanceTableExists) {
                $pdo->query("UPDATE maintenance SET is_active = 0");
            } else {
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value, category) VALUES ('maintenance_active', '0', 'system') ON DUPLICATE KEY UPDATE setting_value = '0'")->execute();
            }
            echo json_encode(['success' => true, 'message' => 'Mode maintenance desactive']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $hasMessage    = array_key_exists('message', $input);
            $hasAllowedIps = array_key_exists('allowed_ips', $input);
            $hasIsActive   = array_key_exists('is_active', $input);

            $message    = $hasMessage    ? (string)$input['message']       : null;
            $allowedIps = $hasAllowedIps ? (string)$input['allowed_ips']   : null;
            $isActive   = $hasIsActive   ? (int)$input['is_active']        : null;

            if ($maintenanceTableExists) {
                $sets = []; $params = [];
                if ($hasMessage)    { $sets[] = "message = ?";    $params[] = $message; }
                if ($hasAllowedIps) { $sets[] = "allowed_ips = ?"; $params[] = $allowedIps; }
                if ($hasIsActive)   { $sets[] = "is_active = ?";  $params[] = $isActive; }
                if (!empty($sets)) {
                    $pdo->prepare("UPDATE maintenance SET " . implode(', ', $sets) . " ORDER BY id DESC LIMIT 1")->execute($params);
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, category) VALUES (?, ?, 'system') ON DUPLICATE KEY UPDATE setting_value = ?");
                if ($hasMessage)    $stmt->execute(['maintenance_message', $message, $message]);
                if ($hasAllowedIps) $stmt->execute(['maintenance_allowed_ips', $allowedIps, $allowedIps]);
                if ($hasIsActive)   $stmt->execute(['maintenance_active', (string)$isActive, (string)$isActive]);
            }
            echo json_encode(['success' => true, 'message' => 'Maintenance mise a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'clear_cache':
        try {
            $cacheDir = dirname(__DIR__, 3) . '/cache/';
            $cleared = 0;
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) { unlink($file); $cleared++; }
                }
            }
            echo json_encode(['success' => true, 'message' => "{$cleared} fichiers cache supprimes"]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'system_info':
        try {
            $info = [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
                'database_version' => $pdo->query("SELECT VERSION()")->fetchColumn(),
                'memory_limit' => ini_get('memory_limit'),
                'max_upload_size' => ini_get('upload_max_filesize'),
                'max_post_size' => ini_get('post_max_size'),
                'max_execution_time' => ini_get('max_execution_time'),
                'disk_free' => disk_free_space(dirname(__DIR__, 3)),
                'disk_total' => disk_total_space(dirname(__DIR__, 3)),
            ];
            echo json_encode(['success' => true, 'data' => $info]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
