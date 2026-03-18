<?php
/**
 * API Handler: rdv
 * Called via: /admin/api/router.php?module=rdv&action=...
 * Table: appointments
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

// Helper: build start_datetime/end_datetime from separate date + time fields
if (isset($input['date']) && isset($input['start_time'])) {
    $input['start_datetime'] = $input['date'] . ' ' . $input['start_time'] . ':00';
}
if (isset($input['date']) && isset($input['end_time'])) {
    $input['end_datetime'] = $input['date'] . ' ' . $input['end_time'] . ':00';
}
// Accept rdv_id as alias for id
if (isset($input['rdv_id']) && !isset($input['id'])) {
    $input['id'] = $input['rdv_id'];
}
if (isset($_GET['rdv_id']) && !isset($_GET['id'])) {
    $_GET['id'] = $_GET['rdv_id'];
}

switch ($action) {
    case 'list':
        try {
            $month = $input['month'] ?? $_GET['month'] ?? date('Y-m');
            $status = $input['status'] ?? $_GET['status'] ?? '';
            $firstDay = $month . '-01';
            $lastDay = date('Y-m-t', strtotime($firstDay));

            $where = ["DATE(start_datetime) BETWEEN ? AND ?"]; $params = [$firstDay, $lastDay];
            if ($status) { $where[] = "a.status = ?"; $params[] = $status; }
            $whereSQL = implode(' AND ', $where);

            $stmt = $pdo->prepare("SELECT a.*, l.firstname as lead_firstname, l.lastname as lead_lastname, l.phone as lead_phone FROM appointments a LEFT JOIN leads l ON a.lead_id = l.id WHERE {$whereSQL} ORDER BY a.start_datetime ASC");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_rdv':
    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT a.*, l.firstname as lead_firstname, l.lastname as lead_lastname FROM appointments a LEFT JOIN leads l ON a.lead_id = l.id WHERE a.id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'rdv' => $row, 'data' => $row] : ['success' => false, 'message' => 'RDV non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'add_rdv':
    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO appointments (title, description, type, start_datetime, end_datetime, location, lead_id, contact_id, property_id, status, notes, color) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['title'] ?? '', $input['description'] ?? null, $input['type'] ?? 'visite',
                $input['start_datetime'] ?? '', $input['end_datetime'] ?? '',
                $input['location'] ?? null, $input['lead_id'] ?? null,
                $input['contact_id'] ?? null, $input['property_id'] ?? null,
                $input['status'] ?? 'scheduled', $input['notes'] ?? null,
                $input['color'] ?? '#6366f1'
            ]);
            echo json_encode(['success' => true, 'message' => 'RDV cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()]);
        }
        break;

    case 'update_rdv':
    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['title', 'description', 'type', 'start_datetime', 'end_datetime', 'location', 'lead_id', 'contact_id', 'property_id', 'status', 'notes', 'color'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE appointments SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'RDV mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()]);
        }
        break;

    case 'update_status':
        try {
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            if (!$id || !in_array($status, ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'])) {
                echo json_encode(['success' => false, 'message' => 'Parametres invalides']); break;
            }
            $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?")->execute([$status, $id]);
            echo json_encode(['success' => true, 'message' => 'Statut mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_rdv':
    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $pdo->prepare("DELETE FROM appointments WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'RDV supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
