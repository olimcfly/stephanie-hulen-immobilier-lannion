<?php
/**
 * INDEX.PHP - PAGE D'ACCUEIL
 * stephanie-hulen-immobilier-lannion.fr
 */

// ═══════════════════════════════════════════════════════════
// 1. DÉFINIR ROOT_PATH
// ═══════════════════════════════════════════════════════════
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__);
}

// ═══════════════════════════════════════════════════════════
// 2. CHARGER CONFIG
// ═══════════════════════════════════════════════════════════
$config_path = ROOT_PATH . '/config/config.php';

if (!file_exists($config_path)) {
    http_response_code(500);
    die('❌ ERREUR: /config/config.php manquant');
}

try {
    require_once $config_path;
} catch (Throwable $e) {
    http_response_code(500);
    die('❌ ERREUR config.php: ' . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════
// 3. MAINTENANCE CHECK
// ═══════════════════════════════════════════════════════════
$maintenancePath = ROOT_PATH . '/includes/maintenance-check.php';

if (file_exists($maintenancePath)) {
    require_once $maintenancePath;
}

// ═══════════════════════════════════════════════════════════
// 4. PAGE D'ERREUR DB
// ═══════════════════════════════════════════════════════════
if (!function_exists('renderDatabaseErrorPage')) {
    function renderDatabaseErrorPage($dbErrorMessage = '')
    {
        http_response_code(500);

        $safeError   = htmlspecialchars((string) $dbErrorMessage, ENT_QUOTES, 'UTF-8');
        $safeDbHost  = htmlspecialchars(DB_HOST, ENT_QUOTES, 'UTF-8');
        $safeDbName  = htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8');
        $safeDbUser  = htmlspecialchars(DB_USER, ENT_QUOTES, 'UTF-8');

        $technicalBlock = '';
        if (DEBUG_MODE && !empty($safeError)) {
            $technicalBlock = '
                <div class="debug-box">
                    <h3>Détail technique</h3>
                    <code>' . $safeError . '</code>
                </div>
            ';
        }

        echo '<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Erreur connexion base de données</title>
<style>
*{box-sizing:border-box}
body{margin:0;background:#f5f7fb;color:#1f2937;font-family:Arial}
.wrap{max-width:900px;margin:60px auto;padding:20px}
.card{background:#fff;border-radius:16px;padding:32px;
box-shadow:0 10px 30px rgba(0,0,0,.08);border:1px solid #e5e7eb}
.badge{display:inline-block;padding:8px 12px;background:#fee2e2;
color:#991b1b;border-radius:999px;font-size:14px;font-weight:bold;margin-bottom:16px}
h1{margin:0 0 12px;font-size:30px}
p{font-size:16px;line-height:1.6}
.box{background:#f9fafb;border:1px solid #e5e7eb;border-radius:12px;padding:18px;margin-top:22px}
ul{padding-left:20px;line-height:1.8}
code{background:#111827;color:#f9fafb;display:block;padding:14px;border-radius:10px}
.debug-box{margin-top:24px;background:#fff7ed;border:1px solid #fdba74;border-radius:12px;padding:18px}
</style>
</head>
<body>
<div class="wrap">
<div class="card">

<div class="badge">❌ Connexion base de données impossible</div>
<h1>Le site ne peut pas démarrer pour le moment</h1>

<p>
Le CMS a bien trouvé le fichier de configuration,
mais il n\'arrive pas à se connecter à la base MySQL.
</p>

<div class="box">
<h2>À vérifier dans /config/config.php</h2>
<ul>
<li>DB_HOST</li>
<li>DB_NAME</li>
<li>DB_USER</li>
<li>DB_PASS</li>
</ul>
</div>

<div class="box">
<h2>Configuration lue</h2>
<ul>
<li><strong>Host :</strong> ' . $safeDbHost . '</li>
<li><strong>Base :</strong> ' . $safeDbName . '</li>
<li><strong>User :</strong> ' . $safeDbUser . '</li>
</ul>
</div>

' . $technicalBlock . '

</div>
</div>
</body>
</html>';

        exit;
    }
}

// ═══════════════════════════════════════════════════════════
// 5. TEST CONNEXION DB
// ═══════════════════════════════════════════════════════════
try {
    getDB(); // Teste la connexion
} catch (Throwable $e) {
    renderDatabaseErrorPage($e->getMessage());
}
// ═══════════════════════════════════════════════════════════
// 6. CHARGER LE ROUTER
// ═══════════════════════════════════════════════════════════
define('FRONT_ROUTER', true);

$_GET['_uri'] = 'accueil';

$routerPath = ROOT_PATH . '/front/page.php';

if (file_exists($routerPath)) {
    require $routerPath;
    exit;
}

http_response_code(500);
die('❌ ERREUR: /front/router.php manquant');