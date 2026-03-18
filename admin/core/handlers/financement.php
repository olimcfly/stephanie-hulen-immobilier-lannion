<?php
/**
 * API Handler: financement
 * Called via: /admin/api/router.php?module=financement&action=...
 * Tables: financement_leads, financement_courtiers
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $search = $input['search'] ?? $_GET['search'] ?? '';
            $statut = $input['statut'] ?? $_GET['statut'] ?? '';
            $page = max(1, (int)($input['page'] ?? $_GET['page'] ?? 1));
            $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 20);
            $offset = ($page - 1) * $perPage;

            $where = []; $params = [];
            if ($statut) { $where[] = "fl.statut = ?"; $params[] = $statut; }
            if ($search) { $where[] = "(fl.nom LIKE ? OR fl.prenom LIKE ? OR fl.email LIKE ?)"; $s = "%{$search}%"; $params = array_merge($params, [$s, $s, $s]); }
            $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM financement_leads fl {$whereSQL}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT fl.*, fc.nom as courtier_nom, fc.prenom as courtier_prenom FROM financement_leads fl LEFT JOIN financement_courtiers fc ON fl.courtier_id = fc.id {$whereSQL} ORDER BY fl.created_at DESC LIMIT ? OFFSET ?");
            $params[] = $perPage; $params[] = $offset;
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'leads' => $data, 'data' => $data, 'total' => $total]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT fl.*, fc.nom as courtier_nom FROM financement_leads fl LEFT JOIN financement_courtiers fc ON fl.courtier_id = fc.id WHERE fl.id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Lead non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_lead':
    case 'create':
        try {
            $id = (int)($input['id'] ?? 0);
            if ($id > 0) {
                // Update existing
                $allowed = ['nom','prenom','email','telephone','montant_projet','apport','type_projet','statut','courtier_id','commission_montant','notes'];
                $sets = []; $params = [];
                foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
                if (!empty($sets)) {
                    $params[] = $id;
                    $pdo->prepare("UPDATE financement_leads SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
                }
                echo json_encode(['success' => true, 'message' => 'Lead mis a jour']);
            } else {
                // Create new
                $stmt = $pdo->prepare("INSERT INTO financement_leads (nom, prenom, email, telephone, montant_projet, apport, type_projet, statut, courtier_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $input['nom'] ?? '', $input['prenom'] ?? '', $input['email'] ?? '',
                    $input['telephone'] ?? '', $input['montant_projet'] ?? null,
                    $input['apport'] ?? null, $input['type_projet'] ?? 'achat_residence',
                    $input['statut'] ?? 'nouveau', $input['courtier_id'] ?? null,
                    $input['notes'] ?? null
                ]);
                echo json_encode(['success' => true, 'message' => 'Lead cree', 'id' => $pdo->lastInsertId()]);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['nom', 'prenom', 'email', 'telephone', 'montant_projet', 'apport', 'type_projet', 'statut', 'courtier_id', 'commission_montant', 'notes'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ a mettre a jour']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE financement_leads SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Lead mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_status':
        try {
            $id = (int)($input['id'] ?? 0);
            $statut = $input['statut'] ?? '';
            if (!$id || !$statut) { echo json_encode(['success' => false, 'message' => 'ID et statut requis']); break; }
            $pdo->prepare("UPDATE financement_leads SET statut = ? WHERE id = ?")->execute([$statut, $id]);
            echo json_encode(['success' => true, 'message' => 'Statut mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_lead':
    case 'delete':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $pdo->prepare("DELETE FROM financement_leads WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Lead supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = $pdo->query("SELECT COUNT(*) as total, SUM(statut='nouveau') as nouveaux, SUM(statut='transmis') as transmis, SUM(statut='en_cours') as en_cours, SUM(statut='finance') as finances, SUM(CASE WHEN statut IN ('finance','commission_percue') THEN commission_montant ELSE 0 END) as total_commissions FROM financement_leads")->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'list_courtiers':
    case 'courtiers_list':
        try {
            $data = $pdo->query("SELECT * FROM financement_courtiers ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'courtiers' => $data, 'data' => $data]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_courtier':
        try {
            $id = (int)($input['id'] ?? 0);
            if ($id > 0) {
                $allowed = ['nom','prenom','email','telephone','organisme','taux_commission','notes'];
                $sets = []; $params = [];
                foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
                if (!empty($sets)) {
                    $params[] = $id;
                    $pdo->prepare("UPDATE financement_courtiers SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
                }
                echo json_encode(['success' => true, 'message' => 'Courtier mis a jour']);
            } else {
                $stmt = $pdo->prepare("INSERT INTO financement_courtiers (nom, prenom, email, telephone, organisme, taux_commission, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $input['nom'] ?? '', $input['prenom'] ?? '', $input['email'] ?? '',
                    $input['telephone'] ?? '', $input['organisme'] ?? '',
                    $input['taux_commission'] ?? null, $input['notes'] ?? null
                ]);
                echo json_encode(['success' => true, 'message' => 'Courtier cree', 'id' => $pdo->lastInsertId()]);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_courtier':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $pdo->prepare("DELETE FROM financement_courtiers WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Courtier supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
