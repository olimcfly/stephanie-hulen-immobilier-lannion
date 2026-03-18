<?php
/**
 * API Handler: contact
 * Called via: /admin/api/router.php?module=contact&action=...
 * Table: contacts
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $search = $input['search'] ?? $_GET['search'] ?? '';
            $category = $input['category'] ?? $_GET['category'] ?? '';
            $status = $input['status'] ?? $_GET['status'] ?? '';
            $page = max(1, (int)($input['page'] ?? $_GET['page'] ?? 1));
            $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 25);
            $offset = ($page - 1) * $perPage;

            $where = 'WHERE 1=1'; $params = [];
            if ($search) { $where .= " AND (firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR company LIKE ?)"; $s = "%{$search}%"; $params = array_merge($params, [$s,$s,$s,$s]); }
            if ($category) { $where .= " AND category = ?"; $params[] = $category; }
            if ($status) { $where .= " AND status = ?"; $params[] = $status; }

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM contacts {$where}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $params[] = $perPage; $params[] = $offset;
            $stmt = $pdo->prepare("SELECT * FROM contacts {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_contact':
    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'contact' => $row, 'data' => $row] : ['success' => false, 'message' => 'Contact non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'add_contact':
    case 'create':
        try {
            $allowed = ['civility','firstname','lastname','email','phone','mobile','address','city','postal_code','country','company','job_title','category','status','rating','birthday','tags','notes'];
            $cols = []; $vals = []; $placeholders = [];
            foreach ($allowed as $col) {
                if (isset($input[$col]) && $input[$col] !== '') {
                    $cols[] = $col;
                    $vals[] = $input[$col];
                    $placeholders[] = '?';
                }
            }
            if (empty($cols)) { echo json_encode(['success' => false, 'message' => 'Aucune donnee']); break; }
            $stmt = $pdo->prepare("INSERT INTO contacts (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")");
            $stmt->execute($vals);
            echo json_encode(['success' => true, 'message' => 'Contact cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_contact':
    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['civility','firstname','lastname','email','phone','mobile','address','city','postal_code','country','company','job_title','category','status','rating','birthday','tags','notes'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE contacts SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Contact mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_contact':
    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $pdo->prepare("DELETE FROM contacts WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Contact supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_delete':
        try {
            $rawIds = $input['ids'] ?? [];
            $ids = array_map('intval', is_string($rawIds) ? json_decode($rawIds, true) ?? [] : $rawIds);
            if (empty($ids)) { echo json_encode(['success' => false, 'message' => 'Aucun ID']); break; }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM contacts WHERE id IN ({$placeholders})")->execute($ids);
            echo json_encode(['success' => true, 'message' => count($ids) . ' contacts supprimes']);
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
            if (empty($ids) || !$status) { echo json_encode(['success' => false, 'message' => 'IDs et status requis']); break; }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$status], $ids);
            $pdo->prepare("UPDATE contacts SET status = ? WHERE id IN ({$placeholders})")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Statuts mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'export':
        try {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="contacts_export_' . date('Y-m-d') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','Civilite','Prenom','Nom','Email','Telephone','Mobile','Ville','Entreprise','Categorie','Statut','Date creation']);
            $rows = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                fputcsv($out, [$r['id'],$r['civility'] ?? '',$r['firstname'],$r['lastname'],$r['email'],$r['phone'],$r['mobile'] ?? '',$r['city'],$r['company'] ?? '',$r['category'],$r['status'],$r['created_at']]);
            }
            fclose($out);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'categories':
        try {
            $stmt = $pdo->query("SELECT DISTINCT category FROM contacts ORDER BY category");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = [
                'total' => (int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn(),
                'active' => (int)$pdo->query("SELECT COUNT(*) FROM contacts WHERE status = 'active'")->fetchColumn(),
                'by_category' => [],
            ];
            $catStmt = $pdo->query("SELECT category, COUNT(*) as count FROM contacts GROUP BY category ORDER BY count DESC");
            foreach ($catStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $stats['by_category'][$row['category']] = (int)$row['count'];
            }
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
