<?php
/**
 * 🔧 CONFIG INSTANCE - EXEMPLE
 * /config/config.php
 *
 * Copier ce fichier en config/config.php et remplir les valeurs
 * À MODIFIER pour chaque duplication
 */

// ═══════════════════════════════════════════════════════════
// 📌 INSTANCE-SPECIFIC (À CHANGER POUR CHAQUE DUPLICATION)
// ═══════════════════════════════════════════════════════════

define('INSTANCE_ID', 'mon-instance');                  // Identifiant unique
define('SITE_TITLE', 'Mon Site Immobilier');
define('SITE_DOMAIN', 'mon-domaine.fr');
define('ADMIN_EMAIL', 'admin@mon-domaine.fr');

define('DB_HOST', 'localhost');
define('DB_NAME', 'ma_base_de_donnees');
define('DB_USER', 'mon_utilisateur_db');
define('DB_PASS', 'mon_mot_de_passe_db');

// ═══════════════════════════════════════════════════════════
// 🤖 CONFIGURATION IA (SEO, Génération contenu)
// ═══════════════════════════════════════════════════════════

// OpenAI API Key (GPT-4)
define('OPENAI_API_KEY', 'sk-proj-VOTRE_CLE_OPENAI');

// Claude (Anthropic) API Key - Utilisé en priorité
define('ANTHROPIC_API_KEY', 'sk-ant-VOTRE_CLE_ANTHROPIC');

// ═══════════════════════════════════════════════════════════
// 🔧 CHEMINS (Automatique - Ne pas modifier)
// ═══════════════════════════════════════════════════════════

define('ROOT_PATH', dirname(dirname(__FILE__)));
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('API_PATH', ROOT_PATH . '/api');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// ═══════════════════════════════════════════════════════════
// 🌐 URLS (Auto-détection du domaine + fallback)
// ═══════════════════════════════════════════════════════════

$detected_domain = $_SERVER['HTTP_HOST'] ?? SITE_DOMAIN;
$detected_domain = str_replace('www.', '', $detected_domain);

define('SITE_URL', 'https://' . $detected_domain);
define('ADMIN_URL', SITE_URL . '/admin');
define('API_URL', SITE_URL . '/api');
define('ASSETS_URL', SITE_URL . '/assets');
define('UPLOADS_URL', SITE_URL . '/uploads');

// ═══════════════════════════════════════════════════════════
// 🗄️ BASE DE DONNÉES
// ═══════════════════════════════════════════════════════════

define('DB_CHARSET', 'utf8mb4');
define('DB_TIMEZONE', 'Europe/Paris');

// ═══════════════════════════════════════════════════════════
// 🔒 SÉCURITÉ
// ═══════════════════════════════════════════════════════════

define('SESSION_TIMEOUT', 3600);
define('SESSION_NAME', 'ECOSYSTEM_' . strtoupper(INSTANCE_ID));
define('CSRF_TOKEN_NAME', '_csrf_token');

// ═══════════════════════════════════════════════════════════
// 📊 FEATURES
// ═══════════════════════════════════════════════════════════

define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('ENABLE_SMS', false);
define('ENABLE_ANALYTICS', true);
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB

// ═══════════════════════════════════════════════════════════
// 📝 LOGS & ERRORS
// ═══════════════════════════════════════════════════════════

define('LOGS_PATH', ROOT_PATH . '/logs');
define('DEBUG_MODE', false);

error_reporting(E_ALL);
ini_set('display_errors', DEBUG_MODE ? 1 : 0);
ini_set('log_errors', 1);
ini_set('error_log', LOGS_PATH . '/php_errors.log');

// ═══════════════════════════════════════════════════════════
// 🔌 SESSION
// ═══════════════════════════════════════════════════════════

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// ═══════════════════════════════════════════════════════════
// 🗂️ FONCTIONS GLOBALES
// ═══════════════════════════════════════════════════════════

function sanitize($input, $type = 'string') {
    if ($type === 'email') {
        return filter_var($input, FILTER_SANITIZE_EMAIL);
    }
    if ($type === 'int') {
        return (int) $input;
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isAdminLoggedIn() {
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_email']);
}

function getAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

function getAdminEmail() {
    return $_SESSION['admin_email'] ?? null;
}

function writeLog($message, $level = 'INFO') {
    $log_file = LOGS_PATH . '/app.log';
    @mkdir(dirname($log_file), 0755, true);

    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $log_message = "[$timestamp] [$level] [$ip] $message\n";

    @file_put_contents($log_file, $log_message, FILE_APPEND);
}

function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            writeLog("DB Connection Error: " . $e->getMessage(), 'ERROR');
            die('❌ Erreur de connexion base de données');
        }
    }

    return $pdo;
}

function getSiteEmail() {
    if (isAdminLoggedIn()) {
        return getAdminEmail();
    }
    return ADMIN_EMAIL;
}

function getSiteUrl() {
    return SITE_URL;
}

function getSiteDomain() {
    return str_replace('https://', '', SITE_URL);
}
