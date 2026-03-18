<?php
/**
 * Configuration centrale IA
 * /includes/config/ia.config.php
 */

// Modèle Claude
define('AI_MODEL',      'claude-sonnet-4-6');
define('AI_MAX_TOKENS', 2000);
define('AI_TIMEOUT',    60);
define('AI_API_URL',    'https://api.anthropic.com/v1/messages');

// Source de la clé API : 'db' ou 'env'
define('AI_KEY_SOURCE', 'db');
define('AI_KEY_DB_KEY', 'claude_api_key'); // nom dans la table settings
define('AI_KEY_ENV_VAR', 'CLAUDE_API_KEY'); // fallback env