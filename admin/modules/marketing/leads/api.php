<?php
/**
 * API Leads — Point d'entrée AJAX
 * /admin/modules/marketing/leads/api.php
 *
 * Toutes les actions AJAX du module leads passent par ce fichier.
 */

// ── Bootstrap DB ────────────────────────────────────────────────────────────
require_once __DIR__ . '/../../../../config/config.php';
$pdo = getDB();

require_once __DIR__ . '/LeadService.php';

// ── Headers ─────────────────────────────────────────────────────────────────
if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

$service = new LeadService($pdo);
$action  = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        case 'get_lead':
            $id  = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
            $tbl = $_POST['tbl'] ?? $_GET['tbl'] ?? 'leads';
            echo json_encode($service->getLead($id, $tbl));
            break;

        case 'add_lead':
            echo json_encode($service->addLead($_POST));
            break;

        case 'update_lead':
            $id  = (int)($_POST['id'] ?? 0);
            $tbl = $_POST['tbl'] ?? 'leads';
            echo json_encode($service->updateLead($id, $tbl, $_POST));
            break;

        case 'delete_lead':
            $id  = (int)($_POST['id'] ?? 0);
            $tbl = $_POST['tbl'] ?? 'leads';
            echo json_encode($service->deleteLead($id, $tbl));
            break;

        case 'get_interactions':
            $lid = (int)($_POST['lead_id'] ?? $_GET['lead_id'] ?? 0);
            echo json_encode($service->getInteractions($lid));
            break;

        case 'add_interaction':
            echo json_encode($service->addInteraction($_POST));
            break;

        case 'export':
            $service->exportCsv();
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue: '.$action]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
