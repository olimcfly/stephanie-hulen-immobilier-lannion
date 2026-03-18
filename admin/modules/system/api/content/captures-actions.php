<?php
/**
 * API — Pages de capture : Actions AJAX
 * admin/api/content/captures-actions.php
 *
 * Ancienne position : modules/content/pages-capture/api.php
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
    $input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $action = $input['action'] ?? $_GET['action'] ?? '';

    switch ($action) {

        case 'list':
            $pages = $pdo->query(
                "SELECT id, titre, slug, statut, conversions, views, created_at
                 FROM capture_pages ORDER BY created_at DESC"
            )->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $pages]);
            break;

        case 'get':
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID manquant']); break; }
            $page = $pdo->prepare("SELECT * FROM capture_pages WHERE id=?")->execute([$id]);
            $page = $pdo->prepare("SELECT * FROM capture_pages WHERE id=?");
            $page->execute([$id]);
            $data = $page->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => (bool)$data, 'data' => $data]);
            break;

        case 'toggle_statut':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID manquant']); break; }
            $current = $pdo->prepare("SELECT statut FROM capture_pages WHERE id=?");
            $current->execute([$id]);
            $row = $current->fetch(PDO::FETCH_ASSOC);
            $new = ($row['statut'] ?? 'inactif') === 'actif' ? 'inactif' : 'actif';
            $pdo->prepare("UPDATE capture_pages SET statut=? WHERE id=?")->execute([$new, $id]);
            echo json_encode(['success' => true, 'statut' => $new]);
            break;

        case 'delete':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID manquant']); break; }
            $pdo->prepare("DELETE FROM capture_pages WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'stats':
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID manquant']); break; }
            $stmt = $pdo->prepare(
                "SELECT views, conversions,
                        ROUND(IF(views>0, conversions/views*100, 0), 1) AS taux_conversion
                 FROM capture_pages WHERE id=?"
            );
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            break;

        case 'increment_view':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false]); break; }
            $pdo->prepare("UPDATE capture_pages SET views = views+1 WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'increment_conversion':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false]); break; }
            $pdo->prepare("UPDATE capture_pages SET conversions = conversions+1 WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue: ' . htmlspecialchars($action)]);
    }

} catch (Exception $e) {
    error_log('[API captures-actions] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}