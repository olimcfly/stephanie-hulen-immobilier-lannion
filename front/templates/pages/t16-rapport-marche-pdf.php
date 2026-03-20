<?php
/**
 * /front/templates/pages/t16-rapport-marche-pdf.php
 * Génère une version PDF-friendly du rapport de marché
 * Utilise une mise en page HTML optimisée pour l'impression / Ctrl+P / window.print()
 *
 * Ce fichier est servi directement comme page HTML avec en-têtes
 * Content-Disposition pour déclencher le téléchargement.
 */

// ── Charger la config si disponible ──
$configPath = __DIR__ . '/../../../config/config.php';
if (file_exists($configPath)) {
    require_once $configPath;
}

$advisorName = 'Stéphanie Hulen';
$advisorCity = 'Lannion';

// ── Données du rapport (identiques à t16) ──
$prixM2Global  = '1 850 €';
$prixM2Maison  = '1 720 €';
$prixM2Appart  = '2 050 €';
$prixM2Terrain = '85 €';
$nbTransactions = '345';
$delaiMoyen     = '72 jours';

$evolGlobal  = '+2,8 %';
$evolMaison  = '+3,1 %';
$evolAppart  = '+2,4 %';
$evolTerrain = '+1,5 %';
$evolPeriode = 'sur 12 mois (mars 2025 — mars 2026)';

$secteurPrix = [
    ['nom' => 'Centre-ville Lannion',     'maison' => '2 100 €', 'appart' => '2 350 €', 'evol' => '+3,5 %'],
    ['nom' => 'Brélévenez',               'maison' => '1 850 €', 'appart' => '2 100 €', 'evol' => '+2,9 %'],
    ['nom' => 'Servel',                    'maison' => '1 650 €', 'appart' => '1 800 €', 'evol' => '+2,2 %'],
    ['nom' => 'Ploubezre',                 'maison' => '1 520 €', 'appart' => '—',       'evol' => '+1,8 %'],
    ['nom' => 'Trébeurden',                'maison' => '2 450 €', 'appart' => '2 800 €', 'evol' => '+4,2 %'],
    ['nom' => 'Perros-Guirec',             'maison' => '2 650 €', 'appart' => '3 100 €', 'evol' => '+3,8 %'],
    ['nom' => 'Trégastel',                 'maison' => '2 300 €', 'appart' => '2 700 €', 'evol' => '+3,6 %'],
    ['nom' => 'Pleumeur-Bodou',            'maison' => '1 780 €', 'appart' => '—',       'evol' => '+2,1 %'],
];

$tendances = [
    ['titre' => 'Demande soutenue sur le littoral', 'texte' => 'Les communes côtières (Trébeurden, Perros-Guirec, Trégastel) affichent une hausse continue des prix, portée par l\'attrait touristique et le télétravail.'],
    ['titre' => 'Lannion centre : un marché dynamique', 'texte' => 'Le centre-ville de Lannion bénéficie de la proximité des services, du pôle technologique et des projets de rénovation urbaine.'],
    ['titre' => 'Première couronne : le meilleur rapport qualité-prix', 'texte' => 'Des communes comme Ploubezre, Servel ou Pleumeur-Bodou offrent des maisons avec terrain à des prix attractifs.'],
    ['titre' => 'Impact du DPE sur les prix', 'texte' => 'Les biens mal classés (F-G) subissent une décote de 10 à 15 %, tandis que les logements rénovés énergétiquement se valorisent.'],
];

$conseils = [
    'Vendeurs : c\'est le moment de profiter d\'un marché haussier. Une estimation précise maximise votre prix de vente.',
    'Acheteurs : anticipez les délais et préparez votre dossier de financement en amont.',
    'Investisseurs : les petites surfaces à Lannion centre offrent des rendements locatifs intéressants.',
];

function pdf_evolColor($val) {
    if (strpos($val, '+') !== false) return '#10b981';
    if (strpos($val, '-') !== false) return '#ef4444';
    return '#718096';
}

