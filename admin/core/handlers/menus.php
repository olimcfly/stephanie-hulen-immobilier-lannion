<?php
/**
 * API Handler: menus
 * Called via: /admin/api/router.php?module=menus&action=...
 * Tables: menus, menu_items
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $stmt = $pdo->query("SELECT m.*, (SELECT COUNT(*) FROM menu_items WHERE menu_id = m.id) as items_count FROM menus m ORDER BY m.name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM menus WHERE id = ?");
            $stmt->execute([$id]);
            $menu = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($menu) {
                $itemsStmt = $pdo->prepare("SELECT * FROM menu_items WHERE menu_id = ? ORDER BY parent_id ASC, position ASC");
                $itemsStmt->execute([$id]);
                $menu['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            echo json_encode($menu ? ['success' => true, 'data' => $menu] : ['success' => false, 'message' => 'Menu non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO menus (name, slug, location, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $input['name'] ?? '', $input['slug'] ?? '', $input['location'] ?? 'header',
                $input['status'] ?? 'active'
            ]);
            echo json_encode(['success' => true, 'message' => 'Menu cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['name', 'slug', 'location', 'status'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE menus SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Menu mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM menu_items WHERE menu_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM menus WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Menu et items supprimes']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // --- Menu Items ---
    case 'add_item':
        try {
            $stmt = $pdo->prepare("INSERT INTO menu_items (menu_id, parent_id, title, url, target, icon, css_class, position, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                (int)($input['menu_id'] ?? 0), $input['parent_id'] ?? null,
                $input['title'] ?? '', $input['url'] ?? '#', $input['target'] ?? '_self',
                $input['icon'] ?? null, $input['css_class'] ?? null,
                (int)($input['position'] ?? 0), (int)($input['is_active'] ?? 1)
            ]);
            echo json_encode(['success' => true, 'message' => 'Item ajoute', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_item':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['parent_id', 'title', 'url', 'target', 'icon', 'css_class', 'position', 'is_active'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE menu_items SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Item mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_item':
        try {
            $id = (int)($input['id'] ?? 0);
            // Also delete child items
            $pdo->prepare("DELETE FROM menu_items WHERE parent_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Item supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'reorder':
        try {
            $items = $input['items'] ?? [];
            $stmt = $pdo->prepare("UPDATE menu_items SET position = ?, parent_id = ? WHERE id = ?");
            foreach ($items as $pos => $item) {
                $stmt->execute([(int)$pos, $item['parent_id'] ?? null, (int)($item['id'] ?? 0)]);
            }
            echo json_encode(['success' => true, 'message' => 'Ordre mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
