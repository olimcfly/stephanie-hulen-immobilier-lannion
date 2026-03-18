<?php
/**
 * Module Scoring Leads  v2.0
 * /admin/modules/scoring/index.php
 * Pattern aligné pages/index.php v1.0
 */

// ─── Connexion DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(__DIR__)) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

// ─── Initialisation tables ───
$pdo->exec("CREATE TABLE IF NOT EXISTS scoring_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    operator VARCHAR(20) NOT NULL,
    field_value VARCHAR(255) DEFAULT NULL,
    points INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

try {
    $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS score INT DEFAULT 0");
    $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS temperature ENUM('cold','warm','hot') DEFAULT 'cold'");
    $pdo->exec("ALTER TABLE leads ADD COLUMN IF NOT EXISTS score_updated_at TIMESTAMP NULL");
} catch (PDOException $e) {}

// ─── Règles par défaut ───
$rulesCount = $pdo->query("SELECT COUNT(*) FROM scoring_rules")->fetchColumn();
if ($rulesCount == 0) {
    $defaultRules = [
        ['Email fourni',            'engagement', 'email',              'not_empty',    null,           10],
        ['Téléphone fourni',        'engagement', 'phone',              'not_empty',    null,           15],
        ['Notes renseignées',       'engagement', 'notes',              'not_empty',    null,            5],
        ['Source: Recommandation',  'source',     'source',             'equals',       'Recommandation',25],
        ['Source: Site web',        'source',     'source',             'equals',       'Site web',      15],
        ['Source: Google',          'source',     'source',             'equals',       'Google',        10],
        ['Source: Facebook',        'source',     'source',             'equals',       'Facebook',       8],
        ['Valeur > 100 000€',       'value',      'estimated_value',    'greater_than', '100000',        20],
        ['Valeur > 200 000€',       'value',      'estimated_value',    'greater_than', '200000',        30],
        ['Valeur > 500 000€',       'value',      'estimated_value',    'greater_than', '500000',        40],
        ['Étape: Premier contact',  'pipeline',   'pipeline_stage_id',  'equals',       '2',             10],
        ['Étape: Qualification',    'pipeline',   'pipeline_stage_id',  'equals',       '3',             20],
        ['Étape: Visite programmée','pipeline',   'pipeline_stage_id',  'equals',       '4',             35],
        ['Étape: Offre en cours',   'pipeline',   'pipeline_stage_id',  'equals',       '5',             50],
        ['Action planifiée',        'activity',   'next_action',        'not_empty',    null,            10],
        ['Créé < 7 jours',          'activity',   'created_days',       'less_than',    '7',             15],
        ['Créé < 30 jours',         'activity',   'created_days',       'less_than',    '30',             5],
    ];
    $stmt = $pdo->prepare("INSERT INTO scoring_rules (name, category, field_name, operator, field_value, points) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($defaultRules as $rule) $stmt->execute($rule);
}

// ─── Helpers ───
function calculateLeadScore(array $lead, array $rules): array {
    $score = 0;
    $matched = [];
    $createdDays = floor((time() - strtotime($lead['created_at'])) / 86400);
    foreach ($rules as $rule) {
        if (!$rule['is_active']) continue;
        $fv = $rule['field_name'] === 'created_days' ? $createdDays : ($lead[$rule['field_name']] ?? null);
        $ok = match($rule['operator']) {
            'equals'       => $fv == $rule['field_value'],
            'not_equals'   => $fv != $rule['field_value'],
            'not_empty'    => !empty($fv),
            'empty'        => empty($fv),
            'greater_than' => floatval($fv) > floatval($rule['field_value']),
            'less_than'    => floatval($fv) < floatval($rule['field_value']),
            'contains'     => stripos((string)$fv, (string)$rule['field_value']) !== false,
            default        => false,
        };
        if ($ok) { $score += $rule['points']; $matched[] = $rule; }
    }
    return ['score' => $score, 'rules' => $matched];
}

function getTemperature(int $score): string {
    if ($score >= 70) return 'hot';
    if ($score >= 35) return 'warm';
    return 'cold';
}

// ─── CSRF ───
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ─── Données ───
$rules = $pdo->query("SELECT * FROM scoring_rules ORDER BY category, points DESC")->fetchAll();

$rawLeads = $pdo->query("
    SELECT l.*, ps.name AS stage_name, ps.color AS stage_color
    FROM leads l
    LEFT JOIN pipeline_stages ps ON l.pipeline_stage_id = ps.id
    ORDER BY l.created_at DESC
")->fetchAll();

// Recalcul + mise à jour
$allLeads = [];
foreach ($rawLeads as $lead) {
    $result      = calculateLeadScore($lead, $rules);
    $temperature = getTemperature($result['score']);
    if ($lead['score'] != $result['score'] || $lead['temperature'] !== $temperature) {
        $pdo->prepare("UPDATE leads SET score=?, temperature=?, score_updated_at=NOW() WHERE id=?")
            ->execute([$result['score'], $temperature, $lead['id']]);
    }
    $lead['score']         = $result['score'];
    $lead['temperature']   = $temperature;
    $lead['matched_rules'] = $result['rules'];
    $allLeads[]            = $lead;
}
usort($allLeads, fn($a, $b) => $b['score'] - $a['score']);

// ─── Filtres URL ───
$filterTemp  = $_GET['temp']   ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');
$currentPage = max(1, (int)($_GET['p'] ?? 1));
$perPage     = 25;

// Filtrage applicatif
$filtered = array_filter($allLeads, function($l) use ($filterTemp, $searchQuery) {
    if ($filterTemp !== 'all' && $l['temperature'] !== $filterTemp) return false;
    if ($searchQuery !== '') {
        $name = strtolower(($l['firstname'] ?? '') . ' ' . ($l['lastname'] ?? '') . ' ' . ($l['email'] ?? ''));
        if (stripos($name, $searchQuery) === false) return false;
    }
    return true;
});
$filtered = array_values($filtered);

$totalFiltered = count($filtered);
$totalPages    = max(1, ceil($totalFiltered / $perPage));
$offset        = ($currentPage - 1) * $perPage;
$pagedLeads    = array_slice($filtered, $offset, $perPage);

// ─── Stats ───
$hotCount  = count(array_filter($allLeads, fn($l) => $l['temperature'] === 'hot'));
$warmCount = count(array_filter($allLeads, fn($l) => $l['temperature'] === 'warm'));
$coldCount = count(array_filter($allLeads, fn($l) => $l['temperature'] === 'cold'));
$avgScore  = count($allLeads) > 0 ? round(array_sum(array_column($allLeads, 'score')) / count($allLeads)) : 0;

// ─── Grouper règles par catégorie ───
$rulesByCategory = [];
foreach ($rules as $rule) $rulesByCategory[$rule['category']][] = $rule;

$categoryMeta = [
    'engagement' => ['label' => 'Engagement', 'icon' => 'fa-handshake',  'color' => '#6366f1'],
    'source'     => ['label' => 'Source',      'icon' => 'fa-globe',      'color' => '#10b981'],
    'value'      => ['label' => 'Valeur',       'icon' => 'fa-euro-sign', 'color' => '#f59e0b'],
    'pipeline'   => ['label' => 'Pipeline',     'icon' => 'fa-filter',    'color' => '#ec4899'],
    'activity'   => ['label' => 'Activité',     'icon' => 'fa-clock',     'color' => '#06b6d4'],
];

$flash = $_GET['msg'] ?? '';
?>

<style>
/* ══════════════════════════════════════════════════════════════
   SCORING MODULE v2.0
   Pattern aligné pages/index.php v1.0
══════════════════════════════════════════════════════════════ */
.scm-wrap { font-family: var(--font, 'Inter', sans-serif); }

/* ─── Banner ─── */
.scm-banner {
    background: var(--surface, #fff);
    border-radius: 16px; padding: 26px 30px; margin-bottom: 22px;
    display: flex; align-items: center; justify-content: space-between;
    border: 1px solid var(--border, #e5e7eb); position: relative; overflow: hidden;
}
.scm-banner::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #f59e0b, #ef4444, #6366f1);
}
.scm-banner::after {
    content: ''; position: absolute; top: -40%; right: -5%;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(239,68,68,.05), transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.scm-banner-left { position: relative; z-index: 1; }
.scm-banner-left h2 { font-size: 1.35rem; font-weight: 700; color: var(--text, #111827); margin: 0 0 4px; display: flex; align-items: center; gap: 10px; letter-spacing: -.02em; }
.scm-banner-left h2 i { font-size: 16px; color: #ef4444; }
.scm-banner-left p { color: var(--text-2, #6b7280); font-size: .85rem; margin: 0; }
.scm-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.scm-stat { text-align: center; padding: 10px 16px; background: var(--surface-2, #f9fafb); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); min-width: 72px; transition: all .2s; cursor: default; }
.scm-stat:hover { border-color: var(--border-h, #d1d5db); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.scm-stat .num { font-size: 1.45rem; font-weight: 800; line-height: 1; color: var(--text, #111827); letter-spacing: -.03em; }
.scm-stat .num.red    { color: #ef4444; }
.scm-stat .num.amber  { color: #f59e0b; }
.scm-stat .num.blue   { color: #3b82f6; }
.scm-stat .num.violet { color: #7c3aed; }
.scm-stat .lbl { font-size: .58rem; color: var(--text-3, #9ca3af); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }

/* ─── Toolbar ─── */
.scm-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.scm-filters { display: flex; gap: 3px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; padding: 3px; flex-wrap: wrap; }
.scm-fbtn { padding: 7px 15px; border: none; background: transparent; color: var(--text-2, #6b7280); font-size: .78rem; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all .15s; font-family: inherit; display: flex; align-items: center; gap: 5px; text-decoration: none; }
.scm-fbtn:hover { color: var(--text, #111827); background: var(--surface-2, #f9fafb); }
.scm-fbtn.active { background: #6366f1; color: #fff; box-shadow: 0 1px 4px rgba(99,102,241,.25); }
.scm-fbtn.hot.active   { background: #ef4444; box-shadow: 0 1px 4px rgba(239,68,68,.25); }
.scm-fbtn.warm.active  { background: #f59e0b; box-shadow: 0 1px 4px rgba(245,158,11,.25); }
.scm-fbtn.cold.active  { background: #3b82f6; box-shadow: 0 1px 4px rgba(59,130,246,.25); }
.scm-fbtn .badge { font-size: .68rem; padding: 1px 7px; border-radius: 10px; background: var(--surface-2, #f3f4f6); font-weight: 700; color: var(--text-3, #9ca3af); }
.scm-fbtn.active .badge { background: rgba(255,255,255,.22); color: #fff; }

/* ─── Toolbar right ─── */
.scm-toolbar-r { display: flex; align-items: center; gap: 10px; }
.scm-view-toggle { display: flex; gap: 2px; background: var(--surface-2, #f9fafb); border: 1px solid var(--border, #e5e7eb); border-radius: 8px; padding: 3px; }
.scm-view-btn { width: 30px; height: 28px; border: none; background: transparent; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-3, #9ca3af); transition: all .15s; font-size: .78rem; }
.scm-view-btn:hover { color: var(--text, #111827); }
.scm-view-btn.active { background: white; color: #6366f1; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.scm-search { position: relative; }
.scm-search input { padding: 8px 12px 8px 34px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; color: var(--text, #111827); font-size: .82rem; width: 200px; font-family: inherit; transition: all .2s; }
.scm-search input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); width: 230px; }
.scm-search input::placeholder { color: var(--text-3, #9ca3af); }
.scm-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-3, #9ca3af); font-size: .75rem; }
.scm-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 10px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; font-family: inherit; text-decoration: none; line-height: 1.3; }
.scm-btn-primary { background: #6366f1; color: #fff; box-shadow: 0 1px 4px rgba(99,102,241,.22); }
.scm-btn-primary:hover { background: #4f46e5; transform: translateY(-1px); color: #fff; }
.scm-btn-outline { background: var(--surface, #fff); color: var(--text-2, #6b7280); border: 1px solid var(--border, #e5e7eb); }
.scm-btn-outline:hover { border-color: #6366f1; color: #6366f1; }
.scm-btn-sm { padding: 5px 12px; font-size: .75rem; }

/* ─── Tabs ─── */
.scm-tabs { display: flex; gap: 3px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; padding: 3px; width: fit-content; margin-bottom: 18px; }
.scm-tab { padding: 7px 18px; border: none; background: transparent; color: var(--text-2, #6b7280); font-size: .78rem; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all .15s; font-family: inherit; display: flex; align-items: center; gap: 5px; }
.scm-tab:hover { color: var(--text, #111827); background: var(--surface-2, #f9fafb); }
.scm-tab.active { background: #6366f1; color: #fff; box-shadow: 0 1px 4px rgba(99,102,241,.25); }
.scm-tab-content { display: none; }
.scm-tab-content.active { display: block; }

/* ─── Table ─── */
.scm-table-wrap { background: var(--surface, #fff); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; }
.scm-table { width: 100%; border-collapse: collapse; }
.scm-table thead th { padding: 11px 14px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-3, #9ca3af); background: var(--surface-2, #f9fafb); border-bottom: 1px solid var(--border, #e5e7eb); text-align: left; white-space: nowrap; }
.scm-table thead th.center { text-align: center; }
.scm-table tbody tr { border-bottom: 1px solid var(--border, #f3f4f6); transition: background .1s; }
.scm-table tbody tr:hover { background: rgba(99,102,241,.02); }
.scm-table tbody tr:last-child { border-bottom: none; }
.scm-table td { padding: 11px 14px; font-size: .83rem; color: var(--text, #111827); vertical-align: middle; }
.scm-table td.center { text-align: center; }

/* ─── Lead cell ─── */
.scm-lead-name { font-weight: 600; color: var(--text, #111827); display: flex; align-items: center; gap: 6px; }
.scm-lead-contact { font-size: .72rem; color: var(--text-3, #9ca3af); margin-top: 3px; display: flex; align-items: center; gap: 6px; }

/* ─── Score ring ─── */
.scm-score-wrap { display: flex; flex-direction: column; align-items: center; gap: 3px; min-width: 58px; }
.scm-score-ring { width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 800; border: 3px solid transparent; transition: transform .2s; cursor: help; }
.scm-score-ring:hover { transform: scale(1.1); }
.scm-score-ring.hot  { background: #fef2f2; border-color: #ef4444; color: #dc2626; }
.scm-score-ring.warm { background: #fffbeb; border-color: #f59e0b; color: #d97706; }
.scm-score-ring.cold { background: #eff6ff; border-color: #3b82f6; color: #2563eb; }
.scm-score-bar  { width: 40px; height: 3px; background: var(--border, #e5e7eb); border-radius: 2px; overflow: hidden; }
.scm-score-fill { height: 100%; border-radius: 2px; transition: width .5s cubic-bezier(.4,0,.2,1); }
.scm-score-fill.hot  { background: #ef4444; }
.scm-score-fill.warm { background: #f59e0b; }
.scm-score-fill.cold { background: #3b82f6; }

/* ─── Temperature badge ─── */
.scm-temp { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 12px; font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.scm-temp.hot  { background: #fecaca; color: #991b1b; }
.scm-temp.warm { background: #fde68a; color: #92400e; }
.scm-temp.cold { background: #bfdbfe; color: #1e40af; }

/* ─── Stage badge ─── */
.scm-stage { display: inline-block; padding: 3px 10px; border-radius: 8px; font-size: .68rem; font-weight: 600; background: var(--surface-2, #f1f5f9); color: var(--text-2, #64748b); }

/* ─── Value ─── */
.scm-value { font-weight: 700; color: #10b981; font-size: .83rem; }

/* ─── Actions ─── */
.scm-actions { display: flex; gap: 3px; justify-content: flex-end; }
.scm-actions a, .scm-actions button { width: 30px; height: 30px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--text-3, #9ca3af); background: transparent; border: 1px solid transparent; cursor: pointer; transition: all .12s; text-decoration: none; font-size: .78rem; }
.scm-actions a:hover, .scm-actions button:hover { color: #6366f1; border-color: var(--border, #e5e7eb); background: rgba(99,102,241,.07); }

/* ─── Tooltip score detail ─── */
.scm-score-detail { position: relative; }
.scm-score-tooltip { display: none; position: absolute; top: 100%; left: 50%; transform: translateX(-50%); margin-top: 8px; background: #fff; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,.15); padding: 14px 16px; min-width: 240px; z-index: 100; border: 1px solid var(--border, #e5e7eb); }
.scm-score-detail:hover .scm-score-tooltip { display: block; }
.scm-score-tooltip h4 { font-size: .65rem; font-weight: 700; color: var(--text-3, #9ca3af); margin-bottom: 10px; text-transform: uppercase; letter-spacing: .05em; }
.scm-score-tooltip-item { display: flex; justify-content: space-between; align-items: center; padding: 6px 0; border-bottom: 1px solid var(--border, #f1f5f9); font-size: .78rem; }
.scm-score-tooltip-item:last-child { border-bottom: none; }
.scm-score-tooltip-pts { font-weight: 700; color: #10b981; }

/* ══ VUE GRILLE ══════════════════════════════════════════════ */
.scm-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
.scm-card { background: var(--surface, #fff); border-radius: 14px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; transition: all .2s; display: flex; flex-direction: column; }
.scm-card:hover { border-color: #6366f1; box-shadow: 0 4px 20px rgba(99,102,241,.1); transform: translateY(-2px); }
.scm-card-accent { height: 3px; width: 100%; }
.scm-card-accent.hot  { background: #ef4444; }
.scm-card-accent.warm { background: #f59e0b; }
.scm-card-accent.cold { background: #3b82f6; }
.scm-card-top { padding: 14px 16px 12px; flex: 1; }
.scm-card-title { font-size: .88rem; font-weight: 700; color: var(--text, #111827); display: block; line-height: 1.35; }
.scm-card-contact { font-size: .7rem; color: var(--text-3, #9ca3af); margin-top: 4px; }
.scm-card-badges { display: flex; gap: 5px; flex-wrap: wrap; align-items: center; margin-top: 8px; }
.scm-card-stats { display: flex; gap: 0; border-top: 1px solid var(--border, #f3f4f6); }
.scm-card-stat { flex: 1; text-align: center; padding: 9px 6px; border-right: 1px solid var(--border, #f3f4f6); }
.scm-card-stat:last-child { border-right: none; }
.scm-card-stat-val { font-size: .82rem; font-weight: 800; color: var(--text, #111827); display: block; }
.scm-card-stat-lbl { font-size: .55rem; color: var(--text-3, #9ca3af); text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }
.scm-card-footer { display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-top: 1px solid var(--border, #f3f4f6); }

/* ─── Masquage vues ─── */
.scm-list-view .scm-grid-wrap { display: none !important; }
.scm-grid-view .scm-list-wrap { display: none !important; }

/* ─── Rules grid ─── */
.scm-rules-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(330px, 1fr)); gap: 16px; }
.scm-rules-cat { background: var(--surface, #fff); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; }
.scm-rules-cat-header { padding: 14px 18px; background: var(--surface-2, #f9fafb); border-bottom: 1px solid var(--border, #e5e7eb); display: flex; align-items: center; gap: 10px; }
.scm-rules-cat-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; font-size: .85rem; flex-shrink: 0; }
.scm-rules-cat-title { font-weight: 700; font-size: .85rem; color: var(--text, #111827); }
.scm-rules-list { padding: 10px; }
.scm-rule-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; border-radius: 8px; margin-bottom: 6px; background: var(--surface-2, #f9fafb); transition: all .15s; }
.scm-rule-item:last-child { margin-bottom: 0; }
.scm-rule-item:hover { background: var(--surface, #f1f5f9); }
.scm-rule-item.inactive { opacity: .5; }
.scm-rule-name { font-size: .78rem; font-weight: 500; color: var(--text-2, #374151); display: flex; align-items: center; gap: 8px; }
.scm-rule-pts { font-size: .8rem; font-weight: 700; color: #10b981; background: rgba(16,185,129,.1); padding: 3px 9px; border-radius: 6px; }
.scm-rule-pts.neg { color: #ef4444; background: rgba(239,68,68,.1); }

/* ─── Toggle switch ─── */
.scm-toggle { position: relative; width: 40px; height: 22px; background: var(--border, #e2e8f0); border-radius: 11px; cursor: pointer; transition: all .2s; flex-shrink: 0; border: none; }
.scm-toggle.on { background: #10b981; }
.scm-toggle::after { content: ''; position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; background: white; border-radius: 50%; transition: all .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
.scm-toggle.on::after { left: 20px; }

/* ─── Pagination ─── */
.scm-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-top: 1px solid var(--border, #e5e7eb); font-size: .78rem; color: var(--text-3, #9ca3af); }
.scm-pagination a { padding: 6px 12px; border: 1px solid var(--border, #e5e7eb); border-radius: 10px; color: var(--text-2, #6b7280); text-decoration: none; font-weight: 600; transition: all .15s; font-size: .78rem; }
.scm-pagination a:hover { border-color: #6366f1; color: #6366f1; }
.scm-pagination a.active { background: #6366f1; color: #fff; border-color: #6366f1; }

/* ─── Flash / Empty ─── */
.scm-flash { padding: 12px 18px; border-radius: 10px; font-size: .85rem; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; animation: scmFadeIn .3s; }
.scm-flash.success { background: #d1fae5; color: #059669; border: 1px solid rgba(5,150,105,.12); }
@keyframes scmFadeIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }
.scm-empty { text-align: center; padding: 60px 20px; color: var(--text-3, #9ca3af); }
.scm-empty i { font-size: 2.5rem; opacity: .2; margin-bottom: 12px; display: block; }
.scm-empty h3 { color: var(--text-2, #6b7280); font-size: 1rem; font-weight: 600; margin-bottom: 6px; }

/* ─── Threshold legend ─── */
.scm-legend { display: flex; gap: 16px; flex-wrap: wrap; padding: 12px 16px; background: var(--surface-2, #f9fafb); border-radius: 10px; border: 1px solid var(--border, #e5e7eb); margin-bottom: 18px; }
.scm-legend-item { display: flex; align-items: center; gap: 6px; font-size: .75rem; color: var(--text-2, #6b7280); font-weight: 500; }
.scm-legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

@media (max-width: 960px) {
    .scm-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .scm-toolbar { flex-direction: column; align-items: flex-start; }
    .scm-table-wrap { overflow-x: auto; }
    .scm-rules-grid { grid-template-columns: 1fr; }
}
</style>

<div class="scm-wrap" id="scmWrap">

<?php if ($flash === 'recalculated'): ?>
    <div class="scm-flash success"><i class="fas fa-check-circle"></i> Scores recalculés avec succès</div>
<?php endif; ?>

<!-- ─── Banner ─── -->
<div class="scm-banner">
    <div class="scm-banner-left">
        <h2><i class="fas fa-fire"></i> Scoring des leads</h2>
        <p>Identifiez et priorisez vos prospects les plus qualifiés</p>
    </div>
    <div class="scm-stats">
        <div class="scm-stat" title="Leads chauds (score ≥ 70)">
            <div class="num red"><?= $hotCount ?></div>
            <div class="lbl">🔥 Chauds</div>
        </div>
        <div class="scm-stat" title="Leads tièdes (score 35–69)">
            <div class="num amber"><?= $warmCount ?></div>
            <div class="lbl">☀️ Tièdes</div>
        </div>
        <div class="scm-stat" title="Leads froids (score < 35)">
            <div class="num blue"><?= $coldCount ?></div>
            <div class="lbl">❄️ Froids</div>
        </div>
        <div class="scm-stat" title="Score moyen de tous les leads">
            <div class="num violet"><?= $avgScore ?></div>
            <div class="lbl">Score moy.</div>
        </div>
    </div>
</div>

<!-- ─── Tabs ─── -->
<div class="scm-tabs">
    <button class="scm-tab active" id="scmTabLeads" onclick="SCORING.showTab('leads')">
        <i class="fas fa-users"></i> Leads <span style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:8px;font-size:.68rem"><?= count($allLeads) ?></span>
    </button>
    <button class="scm-tab" id="scmTabRules" onclick="SCORING.showTab('rules')">
        <i class="fas fa-sliders-h"></i> Règles <span style="background:rgba(255,255,255,.2);padding:1px 7px;border-radius:8px;font-size:.68rem"><?= count($rules) ?></span>
    </button>
</div>

<!-- ══ TAB LEADS ══════════════════════════════════════════════ -->
<div class="scm-tab-content active" id="scmContentLeads">

    <!-- Toolbar -->
    <div class="scm-toolbar">
        <div class="scm-filters">
            <?php
            $tempFilters = [
                'all'  => ['label' => 'Tous',    'emoji' => '',   'count' => count($allLeads)],
                'hot'  => ['label' => 'Chauds',  'emoji' => '🔥', 'count' => $hotCount],
                'warm' => ['label' => 'Tièdes',  'emoji' => '☀️', 'count' => $warmCount],
                'cold' => ['label' => 'Froids',  'emoji' => '❄️', 'count' => $coldCount],
            ];
            foreach ($tempFilters as $key => $f):
                $active = ($filterTemp === $key) ? ' active ' . ($key !== 'all' ? $key : '') : '';
                $url = '?page=scoring' . ($key !== 'all' ? '&temp=' . $key : '');
                if ($searchQuery) $url .= '&q=' . urlencode($searchQuery);
            ?>
                <a href="<?= $url ?>" class="scm-fbtn<?= $active ?>">
                    <?= $f['emoji'] ?> <?= $f['label'] ?>
                    <span class="badge"><?= $f['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="scm-toolbar-r">
            <div class="scm-view-toggle">
                <button class="scm-view-btn active" id="scmBtnList" onclick="SCORING.setView('list')" title="Vue liste"><i class="fas fa-list"></i></button>
                <button class="scm-view-btn"         id="scmBtnGrid" onclick="SCORING.setView('grid')" title="Vue grille"><i class="fas fa-th-large"></i></button>
            </div>
            <form class="scm-search" method="GET">
                <input type="hidden" name="page" value="scoring">
                <?php if ($filterTemp !== 'all'): ?>
                    <input type="hidden" name="temp" value="<?= htmlspecialchars($filterTemp) ?>">
                <?php endif; ?>
                <i class="fas fa-search"></i>
                <input type="text" name="q" placeholder="Nom, email…" value="<?= htmlspecialchars($searchQuery) ?>">
            </form>
            <button class="scm-btn scm-btn-outline" onclick="SCORING.recalculate()">
                <i class="fas fa-sync-alt"></i> Recalculer
            </button>
        </div>
    </div>

    <?php if (empty($pagedLeads)): ?>
        <div class="scm-empty">
            <i class="fas fa-user-slash"></i>
            <h3>Aucun lead trouvé</h3>
            <p><?= ($searchQuery || $filterTemp !== 'all') ? '<a href="?page=scoring">Effacer les filtres</a>' : 'Ajoutez des leads dans le pipeline.' ?></p>
        </div>
    <?php else: ?>

    <!-- ══ VUE LISTE ══ -->
    <div class="scm-list-wrap">
        <div class="scm-table-wrap">
            <table class="scm-table">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th class="center">Score</th>
                        <th>Température</th>
                        <th>Étape</th>
                        <th>Valeur</th>
                        <th>Source</th>
                        <th style="text-align:right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($pagedLeads as $lead):
                    $temp  = $lead['temperature'];
                    $score = $lead['score'];
                    $name  = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? '')) ?: 'Sans nom';
                    $tempLabels = ['hot' => 'Chaud', 'warm' => 'Tiède', 'cold' => 'Froid'];
                    $tempEmojis = ['hot' => '🔥', 'warm' => '☀️', 'cold' => '❄️'];
                ?>
                <tr data-id="<?= (int)$lead['id'] ?>">
                    <!-- Lead -->
                    <td>
                        <div class="scm-lead-name">
                            <?= $tempEmojis[$temp] ?? '' ?>
                            <?= htmlspecialchars($name) ?>
                        </div>
                        <div class="scm-lead-contact">
                            <?php if (!empty($lead['email'])): ?>
                                <span><i class="fas fa-envelope" style="font-size:.65rem"></i> <?= htmlspecialchars($lead['email']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($lead['phone'])): ?>
                                <span><i class="fas fa-phone" style="font-size:.65rem"></i> <?= htmlspecialchars($lead['phone']) ?></span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <!-- Score -->
                    <td class="center">
                        <div class="scm-score-detail">
                            <div class="scm-score-wrap">
                                <div class="scm-score-ring <?= $temp ?>" title="Survolez pour le détail">
                                    <?= $score ?>
                                </div>
                                <div class="scm-score-bar">
                                    <div class="scm-score-fill <?= $temp ?>" style="width:<?= min($score, 100) ?>%"></div>
                                </div>
                            </div>
                            <?php if (!empty($lead['matched_rules'])): ?>
                            <div class="scm-score-tooltip">
                                <h4>Détail du score</h4>
                                <?php foreach ($lead['matched_rules'] as $rule): ?>
                                    <div class="scm-score-tooltip-item">
                                        <span style="color:var(--text-2,#374151)"><?= htmlspecialchars($rule['name']) ?></span>
                                        <span class="scm-score-tooltip-pts">+<?= $rule['points'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                                <div style="margin-top:8px;padding-top:8px;border-top:2px solid var(--border,#e5e7eb);display:flex;justify-content:space-between;font-size:.78rem;font-weight:700">
                                    <span>Total</span>
                                    <span style="color:#6366f1"><?= $score ?> pts</span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <!-- Température -->
                    <td>
                        <span class="scm-temp <?= $temp ?>">
                            <?= $tempEmojis[$temp] ?? '' ?> <?= $tempLabels[$temp] ?? $temp ?>
                        </span>
                    </td>
                    <!-- Étape -->
                    <td>
                        <?php if (!empty($lead['stage_name'])): ?>
                            <span class="scm-stage" style="background:<?= $lead['stage_color'] ?? '#f1f5f9' ?>22;color:<?= $lead['stage_color'] ?? '#64748b' ?>">
                                <?= htmlspecialchars($lead['stage_name']) ?>
                            </span>
                        <?php else: ?>
                            <span style="color:var(--text-3,#9ca3af)">—</span>
                        <?php endif; ?>
                    </td>
                    <!-- Valeur -->
                    <td>
                        <?php if (($lead['estimated_value'] ?? 0) > 0): ?>
                            <span class="scm-value"><?= number_format($lead['estimated_value'], 0, ',', "\u{202F}") ?> €</span>
                        <?php else: ?>
                            <span style="color:var(--text-3,#9ca3af)">—</span>
                        <?php endif; ?>
                    </td>
                    <!-- Source -->
                    <td style="font-size:.78rem;color:var(--text-2,#6b7280)">
                        <?= htmlspecialchars($lead['source'] ?? '—') ?>
                    </td>
                    <!-- Actions -->
                    <td>
                        <div class="scm-actions">
                            <a href="?page=crm-pipeline&lead_id=<?= (int)$lead['id'] ?>" title="Voir dans le pipeline"><i class="fas fa-external-link-alt"></i></a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
            <div class="scm-pagination">
                <span>Affichage <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalFiltered) ?> sur <?= $totalFiltered ?> leads</span>
                <div style="display:flex;gap:4px">
                    <?php for ($i = 1; $i <= $totalPages; $i++):
                        $pUrl = '?page=scoring&p=' . $i;
                        if ($filterTemp !== 'all') $pUrl .= '&temp=' . $filterTemp;
                        if ($searchQuery)           $pUrl .= '&q=' . urlencode($searchQuery);
                    ?>
                        <a href="<?= $pUrl ?>" class="<?= $i === $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ VUE GRILLE ══ -->
    <div class="scm-grid-wrap">
        <div class="scm-grid">
        <?php foreach ($pagedLeads as $lead):
            $temp  = $lead['temperature'];
            $score = $lead['score'];
            $name  = trim(($lead['firstname'] ?? '') . ' ' . ($lead['lastname'] ?? '')) ?: 'Sans nom';
            $tempLabels = ['hot' => 'Chaud', 'warm' => 'Tiède', 'cold' => 'Froid'];
            $tempEmojis = ['hot' => '🔥', 'warm' => '☀️', 'cold' => '❄️'];
        ?>
        <div class="scm-card" data-id="<?= (int)$lead['id'] ?>">
            <div class="scm-card-accent <?= $temp ?>"></div>
            <div class="scm-card-top">
                <span class="scm-card-title"><?= $tempEmojis[$temp] ?> <?= htmlspecialchars($name) ?></span>
                <div class="scm-card-contact">
                    <?php if (!empty($lead['email'])): ?><span><?= htmlspecialchars($lead['email']) ?></span><?php endif; ?>
                </div>
                <div class="scm-card-badges">
                    <span class="scm-temp <?= $temp ?>"><?= $tempEmojis[$temp] ?> <?= $tempLabels[$temp] ?? $temp ?></span>
                    <?php if (!empty($lead['stage_name'])): ?>
                        <span class="scm-stage" style="font-size:.6rem;padding:2px 8px"><?= htmlspecialchars($lead['stage_name']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="scm-card-stats">
                <div class="scm-card-stat">
                    <span class="scm-card-stat-val" style="color:<?= $temp === 'hot' ? '#ef4444' : ($temp === 'warm' ? '#f59e0b' : '#3b82f6') ?>"><?= $score ?></span>
                    <span class="scm-card-stat-lbl">Score</span>
                </div>
                <div class="scm-card-stat">
                    <span class="scm-card-stat-val" style="color:#10b981;font-size:.72rem">
                        <?= ($lead['estimated_value'] ?? 0) > 0 ? number_format($lead['estimated_value'], 0, ',', "\u{202F}") . ' €' : '—' ?>
                    </span>
                    <span class="scm-card-stat-lbl">Valeur</span>
                </div>
                <div class="scm-card-stat">
                    <span class="scm-card-stat-val" style="font-size:.72rem;color:var(--text-3)"><?= count($lead['matched_rules']) ?></span>
                    <span class="scm-card-stat-lbl">Règles</span>
                </div>
            </div>
            <div class="scm-card-footer">
                <span style="font-size:.72rem;color:var(--text-3,#9ca3af)"><?= htmlspecialchars($lead['source'] ?? '') ?></span>
                <div class="scm-actions" style="justify-content:flex-end">
                    <a href="?page=crm-pipeline&lead_id=<?= (int)$lead['id'] ?>" title="Voir dans le pipeline"><i class="fas fa-external-link-alt"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="scm-pagination" style="background:var(--surface,#fff);border-radius:12px;border:1px solid var(--border,#e5e7eb);margin-top:12px">
            <span>Affichage <?= $offset + 1 ?>–<?= min($offset + $perPage, $totalFiltered) ?> sur <?= $totalFiltered ?> leads</span>
            <div style="display:flex;gap:4px">
                <?php for ($i = 1; $i <= $totalPages; $i++):
                    $pUrl = '?page=scoring&p=' . $i;
                    if ($filterTemp !== 'all') $pUrl .= '&temp=' . $filterTemp;
                    if ($searchQuery)           $pUrl .= '&q=' . urlencode($searchQuery);
                ?>
                    <a href="<?= $pUrl ?>" class="<?= $i === $currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<!-- ══ TAB RULES ══════════════════════════════════════════════ -->
<div class="scm-tab-content" id="scmContentRules">

    <!-- Toolbar règles -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:10px">
        <div class="scm-legend">
            <div class="scm-legend-item">
                <div class="scm-legend-dot" style="background:#ef4444"></div>
                🔥 Chaud : score ≥ 70 pts
            </div>
            <div class="scm-legend-item">
                <div class="scm-legend-dot" style="background:#f59e0b"></div>
                ☀️ Tiède : score 35–69 pts
            </div>
            <div class="scm-legend-item">
                <div class="scm-legend-dot" style="background:#3b82f6"></div>
                ❄️ Froid : score &lt; 35 pts
            </div>
        </div>
        <button class="scm-btn scm-btn-primary" onclick="SCORING.openRuleModal()">
            <i class="fas fa-plus"></i> Nouvelle règle
        </button>
    </div>

    <div class="scm-rules-grid">
    <?php foreach ($rulesByCategory as $category => $catRules):
        $meta = $categoryMeta[$category] ?? ['label' => ucfirst($category), 'icon' => 'fa-tag', 'color' => '#6b7280'];
    ?>
        <div class="scm-rules-cat">
            <div class="scm-rules-cat-header">
                <div class="scm-rules-cat-icon" style="background:<?= $meta['color'] ?>">
                    <i class="fas <?= $meta['icon'] ?>"></i>
                </div>
                <div>
                    <div class="scm-rules-cat-title"><?= $meta['label'] ?></div>
                    <div style="font-size:.65rem;color:var(--text-3,#9ca3af)"><?= count($catRules) ?> règle(s)</div>
                </div>
            </div>
            <div class="scm-rules-list">
                <?php foreach ($catRules as $rule): ?>
                <div class="scm-rule-item <?= $rule['is_active'] ? '' : 'inactive' ?>" id="scmRule<?= $rule['id'] ?>">
                    <div class="scm-rule-name">
                        <button class="scm-toggle <?= $rule['is_active'] ? 'on' : '' ?>"
                                onclick="SCORING.toggleRule(<?= $rule['id'] ?>, this)"
                                title="<?= $rule['is_active'] ? 'Désactiver' : 'Activer' ?>"></button>
                        <?= htmlspecialchars($rule['name']) ?>
                    </div>
                    <span class="scm-rule-pts <?= $rule['points'] < 0 ? 'neg' : '' ?>">
                        <?= $rule['points'] > 0 ? '+' : '' ?><?= $rule['points'] ?> pts
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>

</div><!-- /scm-wrap -->

<!-- ══ MODAL CUSTOM ══════════════════════════════════════════ -->
<div id="scmModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    <div onclick="SCORING.modalClose()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px);"></div>
    <div id="scmModalBox" style="position:relative;z-index:1;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:460px;margin:16px;overflow:hidden;transform:scale(.94) translateY(8px);transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .15s;opacity:0;">
        <!-- Modal confirm -->
        <div id="scmModalConfirmContent">
            <div id="scmModalHeader" style="padding:20px 22px 16px;display:flex;align-items:flex-start;gap:14px;">
                <div id="scmModalIcon" style="width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;"></div>
                <div style="flex:1">
                    <div id="scmModalTitle" style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:5px;"></div>
                    <div id="scmModalMsg"   style="font-size:.82rem;color:#6b7280;line-height:1.5;"></div>
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;padding:12px 20px 18px;border-top:1px solid #f3f4f6;">
                <button onclick="SCORING.modalClose()" class="scm-btn scm-btn-outline">Annuler</button>
                <button id="scmModalConfirmBtn" class="scm-btn" style="color:#fff;"></button>
            </div>
        </div>
        <!-- Modal add rule -->
        <div id="scmModalRuleContent" style="display:none;">
            <div style="padding:20px 22px 16px;border-bottom:1px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;">
                <h3 style="font-size:.95rem;font-weight:700;margin:0">Nouvelle règle de scoring</h3>
                <button onclick="SCORING.modalClose()" style="background:#f1f5f9;border:none;width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:1rem;">×</button>
            </div>
            <form id="scmRuleForm" style="padding:20px 22px">
                <div style="margin-bottom:14px">
                    <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:5px;color:var(--text-2,#374151)">Nom de la règle *</label>
                    <input type="text" name="name" required style="width:100%;padding:9px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:.83rem;font-family:inherit;box-sizing:border-box">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                    <div>
                        <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:5px;color:var(--text-2,#374151)">Catégorie *</label>
                        <select name="category" required style="width:100%;padding:9px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:.83rem;font-family:inherit">
                            <option value="engagement">Engagement</option>
                            <option value="source">Source</option>
                            <option value="value">Valeur</option>
                            <option value="pipeline">Pipeline</option>
                            <option value="activity">Activité</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:5px;color:var(--text-2,#374151)">Points *</label>
                        <input type="number" name="points" value="10" required style="width:100%;padding:9px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:.83rem;font-family:inherit;box-sizing:border-box">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                    <div>
                        <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:5px;color:var(--text-2,#374151)">Champ *</label>
                        <select name="field_name" required style="width:100%;padding:9px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:.83rem;font-family:inherit">
                            <option value="email">Email</option>
                            <option value="phone">Téléphone</option>
                            <option value="source">Source</option>
                            <option value="estimated_value">Valeur estimée</option>
                            <option value="pipeline_stage_id">Étape pipeline</option>
                            <option value="next_action">Prochaine action</option>
                            <option value="notes">Notes</option>
                            <option value="created_days">Jours depuis création</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:5px;color:var(--text-2,#374151)">Opérateur *</label>
                        <select name="operator" required style="width:100%;padding:9px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:.83rem;font-family:inherit">
                            <option value="not_empty">N'est pas vide</option>
                            <option value="empty">Est vide</option>
                            <option value="equals">Égal à</option>
                            <option value="not_equals">Différent de</option>
                            <option value="greater_than">Supérieur à</option>
                            <option value="less_than">Inférieur à</option>
                            <option value="contains">Contient</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:20px">
                    <label style="display:block;font-size:.75rem;font-weight:600;margin-bottom:5px;color:var(--text-2,#374151)">Valeur (si applicable)</label>
                    <input type="text" name="field_value" placeholder="Laisser vide si non applicable" style="width:100%;padding:9px 12px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:.83rem;font-family:inherit;box-sizing:border-box">
                </div>
                <div style="display:flex;justify-content:flex-end;gap:8px;">
                    <button type="button" onclick="SCORING.modalClose()" class="scm-btn scm-btn-outline">Annuler</button>
                    <button type="submit" class="scm-btn scm-btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const SCORING = {
    apiUrl: '/admin/modules/scoring/api.php',
    _modalCb: null,

    // ── Tabs ───────────────────────────────────────────────
    showTab(tab) {
        document.querySelectorAll('.scm-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.scm-tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById('scmContent' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('active');
        document.getElementById('scmTab' + tab.charAt(0).toUpperCase() + tab.slice(1)).classList.add('active');
        try { sessionStorage.setItem('scm_tab', tab); } catch(e) {}
    },
    initTab() {
        let tab = 'leads';
        try { tab = sessionStorage.getItem('scm_tab') || 'leads'; } catch(e) {}
        // Ne switcher que si on n'a pas de paramètre d'URL forçant un tab
        if (new URLSearchParams(window.location.search).get('tab')) {
            tab = new URLSearchParams(window.location.search).get('tab');
        }
        if (tab !== 'leads') this.showTab(tab);
    },

    // ── Vue liste / grille ─────────────────────────────────
    setView(v) {
        const wrap = document.getElementById('scmWrap');
        wrap.classList.remove('scm-list-view', 'scm-grid-view');
        wrap.classList.add(v === 'grid' ? 'scm-grid-view' : 'scm-list-view');
        document.getElementById('scmBtnList').classList.toggle('active', v !== 'grid');
        document.getElementById('scmBtnGrid').classList.toggle('active', v === 'grid');
        try { sessionStorage.setItem('scm_view', v); } catch(e) {}
    },
    initView() {
        let v = 'list';
        try { v = sessionStorage.getItem('scm_view') || 'list'; } catch(e) {}
        this.setView(v);
    },

    // ── Toggle règle ───────────────────────────────────────
    async toggleRule(ruleId, btn) {
        const isOn = btn.classList.contains('on');
        const fd = new FormData();
        fd.append('action', 'toggle_rule');
        fd.append('rule_id', ruleId);
        fd.append('is_active', isOn ? 0 : 1);
        try {
            const r = await fetch(this.apiUrl, { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success) {
                btn.classList.toggle('on');
                document.getElementById('scmRule' + ruleId).classList.toggle('inactive');
                this.toast(isOn ? 'Règle désactivée' : 'Règle activée', 'success');
            } else {
                this.toast(d.error || 'Erreur', 'error');
            }
        } catch(e) { this.toast('Erreur réseau', 'error'); }
    },

    // ── Recalculer ─────────────────────────────────────────
    recalculate() {
        this.toast('Recalcul en cours…', 'info');
        setTimeout(() => {
            const url = new URL(window.location.href);
            url.searchParams.set('msg', 'recalculated');
            window.location.href = url.toString();
        }, 400);
    },

    // ── Modal confirm générique ────────────────────────────
    modal({ icon, iconBg, iconColor, title, msg, confirmLabel, confirmColor, onConfirm }) {
        document.getElementById('scmModalConfirmContent').style.display = 'block';
        document.getElementById('scmModalRuleContent').style.display    = 'none';
        const el  = document.getElementById('scmModal');
        const box = document.getElementById('scmModalBox');
        document.getElementById('scmModalIcon').innerHTML      = icon;
        document.getElementById('scmModalIcon').style.background = iconBg;
        document.getElementById('scmModalIcon').style.color     = iconColor;
        document.getElementById('scmModalHeader').style.background = iconBg + '33';
        document.getElementById('scmModalTitle').textContent   = title;
        document.getElementById('scmModalMsg').innerHTML       = msg;
        const btn = document.getElementById('scmModalConfirmBtn');
        btn.textContent      = confirmLabel || 'Confirmer';
        btn.style.background = confirmColor || '#6366f1';
        btn.onmouseover = () => btn.style.filter = 'brightness(.88)';
        btn.onmouseout  = () => btn.style.filter = '';
        this._modalCb = onConfirm;
        btn.onclick = () => { this.modalClose(); if (this._modalCb) this._modalCb(); };
        el.style.display = 'flex';
        requestAnimationFrame(() => { box.style.opacity = '1'; box.style.transform = 'scale(1) translateY(0)'; });
        document.addEventListener('keydown', this._escHandler);
    },

    // ── Modal add rule ─────────────────────────────────────
    openRuleModal() {
        document.getElementById('scmModalConfirmContent').style.display = 'none';
        document.getElementById('scmModalRuleContent').style.display    = 'block';
        const el  = document.getElementById('scmModal');
        const box = document.getElementById('scmModalBox');
        el.style.display = 'flex';
        requestAnimationFrame(() => { box.style.opacity = '1'; box.style.transform = 'scale(1) translateY(0)'; });
        document.addEventListener('keydown', this._escHandler);

        document.getElementById('scmRuleForm').onsubmit = async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('action', 'add_rule');
            try {
                const r = await fetch(this.apiUrl, { method: 'POST', body: fd });
                const d = await r.json();
                if (d.success) {
                    this.toast('Règle ajoutée ✓', 'success');
                    this.modalClose();
                    setTimeout(() => location.reload(), 700);
                } else { this.toast(d.error || 'Erreur', 'error'); }
            } catch(err) { this.toast('Erreur réseau', 'error'); }
        };
    },

    modalClose() {
        const el  = document.getElementById('scmModal');
        const box = document.getElementById('scmModalBox');
        box.style.opacity = '0'; box.style.transform = 'scale(.94) translateY(8px)';
        setTimeout(() => el.style.display = 'none', 160);
        document.removeEventListener('keydown', this._escHandler);
    },
    _escHandler(e) { if (e.key === 'Escape') SCORING.modalClose(); },

    // ── Toast ──────────────────────────────────────────────
    toast(msg, type = 'success') {
        const colors = { success: '#059669', error: '#dc2626', info: '#3b82f6' };
        const icons  = { success: '✓', error: '✕', info: 'ℹ' };
        const t = document.createElement('div');
        t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:10000;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:10px;font-size:.83rem;font-weight:600;color:#111827;box-shadow:0 8px 24px rgba(0,0,0,.12);transform:translateY(20px);opacity:0;transition:all .25s;';
        t.innerHTML = `<span style="width:22px;height:22px;border-radius:50%;background:${colors[type]}22;color:${colors[type]};display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800">${icons[type]}</span>${msg}`;
        document.body.appendChild(t);
        requestAnimationFrame(() => { t.style.opacity = '1'; t.style.transform = 'translateY(0)'; });
        setTimeout(() => { t.style.opacity = '0'; t.style.transform = 'translateY(10px)'; setTimeout(() => t.remove(), 250); }, 3200);
    }
};

document.addEventListener('DOMContentLoaded', () => {
    SCORING.initView();
    SCORING.initTab();
});
document.getElementById('scmModal').addEventListener('click', function(e) {
    if (e.target === this) SCORING.modalClose();
});
</script>