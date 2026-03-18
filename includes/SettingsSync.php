<?php
/**
 * ========================================================================
 * SettingsSync.php - Synchronisation Settings → Footers / Headers
 * ========================================================================
 * 
 * Chemin : /includes/SettingsSync.php
 * 
 * Quand les settings admin sont modifiés, cette classe propage
 * les changements dans les tables footers et headers.
 * 
 * Usage :
 *   SettingsSync::sync($pdo);                    // Sync tous les footers par défaut
 *   SettingsSync::syncAll($pdo);                 // Sync TOUS les footers (pas que default)
 *   SettingsSync::syncFooter($pdo, $footerId);   // Sync un footer spécifique
 */

class SettingsSync
{
    /**
     * Synchroniser les footers marqués "par défaut" (is_default = 1)
     * Appelé automatiquement après save des settings admin
     */
    public static function sync(PDO $pdo): array
    {
        $results = ['footers' => 0, 'headers' => 0, 'errors' => []];

        try {
            // Charger les settings admin
            $settings = self::loadSettings($pdo);
            if (empty($settings)) return $results;

            // ── Sync footers par défaut ──
            $results['footers'] = self::updateFooters($pdo, $settings, true);

            // ── Sync headers par défaut (si table existe) ──
            $results['headers'] = self::updateHeaders($pdo, $settings, true);

        } catch (PDOException $e) {
            $results['errors'][] = $e->getMessage();
            error_log("SettingsSync::sync() error: " . $e->getMessage());
        }

        return $results;
    }

