<?php
/**
 * API Handler: blog
 * Called via: /admin/api/router.php?module=blog&action=...
 * Table: blog_articles
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $search = $input['search'] ?? $_GET['search'] ?? '';
            $status = $input['status'] ?? $_GET['status'] ?? '';
            $category = $input['category'] ?? $_GET['category'] ?? '';
            $page = max(1, (int)($input['page'] ?? $_GET['page'] ?? 1));
            $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 20);
            $offset = ($page - 1) * $perPage;

            $where = []; $params = [];
            if ($status) { $where[] = "status = ?"; $params[] = $status; }
            if ($category) { $where[] = "category = ?"; $params[] = $category; }
            if ($search) { $where[] = "(title LIKE ? OR slug LIKE ? OR excerpt LIKE ?)"; $s = "%{$search}%"; $params = array_merge($params, [$s, $s, $s]); }
            $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM blog_articles {$whereSQL}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $params[] = $perPage; $params[] = $offset;
            $stmt = $pdo->prepare("SELECT * FROM blog_articles {$whereSQL} ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute($params);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'data' => $data, 'total' => $total, 'page' => $page, 'per_page' => $perPage]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM blog_articles WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Article non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO blog_articles (title, slug, excerpt, content, category, tags, image, author, main_keyword, seo_title, seo_description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['title'] ?? '', $input['slug'] ?? '', $input['excerpt'] ?? '',
                $input['content'] ?? '', $input['category'] ?? null, $input['tags'] ?? null,
                $input['image'] ?? null, $input['author'] ?? null, $input['main_keyword'] ?? null,
                $input['seo_title'] ?? null, $input['seo_description'] ?? null,
                $input['status'] ?? 'draft'
            ]);
            echo json_encode(['success' => true, 'message' => 'Article cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['title', 'slug', 'excerpt', 'content', 'category', 'tags', 'image', 'author', 'main_keyword', 'seo_title', 'seo_description', 'status'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ a mettre a jour']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE blog_articles SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Article mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'toggle_status':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $stmt = $pdo->prepare("SELECT status FROM blog_articles WHERE id = ?");
            $stmt->execute([$id]);
            $current = $stmt->fetchColumn();
            $newStatus = $current === 'published' ? 'draft' : 'published';
            $pdo->prepare("UPDATE blog_articles SET status = ?, published_at = IF(? = 'published' AND published_at IS NULL, NOW(), published_at) WHERE id = ?")->execute([$newStatus, $newStatus, $id]);
            echo json_encode(['success' => true, 'message' => 'Statut mis a jour', 'new_status' => $newStatus]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $pdo->prepare("DELETE FROM blog_articles WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Article supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
