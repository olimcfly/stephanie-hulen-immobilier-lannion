<?php
/**
 * API Handler: emails
 * Called via: /admin/api/router.php?module=emails&action=...
 * Table: email_messages
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        try {
            $folder = $input['folder'] ?? $_GET['folder'] ?? 'inbox';
            $search = $input['search'] ?? $_GET['search'] ?? '';
            $page = max(1, (int)($input['page'] ?? $_GET['page'] ?? 1));
            $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 25);
            $offset = ($page - 1) * $perPage;

            $where = "WHERE 1=1"; $params = [];
            if ($folder === 'starred') { $where .= " AND is_starred=1"; }
            else { $where .= " AND folder=?"; $params[] = $folder; }
            if ($search) { $where .= " AND (subject LIKE ? OR from_email LIKE ? OR from_name LIKE ?)"; $s = "%{$search}%"; $params = array_merge($params, [$s,$s,$s]); }

            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM email_messages {$where}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $stmt = $pdo->prepare("SELECT * FROM email_messages {$where} ORDER BY sent_at DESC, created_at DESC LIMIT {$perPage} OFFSET {$offset}");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $rows, 'emails' => $rows, 'total' => $total]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_email':
    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM email_messages WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !$row['is_read']) {
                $pdo->prepare("UPDATE email_messages SET is_read = 1 WHERE id = ?")->execute([$id]);
            }
            echo json_encode($row ? ['success' => true, 'data' => $row, 'email' => $row] : ['success' => false, 'message' => 'Email non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'send':
        try {
            // Accept both legacy field names (to, body) and canonical names (to_email, body_text)
            $toEmail = $input['to_email'] ?? $input['to'] ?? '';
            $fromEmail = $input['from_email'] ?? '';
            $fromName = $input['from_name'] ?? '';
            $cc = $input['cc'] ?? null;
            $subject = $input['subject'] ?? '';
            $bodyText = $input['body_text'] ?? $input['body'] ?? '';
            $bodyHtml = $input['body_html'] ?? '';

            $stmt = $pdo->prepare("INSERT INTO email_messages (folder, from_email, from_name, to_email, cc, subject, body_text, body_html, sent_at) VALUES ('sent', ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$fromEmail, $fromName, $toEmail, $cc, $subject, $bodyText, $bodyHtml]);
            echo json_encode(['success' => true, 'message' => 'Email envoye', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'star':
    case 'toggle_star':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("UPDATE email_messages SET is_starred = NOT is_starred WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Favori mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'read':
    case 'mark_read':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("UPDATE email_messages SET is_read = 1 WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Marque comme lu']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'mark_all_read':
        try {
            $pdo->exec("UPDATE email_messages SET is_read = 1 WHERE folder='inbox' AND is_read = 0");
            echo json_encode(['success' => true, 'message' => 'Tout marque comme lu']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'move':
        try {
            $id = (int)($input['id'] ?? 0);
            $folder = $input['folder'] ?? '';
            if (!in_array($folder, ['inbox','sent','drafts','trash'])) { echo json_encode(['success' => false, 'message' => 'Dossier invalide']); break; }
            $pdo->prepare("UPDATE email_messages SET folder = ? WHERE id = ?")->execute([$folder, $id]);
            echo json_encode(['success' => true, 'message' => 'Email deplace']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            // If in trash already, permanently delete; otherwise move to trash
            $cur = $pdo->prepare("SELECT folder FROM email_messages WHERE id = ?");
            $cur->execute([$id]);
            $cf = $cur->fetchColumn();
            if ($cf === 'trash') {
                $pdo->prepare("DELETE FROM email_messages WHERE id = ?")->execute([$id]);
            } else {
                $pdo->prepare("UPDATE email_messages SET folder = 'trash' WHERE id = ?")->execute([$id]);
            }
            echo json_encode(['success' => true, 'message' => 'Email supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'bulk_delete':
        try {
            $rawIds = $input['ids'] ?? [];
            $ids = is_string($rawIds) ? json_decode($rawIds, true) ?? [] : $rawIds;
            if (empty($ids)) { echo json_encode(['success' => false, 'message' => 'Aucun id fourni']); break; }
            $ids = array_map('intval', $ids);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM email_messages WHERE id IN ({$placeholders})")->execute($ids);
            echo json_encode(['success' => true, 'message' => count($ids) . ' email(s) supprime(s)']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'counts':
        try {
            $counts = [];
            foreach (['inbox','sent','drafts','trash'] as $f) {
                $counts[$f] = (int)$pdo->query("SELECT COUNT(*) FROM email_messages WHERE folder='{$f}'")->fetchColumn();
            }
            $counts['unread'] = (int)$pdo->query("SELECT COUNT(*) FROM email_messages WHERE folder='inbox' AND is_read=0")->fetchColumn();
            $counts['starred'] = (int)$pdo->query("SELECT COUNT(*) FROM email_messages WHERE is_starred=1")->fetchColumn();
            echo json_encode(['success' => true, 'data' => $counts, 'counts' => $counts]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
