<?php
/**
 * API Handler: builder
 * Called via: /admin/api/router.php?module=builder&action=...
 * Delegates to BuilderController
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

$controllerPath = dirname(__DIR__, 2) . '/modules/builder/builder/BuilderController.php';

if (file_exists($controllerPath)) {
    require_once $controllerPath;
    $ctrl = new BuilderController($pdo);

    switch ($action) {
        case 'layouts':
        case 'list':
            $ctx = $input['context'] ?? $_GET['context'] ?? '';
            echo json_encode(['success' => true, 'data' => $ctrl->getLayouts($ctx)]);
            break;

        case 'templates':
            $ctx = $input['context'] ?? $_GET['context'] ?? '';
            echo json_encode(['success' => true, 'data' => $ctrl->getTemplates($ctx)]);
            break;

        case 'block_types':
            $ctx = $input['context'] ?? $_GET['context'] ?? '';
            echo json_encode(['success' => true, 'data' => $ctrl->getBlockTypes($ctx)]);
            break;

        case 'load':
            $ctx      = $input['context']   ?? $_GET['context']   ?? '';
            $entityId = (int)($input['entity_id'] ?? $_GET['entity_id'] ?? 0);
            $content  = $ctrl->loadContent($ctx, $entityId);
            echo json_encode(['success' => (bool)$content, 'data' => $content]);
            break;

        case 'save':
        case 'save_direct':
            // Save is handled by save-content.php; this is the fallback
            $ctx      = $input['context']   ?? '';
            $entityId = (int)($input['entity_id'] ?? 0);
            $status   = $input['status']    ?? 'draft';
            $layoutId = (int)($input['layout_id'] ?? 0);
            $blocks   = $input['blocks_data'] ?? ['html' => $input['html_content'] ?? '', 'css' => $input['custom_css'] ?? '', 'js' => $input['custom_js'] ?? ''];
            $ok = $ctrl->saveContent($ctx, $entityId, $blocks, $layoutId, $status);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Sauvegardé' : 'Erreur sauvegarde']);
            break;

        case 'revisions':
            $ctx      = $input['context']   ?? $_GET['context']   ?? '';
            $entityId = (int)($input['entity_id'] ?? $_GET['entity_id'] ?? 0);
            $limit    = (int)($input['limit'] ?? $_GET['limit'] ?? 20);
            echo json_encode(['success' => true, 'data' => $ctrl->getRevisions($ctx, $entityId, $limit)]);
            break;

        case 'restore':
            $revId = (int)($input['revision_id'] ?? $_GET['revision_id'] ?? $input['id'] ?? 0);
            $data  = $ctrl->restoreRevision($revId);
            echo json_encode(['success' => (bool)$data, 'data' => $data]);
            break;

        case 'saved_blocks':
            $ctx = $input['context'] ?? $_GET['context'] ?? 'global';
            echo json_encode(['success' => true, 'data' => $ctrl->getSavedBlocks($ctx)]);
            break;

        case 'save_block':
            $name      = $input['name']       ?? '';
            $blockType = $input['block_type'] ?? $input['type'] ?? '';
            $blockData = $input['block_data'] ?? $input['data'] ?? [];
            $ctx       = $input['context']    ?? 'global';
            if (!$name) { echo json_encode(['success' => false, 'message' => 'name requis']); break; }
            $ok = $ctrl->saveBlock($name, $blockType, is_array($blockData) ? $blockData : ['html' => $blockData], $ctx);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Bloc sauvegardé' : 'Erreur']);
            break;

        case 'delete_block':
            $blockId = (int)($input['id'] ?? $_GET['id'] ?? 0);
            echo json_encode(['success' => $ctrl->deleteSavedBlock($blockId), 'id' => $blockId]);
            break;

        case 'load_template':
            $tplId = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $tpl   = $ctrl->loadTemplate($tplId);
            echo json_encode(['success' => (bool)$tpl, 'template' => $tpl]);
            break;

        case 'preview':
            // Return entity content for preview
            $ctx      = $input['context']   ?? $_GET['context']   ?? '';
            $entityId = (int)($input['entity_id'] ?? $_GET['entity_id'] ?? 0);
            $content  = $ctrl->loadContent($ctx, $entityId);
            echo json_encode(['success' => true, 'content' => $content]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'BuilderController non trouvé: ' . $controllerPath]);
}
