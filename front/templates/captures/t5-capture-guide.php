<?php
/**
 * /front/templates/captures/t5-capture-guide.php
 * Landing page capture (guide/lead magnet) — v2.1
 * Pas de header/footer nav → page isolation conversion
 * Variables injectées par router.php :
 *   $page, $fields, $advisor, $site, $editMode, $headerData, $footerData
 *   $guide_slug_url  ← slug du guide passé par le router
 */

$fields        = $fields        ?? [];
$editMode      = $editMode      ?? false;
$advisor       = $advisor       ?? [];
$site          = $site          ?? [];
$guideSlugUrl  = $guide_slug_url ?? ($_GET['guide'] ?? '');

$advisorName    = $advisor['name']    ?? ($site['name']    ?? 'Votre conseiller');
$advisorCity    = $advisor['city']    ?? ($site['city']    ?? 'votre ville');
$advisorAvatar  = $advisor['avatar']  ?? '';
$advisorPhone   = $advisor['phone']   ?? '';
$advisorNetwork = $advisor['network'] ?? 'eXp France';
$siteUrl        = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

// ── Champs $TPL['t5-capture-guide'] (standard) ──────────
// Hero
$heroEyebrow  = $fields['hero_eyebrow']  ?? 'Guide gratuit';
$heroTitle    = $fields['hero_title']    ?? 'Téléchargez votre guide offert';
$heroSubtitle = $fields['hero_subtitle'] ?? 'Tout ce que vous devez savoir pour réussir votre projet immobilier à ' . $advisorCity . '.';

// Bénéfices (3)
$ben1Icon  = $fields['ben1_icon']  ?? '✅';
$ben1Text  = $fields['ben1_text']  ?? 'Les erreurs à éviter absolument';
$ben2Icon  = $fields['ben2_icon']  ?? '✅';
$ben2Text  = $fields['ben2_text']  ?? 'Les étapes clés pour vendre vite et au bon prix';
$ben3Icon  = $fields['ben3_icon']  ?? '✅';
$ben3Text  = $fields['ben3_text']  ?? 'Les conseils d\'un expert local';

// Formulaire
$formTitle   = $fields['form_title']   ?? 'Recevoir le guide gratuitement';
$formCta     = $fields['form_cta']     ?? 'Je veux mon guide gratuit';
$formMention = $fields['form_mention'] ?? 'Vos données sont confidentielles. Désinscription en 1 clic.';

// Social proof
$sp1Text = $fields['sp1_text'] ?? '« Exactement ce qu\'il me fallait pour préparer ma vente. »';
$sp1Name = $fields['sp1_name'] ?? 'Marie T. — Vendu en 3 semaines';
$sp2Text = $fields['sp2_text'] ?? '« Guide clair, pratique, je recommande ! »';
$sp2Name = $fields['sp2_name'] ?? 'Paul D. — Bordeaux';

// Footer capture
$legalText = $fields['legal_text'] ?? '';

$metaTitle = $page['meta_title']       ?? 'Guide gratuit — ' . $advisorName;
$metaDesc  = $page['meta_description'] ?? 'Téléchargez le guide immobilier gratuit de ' . $advisorName . '.';
$canonical = $siteUrl . '/capture/' . ltrim($guideSlugUrl, '/');

// Action formulaire → /merci
$formAction = $siteUrl . '/merci';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($metaTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
<meta name="robots" content="noindex,follow">
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
    --tp-shadow-lg:  0 12px 48px rgba(27,58,75,.16);
}
html, body { margin:0; padding:0; background:var(--tp-bg); font-family:var(--tp-ff-body); color:var(--tp-text); }
a { color:inherit; text-decoration:none; }

/* ── Barre top ────────────────────────────────────────── */
.cap-topbar { background:var(--tp-primary); padding:12px 24px; display:flex; align-items:center; justify-content:center; gap:12px; }
.cap-topbar-text { font-size:.8rem; color:rgba(255,255,255,.85); font-weight:600; }
.cap-topbar-badge { background:var(--tp-accent); color:var(--tp-primary-d); font-size:.7rem; font-weight:800; padding:3px 10px; border-radius:40px; }

