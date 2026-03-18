<?php
/**
 * /front/templates/ressources/t18-ressources-single.php
 * Template Single Ressource — Détail + Formulaire téléchargement
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$page       = $page       ?? [];
$pdo        = $pdo        ?? null;
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;

$siteUrl = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

// ════════════════════════════════════════════════════════════════════════════════
// RÉCUPÉRER LA RESSOURCE
// ════════════════════════════════════════════════════════════════════════════════

$guideSlug = $page['slug'] ?? '';
$guide = null;

if ($pdo && !empty($guideSlug)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM ressources WHERE slug = ? AND status = 'active'");
        $stmt->execute([$guideSlug]);
        $guide = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("[Ressources Single] Error: " . $e->getMessage());
    }
}

if (!$guide) {
    http_response_code(404);
    echo '<div style="text-align:center;padding:60px 20px;font-family:Inter,sans-serif">
        <h1 style="color:#dc2626">Ressource introuvable</h1>
        <a href="' . $siteUrl . '/ressources" style="color:var(--tp-accent);text-decoration:none;font-weight:600">← Retour</a>
    </div>';
    exit;
}

// ════════════════════════════════════════════════════════════════════════════════
// TRAITER FORMULAIRE
// ════════════════════════════════════════════════════════════════════════════════

$formError = '';
$formData = ['firstname' => '', 'lastname' => '', 'email' => '', 'city' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['firstname'] = trim($_POST['firstname'] ?? '');
    $formData['lastname']  = trim($_POST['lastname']  ?? '');
    $formData['email']     = trim($_POST['email']     ?? '');
    $formData['city']      = trim($_POST['city']      ?? '');

    if (empty($formData['firstname'])) $formError = 'Prénom requis';
    elseif (empty($formData['lastname'])) $formError = 'Nom requis';
    elseif (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) $formError = 'Email invalide';
    elseif (empty($formData['city'])) $formError = 'Ville requise';

    if (empty($formError) && $pdo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO ressources_downloads 
                (resource_id, firstname, lastname, email, city, user_agent, ip_address, referer)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $guide['id'],
                $formData['firstname'],
                $formData['lastname'],
                $formData['email'],
                $formData['city'],
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_REFERER'] ?? ''
            ]);

            header('Location: ' . $siteUrl . '/ressources/merci?guide=' . urlencode($guide['name']));
            exit;
        } catch (Exception $e) {
            $formError = 'Erreur lors de l\'enregistrement.';
        }
    }
}

// Stats
$stats = ['downloads' => 0, 'unique_leads' => 0];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, COUNT(DISTINCT email) as unique FROM ressources_downloads WHERE resource_id = ?");
        $stmt->execute([$guide['id']]);
        $row = $stmt->fetch();
        $stats['downloads'] = $row['total'] ?? 0;
        $stats['unique_leads'] = $row['unique'] ?? 0;
    } catch (Exception $e) {}
}

$personaLabel = ['vendeur' => '🏷️ Vendeur', 'acheteur' => '🛒 Acheteur', 'proprietaire' => '🏠 Propriétaire'];
$persona = $personaLabel[$guide['persona']] ?? '📚 Guide';

$metaTitle = $guide['name'] . ' | Ressources';
$metaDesc  = $guide['description'] ?? '';
$canonical = $siteUrl . '/ressources/' . $guide['slug'];

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
.guide-header { background:linear-gradient(135deg, var(--tp-primary) 0%, var(--tp-primary-dark) 100%); color:white; padding:60px 20px; text-align:center; }
.guide-header h1 { font-family:var(--tp-ff-display); font-size:2.5rem; font-weight:800; margin-bottom:16px; }
.guide-header-meta { display:flex; gap:20px; justify-content:center; flex-wrap:wrap; margin-bottom:24px; font-size:.9rem; }
.guide-stats { display:flex; gap:32px; justify-content:center; padding:24px; background:rgba(255,255,255,.1); border-radius:12px; margin-top:24px; }
.guide-stat { text-align:center; }
.guide-stat-num { font-size:2rem; font-weight:800; display:block; }

.guide-content { display:grid; grid-template-columns:1fr 1fr; gap:60px; align-items:start; }
@media(max-width:768px) { .guide-content { grid-template-columns:1fr; } }

.guide-description { color:var(--tp-text2); line-height:1.8; margin-bottom:32px; }
.guide-chapters { background:white; border:1px solid var(--tp-border); border-radius:var(--tp-radius); padding:24px; }
.guide-chapters h4 { font-family:var(--tp-ff-display); font-size:1.1rem; font-weight:800; color:var(--tp-primary); margin-bottom:20px; }
.guide-chapters ul { list-style:none; }
.guide-chapters li { padding:12px 0; border-bottom:1px solid var(--tp-border); color:var(--tp-text2); display:flex; align-items:center; gap:10px; }
.guide-chapters li:last-child { border-bottom:none; }
.guide-chapters li:before { content:'✓'; color:var(--tp-accent); font-weight:bold; }

.form-wrapper { background:white; border:1px solid var(--tp-border); border-radius:var(--tp-radius); padding:40px; }
.form-wrapper h3 { font-family:var(--tp-ff-display); font-size:1.4rem; font-weight:800; color:var(--tp-primary); margin-bottom:8px; display:flex; align-items:center; gap:10px; }

.form-group { margin-bottom:20px; }
.form-label { display:block; font-size:.75rem; font-weight:700; color:var(--tp-text2); text-transform:uppercase; letter-spacing:.05em; margin-bottom:8px; }
.form-input { width:100%; padding:12px 16px; border:1px solid var(--tp-border); border-radius:8px; font-size:.95rem; font-family:inherit; }
.form-input:focus { outline:none; border-color:var(--tp-accent); box-shadow:0 0 0 3px rgba(212,165,116,.1); }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:600px) { .form-row { grid-template-columns:1fr; } }
.form-submit { width:100%; padding:14px 20px; background:var(--tp-accent); color:white; border:none; border-radius:8px; font-weight:700; cursor:pointer; transition:all .2s; margin-top:8px; font-family:inherit; }
.form-submit:hover { background:var(--tp-accent-d); }

.alert { padding:14px 16px; border-radius:8px; font-size:.9rem; margin-bottom:20px; display:flex; align-items:center; gap:10px; }
.alert.error { background:#fef2f2; color:#dc2626; border:1px solid rgba(220,38,38,.12); }
</style>
</head>
<body>
<?php if (function_exists('renderHeader')) echo renderHeader($headerData); ?>
<main class="tp-page">

<div class="guide-header">
    <a href="<?= $siteUrl ?>/ressources" style="color:white; text-decoration:none; font-size:.9rem; opacity:.8; margin-bottom:16px; display:inline-flex; gap:6px; align-items:center;">
        <i class="fas fa-arrow-left"></i> Retour
    </a>
    <h1><?= htmlspecialchars($guide['name']) ?></h1>
    <div class="guide-header-meta">
        <span><?= $persona ?></span>
        <span><?= htmlspecialchars($guide['format'] ?? 'PDF') ?></span>
        <span><?= htmlspecialchars($guide['pages'] ?? '—') ?></span>
    </div>
    <div class="guide-stats">
        <div class="guide-stat">
            <span class="guide-stat-num"><?= $stats['downloads'] ?></span>
            <span style="font-size:.8rem; opacity:.8; text-transform:uppercase;">Téléchargements</span>
        </div>
        <div class="guide-stat">
            <span class="guide-stat-num"><?= $stats['unique_leads'] ?></span>
            <span style="font-size:.8rem; opacity:.8; text-transform:uppercase;">Leads</span>
        </div>
    </div>
</div>

<section class="tp-section-white">
    <div class="tp-container">
        <div class="guide-content">
            <div>
                <p class="guide-description"><?= nl2br(htmlspecialchars($guide['description'])) ?></p>
                <div style="background:var(--tp-bg); padding:20px; border-radius:var(--tp-radius); font-size:.9rem; color:var(--tp-text2); line-height:1.6;">
                    <p><strong>À propos:</strong><br><?= nl2br(htmlspecialchars($guide['extrait'] ?? 'Ressource professionnelle')) ?></p>
                </div>
            </div>

            <div>
                <div class="form-wrapper">
                    <h3>🎁 Télécharger</h3>
                    <p style="font-size:.9rem; color:var(--tp-text2); margin-bottom:24px;">Remplissez le formulaire pour accéder à la ressource.</p>

                    <?php if (!empty($formError)): ?>
                    <div class="alert error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($formError) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Prénom</label>
                                <input type="text" name="firstname" class="form-input" required value="<?= htmlspecialchars($formData['firstname']) ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Nom</label>
                                <input type="text" name="lastname" class="form-input" required value="<?= htmlspecialchars($formData['lastname']) ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-input" required value="<?= htmlspecialchars($formData['email']) ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ville</label>
                            <input type="text" name="city" class="form-input" required value="<?= htmlspecialchars($formData['city']) ?>">
                        </div>
                        <button type="submit" class="form-submit">
                            <i class="fas fa-download"></i> Télécharger
                        </button>
                        <p style="font-size:.75rem; color:var(--tp-text2); margin-top:12px; text-align:center;">
                            ✓ Votre email restera confidentiel.
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <?php if (!empty($guide['chapitres'])): ?>
        <div class="guide-chapters" style="margin-top:40px;">
            <h4>📚 Chapitres inclus</h4>
            <ul>
                <?php 
                $chapitres = json_decode($guide['chapitres'], true) ?: [];
                foreach ($chapitres as $ch): 
                ?>
                <li><?= htmlspecialchars($ch) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</section>

</main>
<?php if (function_exists('renderFooter')) echo renderFooter($footerData); ?>
</body>
</html>