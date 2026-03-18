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
