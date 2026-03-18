<?php
/**
 * Section Model
 * /includes/classes/Section.php
 */

class Section {
    private $db;
    private $table = 'pages_sections';
    
    public function __construct($pdo) {
        $this->db = $pdo;
    }
    
    /**
     * Récupérer toutes les sections d'une page
     */
    public function getByPageId($pageId) {
        $sql = "SELECT * FROM {$this->table} WHERE page_id = ? ORDER BY `order` ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pageId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupérer une section
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Créer une section
     */
    public function create($pageId, $type, $data, $order = 0) {
        $dataJson = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
        
        $sql = "INSERT INTO {$this->table} (page_id, type, data, `order`) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pageId, $type, $dataJson, $order]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Mettre à jour une section
     */
    public function update($id, $data) {
        $dataJson = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data;
        
        $sql = "UPDATE {$this->table} SET data = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$dataJson, $id]);
    }
    
    /**
     * Mettre à jour l'ordre
     */
    public function updateOrder($id, $order) {
        $sql = "UPDATE {$this->table} SET `order` = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$order, $id]);
    }
    
    /**
     * Supprimer une section
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Supprimer toutes les sections d'une page
     */
    public function deleteByPageId($pageId) {
        $sql = "DELETE FROM {$this->table} WHERE page_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$pageId]);
    }
    
    /**
     * Sauvegarder l'ordre des sections (bulk)
     */
    public function saveOrder($orders) {
        $sql = "UPDATE {$this->table} SET `order` = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        foreach ($orders as $id => $order) {
            $stmt->execute([$order, $id]);
        }
        
        return true;
    }
    
    /**
     * Compter les sections d'une page
     */
    public function countByPageId($pageId) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE page_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pageId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}
?>