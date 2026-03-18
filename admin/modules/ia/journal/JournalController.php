<?php
/**
 * JournalController.php - CRUD + Logique metier
 * Module Journal Editorial V3
 * Fichier : admin/modules/journal/JournalController.php
 *
 * MODIF v3.1 :
 *   - getList()   : ajout filtre 'search' (LIKE sur title)
 *   - countList() : nouvelle methode pour la pagination
 */

class JournalController
{
    private PDO $db;

    // ================================================================
    // REFERENTIELS STATIQUES
    // ================================================================

    public const PROFILES = [
        'vendeur'      => ['label' => 'Vendeur',        'icon' => "\xF0\x9F\x8F\xA0", 'color' => '#e74c3c', 'desc' => 'Proprietaire souhaitant vendre'],
        'acheteur'     => ['label' => 'Acheteur',       'icon' => "\xF0\x9F\x94\x91", 'color' => '#3498db', 'desc' => 'Cherche a acheter un bien'],
        'investisseur' => ['label' => 'Investisseur',   'icon' => "\xF0\x9F\x93\x88", 'color' => '#2ecc71', 'desc' => 'Investissement locatif'],
        'primo'        => ['label' => 'Primo-accedant', 'icon' => "\xF0\x9F\x8C\xB1", 'color' => '#9b59b6', 'desc' => 'Premier achat immobilier'],
    ];

    public const CHANNELS = [
        'blog'      => ['label' => 'Blog / Article SEO',  'icon' => 'fas fa-pen-fancy',      'color' => '#2c3e50', 'create_url' => '?page=articles&action=create'],
        'gmb'       => ['label' => 'Google My Business',  'icon' => 'fas fa-map-marker-alt', 'color' => '#4285f4', 'create_url' => '?page=local-seo&tab=publications&action=create'],
        'facebook'  => ['label' => 'Facebook',            'icon' => 'fab fa-facebook',       'color' => '#1877f2', 'create_url' => '?page=facebook&tab=rediger'],
        'instagram' => ['label' => 'Instagram',           'icon' => 'fab fa-instagram',      'color' => '#e4405f', 'create_url' => '?page=instagram&tab=rediger'],
        'tiktok'    => ['label' => 'TikTok',              'icon' => 'fab fa-tiktok',         'color' => '#010101', 'create_url' => '?page=tiktok&tab=scripts'],
        'linkedin'  => ['label' => 'LinkedIn',            'icon' => 'fab fa-linkedin',       'color' => '#0a66c2', 'create_url' => '?page=linkedin&tab=rediger'],
        'email'     => ['label' => 'Emails & Sequences',  'icon' => 'fas fa-envelope',       'color' => '#e74c3c', 'create_url' => '?page=emails&action=create'],
    ];

    public const AWARENESS = [
        'unaware'    => ['label' => 'Inconscient',              'step' => 1, 'color' => '#95a5a6', 'short' => 'Inconscient'],
        'problem'    => ['label' => 'Conscient du probleme',    'step' => 2, 'color' => '#e67e22', 'short' => 'Probleme'],
        'solution'   => ['label' => 'Conscient de la solution', 'step' => 3, 'color' => '#f1c40f', 'short' => 'Solution'],
        'product'    => ['label' => 'Conscient du produit',     'step' => 4, 'color' => '#3498db', 'short' => 'Produit'],
        'most-aware' => ['label' => 'Pret a agir',              'step' => 5, 'color' => '#2ecc71', 'short' => 'Pret'],
    ];

    public const OBJECTIVES = [
        'notoriete'    => ['label' => 'Notoriete',       'icon' => 'fas fa-bullhorn'],
        'trafic'       => ['label' => 'Trafic site',     'icon' => 'fas fa-globe'],
        'leads'        => ['label' => 'Generation leads','icon' => 'fas fa-crosshairs'],
        'nurturing'    => ['label' => 'Nurturing',       'icon' => 'fas fa-heart'],
        'conversion'   => ['label' => 'Conversion',      'icon' => 'fas fa-check-circle'],
        'fidelisation' => ['label' => 'Fidelisation',    'icon' => 'fas fa-handshake'],
        'seo-local'    => ['label' => 'SEO Local',       'icon' => 'fas fa-map-pin'],
        'autorite'     => ['label' => 'Autorite',        'icon' => 'fas fa-trophy'],
    ];

