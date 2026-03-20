<?php
class Lead {
    private $db;
    private ?Encryption $encryption = null;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        try {
            $this->encryption = Encryption::getInstance();
        } catch (RuntimeException $e) {
            error_log('Lead: Encryption non disponible — ' . $e->getMessage());
        }
    }

    public function create($data) {
        $email = $data['email'] ?? null;
        $phone = $data['phone'] ?? null;
        $emailHash = null;

        if ($this->encryption) {
            $emailHash = $email ? $this->encryption->hash($email) : null;
            $email = $this->encryption->encrypt($email);
            $phone = $this->encryption->encrypt($phone);
        }

        $sql = "INSERT INTO leads (email, phone, email_hash, first_name, last_name, city, interest, source, capture_page_id, gdpr_consent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $email,
            $phone,
            $emailHash,
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['city'] ?? null,
            $data['interest'] ?? null,
            $data['source'] ?? 'website',
            $data['capture_page_id'] ?? null,
            $data['gdpr_consent'] ?? 0
        ]);
    }

    public function getAll($limit = 50, $offset = 0, $status = null) {
        $sql = "SELECT * FROM leads "; $params = [];
        if ($status) { $sql .= "WHERE status = ? "; $params[] = $status; }
        $sql .= "ORDER BY created_at DESC LIMIT ? OFFSET ?"; $params[] = $limit; $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->decryptLeads($leads);
    }

    public function count($status = null) {
        $sql = "SELECT COUNT(*) as total FROM leads "; $params = [];
        if ($status) { $sql .= "WHERE status = ?"; $params[] = $status; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    }

    /**
     * Rechercher un lead par email via le hash
     */
    public function findByEmail(string $email): ?array {
        if (!$this->encryption) {
            $stmt = $this->db->prepare("SELECT * FROM leads WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $lead = $stmt->fetch(PDO::FETCH_ASSOC);
            return $lead ?: null;
        }

        $hash = $this->encryption->hash($email);
        $stmt = $this->db->prepare("SELECT * FROM leads WHERE email_hash = ? LIMIT 1");
        $stmt->execute([$hash]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($lead) {
            $leads = $this->decryptLeads([$lead]);
            return $leads[0];
        }
        return null;
    }

    /**
     * Déchiffrer email et phone dans un tableau de leads
     */
    private function decryptLeads(array $leads): array {
        if (!$this->encryption) {
            return $leads;
        }
        foreach ($leads as &$lead) {
            if (isset($lead['email'])) {
                $lead['email'] = $this->encryption->decrypt($lead['email']);
            }
            if (isset($lead['phone'])) {
                $lead['phone'] = $this->encryption->decrypt($lead['phone']);
            }
        }
        return $leads;
    }
}
