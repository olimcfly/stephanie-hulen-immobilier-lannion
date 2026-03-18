<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE MÉTHODE ANCRE — Page de présentation
 * /admin/modules/strategy/ancre/index.php
 *
 * Accès : dashboard.php?page=ancre
 * Dépend : ROOT_PATH, INSTANCE_ID, ADMIN_URL — config.php
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);

if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

// ── Définition des 5 piliers ANCRE ────────────────────────────
// Alignés 1:1 avec les parcours Launchpad A→E
// Chaque action a une page de destination directe
$piliers = [
    'A' => [
        'lettre'      => 'A',
        'mot'         => 'Ancrage',
        'sous_titre'  => 'local',
        'emoji'       => '📍',
        'color_var'   => '--ancre-a',
        'launchpad'   => 'Conquête Vendeurs',
        'page'        => 'neuropersona',
        'description' => 'Définissez votre territoire, identifiez vos personas prioritaires et positionnez-vous comme LA référence locale incontournable.',
        'actions'     => [
            ['texte' => 'Cartographier vos 3 personas vendeurs prioritaires', 'page' => 'neuropersona', 'icon' => 'fa-brain'],
            ['texte' => 'Optimiser votre fiche Google My Business', 'page' => 'gmb', 'icon' => 'fa-map-marker-alt'],
            ['texte' => 'Définir votre zone de chalandise exclusive', 'page' => 'local-seo', 'icon' => 'fa-map'],
            ['texte' => 'Rédiger votre promesse de positionnement local', 'page' => 'strategy-module', 'icon' => 'fa-pen-fancy'],
        ],
        'outils'      => [
            ['nom' => 'NeuroPersona', 'page' => 'neuropersona', 'icon' => 'fa-brain'],
            ['nom' => 'GMB Contacts', 'page' => 'gmb', 'icon' => 'fa-google'],
            ['nom' => 'Local SEO', 'page' => 'local-seo', 'icon' => 'fa-map'],
        ],
    ],
    'N' => [
        'lettre'      => 'N',
        'mot'         => 'Notoriété',
        'sous_titre'  => 'digitale',
        'emoji'       => '📣',
        'color_var'   => '--ancre-n',
        'launchpad'   => 'Acheteurs Solvables',
        'page'        => 'local-seo',
        'description' => 'Construisez votre visibilité organique par le SEO local, le contenu de référence et les réseaux sociaux pour attirer acheteurs et vendeurs.',
        'actions'     => [
            ['texte' => 'Publier 2 articles SEO par mois sur votre zone', 'page' => 'articles', 'icon' => 'fa-newspaper'],
            ['texte' => 'Créer une page par quartier stratégique', 'page' => 'pages', 'icon' => 'fa-file-alt'],
            ['texte' => 'Mettre en place un calendrier réseaux sociaux', 'page' => 'reseaux-sociaux', 'icon' => 'fa-calendar-alt'],
            ['texte' => 'Obtenir 20+ avis Google vérifiés', 'page' => 'local-seo', 'icon' => 'fa-star'],
        ],
        'outils'      => [
            ['nom' => 'Articles SEO', 'page' => 'articles', 'icon' => 'fa-pen'],
            ['nom' => 'Pages CMS', 'page' => 'pages', 'icon' => 'fa-layer-group'],
            ['nom' => 'Réseaux Sociaux', 'page' => 'reseaux-sociaux', 'icon' => 'fa-share-alt'],
        ],
    ],
    'C' => [
        'lettre'      => 'C',
        'mot'         => 'Conversion',
        'sous_titre'  => 'optimisée',
        'emoji'       => '🎯',
        'color_var'   => '--ancre-c',
        'launchpad'   => 'Conversion & Copy',
        'page'        => 'pages-capture',
        'description' => 'Transformez vos visiteurs en leads qualifiés grâce au copywriting MÈRE, aux pages de capture et aux offres irrésistibles.',
        'actions'     => [
            ['texte' => 'Refondre votre page d\'estimation avec la méthode MÈRE', 'page' => 'builder', 'icon' => 'fa-wand-magic-sparkles'],
            ['texte' => 'Créer 3 pages de capture thématiques', 'page' => 'pages-capture', 'icon' => 'fa-magnet'],
            ['texte' => 'Ajouter des CTA contextuels sur chaque page', 'page' => 'builder', 'icon' => 'fa-cursor-pointer'],
            ['texte' => 'Intégrer vos témoignages avec photo et prénom', 'page' => 'pages', 'icon' => 'fa-quote-left'],
        ],
        'outils'      => [
            ['nom' => 'Builder Pro', 'page' => 'builder', 'icon' => 'fa-drafting-compass'],
            ['nom' => 'Pages de Capture', 'page' => 'pages-capture', 'icon' => 'fa-mortar-pestle'],
            ['nom' => 'Copy MÈRE', 'page' => 'strategy-module', 'icon' => 'fa-lightbulb'],
        ],
    ],
    'R' => [
        'lettre'      => 'R',
        'mot'         => 'Relation',
        'sous_titre'  => 'durable',
        'emoji'       => '🤝',
        'color_var'   => '--ancre-r',
        'launchpad'   => 'Organisation & Système',
        'page'        => 'crm',
        'description' => 'Nurturez vos prospects dans la durée via un CRM structuré, des séquences email automatisées et un scoring intelligent.',
        'actions'     => [
            ['texte' => 'Configurer votre pipeline CRM en 5 étapes', 'page' => 'crm', 'icon' => 'fa-funnel'],
            ['texte' => 'Créer 3 séquences email de nurturing', 'page' => 'sequences', 'icon' => 'fa-envelope-open-text'],
            ['texte' => 'Mettre en place le scoring prospects', 'page' => 'scoring', 'icon' => 'fa-chart-line'],
            ['texte' => 'Automatiser les relances à J+3, J+7, J+14', 'page' => 'sequences', 'icon' => 'fa-robot'],
        ],
        'outils'      => [
            ['nom' => 'CRM Pipeline', 'page' => 'crm', 'icon' => 'fa-tasks'],
            ['nom' => 'Séquences Email', 'page' => 'sequences', 'icon' => 'fa-envelope'],
            ['nom' => 'Scoring Leads', 'page' => 'scoring', 'icon' => 'fa-medal'],
        ],
    ],
    'E' => [
        'lettre'      => 'E',
        'mot'         => 'Expansion',
        'sous_titre'  => 'continue',
        'emoji'       => '🚀',
        'color_var'   => '--ancre-e',
        'launchpad'   => 'Scale & Domination',
        'page'        => 'ads-launch',
        'description' => 'Scalez votre acquisition via la publicité payante, le retargeting et les partenariats pour dominer durablement votre marché local.',
        'actions'     => [
            ['texte' => 'Lancer une campagne Facebook Ads vendeurs', 'page' => 'ads-launch', 'icon' => 'fa-facebook'],
            ['texte' => 'Installer le pixel de retargeting', 'page' => 'ads-launch', 'icon' => 'fa-crosshairs'],
            ['texte' => 'Créer 3 audiences personnalisées', 'page' => 'ads-launch', 'icon' => 'fa-users'],
            ['texte' => 'Mettre en place un programme de partenaires', 'page' => 'websites', 'icon' => 'fa-handshake'],
        ],
        'outils'      => [
            ['nom' => 'Ads Launch', 'page' => 'ads-launch', 'icon' => 'fa-ad'],
            ['nom' => 'Google Ads', 'page' => 'ads-launch', 'icon' => 'fa-google'],
            ['nom' => 'Partenaires', 'page' => 'websites', 'icon' => 'fa-network-wired'],
        ],
    ],
];

