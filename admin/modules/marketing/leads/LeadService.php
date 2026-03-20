<?php
/**
 * LeadService — Business logic for the Leads module
 * Extracted from admin/modules/marketing/leads/index.php (94KB monolith)
 * and admin/core/handlers/leads.php
 */

class LeadService
{
    private PDO $pdo;

    /** Allowed columns for lead updates */
    private const ALLOWED_FIELDS = [
        'firstname','lastname','email','phone','address','city','postal_code',
        'source','type','status','temperature','score',
        'budget_min','budget_max','property_type',
        'surface_min','surface_max','rooms_min','bedrooms_min',
        'notes','tags','next_action','next_action_date','last_contact'
    ];

    /** Allowed sort columns */
    private const ALLOWED_SORTS = [
        'firstname','lastname','email','status','temperature','score','created_at',
        '_fn','_email','_score'
    ];

    /** Source label mapping */
    private const SOURCE_MAP = [
        'site_web'        => 'Site web',
        'gmb'             => 'GMB',
        'pub_facebook'    => 'Facebook',
        'pub_google'      => 'Google',
        'recommandation'  => 'Recommandation',
        'telephone'       => 'Téléphone',
        'flyer'           => 'Flyer',
        'boitage'         => 'Boîtage',
        'salon'           => 'Salon',
        'estimation'      => 'Estimation',
        'capture'         => 'Capture',
        'financement'     => 'Financement',
        'manuel'          => 'Manuel',
        'autre'           => 'Autre',
    ];

    /** Valid interaction types */
    private const INTERACTION_TYPES = ['note','appel','email','rdv','sms','visite'];

    /** Tables that support multi-table operations */
    private const MULTI_TABLES = ['leads','capture_leads','demandes_estimation','contacts','financement_leads'];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ─── Single-table CRUD (leads table only) ────────────────────────────────

