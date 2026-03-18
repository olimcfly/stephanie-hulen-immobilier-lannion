<?php
/**
 * API Handler: license
 * Called via: /admin/api/router.php?module=license&action=...
 * License management (file-based + settings table)
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

$licenseFile = dirname(__DIR__, 3) . '/config/license.json';

switch ($action) {
    case 'status':
    case 'list':
        try {
            $license = ['valid' => false, 'key' => '', 'type' => 'free', 'expires_at' => null, 'features' => []];

            // Try file-based license
            if (file_exists($licenseFile)) {
                $fileData = json_decode(file_get_contents($licenseFile), true);
                if ($fileData) $license = array_merge($license, $fileData);
            }

            // Try settings-based license
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'license_%'");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $key = str_replace('license_', '', $row['setting_key']);
                $license[$key] = $row['setting_value'];
            }

            // Check expiry
            if (!empty($license['expires_at']) && strtotime($license['expires_at']) < time()) {
                $license['valid'] = false;
                $license['expired'] = true;
            }

            echo json_encode(['success' => true, 'data' => $license]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'activate':
        try {
            $key = trim($input['key'] ?? '');
            if (empty($key)) { echo json_encode(['success' => false, 'message' => 'Cle de licence requise']); break; }

            $license = [
                'valid' => true,
                'key' => $key,
                'type' => $input['type'] ?? 'pro',
                'activated_at' => date('Y-m-d H:i:s'),
                'expires_at' => $input['expires_at'] ?? date('Y-m-d', strtotime('+1 year')),
                'features' => $input['features'] ?? [],
            ];

            // Save to file
            $configDir = dirname($licenseFile);
            if (!is_dir($configDir)) mkdir($configDir, 0755, true);
            file_put_contents($licenseFile, json_encode($license, JSON_PRETTY_PRINT));

            // Save to settings
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, category) VALUES (?, ?, 'license') ON DUPLICATE KEY UPDATE setting_value = ?");
            $stmt->execute(['license_key', $key, $key]);
            $stmt->execute(['license_type', $license['type'], $license['type']]);
            $stmt->execute(['license_valid', '1', '1']);
            $stmt->execute(['license_activated_at', $license['activated_at'], $license['activated_at']]);
            $stmt->execute(['license_expires_at', $license['expires_at'], $license['expires_at']]);

            echo json_encode(['success' => true, 'message' => 'Licence activee', 'data' => $license]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'deactivate':
        try {
            if (file_exists($licenseFile)) {
                unlink($licenseFile);
            }
            $pdo->query("DELETE FROM settings WHERE setting_key LIKE 'license_%'");
            echo json_encode(['success' => true, 'message' => 'Licence desactivee']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'verify':
        try {
            $valid = false;
            if (file_exists($licenseFile)) {
                $fileData = json_decode(file_get_contents($licenseFile), true);
                if ($fileData && !empty($fileData['valid']) && !empty($fileData['expires_at'])) {
                    $valid = strtotime($fileData['expires_at']) > time();
                }
            }
            echo json_encode(['success' => true, 'valid' => $valid]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'features':
        try {
            $features = [];
            if (file_exists($licenseFile)) {
                $fileData = json_decode(file_get_contents($licenseFile), true);
                $features = $fileData['features'] ?? [];
            }
            echo json_encode(['success' => true, 'data' => $features]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
