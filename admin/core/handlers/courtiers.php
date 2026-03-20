<?php
/**
 * API Handler: courtiers
 * Called via: /admin/api/router.php?module=courtiers&action=...
 * Delegates to: /admin/modules/immobilier/courtiers/api.php
 */

$moduleApiFile = dirname(__DIR__, 2) . '/modules/immobilier/courtiers/api.php';

if (file_exists($moduleApiFile)) {
    require_once $moduleApiFile;
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Module courtiers: api.php introuvable',
    ]);
}
