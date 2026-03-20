<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE AUDIT LOGS ADMIN
 * /admin/modules/system/audit-logs/index.php
 * Acces : dashboard.php?page=audit-logs
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

$pdo = getDB();

// ── AJAX : liste paginee ──────────────────────────────────────
if (!empty($_GET['ajax']) && $_GET['ajax'] === 'list') {
    header('Content-Type: application/json');

    $page    = max(1, (int)($_GET['pg'] ?? 1));
    $perPage = min(100, max(10, (int)($_GET['per_page'] ?? 50)));
    $offset  = ($page - 1) * $perPage;
    $action  = trim($_GET['action_filter'] ?? '');
    $entity  = trim($_GET['entity_filter'] ?? '');
    $search  = trim($_GET['search'] ?? '');

    $where = []; $params = [];
    if ($action) { $where[] = 'action = ?'; $params[] = $action; }
    if ($entity) { $where[] = 'entity_type = ?'; $params[] = $entity; }
    if ($search) {
        $where[] = '(admin_email LIKE ? OR action LIKE ? OR entity_type LIKE ? OR details_json LIKE ?)';
        $s = "%{$search}%";
        $params = array_merge($params, [$s, $s, $s, $s]);
    }
    $wClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    try {
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM audit_logs {$wClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $params[] = $perPage;
        $params[] = $offset;
        $stmt = $pdo->prepare("SELECT * FROM audit_logs {$wClause} ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'per_page'=> $perPage,
            'total_pages' => max(1, ceil($total / $perPage)),
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── AJAX : filtres disponibles ────────────────────────────────
if (!empty($_GET['ajax']) && $_GET['ajax'] === 'filters') {
    header('Content-Type: application/json');
    try {
        $actions  = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
        $entities = $pdo->query("SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type")->fetchAll(PDO::FETCH_COLUMN);
        echo json_encode(['success' => true, 'actions' => $actions, 'entities' => $entities]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── Stats rapides ─────────────────────────────────────────────
$statsToday = 0;
$statsTotal = 0;
$lastAction = null;
try {
    $statsTotal = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    $statsToday = (int)$pdo->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $lastAction = $pdo->query("SELECT created_at FROM audit_logs ORDER BY created_at DESC LIMIT 1")->fetchColumn();
} catch (PDOException $e) {}
?>
<style>
.audit-wrap {
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
.audit-header { display:flex;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap; }
.audit-header-title { font-size:1.25rem;font-weight:900;color:var(--text);letter-spacing:-.02em; }
.audit-header-sub   { font-size:.8rem;color:var(--text-3);margin-top:2px; }

/* Stats cards */
.audit-stats { display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px;margin-bottom:20px; }
.audit-stat-card {
    background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    padding:16px 18px;display:flex;align-items:center;gap:14px;
}
.audit-stat-icon { width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:.95rem;color:#fff;flex-shrink:0; }
.audit-stat-value { font-size:1.4rem;font-weight:900;color:var(--text);line-height:1; }
.audit-stat-label { font-size:.72rem;color:var(--text-3);margin-top:2px; }

/* Toolbar */
.audit-toolbar { display:flex;align-items:center;gap:10px;margin-bottom:14px;flex-wrap:wrap; }
.audit-search-wrap { position:relative;flex:1;min-width:200px; }
.audit-search-wrap i { position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:.75rem;pointer-events:none; }
.audit-search { width:100%;box-sizing:border-box;background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:8px 12px 8px 36px;font-size:.82rem;color:var(--text);outline:none;font-family:inherit;transition:border-color .15s; }
.audit-search:focus { border-color:#6366f1; }
.audit-select { padding:8px 12px;border:1px solid var(--border);border-radius:8px;background:var(--surface);font-size:.78rem;color:var(--text-2);cursor:pointer;font-family:inherit;outline:none; }
.audit-btn { display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:8px;font-size:.78rem;font-weight:700;border:1px solid var(--border);background:var(--surface);color:var(--text-2);cursor:pointer;font-family:inherit;transition:background .15s,border-color .15s,color .15s; }
.audit-btn:hover { background:var(--surface-2);color:var(--text); }
.audit-count-badge { padding:2px 8px;border-radius:20px;background:var(--surface-2);border:1px solid var(--border);font-size:.7rem;font-weight:700;color:var(--text-3); }

/* Table */
.audit-table-wrap { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden; }
.audit-table { width:100%;border-collapse:collapse;font-size:.78rem; }
.audit-table thead th { background:var(--surface-2);padding:10px 14px;text-align:left;font-size:.68rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em;color:var(--text-3);border-bottom:1px solid var(--border);white-space:nowrap; }
.audit-table tbody tr { border-bottom:1px solid var(--border);transition:background .1s; }
.audit-table tbody tr:last-child { border:none; }
.audit-table tbody tr:hover { background:var(--surface-2); }
.audit-table tbody td { padding:8px 14px;vertical-align:top;color:var(--text-2); }

.audit-action-pill { display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:20px;font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em; }
.act-login    { background:#f0fdf4;color:#16a34a; }
.act-logout   { background:#fefce8;color:#ca8a04; }
.act-create   { background:#eff6ff;color:#2563eb; }
.act-update   { background:#fdf4ff;color:#9333ea; }
.act-delete   { background:#fef2f2;color:#dc2626; }
.act-send     { background:#f0fdfa;color:#0d9488; }
.act-import   { background:#faf5ff;color:#7c3aed; }
.act-default  { background:#f8fafc;color:#475569; }

.audit-entity { font-family:monospace;font-size:.72rem;color:var(--text-2); }
.audit-details { font-family:monospace;font-size:.68rem;color:var(--text-3);max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap; }
.audit-ip { font-family:monospace;font-size:.68rem;color:var(--text-3); }
.audit-date { white-space:nowrap;font-family:monospace;font-size:.72rem; }

/* Pagination */
.audit-pagination { display:flex;align-items:center;justify-content:center;gap:6px;margin-top:16px; }
.audit-page-btn { padding:6px 12px;border:1px solid var(--border);border-radius:6px;background:var(--surface);font-size:.75rem;cursor:pointer;color:var(--text-2);font-family:inherit;transition:all .15s; }
.audit-page-btn:hover { background:var(--surface-2);color:var(--text); }
.audit-page-btn.active { background:#0f172a;border-color:#0f172a;color:#fff; }
.audit-page-btn:disabled { opacity:.4;cursor:default; }

.audit-empty { padding:60px 20px;text-align:center;color:var(--text-3); }
.audit-empty i { font-size:2rem;margin-bottom:12px;display:block; }
.audit-loading { padding:40px;text-align:center;color:var(--text-3); }
.audit-loading i { animation:audit-spin .8s linear infinite; }
@keyframes audit-spin { to { transform:rotate(360deg); } }

@media (max-width:700px) {
    .audit-stats { grid-template-columns:1fr 1fr; }
    .audit-table td.col-ip,
    .audit-table td.col-details { display:none; }
    .audit-table th.col-ip,
    .audit-table th.col-details { display:none; }
}
</style>

<div class="audit-wrap">

    <div class="audit-header anim">
        <div>
            <div class="audit-header-title">
                <i class="fas fa-clipboard-list" style="color:#6366f1;margin-right:8px"></i>
                Audit admin
            </div>
            <div class="audit-header-sub">Historique de toutes les actions administrateur</div>
        </div>
        <div style="margin-left:auto;display:flex;gap:8px">
            <button class="audit-btn" onclick="AUDIT.refresh()">
                <i class="fas fa-rotate-right"></i> Actualiser
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="audit-stats anim">
        <div class="audit-stat-card">
            <div class="audit-stat-icon" style="background:#6366f1"><i class="fas fa-list-check"></i></div>
            <div>
                <div class="audit-stat-value"><?= number_format($statsTotal) ?></div>
                <div class="audit-stat-label">Actions totales</div>
            </div>
        </div>
        <div class="audit-stat-card">
            <div class="audit-stat-icon" style="background:#10b981"><i class="fas fa-calendar-day"></i></div>
            <div>
                <div class="audit-stat-value"><?= number_format($statsToday) ?></div>
                <div class="audit-stat-label">Aujourd'hui</div>
            </div>
        </div>
        <div class="audit-stat-card">
            <div class="audit-stat-icon" style="background:#f59e0b"><i class="fas fa-clock"></i></div>
            <div>
                <div class="audit-stat-value" style="font-size:1rem"><?= $lastAction ? date('d/m H:i', strtotime($lastAction)) : '—' ?></div>
                <div class="audit-stat-label">Derniere action</div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="audit-toolbar anim">
        <div class="audit-search-wrap">
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" class="audit-search" id="auditSearch"
                   placeholder="Rechercher dans les logs..." oninput="AUDIT.debounceSearch(this.value)">
        </div>
        <select class="audit-select" id="auditAction" onchange="AUDIT.setAction(this.value)">
            <option value="">Toutes les actions</option>
        </select>
        <select class="audit-select" id="auditEntity" onchange="AUDIT.setEntity(this.value)">
            <option value="">Tous les types</option>
        </select>
        <span class="audit-count-badge" id="auditCount">— entrees</span>
    </div>

    <!-- Table -->
    <div class="audit-table-wrap anim">
        <table class="audit-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Entite</th>
                    <th>Admin</th>
                    <th class="col-details">Details</th>
                    <th class="col-ip">IP</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="auditTableBody">
                <tr><td colspan="6" class="audit-loading"><i class="fas fa-circle-notch"></i> Chargement...</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="audit-pagination" id="auditPagination"></div>

</div>

<script>
const AUDIT = (function() {
    'use strict';

    let state = { page:1, perPage:50, action:'', entity:'', search:'', total:0, totalPages:1, searchTimer:null };

    const actionColors = {
        login:'act-login', logout:'act-logout',
        create:'act-create', update:'act-update', delete:'act-delete',
        send:'act-send', import:'act-import',
    };

    function apiUrl(params) {
        const base = location.href.split('?')[0] + '?page=audit-logs';
        return base + '&' + new URLSearchParams(params).toString();
    }

    function load() {
        const params = { ajax:'list', pg:state.page, per_page:state.perPage };
        if (state.action) params.action_filter = state.action;
        if (state.entity) params.entity_filter = state.entity;
        if (state.search) params.search = state.search;

        fetch(apiUrl(params))
            .then(r => r.json())
            .then(data => {
                if (!data.success) { showError(data.message || 'Erreur'); return; }
                state.total = data.total;
                state.totalPages = data.total_pages;
                renderTable(data.data || []);
                renderPagination();
                const ce = document.getElementById('auditCount');
                if (ce) ce.textContent = data.total + ' entree' + (data.total !== 1 ? 's' : '');
            })
            .catch(() => showError('Erreur reseau'));
    }

    function loadFilters() {
        fetch(apiUrl({ ajax:'filters' }))
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const actionSel = document.getElementById('auditAction');
                const entitySel = document.getElementById('auditEntity');
                (data.actions || []).forEach(a => {
                    const opt = document.createElement('option');
                    opt.value = a; opt.textContent = a;
                    actionSel.appendChild(opt);
                });
                (data.entities || []).forEach(e => {
                    const opt = document.createElement('option');
                    opt.value = e; opt.textContent = e;
                    entitySel.appendChild(opt);
                });
            });
    }

    function renderTable(rows) {
        const tbody = document.getElementById('auditTableBody');
        if (!tbody) return;
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="6"><div class="audit-empty"><i class="fas fa-inbox"></i><p>Aucun log d\'audit</p></div></td></tr>';
            return;
        }
        tbody.innerHTML = rows.map(r => {
            const cls = actionColors[r.action] || 'act-default';
            const details = r.details_json ? truncate(r.details_json, 60) : '—';
            const entity = r.entity_type ? r.entity_type + (r.entity_id ? ' #' + r.entity_id : '') : '—';
            const date = r.created_at ? r.created_at.substring(0, 16).replace('T', ' ') : '—';
            return `<tr>
                <td><span class="audit-action-pill ${cls}">${esc(r.action)}</span></td>
                <td class="audit-entity">${esc(entity)}</td>
                <td style="font-size:.75rem">${esc(r.admin_email || '—')}</td>
                <td class="col-details audit-details" title="${esc(r.details_json || '')}">${esc(details)}</td>
                <td class="col-ip audit-ip">${esc(r.ip_address || '—')}</td>
                <td class="audit-date">${esc(date)}</td>
            </tr>`;
        }).join('');
    }

    function renderPagination() {
        const el = document.getElementById('auditPagination');
        if (!el || state.totalPages <= 1) { if (el) el.innerHTML = ''; return; }
        let html = '';
        html += `<button class="audit-page-btn" ${state.page <= 1 ? 'disabled' : ''} onclick="AUDIT.goPage(${state.page - 1})"><i class="fas fa-chevron-left"></i></button>`;
        const start = Math.max(1, state.page - 2);
        const end = Math.min(state.totalPages, state.page + 2);
        for (let i = start; i <= end; i++) {
            html += `<button class="audit-page-btn ${i === state.page ? 'active' : ''}" onclick="AUDIT.goPage(${i})">${i}</button>`;
        }
        html += `<button class="audit-page-btn" ${state.page >= state.totalPages ? 'disabled' : ''} onclick="AUDIT.goPage(${state.page + 1})"><i class="fas fa-chevron-right"></i></button>`;
        el.innerHTML = html;
    }

    function showError(msg) {
        const tbody = document.getElementById('auditTableBody');
        if (tbody) tbody.innerHTML = `<tr><td colspan="6" style="padding:20px;color:#ef4444;font-size:.8rem"><i class="fas fa-exclamation-triangle"></i> ${esc(msg)}</td></tr>`;
    }

    function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
    function truncate(s, n) { return s.length > n ? s.substring(0, n) + '...' : s; }

    load();
    loadFilters();

    return {
        refresh: () => load(),
        setAction: v => { state.action = v; state.page = 1; load(); },
        setEntity: v => { state.entity = v; state.page = 1; load(); },
        goPage: p => { state.page = Math.max(1, Math.min(state.totalPages, p)); load(); },
        debounceSearch: v => {
            clearTimeout(state.searchTimer);
            state.searchTimer = setTimeout(() => { state.search = v; state.page = 1; load(); }, 300);
        },
    };
})();
</script>
