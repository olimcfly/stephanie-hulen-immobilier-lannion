<?php
/**
 * Template : t1-accueil.php
 * Affiche la page d'accueil dynamique (Stéphanie Hulen - Lannion)
 * Utilise la classe PageContentT1Accueil pour charger les données
 * 
 * @usage Dans front/templates/pages/t1-accueil.php
 * @version 1.0
 */

// Charger la classe
require_once __DIR__ . '/../../admin/classes/PageContentT1Accueil.php';

// Initialiser la classe avec la connexion DB
$page_content = new PageContentT1Accueil($db);

// Récupérer tout le contenu
$content = $page_content->getAll();

// Récupérer les sections
$hero = $page_content->getSection('hero');
$benefits = $page_content->getSection('benefits');
$method = $page_content->getSection('method');
$guide = $page_content->getSection('guide');
$cta_final = $page_content->getSection('cta');

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendre votre bien à Lannion | Stéphanie Hulen - eXp France</title>
    <meta name="description" content="<?php echo htmlspecialchars($hero['subtitle'] ?? ''); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo site_url('front/css/t1-accueil.css'); ?>">
</head>
<body>

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section id="hero" class="hero-landing">
    <div class="hero-landing-inner">
        
        <p class="hero-subtitle">
            <?php echo htmlspecialchars($hero['eyebrow'] ?? ''); ?>
        </p>

        <h1 class="hero-landing-title">
            <?php echo htmlspecialchars($hero['title'] ?? ''); ?>
        </h1>

        <p class="hero-landing-description">
            <?php echo htmlspecialchars($hero['subtitle'] ?? ''); ?>
        </p>

        <div class="hero-landing-boxes">
            <div class="landing-box">
                <div class="landing-box-icon">🎯</div>
                <h3><?php echo htmlspecialchars($benefits['ben1_title'] ?? ''); ?></h3>
                <p><?php echo htmlspecialchars($benefits['ben1_text'] ?? ''); ?></p>
            </div>
            <div class="landing-box">
                <div class="landing-box-icon">🤝</div>
                <h3><?php echo htmlspecialchars($benefits['ben2_title'] ?? ''); ?></h3>
                <p><?php echo htmlspecialchars($benefits['ben2_text'] ?? ''); ?></p>
            </div>
        </div>

        <a href="<?php echo $hero['cta_url'] ?? '#section-contact'; ?>" class="hero-landing-cta">
            <?php echo htmlspecialchars($hero['cta_text'] ?? 'Recevoir mon avis de valeur gratuit'); ?>
        </a>
    </div>
</section>


<!-- ============================================================
     BÉNÉFICES / PREUVES
     ============================================================ -->
<section id="section-benefits" class="section-beige">
    <div class="container">

        <div class="text-center section-header">
            <span class="section-badge">Votre allié</span>
            <h2 class="section-title">
                <?php echo htmlspecialchars($benefits['title'] ?? ''); ?>
            </h2>
            <p class="section-subtitle">
                Je m'appelle <strong>Stéphanie Hulen</strong> et j'accompagne les vendeurs lannionais et du Trégor
                pour obtenir le meilleur prix dans les meilleurs délais, avec l'appui du réseau <strong>eXp France</strong>.
            </p>
        </div>

        <div class="cards-wrapper cards-3">

            <div class="card card--preuve">
                <div class="card-icon">🎯</div>
                <h3><?php echo htmlspecialchars($benefits['ben1_title'] ?? ''); ?></h3>
                <p><?php echo htmlspecialchars($benefits['ben1_text'] ?? ''); ?></p>
            </div>

            <div class="card card--preuve">
                <div class="card-icon">🔍</div>
                <h3><?php echo htmlspecialchars($benefits['ben2_title'] ?? ''); ?></h3>
                <p><?php echo htmlspecialchars($benefits['ben2_text'] ?? ''); ?></p>
            </div>

            <div class="card card--preuve">
                <div class="card-icon">🛡️</div>
                <h3><?php echo htmlspecialchars($benefits['ben3_title'] ?? ''); ?></h3>
                <p><?php echo htmlspecialchars($benefits['ben3_text'] ?? ''); ?></p>
            </div>

        </div>
    </div>
</section>


<!-- ============================================================
     MÉTHODE EN 4 ÉTAPES
     ============================================================ -->
