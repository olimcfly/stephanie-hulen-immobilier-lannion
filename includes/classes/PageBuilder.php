<?php
/**
 * CLASSE PageBuilder
 * /includes/classes/PageBuilder.php
 * Gère la création, modification et récupération des pages
 */

class PageBuilder {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Créer une nouvelle page
     */
    public function createPage($title, $slug, $type = 'page') {
        // Vérifier l'unicité du slug
        $stmt = $this->pdo->prepare("SELECT id FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        
        if ($stmt->fetch()) {
            // Slug déjà existant, ajouter un suffixe
            $counter = 1;
            $baseSlug = $slug;
            do {
                $slug = $baseSlug . '-' . (++$counter);
                $stmt->execute([$slug]);
            } while ($stmt->fetch());
        }
        
        $stmt = $this->pdo->prepare("
            INSERT INTO pages (title, slug, status, template, header_enabled, footer_enabled, created_at, updated_at) 
            VALUES (?, ?, 'draft', ?, 1, 1, NOW(), NOW())
        ");
        
        if ($stmt->execute([$title, $slug, $type])) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }
    
    /**
     * Récupérer une page complète
     */
    public function getPage($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM pages WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer une page par slug
     */
    public function getPageBySlug($slug) {
        $stmt = $this->pdo->prepare("SELECT * FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lister toutes les pages
     */
    public function listPages($limit = 50, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT id, title, slug, status, header_enabled, footer_enabled, updated_at, created_at 
            FROM pages 
            ORDER BY updated_at DESC 
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Sauvegarder le contenu HTML
     */
    public function saveContent($pageId, $content, $customCss = null, $customJs = null) {
        $fields = ['content = ?, updated_at = NOW()'];
        $params = [$content, $pageId];
        
        if ($customCss !== null) {
            array_unshift($fields, 'custom_css = ?');
            array_unshift($params, $customCss);
        }
        
        if ($customJs !== null) {
            array_unshift($fields, 'custom_js = ?');
            array_unshift($params, $customJs);
        }
        
        $sql = "UPDATE pages SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Sauvegarder les metas
     */
    public function saveMeta($pageId, $metaTitle, $metaDesc, $keyword = null) {
        $stmt = $this->pdo->prepare("
            UPDATE pages 
            SET meta_title = ?, meta_description = ?, focus_keyword = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$metaTitle, $metaDesc, $keyword, $pageId]);
    }
    
    /**
     * Sauvegarder complètement une page
     */
    public function savePage($pageId, $data) {
        $fields = ['updated_at = NOW()'];
        $params = [];
        
        $allowedFields = ['title', 'slug', 'status', 'content', 'custom_css', 'custom_js', 
                         'meta_title', 'meta_description', 'focus_keyword', 'ai_description',
                         'header_enabled', 'footer_enabled'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        $params[] = $pageId;
        $sql = "UPDATE pages SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Basculer le statut d'une page
     */
    public function toggleStatus($pageId) {
        $page = $this->getPage($pageId);
        if (!$page) return false;
        
        $newStatus = $page['status'] === 'published' ? 'draft' : 'published';
        $stmt = $this->pdo->prepare("UPDATE pages SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$newStatus, $pageId]);
    }
    
    /**
     * Basculer le header
     */
    public function toggleHeader($pageId) {
        $page = $this->getPage($pageId);
        if (!$page) return false;
        
        $newState = $page['header_enabled'] ? 0 : 1;
        $stmt = $this->pdo->prepare("UPDATE pages SET header_enabled = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$newState, $pageId]);
    }
    
    /**
     * Basculer le footer
     */
    public function toggleFooter($pageId) {
        $page = $this->getPage($pageId);
        if (!$page) return false;
        
        $newState = $page['footer_enabled'] ? 0 : 1;
        $stmt = $this->pdo->prepare("UPDATE pages SET footer_enabled = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$newState, $pageId]);
    }
    
    /**
     * Supprimer une page
     */
    public function deletePage($pageId) {
        $stmt = $this->pdo->prepare("DELETE FROM pages WHERE id = ?");
        return $stmt->execute([$pageId]);
    }
}