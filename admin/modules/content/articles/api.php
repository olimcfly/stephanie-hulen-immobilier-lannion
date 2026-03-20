<?php
/**
 * MODULE ADMIN — Articles — API
 * Point d'entree AJAX local, delegue au ArticleController
 */

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

require_once __DIR__ . '/ArticleController.php';
$controller = new ArticleController($pdo);

header('Content-Type: application/json; charset=utf-8');

// --- CSRF pour POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRF invalide']);
        exit;
    }
}

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $_GET['action'] ?? $input['action'] ?? '';
$result = ['success' => false, 'error' => 'Action inconnue'];

try {
    switch ($action) {

        // --- LIST ---
        case 'list':
            $page    = (int)($input['page'] ?? $_GET['page'] ?? 1);
            $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 20);
            $search  = $input['search'] ?? $_GET['search'] ?? '';
            $status  = $input['status'] ?? $_GET['status'] ?? null;
            $data    = $controller->getArticles($page, $perPage, $search, $status);
            $result  = ['success' => true, 'data' => $data];
            break;

        // --- GET ---
        case 'get':
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if (!$id) throw new Exception('ID requis');
            $article = $controller->getArticleById($id);
            if ($article) {
                $result = ['success' => true, 'data' => $article];
            } else {
                http_response_code(404);
                $result = ['success' => false, 'error' => 'Article non trouve'];
            }
            break;

        // --- SAVE (create ou update selon id) ---
        case 'save':
            $id = (int)($input['id'] ?? 0);
            if ($id) {
                $result = $controller->updateArticle($id, $input);
            } else {
                $result = $controller->createArticle($input);
            }
            break;

        // --- CREATE ---
        case 'create':
            $result = $controller->createArticle($input);
            break;

        // --- UPDATE ---
        case 'update':
            $id = (int)($input['id'] ?? 0);
            if (!$id) throw new Exception('ID requis');
            $result = $controller->updateArticle($id, $input);
            break;

        // --- DELETE ---
        case 'delete':
            $id = (int)($input['id'] ?? 0);
            if (!$id) throw new Exception('ID requis');
            $result = $controller->deleteArticle($id);
            break;

        // --- TOGGLE STATUS ---
        case 'toggle_status':
            $id     = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            if (!$id) throw new Exception('ID requis');
            $result = $controller->toggleStatus($id, $status);
            break;

        // --- DUPLICATE ---
        case 'duplicate':
            $id = (int)($input['id'] ?? 0);
            if (!$id) throw new Exception('ID requis');
            $result = $controller->duplicateArticle($id);
            break;

        // --- STATS ---
        case 'stats':
            $stats  = $controller->getStats();
            $result = ['success' => true, 'data' => $stats];
            break;

        default:
            throw new Exception('Action inconnue: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    $result = ['success' => false, 'error' => $e->getMessage()];
    error_log("[Articles API] Error in action '$action': " . $e->getMessage());
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
