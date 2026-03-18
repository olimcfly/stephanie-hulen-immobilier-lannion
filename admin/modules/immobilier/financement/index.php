<?php
/**
 * Module Financement - Gestion des Leads Courtage
 * Même design que les autres modules du dashboard
 */

// Récupérer les statistiques
try {
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'nouveau' THEN 1 ELSE 0 END) as nouveaux,
        SUM(CASE WHEN statut = 'transmis' THEN 1 ELSE 0 END) as transmis,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
        SUM(CASE WHEN statut = 'finance' THEN 1 ELSE 0 END) as finances,
        SUM(CASE WHEN statut = 'commission_percue' THEN 1 ELSE 0 END) as commissions_percues,
        SUM(CASE WHEN statut = 'finance' OR statut = 'commission_percue' THEN commission_montant ELSE 0 END) as total_commissions,
        SUM(CASE WHEN statut = 'commission_percue' THEN commission_montant ELSE 0 END) as commissions_encaissees
        FROM financement_leads");
    $stats_fin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt_courtiers = $pdo->query("SELECT COUNT(*) as total FROM financement_courtiers WHERE actif = 1");
    $stats_courtiers = $stmt_courtiers->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $stats_fin = ['total' => 0, 'nouveaux' => 0, 'transmis' => 0, 'en_cours' => 0, 'finances' => 0, 'commissions_percues' => 0, 'total_commissions' => 0, 'commissions_encaissees' => 0];
    $stats_courtiers = ['total' => 0];
}
?>

<style>
.fin-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
.fin-header h2 { font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0; display: flex; align-items: center; gap: 10px; }
.fin-header h2 i { color: var(--success); }
.fin-actions { display: flex; gap: 10px; }

