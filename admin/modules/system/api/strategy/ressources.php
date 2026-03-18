<?php
/**
 * API — Ressources stratégie
 * admin/api/strategy/ressources.php
 *
 * Ancienne position : modules/strategy/strategy/ressources/api.php
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
    $action = $input['action'] ?? $_GET['action'] ?? 'list';

    switch ($action) {

        case 'list':
            $cat  = $input['categorie'] ?? '';
            $sql  = "SELECT * FROM ressources";
            $params = [];
            if ($cat) { $sql .= " WHERE categorie=?"; $params[] = $cat; }
            $sql .= " ORDER BY ordre ASC, created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'save':
            $id        = (int)($input['id'] ?? 0);
            $titre     = trim($input['titre']     ?? '');
            $contenu   = trim($input['contenu']   ?? '');
            $categorie = trim($input['categorie'] ?? 'general');
            $type      = trim($input['type']      ?? 'guide');
            $url       = trim($input['url']       ?? '');
            $ordre     = (int)($input['ordre']    ?? 0);

            if (!$titre) {
                echo json_encode(['success' => false, 'error' => 'Titre requis']);
                break;
            }

            if ($id) {
                $pdo->prepare(
                    "UPDATE ressources SET titre=?,contenu=?,categorie=?,type=?,url=?,ordre=?,updated_at=NOW() WHERE id=?"
                )->execute([$titre, $contenu, $categorie, $type, $url, $ordre, $id]);
            } else {
                $pdo->prepare(
                    "INSERT INTO ressources (titre,contenu,categorie,type,url,ordre,created_at) VALUES (?,?,?,?,?,?,NOW())"
                )->execute([$titre, $contenu, $categorie, $type, $url, $ordre]);
                $id = $pdo->lastInsertId();
            }
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'delete':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID manquant']); break; }
            $pdo->prepare("DELETE FROM ressources WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    }

} catch (Exception $e) {
    error_log('[API ressources] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}