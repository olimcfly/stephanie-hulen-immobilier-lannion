<?php
/**
 * ══════════════════════════════════════════════════════════════
 * PropertyController — IMMO LOCAL+
 * /admin/modules/immobilier/properties/PropertyController.php
 *
 * Gère toutes les opérations CRUD sur la table `properties`
 * Utilisé par index.php, edit.php et /admin/api/immobilier/properties.php
 * ══════════════════════════════════════════════════════════════
 */

class PropertyController
{
    private PDO $pdo;

    // Colonnes détectées dynamiquement (FR/EN)
    public string $colTitle;
    public string $colPrice;
    public string $colSurface;
    public string $colType;
    public string $colStatus;
    public string $colTrans;
    public string $colCity;
    public string $colRooms;
    public string $colRef;
    public bool   $hasSlug;
    public bool   $hasFeatured;
    public string $colFeatured;
    public bool   $hasPhotos;
    public string $colPhotos;
    public bool   $hasUpdatedAt;
    public bool   $hasMandat;
    public string $colMandat;
    public bool   $hasDpe;
    public string $colDpe;
    public bool   $tableExists = false;
    public array  $availCols   = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->detectSchema();
    }

    // ─── Détection schéma DB (FR/EN) ───────────────────────────
    private function detectSchema(): void
    {
        try {
            $this->pdo->query("SELECT 1 FROM properties LIMIT 1");
            $this->tableExists = true;
            $this->availCols   = $this->pdo
                ->query("SHOW COLUMNS FROM properties")
                ->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->tableExists = false;
            return;
        }

        $c = $this->availCols;

        $this->colTitle    = in_array('titre',       $c) ? 'titre'       : 'title';
        $this->colPrice    = in_array('prix',        $c) ? 'prix'        : 'price';
        $this->colSurface  = in_array('surface',     $c) ? 'surface'     : 'area';
        $this->colType     = in_array('type_bien',   $c) ? 'type_bien'   : 'type';
        $this->colStatus   = in_array('statut',      $c) ? 'statut'      : (in_array('status', $c) ? 'status' : 'statut');
        $this->colTrans    = in_array('transaction', $c) ? 'transaction' : 'transaction_type';
        $this->colCity     = in_array('ville',       $c) ? 'ville'       : 'city';
        $this->colRooms    = in_array('pieces',      $c) ? 'pieces'      : 'rooms';
        $this->colRef      = in_array('reference',   $c) ? 'reference'   : 'ref';

        $this->hasSlug     = in_array('slug',        $c);
        $this->hasFeatured = in_array('is_featured', $c) || in_array('featured', $c);
        $this->colFeatured = in_array('is_featured', $c) ? 'is_featured' : 'featured';
        $this->hasPhotos   = in_array('photos',      $c) || in_array('images', $c);
        $this->colPhotos   = in_array('photos',      $c) ? 'photos'      : 'images';
        $this->hasUpdatedAt= in_array('updated_at',  $c);
        $this->hasMandat   = in_array('mandat',      $c) || in_array('type_mandat', $c);
        $this->colMandat   = in_array('mandat',      $c) ? 'mandat'      : 'type_mandat';
        $this->hasDpe      = in_array('dpe',         $c) || in_array('classe_energie', $c);
        $this->colDpe      = in_array('dpe',         $c) ? 'dpe'         : 'classe_energie';
    }

    // ─── Stats globales ────────────────────────────────────────
    public function getStats(): array
    {
        $stats = ['total'=>0,'active'=>0,'vendu'=>0,'loue'=>0,'brouillon'=>0,'avg_price'=>0,'featured'=>0];
        if (!$this->tableExists) return $stats;

        try {
            $stats['total']     = (int)$this->pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
            $stats['active']    = (int)$this->pdo->query("SELECT COUNT(*) FROM properties WHERE `{$this->colStatus}` IN ('actif','active','disponible','available')")->fetchColumn();
            $stats['vendu']     = (int)$this->pdo->query("SELECT COUNT(*) FROM properties WHERE `{$this->colStatus}` IN ('vendu','sold','loue','rented')")->fetchColumn();
            $stats['brouillon'] = (int)$this->pdo->query("SELECT COUNT(*) FROM properties WHERE `{$this->colStatus}` IN ('brouillon','draft')")->fetchColumn();
            $stats['avg_price'] = (int)$this->pdo->query("SELECT ROUND(AVG(NULLIF(`{$this->colPrice}`,0)),0) FROM properties WHERE `{$this->colStatus}` NOT IN ('vendu','sold','loue','rented')")->fetchColumn();
            if ($this->hasFeatured) {
                $stats['featured'] = (int)$this->pdo->query("SELECT COUNT(*) FROM properties WHERE `{$this->colFeatured}` = 1")->fetchColumn();
            }
        } catch (PDOException $e) {
            error_log('[PropertyController::getStats] ' . $e->getMessage());
        }

        return $stats;
    }

    // ─── Liste paginée avec filtres ────────────────────────────
    public function getList(array $filters = []): array
    {
        if (!$this->tableExists) return ['items' => [], 'total' => 0, 'pages' => 1];

        $filterStatus = $filters['status']      ?? 'all';
        $filterType   = $filters['type']        ?? 'all';
        $filterTrans  = $filters['transaction'] ?? 'all';
        $search       = trim($filters['q']      ?? '');
        $page         = max(1, (int)($filters['page'] ?? 1));
        $perPage      = (int)($filters['per_page'] ?? 20);
        $offset       = ($page - 1) * $perPage;

        [$whereSQL, $params] = $this->buildWhere($filterStatus, $filterType, $filterTrans, $search);

        try {
            $total = (int)$this->pdo->prepare("SELECT COUNT(*) FROM properties {$whereSQL}")
                ->execute($params) ? $this->pdo->prepare("SELECT COUNT(*) FROM properties {$whereSQL}") : 0;

            $stmtCount = $this->pdo->prepare("SELECT COUNT(*) FROM properties {$whereSQL}");
            $stmtCount->execute($params);
            $total = (int)$stmtCount->fetchColumn();
            $pages = max(1, ceil($total / $perPage));

            $select = $this->buildSelect();
            $stmt   = $this->pdo->prepare("SELECT {$select} FROM properties {$whereSQL} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
            $stmt->execute($params);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return ['items' => $items, 'total' => $total, 'pages' => $pages];
        } catch (PDOException $e) {
            error_log('[PropertyController::getList] ' . $e->getMessage());
            return ['items' => [], 'total' => 0, 'pages' => 1];
        }
    }

    // ─── Récupérer un bien par ID ──────────────────────────────
    public function getById(int $id): ?array
    {
        if (!$this->tableExists || $id <= 0) return null;

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM properties WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            error_log('[PropertyController::getById] ' . $e->getMessage());
            return null;
        }
    }

    // ─── Créer un bien ─────────────────────────────────────────
    public function create(array $data): int|false
    {
        if (!$this->tableExists) return false;

        $fields = $this->sanitizeData($data);
        $fields['created_at'] = date('Y-m-d H:i:s');
        if ($this->hasUpdatedAt) $fields['updated_at'] = date('Y-m-d H:i:s');
        if ($this->hasSlug && empty($fields['slug'])) {
            $fields['slug'] = $this->generateSlug($data[$this->colTitle] ?? 'bien-' . time());
        }

        $cols   = implode(', ', array_map(fn($k) => "`$k`", array_keys($fields)));
        $vals   = implode(', ', array_fill(0, count($fields), '?'));

        try {
            $stmt = $this->pdo->prepare("INSERT INTO properties ({$cols}) VALUES ({$vals})");
            $stmt->execute(array_values($fields));
            return (int)$this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log('[PropertyController::create] ' . $e->getMessage());
            return false;
        }
    }

    // ─── Mettre à jour un bien ─────────────────────────────────
    public function update(int $id, array $data): bool
    {
        if (!$this->tableExists || $id <= 0) return false;

        $fields = $this->sanitizeData($data);
        if ($this->hasUpdatedAt) $fields['updated_at'] = date('Y-m-d H:i:s');

        $sets = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($fields)));
        $vals = array_values($fields);
        $vals[] = $id;

        try {
            $stmt = $this->pdo->prepare("UPDATE properties SET {$sets} WHERE id = ?");
            return $stmt->execute($vals);
        } catch (PDOException $e) {
            error_log('[PropertyController::update] ' . $e->getMessage());
            return false;
        }
    }

    // ─── Supprimer un bien ─────────────────────────────────────
    public function delete(int $id): bool
    {
        if (!$this->tableExists || $id <= 0) return false;

        try {
            $stmt = $this->pdo->prepare("DELETE FROM properties WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log('[PropertyController::delete] ' . $e->getMessage());
            return false;
        }
    }

    // ─── Suppression en masse ──────────────────────────────────
    public function bulkDelete(array $ids): bool
    {
        if (!$this->tableExists || empty($ids)) return false;

        $ids   = array_map('intval', $ids);
        $ph    = implode(',', array_fill(0, count($ids), '?'));

        try {
            return $this->pdo->prepare("DELETE FROM properties WHERE id IN ({$ph})")->execute($ids);
        } catch (PDOException $e) {
            error_log('[PropertyController::bulkDelete] ' . $e->getMessage());
            return false;
        }
    }

    // ─── Changement statut en masse ───────────────────────────
    public function bulkStatus(array $ids, string $status): bool
    {
        if (!$this->tableExists || empty($ids)) return false;

        $ids   = array_map('intval', $ids);
        $ph    = implode(',', array_fill(0, count($ids), '?'));
        $vals  = [$status, ...$ids];
        if ($this->hasUpdatedAt) {
            $vals = [$status, date('Y-m-d H:i:s'), ...$ids];
            $sql  = "UPDATE properties SET `{$this->colStatus}` = ?, updated_at = ? WHERE id IN ({$ph})";
        } else {
            $sql  = "UPDATE properties SET `{$this->colStatus}` = ? WHERE id IN ({$ph})";
        }

        try {
            return $this->pdo->prepare($sql)->execute($vals);
        } catch (PDOException $e) {
            error_log('[PropertyController::bulkStatus] ' . $e->getMessage());
            return false;
        }
    }

    // ─── Toggle statut actif/brouillon ────────────────────────
    public function toggleStatus(int $id, string $newStatus): bool
    {
        return $this->update($id, [$this->colStatus => $newStatus]);
    }

    // ─── Toggle à la une ──────────────────────────────────────
    public function toggleFeatured(int $id): bool
    {
        if (!$this->tableExists || !$this->hasFeatured) return false;

        try {
            $stmt = $this->pdo->prepare("SELECT `{$this->colFeatured}` FROM properties WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $current = (int)$stmt->fetchColumn();
            return $this->update($id, [$this->colFeatured => $current ? 0 : 1]);
        } catch (PDOException $e) {
            error_log('[PropertyController::toggleFeatured] ' . $e->getMessage());
            return false;
        }
    }

    // ─── Types disponibles ────────────────────────────────────
    public function getTypes(): array
    {
        if (!$this->tableExists || !in_array($this->colType, $this->availCols)) return [];

        try {
            return $this->pdo->query(
                "SELECT DISTINCT `{$this->colType}` FROM properties WHERE `{$this->colType}` IS NOT NULL AND `{$this->colType}` != '' ORDER BY `{$this->colType}`"
            )->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            return [];
        }
    }

    // ─── Créer la table si elle n'existe pas ──────────────────
    public function createTable(): bool
    {
        $sql = "CREATE TABLE IF NOT EXISTS properties (
            id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            titre            VARCHAR(255)  NOT NULL DEFAULT '',
            slug             VARCHAR(255)  NOT NULL DEFAULT '',
            prix             DECIMAL(12,2) NOT NULL DEFAULT 0,
            surface          INT           NOT NULL DEFAULT 0,
            pieces           INT           NOT NULL DEFAULT 0,
            type_bien        VARCHAR(80)   NOT NULL DEFAULT '',
            transaction      VARCHAR(20)   NOT NULL DEFAULT 'vente',
            statut           VARCHAR(20)   NOT NULL DEFAULT 'brouillon',
            ville            VARCHAR(100)  NOT NULL DEFAULT '',
            code_postal      VARCHAR(10)   NOT NULL DEFAULT '',
            adresse          TEXT,
            description      LONGTEXT,
            photos           JSON,
            reference        VARCHAR(60)   DEFAULT NULL,
            mandat           VARCHAR(20)   DEFAULT NULL,
            dpe              CHAR(1)       DEFAULT NULL,
            is_featured      TINYINT(1)    NOT NULL DEFAULT 0,
            latitude         DECIMAL(10,7) DEFAULT NULL,
            longitude        DECIMAL(10,7) DEFAULT NULL,
            created_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_statut    (statut),
            INDEX idx_type      (type_bien),
            INDEX idx_ville     (ville),
            INDEX idx_featured  (is_featured),
            INDEX idx_created   (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $this->pdo->exec($sql);
            $this->detectSchema(); // Rafraîchir après création
            return true;
        } catch (PDOException $e) {
            error_log('[PropertyController::createTable] ' . $e->getMessage());
            return false;
        }
    }

    // ─── Helpers privés ───────────────────────────────────────

    private function buildWhere(string $status, string $type, string $trans, string $q): array
    {
        $where  = [];
        $params = [];

        $statusMap = [
            'active'  => ['actif','active','disponible','available'],
            'vendu'   => ['vendu','sold'],
            'loue'    => ['loue','rented'],
            'draft'   => ['brouillon','draft'],
            'archive' => ['archive','archived'],
        ];

        if ($status !== 'all' && isset($statusMap[$status])) {
            $ph      = implode(',', array_fill(0, count($statusMap[$status]), '?'));
            $where[] = "`{$this->colStatus}` IN ({$ph})";
            foreach ($statusMap[$status] as $v) $params[] = $v;
        }

        if ($type !== 'all') {
            $where[] = "`{$this->colType}` = ?";
            $params[] = $type;
        }

        if ($trans !== 'all') {
            $where[] = "`{$this->colTrans}` = ?";
            $params[] = $trans;
        }

        if ($q !== '') {
            $w  = "(`{$this->colTitle}` LIKE ?";  $params[] = "%{$q}%";
            $w .= " OR `{$this->colCity}` LIKE ?"; $params[] = "%{$q}%";
            if (in_array($this->colRef, $this->availCols)) {
                $w .= " OR `{$this->colRef}` LIKE ?";
                $params[] = "%{$q}%";
            }
            $w .= ")";
            $where[] = $w;
        }

        $sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        return [$sql, $params];
    }

    private function buildSelect(): string
    {
        $parts = [
            "id",
            "`{$this->colTitle}` AS display_title",
            "`{$this->colPrice}` AS display_price",
            "`{$this->colSurface}` AS display_surface",
            "`{$this->colType}` AS display_type",
            "`{$this->colStatus}` AS display_status",
            "`{$this->colTrans}` AS display_transaction",
            "`{$this->colCity}` AS display_city",
            "`{$this->colRooms}` AS display_rooms",
            "created_at",
        ];
        if (in_array($this->colRef, $this->availCols))  $parts[] = "`{$this->colRef}` AS display_ref";
        if ($this->hasSlug)      $parts[] = "slug";
        if ($this->hasFeatured)  $parts[] = "`{$this->colFeatured}` AS is_featured";
        if ($this->hasPhotos)    $parts[] = "`{$this->colPhotos}` AS display_photos";
        if ($this->hasUpdatedAt) $parts[] = "updated_at";
        if ($this->hasMandat)    $parts[] = "`{$this->colMandat}` AS display_mandat";
        if ($this->hasDpe)       $parts[] = "`{$this->colDpe}` AS display_dpe";

        return implode(', ', $parts);
    }

    private function sanitizeData(array $data): array
    {
        $allowed = [
            $this->colTitle, $this->colPrice, $this->colSurface, $this->colType,
            $this->colStatus, $this->colTrans, $this->colCity, $this->colRooms,
            'description', 'adresse', 'code_postal', 'latitude', 'longitude',
        ];
        if (in_array($this->colRef, $this->availCols)) $allowed[] = $this->colRef;
        if ($this->hasSlug)     $allowed[] = 'slug';
        if ($this->hasFeatured) $allowed[] = $this->colFeatured;
        if ($this->hasPhotos)   $allowed[] = $this->colPhotos;
        if ($this->hasMandat)   $allowed[] = $this->colMandat;
        if ($this->hasDpe)      $allowed[] = $this->colDpe;

        $clean = [];
        foreach ($data as $k => $v) {
            if (!in_array($k, $allowed)) continue;
            // JSON pour photos
            if ($k === $this->colPhotos && is_array($v)) {
                $v = json_encode($v);
            }
            $clean[$k] = $v;
        }
        return $clean;
    }

    private function generateSlug(string $title): string
    {
        $slug = strtolower($title);
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = $slug ?: 'bien';

        // Unicité
        $base  = $slug;
        $i     = 1;
        while (true) {
            try {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM properties WHERE slug = ?");
                $stmt->execute([$slug]);
                if ((int)$stmt->fetchColumn() === 0) break;
                $slug = $base . '-' . $i++;
            } catch (PDOException $e) {
                break;
            }
        }
        return $slug;
    }
}