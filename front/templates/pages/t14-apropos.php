<?php
$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

$advisorName = $advisor['name'] ?? ($site['name'] ?? 'Conseiller');
require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

$heroTitle    = $fields['hero_title']    ?? 'À propos de ' . htmlspecialchars($advisorName);
$heroSubtitle = $fields['hero_subtitle'] ?? 'Mon histoire et mon expertise immobilière';
$aboutContent = $fields['about_content'] ?? 'Contenu à propos.';

ob_start();
?>
<section class="tp-section-hero" style="background: linear-gradient(135deg, #1a4d7a 0%, #0f3a5a 100%); color: white; padding: 80px 20px; text-align: center;">
    <div class="tp-container">
        <h1 <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?> style="font-size: 3rem; margin-bottom: 20px; font-weight: bold;">
            <?= htmlspecialchars($heroTitle) ?>
        </h1>
        <p <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?> style="font-size: 1.3rem; margin-bottom: 30px; opacity: 0.95;">
            <?= htmlspecialchars($heroSubtitle) ?>
        </p>
    </div>
</section>

<section class="tp-section-white" style="background: white; padding: 80px 20px;">
    <div class="tp-container" style="max-width: 900px; margin: 0 auto;">
        <div <?= $editMode ? 'data-field="about_content" class="ef-zone"' : '' ?> style="line-height: 1.8; color: #666; font-size: 1.05rem;">
            <?= nl2br(htmlspecialchars($aboutContent)) ?>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>