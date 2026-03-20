<?php
/**
 * /front/templates/pages/t12-legal.php
 * Template Mentions légales — v2.0
 * Clés $fields : voir $TPL['t12-legal'] dans tpl.php
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

$heroTitle   = $fields['hero_title']   ?? 'Mentions légales';
$lastUpdate  = $fields['last_update']  ?? '';

$editorName   = $fields['editor_name']   ?? $advisorName;
$editorStatus = $fields['editor_status'] ?? '';
$editorSiret  = $fields['editor_siret']  ?? '';
$editorRsac   = $fields['editor_rsac']   ?? '';
$editorAddr   = $fields['editor_addr']   ?? '';
$editorEmail  = $fields['editor_email']  ?? '';
$editorPhone  = $fields['editor_phone']  ?? '';

$hostName = $fields['host_name'] ?? '';
$hostAddr = $fields['host_addr'] ?? '';

$legalContent = $fields['legal_content'] ?? '';

$metaTitle = $page['meta_title']       ?? 'Mentions légales | ' . $advisorName;
$metaDesc  = $page['meta_description'] ?? 'Mentions légales du site de ' . $advisorName . ' à ' . $advisorCity . '.';
$canonical = $siteUrl . '/' . ltrim($page['slug'] ?? 'mentions-legales', '/');
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
.t12-section { margin-bottom:48px; }
.t12-section-title { font-family:var(--tp-ff-display); font-size:1.4rem; font-weight:800; color:var(--tp-primary); margin:0 0 20px; padding-bottom:12px; border-bottom:2px solid var(--tp-accent); display:flex; align-items:center; gap:12px; }
.t12-section-title i { color:var(--tp-accent); font-size:1.1rem; }
.t12-update { font-size:.82rem; color:var(--tp-text3); margin-bottom:40px; }
.t12-info-grid { display:grid; grid-template-columns:160px 1fr; gap:8px 16px; font-size:.9rem; }
.t12-info-label { font-weight:700; color:var(--tp-primary); }
.t12-info-value { color:var(--tp-text2); }
.t12-paragraph { font-size:.9rem; color:var(--tp-text2); line-height:1.8; margin:0 0 14px; }
.t12-paragraph:last-child { margin-bottom:0; }
.t12-list { padding-left:20px; margin:12px 0; }
.t12-list li { font-size:.9rem; color:var(--tp-text2); line-height:1.8; margin-bottom:6px; }
.t12-email-link { color:var(--tp-primary-l); text-decoration:underline; }
.t12-email-link:hover { color:var(--tp-accent-d); }
@media (max-width:600px) {
    .t12-info-grid { grid-template-columns:1fr; gap:4px 0; }
    .t12-info-label { margin-top:10px; }
}
</style>
</head>
<body>
<?php if (function_exists('renderHeader')) echo renderHeader($headerData); ?>
<main class="tp-page">

<!-- HERO -->
<section class="tp-hero" aria-label="Mentions légales">
    <div class="tp-hero-inner">
        <div class="tp-eyebrow">Informations légales</div>
        <h1 class="tp-hero-h1" <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroTitle) ?>
        </h1>
        <p class="tp-hero-sub">
            Conformément aux dispositions des articles 6-III et 19 de la Loi n°2004-575 du 21 juin 2004 pour la Confiance dans l'économie numérique.
        </p>
    </div>
</section>

<!-- CONTENU LEGAL -->
<section class="tp-section-white" aria-label="Contenu légal">
    <div class="tp-container-sm">

        <?php if ($lastUpdate): ?>
        <p class="t12-update" <?= $editMode ? 'data-field="last_update" class="ef-zone"' : '' ?>>
            Dernière mise à jour : <?= htmlspecialchars($lastUpdate) ?>
        </p>
        <?php endif; ?>

        <!-- 1. IDENTITE DE L'EDITEUR -->
        <div class="t12-section">
            <h2 class="t12-section-title"><i class="fa-solid fa-user-tie"></i> Identité de l'éditeur</h2>
            <div class="t12-info-grid">
                <?php if ($editorName): ?>
                <span class="t12-info-label">Nom</span>
                <span class="t12-info-value" <?= $editMode ? 'data-field="editor_name" class="ef-zone"' : '' ?>><?= htmlspecialchars($editorName) ?></span>
                <?php endif; ?>

                <?php if ($editorStatus): ?>
                <span class="t12-info-label">Statut juridique</span>
                <span class="t12-info-value" <?= $editMode ? 'data-field="editor_status" class="ef-zone"' : '' ?>><?= htmlspecialchars($editorStatus) ?></span>
                <?php endif; ?>

                <?php if ($editorSiret): ?>
                <span class="t12-info-label">SIRET</span>
                <span class="t12-info-value" <?= $editMode ? 'data-field="editor_siret" class="ef-zone"' : '' ?>><?= htmlspecialchars($editorSiret) ?></span>
                <?php endif; ?>

                <?php if ($editorRsac): ?>
                <span class="t12-info-label">Carte professionnelle</span>
                <span class="t12-info-value" <?= $editMode ? 'data-field="editor_rsac" class="ef-zone"' : '' ?>><?= htmlspecialchars($editorRsac) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 2. ADRESSE -->
        <div class="t12-section">
            <h2 class="t12-section-title"><i class="fa-solid fa-location-dot"></i> Adresse professionnelle</h2>
            <div class="t12-info-grid">
                <?php if ($editorAddr): ?>
                <span class="t12-info-label">Adresse</span>
                <span class="t12-info-value" <?= $editMode ? 'data-field="editor_addr" class="ef-zone"' : '' ?>><?= htmlspecialchars($editorAddr) ?></span>
                <?php endif; ?>

                <?php if ($editorEmail): ?>
                <span class="t12-info-label">Email</span>
                <span class="t12-info-value" <?= $editMode ? 'data-field="editor_email" class="ef-zone"' : '' ?>><a href="mailto:<?= htmlspecialchars($editorEmail) ?>" class="t12-email-link"><?= htmlspecialchars($editorEmail) ?></a></span>
                <?php endif; ?>

                <?php if ($editorPhone): ?>
                <span class="t12-info-label">Téléphone</span>
                <span class="t12-info-value" <?= $editMode ? 'data-field="editor_phone" class="ef-zone"' : '' ?>><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $editorPhone)) ?>" class="t12-email-link"><?= htmlspecialchars($editorPhone) ?></a></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. HEBERGEUR -->
        <div class="t12-section">
            <h2 class="t12-section-title"><i class="fa-solid fa-server"></i> Hébergeur</h2>
            <div class="t12-info-grid">
                <?php if ($hostName): ?>
                <span class="t12-info-label">Hébergeur</span>
                <span class="t12-info-value" <?= $editMode ? 'data-field="host_name" class="ef-zone"' : '' ?>><?= htmlspecialchars($hostName) ?></span>
                <?php endif; ?>

                <?php if ($hostAddr): ?>
                <span class="t12-info-label">Adresse</span>
                <span class="t12-info-value" <?= $editMode ? 'data-field="host_addr" class="ef-zone"' : '' ?>><?= htmlspecialchars($hostAddr) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 4. RGPD / POLITIQUE DE CONFIDENTIALITE -->
        <div class="t12-section">
            <h2 class="t12-section-title"><i class="fa-solid fa-shield-halved"></i> Protection des données personnelles (RGPD)</h2>
            <p class="t12-paragraph">
                Conformément au Règlement Général sur la Protection des Données (RGPD) du 25 mai 2018 et à la loi « Informatique et Libertés » du 6 janvier 1978 modifiée, vous disposez des droits suivants concernant vos données personnelles :
            </p>
            <ul class="t12-list">
                <li><strong>Droit d'accès :</strong> vous pouvez obtenir la confirmation que des données vous concernant sont traitées et en obtenir une copie.</li>
                <li><strong>Droit de rectification :</strong> vous pouvez demander la correction de données inexactes ou incomplètes.</li>
                <li><strong>Droit à l'effacement :</strong> vous pouvez demander la suppression de vos données dans les conditions prévues par la réglementation.</li>
                <li><strong>Droit à la limitation :</strong> vous pouvez demander la limitation du traitement de vos données.</li>
                <li><strong>Droit d'opposition :</strong> vous pouvez vous opposer au traitement de vos données pour des motifs légitimes.</li>
                <li><strong>Droit à la portabilité :</strong> vous pouvez recevoir vos données dans un format structuré et couramment utilisé.</li>
            </ul>
            <p class="t12-paragraph">
                Les données personnelles collectées via les formulaires de ce site (nom, prénom, email, téléphone, message) sont destinées exclusivement à <?= htmlspecialchars($editorName) ?> pour le traitement de votre demande. Elles ne sont ni cédées ni vendues à des tiers.
            </p>
            <p class="t12-paragraph">
                La durée de conservation des données est de 3 ans à compter du dernier contact.
            </p>
            <?php if ($editorEmail): ?>
            <p class="t12-paragraph">
                Pour exercer vos droits, vous pouvez adresser votre demande par email à <a href="mailto:<?= htmlspecialchars($editorEmail) ?>" class="t12-email-link"><?= htmlspecialchars($editorEmail) ?></a> ou par courrier à l'adresse indiquée ci-dessus.
            </p>
            <?php endif; ?>
            <p class="t12-paragraph">
                En cas de litige, vous pouvez introduire une réclamation auprès de la CNIL (Commission Nationale de l'Informatique et des Libertés).
            </p>
        </div>

        <!-- 5. COOKIES -->
        <div class="t12-section">
            <h2 class="t12-section-title"><i class="fa-solid fa-cookie-bite"></i> Politique de cookies</h2>
            <p class="t12-paragraph">
                Ce site peut utiliser des cookies pour améliorer l'expérience de navigation. Un cookie est un petit fichier texte stocké sur votre terminal (ordinateur, tablette, smartphone) lors de la consultation d'un site internet.
            </p>
            <p class="t12-paragraph"><strong>Types de cookies utilisés :</strong></p>
            <ul class="t12-list">
                <li><strong>Cookies strictement nécessaires :</strong> indispensables au fonctionnement du site, ils ne peuvent pas être désactivés.</li>
                <li><strong>Cookies analytiques :</strong> permettent de mesurer l'audience du site et d'analyser la navigation afin d'améliorer les performances et le contenu.</li>
            </ul>
            <p class="t12-paragraph">
                Vous pouvez à tout moment paramétrer vos préférences en matière de cookies via les réglages de votre navigateur. Le refus de cookies peut limiter l'accès à certaines fonctionnalités du site.
            </p>
            <p class="t12-paragraph">
                Pour en savoir plus sur les cookies et leur gestion, vous pouvez consulter le site de la CNIL.
            </p>
        </div>

        <!-- CONTENU SUPPLEMENTAIRE (éditable via l'admin) -->
        <?php if ($legalContent): ?>
        <div class="t12-section">
            <h2 class="t12-section-title"><i class="fa-solid fa-file-lines"></i> Informations complémentaires</h2>
            <div class="tp-rich-body" <?= $editMode ? 'data-field="legal_content" class="ef-zone ef-rich"' : '' ?>><?= $legalContent ?></div>
        </div>
        <?php endif; ?>

    </div>
</section>

</main>
<?php if (function_exists('renderFooter')) echo renderFooter($footerData); ?>
</body>
</html>
