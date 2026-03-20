<?php
/**
 * ══════════════════════════════════════════════════════════════
 * ESTIMATION API v1.0
 * /admin/modules/immobilier/estimation/api.php
 * Actions : list, get, update_status, update_notes, delete, send_email, export, stats
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
    echo json_encode(['success'=>false,'message'=>'DB error: '.$e->getMessage()]); exit;
}

// ─── Helpers ───
function resp(bool $ok, string $msg='', array $extra=[]): void {
    echo json_encode(array_merge(['success'=>$ok,'message'=>$msg], $extra)); exit;
}

// ─── Check table ───
try {
    if ($pdo->query("SHOW TABLES LIKE 'estimations'")->rowCount() === 0) {
        resp(false, 'Table estimations introuvable');
    }
} catch (Exception $e) {
    resp(false, 'Erreur DB: '.$e->getMessage());
}

// ─── Action routing ───
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input  = $_POST;

switch ($action) {

    // ═══ LIST — Liste avec pagination et filtres ═══
    case 'list':
        try {
            $status  = $_GET['status'] ?? '';
            $search  = trim($_GET['q'] ?? '');
            $typeBien = $_GET['type_bien'] ?? '';
            $page    = max(1, (int)($_GET['page'] ?? 1));
            $perPage = (int)($_GET['per_page'] ?? 25);
            $offset  = ($page - 1) * $perPage;

            $where = 'WHERE 1=1'; $params = [];
            if ($status)   { $where .= " AND statut = :s";   $params[':s'] = $status; }
            if ($typeBien) { $where .= " AND type_bien = :t"; $params[':t'] = $typeBien; }
            if ($search)   {
                $where .= " AND (nom LIKE :q OR prenom LIKE :q OR email LIKE :q OR telephone LIKE :q OR adresse LIKE :q OR ville LIKE :q)";
                $params[':q'] = '%'.$search.'%';
            }

            $cs = $pdo->prepare("SELECT COUNT(*) FROM estimations $where");
            $cs->execute($params);
            $total = (int)$cs->fetchColumn();

            $ds = $pdo->prepare("SELECT e.*,
                (SELECT COUNT(*) FROM estimation_rdv r WHERE r.request_id=e.id) as nb_rdv,
                (SELECT COUNT(*) FROM estimation_reports rp WHERE rp.request_id=e.id) as nb_reports,
                (SELECT COUNT(*) FROM estimation_contacts c WHERE c.request_id=e.id) as nb_contacts
                FROM estimations e $where
                ORDER BY FIELD(e.statut,'en_attente','traitee','convertie'), e.date_creation DESC
                LIMIT $perPage OFFSET $offset");
            $ds->execute($params);

            echo json_encode([
                'success' => true,
                'data'    => $ds->fetchAll(PDO::FETCH_ASSOC),
                'total'   => $total,
                'page'    => $page,
                'pages'   => max(1, ceil($total / $perPage))
            ]);
        } catch (PDOException $e) {
            resp(false, $e->getMessage());
        }
        break;

    // ═══ GET — Détail d'une estimation ═══
    case 'get':
        try {
            $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
            if (!$id) resp(false, 'ID requis');
            $stmt = $pdo->prepare("SELECT e.*,
                (SELECT COUNT(*) FROM estimation_rdv r WHERE r.request_id=e.id) as nb_rdv,
                (SELECT COUNT(*) FROM estimation_reports rp WHERE rp.request_id=e.id) as nb_reports,
                (SELECT COUNT(*) FROM estimation_contacts c WHERE c.request_id=e.id) as nb_contacts
                FROM estimations e WHERE e.id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) echo json_encode(['success'=>true, 'data'=>$row]);
            else resp(false, 'Estimation non trouvée');
        } catch (PDOException $e) {
            resp(false, $e->getMessage());
        }
        break;

    // ═══ UPDATE_STATUS — Changer statut (en_attente, traitee, convertie) ═══
    case 'update_status':
        try {
            $id = (int)($input['id'] ?? 0);
            $statut = $input['statut'] ?? '';
            if ($id <= 0 || !in_array($statut, ['en_attente','traitee','convertie'])) {
                resp(false, 'Paramètres invalides');
            }
            $pdo->prepare("UPDATE estimations SET statut = :s WHERE id = :id")
                ->execute([':s'=>$statut, ':id'=>$id]);
            resp(true, 'Statut mis à jour');
        } catch (PDOException $e) {
            resp(false, $e->getMessage());
        }
        break;

    // ═══ UPDATE_NOTES — Mettre à jour les notes ═══
    case 'update_notes':
        try {
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) resp(false, 'ID requis');
            $pdo->prepare("UPDATE estimations SET notes = :n WHERE id = :id")
                ->execute([':n'=>trim($input['notes'] ?? ''), ':id'=>$id]);
            resp(true, 'Notes sauvegardées');
        } catch (PDOException $e) {
            resp(false, $e->getMessage());
        }
        break;

    // ═══ DELETE — Supprimer ═══
    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) resp(false, 'ID requis');
            $pdo->prepare("DELETE FROM estimations WHERE id = :id")->execute([':id'=>$id]);
            resp(true, 'Demande supprimée');
        } catch (PDOException $e) {
            resp(false, $e->getMessage());
        }
        break;

    // ═══ SEND_EMAIL — Envoyer un email rapide via template ═══
    case 'send_email':
        try {
            $eid   = (int)($input['estimation_id'] ?? 0);
            $ttype = $input['template_type'] ?? 'confirmation';
            $mailerPath = realpath(dirname(dirname(dirname(__DIR__)))).'/includes/estimation_mailer.php';

            if ($eid <= 0) resp(false, 'ID estimation invalide');
            if (!file_exists($mailerPath)) resp(false, 'Mailer non trouvé');

            require_once $mailerPath;

            $est = $pdo->prepare("SELECT * FROM estimations WHERE id = :id");
            $est->execute([':id'=>$eid]);
            $estimation = $est->fetch(PDO::FETCH_ASSOC);

            if (!$estimation || empty($estimation['email'])) resp(false, 'Email manquant');

            $tpl = $pdo->prepare("SELECT * FROM estimation_templates WHERE type = :t AND status = 'actif' LIMIT 1");
            $tpl->execute([':t'=>$ttype]);
            $template = $tpl->fetch(PDO::FETCH_ASSOC);

            if (!$template) resp(false, 'Aucun template actif "'.$ttype.'"');

            $vars = [
                'prenom'          => $estimation['prenom'] ?? '',
                'nom'             => $estimation['nom'] ?? '',
                'email'           => $estimation['email'] ?? '',
                'telephone'       => $estimation['telephone'] ?? '',
                'type_bien'       => ucfirst($estimation['type_bien'] ?? ''),
                'surface'         => $estimation['surface'] ?? '',
                'pieces'          => $estimation['pieces'] ?? '',
                'adresse'         => $estimation['adresse'] ?? '',
                'ville'           => $estimation['ville'] ?? '',
                'code_postal'     => $estimation['code_postal'] ?? '',
                'estimation_basse'=> $estimation['estimation_basse']
                    ? number_format((float)$estimation['estimation_basse'], 0, ',', ' ') : '—',
                'estimation_haute'=> $estimation['estimation_haute']
                    ? number_format((float)$estimation['estimation_haute'], 0, ',', ' ') : '—',
                'date_creation'   => $estimation['date_creation']
                    ? date('d/m/Y', strtotime($estimation['date_creation'])) : date('d/m/Y'),
            ];

            $subj = replaceVariables($template['subject'], $vars);
            $body = replaceVariables($template['body'], $vars);
            $sent = sendHtmlEmail($estimation['email'], $subj, $body, $pdo);

            if ($sent) {
                logEmailContact($pdo, $eid, $subj, $body, 'out');
                resp(true, 'Email envoyé à '.$estimation['email']);
            } else {
                resp(false, 'Échec envoi');
            }
        } catch (Exception $e) {
            resp(false, $e->getMessage());
        }
        break;

    // ═══ EXPORT — Export CSV ═══
    case 'export':
        try {
            $status  = $_GET['status'] ?? '';
            $typeBien = $_GET['type_bien'] ?? '';

            $where = 'WHERE 1=1'; $params = [];
            if ($status)   { $where .= " AND statut = :s";   $params[':s'] = $status; }
            if ($typeBien) { $where .= " AND type_bien = :t"; $params[':t'] = $typeBien; }

            $stmt = $pdo->prepare("SELECT id, prenom, nom, email, telephone, type_bien, surface, pieces, adresse, code_postal, ville, estimation_basse, estimation_haute, statut, notes, date_creation FROM estimations $where ORDER BY date_creation DESC");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="estimations_'.date('Y-m-d').'.csv"');

            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID','Prénom','Nom','Email','Téléphone','Type bien','Surface','Pièces','Adresse','CP','Ville','Est. basse','Est. haute','Statut','Notes','Date'], ';');
            foreach ($rows as $r) {
                fputcsv($out, array_values($r), ';');
            }
            fclose($out);
        } catch (PDOException $e) {
            header('Content-Type: application/json; charset=utf-8');
            resp(false, $e->getMessage());
        }
        exit;

    // ═══ STATS — Statistiques ═══
    case 'stats':
        try {
            $stats = $pdo->query("SELECT
                COUNT(*) as total,
                SUM(CASE WHEN statut='en_attente' THEN 1 ELSE 0 END) as en_attente,
                SUM(CASE WHEN statut='traitee' THEN 1 ELSE 0 END) as traitee,
                SUM(CASE WHEN statut='convertie' THEN 1 ELSE 0 END) as convertie
                FROM estimations")->fetch(PDO::FETCH_ASSOC);

            $extra = [];
            try { $extra['rdv'] = (int)$pdo->query("SELECT COUNT(*) FROM estimation_rdv WHERE status IN ('proposed','planifie','confirmed')")->fetchColumn(); } catch(Exception $e){ $extra['rdv'] = 0; }
            try { $extra['reports'] = (int)$pdo->query("SELECT COUNT(*) FROM estimation_reports")->fetchColumn(); } catch(Exception $e){ $extra['reports'] = 0; }
            try { $extra['emails'] = (int)$pdo->query("SELECT COUNT(*) FROM estimation_contacts WHERE contact_type='email'")->fetchColumn(); } catch(Exception $e){ $extra['emails'] = 0; }

            echo json_encode(['success'=>true, 'data'=>array_merge($stats, $extra)]);
        } catch (PDOException $e) {
            resp(false, $e->getMessage());
        }
        break;

    default:
        resp(false, "Action '{$action}' non supportée");
}
