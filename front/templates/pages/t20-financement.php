<?php
/**
 * /front/templates/pages/t20-financement.php
 * Template Financement — simulateur de prêt + courtiers partenaires
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

$advisorName  = $advisor['name']  ?? ($site['name']  ?? 'Conseiller');
$advisorPhone = $advisor['phone'] ?? '';
require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

// ════════════════════════════════════════════════
// CHAMPS ÉDITABLES
// ════════════════════════════════════════════════

$heroEyebrow  = $fields['hero_eyebrow']  ?? 'Financement immobilier';
$heroTitle    = $fields['hero_title']     ?? 'Financez votre projet immobilier à Lannion';
$heroSubtitle = $fields['hero_subtitle']  ?? 'Simulez votre prêt en quelques clics et découvrez nos courtiers partenaires pour obtenir les meilleures conditions.';
$heroCta      = $fields['hero_cta_text']  ?? 'Simuler mon prêt';

// Simulateur
$simTitle     = $fields['sim_title']      ?? 'Simulateur de prêt immobilier';
$simText      = $fields['sim_text']       ?? 'Estimez vos mensualités en fonction du montant emprunté, de la durée et du taux d\'intérêt.';

// Avantages
$avTitle      = $fields['av_title']       ?? 'Pourquoi passer par un courtier ?';
$av1Icon      = $fields['av1_icon']       ?? '💰';
$av1Title     = $fields['av1_title']      ?? 'Meilleurs taux';
$av1Text      = $fields['av1_text']       ?? 'Un courtier négocie pour vous les meilleures conditions auprès de nombreuses banques.';
$av2Icon      = $fields['av2_icon']       ?? '⏱️';
$av2Title     = $fields['av2_title']      ?? 'Gain de temps';
$av2Text      = $fields['av2_text']       ?? 'Plus besoin de démarcher chaque banque : votre courtier s\'occupe de tout.';
$av3Icon      = $fields['av3_icon']       ?? '🛡️';
$av3Title     = $fields['av3_title']      ?? 'Accompagnement complet';
$av3Text      = $fields['av3_text']       ?? 'Du montage du dossier jusqu\'à la signature chez le notaire, vous êtes accompagné.';

// Courtiers
$courtTitle   = $fields['court_title']    ?? 'Nos courtiers partenaires à Lannion';
$courtText    = $fields['court_text']     ?? 'Des professionnels de confiance sélectionnés pour leur expertise et leur connaissance du marché local.';
$court1Name   = $fields['court1_name']    ?? 'Courtier partenaire 1';
$court1Desc   = $fields['court1_desc']    ?? 'Spécialiste du crédit immobilier, accompagnement personnalisé pour votre projet.';
$court1Phone  = $fields['court1_phone']   ?? '';
$court2Name   = $fields['court2_name']    ?? 'Courtier partenaire 2';
$court2Desc   = $fields['court2_desc']    ?? 'Expert en financement immobilier et renégociation de prêts.';
$court2Phone  = $fields['court2_phone']   ?? '';
$court3Name   = $fields['court3_name']    ?? 'Courtier partenaire 3';
$court3Desc   = $fields['court3_desc']    ?? 'Courtier indépendant, solutions sur mesure pour primo-accédants et investisseurs.';
$court3Phone  = $fields['court3_phone']   ?? '';

// Guide SEO
$guideTitle   = $fields['guide_title']    ?? 'Tout savoir sur le financement immobilier à Lannion';
$g1Num        = $fields['g1_num']         ?? '01';
$g1Title      = $fields['g1_title']       ?? 'Préparer son dossier de financement';
$g1Text       = $fields['g1_text']        ?? '<p>Un dossier solide est la clé pour obtenir les meilleures conditions. Rassemblez vos justificatifs de revenus, votre apport personnel et votre situation professionnelle.</p>';
$g2Num        = $fields['g2_num']         ?? '02';
$g2Title      = $fields['g2_title']       ?? 'Comprendre les taux et les durées';
$g2Text       = $fields['g2_text']        ?? '<p>Le taux d\'intérêt et la durée du prêt impactent directement vos mensualités. Un courtier vous aide à trouver l\'équilibre optimal.</p>';
$g3Num        = $fields['g3_num']         ?? '03';
$g3Title      = $fields['g3_title']       ?? 'Les aides au financement';
$g3Text       = $fields['g3_text']        ?? '<p>PTZ, prêt Action Logement, aides locales… Découvrez les dispositifs auxquels vous pouvez prétendre pour votre achat à Lannion.</p>';

// CTA finale
$ctaTitle     = $fields['cta_title']      ?? 'Besoin d\'un conseil personnalisé ?';
$ctaText      = $fields['cta_text']       ?? 'Je vous accompagne dans toutes les étapes de votre projet immobilier, y compris le financement.';
$ctaBtnText   = $fields['cta_btn_text']   ?? 'Me contacter';
$ctaBtnUrl    = $fields['cta_btn_url']    ?? '/contact';

$estimationUrl = _findMenuUrl($headerMenu['items'] ?? [], 'Estimation', '/estimation');
$contactUrl    = _findMenuUrl($headerMenu['items'] ?? [], 'Contact', '/contact');

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
        <a href="#simulateur" class="tp-hero-cta"
           <?= $editMode ? 'data-field="hero_cta_text"' : '' ?>><?= htmlspecialchars($heroCta) ?></a>
    </div>
</section>

<!-- SIMULATEUR DE PRÊT -->
<section id="simulateur" class="tp-section-white">
    <div class="tp-container" style="max-width:760px;">
        <div class="tp-section-badge" style="display:flex; justify-content:center; width:100%; text-align:center;">🧮 Simulateur</div>
        <h2 <?= $editMode ? 'data-field="sim_title" class="ef-zone"' : '' ?>
            class="tp-section-title"><?= htmlspecialchars($simTitle) ?></h2>
        <p <?= $editMode ? 'data-field="sim_text" class="ef-zone"' : '' ?>
           style="text-align:center; color:var(--tp-text2); margin-bottom:40px;"><?= htmlspecialchars($simText) ?></p>

        <div class="tp-card" style="padding:40px;">
            <div class="form-group">
                <label class="form-label" for="sim-montant">Montant emprunté (€)</label>
                <input type="number" id="sim-montant" class="form-input" value="200000" min="10000" max="2000000" step="5000">
            </div>
            <div class="form-row" style="margin-bottom:20px;">
                <div class="form-group">
                    <label class="form-label" for="sim-duree">Durée (années)</label>
                    <input type="number" id="sim-duree" class="form-input" value="20" min="5" max="30">
                </div>
                <div class="form-group">
                    <label class="form-label" for="sim-taux">Taux d'intérêt (%)</label>
                    <input type="number" id="sim-taux" class="form-input" value="3.5" min="0.1" max="10" step="0.1">
                </div>
            </div>
            <button type="button" onclick="calculerPret()" class="form-submit" style="margin-bottom:24px;">Calculer mes mensualités</button>

            <div id="sim-resultat" style="display:none; background:var(--tp-bg); border-radius:var(--tp-radius); padding:28px; text-align:center;">
                <p style="color:var(--tp-text2); margin:0 0 8px; font-size:.85rem; text-transform:uppercase; letter-spacing:.05em; font-weight:700;">Mensualité estimée</p>
                <p id="sim-mensualite" style="font-family:var(--tp-ff-display); font-size:2.8rem; font-weight:900; color:var(--tp-primary); margin:0 0 8px;"></p>
                <p id="sim-cout-total" style="color:var(--tp-text3); font-size:.85rem; margin:0;"></p>
            </div>
        </div>

        <p style="text-align:center; color:var(--tp-text3); font-size:.8rem; margin-top:16px;">
            * Simulation indicative hors assurance. Contactez un courtier pour une étude personnalisée.
        </p>
    </div>
</section>

<script>
function calculerPret() {
    var montant = parseFloat(document.getElementById('sim-montant').value);
    var duree   = parseInt(document.getElementById('sim-duree').value);
    var taux    = parseFloat(document.getElementById('sim-taux').value) / 100 / 12;
    var nbMois  = duree * 12;

    if (montant <= 0 || duree <= 0 || taux <= 0) return;

    var mensualite = montant * taux / (1 - Math.pow(1 + taux, -nbMois));
    var coutTotal  = mensualite * nbMois;
    var coutInterets = coutTotal - montant;

    document.getElementById('sim-mensualite').textContent = Math.round(mensualite).toLocaleString('fr-FR') + ' €/mois';
    document.getElementById('sim-cout-total').textContent = 'Coût total du crédit : ' + Math.round(coutInterets).toLocaleString('fr-FR') + ' € d\'intérêts sur ' + duree + ' ans';
    document.getElementById('sim-resultat').style.display = 'block';
}
</script>

<!-- AVANTAGES COURTIER -->
<section class="tp-section-light">
    <div class="tp-container">
        <div class="tp-section-badge" style="display:flex; justify-content:center; width:100%; text-align:center;">🤝 Avantages</div>
        <h2 <?= $editMode ? 'data-field="av_title" class="ef-zone"' : '' ?>
            class="tp-section-title"><?= htmlspecialchars($avTitle) ?></h2>

        <div class="tp-grid-3">
            <div class="tp-card" style="text-align:center;">
                <div style="font-size:2.5rem; margin-bottom:16px;"
                     <?= $editMode ? 'data-field="av1_icon" class="ef-zone"' : '' ?>><?= htmlspecialchars($av1Icon) ?></div>
                <h3 <?= $editMode ? 'data-field="av1_title" class="ef-zone"' : '' ?>
                    style="font-family:var(--tp-ff-display); font-size:1.1rem; font-weight:800; color:var(--tp-primary); margin-bottom:12px;"><?= htmlspecialchars($av1Title) ?></h3>
                <p <?= $editMode ? 'data-field="av1_text" class="ef-zone"' : '' ?>
                   style="color:var(--tp-text2); font-size:.9rem; line-height:1.7;"><?= htmlspecialchars($av1Text) ?></p>
            </div>
            <div class="tp-card" style="text-align:center;">
                <div style="font-size:2.5rem; margin-bottom:16px;"
                     <?= $editMode ? 'data-field="av2_icon" class="ef-zone"' : '' ?>><?= htmlspecialchars($av2Icon) ?></div>
                <h3 <?= $editMode ? 'data-field="av2_title" class="ef-zone"' : '' ?>
                    style="font-family:var(--tp-ff-display); font-size:1.1rem; font-weight:800; color:var(--tp-primary); margin-bottom:12px;"><?= htmlspecialchars($av2Title) ?></h3>
                <p <?= $editMode ? 'data-field="av2_text" class="ef-zone"' : '' ?>
                   style="color:var(--tp-text2); font-size:.9rem; line-height:1.7;"><?= htmlspecialchars($av2Text) ?></p>
            </div>
            <div class="tp-card" style="text-align:center;">
                <div style="font-size:2.5rem; margin-bottom:16px;"
                     <?= $editMode ? 'data-field="av3_icon" class="ef-zone"' : '' ?>><?= htmlspecialchars($av3Icon) ?></div>
                <h3 <?= $editMode ? 'data-field="av3_title" class="ef-zone"' : '' ?>
                    style="font-family:var(--tp-ff-display); font-size:1.1rem; font-weight:800; color:var(--tp-primary); margin-bottom:12px;"><?= htmlspecialchars($av3Title) ?></h3>
                <p <?= $editMode ? 'data-field="av3_text" class="ef-zone"' : '' ?>
                   style="color:var(--tp-text2); font-size:.9rem; line-height:1.7;"><?= htmlspecialchars($av3Text) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- COURTIERS PARTENAIRES -->
<section class="tp-section-white">
    <div class="tp-container">
        <div class="tp-section-badge" style="display:flex; justify-content:center; width:100%; text-align:center;">🏦 Partenaires</div>
        <h2 <?= $editMode ? 'data-field="court_title" class="ef-zone"' : '' ?>
            class="tp-section-title"><?= htmlspecialchars($courtTitle) ?></h2>
        <p <?= $editMode ? 'data-field="court_text" class="ef-zone"' : '' ?>
           style="text-align:center; color:var(--tp-text2); max-width:620px; margin:0 auto 48px; line-height:1.7;"><?= htmlspecialchars($courtText) ?></p>

        <div class="tp-grid-3">
            <?php for ($i = 1; $i <= 3; $i++):
                $cName  = ${'court'.$i.'Name'};
                $cDesc  = ${'court'.$i.'Desc'};
                $cPhone = ${'court'.$i.'Phone'};
            ?>
            <div class="tp-card">
                <div style="font-size:2rem; margin-bottom:12px;">🏢</div>
                <h3 <?= $editMode ? 'data-field="court'.$i.'_name" class="ef-zone"' : '' ?>
                    style="font-family:var(--tp-ff-display); font-size:1.15rem; font-weight:800; color:var(--tp-primary); margin-bottom:12px;"><?= htmlspecialchars($cName) ?></h3>
                <p <?= $editMode ? 'data-field="court'.$i.'_desc" class="ef-zone"' : '' ?>
                   style="color:var(--tp-text2); font-size:.88rem; line-height:1.7; margin-bottom:16px;"><?= htmlspecialchars($cDesc) ?></p>
                <?php if ($cPhone): ?>
                <a href="tel:<?= htmlspecialchars(str_replace(' ', '', $cPhone)) ?>"
                   style="display:inline-flex; align-items:center; gap:6px; color:var(--tp-accent-d); font-weight:700; font-size:.9rem;">
                    📞 <span <?= $editMode ? 'data-field="court'.$i.'_phone" class="ef-zone"' : '' ?>><?= htmlspecialchars($cPhone) ?></span>
                </a>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- GUIDE SEO -->
<section class="tp-section-light">
    <div class="tp-container">
        <div class="tp-section-badge" style="display:flex; justify-content:center; width:100%; text-align:center;">📖 Guide</div>
        <h2 <?= $editMode ? 'data-field="guide_title" class="ef-zone"' : '' ?>
            class="tp-section-title"><?= htmlspecialchars($guideTitle) ?></h2>

        <div style="display:flex; flex-direction:column; gap:24px;">
            <?php for ($i = 1; $i <= 3; $i++):
                $gNum   = ${'g'.$i.'Num'};
                $gTitle = ${'g'.$i.'Title'};
                $gText  = ${'g'.$i.'Text'};
            ?>
            <div class="tp-guide-item">
                <div <?= $editMode ? 'data-field="g'.$i.'_num" class="ef-zone"' : '' ?>
                     class="tp-guide-num"><?= htmlspecialchars($gNum) ?></div>
                <div>
                    <h3 <?= $editMode ? 'data-field="g'.$i.'_title" class="ef-zone"' : '' ?>
                        class="tp-guide-h3"><?= htmlspecialchars($gTitle) ?></h3>
                    <div <?= $editMode ? 'data-field="g'.$i.'_text" class="ef-zone ef-rich"' : '' ?>
                         class="tp-guide-body"><?= $gText ?></div>
                </div>
            </div>
            <?php endfor; ?>
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
        <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="tp-cta-btn"
           <?= $editMode ? 'data-field="cta_btn_text"' : '' ?>><?= htmlspecialchars($ctaBtnText) ?></a>
    </div>
</section>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>
