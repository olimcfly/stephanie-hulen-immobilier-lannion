<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE MAINTENANCE — index.php v3.1
 *  /admin/modules/system/maintenance/index.php
 *  Toutes les classes CSS déclarées localement (set-section,
 *  set-field, set-input, set-btn, flash, api-badge, toggle-row)
 * ══════════════════════════════════════════════════════════════
 */
if (!defined('ADMIN_ROUTER')) { header('Location: /admin/dashboard.php?page=maintenance'); exit; }

if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;
try {
    if (!isset($pdo)) $pdo = Database::getInstance();
} catch (Exception $e) {
    echo '<div style="padding:20px;color:#dc2626;font-family:monospace">DB : '.htmlspecialchars($e->getMessage()).'</div>';
    return;
}

// ─── Table + données ──────────────────────────────────────────
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `maintenance` (
        `id`          INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
        `is_active`   TINYINT(1)  NOT NULL DEFAULT 0,
        `message`     TEXT,
        `allowed_ips` TEXT,
        `end_date`    DATETIME    DEFAULT NULL,
        `updated_at`  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $row = $pdo->query("SELECT * FROM maintenance WHERE id=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        $pdo->exec("INSERT INTO maintenance (id,is_active,message,allowed_ips) VALUES (1,0,'','127.0.0.1')");
        $row = ['is_active'=>0,'message'=>'','allowed_ips'=>'127.0.0.1','end_date'=>null];
    }
} catch (Exception $e) {
    $row = ['is_active'=>0,'message'=>'','allowed_ips'=>'127.0.0.1','end_date'=>null];
}

$isActive   = (int)($row['is_active'] ?? 0);
$message    = $row['message']     ?? '';
$allowedIps = $row['allowed_ips'] ?? '127.0.0.1';
$endDate    = $row['end_date']    ?? '';

// ─── IP visiteur ──────────────────────────────────────────────
$visitorIp = $_SERVER['HTTP_CF_CONNECTING_IP']
          ?? $_SERVER['HTTP_X_FORWARDED_FOR']
          ?? $_SERVER['REMOTE_ADDR']
          ?? '';
if (str_contains($visitorIp, ',')) $visitorIp = trim(explode(',', $visitorIp)[0]);
$ipList      = array_filter(array_map('trim', explode(',', $allowedIps)));
$ipIsAllowed = in_array($visitorIp, $ipList, true);

$apiUrl = '/admin/api/system/maintenance/save.php';
?>

<style>
/* ═══════════════════════════════════════════════════════════
   MAINTENANCE v3.1 — classes auto-contenues
   Reprend EXACTEMENT le langage visuel de settings/index.php
   ═══════════════════════════════════════════════════════════ */

/* ── Wrapper ────────────────────────────────────────────── */
.mnt-wrap { max-width: 900px; }

/* ── Section — COPIÉ de settings/index.php ──────────────── */
.mnt-section {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}
.mnt-section-hd {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
    background: var(--surface-2);
}
.mnt-section-hd h3 {
    font-size: 13px; font-weight: 700;
    font-family: var(--font-display, inherit);
}
.mnt-section-hd .hd-icon {
    color: var(--accent); font-size: 13px;
    width: 18px; text-align: center; flex-shrink: 0;
}
.mnt-section-hd .hd-meta {
    font-size: 11px; color: var(--text-3); margin-left: auto;
    display: flex; align-items: center; gap: 8px;
}
.mnt-section-body { padding: 20px; }

/* ── Champs — COPIÉ de settings/index.php ───────────────── */
.mnt-field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 14px; }
.mnt-field:last-child { margin-bottom: 0; }
.mnt-field label { font-size: 11px; font-weight: 700; color: var(--text-2); letter-spacing: .01em; }
.mnt-field small  { font-size: 10px; color: var(--text-3); }
.mnt-input {
    padding: 9px 12px;
    border: 1px solid var(--border); border-radius: var(--radius);
    font-size: 12px; font-family: var(--font);
    background: var(--surface); color: var(--text);
    transition: border-color .15s; width: 100%; box-sizing: border-box;
}
.mnt-input:focus {
    outline: none; border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(79,70,229,.08);
}
.mnt-input.mono { font-family: var(--mono); font-size: 11px; }
.mnt-textarea { resize: vertical; min-height: 72px; line-height: 1.6; }

