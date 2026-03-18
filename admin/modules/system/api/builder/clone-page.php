<?php
/**
 * /admin/api/builder/clone-page.php
 * Retourne le HTML+CSS+JS d'une page existante pour clonage dans le builder
 */
if (!defined('ADMIN_ROUTER')) define('ADMIN_ROUTER', true);
require_once dirname(__DIR__, 3) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

$isAuth = !empty($_SESSION['admin_logged_in'])||!empty($_SESSION['user_id'])||!empty($_SESSION['admin_id'])||!empty($_SESSION['logged_in'])||!empty($_SESSION['is_admin']);
if (!$isAuth) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'Non autorise']); exit; }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { echo json_encode(['success'=>false,'error'=>'ID manquant']); exit; }

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT title, content, custom_css, custom_js, slug FROM pages WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$page) { echo json_encode(['success'=>false,'error'=>"Page #$id introuvable"]); exit; }

    echo json_encode([
        'success' => true,
        'html'    => $page['content']    ?? '',
        'css'     => $page['custom_css'] ?? '',
        'js'      => $page['custom_js']  ?? '',
        'title'   => $page['title']      ?? '',
        'slug'    => $page['slug']        ?? '',
    ]);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}