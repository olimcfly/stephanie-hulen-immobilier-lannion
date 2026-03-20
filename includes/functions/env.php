<?php
/**
 * Helper pour les variables d'environnement (.env)
 * includes/functions/env.php
 *
 * Charge le fichier .env à la racine et fournit la fonction env()
 */

if (!function_exists('env')) :

/**
 * Charge et met en cache les variables du fichier .env
 */
function _env_load(): array {
    static $vars = null;
    if ($vars !== null) return $vars;

    $vars = [];
    $envFile = defined('ROOT_PATH') ? ROOT_PATH . '/.env' : dirname(__DIR__, 2) . '/.env';

    if (!is_file($envFile)) return $vars;

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignorer les commentaires
        if ($line === '' || $line[0] === '#') continue;

        $pos = strpos($line, '=');
        if ($pos === false) continue;

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        // Retirer les guillemets encadrants
        if (strlen($value) >= 2 && (
            ($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
            ($value[0] === "'" && $value[strlen($value) - 1] === "'")
        )) {
            $value = substr($value, 1, -1);
        }

        $vars[$key] = $value;
        // Rendre disponible via getenv() aussi
        putenv("$key=$value");
    }

    return $vars;
}

/**
 * Récupère une variable d'environnement.
 *
 * @param string $key     Nom de la variable
 * @param mixed  $default Valeur par défaut si non définie
 * @return mixed
 */
function env(string $key, $default = null) {
    $vars = _env_load();

    if (isset($vars[$key])) return $vars[$key];

    $val = getenv($key);
    if ($val !== false) return $val;

    return $default;
}

endif;
