<?php
/**
 * ADMIN LOGIN
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

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
/* Variables */

$error = '';
$success = '';
$step = $_POST['step'] ?? 'email';

/* Traitement */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($step === 'email') {

        $email = sanitize($_POST['email'] ?? '', 'email');
        $phone = sanitize($_POST['phone'] ?? '');

        if (!$email || !isValidEmail($email)) {

            $error = "Email invalide";

        } else {

            $stmt = $db->prepare("SELECT id,email FROM admins WHERE email=? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if (!$admin) {

                $error = "Email non autorisé";

            } else {

                $otp = str_pad(random_int(0,999999),6,'0',STR_PAD_LEFT);

                $_SESSION['otp'] = $otp;
                $_SESSION['otp_email'] = $email;
                $_SESSION['otp_phone'] = $phone;
                $_SESSION['otp_time'] = time();

                $subject = '[' . SITE_TITLE . '] Code de connexion';
                $message = "Votre code : $otp\nValide 10 minutes.";
                $headers = "From: ".ADMIN_EMAIL."\r\n";

                mail($email,$subject,$message,$headers);

                $success = "Code envoyé par email";
                $step = "otp";
            }
        }
    }

    elseif ($step === 'otp') {

        $otp = sanitize($_POST['otp'] ?? '');

        if (!isset($_SESSION['otp'])) {

            $error = "Session expirée";
            $step='email';

        }

        elseif (time() - $_SESSION['otp_time'] > 600) {

            $error="Code expiré";
            $step='email';
            unset($_SESSION['otp']);

        }

        elseif ($otp !== $_SESSION['otp']) {

            $error="Code incorrect";

        }

        else {

            $stmt=$db->prepare("SELECT id,email FROM admins WHERE email=? LIMIT 1");
            $stmt->execute([$_SESSION['otp_email']]);
            $admin=$stmt->fetch();

            $_SESSION['admin_id']=$admin['id'];
            $_SESSION['admin_email']=$admin['email'];

            unset($_SESSION['otp']);
            unset($_SESSION['otp_email']);
            unset($_SESSION['otp_phone']);
            unset($_SESSION['otp_time']);

            header("Location: /admin/dashboard.php");
            exit;
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
<div class="error"><?= $error ?></div>
<?php endif ?>

<?php if($success): ?>
<div class="success"><?= $success ?></div>
<?php endif ?>

<?php if($step==='email'): ?>

<form method="POST">

<input type="hidden" name="step" value="email">

<input type="email"
name="email"
placeholder="<?= ADMIN_EMAIL ?>"
required>

<input type="tel"
name="phone"
placeholder="Numéro de téléphone"
required>

<button>Recevoir le code</button>

</form>

<?php else: ?>

<form method="POST">

<input type="hidden" name="step" value="otp">

<p class="info">
Code envoyé à<br>
<strong><?= htmlspecialchars($_SESSION['otp_email'] ?? '') ?></strong>
</p>

<input type="text"
name="otp"
placeholder="000000"
maxlength="6"
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