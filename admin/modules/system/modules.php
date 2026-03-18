<?php
/**
 * /admin/modules/system/modules.php — v5
 * Gestionnaire de Modules — IMMO LOCAL+
 *
 * v5 : Filtres avancés multi-critères, vue Liste + Grille + Kanban,
 *       tags sujets (CRM/SEO/IA…), filtre statut inline, recherche temps-réel
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);

ob_start();

$rootPath = dirname(__DIR__, 3);
if (!defined('DB_HOST')) require_once $rootPath . '/config/config.php';
if (!class_exists('Database')) require_once $rootPath . '/includes/classes/Database.php';

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    ob_end_clean();
    die('<div style="padding:20px;color:#dc2626;font-family:monospace">&#10060; DB: ' . htmlspecialchars($e->getMessage()) . '</div>');
}

if (!class_exists('ModuleDiagnostic')) {
   require_once dirname(__DIR__, 2) . '/includes/classes/ModuleDiagnostic.php';
}

// ── AJAX ──────────────────────────────────────────────
$rawBody    = file_get_contents('php://input');
$jsonBody   = json_decode($rawBody, true);
$ajaxAction = $_POST['ajax_action'] ?? ($jsonBody['ajax_action'] ?? null);

if ($ajaxAction) {
    header('Content-Type: application/json; charset=utf-8');
    ob_end_clean();

    if ($ajaxAction === 'toggle') {
        $slug   = preg_replace('/[^a-z0-9_-]/', '', $_POST['module'] ?? '');
        $enable = ($_POST['enable'] ?? '0') === '1';
        $f      = $rootPath . '/config/module-states.json';
        $states = file_exists($f) ? (json_decode(file_get_contents($f), true) ?? []) : [];
        $states[$slug] = ['enabled' => $enable, 'updated_at' => date('Y-m-d H:i:s')];
        file_put_contents($f, json_encode($states, JSON_PRETTY_PRINT));
        echo json_encode(['success' => true, 'module' => $slug, 'enabled' => $enable]);
        exit;
    }

    if ($ajaxAction === 'ai_proxy') {
        $apiKey = '';
        try {
            $r = $db->query("SELECT setting_value FROM settings WHERE setting_key='anthropic_api_key' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($r) $apiKey = trim($r['setting_value']);
        } catch (Exception $e) {}
        if (!$apiKey && defined('ANTHROPIC_API_KEY')) $apiKey = ANTHROPIC_API_KEY;

        if (!$apiKey) { echo json_encode(['error' => 'Cl&eacute; API Anthropic non configur&eacute;e.']); exit; }

        unset($jsonBody['ajax_action']);
        $payload = $jsonBody ?: ['model' => 'claude-sonnet-4-20250514', 'max_tokens' => 1000, 'messages' => []];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json','x-api-key: '.$apiKey,'anthropic-version: 2023-06-01'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_TIMEOUT        => 45,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);
        if ($curlErr) { echo json_encode(['error' => 'cURL: '.$curlErr]); exit; }
        http_response_code($httpCode);
        echo $response;
        exit;
    }

    echo json_encode(['error' => 'Action inconnue']);
    exit;
}

// ── Diagnostic ────────────────────────────────────────
$modulesBasePath = realpath(__DIR__ . '/../');
try {
    $diagnostic = new ModuleDiagnostic($db, $modulesBasePath);
    $report     = $diagnostic->runFullDiagnostic();
} catch (Exception $e) {
    $report = ['timestamp'=>date('Y-m-d H:i:s'),'summary'=>['total'=>0,'ok'=>0,'warning'=>0,'error'=>0],'db_health'=>[['check'=>'Erreur diagnostic','status'=>'error','value'=>$e->getMessage()]],'modules'=>[]];
}

$summary    = $report['summary'];
$modules    = $report['modules'];
$dbHealth   = $report['db_health'];
$scanDate   = $report['timestamp'];
$countOk    = $summary['ok'];
$countWarn  = $summary['warning'];
$countErr   = $summary['error'];
$total      = $summary['total'];
$scorePct   = $total > 0 ? round(($countOk / $total) * 100) : 0;
$scoreColor = $scorePct >= 70 ? 'var(--green)' : ($scorePct >= 40 ? 'var(--amber)' : 'var(--red)');

// Grouper par catégorie
$categories = [];
foreach ($modules as $slug => $mod) {
    $categories[$mod['category']][$slug] = $mod;
}
$catOrder = ['CRM','CMS','Immobilier','SEO','Marketing','Social','IA','Strat&eacute;gie','Syst&egrave;me','Network','Non r&eacute;f&eacute;renc&eacute;'];
uksort($categories, function($a, $b) use ($catOrder) {
    $ia = array_search($a, $catOrder); $ib = array_search($b, $catOrder);
    return ($ia === false ? 99 : $ia) - ($ib === false ? 99 : $ib);
});

$dbTableCount = 0;
try { $dbTableCount = (int)$db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()")->fetchColumn(); } catch(Exception $e) {}

$statesFile = $rootPath . '/config/module-states.json';
$states     = file_exists($statesFile) ? (json_decode(file_get_contents($statesFile), true) ?? []) : [];

$tableDescriptions = [
    'leads'              => 'Contacts et prospects captur&eacute;s via formulaires, estimation ou landing pages',
    'builder_pages'      => 'Pages CMS cr&eacute;&eacute;es avec le Builder Pro (HTML, CSS, JS, m&eacute;tadonn&eacute;es SEO)',
    'builder_sections'   => 'Sections r&eacute;utilisables entre pages (blocs, composants modulaires)',
    'builder_templates'  => 'Templates pr&eacute;d&eacute;finis disponibles &agrave; la cr&eacute;ation de nouvelles pages',
    'properties'         => 'Biens immobiliers (vente/location) avec prix, surface, photos, statut',
    'capture_pages'      => 'Pages de capture A/B avec conversion, stats et leads associ&eacute;s',
    'articles'           => 'Articles de blog avec contenu SEO, images, cat&eacute;gories et statut publication',
    'secteurs'           => 'Quartiers et secteurs g&eacute;ographiques avec contenu local SEO',
    'settings'           => 'Param&egrave;tres globaux de la plateforme (SMTP, API keys, identit&eacute; site)',
    'admins'             => 'Comptes administrateurs avec r&ocirc;les et permissions d\'acc&egrave;s',
    'api_keys'           => 'Cl&eacute;s API tierces chiffr&eacute;es (Anthropic, OpenAI, Google, Facebook&hellip;)',
    'gmb_contacts'       => 'Contacts Google My Business prospect&eacute;s via le scraper GMB',
    'gmb_sequences'      => 'S&eacute;quences d\'emails automatis&eacute;es pour la prospection GMB B2B',
];

// ── Inventaire ────────────────────────────────────────
function buildInventory(string $root): array {
    $excludeDirs = ['.git','node_modules','vendor','.svn','cache','tmp','logs','.well-known','cgi-bin','uploads'];
    $totalDirs=0; $totalFiles=0; $emptyFiles=[]; $dirCards=[];
    $dirIter = new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS);
    $iter    = new RecursiveIteratorIterator($dirIter, RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iter as $node) {
        $rel=str_replace($root.'/','',$node->getPathname()); $parts=explode('/',$rel); $skip=false;
        foreach ($excludeDirs as $ex) { if(in_array($ex,$parts)){$skip=true;break;} }
        if($skip) continue;
        if($node->isDir()){$totalDirs++;$dirRel=str_replace($root.'/','', $node->getPathname());if(!isset($dirCards[$dirRel]))$dirCards[$dirRel]=['files'=>0,'empty'=>0,'size_bytes'=>0];}
        elseif($node->isFile()){$size=$node->getSize();$dirRel=str_replace($root.'/','', $node->getPath());$totalFiles++;if(!isset($dirCards[$dirRel]))$dirCards[$dirRel]=['files'=>0,'empty'=>0,'size_bytes'=>0];$dirCards[$dirRel]['files']++;$dirCards[$dirRel]['size_bytes']+=$size;if($size===0){$dirCards[$dirRel]['empty']++;$emptyFiles[]=['path'=>str_replace($root.'/','', $node->getPathname()),'dir'=>$dirRel,'name'=>$node->getFilename(),'ext'=>$node->getExtension()];}}
    }
    arsort($dirCards);
    return ['total_dirs'=>$totalDirs,'total_files'=>$totalFiles,'empty_count'=>count($emptyFiles),'empty_files'=>$emptyFiles,'top_dirs'=>array_slice($dirCards,0,20,true),'all_dirs'=>$dirCards];
}
$inventory = buildInventory($rootPath);

// ── Contexte IA ───────────────────────────────────────
$modsOk   = implode(', ', array_keys(array_filter($modules, fn($m) => $m['status'] === 'ok')));
$modsWarn = implode(', ', array_keys(array_filter($modules, fn($m) => $m['status'] === 'warning')));
$modsErr  = implode(', ', array_keys(array_filter($modules, fn($m) => $m['status'] === 'error')));
$modDetails = "";
foreach ($modules as $slug => $mod) {
    if ($mod['status'] === 'ok') continue;
    $modDetails .= "\n[{$slug}] status={$mod['status']} cat={$mod['category']}\n";
    foreach ($mod['checks']??[] as $ck) { if($ck['status']!=='ok') $modDetails.="  &#9888; {$ck['message']}\n"; }
}
$handlersPath  = $rootPath.'/admin/core/handlers';
$handlersExist = is_dir($handlersPath) ? array_filter(scandir($handlersPath),fn($f)=>str_ends_with($f,'.php')) : [];
$apiPath=$rootPath.'/admin/api'; $apiFiles=[];
if(is_dir($apiPath)){$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($apiPath));foreach($it as $f){if($f->isFile()&&$f->getExtension()==='php')$apiFiles[]=str_replace($rootPath.'/','',$f->getPathname());}}

$aiContext = "=== DIAGNOSTIC R&Eacute;EL &Eacute;COSYST&Egrave;ME IMMO LOCAL+ ===\nSite: eduardo-desul-immobilier.fr | Scan: {$scanDate}\nScore sant&eacute;: {$scorePct}% | {$countOk} OK / {$countWarn} warnings / {$countErr} erreurs / {$total} modules\nDB: {$dbTableCount} tables MySQL | Serveur: 02switch cPanel | PHP ".PHP_VERSION."\nInventaire: {$inventory['total_dirs']} dossiers &middot; {$inventory['total_files']} fichiers &middot; {$inventory['empty_count']} fichiers vides\n\n--- MODULES OK ({$countOk}) ---\n".$modsOk."\n--- MODULES EN WARNING ({$countWarn}) ---\n".$modsWarn."\n--- MODULES EN ERREUR ({$countErr}) ---\n".$modsErr."\n--- D&Eacute;TAIL DES PROBL&Egrave;MES ---".$modDetails."\n--- HANDLERS PHP ---\n".implode(', ',$handlersExist)."\n--- FICHIERS API ---\n".implode(', ',$apiFiles)."\n=== FIN ===";

$jsonExport = json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
$proxyUrl = '/admin/dashboard.php?page=system/modules';

$catColors = [
    'CRM'=>'#ec4899','CMS'=>'#3b82f6','Immobilier'=>'#10b981','SEO'=>'#f59e0b',
    'Marketing'=>'#06b6d4','Social'=>'#8b5cf6','IA'=>'#6366f1',
    'Strat&eacute;gie'=>'#f97316','Syst&egrave;me'=>'#64748b','Network'=>'#14b8a6','Non r&eacute;f&eacute;renc&eacute;'=>'#94a3b8',
];

// Collecter toutes les catégories existantes pour les tags de filtre
$allCats = array_keys($categories);

ob_end_clean();
?>

<style>
/* ══ Modules v5 ══════════════════════════════════════════════════ */
/* Tabs principale */
.mod-tabs { display:flex; gap:0; border-bottom:2px solid var(--border); margin-bottom:20px; }
.mod-tab { padding:9px 18px; border:none; background:transparent; color:var(--text-3); cursor:pointer; font-size:12px; font-weight:700; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all .14s; display:flex; align-items:center; gap:6px; font-family:var(--font); }
.mod-tab:hover { color:var(--text-2); }
.mod-tab.active { color:var(--accent); border-bottom-color:var(--accent); }

