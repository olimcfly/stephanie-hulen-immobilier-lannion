<?php
/**
 * /admin/modules/design/edit.php
 * Formulaire édition Header/Footer
 */

define('ADMIN_ROUTER', true);
require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';

try {
    $pdo = getDB();
} catch (Exception $e) {
    die('Erreur BD');
}

$type = $_GET['type'] ?? 'header'; // header ou footer
$id = $_GET['id'] ?? 'new';

if ($type === 'header') {
    $table = 'headers';
    $title = '🎯 Éditer Header';
} elseif ($type === 'footer') {
    $table = 'footers';
    $title = '📍 Éditer Footer';
} else {
    die('Type invalide');
}

$data = null;
if ($id !== 'new') {
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id=?");
    $stmt->execute([$id]);
    $data = $stmt->fetch();
}

// Fallback si nouveau
if (!$data) {
    $data = [
        'id' => null,
        'name' => ucfirst($type) . ' par défaut',
        'logo_url' => '',
        'logo_width' => 160,
        'bg_color' => '#ffffff',
        'text_color' => '#000000',
        'phone' => '',
        'email' => '',
        'address' => '',
        'menu_items' => '[]',
        'columns' => '[]',
        'social_links' => '[]'
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f7fa; }
        
        .container { max-width: 900px; margin: 0 auto; padding: 40px 20px; }
        
        h1 { font-size: 28px; margin-bottom: 30px; color: #0f172a; }
        
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.08);
            border: 1px solid #e2e8f0;
        }
        
        .form-section h2 { font-size: 16px; margin-bottom: 20px; color: #1e293b; padding-bottom: 10px; border-bottom: 2px solid #e2e8f0; }
        
        .form-group { margin-bottom: 20px; }
        
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 6px; color: #334155; }
        
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        input[type="color"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 13px;
            font-family: inherit;
        }
        
        textarea { resize: vertical; min-height: 100px; }
        
        input[type="color"] { padding: 6px; cursor: pointer; }
        
        .color-preview {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        .btn-group { display: flex; gap: 10px; margin-top: 30px; }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            border: none;
            cursor: pointer;
            transition: all .2s;
        }
        
        .btn-primary {
            background: #6366f1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4f46e5;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #334155;
        }
        
        .btn-secondary:hover {
            background: #cbd5e1;
        }
    </style>
</head>
<body>

<div class="container">
    <h1><?= $title ?></h1>
    
    <form method="POST" action="/admin/api/design/save.php">
        <input type="hidden" name="type" value="<?= $type ?>">
        <input type="hidden" name="id" value="<?= $data['id'] ?? '' ?>">
        
        <!-- Infos générales -->
        <div class="form-section">
            <h2>📋 Informations générales</h2>
            
            <div class="form-group">
                <label>Nom du <?= $type ?></label>
                <input type="text" name="name" value="<?= htmlspecialchars($data['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>URL du logo</label>
                <input type="text" name="logo_url" value="<?= htmlspecialchars($data['logo_url']) ?>" placeholder="/assets/img/logo.png">
            </div>
            
            <div class="form-group">
                <label>Largeur du logo (px)</label>
                <input type="text" name="logo_width" value="<?= $data['logo_width'] ?? 160 ?>" placeholder="160">
            </div>
        </div>
        
        <!-- Couleurs -->
        <div class="form-section">
            <h2>🎨 Couleurs</h2>
            
            <div class="form-group">
                <label>Couleur de fond</label>
                <input type="color" name="bg_color" value="<?= $data['bg_color'] ?? '#ffffff' ?>">
                <span class="color-preview" style="background-color: <?= $data['bg_color'] ?? '#ffffff' ?>"></span>
            </div>
            
            <div class="form-group">
                <label>Couleur du texte</label>
                <input type="color" name="text_color" value="<?= $data['text_color'] ?? '#000000' ?>">
                <span class="color-preview" style="background-color: <?= $data['text_color'] ?? '#000000' ?>"></span>
            </div>
            
            <?php if ($type === 'header'): ?>
                <div class="form-group">
                    <label>Couleur du survol</label>
                    <input type="color" name="hover_color" value="<?= $data['hover_color'] ?? '#6366f1' ?>">
                    <span class="color-preview" style="background-color: <?= $data['hover_color'] ?? '#6366f1' ?>"></span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Infos de contact (footer) -->
        <?php if ($type === 'footer'): ?>
        <div class="form-section">
            <h2>📞 Informations de contact</h2>
            
            <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($data['phone']) ?>" placeholder="+33 6 12 34 56 78">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($data['email']) ?>" placeholder="contact@exemple.fr">
            </div>
            
            <div class="form-group">
                <label>Adresse</label>
                <textarea name="address" placeholder="123 rue de la Paix, 75000 Paris"><?= htmlspecialchars($data['address']) ?></textarea>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Header spécifique -->
        <?php if ($type === 'header'): ?>
        <div class="form-section">
            <h2>🎯 Menu & CTA</h2>
            
            <div class="form-group">
                <label>Numéro de téléphone</label>
                <input type="tel" name="phone_number" value="<?= htmlspecialchars($data['phone_number']) ?>" placeholder="+33 6 12 34 56 78">
            </div>
            
            <div class="form-group">
                <label>Texte du bouton CTA</label>
                <input type="text" name="cta_text" value="<?= htmlspecialchars($data['cta_text'] ?? '') ?>" placeholder="Contactez-moi">
            </div>
            
            <div class="form-group">
                <label>Lien du bouton CTA</label>
                <input type="text" name="cta_link" value="<?= htmlspecialchars($data['cta_link'] ?? '') ?>" placeholder="/contact">
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Boutons -->
        <div class="btn-group">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Sauvegarder
            </button>
            <a href="?page=design" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </form>
</div>

</body>
</html>
