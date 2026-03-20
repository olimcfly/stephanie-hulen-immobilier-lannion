<?php
/**
 * LOGOUT ADMIN
 * /admin/logout.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* audit log avant destruction session */
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';
require_once ROOT_PATH . '/includes/functions/helpers.php';
auditLog('logout', 'admin', (int)($_SESSION['admin_id'] ?? 0), ['email' => $_SESSION['admin_email'] ?? '']);

/* vider la session */
$_SESSION = [];

/* détruire session */
session_destroy();

/* supprimer cookie session */
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );

}

/* redirection login */

header("Location: /admin/login.php");
exit;