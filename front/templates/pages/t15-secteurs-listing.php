<?php
$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

$heroTitle    = $fields['hero_title']    ?? 'Nos secteurs d\'intervention';
$heroSubtitle = $fields['hero_subtitle'] ?? 'Expertise locale dans tous les quartiers';
$introText    = $fields['intro_text']    ?? 'Nous couvrons une large zone géographique.';

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

<section class="tp-section-white" style="background: white; padding: 60px 20px; text-align: center;">
    <div class="tp-container" style="max-width: 800px; margin: 0 auto;">
        <p <?= $editMode ? 'data-field="intro_text" class="ef-zone"' : '' ?> style="font-size: 1.1rem; color: #666; line-height: 1.8;">
            <?= htmlspecialchars($introText) ?>
        </p>
    </div>
</section>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>