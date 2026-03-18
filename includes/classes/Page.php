<?php
class Page {
    private $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }
    public function getBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM pages WHERE slug = ? AND (status = 'published' OR status = '1') LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getAll($limit = 50, $offset = 0) {
        $stmt = $this->db->prepare("SELECT * FROM pages ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
