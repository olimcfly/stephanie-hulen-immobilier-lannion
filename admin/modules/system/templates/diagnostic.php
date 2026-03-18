<?php
/**
 * ════════════════════════════════════════════════════════════
 * admin/modules/system/design/diagnostic.php
 * ════════════════════════════════════════════════════════════
 * Diagnostic DB — Vérifie la structure headers/footers
 * Crée les colonnes manquantes si besoin
 */

define('ADMIN_ROUTER', true);

// Déterminer le chemin racine
$_rootPath = dirname(__DIR__, 4); // admin/modules/system/design → racine
$_initPath = $_rootPath . '/includes/init.php';

if (!file_exists($_initPath)) {
    http_response_code(500);
    die('Impossible de charger init.php. Chemin cherché: ' . $_initPath);
}

require_once $_initPath;

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin_id'])) { http_response_code(403); die('Accès refusé'); }

try {
    $pdo = getDB();
} catch (Exception $e) {
    http_response_code(500);
    die('Erreur BD: ' . $e->getMessage());
}

// ... reste du code identique
?>

// ── Test 1 : Table headers ────────────────────────────────────
try {
    $cols = $pdo->query("DESCRIBE headers")->fetchAll(PDO::FETCH_ASSOC);
    $colMap = array_column($cols, null, 'Field');
    
    $result['headers'] = [
        'exists' => true,
        'cols_count' => count($cols),
        'required' => [
            'logo_url' => isset($colMap['logo_url']),
            'logo_width' => isset($colMap['logo_width']),
            'menu_items' => isset($colMap['menu_items']),
            'cta_text' => isset($colMap['cta_text']),
            'cta_link' => isset($colMap['cta_link']),
            'bg_color' => isset($colMap['bg_color']),
            'text_color' => isset($colMap['text_color']),
            'hover_color' => isset($colMap['hover_color']),
            'phone_number' => isset($colMap['phone_number']),
        ]
    ];
} catch (Exception $e) {
    $errors[] = 'Headers: ' . $e->getMessage();
}

// ── Test 2 : Table footers ────────────────────────────────────
try {
    $cols = $pdo->query("DESCRIBE footers")->fetchAll(PDO::FETCH_ASSOC);
    $colMap = array_column($cols, null, 'Field');
    
    $result['footers'] = [
        'exists' => true,
        'cols_count' => count($cols),
        'required' => [
            'logo_url' => isset($colMap['logo_url']),
            'logo_width' => isset($colMap['logo_width']),
            'columns' => isset($colMap['columns']),
            'phone' => isset($colMap['phone']),
            'email' => isset($colMap['email']),
            'address' => isset($colMap['address']),
            'bg_color' => isset($colMap['bg_color']),
            'text_color' => isset($colMap['text_color']),
            'link_color' => isset($colMap['link_color']),
            'social_links' => isset($colMap['social_links']),
        ]
    ];
} catch (Exception $e) {
    $errors[] = 'Footers: ' . $e->getMessage();
}

// ── Test 3 : Enregistrements actifs ──────────────────────────
try {
    $activeHeader = $pdo->query("SELECT id, name FROM headers WHERE status='active' LIMIT 1")->fetch();
    $activeFooter = $pdo->query("SELECT id, name FROM footers WHERE status='active' LIMIT 1")->fetch();
    
    $result['active'] = [
        'header' => $activeHeader ?: null,
        'footer' => $activeFooter ?: null,
    ];
} catch (Exception $e) {
    $errors[] = 'Active records: ' . $e->getMessage();
}

// ── Réponse ──────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => empty($errors),
    'result' => $result,
    'errors' => $errors,
    'timestamp' => date('Y-m-d H:i:s'),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
```

**TEST :** Lance dans le navigateur :
```
http://localhost/admin/modules/system/design/diagnostic.php