<section id="section-methodologie" class="section-white">
    <div class="container">

        <div class="text-center section-header">
            <span class="section-badge">La méthode</span>
            <h2 class="section-title">
                <?php echo htmlspecialchars($method['title'] ?? ''); ?>
            </h2>
            <p class="section-subtitle">De l'estimation à la signature, chaque étape est maîtrisée pour maximiser votre résultat.</p>
        </div>

        <div class="steps-wrapper">

            <div class="step-card">
                <div class="step-number">1</div>
                <h3><?php echo htmlspecialchars($method['step1_title'] ?? ''); ?></h3>
                <p><?php echo htmlspecialchars($method['step1_text'] ?? ''); ?></p>
            </div>

            <div class="step-card">
                <div class="step-number">2</div>
                <h3><?php echo htmlspecialchars($method['step2_title'] ?? ''); ?></h3>
                <p><?php echo htmlspecialchars($method['step2_text'] ?? ''); ?></p>
            </div>

            <div class="step-card">
                <div class="step-number">3</div>
                <h3>Visites & négociation</h3>
                <p>Qualification des acheteurs, organisation des visites et négociation du meilleur prix pour vous.</p>
            </div>

            <div class="step-card">
                <div class="step-number">4</div>
                <h3>Compromis & signature</h3>
                <p>Rédaction du compromis, suivi du financement et accompagnement jusqu'à la signature chez le notaire.</p>
            </div>

        </div>

        <div class="text-center methodology-footer">
            <p class="methodology-result">
                <strong>Résultat :</strong> vente au juste prix, dans les meilleurs délais, en toute sérénité.
            </p>
            <a href="#section-contact" class="cta-btn">Démarrer mon estimation gratuite</a>
        </div>

    </div>
</section>


<!-- ============================================================
     GUIDE PRATIQUE : CONTENU SEO
     ============================================================ -->
<section id="section-guide" class="section-guide">
    <div class="container">

        <div class="text-center section-header">
            <span class="section-badge">Guide pratique</span>
            <h2 class="section-title">
                <?php echo htmlspecialchars($guide['title'] ?? ''); ?>
            </h2>
        </div>

        <div class="guide-cards">

            <article class="guide-card">
                <div class="guide-card-number">01</div>
                <div class="guide-card-content">
                    <h3><?php echo htmlspecialchars($guide['g1_title'] ?? ''); ?></h3>
                    <p><?php echo $guide['g1_text'] ?? ''; ?></p>
                </div>
            </article>

            <article class="guide-card">
                <div class="guide-card-number">02</div>
                <div class="guide-card-content">
                    <h3><?php echo htmlspecialchars($guide['g2_title'] ?? ''); ?></h3>
                    <p><?php echo $guide['g2_text'] ?? ''; ?></p>
                </div>
            </article>

            <article class="guide-card">
                <div class="guide-card-number">03</div>
                <div class="guide-card-content">
                    <h3><?php echo htmlspecialchars($guide['g3_title'] ?? ''); ?></h3>
                    <p><?php echo $guide['g3_text'] ?? ''; ?></p>
                </div>
            </article>

        </div>

    </div>
</section>


<!-- ============================================================
     CTA FINALE
     ============================================================ -->
<section id="section-contact" class="cta-final">
    <div class="container">
        <div class="cta-final-inner">
            <h2><?php echo htmlspecialchars($cta_final['title'] ?? ''); ?></h2>
            <p class="cta-description">
                <?php echo htmlspecialchars($cta_final['text'] ?? ''); ?>
            </p>
            <a href="/estimation" class="cta-btn cta-btn-large">
                <?php echo htmlspecialchars($cta_final['btn_text'] ?? 'Recevoir mon estimation gratuite'); ?>
            </a>
            <p class="urgency-note">⏱️ Réponse sous 24h · 100% gratuit · Sans engagement</p>
        </div>
    </div>
</section>


<!-- Schema.org -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Service",
    "name": "Vente immobilière Lannion",
    "description": "Accompagnement personnalisé pour la vente d'un bien immobilier à Lannion et en Trégor : estimation, diffusion, négociation, sécurisation juridique.",
    "provider": {
        "@type": "RealEstateAgent",
        "name": "Stéphanie Hulen",
        "areaServed": {
            "@type": "City",
            "name": "Lannion"
        },
        "memberOf": {
            "@type": "Organization",
            "name": "eXp France"
        }
    },
    "areaServed": {
        "@type": "City",
        "name": "Lannion",
        "containedInPlace": {
            "@type": "AdministrativeArea",
            "name": "Côtes-d'Armor"
        }
    },
    "serviceType": "Accompagnement vente immobilière"
}
</script>

<!-- CSS (shared avec Eduardo) -->
<link rel="stylesheet" href="<?php echo site_url('front/css/t1-accueil-shared.css'); ?>">

</body>
</html>