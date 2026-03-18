<?php
/**
 * footer.php - Footer réutilisable pour PUBLIC (PATCHED - MENUS DYNAMIQUES)
 * Chemin : /front/includes/footer.php
 */

// ── Charger le helper menus ──
if (file_exists(__DIR__ . '/menu-helper.php')) {
    require_once __DIR__ . '/menu-helper.php';
}

// ── S'assurer que SiteSettings est disponible ──
if (!class_exists('SiteSettings')) {
    $ssPath = __DIR__ . '/../../includes/SiteSettings.php';
    if (!file_exists($ssPath)) $ssPath = __DIR__ . '/../includes/SiteSettings.php';
    if (!file_exists($ssPath)) $ssPath = __DIR__ . '/SiteSettings.php';
    if (file_exists($ssPath) && isset($pdo)) {
        require_once $ssPath;
        SiteSettings::init($pdo);
    }
}

// ── Helper local : récupérer un setting avec fallback ──
function _fs(string $key, string $fallback = ''): string {
    if (class_exists('SiteSettings')) {
        return htmlspecialchars(SiteSettings::get($key, $fallback), ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8');
}

function _fsRaw(string $key, string $fallback = ''): string {
    if (class_exists('SiteSettings')) {
        return SiteSettings::get($key, $fallback);
    }
    return $fallback;
}

// ── Préparer les données ──
$agentName    = _fs('agent_name',    'Eduardo De Sul');
$agentTitle   = _fs('agent_title',   'Conseiller immobilier professionnel');
$agentCity    = _fs('agent_city',    'Bordeaux');
$agentRegion  = _fs('agent_region',  'Gironde (33)');
$agentNetwork = _fs('agent_network', '');
$agentPhone   = _fs('agent_phone',   '');
$agentEmail   = _fs('agent_email',   '');
$agentAddress = _fs('agent_address', '');
$postalCode   = _fs('agent_postal_code', '');
$siteName     = _fs('site_name',     'Eduardo De Sul Immobilier');
$legalEntity  = _fs('legal_entity',  $agentName . ' Immobilier');
$agentRsac    = _fs('agent_rsac',    '');
$agentSiret   = _fs('agent_siret',   '');

// Téléphone formaté et lien
$phoneClean = preg_replace('/[^0-9]/', '', _fsRaw('agent_phone'));
$phoneFormatted = $agentPhone;
if (strlen($phoneClean) === 10 && $phoneClean[0] === '0') {
    $phoneFormatted = preg_replace('/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', '$1 $2 $3 $4 $5', $phoneClean);
    $phoneLink = 'tel:+33' . substr($phoneClean, 1);
} else {
    $phoneLink = 'tel:' . $phoneClean;
}

// Réseaux sociaux
$socialNetworks = [];
$socialMap = [
    'social_facebook'  => ['icon' => 'fab fa-facebook',  'label' => 'Facebook'],
    'social_instagram' => ['icon' => 'fab fa-instagram',  'label' => 'Instagram'],
    'social_linkedin'  => ['icon' => 'fab fa-linkedin',   'label' => 'LinkedIn'],
    'social_youtube'   => ['icon' => 'fab fa-youtube',     'label' => 'YouTube'],
    'social_tiktok'    => ['icon' => 'fab fa-tiktok',      'label' => 'TikTok'],
    'social_whatsapp'  => ['icon' => 'fab fa-whatsapp',    'label' => 'WhatsApp'],
];
foreach ($socialMap as $key => $info) {
    $url = _fsRaw($key);
    if ($url) {
        $socialNetworks[] = ['url' => $url, 'icon' => $info['icon'], 'label' => $info['label']];
    }
}

// ── Charger les menus footer ──
$footerServices = getMenu('footer-services', $pdo ?? null);
$footerResources = getMenu('footer-col2', $pdo ?? null);
$footerLegal = getMenu('footer-col3', $pdo ?? null);
?>

</div> <!-- Fin main-content -->

<!-- FOOTER -->
<footer class="main-footer">
    <div class="footer-container">
        
        <!-- Footer Top -->
        <div class="footer-top">
            <div class="container">
                <div class="row">
                    
                    <!-- Colonne 1: À propos -->
                    <div class="footer-col col-lg-3 col-md-6 mb-4">
                        <h5 class="footer-title">
                            <i class="fas fa-building"></i> <?= $agentName ?>
                        </h5>
                        <p class="footer-text">
                            <?= $agentTitle ?> à <?= $agentCity ?>, <?= $agentCity ?> Métropole et <?= $agentRegion ?>.
                            <?php if ($agentNetwork): ?>
                                <br>Réseau <?= $agentNetwork ?>.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($socialNetworks)): ?>
                            <div class="footer-social">
                                <?php foreach ($socialNetworks as $social): ?>
                                    <a href="<?= htmlspecialchars($social['url']) ?>" 
                                       class="social-link" 
                                       title="<?= htmlspecialchars($social['label']) ?>"
                                       target="_blank" rel="noopener noreferrer">
                                        <i class="<?= $social['icon'] ?>"></i>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- Fallback si aucun réseau configuré -->
                            <div class="footer-social">
                                <a href="#" class="social-link" title="Facebook"><i class="fab fa-facebook"></i></a>
                                <a href="#" class="social-link" title="Instagram"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-link" title="LinkedIn"><i class="fab fa-linkedin"></i></a>
                                <a href="#" class="social-link" title="WhatsApp"><i class="fab fa-whatsapp"></i></a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Colonne 2: Services (Menu Dynamique) -->
                    <div class="footer-col col-lg-3 col-md-6 mb-4">
                        <h5 class="footer-title">Services</h5>
                        <?php 
                        if (!empty($footerServices['items'])) {
                            echo buildFooterMenuHtml($footerServices['items']);
                        } else {
                            ?>
                            <ul class="footer-links">
                                <li><a href="/estimation">Estimation gratuite</a></li>
                                <li><a href="/diagnostic">Diagnostic vendeur</a></li>
                                <li><a href="/consultation">Consultation</a></li>
                                <li><a href="/financement">Aide au financement</a></li>
                                <li><a href="/contact">Me contacter</a></li>
                            </ul>
                            <?php
                        }
                        ?>
                    </div>
                    
                    <!-- Colonne 3: Ressources (Menu Dynamique) -->
                    <div class="footer-col col-lg-3 col-md-6 mb-4">
                        <h5 class="footer-title">Ressources</h5>
                        <?php 
                        if (!empty($footerResources['items'])) {
                            echo buildFooterMenuHtml($footerResources['items']);
                        } else {
                            ?>
                            <ul class="footer-links">
                                <li><a href="/">Accueil</a></li>
                                <li><a href="/a-propos">À propos</a></li>
                                <li><a href="/acheter">Acheter</a></li>
                                <li><a href="/vendre">Vendre</a></li>
                                <li><a href="/louer">Louer</a></li>
                                <li><a href="/blog">Blog</a></li>
                            </ul>
                            <?php
                        }
                        ?>
                    </div>
                    
                    <!-- Colonne 4: Contact -->
                    <div class="footer-col col-lg-3 col-md-6 mb-4">
                        <h5 class="footer-title">
                            <i class="fas fa-phone"></i> Contact
                        </h5>
                        <div class="footer-contact">
                            <?php if ($agentPhone): ?>
                                <p>
                                    <strong>Téléphone :</strong><br>
                                    <a href="<?= $phoneLink ?>"><?= $phoneFormatted ?></a>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($agentEmail): ?>
                                <p>
                                    <strong>Email :</strong><br>
                                    <a href="mailto:<?= $agentEmail ?>"><?= $agentEmail ?></a>
                                </p>
                            <?php endif; ?>
                            
                            <p>
                                <strong>Localisation :</strong><br>
                                <?php if ($agentAddress): ?>
                                    <?= $agentAddress ?><br>
                                    <?php if ($postalCode): ?><?= $postalCode ?> <?php endif; ?>
                                <?php endif; ?>
                                <?= $agentCity ?>
                                <?php if ($agentRegion): ?>, <?= $agentRegion ?><?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom (Copyright + Menu Légal Dynamique) -->
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-content">
                    <p class="copyright">
                        &copy; <?= date('Y') ?> <strong><?= $siteName ?></strong>. 
                        Tous droits réservés.
                        <?php if ($agentRsac): ?>
                            | RSAC <?= $agentRsac ?>
                        <?php endif; ?>
                    </p>
                    <div class="footer-bottom-links">
                        <?php 
                        if (!empty($footerLegal['items'])) {
                            $menuItems = $footerLegal['items'];
                            foreach ($menuItems as $key => $item) {
                                echo '<a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['title']) . '</a>';
                                if ($key < count($menuItems) - 1) echo '<span class="separator">|</span>';
                            }
                        } else {
                            ?>
                            <a href="/mentions-legales">Mentions légales</a>
                            <span class="separator">|</span>
                            <a href="/politique-confidentialite">Politique de confidentialité</a>
                            <span class="separator">|</span>
                            <a href="/cgu">Conditions générales</a>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</footer>

<!-- SCRIPTS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/script.js"></script>

</body>
</html>