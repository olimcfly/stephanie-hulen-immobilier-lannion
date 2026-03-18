<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE LOGS ADMIN
 * /admin/modules/system/logs/index.php
 * Accès : dashboard.php?page=logs
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

$root = defined('ROOT_PATH') ? rtrim(ROOT_PATH, '/') : dirname(__DIR__, 4);

$logFiles = [
    'php'   => ['path' => $root . '/admin/error_log',  'label' => 'Erreurs PHP',  'color' => '#ef4444', 'icon' => 'fa-bug'],
    'app'   => ['path' => $root . '/logs/app.log',     'label' => 'App',          'color' => '#6366f1', 'icon' => 'fa-server'],
    'ai'    => ['path' => $root . '/logs/ai.log',      'label' => 'IA',           'color' => '#10b981', 'icon' => 'fa-robot'],
    'login' => ['path' => $root . '/logs/login.log',   'label' => 'Connexions',   'color' => '#f59e0b', 'icon' => 'fa-user-shield'],
];

// ── AJAX tail ─────────────────────────────────────────────────
if (!empty($_GET['ajax']) && $_GET['ajax'] === 'tail') {
    header('Content-Type: application/json');
    $type   = preg_replace('/[^a-z]/', '', $_GET['type'] ?? 'php');
    $lines  = min(500, max(10, (int)($_GET['lines'] ?? 100)));
    $since  = (int)($_GET['since'] ?? 0);
    $filter = trim($_GET['filter'] ?? '');

    if (!isset($logFiles[$type])) { echo json_encode(['error'=>'Type inconnu']); exit; }
    $path = $logFiles[$type]['path'];
    if (!file_exists($path)) { echo json_encode(['lines'=>[],'size'=>0,'mtime'=>0]); exit; }

    $mtime = filemtime($path);
    if ($since && $mtime <= $since) {
        echo json_encode(['lines'=>[],'size'=>filesize($path),'mtime'=>$mtime,'unchanged'=>true]); exit;
    }

    $fp = fopen($path, 'r');
    $buffer = [];
    if ($fp) {
        while (!feof($fp)) {
            $line = fgets($fp);
            if ($line !== false) {
                $buffer[] = rtrim($line);
                if (count($buffer) > $lines) array_shift($buffer);
            }
        }
        fclose($fp);
    }
    if ($filter) $buffer = array_values(array_filter($buffer, fn($l) => stripos($l, $filter) !== false));
    $parsed = array_map(fn($l) => parseLine($l, $type), $buffer);

    echo json_encode(['lines'=>array_values($parsed),'size'=>filesize($path),'mtime'=>$mtime,'total'=>count($parsed)]);
    exit;
}

// ── AJAX clear ────────────────────────────────────────────────
if (!empty($_POST['ajax']) && $_POST['ajax'] === 'clear') {
    header('Content-Type: application/json');
    $type = preg_replace('/[^a-z]/', '', $_POST['type'] ?? '');
    if (!isset($logFiles[$type])) { echo json_encode(['error'=>'Type inconnu']); exit; }
    $path = $logFiles[$type]['path'];
    if (file_exists($path)) file_put_contents($path, '');
    echo json_encode(['success'=>true]); exit;
}

// ── Parser une ligne ──────────────────────────────────────────
function parseLine(string $line, string $type): array {
    $level = 'info'; $date = ''; $msg = $line;
    $lower = strtolower($line);
    if (str_contains($lower,'error')||str_contains($lower,'fatal')||str_contains($lower,'exception')) $level='error';
    elseif (str_contains($lower,'warning')||str_contains($lower,'warn')) $level='warning';
    elseif (str_contains($lower,'notice'))  $level='notice';
    elseif (str_contains($lower,'debug'))   $level='debug';
    elseif (str_contains($lower,'success')||str_contains($lower,'ok')) $level='success';

    if (preg_match('/^\[(\d{2}-\w{3}-\d{4} \d{2}:\d{2}:\d{2}[^\]]*)\]\s*(.*)$/s',$line,$m)) {
        $date=$m[1]; $msg=$m[2];
    } elseif (preg_match('/^(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2})\s+(.*)$/s',$line,$m)) {
        $date=$m[1]; $msg=$m[2];
    }
    return ['level'=>$level,'date'=>$date,'msg'=>$msg,'raw'=>$line];
}

