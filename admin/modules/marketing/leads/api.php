<?php
/**
 * API Router: Leads module
 * Thin routing layer that delegates to LeadService.
 *
 * Called via AJAX from the leads front-end.
 * Expects $pdo to be available (from bootstrap or parent include).
 */

require_once __DIR__ . '/LeadService.php';

// ── Bootstrap PDO if not already available ──────────────────────────────────
if (!isset($pdo)) {
    if (isset($db)) {
        $pdo = $db;
    } else {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
            exit;
        }
    }
}

$service = new LeadService($pdo);

// ── Resolve input & action ──────────────────────────────────────────────────
$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? (defined('CURRENT_ACTION') ? CURRENT_ACTION : '');
$id     = (int)($input['id'] ?? $_POST['id'] ?? $_GET['id'] ?? 0);
$table  = preg_replace('/[^a-z_]/', '', $input['tbl'] ?? $_POST['tbl'] ?? $_GET['tbl'] ?? 'leads');

// ── JSON response ───────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');

switch ($action) {

    // ── List (single-table, paginated) ──────────────────────────────────────
    case 'list':
        echo json_encode($service->list(array_merge($_GET, $input)));
        break;

    // ── Get single lead ─────────────────────────────────────────────────────
    case 'get':
    case 'get_lead':
        echo json_encode($service->get($id, $table));
        break;

    // ── Create or Update (save) ─────────────────────────────────────────────
    case 'save':
        echo json_encode($id
            ? $service->update($id, $input, $table)
            : $service->create($input)
        );
        break;

    case 'add_lead':
    case 'create':
        echo json_encode($service->create($input));
        break;

    case 'update_lead':
    case 'update':
        echo json_encode($service->update($id, $input, $table));
        break;

    // ── Delete ──────────────────────────────────────────────────────────────
    case 'delete':
    case 'delete_lead':
        echo json_encode($service->delete($id, $table));
        break;

    // ── Bulk operations ─────────────────────────────────────────────────────
    case 'bulk_delete':
        echo json_encode($service->bulkDelete($input['ids'] ?? []));
        break;

    case 'bulk_status':
    case 'bulk_update_status':
        echo json_encode($service->bulkUpdateStatus($input['ids'] ?? [], $input['status'] ?? ''));
        break;

    // ── Status & temperature shortcuts ──────────────────────────────────────
    case 'update_status':
        echo json_encode($service->updateStatus($id, $input['status'] ?? ''));
        break;

    case 'update_temperature':
        echo json_encode($service->updateTemperature($id, $input['temperature'] ?? ''));
        break;

    // ── Interactions / Notes ────────────────────────────────────────────────
    case 'get_interactions':
    case 'get_activity':
        $leadId = (int)($input['lead_id'] ?? $_POST['lead_id'] ?? $id);
        echo json_encode($service->getActivity($leadId));
        break;

    case 'add_interaction':
    case 'add_note':
        $leadId = (int)($input['lead_id'] ?? $_POST['lead_id'] ?? $id);
        echo json_encode($service->addNote($leadId, $input));
        break;

    // ── Stats ───────────────────────────────────────────────────────────────
    case 'stats':
        echo json_encode($service->stats());
        break;

    // ── Export (CSV download) ───────────────────────────────────────────────
    case 'export':
        $result = $service->export($input);
        if (!$result['success']) {
            echo json_encode($result);
            break;
        }
        // Override content-type for CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="leads_export_' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
        fputcsv($out, $result['headers'], ';');
        foreach ($result['rows'] as $row) {
            fputcsv($out, $row, ';');
        }
        fclose($out);
        exit;

    // ── Unified multi-source listing ────────────────────────────────────────
    case 'list_all':
        $search    = $input['search'] ?? $_GET['search'] ?? '';
        $srcFilter = $input['src'] ?? $_GET['src'] ?? '';
        $statusFlt = $input['status'] ?? $_GET['status'] ?? '';
        $sort      = $input['sort'] ?? $_GET['sort'] ?? 'created_at';
        $order     = $input['order'] ?? $_GET['order'] ?? 'DESC';
        $page      = max(1, (int)($input['page'] ?? $_GET['page'] ?? 1));
        $perPage   = (int)($input['per_page'] ?? $_GET['per_page'] ?? 25);
        $offset    = ($page - 1) * $perPage;

        $result = $service->listAll($search, $srcFilter, $sort, $order, $statusFlt, $offset, $perPage);
        echo json_encode(['success' => true, 'data' => $result['rows'], 'total' => $result['total'], 'page' => $page]);
        break;

    // ── Unknown action ──────────────────────────────────────────────────────
    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportée"]);
}
