<?php
/**
 * PAGE PUBLIQUE : SECTEUR INDIVIDUEL (SINGLE)
 * Fichier : /secteur-single.php
 * 
 * Appelé par le routeur .htaccess quand on accède à /bacalan-bordeaux, 
 * /chartrons-bordeaux, etc.
 * 
 * Affiche toutes les sections : hero, présentation, atouts, marché,
 * profils cibles, conseils, FAQ, secteurs liés
 * 
 * v3.0 - Rendu complet depuis la table `secteurs`
 */

// ─── INIT ───
$rootPath = defined('ROOT_PATH') ? ROOT_PATH : $_SERVER['DOCUMENT_ROOT'];

// Charger Database
$dbPaths = [
    $rootPath . '/includes/classes/Database.php',
    $rootPath . '/includes/Database.php',
    dirname(__DIR__) . '/includes/classes/Database.php',
];
$dbLoaded = false;
foreach ($dbPaths as $path) {
    if (file_exists($path)) { require_once $path; $dbLoaded = true; break; }
}
if (!$dbLoaded) { http_response_code(500); die('Erreur serveur.'); }

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    http_response_code(500);
    die('Erreur de connexion.');
}

// ─── RÉCUPÉRER LE SLUG ───
$slug = $_GET['slug'] ?? '';
$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug)));

if (empty($slug)) {
    http_response_code(404);
    header('Location: /secteurs');
    exit;
}

// ─── CHARGER LE SECTEUR ───
try {
    $stmt = $db->prepare("SELECT * FROM secteurs WHERE slug = ? AND status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    $s = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur secteur single: " . $e->getMessage());
    $s = null;
}

if (!$s) {
    http_response_code(404);
    // Inclure la page 404
    $headerPaths = [
        $rootPath . '/public/includes/header.php',
        $rootPath . '/includes/header.php',
    ];
    foreach ($headerPaths as $hp) { if (file_exists($hp)) { include $hp; break; } }
    echo '<div style="text-align:center;padding:80px 20px;">
        <h1 style="font-size:48px;color:#ccc;margin-bottom:16px;">404</h1>
        <h2>Quartier non trouvé</h2>
        <p style="color:#777;margin:16px 0;">Ce quartier n\'existe pas ou n\'est plus disponible.</p>
        <a href="/secteurs" style="display:inline-block;padding:12px 24px;background:#e67e22;color:white;border-radius:8px;text-decoration:none;font-weight:600;">Voir tous les quartiers</a>
    </div>';
    $footerPaths = [
        $rootPath . '/public/includes/footer.php',
        $rootPath . '/includes/footer.php',
    ];
    foreach ($footerPaths as $fp) { if (file_exists($fp)) { include $fp; break; } }
    exit;
}

// ─── DÉCODER LES CHAMPS JSON ───
function jsonDecode($val) {
    if (empty($val)) return [];
    $decoded = json_decode($val, true);
    return is_array($decoded) ? $decoded : [];
}

$presentation = jsonDecode($s['presentation']);
$atouts = jsonDecode($s['atouts']);
$marcheDesc = jsonDecode($s['marche_description']);
$profilsCibles = jsonDecode($s['profils_cibles']);
$galerie = jsonDecode($s['galerie']);
$conseils = jsonDecode($s['conseils']);
$faq = jsonDecode($s['faq']);
$secteursLies = jsonDecode($s['secteurs_lies']);

// ─── SEO ───
$pageTitle = $s['meta_title'] ?: $s['nom'] . ' - Quartier de ' . ($s['ville'] ?? 'Bordeaux');
$pageDescription = $s['meta_description'] ?: 'Découvrez ' . $s['nom'] . ' à ' . ($s['ville'] ?? 'Bordeaux') . ' : prix immobilier, cadre de vie, conseils.';
$pageKeywords = $s['meta_keywords'] ?? '';
$ogImage = $s['og_image'] ?: ($s['hero_image'] ?? '');
$canonicalUrl = $s['canonical_url'] ?: '/' . $s['slug'];
$metaRobots = $s['meta_robots'] ?? 'index, follow';

