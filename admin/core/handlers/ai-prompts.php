<?php
/**
 * API Handler: ai-prompts
 * Called via: /admin/api/router.php?module=ai-prompts&action=...
 * Table: ai_prompts
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $category   = $input['category']   ?? $_GET['category']   ?? '';
            $activeOnly = ($input['active_only'] ?? $_GET['active_only'] ?? '') === '1';
            $wheres = []; $params = [];
            if ($category)   { $wheres[] = 'category = ?'; $params[] = $category; }
            if ($activeOnly) { $wheres[] = 'is_active = 1'; }
            $where = $wheres ? 'WHERE ' . implode(' AND ', $wheres) : '';
            $stmt = $pdo->prepare("SELECT * FROM ai_prompts {$where} ORDER BY is_default DESC, category, name");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // Return both 'data' (legacy) and 'prompts' (editor key)
            echo json_encode(['success' => true, 'data' => $rows, 'prompts' => $rows]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM ai_prompts WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'prompt' => $row, 'data' => $row] : ['success' => false, 'message' => 'Prompt non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_default':
        try {
            $category = $input['category'] ?? $_GET['category'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM ai_prompts WHERE category = ? AND is_default = 1 AND is_active = 1 LIMIT 1");
            $stmt->execute([$category]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row && $category) {
                // Fallback: any active prompt in this category
                $stmt2 = $pdo->prepare("SELECT * FROM ai_prompts WHERE category = ? AND is_active = 1 ORDER BY id LIMIT 1");
                $stmt2->execute([$category]);
                $row = $stmt2->fetch(PDO::FETCH_ASSOC);
            }
            if (!$row) {
                // Fallback: any active general prompt
                $stmt3 = $pdo->prepare("SELECT * FROM ai_prompts WHERE category = 'general' AND is_default = 1 AND is_active = 1 LIMIT 1");
                $stmt3->execute();
                $row = $stmt3->fetch(PDO::FETCH_ASSOC);
            }
            echo json_encode($row ? ['success' => true, 'prompt' => $row] : ['success' => false, 'message' => 'Aucun prompt par défaut']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'track_usage':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id) {
                // Try to increment usage_count if column exists
                try {
                    $pdo->prepare("UPDATE ai_prompts SET usage_count = COALESCE(usage_count,0)+1, last_used_at = NOW() WHERE id = ?")->execute([$id]);
                } catch (PDOException $e2) {
                    // Column might not exist – silently ignore
                }
            }
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => true]); // Non-critical
        }
        break;

    case 'create':
        try {
            $slug = $input['slug'] ?? '';
            if (!$slug && !empty($input['name'])) {
                $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', trim($input['name'])));
            }
            $stmt = $pdo->prepare("INSERT INTO ai_prompts (name, slug, system_prompt, user_prompt_template, category, model, temperature, max_tokens, is_active, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['name'] ?? '', $slug, $input['system_prompt'] ?? '',
                $input['user_prompt_template'] ?? '', $input['category'] ?? 'general',
                $input['model'] ?? null, (float)($input['temperature'] ?? 0.7),
                (int)($input['max_tokens'] ?? 2000), (int)($input['is_active'] ?? 1),
                (int)($input['is_default'] ?? 0)
            ]);
            echo json_encode(['success' => true, 'message' => 'Prompt cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['name', 'slug', 'system_prompt', 'user_prompt_template', 'category', 'model', 'temperature', 'max_tokens', 'is_active', 'is_default'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE ai_prompts SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Prompt mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM ai_prompts WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Prompt supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("UPDATE ai_prompts SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Prompt active/desactive']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'set_default':
        try {
            $id = (int)($input['id'] ?? 0);
            $category = $input['category'] ?? '';
            if ($category) {
                $pdo->prepare("UPDATE ai_prompts SET is_default = 0 WHERE category = ?")->execute([$category]);
            }
            $pdo->prepare("UPDATE ai_prompts SET is_default = 1 WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Prompt defini par defaut']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'duplicate':
        try {
            $id = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM ai_prompts WHERE id = ?");
            $stmt->execute([$id]);
            $original = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$original) { echo json_encode(['success' => false, 'message' => 'Prompt non trouve']); break; }

            $newName = $original['name'] . ' (copie)';
            $newSlug = $original['slug'] . '-copy-' . time();
            $ins = $pdo->prepare("INSERT INTO ai_prompts (name, slug, system_prompt, user_prompt_template, category, model, temperature, max_tokens, is_active, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
            $ins->execute([$newName, $newSlug, $original['system_prompt'], $original['user_prompt_template'] ?? '', $original['category'], $original['model'] ?? null, $original['temperature'] ?? 0.7, $original['max_tokens'] ?? 2000, 0]);
            echo json_encode(['success' => true, 'message' => 'Prompt duplique', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'categories':
        try {
            $stmt = $pdo->query("SELECT DISTINCT category FROM ai_prompts ORDER BY category");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_delete':
        try {
            $rawIds = $input['ids'] ?? [];
            $ids = is_string($rawIds) ? json_decode($rawIds, true) ?? [] : $rawIds;
            if (empty($ids)) { echo json_encode(['success' => false, 'error' => 'Aucun ID fourni']); break; }
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM ai_prompts WHERE id IN ({$placeholders})")->execute($ids);
            echo json_encode(['success' => true, 'message' => count($ids) . ' prompt(s) supprime(s)']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_status':
        try {
            $rawIds = $input['ids'] ?? [];
            $ids = is_string($rawIds) ? json_decode($rawIds, true) ?? [] : $rawIds;
            $isActive = (int)($input['is_active'] ?? 0);
            if (empty($ids)) { echo json_encode(['success' => false, 'error' => 'Aucun ID fourni']); break; }
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$isActive], $ids);
            $pdo->prepare("UPDATE ai_prompts SET is_active = ? WHERE id IN ({$placeholders})")->execute($params);
            echo json_encode(['success' => true, 'message' => count($ids) . ' prompt(s) mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
