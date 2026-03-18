<?php
/**
 * API Handler: sections
 * Called via: /admin/api/router.php?module=sections&action=...
 * Delegates to SectionController
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

$controllerPath = dirname(__DIR__, 2) . '/modules/content/sections/SectionController.php';
if (file_exists($controllerPath)) {
    require_once $controllerPath;
    $controller = new SectionController($pdo);

    switch ($action) {
        case 'list':
            $pageId = (int)($input['page_id'] ?? $_GET['page_id'] ?? 0);
            if (!$pageId) {
                echo json_encode(['success' => false, 'message' => 'page_id requis']);
                break;
            }
            $result = $controller->getSectionsForPage($pageId);
            echo json_encode($result);
            break;

        case 'create':
            $pageId = (int)($input['page_id'] ?? 0);
            $type = $input['type'] ?? '';
            $data = $input['data'] ?? [];
            $result = $controller->addSection($pageId, $type, $data);
            echo json_encode($result);
            break;

        case 'update':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $data = $input['data'] ?? $input;
            $result = $controller->updateSection($id, $data);
            echo json_encode($result);
            break;

        case 'delete':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $result = $controller->deleteSection($id);
            echo json_encode($result);
            break;

        case 'reorder':
            $orders = $input['orders'] ?? [];
            $result = $controller->reorderSections($orders);
            echo json_encode($result);
            break;

        case 'duplicate':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            try {
                $stmt = $pdo->prepare("SELECT * FROM sections WHERE id = ?");
                $stmt->execute([$id]);
                $original = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$original) {
                    echo json_encode(['success' => false, 'message' => 'Section introuvable']);
                    break;
                }
                $cols = array_keys($original);
                $cols = array_filter($cols, fn($c) => $c !== 'id');
                // Rename duplicated section
                if (isset($original['name'])) {
                    $original['name'] = $original['name'] . ' (copie)';
                }
                $colsList = implode(', ', array_map(fn($c) => "`{$c}`", $cols));
                $placeholders = implode(', ', array_fill(0, count($cols), '?'));
                $values = array_map(fn($c) => $original[$c], $cols);
                $ins = $pdo->prepare("INSERT INTO sections ({$colsList}) VALUES ({$placeholders})");
                $ins->execute($values);
                $newId = $pdo->lastInsertId();
                echo json_encode(['success' => true, 'id' => (int)$newId, 'message' => 'Section dupliquee']);
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => 'Erreur duplication: ' . $e->getMessage()]);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'SectionController introuvable']);
}
