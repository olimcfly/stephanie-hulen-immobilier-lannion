<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MESSAGERIE CRM — Interface
 *  /admin/modules/marketing/crm/messenger.php
 *  Route : ?page=messenger   |   AJAX : ?page=messenger&msgrapi=1
 *
 *  Layout 3 colonnes (HubSpot-like) :
 *  [Sidebar comptes] | [Liste threads] | [Conversation + CRM]
 * ══════════════════════════════════════════════════════════════
 */
if (!defined('ADMIN_ROUTER')) { http_response_code(403); exit; }
if (isset($db) && !isset($pdo)) $pdo = $db;

// ── Inclure l'API si appel AJAX ───────────────────────────────
if (!empty($_GET['msgrapi'])) {
    $apiFile = __DIR__ . '/messenger_api.php';
    if (!file_exists($apiFile)) {
        header('Content-Type: application/json');
        echo json_encode(['success'=>false,'error'=>'messenger_api.php introuvable dans '.__DIR__]);
        exit;
    }
    require_once $apiFile;
    exit;
}

// ── Charger les comptes mail du user ──────────────────────────
$mailAccounts = [];
$totalUnread  = 0;
try {
    $mailAccounts = $pdo->query("SELECT id, label, email, from_name, last_sync, active FROM crm_mail_accounts ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($mailAccounts as $acc) {
        $u = $pdo->prepare("SELECT SUM(unread_count) FROM crm_threads WHERE account_id=? AND status='open'");
        $u->execute([$acc['id']]);
        $totalUnread += (int)$u->fetchColumn();
    }
} catch (Exception $e) {}

