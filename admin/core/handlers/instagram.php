<?php
/**
 * API Handler: instagram
 * Called via: /admin/api/router.php?module=instagram&action=...
 * Table: social_posts (filtered by platform='instagram')
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $status = $input['status'] ?? $_GET['status'] ?? '';
            $where = "WHERE platform = 'instagram'"; $params = [];
            if ($status) { $where .= ' AND status = ?'; $params[] = $status; }
            $stmt = $pdo->prepare("SELECT * FROM social_posts {$where} ORDER BY scheduled_at DESC, created_at DESC");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM social_posts WHERE id = ? AND platform = 'instagram'");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Post non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO social_posts (platform, post_type, content, media_url, hashtags, scheduled_at, status, persona_id) VALUES ('instagram', ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['post_type'] ?? 'post', $input['content'] ?? '',
                $input['media_url'] ?? null, $input['hashtags'] ?? null,
                $input['scheduled_at'] ?? null, $input['status'] ?? 'draft',
                $input['persona_id'] ?? null
            ]);
            echo json_encode(['success' => true, 'message' => 'Post Instagram cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['post_type', 'content', 'media_url', 'hashtags', 'scheduled_at', 'status', 'persona_id', 'published_at', 'engagement_likes', 'engagement_comments', 'engagement_shares'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE social_posts SET " . implode(', ', $sets) . " WHERE id = ? AND platform = 'instagram'")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Post mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM social_posts WHERE id = ? AND platform = 'instagram'")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Post supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'publish':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("UPDATE social_posts SET status = 'published', published_at = NOW() WHERE id = ? AND platform = 'instagram'")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Post publie']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = [
                'total' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE platform = 'instagram'")->fetchColumn(),
                'published' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE platform = 'instagram' AND status = 'published'")->fetchColumn(),
                'scheduled' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE platform = 'instagram' AND status = 'scheduled'")->fetchColumn(),
                'draft' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE platform = 'instagram' AND status = 'draft'")->fetchColumn(),
                'total_likes' => (int)$pdo->query("SELECT COALESCE(SUM(engagement_likes), 0) FROM social_posts WHERE platform = 'instagram'")->fetchColumn(),
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
