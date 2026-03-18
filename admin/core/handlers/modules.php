<?php
/**
 * API Handler: modules
 * Called via: /admin/api/router.php?module=modules&action=...
 * Module management - enable/disable/configure modules
 * Table: modules (if exists), otherwise settings-based
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

// Check if modules table exists
$modulesTableExists = false;
try { $pdo->query("SELECT 1 FROM modules LIMIT 1"); $modulesTableExists = true; } catch (PDOException $e) {}

switch ($action) {
    case 'list':
        try {
            if ($modulesTableExists) {
                $stmt = $pdo->query("SELECT * FROM modules ORDER BY category, name ASC");
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } else {
                // Derive module list from router module map
                $modulesDir = dirname(__DIR__, 2) . '/modules/';
                $modules = [];
                $categories = [
                    'content' => ['articles', 'pages', 'captures', 'secteurs', 'blog', 'templates', 'sections'],
                    'immobilier' => ['biens', 'estimation', 'financement', 'rdv'],
                    'marketing' => ['leads', 'crm', 'emails', 'scoring', 'sequences'],
                    'seo' => ['seo', 'seo-semantic', 'local-seo', 'analytics'],
                    'social' => ['gmb', 'social', 'facebook', 'instagram', 'linkedin', 'tiktok', 'reseaux-sociaux'],
                    'ai' => ['ai', 'agents', 'ai-prompts', 'neuropersona', 'journal'],
                    'network' => ['contact', 'scraper-gmb', 'websites'],
                    'builder' => ['builder', 'design', 'menus'],
                    'strategy' => ['strategy', 'launchpad', 'ressources'],
                    'system' => ['media', 'settings', 'maintenance', 'license', 'modules'],
                ];
                foreach ($categories as $cat => $mods) {
                    foreach ($mods as $mod) {
                        $isEnabled = true;
                        // Check settings for disabled modules
                        $checkStmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                        $checkStmt->execute(["module_{$mod}_enabled"]);
                        $val = $checkStmt->fetchColumn();
                        if ($val !== false) $isEnabled = (bool)$val;

                        $modules[] = [
                            'slug' => $mod,
                            'name' => ucfirst(str_replace('-', ' ', $mod)),
                            'category' => $cat,
                            'is_enabled' => $isEnabled,
                        ];
                    }
                }
                echo json_encode(['success' => true, 'data' => $modules, 'source' => 'derived']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $slug = $input['slug'] ?? $_GET['slug'] ?? '';
            if ($modulesTableExists) {
                $stmt = $pdo->prepare("SELECT * FROM modules WHERE slug = ?");
                $stmt->execute([$slug]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Module non trouve']);
            } else {
                $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                $stmt->execute(["module_{$slug}_enabled"]);
                $val = $stmt->fetchColumn();
                echo json_encode(['success' => true, 'data' => ['slug' => $slug, 'is_enabled' => $val !== false ? (bool)$val : true]]);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'enable':
        try {
            $slug = $input['slug'] ?? '';
            if (!$slug) { echo json_encode(['success' => false, 'message' => 'Slug requis']); break; }
            if ($modulesTableExists) {
                $pdo->prepare("UPDATE modules SET is_enabled = 1 WHERE slug = ?")->execute([$slug]);
            } else {
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value, category) VALUES (?, '1', 'modules') ON DUPLICATE KEY UPDATE setting_value = '1'")->execute(["module_{$slug}_enabled"]);
            }
            echo json_encode(['success' => true, 'message' => "Module '{$slug}' active"]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'disable':
        try {
            $slug = $input['slug'] ?? '';
            if (!$slug) { echo json_encode(['success' => false, 'message' => 'Slug requis']); break; }
            // Prevent disabling system modules
            $protected = ['settings', 'maintenance', 'modules'];
            if (in_array($slug, $protected)) {
                echo json_encode(['success' => false, 'message' => "Le module '{$slug}' ne peut pas etre desactive"]);
                break;
            }
            if ($modulesTableExists) {
                $pdo->prepare("UPDATE modules SET is_enabled = 0 WHERE slug = ?")->execute([$slug]);
            } else {
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value, category) VALUES (?, '0', 'modules') ON DUPLICATE KEY UPDATE setting_value = '0'")->execute(["module_{$slug}_enabled"]);
            }
            echo json_encode(['success' => true, 'message' => "Module '{$slug}' desactive"]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle':
        try {
            $slug = $input['slug'] ?? '';
            if (!$slug) { echo json_encode(['success' => false, 'message' => 'Slug requis']); break; }
            $protected = ['settings', 'maintenance', 'modules'];
            if (in_array($slug, $protected)) {
                echo json_encode(['success' => false, 'message' => "Le module '{$slug}' ne peut pas etre desactive"]);
                break;
            }
            if ($modulesTableExists) {
                $pdo->prepare("UPDATE modules SET is_enabled = NOT is_enabled WHERE slug = ?")->execute([$slug]);
            } else {
                $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
                $stmt->execute(["module_{$slug}_enabled"]);
                $current = $stmt->fetchColumn();
                $newVal = ($current === false || $current === '1') ? '0' : '1';
                $pdo->prepare("INSERT INTO settings (setting_key, setting_value, category) VALUES (?, ?, 'modules') ON DUPLICATE KEY UPDATE setting_value = ?")->execute(["module_{$slug}_enabled", $newVal, $newVal]);
            }
            echo json_encode(['success' => true, 'message' => "Module '{$slug}' bascule"]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            if ($modulesTableExists) {
                $stats = [
                    'total' => (int)$pdo->query("SELECT COUNT(*) FROM modules")->fetchColumn(),
                    'enabled' => (int)$pdo->query("SELECT COUNT(*) FROM modules WHERE is_enabled = 1")->fetchColumn(),
                    'disabled' => (int)$pdo->query("SELECT COUNT(*) FROM modules WHERE is_enabled = 0")->fetchColumn(),
                ];
            } else {
                $total = 47;
                $disabled = (int)$pdo->query("SELECT COUNT(*) FROM settings WHERE setting_key LIKE 'module_%_enabled' AND setting_value = '0'")->fetchColumn();
                $stats = ['total' => $total, 'enabled' => $total - $disabled, 'disabled' => $disabled];
            }
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
