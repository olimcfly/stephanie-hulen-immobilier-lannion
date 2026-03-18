<?php
/**
 * MODULE SÉQUENCES EMAIL — v2.0
 * /admin/modules/marketing/sequences/index.php
 * Pattern aligné Pages v2.3 / Articles v2.3
 * AJAX-first · Modal custom · Toast · Drag-drop steps · Objet SEQ JS
 */

if (!defined('ADMIN_ROUTER')) die("Accès direct interdit.");

$page_title     = "Séquences Email";
$current_module = "sequences";

// ── Connexion DB ──────────────────────────────────────────────────────────────
if (!isset($pdo) && !isset($db)) {
    try {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        echo '<div style="padding:20px;color:#ef4444">Erreur DB: '.htmlspecialchars($e->getMessage()).'</div>';
        return;
    }
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

// ── CSRF ──────────────────────────────────────────────────────────────────────
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ── Routage AJAX → déléguer à api.php ────────────────────────────────────────
if (isset($_GET['ajax']) || isset($_POST['action'])) {
    // Actions API (non-formulaire) → déléguer
    $ajaxActions = [
        'toggle_sequence','delete_sequence','duplicate_sequence',
        'get_steps','add_step','update_step','delete_step','reorder_steps','toggle_step',
        'enroll_leads','unenroll_lead','get_stats','get_sequence',
    ];
    $postedAction = $_POST['action'] ?? '';
    if (in_array($postedAction, $ajaxActions)) {
        $apiFile = __DIR__ . '/api.php';
        if (file_exists($apiFile)) {
            require $apiFile;
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success'=>false,'error'=>'api.php introuvable']);
        }
        return;
    }
}

// ── Handler POST formulaire création ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_sequence_form') {
    $postCsrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($csrf, $postCsrf)) {
        $createError = 'Token CSRF invalide';
    } else {
        try {
            $stmt = $db->prepare("
                INSERT INTO crm_sequences
                    (name, description, trigger_type, trigger_value, target_segment,
                     from_name, from_email, reply_to,
                     send_window_start, send_window_end, send_days)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->execute([
                mb_substr(trim($_POST['name'] ?? ''), 0, 255),
                mb_substr(trim($_POST['description'] ?? ''), 0, 1000),
                $_POST['trigger_type'] ?? 'manual',
                mb_substr(trim($_POST['trigger_value'] ?? ''), 0, 255),
                ($_POST['target_segment'] ?? '') ?: null,
                mb_substr(trim($_POST['from_name'] ?? ''), 0, 255),
                mb_substr(trim($_POST['from_email'] ?? ''), 0, 255),
                mb_substr(trim($_POST['reply_to'] ?? ''), 0, 255),
                $_POST['send_window_start'] ?? '09:00:00',
                $_POST['send_window_end']   ?? '19:00:00',
                $_POST['send_days']         ?? '1,2,3,4,5',
            ]);
            $newId = (int)$db->lastInsertId();
            header("Location: ?page=sequences&action=edit&id={$newId}&msg=created");
            exit;
        } catch (PDOException $e) {
            $createError = $e->getMessage();
        }
    }
}

// ── Création tables si absentes ───────────────────────────────────────────────
$tablesExist = true;
try { $db->query("SELECT 1 FROM crm_sequences LIMIT 1"); }
catch (PDOException $e) { $tablesExist = false; }

if (!$tablesExist) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `crm_sequences` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `trigger_type` ENUM('manual','new_lead','status_change','tag_added','form_submit') DEFAULT 'manual',
            `trigger_value` VARCHAR(255) DEFAULT NULL,
            `target_segment` VARCHAR(100) DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 0,
            `send_window_start` TIME DEFAULT '09:00:00',
            `send_window_end` TIME DEFAULT '19:00:00',
            `send_days` VARCHAR(50) DEFAULT '1,2,3,4,5',
            `from_name` VARCHAR(255) DEFAULT NULL,
            `from_email` VARCHAR(255) DEFAULT NULL,
            `reply_to` VARCHAR(255) DEFAULT NULL,
            `total_enrolled` INT(11) DEFAULT 0,
            `total_completed` INT(11) DEFAULT 0,
            `total_unsubscribed` INT(11) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS `crm_sequence_steps` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `sequence_id` INT(11) NOT NULL,
            `step_order` INT(11) NOT NULL DEFAULT 1,
            `step_type` ENUM('email','sms','wait','condition','task') DEFAULT 'email',
            `delay_days` INT(11) DEFAULT 0,
            `delay_hours` INT(11) DEFAULT 0,
            `subject` VARCHAR(255) DEFAULT NULL,
            `body_html` LONGTEXT DEFAULT NULL,
            `body_text` TEXT DEFAULT NULL,
            `sms_text` VARCHAR(480) DEFAULT NULL,
            `condition_field` VARCHAR(100) DEFAULT NULL,
            `condition_operator` VARCHAR(20) DEFAULT NULL,
            `condition_value` VARCHAR(255) DEFAULT NULL,
            `task_description` TEXT DEFAULT NULL,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_sequence_order` (`sequence_id`, `step_order`),
            CONSTRAINT `fk_css_sequence` FOREIGN KEY (`sequence_id`) REFERENCES `crm_sequences` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS `crm_sequence_enrollments` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `sequence_id` INT(11) NOT NULL,
            `lead_id` INT(11) NOT NULL,
            `current_step` INT(11) DEFAULT 1,
            `status` ENUM('active','paused','completed','unsubscribed','bounced','failed') DEFAULT 'active',
            `enrolled_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `next_action_at` DATETIME DEFAULT NULL,
            `completed_at` DATETIME DEFAULT NULL,
            `unsubscribed_at` DATETIME DEFAULT NULL,
            `metadata` JSON DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_seq_lead` (`sequence_id`,`lead_id`),
            CONSTRAINT `fk_cse_sequence` FOREIGN KEY (`sequence_id`) REFERENCES `crm_sequences` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $db->exec("CREATE TABLE IF NOT EXISTS `crm_sequence_sends` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `enrollment_id` INT(11) NOT NULL,
            `step_id` INT(11) NOT NULL,
            `lead_id` INT(11) NOT NULL,
            `sequence_id` INT(11) NOT NULL,
            `subject` VARCHAR(255) DEFAULT NULL,
            `status` ENUM('queued','scheduled','sent','delivered','opened','clicked','replied','bounced','failed','cancelled') DEFAULT 'queued',
            `scheduled_at` DATETIME DEFAULT NULL,
            `sent_at` DATETIME DEFAULT NULL,
            `opened_at` DATETIME DEFAULT NULL,
            `clicked_at` DATETIME DEFAULT NULL,
            `replied_at` DATETIME DEFAULT NULL,
            `bounced_at` DATETIME DEFAULT NULL,
            `error_message` TEXT DEFAULT NULL,
            `tracking_id` VARCHAR(100) DEFAULT NULL,
            `open_count` INT(11) DEFAULT 0,
            `click_count` INT(11) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            CONSTRAINT `fk_cssd_enrollment` FOREIGN KEY (`enrollment_id`) REFERENCES `crm_sequence_enrollments` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_cssd_step` FOREIGN KEY (`step_id`) REFERENCES `crm_sequence_steps` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

        $tablesExist = true;
    } catch (PDOException $e) {
        echo '<div style="padding:20px;color:#ef4444">Erreur création tables : '.htmlspecialchars($e->getMessage()).'</div>';
        return;
    }
}

// ── Routing ───────────────────────────────────────────────────────────────────
$action     = $_GET['action'] ?? 'list';
$sequenceId = (int)($_GET['id'] ?? 0);

// ── Stats globales ────────────────────────────────────────────────────────────
$stats = ['total'=>0,'active'=>0,'enrolled'=>0,'sent'=>0,'opened'=>0,'replied'=>0,'avg_open_rate'=>0];
try {
    $stats['total']   = (int)$db->query("SELECT COUNT(*) FROM crm_sequences")->fetchColumn();
    $stats['active']  = (int)$db->query("SELECT COUNT(*) FROM crm_sequences WHERE is_active=1")->fetchColumn();
    $stats['enrolled']= (int)$db->query("SELECT COUNT(*) FROM crm_sequence_enrollments")->fetchColumn();
    $stats['sent']    = (int)$db->query("SELECT COUNT(*) FROM crm_sequence_sends WHERE status IN ('sent','delivered','opened','clicked','replied')")->fetchColumn();
    $stats['opened']  = (int)$db->query("SELECT COUNT(*) FROM crm_sequence_sends WHERE opened_at IS NOT NULL")->fetchColumn();
    $stats['replied'] = (int)$db->query("SELECT COUNT(*) FROM crm_sequence_sends WHERE replied_at IS NOT NULL")->fetchColumn();
    if ($stats['sent'] > 0)
        $stats['avg_open_rate'] = round(($stats['opened'] / $stats['sent']) * 100, 1);
} catch (PDOException $e) {}

