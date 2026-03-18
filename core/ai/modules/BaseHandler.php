<?php
/**
 * ============================================================
 *  BaseHandler — Classe abstraite parente de tous les modules IA
 *  Fichier : core/ai/modules/BaseHandler.php
 * ============================================================
 *
 *  Chaque Handler de module hérite de cette classe.
 *  Elle expose les outils communs : client IA, helpers, réponses.
 *
 *  Convention de nommage des méthodes :
 *    → handle_generate()      pour l'action 'generate'
 *    → handle_improve()       pour l'action 'improve'
 *    → handle_price_analysis() pour l'action 'price_analysis'
 *
 *  Exemple de Handler minimal :
 *
 *    class MonModuleHandler extends BaseHandler {
 *        protected array $actions = ['generate', 'analyze'];
 *
 *        protected function handle_generate(array $input): void {
 *            $prompt = "Génère quelque chose sur : " . ($input['subject'] ?? '');
 *            $result = $this->generate($prompt, $this->context(), 2000);
 *            $this->track('generate', $result);
 *            if (!$result['success']) $this->fail($result['error'] ?? 'Erreur');
 *            $parsed = $this->parseJson($result['content']);
 *            $this->success(['data' => $parsed]);
 *        }
 *    }
 * ============================================================
 */

declare(strict_types=1);

abstract class BaseHandler
{
    // ─── Actions supportées — à déclarer dans chaque Handler ─────────────────
    /** @var string[] */
    protected array $actions = [];

    // ─── Client IA partagé ────────────────────────────────────────────────────
    protected AiClient $client;

    // =========================================================================
    //  Initialisation
    // =========================================================================
    public function __construct()
    {
        $this->client = AiClient::getInstance();
    }

    // =========================================================================
    //  Routing interne (appelé par AiDispatcher)
    // =========================================================================
    /**
     * Route l'action vers la méthode handle_{action}().
     *
     * @param string $action  ex: 'generate', 'improve'
     * @param array  $input   Paramètres de la requête
     */
    final public function handle(string $action, array $input): void
    {
        $method = 'handle_' . $action;

        if (!method_exists($this, $method)) {
            AiResponse::error(
                "Méthode '{$method}' non implémentée dans " . static::class,
                500
            );
        }

        $this->$method($input);
    }

    /**
     * Vérifie qu'une action est déclarée dans $actions.
     */
    final public function hasAction(string $action): bool
    {
        return in_array($action, $this->actions, true);
    }

    /**
     * Retourne la liste des actions disponibles.
     */
    final public function getActions(): array
    {
        return $this->actions;
    }

    // =========================================================================
    //  Helpers IA — disponibles dans tous les modules
    // =========================================================================

    /**
     * Génère du contenu via Claude avec fallback automatique sur OpenAI.
     * Méthode principale à utiliser dans les Handlers.
     *
     * @param  string $prompt
     * @param  string $system      Contexte système (persona)
     * @param  int    $maxTokens
     * @param  float  $temperature 0.0 = déterministe / 1.0 = créatif
     * @return array{success:bool, content:string, provider:string, usage:array, error?:string}
     */
    protected function generate(
        string $prompt,
        string $system      = '',
        int    $maxTokens   = 2000,
        float  $temperature = 0.7
    ): array {
        return $this->client->withFallback($prompt, $system, $maxTokens, $temperature);
    }

    /**
     * Appel direct Claude (sans fallback).
     * Utiliser quand on veut contrôler précisément le provider.
     */
    protected function claude(
        string $prompt,
        string $system    = '',
        int    $maxTokens = 2000,
        float  $temperature = 0.7
    ): array {
        return $this->client->claude($prompt, $system, $maxTokens, $temperature);
    }

    /**
     * Enrichissement avec données marché Perplexity.
     * Retourne le contenu texte ou chaîne vide si échec.
     */
    protected function marketData(string $query): string
    {
        $result = $this->client->perplexity($query);
        return $result['success'] ? ($result['content'] ?? '') : '';
    }

    /**
     * Extrait le JSON de la réponse IA.
     *
     * @return array|null
     */
    protected function parseJson(string $content): ?array
    {
        return AiPromptBuilder::extractJson($content);
    }

    /**
     * Retourne le contexte système du module courant.
     * Déduit automatiquement depuis le nom de la classe Handler.
     * ex: ArticlesHandler → context('articles')
     */
    protected function context(): string
    {
        $module = strtolower(
            str_replace('Handler', '', static::class)
        );
        return AiPromptBuilder::context($module);
    }

    // =========================================================================
    //  Helpers réponse
    // =========================================================================

    /**
     * Envoie une réponse de succès et termine.
     */
    protected function success(array $data, array $meta = []): never
    {
        AiResponse::success($data, $meta);
    }

    /**
     * Envoie une réponse d'erreur et termine.
     */
    protected function fail(string $message, int $httpCode = 200): never
    {
        AiResponse::error($message, $httpCode);
    }

    // =========================================================================
    //  Helpers validation
    // =========================================================================

    /**
     * Vérifie que les champs requis sont présents dans $input.
     * Envoie une erreur 400 si un champ manque.
     *
     * @param array    $input   Tableau de données
     * @param string[] $fields  Noms des champs requis
     */
    protected function requireFields(array $input, string ...$fields): void
    {
        foreach ($fields as $field) {
            if (!isset($input[$field]) || $input[$field] === '' || $input[$field] === null) {
                $this->fail("Champ requis manquant : '{$field}'", 400);
            }
        }
    }

    /**
     * Raccourci pour requireFields (alias plus court).
     */
    protected function need(array $input, string ...$fields): void
    {
        $this->requireFields($input, ...$fields);
    }

    /**
     * Retourne une valeur string nettoyée depuis $input.
     *
     * @param array  $input
     * @param string $key
     * @param string $default
     */
    protected function str(array $input, string $key, string $default = ''): string
    {
        return trim((string)($input[$key] ?? $default));
    }

    /**
     * Retourne une valeur int depuis $input avec min/max optionnels.
     */
    protected function int(array $input, string $key, int $default = 0, int $min = 0, int $max = PHP_INT_MAX): int
    {
        $val = (int)($input[$key] ?? $default);
        return max($min, min($max, $val));
    }

    // =========================================================================
    //  Tracking usage (logs + stats DB)
    // =========================================================================

    /**
     * Enregistre l'usage d'un appel IA dans les logs et la DB.
     * À appeler après chaque appel IA dans un Handler.
     *
     * @param string $action  Action exécutée (ex: 'generate')
     * @param array  $result  Résultat retourné par generate() ou claude()
     */
    protected function track(string $action, array $result): void
    {
        $module   = strtolower(str_replace('Handler', '', static::class));
        $tokens   = (int)(
            $result['usage']['output_tokens']     // Claude
            ?? $result['usage']['completion_tokens'] // OpenAI
            ?? 0
        );
        $provider = $result['provider'] ?? $result['model'] ?? 'claude';

        AiLogger::track($module, $action, $provider, $tokens, $result['success']);
    }
}