/* Score cards */
.mod-scores { display:grid; grid-template-columns:repeat(5,1fr); gap:10px; margin-bottom:16px; }
.mod-score-main { background:var(--surface); border:2px solid var(--accent); border-radius:var(--radius-lg); padding:16px; display:flex; flex-direction:column; align-items:center; justify-content:center; box-shadow:var(--shadow-sm); }
.mod-score-main .pct { font-size:28px; font-weight:900; line-height:1; }
.mod-score-main .lbl { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-3); margin-top:3px; }

.mod-progress { height:6px; border-radius:99px; background:var(--surface-3); display:flex; overflow:hidden; margin-bottom:20px; }
.mp-ok{background:var(--green)}.mp-wa{background:var(--amber)}.mp-er{background:var(--red)}

/* ════ BARRE DE FILTRES AVANCÉE ════ */
.filter-bar {
    background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg);
    padding:14px 16px; margin-bottom:14px; display:flex; flex-direction:column; gap:10px;
    box-shadow:var(--shadow-sm);
}
.filter-bar-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.filter-bar-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-3); min-width:56px; flex-shrink:0; }

/* Chips filtre */
.fc { display:inline-flex; align-items:center; gap:4px; padding:4px 10px; border-radius:99px; border:1px solid var(--border); background:var(--surface-2); color:var(--text-3); cursor:pointer; font-size:11px; font-weight:600; transition:all .13s; user-select:none; font-family:var(--font); }
.fc:hover { border-color:var(--accent); color:var(--text-2); }
.fc.active { border-color:var(--accent); background:var(--accent-bg); color:var(--accent-2); }
.fc .cnt { background:rgba(0,0,0,.08); padding:0 5px; border-radius:99px; font-size:9px; font-weight:700; }
.fc.status-ok    { --fc-c:var(--green); }
.fc.status-warn  { --fc-c:var(--amber); }
.fc.status-err   { --fc-c:var(--red); }
.fc.status-ok.active   { border-color:var(--green); background:var(--green-bg); color:var(--green); }
.fc.status-warn.active { border-color:var(--amber); background:var(--amber-bg); color:var(--amber); }
.fc.status-err.active  { border-color:var(--red); background:var(--red-bg); color:var(--red); }

/* Search */
.filter-search-wrap { position:relative; flex:1; min-width:200px; }
.filter-search-wrap i { position:absolute; left:10px; top:50%; transform:translateY(-50%); color:var(--text-3); font-size:11px; pointer-events:none; }
.filter-search { width:100%; padding:7px 10px 7px 30px; border-radius:var(--radius); border:1px solid var(--border); background:var(--surface-2); color:var(--text); font-size:11px; outline:none; font-family:var(--font); transition:border-color .15s; }
.filter-search:focus { border-color:var(--accent); }
.filter-search::placeholder { color:var(--text-3); }

/* Résultats count */
.filter-results { font-size:11px; color:var(--text-3); font-weight:600; padding:0 4px; white-space:nowrap; }
.filter-results strong { color:var(--accent); }

/* Vue toggle */
.view-toggle { display:flex; gap:1px; background:var(--surface-3); border:1px solid var(--border); border-radius:var(--radius); padding:2px; flex-shrink:0; }
.view-btn { border:none; background:transparent; color:var(--text-3); cursor:pointer; padding:5px 10px; border-radius:calc(var(--radius) - 2px); font-size:11px; transition:all .13s; display:flex; align-items:center; gap:4px; font-family:var(--font); font-weight:600; white-space:nowrap; }
.view-btn.active { background:var(--surface); color:var(--accent); box-shadow:var(--shadow-sm); }
.view-btn:hover:not(.active) { color:var(--text-2); }

/* Clear filters */
.filter-clear { padding:4px 10px; border-radius:99px; border:1px solid var(--red-border,rgba(239,68,68,.3)); background:var(--red-bg); color:var(--red); font-size:10px; font-weight:700; cursor:pointer; transition:all .13s; display:none; font-family:var(--font); }
.filter-clear.show { display:inline-flex; align-items:center; gap:4px; }
.filter-clear:hover { background:var(--red); color:#fff; }

/* ════ DB health ════ */
.db-health-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); margin-bottom:16px; overflow:hidden; box-shadow:var(--shadow-sm); }
.db-health-card-hd { padding:11px 16px; border-bottom:1px solid var(--border); font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:space-between; background:var(--surface-2); color:var(--text-2); }

/* ════ VUE LISTE ════ */
.cat-section { margin-bottom:20px; }
.cat-section-title { font-size:10px; font-weight:800; text-transform:uppercase; letter-spacing:.1em; color:var(--text-3); margin-bottom:8px; display:flex; align-items:center; gap:7px; padding-left:4px; }
.cat-section-dot { width:8px; height:8px; border-radius:50%; display:inline-block; flex-shrink:0; }

.mod-row { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); padding:9px 14px; display:flex; align-items:center; gap:10px; margin-bottom:4px; transition:all .15s; cursor:pointer; }
.mod-row:hover { border-color:rgba(99,102,241,.3); box-shadow:var(--shadow-sm); transform:translateX(2px); }
.mod-row.s-warning { border-left:3px solid var(--amber); }
.mod-row.s-error   { border-left:3px solid var(--red); }
.mod-row.s-ok      { border-left:3px solid var(--green); }
.mod-row.disabled  { opacity:.45; }
.mod-row-icon { width:30px; height:30px; border-radius:var(--radius); display:flex; align-items:center; justify-content:center; font-size:11px; flex-shrink:0; }
.mod-row-body { flex:1; min-width:0; }
.mod-row-name { font-size:12px; font-weight:700; display:flex; align-items:center; gap:6px; flex-wrap:wrap; color:var(--text); }
.mod-row-name a { color:var(--text); transition:color .12s; }
.mod-row-name a:hover { color:var(--accent); }
.mod-row-desc { font-size:10px; color:var(--text-2); margin-top:2px; line-height:1.5; }
.mod-row-meta { font-size:10px; color:var(--text-3); margin-top:3px; display:flex; gap:10px; flex-wrap:wrap; font-family:var(--mono); }
.mod-row-acts { display:flex; align-items:center; gap:8px; flex-shrink:0; }
.mod-row-checks { display:none; padding:8px 14px 10px 54px; border-top:1px solid var(--border); background:var(--surface-2); border-radius:0 0 var(--radius) var(--radius); margin-top:-4px; margin-bottom:4px; }
.ck-item { display:flex; align-items:flex-start; gap:6px; padding:3px 0; font-size:10px; color:var(--text-2); }
.ck-dot { width:6px; height:6px; border-radius:50%; margin-top:3px; flex-shrink:0; }
.ck-dot.ok{background:var(--green)}.ck-dot.warning{background:var(--amber)}.ck-dot.error{background:var(--red)}
.exp-btn { background:transparent; border:none; color:var(--text-3); cursor:pointer; padding:3px 5px; font-size:10px; transition:transform .15s; }

/* ════ VUE GRILLE ════ */
.mod-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:12px; margin-bottom:20px; }
.mod-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; transition:all .15s; cursor:pointer; display:flex; flex-direction:column; }
.mod-card:hover { border-color:rgba(99,102,241,.4); box-shadow:var(--shadow); transform:translateY(-2px); }
.mod-card.s-warning { border-top:3px solid var(--amber); }
.mod-card.s-error   { border-top:3px solid var(--red); }
.mod-card.s-ok      { border-top:3px solid var(--green); }
.mod-card.disabled  { opacity:.45; }
.mod-card-hd { padding:12px 14px 8px; display:flex; align-items:flex-start; gap:10px; }
.mod-card-icon { width:34px; height:34px; border-radius:var(--radius); display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }
.mod-card-info { flex:1; min-width:0; }
.mod-card-name { font-size:12px; font-weight:700; color:var(--text); display:flex; align-items:center; gap:5px; flex-wrap:wrap; }
.mod-card-name a { color:var(--text); }
.mod-card-name a:hover { color:var(--accent); }
.mod-card-slug { font-size:9px; color:var(--text-3); font-family:var(--mono); margin-top:1px; }
.mod-card-desc { padding:0 14px 10px; font-size:10px; color:var(--text-2); line-height:1.6; flex:1; }
.mod-card-ft { padding:8px 14px; border-top:1px solid var(--border); background:var(--surface-2); display:flex; align-items:center; gap:8px; }
.mod-card-meta { font-size:9px; color:var(--text-3); font-family:var(--mono); }
.mod-card-acts { margin-left:auto; display:flex; align-items:center; gap:6px; }
.mod-card-checks { display:none; padding:8px 14px 10px; border-top:1px solid var(--border); background:var(--surface-2); }
.mod-card.open .mod-card-checks { display:block; }

/* ════ VUE KANBAN ════ */
.kanban-toolbar { display:flex; align-items:center; gap:8px; margin-bottom:14px; flex-wrap:wrap; }
.kanban-group-label { font-size:10px; font-weight:700; color:var(--text-3); text-transform:uppercase; letter-spacing:.07em; }
.kanban-group-btn { padding:4px 10px; border-radius:99px; border:1px solid var(--border); background:var(--surface-2); color:var(--text-3); cursor:pointer; font-size:11px; font-weight:600; transition:all .13s; font-family:var(--font); }
.kanban-group-btn.active { border-color:var(--accent); background:var(--accent-bg); color:var(--accent-2); }

.kanban-board { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; align-items:start; }
.kanban-col { background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; min-height:300px; }
.kanban-col-hd { padding:11px 14px; display:flex; align-items:center; gap:8px; border-bottom:1px solid var(--border); background:var(--surface); position:sticky; top:0; z-index:2; }
.kanban-col-title { font-size:12px; font-weight:700; flex:1; }
.kanban-col-count { background:var(--surface-3); color:var(--text-3); font-size:10px; font-weight:700; padding:2px 7px; border-radius:99px; }
.kanban-col-body { padding:8px; display:flex; flex-direction:column; gap:6px; }

.kanban-card {
    background:var(--surface); border:1px solid var(--border); border-radius:var(--radius);
    padding:10px 12px; cursor:pointer; transition:all .15s; position:relative; overflow:hidden;
}
.kanban-card::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; }
.kanban-card.s-ok::before    { background:var(--green); }
.kanban-card.s-warning::before { background:var(--amber); }
.kanban-card.s-error::before   { background:var(--red); }
.kanban-card:hover { box-shadow:var(--shadow); transform:translateY(-1px); border-color:rgba(99,102,241,.3); }
.kanban-card.disabled { opacity:.4; }
.kanban-card-hd { display:flex; align-items:flex-start; gap:8px; margin-bottom:6px; }
.kanban-card-icon { width:26px; height:26px; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:10px; flex-shrink:0; }
.kanban-card-name { font-size:11px; font-weight:700; color:var(--text); flex:1; line-height:1.3; display:flex; flex-wrap:wrap; align-items:center; gap:4px; }
.kanban-card-name a { color:var(--text); }
.kanban-card-name a:hover { color:var(--accent); }
.kanban-card-slug { font-size:9px; color:var(--text-3); font-family:var(--mono); margin-bottom:4px; }
.kanban-card-tags { display:flex; gap:3px; flex-wrap:wrap; margin-bottom:6px; }
.kanban-tag { font-size:8px; padding:1px 5px; border-radius:3px; font-weight:700; }
.kanban-card-ft { display:flex; align-items:center; gap:6px; }
.kanban-card-meta { font-size:9px; color:var(--text-3); font-family:var(--mono); flex:1; }
.kanban-card-issues { font-size:9px; color:var(--red); font-weight:700; }
.kanban-checks-detail { display:none; margin-top:8px; padding-top:8px; border-top:1px solid var(--border); }
.kanban-card.open .kanban-checks-detail { display:block; }

