<?php
/**
 * Handler captures - dispatche vers le bon fichier selon action
 * /admin/core/handlers/captures.php
 *
 * Appelé par dashboard.php quand ?page=captures
 */

$action = $_GET['action'] ?? $_POST['action'] ?? 'index';
$id     = (int)($_GET['id'] ?? 0);

$baseDir = dirname(__DIR__, 2) . '/modules/content/pages-capture/';

switch ($action) {
    case 'edit':
    case 'create':
        require_once $baseDir . 'edit.php';
        break;

    case 'delete':
        require_once $baseDir . 'delete.php';
        break;

    case 'index':
    default:
        require_once $baseDir . 'index.php';
        break;
}