    public const CONTENT_TYPES = [
        'article-pilier'    => 'Article pilier',
        'article-satellite' => 'Article satellite',
        'post-court'        => 'Post court',
        'story'             => 'Story',
        'reel'              => 'Reel / Short',
        'video-script'      => 'Script video',
        'email'             => 'Email',
        'lead-magnet'       => 'Lead magnet',
        'fiche-gmb'         => 'Fiche GMB',
    ];

    public const CTA_TYPES = [
        'estimation'       => 'Estimation gratuite',
        'rdv'              => 'Prendre RDV',
        'guide-pdf'        => 'Telecharger un guide',
        'newsletter'       => 'Inscription newsletter',
        'visite-virtuelle' => 'Visite virtuelle',
        'checklist'        => 'Checklist gratuite',
    ];

    public const STATUSES = [
        'idea'      => ['label' => 'Idee',           'color' => '#95a5a6', 'bg' => '#f0f0f0', 'icon' => 'fas fa-lightbulb'],
        'planned'   => ['label' => 'Planifie',       'color' => '#3498db', 'bg' => '#ebf5fb', 'icon' => 'fas fa-calendar-alt'],
        'validated' => ['label' => 'Valide',         'color' => '#e67e22', 'bg' => '#fdf2e9', 'icon' => 'fas fa-check'],
        'writing'   => ['label' => 'En redaction',   'color' => '#9b59b6', 'bg' => '#f4ecf7', 'icon' => 'fas fa-pencil-alt'],
        'ready'     => ['label' => 'Pret a publier', 'color' => '#2ecc71', 'bg' => '#eafaf1', 'icon' => 'fas fa-rocket'],
        'published' => ['label' => 'Publie',         'color' => '#27ae60', 'bg' => '#d5f5e3', 'icon' => 'fas fa-check-double'],
        'rejected'  => ['label' => 'Rejete',         'color' => '#e74c3c', 'bg' => '#fdedec', 'icon' => 'fas fa-times'],
    ];

    // ================================================================
    // CONSTRUCTEUR
    // ================================================================

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ================================================================
    // LECTURE — LISTES
    // ================================================================

