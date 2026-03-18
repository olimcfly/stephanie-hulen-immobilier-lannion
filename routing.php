<?php
/**
 * TEST DIAGNOSTIC - routing.php
 * Upload ce fichier à la racine (/public_html/)
 * Accède à: https://eduardo-desul-immobilier.fr/routing.php
 */

echo "<h1>🔍 Diagnostic Routing</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .ok{color:green;} .err{color:red;} pre{background:#f5f5f5;padding:15px;border-radius:8px;}</style>";

// 1. Info Request
echo "<h2>1. Request Info</h2>";
echo "<pre>";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "</pre>";

// 2. Test slug extraction
$slug = 'financer-mon-projet';
echo "<h2>2. Test slug: <code>$slug</code></h2>";

// 3. Database connection
echo "<h2>3. Connexion DB</h2>";
$configPaths = [
    __DIR__ . '/config/config.php',
    __DIR__ . '/includes/classes/Database.php',
];

$dbLoaded = false;
foreach ($configPaths as $p) {
    if (file_exists($p)) {
        echo "<p class='ok'>✅ Trouvé: $p</p>";
        require_once $p;
        $dbLoaded = true;
    }
}

if (!$dbLoaded) {
    echo "<p class='err'>❌ Aucun fichier config trouvé</p>";
}

// 4. Test query
echo "<h2>4. Test requête page</h2>";
try {
    if (class_exists('Database')) {
        $db = Database::getInstance();
        echo "<p class='ok'>✅ Database::getInstance() OK</p>";
        
        // Chercher la page
        $stmt = $db->prepare("SELECT id, slug, status, title FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($page) {
            echo "<p class='ok'>✅ Page trouvée!</p>";
            echo "<pre>";
            print_r($page);
            echo "</pre>";
            
            if ($page['status'] === 'published') {
                echo "<p class='ok'>✅ Status = published (OK)</p>";
            } else {
                echo "<p class='err'>❌ Status = {$page['status']} (devrait être 'published')</p>";
            }
        } else {
            echo "<p class='err'>❌ Page non trouvée avec slug: $slug</p>";
            
            // Lister toutes les pages
            echo "<h3>Pages existantes:</h3>";
            $all = $db->query("SELECT id, slug, status FROM pages LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>";
            print_r($all);
            echo "</pre>";
        }
    }
} catch (Exception $e) {
    echo "<p class='err'>❌ Erreur: " . $e->getMessage() . "</p>";
}

// 5. Test fichiers front
echo "<h2>5. Fichiers Front</h2>";
$frontFiles = [
    '/front/index.php',
    '/front/page.php',
    '/front/templates/page-template.php',
    '/.htaccess',
];

foreach ($frontFiles as $f) {
    $path = __DIR__ . $f;
    if (file_exists($path)) {
        echo "<p class='ok'>✅ Existe: $f</p>";
    } else {
        echo "<p class='err'>❌ Manquant: $f</p>";
    }
}

// 6. Contenu .htaccess
echo "<h2>6. Contenu .htaccess</h2>";
$htaccess = __DIR__ . '/.htaccess';
if (file_exists($htaccess)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($htaccess)) . "</pre>";
} else {
    echo "<p class='err'>❌ .htaccess non trouvé à la racine</p>";
}

echo "<hr><p><strong>Supprime ce fichier après le test!</strong></p>";