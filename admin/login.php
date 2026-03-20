<?php
/**
 * ADMIN LOGIN
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

/* Headers de sécurité */

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

/* Cookie de session sécurisé */

if (session_status() === PHP_SESSION_ACTIVE) {
    $params = session_get_cookie_params();
    if (!$params['secure'] || !$params['httponly']) {
        ini_set('session.cookie_secure', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
    }
}

/* Déjà connecté */

if (!empty($_SESSION['admin_id'])) {
    header("Location: /admin/dashboard.php");
    exit;
}

/* DB */

try {
    $db = getDB();
} catch (Exception $e) {
    die("Erreur connexion base");
}

/* Anti brute-force : max 5 tentatives par 15 minutes */

$maxAttempts = 5;
$lockoutTime = 900; // 15 minutes

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['login_first_attempt'] = time();
}

if ($_SESSION['login_attempts'] >= $maxAttempts && (time() - $_SESSION['login_first_attempt']) < $lockoutTime) {
    $remainingTime = ceil(($lockoutTime - (time() - $_SESSION['login_first_attempt'])) / 60);
    $error = "Trop de tentatives. Réessayez dans {$remainingTime} minute(s).";
    $step = 'blocked';
} else {
    if ((time() - ($_SESSION['login_first_attempt'] ?? 0)) >= $lockoutTime) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['login_first_attempt'] = time();
    }
}

/* Variables */

$error = $error ?? '';
$success = '';
$step = $step ?? ($_POST['step'] ?? 'email');

/* Traitement */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($step ?? '') !== 'blocked') {

    /* Vérification CSRF */
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        $error = "Jeton de sécurité invalide. Rechargez la page.";
        $step = 'email';
    }

    elseif ($step === 'email') {

        $email = sanitize($_POST['email'] ?? '', 'email');

        if (!$email || !isValidEmail($email)) {

            $error = "Email invalide";

        } else {

            $stmt = $db->prepare("SELECT id,email FROM admins WHERE email=? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if (!$admin) {

                /* Message générique pour ne pas révéler si l'email existe */
                $success = "Si cette adresse est autorisée, un code a été envoyé.";
                $step = "otp";
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_time'] = time();

            } else {

                $otp = str_pad(random_int(0,999999),6,'0',STR_PAD_LEFT);

                $_SESSION['otp'] = password_hash($otp, PASSWORD_DEFAULT);
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_time'] = time();
                $_SESSION['otp_attempts'] = 0;

                $subject = '[' . SITE_TITLE . '] Code de connexion';
                $message = "Votre code : $otp\nValide 10 minutes.";
                $headers = "From: ".ADMIN_EMAIL."\r\n";

                mail($email,$subject,$message,$headers);

                $success = "Si cette adresse est autorisée, un code a été envoyé.";
                $step = "otp";
            }
        }
    }

    elseif ($step === 'otp') {

        $otp = sanitize($_POST['otp'] ?? '');

        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;

        if (!isset($_SESSION['otp'])) {

            $error = "Session expirée";
            $step='email';

        }

        elseif (time() - $_SESSION['otp_time'] > 600) {

            $error="Code expiré";
            $step='email';
            unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time'], $_SESSION['otp_attempts']);

        }

        elseif (($_SESSION['otp_attempts'] ?? 0) >= 5) {

            $error="Trop de tentatives. Demandez un nouveau code.";
            $step='email';
            unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time'], $_SESSION['otp_attempts']);

        }

        elseif (!password_verify($otp, $_SESSION['otp'])) {

            $_SESSION['otp_attempts'] = ($_SESSION['otp_attempts'] ?? 0) + 1;
            $error="Code incorrect";

        }

        else {

            $stmt=$db->prepare("SELECT id,email FROM admins WHERE email=? LIMIT 1");
            $stmt->execute([$_SESSION['otp_email']]);
            $admin=$stmt->fetch();

            if (!$admin) {
                $error = "Erreur d'authentification";
                $step = 'email';
            } else {
                /* Régénérer l'ID de session pour éviter la fixation de session */
                session_regenerate_id(true);

                $_SESSION['admin_id']=$admin['id'];
                $_SESSION['admin_email']=$admin['email'];
                $_SESSION['admin_login_time']=time();

                unset($_SESSION['otp'], $_SESSION['otp_email'], $_SESSION['otp_time'], $_SESSION['otp_attempts']);
                unset($_SESSION['login_attempts'], $_SESSION['login_first_attempt']);

                header("Location: /admin/dashboard.php");
                exit;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connexion administration</title>

<style>

body{
font-family:Arial;
background:linear-gradient(135deg,#667eea,#764ba2);
display:flex;
align-items:center;
justify-content:center;
height:100vh;
margin:0;
}

.box{
background:white;
padding:40px;
border-radius:10px;
width:360px;
box-shadow:0 10px 40px rgba(0,0,0,0.2);
}

.logo{
text-align:center;
margin-bottom:20px;
}

.logo img{
max-width:160px;
}

h2{
text-align:center;
margin-bottom:20px;
}

input{
width:100%;
padding:12px;
margin-top:10px;
border:1px solid #ddd;
border-radius:6px;
}

button{
width:100%;
padding:13px;
margin-top:15px;
background:#667eea;
color:white;
border:none;
border-radius:6px;
font-weight:bold;
cursor:pointer;
}

.error{
background:#ffe6e6;
padding:10px;
margin-bottom:15px;
border-radius:6px;
}

.success{
background:#e6ffe6;
padding:10px;
margin-bottom:15px;
border-radius:6px;
}

.info{
font-size:13px;
color:#777;
margin-top:10px;
text-align:center;
}

</style>

</head>

<body>

<div class="box">

<div class="logo">
<img src="/assets/img/ecosysteme-immo-logo.png">
</div>

<h2>🔐 Administration</h2>

<?php if($error): ?>
<div class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif ?>

<?php if($success): ?>
<div class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif ?>

<?php if($step==='email'): ?>

<form method="POST">

<input type="hidden" name="step" value="email">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

<input type="email"
name="email"
placeholder="Votre adresse email"
required
autocomplete="email">

<button>Recevoir le code</button>

</form>

<?php elseif($step==='otp'): ?>

<form method="POST">

<input type="hidden" name="step" value="otp">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

<p class="info">
Code envoyé à<br>
<strong><?= htmlspecialchars($_SESSION['otp_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
</p>

<input type="text"
name="otp"
placeholder="000000"
maxlength="6"
pattern="[0-9]{6}"
inputmode="numeric"
autocomplete="one-time-code"
required>

<button>Connexion</button>

</form>

<?php endif ?>

<p class="info">
Propulsé par <strong>ÉCOSYSTÈME IMMO</strong>
</p>

</div>

</body>
</html>