<?php
/**
 * API — Tiktok : Sauvegarde script
 * admin/api/social/tiktok-save-script.php
 *
 * Ancienne position : modules/social/tiktok/api/save-script.php
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

define('ADMIN_ROUTER', true);
require_once dirname(__DIR__, 3) . '/config/config.php';
if (!class_exists('Database')) {
    require_once dirname(__DIR__, 3) . '/includes/classes/Database.php';
}

header('Content-Type: application/json');

try {
    $pdo   = Database::getInstance();
    $input = json_decode(file_get_contents('php://input'), true);

    $id      = (int)($input['id'] ?? 0);
    $script  = trim($input['script'] ?? '');
    $titre   = trim($input['titre'] ?? '');
    $statut  = in_array($input['statut'] ?? '', ['brouillon','prêt','publié']) ? $input['statut'] : 'brouillon';

    if (!$id && !$titre) {
        echo json_encode(['success' => false, 'error' => 'Données manquantes']);
        exit;
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE tiktok_scripts SET titre=?, script=?, statut=?, updated_at=NOW() WHERE id=?");
        $stmt->execute([$titre, $script, $statut, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO tiktok_scripts (titre, script, statut, created_at) VALUES (?,?,?,NOW())");
        $stmt->execute([$titre, $script, $statut]);
        $id = $pdo->lastInsertId();
    }

    echo json_encode(['success' => true, 'id' => $id]);

} catch (Exception $e) {
    error_log('[API tiktok-save-script] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}