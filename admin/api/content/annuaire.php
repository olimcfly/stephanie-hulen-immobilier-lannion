<?php
/**
 * /admin/api/content/annuaire.php
 * API Annuaire — delete, toggle_status, toggle_featured, bulk actions
 */

header('Content-Type: application/json; charset=utf-8');

// ── Init ──
require_once dirname(__DIR__, 3) . '/includes/init.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

try {
    $pdo = getDB();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur BD']);
    exit;
}

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'error' => 'Méthode non autorisée'], 405);
}

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);

// ── Delete ──
if ($action === 'delete' && $id > 0) {
    try {
        $pdo->prepare("DELETE FROM annuaire WHERE id = ?")->execute([$id]);
        respond(['success' => true]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// ── Toggle status ──
if ($action === 'toggle_status' && $id > 0) {
    $newStatus = $_POST['status'] ?? null;
    try {
        if ($newStatus && in_array($newStatus, ['published', 'draft'], true)) {
            $pdo->prepare("UPDATE annuaire SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
        } else {
            $pdo->prepare("UPDATE annuaire SET status = IF(status='published','draft','published') WHERE id = ?")->execute([$id]);
        }
        respond(['success' => true]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// ── Toggle featured ──
if ($action === 'toggle_featured' && $id > 0) {
    try {
        $pdo->prepare("UPDATE annuaire SET is_featured = IF(is_featured=1,0,1) WHERE id = ?")->execute([$id]);
        respond(['success' => true]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// ── Bulk delete ──
if ($action === 'bulk_delete') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (!is_array($ids) || empty($ids)) {
        respond(['success' => false, 'error' => 'Aucun ID fourni'], 400);
    }
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM annuaire WHERE id IN ($placeholders)")->execute(array_map('intval', $ids));
        respond(['success' => true]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// ── Bulk status ──
if ($action === 'bulk_status') {
    $ids    = json_decode($_POST['ids'] ?? '[]', true);
    $status = $_POST['status'] ?? '';
    if (!is_array($ids) || empty($ids) || !in_array($status, ['published', 'draft'], true)) {
        respond(['success' => false, 'error' => 'Paramètres invalides'], 400);
    }
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$status], array_map('intval', $ids));
        $pdo->prepare("UPDATE annuaire SET status = ? WHERE id IN ($placeholders)")->execute($params);
        respond(['success' => true]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// ── Bulk feature ──
if ($action === 'bulk_feature') {
    $ids = json_decode($_POST['ids'] ?? '[]', true);
    if (!is_array($ids) || empty($ids)) {
        respond(['success' => false, 'error' => 'Aucun ID fourni'], 400);
    }
    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE annuaire SET is_featured = 1 WHERE id IN ($placeholders)")->execute(array_map('intval', $ids));
        respond(['success' => true]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

respond(['success' => false, 'error' => 'Action inconnue: ' . $action], 400);
