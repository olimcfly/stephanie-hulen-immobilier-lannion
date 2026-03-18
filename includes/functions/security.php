<?php
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
