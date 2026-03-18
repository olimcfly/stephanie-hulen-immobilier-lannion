<?php
require_once dirname(__DIR__) . '/includes/autoload.php';
if (!is_admin_logged_in()) redirect('/admin/login.php');
if (time() - ($_SESSION['admin_login_time'] ?? 0) > SESSION_TIMEOUT) { session_destroy(); redirect('/admin/login.php'); }
$_SESSION['admin_login_time'] = time();
