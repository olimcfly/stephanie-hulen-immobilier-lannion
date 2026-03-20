<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE CAPTURES — API AJAX  v1.0
 *  /admin/modules/content/capture/api.php
 *
 *  Endpoint POST centralisé pour les actions AJAX :
 *    action=save   → INSERT / UPDATE capture
 *    action=delete → DELETE capture + stats associées
 *
 *  Input  : POST multipart/form-data ou JSON
 *  Output : JSON { success, id?, message, errors? }
 * ══════════════════════════════════════════════════════════════
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();

// ─── Auth ───
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// ─── CSRF ───
$csrfHeader = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$csrfSession = $_SESSION['csrf_token'] ?? '';
if (!$csrfHeader || !$csrfSession || !hash_equals($csrfSession, $csrfHeader)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
    exit;
}

// ─── DB ───
if (!isset($pdo) && !isset($db)) {
    require_once __DIR__ . '/../../../config/config.php';
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur DB : ' . $e->getMessage()]);
        exit;
    }
}
if (isset($db)  && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db))  $db  = $pdo;

// ─── Lire les données ───
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
}

$action = $input['action'] ?? '';

// ════════════════════════════════════════════════════════════
//  ACTION : SAVE (INSERT / UPDATE)
// ════════════════════════════════════════════════════════════
if ($action === 'save') {
    $id = (int)($input['id'] ?? 0);

    $d = [
        'titre'         => trim($input['titre']         ?? ''),
        'slug'          => trim($input['slug']          ?? ''),
        'type'          => $input['type']               ?? 'guide',
        'template'      => $input['template']           ?? 'split',
        'headline'      => trim($input['headline']      ?? ''),
        'sous_titre'    => trim($input['sous_titre']    ?? ''),
        'description'   => trim($input['description']   ?? ''),
        'cta_text'      => trim($input['cta_text']      ?? ''),
        'page_merci_url'=> trim($input['page_merci_url'] ?? '/merci'),
        'status'        => ($input['status'] ?? 'inactive') === 'active' ? 'active' : 'inactive',
        'active'        => ($input['status'] ?? 'inactive') === 'active' ? 1 : 0,
        'actif'         => ($input['status'] ?? 'inactive') === 'active' ? 1 : 0,
    ];

    // Slug auto si vide
    if (!$d['slug'] && $d['titre']) {
        $d['slug'] = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-',
            iconv('UTF-8', 'ASCII//TRANSLIT', $d['titre'])), '-'));
    }
    $d['slug'] = preg_replace('/[^a-z0-9-]/', '', strtolower($d['slug']));

    // Validation
    $errors = [];
    if (empty($d['titre'])) $errors['titre'] = 'Le titre est obligatoire.';
    if (empty($d['slug']))  $errors['slug']  = 'Le slug est obligatoire.';
    if (!empty($errors)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Données invalides', 'errors' => $errors]);
        exit;
    }

    // Unicité du slug
    try {
        $slugCheck = $pdo->prepare("SELECT id FROM captures WHERE slug = ? AND id != ?");
        $slugCheck->execute([$d['slug'], $id]);
        if ($slugCheck->fetch()) {
            $d['slug'] = $d['slug'] . '-' . time();
        }
    } catch (PDOException $e) {}

    try {
        if ($id > 0) {
            // UPDATE
            $exists = $pdo->prepare("SELECT id FROM captures WHERE id = ?");
            $exists->execute([$id]);
            if (!$exists->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Capture introuvable']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE captures SET
                titre=:titre, slug=:slug, type=:type, template=:template,
                headline=:headline, sous_titre=:sous_titre, description=:description,
                cta_text=:cta_text, page_merci_url=:page_merci_url,
                status=:status, active=:active, actif=:actif
                WHERE id=:id");
            $stmt->execute(array_merge($d, ['id' => $id]));
            echo json_encode(['success' => true, 'id' => $id, 'message' => 'Page de capture mise à jour avec succès.']);
        } else {
            // INSERT
            $stmt = $pdo->prepare("INSERT INTO captures
                (titre, slug, type, template, headline, sous_titre, description, cta_text, page_merci_url, status, active, actif, vues, conversions, taux_conversion)
                VALUES
                (:titre,:slug,:type,:template,:headline,:sous_titre,:description,:cta_text,:page_merci_url,:status,:active,:actif,0,0,0.00)");
            $stmt->execute($d);
            $newId = (int)$pdo->lastInsertId();
            echo json_encode(['success' => true, 'id' => $newId, 'message' => 'Page de capture créée avec succès.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()]);
    }
    exit;
}

// ════════════════════════════════════════════════════════════
//  ACTION : DELETE
// ════════════════════════════════════════════════════════════
if ($action === 'delete') {
    $id = (int)($input['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID invalide']);
        exit;
    }

    try {
        $exists = $pdo->prepare("SELECT id, titre FROM captures WHERE id = ?");
        $exists->execute([$id]);
        $rec = $exists->fetch(PDO::FETCH_ASSOC);
        if (!$rec) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Capture introuvable']);
            exit;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare("DELETE FROM captures_stats WHERE capture_id = ?")->execute([$id]);
        } catch (PDOException $e) {} // table optionnelle
        $pdo->prepare("DELETE FROM captures WHERE id = ?")->execute([$id]);
        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Page de capture supprimée.']);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()]);
    }
    exit;
}

// ─── Action inconnue ───
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Action inconnue : ' . $action]);
