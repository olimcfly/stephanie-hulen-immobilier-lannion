<?php
/**
 * /front/templates/pages/t6-guide.php
 * Template Guide Local — Lannion et le Trégor
 * 3 sections : Infos pratiques, Vivre à Lannion, Immobilier
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
$siteUrl    = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

$advisorName    = $advisor['name']    ?? ($site['name']    ?? 'Votre conseillère');
$advisorCity    = $advisor['city']    ?? ($site['city']    ?? 'Lannion');
$advisorPhone   = $advisor['phone']   ?? '';
$advisorNetwork = $advisor['network'] ?? '';

// ════════════════════════════════════════════════
// CHAMPS ÉDITABLES
// ════════════════════════════════════════════════

$guideTitle = $fields['guide_title'] ?? 'Guide local de Lannion et du Trégor';
$guideIntro = $fields['guide_intro'] ?? 'Tout savoir pour vivre, s\'installer et investir dans le Trégor';

// Section 1 — Infos pratiques
$s1Title     = $fields['section1_title']     ?? 'Infos pratiques';
$s1Subtitle  = $fields['section1_subtitle']  ?? 'Tout ce qu\'il faut savoir pour s\'installer à Lannion';
$s1Mairie    = $fields['section1_mairie']    ?? 'La mairie de Lannion est située place du Général Leclerc, en plein centre-ville. Elle est ouverte du lundi au vendredi de 8h30 à 12h et de 13h30 à 17h30. Vous y trouverez l\'état civil, l\'urbanisme, les services sociaux et le CCAS.';
$s1Transport = $fields['section1_transport'] ?? 'Lannion est desservie par la gare SNCF (ligne TGV Paris–Brest via Guingamp), un réseau de bus urbain TILT, ainsi que l\'aéroport de Lannion pour les liaisons avec Paris-CDG. La ville est à 1h de Brest et 1h30 de Rennes par la voie express.';
$s1Services  = $fields['section1_services']  ?? 'La ville dispose d\'un centre hospitalier, de nombreuses écoles (maternelles, primaires, collèges et lycées), de l\'IUT et de l\'ENSSAT pour l\'enseignement supérieur. Commerces, marchés hebdomadaires (jeudi matin) et grandes surfaces complètent l\'offre de services.';
$s1Demarches = $fields['section1_demarches'] ?? 'Pour vos démarches administratives : Pôle Emploi, CAF, CPAM et Trésor Public sont présents à Lannion. La sous-préfecture se trouve à proximité pour les questions liées aux titres de séjour et aux cartes grises.';

// Section 2 — Vivre à Lannion
$s2Title     = $fields['section2_title']     ?? 'Vivre à Lannion';
$s2Subtitle  = $fields['section2_subtitle']  ?? 'Une qualité de vie exceptionnelle entre terre et mer';
$s2Ambiance  = $fields['section2_ambiance']  ?? 'Lannion séduit par son centre historique aux maisons à colombages, ses ruelles pavées et ses escaliers de Brélévenez. Ville à taille humaine (environ 20 000 habitants), elle offre un cadre de vie paisible tout en étant un pôle économique dynamique grâce à la « Télécom Valley » bretonne.';
$s2Culture   = $fields['section2_culture']   ?? 'La vie culturelle est riche : le Carré Magique (scène nationale), le cinéma Les Baladins, la médiathèque et de nombreuses associations. Lannion accueille chaque été le festival des Tardives et propose une programmation culturelle variée tout au long de l\'année.';
$s2Events    = $fields['section2_events']    ?? 'Parmi les événements incontournables : les Jeudis de Lannion (marchés d\'été animés), la fête de la Saint-Patrick, le festival Peut-être, les concerts sur les places et les marchés de Noël. Le patrimoine se découvre aussi lors des Journées du Patrimoine en septembre.';
$s2Nature    = $fields['section2_nature']    ?? 'À quelques minutes, la Côte de Granit Rose offre des paysages spectaculaires. Perros-Guirec, Trébeurden et Trégastel sont à moins de 15 minutes. Randonnées sur le GR34, sports nautiques, balades en forêt : la nature est omniprésente.';

// Section 3 — Immobilier
$s3Title      = $fields['section3_title']      ?? 'Immobilier à Lannion';
$s3Subtitle   = $fields['section3_subtitle']   ?? 'Tendances du marché, prix et quartiers prisés';
$s3Tendances  = $fields['section3_tendances']  ?? 'Le marché immobilier lannionnais reste attractif avec des prix bien en dessous de la moyenne nationale. La demande est soutenue grâce à l\'attractivité économique (pôle télécom, numérique) et au cadre de vie. Les maisons avec jardin et les biens rénovés en centre-ville sont particulièrement recherchés.';
$s3Prix       = $fields['section3_prix']       ?? 'Le prix médian se situe autour de 1 600 à 2 100 €/m² pour les maisons et de 1 400 à 1 900 €/m² pour les appartements, selon le quartier et l\'état du bien. Les prix ont connu une hausse modérée ces dernières années, portée par l\'attrait post-Covid pour les villes moyennes offrant un cadre de vie agréable.';
$s3Quartiers  = $fields['section3_quartiers']  ?? 'Les quartiers les plus prisés incluent le centre-ville historique (charme et commodités), Brélévenez (calme et vue panoramique), Servel-Lannion (résidentiel, proche du pôle technologique), et les communes limitrophes comme Ploubezre ou Rospez pour ceux qui recherchent plus d\'espace. Le secteur côtier (Perros-Guirec, Trébeurden) reste prisé pour les résidences secondaires.';
$s3Investir   = $fields['section3_investir']   ?? 'Investir à Lannion, c\'est miser sur une ville en développement avec un bassin d\'emploi solide. Le rendement locatif est intéressant pour les petites surfaces (studios, T2) grâce à la présence étudiante et des jeunes actifs du pôle numérique. Les dispositifs de défiscalisation (Denormandie dans l\'ancien) sont applicables dans certains secteurs.';

// ── Données marché rapides ──
$prixMoyen  = $fields['prix_moyen']  ?? '~1 850 €/m²';
$evolution  = $fields['evolution']    ?? '+3,5 % / an';
$nbHab      = $fields['nb_habitants'] ?? '~20 000';
$superficie = $fields['superficie']   ?? '43,9 km²';

// ── CTA ──
$ctaTitle   = $fields['cta_title']    ?? 'Votre projet immobilier à Lannion ?';
$ctaText    = $fields['cta_text']     ?? 'Que vous souhaitiez acheter, vendre ou simplement vous renseigner, je suis à votre disposition pour vous accompagner dans votre projet.';
$ctaBtnText = $fields['cta_btn_text'] ?? 'Contactez-moi';
$ctaBtnUrl  = $fields['cta_btn_url']  ?? '/contact';

// ── SEO ──
$metaTitle = $page['meta_title'] ?? $fields['seo_title'] ?? $guideTitle;
$metaDesc  = $page['meta_description'] ?? $fields['seo_description'] ?? $guideIntro;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($metaTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
    <?php if (!empty($page['og_image'])): ?><meta property="og:image" content="<?= htmlspecialchars($page['og_image']) ?>"><?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php require_once __DIR__ . '/_tpl-common.php'; ?>
    <style>
    /* ── Guide Local specific styles ── */
    .t6-toc {
        display:flex; justify-content:center; gap:12px; flex-wrap:wrap;
        margin-top:32px;
    }
    .t6-toc a {
        display:inline-flex; align-items:center; gap:8px;
        background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.25);
        color:rgba(255,255,255,.9); padding:10px 22px; border-radius:50px;
        font-size:.85rem; font-weight:600; transition:all .2s;
    }
    .t6-toc a:hover {
        background:var(--tp-accent); color:var(--tp-primary-d);
        border-color:var(--tp-accent);
    }

    .t6-stats-bar {
        display:grid; grid-template-columns:repeat(4,1fr); gap:0;
        background:var(--tp-white); border-bottom:1px solid var(--tp-border);
    }
    .t6-stat {
        text-align:center; padding:28px 16px;
        border-right:1px solid var(--tp-border);
    }
    .t6-stat:last-child { border-right:none; }
    .t6-stat-num {
        font-family:var(--tp-ff-display); font-size:1.8rem; font-weight:900;
        color:var(--tp-primary); line-height:1; margin-bottom:6px;
    }
    .t6-stat-lbl {
        font-size:.72rem; color:var(--tp-text3); text-transform:uppercase;
        letter-spacing:.05em; font-weight:600;
    }

    .t6-section-header {
        text-align:center; margin-bottom:48px;
    }
    .t6-section-header h2 {
        font-family:var(--tp-ff-display); font-size:clamp(1.5rem,3vw,2.2rem);
        font-weight:800; color:var(--tp-primary); margin:0 0 12px;
    }
    .t6-section-header p {
        font-size:1rem; color:var(--tp-text3); max-width:600px; margin:0 auto;
    }

    .t6-cards-grid {
        display:grid; grid-template-columns:repeat(2,1fr); gap:28px;
    }
    .t6-card {
        background:var(--tp-white); border-radius:var(--tp-radius);
        border:1px solid var(--tp-border); padding:32px 28px;
        box-shadow:var(--tp-shadow); transition:transform .2s,box-shadow .2s;
    }
    .t6-card:hover { transform:translateY(-3px); box-shadow:var(--tp-shadow-lg); }
    .t6-card-icon {
        width:52px; height:52px; border-radius:14px;
        display:flex; align-items:center; justify-content:center;
        margin-bottom:18px; font-size:1.4rem;
    }
    .t6-card-icon.blue { background:rgba(27,58,75,.08); color:var(--tp-primary); }
    .t6-card-icon.gold { background:rgba(200,169,110,.12); color:var(--tp-accent-d); }
    .t6-card-icon.green { background:rgba(16,185,129,.08); color:var(--tp-green); }
    .t6-card-title {
        font-family:var(--tp-ff-display); font-size:1.1rem; font-weight:800;
        color:var(--tp-primary); margin-bottom:12px;
    }
    .t6-card-text {
        font-size:.88rem; color:var(--tp-text2); line-height:1.75;
    }

    .t6-highlight-box {
        background:linear-gradient(135deg,var(--tp-primary-d) 0%,var(--tp-primary) 100%);
        border-radius:var(--tp-radius); padding:40px 36px;
        color:var(--tp-white); position:relative; overflow:hidden;
    }
    .t6-highlight-box::before {
        content:''; position:absolute; top:-40px; right:-40px;
        width:200px; height:200px;
        background:radial-gradient(circle,rgba(200,169,110,.15),transparent 65%);
        border-radius:50%;
    }
    .t6-highlight-box h3 {
        font-family:var(--tp-ff-display); font-size:1.3rem; font-weight:800;
        margin-bottom:14px; position:relative;
    }
    .t6-highlight-box p {
        font-size:.9rem; line-height:1.75; opacity:.9; position:relative;
    }

    .t6-quartiers-grid {
        display:grid; grid-template-columns:repeat(3,1fr); gap:24px;
    }
    .t6-quartier {
        background:var(--tp-white); border-radius:var(--tp-radius);
        border:1px solid var(--tp-border); padding:28px 24px;
        box-shadow:var(--tp-shadow); transition:all .2s; text-align:center;
    }
    .t6-quartier:hover { transform:translateY(-3px); box-shadow:var(--tp-shadow-lg); border-color:var(--tp-accent); }
    .t6-quartier-name {
        font-family:var(--tp-ff-display); font-size:1.05rem; font-weight:800;
        color:var(--tp-primary); margin-bottom:10px;
    }
    .t6-quartier-desc {
        font-size:.83rem; color:var(--tp-text2); line-height:1.65;
    }

    @media (max-width:960px) {
        .t6-stats-bar { grid-template-columns:repeat(2,1fr); }
        .t6-stat { border-bottom:1px solid var(--tp-border); }
        .t6-cards-grid { grid-template-columns:1fr; }
        .t6-quartiers-grid { grid-template-columns:1fr; }
    }
    @media (max-width:600px) {
        .t6-stats-bar { grid-template-columns:1fr; }
        .t6-toc { flex-direction:column; align-items:center; }
        .t6-toc a { width:100%; max-width:280px; justify-content:center; }
        .t6-highlight-box { padding:28px 24px; }
    }
    </style>
