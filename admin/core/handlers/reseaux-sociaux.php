<?php
/**
 * API Handler: reseaux-sociaux
 * Called via: /admin/api/router.php?module=reseaux-sociaux&action=...
 * Aggregated social media overview across all platforms
 * Tables: social_posts, facebook_posts, tiktok_scripts
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'overview':
    case 'list':
        try {
            $stats = [
                'social_posts' => [
                    'total' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts")->fetchColumn(),
                    'published' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE status = 'published'")->fetchColumn(),
                    'scheduled' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE status = 'scheduled'")->fetchColumn(),
                    'draft' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE status = 'draft'")->fetchColumn(),
                ],
                'facebook' => [
                    'total' => (int)$pdo->query("SELECT COUNT(*) FROM facebook_posts")->fetchColumn(),
                    'published' => (int)$pdo->query("SELECT COUNT(*) FROM facebook_posts WHERE status = 'published'")->fetchColumn(),
                ],
                'instagram' => [
                    'total' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE platform = 'instagram'")->fetchColumn(),
                    'published' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE platform = 'instagram' AND status = 'published'")->fetchColumn(),
                ],
                'linkedin' => [
                    'total' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE platform = 'linkedin'")->fetchColumn(),
                    'published' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE platform = 'linkedin' AND status = 'published'")->fetchColumn(),
                ],
                'tiktok' => [
                    'total' => (int)$pdo->query("SELECT COUNT(*) FROM tiktok_scripts")->fetchColumn(),
                    'published' => (int)$pdo->query("SELECT COUNT(*) FROM tiktok_scripts WHERE status = 'published'")->fetchColumn(),
                ],
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'calendar':
        try {
            $month = (int)($input['month'] ?? $_GET['month'] ?? date('m'));
            $year = (int)($input['year'] ?? $_GET['year'] ?? date('Y'));

            $results = [];

            // Social posts
            $stmt = $pdo->prepare("SELECT id, platform, content, status, scheduled_at, published_at, 'social' as source FROM social_posts WHERE (MONTH(COALESCE(scheduled_at, published_at, created_at)) = ? AND YEAR(COALESCE(scheduled_at, published_at, created_at)) = ?)");
            $stmt->execute([$month, $year]);
            $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

            // Facebook posts
            $stmt = $pdo->prepare("SELECT id, 'facebook' as platform, content, status, scheduled_at, published_at, 'facebook' as source FROM facebook_posts WHERE (MONTH(COALESCE(scheduled_at, created_at)) = ? AND YEAR(COALESCE(scheduled_at, created_at)) = ?)");
            $stmt->execute([$month, $year]);
            $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

            // TikTok scripts
            $stmt = $pdo->prepare("SELECT id, 'tiktok' as platform, title as content, status, NULL as scheduled_at, NULL as published_at, 'tiktok' as source FROM tiktok_scripts WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?");
            $stmt->execute([$month, $year]);
            $results = array_merge($results, $stmt->fetchAll(PDO::FETCH_ASSOC));

            echo json_encode(['success' => true, 'data' => $results]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'recent':
        try {
            $limit = max(1, (int)($input['limit'] ?? $_GET['limit'] ?? 10));
            $stmt = $pdo->prepare("SELECT id, platform, content, status, published_at, created_at FROM social_posts ORDER BY created_at DESC LIMIT ?");
            $stmt->execute([$limit]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $days = max(1, (int)($input['days'] ?? $_GET['days'] ?? 30));
            $stats = [
                'posts_this_period' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")->fetchColumn(),
                'published_this_period' => (int)$pdo->query("SELECT COUNT(*) FROM social_posts WHERE status = 'published' AND published_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")->fetchColumn(),
                'total_engagement' => (int)$pdo->query("SELECT COALESCE(SUM(engagement_likes + engagement_comments + engagement_shares), 0) FROM social_posts WHERE published_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")->fetchColumn(),
                'by_platform' => [],
            ];
            $platformStmt = $pdo->query("SELECT platform, COUNT(*) as count, SUM(CASE WHEN status='published' THEN 1 ELSE 0 END) as published FROM social_posts GROUP BY platform");
            foreach ($platformStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $stats['by_platform'][$row['platform']] = ['total' => (int)$row['count'], 'published' => (int)$row['published']];
            }
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
