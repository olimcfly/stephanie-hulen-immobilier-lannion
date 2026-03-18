<?php
/**
 * Module GMB — Séquences Email B2B
 * /admin/modules/gmb/sequences.php
 *
 * Routé par dashboard.php via $gmbMapping :
 *   ?page=gmb-sequences → ce fichier
 */

// ─── Guard : inclusion par le routeur uniquement ───
$isEmbedded = defined('ADMIN_ROUTER');

if (!$isEmbedded) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
    if (file_exists(ROOT_PATH . '/admin/includes/init.php')) {
        require_once ROOT_PATH . '/admin/includes/init.php';
    }
    if (file_exists(ROOT_PATH . '/includes/Database.php')) {
        require_once ROOT_PATH . '/includes/Database.php';
    } elseif (file_exists(ROOT_PATH . '/admin/includes/Database.php')) {
        require_once ROOT_PATH . '/admin/includes/Database.php';
    }
    if (file_exists(ROOT_PATH . '/core/db.php')) {
        require_once ROOT_PATH . '/core/db.php';
    }
}

// ─── Connexion DB ───
if (!isset($pdo) && !isset($db)) {
    if (function_exists('db')) {
        $pdo = db();
    } elseif (class_exists('Database')) {
        $pdo = Database::getInstance();
    }
} elseif (isset($db) && !isset($pdo)) {
    $pdo = $db;
}

// ─── Variables page ───
$page_title = "Séquences Email B2B";
$current_module = "gmb";

// ─── Charger les séquences ───
$sequences = [];
$templates = [];
$stats = ['total' => 0, 'active' => 0, 'paused' => 0, 'total_sent' => 0, 'total_opened' => 0, 'taux_ouverture' => 0];
$db_error = null;

