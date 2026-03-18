<?php
/**
 * /front/templates/ressources/t17-ressources-listing.php
 * Template Ressources Hub — Page listing tous les guides
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$page       = $page       ?? [];
$pdo        = $pdo        ?? null;
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;

$advisorName = $advisor['name'] ?? ($site['name'] ?? 'Votre conseiller');
$siteUrl     = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

// ════════════════════════════════════════════════════════════════════════════════
// CHAMPS ÉDITABLES
// ════════════════════════════════════════════════════════════════════════════════

$heroTitle    = $fields['hero_title']    ?? 'Notre bibliothèque de guides';
$heroSubtitle = $fields['hero_subtitle'] ?? 'Ressources gratuites pour réussir votre projet immobilier';
$introTitle   = $fields['intro_title']   ?? 'Choisissez votre guide';
$introText    = $fields['intro_text']    ?? 'Parcourez nos guides thématiques et téléchargez celui qui vous convient.';
$ctaTitle     = $fields['cta_title']     ?? 'Vous ne trouvez pas le guide que vous cherchez ?';
$ctaBtnText   = $fields['cta_btn_text']  ?? 'Me contacter';
$ctaBtnUrl    = $fields['cta_btn_url']   ?? $siteUrl . '/contact';

$metaTitle = $page['meta_title']       ?? 'Guides gratuits | ' . $advisorName;
$metaDesc  = $page['meta_description'] ?? 'Bibliothèque de guides immobiliers gratuits.';
$canonical = $siteUrl . '/' . ltrim($page['slug'] ?? 'ressources', '/');

// ════════════════════════════════════════════════════════════════════════════════
// RÉCUPÉRER LES GUIDES
// ════════════════════════════════════════════════════════════════════════════════

$guides = [];
$search = trim($_GET['q'] ?? '');
$filter = trim($_GET['type'] ?? '');

if ($pdo) {
    try {
        $sql = "SELECT * FROM ressources WHERE status = 'active'";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if (!empty($filter)) {
            $sql .= " AND persona = ?";
            $params[] = $filter;
        }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $guides = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) {
        error_log("[Ressources Listing] Error: " . $e->getMessage());
    }
}

$personaLabels = ['vendeur' => '🏷️ Vendeur', 'acheteur' => '🛒 Acheteur', 'proprietaire' => '🏠 Propriétaire'];

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
.guides-filter { background:white; border:1px solid var(--tp-border); border-radius:var(--tp-radius); padding:24px; margin-bottom:40px; display:flex; gap:16px; flex-wrap:wrap; align-items:center; }
.guides-filter input { flex:1; min-width:200px; padding:11px 14px; border:1px solid var(--tp-border); border-radius:8px; font-size:.9rem; }
.guides-filter select { padding:11px 14px; border:1px solid var(--tp-border); border-radius:8px; font-size:.9rem; font-family:inherit; background:white; cursor:pointer; }
.guides-filter button { padding:11px 24px; background:var(--tp-accent); color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; transition:all .2s; }
.guides-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); gap:32px; }
.guide-card { background:white; border:1px solid var(--tp-border); border-radius:var(--tp-radius); overflow:hidden; transition:all .3s; display:flex; flex-direction:column; }
.guide-card:hover { box-shadow:var(--tp-shadow); border-color:var(--tp-accent); transform:translateY(-4px); }
.guide-card-header { padding:24px; background:linear-gradient(135deg, var(--tp-primary) 0%, var(--tp-primary-dark) 100%); color:white; }
.guide-card-icon { font-size:2.5rem; margin-bottom:12px; }
.guide-card-title { font-family:var(--tp-ff-display); font-size:1.2rem; font-weight:800; margin-bottom:8px; }
.guide-card-body { padding:24px; flex:1; display:flex; flex-direction:column; }
.guide-card-desc { color:var(--tp-text2); line-height:1.6; margin-bottom:16px; flex:1; }
.guide-card-footer { display:flex; gap:12px; font-size:.8rem; color:var(--tp-text2); text-transform:uppercase; margin-bottom:16px; }
.guide-card-btn { display:block; text-align:center; padding:12px 20px; background:var(--tp-primary); color:white; text-decoration:none; border-radius:8px; font-weight:700; font-size:.9rem; transition:all .2s; }
.guide-card-btn:hover { background:var(--tp-primary-dark); }
.guides-empty { text-align:center; padding:60px 20px; }
</style>
</head>
<body>
<?php if (function_exists('renderHeader')) echo renderHeader($headerData); ?>
<main class="tp-page">

<section class="tp-hero">
    <div class="tp-hero-inner">
        <div class="tp-eyebrow">Ressources</div>
        <h1 class="tp-hero-h1" <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroTitle) ?>
        </h1>
        <p class="tp-hero-sub" <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroSubtitle) ?>
        </p>
    </div>
</section>

<section class="tp-section-white">
    <div class="tp-container-sm" style="text-align:center; margin-bottom:40px;">
        <h2 style="font-family:var(--tp-ff-display); font-size:1.8rem; font-weight:800; color:var(--tp-primary); margin-bottom:16px;" <?= $editMode ? 'data-field="intro_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($introTitle) ?>
        </h2>
        <p style="font-size:1.05rem; color:var(--tp-text2); line-height:1.7;" <?= $editMode ? 'data-field="intro_text" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($introText) ?>
        </p>
    </div>

    <div class="tp-container">
        <form method="GET" class="guides-filter">
            <input type="text" name="q" placeholder="Rechercher un guide..." value="<?= htmlspecialchars($search) ?>">
            <select name="type">
                <option value="">Tous les types</option>
                <option value="vendeur" <?= $filter === 'vendeur' ? 'selected' : '' ?>>🏷️ Vendeur</option>
                <option value="acheteur" <?= $filter === 'acheteur' ? 'selected' : '' ?>>🛒 Acheteur</option>
                <option value="proprietaire" <?= $filter === 'proprietaire' ? 'selected' : '' ?>>🏠 Propriétaire</option>
            </select>
            <button type="submit"><i class="fas fa-search"></i> Chercher</button>
        </form>
    </div>
</section>

<section class="tp-section-light">
    <div class="tp-container">
        <?php if (!empty($guides)): ?>
        <div class="guides-grid">
            <?php foreach ($guides as $guide): ?>
            <div class="guide-card">
                <div class="guide-card-header">
                    <div class="guide-card-icon">📖</div>
                    <h3 class="guide-card-title"><?= htmlspecialchars($guide['name']) ?></h3>
                    <div class="guide-card-meta"><?= $personaLabels[$guide['persona']] ?? '📚 Guide' ?></div>
                </div>
                <div class="guide-card-body">
                    <p class="guide-card-desc"><?= htmlspecialchars(substr($guide['description'], 0, 120)) ?>...</p>
                    <div class="guide-card-footer">
                        <span><?= htmlspecialchars($guide['pages'] ?? '—') ?></span>
                        <span><?= htmlspecialchars($guide['format'] ?? 'PDF') ?></span>
                    </div>
                    <a href="<?= htmlspecialchars($siteUrl . '/ressources/' . $guide['slug']) ?>" class="guide-card-btn">
                        <i class="fas fa-download"></i> Télécharger
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="guides-empty">
            <h3>Aucun guide trouvé</h3>
            <p>Essayez une autre recherche ou un autre filtre.</p>
            <a href="?q=&type=" style="color:var(--tp-accent); text-decoration:none; font-weight:600;">← Réinitialiser</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<section class="tp-cta-section">
    <div class="tp-container">
        <h2 class="tp-cta-title" <?= $editMode ? 'data-field="cta_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($ctaTitle) ?>
        </h2>
        <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="tp-cta-btn" <?= $editMode ? 'data-field="cta_btn_text" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($ctaBtnText) ?>
        </a>
    </div>
</section>

</main>
<?php if (function_exists('renderFooter')) echo renderFooter($footerData); ?>
</body>
</html>