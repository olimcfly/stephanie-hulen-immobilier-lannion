<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MÉTHODE ANCRE — Pilier A : Ancrage local
 * /admin/modules/strategy/ancre/pilier-a.php
 *
 * Accès : dashboard.php?page=ancre-a
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

$user_id     = (int)($_SESSION['admin_id'] ?? 0);
$instance_id = INSTANCE_ID;
$api_base    = ADMIN_URL . '/modules/system/api/strategy/ancre-progress.php';

// ── Définition des étapes du pilier A ─────────────────────────
$steps = [
    [
        'key'         => 'personas_map',
        'num'         => 1,
        'titre'       => 'Cartographier vos 3 personas vendeurs',
        'desc'        => 'Identifiez vos 3 profils de vendeurs prioritaires sur votre zone : motivations, freins, déclencheurs de décision. C\'est la fondation de toute votre communication.',
        'duree'       => '45 min',
        'difficulte'  => 'Moyen',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=neuropersona',
        'module_nom'  => 'Ouvrir NeuroPersona',
        'module_icon' => 'fa-brain',
        'ressources'  => ['Grille 8 personas vendeurs','Méthode FOTO','Tableau AIDA local'],
        'tips'        => 'Commencez par le persona que vous croisez le plus souvent dans vos mandats.',
    ],
    [
        'key'         => 'gmb_optimise',
        'num'         => 2,
        'titre'       => 'Optimiser votre fiche Google My Business',
        'desc'        => 'Votre GMB est votre vitrine locale #1. Photo de profil professionnelle, description avec mots-clés locaux, catégories exactes, horaires à jour.',
        'duree'       => '30 min',
        'difficulte'  => 'Facile',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=gmb',
        'module_nom'  => 'Ouvrir GMB',
        'module_icon' => 'fa-map-marker-alt',
        'ressources'  => ['Checklist GMB 40 points','Template description locale'],
        'tips'        => 'La description doit contenir votre ville + "conseiller immobilier" + votre spécialité.',
    ],
    [
        'key'         => 'zone_chalandise',
        'num'         => 3,
        'titre'       => 'Définir votre zone de chalandise exclusive',
        'desc'        => 'Délimitez précisément les quartiers/communes où vous vous positionnez comme référent. 3 à 5 secteurs max pour une présence forte et mémorable.',
        'duree'       => '20 min',
        'difficulte'  => 'Facile',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=local-seo',
        'module_nom'  => 'Voir SEO Local',
        'module_icon' => 'fa-map',
        'ressources'  => ['Matrice choix territoire','Analyse concurrence locale'],
        'tips'        => 'Choisissez des secteurs où vous avez déjà des références ou une affinité personnelle.',
    ],
    [
        'key'         => 'promesse_positionnement',
        'num'         => 4,
        'titre'       => 'Rédiger votre promesse de positionnement',
        'desc'        => 'Une phrase de positionnement claire : "Je suis LE conseiller [specialité] à [ville] pour [profil client]". Cette promesse doit apparaître sur tous vos supports.',
        'duree'       => '30 min',
        'difficulte'  => 'Moyen',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=strategy-module',
        'module_nom'  => 'Stratégie & Positionnement',
        'module_icon' => 'fa-bullseye',
        'ressources'  => ['Formule de positionnement','50 exemples de promesses'],
        'tips'        => 'Testez votre promesse : si quelqu\'un d\'autre peut dire la même chose, elle n\'est pas assez spécifique.',
    ],
    [
        'key'         => 'avis_google',
        'num'         => 5,
        'titre'       => 'Obtenir vos 20 premiers avis Google',
        'desc'        => 'Les avis sont le signal de confiance #1 pour les vendeurs. Créez un système simple : lien direct GMB + message type + timing post-transaction.',
        'duree'       => '1 semaine',
        'difficulte'  => 'Continu',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=gmb',
        'module_nom'  => 'Gérer les avis GMB',
        'module_icon' => 'fa-star',
        'ressources'  => ['Template demande d\'avis','Script de relance SMS'],
        'tips'        => 'Envoyez la demande 48h après la signature — c\'est quand l\'émotion positive est au pic.',
    ],
];

