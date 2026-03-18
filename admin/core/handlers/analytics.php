<?php
/**
 * API Handler: analytics
 * Called via: /admin/api/router.php?module=analytics&action=...
 * Tables: page_views, conversion_events
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'overview':
    case 'list':
        try {
            $period = $input['period'] ?? $_GET['period'] ?? '30';
            $days = max(1, (int)$period);
            $stats = [
                'total_views' => (int)$pdo->prepare("SELECT COUNT(*) FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)")->execute([$days]) ? $pdo->query("SELECT COUNT(*) FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")->fetchColumn() : 0,
                'unique_sessions' => (int)$pdo->query("SELECT COUNT(DISTINCT session_id) FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")->fetchColumn(),
                'total_conversions' => (int)$pdo->query("SELECT COUNT(*) FROM conversion_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")->fetchColumn(),
                'conversion_value' => (float)$pdo->query("SELECT COALESCE(SUM(value), 0) FROM conversion_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)")->fetchColumn(),
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'page_views':
        try {
            $days = max(1, (int)($input['days'] ?? $_GET['days'] ?? 30));
            $limit = max(1, (int)($input['limit'] ?? $_GET['limit'] ?? 20));
            $stmt = $pdo->prepare("SELECT page_url, page_title, COUNT(*) as views, COUNT(DISTINCT session_id) as unique_views FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY page_url, page_title ORDER BY views DESC LIMIT ?");
            $stmt->execute([$days, $limit]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'traffic_sources':
        try {
            $days = max(1, (int)($input['days'] ?? $_GET['days'] ?? 30));
            $stmt = $pdo->prepare("SELECT source, medium, COUNT(*) as visits FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY source, medium ORDER BY visits DESC");
            $stmt->execute([$days]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'devices':
        try {
            $days = max(1, (int)($input['days'] ?? $_GET['days'] ?? 30));
            $stmt = $pdo->prepare("SELECT device, COUNT(*) as count FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY device ORDER BY count DESC");
            $stmt->execute([$days]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'conversions':
        try {
            $days = max(1, (int)($input['days'] ?? $_GET['days'] ?? 30));
            $stmt = $pdo->prepare("SELECT event_type, event_label, COUNT(*) as count, COALESCE(SUM(value), 0) as total_value FROM conversion_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY event_type, event_label ORDER BY count DESC");
            $stmt->execute([$days]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'timeline':
        try {
            $days = max(1, (int)($input['days'] ?? $_GET['days'] ?? 30));
            $stmt = $pdo->prepare("SELECT DATE(viewed_at) as date, COUNT(*) as views, COUNT(DISTINCT session_id) as sessions FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY DATE(viewed_at) ORDER BY date ASC");
            $stmt->execute([$days]);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'track_view':
        try {
            $stmt = $pdo->prepare("INSERT INTO page_views (page_url, page_title, referrer, source, medium, device, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['page_url'] ?? '', $input['page_title'] ?? '', $input['referrer'] ?? '',
                $input['source'] ?? 'direct', $input['medium'] ?? '', $input['device'] ?? 'desktop',
                $input['session_id'] ?? '', $input['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? '',
                $input['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'track_conversion':
        try {
            $stmt = $pdo->prepare("INSERT INTO conversion_events (event_type, event_label, page_url, value, session_id, lead_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['event_type'] ?? '', $input['event_label'] ?? '', $input['page_url'] ?? '',
                (float)($input['value'] ?? 0), $input['session_id'] ?? '', $input['lead_id'] ?? null
            ]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
