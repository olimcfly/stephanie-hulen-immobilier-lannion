<?php
/**
 * API — Tiktok : Mise à jour statut
 * admin/api/social/tiktok-update-status.php
 *
 * Ancienne position : modules/social/tiktok/api/update-status.php
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
    $pdo    = Database::getInstance();
    $input  = json_decode(file_get_contents('php://input'), true);

    $id     = (int)($input['id'] ?? 0);
    $statut = $input['statut'] ?? '';

    $allowed = ['brouillon', 'prêt', 'publié', 'archivé'];
    if (!$id || !in_array($statut, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Paramètres invalides']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE tiktok_scripts SET statut=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$statut, $id]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('[API tiktok-update-status] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}