// ─── CHARGER LES SECTEURS LIÉS ───
$relatedSecteurs = [];
if (!empty($secteursLies)) {
    $placeholders = implode(',', array_fill(0, count($secteursLies), '?'));
    try {
        $stmtRel = $db->prepare("SELECT id, nom, slug, ville, type_secteur, hero_image, prix_min, prix_max, code_postal FROM secteurs WHERE slug IN ($placeholders) AND status = 'published'");
        $stmtRel->execute($secteursLies);
        $relatedSecteurs = $stmtRel->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { /* silencieux */ }
}

// ─── HEADER ───
$headerPaths = [
    $rootPath . '/public/includes/header.php',
    $rootPath . '/includes/header.php',
];
$headerLoaded = false;
foreach ($headerPaths as $hp) {
    if (file_exists($hp)) { include $hp; $headerLoaded = true; break; }
}
if (!$headerLoaded) {
    echo '<!DOCTYPE html><html lang="fr"><head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($pageTitle) . '</title>
    <meta name="description" content="' . htmlspecialchars($pageDescription) . '">
    <meta name="robots" content="' . htmlspecialchars($metaRobots) . '">
    <link rel="canonical" href="https://eduardo-desul-immobilier.fr' . htmlspecialchars($canonicalUrl) . '">
    ' . ($ogImage ? '<meta property="og:image" content="' . htmlspecialchars($ogImage) . '">' : '') . '
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    </head><body>';
}
?>

<style>
/* ══════════════════════════════════════════════════════════════
   PAGE SECTEUR SINGLE - Frontend Public
   ══════════════════════════════════════════════════════════════ */

:root {
    --sp-primary: #1a1a2e;
    --sp-accent: #e67e22;
    --sp-accent-dark: #d35400;
    --sp-text: #2c3e50;
    --sp-text-light: #7f8c8d;
    --sp-bg: #fafaf8;
    --sp-white: #ffffff;
    --sp-border: #ecf0f1;
    --sp-radius: 12px;
    --font-display: 'Playfair Display', Georgia, serif;
    --font-body: 'DM Sans', -apple-system, sans-serif;
}

.sp-page { font-family: var(--font-body); color: var(--sp-text); }

/* ─── HERO ─── */
.sp-hero {
    position: relative;
    height: 480px;
    display: flex;
    align-items: flex-end;
    overflow: hidden;
    color: white;
}

.sp-hero__bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    background-color: var(--sp-primary);
}

.sp-hero__bg::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(0,0,0,0.75) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0.1) 100%);
}

.sp-hero__inner {
    position: relative;
    z-index: 2;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px 48px;
}

.sp-hero__breadcrumb {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    margin-bottom: 20px;
    opacity: 0.8;
}

.sp-hero__breadcrumb a { color: white; text-decoration: none; }
.sp-hero__breadcrumb a:hover { text-decoration: underline; }
.sp-hero__breadcrumb span { opacity: 0.5; }

.sp-hero__badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 12px;
}

.sp-hero__badge.quartier { background: rgba(230,126,34,0.9); }
.sp-hero__badge.commune { background: rgba(142,68,173,0.9); }

.sp-hero__title {
    font-family: var(--font-display);
    font-size: clamp(28px, 4.5vw, 46px);
    font-weight: 700;
    line-height: 1.15;
    margin-bottom: 12px;
    text-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.sp-hero__subtitle {
    font-size: 17px;
    max-width: 700px;
    line-height: 1.6;
    opacity: 0.9;
    margin-bottom: 24px;
}

.sp-hero__ctas {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.sp-hero__cta {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: 50px;
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s;
}

.sp-hero__cta--primary {
    background: var(--sp-accent);
    color: white;
    box-shadow: 0 4px 15px rgba(230,126,34,0.3);
}

.sp-hero__cta--primary:hover {
    background: var(--sp-accent-dark);
    transform: translateY(-2px);
}

.sp-hero__cta--secondary {
    background: rgba(255,255,255,0.15);
    color: white;
    border: 2px solid rgba(255,255,255,0.4);
    backdrop-filter: blur(5px);
}

.sp-hero__cta--secondary:hover {
    background: rgba(255,255,255,0.25);
}

/* ─── CONTAINER ─── */
.sp-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 24px;
}

/* ─── SECTION GÉNÉRIQUE ─── */
.sp-section {
    padding: 64px 0;
}

.sp-section--alt { background: var(--sp-bg); }

.sp-section__header {
    text-align: center;
    margin-bottom: 40px;
}

.sp-section__label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 700;
    color: var(--sp-accent);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 10px;
}

