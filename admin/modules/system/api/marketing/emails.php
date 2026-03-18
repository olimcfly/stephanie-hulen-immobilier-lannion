<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  /admin/api/marketing/emails.php
 *  Séquences, templates, file d'attente, envoi, stats
 *
 *  ?route=marketing.emails&action=stats
 *  ?route=marketing.emails&action=sequences          (GET list)
 *  ?route=marketing.emails&action=sequences-save      (POST)
 *  ?route=marketing.emails&action=sequences-delete    (POST)
 *  ?route=marketing.emails&action=sequences-toggle    (POST)
 *  ?route=marketing.emails&action=templates           (GET list)
 *  ?route=marketing.emails&action=templates-save      (POST)
 *  ?route=marketing.emails&action=templates-delete    (POST)
 *  ?route=marketing.emails&action=queue               (GET list)
 *  ?route=marketing.emails&action=queue-add           (POST)
 *  ?route=marketing.emails&action=queue-cancel        (POST)
 *  ?route=marketing.emails&action=queue-retry         (POST)
 *  ?route=marketing.emails&action=send                (POST)
 *  ?route=marketing.emails&action=process-queue       (cron)
 * ══════════════════════════════════════════════════════════════
 */

$pdo    = $ctx['pdo'];
$action = $ctx['action'];
$method = $ctx['method'];
$p      = $ctx['params'];

// ─── stats ───
if ($action === 'stats') {
    $s = [];
    $qs = [
        'sequences_total'  => "SELECT COUNT(*) FROM email_sequences",
        'sequences_active' => "SELECT COUNT(*) FROM email_sequences WHERE status='active'",
        'templates_total'  => "SELECT COUNT(*) FROM email_templates",
        'queue_pending'    => "SELECT COUNT(*) FROM email_queue WHERE status='pending'",
        'queue_sent'       => "SELECT COUNT(*) FROM email_queue WHERE status='sent'",
        'queue_failed'     => "SELECT COUNT(*) FROM email_queue WHERE status='failed'",
        'logs_total'       => "SELECT COUNT(*) FROM email_logs",
        'logs_opened'      => "SELECT COUNT(*) FROM email_logs WHERE event='opened'",
        'logs_clicked'     => "SELECT COUNT(*) FROM email_logs WHERE event='clicked'",
        'logs_bounced'     => "SELECT COUNT(*) FROM email_logs WHERE event='bounced'",
    ];
    foreach ($qs as $k => $sql) {
        try { $s[$k] = (int)$pdo->query($sql)->fetchColumn(); } catch (Exception $e) { $s[$k] = 0; }
    }
    $s['open_rate'] = $s['logs_total'] > 0 ? round(($s['logs_opened'] / $s['logs_total']) * 100, 1) : 0;
    return ['success' => true, 'stats' => $s];
}

// ─── sequences (list) ───
if ($action === 'sequences' && $method === 'GET') {
    $sql = "SELECT * FROM email_sequences";
    $params = [];
    if (!empty($p['status'])) { $sql .= " WHERE status = ?"; $params[] = $p['status']; }
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    return ['success' => true, 'sequences' => $stmt->fetchAll()];
}

