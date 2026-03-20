<?php
/**
 * /admin/includes/init.php
 * Initialisation Admin
 */

if (defined('ADMIN_INIT_LOADED')) return;
define('ADMIN_INIT_LOADED', true);


/* SESSION */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/* CONFIG */

$configPath = dirname(dirname(__DIR__)) . '/config/config.php';

if (!file_exists($configPath)) {
    http_response_code(500);
    die('Erreur : config/config.php introuvable');
}

require_once $configPath;


/* AUTH ADMIN */

if (!defined('ADMIN_API')) {

    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }

    /* Timeout de session admin */
    if (defined('SESSION_TIMEOUT') && time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: /admin/login.php');
        exit;
    }

    $_SESSION['last_activity'] = time();

}


/* CSRF */

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


/* DB */

try {

    $pdo = getDB();
    $db  = $pdo;

} catch (Exception $e) {

    http_response_code(500);
    die('Erreur connexion base');

}


/* CONSTANTES */

if (!defined('ADMIN_ROUTER')) {
    define('ADMIN_ROUTER', true);
}


/* INFOS ADMIN */

$adminName = $_SESSION['admin_name']
    ?? $_SESSION['admin_email']
    ?? 'Admin';

$adminInitial = strtoupper(substr($adminName, 0, 1));

$adminId = $_SESSION['admin_id'] ?? null;