    /**
     * Liste filtree avec pagination
     */
    public function getList(array $filters = [], int $limit = 200, int $offset = 0): array
    {
        $where  = ['1=1'];
        $params = [];

        $textFilters = [
            'channel_id', 'profile_id', 'awareness_level', 'sector_id',
            'status', 'objective_id', 'content_type'
        ];
        foreach ($textFilters as $f) {
            if (!empty($filters[$f])) {
                $where[]       = "ej.$f = :$f";
                $params[":$f"] = $filters[$f];
            }
        }

        if (!empty($filters['week_number'])) {
            $where[]                = 'ej.week_number = :week_number';
            $params[':week_number'] = (int)$filters['week_number'];
        }
        if (!empty($filters['year'])) {
            $where[]         = 'ej.year = :year';
            $params[':year'] = (int)$filters['year'];
        }

        // ── FILTRE RECHERCHE (ajout v3.1) ──
        if (!empty($filters['search'])) {
            $where[]           = 'ej.title LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (empty($filters['include_rejected'])) {
            $where[] = "ej.status != 'rejected'";
        }

        $sql = "SELECT ej.*
                FROM editorial_journal ej
                WHERE " . implode(' AND ', $where) . "
                ORDER BY ej.year ASC, ej.week_number ASC, ej.priority ASC, ej.profile_id ASC
                LIMIT :lim OFFSET :off";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Compter les resultats pour la pagination — memes filtres que getList()
     * (ajout v3.1)
     */
    public function countList(array $filters = []): int
    {
        $where  = ['1=1'];
        $params = [];

        $textFilters = [
            'channel_id', 'profile_id', 'awareness_level', 'sector_id',
            'status', 'objective_id', 'content_type'
        ];
        foreach ($textFilters as $f) {
            if (!empty($filters[$f])) {
                $where[]       = "ej.$f = :$f";
                $params[":$f"] = $filters[$f];
            }
        }

        if (!empty($filters['week_number'])) {
            $where[]                = 'ej.week_number = :week_number';
            $params[':week_number'] = (int)$filters['week_number'];
        }
        if (!empty($filters['year'])) {
            $where[]         = 'ej.year = :year';
            $params[':year'] = (int)$filters['year'];
        }
        if (!empty($filters['search'])) {
            $where[]           = 'ej.title LIKE :search';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (empty($filters['include_rejected'])) {
            $where[] = "ej.status != 'rejected'";
        }

        $sql  = "SELECT COUNT(*) FROM editorial_journal ej WHERE " . implode(' AND ', $where);
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /**
     * Recuperer une entree par ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM editorial_journal WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ================================================================
    // LECTURE — STATISTIQUES
    // ================================================================

    /**
     * Stats par canal — cards du hub central
     */
    public function getStatsByChannel(): array
    {
        $sql = "SELECT 
                    channel_id,
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'idea'      THEN 1 ELSE 0 END) AS ideas,
                    SUM(CASE WHEN status = 'planned'   THEN 1 ELSE 0 END) AS planned,
                    SUM(CASE WHEN status = 'validated' THEN 1 ELSE 0 END) AS validated,
                    SUM(CASE WHEN status = 'writing'   THEN 1 ELSE 0 END) AS writing,
                    SUM(CASE WHEN status = 'ready'     THEN 1 ELSE 0 END) AS ready,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published,
                    SUM(CASE WHEN status = 'rejected'  THEN 1 ELSE 0 END) AS rejected
                FROM editorial_journal
                GROUP BY channel_id
                ORDER BY FIELD(channel_id, 'blog','gmb','facebook','instagram','tiktok','linkedin','email')";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Stats globales — metriques dashboard
     */
    public function getStatsGlobal(): array
    {
        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'idea'      THEN 1 ELSE 0 END) AS ideas,
                    SUM(CASE WHEN status = 'planned'   THEN 1 ELSE 0 END) AS planned,
                    SUM(CASE WHEN status = 'validated' THEN 1 ELSE 0 END) AS validated,
                    SUM(CASE WHEN status = 'writing'   THEN 1 ELSE 0 END) AS writing,
                    SUM(CASE WHEN status = 'ready'     THEN 1 ELSE 0 END) AS ready,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published
                FROM editorial_journal
                WHERE status != 'rejected'";

        return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [
            'total' => 0, 'ideas' => 0, 'planned' => 0, 'validated' => 0,
            'writing' => 0, 'ready' => 0, 'published' => 0
        ];
    }

    /**
     * Stats pour un canal specifique — widget
     */
    public function getChannelStats(string $channelId): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'idea'      THEN 1 ELSE 0 END) AS ideas,
                SUM(CASE WHEN status = 'planned'   THEN 1 ELSE 0 END) AS planned,
                SUM(CASE WHEN status = 'validated' THEN 1 ELSE 0 END) AS validated,
                SUM(CASE WHEN status = 'writing'   THEN 1 ELSE 0 END) AS writing,
                SUM(CASE WHEN status = 'ready'     THEN 1 ELSE 0 END) AS ready,
                SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published
            FROM editorial_journal 
            WHERE channel_id = :ch AND status != 'rejected'
        ");
        $stmt->execute([':ch' => $channelId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'total' => 0, 'ideas' => 0, 'planned' => 0, 'validated' => 0,
            'writing' => 0, 'ready' => 0, 'published' => 0
        ];
    }

    /**
     * Compteurs par canal — badges sidebar
     */
    public function countByChannel(): array
    {
        $sql = "SELECT 
                    channel_id,
                    SUM(CASE WHEN status IN ('idea','planned') THEN 1 ELSE 0 END) AS ideas,
                    SUM(CASE WHEN status IN ('validated','writing','ready') THEN 1 ELSE 0 END) AS actifs
                FROM editorial_journal 
                WHERE status != 'rejected'
                GROUP BY channel_id";

        $rows   = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $r) {
            $result[$r['channel_id']] = $r;
        }
        return $result;
    }