.sp-section__title {
    font-family: var(--font-display);
    font-size: 30px;
    font-weight: 700;
    color: var(--sp-primary);
    margin-bottom: 10px;
}

.sp-section__subtitle {
    font-size: 16px;
    color: var(--sp-text-light);
    max-width: 600px;
    margin: 0 auto;
}

/* ─── STATS BAR ─── */
.sp-stats-bar {
    background: var(--sp-white);
    border-bottom: 1px solid var(--sp-border);
    padding: 24px 0;
}

.sp-stats-bar__inner {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
}

.sp-stat {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--sp-bg);
    border-radius: 10px;
}

.sp-stat__icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}

.sp-stat__value { font-size: 16px; font-weight: 700; color: var(--sp-primary); line-height: 1.2; }
.sp-stat__label { font-size: 11px; color: var(--sp-text-light); text-transform: uppercase; }

/* ─── PRÉSENTATION ─── */
.sp-presentation { column-count: 2; column-gap: 40px; }
.sp-presentation p { margin-bottom: 16px; line-height: 1.7; font-size: 15px; color: var(--sp-text); break-inside: avoid; }

@media (max-width: 768px) { .sp-presentation { column-count: 1; } }

/* ─── ATOUTS GRID ─── */
.sp-atouts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.sp-atout {
    display: flex;
    gap: 14px;
    padding: 20px;
    background: var(--sp-white);
    border-radius: var(--sp-radius);
    border: 1px solid var(--sp-border);
    transition: all 0.3s;
}

.sp-atout:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    border-color: var(--sp-accent);
}

.sp-atout__icon { font-size: 28px; flex-shrink: 0; line-height: 1; }
.sp-atout__title { font-size: 15px; font-weight: 700; color: var(--sp-primary); margin-bottom: 4px; }
.sp-atout__desc { font-size: 13px; color: var(--sp-text-light); line-height: 1.5; }

/* ─── MARCHÉ IMMOBILIER ─── */
.sp-marche-content { max-width: 800px; margin: 0 auto; }
.sp-marche-content p { margin-bottom: 16px; line-height: 1.7; font-size: 15px; color: var(--sp-text); }

/* ─── PROFILS CIBLES ─── */
.sp-profils-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 24px;
}

.sp-profil {
    padding: 24px;
    background: var(--sp-white);
    border-radius: var(--sp-radius);
    border: 1px solid var(--sp-border);
    text-align: center;
}

.sp-profil__icon { font-size: 36px; margin-bottom: 12px; }
.sp-profil__title { font-family: var(--font-display); font-size: 18px; font-weight: 700; color: var(--sp-primary); margin-bottom: 8px; }
.sp-profil__desc { font-size: 14px; color: var(--sp-text-light); line-height: 1.6; }

/* ─── CONSEILS ─── */
.sp-conseils-list { max-width: 700px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }

.sp-conseil {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 16px;
    background: var(--sp-white);
    border-radius: 10px;
    border: 1px solid var(--sp-border);
}

.sp-conseil__icon { font-size: 20px; flex-shrink: 0; line-height: 1.4; }
.sp-conseil__text { font-size: 14px; color: var(--sp-text); line-height: 1.6; }

/* ─── FAQ ─── */
.sp-faq-list { max-width: 800px; margin: 0 auto; display: flex; flex-direction: column; gap: 12px; }

.sp-faq {
    background: var(--sp-white);
    border-radius: var(--sp-radius);
    border: 1px solid var(--sp-border);
    overflow: hidden;
}

