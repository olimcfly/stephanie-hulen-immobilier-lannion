<?php
$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

$pageTitle   = $fields['page_title']   ?? 'Mentions légales';
$pageContent = $fields['page_content'] ?? 'Contenu légal du site.';

ob_start();
?>
<section class="tp-section-white" style="background: white; padding: 60px 20px;">
    <div class="tp-container" style="max-width: 900px; margin: 0 auto;">
        <h1 <?= $editMode ? 'data-field="page_title" class="ef-zone"' : '' ?> style="font-size: 2.5rem; color: #1a4d7a; margin-bottom: 30px;">
            <?= htmlspecialchars($pageTitle) ?>
        </h1>
        <div <?= $editMode ? 'data-field="page_content" class="ef-zone"' : '' ?> style="line-height: 1.8; color: #666; font-size: 1.05rem;">
            <?= nl2br(htmlspecialchars($pageContent)) ?>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>