    // ================================================================
    // LECTURE — MATRICE STRATEGIQUE
    // ================================================================

    /**
     * Donnees matrice : profil x conscience
     */
    public function getMatrixData(?string $channelId = null): array
    {
        $where  = "status != 'rejected'";
        $params = [];

        if ($channelId) {
            $where        .= " AND channel_id = :ch";
            $params[':ch'] = $channelId;
        }

        $sql = "SELECT 
                    profile_id, 
                    awareness_level, 
                    COUNT(*) AS cnt,
                    SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) AS published
                FROM editorial_journal
                WHERE $where
                GROUP BY profile_id, awareness_level";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Structurer en matrice [profile][awareness]
        $matrix = [];
        foreach (array_keys(self::PROFILES) as $p) {
            foreach (array_keys(self::AWARENESS) as $a) {
                $matrix[$p][$a] = ['cnt' => 0, 'published' => 0];
            }
        }
        foreach ($rows as $r) {
            if (isset($matrix[$r['profile_id']][$r['awareness_level']])) {
                $matrix[$r['profile_id']][$r['awareness_level']] = [
                    'cnt'       => (int)$r['cnt'],
                    'published' => (int)$r['published'],
                ];
            }
        }

        return $matrix;
    }

    // ================================================================
    // LECTURE — DONNEES EXTERNES
    // ================================================================

    /**
     * Charger les secteurs (avec fallback Bordeaux)
     */
    public function getSecteurs(): array
    {
        try {
            $rows = $this->db->query(
                "SELECT id, nom, slug FROM secteurs WHERE status = 'active' ORDER BY nom"
            )->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) return $rows;
        } catch (\Exception $e) {}

        return [
            ['id' =>  1, 'nom' => 'Bordeaux Centre',    'slug' => 'bordeaux-centre'],
            ['id' =>  2, 'nom' => 'Les Chartrons',      'slug' => 'chartrons'],
            ['id' =>  3, 'nom' => 'Saint-Pierre',       'slug' => 'saint-pierre'],
            ['id' =>  4, 'nom' => 'La Bastide',         'slug' => 'bastide'],
            ['id' =>  5, 'nom' => 'Cauderan',           'slug' => 'cauderan'],
            ['id' =>  6, 'nom' => 'Merignac',           'slug' => 'merignac'],
            ['id' =>  7, 'nom' => 'Pessac',             'slug' => 'pessac'],
            ['id' =>  8, 'nom' => 'Talence',            'slug' => 'talence'],
            ['id' =>  9, 'nom' => 'Begles',             'slug' => 'begles'],
            ['id' => 10, 'nom' => "Villenave-d'Ornon",  'slug' => 'villenave'],
            ['id' => 11, 'nom' => 'Gradignan',          'slug' => 'gradignan'],
            ['id' => 12, 'nom' => 'Le Bouscat',         'slug' => 'le-bouscat'],
            ['id' => 13, 'nom' => 'Bruges',             'slug' => 'bruges'],
            ['id' => 14, 'nom' => 'Blanquefort',        'slug' => 'blanquefort'],
            ['id' => 15, 'nom' => 'Saint-Michel',       'slug' => 'saint-michel'],
        ];
    }

    /**
     * Charger les personas depuis neuropersona_types
     */
    public function getPersonas(): array
    {
        try {
            return $this->db->query(
                "SELECT id, nom, slug, categorie FROM neuropersona_types WHERE status = 'active' ORDER BY nom"
            )->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Charger la config du journal
     */
    public function getConfig(): array
    {
        $config = [];
        try {
            $rows = $this->db->query(
                "SELECT config_key, config_value FROM editorial_journal_config"
            )->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $decoded        = json_decode($r['config_value'], true);
                $config[$r['config_key']] = ($decoded !== null) ? $decoded : $r['config_value'];
            }
        } catch (\Exception $e) {}
        return $config;
    }

    /**
     * Mettre a jour une config
     */
    public function setConfig(string $key, $value): bool
    {
        $val  = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
        $stmt = $this->db->prepare(
            "INSERT INTO editorial_journal_config (config_key, config_value) VALUES (:k, :v)
             ON DUPLICATE KEY UPDATE config_value = :v2"
        );
        return $stmt->execute([':k' => $key, ':v' => $val, ':v2' => $val]);
    }

    // ================================================================
    // ECRITURE — CRUD
    // ================================================================

    /**
     * Creer une nouvelle entree
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO editorial_journal 
                (title, description, keywords, content_type,
                 profile_id, sector_id, channel_id, awareness_level, objective_id,
                 week_number, year, planned_date, planned_time, day_of_week, priority,
                 cta_type, cta_text, lead_magnet_title,
                 status, persona_id, ai_generated, source_diagnostic, mere_hook, notes, created_by)
                VALUES 
                (:title, :description, :keywords, :content_type,
                 :profile_id, :sector_id, :channel_id, :awareness_level, :objective_id,
                 :week_number, :year, :planned_date, :planned_time, :day_of_week, :priority,
                 :cta_type, :cta_text, :lead_magnet_title,
                 :status, :persona_id, :ai_generated, :source_diagnostic, :mere_hook, :notes, :created_by)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':title'             => $data['title'],
            ':description'       => $data['description']       ?? null,
            ':keywords'          => $data['keywords']          ?? null,
            ':content_type'      => $data['content_type']      ?? 'post-court',
            ':profile_id'        => $data['profile_id'],
            ':sector_id'         => $data['sector_id']         ?? null,
            ':channel_id'        => $data['channel_id'],
            ':awareness_level'   => $data['awareness_level']   ?? 'problem',
            ':objective_id'      => $data['objective_id']      ?? 'notoriete',
            ':week_number'       => $data['week_number']       ?? (int)date('W'),
            ':year'              => $data['year']              ?? (int)date('Y'),
            ':planned_date'      => $data['planned_date']      ?? null,
            ':planned_time'      => $data['planned_time']      ?? null,
            ':day_of_week'       => $data['day_of_week']       ?? null,
            ':priority'          => $data['priority']          ?? 5,
            ':cta_type'          => $data['cta_type']          ?? null,
            ':cta_text'          => $data['cta_text']          ?? null,
            ':lead_magnet_title' => $data['lead_magnet_title'] ?? null,
            ':status'            => $data['status']            ?? 'idea',
            ':persona_id'        => $data['persona_id']        ?? null,
            ':ai_generated'      => $data['ai_generated']      ?? 0,
            ':source_diagnostic' => $data['source_diagnostic'] ?? null,
            ':mere_hook'         => $data['mere_hook']         ?? null,
            ':notes'             => $data['notes']             ?? null,
            ':created_by'        => $data['created_by']        ?? ($_SESSION['admin_id'] ?? null),
        ]);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Mettre a jour une entree
     */
    public function update(int $id, array $data): bool
    {
        $allowed = [
            'title', 'description', 'keywords', 'content_type',
            'profile_id', 'sector_id', 'channel_id', 'awareness_level', 'objective_id',
            'week_number', 'year', 'planned_date', 'planned_time', 'day_of_week',
            'priority', 'cta_type', 'cta_text', 'lead_magnet_title',
            'notes', 'mere_hook', 'persona_id'
        ];

        $fields = [];
        $params = [':id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[]          = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }

        if (empty($fields)) return false;

        $sql  = "UPDATE editorial_journal SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Supprimer une entree
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM editorial_journal WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ================================================================
    // ECRITURE — WORKFLOW
    // ================================================================

    /**
     * Changer le statut (avec timestamps auto)
     */
    public function updateStatus(int $id, string $newStatus): bool
    {
        if (!isset(self::STATUSES[$newStatus])) return false;

        $extra = '';
        if ($newStatus === 'validated') $extra = ', validated_at = NOW()';
        if ($newStatus === 'published') $extra = ', published_at = NOW()';

        $stmt = $this->db->prepare(
            "UPDATE editorial_journal SET status = :status $extra WHERE id = :id"
        );
        return $stmt->execute([':status' => $newStatus, ':id' => $id]);
    }

    /**
     * Validation en masse
     */
    public function bulkValidate(array $ids): int
    {
        if (empty($ids)) return 0;
        $clean = array_map('intval', $ids);
        $ph    = implode(',', array_fill(0, count($clean), '?'));

        $stmt = $this->db->prepare(
            "UPDATE editorial_journal SET status = 'validated', validated_at = NOW() 
             WHERE id IN ($ph) AND status IN ('idea','planned')"
        );
        $stmt->execute($clean);
        return $stmt->rowCount();
    }

    /**
     * Rejet en masse
     */
    public function bulkReject(array $ids): int
    {
        if (empty($ids)) return 0;
        $clean = array_map('intval', $ids);
        $ph    = implode(',', array_fill(0, count($clean), '?'));

        $stmt = $this->db->prepare(
            "UPDATE editorial_journal SET status = 'rejected' 
             WHERE id IN ($ph) AND status IN ('idea','planned')"
        );
        $stmt->execute($clean);
        return $stmt->rowCount();
    }

    /**
     * Suppression en masse
     */
    public function bulkDelete(array $ids): int
    {
        if (empty($ids)) return 0;
        $clean = array_map('intval', $ids);
        $ph    = implode(',', array_fill(0, count($clean), '?'));

        $stmt = $this->db->prepare("DELETE FROM editorial_journal WHERE id IN ($ph)");
        $stmt->execute($clean);
        return $stmt->rowCount();
    }

    // ================================================================
    // ECRITURE — CONNEXION MODULES
    // ================================================================

    /**
     * Lier un contenu cree a une entree du journal
     */
    public function linkContent(int $journalId, int $contentId, string $contentType, ?string $url = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE editorial_journal 
            SET created_content_id   = :cid, 
                created_content_type = :ctype, 
                published_url        = :url, 
                status               = 'ready'
            WHERE id = :id
        ");
        return $stmt->execute([
            ':cid'   => $contentId,
            ':ctype' => $contentType,
            ':url'   => $url,
            ':id'    => $journalId,
        ]);
    }

    /**
     * Marquer comme publie avec URL
     */
    public function markPublished(int $id, ?string $url = null): bool
    {
        $stmt = $this->db->prepare("
            UPDATE editorial_journal 
            SET status = 'published', published_at = NOW(),
                published_url = COALESCE(:url, published_url)
            WHERE id = :id
        ");
        return $stmt->execute([':url' => $url, ':id' => $id]);
    }

    // ================================================================
    // UTILITAIRES
    // ================================================================

    /**
     * Generer l'URL de creation de contenu pour un item
     */
    public function getCreateContentUrl(array $item): string
    {
        $channel = $item['channel_id'] ?? '';
        $baseUrl = self::CHANNELS[$channel]['create_url'] ?? '?page=articles&action=create';

        $params = ['from_journal' => $item['id']];
        if (!empty($item['title']))      $params['title']      = $item['title'];
        if (!empty($item['keywords']))   $params['keywords']   = $item['keywords'];
        if (!empty($item['persona_id'])) $params['persona_id'] = $item['persona_id'];
        if (!empty($item['profile_id'])) $params['profile']    = $item['profile_id'];
        if (!empty($item['sector_id']))  $params['sector']     = $item['sector_id'];

        $sep = (strpos($baseUrl, '?') !== false) ? '&' : '?';
        return $baseUrl . $sep . http_build_query($params);
    }

    /**
     * Semaine actuelle
     */
    public static function getCurrentWeek(): array
    {
        return ['week' => (int)date('W'), 'year' => (int)date('Y')];
    }

    /**
     * Verifier si la table existe
     */
    public function tableExists(): bool
    {
        try {
            $this->db->query("SELECT 1 FROM editorial_journal LIMIT 1");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}