// ── Méta fichiers ─────────────────────────────────────────────
foreach ($logFiles as $key => &$lf) {
    $lf['exists'] = file_exists($lf['path']);
    $lf['size']   = $lf['exists'] ? filesize($lf['path']) : 0;
    $lf['mtime']  = $lf['exists'] ? filemtime($lf['path']) : 0;
    $lf['size_h'] = $lf['size'] > 1048576
        ? round($lf['size']/1048576,1).' Mo'
        : ($lf['size'] > 1024 ? round($lf['size']/1024,1).' Ko' : $lf['size'].' o');
    // Statut enrichi
    $lf['status'] = !$lf['exists'] ? 'absent' : ($lf['size'] === 0 ? 'vide' : 'actif');
}
unset($lf);
?>
<style>
.logs-wrap {
    --lc-navy:  #0f172a;
    --surface:  var(--surface,  #fff);
    --surface-2:var(--surface-2,#f9fafb);
    --border:   var(--border,   #e5e7eb);
    --radius:   var(--radius-lg,12px);
    --shadow:   var(--shadow-sm,0 1px 3px rgba(0,0,0,.08));
    --text:     var(--text,     #111827);
    --text-2:   var(--text-2,   #6b7280);
    --text-3:   var(--text-3,   #9ca3af);
    max-width:1200px; margin:0 auto;
    font-family:'DM Sans',system-ui,sans-serif;
}

/* Header */
.logs-header { display:flex;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap; }
.logs-header-title { font-size:1.25rem;font-weight:900;color:var(--text);letter-spacing:-.02em; }
.logs-header-sub   { font-size:.8rem;color:var(--text-3);margin-top:2px; }

/* Cards */
.logs-files { display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:10px;margin-bottom:20px; }
.logs-file-card {
    background:var(--surface);border:2px solid var(--border);border-radius:var(--radius);
    padding:14px 16px;cursor:pointer;
    transition:border-color .15s,box-shadow .15s,transform .15s;
    display:flex;align-items:center;gap:12px;user-select:none;
}
.logs-file-card:hover { transform:translateY(-2px);box-shadow:var(--shadow); }
.logs-file-card.active { border-color:var(--lfc,#6366f1);background:color-mix(in srgb,var(--lfc,#6366f1) 6%,white); }
.logs-file-icon { width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.9rem;color:#fff;flex-shrink:0; }
.logs-file-name { font-size:.83rem;font-weight:700;color:var(--text); }
.logs-file-meta { font-size:.7rem;color:var(--text-3);margin-top:2px; }

/* Statut card */
.logs-file-status { margin-left:auto;flex-shrink:0; }
.lfs-pill { display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:.62rem;font-weight:700; }
.lfs-actif  { background:#f0fdf4;color:#16a34a; }
.lfs-vide   { background:#fefce8;color:#ca8a04; }
.lfs-absent { background:#fef2f2;color:#dc2626; }

/* Toolbar */
.logs-toolbar { display:flex;align-items:center;gap:10px;margin-bottom:12px;flex-wrap:wrap; }
.logs-search-wrap { position:relative;flex:1;min-width:200px; }
.logs-search-wrap i { position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:.75rem;pointer-events:none; }
.logs-search { width:100%;box-sizing:border-box;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:8px 12px 8px 36px;font-size:.82rem;color:var(--text);outline:none;font-family:inherit;transition:border-color .15s; }
.logs-search:focus { border-color:#6366f1; }
.logs-select { padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--surface);font-size:.78rem;color:var(--text-2);cursor:pointer;font-family:inherit;outline:none; }
.logs-btn { display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;font-size:.78rem;font-weight:700;border:1px solid var(--border);background:var(--surface);color:var(--text-2);cursor:pointer;font-family:inherit;transition:background .15s,border-color .15s,color .15s; }
.logs-btn:hover { background:var(--surface-2);color:var(--text); }
.logs-btn.danger:hover { background:#fef2f2;border-color:#fecaca;color:#dc2626; }
.logs-btn.active-live { background:#f0fdf4;border-color:#86efac;color:#16a34a; }
.logs-btn.active-live i { animation:pulse-dot 1s ease-in-out infinite; }
@keyframes pulse-dot { 0%,100%{opacity:1}50%{opacity:.4} }
.logs-count-badge { padding:2px 8px;border-radius:20px;background:var(--surface-2);border:1px solid var(--border);font-size:.7rem;font-weight:700;color:var(--text-3); }

/* Vue tabs */
.logs-view-tabs { display:flex;gap:4px;margin-bottom:12px; }
.logs-view-tab { padding:6px 14px;border-radius:8px;font-size:.75rem;font-weight:700;border:1px solid var(--border);background:var(--surface);color:var(--text-3);cursor:pointer;transition:all .15s; }
.logs-view-tab.active { background:#0f172a;border-color:#0f172a;color:#fff; }

/* Table */
.logs-table-wrap { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden; }
.logs-table { width:100%;border-collapse:collapse;font-size:.78rem; }
.logs-table thead th { background:var(--surface-2);padding:10px 14px;text-align:left;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3);border-bottom:1px solid var(--border);white-space:nowrap; }
.logs-table tbody tr { border-bottom:1px solid var(--border);transition:background .1s; }
.logs-table tbody tr:last-child { border:none; }
.logs-table tbody tr:hover { background:var(--surface-2); }
.logs-table tbody td { padding:8px 14px;vertical-align:top;color:var(--text-2); }
.logs-table td.col-level { width:90px; }
.logs-table td.col-date  { width:170px;white-space:nowrap;font-family:monospace;font-size:.72rem; }
.logs-table td.col-msg   { word-break:break-all;font-family:monospace;font-size:.75rem; }

.log-level-pill { display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em; }
.lvl-error   { background:#fef2f2;color:#dc2626; }
.lvl-warning { background:#fffbeb;color:#d97706; }
.lvl-notice  { background:#eef2ff;color:#4f46e5; }
.lvl-debug   { background:#f8fafc;color:#64748b; }
.lvl-success { background:#f0fdf4;color:#16a34a; }
.lvl-info    { background:#f8fafc;color:#475569; }

/* Terminal */
.logs-terminal { background:var(--lc-navy);border-radius:var(--radius);overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.25); }
.logs-terminal-bar { background:#1e293b;padding:10px 14px;display:flex;align-items:center;gap:8px;border-bottom:1px solid rgba(255,255,255,.07); }
.logs-terminal-dots { display:flex;gap:5px; }
.logs-terminal-dot  { width:10px;height:10px;border-radius:50%; }
.logs-terminal-title { font-size:.72rem;color:#475569;margin-left:8px;font-family:monospace; }
.logs-terminal-body { padding:14px 16px;height:480px;overflow-y:auto;font-family:'Fira Code','Courier New',monospace;font-size:.72rem;line-height:1.6;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.1) transparent; }
.logs-terminal-body::-webkit-scrollbar { width:4px; }
.logs-terminal-body::-webkit-scrollbar-thumb { background:rgba(255,255,255,.1);border-radius:2px; }
.term-line { display:flex;gap:10px;padding:1px 0; }
.term-line:hover { background:rgba(255,255,255,.03);border-radius:4px; }
.term-date { color:#475569;white-space:nowrap;flex-shrink:0;font-size:.68rem; }
.term-lvl  { font-weight:700;flex-shrink:0;width:56px;font-size:.68rem; }
.term-msg  { color:#94a3b8;word-break:break-all;flex:1; }
.term-lvl.error   { color:#f87171; } .term-msg.error   { color:#fca5a5; }
.term-lvl.warning { color:#fbbf24; } .term-msg.warning { color:#fde68a; }
.term-lvl.notice  { color:#818cf8; } .term-msg.notice  { color:#c7d2fe; }
.term-lvl.debug   { color:#475569; }
.term-lvl.success { color:#34d399; } .term-msg.success { color:#6ee7b7; }
.term-lvl.info    { color:#64748b; }

.logs-empty { padding:60px 20px;text-align:center;color:var(--text-3); }
.logs-empty i { font-size:2rem;margin-bottom:12px;display:block; }
.logs-empty p { font-size:.85rem; }
.logs-loading { padding:40px;text-align:center;color:var(--text-3); }
.logs-loading i { animation:spin .8s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

@media (max-width:700px) {
    .logs-files { grid-template-columns:1fr 1fr; }
    .logs-toolbar { gap:6px; }
    .logs-table td.col-date { display:none; }
}
</style>

<div class="logs-wrap">

    <div class="logs-header anim">
        <div>
            <div class="logs-header-title">
                <i class="fas fa-terminal" style="color:#6366f1;margin-right:8px"></i>
                Logs système
            </div>
            <div class="logs-header-sub">Surveillance en temps réel des erreurs et événements</div>
        </div>
        <div style="margin-left:auto;display:flex;gap:8px">
            <button class="logs-btn" onclick="LOGS.refresh()">
                <i class="fas fa-rotate-right"></i> Actualiser
            </button>
        </div>
    </div>

    <!-- Cards avec statut enrichi -->
    <div class="logs-files anim" id="logsFiles">
        <?php foreach ($logFiles as $key => $lf): ?>
        <div class="logs-file-card <?= $key === 'php' ? 'active' : '' ?>"
             id="fc-<?= $key ?>"
             style="--lfc:<?= $lf['color'] ?>"
             onclick="LOGS.selectType('<?= $key ?>')">
            <div class="logs-file-icon" style="background:<?= $lf['color'] ?>">
                <i class="fas <?= $lf['icon'] ?>"></i>
            </div>
            <div style="min-width:0;flex:1">
                <div class="logs-file-name"><?= $lf['label'] ?></div>
                <div class="logs-file-meta">
                    <?= $lf['exists'] ? $lf['size_h'] : 'Fichier absent' ?>
                    <?php if ($lf['mtime']): ?> · <?= date('H:i', $lf['mtime']) ?><?php endif; ?>
                </div>
            </div>
            <!-- Statut visuel enrichi -->
            <div class="logs-file-status">
                <?php if ($lf['status'] === 'actif'): ?>
                    <span class="lfs-pill lfs-actif"><i class="fas fa-circle-check"></i> Actif</span>
                <?php elseif ($lf['status'] === 'vide'): ?>
                    <span class="lfs-pill lfs-vide"><i class="fas fa-circle"></i> Vide</span>
                <?php else: ?>
                    <span class="lfs-pill lfs-absent"><i class="fas fa-circle-xmark"></i> Absent</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Toolbar -->
    <div class="logs-toolbar anim">
        <div class="logs-search-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" class="logs-search" id="logsSearch"
                   placeholder="Filtrer les logs…" oninput="LOGS.setFilter(this.value)">
        </div>
        <select class="logs-select" id="logsLevel" onchange="LOGS.setLevel(this.value)">
            <option value="">Tous les niveaux</option>
            <option value="error">Erreurs</option>
            <option value="warning">Warnings</option>
            <option value="notice">Notices</option>
            <option value="debug">Debug</option>
            <option value="success">Succès</option>
            <option value="info">Info</option>
        </select>
        <select class="logs-select" id="logsLines" onchange="LOGS.setLines(this.value)">
            <option value="50">50 lignes</option>
            <option value="100" selected>100 lignes</option>
            <option value="200">200 lignes</option>
            <option value="500">500 lignes</option>
        </select>
        <span class="logs-count-badge" id="logsCount">— lignes</span>
        <button class="logs-btn" id="liveBtn" onclick="LOGS.toggleLive()">
            <i class="fas fa-circle" style="font-size:.5rem"></i> Live
        </button>
        <button class="logs-btn danger" onclick="LOGS.clear()">
            <i class="fas fa-trash"></i> Vider
        </button>
    </div>

    <!-- Vue tabs -->
    <div class="logs-view-tabs anim">
        <button class="logs-view-tab active" id="tabTable" onclick="LOGS.setView('table')">
            <i class="fas fa-table"></i> Tableau
        </button>
        <button class="logs-view-tab" id="tabTerm" onclick="LOGS.setView('terminal')">
            <i class="fas fa-terminal"></i> Terminal
        </button>
    </div>

    <!-- Table view -->
    <div id="viewTable" class="anim">
        <div class="logs-table-wrap">
            <table class="logs-table">
                <thead>
                    <tr>
                        <th class="col-level">Niveau</th>
                        <th class="col-date">Date / Heure</th>
                        <th class="col-msg">Message</th>
                    </tr>
                </thead>
                <tbody id="logsTableBody">
                    <tr><td colspan="3" class="logs-loading"><i class="fas fa-circle-notch"></i> Chargement…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Terminal view -->
    <div id="viewTerminal" style="display:none" class="anim">
        <div class="logs-terminal">
            <div class="logs-terminal-bar">
                <div class="logs-terminal-dots">
                    <div class="logs-terminal-dot" style="background:#ef4444"></div>
                    <div class="logs-terminal-dot" style="background:#f59e0b"></div>
                    <div class="logs-terminal-dot" style="background:#10b981"></div>
                </div>
                <span class="logs-terminal-title" id="termTitle">immo-local — logs/php</span>
            </div>
            <div class="logs-terminal-body" id="logsTermBody">
                <div class="logs-loading" style="color:#475569">
                    <i class="fas fa-circle-notch"></i> Chargement…
                </div>
            </div>
        </div>
    </div>

</div>

<script>
const LOGS = (function () {
    'use strict';

    let state = {
        type:'php', lines:100, filter:'', level:'',
        view:'table', live:false, liveInt:null, lastMtime:0, data:[],
    };

    function apiUrl(extra) {
        return location.href.split('?')[0] + '?page=logs' + (extra||'');
    }

    function selectType(type) {
        state.type = type; state.lastMtime = 0;
        document.querySelectorAll('.logs-file-card').forEach(c=>c.classList.remove('active'));
        const fc = document.getElementById('fc-'+type);
        if (fc) fc.classList.add('active');
        const tt = document.getElementById('termTitle');
        if (tt) tt.textContent = 'immo-local — logs/'+type;
        load();
    }

    function load(liveMode) {
        const p = new URLSearchParams({
            ajax:'tail', type:state.type, lines:state.lines,
            filter:state.filter, since:liveMode ? state.lastMtime : 0,
        });
        fetch(apiUrl('&'+p.toString()))
            .then(r=>r.json())
            .then(data=>{
                if (data.error)     { showError(data.error); return; }
                if (data.unchanged) return;
                state.lastMtime = data.mtime || 0;
                state.data = data.lines || [];
                render();
            })
            .catch(()=>showError('Erreur réseau'));
    }

    function render() {
        let rows = state.data;
        if (state.level) rows = rows.filter(r=>r.level===state.level);
        const ce = document.getElementById('logsCount');
        if (ce) ce.textContent = rows.length+' ligne'+(rows.length!==1?'s':'');
        if (state.view==='table') renderTable(rows);
        else renderTerminal(rows);
    }

    function renderTable(rows) {
        const tbody = document.getElementById('logsTableBody');
        if (!tbody) return;
        if (!rows.length) {
            tbody.innerHTML='<tr><td colspan="3"><div class="logs-empty"><i class="fas fa-inbox"></i><p>Aucun log trouvé</p></div></td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(r=>`
            <tr>
                <td class="col-level"><span class="log-level-pill lvl-${r.level}">${r.level}</span></td>
                <td class="col-date">${escHtml(r.date||'—')}</td>
                <td class="col-msg">${highlight(escHtml(r.msg||r.raw||''),state.filter)}</td>
            </tr>`).join('');
    }

    function renderTerminal(rows) {
        const body = document.getElementById('logsTermBody');
        if (!body) return;
        if (!rows.length) { body.innerHTML='<span style="color:#475569">~ no logs found</span>'; return; }
        body.innerHTML = rows.map(r=>`
            <div class="term-line">
                <span class="term-date">${escHtml(r.date||'').substring(0,19)}</span>
                <span class="term-lvl ${r.level}">${r.level.toUpperCase().padEnd(7)}</span>
                <span class="term-msg ${r.level}">${highlight(escHtml(r.msg||r.raw||''),state.filter)}</span>
            </div>`).join('');
        body.scrollTop = body.scrollHeight;
    }

    function toggleLive() {
        state.live = !state.live;
        const btn = document.getElementById('liveBtn');
        if (state.live) {
            btn.classList.add('active-live');
            btn.innerHTML='<i class="fas fa-circle" style="font-size:.5rem"></i> Live ON';
            state.liveInt = setInterval(()=>load(true), 3000);
        } else {
            btn.classList.remove('active-live');
            btn.innerHTML='<i class="fas fa-circle" style="font-size:.5rem"></i> Live';
            clearInterval(state.liveInt);
        }
    }

    function clear() {
        if (!confirm('Vider le fichier de log "'+state.type+'" ?')) return;
        const fd = new FormData();
        fd.append('ajax','clear'); fd.append('type',state.type);
        fetch(apiUrl(), {method:'POST',body:fd})
            .then(r=>r.json()).then(()=>load()).catch(()=>{});
    }

    function setView(v) {
        state.view = v;
        document.getElementById('viewTable').style.display    = v==='table'    ? '' : 'none';
        document.getElementById('viewTerminal').style.display = v==='terminal' ? '' : 'none';
        document.getElementById('tabTable').classList.toggle('active', v==='table');
        document.getElementById('tabTerm').classList.toggle('active',  v==='terminal');
        render();
    }

    function showError(msg) {
        const tbody = document.getElementById('logsTableBody');
        if (tbody) tbody.innerHTML=`<tr><td colspan="3" style="padding:20px;color:#ef4444;font-size:.8rem"><i class="fas fa-exclamation-triangle"></i> ${escHtml(msg)}</td></tr>`;
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function highlight(str, q) {
        if (!q) return str;
        const safe = q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
        return str.replace(new RegExp('('+safe+')','gi'),
            '<mark style="background:#fef08a;color:#000;border-radius:2px">$1</mark>');
    }

    load();

    return { selectType, toggleLive, clear, setView,
             setFilter:v=>{state.filter=v;load();},
             setLevel: v=>{state.level=v;render();},
             setLines: v=>{state.lines=parseInt(v);load();},
             refresh:  ()=>load() };
})();
</script>