<?php
function sanitize($data, $type = 'string') {
    if (is_array($data)) return array_map(fn($v) => sanitize($v, $type), $data);
    return match($type) {
        'email' => filter_var($data, FILTER_SANITIZE_EMAIL),
        'url' => filter_var($data, FILTER_SANITIZE_URL),
        'int' => intval($data),
        'html' => htmlspecialchars($data, ENT_QUOTES, 'UTF-8'),
        default => htmlspecialchars($data, ENT_QUOTES, 'UTF-8'),
    };
}
function isValidEmail($email) { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
/**
 * Valide un fichier uploadé : MIME réel (finfo), taille, et renommage sécurisé.
 *
 * @param array  $file         Entrée $_FILES['...']
 * @param string $category     'image', 'document' ou 'media' (images+docs+vidéos)
 * @return array ['valid' => bool, 'error' => string|null, 'mime' => string, 'ext' => string, 'safe_name' => string]
 */
function validateUpload(array $file, string $category = 'image'): array {
    // Vérifier erreur upload
    if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE   => 'Fichier trop lourd (limite serveur)',
            UPLOAD_ERR_FORM_SIZE  => 'Fichier trop lourd (limite formulaire)',
            UPLOAD_ERR_PARTIAL    => 'Upload incomplet',
            UPLOAD_ERR_NO_FILE    => 'Aucun fichier reçu',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire sur le disque',
        ];
        $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        return ['valid' => false, 'error' => $errors[$code] ?? 'Erreur upload'];
    }

    // MIME types autorisés par catégorie
    $mimeWhitelist = [
        'image' => [
            'image/jpeg'    => 'jpg',
            'image/png'     => 'png',
            'image/webp'    => 'webp',
            'image/svg+xml' => 'svg',
        ],
        'document' => [
            'application/pdf' => 'pdf',
        ],
        'media' => [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'image/svg+xml'   => 'svg',
            'application/pdf' => 'pdf',
        ],
    ];

    // Limites de taille par catégorie (en octets)
    $sizeLimits = [
        'image'    => 5 * 1024 * 1024,   // 5 Mo
        'document' => 10 * 1024 * 1024,   // 10 Mo
        'media'    => 10 * 1024 * 1024,   // 10 Mo
    ];

    $allowed = $mimeWhitelist[$category] ?? $mimeWhitelist['image'];
    $maxSize = $sizeLimits[$category] ?? $sizeLimits['image'];

    // Vérifier MIME réel avec finfo (pas l'extension ni le Content-Type envoyé)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowed[$realMime])) {
        return ['valid' => false, 'error' => 'Type de fichier non autorisé : ' . $realMime];
    }

    // Vérifier taille
    if ($file['size'] > $maxSize) {
        $maxMo = round($maxSize / 1024 / 1024);
        return ['valid' => false, 'error' => "Fichier trop volumineux (max {$maxMo} Mo)"];
    }

    // Extension basée sur le MIME réel (pas sur le nom d'origine)
    $ext = $allowed[$realMime];

    // Nom sécurisé : hash unique pour éviter path traversal et collisions
    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;

    return [
        'valid'     => true,
        'error'     => null,
        'mime'      => $realMime,
        'ext'       => $ext,
        'safe_name' => $safeName,
    ];
}

function isRateLimited($key, $limit = 5, $window = 60) {
    $cache_key = "rate_limit_$key";
    if (!isset($_SESSION[$cache_key])) $_SESSION[$cache_key] = [];
    $now = time();
    $_SESSION[$cache_key] = array_filter($_SESSION[$cache_key], fn($t) => $now - $t < $window);
    if (count($_SESSION[$cache_key]) >= $limit) return true;
    $_SESSION[$cache_key][] = $now;
    return false;
}
