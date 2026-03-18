<?php
/**
 * Module ESTIMATION - /admin/modules/estimation/public.php
 * Page d'estimation gratuite pour les propriétaires
 * Accessible publiquement sur le site
 */

// Variables pour l'estimation
$estimation_type = $_GET['type'] ?? 'gratuite'; // gratuite ou avis_de_valeur
$step = $_GET['step'] ?? 1; // Étapes du formulaire

// Simule une estimation gratuite (sera connectée à OpenAI/Perplexity)
$sample_estimation = [
    'address' => 'Paris, 75001',
    'type' => 'Appartement',
    'surface' => 65,
    'rooms' => 2,
    'prix_bas' => 390000,
    'prix_moyen' => 455000,
    'prix_haut' => 520000,
    'justification' => 'Basé sur comparables récents dans le 1er arrondissement'
];

?>

<style>
/* Hero Banner */
.estimation-hero {
    background: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%);
    border-radius: 20px;
    padding: 40px;
    color: white;
    margin-bottom: 32px;
}

.estimation-hero h2 {
    font-size: 28px;
    font-weight: 800;
    margin-bottom: 12px;
}

.estimation-hero p {
    opacity: 0.95;
    font-size: 16px;
    max-width: 600px;
}

.estimation-hero .badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-top: 12px;
}

/* Two Column Layout */
.estimation-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
    margin-bottom: 32px;
}

@media (max-width: 1024px) {
    .estimation-container { grid-template-columns: 1fr; }
}

/* Card */
.estimation-card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.estimation-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(236, 72, 153, 0.15);
    border-color: #ec4899;
}

.estimation-card h3 {
    font-size: 20px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.estimation-card .icon {
    font-size: 28px;
}

.estimation-card p {
    color: #6b7280;
    font-size: 14px;
    line-height: 1.6;
    margin-bottom: 20px;
    min-height: 50px;
}

.estimation-card .features {
    list-style: none;
    margin-bottom: 20px;
}

.estimation-card .features li {
    color: #6b7280;
    font-size: 14px;
    padding: 8px 0;
    padding-left: 24px;
    position: relative;
}

.estimation-card .features li:before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #10b981;
    font-weight: bold;
}

.btn-estimation {
    width: 100%;
    padding: 14px 24px;
    border-radius: 10px;
    font-size: 15px;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-estimation.primary {
    background: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%);
    color: white;
}

.btn-estimation.primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(236, 72, 153, 0.3);
}

.btn-estimation.secondary {
    background: white;
    color: #ec4899;
    border: 2px solid #ec4899;
}

.btn-estimation.secondary:hover {
    background: #fdf2f8;
}

/* Form Styles */
.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 8px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #ec4899;
    box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.1);
}

/* Estimation Result */
.estimation-result {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 2px solid #ec4899;
}

.estimation-result h3 {
    font-size: 18px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 24px;
}

.price-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .price-grid { grid-template-columns: 1fr; }
}

.price-box {
    background: #f9fafb;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
}

.price-box.bas {
    border-left: 4px solid #ef4444;
}

.price-box.moyen {
    border-left: 4px solid #ec4899;
    background: #fdf2f8;
}

.price-box.haut {
    border-left: 4px solid #10b981;
}

.price-box label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 8px;
    display: block;
}

.price-box .value {
    font-size: 28px;
    font-weight: 800;
    color: #111827;
}

.estimation-justification {
    background: #f9fafb;
    border-left: 4px solid #ec4899;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 14px;
    color: #6b7280;
    line-height: 1.6;
}

/* Progress */
.progress-bar {
    display: flex;
    gap: 12px;
    margin-bottom: 32px;
}

.progress-item {
    flex: 1;
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
}

