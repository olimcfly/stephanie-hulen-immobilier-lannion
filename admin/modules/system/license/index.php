<?php
/**
 * /admin/modules/system/license/index.php — v2.0
 * Module Licence — IMMO LOCAL+
 * Design harmonisé avec modules.php
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);

ob_start();

$rootPath = dirname(__DIR__, 3); // → public_html
if (!defined('DB_HOST'))       require_once $rootPath . '/config/config.php';
if (!class_exists('Database')) require_once $rootPath . '/includes/classes/Database.php';

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    ob_end_clean();
    die('<div style="padding:20px;color:#dc2626;font-family:monospace">❌ DB: ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// ── Constantes ────────────────────────────────────────
// URL du serveur de licence Ecosystème Immo (à adapter)
defined('LICENSE_API_URL') or define('LICENSE_API_URL', 'https://api.ecosystemeimmo.fr/v1/license');
defined('LICENSE_SITE_URL') or define('LICENSE_SITE_URL', (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

// ── Helpers ───────────────────────────────────────────
function licGetSetting(PDO $db, string $key, string $default = ''): string {
    try {
        $s = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $s->execute([$key]);
        $r = $s->fetchColumn();
        return $r !== false ? (string)$r : $default;
    } catch (Exception $e) { return $default; }
}

function licSetSetting(PDO $db, string $key, string $value): void {
    try {
        $s = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=?");
        $s->execute([$key, $value, $value]);
    } catch (Exception $e) {}
}

function licAddHistory(PDO $db, string $action, string $status, string $detail = ''): void {
    try {
        $db->prepare("INSERT INTO license_history (action, status, detail, created_at) VALUES (?,?,?,NOW())")->execute([$action, $status, $detail]);
    } catch (Exception $e) {
        // Table optionnelle — on ignore si absente
    }
}

function licCallApi(string $licenseKey, string $action = 'activate'): array {
    if (!$licenseKey) return ['success' => false, 'message' => 'Clé vide'];

    $payload = json_encode([
        'license_key' => $licenseKey,
        'domain'      => LICENSE_SITE_URL,
        'action'      => $action,
    ]);

    $ch = curl_init(LICENSE_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) return ['success' => false, 'message' => 'Erreur réseau: ' . $curlErr];
    if (!$response) return ['success' => false, 'message' => 'Réponse vide du serveur'];

    $data = json_decode($response, true);
    if (!$data) return ['success' => false, 'message' => 'Réponse invalide du serveur'];
    return $data;
}

// ── AJAX ──────────────────────────────────────────────
$rawBody    = file_get_contents('php://input');
$jsonBody   = json_decode($rawBody, true) ?? [];
$ajaxAction = $_POST['ajax_action'] ?? ($jsonBody['ajax_action'] ?? null);

if ($ajaxAction) {
    header('Content-Type: application/json; charset=utf-8');
    ob_end_clean();

    // ── Vérifier / activer ────────────────────────────
    if ($ajaxAction === 'verify' || $ajaxAction === 'activate') {
        $key    = trim($_POST['license_key'] ?? ($jsonBody['license_key'] ?? licGetSetting($db, 'license_key')));
        $result = licCallApi($key, 'activate');

        if ($result['success'] ?? false) {
            licSetSetting($db, 'license_key',         $key);
            licSetSetting($db, 'license_status',      $result['status']      ?? 'active');
            licSetSetting($db, 'license_plan',        $result['plan']        ?? 'standard');
            licSetSetting($db, 'license_holder',      $result['holder']      ?? '');
            licSetSetting($db, 'license_expires_at',  $result['expires_at']  ?? '');
            licSetSetting($db, 'license_domain',      $result['domain']      ?? LICENSE_SITE_URL);
            licSetSetting($db, 'license_verified_at', date('Y-m-d H:i:s'));
            licAddHistory($db, 'activate', 'OK', 'Plan=' . ($result['plan'] ?? '') . ' Status=' . ($result['status'] ?? ''));
        } else {
            licAddHistory($db, 'activate', 'CONNECTION_ERROR', $result['message'] ?? 'Échec');
        }

        echo json_encode(['success' => $result['success'] ?? false, 'message' => $result['message'] ?? 'Erreur', 'data' => $result]);
        exit;
    }

    // ── Désactiver ────────────────────────────────────
    if ($ajaxAction === 'deactivate') {
        $key = licGetSetting($db, 'license_key');
        licCallApi($key, 'deactivate');
        licSetSetting($db, 'license_status', 'inactive');
        licAddHistory($db, 'deactivate', 'OK', 'Désactivée manuellement');
        echo json_encode(['success' => true, 'message' => 'Licence désactivée']);
        exit;
    }

    // ── Sauvegarder clé (sans vérifier) ──────────────
    if ($ajaxAction === 'save_key') {
        $key = trim($jsonBody['license_key'] ?? '');
        if ($key) {
            licSetSetting($db, 'license_key', $key);
            licAddHistory($db, 'save_key', 'OK', 'Clé enregistrée');
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Clé vide']);
        }
        exit;
    }

    echo json_encode(['error' => 'Action inconnue']);
    exit;
}

// ── Lecture des données ───────────────────────────────
$licKey        = licGetSetting($db, 'license_key');
$licStatus     = licGetSetting($db, 'license_status', 'unknown');
$licPlan       = licGetSetting($db, 'license_plan', '—');
$licHolder     = licGetSetting($db, 'license_holder', '—');
$licExpiresAt  = licGetSetting($db, 'license_expires_at', '');
$licDomain     = licGetSetting($db, 'license_domain', LICENSE_SITE_URL);
$licVerifiedAt = licGetSetting($db, 'license_verified_at', '');

// Vérification si la dernière vérif date de plus de 24h
$needsReverify = false;
if ($licVerifiedAt) {
    $diff = time() - strtotime($licVerifiedAt);
    $needsReverify = $diff > 86400; // > 24h
}

// Historique (table optionnelle)
$history = [];
try {
    $history = $db->query("SELECT * FROM license_history ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Masquer la clé : affiche début et fin
function maskKey(string $key): string {
    if (strlen($key) < 12) return str_repeat('•', strlen($key));
    return substr($key, 0, 8) . str_repeat('•', max(4, strlen($key) - 16)) . substr($key, -8);
}

$isActive   = $licStatus === 'active';
$statusInfo = match($licStatus) {
    'active'   => ['label' => 'ACTIVE',   'color' => 'var(--green)', 'bg' => 'var(--green-bg)',  'icon' => 'fa-check-circle'],
    'inactive' => ['label' => 'INACTIVE', 'color' => 'var(--red)',   'bg' => 'var(--red-bg)',    'icon' => 'fa-times-circle'],
    'expired'  => ['label' => 'EXPIRÉE',  'color' => 'var(--red)',   'bg' => 'var(--red-bg)',    'icon' => 'fa-clock'],
    'trial'    => ['label' => 'ESSAI',    'color' => 'var(--amber)', 'bg' => 'var(--amber-bg)',  'icon' => 'fa-hourglass-half'],
    default    => ['label' => 'INCONNUE', 'color' => 'var(--text-3)','bg' => 'var(--surface-3)', 'icon' => 'fa-question-circle'],
};

$planColors = [
    'beta'       => ['color' => '#8b5cf6', 'bg' => '#8b5cf618'],
    'standard'   => ['color' => '#3b82f6', 'bg' => '#3b82f618'],
    'pro'        => ['color' => '#f59e0b', 'bg' => '#f59e0b18'],
    'enterprise' => ['color' => '#10b981', 'bg' => '#10b98118'],
];
$planStyle = $planColors[strtolower($licPlan)] ?? ['color' => 'var(--accent)', 'bg' => 'var(--accent-bg)'];

$proxyUrl = '/admin/dashboard.php?page=system/license';

ob_end_clean();
?>

<style>
/* ══ Licence v2 — design harmonisé modules.php ══════════════ */
.lic-header-bar {
    display:flex; align-items:center; justify-content:space-between;
    margin-bottom:20px; flex-wrap:wrap; gap:12px;
}

