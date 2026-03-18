<?php
// Chemins
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('CACHE_PATH', ROOT_PATH . '/cache');

// URLs
define('SITE_URL', 'https://www.mon-domaine.fr/');
define('ADMIN_URL', SITE_URL . '/admin');
define('API_URL', SITE_URL . '/api');

// Base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'ma_base_de_donnees');
define('DB_USER', 'mon_utilisateur_db');
define('DB_PASS', 'mon_mot_de_passe_db');

// Sécurité
define('SESSION_TIMEOUT', 3600);
define('ADMIN_EMAIL', 'admin@mon-domaine.fr');
define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'production');
define('DEBUG_MODE', ENVIRONMENT === 'development');

// SEO
define('SITE_TITLE', 'Mon Site Immobilier');
define('SITE_DESCRIPTION', 'Description du site.');
define('SITE_KEYWORDS', 'immobilier, ville, achat, vente');

// Pagination
define('ITEMS_PER_PAGE', 12);
define('ARTICLES_PER_PAGE', 10);
define('ADMIN_ITEMS_PER_PAGE', 50);

// ========================================
// CONFIGURATION IA POUR SEO
// ========================================
// Claude (Anthropic) sera utilisé en priorité si les deux sont configurés

// OpenAI API Key
define('OPENAI_API_KEY', 'sk-proj-VOTRE_CLE_OPENAI');

// Claude (Anthropic) API Key
define('ANTHROPIC_API_KEY', 'sk-ant-VOTRE_CLE_ANTHROPIC');
