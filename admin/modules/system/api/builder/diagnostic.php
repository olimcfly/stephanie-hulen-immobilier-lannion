<?php
/**
 * /admin/api/builder/diagnostic.php
 * Diagnostic complet : DB, tables, colonnes, save test
 */

if (!defined('ADMIN_ROUTER')) define('ADMIN_ROUTER', true);

require_once dirname(__DIR__, 3) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

$isAuth = !empty($_SESSION['admin_logged_in'])
       || !empty($_SESSION['user_id'])
       || !empty($_SESSION['admin_id'])
       || !empty($_SESSION['logged_in'])
       || !empty($_SESSION['is_admin']);

if (!$isAuth) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorise']);
    exit;
}

$results = [];
$globalOk = true;

// ── 1. Connexion DB ───────────────────────────────────────────────────────────
try {
    $pdo = getDB();
    $pdo->query('SELECT 1');
    $results['db_connection'] = ['ok' => true, 'label' => 'Connexion base de donnees', 'detail' => 'PDO connecte'];
} catch (Exception $e) {
    $results['db_connection'] = ['ok' => false, 'label' => 'Connexion base de donnees', 'detail' => $e->getMessage()];
    $globalOk = false;
    echo json_encode(['success' => false, 'global_ok' => false, 'results' => $results]);
    exit;
}

// ── 2. Nom de la DB + version MySQL ──────────────────────────────────────────
try {
    $dbName  = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    $results['db_info'] = ['ok' => true, 'label' => 'Serveur MySQL', 'detail' => "DB: $dbName | MySQL $version"];
} catch (Exception $e) {
    $results['db_info'] = ['ok' => false, 'label' => 'Infos DB', 'detail' => $e->getMessage()];
}

// ── 3. Tables du builder ──────────────────────────────────────────────────────
$builderTables = [
    'pages'         => ['content', 'custom_css', 'custom_js', 'slug', 'status', 'title'],
    'articles'      => ['content', 'custom_css', 'custom_js', 'slug', 'status', 'title'],
    'secteurs'      => ['content', 'custom_css', 'custom_js', 'slug', 'status', 'nom'],
    'guide_local'   => ['contenu', 'custom_css', 'custom_js', 'slug', 'statut', 'titre'],
    'headers'       => ['custom_html', 'custom_css', 'custom_js', 'name', 'status'],
    'footers'       => ['custom_html', 'custom_css', 'custom_js', 'name', 'status'],
    'capture_pages' => ['content', 'custom_css', 'custom_js', 'slug', 'status', 'name'],
];

$tableCount  = 0;
$tableTotal  = count($builderTables);
$tableErrors = [];

foreach ($builderTables as $table => $requiredCols) {
    try {
        // Vérifier existence table
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if (!$stmt->fetchColumn()) {
            $tableErrors[] = "$table (manquante)";
            $results['table_'.$table] = ['ok' => false, 'label' => "Table `$table`", 'detail' => 'Table absente'];
            continue;
        }
        // Vérifier colonnes
        $cols = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN);
        $missing = array_diff($requiredCols, $cols);
        $count   = (int) $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        if ($missing) {
            $tableErrors[] = "$table (colonnes manquantes: ".implode(', ',$missing).")";
            $results['table_'.$table] = ['ok' => false, 'label' => "Table `$table`", 'detail' => "Colonnes absentes: ".implode(', ',$missing)." | $count lignes"];
        } else {
            $tableCount++;
            $results['table_'.$table] = ['ok' => true, 'label' => "Table `$table`", 'detail' => "$count ligne(s) | ".count($cols)." colonnes"];
        }
    } catch (Exception $e) {
        $tableErrors[] = "$table (".$e->getMessage().")";
        $results['table_'.$table] = ['ok' => false, 'label' => "Table `$table`", 'detail' => $e->getMessage()];
    }
}

$results['tables_summary'] = [
    'ok'     => $tableCount === $tableTotal,
    'label'  => 'Tables builder',
    'detail' => "$tableCount / $tableTotal tables OK" . ($tableErrors ? " | Erreurs: ".implode('; ', $tableErrors) : ''),
];
if ($tableCount < $tableTotal) $globalOk = false;

