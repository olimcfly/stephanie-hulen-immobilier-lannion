<?php
// ════════════════════════════════════════════════════════════
//  admin/layout/sidebar.php — IMMO LOCAL+ v9.0
//  REFONTE : Parcours d'accompagnement, source unique
//  Plus de navigation hardcodée — tout vient de routes-nav.php
// ════════════════════════════════════════════════════════════

$activeModule = $activeModule ?? ($_GET['page'] ?? 'dashboard');

// ── Source unique depuis routes-nav.php ─────────────────────
$routesNavPath = dirname(__DIR__) . '/config/routes-nav.php';
if (!function_exists('getNavGroups') && file_exists($routesNavPath)) {
    require_once $routesNavPath;
}
$navGroups = function_exists('getNavGroups') ? getNavGroups() : [];

// ── Infos conseiller ────────────────────────────────────────
$advisorName   = 'Mon espace';
$advisorAvatar = '';
$advisorCity   = '';
try {
    $r = $pdo->query(
        "SELECT field_key, field_value FROM advisor_context
         WHERE field_key IN ('advisor_name','advisor_photo','advisor_city') LIMIT 3"
    )->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!empty($r['advisor_name']))  $advisorName   = $r['advisor_name'];
    if (!empty($r['advisor_photo'])) $advisorAvatar = $r['advisor_photo'];
    if (!empty($r['advisor_city']))  $advisorCity   = $r['advisor_city'];
} catch (Exception $e) {}

// ── Groupe actif ────────────────────────────────────────────
$autoOpenGroup = function_exists('getActiveGroup')
    ? getActiveGroup($activeModule)
    : '';
?>

<!-- Link CSS externe -->
<link rel="stylesheet" href="/admin/assets/css/sidebar.css">

<!-- Sidebar overlay (mobile) -->
<div class="sb-overlay" id="sbOverlay" onclick="sbClose()"></div>

<!-- Sidebar principal -->
<aside class="sb" id="sidebar">

    <div class="sb-logo">
        <a href="?page=dashboard" class="sb-logo-inner">
            <div class="sb-logo-icon"><i class="fas fa-house-chimney"></i></div>
            <div>
                <div class="sb-logo-name">IMMO LOCAL+</div>
                <div class="sb-logo-ver">v<?= defined('IMMO_VERSION') ? IMMO_VERSION : '9.0' ?></div>
            </div>
        </a>
        <button class="sb-close-btn" onclick="sbClose()" aria-label="Fermer le menu">
            <i class="fas fa-xmark"></i>
        </button>
    </div>

    <div class="sb-search">
        <div class="sb-search-inner">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" class="sb-search-input" id="sbSearch"
                   placeholder="Rechercher…" autocomplete="off">
        </div>
    </div>

    <a href="?page=dashboard"
       class="sb-dashboard<?= $activeModule === 'dashboard' ? ' active' : '' ?>">
        <i class="fas fa-grid-2"></i>
        <span>Tableau de bord</span>
    </a>

    <nav class="sb-nav" id="sidebarNav">
    <?php foreach ($navGroups as $grp):
        $isOpen   = ($grp['id'] === $autoOpenGroup);
        $srchData = strtolower($grp['label'] . ' ' . implode(' ', array_map(fn($i) => $i['label'] . ' ' . $i['slug'], $grp['items'])));
    ?>
        <?php if (!empty($grp['sep'])): ?><div class="sb-sep" aria-hidden="true"></div><?php endif; ?>

        <div class="sb-group<?= $isOpen ? ' open' : '' ?>"
             id="<?= $grp['id'] ?>"
             data-search="<?= htmlspecialchars($srchData) ?>">

            <button class="sb-group-hd<?= $isOpen ? ' active' : '' ?>"
                    onclick="sbToggle('<?= $grp['id'] ?>')"
                    aria-expanded="<?= $isOpen ? 'true' : 'false' ?>">
                <div class="sb-group-icon"
                     style="background:<?= $grp['color'] ?>1e;color:<?= $grp['color'] ?>">
                    <i class="fas <?= $grp['icon'] ?>"></i>
                </div>
                <span class="sb-group-label"><?= htmlspecialchars($grp['label']) ?></span>
                <div class="sb-group-dot" style="background:<?= $grp['color'] ?>"></div>
                <i class="fas fa-chevron-right sb-group-chv"></i>
            </button>

            <div class="sb-children">
                <?php foreach ($grp['items'] as $item):
                    $active   = ($activeModule === $item['slug']);
                    $icoClass = str_starts_with($item['icon'], 'fab ') ? $item['icon'] : 'fas '.$item['icon'];
                    $bdgCls   = strtolower($item['badge'] ?? '');
                ?>
                <a href="?page=<?= htmlspecialchars($item['slug']) ?>"
                   class="sb-item<?= $active ? ' active' : '' ?>"
                   data-label="<?= htmlspecialchars(strtolower($item['label'].' '.$item['slug'])) ?>"
                   <?= $active ? 'style="--sb-c:'.$grp['color'].'"' : '' ?>>
                    <i class="<?= $icoClass ?>"></i>
                    <span><?= htmlspecialchars($item['label']) ?></span>
                    <?php if (!empty($item['badge'])): ?>
                    <span class="sb-badge <?= $bdgCls ?>"><?= $item['badge'] ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </nav>

    <div class="sb-footer">
        <a href="?page=advisor-context" class="sb-user">
            <div class="sb-user-av">
                <?php if ($advisorAvatar): ?>
                <img src="<?= htmlspecialchars($advisorAvatar) ?>" alt="">
                <?php else: ?>
                <?= mb_strtoupper(mb_substr($advisorName,0,1,'UTF-8')) ?>
                <?php endif; ?>
            </div>
            <div style="min-width:0;flex:1">
                <div class="sb-user-name"><?= htmlspecialchars($advisorName) ?></div>
                <div class="sb-user-sub"><?= $advisorCity ? htmlspecialchars($advisorCity) : 'Administrateur' ?></div>
            </div>
            <i class="fas fa-gear sb-user-gear"></i>
        </a>
    </div>