/* ── Score / status cards (même structure que mod-scores) ── */
.lic-scores {
    display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin-bottom:16px;
}
.lic-score-main {
    background:var(--surface); border:2px solid var(--accent);
    border-radius:var(--radius-lg); padding:16px;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    box-shadow:var(--shadow-sm);
}
.lic-score-main .lic-status-val { font-size:22px; font-weight:900; line-height:1; }
.lic-score-main .lic-lbl { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-3); margin-top:4px; }

/* ── Alerte re-vérification ── */
.lic-alert {
    display:flex; align-items:center; gap:10px;
    background:var(--amber-bg); border:1px solid var(--amber);
    border-radius:var(--radius); padding:10px 16px; margin-bottom:16px;
    font-size:12px; color:var(--amber);
}
.lic-alert-ok {
    background:var(--green-bg); border-color:var(--green); color:var(--green);
}

/* ── Card principale licence ── */
.lic-card {
    background:var(--surface); border:1px solid var(--border);
    border-radius:var(--radius-lg); overflow:hidden;
    box-shadow:var(--shadow-sm); margin-bottom:16px;
}
.lic-card.active   { border-top:3px solid var(--green); }
.lic-card.inactive { border-top:3px solid var(--red); }
.lic-card.unknown  { border-top:3px solid var(--border); }

