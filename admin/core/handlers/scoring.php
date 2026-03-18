<?php
/**
 * API Handler: scoring
 * Called via: /admin/api/router.php?module=scoring&action=...
 * Table: scoring_rules, leads
 * Mirrors existing admin/modules/marketing/scoring/api.php
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'get_rules':
    case 'list':
        try {
            $rules = $pdo->query("SELECT * FROM scoring_rules ORDER BY category, points DESC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $rules]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_rule':
        try {
            $id = (int)($input['rule_id'] ?? $_GET['rule_id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM scoring_rules WHERE id = ?");
            $stmt->execute([$id]);
            $rule = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($rule ? ['success' => true, 'data' => $rule] : ['success' => false, 'message' => 'Regle non trouvee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'add_rule':
    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO scoring_rules (name, category, field_name, operator, field_value, points, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([
                trim($input['name'] ?? ''), $input['category'] ?? 'engagement',
                $input['field_name'] ?? 'email', $input['operator'] ?? 'not_empty',
                !empty($input['field_value']) ? trim($input['field_value']) : null,
                (int)($input['points'] ?? 10)
            ]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_rule':
    case 'update':
        try {
            $id = (int)($input['rule_id'] ?? $input['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE scoring_rules SET name = ?, category = ?, field_name = ?, operator = ?, field_value = ?, points = ? WHERE id = ?");
            $stmt->execute([
                trim($input['name'] ?? ''), $input['category'] ?? 'engagement',
                $input['field_name'] ?? 'email', $input['operator'] ?? 'not_empty',
                !empty($input['field_value']) ? trim($input['field_value']) : null,
                (int)($input['points'] ?? 10), $id
            ]);
            echo json_encode(['success' => true, 'message' => 'Regle mise a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle_rule':
        try {
            $id = (int)($input['rule_id'] ?? $input['id'] ?? 0);
            $isActive = (int)($input['is_active'] ?? 0);
            $pdo->prepare("UPDATE scoring_rules SET is_active = ? WHERE id = ?")->execute([$isActive, $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_rule':
    case 'delete':
        try {
            $id = (int)($input['rule_id'] ?? $input['id'] ?? 0);
            $pdo->prepare("DELETE FROM scoring_rules WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Regle supprimee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'recalculate_all':
        try {
            $rules = $pdo->query("SELECT * FROM scoring_rules WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);
            $leads = $pdo->query("SELECT * FROM leads")->fetchAll(PDO::FETCH_ASSOC);
            $updated = 0;
            foreach ($leads as $lead) {
                $score = 0;
                $createdDays = floor((time() - strtotime($lead['created_at'])) / 86400);
                foreach ($rules as $rule) {
                    $fieldValue = $rule['field_name'] === 'created_days' ? $createdDays : ($lead[$rule['field_name']] ?? null);
                    $matched = false;
                    switch ($rule['operator']) {
                        case 'equals': $matched = ($fieldValue == $rule['field_value']); break;
                        case 'not_equals': $matched = ($fieldValue != $rule['field_value']); break;
                        case 'not_empty': $matched = !empty($fieldValue); break;
                        case 'empty': $matched = empty($fieldValue); break;
                        case 'greater_than': $matched = (floatval($fieldValue) > floatval($rule['field_value'])); break;
                        case 'less_than': $matched = (floatval($fieldValue) < floatval($rule['field_value'])); break;
                        case 'contains': $matched = (stripos($fieldValue, $rule['field_value']) !== false); break;
                    }
                    if ($matched) $score += $rule['points'];
                }
                $temperature = $score >= 70 ? 'hot' : ($score >= 35 ? 'warm' : 'cold');
                $pdo->prepare("UPDATE leads SET score = ?, temperature = ?, score_updated_at = NOW() WHERE id = ?")->execute([$score, $temperature, $lead['id']]);
                $updated++;
            }
            echo json_encode(['success' => true, 'updated' => $updated]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_stats':
    case 'stats':
        try {
            $stats = [
                'total' => (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
                'hot' => (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE temperature = 'hot'")->fetchColumn(),
                'warm' => (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE temperature = 'warm'")->fetchColumn(),
                'cold' => (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE temperature = 'cold'")->fetchColumn(),
                'avg_score' => round((float)$pdo->query("SELECT AVG(score) FROM leads")->fetchColumn(), 1),
                'rules_count' => (int)$pdo->query("SELECT COUNT(*) FROM scoring_rules WHERE is_active = 1")->fetchColumn()
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
