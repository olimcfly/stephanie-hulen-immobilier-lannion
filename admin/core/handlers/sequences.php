<?php
/**
 * API Handler: sequences
 * Called via: /admin/api/router.php?module=sequences&action=...
 * Table: crm_sequences, crm_sequence_steps, crm_sequence_enrollments
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $stmt = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM crm_sequence_steps WHERE sequence_id = s.id) as steps_count FROM crm_sequences s ORDER BY s.created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM crm_sequences WHERE id = ?");
            $stmt->execute([$id]);
            $seq = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($seq) {
                $stepsStmt = $pdo->prepare("SELECT * FROM crm_sequence_steps WHERE sequence_id = ? ORDER BY step_order ASC");
                $stepsStmt->execute([$id]);
                $seq['steps'] = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            echo json_encode($seq ? ['success' => true, 'data' => $seq] : ['success' => false, 'message' => 'Sequence non trouvee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO crm_sequences (name, description, trigger_type, trigger_value, target_segment, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['name'] ?? '', $input['description'] ?? '', $input['trigger_type'] ?? 'manual',
                $input['trigger_value'] ?? null, $input['target_segment'] ?? null, (int)($input['is_active'] ?? 0)
            ]);
            echo json_encode(['success' => true, 'message' => 'Sequence creee', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['name', 'description', 'trigger_type', 'trigger_value', 'target_segment', 'is_active', 'from_name', 'from_email', 'reply_to', 'send_window_start', 'send_window_end', 'send_days'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE crm_sequences SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Sequence mise a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM crm_sequences WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Sequence supprimee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("UPDATE crm_sequences SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Sequence activee/desactivee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
