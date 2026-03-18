<?php
/**
 * API Handler: leads
 * Called via: /admin/api/router.php?module=leads&action=...
 * Table: leads
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $search = $input['search'] ?? $_GET['search'] ?? '';
            $status = $input['status'] ?? $_GET['status'] ?? '';
            $type = $input['type'] ?? $_GET['type'] ?? '';
            $source = $input['source'] ?? $_GET['source'] ?? '';
            $temperature = $input['temperature'] ?? $_GET['temperature'] ?? '';
            $page = max(1, (int)($input['page'] ?? $_GET['page'] ?? 1));
            $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 20);
            $offset = ($page - 1) * $perPage;

            $where = ['1=1']; $params = [];
            if ($search) { $where[] = "(firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?)"; $s = "%{$search}%"; $params = array_merge($params, [$s,$s,$s,$s,$s]); }
            if ($status) { $where[] = "status = ?"; $params[] = $status; }
            if ($type) { $where[] = "type = ?"; $params[] = $type; }
            if ($source) { $where[] = "source = ?"; $params[] = $source; }
            if ($temperature) { $where[] = "temperature = ?"; $params[] = $temperature; }
            $whereSQL = implode(' AND ', $where);

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE {$whereSQL}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $sortBy = in_array($input['sort'] ?? '', ['firstname','lastname','email','status','temperature','score','created_at']) ? $input['sort'] : 'created_at';
            $sortOrder = strtoupper($input['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

            $params[] = $perPage; $params[] = $offset;
            $stmt = $pdo->prepare("SELECT * FROM leads WHERE {$whereSQL} ORDER BY {$sortBy} {$sortOrder} LIMIT ? OFFSET ?");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_lead':
    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'lead' => $row, 'data' => $row] : ['success' => false, 'message' => 'Lead non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'add_lead':
    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO leads (firstname, lastname, email, phone, address, city, postal_code, source, type, status, temperature, budget_min, budget_max, property_type, notes, tags) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $input['firstname'] ?? '', $input['lastname'] ?? '', $input['email'] ?? null,
                $input['phone'] ?? null, $input['address'] ?? null, $input['city'] ?? null,
                $input['postal_code'] ?? null, $input['source'] ?? 'site_web', $input['type'] ?? 'vendeur',
                $input['status'] ?? 'new', $input['temperature'] ?? 'warm',
                $input['budget_min'] ?? null, $input['budget_max'] ?? null,
                $input['property_type'] ?? null, $input['notes'] ?? null, $input['tags'] ?? null
            ]);
            echo json_encode(['success' => true, 'message' => 'Lead cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_lead':
    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['firstname','lastname','email','phone','address','city','postal_code','source','type','status','temperature','score','budget_min','budget_max','property_type','surface_min','surface_max','rooms_min','bedrooms_min','notes','tags','next_action','next_action_date','last_contact'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE leads SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Lead mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_lead':
    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $pdo->prepare("DELETE FROM leads WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Lead supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_delete':
        try {
            $rawIds = $input['ids'] ?? [];
            $ids = array_map('intval', is_string($rawIds) ? json_decode($rawIds, true) ?? [] : $rawIds);
            if (empty($ids)) { echo json_encode(['success' => false, 'message' => 'IDs requis']); break; }
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM leads WHERE id IN ({$ph})")->execute($ids);
            echo json_encode(['success' => true, 'message' => count($ids) . ' lead(s) supprime(s)']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_status':
    case 'bulk_update_status':
        try {
            $rawIds = $input['ids'] ?? [];
            $ids = array_map('intval', is_string($rawIds) ? json_decode($rawIds, true) ?? [] : $rawIds);
            $status = $input['status'] ?? '';
            if (empty($ids) || !$status) { echo json_encode(['success' => false, 'message' => 'IDs et statut requis']); break; }
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("UPDATE leads SET status = ? WHERE id IN ({$ph})")->execute(array_merge([$status], $ids));
            echo json_encode(['success' => true, 'message' => 'Statut mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = $pdo->query("SELECT COUNT(*) as total, SUM(status='new') as new_leads, SUM(temperature='hot') as hot, SUM(temperature='warm') as warm, SUM(temperature='cold') as cold, AVG(score) as avg_score FROM leads")->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'export':
        try {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="leads_export_' . date('Y-m-d') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','Prenom','Nom','Email','Telephone','Ville','Source','Type','Statut','Temperature','Score','Date creation']);
            $rows = $pdo->query("SELECT * FROM leads ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                fputcsv($out, [$r['id'],$r['firstname'],$r['lastname'],$r['email'],$r['phone'],$r['city'],$r['source'],$r['type'],$r['status'],$r['temperature'],$r['score'],$r['created_at']]);
            }
            fclose($out);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
