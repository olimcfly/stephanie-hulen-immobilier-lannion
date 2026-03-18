<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  /admin/api/system/modules.php
 *  Diagnostic, toggle, create-table, test-smtp, system-info
 *
 *  ?route=system.modules&action=diagnose&module=crm&table=contacts
 *  ?route=system.modules&action=toggle       (POST module=crm&enable=1)
 *  ?route=system.modules&action=create-table  (POST table=crm_emails)
 *  ?route=system.modules&action=test-smtp
 *  ?route=system.modules&action=system-info
 *  ?route=system.modules&action=list
 * ══════════════════════════════════════════════════════════════
 */

$pdo    = $ctx['pdo'];
$action = $ctx['action'];
$method = $ctx['method'];
$p      = $ctx['params'];

// ────────────────────────────────────────────
// list — Liste tous les modules + état
// ────────────────────────────────────────────
if ($action === 'list') {
    $modules = getModuleDefs();
    $states  = loadModuleStates();

    foreach ($modules as &$m) {
        $m['enabled'] = $states[$m['id']]['enabled'] ?? true;
        $m['health']  = $m['table'] ? (tblExists($pdo, $m['table']) ? 'ok' : 'missing_table') : 'ok';
    }
    unset($m);

    $enabled = count(array_filter($modules, fn($m) => $m['enabled']));
    $healthy = count(array_filter($modules, fn($m) => $m['health'] === 'ok'));

    return [
        'success' => true,
        'modules' => $modules,
        'stats'   => [
            'total'   => count($modules),
            'enabled' => $enabled,
            'healthy' => $healthy,
            'score'   => count($modules) > 0 ? round(($healthy / count($modules)) * 100) : 0,
        ],
    ];
}