.progress-item.active {
    background: linear-gradient(90deg, #ec4899, #f43f5e);
}

/* Buttons */
.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.form-actions button {
    flex: 1;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.form-actions .btn-prev {
    background: white;
    color: #111827;
    border: 1px solid #e5e7eb;
}

.form-actions .btn-next {
    background: linear-gradient(135deg, #ec4899 0%, #f43f5e 100%);
    color: white;
}

.form-actions .btn-next:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(236, 72, 153, 0.3);
}

/* Empty State */
.empty-state {
    background: white;
    border-radius: 16px;
    padding: 60px 20px;
    text-align: center;
    color: #6b7280;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}

.empty-state i {
    font-size: 48px;
    color: #e5e7eb;
    margin-bottom: 16px;
}

/* Checkbox Group */
.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.checkbox-item:hover {
    border-color: #ec4899;
    background: #fdf2f8;
}

.checkbox-item input {
    width: auto;
    margin-right: 12px;
    cursor: pointer;
}

.checkbox-item label {
    flex: 1;
    margin: 0;
    cursor: pointer;
}

/* Alert */
.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert.info {
    background: #f0f9ff;
    color: #0369a1;
    border-left: 4px solid #0284c7;
}

.alert.success {
    background: #f0fdf4;
    color: #166534;
    border-left: 4px solid #22c55e;
}

.alert.warning {
    background: #fef3c7;
    color: #92400e;
    border-left: 4px solid #fbbf24;
}
</style>

<!-- HERO BANNER -->
<div class="estimation-hero">
    <h2>🏷️ Estimer la valeur de votre bien</h2>
    <p>Découvrez la valeur estimée de votre propriété en quelques minutes. Une estimation gratuite, rapide et sans engagement.</p>
    <div class="badge">✓ Gratuit et sans engagement</div>
</div>

<!-- CHOICE BETWEEN FREE AND PRO -->
<div class="estimation-container">
    
    <!-- FREE ESTIMATION -->
    <div class="estimation-card">
        <h3>
            <span class="icon">💰</span>
            Estimation Gratuite
        </h3>
        <p>Une estimation rapide basée sur les données du marché immobilier local et les comparables.</p>
        
        <ul class="features">
            <li>Résultat en 2-3 minutes</li>
            <li>Prix bas, moyen et haut</li>
            <li>Justification détaillée</li>
            <li>Basé sur algorithme IA</li>
            <li>Pas d'engagement requis</li>
        </ul>
        
        <button onclick="goToStep('gratuite', 1)" class="btn-estimation primary">
            <i class="fas fa-arrow-right"></i> Commencer l'estimation
        </button>
    </div>

    <!-- PROFESSIONAL APPRAISAL -->
    <div class="estimation-card">
        <h3>
            <span class="icon">⭐</span>
            Avis de Valeur
        </h3>
        <p>Un vrai avis de valeur avec un conseiller immobilier expert de votre région.</p>
        
        <ul class="features">
            <li>Analyse approfondie</li>
            <li>Expertise du conseiller</li>
            <li>Prise de RDV gratuit</li>
            <li>Rapport détaillé</li>
            <li>Qualification BANT</li>
        </ul>
        
        <button onclick="goToStep('avis_de_valeur', 1)" class="btn-estimation secondary">
            <i class="fas fa-calendar"></i> Demander un avis de valeur
        </button>
    </div>

</div>

<!-- ESTIMATION FORM -->
<div id="estimation-form" style="display: none;">
    <div class="estimation-result">
        
        <!-- Progress -->
        <div class="progress-bar">
            <div class="progress-item active"></div>
            <div class="progress-item" id="progress-2"></div>
            <div class="progress-item" id="progress-3"></div>
        </div>

        <!-- Form Title -->
        <h3 id="form-title">Informations sur votre bien</h3>

        <!-- Alert -->
        <div class="alert info">
            <i class="fas fa-info-circle"></i> Vos données sont 100% confidentielles et utilisées uniquement pour l'estimation.
        </div>

        <!-- Step 1: Property Details -->
        <div id="step-1" class="form-step">
            <form onsubmit="nextStep(event, 2)">
                <div class="form-group">
                    <label>Adresse du bien *</label>
                    <input type="text" placeholder="Ex: 123 Rue de la Paix, 75000 Paris" required>
                </div>

                <div class="form-group">
                    <label>Type de bien *</label>
                    <select required>
                        <option value="">-- Sélectionner --</option>
                        <option value="appartement">Appartement</option>
                        <option value="maison">Maison</option>
                        <option value="studio">Studio</option>
                        <option value="loft">Loft</option>
                        <option value="villa">Villa</option>
                        <option value="duplex">Duplex</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label>Surface habitable (m²) *</label>
                        <input type="number" placeholder="Ex: 65" min="0" required>
                    </div>

                    <div class="form-group">
                        <label>Nombre de pièces *</label>
                        <input type="number" placeholder="Ex: 2" min="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>État général du bien *</label>
                    <select required>
                        <option value="">-- Sélectionner --</option>
                        <option value="neuf">Neuf / Très bon</option>
                        <option value="bon">Bon état</option>
                        <option value="moyen">État moyen</option>
                        <option value="renovation">À rénover</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeEstimation()" class="btn-prev">Annuler</button>
                    <button type="submit" class="btn-next">Continuer</button>
                </div>
            </form>
        </div>

        <!-- Step 2: Additional Details -->
        <div id="step-2" class="form-step" style="display: none;">
            <form onsubmit="nextStep(event, 3)">
                <div class="form-group">
                    <label>Étage du logement</label>
                    <input type="text" placeholder="Ex: 3ème étage">
                </div>

                <div class="form-group">
                    <label>Équipements / Améliorations</label>
                    <div class="checkbox-group">
                        <div class="checkbox-item">
                            <input type="checkbox" id="terrace" name="amenities" value="terrasse">
                            <label for="terrace">Terrasse / Balcon</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="parking" name="amenities" value="parking">
                            <label for="parking">Parking / Garage</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="garden" name="amenities" value="jardin">
                            <label for="garden">Jardin</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="cellar" name="amenities" value="cave">
                            <label for="cellar">Cave / Grenier</label>
                        </div>
                        <div class="checkbox-item">
                            <input type="checkbox" id="pool" name="amenities" value="piscine">
                            <label for="pool">Piscine</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Vues / Caractéristiques spéciales</label>
                    <textarea placeholder="Ex: Vue sur la Seine, Cuisine ouverte, Pierres apparentes..." style="height: 100px;"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="previousStep(1)" class="btn-prev">Retour</button>
                    <button type="submit" class="btn-next">Continuer</button>
                </div>
            </form>
        </div>

        <!-- Step 3: Contact Details (for appraisal) -->
        <div id="step-3" class="form-step" style="display: none;">
            <form onsubmit="submitEstimation(event)">
                
                <div id="bant-section" style="display: none;">
                    <h4 style="margin-bottom: 16px; color: #111827; font-weight: 700;">Qualification (BANT)</h4>

                    <div class="form-group">
                        <label>Budget estimé pour vendre *</label>
                        <select required>
                            <option value="">-- Sélectionner --</option>
                            <option value="150-300k">150 000€ - 300 000€</option>
                            <option value="300-500k">300 000€ - 500 000€</option>
                            <option value="500k-1m">500 000€ - 1 000 000€</option>
                            <option value="1m+">Plus de 1 000 000€</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Qui prend la décision? *</label>
                        <select required>
                            <option value="">-- Sélectionner --</option>
                            <option value="moi">Moi seul</option>
                            <option value="couple">Mon conjoint et moi</option>
                            <option value="famille">Avec ma famille</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Avez-vous un besoin immédiat de vendre? *</label>
                        <select required>
                            <option value="">-- Sélectionner --</option>
                            <option value="oui">Oui, dans les 3 mois</option>
                            <option value="peut-etre">Peut-être, dans 6-12 mois</option>
                            <option value="non">Non, simple curiosité</option>
                            <option value="heritage">Héritage ou succession</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Quel type de vendeur êtes-vous? *</label>
                        <select required>
                            <option value="">-- Sélectionner --</option>
                            <option value="proprietaire">Propriétaire occupant</option>
                            <option value="investisseur">Investisseur</option>
                            <option value="succession">Héritier</option>
                            <option value="other">Autre</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nom complet *</label>
                    <input type="text" placeholder="Ex: Jean Dupont" required>
                </div>

                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" placeholder="Ex: jean@email.com" required>
                </div>

                <div class="form-group">
                    <label>Téléphone *</label>
                    <input type="tel" placeholder="Ex: 06 12 34 56 78" required>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" required style="width: auto;">
                        J'accepte d'être contacté par nos conseillers
                    </label>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="previousStep(2)" class="btn-prev">Retour</button>
                    <button type="submit" class="btn-next">
                        <span id="submit-text">Obtenir l'estimation</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Results -->
        <div id="results" style="display: none;">
            <h3>Résultat de votre estimation</h3>

            <div class="price-grid">
                <div class="price-box bas">
                    <label>Estimation basse</label>
                    <div class="value" id="prix-bas">390 000€</div>
                </div>
                <div class="price-box moyen">
                    <label>Estimation moyenne</label>
                    <div class="value" id="prix-moyen">455 000€</div>
                </div>
                <div class="price-box haut">
                    <label>Estimation haute</label>
                    <div class="value" id="prix-haut">520 000€</div>
                </div>
            </div>

            <div class="estimation-justification">
                <strong>Justification:</strong>
                <p id="justification-text">Basé sur comparables récents et analyse du marché local.</p>
            </div>

            <div id="rdv-button" style="display: none;">
                <button onclick="gotoRDV()" class="btn-estimation primary">
                    <i class="fas fa-calendar"></i> Demander un avis de valeur avec un conseiller
                </button>
            </div>

            <div style="margin-top: 24px;">
                <button onclick="closeEstimation()" class="btn-estimation secondary">
                    Fermer
                </button>
            </div>
        </div>

    </div>
</div>

<script>
let currentEstimationType = 'gratuite';
let currentStep = 1;

function goToStep(type, step) {
    currentEstimationType = type;
    currentStep = step;
    
    // Show form
    document.getElementById('estimation-form').style.display = 'block';
    
    // Hide choice container
    document.querySelector('.estimation-container').style.display = 'none';
    
    // Update form title
    let title = type === 'gratuite' ? 'Votre bien immobilier' : 'Demande d\'avis de valeur';
    document.getElementById('form-title').textContent = title;
    
    // Show/hide BANT section
    document.getElementById('bant-section').style.display = type === 'avis_de_valeur' ? 'block' : 'none';
    
    // Update submit button text
    document.getElementById('submit-text').textContent = 
        type === 'gratuite' ? 'Obtenir l\'estimation' : 'Demander un avis de valeur';
    
    // Show step 1
    showStep(1);
    
    // Smooth scroll
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showStep(step) {
    // Hide all steps
    document.getElementById('step-1').style.display = 'none';
    document.getElementById('step-2').style.display = 'none';
    document.getElementById('step-3').style.display = 'none';
    document.getElementById('results').style.display = 'none';
    
    // Show current step
    document.getElementById('step-' + step).style.display = 'block';
    
    // Update progress
    document.getElementById('progress-2').classList.toggle('active', step >= 2);
    document.getElementById('progress-3').classList.toggle('active', step >= 3);
    
    currentStep = step;
}

function nextStep(e, step) {
    e.preventDefault();
    showStep(step);
}

function previousStep(step) {
    showStep(step);
}

function submitEstimation(e) {
    e.preventDefault();
    
    // Show results
    showStep(0);
    document.getElementById('results').style.display = 'block';
    
    // Show RDV button only for free estimation
    document.getElementById('rdv-button').style.display = currentEstimationType === 'gratuite' ? 'block' : 'none';
    
    // Scroll to results
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function gotoRDV() {
    // Switch to appraisal type and restart
    goToStep('avis_de_valeur', 1);
}

function closeEstimation() {
    // Reset
    document.getElementById('estimation-form').style.display = 'none';
    document.querySelector('.estimation-container').style.display = 'grid';
    currentStep = 1;
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
</script>