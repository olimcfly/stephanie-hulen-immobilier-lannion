<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MESSAGERIE CRM — API AJAX
 *  /admin/modules/marketing/crm/messenger_api.php
 *  Route : ?page=crm&msgrapi=1
 *
 *  Actions :
 *   account_save    — créer/modifier compte SMTP/IMAP
 *   account_delete  — supprimer compte
 *   account_test    — tester SMTP + IMAP
 *   mail_sync       — synchronisation IMAP → DB
 *   threads_list    — liste threads (pagination)
 *   thread_get      — messages d'un thread
 *   mail_send       — envoyer un email
 *   mail_delete     — supprimer message
 *   thread_close    — fermer / rouvrir thread
 *   thread_link     — lier thread à un lead
 *   mark_read       — marquer lu
 *   ai_suggest      — suggestion réponse IA
 *   prompt_save     — sauvegarder prompt IA
 * ══════════════════════════════════════════════════════════════
 */
if (!defined('ADMIN_ROUTER')) { http_response_code(403); exit; }
if (isset($db) && !isset($pdo)) $pdo = $db;

header('Content-Type: application/json; charset=utf-8');

// ── Clé de chiffrement (AES-256) ─────────────────────────────
// Définir MAIL_ENC_KEY dans config.php ou .env  : 32 chars
define('MAIL_ENC_KEY', defined('CRM_MAIL_KEY') ? CRM_MAIL_KEY : 'immolocal_crm_mail_key_32chars!!');

function encPass(string $plain): string {
    $iv  = random_bytes(16);
    $enc = openssl_encrypt($plain, 'AES-256-CBC', MAIL_ENC_KEY, 0, $iv);
    return base64_encode($iv . $enc);
}
function decPass(string $stored): string {
    $raw = base64_decode($stored);
    $iv  = substr($raw, 0, 16);
    $enc = substr($raw, 16);
    return openssl_decrypt($enc, 'AES-256-CBC', MAIL_ENC_KEY, 0, $iv) ?: '';
}

// ── Helpers ───────────────────────────────────────────────────
function jsonOk(array $data = []): void {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}
function jsonErr(string $msg, int $code = 0): void {
    echo json_encode(['success' => false, 'error' => $msg, 'code' => $code]);
    exit;
}

