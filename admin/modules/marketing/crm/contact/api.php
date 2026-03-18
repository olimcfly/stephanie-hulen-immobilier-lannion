<?php
/**
 * ══════════════════════════════════════════════════════════════
 * CRM CONTACTS API  v1.0
 * /admin/modules/crm/contacts/api.php
 * Actions : delete, bulk_delete, bulk_status, export
 * Réponses : JSON {success, message, data} ou CSV
 * ══════════════════════════════════════════════════════════════
 */

if (!defined('ADMIN_ROUTER')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;

// ─── Export CSV (GET) ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'export') {
    $ids = array_filter(array_map('intval', explode(',', $_GET['ids'] ?? '')));
    exportContacts($pdo, $ids);
    exit;
}

// ─── API JSON (POST) ────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']); exit;
}

$action = trim($_POST['action'] ?? '');

try {
    switch ($action) {

        // ── Supprimer un contact ──────────────────────────
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'ID invalide']); exit; }

            $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) { echo json_encode(['success'=>false,'error'=>'Contact introuvable']); exit; }

            echo json_encode(['success'=>true,'message'=>'Contact supprimé']);
            break;

        // ── Suppression groupée ───────────────────────────
        case 'bulk_delete':
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            if (!is_array($ids) || empty($ids)) { echo json_encode(['success'=>false,'error'=>'Aucun ID fourni']); exit; }
            $ids = array_filter(array_map('intval', $ids));
            if (empty($ids)) { echo json_encode(['success'=>false,'error'=>'IDs invalides']); exit; }

            $ph   = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM contacts WHERE id IN ({$ph})");
            $stmt->execute(array_values($ids));

            echo json_encode(['success'=>true,'message'=>$stmt->rowCount().' contact(s) supprimé(s)','deleted'=>$stmt->rowCount()]);
            break;

        // ── Changement de statut groupé ───────────────────
        case 'bulk_status':
            $ids    = json_decode($_POST['ids'] ?? '[]', true);
            $status = trim($_POST['status'] ?? '');
            $allowed = ['active','inactive','vip','blacklist'];

            if (!is_array($ids) || empty($ids)) { echo json_encode(['success'=>false,'error'=>'Aucun ID fourni']); exit; }
            if (!in_array($status, $allowed))   { echo json_encode(['success'=>false,'error'=>'Statut invalide']); exit; }
            $ids = array_filter(array_map('intval', $ids));
            if (empty($ids)) { echo json_encode(['success'=>false,'error'=>'IDs invalides']); exit; }

            $ph   = implode(',', array_fill(0, count($ids), '?'));
            $params = array_merge([$status], array_values($ids));
            $stmt = $pdo->prepare("UPDATE contacts SET status = ?, updated_at = NOW() WHERE id IN ({$ph})");
            $stmt->execute($params);

            echo json_encode(['success'=>true,'message'=>$stmt->rowCount().' contact(s) mis à jour','updated'=>$stmt->rowCount()]);
            break;

        // ── Toggle status individuel ───────────────────────
        case 'toggle_status':
            $id     = (int)($_POST['id'] ?? 0);
            $status = trim($_POST['status'] ?? '');
            $allowed = ['active','inactive','vip','blacklist'];
            if ($id <= 0)                      { echo json_encode(['success'=>false,'error'=>'ID invalide']); exit; }
            if (!in_array($status, $allowed))  { echo json_encode(['success'=>false,'error'=>'Statut invalide']); exit; }

            $stmt = $pdo->prepare("UPDATE contacts SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);

            echo json_encode(['success'=>true,'message'=>'Statut mis à jour','status'=>$status]);
            break;

        // ── Mettre à jour la note (rating) ─────────────────
        case 'update_rating':
            $id     = (int)($_POST['id'] ?? 0);
            $rating = max(0, min(5, (int)($_POST['rating'] ?? 0)));
            if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'ID invalide']); exit; }

            $stmt = $pdo->prepare("UPDATE contacts SET rating = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$rating, $id]);

            echo json_encode(['success'=>true,'message'=>'Note mise à jour','rating'=>$rating]);
            break;

        // ── Mettre à jour last_contact ─────────────────────
        case 'update_last_contact':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'ID invalide']); exit; }

            $stmt = $pdo->prepare("UPDATE contacts SET last_contact = CURDATE(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['success'=>true,'message'=>'Dernier contact mis à jour']);
            break;

        // ── Export CSV (POST alternative) ──────────────────
        case 'export':
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            $ids = is_array($ids) ? array_filter(array_map('intval', $ids)) : [];
            exportContacts($pdo, $ids);
            exit;

        default:
            echo json_encode(['success'=>false,'error'=>'Action inconnue : '.$action]);
    }
} catch (PDOException $e) {
    error_log('[CRM Contacts API] ' . $e->getMessage());
    echo json_encode(['success'=>false,'error'=>'Erreur base de données']);
} catch (Exception $e) {
    error_log('[CRM Contacts API] ' . $e->getMessage());
    echo json_encode(['success'=>false,'error'=>'Erreur serveur']);
}

// ─── Fonction export CSV ────────────────────────────────────
function exportContacts(PDO $pdo, array $ids = []): void {
    $filename = 'contacts_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

    // En-têtes CSV
    fputcsv($out, [
        'ID','Nom','Prénom','Email','Téléphone','Mobile',
        'Civilité','Catégorie','Statut','Source',
        'Entreprise','Poste','Ville','Code postal','Pays',
        'Note (étoiles)','Tags','Dernier contact','Prochaine relance',
        'Site web','LinkedIn','Facebook','Instagram',
        'Notes','Date création','Date mise à jour'
    ], ';');

    try {
        $where = '';
        $params = [];
        if (!empty($ids)) {
            $ph    = implode(',', array_fill(0, count($ids), '?'));
            $where = "WHERE id IN ({$ph})";
            $params = array_values($ids);
        }
        $stmt = $pdo->prepare("SELECT * FROM contacts {$where} ORDER BY created_at DESC");
        $stmt->execute($params);

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [
                $row['id']           ?? '',
                $row['nom']          ?? '',
                $row['prenom']       ?? ($row['firstname'] ?? ''),
                $row['email']        ?? '',
                $row['telephone']    ?? ($row['phone'] ?? ''),
                $row['mobile']       ?? '',
                $row['civility']     ?? '',
                $row['category']     ?? '',
                $row['status']       ?? '',
                $row['source']       ?? '',
                $row['company']      ?? '',
                $row['job_title']    ?? '',
                $row['city']         ?? '',
                $row['postal_code']  ?? '',
                $row['country']      ?? '',
                $row['rating']       ?? '0',
                $row['tags']         ?? '',
                $row['last_contact'] ?? '',
                $row['next_followup']?? '',
                $row['website']      ?? '',
                $row['linkedin']     ?? '',
                $row['facebook']     ?? '',
                $row['instagram']    ?? '',
                $row['notes']        ?? '',
                $row['created_at']   ?? '',
                $row['updated_at']   ?? '',
            ], ';');
        }
    } catch (PDOException $e) {
        error_log('[CRM Contacts Export] ' . $e->getMessage());
    }

    fclose($out);
}