<?php
/**
 * /includes/classes/AIContentGenerator.php
 * Générateur de contenu avec Claude API
 * Adapté à la structure O2Switch avec Database.php existant
 */

class AIContentGenerator {
    private $db;
    private $apiKey;
    private $apiUrl = 'https://api.anthropic.com/v1/messages';
    private $model = 'claude-opus-4-5-20251101';
    private $maxTokens = 2000;
    
    public function __construct($database = null) {
        global $db;
        $this->db = $database ?? $db;
        $this->apiKey = getenv('CLAUDE_API_KEY') ?: '';
        
        if (!$this->apiKey) {
            throw new Exception('CLAUDE_API_KEY non configurée');
        }
    }
    
    /**
     * Génère le contenu pour une page
     */
    public function generatePageContent($pageName, $contentType = 'page_intro', $customContext = []) {
        try {
            $prompt = $this->buildPrompt($pageName, $contentType, $customContext);
            $content = $this->callClaudeAPI($prompt);
            return $content;
        } catch (Exception $e) {
            error_log("AIContentGenerator Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Génère tout le contenu d'une page
     */
    public function generateFullPageContent($pageName, $customContext = []) {
        $sections = [
            'hero' => $this->generatePageContent($pageName, 'page_hero', $customContext),
            'intro' => $this->generatePageContent($pageName, 'page_intro', $customContext),
            'features' => $this->generatePageContent($pageName, 'page_features', $customContext),
            'faqs' => $this->generatePageContent($pageName, 'faq_content', $customContext),
            'ctas' => $this->generatePageContent($pageName, 'page_cta', $customContext),
            'seo' => $this->generatePageContent($pageName, 'seo_meta', $customContext)
        ];
        
        return $sections;
    }
    
    /**
     * Construit le prompt avec contexte métier
     */
    private function buildPrompt($pageName, $contentType, $customContext = []) {
        $templates = [
            'page_hero' => "Crée un héro section accrocheur et court pour une page de {page_name}. 
                           Le ton doit être professionnel mais accessible. 
                           Inclus une accroche, un sous-titre et un appel à l'action. 
                           Format: HTML avec balises <h1>, <p>, <button>",
            
            'page_intro' => "Rédige une introduction de 200 mots pour la page {page_name}. 
                            Parle directement aux conseillers immobiliers indépendants. 
                            Basée sur la méthodologie Persona-Contenu-Traffic. 
                            Ton: Professionnel, bienveillant, solution-oriented",
            
            'page_features' => "Liste 5 fonctionnalités clés avec description pour {page_name}. 
                               Format JSON avec structure: {'features': [{'title': '', 'description': '', 'icon': ''}]}",
            
            'faq_content' => "Génère 5 questions-réponses pertinentes pour {page_name}. 
                             Adresse les préoccupations des conseillers immobiliers. 
                             Format JSON: {'faqs': [{'question': '', 'answer': ''}]}",
            
            'page_cta' => "Crée 3 appels à l'action différents pour {page_name}. 
                          Format JSON: {'ctas': [{'text': '', 'style': 'primary|secondary', 'action': ''}]}",
            
            'seo_meta' => "Génère les metas SEO pour {page_name}. 
                          Format JSON: {'title': '', 'description': '', 'keywords': []}"
        ];
        
        $template = $templates[$contentType] ?? $templates['page_intro'];
        $prompt = str_replace('{page_name}', $pageName, $template);
        
        $systemPrompt = "Tu es un expert en marketing immobilier et copywriting pour ÉCOSYSTÈME IMMO LOCAL+.

Mission: Aider les conseillers immobiliers indépendants à maîtriser le marketing digital
Méthodologie: Persona | Contenu | Traffic
Spécialité: Immobilier résidentiel France
Unique Value: Exclusivité territoriale - 1 licence par ville (10km rayon)

Tu génères du contenu de haute qualité, authentique et aligné avec cette mission.
Pas de promesses exagérées, de l'authenticité avant tout.

Contexte supplémentaire: " . json_encode($customContext);
        
        return [
            'system' => $systemPrompt,
            'user' => $prompt
        ];
    }
    
    /**
     * Appel à l'API Claude
     */
    private function callClaudeAPI($prompt) {
        $payload = [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'system' => $prompt['system'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt['user']
                ]
            ]
        ];
        
        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $error = json_decode($response, true);
            throw new Exception('Claude API Error: ' . ($error['error']['message'] ?? 'Unknown error'));
        }
        
        $data = json_decode($response, true);
        return $data['content'][0]['text'] ?? '';
    }
    
    /**
     * Sauvegarde le contenu en base de données
     */
    public function saveContentToDB($pageName, $contentType, $content) {
        try {
            $sql = "INSERT INTO ai_generated_content 
                    (page_name, content_type, content, generated_at) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    content = VALUES(content), 
                    updated_at = NOW()";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$pageName, $contentType, $content]);
        } catch (Exception $e) {
            error_log("Save Content Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère le contenu du cache
     */
    public function getContentFromDB($pageName, $contentType) {
        try {
            $sql = "SELECT content FROM ai_generated_content 
                    WHERE page_name = ? AND content_type = ? 
                    ORDER BY generated_at DESC LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$pageName, $contentType]);
            return $stmt->fetchColumn();
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
                id INT PRIMARY KEY AUTO_INCREMENT,
                page_name VARCHAR(255) NOT NULL,
                content_type VARCHAR(100) NOT NULL,
                content LONGTEXT NOT NULL,
                generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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

?>