/* Colonnes Kanban par catégorie (mode groupBy=cat) */
.kanban-board-cat { display:flex; flex-wrap:wrap; gap:12px; }
.kanban-col-cat { flex:0 0 220px; background:var(--surface-2); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; max-height:600px; overflow-y:auto; }

/* ════ Status dots ════ */
.status-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.status-dot.ok      { background:var(--green); box-shadow:0 0 5px var(--green); }
.status-dot.warning { background:var(--amber); box-shadow:0 0 5px var(--amber); }
.status-dot.error   { background:var(--red);   box-shadow:0 0 5px var(--red); }

/* Toggle */
.tg{position:relative;display:inline-block;width:30px;height:16px}.tg input{opacity:0;width:0;height:0}.tg-sl{position:absolute;inset:0;background:var(--surface-3);border-radius:16px;cursor:pointer;transition:.16s;border:1px solid var(--border)}.tg-sl:before{position:absolute;content:"";height:10px;width:10px;left:2px;bottom:2px;background:var(--text-3);border-radius:50%;transition:.16s}input:checked+.tg-sl{background:var(--accent);border-color:var(--accent)}input:checked+.tg-sl:before{transform:translateX(14px);background:#fff}

/* ════ INVENTAIRE ════ */
.inv-scores{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px}
.inv-score-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:16px 14px;display:flex;align-items:center;gap:12px;box-shadow:var(--shadow-sm)}
.inv-score-icon{width:36px;height:36px;border-radius:var(--radius);display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}
.inv-score-val{font-size:22px;font-weight:900;line-height:1}
.inv-score-lbl{font-size:10px;color:var(--text-3);font-weight:600;margin-top:2px}

.db-table-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:10px;margin-bottom:20px}
.db-table-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:12px 14px;box-shadow:var(--shadow-sm);transition:all .15s}
.db-table-card:hover{border-color:var(--accent);box-shadow:var(--shadow);transform:translateY(-1px)}
.db-table-card.missing{border-left:3px solid var(--red)}
.db-table-card.present{border-left:3px solid var(--green)}
.db-table-card-hd{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.db-table-card-name{font-size:12px;font-weight:700;color:var(--text);font-family:var(--mono)}
.db-table-card-rows{font-size:10px;color:var(--text-3);margin-left:auto;font-family:var(--mono)}
.db-table-card-desc{font-size:10px;color:var(--text-2);line-height:1.6}
.db-table-card-tags{display:flex;gap:4px;flex-wrap:wrap;margin-top:6px}
.db-table-card-tag{font-size:8px;padding:2px 6px;border-radius:4px;background:var(--surface-3);color:var(--text-3);font-weight:600}

.inv-table{width:100%;border-collapse:collapse;font-size:11px}
.inv-table th{padding:7px 10px;text-align:left;font-size:10px;font-weight:700;color:var(--text-3);text-transform:uppercase;letter-spacing:.06em;border-bottom:2px solid var(--border);background:var(--surface-2)}
.inv-table td{padding:7px 10px;border-bottom:1px solid var(--border);vertical-align:middle}
.inv-table tr:hover td{background:var(--surface-2)}
.inv-table tr:last-child td{border-bottom:none}
.inv-bar-wrap{background:var(--surface-3);border-radius:99px;height:5px;width:100px;overflow:hidden}
.inv-bar{height:5px;border-radius:99px;background:var(--accent)}
.inv-path{font-family:var(--mono);color:var(--text-2);word-break:break-all}
.inv-badge-empty{display:inline-flex;align-items:center;gap:4px;padding:2px 7px;border-radius:99px;background:var(--red-bg);color:var(--red);font-size:10px;font-weight:700}

.empty-list{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden}
.empty-list-hd{padding:10px 14px;border-bottom:1px solid var(--border);background:var(--surface-2);display:flex;align-items:center;gap:8px}
.empty-file-row{display:flex;align-items:center;gap:10px;padding:7px 14px;border-bottom:1px solid var(--border);font-size:11px}
.empty-file-row:last-child{border-bottom:none}
.empty-file-row:hover{background:var(--surface-2)}
.empty-file-icon{width:24px;height:24px;border-radius:5px;background:var(--red-bg);color:var(--red);display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0}
.empty-file-name{font-weight:700;color:var(--text)}
.empty-file-dir{font-size:10px;color:var(--text-3);font-family:var(--mono)}
.empty-file-tag{margin-left:auto;font-size:9px;padding:2px 6px;border-radius:4px;background:var(--surface-3);color:var(--text-3);font-weight:600;flex-shrink:0}

/* ════ TREE ════ */
.tree-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm)}
.tree-search-bar{padding:10px 14px;border-bottom:1px solid var(--border);background:var(--surface-2);display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.tree-body{padding:8px 6px 12px;font-size:11px}
.tree-dir-btn{display:flex;align-items:center;gap:5px;padding:3px 6px;border-radius:5px;background:none;border:none;cursor:pointer;color:var(--text-2);font-size:11px;font-family:var(--font);font-weight:600;width:100%;text-align:left;transition:background .1s}
.tree-dir-btn:hover{background:var(--surface-2)}
.tree-dir-icon{font-size:10px;color:var(--text-3);transition:transform .15s;flex-shrink:0}
.tree-dir-btn.open .tree-dir-icon{transform:rotate(90deg)}
.tree-folder-ic{color:#eab308;font-size:11px;flex-shrink:0}
.tree-children{padding-left:18px;border-left:1px solid var(--border);margin-left:14px;display:none}
.tree-children.open{display:block}
.tree-file{display:flex;align-items:center;gap:6px;padding:2px 6px;border-radius:5px;color:var(--text-3);font-family:var(--mono);font-size:10px;transition:background .1s}
.tree-file:hover{background:var(--surface-2);color:var(--text-2)}
.tree-file-ic{font-size:9px;flex-shrink:0}
.tree-file-ic.php{color:var(--accent-2)}.tree-file-ic.json{color:var(--amber)}.tree-file-ic.css{color:#06b6d4}.tree-file-ic.js{color:#eab308}.tree-file-ic.sql{color:var(--green)}.tree-file-ic.html{color:#f97316}.tree-file-ic.other{color:var(--text-3)}
.tree-file-size{margin-left:auto;color:var(--text-3);font-size:9px}
.tree-cnt-badge{background:var(--surface-3);color:var(--text-3);font-size:8px;padding:0 5px;border-radius:3px;font-family:var(--font);font-weight:600;flex-shrink:0}

/* ════ CHAT ════ */
.chat-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);display:flex;flex-direction:column;height:580px;overflow:hidden;box-shadow:var(--shadow-sm)}
.chat-hdr{padding:11px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;background:var(--surface-2)}
.chat-hdr-ic{width:28px;height:28px;border-radius:var(--radius);background:var(--accent);display:flex;align-items:center;justify-content:center;font-size:12px;color:#fff}
.chat-hdr-title{font-size:12px;font-weight:700;color:var(--text)}
.chat-hdr-sub{font-size:10px;color:var(--text-3)}
.quick-qs{padding:8px 12px;border-bottom:1px solid var(--border);display:flex;gap:5px;flex-wrap:wrap;background:var(--surface-2)}
.qq{padding:3px 9px;border-radius:99px;border:1px solid var(--border);background:var(--surface);color:var(--text-3);cursor:pointer;font-size:10px;font-weight:600;transition:all .13s;font-family:var(--font)}
.qq:hover{border-color:var(--accent);color:var(--accent-2);background:var(--accent-bg)}
.chat-msgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:10px}
.chat-msgs::-webkit-scrollbar{width:3px}
.chat-msgs::-webkit-scrollbar-thumb{background:var(--border);border-radius:2px}
.msg{max-width:86%;padding:9px 13px;font-size:12px;line-height:1.7;word-break:break-word;border-radius:var(--radius)}
.msg-u{align-self:flex-end;background:var(--accent);color:#fff;border-radius:var(--radius-lg) var(--radius-lg) 4px var(--radius-lg)}
.msg-a{align-self:flex-start;background:var(--surface-2);border:1px solid var(--border);border-radius:4px var(--radius-lg) var(--radius-lg) var(--radius-lg);color:var(--text)}
.msg-a-lbl{font-size:8px;color:var(--accent-2);font-weight:800;text-transform:uppercase;letter-spacing:.07em;margin-bottom:4px}
.typing-w{padding:0 14px 8px;display:none}
.typing{display:flex;gap:4px;padding:9px 13px;background:var(--surface-2);border:1px solid var(--border);border-radius:4px var(--radius-lg) var(--radius-lg) var(--radius-lg);width:fit-content}
.typing span{width:5px;height:5px;border-radius:50%;background:var(--accent);animation:ty 1.4s ease-in-out infinite}
.typing span:nth-child(2){animation-delay:.2s}.typing span:nth-child(3){animation-delay:.4s}
.chat-footer{padding:10px 12px;border-top:1px solid var(--border);display:flex;gap:7px;background:var(--surface-2)}
.chat-input{flex:1;padding:8px 11px;border-radius:var(--radius);border:1px solid var(--border);background:var(--surface);color:var(--text);font-size:12px;outline:none;resize:none;font-family:var(--font);line-height:1.5;transition:border-color .15s}
.chat-input:focus{border-color:var(--accent)}
.chat-input::placeholder{color:var(--text-3)}
.chat-send{padding:8px 14px;border-radius:var(--radius);border:none;background:var(--accent);color:#fff;cursor:pointer;font-weight:700;font-size:13px;transition:all .13s;align-self:flex-end}
.chat-send:hover{background:#4f46e5}
.chat-send:disabled{background:var(--surface-3);color:var(--text-3);cursor:not-allowed}

.mod-toast{position:fixed;bottom:16px;right:16px;background:var(--surface);border:1px solid var(--border);color:var(--text);padding:9px 14px;border-radius:var(--radius);font-size:11px;z-index:9999;opacity:0;transform:translateY(5px);transition:all .2s;pointer-events:none;box-shadow:var(--shadow)}
.mod-toast.show{opacity:1;transform:translateY(0)}
.hidden{display:none!important}

/* Inv section */
.inv-section{margin-bottom:20px}
.inv-section-title{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:var(--text-3);margin-bottom:10px;display:flex;align-items:center;gap:8px}

@keyframes ty{0%,80%,100%{transform:scale(.7);opacity:.5}40%{transform:scale(1);opacity:1}}
@media(max-width:760px){
  .mod-scores,.inv-scores{grid-template-columns:1fr 1fr}
  .mod-grid{grid-template-columns:1fr}
  .kanban-board{grid-template-columns:1fr}
  .db-table-grid{grid-template-columns:1fr}
  .filter-bar-row{gap:4px}
}

/* animation entrée */
.anim{animation:fadeUp .3s ease both}
@keyframes fadeUp{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}

/* No results */
.no-results{padding:40px;text-align:center;color:var(--text-3);font-size:13px;display:none}
.no-results i{font-size:28px;margin-bottom:10px;display:block;opacity:.4}
</style>

<!-- ════════ PAGE HEADER ════════ -->
<div class="page-hd">
    <div>
        <h1>Gestion des modules</h1>
        <div class="page-hd-sub"><?= $scanDate ?> &middot; <?= $total ?> modules &middot; <?= $dbTableCount ?> tables DB &middot; <?= $inventory['total_files'] ?> fichiers &middot; <?= $inventory['total_dirs'] ?> dossiers</div>
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
        <button class="btn btn-s btn-sm" onclick="location.reload()"><i class="fas fa-sync-alt"></i> Relancer</button>
        <button class="btn btn-s btn-sm" onclick="doExport()"><i class="fas fa-code"></i> Export JSON</button>
        <button class="btn btn-p btn-sm" onclick="switchModTab('ia');setTimeout(triggerAnalysis,150)"><i class="fas fa-robot"></i> Analyse IA</button>
    </div>
</div>

<!-- Score cards -->
<div class="mod-scores anim">
    <div class="mod-score-main">
        <div class="pct" style="color:<?= $scoreColor ?>"><?= $scorePct ?>%</div>
        <div class="lbl">Sant&eacute;</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--accent-bg);color:var(--accent)"><i class="fas fa-puzzle-piece"></i></div>
        <div class="stat-info"><div class="stat-val"><?= $total ?></div><div class="stat-label">Total modules</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--green-bg);color:var(--green)"><i class="fas fa-check"></i></div>
        <div class="stat-info"><div class="stat-val"><?= $countOk ?></div><div class="stat-label">Modules OK</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--amber-bg);color:var(--amber)"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-info"><div class="stat-val"><?= $countWarn ?></div><div class="stat-label">Warnings</div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--red-bg);color:var(--red)"><i class="fas fa-xmark"></i></div>
        <div class="stat-info"><div class="stat-val"><?= $countErr ?></div><div class="stat-label">Erreurs</div></div>
    </div>
