<?php
/**
 * /includes/classes/AIContentGenerator.php
 * Générateur de contenu avec Claude API
 */

class AIContentGenerator {
    private $db;
    private $apiKey;
    private $apiUrl = 'https://api.anthropic.com/v1/messages';
    private $model = 'claude-sonnet-4-6';
    private $maxTokens = 2000;
    
    public function __construct($database = null) {
        global $db;
        $this->db = $database ?? $db;
        
        // Récupération de la clé API depuis la table settings
        try {
            $stmt = $this->db->prepare(
                "SELECT setting_value FROM settings WHERE setting_key = 'claude_api_key' LIMIT 1"
            );
            $stmt->execute();
            $this->apiKey = $stmt->fetchColumn() ?: '';
        } catch (Exception $e) {
            $this->apiKey = '';
        }
        
        if (!$this->apiKey) {
            throw new Exception('Clé API Claude non configurée dans Réglages');
        }
    }
    
    /**
     * Génère le contenu pour une section
     */
    public function generatePageContent($pageName, $contentType = 'page_intro', $customContext = []) {
        try {
            $prompt = $this->buildPrompt($pageName, $contentType, $customContext);
            return $this->callClaudeAPI($prompt);
        } catch (Exception $e) {
            error_log("AIContentGenerator Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Génère tout le contenu d'une page (6 sections)
     */
    public function generateFullPageContent($pageName, $customContext = []) {
        return [
            'hero'     => $this->generatePageContent($pageName, 'page_hero',     $customContext),
            'intro'    => $this->generatePageContent($pageName, 'page_intro',    $customContext),
            'features' => $this->generatePageContent($pageName, 'page_features', $customContext),
            'faqs'     => $this->generatePageContent($pageName, 'faq_content',   $customContext),
            'ctas'     => $this->generatePageContent($pageName, 'page_cta',      $customContext),
            'seo'      => $this->generatePageContent($pageName, 'seo_meta',      $customContext),
        ];
    }

    /**
     * Méthode générique pour n'importe quel prompt custom
     * Utilisée par les autres modules (articles, SEO, CRM...)
     */
    public function generate($systemPrompt, $userPrompt, $maxTokens = null) {
        $payload = [
            'system'  => $systemPrompt,
            'user'    => $userPrompt
        ];
        if ($maxTokens) $this->maxTokens = $maxTokens;
        return $this->callClaudeAPI($payload);
    }
    
    /**
     * Construit le prompt avec contexte métier
     */
    private function buildPrompt($pageName, $contentType, $customContext = []) {
        $tone        = $customContext['tone']             ?? 'professionnel';
        $audience    = $customContext['target_audience']  ?? 'Conseillers immobiliers indépendants';
        $description = $customContext['page_description'] ?? '';

        $templates = [
            'page_hero' => 
                "Crée une section héro courte et percutante pour la page « {page_name} ».
                Ton: {tone}. Public: {audience}.
                Inclus: une accroche H1 forte, un sous-titre P, un bouton CTA.
                Format: HTML avec balises <h1>, <p>, <a class=\"btn-primary\">.",

            'page_intro' => 
                "Rédige une introduction de 200 mots pour la page « {page_name} ».
                Ton: {tone}. Public cible: {audience}.
                Basée sur la méthodologie Persona | Contenu | Traffic.
                Parle directement au lecteur (tu/vous selon ton choisi).
                {description}",

            'page_features' => 
                "Liste 5 fonctionnalités ou bénéfices clés pour la page « {page_name} ».
                Public: {audience}. Ton: {tone}.
                Réponds UNIQUEMENT en JSON valide:
                {\"features\": [{\"title\": \"\", \"description\": \"\", \"icon\": \"\"}]}",

            'faq_content' => 
                "Génère 5 questions-réponses pertinentes pour la page « {page_name} ».
                Public: {audience}. Ton: {tone}.
                Réponds UNIQUEMENT en JSON valide:
                {\"faqs\": [{\"question\": \"\", \"answer\": \"\"}]}",

            'page_cta' => 
                "Crée 3 appels à l'action différents pour la page « {page_name} ».
                Public: {audience}. Ton: {tone}.
                Réponds UNIQUEMENT en JSON valide:
                {\"ctas\": [{\"text\": \"\", \"style\": \"primary\", \"action\": \"\"}]}",

            'seo_meta' => 
                "Génère les balises SEO optimisées pour la page « {page_name} ».
                Public: {audience}. Ville cible selon contexte.
                Réponds UNIQUEMENT en JSON valide:
                {\"title\": \"\", \"description\": \"\", \"keywords\": []}",
        ];

        $template = $templates[$contentType] ?? $templates['page_intro'];
        $userPrompt = str_replace(
            ['{page_name}', '{tone}', '{audience}', '{description}'],
            [$pageName,     $tone,    $audience,    $description],
            $template
        );

        $systemPrompt = "Tu es un expert en marketing immobilier et copywriting pour ÉCOSYSTÈME IMMO LOCAL+.

Mission : Aider les conseillers immobiliers indépendants à maîtriser le marketing digital.
Méthodologie : Persona | Contenu | Traffic.
Valeur unique : Exclusivité territoriale — 1 licence par ville (rayon 10 km).
Spécialité : Immobilier résidentiel en France.

Tu génères du contenu authentique, de haute qualité, sans promesses exagérées.
Quand le format demandé est JSON, tu renvoies UNIQUEMENT du JSON valide, sans texte autour.";

        return [
            'system' => $systemPrompt,
            'user'   => $userPrompt
        ];
    }
    
    /**
     * Appel à l'API Claude
     */
    private function callClaudeAPI($prompt) {
        $payload = [
            'model'      => $this->model,
            'max_tokens' => $this->maxTokens,
            'system'     => $prompt['system'],
            'messages'   => [
                ['role' => 'user', 'content' => $prompt['user']]
            ]
        ];
        
        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT    => 60,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception('cURL Error: ' . $curlError);
        }
        
        if ($httpCode !== 200) {
            $error = json_decode($response, true);
            throw new Exception('Claude API ' . $httpCode . ': ' . ($error['error']['message'] ?? $response));
        }
        
        $data = json_decode($response, true);
        return $data['content'][0]['text'] ?? '';
    }
    
    /**
     * Sauvegarde en base de données
     */
    public function saveContentToDB($pageName, $contentType, $content) {
        try {
            $sql = "INSERT INTO ai_generated_content 
                        (page_name, content_type, content, generated_at) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        content    = VALUES(content), 
                        updated_at = NOW()";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $pageName,
                $contentType,
                is_array($content) ? json_encode($content, JSON_UNESCAPED_UNICODE) : $content
            ]);
        } catch (Exception $e) {
            error_log("Save Content Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère depuis le cache DB
     */
    public function getContentFromDB($pageName, $contentType) {
        try {
            $sql = "SELECT content FROM ai_generated_content 
                    WHERE page_name = ? AND content_type = ? 
                    ORDER BY generated_at DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$pageName, $contentType]);
            return $stmt->fetchColumn() ?: null;
        } catch (Exception $e) {
            error_log("Get Content Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Crée la table si elle n'existe pas
     */
    public function createTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS ai_generated_content (
                id           INT PRIMARY KEY AUTO_INCREMENT,
                page_name    VARCHAR(255) NOT NULL,
                content_type VARCHAR(100) NOT NULL,
                content      LONGTEXT NOT NULL,
                generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_content (page_name, content_type)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $this->db->exec($sql);
            return true;
        } catch (Exception $e) {
            error_log("Create Table Error: " . $e->getMessage());
            return false;
        }
    }
}