.lic-card-hd {
    padding:16px 20px; display:flex; align-items:center; gap:14px;
    border-bottom:1px solid var(--border); background:var(--surface-2);
}
.lic-card-icon {
    width:44px; height:44px; border-radius:var(--radius);
    display:flex; align-items:center; justify-content:center;
    font-size:18px; flex-shrink:0;
}
.lic-card-title { font-size:15px; font-weight:800; color:var(--text); }
.lic-card-sub   { font-size:11px; color:var(--text-3); margin-top:2px; }
.lic-card-status {
    margin-left:auto; display:flex; align-items:center; gap:8px;
}
.lic-status-pill {
    padding:5px 14px; border-radius:99px; font-size:11px; font-weight:800;
    display:flex; align-items:center; gap:6px;
}

/* Grille infos ── */
.lic-info-grid {
    display:grid; grid-template-columns:repeat(3,1fr); gap:0;
}
.lic-info-cell {
    padding:14px 20px; border-right:1px solid var(--border);
    border-bottom:1px solid var(--border);
}
.lic-info-cell:nth-child(3n) { border-right:none; }
.lic-info-cell:nth-last-child(-n+3) { border-bottom:none; }
.lic-info-label {
    font-size:9px; font-weight:700; text-transform:uppercase;
    letter-spacing:.08em; color:var(--text-3); margin-bottom:6px;
}
.lic-info-val {
    font-size:13px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:6px;
}
.lic-key-wrap {
    display:flex; align-items:center; gap:8px; margin:14px 20px;
    background:var(--surface-2); border:1px solid var(--border);
    border-radius:var(--radius); padding:10px 14px;
}
.lic-key-val {
    font-family:var(--mono); font-size:12px; color:var(--text-2);
    letter-spacing:.05em; flex:1; word-break:break-all;
}
.lic-copy-btn {
    border:none; background:transparent; color:var(--text-3);
    cursor:pointer; padding:4px 8px; border-radius:var(--radius);
    font-size:11px; transition:all .13s; display:flex; align-items:center; gap:4px;
    font-family:var(--font); font-weight:600;
}
.lic-copy-btn:hover { background:var(--surface-3); color:var(--text); }

/* ── Section saisie clé ── */
.lic-input-section {
    background:var(--surface); border:1px solid var(--border);
    border-radius:var(--radius-lg); padding:18px 20px;
    margin-bottom:16px; box-shadow:var(--shadow-sm);
}
.lic-input-section-title {
    font-size:11px; font-weight:800; text-transform:uppercase;
    letter-spacing:.08em; color:var(--text-3); margin-bottom:12px;
    display:flex; align-items:center; gap:7px;
}
.lic-key-input-wrap {
    display:flex; gap:8px; align-items:center;
}
.lic-key-input {
    flex:1; padding:9px 12px; border-radius:var(--radius);
    border:1px solid var(--border); background:var(--surface-2);
    color:var(--text); font-size:12px; font-family:var(--mono);
    outline:none; transition:border-color .15s;
}
.lic-key-input:focus { border-color:var(--accent); }
.lic-key-input::placeholder { color:var(--text-3); font-family:var(--font); }

