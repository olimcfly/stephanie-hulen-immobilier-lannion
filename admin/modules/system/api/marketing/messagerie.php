<?php
/**
 * /admin/api/marketing/messagerie.php
 * API Messagerie CRM — IMAP sync + envoi SMTP + CRUD emails
 *
 * Standalone: /admin/api/marketing/messagerie.php?action=list
 * Via dispatcher: ?route=marketing.messagerie&action=list
 */

// Standalone mode: when called directly (not via dispatcher)
$_standalone = !isset($ctx);

if ($_standalone) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');

    require_once dirname(__DIR__, 2) . '/includes/init.php';

    // CSRF check for POST
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
            exit;
        }
    }

    $ctx = [
        'pdo'      => $pdo,
        'action'   => $_GET['action'] ?? $_POST['action'] ?? 'list',
        'method'   => $_SERVER['REQUEST_METHOD'],
        'params'   => array_merge($_GET, $_POST),
        'admin_id' => $_SESSION['admin_id'] ?? null,
    ];
}

// ── Handler function ──
function _messagerie_handler(array $ctx): array {
    $pdo    = $ctx['pdo'];
    $action = $ctx['action'];
    $method = $ctx['method'];
    $p      = $ctx['params'];

    require_once dirname(__DIR__, 3) . '/includes/classes/EmailService.php';
    $emailService = new EmailService($pdo);

    // ── stats ──
    if ($action === 'stats') {
        $s = ['total' => 0, 'unread' => 0, 'sent' => 0, 'inbox' => 0];
        try { $s['total']  = (int)$pdo->query("SELECT COUNT(*) FROM crm_emails")->fetchColumn(); } catch (Exception $e) {}
        try { $s['unread'] = (int)$pdo->query("SELECT COUNT(*) FROM crm_emails WHERE is_read=0 AND direction='inbound'")->fetchColumn(); } catch (Exception $e) {}
        try { $s['sent']   = (int)$pdo->query("SELECT COUNT(*) FROM crm_emails WHERE direction='outbound'")->fetchColumn(); } catch (Exception $e) {}
        try { $s['inbox']  = (int)$pdo->query("SELECT COUNT(*) FROM crm_emails WHERE direction='inbound'")->fetchColumn(); } catch (Exception $e) {}
        return ['success' => true, 'stats' => $s];
    }

    // ── list ──
    if ($action === 'list') {
        $folder = $p['folder'] ?? 'all';
        $limit  = min((int)($p['limit'] ?? 50), 200);
        $offset = (int)($p['offset'] ?? 0);
        $search = $p['search'] ?? '';

        $sql = "SELECT id, contact_id, lead_id, direction, from_email, from_name, to_email, to_name, subject, is_read, is_starred, folder, message_id, sent_at, created_at FROM crm_emails WHERE 1=1";
        $params = [];

        if ($folder === 'inbox') {
            $sql .= " AND direction='inbound'";
        } elseif ($folder === 'sent') {
            $sql .= " AND direction='outbound'";
        } elseif ($folder === 'starred') {
            $sql .= " AND is_starred=1";
        } elseif ($folder === 'unread') {
            $sql .= " AND is_read=0 AND direction='inbound'";
        } elseif ($folder !== 'all') {
            $sql .= " AND folder=?";
            $params[] = $folder;
        }

        if (!empty($search)) {
            $sql .= " AND (subject LIKE ? OR from_email LIKE ? OR from_name LIKE ? OR to_email LIKE ?)";
            $s = "%{$search}%";
            $params = array_merge($params, [$s, $s, $s, $s]);
        }

        $countSql = str_replace("SELECT id, contact_id, lead_id, direction, from_email, from_name, to_email, to_name, subject, is_read, is_starred, folder, message_id, sent_at, created_at", "SELECT COUNT(*)", $sql);
        $cstmt = $pdo->prepare($countSql);
        $cstmt->execute($params);
        $total = (int)$cstmt->fetchColumn();

        $sql .= " ORDER BY COALESCE(sent_at, created_at) DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return ['success' => true, 'emails' => $stmt->fetchAll(), 'total' => $total];
    }

    // ── get ──
    if ($action === 'get') {
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) return ['success' => false, 'error' => 'ID requis'];

        $stmt = $pdo->prepare("SELECT * FROM crm_emails WHERE id = ?");
        $stmt->execute([$id]);
        $email = $stmt->fetch();
        if (!$email) return ['success' => false, 'error' => 'Email introuvable', '_http_code' => 404];

        if (!$email['is_read']) {
            $pdo->prepare("UPDATE crm_emails SET is_read=1 WHERE id=?")->execute([$id]);
            $email['is_read'] = 1;
        }

        return ['success' => true, 'email' => $email];
    }

    // ── send ──
    if ($action === 'send' && $method === 'POST') {
        $to      = $p['to_email'] ?? '';
        $subject = $p['subject'] ?? '';
        $body    = $p['body_html'] ?? $p['body'] ?? '';

        if (empty($to) || empty($subject)) {
            return ['success' => false, 'error' => 'Destinataire et sujet requis'];
        }

        $options = [
            'to_name'    => $p['to_name'] ?? '',
            'contact_id' => $p['contact_id'] ?? null,
            'lead_id'    => $p['lead_id'] ?? null,
        ];

        if (!empty($p['from_email'])) $options['from_email'] = $p['from_email'];
        if (!empty($p['from_name']))  $options['from_name']  = $p['from_name'];
        if (!empty($p['reply_to']))   $options['reply_to']   = $p['reply_to'];
        if (!empty($p['cc']))         $options['cc']         = $p['cc'];

        return $emailService->sendEmail($to, $subject, $body, $options);
    }

    // ── reply ──
    if ($action === 'reply' && $method === 'POST') {
        $originalId = (int)($p['original_id'] ?? 0);
        $body       = $p['body_html'] ?? $p['body'] ?? '';

        if ($originalId <= 0 || empty($body)) {
            return ['success' => false, 'error' => 'ID original et corps requis'];
        }

        $stmt = $pdo->prepare("SELECT * FROM crm_emails WHERE id = ?");
        $stmt->execute([$originalId]);
        $original = $stmt->fetch();

        if (!$original) return ['success' => false, 'error' => 'Email original introuvable'];

        $to      = $original['direction'] === 'inbound' ? $original['from_email'] : $original['to_email'];
        $subject = 'Re: ' . preg_replace('/^Re:\s*/i', '', $original['subject']);

        $options = [
            'in_reply_to' => $original['message_id'] ?? '',
            'contact_id'  => $original['contact_id'],
            'lead_id'     => $original['lead_id'],
        ];

        return $emailService->sendEmail($to, $subject, $body, $options);
    }

    // ── mark-read ──
    if ($action === 'mark-read' && $method === 'POST') {
        $id = (int)($p['id'] ?? 0);
        $pdo->prepare("UPDATE crm_emails SET is_read=1 WHERE id=?")->execute([$id]);
        return ['success' => true];
    }

    // ── mark-unread ──
    if ($action === 'mark-unread' && $method === 'POST') {
        $id = (int)($p['id'] ?? 0);
        $pdo->prepare("UPDATE crm_emails SET is_read=0 WHERE id=?")->execute([$id]);
        return ['success' => true];
    }

    // ── star ──
    if ($action === 'star' && $method === 'POST') {
        $id   = (int)($p['id'] ?? 0);
        $star = (int)($p['starred'] ?? 1);
        $pdo->prepare("UPDATE crm_emails SET is_starred=? WHERE id=?")->execute([$star, $id]);
        return ['success' => true];
    }

    // ── delete ──
    if ($action === 'delete' && $method === 'POST') {
        $id = (int)($p['id'] ?? 0);
        $pdo->prepare("UPDATE crm_emails SET folder='trash' WHERE id=?")->execute([$id]);
        return ['success' => true];
    }

    // ── archive ──
    if ($action === 'archive' && $method === 'POST') {
        $id = (int)($p['id'] ?? 0);
        $pdo->prepare("UPDATE crm_emails SET folder='archive' WHERE id=?")->execute([$id]);
        return ['success' => true];
    }

    // ── sync ──
    if ($action === 'sync') {
        $folder = $p['folder'] ?? 'INBOX';
        $limit  = min((int)($p['limit'] ?? 30), 100);
        return $emailService->syncToDatabase($folder, $limit);
    }

    // ── folders ──
    if ($action === 'folders') {
        return ['success' => true, 'folders' => $emailService->listFolders()];
    }

    // ── accounts ──
    if ($action === 'accounts') {
        $config = $emailService->getConfig();
        return [
            'success'  => true,
            'accounts' => $config['email_accounts'] ?? [],
            'aliases'  => $config['email_aliases'] ?? [],
            'roles'    => $config['email_roles'] ?? [],
        ];
    }

    return [
        'success' => false,
        'error'   => "Action '{$action}' non reconnue",
        '_http_code' => 404,
        'actions' => ['stats', 'list', 'get', 'send', 'reply', 'mark-read', 'mark-unread', 'star', 'delete', 'archive', 'sync', 'folders', 'accounts'],
    ];
}

// Execute
$_result = _messagerie_handler($ctx);

if ($_standalone) {
    if (isset($_result['_http_code'])) {
        http_response_code($_result['_http_code']);
        unset($_result['_http_code']);
    }
    echo json_encode($_result, JSON_UNESCAPED_UNICODE);
    exit;
}

return $_result;