</div>

<?php if ($total > 0): ?>
<div class="mod-progress anim">
    <div class="mp-ok" style="width:<?= round($countOk/$total*100) ?>%"></div>
    <div class="mp-wa" style="width:<?= round($countWarn/$total*100) ?>%"></div>
    <div class="mp-er" style="width:<?= round($countErr/$total*100) ?>%"></div>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="mod-tabs">
    <button class="mod-tab active" id="tab-diag"  onclick="switchModTab('diag')"><i class="fas fa-stethoscope"></i> Diagnostic</button>
    <button class="mod-tab" id="tab-kanban" onclick="switchModTab('kanban')"><i class="fas fa-columns"></i> Kanban</button>
    <button class="mod-tab" id="tab-inv"   onclick="switchModTab('inv')">
        <i class="fas fa-layer-group"></i> Inventaire
        <?php if ($inventory['empty_count'] > 0): ?>
        <span style="background:var(--red);color:#fff;font-size:8px;padding:1px 5px;border-radius:99px"><?= $inventory['empty_count'] ?></span>
        <?php endif; ?>
    </button>
    <button class="mod-tab" id="tab-tree"  onclick="switchModTab('tree')"><i class="fas fa-folder-tree"></i> Structure</button>
    <button class="mod-tab" id="tab-ia"    onclick="switchModTab('ia')"><i class="fas fa-robot"></i> Assistant IA</button>
</div>

<!-- ════════════════════════════════════════════════════════
     BARRE DE FILTRES AVANCÉE (partagée Diagnostic + Kanban)
════════════════════════════════════════════════════════ -->
<div class="filter-bar anim" id="shared-filter-bar">
    <!-- Ligne 1 : Recherche + Vue + Clear -->
    <div class="filter-bar-row">
        <div class="filter-search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" class="filter-search" id="filter-search" placeholder="Rechercher un module&hellip;" oninput="applyAllFilters()">
        </div>
        <span class="filter-results" id="filter-results"><strong id="filter-count"><?= $total ?></strong> module(s)</span>
        <button class="filter-clear" id="filter-clear-btn" onclick="clearAllFilters()"><i class="fas fa-xmark"></i> R&eacute;initialiser</button>
        <div class="view-toggle" id="view-toggle-diag">
            <button class="view-btn active" id="vbtn-list" onclick="setView('list')"><i class="fas fa-list"></i> Liste</button>
            <button class="view-btn" id="vbtn-grid" onclick="setView('grid')"><i class="fas fa-th-large"></i> Grille</button>
        </div>
    </div>
    <!-- Ligne 2 : Statut -->
    <div class="filter-bar-row">
        <span class="filter-bar-label">Statut</span>
        <button class="fc active" data-ftype="status" data-fval="all" onclick="toggleFilter(this)">Tous <span class="cnt"><?= $total ?></span></button>
        <button class="fc status-ok" data-ftype="status" data-fval="ok" onclick="toggleFilter(this)"><i class="fas fa-check" style="font-size:9px"></i> OK <span class="cnt"><?= $countOk ?></span></button>
        <button class="fc status-warn" data-ftype="status" data-fval="warning" onclick="toggleFilter(this)"><i class="fas fa-triangle-exclamation" style="font-size:9px"></i> Warning <span class="cnt"><?= $countWarn ?></span></button>
        <?php if($countErr>0): ?>
        <button class="fc status-err" data-ftype="status" data-fval="error" onclick="toggleFilter(this)"><i class="fas fa-xmark" style="font-size:9px"></i> Erreur <span class="cnt"><?= $countErr ?></span></button>
        <?php endif; ?>
        <button class="fc" data-ftype="status" data-fval="disabled" onclick="toggleFilter(this)"><i class="fas fa-eye-slash" style="font-size:9px"></i> D&eacute;sactiv&eacute;</button>
    </div>
    <!-- Ligne 3 : Catégories -->
    <div class="filter-bar-row" style="flex-wrap:wrap">
        <span class="filter-bar-label">Cat&eacute;gorie</span>
        <?php
        $catColorMap = ['CRM'=>'#ec4899','CMS'=>'#3b82f6','Immobilier'=>'#10b981','SEO'=>'#f59e0b','Marketing'=>'#06b6d4','Social'=>'#8b5cf6','IA'=>'#6366f1','Strat&eacute;gie'=>'#f97316','Syst&egrave;me'=>'#64748b','Network'=>'#14b8a6'];
        foreach ($allCats as $cat):
            $cc = $catColorMap[$cat] ?? '#6366f1';
            $catCount = count($categories[$cat] ?? []);
        ?>
        <button class="fc" data-ftype="cat" data-fval="<?= htmlspecialchars($cat) ?>" onclick="toggleFilter(this)"
                style="--cat-c:<?= $cc ?>">
            <span style="width:7px;height:7px;border-radius:50%;background:<?= $cc ?>;display:inline-block;flex-shrink:0"></span>
            <?= htmlspecialchars($cat) ?> <span class="cnt"><?= $catCount ?></span>
        </button>
        <?php endforeach; ?>
    </div>
    <!-- Ligne 4 : Tags sujets -->
    <div class="filter-bar-row">
        <span class="filter-bar-label">Sujets</span>
        <button class="fc" data-ftype="tag" data-fval="has_table" onclick="toggleFilter(this)"><i class="fas fa-database" style="font-size:9px"></i> Avec table DB</button>
        <button class="fc" data-ftype="tag" data-fval="missing_table" onclick="toggleFilter(this)"><i class="fas fa-triangle-exclamation" style="font-size:9px;color:var(--red)"></i> Table manquante</button>
        <button class="fc" data-ftype="tag" data-fval="has_file" onclick="toggleFilter(this)"><i class="fas fa-file-code" style="font-size:9px"></i> A des fichiers</button>
        <button class="fc" data-ftype="tag" data-fval="empty_file" onclick="toggleFilter(this)"><i class="fas fa-file-circle-xmark" style="font-size:9px;color:var(--red)"></i> Fichier vide</button>
        <button class="fc" data-ftype="tag" data-fval="has_handler" onclick="toggleFilter(this)"><i class="fas fa-gear" style="font-size:9px"></i> Handler</button>
    </div>
</div>

<!-- ════════════════════════════════════════════
     ONGLET 1 : DIAGNOSTIC (Liste + Grille)
