<?php
function redirect($url) { header('Location: ' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8')); exit; }
function writeLog($message, $level = 'INFO') { $timestamp = date('Y-m-d H:i:s'); $log_file = LOGS_PATH . '/app.log'; $log_entry = "[$timestamp] [$level] $message\n"; @file_put_contents($log_file, $log_entry, FILE_APPEND); }
function slugify($text) { $text = mb_strtolower($text, 'UTF-8'); $text = preg_replace('/[^a-z0-9]+/', '-', $text); return trim($text, '-'); }
function formatDate($date, $format = 'FR') { $dt = new DateTime($date); return match($format) { 'FR' => $dt->format('d/m/Y'), 'TIME' => $dt->format('d/m/Y H:i'), default => $dt->format('Y-m-d') }; }
function formatPrice($price) { return number_format($price, 0, ',', ' ') . ' €'; }
function is_admin_logged_in() { return isset($_SESSION['admin_id']) && isset($_SESSION['admin_email']); }
function require_admin_login() { if (!is_admin_logged_in()) redirect('/admin/login.php'); }
function get_admin_email() { return $_SESSION['admin_email'] ?? ''; }
function csrf_token() { if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }

/**
 * Enregistre une action admin dans la table audit_logs.
 * @param string $action      Ex: 'login', 'create', 'update', 'delete', 'send_email'
 * @param string|null $entityType  Ex: 'page', 'bien', 'lead', 'setting', 'email'
 * @param int|null $entityId       ID de l'entité concernée
 * @param array $details           Détails supplémentaires (sera stocké en JSON)
 */
function auditLog(string $action, ?string $entityType = null, ?int $entityId = null, array $details = []): void {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO audit_logs (admin_id, admin_email, action, entity_type, entity_id, details_json, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            $_SESSION['admin_id'] ?? null,
            $_SESSION['admin_email'] ?? null,
            $action,
            $entityType,
            $entityId,
            !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    } catch (Throwable $e) {
        writeLog("auditLog error: " . $e->getMessage(), 'ERROR');
    }
}
