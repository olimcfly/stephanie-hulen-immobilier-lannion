<?php

$secret = "CHANGE_MOI_SECRET";

$payload = file_get_contents("php://input");
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

$expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    exit('Invalid signature');
}

// log simple
file_put_contents(__DIR__.'/deploy.log', date('Y-m-d H:i:s')." - Deploy OK\n", 
FILE_APPEND);

// lancer le script deploy
exec('bash /home/cool1933/deploy.sh');

echo "DEPLOY OK";
