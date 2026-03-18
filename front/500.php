<?php
/**
 * Page 500 - Erreur serveur
 * /front/500.php
 */
http_response_code(500);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur serveur - 500</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
            color: #fff;
            text-align: center;
            padding: 20px;
        }
        .error-container {
            max-width: 500px;
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            line-height: 1;
            opacity: 0.3;
            margin-bottom: 10px;
        }
        .error-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .error-message {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .error-btn {
            display: inline-block;
            padding: 14px 35px;
            background: #fff;
            color: #1e3a5f;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .error-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">500</div>
        <h1 class="error-title">Erreur serveur</h1>
        <p class="error-message">
            Une erreur inattendue s'est produite. Notre équipe a été notifiée.
            Veuillez réessayer dans quelques instants.
        </p>
        <a href="/" class="error-btn">Retour à l'accueil</a>
    </div>
</body>
</html>