<?php
/**
 * ══════════════════════════════════════════════════════════════
 * COURTIERS — Formulaire Create / Edit v1.0
 * /admin/modules/courtiers/edit.php
 * ══════════════════════════════════════════════════════════════
 */
if (!defined('ADMIN_ROUTER')) { http_response_code(403); exit('Accès refusé'); }
if (isset($db) && !isset($pdo)) $pdo = $db;

$id       = (int)($_GET['id'] ?? 0);
$isEdit   = ($id > 0);
$courtier = [];

// ─── Leads disponibles pour liaison ───
$leads = [];
try {
    $pdo->query("SELECT 1 FROM leads LIMIT 1");
    $leads = $pdo->query("SELECT id, CONCAT(COALESCE(firstname,''),' ',COALESCE(lastname,'')) AS nom, email FROM leads ORDER BY lastname, firstname")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// ─── Charger courtier existant ───
if ($isEdit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM courtiers WHERE id = ?");
        $stmt->execute([$id]);
        $courtier = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$courtier) { header('Location: /admin/dashboard.php?page=courtiers&msg=error'); exit; }
    } catch (PDOException $e) {
        header('Location: /admin/dashboard.php?page=courtiers&msg=error'); exit;
    }
}

$pageTitle = $isEdit ? 'Modifier le courtier' : 'Nouveau courtier';
$v = fn($k, $d='') => htmlspecialchars($courtier[$k] ?? $d);
?>
<style>
/* ── Formulaire courtier ─────────────────────────────────────── */
.crt-form-wrap { }

