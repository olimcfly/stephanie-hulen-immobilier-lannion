<?php
/**
 * Module Scraper GMB v5.0
 * - Onglets : Prospection | Campagnes
 * - Vue liste/grille toggle
 * - Badges visuels : en campagne (🟡) / converti CRM (🟢)
 * - Email : scraping auto + saisie manuelle + vérification SMTP/MX/regex
 * - Historique : relancer recherche, fusionner dans campagnes
 */

if (!defined('ADMIN_ROUTER')) {
    http_response_code(403); exit;
}

$apiBase = '/admin/api/gmb/gmb.php';
?>

<div class="gmb-module">

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- ONGLETS PRINCIPAUX                                                      -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div class="gmb-tabs-nav">
    <button class="gmb-tab-btn active" data-tab="prospection" onclick="gmbTab('prospection', this)">
        <i class="fas fa-search"></i> Prospection GMB
    </button>
    <button class="gmb-tab-btn" data-tab="campagnes" onclick="gmbTab('campagnes', this)">
        <i class="fas fa-bullhorn"></i> Campagnes
        <span class="gmb-badge" id="badge-campagnes">0</span>
    </button>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- ONGLET : PROSPECTION                                                    -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div id="tab-prospection" class="gmb-tab-content active">

    <!-- Banner stats -->
    <div class="gmb-stats-banner">
        <div class="gmb-stat"><span class="gmb-stat-val" id="stat-searches">0</span><span class="gmb-stat-lbl">Recherches</span></div>
        <div class="gmb-stat"><span class="gmb-stat-val" id="stat-total">0</span><span class="gmb-stat-lbl">Entreprises</span></div>
        <div class="gmb-stat"><span class="gmb-stat-val" id="stat-campaign">0</span><span class="gmb-stat-lbl">En campagne</span></div>
        <div class="gmb-stat"><span class="gmb-stat-val" id="stat-converted">0</span><span class="gmb-stat-lbl">Convertis CRM</span></div>
        <div class="gmb-stat"><span class="gmb-stat-val" id="stat-verified">0</span><span class="gmb-stat-lbl">Emails vérifiés</span></div>
        <div class="gmb-stat"><span class="gmb-stat-val" id="stat-rating">0</span><span class="gmb-stat-lbl">Note ≥ 4</span></div>
    </div>

    <!-- Formulaire de recherche -->
    <div class="gmb-search-card">
        <h3><i class="fas fa-map-marker-alt"></i> Nouvelle recherche</h3>
        <div class="gmb-search-row">
            <div class="gmb-field">
                <label>Type d'activité <span class="gmb-required">*</span></label>
                <input type="text" id="srch-query" placeholder="ex: courtier immobilier, diagnostiqueur…" />
            </div>
            <div class="gmb-field">
                <label>Zone de prospection <span class="gmb-required">*</span></label>
                <div class="gmb-location-wrap">
                    <select id="srch-location-select" onchange="gmbLocationSelect(this)">
                        <option value="">⏳ Chargement des secteurs…</option>
                    </select>
                    <input type="text" id="srch-location" placeholder="ou saisir une ville…" style="display:none" />
                    <button class="gmb-btn-icon" id="btn-location-toggle" onclick="gmbToggleLocationMode()" title="Saisie manuelle">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
                <div id="srch-location-tags" class="gmb-location-tags"></div>
            </div>
            <div class="gmb-field gmb-field-sm">
                <label>Rayon (km)</label>
                <select id="srch-radius">
                    <option value="3000">3 km</option>
                    <option value="5000" selected>5 km</option>
                    <option value="10000">10 km</option>
                    <option value="20000">20 km</option>
                    <option value="50000">50 km</option>
                </select>
            </div>
            <div class="gmb-field gmb-field-action">
                <label>&nbsp;</label>
                <button class="gmb-btn-primary" id="btn-search" onclick="gmbSearch()">
                    <i class="fas fa-search"></i> Rechercher
                </button>
            </div>
        </div>
        <div id="gmb-multi-search-bar" style="display:none" class="gmb-multi-bar">
            <i class="fas fa-layer-group"></i>
            <span id="gmb-multi-label">0 secteur(s) sélectionné(s)</span>
            <button class="gmb-btn-sm gmb-btn-primary" onclick="gmbSearchAllSelected()">
                <i class="fas fa-search"></i> Rechercher sur tous les secteurs
            </button>
            <button class="gmb-btn-ghost gmb-btn-sm" onclick="gmbClearSelectedSectors()">
                <i class="fas fa-times"></i> Vider
            </button>
        </div>
    </div>

    <!-- Barre d'actions résultats -->
    <div class="gmb-results-bar" id="results-bar" style="display:none">
        <div class="gmb-results-left">
            <input type="checkbox" id="chk-all" onchange="gmbToggleAll(this)" title="Tout sélectionner" />
            <span id="results-count-lbl">0 résultats</span>
            <div class="gmb-view-toggle">
                <button class="gmb-view-btn active" id="btn-view-list" onclick="gmbSetView('list')" title="Vue liste">
                    <i class="fas fa-list"></i>
                </button>
                <button class="gmb-view-btn" id="btn-view-grid" onclick="gmbSetView('grid')" title="Vue grille">
                    <i class="fas fa-th"></i>
                </button>
            </div>
        </div>
        <div class="gmb-results-right" id="bulk-actions" style="display:none">
            <button class="gmb-btn-sm gmb-btn-yellow" onclick="gmbBulkAddToCampaign()">
                <i class="fas fa-bullhorn"></i> Ajouter à campagne
            </button>
            <button class="gmb-btn-sm gmb-btn-blue" onclick="gmbBulkScrapeEmails()">
                <i class="fas fa-at"></i> Scraper emails
            </button>
            <button class="gmb-btn-sm gmb-btn-green" onclick="gmbBulkVerifyEmails()">
                <i class="fas fa-check-circle"></i> Vérifier emails
            </button>
            <button class="gmb-btn-sm gmb-btn-red" onclick="gmbBulkDelete()">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>
    </div>

    <!-- Historique des recherches -->
    <div class="gmb-section" id="searches-history" style="display:none">
        <div class="gmb-section-header">
            <h4><i class="fas fa-history"></i> Historique des recherches</h4>
            <button class="gmb-btn-ghost" onclick="gmbToggleHistory()">
                <i class="fas fa-chevron-down" id="history-chevron"></i>
            </button>
        </div>
        <div id="history-body" style="display:none">
            <table class="gmb-table gmb-table-history">
                <thead>
                    <tr>
                        <th>Recherche</th><th>Ville</th><th>Résultats</th><th>Date</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody id="history-rows"></tbody>
            </table>
        </div>
    </div>

    <!-- Zone de résultats -->
    <div id="gmb-results-zone">
        <div id="gmb-loading" style="display:none" class="gmb-loading">
            <div class="gmb-spinner"></div>
            <p>Recherche en cours…</p>
        </div>
        <div id="gmb-list-view">
            <table class="gmb-table" id="results-table" style="display:none">
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" id="chk-all2" onchange="gmbToggleAll(this)" /></th>
                        <th>Entreprise</th>
                        <th>Catégorie</th>
                        <th>Adresse</th>
                        <th>Téléphone</th>
                        <th>Email</th>
                        <th>Note</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="results-tbody"></tbody>
            </table>
        </div>
        <div id="gmb-grid-view" style="display:none">
            <div class="gmb-grid" id="results-grid"></div>
        </div>
        <div id="gmb-empty" style="display:none" class="gmb-empty">
            <i class="fas fa-search"></i>
            <p>Aucun résultat. Lancez une recherche.</p>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- ONGLET : CAMPAGNES                                                      -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<div id="tab-campagnes" class="gmb-tab-content" style="display:none">

    <div class="gmb-camp-header">
        <h3><i class="fas fa-bullhorn"></i> Groupes de prospection</h3>
        <button class="gmb-btn-primary" onclick="gmbShowCreateCampaign()">
            <i class="fas fa-plus"></i> Nouvelle campagne
        </button>
    </div>
    <p class="gmb-camp-info">
        <i class="fas fa-info-circle"></i>
        Les campagnes regroupent vos prospects <strong>non encore contactés</strong>.
        Une fois le contact établi, convertissez-les en leads CRM.
    </p>

    <div id="campaigns-grid" class="gmb-camps-grid"></div>
    <div id="campaigns-empty" class="gmb-empty" style="display:none">
        <i class="fas fa-bullhorn"></i>
        <p>Aucune campagne. Créez votre premier groupe de prospection.</p>
    </div>

    <!-- Détail campagne -->
    <div id="campaign-detail" style="display:none">
        <div class="gmb-camp-detail-header">
            <button class="gmb-btn-ghost" onclick="gmbBackToCampaigns()">
                <i class="fas fa-arrow-left"></i> Retour
            </button>
            <h3 id="camp-detail-name"></h3>
            <div class="gmb-camp-detail-actions">
                <button class="gmb-btn-sm gmb-btn-blue" onclick="gmbBulkVerifyInCampaign()">
                    <i class="fas fa-check-circle"></i> Vérifier tous les emails
                </button>
                <button class="gmb-btn-sm gmb-btn-green" onclick="gmbConvertCampaignToLeads()">
                    <i class="fas fa-user-plus"></i> Convertir sélection en leads
                </button>
            </div>
        </div>
        <table class="gmb-table">
            <thead>
                <tr>
                    <th width="30"><input type="checkbox" id="chk-camp-all" onchange="gmbToggleCampAll(this)" /></th>
                    <th>Entreprise</th><th>Téléphone</th><th>Email</th><th>Statut email</th><th>Actions</th>
                </tr>
            </thead>
            <tbody id="camp-detail-tbody"></tbody>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- MODALS                                                                  -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->

<!-- Modal : Ajouter à campagne -->
<div id="modal-add-campaign" class="gmb-modal" style="display:none">
    <div class="gmb-modal-box">
        <div class="gmb-modal-header">
            <h4>Ajouter à une campagne</h4>
            <button onclick="gmbCloseModal('modal-add-campaign')"><i class="fas fa-times"></i></button>
        </div>
        <div class="gmb-modal-body">
            <p id="modal-add-info" class="gmb-modal-info"></p>
            <label>Choisir une campagne</label>
            <select id="modal-camp-select">
                <option value="">— Sélectionner —</option>
            </select>
            <div class="gmb-modal-divider">ou</div>
            <label>Créer une nouvelle campagne</label>
            <input type="text" id="modal-camp-new-name" placeholder="Nom de la campagne…" />
            <input type="text" id="modal-camp-new-city" placeholder="Ville cible…" />
        </div>
        <div class="gmb-modal-footer">
            <button class="gmb-btn-ghost" onclick="gmbCloseModal('modal-add-campaign')">Annuler</button>
            <button class="gmb-btn-primary" onclick="gmbConfirmAddToCampaign()">
                <i class="fas fa-check"></i> Confirmer
            </button>
        </div>
    </div>