/* ── Boutons — COPIÉ de settings/index.php ──────────────── */
.mnt-btn {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px; border-radius: var(--radius);
    font-family: var(--font); font-size: 12px; font-weight: 600;
    cursor: pointer; border: 1px solid var(--border);
    transition: all .15s; text-decoration: none; background: none;
}
.mnt-btn-p { background: var(--accent); color: #fff; border-color: var(--accent); }
.mnt-btn-p:hover { background: #4338ca; border-color: #4338ca; }
.mnt-btn-s { background: var(--surface); color: var(--text); }
.mnt-btn-s:hover { background: var(--surface-2); }

/* ── Actions footer — COPIÉ de settings/index.php ───────── */
.mnt-actions {
    display: flex; gap: 8px; align-items: center;
    padding-top: 14px;
    border-top: 1px solid var(--border);
    margin-top: 14px;
}

/* ── Flash banner — COPIÉ de settings/index.php ─────────── */
.mnt-flash {
    padding: 11px 16px; border-radius: var(--radius);
    font-size: 12px; font-weight: 600;
    margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
    border: 1px solid;
}
.mnt-flash.ok  { background: var(--green-bg); color: var(--green); border-color: rgba(5,150,105,.18); }
.mnt-flash.err { background: var(--red-bg);   color: var(--red);   border-color: rgba(220,38,38,.18); }

/* ── Badges statut — COPIÉ de settings (api-badge) ──────── */
.mnt-badge {
    display: inline-flex; align-items: center;
    font-size: 9px; font-weight: 800;
    padding: 2px 8px; border-radius: 4px;
    letter-spacing: .3px; text-transform: uppercase;
}
.mnt-badge.ok   { background: var(--green-bg); color: var(--green); }
.mnt-badge.warn { background: var(--amber-bg); color: var(--amber); }
.mnt-badge.err  { background: var(--red-bg);   color: var(--red);   }
.mnt-badge.idle { background: var(--surface-3,#f1f5f9); color: var(--text-3); }

/* ── Stat cards ─────────────────────────────────────────── */
.mnt-stats {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px; margin-bottom: 20px;
}
@media(max-width:860px) { .mnt-stats { grid-template-columns: repeat(3,1fr); } }
@media(max-width:560px) { .mnt-stats { grid-template-columns: 1fr 1fr; } }

.mnt-stat {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); padding: 14px 16px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: var(--shadow-sm);
}
.mnt-stat-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
}
.mnt-stat-val   { font-size: 15px; font-weight: 800; line-height: 1.2; }
.mnt-stat-label { font-size: 10px; color: var(--text-3); margin-top: 2px; }

/* ── Bannière mode — comme .flash mais centrée ───────────── */
.mnt-banner {
    padding: 11px 16px; border-radius: var(--radius);
    font-size: 12px; font-weight: 700;
    margin-bottom: 16px;
    display: flex; align-items: center; gap: 9px;
    border: 1px solid;
}
.mnt-banner.online  { background: var(--green-bg); color: var(--green); border-color: rgba(5,150,105,.2); }
.mnt-banner.offline { background: var(--red-bg);   color: var(--red);   border-color: rgba(220,38,38,.2); }

/* ── Toggle grid ──────────────────────────────────────────── */
.mnt-toggle-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 14px;
}
@media(max-width:540px) { .mnt-toggle-grid { grid-template-columns: 1fr; } }

.mnt-toggle-btn {
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    gap: 10px; padding: 22px 16px;
    border-radius: var(--radius-lg); border: 2px solid;
    cursor: pointer; font-family: var(--font);
    font-weight: 700; font-size: 13px;
    transition: all .16s; background: none;
}
.mnt-toggle-btn i { font-size: 24px; }
.mnt-toggle-btn.is-on   { background: var(--red-bg);   border-color: var(--red);   color: var(--red); }
.mnt-toggle-btn.is-off  { background: var(--green-bg); border-color: var(--green); color: var(--green); }
.mnt-toggle-btn.is-idle {
    background: var(--surface-2); border-color: var(--border); color: var(--text-3);
}
.mnt-toggle-btn.is-idle:hover {
    border-color: var(--accent); color: var(--accent); background: var(--accent-bg);
}

/* ── Toggle row (Ajouter mon IP) ─────────────────────────── */
.mnt-toggle-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 0; border-top: 1px solid var(--border); margin-top: 10px;
    gap: 12px; flex-wrap: wrap;
}
.mnt-toggle-row label { font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px; }
.mnt-toggle-row small  { font-size: 10px; color: var(--text-3); }

