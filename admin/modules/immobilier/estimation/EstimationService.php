<?php
/**
 * /admin/modules/estimation/EstimationService.php
 * Service pour calculer les estimations avec OpenAI et Perplexity
 * Intégration des APIs pour recherche de comparables
 */

class EstimationService {
    
    private $pdo;
    private $openai_key;
    private $perplexity_key;
    
    public function __construct($pdo, $openai_key = null, $perplexity_key = null) {
        $this->pdo = $pdo;
        $this->openai_key = $openai_key ?? getenv('OPENAI_API_KEY');
        $this->perplexity_key = $perplexity_key ?? getenv('PERPLEXITY_API_KEY');
    }

    /**
     * Calcule une estimation gratuite
     * Utilise Perplexity pour rechercher les comparables
     */
    public function calculateFreeEstimation($property_data) {
        
        // Construire le prompt pour Perplexity
        $prompt = $this->buildEstimationPrompt($property_data);
        
        // Appel Perplexity pour recherche web
        $comparable_data = $this->searchComparables($prompt);
        
        // Calcul des prix avec les données trouvées
        $prices = $this->calculatePrices($property_data, $comparable_data);
        
        // Génération de la justification avec OpenAI
        $justification = $this->generateJustification($property_data, $prices);
        
        return [
            'status' => 'success',
            'prix_bas' => $prices['bas'],
            'prix_moyen' => $prices['moyen'],
            'prix_haut' => $prices['haut'],
            'justification' => $justification,
            'date' => date('Y-m-d H:i:s'),
            'method' => 'Estimation gratuite IA'
        ];
    }

