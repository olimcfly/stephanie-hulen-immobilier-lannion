<?php
/**
 * API Handler: estimation
 * Called via: /admin/api/router.php?module=estimation&action=...
 * Table: estimations
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $status = $input['status'] ?? $_GET['status'] ?? '';
            $search = $input['search'] ?? $_GET['search'] ?? '';
            $page = max(1, (int)($input['page'] ?? $_GET['page'] ?? 1));
            $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 20);
            $offset = ($page - 1) * $perPage;

            $where = []; $params = [];
            if ($status) { $where[] = "statut = ?"; $params[] = $status; }
            if ($search) { $where[] = "(nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR telephone LIKE ? OR ville LIKE ?)"; $s = "%{$search}%"; $params = array_merge($params, [$s, $s, $s, $s, $s]); }
            $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM estimations {$whereSQL}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $params[] = $perPage; $params[] = $offset;
            $stmt = $pdo->prepare("SELECT * FROM estimations {$whereSQL} ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM estimations WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Estimation non trouvee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_statut':
        try {
            $id = (int)($input['id'] ?? 0);
            $statut = $input['statut'] ?? '';
            if (!$id || !in_array($statut, ['en_attente', 'traitee', 'convertie'])) {
                echo json_encode(['success' => false, 'message' => 'Parametres invalides']);
                break;
            }
            $pdo->prepare("UPDATE estimations SET statut = ? WHERE id = ?")->execute([$statut, $id]);
            echo json_encode(['success' => true, 'message' => 'Statut mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_notes':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $pdo->prepare("UPDATE estimations SET notes = ? WHERE id = ?")->execute([trim($input['notes'] ?? ''), $id]);
            echo json_encode(['success' => true, 'message' => 'Notes sauvegardees']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $pdo->prepare("DELETE FROM estimations WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Demande supprimee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = $pdo->query("SELECT COUNT(*) as total, SUM(statut='en_attente') as en_attente, SUM(statut='traitee') as traitee, SUM(statut='convertie') as convertie FROM estimations")->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