/* ── Actions ── */
.lic-actions {
    display:flex; gap:8px; align-items:center; flex-wrap:wrap;
}
.lic-btn-verify {
    padding:8px 18px; border-radius:var(--radius); border:none;
    background:var(--accent); color:#fff; cursor:pointer;
    font-weight:700; font-size:12px; transition:all .13s;
    display:flex; align-items:center; gap:7px; font-family:var(--font);
}
.lic-btn-verify:hover { background:#4f46e5; }
.lic-btn-verify:disabled { background:var(--surface-3); color:var(--text-3); cursor:not-allowed; }
.lic-btn-deactivate {
    padding:8px 16px; border-radius:var(--radius);
    border:1px solid var(--red); background:transparent;
    color:var(--red); cursor:pointer; font-weight:700; font-size:12px;
    transition:all .13s; display:flex; align-items:center; gap:7px;
    font-family:var(--font);
}
.lic-btn-deactivate:hover { background:var(--red); color:#fff; }

/* ── Historique (même style que db-health-card) ── */
.lic-history-card {
    background:var(--surface); border:1px solid var(--border);
    border-radius:var(--radius-lg); overflow:hidden;
    box-shadow:var(--shadow-sm); margin-bottom:16px;
}
.lic-history-hd {
    padding:11px 16px; border-bottom:1px solid var(--border);
    font-size:11px; font-weight:700; display:flex; align-items:center;
    justify-content:space-between; background:var(--surface-2); color:var(--text-2);
}
.lic-history-row {
    display:flex; align-items:center; gap:10px;
    padding:8px 16px; border-bottom:1px solid var(--border);
    font-size:11px; transition:background .12s;
}
.lic-history-row:last-child { border-bottom:none; }
.lic-history-row:hover { background:var(--surface-2); }
.lic-h-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.lic-h-dot.ok      { background:var(--green); box-shadow:0 0 4px var(--green); }
.lic-h-dot.error   { background:var(--red);   box-shadow:0 0 4px var(--red); }
.lic-h-dot.warning { background:var(--amber); box-shadow:0 0 4px var(--amber); }
.lic-h-action { font-weight:700; color:var(--text); min-width:90px; font-family:var(--mono); font-size:10px; }
.lic-h-status { font-size:10px; padding:2px 7px; border-radius:4px; font-weight:700; }
.lic-h-status.ok      { background:var(--green-bg); color:var(--green); }
.lic-h-status.error   { background:var(--red-bg);   color:var(--red); }
.lic-h-status.warning { background:var(--amber-bg); color:var(--amber); }
.lic-h-detail { color:var(--text-3); flex:1; font-family:var(--mono); font-size:10px; }
.lic-h-date   { color:var(--text-3); font-size:9px; flex-shrink:0; }

/* ── Toast (même que modules.php) ── */
.lic-toast {
    position:fixed; bottom:16px; right:16px; background:var(--surface);
    border:1px solid var(--border); color:var(--text); padding:9px 14px;
    border-radius:var(--radius); font-size:11px; z-index:9999;
    opacity:0; transform:translateY(5px); transition:all .2s;
    pointer-events:none; box-shadow:var(--shadow);
}
.lic-toast.show { opacity:1; transform:translateY(0); }

/* ── Empty state ── */
.lic-empty {
    padding:40px 20px; text-align:center;
}
.lic-empty-icon {
    width:56px; height:56px; border-radius:var(--radius-lg);
    background:var(--surface-3); display:flex; align-items:center;
    justify-content:center; font-size:22px; color:var(--text-3);
    margin:0 auto 14px;
}

@media(max-width:760px) {
    .lic-scores       { grid-template-columns:1fr 1fr; }
    .lic-info-grid    { grid-template-columns:1fr 1fr; }
    .lic-key-input-wrap { flex-direction:column; }
}
</style>

<!-- ════════ PAGE HEADER ════════ -->
<div class="page-hd">
    <div>
        <h1>Licence</h1>
        <div class="page-hd-sub">
            Gestion de la licence IMMO LOCAL+ · <?= htmlspecialchars($licDomain ?: LICENSE_SITE_URL) ?>
        </div>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
        <button class="btn btn-s btn-sm" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Actualiser
        </button>
        <button class="btn btn-p btn-sm" id="btn-reverify" onclick="doVerify()">
            <i class="fas fa-shield-check"></i> Re-vérifier
        </button>
    </div>
</div>

<!-- ── Score cards (style mod-scores) ── -->
<div class="lic-scores anim">
    <div class="lic-score-main">
        <div class="lic-status-val" style="color:<?= $statusInfo['color'] ?>">
            <i class="fas <?= $statusInfo['icon'] ?>" style="font-size:24px"></i>
        </div>
        <div class="lic-lbl"><?= $statusInfo['label'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:<?= $planStyle['bg'] ?>;color:<?= $planStyle['color'] ?>">
            <i class="fas fa-layer-group"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val" style="font-size:14px;text-transform:uppercase"><?= htmlspecialchars(strtoupper($licPlan)) ?></div>
            <div class="stat-label">Plan</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--accent-bg);color:var(--accent)">
            <i class="fas fa-user"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val" style="font-size:13px"><?= htmlspecialchars($licHolder ?: '—') ?></div>
            <div class="stat-label">Titulaire</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--amber-bg);color:var(--amber)">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val" style="font-size:13px">
                <?= $licExpiresAt ? htmlspecialchars($licExpiresAt) : '∞ Illimitée' ?>
            </div>
            <div class="stat-label">Expiration</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--surface-3);color:var(--text-3)">
            <i class="fas fa-history"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val"><?= count($history) ?></div>
            <div class="stat-label">Vérifications</div>
        </div>
    </div>
