<?php
/**
 * Module ESTIMATION - Avis de Valeur Professionnel
 * /admin/modules/estimation/avisdevaleur.php
 * Formulaire détaillé + BANT + Prise de RDV
 */

$step = $_GET['step'] ?? 1;
$submitted = isset($_POST['submit']);

?>

<style>
/* BANT Section Styles */
.bant-section {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    border: 1px solid #fcd34d;
}

.bant-section h4 {
    color: #92400e;
    font-weight: 700;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.bant-field {
    background: white;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 12px;
}

.bant-field label {
    display: block;
    font-weight: 600;
    color: #111827;
    margin-bottom: 8px;
    font-size: 14px;
}

.bant-field select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
}

/* Temperature indicator */
.temperature-indicator {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 12px;
}

.temperature-hot {
    background: #fecaca;
    color: #991b1b;
}

.temperature-warm {
    background: #fcdab7;
    color: #9a3412;
}

.temperature-cold {
    background: #e0e7ff;
    color: #3730a3;
}

/* Form Steps */
.form-step {
    display: none;
}

.form-step.active {
    display: block;
}

/* RDV Selection */
.rdv-slots {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .rdv-slots {
        grid-template-columns: 1fr;
    }
}

.rdv-slot {
    padding: 16px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.rdv-slot:hover {
    border-color: #059669;
    background: #f0fdf4;
}

.rdv-slot.selected {
    border-color: #059669;
    background: linear-gradient(135deg, #f0fdf4 0%, #dbeafe 100%);
}

.rdv-slot strong {
    display: block;
    color: #111827;
    margin-bottom: 4px;
}

.rdv-slot small {
    color: #6b7280;
}

/* Confirmation Box */
.confirmation-box {
    background: #f0fdf4;
    border: 2px solid #059669;
    border-radius: 12px;
    padding: 32px;
    text-align: center;
    margin-bottom: 24px;
}

.confirmation-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.confirmation-box h3 {
    font-size: 20px;
    font-weight: 700;
    color: #065f46;
    margin-bottom: 8px;
}

.confirmation-box p {
    color: #047857;
    margin-bottom: 20px;
}

.confirmation-details {
    background: white;
    border-radius: 8px;
    padding: 20px;
    text-align: left;
    margin-bottom: 20px;
}

.confirmation-detail {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

.confirmation-detail:last-child {
    border-bottom: none;
}

.confirmation-detail strong {
    color: #111827;
}

.confirmation-detail span {
    color: #6b7280;
}

/* Summary */
.summary-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    border: 1px solid #e5e7eb;
    margin-bottom: 24px;
}

.summary-section h4 {
    font-size: 14px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #e5e7eb;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    font-size: 14px;
}

.summary-item .label {
    color: #6b7280;
}

.summary-item .value {
    color: #111827;
    font-weight: 600;
}
</style>

<div class="form-wrapper">
    <div class="form-card">
        
        <!-- FORM HEADER -->
        <div class="form-header">
            <h2>⭐ Demande d'Avis de Valeur</h2>
            <p>Obtenez une analyse professionnelle avec un conseiller immobilier</p>
        </div>
        
        <!-- PROGRESS -->
        <div class="progress-wrapper">
            <div class="progress-bar">
                <div class="progress-dot <?php echo $step >= 1 ? 'active' : ''; ?>"></div>
                <div class="progress-dot <?php echo $step >= 2 ? 'active' : ''; ?>"></div>
                <div class="progress-dot <?php echo $step >= 3 ? 'active' : ''; ?>"></div>
                <div class="progress-dot <?php echo $step >= 4 ? 'active' : ''; ?>"></div>
            </div>
            <div class="progress-text">Étape <?php echo $step; ?>/4</div>
        </div>
        
        <!-- FORM -->
        <form method="POST" id="valuation-form">
            
            <?php if ($step == 1 && !$submitted): ?>
            <!-- STEP 1: PROPERTY DETAILS -->
            <h3 style="margin-bottom: 24px; font-size: 16px; color: #111827; font-weight: 700;">1️⃣ Votre Propriété</h3>
            
            <div class="form-group">
                <label>Adresse complète *</label>
                <input type="text" name="address" placeholder="Ex: 75 Rue de la Paix, 75002 Paris" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Type de bien *</label>
                    <select name="property_type" required>
                        <option value="">Sélectionner...</option>
                        <option value="appartement">Appartement</option>
                        <option value="maison">Maison</option>
                        <option value="studio">Studio</option>
                        <option value="villa">Villa</option>
                        <option value="duplex">Duplex</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Surface (m²) *</label>
                    <input type="number" name="surface" placeholder="Ex: 65" min="1" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Pièces *</label>
                    <input type="number" name="rooms" placeholder="Ex: 2" min="1" required>
                </div>
                <div class="form-group">
                    <label>État *</label>
                    <select name="condition" required>
                        <option value="">Sélectionner...</option>
                        <option value="neuf">Neuf</option>
                        <option value="bon">Bon</option>
                        <option value="moyen">Moyen</option>
                        <option value="renovation">À rénover</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Détails supplémentaires</label>
                <textarea name="details" placeholder="Terrasse, parking, cuisine ouverte..." style="height: 80px;"></textarea>
            </div>
            
            <?php elseif ($step == 2 && !$submitted): ?>
            <!-- STEP 2: BANT QUALIFICATION -->
            <h3 style="margin-bottom: 24px; font-size: 16px; color: #111827; font-weight: 700;">2️⃣ Qualification (BANT)</h3>
            
            <div class="bant-section">
                <h4>💰 Budget (B)</h4>
                <div class="bant-field">
                    <label>Quel est votre budget estimé pour vendre? *</label>
                    <select name="bant_budget" onchange="updateTemperature()" required>
                        <option value="">Sélectionner...</option>
                        <option value="150-300k">150 000€ - 300 000€</option>
                        <option value="300-500k">300 000€ - 500 000€</option>
                        <option value="500k-1m">500 000€ - 1 000 000€</option>
                        <option value="1m+">Plus de 1 000 000€</option>
                    </select>
                </div>
            </div>
            
            <div class="bant-section">
                <h4>👥 Autorité (A)</h4>
                <div class="bant-field">
                    <label>Qui prend la décision? *</label>
                    <select name="bant_authority" onchange="updateTemperature()" required>
                        <option value="">Sélectionner...</option>
                        <option value="moi">Moi seul</option>
                        <option value="couple">Mon conjoint et moi</option>
                        <option value="famille">Avec la famille</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
            </div>
            
            <div class="bant-section">
                <h4>⏰ Besoin (N) - CRUCIAL</h4>
                <div class="bant-field">
                    <label>Quel est votre besoin de vendre? *</label>
                    <select name="bant_need" onchange="updateTemperature()" required>
                        <option value="">Sélectionner...</option>
                        <option value="oui">🔥 Oui, dans 3 mois (URGENT)</option>
                        <option value="peut-etre">⚠️ Peut-être, dans 6-12 mois</option>
                        <option value="non">❓ Non, simple curiosité</option>
                        <option value="heritage">📋 Héritage/Succession</option>
                    </select>
                </div>
            </div>
            
            <div class="bant-section">
                <h4>📅 Délai (T)</h4>
                <div class="bant-field">
                    <label>Timeline d'action? *</label>
                    <select name="bant_timeline" onchange="updateTemperature()" required>
                        <option value="">Sélectionner...</option>
                        <option value="immediate">Immédiat (< 3 mois)</option>
                        <option value="short">Court terme (3-6 mois)</option>
                        <option value="medium">Moyen terme (6-12 mois)</option>
                        <option value="long">Long terme (> 12 mois)</option>
                    </select>
                </div>
            </div>
            
            <!-- Temperature Indicator -->
            <div id="temperature" class="temperature-indicator temperature-cold" style="width: 100%; justify-content: center;">
                ❓ Évaluation: En attente...
            </div>
            
            <?php elseif ($step == 3 && !$submitted): ?>
            <!-- STEP 3: SELLER TYPE & CONTACT -->
            <h3 style="margin-bottom: 24px; font-size: 16px; color: #111827; font-weight: 700;">3️⃣ Vos Informations</h3>
            
            <div class="form-group">
                <label>Type de vendeur *</label>
                <select name="seller_type" required>
                    <option value="">Sélectionner...</option>
                    <option value="proprietaire">👤 Propriétaire occupant</option>
                    <option value="investisseur">📈 Investisseur</option>
                    <option value="succession">📋 Héritier/Succession</option>
                    <option value="autre">Autre</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Nom complet *</label>
                <input type="text" name="name" placeholder="Ex: Jean Dupont" required>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" placeholder="Ex: jean@email.com" required>
                </div>
                <div class="form-group">
                    <label>Téléphone *</label>
                    <input type="tel" name="phone" placeholder="06 12 34 56 78" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Message (optionnel)</label>
                <textarea name="message" placeholder="Précisions ou questions supplémentaires..." style="height: 80px;"></textarea>
            </div>
            
            <?php elseif ($step == 4 && !$submitted): ?>
            <!-- STEP 4: RDV PLANNING -->
            <h3 style="margin-bottom: 24px; font-size: 16px; color: #111827; font-weight: 700;">4️⃣ Planifier votre RDV</h3>
            
            <p style="margin-bottom: 20px; color: #6b7280; font-size: 14px;">
                📅 Sélectionnez un créneau qui vous convient. Un conseiller confirmera rapidement votre RDV.
            </p>
            
            <label style="display: block; margin-bottom: 16px; font-weight: 600; color: #111827;">Créneaux disponibles:</label>
            
            <div class="rdv-slots">
                <div class="rdv-slot" onclick="selectRDV(this, '2024-02-15', '10:00')">
                    <strong>Jeudi 15 février</strong>
                    <small>10:00 - 11:00</small>
                </div>
                <div class="rdv-slot" onclick="selectRDV(this, '2024-02-15', '14:00')">
                    <strong>Jeudi 15 février</strong>
                    <small>14:00 - 15:00</small>
                </div>
                <div class="rdv-slot" onclick="selectRDV(this, '2024-02-16', '10:00')">
                    <strong>Vendredi 16 février</strong>
                    <small>10:00 - 11:00</small>
                </div>
                <div class="rdv-slot" onclick="selectRDV(this, '2024-02-16', '15:00')">
                    <strong>Vendredi 16 février</strong>
                    <small>15:00 - 16:00</small>
                </div>
                <div class="rdv-slot" onclick="selectRDV(this, '2024-02-19', '09:00')">
                    <strong>Lundi 19 février</strong>
                    <small>09:00 - 10:00</small>
                </div>
                <div class="rdv-slot" onclick="selectRDV(this, '2024-02-19', '16:00')">
                    <strong>Lundi 19 février</strong>
                    <small>16:00 - 17:00</small>
                </div>
            </div>
            
            <input type="hidden" name="rdv_date" id="rdv_date">
            <input type="hidden" name="rdv_time" id="rdv_time">
            
            <div class="form-group">
                <label>Type de RDV *</label>
                <select name="rdv_type" required>
                    <option value="">Sélectionner...</option>
                    <option value="visioconference">📱 Visioconférence</option>
                    <option value="telephone">☎️ Téléphone</option>
                    <option value="domicile">🏠 À domicile</option>
                </select>
            </div>
            
            <?php elseif ($submitted): ?>
            <!-- CONFIRMATION -->
            <div class="confirmation-box">
                <div class="confirmation-icon">✅</div>
                <h3>Votre demande est confirmée!</h3>
                <p>Un conseiller vous contactera dans les 24 heures</p>
                
                <div class="confirmation-details">
                    <div class="confirmation-detail">
                        <strong>RDV prévu:</strong>
                        <span id="confirm-rdv">Vendredi 15 février à 10:00</span>
                    </div>
                    <div class="confirmation-detail">
                        <strong>Contacté par:</strong>
                        <span id="confirm-phone">06 12 34 56 78</span>
                    </div>
                    <div class="confirmation-detail">
                        <strong>Type de bien:</strong>
                        <span>Appartement, 65m²</span>
                    </div>
                    <div class="confirmation-detail">
                        <strong>Adresse:</strong>
                        <span id="confirm-address">75 Rue de la Paix, 75002 Paris</span>
                    </div>
                </div>
                
                <p style="font-size: 13px; color: #047857; margin-bottom: 20px;">
                    Un email de confirmation a été envoyé à votre adresse.
                </p>
                
                <div style="display: flex; gap: 12px;">
                    <a href="/admin/dashboard.php?page=estimation" class="btn-cta secondary" style="flex: 1;">
                        ← Retour à l'accueil
                    </a>
                    <a href="/admin/dashboard.php?page=estimation&action=free-estimation" class="btn-cta primary" style="flex: 1;">
                        Nouvelle estimation
                    </a>
                </div>
            </div>
            
            <?php endif; ?>
            
            <!-- Buttons -->
            <?php if (!$submitted): ?>
            <div class="button-group">
                <?php if ($step > 1): ?>
                <button type="button" class="btn btn-secondary" onclick="previousStep()">← Retour</button>
                <?php endif; ?>
                
                <?php if ($step < 4): ?>
                <button type="button" class="btn btn-primary" onclick="nextStep()">Continuer →</button>
                <?php else: ?>
                <button type="submit" name="submit" class="btn btn-primary">Confirmer la demande</button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
function nextStep() {
    const step = <?php echo $step; ?>;
    window.location.href = '?page=estimation&action=professional-valuation&step=' + (step + 1);
}

function previousStep() {
    const step = <?php echo $step; ?>;
    if (step > 1) {
        window.location.href = '?page=estimation&action=professional-valuation&step=' + (step - 1);
    }
}

function updateTemperature() {
    const need = document.querySelector('select[name="bant_need"]').value;
    const timeline = document.querySelector('select[name="bant_timeline"]').value;
    const temp = document.getElementById('temperature');
    
    let score = 0;
    let level = 'cold';
    let text = 'Évaluation: Tiède';
    
    if (need === 'oui') score += 40;
    if (need === 'peut-etre') score += 20;
    
    if (timeline === 'immediate') score += 35;
    if (timeline === 'short') score += 20;
    
    if (score >= 75) {
        level = 'hot';
        text = '🔥 Hot Lead - Priorité haute!';
    } else if (score >= 50) {
        level = 'warm';
        text = '⚠️ Warm Lead - Important';
    } else {
        level = 'cold';
        text = '❓ Cold Lead - Suivi futur';
    }
    
    temp.className = 'temperature-indicator temperature-' + level;
    temp.textContent = text;
}

function selectRDV(element, date, time) {
    // Remove previous selection
    document.querySelectorAll('.rdv-slot').forEach(el => {
        el.classList.remove('selected');
    });
    
    // Add selection to clicked element
    element.classList.add('selected');
    
    // Store values
    document.getElementById('rdv_date').value = date;
    document.getElementById('rdv_time').value = time;
}

// Form submission
document.getElementById('valuation-form').addEventListener('submit', function(e) {
    if (<?php echo $step; ?> === 4) {
        if (!document.getElementById('rdv_date').value) {
            e.preventDefault();
            alert('Veuillez sélectionner un créneau pour votre RDV');
        }
    }
});
</script>