// ── 4. Table settings ─────────────────────────────────────────────────────────
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->fetchColumn()) {
        $settingCount = (int) $pdo->query("SELECT COUNT(*) FROM settings")->fetchColumn();
        $results['settings'] = ['ok' => true, 'label' => 'Table `settings`', 'detail' => "$settingCount parametre(s)"];
    } else {
        $results['settings'] = ['ok' => false, 'label' => 'Table `settings`', 'detail' => 'Absente (optionnelle)'];
    }
} catch (Exception $e) {
    $results['settings'] = ['ok' => false, 'label' => 'Table `settings`', 'detail' => $e->getMessage()];
}

// ── 5. Test de sauvegarde (dry-run sur pages) ─────────────────────────────────
try {
    $testRow = $pdo->query("SELECT id, title FROM pages LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($testRow) {
        // On reécrit exactement la même valeur — aucun changement réel
        $stmt = $pdo->prepare("UPDATE pages SET updated_at = updated_at WHERE id = ?");
        $stmt->execute([$testRow['id']]);
        $results['save_test'] = ['ok' => true, 'label' => 'Test sauvegarde', 'detail' => "UPDATE OK sur pages#".$testRow['id']." (".$testRow['title'].")"];
    } else {
        $results['save_test'] = ['ok' => true, 'label' => 'Test sauvegarde', 'detail' => 'Table pages vide — aucune ligne a tester'];
    }
} catch (Exception $e) {
    $results['save_test'] = ['ok' => false, 'label' => 'Test sauvegarde', 'detail' => $e->getMessage()];
    $globalOk = false;
}

// ── 6. Fichiers API builder ───────────────────────────────────────────────────
$apiFiles = [
    'save-content.php' => dirname(__DIR__) . '/builder/save-content.php',
    'diagnostic.php'   => dirname(__DIR__) . '/builder/diagnostic.php',
    'clone-design.php' => dirname(__DIR__) . '/builder/clone-design.php',
    'generate.php (IA)'=> dirname(dirname(__DIR__)) . '/api/ai/generate.php',
];
foreach ($apiFiles as $label => $path) {
    $exists = file_exists($path);
    $results['file_'.preg_replace('/[^a-z0-9]/','_',strtolower($label))] = [
        'ok'     => $exists,
        'label'  => "Fichier $label",
        'detail' => $exists ? $path : "ABSENT : $path",
    ];
    if (!$exists && $label === 'save-content.php') $globalOk = false;
}

// ── 7. PHP / extensions ───────────────────────────────────────────────────────
$results['php_version'] = [
    'ok'     => version_compare(PHP_VERSION, '7.4', '>='),
    'label'  => 'Version PHP',
    'detail' => PHP_VERSION . (version_compare(PHP_VERSION, '8.0', '>=') ? ' ✓ PHP 8+' : ' (recommande PHP 8+)'),
];
$results['pdo_ext'] = [
    'ok'     => extension_loaded('pdo_mysql'),
    'label'  => 'Extension PDO MySQL',
    'detail' => extension_loaded('pdo_mysql') ? 'Chargee' : 'MANQUANTE',
];
$results['json_ext'] = [
    'ok'     => extension_loaded('json'),
    'label'  => 'Extension JSON',
    'detail' => extension_loaded('json') ? 'Chargee' : 'MANQUANTE',
];
$results['mbstring'] = [
    'ok'     => extension_loaded('mbstring'),
    'label'  => 'Extension mbstring',
    'detail' => extension_loaded('mbstring') ? 'Chargee' : 'ABSENTE (optionnelle)',
];

// ── 8. Résumé global ──────────────────────────────────────────────────────────
$okCount  = count(array_filter($results, fn($r) => $r['ok']));
$totCount = count($results);

echo json_encode([
    'success'    => true,
    'global_ok'  => $globalOk,
    'score'      => "$okCount / $totCount",
    'results'    => $results,
    'timestamp'  => date('d/m/Y H:i:s'),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);