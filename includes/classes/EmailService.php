<?php
/**
 * EmailService — includes/classes/EmailService.php
 * Service centralise pour l'envoi SMTP et la lecture IMAP
 */

class EmailService
{
    private array $config;
    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
        $configFile = dirname(__DIR__, 2) . '/config/smtp.php';
        $this->config = file_exists($configFile) ? (include $configFile) : [];
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    // ================================================================
    // ENVOI SMTP
    // ================================================================

    /**
     * Envoyer un email via SMTP SSL
     *
     * @param string $to          Destinataire
     * @param string $subject     Objet
     * @param string $htmlBody    Corps HTML
     * @param array  $options     [from_email, from_name, reply_to, cc, bcc, headers, contact_id, lead_id]
     * @return array              ['success'=>bool, 'error'=>string|null]
     */
    public function sendEmail(string $to, string $subject, string $htmlBody, array $options = []): array
    {
        $fromEmail = $options['from_email'] ?? $this->config['smtp_from'] ?? $this->config['smtp_user'] ?? '';
        $fromName  = $options['from_name']  ?? $this->config['smtp_from_name'] ?? 'Eduardo De Sul Immobilier';
        $replyTo   = $options['reply_to']   ?? $fromEmail;

        $host = $this->config['smtp_host'] ?? '';
        $port = (int)($this->config['smtp_port'] ?? 465);
        $user = $this->config['smtp_user'] ?? '';
        $pass = $this->config['smtp_pass'] ?? '';

        if (empty($host) || empty($user)) {
            return ['success' => false, 'error' => 'Configuration SMTP manquante'];
        }

        $result = $this->smtpSend($host, $port, $user, $pass, $fromEmail, $fromName, $replyTo, $to, $subject, $htmlBody, $options);

        // Log dans crm_emails si PDO disponible
        if ($this->pdo) {
            $this->logEmail([
                'direction'  => 'outbound',
                'from_email' => $fromEmail,
                'from_name'  => $fromName,
                'to_email'   => $to,
                'to_name'    => $options['to_name'] ?? '',
                'subject'    => $subject,
                'body_html'  => $htmlBody,
                'folder'     => 'sent',
                'contact_id' => $options['contact_id'] ?? null,
                'lead_id'    => $options['lead_id'] ?? null,
                'is_read'    => 1,
                'sent_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        return $result;
    }

    private function smtpSend(string $host, int $port, string $user, string $pass, string $fromEmail, string $fromName, string $replyTo, string $to, string $subject, string $htmlBody, array $options = []): array
    {
        try {
            $prefix = ($port === 465) ? 'ssl://' : '';
            $socket = @fsockopen($prefix . $host, $port, $errno, $errstr, 15);

            if (!$socket) {
                return ['success' => false, 'error' => "Connexion SMTP echouee: {$errstr}"];
            }

            stream_set_timeout($socket, 15);

            $readLine = function () use ($socket) {
                $response = '';
                while ($line = fgets($socket, 515)) {
                    $response .= $line;
                    if (substr($line, 3, 1) === ' ') break;
                }
                return trim($response);
            };

            $sendCmd = function ($cmd) use ($socket, $readLine) {
                fwrite($socket, $cmd . "\r\n");
                return $readLine();
            };

            // Banner
            $readLine();

            // EHLO
            $sendCmd("EHLO eduardo-desul-immobilier.fr");

            // STARTTLS si port 587
            if ($port === 587) {
                $sendCmd("STARTTLS");
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
                $sendCmd("EHLO eduardo-desul-immobilier.fr");
            }

            // AUTH LOGIN
            $sendCmd("AUTH LOGIN");
            $sendCmd(base64_encode($user));
            $response = $sendCmd(base64_encode($pass));

            if (substr($response, 0, 3) !== '235') {
                fclose($socket);
                return ['success' => false, 'error' => 'Auth SMTP echouee: ' . $response];
            }

            // MAIL FROM
            $sendCmd("MAIL FROM:<{$fromEmail}>");

            // RCPT TO
            $response = $sendCmd("RCPT TO:<{$to}>");
            if (substr($response, 0, 3) !== '250' && substr($response, 0, 3) !== '251') {
                fclose($socket);
                return ['success' => false, 'error' => 'Destinataire rejete: ' . $response];
            }

            // CC
            if (!empty($options['cc'])) {
                foreach ((array)$options['cc'] as $cc) {
                    $sendCmd("RCPT TO:<{$cc}>");
                }
            }

            // DATA
            $sendCmd("DATA");

            $messageId = '<' . uniqid('msg_', true) . '@eduardo-desul-immobilier.fr>';

            // Headers
            $message = "Message-ID: {$messageId}\r\n";
            $message .= "From: {$fromName} <{$fromEmail}>\r\n";
            $message .= "To: {$to}\r\n";
            if (!empty($options['cc'])) {
                $message .= "Cc: " . implode(', ', (array)$options['cc']) . "\r\n";
            }
            $message .= "Reply-To: {$replyTo}\r\n";
            $message .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $message .= "Date: " . date('r') . "\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: text/html; charset=utf-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "X-Mailer: EcosystemeImmo-CRM/1.0\r\n";

            if (!empty($options['in_reply_to'])) {
                $message .= "In-Reply-To: {$options['in_reply_to']}\r\n";
                $message .= "References: {$options['in_reply_to']}\r\n";
            }

            $message .= "\r\n";
            $message .= chunk_split(base64_encode($htmlBody));
            $message .= "\r\n.\r\n";

            fwrite($socket, $message);
            $response = $readLine();

            $sendCmd("QUIT");
            fclose($socket);

            if (substr($response, 0, 3) === '250') {
                return ['success' => true, 'message_id' => $messageId];
            }

            return ['success' => false, 'error' => 'Envoi rejete: ' . $response];

        } catch (Exception $e) {
            if (isset($socket) && is_resource($socket)) fclose($socket);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ================================================================
    // IMAP - Lecture emails
    // ================================================================

    /**
     * Connexion IMAP
     * @return resource|false
     */
    private function imapConnect(string $folder = 'INBOX')
    {
        $host    = $this->config['imap_host'] ?? '';
        $port    = (int)($this->config['imap_port'] ?? 993);
        $user    = $this->config['imap_user'] ?? '';
        $pass    = $this->config['imap_pass'] ?? '';
        $secure  = $this->config['imap_secure'] ?? 'ssl';

        if (empty($host) || empty($user)) return false;

        $mailbox = "{{$host}:{$port}/imap/{$secure}}{$folder}";
        return @imap_open($mailbox, $user, $pass, 0, 1);
    }

    /**
     * Recuperer la liste des emails
     */
    public function fetchInbox(string $folder = 'INBOX', int $limit = 50, int $offset = 0): array
    {
        $imap = $this->imapConnect($folder);
        if (!$imap) {
            return ['success' => false, 'error' => 'Connexion IMAP echouee: ' . imap_last_error()];
        }

        $info = imap_check($imap);
        $total = $info->Nmsgs;

        if ($total === 0) {
            imap_close($imap);
            return ['success' => true, 'emails' => [], 'total' => 0];
        }

        $start = max(1, $total - $offset - $limit + 1);
        $end   = max(1, $total - $offset);

        $emails = [];
        $overview = imap_fetch_overview($imap, "{$start}:{$end}", 0);

        if ($overview) {
            $overview = array_reverse($overview);
            foreach ($overview as $msg) {
                $emails[] = [
                    'uid'      => $msg->uid ?? imap_uid($imap, $msg->msgno),
                    'msgno'    => $msg->msgno,
                    'from'     => isset($msg->from) ? $this->decodeHeader($msg->from) : '',
                    'to'       => isset($msg->to) ? $this->decodeHeader($msg->to) : '',
                    'subject'  => isset($msg->subject) ? $this->decodeHeader($msg->subject) : '(sans objet)',
                    'date'     => $msg->date ?? '',
                    'is_read'  => isset($msg->seen) ? (int)$msg->seen : 0,
                    'is_starred' => isset($msg->flagged) ? (int)$msg->flagged : 0,
                    'size'     => $msg->size ?? 0,
                ];
            }
        }

        imap_close($imap);
        return ['success' => true, 'emails' => $emails, 'total' => $total];
    }

    /**
     * Lire un email complet par UID
     */
    public function getMessage(int $uid, string $folder = 'INBOX'): array
    {
        $imap = $this->imapConnect($folder);
        if (!$imap) {
            return ['success' => false, 'error' => 'Connexion IMAP echouee'];
        }

        $msgno = imap_msgno($imap, $uid);
        if ($msgno === 0) {
            imap_close($imap);
            return ['success' => false, 'error' => 'Message introuvable'];
        }

        $header  = imap_headerinfo($imap, $msgno);
        $structure = imap_fetchstructure($imap, $msgno);

        $body = $this->getBody($imap, $msgno, $structure);

        // Marquer comme lu
        imap_setflag_full($imap, (string)$uid, '\\Seen', ST_UID);

        $fromAddr = '';
        $fromName = '';
        if (!empty($header->from)) {
            $fromAddr = $header->from[0]->mailbox . '@' . $header->from[0]->host;
            $fromName = isset($header->from[0]->personal) ? $this->decodeHeader($header->from[0]->personal) : '';
        }

        $toAddr = '';
        $toName = '';
        if (!empty($header->to)) {
            $toAddr = $header->to[0]->mailbox . '@' . $header->to[0]->host;
            $toName = isset($header->to[0]->personal) ? $this->decodeHeader($header->to[0]->personal) : '';
        }

        $messageId = isset($header->message_id) ? trim($header->message_id) : '';
        $inReplyTo = isset($header->in_reply_to) ? trim($header->in_reply_to) : '';

        $email = [
            'uid'         => $uid,
            'message_id'  => $messageId,
            'in_reply_to' => $inReplyTo,
            'from_email'  => $fromAddr,
            'from_name'   => $fromName,
            'to_email'    => $toAddr,
            'to_name'     => $toName,
            'subject'     => isset($header->subject) ? $this->decodeHeader($header->subject) : '(sans objet)',
            'date'        => date('Y-m-d H:i:s', strtotime($header->date ?? 'now')),
            'body_html'   => $body['html'] ?? '',
            'body_text'   => $body['text'] ?? '',
            'is_read'     => 1,
        ];

        imap_close($imap);
        return ['success' => true, 'email' => $email];
    }

    /**
     * Marquer comme lu/non lu
     */
    public function markAsRead(int $uid, string $folder = 'INBOX'): bool
    {
        $imap = $this->imapConnect($folder);
        if (!$imap) return false;
        imap_setflag_full($imap, (string)$uid, '\\Seen', ST_UID);
        imap_close($imap);
        return true;
    }

    public function markAsUnread(int $uid, string $folder = 'INBOX'): bool
    {
        $imap = $this->imapConnect($folder);
        if (!$imap) return false;
        imap_clearflag_full($imap, (string)$uid, '\\Seen', ST_UID);
        imap_close($imap);
        return true;
    }

    public function starMessage(int $uid, bool $star = true, string $folder = 'INBOX'): bool
    {
        $imap = $this->imapConnect($folder);
        if (!$imap) return false;
        if ($star) {
            imap_setflag_full($imap, (string)$uid, '\\Flagged', ST_UID);
        } else {
            imap_clearflag_full($imap, (string)$uid, '\\Flagged', ST_UID);
        }
        imap_close($imap);
        return true;
    }

    public function deleteMessage(int $uid, string $folder = 'INBOX'): bool
    {
        $imap = $this->imapConnect($folder);
        if (!$imap) return false;
        imap_delete($imap, (string)$uid, FT_UID);
        imap_expunge($imap);
        imap_close($imap);
        return true;
    }

    /**
     * Lister les dossiers IMAP
     */
    public function listFolders(): array
    {
        $host   = $this->config['imap_host'] ?? '';
        $port   = (int)($this->config['imap_port'] ?? 993);
        $secure = $this->config['imap_secure'] ?? 'ssl';

        $imap = $this->imapConnect('INBOX');
        if (!$imap) return [];

        $ref = "{{$host}:{$port}/imap/{$secure}}";
        $folders = imap_list($imap, $ref, '*');
        imap_close($imap);

        if (!$folders) return ['INBOX'];

        return array_map(function ($f) use ($ref) {
            return str_replace($ref, '', $f);
        }, $folders);
    }

    /**
     * Synchroniser les emails IMAP vers crm_emails
     */
    public function syncToDatabase(string $folder = 'INBOX', int $limit = 50): array
    {
        if (!$this->pdo) {
            return ['success' => false, 'error' => 'PDO non disponible'];
        }

        $this->ensureTable();

        $result = $this->fetchInbox($folder, $limit);
        if (!$result['success']) return $result;

        $synced = 0;
        $skipped = 0;

        foreach ($result['emails'] as $emailSummary) {
            // Verifier si deja en base par UID
            $stmt = $this->pdo->prepare("SELECT id FROM crm_emails WHERE message_id = ? LIMIT 1");
            $uid = $emailSummary['uid'];

            // Recuperer le message complet
            $full = $this->getMessage($uid, $folder);
            if (!$full['success']) {
                $skipped++;
                continue;
            }

            $email = $full['email'];
            $messageId = $email['message_id'];

            if (!empty($messageId)) {
                $stmt->execute([$messageId]);
                if ($stmt->fetch()) {
                    $skipped++;
                    continue;
                }
            }

            $this->logEmail([
                'direction'  => 'inbound',
                'from_email' => $email['from_email'],
                'from_name'  => $email['from_name'],
                'to_email'   => $email['to_email'],
                'to_name'    => $email['to_name'],
                'subject'    => $email['subject'],
                'body_html'  => $email['body_html'],
                'body_text'  => $email['body_text'],
                'folder'     => 'inbox',
                'message_id' => $messageId,
                'in_reply_to' => $email['in_reply_to'],
                'is_read'    => $emailSummary['is_read'],
                'is_starred' => $emailSummary['is_starred'],
                'sent_at'    => $email['date'],
            ]);
            $synced++;
        }

        return ['success' => true, 'synced' => $synced, 'skipped' => $skipped, 'total' => $result['total']];
    }

    // ================================================================
    // HELPERS
    // ================================================================

    private function decodeHeader(string $text): string
    {
        $elements = imap_mime_header_decode($text);
        $decoded = '';
        foreach ($elements as $el) {
            $decoded .= $el->text;
        }
        return $decoded;
    }

    private function getBody($imap, int $msgno, $structure): array
    {
        $body = ['html' => '', 'text' => ''];

        if (empty($structure->parts)) {
            // Simple message
            $content = imap_fetchbody($imap, $msgno, '1');
            $content = $this->decodeBody($content, $structure->encoding ?? 0);

            if (strtolower($structure->subtype ?? '') === 'html') {
                $body['html'] = $content;
                $body['text'] = strip_tags($content);
            } else {
                $body['text'] = $content;
            }
            return $body;
        }

        // Multipart
        foreach ($structure->parts as $i => $part) {
            $partNum = (string)($i + 1);
            $content = imap_fetchbody($imap, $msgno, $partNum);
            $content = $this->decodeBody($content, $part->encoding ?? 0);

            if (strtolower($part->subtype ?? '') === 'html') {
                $body['html'] = $content;
            } elseif (strtolower($part->subtype ?? '') === 'plain') {
                $body['text'] = $content;
            }

            // Sous-parties (multipart/alternative dans multipart/mixed)
            if (!empty($part->parts)) {
                foreach ($part->parts as $j => $subpart) {
                    $subPartNum = ($i + 1) . '.' . ($j + 1);
                    $subContent = imap_fetchbody($imap, $msgno, $subPartNum);
                    $subContent = $this->decodeBody($subContent, $subpart->encoding ?? 0);

                    if (strtolower($subpart->subtype ?? '') === 'html') {
                        $body['html'] = $subContent;
                    } elseif (strtolower($subpart->subtype ?? '') === 'plain') {
                        $body['text'] = $subContent;
                    }
                }
            }
        }

        if (empty($body['html']) && !empty($body['text'])) {
            $body['html'] = nl2br(htmlspecialchars($body['text']));
        }

        return $body;
    }

    private function decodeBody(string $body, int $encoding): string
    {
        switch ($encoding) {
            case 0: // 7BIT
            case 1: // 8BIT
                return $body;
            case 2: // BINARY
                return $body;
            case 3: // BASE64
                return base64_decode($body);
            case 4: // QUOTED-PRINTABLE
                return quoted_printable_decode($body);
            default:
                return $body;
        }
    }

    private function logEmail(array $data): void
    {
        if (!$this->pdo) return;

        try {
            $this->ensureTable();

            $fields = [
                'direction', 'from_email', 'from_name', 'to_email', 'to_name',
                'subject', 'body_html', 'body_text', 'folder', 'message_id',
                'in_reply_to', 'thread_id', 'is_read', 'is_starred',
                'contact_id', 'lead_id', 'sent_at',
            ];

            $cols = [];
            $placeholders = [];
            $values = [];
            foreach ($fields as $f) {
                if (array_key_exists($f, $data)) {
                    $cols[] = "`{$f}`";
                    $placeholders[] = '?';
                    $values[] = $data[$f];
                }
            }

            if (empty($cols)) return;

            $sql = "INSERT INTO crm_emails (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
            $this->pdo->prepare($sql)->execute($values);

        } catch (Exception $e) {
            error_log("EmailService::logEmail error: " . $e->getMessage());
        }
    }

    private function ensureTable(): void
    {
        if (!$this->pdo) return;

        try {
            $this->pdo->query("SELECT 1 FROM crm_emails LIMIT 1");
        } catch (Exception $e) {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `crm_emails` (
                `id` INT AUTO_INCREMENT PRIMARY KEY, `contact_id` INT, `lead_id` INT,
                `direction` ENUM('inbound','outbound') DEFAULT 'inbound',
                `from_email` VARCHAR(255) NOT NULL DEFAULT '', `from_name` VARCHAR(255) DEFAULT '',
                `to_email` VARCHAR(255) NOT NULL DEFAULT '', `to_name` VARCHAR(255) DEFAULT '',
                `subject` VARCHAR(500) DEFAULT '', `body_html` LONGTEXT, `body_text` TEXT,
                `is_read` TINYINT(1) DEFAULT 0, `is_starred` TINYINT(1) DEFAULT 0,
                `folder` ENUM('inbox','sent','draft','trash','archive') DEFAULT 'inbox',
                `labels` JSON, `attachments` JSON, `message_id` VARCHAR(255),
                `in_reply_to` VARCHAR(255), `thread_id` VARCHAR(255), `sent_at` DATETIME,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX(`contact_id`), INDEX(`lead_id`), INDEX(`folder`), INDEX(`is_read`), INDEX(`thread_id`), INDEX(`message_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }
}