// ── Données liste ─────────────────────────────────────────────────────────────
$sequences = [];
if ($action === 'list') {
    try {
        $sequences = $db->query("
            SELECT s.*,
                (SELECT COUNT(*) FROM crm_sequence_steps ss WHERE ss.sequence_id=s.id) AS steps_count,
                (SELECT COUNT(*) FROM crm_sequence_enrollments se WHERE se.sequence_id=s.id AND se.status='active') AS active_enrolled,
                (SELECT COUNT(*) FROM crm_sequence_sends snd WHERE snd.sequence_id=s.id AND snd.status IN ('sent','delivered','opened','clicked','replied')) AS emails_sent,
                (SELECT COUNT(*) FROM crm_sequence_sends snd WHERE snd.sequence_id=s.id AND snd.opened_at IS NOT NULL) AS emails_opened
            FROM crm_sequences s ORDER BY s.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}
}

// ── Données édition ───────────────────────────────────────────────────────────
$sequence = null;
$steps    = [];
$enrollments   = [];
$availableLeads = [];

if ($action === 'edit' && $sequenceId > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM crm_sequences WHERE id=?");
        $stmt->execute([$sequenceId]);
        $sequence = $stmt->fetch();

        if ($sequence) {
            $s = $db->prepare("SELECT * FROM crm_sequence_steps WHERE sequence_id=? ORDER BY step_order");
            $s->execute([$sequenceId]);
            $steps = $s->fetchAll();

            $e = $db->prepare("
                SELECT se.*, l.first_name, l.last_name, l.email, l.status AS lead_status
                FROM crm_sequence_enrollments se
                LEFT JOIN leads l ON l.id=se.lead_id
                WHERE se.sequence_id=? ORDER BY se.enrolled_at DESC LIMIT 100
            ");
            $e->execute([$sequenceId]);
            $enrollments = $e->fetchAll();

            try {
                $a = $db->prepare("
                    SELECT l.id, l.first_name, l.last_name, l.email, l.source, l.status
                    FROM leads l
                    WHERE l.email IS NOT NULL AND l.email!=''
                    AND l.id NOT IN (SELECT lead_id FROM crm_sequence_enrollments WHERE sequence_id=?)
                    ORDER BY l.created_at DESC LIMIT 300
                ");
                $a->execute([$sequenceId]);
                $availableLeads = $a->fetchAll();
            } catch (PDOException $e2) {}
        } else { $action = 'list'; }
    } catch (PDOException $e) { $action = 'list'; }
}

// ── Données création ──────────────────────────────────────────────────────────
// (formulaire vide, pas de données à charger)

// ── Variables template email ──────────────────────────────────────────────────
$templateVars = [
    '{{prenom}}'              => 'Prénom du lead',
    '{{nom}}'                 => 'Nom',
    '{{email}}'               => 'Email',
    '{{telephone}}'           => 'Téléphone',
    '{{source}}'              => 'Source',
    '{{agent_nom}}'           => 'Agent',
    '{{agent_tel}}'           => 'Tél. agent',
    '{{site_url}}'            => 'URL site',
    '{{lien_desinscription}}' => 'Désinscription',
];

// ── Labels trigger ────────────────────────────────────────────────────────────
$triggerLabels = [
    'manual'        => ['icon'=>'fa-hand-pointer',   'label'=>'Manuel',          'color'=>'#6366f1'],
    'new_lead'      => ['icon'=>'fa-user-plus',       'label'=>'Nouveau lead',    'color'=>'#10b981'],
    'status_change' => ['icon'=>'fa-exchange-alt',    'label'=>'Chgt. statut',    'color'=>'#f59e0b'],
    'tag_added'     => ['icon'=>'fa-tag',             'label'=>'Tag ajouté',      'color'=>'#0891b2'],
    'form_submit'   => ['icon'=>'fa-wpforms',         'label'=>'Formulaire',      'color'=>'#8b5cf6'],
];
$segmentLabels = [
    'acheteur'    => ['icon'=>'fa-home',        'color'=>'#3b82f6'],
    'vendeur'     => ['icon'=>'fa-sign',        'color'=>'#10b981'],
    'investisseur'=> ['icon'=>'fa-chart-line',  'color'=>'#f59e0b'],
    'estimation'  => ['icon'=>'fa-calculator',  'color'=>'#8b5cf6'],
    'locataire'   => ['icon'=>'fa-key',         'color'=>'#0891b2'],
];
$stepTypeConfig = [
    'email'     => ['icon'=>'fa-envelope',      'color'=>'#6366f1', 'label'=>'Email'],
    'sms'       => ['icon'=>'fa-comment-sms',   'color'=>'#10b981', 'label'=>'SMS'],
    'wait'      => ['icon'=>'fa-clock',         'color'=>'#f59e0b', 'label'=>'Attente'],
    'condition' => ['icon'=>'fa-code-branch',   'color'=>'#0891b2', 'label'=>'Condition'],
    'task'      => ['icon'=>'fa-tasks',         'color'=>'#8b5cf6', 'label'=>'Tâche'],
];
?>
<!– Pas de doctype ici, on est dans un include admin –>

<style>
/* ══════════════════════════════════════════════════════════════
   SÉQUENCES EMAIL v2.0 — Pattern aligné Pages / Articles
══════════════════════════════════════════════════════════════ */

/* ── Banner ─────────────────────────────────────────────────── */
.sqm-banner {
    background: var(--surface);
    border-radius: 16px; padding: 26px 30px; margin-bottom: 22px;
    display: flex; align-items: center; justify-content: space-between;
    border: 1px solid var(--border); position: relative; overflow: hidden;
}
.sqm-banner::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #6366f1, #8b5cf6, #0891b2);
}
.sqm-banner::after {
    content: ''; position: absolute; top: -40%; right: -5%;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(99,102,241,.05), transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.sqm-banner-left { position: relative; z-index: 1; }
.sqm-banner-left h2 {
    font-size: 1.35rem; font-weight: 700; color: var(--text);
    margin: 0 0 4px; display: flex; align-items: center; gap: 10px; letter-spacing: -.02em;
}
.sqm-banner-left h2 i { font-size: 16px; color: #6366f1; }
.sqm-banner-left p { color: var(--text-2); font-size: .85rem; margin: 0; }
.sqm-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.sqm-stat {
    text-align: center; padding: 10px 16px;
    background: var(--surface-2); border-radius: 12px;
    border: 1px solid var(--border); min-width: 72px; transition: all .2s;
}
.sqm-stat:hover { box-shadow: 0 2px 8px rgba(0,0,0,.06); border-color: var(--border-h); }
.sqm-stat .num { font-size: 1.45rem; font-weight: 800; line-height: 1; color: var(--text); letter-spacing: -.03em; }
.sqm-stat .num.indigo { color: #6366f1; }
.sqm-stat .num.green  { color: #10b981; }
.sqm-stat .num.amber  { color: #f59e0b; }
.sqm-stat .num.teal   { color: #0891b2; }
.sqm-stat .lbl { font-size: .58rem; color: var(--text-3); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }

/* ── Toolbar ────────────────────────────────────────────────── */
.sqm-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px; flex-wrap: wrap; gap: 10px;
}
.sqm-toolbar-r { display: flex; align-items: center; gap: 10px; }
.sqm-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 18px; border-radius: 10px; font-size: .82rem;
    font-weight: 600; cursor: pointer; border: none; transition: all .15s;
    font-family: inherit; text-decoration: none; line-height: 1.3;
}
.sqm-btn-primary { background: #6366f1; color: #fff; box-shadow: 0 1px 4px rgba(99,102,241,.22); }
.sqm-btn-primary:hover { background: #4f46e5; transform: translateY(-1px); color: #fff; }
.sqm-btn-outline { background: var(--surface); color: var(--text-2); border: 1px solid var(--border); }
.sqm-btn-outline:hover { border-color: #6366f1; color: #6366f1; }
.sqm-btn-success { background: #10b981; color: #fff; }
.sqm-btn-success:hover { background: #059669; color: #fff; }
.sqm-btn-danger  { background: #ef4444; color: #fff; }
.sqm-btn-danger:hover  { background: #dc2626; color: #fff; }
.sqm-btn-sm { padding: 5px 12px; font-size: .75rem; }
.sqm-btn-xs { padding: 4px 10px; font-size: .72rem; border-radius: 8px; }

/* ── Cards liste ────────────────────────────────────────────── */
.sqm-cards { display: flex; flex-direction: column; gap: 12px; }
.sqm-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 20px 24px;
    transition: all .2s; position: relative; overflow: hidden;
}
.sqm-card::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px; background: var(--border); transition: background .2s;
}
.sqm-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.07); border-color: rgba(99,102,241,.2); }
.sqm-card:hover::before { background: #6366f1; }
.sqm-card.active-seq::before { background: #10b981; }
.sqm-card-row {
    display: flex; align-items: center; justify-content: space-between;
    gap: 16px; flex-wrap: wrap;
}
.sqm-card-info { flex: 1; min-width: 0; }
.sqm-card-title {
    font-size: 1rem; font-weight: 700; color: var(--text);
    text-decoration: none; display: block; margin-bottom: 4px;
}
.sqm-card-title:hover { color: #6366f1; }
.sqm-card-desc { font-size: .8rem; color: var(--text-3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 420px; }
.sqm-card-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; }

/* ── Badges ─────────────────────────────────────────────────── */
.sqm-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 20px; font-size: .68rem; font-weight: 700;
}
.sqm-badge-active   { background: #d1fae5; color: #065f46; }
.sqm-badge-inactive { background: #fee2e2; color: #991b1b; }
.sqm-badge-trigger  { background: rgba(99,102,241,.1); color: #6366f1; }
.sqm-badge-segment  { background: rgba(139,92,246,.1); color: #7c3aed; }

/* ── Mini stats ligne ───────────────────────────────────────── */
.sqm-mini-stats {
    display: flex; gap: 20px; align-items: center;
    padding-top: 12px; margin-top: 12px;
    border-top: 1px solid var(--border); flex-wrap: wrap;
}
.sqm-mini-stat .v { font-size: 1.05rem; font-weight: 800; color: var(--text); }
.sqm-mini-stat .l { font-size: .65rem; color: var(--text-3); text-transform: uppercase; letter-spacing: .04em; }
.sqm-mini-stat .rate { font-size: .7rem; color: #10b981; font-weight: 700; }
.sqm-card-actions { display: flex; gap: 6px; align-items: center; }
.sqm-icon-btn {
    width: 32px; height: 32px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: var(--text-3); background: transparent; border: 1px solid transparent;
    cursor: pointer; transition: all .12s; font-size: .78rem; text-decoration: none;
}
.sqm-icon-btn:hover { color: #6366f1; border-color: var(--border); background: rgba(99,102,241,.07); }
.sqm-icon-btn.del:hover { color: #dc2626; border-color: rgba(220,38,38,.2); background: #fef2f2; }
.sqm-icon-btn.dup:hover { color: #3b82f6; border-color: rgba(59,130,246,.2); background: #eff6ff; }

/* ── Empty state ────────────────────────────────────────────── */
.sqm-empty {
    text-align: center; padding: 70px 20px; color: var(--text-3);
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px;
}
.sqm-empty i { font-size: 2.8rem; opacity: .18; margin-bottom: 16px; display: block; }
.sqm-empty h3 { font-size: 1.05rem; color: var(--text-2); font-weight: 600; margin-bottom: 6px; }

/* ── Form ───────────────────────────────────────────────────── */
.sqm-form-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 28px; margin-bottom: 20px;
}
.sqm-section-title {
    font-size: .7rem; font-weight: 700; color: var(--text-3);
    text-transform: uppercase; letter-spacing: .08em;
    border-bottom: 1px solid var(--border); padding-bottom: 10px; margin-bottom: 16px;
}
.sqm-form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 14px; }
.sqm-fgroup { display: flex; flex-direction: column; gap: 5px; }
.sqm-fgroup label { font-size: .78rem; font-weight: 600; color: var(--text); }
.sqm-fgroup input,
.sqm-fgroup select,
.sqm-fgroup textarea {
    padding: 9px 12px; border: 1px solid var(--border);
    border-radius: 9px; font-size: .85rem; color: var(--text);
    background: var(--surface); font-family: inherit;
    transition: border-color .2s, box-shadow .2s; box-sizing: border-box; width: 100%;
}
.sqm-fgroup input:focus,
.sqm-fgroup select:focus,
.sqm-fgroup textarea:focus {
    outline: none; border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,.1);
}

/* ── Tabs (vue édition) ─────────────────────────────────────── */
.sqm-tabs { display: flex; border-bottom: 2px solid var(--border); margin-bottom: 24px; gap: 2px; }
.sqm-tab {
    padding: 11px 18px; cursor: pointer; font-weight: 600; font-size: .82rem;
    color: var(--text-3); border-bottom: 3px solid transparent; margin-bottom: -2px;
    transition: all .15s; background: none;
    border-top: none; border-left: none; border-right: none; font-family: inherit;
    display: flex; align-items: center; gap: 6px;
}
.sqm-tab:hover { color: #6366f1; }
.sqm-tab.active { color: #6366f1; border-bottom-color: #6366f1; }
.sqm-tab-badge {
    font-size: .6rem; padding: 1px 6px; border-radius: 10px;
    background: var(--surface-2); color: var(--text-3); font-weight: 700;
}
.sqm-tab.active .sqm-tab-badge { background: rgba(99,102,241,.12); color: #6366f1; }
.sqm-tab-pane { display: none; }
.sqm-tab-pane.active { display: block; }

/* ── Timeline étapes ────────────────────────────────────────── */
.sqm-timeline { position: relative; padding-left: 44px; }
.sqm-timeline::before {
    content: ''; position: absolute; left: 18px; top: 24px; bottom: 24px;
    width: 2px; background: var(--border); border-radius: 2px;
}
.sqm-step {
    position: relative; margin-bottom: 16px;
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 16px 18px;
    transition: all .2s; cursor: grab;
}
.sqm-step:active { cursor: grabbing; }
.sqm-step:hover { border-color: rgba(99,102,241,.25); box-shadow: 0 2px 12px rgba(0,0,0,.06); }
.sqm-step.sortable-ghost { opacity: .4; background: rgba(99,102,241,.05); }
.sqm-step.sortable-drag  { box-shadow: 0 8px 28px rgba(0,0,0,.14); }
.sqm-step.step-inactive  { opacity: .55; }
.sqm-step-dot {
    position: absolute; left: -34px; top: 20px;
    width: 14px; height: 14px; border-radius: 50%;
    border: 3px solid var(--surface-2);
    box-shadow: 0 0 0 2px currentColor;
}
.sqm-step-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; }
.sqm-step-meta   { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.sqm-step-num    { font-size: .72rem; font-weight: 800; }
.sqm-step-type   {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 6px; font-size: .7rem; font-weight: 700;
}
.sqm-step-delay  { font-size: .72rem; color: var(--text-3); display: flex; align-items: center; gap: 4px; }
.sqm-step-body   { margin-top: 8px; }
.sqm-step-subject { font-weight: 600; font-size: .9rem; color: var(--text); margin-bottom: 4px; }
.sqm-step-preview { font-size: .78rem; color: var(--text-3); line-height: 1.5; max-height: 48px; overflow: hidden; }
.sqm-step-actions {
    display: flex; gap: 4px;
    padding-top: 10px; margin-top: 10px; border-top: 1px solid var(--border);
}
.sqm-drag-handle {
    position: absolute; left: -28px; top: 0; bottom: 0;
    display: flex; align-items: center; cursor: grab;
    color: var(--text-3); font-size: .65rem; opacity: .5; transition: opacity .15s;
    padding: 0 4px;
}
.sqm-step:hover .sqm-drag-handle { opacity: 1; }

/* ── Vars template ──────────────────────────────────────────── */
.sqm-vars { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 6px; }
.sqm-var {
    font-family: monospace; font-size: .72rem;
    background: var(--surface-2); color: #6366f1;
    padding: 3px 8px; border-radius: 5px; cursor: pointer; transition: all .15s;
    border: 1px solid var(--border);
}
.sqm-var:hover { background: #6366f1; color: #fff; border-color: #6366f1; }

/* ── Table inscrits ─────────────────────────────────────────── */
.sqm-table-wrap { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.sqm-table { width: 100%; border-collapse: collapse; }
.sqm-table th {
    padding: 10px 14px; font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em; color: var(--text-3);
    background: var(--surface-2); border-bottom: 1px solid var(--border); text-align: left;
}
.sqm-table td { padding: 10px 14px; font-size: .82rem; border-bottom: 1px solid var(--border); }
.sqm-table tr:last-child td { border-bottom: none; }
.sqm-table tr:hover td { background: rgba(99,102,241,.02); }

/* ── Enrollment status ──────────────────────────────────────── */
.enr-badge {
    display: inline-flex; padding: 2px 8px; border-radius: 10px;
    font-size: .63rem; font-weight: 700; text-transform: uppercase;
}
.enr-active      { background: #d1fae5; color: #059669; }
.enr-paused      { background: #fef3c7; color: #d97706; }
.enr-completed   { background: #dbeafe; color: #2563eb; }
.enr-unsubscribed{ background: #f3f4f6; color: #6b7280; }
.enr-bounced,
.enr-failed      { background: #fee2e2; color: #dc2626; }

/* ── Enroll table ───────────────────────────────────────────── */
.sqm-enroll-wrap {
    max-height: 420px; overflow-y: auto;
    border: 1px solid var(--border); border-radius: 10px;
    background: var(--surface);
}
.sqm-enroll-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; border-bottom: 1px solid var(--border);
    background: var(--surface-2); border-radius: 10px 10px 0 0;
    position: sticky; top: 0; z-index: 2;
}
.sqm-table input[type="checkbox"] { accent-color: #6366f1; cursor: pointer; }

/* ── Modal ──────────────────────────────────────────────────── */
.sqm-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.45); backdrop-filter: blur(3px);
    z-index: 9999; align-items: center; justify-content: center;
}
.sqm-modal-overlay.open { display: flex; }
.sqm-modal {
    background: var(--surface); border-radius: 16px;
    width: 90%; max-width: 720px; max-height: 88vh;
    overflow-y: auto; position: relative;
    box-shadow: 0 24px 80px rgba(0,0,0,.18);
    transform: scale(.94) translateY(8px);
    transition: transform .2s cubic-bezier(.34,1.56,.64,1), opacity .15s;
    opacity: 0;
}
.sqm-modal-overlay.open .sqm-modal { transform: scale(1) translateY(0); opacity: 1; }
.sqm-modal-header {
    display: flex; justify-content: space-between; align-items: center;
    padding: 22px 26px 18px; border-bottom: 1px solid var(--border); position: sticky; top: 0;
    background: var(--surface); z-index: 1; border-radius: 16px 16px 0 0;
}
.sqm-modal-header h3 { font-size: 1.05rem; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 8px; }
.sqm-modal-body { padding: 22px 26px; }
.sqm-modal-footer {
    display: flex; gap: 10px; justify-content: flex-end;
    padding: 16px 26px; border-top: 1px solid var(--border);
}
.sqm-modal-close {
    width: 32px; height: 32px; border: none; background: var(--surface-2);
    border-radius: 8px; cursor: pointer; color: var(--text-3); font-size: 1rem;
    display: flex; align-items: center; justify-content: center; transition: all .15s;
}
.sqm-modal-close:hover { background: var(--border); color: var(--text); }

/* ── Confirm modal ──────────────────────────────────────────── */
#sqmConfirmModal .sqm-modal { max-width: 420px; }
.sqm-confirm-icon {
    width: 48px; height: 48px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; margin-bottom: 14px;
}

/* ── Flash / Toast ──────────────────────────────────────────── */
.sqm-flash {
    padding: 12px 18px; border-radius: 10px; font-size: .85rem; font-weight: 600;
    margin-bottom: 16px; display: flex; align-items: center; gap: 8px;
    animation: sqmFadeIn .3s;
}
.sqm-flash.success { background: #d1fae5; color: #059669; border: 1px solid rgba(5,150,105,.12); }
.sqm-flash.error   { background: #fef2f2; color: #dc2626; border: 1px solid rgba(220,38,38,.12); }
@keyframes sqmFadeIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

/* ── Edit page header ───────────────────────────────────────── */
.sqm-edit-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 22px; flex-wrap: wrap; gap: 12px;
}
.sqm-edit-title {
    font-size: 1.3rem; font-weight: 700; color: var(--text);
    display: flex; align-items: center; gap: 10px;
}
.sqm-edit-title i { color: #6366f1; }
.sqm-edit-actions { display: flex; gap: 8px; align-items: center; }

/* ── Responsive ─────────────────────────────────────────────── */
@media (max-width: 768px) {
    .sqm-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .sqm-stats  { width: 100%; }
    .sqm-toolbar { flex-direction: column; align-items: flex-start; }
    .sqm-form-grid { grid-template-columns: 1fr; }
    .sqm-tabs { overflow-x: auto; }
    .sqm-modal { max-width: calc(100vw - 32px); }
}
</style>

<?php
// ── Flash GET ─────────────────────────────────────────────────────────────────
$flash = $_GET['msg'] ?? '';
if (!empty($createError)): ?>
<div class="sqm-flash error">
    <i class="fas fa-exclamation-circle"></i>
    <?= htmlspecialchars($createError) ?>
</div>
<?php elseif ($flash): ?>
<div class="sqm-flash <?= $flash === 'error' ? 'error' : 'success' ?>">
    <i class="fas fa-<?= $flash === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
    <?= match($flash) {
        'created'  => 'Séquence créée avec succès.',
        'updated'  => 'Séquence mise à jour.',
        'deleted'  => 'Séquence supprimée.',
        'error'    => 'Une erreur est survenue.',
        default    => htmlspecialchars($flash),
    } ?>
</div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
<!-- ════════════════════════════════════════════════════════════
     VUE LISTE
════════════════════════════════════════════════════════════ -->

<!-- Banner -->
<div class="sqm-banner">
    <div class="sqm-banner-left">
        <h2><i class="fas fa-layer-group"></i> Séquences Email</h2>
        <p>Automatisez le nurturing de vos leads immobiliers avec des séquences intelligentes</p>
    </div>
    <div class="sqm-stats">
        <div class="sqm-stat"><div class="num indigo"><?= $stats['total'] ?></div><div class="lbl">Total</div></div>
        <div class="sqm-stat"><div class="num green"><?= $stats['active'] ?></div><div class="lbl">Actives</div></div>
        <div class="sqm-stat"><div class="num"><?= $stats['enrolled'] ?></div><div class="lbl">Inscrits</div></div>
        <div class="sqm-stat"><div class="num"><?= $stats['sent'] ?></div><div class="lbl">Envoyés</div></div>
        <div class="sqm-stat"><div class="num amber"><?= $stats['avg_open_rate'] ?>%</div><div class="lbl">Ouverture</div></div>
        <div class="sqm-stat"><div class="num teal"><?= $stats['replied'] ?></div><div class="lbl">Réponses</div></div>
    </div>
</div>

<!-- Toolbar -->
<div class="sqm-toolbar">
    <div></div>
    <div class="sqm-toolbar-r">
        <a href="?page=sequences&action=create" class="sqm-btn sqm-btn-primary">
            <i class="fas fa-plus"></i> Nouvelle séquence
        </a>
    </div>
</div>

<?php if (empty($sequences)): ?>
<div class="sqm-empty">
    <i class="fas fa-layer-group"></i>
    <h3>Aucune séquence créée</h3>
    <p>Créez votre première séquence automatisée pour engager vos leads.</p>
    <a href="?page=sequences&action=create" class="sqm-btn sqm-btn-primary" style="margin-top:16px">
        <i class="fas fa-plus"></i> Créer une séquence
    </a>
</div>

<?php else: ?>
<div class="sqm-cards">
<?php foreach ($sequences as $seq):
    $trig   = $triggerLabels[$seq['trigger_type']] ?? $triggerLabels['manual'];
    $segInfo = $segmentLabels[$seq['target_segment'] ?? ''] ?? null;
    $openRate = $seq['emails_sent'] > 0 ? round(($seq['emails_opened']/$seq['emails_sent'])*100) : 0;
?>
<div class="sqm-card <?= $seq['is_active'] ? 'active-seq' : '' ?>" data-id="<?= $seq['id'] ?>">
    <div class="sqm-card-row">
        <div class="sqm-card-info">
            <a href="?page=sequences&action=edit&id=<?= $seq['id'] ?>" class="sqm-card-title">
                <?= htmlspecialchars($seq['name']) ?>
            </a>
            <?php if ($seq['description']): ?>
            <div class="sqm-card-desc"><?= htmlspecialchars(mb_substr($seq['description'],0,120)) ?></div>
            <?php endif; ?>
            <div class="sqm-card-badges">
                <span class="sqm-badge <?= $seq['is_active'] ? 'sqm-badge-active' : 'sqm-badge-inactive' ?>">
                    <i class="fas fa-circle" style="font-size:5px"></i>
                    <?= $seq['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
                <span class="sqm-badge sqm-badge-trigger" style="background:<?= $trig['color'] ?>18;color:<?= $trig['color'] ?>">
                    <i class="fas <?= $trig['icon'] ?>" style="font-size:.6rem"></i>
                    <?= $trig['label'] ?>
                </span>
                <?php if ($segInfo): ?>
                <span class="sqm-badge sqm-badge-segment" style="background:<?= $segInfo['color'] ?>18;color:<?= $segInfo['color'] ?>">
                    <i class="fas <?= $segInfo['icon'] ?>" style="font-size:.6rem"></i>
                    <?= htmlspecialchars(ucfirst($seq['target_segment'])) ?>
                </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="sqm-card-actions">
            <a href="?page=sequences&action=edit&id=<?= $seq['id'] ?>" class="sqm-icon-btn" title="Éditer">
                <i class="fas fa-edit"></i>
            </a>
            <button class="sqm-icon-btn dup" title="Dupliquer"
                onclick="SEQ.duplicate(<?= $seq['id'] ?>, '<?= addslashes(htmlspecialchars($seq['name'])) ?>')">
                <i class="fas fa-copy"></i>
            </button>
            <button class="sqm-icon-btn" title="<?= $seq['is_active'] ? 'Désactiver' : 'Activer' ?>"
                onclick="SEQ.toggleSequence(<?= $seq['id'] ?>)"
                style="<?= $seq['is_active'] ? 'color:#ef4444' : 'color:#10b981' ?>">
                <i class="fas fa-<?= $seq['is_active'] ? 'pause' : 'play' ?>"></i>
            </button>
            <button class="sqm-icon-btn del" title="Supprimer"
                onclick="SEQ.deleteSequence(<?= $seq['id'] ?>, '<?= addslashes(htmlspecialchars($seq['name'])) ?>')">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>

    <div class="sqm-mini-stats">
        <div class="sqm-mini-stat">
            <div class="v"><?= $seq['steps_count'] ?></div>
            <div class="l">Étapes</div>
        </div>
        <div class="sqm-mini-stat">
            <div class="v"><?= $seq['active_enrolled'] ?></div>
            <div class="l">Inscrits actifs</div>
        </div>
        <div class="sqm-mini-stat">
            <div class="v"><?= $seq['emails_sent'] ?></div>
            <div class="l">Envoyés</div>
        </div>
        <div class="sqm-mini-stat">
            <div class="v"><?= $openRate ?>%</div>
            <div class="l">Ouverture</div>
            <?php if ($openRate >= 30): ?><div class="rate">↑ Bon</div><?php endif; ?>
        </div>
        <?php if ($seq['from_email']): ?>
        <div class="sqm-mini-stat" style="margin-left:auto">
            <div class="v" style="font-size:.75rem;color:var(--text-3)"><?= htmlspecialchars($seq['from_email']) ?></div>
            <div class="l">Expéditeur</div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php elseif ($action === 'create'): ?>
<!-- ════════════════════════════════════════════════════════════
     CRÉATION
════════════════════════════════════════════════════════════ -->

<div class="sqm-edit-header">
    <div class="sqm-edit-title">
        <i class="fas fa-plus-circle"></i> Nouvelle séquence
    </div>
    <div class="sqm-edit-actions">
        <a href="?page=sequences" class="sqm-btn sqm-btn-outline sqm-btn-sm">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<form method="POST" action="?page=sequences" id="createForm">
    <input type="hidden" name="action" value="create_sequence_form">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

    <div class="sqm-form-card">
        <div class="sqm-section-title">Informations générales</div>
        <div class="sqm-fgroup" style="margin-bottom:14px">
            <label>Nom de la séquence *</label>
            <input type="text" name="name" required placeholder="Ex : Nurturing acheteur Bordeaux" autofocus>
        </div>
        <div class="sqm-fgroup">
            <label>Description</label>
            <textarea name="description" rows="2" placeholder="Objectif de cette séquence…"></textarea>
        </div>
    </div>

    <div class="sqm-form-card">
        <div class="sqm-section-title">Déclencheur & ciblage</div>
        <div class="sqm-form-grid">
            <div class="sqm-fgroup">
                <label>Déclencheur</label>
                <select name="trigger_type">
                    <?php foreach ($triggerLabels as $v => $t): ?>
                    <option value="<?= $v ?>"><?= $t['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sqm-fgroup">
                <label>Valeur déclencheur</label>
                <input type="text" name="trigger_value" placeholder="Ex: source=google_ads">
            </div>
            <div class="sqm-fgroup">
                <label>Segment cible</label>
                <select name="target_segment">
                    <option value="">Tous</option>
                    <?php foreach (['acheteur','vendeur','investisseur','estimation','locataire'] as $s): ?>
                    <option value="<?= $s ?>"><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <div class="sqm-form-card">
        <div class="sqm-section-title">Expéditeur</div>
        <div class="sqm-form-grid">
            <div class="sqm-fgroup">
                <label>Nom affiché</label>
                <input type="text" name="from_name" placeholder="Jean Dupont Immobilier">
            </div>
            <div class="sqm-fgroup">
                <label>Email d'expédition</label>
                <input type="email" name="from_email" placeholder="contact@votresite.fr">
            </div>
            <div class="sqm-fgroup">
                <label>Répondre à</label>
                <input type="email" name="reply_to" placeholder="contact@votresite.fr">
            </div>
        </div>
    </div>

    <div class="sqm-form-card">
        <div class="sqm-section-title">Fenêtre d'envoi</div>
        <div class="sqm-form-grid">
            <div class="sqm-fgroup">
                <label>Heure de début</label>
                <input type="time" name="send_window_start" value="09:00">
            </div>
            <div class="sqm-fgroup">
                <label>Heure de fin</label>
                <input type="time" name="send_window_end" value="19:00">
            </div>
            <div class="sqm-fgroup">
                <label>Jours d'envoi</label>
                <select name="send_days">
                    <option value="1,2,3,4,5">Lundi – Vendredi</option>
                    <option value="1,2,3,4,5,6">Lundi – Samedi</option>
                    <option value="1,2,3,4,5,6,7">Tous les jours</option>
                </select>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px">
        <button type="submit" class="sqm-btn sqm-btn-primary">
            <i class="fas fa-save"></i> Créer la séquence
        </button>
        <a href="?page=sequences" class="sqm-btn sqm-btn-outline">Annuler</a>
    </div>
</form>

<?php elseif ($action === 'edit' && $sequence): ?>
<!-- ════════════════════════════════════════════════════════════
     ÉDITION
════════════════════════════════════════════════════════════ -->

<div class="sqm-edit-header">
    <div class="sqm-edit-title">
        <i class="fas fa-layer-group"></i>
        <?= htmlspecialchars($sequence['name']) ?>
        <span class="sqm-badge <?= $sequence['is_active'] ? 'sqm-badge-active' : 'sqm-badge-inactive' ?>" style="font-size:.72rem">
            <i class="fas fa-circle" style="font-size:5px"></i>
            <?= $sequence['is_active'] ? 'Active' : 'Inactive' ?>
        </span>
    </div>
    <div class="sqm-edit-actions">
        <button class="sqm-btn sqm-btn-sm <?= $sequence['is_active'] ? 'sqm-btn-danger' : 'sqm-btn-success' ?>"
            onclick="SEQ.toggleSequence(<?= $sequence['id'] ?>, true)">
            <i class="fas fa-<?= $sequence['is_active'] ? 'pause' : 'play' ?>"></i>
            <?= $sequence['is_active'] ? 'Désactiver' : 'Activer' ?>
        </button>
        <a href="?page=sequences" class="sqm-btn sqm-btn-outline sqm-btn-sm">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
</div>

<!-- Onglets -->
<div class="sqm-tabs">
    <button class="sqm-tab active" onclick="SEQ.switchTab('steps',this)">
        <i class="fas fa-list-ol"></i> Étapes
        <span class="sqm-tab-badge"><?= count($steps) ?></span>
    </button>
    <button class="sqm-tab" onclick="SEQ.switchTab('settings',this)">
        <i class="fas fa-cog"></i> Paramètres
    </button>
    <button class="sqm-tab" onclick="SEQ.switchTab('enrollments',this)">
        <i class="fas fa-users"></i> Inscrits
        <span class="sqm-tab-badge"><?= count($enrollments) ?></span>
    </button>
    <button class="sqm-tab" onclick="SEQ.switchTab('enroll',this)">
        <i class="fas fa-user-plus"></i> Inscrire
        <span class="sqm-tab-badge"><?= count($availableLeads) ?></span>
    </button>
</div>

<!-- ── Pane : Étapes ──────────────────────────────────────── -->
<div id="sqm-pane-steps" class="sqm-tab-pane active">
    <?php if (empty($steps)): ?>
    <div class="sqm-empty" style="padding:40px">
        <i class="fas fa-list-ol"></i>
        <h3>Aucune étape configurée</h3>
        <p>Ajoutez la première étape de votre séquence.</p>
    </div>
    <?php else: ?>
    <div class="sqm-timeline" id="sqmStepList">
        <?php foreach ($steps as $step):
            $sc = $stepTypeConfig[$step['step_type']] ?? $stepTypeConfig['email'];
            $delayStr = '';
            if ($step['delay_days'] > 0 || $step['delay_hours'] > 0) {
                $delayStr = ($step['delay_days'] > 0 ? $step['delay_days'].'j ' : '') . ($step['delay_hours'] > 0 ? $step['delay_hours'].'h ' : '') . 'après étape précédente';
            } else { $delayStr = 'Immédiatement'; }
        ?>
        <div class="sqm-step <?= $step['step_type'] === 'wait' ? '' : '' ?> <?= !$step['is_active'] ? 'step-inactive' : '' ?>"
             data-step-id="<?= $step['id'] ?>">
            <span class="sqm-drag-handle"><i class="fas fa-grip-vertical"></i></span>
            <span class="sqm-step-dot" style="color:<?= $sc['color'] ?>;background:<?= $sc['color'] ?>22"></span>

            <div class="sqm-step-header">
                <div class="sqm-step-meta">
                    <span class="sqm-step-num" style="color:<?= $sc['color'] ?>">
                        #<?= $step['step_order'] ?>
                    </span>
                    <span class="sqm-step-type" style="background:<?= $sc['color'] ?>15;color:<?= $sc['color'] ?>">
                        <i class="fas <?= $sc['icon'] ?>" style="font-size:.65rem"></i>
                        <?= $sc['label'] ?>
                    </span>
                    <?php if (!$step['is_active']): ?>
                    <span class="sqm-badge" style="background:#fee2e2;color:#991b1b;font-size:.62rem">Désactivée</span>
                    <?php endif; ?>
                    <span class="sqm-step-delay">
                        <i class="fas fa-hourglass-half"></i> <?= $delayStr ?>
                    </span>
                </div>
                <div style="display:flex;gap:4px">
                    <button class="sqm-icon-btn sqm-btn-xs" style="width:28px;height:28px"
                        onclick="SEQ.openStepModal(<?= htmlspecialchars(json_encode($step), ENT_QUOTES) ?>)"
                        title="Modifier"><i class="fas fa-edit"></i></button>
                    <button class="sqm-icon-btn sqm-btn-xs" style="width:28px;height:28px"
                        onclick="SEQ.toggleStep(<?= $step['id'] ?>, <?= $sequence['id'] ?>)"
                        title="<?= $step['is_active'] ? 'Désactiver' : 'Activer' ?>">
                        <i class="fas fa-<?= $step['is_active'] ? 'eye-slash' : 'eye' ?>"></i></button>
                    <button class="sqm-icon-btn del sqm-btn-xs" style="width:28px;height:28px"
                        onclick="SEQ.deleteStep(<?= $step['id'] ?>, <?= $sequence['id'] ?>)"
                        title="Supprimer"><i class="fas fa-trash"></i></button>
                </div>
            </div>

            <?php if ($step['step_type'] === 'email'): ?>
            <div class="sqm-step-body">
                <div class="sqm-step-subject">
                    <?= htmlspecialchars($step['subject'] ?: '(Sans objet)') ?>
                </div>
                <div class="sqm-step-preview">
                    <?= htmlspecialchars(mb_substr(strip_tags($step['body_html'] ?? ''), 0, 220)) ?>
                </div>
            </div>
            <?php elseif ($step['step_type'] === 'sms'): ?>
            <div class="sqm-step-body">
                <div class="sqm-step-preview"><?= htmlspecialchars(mb_substr($step['sms_text'] ?? '', 0, 160)) ?></div>
            </div>
            <?php elseif ($step['step_type'] === 'task'): ?>
            <div class="sqm-step-body">
                <div class="sqm-step-preview"><?= htmlspecialchars($step['task_description'] ?? '') ?></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <button class="sqm-btn sqm-btn-primary" style="margin-top:16px" onclick="SEQ.openStepModal(null)">
        <i class="fas fa-plus"></i> Ajouter une étape
    </button>
</div>

<!-- ── Pane : Paramètres ──────────────────────────────────── -->
<div id="sqm-pane-settings" class="sqm-tab-pane">
    <form id="settingsForm">
        <input type="hidden" name="action" value="update_sequence">
        <input type="hidden" name="id" value="<?= $sequence['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="sqm-form-card">
            <div class="sqm-section-title">Informations générales</div>
            <div class="sqm-fgroup" style="margin-bottom:14px">
                <label>Nom *</label>
                <input type="text" name="name" required value="<?= htmlspecialchars($sequence['name']) ?>">
            </div>
            <div class="sqm-fgroup">
                <label>Description</label>
                <textarea name="description" rows="2"><?= htmlspecialchars($sequence['description'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="sqm-form-card">
            <div class="sqm-section-title">Déclencheur & ciblage</div>
            <div class="sqm-form-grid">
                <div class="sqm-fgroup">
                    <label>Déclencheur</label>
                    <select name="trigger_type">
                        <?php foreach ($triggerLabels as $v => $t): ?>
                        <option value="<?= $v ?>" <?= $sequence['trigger_type']===$v?'selected':'' ?>><?= $t['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sqm-fgroup">
                    <label>Valeur déclencheur</label>
                    <input type="text" name="trigger_value" value="<?= htmlspecialchars($sequence['trigger_value'] ?? '') ?>">
                </div>
                <div class="sqm-fgroup">
                    <label>Segment cible</label>
                    <select name="target_segment">
                        <option value="">Tous</option>
                        <?php foreach (['acheteur','vendeur','investisseur','estimation','locataire'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($sequence['target_segment']??'')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="sqm-form-card">
            <div class="sqm-section-title">Expéditeur</div>
            <div class="sqm-form-grid">
                <div class="sqm-fgroup">
                    <label>Nom affiché</label>
                    <input type="text" name="from_name" value="<?= htmlspecialchars($sequence['from_name'] ?? '') ?>">
                </div>
                <div class="sqm-fgroup">
                    <label>Email d'expédition</label>
                    <input type="email" name="from_email" value="<?= htmlspecialchars($sequence['from_email'] ?? '') ?>">
                </div>
                <div class="sqm-fgroup">
                    <label>Répondre à</label>
                    <input type="email" name="reply_to" value="<?= htmlspecialchars($sequence['reply_to'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="sqm-form-card">
            <div class="sqm-section-title">Fenêtre d'envoi</div>
            <div class="sqm-form-grid">
                <div class="sqm-fgroup">
                    <label>Début</label>
                    <input type="time" name="send_window_start" value="<?= substr($sequence['send_window_start']??'09:00:00',0,5) ?>">
                </div>
                <div class="sqm-fgroup">
                    <label>Fin</label>
                    <input type="time" name="send_window_end" value="<?= substr($sequence['send_window_end']??'19:00:00',0,5) ?>">
                </div>
                <div class="sqm-fgroup">
                    <label>Jours</label>
                    <select name="send_days">
                        <?php foreach (['1,2,3,4,5'=>'Lun – Ven','1,2,3,4,5,6'=>'Lun – Sam','1,2,3,4,5,6,7'=>'Tous les jours'] as $v=>$l): ?>
                        <option value="<?= $v ?>" <?= ($sequence['send_days']??'')===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div style="display:flex;gap:10px;align-items:center">
            <button type="button" class="sqm-btn sqm-btn-primary" onclick="SEQ.saveSettings()">
                <i class="fas fa-save"></i> Enregistrer
            </button>
            <button type="button" class="sqm-btn sqm-btn-danger sqm-btn-sm"
                onclick="SEQ.deleteSequence(<?= $sequence['id'] ?>, '<?= addslashes(htmlspecialchars($sequence['name'])) ?>', true)">
                <i class="fas fa-trash"></i> Supprimer la séquence
            </button>
        </div>
    </form>
</div>

<!-- ── Pane : Inscrits ────────────────────────────────────── -->
<div id="sqm-pane-enrollments" class="sqm-tab-pane">
    <?php if (empty($enrollments)): ?>
    <div class="sqm-empty" style="padding:40px">
        <i class="fas fa-users"></i>
        <h3>Aucun lead inscrit</h3>
        <p>Inscrivez des leads via l'onglet "Inscrire".</p>
    </div>
    <?php else: ?>
    <div class="sqm-table-wrap">
        <table class="sqm-table">
            <thead>
                <tr>
                    <th>Lead</th><th>Email</th><th>Étape</th>
                    <th>Statut</th><th>Inscrit le</th><th>Prochaine action</th><th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($enrollments as $e): ?>
            <tr>
                <td><strong><?= htmlspecialchars(trim(($e['first_name']??'').' '.($e['last_name']??''))) ?></strong></td>
                <td style="font-size:.78rem;color:var(--text-3)"><?= htmlspecialchars($e['email']??'—') ?></td>
                <td><strong style="color:#6366f1"><?= $e['current_step'] ?></strong><span style="color:var(--text-3)">/<?= count($steps) ?></span></td>
                <td><span class="enr-badge enr-<?= $e['status'] ?>"><?= $e['status'] ?></span></td>
                <td style="font-size:.75rem;color:var(--text-3)"><?= $e['enrolled_at'] ? date('d/m/Y H:i', strtotime($e['enrolled_at'])) : '—' ?></td>
                <td style="font-size:.75rem;color:var(--text-3)"><?= $e['next_action_at'] ? date('d/m/Y H:i', strtotime($e['next_action_at'])) : '—' ?></td>
                <td>
                    <?php if ($e['status'] === 'active'): ?>
                    <button class="sqm-icon-btn" style="width:26px;height:26px;font-size:.7rem" title="Désinscrire"
                        onclick="SEQ.unenroll(<?= $e['lead_id'] ?>, <?= $sequence['id'] ?>)">
                        <i class="fas fa-user-minus"></i>
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── Pane : Inscrire des leads ─────────────────────────── -->
<div id="sqm-pane-enroll" class="sqm-tab-pane">
    <?php if (empty($availableLeads)): ?>
    <div class="sqm-empty" style="padding:40px">
        <i class="fas fa-user-plus"></i>
        <h3>Aucun lead disponible</h3>
        <p>Tous les leads avec email sont déjà inscrits.</p>
    </div>
    <?php else: ?>
    <div class="sqm-enroll-wrap">
        <div class="sqm-enroll-header">
            <div style="display:flex;align-items:center;gap:10px">
                <input type="checkbox" id="sqmSelectAll" onchange="SEQ.toggleAllLeads(this.checked)">
                <label for="sqmSelectAll" style="font-size:.8rem;font-weight:600;cursor:pointer">
                    <?= count($availableLeads) ?> leads disponibles
                </label>
            </div>
            <button class="sqm-btn sqm-btn-success sqm-btn-sm" onclick="SEQ.enrollSelected()">
                <i class="fas fa-user-plus"></i> Inscrire la sélection
            </button>
        </div>
        <table class="sqm-table">
            <thead>
                <tr>
                    <th style="width:36px"></th>
                    <th>Nom</th><th>Email</th><th>Source</th><th>Statut</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($availableLeads as $lead): ?>
            <tr>
                <td><input type="checkbox" class="sqm-lead-cb" value="<?= $lead['id'] ?>"></td>
                <td><strong><?= htmlspecialchars(trim(($lead['first_name']??'').' '.($lead['last_name']??''))) ?></strong></td>
                <td style="font-size:.78rem;color:var(--text-3)"><?= htmlspecialchars($lead['email']) ?></td>
                <td style="font-size:.75rem"><?= htmlspecialchars($lead['source']??'—') ?></td>
                <td>
                    <span class="sqm-badge" style="background:var(--surface-2);color:var(--text-2)">
                        <?= htmlspecialchars(ucfirst($lead['status']??'new')) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Data PHP → JS -->
<script>
const SEQ_DATA = {
    sequenceId: <?= $sequence['id'] ?>,
    csrf: '<?= $csrf ?>',
    apiUrl: '?page=sequences&ajax=1',
    stepsCount: <?= count($steps) ?>
};
</script>

<?php endif; // fin create / edit ?>

<!-- Data PHP → JS pour liste -->
<?php if ($action === 'list'): ?>
<script>
const SEQ_DATA = { csrf: '<?= $csrf ?>', apiUrl: '?page=sequences&ajax=1' };
</script>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     MODAL ÉTAPE
════════════════════════════════════════════════════════════ -->
<div class="sqm-modal-overlay" id="sqmStepModal">
    <div class="sqm-modal">
        <div class="sqm-modal-header">
            <h3 id="sqmStepModalTitle">
                <i class="fas fa-plus" id="sqmStepModalIcon"></i>
                <span id="sqmStepModalLabel">Ajouter une étape</span>
            </h3>
            <button class="sqm-modal-close" onclick="SEQ.closeModal('sqmStepModal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="sqm-modal-body">
            <form id="sqmStepForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="step_id" id="sqmStepId">
                <input type="hidden" name="sequence_id" value="<?= $sequenceId ?: '' ?>" id="sqmStepSeqId">

                <div class="sqm-form-grid" style="margin-bottom:14px">
                    <div class="sqm-fgroup">
                        <label>Type</label>
                        <select name="step_type" id="sqmStepType" onchange="SEQ.updateStepFields()">
                            <?php foreach ($stepTypeConfig as $v => $cfg): ?>
                            <option value="<?= $v ?>"><?= $cfg['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="sqm-fgroup">
                        <label>Délai (jours)</label>
                        <input type="number" name="delay_days" id="sqmDelayDays" value="1" min="0" max="365">
                    </div>
                    <div class="sqm-fgroup">
                        <label>Délai (heures)</label>
                        <input type="number" name="delay_hours" id="sqmDelayHours" value="0" min="0" max="23">
                    </div>
                </div>

                <div id="sqmEmailFields">
                    <div class="sqm-fgroup" style="margin-bottom:12px">
                        <label>Objet de l'email</label>
                        <input type="text" name="subject" id="sqmSubject"
                               placeholder="Ex : {{prenom}}, votre projet immobilier à Bordeaux">
                    </div>
                    <div class="sqm-fgroup">
                        <label>Corps (HTML)</label>
                        <textarea name="body_html" id="sqmBodyHtml" rows="10"
                                  placeholder="Bonjour {{prenom}},&#10;&#10;..."></textarea>
                        <div style="margin-top:6px">
                            <span style="font-size:.72rem;color:var(--text-3);font-weight:600">Variables disponibles :</span>
                            <div class="sqm-vars" style="margin-top:5px">
                                <?php foreach ($templateVars as $var => $desc): ?>
                                <span class="sqm-var" onclick="SEQ.insertVar('<?= $var ?>')" title="<?= $desc ?>"><?= $var ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="sqmSmsFields" style="display:none">
                    <div class="sqm-fgroup">
                        <label>Texte SMS <small id="sqmSmsCount" style="color:var(--text-3)">0 / 480</small></label>
                        <textarea name="sms_text" id="sqmSmsText" rows="4" maxlength="480"
                                  placeholder="Bonjour {{prenom}}, ..."></textarea>
                    </div>
                </div>

                <div id="sqmTaskFields" style="display:none">
                    <div class="sqm-fgroup">
                        <label>Description de la tâche</label>
                        <textarea name="task_description" id="sqmTaskDesc" rows="4"
                                  placeholder="Appeler le lead pour faire le point sur son projet…"></textarea>
                    </div>
                </div>

                <div id="sqmWaitFields" style="display:none">
                    <p style="font-size:.85rem;color:var(--text-3);padding:12px;background:var(--surface-2);border-radius:8px">
                        <i class="fas fa-clock" style="color:#f59e0b"></i>
                        L'étape "Attente" marque une pause dans la séquence.
                        Configurez la durée avec les champs délai ci-dessus.
                    </p>
                </div>

                <div style="margin-top:14px" id="sqmActiveToggle">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.83rem">
                        <input type="checkbox" name="is_active" id="sqmIsActive" checked style="accent-color:#6366f1">
                        Étape active
                    </label>
                </div>
            </form>
        </div>
        <div class="sqm-modal-footer">
            <button class="sqm-btn sqm-btn-outline" onclick="SEQ.closeModal('sqmStepModal')">Annuler</button>
            <button class="sqm-btn sqm-btn-primary" id="sqmStepSubmit" onclick="SEQ.saveStep()">
                <i class="fas fa-save"></i> <span id="sqmStepSubmitLabel">Ajouter</span>
            </button>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     CONFIRM MODAL (réutilisable)
════════════════════════════════════════════════════════════ -->
<div class="sqm-modal-overlay" id="sqmConfirmModal">
    <div class="sqm-modal">
        <div class="sqm-modal-body" style="padding:28px">
            <div class="sqm-confirm-icon" id="sqmConfirmIcon"></div>
            <div id="sqmConfirmTitle" style="font-size:1rem;font-weight:700;color:var(--text);margin-bottom:8px"></div>
            <div id="sqmConfirmMsg" style="font-size:.85rem;color:var(--text-2);line-height:1.6"></div>
        </div>
        <div class="sqm-modal-footer">
            <button class="sqm-btn sqm-btn-outline" onclick="SEQ.closeModal('sqmConfirmModal')">Annuler</button>
            <button class="sqm-btn" id="sqmConfirmBtn" onclick="SEQ._confirmCallback && SEQ._confirmCallback()">
                Confirmer
            </button>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     JS — Objet SEQ
════════════════════════════════════════════════════════════ -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
const SEQ = {

    // ── Config ─────────────────────────────────────────────
    apiUrl: (typeof SEQ_DATA !== 'undefined' ? SEQ_DATA.apiUrl : '?page=sequences&ajax=1'),
    csrf:   (typeof SEQ_DATA !== 'undefined' ? SEQ_DATA.csrf   : ''),
    sequenceId: (typeof SEQ_DATA !== 'undefined' ? SEQ_DATA.sequenceId : null),
    _confirmCallback: null,
    _sortable: null,

    // ══════════════════════════════════════════════════════
    // AJAX helpers
    // ══════════════════════════════════════════════════════
    async post(data) {
        data.csrf_token = this.csrf;
        const fd = new FormData();
        Object.entries(data).forEach(([k, v]) => fd.append(k, v));
        const r = await fetch(this.apiUrl, { method: 'POST', body: fd });
        return r.json();
    },

    // ══════════════════════════════════════════════════════
    // TABS
    // ══════════════════════════════════════════════════════
    switchTab(name, btn) {
        document.querySelectorAll('.sqm-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.sqm-tab-pane').forEach(p => p.classList.remove('active'));
        const pane = document.getElementById('sqm-pane-' + name);
        if (pane) pane.classList.add('active');
        if (btn) btn.classList.add('active');
        // Init sortable sur l'onglet étapes
        if (name === 'steps') this.initSortable();
    },

    // ══════════════════════════════════════════════════════
    // MODALS
    // ══════════════════════════════════════════════════════
    openModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.add('open');
    },
    closeModal(id) {
        const el = document.getElementById(id);
        if (el) el.classList.remove('open');
    },
    confirm({ icon, iconBg, iconColor, title, msg, btnLabel, btnColor, onConfirm }) {
        document.getElementById('sqmConfirmIcon').innerHTML = icon;
        document.getElementById('sqmConfirmIcon').style.cssText = `background:${iconBg};color:${iconColor};`;
        document.getElementById('sqmConfirmTitle').textContent = title;
        document.getElementById('sqmConfirmMsg').innerHTML    = msg;
        const btn = document.getElementById('sqmConfirmBtn');
        btn.textContent       = btnLabel || 'Confirmer';
        btn.style.background  = btnColor || '#6366f1';
        btn.style.color       = '#fff';
        btn.onmouseover = () => btn.style.filter = 'brightness(.88)';
        btn.onmouseout  = () => btn.style.filter = '';
        this._confirmCallback = () => { this.closeModal('sqmConfirmModal'); onConfirm(); };
        this.openModal('sqmConfirmModal');
    },

    // ══════════════════════════════════════════════════════
    // TOAST
    // ══════════════════════════════════════════════════════
    toast(msg, type = 'success') {
        const map = {
            success: { bg:'#d1fae5', color:'#059669', icon:'✓' },
            error:   { bg:'#fee2e2', color:'#dc2626', icon:'✕' },
            info:    { bg:'#dbeafe', color:'#2563eb', icon:'ℹ' },
        };
        const m = map[type] || map.success;
        const t = document.createElement('div');
        t.style.cssText = `position:fixed;bottom:24px;right:24px;z-index:10000;background:#fff;
            border:1px solid #e5e7eb;border-radius:12px;padding:11px 18px;
            display:flex;align-items:center;gap:10px;font-size:.83rem;font-weight:600;
            color:#111827;box-shadow:0 8px 24px rgba(0,0,0,.12);
            transform:translateY(20px);opacity:0;transition:all .25s;max-width:320px;`;
        t.innerHTML = `<span style="width:24px;height:24px;border-radius:50%;background:${m.bg};
            color:${m.color};display:flex;align-items:center;justify-content:center;
            font-size:.78rem;font-weight:900;flex-shrink:0">${m.icon}</span>${msg}`;
        document.body.appendChild(t);
        requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; });
        setTimeout(() => {
            t.style.opacity='0'; t.style.transform='translateY(10px)';
            setTimeout(() => t.remove(), 260);
        }, 3600);
    },

    // ══════════════════════════════════════════════════════
    // SÉQUENCES
    // ══════════════════════════════════════════════════════
    async toggleSequence(id, reload = false) {
        const d = await this.post({ action: 'toggle_sequence', id });
        if (d.success) {
            this.toast(d.message, 'success');
            if (reload) setTimeout(() => location.reload(), 700);
            else setTimeout(() => location.reload(), 600);
        } else { this.toast(d.error || 'Erreur', 'error'); }
    },

    deleteSequence(id, name, redirectAfter = false) {
        this.confirm({
            icon: '<i class="fas fa-trash"></i>', iconBg: '#fee2e2', iconColor: '#dc2626',
            title: 'Supprimer cette séquence ?',
            msg: `<strong>${name}</strong> et toutes ses étapes seront supprimées définitivement.`,
            btnLabel: 'Supprimer', btnColor: '#dc2626',
            onConfirm: async () => {
                const d = await this.post({ action: 'delete_sequence', id });
                if (d.success) {
                    this.toast('Séquence supprimée', 'success');
                    setTimeout(() => {
                        if (redirectAfter) window.location.href = '?page=sequences&msg=deleted';
                        else {
                            const card = document.querySelector(`.sqm-card[data-id="${id}"]`);
                            if (card) { card.style.cssText='opacity:0;transform:scale(.95);transition:all .3s'; setTimeout(()=>card.remove(),300); }
                        }
                    }, 500);
                } else { this.toast(d.error || 'Erreur', 'error'); }
            }
        });
    },

    duplicate(id, name) {
        this.confirm({
            icon: '<i class="fas fa-copy"></i>', iconBg: '#eff6ff', iconColor: '#3b82f6',
            title: 'Dupliquer cette séquence ?',
            msg: `Une copie brouillon de <strong>${name}</strong> sera créée avec toutes ses étapes.`,
            btnLabel: 'Dupliquer', btnColor: '#3b82f6',
            onConfirm: async () => {
                const d = await this.post({ action: 'duplicate_sequence', id });
                if (d.success) {
                    this.toast('Séquence dupliquée ✓', 'success');
                    setTimeout(() => window.location.href = `?page=sequences&action=edit&id=${d.id}`, 700);
                } else { this.toast(d.error || 'Erreur', 'error'); }
            }
        });
    },

    async saveSettings() {
        const form = document.getElementById('settingsForm');
        if (!form) return;
        const fd = new FormData(form);
        const data = {};
        fd.forEach((v, k) => data[k] = v);
        const d = await this.post(data);
        if (d.success) this.toast('Paramètres enregistrés ✓', 'success');
        else this.toast(d.error || 'Erreur', 'error');
    },

    // ══════════════════════════════════════════════════════
    // ÉTAPES
    // ══════════════════════════════════════════════════════
    openStepModal(step) {
        const isEdit = !!step;
        document.getElementById('sqmStepModalLabel').textContent = isEdit ? `Modifier l'étape #${step.step_order}` : 'Ajouter une étape';
        document.getElementById('sqmStepModalIcon').className   = isEdit ? 'fas fa-edit' : 'fas fa-plus';
        document.getElementById('sqmStepSubmitLabel').textContent = isEdit ? 'Mettre à jour' : 'Ajouter';
        document.getElementById('sqmStepId').value         = step ? step.id : '';
        document.getElementById('sqmStepSeqId').value      = this.sequenceId || '';
        document.getElementById('sqmStepType').value       = step ? step.step_type  : 'email';
        document.getElementById('sqmDelayDays').value      = step ? step.delay_days : 1;
        document.getElementById('sqmDelayHours').value     = step ? step.delay_hours: 0;
        document.getElementById('sqmSubject').value        = step ? (step.subject||'') : '';
        document.getElementById('sqmBodyHtml').value       = step ? (step.body_html||'') : '';
        document.getElementById('sqmSmsText').value        = step ? (step.sms_text||'') : '';
        document.getElementById('sqmTaskDesc').value       = step ? (step.task_description||'') : '';
        document.getElementById('sqmIsActive').checked     = step ? !!parseInt(step.is_active) : true;
        document.getElementById('sqmSmsCount').textContent = `${(step?.sms_text||'').length} / 480`;
        this.updateStepFields();
        this.openModal('sqmStepModal');
    },

    updateStepFields() {
        const type = document.getElementById('sqmStepType').value;
        const show = (id, v) => { const el=document.getElementById(id); if(el) el.style.display=v?'block':'none'; };
        show('sqmEmailFields', type === 'email');
        show('sqmSmsFields',   type === 'sms');
        show('sqmTaskFields',  type === 'task');
        show('sqmWaitFields',  type === 'wait');
        show('sqmActiveToggle', type !== 'wait');
    },

    insertVar(varName) {
        const type = document.getElementById('sqmStepType').value;
        const targets = { email: 'sqmBodyHtml', sms: 'sqmSmsText', task: 'sqmTaskDesc' };
        const el = document.getElementById(targets[type] || 'sqmBodyHtml');
        if (!el) return;
        const s = el.selectionStart, e = el.selectionEnd;
        el.value = el.value.substring(0,s) + varName + el.value.substring(e);
        el.selectionStart = el.selectionEnd = s + varName.length;
        el.focus();
    },

    async saveStep() {
        const form = document.getElementById('sqmStepForm');
        const fd   = new FormData(form);
        const stepId = document.getElementById('sqmStepId').value;
        const data = { action: stepId ? 'update_step' : 'add_step' };
        fd.forEach((v, k) => data[k] = v);
        if (stepId) { data.step_id = stepId; data.action = 'update_step'; }
        const d = await this.post(data);
        if (d.success) {
            this.toast(d.message || 'Étape sauvegardée ✓', 'success');
            this.closeModal('sqmStepModal');
            setTimeout(() => location.reload(), 600);
        } else { this.toast(d.error || 'Erreur', 'error'); }
    },

    deleteStep(stepId, seqId) {
        this.confirm({
            icon: '<i class="fas fa-trash"></i>', iconBg: '#fee2e2', iconColor: '#dc2626',
            title: 'Supprimer cette étape ?',
            msg: 'L\'étape sera supprimée et les suivantes réordonnées.',
            btnLabel: 'Supprimer', btnColor: '#dc2626',
            onConfirm: async () => {
                const d = await this.post({ action:'delete_step', step_id:stepId, sequence_id:seqId });
                if (d.success) {
                    this.toast('Étape supprimée', 'success');
                    const el = document.querySelector(`[data-step-id="${stepId}"]`);
                    if (el) { el.style.cssText='opacity:0;transform:scale(.95);transition:all .3s'; setTimeout(()=>location.reload(),300); }
                } else { this.toast(d.error || 'Erreur', 'error'); }
            }
        });
    },

    async toggleStep(stepId, seqId) {
        const d = await this.post({ action:'toggle_step', step_id:stepId, sequence_id:seqId });
        if (d.success) { this.toast(d.message, 'success'); setTimeout(()=>location.reload(),500); }
        else this.toast(d.error || 'Erreur', 'error');
    },

    // ── Drag-drop réordonnancement ──────────────────────────
    initSortable() {
        const list = document.getElementById('sqmStepList');
        if (!list || this._sortable) return;
        this._sortable = Sortable.create(list, {
            animation: 180,
            handle: '.sqm-drag-handle',
            ghostClass: 'sortable-ghost',
            dragClass:  'sortable-drag',
            onEnd: async () => {
                const order = [...list.querySelectorAll('[data-step-id]')]
                    .map(el => parseInt(el.dataset.stepId));
                const d = await this.post({
                    action: 'reorder_steps',
                    sequence_id: this.sequenceId,
                    order: JSON.stringify(order)
                });
                if (d.success) this.toast('Ordre mis à jour ✓', 'success');
                else this.toast(d.error || 'Erreur réordonnancement', 'error');
            }
        });
    },

    // ══════════════════════════════════════════════════════
    // ENROLLMENTS
    // ══════════════════════════════════════════════════════
    toggleAllLeads(checked) {
        document.querySelectorAll('.sqm-lead-cb').forEach(cb => cb.checked = checked);
    },

    async enrollSelected() {
        const ids = [...document.querySelectorAll('.sqm-lead-cb:checked')].map(cb => parseInt(cb.value));
        if (!ids.length) { this.toast('Sélectionnez au moins un lead', 'info'); return; }
        const d = await this.post({
            action: 'enroll_leads',
            sequence_id: this.sequenceId,
            lead_ids: JSON.stringify(ids)
        });
        if (d.success) {
            this.toast(`${d.enrolled} lead(s) inscrit(s) ✓`, 'success');
            setTimeout(() => location.reload(), 800);
        } else { this.toast(d.error || 'Erreur', 'error'); }
    },

    async unenroll(leadId, seqId) {
        this.confirm({
            icon: '<i class="fas fa-user-minus"></i>', iconBg: '#fef3c7', iconColor: '#d97706',
            title: 'Désinscrire ce lead ?',
            msg: 'Le lead sera marqué comme désinscrit et ne recevra plus les emails de cette séquence.',
            btnLabel: 'Désinscrire', btnColor: '#f59e0b',
            onConfirm: async () => {
                const d = await this.post({ action:'unenroll_lead', lead_id:leadId, sequence_id:seqId });
                if (d.success) { this.toast('Lead désinscrit', 'success'); setTimeout(()=>location.reload(),600); }
                else this.toast(d.error || 'Erreur', 'error');
            }
        });
    },
};

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    // SMS counter
    const smsTA = document.getElementById('sqmSmsText');
    if (smsTA) smsTA.addEventListener('input', function() {
        const c = document.getElementById('sqmSmsCount');
        if (c) c.textContent = `${this.value.length} / 480`;
    });

    // Fermer modal sur Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.sqm-modal-overlay.open').forEach(m => m.classList.remove('open'));
        }
    });

    // Fermer modal sur clic overlay
    document.querySelectorAll('.sqm-modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', e => {
            if (e.target === overlay) overlay.classList.remove('open');
        });
    });

    // Sortable si onglet étapes visible
    const stepsPane = document.getElementById('sqm-pane-steps');
    if (stepsPane && stepsPane.classList.contains('active')) SEQ.initSortable();

    // Formulaire création (POST classique, pas AJAX ici)
    const createForm = document.getElementById('createForm');
    if (createForm) {
        // Submit standard → redirection PHP
    }
});
</script>