<?php
/**
 * API AJAX — MODULE SMS MARKETING
 * /admin/modules/marketing/sms/api.php
 * Réponses JSON — pattern aligné séquences / pages
 */

if (!defined('ADMIN_ROUTER')) {
    session_start();
    if (empty($_SESSION['admin_id']) && empty($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['success'=>false,'error'=>'Non autorisé']);
        exit;
    }
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── DB ────────────────────────────────────────────────────────────────────────
if (!isset($pdo) && !isset($db)) {
    $cfg = dirname(dirname(dirname(dirname(__DIR__)))) . '/config/config.php';
    if (!file_exists($cfg)) $cfg = dirname(dirname(dirname(__DIR__))) . '/includes/config.php';
    if (file_exists($cfg)) require_once $cfg;
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    } catch (PDOException $e) { echo json_encode(['success'=>false,'error'=>'DB: '.$e->getMessage()]); exit; }
}
if (isset($db) && !isset($pdo)) $pdo = $db;

// ── Provider ──────────────────────────────────────────────────────────────────
require_once __DIR__ . '/SmsProvider.php';

// ── Helpers ───────────────────────────────────────────────────────────────────
function sms_ok(array $d=[]): void { echo json_encode(array_merge(['success'=>true],$d)); exit; }
function sms_err(string $m, int $c=400): void { http_response_code($c); echo json_encode(['success'=>false,'error'=>$m]); exit; }
function sms_csrf(): void {
    $t = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $s = $_SESSION['csrf_token'] ?? '';
    if (!$s || !hash_equals($s,$t)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'CSRF invalide']); exit; }
}
function sms_int(string $k): int { return (int)($_POST[$k] ?? 0); }
function sms_str(string $k, int $max=255): string { return mb_substr(trim($_POST[$k]??''),0,$max); }

// ── Routing ───────────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try { switch($action) {

// ══════════════════════════════════════════════════════════════════════════════
// SETTINGS
// ══════════════════════════════════════════════════════════════════════════════