/* ── Code IP ─────────────────────────────────────────────── */
.mnt-code {
    font-family: var(--mono); font-size: 10.5px;
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: 5px; padding: 2px 7px; color: var(--text-2);
}
</style>

<div class="mnt-wrap">

<!-- ── Page header ──────────────────────────────────────── -->
<div class="page-hd">
    <div>
        <h1>Maintenance</h1>
        <div class="page-hd-sub">Contrôle d'accès visiteurs · Whitelist IP · Message de maintenance</div>
    </div>
    <a href="?page=system" class="mnt-btn mnt-btn-s">
        <i class="fas fa-arrow-left"></i> Système
    </a>
</div>

<!-- ── Stat cards ───────────────────────────────────────── -->
<div class="mnt-stats anim">

    <div class="mnt-stat">
        <div class="mnt-stat-icon" id="sc-icon-mode"
             style="background:<?= $isActive?'var(--red-bg)':'var(--green-bg)' ?>;color:<?= $isActive?'var(--red)':'var(--green)' ?>">
            <i class="fas fa-power-off"></i>
        </div>
        <div>
            <div class="mnt-stat-val" id="sc-mode"
                 style="color:<?= $isActive?'var(--red)':'var(--green)' ?>">
                <?= $isActive ? 'Maintenance' : 'En ligne' ?>
            </div>
            <div class="mnt-stat-label">Statut site</div>
        </div>
    </div>

    <div class="mnt-stat">
        <div class="mnt-stat-icon" style="background:var(--accent-bg);color:var(--accent)">
            <i class="fas fa-eye<?= $isActive?'-slash':'' ?>" id="sc-vis-icon"></i>
        </div>
        <div>
            <div class="mnt-stat-val" id="sc-vis"><?= $isActive ? 'Bloqués' : 'Libres' ?></div>
            <div class="mnt-stat-label">Visiteurs</div>
        </div>
    </div>

    <div class="mnt-stat">
        <div class="mnt-stat-icon" style="background:var(--surface-2);color:var(--text-3)">
            <i class="fas fa-globe"></i>
        </div>
        <div>
            <div class="mnt-stat-val" style="font-size:11px;font-family:var(--mono)"><?= htmlspecialchars($visitorIp) ?></div>
            <div class="mnt-stat-label">Votre IP</div>
        </div>
    </div>

    <div class="mnt-stat">
        <div class="mnt-stat-icon"
             style="background:<?= $ipIsAllowed?'var(--green-bg)':'var(--amber-bg)' ?>;color:<?= $ipIsAllowed?'var(--green)':'var(--amber)' ?>">
            <i class="fas fa-shield-halved"></i>
        </div>
        <div>
            <div class="mnt-stat-val" id="sc-ip-status" style="font-size:12px">
                <?= $ipIsAllowed ? 'Autorisée' : 'Non listée' ?>
            </div>
            <div class="mnt-stat-label">IP autorisée</div>
        </div>
    </div>

    <div class="mnt-stat">
        <div class="mnt-stat-icon" style="background:var(--surface-2);color:var(--text-3)">
            <i class="fas fa-list-check"></i>
        </div>
        <div>
            <div class="mnt-stat-val"><?= count($ipList) ?></div>
            <div class="mnt-stat-label">IP(s) whitelistée(s)</div>
        </div>
    </div>