════════════════════════════════════════════ -->
<div id="panel-diag">

    <!-- DB Health -->
    <div class="db-health-card">
        <div class="db-health-card-hd">
            <span><i class="fas fa-database" style="color:var(--accent);margin-right:6px"></i>Base de donn&eacute;es</span>
            <span style="font-weight:400;font-size:10px;color:var(--text-3)"><?= $dbTableCount ?> tables MySQL</span>
        </div>
        <table class="tbl">
        <?php foreach ($dbHealth as $row): ?>
            <tr>
                <td style="width:20px"><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $row['status']==='ok'?'var(--green)':($row['status']==='warning'?'var(--amber)':'var(--red)') ?>"></span></td>
                <td style="font-weight:600;font-size:11px"><?= htmlspecialchars($row['check']) ?></td>
                <td style="font-size:10px;color:var(--text-2)">
                <?php
                $tname = str_replace(['Table `','`'], '', $row['check'] ?? '');
                if(isset($tableDescriptions[$tname])) echo $tableDescriptions[$tname];
                ?>
                </td>
                <td style="text-align:right;font-size:10px;color:var(--text-3)"><?= htmlspecialchars($row['value'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </table>
    </div>

    <!-- Vue liste -->
    <div id="view-list">
        <?php foreach ($categories as $catName => $catMods):
            $cc = $catColors[$catName] ?? '#6366f1';
        ?>
        <div class="cat-section" data-cat="<?= htmlspecialchars($catName) ?>">
            <div class="cat-section-title">
                <span class="cat-section-dot" style="background:<?= $cc ?>"></span>
                <span style="color:<?= $cc ?>"><?= htmlspecialchars($catName) ?></span>
                <span style="font-weight:400">(<?= count($catMods) ?>)</span>
            </div>
            <?php foreach ($catMods as $slug => $mod):
                $enabled  = $states[$slug]['enabled'] ?? true;
                $rowCls   = 's-'.$mod['status'].($enabled?'':' disabled');
                $ckOk     = count(array_filter($mod['checks']??[], fn($c)=>$c['status']==='ok'));
                $ckAll    = count($mod['checks']??[]);
                $hasMissingTable = false;
                $hasHandler = false;
                foreach ($mod['checks']??[] as $ck){
                    if(str_contains($ck['message']??'','manquante')||($ck['type']==='table'&&$ck['status']!=='ok')) $hasMissingTable=true;
                    if(str_contains($ck['message']??'','handler')||($ck['type']==='handler')) $hasHandler=true;
                }
                // Tags pour filtrage JS
                $jsTags = [];
                if(!empty($mod['file_count'])) $jsTags[]='has_file';
                if($ckAll>0) $jsTags[]='has_table';
                if($hasMissingTable) $jsTags[]='missing_table';
                if($hasHandler) $jsTags[]='has_handler';
                // Desc
                $modDesc='';
                foreach ($mod['checks']??[] as $ck){ if($ck['type']==='table'&&$ck['status']==='ok'){ preg_match('/`([^`]+)`/',$ck['message'],$m2); if(!empty($m2[1])&&isset($tableDescriptions[$m2[1]])){ $modDesc=$tableDescriptions[$m2[1]]; break;} } }
            ?>
            <div class="mod-row <?= $rowCls ?>" id="row-<?= $slug ?>"
                 data-status="<?= $mod['status'] ?>"
                 data-cat="<?= htmlspecialchars($catName) ?>"
                 data-name="<?= strtolower(htmlspecialchars($mod['label'])) ?> <?= $slug ?>"
                 data-tags="<?= implode(' ',$jsTags) ?>"
                 data-enabled="<?= $enabled?'1':'0' ?>"
                 onclick="toggleModRow('<?= $slug ?>')">
                <div class="mod-row-icon" style="background:<?= $cc ?>18;color:<?= $cc ?>">
                    <i class="<?= htmlspecialchars($mod['icon']??'fas fa-folder') ?>"></i>
                </div>
                <div class="mod-row-body">
                    <div class="mod-row-name">
                        <a href="/admin/dashboard.php?page=<?= $slug ?>" onclick="event.stopPropagation()"><?= htmlspecialchars($mod['label']) ?></a>
                        <?php if($mod['status']==='ok'): ?><span class="badge badge-green">OK</span>
                        <?php elseif($mod['status']==='warning'): ?><span class="badge badge-amber">WARN</span>
                        <?php else: ?><span class="badge badge-red">ERR</span>
                        <?php endif; ?>
                        <?php if(!$enabled): ?><span class="badge" style="background:var(--surface-3);color:var(--text-3)">OFF</span><?php endif; ?>
                    </div>
                    <?php if($modDesc): ?><div class="mod-row-desc"><?= $modDesc ?></div><?php endif; ?>
                    <div class="mod-row-meta">
                        <span>/<?= $slug ?>/</span>
                        <span><?= $mod['file_count']??0 ?> fichier(s)</span>
                        <span><?= $ckOk ?>/<?= $ckAll ?> checks</span>
                        <span style="color:<?= $cc ?>"><?= htmlspecialchars($catName) ?></span>
                    </div>
                </div>
                <div class="mod-row-acts" onclick="event.stopPropagation()">
                    <button class="exp-btn" onclick="toggleModRow('<?= $slug ?>')" title="D&eacute;tails"><i class="fas fa-chevron-right" id="exp-<?= $slug ?>"></i></button>
                    <div class="status-dot <?= $mod['status'] ?>"></div>
                    <label class="tg" title="Activer/d&eacute;sactiver">
                        <input type="checkbox" <?= $enabled?'checked':'' ?> onchange="toggleMod('<?= $slug ?>',this)">
                        <span class="tg-sl"></span>
                    </label>
                </div>
            </div>
            <div class="mod-row-checks" id="checks-<?= $slug ?>" style="display:none">
                <?php foreach ($mod['checks']??[] as $ck): ?>
                <div class="ck-item"><span class="ck-dot <?= $ck['status'] ?>"></span><span><?= htmlspecialchars($ck['message']) ?></span></div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        <div class="no-results" id="list-no-results"><i class="fas fa-filter"></i>Aucun module ne correspond aux filtres actifs.</div>
    </div><!-- /view-list -->

    <!-- Vue grille -->
    <div id="view-grid" style="display:none">
        <?php foreach ($categories as $catName => $catMods):
            $cc = $catColors[$catName] ?? '#6366f1';
        ?>
        <div class="cat-section" data-cat-grid="<?= htmlspecialchars($catName) ?>">
            <div class="cat-section-title">
                <span class="cat-section-dot" style="background:<?= $cc ?>"></span>
                <span style="color:<?= $cc ?>"><?= htmlspecialchars($catName) ?></span>
            </div>
            <div class="mod-grid">
            <?php foreach ($catMods as $slug => $mod):
                $enabled = $states[$slug]['enabled'] ?? true;
                $cardCls = 's-'.$mod['status'].($enabled?'':' disabled');
                $ckOk2   = count(array_filter($mod['checks']??[], fn($c)=>$c['status']==='ok'));
                $ckAll2  = count($mod['checks']??[]);
                $hasMT2=false; $hasH2=false;
                foreach ($mod['checks']??[] as $ck){ if(str_contains($ck['message']??'','manquante')||($ck['type']==='table'&&$ck['status']!=='ok')) $hasMT2=true; if(str_contains($ck['message']??'','handler')) $hasH2=true; }
                $jsTags2=[];if(!empty($mod['file_count']))$jsTags2[]='has_file';if($ckAll2>0)$jsTags2[]='has_table';if($hasMT2)$jsTags2[]='missing_table';if($hasH2)$jsTags2[]='has_handler';
                $modDesc2='Module '.strtolower($mod['category']).' &mdash; '.($mod['file_count']??0).' fichiers';
                foreach ($mod['checks']??[] as $ck){ if($ck['type']==='table'&&$ck['status']==='ok'){ preg_match('/`([^`]+)`/',$ck['message'],$m3); if(!empty($m3[1])&&isset($tableDescriptions[$m3[1]])){ $modDesc2=$tableDescriptions[$m3[1]]; break;} } }
            ?>
            <div class="mod-card <?= $cardCls ?>" id="card-<?= $slug ?>"
                 data-status="<?= $mod['status'] ?>"
                 data-cat="<?= htmlspecialchars($catName) ?>"
                 data-name="<?= strtolower(htmlspecialchars($mod['label'])) ?> <?= $slug ?>"
                 data-tags="<?= implode(' ',$jsTags2) ?>"
                 data-enabled="<?= $enabled?'1':'0' ?>"
                 onclick="toggleModCard('<?= $slug ?>')">
                <div class="mod-card-hd">
                    <div class="mod-card-icon" style="background:<?= $cc ?>18;color:<?= $cc ?>">
                        <i class="<?= htmlspecialchars($mod['icon']??'fas fa-folder') ?>"></i>
                    </div>
                    <div class="mod-card-info">
                        <div class="mod-card-name">
                            <a href="/admin/dashboard.php?page=<?= $slug ?>" onclick="event.stopPropagation()"><?= htmlspecialchars($mod['label']) ?></a>
                            <?php if($mod['status']==='ok'): ?><span class="badge badge-green" style="font-size:8px">OK</span>
                            <?php elseif($mod['status']==='warning'): ?><span class="badge badge-amber" style="font-size:8px">WARN</span>
                            <?php else: ?><span class="badge badge-red" style="font-size:8px">ERR</span><?php endif; ?>
                        </div>
                        <div class="mod-card-slug">/<?= $slug ?>/</div>
                    </div>
                </div>
                <div class="mod-card-desc"><?= $modDesc2 ?></div>
                <div class="mod-card-ft">
                    <span class="mod-card-meta"><?= $mod['file_count']??0 ?> fichiers &middot; <?= $ckOk2 ?>/<?= $ckAll2 ?> checks</span>
                    <div class="mod-card-acts" onclick="event.stopPropagation()">
                        <div class="status-dot <?= $mod['status'] ?>"></div>
                        <label class="tg"><input type="checkbox" <?= $enabled?'checked':'' ?> onchange="toggleMod('<?= $slug ?>',this)"><span class="tg-sl"></span></label>
                    </div>
                </div>
                <div class="mod-card-checks">
                    <?php foreach ($mod['checks']??[] as $ck): ?>
                    <div class="ck-item"><span class="ck-dot <?= $ck['status'] ?>"></span><span><?= htmlspecialchars($ck['message']) ?></span></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="no-results" id="grid-no-results"><i class="fas fa-filter"></i>Aucun module ne correspond aux filtres actifs.</div>
    </div><!-- /view-grid -->

</div><!-- /panel-diag -->

<!-- ════════════════════════════════════════════
     ONGLET 2 : KANBAN
════════════════════════════════════════════ -->
<div id="panel-kanban" style="display:none">
    <div class="kanban-toolbar">
        <span class="kanban-group-label">Grouper par :</span>
        <button class="kanban-group-btn active" id="kgb-status" onclick="setKanbanGroup('status')">Statut</button>
        <button class="kanban-group-btn" id="kgb-cat" onclick="setKanbanGroup('cat')">Cat&eacute;gorie</button>
    </div>

    <!-- Kanban par statut (défaut) -->
    <div id="kanban-status-view">
        <div class="kanban-board">
            <?php
            $kanbanCols = [
                'ok'      => ['label'=>'&#10003;&nbsp;OK',       'color'=>'var(--green)', 'bg'=>'var(--green-bg)'],
                'warning' => ['label'=>'&#9888;&nbsp;Warning',   'color'=>'var(--amber)', 'bg'=>'var(--amber-bg)'],
                'error'   => ['label'=>'&#10007;&nbsp;Erreur',   'color'=>'var(--red)',   'bg'=>'var(--red-bg)'],
            ];
            foreach ($kanbanCols as $kStatus => $kCol):
                $kMods = array_filter($modules, fn($m)=>$m['status']===$kStatus);
            ?>
            <div class="kanban-col" id="kcol-<?= $kStatus ?>">
                <div class="kanban-col-hd" style="border-top:3px solid <?= $kCol['color'] ?>">
                    <div class="status-dot <?= $kStatus ?>"></div>
                    <span class="kanban-col-title"><?= $kCol['label'] ?></span>
                    <span class="kanban-col-count" style="background:<?= $kCol['bg'] ?>;color:<?= $kCol['color'] ?>"><?= count($kMods) ?></span>
                </div>
                <div class="kanban-col-body" id="kcol-body-<?= $kStatus ?>">
                    <?php foreach ($kMods as $slug => $mod):
                        $enabled  = $states[$slug]['enabled'] ?? true;
                        $cc       = $catColors[$mod['category']] ?? '#6366f1';
                        $ckNotOk  = array_filter($mod['checks']??[], fn($c)=>$c['status']!=='ok');
                        $issueCount = count($ckNotOk);
                        $hasMT = false;
                        foreach ($mod['checks']??[] as $ck){ if(str_contains($ck['message']??'','manquante')||($ck['type']==='table'&&$ck['status']!=='ok')) $hasMT=true; }
                    ?>
                    <div class="kanban-card s-<?= $mod['status'] ?><?= $enabled?'':' disabled' ?>"
                         id="kcard-<?= $slug ?>"
                         data-cat="<?= htmlspecialchars($mod['category']) ?>"
                         data-name="<?= strtolower(htmlspecialchars($mod['label'])) ?> <?= $slug ?>"
                         data-enabled="<?= $enabled?'1':'0' ?>"
                         onclick="toggleKCard('<?= $slug ?>')">
                        <div class="kanban-card-hd">
                            <div class="kanban-card-icon" style="background:<?= $cc ?>18;color:<?= $cc ?>">
                                <i class="<?= htmlspecialchars($mod['icon']??'fas fa-folder') ?>"></i>
                            </div>
                            <div class="kanban-card-name">
                                <a href="/admin/dashboard.php?page=<?= $slug ?>" onclick="event.stopPropagation()"><?= htmlspecialchars($mod['label']) ?></a>
                            </div>
                            <label class="tg" onclick="event.stopPropagation()" style="flex-shrink:0">
                                <input type="checkbox" <?= $enabled?'checked':'' ?> onchange="toggleMod('<?= $slug ?>',this)">
                                <span class="tg-sl"></span>
                            </label>
                        </div>
                        <div class="kanban-card-slug">/<?= $slug ?>/</div>
                        <div class="kanban-card-tags">
                            <span class="kanban-tag" style="background:<?= $cc ?>18;color:<?= $cc ?>"><?= htmlspecialchars($mod['category']) ?></span>
                            <span class="kanban-tag" style="background:var(--surface-3);color:var(--text-3)"><?= $mod['file_count']??0 ?> fichiers</span>
                            <?php if($hasMT): ?><span class="kanban-tag" style="background:var(--red-bg);color:var(--red)">Table manquante</span><?php endif; ?>
                        </div>
                        <div class="kanban-card-ft">
                            <span class="kanban-card-meta"><?= count($mod['checks']??[]) ?> checks</span>
                            <?php if($issueCount>0): ?><span class="kanban-card-issues"><?= $issueCount ?> probl&egrave;me(s)</span><?php endif; ?>
                            <button class="btn btn-s btn-sm" style="margin-left:auto;font-size:9px;padding:2px 7px" onclick="event.stopPropagation();window.location='/admin/dashboard.php?page=<?= $slug ?>'"><i class="fas fa-arrow-right"></i></button>
                        </div>
                        <div class="kanban-checks-detail" id="kd-<?= $slug ?>">
                            <?php foreach ($ckNotOk as $ck): ?>
                            <div class="ck-item"><span class="ck-dot <?= $ck['status'] ?>"></span><span style="font-size:10px"><?= htmlspecialchars($ck['message']) ?></span></div>
                            <?php endforeach; ?>
                            <?php if(empty($ckNotOk)): ?>
                            <div class="ck-item" style="color:var(--green)"><span class="ck-dot ok"></span><span>Tous les checks passent &#10003;</span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if(empty($kMods)): ?>
                    <div style="padding:24px;text-align:center;color:var(--text-3);font-size:11px">
                        <i class="fas fa-check-circle" style="font-size:20px;margin-bottom:6px;display:block;opacity:.3"></i>
                        Aucun module dans cette colonne
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div><!-- /kanban-status-view -->

    <!-- Kanban par catégorie -->
    <div id="kanban-cat-view" style="display:none">
        <div class="kanban-board-cat">
            <?php foreach ($categories as $catName => $catMods):
                $cc = $catColors[$catName] ?? '#6366f1';
                $catOk   = count(array_filter($catMods, fn($m)=>$m['status']==='ok'));
                $catWarn = count(array_filter($catMods, fn($m)=>$m['status']==='warning'));
                $catErr  = count(array_filter($catMods, fn($m)=>$m['status']==='error'));
            ?>
            <div class="kanban-col-cat">
                <div class="kanban-col-hd" style="border-top:3px solid <?= $cc ?>;background:<?= $cc ?>0a">
                    <span style="width:8px;height:8px;border-radius:50%;background:<?= $cc ?>;display:inline-block;flex-shrink:0"></span>
                    <span class="kanban-col-title" style="color:<?= $cc ?>"><?= htmlspecialchars($catName) ?></span>
                    <span class="kanban-col-count" style="background:<?= $cc ?>18;color:<?= $cc ?>"><?= count($catMods) ?></span>
                </div>
                <div style="padding:4px 6px 2px;border-bottom:1px solid var(--border);display:flex;gap:4px;background:<?= $cc ?>05">
                    <?php if($catOk>0): ?><span style="font-size:9px;color:var(--green);font-weight:700"><?= $catOk ?> OK</span><?php endif; ?>
                    <?php if($catWarn>0): ?><span style="font-size:9px;color:var(--amber);font-weight:700"><?= $catWarn ?> WARN</span><?php endif; ?>
                    <?php if($catErr>0): ?><span style="font-size:9px;color:var(--red);font-weight:700"><?= $catErr ?> ERR</span><?php endif; ?>
                </div>
                <div style="padding:6px;display:flex;flex-direction:column;gap:4px">
                <?php foreach ($catMods as $slug => $mod):
                    $enabled = $states[$slug]['enabled'] ?? true;
                ?>
                <div class="kanban-card s-<?= $mod['status'] ?><?= $enabled?'':' disabled' ?>" style="padding:8px 10px"
                     onclick="event.stopPropagation();window.location='/admin/dashboard.php?page=<?= $slug ?>'">
                    <div style="display:flex;align-items:center;gap:6px">
                        <div class="status-dot <?= $mod['status'] ?>"></div>
                        <span style="font-size:11px;font-weight:600;color:var(--text);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($mod['label']) ?></span>
                        <label class="tg" onclick="event.stopPropagation()" style="flex-shrink:0">
                            <input type="checkbox" <?= $enabled?'checked':'' ?> onchange="toggleMod('<?= $slug ?>',this)">
                            <span class="tg-sl"></span>
                        </label>
                    </div>
                    <div style="font-size:9px;color:var(--text-3);font-family:var(--mono);margin-top:2px">/<?= $slug ?>/</div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div><!-- /kanban-cat-view -->
</div><!-- /panel-kanban -->

<!-- ════════════════════════════════════════════
     ONGLET 3 : INVENTAIRE
════════════════════════════════════════════ -->
<div id="panel-inv" style="display:none">
    <div class="inv-scores">
        <div class="inv-score-card">
            <div class="inv-score-icon" style="background:var(--accent-bg);color:var(--accent)"><i class="fas fa-folder-open"></i></div>
            <div><div class="inv-score-val"><?= number_format($inventory['total_dirs']) ?></div><div class="inv-score-lbl">Dossiers</div></div>
        </div>
        <div class="inv-score-card">
            <div class="inv-score-icon" style="background:var(--green-bg);color:var(--green)"><i class="fas fa-file-code"></i></div>
            <div><div class="inv-score-val"><?= number_format($inventory['total_files']) ?></div><div class="inv-score-lbl">Fichiers total</div></div>
        </div>
        <div class="inv-score-card">
            <div class="inv-score-icon" style="background:<?= $inventory['empty_count']>0?'var(--red-bg)':'var(--green-bg)' ?>;color:<?= $inventory['empty_count']>0?'var(--red)':'var(--green)' ?>"><i class="fas fa-file-circle-xmark"></i></div>
            <div><div class="inv-score-val" style="color:<?= $inventory['empty_count']>0?'var(--red)':'var(--green)' ?>"><?= $inventory['empty_count'] ?></div><div class="inv-score-lbl">Fichiers vides</div></div>
        </div>
        <div class="inv-score-card">
            <div class="inv-score-icon" style="background:var(--amber-bg);color:var(--amber)"><i class="fas fa-chart-pie"></i></div>
            <div>
                <?php $avgFiles = $inventory['total_dirs']>0 ? round($inventory['total_files']/$inventory['total_dirs'],1) : 0; ?>
                <div class="inv-score-val"><?= $avgFiles ?></div><div class="inv-score-lbl">Fichiers/dossier</div>
            </div>
        </div>
    </div>

    <!-- Tables DB -->
    <div class="inv-section">
        <div class="inv-section-title"><i class="fas fa-database" style="color:var(--accent)"></i> Tables MySQL &mdash; Description &amp; contenu</div>
        <div class="db-table-grid">
        <?php
        $coreTablesInfo = [
            'leads'=>['icon'=>'fas fa-users','color'=>'#ec4899','tags'=>['CRM','Formulaires','Leads']],
            'builder_pages'=>['icon'=>'fas fa-file-alt','color'=>'#3b82f6','tags'=>['Builder','CMS','Pages']],
            'builder_sections'=>['icon'=>'fas fa-puzzle-piece','color'=>'#3b82f6','tags'=>['Builder','Blocs']],
            'builder_templates'=>['icon'=>'fas fa-palette','color'=>'#3b82f6','tags'=>['Builder','Templates']],
            'properties'=>['icon'=>'fas fa-home','color'=>'#10b981','tags'=>['Immobilier','Biens']],
            'capture_pages'=>['icon'=>'fas fa-magnet','color'=>'#8b5cf6','tags'=>['Marketing','A/B','Leads']],
            'articles'=>['icon'=>'fas fa-newspaper','color'=>'#f59e0b','tags'=>['SEO','Blog','Contenu']],
            'secteurs'=>['icon'=>'fas fa-map-marker-alt','color'=>'#10b981','tags'=>['SEO Local','Quartiers']],
            'settings'=>['icon'=>'fas fa-cog','color'=>'#64748b','tags'=>['Syst&egrave;me','Config']],
            'admins'=>['icon'=>'fas fa-user-shield','color'=>'#64748b','tags'=>['Syst&egrave;me','Auth']],
            'api_keys'=>['icon'=>'fas fa-key','color'=>'#f97316','tags'=>['API','S&eacute;curit&eacute;','Chiffr&eacute;']],
            'gmb_contacts'=>['icon'=>'fab fa-google','color'=>'#06b6d4','tags'=>['GMB','Prospection','B2B']],
            'gmb_sequences'=>['icon'=>'fas fa-stream','color'=>'#06b6d4','tags'=>['GMB','Email','S&eacute;quences']],
        ];
        $existingTables=[];
        try { $existingTables=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN); } catch(Exception $e){}
        $tableRowCounts=[];
        foreach ($existingTables as $t){try{$tableRowCounts[$t]=(int)$db->query("SELECT COUNT(*) FROM `".str_replace('`','',$t)."`")->fetchColumn();}catch(Exception $e){$tableRowCounts[$t]=0;}}
        foreach ($coreTablesInfo as $tname => $tinfo):
            $exists = in_array($tname,$existingTables);
            $rows   = $tableRowCounts[$tname]??0;
            $desc   = $tableDescriptions[$tname]??'';
        ?>
        <div class="db-table-card <?= $exists?'present':'missing' ?>">
            <div class="db-table-card-hd">
                <div style="width:28px;height:28px;border-radius:6px;background:<?= $tinfo['color'] ?>18;color:<?= $tinfo['color'] ?>;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0"><i class="<?= $tinfo['icon'] ?>"></i></div>
                <div class="db-table-card-name"><?= htmlspecialchars($tname) ?></div>
                <div class="db-table-card-rows"><?= $exists?('<span style="color:var(--green)">'.$rows.' lignes</span>') : '<span style="color:var(--red)">ABSENTE</span>' ?></div>
            </div>
            <div class="db-table-card-desc"><?= $desc ?></div>
            <div class="db-table-card-tags">
                <?php foreach ($tinfo['tags'] as $tag): ?><span class="db-table-card-tag" style="background:<?= $tinfo['color'] ?>18;color:<?= $tinfo['color'] ?>"><?= $tag ?></span><?php endforeach; ?>
                <?php if(!$exists): ?><span class="db-table-card-tag" style="background:var(--red-bg);color:var(--red)">&#9888; &Agrave; CR&Eacute;ER</span><?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    </div>

    <!-- Top dirs -->
    <div class="inv-section">
        <div class="inv-section-title"><i class="fas fa-ranking-star" style="color:var(--accent)"></i> Top 20 dossiers par nombre de fichiers</div>
        <?php $maxFiles = max(array_column(array_values($inventory['top_dirs']),'files')?: [1]); ?>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-sm)">
            <table class="inv-table">
                <thead><tr><th>#</th><th>Dossier</th><th style="text-align:center">Fichiers</th><th style="text-align:center">Vides</th><th>R&eacute;partition</th><th style="text-align:right">Taille</th></tr></thead>
                <tbody>
                <?php $rank=1; foreach ($inventory['top_dirs'] as $dirRel => $stats): ?>
                <tr>
                    <td style="color:var(--text-3);font-size:10px;width:24px"><?= $rank++ ?></td>
                    <td class="inv-path"><?= htmlspecialchars('/'.$dirRel) ?></td>
                    <td style="text-align:center;font-weight:700"><?= $stats['files'] ?></td>
                    <td style="text-align:center"><?= $stats['empty']>0 ? '<span class="inv-badge-empty"><i class="fas fa-circle-exclamation"></i> '.$stats['empty'].'</span>' : '<span style="color:var(--text-3);font-size:10px">&mdash;</span>' ?></td>
                    <td style="width:120px"><div class="inv-bar-wrap"><div class="inv-bar" style="width:<?= $maxFiles>0?round($stats['files']/$maxFiles*100):0 ?>%"></div></div></td>
                    <td style="text-align:right;font-size:10px;color:var(--text-3)"><?= $stats['size_bytes']>1048576?round($stats['size_bytes']/1048576,1).' Mo':($stats['size_bytes']>1024?round($stats['size_bytes']/1024,1).' Ko':$stats['size_bytes'].' o') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Fichiers vides -->
    <div class="inv-section">
        <div class="inv-section-title">
            <i class="fas fa-file-circle-xmark" style="color:var(--red)"></i>
            Fichiers vides &mdash; 0 octet
            <span style="background:var(--red-bg);color:var(--red);padding:2px 8px;border-radius:99px;font-size:10px;font-weight:700"><?= $inventory['empty_count'] ?></span>
        </div>
        <?php if (empty($inventory['empty_files'])): ?>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:24px;text-align:center">
            <i class="fas fa-circle-check" style="color:var(--green);font-size:24px;margin-bottom:8px;display:block"></i>
            <div style="font-size:13px;font-weight:700">Aucun fichier vide d&eacute;tect&eacute;</div>
            <div style="font-size:11px;color:var(--text-3);margin-top:4px">Tous les <?= $inventory['total_files'] ?> fichiers ont du contenu</div>
        </div>
        <?php else: ?>
        <div style="display:flex;gap:6px;margin-bottom:10px;flex-wrap:wrap;align-items:center">
            <?php $emptyByExt=[]; foreach ($inventory['empty_files'] as $ef){$emptyByExt[$ef['ext']?:'autre'][]=$ef;} ?>
            <button class="fc active" id="ef-all" onclick="filterEmpty('all',this)">Tous <span class="cnt"><?= count($inventory['empty_files']) ?></span></button>
            <?php foreach ($emptyByExt as $ext => $files): ?>
            <button class="fc" id="ef-<?= $ext ?>" onclick="filterEmpty(<?= json_encode($ext) ?>,this)">.<?= htmlspecialchars($ext) ?> <span class="cnt"><?= count($files) ?></span></button>
            <?php endforeach; ?>
            <input type="text" class="filter-search" placeholder="Filtrer&hellip;" oninput="filterEmptySearch(this.value)" style="max-width:200px;margin-left:auto">
        </div>
        <div class="empty-list">
            <div class="empty-list-hd">
                <i class="fas fa-triangle-exclamation" style="color:var(--red);font-size:11px"></i>
                <span style="font-size:11px;font-weight:700">Ces fichiers existent mais ne contiennent rien</span>
                <span style="font-size:10px;color:var(--text-3);margin-left:auto"><?= htmlspecialchars($rootPath) ?></span>
            </div>
            <div id="empty-files-list">
            <?php foreach ($inventory['empty_files'] as $ef):
                $extColors=['php'=>'var(--accent-2)','json'=>'var(--amber)','css'=>'#06b6d4','js'=>'#eab308','html'=>'#f97316'];
                $extColor=$extColors[$ef['ext']]??'var(--text-3)';
            ?>
            <div class="empty-file-row" data-ext="<?= htmlspecialchars($ef['ext']) ?>" data-path="<?= strtolower(htmlspecialchars($ef['path'])) ?>">
                <div class="empty-file-icon"><i class="fas fa-file" style="color:<?= $extColor ?>"></i></div>
                <div style="min-width:0;flex:1">
                    <div class="empty-file-name"><?= htmlspecialchars($ef['name']) ?></div>
                    <div class="empty-file-dir"><?= htmlspecialchars('/'.$ef['dir']) ?></div>
                </div>
                <span class="empty-file-tag" style="color:<?= $extColor ?>;background:<?= $extColor ?>18">.<?= htmlspecialchars($ef['ext']?:'?') ?></span>
                <span style="font-size:9px;color:var(--red);font-weight:700;padding:2px 6px;background:var(--red-bg);border-radius:4px;flex-shrink:0">0 o</span>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div><!-- /panel-inv -->