// ────────────────────────────────────────────
// diagnose — Diagnostic profond d'un module
// ────────────────────────────────────────────
if ($action === 'diagnose') {
    $moduleId = preg_replace('/[^a-z0-9_-]/', '', $p['module'] ?? '');
    $table    = preg_replace('/[^a-z0-9_]/', '', $p['table'] ?? '');
    $file     = $p['file'] ?? '';

    $result = [
        'success'   => true,
        'module'    => $moduleId,
        'table'     => $table,
        'checks'    => [],
        'overall'   => 'ok',
        'timestamp' => date('Y-m-d H:i:s'),
    ];

    if (empty($table)) {
        $result['checks'][] = chk('Table DB', 'skip', 'Module sans base de données', 'fa-database');
        return $result;
    }

    // 1. Table exists
    $exists = tblExists($pdo, $table);
    $result['checks'][] = chk('Table existe', $exists ? 'ok' : 'fail',
        $exists ? "Table `{$table}` trouvée" : "Table `{$table}` introuvable", 'fa-database');
    if (!$exists) { $result['overall'] = 'fail'; return $result; }

    // 2. Columns
    $cols = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll();
    $colNames = array_column($cols, 'Field');
    $result['columns'] = array_map(fn($c) => [
        'name' => $c['Field'], 'type' => $c['Type'], 'null' => $c['Null'],
        'key' => $c['Key'], 'default' => $c['Default']
    ], $cols);
    $result['column_count'] = count($cols);
    $hasId = in_array('id', $colNames);
    $result['checks'][] = chk('Structure', 'ok',
        count($cols) . " colonnes" . ($hasId ? ' · PK: id' : ''), 'fa-columns');

    // 3. Row count
    $count = (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    $result['row_count'] = $count;
    $result['checks'][] = chk('Données', $count > 0 ? 'ok' : 'warning',
        $count > 0 ? "{$count} enregistrement(s)" : "Table vide", 'fa-layer-group');

    // 4. SELECT
    try {
        $row = $pdo->query("SELECT * FROM `{$table}` ORDER BY id DESC LIMIT 1")->fetch();
        $result['checks'][] = chk('SELECT', 'ok',
            'OK' . ($row ? ' — dernier ID: ' . $row['id'] : ''), 'fa-eye');
    } catch (Exception $e) {
        $result['checks'][] = chk('SELECT', 'fail', $e->getMessage(), 'fa-eye');
    }

    // 5-7. INSERT → UPDATE → DELETE (rollback)
    try {
        $pdo->beginTransaction();
        $iCols = []; $iVals = []; $iPh = [];
        foreach ($cols as $c) {
            if ($c['Field'] === 'id' || $c['Key'] === 'PRI') continue;
            $v = guessValue($c['Type']);
            if ($v !== null) { $iCols[] = "`{$c['Field']}`"; $iVals[] = $v; $iPh[] = '?'; }
            if (count($iCols) >= 5) break;
        }
        if ($iCols) {
            $pdo->prepare("INSERT INTO `{$table}` (" . implode(',', $iCols) . ") VALUES (" . implode(',', $iPh) . ")")->execute($iVals);
            $nid = $pdo->lastInsertId();
            $result['checks'][] = chk('INSERT', 'ok', "OK — test ID: {$nid}", 'fa-plus-circle');

            $pdo->prepare("UPDATE `{$table}` SET {$iCols[0]} = ? WHERE id = ?")->execute(['__upd_' . time(), $nid]);
            $result['checks'][] = chk('UPDATE', 'ok', "OK sur ID {$nid}", 'fa-pen');

            $pdo->prepare("DELETE FROM `{$table}` WHERE id = ?")->execute([$nid]);
            $result['checks'][] = chk('DELETE', 'ok', "OK — ID {$nid} nettoyé", 'fa-trash');
        } else {
            $result['checks'][] = chk('INSERT', 'warning', 'Colonnes non standard', 'fa-plus-circle');
        }
        $pdo->rollBack();
    } catch (Exception $e) {
        try { $pdo->rollBack(); } catch (Exception $x) {}
        $result['checks'][] = chk('CRUD', 'fail', $e->getMessage(), 'fa-exclamation-triangle');
    }

    // 8. Module file
    if ($file) {
        $fp = __DIR__ . '/../../modules/' . $file;
        $ok = file_exists($fp);
        $result['checks'][] = chk('Fichier', $ok ? 'ok' : 'warning',
            $ok ? "modules/{$file}" : "Manquant: modules/{$file}", 'fa-file-code');
    }

    // 9. SQL install file (convention: table.sql or install.sql in module dir)
    // Auto-detect from modules structure
    $sqlPaths = findSqlFiles($moduleId);
    if (!empty($sqlPaths)) {
        $result['sql_files'] = $sqlPaths;
        $result['checks'][] = chk('Fichier SQL', 'ok',
            count($sqlPaths) . " fichier(s) SQL trouvé(s)", 'fa-file-code');
    }

    // Overall
    $fails = count(array_filter($result['checks'], fn($c) => $c['status'] === 'fail'));
    $warns = count(array_filter($result['checks'], fn($c) => $c['status'] === 'warning'));
    $result['overall'] = $fails > 0 ? 'fail' : ($warns > 0 ? 'warning' : 'ok');

    return $result;
}

// ────────────────────────────────────────────
// toggle — Activer/désactiver
// ────────────────────────────────────────────
if ($action === 'toggle' && $method === 'POST') {
    $modId  = preg_replace('/[^a-z0-9_-]/', '', $p['module'] ?? '');
    $enable = ($p['enable'] ?? '0') === '1';
    $states = loadModuleStates();
    $states[$modId] = ['enabled' => $enable, 'updated_at' => date('Y-m-d H:i:s'), 'by' => $ctx['admin_id']];
    saveModuleStates($states);
    return ['success' => true, 'module' => $modId, 'enabled' => $enable];
}

// ────────────────────────────────────────────
// create-table — Créer table manquante
// ────────────────────────────────────────────
if ($action === 'create-table' && $method === 'POST') {
    $table = preg_replace('/[^a-z0-9_]/', '', $p['table'] ?? '');
    $schemas = getTableSchemas();
    if (!isset($schemas[$table])) {
        return ['success' => false, 'error' => "Pas de schéma pour `{$table}`"];
    }
    $pdo->exec($schemas[$table]);
    return ['success' => true, 'message' => "Table `{$table}` créée"];
}

// ────────────────────────────────────────────
// test-smtp
// ────────────────────────────────────────────
if ($action === 'test-smtp') {
    $cfg = loadSmtpConfig($pdo);
    $checks = [];

    $host = $cfg['smtp_host'] ?? $cfg['SMTP_HOST'] ?? $cfg['host'] ?? '';
    $port = (int)($cfg['smtp_port'] ?? $cfg['SMTP_PORT'] ?? $cfg['port'] ?? 587);
    $user = $cfg['smtp_user'] ?? $cfg['SMTP_USER'] ?? $cfg['username'] ?? '';
    $from = $cfg['smtp_from'] ?? $cfg['SMTP_FROM'] ?? $cfg['from_email'] ?? $cfg['email_from'] ?? '';

    $checks[] = chk('Config SMTP', $host ? 'ok' : 'fail',
        $host ? "{$host}:{$port} | User: " . substr($user, 0, 3) . '***' : 'Aucune config trouvée');
    $checks[] = chk('Email expéditeur', $from ? 'ok' : 'warning',
        $from ? "From: {$from}" : 'Non configuré');

    if ($host) {
        $fp = @fsockopen($host, $port, $errno, $errstr, 5);
        if ($fp) {
            $banner = trim(substr(fgets($fp, 256), 0, 60));
            fclose($fp);
            $checks[] = chk('Connexion TCP', 'ok', "OK — {$banner}");
        } else {
            $checks[] = chk('Connexion TCP', 'fail', "Échec {$host}:{$port} — {$errstr}");
        }
    }

    $hasPHPMailer = file_exists(__DIR__ . '/../../vendor/phpmailer/phpmailer/src/PHPMailer.php');
    $checks[] = chk('Moteur envoi', ($hasPHPMailer || function_exists('mail')) ? 'ok' : 'fail',
        $hasPHPMailer ? 'PHPMailer' : (function_exists('mail') ? 'mail() PHP' : 'Aucun'));

    foreach (['crm_emails', 'email_sequences', 'email_templates', 'email_queue', 'email_logs'] as $t) {
        $ok = tblExists($pdo, $t);
        $cnt = $ok ? (int)$pdo->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn() : 0;
        $checks[] = chk("Table {$t}", $ok ? 'ok' : 'warning',
            $ok ? "{$cnt} ligne(s)" : 'Non trouvée');
    }

    return ['success' => true, 'checks' => $checks, 'config_found' => !empty($host)];
}

// ────────────────────────────────────────────
// system-info
// ────────────────────────────────────────────
if ($action === 'system-info') {
    return [
        'success'      => true,
        'php_version'  => PHP_VERSION,
        'db_version'   => $pdo->query("SELECT VERSION()")->fetchColumn(),
        'db_name'      => DB_NAME,
        'server'       => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'disk_free'    => disk_free_space('/') ? round(disk_free_space('/') / 1073741824, 2) . ' GB' : 'N/A',
        'memory_limit' => ini_get('memory_limit'),
        'max_upload'   => ini_get('upload_max_filesize'),
        'max_post'     => ini_get('post_max_size'),
        'extensions'   => array_map('extension_loaded',
            array_combine(
                ['pdo_mysql','curl','json','mbstring','openssl','gd','zip','fileinfo'],
                ['pdo_mysql','curl','json','mbstring','openssl','gd','zip','fileinfo']
            )
        ),
    ];
}

// ────────────────────────────────────────────
// Route inconnue
// ────────────────────────────────────────────
return [
    'success' => false,
    'error'   => "Action '{$action}' non reconnue",
    '_http_code' => 404,
    'actions' => ['list','diagnose','toggle','create-table','test-smtp','system-info'],
];


// ══════════════════════════════════════════════
// FONCTIONS
// ══════════════════════════════════════════════

function chk(string $name, string $status, string $detail, string $icon = ''): array {
    return compact('name', 'status', 'detail', 'icon');
}

function tblExists(PDO $pdo, ?string $t): bool {
    if (!$t) return false;
    try { return $pdo->query("SHOW TABLES LIKE '{$t}'")->rowCount() > 0; }
    catch (Exception $e) { return false; }
}

function guessValue(string $type): mixed {
    $t = strtolower($type);
    if (str_contains($t, 'varchar') || str_contains($t, 'text'))     return '__test_' . time();
    if (str_contains($t, 'int') || str_contains($t, 'tinyint'))      return 0;
    if (str_contains($t, 'decimal') || str_contains($t, 'float'))    return 0.0;
    if (str_contains($t, 'datetime') || str_contains($t, 'timestamp')) return date('Y-m-d H:i:s');
    if (str_contains($t, 'date'))     return date('Y-m-d');
    if (str_contains($t, 'json'))     return '{}';
    if (str_contains($t, 'enum'))   { preg_match("/enum\('([^']+)'/", $t, $m); return $m[1] ?? ''; }
    return null;
}

function loadModuleStates(): array {
    $f = __DIR__ . '/../../config/module-states.json';
    return file_exists($f) ? (json_decode(file_get_contents($f), true) ?? []) : [];
}

function saveModuleStates(array $states): void {
    $f = __DIR__ . '/../../config/module-states.json';
    file_put_contents($f, json_encode($states, JSON_PRETTY_PRINT));
}

function loadSmtpConfig(PDO $pdo): array {
    $cfg = [];
    try {
        $s = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp%' OR setting_key LIKE 'email%'");
        while ($r = $s->fetch()) $cfg[$r['setting_key']] = $r['setting_value'];
    } catch (Exception $e) {}
    $f = __DIR__ . '/../../config/smtp.php';
    if (file_exists($f)) { $fc = include $f; if (is_array($fc)) $cfg = array_merge($cfg, $fc); }
    return $cfg;
}

function findSqlFiles(string $moduleId): array {
    $found = [];
    $modulesDir = __DIR__ . '/../../modules';
    // Search recursively for .sql files matching module name
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modulesDir, RecursiveDirectoryIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'sql') {
            $rel = str_replace($modulesDir . '/', '', $file->getPathname());
            // Match by module id in path
            if (stripos($rel, $moduleId) !== false || stripos($rel, str_replace('-', '_', $moduleId)) !== false) {
                $found[] = $rel;
            }
        }
    }
    return $found;
}