// ─── sequences-get ───
if ($action === 'sequences-get') {
    $id = (int)($p['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM email_sequences WHERE id = ?");
    $stmt->execute([$id]);
    $seq = $stmt->fetch();
    if (!$seq) return ['success' => false, 'error' => 'Séquence non trouvée', '_http_code' => 404];
    $seq['steps'] = json_decode($seq['steps'] ?? '[]', true);
    return ['success' => true, 'sequence' => $seq];
}

// ─── sequences-save ───
if ($action === 'sequences-save' && $method === 'POST') {
    $id = (int)($p['id'] ?? 0);
    $fields = [
        'name'          => $p['name'] ?? 'Nouvelle séquence',
        'slug'          => $p['slug'] ?? slugify($p['name'] ?? 'nouvelle-sequence'),
        'description'   => $p['description'] ?? '',
        'trigger_type'  => $p['trigger_type'] ?? 'manual',
        'trigger_value' => $p['trigger_value'] ?? null,
        'status'        => $p['status'] ?? 'draft',
        'steps'         => is_array($p['steps'] ?? null) ? json_encode($p['steps']) : ($p['steps'] ?? '[]'),
    ];
    if ($id > 0) {
        $sets = []; $vals = [];
        foreach ($fields as $c => $v) { $sets[] = "`{$c}`=?"; $vals[] = $v; }
        $vals[] = $id;
        $pdo->prepare("UPDATE email_sequences SET " . implode(',', $sets) . " WHERE id=?")->execute($vals);
        return ['success' => true, 'message' => 'Mise à jour OK', 'id' => $id];
    }
    $cols = array_keys($fields);
    $pdo->prepare("INSERT INTO email_sequences (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")")->execute(array_values($fields));
    return ['success' => true, 'message' => 'Créée', 'id' => (int)$pdo->lastInsertId()];
}

// ─── sequences-delete ───
if ($action === 'sequences-delete' && $method === 'POST') {
    $pdo->prepare("DELETE FROM email_sequences WHERE id=?")->execute([(int)($p['id'] ?? 0)]);
    return ['success' => true, 'message' => 'Supprimée'];
}

// ─── sequences-toggle ───
if ($action === 'sequences-toggle' && $method === 'POST') {
    $id = (int)($p['id'] ?? 0);
    $cur = $pdo->prepare("SELECT status FROM email_sequences WHERE id=?"); $cur->execute([$id]);
    $new = $cur->fetchColumn() === 'active' ? 'paused' : 'active';
    $pdo->prepare("UPDATE email_sequences SET status=? WHERE id=?")->execute([$new, $id]);
    return ['success' => true, 'new_status' => $new];
}

// ─── templates (list) ───
if ($action === 'templates' && $method === 'GET') {
    $sql = "SELECT * FROM email_templates";
    $params = [];
    if (!empty($p['category'])) { $sql .= " WHERE category=?"; $params[] = $p['category']; }
    $sql .= " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    return ['success' => true, 'templates' => $stmt->fetchAll()];
}

// ─── templates-save ───
if ($action === 'templates-save' && $method === 'POST') {
    $id = (int)($p['id'] ?? 0);
    $fields = [
        'name'      => $p['name'] ?? 'Nouveau template',
        'slug'      => $p['slug'] ?? slugify($p['name'] ?? 'nouveau-template'),
        'category'  => $p['category'] ?? 'custom',
        'subject'   => $p['subject'] ?? '',
        'body_html' => $p['body_html'] ?? '',
        'body_text' => $p['body_text'] ?? strip_tags($p['body_html'] ?? ''),
        'variables' => is_array($p['variables'] ?? null) ? json_encode($p['variables']) : ($p['variables'] ?? null),
        'status'    => $p['status'] ?? 'active',
    ];
    if ($id > 0) {
        $sets = []; $vals = [];
        foreach ($fields as $c => $v) { $sets[] = "`{$c}`=?"; $vals[] = $v; }
        $vals[] = $id;
        $pdo->prepare("UPDATE email_templates SET " . implode(',', $sets) . " WHERE id=?")->execute($vals);
        return ['success' => true, 'message' => 'Mise à jour OK', 'id' => $id];
    }
    $cols = array_keys($fields);
    $pdo->prepare("INSERT INTO email_templates (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")")->execute(array_values($fields));
    return ['success' => true, 'message' => 'Créé', 'id' => (int)$pdo->lastInsertId()];
}

// ─── templates-delete ───
if ($action === 'templates-delete' && $method === 'POST') {
    $pdo->prepare("DELETE FROM email_templates WHERE id=?")->execute([(int)($p['id'] ?? 0)]);
    return ['success' => true, 'message' => 'Supprimé'];
}

// ─── queue (list) ───
if ($action === 'queue' && $method === 'GET') {
    $lim = min((int)($p['limit'] ?? 50), 200);
    $sql = "SELECT q.*, s.name AS sequence_name FROM email_queue q LEFT JOIN email_sequences s ON q.sequence_id=s.id";
    $params = [];
    if (!empty($p['status'])) { $sql .= " WHERE q.status=?"; $params[] = $p['status']; }
    $sql .= " ORDER BY q.priority ASC, q.scheduled_at ASC LIMIT {$lim}";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    return ['success' => true, 'queue' => $stmt->fetchAll()];
}

// ─── queue-add ───
if ($action === 'queue-add' && $method === 'POST') {
    if (empty($p['to_email'])) return ['success' => false, 'error' => 'Destinataire requis'];
    $fields = [
        'sequence_id'  => $p['sequence_id'] ?? null,
        'template_id'  => $p['template_id'] ?? null,
        'contact_id'   => $p['contact_id'] ?? null,
        'lead_id'      => $p['lead_id'] ?? null,
        'to_email'     => $p['to_email'],
        'to_name'      => $p['to_name'] ?? '',
        'subject'      => $p['subject'] ?? '',
        'body_html'    => $p['body_html'] ?? '',
        'priority'     => (int)($p['priority'] ?? 5),
        'status'       => 'pending',
        'scheduled_at' => $p['scheduled_at'] ?? null,
    ];
    $cols = array_keys($fields);
    $pdo->prepare("INSERT INTO email_queue (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")")->execute(array_values($fields));
    return ['success' => true, 'message' => 'Ajouté à la file', 'id' => (int)$pdo->lastInsertId()];
}

// ─── queue-cancel ───
if ($action === 'queue-cancel' && $method === 'POST') {
    $pdo->prepare("UPDATE email_queue SET status='cancelled' WHERE id=? AND status='pending'")->execute([(int)($p['id'] ?? 0)]);
    return ['success' => true, 'message' => 'Annulé'];
}

// ─── queue-retry ───
if ($action === 'queue-retry' && $method === 'POST') {
    $pdo->prepare("UPDATE email_queue SET status='pending', attempts=0, error_message=NULL WHERE id=? AND status='failed'")->execute([(int)($p['id'] ?? 0)]);
    return ['success' => true, 'message' => 'Remis en file'];
}

// ─── send (envoi immédiat) ───
if ($action === 'send' && $method === 'POST') {
    $to      = $p['to_email'] ?? '';
    $toName  = $p['to_name'] ?? '';
    $subject = $p['subject'] ?? '';
    $html    = $p['body_html'] ?? '';
    if (!$to || !$subject) return ['success' => false, 'error' => 'Destinataire et sujet requis'];

    // Load template if provided
    $tplId = (int)($p['template_id'] ?? 0);
    if ($tplId > 0) {
        $tpl = $pdo->prepare("SELECT * FROM email_templates WHERE id=?"); $tpl->execute([$tplId]);
        $tpl = $tpl->fetch();
        if ($tpl) {
            if (!$subject) $subject = $tpl['subject'];
            if (!$html)    $html = $tpl['body_html'];
            $pdo->prepare("UPDATE email_templates SET usage_count=usage_count+1 WHERE id=?")->execute([$tplId]);
        }
    }

    // Variables replacement
    foreach (($p['variables'] ?? []) as $k => $v) {
        $subject = str_replace('{{' . $k . '}}', $v, $subject);
        $html    = str_replace('{{' . $k . '}}', $v, $html);
    }

    // Send
    $sent = false; $error = '';
    $phpmailer = __DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php';
    if (file_exists($phpmailer)) {
        require_once $phpmailer;
        require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/SMTP.php';
        require_once __DIR__ . '/../../vendor/phpmailer/phpmailer/src/Exception.php';
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $cfg = loadEmailSmtp($pdo);
            $host = $cfg['smtp_host'] ?? $cfg['SMTP_HOST'] ?? '';
            if ($host) {
                $mail->isSMTP();
                $mail->Host       = $host;
                $mail->SMTPAuth   = true;
                $mail->Username   = $cfg['smtp_user'] ?? $cfg['SMTP_USER'] ?? '';
                $mail->Password   = $cfg['smtp_pass'] ?? $cfg['SMTP_PASS'] ?? '';
                $mail->SMTPSecure = $cfg['smtp_secure'] ?? 'tls';
                $mail->Port       = (int)($cfg['smtp_port'] ?? $cfg['SMTP_PORT'] ?? 587);
            }
            $from     = $cfg['smtp_from'] ?? $cfg['SMTP_FROM'] ?? $cfg['email_from'] ?? 'noreply@example.com';
            $fromName = $cfg['smtp_from_name'] ?? $cfg['SMTP_FROM_NAME'] ?? 'IMMO LOCAL+';
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to, $toName);
            $mail->isHTML(true); $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject; $mail->Body = $html; $mail->AltBody = strip_tags($html);
            $mail->send(); $sent = true;
        } catch (Exception $e) { $error = $e->getMessage(); }
    } elseif (function_exists('mail')) {
        $h = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n";
        $sent = @mail($to, $subject, $html, $h);
        if (!$sent) $error = 'mail() returned false';
    } else {
        $error = 'Aucun moteur disponible';
    }

    // Log
    try { $pdo->prepare("INSERT INTO email_logs (to_email,subject,event) VALUES (?,?,?)")->execute([$to, $subject, $sent?'sent':'failed']); } catch(Exception $e){}
    try { $pdo->prepare("INSERT INTO crm_emails (contact_id,lead_id,direction,from_email,to_email,to_name,subject,body_html,folder,sent_at,created_at) VALUES (?,?,'outbound',?,?,?,?,?,'sent',NOW(),NOW())")
        ->execute([$p['contact_id']??null, $p['lead_id']??null, $from??'', $to, $toName, $subject, $html]); } catch(Exception $e){}

    return $sent
        ? ['success' => true, 'message' => "Envoyé à {$to}"]
        : ['success' => false, 'error' => "Échec: {$error}"];
}