<!-- ════════════════════════════════════════════
     ONGLET 4 : STRUCTURE FICHIERS
════════════════════════════════════════════ -->
<div id="panel-tree" style="display:none">
    <div class="tree-wrap">
        <div class="tree-search-bar">
            <i class="fas fa-folder-tree" style="color:var(--accent);font-size:12px"></i>
            <span style="font-size:12px;font-weight:700">Structure compl&egrave;te</span>
            <span style="font-size:10px;color:var(--text-3)"><?= htmlspecialchars($rootPath) ?></span>
            <input type="text" class="filter-search" id="tree-srch" placeholder="Filtrer&hellip;" oninput="filterTree(this.value)" style="max-width:220px;margin-left:auto">
            <button class="btn btn-s btn-sm" onclick="expandAllTree(true)"><i class="fas fa-expand"></i> Tout ouvrir</button>
            <button class="btn btn-s btn-sm" onclick="expandAllTree(false)"><i class="fas fa-compress"></i> Tout fermer</button>
        </div>
        <div class="tree-body" id="tree-body">
            <?php
            function renderTree(array $nodes): void {
                foreach ($nodes as $node) {
                    if ($node['type']==='dir') {
                        $cc=countTreeFiles($node['children']);
                        echo '<div class="tree-node tree-dir-node">';
                        echo '<button class="tree-dir-btn" onclick="toggleTreeDir(this)">';
                        echo '<i class="fas fa-chevron-right tree-dir-icon"></i>';
                        echo '<i class="fas fa-folder-open tree-folder-ic"></i> '.htmlspecialchars($node['name']);
                        echo ' <span class="tree-cnt-badge">'.$cc.'</span>';
                        echo '</button>';
                        echo '<div class="tree-children">';
                        renderTree($node['children']);
                        echo '</div></div>';
                    } else {
                        $ext=$node['ext']??'other';
                        $extCls=in_array($ext,['php','json','css','js','sql','html'])?$ext:'other';
                        $icon=match($ext){'php'=>'fa-code','json'=>'fa-brackets-curly','css'=>'fa-palette','js'=>'fa-file-code','sql'=>'fa-database','html'=>'fa-file-code',default=>'fa-file'};
                        $sz=$node['size']>1024?round($node['size']/1024,1).'Ko':$node['size'].'o';
                        $em=$node['size']===0?' style="color:var(--red)" title="Fichier vide"':'';
                        echo '<div class="tree-node tree-file-node">';
                        echo '<div class="tree-file"'.$em.'>';
                        echo '<i class="fas '.$icon.' tree-file-ic '.$extCls.'"></i>';
                        echo htmlspecialchars($node['name']);
                        if($node['size']===0) echo ' <span style="font-size:8px;background:var(--red-bg);color:var(--red);padding:1px 4px;border-radius:3px;font-weight:700">VIDE</span>';
                        echo '<span class="tree-file-size">'.$sz.'</span>';
                        echo '</div></div>';
                    }
                }
            }
            function countTreeFiles(array $nodes): int {
                $c=0; foreach($nodes as $n) $c+=$n['type']==='file'?1:countTreeFiles($n['children']??[]); return $c;
            }
            $siteTree = buildTree($rootPath, 0, 4);
            function buildTree(string $path, int $depth=0, int $maxDepth=4): array {
                $tree=[];
                if($depth>$maxDepth||!is_dir($path)) return $tree;
                $items=@scandir($path); if(!$items) return $tree;
                sort($items);
                foreach($items as $item){
                    if($item==='.'||$item==='..') continue;
                    $skip=['error_log','diagnostic-error.log','.htaccess','.git','node_modules','vendor','uploads','cache','tmp','logs','.well-known','cgi-bin'];
                    if(in_array($item,$skip)) continue;
                    $full=$path.'/'.$item;
                    if(is_dir($full)){$tree[]=['name'=>$item,'type'=>'dir','path'=>$full,'children'=>buildTree($full,$depth+1,$maxDepth)];}
                    else{$ext=strtolower(pathinfo($item,PATHINFO_EXTENSION));if(!in_array($ext,['php','json','css','js','sql','txt','md','html']))continue;$size=filesize($full);$tree[]=['name'=>$item,'type'=>'file','path'=>$full,'size'=>$size,'ext'=>$ext];}
                }
                return $tree;
            }
            renderTree($siteTree);
            ?>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════
     ONGLET 5 : ASSISTANT IA
