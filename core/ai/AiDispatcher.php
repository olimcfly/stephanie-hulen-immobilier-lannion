<?php
/**
 * ============================================================
 *  AiDispatcher — Routeur central IA
 *  Fichier : core/ai/AiDispatcher.php
 * ============================================================
 *
 *  Reçoit module + action depuis generate.php
 *  → valide le module
 *  → charge le Handler correspondant
 *  → vérifie que l'action existe
 *  → exécute
 *
 *  Pour ajouter un nouveau module :
 *    1. Créer core/ai/modules/MonModuleHandler.php (hérite BaseHandler)
 *    2. Ajouter 'monmodule' => 'MonModuleHandler' dans HANDLERS ci-dessous
 *    3. C'est tout — generate.php n'a pas besoin d'être modifié
 *
 *  Usage (depuis generate.php) :
 *    AiDispatcher::dispatch('articles', 'generate', $input);
 * ============================================================
 */

declare(strict_types=1);

class AiDispatcher
{
    // ─── Table de correspondance module → classe Handler ─────────────────────
    private const HANDLERS = [
        'articles' => 'ArticlesHandler',
        'biens'    => 'BiensHandler',
        'leads'    => 'LeadsHandler',
        'seo'      => 'SeoHandler',
        'social'   => 'SocialHandler',
        'gmb'      => 'GmbHandler',
        'captures' => 'CapturesHandler',
    ];

    // ─── Chemin vers les fichiers Handler ─────────────────────────────────────
    private const MODULES_PATH = __DIR__ . '/modules/';

    // =========================================================================
    //  Point d'entrée principal
    // =========================================================================
    /**
     * Dispatche une requête IA vers le bon Handler.
     * Envoie une réponse JSON et termine l'exécution (via AiResponse).
     *
     * @param string $module  ex: 'articles', 'leads', 'seo'
     * @param string $action  ex: 'generate', 'qualify', 'analyze'
     * @param array  $input   Paramètres de la requête (POST body décodé)
     */
    public static function dispatch(string $module, string $action, array $input): void
    {
        // 1. Valider que le module existe
        if (!array_key_exists($module, self::HANDLERS)) {
            AiLogger::warning("Dispatch : module inconnu '{$module}'");
            AiResponse::error(
                "Module '{$module}' non reconnu. Modules disponibles : " . implode(', ', array_keys(self::HANDLERS)),
                400
            );
        }

        // 2. Charger le fichier Handler
        $handlerClass = self::HANDLERS[$module];
        $handlerFile  = self::MODULES_PATH . $handlerClass . '.php';
        $baseFile     = self::MODULES_PATH . 'BaseHandler.php';

        if (!file_exists($baseFile)) {
            AiLogger::error("BaseHandler.php introuvable : {$baseFile}");
            AiResponse::error('Erreur configuration : BaseHandler.php manquant', 500);
        }

        if (!file_exists($handlerFile)) {
            AiLogger::error("Handler manquant : {$handlerFile}");
            AiResponse::error("Handler pour le module '{$module}' introuvable", 500);
        }

        require_once $baseFile;
        require_once $handlerFile;

        // 3. Vérifier que la classe existe
        if (!class_exists($handlerClass)) {
            AiLogger::error("Classe '{$handlerClass}' non définie dans {$handlerFile}");
            AiResponse::error("Classe '{$handlerClass}' non définie", 500);
        }

        // 4. Instancier et valider l'action
        /** @var BaseHandler $handler */
        $handler = new $handlerClass();

        if (!$handler->hasAction($action)) {
            AiLogger::warning("Action inconnue : {$module}.{$action}");
            AiResponse::error(
                "Action '{$action}' non reconnue pour le module '{$module}'. "
                . "Actions disponibles : " . implode(', ', $handler->getActions()),
                400
            );
        }

        // 5. Logger l'appel entrant
        AiLogger::info("→ {$module}.{$action}", [
            'admin_id' => $_SESSION['admin_id'] ?? 0,
            'ip'       => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ]);

        // 6. Exécuter
        try {
            $handler->handle($action, $input);

        } catch (Throwable $e) {
            AiLogger::error(
                "Exception {$module}.{$action} : " . $e->getMessage(),
                ['file' => $e->getFile(), 'line' => $e->getLine()]
            );
            AiResponse::error(
                'Une erreur est survenue lors du traitement.',
                500,
                [
                    'exception' => $e->getMessage(),
                    'file'      => basename($e->getFile()),
                    'line'      => $e->getLine(),
                ]
            );
        }
    }

    // =========================================================================
    //  Registre (pour debug / documentation)
    // =========================================================================
    /**
     * Retourne la liste de tous les modules et leurs actions disponibles.
     * Utilisé par la page de diagnostic admin.
     *
     * @return array<string, array{handler:string, actions:string[], status:string}>
     */
    public static function getRegistry(): array
    {
        $registry = [];
        $baseFile  = self::MODULES_PATH . 'BaseHandler.php';

        if (file_exists($baseFile)) {
            require_once $baseFile;
        }

        foreach (self::HANDLERS as $module => $handlerClass) {
            $file = self::MODULES_PATH . $handlerClass . '.php';

            if (!file_exists($file)) {
                $registry[$module] = [
                    'handler' => $handlerClass,
                    'actions' => [],
                    'status'  => 'error: fichier manquant',
                ];
                continue;
            }

            require_once $file;

            if (!class_exists($handlerClass)) {
                $registry[$module] = [
                    'handler' => $handlerClass,
                    'actions' => [],
                    'status'  => 'error: classe non définie',
                ];
                continue;
            }

            $handler = new $handlerClass();
            $registry[$module] = [
                'handler' => $handlerClass,
                'actions' => $handler->getActions(),
                'status'  => 'ok',
            ];
        }

        return $registry;
    }

    // ─── Liste des modules disponibles ────────────────────────────────────────
    public static function getModules(): array
    {
        return array_keys(self::HANDLERS);
    }
}