<?php
/**
 * /front/templates/pages/t5-article.php
 * Template Article — CONVERTI pour layout-page.php
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

$advisorName = $advisor['name'] ?? ($site['name'] ?? 'Conseiller');
require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

// ════════════════════════════════════════════════
// CHAMPS SPÉCIFIQUES
// ════════════════════════════════════════════════

$articleTitle     = $fields['article_title']     ?? 'Titre de l\'article';
$articleDate      = $fields['article_date']      ?? date('d/m/Y');
$articleAuthor    = $fields['article_author']    ?? $advisorName;
$articleCategory  = $fields['article_category']  ?? 'Actualités';
$articleImage     = $fields['article_image']     ?? '';
$articleContent   = $fields['article_content']   ?? 'Contenu de l\'article';

// ════════════════════════════════════════════════
// CONTENU HTML
// ════════════════════════════════════════════════

ob_start();
?>

<!-- ARTICLE -->
<article class="tp-section-white" style="background: white; padding: 60px 20px;">
    <div class="tp-container" style="max-width: 800px; margin: 0 auto;">
        
        <!-- HEADER -->
        <header style="margin-bottom: 40px; border-bottom: 2px solid #d4a574; padding-bottom: 30px;">
            <h1 <?= $editMode ? 'data-field="article_title" class="ef-zone"' : '' ?> style="font-size: 2.5rem; color: #1a4d7a; margin-bottom: 20px; line-height: 1.3;">
                <?= htmlspecialchars($articleTitle) ?>
            </h1>
            
            <!-- META -->
            <div style="display: flex; gap: 20px; flex-wrap: wrap; color: #999; font-size: 0.95rem;">
                <span style="display: flex; align-items: center; gap: 5px;">
                    📅 <span <?= $editMode ? 'data-field="article_date" class="ef-zone"' : '' ?>>
                        <?= htmlspecialchars($articleDate) ?>
                    </span>
                </span>
                <span style="display: flex; align-items: center; gap: 5px;">
                    ✍️ <span <?= $editMode ? 'data-field="article_author" class="ef-zone"' : '' ?>>
                        Par <?= htmlspecialchars($articleAuthor) ?>
                    </span>
                </span>
                <span style="display: flex; align-items: center; gap: 5px;">
                    🏷️ <span <?= $editMode ? 'data-field="article_category" class="ef-zone"' : '' ?>>
                        <?= htmlspecialchars($articleCategory) ?>
                    </span>
                </span>
            </div>
        </header>

        <!-- IMAGE (optionnel) -->
        <?php if ($articleImage): ?>
        <figure style="margin-bottom: 40px;">
            <img src="<?= htmlspecialchars($articleImage) ?>" alt="<?= htmlspecialchars($articleTitle) ?>" style="width: 100%; height: auto; border-radius: 8px;">
        </figure>
        <?php endif; ?>

        <!-- CONTENU -->
        <div <?= $editMode ? 'data-field="article_content" class="ef-zone"' : '' ?> style="line-height: 1.8; color: #333; font-size: 1.05rem; margin-bottom: 40px;">
            <?= nl2br(htmlspecialchars($articleContent)) ?>
        </div>

        <!-- FOOTER -->
        <footer style="background: #f9f6f3; padding: 30px; border-radius: 8px; border-left: 4px solid #d4a574;">
            <p style="margin: 0; color: #666;">
                <strong>À propos de l\'auteur:</strong><br>
                <?= htmlspecialchars($articleAuthor) ?> est un conseiller immobilier expérimenté avec une passion pour aider ses clients à trouver le bien idéal.
            </p>
        </footer>

    </div>
</article>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>