// ── Leads pour le sélecteur "lier à un lead" ─────────────────
$leads = [];
try {
    $leads = $pdo->query("SELECT id, CONCAT(firstname,' ',lastname) AS name, email FROM leads ORDER BY lastname LIMIT 500")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// ── Prompts IA ────────────────────────────────────────────────
$aiPrompts = [];
if (!empty($mailAccounts)) {
    try {
        $ids = implode(',', array_map('intval', array_column($mailAccounts, 'id')));
        $aiPrompts = $pdo->query("SELECT * FROM crm_ai_prompts WHERE account_id IN ($ids) ORDER BY account_id, is_default DESC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

$firstAccount = $mailAccounts[0] ?? null;
?>

<style>
/* ══════════════════════════════════════════════════════════════
   MESSAGERIE CRM v1.0
══════════════════════════════════════════════════════════════ */
:root {
    --msg-accent:  #6366f1;
    --msg-accent2: #4f46e5;
    --msg-surface: #fff;
    --msg-bg:      #f8fafc;
    --msg-bg2:     #f1f5f9;
    --msg-border:  #e2e8f0;
    --msg-border2: #e9eef5;
    --msg-text:    #111827;
    --msg-text2:   #374151;
    --msg-text3:   #64748b;
    --msg-text4:   #94a3b8;
    --msg-won:     #10b981;
    --msg-danger:  #ef4444;
    --msg-warn:    #f59e0b;
    --msg-in-bg:   #f8fafc;
    --msg-out-bg:  #eef2ff;
    --msg-out-c:   #4f46e5;
    --sidebar-w:   220px;
    --list-w:      320px;
}

/* ── Wrapper global ────────────────────────────────────────── */
.msg-wrap {
    display: flex;
    height: calc(100vh - 120px);
    min-height: 600px;
    background: var(--msg-surface);
    border-radius: 16px;
    border: 1px solid var(--msg-border);
    overflow: hidden;
    box-shadow: 0 4px 24px rgba(0,0,0,.06);
}

/* ══ COLONNE 1 — Sidebar comptes ════════════════════════════ */
.msg-sidebar {
    width: var(--sidebar-w);
    min-width: var(--sidebar-w);
    background: #0f172a;
    display: flex;
    flex-direction: column;
    border-right: 1px solid rgba(255,255,255,.07);
}
.msg-sidebar-header {
    padding: 18px 16px 14px;
    border-bottom: 1px solid rgba(255,255,255,.08);
}
.msg-sidebar-title {
    color: #fff;
    font-size: .9rem;
    font-weight: 800;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 2px;
}
.msg-sidebar-title i { color: #818cf8; }
.msg-sidebar-sub { color: rgba(255,255,255,.4); font-size: .72rem; }

.msg-sidebar-section { padding: 10px 12px 4px; color: rgba(255,255,255,.3); font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; }

.msg-sidebar-item {
    display: flex; align-items: center; gap: 9px;
    padding: 9px 14px; cursor: pointer; transition: background .15s;
    border-radius: 0; position: relative;
}
.msg-sidebar-item:hover { background: rgba(255,255,255,.06); }
.msg-sidebar-item.active { background: rgba(99,102,241,.2); }
.msg-sidebar-item.active::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0;
    width: 3px; background: var(--msg-accent); border-radius: 0 2px 2px 0;
}
.msg-sidebar-avatar {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex; align-items: center; justify-content: center;
    font-size: .7rem; font-weight: 800; color: #fff;
}
.msg-sidebar-info { flex: 1; min-width: 0; }
.msg-sidebar-label { color: #e2e8f0; font-size: .78rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.msg-sidebar-email { color: rgba(255,255,255,.35); font-size: .65rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.msg-sidebar-badge { background: var(--msg-accent); color: #fff; border-radius: 20px; padding: 1px 7px; font-size: .62rem; font-weight: 700; flex-shrink: 0; }

.msg-sidebar-footer { margin-top: auto; padding: 12px; border-top: 1px solid rgba(255,255,255,.07); }
.msg-sidebar-add {
    width: 100%; padding: 8px; border-radius: 9px; border: 1px dashed rgba(255,255,255,.2);
    background: transparent; color: rgba(255,255,255,.5); font-size: .75rem; font-weight: 600;
    cursor: pointer; transition: .15s; font-family: inherit;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.msg-sidebar-add:hover { border-color: var(--msg-accent); color: #a5b4fc; }

.msg-sidebar-views { padding: 4px 0; }
.msg-sidebar-view {
    display: flex; align-items: center; gap: 8px;
    padding: 7px 14px; cursor: pointer; transition: .15s;
    color: rgba(255,255,255,.45); font-size: .77rem; font-weight: 500;
    border-radius: 0;
}
.msg-sidebar-view:hover { color: rgba(255,255,255,.8); background: rgba(255,255,255,.04); }
.msg-sidebar-view.active { color: #fff; background: rgba(99,102,241,.15); }
.msg-sidebar-view i { width: 14px; text-align: center; font-size: .7rem; }

/* ══ COLONNE 2 — Liste threads ══════════════════════════════ */
.msg-list-col {
    width: var(--list-w);
    min-width: var(--list-w);
    border-right: 1px solid var(--msg-border);
    display: flex;
    flex-direction: column;
    background: var(--msg-surface);
}
.msg-list-head {
    padding: 14px 16px 10px;
    border-bottom: 1px solid var(--msg-border2);
    flex-shrink: 0;
}
.msg-list-head-row { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
.msg-list-title { font-size: .9rem; font-weight: 800; color: var(--msg-text); }
.msg-list-count { font-size: .72rem; color: var(--msg-text4); background: var(--msg-bg2); padding: 2px 8px; border-radius: 20px; font-weight: 600; }
.msg-search {
    display: flex; align-items: center; gap: 7px;
    background: var(--msg-bg); border: 1px solid var(--msg-border);
    border-radius: 9px; padding: 7px 11px;
}
.msg-search:focus-within { border-color: var(--msg-accent); }
.msg-search i { color: var(--msg-text4); font-size: .75rem; }
.msg-search input { border: none; outline: none; font-size: .8rem; background: none; color: var(--msg-text2); width: 100%; font-family: inherit; }
.msg-search input::placeholder { color: var(--msg-text4); }

.msg-thread-list { flex: 1; overflow-y: auto; }
.msg-thread-list::-webkit-scrollbar { width: 4px; }
.msg-thread-list::-webkit-scrollbar-thumb { background: var(--msg-border); border-radius: 2px; }

.msg-thread-item {
    padding: 12px 16px; border-bottom: 1px solid var(--msg-border2);
    cursor: pointer; transition: background .12s; position: relative;
}
.msg-thread-item:hover { background: var(--msg-bg); }
.msg-thread-item.active { background: #eef2ff; border-left: 3px solid var(--msg-accent); }
.msg-thread-item.unread .msg-thread-sender { font-weight: 800; color: var(--msg-text); }
.msg-thread-unread-dot {
    width: 8px; height: 8px; border-radius: 50%; background: var(--msg-accent);
    position: absolute; right: 14px; top: 16px; flex-shrink: 0;
}
.msg-thread-row1 { display: flex; align-items: center; justify-content: space-between; margin-bottom: 3px; }
.msg-thread-sender { font-size: .82rem; font-weight: 600; color: var(--msg-text2); }
.msg-thread-date { font-size: .65rem; color: var(--msg-text4); white-space: nowrap; }
.msg-thread-subject { font-size: .78rem; color: var(--msg-text); margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.msg-thread-preview { font-size: .72rem; color: var(--msg-text4); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.msg-thread-lead-tag {
    display: inline-flex; align-items: center; gap: 3px;
    font-size: .6rem; font-weight: 700; padding: 1px 6px; border-radius: 4px;
    background: #dbeafe; color: #1d4ed8; margin-top: 3px;
}
.msg-thread-empty { padding: 40px 20px; text-align: center; color: var(--msg-text4); }
.msg-thread-empty i { font-size: 2rem; opacity: .2; display: block; margin-bottom: 10px; }
.msg-thread-empty p { font-size: .8rem; }

/* ══ COLONNE 3 — Conversation ══════════════════════════════ */
.msg-conv-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 0;
    background: var(--msg-bg);
}

/* ── En-tête conversation ── */
.msg-conv-head {
    padding: 12px 20px;
    background: var(--msg-surface);
    border-bottom: 1px solid var(--msg-border);
    display: flex; align-items: center; gap: 12px; flex-shrink: 0;
}
.msg-conv-avatar {
    width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, var(--msg-accent), #8b5cf6);
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; font-weight: 800; color: #fff;
}
.msg-conv-info { flex: 1; min-width: 0; }
.msg-conv-name { font-size: .9rem; font-weight: 800; color: var(--msg-text); }
.msg-conv-email { font-size: .72rem; color: var(--msg-text3); }
.msg-conv-actions { display: flex; gap: 6px; }
.msg-icon-btn {
    width: 32px; height: 32px; border-radius: 8px; border: 1px solid var(--msg-border);
    background: var(--msg-surface); color: var(--msg-text3); cursor: pointer;
    display: flex; align-items: center; justify-content: center; font-size: .75rem;
    transition: .15s; font-family: inherit;
}
.msg-icon-btn:hover { background: var(--msg-bg2); color: var(--msg-text); }
.msg-icon-btn.danger:hover { background: #fee2e2; color: var(--msg-danger); border-color: #fecaca; }

/* ── CRM context bar ── */
.msg-crm-bar {
    background: linear-gradient(135deg, #0f172a, #1e3a5f);
    padding: 8px 20px;
    display: flex; align-items: center; gap: 12px;
    font-size: .75rem; color: rgba(255,255,255,.65);
    flex-shrink: 0;
}
.msg-crm-bar i { color: #818cf8; }
.msg-crm-link {
    color: #a5b4fc; font-weight: 700; text-decoration: none;
    display: flex; align-items: center; gap: 4px;
}
.msg-crm-link:hover { color: #c7d2fe; }
.msg-crm-bar-actions { margin-left: auto; display: flex; gap: 6px; }
.msg-crm-pill {
    padding: 3px 10px; border-radius: 20px; font-size: .68rem; font-weight: 700;
    background: rgba(255,255,255,.1); color: rgba(255,255,255,.7);
    border: 1px solid rgba(255,255,255,.12); cursor: pointer; transition: .15s;
    font-family: inherit;
}
.msg-crm-pill:hover { background: rgba(99,102,241,.3); color: #c7d2fe; border-color: #6366f1; }

/* ── Fil de messages ── */
.msg-messages {
    flex: 1; overflow-y: auto; padding: 20px;
    display: flex; flex-direction: column; gap: 16px;
}
.msg-messages::-webkit-scrollbar { width: 5px; }
.msg-messages::-webkit-scrollbar-thumb { background: var(--msg-border); border-radius: 3px; }

/* Groupe de date */
.msg-date-sep {
    text-align: center; font-size: .68rem; color: var(--msg-text4); font-weight: 600;
    position: relative; margin: 4px 0;
}
.msg-date-sep::before {
    content: ''; position: absolute; left: 0; right: 0; top: 50%;
    height: 1px; background: var(--msg-border);
}
.msg-date-sep span { background: var(--msg-bg); padding: 0 10px; position: relative; }

/* Message individuel */
.msg-msg { display: flex; flex-direction: column; max-width: 82%; }
.msg-msg.in  { align-self: flex-start; }
.msg-msg.out { align-self: flex-end; align-items: flex-end; }

.msg-msg-meta { font-size: .65rem; color: var(--msg-text4); margin-bottom: 4px; display: flex; align-items: center; gap: 5px; }
.msg-msg.in  .msg-msg-meta { padding-left: 4px; }
.msg-msg.out .msg-msg-meta { padding-right: 4px; }
.msg-ai-badge { background: #f0fdf4; color: #059669; border-radius: 4px; padding: 1px 5px; font-size: .6rem; font-weight: 700; }

.msg-bubble {
    padding: 12px 16px; border-radius: 14px; font-size: .82rem; line-height: 1.6;
    position: relative; box-shadow: 0 1px 4px rgba(0,0,0,.06);
    max-width: 100%; word-wrap: break-word;
}
.msg-msg.in  .msg-bubble { background: var(--msg-surface); border: 1px solid var(--msg-border2); border-radius: 4px 14px 14px 14px; color: var(--msg-text2); }
.msg-msg.out .msg-bubble { background: var(--msg-out-bg); border: 1px solid #c7d2fe; border-radius: 14px 4px 14px 14px; color: var(--msg-out-c); }
.msg-bubble p { margin: 0 0 6px; }
.msg-bubble p:last-child { margin: 0; }
.msg-bubble a { color: var(--msg-accent); }

/* ── Zone composer ── */
.msg-composer {
    background: var(--msg-surface);
    border-top: 1px solid var(--msg-border);
    padding: 14px 20px;
    flex-shrink: 0;
}
.msg-composer-to {
    display: flex; align-items: center; gap: 8px; margin-bottom: 8px;
    padding-bottom: 8px; border-bottom: 1px solid var(--msg-border2);
}
.msg-composer-to label { font-size: .75rem; color: var(--msg-text4); font-weight: 600; white-space: nowrap; }
.msg-composer-to input {
    flex: 1; border: none; outline: none; font-size: .82rem;
    color: var(--msg-text2); font-family: inherit; background: none;
}
.msg-composer-subject {
    display: flex; align-items: center; gap: 8px; margin-bottom: 8px;
    padding-bottom: 8px; border-bottom: 1px solid var(--msg-border2);
}
.msg-composer-subject label { font-size: .75rem; color: var(--msg-text4); font-weight: 600; white-space: nowrap; }
.msg-composer-subject input {
    flex: 1; border: none; outline: none; font-size: .82rem;
    color: var(--msg-text2); font-family: inherit; background: none;
}
.msg-composer-toolbar { display: flex; gap: 4px; margin-bottom: 8px; flex-wrap: wrap; }
.msg-fmt-btn {
    width: 28px; height: 28px; border-radius: 6px; border: 1px solid var(--msg-border);
    background: var(--msg-surface); color: var(--msg-text3); cursor: pointer;
    display: flex; align-items: center; justify-content: center; font-size: .72rem;
    transition: .12s; font-family: inherit;
}
.msg-fmt-btn:hover { background: var(--msg-bg2); color: var(--msg-text); }
.msg-fmt-btn.active { background: var(--msg-accent); color: #fff; border-color: transparent; }
.msg-composer-sep { width: 1px; height: 20px; background: var(--msg-border); margin: 4px 2px; }
.msg-editor {
    min-height: 100px; max-height: 220px; overflow-y: auto;
    font-size: .83rem; line-height: 1.6; color: var(--msg-text2);
    outline: none; padding: 4px 0;
}
.msg-editor:empty::before { content: attr(data-placeholder); color: var(--msg-text4); pointer-events: none; }
.msg-editor p { margin: 0 0 4px; }
.msg-composer-foot {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--msg-border2);
}
.msg-composer-left { display: flex; gap: 6px; align-items: center; }
.msg-send-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 22px; border-radius: 9px; font-size: .82rem; font-weight: 700;
    border: none; cursor: pointer; font-family: inherit;
    background: var(--msg-accent); color: #fff; transition: .2s;
}
.msg-send-btn:hover { background: var(--msg-accent2); transform: translateY(-1px); }
.msg-send-btn:disabled { opacity: .6; pointer-events: none; }
.msg-ai-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 7px 14px; border-radius: 9px; font-size: .78rem; font-weight: 600;
    border: 1px solid #c4b5fd; background: #faf5ff; color: #7c3aed; cursor: pointer;
    transition: .15s; font-family: inherit;
}
.msg-ai-btn:hover { background: #f3e8ff; }
.msg-ai-btn:disabled { opacity: .6; pointer-events: none; }

/* ── Aucun thread sélectionné ── */
.msg-empty-state {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 12px;
    color: var(--msg-text4);
}
.msg-empty-state i { font-size: 3rem; opacity: .15; }
.msg-empty-state h3 { font-size: .95rem; font-weight: 700; color: var(--msg-text3); margin: 0; }
.msg-empty-state p { font-size: .8rem; margin: 0; }

/* ── Aucun compte ── */
.msg-no-account {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 14px;
    padding: 40px;
}
.msg-no-account i { font-size: 2.5rem; color: var(--msg-accent); opacity: .3; }
.msg-no-account h3 { font-size: 1rem; font-weight: 800; color: var(--msg-text); margin: 0; }
.msg-no-account p { font-size: .82rem; color: var(--msg-text3); margin: 0; text-align: center; max-width: 300px; }

/* ══ TOAST STACK ═══════════════════════════════════════════ */
.msg-toast-wrap {
    position: fixed; bottom: 24px; right: 24px; z-index: 10000;
    display: flex; flex-direction: column; gap: 8px; pointer-events: none;
}
.msg-toast-item {
    display: flex; align-items: center; gap: 10px; padding: 12px 18px;
    border-radius: 12px; font-size: .83rem; font-weight: 600;
    background: var(--msg-surface); border: 1px solid var(--msg-border); color: var(--msg-text);
    box-shadow: 0 8px 24px rgba(0,0,0,.12);
    transform: translateY(20px); opacity: 0; transition: all .25s;
    pointer-events: auto; max-width: 320px;
}
.msg-toast-item.visible { transform: translateY(0); opacity: 1; }
.msg-toast-icon { width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 800; }

/* ══ MODALS ═════════════════════════════════════════════════ */
.msg-overlay {
    position: fixed; inset: 0; z-index: 3000;
    background: rgba(15,23,42,.55); backdrop-filter: blur(3px);
    display: none; align-items: center; justify-content: center; padding: 20px;
}
.msg-overlay.open { display: flex; }
.msg-modal {
    background: var(--msg-surface); border-radius: 16px;
    width: 100%; max-width: 560px; max-height: 90vh;
    display: flex; flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,.22);
    transform: scale(.95) translateY(10px); opacity: 0;
    transition: transform .25s cubic-bezier(.16,1,.3,1), opacity .2s;
}
.msg-overlay.open .msg-modal { transform: scale(1) translateY(0); opacity: 1; }
.msg-modal-head {
    padding: 18px 22px; border-bottom: 1px solid var(--msg-border);
    display: flex; align-items: center; justify-content: space-between;
}
.msg-modal-head h3 { font-size: .95rem; font-weight: 700; color: var(--msg-text); margin: 0; display: flex; align-items: center; gap: 8px; }
.msg-modal-head h3 i { color: var(--msg-accent); }
.msg-modal-close { width: 30px; height: 30px; border: none; background: var(--msg-bg2); border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--msg-text3); font-size: .85rem; transition: .15s; }
.msg-modal-close:hover { background: var(--msg-border); transform: rotate(90deg); }
.msg-modal-body { padding: 20px 22px; overflow-y: auto; flex: 1; }
.msg-modal-foot { padding: 14px 22px; border-top: 1px solid var(--msg-border); display: flex; gap: 8px; justify-content: flex-end; }

/* Form */
.msg-form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
.msg-form-group { display: flex; flex-direction: column; margin-bottom: 12px; }
.msg-form-group label { font-size: .77rem; font-weight: 600; color: var(--msg-text2); margin-bottom: 5px; }
.msg-input {
    padding: 9px 12px; border: 1px solid var(--msg-border); border-radius: 8px;
    font-size: .83rem; color: var(--msg-text2); outline: none; font-family: inherit;
    background: var(--msg-surface); width: 100%; box-sizing: border-box; transition: .2s;
}
.msg-input:focus { border-color: var(--msg-accent); box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.msg-form-section { font-size: .8rem; font-weight: 700; color: var(--msg-text); padding-bottom: 7px; border-bottom: 2px solid var(--msg-bg2); margin: 16px 0 12px; display: flex; align-items: center; gap: 6px; }
.msg-form-section:first-child { margin-top: 0; }
.msg-form-section i { color: var(--msg-accent); }
.msg-btn { display: inline-flex; align-items: center; gap: 7px; padding: 9px 18px; border-radius: 9px; font-size: .82rem; font-weight: 600; border: none; cursor: pointer; transition: .2s; font-family: inherit; }
.msg-btn-primary { background: var(--msg-accent); color: #fff; }
.msg-btn-primary:hover { background: var(--msg-accent2); }
.msg-btn-secondary { background: var(--msg-surface); color: var(--msg-text2); border: 1px solid var(--msg-border); }
.msg-btn-secondary:hover { background: var(--msg-bg2); }
.msg-btn-danger { background: var(--msg-danger); color: #fff; }
.msg-btn-danger:hover { filter: brightness(.9); }
.msg-btn:disabled { opacity: .6; pointer-events: none; }

.msg-test-result { padding: 10px 14px; border-radius: 8px; font-size: .8rem; margin-top: 8px; display: none; }
.msg-test-result.ok  { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
.msg-test-result.err { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

@media (max-width: 1024px) {
    :root { --list-w: 260px; --sidebar-w: 180px; }
}
@media (max-width: 768px) {
    .msg-sidebar { display: none; }
    :root { --list-w: 220px; }
}
</style>

<!-- ══ WRAPPER MESSAGERIE ════════════════════════════════════ -->
<div class="msg-wrap" id="msgWrap">

    <!-- ══ COL 1 : SIDEBAR ══════════════════════════════════ -->
    <div class="msg-sidebar">
        <div class="msg-sidebar-header">
            <div class="msg-sidebar-title"><i class="fas fa-envelope"></i> Messagerie</div>
            <div class="msg-sidebar-sub"><?= $totalUnread > 0 ? "$totalUnread non lu" . ($totalUnread > 1 ? 's' : '') : 'Tout lu' ?></div>
        </div>

        <div class="msg-sidebar-section">Vues</div>
        <div class="msg-sidebar-views">
            <div class="msg-sidebar-view active" data-view-filter="open">
                <i class="fas fa-inbox"></i> Boîte de réception
            </div>
            <div class="msg-sidebar-view" data-view-filter="all">
                <i class="fas fa-th-list"></i> Tous les threads
            </div>
            <div class="msg-sidebar-view" data-view-filter="closed">
                <i class="fas fa-check-circle"></i> Fermés
            </div>
            <div class="msg-sidebar-view" data-view-filter="spam">
                <i class="fas fa-ban"></i> Spam
            </div>
        </div>

        <?php if (!empty($mailAccounts)): ?>
        <div class="msg-sidebar-section">Comptes</div>
        <?php foreach ($mailAccounts as $i => $acc):
            $initials = strtoupper(substr($acc['from_name'] ?: $acc['email'], 0, 2));
        ?>
        <div class="msg-sidebar-item <?= $i === 0 ? 'active' : '' ?>"
             data-account-id="<?= $acc['id'] ?>">
            <div class="msg-sidebar-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="msg-sidebar-info">
                <div class="msg-sidebar-label"><?= htmlspecialchars($acc['label']) ?></div>
                <div class="msg-sidebar-email"><?= htmlspecialchars($acc['email']) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <div class="msg-sidebar-footer">
            <button class="msg-sidebar-add" id="btnAddAccount">
                <i class="fas fa-plus"></i> Ajouter un compte
            </button>
        </div>
    </div>

    <!-- ══ COL 2 : LISTE THREADS ════════════════════════════ -->
    <div class="msg-list-col">
        <div class="msg-list-head">
            <div class="msg-list-head-row">
                <span class="msg-list-title">Conversations</span>
                <span class="msg-list-count" id="msgListCount">—</span>
            </div>
            <div class="msg-search">
                <i class="fas fa-search"></i>
                <input type="text" id="msgSearchInput" placeholder="Rechercher…">
            </div>
        </div>
        <div class="msg-thread-list" id="msgThreadList">
            <div class="msg-thread-empty" id="msgListPlaceholder">
                <?php if (empty($mailAccounts)): ?>
                <i class="fas fa-plug"></i>
                <p>Configurez un compte email pour commencer.</p>
                <?php else: ?>
                <i class="fas fa-sync fa-spin" style="opacity:.3;font-size:1.5rem"></i>
                <p>Chargement…</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ══ COL 3 : CONVERSATION ════════════════════════════ -->
    <div class="msg-conv-col" id="msgConvCol">

        <?php if (empty($mailAccounts)): ?>
        <!-- Pas de compte -->
        <div class="msg-no-account">
            <i class="fas fa-envelope-open"></i>
            <h3>Connectez votre boîte email</h3>
            <p>Configurez vos paramètres SMTP/IMAP pour envoyer et recevoir des emails directement depuis votre CRM.</p>
            <button class="msg-btn msg-btn-primary" id="btnAddAccountMain">
                <i class="fas fa-plus"></i> Configurer un compte
            </button>
        </div>
        <?php else: ?>
        <!-- État vide par défaut -->
        <div class="msg-empty-state" id="msgEmptyState">
            <i class="fas fa-comments"></i>
            <h3>Sélectionnez une conversation</h3>
            <p>Cliquez sur un thread dans la liste ou composez un nouveau message.</p>
            <button class="msg-btn msg-btn-primary" id="btnComposeNew" style="margin-top:8px">
                <i class="fas fa-pen"></i> Nouveau message
            </button>
        </div>

        <!-- Conversation (masquée par défaut) -->
        <div id="msgConvView" style="display:none;flex:1;flex-direction:column;overflow:hidden">

            <!-- En-tête -->
            <div class="msg-conv-head">
                <div class="msg-conv-avatar" id="convAvatar">—</div>
                <div class="msg-conv-info">
                    <div class="msg-conv-name" id="convName">—</div>
                    <div class="msg-conv-email" id="convEmail">—</div>
                </div>
                <div class="msg-conv-actions">
                    <button class="msg-icon-btn" id="btnSyncThread" title="Synchroniser IMAP"><i class="fas fa-sync"></i></button>
                    <button class="msg-icon-btn" id="btnCloseThread" title="Fermer la conversation"><i class="fas fa-check"></i></button>
                    <button class="msg-icon-btn danger" id="btnDeleteThread" title="Supprimer"><i class="fas fa-trash"></i></button>
                </div>
            </div>

            <!-- Barre CRM -->
            <div class="msg-crm-bar" id="convCrmBar">
                <i class="fas fa-user-tie"></i>
                <span id="convLeadInfo">Non lié à un lead</span>
                <div class="msg-crm-bar-actions">
                    <button class="msg-crm-pill" id="btnLinkLead">
                        <i class="fas fa-link"></i> Lier un lead
                    </button>
                    <button class="msg-crm-pill" id="btnViewLead" style="display:none">
                        <i class="fas fa-external-link-alt"></i> Voir le lead
                    </button>
                </div>
            </div>

            <!-- Messages -->
            <div class="msg-messages" id="msgMessages"></div>

            <!-- Composer réponse -->
            <div class="msg-composer" id="msgComposer">
                <div class="msg-composer-toolbar">
                    <button class="msg-fmt-btn" data-cmd="bold"        title="Gras"><i class="fas fa-bold"></i></button>
                    <button class="msg-fmt-btn" data-cmd="italic"      title="Italique"><i class="fas fa-italic"></i></button>
                    <button class="msg-fmt-btn" data-cmd="underline"   title="Souligné"><i class="fas fa-underline"></i></button>
                    <div class="msg-composer-sep"></div>
                    <button class="msg-fmt-btn" data-cmd="insertUnorderedList" title="Liste"><i class="fas fa-list-ul"></i></button>
                    <button class="msg-fmt-btn" data-cmd="createLink"  title="Lien"><i class="fas fa-link"></i></button>
                    <div class="msg-composer-sep"></div>
                    <button class="msg-fmt-btn" data-cmd="removeFormat" title="Effacer formatage"><i class="fas fa-eraser"></i></button>
                </div>
                <div class="msg-editor" id="msgEditor" contenteditable="true" data-placeholder="Rédigez votre réponse…"></div>
                <div class="msg-composer-foot">
                    <div class="msg-composer-left">
                        <button class="msg-ai-btn" id="btnAiSuggest">
                            <i class="fas fa-robot"></i> Réponse IA
                        </button>
                        <select class="msg-input" id="aiPromptSel" style="width:auto;padding:6px 10px;font-size:.75rem">
                            <?php foreach ($aiPrompts as $p): ?>
                            <option value="<?= $p['id'] ?>" data-account="<?= $p['account_id'] ?>">
                                <?= htmlspecialchars($p['name']) ?><?= $p['is_default'] ? ' ★' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="msg-send-btn" id="btnSend">
                        <i class="fas fa-paper-plane"></i> Envoyer
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /msg-conv-col -->
</div><!-- /msg-wrap -->

<!-- ══ MODAL : NOUVEAU MESSAGE ══════════════════════════════ -->
<div class="msg-overlay" id="modalCompose">
    <div class="msg-modal">
        <div class="msg-modal-head">
            <h3><i class="fas fa-pen"></i> Nouveau message</h3>
            <button class="msg-modal-close" data-close="modalCompose"><i class="fas fa-times"></i></button>
        </div>
        <div class="msg-modal-body">
            <div class="msg-form-group">
                <label>Compte expéditeur</label>
                <select class="msg-input" id="composeAccount">
                    <?php foreach ($mailAccounts as $acc): ?>
                    <option value="<?= $acc['id'] ?>"><?= htmlspecialchars($acc['label']) ?> — <?= htmlspecialchars($acc['email']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="msg-form-group">
                <label>À <span style="color:#ef4444">*</span></label>
                <input type="email" class="msg-input" id="composeTo" placeholder="destinataire@email.com" autocomplete="off">
            </div>
            <div class="msg-form-group">
                <label>Objet <span style="color:#ef4444">*</span></label>
                <input type="text" class="msg-input" id="composeSubject" placeholder="Objet du message">
            </div>
            <div class="msg-form-group">
                <label>Message</label>
                <div class="msg-editor" id="composeEditor" contenteditable="true"
                     data-placeholder="Rédigez votre message…"
                     style="border:1px solid var(--msg-border);border-radius:8px;padding:12px;min-height:160px"></div>
            </div>
        </div>
        <div class="msg-modal-foot">
            <button class="msg-btn msg-btn-secondary" data-close="modalCompose">Annuler</button>
            <button class="msg-btn msg-btn-primary" id="btnComposeSend">
                <i class="fas fa-paper-plane"></i> Envoyer
            </button>
        </div>
    </div>
</div>

<!-- ══ MODAL : COMPTE SMTP/IMAP ═════════════════════════════ -->
<div class="msg-overlay" id="modalAccount">
    <div class="msg-modal" style="max-width:640px">
        <div class="msg-modal-head">
            <h3><i class="fas fa-cog"></i> <span id="modalAccountTitle">Configurer un compte email</span></h3>
            <button class="msg-modal-close" data-close="modalAccount"><i class="fas fa-times"></i></button>
        </div>
        <div class="msg-modal-body">
            <input type="hidden" id="accountId">

            <!-- Bannière erreur visible sans scroll -->
            <div id="accErrBanner" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:.8rem;color:#dc2626;gap:8px;align-items:center">
                <i class="fas fa-circle-exclamation"></i> <span id="accErrMsg"></span>
            </div>

            <div class="msg-form-section" style="margin-top:0"><i class="fas fa-paper-plane"></i> Envoi — SMTP</div>
            <div class="msg-form-row">
                <div class="msg-form-group">
                    <label>Hôte SMTP <span style="color:#ef4444">*</span></label>
                    <input type="text" class="msg-input" id="accSmtpHost" placeholder="smtp.gmail.com">
                </div>
                <div class="msg-form-group">
                    <label>Port</label>
                    <input type="number" class="msg-input" id="accSmtpPort" value="587" min="1" max="65535">
                </div>
            </div>
            <div class="msg-form-row">
                <div class="msg-form-group">
                    <label>Utilisateur SMTP</label>
                    <input type="text" class="msg-input" id="accSmtpUser" placeholder="moi@gmail.com" autocomplete="off"
                           oninput="if(!document.getElementById('accEmail').value) document.getElementById('accEmail').value=this.value"
                           onblur="if(!document.getElementById('accEmail').value) document.getElementById('accEmail').value=this.value; if(!document.getElementById('accImapUser').value) document.getElementById('accImapUser').value=this.value">
                </div>
                <div class="msg-form-group">
                    <label>Mot de passe SMTP</label>
                    <input type="password" class="msg-input" id="accSmtpPass" placeholder="Laisser vide pour conserver" autocomplete="new-password">
                </div>
            </div>
            <div class="msg-form-group">
                <label>Sécurité</label>
                <select class="msg-input" id="accSmtpSecure">
                    <option value="tls">STARTTLS (port 587)</option>
                    <option value="ssl">SSL/TLS (port 465)</option>
                    <option value="none">Aucune (déconseillé)</option>
                </select>
            </div>

            <div class="msg-form-section"><i class="fas fa-inbox"></i> Réception — IMAP</div>
            <div class="msg-form-row">
                <div class="msg-form-group">
                    <label>Hôte IMAP</label>
                    <input type="text" class="msg-input" id="accImapHost" placeholder="imap.gmail.com">
                </div>
                <div class="msg-form-group">
                    <label>Port</label>
                    <input type="number" class="msg-input" id="accImapPort" value="993" min="1" max="65535">
                </div>
            </div>
            <div class="msg-form-row">
                <div class="msg-form-group">
                    <label>Utilisateur IMAP</label>
                    <input type="text" class="msg-input" id="accImapUser" placeholder="moi@gmail.com" autocomplete="off">
                </div>
                <div class="msg-form-group">
                    <label>Mot de passe IMAP</label>
                    <input type="password" class="msg-input" id="accImapPass" placeholder="Laisser vide pour conserver" autocomplete="new-password">
                </div>
            </div>
            <div class="msg-form-row">
                <div class="msg-form-group">
                    <label>Sécurité IMAP</label>
                    <select class="msg-input" id="accImapSecure">
                        <option value="ssl">SSL/TLS (port 993)</option>
                        <option value="tls">STARTTLS</option>
                        <option value="none">Aucune</option>
                    </select>
                </div>
                <div class="msg-form-group">
                    <label>Dossier IMAP</label>
                    <input type="text" class="msg-input" id="accImapFolder" value="INBOX">
                </div>
            </div>

            <div id="accTestResult" class="msg-test-result"></div>

            <div class="msg-form-section"><i class="fas fa-id-card"></i> Identité &amp; affichage</div>
            <div class="msg-form-row">
                <div class="msg-form-group">
                    <label>Adresse email expéditeur <span style="color:#ef4444">*</span></label>
                    <input type="email" class="msg-input" id="accEmail" placeholder="moi@mondomaine.fr">
                    <small style="color:#94a3b8;font-size:.7rem;margin-top:3px">Sera auto-rempli depuis l'utilisateur SMTP</small>
                </div>
                <div class="msg-form-group">
                    <label>Nom affiché à l'expéditeur</label>
                    <input type="text" class="msg-input" id="accFromName" placeholder="Jean Dupont">
                </div>
            </div>
            <div class="msg-form-group">
                <label>Libellé du compte (interne)</label>
                <input type="text" class="msg-input" id="accLabel" placeholder="Mon email pro">
            </div>

            <div class="msg-form-section"><i class="fas fa-robot"></i> Prompt IA par défaut</div>
            <div class="msg-form-group">
                <textarea class="msg-input" id="accAiPrompt" rows="4" style="resize:vertical"
                    placeholder="Ex: Tu es l'assistant email d'un conseiller immobilier…"></textarea>
            </div>
        </div>
        <div class="msg-modal-foot">
            <button class="msg-btn msg-btn-secondary" id="btnTestAccount">
                <i class="fas fa-plug"></i> Tester la connexion
            </button>
            <button class="msg-btn msg-btn-secondary" data-close="modalAccount">Annuler</button>
            <button class="msg-btn msg-btn-primary" id="btnSaveAccount">
                <i class="fas fa-save"></i> Enregistrer
            </button>
        </div>
    </div>
</div>

<!-- ══ MODAL : LIER UN LEAD ══════════════════════════════════ -->
<div class="msg-overlay" id="modalLinkLead">
    <div class="msg-modal" style="max-width:420px">
        <div class="msg-modal-head">
            <h3><i class="fas fa-user-tie"></i> Lier à un lead CRM</h3>
            <button class="msg-modal-close" data-close="modalLinkLead"><i class="fas fa-times"></i></button>
        </div>
        <div class="msg-modal-body">
            <div class="msg-form-group">
                <label>Sélectionner le lead</label>
                <select class="msg-input" id="linkLeadSel">
                    <option value="">— Aucun lead (délier) —</option>
                    <?php foreach ($leads as $l): ?>
                    <option value="<?= $l['id'] ?>"><?= htmlspecialchars($l['name']) ?> — <?= htmlspecialchars($l['email']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="msg-modal-foot">
            <button class="msg-btn msg-btn-secondary" data-close="modalLinkLead">Annuler</button>
            <button class="msg-btn msg-btn-primary" id="btnConfirmLinkLead">
                <i class="fas fa-link"></i> Lier
            </button>
        </div>
    </div>
</div>

<!-- ══ MODAL : CONFIRM SUPPRESSION ═══════════════════════════ -->
<div class="msg-overlay" id="modalConfirmDel">
    <div class="msg-modal" style="max-width:400px">
        <div class="msg-modal-head">
            <h3 style="color:#ef4444"><i class="fas fa-trash"></i> Supprimer ?</h3>
            <button class="msg-modal-close" data-close="modalConfirmDel"><i class="fas fa-times"></i></button>
        </div>
        <div class="msg-modal-body">
            <p style="font-size:.85rem;color:#374151" id="confirmDelMsg">Cette conversation sera définitivement supprimée.</p>
        </div>
        <div class="msg-modal-foot">
            <button class="msg-btn msg-btn-secondary" data-close="modalConfirmDel">Annuler</button>
            <button class="msg-btn msg-btn-danger" id="btnConfirmDel"><i class="fas fa-trash"></i> Supprimer</button>
        </div>
    </div>
</div>

<!-- ══ TOASTS ════════════════════════════════════════════════ -->
<div class="msg-toast-wrap" id="msgToastWrap"></div>

<!-- ══ DATA PHP → JS ════════════════════════════════════════ -->
<script>
const MSG_API      = '?page=messenger&msgrapi=1';
const MSG_ACCOUNTS = <?= json_encode($mailAccounts, JSON_UNESCAPED_UNICODE) ?>;
const MSG_LEADS    = <?= json_encode($leads, JSON_UNESCAPED_UNICODE) ?>;

/* ══════════════════════════════════════════════════════════════
   MESSAGERIE CRM — JS
══════════════════════════════════════════════════════════════ */
const MSG = (() => {
    const $  = id => document.getElementById(id);
    const $$ = sel => document.querySelectorAll(sel);

    // ── État ────────────────────────────────────────────────
    let activeAccountId = MSG_ACCOUNTS[0]?.id || null;
    let activeThreadId  = null;
    let activeThread    = null;
    let viewFilter      = 'open';
    let searchQ         = '';
    let searchTimer     = null;
    let _delTarget      = null;

    // ── API ─────────────────────────────────────────────────
    async function api(data) {
        const fd = new FormData();
        for (const [k, v] of Object.entries(data)) {
            if (v !== null && v !== undefined) fd.append(k, String(v));
        }
        const r = await fetch(MSG_API, { method: 'POST', body: fd });
        return r.json();
    }

    // ── Toast ────────────────────────────────────────────────
    function toast(msg, type = 'success') {
        const colors = { success:'#059669', error:'#dc2626', info:'#6366f1', warning:'#d97706' };
        const icons  = { success:'✓', error:'✕', info:'ℹ', warning:'!' };
        const wrap   = $('msgToastWrap');
        const t      = document.createElement('div');
        t.className  = 'msg-toast-item';
        t.innerHTML  = `<span class="msg-toast-icon" style="background:${colors[type]}22;color:${colors[type]}">${icons[type]}</span>${msg}`;
        wrap.appendChild(t);
        requestAnimationFrame(() => t.classList.add('visible'));
        setTimeout(() => { t.classList.remove('visible'); setTimeout(() => t.remove(), 280); }, 3500);
    }

    // ── Modal helpers ────────────────────────────────────────
    function openModal(id) { $(id)?.classList.add('open'); }
    function closeModal(id) { $(id)?.classList.remove('open'); }

    // ── Fermer modals avec boutons data-close ────────────────
    document.addEventListener('click', e => {
        const cl = e.target.closest('[data-close]');
        if (cl) closeModal(cl.dataset.close);
        // Backdrop
        if (e.target.classList.contains('msg-overlay')) {
            closeModal(e.target.id);
        }
    });
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') $$('.msg-overlay.open').forEach(m => closeModal(m.id));
    });

    // ══ THREADS ══════════════════════════════════════════════

    async function loadThreads() {
        if (!activeAccountId) return;
        const list = $('msgThreadList');
        list.innerHTML = '<div class="msg-thread-empty"><i class="fas fa-sync fa-spin" style="opacity:.3;font-size:1.5rem"></i><p>Chargement…</p></div>';
        const res = await api({ action:'threads_list', account_id:activeAccountId, status:viewFilter, search:searchQ });
        if (!res.success) { toast(res.error || 'Erreur chargement', 'error'); return; }
        $('msgListCount').textContent = res.total;
        if (!res.threads.length) {
            list.innerHTML = '<div class="msg-thread-empty"><i class="fas fa-inbox"></i><p>Aucune conversation</p></div>';
            return;
        }
        list.innerHTML = res.threads.map(th => threadItemHtml(th)).join('');
    }

    function threadItemHtml(th) {
        const unreadDot = th.unread_count > 0 ? '<span class="msg-thread-unread-dot"></span>' : '';
        const unreadClass = th.unread_count > 0 ? ' unread' : '';
        const date = formatDate(th.last_message_at);
        const leadName = th.lead_fn ? `${th.lead_fn} ${th.lead_ln}` : '';
        const leadTag = leadName ? `<span class="msg-thread-lead-tag"><i class="fas fa-user-tie"></i>${escHtml(leadName)}</span>` : '';
        const preview = escHtml(th.last_preview || '');
        const active  = activeThreadId === th.id ? ' active' : '';
        return `<div class="msg-thread-item${unreadClass}${active}" data-thread-id="${th.id}" data-contact="${escHtml(th.contact_email)}">
            ${unreadDot}
            <div class="msg-thread-row1">
                <span class="msg-thread-sender">${escHtml(th.contact_name || th.contact_email)}</span>
                <span class="msg-thread-date">${date}</span>
            </div>
            <div class="msg-thread-subject">${escHtml(th.subject)}</div>
            <div class="msg-thread-preview">${preview}</div>
            ${leadTag}
        </div>`;
    }

    // ══ CONVERSATION ═════════════════════════════════════════

    async function openThread(threadId) {
        activeThreadId = threadId;
        // Mettre en évidence dans la liste
        $$('.msg-thread-item').forEach(el => el.classList.toggle('active', parseInt(el.dataset.threadId) === threadId));

        // Afficher la vue conversation
        $('msgEmptyState').style.display    = 'none';
        const cv = $('msgConvView');
        cv.style.display = 'flex';

        $('msgMessages').innerHTML = '<div style="flex:1;display:flex;align-items:center;justify-content:center"><i class="fas fa-sync fa-spin" style="color:#94a3b8;font-size:1.2rem"></i></div>';

        const res = await api({ action:'thread_get', thread_id:threadId });
        if (!res.success) { toast(res.error || 'Erreur', 'error'); return; }

        activeThread = res.thread;
        const messages = res.messages || [];
        const lead     = res.lead;

        // En-tête
        const initials = (res.thread.contact_name || res.thread.contact_email).substring(0, 2).toUpperCase();
        $('convAvatar').textContent  = initials;
        $('convName').textContent    = res.thread.contact_name || res.thread.contact_email;
        $('convEmail').textContent   = res.thread.contact_email;

        // Barre CRM
        if (lead) {
            $('convLeadInfo').innerHTML = `<strong>${escHtml(lead.firstname)} ${escHtml(lead.lastname)}</strong> — Pipeline : <em>${escHtml(lead.status)}</em>`;
            $('btnViewLead').style.display = 'flex';
            $('btnViewLead').onclick = () => { window.location.href = '?page=crm'; };
        } else {
            $('convLeadInfo').textContent = 'Non lié à un lead';
            $('btnViewLead').style.display = 'none';
        }

        // Bouton fermer/rouvrir
        $('btnCloseThread').title = res.thread.status === 'open' ? 'Fermer' : 'Rouvrir';
        $('btnCloseThread').querySelector('i').className = res.thread.status === 'open' ? 'fas fa-check' : 'fas fa-undo';

        // Messages
        renderMessages(messages);

        // Pré-remplir composer
        $('msgEditor').innerHTML = '';
        $('msgEditor').focus();

        // Màj liste (retirer badge unread)
        const itemEl = document.querySelector(`.msg-thread-item[data-thread-id="${threadId}"]`);
        if (itemEl) {
            itemEl.querySelector('.msg-thread-unread-dot')?.remove();
            itemEl.classList.remove('unread');
        }
    }

    function renderMessages(messages) {
        const container = $('msgMessages');
        if (!messages.length) {
            container.innerHTML = '<div class="msg-thread-empty"><i class="fas fa-comment-slash"></i><p>Aucun message</p></div>';
            return;
        }

        let lastDate = '';
        let html = '';
        messages.forEach(m => {
            const d    = m.sent_at ? m.sent_at.slice(0,10) : '';
            if (d !== lastDate) {
                html += `<div class="msg-date-sep"><span>${formatDateFull(m.sent_at)}</span></div>`;
                lastDate = d;
            }
            const dir  = m.direction === 'out' ? 'out' : 'in';
            const name = dir === 'out' ? (activeThread?.account_name || 'Vous') : (m.from_name || m.from_email);
            const time = m.sent_at ? m.sent_at.slice(11,16) : '';
            const aiBadge = m.is_ai ? '<span class="msg-ai-badge"><i class="fas fa-robot"></i> IA</span>' : '';
            const body = m.body_html ? sanitizeHtml(m.body_html) : escHtml(m.body_text || '').replace(/\n/g, '<br>');

            html += `<div class="msg-msg ${dir}">
                <div class="msg-msg-meta">
                    <strong>${escHtml(name)}</strong> · ${time}
                    ${aiBadge}
                </div>
                <div class="msg-bubble">${body}</div>
            </div>`;
        });
        container.innerHTML = html;
        container.scrollTop = container.scrollHeight;
    }

    function sanitizeHtml(html) {
        // Retirer scripts, styles inline dangereux, conserver structure email
        return html
            .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '')
            .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '')
            .replace(/on\w+="[^"]*"/gi, '')
            .replace(/javascript:/gi, '');
    }

    // ══ ENVOI ════════════════════════════════════════════════

    async function sendReply() {
        if (!activeThread || !activeAccountId) return;
        const body = $('msgEditor').innerHTML.trim();
        if (!body || body === '<br>') { toast('Rédigez un message', 'warning'); return; }

        const btn = $('btnSend');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const res = await api({
            action:     'mail_send',
            account_id: activeAccountId,
            thread_id:  activeThreadId,
            to_email:   activeThread.contact_email,
            subject:    'Re: ' + activeThread.subject,
            body_html:  body,
        });

        if (res.success) {
            toast('Message envoyé ✓');
            $('msgEditor').innerHTML = '';
            // Ajouter le message dans le fil immédiatement
            const fakeMsg = {
                direction: 'out', from_name: activeThread.account_name,
                from_email: activeThread.account_email, body_html: body,
                sent_at: new Date().toISOString().replace('T',' ').slice(0,19),
                is_ai: 0,
            };
            const container = $('msgMessages');
            const tmpDiv = document.createElement('div');
            tmpDiv.innerHTML = `<div class="msg-msg out">
                <div class="msg-msg-meta"><strong>Vous</strong> · ${fakeMsg.sent_at.slice(11,16)}</div>
                <div class="msg-bubble">${body}</div>
            </div>`;
            container.appendChild(tmpDiv.firstElementChild);
            container.scrollTop = container.scrollHeight;
        } else {
            toast(res.error || 'Erreur envoi', 'error');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer';
    }

    async function sendNewMessage() {
        const accountId = parseInt($('composeAccount').value);
        const to        = $('composeTo').value.trim();
        const subject   = $('composeSubject').value.trim();
        const body      = $('composeEditor').innerHTML.trim();
        if (!to || !subject || !body) { toast('Tous les champs sont requis', 'warning'); return; }

        const btn = $('btnComposeSend');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const res = await api({ action:'mail_send', account_id:accountId, thread_id:0, to_email:to, subject, body_html:body });
        if (res.success) {
            toast('Message envoyé ✓');
            closeModal('modalCompose');
            if (accountId === activeAccountId) loadThreads();
        } else {
            toast(res.error || 'Erreur envoi', 'error');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer';
    }

    // ══ IA ═══════════════════════════════════════════════════

    async function aiSuggest() {
        if (!activeThreadId || !activeAccountId) return;
        const btn      = $('btnAiSuggest');
        const promptId = $('aiPromptSel').value;
        btn.disabled   = true;
        btn.innerHTML  = '<i class="fas fa-spinner fa-spin"></i> Génération…';

        const res = await api({ action:'ai_suggest', account_id:activeAccountId, thread_id:activeThreadId, prompt_id:promptId });
        if (res.success) {
            $('msgEditor').innerHTML = res.suggestion.replace(/\n/g, '<br>');
            toast('Suggestion IA prête ✓', 'info');
            $('msgEditor').focus();
        } else {
            toast(res.error || 'Erreur IA', 'error');
        }
        btn.disabled  = false;
        btn.innerHTML = '<i class="fas fa-robot"></i> Réponse IA';
    }

    // ══ COMPTE SMTP/IMAP ═════════════════════════════════════

    function openAccountModal(acc = null) {
        $('accountId').value    = acc?.id || '';
        $('accFromName').value  = acc?.from_name   || '';
        $('accLabel').value     = acc?.label       || '';
        $('accEmail').value     = acc?.email       || '';
        $('accSmtpHost').value  = acc?.smtp_host   || '';
        $('accSmtpPort').value  = acc?.smtp_port   || 587;
        $('accSmtpUser').value  = acc?.smtp_user   || '';
        $('accSmtpPass').value  = '';
        $('accSmtpSecure').value= acc?.smtp_secure || 'tls';
        $('accImapHost').value  = acc?.imap_host   || '';
        $('accImapPort').value  = acc?.imap_port   || 993;
        $('accImapUser').value  = acc?.imap_user   || '';
        $('accImapPass').value  = '';
        $('accImapSecure').value= acc?.imap_secure || 'ssl';
        $('accImapFolder').value= acc?.imap_folder || 'INBOX';
        $('modalAccountTitle').textContent = acc ? 'Modifier le compte' : 'Configurer un compte email';
        $('accTestResult').style.display = 'none';
        openModal('modalAccount');
        setTimeout(() => $('accFromName')?.focus(), 80);
    }

    function showAccErr(msg) {
        const ban = $('accErrBanner');
        if (!ban) return;
        $('accErrMsg').textContent = msg;
        ban.style.display = 'flex';
        // Scroll en haut du modal body
        ban.closest('.msg-modal-body')?.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function hideAccErr() {
        const ban = $('accErrBanner');
        if (ban) ban.style.display = 'none';
    }

    async function saveAccount() {
        hideAccErr();
        const btn = $('btnSaveAccount');

        // ── Validation côté client avant envoi ────────────────
        const smtpUser = $('accSmtpUser').value.trim();
        const smtpHost = $('accSmtpHost').value.trim();
        const smtpPass = $('accSmtpPass').value;
        const accountId = $('accountId').value;

        // Auto-dériver l'email depuis smtp_user si vide
        if (!$('accEmail').value.trim() && smtpUser) {
            $('accEmail').value = smtpUser;
        }
        // Auto-dériver le libellé depuis l'email si vide
        if (!$('accLabel').value.trim() && $('accEmail').value.trim()) {
            $('accLabel').value = $('accEmail').value.trim();
        }
        // Auto-dériver imap_user depuis smtp_user si vide
        if (!$('accImapUser').value.trim() && smtpUser) {
            $('accImapUser').value = smtpUser;
        }

        const email = $('accEmail').value.trim();
        if (!email) { showAccErr('L'adresse email expéditeur est requise.'); return; }
        if (!smtpHost) { showAccErr('L'hôte SMTP est requis.'); return; }
        if (!smtpUser) { showAccErr('L'utilisateur SMTP est requis.'); return; }
        if (!accountId && !smtpPass) { showAccErr('Le mot de passe SMTP est requis pour un nouveau compte.'); return; }

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement…';

        const data = {
            action:'account_save', id: accountId,
            from_name:$('accFromName').value, label:$('accLabel').value,
            email: email, smtp_host: smtpHost,
            smtp_port:$('accSmtpPort').value, smtp_user: smtpUser,
            smtp_pass: smtpPass, smtp_secure:$('accSmtpSecure').value,
            imap_host:$('accImapHost').value, imap_port:$('accImapPort').value,
            imap_user:$('accImapUser').value, imap_pass:$('accImapPass').value,
            imap_secure:$('accImapSecure').value, imap_folder:$('accImapFolder').value,
        };

        try {
            const res = await api(data);
            if (res.success) {
                toast('Compte enregistré ✓');
                closeModal('modalAccount');
                setTimeout(() => window.location.reload(), 800);
            } else {
                const errMsg = res.error || 'Erreur inconnue lors de l'enregistrement.';
                showAccErr(errMsg);
                toast(errMsg, 'error');
            }
        } catch(e) {
            const errMsg = 'Erreur réseau : ' + e.message;
            showAccErr(errMsg);
            toast(errMsg, 'error');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
    }

    async function testAccount() {
        const id = $('accountId').value;
        if (!id) { toast('Sauvegardez d\'abord le compte', 'warning'); return; }
        const btn = $('btnTestAccount');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Test…';
        const res = await api({ action:'account_test', id });
        const div = $('accTestResult');
        div.style.display = 'block';
        if (res.success) {
            const smtpOk = res.smtp ? '✓ ' + res.smtp_msg : '✕ ' + res.smtp_msg;
            const imapOk = res.imap ? '✓ ' + res.imap_msg : '⚠ ' + res.imap_msg;
            div.className = `msg-test-result ${res.smtp ? 'ok' : 'err'}`;
            div.innerHTML = `SMTP : ${escHtml(smtpOk)}<br>IMAP : ${escHtml(imapOk)}`;
        } else {
            div.className = 'msg-test-result err';
            div.textContent = res.error;
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plug"></i> Tester la connexion';
    }

    // ══ SYNC IMAP ════════════════════════════════════════════

    async function syncImap() {
        if (!activeAccountId) return;
        const btn = $('btnSyncThread');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        toast('Synchronisation IMAP…', 'info');
        const res = await api({ action:'mail_sync', account_id:activeAccountId });
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-sync"></i>';
        if (res.success) {
            toast(`Sync OK — ${res.imported} nouveau(x) message(s)`);
            loadThreads();
        } else {
            toast(res.error || 'Erreur sync', 'error');
        }
    }

    // ══ LIER LEAD ════════════════════════════════════════════

    async function confirmLinkLead() {
        const leadId = $('linkLeadSel').value;
        const res = await api({ action:'thread_link', thread_id:activeThreadId, lead_id:leadId || 0 });
        if (res.success) {
            toast(leadId ? 'Lead lié ✓' : 'Lead délié');
            closeModal('modalLinkLead');
            openThread(activeThreadId); // recharger
        } else {
            toast(res.error || 'Erreur', 'error');
        }
    }

    // ══ SUPPRIMER THREAD ══════════════════════════════════════

    async function deleteThread() {
        const res = await api({ action:'mail_delete', thread_id:activeThreadId });
        closeModal('modalConfirmDel');
        if (res.success) {
            toast('Conversation supprimée');
            activeThreadId = null;
            activeThread   = null;
            $('msgConvView').style.display = 'none';
            $('msgEmptyState').style.display = 'flex';
            loadThreads();
        } else {
            toast(res.error || 'Erreur', 'error');
        }
    }

    // ══ FORMATAGE ════════════════════════════════════════════

    function formatDate(dt) {
        if (!dt) return '';
        const d = new Date(dt.replace(' ', 'T'));
        const now = new Date();
        const diff = now - d;
        if (diff < 86400000 && d.getDate() === now.getDate()) {
            return d.toLocaleTimeString('fr-FR', { hour:'2-digit', minute:'2-digit' });
        }
        if (diff < 604800000) {
            return d.toLocaleDateString('fr-FR', { weekday:'short' });
        }
        return d.toLocaleDateString('fr-FR', { day:'2-digit', month:'short' });
    }

    function formatDateFull(dt) {
        if (!dt) return '';
        return new Date(dt.replace(' ', 'T')).toLocaleDateString('fr-FR', { weekday:'long', day:'numeric', month:'long', year:'numeric' });
    }

    function escHtml(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ══ ÉDITEUR RICH TEXT ════════════════════════════════════

    function initEditor(editorId) {
        const editor = $(editorId);
        if (!editor) return;
        editor.addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                document.execCommand('insertParagraph');
            }
        });
    }

    $$('.msg-fmt-btn[data-cmd]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            const cmd = btn.dataset.cmd;
            if (cmd === 'createLink') {
                const url = prompt('URL du lien :');
                if (url) document.execCommand(cmd, false, url);
            } else {
                document.execCommand(cmd, false, null);
            }
        });
    });

    // ══ INIT ═════════════════════════════════════════════════

    function init() {
        // Sidebar : changer de compte
        $$('.msg-sidebar-item[data-account-id]').forEach(item => {
            item.addEventListener('click', () => {
                $$('.msg-sidebar-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                activeAccountId = parseInt(item.dataset.accountId);
                loadThreads();
            });
        });

        // Sidebar : vues
        $$('.msg-sidebar-view[data-view-filter]').forEach(v => {
            v.addEventListener('click', () => {
                $$('.msg-sidebar-view').forEach(x => x.classList.remove('active'));
                v.classList.add('active');
                viewFilter = v.dataset.viewFilter;
                loadThreads();
            });
        });

        // Liste threads : clic sur thread
        $('msgThreadList').addEventListener('click', e => {
            const item = e.target.closest('.msg-thread-item[data-thread-id]');
            if (item) openThread(parseInt(item.dataset.threadId));
        });

        // Recherche
        $('msgSearchInput')?.addEventListener('input', e => {
            clearTimeout(searchTimer);
            searchQ = e.target.value;
            searchTimer = setTimeout(loadThreads, 300);
        });

        // Boutons toolbar conversation
        $('btnSyncThread')?.addEventListener('click', syncImap);
        $('btnCloseThread')?.addEventListener('click', async () => {
            if (!activeThreadId) return;
            const newStatus = activeThread?.status === 'open' ? 'closed' : 'open';
            const res = await api({ action:'thread_close', thread_id:activeThreadId, status:newStatus });
            if (res.success) { toast(`Thread ${newStatus === 'open' ? 'rouvert' : 'fermé'} ✓`); loadThreads(); }
        });
        $('btnDeleteThread')?.addEventListener('click', () => {
            $('confirmDelMsg').textContent = 'Cette conversation sera définitivement supprimée.';
            openModal('modalConfirmDel');
        });
        $('btnConfirmDel')?.addEventListener('click', deleteThread);

        // Lier lead
        $('btnLinkLead')?.addEventListener('click', () => openModal('modalLinkLead'));
        $('btnConfirmLinkLead')?.addEventListener('click', confirmLinkLead);

        // Composer
        $('btnSend')?.addEventListener('click', sendReply);
        $('msgEditor')?.addEventListener('keydown', e => {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) sendReply();
        });

        // IA
        $('btnAiSuggest')?.addEventListener('click', aiSuggest);

        // Nouveau message
        const openCompose = () => openModal('modalCompose');
        $('btnComposeNew')?.addEventListener('click', openCompose);
        $('btnComposeSend')?.addEventListener('click', sendNewMessage);

        // Ajout compte
        const openAcc = () => openAccountModal();
        $('btnAddAccount')?.addEventListener('click', openAcc);
        $('btnAddAccountMain')?.addEventListener('click', openAcc);
        $('btnSaveAccount')?.addEventListener('click', saveAccount);
        $('btnTestAccount')?.addEventListener('click', testAccount);

        // Editors
        initEditor('msgEditor');
        initEditor('composeEditor');

        // Chargement initial
        if (activeAccountId) loadThreads();
    }

    return { init };
})();

MSG.init();
</script>