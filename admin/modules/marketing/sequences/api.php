<?php
/**
 * API AJAX — MODULE SÉQUENCES EMAIL
 * /admin/modules/marketing/sequences/api.php
 * Réponses JSON uniquement — pattern aligné Pages api.php
 */

if (!defined('ADMIN_ROUTER')) {
    // Accès direct via AJAX — vérifier session admin
    session_start();
    if (empty($_SESSION['admin_id']) && empty($_SESSION['user_id'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Non autorisé']);
        exit;
    }
}

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ── Connexion DB ──────────────────────────────────────────────────────────────
if (!isset($pdo) && !isset($db)) {
    $cfgPath = dirname(dirname(dirname(dirname(__DIR__)))) . '/includes/config.php';
    if (!file_exists($cfgPath)) {
        $cfgPath = dirname(dirname(dirname(__DIR__))) . '/includes/config.php';
    }
    if (file_exists($cfgPath)) require_once $cfgPath;
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'DB: ' . $e->getMessage()]);
        exit;
    }
}
if (isset($db) && !isset($pdo)) $pdo = $db;

// ── CSRF ──────────────────────────────────────────────────────────────────────
function verifyCsrf(): void {
    $token   = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $session = $_SESSION['csrf_token'] ?? '';
    if (!$session || !hash_equals($session, $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Token CSRF invalide']);
        exit;
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function ok(array $data = []): void {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}
function err(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}
function intPost(string $key): int { return (int)($_POST[$key] ?? 0); }
function strPost(string $key, int $max = 255): string {
    return mb_substr(trim($_POST[$key] ?? ''), 0, $max);
}

// ── Routing ───────────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {

        // ══════════════════════════════════════════════════════════════════════
        // SÉQUENCES
        // ══════════════════════════════════════════════════════════════════════

        case 'get_sequence':
            $id   = intPost('id') ?: (int)($_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM crm_sequences WHERE id = ?");
            $stmt->execute([$id]);
            $seq  = $stmt->fetch();
            if (!$seq) err('Séquence introuvable', 404);
            ok(['sequence' => $seq]);

        case 'create_sequence':
            verifyCsrf();
            $name = strPost('name');
            if (!$name) err('Le nom est obligatoire');
            $stmt = $pdo->prepare("
                INSERT INTO crm_sequences
                    (name, description, trigger_type, trigger_value, target_segment,
                     from_name, from_email, reply_to,
                     send_window_start, send_window_end, send_days)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $name,
                strPost('description', 1000),
                strPost('trigger_type') ?: 'manual',
                strPost('trigger_value'),
                strPost('target_segment') ?: null,
                strPost('from_name'),
                strPost('from_email'),
                strPost('reply_to'),
                strPost('send_window_start') ?: '09:00:00',
                strPost('send_window_end')   ?: '19:00:00',
                strPost('send_days')         ?: '1,2,3,4,5',
            ]);
            ok(['id' => (int)$pdo->lastInsertId(), 'message' => 'Séquence créée']);

        case 'update_sequence':
            verifyCsrf();
            $id = intPost('id');
            if (!$id) err('ID manquant');
            $stmt = $pdo->prepare("
                UPDATE crm_sequences SET
                    name=?, description=?, trigger_type=?, trigger_value=?,
                    target_segment=?, from_name=?, from_email=?, reply_to=?,
                    send_window_start=?, send_window_end=?, send_days=?
                WHERE id=?
            ");
            $stmt->execute([
                strPost('name'),
                strPost('description', 1000),
                strPost('trigger_type') ?: 'manual',
                strPost('trigger_value'),
                strPost('target_segment') ?: null,
                strPost('from_name'),
                strPost('from_email'),
                strPost('reply_to'),
                strPost('send_window_start') ?: '09:00:00',
                strPost('send_window_end')   ?: '19:00:00',
                strPost('send_days')         ?: '1,2,3,4,5',
                $id,
            ]);
            ok(['message' => 'Séquence mise à jour']);

        case 'toggle_sequence':
            verifyCsrf();
            $id = intPost('id');
            if (!$id) err('ID manquant');
            $pdo->prepare("UPDATE crm_sequences SET is_active = NOT is_active WHERE id = ?")
                ->execute([$id]);
            $active = (int)$pdo->prepare("SELECT is_active FROM crm_sequences WHERE id=?")->execute([$id]);
            $stmt   = $pdo->prepare("SELECT is_active FROM crm_sequences WHERE id=?");
            $stmt->execute([$id]);
            $newVal = (int)$stmt->fetchColumn();
            ok(['is_active' => $newVal, 'message' => $newVal ? 'Séquence activée' : 'Séquence désactivée']);

        case 'delete_sequence':
            verifyCsrf();
            $id = intPost('id');
            if (!$id) err('ID manquant');
            $pdo->prepare("DELETE FROM crm_sequences WHERE id=?")->execute([$id]);
            ok(['message' => 'Séquence supprimée']);

        case 'duplicate_sequence':
            verifyCsrf();
            $id = intPost('id');
            if (!$id) err('ID manquant');
            $stmt = $pdo->prepare("SELECT * FROM crm_sequences WHERE id=?");
            $stmt->execute([$id]);
            $seq  = $stmt->fetch();
            if (!$seq) err('Séquence introuvable', 404);

            $ins = $pdo->prepare("
                INSERT INTO crm_sequences
                    (name, description, trigger_type, trigger_value, target_segment,
                     from_name, from_email, reply_to,
                     send_window_start, send_window_end, send_days, is_active)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,0)
            ");
            $ins->execute([
                'Copie — ' . $seq['name'],
                $seq['description'],
                $seq['trigger_type'],
                $seq['trigger_value'],
                $seq['target_segment'],
                $seq['from_name'],
                $seq['from_email'],
                $seq['reply_to'],
                $seq['send_window_start'],
                $seq['send_window_end'],
                $seq['send_days'],
            ]);
            $newId = (int)$pdo->lastInsertId();

            // Dupliquer les étapes
            $steps = $pdo->prepare("SELECT * FROM crm_sequence_steps WHERE sequence_id=? ORDER BY step_order");
            $steps->execute([$id]);
            $sIns  = $pdo->prepare("
                INSERT INTO crm_sequence_steps
                    (sequence_id, step_order, step_type, delay_days, delay_hours,
                     subject, body_html, body_text, sms_text, task_description, is_active)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            foreach ($steps->fetchAll() as $s) {
                $sIns->execute([
                    $newId, $s['step_order'], $s['step_type'],
                    $s['delay_days'], $s['delay_hours'],
                    $s['subject'], $s['body_html'], $s['body_text'],
                    $s['sms_text'], $s['task_description'], $s['is_active'],
                ]);
            }
            ok(['id' => $newId, 'message' => 'Séquence dupliquée']);

        // ══════════════════════════════════════════════════════════════════════
        // ÉTAPES
        // ══════════════════════════════════════════════════════════════════════

        case 'get_steps':
            $seqId = intPost('sequence_id') ?: (int)($_GET['sequence_id'] ?? 0);
            if (!$seqId) err('sequence_id manquant');
            $stmt  = $pdo->prepare("SELECT * FROM crm_sequence_steps WHERE sequence_id=? ORDER BY step_order");
            $stmt->execute([$seqId]);
            ok(['steps' => $stmt->fetchAll()]);

        case 'add_step':
            verifyCsrf();
            $seqId = intPost('sequence_id');
            if (!$seqId) err('sequence_id manquant');
            $maxStmt = $pdo->prepare("SELECT COALESCE(MAX(step_order),0)+1 FROM crm_sequence_steps WHERE sequence_id=?");
            $maxStmt->execute([$seqId]);
            $nextOrder = (int)$maxStmt->fetchColumn();
            $stmt = $pdo->prepare("
                INSERT INTO crm_sequence_steps
                    (sequence_id, step_order, step_type, delay_days, delay_hours,
                     subject, body_html, sms_text, task_description)
                VALUES (?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                $seqId, $nextOrder,
                strPost('step_type') ?: 'email',
                intPost('delay_days'),
                intPost('delay_hours'),
                strPost('subject'),
                $_POST['body_html'] ?? '',
                strPost('sms_text', 480),
                strPost('task_description', 1000),
            ]);
            $newId = (int)$pdo->lastInsertId();
            $newStep = $pdo->prepare("SELECT * FROM crm_sequence_steps WHERE id=?");
            $newStep->execute([$newId]);
            ok(['step' => $newStep->fetch(), 'message' => 'Étape ajoutée']);

        case 'update_step':
            verifyCsrf();
            $stepId = intPost('step_id');
            $seqId  = intPost('sequence_id');
            if (!$stepId || !$seqId) err('IDs manquants');
            $stmt = $pdo->prepare("
                UPDATE crm_sequence_steps SET
                    step_type=?, delay_days=?, delay_hours=?,
                    subject=?, body_html=?, sms_text=?, task_description=?, is_active=?
                WHERE id=? AND sequence_id=?
            ");
            $stmt->execute([
                strPost('step_type') ?: 'email',
                intPost('delay_days'),
                intPost('delay_hours'),
                strPost('subject'),
                $_POST['body_html'] ?? '',
                strPost('sms_text', 480),
                strPost('task_description', 1000),
                isset($_POST['is_active']) ? 1 : 0,
                $stepId, $seqId,
            ]);
            ok(['message' => 'Étape mise à jour']);

        case 'delete_step':
            verifyCsrf();
            $stepId = intPost('step_id');
            $seqId  = intPost('sequence_id');
            if (!$stepId || !$seqId) err('IDs manquants');
            $pdo->prepare("DELETE FROM crm_sequence_steps WHERE id=? AND sequence_id=?")
                ->execute([$stepId, $seqId]);
            // Réordonner
            $remaining = $pdo->prepare("SELECT id FROM crm_sequence_steps WHERE sequence_id=? ORDER BY step_order");
            $remaining->execute([$seqId]);
            $upd = $pdo->prepare("UPDATE crm_sequence_steps SET step_order=? WHERE id=?");
            $i   = 1;
            foreach ($remaining->fetchAll() as $r) { $upd->execute([$i++, $r['id']]); }
            ok(['message' => 'Étape supprimée']);

        case 'reorder_steps':
            verifyCsrf();
            $seqId = intPost('sequence_id');
            $order = json_decode($_POST['order'] ?? '[]', true);
            if (!$seqId || !is_array($order)) err('Données invalides');
            $upd = $pdo->prepare("UPDATE crm_sequence_steps SET step_order=? WHERE id=? AND sequence_id=?");
            foreach ($order as $pos => $stepId) {
                $upd->execute([$pos + 1, (int)$stepId, $seqId]);
            }
            ok(['message' => 'Ordre mis à jour']);

        case 'toggle_step':
            verifyCsrf();
            $stepId = intPost('step_id');
            $seqId  = intPost('sequence_id');
            if (!$stepId) err('ID manquant');
            $pdo->prepare("UPDATE crm_sequence_steps SET is_active = NOT is_active WHERE id=? AND sequence_id=?")
                ->execute([$stepId, $seqId]);
            $stmt = $pdo->prepare("SELECT is_active FROM crm_sequence_steps WHERE id=?");
            $stmt->execute([$stepId]);
            $val  = (int)$stmt->fetchColumn();
            ok(['is_active' => $val, 'message' => $val ? 'Étape activée' : 'Étape désactivée']);

        // ══════════════════════════════════════════════════════════════════════
        // ENROLLMENTS
        // ══════════════════════════════════════════════════════════════════════

        case 'enroll_leads':
            verifyCsrf();
            $seqId   = intPost('sequence_id');
            $leadIds = json_decode($_POST['lead_ids'] ?? '[]', true);
            if (!$seqId || !is_array($leadIds) || empty($leadIds)) err('Données invalides');
            $stmt    = $pdo->prepare("
                INSERT IGNORE INTO crm_sequence_enrollments
                    (sequence_id, lead_id, status, next_action_at)
                VALUES (?,?,'active',NOW())
            ");
            $enrolled = 0;
            foreach ($leadIds as $lid) {
                $stmt->execute([$seqId, (int)$lid]);
                if ($stmt->rowCount() > 0) $enrolled++;
            }
            if ($enrolled > 0) {
                $pdo->prepare("UPDATE crm_sequences SET total_enrolled = total_enrolled + ? WHERE id=?")
                    ->execute([$enrolled, $seqId]);
            }
            ok(['enrolled' => $enrolled, 'message' => "$enrolled lead(s) inscrit(s)"]);

        case 'unenroll_lead':
            verifyCsrf();
            $seqId  = intPost('sequence_id');
            $leadId = intPost('lead_id');
            if (!$seqId || !$leadId) err('IDs manquants');
            $pdo->prepare("UPDATE crm_sequence_enrollments SET status='unsubscribed', unsubscribed_at=NOW() WHERE sequence_id=? AND lead_id=?")
                ->execute([$seqId, $leadId]);
            ok(['message' => 'Lead désinscrit']);

        case 'get_stats':
            $seqId = intPost('sequence_id') ?: (int)($_GET['sequence_id'] ?? 0);
            if (!$seqId) err('sequence_id manquant');
            $sent    = (int)$pdo->prepare("SELECT COUNT(*) FROM crm_sequence_sends WHERE sequence_id=? AND status IN ('sent','delivered','opened','clicked','replied')")->execute([$seqId]) ? $pdo->query("SELECT COUNT(*) FROM crm_sequence_sends WHERE sequence_id=$seqId AND status IN ('sent','delivered','opened','clicked','replied')")->fetchColumn() : 0;
            $stmt    = $pdo->prepare("SELECT
                COUNT(*) as sent,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN clicked_at IS NOT NULL THEN 1 ELSE 0 END) as clicked,
                SUM(CASE WHEN replied_at IS NOT NULL THEN 1 ELSE 0 END) as replied,
                SUM(CASE WHEN status='bounced' THEN 1 ELSE 0 END) as bounced
            FROM crm_sequence_sends WHERE sequence_id=? AND status IN ('sent','delivered','opened','clicked','replied')");
            $stmt->execute([$seqId]);
            $stats = $stmt->fetch();
            $enrolled = (int)$pdo->prepare("SELECT COUNT(*) FROM crm_sequence_enrollments WHERE sequence_id=?")->execute([$seqId]);
            $estmt    = $pdo->prepare("SELECT COUNT(*) FROM crm_sequence_enrollments WHERE sequence_id=?");
            $estmt->execute([$seqId]);
            $stats['enrolled'] = (int)$estmt->fetchColumn();
            ok(['stats' => $stats]);

        default:
            err('Action inconnue : ' . htmlspecialchars($action), 400);
    }
} catch (PDOException $e) {
    err('Erreur base de données : ' . $e->getMessage(), 500);
} catch (Exception $e) {
    err('Erreur : ' . $e->getMessage(), 500);
}