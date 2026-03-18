<?php
/**
 * ============================================================
 * CORE API RESPONSE
 * /core/api/response.php
 * 
 * Classe utilitaire pour standardiser toutes les réponses API
 * ============================================================
 */

class ApiResponse {

    /**
     * Réponse succès
     */
    public static function success($data = null, string $message = 'Succès', int $code = 200): void {
        http_response_code($code);
        echo json_encode([
            'success'   => true,
            'message'   => $message,
            'data'      => $data,
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Réponse erreur
     */
    public static function error(string $message = 'Erreur', int $code = 400, $details = null): void {
        http_response_code($code);
        echo json_encode([
            'success'   => false,
            'message'   => $message,
            'details'   => $details,
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Réponse liste paginée
     */
    public static function paginated(array $items, int $total, int $page, int $perPage, string $message = 'OK'): void {
        http_response_code(200);
        echo json_encode([
            'success'    => true,
            'message'    => $message,
            'data'       => $items,
            'pagination' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'total_pages'  => ceil($total / $perPage),
                'has_next'     => $page < ceil($total / $perPage),
                'has_prev'     => $page > 1
            ],
            'timestamp'  => time()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Réponse non autorisé
     */
    public static function unauthorized(string $message = 'Non autorisé'): void {
        self::error($message, 401);
    }

    /**
     * Réponse non trouvé
     */
    public static function notFound(string $message = 'Ressource introuvable'): void {
        self::error($message, 404);
    }

    /**
     * Réponse validation échouée
     */
    public static function validationError(array $errors): void {
        http_response_code(422);
        echo json_encode([
            'success'   => false,
            'message'   => 'Erreurs de validation',
            'errors'    => $errors,
            'timestamp' => time()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ============================================================
// FONCTIONS HELPER GLOBALES
// ============================================================

/**
 * Retourne les données POST décodées (JSON ou form-data)
 */
function getRequestData(): array {
    // Essayer JSON body d'abord
    $json = file_get_contents('php://input');
    if (!empty($json)) {
        $decoded = json_decode($json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }
    }
    // Fallback sur $_POST
    return $_POST;
}

/**
 * Sanitize une chaîne
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un slug depuis un titre
 */
function generateSlug(string $title): string {
    $slug = mb_strtolower($title, 'UTF-8');
    
    // Remplacer accents
    $map = [
        'à'=>'a','â'=>'a','ä'=>'a','á'=>'a','ã'=>'a',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ò'=>'o','ó'=>'o','ô'=>'o','ö'=>'o','õ'=>'o',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c','ñ'=>'n'
    ];
    $slug = strtr($slug, $map);
    
    // Remplacer tout ce qui n'est pas alphanumérique par -
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $slug = trim($slug, '-');
    
    return $slug;
}

/**
 * Validate les champs requis
 */
function validateRequired(array $data, array $fields): array {
    $errors = [];
    foreach ($fields as $field) {
        if (empty($data[$field])) {
            $errors[$field] = "Le champ '{$field}' est obligatoire";
        }
    }
    return $errors;
}