.sp-faq__q {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 18px 20px;
    cursor: pointer;
    font-size: 15px;
    font-weight: 600;
    color: var(--sp-primary);
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    font-family: var(--font-body);
    transition: background 0.2s;
}

.sp-faq__q:hover { background: var(--sp-bg); }
.sp-faq__q i { transition: transform 0.3s; color: var(--sp-accent); }
.sp-faq.open .sp-faq__q i { transform: rotate(180deg); }

.sp-faq__a {
    padding: 0 20px;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
}

.sp-faq.open .sp-faq__a { max-height: 300px; padding: 0 20px 18px; }

.sp-faq__a p { font-size: 14px; color: var(--sp-text-light); line-height: 1.7; }

/* ─── SECTEURS LIÉS ─── */
.sp-related-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 20px;
}

.sp-related-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    background: var(--sp-white);
    border-radius: var(--sp-radius);
    border: 1px solid var(--sp-border);
    text-decoration: none;
    color: inherit;
    transition: all 0.3s;
}

.sp-related-card:hover {
    border-color: var(--sp-accent);
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
}

.sp-related-card__img {
    width: 64px;
    height: 48px;
    border-radius: 8px;
    background-size: cover;
    background-position: center;
    background-color: #e8e8e8;
    flex-shrink: 0;
}

.sp-related-card__name { font-weight: 700; font-size: 14px; color: var(--sp-primary); }
.sp-related-card__info { font-size: 12px; color: var(--sp-text-light); }

/* ─── CONTENT HTML (Builder) ─── */
.sp-content { max-width: 800px; margin: 0 auto; }
.sp-content h2 { font-family: var(--font-display); font-size: 26px; font-weight: 700; color: var(--sp-primary); margin: 32px 0 16px; }
.sp-content h3 { font-size: 20px; font-weight: 700; color: var(--sp-primary); margin: 24px 0 12px; }
.sp-content p { margin-bottom: 16px; line-height: 1.7; font-size: 15px; }
.sp-content img { max-width: 100%; border-radius: 10px; margin: 16px 0; }
.sp-content ul, .sp-content ol { margin-bottom: 16px; padding-left: 24px; }
.sp-content li { margin-bottom: 6px; line-height: 1.6; }

/* ─── CTA ─── */
.sp-bottom-cta {
    background: linear-gradient(135deg, var(--sp-primary), #0f3460);
    padding: 64px 24px;
    text-align: center;
    color: white;
}

.sp-bottom-cta__title { font-family: var(--font-display); font-size: 30px; font-weight: 700; margin-bottom: 12px; }
.sp-bottom-cta__text { font-size: 16px; opacity: 0.8; margin-bottom: 28px; max-width: 500px; margin-left: auto; margin-right: auto; line-height: 1.6; }

.sp-bottom-cta__btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 16px 36px;
    background: var(--sp-accent);
    color: white;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s;
}

.sp-bottom-cta__btn:hover { background: var(--sp-accent-dark); transform: translateY(-2px); }

/* ─── RESPONSIVE ─── */
@media (max-width: 768px) {
    .sp-hero { height: 400px; }
    .sp-hero__inner { padding-bottom: 32px; }
    .sp-hero__ctas { flex-direction: column; }
    .sp-hero__cta { justify-content: center; }
    .sp-stats-bar__inner { grid-template-columns: repeat(2, 1fr); }
    .sp-atouts-grid { grid-template-columns: 1fr; }
    .sp-profils-grid { grid-template-columns: 1fr; }
}
</style>

<div class="sp-page">

<!-- ══════════════════════════════════════════════════════════════
     HERO
     ══════════════════════════════════════════════════════════════ -->