// ── Charger PHPMailer (via Composer ou include direct) ────────
function loadMailer(): bool {
    // Composer autoload
    $autoload = dirname(__DIR__, 4) . '/vendor/autoload.php';
    if (file_exists($autoload)) { require_once $autoload; return true; }
    // Include manuel (PHPMailer dans /lib/)
    $lib = dirname(__DIR__, 4) . '/lib/PHPMailer/';
    if (is_dir($lib)) {
        require_once $lib . 'Exception.php';
        require_once $lib . 'PHPMailer.php';
        require_once $lib . 'SMTP.php';
        return true;
    }
    return false;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ════════════════════════════════════════════════════════════
switch ($action) {

// ── Sauvegarder compte SMTP/IMAP ────────────────────────────
case 'account_save':
    $id = (int)($_POST['id'] ?? 0);
    $fields = [
        'label'       => trim($_POST['label']       ?? 'Mon email'),
        'from_name'   => trim($_POST['from_name']   ?? ''),
        'email'       => strtolower(trim($_POST['email'] ?? '')),
        'smtp_host'   => trim($_POST['smtp_host']   ?? ''),
        'smtp_port'   => (int)($_POST['smtp_port']  ?? 587),
        'smtp_user'   => trim($_POST['smtp_user']   ?? ''),
        'smtp_secure' => in_array($_POST['smtp_secure'] ?? '', ['tls','ssl','none']) ? $_POST['smtp_secure'] : 'tls',
        'imap_host'   => trim($_POST['imap_host']   ?? ''),
        'imap_port'   => (int)($_POST['imap_port']  ?? 993),
        'imap_user'   => trim($_POST['imap_user']   ?? ''),
        'imap_secure' => in_array($_POST['imap_secure'] ?? '', ['ssl','tls','none']) ? $_POST['imap_secure'] : 'ssl',
        'imap_folder' => trim($_POST['imap_folder'] ?? 'INBOX'),
    ];
    if (!$fields['email'] || !$fields['smtp_host'] || !$fields['smtp_user']) {
        jsonErr('Email, hôte SMTP et utilisateur SMTP sont requis');
    }

    // Mot de passe : ne rechiffrer que si fourni
    $smtpPass = trim($_POST['smtp_pass'] ?? '');
    $imapPass = trim($_POST['imap_pass'] ?? '');

    try {
        if ($id) {
            $row = $pdo->prepare("SELECT smtp_pass, imap_pass FROM crm_mail_accounts WHERE id=?");
            $row->execute([$id]);
            $existing = $row->fetch(PDO::FETCH_ASSOC);
            $fields['smtp_pass'] = $smtpPass ? encPass($smtpPass) : ($existing['smtp_pass'] ?? '');
            $fields['imap_pass'] = $imapPass ? encPass($imapPass) : ($existing['imap_pass'] ?? '');
            $sets = implode(',', array_map(fn($f) => "`$f`=?", array_keys($fields)));
            $pdo->prepare("UPDATE crm_mail_accounts SET $sets WHERE id=?")
                ->execute([...array_values($fields), $id]);
            jsonOk(['id' => $id]);
        } else {
            $fields['smtp_pass'] = encPass($smtpPass);
            $fields['imap_pass'] = encPass($imapPass);
            $cols = implode(',', array_map(fn($f) => "`$f`", array_keys($fields)));
            $ph   = implode(',', array_fill(0, count($fields), '?'));
            $pdo->prepare("INSERT INTO crm_mail_accounts ($cols) VALUES ($ph)")
                ->execute(array_values($fields));
            $newId = (int)$pdo->lastInsertId();
            // Prompt IA par défaut
            $pdo->prepare("INSERT INTO crm_ai_prompts (account_id, name, prompt, is_default) VALUES (?,?,?,1)")
                ->execute([$newId, 'Réponse professionnelle', "Tu es l'assistant email d'un conseiller immobilier indépendant en France. Réponds de manière professionnelle, chaleureuse et concise. Signe avec le prénom du conseiller. Ne donne jamais de conseil juridique ou financier précis. Adapte-toi au ton du prospect."]);
            jsonOk(['id' => $newId]);
        }
    } catch (Exception $e) { jsonErr($e->getMessage()); }

// ── Supprimer compte ─────────────────────────────────────────
case 'account_delete':
    $id = (int)($_POST['id'] ?? 0);
    try {
        $pdo->prepare("DELETE FROM crm_mail_accounts WHERE id=?")->execute([$id]);
        jsonOk();
    } catch (Exception $e) { jsonErr($e->getMessage()); }

// ── Tester connexion ─────────────────────────────────────────
case 'account_test':
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) jsonErr('ID compte manquant');
    try {
        $row = $pdo->prepare("SELECT * FROM crm_mail_accounts WHERE id=?");
        $row->execute([$id]);
        $acc = $row->fetch(PDO::FETCH_ASSOC);
        if (!$acc) jsonErr('Compte introuvable');

        $result = ['smtp' => false, 'imap' => false, 'smtp_msg' => '', 'imap_msg' => ''];

        // Test SMTP
        if (!loadMailer()) { jsonErr('PHPMailer non installé. Exécutez : composer require phpmailer/phpmailer'); }
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = $acc['smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $acc['smtp_user'];
            $mail->Password   = decPass($acc['smtp_pass']);
            $mail->SMTPSecure = $acc['smtp_secure'] === 'ssl' ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $acc['smtp_port'];
            $mail->SMTPDebug  = 0;
            $mail->Timeout    = 10;
            $mail->smtpConnect();
            $mail->smtpClose();
            $result['smtp']     = true;
            $result['smtp_msg'] = 'Connexion SMTP réussie';
        } catch (Exception $e) {
            $result['smtp_msg'] = 'SMTP : ' . $e->getMessage();
        }

        // Test IMAP
        if ($acc['imap_host'] && function_exists('imap_open')) {
            $flags   = $acc['imap_secure'] === 'ssl' ? '/ssl' : ($acc['imap_secure'] === 'tls' ? '/tls' : '');
            $mailbox = '{' . $acc['imap_host'] . ':' . $acc['imap_port'] . '/imap' . $flags . '/novalidate-cert}' . $acc['imap_folder'];
            $conn    = @imap_open($mailbox, $acc['imap_user'], decPass($acc['imap_pass']), 0, 1);
            if ($conn) {
                $result['imap']     = true;
                $result['imap_msg'] = 'Connexion IMAP réussie';
                imap_close($conn);
            } else {
                $result['imap_msg'] = 'IMAP : ' . imap_last_error();
            }
        } elseif (!function_exists('imap_open')) {
            $result['imap_msg'] = 'Extension IMAP PHP non activée';
        }

        jsonOk($result);
    } catch (Exception $e) { jsonErr($e->getMessage()); }

// ── Sync IMAP → DB ───────────────────────────────────────────
case 'mail_sync':
    $accountId = (int)($_POST['account_id'] ?? 0);
    if (!$accountId) jsonErr('account_id manquant');
    if (!function_exists('imap_open')) jsonErr('Extension PHP IMAP non activée (activer imap dans php.ini)');

    try {
        $row = $pdo->prepare("SELECT * FROM crm_mail_accounts WHERE id=?");
        $row->execute([$accountId]);
        $acc = $row->fetch(PDO::FETCH_ASSOC);
        if (!$acc) jsonErr('Compte introuvable');

        $flags   = $acc['imap_secure'] === 'ssl' ? '/ssl' : ($acc['imap_secure'] === 'tls' ? '/tls' : '');
        $mailbox = '{' . $acc['imap_host'] . ':' . $acc['imap_port'] . '/imap' . $flags . '/novalidate-cert}' . $acc['imap_folder'];
        $conn    = imap_open($mailbox, $acc['imap_user'], decPass($acc['imap_pass']));
        if (!$conn) jsonErr('Connexion IMAP impossible : ' . imap_last_error());

        $limit   = 50; // derniers N messages
        $total   = imap_num_msg($conn);
        $start   = max(1, $total - $limit + 1);
        $uids    = imap_fetch_overview($conn, "$start:$total", 0);
        $imported = 0;

        foreach ($uids as $overview) {
            $uid = $overview->uid ?? $overview->msgno;
            // Vérifier si déjà importé
            $exists = $pdo->prepare("SELECT id FROM crm_messages WHERE account_id=? AND imap_uid=?");
            $exists->execute([$accountId, $uid]);
            if ($exists->fetchColumn()) continue;

            $header  = imap_headerinfo($conn, $overview->msgno);
            $fromArr = $header->from[0] ?? null;
            $fromEmail = $fromArr ? ($fromArr->mailbox . '@' . $fromArr->host) : '';
            $fromName  = $fromArr ? (isset($fromArr->personal) ? imap_utf8($fromArr->personal) : '') : '';

            // Ne pas importer ses propres emails envoyés depuis IMAP (doublon avec 'out')
            if (strtolower($fromEmail) === strtolower($acc['email'])) continue;

            $subject   = imap_utf8($overview->subject ?? '(Sans objet)');
            $messageId = trim($header->message_id ?? '');
            $sentAt    = date('Y-m-d H:i:s', $overview->udate ?? time());
            $isRead    = ($overview->seen ?? 0) ? 1 : 0;

            // Corps du message
            $structure = imap_fetchstructure($conn, $overview->msgno);
            $bodyHtml  = _imapGetBody($conn, $overview->msgno, $structure, 'html');
            $bodyText  = _imapGetBody($conn, $overview->msgno, $structure, 'text');

            // Thread : chercher par message_id ou par email+sujet nettoyé
            $cleanSubject = preg_replace('/^(Re|Fwd|Fw|TR|AW)\s*:\s*/i', '', $subject);
            $thread = null;

            if ($messageId) {
                $t = $pdo->prepare("SELECT t.id FROM crm_threads t JOIN crm_messages m ON m.thread_id=t.id WHERE t.account_id=? AND m.message_id=? LIMIT 1");
                $t->execute([$accountId, $messageId]);
                $thread = $t->fetchColumn();
            }
            if (!$thread) {
                $t = $pdo->prepare("SELECT id FROM crm_threads WHERE account_id=? AND contact_email=? AND subject LIKE ? LIMIT 1");
                $t->execute([$accountId, $fromEmail, '%' . $cleanSubject . '%']);
                $thread = $t->fetchColumn();
            }
            if (!$thread) {
                // Chercher lead par email
                $leadId = null;
                $l = $pdo->prepare("SELECT id FROM leads WHERE email=? LIMIT 1");
                $l->execute([$fromEmail]);
                $leadId = $l->fetchColumn() ?: null;

                $pdo->prepare("INSERT INTO crm_threads (account_id, lead_id, subject, contact_email, contact_name, last_message_at, unread_count) VALUES (?,?,?,?,?,?,1)")
                    ->execute([$accountId, $leadId, $cleanSubject, $fromEmail, $fromName, $sentAt]);
                $thread = (int)$pdo->lastInsertId();
            } else {
                $pdo->prepare("UPDATE crm_threads SET last_message_at=?, unread_count=unread_count+1 WHERE id=?")
                    ->execute([$sentAt, $thread]);
            }

            $pdo->prepare("INSERT INTO crm_messages (thread_id, account_id, imap_uid, message_id, direction, from_email, from_name, to_email, subject, body_html, body_text, is_read, sent_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$thread, $accountId, $uid, $messageId ?: null, 'in', $fromEmail, $fromName, $acc['email'], $subject, $bodyHtml, $bodyText, $isRead, $sentAt]);
            $imported++;
        }

        imap_close($conn);
        $pdo->prepare("UPDATE crm_mail_accounts SET last_sync=NOW() WHERE id=?")->execute([$accountId]);
        jsonOk(['imported' => $imported, 'total_scanned' => $total]);
    } catch (Exception $e) { jsonErr($e->getMessage()); }

// ── Liste des threads ─────────────────────────────────────────
case 'threads_list':
    $accountId = (int)($_POST['account_id'] ?? 0);
    $status    = $_POST['status'] ?? 'open';
    $search    = trim($_POST['search'] ?? '');
    $page      = max(1, (int)($_POST['page'] ?? 1));
    $limit     = 30;
    $offset    = ($page - 1) * $limit;

    try {
        $where = ['t.account_id=?'];
        $params = [$accountId];
        if ($status !== 'all') { $where[] = 't.status=?'; $params[] = $status; }
        if ($search) { $where[] = '(t.subject LIKE ? OR t.contact_email LIKE ? OR t.contact_name LIKE ?)'; $s = "%$search%"; $params[] = $s; $params[] = $s; $params[] = $s; }
        $w = implode(' AND ', $where);

        $total = $pdo->prepare("SELECT COUNT(*) FROM crm_threads t WHERE $w");
        $total->execute($params);
        $totalCount = (int)$total->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT t.*, l.firstname AS lead_fn, l.lastname AS lead_ln,
                   (SELECT body_text FROM crm_messages WHERE thread_id=t.id ORDER BY sent_at DESC LIMIT 1) AS last_preview
            FROM crm_threads t
            LEFT JOIN leads l ON l.id = t.lead_id
            WHERE $w
            ORDER BY t.last_message_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Nettoyer preview
        foreach ($threads as &$th) {
            $th['last_preview'] = mb_substr(strip_tags($th['last_preview'] ?? ''), 0, 90);
        }

        jsonOk(['threads' => $threads, 'total' => $totalCount, 'page' => $page, 'pages' => ceil($totalCount / $limit)]);
    } catch (Exception $e) { jsonErr($e->getMessage()); }

// ── Messages d'un thread ─────────────────────────────────────
case 'thread_get':
    $threadId = (int)($_POST['thread_id'] ?? 0);
    try {
        $t = $pdo->prepare("SELECT t.*, a.email AS account_email, a.from_name AS account_name FROM crm_threads t JOIN crm_mail_accounts a ON a.id=t.account_id WHERE t.id=?");
        $t->execute([$threadId]);
        $thread = $t->fetch(PDO::FETCH_ASSOC);
        if (!$thread) jsonErr('Thread introuvable');

        $m = $pdo->prepare("SELECT m.*, GROUP_CONCAT(att.filename SEPARATOR '||') AS att_names, GROUP_CONCAT(att.id SEPARATOR '||') AS att_ids FROM crm_messages m LEFT JOIN crm_attachments att ON att.message_id=m.id WHERE m.thread_id=? GROUP BY m.id ORDER BY m.sent_at ASC");
        $m->execute([$threadId]);
        $messages = $m->fetchAll(PDO::FETCH_ASSOC);

        // Lead associé
        $lead = null;
        if ($thread['lead_id']) {
            $l = $pdo->prepare("SELECT id, firstname, lastname, email, phone, status FROM leads WHERE id=?");
            $l->execute([$thread['lead_id']]);
            $lead = $l->fetch(PDO::FETCH_ASSOC);
        }

        // Marquer tout comme lu
        $pdo->prepare("UPDATE crm_messages SET is_read=1 WHERE thread_id=? AND direction='in'")->execute([$threadId]);
        $pdo->prepare("UPDATE crm_threads SET unread_count=0 WHERE id=?")->execute([$threadId]);

        jsonOk(['thread' => $thread, 'messages' => $messages, 'lead' => $lead]);
    } catch (Exception $e) { jsonErr($e->getMessage()); }

// ── Envoyer email ─────────────────────────────────────────────
case 'mail_send':
    $accountId = (int)($_POST['account_id'] ?? 0);
    $threadId  = (int)($_POST['thread_id']  ?? 0);
    $toEmail   = trim($_POST['to_email']    ?? '');
    $subject   = trim($_POST['subject']     ?? '');
    $bodyHtml  = trim($_POST['body_html']   ?? '');
    $bodyText  = strip_tags($bodyHtml);

    if (!$accountId || !$toEmail || !$subject || !$bodyHtml) jsonErr('Champs requis manquants');
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) jsonErr('Email destinataire invalide');
    if (!loadMailer()) jsonErr('PHPMailer non installé');

    try {
        $row = $pdo->prepare("SELECT * FROM crm_mail_accounts WHERE id=?");
        $row->execute([$accountId]);
        $acc = $row->fetch(PDO::FETCH_ASSOC);
        if (!$acc) jsonErr('Compte mail introuvable');

        // PHPMailer
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->CharSet  = 'UTF-8';
        $mail->isSMTP();
        $mail->Host       = $acc['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $acc['smtp_user'];
        $mail->Password   = decPass($acc['smtp_pass']);
        $mail->SMTPSecure = $acc['smtp_secure'] === 'ssl'
            ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)$acc['smtp_port'];
        $mail->Timeout    = 15;
        $mail->setFrom($acc['email'], $acc['from_name'] ?: $acc['email']);
        $mail->addAddress($toEmail);
        $mail->Subject  = $subject;
        $mail->isHTML(true);
        $mail->Body     = $bodyHtml;
        $mail->AltBody  = $bodyText;
        $mail->send();

        $messageId = $mail->getLastMessageID();

        // Créer ou récupérer le thread
        if (!$threadId) {
            // Chercher un thread existant avec ce destinataire
            $t = $pdo->prepare("SELECT id FROM crm_threads WHERE account_id=? AND contact_email=? AND subject=? LIMIT 1");
            $t->execute([$accountId, $toEmail, $subject]);
            $threadId = (int)($t->fetchColumn() ?: 0);
            if (!$threadId) {
                $leadId = null;
                $l = $pdo->prepare("SELECT id FROM leads WHERE email=? LIMIT 1");
                $l->execute([$toEmail]);
                $leadId = $l->fetchColumn() ?: null;
                $pdo->prepare("INSERT INTO crm_threads (account_id, lead_id, subject, contact_email, last_message_at) VALUES (?,?,?,?,NOW())")
                    ->execute([$accountId, $leadId, $subject, $toEmail]);
                $threadId = (int)$pdo->lastInsertId();
            }
        }

        $pdo->prepare("INSERT INTO crm_messages (thread_id, account_id, message_id, direction, from_email, from_name, to_email, subject, body_html, body_text, is_read, sent_at) VALUES (?,?,?,?,?,?,?,?,?,?,1,NOW())")
            ->execute([$threadId, $accountId, $messageId, 'out', $acc['email'], $acc['from_name'], $toEmail, $subject, $bodyHtml, $bodyText]);

        $pdo->prepare("UPDATE crm_threads SET last_message_at=NOW() WHERE id=?")->execute([$threadId]);

        jsonOk(['thread_id' => $threadId, 'message_id' => (int)$pdo->lastInsertId()]);
    } catch (Exception $e) { jsonErr('Envoi échoué : ' . $e->getMessage()); }

// ── Supprimer message / thread ────────────────────────────────
case 'mail_delete':
    $threadId  = (int)($_POST['thread_id']  ?? 0);
    $messageId = (int)($_POST['message_id'] ?? 0);
    try {
        if ($messageId) {
            $pdo->prepare("DELETE FROM crm_messages WHERE id=?")->execute([$messageId]);
        } elseif ($threadId) {
            $pdo->prepare("DELETE FROM crm_threads WHERE id=?")->execute([$threadId]);
        }
        jsonOk();
    } catch (Exception $e) { jsonErr($e->getMessage()); }

// ── Fermer / rouvrir thread ───────────────────────────────────
case 'thread_close':
    $threadId = (int)($_POST['thread_id'] ?? 0);
    $status   = $_POST['status'] ?? 'closed';
    $status   = in_array($status, ['open','closed','spam']) ? $status : 'closed';
    try {
        $pdo->prepare("UPDATE crm_threads SET status=? WHERE id=?")->execute([$status, $threadId]);
        jsonOk();
    } catch (Exception $e) { jsonErr($e->getMessage()); }

// ── Lier thread à un lead ─────────────────────────────────────
case 'thread_link':
    $threadId = (int)($_POST['thread_id'] ?? 0);
    $leadId   = (int)($_POST['lead_id']   ?? 0);
    try {
        $pdo->prepare("UPDATE crm_threads SET lead_id=? WHERE id=?")->execute([$leadId ?: null, $threadId]);
        jsonOk();
    } catch (Exception $e) { jsonErr($e->getMessage()); }

// ── Marquer lu ────────────────────────────────────────────────
case 'mark_read':
    $threadId = (int)($_POST['thread_id'] ?? 0);
    try {
        $pdo->prepare("UPDATE crm_messages SET is_read=1 WHERE thread_id=? AND direction='in'")->execute([$threadId]);
        $pdo->prepare("UPDATE crm_threads SET unread_count=0 WHERE id=?")->execute([$threadId]);
        jsonOk();
    } catch (Exception $e) { jsonErr($e->getMessage()); }

// ── Suggestion IA ─────────────────────────────────────────────
case 'ai_suggest':
    $accountId  = (int)($_POST['account_id'] ?? 0);
    $threadId   = (int)($_POST['thread_id']  ?? 0);
    $promptIdPo = (int)($_POST['prompt_id']  ?? 0);

    try {
        // Charger contexte thread (derniers 5 messages)
        $msgs = $pdo->prepare("SELECT direction, from_name, body_text, sent_at FROM crm_messages WHERE thread_id=? ORDER BY sent_at DESC LIMIT 5");
        $msgs->execute([$threadId]);
        $history = array_reverse($msgs->fetchAll(PDO::FETCH_ASSOC));

        // Charger lead si associé
        $leadCtx = '';
        $t = $pdo->prepare("SELECT t.lead_id, t.contact_name, t.subject, a.from_name FROM crm_threads t JOIN crm_mail_accounts a ON a.id=t.account_id WHERE t.id=?");
        $t->execute([$threadId]);
        $threadRow = $t->fetch(PDO::FETCH_ASSOC);

        if ($threadRow['lead_id']) {
            $l = $pdo->prepare("SELECT firstname, lastname, email, phone, status FROM leads WHERE id=?");
            $l->execute([$threadRow['lead_id']]);
            $lead = $l->fetch(PDO::FETCH_ASSOC);
            if ($lead) {
                $leadCtx = "\n\nContexte CRM — Lead associé : {$lead['firstname']} {$lead['lastname']}, statut pipeline : {$lead['status']}, email : {$lead['email']}.";
            }
        }

        // Charger prompt IA
        $promptSql = $promptIdPo
            ? "SELECT prompt FROM crm_ai_prompts WHERE id=? AND account_id=?"
            : "SELECT prompt FROM crm_ai_prompts WHERE account_id=? AND is_default=1 LIMIT 1";
        $pStmt = $pdo->prepare($promptSql);
        $promptIdPo
            ? $pStmt->execute([$promptIdPo, $accountId])
            : $pStmt->execute([$accountId]);
        $systemPrompt = $pStmt->fetchColumn()
            ?: "Tu es un assistant email professionnel pour un conseiller immobilier. Réponds de façon concise et professionnelle.";

        // Construire conversation pour l'API
        $conversation = [];
        foreach ($history as $msg) {
            $role = $msg['direction'] === 'out' ? 'assistant' : 'user';
            $conversation[] = ['role' => $role, 'content' => mb_substr($msg['body_text'] ?? '', 0, 800)];
        }
        $conversation[] = ['role' => 'user', 'content' => "Rédige une réponse professionnelle à cet échange email. Objet : {$threadRow['subject']}.{$leadCtx}\n\nRéponds uniquement avec le corps de l'email, sans ligne d'objet ni en-tête."];

        // Appel API Anthropic (utilise la clé déjà en DB si disponible)
        $aiKey = '';
        try {
            $ks = $pdo->query("SELECT api_key FROM ai_settings WHERE provider='anthropic' LIMIT 1");
            $aiKey = $ks ? $ks->fetchColumn() : '';
        } catch(Exception $e) {}

        if (!$aiKey) jsonErr('Clé API Anthropic non configurée dans Paramètres IA');

        $response = file_get_contents('https://api.anthropic.com/v1/messages', false, stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nanthropic-version: 2023-06-01\r\nx-api-key: $aiKey\r\n",
                'content' => json_encode([
                    'model'      => 'claude-haiku-4-5-20251001',
                    'max_tokens' => 600,
                    'system'     => $systemPrompt,
                    'messages'   => $conversation,
                ]),
                'timeout' => 20,
            ]
        ]));

        if (!$response) jsonErr('Erreur appel API IA');
        $data = json_decode($response, true);
        $text = $data['content'][0]['text'] ?? '';
        if (!$text) jsonErr($data['error']['message'] ?? 'Réponse IA vide');

        jsonOk(['suggestion' => $text]);
    } catch (Exception $e) { jsonErr($e->getMessage()); }

