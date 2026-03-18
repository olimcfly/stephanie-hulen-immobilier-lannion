<?php
/**
 * API Handler: neuropersona
 * Called via: /admin/api/router.php?module=neuropersona&action=...
 * Table: neuropersona_types
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $category = $input['category'] ?? $_GET['category'] ?? '';
            $where = ''; $params = [];
            if ($category) { $where = 'WHERE categorie = ?'; $params[] = $category; }
            $stmt = $pdo->prepare("SELECT * FROM neuropersona_types {$where} ORDER BY categorie, nom");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM neuropersona_types WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Neuropersona non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO neuropersona_types (nom, code, categorie, description, traits, motivations, objections, tone_of_voice, content_preferences, color, icon, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['nom'] ?? '', $input['code'] ?? '', $input['categorie'] ?? '',
                $input['description'] ?? '', $input['traits'] ?? null, $input['motivations'] ?? null,
                $input['objections'] ?? null, $input['tone_of_voice'] ?? null,
                $input['content_preferences'] ?? null, $input['color'] ?? '#6366f1',
                $input['icon'] ?? null, $input['status'] ?? 'active'
            ]);
            echo json_encode(['success' => true, 'message' => 'Neuropersona cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['nom', 'code', 'categorie', 'description', 'traits', 'motivations', 'objections', 'tone_of_voice', 'content_preferences', 'color', 'icon', 'status'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE neuropersona_types SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Neuropersona mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM neuropersona_types WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Neuropersona supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle':
        try {
            $id = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT status FROM neuropersona_types WHERE id = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetchColumn();
            $newStatus = ($current === 'active') ? 'inactive' : 'active';
            $pdo->prepare("UPDATE neuropersona_types SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
            echo json_encode(['success' => true, 'message' => 'Statut mis a jour', 'status' => $newStatus]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'categories':
        try {
            $stmt = $pdo->query("SELECT DISTINCT categorie FROM neuropersona_types ORDER BY categorie");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = [
                'total' => (int)$pdo->query("SELECT COUNT(*) FROM neuropersona_types")->fetchColumn(),
                'active' => (int)$pdo->query("SELECT COUNT(*) FROM neuropersona_types WHERE status = 'active'")->fetchColumn(),
                'by_category' => [],
            ];
            $catStmt = $pdo->query("SELECT categorie, COUNT(*) as count FROM neuropersona_types GROUP BY categorie ORDER BY categorie");
            foreach ($catStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $stats['by_category'][$row['categorie']] = (int)$row['count'];
            }
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
