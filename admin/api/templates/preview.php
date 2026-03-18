<?php
/**
 * /admin/api/templates/preview.php
 * 
 * Charge et rend un template PHP avec les données injectées.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

// ────────────────────────────────────────────────────
// 1️⃣ VALIDATION
// ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid JSON']));
}

$templateId = $input['template'] ?? '';
$fields = $input['fields'] ?? [];

// Whitelist des templates
$validTemplates = ['t1-accueil', 't2-vendre', 't3-acheter', 't4-investir'];
if (!in_array($templateId, $validTemplates)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid template: ' . $templateId]));
}

// ────────────────────────────────────────────────────
// 2️⃣ CHARGER LE TEMPLATE
// ────────────────────────────────────────────────────
$rootPath = '/home/cool1933/public_html';
$templatePath = $rootPath . '/front/templates/pages/tpl/' . $templateId . '.php';

if (!file_exists($templatePath)) {
    http_response_code(404);
    exit(json_encode(['error' => 'Template not found: ' . $templatePath]));
}

// ────────────────────────────────────────────────────
// 3️⃣ PRÉPARER LES VARIABLES - Générer des données placeholder
// ────────────────────────────────────────────────────
require_once '/home/cool1933/public_html/config/config.php';

$pdo = getDB();
$site = ['name' => SITE_TITLE, 'city' => 'Lannion'];
$advisor = [
    'name' => 'Stéphanie Hulen',
    'city' => 'Lannion',
    'phone' => '02 XX XX XX XX',
    'network' => 'eXp France',
    'avatar' => null
];

// ── Générer les données placeholder ──
// Si les champs ne sont pas fournis, utiliser les defaults du schema
$fieldsSchema = [
    't1-accueil' => [
        'hero_eyebrow' => 'Conseiller immobilier à Lannion',
        'hero_title' => 'Votre expert immobilier de confiance',
        'hero_subtitle' => 'Estimation gratuite, accompagnement personnalisé, résultats concrets.',
        'hero_cta_text' => 'Estimer mon bien gratuitement',
        'hero_cta_url' => '/estimation',
        'hero_cta2_text' => 'Découvrir mes services',
        'hero_cta2_url' => '/vendre',
        'hero_stat1_num' => '98%',
        'hero_stat1_lbl' => 'clients satisfaits',
        'hero_stat2_num' => '45j',
        'hero_stat2_lbl' => 'délai moyen de vente',
        'hero_stat3_num' => '12+',
        'hero_stat3_lbl' => 'années d\'expérience',
        'ben_title' => 'Pourquoi choisir un conseiller local ?',
        'ben1_icon' => '📍',
        'ben1_title' => 'Expertise locale',
        'ben1_text' => 'Connaissance approfondie des quartiers et du marché de Lannion.',
        'ben2_icon' => '🤝',
        'ben2_title' => 'Accompagnement personnalisé',
        'ben2_text' => 'Un seul interlocuteur du début à la fin. Disponible 7j/7.',
        'ben3_icon' => '🏆',
        'ben3_title' => 'Réseau eXp France',
        'ben3_text' => 'Accès à des outils digitaux exclusifs et un réseau national d\'acheteurs.',
        'pres_title' => 'Stéphanie Hulen',
        'pres_sub' => 'Conseiller indépendant eXp France — Spécialiste à Lannion',
        'pres_text' => '<p>Passionné par l\'immobilier et profondément attaché à Lannion, j\'ai choisi d\'exercer de façon indépendante pour vous offrir un service sur-mesure.</p>',
        'pres_cta_text' => 'En savoir plus sur moi',
        'pres_cta_url' => '/a-propos',
        'pres_tag1' => '✓ Conseiller certifié',
        'pres_tag2' => '✓ 100% local',
        'pres_tag3' => '✓ eXp France',
        'exp_title' => 'Mon expertise à votre service',
        'exp1_icon' => '🏠',
        'exp1_title' => 'Vente immobilière',
        'exp1_text' => 'Estimation, stratégie, photos pro, diffusion tous portails.',
        'exp1_link' => '/vendre',
        'exp2_icon' => '🔑',
        'exp2_title' => 'Achat immobilier',
        'exp2_text' => 'Sélection, visites, négociation, suivi jusqu\'à la signature.',
        'exp2_link' => '/acheter',
        'exp3_icon' => '📈',
        'exp3_title' => 'Investissement locatif',
        'exp3_text' => 'Analyse de rentabilité, sélection des biens à fort potentiel.',
        'exp3_link' => '/investir',
        'method_title' => 'Comment je travaille',
        'step1_num' => '01',
        'step1_title' => 'Premier contact gratuit',
        'step1_text' => 'Échange de 30 minutes pour comprendre votre projet.',
        'step2_num' => '02',
        'step2_title' => 'Stratégie sur-mesure',
        'step2_text' => 'Analyse du marché local, estimation, plan d\'action.',
        'step3_num' => '03',
        'step3_title' => 'Accompagnement jusqu\'au bout',
        'step3_text' => 'Suivi personnalisé jusqu\'à la signature chez le notaire.',
        'method_cta_text' => 'Prendre rendez-vous',
        'method_cta_url' => '/contact',
        'guide_title' => 'Tout savoir sur l\'immobilier à Lannion',
        'g1_num' => '01',
        'g1_title' => 'Le marché immobilier à Lannion en 2026',
        'g1_text' => '<p>Le marché immobilier de Lannion présente des caractéristiques uniques avec une demande croissante.</p>',
        'g2_num' => '02',
        'g2_title' => 'Vendre ou acheter : par où commencer ?',
        'g2_text' => '<p>La première étape est de bien définir ses objectifs et son budget réaliste.</p>',
        'g3_num' => '03',
        'g3_title' => 'Pourquoi choisir un conseiller indépendant ?',
        'g3_text' => '<p>Contrairement aux agences traditionnelles, je travaille pour vous et uniquement pour vous.</p>',
        'cta_title' => 'Votre projet immobilier commence ici',
        'cta_text' => 'Estimation gratuite, conseil personnalisé, zéro engagement.',
        'cta_btn_text' => 'Je veux une estimation gratuite',
        'cta_btn_url' => '/estimation',
        'cta_phone_text' => 'Ou appelez-moi directement',
    ]
];

// Fusionner les champs fournis avec les placeholders
$defaultFields = $fieldsSchema[$templateId] ?? [];
$fields = array_merge($defaultFields, $fields);

// ────────────────────────────────────────────────────
// 3.5️⃣ DÉTECTER ET CHARGER LE CSS DU TEMPLATE
// ────────────────────────────────────────────────────
$cssContent = '';
$templateDir = dirname($templatePath); // /home/cool1933/public_html/front/templates/pages
$cssDirPath = $templateDir . '/css';  // /home/cool1933/public_html/front/templates/pages/css

// Ordre de recherche :
// 1. CSS dans le dossier /css/ avec le même nom que le template
// 2. Tous les CSS du dossier /css/
// 3. CSS à la racine du template (fallback)

$cssPaths = [
    $cssDirPath . '/' . $templateId . '.css',                               // css/t1-accueil.css
    $templateDir . '/' . $templateId . '.css',                              // t1-accueil.css (fallback)
    dirname($templateDir) . '/css/' . $templateId . '.css',                 // ../css/t1-accueil.css
];

foreach ($cssPaths as $cssPath) {
    if (file_exists($cssPath)) {
        $cssContent = file_get_contents($cssPath);
        break;
    }
}

// Fallback : charger tous les CSS du dossier /css si aucun spécifique trouvé
if (empty($cssContent) && is_dir($cssDirPath)) {
    $cssFiles = glob($cssDirPath . '/*.css');
    foreach ($cssFiles as $file) {
        $cssContent .= file_get_contents($file) . "\n\n";
    }
}

// ────────────────────────────────────────────────────
// 4️⃣ RENDRE LE TEMPLATE
// ────────────────────────────────────────────────────
ob_start();

try {
    // Injecter les variables
    $page = $templateId;
    $editMode = false;
    $headerData = null;
    $footerData = null;
    
    // Charger le template (qui lui-même utilise ob_start())
    include $templatePath;
    
    $html = ob_get_clean();
    
    // ✅ Succès - SEULE réponse retournée avec CSS
    echo json_encode([
        'success' => true,
        'html' => $html,
        'template' => $templateId,
        'css' => $cssContent
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'error' => 'Template rendering failed',
        'message' => $e->getMessage()
    ]);
}
?>