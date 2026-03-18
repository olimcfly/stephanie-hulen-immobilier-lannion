<?php
/**
 * MODULE ADMIN — Guides & Ressources — API
 * /admin/modules/content/guides/api.php
 * Endpoints AJAX pour actions : delete, toggle_status, duplicate, bulk actions
 */

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

header('Content-Type: application/json; charset=utf-8');

// ─── Vérifier AJAX ───
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
}

$action = $_POST['action'] ?? '';
$result = ['success' => false, 'error' => 'Unknown action'];

try {
    switch ($action) {

        // ══════════════════════════════════════════════════════════
        // DELETE — Supprimer un guide
        // ══════════════════════════════════════════════════════════
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID requis');

            // Récupérer infos avant suppression
            $stmt = $pdo->prepare("SELECT title FROM guides WHERE id = ?");
            $stmt->execute([$id]);
            $guide = $stmt->fetch();
            if (!$guide) throw new Exception('Guide introuvable');

            // Supprimer
            $stmt = $pdo->prepare("DELETE FROM guides WHERE id = ?");
            $stmt->execute([$id]);

            writeLog('guides', "Guide supprimé: {$guide['title']}", 'delete', ['guide_id' => $id]);
            $result = ['success' => true, 'message' => 'Guide supprimé'];
            break;

        // ══════════════════════════════════════════════════════════
        // TOGGLE_STATUS — Activer/Désactiver un guide
        // ══════════════════════════════════════════════════════════
        case 'toggle_status':
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            if (!$id || !in_array($status, ['active', 'inactive'])) {
                throw new Exception('Paramètres invalides');
            }

            // Récupérer infos avant modif
            $stmt = $pdo->prepare("SELECT title, status FROM guides WHERE id = ?");
            $stmt->execute([$id]);
            $guide = $stmt->fetch();
            if (!$guide) throw new Exception('Guide introuvable');

            // Mettre à jour
            $stmt = $pdo->prepare("UPDATE guides SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);

            $action_txt = $status === 'active' ? 'activé' : 'désactivé';
            writeLog('guides', "Guide {$action_txt}: {$guide['title']}", 'update', ['guide_id' => $id, 'status' => $status]);
            $result = ['success' => true, 'message' => 'Statut mis à jour', 'status' => $status];
            break;

        // ══════════════════════════════════════════════════════════
        // DUPLICATE — Dupliquer un guide
        // ══════════════════════════════════════════════════════════
        case 'duplicate':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID requis');

            // Récupérer le guide original
            $stmt = $pdo->prepare("SELECT * FROM guides WHERE id = ?");
            $stmt->execute([$id]);
            $original = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$original) throw new Exception('Guide introuvable');

            // Générer nouveau slug (ajouter -copy-timestamp)
            $timestamp = date('YmdHis');
            $newSlug = $original['slug'] . '-copy-' . $timestamp;

            // Vérifier l'unicité
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM guides WHERE slug = ?");
            $stmt->execute([$newSlug]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception('Slug déjà existant');
            }

            // Dupliquer
            $sql = "INSERT INTO guides 
                (title, slug, description, type, format, niveau, content, headline,
                 file_url, file_size, status, rating, downloads, vues, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'inactive', ?, 0, 0, NOW(), NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $original['title'] . ' (copie)',
                $newSlug,
                $original['description'],
                $original['type'],
                $original['format'],
                $original['niveau'],
                $original['content'],
                $original['headline'],
                $original['file_url'],
                $original['file_size'],
                0 // rating = 0 pour la copie
            ]);

            $newId = $pdo->lastInsertId();
            writeLog('guides', "Guide dupliqué: {$original['title']} → #$newId", 'create', ['guide_id' => $newId, 'source_id' => $id]);
            $result = ['success' => true, 'message' => 'Guide dupliqué', 'new_id' => $newId];
            break;

        // ══════════════════════════════════════════════════════════
        // BULK_DELETE — Supprimer plusieurs guides
        // ══════════════════════════════════════════════════════════
        case 'bulk_delete':
            $idsJson = $_POST['ids'] ?? '[]';
            $ids = json_decode($idsJson, true);
            if (!is_array($ids) || empty($ids)) {
                throw new Exception('IDs invalides');
            }

            // Valider et purifier les IDs
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, fn($i) => $i > 0);
            if (empty($ids)) throw new Exception('Aucun ID valide');

            // Récupérer les guides à supprimer
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, title FROM guides WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Supprimer
            $stmt = $pdo->prepare("DELETE FROM guides WHERE id IN ($placeholders)");
            $stmt->execute($ids);

            // Log
            foreach ($guides as $g) {
                writeLog('guides', "Guide supprimé (bulk): {$g['title']}", 'delete', ['guide_id' => $g['id']]);
            }

            $result = [
                'success' => true,
                'message' => count($guides) . ' guide(s) supprimé(s)',
                'count' => count($guides)
            ];
            break;

        // ══════════════════════════════════════════════════════════
        // BULK_STATUS — Modifier statut de plusieurs guides
        // ══════════════════════════════════════════════════════════
        case 'bulk_status':
            $idsJson = $_POST['ids'] ?? '[]';
            $status = $_POST['status'] ?? '';
            $ids = json_decode($idsJson, true);

            if (!is_array($ids) || empty($ids) || !in_array($status, ['active', 'inactive'])) {
                throw new Exception('Paramètres invalides');
            }

            // Valider et purifier les IDs
            $ids = array_map('intval', $ids);
            $ids = array_filter($ids, fn($i) => $i > 0);
            if (empty($ids)) throw new Exception('Aucun ID valide');

            // Récupérer les guides
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, title FROM guides WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            $guides = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Mettre à jour
            $stmt = $pdo->prepare("UPDATE guides SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$status], $ids));

            // Log
            $action_txt = $status === 'active' ? 'activé' : 'désactivé';
            foreach ($guides as $g) {
                writeLog('guides', "Guide {$action_txt} (bulk): {$g['title']}", 'update', ['guide_id' => $g['id'], 'status' => $status]);
            }

            $result = [
                'success' => true,
                'message' => count($guides) . ' guide(s) ' . $action_txt . '(s)',
                'count' => count($guides),
                'status' => $status
            ];
            break;

        // ══════════════════════════════════════════════════════════
        // Unknown action
        // ══════════════════════════════════════════════════════════
        default:
            throw new Exception('Action inconnue: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    $result = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    error_log("[Guides API] Error in action '$action': " . $e->getMessage());
}

// ─── Retour JSON ───
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;