.btn { padding: 10px 18px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease; text-decoration: none; }
.btn-primary { background: var(--primary); color: white; }
.btn-primary:hover { background: var(--primary-dark); }
.btn-success { background: var(--success); color: white; }
.btn-success:hover { background: #059669; }
.btn-secondary { background: var(--light); color: var(--text-primary); border: 1px solid var(--border); }
.btn-secondary:hover { background: #e2e8f0; }
.btn-danger { background: var(--danger); color: white; }
.btn-sm { padding: 6px 12px; font-size: 12px; }

.fin-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
.fin-stat { background: white; border: 1px solid var(--border); border-radius: 10px; padding: 15px; }
.fin-stat:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.fin-stat-icon { width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 16px; margin-bottom: 10px; }
.fin-stat-icon.blue { background: rgba(99,102,241,0.1); color: var(--primary); }
.fin-stat-icon.green { background: rgba(16,185,129,0.1); color: var(--success); }
.fin-stat-icon.orange { background: rgba(245,158,11,0.1); color: var(--warning); }
.fin-stat-icon.cyan { background: rgba(6,182,212,0.1); color: #06b6d4; }
.fin-stat-icon.purple { background: rgba(139,92,246,0.1); color: var(--secondary); }
.fin-stat-icon.red { background: rgba(239,68,68,0.1); color: var(--danger); }
.fin-stat-value { font-size: 22px; font-weight: 800; color: var(--text-primary); }
.fin-stat-label { font-size: 11px; color: var(--text-secondary); margin-top: 3px; }

.fin-tabs { display: flex; gap: 5px; margin-bottom: 20px; border-bottom: 1px solid var(--border); }
.fin-tab { padding: 12px 18px; border: none; background: transparent; color: var(--text-secondary); cursor: pointer; font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 8px; border-bottom: 2px solid transparent; margin-bottom: -1px; transition: all 0.2s; }
.fin-tab:hover { color: var(--primary); }
.fin-tab.active { color: var(--primary); border-bottom-color: var(--primary); }
.fin-content { display: none; }
.fin-content.active { display: block; }

.fin-card { background: white; border: 1px solid var(--border); border-radius: 10px; margin-bottom: 20px; }
.fin-card-header { padding: 15px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.fin-card-title { font-size: 14px; font-weight: 600; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
.fin-card-body { padding: 20px; }

.fin-filters { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px; }
.fin-filter label { font-size: 11px; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; display: block; margin-bottom: 5px; }
.fin-input, .fin-select { padding: 9px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; color: var(--text-primary); background: white; min-width: 170px; }
.fin-input:focus, .fin-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

.fin-table { width: 100%; border-collapse: collapse; }
.fin-table th, .fin-table td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--border); }
.fin-table th { background: var(--light); color: var(--text-secondary); font-weight: 600; font-size: 11px; text-transform: uppercase; }
.fin-table tr:hover { background: rgba(99,102,241,0.02); }
.fin-table td { color: var(--text-primary); font-size: 13px; }

.lead-info { display: flex; flex-direction: column; gap: 2px; }
.lead-name { font-weight: 600; color: var(--text-primary); }
.lead-contact { font-size: 11px; color: var(--text-secondary); }

.badge { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
.badge-nouveau { background: rgba(99,102,241,0.1); color: var(--primary); }
.badge-transmis { background: rgba(6,182,212,0.1); color: #06b6d4; }
.badge-en_cours { background: rgba(245,158,11,0.1); color: var(--warning); }
.badge-finance { background: rgba(16,185,129,0.1); color: var(--success); }
.badge-commission_percue { background: rgba(16,185,129,0.15); color: #059669; }
.badge-perdu { background: rgba(239,68,68,0.1); color: var(--danger); }

.montant { font-weight: 700; color: var(--success); }
.courtier-tag { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; background: var(--light); border-radius: 6px; font-size: 12px; }

.actions-cell { display: flex; gap: 5px; }
.action-btn { width: 28px; height: 28px; border: 1px solid var(--border); border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; background: white; color: var(--text-secondary); transition: all 0.2s; }
.action-btn:hover { border-color: var(--primary); color: var(--primary); }
.action-btn.delete:hover { border-color: var(--danger); color: var(--danger); }
.action-btn.success:hover { border-color: var(--success); color: var(--success); }

.fin-modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 20px; }
.fin-modal-overlay.active { display: flex; }
.fin-modal { background: white; border-radius: 12px; width: 100%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 50px rgba(0,0,0,0.2); }
.fin-modal-header { padding: 18px 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.fin-modal-title { font-size: 15px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 10px; }
.fin-modal-close { width: 30px; height: 30px; border: none; background: var(--light); border-radius: 8px; cursor: pointer; color: var(--text-secondary); font-size: 16px; }
.fin-modal-close:hover { background: var(--danger); color: white; }
.fin-modal-body { padding: 20px; }
.fin-modal-footer { padding: 15px 20px; border-top: 1px solid var(--border); display: flex; justify-content: flex-end; gap: 10px; background: var(--light); }

.fin-form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
.fin-form-group { display: flex; flex-direction: column; gap: 5px; }
.fin-form-group.full { grid-column: span 2; }
.fin-form-group label { font-size: 13px; font-weight: 500; color: var(--text-primary); }
.fin-form-group label .req { color: var(--danger); }
.fin-form-control { padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; color: var(--text-primary); }
.fin-form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
textarea.fin-form-control { min-height: 70px; resize: vertical; }
.fin-section { font-size: 12px; font-weight: 600; color: var(--text-secondary); margin: 18px 0 10px; padding-bottom: 8px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 6px; }
.fin-section:first-child { margin-top: 0; }

.fin-pipeline { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; overflow-x: auto; }
.fin-col { background: var(--light); border-radius: 10px; min-width: 200px; border: 1px solid var(--border); }
.fin-col-header { padding: 12px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; }
.fin-col-title { font-weight: 600; font-size: 12px; display: flex; align-items: center; gap: 6px; }
.fin-col-count { background: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; border: 1px solid var(--border); }
.fin-col-body { padding: 8px; min-height: 250px; max-height: 400px; overflow-y: auto; }
.fin-pipe-card { background: white; border: 1px solid var(--border); border-radius: 8px; padding: 10px; margin-bottom: 8px; cursor: pointer; }
.fin-pipe-card:hover { border-color: var(--primary); box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.fin-pipe-name { font-weight: 600; font-size: 13px; color: var(--text-primary); }
.fin-pipe-amount { font-weight: 700; font-size: 12px; color: var(--success); }
.fin-pipe-info { font-size: 11px; color: var(--text-secondary); margin-top: 4px; }

.fin-courtiers { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 15px; }
.fin-courtier { background: white; border: 1px solid var(--border); border-radius: 10px; padding: 18px; }
.fin-courtier:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.fin-courtier-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.fin-courtier-logo { width: 42px; height: 42px; border-radius: 10px; background: var(--light); display: flex; align-items: center; justify-content: center; font-size: 18px; color: var(--primary); }
.fin-courtier-name { font-size: 14px; font-weight: 600; color: var(--text-primary); }
.fin-courtier-contact { font-size: 12px; color: var(--text-secondary); }
.fin-courtier-stats { display: flex; gap: 12px; padding: 12px 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); margin-bottom: 12px; }
.fin-courtier-stat { text-align: center; flex: 1; }
.fin-courtier-stat-val { font-size: 16px; font-weight: 700; color: var(--text-primary); }
.fin-courtier-stat-lbl { font-size: 10px; color: var(--text-secondary); text-transform: uppercase; }
.fin-courtier-actions { display: flex; gap: 8px; }
.fin-courtier-actions .btn { flex: 1; justify-content: center; }

.fin-commission { display: flex; justify-content: space-between; align-items: center; padding: 14px; background: var(--light); border-radius: 8px; margin-bottom: 10px; border: 1px solid var(--border); }
.fin-commission-info span { display: block; }
.fin-commission-lead { font-weight: 600; color: var(--text-primary); font-size: 13px; }
.fin-commission-date { font-size: 11px; color: var(--text-secondary); }
.fin-commission-amount { font-size: 18px; font-weight: 700; color: var(--success); }

.fin-empty { text-align: center; padding: 40px 20px; color: var(--text-secondary); }
.fin-empty i { font-size: 40px; margin-bottom: 12px; opacity: 0.3; }
.fin-empty h3 { color: var(--text-primary); margin-bottom: 6px; font-size: 15px; }
.fin-empty p { font-size: 12px; }

.fin-toast-container { position: fixed; top: 80px; right: 20px; z-index: 2000; }
.fin-toast { padding: 12px 16px; border-radius: 8px; color: white; font-weight: 500; font-size: 13px; display: flex; align-items: center; gap: 8px; margin-bottom: 8px; animation: slideIn 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.fin-toast.success { background: var(--success); }
.fin-toast.error { background: var(--danger); }
.fin-toast.warning { background: var(--warning); }
@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }

@media (max-width: 768px) {
    .fin-form-grid { grid-template-columns: 1fr; }
    .fin-form-group.full { grid-column: span 1; }
    .fin-pipeline { grid-template-columns: repeat(5, 220px); }
}
</style>

<div class="fin-header">
    <h2><i class="fas fa-hand-holding-usd"></i> Financement & Courtage</h2>
    <div class="fin-actions">
        <button class="btn btn-secondary" onclick="openCourtierModal()"><i class="fas fa-handshake"></i> Gérer courtiers</button>
        <button class="btn btn-success" onclick="openLeadModal()"><i class="fas fa-plus"></i> Nouveau lead</button>
    </div>
</div>

<div class="fin-stats">
    <div class="fin-stat">
        <div class="fin-stat-icon blue"><i class="fas fa-users"></i></div>
        <div class="fin-stat-value"><?= $stats_fin['total'] ?? 0 ?></div>
        <div class="fin-stat-label">Total leads</div>
    </div>
    <div class="fin-stat">
        <div class="fin-stat-icon cyan"><i class="fas fa-paper-plane"></i></div>
        <div class="fin-stat-value"><?= $stats_fin['transmis'] ?? 0 ?></div>
        <div class="fin-stat-label">Transmis</div>
    </div>
    <div class="fin-stat">
        <div class="fin-stat-icon orange"><i class="fas fa-spinner"></i></div>
        <div class="fin-stat-value"><?= $stats_fin['en_cours'] ?? 0 ?></div>
        <div class="fin-stat-label">En cours</div>
    </div>
    <div class="fin-stat">
        <div class="fin-stat-icon green"><i class="fas fa-check-circle"></i></div>
        <div class="fin-stat-value"><?= $stats_fin['finances'] ?? 0 ?></div>
        <div class="fin-stat-label">Financés</div>
    </div>
    <div class="fin-stat">
        <div class="fin-stat-icon purple"><i class="fas fa-euro-sign"></i></div>
        <div class="fin-stat-value"><?= number_format($stats_fin['total_commissions'] ?? 0, 0, ',', ' ') ?>€</div>
        <div class="fin-stat-label">Commissions</div>
    </div>
    <div class="fin-stat">
        <div class="fin-stat-icon red"><i class="fas fa-building"></i></div>
        <div class="fin-stat-value"><?= $stats_courtiers['total'] ?? 0 ?></div>
        <div class="fin-stat-label">Courtiers</div>
    </div>
</div>

<div class="fin-tabs">
    <button class="fin-tab active" data-tab="liste"><i class="fas fa-list"></i> Liste des leads</button>
    <button class="fin-tab" data-tab="pipeline"><i class="fas fa-columns"></i> Pipeline</button>
    <button class="fin-tab" data-tab="courtiers"><i class="fas fa-handshake"></i> Courtiers</button>
    <button class="fin-tab" data-tab="commissions"><i class="fas fa-euro-sign"></i> Commissions</button>
</div>

<div class="fin-content active" id="tab-liste">
    <div class="fin-card">
        <div class="fin-card-header">
            <span class="fin-card-title"><i class="fas fa-list"></i> Tous les leads financement</span>
            <button class="btn btn-sm btn-secondary"><i class="fas fa-download"></i> Exporter</button>
        </div>
        <div class="fin-card-body">
            <div class="fin-filters">
                <div class="fin-filter">
                    <label>Rechercher</label>
                    <input type="text" class="fin-input" id="search-lead" placeholder="Nom, email...">
                </div>
                <div class="fin-filter">
                    <label>Statut</label>
                    <select class="fin-select" id="filter-statut">
                        <option value="">Tous</option>
                        <option value="nouveau">Nouveau</option>
                        <option value="transmis">Transmis</option>
                        <option value="en_cours">En cours</option>
                        <option value="finance">Financé</option>
                        <option value="commission_percue">Commission perçue</option>
                    </select>
                </div>
                <div class="fin-filter">
                    <label>Courtier</label>
                    <select class="fin-select" id="filter-courtier">
                        <option value="">Tous</option>
                    </select>
                </div>
            </div>
            <div style="overflow-x: auto;">
                <table class="fin-table">
                    <thead>
                        <tr>
                            <th>Lead</th>
                            <th>Projet</th>
                            <th>Montant</th>
                            <th>Courtier</th>
                            <th>Statut</th>
                            <th>Commission</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="leads-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="fin-content" id="tab-pipeline">
    <div class="fin-pipeline">
        <div class="fin-col" data-status="nouveau">
            <div class="fin-col-header">
                <span class="fin-col-title" style="color:var(--primary);"><i class="fas fa-inbox"></i> Nouveau</span>
                <span class="fin-col-count" id="count-nouveau">0</span>
            </div>
            <div class="fin-col-body" id="pipeline-nouveau"></div>
        </div>
        <div class="fin-col" data-status="transmis">
            <div class="fin-col-header">
                <span class="fin-col-title" style="color:#06b6d4;"><i class="fas fa-paper-plane"></i> Transmis</span>
                <span class="fin-col-count" id="count-transmis">0</span>
            </div>
            <div class="fin-col-body" id="pipeline-transmis"></div>
        </div>
        <div class="fin-col" data-status="en_cours">
            <div class="fin-col-header">
                <span class="fin-col-title" style="color:var(--warning);"><i class="fas fa-spinner"></i> En cours</span>
                <span class="fin-col-count" id="count-en_cours">0</span>
            </div>
            <div class="fin-col-body" id="pipeline-en_cours"></div>
        </div>
        <div class="fin-col" data-status="finance">
            <div class="fin-col-header">
                <span class="fin-col-title" style="color:var(--success);"><i class="fas fa-check-circle"></i> Financé</span>
                <span class="fin-col-count" id="count-finance">0</span>
            </div>
            <div class="fin-col-body" id="pipeline-finance"></div>
        </div>
        <div class="fin-col" data-status="commission_percue">
            <div class="fin-col-header">
                <span class="fin-col-title" style="color:#059669;"><i class="fas fa-coins"></i> Perçue</span>
                <span class="fin-col-count" id="count-commission_percue">0</span>
            </div>
            <div class="fin-col-body" id="pipeline-commission_percue"></div>
        </div>
    </div>
</div>

<div class="fin-content" id="tab-courtiers">
    <div class="fin-card">
        <div class="fin-card-header">
            <span class="fin-card-title"><i class="fas fa-handshake"></i> Courtiers partenaires</span>
            <button class="btn btn-success" onclick="openCourtierModal()"><i class="fas fa-plus"></i> Ajouter</button>
        </div>
        <div class="fin-card-body">
            <div class="fin-courtiers" id="courtiers-grid"></div>
        </div>
    </div>
</div>

<div class="fin-content" id="tab-commissions">
    <div class="fin-card">
        <div class="fin-card-header">
            <span class="fin-card-title"><i class="fas fa-euro-sign"></i> Suivi des commissions</span>
        </div>
        <div class="fin-card-body" id="commissions-list"></div>
    </div>
</div>

<!-- Modal Lead -->
<div class="fin-modal-overlay" id="modal-lead">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <span class="fin-modal-title"><i class="fas fa-user-plus"></i> <span id="modal-lead-title">Nouveau lead</span></span>
            <button class="fin-modal-close" onclick="closeModal('modal-lead')">&times;</button>
        </div>
        <div class="fin-modal-body">
            <form id="form-lead">
                <input type="hidden" id="lead-id" name="id">
                <div class="fin-section"><i class="fas fa-user"></i> Contact</div>
                <div class="fin-form-grid">
                    <div class="fin-form-group"><label>Nom <span class="req">*</span></label><input type="text" class="fin-form-control" id="lead-nom" name="nom" required></div>
                    <div class="fin-form-group"><label>Prénom <span class="req">*</span></label><input type="text" class="fin-form-control" id="lead-prenom" name="prenom" required></div>
                    <div class="fin-form-group"><label>Email <span class="req">*</span></label><input type="email" class="fin-form-control" id="lead-email" name="email" required></div>
                    <div class="fin-form-group"><label>Téléphone <span class="req">*</span></label><input type="tel" class="fin-form-control" id="lead-telephone" name="telephone" required></div>
                </div>
                <div class="fin-section"><i class="fas fa-home"></i> Projet</div>
                <div class="fin-form-grid">
                    <div class="fin-form-group"><label>Type</label><select class="fin-form-control" id="lead-type-projet" name="type_projet"><option value="achat_residence">Résidence principale</option><option value="achat_investissement">Investissement</option><option value="rachat_credit">Rachat crédit</option><option value="construction">Construction</option><option value="travaux">Travaux</option></select></div>
                    <div class="fin-form-group"><label>Montant (€)</label><input type="number" class="fin-form-control" id="lead-montant-projet" name="montant_projet"></div>
                    <div class="fin-form-group"><label>Apport (€)</label><input type="number" class="fin-form-control" id="lead-apport" name="apport"></div>
                    <div class="fin-form-group"><label>Revenus (€)</label><input type="number" class="fin-form-control" id="lead-revenus" name="revenus"></div>
                </div>
                <div class="fin-section"><i class="fas fa-handshake"></i> Prescription</div>
                <div class="fin-form-grid">
                    <div class="fin-form-group"><label>Courtier</label><select class="fin-form-control" id="lead-courtier" name="courtier_id"><option value="">-- Sélectionner --</option></select></div>
                    <div class="fin-form-group"><label>Statut</label><select class="fin-form-control" id="lead-statut" name="statut"><option value="nouveau">Nouveau</option><option value="transmis">Transmis</option><option value="en_cours">En cours</option><option value="finance">Financé</option><option value="commission_percue">Commission perçue</option></select></div>
                    <div class="fin-form-group"><label>Commission (€)</label><input type="number" class="fin-form-control" id="lead-commission" name="commission_montant"></div>
                    <div class="fin-form-group"><label>Taux (%)</label><input type="number" class="fin-form-control" id="lead-taux-commission" name="taux_commission" step="0.1"></div>
                </div>
                <div class="fin-section"><i class="fas fa-sticky-note"></i> Notes</div>
                <div class="fin-form-group full"><textarea class="fin-form-control" id="lead-notes" name="notes" placeholder="Notes..."></textarea></div>
            </form>
        </div>
        <div class="fin-modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-lead')">Annuler</button>
            <button class="btn btn-success" onclick="saveLead()"><i class="fas fa-save"></i> Enregistrer</button>
        </div>
    </div>
</div>

<!-- Modal Courtier -->
<div class="fin-modal-overlay" id="modal-courtier">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <span class="fin-modal-title"><i class="fas fa-handshake"></i> <span id="modal-courtier-title">Nouveau courtier</span></span>
            <button class="fin-modal-close" onclick="closeModal('modal-courtier')">&times;</button>
        </div>
        <div class="fin-modal-body">
            <form id="form-courtier">
                <input type="hidden" id="courtier-id" name="id">
                <div class="fin-form-grid">
                    <div class="fin-form-group full"><label>Nom <span class="req">*</span></label><input type="text" class="fin-form-control" id="courtier-nom" name="nom" required></div>
                    <div class="fin-form-group"><label>Contact</label><input type="text" class="fin-form-control" id="courtier-contact" name="contact_nom"></div>
                    <div class="fin-form-group"><label>Email</label><input type="email" class="fin-form-control" id="courtier-email" name="email"></div>
                    <div class="fin-form-group"><label>Téléphone</label><input type="tel" class="fin-form-control" id="courtier-telephone" name="telephone"></div>
                    <div class="fin-form-group"><label>Taux commission (%)</label><input type="number" class="fin-form-control" id="courtier-taux" name="taux_commission" step="0.1" value="1"></div>
                    <div class="fin-form-group full"><label>Notes</label><textarea class="fin-form-control" id="courtier-notes" name="notes"></textarea></div>
                </div>
            </form>
        </div>
        <div class="fin-modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-courtier')">Annuler</button>
            <button class="btn btn-success" onclick="saveCourtier()"><i class="fas fa-save"></i> Enregistrer</button>
        </div>
    </div>
</div>

<!-- Modal Détail -->
<div class="fin-modal-overlay" id="modal-detail">
    <div class="fin-modal">
        <div class="fin-modal-header">
            <span class="fin-modal-title"><i class="fas fa-user"></i> <span id="modal-detail-title">Détail</span></span>
            <button class="fin-modal-close" onclick="closeModal('modal-detail')">&times;</button>
        </div>
        <div class="fin-modal-body" id="modal-detail-content"></div>
        <div class="fin-modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modal-detail')">Fermer</button>
            <button class="btn btn-primary" onclick="editCurrentLead()"><i class="fas fa-edit"></i> Modifier</button>
        </div>
    </div>
</div>

<div class="fin-toast-container" id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
let currentLeadId = null, leads = [], courtiers = [];

document.addEventListener('DOMContentLoaded', function() {
    initTabs();
    loadCourtiers();
    loadLeads();
});

function initTabs() {
    document.querySelectorAll('.fin-tab').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.fin-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.fin-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('tab-' + this.dataset.tab).classList.add('active');
            if (this.dataset.tab === 'pipeline') renderPipeline();
            if (this.dataset.tab === 'courtiers') renderCourtiers();
            if (this.dataset.tab === 'commissions') renderCommissions();
        });
    });
}

async function loadCourtiers() {
    try {
        const r = await fetch('/admin/modules/financement/api/courtiers.php');
        const d = await r.json();
        if (d.success) { courtiers = d.courtiers; populateCourtierSelects(); renderCourtiers(); }
    } catch (e) { console.error(e); }
}

async function loadLeads() {
    try {
        const r = await fetch('/admin/modules/financement/api/leads.php');
        const d = await r.json();
        if (d.success) { leads = d.leads; renderLeadsTable(); renderPipeline(); }
    } catch (e) { console.error(e); }
}

function renderLeadsTable() {
    const tbody = document.getElementById('leads-tbody');
    const search = document.getElementById('search-lead').value.toLowerCase();
    const statut = document.getElementById('filter-statut').value;
    const courtier = document.getElementById('filter-courtier').value;
    
    let filtered = leads.filter(l => {
        const ms = !search || l.nom.toLowerCase().includes(search) || l.prenom.toLowerCase().includes(search) || l.email.toLowerCase().includes(search);
        const mst = !statut || l.statut === statut;
        const mc = !courtier || l.courtier_id == courtier;
        return ms && mst && mc;
    });

    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="8"><div class="fin-empty"><i class="fas fa-inbox"></i><h3>Aucun lead</h3></div></td></tr>';
        return;
    }

    tbody.innerHTML = filtered.map(l => `
        <tr>
            <td><div class="lead-info"><span class="lead-name">${l.prenom} ${l.nom}</span><span class="lead-contact">${l.email}</span><span class="lead-contact">${l.telephone}</span></div></td>
            <td>${formatType(l.type_projet)}</td>
            <td><span class="montant">${formatMontant(l.montant_projet)}</span></td>
            <td>${l.courtier_nom ? `<span class="courtier-tag"><i class="fas fa-building"></i> ${l.courtier_nom}</span>` : '-'}</td>
            <td><span class="badge badge-${l.statut}">${formatStatut(l.statut)}</span></td>
            <td><span class="montant">${l.commission_montant ? formatMontant(l.commission_montant) : '-'}</span></td>
            <td>${formatDate(l.created_at)}</td>
            <td><div class="actions-cell">
                <button class="action-btn" onclick="viewLead(${l.id})"><i class="fas fa-eye"></i></button>
                <button class="action-btn" onclick="editLead(${l.id})"><i class="fas fa-edit"></i></button>
                ${l.statut === 'nouveau' ? `<button class="action-btn success" onclick="transmitLead(${l.id})"><i class="fas fa-paper-plane"></i></button>` : ''}
                <button class="action-btn delete" onclick="deleteLead(${l.id})"><i class="fas fa-trash"></i></button>
            </div></td>
        </tr>
    `).join('');
}

function renderPipeline() {
    ['nouveau', 'transmis', 'en_cours', 'finance', 'commission_percue'].forEach(s => {
        const cont = document.getElementById('pipeline-' + s);
        const count = document.getElementById('count-' + s);
        const ls = leads.filter(l => l.statut === s);
        count.textContent = ls.length;
        
        if (!ls.length) { cont.innerHTML = '<div class="fin-empty" style="padding:20px;"><i class="fas fa-inbox" style="font-size:20px;"></i><p style="font-size:11px;">Aucun</p></div>'; return; }
        
        cont.innerHTML = ls.map(l => `
            <div class="fin-pipe-card" onclick="viewLead(${l.id})" data-id="${l.id}">
                <div style="display:flex;justify-content:space-between;"><span class="fin-pipe-name">${l.prenom} ${l.nom}</span><span class="fin-pipe-amount">${formatMontant(l.montant_projet)}</span></div>
                <div class="fin-pipe-info">${l.telephone}</div>
                ${l.courtier_nom ? `<div class="fin-pipe-info" style="color:var(--primary);"><i class="fas fa-building"></i> ${l.courtier_nom}</div>` : ''}
            </div>
        `).join('');
        
        new Sortable(cont, { group: 'pipeline', animation: 150, onEnd: e => updateLeadStatus(e.item.dataset.id, e.to.closest('.fin-col').dataset.status) });
    });
}

function renderCourtiers() {
    const grid = document.getElementById('courtiers-grid');
    if (!courtiers.length) { grid.innerHTML = '<div class="fin-empty" style="grid-column:1/-1;"><i class="fas fa-handshake"></i><h3>Aucun courtier</h3><button class="btn btn-success" onclick="openCourtierModal()" style="margin-top:10px;"><i class="fas fa-plus"></i> Ajouter</button></div>'; return; }
    
    grid.innerHTML = courtiers.map(c => {
        const lc = leads.filter(l => l.courtier_id == c.id);
        const fin = lc.filter(l => l.statut === 'finance' || l.statut === 'commission_percue').length;
        const com = lc.reduce((s, l) => s + (parseFloat(l.commission_montant) || 0), 0);
        return `
            <div class="fin-courtier">
                <div class="fin-courtier-header">
                    <div class="fin-courtier-logo"><i class="fas fa-building"></i></div>
                    <div><div class="fin-courtier-name">${c.nom}</div><div class="fin-courtier-contact">${c.contact_nom || '-'}</div></div>
                </div>
                <div class="fin-courtier-stats">
                    <div class="fin-courtier-stat"><div class="fin-courtier-stat-val">${lc.length}</div><div class="fin-courtier-stat-lbl">Leads</div></div>
                    <div class="fin-courtier-stat"><div class="fin-courtier-stat-val">${fin}</div><div class="fin-courtier-stat-lbl">Financés</div></div>
                    <div class="fin-courtier-stat"><div class="fin-courtier-stat-val">${formatMontant(com)}</div><div class="fin-courtier-stat-lbl">Com.</div></div>
                </div>
                <div class="fin-courtier-actions">
                    <button class="btn btn-sm btn-secondary" onclick="editCourtier(${c.id})"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-danger" onclick="deleteCourtier(${c.id})"><i class="fas fa-trash"></i></button>
                </div>
            </div>
        `;
    }).join('');
}

function renderCommissions() {
    const cont = document.getElementById('commissions-list');
    const lc = leads.filter(l => l.statut === 'finance' || l.statut === 'commission_percue');
    if (!lc.length) { cont.innerHTML = '<div class="fin-empty"><i class="fas fa-euro-sign"></i><h3>Aucune commission</h3></div>'; return; }
    cont.innerHTML = lc.map(l => `
        <div class="fin-commission">
            <div class="fin-commission-info"><span class="fin-commission-lead">${l.prenom} ${l.nom}</span><span class="fin-commission-date">${l.courtier_nom || '-'} - ${formatDate(l.updated_at)}</span></div>
            <span class="badge badge-${l.statut}">${formatStatut(l.statut)}</span>
            <span class="fin-commission-amount">${formatMontant(l.commission_montant)}</span>
        </div>
    `).join('');
}

function populateCourtierSelects() {
    ['lead-courtier', 'filter-courtier'].forEach(id => {
        const s = document.getElementById(id); if (!s) return;
        const fo = s.options[0]; s.innerHTML = ''; s.appendChild(fo);
        courtiers.forEach(c => { const o = document.createElement('option'); o.value = c.id; o.textContent = c.nom; s.appendChild(o); });
    });
}

function openLeadModal(l = null) {
    document.getElementById('form-lead').reset();
    document.getElementById('lead-id').value = '';
    document.getElementById('modal-lead-title').textContent = l ? 'Modifier le lead' : 'Nouveau lead';
    if (l) {
        document.getElementById('lead-id').value = l.id;
        document.getElementById('lead-nom').value = l.nom;
        document.getElementById('lead-prenom').value = l.prenom;
        document.getElementById('lead-email').value = l.email;
        document.getElementById('lead-telephone').value = l.telephone;
        document.getElementById('lead-type-projet').value = l.type_projet || 'achat_residence';
        document.getElementById('lead-montant-projet').value = l.montant_projet;
        document.getElementById('lead-apport').value = l.apport;
        document.getElementById('lead-revenus').value = l.revenus;
        document.getElementById('lead-courtier').value = l.courtier_id || '';
        document.getElementById('lead-statut').value = l.statut;
        document.getElementById('lead-commission').value = l.commission_montant;
        document.getElementById('lead-taux-commission').value = l.taux_commission;
        document.getElementById('lead-notes').value = l.notes || '';
    }
    document.getElementById('modal-lead').classList.add('active');
}

function openCourtierModal(c = null) {
    document.getElementById('form-courtier').reset();
    document.getElementById('courtier-id').value = '';
    document.getElementById('modal-courtier-title').textContent = c ? 'Modifier' : 'Nouveau courtier';
    if (c) {
        document.getElementById('courtier-id').value = c.id;
        document.getElementById('courtier-nom').value = c.nom;
        document.getElementById('courtier-contact').value = c.contact_nom || '';
        document.getElementById('courtier-email').value = c.email || '';
        document.getElementById('courtier-telephone').value = c.telephone || '';
        document.getElementById('courtier-taux').value = c.taux_commission || 1;
        document.getElementById('courtier-notes').value = c.notes || '';
    }
    document.getElementById('modal-courtier').classList.add('active');
}

function closeModal(id) { document.getElementById(id).classList.remove('active'); }

async function saveLead() {
    const d = Object.fromEntries(new FormData(document.getElementById('form-lead')).entries());
    try {
        const r = await fetch('/admin/modules/financement/api/leads.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(d) });
        const res = await r.json();
        if (res.success) { showToast('Lead enregistré', 'success'); closeModal('modal-lead'); loadLeads(); }
        else showToast(res.message || 'Erreur', 'error');
    } catch (e) { showToast('Erreur', 'error'); }
}

function editLead(id) { const l = leads.find(x => x.id == id); if (l) openLeadModal(l); }

function viewLead(id) {
    currentLeadId = id;
    const l = leads.find(x => x.id == id); if (!l) return;
    document.getElementById('modal-detail-title').textContent = `${l.prenom} ${l.nom}`;
    document.getElementById('modal-detail-content').innerHTML = `
        <div class="fin-form-grid">
            <div class="fin-form-group"><label>Email</label><p style="margin:0;">${l.email}</p></div>
            <div class="fin-form-group"><label>Téléphone</label><p style="margin:0;">${l.telephone}</p></div>
            <div class="fin-form-group"><label>Projet</label><p style="margin:0;">${formatType(l.type_projet)}</p></div>
            <div class="fin-form-group"><label>Montant</label><p style="margin:0;color:var(--success);font-weight:700;">${formatMontant(l.montant_projet)}</p></div>
            <div class="fin-form-group"><label>Courtier</label><p style="margin:0;">${l.courtier_nom || '-'}</p></div>
            <div class="fin-form-group"><label>Statut</label><p style="margin:0;"><span class="badge badge-${l.statut}">${formatStatut(l.statut)}</span></p></div>
            <div class="fin-form-group"><label>Commission</label><p style="margin:0;color:var(--success);font-weight:700;">${l.commission_montant ? formatMontant(l.commission_montant) : '-'}</p></div>
            <div class="fin-form-group"><label>Date</label><p style="margin:0;">${formatDate(l.created_at)}</p></div>
        </div>
    `;
    document.getElementById('modal-detail').classList.add('active');
}

function editCurrentLead() { closeModal('modal-detail'); editLead(currentLeadId); }

async function deleteLead(id) {
    if (!confirm('Supprimer ce lead ?')) return;
    try {
        const r = await fetch('/admin/modules/financement/api/leads.php?id=' + id, { method: 'DELETE' });
        const res = await r.json();
        if (res.success) { showToast('Supprimé', 'success'); loadLeads(); }
    } catch (e) { showToast('Erreur', 'error'); }
}

async function transmitLead(id) {
    const l = leads.find(x => x.id == id);
    if (!l.courtier_id) { showToast('Assignez d\'abord un courtier', 'warning'); editLead(id); return; }
    if (confirm('Transmettre au courtier ?')) updateLeadStatus(id, 'transmis');
}

async function updateLeadStatus(id, statut) {
    try {
        const r = await fetch('/admin/modules/financement/api/leads.php', { method: 'PATCH', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id, statut }) });
        const res = await r.json();
        if (res.success) { showToast('Statut mis à jour', 'success'); loadLeads(); }
    } catch (e) { showToast('Erreur', 'error'); }
}

