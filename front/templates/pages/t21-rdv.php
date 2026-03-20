<?php
/**
 * /front/templates/pages/t21-rdv.php
 * Template RDV en ligne — prise de rendez-vous
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

$advisorName  = $advisor['name']  ?? ($site['name']  ?? 'Conseiller');
$advisorPhone = $advisor['phone'] ?? '';
$advisorEmail = $advisor['email'] ?? '';
require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

// ════════════════════════════════════════════════
// CHAMPS ÉDITABLES
// ════════════════════════════════════════════════

$heroEyebrow  = $fields['hero_eyebrow']  ?? 'Rendez-vous en ligne';
$heroTitle    = $fields['hero_title']     ?? 'Prenez rendez-vous avec ' . htmlspecialchars($advisorName);
$heroSubtitle = $fields['hero_subtitle']  ?? 'Choisissez le créneau qui vous convient pour discuter de votre projet immobilier.';

// Types de RDV
$typesTitle   = $fields['types_title']    ?? 'Quel type de rendez-vous souhaitez-vous ?';
$type1Icon    = $fields['type1_icon']     ?? '🏠';
$type1Title   = $fields['type1_title']    ?? 'Estimation de bien';
$type1Text    = $fields['type1_text']     ?? 'Faites estimer votre bien gratuitement. Je me déplace chez vous pour une évaluation précise.';
$type1Duree   = $fields['type1_duree']    ?? '45 min';
$type2Icon    = $fields['type2_icon']     ?? '🔍';
$type2Title   = $fields['type2_title']    ?? 'Projet d\'achat';
$type2Text    = $fields['type2_text']     ?? 'Discutons de votre projet d\'achat, vos critères de recherche et votre budget.';
$type2Duree   = $fields['type2_duree']    ?? '30 min';
$type3Icon    = $fields['type3_icon']     ?? '📋';
$type3Title   = $fields['type3_title']    ?? 'Mise en vente';
$type3Text    = $fields['type3_text']     ?? 'Préparons ensemble la mise en vente de votre bien : stratégie, prix, mise en valeur.';
$type3Duree   = $fields['type3_duree']    ?? '60 min';
$type4Icon    = $fields['type4_icon']     ?? '💼';
$type4Title   = $fields['type4_title']    ?? 'Conseil investissement';
$type4Text    = $fields['type4_text']     ?? 'Étudions vos opportunités d\'investissement immobilier dans la région de Lannion.';
$type4Duree   = $fields['type4_duree']    ?? '30 min';

// Calendrier / Booking
$bookTitle    = $fields['book_title']     ?? 'Réservez votre créneau';
$bookText     = $fields['book_text']      ?? 'Sélectionnez une date et un horaire qui vous conviennent. Je vous confirmerai le rendez-vous par email.';
$bookUrl      = $fields['book_url']       ?? '';
$bookBtnText  = $fields['book_btn_text']  ?? 'Ouvrir l\'agenda en ligne';

// Infos pratiques
$infoTitle    = $fields['info_title']     ?? 'Informations pratiques';
$infoLieu     = $fields['info_lieu']      ?? 'En agence, à domicile ou en visioconférence';
$infoHoraires = $fields['info_horaires']  ?? 'Du lundi au samedi, de 9h à 19h';
$infoDelai    = $fields['info_delai']     ?? 'Confirmation sous 2h (jours ouvrés)';

// CTA
$ctaTitle     = $fields['cta_title']      ?? 'Vous préférez m\'appeler directement ?';
$ctaText      = $fields['cta_text']       ?? 'Je suis disponible par téléphone pour répondre à vos questions.';
$ctaPhoneText = $fields['cta_phone_text'] ?? $advisorPhone;

// ════════════════════════════════════════════════
// CONTENU HTML
// ════════════════════════════════════════════════

ob_start();
require_once __DIR__ . '/_tpl-common.php';
?>

<!-- HERO -->
<section class="tp-hero">
    <div class="tp-hero-inner">
        <div <?= $editMode ? 'data-field="hero_eyebrow" class="ef-zone"' : '' ?>
             class="tp-eyebrow"><?= htmlspecialchars($heroEyebrow) ?></div>
        <h1 <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?>
            class="tp-hero-h1"><?= htmlspecialchars($heroTitle) ?></h1>
        <p <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?>
           class="tp-hero-sub"><?= htmlspecialchars($heroSubtitle) ?></p>
        <a href="#reserver" class="tp-hero-cta">Choisir un créneau</a>
    </div>
</section>

<!-- TYPES DE RDV -->
<section class="tp-section-white">
    <div class="tp-container">
        <div class="tp-section-badge" style="display:flex; justify-content:center; width:100%; text-align:center;">📅 Types de RDV</div>
        <h2 <?= $editMode ? 'data-field="types_title" class="ef-zone"' : '' ?>
            class="tp-section-title"><?= htmlspecialchars($typesTitle) ?></h2>

        <div class="tp-grid-2">
            <?php for ($i = 1; $i <= 4; $i++):
                $tIcon  = ${'type'.$i.'Icon'};
                $tTitle = ${'type'.$i.'Title'};
                $tText  = ${'type'.$i.'Text'};
                $tDuree = ${'type'.$i.'Duree'};
            ?>
            <div class="tp-card" style="display:flex; gap:20px; align-items:start;">
                <div style="font-size:2.2rem; flex-shrink:0;"
                     <?= $editMode ? 'data-field="type'.$i.'_icon" class="ef-zone"' : '' ?>><?= htmlspecialchars($tIcon) ?></div>
                <div>
                    <h3 <?= $editMode ? 'data-field="type'.$i.'_title" class="ef-zone"' : '' ?>
                        style="font-family:var(--tp-ff-display); font-size:1.1rem; font-weight:800; color:var(--tp-primary); margin:0 0 8px;"><?= htmlspecialchars($tTitle) ?></h3>
                    <p <?= $editMode ? 'data-field="type'.$i.'_text" class="ef-zone"' : '' ?>
                       style="color:var(--tp-text2); font-size:.88rem; line-height:1.7; margin:0 0 10px;"><?= htmlspecialchars($tText) ?></p>
                    <span <?= $editMode ? 'data-field="type'.$i.'_duree" class="ef-zone"' : '' ?>
                          style="display:inline-block; background:var(--tp-bg); border:1px solid var(--tp-border); border-radius:50px; padding:4px 14px; font-size:.78rem; font-weight:700; color:var(--tp-primary);">⏱ <?= htmlspecialchars($tDuree) ?></span>
                </div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- RÉSERVATION -->
<section id="reserver" class="tp-section-light">
    <div class="tp-container" style="max-width:760px;">
        <div class="tp-section-badge" style="display:flex; justify-content:center; width:100%; text-align:center;">🗓️ Réservation</div>
        <h2 <?= $editMode ? 'data-field="book_title" class="ef-zone"' : '' ?>
            class="tp-section-title"><?= htmlspecialchars($bookTitle) ?></h2>
        <p <?= $editMode ? 'data-field="book_text" class="ef-zone"' : '' ?>
           style="text-align:center; color:var(--tp-text2); max-width:560px; margin:0 auto 40px; line-height:1.7;"><?= htmlspecialchars($bookText) ?></p>

        <?php if ($bookUrl): ?>
        <!-- Calendrier intégré (Calendly / Cal.com / autre) -->
        <div style="background:var(--tp-white); border:1px solid var(--tp-border); border-radius:var(--tp-radius); overflow:hidden; min-height:600px; margin-bottom:24px;">
            <iframe src="<?= htmlspecialchars($bookUrl) ?>" width="100%" height="650" frameborder="0" style="border:0;"></iframe>
        </div>
        <?php else: ?>
        <!-- Placeholder booking -->
        <div class="tp-card" style="text-align:center; padding:60px 40px;">
            <div style="font-size:4rem; margin-bottom:20px;">📅</div>
            <h3 style="font-family:var(--tp-ff-display); font-size:1.3rem; font-weight:800; color:var(--tp-primary); margin-bottom:12px;">Agenda en ligne</h3>
            <p style="color:var(--tp-text2); margin-bottom:24px; line-height:1.7;">
                Cliquez sur le bouton ci-dessous pour accéder à mon agenda et réserver un créneau.
            </p>
            <a href="<?= htmlspecialchars($bookUrl ?: $contactUrl ?? '/contact') ?>" class="tp-btn-gold"
               <?= $editMode ? 'data-field="book_btn_text"' : '' ?>><?= htmlspecialchars($bookBtnText) ?></a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- INFOS PRATIQUES -->
<section class="tp-section-white">
    <div class="tp-container">
        <div class="tp-section-badge" style="display:flex; justify-content:center; width:100%; text-align:center;">ℹ️ Infos</div>
        <h2 <?= $editMode ? 'data-field="info_title" class="ef-zone"' : '' ?>
            class="tp-section-title"><?= htmlspecialchars($infoTitle) ?></h2>

        <div class="tp-grid-3">
            <div class="tp-card" style="text-align:center;">
                <div style="font-size:2rem; margin-bottom:12px;">📍</div>
                <h3 style="font-weight:800; font-size:.95rem; color:var(--tp-primary); margin-bottom:8px;">Lieu</h3>
                <p <?= $editMode ? 'data-field="info_lieu" class="ef-zone"' : '' ?>
                   style="color:var(--tp-text2); font-size:.88rem; line-height:1.6;"><?= htmlspecialchars($infoLieu) ?></p>
            </div>
            <div class="tp-card" style="text-align:center;">
                <div style="font-size:2rem; margin-bottom:12px;">🕐</div>
                <h3 style="font-weight:800; font-size:.95rem; color:var(--tp-primary); margin-bottom:8px;">Horaires</h3>
                <p <?= $editMode ? 'data-field="info_horaires" class="ef-zone"' : '' ?>
                   style="color:var(--tp-text2); font-size:.88rem; line-height:1.6;"><?= htmlspecialchars($infoHoraires) ?></p>
            </div>
            <div class="tp-card" style="text-align:center;">
                <div style="font-size:2rem; margin-bottom:12px;">⚡</div>
                <h3 style="font-weight:800; font-size:.95rem; color:var(--tp-primary); margin-bottom:8px;">Confirmation</h3>
                <p <?= $editMode ? 'data-field="info_delai" class="ef-zone"' : '' ?>
                   style="color:var(--tp-text2); font-size:.88rem; line-height:1.6;"><?= htmlspecialchars($infoDelai) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- CTA FINALE -->
<section class="tp-cta-section">
    <div class="tp-container" style="max-width:700px;">
        <h2 <?= $editMode ? 'data-field="cta_title" class="ef-zone"' : '' ?>
            class="tp-cta-title"><?= htmlspecialchars($ctaTitle) ?></h2>
        <p <?= $editMode ? 'data-field="cta_text" class="ef-zone"' : '' ?>
           class="tp-cta-text"><?= htmlspecialchars($ctaText) ?></p>
        <?php if ($ctaPhoneText): ?>
        <a href="tel:<?= htmlspecialchars(str_replace(' ', '', $ctaPhoneText)) ?>" class="tp-cta-btn"
           <?= $editMode ? 'data-field="cta_phone_text"' : '' ?>>📞 <?= htmlspecialchars($ctaPhoneText) ?></a>
        <?php endif; ?>
    </div>
</section>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>
