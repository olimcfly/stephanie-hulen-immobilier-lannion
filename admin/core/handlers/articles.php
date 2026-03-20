<?php
/**
 * API Handler: articles
 * Called via: /admin/api/router.php?module=articles&action=...
 * Delegue au point d'entree local du module Articles
 */

require_once dirname(__DIR__, 2) . '/modules/content/articles/api.php';