════════════════════════════════════════════ -->
<div id="panel-ia" style="display:none">
    <div class="chat-wrap">
        <div class="chat-hdr">
            <div class="chat-hdr-ic"><i class="fas fa-robot"></i></div>
            <div>
                <div class="chat-hdr-title">Assistant IA &mdash; Architecture IMMO LOCAL+</div>
                <div class="chat-hdr-sub">Score <?= $scorePct ?>% &middot; <?= $total ?> modules &middot; <?= $inventory['total_files'] ?> fichiers</div>
            </div>
        </div>
        <div class="quick-qs">
            <?php foreach (['Mes 5 priorit&eacute;s imm&eacute;diates','Modules avec doublons ?','Plan pour passer &agrave; 80%+','Tables DB &agrave; cr&eacute;er','Expliquer les fichiers vides','Risques routing frontend'] as $q): ?>
            <button class="qq" onclick="sendAiMsg(<?= json_encode(html_entity_decode($q)) ?>)"><?= $q ?></button>
            <?php endforeach; ?>
        </div>
        <div class="chat-msgs" id="chat-msgs">
            <div class="msg msg-a">
                <div class="msg-a-lbl">&#129302; Assistant IA</div>
                <span>Bonjour&nbsp;! Je connais votre plateforme&nbsp;:<br><br>
                &bull;&nbsp;<strong><?= $total ?> modules</strong> &middot; Score <strong><?= $scorePct ?>%</strong><br>
                &bull;&nbsp;<strong><?= $inventory['total_dirs'] ?> dossiers</strong> &middot; <strong><?= $inventory['total_files'] ?> fichiers</strong><?php if($inventory['empty_count']>0): ?> &middot; <strong style="color:var(--red)"><?= $inventory['empty_count'] ?> vides&nbsp;!</strong><?php endif; ?><br>
                &bull;&nbsp;<strong><?= $dbTableCount ?> tables</strong> DB<br><br>
                Posez une question ou cliquez sur <strong>Analyse IA</strong>.</span>
            </div>
        </div>
        <div class="typing-w" id="typing-w"><div class="typing"><span></span><span></span><span></span></div></div>
        <div class="chat-footer">
            <textarea class="chat-input" id="chat-input" rows="2"
                placeholder="Question sur vos modules, architecture, fichiers vides&hellip;"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendFromInput()}"></textarea>
            <button class="chat-send" id="send-btn" onclick="sendFromInput()">&rarr;</button>
        </div>
    </div>
</div>

<div class="mod-toast" id="mod-toast"></div>

<script>
const AI_CTX  = <?= json_encode($aiContext) ?>;
const JDATA   = <?= $jsonExport ?>;
const PROXY   = <?= json_encode($proxyUrl) ?>;
let chatHist  = [];
let aiLoading = false;
let currentView = 'list';
let kanbanGroup = 'status';

// ── État filtres ──────────────────────────────────────
const FILTERS = { status: 'all', cat: '', tags: [], search: '' };

// ── Tabs ──────────────────────────────────────────────
function switchModTab(t) {
    ['diag','kanban','inv','tree','ia'].forEach(id => {
        const p = document.getElementById('panel-' + id);
        const b = document.getElementById('tab-' + id);
        if (p) p.style.display = t === id ? '' : 'none';
        if (b) b.classList.toggle('active', t === id);
    });
    // Barre de filtres visible seulement pour diag et kanban
    const fb = document.getElementById('shared-filter-bar');
    const vt = document.getElementById('view-toggle-diag');
    if (fb) fb.style.display = ['diag','kanban'].includes(t) ? '' : 'none';
    if (vt) vt.style.display = t === 'diag' ? '' : 'none';
}

// ── Vue liste / grille ────────────────────────────────
function setView(v) {
    currentView = v;
    document.getElementById('view-list').style.display = v === 'list' ? '' : 'none';
    document.getElementById('view-grid').style.display = v === 'grid' ? '' : 'none';
    document.getElementById('vbtn-list').classList.toggle('active', v === 'list');
    document.getElementById('vbtn-grid').classList.toggle('active', v === 'grid');
    applyAllFilters();
}

// ── Kanban groupBy ────────────────────────────────────
function setKanbanGroup(g) {
    kanbanGroup = g;
    document.getElementById('kgb-status').classList.toggle('active', g === 'status');
    document.getElementById('kgb-cat').classList.toggle('active',    g === 'cat');
    document.getElementById('kanban-status-view').style.display = g === 'status' ? '' : 'none';
    document.getElementById('kanban-cat-view').style.display    = g === 'cat'    ? '' : 'none';
    applyAllFilters();
}

// ── FILTRES ───────────────────────────────────────────
function toggleFilter(btn) {
    const ftype = btn.dataset.ftype;
    const fval  = btn.dataset.fval;

    if (ftype === 'status') {
        document.querySelectorAll('[data-ftype="status"]').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        FILTERS.status = fval;
    } else if (ftype === 'cat') {
        const wasActive = btn.classList.toggle('active');
        FILTERS.cat = wasActive ? fval : '';
        // désactiver les autres cats
        if (wasActive) {
            document.querySelectorAll('[data-ftype="cat"]').forEach(b => { if(b !== btn) b.classList.remove('active'); });
        }
    } else if (ftype === 'tag') {
        btn.classList.toggle('active');
        const idx = FILTERS.tags.indexOf(fval);
        if (idx === -1) FILTERS.tags.push(fval);
        else FILTERS.tags.splice(idx, 1);
    }
    applyAllFilters();
}

function clearAllFilters() {
    FILTERS.status = 'all';
    FILTERS.cat    = '';
    FILTERS.tags   = [];
    FILTERS.search = '';
    document.getElementById('filter-search').value = '';
    document.querySelectorAll('[data-ftype]').forEach(b => b.classList.remove('active'));
    document.querySelector('[data-ftype="status"][data-fval="all"]')?.classList.add('active');
    document.getElementById('filter-clear-btn').classList.remove('show');
    applyAllFilters();
}

