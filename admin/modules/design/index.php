<?php
/**
 * /admin/modules/design/index.php
 * Dashboard Design — Gestion Header/Footer
 */

define('ADMIN_ROUTER', true);
require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';

try {
    $pdo = getDB();
} catch (Exception $e) {
    die('Erreur BD');
}

// Récupérer header/footer actifs
$header = $pdo->query("SELECT * FROM headers WHERE status='active' LIMIT 1")->fetch();
$footer = $pdo->query("SELECT * FROM footers WHERE status='active' LIMIT 1")->fetch();

// Fallbacks
if (!$header) {
    $header = ['id' => null, 'name' => 'Header par défaut', 'logo_url' => '', 'menu_items' => '[]'];
}
if (!$footer) {
    $footer = ['id' => null, 'name' => 'Footer par défaut', 'logo_url' => '', 'columns' => '[]'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Design — Header & Footer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f7fa; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        
        h1 { font-size: 28px; margin-bottom: 30px; color: #0f172a; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            border: 1px solid #e2e8f0;
        }
        
        .card h2 { font-size: 18px; margin-bottom: 15px; color: #1e293b; }
        
        .card p { font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.6; }
        
        .btn-group { display: flex; gap: 10px; }
        
        .btn {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            border: none;
            cursor: pointer;
            transition: all .2s;
            text-align: center;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99,102,241,.3);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }
        
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        
        .status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            background: #dcfce7;
            color: #166534;
        }
        
        .info-box {
            background: #f0f9ff;
            border-left: 3px solid #0284c7;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            font-size: 13px;
            color: #0369a1;
        }
    </style>
</head>
<body>

<div class="container">
    <h1><i class="fas fa-palette"></i> Design — Header & Footer</h1>
    
    <div class="grid">
        
        <!-- Header Card -->
        <div class="card">
            <h2>🎯 Header</h2>
            <p>Gérez le logo, le menu et l'appel à l'action du header.</p>
            
            <?php if ($header['id']): ?>
                <div class="status">✓ Actif</div>
                <div class="info-box">
                    <strong><?= htmlspecialchars($header['name']) ?></strong><br>
                    ID: <?= $header['id'] ?>
                </div>
            <?php else: ?>
                <div class="info-box">Aucun header créé</div>
            <?php endif; ?>
            
            <div class="btn-group" style="margin-top: 20px;">
                <a href="?page=design&type=header&id=<?= $header['id'] ?? 'new' ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Éditer
                </a>
            </div>
        </div>
        
        <!-- Footer Card -->
        <div class="card">
            <h2>📍 Footer</h2>
            <p>Gérez le logo, les liens, les colonnes et les réseaux du footer.</p>
            
            <?php if ($footer['id']): ?>
                <div class="status">✓ Actif</div>
                <div class="info-box">
                    <strong><?= htmlspecialchars($footer['name']) ?></strong><br>
                    ID: <?= $footer['id'] ?>
                </div>
            <?php else: ?>
                <div class="info-box">Aucun footer créé</div>
            <?php endif; ?>
            
            <div class="btn-group" style="margin-top: 20px;">
                <a href="?page=design&type=footer&id=<?= $footer['id'] ?? 'new' ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Éditer
                </a>
            </div>
        </div>
        
    </div>
</div>

</body>
</html>
