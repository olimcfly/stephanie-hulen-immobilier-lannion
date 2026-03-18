<?php
session_start();
require_once dirname(__DIR__) . '/config/config.php';
spl_autoload_register(function ($class) {
    $file = INCLUDES_PATH . '/classes/' . $class . '.php';
    if (file_exists($file)) require_once $file;
});
require_once INCLUDES_PATH . '/functions/helpers.php';
require_once INCLUDES_PATH . '/functions/security.php';
require_once INCLUDES_PATH . '/classes/Section.php';

$db = Database::getInstance();
