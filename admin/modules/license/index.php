<?php
/**
 * ══════════════════════════════════════════════════════════════════════
 *  MODULE LICENCE — Écosystème Immo Local+
 *  /admin/modules/license/index.php
 *  
 *  Gère l'activation, la vérification et le suivi de la licence.
 *  Communique avec l'API du portail install.ecosystemeimmo.fr
 * ══════════════════════════════════════════════════════════════════════
 */

if (!defined('ADMIN_ROUTER')) { header('Location: /admin/dashboard.php?page=license'); exit; }

// ============================================
// CONFIG
// ============================================
$LICENSE_API_URL = 'https://install.ecosystemeimmo.fr/api/verify-license.php';
$licenseFile     = __DIR__ . '/../../../config/license.json';
$licenseLogFile  = __DIR__ . '/../../../logs/license.log';

// ============================================
// HELPERS
// ============================================
function licenseLoad(string $path): ?array {
    if (!file_exists($path)) return null;
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) && !empty($data['license_key']) ? $data : null;
}

function licenseSave(string $path, array $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return (bool)file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function licenseLog(string $path, string $action, string $result, ?string $detail = null): void {
    $dir = dirname($path);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $line = date('Y-m-d H:i:s') . " | $action | $result" . ($detail ? " | $detail" : '') . "\n";
    @file_put_contents($path, $line, FILE_APPEND);
}

function licenseCallAPI(string $url, string $key, string $domain): array {
    $payload = json_encode(['license_key' => $key, 'domain' => $domain]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    $errno    = curl_errno($ch);
    curl_close($ch);
    
    if ($errno !== 0) {
        return ['success' => false, 'error' => 'connection_failed', 'message' => $error, 'http_code' => 0];
    }
    
    $data = json_decode($response, true);
    if (!is_array($data)) {
        return ['success' => false, 'error' => 'invalid_response', 'message' => 'Réponse invalide du serveur', 'http_code' => $httpCode];
    }
    
    $data['http_code'] = $httpCode;
    $data['success'] = true;
    return $data;
}

function licenseMaskKey(string $key): string {
    $len = strlen($key);
    if ($len <= 12) return $key;
    return substr($key, 0, 8) . str_repeat('•', min(16, $len - 16)) . substr($key, -8);
}

function licenseTimeAgo(string $datetime): string {
    $ts = strtotime($datetime);
    if ($ts === false) return $datetime;
    $diff = time() - $ts;
    if ($diff < 60) return "à l'instant";
    if ($diff < 3600) return (int)($diff/60) . ' min';
    if ($diff < 86400) return (int)($diff/3600) . ' h';
    if ($diff < 604800) return (int)($diff/86400) . ' jour(s)';
    return date('d/m/Y H:i', $ts);
}

// ============================================
// STATE
// ============================================
$licenseData = licenseLoad($licenseFile);
$flash = null;
$flashType = null;
$currentDomain = $_SERVER['HTTP_HOST'] ?? '';

// ============================================
// ACTIONS POST
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
        $flash = "Erreur de sécurité (CSRF). Rechargez la page.";
        $flashType = 'error';
    } else {
        $postAction = $_POST['license_action'] ?? '';
        
        // ── ACTIVATE / VERIFY ──
        if ($postAction === 'activate' || $postAction === 'verify') {
            $inputKey = trim($_POST['license_key'] ?? ($licenseData['license_key'] ?? ''));
            
            if ($inputKey === '') {
                $flash = "Veuillez entrer une clé de licence.";
                $flashType = 'error';
            } else {
                $result = licenseCallAPI($LICENSE_API_URL, $inputKey, $currentDomain);
                
                if (!$result['success']) {
                    $flash = "Impossible de contacter le serveur de licences. " . ($result['message'] ?? '');
                    $flashType = 'error';
                    licenseLog($licenseLogFile, $postAction, 'CONNECTION_ERROR', $result['message'] ?? '');
                } elseif ($result['valid'] === true) {
                    // Success — save license
                    $licenseData = [
                        'license_key' => $inputKey,
                        'status'      => $result['status'] ?? 'active',
                        'plan'        => $result['plan'] ?? 'beta',
                        'client'      => $result['client'] ?? null,
                        'email'       => $result['email'] ?? null,
                        'domain'      => $result['domain'] ?? $currentDomain,
                        'expires_at'  => $result['expires_at'] ?? null,
                        'created_at'  => $result['created_at'] ?? null,
                        'verified_at' => date('Y-m-d H:i:s'),
                        'verify_count'=> (int)($licenseData['verify_count'] ?? 0) + 1,
                        'first_activated_at' => $licenseData['first_activated_at'] ?? date('Y-m-d H:i:s'),
                    ];
                    licenseSave($licenseFile, $licenseData);
                    
                    $flash = $postAction === 'activate' 
                        ? "Licence activée avec succès ! Plan : " . strtoupper($result['plan'] ?? 'beta')
                        : "Licence vérifiée — tout est en ordre.";
                    $flashType = 'success';
                    licenseLog($licenseLogFile, $postAction, 'OK', 'Plan=' . ($result['plan'] ?? '?') . ' Status=' . ($result['status'] ?? '?'));
                } else {
                    // API returned valid=false
                    $errCode = $result['error'] ?? 'unknown';
                    $errMessages = [
                        'license_not_found' => "Cette clé de licence est introuvable. Vérifiez que vous l'avez correctement copiée.",
                        'license_suspended' => "Cette licence est suspendue. Contactez votre administrateur Écosystème Immo.",
                        'license_expired'   => "Cette licence a expiré. Renouvelez-la depuis le portail.",
                        'domain_mismatch'   => "Cette licence est associée au domaine « " . ($result['expected'] ?? '?') . " » et non à « $currentDomain ».",
                        'missing_parameters'=> "Paramètres manquants dans la requête.",
                    ];
                    $flash = $errMessages[$errCode] ?? "Erreur de vérification ($errCode).";
                    $flashType = 'error';
                    licenseLog($licenseLogFile, $postAction, 'FAIL', $errCode);
                    
                    // If the license was previously saved and is now invalid, update status
                    if ($licenseData && $errCode !== 'license_not_found') {
                        $licenseData['status'] = $result['status'] ?? $errCode;
                        $licenseData['verified_at'] = date('Y-m-d H:i:s');
                        licenseSave($licenseFile, $licenseData);
                    }
                }
            }
        }
        
        // ── DEACTIVATE ──
        if ($postAction === 'deactivate') {
            if (file_exists($licenseFile)) {
                $oldKey = $licenseData['license_key'] ?? '?';
                @unlink($licenseFile);
                licenseLog($licenseLogFile, 'deactivate', 'OK', 'Removed key=' . substr($oldKey, 0, 8) . '...');
            }
            $licenseData = null;
            $flash = "Licence désactivée et supprimée localement.";
            $flashType = 'success';
        }
    }
}

