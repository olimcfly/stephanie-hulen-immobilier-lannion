<?php
/**
 * DIAGNOSTIC - Table Secteurs
 * /admin/modules/secteurs/diagnostic.php
 * 
 * Vérifie la structure de la table, les colonnes présentes/manquantes,
 * et affiche un résumé des données.
 * 
 * ⚠️ Supprimer ce fichier après vérification !
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    die('Accès refusé. <a href="/admin/login.php">Connexion</a>');
}

define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
require_once ROOT_PATH . '/includes/classes/Database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnostic Secteurs</title>
<style>
body { font-family: 'Inter', sans-serif; background: #f1f5f9; padding: 40px; color: #1e293b; max-width: 900px; margin: 0 auto; }
h1 { font-size: 20px; margin-bottom: 20px; }
h2 { font-size: 16px; margin: 30px 0 10px; color: #3b82f6; }
.card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 16px; border: 1px solid #e2e8f0; }
.ok { color: #059669; } .warn { color: #d97706; } .err { color: #dc2626; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
th, td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
th { background: #f8fafc; font-weight: 600; color: #64748b; font-size: 11px; text-transform: uppercase; }
code { background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
.badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; }
.badge-ok { background: #d1fae5; color: #065f46; }
.badge-miss { background: #fee2e2; color: #dc2626; }
</style></head><body>";

echo "<h1>🔍 Diagnostic Module Secteurs</h1>";

// 1. Vérifier que la table existe
echo "<div class='card'><h2>1. Table `secteurs`</h2>";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'secteurs'");
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        echo "<p class='ok'>✅ Table `secteurs` trouvée</p>";
    } else {
        echo "<p class='err'>❌ Table `secteurs` non trouvée !</p>";
        echo "</div></body></html>";
        exit;
    }
} catch (Exception $e) {
    echo "<p class='err'>❌ Erreur: " . $e->getMessage() . "</p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// 2. Structure de la table
echo "<div class='card'><h2>2. Structure des colonnes</h2>";

$requiredCols = [
    'id', 'nom', 'slug', 'ville', 'type_secteur', 'status',
    'meta_title', 'meta_description', 'meta_keywords', 'canonical_url', 'og_image',
    'hero_image', 'hero_title', 'hero_subtitle', 'hero_cta_text', 'hero_cta_url',
    'content', 'custom_css', 'custom_js', 'description',
    'atouts', 'ambiance', 'prix_moyen', 'transport',
    'header_id', 'footer_id', 'external_css', 'external_js',
    'created_at', 'updated_at'
];

try {
    $stmt = $db->query("DESCRIBE secteurs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existingCols = array_column($columns, 'Field');
    
    echo "<table><thead><tr><th>Colonne requise</th><th>Statut</th><th>Type actuel</th></tr></thead><tbody>";
    
    $missing = [];
    foreach ($requiredCols as $col) {
        $found = in_array($col, $existingCols);
        $type = '—';
        if ($found) {
            foreach ($columns as $c) {
                if ($c['Field'] === $col) { $type = $c['Type']; break; }
            }
        } else {
            $missing[] = $col;
        }
        
        echo "<tr>";
        echo "<td><code>$col</code></td>";
        echo "<td>" . ($found ? "<span class='badge badge-ok'>✅ OK</span>" : "<span class='badge badge-miss'>❌ Manquante</span>") . "</td>";
        echo "<td>$type</td>";
        echo "</tr>";
    }
    
    echo "</tbody></table>";
    
    // Colonnes supplémentaires dans la table
    $extra = array_diff($existingCols, $requiredCols);
    if (!empty($extra)) {
        echo "<p style='margin-top: 12px; font-size: 12px; color: #64748b;'>📝 Colonnes supplémentaires dans la table : <code>" . implode('</code>, <code>', $extra) . "</code></p>";
    }
    
    if (!empty($missing)) {
        echo "<p style='margin-top: 12px;' class='warn'>⚠️ " . count($missing) . " colonne(s) manquante(s). Exécutez le script <code>sql/migration-secteurs.sql</code> dans phpMyAdmin.</p>";
    } else {
        echo "<p style='margin-top: 12px;' class='ok'>✅ Toutes les colonnes requises sont présentes !</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='err'>❌ " . $e->getMessage() . "</p>";
}
echo "</div>";

// 3. Données
echo "<div class='card'><h2>3. Données existantes</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as total, SUM(status='published') as pub, SUM(status='draft') as draft FROM secteurs");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total : <strong>" . $stats['total'] . "</strong> secteur(s)</p>";
    echo "<p>Publiés : <strong>" . ($stats['pub'] ?? 0) . "</strong> | Brouillons : <strong>" . ($stats['draft'] ?? 0) . "</strong></p>";
    
    // Aperçu des 5 premiers
    $stmt = $db->query("SELECT id, nom, slug, ville, type_secteur, status FROM secteurs ORDER BY id ASC LIMIT 5");
    $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($sample) {
        echo "<table><thead><tr><th>ID</th><th>Nom</th><th>Slug</th><th>Ville</th><th>Type</th><th>Statut</th></tr></thead><tbody>";
        foreach ($sample as $r) {
            echo "<tr>";
            echo "<td>{$r['id']}</td>";
            echo "<td>{$r['nom']}</td>";
            echo "<td><code>{$r['slug']}</code></td>";
            echo "<td>{$r['ville']}</td>";
            echo "<td>{$r['type_secteur']}</td>";
            echo "<td>{$r['status']}</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
        if ($stats['total'] > 5) echo "<p style='font-size:12px; color:#94a3b8;'>... et " . ($stats['total'] - 5) . " de plus</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='err'>❌ " . $e->getMessage() . "</p>";
}
echo "</div>";

// 4. Test des chemins
echo "<div class='card'><h2>4. Chemins fichiers</h2>";
$paths = [
    '/admin/modules/secteurs/index.php' => ROOT_PATH . '/admin/modules/secteurs/index.php',
    '/admin/modules/secteurs/edit.php' => ROOT_PATH . '/admin/modules/secteurs/edit.php',
    '/admin/modules/secteurs/api/save.php' => ROOT_PATH . '/admin/modules/secteurs/api/save.php',
    '/admin/modules/secteurs/api/bulk.php' => ROOT_PATH . '/admin/modules/secteurs/api/bulk.php',
    '/admin/modules/secteurs/assets/css/secteurs.css' => ROOT_PATH . '/admin/modules/secteurs/assets/css/secteurs.css',
];

foreach ($paths as $rel => $abs) {
    $exists = file_exists($abs);
    echo "<p>" . ($exists ? '✅' : '❌') . " <code>$rel</code></p>";
}
echo "</div>";

echo "<p style='text-align: center; margin-top: 30px; font-size: 12px; color: #94a3b8;'>
<a href='/admin/dashboard.php?page=secteurs'>← Retour aux secteurs</a> | 
Généré le " . date('d/m/Y H:i:s') . "
</p>";
echo "</body></html>";