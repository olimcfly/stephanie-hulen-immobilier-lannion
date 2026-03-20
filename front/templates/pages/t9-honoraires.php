<?php
/**
 * /front/templates/pages/t9-honoraires.php
 * Template Honoraires — v3.0 conformité loi Alur
 * Barème détaillé : vente, location, gestion locative
 * Clés $fields : voir $TPL['t9-honoraires'] dans tpl.php
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;

$advisorName = $advisor['name'] ?? ($site['name'] ?? 'Votre conseiller');
$advisorCity = $advisor['city'] ?? ($site['city'] ?? 'votre ville');
$siteUrl     = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

$heroTitle    = $fields['hero_title']    ?? 'Honoraires et tarifs';
$heroSubtitle = $fields['hero_subtitle'] ?? 'Transparence totale sur mes honoraires, conformément à la loi Alur.';

/* --- Vente --- */
$venteIntro  = $fields['vente_intro'] ?? '';
$venteTranches = [];
for ($i = 1; $i <= 4; $i++) {
    $lbl = $fields["vente_tranche{$i}_label"] ?? '';
    $val = $fields["vente_tranche{$i}_rate"]  ?? '';
    if ($lbl || $val) $venteTranches[] = ['label'=>$lbl,'value'=>$val,'kl'=>"vente_tranche{$i}_label",'kv'=>"vente_tranche{$i}_rate"];
}
$venteCharge = $fields['vente_charge'] ?? 'Honoraires à la charge de l\'acquéreur';
$venteNote   = $fields['vente_note']   ?? '';

/* --- Location --- */
$locationIntro = $fields['location_intro'] ?? '';
$locationLines = [];
$locKeys = ['visite','bail','etat'];
$locDefaults = [
    'visite' => ['Visite du bien et constitution du dossier', '8 € TTC / m² de surface habitable'],
    'bail'   => ['Rédaction du bail',                         '8 € TTC / m² de surface habitable'],
    'etat'   => ['Établissement de l\'état des lieux',        '3 € TTC / m² de surface habitable'],
];
foreach ($locKeys as $k) {
    $lbl = $fields["location_{$k}_label"] ?? $locDefaults[$k][0];
    $val = $fields["location_{$k}_rate"]  ?? $locDefaults[$k][1];
    if ($lbl || $val) $locationLines[] = ['label'=>$lbl,'value'=>$val,'kl'=>"location_{$k}_label",'kv'=>"location_{$k}_rate"];
}
$locationCharge  = $fields['location_charge']       ?? 'Honoraires partagés entre le bailleur et le locataire (part locataire plafonnée par décret)';
$locationPlafond = $fields['location_plafond_note'] ?? 'Plafonds réglementaires applicables au locataire (décret n° 2014-890 du 1er août 2014) : zone très tendue : 12 € / m² ; zone tendue : 10 € / m² ; zone non tendue : 8 € / m². Lannion se situe en zone non tendue.';

/* --- Gestion locative --- */
$gestionIntro  = $fields['gestion_intro']  ?? '';
$gestionRate   = $fields['gestion_rate']   ?? '';
$gestionInclus = $fields['gestion_inclus'] ?? '';
$gestionNote   = $fields['gestion_note']   ?? '';

/* --- Mentions légales --- */
$legalContent = $fields['legal_content'] ?? '';

/* --- CTA --- */
$ctaTitle   = $fields['cta_title']    ?? 'Des questions sur mes honoraires ?';
$ctaBtnText = $fields['cta_btn_text'] ?? 'Me contacter';
$ctaBtnUrl  = $fields['cta_btn_url']  ?? $siteUrl . '/contact';

