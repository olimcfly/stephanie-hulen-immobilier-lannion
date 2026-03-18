<?php
/**
 * API: Liste des contenus pour le select dynamique menus
 * Chemin: /admin/modules/builder/menus/api-pages.php
 * 
 * Appelé en AJAX — doit charger config + session + DB lui-même
 */

// ── Init : config + session + DB ──────────────────────────
require_once dirname(__DIR__, 3) . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

// Auth
if (empty($_SESSION['admin_id']) && empty($_SESSION['admin_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

// DB
try {
    $pdo = getDB();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur DB: ' . $e->getMessage()]);
    exit;
}

// ── Nettoyage output buffer (config.php fait ob_start) ────
if (ob_get_level()) ob_clean();

$results = [];

// ── Pages spéciales (toujours disponibles) ────────────────
$results[] = ['type'=>'special', 'label'=>'Accueil',                       'url'=>'/',                           'group'=>'Pages spéciales'];
$results[] = ['type'=>'special', 'label'=>'Blog',                          'url'=>'/blog',                       'group'=>'Pages spéciales'];
$results[] = ['type'=>'special', 'label'=>'Estimation gratuite',           'url'=>'/estimation',                 'group'=>'Pages spéciales'];
$results[] = ['type'=>'special', 'label'=>'Contact',                       'url'=>'/contact',                    'group'=>'Pages spéciales'];
$results[] = ['type'=>'special', 'label'=>'Nos secteurs',                  'url'=>'/secteurs',                   'group'=>'Pages spéciales'];
$results[] = ['type'=>'special', 'label'=>'Mentions légales',              'url'=>'/mentions-legales',            'group'=>'Pages spéciales'];
$results[] = ['type'=>'special', 'label'=>'Politique de confidentialité',  'url'=>'/politique-confidentialite',   'group'=>'Pages spéciales'];

// ── Pages CMS (table pages : title, slug) ─────────────────
try {
    $rows = $pdo->query("SELECT title, slug FROM pages WHERE status = 'published' ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        if (!empty($r['title']) && !empty($r['slug'])) {
            $results[] = ['type'=>'page', 'label'=>$r['title'], 'url'=>'/'.ltrim($r['slug'],'/'), 'group'=>'Pages'];
        }
    }
} catch (Exception $e) {}

// ── Builder Pages (title, slug) ───────────────────────────
try {
    $rows = $pdo->query("SELECT title, slug FROM builder_pages WHERE status = 'published' ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
    $existingUrls = array_column($results, 'url');
    foreach ($rows as $r) {
        if (!empty($r['title']) && !empty($r['slug'])) {
            $url = '/'.ltrim($r['slug'],'/');
            if (!in_array($url, $existingUrls)) {
                $results[] = ['type'=>'page', 'label'=>$r['title'], 'url'=>$url, 'group'=>'Pages'];
            }
        }
    }
} catch (Exception $e) {}

// ── Articles (titre, slug) ────────────────────────────────
try {
    $rows = $pdo->query("SELECT titre, slug FROM articles WHERE status = 'published' ORDER BY titre ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        if (!empty($r['titre']) && !empty($r['slug'])) {
            $results[] = ['type'=>'article', 'label'=>$r['titre'], 'url'=>'/blog/'.ltrim($r['slug'],'/'), 'group'=>'Articles'];
        }
    }
} catch (Exception $e) {}

// ── Secteurs (nom, slug) ──────────────────────────────────
try {
    $rows = $pdo->query("SELECT nom, slug FROM secteurs WHERE status = 'published' ORDER BY nom ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        if (!empty($r['nom']) && !empty($r['slug'])) {
            $results[] = ['type'=>'secteur', 'label'=>$r['nom'], 'url'=>'/secteur/'.ltrim($r['slug'],'/'), 'group'=>'Quartiers'];
        }
    }
} catch (Exception $e) {}

// ── Guides (name, slug) ───────────────────────────────────
try {
    $rows = $pdo->query("SELECT name, slug FROM guides WHERE status = 'active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        if (!empty($r['name']) && !empty($r['slug'])) {
            $results[] = ['type'=>'guide', 'label'=>$r['name'], 'url'=>'/guide/'.ltrim($r['slug'],'/'), 'group'=>'Guides'];
        }
    }
} catch (Exception $e) {}

echo json_encode(['success' => true, 'data' => $results, 'count' => count($results)]);
exit;