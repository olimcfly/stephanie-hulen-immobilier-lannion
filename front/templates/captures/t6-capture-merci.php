<?php
/**
 * /front/templates/captures/t6-capture-merci.php
 * Page merci post-opt-in (capture) — v2.1
 * Pas de header/footer nav → page isolation conversion
 * Variables injectées par router.php via dispatchTemplate()
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];

$advisorName    = $advisor['name']    ?? ($site['name']    ?? 'Votre conseiller');
$advisorCity    = $advisor['city']    ?? ($site['city']    ?? 'votre ville');
$advisorAvatar  = $advisor['avatar']  ?? '';
$advisorPhone   = $advisor['phone']   ?? '';
$advisorNetwork = $advisor['network'] ?? 'eXp France';
$siteUrl        = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

// Récupérer le prénom passé en GET ou POST
$prenom = htmlspecialchars(trim($_POST['prenom'] ?? $_GET['prenom'] ?? ''));

// ── Champs ──────────────────────────────────────────────
$merciTitle  = $fields['merci_title']  ?? 'Merci' . ($prenom ? ' ' . $prenom : '') . ' !';
$merciText   = $fields['merci_text']   ?? 'Votre guide est en route. Vérifiez votre boîte mail (et vos spams si besoin).';
$nextStep2   = $fields['next_step_2']  ?? 'Recevez le guide dans votre boîte mail dans quelques minutes.';
$nextStep3   = $fields['next_step_3']  ?? 'Je vous contacterai prochainement pour un accompagnement personnalisé.';

$ctaTitle       = $fields['cta_title']           ?? 'Pendant que vous attendez…';
$ctaDesc        = $fields['cta_desc']            ?? 'Découvrez mes autres ressources gratuites.';
$ctaBtnPrimary  = $fields['cta_btn_primary']     ?? 'Visiter le site';
$ctaBtnUrl      = $fields['cta_btn_url']         ?? $siteUrl . '/';
$ctaBtnSecondary= $fields['cta_btn_secondary']   ?? 'Lire le blog';

// Upsell doux
$upsellTitle = $fields['upsell_title'] ?? 'Besoin d\'une estimation gratuite ?';
$upsellText  = $fields['upsell_text']  ?? 'Profitez-en pour demander l\'estimation de votre bien. C\'est gratuit et sans engagement.';
$upsellCta   = $fields['upsell_cta']   ?? 'Estimer mon bien';
$upsellUrl   = $fields['upsell_url']   ?? $siteUrl . '/estimation';

$metaTitle = $page['meta_title'] ?? 'Merci — ' . $advisorName;
$canonical = $siteUrl . '/merci';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($metaTitle) ?></title>
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing:border-box; }
:root {
    --tp-primary:    #1B3A4B;
    --tp-primary-l:  #2C5F7C;
    --tp-primary-d:  #122A37;
    --tp-accent:     #C8A96E;
    --tp-accent-l:   #E8D5A8;
    --tp-accent-d:   #A68B4B;
    --tp-white:      #FFFFFF;
    --tp-bg:         #F8F6F3;
    --tp-border:     #E2D9CC;
    --tp-text:       #1a1a2e;
    --tp-text2:      #4a5568;
    --tp-text3:      #718096;
    --tp-ff-display: 'Playfair Display', Georgia, serif;
    --tp-ff-body:    'DM Sans', 'Segoe UI', sans-serif;
    --tp-radius:     16px;
    --tp-shadow:     0 4px 24px rgba(27,58,75,.10);
    --tp-shadow-lg:  0 12px 48px rgba(27,58,75,.16);
}
html, body { margin:0; padding:0; background:var(--tp-bg); font-family:var(--tp-ff-body); color:var(--tp-text); min-height:100vh; }
a { color:inherit; text-decoration:none; }

/* ── Barre top ────────────────────────────────────────── */
.cmerci-topbar { background:var(--tp-primary); padding:12px 24px; text-align:center; }
.cmerci-topbar a { color:rgba(255,255,255,.7); font-size:.78rem; border-bottom:1px solid rgba(255,255,255,.25); }

/* ── Main card ────────────────────────────────────────── */
.cmerci-wrap { max-width:680px; margin:0 auto; padding:60px 24px 80px; }
.cmerci-card { background:var(--tp-white); border-radius:28px; border:1px solid var(--tp-border); padding:56px 48px; text-align:center; box-shadow:var(--tp-shadow-lg); position:relative; overflow:hidden; margin-bottom:24px; }
.cmerci-card::before { content:''; position:absolute; top:-80px; left:50%; transform:translateX(-50%); width:300px; height:300px; background:radial-gradient(circle,rgba(200,169,110,.07),transparent 65%); border-radius:50%; pointer-events:none; }