// ─── process-queue (cron) ───
if ($action === 'process-queue') {
    $limit = min((int)($p['limit'] ?? 10), 50);
    $items = $pdo->prepare("SELECT * FROM email_queue WHERE status='pending' AND (scheduled_at IS NULL OR scheduled_at<=NOW()) AND attempts<max_attempts ORDER BY priority ASC, created_at ASC LIMIT ?");
    $items->execute([$limit]); $items = $items->fetchAll();
    $sent = 0; $failed = 0;
    foreach ($items as $item) {
        $pdo->prepare("UPDATE email_queue SET status='sending', attempts=attempts+1 WHERE id=?")->execute([$item['id']]);
        $ok = false;
        if (function_exists('mail')) {
            $ok = @mail($item['to_email'], $item['subject'], $item['body_html'], "MIME-Version: 1.0\r\nContent-Type: text/html; charset=UTF-8\r\n");
        }
        if ($ok) {
            $pdo->prepare("UPDATE email_queue SET status='sent', sent_at=NOW() WHERE id=?")->execute([$item['id']]);
            try { $pdo->prepare("INSERT INTO email_logs (queue_id,sequence_id,to_email,subject,event) VALUES (?,?,?,?,'sent')")->execute([$item['id'],$item['sequence_id'],$item['to_email'],$item['subject']]); } catch(Exception $e){}
            $sent++;
        } else {
            $ns = ($item['attempts']+1 >= $item['max_attempts']) ? 'failed' : 'pending';
            $pdo->prepare("UPDATE email_queue SET status=?, error_message='send failed' WHERE id=?")->execute([$ns, $item['id']]);
            $failed++;
        }
    }
    return ['success' => true, 'processed' => count($items), 'sent' => $sent, 'failed' => $failed];
}

// ─── Inconnu ───
return ['success' => false, 'error' => "Action '{$action}' non reconnue", '_http_code' => 404,
    'actions' => ['stats','sequences','sequences-save','sequences-delete','sequences-toggle','templates','templates-save','templates-delete','queue','queue-add','queue-cancel','queue-retry','send','process-queue']];

// ── Helpers ──
function slugify(string $s): string { return strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $s), '-')); }
function loadEmailSmtp(PDO $pdo): array {
    $c = [];
    try { $s=$pdo->query("SELECT setting_key,setting_value FROM settings WHERE setting_key LIKE 'smtp%' OR setting_key LIKE 'email%'"); while($r=$s->fetch()) $c[$r['setting_key']]=$r['setting_value']; } catch(Exception $e){}
    $f=__DIR__.'/../../config/smtp.php'; if(file_exists($f)){$fc=include $f;if(is_array($fc))$c=array_merge($c,$fc);}
    return $c;
}