</head>
<body>
<div class="tp-page">

<?php
// Header
if (file_exists(__DIR__ . '/../../page.php') && function_exists('renderHeader')) {
    echo renderHeader($headerData);
} elseif (file_exists(__DIR__ . '/../../helpers/layout.php')) {
    require_once __DIR__ . '/../../helpers/layout.php';
    if (function_exists('renderHeader')) echo renderHeader($headerData);
}
?>

<!-- ═══════════════════════════════════════════════════
     HERO — GUIDE LOCAL
     ═══════════════════════════════════════════════════ -->
<section class="tp-hero">
    <div class="tp-hero-inner" style="text-align:center">
        <div class="tp-eyebrow">Guide local</div>
        <h1 class="tp-hero-h1" style="margin-left:auto;margin-right:auto"
            <?= $editMode ? 'data-field="guide_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($guideTitle) ?>
        </h1>
        <p class="tp-hero-sub" style="margin-left:auto;margin-right:auto"
            <?= $editMode ? 'data-field="guide_intro" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($guideIntro) ?>
        </p>
        <nav class="t6-toc">
            <a href="#infos-pratiques"><i class="fas fa-landmark"></i> Infos pratiques</a>
            <a href="#vivre-a-lannion"><i class="fas fa-heart"></i> Vivre à Lannion</a>
            <a href="#immobilier"><i class="fas fa-home"></i> Immobilier</a>
        </nav>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     BARRE DE CHIFFRES CLÉS
     ═══════════════════════════════════════════════════ -->