</div>

<!-- ── Alerte re-vérification ── -->
<?php if ($needsReverify && $licKey): ?>
<div class="lic-alert anim">
    <i class="fas fa-triangle-exclamation"></i>
    <span>
        Dernière vérification il y a plus de 24h. Cliquez sur
        <strong>« Re-vérifier »</strong> pour confirmer le statut.
    </span>
    <button class="btn btn-s btn-sm" style="margin-left:auto" onclick="doVerify()">
        <i class="fas fa-sync-alt"></i> Vérifier
    </button>
</div>
<?php elseif ($licKey && $isActive): ?>
<div class="lic-alert lic-alert-ok anim">
    <i class="fas fa-check-circle"></i>
    <span>Licence valide — Vérifiée le <?= htmlspecialchars($licVerifiedAt ?: '—') ?></span>
</div>
<?php endif; ?>

<!-- ── Card principale (si clé présente) ── -->
<?php if ($licKey): ?>
<div class="lic-card <?= $isActive ? 'active' : ($licStatus === 'inactive' ? 'inactive' : 'unknown') ?> anim">

    <!-- Header card -->
    <div class="lic-card-hd">
        <div class="lic-card-icon" style="background:<?= $statusInfo['bg'] ?>;color:<?= $statusInfo['color'] ?>">
            <i class="fas fa-id-card"></i>
        </div>
        <div>
            <div class="lic-card-title">
                Licence <?= htmlspecialchars(strtoupper($licPlan)) ?>
                <span style="font-size:10px;padding:2px 8px;border-radius:4px;background:<?= $planStyle['bg'] ?>;color:<?= $planStyle['color'] ?>;margin-left:6px">
                    <?= htmlspecialchars(strtoupper($licPlan)) ?>
                </span>
            </div>
            <div class="lic-card-sub">
                Votre plateforme Écosystème Immo est
                <?= $isActive ? 'pleinement opérationnelle' : 'en attente d\'activation' ?>.
                <?php if ($licHolder): ?>
                Licence attribuée à <strong><?= htmlspecialchars($licHolder) ?></strong>.
                <?php endif; ?>
            </div>
        </div>
        <div class="lic-card-status">
            <div class="lic-status-pill" style="background:<?= $statusInfo['bg'] ?>;color:<?= $statusInfo['color'] ?>">
                <i class="fas <?= $statusInfo['icon'] ?>"></i>
                <?= $statusInfo['label'] ?>
            </div>
        </div>
    </div>

    <!-- Clé masquée avec bouton copier -->
    <div class="lic-key-wrap">
        <i class="fas fa-key" style="color:var(--amber);font-size:11px;flex-shrink:0"></i>
        <span class="lic-key-val" id="lic-key-display"><?= htmlspecialchars(maskKey($licKey)) ?></span>
        <button class="lic-copy-btn" onclick="copyKey()" title="Copier la clé">
            <i class="fas fa-copy"></i> Copier
        </button>
        <button class="lic-copy-btn" onclick="toggleKeyVisibility()" id="btn-show-key" title="Afficher/masquer">
            <i class="fas fa-eye" id="eye-icon"></i>
        </button>
    </div>

    <!-- Grille infos -->
    <div class="lic-info-grid">
        <div class="lic-info-cell">
            <div class="lic-info-label">Plan</div>
            <div class="lic-info-val" style="color:<?= $planStyle['color'] ?>">
                <i class="fas fa-layer-group" style="font-size:11px"></i>
                <?= htmlspecialchars(strtoupper($licPlan)) ?>
            </div>
        </div>
        <div class="lic-info-cell">
            <div class="lic-info-label">Statut</div>
            <div class="lic-info-val" style="color:<?= $statusInfo['color'] ?>">
                <i class="fas <?= $statusInfo['icon'] ?>" style="font-size:11px"></i>
                <?= $statusInfo['label'] ?>
            </div>
        </div>
        <div class="lic-info-cell">
            <div class="lic-info-label">Expiration</div>
            <div class="lic-info-val">
                <?= $licExpiresAt ? htmlspecialchars($licExpiresAt) : '∞ Illimitée' ?>
            </div>
        </div>
        <div class="lic-info-cell">
            <div class="lic-info-label">Domaine associé</div>
            <div class="lic-info-val" style="font-size:11px;font-family:var(--mono)">
                <?= htmlspecialchars($licDomain ?: '—') ?>
            </div>
        </div>
        <div class="lic-info-cell">
            <div class="lic-info-label">Dernière vérification</div>
            <div class="lic-info-val" style="font-size:11px">
                <?= $licVerifiedAt ? htmlspecialchars($licVerifiedAt) : '—' ?>
            </div>
        </div>
        <div class="lic-info-cell">
            <div class="lic-info-label">Activée le</div>
            <div class="lic-info-val" style="font-size:11px">
                <?php
                $firstOk = array_filter($history, fn($h) => ($h['action'] ?? '') === 'activate' && ($h['status'] ?? '') === 'OK');
                $firstOk = !empty($firstOk) ? end($firstOk) : null;
                echo $firstOk ? htmlspecialchars(substr($firstOk['created_at'], 0, 10)) : '—';
                ?>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div style="padding:14px 20px;border-top:1px solid var(--border);background:var(--surface-2);display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        <button class="lic-btn-verify" id="btn-verify-main" onclick="doVerify()">
            <i class="fas fa-sync-alt"></i> Re-vérifier la licence
        </button>
        <?php if ($isActive): ?>
        <button class="lic-btn-deactivate" onclick="confirmDeactivate()">
            <i class="fas fa-power-off"></i> Désactiver
        </button>
        <?php endif; ?>
        <span style="margin-left:auto;font-size:10px;color:var(--text-3);font-family:var(--mono)">
            ID: <?= htmlspecialchars(substr($licKey, 0, 8)) ?>...
        </span>
    </div>