function getModuleDefs(): array {
    return [
        ['id'=>'pages',       'name'=>'Pages CMS',            'category'=>'content',   'table'=>'pages',            'critical'=>true,  'file'=>'content/pages/index.php'],
        ['id'=>'articles',    'name'=>'Blog / Articles',      'category'=>'content',   'table'=>'articles',         'critical'=>false, 'file'=>'content/articles/index.php'],
        ['id'=>'captures',    'name'=>'Pages de Capture',     'category'=>'content',   'table'=>'captures',         'critical'=>false, 'file'=>'content/pages-capture/index.php'],
        ['id'=>'secteurs',    'name'=>'Quartiers / Secteurs', 'category'=>'content',   'table'=>'secteurs',         'critical'=>false, 'file'=>'content/secteurs/index.php'],
        ['id'=>'builder',     'name'=>'Website Builder',      'category'=>'builder',   'table'=>'builder_templates','critical'=>false, 'file'=>'builder/builder/index.php'],
        ['id'=>'templates',   'name'=>'Templates',            'category'=>'builder',   'table'=>'builder_templates','critical'=>false, 'file'=>'builder/builder/templates.php'],
        ['id'=>'headers',     'name'=>'Headers',              'category'=>'builder',   'table'=>'headers',          'critical'=>false, 'file'=>'builder/design/index.php'],
        ['id'=>'footers',     'name'=>'Footers',              'category'=>'builder',   'table'=>'footers',          'critical'=>false, 'file'=>'builder/design/index.php'],
        ['id'=>'menus',       'name'=>'Menus',                'category'=>'builder',   'table'=>'menus',            'critical'=>false, 'file'=>'builder/menus/index.php'],
        ['id'=>'crm',         'name'=>'CRM Contacts',         'category'=>'marketing', 'table'=>'contacts',         'critical'=>true,  'file'=>'marketing/crm/index.php'],
        ['id'=>'leads',       'name'=>'Prospects',            'category'=>'marketing', 'table'=>'leads',            'critical'=>true,  'file'=>'marketing/leads/index.php'],
        ['id'=>'messagerie',  'name'=>'Messagerie',           'category'=>'marketing', 'table'=>'crm_emails',       'critical'=>false, 'file'=>'marketing/emails/index.php'],
        ['id'=>'email-auto',  'name'=>'Emails Auto',          'category'=>'marketing', 'table'=>'email_sequences',  'critical'=>false, 'file'=>'marketing/emails/index.php'],
        ['id'=>'scoring',     'name'=>'Lead Scoring',         'category'=>'marketing', 'table'=>'lead_scoring',     'critical'=>false, 'file'=>'marketing/scoring/index.php'],
        ['id'=>'sequences',   'name'=>'Séquences',            'category'=>'marketing', 'table'=>'email_sequences',  'critical'=>false, 'file'=>'marketing/sequences/index.php'],
        ['id'=>'biens',       'name'=>'Annonces',             'category'=>'immobilier','table'=>'biens',            'critical'=>true,  'file'=>'immobilier/biens/index.php'],
        ['id'=>'estimation',  'name'=>'Estimations',          'category'=>'immobilier','table'=>'estimations',      'critical'=>false, 'file'=>'immobilier/estimation/index.php'],
        ['id'=>'financement', 'name'=>'Financement',          'category'=>'immobilier','table'=>'financing',        'critical'=>false, 'file'=>'immobilier/financement/index.php'],
        ['id'=>'rdv',         'name'=>'Agenda / RDV',         'category'=>'immobilier','table'=>'appointments',     'critical'=>false, 'file'=>'immobilier/rdv/index.php'],
        ['id'=>'seo',         'name'=>'SEO Global',           'category'=>'seo',       'table'=>null,               'critical'=>false, 'file'=>'seo/seo/index.php'],
        ['id'=>'seo-semantic','name'=>'Sémantique',           'category'=>'seo',       'table'=>null,               'critical'=>false, 'file'=>'seo/seo-semantic/index.php'],
        ['id'=>'local-seo',   'name'=>'SEO Local',            'category'=>'seo',       'table'=>null,               'critical'=>false, 'file'=>'seo/local-seo/index.php'],
        ['id'=>'analytics',   'name'=>'Statistiques',         'category'=>'seo',       'table'=>null,               'critical'=>false, 'file'=>'seo/analytics/index.php'],
        ['id'=>'journal',     'name'=>'Journal Éditorial',    'category'=>'ai',        'table'=>'editorial_journal','critical'=>false, 'file'=>'ai/journal/index.php'],
        ['id'=>'ia',          'name'=>'Assistant IA',          'category'=>'ai',        'table'=>null,               'critical'=>false, 'file'=>'ai/ai/index.php'],
        ['id'=>'agents',      'name'=>'Agents IA',            'category'=>'ai',        'table'=>null,               'critical'=>false, 'file'=>'ai/agents/index.php'],
        ['id'=>'ai-prompts',  'name'=>'Prompts IA',           'category'=>'ai',        'table'=>null,               'critical'=>false, 'file'=>'ai/ai-prompts/index.php'],
        ['id'=>'neuropersona','name'=>'NeuroPersona™',        'category'=>'ai',        'table'=>null,               'critical'=>false, 'file'=>'ai/neuropersona/index.php'],
        ['id'=>'gmb',         'name'=>'Prospection GMB',      'category'=>'network',   'table'=>'gmb_prospects',    'critical'=>false, 'file'=>'social/gmb/index.php'],
        ['id'=>'contact',     'name'=>'Formulaire Contact',   'category'=>'network',   'table'=>'contacts',         'critical'=>false, 'file'=>'network/contact/index.php'],
        ['id'=>'facebook',    'name'=>'Facebook',             'category'=>'social',    'table'=>null,               'critical'=>false, 'file'=>'social/facebook/index.php'],
        ['id'=>'instagram',   'name'=>'Instagram',            'category'=>'social',    'table'=>null,               'critical'=>false, 'file'=>'social/instagram/index.php'],
        ['id'=>'tiktok',      'name'=>'TikTok',               'category'=>'social',    'table'=>null,               'critical'=>false, 'file'=>'social/tiktok/index.php'],
        ['id'=>'linkedin',    'name'=>'LinkedIn',             'category'=>'social',    'table'=>null,               'critical'=>false, 'file'=>'social/linkedin/index.php'],
        ['id'=>'ads',         'name'=>'Publicité',            'category'=>'marketing', 'table'=>null,               'critical'=>false, 'file'=>'marketing/ads-launch/index.php'],
        ['id'=>'launchpad',   'name'=>'Launchpad',            'category'=>'strategy',  'table'=>null,               'critical'=>false, 'file'=>'strategy/launchpad/index.php'],
        ['id'=>'settings',    'name'=>'Configuration',        'category'=>'system',    'table'=>'settings',         'critical'=>true,  'file'=>'system/settings/index.php'],
        ['id'=>'maintenance', 'name'=>'Maintenance',          'category'=>'system',    'table'=>null,               'critical'=>false, 'file'=>'system/maintenance/index.php'],
    ];
}

