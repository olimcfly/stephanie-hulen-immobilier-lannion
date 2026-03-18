<?php
// admin/modules/ads-launch/AdsLaunchService.php

class AdsLaunchService {
    
    private $db;
    private $userId;
    
    public function __construct($database, $userId) {
        $this->db = $database;
        $this->userId = $userId;
    }
    
    // ========== COMPTES PUBLICITAIRES ==========
    
    public function createAccount($data) {
        $sql = "INSERT INTO ads_accounts 
                (user_id, account_name, business_manager_id, ad_account_id, 
                 facebook_page_id, instagram_account_id, pixel_id, domain, 
                 gtm_id, currency, timezone) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $this->userId,
            $data['account_name'],
            $data['business_manager_id'] ?? null,
            $data['ad_account_id'] ?? null,
            $data['facebook_page_id'] ?? null,
            $data['instagram_account_id'] ?? null,
            $data['pixel_id'] ?? null,
            $data['domain'] ?? null,
            $data['gtm_id'] ?? null,
            $data['currency'] ?? 'EUR',
            $data['timezone'] ?? 'Europe/Paris'
        ]);
        
        if ($result) {
            $accountId = $this->db->lastInsertId();
            $this->initializeChecklist($accountId);
            return $accountId;
        }
        return false;
    }
    
    public function getAccounts() {
        $sql = "SELECT * FROM ads_accounts WHERE user_id = ? ORDER BY date_creation DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAccountById($accountId) {
        $sql = "SELECT * FROM ads_accounts WHERE id = ? AND user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountId, $this->userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateAccount($accountId, $data) {
        $sql = "UPDATE ads_accounts SET 
                account_name = ?, business_manager_id = ?, ad_account_id = ?, 
                facebook_page_id = ?, instagram_account_id = ?, pixel_id = ?, 
                domain = ?, domain_verified = ?, gtm_id = ?, currency = ?, timezone = ?, status = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['account_name'],
            $data['business_manager_id'] ?? null,
            $data['ad_account_id'] ?? null,
            $data['facebook_page_id'] ?? null,
            $data['instagram_account_id'] ?? null,
            $data['pixel_id'] ?? null,
            $data['domain'] ?? null,
            $data['domain_verified'] ?? FALSE,
            $data['gtm_id'] ?? null,
            $data['currency'] ?? 'EUR',
            $data['timezone'] ?? 'Europe/Paris',
            $data['status'] ?? 'setup',
            $accountId,
            $this->userId
        ]);
    }
    
    // ========== PRÉREQUIS TECHNIQUES ==========
    
    public function initializePrerequisites($accountId) {
        $sql = "INSERT INTO ads_prerequisites (account_id) VALUES (?)
                ON DUPLICATE KEY UPDATE date_modification = NOW()";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$accountId]);
    }
    
    public function getPrerequisites($accountId) {
        $sql = "SELECT * FROM ads_prerequisites WHERE account_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountId]);
        $prereq = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($prereq && $prereq['custom_events_configured']) {
            $prereq['custom_events_configured'] = json_decode($prereq['custom_events_configured'], true);
        }
        
        return $prereq;
    }
    
    public function updatePrerequisite($accountId, $field, $value) {
        $allowedFields = [
            'pixel_installed', 'pixel_code_copied', 'pixel_tested',
            'gtm_installed', 'gtm_code_copied', 'gtm_tested',
            'conversion_purchase', 'conversion_lead', 'conversion_viewcontent', 'conversion_addtocart'
        ];
        
        if (!in_array($field, $allowedFields)) {
            return false;
        }
        
        $sql = "UPDATE ads_prerequisites SET {$field} = ? WHERE account_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$value, $accountId]);
    }
    
    public function getPrerequisiteProgress($accountId) {
        $prereq = $this->getPrerequisites($accountId);
        if (!$prereq) return 0;
        
        $checks = [
            'pixel_installed', 'pixel_tested', 'gtm_installed', 'gtm_tested',
            'conversion_purchase', 'conversion_lead', 'conversion_viewcontent'
        ];
        
        $completed = 0;
        foreach ($checks as $check) {
            if ($prereq[$check]) $completed++;
        }
        
        return round(($completed / count($checks)) * 100);
    }
    
    // ========== AUDIENCES ==========
    
    public function createAudience($data) {
        $sql = "INSERT INTO ads_audiences 
                (account_id, name, audience_type, temperature, description, 
                 source_metric, lookback_days, size_estimate, configuration) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['account_id'],
            $data['name'],
            $data['audience_type'],
            $data['temperature'],
            $data['description'] ?? null,
            $data['source_metric'] ?? null,
            $data['lookback_days'] ?? 180,
            $data['size_estimate'] ?? null,
            json_encode($data['configuration'] ?? [])
        ]);
    }
    
    public function getAudiencesByAccount($accountId) {
        $sql = "SELECT * FROM ads_audiences 
                WHERE account_id = ? 
                ORDER BY temperature ASC, audience_type ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(function($a) {
            $a['configuration'] = json_decode($a['configuration'], true) ?? [];
            return $a;
        }, $results);
    }
    
    public function getRecommendedAudiences() {
        return [
            [
                'name' => 'Cold - Intérêts (CI) - Produit',
                'type' => 'ci',
                'temperature' => 'cold',
                'description' => 'Audience froide basée sur les intérêts produits'
            ],
            [
                'name' => 'Cold - Intérêts (CI) - Compétiteurs',
                'type' => 'ci',
                'temperature' => 'cold',
                'description' => 'Audience froide basée sur les compétiteurs'
            ],
            [
                'name' => 'Cold - Intérêts (CI) - Problème',
                'type' => 'ci',
                'temperature' => 'cold',
                'description' => 'Audience froide basée sur le problème résolu'
            ],
            [
                'name' => 'Warm - LAL 180j (Visiteurs)',
                'type' => 'lal',
                'temperature' => 'warm',
                'description' => 'Lookalike 180j des visiteurs du site'
            ],
            [
                'name' => 'Warm - LAL 180j (Leads)',
                'type' => 'lal',
                'temperature' => 'warm',
                'description' => 'Lookalike 180j des leads générés'
            ],
            [
                'name' => 'Warm - LAL 180j (Clients)',
                'type' => 'lal',
                'temperature' => 'warm',
                'description' => 'Lookalike 180j des clients'
            ],
            [
                'name' => 'Hot - TNT - Visiteurs (7j)',
                'type' => 'tnt',
                'temperature' => 'hot',
                'description' => 'Retargeting des visiteurs des 7 derniers jours'
            ],
            [
                'name' => 'Hot - TNT - Visiteurs (30j)',
                'type' => 'tnt',
                'temperature' => 'hot',
                'description' => 'Retargeting des visiteurs des 30 derniers jours'
            ],
            [
                'name' => 'Hot - TNT - Panier Abandonné',
                'type' => 'tnt',
                'temperature' => 'hot',
                'description' => 'Retargeting des utilisateurs avec panier abandonné'
            ],
            [
                'name' => 'Hot - TNT - Visiteurs Page Produit',
                'type' => 'tnt',
                'temperature' => 'hot',
                'description' => 'Retargeting des visiteurs de pages produit'
            ]
        ];
    }
    
    public function deleteAudience($audienceId, $accountId) {
        $sql = "DELETE FROM ads_audiences WHERE id = ? AND account_id IN 
                (SELECT id FROM ads_accounts WHERE user_id = ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$audienceId, $this->userId]);
    }
    
    // ========== CAMPAGNES ==========
    
    public function createCampaign($data) {
        $slug = $this->generateSlug($data['name']);
        
        $sql = "INSERT INTO ads_campaigns 
                (account_id, name, slug, objective, temperature, product_name, 
                 conversion_goal, daily_budget, lifetime_budget, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['account_id'],
            $data['name'],
            $slug,
            $data['objective'] ?? 'conversion',
            $data['temperature'],
            $data['product_name'] ?? null,
            $data['conversion_goal'] ?? null,
            $data['daily_budget'] ?? null,
            $data['lifetime_budget'] ?? null,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null
        ]);
    }
    
    public function getCampaignsByAccount($accountId) {
        $sql = "SELECT * FROM ads_campaigns 
                WHERE account_id IN (SELECT id FROM ads_accounts WHERE user_id = ?) 
                ORDER BY temperature ASC, date_creation DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function generateNomenclaturedName($type, $data) {
        $templates = [
            'campaign' => '{order}-{temperature}-{product}-{goal}',
            'adset' => '{audience_type}-{keyword}-{age_min}-{age_max}-{country}',
            'creative' => 'txt_{angle}-img_{visual}'
        ];
        
        $template = $templates[$type] ?? $templates['campaign'];
        
        $replacements = [
            '{order}' => $data['order'] ?? '1',
            '{temperature}' => ucfirst($data['temperature'] ?? 'cold'),
            '{product}' => $data['product'] ?? 'Produit',
            '{goal}' => $data['goal'] ?? 'Lead',
            '{audience_type}' => strtoupper($data['audience_type'] ?? 'CI'),
            '{keyword}' => str_replace(' ', '', $data['keyword'] ?? 'Keyword'),
            '{age_min}' => $data['age_min'] ?? '25',
            '{age_max}' => $data['age_max'] ?? '44',
            '{country}' => strtoupper($data['country'] ?? 'FR'),
            '{angle}' => str_replace(' ', '', $data['angle'] ?? 'Angle'),
            '{visual}' => str_replace(' ', '', $data['visual'] ?? 'Visual')
        ];
        
        $name = str_replace(array_keys($replacements), array_values($replacements), $template);
        return $name;
    }
    
    public function validateNomenclature($name, $type) {
        $rules = [
            'campaign' => '/^\d+-\w+-\w+-\w+$/',
            'adset' => '/^[A-Z]+-\w+-\d+-\d+-[A-Z]{2}$/',
            'creative' => '/^txt_\w+-img_\w+$/'
        ];
        
        $pattern = $rules[$type] ?? null;
        if (!$pattern) return ['valid' => false, 'message' => 'Type invalide'];
        
        if (preg_match($pattern, $name)) {
            return ['valid' => true, 'message' => 'Nomenclature valide ✓'];
        }
        
        return ['valid' => false, 'message' => 'La nomenclature ne respecte pas le format officiel'];
    }
    
    // ========== ENSEMBLES PUBLICITAIRES ==========
    
    public function createAdset($data) {
        $sql = "INSERT INTO ads_adsets 
                (campaign_id, name, audience_id, targeting_age_min, targeting_age_max, 
                 targeting_gender, targeting_countries, targeting_languages, 
                 targeting_interests, targeting_behaviors, daily_budget, bid_strategy) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['campaign_id'],
            $data['name'],
            $data['audience_id'] ?? null,
            $data['targeting_age_min'] ?? 18,
            $data['targeting_age_max'] ?? 65,
            $data['targeting_gender'] ?? null,
            json_encode($data['targeting_countries'] ?? ['FR']),
            json_encode($data['targeting_languages'] ?? []),
            json_encode($data['targeting_interests'] ?? []),
            json_encode($data['targeting_behaviors'] ?? []),
            $data['daily_budget'] ?? null,
            $data['bid_strategy'] ?? 'LOWEST_COST'
        ]);
    }
    
    // ========== CRÉATIVES ==========
    
    public function createCreative($data) {
        $sql = "INSERT INTO ads_creatives 
                (adset_id, name, angle, visual_description, headline, 
                 primary_text, description, cta_button, cta_url, 
                 image_url, video_url, creative_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['adset_id'],
            $data['name'],
            $data['angle'] ?? null,
            $data['visual_description'] ?? null,
            $data['headline'] ?? null,
            $data['primary_text'] ?? null,
            $data['description'] ?? null,
            $data['cta_button'] ?? 'LEARN_MORE',
            $data['cta_url'] ?? null,
            $data['image_url'] ?? null,
            $data['video_url'] ?? null,
            $data['creative_type'] ?? 'image'
        ]);
    }
    
    // ========== PERFORMANCES & KPIs ==========
    
    public function getPerformance($campaignId, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT * FROM ads_performance 
                WHERE campaign_id = ?";
        
        $params = [$campaignId];
        
        if ($dateFrom) {
            $sql .= " AND date >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND date <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function calculateKPIs($performance) {
        if (empty($performance)) return [];
        
        $total = [
            'impressions' => 0,
            'clicks' => 0,
            'spend' => 0,
            'conversions' => 0,
            'revenue' => 0,
            'video_views' => 0
        ];
        
        foreach ($performance as $data) {
            $total['impressions'] += $data['impressions'] ?? 0;
            $total['clicks'] += $data['clicks'] ?? 0;
            $total['spend'] += $data['spend'] ?? 0;
            $total['conversions'] += $data['conversions'] ?? 0;
            $total['revenue'] += $data['revenue'] ?? 0;
            $total['video_views'] += $data['video_views'] ?? 0;
        }
        
        $kpis = [
            'spend' => round($total['spend'], 2),
            'impressions' => $total['impressions'],
            'clicks' => $total['clicks'],
            'conversions' => $total['conversions'],
            'revenue' => round($total['revenue'], 2),
            'cpc' => $total['clicks'] > 0 ? round($total['spend'] / $total['clicks'], 2) : 0,
            'cpm' => $total['impressions'] > 0 ? round(($total['spend'] / $total['impressions']) * 1000, 2) : 0,
            'ctr' => $total['impressions'] > 0 ? round(($total['clicks'] / $total['impressions']) * 100, 2) : 0,
            'cpa' => $total['conversions'] > 0 ? round($total['spend'] / $total['conversions'], 2) : 0,
            'roas' => $total['spend'] > 0 ? round($total['revenue'] / $total['spend'], 2) : 0,
            'cost_per_lead' => $total['conversions'] > 0 ? round($total['spend'] / $total['conversions'], 2) : 0
        ];
        
        return $kpis;
    }
    
    // ========== ALERTES & RECOMMANDATIONS ==========
    
    public function generateAlerts($accountId) {
        $alerts = [];
        $campaigns = $this->getCampaignsByAccount($accountId);
        
        foreach ($campaigns as $campaign) {
            $perf = $this->getPerformance($campaign['id']);
            if (empty($perf)) continue;
            
            $kpis = $this->calculateKPIs($perf);
            
            // Alerte: CPC élevé
            if ($kpis['cpc'] > 2) {
                $this->createAlert([
                    'account_id' => $accountId,
                    'campaign_id' => $campaign['id'],
                    'alert_type' => 'high_cpc',
                    'severity' => 'warning',
                    'title' => 'CPC élevé détecté',
                    'message' => "Le CPC de la campagne est de {$kpis['cpc']}€",
                    'recommendation' => 'Augmentez le budget ou améliorez la créative'
                ]);
            }
            
            // Alerte: Faible ROAS
            if ($kpis['roas'] < 1 && $kpis['spend'] > 50) {
                $this->createAlert([
                    'account_id' => $accountId,
                    'campaign_id' => $campaign['id'],
                    'alert_type' => 'low_performance',
                    'severity' => 'critical',
                    'title' => 'ROAS faible',
                    'message' => "Le ROAS est seulement de {$kpis['roas']}",
                    'recommendation' => 'Revoyez votre targeting ou arrêtez la campagne'
                ]);
            }
            
            // Alerte: Fréquence élevée
            if ($perf[0]['frequency'] ?? 0 > 5) {
                $this->createAlert([
                    'account_id' => $accountId,
                    'campaign_id' => $campaign['id'],
                    'alert_type' => 'high_frequency',
                    'severity' => 'warning',
                    'title' => 'Fréquence trop élevée',
                    'message' => "La fréquence est de {$perf[0]['frequency']}",
                    'recommendation' => 'Augmentez la taille de l\'audience ou arrêtez la campagne'
                ]);
            }
        }
        
        return $this->getAlerts($accountId);
    }
    
    public function createAlert($data) {
        $sql = "INSERT INTO ads_alerts 
                (account_id, campaign_id, adset_id, creative_id, alert_type, 
                 severity, title, message, recommendation) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['account_id'],
            $data['campaign_id'] ?? null,
            $data['adset_id'] ?? null,
            $data['creative_id'] ?? null,
            $data['alert_type'],
            $data['severity'] ?? 'warning',
            $data['title'],
            $data['message'] ?? null,
            $data['recommendation'] ?? null
        ]);
    }
    
    public function getAlerts($accountId) {
        $sql = "SELECT * FROM ads_alerts 
                WHERE account_id = ? AND status IN ('new', 'acknowledged')
                ORDER BY severity DESC, date_creation DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$accountId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // ========== UTILITAIRES ==========
    
    private function generateSlug($name) {
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
    
    private function initializeChecklist($accountId) {
        $steps = [
            ['number' => 1, 'name' => 'Prérequis Techniques', 'description' => 'Configuration Pixel & GTM'],
            ['number' => 2, 'name' => 'Structure du Compte', 'description' => 'Business Manager & Compte Ads'],
            ['number' => 3, 'name' => 'Audiences Stratégiques', 'description' => 'CI, LAL, TNT'],
            ['number' => 4, 'name' => 'Campagnes', 'description' => 'Création selon nomenclature'],
            ['number' => 5, 'name' => 'Optimisation', 'description' => 'Suivi & Ajustements']
        ];
        
        $sql = "INSERT INTO ads_checklist (account_id, step_number, step_name, description) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        foreach ($steps as $step) {
            $stmt->execute([
                $accountId,
                $step['number'],
                $step['name'],
                $step['description']
            ]);
        }
    }
}
?>