// Derived state
$hasLicense    = ($licenseData !== null);
$isActive      = $hasLicense && in_array($licenseData['status'] ?? '', ['active', 'beta']);
$isSuspended   = $hasLicense && ($licenseData['status'] ?? '') === 'suspended';
$isExpired     = $hasLicense && ($licenseData['status'] ?? '') === 'expired';
$needsCheck    = $hasLicense && !empty($licenseData['verified_at']) && (time() - strtotime($licenseData['verified_at']) > 86400);

// Load recent log entries
$recentLogs = [];
if (file_exists($licenseLogFile)) {
    $allLines = file($licenseLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($allLines) {
        $recentLogs = array_slice(array_reverse($allLines), 0, 10);
    }
}

$csrfToken = $_SESSION['csrf_token'] ?? '';
?>

<style>
/* ── License Module Styles ── */
.lic-wrap { max-width: 840px; }

.lic-flash {
    padding: 14px 18px; border-radius: var(--radius-lg, 14px); margin-bottom: 20px;
    font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 10px;
    animation: slideUp .3s ease both;
}
.lic-flash.success { background: #dcfce7; border: 1px solid #bbf7d0; color: #166534; }
.lic-flash.error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.lic-flash i { font-size: 16px; flex-shrink: 0; }

/* Status hero */
.lic-status-hero {
    background: var(--surface, #fff); border: 1px solid var(--border, rgba(0,0,0,.06));
    border-radius: 18px; padding: 36px; margin-bottom: 24px; position: relative; overflow: hidden;
}
.lic-status-hero::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
    border-radius: 4px 4px 0 0;
}
.lic-status-hero.active::before { background: linear-gradient(90deg, #059669, #34d399); }
.lic-status-hero.inactive::before { background: linear-gradient(90deg, #d97706, #fbbf24); }
.lic-status-hero.suspended::before { background: linear-gradient(90deg, #dc2626, #f87171); }
.lic-status-hero.expired::before { background: linear-gradient(90deg, #6b7280, #9ca3af); }

.lic-status-row {
    display: flex; align-items: center; gap: 16px; margin-bottom: 24px;
}
.lic-status-indicator {
    width: 52px; height: 52px; border-radius: 14px; display: flex;
    align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;
}
.active .lic-status-indicator { background: rgba(5,150,105,.08); color: #059669; }
.inactive .lic-status-indicator { background: rgba(217,119,6,.08); color: #d97706; }
.suspended .lic-status-indicator { background: rgba(220,38,38,.08); color: #dc2626; }
.expired .lic-status-indicator { background: rgba(107,114,128,.08); color: #6b7280; }

.lic-status-text h3 {
    font-family: var(--font-display, 'Space Grotesk', sans-serif);
    font-size: 20px; font-weight: 700; letter-spacing: -.02em; margin-bottom: 4px;
}
.lic-status-text p { font-size: 13px; color: var(--text-2, #57534e); line-height: 1.5; }

/* Key display */
.lic-key-box {
    display: flex; align-items: center; gap: 12px;
    background: var(--surface-2, #f3f2ef); border: 1px solid var(--border, rgba(0,0,0,.06));
    border-radius: 12px; padding: 14px 18px; margin-bottom: 24px;
}
.lic-key-icon { font-size: 18px; flex-shrink: 0; opacity: .5; }
.lic-key-value {
    flex: 1; font-family: var(--mono, monospace); font-size: 13px; font-weight: 600;
    letter-spacing: .02em; word-break: break-all; color: var(--text, #1c1917);
}
.lic-key-copy {
    padding: 6px 12px; border-radius: 8px; border: 1px solid var(--border, rgba(0,0,0,.06));
    background: var(--surface, #fff); font-size: 11px; font-weight: 600; cursor: pointer;
    color: var(--text-2, #57534e); transition: all .15s; font-family: inherit;
}
.lic-key-copy:hover { background: var(--accent-bg, rgba(79,70,229,.05)); color: var(--accent, #4f46e5); }

/* Info grid */
.lic-grid {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 24px;
}
.lic-info-card {
    padding: 16px 18px; background: var(--surface-2, #f3f2ef);
    border-radius: 12px; border: 1px solid var(--border, rgba(0,0,0,.06));
}
.lic-info-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: var(--text-3, #a8a29e); margin-bottom: 4px;
}
.lic-info-value {
    font-size: 14px; font-weight: 700; color: var(--text, #1c1917);
}
.lic-info-value.mono { font-family: var(--mono, monospace); font-size: 12px; }

/* Actions bar */
.lic-actions {
    display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
    padding-top: 20px; border-top: 1px solid var(--border, rgba(0,0,0,.06));
}
.lic-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 20px; border-radius: 10px; border: none;
    font-family: inherit; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: all .2s; text-decoration: none;
}
.lic-btn-primary { background: var(--accent, #4f46e5); color: #fff; box-shadow: 0 2px 8px rgba(79,70,229,.2); }
.lic-btn-primary:hover { background: #4338ca; transform: translateY(-1px); }
.lic-btn-secondary { background: var(--surface-2, #f3f2ef); color: var(--text, #1c1917); border: 1px solid var(--border, rgba(0,0,0,.06)); }
.lic-btn-secondary:hover { background: var(--surface-3, #eae8e4); }
.lic-btn-danger { background: rgba(220,38,38,.06); color: #dc2626; border: 1px solid rgba(220,38,38,.12); }
.lic-btn-danger:hover { background: rgba(220,38,38,.1); }
.lic-btn-sm { padding: 7px 14px; font-size: 12px; }

/* Activation form (when no license) */
.lic-activate-card {
    background: var(--surface, #fff); border: 1px solid var(--border, rgba(0,0,0,.06));
    border-radius: 18px; padding: 40px; text-align: center; margin-bottom: 24px;
}
.lic-activate-icon {
    width: 72px; height: 72px; margin: 0 auto 20px; border-radius: 18px;
    background: var(--accent-bg, rgba(79,70,229,.05)); display: flex;
    align-items: center; justify-content: center; font-size: 32px; color: var(--accent, #4f46e5);
}
.lic-activate-card h3 {
    font-family: var(--font-display, 'Space Grotesk', sans-serif);
    font-size: 22px; font-weight: 700; letter-spacing: -.02em; margin-bottom: 8px;
}
.lic-activate-card > p { font-size: 14px; color: var(--text-2, #57534e); margin-bottom: 28px; line-height: 1.6; }
.lic-activate-form { max-width: 520px; margin: 0 auto; }
.lic-input-group {
    display: flex; gap: 10px; margin-bottom: 16px;
}
.lic-input {
    flex: 1; padding: 13px 18px; border: 1px solid var(--border-h, rgba(0,0,0,.1));
    border-radius: 12px; font-family: var(--mono, monospace); font-size: 14px;
    outline: none; transition: border-color .2s, box-shadow .2s;
    background: var(--surface-2, #f3f2ef); color: var(--text, #1c1917);
}
.lic-input:focus { border-color: var(--accent, #4f46e5); box-shadow: 0 0 0 3px rgba(79,70,229,.1); background: var(--surface, #fff); }
.lic-input::placeholder { font-family: var(--font, sans-serif); color: var(--text-3, #a8a29e); }
.lic-help { font-size: 12px; color: var(--text-3, #a8a29e); line-height: 1.5; }
.lic-help i { margin-right: 4px; }

/* Features grid */
.lic-features {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
    margin-top: 28px; text-align: left; max-width: 520px; margin-left: auto; margin-right: auto;
}
.lic-feature {
    display: flex; align-items: start; gap: 10px; padding: 12px;
    background: var(--surface-2, #f3f2ef); border-radius: 10px; font-size: 12px;
}
.lic-feature-icon { color: var(--accent, #4f46e5); font-size: 14px; margin-top: 1px; flex-shrink: 0; }
.lic-feature strong { display: block; font-weight: 700; margin-bottom: 2px; }
.lic-feature span { color: var(--text-3, #a8a29e); font-size: 11px; }

/* Log section */
.lic-log-card {
    background: var(--surface, #fff); border: 1px solid var(--border, rgba(0,0,0,.06));
    border-radius: 14px; overflow: hidden;
}
.lic-log-hd {
    padding: 14px 18px; border-bottom: 1px solid var(--border, rgba(0,0,0,.06));
    font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px;
}
.lic-log-body { padding: 12px 18px; max-height: 220px; overflow-y: auto; }
.lic-log-entry {
    font-family: var(--mono, monospace); font-size: 11px; line-height: 1.8;
    color: var(--text-2, #57534e); border-bottom: 1px solid var(--border, rgba(0,0,0,.03));
    padding: 4px 0;
}
.lic-log-entry:last-child { border-bottom: none; }
.lic-log-ok { color: #059669; }
.lic-log-fail { color: #dc2626; }
.lic-log-empty {
    text-align: center; padding: 24px; color: var(--text-3, #a8a29e); font-size: 12px;
}

/* Alert bar */
.lic-alert {
    display: flex; align-items: center; gap: 12px;
    padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 13px;
}
.lic-alert.warn { background: rgba(217,119,6,.06); border: 1px solid rgba(217,119,6,.12); color: #92400e; }
.lic-alert.info { background: rgba(37,99,235,.05); border: 1px solid rgba(37,99,235,.1); color: #1d4ed8; }
.lic-alert i { font-size: 16px; flex-shrink: 0; }
.lic-alert-text { flex: 1; }
.lic-alert-text strong { display: block; margin-bottom: 2px; }

/* Domain badge */
.lic-domain-badge {
    display: inline-flex; align-items: center; gap: 6px;
    font-family: var(--mono, monospace); font-size: 11px; font-weight: 600;
    padding: 4px 10px; border-radius: 6px;
    background: var(--surface-2, #f3f2ef); color: var(--text-2, #57534e);
}
.lic-domain-badge::before {
    content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: .4;
}

@media(max-width:640px) {
    .lic-grid { grid-template-columns: 1fr; }
    .lic-input-group { flex-direction: column; }
    .lic-features { grid-template-columns: 1fr; }
    .lic-actions { flex-direction: column; }
    .lic-actions .lic-btn { width: 100%; justify-content: center; }
}
</style>

<div class="lic-wrap">

    <!-- Flash message -->
    <?php if ($flash): ?>
    <div class="lic-flash <?= $flashType ?>">
        <i class="fas fa-<?= $flashType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
        <?= htmlspecialchars($flash) ?>
    </div>
    <?php endif; ?>

    <?php if ($hasLicense): ?>
    <!-- ══════════════════════════════════════
         LICENCE ACTIVÉE
         ══════════════════════════════════════ -->

    <!-- Auto-check alert -->
    <?php if ($needsCheck): ?>
    <div class="lic-alert warn">
        <i class="fas fa-clock"></i>
        <div class="lic-alert-text">
            <strong>Vérification recommandée</strong>
            Dernière vérification il y a plus de 24h. Cliquez sur « Re-vérifier » pour confirmer le statut.
        </div>
        <form method="post" style="flex-shrink:0">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="license_action" value="verify">
            <input type="hidden" name="license_key" value="<?= htmlspecialchars($licenseData['license_key']) ?>">
            <button type="submit" class="lic-btn lic-btn-secondary lic-btn-sm"><i class="fas fa-sync-alt"></i> Vérifier</button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Status hero -->
    <div class="lic-status-hero <?= $isActive ? 'active' : ($isSuspended ? 'suspended' : ($isExpired ? 'expired' : 'inactive')) ?>">
        <div class="lic-status-row">
            <div class="lic-status-indicator">
                <?php if ($isActive): ?><i class="fas fa-shield-alt"></i>
                <?php elseif ($isSuspended): ?><i class="fas fa-pause-circle"></i>
                <?php elseif ($isExpired): ?><i class="fas fa-hourglass-end"></i>
                <?php else: ?><i class="fas fa-question-circle"></i><?php endif; ?>
            </div>
            <div class="lic-status-text">
                <h3>
                    <?php if ($isActive): ?>Licence active
                    <?php elseif ($isSuspended): ?>Licence suspendue
                    <?php elseif ($isExpired): ?>Licence expirée
                    <?php else: ?>Licence — statut: <?= htmlspecialchars($licenseData['status'] ?? 'inconnu') ?>
                    <?php endif; ?>
                </h3>
                <p>
                    <?php if ($isActive): ?>
                        Votre plateforme Écosystème Immo est pleinement opérationnelle.
                        <?php if (!empty($licenseData['client'])): ?>Licence attribuée à <strong><?= htmlspecialchars($licenseData['client']) ?></strong>.<?php endif; ?>
                    <?php elseif ($isSuspended): ?>
                        Votre licence a été suspendue. Contactez votre administrateur Écosystème Immo pour la réactiver.
                    <?php elseif ($isExpired): ?>
                        Votre licence a expiré. Renouvelez-la depuis le portail pour continuer à utiliser toutes les fonctionnalités.
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Key display -->
        <div class="lic-key-box">
            <div class="lic-key-icon">🔑</div>
            <div class="lic-key-value" id="licKeyText"><?= htmlspecialchars(licenseMaskKey($licenseData['license_key'])) ?></div>
            <button class="lic-key-copy" onclick="copyLicenseKey()" id="licCopyBtn" data-key="<?= htmlspecialchars($licenseData['license_key']) ?>">
                <i class="fas fa-copy"></i> Copier
            </button>
        </div>

        <!-- Info grid -->
        <div class="lic-grid">
            <div class="lic-info-card">
                <div class="lic-info-label">Plan</div>
                <div class="lic-info-value"><?= strtoupper(htmlspecialchars($licenseData['plan'] ?? '—')) ?></div>
            </div>
            <div class="lic-info-card">
                <div class="lic-info-label">Statut</div>
                <div class="lic-info-value" style="color:<?= $isActive?'#059669':($isSuspended?'#dc2626':'#6b7280') ?>">
                    <?= strtoupper(htmlspecialchars($licenseData['status'] ?? '—')) ?>
                </div>
            </div>
            <div class="lic-info-card">
                <div class="lic-info-label">Expiration</div>
                <div class="lic-info-value"><?= $licenseData['expires_at'] ? htmlspecialchars($licenseData['expires_at']) : '∞ Illimitée' ?></div>
            </div>
            <div class="lic-info-card">
                <div class="lic-info-label">Domaine associé</div>
                <div class="lic-info-value mono">
                    <span class="lic-domain-badge"><?= htmlspecialchars($licenseData['domain'] ?? $currentDomain) ?></span>
                </div>
            </div>
            <div class="lic-info-card">
                <div class="lic-info-label">Dernière vérification</div>
                <div class="lic-info-value"><?= !empty($licenseData['verified_at']) ? licenseTimeAgo($licenseData['verified_at']) : '—' ?></div>
            </div>
            <div class="lic-info-card">
                <div class="lic-info-label">Activée le</div>
                <div class="lic-info-value"><?= !empty($licenseData['first_activated_at']) ? date('d/m/Y', strtotime($licenseData['first_activated_at'])) : '—' ?></div>
            </div>
        </div>

        <!-- Actions -->
        <div class="lic-actions">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="license_action" value="verify">
                <input type="hidden" name="license_key" value="<?= htmlspecialchars($licenseData['license_key']) ?>">
                <button type="submit" class="lic-btn lic-btn-primary"><i class="fas fa-sync-alt"></i> Re-vérifier la licence</button>
            </form>
            <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir désactiver cette licence ?\n\nLa clé sera supprimée de ce site. Vous pourrez la réactiver plus tard.')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="license_action" value="deactivate">
                <button type="submit" class="lic-btn lic-btn-danger"><i class="fas fa-unlink"></i> Désactiver</button>
            </form>
        </div>
    </div>

    <!-- Activity log -->
    <?php if (!empty($recentLogs)): ?>
    <div class="lic-log-card">
        <div class="lic-log-hd"><i class="fas fa-list" style="color:var(--text-3,#a8a29e);font-size:12px"></i> Historique des vérifications</div>
        <div class="lic-log-body">
            <?php foreach ($recentLogs as $logLine):
                $isOk = str_contains($logLine, '| OK');
                $isFail = str_contains($logLine, '| FAIL') || str_contains($logLine, '| CONNECTION_ERROR');
            ?>
            <div class="lic-log-entry <?= $isOk ? 'lic-log-ok' : ($isFail ? 'lic-log-fail' : '') ?>">
                <?= htmlspecialchars($logLine) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- ══════════════════════════════════════
         PAS DE LICENCE
         ══════════════════════════════════════ -->

    <div class="lic-activate-card">
        <div class="lic-activate-icon"><i class="fas fa-key"></i></div>
        <h3>Activez votre licence</h3>
        <p>Collez votre clé de licence pour activer votre plateforme Écosystème Immo.<br>
        Cette clé vous a été fournie lors de la mise en place de votre instance.</p>

        <div class="lic-activate-form">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="license_action" value="activate">
                <div class="lic-input-group">
                    <input type="text" name="license_key" class="lic-input"
                           placeholder="Collez votre clé de licence ici…"
                           autocomplete="off" spellcheck="false" required>
                    <button type="submit" class="lic-btn lic-btn-primary">
                        <i class="fas fa-check"></i> Activer
                    </button>
                </div>
            </form>
            <div class="lic-help">
                <i class="fas fa-info-circle"></i>
                Domaine actuel : <strong><?= htmlspecialchars($currentDomain) ?></strong> — 
                La licence sera vérifiée avec ce domaine.
            </div>
        </div>

        <div class="lic-features">
            <div class="lic-feature">
                <i class="fas fa-check-circle lic-feature-icon"></i>
                <div><strong>Vérification instantanée</strong><span>Validation en temps réel via le portail</span></div>
            </div>
            <div class="lic-feature">
                <i class="fas fa-shield-alt lic-feature-icon"></i>
                <div><strong>Sécurisé</strong><span>Vérifie le domaine et la clé</span></div>
            </div>
            <div class="lic-feature">
                <i class="fas fa-sync-alt lic-feature-icon"></i>
                <div><strong>Auto-vérification</strong><span>Statut mis à jour quotidiennement</span></div>
            </div>
            <div class="lic-feature">
                <i class="fas fa-unlock lic-feature-icon"></i>
                <div><strong>Toutes fonctionnalités</strong><span>Débloquez l'accès complet au CRM</span></div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<script>
function copyLicenseKey() {
    const btn = document.getElementById('licCopyBtn');
    const key = btn.getAttribute('data-key');
    if (!key) return;
    
    navigator.clipboard.writeText(key).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copié !';
        btn.style.color = '#059669';
        setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; }, 2000);
    }).catch(() => {
        // Fallback
        const ta = document.createElement('textarea');
        ta.value = key; ta.style.position = 'fixed'; ta.style.left = '-999px';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); btn.innerHTML = '<i class="fas fa-check"></i> Copié !'; }
        catch(e) {}
        document.body.removeChild(ta);
    });
}
</script>