<div class="t6-stats-bar">
    <div class="t6-stat">
        <div class="t6-stat-num" <?= $editMode ? 'data-field="prix_moyen" class="ef-zone"' : '' ?>><?= htmlspecialchars($prixMoyen) ?></div>
        <div class="t6-stat-lbl">Prix moyen / m²</div>
    </div>
    <div class="t6-stat">
        <div class="t6-stat-num" <?= $editMode ? 'data-field="evolution" class="ef-zone"' : '' ?>><?= htmlspecialchars($evolution) ?></div>
        <div class="t6-stat-lbl">Évolution annuelle</div>
    </div>
    <div class="t6-stat">
        <div class="t6-stat-num" <?= $editMode ? 'data-field="nb_habitants" class="ef-zone"' : '' ?>><?= htmlspecialchars($nbHab) ?></div>
        <div class="t6-stat-lbl">Habitants</div>
    </div>
    <div class="t6-stat">
        <div class="t6-stat-num" <?= $editMode ? 'data-field="superficie" class="ef-zone"' : '' ?>><?= htmlspecialchars($superficie) ?></div>
        <div class="t6-stat-lbl">Superficie</div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════
     SECTION 1 — INFOS PRATIQUES
     ═══════════════════════════════════════════════════ -->
<section id="infos-pratiques" class="tp-section-white">
    <div class="tp-container">
        <div class="t6-section-header">
            <div class="tp-section-badge">Pratique</div>
            <h2 <?= $editMode ? 'data-field="section1_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($s1Title) ?></h2>
            <p <?= $editMode ? 'data-field="section1_subtitle" class="ef-zone"' : '' ?>><?= htmlspecialchars($s1Subtitle) ?></p>
        </div>
        <div class="t6-cards-grid">
            <div class="t6-card">
                <div class="t6-card-icon blue"><i class="fas fa-landmark"></i></div>
                <div class="t6-card-title">Mairie &amp; administration</div>
                <div class="t6-card-text" <?= $editMode ? 'data-field="section1_mairie" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($s1Mairie) ?>
                </div>
            </div>
            <div class="t6-card">
                <div class="t6-card-icon gold"><i class="fas fa-bus"></i></div>
                <div class="t6-card-title">Transports &amp; accès</div>
                <div class="t6-card-text" <?= $editMode ? 'data-field="section1_transport" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($s1Transport) ?>
                </div>
            </div>
            <div class="t6-card">
                <div class="t6-card-icon green"><i class="fas fa-stethoscope"></i></div>
                <div class="t6-card-title">Santé, éducation &amp; commerces</div>
                <div class="t6-card-text" <?= $editMode ? 'data-field="section1_services" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($s1Services) ?>
                </div>
            </div>
            <div class="t6-card">
                <div class="t6-card-icon blue"><i class="fas fa-file-alt"></i></div>
                <div class="t6-card-title">Démarches administratives</div>
                <div class="t6-card-text" <?= $editMode ? 'data-field="section1_demarches" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($s1Demarches) ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     SECTION 2 — VIVRE À LANNION
     ═══════════════════════════════════════════════════ -->
