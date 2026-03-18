<?php
/**
 * /admin/modules/aide/guide-plateforme.php — Guide Plateforme v1.0
 * ════════════════════════════════════════════════════════════
 * Tutoriels par module — Structure 3R + SOP
 * 3R : Réalité, Résultat, Risque à éviter
 * SOP : Procédure pas à pas avec emplacements vidéo
 * ════════════════════════════════════════════════════════════
 */

if (!isset($pdo)) {
    require_once dirname(__DIR__, 3) . '/includes/init.php';
}

$tutoSlug    = $_GET['tuto'] ?? '';
$filterCat   = $_GET['cat'] ?? 'all';

// ── Catégories ──
$categories = [
    'commencez-ici'  => ['label' => 'Commencez ici',  'icon' => 'fa-rocket',       'color' => '#c9913b', 'desc' => 'Profil, stratégie, personas'],
    'mes-leads'      => ['label' => 'Mes leads',      'icon' => 'fa-user-plus',    'color' => '#dc2626', 'desc' => 'Leads, estimations, messagerie'],
    'mon-site'       => ['label' => 'Mon site',       'icon' => 'fa-globe',        'color' => '#6366f1', 'desc' => 'Pages, quartiers, articles'],
    'ma-visibilite'  => ['label' => 'Ma visibilité',  'icon' => 'fa-eye',          'color' => '#65a30d', 'desc' => 'SEO, pub Facebook, réseaux'],
];

// ═══════════════════════════════════════════════════════════
// VUE TUTO UNIQUE
// ═══════════════════════════════════════════════════════════
if ($tutoSlug) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM guide_tutorials WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$tutoSlug]);
        $tuto = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { $tuto = null; }

    if (!$tuto) {
        echo '<div style="text-align:center;padding:60px 20px;color:#9ca3af">
            <i class="fas fa-circle-question" style="font-size:2rem;opacity:.3;margin-bottom:12px;display:block"></i>
            <h3 style="color:#6b7280">Tutoriel non trouvé</h3>
            <p><a href="?page=guide-plateforme" style="color:#6366f1;font-weight:600">← Retour aux tutoriels</a></p>
        </div>';
        return;
    }

    $cat = $categories[$tuto['category']] ?? ['label'=>'Guide','icon'=>'fa-book','color'=>'#6366f1'];
    $steps = json_decode($tuto['sop_steps'] ?? '[]', true) ?: [];

    // Tutos connexes
    $related = [];
    try {
        $stmtR = $pdo->prepare("SELECT slug, title, icon, video_duration FROM guide_tutorials WHERE category = ? AND slug != ? AND status = 'published' ORDER BY sort_order ASC LIMIT 4");
        $stmtR->execute([$tuto['category'], $tutoSlug]);
        $related = $stmtR->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}
    ?>

