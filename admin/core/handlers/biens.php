<?php
/**
 * API Handler: biens
 * Called via: /admin/api/router.php?module=biens&action=...
 * Table: properties
 * Delegates to PropertyController
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

$controllerPath = dirname(__DIR__, 2) . '/modules/immobilier/properties/PropertyController.php';
if (file_exists($controllerPath)) {
    require_once $controllerPath;
    $controller = new PropertyController($pdo);

    switch ($action) {
        case 'list':
            $filters = array_merge($_GET, $input);
            $result = $controller->getAll($filters);
            echo json_encode(['success' => true, 'data' => $result['data'], 'total' => $result['total'], 'page' => $result['page'], 'per_page' => $result['per_page'], 'total_pages' => $result['total_pages']]);
            break;

        case 'get':
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $property = $controller->getById($id);
            echo json_encode($property ? ['success' => true, 'data' => $property] : ['success' => false, 'message' => 'Bien non trouve']);
            break;

        case 'create':
            $result = $controller->create($input);
            echo json_encode($result);
            break;

        case 'update':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $result = $controller->update($id, $input);
            echo json_encode($result);
            break;

        case 'delete':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $deleted = $controller->delete($id);
            echo json_encode($deleted ? ['success' => true, 'message' => 'Bien supprime'] : ['success' => false, 'message' => 'Bien non trouve']);
            break;

        case 'update_status':
            $id = (int)($input['id'] ?? 0);
            $status = $input['status'] ?? '';
            $result = $controller->updateStatus($id, $status);
            echo json_encode(['success' => $result, 'message' => $result ? 'Statut mis a jour' : 'Erreur']);
            break;

        case 'toggle_featured':
            $id = (int)($input['id'] ?? 0);
            $result = $controller->toggleFeatured($id);
            echo json_encode(['success' => $result, 'message' => $result ? 'Mise en avant modifiee' : 'Erreur']);
            break;

        case 'stats':
            $stats = $controller->getStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;

        case 'cities':
            $cities = $controller->getCities();
            echo json_encode(['success' => true, 'data' => $cities]);
            break;

        case 'neighborhoods':
            $city = $input['city'] ?? $_GET['city'] ?? null;
            $neighborhoods = $controller->getNeighborhoods($city);
            echo json_encode(['success' => true, 'data' => $neighborhoods]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'PropertyController introuvable']);
}
