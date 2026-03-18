<?php
/**
 * ============================================================
 *  AiClient — Client API unifié
 *  Fichier : core/ai/AiClient.php
 * ============================================================
 *
 *  Gère TOUS les appels vers les APIs IA externes.
 *  C'est le SEUL fichier qui connaît les URLs et clés API.
 *
 *  Providers supportés :
 *    → Claude   (Anthropic)     — génération de contenu principal
 *    → OpenAI   (GPT-4o)        — fallback si Claude échoue
 *    → Perplexity               — enrichissement données marché live
 *    → DALL-E 3                 — génération d'images
 *
 *  Usage :
 *    $client = AiClient::getInstance();
 *    $result = $client->claude($prompt, $system, 2000);
 *    $result = $client->openai($prompt, 'gpt-4o', 2000, $system);
 *    $result = $client->perplexity($prompt);
 *    $result = $client->dalle($prompt, '1792x1024');
 *    $result = $client->withFallback($prompt, $system);  // Claude → OpenAI auto
 * ============================================================
 */

declare(strict_types=1);

class AiClient
{
    // ─── Singleton ────────────────────────────────────────────────────────────
    private static ?self $instance = null;

    private function __construct() {}
    private function __clone() {}

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // ─── Résolution des clés API ──────────────────────────────────────────────
    // Cherche dans les constantes PHP (config.php) puis dans les variables d'env
    private function key(string $provider): string
    {
        $map = [
            'claude'     => defined('CLAUDE_API_KEY')     ? CLAUDE_API_KEY     : (getenv('CLAUDE_API_KEY')     ?: ''),
            'openai'     => defined('OPENAI_API_KEY')     ? OPENAI_API_KEY     : (getenv('OPENAI_API_KEY')     ?: ''),
            'perplexity' => defined('PERPLEXITY_API_KEY') ? PERPLEXITY_API_KEY : (getenv('PERPLEXITY_API_KEY') ?: ''),
        ];

        $k = $map[$provider] ?? '';

        if (empty($k)) {
            throw new RuntimeException("Clé API manquante pour le provider : {$provider}");
        }

        return $k;
    }

