<?php
/**
 * /includes/init.php — Init simple
 */
if (defined('ADMIN_INIT_LOADED')) return;
define('ADMIN_INIT_LOADED', true);

require_once dirname(dirname(__FILE__)) . '/config/config.php';

// Auth check (sauf login.php)
$public_pages = ['login.php', 'diag-pages.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!in_array($current_page, $public_pages)) {
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
}
?>