function getTableSchemas(): array {
    return [
        'crm_emails' => "CREATE TABLE IF NOT EXISTS `crm_emails` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `contact_id` INT, `lead_id` INT,
            `direction` ENUM('inbound','outbound') DEFAULT 'inbound',
            `from_email` VARCHAR(255) NOT NULL, `from_name` VARCHAR(255) DEFAULT '',
            `to_email` VARCHAR(255) NOT NULL, `to_name` VARCHAR(255) DEFAULT '',
            `subject` VARCHAR(500) DEFAULT '', `body_html` LONGTEXT, `body_text` TEXT,
            `is_read` TINYINT(1) DEFAULT 0, `is_starred` TINYINT(1) DEFAULT 0,
            `folder` ENUM('inbox','sent','draft','trash','archive') DEFAULT 'inbox',
            `labels` JSON, `attachments` JSON, `message_id` VARCHAR(255),
            `in_reply_to` VARCHAR(255), `thread_id` VARCHAR(255), `sent_at` DATETIME,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX(`contact_id`), INDEX(`lead_id`), INDEX(`folder`), INDEX(`is_read`), INDEX(`thread_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'email_sequences' => "CREATE TABLE IF NOT EXISTS `email_sequences` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL, `slug` VARCHAR(255),
            `description` TEXT, `trigger_type` ENUM('manual','lead_new','lead_scored','contact_tag','estimation','capture','appointment') DEFAULT 'manual',
            `trigger_value` VARCHAR(255), `status` ENUM('active','paused','draft') DEFAULT 'draft',
            `steps` JSON, `total_enrolled` INT DEFAULT 0, `total_completed` INT DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'email_templates' => "CREATE TABLE IF NOT EXISTS `email_templates` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL, `slug` VARCHAR(255),
            `category` ENUM('welcome','followup','nurturing','estimation','property','newsletter','custom') DEFAULT 'custom',
            `subject` VARCHAR(500) NOT NULL, `body_html` LONGTEXT, `body_text` TEXT,
            `variables` JSON, `usage_count` INT DEFAULT 0, `status` ENUM('active','draft','archived') DEFAULT 'active',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'email_queue' => "CREATE TABLE IF NOT EXISTS `email_queue` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `sequence_id` INT, `template_id` INT, `contact_id` INT, `lead_id` INT,
            `to_email` VARCHAR(255) NOT NULL, `to_name` VARCHAR(255) DEFAULT '', `subject` VARCHAR(500) NOT NULL,
            `body_html` LONGTEXT, `priority` TINYINT DEFAULT 5,
            `status` ENUM('pending','sending','sent','failed','cancelled') DEFAULT 'pending',
            `scheduled_at` DATETIME, `sent_at` DATETIME, `error_message` TEXT,
            `attempts` TINYINT DEFAULT 0, `max_attempts` TINYINT DEFAULT 3,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'email_logs' => "CREATE TABLE IF NOT EXISTS `email_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `email_id` INT, `queue_id` INT, `sequence_id` INT,
            `to_email` VARCHAR(255) NOT NULL, `subject` VARCHAR(500) DEFAULT '',
            `event` ENUM('sent','delivered','opened','clicked','bounced','unsubscribed','failed') DEFAULT 'sent',
            `metadata` JSON, `ip_address` VARCHAR(45), `user_agent` TEXT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX(`email_id`), INDEX(`sequence_id`), INDEX(`event`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'lead_scoring' => "CREATE TABLE IF NOT EXISTS `lead_scoring` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `lead_id` INT NOT NULL,
            `score_total` INT DEFAULT 0, `score_budget` INT DEFAULT 0, `score_authority` INT DEFAULT 0,
            `score_need` INT DEFAULT 0, `score_timing` INT DEFAULT 0,
            `grade` ENUM('A','B','C','D','F') DEFAULT 'F', `notes` TEXT, `last_activity` DATETIME,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY(`lead_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'appointments' => "CREATE TABLE IF NOT EXISTS `appointments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `contact_id` INT, `lead_id` INT,
            `title` VARCHAR(255) NOT NULL, `description` TEXT,
            `type` ENUM('visite','estimation','rdv_agence','signature','appel','autre') DEFAULT 'autre',
            `location` VARCHAR(500), `start_at` DATETIME NOT NULL, `end_at` DATETIME,
            `status` ENUM('scheduled','confirmed','completed','cancelled','no_show') DEFAULT 'scheduled',
            `reminder_sent` TINYINT(1) DEFAULT 0, `notes` TEXT, `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'gmb_prospects' => "CREATE TABLE IF NOT EXISTS `gmb_prospects` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `business_name` VARCHAR(255) NOT NULL,
            `category` VARCHAR(255), `address` VARCHAR(500), `phone` VARCHAR(50),
            `email` VARCHAR(255), `website` VARCHAR(500), `rating` DECIMAL(2,1),
            `reviews_count` INT DEFAULT 0, `gmb_url` VARCHAR(500),
            `status` ENUM('new','contacted','interested','not_interested','converted') DEFAULT 'new',
            `notes` TEXT, `sequence_id` INT, `last_contacted` DATETIME,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'estimations' => "CREATE TABLE IF NOT EXISTS `estimations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `lead_id` INT, `contact_id` INT,
            `property_type` ENUM('appartement','maison','terrain','commerce','autre') DEFAULT 'appartement',
            `address` VARCHAR(500), `city` VARCHAR(100) DEFAULT 'Bordeaux', `postal_code` VARCHAR(10),
            `surface` INT, `rooms` INT, `bedrooms` INT, `floor` INT, `parking` TINYINT DEFAULT 0,
            `condition_state` ENUM('neuf','bon','moyen','a_renover') DEFAULT 'bon',
            `estimated_price_low` DECIMAL(12,2), `estimated_price_high` DECIMAL(12,2), `estimated_price_avg` DECIMAL(12,2),
            `bant_score` INT, `status` ENUM('pending','completed','contacted','converted') DEFAULT 'pending',
            `notes` TEXT, `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'menus' => "CREATE TABLE IF NOT EXISTS `menus` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `name` VARCHAR(255) NOT NULL, `slug` VARCHAR(255),
            `location` ENUM('header','footer','sidebar','mobile') DEFAULT 'header',
            `items` JSON, `status` ENUM('active','draft') DEFAULT 'active',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'financing' => "CREATE TABLE IF NOT EXISTS `financing` (
            `id` INT AUTO_INCREMENT PRIMARY KEY, `lead_id` INT, `contact_id` INT,
            `loan_amount` DECIMAL(12,2), `duration_months` INT DEFAULT 240, `interest_rate` DECIMAL(4,2),
            `monthly_payment` DECIMAL(10,2), `income` DECIMAL(12,2),
            `status` ENUM('simulation','submitted','approved','rejected') DEFAULT 'simulation',
            `notes` TEXT, `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}
