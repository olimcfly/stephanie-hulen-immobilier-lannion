<?php
/**
 * Module ESTIMATION - Estimation Gratuite
 * /admin/modules/estimation/estimation-gratuite.php
 * Formulaire + Calcul temps réel avec OpenAI + Perplexity
 */

// Récupère l'étape du formulaire
$step = $_GET['step'] ?? 1;
$submitted = isset($_POST['submit']);

// Données simulées pour démo (à connecter à la vraie API)
$sample_result = [
    'address' => '75 Rue de la Paix, 75002 Paris',
    'type' => 'Appartement',
    'surface' => 65,
    'rooms' => 2,
    'condition' => 'bon',
    'prix_bas' => 390000,
    'prix_moyen' => 455000,
    'prix_haut' => 520000,
    'justification' => 'Basé sur 12 biens comparables récemment vendus dans le 2ème arrondissement. Prix moyen: 7000€/m². Votre bien: 65m² × 7000€ = 455 000€. Variations: -15% (condition moyenne) à +15% (bien exposé).',
    'search_data' => [
        'bien_1' => ['adresse' => '10 Rue Réaumur', 'surface' => 72, 'price' => 504000],
        'bien_2' => ['adresse' => '22 Rue Tiquetonne', 'surface' => 58, 'price' => 406000],
        'bien_3' => ['adresse' => '45 Rue Étienne Marcel', 'surface' => 68, 'price' => 476000],
    ]
];

?>

<style>
/* Form Styles */
.form-wrapper {
    max-width: 600px;
    margin: 0 auto 40px;
}

.form-card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}

.form-header {
    margin-bottom: 32px;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 24px;
}

.form-header h2 {
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 8px;
}

.form-header p {
    font-size: 14px;
    color: #6b7280;
}

/* Progress Bar */
.progress-wrapper {
    margin-bottom: 32px;
}

.progress-bar {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
}

.progress-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #e5e7eb;
    transition: all 0.3s ease;
}

.progress-dot.active {
    background: #059669;
    width: 24px;
}

.progress-text {
    font-size: 12px;
    color: #6b7280;
}

/* Form Group */
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
    border-color: #059669;
    box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

/* Button Group */
.button-group {
    display: flex;
    gap: 12px;
    margin-top: 32px;
}

.btn {
    flex: 1;
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-primary {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
}

.btn-secondary {
    background: white;
    color: #374151;
    border: 1px solid #e5e7eb;
}

.btn-secondary:hover {
    background: #f9fafb;
    border-color: #059669;
    color: #059669;
}

/* Result Box */
.result-container {
    max-width: 700px;
    margin: 0 auto;
}

.result-hero {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-radius: 16px;
    padding: 32px;
    text-align: center;
    margin-bottom: 32px;
}

.result-hero h3 {
    font-size: 16px;
    font-weight: 600;
    color: #065f46;
    margin-bottom: 12px;
}

.result-hero p {
    font-size: 14px;
    color: #047857;
}

/* Price Grid */
.price-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 32px;
}

@media (max-width: 768px) {
    .price-grid {
        grid-template-columns: 1fr;
    }
}

.price-box {
    background: white;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
}

.price-box:hover {
    border-color: #059669;
    box-shadow: 0 4px 12px rgba(5, 150, 105, 0.1);
}

.price-box.bas {
    border-left: 4px solid #ef4444;
}

.price-box.moyen {
    border-left: 4px solid #059669;
    background: #f0fdf4;
}

.price-box.haut {
    border-left: 4px solid #10b981;
}

.price-label {
    font-size: 12px;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 12px;
}

.price-value {
    font-size: 32px;
    font-weight: 800;
    color: #059669;
}

.price-unit {
    font-size: 14px;
    color: #6b7280;
    margin-top: 8px;
}

/* Details Box */
.details-box {
    background: #f9fafb;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.details-box h4 {
    font-size: 14px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 16px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e5e7eb;
    font-size: 13px;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #6b7280;
}

.detail-value {
    color: #111827;
    font-weight: 600;
}

/* Justification */
.justification-box {
    background: white;
    border-radius: 12px;
    padding: 24px;
    border-left: 4px solid #059669;
    margin-bottom: 24px;
}

.justification-box h4 {
    font-size: 14px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 12px;
}

.justification-box p {
    font-size: 14px;
    color: #6b7280;
    line-height: 1.6;
}

/* Comparables Section */
.comparables-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
}

.comparables-section h4 {
    font-size: 14px;
    font-weight: 700;
    color: #111827;
    margin-bottom: 16px;
}

.comparable-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 13px;
}

.comparable-address {
    font-weight: 600;
    color: #111827;
}

.comparable-info {
    color: #6b7280;
    margin-top: 4px;
}

.comparable-price {
    font-weight: 700;
    color: #059669;
}

