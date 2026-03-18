<?php
/**
 * API Handler: ai
 * Called via: /admin/api/router.php?module=ai&action=...
 * AI content generation settings and endpoints
 * Tables: ai_prompts, settings (ai_* keys)
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'generate':
        try {
            $type = $input['type'] ?? 'content'; // content, seo_title, seo_description, social_post, email
            $context = $input['context'] ?? '';
            $promptSlug = $input['prompt_slug'] ?? $type;
            $persona = $input['persona'] ?? null;

            // Load prompt template
            $stmt = $pdo->prepare("SELECT * FROM ai_prompts WHERE (slug = ? OR category = ?) AND is_active = 1 ORDER BY is_default DESC, usage_count DESC LIMIT 1");
            $stmt->execute([$promptSlug, $type]);
            $prompt = $stmt->fetch(PDO::FETCH_ASSOC);

            // Load AI settings
            $settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ai_%'");
            $aiSettings = [];
            foreach ($settingsStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
                $aiSettings[$s['setting_key']] = $s['setting_value'];
            }

            // Increment usage
            if ($prompt) {
                $pdo->prepare("UPDATE ai_prompts SET usage_count = usage_count + 1 WHERE id = ?")->execute([$prompt['id']]);
            }

            echo json_encode(['success' => true, 'data' => [
                'prompt' => $prompt,
                'settings' => $aiSettings,
                'context' => $context,
                'type' => $type,
                'message' => 'Prompt prepare - integration API externe requise pour generation',
            ]]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'settings':
    case 'list':
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'ai_%' ORDER BY setting_key");
            $settings = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            echo json_encode(['success' => true, 'data' => $settings]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_settings':
        try {
            $updated = 0;
            foreach ($input as $key => $value) {
                if (strpos($key, 'ai_') === 0) {
                    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, category) VALUES (?, ?, 'ai') ON DUPLICATE KEY UPDATE setting_value = ?");
                    $stmt->execute([$key, $value, $value]);
                    $updated++;
                }
            }
            echo json_encode(['success' => true, 'message' => "{$updated} parametres AI mis a jour"]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'prompts':
        try {
            $stmt = $pdo->query("SELECT * FROM ai_prompts ORDER BY category, name");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = [
                'total_prompts' => (int)$pdo->query("SELECT COUNT(*) FROM ai_prompts")->fetchColumn(),
                'active_prompts' => (int)$pdo->query("SELECT COUNT(*) FROM ai_prompts WHERE is_active = 1")->fetchColumn(),
                'total_usage' => (int)$pdo->query("SELECT COALESCE(SUM(usage_count), 0) FROM ai_prompts")->fetchColumn(),
                'top_prompts' => $pdo->query("SELECT name, category, usage_count FROM ai_prompts ORDER BY usage_count DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC),
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
