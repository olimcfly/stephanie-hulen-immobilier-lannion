<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE CAPTURES — Statistiques  v1.0
 *  /admin/modules/content/captures/stats.php
 *
 *  Accès : ?page=captures&action=stats&id=X
 *
 *  Données :
 *    captures          → totaux cumulés (vues, conversions, taux)
 *    captures_stats    → historique journalier 30/90 jours
 *
 *  Graphiques (Chart.js CDN) :
 *    - Courbe vues + conversions sur 30 jours
 *    - Barre taux de conversion par jour
 *    - Donut répartition (top vs reste)
 *
 *  KPIs :
 *    Total vues, Total conversions, Taux moyen,
 *    Meilleur jour, Dernière conversion, Jours actif
 * ══════════════════════════════════════════════════════════════
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER'))
        require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/includes/init.php';
}
if (isset($db)  && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db))  $db  = $pdo;

$pageId = (int)($_GET['id'] ?? 0);
if ($pageId <= 0) { header('Location: ?page=captures'); exit; }

// ─── Charger la capture ───
$capture = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM captures WHERE id = ?");
    $stmt->execute([$pageId]);
    $capture = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
if (!$capture) { header('Location: ?page=captures&msg=notfound'); exit; }

// ─── Période sélectionnée ───
$period = in_array($_GET['period'] ?? '30', ['7','30','90']) ? (int)$_GET['period'] : 30;

// ─── Stats journalières (captures_stats) ───
$dailyStats   = [];
$statsAvail   = false;
$totalVues30  = 0;
$totalConv30  = 0;

try {
    $pdo->query("SELECT 1 FROM captures_stats LIMIT 1");
    $statsAvail = true;
} catch (PDOException $e) {}