</div>

<!-- ── Bannière statut ───────────────────────────────────── -->
<div id="mnt-banner" class="mnt-banner <?= $isActive ? 'offline' : 'online' ?> anim">
    <i class="fas <?= $isActive ? 'fa-wrench' : 'fa-circle-check' ?>" id="mnt-banner-icon"></i>
    <span id="mnt-banner-txt">
        <?= $isActive
            ? 'MODE MAINTENANCE ACTIF — Les visiteurs voient la page de maintenance'
            : 'SITE EN LIGNE — Accessible normalement à tous les visiteurs' ?>
    </span>
</div>

<!-- ═══════════════════════════════════════════════════════
     SECTION 1 — Contrôle
═══════════════════════════════════════════════════════ -->
<div class="mnt-section anim">
    <div class="mnt-section-hd">
        <i class="fas fa-power-off hd-icon"></i>
        <h3>Contrôle de la maintenance</h3>
        <span class="hd-meta">Basculez l'état du site</span>
    </div>
    <div class="mnt-section-body">
        <div class="mnt-toggle-grid">

            <button onclick="maintToggle(1)" id="btn-on"
                    class="mnt-toggle-btn <?= $isActive ? 'is-on' : 'is-idle' ?>">
                <i class="fas fa-wrench"></i>
                <span>Activer la maintenance</span>
                <span class="mnt-badge <?= $isActive ? 'err' : 'idle' ?>" id="badge-on">
                    <?= $isActive ? 'ACTIF' : 'INACTIF' ?>
                </span>
            </button>

            <button onclick="maintToggle(0)" id="btn-off"
                    class="mnt-toggle-btn <?= !$isActive ? 'is-off' : 'is-idle' ?>">
                <i class="fas fa-globe"></i>
                <span>Remettre en ligne</span>
                <span class="mnt-badge <?= !$isActive ? 'ok' : 'idle' ?>" id="badge-off">
                    <?= !$isActive ? 'EN LIGNE' : 'INACTIF' ?>
                </span>
            </button>

        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     SECTION 2 — Message
═══════════════════════════════════════════════════════ -->
<div class="mnt-section anim">
    <div class="mnt-section-hd">
        <i class="fas fa-pen-to-square hd-icon"></i>
        <h3>Message affiché aux visiteurs</h3>
        <span class="hd-meta">Texte de la page de maintenance</span>
    </div>
    <div class="mnt-section-body">

        <div class="mnt-field">
            <label>Message</label>
            <textarea id="maint-message"
                      class="mnt-input mnt-textarea" rows="3"
                      placeholder="Ex : Nous effectuons une mise à jour. Retour prévu demain à 9h."
            ><?= htmlspecialchars($message) ?></textarea>
            <small>Soyez clair et professionnel. Indiquez si possible la date de retour.</small>
        </div>

        <div class="mnt-field">
            <label>Date de fin prévue <span style="font-weight:400;color:var(--text-3)">(optionnel)</span></label>
            <input type="datetime-local" id="maint-enddate"
                   class="mnt-input"
                   value="<?= $endDate ? date('Y-m-d\TH:i', strtotime($endDate)) : '' ?>">
            <small>Affiché aux visiteurs si renseigné.</small>
        </div>

        <div class="mnt-actions">
            <button onclick="maintSaveMessage()" class="mnt-btn mnt-btn-p" id="btn-save-msg">
                <i class="fas fa-save"></i> Sauvegarder le message
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════
     SECTION 3 — Whitelist IP
