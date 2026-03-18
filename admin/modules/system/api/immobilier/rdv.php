<?php
/**
 * API — RDV : Actions AJAX
 * admin/api/immobilier/rdv.php
 *
 * Ancienne position : modules/immobilier/rdv/api-rdv.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

define('ADMIN_ROUTER', true);
require_once dirname(__DIR__, 3) . '/config/config.php';
if (!class_exists('Database')) {
    require_once dirname(__DIR__, 3) . '/includes/classes/Database.php';
}

header('Content-Type: application/json');

try {
    $pdo    = Database::getInstance();
    $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? $_GET['action'] ?? '';

    switch ($action) {

        case 'list':
            $statut = $input['statut'] ?? '';
            $sql    = "SELECT r.*, l.nom, l.prenom, l.email, l.telephone
                       FROM appointments r
                       LEFT JOIN leads l ON l.id = r.lead_id";
            $params = [];
            if ($statut) { $sql .= " WHERE r.statut=?"; $params[] = $statut; }
            $sql .= " ORDER BY r.date_rdv DESC LIMIT 100";
            $rdvs = $pdo->prepare($sql);
            $rdvs->execute($params);
            echo json_encode(['success' => true, 'data' => $rdvs->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'save':
            $id        = (int)($input['id'] ?? 0);
            $lead_id   = (int)($input['lead_id'] ?? 0);
            $date_rdv  = $input['date_rdv'] ?? '';
            $type      = $input['type']     ?? 'visite';
            $notes     = $input['notes']    ?? '';
            $statut    = in_array($input['statut'] ?? '', ['planifié','confirmé','annulé','réalisé'])
                         ? $input['statut'] : 'planifié';

            if (!$date_rdv) {
                echo json_encode(['success' => false, 'error' => 'Date manquante']);
                break;
            }

            if ($id) {
                $pdo->prepare("UPDATE appointments SET lead_id=?,date_rdv=?,type=?,notes=?,statut=?,updated_at=NOW() WHERE id=?")
                    ->execute([$lead_id, $date_rdv, $type, $notes, $statut, $id]);
            } else {
                $pdo->prepare("INSERT INTO appointments (lead_id,date_rdv,type,notes,statut,created_at) VALUES (?,?,?,?,?,NOW())")
                    ->execute([$lead_id, $date_rdv, $type, $notes, $statut]);
                $id = $pdo->lastInsertId();
            }
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'delete':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID manquant']); break; }
            $pdo->prepare("DELETE FROM appointments WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'update_statut':
            $id     = (int)($input['id'] ?? 0);
            $statut = $input['statut'] ?? '';
            $allowed = ['planifié','confirmé','annulé','réalisé'];
            if (!$id || !in_array($statut, $allowed)) {
                echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
                break;
            }
            $pdo->prepare("UPDATE appointments SET statut=?,updated_at=NOW() WHERE id=?")->execute([$statut, $id]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }

} catch (Exception $e) {
    error_log('[API rdv] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}