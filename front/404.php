<?php
/**
 * PAGE 404 - /front/404.php
 * AFFICHÉE QUAND UNE PAGE N'EXISTE PAS OU EST EN BROUILLON
 */

http_response_code(404);
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page non trouvée</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --secondary: #8b5cf6;
            --bg: #f8fafc;
            --text: #1e293b;
            --muted: #64748b;
        }
        
        * { 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--bg), #f1f5f9);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
        }
        
        .container {
            text-align: center;
            padding: 40px 20px;
            max-width: 600px;
        }
        
        .code {
            font-size: 8rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            line-height: 1;
        }
        
        h1 {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--text);
        }
        
        p {
            font-size: 1.1rem;
            color: var(--muted);
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: #fff;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.3);
        }
        
        .icon {
            font-size: 5rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📄</div>
        <div class="code">404</div>
        <h1>Page non trouvée</h1>
        <p>
            Désolé, la page que vous cherchez n'existe pas ou n'est pas accessible.
            <br>Elle est peut-être en brouillon ou a été supprimée.
        </p>
        <a href="/" class="btn">← Revenir à l'accueil</a>
    </div>
</body>
</html>