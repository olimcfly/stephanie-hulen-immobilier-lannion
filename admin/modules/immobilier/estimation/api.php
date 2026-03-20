<?php
/**
 * ═══════════════════════════════════════════════════════════════
 * API Estimation - /admin/modules/immobilier/estimation/api.php
 * ═══════════════════════════════════════════════════════════════
 * Actions :
 *   list          → Liste avec pagination et filtres
 *   get           → Détail d'une estimation
 *   update_status → Changer statut (en_attente, traitee, convertie)
 *   update_notes  → Mettre à jour les notes
 *   delete        → Supprimer
 *   send_email    → Envoyer un email rapide via template
 *   export        → Export CSV
 *   stats         → Statistiques
 * ═══════════════════════════════════════════════════════════════
 */

header('Content-Type: application/json; charset=utf-8');

// ─── INITIALISATION DB ──────────────────────────────────────
if (!isset($pdo)) {
    $rootPath = realpath(__DIR__ . '/../../../');
    foreach (['/config/database.php', '/includes/Database.php', '/config/config.php'] as $f) {
        if (file_exists($rootPath . $f)) { require_once $rootPath . $f; break; }
    }
    if (class_exists('Database')) $pdo = Database::getInstance();
}

if (!isset($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Connexion base de données indisponible']);
    exit;
}

// Vérifier que la table existe
try {
    $tableExists = ($pdo->query("SHOW TABLES LIKE 'estimations'")->rowCount() > 0);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur DB: ' . $e->getMessage()]);
    exit;
}

if (!$tableExists) {
    echo json_encode(['success' => false, 'message' => 'Table estimations introuvable']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input = $_POST;

switch ($action) {

    // ─── LIST ────────────────────────────────────────────────
    case 'list':
        try {
            $filterStatut = $input['status'] ?? $_GET['status'] ?? 'all';
            $filterSearch = trim($input['search'] ?? $_GET['q'] ?? '');
            $filterType = $input['type_bien'] ?? $_GET['type_bien'] ?? '';
            $page = max(1, (int)($input['page'] ?? $_GET['p'] ?? 1));
            $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 25);
            $offset = ($page - 1) * $perPage;

            $where = "WHERE 1=1";
            $params = [];
            if ($filterStatut !== 'all' && $filterStatut !== '') {
                $where .= " AND statut=:fs";
                $params[':fs'] = $filterStatut;
            }
            if ($filterType !== '') {
                $where .= " AND type_bien=:ft";
                $params[':ft'] = $filterType;
            }
            if ($filterSearch !== '') {
                $where .= " AND (nom LIKE :q OR prenom LIKE :q OR email LIKE :q OR telephone LIKE :q OR adresse LIKE :q OR ville LIKE :q)";
                $params[':q'] = '%' . $filterSearch . '%';
            }

            $cs = $pdo->prepare("SELECT COUNT(*) FROM estimations $where");
            $cs->execute($params);
            $total = (int)$cs->fetchColumn();
            $totalPages = max(1, ceil($total / $perPage));

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
                'data' => $ds->fetchAll(PDO::FETCH_ASSOC),
                'total' => $total,
                'page' => $page,
                'total_pages' => $totalPages
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ─── GET ─────────────────────────────────────────────────
    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID requis']);
                break;
            }
            $stmt = $pdo->prepare("SELECT e.*,
                (SELECT COUNT(*) FROM estimation_rdv r WHERE r.request_id=e.id) as nb_rdv,
                (SELECT COUNT(*) FROM estimation_reports rp WHERE rp.request_id=e.id) as nb_reports,
                (SELECT COUNT(*) FROM estimation_contacts c WHERE c.request_id=e.id) as nb_contacts
                FROM estimations e WHERE e.id=:id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row
                ? ['success' => true, 'data' => $row]
                : ['success' => false, 'message' => 'Estimation non trouvée']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ─── UPDATE STATUS ───────────────────────────────────────
    case 'update_status':
        try {
            $id = (int)($input['id'] ?? 0);
            $s = $input['statut'] ?? '';
            if ($id > 0 && in_array($s, ['en_attente', 'traitee', 'convertie'])) {
                $pdo->prepare("UPDATE estimations SET statut=:s WHERE id=:id")->execute([':s' => $s, ':id' => $id]);
                echo json_encode(['success' => true, 'message' => 'Statut mis à jour']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ─── UPDATE NOTES ────────────────────────────────────────
    case 'update_notes':
        try {
            $id = (int)($input['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare("UPDATE estimations SET notes=:n WHERE id=:id")->execute([':n' => trim($input['notes'] ?? ''), ':id' => $id]);
                echo json_encode(['success' => true, 'message' => 'Notes sauvegardées']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID requis']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ─── DELETE ──────────────────────────────────────────────
    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare("DELETE FROM estimations WHERE id=:id")->execute([':id' => $id]);
                echo json_encode(['success' => true, 'message' => 'Demande supprimée']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ID requis']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ─── SEND EMAIL ──────────────────────────────────────────
    case 'send_email':
        try {
            $eid = (int)($input['estimation_id'] ?? 0);
            $ttype = $input['template_type'] ?? 'confirmation';
            $mailerPath = realpath(__DIR__ . '/../../../') . '/includes/estimation_mailer.php';

            if ($eid <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID estimation invalide']);
                break;
            }
            if (!file_exists($mailerPath)) {
                echo json_encode(['success' => false, 'message' => 'Mailer non trouvé']);
                break;
            }

            require_once $mailerPath;

            $est = $pdo->prepare("SELECT * FROM estimations WHERE id=:id");
            $est->execute([':id' => $eid]);
            $estimation = $est->fetch(PDO::FETCH_ASSOC);

            if (!$estimation || empty($estimation['email'])) {
                echo json_encode(['success' => false, 'message' => 'Email manquant ou estimation introuvable']);
                break;
            }

            $tpl = $pdo->prepare("SELECT * FROM estimation_templates WHERE type=:t AND status='actif' LIMIT 1");
            $tpl->execute([':t' => $ttype]);
            $template = $tpl->fetch(PDO::FETCH_ASSOC);

            if (!$template) {
                echo json_encode(['success' => false, 'message' => 'Aucun template actif "' . $ttype . '"']);
                break;
            }

            $vars = [
                'prenom' => $estimation['prenom'] ?? '',
                'nom' => $estimation['nom'] ?? '',
                'email' => $estimation['email'] ?? '',
                'telephone' => $estimation['telephone'] ?? '',
                'type_bien' => ucfirst($estimation['type_bien'] ?? ''),
                'surface' => $estimation['surface'] ?? '',
                'pieces' => $estimation['pieces'] ?? '',
                'adresse' => $estimation['adresse'] ?? '',
                'ville' => $estimation['ville'] ?? '',
                'code_postal' => $estimation['code_postal'] ?? '',
                'estimation_basse' => $estimation['estimation_basse'] ? number_format((float)$estimation['estimation_basse'], 0, ',', ' ') : '—',
                'estimation_haute' => $estimation['estimation_haute'] ? number_format((float)$estimation['estimation_haute'], 0, ',', ' ') : '—',
                'date_creation' => $estimation['date_creation'] ? date('d/m/Y', strtotime($estimation['date_creation'])) : date('d/m/Y'),
            ];

            $subj = replaceVariables($template['subject'], $vars);
            $body = replaceVariables($template['body'], $vars);
            $sent = sendHtmlEmail($estimation['email'], $subj, $body, $pdo);

            if ($sent) {
                logEmailContact($pdo, $eid, $subj, $body, 'out');
                echo json_encode(['success' => true, 'message' => 'Email envoyé à ' . $estimation['email']]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Échec envoi']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ─── EXPORT CSV ──────────────────────────────────────────
    case 'export':
        try {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="estimations_' . date('Y-m-d_His') . '.csv"');

            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // BOM UTF-8

            fputcsv($output, ['ID', 'Prénom', 'Nom', 'Email', 'Téléphone', 'Type bien', 'Surface', 'Pièces', 'Adresse', 'Code postal', 'Ville', 'Estimation basse', 'Estimation haute', 'Statut', 'Notes', 'Date création'], ';');

            $stmt = $pdo->query("SELECT * FROM estimations ORDER BY date_creation DESC");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, [
                    $row['id'],
                    $row['prenom'] ?? '',
                    $row['nom'] ?? '',
                    $row['email'] ?? '',
                    $row['telephone'] ?? '',
                    $row['type_bien'] ?? '',
                    $row['surface'] ?? '',
                    $row['pieces'] ?? '',
                    $row['adresse'] ?? '',
                    $row['code_postal'] ?? '',
                    $row['ville'] ?? '',
                    $row['estimation_basse'] ?? '',
                    $row['estimation_haute'] ?? '',
                    $row['statut'] ?? '',
                    $row['notes'] ?? '',
                    $row['date_creation'] ?? '',
                ], ';');
            }

            fclose($output);
        } catch (Exception $e) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;

    // ─── STATS ───────────────────────────────────────────────
    case 'stats':
        try {
            $stats = $pdo->query("SELECT
                COUNT(*) as total,
                SUM(CASE WHEN statut='en_attente' THEN 1 ELSE 0 END) as nb_en_attente,
                SUM(CASE WHEN statut='traitee' THEN 1 ELSE 0 END) as nb_traitee,
                SUM(CASE WHEN statut='convertie' THEN 1 ELSE 0 END) as nb_convertie
                FROM estimations")->fetch(PDO::FETCH_ASSOC);

            $rdvCount = 0; $reportCount = 0; $emailCount = 0;
            try { $rdvCount = (int)$pdo->query("SELECT COUNT(*) FROM estimation_rdv WHERE status IN ('proposed','planifie','confirmed')")->fetchColumn(); } catch (Exception $e) {}
            try { $reportCount = (int)$pdo->query("SELECT COUNT(*) FROM estimation_reports")->fetchColumn(); } catch (Exception $e) {}
            try { $emailCount = (int)$pdo->query("SELECT COUNT(*) FROM estimation_contacts WHERE contact_type='email'")->fetchColumn(); } catch (Exception $e) {}

            $stats['nb_rdv'] = $rdvCount;
            $stats['nb_reports'] = $reportCount;
            $stats['nb_emails'] = $emailCount;

            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ─── DEFAULT ─────────────────────────────────────────────
    default:
        echo json_encode(['success' => false, 'message' => "Action '$action' non supportée"]);
}

exit;