if (isset($pdo)) {
    try {
        // Stats globales
        $stmt = $pdo->query("SELECT COUNT(*) FROM gmb_email_sequences");
        $stats['total'] = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM gmb_email_sequences WHERE is_active = 1");
        $stats['active'] = (int)$stmt->fetchColumn();
        
        $stats['paused'] = $stats['total'] - $stats['active'];
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM gmb_email_logs WHERE status = 'sent'");
        $stats['total_sent'] = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM gmb_email_logs WHERE opened_at IS NOT NULL");
        $stats['total_opened'] = (int)$stmt->fetchColumn();
        
        if ($stats['total_sent'] > 0) {
            $stats['taux_ouverture'] = round(($stats['total_opened'] / $stats['total_sent']) * 100, 1);
        }
        
        // Liste des séquences avec stats
        $stmt = $pdo->query("
            SELECT s.*,
                   (SELECT COUNT(*) FROM gmb_email_sequence_steps WHERE sequence_id = s.id) as nb_steps,
                   (SELECT COUNT(*) FROM gmb_email_logs WHERE sequence_id = s.id AND status = 'sent') as emails_sent,
                   (SELECT COUNT(*) FROM gmb_email_logs WHERE sequence_id = s.id AND opened_at IS NOT NULL) as emails_opened,
                   (SELECT COUNT(DISTINCT contact_id) FROM gmb_email_logs WHERE sequence_id = s.id) as nb_contacts
            FROM gmb_email_sequences s
            ORDER BY s.created_at DESC
        ");
        $sequences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Charger les templates disponibles
        $templateDir = __DIR__ . '/templates/';
        if (is_dir($templateDir)) {
            foreach (glob($templateDir . '*.html') as $tpl) {
                $filename = basename($tpl, '.html');
                $content = file_get_contents($tpl);
                // Extraire le sujet du template (première ligne ou <title>)
                $subject = '';
                if (preg_match('/<title>(.*?)<\/title>/i', $content, $m)) {
                    $subject = $m[1];
                }
                $templates[] = [
                    'file' => $filename,
                    'name' => ucwords(str_replace(['-', '_'], ' ', $filename)),
                    'subject' => $subject,
                    'preview' => mb_substr(strip_tags($content), 0, 120) . '...',
                ];
            }
        }
        
    } catch (PDOException $e) {
        $db_error = $e->getMessage();
    }
}

// ─── Début du contenu ───
if (!$isEmbedded) {
    ob_start();
}
?>

<style>
/* ─── Séquences Styles ─── */
.seq-page { padding: 0; }
.seq-page h1 { font-size: 24px; font-weight: 700; color: #1a1a2e; margin: 0 0 4px; }
.seq-page .subtitle { color: #6b7280; font-size: 14px; margin-bottom: 24px; }

/* Header row */
.seq-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
}
.seq-header-row .btn-create {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #7c3aed;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}
.seq-header-row .btn-create:hover { background: #6d28d9; transform: translateY(-1px); }

/* Stats Mini */
.seq-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 28px;
}
.seq-stat {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px;
    text-align: center;
}
.seq-stat .val { font-size: 26px; font-weight: 700; color: #1a1a2e; }
.seq-stat .lbl { font-size: 12px; color: #6b7280; margin-top: 4px; font-weight: 500; }
.seq-stat.purple .val { color: #7c3aed; }
.seq-stat.green .val  { color: #059669; }
.seq-stat.blue .val   { color: #2563eb; }
.seq-stat.amber .val  { color: #d97706; }

/* Sequence Cards */
.seq-list { display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px; }
.seq-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    transition: all 0.2s;
}
.seq-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    border-color: #d1d5db;
}
.seq-card .seq-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.seq-card .seq-icon.active  { background: #ede9fe; color: #7c3aed; }
.seq-card .seq-icon.paused  { background: #f3f4f6; color: #9ca3af; }
.seq-card .seq-info { flex: 1; min-width: 0; }
.seq-card .seq-name {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 4px;
}
.seq-card .seq-meta {
    font-size: 13px;
    color: #6b7280;
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.seq-card .seq-meta span i { margin-right: 4px; }
.seq-card .seq-stats-mini {
    display: flex;
    gap: 20px;
    flex-shrink: 0;
}
.seq-card .seq-stats-mini .mini-stat {
    text-align: center;
}
.seq-card .seq-stats-mini .mini-val {
    font-size: 18px;
    font-weight: 700;
    color: #374151;
}
.seq-card .seq-stats-mini .mini-lbl {
    font-size: 11px;
    color: #9ca3af;
}
.seq-card .seq-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}
.seq-card .seq-actions button {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: white;
    cursor: pointer;
    font-size: 14px;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}
.seq-card .seq-actions button:hover { background: #f3f4f6; color: #1a1a2e; }
.seq-card .seq-actions button.danger:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
.seq-card .seq-actions button.success:hover { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }

/* Badge */
.seq-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.seq-badge.active { background: #d1fae5; color: #059669; }
.seq-badge.paused { background: #f3f4f6; color: #6b7280; }

/* Templates section */
.seq-templates {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}
.seq-tpl-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s;
}
.seq-tpl-card:hover {
    border-color: #7c3aed;
    box-shadow: 0 2px 8px rgba(124,58,237,0.1);
}
.seq-tpl-card .tpl-name {
    font-size: 15px;
    font-weight: 600;
    color: #1a1a2e;
    margin-bottom: 6px;
}
.seq-tpl-card .tpl-preview {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.4;
}

/* Section wrapper */
.seq-section {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    margin-bottom: 24px;
    overflow: hidden;
}
.seq-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
}
.seq-section-header h3 { font-size: 16px; font-weight: 600; color: #1a1a2e; margin: 0; }
.seq-section-body { padding: 20px; }

/* Empty state */
.seq-empty {
    text-align: center;
    padding: 48px 20px;
    color: #9ca3af;
}
.seq-empty i { font-size: 48px; margin-bottom: 16px; }
.seq-empty h3 { font-size: 18px; color: #6b7280; margin-bottom: 8px; }
.seq-empty p { font-size: 14px; margin-bottom: 20px; }
.seq-empty .btn-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: #7c3aed;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}

/* Error */
.seq-error {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

/* Modal */
.seq-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.seq-modal-overlay.active { display: flex; }
.seq-modal {
    background: white;
    border-radius: 16px;
    max-width: 700px;
    width: 95%;
    max-height: 90vh;
    overflow-y: auto;
    padding: 30px;
    position: relative;
}
.seq-modal .close-btn {
    position: absolute;
    top: 12px;
    right: 16px;
    background: none;
    border: none;
    font-size: 22px;
    cursor: pointer;
    color: #9ca3af;
}
.seq-modal h2 { font-size: 20px; color: #1a1a2e; margin: 0 0 20px; }
.seq-modal label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.seq-modal input[type="text"],
.seq-modal input[type="number"],
.seq-modal select,
.seq-modal textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 16px;
    box-sizing: border-box;
    font-family: inherit;
}
.seq-modal textarea { min-height: 100px; resize: vertical; }
.seq-modal .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.seq-modal .btn-submit {
    width: 100%;
    padding: 12px;
    background: #7c3aed;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 8px;
}
.seq-modal .btn-submit:hover { background: #6d28d9; }

/* Responsive */
@media (max-width: 768px) {
    .seq-stats { grid-template-columns: repeat(2, 1fr); }
    .seq-card { flex-direction: column; align-items: flex-start; }
    .seq-card .seq-stats-mini { width: 100%; justify-content: space-around; }
    .seq-templates { grid-template-columns: 1fr; }
}
</style>

<div class="seq-page">
    <!-- Header -->
    <div class="seq-header-row">
        <div>
            <h1><i class="fas fa-envelope" style="color:#7c3aed;margin-right:8px;"></i> Séquences Email B2B</h1>
            <p class="subtitle">Automatisez vos campagnes de prospection</p>
        </div>
        <button class="btn-create" onclick="seqOpenCreate()">
            <i class="fas fa-plus"></i> Nouvelle séquence
        </button>
    </div>

    <?php if ($db_error): ?>
        <div class="seq-error">
            <strong><i class="fas fa-exclamation-triangle"></i> Erreur :</strong> <?= htmlspecialchars($db_error) ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="seq-stats">
        <div class="seq-stat purple">
            <div class="val"><?= $stats['total'] ?></div>
            <div class="lbl">Séquences</div>
        </div>
        <div class="seq-stat green">
            <div class="val"><?= $stats['active'] ?></div>
            <div class="lbl">Actives</div>
        </div>
        <div class="seq-stat blue">
            <div class="val"><?= number_format($stats['total_sent']) ?></div>
            <div class="lbl">Emails envoyés</div>
        </div>
        <div class="seq-stat amber">
            <div class="val"><?= $stats['taux_ouverture'] ?>%</div>
            <div class="lbl">Taux ouverture</div>
        </div>
    </div>

    <!-- Liste des séquences -->
    <?php if (empty($sequences) && !$db_error): ?>
        <div class="seq-empty">
            <i class="fas fa-envelope-open-text"></i>
            <h3>Aucune séquence email</h3>
            <p>Créez votre première séquence de prospection B2B pour contacter automatiquement vos prospects.</p>
            <button class="btn-action" onclick="seqOpenCreate()"><i class="fas fa-plus"></i> Créer une séquence</button>
        </div>
    <?php else: ?>
        <div class="seq-list">
            <?php foreach ($sequences as $seq): 
                $isActive = ($seq['is_active'] ?? 0) == 1;
                $sent = (int)($seq['emails_sent'] ?? 0);
                $opened = (int)($seq['emails_opened'] ?? 0);
                $openRate = $sent > 0 ? round(($opened / $sent) * 100) : 0;
            ?>
                <div class="seq-card" data-id="<?= $seq['id'] ?>">
                    <div class="seq-icon <?= $isActive ? 'active' : 'paused' ?>">
                        <i class="fas fa-<?= $isActive ? 'play-circle' : 'pause-circle' ?>"></i>
                    </div>
                    <div class="seq-info">
                        <div class="seq-name">
                            <?= htmlspecialchars($seq['name'] ?? $seq['title'] ?? 'Sans nom') ?>
                            <span class="seq-badge <?= $isActive ? 'active' : 'paused' ?>"><?= $isActive ? 'Active' : 'Pause' ?></span>
                        </div>
                        <div class="seq-meta">
                            <span><i class="fas fa-layer-group"></i> <?= (int)($seq['nb_steps'] ?? 0) ?> étapes</span>
                            <span><i class="fas fa-users"></i> <?= (int)($seq['nb_contacts'] ?? 0) ?> contacts</span>
                            <span><i class="fas fa-calendar"></i> <?= !empty($seq['created_at']) ? date('d/m/Y', strtotime($seq['created_at'])) : '—' ?></span>
                        </div>
                    </div>
                    <div class="seq-stats-mini">
                        <div class="mini-stat">
                            <div class="mini-val"><?= $sent ?></div>
                            <div class="mini-lbl">Envoyés</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-val"><?= $opened ?></div>
                            <div class="mini-lbl">Ouverts</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-val"><?= $openRate ?>%</div>
                            <div class="mini-lbl">Taux</div>
                        </div>
                    </div>
                    <div class="seq-actions">
                        <button title="<?= $isActive ? 'Pause' : 'Activer' ?>" class="success" onclick="seqToggle(<?= $seq['id'] ?>, <?= $isActive ? 0 : 1 ?>)">
                            <i class="fas fa-<?= $isActive ? 'pause' : 'play' ?>"></i>
                        </button>
                        <button title="Modifier" onclick="seqEdit(<?= $seq['id'] ?>)">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button title="Supprimer" class="danger" onclick="seqDelete(<?= $seq['id'] ?>, '<?= htmlspecialchars(addslashes($seq['name'] ?? ''), ENT_QUOTES) ?>')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Templates disponibles -->
    <?php if (!empty($templates)): ?>
        <div class="seq-section">
            <div class="seq-section-header">
                <h3><i class="fas fa-file-alt" style="color:#2563eb;margin-right:6px;"></i> Templates email B2B</h3>
            </div>
            <div class="seq-section-body">
                <div class="seq-templates">
                    <?php foreach ($templates as $tpl): ?>
                        <div class="seq-tpl-card" onclick="seqUseTemplate('<?= $tpl['file'] ?>')">
                            <div class="tpl-name"><i class="fas fa-file-code" style="color:#7c3aed;margin-right:6px;"></i> <?= htmlspecialchars($tpl['name']) ?></div>
                            <div class="tpl-preview"><?= htmlspecialchars($tpl['preview']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ─── Modal Création / Édition ─── -->
<div id="seqModal" class="seq-modal-overlay">
    <div class="seq-modal">
        <button class="close-btn" onclick="seqCloseModal()">×</button>
        <h2 id="seqModalTitle"><i class="fas fa-plus-circle" style="color:#7c3aed;"></i> Nouvelle séquence</h2>
        
        <input type="hidden" id="seqId" value="">
        
        <label>Nom de la séquence</label>
        <input type="text" id="seqName" placeholder="Ex: Échange de liens Google">
        
        <div class="form-row">
            <div>
                <label>Type de campagne</label>
                <select id="seqType">
                    <option value="link-exchange">Échange de liens Google</option>
                    <option value="local-partnership">Partenariat local</option>
                    <option value="local-guide">Guide local</option>
                    <option value="broker-network">Réseau courtier</option>
                    <option value="custom">Personnalisé</option>
                </select>
            </div>
            <div>
                <label>Délai entre emails (jours)</label>
                <input type="number" id="seqDelay" value="3" min="1" max="30">
            </div>
        </div>
        
        <label>Description (optionnel)</label>
        <textarea id="seqDescription" placeholder="Objectif de cette séquence..."></textarea>
        
        <div id="seqStepsContainer">
            <label style="margin-bottom:12px;">Étapes de la séquence</label>
            <div id="seqStepsList"></div>
            <button type="button" onclick="seqAddStep()" style="margin-top:8px;padding:8px 16px;border:1px dashed #d1d5db;border-radius:8px;background:white;cursor:pointer;color:#6b7280;font-size:13px;width:100%;">
                <i class="fas fa-plus"></i> Ajouter une étape
            </button>
        </div>
        
        <button class="btn-submit" onclick="seqSave()">
            <i class="fas fa-save"></i> Enregistrer la séquence
        </button>
    </div>
</div>

<script>
const SEQ_API = 'modules/gmb/api/sequences.php';
let seqStepCount = 0;

// ─── CRUD ───
function seqOpenCreate() {
    document.getElementById('seqId').value = '';
    document.getElementById('seqName').value = '';
    document.getElementById('seqType').value = 'link-exchange';
    document.getElementById('seqDelay').value = '3';
    document.getElementById('seqDescription').value = '';
    document.getElementById('seqStepsList').innerHTML = '';
    document.getElementById('seqModalTitle').innerHTML = '<i class="fas fa-plus-circle" style="color:#7c3aed;"></i> Nouvelle séquence';
    seqStepCount = 0;
    seqAddStep(); // Ajouter une première étape par défaut
    document.getElementById('seqModal').classList.add('active');
}

function seqCloseModal() {
    document.getElementById('seqModal').classList.remove('active');
}

function seqAddStep() {
    seqStepCount++;
    const div = document.createElement('div');
    div.style.cssText = 'background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-bottom:10px;position:relative;';
    div.innerHTML = `
        <button onclick="this.parentElement.remove()" style="position:absolute;top:8px;right:8px;background:none;border:none;color:#9ca3af;cursor:pointer;font-size:16px;">×</button>
        <div style="font-size:13px;font-weight:600;color:#7c3aed;margin-bottom:8px;">Étape ${seqStepCount}</div>
        <input type="text" class="step-subject" placeholder="Sujet de l'email" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;margin-bottom:8px;box-sizing:border-box;">
        <textarea class="step-body" placeholder="Corps de l'email (variables: {{nom}}, {{entreprise}}, {{ville}}, {{lien_site}})" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;min-height:80px;box-sizing:border-box;font-family:inherit;resize:vertical;"></textarea>
    `;
    document.getElementById('seqStepsList').appendChild(div);
}

function seqSave() {
    const id = document.getElementById('seqId').value;
    const name = document.getElementById('seqName').value.trim();
    const type = document.getElementById('seqType').value;
    const delay = document.getElementById('seqDelay').value;
    const description = document.getElementById('seqDescription').value.trim();
    
    if (!name) { alert('Nom requis'); return; }
    
    // Collecter les étapes
    const steps = [];
    document.querySelectorAll('#seqStepsList > div').forEach((div, i) => {
        const subject = div.querySelector('.step-subject')?.value?.trim() || '';
        const body = div.querySelector('.step-body')?.value?.trim() || '';
        if (subject || body) {
            steps.push({ step_number: i + 1, subject, body, delay_days: parseInt(delay) });
        }
    });
    
    const action = id ? 'update' : 'create';
    const payload = { action, name, type, delay_days: parseInt(delay), description, steps };
    if (id) payload.id = parseInt(id);
    
    fetch(SEQ_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            seqCloseModal();
            seqToast('Séquence ' + (id ? 'mise à jour' : 'créée') + ' !', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('Erreur: ' + (data.error || 'Erreur inconnue'));
        }
    })
    .catch(err => alert('Erreur réseau: ' + err.message));
}

function seqToggle(id, newState) {
    fetch(SEQ_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'toggle', id, is_active: newState })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            seqToast(newState ? 'Séquence activée' : 'Séquence en pause', 'success');
            setTimeout(() => location.reload(), 800);
        }
    });
}

function seqDelete(id, name) {
    if (!confirm('Supprimer la séquence "' + name + '" et toutes ses étapes ?')) return;
    
    fetch(SEQ_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'delete', id })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            seqToast('Séquence supprimée', 'success');
            setTimeout(() => location.reload(), 800);
        }
    });
}

function seqEdit(id) {
    fetch(SEQ_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get', id })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.sequence) {
            const s = data.sequence;
            document.getElementById('seqId').value = s.id;
            document.getElementById('seqName').value = s.name || s.title || '';
            document.getElementById('seqType').value = s.type || 'custom';
            document.getElementById('seqDelay').value = s.delay_days || 3;
            document.getElementById('seqDescription').value = s.description || '';
            document.getElementById('seqModalTitle').innerHTML = '<i class="fas fa-pen" style="color:#7c3aed;"></i> Modifier la séquence';
            
            // Charger les étapes
            document.getElementById('seqStepsList').innerHTML = '';
            seqStepCount = 0;
            if (data.steps && data.steps.length > 0) {
                data.steps.forEach(step => {
                    seqStepCount++;
                    const div = document.createElement('div');
                    div.style.cssText = 'background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-bottom:10px;position:relative;';
                    div.innerHTML = `
                        <button onclick="this.parentElement.remove()" style="position:absolute;top:8px;right:8px;background:none;border:none;color:#9ca3af;cursor:pointer;font-size:16px;">×</button>
                        <div style="font-size:13px;font-weight:600;color:#7c3aed;margin-bottom:8px;">Étape ${seqStepCount}</div>
                        <input type="text" class="step-subject" value="${(step.subject || '').replace(/"/g, '&quot;')}" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;margin-bottom:8px;box-sizing:border-box;">
                        <textarea class="step-body" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;min-height:80px;box-sizing:border-box;font-family:inherit;resize:vertical;">${(step.body || '').replace(/</g, '&lt;')}</textarea>
                    `;
                    document.getElementById('seqStepsList').appendChild(div);
                });
            } else {
                seqAddStep();
            }
            
            document.getElementById('seqModal').classList.add('active');
        }
    });
}

function seqUseTemplate(file) {
    fetch(SEQ_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'get_template', template: file })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            seqOpenCreate();
            document.getElementById('seqName').value = data.name || file;
            if (data.steps && data.steps.length > 0) {
                document.getElementById('seqStepsList').innerHTML = '';
                seqStepCount = 0;
                data.steps.forEach(step => {
                    seqStepCount++;
                    const div = document.createElement('div');
                    div.style.cssText = 'background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:14px;margin-bottom:10px;position:relative;';
                    div.innerHTML = `
                        <button onclick="this.parentElement.remove()" style="position:absolute;top:8px;right:8px;background:none;border:none;color:#9ca3af;cursor:pointer;font-size:16px;">×</button>
                        <div style="font-size:13px;font-weight:600;color:#7c3aed;margin-bottom:8px;">Étape ${seqStepCount}</div>
                        <input type="text" class="step-subject" value="${(step.subject || '').replace(/"/g, '&quot;')}" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;margin-bottom:8px;box-sizing:border-box;">
                        <textarea class="step-body" style="width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:13px;min-height:80px;box-sizing:border-box;font-family:inherit;resize:vertical;">${(step.body || '').replace(/</g, '&lt;')}</textarea>
                    `;
                    document.getElementById('seqStepsList').appendChild(div);
                });
            }
        }
    });
}

// Toast notification
function seqToast(msg, type) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:8px;font-size:14px;font-weight:500;z-index:99999;color:white;background:' + (type === 'success' ? '#059669' : '#dc2626');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

// Close modal on Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') seqCloseModal();
});
</script>

<?php
if (!$isEmbedded) {
    $content = ob_get_clean();
    if (file_exists(__DIR__ . '/../../includes/layout.php')) {
        require __DIR__ . '/../../includes/layout.php';
    } else {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . $page_title . '</title>';
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
        echo '</head><body style="padding:40px;">' . $content . '</body></html>';
    }
}
?>