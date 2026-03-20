<?php
/**
 * /front/includes/header.php
 * Affiche le header dynamique depuis la BD
 */

try {
    $pdo = getDB();

    // Charger le middleware menus dynamiques
    $middlewarePath = dirname(__DIR__) . '/middleware/menu-middleware.php';
    if (file_exists($middlewarePath)) require_once $middlewarePath;

    // Récupérer le header actif
    $stmt = $pdo->prepare("SELECT * FROM headers WHERE status='active' LIMIT 1");
    $stmt->execute();
    $header = $stmt->fetch();

    if (!$header) {
        // Fallback si pas de header actif
        $header = [
            'logo_url' => '/assets/img/logo.png',
            'logo_width' => '160',
            'menu_items' => '[]',
            'cta_text' => 'Contactez-moi',
            'cta_link' => '/contact',
            'bg_color' => '#ffffff',
            'text_color' => '#000000',
            'hover_color' => '#6366f1',
            'phone_number' => ''
        ];
    }

    // Menu : priorite menus dynamiques → JSON header
    $menuItems = function_exists('dynamicHeaderMenu') ? dynamicHeaderMenu($pdo) : [];
    if (empty($menuItems) && !empty($header['menu_items'])) {
        $decoded = json_decode($header['menu_items'], true);
        $menuItems = is_array($decoded) ? $decoded : [];
    }

} catch (Exception $e) {
    die('Erreur header: ' . $e->getMessage());
}
?>

<header class="hd" style="background-color: <?= htmlspecialchars($header['bg_color']) ?>">
    <div class="hd-container">
        
        <!-- Logo -->
        <a href="/" class="hd-logo">
            <img src="<?= htmlspecialchars($header['logo_url']) ?>" 
                 width="<?= (int)$header['logo_width'] ?>" 
                 alt="Logo">
        </a>
        
        <!-- Nav -->
        <nav class="hd-nav">
            <?php foreach ($menuItems as $item): ?>
                <a href="<?= htmlspecialchars($item['url'] ?? '/') ?>" 
                   class="hd-nav-item"
                   style="color: <?= htmlspecialchars($header['text_color']) ?>">
                    <?= htmlspecialchars($item['label'] ?? '') ?>
                </a>
            <?php endforeach; ?>
        </nav>
        
        <!-- CTA Button -->
        <?php if (!empty($header['cta_text']) && !empty($header['cta_link'])): ?>
            <a href="<?= htmlspecialchars($header['cta_link']) ?>" 
               class="hd-cta"
               style="background-color: <?= htmlspecialchars($header['hover_color']) ?>">
                <?= htmlspecialchars($header['cta_text']) ?>
            </a>
        <?php endif; ?>
        
        <!-- Phone (si présent) -->
        <?php if (!empty($header['phone_number'])): ?>
            <a href="tel:<?= htmlspecialchars($header['phone_number']) ?>" 
               class="hd-phone"
               style="color: <?= htmlspecialchars($header['text_color']) ?>">
                <i class="fas fa-phone"></i>
            </a>
        <?php endif; ?>
        
    </div>
</header>

<style>
.hd {
    padding: 16px 0;
    border-bottom: 1px solid rgba(0,0,0,.08);
}

.hd-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    gap: 30px;
}

.hd-logo img {
    height: auto;
    display: block;
}

.hd-nav {
    display: flex;
    gap: 25px;
    flex: 1;
    justify-content: center;
}

.hd-nav-item {
    text-decoration: none;
    font-weight: 500;
    font-size: 14px;
    transition: opacity .2s;
}

.hd-nav-item:hover {
    opacity: .7;
}

.hd-cta {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 6px;
    color: white;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    transition: opacity .2s;
    white-space: nowrap;
}

.hd-cta:hover {
    opacity: .9;
}

.hd-phone {
    text-decoration: none;
    font-size: 18px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0,0,0,.05);
    transition: background .2s;
}

.hd-phone:hover {
    background: rgba(0,0,0,.1);
}

@media (max-width: 768px) {
    .hd-container {
        flex-wrap: wrap;
        gap: 15px;
    }
    .hd-nav {
        order: 3;
        width: 100%;
        gap: 15px;
        justify-content: flex-start;
    }
}
</style>