</div>

<?php else: ?>
<!-- ── État vide — aucune clé ── -->
<div class="lic-card unknown anim">
    <div class="lic-empty">
        <div class="lic-empty-icon"><i class="fas fa-key"></i></div>
        <div style="font-size:14px;font-weight:700;margin-bottom:6px">Aucune licence configurée</div>
        <div style="font-size:12px;color:var(--text-3);max-width:400px;margin:0 auto">
            Entrez votre clé de licence ci-dessous pour activer la plateforme IMMO LOCAL+.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ── Saisie / modification de clé ── -->
<div class="lic-input-section anim">
    <div class="lic-input-section-title">
        <i class="fas fa-keyboard" style="color:var(--accent)"></i>
        <?= $licKey ? 'Modifier la clé de licence' : 'Activer la licence' ?>
    </div>
    <div class="lic-key-input-wrap">
        <input type="text"
               class="lic-key-input"
               id="new-license-key"
               placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX"
               value="<?= htmlspecialchars($licKey) ?>"
               autocomplete="off"
               spellcheck="false">
        <button class="lic-btn-verify" onclick="doActivate()">
            <i class="fas fa-shield-check"></i>
            <?= $licKey ? 'Vérifier & Activer' : 'Activer' ?>
        </button>
    </div>
    <div style="font-size:10px;color:var(--text-3);margin-top:8px">
        <i class="fas fa-info-circle" style="margin-right:4px;color:var(--accent)"></i>
        La clé est vérifiée en temps réel auprès des serveurs IMMO LOCAL+.
        Votre domaine <code style="font-family:var(--mono);background:var(--surface-3);padding:1px 4px;border-radius:3px"><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '') ?></code> sera associé à cette licence.
    </div>
</div>

