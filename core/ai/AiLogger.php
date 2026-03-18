<?php
/**
 * ============================================================
 *  AiLogger — Logs centralisés pour toutes les opérations IA
 *  Fichier : core/ai/AiLogger.php
 * ============================================================
 *
 *  Deux destinations :
 *    1. Fichier  → logs/ai.log   (toujours)
 *    2. Base DB  → table ai_usage_log  (si Database disponible)
 *
 *  Niveaux disponibles :
 *    AiLogger::info($msg, $context)
 *    AiLogger::warning($msg, $context)
 *    AiLogger::error($msg, $context)
 *    AiLogger::debug($msg, $context)   ← seulement si APP_DEBUG=true
 *
 *  Tracking appels API :
 *    AiLogger::track($module, $action, $provider, $tokens, $success)
 *    → log fichier + INSERT en DB pour les stats dashboard
 *
 *  Utilitaires :
 *    AiLogger::tail(50)           → 50 dernières lignes du log
 *    AiLogger::installSql()       → SQL pour créer la table ai_usage_log
 * ============================================================
 */

declare(strict_types=1);

class AiLogger
{
    // ─── Chemin du fichier log (résolu au premier appel) ──────────────────────
    private static string $logFile = '';

    // ─── Constantes de niveau ────────────────────────────────────────────────
    private const LEVEL_INFO  = 'INFO ';
    private const LEVEL_WARN  = 'WARN ';
    private const LEVEL_ERROR = 'ERROR';
    private const LEVEL_DEBUG = 'DEBUG';
    private const LEVEL_TRACK = 'TRACK';

    // =========================================================================
    //  API publique — niveaux de log
    // =========================================================================

    /** Log informatif standard */
    public static function info(string $message, array $context = []): void
    {
        self::write(self::LEVEL_INFO, $message, $context);
    }

    /** Log avertissement (dégradation, fallback, comportement inattendu) */
    public static function warning(string $message, array $context = []): void
    {
        self::write(self::LEVEL_WARN, $message, $context);
    }

    /** Log erreur (exception, appel API raté, fichier manquant) */
    public static function error(string $message, array $context = []): void
    {
        self::write(self::LEVEL_ERROR, $message, $context);
    }

    /** Log debug — uniquement si constante APP_DEBUG = true */
    public static function debug(string $message, array $context = []): void
    {
        if (defined('APP_DEBUG') && APP_DEBUG === true) {
            self::write(self::LEVEL_DEBUG, $message, $context);
        }
    }

    // =========================================================================
    //  Tracking des appels API (coût, tokens, provider, succès)
    // =========================================================================
    /**
     * Enregistre chaque appel IA pour alimenter les stats du dashboard admin.
     *
     * @param string $module    ex: 'articles', 'leads'
     * @param string $action    ex: 'generate', 'qualify'
     * @param string $provider  ex: 'claude', 'openai_fallback', 'perplexity'
     * @param int    $tokens    Nombre de tokens consommés (output)
     * @param bool   $success   Succès ou échec de l'appel
     */
    public static function track(
        string $module,
        string $action,
        string $provider,
        int    $tokens,
        bool   $success
    ): void {
        self::write(self::LEVEL_TRACK, "{$module}.{$action}", [
            'provider' => $provider,
            'tokens'   => $tokens,
            'success'  => $success ? 'true' : 'false',
            'admin_id' => $_SESSION['admin_id'] ?? 0,
        ]);

        // Persister en DB (silencieux si table inexistante)
        self::persistToDb($module, $action, $provider, $tokens, $success);
    }

    // =========================================================================
    //  Utilitaires
    // =========================================================================

    /**
     * Retourne les N dernières lignes du log (ordre antéchronologique).
     * Utilisé par la page diagnostic / dashboard.
     *
     * @param  int $lines Nombre de lignes à retourner
     * @return string[]
     */
    public static function tail(int $lines = 50): array
    {
        $file = self::resolveLogFile();

        if (!file_exists($file) || filesize($file) === 0) {
            return [];
        }

        $all = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice(array_reverse($all), 0, $lines);
    }

    /**
     * Vide le fichier de log (action admin).
     */
    public static function clear(): bool
    {
        $file = self::resolveLogFile();
        return file_put_contents($file, '') !== false;
    }

    /**
     * Retourne la taille du fichier log en KB.
     */
    public static function size(): float
    {
        $file = self::resolveLogFile();
        return file_exists($file) ? round(filesize($file) / 1024, 1) : 0.0;
    }

    /**
     * SQL pour créer la table de statistiques.
     * À exécuter une seule fois via phpMyAdmin ou le setup admin.
     */
    public static function installSql(): string
    {
        return <<<SQL
CREATE TABLE IF NOT EXISTS `ai_usage_log` (
    `id`          INT            NOT NULL AUTO_INCREMENT,
    `module`      VARCHAR(50)    NOT NULL,
    `action`      VARCHAR(100)   NOT NULL,
    `provider`    VARCHAR(30)    NOT NULL DEFAULT 'claude',
    `tokens_used` INT            NOT NULL DEFAULT 0,
    `success`     TINYINT(1)     NOT NULL DEFAULT 1,
    `admin_id`    INT            NOT NULL DEFAULT 0,
    `created_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_module`   (`module`),
    INDEX `idx_provider` (`provider`),
    INDEX `idx_created`  (`created_at`),
    INDEX `idx_admin`    (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
    }

    // =========================================================================
    //  Privé — écriture fichier
    // =========================================================================
    private static function write(string $level, string $message, array $context): void
    {
        $file = self::resolveLogFile();
        $date = date('Y-m-d H:i:s');
        $ctx  = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
        $line = "[{$date}] [{$level}] {$message}{$ctx}" . PHP_EOL;

        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    private static function resolveLogFile(): string
    {
        if (empty(self::$logFile)) {
            // Remonte jusqu'à la racine public_html
            $base          = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
            self::$logFile = rtrim($base, '/') . '/logs/ai.log';
        }
        return self::$logFile;
    }

    // =========================================================================
    //  Privé — persistance DB
    // =========================================================================
    private static function persistToDb(
        string $module,
        string $action,
        string $provider,
        int    $tokens,
        bool   $success
    ): void {
        try {
            if (!class_exists('Database')) {
                return;
            }

            $db = Database::getInstance();
            $db->prepare("
                INSERT INTO ai_usage_log
                    (module, action, provider, tokens_used, success, admin_id, created_at)
                VALUES
                    (?, ?, ?, ?, ?, ?, NOW())
            ")->execute([
                $module,
                $action,
                $provider,
                $tokens,
                (int) $success,
                $_SESSION['admin_id'] ?? 0,
            ]);

        } catch (Throwable) {
            // Silencieux — la table peut ne pas encore exister
            // L'essentiel est dans le fichier log
        }
    }
}