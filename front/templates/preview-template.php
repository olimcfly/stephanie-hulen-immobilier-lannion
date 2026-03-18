<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  Preview Template — Rendu frontend avec données fictives
 *  /front/preview-template.php
 *  
 *  Usage: /front/preview-template.php?tpl=pages/t1-accueil&token=XXX
 *  Accessible uniquement avec un token admin valide
 * ══════════════════════════════════════════════════════════════
 */

// ─── Auth par token de session admin ───
session_start();
$isAdmin = !empty($_SESSION['admin_logged_in']) || !empty($_SESSION['admin_id']) || !empty($_SESSION['is_admin']) || !empty($_SESSION['user_id']);

if (!$isAdmin) {
    http_response_code(403);
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:40px;color:#c0392b;"><h2>Accès refusé</h2><p>Connectez-vous à l\'admin pour accéder à la preview.</p></body></html>';
    exit;
}

// ─── Paramètres ───
$tpl = $_GET['tpl'] ?? '';
$tpl = str_replace(['..', "\0"], '', $tpl);

if (!preg_match('/^(pages|captures|ressources)\/[a-zA-Z0-9_\-]+\.php$/', $tpl)) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:40px;color:#c0392b;"><h2>Template invalide</h2><p>Format attendu : pages/t1-accueil.php</p></body></html>';
    exit;
}

define('ROOT_PATH', dirname(__DIR__));
$templateFile = ROOT_PATH . '/front/templates/' . $tpl;

if (!file_exists($templateFile)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:40px;color:#c0392b;"><h2>Template non trouvé</h2><p>' . htmlspecialchars($tpl) . '</p></body></html>';
    exit;
}

// ─── Charger la DB ───
if (file_exists(ROOT_PATH . '/includes/classes/Database.php')) {
    require_once ROOT_PATH . '/includes/classes/Database.php';
}
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    $db = null;
}

// ─── Charger les helpers frontend ───
$helpers = ['utils', 'palette', 'layout', 'site-config', 'menu-helper'];
foreach ($helpers as $h) {
    $f = ROOT_PATH . '/front/helpers/' . $h . '.php';
    if (file_exists($f)) require_once $f;
}
$fInc = ROOT_PATH . '/front/includes/functions/menu-functions.php';
if (file_exists($fInc)) require_once $fInc;

// ─── Données mock selon la catégorie ───
$parts = explode('/', $tpl);
$category = $parts[0]; // pages, captures, ressources
$filename = $parts[1]; // t1-accueil.php

// Données communes
$siteConfig = [
    'site_name'    => 'Eduardo De Sul Immobilier',
    'site_tagline' => 'Votre conseiller immobilier à Bordeaux',
    'phone'        => '06 12 34 56 78',
    'email'        => 'contact@eduardo-desul-immobilier.fr',
    'address'      => '33290 Blanquefort, Bordeaux Métropole',
    'logo_url'     => '/front/assets/images/logo.svg',
];

// Mock article
$article = [
    'id'             => 99,
    'title'          => 'Guide complet : Acheter un bien à Bordeaux en 2025',
    'titre'          => 'Guide complet : Acheter un bien à Bordeaux en 2025',
    'slug'           => 'acheter-bien-bordeaux-2025',
    'content'        => '<h2>Introduction</h2><p>Bordeaux reste une ville attractive pour l\'investissement immobilier. Découvrez notre guide complet pour acheter en toute sérénité.</p><h2>Les quartiers porteurs</h2><p>De Bacalan aux Chartrons, en passant par Saint-Michel et Nansouty, chaque quartier a ses atouts.</p><h2>Les étapes clés</h2><p>1. Définir votre budget<br>2. Obtenir votre financement<br>3. Rechercher le bien idéal<br>4. Faire une offre<br>5. Signer le compromis</p><h2>Conclusion</h2><p>N\'hésitez pas à me contacter pour un accompagnement personnalisé dans votre projet immobilier à Bordeaux.</p>',
    'excerpt'        => 'Découvrez notre guide complet pour acheter un bien immobilier à Bordeaux en 2025.',
    'meta_title'     => 'Acheter un bien à Bordeaux — Guide 2025',
    'meta_description' => 'Tout savoir pour acheter un bien immobilier à Bordeaux : quartiers, prix, étapes, conseils.',
    'image_url'      => '/front/assets/images/hero-bordeaux.jpg',
    'featured_image' => '/front/assets/images/hero-bordeaux.jpg',
    'author'         => 'Eduardo De Sul',
    'category'       => 'Achat immobilier',
    'tags'           => 'bordeaux, achat, immobilier, guide',
    'reading_time'   => 8,
    'temps_lecture'   => 8,
    'status'         => 'published',
    'statut'         => 'published',
    'created_at'     => date('Y-m-d H:i:s', strtotime('-3 days')),
    'updated_at'     => date('Y-m-d H:i:s'),
    'published_at'   => date('Y-m-d H:i:s', strtotime('-2 days')),
];

