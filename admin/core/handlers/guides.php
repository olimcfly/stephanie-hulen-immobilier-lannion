<?php
/**
 * API Handler: guides
 * Called via: /admin/api/router.php?module=guides&action=...
 * Delegates to: /admin/modules/content/guides/api.php
 */

$moduleApiFile = dirname(__DIR__, 2) . '/modules/content/guides/api.php';

if (file_exists($moduleApiFile)) {
    require_once $moduleApiFile;
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Module guides: api.php introuvable',
    ]);
}