// ── Récupérer la progression en DB ────────────────────────────
$progress = [];
try {
    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT step_key, status, note FROM ancre_progress
         WHERE instance_id=:iid AND user_id=:uid AND pilier='A'"
    );
    $stmt->execute([':iid' => $instance_id, ':uid' => $user_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $progress[$row['step_key']] = $row;
    }
} catch (Exception $e) { /* table absente — première visite */ }

$done_count  = count(array_filter($progress, fn($r) => $r['status'] === 'done'));
$total_steps = count($steps);
$pct         = $total_steps > 0 ? round($done_count / $total_steps * 100) : 0;

// ── Composant coach ───────────────────────────────────────────
$coach_pilier = [
    'lettre'  => 'A',
    'mot'     => 'Ancrage local',
    'contexte'=> "Le pilier A — Ancrage local consiste à :
1. Cartographier les personas vendeurs (motivations, freins, déclencheurs)
2. Optimiser la fiche Google My Business (photos, description, catégories, horaires)
3. Définir une zone de chalandise exclusive (3-5 secteurs max)
4. Rédiger une promesse de positionnement unique et mémorable
5. Obtenir 20+ avis Google via un système de demande automatisé

L'objectif est de devenir LA référence locale incontournable sur son territoire, reconnue avant même le premier contact.",
    'suggestions' => [
        'Comment choisir mes 3 personas ?',
        'Optimiser mon GMB en 30 min',
        'Rédiger ma promesse de positionnement',
        'Script pour demander des avis',
    ],
];
?>

<style>
/* ════════════════════════════════════════════════════════════
   PILIER A — Ancrage local
   Couleur : --ancre-a = #ef4444 (rouge)
   ════════════════════════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,700;0,900;1,400&family=DM+Sans:wght@300;400;500;700&display=swap');

.pilier-wrap {
    --pc:        #ef4444;
    --pc-light:  #fef2f2;
    --pc-border: #fecaca;
    --pc-text:   #991b1b;

    --surface:   var(--surface,  #fff);
    --surface-2: var(--surface-2,#f9fafb);
    --border:    var(--border,   #e5e7eb);
    --radius:    var(--radius-lg,12px);
    --shadow:    var(--shadow-sm,0 1px 3px rgba(0,0,0,.08));
    --text:      var(--text,     #111827);
    --text-2:    var(--text-2,   #6b7280);
    --text-3:    var(--text-3,   #9ca3af);

    font-family: 'DM Sans', sans-serif;
    max-width: 900px; margin: 0 auto;
}

/* ── Fil d'Ariane ────────────────────────────────────────── */
.pilier-breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: .75rem; color: var(--text-3);
    margin-bottom: 20px;
}
.pilier-breadcrumb a {
    color: var(--text-2); text-decoration: none;
    transition: color .15s;
}
.pilier-breadcrumb a:hover { color: var(--pc); }
.pilier-breadcrumb .sep { opacity: .5; }

/* ── Hero pilier ─────────────────────────────────────────── */
.pilier-hero {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 28px 32px;
    margin-bottom: 20px;
    box-shadow: var(--shadow);
    display: flex; align-items: flex-start;
    gap: 24px; flex-wrap: wrap;
}
.pilier-hero-left { flex: 1; min-width: 240px; }
.pilier-letter-badge {
    display: inline-flex; align-items: center; justify-content: center;
    width: 52px; height: 52px; border-radius: 14px;
    background: var(--pc); color: #fff;
    font-family: 'Fraunces', Georgia, serif;
    font-size: 1.8rem; font-weight: 900;
    margin-bottom: 12px;
}
.pilier-hero-title {
    font-family: 'Fraunces', Georgia, serif;
    font-size: 1.5rem; font-weight: 900;
    color: var(--text); margin: 0 0 6px;
    line-height: 1.2;
}
.pilier-hero-sub {
    font-size: .85rem; color: var(--text-2);
    line-height: 1.6; max-width: 480px; margin: 0;
}

/* Progress tracker */
.pilier-progress-block { min-width: 200px; flex-shrink: 0; }
.pilier-progress-label {
    font-size: .7rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .08em;
    color: var(--text-3); margin-bottom: 8px;
}
.pilier-progress-bar-wrap {
    height: 8px; background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 20px; overflow: hidden; margin-bottom: 8px;
}
.pilier-progress-bar {
    height: 100%; background: var(--pc);
    border-radius: 20px;
    transition: width .6s ease;
}
.pilier-progress-stats {
    display: flex; justify-content: space-between;
    align-items: center;
}
.pilier-progress-pct {
    font-size: 1.4rem; font-weight: 800;
    color: var(--pc); font-family: 'Fraunces', Georgia, serif;
}
.pilier-progress-frac {
    font-size: .75rem; color: var(--text-3);
}

