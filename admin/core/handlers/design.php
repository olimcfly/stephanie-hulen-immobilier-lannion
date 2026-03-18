<?php
/**
 * API Handler: design
 * Called via: /admin/api/router.php?module=design&action=...
 * Tables: headers, footers
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    // --- Headers ---
    case 'list':
    case 'headers':
        try {
            $stmt = $pdo->query("SELECT * FROM headers ORDER BY is_default DESC, name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_header':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM headers WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Header non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_header':
        try {
            $stmt = $pdo->prepare("INSERT INTO headers (name, slug, html_content, css_content, status, is_default) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['name'] ?? '', $input['slug'] ?? '', $input['html_content'] ?? '',
                $input['css_content'] ?? '', $input['status'] ?? 'draft', (int)($input['is_default'] ?? 0)
            ]);
            echo json_encode(['success' => true, 'message' => 'Header cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_header':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['name', 'slug', 'html_content', 'css_content', 'status', 'is_default'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE headers SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Header mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_header':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM headers WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Header supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'set_default_header':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->query("UPDATE headers SET is_default = 0");
            $pdo->prepare("UPDATE headers SET is_default = 1 WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Header par defaut defini']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // --- Footers ---
    case 'footers':
        try {
            $stmt = $pdo->query("SELECT * FROM footers ORDER BY is_default DESC, name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_footer':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM footers WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Footer non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_footer':
        try {
            $stmt = $pdo->prepare("INSERT INTO footers (name, slug, html_content, css_content, status, is_default) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['name'] ?? '', $input['slug'] ?? '', $input['html_content'] ?? '',
                $input['css_content'] ?? '', $input['status'] ?? 'draft', (int)($input['is_default'] ?? 0)
            ]);
            echo json_encode(['success' => true, 'message' => 'Footer cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_footer':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['name', 'slug', 'html_content', 'css_content', 'status', 'is_default'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE footers SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Footer mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_footer':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM footers WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Footer supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'set_default_footer':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->query("UPDATE footers SET is_default = 0");
            $pdo->prepare("UPDATE footers SET is_default = 1 WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Footer par defaut defini']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
