<?php
class Lead {
    private $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }
    public function create($data) {
        $sql = "INSERT INTO leads (email, phone, first_name, last_name, city, interest, source, capture_page_id, gdpr_consent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$data['email'], $data['phone'] ?? null, $data['first_name'] ?? null, $data['last_name'] ?? null, $data['city'] ?? null, $data['interest'] ?? null, $data['source'] ?? 'website', $data['capture_page_id'] ?? null, $data['gdpr_consent'] ?? 0]);
    }
    public function getAll($limit = 50, $offset = 0, $status = null) {
        $sql = "SELECT * FROM leads "; $params = [];
        if ($status) { $sql .= "WHERE status = ? "; $params[] = $status; }
        $sql .= "ORDER BY created_at DESC LIMIT ? OFFSET ?"; $params[] = $limit; $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function count($status = null) {
        $sql = "SELECT COUNT(*) as total FROM leads "; $params = [];
        if ($status) { $sql .= "WHERE status = ?"; $params[] = $status; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
}