<!-- ── Historique ── -->
<div class="lic-history-card anim">
    <div class="lic-history-hd">
        <span><i class="fas fa-history" style="color:var(--accent);margin-right:6px"></i>Historique des vérifications</span>
        <span style="font-weight:400;font-size:10px;color:var(--text-3)"><?= count($history) ?> entrée(s)</span>
    </div>
    <?php if (empty($history)): ?>
    <div style="padding:24px;text-align:center;color:var(--text-3);font-size:12px">
        <i class="fas fa-history" style="font-size:20px;margin-bottom:8px;display:block;opacity:.4"></i>
        Aucun historique — les vérifications apparaîtront ici
    </div>
    <?php else: ?>
    <?php foreach ($history as $h):
        $hStatus = strtolower(str_contains($h['status'] ?? '', 'ERROR') ? 'error' : (str_contains($h['status'] ?? '', 'OK') ? 'ok' : 'warning'));
    ?>
    <div class="lic-history-row">
        <span class="lic-h-dot <?= $hStatus ?>"></span>
        <span class="lic-h-action"><?= htmlspecialchars($h['action'] ?? '') ?></span>
        <span class="lic-h-status <?= $hStatus ?>"><?= htmlspecialchars($h['status'] ?? '') ?></span>
        <span class="lic-h-detail"><?= htmlspecialchars($h['detail'] ?? '') ?></span>
        <span class="lic-h-date"><?= htmlspecialchars($h['created_at'] ?? '') ?></span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- ── Toast ── -->
<div class="lic-toast" id="lic-toast"></div>

<!-- ── Script ── -->
<script>
const LIC_PROXY  = <?= json_encode($proxyUrl) ?>;
const LIC_KEY_DB = <?= json_encode($licKey) ?>;
let keyVisible   = false;

// ── Copier clé ────────────────────────────────────────
function copyKey() {
    navigator.clipboard.writeText(LIC_KEY_DB).then(() => licToast('Clé copiée ✓'));
}

// ── Afficher/masquer clé ──────────────────────────────
function toggleKeyVisibility() {
    keyVisible = !keyVisible;
    const el   = document.getElementById('lic-key-display');
    const ico  = document.getElementById('eye-icon');
    if (el) el.textContent = keyVisible ? LIC_KEY_DB : <?= json_encode(maskKey($licKey)) ?>;
    if (ico) ico.className = keyVisible ? 'fas fa-eye-slash' : 'fas fa-eye';
}

// ── Vérifier (clé existante) ──────────────────────────
async function doVerify() {
    const btns = document.querySelectorAll('#btn-reverify, #btn-verify-main');
    btns.forEach(b => { b.disabled = true; b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Vérification…'; });

    try {
        const fd = new FormData();
        fd.append('ajax_action', 'verify');
        const res  = await fetch(LIC_PROXY, { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            licToast('✓ Licence vérifiée avec succès');
            setTimeout(() => location.reload(), 1200);
        } else {
            licToast('⚠ ' + (data.message || 'Échec de la vérification'), true);
            btns.forEach(b => { b.disabled = false; b.innerHTML = '<i class="fas fa-sync-alt"></i> Re-vérifier la licence'; });
        }
    } catch (e) {
        licToast('❌ Erreur réseau', true);
        btns.forEach(b => { b.disabled = false; b.innerHTML = '<i class="fas fa-sync-alt"></i> Re-vérifier la licence'; });
    }
}

// ── Activer (nouvelle clé) ────────────────────────────
async function doActivate() {
    const key = document.getElementById('new-license-key')?.value.trim();
    if (!key) { licToast('Entrez une clé de licence', true); return; }

    const fd = new FormData();
    fd.append('ajax_action', 'activate');
    fd.append('license_key', key);

    try {
        const res  = await fetch(LIC_PROXY, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            licToast('✓ Licence activée !');
            setTimeout(() => location.reload(), 1200);
        } else {
            licToast('❌ ' + (data.message || 'Activation échouée'), true);
        }
    } catch (e) {
        licToast('❌ Erreur réseau', true);
    }
}

// ── Désactiver ────────────────────────────────────────
async function confirmDeactivate() {
    if (!confirm('Désactiver la licence ? Le site perdra l\'accès aux fonctionnalités premium.')) return;

    const fd = new FormData();
    fd.append('ajax_action', 'deactivate');
    try {
        const res  = await fetch(LIC_PROXY, { method: 'POST', body: fd });
        const data = await res.json();
        licToast(data.success ? 'Licence désactivée' : '❌ Erreur', !data.success);
        if (data.success) setTimeout(() => location.reload(), 1200);
    } catch (e) {
        licToast('❌ Erreur réseau', true);
    }
}

// ── Toast ─────────────────────────────────────────────
function licToast(msg, err = false) {
    const el = document.getElementById('lic-toast');
    el.textContent      = msg;
    el.style.borderColor = err ? 'var(--red)' : 'var(--border)';
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 3000);
}
</script>