<?php
/**
 * ══════════════════════════════════════════════════════════════
 * API ARTICLES — Endpoint AJAX
 * /admin/modules/articles/api/articles.php
 *
 * Actions supportées :
 *  - delete         → supprimer un article
 *  - bulk_delete    → supprimer plusieurs articles
 *  - toggle_status  → changer le statut (published ↔ draft)
 *  - bulk_status    → changer le statut de plusieurs articles
 *  - duplicate      → dupliquer un article
 * ══════════════════════════════════════════════════════════════
 */

header('Content-Type: application/json; charset=utf-8');

// ─── Sécurité : accès admin uniquement ───
if (!defined('ADMIN_ROUTER')) {
    require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

// ─── Méthode POST uniquement ───
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']); exit;
}

// ─── Auth session ───
if (empty($_SESSION['admin_id']) && empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non authentifié']); exit;
}

// ─── Détecter la table ───
$tableName = 'articles';
try {
    $pdo->query("SELECT 1 FROM articles LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->query("SELECT 1 FROM blog_articles LIMIT 1");
        $tableName = 'blog_articles';
    } catch (PDOException $e2) {
        echo json_encode(['success' => false, 'error' => 'Table articles introuvable']); exit;
    }
}

// ─── Dispatcher ───
$action = $_POST['action'] ?? '';

try {
    switch ($action) {

        // ── Supprimer un article ──
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID invalide');

            $check = $pdo->prepare("SELECT id FROM `{$tableName}` WHERE id = ?");
            $check->execute([$id]);
            if (!$check->fetchColumn()) throw new Exception('Article introuvable');

            $pdo->prepare("DELETE FROM `{$tableName}` WHERE id = ?")->execute([$id]);
            // Supprimer les scores SEO associés si la table existe
            try {
                $pdo->prepare("DELETE FROM seo_scores WHERE context = 'article' AND entity_id = ?")->execute([$id]);
            } catch (PDOException $e) {}

            echo json_encode(['success' => true, 'id' => $id]);
            break;

        // ── Supprimer plusieurs articles ──
        case 'bulk_delete':
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            $ids = array_filter(array_map('intval', (array)$ids));
            if (empty($ids)) throw new Exception('Aucun ID fourni');

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("DELETE FROM `{$tableName}` WHERE id IN ({$placeholders})")->execute($ids);
            try {
                $pdo->prepare("DELETE FROM seo_scores WHERE context = 'article' AND entity_id IN ({$placeholders})")->execute($ids);
            } catch (PDOException $e) {}

            echo json_encode(['success' => true, 'deleted' => count($ids)]);
            break;

        // ── Changer le statut d'un article ──
        case 'toggle_status':
            $id     = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            if (!$id) throw new Exception('ID invalide');
            if (!in_array($status, ['published', 'draft', 'archived'])) throw new Exception('Statut invalide');

            $extra = $status === 'published' ? ', published_at = NOW()' : '';
            // Vérifier si published_at existe
            try {
                $cols = $pdo->query("SHOW COLUMNS FROM `{$tableName}` LIKE 'published_at'")->fetchAll();
                if (empty($cols)) $extra = '';
            } catch (PDOException $e) { $extra = ''; }

            $pdo->prepare("UPDATE `{$tableName}` SET status = ?, updated_at = NOW() {$extra} WHERE id = ?")
                ->execute([$status, $id]);

            echo json_encode(['success' => true, 'id' => $id, 'status' => $status]);
            break;

        // ── Changer le statut de plusieurs articles ──
        case 'bulk_status':
            $ids    = json_decode($_POST['ids'] ?? '[]', true);
            $ids    = array_filter(array_map('intval', (array)$ids));
            $status = $_POST['status'] ?? '';
            if (empty($ids)) throw new Exception('Aucun ID fourni');
            if (!in_array($status, ['published', 'draft', 'archived'])) throw new Exception('Statut invalide');

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params       = array_merge([$status], $ids);
            $pdo->prepare("UPDATE `{$tableName}` SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})")
                ->execute($params);

            echo json_encode(['success' => true, 'updated' => count($ids), 'status' => $status]);
            break;

        // ── Dupliquer un article ──
        case 'duplicate':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID invalide');

            $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE id = ?");
            $stmt->execute([$id]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$article) throw new Exception('Article introuvable');

            // Générer un slug unique
            $baseSlug = ($article['slug'] ?? 'article') . '-copie';
            $slug     = $baseSlug;
            $i        = 1;
            while (true) {
                $exists = $pdo->prepare("SELECT id FROM `{$tableName}` WHERE slug = ?");
                $exists->execute([$slug]);
                if (!$exists->fetchColumn()) break;
                $slug = $baseSlug . '-' . $i++;
            }

            // Préparer les données du duplicata
            unset($article['id']);
            $article['title']      = ($article['title'] ?? 'Article') . ' (Copie)';
            $article['slug']       = $slug;
            $article['status']     = 'draft';
            $article['created_at'] = date('Y-m-d H:i:s');
            $article['updated_at'] = date('Y-m-d H:i:s');
            $article['seo_score']  = 0;

            // Reset champs calculés si présents
            foreach (['semantic_score', 'is_indexed', 'google_indexed', 'is_featured', 'views', 'shares'] as $f) {
                if (isset($article[$f])) $article[$f] = 0;
            }
            if (isset($article['google_indexed'])) $article['google_indexed'] = 'unknown';

            // Obtenir les colonnes disponibles pour l'INSERT
            $availCols  = $pdo->query("SHOW COLUMNS FROM `{$tableName}`")->fetchAll(PDO::FETCH_COLUMN);
            $insertCols = array_intersect(array_keys($article), $availCols);
            $insertCols = array_values($insertCols);

            $cols    = implode(', ', array_map(fn($c) => "`{$c}`", $insertCols));
            $pholds  = implode(', ', array_fill(0, count($insertCols), '?'));
            $values  = array_map(fn($c) => $article[$c], $insertCols);

            $pdo->prepare("INSERT INTO `{$tableName}` ({$cols}) VALUES ({$pholds})")->execute($values);
            $newId = (int)$pdo->lastInsertId();

            echo json_encode(['success' => true, 'id' => $newId, 'slug' => $slug]);
            break;

        default:
            throw new Exception("Action inconnue : {$action}");
    }

} catch (PDOException $e) {
    error_log("[ARM API] PDO Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}