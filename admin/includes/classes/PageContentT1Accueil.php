<?php
/**
 * Classe PageContentT1Accueil
 * Gère le contenu du template t1-accueil (Accueil/Home)
 * 
 * @package IMMO_LOCAL_v8_6
 * @version 1.0
 */

class PageContentT1Accueil {
    
    private $db;
    private $table = 'page_content_t1_accueil';
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Récupère tout le contenu t1-accueil
     * @return array|null
     */
    public function getAll() {
        $query = "SELECT * FROM {$this->table} LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère une section spécifique du template
     * @param string $section : 'hero', 'benefits', 'method', 'guide', 'cta'
     * @return array
     */
    public function getSection($section) {
        $content = $this->getAll();
        
        if (!$content) {
            return [];
        }
        
        switch ($section) {
            case 'hero':
                return [
                    'eyebrow' => $content['hero_eyebrow'],
                    'title' => $content['hero_title'],
                    'subtitle' => $content['hero_subtitle'],
                    'cta_text' => $content['hero_cta_text'],
                    'cta_url' => $content['hero_cta_url'],
                    'stat1_num' => $content['hero_stat1_num'],
                    'stat1_lbl' => $content['hero_stat1_lbl'],
                    'stat2_num' => $content['hero_stat2_num'],
                    'stat2_lbl' => $content['hero_stat2_lbl'],
                ];
                
            case 'benefits':
                return [
                    'title' => $content['ben_title'],
                    'ben1_title' => $content['ben1_title'],
                    'ben1_text' => $content['ben1_text'],
                    'ben2_title' => $content['ben2_title'],
                    'ben2_text' => $content['ben2_text'],
                    'ben3_title' => $content['ben3_title'],
                    'ben3_text' => $content['ben3_text'],
                ];
                
            case 'method':
                return [
                    'title' => $content['method_title'],
                    'step1_title' => $content['step1_title'],
                    'step1_text' => $content['step1_text'],
                    'step2_title' => $content['step2_title'],
                    'step2_text' => $content['step2_text'],
                    'step3_title' => $content['step3_title'],
                    'step3_text' => $content['step3_text'],
                ];
                
            case 'guide':
                return [
                    'title' => $content['guide_title'],
                    'g1_title' => $content['g1_title'],
                    'g1_text' => $content['g1_text'],
                    'g2_title' => $content['g2_title'],
                    'g2_text' => $content['g2_text'],
                    'g3_title' => $content['g3_title'],
                    'g3_text' => $content['g3_text'],
                ];
                
            case 'cta':
                return [
                    'title' => $content['cta_title'],
                    'text' => $content['cta_text'],
                    'btn_text' => $content['cta_btn_text'],
                ];
                
            default:
                return $content;
        }
    }
    
    /**
     * Met à jour un champ spécifique
     * @param string $field_name
     * @param string $field_value
     * @return bool
     */
    public function updateField($field_name, $field_value) {
        // Vérifier que le champ existe
        $allowed_fields = [
            'hero_eyebrow', 'hero_title', 'hero_subtitle', 'hero_cta_text', 'hero_cta_url',
            'hero_stat1_num', 'hero_stat1_lbl', 'hero_stat2_num', 'hero_stat2_lbl',
            'ben_title', 'ben1_title', 'ben1_text', 'ben2_title', 'ben2_text', 'ben3_title', 'ben3_text',
            'method_title', 'step1_title', 'step1_text', 'step2_title', 'step2_text', 'step3_title', 'step3_text',
            'guide_title', 'g1_title', 'g1_text', 'g2_title', 'g2_text', 'g3_title', 'g3_text',
            'cta_title', 'cta_text', 'cta_btn_text'
        ];
        
        if (!in_array($field_name, $allowed_fields)) {
            return false;
        }
        
        $query = "UPDATE {$this->table} SET `{$field_name}` = :value, updated_at = NOW() WHERE id = 1";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute([':value' => $field_value]);
    }
    
    /**
     * Met à jour plusieurs champs à la fois
     * @param array $data : ['field_name' => 'value', ...]
     * @return bool
     */
    public function updateMultiple($data) {
        $allowed_fields = [
            'hero_eyebrow', 'hero_title', 'hero_subtitle', 'hero_cta_text', 'hero_cta_url',
            'hero_stat1_num', 'hero_stat1_lbl', 'hero_stat2_num', 'hero_stat2_lbl',
            'ben_title', 'ben1_title', 'ben1_text', 'ben2_title', 'ben2_text', 'ben3_title', 'ben3_text',
            'method_title', 'step1_title', 'step1_text', 'step2_title', 'step2_text', 'step3_title', 'step3_text',
            'guide_title', 'g1_title', 'g1_text', 'g2_title', 'g2_text', 'g3_title', 'g3_text',
            'cta_title', 'cta_text', 'cta_btn_text'
        ];
        
        $set_parts = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowed_fields)) {
                $set_parts[] = "`{$field}` = :{$field}";
                $params[":{$field}"] = $value;
            }
        }
        
        if (empty($set_parts)) {
            return false;
        }
        
        $query = "UPDATE {$this->table} SET " . implode(', ', $set_parts) . ", updated_at = NOW() WHERE id = 1";
        $stmt = $this->db->prepare($query);
        
        return $stmt->execute($params);
    }
    
    /**
     * Récupère les métadonnées (dates création/modification)
     * @return array|null
     */
    public function getMetadata() {
        $query = "SELECT id, created_at, updated_at FROM {$this->table} LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

// ============================================================
// EXEMPLE D'UTILISATION
// ============================================================

/*
// Initialiser la classe
$page_content = new PageContentT1Accueil($db);

// Récupérer tout le contenu
$all_content = $page_content->getAll();

// Récupérer une section spécifique
$hero = $page_content->getSection('hero');
$benefits = $page_content->getSection('benefits');
$method = $page_content->getSection('method');
$guide = $page_content->getSection('guide');
$cta = $page_content->getSection('cta');

// Mettre à jour un champ
$page_content->updateField('hero_title', 'Nouveau titre hero');

// Mettre à jour plusieurs champs
$page_content->updateMultiple([
    'hero_eyebrow' => 'Nouveau eyebrow',
    'hero_subtitle' => 'Nouveau sous-titre',
    'ben1_title' => 'Nouveau bénéfice'
]);

// Récupérer les métadonnées
$meta = $page_content->getMetadata();
*/
?>