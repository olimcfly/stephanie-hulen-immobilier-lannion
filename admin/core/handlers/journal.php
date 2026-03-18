<?php
/**
 * API Handler: journal
 * Called via: /admin/api/router.php?module=journal&action=...
 * Table: editorial_journal - delegates to JournalController
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

$controllerPath = dirname(__DIR__, 2) . '/modules/ai/journal/JournalController.php';

if (file_exists($controllerPath)) {
    require_once $controllerPath;
    $ctrl = new JournalController($pdo);

    switch ($action) {
        case 'list':
            echo json_encode($ctrl->getList($input));
            break;
        case 'get':
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            echo json_encode($ctrl->getById($id));
            break;
        case 'create':
            echo json_encode($ctrl->create($input));
            break;
        case 'update':
            $id = (int)($input['id'] ?? 0);
            echo json_encode($ctrl->update($id, $input));
            break;
        case 'delete':
            $id = (int)($input['id'] ?? 0);
            echo json_encode($ctrl->delete($id));
            break;
        case 'update_status':
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            echo json_encode($ctrl->updateStatus($id, $status));
            break;
        case 'bulk_validate':
            $ids = $input['ids'] ?? [];
            echo json_encode($ctrl->bulkValidate($ids));
            break;
        case 'bulk_reject':
            $ids = $input['ids'] ?? [];
            echo json_encode($ctrl->bulkReject($ids));
            break;
        case 'bulk_delete':
            $ids = $input['ids'] ?? [];
            echo json_encode($ctrl->bulkDelete($ids));
            break;
        case 'stats':
            echo json_encode($ctrl->getStatsGlobal());
            break;
        case 'stats_by_channel':
            echo json_encode($ctrl->getStatsByChannel());
            break;
        case 'matrix':
            echo json_encode($ctrl->getMatrixData($input));
            break;
        case 'link_content':
            $id = (int)($input['id'] ?? 0);
            echo json_encode($ctrl->linkContent($id, $input));
            break;
        case 'mark_published':
            $id = (int)($input['id'] ?? 0);
            echo json_encode($ctrl->markPublished($id, $input));
            break;
        default:
            echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
    }
} else {
    // Fallback: direct SQL on editorial_journal
    switch ($action) {
        case 'list':
            try {
                $status = $input['status'] ?? $_GET['status'] ?? '';
                $where = ''; $params = [];
                if ($status) { $where = 'WHERE status = ?'; $params[] = $status; }
                $stmt = $pdo->prepare("SELECT * FROM editorial_journal {$where} ORDER BY year DESC, week_number DESC, created_at DESC");
                $stmt->execute($params);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'get':
            try {
                $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
                $stmt = $pdo->prepare("SELECT * FROM editorial_journal WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Entree non trouvee']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'create':
            try {
                $stmt = $pdo->prepare("INSERT INTO editorial_journal (title, description, keywords, content_type, profile_id, channel_id, awareness_level, objective_id, week_number, year, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $input['title'] ?? '', $input['description'] ?? '', $input['keywords'] ?? '',
                    $input['content_type'] ?? '', $input['profile_id'] ?? null, $input['channel_id'] ?? null,
                    $input['awareness_level'] ?? null, $input['objective_id'] ?? null,
                    (int)($input['week_number'] ?? date('W')), (int)($input['year'] ?? date('Y')),
                    $input['status'] ?? 'draft'
                ]);
                echo json_encode(['success' => true, 'message' => 'Entree creee', 'id' => $pdo->lastInsertId()]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'update':
            try {
                $id = (int)($input['id'] ?? 0);
                if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
                $allowed = ['title', 'description', 'keywords', 'content_type', 'profile_id', 'channel_id', 'awareness_level', 'objective_id', 'week_number', 'year', 'status'];
                $sets = []; $params = [];
                foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
                if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
                $params[] = $id;
                $pdo->prepare("UPDATE editorial_journal SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
                echo json_encode(['success' => true, 'message' => 'Entree mise a jour']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'delete':
            try {
                $id = (int)($input['id'] ?? 0);
                $pdo->prepare("DELETE FROM editorial_journal WHERE id = ?")->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Entree supprimee']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        case 'stats':
            try {
                $stats = [
                    'total' => (int)$pdo->query("SELECT COUNT(*) FROM editorial_journal")->fetchColumn(),
                    'draft' => (int)$pdo->query("SELECT COUNT(*) FROM editorial_journal WHERE status = 'draft'")->fetchColumn(),
                    'validated' => (int)$pdo->query("SELECT COUNT(*) FROM editorial_journal WHERE status = 'validated'")->fetchColumn(),
                    'published' => (int)$pdo->query("SELECT COUNT(*) FROM editorial_journal WHERE status = 'published'")->fetchColumn(),
                ];
                echo json_encode(['success' => true, 'data' => $stats]);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
    }
}