<style>
.gt-tuto { font-family:'Inter',-apple-system,sans-serif; max-width:820px; margin:0 auto; padding:20px 0; }
.gt-back { display:inline-flex; align-items:center; gap:6px; color:#6b7280; font-size:12px; font-weight:600; text-decoration:none; margin-bottom:16px; transition:color .15s; }
.gt-back:hover { color:#6366f1; }
.gt-tuto-cat { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:8px; font-size:11px; font-weight:700; text-transform:uppercase; margin-bottom:10px; }
.gt-tuto-title { font-size:1.5rem; font-weight:800; color:#111827; margin:0 0 6px; line-height:1.3; }
.gt-tuto-meta { display:flex; gap:12px; font-size:12px; color:#9ca3af; margin-bottom:20px; align-items:center; }
.gt-tuto-meta i { font-size:11px; }

/* ── Video placeholder ── */
.gt-video { background:#0f172a; border-radius:14px; padding:0; margin-bottom:24px; overflow:hidden; position:relative; }
.gt-video-placeholder { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:50px 20px; color:#475569; }
.gt-video-placeholder i { font-size:40px; opacity:.3; margin-bottom:12px; }
.gt-video-placeholder p { font-size:13px; font-weight:600; }
.gt-video-placeholder span { font-size:11px; color:#334155; margin-top:4px; }
.gt-video iframe { width:100%; aspect-ratio:16/9; border:none; display:block; }

/* ── 3R Cards ── */
.gt-3r { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px; }
.gt-3r-card { border-radius:12px; padding:16px 18px; border:1px solid; }
.gt-3r-card h4 { font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:.04em; margin:0 0 8px; display:flex; align-items:center; gap:6px; }
.gt-3r-card p { font-size:12.5px; line-height:1.6; margin:0; }
.gt-3r-realite { background:#fef3c71a; border-color:#fbbf2440; }
.gt-3r-realite h4 { color:#92400e; }
.gt-3r-realite p { color:#78350f; }
.gt-3r-resultat { background:#d1fae51a; border-color:#10b98140; }
.gt-3r-resultat h4 { color:#065f46; }
.gt-3r-resultat p { color:#064e3b; }
.gt-3r-risque { background:#fee2e21a; border-color:#ef444440; }
.gt-3r-risque h4 { color:#991b1b; }
.gt-3r-risque p { color:#7f1d1d; }

/* ── SOP Steps ── */
.gt-sop { background:#fff; border:1px solid #e5e7eb; border-radius:14px; margin-bottom:24px; overflow:hidden; }
.gt-sop-head { padding:16px 20px; border-bottom:1px solid #e5e7eb; background:#f9fafb; display:flex; align-items:center; gap:8px; }
.gt-sop-head h3 { font-size:14px; font-weight:700; color:#111827; margin:0; }
.gt-sop-head i { color:#6366f1; font-size:13px; }
.gt-step { display:flex; gap:14px; padding:16px 20px; border-bottom:1px solid #f3f4f6; transition:background .12s; }
.gt-step:last-child { border-bottom:none; }
.gt-step:hover { background:#fafbff; }
.gt-step-num { width:32px; height:32px; border-radius:50%; background:#6366f1; color:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:800; flex-shrink:0; margin-top:2px; }
.gt-step-content { flex:1; min-width:0; }
.gt-step-title { font-size:14px; font-weight:700; color:#111827; margin-bottom:4px; }
.gt-step-detail { font-size:12.5px; color:#6b7280; line-height:1.6; }
.gt-step-screenshot { margin-top:10px; background:#f3f4f6; border:1px dashed #d1d5db; border-radius:8px; padding:20px; text-align:center; color:#9ca3af; font-size:11px; }

/* ── Tips ── */
.gt-tips { background:#f0f9ff; border:1px solid #bae6fd; border-radius:12px; padding:14px 18px; margin-bottom:24px; display:flex; align-items:flex-start; gap:10px; }
.gt-tips i { color:#0284c7; font-size:14px; margin-top:2px; flex-shrink:0; }
.gt-tips p { font-size:13px; color:#0c4a6e; line-height:1.6; margin:0; }

/* ── Related ── */
.gt-related { background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:18px 22px; }
.gt-related h4 { font-size:13px; font-weight:700; color:#6b7280; margin:0 0 12px; text-transform:uppercase; letter-spacing:.03em; }
.gt-related-list { display:flex; flex-direction:column; gap:6px; }
.gt-related-link { display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; text-decoration:none; color:#374151; font-size:.85rem; font-weight:500; transition:all .12s; }
.gt-related-link:hover { background:#fff; color:#6366f1; }
.gt-related-link i { font-size:11px; color:#9ca3af; width:16px; text-align:center; }
.gt-related-link .dur { margin-left:auto; font-size:10px; color:#9ca3af; }

/* ── CTA module ── */
.gt-cta { background:linear-gradient(135deg,#6366f1,#4f46e5); border-radius:12px; padding:20px 24px; display:flex; align-items:center; gap:14px; margin-bottom:24px; }
.gt-cta-text { flex:1; color:#fff; }
.gt-cta-text h4 { font-size:14px; font-weight:700; margin:0 0 4px; }
.gt-cta-text p { font-size:12px; opacity:.8; margin:0; }
.gt-cta-btn { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; background:#fff; color:#6366f1; border-radius:10px; font-size:13px; font-weight:700; text-decoration:none; transition:all .15s; flex-shrink:0; }
.gt-cta-btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.2); }

@media(max-width:768px) {
    .gt-3r { grid-template-columns:1fr; }
}
</style>

<div class="gt-tuto">
    <a href="?page=guide-plateforme&cat=<?= htmlspecialchars($tuto['category']) ?>" class="gt-back"><i class="fas fa-arrow-left"></i> <?= htmlspecialchars($cat['label']) ?></a>

    <div class="gt-tuto-cat" style="background:<?= $cat['color'] ?>15;color:<?= $cat['color'] ?>">
        <i class="fas <?= $cat['icon'] ?>"></i> <?= htmlspecialchars($cat['label']) ?>
    </div>
    <h1 class="gt-tuto-title"><?= htmlspecialchars($tuto['title']) ?></h1>
    <div class="gt-tuto-meta">
        <span><i class="fas fa-clock"></i> <?= htmlspecialchars($tuto['video_duration'] ?? '3 min') ?></span>
        <span><i class="fas fa-list-ol"></i> <?= count($steps) ?> étapes</span>
        <span><i class="fas fa-puzzle-piece"></i> Module : <?= htmlspecialchars($tuto['module_slug']) ?></span>
    </div>

    <!-- Video -->
    <div class="gt-video">
        <?php if (!empty($tuto['video_url'])): ?>
        <iframe src="<?= htmlspecialchars($tuto['video_url']) ?>" allowfullscreen></iframe>
        <?php else: ?>
        <div class="gt-video-placeholder">
            <i class="fas fa-video"></i>
            <p>Vidéo tutoriel à venir</p>
            <span>Le guide écrit ci-dessous vous explique tout en attendant</span>
        </div>
        <?php endif; ?>
    </div>

    <!-- 3R : Réalité — Résultat — Risque -->
    <div class="gt-3r">
        <div class="gt-3r-card gt-3r-realite">
            <h4><i class="fas fa-eye"></i> Réalité</h4>
            <p><?= nl2br(htmlspecialchars($tuto['realite'] ?? '')) ?></p>
        </div>
        <div class="gt-3r-card gt-3r-resultat">
            <h4><i class="fas fa-bullseye"></i> Résultat</h4>
            <p><?= nl2br(htmlspecialchars($tuto['resultat'] ?? '')) ?></p>
        </div>
        <div class="gt-3r-card gt-3r-risque">
            <h4><i class="fas fa-triangle-exclamation"></i> Risque à éviter</h4>
            <p><?= nl2br(htmlspecialchars($tuto['risque'] ?? '')) ?></p>
        </div>
    </div>

    <!-- SOP : Procédure -->
    <?php if (!empty($steps)): ?>
    <div class="gt-sop">
        <div class="gt-sop-head">
            <i class="fas fa-list-check"></i>
            <h3>Procédure pas à pas</h3>
        </div>
        <?php foreach ($steps as $i => $step): ?>
        <div class="gt-step">
            <div class="gt-step-num"><?= $i + 1 ?></div>
            <div class="gt-step-content">
                <div class="gt-step-title"><?= htmlspecialchars($step['step'] ?? '') ?></div>
                <div class="gt-step-detail"><?= htmlspecialchars($step['detail'] ?? '') ?></div>
                <?php if (!empty($step['screenshot'])): ?>
                <img src="<?= htmlspecialchars($step['screenshot']) ?>" alt="Étape <?= $i+1 ?>" style="margin-top:10px;max-width:100%;border-radius:8px;border:1px solid #e5e7eb">
                <?php else: ?>
                <div class="gt-step-screenshot"><i class="fas fa-camera" style="margin-right:4px"></i> Capture d'écran à ajouter</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tips -->
    <?php if (!empty($tuto['tips'])): ?>
    <div class="gt-tips">
        <i class="fas fa-lightbulb"></i>
        <p><?= nl2br(htmlspecialchars($tuto['tips'])) ?></p>
    </div>
    <?php endif; ?>

    <!-- CTA vers le module -->
    <div class="gt-cta">
        <div class="gt-cta-text">
            <h4>Prêt à passer à l'action ?</h4>
            <p>Ouvrez le module et suivez les étapes ci-dessus</p>
        </div>
        <a href="?page=<?= htmlspecialchars($tuto['module_slug']) ?>" class="gt-cta-btn">
            <i class="fas fa-arrow-right"></i> Ouvrir <?= htmlspecialchars($tuto['module_slug']) ?>
        </a>
    </div>

    <!-- Related -->
    <?php if (!empty($related)): ?>
    <div class="gt-related">
        <h4>Autres tutoriels — <?= htmlspecialchars($cat['label']) ?></h4>
        <div class="gt-related-list">
            <?php foreach ($related as $rel): ?>
            <a href="?page=guide-plateforme&tuto=<?= htmlspecialchars($rel['slug']) ?>" class="gt-related-link">
                <i class="fas <?= htmlspecialchars($rel['icon'] ?? 'fa-circle-question') ?>"></i>
                <?= htmlspecialchars($rel['title']) ?>
                <?php if (!empty($rel['video_duration'])): ?>
                <span class="dur"><i class="fas fa-clock"></i> <?= htmlspecialchars($rel['video_duration']) ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

    <?php
    return;
}

// ═══════════════════════════════════════════════════════════
// VUE LISTE
// ═══════════════════════════════════════════════════════════
$tutorials = [];
$counts = [];
try {
    $where = ["status = 'published'"];
    $params = [];
    if ($filterCat !== 'all' && isset($categories[$filterCat])) {
        $where[] = "category = ?";
        $params[] = $filterCat;
    }
    $whereSQL = 'WHERE ' . implode(' AND ', $where);
    $stmt = $pdo->prepare("SELECT id, category, title, slug, icon, video_url, video_duration, sort_order FROM guide_tutorials {$whereSQL} ORDER BY category ASC, sort_order ASC");
    $stmt->execute($params);
    $tutorials = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $stmtC = $pdo->query("SELECT category, COUNT(*) as cnt FROM guide_tutorials WHERE status='published' GROUP BY category");
    while ($row = $stmtC->fetch()) { $counts[$row['category']] = (int)$row['cnt']; }
} catch (Throwable $e) {
    error_log('[guide-plateforme] ' . $e->getMessage());
}
$totalTutos = array_sum($counts);

$grouped = [];
foreach ($tutorials as $t) { $grouped[$t['category']][] = $t; }
?>

<style>
.gt-wrap { font-family:'Inter',-apple-system,sans-serif; }
.gt-banner { background:#fff; border-radius:16px; padding:28px 32px; margin-bottom:22px; border:1px solid #e5e7eb; position:relative; overflow:hidden; text-align:center; }
.gt-banner::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,#c9913b,#6366f1,#dc2626); opacity:.75; }
.gt-banner h2 { font-size:1.4rem; font-weight:800; color:#111827; margin:0 0 6px; display:flex; align-items:center; justify-content:center; gap:10px; }
.gt-banner h2 i { color:#c9913b; }
.gt-banner p { color:#6b7280; font-size:.85rem; margin:0 0 4px; }
.gt-banner .gt-count { font-size:.75rem; color:#9ca3af; }

.gt-cats { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; margin-bottom:28px; }
.gt-cat { display:flex; align-items:center; gap:12px; padding:14px 16px; background:#fff; border:1px solid #e5e7eb; border-radius:12px; text-decoration:none; color:inherit; transition:all .15s; }
.gt-cat:hover { border-color:var(--gc-c,#6366f1); box-shadow:0 2px 12px rgba(0,0,0,.06); transform:translateY(-1px); }
.gt-cat.active { border-color:var(--gc-c,#6366f1); background:var(--gc-bg,#f0f4ff); }
.gt-cat-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.gt-cat-label { font-size:13px; font-weight:700; color:#111827; }
.gt-cat-desc { font-size:11px; color:#9ca3af; margin-top:1px; }
.gt-cat-count { margin-left:auto; font-size:11px; font-weight:700; padding:2px 8px; border-radius:8px; background:#f3f4f6; color:#9ca3af; flex-shrink:0; }

.gt-section { margin-bottom:24px; }
.gt-section-head h3 { font-size:.95rem; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:8px; margin-bottom:12px; }
.gt-section-head .count { font-size:.65rem; font-weight:600; padding:2px 8px; border-radius:8px; background:#f3f4f6; color:#9ca3af; }

.gt-tutos { display:flex; flex-direction:column; gap:8px; }
.gt-tuto-card { display:flex; align-items:center; gap:14px; padding:14px 18px; background:#fff; border:1px solid #e5e7eb; border-radius:12px; text-decoration:none; color:inherit; transition:all .15s; }
.gt-tuto-card:hover { border-color:#6366f1; box-shadow:0 2px 12px rgba(99,102,241,.06); transform:translateY(-1px); }
.gt-tuto-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0; }
.gt-tuto-info { flex:1; min-width:0; }
.gt-tuto-name { font-size:.88rem; font-weight:700; color:#111827; margin-bottom:2px; }
.gt-tuto-badges { display:flex; gap:6px; align-items:center; }
.gt-tuto-dur { font-size:10px; color:#9ca3af; padding:2px 8px; background:#f3f4f6; border-radius:6px; display:flex; align-items:center; gap:4px; }
.gt-tuto-vid { font-size:10px; color:#10b981; padding:2px 8px; background:#d1fae5; border-radius:6px; font-weight:700; }
.gt-tuto-arrow { color:#d1d5db; font-size:10px; flex-shrink:0; transition:all .13s; }
.gt-tuto-card:hover .gt-tuto-arrow { color:#6366f1; transform:translateX(2px); }

.gt-empty { text-align:center; padding:50px 20px; color:#9ca3af; }
.gt-empty i { font-size:2rem; opacity:.2; margin-bottom:10px; display:block; }

@media(max-width:640px) { .gt-cats { grid-template-columns:1fr; } }
</style>

<div class="gt-wrap">

<div class="gt-banner">
    <h2><i class="fas fa-graduation-cap"></i> Guide plateforme</h2>
    <p>Apprenez à utiliser chaque module avec nos tutoriels pas à pas</p>
    <span class="gt-count"><?= $totalTutos ?> tutoriels disponibles</span>
</div>

<div class="gt-cats">
    <?php foreach ($categories as $catKey => $cat):
        $isActive = ($filterCat === $catKey);
        $cnt = $counts[$catKey] ?? 0;
        $url = $isActive ? '?page=guide-plateforme' : '?page=guide-plateforme&cat=' . $catKey;
    ?>
    <a href="<?= $url ?>" class="gt-cat<?= $isActive ? ' active' : '' ?>" style="--gc-c:<?= $cat['color'] ?>;--gc-bg:<?= $cat['color'] ?>0a">
        <div class="gt-cat-icon" style="background:<?= $cat['color'] ?>15;color:<?= $cat['color'] ?>">
            <i class="fas <?= $cat['icon'] ?>"></i>
        </div>
        <div>
            <div class="gt-cat-label"><?= $cat['label'] ?></div>
            <div class="gt-cat-desc"><?= $cat['desc'] ?></div>
        </div>
        <span class="gt-cat-count"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($tutorials)): ?>
<div class="gt-empty">
    <i class="fas fa-book-open"></i>
    <h3 style="color:#6b7280;font-size:1rem;font-weight:600">Aucun tutoriel</h3>
    <p>Les tutoriels seront bientôt disponibles.</p>
</div>
<?php else: ?>

<?php foreach ($categories as $catKey => $cat):
    if (!isset($grouped[$catKey])) continue;
    if ($filterCat !== 'all' && $filterCat !== $catKey) continue;
?>
<div class="gt-section">
    <div class="gt-section-head">
        <h3>
            <i class="fas <?= $cat['icon'] ?>" style="color:<?= $cat['color'] ?>;font-size:14px"></i>
            <?= $cat['label'] ?>
            <span class="count"><?= count($grouped[$catKey]) ?></span>
        </h3>
    </div>
    <div class="gt-tutos">
        <?php foreach ($grouped[$catKey] as $t): ?>
        <a href="?page=guide-plateforme&tuto=<?= htmlspecialchars($t['slug']) ?>" class="gt-tuto-card">
            <div class="gt-tuto-icon" style="background:<?= $cat['color'] ?>12;color:<?= $cat['color'] ?>">
                <i class="fas <?= htmlspecialchars($t['icon'] ?? 'fa-circle-question') ?>"></i>
            </div>
            <div class="gt-tuto-info">
                <div class="gt-tuto-name"><?= htmlspecialchars($t['title']) ?></div>
                <div class="gt-tuto-badges">
                    <?php if (!empty($t['video_duration'])): ?>
                    <span class="gt-tuto-dur"><i class="fas fa-clock"></i> <?= htmlspecialchars($t['video_duration']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($t['video_url'])): ?>
                    <span class="gt-tuto-vid"><i class="fas fa-video"></i> Vidéo</span>
                    <?php endif; ?>
                </div>
            </div>
            <i class="fas fa-chevron-right gt-tuto-arrow"></i>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

</div>