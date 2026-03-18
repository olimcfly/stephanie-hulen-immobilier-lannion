<?php
/**
 * ══════════════════════════════════════════════════════════════
 * COURTIERS API v1.0
 * /admin/modules/courtiers/api.php
 * Actions : create, update, delete, bulk_delete, bulk_status, toggle_status
 * ══════════════════════════════════════════════════════════════
 */
header('Content-Type: application/json; charset=utf-8');

// ─── Bootstrap ───
if (!defined('ADMIN_ROUTER')) {
    $root = dirname(dirname(dirname(dirname(__DIR__))));
    if (!defined('DB_HOST') && file_exists($root . '/config/config.php')) {
        require_once $root . '/config/config.php';
    }
}
if (session_status() === PHP_SESSION_NONE) session_start();

// ─── PDO ───
$pdo = null;
try {
    if (isset($db))  $pdo = $db;
    elseif (class_exists('Database')) $pdo = Database::getInstance();
    else {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
        );
    }
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>'DB error: '.$e->getMessage()]); exit;
}

// ─── Helpers ───
function resp(bool $ok, string $msg='', array $extra=[]): void {
    echo json_encode(array_merge(['success'=>$ok,'message'=>$msg], $extra)); exit;
}
if (!function_exists('sanitize')) {
    function sanitize($v) { return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8'); }
}

// ─── Méthode ───
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// ─── POST : CRUD ───
if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    // CSRF — on vérifie sur create/update/delete
    if (in_array($action, ['create','update','delete','bulk_delete'])) {
        $token = $_POST['csrf_token'] ?? '';
        if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            resp(false, 'Token CSRF invalide');
        }
    }

    // ── CREATE ──────────────────────────────────────────────
    if ($action === 'create') {
        $nom    = sanitize($_POST['nom']    ?? '');
        $prenom = sanitize($_POST['prenom'] ?? '');
        if (!$nom) resp(false, 'Le nom est obligatoire');

        $fields = [
            'nom'             => $nom,
            'prenom'          => $prenom,
            'email'           => sanitize($_POST['email']           ?? ''),
            'phone'           => sanitize($_POST['phone']           ?? ''),
            'company'         => sanitize($_POST['company']         ?? ''),
            'city'            => sanitize($_POST['city']            ?? ''),
            'zone_geo'        => sanitize($_POST['zone_geo']        ?? ''),
            'type'            => in_array($_POST['type']??'', ['courtier','mandataire','apporteur','partenaire','notaire']) ? $_POST['type'] : 'courtier',
            'status'          => in_array($_POST['status']??'', ['actif','prospect','inactif','pause']) ? $_POST['status'] : 'prospect',
            'commission_rate' => (float)($_POST['commission_rate']  ?? 0),
            'notes'           => sanitize($_POST['notes']           ?? ''),
            'lead_id'         => (int)($_POST['lead_id'] ?? 0) ?: null,
        ];

        $cols = implode(',', array_keys($fields));
        $plcs = implode(',', array_fill(0, count($fields), '?'));
        try {
            $stmt = $pdo->prepare("INSERT INTO courtiers ($cols) VALUES ($plcs)");
            $stmt->execute(array_values($fields));
            $newId = $pdo->lastInsertId();
            $redirect = "?page=courtiers&msg=created";
            // Si appel AJAX → JSON, sinon redirect HTML
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strpos($_SERVER['HTTP_ACCEPT']??'','application/json')!==false) {
                resp(true, 'Courtier créé', ['id'=>$newId, 'redirect'=>$redirect]);
            }
            header("Location: $redirect"); exit;
        } catch (PDOException $e) {
            error_log("[Courtiers API] Create error: ".$e->getMessage());
            resp(false, 'Erreur lors de la création');
        }
    }

    // ── UPDATE ──────────────────────────────────────────────
    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) resp(false, 'ID manquant');

        $nom = sanitize($_POST['nom'] ?? '');
        if (!$nom) resp(false, 'Le nom est obligatoire');

        $fields = [
            'nom'             => $nom,
            'prenom'          => sanitize($_POST['prenom']          ?? ''),
            'email'           => sanitize($_POST['email']           ?? ''),
            'phone'           => sanitize($_POST['phone']           ?? ''),
            'company'         => sanitize($_POST['company']         ?? ''),
            'city'            => sanitize($_POST['city']            ?? ''),
            'zone_geo'        => sanitize($_POST['zone_geo']        ?? ''),
            'type'            => in_array($_POST['type']??'',['courtier','mandataire','apporteur','partenaire','notaire']) ? $_POST['type'] : 'courtier',
            'status'          => in_array($_POST['status']??'',['actif','prospect','inactif','pause']) ? $_POST['status'] : 'prospect',
            'commission_rate' => (float)($_POST['commission_rate']  ?? 0),
            'reco_count'      => (int)($_POST['reco_count']         ?? 0),
            'revenu_total'    => (float)($_POST['revenu_total']     ?? 0),
            'notes'           => sanitize($_POST['notes']           ?? ''),
            'lead_id'         => (int)($_POST['lead_id'] ?? 0) ?: null,
        ];

        $set = implode(',', array_map(fn($k) => "`$k`=?", array_keys($fields)));
        try {
            $stmt = $pdo->prepare("UPDATE courtiers SET $set WHERE id=?");
            $stmt->execute([...array_values($fields), $id]);
            $redirect = "?page=courtiers&msg=updated";
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strpos($_SERVER['HTTP_ACCEPT']??'','application/json')!==false) {
                resp(true, 'Courtier mis à jour', ['redirect'=>$redirect]);
            }
            header("Location: $redirect"); exit;
        } catch (PDOException $e) {
            error_log("[Courtiers API] Update error: ".$e->getMessage());
            resp(false, 'Erreur lors de la mise à jour');
        }
    }

    // ── DELETE ──────────────────────────────────────────────
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) resp(false, 'ID manquant');
        try {
            $pdo->prepare("DELETE FROM courtiers WHERE id=?")->execute([$id]);
            resp(true, 'Courtier supprimé');
        } catch (PDOException $e) {
            resp(false, 'Erreur lors de la suppression');
        }
    }

    // ── TOGGLE STATUS ────────────────────────────────────────
    if ($action === 'toggle_status') {
        $id     = (int)($_POST['id']     ?? 0);
        $status = $_POST['status'] ?? '';
        if (!$id || !in_array($status, ['actif','inactif','prospect','pause'])) resp(false, 'Paramètres invalides');
        try {
            $pdo->prepare("UPDATE courtiers SET status=? WHERE id=?")->execute([$status, $id]);
            resp(true, 'Statut mis à jour');
        } catch (PDOException $e) {
            resp(false, 'Erreur');
        }
    }

    // ── BULK STATUS ──────────────────────────────────────────
    if ($action === 'bulk_status') {
        $ids    = json_decode($_POST['ids'] ?? '[]', true);
        $status = $_POST['status'] ?? '';
        if (!is_array($ids) || empty($ids) || !in_array($status, ['actif','inactif','prospect','pause'])) {
            resp(false, 'Paramètres invalides');
        }
        $ids = array_map('intval', $ids);
        $plc = implode(',', array_fill(0, count($ids), '?'));
        try {
            $pdo->prepare("UPDATE courtiers SET status=? WHERE id IN ($plc)")->execute([$status, ...$ids]);
            resp(true, 'Statuts mis à jour', ['count'=>count($ids)]);
        } catch (PDOException $e) {
            resp(false, 'Erreur');
        }
    }

    // ── BULK DELETE ──────────────────────────────────────────
    if ($action === 'bulk_delete') {
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (!is_array($ids) || empty($ids)) resp(false, 'IDs manquants');
        $ids = array_map('intval', $ids);
        $plc = implode(',', array_fill(0, count($ids), '?'));
        try {
            $pdo->prepare("DELETE FROM courtiers WHERE id IN ($plc)")->execute($ids);
            resp(true, 'Suppression effectuée', ['count'=>count($ids)]);
        } catch (PDOException $e) {
            resp(false, 'Erreur lors de la suppression');
        }
    }

    resp(false, 'Action inconnue');
}

// ─── GET : form submit redirect (form HTML POST) ───
if ($method === 'POST' || $method === 'GET') {
    // Le formulaire submit déclenche un redirect côté JS
    // mais si JS est désactivé, on le gère ici
    resp(false, 'Méthode non supportée');
}

resp(false, 'Requête invalide');