/* CTA */
.cta-section {
    background: linear-gradient(135deg, #f0fdf4 0%, #dbeafe 100%);
    border-radius: 12px;
    padding: 32px;
    text-align: center;
    border: 2px solid #059669;
}

.cta-section h3 {
    font-size: 18px;
    font-weight: 700;
    color: #065f46;
    margin-bottom: 12px;
}

.cta-section p {
    font-size: 14px;
    color: #047857;
    margin-bottom: 20px;
}

.cta-buttons {
    display: flex;
    gap: 12px;
    justify-content: center;
}

@media (max-width: 768px) {
    .cta-buttons {
        flex-direction: column;
    }
}

.btn-cta {
    padding: 12px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-cta.primary {
    background: #059669;
    color: white;
}

.btn-cta.primary:hover {
    background: #047857;
}

.btn-cta.secondary {
    background: white;
    color: #059669;
    border: 2px solid #059669;
}

.btn-cta.secondary:hover {
    background: #f0fdf4;
}

/* Loading State */
.loading {
    text-align: center;
    padding: 40px 20px;
}

.spinner {
    display: inline-block;
    width: 40px;
    height: 40px;
    border: 4px solid #e5e7eb;
    border-top-color: #059669;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    margin-top: 16px;
    font-size: 14px;
    color: #6b7280;
}
</style>

<?php if (!$submitted && $step <= 3): ?>

<!-- FORM SECTION -->
<div class="form-wrapper">
    <div class="form-card">
        
        <!-- Header -->
        <div class="form-header">
            <h2>📋 Estimation Gratuite</h2>
            <p>Entrez les détails de votre bien immobilier pour obtenir une estimation</p>
        </div>
        
        <!-- Progress -->
        <div class="progress-wrapper">
            <div class="progress-bar">
                <div class="progress-dot active"></div>
                <div class="progress-dot <?php echo $step >= 2 ? 'active' : ''; ?>"></div>
                <div class="progress-dot <?php echo $step >= 3 ? 'active' : ''; ?>"></div>
            </div>
            <div class="progress-text">Étape <?php echo $step; ?>/3</div>
        </div>
        
        <!-- Form -->
        <form method="POST" onsubmit="showLoading()">
            
            <?php if ($step == 1): ?>
            <!-- STEP 1: Propriété -->
            <h3 style="margin-bottom: 24px; font-size: 16px; color: #111827; font-weight: 700;">Votre Propriété</h3>
            
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
                        <option value="loft">Loft</option>
                        <option value="villa">Villa</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>État du bien *</label>
                    <select name="condition" required>
                        <option value="">Sélectionner...</option>
                        <option value="neuf">Neuf</option>
                        <option value="bon">Bon état</option>
                        <option value="moyen">État moyen</option>
                        <option value="renovation">À rénover</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Surface habitable (m²) *</label>
                    <input type="number" name="surface" placeholder="Ex: 65" min="1" required>
                </div>
                <div class="form-group">
                    <label>Nombre de pièces *</label>
                    <input type="number" name="rooms" placeholder="Ex: 2" min="1" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description/Détails supplémentaires</label>
                <textarea name="details" placeholder="Ex: Terrasse, parking, cuisine ouverte..." style="height: 100px;"></textarea>
            </div>
            
            <?php elseif ($step == 2): ?>
            <!-- STEP 2: Plus de détails -->
            <h3 style="margin-bottom: 24px; font-size: 16px; color: #111827; font-weight: 700;">Détails Complémentaires</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Étage (le cas échéant)</label>
                    <input type="text" name="floor" placeholder="Ex: 3ème étage">
                </div>
                <div class="form-group">
                    <label>Année de construction</label>
                    <input type="number" name="year_built" placeholder="Ex: 1995" min="1800">
                </div>
            </div>
            
            <div class="form-group">
                <label>Équipements/Aménagements</label>
                <div style="display: grid; gap: 8px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                        <input type="checkbox" name="amenities" value="terrasse"> Terrasse/Balcon
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                        <input type="checkbox" name="amenities" value="parking"> Parking/Garage
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                        <input type="checkbox" name="amenities" value="jardin"> Jardin
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-weight: normal;">
                        <input type="checkbox" name="amenities" value="cave"> Cave/Grenier
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Vous recherchez une estimation pour...</label>
                <select name="purpose" required>
                    <option value="">Sélectionner...</option>
                    <option value="vente">Vendre</option>
                    <option value="achat">Acheter/Référence</option>
                    <option value="assurance">Assurance/Assurance</option>
                    <option value="curiosité">Simple curiosité</option>
                </select>
            </div>
            
            <?php elseif ($step == 3): ?>
            <!-- STEP 3: Contact -->
            <h3 style="margin-bottom: 24px; font-size: 16px; color: #111827; font-weight: 700;">Vos Informations</h3>
            
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
                    <label>Téléphone</label>
                    <input type="tel" name="phone" placeholder="Ex: 06 12 34 56 78">
                </div>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: flex-start; gap: 8px; font-weight: normal;">
                    <input type="checkbox" name="contact_ok" value="1" required style="margin-top: 4px;">
                    J'accepte d'être contacté par nos conseillers immobiliers
                </label>
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: flex-start; gap: 8px; font-weight: normal;">
                    <input type="checkbox" name="privacy_ok" value="1" required style="margin-top: 4px;">
                    J'accepte la politique de confidentialité
                </label>
            </div>
            
            <?php endif; ?>
            
            <!-- Buttons -->
            <div class="button-group">
                <?php if ($step > 1): ?>
                <button type="button" class="btn btn-secondary" onclick="previousStep()">← Retour</button>
                <?php endif; ?>
                
                <?php if ($step < 3): ?>
                <button type="button" class="btn btn-primary" onclick="nextStep()">Continuer →</button>
                <?php else: ?>
                <button type="submit" name="submit" class="btn btn-primary">Obtenir l'estimation</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php else: ?>

<!-- RESULTS SECTION -->
<div class="result-container">
    
    <!-- Loading State (simule le calcul) -->
    <div class="loading" id="loading-state" style="display: none;">
        <div class="spinner"></div>
        <div class="loading-text">Calcul de votre estimation...</div>
        <p style="font-size: 12px; color: #9ca3af; margin-top: 12px;">OpenAI + Perplexity analysent le marché...</p>
    </div>
    
    <!-- Results -->
    <div id="results-content">
        <!-- Hero -->
        <div class="result-hero">
            <h3>✅ Estimation Calculée</h3>
            <p><?php echo $sample_result['address']; ?></p>
        </div>
        
        <!-- Property Details -->
        <div class="details-box">
            <h4>📊 Détails du Bien</h4>
            <div class="detail-row">
                <span class="detail-label">Type:</span>
                <span class="detail-value"><?php echo ucfirst($sample_result['type']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Surface:</span>
                <span class="detail-value"><?php echo $sample_result['surface']; ?>m²</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Pièces:</span>
                <span class="detail-value"><?php echo $sample_result['rooms']; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">État:</span>
                <span class="detail-value"><?php echo ucfirst($sample_result['condition']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Prix/m²:</span>
                <span class="detail-value"><?php echo number_format($sample_result['prix_moyen'] / $sample_result['surface'], 0); ?>€</span>
            </div>
        </div>
        
        <!-- Price Grid -->
        <h3 style="margin-bottom: 20px; font-size: 16px; color: #111827; font-weight: 700;">💰 Estimation des Prix</h3>
        <div class="price-grid">
            <div class="price-box bas">
                <div class="price-label">Estimation Basse</div>
                <div class="price-value"><?php echo number_format($sample_result['prix_bas'], 0); ?></div>
                <div class="price-unit">€</div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 8px;">-15%</div>
            </div>
            
            <div class="price-box moyen">
                <div class="price-label">Estimation Moyenne</div>
                <div class="price-value"><?php echo number_format($sample_result['prix_moyen'], 0); ?></div>
                <div class="price-unit">€</div>
                <div style="font-size: 12px; color: #047857; margin-top: 8px;">📍 Recommandée</div>
            </div>
            
            <div class="price-box haut">
                <div class="price-label">Estimation Haute</div>
                <div class="price-value"><?php echo number_format($sample_result['prix_haut'], 0); ?></div>
                <div class="price-unit">€</div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 8px;">+15%</div>
            </div>
        </div>
        
        <!-- Justification -->
        <div class="justification-box">
            <h4>📝 Justification</h4>
            <p><?php echo $sample_result['justification']; ?></p>
        </div>
        
        <!-- Comparables -->
        <div class="comparables-section">
            <h4>🏢 Biens Comparables Trouvés</h4>
            <?php foreach ($sample_result['search_data'] as $bien): ?>
            <div class="comparable-item">
                <div>
                    <div class="comparable-address"><?php echo $bien['adresse']; ?></div>
                    <div class="comparable-info"><?php echo $bien['surface']; ?>m² • <?php echo number_format($bien['price'], 0); ?>€</div>
                </div>
                <div class="comparable-price"><?php echo number_format($bien['price'] / $bien['surface'], 0); ?>€/m²</div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- CTA -->
        <div class="cta-section">
            <h3>Intéressé par un avis professionnel?</h3>
            <p>Obtenez une analyse complète avec un conseiller immobilier expert de votre région</p>
            <div class="cta-buttons">
                <a href="/admin/dashboard.php?page=estimation&action=professional-valuation" class="btn-cta primary">
                    Demander un avis de valeur
                </a>
                <a href="/admin/dashboard.php?page=estimation" class="btn-cta secondary">
                    Nouvelle estimation
                </a>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<script>
function nextStep() {
    const step = <?php echo $step; ?>;
    window.location.href = '?page=estimation&action=free-estimation&step=' + (step + 1);
}

function previousStep() {
    const step = <?php echo $step; ?>;
    if (step > 1) {
        window.location.href = '?page=estimation&action=free-estimation&step=' + (step - 1);
    }
}

function showLoading() {
    document.getElementById('loading-state').style.display = 'block';
    document.getElementById('results-content').style.display = 'none';
    
    // Simule 3 secondes de calcul
    setTimeout(() => {
        document.getElementById('loading-state').style.display = 'none';
        document.getElementById('results-content').style.display = 'block';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }, 3000);
}
</script>