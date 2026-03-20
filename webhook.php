<?php

// Charger les variables d'environnement depuis .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            putenv(trim($line));
        }
    }
}

$logFile = __DIR__ . '/deploy.log';

$secret = getenv('WEBHOOK_SECRET');

// Refuser toute requête si le secret n'est pas configuré
if (empty($secret)) {
    http_response_code(500);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERREUR : WEBHOOK_SECRET non configuré\n", FILE_APPEND);
    exit('Server misconfiguration');
}

$payload = file_get_contents("php://input");
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ECHEC : signature invalide depuis " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n", FILE_APPEND);
    exit('Invalid signature');
}

// Log de la tentative réussie
file_put_contents($logFile, date('Y-m-d H:i:s') . " - SUCCES : deploy déclenché depuis " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n", FILE_APPEND);

// Lancer le script deploy
exec('bash /home/cool1933/deploy.sh');

echo "DEPLOY OK";
