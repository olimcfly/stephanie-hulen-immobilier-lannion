<?php

// ── CSRF Protection ─────────────────────────────────────────

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField(): string {
    $token = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Extract CSRF token from request (POST body, JSON body, or X-CSRF-Token header).
 */
function getCsrfTokenFromRequest(): string {
    // 1. POST field
    if (!empty($_POST['csrf_token'])) {
        return $_POST['csrf_token'];
    }
    // 2. X-CSRF-Token header (AJAX)
    if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        return $_SERVER['HTTP_X_CSRF_TOKEN'];
    }
    // 3. JSON body
    $raw = file_get_contents('php://input');
    if ($raw) {
        $json = json_decode($raw, true);
        if (is_array($json) && !empty($json['csrf_token'])) {
            return $json['csrf_token'];
        }
    }
    return '';
}

// ── Sanitization ────────────────────────────────────────────

function sanitize($data, $type = 'string') {
    if (is_array($data)) return array_map(fn($v) => sanitize($v, $type), $data);
    return match($type) {
        'email' => filter_var($data, FILTER_SANITIZE_EMAIL),
        'url' => filter_var($data, FILTER_SANITIZE_URL),
        'int' => intval($data),
        'html' => htmlspecialchars($data, ENT_QUOTES, 'UTF-8'),
        default => htmlspecialchars($data, ENT_QUOTES, 'UTF-8'),
    };
}
function isValidEmail($email) { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
function isRateLimited($key, $limit = 5, $window = 60) {
    $cache_key = "rate_limit_$key";
    if (!isset($_SESSION[$cache_key])) $_SESSION[$cache_key] = [];
    $now = time();
    $_SESSION[$cache_key] = array_filter($_SESSION[$cache_key], fn($t) => $now - $t < $window);
    if (count($_SESSION[$cache_key]) >= $limit) return true;
    $_SESSION[$cache_key][] = $now;
    return false;
}
