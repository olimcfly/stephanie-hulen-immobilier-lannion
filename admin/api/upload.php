<?php
/**
 * /admin/api/upload.php
 * Upload d'images pour l'éditeur de pages
 * POST multipart/form-data : file + csrf_token + (optionnel) folder
 */

define('ADMIN_ROUTER', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions/security.php';

header('Content-Type: application/json');

// Auth
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// CSRF
$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Token invalide']);
    exit;
}

// Fichier
if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE   => 'Fichier trop lourd (limite serveur)',
        UPLOAD_ERR_FORM_SIZE  => 'Fichier trop lourd (limite formulaire)',
        UPLOAD_ERR_PARTIAL    => 'Upload incomplet',
        UPLOAD_ERR_NO_FILE    => 'Aucun fichier reçu',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
        UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire sur le disque',
    ];
    $code = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    echo json_encode(['success' => false, 'error' => $errors[$code] ?? 'Erreur upload']);
    exit;
}

$file     = $_FILES['file'];
$folder   = trim($_POST['folder'] ?? 'pages');
$folder   = preg_replace('/[^a-z0-9\-_]/', '', strtolower($folder)) ?: 'pages';

// Validation centralisée (MIME réel, taille, renommage sécurisé)
$validation = validateUpload($file, 'image');
if (!$validation['valid']) {
    echo json_encode(['success' => false, 'error' => $validation['error']]);
    exit;
}

$mimeType = $validation['mime'];
$ext      = $validation['ext'];
$filename = $validation['safe_name'];

// Dossier cible
$uploadRoot = $_SERVER['DOCUMENT_ROOT'] . '/uploads/' . $folder . '/';
if (!is_dir($uploadRoot)) {
    mkdir($uploadRoot, 0755, true);
}

$destPath = $uploadRoot . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'error' => 'Impossible de déplacer le fichier']);
    exit;
}

// URL publique
$baseUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

$publicUrl = $baseUrl . '/uploads/' . $folder . '/' . $filename;

echo json_encode([
    'success'  => true,
    'url'      => $publicUrl,
    'filename' => $filename,
    'folder'   => $folder,
    'size'     => $file['size'],
    'mime'     => $mimeType,
]);
exit;