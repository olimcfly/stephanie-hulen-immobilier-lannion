<?php
/**
 * API Handler: gmb
 * Called via: /admin/api/router.php?module=gmb&action=...
 * GMB B2B Prospection - delegates to ContactController & SequenceController
 * Tables: gmb_contacts, gmb_contact_lists, gmb_email_sequences
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

// Load controllers
$gmbDir = dirname(__DIR__, 2) . '/modules/social/gmb/';

switch ($action) {
    // --- Contacts ---
    case 'list':
    case 'contacts':
        try {
            if (file_exists($gmbDir . 'ContactController.php')) {
                require_once $gmbDir . 'ContactController.php';
                $ctrl = new ContactController($pdo);
                $result = $ctrl->getContacts($input);
                echo json_encode($result);
            } else {
                $page = max(1, (int)($input['page'] ?? $_GET['page'] ?? 1));
                $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 25);
                $offset = ($page - 1) * $perPage;
                $stmt = $pdo->prepare("SELECT * FROM gmb_contacts ORDER BY created_at DESC LIMIT ? OFFSET ?");
                $stmt->execute([$perPage, $offset]);
                $total = (int)$pdo->query("SELECT COUNT(*) FROM gmb_contacts")->fetchColumn();
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_contact':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM gmb_contacts WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Contact non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_contact':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['name', 'email', 'phone', 'website', 'category', 'address', 'city', 'status', 'notes', 'rating', 'reviews_count'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE gmb_contacts SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Contact mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_contact':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM gmb_contacts WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Contact supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_delete':
        try {
            $ids = $input['ids'] ?? [];
            if (empty($ids)) { echo json_encode(['success' => false, 'message' => 'Aucun ID']); break; }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM gmb_contacts WHERE id IN ({$placeholders})")->execute(array_map('intval', $ids));
            echo json_encode(['success' => true, 'message' => count($ids) . ' contacts supprimes']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_update_status':
        try {
            $ids = $input['ids'] ?? [];
            $status = $input['status'] ?? '';
            if (empty($ids) || !$status) { echo json_encode(['success' => false, 'message' => 'IDs et status requis']); break; }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$status], array_map('intval', $ids));
            $pdo->prepare("UPDATE gmb_contacts SET status = ? WHERE id IN ({$placeholders})")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Statuts mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // --- Lists ---
    case 'lists':
        try {
            $stmt = $pdo->query("SELECT cl.*, (SELECT COUNT(*) FROM gmb_contact_list_members WHERE list_id = cl.id) as members_count FROM gmb_contact_lists cl ORDER BY cl.created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_list':
        try {
            $stmt = $pdo->prepare("INSERT INTO gmb_contact_lists (name, description) VALUES (?, ?)");
            $stmt->execute([$input['name'] ?? '', $input['description'] ?? '']);
            echo json_encode(['success' => true, 'message' => 'Liste creee', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'add_to_list':
        try {
            $listId = (int)($input['list_id'] ?? 0);
            $contactIds = $input['contact_ids'] ?? [];
            $added = 0;
            foreach ($contactIds as $cid) {
                try {
                    $pdo->prepare("INSERT IGNORE INTO gmb_contact_list_members (list_id, contact_id) VALUES (?, ?)")->execute([$listId, (int)$cid]);
                    $added++;
                } catch (PDOException $e) { /* duplicate ignored */ }
            }
            echo json_encode(['success' => true, 'message' => "{$added} contacts ajoutes a la liste"]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // --- Sequences ---
    case 'sequences':
        try {
            if (file_exists($gmbDir . 'SequenceController.php')) {
                require_once $gmbDir . 'SequenceController.php';
                $ctrl = new SequenceController($pdo);
                echo json_encode($ctrl->getSequences());
            } else {
                $stmt = $pdo->query("SELECT * FROM gmb_email_sequences ORDER BY created_at DESC");
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // --- Stats ---
    case 'stats':
        try {
            $stats = [
                'total_contacts' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_contacts")->fetchColumn(),
                'contacts_with_email' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_contacts WHERE email IS NOT NULL AND email != ''")->fetchColumn(),
                'total_lists' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_contact_lists")->fetchColumn(),
                'total_sequences' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_email_sequences")->fetchColumn(),
                'active_sequences' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_email_sequences WHERE is_active = 1")->fetchColumn(),
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'export':
        try {
            if (file_exists($gmbDir . 'ContactController.php')) {
                require_once $gmbDir . 'ContactController.php';
                $ctrl = new ContactController($pdo);
                $ctrl->exportCSV($input);
            } else {
                $stmt = $pdo->query("SELECT * FROM gmb_contacts ORDER BY created_at DESC");
                $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode(['success' => true, 'data' => $contacts]);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
