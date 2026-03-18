<?php
/**
 * API Handler: agents
 * Called via: /admin/api/router.php?module=agents&action=...
 * AI Agents configuration and management
 * Tables: ai_agents (if exists), ai_prompts
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

// Check if ai_agents table exists
$agentsTableExists = false;
try {
    $pdo->query("SELECT 1 FROM ai_agents LIMIT 1");
    $agentsTableExists = true;
} catch (PDOException $e) {
    // Table does not exist, use static config
}

switch ($action) {
    case 'list':
        try {
            if ($agentsTableExists) {
                $stmt = $pdo->query("SELECT * FROM ai_agents ORDER BY name ASC");
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } else {
                // Static agent definitions
                $agents = [
                    ['id' => 'content-writer', 'name' => 'Redacteur Web', 'description' => 'Genere des articles et contenus web optimises SEO', 'category' => 'content', 'is_active' => true],
                    ['id' => 'seo-analyst', 'name' => 'Analyste SEO', 'description' => 'Analyse et optimise le referencement des pages', 'category' => 'seo', 'is_active' => true],
                    ['id' => 'social-manager', 'name' => 'Community Manager', 'description' => 'Cree des posts pour les reseaux sociaux', 'category' => 'social', 'is_active' => true],
                    ['id' => 'email-writer', 'name' => 'Email Marketing', 'description' => 'Redige des emails et sequences marketing', 'category' => 'email', 'is_active' => true],
                    ['id' => 'property-describer', 'name' => 'Descripteur Immobilier', 'description' => 'Genere des descriptions de biens immobiliers', 'category' => 'immobilier', 'is_active' => true],
                    ['id' => 'lead-qualifier', 'name' => 'Qualificateur de Leads', 'description' => 'Analyse et qualifie les prospects', 'category' => 'crm', 'is_active' => true],
                ];
                echo json_encode(['success' => true, 'data' => $agents, 'source' => 'static']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = $input['id'] ?? $_GET['id'] ?? '';
            if ($agentsTableExists) {
                $stmt = $pdo->prepare("SELECT * FROM ai_agents WHERE id = ?");
                $stmt->execute([$id]);
                $agent = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $agent = null;
            }
            echo json_encode($agent ? ['success' => true, 'data' => $agent] : ['success' => false, 'message' => 'Agent non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create':
        try {
            if (!$agentsTableExists) {
                echo json_encode(['success' => false, 'message' => 'Table ai_agents non disponible']);
                break;
            }
            $stmt = $pdo->prepare("INSERT INTO ai_agents (name, description, category, system_prompt, model, temperature, max_tokens, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['name'] ?? '', $input['description'] ?? '', $input['category'] ?? 'general',
                $input['system_prompt'] ?? '', $input['model'] ?? 'gpt-4',
                (float)($input['temperature'] ?? 0.7), (int)($input['max_tokens'] ?? 2000),
                (int)($input['is_active'] ?? 1)
            ]);
            echo json_encode(['success' => true, 'message' => 'Agent cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            if (!$agentsTableExists) {
                echo json_encode(['success' => false, 'message' => 'Table ai_agents non disponible']);
                break;
            }
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['name', 'description', 'category', 'system_prompt', 'model', 'temperature', 'max_tokens', 'is_active'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE ai_agents SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Agent mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            if (!$agentsTableExists) {
                echo json_encode(['success' => false, 'message' => 'Table ai_agents non disponible']);
                break;
            }
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM ai_agents WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Agent supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle':
        try {
            if (!$agentsTableExists) {
                echo json_encode(['success' => false, 'message' => 'Table ai_agents non disponible']);
                break;
            }
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("UPDATE ai_agents SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Agent active/desactive']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
