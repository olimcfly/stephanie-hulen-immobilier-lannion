<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE CAPTURES — Save (INSERT / UPDATE)  v1.0
 *  /admin/modules/content/captures/save.php
 *
 *  Endpoint POST centralisé pour créer ou mettre à jour
 *  une capture. Utilisé par create.php et les appels AJAX.
 *
 *  Input  : POST multipart ou JSON
 *  Output : JSON  { success, id, message, errors? }
 *         ou redirect si X-Requested-With absent
 *
 *  Champs supportés (table captures) :
 *    titre*, slug*, description, type, template, contenu,
 *    headline, sous_titre, image_url, cta_text,
 *    guide_ids(JSON), champs_formulaire(JSON),
 *    page_merci_url, active, actif, status
 * ══════════════════════════════════════════════════════════════
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    _respond(false, null, 'Non authentifié', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    _respond(false, null, 'Méthode non autorisée', 405);
}

// ─── DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) {
        require_once __DIR__ . '/../../../config/config.php';
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            _respond(false, null, 'DB: ' . $e->getMessage(), 500);
        }
    }
}
if (isset($db)  && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db))  $db  = $pdo;

// ─── Détecter appel AJAX ───
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
          str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
          !empty($_POST['_ajax']);

// ─── Lire les données (POST form ou JSON body) ───
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $body = file_get_contents('php://input');
    $input = json_decode($body, true) ?: [];
}

// ─── Récupérer les champs ───
$id             = (int)($input['id']             ?? 0);
$titre          = trim($input['titre']           ?? '');
$slug           = trim($input['slug']            ?? '');
$description    = trim($input['description']     ?? '');
$headline       = trim($input['headline']        ?? '');
$sous_titre     = trim($input['sous_titre']      ?? '');
$contenu        = trim($input['contenu']         ?? '');
$image_url      = trim($input['image_url']       ?? '');
$cta_text       = trim($input['cta_text']        ?? '');
$page_merci_url = trim($input['page_merci_url']  ?? '');
$type           = $input['type']                 ?? 'contact';
$template       = $input['template']             ?? 'simple';
$status_val     = $input['status']               ?? 'active';
$active         = ($status_val === 'active') ? 1 : 0;
$actif          = $active;

// Guide IDs (JSON ou tableau)
$guide_ids_raw = $input['guide_ids'] ?? null;
$guide_ids_json = null;
if (!empty($guide_ids_raw)) {
    if (is_array($guide_ids_raw)) {
        $guide_ids_json = json_encode($guide_ids_raw);
    } elseif (is_string($guide_ids_raw)) {
        $decoded = json_decode($guide_ids_raw, true);
        $guide_ids_json = ($decoded !== null) ? $guide_ids_raw : null;
    }
}

// Champs formulaire (JSON ou tableau)
$champs_raw = $input['champs_formulaire'] ?? null;
$champs_json = null;
if (!empty($champs_raw)) {
    if (is_array($champs_raw)) {
        $champs_json = json_encode($champs_raw);
    } elseif (is_string($champs_raw)) {
        $decoded = json_decode($champs_raw, true);
        $champs_json = ($decoded !== null) ? $champs_raw : json_encode(['raw' => $champs_raw]);
    }
}

// ─── Validation ───
$errors = [];
if (empty($titre)) $errors['titre'] = 'Le titre est obligatoire.';
if (empty($slug)) {
    if (!empty($titre)) {
        // Auto-générer
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-',
            iconv('UTF-8', 'ASCII//TRANSLIT', $titre)));
        $slug = trim($slug, '-');
    } else {
        $errors['slug'] = 'Le slug est obligatoire.';
    }
}
if (!in_array($type, ['estimation', 'contact', 'newsletter', 'guide'])) {
    $errors['type'] = 'Type invalide.';
}
if (!in_array($status_val, ['active', 'inactive', 'archived'])) {
    $errors['status'] = 'Statut invalide.';
}
if (!empty($errors)) {
    _respond(false, null, 'Données invalides', 422, $errors);
}

// ─── Vérifier unicité du slug ───
try {
    $slugCheck = $pdo->prepare("SELECT id FROM captures WHERE slug = ? AND id != ?");
    $slugCheck->execute([$slug, $id]);
    if ($slugCheck->fetch()) {
        $slug = $slug . '-' . time(); // Rendre unique automatiquement
    }
} catch (PDOException $e) {}

// ─── Données à persister ───
$data = [
    'titre'             => $titre,
    'slug'              => $slug,
    'description'       => $description ?: null,
    'headline'          => $headline     ?: null,
    'sous_titre'        => $sous_titre   ?: null,
    'contenu'           => $contenu      ?: null,
    'image_url'         => $image_url    ?: null,
    'cta_text'          => $cta_text     ?: null,
    'page_merci_url'    => $page_merci_url ?: null,
    'guide_ids'         => $guide_ids_json,
    'champs_formulaire' => $champs_json,
    'type'              => $type,
    'template'          => $template,
    'status'            => $status_val,
    'active'            => $active,
    'actif'             => $actif,
];

try {
    if ($id > 0) {
        // ─── UPDATE ───
        // Vérifier que la capture existe
        $exists = $pdo->prepare("SELECT id FROM captures WHERE id = ?");
        $exists->execute([$id]);
        if (!$exists->fetch()) {
            _respond(false, null, 'Capture introuvable', 404);
        }
        $sets = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $pdo->prepare("UPDATE captures SET $sets, updated_at = NOW() WHERE id = ?")
            ->execute([...array_values($data), $id]);
        $resultId = $id;
        $message  = 'Page de capture mise à jour avec succès.';
    } else {
        // ─── INSERT ───
        $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
        $ph   = implode(', ', array_fill(0, count($data), '?'));
        $pdo->prepare("INSERT INTO captures ($cols, created_at) VALUES ($ph, NOW())")
            ->execute(array_values($data));
        $resultId = (int)$pdo->lastInsertId();
        $message  = 'Page de capture créée avec succès.';
    }

    // ─── Réponse ───
    if ($isAjax) {
        _respond(true, $resultId, $message);
    } else {
        // Redirect classique vers édition
        header('Location: ?page=captures&action=edit&id=' . $resultId . '&msg=' . ($id > 0 ? 'updated' : 'created'));
        exit;
    }

} catch (PDOException $e) {
    _respond(false, null, 'Erreur SQL : ' . $e->getMessage(), 500);
}

// ─── Helper réponse ───
function _respond(bool $success, ?int $id = null, string $message = '', int $code = 200, array $errors = []): void {
    $isAjaxCtx = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ||
                  str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') ||
                  !empty($_POST['_ajax']);

    if ($isAjaxCtx || !$success) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        $out = ['success' => $success, 'message' => $message];
        if ($id)     $out['id']     = $id;
        if ($errors) $out['errors'] = $errors;
        echo json_encode($out);
        exit;
    }
    // Fallback non-AJAX sur erreur
    if (!$success) {
        header('Location: ?page=captures&msg=error');
        exit;
    }
}