// ── Sauvegarder prompt ────────────────────────────────────────
case 'prompt_save':
    $accountId = (int)($_POST['account_id'] ?? 0);
    $promptId  = (int)($_POST['prompt_id']  ?? 0);
    $name      = trim($_POST['name']   ?? 'Mon prompt');
    $prompt    = trim($_POST['prompt'] ?? '');
    $isDefault = (int)($_POST['is_default'] ?? 0);
    if (!$prompt) jsonErr('Prompt vide');
    try {
        if ($isDefault) {
            $pdo->prepare("UPDATE crm_ai_prompts SET is_default=0 WHERE account_id=?")->execute([$accountId]);
        }
        if ($promptId) {
            $pdo->prepare("UPDATE crm_ai_prompts SET name=?, prompt=?, is_default=? WHERE id=? AND account_id=?")
                ->execute([$name, $prompt, $isDefault, $promptId, $accountId]);
        } else {
            $pdo->prepare("INSERT INTO crm_ai_prompts (account_id, name, prompt, is_default) VALUES (?,?,?,?)")
                ->execute([$accountId, $name, $prompt, $isDefault]);
            $promptId = (int)$pdo->lastInsertId();
        }
        jsonOk(['prompt_id' => $promptId]);
    } catch (Exception $e) { jsonErr($e->getMessage()); }

