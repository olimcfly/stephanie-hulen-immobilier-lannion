<?php
/**
 * API Handler: crm
 * Called via: /admin/api/router.php?module=crm&action=...
 * Tables: leads, pipeline_stages
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

// Ensure required tables exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pipeline_stages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        color VARCHAR(20) DEFAULT '#6366f1',
        position INT DEFAULT 0,
        is_won TINYINT(1) DEFAULT 0,
        is_lost TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Add pipeline_stage_id and estimated_value to leads if missing
    $cols = array_column($pdo->query("SHOW COLUMNS FROM leads")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('pipeline_stage_id', $cols)) {
        $pdo->exec("ALTER TABLE leads ADD COLUMN pipeline_stage_id INT DEFAULT NULL");
    }
    if (!in_array('estimated_value', $cols)) {
        $pdo->exec("ALTER TABLE leads ADD COLUMN estimated_value DECIMAL(12,2) DEFAULT 0");
    }

    // Insert default stages if empty
    $stageCount = (int)$pdo->query("SELECT COUNT(*) FROM pipeline_stages")->fetchColumn();
    if ($stageCount === 0) {
        $pdo->exec("INSERT INTO pipeline_stages (name, color, position, is_won, is_lost) VALUES
            ('Nouveau', '#6366f1', 1, 0, 0),
            ('Contacté', '#3b82f6', 2, 0, 0),
            ('Qualifié', '#f59e0b', 3, 0, 0),
            ('Proposition', '#8b5cf6', 4, 0, 0),
            ('Négociation', '#ec4899', 5, 0, 0),
            ('Gagné', '#10b981', 6, 1, 0),
            ('Perdu', '#ef4444', 7, 0, 1)");
    }

    // Deduplicate stages (keep lowest id per name)
    $dupes = $pdo->query("SELECT MIN(id) as keep_id, name FROM pipeline_stages GROUP BY name HAVING COUNT(*) > 1")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($dupes as $d) {
        $pdo->prepare("UPDATE leads SET pipeline_stage_id = ? WHERE pipeline_stage_id IN (SELECT id FROM pipeline_stages WHERE name = ? AND id != ?)")->execute([$d['keep_id'], $d['name'], $d['keep_id']]);
        $pdo->prepare("DELETE FROM pipeline_stages WHERE name = ? AND id != ?")->execute([$d['name'], $d['keep_id']]);
    }
} catch (PDOException $e) {
    // Tables may already exist, continue
}

switch ($action) {
    case 'list':
    case 'pipeline':
        try {
            $stages = $pdo->query("SELECT * FROM pipeline_stages ORDER BY position ASC")->fetchAll(PDO::FETCH_ASSOC);
            $leads = $pdo->query("SELECT l.*, ps.name as stage_name, ps.color as stage_color FROM leads l LEFT JOIN pipeline_stages ps ON l.pipeline_stage_id = ps.id ORDER BY l.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => ['stages' => $stages, 'leads' => $leads]]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'move_lead':
        try {
            $leadId = (int)($input['lead_id'] ?? 0);
            $stageId = (int)($input['stage_id'] ?? 0);
            if (!$leadId || !$stageId) { echo json_encode(['success' => false, 'message' => 'lead_id et stage_id requis']); break; }
            $pdo->prepare("UPDATE leads SET pipeline_stage_id = ? WHERE id = ?")->execute([$stageId, $leadId]);
            echo json_encode(['success' => true, 'message' => 'Lead deplace']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stages':
        try {
            $stages = $pdo->query("SELECT * FROM pipeline_stages ORDER BY position ASC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $stages]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_stage':
        try {
            $name = trim($input['name'] ?? '');
            if (!$name) { echo json_encode(['success' => false, 'message' => 'Nom requis']); break; }
            $maxPos = (int)$pdo->query("SELECT COALESCE(MAX(position),0) FROM pipeline_stages")->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO pipeline_stages (name, color, position, is_won, is_lost) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $input['color'] ?? '#6366f1', $input['position'] ?? $maxPos + 1, (int)($input['is_won'] ?? 0), (int)($input['is_lost'] ?? 0)]);
            echo json_encode(['success' => true, 'message' => 'Etape creee', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_stage':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $name = trim($input['name'] ?? '');
            if (!$name) { echo json_encode(['success' => false, 'message' => 'Nom requis']); break; }
            $stmt = $pdo->prepare("UPDATE pipeline_stages SET name = ?, color = ?, is_won = ?, is_lost = ? WHERE id = ?");
            $stmt->execute([$name, $input['color'] ?? '#6366f1', (int)($input['is_won'] ?? 0), (int)($input['is_lost'] ?? 0), $id]);
            echo json_encode(['success' => true, 'message' => 'Etape mise a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_stage':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            // Check if leads are in this stage
            $leadsInStage = (int)$pdo->prepare("SELECT COUNT(*) FROM leads WHERE pipeline_stage_id = ?");
            $leadsInStage->execute([$id]);
            $count = (int)$leadsInStage->fetchColumn();
            if ($count > 0) {
                // Move leads to the first available stage
                $firstStage = $pdo->prepare("SELECT id FROM pipeline_stages WHERE id != ? ORDER BY position LIMIT 1");
                $firstStage->execute([$id]);
                $moveToId = $firstStage->fetchColumn();
                if ($moveToId) {
                    $pdo->prepare("UPDATE leads SET pipeline_stage_id = ? WHERE pipeline_stage_id = ?")->execute([$moveToId, $id]);
                }
            }
            $pdo->prepare("DELETE FROM pipeline_stages WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Etape supprimee', 'leads_moved' => $count]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'reorder_stages':
        try {
            $order = $input['order'] ?? [];
            if (is_string($order)) $order = json_decode($order, true) ?? [];
            if (empty($order)) { echo json_encode(['success' => false, 'message' => 'Ordre requis']); break; }
            $stmt = $pdo->prepare("UPDATE pipeline_stages SET position = ? WHERE id = ?");
            foreach ($order as $pos => $stageId) {
                $stmt->execute([$pos + 1, (int)$stageId]);
            }
            echo json_encode(['success' => true, 'message' => 'Ordre mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $totalLeads = (int)$pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
            $totalValue = (float)$pdo->query("SELECT COALESCE(SUM(estimated_value), 0) FROM leads")->fetchColumn();
            echo json_encode(['success' => true, 'data' => ['total_leads' => $totalLeads, 'total_value' => $totalValue]]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_lead':
        try {
            $leadId = (int)($_GET['lead_id'] ?? $input['lead_id'] ?? 0);
            if (!$leadId) { echo json_encode(['success' => false, 'error' => 'lead_id requis']); break; }
            $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
            $stmt->execute([$leadId]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$lead) { echo json_encode(['success' => false, 'error' => 'Lead non trouve']); break; }
            echo json_encode(['success' => true, 'lead' => $lead]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'add_lead':
        try {
            $fields = ['firstname', 'lastname', 'email', 'phone', 'estimated_value', 'source', 'next_action', 'next_action_date', 'notes'];
            $firstname = trim($input['firstname'] ?? '');
            $lastname = trim($input['lastname'] ?? '');
            if (!$firstname || !$lastname) { echo json_encode(['success' => false, 'error' => 'Prenom et nom requis']); break; }
            $firstStage = $pdo->query("SELECT id FROM pipeline_stages ORDER BY position ASC LIMIT 1")->fetchColumn();
            $stmt = $pdo->prepare("INSERT INTO leads (firstname, lastname, email, phone, estimated_value, source, pipeline_stage_id, next_action, next_action_date, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $firstname, $lastname,
                trim($input['email'] ?? ''), trim($input['phone'] ?? ''),
                (float)($input['estimated_value'] ?? 0),
                $input['source'] ?? 'Manuel',
                (int)($input['pipeline_stage_id'] ?? $firstStage ?: 1),
                trim($input['next_action'] ?? ''),
                $input['next_action_date'] ?: null,
                trim($input['notes'] ?? '')
            ]);
            echo json_encode(['success' => true, 'message' => 'Lead ajoute', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'update_lead':
        try {
            $leadId = (int)($input['lead_id'] ?? 0);
            if (!$leadId) { echo json_encode(['success' => false, 'error' => 'lead_id requis']); break; }
            $stmt = $pdo->prepare("UPDATE leads SET firstname=?, lastname=?, email=?, phone=?, estimated_value=?, source=?, pipeline_stage_id=?, next_action=?, next_action_date=?, notes=? WHERE id=?");
            $stmt->execute([
                trim($input['firstname'] ?? ''), trim($input['lastname'] ?? ''),
                trim($input['email'] ?? ''), trim($input['phone'] ?? ''),
                (float)($input['estimated_value'] ?? 0),
                $input['source'] ?? 'Manuel',
                (int)($input['pipeline_stage_id'] ?? 1),
                trim($input['next_action'] ?? ''),
                $input['next_action_date'] ?: null,
                trim($input['notes'] ?? ''),
                $leadId
            ]);
            echo json_encode(['success' => true, 'message' => 'Lead mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'mark_lost':
        try {
            $leadId = (int)($input['lead_id'] ?? 0);
            if (!$leadId) { echo json_encode(['success' => false, 'error' => 'lead_id requis']); break; }
            $lostStage = $pdo->query("SELECT id FROM pipeline_stages WHERE is_lost = 1 LIMIT 1")->fetchColumn();
            if (!$lostStage) { echo json_encode(['success' => false, 'error' => 'Aucune etape "perdu" configuree']); break; }
            $reason = trim($input['reason'] ?? '');
            $stmt = $pdo->prepare("UPDATE leads SET pipeline_stage_id = ?, notes = CONCAT(IFNULL(notes,''), '\nPerdu: ', ?) WHERE id = ?");
            $stmt->execute([$lostStage, $reason, $leadId]);
            echo json_encode(['success' => true, 'message' => 'Lead marque perdu']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'delete_lead':
        try {
            $leadId = (int)($input['lead_id'] ?? 0);
            if (!$leadId) { echo json_encode(['success' => false, 'error' => 'lead_id requis']); break; }
            $pdo->prepare("DELETE FROM leads WHERE id = ?")->execute([$leadId]);
            echo json_encode(['success' => true, 'message' => 'Lead supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
