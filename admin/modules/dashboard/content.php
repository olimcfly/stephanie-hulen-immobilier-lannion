<?php
/**
 * ════════════════════════════════════════════════════════════
 * admin/dashboard/content.php — Contenu du dashboard
 * ════════════════════════════════════════════════════════════
 * 
 * Fichier appelé par dashboard.php dans une sortie bufferisée
 * Variables disponibles :
 * - $dashStats       : Array with all stats
 * - $firstName       : Prénom du conseiller
 * - $advisorName     : Nom complet
 * - $advisorCity     : Ville
 */
?>

<style>
.dash { --r:14px; --r-sm:10px; --gap:16px; max-width:1100px; margin:0 auto; }

/* ── Hello ── */
.dash-hello { background:var(--surface); border:1px solid var(--border); border-radius:var(--r); padding:16px 20px; margin-bottom:var(--gap); display:flex; align-items:center; gap:12px; }
.dash-hello-av { width:42px; height:42px; border-radius:12px; background:linear-gradient(135deg,#6366f1,#4f46e5); display:flex; align-items:center; justify-content:center; font-size:17px; color:#fff; flex-shrink:0; }
.dash-hello h1 { font-size:16px; font-weight:800; margin-bottom:1px; }
.dash-hello p { font-size:11.5px; color:var(--text-3); }

/* ── Alertes leads ── */
.dash-alerts { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:var(--gap); }
.dash-alert { background:var(--surface); border:1px solid var(--border); border-radius:var(--r-sm); padding:16px 18px; display:flex; align-items:center; gap:14px; text-decoration:none; color:inherit; transition:all .15s; position:relative; }
.dash-alert:hover { border-color:var(--da-c); box-shadow:0 4px 18px rgba(0,0,0,.08); transform:translateY(-2px); }
.dash-alert-icon { width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; color:#fff; flex-shrink:0; }
.dash-alert-val { font-size:28px; font-weight:900; line-height:1; }
.dash-alert-label { font-size:11px; color:var(--text-3); margin-top:2px; font-weight:500; }
.dash-alert-sub { font-size:10px; margin-top:3px; font-weight:700; }
.dash-alert-badge { position:absolute; top:8px; right:10px; font-size:9px; font-weight:800; padding:2px 8px; border-radius:20px; color:#fff; }

/* ── Two columns ── */
.dash-cols { display:grid; grid-template-columns:1fr 1fr; gap:var(--gap); margin-bottom:var(--gap); }

/* ── Section wrapper ── */
.dash-section { background:var(--surface); border:1px solid var(--border); border-radius:var(--r); overflow:hidden; }
.dash-sec-hd { display:flex; align-items:center; gap:9px; padding:12px 16px; border-bottom:1px solid var(--border); background:var(--surface-2); }
.dash-sec-hd i { color:var(--accent); font-size:12px; }
.dash-sec-hd h2 { font-size:12.5px; font-weight:700; }
.dash-sec-hd .hd-meta { font-size:11px; color:var(--text-3); margin-left:auto; }
.dash-sec-body { padding:0; }

/* ── Lead row ── */
.dash-lead { display:flex; align-items:center; gap:10px; padding:10px 16px; border-bottom:1px solid var(--border); text-decoration:none; color:inherit; transition:background .12s; }
.dash-lead:last-child { border-bottom:none; }
.dash-lead:hover { background:var(--surface-2); }
.dash-lead-dot { width:8px; height:8px; border-radius:50%; background:#dc2626; flex-shrink:0; }
.dash-lead-name { font-size:12.5px; font-weight:600; flex:1; }
.dash-lead-source { font-size:10px; color:var(--text-3); padding:2px 8px; background:var(--surface-2); border-radius:6px; }
.dash-lead-time { font-size:10px; color:var(--text-3); }

/* ── RDV row ── */
.dash-rdv { display:flex; align-items:center; gap:10px; padding:10px 16px; border-bottom:1px solid var(--border); text-decoration:none; color:inherit; transition:background .12s; }
.dash-rdv:last-child { border-bottom:none; }
.dash-rdv:hover { background:var(--surface-2); }
.dash-rdv-time { font-size:11px; font-weight:800; color:#6366f1; min-width:50px; }
.dash-rdv-title { font-size:12.5px; font-weight:600; flex:1; }

/* ── Stats bar (4 mini) ── */
.dash-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:var(--gap); }
.dash-stat { background:var(--surface); border:1px solid var(--border); border-radius:var(--r-sm); padding:14px 16px; text-align:center; text-decoration:none; color:inherit; transition:all .15s; }
.dash-stat:hover { border-color:var(--accent); transform:translateY(-1px); }
.dash-stat-val { font-size:22px; font-weight:900; }
.dash-stat-label { font-size:10px; color:var(--text-3); text-transform:uppercase; font-weight:600; letter-spacing:.03em; margin-top:2px; }

/* ── Quick actions ── */
.dash-quick { display:grid; grid-template-columns:repeat(4,1fr); gap:10px; }
.dash-qk { background:var(--surface); border:1px solid var(--border); border-radius:var(--r-sm); padding:14px; text-decoration:none; color:inherit; display:flex; align-items:center; gap:10px; transition:all .13s; }
.dash-qk:hover { border-color:var(--dq-c,var(--accent)); box-shadow:0 2px 12px rgba(0,0,0,.06); transform:translateY(-1px); }
.dash-qk-icon { width:36px; height:36px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:14px; flex-shrink:0; }
.dash-qk-label { font-size:12px; font-weight:700; }
.dash-qk-sub { font-size:10px; color:var(--text-3); margin-top:1px; }

/* ── Empty state ── */
.dash-empty { padding:20px 16px; text-align:center; color:var(--text-3); font-size:12px; }
.dash-empty i { font-size:20px; opacity:.3; margin-bottom:6px; display:block; }

/* ── Animations ── */
.anim { animation: fadeUp .3s ease both; }
.d1 { animation-delay:.05s; } .d2 { animation-delay:.1s; } .d3 { animation-delay:.15s; } .d4 { animation-delay:.2s; }
@keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:none; } }

/* ── Responsive ── */
@media(max-width:768px) {
    .dash-alerts { grid-template-columns:1fr; }
    .dash-cols { grid-template-columns:1fr; }
    .dash-stats { grid-template-columns:repeat(2,1fr); }
    .dash-quick { grid-template-columns:repeat(2,1fr); }
}

@media(max-width:480px) {
    .dash { --gap:8px; }
    .dash > * { margin-bottom:8px; }
    .dash-hello, .dash-section, .dash-alert, .dash-stat, .dash-qk { border-radius:0; border-left:none; border-right:none; }
    .dash-alerts { gap:0; }
    .dash-alert { border-bottom:none; }
    .dash-alert:last-child { border-bottom:1px solid var(--border); }
    .dash-stats { grid-template-columns:1fr 1fr; gap:0; }
    .dash-stat { border-radius:0; border:none; border-bottom:1px solid var(--border); border-right:1px solid var(--border); }
    .dash-stat:nth-child(2n) { border-right:none; }
    .dash-quick { grid-template-columns:1fr; gap:0; }
    .dash-qk { border-radius:0; border:none; border-bottom:1px solid var(--border); }
}
</style>

<div class="dash">

    <!-- ── Hello ── -->
    <div class="dash-hello anim">
        <div class="dash-hello-av"><i class="fas fa-house-chimney"></i></div>
        <div>
            <h1>Bonjour <?= htmlspecialchars($firstName) ?></h1>
            <p><?= date('l d F Y', time()) ?></p>
        </div>
    </div>

    <!-- ── ALERTES (Leads, Estimations, Messages) ── -->
    <div class="dash-alerts anim d1">
        <a href="?page=leads" class="dash-alert" style="--da-c:#dc2626">
            <div class="dash-alert-icon" style="background:linear-gradient(135deg,#dc2626,#dc2626bb)"><i class="fas fa-user-plus"></i></div>
            <div>
                <div class="dash-alert-val"><?= $dashStats['new_leads'] ?? 0 ?></div>
                <div class="dash-alert-label">Nouveaux leads</div>
                <div class="dash-alert-sub" style="color:#dc2626">cette semaine</div>
            </div>
            <?php if (($dashStats['new_leads'] ?? 0) > 0): ?>
            <span class="dash-alert-badge" style="background:#dc2626">+<?= $dashStats['new_leads'] ?></span>
            <?php endif; ?>
        </a>

        <a href="?page=estimation" class="dash-alert" style="--da-c:#ea580c">
            <div class="dash-alert-icon" style="background:linear-gradient(135deg,#ea580c,#ea580cbb)"><i class="fas fa-calculator"></i></div>
            <div>
                <div class="dash-alert-val"><?= $dashStats['estimations_new'] ?? 0 ?></div>
                <div class="dash-alert-label">Estimations</div>
                <div class="dash-alert-sub" style="color:#ea580c">à traiter</div>
            </div>
            <?php if (($dashStats['estimations_new'] ?? 0) > 0): ?>
            <span class="dash-alert-badge" style="background:#ea580c"><?= $dashStats['estimations_new'] ?></span>
            <?php endif; ?>
        </a>

        <a href="?page=messenger" class="dash-alert" style="--da-c:#7c3aed">
            <div class="dash-alert-icon" style="background:linear-gradient(135deg,#7c3aed,#7c3aedbb)"><i class="fas fa-comments"></i></div>
            <div>
                <div class="dash-alert-val"><?= $dashStats['unread_msgs'] ?? 0 ?></div>
                <div class="dash-alert-label">Messages</div>
                <div class="dash-alert-sub" style="color:#7c3aed">non lus</div>
            </div>
            <?php if (($dashStats['unread_msgs'] ?? 0) > 0): ?>
            <span class="dash-alert-badge" style="background:#7c3aed"><?= $dashStats['unread_msgs'] ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- ── DEUX COLONNES : Derniers leads + RDV du jour ── -->
    <div class="dash-cols anim d2">
        <div class="dash-section">
            <div class="dash-sec-hd">
                <i class="fas fa-user-plus"></i>
                <h2>Derniers leads</h2>
                <a href="?page=leads" class="hd-meta" style="color:var(--accent);text-decoration:none;font-weight:600">Tout voir →</a>
            </div>
            <div class="dash-sec-body">
                <?php if (empty($dashStats['recent_leads'])): ?>
                <div class="dash-empty"><i class="fas fa-inbox"></i> Aucun lead récent</div>
                <?php else: ?>
                <?php foreach ($dashStats['recent_leads'] as $lead): ?>
                <a href="?page=leads&id=<?= $lead['id'] ?>" class="dash-lead">
                    <div class="dash-lead-dot"></div>
                    <div class="dash-lead-name"><?= htmlspecialchars($lead['label']) ?></div>
                    <?php if (!empty($lead['source'])): ?>
                    <span class="dash-lead-source"><?= htmlspecialchars($lead['source']) ?></span>
                    <?php endif; ?>
                    <span class="dash-lead-time"><?= date('d/m H:i', strtotime($lead['created_at'])) ?></span>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-section">
            <div class="dash-sec-hd">
                <i class="fas fa-calendar-check"></i>
                <h2>Aujourd'hui</h2>
                <a href="?page=rdv" class="hd-meta" style="color:var(--accent);text-decoration:none;font-weight:600">Agenda →</a>
            </div>
            <div class="dash-sec-body">
                <?php if (empty($dashStats['today_rdv'])): ?>
                <div class="dash-empty"><i class="fas fa-calendar"></i> Aucun RDV aujourd'hui</div>
                <?php else: ?>
                <?php foreach ($dashStats['today_rdv'] as $rdv): ?>
                <a href="?page=rdv&id=<?= $rdv['id'] ?>" class="dash-rdv">
                    <span class="dash-rdv-time"><?= $rdv['rdv_time'] ? date('H:i', strtotime($rdv['rdv_time'])) : '--:--' ?></span>
                    <span class="dash-rdv-title"><?= htmlspecialchars($rdv['title'] ?? 'RDV') ?></span>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── STATS : 4 chiffres clés ── -->
    <div class="dash-stats anim d3">
        <a href="?page=leads" class="dash-stat">
            <div class="dash-stat-val" style="color:#dc2626"><?= $dashStats['leads'] ?? 0 ?></div>
            <div class="dash-stat-label">Leads total</div>
        </a>
        <a href="?page=properties" class="dash-stat">
            <div class="dash-stat-val" style="color:#c9913b"><?= $dashStats['properties'] ?? 0 ?></div>
            <div class="dash-stat-label">Biens actifs</div>
        </a>
        <a href="?page=pages" class="dash-stat">
            <div class="dash-stat-val" style="color:#6366f1"><?= $dashStats['pages'] ?? 0 ?></div>
            <div class="dash-stat-label">Pages publiées</div>
        </a>
        <a href="?page=rdv" class="dash-stat">
            <div class="dash-stat-val" style="color:#10b981"><?= $dashStats['rdv'] ?? 0 ?></div>
            <div class="dash-stat-label">RDV à venir</div>
        </a>
    </div>

    <!-- ── ACCÈS RAPIDES : 4 actions principales ── -->
    <div class="dash-quick anim d4">
        <a href="?page=pages&action=create" class="dash-qk" style="--dq-c:#6366f1">
            <div class="dash-qk-icon" style="background:#6366f11e;color:#6366f1"><i class="fas fa-plus"></i></div>
            <div><div class="dash-qk-label">Nouvelle page</div><div class="dash-qk-sub">Créer du contenu</div></div>
        </a>
        <a href="?page=ai" class="dash-qk" style="--dq-c:#8b5cf6">
            <div class="dash-qk-icon" style="background:#8b5cf61e;color:#8b5cf6"><i class="fas fa-robot"></i></div>
            <div><div class="dash-qk-label">Assistant IA</div><div class="dash-qk-sub">Générer du contenu</div></div>
        </a>
        <a href="?page=captures" class="dash-qk" style="--dq-c:#dc2626">
            <div class="dash-qk-icon" style="background:#dc26261e;color:#dc2626"><i class="fas fa-bolt"></i></div>
            <div><div class="dash-qk-label">Page de capture</div><div class="dash-qk-sub">Générer des leads</div></div>
        </a>
        <a href="?page=ancre" class="dash-qk" style="--dq-c:#c9913b">
            <div class="dash-qk-icon" style="background:#c9913b1e;color:#c9913b"><i class="fas fa-anchor"></i></div>
            <div><div class="dash-qk-label">Ma stratégie</div><div class="dash-qk-sub">Méthode ANCRE</div></div>
        </a>
    </div>

</div>