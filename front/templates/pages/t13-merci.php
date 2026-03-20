<?php
/**
 * /front/templates/pages/t13-merci.php
 * Page Merci — affichée après soumission du formulaire de contact
 * Utilise le layout standard (header + footer)
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

$advisorName    = $advisor['name']    ?? ($site['name']    ?? 'Stéphanie');
$advisorPhone   = $advisor['phone']   ?? '';
$advisorEmail   = $advisor['email']   ?? '';
$advisorAvatar  = $advisor['avatar']  ?? '';
$siteUrl        = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

// ── Champs éditables ─────────────────────────────────────
$merciTitle     = $fields['merci_title']     ?? 'Merci !';
$merciSubtitle  = $fields['merci_subtitle']  ?? 'Stéphanie vous recontactera sous 24h';
$merciText      = $fields['merci_text']      ?? 'Votre message a bien été envoyé. Je l\'ai reçu et je reviendrai vers vous très rapidement.';

$stepTitle      = $fields['step_title']      ?? 'Prochaines étapes';
$step1          = $fields['step_1']          ?? 'Votre message a été transmis à Stéphanie.';
$step2          = $fields['step_2']          ?? 'Elle prendra connaissance de votre demande dans les plus brefs délais.';
$step3          = $fields['step_3']          ?? 'Vous recevrez une réponse personnalisée sous 24 heures maximum.';

$ctaBtnLabel    = $fields['cta_btn_label']   ?? 'Retour à l\'accueil';
$ctaBtnUrl      = $fields['cta_btn_url']     ?? $siteUrl . '/';
$ctaBtn2Label   = $fields['cta_btn2_label']  ?? 'Voir les biens';
$ctaBtn2Url     = $fields['cta_btn2_url']    ?? $siteUrl . '/biens';

$pageTitle       = $fields['page_title']     ?? 'Merci — ' . $advisorName;
$pageDescription = 'Votre message a bien été envoyé.';

ob_start();
?>

<style>
/* ── Merci Page ──────────────────────────────────────────── */
.merci-hero {
    background: linear-gradient(135deg, #1B3A4B 0%, #0f2a3a 100%);
    color: #fff;
    padding: 80px 20px 60px;
    text-align: center;
}
.merci-check {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981, #059669);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    box-shadow: 0 8px 32px rgba(16,185,129,.3);
    animation: merciPop .45s cubic-bezier(.34,1.56,.64,1) both;
}
.merci-check::after {
    content: '\2713';
    color: #fff;
    font-size: 2.4rem;
    font-weight: 900;
}
@keyframes merciPop {
    from { transform: scale(0); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}
.merci-hero h1 {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: 800;
    margin: 0 0 12px;
}
.merci-hero .merci-subtitle {
    font-size: 1.2rem;
    opacity: .92;
    margin: 0 0 8px;
    font-weight: 500;
}
.merci-hero .merci-desc {
    font-size: .95rem;
    opacity: .7;
    max-width: 520px;
    margin: 0 auto;
    line-height: 1.7;
}

/* ── Steps ───────────────────────────────────────────────── */
.merci-steps-section {
    background: #fff;
    padding: 60px 20px;
}
.merci-steps-wrap {
    max-width: 680px;
    margin: 0 auto;
}
.merci-steps-title {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.5rem;
    font-weight: 800;
    color: #1B3A4B;
    text-align: center;
    margin: 0 0 32px;
}
.merci-steps {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.merci-step {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    background: #f9f6f3;
    border-radius: 12px;
    padding: 20px 24px;
    border-left: 4px solid #d4a574;
}
.merci-step-num {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #1B3A4B;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    font-weight: 800;
    flex-shrink: 0;
    margin-top: 1px;
}
.merci-step span {
    font-size: .95rem;
    color: #4a5568;
    line-height: 1.6;
    padding-top: 4px;
}

/* ── Advisor card ────────────────────────────────────────── */
.merci-advisor {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #fff;
    border: 1px solid #e8ddd4;
    border-radius: 14px;
    padding: 20px 24px;
    margin: 32px auto 0;
    max-width: 480px;
    box-shadow: 0 4px 24px rgba(27,58,75,.08);
}
.merci-advisor-avatar {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    border: 2px solid #d4a574;
    object-fit: cover;
    background: #f9f6f3;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    overflow: hidden;
    flex-shrink: 0;
}
.merci-advisor-info .merci-advisor-name {
    font-weight: 800;
    font-size: .9rem;
    color: #1B3A4B;
    margin-bottom: 2px;
}
.merci-advisor-info .merci-advisor-contact {
    font-size: .8rem;
    color: #718096;
}
.merci-advisor-info .merci-advisor-contact a {
    color: #1B3A4B;
    font-weight: 600;
    text-decoration: none;
}

/* ── CTA buttons ─────────────────────────────────────────── */
.merci-actions {
    display: flex;
    gap: 14px;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 40px;
}
.merci-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #1B3A4B;
    color: #fff;
    font-weight: 700;
    font-size: .9rem;
    padding: 14px 28px;
    border-radius: 50px;
    text-decoration: none;
    box-shadow: 0 4px 16px rgba(27,58,75,.18);
    transition: all .2s;
}
.merci-btn-primary:hover {
    background: #2C5F7C;
    transform: translateY(-2px);
}
.merci-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: transparent;
    color: #4a5568;
    border: 1px solid #e8ddd4;
    font-weight: 600;
    font-size: .9rem;
    padding: 13px 24px;
    border-radius: 50px;
    text-decoration: none;
    transition: all .2s;
}
.merci-btn-secondary:hover {
    background: #f9f6f3;
    border-color: #1B3A4B;
}

@media (max-width: 600px) {
    .merci-hero { padding: 60px 20px 40px; }
    .merci-step { padding: 16px 18px; }
    .merci-actions { flex-direction: column; align-items: center; }
    .merci-advisor { flex-direction: column; text-align: center; }
}
</style>

<!-- HERO -->
<section class="merci-hero">
    <div class="merci-check" aria-hidden="true"></div>
    <h1 <?= $editMode ? 'data-field="merci_title" class="ef-zone"' : '' ?>>
        <?= htmlspecialchars($merciTitle) ?>
    </h1>
    <p class="merci-subtitle" <?= $editMode ? 'data-field="merci_subtitle" class="ef-zone"' : '' ?>>
        <?= htmlspecialchars($merciSubtitle) ?>
    </p>
    <p class="merci-desc" <?= $editMode ? 'data-field="merci_text" class="ef-zone"' : '' ?>>
        <?= htmlspecialchars($merciText) ?>
    </p>
</section>

<!-- PROCHAINES ÉTAPES -->
<section class="merci-steps-section">
    <div class="merci-steps-wrap">
        <h2 class="merci-steps-title" <?= $editMode ? 'data-field="step_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($stepTitle) ?>
        </h2>

        <div class="merci-steps">
            <div class="merci-step">
                <div class="merci-step-num">1</div>
                <span <?= $editMode ? 'data-field="step_1" class="ef-zone"' : '' ?>><?= htmlspecialchars($step1) ?></span>
            </div>
            <div class="merci-step">
                <div class="merci-step-num">2</div>
                <span <?= $editMode ? 'data-field="step_2" class="ef-zone"' : '' ?>><?= htmlspecialchars($step2) ?></span>
            </div>
            <div class="merci-step">
                <div class="merci-step-num">3</div>
                <span <?= $editMode ? 'data-field="step_3" class="ef-zone"' : '' ?>><?= htmlspecialchars($step3) ?></span>
            </div>
        </div>

        <!-- CONSEILLER -->
        <div class="merci-advisor">
            <?php if ($advisorAvatar): ?>
                <img src="<?= htmlspecialchars($advisorAvatar) ?>" alt="<?= htmlspecialchars($advisorName) ?>" class="merci-advisor-avatar">
            <?php else: ?>
                <div class="merci-advisor-avatar"><i class="fas fa-user"></i></div>
            <?php endif; ?>
            <div class="merci-advisor-info">
                <div class="merci-advisor-name"><?= htmlspecialchars($advisorName) ?></div>
                <div class="merci-advisor-contact">
                    <?php if ($advisorPhone): ?>
                        <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $advisorPhone)) ?>">
                            <i class="fas fa-phone"></i> <?= htmlspecialchars($advisorPhone) ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($advisorPhone && $advisorEmail): ?> &middot; <?php endif; ?>
                    <?php if ($advisorEmail): ?>
                        <a href="mailto:<?= htmlspecialchars($advisorEmail) ?>">
                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($advisorEmail) ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="merci-actions">
            <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="merci-btn-primary" <?= $editMode ? 'data-field="cta_btn_label" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($ctaBtnLabel) ?> <i class="fas fa-arrow-right"></i>
            </a>
            <a href="<?= htmlspecialchars($ctaBtn2Url) ?>" class="merci-btn-secondary" <?= $editMode ? 'data-field="cta_btn2_label" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($ctaBtn2Label) ?>
            </a>
        </div>
    </div>
</section>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>
