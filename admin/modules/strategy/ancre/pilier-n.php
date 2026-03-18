<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MÉTHODE ANCRE — Pilier N : Notoriété digitale
 * /admin/modules/strategy/ancre/pilier-n.php
 *
 * Accès : dashboard.php?page=ancre-n
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

$user_id     = (int)($_SESSION['admin_id'] ?? 0);
$instance_id = INSTANCE_ID;

$steps = [
    [
        'key'         => 'seo_pages_quartiers',
        'num'         => 1,
        'titre'       => 'Créer une page SEO par quartier stratégique',
        'desc'        => 'Chaque page de quartier = une page optimisée avec contenu unique : marché local, prix au m², points d\'intérêt, témoignages de clients du secteur. Google indexe = vous attirez des vendeurs pré-qualifiés.',
        'duree'       => '2h / page',
        'difficulte'  => 'Moyen',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=local-seo',
        'module_nom'  => 'SEO Local — Pages quartiers',
        'module_icon' => 'fa-map-marker-alt',
        'ressources'  => ['Template page quartier','Checklist SEO on-page','Grille de mots-clés locaux'],
        'tips'        => 'Minimum 800 mots par page. Incluez des données de prix réels récents — Google les adore.',
    ],
    [
        'key'         => 'blog_articles',
        'num'         => 2,
        'titre'       => 'Publier 2 articles SEO par mois',
        'desc'        => 'Des articles qui répondent aux vraies questions de vos prospects : "Comment vendre son appartement à [ville] ?", "Estimation gratuite à [quartier]", "Marché immobilier [ville] 2025". Chaque article = un prospect capté.',
        'duree'       => '1h30 / article',
        'difficulte'  => 'Continu',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=articles',
        'module_nom'  => 'Rédiger un article',
        'module_icon' => 'fa-pen-nib',
        'ressources'  => ['50 titres d\'articles immobiliers','Calendrier éditorial type','Méthode AIDA pour articles'],
        'tips'        => 'Utilisez l\'IA pour générer le plan et les titres H2 — puis personnalisez avec vos anecdotes terrain.',
    ],
    [
        'key'         => 'social_calendrier',
        'num'         => 3,
        'titre'       => 'Mettre en place un calendrier réseaux sociaux',
        'desc'        => 'Fréquence recommandée : 3 posts/semaine (Facebook + Instagram minimum). Mix conseillé : 40% conseils vendeurs, 30% biens & transactions, 30% vie locale & coulisses. La régularité prime sur la perfection.',
        'duree'       => '2h / semaine',
        'difficulte'  => 'Continu',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=reseaux-sociaux',
        'module_nom'  => 'Réseaux sociaux',
        'module_icon' => 'fa-share-nodes',
        'ressources'  => ['Kit 30 visuels prêts à l\'emploi','Calendrier éditorial 90 jours','Hashtags locaux par ville'],
        'tips'        => 'Batch-créez votre contenu le lundi matin pour toute la semaine — 1h de création = 3 posts.',
    ],
    [
        'key'         => 'guide_local',
        'num'         => 4,
        'titre'       => 'Publier un Guide Local téléchargeable',
        'desc'        => 'Un guide PDF "Tout savoir pour vendre à [ville]" ou "Le marché immobilier de [quartier] décrypté" positionne votre expertise ET génère des leads via une page de capture dédiée.',
        'duree'       => '3h',
        'difficulte'  => 'Moyen',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=local-seo',
        'module_nom'  => 'Guide Local',
        'module_icon' => 'fa-file-pdf',
        'ressources'  => ['Template Guide Local PDF','Page de capture guide','Séquence email post-téléchargement'],
        'tips'        => 'Le titre doit contenir l\'année et le nom de votre ville. Ex : "Guide du Vendeur Bordeaux 2025".',
    ],
    [
        'key'         => 'avis_systeme',
        'num'         => 5,
        'titre'       => 'Atteindre 30 avis Google + répondre à tous',
        'desc'        => 'Les avis sont votre preuve sociale principale. Créez un lien direct vers votre fiche GMB, un message WhatsApp type, et programmez la demande J+2 après chaque transaction signée.',
        'duree'       => 'Continu',
        'difficulte'  => 'Continu',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=gmb',
        'module_nom'  => 'Avis GMB',
        'module_icon' => 'fa-star',
        'ressources'  => ['Script SMS demande d\'avis','Template réponse aux avis','Suivi avis Google Sheets'],
        'tips'        => 'Répondez à TOUS les avis, même négatifs — cela montre votre professionnalisme aux futurs vendeurs.',
    ],
];

