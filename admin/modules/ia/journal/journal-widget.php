<?php
/**
 * journal-widget.php — Widget journal éditorial par canal
 * Inclus par : admin/modules/[canal]/tabs/journal.php
 * Variable requise : $journal_channel (ex: 'blog', 'facebook', ...)
 * Variable optionnelle : $journal_module_label (ex: 'Blog / Articles SEO')
 *
 * Fichier : admin/modules/ai/journal/journal-widget.php
 */

if (!defined('ADMIN_ROUTER')) { http_response_code(403); exit; }
if (!isset($pdo))              { echo '<p style="color:red">PDO non disponible</p>'; return; }
if (empty($journal_channel))   { echo '<p style="color:red">$journal_channel non défini</p>'; return; }

require_once __DIR__ . '/JournalController.php';

$jCtrl = new JournalController($pdo);

// ── Handler AJAX ──────────────────────────────────────────────────
if (!empty($_POST['_ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
    header('Content-Type: application/json; charset=utf-8');
    $action = trim($_POST['action'] ?? '');
    try {
        switch ($action) {
            case 'change_status':
                $ok = $jCtrl->updateStatus((int)($_POST['id'] ?? 0), trim($_POST['status'] ?? ''));
                echo json_encode(['success' => $ok]);
                break;
            case 'delete':
                $ok = $jCtrl->delete((int)($_POST['id'] ?? 0));
                echo json_encode(['success' => $ok]);
                break;
            case 'save_idea':
                $id   = (int)($_POST['id'] ?? 0);
                $data = [
                    'title'           => trim($_POST['titre']         ?? ''),
                    'description'     => trim($_POST['description']   ?? ''),
                    'channel_id'      => $journal_channel,
                    'profile_id'      => trim($_POST['persona_cible'] ?? 'vendeur'),
                    'awareness_level' => trim($_POST['conscience']     ?? 'problem'),
                    'content_type'    => trim($_POST['type_contenu']   ?? 'post-court'),
                    'objective_id'    => trim($_POST['objectif']       ?? 'notoriete'),
                    'status'          => trim($_POST['status']         ?? 'idea'),
                    'notes'           => trim($_POST['notes']          ?? ''),
                    'week_number'     => (int)date('W'),
                    'year'            => (int)date('Y'),
                ];
                if (empty($data['title'])) { echo json_encode(['success'=>false,'error'=>'Titre requis']); break; }
                if ($id > 0) {
                    echo json_encode(['success' => $jCtrl->update($id, $data)]);
                } else {
                    $newId = $jCtrl->create($data);
                    echo json_encode(['success' => $newId > 0, 'id' => $newId]);
                }
                break;
            case 'get_item':
                $item = $jCtrl->getById((int)($_POST['id'] ?? 0));
                echo json_encode($item ? ['success'=>true,'data'=>$item] : ['success'=>false,'error'=>'Introuvable']);
                break;
            case 'bulk':
                $ids = json_decode($_POST['ids'] ?? '[]', true);
                $op  = $_POST['op'] ?? 'delete';
                switch ($op) {
                    case 'status_validated': $n = $jCtrl->bulkValidate($ids); break;
                    case 'status_published': foreach ($ids as $bid) $jCtrl->updateStatus((int)$bid, 'published'); $n = count($ids); break;
                    case 'status_rejete':    $n = $jCtrl->bulkReject($ids); break;
                    default:                 $n = $jCtrl->bulkDelete($ids);
                }
                echo json_encode(['success'=>true,'count'=>$n]);
                break;
            default:
                echo json_encode(['success'=>false,'error'=>"Action '$action' inconnue"]);
        }
    } catch (\Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

// ── Données ───────────────────────────────────────────────────────
$channelInfo  = JournalController::CHANNELS[$journal_channel] ?? ['label'=>$journal_channel,'icon'=>'fas fa-newspaper','color'=>'#6366f1'];
$channelLabel = $journal_module_label ?? $channelInfo['label'];
$channelColor = $channelInfo['color'];
$channelIcon  = $channelInfo['icon'];

$filterStatus = $_GET['status'] ?? 'all';
$filterSem    = (int)($_GET['sem'] ?? 0);
$searchQ      = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 40;
$currentWeek  = JournalController::getCurrentWeek();

$filters = ['channel_id' => $journal_channel];
if ($filterStatus !== 'all') $filters['status']      = $filterStatus;
if ($filterSem > 0)          $filters['week_number'] = $filterSem;
if ($searchQ)                $filters['search']       = $searchQ;

$totalItems = $jCtrl->countList($filters);
$totalPages = max(1, ceil($totalItems / $perPage));
$items      = $jCtrl->getList($filters, $perPage, ($page - 1) * $perPage);
$stats      = $jCtrl->getChannelStats($journal_channel);

// Semaines disponibles
$weeksAvail = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT week_number, year FROM editorial_journal WHERE channel_id=? AND status!='rejected' ORDER BY year, week_number");
    $stmt->execute([$journal_channel]);
    $weeksAvail = $stmt->fetchAll();
} catch (\Exception $e) {}

$PROFILES  = JournalController::PROFILES;
$AWARENESS = JournalController::AWARENESS;
$STATUSES  = JournalController::STATUSES;
$CTYPES    = JournalController::CONTENT_TYPES;
$OBJS      = JournalController::OBJECTIVES;

// URL AJAX de ce widget
$ajaxUrl = '/admin/dashboard.php?page=' . urlencode($_GET['page'] ?? 'journal');
?>

<style>
.jw-wrap{--jw-accent:<?= htmlspecialchars($channelColor) ?>}
.jw-header{background:#fff;border:1px solid var(--border);border-radius:var(--radius-xl);padding:20px 24px;margin-bottom:16px;display:flex;align-items:center;gap:16px;position:relative;overflow:hidden}
.jw-header::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--jw-accent)}
.jw-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0;background:var(--jw-accent)}
.jw-title{flex:1}
.jw-title h2{font-family:var(--font-display);font-size:17px;font-weight:700;letter-spacing:-.02em;margin-bottom:2px}
.jw-title p{font-size:11px;color:var(--text-3)}
.jw-stats{display:flex;gap:20px;flex-shrink:0}
.jw-stat{text-align:center}
.jw-stat-v{font-family:var(--font-display);font-size:22px;font-weight:700;line-height:1}
.jw-stat-l{font-size:9px;color:var(--text-3);text-transform:uppercase;letter-spacing:.06em;font-weight:600;margin-top:1px}
.jw-toolbar{display:flex;align-items:center;gap:8px;margin-bottom:12px;flex-wrap:wrap}
.jw-pills{display:flex;gap:4px;flex-wrap:wrap}
.jw-pill{padding:5px 12px;border-radius:20px;font-size:11px;font-weight:600;cursor:pointer;border:1px solid var(--border);background:var(--surface);color:var(--text-3);transition:all .12s;white-space:nowrap}
.jw-pill:hover{border-color:var(--jw-accent);color:var(--jw-accent)}
.jw-pill.active{background:var(--jw-accent);color:#fff;border-color:var(--jw-accent)}
.jw-search{position:relative;margin-left:auto}
.jw-search input{padding:7px 10px 7px 28px;border:1px solid var(--border);border-radius:20px;font-size:11px;width:180px;font-family:inherit;background:var(--surface-2);transition:all .2s}
.jw-search input:focus{outline:0;border-color:var(--jw-accent);width:220px;background:#fff}
.jw-search i{position:absolute;left:10px;top:50%;transform:translateY(-50%);color:var(--text-3);font-size:10px}
.jw-btn-new{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;background:var(--jw-accent);color:#fff;border:none;border-radius:var(--radius);font-size:11.5px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s;white-space:nowrap;text-decoration:none}
.jw-btn-new:hover{opacity:.88;transform:translateY(-1px)}
.jw-select{padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:11px;font-family:inherit;background:var(--surface);color:var(--text);cursor:pointer}
.jw-bulk-bar{display:flex;align-items:center;gap:8px;padding:10px 14px;background:var(--accent-bg);border:1px solid rgba(79,70,229,.12);border-radius:var(--radius-lg);margin-bottom:10px;transition:all .2s;overflow:hidden}
.jw-bulk-bar:not(.active){max-height:0;padding:0;margin:0;border:0;opacity:0;pointer-events:none}
.jw-bulk-bar.active{max-height:60px;opacity:1}
.jw-bulk-cnt{font-size:12px;font-weight:700;color:var(--accent);flex:1}
.jw-bulk-act{padding:5px 8px;border-radius:var(--radius);border:1px solid var(--border);background:#fff;font-size:10px;font-family:inherit;cursor:pointer}
.jw-bulk-ok{padding:5px 12px;border-radius:var(--radius);background:var(--accent);color:#fff;border:none;font-size:10px;font-weight:600;font-family:inherit;cursor:pointer}
.jw-table-wrap{background:#fff;border:1px solid var(--border);border-radius:var(--radius-xl);overflow:hidden}
.jw-table{width:100%;border-collapse:collapse;font-size:11.5px}
.jw-table th{background:var(--surface-2);padding:9px 12px;text-align:left;font-size:9.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-3);border-bottom:1px solid var(--border);white-space:nowrap}
.jw-table td{padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:middle}
.jw-table tr:last-child td{border-bottom:0}
.jw-table tr:hover td{background:var(--surface-2)}
.jw-table tr[data-id]:hover td{background:rgba(<?= implode(',', sscanf($channelColor, '#%02x%02x%02x') ?: [99,102,241]) ?>,.03)}
.jw-title-cell{max-width:320px}
.jw-item-title{font-weight:700;font-size:12px;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.jw-item-desc{font-size:10px;color:var(--text-3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:280px}
.jw-badge{display:inline-flex;align-items:center;gap:3px;padding:2px 8px;border-radius:10px;font-size:9.5px;font-weight:700;white-space:nowrap}
.jw-badge-profile{color:#fff}
.jw-badge-aware{border:1px solid currentColor;background:transparent}
.jw-badge-status{color:#fff}
.jw-week-badge{font-family:var(--mono);font-size:9px;padding:2px 7px;border-radius:4px;background:var(--surface-2);color:var(--text-3);font-weight:600}
.jw-week-badge.current{background:rgba(<?= implode(',', sscanf($channelColor, '#%02x%02x%02x') ?: [99,102,241]) ?>,.12);color:var(--jw-accent)}
.jw-actions{display:flex;gap:4px;justify-content:flex-end}
.jw-act-btn{width:28px;height:28px;border-radius:var(--radius);border:1px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:10px;color:var(--text-3);transition:all .12s;text-decoration:none}
.jw-act-btn:hover{border-color:var(--jw-accent);color:var(--jw-accent);background:rgba(<?= implode(',', sscanf($channelColor, '#%02x%02x%02x') ?: [99,102,241]) ?>,.06)}
.jw-act-btn.danger:hover{border-color:var(--red);color:var(--red);background:var(--red-bg)}
.jw-empty{text-align:center;padding:50px 20px}
.jw-empty i{font-size:36px;opacity:.15;display:block;margin-bottom:10px;color:var(--jw-accent)}
.jw-empty p{font-size:12px;color:var(--text-3);margin-bottom:14px}
.jw-pagination{display:flex;align-items:center;justify-content:center;gap:4px;padding:14px}
.jw-page-btn{width:30px;height:30px;border-radius:var(--radius);border:1px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:11px;font-weight:600;color:var(--text-3);text-decoration:none;transition:all .12s}
.jw-page-btn:hover{border-color:var(--jw-accent);color:var(--jw-accent)}
.jw-page-btn.active{background:var(--jw-accent);color:#fff;border-color:var(--jw-accent)}
/* Modal */
.jw-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:2000;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity .2s}
.jw-overlay.active{opacity:1;pointer-events:all}
.jw-modal{background:#fff;border-radius:var(--radius-xl);width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);transform:translateY(20px);transition:transform .2s}
.jw-overlay.active .jw-modal{transform:translateY(0)}
.jw-modal-hd{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.jw-modal-hd h3{font-family:var(--font-display);font-size:15px;font-weight:700}
.jw-modal-close{width:28px;height:28px;border-radius:var(--radius);border:1px solid var(--border);background:0;cursor:pointer;font-size:12px;display:flex;align-items:center;justify-content:center;color:var(--text-3)}
.jw-modal-close:hover{background:var(--surface-2)}
.jw-modal-body{padding:20px 24px}
.jw-form-row{margin-bottom:14px}
.jw-form-row label{display:block;font-size:10.5px;font-weight:700;color:var(--text-2);margin-bottom:5px;text-transform:uppercase;letter-spacing:.04em}
.jw-form-row input,.jw-form-row textarea,.jw-form-row select{width:100%;padding:8px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;font-family:inherit;background:var(--surface-2);color:var(--text);transition:all .15s}
.jw-form-row input:focus,.jw-form-row textarea:focus,.jw-form-row select:focus{outline:0;border-color:var(--jw-accent);background:#fff}
.jw-form-row textarea{height:70px;resize:vertical}
.jw-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.jw-modal-ft{padding:16px 24px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:8px}
.jw-btn-cancel{padding:8px 16px;border:1px solid var(--border);background:var(--surface-2);border-radius:var(--radius);font-size:11px;font-weight:600;cursor:pointer;font-family:inherit}
.jw-btn-save{padding:8px 20px;background:var(--jw-accent);color:#fff;border:none;border-radius:var(--radius);font-size:11px;font-weight:600;cursor:pointer;font-family:inherit;transition:all .15s}
.jw-btn-save:hover{opacity:.88}
/* Toast */
.jw-toast{position:fixed;bottom:20px;right:20px;padding:10px 18px;border-radius:var(--radius-lg);font-size:12px;font-weight:600;z-index:9999;opacity:0;transform:translateY(8px);transition:all .2s;pointer-events:none}
.jw-toast.active{opacity:1;transform:translateY(0)}
.jw-toast.ok{background:#059669;color:#fff}
.jw-toast.err{background:#dc2626;color:#fff}
.jw-toast.info{background:#2563eb;color:#fff}
</style>

<div class="jw-wrap">

<!-- Header -->
<div class="jw-header">
    <div class="jw-icon"><i class="<?= htmlspecialchars($channelIcon) ?>"></i></div>
    <div class="jw-title">
        <h2>Journal — <?= htmlspecialchars($channelLabel) ?></h2>
        <p>Idées éditoriales · Semaine <?= $currentWeek['week'] ?> / <?= $currentWeek['year'] ?></p>
    </div>
    <div class="jw-stats">
        <div class="jw-stat"><div class="jw-stat-v" style="color:var(--jw-accent)"><?= $stats['total'] ?></div><div class="jw-stat-l">Total</div></div>
        <div class="jw-stat"><div class="jw-stat-v" style="color:#f59e0b"><?= $stats['ideas']+$stats['planned'] ?></div><div class="jw-stat-l">Idées</div></div>
        <div class="jw-stat"><div class="jw-stat-v" style="color:#7c3aed"><?= $stats['validated']+$stats['writing']+$stats['ready'] ?></div><div class="jw-stat-l">WIP</div></div>
        <div class="jw-stat"><div class="jw-stat-v" style="color:#059669"><?= $stats['published'] ?></div><div class="jw-stat-l">Publiés</div></div>
    </div>
    <a href="?page=journal" class="jw-btn-new" style="margin-left:12px"><i class="fas fa-arrow-left"></i> Hub</a>
</div>

<!-- Toolbar -->
<div class="jw-toolbar">
    <div class="jw-pills" id="jwStatusPills">
        <?php
        $statusFilters = [
            'all'       => 'Tous ' . $stats['total'],
            'idea'      => 'Idées ' . ($stats['ideas']+$stats['planned']),
            'validated' => 'Validés ' . ($stats['validated']),
            'writing'   => 'En cours ' . ($stats['writing']),
            'ready'     => 'Prêts ' . ($stats['ready']),
            'published' => 'Publiés ' . ($stats['published']),
        ];
        foreach ($statusFilters as $sv => $sl): ?>
            <button class="jw-pill<?= $filterStatus===$sv?' active':'' ?>" onclick="JW.filterStatus('<?= $sv ?>')"><?= $sl ?></button>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($weeksAvail)): ?>
    <select class="jw-select" onchange="JW.filterWeek(this.value)">
        <option value="0">Toutes les semaines</option>
        <?php foreach ($weeksAvail as $w): ?>
            <option value="<?= $w['week_number'] ?>"<?= $filterSem==$w['week_number']?' selected':'' ?>>
                S<?= $w['week_number'] ?> / <?= $w['year'] ?><?= $w['week_number']==$currentWeek['week']&&$w['year']==$currentWeek['year']?' ← actuelle':'' ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php endif; ?>

    <div class="jw-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Rechercher…" value="<?= htmlspecialchars($searchQ) ?>" id="jwSearchInput" oninput="JW.debounceSearch(this.value)">
    </div>
    <button class="jw-btn-new" onclick="JW.openNew()"><i class="fas fa-plus"></i> Nouvelle idée</button>
</div>

<!-- Barre bulk -->
<div class="jw-bulk-bar" id="jwBulkBar">
    <span class="jw-bulk-cnt"><span id="jwBulkCnt">0</span> sélectionné(s)</span>
    <select class="jw-bulk-act" id="jwBulkAct">
        <option value="">Action…</option>
        <option value="validate">✅ Valider</option>
        <option value="publish">🚀 Publier</option>
        <option value="reject">❌ Rejeter</option>
        <option value="delete">🗑️ Supprimer</option>
    </select>
    <button class="jw-bulk-ok" onclick="JW.bulkExec()">Appliquer</button>
</div>

<!-- Table -->
<div class="jw-table-wrap">
    <?php if (empty($items)): ?>
    <div class="jw-empty">
        <i class="<?= htmlspecialchars($channelIcon) ?>"></i>
        <p><?= $searchQ || $filterStatus !== 'all' ? 'Aucun résultat pour ces filtres.' : 'Aucune idée pour ce canal.' ?></p>
        <button class="jw-btn-new" onclick="JW.openNew()"><i class="fas fa-plus"></i> Créer la première idée</button>
    </div>
    <?php else: ?>
    <table class="jw-table">
        <thead>
            <tr>
                <th style="width:32px"><input type="checkbox" onchange="JW.toggleAll(this.checked)"></th>
                <th style="width:50px">SEM.</th>
                <th>TITRE / CONTENU</th>
                <th style="width:110px">PROFIL</th>
                <th style="width:110px">CONSCIENCE</th>
                <th style="width:110px">TYPE</th>
                <th style="width:90px">STATUT</th>
                <th style="width:90px;text-align:right">ACTIONS</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item):
            $isCurrent = ($item['week_number'] == $currentWeek['week'] && $item['year'] == $currentWeek['year']);
            $prof   = $PROFILES[$item['profile_id']]   ?? ['label'=>$item['profile_id'],  'color'=>'#999'];
            $aware  = $AWARENESS[$item['awareness_level']] ?? ['short'=>$item['awareness_level'], 'color'=>'#999'];
            $stat   = $STATUSES[$item['status']]        ?? ['label'=>$item['status'],      'color'=>'#999','bg'=>'#eee'];
            $ctype  = $CTYPES[$item['content_type']]    ?? ($item['content_type'] ?: '—');
            $createUrl = $jCtrl->getCreateContentUrl($item);
        ?>
        <tr data-id="<?= $item['id'] ?>">
            <td><input type="checkbox" class="jw-cb" value="<?= $item['id'] ?>" onchange="JW.updateBulk()"></td>
            <td>
                <span class="jw-week-badge<?= $isCurrent?' current':'' ?>">
                    S<?= $item['week_number'] ?>
                </span>
            </td>
            <td class="jw-title-cell">
                <div class="jw-item-title" title="<?= htmlspecialchars($item['title']) ?>"><?= htmlspecialchars($item['title']) ?></div>
                <?php if (!empty($item['description'])): ?>
                <div class="jw-item-desc"><?= htmlspecialchars($item['description']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <span class="jw-badge jw-badge-profile" style="background:<?= $prof['color'] ?>"><?= $prof['label'] ?></span>
            </td>
            <td>
                <span class="jw-badge jw-badge-aware" style="color:<?= $aware['color'] ?>;border-color:<?= $aware['color'] ?>"><?= $aware['short'] ?></span>
            </td>
            <td style="font-size:10.5px;color:var(--text-2)"><?= htmlspecialchars($ctype) ?></td>
            <td>
                <span class="jw-badge jw-badge-status" style="background:<?= $stat['color'] ?>"><?= $stat['label'] ?></span>
            </td>
            <td>
                <div class="jw-actions">
                    <?php if (in_array($item['status'], ['idea','planned'])): ?>
                    <button class="jw-act-btn" onclick="JW.setStatus(<?= $item['id'] ?>,'validated')" title="Valider"><i class="fas fa-check"></i></button>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars($createUrl) ?>" class="jw-act-btn" title="Créer le contenu"><i class="fas fa-arrow-right"></i></a>
                    <button class="jw-act-btn" onclick="JW.editItem(<?= $item['id'] ?>)" title="Modifier"><i class="fas fa-pen"></i></button>
                    <button class="jw-act-btn danger" onclick="JW.deleteItem(<?= $item['id'] ?>)" title="Supprimer"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
    <div class="jw-pagination">
        <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['p'=>$pg])) ?>" class="jw-page-btn<?= $pg===$page?' active':'' ?>"><?= $pg ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal -->
<div class="jw-overlay" id="jwOverlay" onclick="if(event.target===this)JW.closeModal()">
    <div class="jw-modal">
        <div class="jw-modal-hd">
            <h3 id="jwModalTitle">Nouvelle idée</h3>
            <button class="jw-modal-close" onclick="JW.closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="jw-modal-body">
            <input type="hidden" id="jwModalId" value="0">
            <div class="jw-form-row">
                <label>Titre *</label>
                <input type="text" id="jwFTitre" placeholder="Ex: Comment vendre son bien rapidement à Bordeaux ?">
            </div>
            <div class="jw-form-row">
                <label>Description</label>
                <textarea id="jwFDesc" placeholder="Contexte, angle éditorial…"></textarea>
            </div>
            <div class="jw-form-grid">
                <div class="jw-form-row">
                    <label>Profil cible</label>
                    <select id="jwFPersona">
                        <?php foreach ($PROFILES as $pk => $pv): ?>
                        <option value="<?= $pk ?>"><?= $pv['icon'] ?> <?= $pv['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="jw-form-row">
                    <label>Niveau de conscience</label>
                    <select id="jwFConscience">
                        <?php foreach ($AWARENESS as $ak => $av): ?>
                        <option value="<?= $ak ?>">Niv.<?= $av['step'] ?> — <?= $av['short'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="jw-form-row">
                    <label>Type de contenu</label>
                    <select id="jwFType">
                        <?php foreach ($CTYPES as $tk => $tv): ?>
                        <option value="<?= $tk ?>"><?= $tv ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="jw-form-row">
                    <label>Objectif</label>
                    <select id="jwFObjectif">
                        <?php foreach ($OBJS as $ok => $ov): ?>
                        <option value="<?= $ok ?>"><?= $ov['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="jw-form-row">
                    <label>Statut</label>
                    <select id="jwFStatus">
                        <?php foreach ($STATUSES as $sk => $sv): if ($sk === 'rejected') continue; ?>
                        <option value="<?= $sk ?>"><?= $sv['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="jw-form-row">
                <label>Notes</label>
                <textarea id="jwFNotes" style="height:50px" placeholder="Notes internes…"></textarea>
            </div>
        </div>
        <div class="jw-modal-ft">
            <button class="jw-btn-cancel" onclick="JW.closeModal()">Annuler</button>
            <button class="jw-btn-save" onclick="JW.saveModal()"><i class="fas fa-save"></i> Enregistrer</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div class="jw-toast" id="jwToast"></div>

</div><!-- .jw-wrap -->

<script>
const JW = {
    AJAX: <?= json_encode($ajaxUrl) ?>,

    toast(msg, type='ok') {
        const t = document.getElementById('jwToast');
        t.textContent = msg;
        t.className = 'jw-toast ' + type + ' active';
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => t.classList.remove('active'), 3000);
    },

    async _post(data) {
        const fd = new FormData();
        for (const [k,v] of Object.entries(data)) fd.append(k, String(v));
        fd.append('_ajax','1');
        const r = await fetch(this.AJAX, {
            method:'POST',
            headers:{'X-Requested-With':'XMLHttpRequest'},
            body: fd
        });
        const text = await r.text();
        try { return JSON.parse(text); }
        catch(e) { console.error('Réponse non-JSON:', text.substring(0,300)); return {success:false,error:'Réponse serveur invalide'}; }
    },

    filterStatus(s) {
        const u = new URL(window.location); u.searchParams.set('status', s); u.searchParams.delete('p'); location.href = u;
    },
    filterWeek(w) {
        const u = new URL(window.location); u.searchParams.set('sem', w); u.searchParams.delete('p'); location.href = u;
    },
    debounceSearch(v) {
        clearTimeout(this._sTimer);
        this._sTimer = setTimeout(() => {
            const u = new URL(window.location); u.searchParams.set('q', v); u.searchParams.delete('p'); location.href = u;
        }, 500);
    },

    toggleAll(c) {
        document.querySelectorAll('.jw-cb').forEach(cb => cb.checked = c);
        this.updateBulk();
    },
    updateBulk() {
        const n = document.querySelectorAll('.jw-cb:checked').length;
        document.getElementById('jwBulkCnt').textContent = n;
        document.getElementById('jwBulkBar').classList.toggle('active', n > 0);
    },

    async setStatus(id, status) {
        const d = await this._post({action:'change_status', id, status});
        d.success ? (this.toast('Statut mis à jour ✓'), location.reload()) : this.toast(d.error||'Erreur','err');
    },

    async deleteItem(id) {
        if (!confirm('Supprimer cette idée ?')) return;
        const d = await this._post({action:'delete', id});
        if (d.success) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) { row.style.opacity='0'; row.style.transition='.3s'; setTimeout(()=>row.remove(),300); }
            this.toast('Supprimé');
        } else this.toast(d.error||'Erreur','err');
    },

    async bulkExec() {
        const act = document.getElementById('jwBulkAct').value; if (!act) return;
        const ids = [...document.querySelectorAll('.jw-cb:checked')].map(c => +c.value);
        if (!ids.length) return;
        if (act === 'delete' && !confirm(`Supprimer ${ids.length} idée(s) ?`)) return;
        const opMap = {validate:'status_validated', publish:'status_published', reject:'status_rejete', delete:'delete'};
        const d = await this._post({action:'bulk', ids: JSON.stringify(ids), op: opMap[act]||act});
        d.success ? location.reload() : this.toast(d.error||'Erreur','err');
    },

    openNew() {
        document.getElementById('jwModalId').value = '0';
        document.getElementById('jwModalTitle').textContent = 'Nouvelle idée';
        ['jwFTitre','jwFDesc','jwFNotes'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('jwFStatus').value = 'idea';
        document.getElementById('jwOverlay').classList.add('active');
    },

    editItem(id) {
        document.getElementById('jwModalTitle').textContent = 'Modifier l\'idée';
        document.getElementById('jwOverlay').classList.add('active');
        this._post({action:'get_item', id}).then(d => {
            if (!d.success) { this.toast('Introuvable','err'); return; }
            const it = d.data;
            document.getElementById('jwModalId').value      = it.id;
            document.getElementById('jwFTitre').value       = it.title || '';
            document.getElementById('jwFDesc').value        = it.description || '';
            document.getElementById('jwFNotes').value       = it.notes || '';
            document.getElementById('jwFPersona').value     = it.profile_id || 'vendeur';
            document.getElementById('jwFConscience').value  = it.awareness_level || 'problem';
            document.getElementById('jwFType').value        = it.content_type || 'post-court';
            document.getElementById('jwFObjectif').value    = it.objective_id || 'notoriete';
            document.getElementById('jwFStatus').value      = it.status || 'idea';
        });
    },

    closeModal() {
        document.getElementById('jwOverlay').classList.remove('active');
    },

    async saveModal() {
        const id    = document.getElementById('jwModalId').value;
        const titre = document.getElementById('jwFTitre').value.trim();
        if (!titre) { this.toast('Le titre est requis','err'); document.getElementById('jwFTitre').focus(); return; }
        const d = await this._post({
            action:         'save_idea',
            id:             id,
            titre:          titre,
            description:    document.getElementById('jwFDesc').value,
            persona_cible:  document.getElementById('jwFPersona').value,
            conscience:     document.getElementById('jwFConscience').value,
            type_contenu:   document.getElementById('jwFType').value,
            objectif:       document.getElementById('jwFObjectif').value,
            status:         document.getElementById('jwFStatus').value,
            notes:          document.getElementById('jwFNotes').value,
        });
        if (d.success) { this.closeModal(); this.toast(id==='0'?'Idée créée ✓':'Idée mise à jour ✓'); setTimeout(()=>location.reload(), 800); }
        else this.toast(d.error||'Erreur','err');
    },
};
</script>