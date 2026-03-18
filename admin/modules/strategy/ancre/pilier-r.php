<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MÉTHODE ANCRE — Pilier R : Relation durable
 * /admin/modules/strategy/ancre/pilier-r.php
 * ══════════════════════════════════════════════════════════════
 */
defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

$user_id     = (int)($_SESSION['admin_id'] ?? 0);
$instance_id = INSTANCE_ID;

$steps = [
    [
        'key'         => 'pipeline_crm',
        'num'         => 1,
        'titre'       => 'Configurer le pipeline CRM en 5 étapes',
        'desc'        => 'Pipeline recommandé : Nouveau lead → Contacté → RDV fixé → Estimation réalisée → Mandat signé. Chaque étape doit avoir une action déclenchée automatiquement (email, SMS, rappel).',
        'duree'       => '1h',
        'difficulte'  => 'Moyen',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=crm',
        'module_nom'  => 'CRM — Pipeline',
        'module_icon' => 'fa-stream',
        'ressources'  => ['Template pipeline 5 étapes','Actions automatiques par étape','SLA de relance par statut'],
        'tips'        => 'Ajoutez une étape "En réflexion" pour les prospects qui ne disent ni oui ni non — ils signent souvent 3-6 mois plus tard.',
    ],
    [
        'key'         => 'sequences_nurturing',
        'num'         => 2,
        'titre'       => 'Créer 3 séquences email de nurturing',
        'desc'        => 'Séquence 1 : Bienvenue (3 emails en 7 jours). Séquence 2 : Prospects froids — valeur marché (1 email/mois). Séquence 3 : Post-estimation (5 emails en 14 jours). L\'automatisation multiplie votre présence sans effort.',
        'duree'       => '3h',
        'difficulte'  => 'Moyen',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=sequences',
        'module_nom'  => 'Séquences email',
        'module_icon' => 'fa-envelope-open-text',
        'ressources'  => ['3 séquences prêtes à l\'emploi','Objets d\'email ouverts à 40%+','Timing optimal de relances'],
        'tips'        => 'L\'email du J+3 est celui avec le meilleur taux de réponse — personnalisez-le avec le prénom et le bien estimé.',
    ],
    [
        'key'         => 'scoring_leads',
        'num'         => 3,
        'titre'       => 'Mettre en place le scoring prospects',
        'desc'        => 'Attribuez des points selon les actions : ouverture email (+5), clic (+10), visite page estimation (+20), formulaire soumis (+50). Un score > 80 déclenche une alerte pour relance manuelle immédiate.',
        'duree'       => '1h',
        'difficulte'  => 'Moyen',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=scoring',
        'module_nom'  => 'Scoring prospects',
        'module_icon' => 'fa-chart-bar',
        'ressources'  => ['Grille de scoring immobilier','Seuils d\'alerte recommandés','Intégration scoring ↔ CRM'],
        'tips'        => 'Un prospect qui visite votre page estimation 3 fois sans soumettre est un HOT LEAD — appelez-le dans les 24h.',
    ],
    [
        'key'         => 'relances_auto',
        'num'         => 4,
        'titre'       => 'Automatiser les relances J+3, J+7, J+14',
        'desc'        => 'Après chaque premier contact ou estimation, les relances automatiques maintiennent la relation sans effort. Chaque relance doit apporter une valeur différente : info marché, témoignage, article pertinent.',
        'duree'       => '2h',
        'difficulte'  => 'Moyen',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=sequences',
        'module_nom'  => 'Séquences — Relances',
        'module_icon' => 'fa-redo',
        'ressources'  => ['Templates relances J+3/J+7/J+14','Objets anti-spam','Conditions d\'arrêt automatique'],
        'tips'        => 'Arrêtez toujours la séquence si le prospect répond ou prend RDV — rien de pire qu\'un email automatique après une signature.',
    ],
    [
        'key'         => 'newsletter_mensuelle',
        'num'         => 5,
        'titre'       => 'Lancer la newsletter mensuelle "Marché local"',
        'desc'        => 'Une newsletter mensuelle maintient le contact avec toute votre base sur le long terme. Format idéal : prix du marché local, 1 bien vendu, 1 conseil vendeur, 1 actu quartier. 300 mots max, 1 CTA.',
        'duree'       => '1h / mois',
        'difficulte'  => 'Continu',
        'module_lien' => ADMIN_URL . '/dashboard.php?page=newsletters',
        'module_nom'  => 'Newsletters',
        'module_icon' => 'fa-newspaper',
        'ressources'  => ['Template newsletter mensuelle','Calendrier de publication','Segmentation par zone géo'],
        'tips'        => 'Envoyez le mardi ou jeudi matin entre 9h et 11h — les taux d\'ouverture sont 30% supérieurs.',
    ],
];

