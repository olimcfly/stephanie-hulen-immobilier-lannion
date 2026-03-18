<?php
/**
 * API — Scoring prospects : Actions AJAX
 * admin/api/marketing/scoring-actions.php
 *
 * Ancienne position : modules/marketing/scoring/api.php
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

        case 'get_rules':
            $rules = $pdo->query("SELECT * FROM scoring_rules ORDER BY points DESC")->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $rules]);
            break;

        case 'save_rule':
            $label     = trim($input['label']     ?? '');
            $condition = trim($input['condition']  ?? '');
            $points    = (int)($input['points']   ?? 0);
            $id        = (int)($input['id']       ?? 0);

            if (!$label || !$condition) {
                echo json_encode(['success' => false, 'error' => 'Champs requis manquants']);
                break;
            }

            if ($id) {
                $pdo->prepare("UPDATE scoring_rules SET label=?, condition_type=?, points=? WHERE id=?")
                    ->execute([$label, $condition, $points, $id]);
            } else {
                $pdo->prepare("INSERT INTO scoring_rules (label, condition_type, points) VALUES (?,?,?)")
                    ->execute([$label, $condition, $points]);
                $id = $pdo->lastInsertId();
            }
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'delete_rule':
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID manquant']); break; }
            $pdo->prepare("DELETE FROM scoring_rules WHERE id=?")->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'recalculate':
            // Recalcul des scores pour tous les leads
            $leads = $pdo->query("SELECT id FROM leads")->fetchAll(PDO::FETCH_COLUMN);
            $rules = $pdo->query("SELECT * FROM scoring_rules")->fetchAll(PDO::FETCH_ASSOC);
            $updated = 0;
            foreach ($leads as $lead_id) {
                $score = 0;
                foreach ($rules as $rule) {
                    // Logique de scoring basique — à adapter selon la structure
                    $score += (int)$rule['points'];
                }
                $pdo->prepare("UPDATE leads SET score=? WHERE id=?")->execute([$score, $lead_id]);
                $updated++;
            }
            echo json_encode(['success' => true, 'updated' => $updated]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Action inconnue: ' . htmlspecialchars($action)]);
    }

} catch (Exception $e) {
    error_log('[API scoring-actions] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur serveur']);
}