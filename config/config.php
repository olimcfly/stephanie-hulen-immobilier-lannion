<?php
/**
 * /config/config.php — CONFIG CENTRALISÉE
 */

define('INSTANCE_ID',   'stephanie-lannion');
define('SITE_TITLE',    'Stephanie Hulen - Lannion');
define('SITE_DOMAIN',   'stephanie-hulen-immobilier-lannion.fr');
define('ADMIN_EMAIL',   'admin@stephanie-hulen-immobilier-lannion.fr');

define('DB_HOST',    'localhost');
define('DB_NAME',    'cool1933_cms-site-sh-lanion');
define('DB_USER',    'cool1933_cms-sh-lannion');
define('DB_PASS',    'qxJ4ij(22dmV');
define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        die('Erreur DB: ' . $e->getMessage());
    }
    
    return $pdo;
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

define('ADMIN_PATH',    ROOT_PATH . '/admin');
define('INCLUDES_PATH', ROOT_PATH . '/includes');

$domain = $_SERVER['HTTP_HOST'] ?? SITE_DOMAIN;
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
define('SITE_URL', $protocol . $domain);
define('ADMIN_URL', SITE_URL . '/admin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function sanitize($input, $type = 'string') {
    if ($type === 'email') return filter_var($input, FILTER_SANITIZE_EMAIL);
    if ($type === 'int')   return (int)$input;
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

define('DEBUG_MODE', true);
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

?>