/* ── Layout principal ─────────────────────────────────── */
.cap-main { max-width:1060px; margin:0 auto; padding:48px 24px 80px; display:grid; grid-template-columns:1fr 420px; gap:60px; align-items:start; }

/* ── Côté gauche ──────────────────────────────────────── */
.cap-eyebrow { display:inline-flex; align-items:center; gap:8px; background:rgba(200,169,110,.12); border:1px solid rgba(200,169,110,.25); color:var(--tp-accent-d); font-size:.72rem; font-weight:700; padding:5px 14px; border-radius:40px; letter-spacing:.06em; text-transform:uppercase; margin-bottom:20px; }
.cap-eyebrow::before { content:'◆'; font-size:.45rem; }
.cap-title { font-family:var(--tp-ff-display); font-size:clamp(1.8rem,4vw,2.8rem); font-weight:800; color:var(--tp-primary); line-height:1.15; margin:0 0 20px; letter-spacing:-.02em; }
.cap-subtitle { font-size:.95rem; color:var(--tp-text2); line-height:1.75; margin-bottom:32px; }
.cap-benefits { display:flex; flex-direction:column; gap:14px; margin-bottom:36px; }
.cap-benefit { display:flex; align-items:center; gap:14px; font-size:.9rem; color:var(--tp-text); font-weight:500; }
.cap-benefit-icon { width:32px; height:32px; border-radius:50%; background:rgba(16,185,129,.12); color:#10b981; display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:900; flex-shrink:0; border:1px solid rgba(16,185,129,.2); }

/* ── Conseiller ───────────────────────────────────────── */
.cap-advisor { display:flex; align-items:center; gap:16px; padding:20px 24px; background:var(--tp-white); border:1px solid var(--tp-border); border-radius:var(--tp-radius); margin-bottom:32px; }
.cap-advisor-avatar { width:56px; height:56px; border-radius:50%; border:2px solid var(--tp-accent); object-fit:cover; background:var(--tp-bg); display:flex; align-items:center; justify-content:center; font-size:1.6rem; overflow:hidden; flex-shrink:0; }
.cap-advisor-name { font-weight:800; font-size:.9rem; color:var(--tp-primary); margin-bottom:2px; }
.cap-advisor-role { font-size:.75rem; color:var(--tp-text3); }

/* ── Social proof ─────────────────────────────────────── */
.cap-testimonials { display:flex; flex-direction:column; gap:14px; }
.cap-testi { background:var(--tp-white); border:1px solid var(--tp-border); border-radius:12px; padding:18px 20px; position:relative; }
.cap-testi::before { content:'"'; font-family:var(--tp-ff-display); font-size:3rem; color:var(--tp-accent); line-height:.6; position:absolute; top:12px; left:16px; opacity:.4; }
.cap-testi-text { font-size:.83rem; color:var(--tp-text2); line-height:1.6; padding-left:8px; font-style:italic; margin-bottom:8px; }
.cap-testi-name { font-size:.75rem; font-weight:700; color:var(--tp-primary); }

/* ── Formulaire (droite) ──────────────────────────────── */
.cap-form-card { background:var(--tp-white); border-radius:24px; padding:40px 36px; box-shadow:var(--tp-shadow-lg); border:1px solid var(--tp-border); position:sticky; top:24px; }
.cap-form-preview { aspect-ratio:4/3; background:linear-gradient(135deg,var(--tp-primary-d),var(--tp-primary)); border-radius:12px; display:flex; align-items:center; justify-content:center; margin-bottom:28px; position:relative; overflow:hidden; }
.cap-form-preview::after { content:'📄'; font-size:4rem; filter:drop-shadow(0 4px 12px rgba(0,0,0,.3)); }
.cap-form-preview-badge { position:absolute; top:12px; right:12px; background:var(--tp-accent); color:var(--tp-primary-d); font-size:.7rem; font-weight:800; padding:4px 10px; border-radius:40px; }
.cap-form-title { font-family:var(--tp-ff-display); font-size:1.25rem; font-weight:800; color:var(--tp-primary); margin:0 0 24px; text-align:center; }
.cap-form-group { margin-bottom:14px; }
.cap-form-group label { display:block; font-size:.78rem; font-weight:700; color:var(--tp-primary); margin-bottom:5px; }
.cap-form-group input {
    width:100%; padding:13px 16px; border:1.5px solid var(--tp-border); border-radius:10px;
    font-family:var(--tp-ff-body); font-size:.9rem; color:var(--tp-text); background:var(--tp-bg);
    outline:none; transition:border-color .2s;
}
.cap-form-group input:focus { border-color:var(--tp-primary); background:var(--tp-white); }
.cap-form-btn { width:100%; padding:17px; background:var(--tp-accent); color:var(--tp-primary-d); font-weight:800; font-size:.95rem; border:none; border-radius:50px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:10px; font-family:var(--tp-ff-body); box-shadow:0 4px 20px rgba(200,169,110,.35); transition:all .2s; margin-top:6px; }
.cap-form-btn:hover { background:var(--tp-accent-l); transform:translateY(-1px); }
.cap-form-btn::after { content:'→'; }
.cap-form-mention { font-size:.72rem; color:var(--tp-text3); text-align:center; margin-top:12px; line-height:1.5; }
.cap-form-security { display:flex; align-items:center; justify-content:center; gap:6px; margin-top:14px; font-size:.72rem; color:var(--tp-text3); }
.cap-form-security::before { content:'🔒'; font-size:.8rem; }

/* ── Footer capture ───────────────────────────────────── */
.cap-footer { text-align:center; padding:24px; border-top:1px solid var(--tp-border); font-size:.72rem; color:var(--tp-text3); }
.cap-footer a { color:var(--tp-text2); border-bottom:1px solid var(--tp-border); }

/* ── Edit mode ────────────────────────────────────────── */
.ef-zone { outline:2px dashed rgba(99,102,241,.35); outline-offset:3px; border-radius:4px; cursor:pointer; }
.ef-zone:hover { outline-color:rgba(99,102,241,.8); background:rgba(99,102,241,.04); }

/* ── Responsive ───────────────────────────────────────── */
@media(max-width:900px) {
    .cap-main { grid-template-columns:1fr; }
    .cap-form-card { position:static; }
}
</style>
</head>
<body>

<!-- BARRE TOP -->
<div class="cap-topbar">
    <span class="cap-topbar-badge">GRATUIT</span>
    <span class="cap-topbar-text">Guide offert — Téléchargement immédiat après inscription</span>
</div>

<!-- MAIN -->
<div class="cap-main">

    <!-- GAUCHE : pitch -->
    <div>
        <div class="cap-eyebrow" <?= $editMode ? 'data-field="hero_eyebrow" class="ef-zone"' : '' ?>><?= htmlspecialchars($heroEyebrow) ?></div>
        <h1 class="cap-title" <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroTitle) ?>
        </h1>
        <p class="cap-subtitle" <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroSubtitle) ?>
        </p>

        <div class="cap-benefits">
            <?php foreach ([
                ['icon'=>$ben1Icon,'text'=>$ben1Text,'ki'=>'ben1_icon','kx'=>'ben1_text'],
                ['icon'=>$ben2Icon,'text'=>$ben2Text,'ki'=>'ben2_icon','kx'=>'ben2_text'],
                ['icon'=>$ben3Icon,'text'=>$ben3Text,'ki'=>'ben3_icon','kx'=>'ben3_text'],
            ] as $b): ?>
            <div class="cap-benefit">
                <div class="cap-benefit-icon" <?= $editMode ? 'data-field="'.$b['ki'].'" class="ef-zone"' : '' ?>><?= $b['icon'] ?></div>
                <span <?= $editMode ? 'data-field="'.$b['kx'].'" class="ef-zone"' : '' ?>><?= htmlspecialchars($b['text']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Conseiller -->
        <div class="cap-advisor">
            <?php if ($advisorAvatar): ?>
            <img src="<?= htmlspecialchars($advisorAvatar) ?>" alt="<?= htmlspecialchars($advisorName) ?>" class="cap-advisor-avatar">
            <?php else: ?>
            <div class="cap-advisor-avatar">👤</div>
            <?php endif; ?>
            <div>
                <div class="cap-advisor-name"><?= htmlspecialchars($advisorName) ?></div>
                <div class="cap-advisor-role">Conseiller <?= htmlspecialchars($advisorNetwork) ?> — <?= htmlspecialchars($advisorCity) ?></div>
            </div>
        </div>

        <!-- Témoignages -->
        <div class="cap-testimonials">
            <?php foreach ([
                ['text'=>$sp1Text,'name'=>$sp1Name,'kt'=>'sp1_text','kn'=>'sp1_name'],
                ['text'=>$sp2Text,'name'=>$sp2Name,'kt'=>'sp2_text','kn'=>'sp2_name'],
            ] as $t): if (!$t['text']) continue; ?>
            <div class="cap-testi">
                <div class="cap-testi-text" <?= $editMode ? 'data-field="'.$t['kt'].'" class="ef-zone"' : '' ?>><?= htmlspecialchars($t['text']) ?></div>
                <div class="cap-testi-name" <?= $editMode ? 'data-field="'.$t['kn'].'" class="ef-zone"' : '' ?>><?= htmlspecialchars($t['name']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- DROITE : formulaire -->
    <div>
        <div class="cap-form-card">
            <div class="cap-form-preview">
                <span class="cap-form-preview-badge">Gratuit</span>
            </div>
            <div class="cap-form-title" <?= $editMode ? 'data-field="form_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($formTitle) ?></div>
            <form method="POST" action="<?= htmlspecialchars($formAction) ?>">
                <input type="hidden" name="form_type" value="guide">
                <input type="hidden" name="guide_slug" value="<?= htmlspecialchars($guideSlugUrl) ?>">
                <div class="cap-form-group">
                    <label for="cap-prenom">Prénom</label>
                    <input type="text" id="cap-prenom" name="prenom" placeholder="Votre prénom" required>
                </div>
                <div class="cap-form-group">
                    <label for="cap-email">Email</label>
                    <input type="email" id="cap-email" name="email" placeholder="votre@email.fr" required>
                </div>
                <?php if ($advisorPhone): ?>
                <div class="cap-form-group">
                    <label for="cap-tel">Téléphone <span style="font-weight:400;color:var(--tp-text3);">(facultatif)</span></label>
                    <input type="tel" id="cap-tel" name="telephone" placeholder="06 xx xx xx xx">
                </div>
                <?php endif; ?>
                <button type="submit" class="cap-form-btn" <?= $editMode ? 'data-field="form_cta" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($formCta) ?>
                </button>
                <p class="cap-form-mention" <?= $editMode ? 'data-field="form_mention" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($formMention) ?>
                </p>
                <div class="cap-form-security">Données sécurisées — Aucun spam</div>
            </form>
        </div>
    </div>

</div>

<!-- FOOTER MINIMALISTE -->
<footer class="cap-footer">
    <?= htmlspecialchars($advisorName) ?> — <?= htmlspecialchars($advisorNetwork) ?> — <?= htmlspecialchars($advisorCity) ?>
    <?php if ($legalText): ?> · <span <?= $editMode ? 'data-field="legal_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($legalText) ?></span><?php endif; ?>
    · <a href="<?= htmlspecialchars($siteUrl) ?>/mentions-legales">Mentions légales</a>
    · <a href="<?= htmlspecialchars($siteUrl) ?>/">Retour au site</a>
</footer>

</body>
</html>