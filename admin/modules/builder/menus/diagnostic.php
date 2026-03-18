<?php
/**
 * Diagnostic Module Menus
 * Chemin: /admin/modules/builder/menus/diagnostic.php
 * 
 * Accès: /admin/modules/builder/menus/diagnostic.php
 * Teste: config, session, DB, tables, API menus, API pages
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<html><head><title>Diag Menus</title><style>
body{font-family:system-ui;padding:20px;background:#0f172a;color:#e2e8f0;max-width:800px;margin:0 auto}
h1{color:#a5b4fc;font-size:20px}
h2{color:#94a3b8;font-size:15px;margin-top:24px;border-bottom:1px solid #1e293b;padding-bottom:6px}
.ok{color:#10b981;font-weight:700} .fail{color:#ef4444;font-weight:700} .warn{color:#f59e0b;font-weight:700}
pre{background:#1e293b;padding:12px;border-radius:8px;overflow-x:auto;font-size:12px;border:1px solid #334155}
.test{padding:8px 0;border-bottom:1px solid #1e293b}
</style></head><body>";

echo "<h1>🔧 Diagnostic Module Menus</h1>";
echo "<p style='color:#64748b'>".date('Y-m-d H:i:s')." — Serveur: ".php_uname('n')."</p>";

// ══════════════════════════════════════════════════════════
// 1. CONFIG
// ══════════════════════════════════════════════════════════
echo "<h2>1. Config & Chemins</h2>";

$configPath = dirname(__DIR__, 3) . '/../config/config.php';
echo "<div class='test'>Config path: <code>$configPath</code> ";
if (file_exists($configPath)) {
    echo "<span class='ok'>✓ EXISTE</span>";
    require_once $configPath;
    echo "<br>INSTANCE_ID: <code>" . (defined('INSTANCE_ID') ? INSTANCE_ID : '❌ non défini') . "</code>";
    echo "<br>DB_NAME: <code>" . (defined('DB_NAME') ? DB_NAME : '❌ non défini') . "</code>";
} else {
    echo "<span class='fail'>✗ INTROUVABLE</span>";
    // Essayer d'autres chemins
    $alt = [
        dirname(__DIR__, 4) . '/config/config.php',
        $_SERVER['DOCUMENT_ROOT'] . '/config/config.php',
    ];
    foreach ($alt as $a) {
        echo "<br>Alt: <code>$a</code> → " . (file_exists($a) ? "<span class='ok'>EXISTE</span>" : "non");
    }
}
echo "</div>";

echo "<div class='test'>__DIR__: <code>".__DIR__."</code></div>";
echo "<div class='test'>DOCUMENT_ROOT: <code>".$_SERVER['DOCUMENT_ROOT']."</code></div>";

// ══════════════════════════════════════════════════════════
// 2. SESSION
// ══════════════════════════════════════════════════════════
echo "<h2>2. Session</h2>";
echo "<div class='test'>session_status: " . session_status() . " (2=active) ";
echo session_status() === 2 ? "<span class='ok'>✓ ACTIVE</span>" : "<span class='fail'>✗ INACTIVE</span>";
echo "</div>";

echo "<div class='test'>admin_id: ";
if (!empty($_SESSION['admin_id'])) {
    echo "<span class='ok'>✓ " . $_SESSION['admin_id'] . "</span>";
} else {
    echo "<span class='fail'>✗ VIDE</span>";
}
echo "</div>";

echo "<div class='test'>admin_logged_in: ";
echo !empty($_SESSION['admin_logged_in']) ? "<span class='ok'>✓</span>" : "<span class='warn'>non défini</span>";
echo "</div>";

echo "<div class='test'>Session keys: <code>" . implode(', ', array_keys($_SESSION ?? [])) . "</code></div>";

// ══════════════════════════════════════════════════════════
// 3. DATABASE
// ══════════════════════════════════════════════════════════
echo "<h2>3. Database</h2>";

$pdo = null;
try {
    if (function_exists('getDB')) {
        $pdo = getDB();
        $pdo->query("SELECT 1");
        echo "<div class='test'><span class='ok'>✓ Connexion DB OK</span></div>";
    } else {
        echo "<div class='test'><span class='fail'>✗ Fonction getDB() non disponible</span></div>";
    }
} catch (Exception $e) {
    echo "<div class='test'><span class='fail'>✗ Erreur DB: " . htmlspecialchars($e->getMessage()) . "</span></div>";
}

// ══════════════════════════════════════════════════════════
// 4. TABLES
// ══════════════════════════════════════════════════════════
echo "<h2>4. Tables pertinentes</h2>";

if ($pdo) {
    $tables = ['menus', 'menu_items', 'pages', 'builder_pages', 'articles', 'secteurs', 'guides'];
    foreach ($tables as $t) {
        echo "<div class='test'><code>$t</code>: ";
        try {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            echo "<span class='ok'>✓ $count lignes</span>";
            
            // Détails pour certaines tables
            if ($t === 'pages') {
                $pub = (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE status='published'")->fetchColumn();
                echo " ($pub publiées)";
            }
            if ($t === 'articles') {
                $pub = (int)$pdo->query("SELECT COUNT(*) FROM articles WHERE status='published'")->fetchColumn();
                echo " ($pub publiés)";
            }
            if ($t === 'secteurs') {
                $pub = (int)$pdo->query("SELECT COUNT(*) FROM secteurs WHERE status='published'")->fetchColumn();
                echo " ($pub publiés)";
            }
            if ($t === 'guides') {
                $act = (int)$pdo->query("SELECT COUNT(*) FROM guides WHERE status='active'")->fetchColumn();
                echo " ($act actifs)";
            }
            if ($t === 'menus') {
                $rows = $pdo->query("SELECT id, name, slug FROM menus ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
                echo "<pre>" . json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "</pre>";
            }
            if ($t === 'menu_items') {
                $rows = $pdo->query("SELECT id, menu_id, title, url, position FROM menu_items ORDER BY menu_id, position")->fetchAll(PDO::FETCH_ASSOC);
                echo "<pre>" . json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "</pre>";
            }
        } catch (Exception $e) {
            echo "<span class='fail'>✗ " . htmlspecialchars($e->getMessage()) . "</span>";
        }
        echo "</div>";
    }
}

// ══════════════════════════════════════════════════════════
// 5. TEST API PAGES (simulation)
// ══════════════════════════════════════════════════════════
echo "<h2>5. Test requêtes API Pages</h2>";

if ($pdo) {
    // Pages
    try {
        $rows = $pdo->query("SELECT title, slug, status FROM pages ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='test'>Pages (toutes): <span class='ok'>" . count($rows) . "</span>";
        echo "<pre>" . json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "</pre></div>";
    } catch (Exception $e) { echo "<div class='test'><span class='fail'>Pages: $e</span></div>"; }

    // Builder pages
    try {
        $rows = $pdo->query("SELECT title, slug, status FROM builder_pages ORDER BY title")->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='test'>Builder Pages: <span class='ok'>" . count($rows) . "</span>";
        if ($rows) echo "<pre>" . json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "</pre>";
        echo "</div>";
    } catch (Exception $e) { echo "<div class='test'><span class='warn'>builder_pages: " . $e->getMessage() . "</span></div>"; }

    // Articles
    try {
        $rows = $pdo->query("SELECT titre, slug, status FROM articles ORDER BY titre")->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='test'>Articles: <span class='ok'>" . count($rows) . "</span>";
        echo "<pre>" . json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "</pre></div>";
    } catch (Exception $e) { echo "<div class='test'><span class='fail'>Articles: $e</span></div>"; }

    // Secteurs
    try {
        $rows = $pdo->query("SELECT nom, slug, status FROM secteurs ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='test'>Secteurs: <span class='ok'>" . count($rows) . "</span>";
        echo "<pre>" . json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "</pre></div>";
    } catch (Exception $e) { echo "<div class='test'><span class='fail'>Secteurs: $e</span></div>"; }

    // Guides
    try {
        $rows = $pdo->query("SELECT name, slug, status FROM guides ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        echo "<div class='test'>Guides: <span class='ok'>" . count($rows) . "</span>";
        if ($rows) echo "<pre>" . json_encode($rows, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . "</pre>";
        echo "</div>";
    } catch (Exception $e) { echo "<div class='test'><span class='warn'>Guides: " . $e->getMessage() . "</span></div>"; }
}

// ══════════════════════════════════════════════════════════
// 6. TEST WRITE menu_items
// ══════════════════════════════════════════════════════════
echo "<h2>6. Test écriture menu_items</h2>";

if ($pdo) {
    try {
        // Insert test
        $stmt = $pdo->prepare("INSERT INTO menu_items (menu_id, title, url, icon, position, is_active) VALUES (1, '__DIAG_TEST__', '/diag-test', '', 999, 0)");
        $stmt->execute();
        $testId = $pdo->lastInsertId();
        echo "<div class='test'><span class='ok'>✓ INSERT OK</span> (id=$testId)</div>";

        // Delete test
        $pdo->prepare("DELETE FROM menu_items WHERE id = ?")->execute([$testId]);
        echo "<div class='test'><span class='ok'>✓ DELETE OK</span></div>";
    } catch (Exception $e) {
        echo "<div class='test'><span class='fail'>✗ Erreur écriture: " . htmlspecialchars($e->getMessage()) . "</span></div>";
    }
}

// ══════════════════════════════════════════════════════════
// 7. TEST API router menus
// ══════════════════════════════════════════════════════════
echo "<h2>7. API Router menus</h2>";

$apiRouter = dirname(__DIR__, 3) . '/modules/system/api/router.php';
echo "<div class='test'>Router path: <code>$apiRouter</code> ";
echo file_exists($apiRouter) ? "<span class='ok'>✓ EXISTE</span>" : "<span class='fail'>✗ INTROUVABLE</span>";
echo "</div>";

// Vérifier le handler menus
$handlerMenus = dirname(__DIR__, 3) . '/core/handlers/menus.php';
echo "<div class='test'>Handler menus: <code>$handlerMenus</code> ";
echo file_exists($handlerMenus) ? "<span class='ok'>✓ EXISTE</span>" : "<span class='fail'>✗ INTROUVABLE</span>";
echo "</div>";

// ══════════════════════════════════════════════════════════
// 8. RÉSUMÉ
// ══════════════════════════════════════════════════════════
echo "<h2>8. Résumé</h2>";

$checks = [
    'Config chargé'    => defined('DB_NAME'),
    'Session active'   => session_status() === 2,
    'Admin connecté'   => !empty($_SESSION['admin_id']),
    'DB connectée'     => $pdo !== null,
    'Table menus'      => $pdo && $pdo->query("SELECT 1 FROM menus LIMIT 1")->fetchColumn(),
    'Table menu_items' => $pdo && $pdo->query("SELECT 1 FROM menu_items LIMIT 1") !== false,
    'Pages dispo'      => $pdo && (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE status='published'")->fetchColumn() > 0,
];

echo "<div style='background:#1e293b;padding:16px;border-radius:10px;margin-top:12px'>";
foreach ($checks as $label => $ok) {
    $icon = $ok ? '✅' : '❌';
    echo "<div style='padding:4px 0'>$icon $label</div>";
}
echo "</div>";

echo "<p style='margin-top:20px;color:#64748b'>Accès API direct: <a href='/admin/modules/builder/menus/api-pages.php?ajax=1' target='_blank' style='color:#a5b4fc'>/admin/modules/builder/menus/api-pages.php?ajax=1</a></p>";

echo "</body></html>";