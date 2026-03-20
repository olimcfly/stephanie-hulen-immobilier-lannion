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

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

*{box-sizing:border-box;margin:0;padding:0;}

body{
font-family:'Inter',system-ui,-apple-system,sans-serif;
background:#0f172a;
display:flex;
align-items:center;
justify-content:center;
min-height:100vh;
margin:0;
overflow:hidden;
position:relative;
}

body::before{
content:'';
position:absolute;
top:-50%;left:-50%;
width:200%;height:200%;
background:
  radial-gradient(ellipse at 20% 50%, rgba(99,102,241,0.15) 0%, transparent 50%),
  radial-gradient(ellipse at 80% 20%, rgba(139,92,246,0.12) 0%, transparent 50%),
  radial-gradient(ellipse at 60% 80%, rgba(59,130,246,0.1) 0%, transparent 50%);
animation:aurora 15s ease-in-out infinite alternate;
}

@keyframes aurora{
0%{transform:translate(0,0) rotate(0deg);}
100%{transform:translate(-2%,2%) rotate(3deg);}
}

.box{
background:rgba(255,255,255,0.03);
backdrop-filter:blur(20px);
-webkit-backdrop-filter:blur(20px);
border:1px solid rgba(255,255,255,0.08);
padding:48px 40px;
border-radius:24px;
width:420px;
max-width:calc(100vw - 32px);
position:relative;
z-index:1;
animation:fadeUp .6s cubic-bezier(.16,1,.3,1) both;
}

@keyframes fadeUp{
from{opacity:0;transform:translateY(24px);}
to{opacity:1;transform:translateY(0);}
}

.logo{
text-align:center;
margin-bottom:32px;
}

.logo img{
max-width:180px;
filter:brightness(0) invert(1);
opacity:.9;
}

h2{
text-align:center;
margin-bottom:32px;
color:#f1f5f9;
font-size:1.5rem;
font-weight:600;
letter-spacing:-.02em;
}

.form-group{
position:relative;
margin-top:20px;
}

.form-group label{
display:block;
font-size:.8rem;
font-weight:500;
color:rgba(148,163,184,.9);
margin-bottom:8px;
text-transform:uppercase;
letter-spacing:.06em;
}

input[type="email"],
input[type="text"]{
width:100%;
padding:14px 16px;
background:rgba(255,255,255,0.05);
border:1px solid rgba(255,255,255,0.1);
border-radius:12px;
color:#f1f5f9;
font-family:inherit;
font-size:1rem;
transition:all .25s ease;
outline:none;
}

input[type="email"]::placeholder,
input[type="text"]::placeholder{
color:rgba(148,163,184,.4);
}

input[type="email"]:focus,
input[type="text"]:focus{
border-color:rgba(99,102,241,.6);
background:rgba(255,255,255,0.07);
box-shadow:0 0 0 3px rgba(99,102,241,.15), 0 0 20px rgba(99,102,241,.08);
}

button{
width:100%;
padding:14px;
margin-top:24px;
background:linear-gradient(135deg,#6366f1,#8b5cf6);
color:white;
border:none;
border-radius:12px;
font-family:inherit;
font-size:.95rem;
font-weight:600;
cursor:pointer;
transition:all .3s ease;
position:relative;
overflow:hidden;
letter-spacing:.01em;
}

button::before{
content:'';
position:absolute;
top:0;left:0;right:0;bottom:0;
background:linear-gradient(135deg,#818cf8,#a78bfa);
opacity:0;
transition:opacity .3s ease;
border-radius:12px;
}

button:hover::before{
opacity:1;
}

button:hover{
transform:translateY(-1px);
box-shadow:0 8px 25px rgba(99,102,241,.35);
}

button:active{
transform:translateY(0);
}

button span{
position:relative;
z-index:1;
}

.error{
background:rgba(239,68,68,0.1);
border:1px solid rgba(239,68,68,0.2);
padding:12px 16px;
margin-bottom:20px;
border-radius:12px;
color:#fca5a5;
font-size:.875rem;
display:flex;
align-items:center;
gap:8px;
}

.error::before{
content:'!';
display:inline-flex;
align-items:center;
justify-content:center;
width:20px;height:20px;
background:rgba(239,68,68,.2);
border-radius:50%;
font-size:.75rem;
font-weight:700;
flex-shrink:0;
}

.success{
background:rgba(34,197,94,0.1);
border:1px solid rgba(34,197,94,0.2);
padding:12px 16px;
margin-bottom:20px;
border-radius:12px;
color:#86efac;
font-size:.875rem;
display:flex;
align-items:center;
gap:8px;
}

.success::before{
content:'\2713';
display:inline-flex;
align-items:center;
justify-content:center;
width:20px;height:20px;
background:rgba(34,197,94,.2);
border-radius:50%;
font-size:.75rem;
font-weight:700;
flex-shrink:0;
}

.info{
font-size:.8rem;
color:rgba(148,163,184,.6);
margin-top:16px;
text-align:center;
line-height:1.6;
}

.info strong{
color:rgba(148,163,184,.8);
font-weight:600;
}

.divider{
height:1px;
background:rgba(255,255,255,0.06);
margin:28px 0 16px;
}

/* OTP input styling */
input[name="otp"]{
text-align:center;
font-size:1.8rem;
font-weight:600;
letter-spacing:.6em;
padding:16px;
font-variant-numeric:tabular-nums;
}

</style>

</head>

<body>

<div class="box">

<div class="logo">
<img src="/assets/img/ecosysteme-immo-logo.png" alt="Logo">
</div>

<h2>Administration</h2>

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

<div class="form-group">
<label for="email">Adresse email</label>
<input type="email"
id="email"
name="email"
placeholder="vous@exemple.fr"
required
autocomplete="email"
autofocus>
</div>

<button><span>Recevoir le code</span></button>

</form>

<?php elseif($step==='otp'): ?>

<form method="POST">

<input type="hidden" name="step" value="otp">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

<p class="info">
Code envoyé à<br>
<strong><?= htmlspecialchars($_SESSION['otp_email'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
</p>

<div class="form-group">
<label for="otp">Code de vérification</label>
<input type="text"
id="otp"
name="otp"
placeholder="000000"
maxlength="6"
pattern="[0-9]{6}"
inputmode="numeric"
autocomplete="one-time-code"
required
autofocus>
</div>

<button><span>Se connecter</span></button>

</form>

<?php endif ?>

<div class="divider"></div>

<p class="info">
Propulsé par <strong>ÉCOSYSTÈME IMMO</strong>
</p>

</div>

</body>
</html>