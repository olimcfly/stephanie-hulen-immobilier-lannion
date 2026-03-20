<?php
/**
 * /front/templates/pages/t14-apropos.php
 * Template À Propos — Biographie complète de Stephanie Hulen
 * Sections : Hero, Bio + Photo, Parcours, Valeurs, Réseau, CTA
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;
$siteUrl    = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';

$advisorName    = $advisor['name']    ?? ($site['name']    ?? 'Stephanie Hulen');
$advisorCity    = $advisor['city']    ?? ($site['city']    ?? 'Lannion');
$advisorNetwork = $advisor['network'] ?? 'eXp France';
$advisorPhone   = $advisor['phone']   ?? '';

require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

// ────────────────────────────────────────────────────
// CHAMPS HERO
// ────────────────────────────────────────────────────
$heroEyebrow  = $fields['hero_eyebrow']  ?? 'Conseillère immobilière à ' . $advisorCity;
$heroTitle    = $fields['hero_title']     ?? 'À propos de ' . $advisorName;
$heroSubtitle = $fields['hero_subtitle']  ?? 'Passionnée par l\'immobilier, engagée pour vous accompagner dans chaque étape de votre projet.';

// ── Bio ──
$bioTitle = $fields['bio_title'] ?? 'Qui suis-je ?';
$bioText  = $fields['bio_text']  ?? '<p>Je suis <strong>Stephanie Hulen</strong>, conseillère immobilière indépendante basée à <strong>Lannion</strong>, dans les Côtes-d\'Armor. Passionnée par l\'immobilier et profondément attachée à la Bretagne, j\'ai fait le choix de mettre mon énergie et mon expertise au service des habitants de Lannion et de sa région.</p>
<p>Mon parcours m\'a conduite à découvrir l\'immobilier après plusieurs années d\'expérience professionnelle dans des domaines variés. Cette diversité de compétences est aujourd\'hui un véritable atout : elle me permet de comprendre les enjeux financiers, juridiques et humains de chaque transaction, et d\'offrir un accompagnement complet et personnalisé à mes clients.</p>
<p>Rejoindre le réseau <strong>eXp France</strong> a été une décision naturelle : je partage la vision d\'un immobilier moderne, transparent et centré sur le client. Ce réseau international me donne accès à des outils performants, une formation continue de haut niveau et un réseau de professionnels partout en France, tout en me laissant la liberté d\'exercer au plus près de mes clients, avec l\'attention et la disponibilité qu\'ils méritent.</p>';
$bioPhoto = $fields['bio_photo'] ?? '';

// ── Parcours ──
$parcoursTitle = $fields['parcours_title'] ?? 'Mon parcours';
$parcours1Icon  = $fields['parcours1_icon']  ?? '🎓';
$parcours1Title = $fields['parcours1_title'] ?? 'Formation immobilière';
$parcours1Text  = $fields['parcours1_text']  ?? 'Formation certifiante en transaction immobilière, droit immobilier et techniques de négociation. Formation continue via l\'académie eXp France.';
$parcours2Icon  = $fields['parcours2_icon']  ?? '🏢';
$parcours2Title = $fields['parcours2_title'] ?? 'Expérience professionnelle';
$parcours2Text  = $fields['parcours2_text']  ?? 'Plusieurs années d\'expérience dans le conseil et l\'accompagnement client, puis spécialisation dans l\'immobilier résidentiel sur le secteur de Lannion et la côte de Granit Rose.';
$parcours3Icon  = $fields['parcours3_icon']  ?? '📜';
$parcours3Title = $fields['parcours3_title'] ?? 'Certifications';
$parcours3Text  = $fields['parcours3_text']  ?? 'Agent commercial en immobilier inscrit au RSAC. Carte professionnelle de transaction sur immeubles et fonds de commerce. Garantie financière et assurance responsabilité civile professionnelle.';
$parcours4Icon  = $fields['parcours4_icon']  ?? '📊';
$parcours4Title = $fields['parcours4_title'] ?? 'Expertise locale';
$parcours4Text  = $fields['parcours4_text']  ?? 'Connaissance approfondie du marché immobilier de Lannion, Perros-Guirec, Trébeurden, Pleumeur-Bodou et l\'ensemble du Trégor. Veille permanente sur les prix et les tendances du secteur.';

// ── Stats ──
$stat1Num = $fields['stat1_num'] ?? '100%';
$stat1Lbl = $fields['stat1_lbl'] ?? 'engagement client';
$stat2Num = $fields['stat2_num'] ?? '45j';
$stat2Lbl = $fields['stat2_lbl'] ?? 'délai moyen de vente';
$stat3Num = $fields['stat3_num'] ?? '5/5';
$stat3Lbl = $fields['stat3_lbl'] ?? 'satisfaction clients';

// ── Valeurs ──
$valeursTitle = $fields['valeurs_title'] ?? 'Mes valeurs et ma philosophie';
$valeursSub   = $fields['valeurs_sub']   ?? 'Ce qui guide mon accompagnement au quotidien';
$valeur1Icon  = $fields['valeur1_icon']  ?? '🤝';
$valeur1Title = $fields['valeur1_title'] ?? 'Écoute et bienveillance';
$valeur1Text  = $fields['valeur1_text']  ?? 'Chaque projet est unique. Je prends le temps de vous écouter, de comprendre vos besoins, vos envies et vos contraintes avant de vous proposer des solutions adaptées.';
$valeur2Icon  = $fields['valeur2_icon']  ?? '🔍';
$valeur2Title = $fields['valeur2_title'] ?? 'Transparence totale';
$valeur2Text  = $fields['valeur2_text']  ?? 'Pas de surprises, pas de zones d\'ombre. Je vous informe à chaque étape, je vous explique les procédures et je vous donne tous les éléments pour prendre les meilleures décisions.';
$valeur3Icon  = $fields['valeur3_icon']  ?? '💪';
$valeur3Title = $fields['valeur3_title'] ?? 'Engagement et réactivité';
$valeur3Text  = $fields['valeur3_text']  ?? 'Disponible et proactive, je m\'investis pleinement dans chaque projet. Votre réussite est ma priorité, et je mets tout en œuvre pour obtenir le meilleur résultat pour vous.';
$valeur4Icon  = $fields['valeur4_icon']  ?? '🏠';
$valeur4Title = $fields['valeur4_title'] ?? 'Ancrage local';
$valeur4Text  = $fields['valeur4_text']  ?? 'Vivre et travailler à Lannion, c\'est connaître chaque quartier, chaque rue, chaque nuance du marché. Cette proximité me permet de vous offrir des conseils précis et pertinents.';

// ── Réseau ──
$reseauTitle = $fields['reseau_title'] ?? 'Mon réseau : ' . $advisorNetwork;
$reseauText  = $fields['reseau_text']  ?? '<p>Je suis fière de faire partie d\'<strong>eXp France</strong>, un réseau immobilier international innovant présent dans plus de 20 pays. Ce modèle unique me permet de combiner la puissance d\'un grand réseau avec la proximité d\'une conseillère locale dédiée.</p>
<p>Grâce à eXp France, je bénéficie d\'<strong>outils technologiques de pointe</strong> pour la valorisation de vos biens (visites virtuelles, diffusion multi-portails, marketing digital), d\'une <strong>formation continue</strong> avec les meilleurs experts du secteur, et d\'un <strong>réseau de partenaires</strong> qui me permet de vous accompagner même dans les projets les plus ambitieux.</p>
<p>Ce qui me distingue ? L\'alliance entre la <strong>force d\'un réseau international</strong> et la <strong>relation humaine de proximité</strong> que je cultive avec chacun de mes clients à Lannion et dans le Trégor.</p>';
$reseauTag1 = $fields['reseau_tag1'] ?? '✓ Réseau international';
$reseauTag2 = $fields['reseau_tag2'] ?? '✓ Outils digitaux avancés';
$reseauTag3 = $fields['reseau_tag3'] ?? '✓ Formation continue';
$reseauTag4 = $fields['reseau_tag4'] ?? '✓ Garanties professionnelles';

// ── CTA Finale ──
$ctaTitle   = $fields['cta_title']    ?? 'Envie d\'en savoir plus ?';
$ctaText    = $fields['cta_text']     ?? 'Que vous souhaitiez vendre, acheter ou simplement obtenir un avis sur votre projet immobilier, je suis là pour vous. Prenons rendez-vous pour en discuter.';
$ctaBtnText = $fields['cta_btn_text'] ?? 'Me contacter';
$ctaBtnUrl  = $fields['cta_btn_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Contact', $siteUrl . '/contact');
$ctaEstText = $fields['cta_est_text'] ?? 'Demander une estimation gratuite';
$ctaEstUrl  = $fields['cta_est_url']  ?? _findMenuUrl($headerMenu['items'] ?? [], 'Estimation', $siteUrl . '/estimation');

// ── Meta ──
$pageTitle       = $fields['hero_title'] ?? 'À propos de ' . $advisorName . ' | Immobilier à ' . $advisorCity;
$pageDescription = $fields['hero_subtitle'] ?? 'Découvrez le parcours et les valeurs de ' . $advisorName . ', conseillère immobilière à ' . $advisorCity . '.';

ob_start();
require_once __DIR__ . '/_tpl-common.php';
?>
<link rel="stylesheet" href="<?= $siteUrl ?>/front/templates/pages/css/t14-apropos.css">

<!-- ═══════════════════════════════════════════════════
     HERO
     ═══════════════════════════════════════════════════ -->
<section class="tp-hero" aria-label="À propos">
    <div class="tp-hero-inner">
        <span class="tp-eyebrow" <?= $editMode ? 'data-field="hero_eyebrow" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroEyebrow) ?>
        </span>

        <h1 class="tp-hero-h1" <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroTitle) ?>
        </h1>

        <p class="tp-hero-sub" <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroSubtitle) ?>
        </p>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     STATS
     ═══════════════════════════════════════════════════ -->
<div class="tp-stats-row" style="grid-template-columns:repeat(3,1fr)">
    <div class="tp-stat">
        <div class="tp-stat-num" <?= $editMode ? 'data-field="stat1_num" class="ef-zone"' : '' ?>><?= htmlspecialchars($stat1Num) ?></div>
        <div class="tp-stat-lbl" <?= $editMode ? 'data-field="stat1_lbl" class="ef-zone"' : '' ?>><?= htmlspecialchars($stat1Lbl) ?></div>
    </div>
    <div class="tp-stat">
        <div class="tp-stat-num" <?= $editMode ? 'data-field="stat2_num" class="ef-zone"' : '' ?>><?= htmlspecialchars($stat2Num) ?></div>
        <div class="tp-stat-lbl" <?= $editMode ? 'data-field="stat2_lbl" class="ef-zone"' : '' ?>><?= htmlspecialchars($stat2Lbl) ?></div>
    </div>
    <div class="tp-stat">
        <div class="tp-stat-num" <?= $editMode ? 'data-field="stat3_num" class="ef-zone"' : '' ?>><?= htmlspecialchars($stat3Num) ?></div>
        <div class="tp-stat-lbl" <?= $editMode ? 'data-field="stat3_lbl" class="ef-zone"' : '' ?>><?= htmlspecialchars($stat3Lbl) ?></div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════
     BIO + PHOTO
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-white" aria-label="Biographie">
    <div class="tp-container">
        <div class="ap-bio-grid">
            <div class="ap-bio-content">
                <span class="tp-section-badge" <?= $editMode ? 'data-field="bio_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($bioTitle) ?></span>
                <h2 style="font-family:var(--tp-ff-display);font-size:clamp(1.5rem,3vw,2.2rem);font-weight:800;color:var(--tp-primary);margin:0 0 24px;letter-spacing:-.02em">
                    <?= htmlspecialchars($advisorName) ?>, votre conseillère à <?= htmlspecialchars($advisorCity) ?>
                </h2>
                <div class="tp-rich-body" <?= $editMode ? 'data-field="bio_text" class="ef-zone ef-rich"' : '' ?>>
                    <?= $bioText ?>
                </div>
            </div>
            <div class="ap-bio-photo">
                <?php if ($bioPhoto): ?>
                    <img src="<?= htmlspecialchars($bioPhoto) ?>" alt="Portrait de <?= htmlspecialchars($advisorName) ?>" class="ap-photo-img">
                <?php else: ?>
                    <div class="ap-photo-placeholder">
                        <div class="ap-photo-initials"><?= mb_substr($advisorName, 0, 1) ?></div>
                        <p class="ap-photo-name"><?= htmlspecialchars($advisorName) ?></p>
                        <p class="ap-photo-role"><?= htmlspecialchars($advisorNetwork) ?></p>
                        <p class="ap-photo-city"><?= htmlspecialchars($advisorCity) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($advisorPhone): ?>
                <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $advisorPhone)) ?>" class="tp-btn-gold" style="margin-top:20px;width:100%;justify-content:center">
                    <?= htmlspecialchars($advisorPhone) ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     PARCOURS — Expérience, formations, certifications
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-light" aria-label="Parcours">
    <div class="tp-container">
        <span class="tp-section-badge" style="display:block;text-align:center">Parcours professionnel</span>
        <h2 class="tp-section-title" <?= $editMode ? 'data-field="parcours_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($parcoursTitle) ?>
        </h2>
        <div class="ap-parcours-grid">
            <div class="ap-parcours-card">
                <div class="ap-parcours-icon" <?= $editMode ? 'data-field="parcours1_icon" class="ef-zone"' : '' ?>><?= $parcours1Icon ?></div>
                <h3 class="ap-parcours-title" <?= $editMode ? 'data-field="parcours1_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($parcours1Title) ?></h3>
                <p class="ap-parcours-text" <?= $editMode ? 'data-field="parcours1_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($parcours1Text) ?></p>
            </div>
            <div class="ap-parcours-card">
                <div class="ap-parcours-icon" <?= $editMode ? 'data-field="parcours2_icon" class="ef-zone"' : '' ?>><?= $parcours2Icon ?></div>
                <h3 class="ap-parcours-title" <?= $editMode ? 'data-field="parcours2_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($parcours2Title) ?></h3>
                <p class="ap-parcours-text" <?= $editMode ? 'data-field="parcours2_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($parcours2Text) ?></p>
            </div>
            <div class="ap-parcours-card">
                <div class="ap-parcours-icon" <?= $editMode ? 'data-field="parcours3_icon" class="ef-zone"' : '' ?>><?= $parcours3Icon ?></div>
                <h3 class="ap-parcours-title" <?= $editMode ? 'data-field="parcours3_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($parcours3Title) ?></h3>
                <p class="ap-parcours-text" <?= $editMode ? 'data-field="parcours3_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($parcours3Text) ?></p>
            </div>
            <div class="ap-parcours-card">
                <div class="ap-parcours-icon" <?= $editMode ? 'data-field="parcours4_icon" class="ef-zone"' : '' ?>><?= $parcours4Icon ?></div>
                <h3 class="ap-parcours-title" <?= $editMode ? 'data-field="parcours4_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($parcours4Title) ?></h3>
                <p class="ap-parcours-text" <?= $editMode ? 'data-field="parcours4_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($parcours4Text) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     VALEURS — Philosophie d'accompagnement
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-white" aria-label="Valeurs">
    <div class="tp-container">
        <span class="tp-section-badge" style="display:block;text-align:center">Ma philosophie</span>
        <h2 class="tp-section-title" <?= $editMode ? 'data-field="valeurs_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($valeursTitle) ?>
        </h2>
        <p style="text-align:center;color:var(--tp-text2);max-width:600px;margin:-28px auto 48px;font-size:.95rem;line-height:1.7" <?= $editMode ? 'data-field="valeurs_sub" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($valeursSub) ?>
        </p>
        <div class="ap-valeurs-grid">
            <div class="ap-valeur-card">
                <div class="ap-valeur-icon" <?= $editMode ? 'data-field="valeur1_icon" class="ef-zone"' : '' ?>><?= $valeur1Icon ?></div>
                <h3 class="ap-valeur-title" <?= $editMode ? 'data-field="valeur1_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($valeur1Title) ?></h3>
                <p class="ap-valeur-text" <?= $editMode ? 'data-field="valeur1_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($valeur1Text) ?></p>
            </div>
            <div class="ap-valeur-card">
                <div class="ap-valeur-icon" <?= $editMode ? 'data-field="valeur2_icon" class="ef-zone"' : '' ?>><?= $valeur2Icon ?></div>
                <h3 class="ap-valeur-title" <?= $editMode ? 'data-field="valeur2_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($valeur2Title) ?></h3>
                <p class="ap-valeur-text" <?= $editMode ? 'data-field="valeur2_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($valeur2Text) ?></p>
            </div>
            <div class="ap-valeur-card">
                <div class="ap-valeur-icon" <?= $editMode ? 'data-field="valeur3_icon" class="ef-zone"' : '' ?>><?= $valeur3Icon ?></div>
                <h3 class="ap-valeur-title" <?= $editMode ? 'data-field="valeur3_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($valeur3Title) ?></h3>
                <p class="ap-valeur-text" <?= $editMode ? 'data-field="valeur3_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($valeur3Text) ?></p>
            </div>
            <div class="ap-valeur-card">
                <div class="ap-valeur-icon" <?= $editMode ? 'data-field="valeur4_icon" class="ef-zone"' : '' ?>><?= $valeur4Icon ?></div>
                <h3 class="ap-valeur-title" <?= $editMode ? 'data-field="valeur4_title" class="ef-zone"' : '' ?>><?= htmlspecialchars($valeur4Title) ?></h3>
                <p class="ap-valeur-text" <?= $editMode ? 'data-field="valeur4_text" class="ef-zone"' : '' ?>><?= htmlspecialchars($valeur4Text) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     RÉSEAU — eXp France
     ═══════════════════════════════════════════════════ -->
<section class="tp-section-light" aria-label="Réseau">
    <div class="tp-container">
        <div class="ap-reseau-grid">
            <div class="ap-reseau-badge-col">
                <div class="ap-reseau-badge">
                    <div class="ap-reseau-logo"><?= mb_substr($advisorNetwork, 0, 3) ?></div>
                    <h3><?= htmlspecialchars($advisorNetwork) ?></h3>
                    <p>Réseau immobilier international</p>
                </div>
                <div class="tp-tags-row" style="justify-content:center;margin-top:24px">
                    <span class="tp-tag-chip" <?= $editMode ? 'data-field="reseau_tag1" class="ef-zone"' : '' ?>><?= htmlspecialchars($reseauTag1) ?></span>
                    <span class="tp-tag-chip" <?= $editMode ? 'data-field="reseau_tag2" class="ef-zone"' : '' ?>><?= htmlspecialchars($reseauTag2) ?></span>
                    <span class="tp-tag-chip" <?= $editMode ? 'data-field="reseau_tag3" class="ef-zone"' : '' ?>><?= htmlspecialchars($reseauTag3) ?></span>
                    <span class="tp-tag-chip" <?= $editMode ? 'data-field="reseau_tag4" class="ef-zone"' : '' ?>><?= htmlspecialchars($reseauTag4) ?></span>
                </div>
            </div>
            <div>
                <span class="tp-section-badge">Mon réseau</span>
                <h2 style="font-family:var(--tp-ff-display);font-size:clamp(1.5rem,3vw,2.2rem);font-weight:800;color:var(--tp-primary);margin:0 0 24px;letter-spacing:-.02em" <?= $editMode ? 'data-field="reseau_title" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($reseauTitle) ?>
                </h2>
                <div class="tp-rich-body" <?= $editMode ? 'data-field="reseau_text" class="ef-zone ef-rich"' : '' ?>>
                    <?= $reseauText ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════
     CTA FINALE
     ═══════════════════════════════════════════════════ -->
<section class="tp-cta-section" aria-label="Appel à l'action">
    <div class="tp-container" style="position:relative">
        <h2 class="tp-cta-title" <?= $editMode ? 'data-field="cta_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($ctaTitle) ?>
        </h2>
        <p class="tp-cta-text" <?= $editMode ? 'data-field="cta_text" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($ctaText) ?>
        </p>
        <div style="display:flex;gap:14px;flex-wrap:wrap;justify-content:center">
            <a href="<?= htmlspecialchars($ctaBtnUrl) ?>" class="tp-cta-btn" <?= $editMode ? 'data-field="cta_btn_text" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($ctaBtnText) ?>
            </a>
            <a href="<?= htmlspecialchars($ctaEstUrl) ?>" class="tp-cta-btn-outline" <?= $editMode ? 'data-field="cta_est_text" class="ef-zone"' : '' ?>>
                <?= htmlspecialchars($ctaEstText) ?>
            </a>
        </div>
        <?php if ($advisorPhone): ?>
        <p style="color:rgba(255,255,255,.6);font-size:.85rem;margin-top:20px;position:relative">
            Ou appelez-moi directement :
            <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $advisorPhone)) ?>" style="color:var(--tp-white);text-decoration:none;font-weight:700">
                <?= htmlspecialchars($advisorPhone) ?>
            </a>
        </p>
        <?php endif; ?>
    </div>
</section>

<!-- Schema.org -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "RealEstateAgent",
    "name": <?= json_encode($advisorName, JSON_UNESCAPED_UNICODE) ?>,
    "description": <?= json_encode(strip_tags($bioText), JSON_UNESCAPED_UNICODE) ?>,
    "areaServed": {
        "@type": "City",
        "name": <?= json_encode($advisorCity, JSON_UNESCAPED_UNICODE) ?>
    }<?php if ($advisorNetwork): ?>,
    "memberOf": {
        "@type": "Organization",
        "name": <?= json_encode($advisorNetwork, JSON_UNESCAPED_UNICODE) ?>
    }<?php endif; ?><?php if ($advisorPhone): ?>,
    "telephone": <?= json_encode($advisorPhone, JSON_UNESCAPED_UNICODE) ?><?php endif; ?><?php if ($bioPhoto): ?>,
    "image": <?= json_encode($bioPhoto, JSON_UNESCAPED_UNICODE) ?><?php endif; ?>
}
</script>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>
