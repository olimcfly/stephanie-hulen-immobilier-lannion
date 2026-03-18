<?php
/**
 * /admin/api/content/pages.php
 * Ajouter ce bloc au fichier existant, ou remplacer l'action save_fields
 *
 * Action : save_fields
 * Reçoit : JSON body { action, page_id, fields, status, meta_title, meta_description }
 * Retourne : { success, message } ou { success, error }
 */

// ── Si ce fichier est appelé directement, inclure le config ──
if (!defined('ADMIN_ROUTER')) {
    define('ADMIN_ROUTER', true);
    require_once __DIR__ . '/../../../config/config.php';
}

// Lire le corps JSON
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);
$action = $body['action'] ?? ($_POST['action'] ?? '');

// Router vers les actions existantes si nécessaire
if ($action !== 'save_fields') {
    // Laisser passer aux actions existantes du fichier
    // (ce bloc s'insère au début du switch existant)
    // Pour un fichier standalone, retourner une erreur
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Action inconnue']);
    exit;
}

/* ════════════════════════════════════════
   ACTION : save_fields
   ════════════════════════════════════════ */
header('Content-Type: application/json');

$pageId = (int)($body['page_id'] ?? 0);
if (!$pageId) {
    echo json_encode(['success' => false, 'error' => 'page_id manquant']);
    exit;
}

$fields     = $body['fields']           ?? [];
$status     = $body['status']           ?? 'draft';
$metaTitle  = trim($body['meta_title']  ?? '');
$metaDesc   = trim($body['meta_description'] ?? '');

// Valider le statut
$allowedStatus = ['draft', 'published', 'publie'];
if (!in_array($status, $allowedStatus)) $status = 'draft';

// Sérialiser les fields en JSON
$fieldsJson = json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

try {
    $pdo = getDB();

    // Vérifier que la colonne fields existe, sinon l'ajouter
    try {
        $pdo->query("SELECT fields FROM pages LIMIT 1");
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE pages ADD COLUMN fields JSON NULL AFTER content");
    }

    $stmt = $pdo->prepare("
        UPDATE pages SET
            fields           = :fields,
            status           = :status,
            meta_title       = :meta_title,
            meta_description = :meta_description,
            updated_at       = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':fields'           => $fieldsJson,
        ':status'           => $status,
        ':meta_title'       => $metaTitle,
        ':meta_description' => $metaDesc,
        ':id'               => $pageId,
    ]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Page introuvable (id=' . $pageId . ')']);
        exit;
    }

    $label = in_array($status, ['published', 'publie']) ? 'Publié' : 'Enregistré';
    echo json_encode([
        'success' => true,
        'message' => $label . ' avec succès',
        'page_id' => $pageId,
        'status'  => $status,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;