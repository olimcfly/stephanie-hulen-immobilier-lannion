<?php
/**
 * ══════════════════════════════════════════════════════════════
 * ANCRE — API progression AJAX
 * /admin/modules/system/api/strategy/ancre-progress.php
 *
 * Actions : get_progress | update_step | reset_pilier
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 5) . '/config/config.php';

header('Content-Type: application/json');

// ── Sécurité ──────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

$user_id     = (int)$_SESSION['admin_id'];
$instance_id = defined('INSTANCE_ID') ? INSTANCE_ID : 0;
$action      = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Init table si inexistante ─────────────────────────────────
function ancreEnsureTable(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS `ancre_progress` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `instance_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `user_id`     INT UNSIGNED NOT NULL DEFAULT 0,
            `pilier`      CHAR(1)      NOT NULL COMMENT 'A|N|C|R|E',
            `step_key`    VARCHAR(60)  NOT NULL,
            `status`      ENUM('todo','doing','done') NOT NULL DEFAULT 'todo',
            `note`        TEXT         NULL,
            `updated_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
                          ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_ancre` (`instance_id`,`user_id`,`pilier`,`step_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

try {
    $db = getDB();
    ancreEnsureTable($db);

    switch ($action) {

        // ── Récupérer toute la progression ────────────────────
        case 'get_progress':
            $pilier = strtoupper($_GET['pilier'] ?? '');
            $where  = 'instance_id = :iid AND user_id = :uid';
            $params = [':iid' => $instance_id, ':uid' => $user_id];

            if ($pilier && preg_match('/^[ANCRE]$/', $pilier)) {
                $where   .= ' AND pilier = :p';
                $params[':p'] = $pilier;
            }

            $rows = $db->prepare(
                "SELECT pilier, step_key, status, note, updated_at
                 FROM ancre_progress WHERE $where"
            );
            $rows->execute($params);
            $data = $rows->fetchAll(PDO::FETCH_ASSOC);

            // Indexer par pilier > step_key pour usage JS facile
            $indexed = [];
            foreach ($data as $row) {
                $indexed[$row['pilier']][$row['step_key']] = [
                    'status'     => $row['status'],
                    'note'       => $row['note'],
                    'updated_at' => $row['updated_at'],
                ];
            }

            echo json_encode(['success' => true, 'data' => $indexed]);
            break;

        // ── Mettre à jour une étape ───────────────────────────
        case 'update_step':
            $pilier   = strtoupper($_POST['pilier']   ?? '');
            $step_key = trim($_POST['step_key']        ?? '');
            $status   = $_POST['status']               ?? 'todo';
            $note     = trim($_POST['note']            ?? '');

            if (!preg_match('/^[ANCRE]$/', $pilier) || $step_key === '') {
                echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
                exit;
            }
            if (!in_array($status, ['todo','doing','done'], true)) $status = 'todo';

            $stmt = $db->prepare("
                INSERT INTO ancre_progress
                    (instance_id, user_id, pilier, step_key, status, note)
                VALUES
                    (:iid, :uid, :p, :sk, :st, :n)
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    note   = VALUES(note),
                    updated_at = NOW()
            ");
            $stmt->execute([
                ':iid' => $instance_id,
                ':uid' => $user_id,
                ':p'   => $pilier,
                ':sk'  => $step_key,
                ':st'  => $status,
                ':n'   => $note ?: null,
            ]);

            // Retourner le % de complétion du pilier
            $total = $db->prepare(
                "SELECT COUNT(*) FROM ancre_progress
                 WHERE instance_id=:iid AND user_id=:uid AND pilier=:p"
            );
            $total->execute([':iid'=>$instance_id,':uid'=>$user_id,':p'=>$pilier]);

            $done = $db->prepare(
                "SELECT COUNT(*) FROM ancre_progress
                 WHERE instance_id=:iid AND user_id=:uid AND pilier=:p AND status='done'"
            );
            $done->execute([':iid'=>$instance_id,':uid'=>$user_id,':p'=>$pilier]);

            echo json_encode([
                'success'    => true,
                'total'      => (int)$total->fetchColumn(),
                'done'       => (int)$done->fetchColumn(),
            ]);
            break;

        // ── Réinitialiser un pilier ───────────────────────────
        case 'reset_pilier':
            $pilier = strtoupper($_POST['pilier'] ?? '');
            if (!preg_match('/^[ANCRE]$/', $pilier)) {
                echo json_encode(['success' => false, 'error' => 'Pilier invalide']);
                exit;
            }
            $stmt = $db->prepare(
                "DELETE FROM ancre_progress
                 WHERE instance_id=:iid AND user_id=:uid AND pilier=:p"
            );
            $stmt->execute([':iid'=>$instance_id,':uid'=>$user_id,':p'=>$pilier]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}