<?php
/**
 * API Handler: social
 * Called via: /admin/api/router.php?module=social&action=...
 * Table: social_posts (cross-platform)
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $platform = $input['platform'] ?? $_GET['platform'] ?? '';
            $status = $input['status'] ?? $_GET['status'] ?? '';
            $page = max(1, (int)($input['page'] ?? $_GET['page'] ?? 1));
            $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 25);
            $offset = ($page - 1) * $perPage;

            $where = 'WHERE 1=1'; $params = [];
            if ($platform) { $where .= ' AND platform = ?'; $params[] = $platform; }
            if ($status) { $where .= ' AND status = ?'; $params[] = $status; }

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM social_posts {$where}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $params[] = $perPage; $params[] = $offset;
            $stmt = $pdo->prepare("SELECT * FROM social_posts {$where} ORDER BY scheduled_at DESC, created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM social_posts WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Post non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO social_posts (platform, post_type, content, media_url, hashtags, scheduled_at, status, persona_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['platform'] ?? '', $input['post_type'] ?? 'post', $input['content'] ?? '',
                $input['media_url'] ?? null, $input['hashtags'] ?? null,
                $input['scheduled_at'] ?? null, $input['status'] ?? 'draft',
                $input['persona_id'] ?? null
            ]);
            echo json_encode(['success' => true, 'message' => 'Post cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['platform', 'post_type', 'content', 'media_url', 'hashtags', 'scheduled_at', 'status', 'persona_id', 'published_at', 'engagement_likes', 'engagement_comments', 'engagement_shares'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE social_posts SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Post mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM social_posts WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Post supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'publish':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("UPDATE social_posts SET status = 'published', published_at = NOW() WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Post publie']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'schedule':
        try {
            $id = (int)($input['id'] ?? 0);
            $scheduledAt = $input['scheduled_at'] ?? '';
            if (!$scheduledAt) { echo json_encode(['success' => false, 'message' => 'Date de planification requise']); break; }
            $pdo->prepare("UPDATE social_posts SET status = 'scheduled', scheduled_at = ? WHERE id = ?")->execute([$scheduledAt, $id]);
            echo json_encode(['success' => true, 'message' => 'Post planifie']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'calendar':
        try {
            $month = (int)($input['month'] ?? $_GET['month'] ?? date('m'));
            $year = (int)($input['year'] ?? $_GET['year'] ?? date('Y'));
            $stmt = $pdo->prepare("SELECT * FROM social_posts WHERE (MONTH(scheduled_at) = ? AND YEAR(scheduled_at) = ?) OR (MONTH(published_at) = ? AND YEAR(published_at) = ?) ORDER BY COALESCE(scheduled_at, published_at) ASC");
            $stmt->execute([$month, $year, $month, $year]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = [
                'total' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts")->fetchColumn(),
                'published' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE status = 'published'")->fetchColumn(),
                'scheduled' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE status = 'scheduled'")->fetchColumn(),
                'draft' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE status = 'draft'")->fetchColumn(),
                'by_platform' => [],
            ];
            $platformStmt = $pdo->query("SELECT platform, COUNT(*) as count FROM social_posts GROUP BY platform");
            foreach ($platformStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $stats['by_platform'][$row['platform']] = (int)$row['count'];
            }
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
