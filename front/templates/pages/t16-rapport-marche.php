<?php
/**
 * /front/templates/pages/t16-rapport-marche.php
 * Template Rapport de Marché — Analyse immobilière locale
 * Affiche prix moyens, évolution, tendances et lien PDF téléchargeable
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
$siteUrl    = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

$advisorName    = $advisor['name']    ?? ($site['name']    ?? 'Stéphanie Hulen');
$advisorCity    = $advisor['city']    ?? ($site['city']    ?? 'Lannion');
$advisorPhone   = $advisor['phone']   ?? ($site['phone']   ?? '');
$advisorNetwork = $advisor['network'] ?? '';

// ────────────────────────────────────────────────────
// CHAMPS CMS
// ────────────────────────────────────────────────────
$heroTitle    = $fields['hero_title']    ?? 'Rapport de marché immobilier';
$heroSubtitle = $fields['hero_subtitle'] ?? 'Analyse détaillée du marché à Lannion et ses environs — Mars 2026';

// ── Texte d'introduction ──
$introText = $fields['intro_text'] ?? 'Retrouvez dans ce rapport une analyse complète du marché immobilier local : prix moyens par type de bien et par secteur, évolutions récentes et tendances à surveiller. Ce document est mis à jour régulièrement pour vous accompagner dans vos projets immobiliers.';

// ── Prix moyens Lannion ──
$prixM2Global  = $fields['prix_m2_global']  ?? '1 850 €';
$prixM2Maison  = $fields['prix_m2_maison']  ?? '1 720 €';
$prixM2Appart  = $fields['prix_m2_appart']  ?? '2 050 €';
$prixM2Terrain = $fields['prix_m2_terrain'] ?? '85 €';
$nbTransactions = $fields['nb_transactions'] ?? '345';
$delaiMoyen     = $fields['delai_moyen']     ?? '72 jours';

// ── Évolution ──
$evolGlobal  = $fields['evol_global']  ?? '+2,8 %';
$evolMaison  = $fields['evol_maison']  ?? '+3,1 %';
$evolAppart  = $fields['evol_appart']  ?? '+2,4 %';
$evolTerrain = $fields['evol_terrain'] ?? '+1,5 %';
$evolPeriode = $fields['evol_periode'] ?? 'sur 12 mois (mars 2025 — mars 2026)';

// ── Prix par secteur ──
$secteurPrix = $fields['secteur_prix'] ?? [
    ['nom' => 'Centre-ville Lannion',     'maison' => '2 100 €', 'appart' => '2 350 €', 'evol' => '+3,5 %'],
    ['nom' => 'Brélévenez',               'maison' => '1 850 €', 'appart' => '2 100 €', 'evol' => '+2,9 %'],
    ['nom' => 'Servel',                    'maison' => '1 650 €', 'appart' => '1 800 €', 'evol' => '+2,2 %'],
    ['nom' => 'Ploubezre',                 'maison' => '1 520 €', 'appart' => '—',       'evol' => '+1,8 %'],
    ['nom' => 'Trébeurden',                'maison' => '2 450 €', 'appart' => '2 800 €', 'evol' => '+4,2 %'],
    ['nom' => 'Perros-Guirec',             'maison' => '2 650 €', 'appart' => '3 100 €', 'evol' => '+3,8 %'],
    ['nom' => 'Trégastel',                 'maison' => '2 300 €', 'appart' => '2 700 €', 'evol' => '+3,6 %'],
    ['nom' => 'Pleumeur-Bodou',            'maison' => '1 780 €', 'appart' => '—',       'evol' => '+2,1 %'],
];

// ── Tendances ──
$tendance1Titre = $fields['tendance_1_titre'] ?? 'Demande soutenue sur le littoral';
$tendance1Texte = $fields['tendance_1_texte'] ?? 'Les communes côtières (Trébeurden, Perros-Guirec, Trégastel) affichent une hausse continue des prix, portée par l\'attrait touristique et le télétravail. La demande dépasse l\'offre sur les biens avec vue mer.';
$tendance2Titre = $fields['tendance_2_titre'] ?? 'Lannion centre : un marché dynamique';
$tendance2Texte = $fields['tendance_2_texte'] ?? 'Le centre-ville de Lannion bénéficie de la proximité des services, du pôle technologique et des projets de rénovation urbaine. Les appartements rénovés trouvent preneur rapidement.';
$tendance3Titre = $fields['tendance_3_titre'] ?? 'Première couronne : le meilleur rapport qualité-prix';
$tendance3Texte = $fields['tendance_3_texte'] ?? 'Des communes comme Ploubezre, Servel ou Pleumeur-Bodou offrent des maisons avec terrain à des prix attractifs, séduisant les familles et primo-accédants.';
$tendance4Titre = $fields['tendance_4_titre'] ?? 'Impact du DPE sur les prix';
$tendance4Texte = $fields['tendance_4_texte'] ?? 'Les biens mal classés (F-G) subissent une décote de 10 à 15 %, tandis que les logements rénovés énergétiquement se valorisent. L\'accompagnement sur les travaux devient un critère d\'achat.';

// ── Conseils ──
$conseil1 = $fields['conseil_1'] ?? 'Vendeurs : c\'est le moment de profiter d\'un marché haussier. Une estimation précise et un accompagnement professionnel maximisent votre prix de vente.';
$conseil2 = $fields['conseil_2'] ?? 'Acheteurs : anticipez les délais et préparez votre dossier de financement en amont pour être réactif face aux biens les plus recherchés.';
$conseil3 = $fields['conseil_3'] ?? 'Investisseurs : les petites surfaces à Lannion centre offrent des rendements locatifs intéressants, avec une demande étudiante et professionnelle stable.';

// ── CTA ──
$ctaTitle   = $fields['cta_title']    ?? 'Besoin d\'un accompagnement personnalisé ?';
$ctaText    = $fields['cta_text']     ?? 'Contactez-moi pour une estimation gratuite ou pour discuter de votre projet immobilier à Lannion et ses environs.';
$ctaBtnText = $fields['cta_btn_text'] ?? 'Me contacter';
$ctaBtnUrl  = $fields['cta_btn_url']  ?? '/contact';

// ── PDF ──
$pdfUrl = $fields['pdf_url'] ?? ($siteUrl . '/front/templates/pages/t16-rapport-marche-pdf.php');

// ── SEO ──
$metaTitle = $page['meta_title'] ?? $fields['seo_title'] ?? 'Rapport de marché immobilier Lannion 2026 | ' . $advisorName;
$metaDesc  = $page['meta_description'] ?? $fields['seo_description'] ?? 'Prix moyens, évolution et tendances du marché immobilier à Lannion et sur la Côte de Granit Rose. Rapport mis à jour en mars 2026.';

// Helper: detect positive/negative evolution
function t16_evolClass($val) {
    if (strpos($val, '+') !== false) return 'positive';
    if (strpos($val, '-') !== false) return 'negative';
    return 'neutral';
}
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
    /* ── T16 Rapport de marché — styles spécifiques ── */

    .t16-stats-row {
        display:grid; grid-template-columns:repeat(3,1fr); gap:0;
        background:var(--tp-white); border-bottom:1px solid var(--tp-border);
    }
    .t16-stats-row.cols-6 { grid-template-columns:repeat(6,1fr); }
    .t16-stat {
        text-align:center; padding:32px 16px;
        border-right:1px solid var(--tp-border);
    }
    .t16-stat:last-child { border-right:none; }
    .t16-stat-num {
        font-family:var(--tp-ff-display); font-size:2rem; font-weight:900;
        color:var(--tp-primary); line-height:1; margin-bottom:6px;
    }
    .t16-stat-lbl {
        font-size:.72rem; color:var(--tp-text3); text-transform:uppercase;
        letter-spacing:.05em; font-weight:600;
    }
    .t16-stat-change {
        font-size:.8rem; font-weight:700; margin-top:6px;
    }
    .t16-stat-change.positive { color:var(--tp-green); }
    .t16-stat-change.negative { color:var(--tp-red); }

    /* Table prix secteurs */
    .t16-table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; margin:30px 0; }
    .t16-table {
        width:100%; border-collapse:collapse; background:var(--tp-white);
        border-radius:var(--tp-radius); overflow:hidden; box-shadow:var(--tp-shadow);
    }
    .t16-table thead {
        background:linear-gradient(135deg,var(--tp-primary),var(--tp-primary-d));
    }
    .t16-table th {
        padding:16px 18px; text-align:left; color:var(--tp-white);
        font-weight:600; font-size:.82rem; text-transform:uppercase;
        letter-spacing:.05em;
    }
    .t16-table td {
        padding:14px 18px; border-bottom:1px solid var(--tp-border);
        font-size:.9rem; color:var(--tp-text);
    }
    .t16-table td:first-child { font-weight:700; color:var(--tp-primary); }
    .t16-table tbody tr:last-child td { border-bottom:none; }
    .t16-table tbody tr:nth-child(even) { background:var(--tp-bg); }
    .t16-table tbody tr:hover { background:rgba(200,169,110,.06); }
    .t16-table .positive { color:var(--tp-green); font-weight:700; }
    .t16-table .negative { color:var(--tp-red); font-weight:700; }

    /* Évolution grid */
    .t16-evol-grid {
        display:grid; grid-template-columns:repeat(4,1fr); gap:20px; margin:40px 0;
    }
    .t16-evol-card {
        background:var(--tp-white); border-radius:var(--tp-radius);
        border:1px solid var(--tp-border); padding:28px 20px; text-align:center;
        box-shadow:var(--tp-shadow); transition:transform .2s,box-shadow .2s;
    }
    .t16-evol-card:hover { transform:translateY(-3px); box-shadow:var(--tp-shadow-lg); }
    .t16-evol-card .evol-icon {
        font-size:1.8rem; margin-bottom:12px; color:var(--tp-accent);
    }
    .t16-evol-card .evol-value {
        font-family:var(--tp-ff-display); font-size:1.8rem; font-weight:900; line-height:1;
        margin-bottom:8px;
    }
    .t16-evol-card .evol-value.positive { color:var(--tp-green); }
    .t16-evol-card .evol-value.negative { color:var(--tp-red); }
    .t16-evol-card .evol-label {
        font-size:.82rem; color:var(--tp-text3); font-weight:600;
        text-transform:uppercase; letter-spacing:.04em;
    }

    /* Tendances */
    .t16-tendances-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:24px; }
    .t16-tendance-card {
        background:var(--tp-white); border-radius:var(--tp-radius);
        border:1px solid var(--tp-border); padding:32px 28px;
        box-shadow:var(--tp-shadow); transition:transform .2s,box-shadow .2s;
        position:relative; overflow:hidden;
    }
    .t16-tendance-card:hover { transform:translateY(-3px); box-shadow:var(--tp-shadow-lg); }
    .t16-tendance-card::before {
        content:''; position:absolute; top:0; left:0; width:4px; height:100%;
        background:var(--tp-accent);
    }
    .t16-tendance-card h3 {
        font-family:var(--tp-ff-display); font-size:1.1rem; font-weight:800;
        color:var(--tp-primary); margin:0 0 14px; padding-left:4px;
    }
    .t16-tendance-card p {
        font-size:.88rem; color:var(--tp-text2); line-height:1.75; margin:0;
        padding-left:4px;
    }

    /* Conseils */
    .t16-conseils {
        background:var(--tp-bg); border-radius:var(--tp-radius);
        padding:36px 32px; margin:40px 0;
    }
    .t16-conseils h3 {
        font-family:var(--tp-ff-display); font-size:1.2rem; font-weight:800;
        color:var(--tp-primary); margin:0 0 24px;
    }
    .t16-conseil-item {
        display:flex; gap:16px; padding:16px 0;
        border-bottom:1px solid var(--tp-border);
    }
    .t16-conseil-item:last-child { border-bottom:none; }
    .t16-conseil-icon {
        flex-shrink:0; width:40px; height:40px; border-radius:10px;
        background:rgba(200,169,110,.12); display:flex; align-items:center;
        justify-content:center; color:var(--tp-accent-d); font-size:1rem;
    }
    .t16-conseil-text {
        font-size:.88rem; color:var(--tp-text2); line-height:1.7;
    }

    /* PDF download banner */
    .t16-pdf-banner {
        background:linear-gradient(135deg, var(--tp-primary) 0%, var(--tp-primary-l) 100%);
        border-radius:var(--tp-radius); padding:40px 36px;
        display:flex; align-items:center; justify-content:space-between;
        gap:24px; margin:40px 0; box-shadow:var(--tp-shadow-lg);
    }
    .t16-pdf-info { flex:1; }
    .t16-pdf-info h3 {
        font-family:var(--tp-ff-display); font-size:1.3rem; font-weight:800;
        color:var(--tp-white); margin:0 0 8px;
    }
    .t16-pdf-info p {
        font-size:.9rem; color:rgba(255,255,255,.8); margin:0; line-height:1.6;
    }
    .t16-pdf-btn {
        display:inline-flex; align-items:center; gap:10px;
        background:var(--tp-accent); color:var(--tp-primary-d);
        font-weight:800; font-size:.95rem; padding:16px 32px;
        border-radius:50px; box-shadow:0 4px 20px rgba(200,169,110,.35);
        transition:all .2s; white-space:nowrap; text-decoration:none;
    }
    .t16-pdf-btn:hover { background:var(--tp-accent-l); transform:translateY(-2px); }
    .t16-pdf-btn i { font-size:1.1rem; }

    /* Date update badge */
    .t16-update-badge {
        display:inline-flex; align-items:center; gap:8px;
        background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.25);
        color:rgba(255,255,255,.9); font-size:.78rem; font-weight:600;
        padding:6px 16px; border-radius:40px; margin-top:16px;
    }
    .t16-update-badge i { font-size:.7rem; }

    @media (max-width:960px) {
        .t16-stats-row.cols-6 { grid-template-columns:repeat(3,1fr); }
        .t16-evol-grid { grid-template-columns:repeat(2,1fr); }
        .t16-tendances-grid { grid-template-columns:1fr; }
        .t16-pdf-banner { flex-direction:column; text-align:center; }
    }
    @media (max-width:600px) {
        .t16-stats-row, .t16-stats-row.cols-6 { grid-template-columns:1fr; }
        .t16-stat { border-right:none; border-bottom:1px solid var(--tp-border); padding:20px 16px; }
        .t16-stat:last-child { border-bottom:none; }
        .t16-evol-grid { grid-template-columns:1fr; }
        .t16-stat-num { font-size:1.6rem; }
        .t16-evol-card .evol-value { font-size:1.5rem; }
    }

    @media print {
        .tp-cta-section, .t16-pdf-banner { display:none !important; }
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
     HERO — RAPPORT DE MARCHÉ
     ═══════════════════════════════════════════════════ -->
<section class="tp-hero">
    <div class="tp-hero-inner" style="text-align:center">
        <div class="tp-eyebrow">Rapport de marché</div>
        <h1 class="tp-hero-h1" <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?> style="margin-left:auto;margin-right:auto">
            <?= htmlspecialchars($heroTitle) ?>
        </h1>
        <p class="tp-hero-sub" <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?> style="margin-left:auto;margin-right:auto">
            <?= htmlspecialchars($heroSubtitle) ?>
        </p>
        <a href="<?= htmlspecialchars($pdfUrl) ?>" class="tp-hero-cta" download>
            <i class="fas fa-file-pdf" style="margin-right:4px"></i> Télécharger le rapport PDF
        </a>
        <div class="t16-update-badge">
            <i class="fas fa-sync-alt"></i> Dernière mise à jour : mars 2026
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     CHIFFRES CLÉS
     ═══════════════════════════════════════════════════ -->
<div class="t16-stats-row cols-6">
    <div class="t16-stat">
        <div class="t16-stat-num"><?= htmlspecialchars($prixM2Global) ?></div>
        <div class="t16-stat-lbl">Prix moyen / m²</div>
        <div class="t16-stat-change <?= t16_evolClass($evolGlobal) ?>"><?= htmlspecialchars($evolGlobal) ?></div>
    </div>
    <div class="t16-stat">
        <div class="t16-stat-num"><?= htmlspecialchars($prixM2Maison) ?></div>
        <div class="t16-stat-lbl">Maisons / m²</div>
        <div class="t16-stat-change <?= t16_evolClass($evolMaison) ?>"><?= htmlspecialchars($evolMaison) ?></div>
    </div>
    <div class="t16-stat">
        <div class="t16-stat-num"><?= htmlspecialchars($prixM2Appart) ?></div>
        <div class="t16-stat-lbl">Appartements / m²</div>
        <div class="t16-stat-change <?= t16_evolClass($evolAppart) ?>"><?= htmlspecialchars($evolAppart) ?></div>
    </div>
    <div class="t16-stat">
        <div class="t16-stat-num"><?= htmlspecialchars($prixM2Terrain) ?></div>
        <div class="t16-stat-lbl">Terrains / m²</div>
        <div class="t16-stat-change <?= t16_evolClass($evolTerrain) ?>"><?= htmlspecialchars($evolTerrain) ?></div>
    </div>
    <div class="t16-stat">
        <div class="t16-stat-num"><?= htmlspecialchars($nbTransactions) ?></div>
        <div class="t16-stat-lbl">Transactions / an</div>
    </div>
    <div class="t16-stat">
        <div class="t16-stat-num"><?= htmlspecialchars($delaiMoyen) ?></div>
        <div class="t16-stat-lbl">Délai moyen de vente</div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════
     INTRODUCTION
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-white">
    <div class="tp-container-sm" style="text-align:center">
        <p <?= $editMode ? 'data-field="intro_text" class="ef-zone"' : '' ?> style="font-size:1.05rem;color:var(--tp-text2);line-height:1.8">
            <?= htmlspecialchars($introText) ?>
        </p>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     PRIX PAR SECTEUR
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-light">
    <div class="tp-container">
        <div class="tp-section-badge" style="display:flex;justify-content:center"><i class="fas fa-map-marker-alt" style="margin-right:6px"></i> Analyse par secteur</div>
        <h2 class="tp-section-title">Prix moyens par commune</h2>

        <div class="t16-table-wrap">
            <table class="t16-table">
                <thead>
                    <tr>
                        <th>Secteur</th>
                        <th>Maisons / m²</th>
                        <th>Appartements / m²</th>
                        <th>Évolution 12 mois</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (is_array($secteurPrix)):
                        foreach ($secteurPrix as $s):
                            $evCls = t16_evolClass($s['evol'] ?? '');
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($s['nom'] ?? '') ?></td>
                        <td><?= htmlspecialchars($s['maison'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($s['appart'] ?? '—') ?></td>
                        <td class="<?= $evCls ?>"><?= htmlspecialchars($s['evol'] ?? '—') ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <p style="font-size:.82rem;color:var(--tp-text3);text-align:center;margin-top:8px">
            <i class="fas fa-info-circle"></i> Prix indicatifs au m² — Sources : données notariales, observatoire local, estimations terrain — <?= htmlspecialchars($evolPeriode) ?>
        </p>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     ÉVOLUTION DU MARCHÉ
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-white">
    <div class="tp-container">
        <div class="tp-section-badge" style="display:flex;justify-content:center"><i class="fas fa-chart-line" style="margin-right:6px"></i> Évolution</div>
        <h2 class="tp-section-title">Évolution des prix <?= htmlspecialchars($evolPeriode) ?></h2>

        <div class="t16-evol-grid">
            <div class="t16-evol-card">
                <div class="evol-icon"><i class="fas fa-home"></i></div>
                <div class="evol-value <?= t16_evolClass($evolGlobal) ?>"><?= htmlspecialchars($evolGlobal) ?></div>
                <div class="evol-label">Marché global</div>
            </div>
            <div class="t16-evol-card">
                <div class="evol-icon"><i class="fas fa-house-chimney"></i></div>
                <div class="evol-value <?= t16_evolClass($evolMaison) ?>"><?= htmlspecialchars($evolMaison) ?></div>
                <div class="evol-label">Maisons</div>
            </div>
            <div class="t16-evol-card">
                <div class="evol-icon"><i class="fas fa-building"></i></div>
                <div class="evol-value <?= t16_evolClass($evolAppart) ?>"><?= htmlspecialchars($evolAppart) ?></div>
                <div class="evol-label">Appartements</div>
            </div>
            <div class="t16-evol-card">
                <div class="evol-icon"><i class="fas fa-mountain-sun"></i></div>
                <div class="evol-value <?= t16_evolClass($evolTerrain) ?>"><?= htmlspecialchars($evolTerrain) ?></div>
                <div class="evol-label">Terrains</div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     TENDANCES DU MARCHÉ LOCAL
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-light">
    <div class="tp-container">
        <div class="tp-section-badge" style="display:flex;justify-content:center"><i class="fas fa-compass" style="margin-right:6px"></i> Tendances</div>
        <h2 class="tp-section-title">Tendances du marché local</h2>

        <div class="t16-tendances-grid">
            <div class="t16-tendance-card">
                <h3 <?= $editMode ? 'data-field="tendance_1_titre" class="ef-zone"' : '' ?>><?= htmlspecialchars($tendance1Titre) ?></h3>
                <p <?= $editMode ? 'data-field="tendance_1_texte" class="ef-zone"' : '' ?>><?= htmlspecialchars($tendance1Texte) ?></p>
            </div>
            <div class="t16-tendance-card">
                <h3 <?= $editMode ? 'data-field="tendance_2_titre" class="ef-zone"' : '' ?>><?= htmlspecialchars($tendance2Titre) ?></h3>
                <p <?= $editMode ? 'data-field="tendance_2_texte" class="ef-zone"' : '' ?>><?= htmlspecialchars($tendance2Texte) ?></p>
            </div>
            <div class="t16-tendance-card">
                <h3 <?= $editMode ? 'data-field="tendance_3_titre" class="ef-zone"' : '' ?>><?= htmlspecialchars($tendance3Titre) ?></h3>
                <p <?= $editMode ? 'data-field="tendance_3_texte" class="ef-zone"' : '' ?>><?= htmlspecialchars($tendance3Texte) ?></p>
            </div>
            <div class="t16-tendance-card">
                <h3 <?= $editMode ? 'data-field="tendance_4_titre" class="ef-zone"' : '' ?>><?= htmlspecialchars($tendance4Titre) ?></h3>
                <p <?= $editMode ? 'data-field="tendance_4_texte" class="ef-zone"' : '' ?>><?= htmlspecialchars($tendance4Texte) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     CONSEILS
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-white">
    <div class="tp-container">
        <div class="t16-conseils">
            <h3><i class="fas fa-lightbulb" style="color:var(--tp-accent);margin-right:8px"></i> Nos conseils</h3>
            <div class="t16-conseil-item">
                <div class="t16-conseil-icon"><i class="fas fa-hand-holding-dollar"></i></div>
                <div class="t16-conseil-text" <?= $editMode ? 'data-field="conseil_1" class="ef-zone"' : '' ?>><?= htmlspecialchars($conseil1) ?></div>
            </div>
            <div class="t16-conseil-item">
                <div class="t16-conseil-icon"><i class="fas fa-key"></i></div>
                <div class="t16-conseil-text" <?= $editMode ? 'data-field="conseil_2" class="ef-zone"' : '' ?>><?= htmlspecialchars($conseil2) ?></div>
            </div>
            <div class="t16-conseil-item">
                <div class="t16-conseil-icon"><i class="fas fa-chart-pie"></i></div>
                <div class="t16-conseil-text" <?= $editMode ? 'data-field="conseil_3" class="ef-zone"' : '' ?>><?= htmlspecialchars($conseil3) ?></div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     TÉLÉCHARGER LE PDF
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-light">
    <div class="tp-container">
        <div class="t16-pdf-banner">
            <div class="t16-pdf-info">
                <h3><i class="fas fa-file-pdf" style="margin-right:8px"></i> Rapport complet au format PDF</h3>
                <p>Téléchargez notre rapport de marché pour le consulter hors ligne ou le partager avec vos proches.</p>
            </div>
            <a href="<?= htmlspecialchars($pdfUrl) ?>" class="t16-pdf-btn" download>
                <i class="fas fa-download"></i> Télécharger le PDF
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     CTA FINALE
     ═══════════════════════════════════════════════════ -->
<section class="tp-cta-section">
    <div class="tp-container" style="text-align:center">
        <div class="tp-cta-title" <?= $editMode ? 'data-field="cta_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($ctaTitle) ?></div>
        <div class="tp-cta-text" <?= $editMode ? 'data-field="cta_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($ctaText) ?></div>
        <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="tp-cta-btn"><?= htmlspecialchars($ctaBtnText) ?></a>
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
