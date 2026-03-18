<?php
class Property {
    private $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }
    public function getAvailable($limit = 12, $offset = 0, $filters = []) {
        $sql = "SELECT * FROM biens WHERE status = 'available' OR status = '1' ";
        $params = [];
        if (!empty($filters['city'])) { $sql .= "AND ville = ? "; $params[] = $filters['city']; }
        if (!empty($filters['type'])) { $sql .= "AND type_bien = ? "; $params[] = $filters['type']; }
        if (!empty($filters['min_price'])) { $sql .= "AND prix >= ? "; $params[] = $filters['min_price']; }
        if (!empty($filters['max_price'])) { $sql .= "AND prix <= ? "; $params[] = $filters['max_price']; }
        $sql .= "ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit; $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM biens WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getAll($limit = 50, $offset = 0) {
        $stmt = $this->db->prepare("SELECT * FROM biens ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function countAll() {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM biens");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}
