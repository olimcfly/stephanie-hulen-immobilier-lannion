<?php
/**
 * DIAGNOSTIC - À supprimer après debug
 * Uploader à la racine : /diagnostic.php
 * Accéder via : https://eduardo-desul-immobilier.fr/diagnostic.php
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Diagnostic du site</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;max-width:1000px;margin:auto}pre{background:#f5f5f5;padding:15px;overflow:auto;border-radius:8px}.ok{color:green}.err{color:red}h2{margin-top:30px;border-bottom:2px solid #ddd;padding-bottom:10px}</style>";

// 1. Chemins
echo "<h2>1. Chemins</h2>";
echo "<pre>";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "\n";
echo "</pre>";

// 2. Database
echo "<h2>2. Base de données</h2>";
$dbPath = __DIR__ . '/includes/classes/Database.php';
if (file_exists($dbPath)) {
    echo "<p class='ok'>✅ Database.php trouvé</p>";
    require_once $dbPath;
    try {
        $db = Database::getInstance();
        echo "<p class='ok'>✅ Connexion DB OK</p>";
        
        // 3. Page accueil
        echo "<h2>3. Page 'accueil' dans la DB</h2>";
        $stmt = $db->query("SELECT id, slug, title, status, header_id, footer_id, 
                           LEFT(content, 200) as content_preview,
                           LEFT(custom_css, 200) as css_preview
                           FROM pages WHERE slug = 'accueil'");
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($page) {
            echo "<p class='ok'>✅ Page 'accueil' trouvée (ID: {$page['id']})</p>";
            echo "<pre>";
            echo "Status: " . ($page['status'] ?? 'NULL') . "\n";
            echo "Header ID: " . ($page['header_id'] ?? 'NULL') . "\n";
            echo "Footer ID: " . ($page['footer_id'] ?? 'NULL') . "\n";
            echo "Content (200 premiers chars): " . htmlspecialchars($page['content_preview'] ?? 'VIDE') . "\n";
            echo "CSS (200 premiers chars): " . htmlspecialchars($page['css_preview'] ?? 'VIDE') . "\n";
            echo "</pre>";
            
            // Vérifier si le contenu est vraiment là
            $stmtFull = $db->query("SELECT LENGTH(content) as content_len, LENGTH(custom_css) as css_len FROM pages WHERE slug = 'accueil'");
            $lengths = $stmtFull->fetch(PDO::FETCH_ASSOC);
            echo "<p>Taille content: <strong>{$lengths['content_len']}</strong> caractères</p>";
            echo "<p>Taille CSS: <strong>{$lengths['css_len']}</strong> caractères</p>";
            
            if ($lengths['content_len'] < 100) {
                echo "<p class='err'>⚠️ Le contenu semble trop court ou vide!</p>";
            }
            if ($lengths['css_len'] < 100) {
                echo "<p class='err'>⚠️ Le CSS semble trop court ou vide!</p>";
            }
            
        } else {
            echo "<p class='err'>❌ Page 'accueil' NON trouvée dans la DB!</p>";
            
            // Lister les pages existantes
            echo "<h3>Pages existantes :</h3>";
            $pages = $db->query("SELECT id, slug, status FROM pages ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            echo "<pre>";
            foreach ($pages as $p) {
                echo "ID: {$p['id']} | Slug: {$p['slug']} | Status: {$p['status']}\n";
            }
            echo "</pre>";
        }
        
        // 4. Headers
        echo "<h2>4. Headers dans la DB</h2>";
        $headers = $db->query("SELECT id, name, is_default, logo_text, logo_url FROM headers")->fetchAll(PDO::FETCH_ASSOC);
        if ($headers) {
            echo "<pre>";
            foreach ($headers as $h) {
                echo "ID: {$h['id']} | Nom: {$h['name']} | Default: {$h['is_default']} | Logo: " . ($h['logo_url'] ?: $h['logo_text'] ?: 'AUCUN') . "\n";
            }
            echo "</pre>";
        } else {
            echo "<p class='err'>❌ Aucun header trouvé</p>";
        }
        
        // 5. Footers
        echo "<h2>5. Footers dans la DB</h2>";
        $footers = $db->query("SELECT id, name, is_default FROM footers")->fetchAll(PDO::FETCH_ASSOC);
        if ($footers) {
            echo "<pre>";
            foreach ($footers as $f) {
                echo "ID: {$f['id']} | Nom: {$f['name']} | Default: {$f['is_default']}\n";
            }
            echo "</pre>";
        } else {
            echo "<p class='err'>❌ Aucun footer trouvé</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='err'>❌ Erreur DB: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='err'>❌ Database.php NON trouvé à: $dbPath</p>";
}

// 6. Fichiers front
echo "<h2>6. Fichiers front</h2>";
$files = [
    '/front/page.php',
    '/front/404.php',
    '/front/500.php',
    '/front/index.php',
    '/.htaccess'
];
echo "<pre>";
foreach ($files as $f) {
    $path = __DIR__ . $f;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "✅ $f ($size bytes)\n";
    } else {
        echo "❌ $f - MANQUANT\n";
    }
}
echo "</pre>";

// 7. Test de rendu direct
echo "<h2>7. Test de rendu (page accueil)</h2>";
if (isset($page) && $page) {
    echo "<p>Aperçu du rendu avec le CSS :</p>";
    echo "<div style='border:2px solid #6c5ce7;padding:20px;margin:20px 0;border-radius:8px;'>";
    
    // Injecter le CSS
    if (!empty($page['css_preview'])) {
        echo "<style>" . $page['css_preview'] . "</style>";
    }
    
    // Afficher un extrait du contenu
    echo "<p><em>(Extrait du contenu)</em></p>";
    echo $page['content_preview'] ?? '<em>Contenu vide</em>';
    echo "</div>";
}

// 8. .htaccess
echo "<h2>8. Contenu .htaccess</h2>";
$htaccess = __DIR__ . '/.htaccess';
if (file_exists($htaccess)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($htaccess)) . "</pre>";
} else {
    echo "<p class='err'>❌ .htaccess non trouvé</p>";
}

echo "<hr><p><strong>Fin du diagnostic</strong> - Supprimer ce fichier après debug!</p>";