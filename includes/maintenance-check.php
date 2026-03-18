<?php
/**
 * MAINTENANCE MIDDLEWARE
 * Version SaaS propre
 */

$GLOBALS['maintenance_banner'] = false;
$GLOBALS['maintenance_message'] = '';
$GLOBALS['maintenance_end'] = '';

$uri = $_SERVER['REQUEST_URI'] ?? '';

/* ========================================
   1. EXCLUSIONS
======================================== */

// admin toujours accessible
if (strpos($uri, '/admin') !== false) {
    return;
}

// assets
$ext = strtolower(pathinfo(strtok($uri,'?'), PATHINFO_EXTENSION));

if (in_array($ext, [
'css','js','png','jpg','jpeg','gif','svg','ico',
'woff','woff2','ttf','eot','webp','map','json','xml'
], true)) {
    return;
}

/* ========================================
   2. SESSION ADMIN
======================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isAdmin = !empty($_SESSION['admin_id']);

/* ========================================
   3. IP VISITEUR
======================================== */

$visitorIp =
$_SERVER['HTTP_CF_CONNECTING_IP']
?? $_SERVER['HTTP_X_FORWARDED_FOR']
?? $_SERVER['REMOTE_ADDR']
?? '';

if (strpos($visitorIp, ',') !== false) {
    $visitorIp = trim(explode(',', $visitorIp)[0]);
}

/* ========================================
   4. DB CONNECTION
======================================== */

try {

$pdo = new PDO(
'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
DB_USER,
DB_PASS,
[
PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]
);

$stmt = $pdo->query("
SELECT is_active,message,allowed_ips,end_date
FROM maintenance
WHERE id=1
LIMIT 1
");

$data = $stmt->fetch();

if (!$data || (int)$data['is_active'] !== 1) {
    return;
}

/* ========================================
   5. WHITELIST IP
======================================== */

$allowed = ['127.0.0.1','::1'];

if (!empty($data['allowed_ips'])) {
    $extra = array_map('trim', explode(',', $data['allowed_ips']));
    $allowed = array_merge($allowed,$extra);
}

if (in_array($visitorIp,$allowed,true)) {

    $GLOBALS['maintenance_banner'] = true;
    $GLOBALS['maintenance_message'] = $data['message'];

    if (!empty($data['end_date'])) {
        $end = new DateTime($data['end_date']);
        $GLOBALS['maintenance_end'] = $end->format('d/m/Y H:i');
    }

    return;
}

/* ========================================
   6. ADMIN CONNECTÉ
======================================== */

if ($isAdmin) {

    $GLOBALS['maintenance_banner'] = true;
    $GLOBALS['maintenance_message'] = $data['message'];

    return;
}

/* ========================================
   7. BLOCK VISITOR
======================================== */

$message = $data['message']
?: "Nous effectuons actuellement une intervention technique.";

/* HEADERS SEO SAFE */

http_response_code(503);

header("Content-Type: text/html; charset=utf-8");
header("Retry-After: 3600");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");
header("X-Robots-Tag: noindex, nofollow");

?>
<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

<title>Maintenance</title>

<style>

body{
margin:0;
height:100vh;
display:flex;
align-items:center;
justify-content:center;
font-family:system-ui;
background:#0f172a;
color:white;
text-align:center;
}

.box{
max-width:500px;
padding:40px;
}

h1{
font-size:32px;
margin-bottom:10px;
}

p{
opacity:.8;
line-height:1.6;
}

.bar{
height:3px;
width:200px;
background:#1e293b;
margin:30px auto;
overflow:hidden;
}

.bar span{
display:block;
height:100%;
width:40%;
background:#3b82f6;
animation:load 2s infinite;
}

@keyframes load{
0%{transform:translateX(-100%)}
50%{transform:translateX(200%)}
100%{transform:translateX(500%)}
}

</style>

</head>

<body>

<div class="box">

<h1>Site en maintenance</h1>

<p><?= nl2br(htmlspecialchars($message)) ?></p>

<div class="bar"><span></span></div>

</div>

</body>
</html>

<?php
exit;

} catch(Exception $e){

error_log('Maintenance error: '.$e->getMessage());

}