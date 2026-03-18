<?php
/**
 * /admin/api/system/modules-ajax.php
 * Proxy AJAX pour le gestionnaire de modules (toggle + IA Anthropic)
 * Appelé directement par fetch() depuis modules.php — PAS via dashboard.php
 */

// Toujours JSON
header('Content-Type: application/json; charset=utf-8');
ob_start();

$rootPath = '/home/mahe6420/public_html';

// Bootstrap minimal
if (!defined('DB_HOST')) {
    @require_once $rootPath . '/config/config.php';
}
if (!class_exists('Database')) {
    @require_once $rootPath . '/includes/classes/Database.php';
}

// Reprendre la session existante du dashboard
if (session_status() === PHP_SESSION_NONE) session_start();

// Lire le body JSON ou POST classique
$rawBody  = file_get_contents('php://input');
$jsonBody = json_decode($rawBody, true) ?? [];
$action   = $_POST['ajax_action'] ?? ($jsonBody['ajax_action'] ?? null);

ob_end_clean();

if (!$action) {
    echo json_encode(['error' => 'Action manquante']);
    exit;
}

// ── Toggle module on/off ───────────────────────────────
if ($action === 'toggle') {
    $slug   = preg_replace('/[^a-z0-9_-]/', '', $_POST['module'] ?? '');
    $enable = ($_POST['enable'] ?? '0') === '1';

    if (!$slug) {
        echo json_encode(['error' => 'Slug invalide']);
        exit;
    }

    $f      = $rootPath . '/config/module-states.json';
    $states = file_exists($f) ? (json_decode(file_get_contents($f), true) ?? []) : [];
    $states[$slug] = ['enabled' => $enable, 'updated_at' => date('Y-m-d H:i:s')];
    file_put_contents($f, json_encode($states, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'module' => $slug, 'enabled' => $enable]);
    exit;
}

// ── Proxy Anthropic API ────────────────────────────────
if ($action === 'ai_proxy') {
    $apiKey = '';

    // 1. Chercher en DB
    try {
        $db = Database::getInstance();
        $r  = $db->query("SELECT setting_value FROM settings WHERE setting_key='anthropic_api_key' LIMIT 1")
                 ->fetch(PDO::FETCH_ASSOC);
        if (!empty($r['setting_value'])) {
            $apiKey = trim($r['setting_value']);
        }
    } catch (Exception $e) {}

    // 2. Fallback constante PHP
    if (!$apiKey && defined('ANTHROPIC_API_KEY')) {
        $apiKey = ANTHROPIC_API_KEY;
    }

    // 3. Fallback lecture config.php
    if (!$apiKey) {
        $cfg = $rootPath . '/config/config.php';
        if (file_exists($cfg)) {
            $cfgContent = file_get_contents($cfg);
            if (preg_match("/ANTHROPIC_API_KEY['\"]?\s*[,=)]\s*['\"]([^'\"]+)['\"]/", $cfgContent, $m)) {
                $apiKey = trim($m[1]);
            }
        }
    }

    if (!$apiKey) {
        echo json_encode(['error' => 'Clé API Anthropic non configurée. Allez dans Réglages > Configuration > IA.']);
        exit;
    }

    // Construire le payload — retirer ajax_action
    unset($jsonBody['ajax_action']);
    $payload = $jsonBody;

    // Valeurs par défaut si payload vide
    if (empty($payload['model']))      $payload['model']      = 'claude-sonnet-4-20250514';
    if (empty($payload['max_tokens'])) $payload['max_tokens'] = 1000;
    if (empty($payload['messages']))   $payload['messages']   = [];

    // Appel cURL Anthropic
    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo json_encode(['error' => 'Erreur cURL : ' . $curlErr]);
        exit;
    }

    http_response_code($httpCode);
    echo $response;
    exit;
}

echo json_encode(['error' => 'Action inconnue : ' . htmlspecialchars($action)]);