═══════════════════════════════════════════════════════ -->
<div class="mnt-section anim">
    <div class="mnt-section-hd">
        <i class="fas fa-shield-halved hd-icon"></i>
        <h3>IPs autorisées pendant la maintenance</h3>
        <div class="hd-meta">
            <span>Votre IP :</span>
            <code class="mnt-code"><?= htmlspecialchars($visitorIp) ?></code>
            <span class="mnt-badge <?= $ipIsAllowed ? 'ok' : 'warn' ?>" id="ip-badge">
                <?= $ipIsAllowed ? 'Autorisée' : 'Non listée' ?>
            </span>
        </div>
    </div>
    <div class="mnt-section-body">

        <div class="mnt-field">
            <label>Adresses IP <span style="font-weight:400;color:var(--text-3)">(séparées par des virgules)</span></label>
            <textarea id="maint-whitelist"
                      class="mnt-input mnt-textarea mono" rows="2"
                      placeholder="Ex : 92.184.103.245, 1.2.3.4"
            ><?= htmlspecialchars($allowedIps) ?></textarea>
            <small>Ces IPs accèdent au site normalement même pendant la maintenance.</small>
        </div>

        <!-- Ligne ajouter mon IP — comme toggle-row de settings ── -->
        <div class="mnt-toggle-row">
            <div>
                <label>Ajouter mon IP automatiquement</label>
                <small>Votre IP actuelle : <code class="mnt-code"><?= htmlspecialchars($visitorIp) ?></code></small>
            </div>
            <button onclick="maintAddMyIp()" class="mnt-btn mnt-btn-s" style="font-size:11px;padding:7px 14px">
                <i class="fas fa-plus"></i> Ajouter mon IP
            </button>
        </div>

        <div class="mnt-actions">
            <button onclick="maintSaveWhitelist()" class="mnt-btn mnt-btn-p" id="btn-save-ip">
                <i class="fas fa-save"></i> Sauvegarder la whitelist
            </button>
        </div>
    </div>
</div>

</div><!-- /mnt-wrap -->