    /**
     * Synchroniser TOUS les footers (pas seulement les défauts)
     */
    public static function syncAll(PDO $pdo): array
    {
        $results = ['footers' => 0, 'headers' => 0, 'errors' => []];

        try {
            $settings = self::loadSettings($pdo);
            if (empty($settings)) return $results;

            $results['footers'] = self::updateFooters($pdo, $settings, false);
            $results['headers'] = self::updateHeaders($pdo, $settings, false);
        } catch (PDOException $e) {
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Synchroniser un footer spécifique par ID
     */
    public static function syncFooter(PDO $pdo, int $footerId): bool
    {
        try {
            $settings = self::loadSettings($pdo);
            if (empty($settings)) return false;

            $data = self::mapSettingsToFooter($settings);
            $sets = [];
            $vals = [];
            foreach ($data as $col => $val) {
                if ($val !== null) {
                    $sets[] = "{$col} = ?";
                    $vals[] = $val;
                }
            }

            if (empty($sets)) return false;

            $vals[] = $footerId;
            $sql = "UPDATE footers SET " . implode(', ', $sets) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($vals);

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("SettingsSync::syncFooter({$footerId}) error: " . $e->getMessage());
            return false;
        }
    }

    // ================================================================
    // PRIVATE METHODS
    // ================================================================

    /**
     * Charger tous les admin_settings en tableau associatif
     */
    private static function loadSettings(PDO $pdo): array
    {
        $settings = [];
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM admin_settings WHERE setting_value IS NOT NULL AND setting_value != ''");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            error_log("SettingsSync::loadSettings() error: " . $e->getMessage());
        }
        return $settings;
    }

    /**
     * Mapper les settings admin vers les colonnes de la table footers
     */
    private static function mapSettingsToFooter(array $s): array
    {
        $data = [];

        // Contact
        if (isset($s['agent_name']))  $data['company_name']  = $s['agent_name'];
        if (isset($s['agent_phone'])) $data['phone']         = $s['agent_phone'];
        if (isset($s['agent_email'])) $data['email']         = $s['agent_email'];

        // Adresse (construire le texte complet)
        $addressParts = array_filter([
            $s['agent_address'] ?? '',
            trim(($s['agent_postal_code'] ?? '') . ' ' . ($s['agent_city'] ?? '')),
        ]);
        if (!empty($addressParts)) {
            $data['address'] = implode("\n", $addressParts);
        }

        // Logo
        if (isset($s['agent_logo_url'])) {
            $data['logo_url'] = $s['agent_logo_url'];
            $data['logo_alt'] = ($s['agent_name'] ?? 'Logo') . ' - Immobilier';
        }

        // Couleurs
        if (isset($s['color_primary']))   $data['bg_color']      = $s['color_primary'];
        if (isset($s['color_secondary'])) $data['heading_color']  = $s['color_secondary'];

        // Réseaux sociaux → JSON pour la colonne social_links
        $socials = self::buildSocialLinksJson($s);
        if ($socials !== null) {
            $data['social_links'] = $socials;
        }

        // Copyright dynamique
        if (isset($s['agent_name'])) {
            $year = date('Y');
            $data['copyright_text'] = "© {$year} {$s['agent_name']} — Tous droits réservés";
        }

        // Description (pour le footer)
        if (isset($s['agent_title']) && isset($s['agent_city'])) {
            $network = isset($s['agent_network']) ? " avec {$s['agent_network']}" : '';
            $data['description'] = "{$s['agent_title']} à {$s['agent_city']}{$network}.";
        }

        return $data;
    }

    /**
     * Construire le JSON des réseaux sociaux pour la colonne social_links
     */
    private static function buildSocialLinksJson(array $s): ?string
    {
        $networks = [
            'social_facebook'  => ['platform' => 'facebook',  'icon' => 'fab fa-facebook-f',  'label' => 'Facebook'],
            'social_instagram' => ['platform' => 'instagram', 'icon' => 'fab fa-instagram',    'label' => 'Instagram'],
            'social_linkedin'  => ['platform' => 'linkedin',  'icon' => 'fab fa-linkedin-in',  'label' => 'LinkedIn'],
            'social_youtube'   => ['platform' => 'youtube',   'icon' => 'fab fa-youtube',      'label' => 'YouTube'],
            'social_tiktok'    => ['platform' => 'tiktok',    'icon' => 'fab fa-tiktok',       'label' => 'TikTok'],
            'social_whatsapp'  => ['platform' => 'whatsapp',  'icon' => 'fab fa-whatsapp',     'label' => 'WhatsApp'],
        ];

        $links = [];
        foreach ($networks as $settingKey => $info) {
            if (!empty($s[$settingKey])) {
                $links[] = [
                    'platform' => $info['platform'],
                    'url'      => $s[$settingKey],
                    'icon'     => $info['icon'],
                    'label'    => $info['label'],
                ];
            }
        }

        return !empty($links) ? json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    }

    /**
     * Mettre à jour les footers dans la DB
     */
    private static function updateFooters(PDO $pdo, array $settings, bool $defaultOnly): int
    {
        $data = self::mapSettingsToFooter($settings);
        if (empty($data)) return 0;

        $sets = [];
        $vals = [];
        foreach ($data as $col => $val) {
            if ($val !== null) {
                $sets[] = "{$col} = ?";
                $vals[] = $val;
            }
        }

        if (empty($sets)) return 0;

        $where = $defaultOnly ? "WHERE is_default = 1" : "WHERE status = 'active'";
        $sql = "UPDATE footers SET " . implode(', ', $sets) . " {$where}";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($vals);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("SettingsSync::updateFooters() error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Mettre à jour les headers dans la DB (si table existe)
     */
    private static function updateHeaders(PDO $pdo, array $settings, bool $defaultOnly): int
    {
        // Vérifier si la table headers existe
        try {
            $check = $pdo->query("SHOW TABLES LIKE 'headers'");
            if ($check->rowCount() === 0) return 0;
        } catch (PDOException $e) {
            return 0;
        }

        $data = [];
        if (isset($settings['agent_phone'])) $data['phone']    = $settings['agent_phone'];
        if (isset($settings['agent_email'])) $data['email']    = $settings['agent_email'];
        if (isset($settings['agent_logo_url'])) $data['logo_url'] = $settings['agent_logo_url'];
        if (isset($settings['agent_name']))  $data['logo_alt'] = $settings['agent_name'];
        if (isset($settings['color_primary'])) $data['bg_color'] = $settings['color_primary'];

        if (empty($data)) return 0;

        $sets = [];
        $vals = [];
        foreach ($data as $col => $val) {
            $sets[] = "{$col} = ?";
            $vals[] = $val;
        }

        $where = $defaultOnly ? "WHERE is_default = 1" : "WHERE status = 'active'";

        try {
            $stmt = $pdo->prepare("UPDATE headers SET " . implode(', ', $sets) . " {$where}");
            $stmt->execute($vals);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("SettingsSync::updateHeaders() error: " . $e->getMessage());
            return 0;
        }
    }
}