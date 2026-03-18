<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE STRATÉGIE DIGITALE — Index
 * /admin/modules/strategy/strategy/index.php
 *
 * Accès : dashboard.php?page=strategy-module
 * Dépendances : ROOT_PATH, INSTANCE_ID, getDB() — config.php
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);

// ── Config & DB ───────────────────────────────────────────────
if (!defined('ROOT_PATH'))    require_once dirname(__DIR__, 4) . '/config/config.php';
if (!defined('DB_HOST'))      require_once ROOT_PATH . '/config/config.php';
if (!function_exists('getDB')) require_once ROOT_PATH . '/includes/classes/Database.php';

try {
    $db = getDB();
} catch (Exception $e) {
    echo '<div style="padding:20px;color:#dc2626;font-family:monospace">❌ DB : '
         . htmlspecialchars($e->getMessage()) . '</div>';
    return;
}

// ── Stats : 1 seule requête UNION ALL ────────────────────────
$stats = ['personas_actifs' => 0, 'campagnes_actives' => 0, 'leads_mois' => 0, 'canaux' => 4];

try {
    $sql = "
        SELECT 'personas'  AS k, COUNT(*) AS v FROM neuropersona_config  WHERE actif    = 1
        UNION ALL
        SELECT 'campagnes' AS k, COUNT(*) AS v FROM neuropersona_campagnes WHERE statut = 'active'
        UNION ALL
        SELECT 'leads'     AS k, COUNT(*) AS v FROM leads
               WHERE created_at >= DATE_FORMAT(NOW(),'%Y-%m-01')
    ";
    foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($row['k'] === 'personas')  $stats['personas_actifs']  = (int)$row['v'];
        if ($row['k'] === 'campagnes') $stats['campagnes_actives'] = (int)$row['v'];
        if ($row['k'] === 'leads')     $stats['leads_mois']        = (int)$row['v'];
    }
} catch (Exception $e) {
    // Tables absentes au premier déploiement — stats restent à 0
    writeLog('strategy/index stats: ' . $e->getMessage(), 'WARNING');
}

