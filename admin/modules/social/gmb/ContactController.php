<?php
/**
 * ContactController - Gestion des contacts GMB et listes
 * Module : admin/modules/gmb/ContactController.php
 * 
 * DB réelle :
 * - gmb_contacts : rating, reviews_count, contact_name, prospect_status (FR), partnership_type
 * - gmb_contact_lists : name, color, icon, contacts_count
 * - gmb_contact_list_members : contact_id, list_id
 */

class ContactController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ============================================================
    // CONTACTS CRUD
    // ============================================================

    /**
     * Liste paginée des contacts avec filtres
     */
    public function getContacts(array $filters = []): array
    {
        $where = ['1=1'];
        $params = [];
        $page = max(1, (int)($filters['page'] ?? 1));
        $perPage = min(100, max(10, (int)($filters['per_page'] ?? 25)));
        $offset = ($page - 1) * $perPage;

        // Filtre par type
        if (!empty($filters['contact_type'])) {
            $where[] = 'c.contact_type = ?';
            $params[] = $filters['contact_type'];
        }

        // Filtre par statut prospect
        if (!empty($filters['prospect_status'])) {
            $where[] = 'c.prospect_status = ?';
            $params[] = $filters['prospect_status'];
        }

        // Filtre par statut email
        if (!empty($filters['email_status'])) {
            $where[] = 'c.email_status = ?';
            $params[] = $filters['email_status'];
        }

        // Filtre par ville
        if (!empty($filters['city'])) {
            $where[] = 'c.city LIKE ?';
            $params[] = '%' . $filters['city'] . '%';
        }

        // Filtre par partnership_type
        if (!empty($filters['partnership_type'])) {
            $where[] = 'c.partnership_type = ?';
            $params[] = $filters['partnership_type'];
        }

        // Filtre par liste
        if (!empty($filters['list_id'])) {
            $where[] = 'c.id IN (SELECT contact_id FROM gmb_contact_list_members WHERE list_id = ?)';
            $params[] = (int)$filters['list_id'];
        }

        // Recherche texte
        if (!empty($filters['search'])) {
            $where[] = '(c.business_name LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR c.contact_name LIKE ? OR c.city LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            $params = array_merge($params, [$s, $s, $s, $s, $s]);
        }

        // Filtre emails valides uniquement
        if (!empty($filters['valid_email_only'])) {
            $where[] = "c.email_status = 'valid'";
        }

        // Filtre avec email
        if (!empty($filters['has_email'])) {
            $where[] = "c.email IS NOT NULL AND c.email != ''";
        }

        $whereClause = implode(' AND ', $where);

        // Tri
        $orderBy = 'c.scraped_at DESC';
        if (!empty($filters['sort'])) {
            $allowedSorts = [
                'name' => 'c.business_name ASC',
                'rating' => 'c.rating DESC',
                'recent' => 'c.scraped_at DESC',
                'city' => 'c.city ASC',
                'status' => 'c.prospect_status ASC',
            ];
            $orderBy = $allowedSorts[$filters['sort']] ?? $orderBy;
        }

        // Compter total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM gmb_contacts c WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        // Récupérer les contacts
        $stmt = $this->db->prepare("
            SELECT c.* 
            FROM gmb_contacts c
            WHERE {$whereClause}
            ORDER BY {$orderBy}
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'contacts' => $contacts,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }

    /**
     * Récupérer un contact par ID
     */
    public function getContact(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM gmb_contacts WHERE id = ?");
        $stmt->execute([$id]);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$contact) return null;

        // Récupérer les listes du contact
        $listStmt = $this->db->prepare("
            SELECT cl.* FROM gmb_contact_lists cl
            INNER JOIN gmb_contact_list_members clm ON cl.id = clm.list_id
            WHERE clm.contact_id = ?
        ");
        $listStmt->execute([$id]);
        $contact['lists'] = $listStmt->fetchAll(PDO::FETCH_ASSOC);

        // Récupérer l'historique des emails envoyés
        $emailStmt = $this->db->prepare("
            SELECT es.*, seq.name as sequence_name 
            FROM gmb_email_sends es
            LEFT JOIN gmb_email_sequences seq ON es.sequence_id = seq.id
            WHERE es.contact_id = ?
            ORDER BY es.created_at DESC
            LIMIT 20
        ");
        $emailStmt->execute([$id]);
        $contact['email_history'] = $emailStmt->fetchAll(PDO::FETCH_ASSOC);

        return $contact;
    }

    /**
     * Mettre à jour un contact
     */
    public function updateContact(int $id, array $data): bool
    {
        $allowed = [
            'business_name', 'business_category', 'phone', 'email', 'secondary_email',
            'secondary_phone', 'contact_name', 'contact_type', 'prospect_status',
            'partnership_type', 'partner_reference', 'notes', 'tags',
            'address', 'city', 'postal_code', 'website',
        ];

        $sets = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $sets[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        if (empty($sets)) return false;

        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE gmb_contacts SET " . implode(', ', $sets) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    /**
     * Supprimer un contact
     */
    public function deleteContact(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM gmb_contacts WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Supprimer plusieurs contacts
     */
    public function deleteContacts(array $ids): int
    {
        if (empty($ids)) return 0;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("DELETE FROM gmb_contacts WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    /**
     * Mise à jour en masse du statut
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        if (empty($ids)) return 0;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$status], $ids);
        $stmt = $this->db->prepare("UPDATE gmb_contacts SET prospect_status = ? WHERE id IN ({$placeholders})");
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Mise à jour en masse du type de contact
     */
    public function bulkUpdateType(array $ids, string $type): int
    {
        if (empty($ids)) return 0;
        
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([$type], $ids);
        $stmt = $this->db->prepare("UPDATE gmb_contacts SET contact_type = ? WHERE id IN ({$placeholders})");
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // ============================================================
    // LISTES DE CONTACTS
    // ============================================================

    /**
     * Toutes les listes
     */
    public function getLists(): array
    {
        $stmt = $this->db->query("
            SELECT cl.*, 
                   (SELECT COUNT(*) FROM gmb_contact_list_members clm WHERE clm.list_id = cl.id) as real_count
            FROM gmb_contact_lists cl 
            ORDER BY cl.name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Créer une liste
     */
    public function createList(string $name, string $description = '', string $color = '#3B82F6', string $icon = 'folder'): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO gmb_contact_lists (name, description, color, icon) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $description, $color, $icon]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Modifier une liste
     */
    public function updateList(int $id, array $data): bool
    {
        $allowed = ['name', 'description', 'color', 'icon'];
        $sets = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $sets[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        if (empty($sets)) return false;

        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE gmb_contact_lists SET " . implode(', ', $sets) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    /**
     * Supprimer une liste
     */
    public function deleteList(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM gmb_contact_lists WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Ajouter des contacts à une liste
     */
    public function addToList(int $listId, array $contactIds): int
    {
        $added = 0;
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO gmb_contact_list_members (contact_id, list_id) VALUES (?, ?)
        ");
        
        foreach ($contactIds as $contactId) {
            $stmt->execute([(int)$contactId, $listId]);
            $added += $stmt->rowCount();
        }
        
        $this->updateListCount($listId);
        return $added;
    }

    /**
     * Retirer des contacts d'une liste
     */
    public function removeFromList(int $listId, array $contactIds): int
    {
        if (empty($contactIds)) return 0;
        
        $placeholders = implode(',', array_fill(0, count($contactIds), '?'));
        $params = array_merge([$listId], $contactIds);
        $stmt = $this->db->prepare("DELETE FROM gmb_contact_list_members WHERE list_id = ? AND contact_id IN ({$placeholders})");
        $stmt->execute($params);
        
        $this->updateListCount($listId);
        return $stmt->rowCount();
    }

    /**
     * Mettre à jour le compteur de la liste
     */
    private function updateListCount(int $listId): void
    {
        $stmt = $this->db->prepare("
            UPDATE gmb_contact_lists 
            SET contacts_count = (SELECT COUNT(*) FROM gmb_contact_list_members WHERE list_id = ?)
            WHERE id = ?
        ");
        $stmt->execute([$listId, $listId]);
    }

    // ============================================================
    // STATISTIQUES
    // ============================================================

    /**
     * Stats globales
     */
    public function getStats(): array
    {
        $stats = [];
        
        $stats['total'] = (int)$this->db->query("SELECT COUNT(*) FROM gmb_contacts")->fetchColumn();
        $stats['with_email'] = (int)$this->db->query("SELECT COUNT(*) FROM gmb_contacts WHERE email IS NOT NULL AND email != ''")->fetchColumn();
        $stats['valid_email'] = (int)$this->db->query("SELECT COUNT(*) FROM gmb_contacts WHERE email_status = 'valid'")->fetchColumn();
        $stats['pending_validation'] = (int)$this->db->query("SELECT COUNT(*) FROM gmb_contacts WHERE email_status = 'unknown' AND email IS NOT NULL AND email != ''")->fetchColumn();
        
        // Par statut prospect
        $stmt = $this->db->query("SELECT prospect_status, COUNT(*) as c FROM gmb_contacts GROUP BY prospect_status");
        $stats['by_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Par type
        $stmt = $this->db->query("SELECT contact_type, COUNT(*) as c FROM gmb_contacts GROUP BY contact_type ORDER BY c DESC");
        $stats['by_type'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Par ville (top 10)
        $stmt = $this->db->query("SELECT city, COUNT(*) as c FROM gmb_contacts WHERE city IS NOT NULL GROUP BY city ORDER BY c DESC LIMIT 10");
        $stats['by_city'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return $stats;
    }

    /**
     * Export CSV
     */
    public function exportCSV(array $filters = []): string
    {
        $result = $this->getContacts(array_merge($filters, ['per_page' => 10000]));
        
        $output = fopen('php://temp', 'r+');
        
        // Header
        fputcsv($output, [
            'ID', 'Entreprise', 'Catégorie', 'Type', 'Contact', 'Email', 'Statut Email',
            'Téléphone', 'Ville', 'CP', 'Note Google', 'Avis', 'Statut Prospect',
            'Partenariat', 'Site Web', 'Date Scrape'
        ], ';');
        
        foreach ($result['contacts'] as $c) {
            fputcsv($output, [
                $c['id'],
                $c['business_name'],
                $c['business_category'],
                $c['contact_type'],
                $c['contact_name'],
                $c['email'],
                $c['email_status'],
                $c['phone'],
                $c['city'],
                $c['postal_code'],
                $c['rating'],
                $c['reviews_count'],
                $c['prospect_status'],
                $c['partnership_type'],
                $c['website'],
                $c['scraped_at'],
            ], ';');
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
}