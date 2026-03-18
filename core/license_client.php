<?php
declare(strict_types=1);

/**
 * /core/license_client.php  (côté instance cliente)
 * Vérifie la licence auprès du portail install + cache local.
 *
 * Requiert côté instance :
 * - config/license.php -> retourne license_key, installation_id, subdomain, target_domain, client_secret_b64
 * - un dossier /cache writable (ou adapte le chemin)
 */

function eim_license_cfg(): array {
    $path = __DIR__ . '/../config/license.php';
    if (!file_exists($path)) {
        return [];
    }
    $cfg = require $path;
    return is_array($cfg) ? $cfg : [];
}

function eim_license_cache_path(): string {
    return __DIR__ . '/../cache/license.json';
}

function eim_license_read_cache(int $maxAgeSeconds = 86400): ?array {
    $p = eim_license_cache_path();
    if (!file_exists($p)) return null;
    $raw = @file_get_contents($p);
    if ($raw === false) return null;

    $data = json_decode($raw, true);
    if (!is_array($data)) return null;

    $ts = (int)($data['_cached_at'] ?? 0);
    if ($ts <= 0) return null;

    if ((time() - $ts) > $maxAgeSeconds) return null;
    return $data;
}

function eim_license_write_cache(array $data): void {
    $p = eim_license_cache_path();
    $dir = dirname($p);
    if (!is_dir($dir)) @mkdir($dir, 0775, true);

    $data['_cached_at'] = time();
    @file_put_contents($p, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function eim_license_canonical(array $params): string {
    ksort($params);
    $parts = [];
    foreach ($params as $k => $v) {
        $parts[] = $k . '=' . (string)$v;
    }
    return implode('&', $parts);
}

function eim_license_sign(array $params, string $clientSecretB64): string {
    $bin = base64_decode($clientSecretB64, true);
    if ($bin === false || $bin === '') return '';
    $canonical = eim_license_canonical($params);
    return hash_hmac('sha256', $canonical, $bin);
}

function eim_license_verify_remote(bool $force = false): array {
    // Cache
    if (!$force) {
        $cached = eim_license_read_cache(3600 * 6); // cache 6h (à ton goût)
        if (is_array($cached)) return $cached;
    }

    $cfg = eim_license_cfg();
    $licenseKey = (string)($cfg['license_key'] ?? '');
    $installationId = (string)($cfg['installation_id'] ?? '');
    $subdomain = (string)($cfg['subdomain'] ?? '');
    $targetDomain = (string)($cfg['target_domain'] ?? '');
    $clientSecretB64 = (string)($cfg['client_secret_b64'] ?? '');

    if ($licenseKey === '' || $clientSecretB64 === '') {
        return [
            'ok' => false,
            'active' => false,
            'reason' => 'missing_license_key_or_client_secret',
        ];
    }

    $params = [
        'installation_id' => $installationId,
        'license_key'     => $licenseKey,
        'nonce'           => bin2hex(random_bytes(12)),
        'subdomain'       => $subdomain,
        'target_domain'   => $targetDomain,
        'ts'              => (string)time(),
    ];

    $sig = eim_license_sign($params, $clientSecretB64);
    if ($sig === '') {
        return [
            'ok' => false,
            'active' => false,
            'reason' => 'cannot_sign_request',
        ];
    }

    $params['sig'] = $sig;

    $url = 'https://install.ecosystemeimmo.fr/api/license/verify.php?' . http_build_query($params);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 8,
            'header' => "User-Agent: EIM-License-Client/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        // fallback cache “stale” si dispo
        $stale = eim_license_read_cache(3600 * 24 * 30);
        if (is_array($stale)) {
            $stale['_stale'] = true;
            $stale['_reason'] = 'portal_unreachable_using_stale_cache';
            return $stale;
        }
        return [
            'ok' => false,
            'active' => false,
            'reason' => 'portal_unreachable',
        ];
    }

    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [
            'ok' => false,
            'active' => false,
            'reason' => 'invalid_json_from_portal',
        ];
    }

    eim_license_write_cache($data);
    return $data;
}

/**
 * À appeler dans tes pages admin / bootstrap admin.
 * Bloque si licence inactive.
 */
function requireValidLicense(): void {
    $res = eim_license_verify_remote(false);

    if (!($res['ok'] ?? false) || !($res['active'] ?? false)) {
        http_response_code(403);
        $reason = htmlspecialchars((string)($res['reason'] ?? 'inactive'), ENT_QUOTES, 'UTF-8');
        echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'>";
        echo "<title>Licence inactive</title>";
        echo "<style>body{font-family:system-ui;background:#0b1220;color:#e8eefc;padding:24px} .card{max-width:720px;margin:0 auto;background:#111a2e;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:18px}</style>";
        echo "</head><body><div class='card'>";
        echo "<h2>Licence inactive</h2>";
        echo "<p>Cette instance n’a pas de licence active. Raison: <b>{$reason}</b></p>";
        echo "<p>Contact support : install.ecosystemeimmo.fr</p>";
        echo "</div></body></html>";
        exit;
    }
}