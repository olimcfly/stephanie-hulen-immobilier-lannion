-- ═══════════════════════════════════════════════════════════════════
-- STEPHANIE HULEN IMMOBILIER LANNION - Schema SQL complet
-- ═══════════════════════════════════════════════════════════════════
--
-- Suggestion de noms :
--   Base de donnees : sh_immo_lannion
--   Utilisateur DB  : sh_immo_user
--
-- Execution :
--   1. Creer la base et l'utilisateur (voir section ci-dessous)
--   2. Executer ce script sur la base creee
--
-- ═══════════════════════════════════════════════════════════════════

-- ┌─────────────────────────────────────────────────────────────────┐
-- │  CREATION BASE + UTILISATEUR (a executer en root MySQL)        │
-- └─────────────────────────────────────────────────────────────────┘

-- CREATE DATABASE IF NOT EXISTS `sh_immo_lannion`
--   CHARACTER SET utf8mb4
--   COLLATE utf8mb4_unicode_ci;
--
-- CREATE USER 'sh_immo_user'@'localhost' IDENTIFIED BY 'CHANGEZ_MOI_mot_de_passe_fort';
-- GRANT ALL PRIVILEGES ON `sh_immo_lannion`.* TO 'sh_immo_user'@'localhost';
-- FLUSH PRIVILEGES;
--
-- USE `sh_immo_lannion`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- ═══════════════════════════════════════════════════════════════════
-- 1. CONFIGURATION & SYSTEME
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `group` VARCHAR(50) DEFAULT 'general',
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT,
    `provider` VARCHAR(50) DEFAULT NULL COMMENT 'openai, anthropic, etc.',
    `model` VARCHAR(100) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `advisor_context` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `field_key` VARCHAR(100) NOT NULL,
    `field_value` TEXT,
    `instance_id` VARCHAR(50) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_field_instance` (`field_key`, `instance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `modules` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `category` VARCHAR(50) DEFAULT NULL,
    `is_enabled` TINYINT(1) DEFAULT 1,
    `description` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `schema_migrations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `migration_key` VARCHAR(255) NOT NULL UNIQUE,
    `applied_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `launchpad_diagnostic` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `diagnostic_data` JSON,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `maintenance` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `status` VARCHAR(20) DEFAULT 'off',
    `message` TEXT,
    `scheduled_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═══════════════════════════════════════════════════════════════════
-- 2. IMMOBILIER (Biens, Estimations, RDV)
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `properties` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `titre` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `reference` VARCHAR(50) DEFAULT NULL,
    `mandat` VARCHAR(50) DEFAULT NULL,
    `type_bien` VARCHAR(50) DEFAULT NULL COMMENT 'appartement, maison, terrain, commerce, etc.',
    `transaction` VARCHAR(20) DEFAULT 'vente' COMMENT 'vente, location',
    `statut` VARCHAR(30) DEFAULT 'available' COMMENT 'available, unavailable, sold, rented',
    `prix` DECIMAL(12,2) DEFAULT NULL,
    `surface` DECIMAL(10,2) DEFAULT NULL,
    `pieces` INT DEFAULT NULL,
    `description` TEXT,
    `adresse` VARCHAR(255) DEFAULT NULL,
    `ville` VARCHAR(100) DEFAULT NULL,
    `code_postal` VARCHAR(10) DEFAULT NULL,
    `latitude` DECIMAL(10,8) DEFAULT NULL,
    `longitude` DECIMAL(11,8) DEFAULT NULL,
    `dpe` VARCHAR(10) DEFAULT NULL COMMENT 'A, B, C, D, E, F, G',
    `photos` JSON DEFAULT NULL,
    `is_featured` TINYINT(1) DEFAULT 0,
    `contact_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_statut` (`statut`),
    INDEX `idx_type_bien` (`type_bien`),
    INDEX `idx_ville` (`ville`),
    INDEX `idx_transaction` (`transaction`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `estimations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) DEFAULT NULL,
    `prenom` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `telephone` VARCHAR(30) DEFAULT NULL,
    `adresse` VARCHAR(255) DEFAULT NULL,
    `ville` VARCHAR(100) DEFAULT NULL,
    `code_postal` VARCHAR(10) DEFAULT NULL,
    `surface` DECIMAL(10,2) DEFAULT NULL,
    `pieces` INT DEFAULT NULL,
    `type_bien` VARCHAR(50) DEFAULT NULL,
    `etat_bien` VARCHAR(30) DEFAULT NULL COMMENT 'neuf, bon, moyen, renovation',
    `valeur_estimee` DECIMAL(12,2) DEFAULT NULL,
    `estimation_basse` DECIMAL(12,2) DEFAULT NULL,
    `estimation_haute` DECIMAL(12,2) DEFAULT NULL,
    `statut` VARCHAR(30) DEFAULT 'en_attente' COMMENT 'en_attente, traitee, convertie',
    `notes` TEXT,
    `rgpd_consent` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_statut` (`statut`),
    INDEX `idx_ville` (`ville`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rdv` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `titre` VARCHAR(255) DEFAULT NULL,
    `description` TEXT,
    `type` VARCHAR(30) DEFAULT 'visite' COMMENT 'visite, consultation, reunion, signature, autre',
    `rdv_date` DATETIME NOT NULL,
    `nom` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `telephone` VARCHAR(30) DEFAULT NULL,
    `adresse` VARCHAR(255) DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `statut` VARCHAR(30) DEFAULT 'pending' COMMENT 'pending, scheduled, confirmed, completed, cancelled, no_show',
    `color` VARCHAR(7) DEFAULT NULL COMMENT 'Hex color code',
    `notes` TEXT,
    `lead_id` INT UNSIGNED DEFAULT NULL,
    `contact_id` INT UNSIGNED DEFAULT NULL,
    `property_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_rdv_date` (`rdv_date`),
    INDEX `idx_statut` (`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═══════════════════════════════════════════════════════════════════
-- 3. FINANCEMENT (Courtiers & Leads financement)
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `financement_courtiers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `company` VARCHAR(150) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `zone_geo` VARCHAR(150) DEFAULT NULL,
    `type` ENUM('courtier','mandataire','apporteur','partenaire','notaire') DEFAULT 'courtier',
    `status` ENUM('actif','prospect','inactif','pause') DEFAULT 'prospect',
    `commission_rate` DECIMAL(5,2) DEFAULT NULL,
    `reco_count` INT DEFAULT 0,
    `revenu_total` DECIMAL(12,2) DEFAULT 0.00,
    `lead_id` INT UNSIGNED DEFAULT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `financement_leads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) DEFAULT NULL,
    `prenom` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `telephone` VARCHAR(30) DEFAULT NULL,
    `montant_projet` DECIMAL(12,2) DEFAULT NULL,
    `apport` DECIMAL(12,2) DEFAULT NULL,
    `type_projet` VARCHAR(50) DEFAULT NULL COMMENT 'achat_residence, investissement, etc.',
    `statut` VARCHAR(30) DEFAULT 'nouveau',
    `courtier_id` INT UNSIGNED DEFAULT NULL,
    `commission_montant` DECIMAL(10,2) DEFAULT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_statut` (`statut`),
    CONSTRAINT `fk_finlead_courtier` FOREIGN KEY (`courtier_id`) REFERENCES `financement_courtiers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═══════════════════════════════════════════════════════════════════
-- 4. CRM & LEADS
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `pipeline_stages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `color` VARCHAR(7) DEFAULT '#6366f1',
    `position` INT DEFAULT 0,
    `is_won` TINYINT(1) DEFAULT 0,
    `is_lost` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leads` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) DEFAULT NULL,
    `prenom` VARCHAR(100) DEFAULT NULL,
    `firstname` VARCHAR(100) DEFAULT NULL,
    `lastname` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `telephone` VARCHAR(30) DEFAULT NULL,
    `mobile` VARCHAR(30) DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `postal_code` VARCHAR(10) DEFAULT NULL,
    `country` VARCHAR(50) DEFAULT 'France',
    `statut` VARCHAR(30) DEFAULT 'nouveau' COMMENT 'nouveau, hot, warm, cold, traite, pending',
    `source` VARCHAR(50) DEFAULT NULL COMMENT 'site, gmb, social, referral, etc.',
    `score` INT DEFAULT 0,
    `temperature` VARCHAR(10) DEFAULT NULL COMMENT 'hot, warm, cold',
    `type` VARCHAR(30) DEFAULT NULL COMMENT 'financing, real_estate, etc.',
    `interest` VARCHAR(100) DEFAULT NULL,
    `property_type` VARCHAR(50) DEFAULT NULL,
    `budget_min` DECIMAL(12,2) DEFAULT NULL,
    `budget_max` DECIMAL(12,2) DEFAULT NULL,
    `estimated_value` DECIMAL(12,2) DEFAULT NULL,
    `tags` VARCHAR(500) DEFAULT NULL,
    `notes` TEXT,
    `capture_page_id` INT UNSIGNED DEFAULT NULL,
    `pipeline_stage_id` INT UNSIGNED DEFAULT NULL,
    `gdpr_consent` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_statut` (`statut`),
    INDEX `idx_source` (`source`),
    INDEX `idx_email` (`email`),
    CONSTRAINT `fk_lead_pipeline` FOREIGN KEY (`pipeline_stage_id`) REFERENCES `pipeline_stages`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `contacts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `civility` VARCHAR(10) DEFAULT NULL,
    `firstname` VARCHAR(100) DEFAULT NULL,
    `lastname` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `mobile` VARCHAR(30) DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `postal_code` VARCHAR(10) DEFAULT NULL,
    `country` VARCHAR(50) DEFAULT 'France',
    `company` VARCHAR(150) DEFAULT NULL,
    `job_title` VARCHAR(100) DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT NULL,
    `status` VARCHAR(30) DEFAULT 'active',
    `rating` INT DEFAULT NULL,
    `birthday` DATE DEFAULT NULL,
    `tags` VARCHAR(500) DEFAULT NULL,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_contacts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(100) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `telephone` VARCHAR(30) DEFAULT NULL,
    `statut` VARCHAR(30) DEFAULT 'nouveau',
    `source` VARCHAR(50) DEFAULT NULL,
    `score` INT DEFAULT 0,
    `notes` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_interactions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `contact_id` INT UNSIGNED NOT NULL,
    `type` VARCHAR(50) NOT NULL COMMENT 'appel, email, visite, note, etc.',
    `notes` TEXT,
    `user_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_contact` (`contact_id`),
    CONSTRAINT `fk_interaction_contact` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_sequences` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `crm_sequence_steps` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sequence_id` INT UNSIGNED NOT NULL,
    `step_number` INT DEFAULT 1,
    `content` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_seqstep_sequence` FOREIGN KEY (`sequence_id`) REFERENCES `crm_sequences`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `scoring_rules` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rule_name` VARCHAR(100) NOT NULL,
    `condition` TEXT,
    `points` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═══════════════════════════════════════════════════════════════════
-- 5. CONTENU & SEO (Pages, Articles, Secteurs, Annuaire)
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `pages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `content` LONGTEXT,
    `status` VARCHAR(20) DEFAULT 'draft' COMMENT 'published, draft, archived',
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `seo_score` INT DEFAULT NULL,
    `word_count` INT DEFAULT NULL,
    `google_indexed` VARCHAR(10) DEFAULT 'unknown' COMMENT 'yes, no, unknown',
    `og_image` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `articles` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `content` LONGTEXT,
    `excerpt` TEXT,
    `status` VARCHAR(20) DEFAULT 'draft' COMMENT 'published, draft',
    `category` VARCHAR(100) DEFAULT NULL,
    `author` VARCHAR(100) DEFAULT NULL,
    `image` VARCHAR(500) DEFAULT NULL,
    `tags` VARCHAR(500) DEFAULT NULL,
    `focus_keyword` VARCHAR(100) DEFAULT NULL,
    `main_keyword` VARCHAR(100) DEFAULT NULL,
    `seo_score` INT DEFAULT NULL,
    `semantic_score` INT DEFAULT NULL,
    `word_count` INT DEFAULT NULL,
    `google_indexed` VARCHAR(10) DEFAULT 'unknown',
    `is_featured` TINYINT(1) DEFAULT 0,
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `published_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_category` (`category`),
    INDEX `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `secteurs` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `description` TEXT,
    `ville` VARCHAR(100) DEFAULT NULL,
    `code_postal` VARCHAR(10) DEFAULT NULL,
    `type_secteur` VARCHAR(50) DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'draft',
    `seo_score` INT DEFAULT NULL,
    `og_image` VARCHAR(500) DEFAULT NULL,
    `meta_title` VARCHAR(255) DEFAULT NULL,
    `meta_description` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_ville` (`ville`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `annuaire` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nom` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `categorie` VARCHAR(100) DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'draft',
    `adresse` VARCHAR(255) DEFAULT NULL,
    `ville` VARCHAR(100) DEFAULT NULL,
    `telephone` VARCHAR(30) DEFAULT NULL,
    `site_web` VARCHAR(500) DEFAULT NULL,
    `gmb_url` VARCHAR(500) DEFAULT NULL,
    `note` DECIMAL(3,1) DEFAULT NULL,
    `audience` VARCHAR(100) DEFAULT NULL,
    `is_featured` TINYINT(1) DEFAULT 0,
    `secteur_id` INT UNSIGNED DEFAULT NULL,
    `og_image` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_categorie` (`categorie`),
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_annuaire_secteur` FOREIGN KEY (`secteur_id`) REFERENCES `secteurs`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `guides` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL UNIQUE,
    `content` LONGTEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seo_scores` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `context` VARCHAR(30) NOT NULL COMMENT 'landing, article, page, secteur',
    `entity_id` INT UNSIGNED NOT NULL,
    `score` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_context_entity` (`context`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═══════════════════════════════════════════════════════════════════
-- 6. IA & CONTENU GENERE
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `ai_prompts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `category` VARCHAR(50) DEFAULT NULL,
    `content` TEXT NOT NULL,
    `is_default` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_agents` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `configuration` JSON DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ai_generated_content` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `type` VARCHAR(50) DEFAULT NULL COMMENT 'article, post, email, etc.',
    `title` VARCHAR(255) DEFAULT NULL,
    `content` LONGTEXT,
    `status` VARCHAR(20) DEFAULT 'draft',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `editorial_journal` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) DEFAULT NULL,
    `content` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═══════════════════════════════════════════════════════════════════
-- 7. GOOGLE MY BUSINESS (Scraper, Contacts, Sequences)
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `gmb_searches` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `query` VARCHAR(255) NOT NULL,
    `location` VARCHAR(150) DEFAULT NULL,
    `radius` INT DEFAULT NULL,
    `results_count` INT DEFAULT 0,
    `status` VARCHAR(20) DEFAULT 'pending',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gmb_results` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `search_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(200) DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `website` VARCHAR(500) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `rating` DECIMAL(2,1) DEFAULT NULL,
    `reviews_count` INT DEFAULT 0,
    `place_id` VARCHAR(255) DEFAULT NULL,
    `latitude` DECIMAL(10,8) DEFAULT NULL,
    `longitude` DECIMAL(11,8) DEFAULT NULL,
    `is_converted` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_search` (`search_id`),
    CONSTRAINT `fk_gmbresult_search` FOREIGN KEY (`search_id`) REFERENCES `gmb_searches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gmb_contacts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(200) DEFAULT NULL,
    `email` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(30) DEFAULT NULL,
    `website` VARCHAR(500) DEFAULT NULL,
    `category` VARCHAR(100) DEFAULT NULL,
    `address` VARCHAR(255) DEFAULT NULL,
    `city` VARCHAR(100) DEFAULT NULL,
    `rating` DECIMAL(2,1) DEFAULT NULL,
    `reviews_count` INT DEFAULT 0,
    `source_search_id` INT UNSIGNED DEFAULT NULL,
    `source_result_id` INT UNSIGNED DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'new' COMMENT 'new, contacted, converted, rejected',
    `is_converted` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    CONSTRAINT `fk_gmbcontact_search` FOREIGN KEY (`source_search_id`) REFERENCES `gmb_searches`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_gmbcontact_result` FOREIGN KEY (`source_result_id`) REFERENCES `gmb_results`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gmb_contact_lists` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gmb_contact_list_members` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `list_id` INT UNSIGNED NOT NULL,
    `contact_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_list_contact` (`list_id`, `contact_id`),
    CONSTRAINT `fk_gmbmember_list` FOREIGN KEY (`list_id`) REFERENCES `gmb_contact_lists`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_gmbmember_contact` FOREIGN KEY (`contact_id`) REFERENCES `gmb_contacts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gmb_email_sequences` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gmb_sequence_steps` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `sequence_id` INT UNSIGNED NOT NULL,
    `step_order` INT DEFAULT 1,
    `subject` VARCHAR(255) DEFAULT NULL,
    `body_html` TEXT,
    `delay_days` INT DEFAULT 0,
    `delay_hours` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_gmbstep_sequence` FOREIGN KEY (`sequence_id`) REFERENCES `gmb_email_sequences`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gmb_email_sends` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `contact_id` INT UNSIGNED DEFAULT NULL,
    `sequence_id` INT UNSIGNED DEFAULT NULL,
    `step_id` INT UNSIGNED DEFAULT NULL,
    `list_id` INT UNSIGNED DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, sent, opened, clicked, bounced',
    `opened_at` DATETIME DEFAULT NULL,
    `clicked_at` DATETIME DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_contact` (`contact_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gmb_email_validations` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) NOT NULL,
    `is_valid` TINYINT(1) DEFAULT NULL,
    `reason` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gmb_publications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) DEFAULT NULL,
    `content` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gmb_questions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `question` TEXT,
    `answer` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gmb_reviews` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `rating` INT DEFAULT NULL,
    `review_text` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═══════════════════════════════════════════════════════════════════
-- 8. SOCIAL MEDIA & NEUROPERSONAS
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `neuropersona_types` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neuropersona_config` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `neuropersona_campagnes` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `status` VARCHAR(20) DEFAULT 'draft',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `social_posts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `platform` VARCHAR(20) NOT NULL COMMENT 'linkedin, facebook, tiktok, instagram',
    `post_type` VARCHAR(30) DEFAULT NULL,
    `content` TEXT,
    `media_url` VARCHAR(500) DEFAULT NULL,
    `hashtags` VARCHAR(500) DEFAULT NULL,
    `scheduled_at` DATETIME DEFAULT NULL,
    `published_at` DATETIME DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'draft' COMMENT 'draft, scheduled, published',
    `persona_id` INT UNSIGNED DEFAULT NULL,
    `engagement_likes` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_platform` (`platform`),
    INDEX `idx_status` (`status`),
    INDEX `idx_scheduled` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `facebook_posts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) DEFAULT NULL,
    `content` TEXT,
    `post_type` VARCHAR(30) DEFAULT NULL,
    `media_url` VARCHAR(500) DEFAULT NULL,
    `link_url` VARCHAR(500) DEFAULT NULL,
    `scheduled_at` DATETIME DEFAULT NULL,
    `published_at` DATETIME DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'draft',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tiktok_scripts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) DEFAULT NULL,
    `content` TEXT,
    `status` VARCHAR(20) DEFAULT 'draft',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═══════════════════════════════════════════════════════════════════
-- 9. EMAIL & MESSAGES
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `email_messages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `to_email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) DEFAULT NULL,
    `content` TEXT,
    `status` VARCHAR(20) DEFAULT 'pending' COMMENT 'pending, sent, failed',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═══════════════════════════════════════════════════════════════════
-- 10. WEBSITE (Menus, Sections, Media, Headers, Footers)
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `websites` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `domain` VARCHAR(255) NOT NULL,
    `domain_verified` TINYINT(1) DEFAULT 0,
    `domain_verified_at` DATETIME DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'draft',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menus` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `menu_items` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `menu_id` INT UNSIGNED NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `url` VARCHAR(500) DEFAULT NULL,
    `position` INT DEFAULT 0,
    `parent_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_menuitem_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sections` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) DEFAULT NULL,
    `content` LONGTEXT,
    `position` INT DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `headers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `content` LONGTEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `footers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `content` LONGTEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `media` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `filename` VARCHAR(255) NOT NULL,
    `url` VARCHAR(500) NOT NULL,
    `size` INT UNSIGNED DEFAULT NULL COMMENT 'Taille en octets',
    `mime_type` VARCHAR(100) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `local_partners` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ═══════════════════════════════════════════════════════════════════
