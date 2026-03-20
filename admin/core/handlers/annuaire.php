<?php
/**
 * API Handler: annuaire
 * Called via: /admin/api/router.php?module=annuaire&action=...
 * Delegates to: /admin/modules/content/annuaire/api.php
 */

$moduleApiFile = dirname(__DIR__, 2) . '/modules/content/annuaire/api.php';

if (file_exists($moduleApiFile)) {
    require_once $moduleApiFile;
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Module annuaire: api.php introuvable',
    ]);
}
