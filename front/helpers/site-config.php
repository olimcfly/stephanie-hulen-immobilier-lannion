<?php
/**
 * ══════════════════════════════════════════════════════════════
 * HELPER PARTAGÉ — front/helpers/site-config.php
 * Charge toutes les variables du conseiller depuis la DB
 * Sources : settings (key_name/value) + advisor_context (field_key/field_value)
 * ══════════════════════════════════════════════════════════════
 */

if (!function_exists('getSiteConfig')) {

    function getSiteConfig(?PDO $db = null): array {
        if (!$db) {
            try { $db = getDB(); } catch (Exception $e) { return _siteConfigDefaults(); }
        }

        $cfg = _siteConfigDefaults();

        // ── 1. Table settings (key_name / value) ─────────────────
        try {
            $rows = $db->query("SELECT key_name, value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
            $map = [
                'agent_name'      => 'advisor_name',
                'agent_firstname' => 'advisor_firstname',
                'agent_phone'     => 'advisor_phone',
                'agent_email'     => 'advisor_email',
                'agent_photo_url' => 'advisor_photo',
                'agent_city'      => 'advisor_city',
                'agent_network'   => 'advisor_network',
                'agent_rsac'      => 'advisor_rsac',
                'agent_title'     => 'advisor_title',
                'site_name'       => 'site_name',
                'site_url'        => 'site_url',
                'email_support'   => 'advisor_email',
                'phone'           => 'advisor_phone',
                'color_primary'   => 'color_primary',
                'color_secondary' => 'color_secondary',
                'brand_name'      => 'site_name',
                'brand_location'  => 'advisor_city',
            ];
            foreach ($map as $dbKey => $cfgKey) {
                if (!empty($rows[$dbKey])) {
                    $cfg[$cfgKey] = $rows[$dbKey];
                }
            }
        } catch (Exception $e) {
            error_log('[SiteConfig:settings] ' . $e->getMessage());
        }

        // ── 2. Table advisor_context (field_key / field_value) ────
        try {
            $rows = $db->query("SELECT field_key, field_value FROM advisor_context")
                       ->fetchAll(PDO::FETCH_KEY_PAIR);
            $map2 = [
                'advisor_name'      => 'advisor_name',
                'advisor_firstname' => 'advisor_firstname',
                'advisor_network'   => 'advisor_network',
                'advisor_zone'      => 'advisor_zone',
                'advisor_card'      => 'advisor_card',
                'advisor_style'     => 'advisor_style',
            ];
            foreach ($map2 as $dbKey => $cfgKey) {
                if (!empty($rows[$dbKey])) {
                    $cfg[$cfgKey] = $rows[$dbKey];
                }
            }
            // Signature complète si dispo
            if (!empty($rows['signature'])) {
                $cfg['advisor_signature'] = $rows['signature'];
            }
        } catch (Exception $e) {
            error_log('[SiteConfig:advisor_context] ' . $e->getMessage());
        }

        // ── 3. Dériver les valeurs calculées ─────────────────────
        // Numéro WhatsApp (même numéro, sans espaces)
        if (!empty($cfg['advisor_phone'])) {
            $wa = preg_replace('/\s+/', '', $cfg['advisor_phone']);
            // Convertir 06/07 → +336/+337
            if (preg_match('/^0([67]\d{8})$/', $wa, $m)) {
                $wa = '+33' . $m[1];
            }
            $cfg['advisor_whatsapp'] = $wa;
        }

        // Domaine canonique
        if (empty($cfg['site_url'])) {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $cfg['site_url'] = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }
        $cfg['site_url'] = rtrim($cfg['site_url'], '/');

        return $cfg;
    }

    function _siteConfigDefaults(): array {
        return [
            'advisor_name'      => 'Votre Conseiller',
            'advisor_firstname' => '',
            'advisor_phone'     => '',
            'advisor_email'     => '',
            'advisor_photo'     => '',
            'advisor_city'      => '',
            'advisor_network'   => '',
            'advisor_zone'      => '',
            'advisor_card'      => '',
            'advisor_title'     => 'Conseiller Immobilier',
            'advisor_style'     => '',
            'advisor_signature' => '',
            'advisor_whatsapp'  => '',
            'site_name'         => 'Immobilier',
            'site_url'          => '',
            'color_primary'     => '#1a4d7a',
            'color_secondary'   => '#d4a574',
        ];
    }

    /**
     * Raccourci : récupérer une seule clé de config
     * Usage : scfg('advisor_name')
     */
    function scfg(string $key, string $default = ''): string {
        static $cache = null;
        if ($cache === null) {
            try { $cache = getSiteConfig(); } catch (Exception $e) { $cache = _siteConfigDefaults(); }
        }
        return (string)($cache[$key] ?? $default);
    }
}