<section id="vivre-a-lannion" class="tp-section-light">
    <div class="tp-container">
        <div class="t6-section-header">
            <div class="tp-section-badge">Cadre de vie</div>
            <h2 <?= $editMode ? 'data-field="section2_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($s2Title) ?></h2>
            <p <?= $editMode ? 'data-field="section2_subtitle" class="ef-zone"' : '' ?>><?= htmlspecialchars($s2Subtitle) ?></p>
        </div>
        <div class="t6-cards-grid">
            <div class="t6-card">
                <div class="t6-card-icon gold"><i class="fas fa-city"></i></div>
                <div class="t6-card-title">Ambiance &amp; cadre de vie</div>
                <div class="t6-card-text" <?= $editMode ? 'data-field="section2_ambiance" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($s2Ambiance) ?>
                </div>
            </div>
            <div class="t6-card">
                <div class="t6-card-icon blue"><i class="fas fa-theater-masks"></i></div>
                <div class="t6-card-title">Culture &amp; loisirs</div>
                <div class="t6-card-text" <?= $editMode ? 'data-field="section2_culture" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($s2Culture) ?>
                </div>
            </div>
            <div class="t6-card">
                <div class="t6-card-icon gold"><i class="fas fa-calendar-star"></i></div>
                <div class="t6-card-title">Événements &amp; festivités</div>
                <div class="t6-card-text" <?= $editMode ? 'data-field="section2_events" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($s2Events) ?>
                </div>
            </div>
            <div class="t6-card">
                <div class="t6-card-icon green"><i class="fas fa-leaf"></i></div>
                <div class="t6-card-title">Nature &amp; Côte de Granit Rose</div>
                <div class="t6-card-text" <?= $editMode ? 'data-field="section2_nature" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($s2Nature) ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     SECTION 3 — IMMOBILIER
     ═══════════════════════════════════════════════════ -->