<script>
(function () {
    const API   = <?= json_encode($apiUrl) ?>;
    const MY_IP = <?= json_encode($visitorIp) ?>;

    /* ── Toast fixe bas droite — reprend .mnt-flash ──────── */
    function flash(msg, type = 'ok') {
        if (window.showAdminFlash) { window.showAdminFlash(msg, type === 'ok' ? 'success' : 'error'); return; }
        const old = document.getElementById('_mnt_toast');
        if (old) old.remove();
        const el = document.createElement('div');
        el.id = '_mnt_toast';
        el.className = 'mnt-flash ' + type;
        el.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;min-width:260px;max-width:380px;box-shadow:0 4px 16px rgba(0,0,0,.12)';
        el.innerHTML = '<i class="fas fa-' + (type==='ok'?'check-circle':'exclamation-circle') + '"></i> ' + msg;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    }

    /* ── API call ─────────────────────────────────────────── */
    async function api(action, data = {}) {
        const fd = new FormData();
        fd.append('action', action);
        Object.entries(data).forEach(([k,v]) => fd.append(k,v));
        try {
            const r = await fetch(API, { method: 'POST', body: fd });
            const t = await r.text();
            try { return JSON.parse(t); } catch { return { success:false, message:'Erreur serveur' }; }
        } catch { return { success:false, message:'Réseau indisponible' }; }
    }

    /* ── Mise à jour UI après toggle ──────────────────────── */
    function updateUI(active) {
        // Boutons toggle
        const btnOn  = document.getElementById('btn-on');
        const btnOff = document.getElementById('btn-off');
        btnOn.className  = 'mnt-toggle-btn ' + (active ? 'is-on'  : 'is-idle');
        btnOff.className = 'mnt-toggle-btn ' + (active ? 'is-idle': 'is-off');

        // Badges boutons
        const bdgOn  = document.getElementById('badge-on');
        const bdgOff = document.getElementById('badge-off');
        bdgOn.className  = 'mnt-badge ' + (active ? 'err' : 'idle');
        bdgOn.textContent  = active ? 'ACTIF' : 'INACTIF';
        bdgOff.className = 'mnt-badge ' + (active ? 'idle' : 'ok');
        bdgOff.textContent = active ? 'INACTIF' : 'EN LIGNE';

        // Bannière
        const banner = document.getElementById('mnt-banner');
        banner.className = 'mnt-banner ' + (active ? 'offline' : 'online');
        document.getElementById('mnt-banner-icon').className = 'fas ' + (active ? 'fa-wrench' : 'fa-circle-check');
        document.getElementById('mnt-banner-txt').textContent = active
            ? 'MODE MAINTENANCE ACTIF — Les visiteurs voient la page de maintenance'
            : 'SITE EN LIGNE — Accessible normalement à tous les visiteurs';

        // Stat cards
        const scMode = document.getElementById('sc-mode');
        scMode.textContent = active ? 'Maintenance' : 'En ligne';
        scMode.style.color = active ? 'var(--red)' : 'var(--green)';
        const scIcon = document.getElementById('sc-icon-mode');
        scIcon.style.background = active ? 'var(--red-bg)'   : 'var(--green-bg)';
        scIcon.style.color      = active ? 'var(--red)'      : 'var(--green)';
        document.getElementById('sc-vis').textContent    = active ? 'Bloqués' : 'Libres';
        document.getElementById('sc-vis-icon').className = 'fas fa-eye' + (active ? '-slash' : '');
    }

    /* ── Toggle ───────────────────────────────────────────── */
    window.maintToggle = async function (val) {
        const btn = document.getElementById(val ? 'btn-on' : 'btn-off');
        const saved = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
        btn.disabled  = true;
        const res = await api('toggle', { is_active: val });
        btn.innerHTML = saved;
        btn.disabled  = false;
        if (res.success) {
            updateUI(val === 1);
            flash(val === 1
                ? 'Maintenance activée — les visiteurs voient la page de maintenance'
                : 'Site remis en ligne !');
        } else {
            flash(res.message || 'Erreur lors du changement de statut', 'err');
        }
    };

    /* ── Sauvegarder message ──────────────────────────────── */
    window.maintSaveMessage = async function () {
        const btn = document.getElementById('btn-save-msg');
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sauvegarde…';
        const res = await api('save_message', {
            message:  document.getElementById('maint-message').value.trim(),
            end_date: document.getElementById('maint-enddate').value,
        });
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Sauvegarder le message';
        flash(res.success ? 'Message sauvegardé !' : (res.message || 'Erreur'), res.success ? 'ok' : 'err');
    };

    /* ── Sauvegarder whitelist ────────────────────────────── */
    window.maintSaveWhitelist = async function () {
        const btn = document.getElementById('btn-save-ip');
        btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Sauvegarde…';
        const ips = document.getElementById('maint-whitelist').value.trim();
        const res = await api('save_whitelist', { allowed_ips: ips });
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Sauvegarder la whitelist';
        if (res.success) {
            const list    = ips.split(',').map(s=>s.trim()).filter(Boolean);
            const allowed = list.includes(MY_IP);
            const ipBadge = document.getElementById('ip-badge');
            ipBadge.className = 'mnt-badge ' + (allowed ? 'ok' : 'warn');
            ipBadge.textContent = allowed ? 'Autorisée' : 'Non listée';
            const scIp = document.getElementById('sc-ip-status');
            if (scIp) scIp.textContent = allowed ? 'Autorisée' : 'Non listée';
            flash('Whitelist sauvegardée !');
        } else {
            flash(res.message || 'Erreur', 'err');
        }
    };

    /* ── Ajouter mon IP ───────────────────────────────────── */
    window.maintAddMyIp = function () {
        const ta   = document.getElementById('maint-whitelist');
        const list = ta.value ? ta.value.split(',').map(s=>s.trim()).filter(Boolean) : [];
        if (list.includes(MY_IP)) { flash('Votre IP est déjà dans la liste'); return; }
        list.push(MY_IP);
        ta.value = list.join(', ');
        flash("IP ajoutée — pensez à sauvegarder la whitelist");
    };

})();
</script>