// Mock secteur
$secteur = [
    'id'               => 1,
    'name'             => 'Bacalan',
    'nom'              => 'Bacalan',
    'slug'             => 'bacalan-bordeaux',
    'city'             => 'Bordeaux',
    'ville'            => 'Bordeaux',
    'description'      => 'Bacalan est un quartier en pleine transformation situé au nord de Bordeaux, entre les bassins à flot et la Garonne.',
    'content'          => '<p>Bacalan bénéficie d\'une situation exceptionnelle...</p>',
    'image_url'        => '/front/assets/images/hero-bordeaux.jpg',
    'featured_image'   => '/front/assets/images/hero-bordeaux.jpg',
    'prix_moyen'       => 4200,
    'avg_price'        => 4200,
    'population'       => 12500,
    'code_postal'      => '33300',
    'meta_title'       => 'Immobilier Bacalan Bordeaux — Prix, avis, quartier',
    'meta_description' => 'Tout savoir sur l\'immobilier à Bacalan : prix au m², tendances, vie de quartier.',
    'status'           => 'published',
    'statut'           => 'published',
];

// Mock bien immobilier
$bien = [
    'id'            => 42,
    'title'         => 'Appartement T3 lumineux — Bacalan',
    'titre'         => 'Appartement T3 lumineux — Bacalan',
    'slug'          => 'appartement-t3-bacalan',
    'type'          => 'Appartement',
    'transaction'   => 'Vente',
    'price'         => 285000,
    'prix'          => 285000,
    'surface'       => 68,
    'rooms'         => 3,
    'pieces'        => 3,
    'bedrooms'      => 2,
    'chambres'      => 2,
    'city'          => 'Bordeaux',
    'ville'         => 'Bordeaux',
    'quartier'      => 'Bacalan',
    'code_postal'   => '33300',
    'description'   => 'Magnifique T3 de 68m² au 3ème étage avec vue dégagée. Deux chambres, séjour lumineux, cuisine équipée, balcon.',
    'image_url'     => '/front/assets/images/maison-a-vendre-bordeaux.png',
    'featured_image'=> '/front/assets/images/maison-a-vendre-bordeaux.png',
    'images'        => '[]',
    'dpe'           => 'C',
    'ges'           => 'B',
    'status'        => 'published',
    'statut'        => 'published',
];

// Mock guide
$guide = [
    'id'          => 10,
    'title'       => 'Guide local : Vivre à Bordeaux',
    'titre'       => 'Guide local : Vivre à Bordeaux',
    'slug'        => 'guide-local-bordeaux',
    'content'     => '<h2>Pourquoi Bordeaux ?</h2><p>Bordeaux est régulièrement classée parmi les villes les plus attractives de France.</p>',
    'excerpt'     => 'Tout ce qu\'il faut savoir pour vivre à Bordeaux.',
    'image_url'   => '/front/assets/images/hero-bordeaux.jpg',
    'status'      => 'published',
];

// Mock capture page
$capture = [
    'id'          => 5,
    'title'       => 'Estimation gratuite de votre bien',
    'titre'       => 'Estimation gratuite de votre bien',
    'slug'        => 'estimation-gratuite',
    'content'     => '<p>Obtenez une estimation gratuite et précise de votre bien immobilier à Bordeaux en quelques minutes.</p>',
    'cta_text'    => 'Estimer mon bien gratuitement',
    'image_url'   => '/front/assets/images/estimation-bordeaux.png',
    'status'      => 'published',
];

