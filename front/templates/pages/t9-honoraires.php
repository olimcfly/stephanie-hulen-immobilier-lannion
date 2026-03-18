<?php
/**
 * /front/templates/pages/t9-honoraires.php
 * Template Honoraires — v2.0 générique
 * Clés $fields : voir $TPL['t9-honoraires'] dans edit.php
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

$tarifIntro  = $fields['tarif_intro']  ?? '';
$tarifs = [];
for ($i = 1; $i <= 3; $i++) {
    $lbl = $fields["tarif{$i}_label"] ?? '';
    $val = $fields["tarif{$i}_value"] ?? '';
    if ($lbl || $val) $tarifs[] = ['label'=>$lbl,'value'=>$val,'kl'=>"tarif{$i}_label",'kv'=>"tarif{$i}_value"];
}
$tarifNote   = $fields['tarif_note']   ?? 'Barème affiché conformément à la loi Alur.';
$legalContent = $fields['legal_content'] ?? '';

$ctaTitle   = $fields['cta_title']    ?? 'Des questions sur mes honoraires ?';
$ctaBtnText = $fields['cta_btn_text'] ?? 'Me contacter';
$ctaBtnUrl  = $fields['cta_btn_url']  ?? $siteUrl . '/contact';

$metaTitle = $page['meta_title']       ?? 'Honoraires | ' . $advisorName . ' — ' . $advisorCity;
$metaDesc  = $page['meta_description'] ?? 'Honoraires immobiliers transparents de ' . $advisorName . ' à ' . $advisorCity . '.';
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
.t9-tarif-table { width:100%; border-collapse:collapse; margin-top:32px; border-radius:var(--tp-radius); overflow:hidden; box-shadow:var(--tp-shadow); }
.t9-tarif-table th { background:var(--tp-primary); color:var(--tp-white); padding:16px 24px; text-align:left; font-size:.85rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; }
.t9-tarif-table td { padding:18px 24px; border-bottom:1px solid var(--tp-border); background:var(--tp-white); font-size:.9rem; }
.t9-tarif-table tr:last-child td { border-bottom:none; }
.t9-tarif-table tr:nth-child(even) td { background:var(--tp-bg); }
.t9-tarif-value { font-family:var(--tp-ff-display); font-size:1.15rem; font-weight:900; color:var(--tp-accent-d); }
.t9-tarif-note { margin-top:16px; padding:16px 20px; background:rgba(200,169,110,.08); border-left:4px solid var(--tp-accent); border-radius:0 8px 8px 0; font-size:.8rem; color:var(--tp-text2); line-height:1.6; }
.t9-legal { margin-top:60px; padding-top:40px; border-top:1px solid var(--tp-border); }
.t9-legal-title { font-family:var(--tp-ff-display); font-size:1.3rem; font-weight:800; color:var(--tp-primary); margin-bottom:20px; }
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

<!-- TARIFS -->
<section class="tp-section-white" aria-label="Grille tarifaire">
    <div class="tp-container-sm">
        <?php if ($tarifIntro): ?>
        <div class="tp-rich-body" <?= $editMode ? 'data-field="tarif_intro" class="ef-zone ef-rich"' : '' ?>><?= $tarifIntro ?></div>
        <?php endif; ?>
        <?php if (!empty($tarifs)): ?>
        <table class="t9-tarif-table" aria-label="Grille tarifaire">
            <thead><tr><th>Prestation</th><th>Tarif</th></tr></thead>
            <tbody>
                <?php foreach ($tarifs as $t): ?>
                <tr>
                    <td <?= $editMode ? 'data-field="'.$t['kl'].'" class="ef-zone"' : '' ?>><?= htmlspecialchars($t['label']) ?></td>
                    <td><span class="t9-tarif-value" <?= $editMode ? 'data-field="'.$t['kv'].'" class="ef-zone"' : '' ?>><?= htmlspecialchars($t['value']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($tarifNote): ?>
        <div class="t9-tarif-note" <?= $editMode ? 'data-field="tarif_note" class="ef-zone"' : '' ?>><?= htmlspecialchars($tarifNote) ?></div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($legalContent): ?>
        <div class="t9-legal">
            <h2 class="t9-legal-title">Mentions légales</h2>
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