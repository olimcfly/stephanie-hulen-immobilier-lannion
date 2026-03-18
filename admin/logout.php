<?php
/**
 * LOGOUT ADMIN
 * /admin/logout.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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