// Mock ressource
$ressource = [
    'id'          => 3,
    'title'       => 'Checklist : Les 10 points à vérifier avant d\'acheter',
    'titre'       => 'Checklist : Les 10 points à vérifier avant d\'acheter',
    'slug'        => 'checklist-achat-immobilier',
    'content'     => '<p>Avant de signer, vérifiez ces 10 points essentiels...</p>',
    'type'        => 'PDF',
    'image_url'   => '/front/assets/images/estimation-immobiliere-bordeaux.png',
    'status'      => 'published',
];

// Mock listings
$articles = [$article, array_merge($article, ['id' => 100, 'title' => 'Vendre son bien à Bordeaux : les erreurs à éviter', 'titre' => 'Vendre son bien à Bordeaux : les erreurs à éviter', 'slug' => 'vendre-bien-bordeaux-erreurs'])];
$secteurs = [$secteur, array_merge($secteur, ['id' => 2, 'name' => 'Chartrons', 'nom' => 'Chartrons', 'slug' => 'chartrons-bordeaux', 'prix_moyen' => 5100, 'avg_price' => 5100])];
$biens = [$bien, array_merge($bien, ['id' => 43, 'title' => 'Maison T5 avec jardin — Caudéran', 'titre' => 'Maison T5 avec jardin — Caudéran', 'price' => 520000, 'prix' => 520000, 'surface' => 135])];
$ressources = [$ressource, array_merge($ressource, ['id' => 4, 'title' => 'Guide du primo-accédant', 'titre' => 'Guide du primo-accédant', 'slug' => 'guide-primo-accedant', 'type' => 'Guide'])];

// Variables universelles passées aux templates
$page = $article; // fallback
$slug = $article['slug'];
$isPreview = true;
$previewMode = true;

// ─── Rendu ───
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview — <?= htmlspecialchars(basename($tpl, '.php')) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php
    // Charger les CSS frontend
    $cssFiles = ['style','components','pages','forms','landing','legal','reports','secteurs'];
    foreach ($cssFiles as $css) {
        $cssPath = '/front/assets/css/' . $css . '.css';
        if (file_exists(ROOT_PATH . $cssPath)) {
            echo '<link rel="stylesheet" href="' . $cssPath . '">' . "\n    ";
        }
    }
    // Palette CSS
    if (function_exists('getPaletteCSS')) {
        echo '<style>' . getPaletteCSS() . '</style>';
    }
    ?>
    <style>
        .preview-banner {
            position: fixed; top: 0; left: 0; right: 0; z-index: 99999;
            background: linear-gradient(135deg, #1a4d7a, #2a6da8);
            color: #fff; padding: 8px 20px;
            display: flex; align-items: center; justify-content: space-between;
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            box-shadow: 0 2px 8px rgba(0,0,0,.15);
        }
        .preview-banner strong { color: #d4a574; }
        .preview-banner a { color: #d4a574; text-decoration: none; margin-left: 12px; }
        .preview-banner a:hover { text-decoration: underline; }
        body { padding-top: 40px; }
    </style>
</head>
<body>
    <div class="preview-banner">
        <span>
            <i class="fas fa-eye"></i>&nbsp;
            <strong>PREVIEW</strong> — <?= htmlspecialchars($tpl) ?>
        </span>
        <span>
            <a href="javascript:window.parent.postMessage('close-preview','*')"><i class="fas fa-times"></i> Fermer</a>
        </span>
    </div>

    <?php
    // Inclure le template
    try {
        include $templateFile;
    } catch (Throwable $e) {
        echo '<div style="padding:40px;color:#c0392b;font-family:monospace;">';
        echo '<h3>Erreur dans le template</h3>';
        echo '<p><strong>' . htmlspecialchars($e->getMessage()) . '</strong></p>';
        echo '<p>Fichier : ' . htmlspecialchars($e->getFile()) . ' ligne ' . $e->getLine() . '</p>';
        echo '</div>';
    }
    ?>

    <script src="/front/assets/js/main.js"></script>
</body>
</html>