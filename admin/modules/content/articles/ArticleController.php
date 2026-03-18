<?php
/**
 * ArticleController - Gestion des articles/blog
 * Importe et gère les articles existants depuis la base de données
 */

class ArticleController {
    
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère tous les articles avec pagination
     */
    public function getArticles($page = 1, $perPage = 20, $search = '', $status = null) {
        try {
            $offset = ($page - 1) * $perPage;
            
            $where = [];
            $params = [];
            
            // Filtre recherche
            if (!empty($search)) {
                $where[] = "(title LIKE ? OR content LIKE ? OR slug LIKE ?)";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
                $params[] = "%{$search}%";
            }
            
            // Filtre statut
            if ($status) {
                $where[] = "status = ?";
                $params[] = $status;
            }
            
            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
            
            // Requête articles
            $query = "SELECT * FROM articles 
                      {$whereClause}
                      ORDER BY created_at DESC 
                      LIMIT ? OFFSET ?";
            
            $params[] = $perPage;
            $params[] = $offset;
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Requête total
            $countQuery = "SELECT COUNT(*) as total FROM articles {$whereClause}";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute(array_slice($params, 0, count($params) - 2));
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'articles' => $articles,
                'total' => $total,
                'pages' => ceil($total / $perPage),
                'current_page' => $page,
                'per_page' => $perPage
            ];
            
        } catch (Exception $e) {
            error_log("Erreur getArticles: " . $e->getMessage());
            return ['articles' => [], 'total' => 0, 'pages' => 0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Récupère un article par ID
     */
    public function getArticleById($id) {
        try {
            $query = "SELECT * FROM articles WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($article && is_string($article['metadata'])) {
                $article['metadata'] = json_decode($article['metadata'], true) ?: [];
            }
            
            return $article;
            
        } catch (Exception $e) {
            error_log("Erreur getArticleById: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère un article par slug
     */
    public function getArticleBySlug($slug) {
        try {
            $query = "SELECT * FROM articles WHERE slug = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$slug]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($article && is_string($article['metadata'])) {
                $article['metadata'] = json_decode($article['metadata'], true) ?: [];
            }
            
            return $article;
            
        } catch (Exception $e) {
            error_log("Erreur getArticleBySlug: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crée un nouvel article
     */
    public function createArticle($data) {
        try {
            // Validation
            if (empty($data['title']) || empty($data['slug'])) {
                return ['success' => false, 'message' => 'Titre et slug obligatoires'];
            }
            
            // Vérifier slug unique
            if ($this->slugExists($data['slug'])) {
                return ['success' => false, 'message' => 'Ce slug existe déjà'];
            }
            
            // Préparer les données
            $title = $data['title'];
            $slug = $data['slug'];
            $content = $data['content'] ?? '';
            $excerpt = $data['excerpt'] ?? '';
            $status = $data['status'] ?? 'draft';
            $featured = $data['featured'] ?? 0;
            
            // Métadonnées réelles immo
            $metadata = [
                'persona' => $data['persona'] ?? '',
                'ville' => $data['ville'] ?? '',
                'niveau_conscience' => $data['niveau_conscience'] ?? '',
                'meta_title' => $data['meta_title'] ?? $title,
                'meta_description' => $data['meta_description'] ?? substr($excerpt, 0, 160),
                'keywords' => $data['keywords'] ?? ''
            ];
            
            $metadataJson = json_encode($metadata);
            
            $query = "INSERT INTO articles 
                      (title, slug, content, excerpt, metadata, status, featured, created_at, updated_at)
                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $title, $slug, $content, $excerpt, $metadataJson, $status, $featured
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Article créé avec succès',
                    'id' => $this->db->lastInsertId()
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la création'];
            
        } catch (Exception $e) {
            error_log("Erreur createArticle: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Met à jour un article
     */
    public function updateArticle($id, $data) {
        try {
            $article = $this->getArticleById($id);
            if (!$article) {
                return ['success' => false, 'message' => 'Article non trouvé'];
            }
            
            // Préparer les données
            $title = $data['title'] ?? $article['title'];
            $slug = $data['slug'] ?? $article['slug'];
            $content = $data['content'] ?? $article['content'];
            $excerpt = $data['excerpt'] ?? $article['excerpt'];
            $status = $data['status'] ?? $article['status'];
            $featured = $data['featured'] ?? $article['featured'];
            
            // Métadonnées
            $metadata = $article['metadata'] ?? [];
            $metadata['persona'] = $data['persona'] ?? $metadata['persona'] ?? '';
            $metadata['ville'] = $data['ville'] ?? $metadata['ville'] ?? '';
            $metadata['niveau_conscience'] = $data['niveau_conscience'] ?? $metadata['niveau_conscience'] ?? '';
            $metadata['meta_title'] = $data['meta_title'] ?? $title;
            $metadata['meta_description'] = $data['meta_description'] ?? substr($excerpt, 0, 160);
            $metadata['keywords'] = $data['keywords'] ?? $metadata['keywords'] ?? '';
            
            $metadataJson = json_encode($metadata);
            
            $query = "UPDATE articles 
                      SET title = ?, slug = ?, content = ?, excerpt = ?, metadata = ?, 
                          status = ?, featured = ?, updated_at = NOW()
                      WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $title, $slug, $content, $excerpt, $metadataJson, $status, $featured, $id
            ]);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Article mis à jour avec succès'
                ];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
            
        } catch (Exception $e) {
            error_log("Erreur updateArticle: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Supprime un article
     */
    public function deleteArticle($id) {
        try {
            $article = $this->getArticleById($id);
            if (!$article) {
                return ['success' => false, 'message' => 'Article non trouvé'];
            }
            
            $query = "DELETE FROM articles WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Article supprimé avec succès'];
            }
            
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
            
        } catch (Exception $e) {
            error_log("Erreur deleteArticle: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Vérifie si un slug existe
     */
    private function slugExists($slug, $excludeId = null) {
        try {
            $query = "SELECT id FROM articles WHERE slug = ?";
            if ($excludeId) {
                $query .= " AND id != ?";
                $stmt = $this->db->prepare($query);
                $stmt->execute([$slug, $excludeId]);
            } else {
                $stmt = $this->db->prepare($query);
                $stmt->execute([$slug]);
            }
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Récupère les statistiques des articles
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Total
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM articles");
            $stmt->execute();
            $stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Par statut
            $stmt = $this->db->prepare("SELECT status, COUNT(*) as count FROM articles GROUP BY status");
            $stmt->execute();
            $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stats['by_status'] = [];
            foreach ($statuses as $s) {
                $stats['by_status'][$s['status']] = $s['count'];
            }
            
            // Vedettes
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM articles WHERE featured = 1");
            $stmt->execute();
            $stats['featured'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Récents (7 jours)
            $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM articles WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stmt->execute();
            $stats['recent'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Erreur getStats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère articles par ville (immobilier)
     */
    public function getArticlesByVille($ville, $limit = 10) {
        try {
            $query = "SELECT * FROM articles 
                      WHERE JSON_CONTAINS(metadata, ?, '$.ville')
                      AND status = 'published'
                      ORDER BY created_at DESC
                      LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([json_encode($ville), $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur getArticlesByVille: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère articles par persona
     */
    public function getArticlesByPersona($persona, $limit = 10) {
        try {
            $query = "SELECT * FROM articles 
                      WHERE JSON_CONTAINS(metadata, ?, '$.persona')
                      AND status = 'published'
                      ORDER BY created_at DESC
                      LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([json_encode($persona), $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Erreur getArticlesByPersona: " . $e->getMessage());
            return [];
        }
    }
}
?>