/* Nav piliers */
.pilier-nav {
    display: flex; gap: 6px; margin-bottom: 24px; flex-wrap: wrap;
}
.pilier-nav-item {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    font-size: .72rem; font-weight: 700;
    color: var(--text-2); text-decoration: none;
    transition: background .15s, border-color .15s, color .15s;
}
.pilier-nav-item:hover, .pilier-nav-item.current {
    background: var(--pc-light);
    border-color: var(--pc-border);
    color: var(--pc-text);
}
.pilier-nav-item.done-nav .pilier-nav-dot {
    background: #10b981;
}
.pilier-nav-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--border);
    transition: background .15s;
}
.pilier-nav-item.current .pilier-nav-dot { background: var(--pc); }

/* ── Steps ───────────────────────────────────────────────── */
.pilier-steps { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; }

.step-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: border-color .15s, box-shadow .15s;
}
.step-card.is-done {
    border-color: #bbf7d0;
    background: #f0fdf4;
}
.step-card.is-doing { border-color: #fde68a; }
.step-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }

.step-card-hd {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px; cursor: pointer;
    transition: background .15s;
}
.step-card-hd:hover { background: var(--surface-2); }
.step-card.is-done .step-card-hd:hover { background: #dcfce7; }

.step-num {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; font-weight: 800; color: #fff;
    background: var(--pc); flex-shrink: 0;
    transition: background .2s;
    font-family: 'Fraunces', Georgia, serif;
}
.step-card.is-done .step-num { background: #10b981; }

.step-meta { flex: 1; min-width: 0; }
.step-titre {
    font-size: .9rem; font-weight: 700;
    color: var(--text); margin-bottom: 3px;
    display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
}
.step-badge {
    padding: 2px 8px; border-radius: 20px;
    font-size: .6rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em;
    flex-shrink: 0;
}
.step-badge.easy   { background: #dcfce7; color: #166534; }
.step-badge.medium { background: #fef3c7; color: #92400e; }
.step-badge.continu{ background: #ede9fe; color: #5b21b6; }

.step-duree { font-size: .72rem; color: var(--text-3); }

.step-right {
    display: flex; align-items: center; gap: 8px; flex-shrink: 0;
}
.step-status-select {
    padding: 5px 10px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 20px;
    font-size: .7rem; font-weight: 600;
    color: var(--text-2); cursor: pointer;
    outline: none; appearance: none;
    -webkit-appearance: none;
    font-family: inherit;
    transition: border-color .15s;
}
.step-status-select:focus { border-color: var(--pc); }
.step-status-select.done   { background: #dcfce7; border-color: #86efac; color: #166534; }
.step-status-select.doing  { background: #fef3c7; border-color: #fde68a; color: #92400e; }

.step-chevron {
    color: var(--text-3); font-size: .7rem;
    transition: transform .25s;
}
.step-card.is-open .step-chevron { transform: rotate(180deg); }

/* Body accordéon */
.step-body {
    max-height: 0; overflow: hidden;
    transition: max-height .35s ease;
}
.step-card.is-open .step-body { max-height: 500px; }

.step-body-inner {
    padding: 0 20px 20px;
    border-top: 1px solid var(--border);
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 16px; padding-top: 16px;
}
.step-card.is-done .step-body-inner { border-color: #bbf7d0; }

.step-desc {
    font-size: .82rem; color: var(--text-2);
    line-height: 1.65; grid-column: 1 / -1;
}

.step-section-title {
    font-size: .68rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .08em;
    color: var(--text-3); margin-bottom: 8px;
    display: flex; align-items: center; gap: 5px;
}

.step-ressource-list {
    list-style: none; margin: 0; padding: 0;
}
.step-ressource-list li {
    font-size: .78rem; color: var(--text-2);
    padding: 5px 0;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 7px;
}
.step-ressource-list li:last-child { border: none; }
.step-ressource-list li::before {
    content: '';
    width: 6px; height: 6px; border-radius: 50%;
    background: var(--pc); flex-shrink: 0;
}

.step-tip {
    background: var(--pc-light);
    border: 1px solid var(--pc-border);
    border-radius: 8px;
    padding: 10px 14px;
    font-size: .78rem; color: var(--pc-text);
    line-height: 1.5;
    display: flex; align-items: flex-start; gap: 8px;
    grid-column: 1 / -1;
}
.step-tip i { color: var(--pc); flex-shrink: 0; margin-top: 2px; }

.step-module-btn {
    grid-column: 1 / -1;
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 18px;
    background: var(--pc); color: #fff;
    border-radius: var(--radius);
    font-size: .78rem; font-weight: 700;
    text-decoration: none; width: fit-content;
    transition: transform .15s, box-shadow .15s, opacity .15s;
}
.step-module-btn:hover {
    transform: translateX(3px);
    box-shadow: 0 4px 14px rgba(239,68,68,.35);
    color: #fff;
}

/* Note textarea */
.step-note-wrap {
    grid-column: 1 / -1; margin-top: 4px;
}
.step-note {
    width: 100%; box-sizing: border-box;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 8px 12px;
    font-size: .78rem; color: var(--text);
    font-family: inherit; resize: vertical;
    min-height: 60px; outline: none;
    transition: border-color .15s;
}
.step-note:focus { border-color: var(--pc); }
.step-note-save {
    margin-top: 6px;
    padding: 5px 14px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 6px;
    font-size: .72rem; font-weight: 600;
    color: var(--text-2); cursor: pointer;
    transition: background .15s;
}
.step-note-save:hover { background: var(--surface); }

/* ── Footer nav ──────────────────────────────────────────── */
.pilier-footer-nav {
    display: flex; justify-content: space-between;
    align-items: center; gap: 16px;
    padding: 20px 0 40px; flex-wrap: wrap;
}
.pilier-back-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    font-size: .8rem; font-weight: 600;
    color: var(--text-2); text-decoration: none;
    transition: background .15s;
}
.pilier-back-btn:hover { background: var(--surface-2); color: var(--text); }

.pilier-next-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 11px 22px;
    background: linear-gradient(135deg, #c9913b, #a0722a);
    color: #fff; border-radius: var(--radius);
    font-size: .83rem; font-weight: 700;
    text-decoration: none;
    transition: transform .2s, box-shadow .2s;
}
.pilier-next-btn:hover {
    transform: translateX(3px);
    box-shadow: 0 6px 20px rgba(201,145,59,.35);
    color: #fff;
}

@media (max-width: 700px) {
    .pilier-hero { padding: 20px; gap: 16px; }
    .step-body-inner { grid-template-columns: 1fr; }
    .pilier-footer-nav { justify-content: center; }
}
</style>

<div class="pilier-wrap">

    <!-- Fil d'Ariane -->
    <div class="pilier-breadcrumb anim">
        <a href="<?= ADMIN_URL ?>/dashboard.php?page=ancre">
            <i class="fas fa-anchor"></i> Méthode ANCRE
        </a>
        <span class="sep">/</span>
        <span style="color:var(--pc); font-weight:700">A — Ancrage local</span>
    </div>

    <!-- Hero -->
    <div class="pilier-hero anim">
        <div class="pilier-hero-left">
            <div class="pilier-letter-badge">A</div>
            <h1 class="pilier-hero-title">Ancrage local</h1>
            <p class="pilier-hero-sub">
                Devenez LA référence incontournable sur votre territoire.
                Définissez vos personas, optimisez votre présence Google et posez
                votre promesse de positionnement unique.
            </p>
        </div>
        <div class="pilier-progress-block">
            <div class="pilier-progress-label">Votre progression</div>
            <div class="pilier-progress-bar-wrap">
                <div class="pilier-progress-bar" id="progressBar"
                     style="width:<?= $pct ?>%"></div>
            </div>
            <div class="pilier-progress-stats">
                <span class="pilier-progress-pct" id="progressPct"><?= $pct ?>%</span>
                <span class="pilier-progress-frac" id="progressFrac">
                    <?= $done_count ?> / <?= $total_steps ?> étapes
                </span>
            </div>
        </div>
    </div>

    <!-- Nav inter-piliers -->
    <div class="pilier-nav anim">
        <?php
        $nav_piliers = [
            'A' => ['mot'=>'Ancrage',    'page'=>'ancre-a', 'color'=>'#ef4444'],
            'N' => ['mot'=>'Notoriété',  'page'=>'ancre-n', 'color'=>'#10b981'],
            'C' => ['mot'=>'Conversion', 'page'=>'ancre-c', 'color'=>'#f59e0b'],
            'R' => ['mot'=>'Relation',   'page'=>'ancre-r', 'color'=>'#6366f1'],
            'E' => ['mot'=>'Expansion',  'page'=>'ancre-e', 'color'=>'#8b5cf6'],
        ];
        foreach ($nav_piliers as $l => $nv): ?>
        <a href="<?= ADMIN_URL ?>/dashboard.php?page=<?= $nv['page'] ?>"
           class="pilier-nav-item <?= $l === 'A' ? 'current' : '' ?>">
            <span class="pilier-nav-dot" style="<?= $l === 'A' ? 'background:var(--pc)' : '' ?>"></span>
            <strong><?= $l ?></strong> — <?= $nv['mot'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Steps -->
    <div class="pilier-steps anim" id="stepsContainer">
        <?php foreach ($steps as $i => $step):
            $status = $progress[$step['key']]['status'] ?? 'todo';
            $note   = htmlspecialchars($progress[$step['key']]['note'] ?? '');
            $is_done  = $status === 'done';
            $is_doing = $status === 'doing';
            $diff_class = match($step['difficulte']) {
                'Facile'  => 'easy',
                'Continu' => 'continu',
                default   => 'medium',
            };
        ?>
        <div class="step-card <?= $is_done ? 'is-done' : ($is_doing ? 'is-doing' : '') ?>"
             id="step-<?= $step['key'] ?>">

            <div class="step-card-hd"
                 onclick="stepToggle('<?= $step['key'] ?>')">

                <div class="step-num"><?= $is_done ? '<i class="fas fa-check"></i>' : $step['num'] ?></div>

                <div class="step-meta">
                    <div class="step-titre">
                        <?= htmlspecialchars($step['titre']) ?>
                        <span class="step-badge <?= $diff_class ?>">
                            <?= htmlspecialchars($step['difficulte']) ?>
                        </span>
                    </div>
                    <div class="step-duree">
                        <i class="fas fa-clock" style="font-size:.65rem"></i>
                        <?= htmlspecialchars($step['duree']) ?>
                    </div>
                </div>

                <div class="step-right">
                    <select class="step-status-select <?= $status ?>"
                            onchange="stepStatusChange('<?= $step['key'] ?>',this)"
                            onclick="event.stopPropagation()">
                        <option value="todo"  <?= $status==='todo'  ?'selected':'' ?>>À faire</option>
                        <option value="doing" <?= $status==='doing' ?'selected':'' ?>>En cours</option>
                        <option value="done"  <?= $status==='done'  ?'selected':'' ?>>✓ Fait</option>
                    </select>
                    <i class="fas fa-chevron-down step-chevron"></i>
                </div>
            </div>

            <div class="step-body">
                <div class="step-body-inner">

                    <p class="step-desc"><?= htmlspecialchars($step['desc']) ?></p>

                    <!-- Ressources -->
                    <div>
                        <div class="step-section-title">
                            <i class="fas fa-file-alt" style="color:var(--pc)"></i>
                            Ressources
                        </div>
                        <ul class="step-ressource-list">
                            <?php foreach ($step['ressources'] as $r): ?>
                            <li><?= htmlspecialchars($r) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Module lien -->
                    <div style="display:flex;flex-direction:column;justify-content:flex-end">
                        <div class="step-section-title">
                            <i class="fas fa-puzzle-piece" style="color:var(--pc)"></i>
                            Module
                        </div>
                        <a href="<?= htmlspecialchars($step['module_lien']) ?>"
                           class="step-module-btn">
                            <i class="fas <?= htmlspecialchars($step['module_icon']) ?>"></i>
                            <?= htmlspecialchars($step['module_nom']) ?>
                        </a>
                    </div>

                    <!-- Tip -->
                    <div class="step-tip">
                        <i class="fas fa-lightbulb"></i>
                        <span><?= htmlspecialchars($step['tips']) ?></span>
                    </div>

                    <!-- Note -->
                    <div class="step-note-wrap">
                        <div class="step-section-title">
                            <i class="fas fa-pencil-alt" style="color:var(--pc)"></i>
                            Mes notes
                        </div>
                        <textarea class="step-note"
                                  id="note-<?= $step['key'] ?>"
                                  placeholder="Notez votre avancement, vos idées…"><?= $note ?></textarea>
                        <button class="step-note-save"
                                onclick="saveNote('<?= $step['key'] ?>')">
                            <i class="fas fa-save"></i> Enregistrer la note
                        </button>
                    </div>

                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Footer nav -->
    <div class="pilier-footer-nav anim">
        <a href="<?= ADMIN_URL ?>/dashboard.php?page=ancre" class="pilier-back-btn">
            <i class="fas fa-arrow-left"></i> Vue d'ensemble ANCRE
        </a>
        <a href="<?= ADMIN_URL ?>/dashboard.php?page=ancre-n" class="pilier-next-btn">
            Pilier N — Notoriété <i class="fas fa-arrow-right"></i>
        </a>
    </div>

</div><!-- /pilier-wrap -->

<?php
// ── Coach IA ───────────────────────────────────────────────────
include __DIR__ . '/coach.php';
?>

<script>
(function () {
    'use strict';

    const API_BASE  = <?= json_encode(rtrim(ADMIN_URL,'/') . '/modules/system/api/strategy/ancre-progress.php') ?>;
    const CSRF      = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
    const PILIER    = 'A';
    const TOTAL     = <?= $total_steps ?>;

    // ── Accordéon ─────────────────────────────────────────────
    window.stepToggle = function (key) {
        const card = document.getElementById('step-' + key);
        if (!card) return;
        const isOpen = card.classList.contains('is-open');
        document.querySelectorAll('.step-card.is-open')
                .forEach(c => c.classList.remove('is-open'));
        if (!isOpen) card.classList.add('is-open');
    };

    // Ouvrir la 1ère étape non faite au chargement
    const firstTodo = document.querySelector('.step-card:not(.is-done)');
    if (firstTodo) {
        setTimeout(() => firstTodo.classList.add('is-open'), 300);
    }

    // ── Changement de statut ──────────────────────────────────
    window.stepStatusChange = function (key, sel) {
        const status = sel.value;
        const card   = document.getElementById('step-' + key);

        // Mise à jour visuelle immédiate
        sel.className = 'step-status-select ' + status;
        card.classList.toggle('is-done',  status === 'done');
        card.classList.toggle('is-doing', status === 'doing');
        const numEl = card.querySelector('.step-num');
        if (status === 'done') {
            numEl.innerHTML = '<i class="fas fa-check"></i>';
        } else {
            numEl.innerHTML = numEl.dataset.num || card.querySelector('.step-meta')?.dataset?.num
                              || sel.closest('.step-card').id.replace('step-','');
        }

        // Sauvegarder + recalculer
        updateProgress(key, status);
    };

    function updateProgress(key, status, note) {
        const body = new FormData();
        body.append('action',   'update_step');
        body.append('pilier',   PILIER);
        body.append('step_key', key);
        body.append('status',   status);
        if (note !== undefined) body.append('note', note);

        fetch(API_BASE, { method: 'POST', body })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const pct  = Math.round(data.done / TOTAL * 100);
                document.getElementById('progressBar').style.width = pct + '%';
                document.getElementById('progressPct').textContent  = pct + '%';
                document.getElementById('progressFrac').textContent =
                    data.done + ' / ' + TOTAL + ' étapes';

                // Notif coach si 1ère étape complétée
                if (data.done === 1) {
                    setTimeout(() => {
                        if (window.coachNotify) coachNotify();
                    }, 800);
                }
            })
            .catch(() => {/* silencieux */});
    }

    // ── Sauvegarde note ───────────────────────────────────────
    window.saveNote = function (key) {
        const note   = document.getElementById('note-' + key)?.value || '';
        const card   = document.getElementById('step-' + key);
        const status = card?.querySelector('.step-status-select')?.value || 'todo';
        updateProgress(key, status, note);

        // Feedback visuel
        const btn = card?.querySelector('.step-note-save');
        if (btn) {
            btn.innerHTML = '<i class="fas fa-check"></i> Enregistré';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-save"></i> Enregistrer la note';
            }, 2000);
        }
    };

    // Numéros dans step-num pour restauration
    document.querySelectorAll('.step-card').forEach((card, idx) => {
        const numEl = card.querySelector('.step-num');
        if (numEl) numEl.dataset.num = idx + 1;
    });

})();
</script>