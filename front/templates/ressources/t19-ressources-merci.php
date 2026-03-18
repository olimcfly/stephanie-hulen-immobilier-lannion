<?php
/**
 * /front/templates/ressources/t19-ressources-merci.php
 * Template Thank You — Page de remerciement après téléchargement
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$page       = $page       ?? [];
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;

$siteUrl   = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$guideName = isset($_GET['guide']) ? htmlspecialchars($_GET['guide']) : 'votre ressource';

// ════════════════════════════════════════════════════════════════════════════════
// CHAMPS ÉDITABLES
// ════════════════════════════════════════════════════════════════════════════════

$heroTitle    = $fields['hero_title']    ?? 'Merci pour votre téléchargement !';
$heroSubtitle = $fields['hero_subtitle'] ?? 'Votre ressource est en chemin';
$heroText     = $fields['hero_text']     ?? 'Vérifiez votre boîte email pour accéder à ' . $guideName . '.';

$nextTitle    = $fields['next_title']    ?? 'Que faire maintenant ?';

$step1Title   = $fields['step1_title']   ?? 'Consultez votre email';
$step1Text    = $fields['step1_text']    ?? 'Vous allez recevoir un email avec le lien de téléchargement.';
$step1Icon    = $fields['step1_icon']    ?? '📧';

$step2Title   = $fields['step2_title']   ?? 'Lisez la ressource';
$step2Text    = $fields['step2_text']    ?? 'Parcourez le contenu à votre rythme et notez vos questions.';
$step2Icon    = $fields['step2_icon']    ?? '📖';

$step3Title   = $fields['step3_title']   ?? 'Contactez-moi';
$step3Text    = $fields['step3_text']    ?? 'Si vous avez des questions, n\'hésitez pas à me contacter.';
$step3Icon    = $fields['step3_icon']    ?? '💬';

$ctaTitle     = $fields['cta_title']     ?? 'Vous avez des questions ?';
$ctaBtnText   = $fields['cta_btn_text']  ?? 'Me contacter';
$ctaBtnUrl    = $fields['cta_btn_url']   ?? $siteUrl . '/contact';

$backBtnText  = $fields['back_btn_text'] ?? 'Voir d\'autres ressources';
$backBtnUrl   = $fields['back_btn_url']  ?? $siteUrl . '/ressources';

$metaTitle = 'Merci pour votre téléchargement';
$metaDesc  = 'Votre ressource a été envoyée à votre adresse email.';
$canonical = $siteUrl . '/ressources/merci';

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
.thankyou-hero { background:linear-gradient(135deg, var(--tp-accent) 0%, var(--tp-accent-d) 100%); color:white; padding:80px 20px; text-align:center; border-radius:var(--tp-radius); margin-bottom:60px; }
.thankyou-hero h1 { font-family:var(--tp-ff-display); font-size:2.8rem; font-weight:800; margin-bottom:16px; }
.thankyou-icon { font-size:4rem; margin-bottom:20px; animation:bounce 2s infinite; }
@keyframes bounce { 0%, 100% { transform:translateY(0); } 50% { transform:translateY(-10px); } }
.thankyou-subtitle { font-size:1.3rem; font-weight:600; margin-bottom:12px; }
.thankyou-text { font-size:1.05rem; opacity:.95; }

.steps-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); gap:32px; margin-bottom:60px; }
.step-card { background:white; border:1px solid var(--tp-border); border-radius:var(--tp-radius); padding:32px; text-align:center; }
.step-icon { font-size:2.5rem; margin-bottom:16px; }
.step-title { font-family:var(--tp-ff-display); font-size:1.2rem; font-weight:800; color:var(--tp-primary); margin-bottom:12px; }
.step-text { color:var(--tp-text2); line-height:1.6; }

.action-buttons { display:flex; gap:16px; justify-content:center; flex-wrap:wrap; margin-top:40px; }
.btn { padding:14px 32px; border-radius:8px; font-weight:700; text-decoration:none; transition:all .2s; display:inline-flex; align-items:center; gap:8px; }
.btn-primary { background:var(--tp-accent); color:white; }
.btn-primary:hover { background:var(--tp-accent-d); }
.btn-secondary { background:var(--tp-primary); color:white; }
.btn-secondary:hover { background:var(--tp-primary-dark); }
</style>
</head>
<body>
<?php if (function_exists('renderHeader')) echo renderHeader($headerData); ?>
<main class="tp-page">

<section class="tp-section-white">
    <div class="tp-container-sm">
        <div class="thankyou-hero">
            <div class="thankyou-icon">✅</div>
            <h1 <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($heroTitle) ?>
            </h1>
            <p class="thankyou-subtitle" <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($heroSubtitle) ?>
            </p>
            <p class="thankyou-text" <?= $editMode ? 'data-field="hero_text" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($heroText) ?>
            </p>
        </div>
    </div>
</section>

<section class="tp-section-white">
    <div class="tp-container">
        <h2 style="font-family:var(--tp-ff-display); font-size:2rem; font-weight:800; color:var(--tp-primary); text-align:center; margin-bottom:48px;" <?= $editMode ? 'data-field="next_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($nextTitle) ?>
        </h2>

        <div class="steps-grid">
            <div class="step-card">
                <div class="step-icon" <?= $editMode ? 'data-field="step1_icon" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step1Icon) ?>
                </div>
                <h3 class="step-title" <?= $editMode ? 'data-field="step1_title" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step1Title) ?>
                </h3>
                <p class="step-text" <?= $editMode ? 'data-field="step1_text" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step1Text) ?>
                </p>
            </div>

            <div class="step-card">
                <div class="step-icon" <?= $editMode ? 'data-field="step2_icon" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step2Icon) ?>
                </div>
                <h3 class="step-title" <?= $editMode ? 'data-field="step2_title" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step2Title) ?>
                </h3>
                <p class="step-text" <?= $editMode ? 'data-field="step2_text" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step2Text) ?>
                </p>
            </div>

            <div class="step-card">
                <div class="step-icon" <?= $editMode ? 'data-field="step3_icon" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step3Icon) ?>
                </div>
                <h3 class="step-title" <?= $editMode ? 'data-field="step3_title" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step3Title) ?>
                </h3>
                <p class="step-text" <?= $editMode ? 'data-field="step3_text" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($step3Text) ?>
                </p>
            </div>
        </div>
    </div>
</section>

<section class="tp-cta-section">
    <div class="tp-container">
        <h2 class="tp-cta-title" <?= $editMode ? 'data-field="cta_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($ctaTitle) ?>
        </h2>
        <div class="action-buttons">
            <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="btn btn-primary" <?= $editMode ? 'data-field="cta_btn_text" class="ef-zone"' : '' ?>>
                <i class="fas fa-envelope"></i> <?= htmlspecialchars($ctaBtnText) ?>
            </a>
            <a href="<?= htmlspecialchars($backBtnUrl) ?>" class="btn btn-secondary" <?= $editMode ? 'data-field="back_btn_text" class="ef-zone"' : '' ?>>
                <i class="fas fa-arrow-left"></i> <?= htmlspecialchars($backBtnText) ?>
            </a>
        </div>
    </div>
</section>

</main>
<?php if (function_exists('renderFooter')) echo renderFooter($footerData); ?>
</body>
</html>