<section class="sp-hero">
    <div class="sp-hero__bg" style="<?= !empty($s['hero_image']) ? "background-image: url('" . htmlspecialchars($s['hero_image']) . "')" : '' ?>"></div>
    <div class="sp-hero__inner">
        <nav class="sp-hero__breadcrumb" aria-label="Fil d'Ariane">
            <a href="/">Accueil</a>
            <span>›</span>
            <a href="/secteurs">Quartiers</a>
            <span>›</span>
            <span><?= htmlspecialchars($s['nom']) ?></span>
        </nav>
        
        <span class="sp-hero__badge <?= $s['type_secteur'] ?? 'quartier' ?>">
            <i class="fas fa-<?= ($s['type_secteur'] ?? 'quartier') === 'commune' ? 'city' : 'map-pin' ?>"></i>
            <?= ucfirst($s['type_secteur'] ?? 'quartier') ?> · <?= htmlspecialchars($s['ville'] ?? 'Bordeaux') ?>
        </span>
        
        <h1 class="sp-hero__title"><?= htmlspecialchars($s['hero_title'] ?: $s['nom']) ?></h1>
        
        <?php if (!empty($s['hero_subtitle'])): ?>
        <p class="sp-hero__subtitle"><?= htmlspecialchars($s['hero_subtitle']) ?></p>
        <?php endif; ?>
        
        <div class="sp-hero__ctas">
            <?php
            $cta1Text = $s['hero_cta1_text'] ?? $s['hero_cta_text'] ?? 'Voir les prix du marché';
            $cta1Link = $s['hero_cta1_link'] ?? $s['hero_cta_url'] ?? '#prix-marche';
            $cta2Text = $s['hero_cta2_text'] ?? 'Estimer mon bien';
            $cta2Link = $s['hero_cta2_link'] ?? '/estimation';
            ?>
            <a href="<?= htmlspecialchars($cta1Link) ?>" class="sp-hero__cta sp-hero__cta--primary">
                <i class="fas fa-chart-line"></i> <?= htmlspecialchars($cta1Text) ?>
            </a>
            <a href="<?= htmlspecialchars($cta2Link) ?>" class="sp-hero__cta sp-hero__cta--secondary">
                <i class="fas fa-calculator"></i> <?= htmlspecialchars($cta2Text) ?>
            </a>
        </div>
    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     BARRE DE STATS
     ══════════════════════════════════════════════════════════════ -->
<div class="sp-stats-bar">
    <div class="sp-container">
        <div class="sp-stats-bar__inner">
            <?php if (!empty($s['prix_min']) && !empty($s['prix_max'])): ?>
            <div class="sp-stat">
                <div class="sp-stat__icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-euro-sign"></i></div>
                <div>
                    <div class="sp-stat__value"><?= number_format($s['prix_min'],0,',',' ') ?> - <?= number_format($s['prix_max'],0,',',' ') ?> €/m²</div>
                    <div class="sp-stat__label">Prix immobilier</div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($s['rendement_min']) && !empty($s['rendement_max'])): ?>
            <div class="sp-stat">
                <div class="sp-stat__icon" style="background:#d1fae5;color:#059669;"><i class="fas fa-chart-line"></i></div>
                <div>
                    <div class="sp-stat__value"><?= number_format($s['rendement_min'],1,',','') ?>% - <?= number_format($s['rendement_max'],1,',','') ?>%</div>
                    <div class="sp-stat__label">Rendement locatif</div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($s['evolution_prix'])): ?>
            <div class="sp-stat">
                <div class="sp-stat__icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-arrow-trend-up"></i></div>
                <div>
                    <div class="sp-stat__value"><?= htmlspecialchars($s['evolution_prix']) ?></div>
                    <div class="sp-stat__label">Évolution</div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($s['delai_vente'])): ?>
            <div class="sp-stat">
                <div class="sp-stat__icon" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-clock"></i></div>
                <div>
                    <div class="sp-stat__value"><?= htmlspecialchars($s['delai_vente']) ?></div>
                    <div class="sp-stat__label">Délai moyen de vente</div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     PRÉSENTATION
     ══════════════════════════════════════════════════════════════ -->