-- 11. ANALYTICS & TRACKING
-- ═══════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `page_views` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `page_url` VARCHAR(500) NOT NULL,
    `page_title` VARCHAR(255) DEFAULT NULL,
    `referrer` VARCHAR(500) DEFAULT NULL,
    `source` VARCHAR(100) DEFAULT NULL,
    `medium` VARCHAR(100) DEFAULT NULL,
    `device` VARCHAR(30) DEFAULT NULL COMMENT 'desktop, mobile, tablet',
    `session_id` VARCHAR(100) DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(500) DEFAULT NULL,
    `viewed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_viewed_at` (`viewed_at`),
    INDEX `idx_page_url` (`page_url`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `conversion_events` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `event_type` VARCHAR(50) NOT NULL COMMENT 'form_submit, phone_click, cta_click, etc.',
    `event_label` VARCHAR(150) DEFAULT NULL,
    `page_url` VARCHAR(500) DEFAULT NULL,
    `value` DECIMAL(10,2) DEFAULT NULL,
    `session_id` VARCHAR(100) DEFAULT NULL,
    `lead_id` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_event_type` (`event_type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;

-- ═══════════════════════════════════════════════════════════════════
-- FIN DU SCHEMA
-- Total : 50+ tables couvrant l'ensemble du CMS immobilier
-- ═══════════════════════════════════════════════════════════════════