    /**
     * List leads with filters, sorting, and pagination.
     */
    public function list(array $filters): array
    {
        try {
            $search      = $filters['search'] ?? '';
            $status      = $filters['status'] ?? '';
            $type        = $filters['type'] ?? '';
            $source      = $filters['source'] ?? '';
            $temperature = $filters['temperature'] ?? '';
            $page        = max(1, (int)($filters['page'] ?? 1));
            $perPage     = (int)($filters['per_page'] ?? 20);
            $offset      = ($page - 1) * $perPage;

            $where  = ['1=1'];
            $params = [];

            if ($search) {
                $where[] = '(firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR phone LIKE ? OR city LIKE ?)';
                $s       = "%{$search}%";
                $params  = array_merge($params, [$s, $s, $s, $s, $s]);
            }
            if ($status)      { $where[] = 'status = ?';      $params[] = $status; }
            if ($type)        { $where[] = 'type = ?';         $params[] = $type; }
            if ($source)      { $where[] = 'source = ?';       $params[] = $source; }
            if ($temperature) { $where[] = 'temperature = ?';  $params[] = $temperature; }

            $whereSQL = implode(' AND ', $where);

            $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM leads WHERE {$whereSQL}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            $sortBy    = in_array($filters['sort'] ?? '', self::ALLOWED_SORTS) ? $filters['sort'] : 'created_at';
            $sortOrder = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

            $params[] = $perPage;
            $params[] = $offset;
            $stmt = $this->pdo->prepare("SELECT * FROM leads WHERE {$whereSQL} ORDER BY {$sortBy} {$sortOrder} LIMIT ? OFFSET ?");
            $stmt->execute($params);

            return ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'page' => $page];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get a single lead by ID. Supports multi-table lookup.
     */
    public function get(int $id, string $table = 'leads'): ?array
    {
        try {
            $table = $this->sanitizeTable($table);
            $stmt  = $this->pdo->prepare("SELECT * FROM `{$table}` WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return $row
                ? ['success' => true, 'lead' => $row, 'data' => $row]
                : ['success' => false, 'message' => 'Lead non trouvé'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create a new lead in the leads table.
     */
    public function create(array $data): array
    {
        $firstname = trim($data['firstname'] ?? '');
        $lastname  = trim($data['lastname'] ?? '');

        if (!$firstname && !$lastname) {
            return ['success' => false, 'message' => 'Prénom ou nom requis'];
        }

        try {
            $this->pdo->prepare(
                "INSERT INTO leads
                    (firstname, lastname, email, phone, address, city, postal_code,
                     source, type, status, temperature,
                     budget_min, budget_max, property_type,
                     notes, tags, next_action, next_action_date,
                     created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())"
            )->execute([
                $firstname,
                $lastname,
                trim($data['email'] ?? '') ?: null,
                trim($data['phone'] ?? '') ?: null,
                trim($data['address'] ?? '') ?: null,
                trim($data['city'] ?? '') ?: null,
                trim($data['postal_code'] ?? '') ?: null,
                $data['source'] ?? 'manuel',
                $data['type'] ?? 'vendeur',
                $data['status'] ?? 'new',
                $data['temperature'] ?? 'warm',
                $data['budget_min'] ?? null,
                $data['budget_max'] ?? null,
                $data['property_type'] ?? null,
                trim($data['notes'] ?? '') ?: null,
                $data['tags'] ?? null,
                trim($data['next_action'] ?? '') ?: null,
                trim($data['next_action_date'] ?? '') ?: null,
            ]);

            return ['success' => true, 'message' => 'Lead créé', 'id' => (int)$this->pdo->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Update a lead. Supports multi-table updates.
     */
    public function update(int $id, array $data, string $table = 'leads'): array
    {
        if (!$id) {
            return ['success' => false, 'message' => 'ID requis'];
        }

        $table = $this->sanitizeTable($table);

        try {
            switch ($table) {
                case 'leads':
                    $sets   = [];
                    $params = [];
                    foreach ($data as $k => $v) {
                        if (in_array($k, self::ALLOWED_FIELDS)) {
                            $sets[]   = "{$k} = ?";
                            $params[] = $v;
                        }
                    }
                    if (empty($sets)) {
                        // Fallback: explicit column update (from index.php pattern)
                        $this->pdo->prepare(
                            "UPDATE leads SET
                                firstname=?, lastname=?, email=?, phone=?, city=?, source=?, notes=?,
                                status=?, temperature=?, next_action=?, next_action_date=?, updated_at=NOW()
                             WHERE id=?"
                        )->execute([
                            trim($data['firstname'] ?? ''), trim($data['lastname'] ?? ''),
                            trim($data['email'] ?? '') ?: null,
                            trim($data['phone'] ?? '') ?: null,
                            trim($data['city'] ?? '') ?: null,
                            $data['source'] ?? 'manuel',
                            trim($data['notes'] ?? '') ?: null,
                            $data['status'] ?? 'new',
                            $data['temperature'] ?? 'warm',
                            trim($data['next_action'] ?? '') ?: null,
                            trim($data['next_action_date'] ?? '') ?: null,
                            $id,
                        ]);
                    } else {
                        $sets[]   = 'updated_at = NOW()';
                        $params[] = $id;
                        $this->pdo->prepare("UPDATE leads SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
                    }
                    break;

                case 'capture_leads':
                    $this->pdo->prepare("UPDATE capture_leads SET prenom=?, nom=?, email=?, tel=? WHERE id=?")
                        ->execute([
                            trim($data['firstname'] ?? ''), trim($data['lastname'] ?? ''),
                            trim($data['email'] ?? ''), trim($data['phone'] ?? ''), $id
                        ]);
                    break;

                case 'demandes_estimation':
                    $this->pdo->prepare("UPDATE demandes_estimation SET email=?, telephone=?, statut=? WHERE id=?")
                        ->execute([trim($data['email'] ?? ''), trim($data['phone'] ?? ''), $data['status'] ?? 'nouveau', $id]);
                    break;

                case 'contacts':
                    $this->pdo->prepare(
                        "UPDATE contacts SET firstname=?, lastname=?, email=?, phone=?, city=?, notes=?, status=?, updated_at=NOW() WHERE id=?"
                    )->execute([
                        trim($data['firstname'] ?? ''), trim($data['lastname'] ?? ''),
                        trim($data['email'] ?? ''), trim($data['phone'] ?? ''),
                        trim($data['city'] ?? ''), trim($data['notes'] ?? ''),
                        $data['status'] ?? 'actif', $id
                    ]);
                    break;

                case 'financement_leads':
                    $this->pdo->prepare(
                        "UPDATE financement_leads SET prenom=?, nom=?, email=?, telephone=?, statut=?, notes=?, updated_at=NOW() WHERE id=?"
                    )->execute([
                        trim($data['firstname'] ?? ''), trim($data['lastname'] ?? ''),
                        trim($data['email'] ?? ''), trim($data['phone'] ?? ''),
                        $data['status'] ?? 'nouveau', trim($data['notes'] ?? ''), $id
                    ]);
                    break;
            }

            return ['success' => true, 'message' => 'Lead mis à jour'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete a lead. Supports multi-table deletion. Cascades to lead_interactions.
     */
    public function delete(int $id, string $table = 'leads'): array
    {
        if (!$id) {
            return ['success' => false, 'message' => 'ID requis'];
        }

        $table = $this->sanitizeTable($table);

        try {
            $this->pdo->prepare("DELETE FROM `{$table}` WHERE id = ?")->execute([$id]);

            // Cascade: delete related interactions for the leads table
            if ($table === 'leads') {
                try {
                    $this->pdo->prepare("DELETE FROM lead_interactions WHERE lead_id = ?")->execute([$id]);
                } catch (\Exception $e) {
                    // Interactions table may not exist — ignore
                }
            }

            return ['success' => true, 'message' => 'Lead supprimé'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Bulk delete leads by IDs.
     */
    public function bulkDelete(array $ids): array
    {
        $ids = array_map('intval', is_string($ids) ? (json_decode($ids, true) ?? []) : $ids);

        if (empty($ids)) {
            return ['success' => false, 'message' => 'IDs requis'];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $this->pdo->prepare("DELETE FROM leads WHERE id IN ({$placeholders})")->execute($ids);

            return ['success' => true, 'message' => count($ids) . ' lead(s) supprimé(s)'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Bulk update status for multiple leads.
     */
    public function bulkUpdateStatus(array $ids, string $status): array
    {
        $ids = array_map('intval', is_string($ids) ? (json_decode($ids, true) ?? []) : $ids);

        if (empty($ids) || !$status) {
            return ['success' => false, 'message' => 'IDs et statut requis'];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $this->pdo->prepare("UPDATE leads SET status = ? WHERE id IN ({$placeholders})")
                ->execute(array_merge([$status], $ids));

            return ['success' => true, 'message' => 'Statut mis à jour'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Status & Temperature shortcuts ──────────────────────────────────────

    /**
     * Update lead status.
     */
    public function updateStatus(int $id, string $status): array
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Update lead temperature.
     */
    public function updateTemperature(int $id, string $temperature): array
    {
        return $this->update($id, ['temperature' => $temperature]);
    }

    // ─── Interactions / Activity ─────────────────────────────────────────────

    /**
     * Get interactions (activity log) for a lead.
     */
    public function getActivity(int $leadId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM lead_interactions WHERE lead_id = ? ORDER BY COALESCE(interaction_date, created_at) DESC"
            );
            $stmt->execute([$leadId]);

            return ['success' => true, 'interactions' => $stmt->fetchAll(PDO::FETCH_ASSOC)];
        } catch (\Exception $e) {
            return ['success' => true, 'interactions' => []];
        }
    }

    /**
     * Add an interaction / note for a lead.
     */
    public function addNote(int $leadId, array $data): array
    {
        $type = in_array($data['type'] ?? '', self::INTERACTION_TYPES) ? $data['type'] : 'note';

        try {
            $this->pdo->prepare(
                "INSERT INTO lead_interactions (lead_id, type, subject, content, interaction_date, duration_minutes, outcome)
                 VALUES (?,?,?,?,?,?,?)"
            )->execute([
                $leadId,
                $type,
                trim($data['subject'] ?? '') ?: null,
                trim($data['content'] ?? '') ?: null,
                trim($data['interaction_date'] ?? '') ?: null,
                (int)($data['duration_minutes'] ?? 0) ?: null,
                $data['outcome'] ?? null,
            ]);

            // Touch the lead's updated_at
            try {
                $this->pdo->prepare("UPDATE leads SET updated_at = NOW() WHERE id = ?")->execute([$leadId]);
            } catch (\Exception $e) {
                // ignore
            }

            return ['success' => true, 'message' => 'Interaction ajoutée'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Stats ───────────────────────────────────────────────────────────────

    /**
     * Get aggregate stats for the leads table.
     */
    public function stats(): array
    {
        try {
            $stats = $this->pdo->query(
                "SELECT COUNT(*) as total,
                        SUM(status='new') as new_leads,
                        SUM(temperature='hot') as hot,
                        SUM(temperature='warm') as warm,
                        SUM(temperature='cold') as cold,
                        AVG(score) as avg_score
                 FROM leads"
            )->fetch(PDO::FETCH_ASSOC);

            return ['success' => true, 'data' => $stats];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Export ──────────────────────────────────────────────────────────────

    /**
     * Export leads as CSV data array (headers + rows).
     * The caller is responsible for sending HTTP headers and writing to output.
     */
    public function export(array $filters): array
    {
        try {
            $rows = $this->pdo->query("SELECT * FROM leads ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

            $csvHeaders = ['ID','Prénom','Nom','Email','Téléphone','Ville','Source','Type','Statut','Température','Score','Date création'];
            $csvRows    = [];

            foreach ($rows as $r) {
                $csvRows[] = [
                    $r['id'], $r['firstname'], $r['lastname'], $r['email'],
                    $r['phone'], $r['city'], $r['source'], $r['type'],
                    $r['status'], $r['temperature'], $r['score'], $r['created_at'],
                ];
            }

            return ['success' => true, 'headers' => $csvHeaders, 'rows' => $csvRows];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // ─── Unified multi-source listing (from index.php's getAllLeads) ─────────

    /**
     * Query leads across all source tables (leads, capture_leads, demandes_estimation,
     * contacts, financement_leads), deduplicate by email, sort and paginate.
     */
    public function listAll(string $search, string $srcFilter, string $sort, string $order, string $statusFilter, int $offset, int $limit): array
    {
        $rows = [];

        // ── leads table
        if (!$srcFilter || in_array($srcFilter, ['Manuel','Site web','GMB','Facebook','Google','Téléphone','Recommandation','Flyer','Boîtage','Salon'])) {
            try {
                $w = ['1=1']; $p = [];
                if ($search) { $t = "%{$search}%"; $w[] = '(firstname LIKE ? OR lastname LIKE ? OR full_name LIKE ? OR email LIKE ? OR phone LIKE ?)'; $p = [$t,$t,$t,$t,$t]; }
                if ($statusFilter) { $w[] = 'status=?'; $p[] = $statusFilter; }
                $s = $this->pdo->prepare("SELECT *,'leads' AS _tbl FROM leads WHERE " . implode(' AND ', $w) . " ORDER BY created_at DESC");
                $s->execute($p);
                foreach ($s->fetchAll() as $r) {
                    $r['_fn'] = trim($r['firstname'] ?? '');
                    $r['_ln'] = trim($r['lastname'] ?? '');
                    if (!$r['_fn'] && !$r['_ln'] && !empty($r['full_name'])) {
                        $pts = explode(' ', trim($r['full_name']), 2);
                        $r['_fn'] = $pts[0]; $r['_ln'] = $pts[1] ?? '';
                    }
                    $r['_email']  = $r['email'] ?? null;
                    $r['_phone']  = $r['phone'] ?? null;
                    $r['_city']   = $r['city'] ?? null;
                    $r['_status'] = $r['status'] ?? '';
                    $r['_score']  = (int)($r['score'] ?? 0);
                    $src = $r['source'] ?? 'manuel';
                    $r['_src_label'] = self::SOURCE_MAP[$src] ?? ucfirst($src);
                    $r['_src_key']   = 'leads';
                    if ($srcFilter && $r['_src_label'] !== $srcFilter) continue;
                    $rows[] = $r;
                }
            } catch (\Exception $e) {}
        }

        // ── capture_leads
        if (!$srcFilter || $srcFilter === 'Capture') {
            try {
                $w = ['1=1']; $p = [];
                if ($search) { $t = "%{$search}%"; $w[] = '(prenom LIKE ? OR nom LIKE ? OR email LIKE ? OR tel LIKE ?)'; $p = [$t,$t,$t,$t]; }
                $s = $this->pdo->prepare("SELECT *,'capture_leads' AS _tbl FROM capture_leads WHERE " . implode(' AND ', $w) . " ORDER BY created_at DESC");
                $s->execute($p);
                foreach ($s->fetchAll() as $r) {
                    $r['_fn'] = $r['prenom'] ?? ''; $r['_ln'] = $r['nom'] ?? '';
                    $r['_email'] = $r['email'] ?? null; $r['_phone'] = $r['tel'] ?? null;
                    $r['_city'] = null; $r['_status'] = $r['injected_crm'] ? 'contacté' : 'nouveau';
                    $r['_score'] = 0; $r['_src_label'] = 'Capture'; $r['_src_key'] = 'capture_leads';
                    $r['notes'] = $r['message'] ?? null;
                    $rows[] = $r;
                }
            } catch (\Exception $e) {}
        }

        // ── demandes_estimation
        if (!$srcFilter || $srcFilter === 'Estimation') {
            try {
                $w = ['1=1']; $p = [];
                if ($search) { $t = "%{$search}%"; $w[] = '(email LIKE ? OR telephone LIKE ? OR ville LIKE ?)'; $p = [$t,$t,$t]; }
                $s = $this->pdo->prepare("SELECT *,'demandes_estimation' AS _tbl FROM demandes_estimation WHERE " . implode(' AND ', $w) . " ORDER BY created_at DESC");
                $s->execute($p);
                foreach ($s->fetchAll() as $r) {
                    $r['_fn'] = ''; $r['_ln'] = trim(($r['type_bien'] ?? 'Bien') . ' ' . ($r['ville'] ?? ''));
                    $r['_email'] = $r['email'] ?? null; $r['_phone'] = $r['telephone'] ?? null;
                    $r['_city'] = $r['ville'] ?? null; $r['_status'] = $r['statut'] ?? 'nouveau'; $r['_score'] = 0;
                    $r['_src_label'] = 'Estimation'; $r['_src_key'] = 'demandes_estimation';
                    $parts = array_filter([$r['type_bien'] ?? '', $r['surface'] ? ($r['surface'] . 'm²') : '', $r['estimation_moyenne'] ? ('~' . number_format($r['estimation_moyenne'], 0, ',', ' ') . '€') : '']);
                    $r['notes'] = implode(' — ', $parts);
                    $rows[] = $r;
                }
            } catch (\Exception $e) {}
        }

        // ── contacts
        if (!$srcFilter || $srcFilter === 'Contact') {
            try {
                $w = ['1=1']; $p = [];
                if ($search) { $t = "%{$search}%"; $w[] = '(firstname LIKE ? OR lastname LIKE ? OR nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR phone LIKE ?)'; $p = [$t,$t,$t,$t,$t,$t]; }
                $s = $this->pdo->prepare("SELECT *,'contacts' AS _tbl FROM contacts WHERE " . implode(' AND ', $w) . " ORDER BY created_at DESC");
                $s->execute($p);
                foreach ($s->fetchAll() as $r) {
                    $r['_fn'] = $r['firstname'] ?? $r['prenom'] ?? ''; $r['_ln'] = $r['lastname'] ?? $r['nom'] ?? '';
                    $r['_email'] = $r['email'] ?? null; $r['_phone'] = $r['phone'] ?? $r['telephone'] ?? null;
                    $r['_city'] = $r['city'] ?? null; $r['_status'] = $r['status'] ?? 'actif';
                    $r['_score'] = (int)($r['rating'] ?? 0); $r['_src_label'] = 'Contact'; $r['_src_key'] = 'contacts';
                    $rows[] = $r;
                }
            } catch (\Exception $e) {}
        }

        // ── financement_leads
        if (!$srcFilter || $srcFilter === 'Financement') {
            try {
                $w = ['1=1']; $p = [];
                if ($search) { $t = "%{$search}%"; $w[] = '(prenom LIKE ? OR nom LIKE ? OR email LIKE ? OR telephone LIKE ?)'; $p = [$t,$t,$t,$t]; }
                $s = $this->pdo->prepare("SELECT *,'financement_leads' AS _tbl FROM financement_leads WHERE " . implode(' AND ', $w) . " ORDER BY created_at DESC");
                $s->execute($p);
                foreach ($s->fetchAll() as $r) {
                    $r['_fn'] = $r['prenom'] ?? ''; $r['_ln'] = $r['nom'] ?? '';
                    $r['_email'] = $r['email'] ?? null; $r['_phone'] = $r['telephone'] ?? null;
                    $r['_city'] = null; $r['_status'] = $r['statut'] ?? 'nouveau'; $r['_score'] = 0;
                    $r['_src_label'] = 'Financement'; $r['_src_key'] = 'financement_leads';
                    $r['notes'] = trim(($r['type_projet'] ?? 'Projet') . ($r['montant_projet'] ? ' — ' . number_format($r['montant_projet'], 0, ',', ' ') . '€' : '') . ($r['notes'] ? ' | ' . $r['notes'] : ''));
                    $rows[] = $r;
                }
            } catch (\Exception $e) {}
        }

        // ── Deduplicate by email
        $seen    = [];
        $deduped = [];
        foreach ($rows as $r) {
            $key = strtolower(trim($r['_email'] ?? ''));
            if ($key && isset($seen[$key])) continue;
            if ($key) $seen[$key] = true;
            $deduped[] = $r;
        }

        // ── Sort
        usort($deduped, function ($a, $b) use ($sort, $order) {
            $va = match ($sort) {
                '_fn'    => strtolower($a['_fn'] . $a['_ln']),
                '_email' => strtolower($a['_email'] ?? ''),
                '_score' => (int)$a['_score'],
                default  => $a['created_at'] ?? '',
            };
            $vb = match ($sort) {
                '_fn'    => strtolower($b['_fn'] . $b['_ln']),
                '_email' => strtolower($b['_email'] ?? ''),
                '_score' => (int)$b['_score'],
                default  => $b['created_at'] ?? '',
            };
            $cmp = is_int($va) ? ($va <=> $vb) : strcmp((string)$va, (string)$vb);
            return $order === 'DESC' ? -$cmp : $cmp;
        });

        $total = count($deduped);

        return ['rows' => array_slice($deduped, $offset, $limit), 'total' => $total];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Sanitize table name to prevent SQL injection.
     */
    private function sanitizeTable(string $table): string
    {
        $table = preg_replace('/[^a-z_]/', '', $table);
        if (!in_array($table, self::MULTI_TABLES)) {
            $table = 'leads';
        }
        return $table;
    }
}
