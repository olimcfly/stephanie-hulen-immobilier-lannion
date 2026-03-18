<?php
class Article {
    private $db;
    public function __construct() { $this->db = Database::getInstance()->getConnection(); }
    public function getPublished($limit = 10, $offset = 0) {
        $sql = "SELECT * FROM articles WHERE status = 'published' OR status = '1' ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getBySlug($slug) {
        $sql = "SELECT * FROM articles WHERE slug = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function countPublished() {
        $sql = "SELECT COUNT(*) as total FROM articles WHERE status = 'published' OR status = '1'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    public function getAll($limit = 50, $offset = 0) {
        $sql = "SELECT * FROM articles ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM articles";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }
    public function getById($id) {
        $sql = "SELECT * FROM articles WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function save($data) {
        $slug = $data['slug'] ?? slugify($data['title'] ?? '');
        if (isset($data['id']) && $data['id']) {
            $sql = "UPDATE articles SET title = ?, slug = ?, content = ?, meta_title = ?, meta_description = ?, status = ?, updated_at = NOW() WHERE id = ?";
            return $this->db->prepare($sql)->execute([$data['title'], $slug, $data['content'], $data['meta_title'] ?? $data['title'], $data['meta_description'] ?? '', $data['status'] ?? 'draft', $data['id']]);
        } else {
            $sql = "INSERT INTO articles (title, slug, content, meta_title, meta_description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            return $this->db->prepare($sql)->execute([$data['title'], $slug, $data['content'], $data['meta_title'] ?? $data['title'], $data['meta_description'] ?? '', $data['status'] ?? 'draft']);
        }
    }
    public function delete($id) {
        $sql = "DELETE FROM articles WHERE id = ?";
        return $this->db->prepare($sql)->execute([$id]);
    }
}