if ($statsAvail) {
    try {
        $rows = $pdo->prepare("
            SELECT
                date,
                COALESCE(SUM(vues), 0)          AS vues,
                COALESCE(SUM(conversions), 0)   AS conversions,
                COALESCE(MAX(taux_conversion),0) AS taux
            FROM captures_stats
            WHERE capture_id = ?
              AND date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY date
            ORDER BY date ASC
        ");
        $rows->execute([$pageId, $period]);
        $rawStats = $rows->fetchAll(PDO::FETCH_ASSOC);

        // Remplir tous les jours (même sans données)
        $dateMap = [];
        foreach ($rawStats as $r) $dateMap[$r['date']] = $r;

        for ($i = $period - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $dailyStats[] = [
                'date'         => $d,
                'label'        => date('d/m', strtotime($d)),
                'vues'         => (int)($dateMap[$d]['vues']         ?? 0),
                'conversions'  => (int)($dateMap[$d]['conversions']  ?? 0),
                'taux'         => (float)($dateMap[$d]['taux']       ?? 0),
            ];
        }

        $totalVues30 = array_sum(array_column($dailyStats, 'vues'));
        $totalConv30 = array_sum(array_column($dailyStats, 'conversions'));

    } catch (PDOException $e) {}
}

// ─── Stats cumulées de la capture ───
$totalVues    = (int)($capture['vues']             ?? 0);
$totalConv    = (int)($capture['conversions']       ?? 0);
$tauxMoyen    = (float)($capture['taux_conversion'] ?? 0);
$lastConvAt   = $capture['last_conversion_at']      ?? null;
$createdAt    = $capture['created_at']              ?? null;

// Jours depuis création
$joursActif = 0;
if ($createdAt) {
    $diff = (new DateTime())->diff(new DateTime($createdAt));
    $joursActif = max(1, $diff->days);
}

// Meilleur jour
$bestDay = null;
if (!empty($dailyStats)) {
    usort($dailyStats, fn($a,$b) => $b['conversions'] <=> $a['conversions']);
    $bestDay = $dailyStats[0]['conversions'] > 0 ? $dailyStats[0] : null;
    // Rétablir ordre chronologique
    usort($dailyStats, fn($a,$b) => strcmp($a['date'], $b['date']));
}

// Vues/jour moyen
$vuesParJour = $joursActif > 0 ? round($totalVues / $joursActif, 1) : 0;

// ─── Données pour graphiques JSON ───
$chartLabels    = json_encode(array_column($dailyStats, 'label'));
$chartVues      = json_encode(array_column($dailyStats, 'vues'));
$chartConv      = json_encode(array_column($dailyStats, 'conversions'));
$chartTaux      = json_encode(array_column($dailyStats, 'taux'));

// ─── Helpers ───
$typeLabels = [
    'estimation' => ['label'=>'Estimation',        'color'=>'#3b82f6','icon'=>'fa-calculator'],
    'contact'    => ['label'=>'Contact',            'color'=>'#10b981','icon'=>'fa-envelope'],
    'newsletter' => ['label'=>'Newsletter',         'color'=>'#ec4899','icon'=>'fa-newspaper'],
    'guide'      => ['label'=>'Guide / Lead Magnet','color'=>'#8b5cf6','icon'=>'fa-book-open'],
];
$typeInfo = $typeLabels[$capture['type'] ?? 'contact'] ?? $typeLabels['contact'];

function statsPeriodUrl(int $p): string {
    $q = $_GET; $q['period'] = $p;
    return '?' . http_build_query($q);
}
?>
<style>
/* ══ CAPTURES STATS — Design unifié ÉCOSYSTÈME IMMO LOCAL+ ══ */
.cs-wrap { font-family: var(--font); }

/* Breadcrumb */
.cs-bc { display:flex; align-items:center; gap:8px; font-size:.78rem; color:var(--text-3); margin-bottom:20px; }
.cs-bc a { color:var(--text-3); text-decoration:none; transition:color .15s; }
.cs-bc a:hover { color:#ef4444; }
.cs-bc i.sep { font-size:.6rem; }

/* Banner capture */
.cs-banner {
    background: var(--surface); border-radius: var(--radius-xl);
    border: 1px solid var(--border); padding: 22px 26px; margin-bottom: 20px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 16px; flex-wrap: wrap; position: relative; overflow: hidden;
}
.cs-banner::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,#ef4444,#f97316,#f59e0b,#10b981,#3b82f6,#8b5cf6);
}
.cs-banner-l { display:flex; align-items:center; gap:14px; }
.cs-type-ico {
    width:44px; height:44px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:18px; color:#fff; flex-shrink:0;
}
.cs-banner-name { font-family:var(--font-display); font-size:1.15rem; font-weight:800; color:var(--text); letter-spacing:-.02em; margin:0 0 3px; }
.cs-banner-slug { font-family:var(--mono); font-size:.72rem; color:var(--text-3); display:flex; align-items:center; gap:5px; }
.cs-status { padding:3px 10px; border-radius:12px; font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; }
.cs-status.active   { background:#dcfce7; color:#166534; }
.cs-status.inactive { background:var(--surface-2); color:var(--text-3); }
.cs-status.archived { background:#fef9c3; color:#a16207; }
.cs-banner-r { display:flex; gap:8px; align-items:center; }
.cs-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:var(--radius); font-size:.8rem; font-weight:600; cursor:pointer; border:none; font-family:var(--font); text-decoration:none; transition:all .15s; }
.cs-btn-outline { background:var(--surface); color:var(--text-2); border:1px solid var(--border); }
.cs-btn-outline:hover { border-color:var(--border-h); background:var(--surface-2); }
.cs-btn-primary { background:#ef4444; color:#fff; }
.cs-btn-primary:hover { background:#dc2626; color:#fff; }

/* Period selector */
.cs-period-bar { display:flex; align-items:center; justify-content:space-between; margin-bottom:18px; flex-wrap:wrap; gap:10px; }
.cs-period-bar h2 { font-family:var(--font-display); font-size:1rem; font-weight:700; color:var(--text); margin:0; display:flex; align-items:center; gap:8px; }
.cs-period-bar h2 i { color:#ef4444; }
.cs-period-tabs { display:flex; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:3px; gap:3px; }
.cs-ptab { padding:6px 14px; border:none; background:transparent; color:var(--text-3); font-size:.78rem; font-weight:600; border-radius:6px; cursor:pointer; font-family:var(--font); text-decoration:none; transition:all .15s; }
.cs-ptab:hover { color:var(--text); background:var(--surface-2); }
.cs-ptab.active { background:#ef4444; color:#fff; box-shadow:0 1px 4px rgba(239,68,68,.25); }

/* KPI grid */
.cs-kpis { display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:20px; }
.cs-kpi {
    background:var(--surface); border:1px solid var(--border);
    border-radius:var(--radius-lg); padding:16px 14px;
    position:relative; overflow:hidden; transition:all .2s var(--ease);
}
.cs-kpi:hover { border-color:var(--border-h); box-shadow:var(--shadow-xs); transform:translateY(-1px); }
.cs-kpi::after {
    content:''; position:absolute; bottom:0; left:0; right:0; height:2px;
}
.cs-kpi.blue::after   { background:#3b82f6; }
.cs-kpi.red::after    { background:#ef4444; }
.cs-kpi.green::after  { background:#10b981; }
.cs-kpi.amber::after  { background:#f59e0b; }
.cs-kpi.violet::after { background:#8b5cf6; }
.cs-kpi.teal::after   { background:#14b8a6; }
.cs-kpi-ico { font-size:1.2rem; margin-bottom:8px; }
.cs-kpi.blue   .cs-kpi-ico { color:#3b82f6; }
.cs-kpi.red    .cs-kpi-ico { color:#ef4444; }
.cs-kpi.green  .cs-kpi-ico { color:#10b981; }
.cs-kpi.amber  .cs-kpi-ico { color:#f59e0b; }
.cs-kpi.violet .cs-kpi-ico { color:#8b5cf6; }
.cs-kpi.teal   .cs-kpi-ico { color:#14b8a6; }
.cs-kpi-val { font-family:var(--font-display); font-size:1.65rem; font-weight:900; color:var(--text); letter-spacing:-.03em; line-height:1; margin-bottom:4px; }
.cs-kpi-lbl { font-size:.65rem; color:var(--text-3); font-weight:700; text-transform:uppercase; letter-spacing:.06em; }
.cs-kpi-sub { font-size:.68rem; color:var(--text-3); margin-top:3px; }

/* Charts grid */
.cs-charts { display:grid; grid-template-columns:2fr 1fr; gap:16px; margin-bottom:20px; }
.cs-chart-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; }
.cs-chart-hd { padding:14px 18px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
.cs-chart-hd h3 { font-family:var(--font-display); font-size:.9rem; font-weight:700; color:var(--text); margin:0; display:flex; align-items:center; gap:7px; }
.cs-chart-hd h3 i { color:#ef4444; }
.cs-chart-hd p { font-size:.72rem; color:var(--text-3); margin:0; }
.cs-chart-body { padding:20px; }
.cs-chart-canvas { width:100%; }

/* No data */
.cs-no-stats {
    text-align:center; padding:40px 20px;
    background:var(--surface-2); border-radius:var(--radius-lg);
    border:1px dashed var(--border); margin-bottom:20px;
}
.cs-no-stats i { font-size:2.5rem; opacity:.15; color:var(--text); margin-bottom:12px; display:block; }
.cs-no-stats h3 { font-family:var(--font-display); font-size:.95rem; font-weight:700; color:var(--text-2); margin-bottom:6px; }
.cs-no-stats p  { font-size:.8rem; color:var(--text-3); }

/* Historique table */
.cs-hist-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; margin-bottom:20px; }
.cs-hist-hd { padding:14px 18px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:8px; }
.cs-hist-hd h3 { font-family:var(--font-display); font-size:.9rem; font-weight:700; color:var(--text); margin:0; }
.cs-hist-hd i { color:#ef4444; }
.cs-hist-table { width:100%; border-collapse:collapse; }
.cs-hist-table th { padding:9px 14px; font-size:.62rem; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-3); background:var(--surface-2); border-bottom:1px solid var(--border); text-align:left; }
.cs-hist-table td { padding:9px 14px; font-size:.82rem; color:var(--text); border-bottom:1px solid var(--border); }
.cs-hist-table tr:last-child td { border-bottom:none; }
.cs-hist-table tr:hover td { background:rgba(239,68,68,.02); }
.cs-hist-table td.num { font-family:var(--font-display); font-weight:700; }
.cs-hist-table td.zero { color:var(--text-3); }
.cs-bar-mini { display:flex; align-items:center; gap:6px; }
.cs-bar-mini-track { flex:1; height:4px; background:var(--surface-2); border-radius:2px; overflow:hidden; max-width:80px; }
.cs-bar-mini-fill { height:100%; border-radius:2px; }
.cs-bar-mini-val  { font-size:.7rem; font-weight:700; min-width:35px; }
.good { color:#059669; } .ok { color:#d97706; } .low { color:#dc2626; }

/* Info card */
.cs-info-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); padding:20px; }
.cs-info-row { display:flex; align-items:flex-start; gap:10px; padding:8px 0; border-bottom:1px solid var(--border); font-size:.83rem; }
.cs-info-row:last-child { border-bottom:none; }
.cs-info-lbl { color:var(--text-3); font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; width:130px; flex-shrink:0; padding-top:1px; }
.cs-info-val { color:var(--text); }
.cs-info-val.mono { font-family:var(--mono); font-size:.78rem; }

@media(max-width:1100px){ .cs-kpis{grid-template-columns:repeat(3,1fr)} }
@media(max-width:900px) { .cs-charts{grid-template-columns:1fr} .cs-kpis{grid-template-columns:repeat(2,1fr)} }
@media(max-width:600px) { .cs-kpis{grid-template-columns:1fr 1fr} .cs-banner{flex-direction:column;align-items:flex-start} }
</style>

<div class="cs-wrap">

<!-- ══ BREADCRUMB ══ -->
<div class="cs-bc">
    <a href="?page=captures"><i class="fas fa-magnet"></i> Pages de capture</a>
    <i class="fas fa-chevron-right sep"></i>
    <a href="?page=captures&action=edit&id=<?= $pageId ?>"><?= htmlspecialchars($capture['titre'] ?? '') ?></a>
    <i class="fas fa-chevron-right sep"></i>
    <span>Statistiques</span>
</div>

<!-- ══ BANNER ══ -->
<div class="cs-banner">
    <div class="cs-banner-l">
        <div class="cs-type-ico" style="background:<?= $typeInfo['color'] ?>">
            <i class="fas <?= $typeInfo['icon'] ?>"></i>
        </div>
        <div>
            <div class="cs-banner-name"><?= htmlspecialchars($capture['titre'] ?? 'Sans titre') ?></div>
            <div class="cs-banner-slug">
                <i class="fas fa-link"></i>
                /capture/<?= htmlspecialchars($capture['slug'] ?? '') ?>
                &nbsp;·&nbsp;
                <span class="cs-status <?= $capture['status'] ?? 'inactive' ?>"><?php
                    echo match($capture['status'] ?? '') {
                        'active'   => 'Active',
                        'inactive' => 'Inactive',
                        'archived' => 'Archivée',
                        default    => ucfirst($capture['status'] ?? '—'),
                    };
                ?></span>
            </div>
        </div>
    </div>
    <div class="cs-banner-r">
        <?php if (($capture['status'] ?? '') === 'active' && !empty($capture['slug'])): ?>
        <a href="/capture/<?= htmlspecialchars($capture['slug']) ?>" target="_blank" class="cs-btn cs-btn-outline">
            <i class="fas fa-eye"></i> Voir la page
        </a>
        <?php endif; ?>
        <a href="?page=captures&action=edit&id=<?= $pageId ?>" class="cs-btn cs-btn-primary">
            <i class="fas fa-edit"></i> Modifier
        </a>
    </div>
</div>

<!-- ══ PÉRIODE ══ -->
<div class="cs-period-bar">
    <h2><i class="fas fa-chart-line"></i> Analyse de performance</h2>
    <div class="cs-period-tabs">
        <?php foreach ([7 => '7 jours', 30 => '30 jours', 90 => '90 jours'] as $p => $lbl): ?>
        <a href="<?= statsPeriodUrl($p) ?>" class="cs-ptab <?= $period===$p?'active':'' ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══ KPIs ══ -->
<div class="cs-kpis">

    <div class="cs-kpi blue">
        <div class="cs-kpi-ico"><i class="fas fa-eye"></i></div>
        <div class="cs-kpi-val"><?= number_format($totalVues) ?></div>
        <div class="cs-kpi-lbl">Vues totales</div>
        <?php if ($statsAvail && $totalVues30 !== $totalVues): ?>
        <div class="cs-kpi-sub"><?= number_format($totalVues30) ?> sur <?= $period ?> j.</div>
        <?php endif; ?>
    </div>

    <div class="cs-kpi red">
        <div class="cs-kpi-ico"><i class="fas fa-users"></i></div>
        <div class="cs-kpi-val"><?= number_format($totalConv) ?></div>
        <div class="cs-kpi-lbl">Leads capturés</div>
        <?php if ($statsAvail && $totalConv30 !== $totalConv): ?>
        <div class="cs-kpi-sub"><?= number_format($totalConv30) ?> sur <?= $period ?> j.</div>
        <?php endif; ?>
    </div>

    <div class="cs-kpi green">
        <div class="cs-kpi-ico"><i class="fas fa-percentage"></i></div>
        <div class="cs-kpi-val"><?= $tauxMoyen > 0 ? number_format($tauxMoyen, 2).'%' : '—' ?></div>
        <div class="cs-kpi-lbl">Taux conversion</div>
        <div class="cs-kpi-sub">
            <?= $tauxMoyen >= 5 ? '🟢 Excellent' : ($tauxMoyen >= 2 ? '🟡 Correct' : ($tauxMoyen > 0 ? '🔴 À optimiser' : 'Pas de données')) ?>
        </div>
    </div>

    <div class="cs-kpi amber">
        <div class="cs-kpi-ico"><i class="fas fa-chart-bar"></i></div>
        <div class="cs-kpi-val"><?= $vuesParJour > 0 ? $vuesParJour : '—' ?></div>
        <div class="cs-kpi-lbl">Vues / jour moy.</div>
        <div class="cs-kpi-sub">Sur <?= $joursActif ?> jours</div>
    </div>

    <div class="cs-kpi violet">
        <div class="cs-kpi-ico"><i class="fas fa-trophy"></i></div>
        <div class="cs-kpi-val"><?= $bestDay ? number_format($bestDay['conversions']) : '—' ?></div>
        <div class="cs-kpi-lbl">Meilleur jour</div>
        <?php if ($bestDay): ?>
        <div class="cs-kpi-sub"><?= date('d/m/Y', strtotime($bestDay['date'])) ?></div>
        <?php endif; ?>
    </div>

    <div class="cs-kpi teal">
        <div class="cs-kpi-ico"><i class="fas fa-clock"></i></div>
        <div class="cs-kpi-val"><?= $lastConvAt ? date('d/m', strtotime($lastConvAt)) : '—' ?></div>
        <div class="cs-kpi-lbl">Dernière conv.</div>
        <?php if ($lastConvAt): ?>
        <div class="cs-kpi-sub"><?= date('H:i', strtotime($lastConvAt)) ?></div>
        <?php endif; ?>
    </div>

</div>

<?php if (!$statsAvail || empty(array_filter(array_column($dailyStats, 'vues'), fn($v) => $v > 0))): ?>
<!-- ══ NO DATA ══ -->
<div class="cs-no-stats">
    <i class="fas fa-chart-area"></i>
    <h3>Pas encore de données journalières</h3>
    <p>
        <?php if (!$statsAvail): ?>
            La table <code>captures_stats</code> n'est pas encore créée.
            Les statistiques se rempliront automatiquement à partir des premières visites.
        <?php else: ?>
            Activez votre page et partagez son lien pour commencer à collecter des données.
        <?php endif; ?>
    </p>
</div>

<?php else: ?>
<!-- ══ GRAPHIQUES ══ -->
<div class="cs-charts">

    <!-- Courbe principale -->
    <div class="cs-chart-card">
        <div class="cs-chart-hd">
            <div>
                <h3><i class="fas fa-chart-area"></i> Vues & Conversions</h3>
                <p>Évolution sur les <?= $period ?> derniers jours</p>
            </div>
        </div>
        <div class="cs-chart-body">
            <canvas id="chartMain" class="cs-chart-canvas" height="200"></canvas>
        </div>
    </div>

    <!-- Taux de conversion -->
    <div class="cs-chart-card">
        <div class="cs-chart-hd">
            <div>
                <h3><i class="fas fa-percentage"></i> Taux journalier</h3>
                <p>Conversion % par jour</p>
            </div>
        </div>
        <div class="cs-chart-body">
            <canvas id="chartTaux" class="cs-chart-canvas" height="200"></canvas>
        </div>
    </div>

</div>

<!-- ══ TABLEAU HISTORIQUE ══ -->
<div class="cs-hist-card">
    <div class="cs-hist-hd">
        <i class="fas fa-table"></i>
        <h3>Historique journalier — <?= $period ?> derniers jours</h3>
    </div>
    <?php
    // Trier du plus récent au plus ancien pour l'affichage
    $displayStats = array_reverse($dailyStats);
    $maxConv = max(1, max(array_column($dailyStats, 'conversions')));
    $maxVues = max(1, max(array_column($dailyStats, 'vues')));
    ?>
    <table class="cs-hist-table">
        <thead><tr>
            <th>Date</th>
            <th>Vues</th>
            <th>Conversions</th>
            <th>Taux</th>
        </tr></thead>
        <tbody>
        <?php foreach ($displayStats as $row):
            $hasData = $row['vues'] > 0 || $row['conversions'] > 0;
            $taux    = $row['taux'];
            $tauxClass = $taux >= 5 ? 'good' : ($taux >= 2 ? 'ok' : ($taux > 0 ? 'low' : ''));
            $convPct = $maxConv > 0 ? min(100, round($row['conversions'] / $maxConv * 100)) : 0;
        ?>
        <tr style="<?= !$hasData ? 'opacity:.4' : '' ?>">
            <td style="font-weight:600">
                <?= date('D d/m', strtotime($row['date'])) ?>
                <?php if ($row['date'] === date('Y-m-d')): ?>
                <span style="font-size:.65rem;color:#3b82f6;font-weight:700;margin-left:4px">Aujourd'hui</span>
                <?php endif; ?>
            </td>
            <td class="num <?= $row['vues']===0?'zero':'' ?>"><?= $row['vues'] > 0 ? number_format($row['vues']) : '—' ?></td>
            <td>
                <div class="cs-bar-mini">
                    <div class="cs-bar-mini-track">
                        <div class="cs-bar-mini-fill" style="width:<?= $convPct ?>%;background:#ef4444"></div>
                    </div>
                    <span class="cs-bar-mini-val num <?= $row['conversions']===0?'zero':'' ?>">
                        <?= $row['conversions'] > 0 ? $row['conversions'] : '—' ?>
                    </span>
                </div>
            </td>
            <td class="<?= $tauxClass ?>">
                <?= $taux > 0 ? number_format($taux, 2).'%' : '—' ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ══ INFOS CAPTURE ══ -->
<div class="cs-info-card">
    <div style="font-family:var(--font-display);font-size:.85rem;font-weight:700;color:var(--text-2);margin-bottom:12px;display:flex;align-items:center;gap:6px">
        <i class="fas fa-info-circle" style="color:#ef4444"></i> Informations de la capture
    </div>
    <div class="cs-info-row">
        <span class="cs-info-lbl">Type</span>
        <span class="cs-info-val"><?= htmlspecialchars($typeInfo['label']) ?></span>
    </div>
    <div class="cs-info-row">
        <span class="cs-info-lbl">Template</span>
        <span class="cs-info-val"><?= htmlspecialchars(ucfirst($capture['template'] ?? 'simple')) ?></span>
    </div>
    <div class="cs-info-row">
        <span class="cs-info-lbl">Slug</span>
        <span class="cs-info-val mono">/capture/<?= htmlspecialchars($capture['slug'] ?? '') ?></span>
    </div>
    <div class="cs-info-row">
        <span class="cs-info-lbl">CTA</span>
        <span class="cs-info-val"><?= htmlspecialchars($capture['cta_text'] ?? '—') ?></span>
    </div>
    <div class="cs-info-row">
        <span class="cs-info-lbl">Page merci</span>
        <span class="cs-info-val mono"><?= htmlspecialchars($capture['page_merci_url'] ?? '—') ?></span>
    </div>
    <div class="cs-info-row">
        <span class="cs-info-lbl">Créée le</span>
        <span class="cs-info-val"><?= $createdAt ? date('d/m/Y à H:i', strtotime($createdAt)) : '—' ?></span>
    </div>
    <div class="cs-info-row">
        <span class="cs-info-lbl">Mise à jour</span>
        <span class="cs-info-val"><?= !empty($capture['updated_at']) ? date('d/m/Y à H:i', strtotime($capture['updated_at'])) : '—' ?></span>
    </div>
    <?php if ($lastConvAt): ?>
    <div class="cs-info-row">
        <span class="cs-info-lbl">Dernière conv.</span>
        <span class="cs-info-val"><?= date('d/m/Y à H:i', strtotime($lastConvAt)) ?></span>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.cs-wrap -->

<!-- ══ CHART.JS ══ -->
<?php if ($statsAvail && !empty(array_filter(array_column($dailyStats, 'vues'), fn($v) => $v > 0))): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const LABELS = <?= $chartLabels ?>;
const VUES   = <?= $chartVues ?>;
const CONV   = <?= $chartConv ?>;
const TAUX   = <?= $chartTaux ?>;

// Couleurs dynamiques
const rootStyle = getComputedStyle(document.documentElement);
const isDark = document.documentElement.classList.contains('dark') ||
               window.matchMedia('(prefers-color-scheme: dark)').matches;
const gridColor = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.05)';
const textColor = isDark ? '#9ca3af' : '#6b7280';

// ─── Graphique principal : Vues + Conversions ───
const ctxMain = document.getElementById('chartMain');
if (ctxMain) {
    new Chart(ctxMain, {
        type: 'line',
        data: {
            labels: LABELS,
            datasets: [
                {
                    label: 'Vues',
                    data: VUES,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,.08)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: .4,
                },
                {
                    label: 'Conversions',
                    data: CONV,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,.08)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: .4,
                    yAxisID: 'y2',
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: {
                    labels: { color: textColor, font: { size: 11, weight: '600' }, boxWidth: 12 }
                },
                tooltip: {
                    backgroundColor: isDark ? '#1f2937' : '#fff',
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                    borderWidth: 1,
                    titleColor: isDark ? '#f3f4f6' : '#111',
                    bodyColor: textColor,
                    padding: 10,
                    callbacks: {
                        label: ctx => ` ${ctx.dataset.label} : ${ctx.parsed.y.toLocaleString('fr-FR')}`
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor, font: { size: 10 }, maxTicksLimit: 10 }
                },
                y: {
                    type: 'linear', position: 'left',
                    grid: { color: gridColor },
                    ticks: { color: '#3b82f6', font: { size: 10 } },
                    title: { display: true, text: 'Vues', color: '#3b82f6', font: { size: 10 } }
                },
                y2: {
                    type: 'linear', position: 'right',
                    grid: { drawOnChartArea: false },
                    ticks: { color: '#ef4444', font: { size: 10 } },
                    title: { display: true, text: 'Conversions', color: '#ef4444', font: { size: 10 } }
                }
            }
        }
    });
}

// ─── Graphique taux de conversion ───
const ctxTaux = document.getElementById('chartTaux');
if (ctxTaux) {
    // Couleur dynamique selon valeur
    const barColors = TAUX.map(v => v >= 5 ? '#10b981' : (v >= 2 ? '#f59e0b' : '#ef4444'));

    new Chart(ctxTaux, {
        type: 'bar',
        data: {
            labels: LABELS,
            datasets: [{
                label: 'Taux (%)',
                data: TAUX,
                backgroundColor: barColors,
                borderRadius: 4,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: isDark ? '#1f2937' : '#fff',
                    borderColor: isDark ? '#374151' : '#e5e7eb',
                    borderWidth: 1,
                    titleColor: isDark ? '#f3f4f6' : '#111',
                    bodyColor: textColor,
                    padding: 10,
                    callbacks: {
                        label: ctx => ` Taux : ${ctx.parsed.y.toFixed(2)}%`
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: gridColor },
                    ticks: { color: textColor, font: { size: 10 }, maxTicksLimit: 10 }
                },
                y: {
                    grid: { color: gridColor },
                    ticks: {
                        color: textColor, font: { size: 10 },
                        callback: v => v + '%'
                    },
                    min: 0,
                }
            }
        }
    });
}
</script>
<?php endif; ?>