<section id="immobilier" class="tp-section-white">
    <div class="tp-container">
        <div class="t6-section-header">
            <div class="tp-section-badge">Immobilier</div>
            <h2 <?= $editMode ? 'data-field="section3_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($s3Title) ?></h2>
            <p <?= $editMode ? 'data-field="section3_subtitle" class="ef-zone"' : '' ?>><?= htmlspecialchars($s3Subtitle) ?></p>
        </div>

        <!-- Tendances & Prix -->
        <div class="t6-cards-grid" style="margin-bottom:32px">
            <div class="t6-card">
                <div class="t6-card-icon gold"><i class="fas fa-chart-line"></i></div>
                <div class="t6-card-title">Tendances du marché</div>
                <div class="t6-card-text" <?= $editMode ? 'data-field="section3_tendances" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($s3Tendances) ?>
                </div>
            </div>
            <div class="t6-card">
                <div class="t6-card-icon blue"><i class="fas fa-euro-sign"></i></div>
                <div class="t6-card-title">Prix de l'immobilier</div>
                <div class="t6-card-text" <?= $editMode ? 'data-field="section3_prix" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($s3Prix) ?>
                </div>
            </div>
        </div>

        <!-- Quartiers -->
        <h3 style="font-family:var(--tp-ff-display);font-size:1.3rem;font-weight:800;color:var(--tp-primary);text-align:center;margin-bottom:28px">
            Les quartiers prisés
        </h3>
        <div class="t6-quartiers-grid" style="margin-bottom:36px">
            <div class="t6-quartier">
                <div style="font-size:1.8rem;margin-bottom:12px">🏛️</div>
                <div class="t6-quartier-name">Centre-ville historique</div>
                <div class="t6-quartier-desc">Charme des maisons à colombages, commodités à pied, vie animée.</div>
            </div>
            <div class="t6-quartier">
                <div style="font-size:1.8rem;margin-bottom:12px">⛪</div>
                <div class="t6-quartier-name">Brélévenez</div>
                <div class="t6-quartier-desc">Quartier calme sur les hauteurs, vue panoramique, patrimoine remarquable.</div>
            </div>
            <div class="t6-quartier">
                <div style="font-size:1.8rem;margin-bottom:12px">💻</div>
                <div class="t6-quartier-name">Servel / Technopôle</div>
                <div class="t6-quartier-desc">Résidentiel et moderne, proche du pôle technologique et de la nature.</div>
            </div>
        </div>

        <!-- Investir — highlight box -->
        <div class="t6-highlight-box">
            <h3><i class="fas fa-key" style="margin-right:10px;color:var(--tp-accent)"></i> Pourquoi investir à Lannion ?</h3>
            <p <?= $editMode ? 'data-field="section3_investir" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($s3Investir) ?>
            </p>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     CTA FINALE
     ═══════════════════════════════════════════════════ -->
<section class="tp-cta-section">
    <div class="tp-container" style="text-align:center">
        <div class="tp-cta-title" <?= $editMode ? 'data-field="cta_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($ctaTitle) ?>
        </div>
        <div class="tp-cta-text" <?= $editMode ? 'data-field="cta_text" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($ctaText) ?>
        </div>
        <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="tp-cta-btn">
            <?= htmlspecialchars($ctaBtnText) ?>
        </a>
    </div>
</section>

<?php
// Footer
if (function_exists('renderFooter')) {
    echo renderFooter($footerData);
}
?>

</div>
</body>
</html>
