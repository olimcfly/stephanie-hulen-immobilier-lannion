<?php
/**
 * API Handler: facebook
 * Called via: /admin/api/router.php?module=facebook&action=...
 * Tables: facebook_posts, social_posts (platform='facebook')
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $status = $input['status'] ?? $_GET['status'] ?? '';
            $where = ''; $params = [];
            if ($status) { $where = 'WHERE status = ?'; $params[] = $status; }
            $stmt = $pdo->prepare("SELECT * FROM facebook_posts {$where} ORDER BY created_at DESC");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM facebook_posts WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Post non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO facebook_posts (title, content, post_type, media_url, link_url, scheduled_at, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['title'] ?? '', $input['content'] ?? '', $input['post_type'] ?? 'post',
                $input['media_url'] ?? null, $input['link_url'] ?? null,
                $input['scheduled_at'] ?? null, $input['status'] ?? 'draft'
            ]);
            echo json_encode(['success' => true, 'message' => 'Post Facebook cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['title', 'content', 'post_type', 'media_url', 'link_url', 'scheduled_at', 'status', 'published_at', 'fb_post_id', 'likes', 'comments', 'shares', 'reach'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE facebook_posts SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Post mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM facebook_posts WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Post supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'publish':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("UPDATE facebook_posts SET status = 'published', published_at = NOW() WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Post publie']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = [
                'total' => (int)$pdo->query("SELECT COUNT(*) FROM facebook_posts")->fetchColumn(),
                'published' => (int)$pdo->query("SELECT COUNT(*) FROM facebook_posts WHERE status = 'published'")->fetchColumn(),
                'scheduled' => (int)$pdo->query("SELECT COUNT(*) FROM facebook_posts WHERE status = 'scheduled'")->fetchColumn(),
                'draft' => (int)$pdo->query("SELECT COUNT(*) FROM facebook_posts WHERE status = 'draft'")->fetchColumn(),
                'total_likes' => (int)$pdo->query("SELECT COALESCE(SUM(likes), 0) FROM facebook_posts")->fetchColumn(),
                'total_shares' => (int)$pdo->query("SELECT COALESCE(SUM(shares), 0) FROM facebook_posts")->fetchColumn(),
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