function applyAllFilters() {
    FILTERS.search = document.getElementById('filter-search').value.toLowerCase().trim();

    // Mettre à jour bouton clear
    const hasFilter = FILTERS.status !== 'all' || FILTERS.cat !== '' || FILTERS.tags.length > 0 || FILTERS.search !== '';
    document.getElementById('filter-clear-btn').classList.toggle('show', hasFilter);

    let visCount = 0;

    // ── Liste ──
    document.querySelectorAll('#view-list .mod-row').forEach(row => {
        const vis = matchesFilters(row);
        row.style.display = vis ? '' : 'none';
        if (vis) visCount++;
        // Masquer le checks si la row est masquée
        const checks = document.getElementById('checks-' + (row.id?.replace('row-','') || ''));
        if (checks && !vis) checks.style.display = 'none';
    });
    // Afficher/masquer sections vides (liste)
    document.querySelectorAll('#view-list .cat-section').forEach(cat => {
        const vis = [...cat.querySelectorAll('.mod-row')].some(r => r.style.display !== 'none');
        cat.style.display = vis ? '' : 'none';
    });
    const listNoRes = document.getElementById('list-no-results');
    if (listNoRes) listNoRes.style.display = visCount === 0 ? 'block' : 'none';

    // ── Grille ──
    visCount = 0;
    document.querySelectorAll('#view-grid .mod-card').forEach(card => {
        const vis = matchesFilters(card);
        card.style.display = vis ? '' : 'none';
        if (vis) visCount++;
    });
    document.querySelectorAll('#view-grid [data-cat-grid]').forEach(cat => {
        const vis = [...cat.querySelectorAll('.mod-card')].some(r => r.style.display !== 'none');
        cat.style.display = vis ? '' : 'none';
    });
    const gridNoRes = document.getElementById('grid-no-results');
    if (gridNoRes) gridNoRes.style.display = visCount === 0 ? 'block' : 'none';

    // ── Kanban ──
    document.querySelectorAll('.kanban-card').forEach(card => {
        const vis = matchesFilters(card);
        card.style.display = vis ? '' : 'none';
    });
    // Mettre à jour compteurs colonnes kanban
    document.querySelectorAll('.kanban-col').forEach(col => {
        const colId = col.id?.replace('kcol-','');
        const visCount2 = [...col.querySelectorAll('.kanban-card')].filter(c => c.style.display !== 'none').length;
        const badge = col.querySelector('.kanban-col-count');
        if (badge) badge.textContent = visCount2;
    });

    // ── Compteur global ──
    const total = [...document.querySelectorAll('#view-list .mod-row, #view-grid .mod-card')].filter(el => el.style.display !== 'none').length / 2 || 
                  document.querySelectorAll('#view-list .mod-row').length;
    const visTotal = [...document.querySelectorAll('#view-list .mod-row')].filter(el => el.style.display !== 'none').length;
    const countEl  = document.getElementById('filter-count');
    if (countEl) countEl.textContent = visTotal;
}

function matchesFilters(el) {
    const status  = el.dataset.status || '';
    const cat     = el.dataset.cat    || '';
    const name    = el.dataset.name   || '';
    const tags    = el.dataset.tags   || '';
    const enabled = el.dataset.enabled || '1';

    // Statut
    if (FILTERS.status === 'disabled') {
        if (enabled !== '0') return false;
    } else if (FILTERS.status !== 'all') {
        if (status !== FILTERS.status) return false;
    }
    // Catégorie
    if (FILTERS.cat && cat !== FILTERS.cat) return false;
    // Tags
    for (const tag of FILTERS.tags) {
        if (!tags.includes(tag)) return false;
    }
    // Recherche
    if (FILTERS.search && !name.includes(FILTERS.search) && !status.includes(FILTERS.search) && !cat.toLowerCase().includes(FILTERS.search)) return false;
    return true;
}

// ── Toggle expand row / card / kanban ────────────────
function toggleModRow(slug) {
    const row    = document.getElementById('row-' + slug);
    const checks = document.getElementById('checks-' + slug);
    const icon   = document.getElementById('exp-' + slug);
    if (!row || !checks) return;
    const isOpen = !checks.style.display || checks.style.display === 'none';
    checks.style.display = isOpen ? 'block' : 'none';
    if (icon) icon.style.transform = isOpen ? 'rotate(90deg)' : '';
}
function toggleModCard(slug) {
    const card = document.getElementById('card-' + slug);
    if (card) card.classList.toggle('open');
}
function toggleKCard(slug) {
    const card = document.getElementById('kcard-' + slug);
    const det  = document.getElementById('kd-' + slug);
    if (!card || !det) return;
    card.classList.toggle('open');
    det.style.display = card.classList.contains('open') ? 'block' : 'none';
}

// ── Toggle module ON/OFF ──────────────────────────────
function toggleMod(slug, cb) {
    const allRows  = document.querySelectorAll('[id="row-'  + slug + '"],[id="card-' + slug + '"],[id="kcard-' + slug + '"]');
    allRows.forEach(el => el.classList.toggle('disabled', !cb.checked));
    // Sync tous les toggles pour ce slug
    document.querySelectorAll('input[onchange*="' + slug + '"]').forEach(inp => { inp.checked = cb.checked; });
    const fd = new FormData();
    fd.append('ajax_action','toggle'); fd.append('module',slug); fd.append('enable',cb.checked?'1':'0');
    fetch(PROXY,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
        if(d.success) showToast(slug+(d.enabled?' activ&eacute; &#10003;':' d&eacute;sactiv&eacute;'));
    }).catch(()=>showToast('Erreur r&eacute;seau',true));
}

// ── Export ────────────────────────────────────────────
function doExport() {
    const b = new Blob([JSON.stringify(JDATA,null,2)],{type:'application/json'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(b);
    a.download = 'diagnostic-immo-' + new Date().toISOString().slice(0,10) + '.json';
    a.click();
    showToast('Export JSON t&eacute;l&eacute;charg&eacute; &#10003;');
}

// ── Inventaire filtres ────────────────────────────────
function filterEmpty(ext, btn) {
    document.querySelectorAll('#panel-inv .fc').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#empty-files-list .empty-file-row').forEach(row => {
        row.style.display = (ext==='all'||row.dataset.ext===ext)?'':'none';
    });
}
function filterEmptySearch(q) {
    q = q.toLowerCase();
    document.querySelectorAll('#empty-files-list .empty-file-row').forEach(row => {
        row.style.display = (!q||row.dataset.path.includes(q))?'':'none';
    });
}

// ── Tree ──────────────────────────────────────────────
function toggleTreeDir(btn) { btn.classList.toggle('open'); const ch=btn.nextElementSibling; if(ch) ch.classList.toggle('open'); }
function expandAllTree(open) {
    document.querySelectorAll('.tree-dir-btn').forEach(btn => { btn.classList.toggle('open',open); const ch=btn.nextElementSibling; if(ch) ch.classList.toggle('open',open); });
}
function filterTree(q) {
    q=q.toLowerCase().trim();
    if(!q){document.querySelectorAll('.tree-node').forEach(n=>n.style.display='');return;}
    document.querySelectorAll('.tree-file-node').forEach(n=>{n.style.display=n.textContent.toLowerCase().includes(q)?'':'none';});
    document.querySelectorAll('.tree-dir-node').forEach(n=>{const vis=[...n.querySelectorAll('.tree-file-node')].some(k=>k.style.display!=='none');n.style.display=vis?'':'none';if(vis){const btn=n.querySelector('.tree-dir-btn');const ch=n.querySelector('.tree-children');if(btn)btn.classList.add('open');if(ch)ch.classList.add('open');}});
}

// ── IA ────────────────────────────────────────────────
function sendFromInput() {
    const el=document.getElementById('chat-input'); const msg=el.value.trim();
    if(!msg||aiLoading) return; el.value=''; sendAiMsg(msg);
}
async function sendAiMsg(msg) {
    if(!msg||aiLoading) return;
    appendMsg('u',msg); chatHist.push({role:'user',content:msg});
    aiLoading=true; document.getElementById('send-btn').disabled=true;
    document.getElementById('typing-w').style.display=''; scrollChat();

    const realErrors=Object.entries(JDATA.modules||{}).filter(([,m])=>m.status==='error').map(([slug,m])=>`[ERREUR] ${slug}\n`+(m.checks||[]).filter(c=>c.status!=='ok').map(c=>'  - '+c.message).join('\n')).join('\n');
    const realWarnings=Object.entries(JDATA.modules||{}).filter(([,m])=>m.status==='warning').map(([slug,m])=>`[WARNING] ${slug}\n`+(m.checks||[]).filter(c=>c.status!=='ok').map(c=>'  - '+c.message).join('\n')).join('\n');
    const realOk=Object.keys(JDATA.modules||{}).filter(s=>JDATA.modules[s].status==='ok').join(', ');

    const sys=`Tu es expert PHP/MySQL SaaS immobilier.\nRÈGLES : cite UNIQUEMENT les fichiers réels. Ne jamais inventer. Répondre en français concis.\n\n${AI_CTX}\n\nMODULES EN ERREUR :\n${realErrors}\nMODULES EN WARNING :\n${realWarnings}\nMODULES OK : ${realOk}`;
    const messages=chatHist.length<=1?[{role:'user',content:sys+'\n\nQuestion: '+msg}]:[{role:'user',content:sys},{role:'assistant',content:'Compris.'}, ...chatHist.slice(-10)];

    try {
        const res=await fetch(PROXY,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({ajax_action:'ai_proxy',model:'claude-sonnet-4-20250514',max_tokens:2000,messages})});
        const data=await res.json();
        const text=data.content?.[0]?.text||data.error||'Erreur API.';
        chatHist.push({role:'assistant',content:text}); appendMsg('a',text);
    } catch(e) { appendMsg('a','&#10060; '+e.message); }
    aiLoading=false; document.getElementById('send-btn').disabled=false;
    document.getElementById('typing-w').style.display='none'; scrollChat();
}
function triggerAnalysis() {
    sendAiMsg('Analyse le diagnostic réel. Pour chaque module en ERREUR ou WARNING, cite le slug exact et le message d\'erreur. Donne les 5 actions prioritaires. Score actuel : '+Math.round((Object.values(JDATA.modules||{}).filter(m=>m.status==='ok').length/Math.max(Object.keys(JDATA.modules||{}).length,1))*100)+'%.');
}
function appendMsg(role,text) {
    const wrap=document.getElementById('chat-msgs');
    const div=document.createElement('div'); div.className=role==='u'?'msg msg-u':'msg msg-a';
    if(role==='a'){
        const lbl=document.createElement('div');lbl.className='msg-a-lbl';lbl.textContent='🤖 Assistant IA';div.appendChild(lbl);
        const span=document.createElement('span');
        span.innerHTML=text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>').replace(/`([^`]+)`/g,'<code style="background:var(--surface-3);padding:1px 4px;border-radius:3px;font-size:10px">$1</code>').replace(/\n/g,'<br>');
        div.appendChild(span);
    } else { div.textContent=text; }
    wrap.appendChild(div);
}
function scrollChat() { setTimeout(()=>{ const w=document.getElementById('chat-msgs'); if(w) w.scrollTop=w.scrollHeight; },60); }
function showToast(msg,err) { const el=document.getElementById('mod-toast'); el.innerHTML=msg; el.style.borderColor=err?'var(--red)':'var(--border)'; el.classList.add('show'); setTimeout(()=>el.classList.remove('show'),3000); }

// ── Init ──────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser la barre de filtres cachée sur inv/tree/ia
    const fb = document.getElementById('shared-filter-bar');
    if (fb) fb.style.display = ''; // visible par défaut sur diag
    applyAllFilters();
});
</script>