case 'save_settings':
    sms_csrf();
    $keys = ['provider','brevo_api_key','brevo_sender',
             'twilio_account_sid','twilio_auth_token','twilio_from','default_sender'];
    $stmt = $pdo->prepare("INSERT INTO sms_settings (setting_key, setting_value)
        VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    foreach ($keys as $k) {
        if (isset($_POST[$k])) $stmt->execute([$k, trim($_POST[$k])]);
    }
    sms_ok(['message'=>'Paramètres enregistrés']);

case 'test_connection':
    sms_csrf();
    $driver = SmsProviderFactory::fromDatabase($pdo);
    if (!$driver) sms_err('Provider non configuré');
    $r = $driver->testConnection();
    sms_ok(['valid'=>$r['valid'],'balance'=>$r['balance'],'provider'=>$driver->getName(),'error'=>$r['error']]);

case 'get_balance':
    $driver = SmsProviderFactory::fromDatabase($pdo);
    if (!$driver) sms_ok(['balance'=>null]);
    sms_ok(['balance'=>$driver->getBalance(),'provider'=>$driver->getName()]);

// ══════════════════════════════════════════════════════════════════════════════
// CAMPAGNES
// ══════════════════════════════════════════════════════════════════════════════

case 'create_campaign':
    sms_csrf();
    $name = sms_str('name');
    if (!$name) sms_err('Nom obligatoire');
    $stmt = $pdo->prepare("INSERT INTO sms_campaigns
        (name, message, sender_name, status, segment_filter, scheduled_at, created_at)
        VALUES (?,?,?,?,?,?,NOW())");
    $stmt->execute([
        $name,
        mb_substr(trim($_POST['message']??''),0,480),
        sms_str('sender_name') ?: 'IMMOLOCAL',
        'draft',
        sms_str('segment_filter') ?: null,
        !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null,
    ]);
    $id = (int)$pdo->lastInsertId();
    // Construire la liste de destinataires
    $contactIds = self_build_recipients($pdo, $id, $_POST);
    $pdo->prepare("UPDATE sms_campaigns SET total_recipients=? WHERE id=?")->execute([count($contactIds),$id]);
    sms_ok(['id'=>$id,'recipients'=>count($contactIds),'message'=>'Campagne créée']);

case 'update_campaign':
    sms_csrf();
    $id = sms_int('id');
    if (!$id) sms_err('ID manquant');
    // Vérifier statut
    $cur = $pdo->prepare("SELECT status FROM sms_campaigns WHERE id=?");
    $cur->execute([$id]);
    $row = $cur->fetch();
    if (!$row) sms_err('Campagne introuvable',404);
    if ($row['status'] === 'sent') sms_err('Impossible de modifier une campagne déjà envoyée');

    $pdo->prepare("UPDATE sms_campaigns SET name=?,message=?,sender_name=?,segment_filter=?,scheduled_at=? WHERE id=?")
        ->execute([
            sms_str('name'),
            mb_substr(trim($_POST['message']??''),0,480),
            sms_str('sender_name') ?: 'IMMOLOCAL',
            sms_str('segment_filter') ?: null,
            !empty($_POST['scheduled_at']) ? $_POST['scheduled_at'] : null,
            $id,
        ]);
    // Reconstruire destinataires
    $pdo->prepare("DELETE FROM sms_campaign_recipients WHERE campaign_id=?")->execute([$id]);
    $contactIds = self_build_recipients($pdo, $id, $_POST);
    $pdo->prepare("UPDATE sms_campaigns SET total_recipients=? WHERE id=?")->execute([count($contactIds),$id]);
    sms_ok(['recipients'=>count($contactIds),'message'=>'Campagne mise à jour']);

case 'delete_campaign':
    sms_csrf();
    $id = sms_int('id');
    if (!$id) sms_err('ID manquant');
    $pdo->prepare("DELETE FROM sms_campaigns WHERE id=? AND status IN ('draft','scheduled')")->execute([$id]);
    sms_ok(['message'=>'Campagne supprimée']);

case 'duplicate_campaign':
    sms_csrf();
    $id = sms_int('id');
    $src = $pdo->prepare("SELECT * FROM sms_campaigns WHERE id=?");
    $src->execute([$id]);
    $c = $src->fetch();
    if (!$c) sms_err('Introuvable',404);
    $ins = $pdo->prepare("INSERT INTO sms_campaigns (name,message,sender_name,segment_filter,status,created_at)
        VALUES (?,?,?,?,'draft',NOW())");
    $ins->execute(['Copie — '.$c['name'],$c['message'],$c['sender_name'],$c['segment_filter']]);
    $newId = (int)$pdo->lastInsertId();
    // Copier les destinataires
    $pdo->exec("INSERT INTO sms_campaign_recipients (campaign_id, phone, first_name, last_name, lead_id, source)
        SELECT {$newId}, phone, first_name, last_name, lead_id, source
        FROM sms_campaign_recipients WHERE campaign_id={$id}");
    $count = (int)$pdo->query("SELECT COUNT(*) FROM sms_campaign_recipients WHERE campaign_id={$newId}")->fetchColumn();
    $pdo->prepare("UPDATE sms_campaigns SET total_recipients=? WHERE id=?")->execute([$count,$newId]);
    sms_ok(['id'=>$newId,'message'=>'Campagne dupliquée']);

case 'get_campaign':
    $id = sms_int('id') ?: (int)($_GET['id']??0);
    $stmt = $pdo->prepare("SELECT * FROM sms_campaigns WHERE id=?");
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if (!$c) sms_err('Introuvable',404);
    sms_ok(['campaign'=>$c]);

case 'send_campaign':
    sms_csrf();
    $id = sms_int('id');
    if (!$id) sms_err('ID manquant');

    // Vérifier statut
    $cur = $pdo->prepare("SELECT * FROM sms_campaigns WHERE id=?");
    $cur->execute([$id]);
    $camp = $cur->fetch();
    if (!$camp) sms_err('Campagne introuvable',404);
    if ($camp['status'] === 'sent') sms_err('Déjà envoyée');

    $driver = SmsProviderFactory::fromDatabase($pdo);
    if (!$driver) sms_err('SMS provider non configuré. Allez dans Paramètres → SMS.');

    // Marquer en cours
    $pdo->prepare("UPDATE sms_campaigns SET status='sending', sent_at=NOW() WHERE id=?")->execute([$id]);

    // Récupérer les destinataires
    $recip = $pdo->prepare("SELECT * FROM sms_campaign_recipients WHERE campaign_id=? AND status='pending'");
    $recip->execute([$id]);
    $recipients = $recip->fetchAll();

    if (empty($recipients)) {
        $pdo->prepare("UPDATE sms_campaigns SET status='draft' WHERE id=?")->execute([$id]);
        sms_err('Aucun destinataire en attente');
    }

    $sent = 0; $failed = 0; $errors = [];
    $updRecip = $pdo->prepare("UPDATE sms_campaign_recipients SET status=?,sent_at=?,error_msg=?,provider_msg_id=? WHERE id=?");
    $updCamp  = $pdo->prepare("UPDATE sms_campaigns SET total_sent=?,total_failed=? WHERE id=?");

    foreach ($recipients as $r) {
        // Personnaliser le message
        $msg = str_replace(
            ['{{prenom}}','{{nom}}','{{telephone}}'],
            [$r['first_name'] ?? '', $r['last_name'] ?? '', $r['phone']],
            $camp['message']
        );
        $result = $driver->send($r['phone'], $msg, $camp['sender_name']);
        if ($result['success']) {
            $sent++;
            $updRecip->execute(['sent', date('Y-m-d H:i:s'), null, $result['message_id'], $r['id']]);
        } else {
            $failed++;
            $errors[] = $r['phone'] . ': ' . $result['error'];
            $updRecip->execute(['failed', null, $result['error'], null, $r['id']]);
        }
        // Update compteurs intermédiaires toutes les 10 requêtes
        if (($sent + $failed) % 10 === 0) {
            $updCamp->execute([$sent, $failed, $id]);
        }
        usleep(50000); // 50ms entre requêtes pour respecter les rate limits
    }

    $finalStatus = ($failed === 0) ? 'sent' : (($sent === 0) ? 'failed' : 'partial');
    $pdo->prepare("UPDATE sms_campaigns SET status=?,total_sent=?,total_failed=?,sent_at=NOW() WHERE id=?")
        ->execute([$finalStatus, $sent, $failed, $id]);

    sms_ok([
        'sent'   => $sent,
        'failed' => $failed,
        'status' => $finalStatus,
        'errors' => array_slice($errors,0,5),
        'message'=> "{$sent} SMS envoyé(s), {$failed} échec(s)",
    ]);

case 'schedule_campaign':
    sms_csrf();
    $id  = sms_int('id');
    $at  = sms_str('scheduled_at');
    if (!$id || !$at) sms_err('Données manquantes');
    $pdo->prepare("UPDATE sms_campaigns SET status='scheduled', scheduled_at=? WHERE id=? AND status='draft'")
        ->execute([$at, $id]);
    sms_ok(['message'=>'Campagne planifiée pour le '.date('d/m/Y H:i', strtotime($at))]);

case 'unschedule_campaign':
    sms_csrf();
    $id = sms_int('id');
    $pdo->prepare("UPDATE sms_campaigns SET status='draft', scheduled_at=NULL WHERE id=? AND status='scheduled'")
        ->execute([$id]);
    sms_ok(['message'=>'Planification annulée']);

// ══════════════════════════════════════════════════════════════════════════════
// DESTINATAIRES
// ══════════════════════════════════════════════════════════════════════════════

case 'get_recipients_preview':
    // Aperçu des destinataires selon les filtres sans créer la campagne
    $filter   = sms_str('segment_filter');
    $listId   = sms_int('list_id');
    $csvData  = $_POST['csv_phones'] ?? '';
    $count    = 0;
    $preview  = [];

    if ($listId) {
        $s = $pdo->prepare("SELECT COUNT(*) FROM sms_contacts WHERE list_id=? AND opted_out=0");
        $s->execute([$listId]);
        $count = (int)$s->fetchColumn();
        $p = $pdo->prepare("SELECT phone,first_name,last_name FROM sms_contacts WHERE list_id=? AND opted_out=0 LIMIT 5");
        $p->execute([$listId]);
        $preview = $p->fetchAll();
    } elseif ($filter) {
        try {
            $q = "SELECT COUNT(*) FROM leads WHERE phone IS NOT NULL AND phone!=''";
            if ($filter && $filter !== 'all') $q .= " AND status=".($pdo->quote($filter));
            $count = (int)$pdo->query($q)->fetchColumn();
            $pq = "SELECT phone,first_name,last_name FROM leads WHERE phone IS NOT NULL AND phone!=''";
            if ($filter && $filter !== 'all') $pq .= " AND status=".($pdo->quote($filter));
            $pq .= " LIMIT 5";
            $preview = $pdo->query($pq)->fetchAll();
        } catch (PDOException $e) {}
    } elseif ($csvData) {
        $phones = array_filter(array_map('trim', explode(',', $csvData)));
        $count  = count($phones);
        foreach (array_slice($phones,0,5) as $ph) $preview[] = ['phone'=>$ph,'first_name'=>'','last_name'=>''];
    }
    sms_ok(['count'=>$count,'preview'=>$preview]);

case 'import_csv_contacts':
    sms_csrf();
    $listId = sms_int('list_id');
    if (!$listId) sms_err('Sélectionnez une liste');
    if (empty($_FILES['csv_file']['tmp_name'])) sms_err('Fichier CSV manquant');
    $handle  = fopen($_FILES['csv_file']['tmp_name'], 'r');
    $headers = fgetcsv($handle, 0, ';') ?: fgetcsv($handle, 0, ',');
    $headers = array_map('strtolower', $headers ?? []);
    $phoneCol  = array_search('telephone', $headers) !== false ? array_search('telephone',$headers)
               : (array_search('phone',$headers) !== false ? array_search('phone',$headers) : 0);
    $firstCol  = array_search('prenom',$headers) !== false ? array_search('prenom',$headers) : (array_search('first_name',$headers) ?: null);
    $lastCol   = array_search('nom',$headers) !== false ? array_search('nom',$headers) : (array_search('last_name',$headers) ?: null);
    $stmt    = $pdo->prepare("INSERT IGNORE INTO sms_contacts (list_id,phone,first_name,last_name,source,created_at)
        VALUES (?,?,?,?,'csv_import',NOW())");
    $imported = 0; $skipped = 0;
    while (($row = fgetcsv($handle,0,';')) !== false ?: ($row = fgetcsv($handle,0,',')) !== false) {
        $phone = trim($row[$phoneCol] ?? '');
        if (!$phone) { $skipped++; continue; }
        $stmt->execute([
            $listId, $phone,
            $firstCol !== null ? trim($row[$firstCol]??'') : '',
            $lastCol  !== null ? trim($row[$lastCol]??'')  : '',
        ]);
        if ($stmt->rowCount()) $imported++; else $skipped++;
    }
    fclose($handle);
    $pdo->prepare("UPDATE sms_lists SET contact_count=(SELECT COUNT(*) FROM sms_contacts WHERE list_id=?) WHERE id=?")
        ->execute([$listId,$listId]);
    sms_ok(['imported'=>$imported,'skipped'=>$skipped,'message'=>"{$imported} contacts importés, {$skipped} ignorés"]);

// ══════════════════════════════════════════════════════════════════════════════
// LISTES DE CONTACTS
// ══════════════════════════════════════════════════════════════════════════════

case 'create_list':
    sms_csrf();
    $name = sms_str('name');
    if (!$name) sms_err('Nom obligatoire');
    $pdo->prepare("INSERT INTO sms_lists (name,description,created_at) VALUES (?,?,NOW())")
        ->execute([$name, sms_str('description',500)]);
    sms_ok(['id'=>(int)$pdo->lastInsertId(),'message'=>'Liste créée']);

case 'delete_list':
    sms_csrf();
    $id = sms_int('id');
    $pdo->prepare("DELETE FROM sms_lists WHERE id=?")->execute([$id]);
    sms_ok(['message'=>'Liste supprimée']);

case 'get_lists':
    $lists = $pdo->query("SELECT l.*, COUNT(c.id) as contact_count
        FROM sms_lists l
        LEFT JOIN sms_contacts c ON c.list_id=l.id AND c.opted_out=0
        GROUP BY l.id ORDER BY l.created_at DESC")->fetchAll();
    sms_ok(['lists'=>$lists]);

case 'add_contact':
    sms_csrf();
    $listId = sms_int('list_id');
    $phone  = sms_str('phone');
    if (!$listId || !$phone) sms_err('Données manquantes');
    $pdo->prepare("INSERT IGNORE INTO sms_contacts (list_id,phone,first_name,last_name,source,created_at)
        VALUES (?,?,?,?,'manual',NOW())")
        ->execute([$listId,$phone,sms_str('first_name'),sms_str('last_name')]);
    $pdo->prepare("UPDATE sms_lists SET contact_count=(SELECT COUNT(*) FROM sms_contacts WHERE list_id=? AND opted_out=0) WHERE id=?")
        ->execute([$listId,$listId]);
    sms_ok(['message'=>'Contact ajouté']);

case 'remove_contact':
    sms_csrf();
    $id = sms_int('id');
    $pdo->prepare("DELETE FROM sms_contacts WHERE id=?")->execute([$id]);
    sms_ok(['message'=>'Contact supprimé']);

case 'opt_out_contact':
    sms_csrf();
    $phone = sms_str('phone');
    $pdo->prepare("UPDATE sms_contacts SET opted_out=1,opted_out_at=NOW() WHERE phone=?")->execute([$phone]);
    sms_ok(['message'=>'Contact désabonné']);

// ══════════════════════════════════════════════════════════════════════════════
// STATS & HISTORIQUE
// ══════════════════════════════════════════════════════════════════════════════

case 'get_stats':
    $stats = [];
    $stats['total_campaigns'] = (int)$pdo->query("SELECT COUNT(*) FROM sms_campaigns")->fetchColumn();
    $stats['total_sent']      = (int)$pdo->query("SELECT COALESCE(SUM(total_sent),0) FROM sms_campaigns")->fetchColumn();
    $stats['total_failed']    = (int)$pdo->query("SELECT COALESCE(SUM(total_failed),0) FROM sms_campaigns")->fetchColumn();
    $stats['active_campaigns']= (int)$pdo->query("SELECT COUNT(*) FROM sms_campaigns WHERE status IN ('sending','scheduled')")->fetchColumn();
    $stats['total_contacts']  = (int)$pdo->query("SELECT COUNT(*) FROM sms_contacts WHERE opted_out=0")->fetchColumn();
    $total = $stats['total_sent'] + $stats['total_failed'];
    $stats['delivery_rate']   = $total > 0 ? round(($stats['total_sent']/$total)*100,1) : 0;
    sms_ok(['stats'=>$stats]);

case 'get_campaign_recipients':
    $campId = sms_int('campaign_id') ?: (int)($_GET['campaign_id']??0);
    $status = $_GET['status'] ?? '';
    $limit  = min(200,(int)($_GET['limit']??100));
    $q = "SELECT * FROM sms_campaign_recipients WHERE campaign_id=?";
    $p = [$campId];
    if ($status) { $q .= " AND status=?"; $p[] = $status; }
    $q .= " ORDER BY id DESC LIMIT {$limit}";
    $stmt = $pdo->prepare($q);
    $stmt->execute($p);
    sms_ok(['recipients'=>$stmt->fetchAll()]);

default:
    sms_err('Action inconnue: '.htmlspecialchars($action));

}} catch (PDOException $e) {
    sms_err('DB: '.$e->getMessage(), 500);
} catch (Exception $e) {
    sms_err($e->getMessage(), 500);
}

// ══════════════════════════════════════════════════════════════════════════════
// Helper interne — construire la liste des destinataires d'une campagne
// ══════════════════════════════════════════════════════════════════════════════
function self_build_recipients(PDO $pdo, int $campaignId, array $post): array {
    $contactIds = [];
    $ins = $pdo->prepare("INSERT IGNORE INTO sms_campaign_recipients
        (campaign_id, phone, first_name, last_name, lead_id, source, status)
        VALUES (?,?,?,?,?,?,?)");

    $source = $post['source'] ?? 'crm'; // crm | list | csv

    // ── Source : leads CRM ────────────────────────────────────────
    if ($source === 'crm' || $source === 'all') {
        $filter = trim($post['segment_filter'] ?? '');
        $q = "SELECT id, phone, first_name, last_name FROM leads WHERE phone IS NOT NULL AND phone!=''";
        if ($filter && $filter !== 'all') $q .= " AND status=".$pdo->quote($filter);
        try {
            foreach ($pdo->query($q)->fetchAll() as $lead) {
                $ins->execute([$campaignId,$lead['phone'],$lead['first_name'],$lead['last_name'],$lead['id'],'crm','pending']);
                if ($ins->rowCount()) $contactIds[] = $lead['id'];
            }
        } catch (PDOException $e) {}
    }

    // ── Source : liste personnalisée ──────────────────────────────
    if ($source === 'list' || $source === 'all') {
        $listId = (int)($post['list_id'] ?? 0);
        if ($listId) {
            $s = $pdo->prepare("SELECT id,phone,first_name,last_name FROM sms_contacts WHERE list_id=? AND opted_out=0");
            $s->execute([$listId]);
            foreach ($s->fetchAll() as $c) {
                $ins->execute([$campaignId,$c['phone'],$c['first_name'],$c['last_name'],null,'list','pending']);
                if ($ins->rowCount()) $contactIds[] = 'c'.$c['id'];
            }
        }
    }

    // ── Source : CSV collé ────────────────────────────────────────
    if ($source === 'csv') {
        $csvPhones = array_filter(array_map('trim', explode("\n", $post['csv_phones'] ?? '')));
        foreach ($csvPhones as $line) {
            $parts = str_getcsv($line, ';');
            $phone = trim($parts[0] ?? '');
            if (!$phone) continue;
            $ins->execute([$campaignId,$phone,$parts[1]??'',$parts[2]??'',null,'csv','pending']);
            if ($ins->rowCount()) $contactIds[] = $phone;
        }
    }

    return $contactIds;
}