</div>

<!-- Modal : Créer campagne -->
<div id="modal-create-campaign" class="gmb-modal" style="display:none">
    <div class="gmb-modal-box">
        <div class="gmb-modal-header">
            <h4>Nouvelle campagne</h4>
            <button onclick="gmbCloseModal('modal-create-campaign')"><i class="fas fa-times"></i></button>
        </div>
        <div class="gmb-modal-body">
            <label>Nom <span class="gmb-required">*</span></label>
            <input type="text" id="new-camp-name" placeholder="ex: Courtiers Bordeaux Sud" />
            <label>Cible</label>
            <input type="text" id="new-camp-target" placeholder="ex: courtier immobilier" />
            <label>Ville</label>
            <input type="text" id="new-camp-city" placeholder="ex: Bordeaux" />
            <label>Notes</label>
            <textarea id="new-camp-notes" rows="3" placeholder="Objectifs, contexte…"></textarea>
        </div>
        <div class="gmb-modal-footer">
            <button class="gmb-btn-ghost" onclick="gmbCloseModal('modal-create-campaign')">Annuler</button>
            <button class="gmb-btn-primary" onclick="gmbCreateCampaign()">
                <i class="fas fa-plus"></i> Créer
            </button>
        </div>
    </div>
</div>

<!-- Modal : Saisie manuelle email -->
<div id="modal-email" class="gmb-modal" style="display:none">
    <div class="gmb-modal-box gmb-modal-sm">
        <div class="gmb-modal-header">
            <h4>Email du contact</h4>
            <button onclick="gmbCloseModal('modal-email')"><i class="fas fa-times"></i></button>
        </div>
        <div class="gmb-modal-body">
            <p id="modal-email-name" class="gmb-modal-info"></p>
            <label>Email</label>
            <input type="email" id="modal-email-input" placeholder="contact@exemple.fr" />
            <div id="modal-email-status" class="gmb-email-status-block" style="display:none"></div>
        </div>
        <div class="gmb-modal-footer">
            <button class="gmb-btn-ghost" onclick="gmbCloseModal('modal-email')">Annuler</button>
            <button class="gmb-btn-blue" onclick="gmbVerifyAndSaveEmail()">
                <i class="fas fa-shield-alt"></i> Vérifier &amp; Sauvegarder
            </button>
        </div>
    </div>
</div>

<!-- Modal : Envoi email -->
<div id="modal-send-email" class="gmb-modal" style="display:none">
    <div class="gmb-modal-box gmb-modal-lg">
        <div class="gmb-modal-header">
            <h4><i class="fas fa-paper-plane"></i> Envoyer un email à <span id="send-email-company"></span></h4>
            <button onclick="gmbCloseModal('modal-send-email')"><i class="fas fa-times"></i></button>
        </div>
        <div class="gmb-modal-body">

            <!-- Sélecteur de template -->
            <label>Modèle d'email</label>
            <div class="gmb-template-btns">
                <button class="gmb-tpl-btn active" data-tpl="guide_local" onclick="gmbSelectTemplate('guide_local', this)">
                    📖 Guide Local
                </button>
                <button class="gmb-tpl-btn" data-tpl="partenariat" onclick="gmbSelectTemplate('partenariat', this)">
                    🤝 Partenariat
                </button>
                <button class="gmb-tpl-btn" data-tpl="echange_lien" onclick="gmbSelectTemplate('echange_lien', this)">
                    🔗 Échange de lien
                </button>
                <button class="gmb-tpl-btn" data-tpl="libre" onclick="gmbSelectTemplate('libre', this)">
                    ✏️ Message libre
                </button>
            </div>

            <!-- Champs email -->
            <label>Destinataire</label>
            <input type="email" id="send-to-email" placeholder="email@contact.fr" readonly style="background:#f8f9fa" />

            <label>Objet <span class="gmb-required">*</span></label>
            <input type="text" id="send-subject" placeholder="Objet de l'email…" />

            <label>Message <span class="gmb-required">*</span></label>
            <div class="gmb-email-editor">
                <div class="gmb-email-toolbar">
                    <button onclick="gmbFmt('bold')" title="Gras"><b>B</b></button>
                    <button onclick="gmbFmt('italic')" title="Italique"><i>I</i></button>
                    <button onclick="gmbFmt('insertUnorderedList')" title="Liste">≡</button>
                    <span class="gmb-toolbar-sep"></span>
                    <span class="gmb-var-hint">Variables : <code>{{nom_entreprise}}</code> <code>{{prenom_contact}}</code> <code>{{ville}}</code> <code>{{note}}</code> <code>{{avis}}</code></span>
                </div>
                <div id="send-body" class="gmb-email-body" contenteditable="true"></div>
            </div>

            <!-- Aperçu personnalisé -->
            <div class="gmb-send-meta">
                <div class="gmb-send-meta-row">
                    <i class="fas fa-info-circle"></i>
                    <span id="send-meta-info">—</span>
                </div>
            </div>
        </div>
        <div class="gmb-modal-footer">
            <button class="gmb-btn-ghost" onclick="gmbCloseModal('modal-send-email')">Annuler</button>
            <button class="gmb-btn-blue gmb-btn-sm" onclick="gmbPreviewEmail()" style="padding:9px 16px">
                <i class="fas fa-eye"></i> Aperçu
            </button>
            <button class="gmb-btn-primary" id="btn-send-email" onclick="gmbSendEmail()">
                <i class="fas fa-paper-plane"></i> Envoyer
            </button>
        </div>
    </div>
</div>

<!-- Modal : Aperçu email -->
<div id="modal-preview-email" class="gmb-modal" style="display:none">
    <div class="gmb-modal-box gmb-modal-lg">
        <div class="gmb-modal-header">
            <h4><i class="fas fa-eye"></i> Aperçu de l'email</h4>
            <button onclick="gmbCloseModal('modal-preview-email')"><i class="fas fa-times"></i></button>
        </div>
        <div class="gmb-modal-body">
            <div class="gmb-preview-header">
                <div><strong>De :</strong> Eduardo De Sul &lt;contact@eduardo-desul-immobilier.fr&gt;</div>
                <div><strong>À :</strong> <span id="preview-to"></span></div>
                <div><strong>Objet :</strong> <span id="preview-subject"></span></div>
            </div>
            <div id="preview-body" class="gmb-preview-body"></div>
        </div>
        <div class="gmb-modal-footer">
            <button class="gmb-btn-ghost" onclick="gmbCloseModal('modal-preview-email')">Modifier</button>
            <button class="gmb-btn-primary" onclick="gmbSendEmail(true)">
                <i class="fas fa-paper-plane"></i> Confirmer l'envoi
            </button>
        </div>
    </div>
</div>

<!-- Toast notifications -->

<div id="gmb-toasts" class="gmb-toasts"></div>

</div><!-- /gmb-module -->

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- STYLES                                                                  -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<style>
:root {
    --gmb-primary: #1a4d7a;
    --gmb-gold: #d4a574;
    --gmb-bg: #f9f6f3;
    --gmb-green: #28a745;
    --gmb-yellow: #ffc107;
    --gmb-red: #dc3545;
    --gmb-blue: #17a2b8;
    --gmb-border: #e0d9d0;
    --gmb-radius: 12px;
    --gmb-shadow: 0 2px 12px rgba(26,77,122,.08);
}