default:
    jsonErr('Action inconnue : ' . $action);
}

// ── Helper IMAP : extraire corps du message ───────────────────
function _imapGetBody($conn, int $msgno, $structure, string $type): string {
    $mimeType = strtolower($type) === 'html' ? 'text/html' : 'text/plain';
    $result   = '';

    if ($structure->type === 0) {
        // Message simple
        $subtype = strtolower($structure->subtype ?? '');
        if (($mimeType === 'text/html' && $subtype === 'html') ||
            ($mimeType === 'text/plain' && $subtype === 'plain')) {
            $body = imap_fetchbody($conn, $msgno, 1);
            $enc  = $structure->encoding ?? 0;
            $result = _imapDecode($body, $enc);
        }
    } elseif ($structure->type === 1) {
        // Multipart
        foreach ($structure->parts as $i => $part) {
            $subtype = strtolower($part->subtype ?? '');
            if (($mimeType === 'text/html' && $subtype === 'html') ||
                ($mimeType === 'text/plain' && $subtype === 'plain')) {
                $body = imap_fetchbody($conn, $msgno, $i + 1);
                $result = _imapDecode($body, $part->encoding ?? 0);
                break;
            }
        }
    }

    // Charset
    if ($result && isset($structure->parameters)) {
        foreach ($structure->parameters as $p) {
            if (strtolower($p->attribute) === 'charset' && strtoupper($p->value) !== 'UTF-8') {
                $result = mb_convert_encoding($result, 'UTF-8', $p->value);
            }
        }
    }
    return $result;
}

function _imapDecode(string $body, int $encoding): string {
    return match($encoding) {
        3 => base64_decode($body),
        4 => quoted_printable_decode($body),
        default => $body,
    };
}