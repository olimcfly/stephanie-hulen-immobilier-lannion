<?php
$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

$bienTitle       = $fields['bien_title']       ?? 'Bien immobilier';
$bienPrice       = $fields['bien_price']       ?? '0 €';
$bienDescription = $fields['bien_description'] ?? 'Description détaillée du bien.';
$bienFeatures    = $fields['bien_features']    ?? '• Pièces\n• Surface\n• Localisation';

ob_start();
?>
<section class="tp-section-white" style="background: white; padding: 80px 20px;">
    <div class="tp-container" style="max-width: 900px; margin: 0 auto;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: start;">
            <div>
                <div style="background: #f9f6f3; padding: 60px; border-radius: 8px; text-align: center; color: #999;">
                    [Images du bien]
                </div>
            </div>
            <div>
                <h1 <?= $editMode ? 'data-field="bien_title" class="ef-zone"' : '' ?> style="font-size: 2.5rem; color: #1a4d7a; margin-bottom: 20px;">
                    <?= htmlspecialchars($bienTitle) ?>
                </h1>
                <div style="font-size: 1.8rem; color: #d4a574; font-weight: bold; margin-bottom: 30px;">
                    <?= htmlspecialchars($bienPrice) ?>
                </div>
                <h3 style="font-size: 1.3rem; color: #1a4d7a; margin-bottom: 10px;">Description</h3>
                <p <?= $editMode ? 'data-field="bien_description" class="ef-zone"' : '' ?> style="color: #666; line-height: 1.8; margin-bottom: 30px;">
                    <?= nl2br(htmlspecialchars($bienDescription)) ?>
                </p>
                <h3 style="font-size: 1.3rem; color: #1a4d7a; margin-bottom: 10px;">Caractéristiques</h3>
                <div <?= $editMode ? 'data-field="bien_features" class="ef-zone"' : '' ?> style="color: #666; line-height: 1.8;">
                    <?= nl2br(htmlspecialchars($bienFeatures)) ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>