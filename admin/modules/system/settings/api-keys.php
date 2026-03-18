<?php
/**
 * CLÉS API & INTÉGRATIONS
 * /admin/modules/system/settings/api-keys.php
 * Accès : dashboard.php?page=api-keys
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);

if (!defined('DB_HOST')) require_once dirname(__DIR__, 4) . '/config/config.php';

if (!isset($pdo)) {
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (PDOException $e) {
        die('<div style="padding:20px;color:#dc2626;font-family:monospace">DB: '.htmlspecialchars($e->getMessage()).'</div>');
    }
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

// ── Chiffrement AES-256 ───────────────────────────────────
$encKey = defined('APP_KEY') ? APP_KEY : (defined('SECRET_KEY') ? SECRET_KEY : 'immolocal_aes_key_2024_secure!!');

function akEncrypt(string $v, string $k): string {
    $iv = random_bytes(16);
    return base64_encode($iv . openssl_encrypt($v, 'aes-256-cbc', $k, 0, $iv));
}
function akDecrypt(string $v, string $k): string {
    if (!$v) return '';
    $raw = base64_decode($v);
    if (strlen($raw) < 17) return '';
    $dec = openssl_decrypt(substr($raw, 16), 'aes-256-cbc', $k, 0, substr($raw, 0, 16));
    return $dec === false ? '' : $dec;
}
function akMask(string $k): string {
    if (!$k) return '';
    if (strlen($k) <= 8) return str_repeat('•', strlen($k));
    return substr($k, 0, 6) . str_repeat('•', max(4, strlen($k) - 10)) . substr($k, -4);
}
function akBadge(string $s): string {
    return match($s) {
        'valid'   => '<span class="ak-badge valid"><i class="fas fa-check-circle"></i> Valide</span>',
        'invalid' => '<span class="ak-badge invalid"><i class="fas fa-times-circle"></i> Invalide</span>',
        'expired' => '<span class="ak-badge expired"><i class="fas fa-clock"></i> Expirée</span>',
        default   => '<span class="ak-badge unknown"><i class="fas fa-question-circle"></i> Inconnue</span>',
    };
}

// ── Catalogue aligné sur les service_key de la DB ─────────
// service_key DB → métadonnées d'affichage
$catalog = [
    'ai' => [
        'label' => 'Intelligence Artificielle', 'icon' => 'fa-robot', 'color' => '#7c3aed',
        'services' => [
            'claude'      => ['name'=>'Claude (Anthropic)',       'icon'=>'fa-brain',       'color'=>'#7c3aed', 'ph'=>'sk-ant-api03-…',  'doc'=>'https://console.anthropic.com/'],
            'openai'      => ['name'=>'OpenAI (GPT-4, DALL-E)',   'icon'=>'fa-robot',       'color'=>'#10b981', 'ph'=>'sk-proj-…',       'doc'=>'https://platform.openai.com/'],
            'perplexity'  => ['name'=>'Perplexity AI',            'icon'=>'fa-search-plus', 'color'=>'#0891b2', 'ph'=>'pplx-…',          'doc'=>'https://www.perplexity.ai/settings/api'],
            'mistral'     => ['name'=>'Mistral AI',               'icon'=>'fa-wind',        'color'=>'#f59e0b', 'ph'=>'…',               'doc'=>'https://console.mistral.ai/'],
        ]
    ],
    'google' => [
        'label' => 'Google', 'icon' => 'fa-google', 'color' => '#4285f4',
        'services' => [
            'google_maps'           => ['name'=>'Google Maps Platform',    'icon'=>'fa-map-marker-alt','color'=>'#ef4444','ph'=>'AIza…',  'doc'=>'https://console.cloud.google.com/'],
            'google_analytics'      => ['name'=>'Google Analytics (GA4)',  'icon'=>'fa-chart-bar',    'color'=>'#f59e0b','ph'=>'G-…',    'doc'=>'https://analytics.google.com/'],
            'google_search_console' => ['name'=>'Google Search Console',  'icon'=>'fa-search',       'color'=>'#34a853','ph'=>'…',      'doc'=>'https://search.google.com/search-console'],
            'google_my_business'    => ['name'=>'Google My Business',     'icon'=>'fa-store',        'color'=>'#4285f4','ph'=>'…',      'doc'=>'https://business.google.com/'],
            'google_ads'            => ['name'=>'Google Ads API',          'icon'=>'fa-ad',           'color'=>'#fbbc04','ph'=>'…',      'doc'=>'https://ads.google.com/'],
        ]
    ],
    'social' => [
        'label' => 'Réseaux sociaux', 'icon' => 'fa-share-alt', 'color' => '#ec4899',
        'services' => [
            'facebook_app'  => ['name'=>'Facebook / Meta API',     'icon'=>'fab fa-facebook', 'color'=>'#1877f2','ph'=>'EAA…',  'doc'=>'https://developers.facebook.com/'],
            'instagram_api' => ['name'=>'Instagram Graph API',     'icon'=>'fab fa-instagram','color'=>'#e1306c','ph'=>'…',    'doc'=>'https://developers.facebook.com/'],
            'tiktok_api'    => ['name'=>'TikTok API',              'icon'=>'fab fa-tiktok',   'color'=>'#010101','ph'=>'…',    'doc'=>'https://developers.tiktok.com/'],
        ]
    ],
    'other' => [
        'label' => 'Autres services', 'icon' => 'fa-plug', 'color' => '#6b7280',
        'services' => [
            'mailjet'    => ['name'=>'Mailjet (Emails)',         'icon'=>'fa-envelope',     'color'=>'#ff5b53','ph'=>'…',        'doc'=>'https://app.mailjet.com/'],
            'sendinblue' => ['name'=>'Brevo / Sendinblue',      'icon'=>'fa-paper-plane',  'color'=>'#0092ff','ph'=>'xkeysib-…','doc'=>'https://app.brevo.com/'],
            'stripe'     => ['name'=>'Stripe (Paiements)',      'icon'=>'fab fa-stripe',   'color'=>'#635bff','ph'=>'sk_live_…','doc'=>'https://dashboard.stripe.com/'],
            'twilio'     => ['name'=>'Twilio (SMS)',            'icon'=>'fa-sms',          'color'=>'#f22f46','ph'=>'SK…',      'doc'=>'https://console.twilio.com/'],
        ]
    ],
];

// ── Tests disponibles par service_key ─────────────────────
$testableServices = ['claude', 'openai', 'perplexity'];

// ── Init table si nécessaire ──────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `api_keys` (
        `id`                  INT AUTO_INCREMENT PRIMARY KEY,
        `service_key`         VARCHAR(50) NOT NULL UNIQUE,
        `service_name`        VARCHAR(100) NOT NULL,
        `api_key_encrypted`   TEXT DEFAULT NULL,
        `category`            ENUM('ai','google','social','analytics','other') DEFAULT 'other',
        `is_active`           TINYINT(1) DEFAULT 1,
        `last_verified_at`    DATETIME DEFAULT NULL,
        `verification_status` ENUM('unknown','valid','invalid','expired') DEFAULT 'unknown',
        `notes`               TEXT DEFAULT NULL,
        `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at`          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (Throwable $e) {}

// ── Charger données DB ────────────────────────────────────
$allKeys = [];
try {
    foreach ($pdo->query("SELECT * FROM api_keys") as $r) $allKeys[$r['service_key']] = $r;
} catch (Throwable $e) {}

$usageStats = [];
try {
    foreach ($pdo->query("SELECT service_name, calls_today, calls_month, cost_this_month FROM api_usage") as $r)
        $usageStats[$r['service_name']] = $r;
} catch (Throwable $e) {}

// ── POST ──────────────────────────────────────────────────
$saveMsg = $saveErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $saveErr = 'Token CSRF invalide.';
    } else {
        $action = $_POST['ak_action'] ?? '';

        if ($action === 'save_key') {
            $sKey   = trim($_POST['service_key']  ?? '');
            $sName  = trim($_POST['service_name'] ?? '');
            $sCat   = trim($_POST['service_cat']  ?? 'other');
            $rawKey = trim($_POST['api_key_raw']  ?? '');
            $notes  = trim($_POST['notes']        ?? '');
            $active = isset($_POST['is_active']) ? 1 : 0;

            if (!$sKey || !$sName) {
                $saveErr = 'Paramètres manquants.';
            } elseif (str_contains($rawKey, '•') && isset($allKeys[$sKey])) {
                try {
                    $pdo->prepare("UPDATE api_keys SET is_active=?, notes=?, updated_at=NOW() WHERE service_key=?")
                        ->execute([$active, $notes, $sKey]);
                    $saveMsg = '✅ Paramètres mis à jour.';
                } catch (Exception $e) { $saveErr = 'DB: '.$e->getMessage(); }
            } else {
                $encrypted = $rawKey ? akEncrypt($rawKey, $encKey) : null;
                try {
                    $pdo->prepare("INSERT INTO api_keys (service_key,service_name,api_key_encrypted,category,is_active,notes,verification_status)
                        VALUES(?,?,?,?,?,?,'unknown')
                        ON DUPLICATE KEY UPDATE
                        service_name=VALUES(service_name),
                        api_key_encrypted=COALESCE(VALUES(api_key_encrypted),api_key_encrypted),
                        category=VALUES(category), is_active=VALUES(is_active), notes=VALUES(notes),
                        verification_status='unknown', last_verified_at=NULL, updated_at=NOW()")
                        ->execute([$sKey, $sName, $encrypted, $sCat, $active, $notes]);
                    $saveMsg = '✅ Clé "'.htmlspecialchars($sName).'" enregistrée.';
                    $allKeys = [];
                    foreach ($pdo->query("SELECT * FROM api_keys") as $r) $allKeys[$r['service_key']] = $r;
                } catch (Exception $e) { $saveErr = 'DB: '.$e->getMessage(); }
            }
        }

        elseif ($action === 'delete_key') {
            $sKey = trim($_POST['service_key'] ?? '');
            try {
                $pdo->prepare("DELETE FROM api_keys WHERE service_key=?")->execute([$sKey]);
                $saveMsg = '🗑️ Clé supprimée.';
                $allKeys = [];
                foreach ($pdo->query("SELECT * FROM api_keys") as $r) $allKeys[$r['service_key']] = $r;
            } catch (Exception $e) { $saveErr = 'DB: '.$e->getMessage(); }
        }

        elseif ($action === 'test_key') {
            $sKey = trim($_POST['service_key'] ?? '');
            if (!isset($allKeys[$sKey]) || !$allKeys[$sKey]['api_key_encrypted']) {
                $saveErr = 'Aucune clé enregistrée pour ce service.';
            } else {
                $raw = akDecrypt($allKeys[$sKey]['api_key_encrypted'], $encKey);
                $status = 'unknown'; $msg = '';
                try {
                    if ($sKey === 'claude') {
                        $ch = curl_init('https://api.anthropic.com/v1/messages');
                        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_TIMEOUT=>12,
                            CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$raw,'anthropic-version: 2023-06-01'],
                            CURLOPT_POSTFIELDS=>json_encode(['model'=>'claude-haiku-4-5-20251001','max_tokens'=>5,'messages'=>[['role'=>'user','content'=>'OK']]])]);
                        curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
                        $status = $code===200 ? 'valid' : 'invalid'; $msg = 'HTTP '.$code;
                    } elseif ($sKey === 'openai') {
                        $ch = curl_init('https://api.openai.com/v1/models');
                        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>10,
                            CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$raw]]);
                        curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
                        $status = $code===200 ? 'valid' : 'invalid'; $msg = 'HTTP '.$code;
                    } elseif ($sKey === 'perplexity') {
                        $ch = curl_init('https://api.perplexity.ai/chat/completions');
                        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_POST=>true, CURLOPT_TIMEOUT=>12,
                            CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$raw],
                            CURLOPT_POSTFIELDS=>json_encode(['model'=>'sonar','messages'=>[['role'=>'user','content'=>'OK']],'max_tokens'=>5])]);
                        curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
                        $status = $code===200 ? 'valid' : 'invalid'; $msg = 'HTTP '.$code;
                    } else {
                        $saveMsg = 'ℹ️ Test automatique non disponible pour ce service.';
                    }
                    if ($status !== 'unknown') {
                        $pdo->prepare("UPDATE api_keys SET verification_status=?, last_verified_at=NOW() WHERE service_key=?")
                            ->execute([$status, $sKey]);
                        $allKeys[$sKey]['verification_status'] = $status;
                        $allKeys[$sKey]['last_verified_at']    = date('Y-m-d H:i:s');
                        if ($status === 'valid')   $saveMsg = '✅ Clé "'.$allKeys[$sKey]['service_name'].'" valide ! ('.$msg.')';
                        if ($status === 'invalid') $saveErr = '❌ Clé "'.$allKeys[$sKey]['service_name'].'" invalide. ('.$msg.')';
                    }
                } catch (Exception $e) { $saveErr = 'Erreur test : '.$e->getMessage(); }
            }
        }
    }
}

// ── Stats ─────────────────────────────────────────────────
$totalKeys   = count($allKeys);
$activeKeys  = count(array_filter($allKeys, fn($k) => $k['is_active']));
$validKeys   = count(array_filter($allKeys, fn($k) => $k['verification_status']==='valid'));
$invalidKeys = count(array_filter($allKeys, fn($k) => $k['verification_status']==='invalid'));
$configuredKeys = count(array_filter($allKeys, fn($k) => !empty($k['api_key_encrypted'])));
$activeTab  = $_GET['tab'] ?? 'all';
?>

<style>
.ak-wrap{max-width:1000px}
.ak-banner{background:linear-gradient(135deg,#1e40af,#1d4ed8);border-radius:var(--radius-lg);padding:22px 28px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;position:relative;overflow:hidden;flex-wrap:wrap;gap:14px}
.ak-banner::after{content:'';position:absolute;top:-30%;right:-2%;width:200px;height:200px;background:radial-gradient(circle,rgba(255,255,255,.06),transparent 70%);border-radius:50%;pointer-events:none}
.ak-banner h2{font-size:1.3rem;font-weight:800;color:#fff;margin:0 0 4px;display:flex;align-items:center;gap:10px}
.ak-banner p{color:rgba(255,255,255,.7);font-size:.82rem;margin:0}
.ak-stats-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px}
.ak-stat{flex:1;min-width:110px;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:14px 16px;text-align:center}
.ak-stat-val{font-size:1.55rem;font-weight:900;line-height:1}
.ak-stat-lbl{font-size:.6rem;text-transform:uppercase;letter-spacing:.07em;color:var(--text-2);margin-top:4px;font-weight:700}
.ak-stat.conf .ak-stat-val{color:#1d4ed8}.ak-stat.valid .ak-stat-val{color:#10b981}.ak-stat.invalid .ak-stat-val{color:#ef4444}
.ak-tabs{display:flex;gap:3px;margin-bottom:20px;background:var(--surface);padding:5px;border-radius:11px;border:1px solid var(--border);overflow-x:auto;width:fit-content;max-width:100%}
.ak-tab{display:flex;align-items:center;gap:6px;padding:7px 15px;border-radius:8px;border:none;font-size:.75rem;font-weight:700;cursor:pointer;transition:all .15s;background:transparent;color:var(--text-2);font-family:inherit;white-space:nowrap}
.ak-tab:hover{color:#1d4ed8;background:#eff6ff}.ak-tab.active{background:#1d4ed8;color:#fff;box-shadow:0 2px 8px rgba(29,78,216,.25)}
.ak-group{margin-bottom:22px}
.ak-group-hd{display:flex;align-items:center;gap:10px;padding:10px 16px;background:var(--surface-2);border:1px solid var(--border);border-radius:10px;margin-bottom:8px}
.ak-group-ic{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:.78rem;color:#fff;flex-shrink:0}
.ak-group-lbl{font-size:.8rem;font-weight:800;color:var(--text);flex:1}
.ak-group-cnt{font-size:.62rem;background:var(--surface);border:1px solid var(--border);padding:2px 8px;border-radius:7px;color:var(--text-2);font-weight:700}
.ak-card{background:var(--surface);border:1.5px solid var(--border);border-radius:12px;margin-bottom:8px;overflow:hidden;transition:border-color .2s,box-shadow .2s}
.ak-card:hover{border-color:#93c5fd;box-shadow:0 2px 12px rgba(29,78,216,.07)}
.ak-card.is-valid{border-left:3px solid #10b981}.ak-card.is-invalid{border-left:3px solid #ef4444}.ak-card.is-unknown{border-left:3px solid #d1d5db}
.ak-card-hd{display:flex;align-items:center;justify-content:space-between;padding:13px 16px;gap:12px;cursor:pointer;user-select:none}
.ak-card-left{display:flex;align-items:center;gap:11px;flex:1;min-width:0}
.ak-svc-ic{width:34px;height:34px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.85rem;color:#fff;flex-shrink:0}
.ak-svc-name{font-size:.83rem;font-weight:700;color:var(--text)}
.ak-svc-key{font-size:.68rem;color:var(--text-3);font-family:'Courier New',monospace;margin-top:1px}
.ak-card-right{display:flex;align-items:center;gap:8px;flex-shrink:0}
.ak-badge{display:inline-flex;align-items:center;gap:4px;font-size:.62rem;font-weight:700;padding:2px 8px;border-radius:7px}
.ak-badge.valid{background:#d1fae5;color:#065f46}.ak-badge.invalid{background:#fee2e2;color:#991b1b}
.ak-badge.unknown{background:#f3f4f6;color:#6b7280}.ak-badge.expired{background:#fef3c7;color:#92400e}
.ak-chev{color:var(--text-3);font-size:.7rem;transition:transform .2s}
.ak-card-body{display:none;padding:16px;border-top:1px solid var(--border);background:#fafbff}
.ak-card-body.open{display:block}
.ak-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:640px){.ak-form-grid{grid-template-columns:1fr}}
.ak-field{display:flex;flex-direction:column;gap:5px}.ak-field.full{grid-column:1/-1}
.ak-lbl{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--text-2);display:flex;align-items:center;gap:6px;justify-content:space-between}
.ak-lbl a{font-size:.62rem;color:#1d4ed8;text-decoration:none;font-weight:600;text-transform:none;letter-spacing:0}
.ak-lbl a:hover{text-decoration:underline}
.ak-input{width:100%;padding:9px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:.8rem;color:var(--text);background:#fff;font-family:inherit;outline:none;transition:border .15s;box-sizing:border-box}
.ak-input:focus{border-color:#1d4ed8;box-shadow:0 0 0 3px rgba(29,78,216,.1)}
.ak-input.key{font-family:'Courier New',monospace;font-size:.75rem;letter-spacing:.03em;padding-right:40px}
.ak-iw{position:relative}
.ak-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-3);font-size:.85rem;transition:color .15s;padding:2px}
.ak-eye:hover{color:#1d4ed8}
.ak-vinfo{font-size:.68rem;color:var(--text-3);display:flex;align-items:center;gap:5px;margin-bottom:12px}
.ak-actions{display:flex;align-items:center;justify-content:space-between;margin-top:14px;flex-wrap:wrap;gap:8px}
.ak-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;border:none;font-size:.75rem;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;text-decoration:none}
.ak-btn-primary{background:#1d4ed8;color:#fff;box-shadow:0 2px 6px rgba(29,78,216,.2)}.ak-btn-primary:hover{background:#1e40af;transform:translateY(-1px)}
.ak-btn-test{background:#f0fdf4;color:#10b981;border:1.5px solid #86efac}.ak-btn-test:hover{background:#d1fae5}
.ak-btn-del{background:#fff1f1;color:#ef4444;border:1.5px solid #fca5a5}.ak-btn-del:hover{background:#fee2e2}
.ak-btn-sm{padding:6px 12px;font-size:.7rem;border-radius:7px}
.ak-usage{display:flex;gap:16px;margin-top:12px;padding:10px 14px;background:#eff6ff;border-radius:8px;border:1px solid #bfdbfe;flex-wrap:wrap}
.ak-usage-item{display:flex;flex-direction:column}
.ak-usage-val{font-size:.85rem;font-weight:800;color:#1d4ed8}
.ak-usage-lbl{font-size:.6rem;color:#3b82f6;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
.ak-alert{padding:11px 16px;border-radius:9px;margin-bottom:18px;display:flex;align-items:center;gap:10px;font-size:.78rem;font-weight:600}
.ak-alert.success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}.ak-alert.error{background:#fff1f2;color:#991b1b;border:1px solid #fca5a5}
.ak-info{background:#eff6ff;border:1px solid #bfdbfe;border-radius:9px;padding:11px 14px;margin-bottom:18px;font-size:.76rem;color:#1e40af;display:flex;gap:9px}
.ak-info i{font-size:.82rem;flex-shrink:0;margin-top:1px}
.ak-sw{position:relative;display:inline-block;width:38px;height:21px;flex-shrink:0}
.ak-sw input{opacity:0;width:0;height:0}
.ak-sw-s{position:absolute;cursor:pointer;inset:0;background:#d1d5db;border-radius:21px;transition:.2s}
.ak-sw-s:before{content:'';position:absolute;height:15px;width:15px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s}
input:checked+.ak-sw-s{background:#1d4ed8}
input:checked+.ak-sw-s:before{transform:translateX(17px)}
</style>

<div class="ak-wrap">

<div class="ak-banner anim">
    <div style="position:relative;z-index:1">
        <h2><i class="fas fa-key"></i> Clés API & Intégrations</h2>
        <p>Gérez et sécurisez vos clés API — stockées chiffrées AES-256</p>
    </div>
    <div style="display:flex;align-items:center;gap:10px;position:relative;z-index:1;flex-wrap:wrap">
        <a href="?page=ai-settings" class="ak-btn ak-btn-sm" style="background:rgba(255,255,255,.1);color:#fff;border:1.5px solid rgba(255,255,255,.25)"><i class="fas fa-robot"></i> Paramètres IA</a>
        <a href="?page=settings"    class="ak-btn ak-btn-sm" style="background:rgba(255,255,255,.1);color:#fff;border:1.5px solid rgba(255,255,255,.25)"><i class="fas fa-cog"></i> Configuration</a>
    </div>
</div>

<?php if ($saveMsg): ?><div class="ak-alert success anim"><i class="fas fa-check-circle"></i><?= htmlspecialchars($saveMsg) ?></div><?php endif; ?>
<?php if ($saveErr): ?><div class="ak-alert error anim"><i class="fas fa-exclamation-circle"></i><?= htmlspecialchars($saveErr) ?></div><?php endif; ?>

<div class="ak-stats-row anim">
    <div class="ak-stat"><div class="ak-stat-val"><?= $totalKeys ?></div><div class="ak-stat-lbl">En base</div></div>
    <div class="ak-stat conf"><div class="ak-stat-val"><?= $configuredKeys ?></div><div class="ak-stat-lbl">Configurées</div></div>
    <div class="ak-stat valid"><div class="ak-stat-val"><?= $validKeys ?></div><div class="ak-stat-lbl">Valides</div></div>
    <div class="ak-stat invalid"><div class="ak-stat-val"><?= $invalidKeys ?></div><div class="ak-stat-lbl">Invalides</div></div>
</div>

<div class="ak-info anim"><i class="fas fa-lock"></i><span>Les clés sont chiffrées AES-256 avant stockage. Jamais affichées en clair dans l'interface ou les logs.</span></div>

<div class="ak-tabs anim">
    <button class="ak-tab <?= $activeTab==='all'?'active':'' ?>" onclick="akTab('all')"><i class="fas fa-th"></i> Tous</button>
    <?php foreach ($catalog as $ck => $cat): ?>
    <button class="ak-tab <?= $activeTab===$ck?'active':'' ?>" onclick="akTab('<?= $ck ?>')">
        <i class="fas <?= $cat['icon'] ?>"></i> <?= $cat['label'] ?>
    </button>
    <?php endforeach; ?>
</div>

<?php foreach ($catalog as $catKey => $cat): ?>
<div class="ak-group anim ak-cat" data-cat="<?= $catKey ?>" <?= ($activeTab!=='all'&&$activeTab!==$catKey)?'style="display:none"':'' ?>>

    <?php
    $nbConf = count(array_filter(array_keys($cat['services']), fn($k) => isset($allKeys[$k]) && !empty($allKeys[$k]['api_key_encrypted'])));
    $nbTotal = count($cat['services']);
    ?>
    <div class="ak-group-hd">
        <div class="ak-group-ic" style="background:<?= $cat['color'] ?>"><i class="fas <?= $cat['icon'] ?>"></i></div>
        <span class="ak-group-lbl"><?= $cat['label'] ?></span>
        <span class="ak-group-cnt"><?= $nbConf ?>/<?= $nbTotal ?> configuré(s)</span>
    </div>

    <?php foreach ($cat['services'] as $svcKey => $svc):
        $e       = $allKeys[$svcKey] ?? null;
        $hasKey  = $e && !empty($e['api_key_encrypted']);
        $vstatus = $e['verification_status'] ?? 'unknown';
        $usage   = $usageStats[$svcKey] ?? null;
        $isFab   = str_starts_with($svc['icon'], 'fab');
        $canTest = in_array($svcKey, $testableServices);
    ?>
    <div class="ak-card <?= $hasKey ? 'is-'.$vstatus : 'is-unknown' ?>" id="ak-card-<?= $svcKey ?>">

        <div class="ak-card-hd" onclick="akToggle('<?= $svcKey ?>')">
            <div class="ak-card-left">
                <div class="ak-svc-ic" style="background:<?= $svc['color'] ?>">
                    <i class="<?= $isFab ? $svc['icon'] : 'fas '.$svc['icon'] ?>"></i>
                </div>
                <div>
                    <div class="ak-svc-name"><?= htmlspecialchars($svc['name']) ?></div>
                    <div class="ak-svc-key"><?= $hasKey ? akMask(akDecrypt($e['api_key_encrypted'], $encKey)) : 'Non configurée' ?></div>
                </div>
            </div>
            <div class="ak-card-right">
                <?= $hasKey ? akBadge($vstatus) : '<span class="ak-badge unknown"><i class="fas fa-plus"></i> À configurer</span>' ?>
                <i class="fas fa-chevron-down ak-chev" id="ak-chev-<?= $svcKey ?>"></i>
            </div>
        </div>

        <div class="ak-card-body" id="ak-body-<?= $svcKey ?>">
            <?php if ($e && $e['last_verified_at']): ?>
            <div class="ak-vinfo"><i class="fas fa-clock"></i> Vérifié le <?= date('d/m/Y à H:i', strtotime($e['last_verified_at'])) ?></div>
            <?php endif; ?>

            <form method="POST">
            <input type="hidden" name="csrf_token"   value="<?= $csrf ?>">
            <input type="hidden" name="ak_action"    value="save_key">
            <input type="hidden" name="service_key"  value="<?= $svcKey ?>">
            <input type="hidden" name="service_name" value="<?= htmlspecialchars($svc['name']) ?>">
            <input type="hidden" name="service_cat"  value="<?= $catKey ?>">

            <div class="ak-form-grid">
                <div class="ak-field full">
                    <div class="ak-lbl">
                        <span><i class="fas fa-key"></i> Clé API</span>
                        <a href="<?= $svc['doc'] ?>" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> Obtenir la clé</a>
                    </div>
                    <div class="ak-iw">
                        <input type="password" name="api_key_raw" id="ak-inp-<?= $svcKey ?>" class="ak-input key"
                            value="<?= $hasKey ? htmlspecialchars(akMask(akDecrypt($e['api_key_encrypted'], $encKey))) : '' ?>"
                            placeholder="<?= htmlspecialchars($svc['ph']) ?>" autocomplete="off">
                        <button type="button" class="ak-eye" onclick="akEye('<?= $svcKey ?>',this)"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="ak-field">
                    <div class="ak-lbl"><i class="fas fa-toggle-on"></i> Statut</div>
                    <div style="display:flex;align-items:center;gap:10px;padding:9px 0">
                        <label class="ak-sw">
                            <input type="checkbox" name="is_active" value="1" <?= ($e['is_active'] ?? 1) ? 'checked' : '' ?>>
                            <span class="ak-sw-s"></span>
                        </label>
                        <span style="font-size:.75rem;color:var(--text-2);font-weight:600"><?= ($e['is_active'] ?? 1) ? 'Activé' : 'Désactivé' ?></span>
                    </div>
                </div>
                <div class="ak-field">
                    <div class="ak-lbl"><i class="fas fa-sticky-note"></i> Notes</div>
                    <input type="text" name="notes" class="ak-input"
                        value="<?= htmlspecialchars($e['notes'] ?? '') ?>"
                        placeholder="Usage, projet, compte…">
                </div>
            </div>

            <?php if ($usage && ($usage['calls_today'] > 0 || $usage['calls_month'] > 0)): ?>
            <div class="ak-usage">
                <div class="ak-usage-item"><span class="ak-usage-val"><?= (int)$usage['calls_today'] ?></span><span class="ak-usage-lbl">Aujourd'hui</span></div>
                <div class="ak-usage-item"><span class="ak-usage-val"><?= (int)$usage['calls_month'] ?></span><span class="ak-usage-lbl">Ce mois</span></div>
                <?php if ((float)$usage['cost_this_month'] > 0): ?>
                <div class="ak-usage-item"><span class="ak-usage-val"><?= number_format((float)$usage['cost_this_month'], 3) ?>$</span><span class="ak-usage-lbl">Coût</span></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="ak-actions">
                <div style="display:flex;gap:7px;flex-wrap:wrap;align-items:center">
                    <?php if ($hasKey && $canTest): ?>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="csrf_token"  value="<?= $csrf ?>">
                        <input type="hidden" name="ak_action"   value="test_key">
                        <input type="hidden" name="service_key" value="<?= $svcKey ?>">
                        <button type="submit" class="ak-btn ak-btn-test ak-btn-sm"><i class="fas fa-plug"></i> Tester</button>
                    </form>
                    <?php endif; ?>
                    <?php if ($hasKey): ?>
                    <form method="POST" style="margin:0" onsubmit="return confirm('Supprimer cette clé ?')">
                        <input type="hidden" name="csrf_token"  value="<?= $csrf ?>">
                        <input type="hidden" name="ak_action"   value="delete_key">
                        <input type="hidden" name="service_key" value="<?= $svcKey ?>">
                        <button type="submit" class="ak-btn ak-btn-del ak-btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php else: ?>
                    <span style="font-size:.68rem;color:var(--text-3);font-style:italic">Pas encore enregistrée</span>
                    <?php endif; ?>
                </div>
                <button type="submit" class="ak-btn ak-btn-primary ak-btn-sm"><i class="fas fa-save"></i> Enregistrer</button>
            </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

</div>

<script>
function akTab(cat) {
    document.querySelectorAll('.ak-cat').forEach(g => g.style.display = (cat==='all' || g.dataset.cat===cat) ? '' : 'none');
    document.querySelectorAll('.ak-tab').forEach(b => b.classList.toggle('active', b.getAttribute('onclick')?.includes("'"+cat+"'")));
    history.replaceState(null, '', '?page=api-keys&tab='+cat);
}
function akToggle(key) {
    const body = document.getElementById('ak-body-'+key);
    const chev = document.getElementById('ak-chev-'+key);
    const open = body.classList.contains('open');
    document.querySelectorAll('.ak-card-body').forEach(b => b.classList.remove('open'));
    document.querySelectorAll('.ak-chev').forEach(c => c.style.transform = '');
    if (!open) {
        body.classList.add('open');
        chev.style.transform = 'rotate(180deg)';
        body.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}
function akEye(key, btn) {
    const i = document.getElementById('ak-inp-'+key);
    i.type = i.type === 'password' ? 'text' : 'password';
    btn.innerHTML = i.type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
}
const _h = location.hash.replace('#','');
if (_h) akToggle(_h);
</script>