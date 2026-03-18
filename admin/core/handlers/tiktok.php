<?php
/**
 * API Handler: tiktok
 * Called via: /admin/api/router.php?module=tiktok&action=...
 * Table: tiktok_scripts
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $status = $input['status'] ?? $_GET['status'] ?? '';
            $where = ''; $params = [];
            if ($status) { $where = 'WHERE status = ?'; $params[] = $status; }
            $stmt = $pdo->prepare("SELECT * FROM tiktok_scripts {$where} ORDER BY created_at DESC");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM tiktok_scripts WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Script non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create':
        try {
            $stmt = $pdo->prepare("INSERT INTO tiktok_scripts (title, hook, script, cta, hashtags, sound_suggestion, duration, persona_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['title'] ?? '', $input['hook'] ?? '', $input['script'] ?? '',
                $input['cta'] ?? '', $input['hashtags'] ?? '', $input['sound_suggestion'] ?? '',
                $input['duration'] ?? null, $input['persona_id'] ?? null,
                $input['status'] ?? 'draft'
            ]);
            echo json_encode(['success' => true, 'message' => 'Script TikTok cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['title', 'hook', 'script', 'cta', 'hashtags', 'sound_suggestion', 'duration', 'persona_id', 'status', 'video_url', 'views', 'likes', 'comments', 'shares'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE tiktok_scripts SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Script mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM tiktok_scripts WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Script supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = [
                'total' => (int)$pdo->query("SELECT COUNT(*) FROM tiktok_scripts")->fetchColumn(),
                'published' => (int)$pdo->query("SELECT COUNT(*) FROM tiktok_scripts WHERE status = 'published'")->fetchColumn(),
                'draft' => (int)$pdo->query("SELECT COUNT(*) FROM tiktok_scripts WHERE status = 'draft'")->fetchColumn(),
                'total_views' => (int)$pdo->query("SELECT COALESCE(SUM(views), 0) FROM tiktok_scripts")->fetchColumn(),
                'total_likes' => (int)$pdo->query("SELECT COALESCE(SUM(likes), 0) FROM tiktok_scripts")->fetchColumn(),
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