<?php if (!empty($presentation)): ?>
<section class="sp-section">
    <div class="sp-container">
        <div class="sp-section__header">
            <span class="sp-section__label"><i class="fas fa-info-circle"></i> Présentation</span>
            <h2 class="sp-section__title">Découvrez <?= htmlspecialchars($s['nom']) ?></h2>
        </div>
        <div class="sp-presentation">
            <?php foreach ($presentation as $para): ?>
            <p><?= htmlspecialchars($para) ?></p>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════
     ATOUTS
     ══════════════════════════════════════════════════════════════ -->
<?php if (!empty($atouts)): ?>
<section class="sp-section sp-section--alt">
    <div class="sp-container">
        <div class="sp-section__header">
            <span class="sp-section__label"><i class="fas fa-star"></i> Atouts</span>
            <h2 class="sp-section__title">Les points forts de <?= htmlspecialchars($s['nom']) ?></h2>
        </div>
        <div class="sp-atouts-grid">
            <?php foreach ($atouts as $a): ?>
            <div class="sp-atout">
                <span class="sp-atout__icon"><?= $a['icon'] ?? '✨' ?></span>
                <div>
                    <h3 class="sp-atout__title"><?= htmlspecialchars($a['title'] ?? '') ?></h3>
                    <p class="sp-atout__desc"><?= htmlspecialchars($a['description'] ?? '') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════
     MARCHÉ IMMOBILIER
     ══════════════════════════════════════════════════════════════ -->
<?php if (!empty($marcheDesc)): ?>
<section class="sp-section" id="prix-marche">
    <div class="sp-container">
        <div class="sp-section__header">
            <span class="sp-section__label"><i class="fas fa-chart-bar"></i> Marché</span>
            <h2 class="sp-section__title">Le marché immobilier à <?= htmlspecialchars($s['nom']) ?></h2>
        </div>
        <div class="sp-marche-content">
            <?php foreach ($marcheDesc as $para): ?>
            <p><?= htmlspecialchars($para) ?></p>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════
     PROFILS CIBLES
     ══════════════════════════════════════════════════════════════ -->
<?php if (!empty($profilsCibles)): ?>
<section class="sp-section sp-section--alt">
    <div class="sp-container">
        <div class="sp-section__header">
            <span class="sp-section__label"><i class="fas fa-users"></i> Pour qui ?</span>
            <h2 class="sp-section__title">Ce quartier est fait pour vous si...</h2>
        </div>
        <div class="sp-profils-grid">
            <?php foreach ($profilsCibles as $p): ?>
            <div class="sp-profil">
                <div class="sp-profil__icon"><?= $p['icon'] ?? '🎯' ?></div>
                <h3 class="sp-profil__title"><?= htmlspecialchars($p['title'] ?? '') ?></h3>
                <p class="sp-profil__desc"><?= htmlspecialchars($p['description'] ?? '') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════
     CONTENU HTML (Builder Pro ou contenu manuel)
     ══════════════════════════════════════════════════════════════ -->
<?php if (!empty($s['content'])): ?>
<section class="sp-section">
    <div class="sp-container">
        <div class="sp-content">
            <?= $s['content'] ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════
     CONSEILS
     ══════════════════════════════════════════════════════════════ -->
<?php if (!empty($conseils)): ?>
<section class="sp-section sp-section--alt">
    <div class="sp-container">
        <div class="sp-section__header">
            <span class="sp-section__label"><i class="fas fa-lightbulb"></i> Conseils</span>
            <h2 class="sp-section__title">Nos conseils pour <?= htmlspecialchars($s['nom']) ?></h2>
        </div>
        <div class="sp-conseils-list">
            <?php foreach ($conseils as $c): ?>
            <div class="sp-conseil">
                <span class="sp-conseil__icon"><?= $c['icon'] ?? '💡' ?></span>
                <p class="sp-conseil__text"><?= htmlspecialchars($c['text'] ?? '') ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════
     FAQ
     ══════════════════════════════════════════════════════════════ -->