async function saveCourtier() {
    const d = Object.fromEntries(new FormData(document.getElementById('form-courtier')).entries());
    try {
        const r = await fetch('/admin/modules/financement/api/courtiers.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(d) });
        const res = await r.json();
        if (res.success) { showToast('Courtier enregistré', 'success'); closeModal('modal-courtier'); loadCourtiers(); }
    } catch (e) { showToast('Erreur', 'error'); }
}

function editCourtier(id) { const c = courtiers.find(x => x.id == id); if (c) openCourtierModal(c); }

async function deleteCourtier(id) {
    if (!confirm('Supprimer ce courtier ?')) return;
    try {
        const r = await fetch('/admin/modules/financement/api/courtiers.php?id=' + id, { method: 'DELETE' });
        const res = await r.json();
        if (res.success) { showToast('Supprimé', 'success'); loadCourtiers(); }
    } catch (e) { showToast('Erreur', 'error'); }
}

document.getElementById('search-lead')?.addEventListener('input', renderLeadsTable);
document.getElementById('filter-statut')?.addEventListener('change', renderLeadsTable);
document.getElementById('filter-courtier')?.addEventListener('change', renderLeadsTable);

function formatMontant(m) { return m ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(m) : '-'; }
function formatDate(d) { return d ? new Date(d).toLocaleDateString('fr-FR') : '-'; }
function formatStatut(s) { return { nouveau: 'Nouveau', transmis: 'Transmis', en_cours: 'En cours', finance: 'Financé', commission_percue: 'Perçue', perdu: 'Perdu' }[s] || s; }
function formatType(t) { return { achat_residence: 'Résidence', achat_investissement: 'Investissement', rachat_credit: 'Rachat', construction: 'Construction', travaux: 'Travaux' }[t] || t; }

function showToast(msg, type = 'info') {
    const t = document.createElement('div');
    t.className = 'fin-toast ' + type;
    t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${msg}`;
    document.getElementById('toast-container').appendChild(t);
    setTimeout(() => t.remove(), 3000);
}

document.querySelectorAll('.fin-modal-overlay').forEach(o => o.addEventListener('click', e => { if (e.target === o) o.classList.remove('active'); }));
</script>