// ── Forcer le téléchargement comme page imprimable ──
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de marché immobilier — <?= htmlspecialchars($advisorCity) ?> — Mars 2026</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
        body {
            font-family:'DM Sans','Segoe UI',sans-serif;
            color:#1a1a2e; line-height:1.6; background:#fff;
        }
        h1,h2,h3 { font-family:'Playfair Display',Georgia,serif; }

        /* ── Layout ── */
        .container { max-width:800px; margin:0 auto; padding:0 32px; }

        /* ── Cover ── */
        .cover {
            background:linear-gradient(145deg,#122A37 0%,#1B3A4B 55%,#2C5F7C 100%);
            color:#fff; padding:80px 32px; text-align:center;
        }
        .cover-eyebrow {
            display:inline-block; background:rgba(200,169,110,.15);
            border:1px solid rgba(200,169,110,.3); color:#E8D5A8;
            font-size:.72rem; font-weight:700; padding:5px 16px;
            border-radius:40px; letter-spacing:.06em; text-transform:uppercase;
            margin-bottom:24px;
        }
        .cover h1 {
            font-size:2.4rem; font-weight:800; margin-bottom:16px; letter-spacing:-.02em;
        }
        .cover p {
            font-size:1.05rem; color:rgba(255,255,255,.8); max-width:560px;
            margin:0 auto 24px; line-height:1.7;
        }
        .cover-date {
            display:inline-block; background:rgba(255,255,255,.12);
            border:1px solid rgba(255,255,255,.2); padding:6px 16px;
            border-radius:40px; font-size:.78rem; font-weight:600;
        }
        .cover-advisor {
            margin-top:32px; font-size:.9rem; color:rgba(255,255,255,.7);
        }

        /* ── Section ── */
        .section { padding:48px 0; }
        .section.grey { background:#F8F6F3; }
        .section-title {
            font-size:1.6rem; font-weight:800; color:#1B3A4B;
            text-align:center; margin-bottom:36px; letter-spacing:-.02em;
        }
        .section-subtitle {
            font-size:.85rem; color:#718096; text-align:center;
            margin-top:-28px; margin-bottom:36px;
        }

        /* ── Stats grid ── */
        .stats-grid {
            display:grid; grid-template-columns:repeat(3,1fr);
            border:1px solid #E2D9CC; border-radius:12px; overflow:hidden;
            margin-bottom:32px;
        }
        .stat-cell {
            text-align:center; padding:24px 12px;
            border-right:1px solid #E2D9CC; border-bottom:1px solid #E2D9CC;
        }
        .stat-cell:nth-child(3n) { border-right:none; }
        .stat-cell:nth-last-child(-n+3) { border-bottom:none; }
        .stat-num {
            font-family:'Playfair Display',serif; font-size:1.6rem;
            font-weight:900; color:#1B3A4B; line-height:1; margin-bottom:4px;
        }
        .stat-lbl {
            font-size:.7rem; color:#718096; text-transform:uppercase;
            letter-spacing:.05em; font-weight:600;
        }
        .stat-evol { font-size:.78rem; font-weight:700; margin-top:4px; }

        /* ── Table ── */
        .prix-table {
            width:100%; border-collapse:collapse; margin:24px 0;
            border-radius:12px; overflow:hidden;
        }
        .prix-table thead { background:#1B3A4B; }
        .prix-table th {
            padding:14px 16px; text-align:left; color:#fff;
            font-weight:600; font-size:.78rem; text-transform:uppercase;
            letter-spacing:.04em;
        }
        .prix-table td {
            padding:12px 16px; border-bottom:1px solid #E2D9CC;
            font-size:.88rem;
        }
        .prix-table td:first-child { font-weight:700; color:#1B3A4B; }
        .prix-table tbody tr:last-child td { border-bottom:none; }
        .prix-table tbody tr:nth-child(even) { background:#F8F6F3; }

        /* ── Tendance cards ── */
        .tendance-item {
            border-left:4px solid #C8A96E; padding:16px 20px;
            margin-bottom:20px; background:#F8F6F3; border-radius:0 12px 12px 0;
        }
        .tendance-item h3 {
            font-size:1rem; font-weight:800; color:#1B3A4B; margin-bottom:8px;
        }
        .tendance-item p {
            font-size:.85rem; color:#4a5568; line-height:1.7; margin:0;
        }

        /* ── Conseils ── */
        .conseil-item {
            display:flex; gap:12px; padding:14px 0;
            border-bottom:1px solid #E2D9CC;
        }
        .conseil-item:last-child { border-bottom:none; }
        .conseil-bullet {
            flex-shrink:0; width:28px; height:28px; border-radius:8px;
            background:rgba(200,169,110,.15); display:flex; align-items:center;
            justify-content:center; font-size:.8rem; color:#A68B4B; font-weight:700;
        }
        .conseil-text { font-size:.85rem; color:#4a5568; line-height:1.7; }

        /* ── Footer ── */
        .pdf-footer {
            text-align:center; padding:40px 32px; border-top:1px solid #E2D9CC;
            font-size:.82rem; color:#718096;
        }
        .pdf-footer strong { color:#1B3A4B; }

        /* ── Print download button ── */
        .print-bar {
            position:fixed; bottom:0; left:0; right:0; background:#1B3A4B;
            padding:14px 24px; text-align:center; z-index:999;
            box-shadow:0 -4px 20px rgba(0,0,0,.15);
        }
        .print-bar button {
            background:#C8A96E; color:#122A37; border:none;
            padding:12px 32px; border-radius:50px; font-weight:800;
            font-size:.9rem; cursor:pointer; font-family:inherit;
            transition:all .2s;
        }
        .print-bar button:hover { background:#E8D5A8; }
        .print-bar span { color:rgba(255,255,255,.7); font-size:.82rem; margin-left:16px; }

        /* ── Print styles ── */
        @media print {
            .print-bar { display:none !important; }
            body { font-size:11pt; }
            .cover { padding:48px 24px; }
            .cover h1 { font-size:1.8rem; }
            .section { padding:28px 0; }
            .container { max-width:100%; padding:0 24px; }
            .tendance-item { break-inside:avoid; }
            .prix-table { font-size:9pt; }
            @page { margin:1.5cm; }
        }
    </style>
</head>
<body>

<!-- ── Print bar (visible à l'écran, masqué à l'impression) ── -->
<div class="print-bar">
    <button onclick="window.print()">Enregistrer en PDF / Imprimer</button>
    <span>Utilisez « Enregistrer au format PDF » dans la boîte de dialogue d'impression</span>
</div>

<!-- ═══════════════════════════════════════════════════
     PAGE DE COUVERTURE
     ═══════════════════════════════════════════════════ -->
<div class="cover">
    <div class="cover-eyebrow">Rapport de marché immobilier</div>
    <h1>Marché immobilier à <?= htmlspecialchars($advisorCity) ?></h1>
    <p>Analyse des prix, évolutions et tendances du marché immobilier sur Lannion et la Côte de Granit Rose.</p>
    <div class="cover-date">Mars 2026</div>
    <div class="cover-advisor">Par <?= htmlspecialchars($advisorName) ?> — Conseillère immobilière</div>
</div>

<!-- ═══════════════════════════════════════════════════
     CHIFFRES CLÉS
     ═══════════════════════════════════════════════════ -->
<div class="section">
    <div class="container">
        <h2 class="section-title">Chiffres clés du marché</h2>
        <div class="stats-grid">
            <div class="stat-cell">
                <div class="stat-num"><?= $prixM2Global ?></div>
                <div class="stat-lbl">Prix moyen / m²</div>
                <div class="stat-evol" style="color:<?= pdf_evolColor($evolGlobal) ?>"><?= $evolGlobal ?></div>
            </div>
            <div class="stat-cell">
                <div class="stat-num"><?= $prixM2Maison ?></div>
                <div class="stat-lbl">Maisons / m²</div>
                <div class="stat-evol" style="color:<?= pdf_evolColor($evolMaison) ?>"><?= $evolMaison ?></div>
            </div>
            <div class="stat-cell">
                <div class="stat-num"><?= $prixM2Appart ?></div>
                <div class="stat-lbl">Appartements / m²</div>
                <div class="stat-evol" style="color:<?= pdf_evolColor($evolAppart) ?>"><?= $evolAppart ?></div>
            </div>
            <div class="stat-cell">
                <div class="stat-num"><?= $prixM2Terrain ?></div>
                <div class="stat-lbl">Terrains / m²</div>
                <div class="stat-evol" style="color:<?= pdf_evolColor($evolTerrain) ?>"><?= $evolTerrain ?></div>
            </div>
            <div class="stat-cell">
                <div class="stat-num"><?= $nbTransactions ?></div>
                <div class="stat-lbl">Transactions / an</div>
            </div>
            <div class="stat-cell">
                <div class="stat-num"><?= $delaiMoyen ?></div>
                <div class="stat-lbl">Délai moyen de vente</div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════
     PRIX PAR SECTEUR
     ═══════════════════════════════════════════════════ -->
<div class="section grey">
    <div class="container">
        <h2 class="section-title">Prix moyens par commune</h2>
        <p class="section-subtitle"><?= htmlspecialchars($evolPeriode) ?></p>

        <table class="prix-table">
            <thead>
                <tr>
                    <th>Secteur</th>
                    <th>Maisons / m²</th>
                    <th>Appartements / m²</th>
                    <th>Évolution</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($secteurPrix as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['nom']) ?></td>
                    <td><?= htmlspecialchars($s['maison']) ?></td>
                    <td><?= htmlspecialchars($s['appart']) ?></td>
                    <td style="color:<?= pdf_evolColor($s['evol']) ?>;font-weight:700"><?= htmlspecialchars($s['evol']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <p style="font-size:.78rem;color:#718096;text-align:center;margin-top:12px">
            Prix indicatifs au m² — Sources : données notariales, observatoire local
        </p>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════
     TENDANCES
     ═══════════════════════════════════════════════════ -->
<div class="section">
    <div class="container">
        <h2 class="section-title">Tendances du marché local</h2>

        <?php foreach ($tendances as $t): ?>
        <div class="tendance-item">
            <h3><?= htmlspecialchars($t['titre']) ?></h3>
            <p><?= htmlspecialchars($t['texte']) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════
     CONSEILS
     ═══════════════════════════════════════════════════ -->
<div class="section grey">
    <div class="container">
        <h2 class="section-title">Nos conseils</h2>

        <?php foreach ($conseils as $i => $c): ?>
        <div class="conseil-item">
            <div class="conseil-bullet"><?= $i + 1 ?></div>
            <div class="conseil-text"><?= htmlspecialchars($c) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════
     PIED DE PAGE
     ═══════════════════════════════════════════════════ -->
<div class="pdf-footer">
    <p><strong><?= htmlspecialchars($advisorName) ?></strong> — Conseillère immobilière à <?= htmlspecialchars($advisorCity) ?></p>
    <p style="margin-top:8px">Rapport de marché — Mars 2026</p>
    <p style="margin-top:4px">Ce document est fourni à titre indicatif. Les prix reflètent les tendances observées et peuvent varier selon les biens.</p>
</div>

<div style="height:80px"></div><!-- espace pour la barre fixe -->

</body>
</html>
