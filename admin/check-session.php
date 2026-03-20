<?php
require_once dirname(__DIR__) . '/includes/autoload.php';

if (!is_admin_logged_in()) {
    redirect('/admin/login.php');
}

/* Timeout de session : détruire si inactivité > SESSION_TIMEOUT */
if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    redirect('/admin/login.php');
}

/* Mettre à jour le timestamp d'activité */
$_SESSION['last_activity'] = time();
