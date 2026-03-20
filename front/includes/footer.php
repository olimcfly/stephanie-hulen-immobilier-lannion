<?php
/**
 * /front/includes/footer.php
 * Affiche le footer dynamique depuis la BD
 */

try {
    $pdo = getDB();

    // Charger le middleware menus dynamiques
    $middlewarePath = dirname(__DIR__) . '/middleware/menu-middleware.php';
    if (file_exists($middlewarePath)) require_once $middlewarePath;

    // Récupérer le footer actif
    $stmt = $pdo->prepare("SELECT * FROM footers WHERE status='active' LIMIT 1");
    $stmt->execute();
    $footer = $stmt->fetch();

    if (!$footer) {
        // Fallback
        $footer = [
            'logo_url' => '/assets/img/logo.png',
            'logo_width' => '120',
            'phone' => '',
            'email' => '',
            'address' => '',
            'bg_color' => '#0f172a',
            'text_color' => '#94a3b8',
            'link_color' => '#cbd5e1',
            'link_hover_color' => '#3b82f6',
            'columns' => '[]',
            'social_links' => '[]'
        ];
    }

    // Colonnes : priorite menus dynamiques → JSON footer
    $columns = function_exists('dynamicFooterColumns') ? dynamicFooterColumns($pdo) : [];
    if (empty($columns) && !empty($footer['columns'])) {
        $decoded = json_decode($footer['columns'], true);
        $columns = is_array($decoded) ? $decoded : [];
    }

    // Décoder réseaux (JSON)
    $socialLinks = [];
    if (!empty($footer['social_links'])) {
        $decoded = json_decode($footer['social_links'], true);
        $socialLinks = is_array($decoded) ? $decoded : [];
    }

} catch (Exception $e) {
    die('Erreur footer: ' . $e->getMessage());
}
?>

<footer class="ft" style="background-color: <?= htmlspecialchars($footer['bg_color']) ?>">
    <div class="ft-container">
        
        <!-- Colonne 1 : Logo + Infos -->
        <div class="ft-col">
            <?php if (!empty($footer['logo_url'])): ?>
                <img src="<?= htmlspecialchars($footer['logo_url']) ?>" 
                     width="<?= (int)$footer['logo_width'] ?>" 
                     alt="Logo"
                     class="ft-logo">
            <?php endif; ?>
            
            <?php if (!empty($footer['phone'])): ?>
                <p class="ft-info">
                    <strong>Tél :</strong> 
                    <a href="tel:<?= htmlspecialchars($footer['phone']) ?>" 
                       style="color: <?= htmlspecialchars($footer['link_color']) ?>">
                        <?= htmlspecialchars($footer['phone']) ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($footer['email'])): ?>
                <p class="ft-info">
                    <strong>Email :</strong> 
                    <a href="mailto:<?= htmlspecialchars($footer['email']) ?>" 
                       style="color: <?= htmlspecialchars($footer['link_color']) ?>">
                        <?= htmlspecialchars($footer['email']) ?>
                    </a>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($footer['address'])): ?>
                <p class="ft-info" style="color: <?= htmlspecialchars($footer['text_color']) ?>">
                    <?= nl2br(htmlspecialchars($footer['address'])) ?>
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Colonnes dynamiques -->
        <?php foreach ($columns as $col): ?>
            <div class="ft-col">
                <h3 class="ft-col-title" style="color: <?= htmlspecialchars($footer['text_color']) ?>">
                    <?= htmlspecialchars($col['title'] ?? '') ?>
                </h3>
                <ul class="ft-col-links">
                    <?php foreach (($col['links'] ?? []) as $link): ?>
                        <li>
                            <a href="<?= htmlspecialchars($link['url'] ?? '#') ?>"
                               style="color: <?= htmlspecialchars($footer['link_color']) ?>"
                               class="ft-link">
                                <?= htmlspecialchars($link['label'] ?? '') ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
        
    </div>
    
    <!-- Bottom : Réseaux + Copyright -->
    <div class="ft-bottom">
        <div class="ft-social">
            <?php foreach ($socialLinks as $social): ?>
                <a href="<?= htmlspecialchars($social['url'] ?? '#') ?>"
                   target="_blank"
                   rel="noopener"
                   class="ft-social-link"
                   title="<?= htmlspecialchars($social['label'] ?? '') ?>"
                   style="color: <?= htmlspecialchars($footer['link_color']) ?>">
                    <i class="<?= htmlspecialchars($social['icon'] ?? 'fas fa-link') ?>"></i>
                </a>
            <?php endforeach; ?>
        </div>
        
        <p class="ft-copyright" style="color: <?= htmlspecialchars($footer['text_color']) ?>">
            © <?= date('Y') ?> — Tous droits réservés
        </p>
    </div>
</footer>

<style>
.ft {
    padding: 40px 0 20px;
    border-top: 1px solid rgba(255,255,255,.1);
    margin-top: 60px;
}

.ft-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 40px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px 30px;
}

.ft-col {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.ft-logo {
    max-width: 100%;
    height: auto;
    margin-bottom: 10px;
}

.ft-info {
    font-size: 13px;
    margin: 0;
    line-height: 1.5;
}

.ft-info a {
    text-decoration: none;
    transition: opacity .2s;
}

.ft-info a:hover {
    opacity: .8;
}

.ft-col-title {
    font-size: 14px;
    font-weight: 700;
    margin: 0;
    text-transform: uppercase;
    letter-spacing: .05em;
}

.ft-col-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.ft-link {
    font-size: 13px;
    text-decoration: none;
    transition: opacity .2s;
}

.ft-link:hover {
    opacity: .8;
}

.ft-bottom {
    border-top: 1px solid rgba(255,255,255,.1);
    padding-top: 20px;
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}

.ft-social {
    display: flex;
    gap: 15px;
    justify-content: center;
}

.ft-social-link {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
    text-decoration: none;
    transition: background .2s;
}

.ft-social-link:hover {
    background: rgba(255,255,255,.15);
}

.ft-copyright {
    font-size: 12px;
    margin: 0;
    opacity: .8;
    width: 100%;
    text-align: center;
}

@media (max-width: 768px) {
    .ft-container {
        grid-template-columns: 1fr;
        gap: 25px;
    }
}
</style>