$progress = [];
try {
    $db   = getDB();
    $stmt = $db->prepare(
        "SELECT step_key, status, note FROM ancre_progress
         WHERE instance_id=:iid AND user_id=:uid AND pilier='N'"
    );
    $stmt->execute([':iid' => $instance_id, ':uid' => $user_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $progress[$row['step_key']] = $row;
    }
} catch (Exception $e) {}

$done_count  = count(array_filter($progress, fn($r) => $r['status'] === 'done'));
$total_steps = count($steps);
$pct         = $total_steps > 0 ? round($done_count / $total_steps * 100) : 0;

$coach_pilier = [
    'lettre'  => 'N',
    'mot'     => 'Notoriété digitale',
    'contexte'=> "Le pilier N — Notoriété digitale consiste à :
1. Créer une page SEO par quartier stratégique (800+ mots, données prix réels)
2. Publier 2 articles SEO par mois (questions réelles des prospects)
3. Calendrier réseaux sociaux 3 posts/semaine (mix conseils/biens/vie locale)
4. Publier un Guide Local téléchargeable (PDF + page de capture)
5. Atteindre 30 avis Google et répondre à tous

L'objectif est d'attirer organiquement vendeurs et acheteurs sans dépendre des portails.",
    'suggestions' => [
        'Idées d\'articles SEO locaux',
        'Structure d\'une page quartier',
        'Calendrier réseaux sociaux',
        'Comment demander des avis ?',
    ],
];
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,700;0,900;1,400&family=DM+Sans:wght@300;400;500;700&display=swap');

.pilier-wrap {
    --pc:        #10b981;
    --pc-light:  #f0fdf4;
    --pc-border: #bbf7d0;
    --pc-text:   #065f46;
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
/* ---- styles identiques au pilier A (réutilisés via cascade) ---- */
.pilier-breadcrumb { display:flex;align-items:center;gap:8px;font-size:.75rem;color:var(--text-3);margin-bottom:20px; }
.pilier-breadcrumb a { color:var(--text-2);text-decoration:none;transition:color .15s; }
.pilier-breadcrumb a:hover { color:var(--pc); }
.pilier-hero { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:28px 32px;margin-bottom:20px;box-shadow:var(--shadow);display:flex;align-items:flex-start;gap:24px;flex-wrap:wrap; }
.pilier-hero-left { flex:1;min-width:240px; }
.pilier-letter-badge { display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:14px;background:var(--pc);color:#fff;font-family:'Fraunces',Georgia,serif;font-size:1.8rem;font-weight:900;margin-bottom:12px; }
.pilier-hero-title { font-family:'Fraunces',Georgia,serif;font-size:1.5rem;font-weight:900;color:var(--text);margin:0 0 6px;line-height:1.2; }
.pilier-hero-sub { font-size:.85rem;color:var(--text-2);line-height:1.6;max-width:480px;margin:0; }
.pilier-progress-block { min-width:200px;flex-shrink:0; }
.pilier-progress-label { font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);margin-bottom:8px; }
.pilier-progress-bar-wrap { height:8px;background:var(--surface-2);border:1px solid var(--border);border-radius:20px;overflow:hidden;margin-bottom:8px; }
.pilier-progress-bar { height:100%;background:var(--pc);border-radius:20px;transition:width .6s ease; }
.pilier-progress-stats { display:flex;justify-content:space-between;align-items:center; }
.pilier-progress-pct { font-size:1.4rem;font-weight:800;color:var(--pc);font-family:'Fraunces',Georgia,serif; }
.pilier-progress-frac { font-size:.75rem;color:var(--text-3); }
.pilier-nav { display:flex;gap:6px;margin-bottom:24px;flex-wrap:wrap; }
.pilier-nav-item { display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:var(--surface);border:1px solid var(--border);border-radius:20px;font-size:.72rem;font-weight:700;color:var(--text-2);text-decoration:none;transition:all .15s; }
.pilier-nav-item.current { background:var(--pc-light);border-color:var(--pc-border);color:var(--pc-text); }
.pilier-nav-dot { width:8px;height:8px;border-radius:50%;background:var(--border); }
.pilier-nav-item.current .pilier-nav-dot { background:var(--pc); }
.pilier-steps { display:flex;flex-direction:column;gap:12px;margin-bottom:24px; }
.step-card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;transition:border-color .15s,box-shadow .15s; }
.step-card.is-done { border-color:#bbf7d0;background:#f0fdf4; }
.step-card.is-doing { border-color:#fde68a; }
.step-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08); }
.step-card-hd { display:flex;align-items:center;gap:14px;padding:16px 20px;cursor:pointer;transition:background .15s; }
.step-card-hd:hover { background:var(--surface-2); }
.step-num { width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:800;color:#fff;background:var(--pc);flex-shrink:0;font-family:'Fraunces',Georgia,serif; }
.step-card.is-done .step-num { background:#10b981; }
.step-meta { flex:1;min-width:0; }
.step-titre { font-size:.9rem;font-weight:700;color:var(--text);margin-bottom:3px;display:flex;align-items:center;gap:8px;flex-wrap:wrap; }
.step-badge { padding:2px 8px;border-radius:20px;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;flex-shrink:0; }
.step-badge.easy { background:#dcfce7;color:#166534; }
.step-badge.medium { background:#fef3c7;color:#92400e; }
.step-badge.continu { background:#ede9fe;color:#5b21b6; }
.step-duree { font-size:.72rem;color:var(--text-3); }
.step-right { display:flex;align-items:center;gap:8px;flex-shrink:0; }
.step-status-select { padding:5px 10px;background:var(--surface-2);border:1px solid var(--border);border-radius:20px;font-size:.7rem;font-weight:600;color:var(--text-2);cursor:pointer;outline:none;appearance:none;font-family:inherit;transition:border-color .15s; }
.step-status-select.done { background:#dcfce7;border-color:#86efac;color:#166534; }
.step-status-select.doing { background:#fef3c7;border-color:#fde68a;color:#92400e; }
.step-chevron { color:var(--text-3);font-size:.7rem;transition:transform .25s; }
.step-card.is-open .step-chevron { transform:rotate(180deg); }
.step-body { max-height:0;overflow:hidden;transition:max-height .35s ease; }
.step-card.is-open .step-body { max-height:500px; }
.step-body-inner { padding:16px 20px 20px;border-top:1px solid var(--border);display:grid;grid-template-columns:1fr 1fr;gap:16px; }
.step-card.is-done .step-body-inner { border-color:#bbf7d0; }
.step-desc { font-size:.82rem;color:var(--text-2);line-height:1.65;grid-column:1 / -1; }
.step-section-title { font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);margin-bottom:8px;display:flex;align-items:center;gap:5px; }
.step-ressource-list { list-style:none;margin:0;padding:0; }
.step-ressource-list li { font-size:.78rem;color:var(--text-2);padding:5px 0;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:7px; }
.step-ressource-list li:last-child { border:none; }
.step-ressource-list li::before { content:'';width:6px;height:6px;border-radius:50%;background:var(--pc);flex-shrink:0; }
.step-tip { background:var(--pc-light);border:1px solid var(--pc-border);border-radius:8px;padding:10px 14px;font-size:.78rem;color:var(--pc-text);line-height:1.5;display:flex;align-items:flex-start;gap:8px;grid-column:1 / -1; }
.step-module-btn { grid-column:1 / -1;display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:var(--pc);color:#fff;border-radius:var(--radius);font-size:.78rem;font-weight:700;text-decoration:none;width:fit-content;transition:transform .15s,box-shadow .15s; }
.step-module-btn:hover { transform:translateX(3px);box-shadow:0 4px 14px rgba(16,185,129,.35);color:#fff; }
.step-note-wrap { grid-column:1 / -1;margin-top:4px; }
.step-note { width:100%;box-sizing:border-box;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:.78rem;color:var(--text);font-family:inherit;resize:vertical;min-height:60px;outline:none;transition:border-color .15s; }
.step-note:focus { border-color:var(--pc); }
.step-note-save { margin-top:6px;padding:5px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:6px;font-size:.72rem;font-weight:600;color:var(--text-2);cursor:pointer;transition:background .15s; }
.step-note-save:hover { background:var(--surface); }
.pilier-footer-nav { display:flex;justify-content:space-between;align-items:center;gap:16px;padding:20px 0 40px;flex-wrap:wrap; }
.pilier-back-btn { display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);font-size:.8rem;font-weight:600;color:var(--text-2);text-decoration:none;transition:background .15s; }
.pilier-back-btn:hover { background:var(--surface-2);color:var(--text); }
.pilier-next-btn { display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:linear-gradient(135deg,#c9913b,#a0722a);color:#fff;border-radius:var(--radius);font-size:.83rem;font-weight:700;text-decoration:none;transition:transform .2s,box-shadow .2s; }
.pilier-next-btn:hover { transform:translateX(3px);box-shadow:0 6px 20px rgba(201,145,59,.35);color:#fff; }
@media (max-width:700px) {
    .pilier-hero { padding:20px;gap:16px; }
    .step-body-inner { grid-template-columns:1fr; }
}
</style>

<div class="pilier-wrap">
    <div class="pilier-breadcrumb anim">
        <a href="<?= ADMIN_URL ?>/dashboard.php?page=ancre"><i class="fas fa-anchor"></i> Méthode ANCRE</a>
        <span>/</span>
        <span style="color:var(--pc);font-weight:700">N — Notoriété digitale</span>
    </div>

    <div class="pilier-hero anim">
        <div class="pilier-hero-left">
            <div class="pilier-letter-badge">N</div>
            <h1 class="pilier-hero-title">Notoriété digitale</h1>
            <p class="pilier-hero-sub">
                Construisez votre visibilité organique durable. SEO local, contenu de référence
                et réseaux sociaux pour attirer acheteurs et vendeurs sans payer les portails.
            </p>
        </div>
        <div class="pilier-progress-block">
            <div class="pilier-progress-label">Votre progression</div>
            <div class="pilier-progress-bar-wrap">
                <div class="pilier-progress-bar" id="progressBar" style="width:<?= $pct ?>%"></div>
            </div>
            <div class="pilier-progress-stats">
                <span class="pilier-progress-pct" id="progressPct"><?= $pct ?>%</span>
                <span class="pilier-progress-frac" id="progressFrac"><?= $done_count ?> / <?= $total_steps ?> étapes</span>
            </div>
        </div>
    </div>

    <div class="pilier-nav anim">
        <?php foreach(['A'=>['Ancrage','ancre-a'],'N'=>['Notoriété','ancre-n'],'C'=>['Conversion','ancre-c'],'R'=>['Relation','ancre-r'],'E'=>['Expansion','ancre-e']] as $l=>[$m,$pg]): ?>
        <a href="<?= ADMIN_URL ?>/dashboard.php?page=<?= $pg ?>" class="pilier-nav-item <?= $l==='N'?'current':'' ?>">
            <span class="pilier-nav-dot" style="<?= $l==='N'?'background:var(--pc)':'' ?>"></span>
            <strong><?= $l ?></strong> — <?= $m ?>
        </a>
        <?php endforeach; ?>
    </div>

    <div class="pilier-steps anim" id="stepsContainer">
        <?php foreach ($steps as $i => $step):
            $status = $progress[$step['key']]['status'] ?? 'todo';
            $note   = htmlspecialchars($progress[$step['key']]['note'] ?? '');
            $diff_class = match($step['difficulte']) { 'Facile'=>'easy','Continu'=>'continu',default=>'medium' };
        ?>
        <div class="step-card <?= $status==='done'?'is-done':($status==='doing'?'is-doing':'') ?>" id="step-<?= $step['key'] ?>">
            <div class="step-card-hd" onclick="stepToggle('<?= $step['key'] ?>')">
                <div class="step-num" data-num="<?= $step['num'] ?>">
                    <?= $status==='done' ? '<i class="fas fa-check"></i>' : $step['num'] ?>
                </div>
                <div class="step-meta">
                    <div class="step-titre">
                        <?= htmlspecialchars($step['titre']) ?>
                        <span class="step-badge <?= $diff_class ?>"><?= htmlspecialchars($step['difficulte']) ?></span>
                    </div>
                    <div class="step-duree"><i class="fas fa-clock" style="font-size:.65rem"></i> <?= htmlspecialchars($step['duree']) ?></div>
                </div>
                <div class="step-right">
                    <select class="step-status-select <?= $status ?>" onchange="stepStatusChange('<?= $step['key'] ?>',this)" onclick="event.stopPropagation()">
                        <option value="todo"  <?= $status==='todo' ?'selected':'' ?>>À faire</option>
                        <option value="doing" <?= $status==='doing'?'selected':'' ?>>En cours</option>
                        <option value="done"  <?= $status==='done' ?'selected':'' ?>>✓ Fait</option>
                    </select>
                    <i class="fas fa-chevron-down step-chevron"></i>
                </div>
            </div>
            <div class="step-body">
                <div class="step-body-inner">
                    <p class="step-desc"><?= htmlspecialchars($step['desc']) ?></p>
                    <div>
                        <div class="step-section-title"><i class="fas fa-file-alt" style="color:var(--pc)"></i> Ressources</div>
                        <ul class="step-ressource-list">
                            <?php foreach ($step['ressources'] as $r): ?><li><?= htmlspecialchars($r) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                    <div style="display:flex;flex-direction:column;justify-content:flex-end">
                        <div class="step-section-title"><i class="fas fa-puzzle-piece" style="color:var(--pc)"></i> Module</div>
                        <a href="<?= htmlspecialchars($step['module_lien']) ?>" class="step-module-btn">
                            <i class="fas <?= htmlspecialchars($step['module_icon']) ?>"></i> <?= htmlspecialchars($step['module_nom']) ?>
                        </a>
                    </div>
                    <div class="step-tip"><i class="fas fa-lightbulb"></i><span><?= htmlspecialchars($step['tips']) ?></span></div>
                    <div class="step-note-wrap">
                        <div class="step-section-title"><i class="fas fa-pencil-alt" style="color:var(--pc)"></i> Mes notes</div>
                        <textarea class="step-note" id="note-<?= $step['key'] ?>" placeholder="Notez votre avancement…"><?= $note ?></textarea>
                        <button class="step-note-save" onclick="saveNote('<?= $step['key'] ?>')"><i class="fas fa-save"></i> Enregistrer la note</button>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="pilier-footer-nav anim">
        <a href="<?= ADMIN_URL ?>/dashboard.php?page=ancre-a" class="pilier-back-btn">
            <i class="fas fa-arrow-left"></i> A — Ancrage local
        </a>
        <a href="<?= ADMIN_URL ?>/dashboard.php?page=ancre-c" class="pilier-next-btn">
            Pilier C — Conversion <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</div>

<?php include __DIR__ . '/coach.php'; ?>

<script>
(function(){
    'use strict';
    const API_BASE = <?= json_encode(rtrim(ADMIN_URL,'/').'/modules/system/api/strategy/ancre-progress.php') ?>;
    const PILIER = 'N', TOTAL = <?= $total_steps ?>;

    window.stepToggle = function(key){
        const card = document.getElementById('step-'+key);
        if (!card) return;
        const wasOpen = card.classList.contains('is-open');
        document.querySelectorAll('.step-card.is-open').forEach(c=>c.classList.remove('is-open'));
        if (!wasOpen) card.classList.add('is-open');
    };
    const firstTodo = document.querySelector('.step-card:not(.is-done)');
    if (firstTodo) setTimeout(()=>firstTodo.classList.add('is-open'),300);

    window.stepStatusChange = function(key, sel){
        const status = sel.value;
        const card   = document.getElementById('step-'+key);
        sel.className = 'step-status-select '+status;
        card.classList.toggle('is-done',  status==='done');
        card.classList.toggle('is-doing', status==='doing');
        const numEl = card.querySelector('.step-num');
        numEl.innerHTML = status==='done' ? '<i class="fas fa-check"></i>' : (numEl.dataset.num||'');
        updateProgress(key, status);
    };

    function updateProgress(key, status, note){
        const body = new FormData();
        body.append('action','update_step'); body.append('pilier',PILIER);
        body.append('step_key',key); body.append('status',status);
        if (note!==undefined) body.append('note',note);
        fetch(API_BASE,{method:'POST',body}).then(r=>r.json()).then(data=>{
            if (!data.success) return;
            const pct = Math.round(data.done/TOTAL*100);
            document.getElementById('progressBar').style.width = pct+'%';
            document.getElementById('progressPct').textContent = pct+'%';
            document.getElementById('progressFrac').textContent = data.done+' / '+TOTAL+' étapes';
        }).catch(()=>{});
    }

    window.saveNote = function(key){
        const note   = document.getElementById('note-'+key)?.value||'';
        const card   = document.getElementById('step-'+key);
        const status = card?.querySelector('.step-status-select')?.value||'todo';
        updateProgress(key, status, note);
        const btn = card?.querySelector('.step-note-save');
        if (btn){ btn.innerHTML='<i class="fas fa-check"></i> Enregistré'; setTimeout(()=>btn.innerHTML='<i class="fas fa-save"></i> Enregistrer la note',2000); }
    };
})();
</script>