<?php if (!empty($faq)): ?>
<section class="sp-section">
    <div class="sp-container">
        <div class="sp-section__header">
            <span class="sp-section__label"><i class="fas fa-question-circle"></i> FAQ</span>
            <h2 class="sp-section__title">Questions fréquentes sur <?= htmlspecialchars($s['nom']) ?></h2>
        </div>
        <div class="sp-faq-list">
            <?php foreach ($faq as $i => $f): ?>
            <div class="sp-faq<?= $i === 0 ? ' open' : '' ?>">
                <button type="button" class="sp-faq__q" onclick="this.parentElement.classList.toggle('open')">
                    <span><?= htmlspecialchars($f['question'] ?? '') ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="sp-faq__a">
                    <p><?= htmlspecialchars($f['answer'] ?? '') ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════
     SECTEURS LIÉS
     ══════════════════════════════════════════════════════════════ -->
<?php if (!empty($relatedSecteurs)): ?>
<section class="sp-section sp-section--alt">
    <div class="sp-container">
        <div class="sp-section__header">
            <span class="sp-section__label"><i class="fas fa-map-signs"></i> À explorer aussi</span>
            <h2 class="sp-section__title">Quartiers à proximité</h2>
        </div>
        <div class="sp-related-grid">
            <?php foreach ($relatedSecteurs as $r): ?>
            <a href="/<?= htmlspecialchars($r['slug']) ?>" class="sp-related-card">
                <div class="sp-related-card__img" style="<?= !empty($r['hero_image']) ? "background-image:url('" . htmlspecialchars($r['hero_image']) . "')" : '' ?>"></div>
                <div>
                    <div class="sp-related-card__name"><?= htmlspecialchars($r['nom']) ?></div>
                    <div class="sp-related-card__info">
                        <?= htmlspecialchars($r['ville'] ?? '') ?>
                        <?php if (!empty($r['prix_min']) && !empty($r['prix_max'])): ?>
                         · <?= number_format($r['prix_min'],0,',',' ') ?>-<?= number_format($r['prix_max'],0,',',' ') ?> €/m²
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════════════
     CTA BOTTOM
     ══════════════════════════════════════════════════════════════ -->
<section class="sp-bottom-cta">
    <h2 class="sp-bottom-cta__title">Un projet immobilier à <?= htmlspecialchars($s['nom']) ?> ?</h2>
    <p class="sp-bottom-cta__text">
        Eduardo vous accompagne dans votre achat, vente ou investissement 
        à <?= htmlspecialchars($s['nom']) ?> et dans toute la métropole bordelaise.
    </p>
    <a href="/contact" class="sp-bottom-cta__btn">
        <i class="fas fa-phone-alt"></i> Prendre rendez-vous
    </a>
</section>

</div>

<!-- Schema.org - FAQPage -->
<?php if (!empty($faq)): ?>
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        <?php foreach ($faq as $i => $f): ?>
        {
            "@type": "Question",
            "name": "<?= htmlspecialchars($f['question'] ?? '', ENT_QUOTES) ?>",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "<?= htmlspecialchars($f['answer'] ?? '', ENT_QUOTES) ?>"
            }
        }<?= $i < count($faq) - 1 ? ',' : '' ?>
        <?php endforeach; ?>
    ]
}
</script>
<?php endif; ?>

<!-- Schema.org - RealEstateAgent -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "RealEstateAgent",
    "name": "Eduardo De Sul - Conseiller Immobilier",
    "areaServed": {
        "@type": "Place",
        "name": "<?= htmlspecialchars($s['nom']) ?>, <?= htmlspecialchars($s['ville'] ?? 'Bordeaux') ?>"
        <?php if (!empty($s['latitude']) && !empty($s['longitude'])): ?>,
        "geo": {
            "@type": "GeoCoordinates",
            "latitude": <?= $s['latitude'] ?>,
            "longitude": <?= $s['longitude'] ?>
        }
        <?php endif; ?>
    }
}
</script>

<?php
// ─── FOOTER ───
$footerPaths = [
    $rootPath . '/public/includes/footer.php',
    $rootPath . '/includes/footer.php',
];
foreach ($footerPaths as $fp) { if (file_exists($fp)) { include $fp; break; } }
if (!$headerLoaded) echo '</body></html>';
?>