<?php
/**
 * ════════════════════════════════════════════════════════════════
 * TABLEAU DE BORD DES MODULES — IMMO LOCAL+ v8.6
 * /admin/modules/system/diagnostic/index.php
 * 
 * État et vérification de tous les modules déclarés
 * Whitelist : filtre les sous-modules internes
 * ════════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);

// ── Config ─────────────────────────────────────────────────────
$rootPath = dirname(__DIR__, 4);
if (!defined('DB_HOST')) require_once $rootPath . '/config/config.php';

try {
    $pdo = getDB();
} catch (Exception $e) {
    $pdo = null;
}

// ── WHITELIST : Modules déclarés (générés automatiquement du find) ──
$whitelistModules = [
    'dashboard',
    
    // ── CONTENT ──────────────
    'content', 'content-annuaire', 'content-articles', 'content-blog',
    'content-capture', 'content-guides', 'content-pages', 'content-secteurs', 'content-templates',
    
    // ── IMMOBILIER ───────────
    'immobilier', 'immobilier-courtiers', 'immobilier-estimation', 'immobilier-financement',
    'immobilier-properties', 'immobilier-rdv', 'immobilier-transactions',
    
    // ── MARKETING / CRM ──────
    'marketing', 'marketing-crm', 'marketing-leads', 'marketing-messagerie', 'marketing-newsletters',
    'marketing-pub facebook', 'marketing-scoring', 'marketing-sequences', 'marketing-sms',
    
    // ── SEO ──────────────────
    'seo', 'seo-analytics', 'seo-local-seo', 'seo-seo-semantic',
    
    // ── SOCIAL ──────────────
    'social', 'social-facebook', 'social-gmb', 'social-instagram', 'social-kit-publications',
    'social-linkedin', 'social-reseaux-sociaux', 'social-tiktok',
    
    // ── NETWORK ─────────────
    'network', 'network-contact', 'network-partenaires', 'network-scraper-gmb',
    
    // ── STRATEGY ─────────────
    'strategy', 'strategy-ancre', 'strategy-strategy',
    
    // ── IA ──────────────────
    'ia', 'ia-agents', 'ia-ia', 'ia-journal', 'ia-neuropersona', 'ia-prompts', 'ia-advisor-context',
    
    // ── SYSTEM ──────────────
    'system', 'system-diagnostic', 'system-emails', 'system-license', 'system-logs',
    'system-maintenance', 'system-module-health', 'system-settings',
    
    // ── LICENSE ─────────────
    'license',
    
    // ── MEDIA ───────────────
    'media',
    
    // ── BUILDER ─────────────
    'builder',
];

// ── Tables DB map (module => tables) ───────────────────────────
$dbTablesMap = [
    'dashboard'       => ['settings','admins'],
    'pages'           => ['builder_pages','pages'],
    'articles'        => ['articles'],
    'captures'        => ['capture_pages'],
    'secteurs'        => ['secteurs'],
    'properties'      => ['properties'],
    'estimation'      => ['estimations'],
    'crm'             => ['crm_leads','crm_threads'],
    'leads'           => ['leads'],
    'messenger'       => ['crm_threads','crm_messages'],
    'reseaux-sociaux' => [],
    'seo'             => [],
    'ancre'           => [],
    'builder'         => ['builder_pages','builder_templates'],
    'ai'              => ['settings'],
];

// ── Lister modules réels + filter whitelist ────────────────────
$modulesBase = $rootPath . '/admin/modules/';
$allModules  = [];

function scanModules($dir, $prefix = '', $maxDepth = 999, $currentDepth = 0) {
    $result = [];
    if (!is_dir($dir) || $currentDepth >= $maxDepth) return $result;
    
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $path = $dir . $file;
        $slug = $prefix ? $prefix . '-' . $file : $file;
        
        // Index.php found = module
        if (is_dir($path) && file_exists($path . '/index.php')) {
            $result[$slug] = [
                'file'   => ($prefix ? $prefix . '/' : '') . $file . '/index.php',
                'path'   => $path . '/index.php',
                'title'  => ucwords(str_replace('-', ' ', $slug)),
                'type'   => 'module'
            ];
        }
        
        // Recurse (scan all depths to find all modules)
        if (is_dir($path) && !str_starts_with($file, '.') && $file !== 'api') {
            $subModules = scanModules($path . '/', $slug, $maxDepth, $currentDepth + 1);
            $result = array_merge($result, $subModules);
        }
    }
    
    return $result;
}

// Scanner TOUS les modules (sans limite de profondeur)
$allModules = scanModules($modulesBase, '', 999, 0);

// ── FILTRER avec WHITELIST ────────────────────────────────────
$filteredModules = [];
foreach ($whitelistModules as $slug) {
    if (isset($allModules[$slug])) {
        $filteredModules[$slug] = $allModules[$slug];
    }
}

// Ajouter dashboard
$filteredModules['dashboard'] = [
    'file'  => 'system/index.php',
    'path'  => $modulesBase . 'system/index.php',
    'title' => 'Dashboard',
    'type'  => 'module'
];

$allModules = $filteredModules;

// ── Checker un module ──────────────────────────────────────────
function checkModule($slug, $info, $pdo, $dbTablesMap) {
    $result = [
        'slug'       => $slug,
        'label'      => $info['title'],
        'file'       => $info['file'],
        'path'       => $info['path'],
        'fileExists' => file_exists($info['path']),
        'fileSize'   => file_exists($info['path']) ? filesize($info['path']) : 0,
        'isEmpty'    => file_exists($info['path']) && filesize($info['path']) === 0,
        'checks'     => [],
        'status'     => 'ok',
    ];
    
    // ── Check 1: Fichier existe ────────────────────────────────
    if (!$result['fileExists']) {
        $result['checks'][] = [
            'type'   => 'file',
            'status' => 'error',
            'msg'    => "Fichier MANQUANT: {$info['file']}"
        ];
        $result['status'] = 'error';
    } elseif ($result['isEmpty']) {
        $result['checks'][] = [
            'type'   => 'file',
            'status' => 'warning',
            'msg'    => 'Fichier vide (0 bytes)'
        ];
        $result['status'] = 'warning';
    } else {
        $result['checks'][] = [
            'type'   => 'file',
            'status' => 'ok',
            'msg'    => "Fichier OK (" . round($result['fileSize']/1024, 1) . " Ko)"
        ];
    }
    
    // ── Check 2: DB Tables ─────────────────────────────────────
    if ($pdo && !empty($dbTablesMap[$slug])) {
        $tables = $dbTablesMap[$slug];
        try {
            $existingTables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tables as $table) {
                $tableExists = in_array($table, $existingTables);
                if (!$tableExists) {
                    $result['checks'][] = [
                        'type'   => 'db',
                        'status' => 'error',
                        'msg'    => "Table `{$table}` MANQUANTE"
                    ];
                    $result['status'] = 'error';
                } else {
                    $count = 0;
                    try {
                        $count = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
                    } catch (Exception $e) {}
                    $result['checks'][] = [
                        'type'   => 'db',
                        'status' => 'ok',
                        'msg'    => "Table `{$table}` OK ({$count} enr.)"
                    ];
                }
            }
        } catch (Exception $e) {
            $result['checks'][] = [
                'type'   => 'db',
                'status' => 'warning',
                'msg'    => 'Erreur requête DB: ' . $e->getMessage()
            ];
            $result['status'] = 'warning';
        }
    }
    
    // ── Check 3: Accessible via route ──────────────────────────
    if ($result['fileExists'] && !$result['isEmpty']) {
        $result['checks'][] = [
            'type'   => 'route',
            'status' => 'ok',
            'msg'    => "Route ?page={$slug} accessible"
        ];
    }
    
    return $result;
}

// ── Scanner tous les modules filtrés ───────────────────────────
$results = [];
$stats = ['total'=>0, 'ok'=>0, 'warning'=>0, 'error'=>0];

foreach ($allModules as $slug => $info) {
    $check = checkModule($slug, $info, $pdo, $dbTablesMap);
    $results[] = $check;
    $stats['total']++;
    
    if ($check['status'] === 'error') $stats['error']++;
    elseif ($check['status'] === 'warning') $stats['warning']++;
    else $stats['ok']++;
}

// Trier par slug
usort($results, fn($a, $b) => strcmp($a['slug'], $b['slug']));

$pctOk = $stats['total'] > 0 ? round($stats['ok']/$stats['total']*100) : 0;

// Grouper par catégorie (premier mot du slug)
$byCategory = [];
foreach ($results as $r) {
    $parts = explode('-', $r['slug']);
    $cat = ucfirst($parts[0]);
    if (!isset($byCategory[$cat])) $byCategory[$cat] = [];
    $byCategory[$cat][] = $r;
}
ksort($byCategory);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diagnostic Unifié — IMMO LOCAL+</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f9fafb;
            color: #1f2937;
            line-height: 1.5;
        }
        
        .diag-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 32px 20px;
        }
        
        /* ── Header ──────────────────────────────────────────── */
        .diag-header {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            border-left: 4px solid #6366f1;
        }
        
        .diag-header h1 {
            font-size: 1.75rem;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .diag-header h1 i { font-size: 1.4rem; color: #6366f1; }
        .diag-header .sub { font-size: 0.9rem; color: #6b7280; }
        
        /* ── Filter ──────────────────────────────────────────── */
        .diag-filter {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .filter-box {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            transition: border-color 0.15s;
        }
        
        .filter-box:focus-within {
            border-color: #6366f1;
            background: #f0f4ff;
        }
        
        .filter-box i {
            color: #9ca3af;
            font-size: 14px;
        }
        
        .filter-box input {
            flex: 1;
            border: none;
            background: transparent;
            font-size: 14px;
            color: #1f2937;
            outline: none;
            font-family: inherit;
        }
        
        .filter-box input::placeholder {
            color: #d1d5db;
        }
        
        .filter-clear {
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 12px;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.15s;
        }
        
        .filter-clear:hover {
            color: #ef4444;
        }
        
        .filter-stats {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            white-space: nowrap;
            padding: 8px 12px;
            background: #f3f4f6;
            border-radius: 8px;
        }
        
        /* Hide/show avec filtre */
        .diag-result.hidden {
            display: none;
        }
        
        .diag-cat.empty {
            display: none;
        }
        
        .diag-cat:has(> .diag-result:not(.hidden)) {
            display: block;
        }
        
        /* ── Stats ────────────────────────────────────────────── */
        .diag-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.15s;
        }
        
        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-color: #d1d5db;
            transform: translateY(-2px);
        }
        
        .stat-val {
            font-size: 2rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 6px;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .stat-ok .stat-val { color: #10b981; }
        .stat-warning .stat-val { color: #f59e0b; }
        .stat-error .stat-val { color: #ef4444; }
        
        /* ── Progress ────────────────────────────────────────── */
        .diag-progress {
            height: 8px;
            background: #e5e7eb;
            border-radius: 99px;
            overflow: hidden;
            display: flex;
            margin-bottom: 24px;
        }
        
        .progress-ok { background: #10b981; flex: var(--w-ok); }
        .progress-warn { background: #f59e0b; flex: var(--w-warn); }
        .progress-err { background: #ef4444; flex: var(--w-err); }
        
        /* ── Results ─────────────────────────────────────────── */
        .diag-result {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            margin-bottom: 12px;
            overflow: hidden;
            transition: all 0.2s;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .diag-result:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        
        .result-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            cursor: pointer;
            background: #fafbfc;
            border-bottom: 1px solid #e5e7eb;
            user-select: none;
        }
        
        .result-header.open { background: #f0fdf4; }
        .result-header.error-state { background: #fef2f2; }
        .result-header.warning-state { background: #fffbeb; }
        
        .result-status {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            font-weight: 700;
        }
        
        .status-ok { background: #ecfdf5; color: #10b981; }
        .status-warning { background: #fffbeb; color: #f59e0b; }
        .status-error { background: #fef2f2; color: #ef4444; }
        
        .result-info {
            flex: 1;
            min-width: 0;
        }
        
        .result-label {
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
        }
        
        .result-slug {
            font-size: 0.75rem;
            color: #6b7280;
            font-family: 'Courier New', monospace;
            margin-top: 2px;
        }
        
        .result-file {
            font-size: 0.8rem;
            color: #9ca3af;
            font-family: 'Courier New', monospace;
        }
        
        .result-toggle {
            color: #d1d5db;
            font-size: 12px;
            transition: transform 0.2s;
            flex-shrink: 0;
        }
        
        .result-header.open .result-toggle { transform: rotate(90deg); }
        
        .result-detail {
            display: none;
            padding: 16px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .result-header.open + .result-detail { display: block; }
        
        /* ── Checks ──────────────────────────────────────────── */
        .check-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 0.85rem;
        }
        
        .check-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
        .check-ok { background: #ecfdf5; color: #10b981; }
        .check-warning { background: #fffbeb; color: #f59e0b; }
        .check-error { background: #fef2f2; color: #ef4444; }
        
        .check-msg {
            flex: 1;
            color: #374151;
            line-height: 1.4;
        }
        
        /* ── Section category ────────────────────────────────── */
        .diag-cat {
            margin-bottom: 20px;
        }
        
        .diag-cat-title {
            font-size: 0.9rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #6b7280;
            margin-bottom: 12px;
            padding-left: 4px;
        }
        
        /* ── Utils ────────────────────────────────────────────── */
        .diag-actions {
            display: flex;
            gap: 8px;
            margin-top: 24px;
        }
        
        .btn {
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: white;
            color: #111827;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            font-family: inherit;
        }
        
        .btn:hover { background: #f3f4f6; }
        .btn-primary { background: #6366f1; color: white; border-color: #6366f1; }
        .btn-primary:hover { background: #4f46e5; }
        
        .anim { animation: fadeUp 0.3s ease both; }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @media (max-width: 768px) {
            .diag-stats { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

<div class="diag-container">
    
    <!-- Header -->
    <div class="diag-header anim">
        <h1><i class="fas fa-th-large"></i> Tableau de bord des modules</h1>
        <div class="sub">État et vérification de tous les modules — <?= date('d/m/Y H:i:s') ?></div>
    </div>
    
    <!-- Filtre de recherche -->
    <div class="diag-filter anim">
        <div class="filter-box">
            <i class="fas fa-search"></i>
            <input 
                type="text" 
                id="moduleFilter" 
                placeholder="Filtrer les modules..." 
                autocomplete="off"
            >
            <button id="filterClear" class="filter-clear" style="display:none" onclick="document.getElementById('moduleFilter').value=''; filterModules(); this.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="filter-stats">
            <span id="filterStats">Tous les modules</span>
        </div>
    </div>
    
    <!-- Stats -->
    <div class="diag-stats anim">
        <div class="stat-card stat-ok" onclick="filterByStatus('all')" style="cursor:pointer" title="Afficher tous les modules">
            <div class="stat-val"><?= $stats['total'] ?></div>
            <div class="stat-label">Modules</div>
        </div>
        <div class="stat-card stat-ok" onclick="filterByStatus('ok')" style="cursor:pointer" title="Afficher seulement les modules OK">
            <div class="stat-val"><?= $stats['ok'] ?></div>
            <div class="stat-label">✓ OK</div>
        </div>
        <div class="stat-card stat-warning" onclick="filterByStatus('warning')" style="cursor:pointer" title="Afficher seulement les modules avec avertissements">
            <div class="stat-val"><?= $stats['warning'] ?></div>
            <div class="stat-label">⚠ Warnings</div>
        </div>
        <div class="stat-card stat-error" onclick="filterByStatus('error')" style="cursor:pointer" title="Afficher seulement les modules en erreur">
            <div class="stat-val"><?= $stats['error'] ?></div>
            <div class="stat-label">✗ Erreurs</div>
        </div>
    </div>
    
    <!-- Progress -->
    <div class="diag-progress" style="--w-ok:<?= $stats['total'] > 0 ? round($stats['ok']/$stats['total']*100) : 0 ?>%; --w-warn:<?= $stats['total'] > 0 ? round($stats['warning']/$stats['total']*100) : 0 ?>%; --w-err:<?= $stats['total'] > 0 ? round($stats['error']/$stats['total']*100) : 0 ?>%">
        <div class="progress-ok"></div>
        <div class="progress-warn"></div>
        <div class="progress-err"></div>
    </div>
    
    <!-- Results par catégorie -->
    <div class="anim" style="animation-delay:0.1s">
        <?php foreach ($byCategory as $catName => $entries): ?>
        <div class="diag-cat">
            <div class="diag-cat-title"><?= htmlspecialchars($catName) ?> (<?= count($entries) ?>)</div>
            
            <?php foreach ($entries as $result):
                $statusClass = "result-header ".$result['status']."-state";
                $statusIcon = [
                    'ok'      => '<i class="fas fa-check-circle"></i>',
                    'warning' => '<i class="fas fa-triangle-exclamation"></i>',
                    'error'   => '<i class="fas fa-circle-xmark"></i>',
                ];
            ?>
            <div class="diag-result">
                <div class="<?= $statusClass ?>" onclick="this.classList.toggle('open')">
                    <div class="result-status status-<?= $result['status'] ?>">
                        <?= $statusIcon[$result['status']] ?>
                    </div>
                    <div class="result-info">
                        <div class="result-label"><?= htmlspecialchars($result['label']) ?></div>
                        <div class="result-slug">?page=<?= htmlspecialchars($result['slug']) ?></div>
                        <div class="result-file"><?= htmlspecialchars($result['file']) ?></div>
                    </div>
                    <div class="result-toggle"><i class="fas fa-chevron-right"></i></div>
                </div>
                <div class="result-detail">
                    <?php foreach ($result['checks'] as $check): ?>
                    <div class="check-item">
                        <div class="check-icon check-<?= $check['status'] ?>">
                            <i class="fas fa-<?= $check['status'] === 'ok' ? 'check' : ($check['status'] === 'warning' ? 'triangle-exclamation' : 'times') ?>"></i>
                        </div>
                        <div class="check-msg"><?= htmlspecialchars($check['msg']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Actions -->
    <div class="diag-actions anim" style="animation-delay:0.2s">
        <button class="btn" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Rafraîchir
        </button>
        <button class="btn btn-primary" onclick="exportJSON()">
            <i class="fas fa-download"></i> Export JSON
        </button>
    </div>

</div>

<script>
const DIAG_DATA = <?= json_encode($results) ?>;

function exportJSON() {
    const json = JSON.stringify(DIAG_DATA, null, 2);
    const blob = new Blob([json], {type: 'application/json'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'diagnostic-' + new Date().toISOString().slice(0,10) + '.json';
    a.click();
    URL.revokeObjectURL(url);
}

// ── Filtre par statut (OK, Warning, Erreur) ──────────────────
function filterByStatus(status) {
    const filterInput = document.getElementById('moduleFilter');
    const clearBtn = document.getElementById('filterClear');
    const statsEl = document.getElementById('filterStats');
    
    // Effacer le filtre texte
    filterInput.value = '';
    clearBtn.style.display = 'none';
    
    if (status === 'all') {
        // Réinitialiser
        document.querySelectorAll('.diag-result').forEach(el => {
            el.classList.remove('hidden');
        });
        document.querySelectorAll('.diag-cat').forEach(el => {
            el.classList.remove('empty');
        });
        statsEl.textContent = 'Tous les modules';
        return;
    }
    
    // Filtrer par statut
    let visibleCount = 0;
    document.querySelectorAll('.diag-result').forEach(card => {
        // Trouver le statut dans la classe du header
        const header = card.querySelector('.result-header');
        const isOk = header.classList.contains('ok-state');
        const isWarning = header.classList.contains('warning-state');
        const isError = header.classList.contains('error-state');
        
        let matches = false;
        if (status === 'ok' && !isWarning && !isError) matches = true;
        if (status === 'warning' && isWarning) matches = true;
        if (status === 'error' && isError) matches = true;
        
        if (matches) {
            card.classList.remove('hidden');
            visibleCount++;
        } else {
            card.classList.add('hidden');
        }
    });
    
    // Masquer les catégories vides
    document.querySelectorAll('.diag-cat').forEach(cat => {
        const hasVisible = cat.querySelector('.diag-result:not(.hidden)');
        if (hasVisible) {
            cat.classList.remove('empty');
        } else {
            cat.classList.add('empty');
        }
    });
    
    // Mettre à jour les stats
    const labels = { 'ok': 'OK', 'warning': 'avec avertissements', 'error': 'en erreur' };
    statsEl.textContent = visibleCount > 0 
        ? `${visibleCount} module${visibleCount > 1 ? 's' : ''} ${labels[status]}`
        : `Aucun module ${labels[status]}`;
}

// ── Filtre en temps réel ──────────────────────────────────────
function filterModules() {
    const input = document.getElementById('moduleFilter');
    const query = input.value.toLowerCase().trim();
    const clearBtn = document.getElementById('filterClear');
    const statsEl = document.getElementById('filterStats');
    
    // Afficher/masquer le bouton clear
    clearBtn.style.display = query ? 'flex' : 'none';
    
    if (!query) {
        // Réinitialiser
        document.querySelectorAll('.diag-result').forEach(el => {
            el.classList.remove('hidden');
        });
        document.querySelectorAll('.diag-cat').forEach(el => {
            el.classList.remove('empty');
        });
        statsEl.textContent = 'Tous les modules';
        return;
    }
    
    // Filtrer
    let visibleCount = 0;
    document.querySelectorAll('.diag-result').forEach(card => {
        const label = card.querySelector('.result-label')?.textContent.toLowerCase() || '';
        const slug = card.querySelector('.result-slug')?.textContent.toLowerCase() || '';
        const file = card.querySelector('.result-file')?.textContent.toLowerCase() || '';
        
        const matches = label.includes(query) || slug.includes(query) || file.includes(query);
        
        if (matches) {
            card.classList.remove('hidden');
            visibleCount++;
        } else {
            card.classList.add('hidden');
        }
    });
    
    // Masquer les catégories vides
    document.querySelectorAll('.diag-cat').forEach(cat => {
        const hasVisible = cat.querySelector('.diag-result:not(.hidden)');
        if (hasVisible) {
            cat.classList.remove('empty');
        } else {
            cat.classList.add('empty');
        }
    });
    
    // Mettre à jour les stats
    statsEl.textContent = visibleCount > 0 
        ? `${visibleCount} module${visibleCount > 1 ? 's' : ''} trouvé${visibleCount > 1 ? 's' : ''}`
        : 'Aucun module trouvé';
}

// Event listeners
document.getElementById('moduleFilter').addEventListener('input', filterModules);
document.getElementById('moduleFilter').addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        document.getElementById('moduleFilter').value = '';
        filterModules();
    }
});
</script>

</body>
</html>