</aside>

<script>
function sbToggle(id) {
    const grp = document.getElementById(id);
    if (!grp) return;
    const opening = !grp.classList.contains('open');
    document.querySelectorAll('.sb-group.open').forEach(g => {
        if (g.id === id) return;
        g.classList.remove('open');
        const b = g.querySelector('.sb-group-hd');
        if (b) { b.classList.remove('active'); b.setAttribute('aria-expanded','false'); }
    });
    grp.classList.toggle('open', opening);
    const btn = grp.querySelector('.sb-group-hd');
    if (btn) { btn.classList.toggle('active', opening); btn.setAttribute('aria-expanded', String(opening)); }
}

function sbOpen() {
    document.getElementById('sidebar')?.classList.add('open');
    document.body.classList.add('sb-open');
    document.body.style.overflow = 'hidden';
}

function sbClose() {
    document.getElementById('sidebar')?.classList.remove('open');
    document.body.classList.remove('sb-open');
    document.body.style.overflow = '';
}

(function () {
    const btn = document.getElementById('tbMenuBtn');
    if (btn) btn.onclick = function () {
        const sb = document.getElementById('sidebar');
        if (!sb) return;
        sb.classList.contains('open') ? sbClose() : sbOpen();
    };
})();

document.querySelectorAll('.sb-item, .sb-dashboard').forEach(function(link) {
    link.addEventListener('click', function() { if (window.innerWidth < 768) sbClose(); });
});

(function () {
    const inp = document.getElementById('sbSearch');
    if (!inp) return;
    const init = <?= json_encode($autoOpenGroup) ?>;
    inp.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('.sb-group').forEach(grp => {
            if (!q) {
                grp.classList.remove('sb-hidden');
                grp.querySelectorAll('.sb-item').forEach(i => i.classList.remove('sb-no-match'));
                const open = grp.id === init;
                grp.classList.toggle('open', open);
                const b = grp.querySelector('.sb-group-hd');
                if (b) { b.classList.toggle('active', open); b.setAttribute('aria-expanded', String(open)); }
                return;
            }
            let hit = false;
            grp.querySelectorAll('.sb-item').forEach(it => {
                const lbl = it.getAttribute('data-label') || it.textContent.toLowerCase();
                const m = lbl.includes(q);
                it.classList.toggle('sb-no-match', !m);
                if (m) hit = true;
            });
            if ((grp.getAttribute('data-search')||'').includes(q)) hit = true;
            grp.classList.toggle('sb-hidden', !hit);
            if (hit) {
                grp.classList.add('open');
                const b = grp.querySelector('.sb-group-hd');
                if (b) { b.classList.add('active'); b.setAttribute('aria-expanded','true'); }
            }
        });
    });
})();
</script>