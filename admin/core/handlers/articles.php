<?php
/**
 * API Handler: articles
 * Called via: /admin/api/router.php?module=articles&action=...
 * Table: articles
 * Delegates to ArticleController
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

$controllerPath = dirname(__DIR__, 2) . '/modules/content/articles/ArticleController.php';
if (file_exists($controllerPath)) {
    require_once $controllerPath;
    $controller = new ArticleController();

    switch ($action) {
        case 'list':
            $page = (int)($input['page'] ?? $_GET['page'] ?? 1);
            $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 20);
            $search = $input['search'] ?? $_GET['search'] ?? '';
            $status = $input['status'] ?? $_GET['status'] ?? null;
            $result = $controller->getArticles($page, $perPage, $search, $status);
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'get':
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $article = $controller->getArticleById($id);
            if ($article) {
                echo json_encode(['success' => true, 'data' => $article]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Article non trouve']);
            }
            break;

        case 'create':
            $result = $controller->createArticle($input);
            echo json_encode($result);
            break;

        case 'update':
            $id = (int)($input['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID requis']);
                break;
            }
            $result = $controller->updateArticle($id, $input);
            echo json_encode($result);
            break;

        case 'delete':
            $id = (int)($input['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID requis']);
                break;
            }
            $result = $controller->deleteArticle($id);
            echo json_encode($result);
            break;

        case 'stats':
            $stats = $controller->getStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ArticleController introuvable']);
}
