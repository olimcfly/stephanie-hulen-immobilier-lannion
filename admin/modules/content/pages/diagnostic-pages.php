<?php
/**
 * Diagnostic Pages Module
 * À exécuter depuis le navigateur : /admin/modules/content/pages/diagnostic-pages.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$diagnostics = [];

// ─────────────────────────────────────────────────────
// 1. Vérifier fichiers
// ─────────────────────────────────────────────────────
$requiredFiles = [
    'api.php' => __DIR__ . '/api.php',
    'guide-wizard.php' => __DIR__ . '/guide-wizard.php',
    'index.php' => __DIR__ . '/index.php',
    'edit.php' => __DIR__ . '/edit.php',
    'tpl-definitions.php' => __DIR__ . '/tpl-definitions.php',
    'api/guide-handler.php' => __DIR__ . '/api/guide-handler.php',
    'api/ia/generate.php' => __DIR__ . '/api/ia/generate.php',
];

$diagnostics['files'] = [];
foreach ($requiredFiles as $name => $path) {
    $exists = file_exists($path);
    $size = $exists ? filesize($path) : 0;
    $readable = $exists && is_readable($path);
    $diagnostics['files'][$name] = [
        'exists' => $exists,
        'size' => $size,
        'readable' => $readable,
        'path' => $path,
    ];
}

// ─────────────────────────────────────────────────────
// 2. Vérifier API
// ─────────────────────────────────────────────────────
$diagnostics['api'] = [];

// Test API list
$testUrl = 'http://localhost' . str_replace(realpath(__DIR__), '', realpath(__DIR__)) . '/api.php?action=list';
$testUrl = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$testUrl = str_replace('diagnostic-pages.php', 'api.php?action=list', $testUrl);

$diagnostics['api']['test_url'] = $testUrl;
$diagnostics['api']['session'] = session_status();

// ─────────────────────────────────────────────────────
// 3. Vérifier BD
// ─────────────────────────────────────────────────────
$diagnostics['database'] = [];

@session_start();
foreach ([
    __DIR__.'/../../../config/config.php',
    dirname(__DIR__, 3).'/config/config.php',
    $_SERVER['DOCUMENT_ROOT'].'/config/config.php'
] as $configPath) {
    if (file_exists($configPath)) {
        require_once $configPath;
        break;
    }
}

try {
    if (!isset($pdo)) {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
    
    // Vérifier table pages
    $stmt = $pdo->query("SHOW TABLES LIKE 'pages'");
    $tableExists = $stmt->rowCount() > 0;
    $diagnostics['database']['pages_table_exists'] = $tableExists;
    
    if ($tableExists) {
        // Compter pages
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM pages");
        $result = $stmt->fetch();
        $diagnostics['database']['pages_count'] = $result['cnt'] ?? 0;
        
        // Voir structure
        $stmt = $pdo->query("DESCRIBE pages");
        $columns = $stmt->fetchAll();
        $diagnostics['database']['pages_columns'] = array_column($columns, 'Field');
    }
    
    $diagnostics['database']['connected'] = true;
    
} catch (Throwable $e) {
    $diagnostics['database']['connected'] = false;
    $diagnostics['database']['error'] = $e->getMessage();
}

// ─────────────────────────────────────────────────────
// 4. Vérifier Claude key
// ─────────────────────────────────────────────────────
$diagnostics['ai'] = [];

try {
    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT service, status FROM api_keys WHERE service = 'anthropic' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        $diagnostics['ai']['anthropic_configured'] = !!$row;
        if ($row) {
            $diagnostics['ai']['anthropic_status'] = $row['status'];
        }
    }
    if (defined('ANTHROPIC_API_KEY')) {
        $diagnostics['ai']['anthropic_constant'] = 'DEFINED';
    }
} catch (Throwable $e) {
    $diagnostics['ai']['error'] = $e->getMessage();
}

// ─────────────────────────────────────────────────────
// 5. Test api.php syntax
// ─────────────────────────────────────────────────────
$diagnostics['syntax'] = [];

foreach (['api.php', 'guide-wizard.php'] as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $output = shell_exec("php -l '$path' 2>&1");
        $diagnostics['syntax'][$file] = [
            'valid' => strpos($output, 'No syntax errors') !== false,
            'output' => trim($output),
        ];
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic Pages Module</title>
    <style>
        body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .section { margin: 20px 0; padding: 15px; background: #252526; border-left: 4px solid #007acc; }
        .ok { color: #4ec9b0; }
        .error { color: #f48771; }
        .warn { color: #dcdcaa; }
        h2 { color: #569cd6; margin-top: 0; }
        pre { background: #1e1e1e; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>

<h1>🔍 Diagnostic Pages Module</h1>

<div class="section">
    <h2>📁 Fichiers</h2>
    <?php foreach ($diagnostics['files'] as $name => $info): ?>
        <div>
            <strong><?php echo $name ?></strong>
            <span class="<?php echo $info['exists'] ? 'ok' : 'error' ?>">
                <?php echo $info['exists'] ? '✓ EXISTS' : '✗ MISSING' ?>
            </span>
            (<?php echo $info['size'] ?> bytes)
        </div>
    <?php endforeach; ?>
</div>

<div class="section">
    <h2>🗄️ Base de Données</h2>
    <div><strong>Connected:</strong> <span class="<?php echo $diagnostics['database']['connected'] ? 'ok' : 'error' ?>">
        <?php echo $diagnostics['database']['connected'] ? '✓ YES' : '✗ NO' ?>
    </span></div>
    
    <?php if ($diagnostics['database']['connected']): ?>
        <div><strong>Pages table:</strong> <span class="<?php echo $diagnostics['database']['pages_table_exists'] ? 'ok' : 'error' ?>">
            <?php echo $diagnostics['database']['pages_table_exists'] ? '✓ EXISTS' : '✗ MISSING' ?>
        </span></div>
        
        <?php if ($diagnostics['database']['pages_table_exists']): ?>
            <div><strong>Pages count:</strong> <?php echo $diagnostics['database']['pages_count'] ?></div>
            <div><strong>Columns:</strong> <?php echo implode(', ', $diagnostics['database']['pages_columns']) ?></div>
        <?php endif; ?>
    <?php else: ?>
        <div class="error">Error: <?php echo $diagnostics['database']['error'] ?></div>
    <?php endif; ?>
</div>

<div class="section">
    <h2>🤖 IA / Claude</h2>
    <div><strong>Anthropic configured:</strong> <span class="<?php echo $diagnostics['ai']['anthropic_configured'] ? 'ok' : 'warn' ?>">
        <?php echo $diagnostics['ai']['anthropic_configured'] ? '✓ YES (in DB)' : '⚠ NO (check config)' ?>
    </span></div>
    <div><strong>ANTHROPIC_API_KEY constant:</strong> <span class="<?php echo isset($diagnostics['ai']['anthropic_constant']) ? 'ok' : 'warn' ?>">
        <?php echo isset($diagnostics['ai']['anthropic_constant']) ? '✓ DEFINED' : '⚠ NOT DEFINED' ?>
    </span></div>
</div>

<div class="section">
    <h2>✓ Syntax Check</h2>
    <?php foreach ($diagnostics['syntax'] as $file => $result): ?>
        <div>
            <strong><?php echo $file ?>:</strong>
            <span class="<?php echo $result['valid'] ? 'ok' : 'error' ?>">
                <?php echo $result['valid'] ? '✓ VALID' : '✗ SYNTAX ERROR' ?>
            </span>
            <?php if (!$result['valid']): ?>
                <pre><?php echo $result['output'] ?></pre>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<div class="section">
    <h2>📋 Debug Info</h2>
    <pre><?php echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?></pre>
</div>

</body>
</html>