// ── Récupérer le diagnostic Launchpad si disponible ───────────
$diagnostic     = null;
$parcours_actif = null;
$lettre_map     = ['A' => 'A', 'B' => 'N', 'C' => 'C', 'D' => 'R', 'E' => 'E'];

try {
    $db   = getDB();
    $stmt = $db->prepare("
        SELECT parcours_principal, scores, completed_at
        FROM   launchpad_diagnostic
        WHERE  instance_id = :iid AND user_id = :uid
          AND  completed_at IS NOT NULL
        ORDER  BY completed_at DESC LIMIT 1
    ");
    $stmt->execute([':iid' => INSTANCE_ID, ':uid' => (int)($_SESSION['admin_id'] ?? 0)]);
    $diagnostic = $stmt->fetch() ?: null;

    if ($diagnostic) {
        $lp_lettre    = $diagnostic['parcours_principal'] ?? 'A';
        $parcours_actif = $lettre_map[$lp_lettre] ?? 'A';
    }
} catch (Exception $e) {
    // Diagnostic absent ou table inexistante — affichage normal
}
?>

<style>
/* ════════════════════════════════════════════════════════════
   MÉTHODE ANCRE — Page de présentation
   Aesthetic : editorial premium, navy profond + or, sobre et fort
   ════════════════════════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,400;0,700;0,900;1,400&family=DM+Sans:wght@300;400;500;700&display=swap');

.ancre-wrap {
    /* Palette ANCRE */
    --ancre-a:       #ef4444;
    --ancre-n:       #10b981;
    --ancre-c:       #f59e0b;
    --ancre-r:       #6366f1;
    --ancre-e:       #8b5cf6;
    --ancre-gold:    #c9913b;
    --ancre-navy:    #0f172a;
    --ancre-navy-2:  #1e293b;

    /* Variables dashboard */
    --ancre-surface:  var(--surface,   #fff);
    --ancre-surface2: var(--surface-2, #f9fafb);
    --ancre-border:   var(--border,    #e5e7eb);
    --ancre-radius:   var(--radius-lg, 12px);
    --ancre-shadow:   var(--shadow-sm, 0 1px 3px rgba(0,0,0,.08));
    --ancre-text:     var(--text,      #111827);
    --ancre-text2:    var(--text-2,    #6b7280);
    --ancre-text3:    var(--text-3,    #9ca3af);

    font-family: 'DM Sans', sans-serif;
    max-width: 1100px;
    margin: 0 auto;
}

/* ── Hero ─────────────────────────────────────────────────── */
.ancre-hero {
    background: var(--ancre-navy);
    border-radius: var(--ancre-radius);
    padding: 52px 48px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
}

.ancre-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
    opacity: .4; pointer-events: none;
}
.ancre-hero::after {
    content: '';
    position: absolute; bottom: -80px; right: -80px;
    width: 320px; height: 320px;
    background: radial-gradient(circle, rgba(201,145,59,.18), transparent 70%);
    border-radius: 50%; pointer-events: none;
}

.ancre-hero-inner {
    position: relative; z-index: 1;
    display: flex; align-items: flex-start;
    justify-content: space-between; gap: 32px; flex-wrap: wrap;
}

.ancre-eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(201,145,59,.15);
    border: 1px solid rgba(201,145,59,.3);
    color: var(--ancre-gold);
    padding: 5px 14px; border-radius: 20px;
    font-size: .7rem; font-weight: 700;
    letter-spacing: .1em; text-transform: uppercase;
    margin-bottom: 16px;
}

.ancre-hero-title {
    font-family: 'Fraunces', Georgia, serif;
    font-size: clamp(2rem, 4vw, 3rem);
    font-weight: 900;
    color: #fff;
    line-height: 1.1;
    margin: 0 0 8px;
    letter-spacing: -.02em;
}
.ancre-hero-title span {
    color: var(--ancre-gold);
    font-style: italic;
}

.ancre-hero-promise {
    font-size: .95rem;
    color: rgba(255,255,255,.65);
    line-height: 1.6;
    max-width: 480px;
    margin: 0 0 24px;
}

.ancre-hero-btns { display: flex; gap: 10px; flex-wrap: wrap; }

.ancre-btn-primary {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, var(--ancre-gold), #a0722a);
    color: #fff; border-radius: var(--ancre-radius);
    font-size: .83rem; font-weight: 700;
    text-decoration: none; border: none; cursor: pointer;
    transition: transform .2s, box-shadow .2s;
}
.ancre-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(201,145,59,.35);
    color: #fff;
}

.ancre-btn-ghost {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 24px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.15);
    color: rgba(255,255,255,.8); border-radius: var(--ancre-radius);
    font-size: .83rem; font-weight: 600;
    text-decoration: none; cursor: pointer;
    transition: background .2s, color .2s;
}
.ancre-btn-ghost:hover {
    background: rgba(255,255,255,.12);
    color: #fff;
}

.ancre-hero-diag {
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: var(--ancre-radius);
    padding: 20px 22px;
    min-width: 220px; flex-shrink: 0;
    position: relative; z-index: 1;
}
.ancre-hero-diag-label {
    font-size: .65rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .1em;
    color: rgba(255,255,255,.4); margin-bottom: 10px;
}
.ancre-hero-diag-pill {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px;
    background: rgba(255,255,255,.07);
    border-radius: 9px; margin-bottom: 6px;
}
.ancre-hero-diag-pill-emoji { font-size: 1.4rem; }
.ancre-hero-diag-pill-txt { font-size: .78rem; font-weight: 700; color: #fff; }
.ancre-hero-diag-pill-sub { font-size: .65rem; color: rgba(255,255,255,.5); }
.ancre-hero-diag-cta {
    display: block; text-align: center;
    font-size: .72rem; color: var(--ancre-gold);
    text-decoration: underline; cursor: pointer;
    background: none; border: none; width: 100%;
    margin-top: 8px;
}

/* ── Acronyme ANCRE ───────────────────────────────────────── */
.ancre-acronyme {
    display: flex; gap: 6px;
    justify-content: center;
    margin-bottom: 28px;
    flex-wrap: wrap;
}
.ancre-letter-card {
    background: var(--ancre-surface);
    border: 1px solid var(--ancre-border);
    border-radius: 10px;
    padding: 14px 18px;
    text-align: center;
    flex: 1; min-width: 80px; max-width: 140px;
    box-shadow: var(--ancre-shadow);
    transition: transform .2s, border-color .15s, box-shadow .2s;
    cursor: default;
}
.ancre-letter-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,.1);
}
.ancre-letter-card.is-active {
    border-width: 2px;
}
.ancre-letter-big {
    font-family: 'Fraunces', Georgia, serif;
    font-size: 2.2rem; font-weight: 900;
    line-height: 1; margin-bottom: 4px;
}
.ancre-letter-mot {
    font-size: .75rem; font-weight: 700;
    color: var(--ancre-text); line-height: 1.2;
}
.ancre-letter-sub {
    font-size: .62rem; color: var(--ancre-text3);
    font-style: italic;
}

/* ── Section titre ────────────────────────────────────────── */
.ancre-sec-hd {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 16px;
}
.ancre-sec-hd h2 {
    font-family: 'Fraunces', Georgia, serif;
    font-size: 1.15rem; font-weight: 700;
    color: var(--ancre-text); margin: 0;
}
.ancre-sec-hd p {
    font-size: .8rem; color: var(--ancre-text2);
    margin: 0 0 0 auto;
}

/* ── Piliers — accordéon vertical ─────────────────────────── */
.ancre-piliers { display: flex; flex-direction: column; gap: 10px; margin-bottom: 28px; }

.ancre-pilier {
    background: var(--ancre-surface);
    border: 1px solid var(--ancre-border);
    border-radius: var(--ancre-radius);
    overflow: hidden;
    box-shadow: var(--ancre-shadow);
    transition: border-color .15s, box-shadow .15s;
}
.ancre-pilier.is-open {
    box-shadow: 0 6px 24px rgba(0,0,0,.1);
}
.ancre-pilier-hd {
    display: flex; align-items: center; gap: 16px;
    padding: 18px 22px; cursor: pointer;
    user-select: none;
    transition: background .15s;
}
.ancre-pilier-hd:hover { background: var(--ancre-surface2); }

.ancre-pilier-letter-wrap {
    width: 48px; height: 48px; border-radius: 11px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.ancre-pilier-letter {
    font-family: 'Fraunces', Georgia, serif;
    font-size: 1.5rem; font-weight: 900; color: #fff;
    line-height: 1;
}

.ancre-pilier-meta { flex: 1; min-width: 0; }
.ancre-pilier-nom {
    font-size: .95rem; font-weight: 800;
    color: var(--ancre-text); margin-bottom: 2px;
    display: flex; align-items: center; gap: 8px;
}
.ancre-pilier-lp {
    font-size: .65rem; font-weight: 600;
    padding: 2px 8px; border-radius: 20px;
    background: var(--ancre-surface2);
    color: var(--ancre-text3);
    border: 1px solid var(--ancre-border);
}
.ancre-pilier-desc-short {
    font-size: .78rem; color: var(--ancre-text2);
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 480px;
}

.ancre-pilier-right {
    display: flex; align-items: center; gap: 10px; flex-shrink: 0;
}
.ancre-pilier-outils {
    display: flex; gap: 4px; flex-wrap: wrap; justify-content: flex-end;
}
.ancre-outil-tag {
    padding: 3px 9px;
    background: var(--ancre-surface2);
    border: 1px solid var(--ancre-border);
    border-radius: 20px;
    font-size: .62rem; font-weight: 600; color: var(--ancre-text3);
}
.ancre-pilier-chevron {
    color: var(--ancre-text3); font-size: .75rem;
    transition: transform .25s;
}
.ancre-pilier.is-open .ancre-pilier-chevron { transform: rotate(180deg); }

/* Contenu accordéon */
.ancre-pilier-body {
    max-height: 0; overflow: hidden;
    transition: max-height .35s ease;
}
.ancre-pilier.is-open .ancre-pilier-body { max-height: 800px; }
.ancre-pilier-body-inner {
    padding: 0 22px 22px;
    border-top: 1px solid var(--ancre-border);
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 16px; padding-top: 18px;
}

.ancre-pilier-desc-full {
    font-size: .83rem; color: var(--ancre-text2);
    line-height: 1.65; margin-bottom: 14px;
    grid-column: 1 / -1;
}

/* Actions à faire */
.ancre-actions-title {
    font-size: .72rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .07em;
    color: var(--ancre-text3); margin-bottom: 10px;
    display: flex; align-items: center; gap: 6px;
}
.ancre-action-item {
    display: flex; align-items: flex-start; gap: 9px;
    padding: 9px 0;
    border-bottom: 1px solid var(--ancre-border);
}
.ancre-action-item:last-child { border: none; }
.ancre-action-link {
    flex: 1;
    font-size: .8rem;
    color: var(--ancre-text);
    text-decoration: none;
    cursor: pointer;
    display: flex; align-items: center; gap: 8px;
    transition: color .15s;
}
.ancre-action-link:hover { color: var(--ancre-gold); font-weight: 600; }
.ancre-action-num {
    width: 20px; height: 20px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .6rem; font-weight: 800; color: #fff;
    flex-shrink: 0; margin-top: 1px;
}

/* Lien module */
.ancre-module-link {
    grid-column: 1 / -1;
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 18px;
    border-radius: var(--ancre-radius);
    font-size: .78rem; font-weight: 700;
    text-decoration: none; color: #fff;
    transition: transform .15s, box-shadow .15s;
    align-self: start; width: fit-content;
    margin-top: 4px;
}
.ancre-module-link:hover {
    transform: translateX(4px);
    color: #fff;
}

/* Outil cliquable */
.ancre-outil-item {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 0;
    border-bottom: 1px solid var(--ancre-border);
    text-decoration: none; color: var(--ancre-text);
    transition: color .15s;
}
.ancre-outil-item:last-child { border: none; }
.ancre-outil-item:hover { color: var(--ancre-gold); font-weight: 600; }
.ancre-outil-icon {
    width: 20px; height: 20px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .6rem; color: #fff;
    flex-shrink: 0;
}

/* ── Bande « comment ça marche » ──────────────────────────── */
.ancre-flow {
    background: var(--ancre-navy);
    border-radius: var(--ancre-radius);
    padding: 32px 36px;
    margin-bottom: 28px;
    position: relative; overflow: hidden;
}
.ancre-flow::after {
    content: '';
    position: absolute; top: -60px; left: -60px;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(201,145,59,.12), transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.ancre-flow-title {
    font-family: 'Fraunces', Georgia, serif;
    font-size: 1.1rem; font-weight: 700;
    color: #fff; margin: 0 0 24px;
    position: relative; z-index: 1;
    display: flex; align-items: center; gap: 10px;
}
.ancre-flow-steps {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0;
    position: relative; z-index: 1;
}
.ancre-flow-step { text-align: center; position: relative; }
.ancre-flow-step:not(:last-child)::after {
    content: '→';
    position: absolute; top: 20px; right: -10px;
    color: rgba(255,255,255,.2);
    font-size: 1rem; z-index: 2;
}
.ancre-flow-step-icon {
    width: 44px; height: 44px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; margin: 0 auto 10px;
    border: 2px solid rgba(255,255,255,.1);
}
.ancre-flow-step-letter {
    font-family: 'Fraunces', Georgia, serif;
    font-size: .72rem; font-weight: 900;
    letter-spacing: .08em; text-transform: uppercase;
    margin-bottom: 4px;
}
.ancre-flow-step-label {
    font-size: .7rem; color: rgba(255,255,255,.55); line-height: 1.3;
}

/* ── Bloc promesse ────────────────────────────────────────── */
.ancre-promesse {
    background: linear-gradient(135deg,
        rgba(201,145,59,.08),
        rgba(201,145,59,.03)
    );
    border: 1px solid rgba(201,145,59,.25);
    border-radius: var(--ancre-radius);
    padding: 28px 32px;
    display: flex; align-items: center; gap: 24px;
    flex-wrap: wrap;
}
.ancre-promesse-quote {
    font-family: 'Fraunces', Georgia, serif;
    font-size: 1.4rem; font-weight: 700;
    font-style: italic;
    color: var(--ancre-text);
    line-height: 1.4; flex: 1;
}
.ancre-promesse-quote strong { color: var(--ancre-gold); font-style: normal; }
.ancre-promesse-cta {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 13px 24px;
    background: linear-gradient(135deg, var(--ancre-gold), #a0722a);
    color: #fff; border-radius: var(--ancre-radius);
    font-size: .83rem; font-weight: 700;
    text-decoration: none; flex-shrink: 0;
    transition: transform .2s, box-shadow .2s;
}
.ancre-promesse-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(201,145,59,.35);
    color: #fff;
}

/* ── Responsive ───────────────────────────────────────────── */
@media (max-width: 900px) {
    .ancre-hero { padding: 32px 24px; }
    .ancre-flow-steps { grid-template-columns: 1fr 1fr; gap: 16px; }
    .ancre-flow-step:not(:last-child)::after { display: none; }
    .ancre-pilier-body-inner { grid-template-columns: 1fr; }
    .ancre-pilier-desc-short { display: none; }
    .ancre-acronyme { gap: 4px; }
}
@media (max-width: 600px) {
    .ancre-acronyme { display: grid; grid-template-columns: repeat(3, 1fr); }
    .ancre-hero-diag { display: none; }
    .ancre-promesse-quote { font-size: 1.1rem; }
    .ancre-flow-steps { grid-template-columns: 1fr; }
}
</style>

<div class="ancre-wrap">

    <!-- ── Hero ──────────────────────────────────────────── -->
    <div class="ancre-hero anim">
        <div class="ancre-hero-inner">
            <div>
                <div class="ancre-eyebrow">
                    <i class="fas fa-anchor"></i> Méthode officielle
                </div>
                <h1 class="ancre-hero-title">
                    La Méthode<br>
                    <span>ANCRE</span>
                </h1>
                <p class="ancre-hero-promise">
                    Un système complet en 5 piliers pour attirer vendeurs et acheteurs localement,
                    sans dépendre des portails immobiliers.
                </p>
                <div class="ancre-hero-btns">
                    <a href="<?= htmlspecialchars(ADMIN_URL) ?>/dashboard.php?page=launchpad"
                       class="ancre-btn-primary">
                        <i class="fas fa-compass"></i> Mon diagnostic ANCRE
                    </a>
                    <a href="<?= htmlspecialchars(ADMIN_URL) ?>/dashboard.php?page=strategy-module"
                       class="ancre-btn-ghost">
                        <i class="fas fa-map"></i> Vue d'ensemble
                    </a>
                </div>
            </div>

            <!-- Bloc diagnostic -->
            <div class="ancre-hero-diag">
                <div class="ancre-hero-diag-label">Votre profil actuel</div>
                <?php if ($diagnostic && $parcours_actif && isset($piliers[$parcours_actif])): ?>
                <?php $p_actif = $piliers[$parcours_actif]; ?>
                <div class="ancre-hero-diag-pill">
                    <span class="ancre-hero-diag-pill-emoji"><?= $p_actif['emoji'] ?></span>
                    <div>
                        <div class="ancre-hero-diag-pill-txt">
                            Pilier <?= htmlspecialchars($parcours_actif) ?> — <?= htmlspecialchars($p_actif['mot']) ?>
                        </div>
                        <div class="ancre-hero-diag-pill-sub">Priorité recommandée</div>
                    </div>
                </div>
                <button class="ancre-hero-diag-cta"
                        onclick="location.href='<?= htmlspecialchars(ADMIN_URL) ?>/dashboard.php?page=launchpad&action=results'">
                    Voir mes résultats →
                </button>
                <?php else: ?>
                <div class="ancre-hero-diag-pill">
                    <span class="ancre-hero-diag-pill-emoji">🧭</span>
                    <div>
                        <div class="ancre-hero-diag-pill-txt">Diagnostic non fait</div>
                        <div class="ancre-hero-diag-pill-sub">2 min pour votre plan</div>
                    </div>
                </div>
                <button class="ancre-hero-diag-cta"
                        onclick="location.href='<?= htmlspecialchars(ADMIN_URL) ?>/dashboard.php?page=launchpad&action=diagnostic&q=1'">
                    Démarrer le diagnostic →
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Acronyme visuel ───────────────────────────────── -->
    <div class="ancre-acronyme anim">
        <?php foreach ($piliers as $lettre => $p): ?>
        <div class="ancre-letter-card <?= $parcours_actif === $lettre ? 'is-active' : '' ?>"
             style="<?= $parcours_actif === $lettre
                ? 'border-color:var(' . htmlspecialchars($p['color_var']) . ')'
                : '' ?>">
            <div class="ancre-letter-big"
                 style="color:var(<?= htmlspecialchars($p['color_var']) ?>)">
                <?= $lettre ?>
            </div>
            <div class="ancre-letter-mot"><?= htmlspecialchars($p['mot']) ?></div>
            <div class="ancre-letter-sub"><?= htmlspecialchars($p['sous_titre']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Flow visuel ───────────────────────────────────── -->
    <div class="ancre-flow anim">
        <div class="ancre-flow-title">
            <i class="fas fa-arrow-right-arrow-left" style="color:var(--ancre-gold)"></i>
            Du premier contact à la signature — le parcours ANCRE
        </div>
        <div class="ancre-flow-steps">
            <?php foreach ($piliers as $lettre => $p): ?>
            <div class="ancre-flow-step">
                <div class="ancre-flow-step-icon"
                     style="background:rgba(255,255,255,.05);border-color:var(<?= htmlspecialchars($p['color_var']) ?>)">
                    <?= $p['emoji'] ?>
                </div>
                <div class="ancre-flow-step-letter"
                     style="color:var(<?= htmlspecialchars($p['color_var']) ?>)">
                    <?= $lettre ?>
                </div>
                <div class="ancre-flow-step-label"><?= htmlspecialchars($p['mot']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ── Les 5 piliers — accordéon ────────────────────── -->
    <div class="ancre-sec-hd anim">
        <i class="fas fa-layer-group" style="color:var(--ancre-gold)"></i>
        <h2>Les 5 piliers de la méthode</h2>
        <p>Cliquez pour voir les actions et outils</p>
    </div>

    <div class="ancre-piliers anim">
        <?php foreach ($piliers as $lettre => $p):
            $is_open   = ($parcours_actif === $lettre);
            $color_val = 'var(' . $p['color_var'] . ')';
        ?>
        <div class="ancre-pilier <?= $is_open ? 'is-open' : '' ?>"
             id="pilier-<?= $lettre ?>">

            <!-- En-tête cliquable -->
            <div class="ancre-pilier-hd"
                 onclick="ancreToggle('<?= $lettre ?>')"
                 style="<?= $is_open ? 'border-left:3px solid ' . $color_val : '' ?>">

                <div class="ancre-pilier-letter-wrap"
                     style="background:linear-gradient(135deg, <?= $color_val ?>, <?= $color_val ?>88)">
                    <span class="ancre-pilier-letter"><?= $lettre ?></span>
                </div>

                <div class="ancre-pilier-meta">
                    <div class="ancre-pilier-nom">
                        <?= htmlspecialchars($p['mot']) ?>
                        <span style="font-size:.75rem;font-weight:400;
                                     color:var(--ancre-text2);font-style:italic">
                            <?= htmlspecialchars($p['sous_titre']) ?>
                        </span>
                        <span class="ancre-pilier-lp">
                            <?= htmlspecialchars($p['launchpad']) ?>
                        </span>
                        <?php if ($parcours_actif === $lettre): ?>
                        <span style="background:<?= $color_val ?>;color:#fff;
                                     padding:2px 8px;border-radius:20px;
                                     font-size:.58rem;font-weight:700">
                            Votre priorité
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="ancre-pilier-desc-short">
                        <?= htmlspecialchars($p['description']) ?>
                    </div>
                </div>

                <div class="ancre-pilier-right">
                    <div class="ancre-pilier-outils">
                        <?php foreach ($p['outils'] as $outil): ?>
                        <span class="ancre-outil-tag"><?= htmlspecialchars($outil['nom']) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <i class="fas fa-chevron-down ancre-pilier-chevron"></i>
                </div>
            </div>

            <!-- Corps accordéon -->
            <div class="ancre-pilier-body">
                <div class="ancre-pilier-body-inner">

                    <div class="ancre-pilier-desc-full">
                        <?= htmlspecialchars($p['description']) ?>
                    </div>

                    <!-- Actions cliquables -->
                    <div>
                        <div class="ancre-actions-title">
                            <i class="fas fa-list-check" style="color:<?= $color_val ?>"></i>
                            Actions clés
                        </div>
                        <?php foreach ($p['actions'] as $i => $action): ?>
                        <div class="ancre-action-item">
                            <span class="ancre-action-num"
                                  style="background:<?= $color_val ?>">
                                <i class="fas <?= htmlspecialchars($action['icon']) ?>" style="font-size:.55rem"></i>
                            </span>
                            <a href="<?= htmlspecialchars(ADMIN_URL) ?>/dashboard.php?page=<?= htmlspecialchars($action['page']) ?>"
                               class="ancre-action-link"
                               title="Aller à <?= htmlspecialchars($action['page']) ?>">
                                <?= htmlspecialchars($action['texte']) ?>
                                <i class="fas fa-arrow-right" style="font-size:.65rem;opacity:.5"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Outils cliquables -->
                    <div>
                        <div class="ancre-actions-title">
                            <i class="fas fa-toolbox" style="color:<?= $color_val ?>"></i>
                            Modules disponibles
                        </div>
                        <?php foreach ($p['outils'] as $outil): ?>
                        <a href="<?= htmlspecialchars(ADMIN_URL) ?>/dashboard.php?page=<?= htmlspecialchars($outil['page']) ?>"
                           class="ancre-outil-item"
                           title="Accéder à <?= htmlspecialchars($outil['nom']) ?>">
                            <span class="ancre-outil-icon" style="background:<?= $color_val ?>">
                                <i class="fas <?= htmlspecialchars($outil['icon']) ?>" style="font-size:.55rem"></i>
                            </span>
                            <span><?= htmlspecialchars($outil['nom']) ?></span>
                            <i class="fas fa-arrow-right" style="font-size:.65rem;opacity:.3;margin-left:auto"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Promesse finale ───────────────────────────────── -->
    <div class="ancre-promesse anim">
        <div class="ancre-promesse-quote">
            "Un système complet pour <strong>attirer vendeurs et acheteurs</strong>
            localement, sans dépendre des portails immobiliers."
        </div>
        <a href="<?= htmlspecialchars(ADMIN_URL) ?>/dashboard.php?page=launchpad"
           class="ancre-promesse-cta">
            <i class="fas fa-anchor"></i> Démarrer ma méthode ANCRE
        </a>
    </div>

</div><!-- /ancre-wrap -->

<script>
(function () {
    'use strict';

    // Accordéon piliers
    window.ancreToggle = function (lettre) {
        const el = document.getElementById('pilier-' + lettre);
        if (!el) return;
        const isOpen = el.classList.contains('is-open');

        // Fermer tous les autres
        document.querySelectorAll('.ancre-pilier.is-open').forEach(p => {
            p.classList.remove('is-open');
            p.querySelector('.ancre-pilier-hd').style.borderLeft = '';
        });

        if (!isOpen) {
            el.classList.add('is-open');
            const colorVar = el.querySelector('.ancre-pilier-letter-wrap')
                               .style.background.match(/var\([^)]+\)/)?.[0] || '';
            el.querySelector('.ancre-pilier-hd').style.borderLeft =
                '3px solid ' + colorVar;

            // Scroll doux vers le pilier ouvert
            setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 50);
        }
    };

    // Ouvrir automatiquement le pilier recommandé
    const recommended = <?= json_encode($parcours_actif) ?>;
    if (recommended) {
        setTimeout(() => {
            const el = document.getElementById('pilier-' + recommended);
            if (el && !el.classList.contains('is-open')) ancreToggle(recommended);
        }, 400);
    }
})();
</script>