<?php
/**
 * API Handler: ressources
 * Called via: /admin/api/router.php?module=ressources&action=...
 * Table: guides
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $category = $input['category'] ?? $_GET['category'] ?? '';
            $persona = $input['persona'] ?? $_GET['persona'] ?? '';
            $where = ''; $params = [];
            $conditions = [];
            if ($category) { $conditions[] = 'categorie = ?'; $params[] = $category; }
            if ($persona) { $conditions[] = 'persona = ?'; $params[] = $persona; }
            if ($conditions) $where = 'WHERE ' . implode(' AND ', $conditions);
            $stmt = $pdo->prepare("SELECT * FROM guides {$where} ORDER BY created_at DESC");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM guides WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Guide non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO guides (titre, slug, description, persona, categorie, fichier_pdf, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['titre'] ?? '', $input['slug'] ?? '', $input['description'] ?? '',
                $input['persona'] ?? '', $input['categorie'] ?? '', $input['fichier_pdf'] ?? '',
                $input['image'] ?? '', $input['status'] ?? 'draft'
            ]);
            echo json_encode(['success' => true, 'message' => 'Guide cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['titre', 'slug', 'description', 'persona', 'categorie', 'fichier_pdf', 'image', 'status'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE guides SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Guide mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM guides WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Guide supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'track_download':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("UPDATE guides SET downloads_count = downloads_count + 1 WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Telechargement enregistre']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'categories':
        try {
            $stmt = $pdo->query("SELECT DISTINCT categorie FROM guides WHERE categorie IS NOT NULL AND categorie != '' ORDER BY categorie");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_COLUMN)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = [
                'total' => (int)$pdo->query("SELECT COUNT(*) FROM guides")->fetchColumn(),
                'published' => (int)$pdo->query("SELECT COUNT(*) FROM guides WHERE status = 'published'")->fetchColumn(),
                'total_downloads' => (int)$pdo->query("SELECT COALESCE(SUM(downloads_count), 0) FROM guides")->fetchColumn(),
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