/* ── Check animé ──────────────────────────────────────── */
.cmerci-check { width:88px; height:88px; border-radius:50%; background:linear-gradient(135deg,#10b981,#059669); display:flex; align-items:center; justify-content:center; margin:0 auto 28px; box-shadow:0 8px 32px rgba(16,185,129,.28); animation:popIn .4s cubic-bezier(.34,1.56,.64,1) both; }
.cmerci-check::after { content:'✓'; color:#fff; font-size:2.6rem; font-weight:900; }
@keyframes popIn { from { transform:scale(0); opacity:0; } to { transform:scale(1); opacity:1; } }

.cmerci-title { font-family:var(--tp-ff-display); font-size:clamp(1.6rem,4vw,2.4rem); font-weight:800; color:var(--tp-primary); margin:0 0 16px; }
.cmerci-text  { font-size:.95rem; color:var(--tp-text2); line-height:1.75; max-width:440px; margin:0 auto 36px; }

/* ── Étapes ───────────────────────────────────────────── */
.cmerci-steps { display:flex; flex-direction:column; gap:12px; text-align:left; background:var(--tp-bg); border-radius:var(--tp-radius); padding:24px 28px; margin-bottom:36px; }
.cmerci-step { display:flex; align-items:flex-start; gap:14px; font-size:.88rem; color:var(--tp-text2); }
.cmerci-step-num { width:28px; height:28px; border-radius:50%; background:var(--tp-primary); color:var(--tp-white); display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:900; flex-shrink:0; margin-top:1px; }

/* ── Actions ──────────────────────────────────────────── */
.cmerci-actions { display:flex; gap:14px; justify-content:center; flex-wrap:wrap; }
.cmerci-btn-primary { display:inline-flex; align-items:center; gap:8px; background:var(--tp-primary); color:var(--tp-white); font-weight:700; font-size:.9rem; padding:14px 28px; border-radius:50px; box-shadow:var(--tp-shadow); transition:all .2s; }
.cmerci-btn-primary:hover { background:var(--tp-primary-l); transform:translateY(-2px); }
.cmerci-btn-primary::after { content:'→'; }
.cmerci-btn-secondary { display:inline-flex; align-items:center; gap:8px; background:transparent; color:var(--tp-text2); border:1px solid var(--tp-border); font-weight:600; font-size:.9rem; padding:13px 24px; border-radius:50px; transition:all .2s; }
.cmerci-btn-secondary:hover { background:var(--tp-bg); border-color:var(--tp-primary); }

/* ── Conseiller ───────────────────────────────────────── */
.cmerci-advisor { display:flex; align-items:center; gap:16px; padding:20px 24px; background:var(--tp-white); border:1px solid var(--tp-border); border-radius:var(--tp-radius); box-shadow:var(--tp-shadow); margin-bottom:20px; }
.cmerci-advisor-avatar { width:56px; height:56px; border-radius:50%; border:2px solid var(--tp-accent); object-fit:cover; background:var(--tp-bg); display:flex; align-items:center; justify-content:center; font-size:1.6rem; overflow:hidden; flex-shrink:0; }
.cmerci-advisor-name { font-weight:800; font-size:.9rem; color:var(--tp-primary); margin-bottom:2px; }
.cmerci-advisor-role { font-size:.75rem; color:var(--tp-text3); }

/* ── Upsell card ──────────────────────────────────────── */
.cmerci-upsell { background:linear-gradient(135deg,var(--tp-primary-d),var(--tp-primary)); border-radius:20px; padding:32px 36px; text-align:center; position:relative; overflow:hidden; }
.cmerci-upsell::before { content:''; position:absolute; bottom:-40px; right:-40px; width:180px; height:180px; background:radial-gradient(circle,rgba(200,169,110,.12),transparent 65%); border-radius:50%; }
.cmerci-upsell-title { font-family:var(--tp-ff-display); font-size:1.2rem; font-weight:800; color:var(--tp-white); margin:0 0 10px; }
.cmerci-upsell-text { font-size:.85rem; color:rgba(255,255,255,.75); margin:0 0 20px; line-height:1.65; }
.cmerci-upsell-btn { display:inline-flex; align-items:center; gap:10px; background:var(--tp-accent); color:var(--tp-primary-d); font-weight:800; font-size:.9rem; padding:14px 28px; border-radius:50px; box-shadow:0 4px 20px rgba(200,169,110,.35); transition:all .2s; position:relative; }
.cmerci-upsell-btn:hover { background:var(--tp-accent-l); transform:translateY(-2px); }
.cmerci-upsell-btn::after { content:'→'; }

/* ── Phone ────────────────────────────────────────────── */
.cmerci-phone { text-align:center; margin-top:16px; font-size:.8rem; color:var(--tp-text3); }
.cmerci-phone a { color:var(--tp-primary); font-weight:700; }

/* ── Footer ───────────────────────────────────────────── */
.cmerci-footer { text-align:center; margin-top:32px; font-size:.72rem; color:var(--tp-text3); }
.cmerci-footer a { color:var(--tp-text2); border-bottom:1px solid var(--tp-border); }

/* ── Edit mode ────────────────────────────────────────── */
.ef-zone { outline:2px dashed rgba(99,102,241,.35); outline-offset:3px; border-radius:4px; cursor:pointer; }
.ef-zone:hover { outline-color:rgba(99,102,241,.8); background:rgba(99,102,241,.04); }

/* ── Responsive ───────────────────────────────────────── */
@media(max-width:600px) {
    .cmerci-card { padding:36px 20px; }
    .cmerci-actions { flex-direction:column; align-items:center; }
    .cmerci-upsell { padding:24px 20px; }
}
</style>
</head>
<body>

<div class="cmerci-topbar">
    <a href="<?= htmlspecialchars($siteUrl) ?>/">← Retour au site de <?= htmlspecialchars($advisorName) ?></a>
</div>

<div class="cmerci-wrap">

    <!-- CARD PRINCIPALE -->
    <div class="cmerci-card">
        <div class="cmerci-check" aria-hidden="true"></div>
        <h1 class="cmerci-title" <?= $editMode ? 'data-field="merci_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($merciTitle) ?></h1>
        <p class="cmerci-text"   <?= $editMode ? 'data-field="merci_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($merciText) ?></p>

        <div class="cmerci-steps">
            <div class="cmerci-step"><div class="cmerci-step-num">1</div><span>Votre guide est en cours d'envoi.</span></div>
            <div class="cmerci-step"><div class="cmerci-step-num">2</div><span <?= $editMode ? 'data-field="next_step_2" class="ef-zone"' : '' ?>><?= htmlspecialchars($nextStep2) ?></span></div>
            <div class="cmerci-step"><div class="cmerci-step-num">3</div><span <?= $editMode ? 'data-field="next_step_3" class="ef-zone"' : '' ?>><?= htmlspecialchars($nextStep3) ?></span></div>
        </div>

        <div class="cmerci-actions">
            <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="cmerci-btn-primary" <?= $editMode ? 'data-field="cta_btn_primary" class="ef-zone"' : '' ?>><?= htmlspecialchars($ctaBtnPrimary) ?></a>
            <a href="<?= htmlspecialchars($siteUrl) ?>/blog" class="cmerci-btn-secondary" <?= $editMode ? 'data-field="cta_btn_secondary" class="ef-zone"' : '' ?>><?= htmlspecialchars($ctaBtnSecondary) ?></a>
        </div>
    </div>

    <!-- CONSEILLER -->
    <div class="cmerci-advisor">
        <?php if ($advisorAvatar): ?>
        <img src="<?= htmlspecialchars($advisorAvatar) ?>" alt="<?= htmlspecialchars($advisorName) ?>" class="cmerci-advisor-avatar">
        <?php else: ?>
        <div class="cmerci-advisor-avatar">👤</div>
        <?php endif; ?>
        <div>
            <div class="cmerci-advisor-name"><?= htmlspecialchars($advisorName) ?></div>
            <div class="cmerci-advisor-role">Conseiller <?= htmlspecialchars($advisorNetwork) ?> — <?= htmlspecialchars($advisorCity) ?></div>
        </div>
        <?php if ($advisorPhone): ?>
        <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/','',$advisorPhone)) ?>" style="margin-left:auto;display:inline-flex;align-items:center;gap:8px;background:var(--tp-bg);border:1px solid var(--tp-border);border-radius:50px;padding:8px 16px;font-size:.8rem;font-weight:700;color:var(--tp-primary);">
            📞 <?= htmlspecialchars($advisorPhone) ?>
        </a>
        <?php endif; ?>
    </div>

    <!-- UPSELL -->
    <div class="cmerci-upsell">
        <div class="cmerci-upsell-title" <?= $editMode ? 'data-field="upsell_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($upsellTitle) ?></div>
        <p class="cmerci-upsell-text"    <?= $editMode ? 'data-field="upsell_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($upsellText) ?></p>
        <a href="<?= htmlspecialchars($upsellUrl) ?>" class="cmerci-upsell-btn" <?= $editMode ? 'data-field="upsell_cta" class="ef-zone"' : '' ?>><?= htmlspecialchars($upsellCta) ?></a>
    </div>

    <div class="cmerci-footer">
        <a href="<?= htmlspecialchars($siteUrl) ?>/mentions-legales">Mentions légales</a> ·
        <a href="<?= htmlspecialchars($siteUrl) ?>/">Retour au site</a>
    </div>

</div>

</body>
</html>