$progress = [];
try {
    $db   = getDB();
    $stmt = $db->prepare("SELECT step_key, status, note FROM ancre_progress WHERE instance_id=:iid AND user_id=:uid AND pilier='R'");
    $stmt->execute([':iid' => $instance_id, ':uid' => $user_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $progress[$row['step_key']] = $row;
} catch (Exception $e) {}

$done_count = count(array_filter($progress, fn($r) => $r['status'] === 'done'));
$total_steps = count($steps);
$pct = $total_steps > 0 ? round($done_count / $total_steps * 100) : 0;

$coach_pilier = [
    'lettre'  => 'R',
    'mot'     => 'Relation durable',
    'contexte'=> "Le pilier R — Relation durable consiste à :
1. Configurer un pipeline CRM en 5 étapes avec actions automatiques
2. Créer 3 séquences email de nurturing (Bienvenue, Prospects froids, Post-estimation)
3. Mettre en place le scoring prospects (actions = points, seuil d'alerte)
4. Automatiser les relances J+3, J+7, J+14 post-contact
5. Lancer une newsletter mensuelle Marché local (300 mots, 1 CTA)

L'objectif est de maintenir la relation dans la durée pour convertir les prospects froids 3 à 6 mois après le premier contact.",
    'suggestions' => [
        'Configurer mon pipeline CRM',
        'Écrire une séquence post-estimation',
        'Scoring : quels critères utiliser ?',
        'Idées pour ma newsletter mensuelle',
    ],
];
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,700;0,900;1,400&family=DM+Sans:wght@300;400;500;700&display=swap');
.pilier-wrap{--pc:#6366f1;--pc-light:#eef2ff;--pc-border:#c7d2fe;--pc-text:#3730a3;--surface:var(--surface,#fff);--surface-2:var(--surface-2,#f9fafb);--border:var(--border,#e5e7eb);--radius:var(--radius-lg,12px);--shadow:var(--shadow-sm,0 1px 3px rgba(0,0,0,.08));--text:var(--text,#111827);--text-2:var(--text-2,#6b7280);--text-3:var(--text-3,#9ca3af);font-family:'DM Sans',sans-serif;max-width:900px;margin:0 auto}
.pilier-breadcrumb{display:flex;align-items:center;gap:8px;font-size:.75rem;color:var(--text-3);margin-bottom:20px}
.pilier-breadcrumb a{color:var(--text-2);text-decoration:none;transition:color .15s}
.pilier-breadcrumb a:hover{color:var(--pc)}
.pilier-hero{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:28px 32px;margin-bottom:20px;box-shadow:var(--shadow);display:flex;align-items:flex-start;gap:24px;flex-wrap:wrap}
.pilier-hero-left{flex:1;min-width:240px}
.pilier-letter-badge{display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:14px;background:var(--pc);color:#fff;font-family:'Fraunces',Georgia,serif;font-size:1.8rem;font-weight:900;margin-bottom:12px}
.pilier-hero-title{font-family:'Fraunces',Georgia,serif;font-size:1.5rem;font-weight:900;color:var(--text);margin:0 0 6px;line-height:1.2}
.pilier-hero-sub{font-size:.85rem;color:var(--text-2);line-height:1.6;max-width:480px;margin:0}
.pilier-progress-block{min-width:200px;flex-shrink:0}
.pilier-progress-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);margin-bottom:8px}
.pilier-progress-bar-wrap{height:8px;background:var(--surface-2);border:1px solid var(--border);border-radius:20px;overflow:hidden;margin-bottom:8px}
.pilier-progress-bar{height:100%;background:var(--pc);border-radius:20px;transition:width .6s ease}
.pilier-progress-stats{display:flex;justify-content:space-between;align-items:center}
.pilier-progress-pct{font-size:1.4rem;font-weight:800;color:var(--pc);font-family:'Fraunces',Georgia,serif}
.pilier-progress-frac{font-size:.75rem;color:var(--text-3)}
.pilier-nav{display:flex;gap:6px;margin-bottom:24px;flex-wrap:wrap}
.pilier-nav-item{display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:var(--surface);border:1px solid var(--border);border-radius:20px;font-size:.72rem;font-weight:700;color:var(--text-2);text-decoration:none;transition:all .15s}
.pilier-nav-item.current{background:var(--pc-light);border-color:var(--pc-border);color:var(--pc-text)}
.pilier-nav-dot{width:8px;height:8px;border-radius:50%;background:var(--border)}
.pilier-nav-item.current .pilier-nav-dot{background:var(--pc)}
.pilier-steps{display:flex;flex-direction:column;gap:12px;margin-bottom:24px}
.step-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;transition:border-color .15s,box-shadow .15s}
.step-card.is-done{border-color:#bbf7d0;background:#f0fdf4}
.step-card.is-doing{border-color:#fde68a}
.step-card:hover{box-shadow:0 4px 16px rgba(0,0,0,.08)}
.step-card-hd{display:flex;align-items:center;gap:14px;padding:16px 20px;cursor:pointer;transition:background .15s}
.step-card-hd:hover{background:var(--surface-2)}
.step-num{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:800;color:#fff;background:var(--pc);flex-shrink:0;font-family:'Fraunces',Georgia,serif}
.step-card.is-done .step-num{background:#10b981}
.step-meta{flex:1;min-width:0}
.step-titre{font-size:.9rem;font-weight:700;color:var(--text);margin-bottom:3px;display:flex;align-items:center;gap:8px;flex-wrap:wrap}
.step-badge{padding:2px 8px;border-radius:20px;font-size:.6rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;flex-shrink:0}
.step-badge.easy{background:#dcfce7;color:#166534}
.step-badge.medium{background:#fef3c7;color:#92400e}
.step-badge.continu{background:#ede9fe;color:#5b21b6}
.step-duree{font-size:.72rem;color:var(--text-3)}
.step-right{display:flex;align-items:center;gap:8px;flex-shrink:0}
.step-status-select{padding:5px 10px;background:var(--surface-2);border:1px solid var(--border);border-radius:20px;font-size:.7rem;font-weight:600;color:var(--text-2);cursor:pointer;outline:none;appearance:none;font-family:inherit;transition:border-color .15s}
.step-status-select.done{background:#dcfce7;border-color:#86efac;color:#166534}
.step-status-select.doing{background:#fef3c7;border-color:#fde68a;color:#92400e}
.step-chevron{color:var(--text-3);font-size:.7rem;transition:transform .25s}
.step-card.is-open .step-chevron{transform:rotate(180deg)}
.step-body{max-height:0;overflow:hidden;transition:max-height .35s ease}
.step-card.is-open .step-body{max-height:500px}
.step-body-inner{padding:16px 20px 20px;border-top:1px solid var(--border);display:grid;grid-template-columns:1fr 1fr;gap:16px}
.step-card.is-done .step-body-inner{border-color:#bbf7d0}
.step-desc{font-size:.82rem;color:var(--text-2);line-height:1.65;grid-column:1 / -1}
.step-section-title{font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);margin-bottom:8px;display:flex;align-items:center;gap:5px}
.step-ressource-list{list-style:none;margin:0;padding:0}
.step-ressource-list li{font-size:.78rem;color:var(--text-2);padding:5px 0;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:7px}
.step-ressource-list li:last-child{border:none}
.step-ressource-list li::before{content:'';width:6px;height:6px;border-radius:50%;background:var(--pc);flex-shrink:0}
.step-tip{background:var(--pc-light);border:1px solid var(--pc-border);border-radius:8px;padding:10px 14px;font-size:.78rem;color:var(--pc-text);line-height:1.5;display:flex;align-items:flex-start;gap:8px;grid-column:1 / -1}
.step-module-btn{grid-column:1 / -1;display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:var(--pc);color:#fff;border-radius:var(--radius);font-size:.78rem;font-weight:700;text-decoration:none;width:fit-content;transition:transform .15s,box-shadow .15s}
.step-module-btn:hover{transform:translateX(3px);color:#fff}
.step-note-wrap{grid-column:1 / -1;margin-top:4px}
.step-note{width:100%;box-sizing:border-box;background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:.78rem;color:var(--text);font-family:inherit;resize:vertical;min-height:60px;outline:none;transition:border-color .15s}
.step-note:focus{border-color:var(--pc)}
.step-note-save{margin-top:6px;padding:5px 14px;background:var(--surface-2);border:1px solid var(--border);border-radius:6px;font-size:.72rem;font-weight:600;color:var(--text-2);cursor:pointer;transition:background .15s}
.pilier-footer-nav{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:20px 0 40px;flex-wrap:wrap}
.pilier-back-btn{display:inline-flex;align-items:center;gap:8px;padding:10px 20px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);font-size:.8rem;font-weight:600;color:var(--text-2);text-decoration:none;transition:background .15s}
.pilier-back-btn:hover{background:var(--surface-2);color:var(--text)}
.pilier-next-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;background:linear-gradient(135deg,#c9913b,#a0722a);color:#fff;border-radius:var(--radius);font-size:.83rem;font-weight:700;text-decoration:none;transition:transform .2s,box-shadow .2s}
.pilier-next-btn:hover{transform:translateX(3px);box-shadow:0 6px 20px rgba(201,145,59,.35);color:#fff}
@media(max-width:700px){.pilier-hero{padding:20px;gap:16px}.step-body-inner{grid-template-columns:1fr}}
</style>

<div class="pilier-wrap">
    <div class="pilier-breadcrumb anim">
        <a href="<?= ADMIN_URL ?>/dashboard.php?page=ancre"><i class="fas fa-anchor"></i> Méthode ANCRE</a>
        <span>/</span>
        <span style="color:var(--pc);font-weight:700">R — Relation durable</span>
    </div>
    <div class="pilier-hero anim">
        <div class="pilier-hero-left">
            <div class="pilier-letter-badge">R</div>
            <h1 class="pilier-hero-title">Relation durable</h1>
            <p class="pilier-hero-sub">Nurturez vos prospects dans la durée. Pipeline CRM structuré, séquences automatisées et scoring intelligent pour ne jamais laisser un lead chaud sans suite.</p>
        </div>
        <div class="pilier-progress-block">
            <div class="pilier-progress-label">Votre progression</div>
            <div class="pilier-progress-bar-wrap"><div class="pilier-progress-bar" id="progressBar" style="width:<?= $pct ?>%"></div></div>
            <div class="pilier-progress-stats">
                <span class="pilier-progress-pct" id="progressPct"><?= $pct ?>%</span>
                <span class="pilier-progress-frac" id="progressFrac"><?= $done_count ?> / <?= $total_steps ?> étapes</span>
            </div>
        </div>
    </div>
    <div class="pilier-nav anim">
        <?php foreach(['A'=>['Ancrage','ancre-a'],'N'=>['Notoriété','ancre-n'],'C'=>['Conversion','ancre-c'],'R'=>['Relation','ancre-r'],'E'=>['Expansion','ancre-e']] as $l=>[$m,$pg]): ?>
        <a href="<?= ADMIN_URL ?>/dashboard.php?page=<?= $pg ?>" class="pilier-nav-item <?= $l==='R'?'current':'' ?>">
            <span class="pilier-nav-dot" style="<?= $l==='R'?'background:var(--pc)':'' ?>"></span>
            <strong><?= $l ?></strong> — <?= $m ?>
        </a>
        <?php endforeach; ?>
    </div>
    <div class="pilier-steps anim">
        <?php foreach ($steps as $step):
            $status=$progress[$step['key']]['status']??'todo';
            $note=htmlspecialchars($progress[$step['key']]['note']??'');
            $dc=match($step['difficulte']){'Facile'=>'easy','Continu'=>'continu',default=>'medium'};
        ?>
        <div class="step-card <?= $status==='done'?'is-done':($status==='doing'?'is-doing':'') ?>" id="step-<?= $step['key'] ?>">
            <div class="step-card-hd" onclick="stepToggle('<?= $step['key'] ?>')">
                <div class="step-num" data-num="<?= $step['num'] ?>"><?= $status==='done'?'<i class="fas fa-check"></i>':$step['num'] ?></div>
                <div class="step-meta">
                    <div class="step-titre"><?= htmlspecialchars($step['titre']) ?><span class="step-badge <?= $dc ?>"><?= $step['difficulte'] ?></span></div>
                    <div class="step-duree"><i class="fas fa-clock" style="font-size:.65rem"></i> <?= $step['duree'] ?></div>
                </div>
                <div class="step-right">
                    <select class="step-status-select <?= $status ?>" onchange="stepStatusChange('<?= $step['key'] ?>',this)" onclick="event.stopPropagation()">
                        <option value="todo" <?= $status==='todo'?'selected':'' ?>>À faire</option>
                        <option value="doing" <?= $status==='doing'?'selected':'' ?>>En cours</option>
                        <option value="done" <?= $status==='done'?'selected':'' ?>>✓ Fait</option>
                    </select>
                    <i class="fas fa-chevron-down step-chevron"></i>
                </div>
            </div>
            <div class="step-body"><div class="step-body-inner">
                <p class="step-desc"><?= htmlspecialchars($step['desc']) ?></p>
                <div><div class="step-section-title"><i class="fas fa-file-alt" style="color:var(--pc)"></i> Ressources</div><ul class="step-ressource-list"><?php foreach($step['ressources'] as $r): ?><li><?= htmlspecialchars($r) ?></li><?php endforeach; ?></ul></div>
                <div style="display:flex;flex-direction:column;justify-content:flex-end"><div class="step-section-title"><i class="fas fa-puzzle-piece" style="color:var(--pc)"></i> Module</div><a href="<?= htmlspecialchars($step['module_lien']) ?>" class="step-module-btn"><i class="fas <?= htmlspecialchars($step['module_icon']) ?>"></i> <?= htmlspecialchars($step['module_nom']) ?></a></div>
                <div class="step-tip"><i class="fas fa-lightbulb"></i><span><?= htmlspecialchars($step['tips']) ?></span></div>
                <div class="step-note-wrap"><div class="step-section-title"><i class="fas fa-pencil-alt" style="color:var(--pc)"></i> Mes notes</div><textarea class="step-note" id="note-<?= $step['key'] ?>" placeholder="Notez votre avancement…"><?= $note ?></textarea><button class="step-note-save" onclick="saveNote('<?= $step['key'] ?>')"><i class="fas fa-save"></i> Enregistrer</button></div>
            </div></div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="pilier-footer-nav anim">
        <a href="<?= ADMIN_URL ?>/dashboard.php?page=ancre-c" class="pilier-back-btn"><i class="fas fa-arrow-left"></i> C — Conversion</a>
        <a href="<?= ADMIN_URL ?>/dashboard.php?page=ancre-e" class="pilier-next-btn">Pilier E — Expansion <i class="fas fa-arrow-right"></i></a>
    </div>
</div>

<?php include __DIR__ . '/coach.php'; ?>
<script>
(function(){
    const API_BASE=<?= json_encode(rtrim(ADMIN_URL,'/').'/modules/system/api/strategy/ancre-progress.php') ?>;
    const PILIER='R',TOTAL=<?= $total_steps ?>;
    window.stepToggle=function(key){const c=document.getElementById('step-'+key);if(!c)return;const o=c.classList.contains('is-open');document.querySelectorAll('.step-card.is-open').forEach(x=>x.classList.remove('is-open'));if(!o)c.classList.add('is-open')};
    const ft=document.querySelector('.step-card:not(.is-done)');if(ft)setTimeout(()=>ft.classList.add('is-open'),300);
    window.stepStatusChange=function(key,sel){const s=sel.value,c=document.getElementById('step-'+key);sel.className='step-status-select '+s;c.classList.toggle('is-done',s==='done');c.classList.toggle('is-doing',s==='doing');const n=c.querySelector('.step-num');n.innerHTML=s==='done'?'<i class="fas fa-check"></i>':(n.dataset.num||'');updateProgress(key,s)};
    function updateProgress(key,status,note){const b=new FormData();b.append('action','update_step');b.append('pilier',PILIER);b.append('step_key',key);b.append('status',status);if(note!==undefined)b.append('note',note);fetch(API_BASE,{method:'POST',body:b}).then(r=>r.json()).then(d=>{if(!d.success)return;const p=Math.round(d.done/TOTAL*100);document.getElementById('progressBar').style.width=p+'%';document.getElementById('progressPct').textContent=p+'%';document.getElementById('progressFrac').textContent=d.done+' / '+TOTAL+' étapes'}).catch(()=>{})}
    window.saveNote=function(key){const note=document.getElementById('note-'+key)?.value||'';const c=document.getElementById('step-'+key);const s=c?.querySelector('.step-status-select')?.value||'todo';updateProgress(key,s,note);const btn=c?.querySelector('.step-note-save');if(btn){btn.innerHTML='<i class="fas fa-check"></i> Enregistré';setTimeout(()=>btn.innerHTML='<i class="fas fa-save"></i> Enregistrer',2000)}};
})();
</script>