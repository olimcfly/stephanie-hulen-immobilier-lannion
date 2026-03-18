<?php
/**
 * API Handler: launchpad
 * Called via: /admin/api/router.php?module=launchpad&action=...
 * Table: launchpad_diagnostic
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $stmt = $pdo->query("SELECT * FROM launchpad_diagnostic ORDER BY created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM launchpad_diagnostic WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if (!empty($row['answers'])) $row['answers_parsed'] = json_decode($row['answers'], true);
                if (!empty($row['scores'])) $row['scores_parsed'] = json_decode($row['scores'], true);
            }
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Diagnostic non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create':
    case 'save':
        try {
            $answers = isset($input['answers']) ? (is_string($input['answers']) ? $input['answers'] : json_encode($input['answers'])) : '{}';
            $scores = isset($input['scores']) ? (is_string($input['scores']) ? $input['scores'] : json_encode($input['scores'])) : '{}';
            $stmt = $pdo->prepare("INSERT INTO launchpad_diagnostic (user_id, answers, scores, parcours_principal, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['user_id'] ?? null, $answers, $scores,
                $input['parcours_principal'] ?? '', $input['status'] ?? 'completed'
            ]);
            echo json_encode(['success' => true, 'message' => 'Diagnostic sauvegarde', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $sets = []; $params = [];
            if (isset($input['answers'])) {
                $sets[] = "answers = ?";
                $params[] = is_string($input['answers']) ? $input['answers'] : json_encode($input['answers']);
            }
            if (isset($input['scores'])) {
                $sets[] = "scores = ?";
                $params[] = is_string($input['scores']) ? $input['scores'] : json_encode($input['scores']);
            }
            if (isset($input['parcours_principal'])) { $sets[] = "parcours_principal = ?"; $params[] = $input['parcours_principal']; }
            if (isset($input['status'])) { $sets[] = "status = ?"; $params[] = $input['status']; }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE launchpad_diagnostic SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Diagnostic mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM launchpad_diagnostic WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Diagnostic supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_latest':
        try {
            $userId = $input['user_id'] ?? $_GET['user_id'] ?? null;
            if ($userId) {
                $stmt = $pdo->prepare("SELECT * FROM launchpad_diagnostic WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
                $stmt->execute([$userId]);
            } else {
                $stmt = $pdo->query("SELECT * FROM launchpad_diagnostic ORDER BY created_at DESC LIMIT 1");
            }
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                if (!empty($row['answers'])) $row['answers_parsed'] = json_decode($row['answers'], true);
                if (!empty($row['scores'])) $row['scores_parsed'] = json_decode($row['scores'], true);
            }
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Aucun diagnostic trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = [
                'total' => (int)$pdo->query("SELECT COUNT(*) FROM launchpad_diagnostic")->fetchColumn(),
                'completed' => (int)$pdo->query("SELECT COUNT(*) FROM launchpad_diagnostic WHERE status = 'completed'")->fetchColumn(),
                'by_parcours' => [],
            ];
            $pStmt = $pdo->query("SELECT parcours_principal, COUNT(*) as count FROM launchpad_diagnostic WHERE parcours_principal IS NOT NULL AND parcours_principal != '' GROUP BY parcours_principal ORDER BY count DESC");
            foreach ($pStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $stats['by_parcours'][$row['parcours_principal']] = (int)$row['count'];
            }
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
