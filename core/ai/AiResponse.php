<?php
/**
 * ============================================================
 *  AiResponse — Format de réponse JSON standardisé
 *  Fichier : core/ai/AiResponse.php
 * ============================================================
 *
 *  Garantit que TOUTES les réponses AJAX de l'IA ont le même format.
 *  Chaque méthode envoie les headers HTTP, echo le JSON, et exit.
 *
 *  Formats de sortie :
 *
 *  Succès :
 *    {"success":true, "article":{...}, "_meta":{"provider":"claude","tokens":450}}
 *
 *  Erreur :
 *    {"success":false, "error":"Message d'erreur"}
 *    {"success":false, "error":"...", "_debug":{...}}  ← si APP_DEBUG=true
 *
 *  Usage :
 *    AiResponse::success(['article' => $data]);
 *    AiResponse::success(['article' => $data], ['provider'=>'claude','tokens'=>450]);
 *    AiResponse::error('Clé API manquante');
 *    AiResponse::error('Erreur serveur', 500);
 *    AiResponse::fromProvider($providerResult, 'article', $parsedData);
 * ============================================================
 */

declare(strict_types=1);

class AiResponse
{
    // =========================================================================
    //  Réponse succès
    // =========================================================================
    /**
     * Envoie une réponse JSON de succès.
     *
     * @param array $data  Données à retourner (ex: ['article' => $parsed])
     * @param array $meta  Métadonnées optionnelles (provider, tokens, timing)
     */
    public static function success(array $data = [], array $meta = []): never
    {
        $payload = ['success' => true];

        // Fusionner les données directement dans la réponse
        foreach ($data as $key => $value) {
            $payload[$key] = $value;
        }

        // Ajouter les méta si présentes (toujours en dernier)
        if (!empty($meta)) {
            $payload['_meta'] = $meta;
        }

        self::send($payload, 200);
    }

    // =========================================================================
    //  Réponse erreur
    // =========================================================================
    /**
     * Envoie une réponse JSON d'erreur.
     *
     * @param string $message   Message d'erreur lisible
     * @param int    $httpCode  Code HTTP (400, 403, 500...)
     * @param array  $debug     Infos debug (uniquement si APP_DEBUG=true)
     */
    public static function error(
        string $message,
        int    $httpCode = 200,
        array  $debug    = []
    ): never {
        $payload = [
            'success' => false,
            'error'   => $message,
        ];

        // Ajouter les infos debug uniquement en mode développement
        if (!empty($debug) && defined('APP_DEBUG') && APP_DEBUG === true) {
            $payload['_debug'] = $debug;
        }

        self::send($payload, $httpCode);
    }

    // =========================================================================
    //  Construction depuis un résultat provider IA
    // =========================================================================
    /**
     * Construit une réponse depuis le résultat brut d'AiClient.
     *
     * Si le provider a échoué → envoie une erreur.
     * Si $parsed est fourni   → envoie le JSON parsé avec méta provider/tokens.
     * Sinon                   → envoie le texte brut dans la clé $key.
     *
     * @param array       $providerResult  Résultat de AiClient::claude() / openai() / withFallback()
     * @param string      $key             Clé principale dans la réponse (ex: 'article', 'email')
     * @param mixed       $parsed          Données parsées (résultat de AiPromptBuilder::extractJson())
     */
    public static function fromProvider(
        array  $providerResult,
        string $key,
        mixed  $parsed = null
    ): never {
        // Provider en échec → erreur
        if (!$providerResult['success']) {
            self::error(
                $providerResult['error'] ?? 'Erreur IA inconnue',
                200
            );
        }

        // Calculer les tokens (Anthropic et OpenAI n'ont pas les mêmes clés)
        $tokens = $providerResult['usage']['output_tokens']      // Claude
               ?? $providerResult['usage']['completion_tokens']  // OpenAI
               ?? 0;

        $meta = [
            'provider' => $providerResult['provider'] ?? $providerResult['model'] ?? 'ai',
            'tokens'   => $tokens,
        ];

        // Données parsées disponibles → réponse complète
        if ($parsed !== null) {
            self::success([$key => $parsed], $meta);
        }

        // Fallback : texte brut
        self::success(
            [$key => $providerResult['content'], 'format' => 'raw_text'],
            $meta
        );
    }

    // =========================================================================
    //  Réponse de validation (pour les endpoints non-IA)
    // =========================================================================
    /**
     * Réponse simple pour confirmer une action (save, delete, update).
     *
     * @param string $message  ex: "Article sauvegardé avec succès"
     * @param array  $data     Données additionnelles optionnelles
     */
    public static function ok(string $message, array $data = []): never
    {
        self::success(array_merge(['message' => $message], $data));
    }

    // =========================================================================
    //  Envoi HTTP
    // =========================================================================
    /**
     * Envoie la réponse JSON avec les headers appropriés et termine l'exécution.
     *
     * @param array $payload  Tableau à JSON-encoder
     * @param int   $code     Code HTTP
     */
    private static function send(array $payload, int $code = 200): never
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
        }

        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );

        exit;
    }
}