$metaTitle = $page['meta_title']       ?? 'Honoraires | ' . $advisorName . ' — ' . $advisorCity;
$metaDesc  = $page['meta_description'] ?? 'Barème des honoraires immobiliers de ' . $advisorName . ' à ' . $advisorCity . ' — vente, location, gestion locative. Conformité loi Alur.';
$canonical = $siteUrl . '/' . ltrim($page['slug'] ?? 'honoraires', '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($metaTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<?php require_once __DIR__ . '/_tpl-common.php'; ?>
<style>
/* ── Tarif tables ── */
.t9-tarif-table { width:100%; border-collapse:collapse; margin-top:24px; border-radius:var(--tp-radius); overflow:hidden; box-shadow:var(--tp-shadow); }
.t9-tarif-table th { background:var(--tp-primary); color:var(--tp-white); padding:14px 24px; text-align:left; font-size:.82rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
.t9-tarif-table td { padding:16px 24px; border-bottom:1px solid var(--tp-border); background:var(--tp-white); font-size:.9rem; }
.t9-tarif-table tr:last-child td { border-bottom:none; }
.t9-tarif-table tr:nth-child(even) td { background:var(--tp-bg); }
.t9-tarif-value { font-family:var(--tp-ff-display); font-size:1.1rem; font-weight:900; color:var(--tp-accent-d); }
.t9-tarif-charge { margin-top:12px; padding:10px 20px; background:var(--tp-bg); border-radius:8px; font-size:.82rem; color:var(--tp-text2); font-weight:500; }
.t9-tarif-note { margin-top:12px; padding:14px 20px; background:rgba(200,169,110,.08); border-left:4px solid var(--tp-accent); border-radius:0 8px 8px 0; font-size:.8rem; color:var(--tp-text2); line-height:1.6; }
/* ── Section titles ── */
.t9-section-title { font-family:var(--tp-ff-display); font-size:1.5rem; font-weight:800; color:var(--tp-primary); margin:0 0 8px; display:flex; align-items:center; gap:12px; }
.t9-section-title i { font-size:1.1rem; color:var(--tp-accent); }
.t9-section-block { margin-bottom:56px; }
.t9-section-block:last-of-type { margin-bottom:0; }
/* ── Gestion rate highlight ── */
.t9-gestion-rate { display:inline-block; padding:16px 32px; background:var(--tp-primary); color:var(--tp-white); font-family:var(--tp-ff-display); font-size:1.4rem; font-weight:800; border-radius:var(--tp-radius); margin:16px 0; box-shadow:var(--tp-shadow); }
/* ── Legal section ── */
.t9-legal { margin-top:60px; padding-top:40px; border-top:1px solid var(--tp-border); }
.t9-legal-title { font-family:var(--tp-ff-display); font-size:1.3rem; font-weight:800; color:var(--tp-primary); margin-bottom:20px; }
/* ── Alur badge ── */
.t9-alur-badge { display:inline-flex; align-items:center; gap:8px; padding:8px 16px; background:rgba(16,185,129,.1); color:#059669; border-radius:20px; font-size:.78rem; font-weight:600; margin-bottom:24px; }
</style>
</head>
<body>
<?php if (function_exists('renderHeader')) echo renderHeader($headerData); ?>
<main class="tp-page">

<!-- HERO -->
<section class="tp-hero" aria-label="Honoraires">
    <div class="tp-hero-inner">
        <div class="tp-eyebrow">Transparence des honoraires</div>
        <h1 class="tp-hero-h1" <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroTitle) ?>
        </h1>
        <p class="tp-hero-sub" <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroSubtitle) ?>
        </p>
    </div>
</section>

<!-- BARÈME -->
<section class="tp-section-white" aria-label="Barème des honoraires">
    <div class="tp-container-sm">

        <div class="t9-alur-badge"><i class="fa-solid fa-shield-check"></i> Barème affiché conformément à la loi Alur</div>

        <!-- ═══ VENTE ═══ -->
        <?php if (!empty($venteTranches)): ?>
        <div class="t9-section-block">
            <h2 class="t9-section-title"><i class="fa-solid fa-house-chimney"></i> Honoraires de vente</h2>

            <?php if ($venteIntro): ?>
            <div class="tp-rich-body" <?= $editMode ? 'data-field="vente_intro" class="ef-zone ef-rich"' : '' ?>><?= $venteIntro ?></div>
            <?php endif; ?>

            <table class="t9-tarif-table" aria-label="Barème honoraires de vente">
                <thead><tr><th>Tranche de prix</th><th>Honoraires TTC</th></tr></thead>
                <tbody>
                    <?php foreach ($venteTranches as $t): ?>
                    <tr>
                        <td <?= $editMode ? 'data-field="'.$t['kl'].'" class="ef-zone"' : '' ?>><?= htmlspecialchars($t['label']) ?></td>
                        <td><span class="t9-tarif-value" <?= $editMode ? 'data-field="'.$t['kv'].'" class="ef-zone"' : '' ?>><?= htmlspecialchars($t['value']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="t9-tarif-charge" <?= $editMode ? 'data-field="vente_charge" class="ef-zone"' : '' ?>>
                <i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($venteCharge) ?>
            </div>

            <?php if ($venteNote): ?>
            <div class="t9-tarif-note" <?= $editMode ? 'data-field="vente_note" class="ef-zone"' : '' ?>><?= htmlspecialchars($venteNote) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ═══ LOCATION ═══ -->
        <?php if (!empty($locationLines)): ?>
        <div class="t9-section-block">
            <h2 class="t9-section-title"><i class="fa-solid fa-key"></i> Honoraires de location</h2>

            <?php if ($locationIntro): ?>
            <div class="tp-rich-body" <?= $editMode ? 'data-field="location_intro" class="ef-zone ef-rich"' : '' ?>><?= $locationIntro ?></div>
            <?php endif; ?>

            <table class="t9-tarif-table" aria-label="Barème honoraires de location">
                <thead><tr><th>Prestation</th><th>Tarif TTC</th></tr></thead>
                <tbody>
                    <?php foreach ($locationLines as $t): ?>
                    <tr>
                        <td <?= $editMode ? 'data-field="'.$t['kl'].'" class="ef-zone"' : '' ?>><?= htmlspecialchars($t['label']) ?></td>
                        <td><span class="t9-tarif-value" <?= $editMode ? 'data-field="'.$t['kv'].'" class="ef-zone"' : '' ?>><?= htmlspecialchars($t['value']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="t9-tarif-charge" <?= $editMode ? 'data-field="location_charge" class="ef-zone"' : '' ?>>
                <i class="fa-solid fa-circle-info"></i> <?= htmlspecialchars($locationCharge) ?>
            </div>

            <?php if ($locationPlafond): ?>
            <div class="t9-tarif-note" <?= $editMode ? 'data-field="location_plafond_note" class="ef-zone"' : '' ?>><?= htmlspecialchars($locationPlafond) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ═══ GESTION LOCATIVE ═══ -->
        <?php if ($gestionRate): ?>
        <div class="t9-section-block">
            <h2 class="t9-section-title"><i class="fa-solid fa-building"></i> Gestion locative</h2>

            <?php if ($gestionIntro): ?>
            <div class="tp-rich-body" <?= $editMode ? 'data-field="gestion_intro" class="ef-zone ef-rich"' : '' ?>><?= $gestionIntro ?></div>
            <?php endif; ?>

            <div class="t9-gestion-rate" <?= $editMode ? 'data-field="gestion_rate" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($gestionRate) ?>
            </div>

            <?php if ($gestionInclus): ?>
            <div class="tp-rich-body" <?= $editMode ? 'data-field="gestion_inclus" class="ef-zone ef-rich"' : '' ?>><?= $gestionInclus ?></div>
            <?php endif; ?>

            <?php if ($gestionNote): ?>
            <div class="t9-tarif-note" <?= $editMode ? 'data-field="gestion_note" class="ef-zone"' : '' ?>><?= htmlspecialchars($gestionNote) ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ═══ MENTIONS LÉGALES ═══ -->
        <?php if ($legalContent): ?>
        <div class="t9-legal">
            <h2 class="t9-legal-title"><i class="fa-solid fa-scale-balanced"></i> Mentions légales obligatoires</h2>
            <div class="tp-rich-body" <?= $editMode ? 'data-field="legal_content" class="ef-zone ef-rich"' : '' ?>><?= $legalContent ?></div>
        </div>
        <?php endif; ?>

    </div>
</section>

<!-- CTA -->
<section class="tp-cta-section" aria-label="CTA">
    <div class="tp-container">
        <h2 class="tp-cta-title" <?= $editMode ? 'data-field="cta_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($ctaTitle) ?></h2>
        <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="tp-cta-btn" <?= $editMode ? 'data-field="cta_btn_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($ctaBtnText) ?></a>
    </div>
</section>

</main>
<?php if (function_exists('renderFooter')) echo renderFooter($footerData); ?>
</body>
</html>