.gmb-module { font-family: 'DM Sans', sans-serif; color: #333; }

/* Onglets */
.gmb-tabs-nav { display:flex; gap:4px; border-bottom:2px solid var(--gmb-border); margin-bottom:24px; }
.gmb-tab-btn { padding:10px 20px; border:none; background:none; cursor:pointer; font-size:14px; font-weight:500; color:#666; border-bottom:3px solid transparent; margin-bottom:-2px; transition:.2s; display:flex; align-items:center; gap:8px; }
.gmb-tab-btn.active { color:var(--gmb-primary); border-bottom-color:var(--gmb-primary); }
.gmb-tab-btn:hover:not(.active) { color:var(--gmb-primary); background:#f0f4f8; }
.gmb-badge { background:var(--gmb-gold); color:#fff; font-size:11px; font-weight:700; padding:2px 7px; border-radius:10px; min-width:18px; text-align:center; }

/* Stats banner */
.gmb-stats-banner { display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:24px; }
.gmb-stat { background:#fff; border:1px solid var(--gmb-border); border-radius:var(--gmb-radius); padding:16px; text-align:center; box-shadow:var(--gmb-shadow); }
.gmb-stat-val { display:block; font-size:28px; font-weight:700; color:var(--gmb-primary); font-family:'Playfair Display',serif; }
.gmb-stat-lbl { font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.5px; }

/* Formulaire recherche */
.gmb-search-card { background:#fff; border:1px solid var(--gmb-border); border-radius:var(--gmb-radius); padding:20px; margin-bottom:16px; box-shadow:var(--gmb-shadow); }
.gmb-search-card h3 { margin:0 0 16px; font-size:16px; color:var(--gmb-primary); display:flex; align-items:center; gap:8px; }
.gmb-search-row { display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap; }
.gmb-field { display:flex; flex-direction:column; gap:4px; flex:1; min-width:160px; }
.gmb-field-sm { flex:0 0 120px; }
.gmb-field-action { flex:0 0 auto; }
.gmb-field label { font-size:12px; font-weight:600; color:#555; }
.gmb-field input, .gmb-field select { padding:9px 12px; border:1px solid var(--gmb-border); border-radius:8px; font-size:14px; font-family:'DM Sans',sans-serif; outline:none; transition:.2s; }
.gmb-field input:focus, .gmb-field select:focus { border-color:var(--gmb-primary); box-shadow:0 0 0 3px rgba(26,77,122,.12); }
.gmb-required { color:var(--gmb-red); }

/* Boutons */
.gmb-btn-primary { background:var(--gmb-primary); color:#fff; border:none; padding:10px 20px; border-radius:8px; font-size:14px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px; transition:.2s; }
.gmb-btn-primary:hover { background:#153d62; }
.gmb-btn-ghost { background:none; border:1px solid var(--gmb-border); color:#555; padding:8px 16px; border-radius:8px; font-size:13px; cursor:pointer; display:flex; align-items:center; gap:6px; transition:.2s; }
.gmb-btn-ghost:hover { border-color:var(--gmb-primary); color:var(--gmb-primary); }
.gmb-btn-sm { padding:6px 12px; border:none; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:.2s; }
.gmb-btn-yellow { background:#fff3cd; color:#856404; } .gmb-btn-yellow:hover { background:#ffc107; color:#fff; }
.gmb-btn-blue   { background:#d1ecf1; color:#0c5460; } .gmb-btn-blue:hover   { background:var(--gmb-blue); color:#fff; }
.gmb-btn-green  { background:#d4edda; color:#155724; } .gmb-btn-green:hover  { background:var(--gmb-green); color:#fff; }
.gmb-btn-red    { background:#f8d7da; color:#721c24; } .gmb-btn-red:hover    { background:var(--gmb-red); color:#fff; }

/* Barre résultats */
.gmb-results-bar { display:flex; justify-content:space-between; align-items:center; padding:10px 16px; background:#fff; border:1px solid var(--gmb-border); border-radius:10px; margin-bottom:12px; }
.gmb-results-left { display:flex; align-items:center; gap:12px; font-size:13px; color:#555; }
.gmb-results-right { display:flex; gap:8px; flex-wrap:wrap; }
.gmb-view-toggle { display:flex; gap:2px; border:1px solid var(--gmb-border); border-radius:6px; overflow:hidden; }
.gmb-view-btn { background:none; border:none; padding:5px 10px; cursor:pointer; color:#888; transition:.2s; }
.gmb-view-btn.active { background:var(--gmb-primary); color:#fff; }

/* Tables */
.gmb-table { width:100%; border-collapse:collapse; background:#fff; border-radius:var(--gmb-radius); overflow:hidden; box-shadow:var(--gmb-shadow); }
.gmb-table thead { background:var(--gmb-primary); color:#fff; }
.gmb-table th { padding:10px 14px; font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:.4px; text-align:left; }
.gmb-table td { padding:10px 14px; font-size:13px; border-bottom:1px solid var(--gmb-bg); vertical-align:middle; }
.gmb-table tr:last-child td { border-bottom:none; }
.gmb-table tr:hover td { background:#f5f1ed; }
.gmb-table-history { margin-bottom:16px; }

/* Badges statut */
.gmb-badge-camp { background:#fff3cd; color:#856404; font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px; border:1px solid #ffc107; }
.gmb-badge-crm  { background:#d4edda; color:#155724; font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px; border:1px solid #28a745; }
.gmb-badge-new  { background:#cce5ff; color:#004085; font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px; }

/* Email status */
.gmb-email-status { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:600; padding:2px 7px; border-radius:10px; }
.email-valid   { background:#d4edda; color:#155724; }
.email-invalid { background:#f8d7da; color:#721c24; }
.email-generic { background:#fff3cd; color:#856404; }
.email-pending { background:#e2e3e5; color:#383d41; }
.email-unverified { background:#f8f9fa; color:#6c757d; border:1px dashed #aaa; }

/* Email cell */
.gmb-email-cell { display:flex; align-items:center; gap:6px; }
.gmb-email-text { font-size:12px; color:#333; max-width:140px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.gmb-btn-icon { background:none; border:none; cursor:pointer; padding:3px 6px; border-radius:4px; color:#888; font-size:13px; transition:.15s; }
.gmb-btn-icon:hover { background:#f0f0f0; color:var(--gmb-primary); }
.gmb-btn-icon.loading { color:var(--gmb-gold); animation:spin .8s linear infinite; }

/* Grille */
.gmb-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; }
.gmb-card { background:#fff; border:1px solid var(--gmb-border); border-radius:var(--gmb-radius); padding:16px; box-shadow:var(--gmb-shadow); transition:.2s; }
.gmb-card:hover { border-color:var(--gmb-gold); box-shadow:0 4px 20px rgba(212,165,116,.2); }
.gmb-card-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; }
.gmb-card-name { font-weight:700; font-size:14px; color:var(--gmb-primary); }
.gmb-card-cat  { font-size:11px; color:#888; margin-top:2px; }
.gmb-card-badges { display:flex; flex-direction:column; gap:3px; align-items:flex-end; }
.gmb-card-info { display:flex; flex-direction:column; gap:5px; font-size:12px; color:#555; margin-bottom:12px; }
.gmb-card-info span { display:flex; align-items:center; gap:6px; }
.gmb-card-info i { color:var(--gmb-gold); width:14px; }
.gmb-card-actions { display:flex; gap:6px; flex-wrap:wrap; }
.gmb-rating { display:inline-flex; align-items:center; gap:4px; font-size:12px; font-weight:600; color:#856404; }
.gmb-stars { color:#ffc107; letter-spacing:-1px; }

/* Campagnes */
.gmb-camps-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; }
.gmb-camp-card { background:#fff; border:1px solid var(--gmb-border); border-radius:var(--gmb-radius); padding:18px; cursor:pointer; transition:.2s; }
.gmb-camp-card:hover { border-color:var(--gmb-primary); box-shadow:0 4px 20px rgba(26,77,122,.1); }
.gmb-camp-card-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; }
.gmb-camp-status { font-size:10px; font-weight:700; padding:3px 9px; border-radius:10px; text-transform:uppercase; }
.status-draft   { background:#e2e3e5; color:#383d41; }
.status-active  { background:#d4edda; color:#155724; }
.status-paused  { background:#fff3cd; color:#856404; }
.status-done    { background:#d1ecf1; color:#0c5460; }
.gmb-camp-name  { font-weight:700; font-size:15px; color:var(--gmb-primary); }
.gmb-camp-meta  { font-size:12px; color:#888; margin-bottom:12px; }
.gmb-camp-stats { display:flex; gap:16px; font-size:12px; margin-bottom:14px; }
.gmb-camp-stats span { display:flex; align-items:center; gap:5px; color:#555; }
.gmb-camp-stats i { color:var(--gmb-gold); }
.gmb-camp-actions { display:flex; gap:6px; }

.gmb-camp-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
.gmb-camp-header h3 { margin:0; color:var(--gmb-primary); }
.gmb-camp-info { background:#e8f4f8; border:1px solid #bee5eb; border-radius:8px; padding:10px 14px; font-size:13px; color:#0c5460; margin-bottom:16px; }
.gmb-camp-detail-header { display:flex; align-items:center; gap:16px; margin-bottom:16px; flex-wrap:wrap; }
.gmb-camp-detail-header h3 { margin:0; flex:1; color:var(--gmb-primary); }
.gmb-camp-detail-actions { display:flex; gap:8px; }

/* Historique */
.gmb-section { background:#fff; border:1px solid var(--gmb-border); border-radius:var(--gmb-radius); margin-bottom:16px; overflow:hidden; }
.gmb-section-header { display:flex; justify-content:space-between; align-items:center; padding:12px 16px; background:#f8f5f0; cursor:pointer; }
.gmb-section-header h4 { margin:0; font-size:14px; color:var(--gmb-primary); display:flex; align-items:center; gap:8px; }

/* Modals */
.gmb-modal { position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:9999; display:flex; align-items:center; justify-content:center; }
.gmb-modal-box { background:#fff; border-radius:var(--gmb-radius); width:480px; max-width:95vw; box-shadow:0 20px 60px rgba(0,0,0,.2); }
.gmb-modal-sm .gmb-modal-box { width:380px; }
.gmb-modal-header { display:flex; justify-content:space-between; align-items:center; padding:16px 20px; border-bottom:1px solid var(--gmb-border); }
.gmb-modal-header h4 { margin:0; font-size:16px; color:var(--gmb-primary); }
.gmb-modal-header button { background:none; border:none; cursor:pointer; font-size:16px; color:#888; }
.gmb-modal-body { padding:20px; display:flex; flex-direction:column; gap:10px; }
.gmb-modal-body label { font-size:12px; font-weight:600; color:#555; }
.gmb-modal-body input, .gmb-modal-body select, .gmb-modal-body textarea { padding:9px 12px; border:1px solid var(--gmb-border); border-radius:8px; font-size:13px; font-family:'DM Sans',sans-serif; outline:none; }
.gmb-modal-body input:focus, .gmb-modal-body select:focus { border-color:var(--gmb-primary); }
.gmb-modal-divider { text-align:center; font-size:12px; color:#aaa; position:relative; }
.gmb-modal-divider::before, .gmb-modal-divider::after { content:''; position:absolute; top:50%; width:40%; height:1px; background:var(--gmb-border); }
.gmb-modal-divider::before { left:0; } .gmb-modal-divider::after { right:0; }
.gmb-modal-footer { display:flex; justify-content:flex-end; gap:8px; padding:16px 20px; border-top:1px solid var(--gmb-border); }
.gmb-modal-info { background:var(--gmb-bg); border-radius:8px; padding:8px 12px; font-size:13px; color:#555; margin:0; }
.gmb-email-status-block { background:var(--gmb-bg); border-radius:8px; padding:10px 12px; font-size:13px; }

.gmb-modal-lg .gmb-modal-box { width: 680px; }
.gmb-template-btns { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:4px; }
.gmb-tpl-btn { padding:7px 14px; border:2px solid var(--gmb-border); border-radius:8px; background:#fff; cursor:pointer; font-size:13px; font-weight:500; transition:.2s; }
.gmb-tpl-btn.active { border-color:var(--gmb-primary); background:#f0f4f8; color:var(--gmb-primary); font-weight:700; }
.gmb-tpl-btn:hover:not(.active) { border-color:var(--gmb-gold); }
.gmb-email-editor { border:1px solid var(--gmb-border); border-radius:8px; overflow:hidden; }
.gmb-email-toolbar { display:flex; align-items:center; gap:4px; padding:6px 10px; background:#f8f5f0; border-bottom:1px solid var(--gmb-border); flex-wrap:wrap; }
.gmb-email-toolbar button { background:none; border:1px solid var(--gmb-border); border-radius:4px; padding:3px 8px; cursor:pointer; font-size:13px; }
.gmb-email-toolbar button:hover { background:var(--gmb-primary); color:#fff; }
.gmb-toolbar-sep { width:1px; height:20px; background:var(--gmb-border); margin:0 4px; }
.gmb-var-hint { font-size:11px; color:#888; margin-left:4px; }
.gmb-var-hint code { background:#e8f4f8; padding:1px 5px; border-radius:3px; color:#0c5460; }
.gmb-email-body { min-height:220px; padding:14px; font-size:13px; line-height:1.7; outline:none; color:#333; }
.gmb-email-body:focus { background:#fafff8; }
.gmb-send-meta { margin-top:8px; }
.gmb-send-meta-row { display:flex; align-items:center; gap:8px; font-size:12px; color:#888; padding:6px 10px; background:#f8f5f0; border-radius:6px; }
.gmb-preview-header { background:#f8f5f0; border-radius:8px; padding:12px 16px; margin-bottom:16px; font-size:13px; display:flex; flex-direction:column; gap:4px; color:#555; }
.gmb-preview-body { border:1px solid var(--gmb-border); border-radius:8px; padding:20px; font-size:14px; line-height:1.8; color:#333; min-height:200px; }


.gmb-location-wrap { display:flex; gap:6px; align-items:center; }
.gmb-location-wrap select, .gmb-location-wrap input { flex:1; }
.gmb-location-tags { display:flex; flex-wrap:wrap; gap:5px; margin-top:6px; min-height:0; }
.gmb-loc-tag { display:inline-flex; align-items:center; gap:5px; background:var(--gmb-primary); color:#fff; font-size:11px; font-weight:600; padding:3px 10px; border-radius:12px; }
.gmb-loc-tag button { background:none; border:none; color:#fff; cursor:pointer; font-size:11px; padding:0; line-height:1; opacity:.8; }
.gmb-loc-tag button:hover { opacity:1; }
.gmb-multi-bar { display:flex; align-items:center; gap:10px; margin-top:12px; padding:10px 14px; background:#e8f4f8; border:1px solid #bee5eb; border-radius:8px; font-size:13px; color:#0c5460; flex-wrap:wrap; }


.gmb-loading { text-align:center; padding:40px; color:#888; }
.gmb-spinner { width:36px; height:36px; border:3px solid var(--gmb-border); border-top-color:var(--gmb-primary); border-radius:50%; animation:spin .8s linear infinite; margin:0 auto 12px; }
@keyframes spin { to { transform:rotate(360deg); } }
.gmb-empty { text-align:center; padding:60px 20px; color:#bbb; }
.gmb-empty i { font-size:40px; margin-bottom:12px; display:block; }

/* Toasts */
.gmb-toasts { position:fixed; bottom:20px; right:20px; z-index:99999; display:flex; flex-direction:column; gap:8px; }
.gmb-toast { background:#333; color:#fff; padding:12px 18px; border-radius:10px; font-size:13px; box-shadow:0 4px 16px rgba(0,0,0,.2); animation:slideIn .3s ease; display:flex; align-items:center; gap:8px; min-width:220px; }
.gmb-toast.success { background:var(--gmb-green); }
.gmb-toast.error   { background:var(--gmb-red); }
.gmb-toast.warning { background:#856404; }
@keyframes slideIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }

@media(max-width:900px) {
    .gmb-stats-banner { grid-template-columns:repeat(3,1fr); }
    .gmb-search-row { flex-direction:column; }
    .gmb-field-sm, .gmb-field-action { flex:1; }
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════════ -->
<!-- JAVASCRIPT                                                              -->
<!-- ═══════════════════════════════════════════════════════════════════════ -->
<script>
const GMB_API = '<?= $apiBase ?>';
let gmbView = localStorage.getItem('gmb_view') || 'list';
let gmbResults = [];
let gmbCampaigns = [];
let gmbSecteurs = [];
let gmbSelectedSectors = []; // secteurs cochés pour recherche multi
let gmbLocationMode = 'select'; // 'select' | 'manual'
let gmbSelectedIds = new Set();
let gmbCurrentCampaignId = null;
let gmbCurrentEmailResultId = null;
let gmbAddCampaignIds = [];
let gmbCurrentSearchId = null;

let gmbCurrentEmailSendId = null;
let gmbCurrentEmailSendData = null;

// ── Templates email ───────────────────────────────────────────────────────────
const GMB_TEMPLATES = {
    guide_local: {
        subject: '{{nom_entreprise}} dans notre Guide Local {{ville}} ?',
        body: `<p>Bonjour {{prenom_contact}},</p>

<p>Je suis Eduardo De Sul, conseiller immobilier indépendant basé à Bordeaux/Blanquefort.</p>

<p>Je constitue actuellement un <strong>Guide Local des Professionnels de Confiance</strong> à destination de mes clients acheteurs et vendeurs sur {{ville}} et ses alentours.</p>

<p>Avec votre note de <strong>{{note}}/5</strong> et vos <strong>{{avis}} avis Google</strong>, {{nom_entreprise}} a toute sa place dans ce guide.</p>

<p><strong>Ce que ça vous apporte :</strong></p>
<ul>
  <li>📖 Mise en avant auprès de personnes qui s'installent sur {{ville}}</li>
  <li>🔗 Un lien depuis mon site web vers le vôtre (bon pour votre référencement local)</li>
  <li>⭐ Votre note Google et vos coordonnées visibles</li>
  <li>🏠 Une recommandation personnelle à chacun de mes clients</li>
</ul>

<p><strong>C'est entièrement gratuit.</strong> En contrepartie, si un de vos clients évoque un projet immobilier, pensez simplement à me mentionner. 🤝</p>

<p>Seriez-vous intéressé(e) ?</p>

<p>Bien cordialement,<br>
<strong>Eduardo De Sul</strong><br>
Conseiller immobilier — eXp France<br>
📞 06 24 10 58 16 | 📍 Blanquefort (33)</p>`
    },
    partenariat: {
        subject: 'Partenariat local {{ville}} — {{nom_entreprise}} & Eduardo De Sul',
        body: `<p>Bonjour {{prenom_contact}},</p>

<p>Je suis Eduardo De Sul, conseiller immobilier indépendant sur {{ville}} et ses alentours (eXp France).</p>

<p>En tant que professionnel(le) reconnu(e) dans votre domaine, {{nom_entreprise}} correspond exactement au profil de partenaires locaux que je recherche.</p>

<p><strong>Mon constat :</strong> mes clients acheteurs et vendeurs ont systématiquement besoin de professionnels complémentaires — et je n'ai pas toujours de bonne adresse à leur recommander.</p>

<p><strong>Ce que je propose :</strong></p>
<ul>
  <li>🏠 Je recommande {{nom_entreprise}} à mes clients qui ont besoin de vos services</li>
  <li>🔗 Je vous mets en avant sur mon site et mes supports</li>
  <li>📩 En retour, vous pensez à moi quand un de vos clients parle d'un projet immobilier</li>
</ul>

<p>Un partenariat simple, local, <strong>gagnant-gagnant</strong>.</p>

<p>Seriez-vous ouvert(e) à un échange rapide pour en discuter ?</p>

<p>Cordialement,<br>
<strong>Eduardo De Sul</strong><br>
Conseiller immobilier — eXp France<br>
📞 06 24 10 58 16 | 📍 Blanquefort (33)</p>`
    },
    echange_lien: {
        subject: 'Échange de visibilité locale — {{nom_entreprise}} & mon site immobilier',
        body: `<p>Bonjour {{prenom_contact}},</p>

<p>Je suis Eduardo De Sul, conseiller immobilier indépendant à Blanquefort, spécialisé sur le secteur de {{ville}}.</p>

<p>Je développe actuellement mon site <strong>eduardo-desul-immobilier.fr</strong>, dédié aux habitants et futurs habitants de l'agglomération bordelaise.</p>

<p>Je souhaite y créer une page "Partenaires de confiance" qui référence les professionnels locaux sérieux comme {{nom_entreprise}}.</p>

<p><strong>Concrètement :</strong></p>
<ul>
  <li>🔗 Un lien depuis mon site vers le vôtre — bon pour votre SEO local</li>
  <li>📍 Votre fiche complète (coordonnées, spécialité, zone) visible par mes visiteurs</li>
  <li>🤝 Si vous le souhaitez, un lien retour depuis votre site vers le mien</li>
</ul>

<p>Cela ne vous engage à rien de contraignant, et c'est bénéfique pour votre visibilité locale sur Google.</p>

<p>Intéressé(e) ?</p>

<p>Bien à vous,<br>
<strong>Eduardo De Sul</strong><br>
Conseiller immobilier — eXp France<br>
📞 06 24 10 58 16</p>`
    },
    libre: {
        subject: '',
        body: `<p>Bonjour {{prenom_contact}},</p>

<p></p>

<p>Cordialement,<br>
<strong>Eduardo De Sul</strong><br>
Conseiller immobilier — eXp France<br>
📞 06 24 10 58 16</p>`
    }
};


function gmbApi(params) {
    return fetch(GMB_API, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(params)
    }).then(r => r.json());
}

function gmbToast(msg, type = 'success', dur = 3500) {
    const el = document.createElement('div');
    el.className = `gmb-toast ${type}`;
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', warning: 'fa-exclamation-triangle' };
    el.innerHTML = `<i class="fas ${icons[type]||'fa-info-circle'}"></i> ${msg}`;
    document.getElementById('gmb-toasts').appendChild(el);
    setTimeout(() => el.remove(), dur);
}

function gmbConfirm(msg) { return window.confirm(msg); }

function gmbStars(rating) {
    if (!rating) return '—';
    const full = Math.round(rating);
    return '★'.repeat(full) + '☆'.repeat(5-full);
}

function gmbEmailStatusBadge(status, email) {
    if (!email) return '<span class="gmb-email-status email-unverified">—</span>';
    const map = {
        valid:     ['email-valid',   '<i class="fas fa-check"></i> Valide'],
        invalid:   ['email-invalid', '<i class="fas fa-times"></i> Invalide'],
        generic:   ['email-generic', '<i class="fas fa-exclamation"></i> Générique'],
        smtp_fail: ['email-invalid', '<i class="fas fa-times"></i> SMTP fail'],
        mx_fail:   ['email-invalid', '<i class="fas fa-times"></i> MX fail'],
        unverified:['email-unverified','<i class="fas fa-question"></i> Non vérifié'],
        pending:   ['email-pending', '<i class="fas fa-clock"></i> En cours'],
    };
    const [cls, lbl] = map[status] || map.unverified;
    return `<span class="gmb-email-status ${cls}">${lbl}</span>`;
}

function gmbStatusBadges(r) {
    let html = '';
    if (r.is_converted == 1) html += '<span class="gmb-badge-crm">✅ CRM</span>';
    else if (r.in_campaign)  html += '<span class="gmb-badge-camp">📋 Campagne</span>';
    else                     html += '<span class="gmb-badge-new">Nouveau</span>';
    return html;
}

// ── Secteurs ─────────────────────────────────────────────────────────────────
async function gmbLoadSecteurs() {
    const data = await gmbApi({ action: 'get_secteurs' }).catch(() => ({}));
    gmbSecteurs = data.secteurs || [];
    const sel = document.getElementById('srch-location-select');

    if (!gmbSecteurs.length) {
        sel.innerHTML = '<option value="">Aucun secteur — saisie manuelle</option>';
        gmbToggleLocationMode(); // bascule auto en mode manuel
        return;
    }

    // Grouper par ville
    const byVille = {};
    gmbSecteurs.forEach(s => {
        const v = s.ville || 'Autres';
        if (!byVille[v]) byVille[v] = [];
        byVille[v].push(s);
    });

    let html = '<option value="">— Choisir un secteur —</option>';
    Object.entries(byVille).forEach(([ville, secteurs]) => {
        html += `<optgroup label="📍 ${ville}">`;
        secteurs.forEach(s => {
            const label = s.nom + (s.type_secteur === 'commune' ? ' 🏙️' : '');
            html += `<option value="${s.ville || s.nom}" data-id="${s.id}" data-nom="${s.nom}" data-type="${s.type_secteur||'quartier'}">${label}</option>`;
        });
        html += '</optgroup>';
    });
    html += '<option value="__manual__">✏️ Saisir une ville manuellement…</option>';
    sel.innerHTML = html;
}

function gmbLocationSelect(sel) {
    const val = sel.value;
    if (val === '__manual__') {
        gmbToggleLocationMode();
        sel.value = '';
        return;
    }
    if (!val) return;

    const opt = sel.options[sel.selectedIndex];
    const nom = opt.dataset.nom || val;
    const ville = val;

    // Ajouter comme tag si pas déjà présent
    if (!gmbSelectedSectors.find(s => s.ville === ville)) {
        gmbSelectedSectors.push({ ville, nom });
        gmbRenderLocationTags();
    }
    sel.value = '';
}

function gmbRenderLocationTags() {
    const container = document.getElementById('srch-location-tags');
    const multiBar  = document.getElementById('gmb-multi-search-bar');
    const lbl       = document.getElementById('gmb-multi-label');

    container.innerHTML = gmbSelectedSectors.map((s, i) => `
        <span class="gmb-loc-tag">
            <i class="fas fa-map-marker-alt"></i> ${s.nom}
            <button onclick="gmbRemoveSector(${i})">×</button>
        </span>
    `).join('');

    const n = gmbSelectedSectors.length;
    multiBar.style.display = n > 1 ? 'flex' : 'none';
    if (n > 0) lbl.textContent = `${n} secteur(s) sélectionné(s)`;
}

function gmbRemoveSector(i) {
    gmbSelectedSectors.splice(i, 1);
    gmbRenderLocationTags();
}

function gmbClearSelectedSectors() {
    gmbSelectedSectors = [];
    gmbRenderLocationTags();
}

function gmbToggleLocationMode() {
    const sel   = document.getElementById('srch-location-select');
    const input = document.getElementById('srch-location');
    const btn   = document.getElementById('btn-location-toggle');
    gmbLocationMode = gmbLocationMode === 'select' ? 'manual' : 'select';
    const isManual = gmbLocationMode === 'manual';
    sel.style.display   = isManual ? 'none' : '';
    input.style.display = isManual ? '' : 'none';
    btn.title = isManual ? 'Retour aux secteurs' : 'Saisie manuelle';
    btn.querySelector('i').className = isManual ? 'fas fa-list' : 'fas fa-edit';
}

// Recherche sur tous les secteurs sélectionnés en séquence
async function gmbSearchAllSelected() {
    if (!gmbSelectedSectors.length) return;
    const query = document.getElementById('srch-query').value.trim();
    if (!query) { gmbToast('Entrez un type d\'activité', 'warning'); return; }

    gmbToast(`Recherche sur ${gmbSelectedSectors.length} secteurs…`);
    let totalResults = [];

    for (const s of gmbSelectedSectors) {
        document.getElementById('results-count-lbl').textContent = `Recherche sur ${s.nom}…`;
        const data = await gmbApi({
            action: 'search',
            query,
            location: s.ville,
            radius: parseInt(document.getElementById('srch-radius').value)
        }).catch(() => ({ success: false }));

        if (data.success && data.results) {
            totalResults = totalResults.concat(data.results);
        }
        await new Promise(r => setTimeout(r, 500));
    }

    gmbResults = totalResults;
    gmbCurrentSearchId = null;
    gmbRenderList(gmbResults);
    gmbRenderGrid(gmbResults);
    gmbSetView(gmbView);
    document.getElementById('results-bar').style.display = 'flex';
    document.getElementById('results-count-lbl').textContent = `${gmbResults.length} résultat(s) — ${gmbSelectedSectors.length} secteurs`;
    gmbLoadHistory();
    gmbToast(`${gmbResults.length} entreprises sur ${gmbSelectedSectors.length} secteurs`, 'success');
}


function gmbTab(tab, btn) {
    document.querySelectorAll('.gmb-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.gmb-tab-content').forEach(t => t.style.display = 'none');
    btn.classList.add('active');
    document.getElementById('tab-' + tab).style.display = '';
    if (tab === 'campagnes') gmbLoadCampaigns();
}

// ── Vue liste/grille ─────────────────────────────────────────────────────────
function gmbSetView(v) {
    gmbView = v;
    localStorage.setItem('gmb_view', v);
    document.getElementById('btn-view-list').classList.toggle('active', v === 'list');
    document.getElementById('btn-view-grid').classList.toggle('active', v === 'grid');
    document.getElementById('gmb-list-view').style.display = v === 'list' ? '' : 'none';
    document.getElementById('gmb-grid-view').style.display  = v === 'grid' ? '' : 'none';
}

// ── Sélection bulk ───────────────────────────────────────────────────────────
function gmbToggleAll(chk) {
    document.querySelectorAll('.gmb-row-chk').forEach(c => {
        c.checked = chk.checked;
        const id = parseInt(c.dataset.id);
        chk.checked ? gmbSelectedIds.add(id) : gmbSelectedIds.delete(id);
    });
    document.getElementById('chk-all').checked  = chk.checked;
    document.getElementById('chk-all2').checked = chk.checked;
    gmbUpdateBulkBar();
}

function gmbRowCheck(chk) {
    const id = parseInt(chk.dataset.id);
    chk.checked ? gmbSelectedIds.add(id) : gmbSelectedIds.delete(id);
    gmbUpdateBulkBar();
}

function gmbUpdateBulkBar() {
    const n = gmbSelectedIds.size;
    document.getElementById('bulk-actions').style.display = n > 0 ? 'flex' : 'none';
}

// ── Recherche ────────────────────────────────────────────────────────────────
async function gmbSearch(reuseSearchId = null) {
    const query = reuseSearchId ? null : document.getElementById('srch-query').value.trim();

    // Déterminer la location selon le mode
    let location = '';
    if (!reuseSearchId) {
        if (gmbLocationMode === 'manual') {
            location = document.getElementById('srch-location').value.trim();
        } else if (gmbSelectedSectors.length === 1) {
            location = gmbSelectedSectors[0].ville;
        } else if (gmbSelectedSectors.length > 1) {
            // Lancer la recherche multi-secteurs
            gmbSearchAllSelected(); return;
        }
        if (!location) { gmbToast('Choisissez un secteur ou saisissez une ville', 'warning'); return; }
    }

    if (!reuseSearchId && !query) {
        gmbToast('Veuillez remplir le type d\'activité.', 'warning'); return;
    }

    const radius = parseInt(document.getElementById('srch-radius').value);
    document.getElementById('gmb-loading').style.display = '';
    document.getElementById('results-table').style.display = 'none';
    document.getElementById('gmb-grid-view').style.display = 'none';
    document.getElementById('gmb-empty').style.display = 'none';
    document.getElementById('results-bar').style.display = 'none';
    document.getElementById('btn-search').disabled = true;

    const payload = reuseSearchId
        ? { action: 'search', reuse_search_id: reuseSearchId }
        : { action: 'search', query, location, radius };

    try {
        const data = await gmbApi(payload);
        if (!data.success) { gmbToast(data.error || 'Erreur recherche', 'error'); return; }

        gmbResults = data.results || [];
        gmbCurrentSearchId = data.search_id || null;
        gmbSelectedIds.clear();

        if (gmbResults.length === 0) {
            document.getElementById('gmb-empty').style.display = '';
        } else {
            document.getElementById('results-bar').style.display = 'flex';
            document.getElementById('results-count-lbl').textContent = `${gmbResults.length} résultat(s)`;
            gmbRenderList(gmbResults);
            gmbRenderGrid(gmbResults);
            gmbSetView(gmbView);
        }
        gmbUpdateStats(data);
        gmbLoadHistory();
        gmbToast(`${gmbResults.length} entreprises trouvées`);
    } catch(e) {
        gmbToast('Erreur réseau : ' + e.message, 'error');
    } finally {
        document.getElementById('gmb-loading').style.display = 'none';
        document.getElementById('btn-search').disabled = false;
    }
}

// ── Rendu liste ───────────────────────────────────────────────────────────────
function gmbRenderList(results) {
    const tbody = document.getElementById('results-tbody');
    tbody.innerHTML = results.map(r => `
        <tr id="row-${r.id}">
            <td><input type="checkbox" class="gmb-row-chk" data-id="${r.id}" onchange="gmbRowCheck(this)" ${gmbSelectedIds.has(r.id)?'checked':''}></td>
            <td>
                <div style="font-weight:600;font-size:13px;color:var(--gmb-primary)">${r.name||'—'}</div>
                <div style="display:flex;gap:4px;margin-top:3px">${gmbStatusBadges(r)}</div>
            </td>
            <td style="font-size:12px;color:#888">${r.category||'—'}</td>
            <td style="font-size:12px">${r.address||'—'}</td>
            <td style="font-size:12px">${r.phone ? `<a href="tel:${r.phone}" style="color:var(--gmb-primary)">${r.phone}</a>` : '—'}</td>
            <td>${gmbEmailCell(r)}</td>
            <td>
                ${r.rating ? `<span class="gmb-rating"><span class="gmb-stars">${gmbStars(r.rating)}</span>${r.rating}</span>` : '—'}
            </td>
            <td>
                <div style="display:flex;gap:4px">
                    <button class="gmb-btn-sm gmb-btn-yellow" onclick="gmbAddToCampaign([${r.id}])" title="Ajouter à campagne"><i class="fas fa-bullhorn"></i></button>
                    ${r.email ? `<button class="gmb-btn-sm gmb-btn-primary" onclick="gmbOpenSendEmail(${r.id})" title="Envoyer un email"><i class="fas fa-paper-plane"></i></button>` : ''}
                    ${r.website ? `<a href="${r.website}" target="_blank" class="gmb-btn-sm gmb-btn-blue" title="Site web"><i class="fas fa-globe"></i></a>` : ''}
                    ${r.is_converted != 1 ? `<button class="gmb-btn-sm gmb-btn-green" onclick="gmbConvert(${r.id})" title="Convertir en lead CRM"><i class="fas fa-user-plus"></i></button>` : ''}
                    <button class="gmb-btn-sm gmb-btn-red" onclick="gmbDeleteResult(${r.id})" title="Supprimer"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
    document.getElementById('results-table').style.display = '';
}

function gmbEmailCell(r) {
    let html = '<div class="gmb-email-cell">';
    if (r.email) {
        html += `<span class="gmb-email-text" title="${r.email}">${r.email}</span>`;
        html += gmbEmailStatusBadge(r.email_verification_status || 'unverified', r.email);
        html += `<button class="gmb-btn-icon" onclick="gmbVerifyEmailRow(${r.id},'${r.email}')" title="Vérifier l'email"><i class="fas fa-shield-alt"></i></button>`;
    } else {
        if (r.website) {
            html += `<button class="gmb-btn-icon" id="scrape-btn-${r.id}" onclick="gmbScrapeEmail(${r.id})" title="Scraper email depuis le site"><i class="fas fa-search"></i></button>`;
        }
        html += `<button class="gmb-btn-icon" onclick="gmbOpenEmailModal(${r.id},'${r.name||''}')" title="Saisir email manuellement"><i class="fas fa-edit"></i></button>`;
    }
    html += '</div>';
    return html;
}

// ── Rendu grille ──────────────────────────────────────────────────────────────
function gmbRenderGrid(results) {
    document.getElementById('results-grid').innerHTML = results.map(r => `
        <div class="gmb-card" id="card-${r.id}">
            <div class="gmb-card-header">
                <div>
                    <div class="gmb-card-name">${r.name||'—'}</div>
                    <div class="gmb-card-cat">${r.category||''}</div>
                </div>
                <div class="gmb-card-badges">${gmbStatusBadges(r)}</div>
            </div>
            <div class="gmb-card-info">
                ${r.address ? `<span><i class="fas fa-map-marker-alt"></i>${r.address}</span>` : ''}
                ${r.phone   ? `<span><i class="fas fa-phone"></i><a href="tel:${r.phone}" style="color:var(--gmb-primary)">${r.phone}</a></span>` : ''}
                ${r.email   ? `<span><i class="fas fa-envelope"></i>${r.email} ${gmbEmailStatusBadge(r.email_verification_status||'unverified',r.email)}</span>` : ''}
                ${r.rating  ? `<span><i class="fas fa-star"></i><span class="gmb-rating"><span class="gmb-stars">${gmbStars(r.rating)}</span>${r.rating} (${r.reviews_count})</span></span>` : ''}
            </div>
            <div class="gmb-card-actions">
                <button class="gmb-btn-sm gmb-btn-yellow" onclick="gmbAddToCampaign([${r.id}])"><i class="fas fa-bullhorn"></i> Campagne</button>
                ${!r.email && r.website ? `<button class="gmb-btn-sm gmb-btn-blue" onclick="gmbScrapeEmail(${r.id})"><i class="fas fa-at"></i> Email</button>` : ''}
                ${r.website ? `<a href="${r.website}" target="_blank" class="gmb-btn-sm gmb-btn-ghost"><i class="fas fa-globe"></i></a>` : ''}
            </div>
        </div>
    `).join('');
}

// ── Stats ─────────────────────────────────────────────────────────────────────
function gmbUpdateStats(data) {
    const r = data.results || [];
    document.getElementById('stat-searches').textContent = data.total_searches || '—';
    document.getElementById('stat-total').textContent    = data.total_results  || r.length;
    document.getElementById('stat-campaign').textContent = data.in_campaign    || r.filter(x=>x.in_campaign).length;
    document.getElementById('stat-converted').textContent= data.converted      || r.filter(x=>x.is_converted==1).length;
    document.getElementById('stat-verified').textContent = data.email_verified || r.filter(x=>x.email_verification_status==='valid').length;
    document.getElementById('stat-rating').textContent   = data.high_rating    || r.filter(x=>x.rating>=4).length;
}

// ── Historique recherches ─────────────────────────────────────────────────────
async function gmbLoadHistory() {
    const data = await gmbApi({ action: 'get_searches' }).catch(() => ({}));
    if (!data.searches || !data.searches.length) return;

    document.getElementById('searches-history').style.display = '';
    const rows = data.searches.map(s => `
        <tr>
            <td><strong>${s.query}</strong></td>
            <td>${s.location||'—'}</td>
            <td>${s.results_count}</td>
            <td style="font-size:11px;color:#888">${new Date(s.created_at).toLocaleDateString('fr-FR')}</td>
            <td>
                <button class="gmb-btn-sm gmb-btn-blue" onclick="gmbRelaunchSearch(${s.id})" title="Relancer cette recherche">
                    <i class="fas fa-redo"></i> Relancer
                </button>
                <button class="gmb-btn-sm gmb-btn-ghost" onclick="gmbLoadSearchResults(${s.id})" title="Charger les résultats">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="gmb-btn-sm gmb-btn-red" onclick="gmbDeleteSearch(${s.id})" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
    document.getElementById('history-rows').innerHTML = rows;
}

function gmbToggleHistory() {
    const body = document.getElementById('history-body');
    const chev = document.getElementById('history-chevron');
    const open = body.style.display === 'none';
    body.style.display = open ? '' : 'none';
    chev.style.transform = open ? 'rotate(180deg)' : '';
}

async function gmbRelaunchSearch(searchId) {
    document.getElementById('gmb-loading').style.display = '';
    try {
        const data = await gmbApi({ action: 'search', reuse_search_id: searchId });
        if (!data.success) { gmbToast(data.error || 'Erreur', 'error'); return; }
        gmbResults = data.results || [];
        gmbCurrentSearchId = searchId;
        gmbRenderList(gmbResults);
        gmbRenderGrid(gmbResults);
        gmbSetView(gmbView);
        document.getElementById('results-bar').style.display = 'flex';
        document.getElementById('results-count-lbl').textContent = `${gmbResults.length} résultat(s)`;
        gmbUpdateStats(data);
        gmbToast('Recherche relancée : ' + gmbResults.length + ' résultats');
    } finally {
        document.getElementById('gmb-loading').style.display = 'none';
    }
}

async function gmbLoadSearchResults(searchId) {
    const data = await gmbApi({ action: 'get', search_id: searchId });
    if (!data.success) { gmbToast('Erreur chargement', 'error'); return; }
    gmbResults = data.results || [];
    gmbCurrentSearchId = searchId;
    gmbRenderList(gmbResults);
    gmbRenderGrid(gmbResults);
    gmbSetView(gmbView);
    document.getElementById('results-bar').style.display = 'flex';
    document.getElementById('results-count-lbl').textContent = `${gmbResults.length} résultat(s)`;
    gmbToast(`${gmbResults.length} résultats chargés`);
}

async function gmbDeleteSearch(id) {
    if (!gmbConfirm('Supprimer cette recherche et ses résultats ?')) return;
    const data = await gmbApi({ action: 'delete_search', id });
    if (data.success) { gmbToast('Recherche supprimée'); gmbLoadHistory(); }
    else gmbToast(data.error, 'error');
}

// ── Email : scraping auto ─────────────────────────────────────────────────────
async function gmbScrapeEmail(id) {
    const btn = document.getElementById(`scrape-btn-${id}`);
    if (btn) { btn.innerHTML = '<i class="fas fa-spinner loading"></i>'; btn.disabled = true; }

    const data = await gmbApi({ action: 'scrape_email', result_id: id });
    if (data.success && data.email) {
        gmbToast(`Email trouvé : ${data.email}`, 'success');
        const r = gmbResults.find(x => x.id === id);
        if (r) {
            r.email = data.email;
            r.email_verification_status = data.verification_status || 'unverified';
        }
        // Refresh la ligne
        const tr = document.getElementById(`row-${id}`);
        if (tr) {
            const tds = tr.querySelectorAll('td');
            if (tds[5]) tds[5].innerHTML = gmbEmailCell(r);
        }
    } else {
        gmbToast(data.error || 'Aucun email trouvé sur le site', 'warning');
        if (btn) { btn.innerHTML = '<i class="fas fa-search"></i>'; btn.disabled = false; }
    }
}

async function gmbBulkScrapeEmails() {
    if (!gmbSelectedIds.size) { gmbToast('Aucun résultat sélectionné', 'warning'); return; }
    const ids = [...gmbSelectedIds];
    gmbToast(`Scraping emails pour ${ids.length} entreprises…`);
    const data = await gmbApi({ action: 'scrape_emails_bulk', result_ids: ids });
    if (data.success) {
        gmbToast(`${data.found} emails trouvés sur ${ids.length}`, 'success');
        await gmbLoadSearchResults(gmbCurrentSearchId);
    } else gmbToast(data.error || 'Erreur', 'error');
}

// ── Email : vérification ──────────────────────────────────────────────────────
async function gmbVerifyEmailRow(id, email) {
    gmbToast('Vérification en cours…');
    const data = await gmbApi({ action: 'verify_email', result_id: id, email });
    const r = gmbResults.find(x => x.id === id);
    if (data.success) {
        if (r) r.email_verification_status = data.status;
        const msgs = {
            valid:     '✅ Email valide et fonctionnel',
            invalid:   '❌ Email invalide',
            generic:   '⚠️ Email générique (info@, contact@…)',
            smtp_fail: '❌ Échec vérification SMTP',
            mx_fail:   '❌ Aucun serveur MX trouvé',
        };
        gmbToast(msgs[data.status] || data.status, data.status === 'valid' ? 'success' : 'warning');
        // Refresh cellule
        const tr = document.getElementById(`row-${id}`);
        if (tr && r) { const tds = tr.querySelectorAll('td'); if (tds[5]) tds[5].innerHTML = gmbEmailCell(r); }
    } else gmbToast(data.error || 'Erreur vérification', 'error');
}

async function gmbBulkVerifyEmails() {
    const ids = [...gmbSelectedIds];
    if (!ids.length) { gmbToast('Sélectionnez des résultats', 'warning'); return; }
    gmbToast(`Vérification de ${ids.length} emails…`);
    const data = await gmbApi({ action: 'verify_emails_bulk', result_ids: ids });
    if (data.success) {
        gmbToast(`${data.valid} valides, ${data.invalid} invalides, ${data.generic} génériques`, 'success');
        await gmbLoadSearchResults(gmbCurrentSearchId);
    } else gmbToast(data.error || 'Erreur', 'error');
}

// ── Email : modal saisie manuelle ─────────────────────────────────────────────
function gmbOpenEmailModal(id, name) {
    gmbCurrentEmailResultId = id;
    document.getElementById('modal-email-name').textContent = name;
    document.getElementById('modal-email-input').value = '';
    document.getElementById('modal-email-status').style.display = 'none';
    document.getElementById('modal-email').style.display = 'flex';
}

async function gmbVerifyAndSaveEmail() {
    const email = document.getElementById('modal-email-input').value.trim();
    if (!email) { gmbToast('Entrez un email', 'warning'); return; }

    const statusEl = document.getElementById('modal-email-status');
    statusEl.style.display = '';
    statusEl.innerHTML = '<i class="fas fa-spinner loading"></i> Vérification…';

    const data = await gmbApi({ action: 'verify_email', result_id: gmbCurrentEmailResultId, email });
    const msgs = {
        valid:     '✅ Email valide et fonctionnel — sauvegardé.',
        invalid:   '❌ Email invalide. Veuillez en saisir un autre.',
        generic:   '⚠️ Email générique (info@, contact@…). Vous pouvez quand même sauvegarder.',
        smtp_fail: '⚠️ La vérification SMTP a échoué. L\'email a été sauvegardé.',
        mx_fail:   '❌ Aucun serveur MX trouvé pour ce domaine.',
    };
    statusEl.innerHTML = msgs[data.status] || data.status;

    if (data.success && data.status !== 'invalid' && data.status !== 'mx_fail') {
        const r = gmbResults.find(x => x.id === gmbCurrentEmailResultId);
        if (r) { r.email = email; r.email_verification_status = data.status; }
        const tr = document.getElementById(`row-${gmbCurrentEmailResultId}`);
        if (tr && r) { const tds = tr.querySelectorAll('td'); if (tds[5]) tds[5].innerHTML = gmbEmailCell(r); }
        setTimeout(() => gmbCloseModal('modal-email'), 1200);
    }
}

// ── Actions résultats ─────────────────────────────────────────────────────────
async function gmbConvert(id) {
    if (!gmbConfirm('Convertir ce prospect en lead CRM ?')) return;
    const data = await gmbApi({ action: 'convert', id });
    if (data.success) {
        gmbToast('Lead CRM créé !', 'success');
        const r = gmbResults.find(x => x.id === id);
        if (r) r.is_converted = 1;
        gmbRenderList(gmbResults);
    } else gmbToast(data.error || 'Erreur', 'error');
}

async function gmbDeleteResult(id) {
    if (!gmbConfirm('Supprimer ce résultat ?')) return;
    const data = await gmbApi({ action: 'delete_result', id });
    if (data.success) {
        gmbResults = gmbResults.filter(r => r.id !== id);
        gmbRenderList(gmbResults);
        gmbRenderGrid(gmbResults);
        gmbToast('Résultat supprimé');
    } else gmbToast(data.error, 'error');
}

async function gmbBulkDelete() {
    const ids = [...gmbSelectedIds];
    if (!ids.length) return;
    if (!gmbConfirm(`Supprimer ${ids.length} résultat(s) ?`)) return;
    const data = await gmbApi({ action: 'bulk_delete', ids });
    if (data.success) {
        gmbResults = gmbResults.filter(r => !ids.includes(r.id));
        gmbSelectedIds.clear();
        gmbRenderList(gmbResults);
        gmbRenderGrid(gmbResults);
        gmbUpdateBulkBar();
        gmbToast(`${ids.length} résultats supprimés`);
    } else gmbToast(data.error, 'error');
}

// ── Campagnes ─────────────────────────────────────────────────────────────────
async function gmbLoadCampaigns() {
    const data = await gmbApi({ action: 'list_campaigns' });
    gmbCampaigns = data.campaigns || [];
    document.getElementById('badge-campagnes').textContent = gmbCampaigns.length;
    gmbRenderCampaigns();
}

function gmbRenderCampaigns() {
    const el = document.getElementById('campaigns-grid');
    const empty = document.getElementById('campaigns-empty');
    const detail = document.getElementById('campaign-detail');
    detail.style.display = 'none';

    if (!gmbCampaigns.length) { el.style.display = 'none'; empty.style.display = ''; return; }
    empty.style.display = 'none';
    el.style.display = 'grid';

    el.innerHTML = gmbCampaigns.map(c => `
        <div class="gmb-camp-card" onclick="gmbOpenCampaign(${c.id})">
            <div class="gmb-camp-card-header">
                <div class="gmb-camp-name">${c.name}</div>
                <span class="gmb-camp-status status-${c.status}">${c.status}</span>
            </div>
            <div class="gmb-camp-meta">
                ${c.target ? `🎯 ${c.target}` : ''} ${c.city ? `— ${c.city}` : ''}
            </div>
            <div class="gmb-camp-stats">
                <span><i class="fas fa-users"></i> ${c.contact_count||0} contacts</span>
                <span><i class="fas fa-envelope"></i> ${c.verified_emails||0} emails vérifiés</span>
            </div>
            <div class="gmb-camp-actions" onclick="event.stopPropagation()">
                <button class="gmb-btn-sm gmb-btn-ghost" onclick="gmbEditCampaign(${c.id}, event)">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="gmb-btn-sm gmb-btn-red" onclick="gmbDeleteCampaign(${c.id}, event)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

async function gmbOpenCampaign(id) {
    gmbCurrentCampaignId = id;
    const c = gmbCampaigns.find(x => x.id === id);
    document.getElementById('camp-detail-name').textContent = c ? c.name : '';
    document.getElementById('campaigns-grid').style.display = 'none';
    document.getElementById('campaigns-empty').style.display = 'none';
    document.getElementById('campaign-detail').style.display = '';

    const data = await gmbApi({ action: 'get_campaign', id });
    const contacts = data.contacts || [];
    const tbody = document.getElementById('camp-detail-tbody');
    tbody.innerHTML = contacts.length === 0
        ? '<tr><td colspan="6" style="text-align:center;color:#aaa;padding:30px">Aucun contact dans cette campagne</td></tr>'
        : contacts.map(r => `
        <tr>
            <td><input type="checkbox" class="gmb-camp-chk" data-id="${r.id}"></td>
            <td><strong style="color:var(--gmb-primary)">${r.name||'—'}</strong></td>
            <td>${r.phone ? `<a href="tel:${r.phone}" style="color:var(--gmb-primary)">${r.phone}</a>` : '—'}</td>
            <td>${r.email || '<span style="color:#aaa">—</span>'}</td>
            <td>${gmbEmailStatusBadge(r.email_verification_status||'unverified', r.email)}</td>
            <td>
                <div style="display:flex;gap:4px">
                    ${r.email ? `<button class="gmb-btn-sm gmb-btn-primary" onclick="gmbOpenSendEmail(${r.id})" title="Envoyer email"><i class="fas fa-paper-plane"></i></button>` : ''}
                    ${!r.email ? `<button class="gmb-btn-sm gmb-btn-blue" onclick="gmbOpenEmailModal(${r.id},'${r.name||''}')"><i class="fas fa-at"></i></button>` : ''}
                    ${r.email ? `<button class="gmb-btn-sm gmb-btn-blue" onclick="gmbVerifyEmailRow(${r.id},'${r.email}')"><i class="fas fa-shield-alt"></i></button>` : ''}
                    <button class="gmb-btn-sm gmb-btn-red" onclick="gmbRemoveFromCampaign(${r.id})" title="Retirer de la campagne"><i class="fas fa-times"></i></button>
                    <button class="gmb-btn-sm gmb-btn-green" onclick="gmbConvert(${r.id})" title="Convertir en lead CRM"><i class="fas fa-user-plus"></i></button>
                </div>
            </td>
        </tr>
    `).join('');
}

function gmbBackToCampaigns() {
    gmbCurrentCampaignId = null;
    document.getElementById('campaign-detail').style.display = 'none';
    gmbRenderCampaigns();
}

function gmbToggleCampAll(chk) {
    document.querySelectorAll('.gmb-camp-chk').forEach(c => c.checked = chk.checked);
}

async function gmbBulkVerifyInCampaign() {
    const ids = [...document.querySelectorAll('.gmb-camp-chk:checked')].map(c => parseInt(c.dataset.id));
    if (!ids.length) { gmbToast('Sélectionnez des contacts', 'warning'); return; }
    const data = await gmbApi({ action: 'verify_emails_bulk', result_ids: ids });
    if (data.success) {
        gmbToast(`${data.valid} valides, ${data.invalid} invalides`, 'success');
        gmbOpenCampaign(gmbCurrentCampaignId);
    } else gmbToast(data.error, 'error');
}

async function gmbConvertCampaignToLeads() {
    const ids = [...document.querySelectorAll('.gmb-camp-chk:checked')].map(c => parseInt(c.dataset.id));
    if (!ids.length) { gmbToast('Sélectionnez des contacts', 'warning'); return; }
    if (!gmbConfirm(`Convertir ${ids.length} contact(s) en leads CRM ?`)) return;
    const data = await gmbApi({ action: 'convert_bulk', ids });
    if (data.success) {
        gmbToast(`${ids.length} leads CRM créés`, 'success');
        gmbOpenCampaign(gmbCurrentCampaignId);
    } else gmbToast(data.error, 'error');
}

async function gmbRemoveFromCampaign(resultId) {
    const data = await gmbApi({ action: 'remove_from_campaign', result_id: resultId, campaign_id: gmbCurrentCampaignId });
    if (data.success) { gmbToast('Contact retiré'); gmbOpenCampaign(gmbCurrentCampaignId); }
    else gmbToast(data.error, 'error');
}

// ── Ajouter à campagne ────────────────────────────────────────────────────────
function gmbAddToCampaign(ids) {
    gmbAddCampaignIds = ids;
    const n = ids.length;
    document.getElementById('modal-add-info').textContent = `${n} entreprise(s) sélectionnée(s)`;
    const sel = document.getElementById('modal-camp-select');
    sel.innerHTML = '<option value="">— Sélectionner une campagne existante —</option>' +
        gmbCampaigns.map(c => `<option value="${c.id}">${c.name} (${c.contact_count||0} contacts)</option>`).join('');
    document.getElementById('modal-camp-new-name').value = '';
    document.getElementById('modal-camp-new-city').value = '';
    document.getElementById('modal-add-campaign').style.display = 'flex';
}

function gmbBulkAddToCampaign() {
    const ids = [...gmbSelectedIds];
    if (!ids.length) { gmbToast('Aucun résultat sélectionné', 'warning'); return; }
    gmbAddToCampaign(ids);
}

async function gmbConfirmAddToCampaign() {
    let campId = parseInt(document.getElementById('modal-camp-select').value);
    const newName = document.getElementById('modal-camp-new-name').value.trim();
    const newCity = document.getElementById('modal-camp-new-city').value.trim();

    if (!campId && newName) {
        const created = await gmbApi({ action: 'create_campaign', name: newName, city: newCity });
        if (!created.success) { gmbToast(created.error || 'Erreur création campagne', 'error'); return; }
        campId = created.id;
        await gmbLoadCampaigns();
    }

    if (!campId) { gmbToast('Choisissez ou créez une campagne', 'warning'); return; }

    const data = await gmbApi({ action: 'add_to_campaign', campaign_id: campId, result_ids: gmbAddCampaignIds });
    if (data.success) {
        gmbToast(`${data.added} contact(s) ajouté(s) à la campagne`, 'success');
        // Mettre à jour les badges
        gmbAddCampaignIds.forEach(id => {
            const r = gmbResults.find(x => x.id === id);
            if (r) r.in_campaign = 1;
        });
        gmbRenderList(gmbResults);
        gmbRenderGrid(gmbResults);
        gmbCloseModal('modal-add-campaign');
        gmbLoadCampaigns();
    } else gmbToast(data.error || 'Erreur', 'error');
}

// ── CRUD Campagnes ────────────────────────────────────────────────────────────
function gmbShowCreateCampaign() {
    document.getElementById('new-camp-name').value = '';
    document.getElementById('new-camp-target').value = '';
    document.getElementById('new-camp-city').value = '';
    document.getElementById('new-camp-notes').value = '';
    document.getElementById('modal-create-campaign').style.display = 'flex';
}

async function gmbCreateCampaign() {
    const name = document.getElementById('new-camp-name').value.trim();
    if (!name) { gmbToast('Nom obligatoire', 'warning'); return; }
    const data = await gmbApi({
        action: 'create_campaign',
        name,
        target: document.getElementById('new-camp-target').value.trim(),
        city:   document.getElementById('new-camp-city').value.trim(),
        notes:  document.getElementById('new-camp-notes').value.trim(),
    });
    if (data.success) {
        gmbToast('Campagne créée !', 'success');
        gmbCloseModal('modal-create-campaign');
        gmbLoadCampaigns();
    } else gmbToast(data.error || 'Erreur', 'error');
}

async function gmbDeleteCampaign(id, event) {
    event.stopPropagation();
    if (!gmbConfirm('Supprimer cette campagne et tous ses contacts ?')) return;
    const data = await gmbApi({ action: 'delete_campaign', id });
    if (data.success) { gmbToast('Campagne supprimée'); gmbLoadCampaigns(); }
    else gmbToast(data.error, 'error');
}

function gmbEditCampaign(id, event) {
    event.stopPropagation();
    // Pour l'instant, ouvre le détail où on peut gérer les contacts
    gmbOpenCampaign(id);
}

// ── Envoi Email ───────────────────────────────────────────────────────────────
async function gmbOpenSendEmail(resultId) {
    // Chercher les données du contact dans les résultats chargés
    let contact = gmbResults.find(r => r.id === resultId);
    if (!contact) {
        // Pas en mémoire (ex: depuis campagne) → appel API
        const data = await gmbApi({ action: 'get_result', id: resultId });
        contact = data.result || null;
    }
    if (!contact || !contact.email) { gmbToast('Email introuvable', 'error'); return; }

    gmbCurrentEmailSendId = resultId;
    gmbCurrentEmailSendData = contact;

    document.getElementById('send-email-company').textContent = contact.name || '—';
    document.getElementById('send-to-email').value = contact.email;

    // Activer template par défaut : guide_local
    document.querySelectorAll('.gmb-tpl-btn').forEach(b => b.classList.remove('active'));
    document.querySelector('[data-tpl="guide_local"]').classList.add('active');
    gmbApplyTemplate('guide_local', contact);

    // Meta info
    const meta = [];
    if (contact.rating) meta.push(`⭐ ${contact.rating}/5`);
    if (contact.reviews_count) meta.push(`${contact.reviews_count} avis`);
    if (contact.category) meta.push(contact.category);
    document.getElementById('send-meta-info').textContent = meta.join(' · ') || 'Aucune info supplémentaire';

    document.getElementById('modal-send-email').style.display = 'flex';
}

function gmbSelectTemplate(tplKey, btn) {
    document.querySelectorAll('.gmb-tpl-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    gmbApplyTemplate(tplKey, gmbCurrentEmailSendData);
}

function gmbApplyTemplate(tplKey, contact) {
    const tpl = GMB_TEMPLATES[tplKey];
    if (!tpl) return;

    const vars = {
        nom_entreprise: contact.name || '',
        prenom_contact: gmbExtractFirstName(contact.name),
        ville: contact.address ? gmbExtractCity(contact.address) : (contact.city || 'Bordeaux'),
        note: contact.rating || '—',
        avis: contact.reviews_count || '—'
    };

    const subject = gmbReplaceVars(tpl.subject, vars);
    const body    = gmbReplaceVars(tpl.body, vars);

    document.getElementById('send-subject').value = subject;
    document.getElementById('send-body').innerHTML = body;
}

function gmbReplaceVars(str, vars) {
    return str.replace(/\{\{(\w+)\}\}/g, (_, k) => vars[k] !== undefined ? vars[k] : `{{${k}}}`);
}

function gmbExtractFirstName(name) {
    if (!name) return '';
    // Essayer d'extraire un prénom si c'est "Prénom NOM" — sinon retourner prénom générique
    const parts = name.trim().split(/\s+/);
    const first = parts[0];
    // Si ça ressemble à un nom d'entreprise (tout maj, ou >2 mots), utiliser générique
    if (parts.length > 3 || first === first.toUpperCase()) return '';
    return first;
}

function gmbExtractCity(address) {
    if (!address) return 'Bordeaux';
    // Format : "Rue, CP Ville" → extraire la ville
    const match = address.match(/\d{5}\s+(.+?)(?:,|$)/);
    if (match) return match[1].trim();
    // Dernier segment après virgule
    const parts = address.split(',');
    return parts[parts.length - 1].trim() || 'Bordeaux';
}

function gmbFmt(cmd) {
    document.execCommand(cmd, false, null);
    document.getElementById('send-body').focus();
}

function gmbPreviewEmail() {
    const to      = document.getElementById('send-to-email').value;
    const subject = document.getElementById('send-subject').value.trim();
    const body    = document.getElementById('send-body').innerHTML.trim();

    if (!subject) { gmbToast('Veuillez saisir un objet', 'warning'); return; }
    if (!body)    { gmbToast('Veuillez saisir un message', 'warning'); return; }

    document.getElementById('preview-to').textContent      = to;
    document.getElementById('preview-subject').textContent = subject;
    document.getElementById('preview-body').innerHTML      = body;

    document.getElementById('modal-send-email').style.display  = 'none';
    document.getElementById('modal-preview-email').style.display = 'flex';
}

async function gmbSendEmail(fromPreview = false) {
    const to      = document.getElementById('send-to-email').value;
    const subject = document.getElementById('send-subject').value.trim();
    const body    = document.getElementById('send-body').innerHTML.trim();

    if (!subject || !body) { gmbToast('Objet et message requis', 'warning'); return; }

    const btn = document.getElementById('btn-send-email');
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi…'; }

    const data = await gmbApi({
        action    : 'send_email',
        result_id : gmbCurrentEmailSendId,
        to        : to,
        subject   : subject,
        body      : body
    });

    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer'; }

    if (data.success) {
        gmbToast(`Email envoyé à ${to} ✓`, 'success');
        gmbCloseModal('modal-send-email');
        gmbCloseModal('modal-preview-email');
    } else {
        gmbToast(data.error || 'Erreur lors de l\'envoi', 'error');
        if (fromPreview) {
            document.getElementById('modal-preview-email').style.display  = 'none';
            document.getElementById('modal-send-email').style.display = 'flex';
        }
    }
}

// ── Modals ────────────────────────────────────────────────────────────────────
function gmbCloseModal(id) {
    document.getElementById(id).style.display = 'none';
}

// Fermer au clic extérieur
document.querySelectorAll('.gmb-modal').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) gmbCloseModal(m.id); });
});

// ── Init ──────────────────────────────────────────────────────────────────────
(async function gmbInit() {
    gmbSetView(gmbView);
    await Promise.all([gmbLoadCampaigns(), gmbLoadSecteurs()]);
    gmbLoadHistory();
})();
</script>