    // =========================================================================
    //  CLAUDE (Anthropic)
    // =========================================================================
    /**
     * Appel à l'API Anthropic Claude.
     *
     * @param  string $prompt       Message utilisateur
     * @param  string $system       Prompt système (persona, contexte, règles)
     * @param  int    $maxTokens    Nombre max de tokens en réponse
     * @param  float  $temperature  0.0 = déterministe / 1.0 = créatif
     * @param  string $model        Modèle Claude à utiliser
     * @return array{
     *   success: bool,
     *   content: string,
     *   usage: array,
     *   model: string,
     *   error?: string
     * }
     */
    public function claude(
        string $prompt,
        string $system      = '',
        int    $maxTokens   = 2000,
        float  $temperature = 0.7,
        string $model       = 'claude-opus-4-5'
    ): array {
        try {
            $payload = [
                'model'       => $model,
                'max_tokens'  => $maxTokens,
                'temperature' => $temperature,
                'messages'    => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ];

            if (!empty($system)) {
                $payload['system'] = $system;
            }

            $response = $this->httpPost(
                'https://api.anthropic.com/v1/messages',
                $payload,
                [
                    'x-api-key: '          . $this->key('claude'),
                    'anthropic-version: 2023-06-01',
                ],
                timeout: 60
            );

            if ($response['http_code'] !== 200) {
                $msg = $response['data']['error']['message'] ?? "HTTP {$response['http_code']}";
                throw new RuntimeException($msg);
            }

            return [
                'success' => true,
                'content' => $response['data']['content'][0]['text'] ?? '',
                'usage'   => $response['data']['usage'] ?? [],
                'model'   => $model,
            ];

        } catch (Throwable $e) {
            AiLogger::error('AiClient::claude — ' . $e->getMessage());
            return [
                'success' => false,
                'content' => '',
                'usage'   => [],
                'model'   => $model,
                'error'   => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    //  OPENAI (GPT)
    // =========================================================================
    /**
     * Appel à l'API OpenAI — utilisé en fallback ou pour des tâches spécifiques.
     *
     * @param  string $prompt
     * @param  string $model     gpt-4o | gpt-4o-mini | gpt-3.5-turbo
     * @param  int    $maxTokens
     * @param  string $system
     * @return array{success:bool, content:string, usage:array, model:string, error?:string}
     */
    public function openai(
        string $prompt,
        string $model     = 'gpt-4o',
        int    $maxTokens = 2000,
        string $system    = ''
    ): array {
        try {
            $messages = [];
            if (!empty($system)) {
                $messages[] = ['role' => 'system', 'content' => $system];
            }
            $messages[] = ['role' => 'user', 'content' => $prompt];

            $response = $this->httpPost(
                'https://api.openai.com/v1/chat/completions',
                [
                    'model'      => $model,
                    'messages'   => $messages,
                    'max_tokens' => $maxTokens,
                ],
                ['Authorization: Bearer ' . $this->key('openai')],
                timeout: 60
            );

            if ($response['http_code'] !== 200) {
                $msg = $response['data']['error']['message'] ?? "HTTP {$response['http_code']}";
                throw new RuntimeException($msg);
            }

            return [
                'success' => true,
                'content' => $response['data']['choices'][0]['message']['content'] ?? '',
                'usage'   => $response['data']['usage'] ?? [],
                'model'   => $model,
            ];

        } catch (Throwable $e) {
            AiLogger::error('AiClient::openai — ' . $e->getMessage());
            return [
                'success' => false,
                'content' => '',
                'usage'   => [],
                'model'   => $model,
                'error'   => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    //  PERPLEXITY (recherche web enrichie)
    // =========================================================================
    /**
     * Appel à Perplexity — pour des données marché immobilier en temps réel.
     * Résultats enrichis avec citations de sources web récentes.
     *
     * @param  string $prompt
     * @param  string $model   llama-3.1-sonar-large-128k-online | sonar-pro
     * @return array{success:bool, content:string, citations:array, error?:string}
     */
    public function perplexity(
        string $prompt,
        string $model = 'llama-3.1-sonar-large-128k-online'
    ): array {
        try {
            $response = $this->httpPost(
                'https://api.perplexity.ai/chat/completions',
                [
                    'model'    => $model,
                    'messages' => [
                        [
                            'role'    => 'system',
                            'content' => 'Tu es un expert immobilier français spécialisé sur le marché bordelais. Réponds toujours en français avec des données récentes et sourcées.',
                        ],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
                ['Authorization: Bearer ' . $this->key('perplexity')],
                timeout: 45
            );

            if ($response['http_code'] !== 200) {
                $msg = $response['data']['error']['message'] ?? "HTTP {$response['http_code']}";
                throw new RuntimeException($msg);
            }

            return [
                'success'   => true,
                'content'   => $response['data']['choices'][0]['message']['content'] ?? '',
                'citations' => $response['data']['citations'] ?? [],
            ];

        } catch (Throwable $e) {
            AiLogger::error('AiClient::perplexity — ' . $e->getMessage());
            return [
                'success'   => false,
                'content'   => '',
                'citations' => [],
                'error'     => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    //  DALL-E 3 (génération d'images)
    // =========================================================================
    /**
     * Génère une image via DALL-E 3.
     * Le prompt est automatiquement sécurisé pour éviter les refus content policy.
     *
     * @param  string $prompt   Description de l'image souhaitée
     * @param  string $size     1024x1024 | 1792x1024 | 1024x1792
     * @param  string $quality  standard | hd
     * @return array{success:bool, url:string, revised_prompt:string, error?:string}
     */
    public function dalle(
        string $prompt,
        string $size    = '1792x1024',
        string $quality = 'standard'
    ): array {
        try {
            // Wrapper sécurisé : réduit les refus content policy DALL-E
            $safePrompt = "Professional real estate photography, {$prompt}, "
                        . "high quality, bright natural lighting, modern French architecture, "
                        . "editorial style photography, no text overlay, clean composition";

            $response = $this->httpPost(
                'https://api.openai.com/v1/images/generations',
                [
                    'model'   => 'dall-e-3',
                    'prompt'  => $safePrompt,
                    'n'       => 1,
                    'size'    => $size,
                    'quality' => $quality,
                    'style'   => 'natural',
                ],
                ['Authorization: Bearer ' . $this->key('openai')],
                timeout: 90
            );

            if ($response['http_code'] !== 200) {
                $msg = $response['data']['error']['message'] ?? "HTTP {$response['http_code']}";
                throw new RuntimeException($msg);
            }

            return [
                'success'        => true,
                'url'            => $response['data']['data'][0]['url']            ?? '',
                'revised_prompt' => $response['data']['data'][0]['revised_prompt'] ?? '',
            ];

        } catch (Throwable $e) {
            AiLogger::error('AiClient::dalle — ' . $e->getMessage());
            return [
                'success'        => false,
                'url'            => '',
                'revised_prompt' => '',
                'error'          => $e->getMessage(),
            ];
        }
    }

    // =========================================================================
    //  FALLBACK AUTOMATIQUE : Claude → OpenAI
    // =========================================================================
    /**
     * Essaie Claude en premier.
     * Si Claude échoue (quota, erreur réseau, etc.), bascule automatiquement sur OpenAI.
     * Ajoute une clé 'provider' dans le résultat pour identifier qui a répondu.
     *
     * @return array{success:bool, content:string, provider:string, usage:array, error?:string}
     */
    public function withFallback(
        string $prompt,
        string $system      = '',
        int    $maxTokens   = 2000,
        float  $temperature = 0.7
    ): array {
        // 1. Tentative Claude
        $result = $this->claude($prompt, $system, $maxTokens, $temperature);

        if ($result['success']) {
            $result['provider'] = 'claude';
            return $result;
        }

        // 2. Fallback OpenAI
        AiLogger::warning('Claude échoué, bascule sur OpenAI', ['error' => $result['error'] ?? '']);

        $result = $this->openai($prompt, 'gpt-4o', $maxTokens, $system);

        if ($result['success']) {
            $result['provider'] = 'openai_fallback';
            return $result;
        }

        // 3. Tous les providers ont échoué
        return [
            'success'  => false,
            'content'  => '',
            'provider' => 'none',
            'usage'    => [],
            'error'    => $result['error'] ?? 'Tous les providers IA ont échoué',
        ];
    }

    // =========================================================================
    //  HTTP Helper interne
    // =========================================================================
    /**
     * Exécute une requête POST HTTP/JSON via cURL.
     *
     * @param  string   $url
     * @param  array    $payload       Corps de la requête (sera JSON-encodé)
     * @param  string[] $extraHeaders  Headers additionnels (auth, versioning)
     * @param  int      $timeout       Timeout en secondes
     * @return array{http_code:int, data:array}
     * @throws RuntimeException si cURL échoue
     */
    private function httpPost(
        string $url,
        array  $payload,
        array  $extraHeaders = [],
        int    $timeout      = 30
    ): array {
        $headers = array_merge(
            ['Content-Type: application/json'],
            $extraHeaders
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT      => 'IMMO-LOCAL-PLUS/1.0',
        ]);

        $raw       = curl_exec($ch);
        $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new RuntimeException("cURL error: {$curlError}");
        }

        return [
            'http_code' => $httpCode,
            'data'      => json_decode((string)$raw, true) ?? [],
        ];
    }
}