// ── Modules depuis MySQL ──────────────────────────────────────
$modules = [];
try {
    $stmt = $db->prepare("
        SELECT slug, title, icon, icon_color_var, status, featured,
               description, tags, links
        FROM   strategy_modules
        WHERE  instance_id = :iid
          AND  active      = 1
        ORDER  BY sort_order ASC
    ");
    $stmt->execute([':iid' => INSTANCE_ID]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $row['tags']  = json_decode($row['tags']  ?? '[]', true) ?: [];
        $row['links'] = json_decode($row['links'] ?? '[]', true) ?: [];
        $modules[]    = $row;
    }
} catch (Exception $e) {
    writeLog('strategy/index modules: ' . $e->getMessage(), 'ERROR');
}

// ── Labels statut (CSS var-based, zéro couleur hardcodée) ────
$statusLabels = [
    'active' => ['● Actif',   'status-active'],
    'beta'   => ['Beta',      'status-beta'],
    'soon'   => ['Bientôt',   'status-soon'],
    'error'  => ['Erreur',    'status-error'],
];

// ── Étapes méthodologie ───────────────────────────────────────
$steps = [
    'Définir vos Personas prioritaires',
    'Crafter vos messages (Méthode MÈRE)',
    'Activer vos canaux de trafic',
    'Capturer & nurturer vos leads',
    'Analyser vos KPI régulièrement',
];
?>

<style>
/* ════════════════════════════════════════════════════════════
   MODULE STRATÉGIE DIGITALE
   100 % variables CSS du dashboard — zéro couleur hardcodée
   ════════════════════════════════════════════════════════════ */

/* ── Palette locale mappée sur les variables globales ──────── */
.strat-wrap {
    --strat-primary:    var(--accent,      #6366f1);
    --strat-primary-2:  var(--accent-dark, #4f46e5);
    --strat-radius:     var(--radius-lg,   12px);
    --strat-shadow:     var(--shadow-sm,   0 1px 3px rgba(0,0,0,.08));

    /* Couleurs sémantiques statuts */
    --status-active-bg:  #dcfce7; --status-active-fg:  #16a34a;
    --status-beta-bg:    #fef3c7; --status-beta-fg:    #b45309;
    --status-soon-bg:    var(--surface-2, #f3f4f6); --status-soon-fg: var(--text-3, #6b7280);
    --status-error-bg:   #fee2e2; --status-error-fg:   #dc2626;

    /* Couleurs icônes modules — variables nommées */
    --color-indigo:  rgba(99,  102, 241, .12);
    --color-emerald: rgba(16,  185, 129, .10);
    --color-amber:   rgba(245, 158,  11, .10);
    --color-cyan:    rgba(6,   182, 212, .10);
    --color-violet:  rgba(139,  92, 246, .10);
    --color-pink:    rgba(236,  72, 153, .10);
}

/* ── Banner ─────────────────────────────────────────────────── */
.strat-banner {
    background: linear-gradient(135deg, var(--strat-primary) 0%, var(--strat-primary-2) 100%);
    border-radius: var(--strat-radius);
    padding: 28px 32px;
    margin-bottom: 20px;
    display: flex; align-items: center; justify-content: space-between;
    position: relative; overflow: hidden; flex-wrap: wrap; gap: 16px;
}
.strat-banner::before {
    content: '';
    position: absolute; top: -50%; right: -5%;
    width: 280px; height: 280px;
    background: radial-gradient(circle, rgba(255,255,255,.08), transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.strat-banner::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, rgba(255,255,255,.3), transparent 60%);
}
.strat-banner-left { position: relative; z-index: 1; }
.strat-banner-left h2 {
    font-size: 1.4rem; font-weight: 800; color: #fff; margin: 0 0 5px;
    display: flex; align-items: center; gap: 10px;
}
.strat-banner-left p { color: rgba(255,255,255,.75); font-size: .85rem; margin: 0; }

/* ── Stats row ──────────────────────────────────────────────── */
.strat-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}
.strat-stat {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: var(--strat-radius);
    padding: 16px;
    text-align: center;
    box-shadow: var(--strat-shadow);
    transition: border-color .15s, box-shadow .15s;
}
.strat-stat:hover {
    border-color: var(--strat-primary);
    box-shadow: 0 4px 12px rgba(99,102,241,.10);
}
.strat-stat .num {
    font-size: 2rem; font-weight: 900; line-height: 1;
    background: linear-gradient(135deg, var(--strat-primary), var(--strat-primary-2));
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text;
    font-variant-numeric: tabular-nums;
}
.strat-stat .lbl {
    font-size: .7rem; color: var(--text-3, #9ca3af);
    text-transform: uppercase; letter-spacing: .06em;
    font-weight: 600; margin-top: 4px;
}

/* ── Grille modules ─────────────────────────────────────────── */
.strat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}

/* ── Carte module ───────────────────────────────────────────── */
.strat-card {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: var(--strat-radius);
    padding: 20px;
    transition: border-color .2s, box-shadow .2s, transform .2s;
    position: relative; overflow: hidden;
    display: flex; flex-direction: column; gap: 10px;
}
.strat-card:hover {
    border-color: var(--strat-primary);
    box-shadow: 0 6px 20px rgba(99,102,241,.12);
    transform: translateY(-2px);
}
.strat-card.is-featured {
    border: 2px solid var(--strat-primary);
    background: linear-gradient(135deg,
        rgba(99,102,241,.025),
        rgba(124,58,237,.025)
    );
}
.strat-card.is-featured::after {
    content: '⭐ Recommandé';
    position: absolute; top: 14px; right: -28px;
    background: linear-gradient(135deg, var(--strat-primary), var(--strat-primary-2));
    color: #fff; padding: 4px 40px;
    font-size: .6rem; font-weight: 700; letter-spacing: .05em;
    transform: rotate(45deg);
}
.strat-card.is-disabled { opacity: .55; pointer-events: none; }

/* Icône */
.strat-card-icon {
    width: 48px; height: 48px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.6rem; flex-shrink: 0;
    /* fond appliqué via var() inline — cf. PHP */
}

/* Header carte */
.strat-card-hd  { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.strat-card-title { font-size: .95rem; font-weight: 700; color: var(--text, #111827); }

/* Badge statut */
.strat-status {
    display: inline-flex; align-items: center;
    padding: 2px 8px; border-radius: 20px;
    font-size: .62rem; font-weight: 600;
}
.strat-status.status-active { background: var(--status-active-bg); color: var(--status-active-fg); }
.strat-status.status-beta   { background: var(--status-beta-bg);   color: var(--status-beta-fg);   }
.strat-status.status-soon   { background: var(--status-soon-bg);   color: var(--status-soon-fg);   }
.strat-status.status-error  { background: var(--status-error-bg);  color: var(--status-error-fg);  }

.strat-card-desc { font-size: .82rem; color: var(--text-2, #6b7280); line-height: 1.6; flex: 1; }

/* Tags */
.strat-tags { display: flex; flex-wrap: wrap; gap: 5px; }
.strat-tag {
    padding: 2px 8px;
    background: var(--surface-2, #f9fafb);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 4px;
    font-size: .68rem; color: var(--text-3, #9ca3af); font-weight: 600;
}

/* Boutons */
.strat-card-btns { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
.strat-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 7px 16px; border-radius: var(--radius, 8px);
    font-size: .78rem; font-weight: 700; cursor: pointer;
    text-decoration: none; transition: all .15s; border: none;
}
.strat-btn.is-primary {
    background: linear-gradient(135deg, var(--strat-primary), var(--strat-primary-2));
    color: #fff;
    box-shadow: 0 2px 6px rgba(99,102,241,.25);
}
.strat-btn.is-primary:hover {
    box-shadow: 0 4px 12px rgba(99,102,241,.35);
    transform: scale(1.02); color: #fff;
}
.strat-btn.is-secondary {
    background: var(--surface, #fff);
    color: var(--strat-primary);
    border: 1px solid var(--strat-primary);
}
.strat-btn.is-secondary:hover { background: rgba(99,102,241,.05); }
.strat-btn:disabled,
.strat-btn[disabled] {
    opacity: .5; cursor: not-allowed;
    transform: none !important; box-shadow: none !important;
}

/* ── Conseil ─────────────────────────────────────────────────── */
.strat-conseil {
    background: linear-gradient(135deg,
        rgba(99,102,241,.06),
        rgba(124,58,237,.06)
    );
    border: 1px solid rgba(99,102,241,.2);
    border-radius: var(--strat-radius);
    padding: 22px 24px;
}
.strat-conseil h3 {
    font-size: .9rem; font-weight: 700; color: var(--text, #111827);
    margin: 0 0 8px; display: flex; align-items: center; gap: 7px;
}
.strat-conseil p {
    font-size: .82rem; color: var(--text-2, #6b7280);
    line-height: 1.6; margin: 0 0 14px;
}
.strat-steps { display: flex; flex-wrap: wrap; gap: 8px; }
.strat-step {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 14px;
    background: var(--surface, #fff);
    border-radius: var(--radius, 8px);
    font-size: .8rem; color: var(--text, #374151);
    border: 1px solid var(--border, #e5e7eb);
}
.strat-step-num {
    width: 22px; height: 22px; border-radius: 50%;
    background: linear-gradient(135deg, var(--strat-primary), var(--strat-primary-2));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-size: .68rem; font-weight: 800; flex-shrink: 0;
}

/* ── État vide (aucun module en DB) ─────────────────────────── */
.strat-empty {
    padding: 48px; text-align: center;
    color: var(--text-3, #9ca3af); font-size: .85rem;
}
.strat-empty i { font-size: 2rem; display: block; margin-bottom: 12px; }

/* ── Responsive ─────────────────────────────────────────────── */
@media (max-width: 860px) {
    .strat-stats { grid-template-columns: repeat(2, 1fr); }
    .strat-grid  { grid-template-columns: 1fr; }
    .strat-steps { flex-direction: column; }
    .strat-banner { padding: 20px; }
}
</style>

<div class="strat-wrap">

    <!-- ── Banner ──────────────────────────────────────────── -->
    <div class="strat-banner anim">
        <div class="strat-banner-left">
            <h2>🎯 Stratégie Digitale</h2>
            <p>Méthodologie complète pour devenir le leader de votre zone :<br>
               Persona → Offre → Canaux → Conversion</p>
        </div>
        <a href="<?= htmlspecialchars(ADMIN_URL) ?>/dashboard.php?page=launchpad"
           class="strat-btn is-primary"
           style="position:relative;z-index:1">
            <i class="fas fa-rocket"></i> Lancer le Launchpad
        </a>
    </div>

    <!-- ── Stats ───────────────────────────────────────────── -->
    <div class="strat-stats anim">
        <?php
        $statItems = [
            [$stats['personas_actifs'],   'Personas actifs'],
            [$stats['campagnes_actives'], 'Campagnes actives'],
            [$stats['leads_mois'],        'Leads ce mois'],
            [$stats['canaux'],            'Canaux disponibles'],
        ];
        foreach ($statItems as [$num, $lbl]):
        ?>
        <div class="strat-stat">
            <div class="num"><?= (int)$num ?></div>
            <div class="lbl"><?= htmlspecialchars($lbl) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ── Modules ─────────────────────────────────────────── -->
    <div class="strat-grid anim">

        <?php if (empty($modules)): ?>
        <div class="strat-empty" style="grid-column:1/-1">
            <i class="fas fa-database"></i>
            Aucun module trouvé pour l'instance
            <strong><?= htmlspecialchars(INSTANCE_ID) ?></strong>.<br>
            Importez le fichier <code>strategy_modules.sql</code> pour initialiser les données.
        </div>

        <?php else: ?>
        <?php foreach ($modules as $mod):
            [$stLabel, $stClass] = $statusLabels[$mod['status']] ?? ['', ''];
            $isDisabled  = $mod['status'] === 'soon';
            $cardClasses = 'strat-card'
                . ($mod['featured']  ? ' is-featured' : '')
                . ($isDisabled       ? ' is-disabled'  : '');
        ?>
        <div class="<?= $cardClasses ?>">

            <!-- Icône -->
            <div class="strat-card-icon"
                 style="background:var(<?= htmlspecialchars($mod['icon_color_var']) ?>)">
                <?= htmlspecialchars($mod['icon']) ?>
            </div>

            <!-- Titre + statut -->
            <div class="strat-card-hd">
                <span class="strat-card-title"><?= htmlspecialchars($mod['title']) ?></span>
                <?php if ($stLabel): ?>
                <span class="strat-status <?= htmlspecialchars($stClass) ?>"><?= $stLabel ?></span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div class="strat-card-desc"><?= htmlspecialchars($mod['description']) ?></div>

            <!-- Tags -->
            <?php if (!empty($mod['tags'])): ?>
            <div class="strat-tags">
                <?php foreach ($mod['tags'] as $tag): ?>
                <span class="strat-tag"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Liens -->
            <div class="strat-card-btns">
                <?php if (!empty($mod['links']) && !$isDisabled): ?>
                    <?php foreach ($mod['links'] as $link): ?>
                    <a href="<?= htmlspecialchars($link['url'] ?? '#') ?>"
                       class="strat-btn <?= !empty($link['primary']) ? 'is-primary' : 'is-secondary' ?>">
                        <?= htmlspecialchars($link['label'] ?? 'Accéder') ?>
                    </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <button class="strat-btn is-secondary" disabled>Bientôt disponible</button>
                <?php endif; ?>
            </div>

        </div>
        <?php endforeach; ?>
        <?php endif; ?>

    </div>

    <!-- ── Conseil ─────────────────────────────────────────── -->
    <div class="strat-conseil anim">
        <h3>💡 Méthodologie recommandée</h3>
        <p>Suivez cette approche étape par étape pour maximiser vos résultats
           et devenir le leader de votre zone géographique.</p>
        <div class="strat-steps">
            <?php foreach ($steps as $i => $step): ?>
            <div class="strat-step">
                <span class="strat-step-num"><?= $i + 1 ?></span>
                <?= htmlspecialchars($step) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div><!-- /strat-wrap -->