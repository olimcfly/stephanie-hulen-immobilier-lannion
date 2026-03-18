<?php
/**
 * HELPER CENTRALISÉ — CLÉS API
 * /home/mahe6420/public_html/includes/functions/api-keys.php
 *
 * Usage dans n'importe quel module :
 *   require_once '/home/mahe6420/public_html/includes/functions/api-keys.php';
 *   $key = get_api_key('claude');   // retourne la clé déchiffrée ou ''
 *
 * Source unique : table api_keys (AES-256)
 * Fallback      : ai_settings pour compatibilité ascendante
 */

if (!function_exists('get_api_key')) :

function _ak_pdo(): ?PDO {
    global $pdo, $db;
    if (isset($pdo)) return $pdo;
    if (isset($db))  return $db;
    try {
        if (!defined('DB_HOST')) require_once '/home/mahe6420/public_html/config/config.php';
        $c = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        return $c;
    } catch (Throwable $e) { return null; }
}

function _ak_decrypt(string $v, string $k): string {
    if (!$v) return '';
    $raw = base64_decode($v);
    if (strlen($raw) < 17) return '';
    $dec = openssl_decrypt(substr($raw, 16), 'aes-256-cbc', $k, 0, substr($raw, 0, 16));
    return $dec === false ? '' : $dec;
}

function _ak_enc_key(): string {
    return defined('APP_KEY') ? APP_KEY : (defined('SECRET_KEY') ? SECRET_KEY : 'immolocal_aes_key_2024_secure!!');
}

/**
 * Retourne la clé API déchiffrée pour un service.
 * @param string $serviceKey  Ex: 'claude', 'openai', 'perplexity', 'google_maps'…
 * @param bool   $activeOnly  Si true (défaut), retourne '' si is_active=0
 * @return string  La clé en clair, ou '' si absente/inactive
 */
function get_api_key(string $serviceKey, bool $activeOnly = true): string {
    static $cache = [];
    $cacheKey = $serviceKey . ($activeOnly ? '_active' : '_any');
    if (isset($cache[$cacheKey])) return $cache[$cacheKey];

    $pdo = _ak_pdo();
    if (!$pdo) return $cache[$cacheKey] = '';

    // ── Source 1 : api_keys (chiffré AES-256) ──
    try {
        $sql = "SELECT api_key_encrypted, is_active FROM api_keys WHERE service_key = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$serviceKey]);
        $row = $stmt->fetch();
        if ($row) {
            if ($activeOnly && !$row['is_active']) return $cache[$cacheKey] = '';
            if (!empty($row['api_key_encrypted'])) {
                $dec = _ak_decrypt($row['api_key_encrypted'], _ak_enc_key());
                if ($dec) return $cache[$cacheKey] = $dec;
            }
        }
    } catch (Throwable $e) {}

    // ── Fallback : ai_settings (clés en clair, compatibilité) ──
    $legacyMap = [
        'claude'      => 'claude_api_key',
        'openai'      => 'openai_api_key',
        'perplexity'  => 'perplexity_api_key',
        'mistral'     => 'mistral_api_key',
        'google_maps' => 'google_maps_key',
    ];
    if (isset($legacyMap[$serviceKey])) {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM ai_settings WHERE setting_key = ?");
            $stmt->execute([$legacyMap[$serviceKey]]);
            $row = $stmt->fetch();
            if ($row && !empty($row['setting_value'])) {
                return $cache[$cacheKey] = $row['setting_value'];
            }
        } catch (Throwable $e) {}
    }

    return $cache[$cacheKey] = '';
}

/**
 * Retourne true si le service est configuré ET actif.
 */
function has_api_key(string $serviceKey): bool {
    return get_api_key($serviceKey) !== '';
}

/**
 * Retourne toutes les clés actives par catégorie.
 * @return array<string, string>  ['claude' => 'sk-ant-…', …]
 */
function get_all_api_keys(string $category = ''): array {
    $pdo = _ak_pdo();
    if (!$pdo) return [];
    try {
        if ($category) {
            $stmt = $pdo->prepare("SELECT service_key, api_key_encrypted FROM api_keys WHERE is_active=1 AND category=? AND api_key_encrypted IS NOT NULL");
            $stmt->execute([$category]);
        } else {
            $stmt = $pdo->query("SELECT service_key, api_key_encrypted FROM api_keys WHERE is_active=1 AND api_key_encrypted IS NOT NULL");
        }
        $result = [];
        $k = _ak_enc_key();
        foreach ($stmt->fetchAll() as $r) {
            $dec = _ak_decrypt($r['api_key_encrypted'], $k);
            if ($dec) $result[$r['service_key']] = $dec;
        }
        return $result;
    } catch (Throwable $e) { return []; }
}

/**
 * Raccourcis pratiques
 */
function get_claude_key(): string      { return get_api_key('claude'); }
function get_openai_key(): string      { return get_api_key('openai'); }
function get_perplexity_key(): string  { return get_api_key('perplexity'); }

endif;