.crt-form-header {
    background:var(--surface,#fff); border-radius:16px; padding:22px 28px; margin-bottom:22px;
    display:flex; align-items:center; justify-content:space-between;
    border:1px solid var(--border,#e5e7eb); position:relative; overflow:hidden;
}
.crt-form-header::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg,#14b8a6,#3b82f6,#8b5cf6);
}
.crt-form-header h2 { font-size:1.1rem; font-weight:700; display:flex; align-items:center; gap:10px; margin:0; }
.crt-form-header h2 i { color:#14b8a6; }
.crt-form-header .breadcrumb { font-size:.78rem; color:var(--text-3,#9ca3af); margin-top:4px; }
.crt-form-header .breadcrumb a { color:#14b8a6; text-decoration:none; }

.crt-card {
    background:var(--surface,#fff); border:1px solid var(--border,#e5e7eb);
    border-radius:14px; overflow:hidden; margin-bottom:18px;
}
.crt-card-head {
    padding:14px 20px; border-bottom:1px solid var(--border,#e5e7eb);
    display:flex; align-items:center; gap:10px; font-size:.85rem; font-weight:700;
    color:var(--text,#111827); background:var(--surface-2,#f9fafb);
}
.crt-card-head i { font-size:.8rem; width:18px; text-align:center; }
.crt-card-body { padding:22px 24px; }

.crt-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.crt-form-grid.cols-3 { grid-template-columns:1fr 1fr 1fr; }
.crt-form-grid.cols-1 { grid-template-columns:1fr; }
.crt-full { grid-column:1/-1; }

.crt-field { display:flex; flex-direction:column; gap:6px; }
.crt-label { font-size:.75rem; font-weight:600; color:var(--text,#111827); display:flex; align-items:center; gap:5px; }
.crt-label .req { color:#ef4444; }
.crt-input {
    padding:10px 14px; border:1px solid var(--border,#e5e7eb); border-radius:9px;
    font-size:.85rem; color:var(--text,#111827); background:var(--surface,#fff);
    font-family:inherit; transition:all .2s; width:100%; box-sizing:border-box;
}
.crt-input:focus { outline:none; border-color:#14b8a6; box-shadow:0 0 0 3px rgba(20,184,166,.1); }
.crt-input::placeholder { color:var(--text-3,#9ca3af); }
textarea.crt-input { resize:vertical; min-height:90px; }
select.crt-input { cursor:pointer; }
.crt-help { font-size:.7rem; color:var(--text-3,#9ca3af); }

/* ─── Liaison contact ─── */
.crt-lead-link {
    padding:12px 16px; background:rgba(20,184,166,.05); border:1px solid rgba(20,184,166,.2);
    border-radius:10px; display:flex; align-items:center; gap:12px; margin-top:8px;
}
.crt-lead-link i { color:#14b8a6; }
.crt-lead-link .info { font-size:.78rem; }
.crt-lead-link .name { font-weight:600; color:var(--text,#111827); }
.crt-lead-link .email { color:var(--text-3,#9ca3af); font-size:.72rem; }
.crt-lead-clear { margin-left:auto; background:none; border:none; color:#ef4444; cursor:pointer; font-size:.75rem; padding:4px 8px; border-radius:6px; }
.crt-lead-clear:hover { background:#fef2f2; }

/* ─── Actions footer ─── */
.crt-form-footer {
    background:var(--surface,#fff); border:1px solid var(--border,#e5e7eb); border-radius:14px;
    padding:16px 24px; display:flex; align-items:center; justify-content:space-between; gap:12px;
}
.crt-btn { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:10px; font-size:.83rem; font-weight:600; cursor:pointer; border:none; transition:all .15s; font-family:inherit; text-decoration:none; line-height:1.3; }
.crt-btn-primary { background:#14b8a6; color:#fff; box-shadow:0 1px 4px rgba(20,184,166,.22); }
.crt-btn-primary:hover { background:#0d9488; color:#fff; }
.crt-btn-outline { background:var(--surface,#fff); color:var(--text-2,#6b7280); border:1px solid var(--border,#e5e7eb); }
.crt-btn-outline:hover { border-color:#14b8a6; color:#14b8a6; }
.crt-btn-danger { background:#fef2f2; color:#dc2626; border:1px solid rgba(220,38,38,.15); }
.crt-btn-danger:hover { background:#dc2626; color:#fff; }

/* ─── Tags reco ─── */
.crt-counter-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
.crt-counter-card { text-align:center; padding:16px; background:var(--surface-2,#f9fafb); border-radius:10px; border:1px solid var(--border,#e5e7eb); }
.crt-counter-card .val { font-size:1.6rem; font-weight:800; color:var(--text,#111827); }
.crt-counter-card .val.teal { color:#14b8a6; }
.crt-counter-card .val.violet { color:#8b5cf6; }
.crt-counter-card .val.green { color:#10b981; }
.crt-counter-card .lbl { font-size:.7rem; color:var(--text-3,#9ca3af); margin-top:4px; text-transform:uppercase; letter-spacing:.04em; font-weight:600; }

@media(max-width:768px){
    .crt-form-grid { grid-template-columns:1fr; }
    .crt-form-grid.cols-3 { grid-template-columns:1fr 1fr; }
    .crt-counter-grid { grid-template-columns:1fr 1fr; }
}
</style>

<div class="crt-form-outer">
<div class="crt-form-wrap">
    <!-- Header -->
    <div class="crt-form-header">
        <div>
            <h2><i class="fas fa-briefcase"></i> <?= $pageTitle ?></h2>
            <div class="breadcrumb">
                <a href="?page=courtiers">Courtiers</a> › <?= $isEdit ? htmlspecialchars(($courtier['prenom']??'').' '.($courtier['nom']??'')) : 'Nouveau' ?>
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="?page=courtiers" class="crt-btn crt-btn-outline"><i class="fas fa-arrow-left"></i> Retour</a>
        </div>
    </div>

    <form method="POST" action="/admin/dashboard.php?page=courtiers-api" id="crtForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

        <!-- Identité -->
        <div class="crt-card">
            <div class="crt-card-head"><i class="fas fa-user" style="color:#14b8a6"></i> Identité</div>
            <div class="crt-card-body">
                <div class="crt-form-grid">
                    <div class="crt-field">
                        <label class="crt-label">Prénom <span class="req">*</span></label>
                        <input type="text" name="prenom" class="crt-input" value="<?= $v('prenom') ?>" placeholder="Jean" required>
                    </div>
                    <div class="crt-field">
                        <label class="crt-label">Nom <span class="req">*</span></label>
                        <input type="text" name="nom" class="crt-input" value="<?= $v('nom') ?>" placeholder="Dupont" required>
                    </div>
                    <div class="crt-field">
                        <label class="crt-label">Email</label>
                        <input type="email" name="email" class="crt-input" value="<?= $v('email') ?>" placeholder="jean@cabinet.fr">
                    </div>
                    <div class="crt-field">
                        <label class="crt-label">Téléphone</label>
                        <input type="tel" name="phone" class="crt-input" value="<?= $v('phone') ?>" placeholder="06 12 34 56 78">
                    </div>
                    <div class="crt-field crt-full">
                        <label class="crt-label">Société / Cabinet</label>
                        <input type="text" name="company" class="crt-input" value="<?= $v('company') ?>" placeholder="Cabinet Dupont Financement">
                    </div>
                </div>
            </div>
        </div>

        <!-- Qualification -->
        <div class="crt-card">
            <div class="crt-card-head"><i class="fas fa-tags" style="color:#8b5cf6"></i> Qualification & Zone</div>
            <div class="crt-card-body">
                <div class="crt-form-grid cols-3">
                    <div class="crt-field">
                        <label class="crt-label">Type</label>
                        <select name="type" class="crt-input">
                            <?php foreach (['courtier'=>'Courtier','mandataire'=>'Mandataire','apporteur'=>"Apporteur d'affaire",'partenaire'=>'Partenaire','notaire'=>'Notaire'] as $k=>$lbl): ?>
                            <option value="<?= $k ?>" <?= ($courtier['type']??'courtier')===$k?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="crt-field">
                        <label class="crt-label">Statut</label>
                        <select name="status" class="crt-input">
                            <?php foreach (['prospect'=>'Prospect','actif'=>'Actif','pause'=>'Pause','inactif'=>'Inactif'] as $k=>$lbl): ?>
                            <option value="<?= $k ?>" <?= ($courtier['status']??'prospect')===$k?'selected':'' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="crt-field">
                        <label class="crt-label">Commission (%)</label>
                        <input type="number" name="commission_rate" class="crt-input" value="<?= $v('commission_rate','0') ?>" min="0" max="100" step="0.5" placeholder="1.5">
                        <span class="crt-help">% de commission sur recommandations converties</span>
                    </div>
                    <div class="crt-field">
                        <label class="crt-label">Ville</label>
                        <input type="text" name="city" class="crt-input" value="<?= $v('city') ?>" placeholder="Bordeaux">
                    </div>
                    <div class="crt-field crt-full">
                        <label class="crt-label">Zone géographique</label>
                        <input type="text" name="zone_geo" class="crt-input" value="<?= $v('zone_geo') ?>" placeholder="Bordeaux, Gironde, Mérignac…">
                        <span class="crt-help">Zones où ce courtier apporte des clients</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liaison CRM -->
        <div class="crt-card">
            <div class="crt-card-head"><i class="fas fa-user-circle" style="color:#3b82f6"></i> Liaison contact CRM</div>
            <div class="crt-card-body">
                <?php if (!empty($leads)): ?>
                <div class="crt-field">
                    <label class="crt-label">Associer à un contact CRM existant</label>
                    <select name="lead_id" class="crt-input" id="leadSelect" onchange="CRT_FORM.updateLeadPreview(this)">
                        <option value="">— Aucun contact lié —</option>
                        <?php foreach ($leads as $lead):
                            $selected = (isset($courtier['lead_id']) && $courtier['lead_id'] == $lead['id']) ? 'selected' : '';
                        ?>
                        <option value="<?= (int)$lead['id'] ?>" <?= $selected ?>
                                data-nom="<?= htmlspecialchars(trim($lead['nom'])) ?>"
                                data-email="<?= htmlspecialchars($lead['email'] ?? '') ?>">
                            <?= htmlspecialchars(trim($lead['nom'])) ?> — <?= htmlspecialchars($lead['email'] ?? '') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="crt-help">Le courtier sera visible depuis sa fiche contact dans le CRM</span>
                </div>
                <div id="leadPreview" style="display:none" class="crt-lead-link">
                    <i class="fas fa-user-circle fa-lg"></i>
                    <div class="info">
                        <div class="name" id="leadPreviewName"></div>
                        <div class="email" id="leadPreviewEmail"></div>
                    </div>
                    <button type="button" class="crt-lead-clear" onclick="CRT_FORM.clearLead()">
                        <i class="fas fa-times"></i> Délier
                    </button>
                </div>
                <?php else: ?>
                <div style="padding:14px;background:var(--surface-2,#f9fafb);border-radius:10px;font-size:.82rem;color:var(--text-2,#6b7280)">
                    <i class="fas fa-info-circle" style="color:#3b82f6"></i>
                    Aucun contact dans le CRM. Ajoutez des leads d'abord pour créer la liaison.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats (edit only) -->
        <?php if ($isEdit): ?>
        <div class="crt-card">
            <div class="crt-card-head"><i class="fas fa-chart-bar" style="color:#10b981"></i> Statistiques</div>
            <div class="crt-card-body">
                <div class="crt-counter-grid">
                    <div class="crt-counter-card">
                        <div class="val violet"><?= (int)($courtier['reco_count'] ?? 0) ?></div>
                        <div class="lbl">Recommandations</div>
                    </div>
                    <div class="crt-counter-card">
                        <div class="val green"><?= number_format((float)($courtier['revenu_total']??0),0,',',' ') ?>€</div>
                        <div class="lbl">CA généré</div>
                    </div>
                    <div class="crt-counter-card">
                        <div class="val teal">
                            <?php
                            $reco = (int)($courtier['reco_count'] ?? 0);
                            $ca   = (float)($courtier['revenu_total'] ?? 0);
                            echo $reco > 0 ? number_format($ca/$reco, 0, ',', ' ').'€' : '—';
                            ?>
                        </div>
                        <div class="lbl">CA / reco</div>
                    </div>
                </div>
                <div class="crt-form-grid" style="margin-top:16px">
                    <div class="crt-field">
                        <label class="crt-label">Nombre de recommandations</label>
                        <input type="number" name="reco_count" class="crt-input" value="<?= (int)($courtier['reco_count']??0) ?>" min="0">
                    </div>
                    <div class="crt-field">
                        <label class="crt-label">Revenu total généré (€)</label>
                        <input type="number" name="revenu_total" class="crt-input" value="<?= (float)($courtier['revenu_total']??0) ?>" min="0" step="0.01">
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <div class="crt-card">
            <div class="crt-card-head"><i class="fas fa-sticky-note" style="color:#f59e0b"></i> Notes internes</div>
            <div class="crt-card-body">
                <div class="crt-field">
                    <textarea name="notes" class="crt-input" placeholder="Spécialités, conditions particulières, historique de la relation…"><?= $v('notes') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Footer actions -->
        <div class="crt-form-footer">
            <div>
                <?php if ($isEdit): ?>
                <button type="button" class="crt-btn crt-btn-danger"
                        onclick="CRT_FORM.confirmDelete(<?= $id ?>, '<?= addslashes(htmlspecialchars(($courtier['prenom']??'').' '.($courtier['nom']??''))) ?>')">
                    <i class="fas fa-trash"></i> Supprimer
                </button>
                <?php endif; ?>
            </div>
            <div style="display:flex;gap:10px">
                <a href="?page=courtiers" class="crt-btn crt-btn-outline"><i class="fas fa-times"></i> Annuler</a>
                <button type="submit" class="crt-btn crt-btn-primary">
                    <i class="fas fa-save"></i> <?= $isEdit ? 'Enregistrer' : 'Créer le courtier' ?>
                </button>
            </div>
        </div>
    </form>
</div><!-- /crt-form-wrap -->

<!-- ══ PANNEAU AIDE CONTEXTUEL ══════════════════════════════ -->
<aside class="crt-help-panel" id="crtHelpPanel">
    <div class="crt-help-header">
        <div class="crt-help-icon"><i class="fas fa-lightbulb"></i></div>
        <div>
            <div class="crt-help-title">Guide courtier</div>
            <div class="crt-help-sub">Conseils par section</div>
        </div>
        <button class="crt-help-toggle" onclick="CRT_HELP.toggle()" title="Réduire">
            <i class="fas fa-chevron-right" id="crtHelpChevron"></i>
        </button>
    </div>

    <div class="crt-help-body" id="crtHelpBody">

        <!-- Section active -->
        <div class="crt-help-active" id="crtHelpActive">
            <div class="crt-help-section-label" id="crtHelpSectionLabel">
                <i class="fas fa-user"></i> Identité
            </div>
            <div class="crt-help-text" id="crtHelpText">
                Renseignez les coordonnées complètes du courtier. L'email et le téléphone permettront de le contacter directement depuis la liste.
            </div>
        </div>

        <div class="crt-help-divider"></div>

        <!-- Conseils statiques -->
        <div class="crt-help-tips">
            <div class="crt-help-tip-title"><i class="fas fa-star"></i> Bonnes pratiques</div>

            <div class="crt-tip-card" data-section="identite">
                <div class="crt-tip-icon" style="background:#14b8a618;color:#14b8a6"><i class="fas fa-user"></i></div>
                <div>
                    <strong>Identité</strong>
                    <p>Toujours renseigner email + téléphone pour activer les boutons d'action rapide dans la liste.</p>
                </div>
            </div>

            <div class="crt-tip-card" data-section="qualification">
                <div class="crt-tip-icon" style="background:#8b5cf618;color:#8b5cf6"><i class="fas fa-tags"></i></div>
                <div>
                    <strong>Type & Commission</strong>
                    <p>Un courtier apporte des clients acheteurs. Un apporteur d'affaire peut être un notaire, diagnostiqueur… La commission standard est entre 0,5% et 1,5%.</p>
                </div>
            </div>

            <div class="crt-tip-card" data-section="qualification">
                <div class="crt-tip-icon" style="background:#f59e0b18;color:#f59e0b"><i class="fas fa-map-marker-alt"></i></div>
                <div>
                    <strong>Zone géographique</strong>
                    <p>Précisez les villes ou codes postaux couverts. Cela vous permettra de filtrer par zone et de voir quel courtier activer selon le secteur d'un bien.</p>
                </div>
            </div>

            <div class="crt-tip-card" data-section="crm">
                <div class="crt-tip-icon" style="background:#3b82f618;color:#3b82f6"><i class="fas fa-user-circle"></i></div>
                <div>
                    <strong>Liaison CRM</strong>
                    <p>Si le courtier vous a déjà envoyé un client, il est peut-être déjà dans vos leads. Liez-le pour centraliser tout l'historique sur sa fiche contact.</p>
                </div>
            </div>

            <div class="crt-tip-card" data-section="notes">
                <div class="crt-tip-icon" style="background:#10b98118;color:#10b981"><i class="fas fa-sticky-note"></i></div>
                <div>
                    <strong>Notes internes</strong>
                    <p>Notez les spécialités (primo-accédants, investisseurs, rachats de crédit), les conditions négociées, ou la qualité des dossiers envoyés.</p>
                </div>
            </div>
        </div>

        <div class="crt-help-divider"></div>

        <!-- Statuts -->
        <div class="crt-help-tip-title"><i class="fas fa-circle-info"></i> Statuts</div>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:8px">
            <?php foreach ([
                ['prospect','#2563eb','Vous l\'avez identifié mais pas encore de collaboration'],
                ['actif',   '#059669','Collaboration en cours, il vous envoie des dossiers'],
                ['pause',   '#d97706','Relation suspendue temporairement'],
                ['inactif', '#9ca3af','Plus de collaboration, gardé pour historique'],
            ] as [$s,$c,$desc]): ?>
            <div style="display:flex;gap:8px;align-items:flex-start;font-size:.75rem">
                <span style="padding:2px 8px;border-radius:10px;background:<?= $c ?>18;color:<?= $c ?>;font-weight:700;font-size:.65rem;flex-shrink:0;margin-top:1px"><?= ucfirst($s) ?></span>
                <span style="color:var(--text-2,#6b7280);line-height:1.5"><?= $desc ?></span>
            </div>
            <?php endforeach; ?>
        </div>

    </div><!-- /crt-help-body -->
</aside>

</div><!-- /crt-form-outer -->

<style>
/* ── Layout 50/50 — deux colonnes de hauteur égale ─────────── */
.crt-form-outer {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    align-items: stretch;  /* les deux colonnes à la même hauteur */
    max-width: 1280px;
}
.crt-form-wrap {
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0;
}
.crt-form-wrap .crt-form-header,
.crt-form-wrap form { max-width: none; }

/* ── Panneau aide — cadre fixe, scroll interne ────────────── */
.crt-help-panel {
    background: var(--surface,#fff);
    border: 1px solid var(--border,#e5e7eb);
    border-radius: 12px;
    position: sticky;
    top: 72px;
    /* Hauteur = viewport - topbar - marges */
    height: calc(100vh - 90px);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    font-size: .78rem;
    box-shadow: 0 1px 3px rgba(0,0,0,.06);
}

.crt-help-header {
    padding: 13px 16px;
    display: flex;
    align-items: center;
    gap: 9px;
    border-bottom: 1px solid var(--border,#e5e7eb);
    background: linear-gradient(135deg, rgba(20,184,166,.07), rgba(59,130,246,.04));
    flex-shrink: 0;
}
.crt-help-icon {
    width: 30px; height: 30px; border-radius: 8px;
    background: #14b8a618; color: #14b8a6;
    display: flex; align-items: center; justify-content: center;
    font-size: .8rem; flex-shrink: 0;
}
.crt-help-title { font-size: .82rem; font-weight: 700; color: var(--text,#111827); }
.crt-help-sub   { font-size: .65rem; color: var(--text-3,#9ca3af); }
.crt-help-toggle {
    margin-left: auto; background: none; border: none; cursor: pointer;
    color: var(--text-3,#9ca3af); padding: 4px 6px; border-radius: 5px;
    transition: all .2s; flex-shrink: 0; font-size: .7rem;
}
.crt-help-toggle:hover { background: var(--surface-2,#f3f4f6); color: var(--text,#111827); }

/* Scroll uniquement sur le body du panneau */
.crt-help-body {
    padding: 14px;
    overflow-y: auto;
    flex: 1;
    scrollbar-width: thin;
    scrollbar-color: rgba(0,0,0,.08) transparent;
}
.crt-help-body::-webkit-scrollbar { width: 4px; }
.crt-help-body::-webkit-scrollbar-thumb { background: rgba(0,0,0,.1); border-radius: 2px; }

/* Section active */
.crt-help-active {
    background: rgba(20,184,166,.05);
    border: 1px solid rgba(20,184,166,.25);
    border-radius: 9px;
    padding: 10px 13px;
    margin-bottom: 12px;
    transition: all .3s;
}
.crt-help-section-label {
    font-size: .67rem; font-weight: 700; color: #14b8a6;
    display: flex; align-items: center; gap: 5px;
    margin-bottom: 5px; text-transform: uppercase; letter-spacing: .05em;
}
.crt-help-text {
    font-size: .75rem; color: var(--text-2,#374151); line-height: 1.6;
    transition: opacity .25s;
}

.crt-help-divider { height: 1px; background: var(--border,#e5e7eb); margin: 12px 0; }

.crt-help-tips { display: flex; flex-direction: column; gap: 7px; }
.crt-help-tip-title {
    font-size: .63rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: var(--text-3,#9ca3af);
    display: flex; align-items: center; gap: 5px; margin-bottom: 4px;
}

.crt-tip-card {
    display: flex; gap: 9px; align-items: flex-start;
    padding: 9px 11px; border-radius: 8px;
    border: 1px solid var(--border,#e5e7eb);
    font-size: .72rem; color: var(--text-2,#374151);
    transition: all .18s; cursor: default; line-height: 1.5;
}
.crt-tip-card:hover { border-color: #14b8a650; background: rgba(20,184,166,.02); }
.crt-tip-card.highlighted {
    border-color: #14b8a6;
    background: rgba(20,184,166,.05);
    box-shadow: 0 1px 6px rgba(20,184,166,.1);
}
.crt-tip-icon {
    width: 26px; height: 26px; border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: .68rem; flex-shrink: 0; margin-top: 1px;
}
.crt-tip-card strong { display: block; margin-bottom: 2px; font-size: .73rem; color: var(--text,#111827); }
.crt-tip-card p { margin: 0; line-height: 1.5; }

@media (max-width: 960px) {
    .crt-form-outer { grid-template-columns: 1fr; }
    .crt-help-panel { position: static; height: auto; }
}
</style>

<script>
const CRT_FORM = {
    updateLeadPreview(select) {
        const opt = select.options[select.selectedIndex];
        const preview = document.getElementById('leadPreview');
        if (opt.value) {
            document.getElementById('leadPreviewName').textContent  = opt.dataset.nom   || '';
            document.getElementById('leadPreviewEmail').textContent = opt.dataset.email || '';
            preview.style.display = 'flex';
        } else {
            preview.style.display = 'none';
        }
    },
    clearLead() {
        document.getElementById('leadSelect').value = '';
        document.getElementById('leadPreview').style.display = 'none';
    },
    confirmDelete(id, name) {
        if (!confirm(`Supprimer définitivement ${name} ?`)) return;
        const fd = new FormData();
        fd.append('action','delete'); fd.append('id',id);
        fd.append('csrf_token','<?= $_SESSION['csrf_token'] ?? '' ?>');
        fetch('/admin/dashboard.php?page=courtiers-api',{method:'POST',body:fd})
            .then(r=>r.json())
            .then(d=>{ if(d.success) window.location.href='?page=courtiers&msg=deleted'; else alert(d.error||'Erreur'); });
    }
};

// ── Aide contextuelle ──────────────────────────────────────
const CRT_HELP = {
    sections: {
        'identite':       { icon:'fa-user',        label:'Identité',           text:'Renseignez les coordonnées complètes du courtier. L\'email et le téléphone activent les boutons d\'action rapide dans la liste.' },
        'qualification':  { icon:'fa-tags',         label:'Qualification & Zone', text:'Le type définit la nature du partenariat. La zone géographique permet de filtrer les courtiers actifs sur un secteur précis.' },
        'crm':            { icon:'fa-user-circle',  label:'Liaison CRM',        text:'Si ce courtier vous a déjà envoyé des clients, il est peut-être dans vos leads. La liaison centralise tout l\'historique sur une seule fiche.' },
        'stats':          { icon:'fa-chart-bar',    label:'Statistiques',        text:'Suivez le nombre de recommandations et le chiffre d\'affaires généré. Ces données vous aident à identifier vos meilleurs partenaires.' },
        'notes':          { icon:'fa-sticky-note',  label:'Notes internes',     text:'Notez les spécialités, conditions négociées, et la qualité des dossiers envoyés. Ces notes sont privées et visibles uniquement par vous.' },
    },

    init() {
        // Observer les sections du formulaire
        document.querySelectorAll('.crt-card').forEach((card, i) => {
            const head = card.querySelector('.crt-card-head');
            if (!head) return;
            const text = head.textContent.trim().toLowerCase();
            let key = 'identite';
            if (text.includes('qualif'))  key = 'qualification';
            if (text.includes('crm'))     key = 'crm';
            if (text.includes('stat'))    key = 'stats';
            if (text.includes('note'))    key = 'notes';
            card.dataset.helpKey = key;
        });

        // Intersection observer — met à jour l'aide selon la section visible
        const obs = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting && e.intersectionRatio > 0.3) {
                    this.setSection(e.target.dataset.helpKey || 'identite');
                    // Highlight tip correspondante
                    document.querySelectorAll('.crt-tip-card').forEach(t => {
                        t.classList.toggle('highlighted', t.dataset.section === e.target.dataset.helpKey);
                    });
                }
            });
        }, { threshold: 0.3, rootMargin: '-100px 0px -200px 0px' });

        document.querySelectorAll('.crt-card').forEach(c => obs.observe(c));

        // Focus sur un champ → mise à jour
        document.querySelectorAll('.crt-input').forEach(input => {
            input.addEventListener('focus', () => {
                const card = input.closest('.crt-card');
                if (card?.dataset.helpKey) this.setSection(card.dataset.helpKey);
            });
        });
    },

    setSection(key) {
        const s = this.sections[key];
        if (!s) return;
        const label = document.getElementById('crtHelpSectionLabel');
        const text  = document.getElementById('crtHelpText');
        if (label) label.innerHTML = `<i class="fas ${s.icon}"></i> ${s.label}`;
        if (text)  { text.style.opacity = '0'; setTimeout(() => { text.textContent = s.text; text.style.opacity = '1'; }, 150); }
    },

    toggle() {
        document.getElementById('crtHelpPanel').classList.toggle('collapsed');
    }
};

document.addEventListener('DOMContentLoaded', () => {
    const s = document.getElementById('leadSelect');
    if (s && s.value) CRT_FORM.updateLeadPreview(s);
    CRT_HELP.init();
});
</script>