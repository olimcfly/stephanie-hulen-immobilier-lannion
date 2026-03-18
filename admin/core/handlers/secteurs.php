<?php
/**
 * API Handler: secteurs
 * Called via: /admin/api/router.php?module=secteurs&action=...
 * Table: secteurs
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $search = $input['search'] ?? $_GET['search'] ?? '';
            $status = $input['status'] ?? $_GET['status'] ?? '';
            $type = $input['type_secteur'] ?? $_GET['type_secteur'] ?? '';
            $ville = $input['ville'] ?? $_GET['ville'] ?? '';
            $page = max(1, (int)($input['page'] ?? $_GET['page'] ?? 1));
            $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 25);
            $offset = ($page - 1) * $perPage;

            $where = [];
            $params = [];
            if ($status) { $where[] = "status = ?"; $params[] = $status; }
            if ($type) { $where[] = "type_secteur = ?"; $params[] = $type; }
            if ($ville) { $where[] = "ville = ?"; $params[] = $ville; }
            if ($search) { $where[] = "(nom LIKE ? OR slug LIKE ? OR ville LIKE ?)"; $s = "%{$search}%"; $params = array_merge($params, [$s, $s, $s]); }
            $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM secteurs {$whereSQL}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $params[] = $perPage;
            $params[] = $offset;
            $stmt = $pdo->prepare("SELECT * FROM secteurs {$whereSQL} ORDER BY nom ASC LIMIT ? OFFSET ?");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $data, 'total' => $total, 'page' => $page, 'per_page' => $perPage]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM secteurs WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Secteur non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO secteurs (nom, slug, ville, type_secteur, description, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['nom'] ?? '',
                $input['slug'] ?? '',
                $input['ville'] ?? '',
                $input['type_secteur'] ?? 'quartier',
                $input['description'] ?? '',
                $input['status'] ?? 'draft'
            ]);
            echo json_encode(['success' => true, 'message' => 'Secteur cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['nom', 'slug', 'ville', 'type_secteur', 'description', 'status'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ a mettre a jour']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE secteurs SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Secteur mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle_status':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $pdo->prepare("UPDATE secteurs SET status = IF(status = 'published', 'draft', 'published') WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Statut mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'duplicate':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $stmt = $pdo->prepare("SELECT * FROM secteurs WHERE id = ?");
            $stmt->execute([$id]);
            $orig = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orig) { echo json_encode(['success' => false, 'message' => 'Secteur non trouve']); break; }
            unset($orig['id']);
            $orig['nom'] .= ' (copie)';
            $orig['slug'] .= '-copie-' . time();
            $orig['status'] = 'draft';
            $cols = array_keys($orig);
            $pdo->prepare("INSERT INTO secteurs (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")")->execute(array_values($orig));
            echo json_encode(['success' => true, 'message' => 'Secteur duplique', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $pdo->prepare("DELETE FROM secteurs WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Secteur supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_delete':
        try {
            $ids = $input['ids'] ?? [];
            if (empty($ids)) { echo json_encode(['success' => false, 'message' => 'IDs requis']); break; }
            $ids = array_map('intval', $ids);
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM secteurs WHERE id IN ({$ph})")->execute($ids);
            echo json_encode(['success' => true, 'message' => count($ids) . ' secteur(s) supprime(s)']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