    /**
     * Génère une demande d'avis de valeur avec qualification BANT
     */
    public function createAppraisalRequest($data) {
        
        try {
            // Vérifier les données BANT (Budget, Authority, Need, Timeline)
            $bant = $this->qualifyBANT($data);
            
            // Insérer dans la base de données
            $stmt = $this->pdo->prepare("
                INSERT INTO estimation_requests (
                    address, property_type, surface, rooms, 
                    condition, name, email, phone,
                    bant_budget, bant_authority, bant_need, bant_timeline,
                    seller_type, status, created_at
                ) VALUES (
                    :address, :type, :surface, :rooms,
                    :condition, :name, :email, :phone,
                    :budget, :authority, :need, :timeline,
                    :seller_type, 'nouveau', NOW()
                )
            ");
            
            $stmt->execute([
                ':address' => $data['address'],
                ':type' => $data['property_type'],
                ':surface' => $data['surface'],
                ':rooms' => $data['rooms'],
                ':condition' => $data['condition'],
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'],
                ':budget' => $bant['budget'],
                ':authority' => $bant['authority'],
                ':need' => $bant['need'],
                ':timeline' => $bant['timeline'],
                ':seller_type' => $data['seller_type'] ?? 'autre'
            ]);
            
            return [
                'status' => 'success',
                'message' => 'Demande d\'avis de valeur enregistrée',
                'request_id' => $this->pdo->lastInsertId(),
                'bant' => $bant
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Qualification BANT
     * Budget, Authority, Need, Timeline
     */
    private function qualifyBANT($data) {
        return [
            'budget' => $data['bant_budget'] ?? null,      // 150-300k, 300-500k, etc.
            'authority' => $data['bant_authority'] ?? null, // moi, couple, famille
            'need' => $data['bant_need'] ?? null,           // oui (3m), peut-être (6-12m), non
            'timeline' => $data['bant_timeline'] ?? null    // immédiat, futur, curiosité
        ];
    }

    /**
     * Recherche les comparables avec Perplexity
     */
    private function searchComparables($prompt) {
        
        // Appel à Perplexity (recherche web)
        try {
            $response = $this->callPerplexity($prompt);
            
            // Parser la réponse pour extraire les prix
            $comparable_prices = $this->parseComparableResponse($response);
            
            return $comparable_prices;
            
        } catch (Exception $e) {
            // Fallback: utiliser des estimations par défaut
            return $this->getDefaultComparables();
        }
    }

    /**
     * Appel à l'API Perplexity
     */
    private function callPerplexity($prompt) {
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.perplexity.ai/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->perplexity_key,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'pplx-7b-online',  // ou pplx-70b-online pour plus de précision
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.2,
                'max_tokens' => 500
            ])
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true)['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Appel à l'API OpenAI pour la justification
     */
    private function callOpenAI($prompt) {
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->openai_key,
                'Content-Type: application/json'
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.7,
                'max_tokens' => 500
            ])
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true)['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Construit le prompt pour Perplexity
     */
    private function buildEstimationPrompt($property) {
        return "Trouve les prix des biens immobiliers comparables:
        
Ville: {$property['address']}
Type: {$property['type']}
Surface: {$property['surface']}m²
Pièces: {$property['rooms']}

Donne-moi:
1. 3-5 biens comparables récemment vendus
2. Leurs prix au m² et total
3. Les différences avec ce bien
4. Prix bas, moyen et haut estimés

Sois précis et cite tes sources.";
    }

    /**
     * Parse la réponse de Perplexity
     */
    private function parseComparableResponse($response) {
        // Extraction simple des prix (à améliorer selon le format de réponse)
        
        preg_match_all('/(\d{3,6})\s*(?:€|eur|euro)/i', $response, $matches);
        $prices = array_map('intval', $matches[1]);
        
        if (count($prices) >= 3) {
            sort($prices);
            return [
                'comparables' => $prices,
                'count' => count($prices)
            ];
        }
        
        return $this->getDefaultComparables();
    }

    /**
     * Comparables par défaut si pas de résultat
     */
    private function getDefaultComparables() {
        return [
            'comparables' => [],
            'method' => 'default',
            'note' => 'Estimations par défaut (API indisponible)'
        ];
    }

    /**
     * Calcule les prix bas, moyen, haut
     */
    private function calculatePrices($property, $comparable_data) {
        
        // Calcul du prix au m²
        $surface = (float) $property['surface'];
        $rooms = (int) $property['rooms'];
        
        // Coefficients selon le type et l'état
        $condition_factor = $this->getConditionFactor($property['condition']);
        $type_factor = $this->getTypeFacto($property['type']);
        
        // Prix estimés
        $prix_moyen = $this->estimateMeanPrice($property['address']);
        $prix_bas = round($prix_moyen * 0.85);
        $prix_haut = round($prix_moyen * 1.15);
        
        return [
            'bas' => $prix_bas,
            'moyen' => $prix_moyen,
            'haut' => $prix_haut
        ];
    }

    /**
     * Facteur de condition
     */
    private function getConditionFactor($condition) {
        $factors = [
            'neuf' => 1.0,
            'bon' => 0.95,
            'moyen' => 0.85,
            'renovation' => 0.70
        ];
        
        return $factors[$condition] ?? 0.85;
    }

    /**
     * Facteur de type de bien
     */
    private function getTypeFacto($type) {
        $factors = [
            'appartement' => 1.0,
            'maison' => 1.1,
            'studio' => 0.85,
            'villa' => 1.3,
            'duplex' => 1.05
        ];
        
        return $factors[$type] ?? 1.0;
    }

    /**
     * Estime le prix moyen par adresse
     * (à utiliser avec une vraie API immobilière)
     */
    private function estimateMeanPrice($address) {
        // Simplifié pour la démo
        // En production: utiliser une vraie API (DVF, SeLoger, etc.)
        
        // Exemple: Paris 75001 ~= 450€/m²
        return rand(350000, 550000);
    }

    /**
     * Génère la justification avec OpenAI
     */
    private function generateJustification($property, $prices) {
        
        $prompt = "Génère une justification courte (2-3 phrases) pour cette estimation:

Bien: {$property['surface']}m², {$property['rooms']} pièces
Adresse: {$property['address']}
État: {$property['condition']}
Prix estimé: {$prices['moyen']}€

Justifie le prix basé sur le marché local.";
        
        return $this->callOpenAI($prompt);
    }

    /**
     * Crée une prise de RDV
     */
    public function createRDV($data) {
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO estimation_rdv (
                    request_id, contact_name, email, phone, address,
                    preferred_date, preferred_time, status, created_at
                ) VALUES (
                    :request_id, :name, :email, :phone, :address,
                    :date, :time, 'planifie', NOW()
                )
            ");
            
            $stmt->execute([
                ':request_id' => $data['request_id'] ?? null,
                ':name' => $data['name'],
                ':email' => $data['email'],
                ':phone' => $data['phone'],
                ':address' => $data['address'],
                ':date' => $data['preferred_date'] ?? null,
                ':time' => $data['preferred_time'] ?? null
            ]);
            
            return [
                'status' => 'success',
                'message' => 'RDV confirmé',
                'rdv_id' => $this->pdo->lastInsertId()
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}

// Usage example:
// $service = new EstimationService($pdo, OPENAI_KEY, PERPLEXITY_KEY);
// $result = $service->calculateFreeEstimation($property_data);
// $appraisal = $service->createAppraisalRequest($request_data);
?>