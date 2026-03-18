<?php
/**
 * /front/router.php
 * v2.1 — FIX: findPage() compatible sans colonne website_id
 *
 * Dispatch vers templates autonomes /front/templates/pages/tN-xxx.php
 *
 * Variables injectées dans chaque template :
 *   $website, $page, $fields, $advisor, $site, $editMode, $pdo,
 *   $headerData, $footerData
 */

ini_set('display_errors', 0);
error_reporting(E_ALL);

// ── Config centrale ──────────────────────────────────────
$configPath = __DIR__ . '/../config/config.php';
if (!file_exists($configPath)) $configPath = $_SERVER['DOCUMENT_ROOT'] . '/config/config.php';
if (!file_exists($configPath)) { http_response_code(500); die('Configuration manquante'); }
require_once $configPath;
require_once __DIR__ . '/../includes/init.php';

// ── Helpers front ────────────────────────────────────────
foreach ([
    __DIR__ . '/helpers/site-config.php',
    __DIR__ . '/helpers/palette.php',
    __DIR__ . '/helpers/layout.php',
    __DIR__ . '/helpers/utils.php',
    __DIR__ . '/includes/functions/menu-functions.php',
] as $h) { if (file_exists($h)) require_once $h; }

// ── Connexion BDD ────────────────────────────────────────
try {
    $pdo = getDB();
} catch (Exception $e) {
    http_response_code(500);
    die('Erreur BDD');
}

// ════════════════════════════════════════════════════════
// 1. DÉTECTION DU DOMAINE
// ════════════════════════════════════════════════════════

$currentHost   = strtolower($_SERVER['HTTP_HOST']);
$currentDomain = preg_replace('/^www\./', '', $currentHost);

if (!defined('MAIN_DOMAIN'))    define('MAIN_DOMAIN',    'ecosysteme-immo.fr');
if (!defined('ADMIN_SUBDOMAIN'))define('ADMIN_SUBDOMAIN','admin');

$website      = null;
$isMainDomain = false;

// Redirection admin
if ($currentDomain === ADMIN_SUBDOMAIN . '.' . MAIN_DOMAIN) {
    header('Location: /admin/'); exit;
}

// Sous-domaine client (slug.ecosysteme-immo.fr)
if (preg_match('/^([a-z0-9-]+)\.' . preg_quote(MAIN_DOMAIN, '/') . '$/i', $currentDomain, $m)) {
    $slug = $m[1];
    $reserved = ['www','admin','api','mail','ftp','cpanel','webmail'];
    if (!in_array($slug, $reserved)) {
        try {
            $st = $pdo->prepare("SELECT * FROM websites WHERE slug=? AND status='published' LIMIT 1");
            $st->execute([$slug]); $website = $st->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
    }
}

// Domaine personnalisé
if (!$website) {
    try {
        $st = $pdo->prepare("SELECT * FROM websites WHERE (domain=? OR domain=? OR domain=?) AND status='published' LIMIT 1");
        $st->execute([$currentDomain, 'www.'.$currentDomain, str_replace('www.','',$currentDomain)]);
        $website = $st->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Domaine principal plateforme
if (!$website && in_array($currentDomain, [MAIN_DOMAIN, 'www.'.MAIN_DOMAIN])) {
    $isMainDomain = true;
    try {
        $st = $pdo->prepare("SELECT * FROM websites WHERE slug IN ('main','accueil') LIMIT 1");
        $st->execute(); $website = $st->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Site introuvable → créer un website virtuel pour les sites mono-conseiller
if (!$website) {
    // Sur un site mono-conseiller (domaine personnalisé sans table websites),
    // on crée un objet $website minimal pour que le router fonctionne
    $website = [
        'id' => 0,
        'slug' => 'main',
        'domain' => $currentDomain,
        'status' => 'published',
        'name' => defined('SITE_TITLE') ? SITE_TITLE : 'Mon Site',
    ];
}

// ── Session ──────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['current_website']    = $website;
$_SESSION['current_website_id'] = $website['id'] ?? null;

// ════════════════════════════════════════════════════════
// 2. PARSE URI
// ════════════════════════════════════════════════════════

if (!empty($_GET['_uri'])) {
    $rawUri = trim($_GET['_uri'], '/');
} else {
    $rawUri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $rawUri = preg_replace('/\.php$/', '', $rawUri);
}
$uri   = preg_replace('/[^a-z0-9\-_\/]/i', '', $rawUri);
$parts = array_values(array_filter(explode('/', $uri)));

// ════════════════════════════════════════════════════════
// 3. HELPER FUNCTIONS
// ════════════════════════════════════════════════════════

/**
 * Charge un template en injectant les variables standard.
 */
function dispatchTemplate(
    string $tplFile,
    PDO    $pdo,
    array  $website,
    ?array $page    = null,
    array  $extra   = []
): void {
    if (!file_exists($tplFile)) {
        http_response_code(500);
        die('Template introuvable : ' . basename($tplFile));
    }

    // Champs éditables
    $fields = [];
    foreach (['fields_json', 'fields'] as $col) {
        if ($page && !empty($page[$col])) {
            $dec = json_decode($page[$col], true);
            if (is_array($dec)) { $fields = $dec; break; }
        }
    }

    $editMode = !empty($_GET['edit_mode']) && !empty($_GET['edit_token']);
    $advisor  = buildAdvisorArray($pdo, $website['id'] ?? 0);
    $site     = $website;

    // Header / Footer depuis DB
    $headerData = null;
    $footerData = null;
    foreach (['headers','site_headers'] as $tbl) {
        try {
            $h = $pdo->query("SELECT * FROM `$tbl` WHERE status='active' ORDER BY is_default DESC, id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($h) { $headerData = $h; break; }
        } catch (Exception $e) {}
    }
    foreach (['footers','site_footers'] as $tbl) {
        try {
            $f = $pdo->query("SELECT * FROM `$tbl` WHERE status='active' ORDER BY is_default DESC, id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($f) { $footerData = $f; break; }
        } catch (Exception $e) {}
    }

    extract($extra, EXTR_OVERWRITE);
    include $tplFile;
}

/**
 * Construit le tableau $advisor depuis advisor_context et settings.
 */
function buildAdvisorArray(PDO $pdo, int $websiteId): array {
    $advisor = [];
    $keys = ['advisor_name','advisor_phone','advisor_email','advisor_city','advisor_address','advisor_card'];
    try {
        $in = implode(',', array_fill(0, count($keys), '?'));
        $st = $pdo->prepare("SELECT field_key, field_value FROM advisor_context WHERE field_key IN ($in) LIMIT ".count($keys));
        $st->execute($keys);
        while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $k = preg_replace('/^advisor_/', '', $row['field_key']);
            $advisor[$k] = $row['field_value'];
        }
    } catch (Exception $e) {}

    if (empty($advisor['name'])) {
        try {
            $st = $pdo->prepare("SELECT key_name, value FROM settings WHERE key_name IN ('site_name','phone','email_support','address') LIMIT 4");
            $st->execute();
            $map = ['site_name'=>'name','phone'=>'phone','email_support'=>'email','address'=>'address'];
            while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                $k = $map[$row['key_name']] ?? $row['key_name'];
                if (empty($advisor[$k])) $advisor[$k] = $row['value'];
            }
        } catch (Exception $e) {}
    }
    return $advisor;
}

/**
 * Cherche une page par slug — compatible avec et sans website_id
 */
function findPage(PDO $pdo, int $websiteId, string $slug): ?array {
    // 1. Essayer avec website_id
    try {
        $st = $pdo->prepare("SELECT * FROM pages WHERE website_id=? AND slug=? AND status='published' LIMIT 1");
        $st->execute([$websiteId, $slug]);
        $p = $st->fetch(PDO::FETCH_ASSOC);
        if ($p) return $p;
    } catch (Exception $e) {
        // Colonne website_id inexistante → passer au fallback
    }

    // 2. Fallback sans website_id (site mono-conseiller)
    try {
        $st = $pdo->prepare("SELECT * FROM pages WHERE slug=? AND status='published' LIMIT 1");
        $st->execute([$slug]);
        $p = $st->fetch(PDO::FETCH_ASSOC);
        return $p ?: null;
    } catch (Exception $e) { return null; }
}

/** Helper require ou echo fallback */
function require_or_die(string $path, string $fallback): void {
    if (file_exists($path)) require $path;
    else echo $fallback;
}

// ════════════════════════════════════════════════════════
// 4. MAPPING layout → fichier template
// ════════════════════════════════════════════════════════

const TEMPLATE_MAP = [
    't1-accueil'          => 'pages/t1-accueil.php',
    't2-edito'            => 'pages/t2-edito.php',
    't3-secteur'          => 'pages/t3-secteur.php',
    't4-blog-hub'         => 'pages/t4-blog-hub.php',
    't5-article'          => 'pages/t5-article.php',
    't6-guide'            => 'pages/t6-guide.php',
    't7-estimation'       => 'pages/t7-estimation.php',
    't8-contact'          => 'pages/t8-contact.php',
    't9-honoraires'       => 'pages/t9-honoraires.php',
    't10-biens-listing'   => 'pages/t10-biens-listing.php',
    't11-bien-single'     => 'pages/t11-bien-single.php',
    't12-legal'           => 'pages/t12-legal.php',
    't13-merci'           => 'pages/t13-merci.php',
    't14-apropos'         => 'pages/t14-apropos.php',
    't15-secteurs-listing'=> 'pages/t15-secteurs-listing.php',
    't16-rapport-marche'  => 'pages/t16-rapport-marche.php',
    't5-capture-guide'    => 'captures/t5-capture-guide.php',
    't6-capture-merci'    => 'captures/t6-capture-merci.php',
    'accueil'             => 'pages/t1-accueil.php',
    'edito'               => 'pages/t2-edito.php',
    'secteur'             => 'pages/t3-secteur.php',
    'blog'                => 'pages/t4-blog-hub.php',
    'blog-hub'            => 'pages/t4-blog-hub.php',
    'article'             => 'pages/t5-article.php',
    'guide'               => 'pages/t6-guide.php',
    'estimation'          => 'pages/t7-estimation.php',
    'contact'             => 'pages/t8-contact.php',
    'honoraires'          => 'pages/t9-honoraires.php',
    'biens-listing'       => 'pages/t10-biens-listing.php',
    'biens'               => 'pages/t10-biens-listing.php',
    'bien-single'         => 'pages/t11-bien-single.php',
    'legal'               => 'pages/t12-legal.php',
    'merci'               => 'pages/t13-merci.php',
    'apropos'             => 'pages/t14-apropos.php',
    'secteurs-listing'    => 'pages/t15-secteurs-listing.php',
    'secteurs'            => 'pages/t15-secteurs-listing.php',
    'rapport-marche'      => 'pages/t16-rapport-marche.php',
    'capture-guide'       => 'captures/t5-capture-guide.php',
    'capture-merci'       => 'captures/t6-capture-merci.php',
    'standard'            => 'pages/t2-edito.php',
];

function resolveTemplate(string $layout): ?string {
    $base = __DIR__ . '/templates/';
    $map  = TEMPLATE_MAP;

    if (isset($map[$layout])) return $base . $map[$layout];
    if (file_exists($base . $layout . '.php')) return $base . $layout . '.php';

    return null;
}

// ════════════════════════════════════════════════════════
// 5. DISPATCH DES ROUTES SPÉCIALES
// ════════════════════════════════════════════════════════

$websiteId = (int)($website['id'] ?? 0);
$tplDir    = __DIR__ . '/templates/';

/* ── /capture/{guide-slug} ──────────────────────────── */
if (!empty($parts[0]) && $parts[0] === 'capture') {
    $guideSlug = $parts[1] ?? '';
    $capPage = findPage($pdo, $websiteId, 'capture/' . $guideSlug)
            ?: findPage($pdo, $websiteId, 'capture');
    dispatchTemplate(
        $tplDir . 'captures/t5-capture-guide.php',
        $pdo, $website, $capPage,
        ['guide_slug_url' => $guideSlug]
    );
    exit;
}

/* ── /merci ─────────────────────────────────────────── */
if ($uri === 'merci') {
    $merciPage = findPage($pdo, $websiteId, 'merci');
    dispatchTemplate($tplDir . 'captures/t6-capture-merci.php', $pdo, $website, $merciPage);
    exit;
}

/* ── /blog/{article-slug} ───────────────────────────── */
if (!empty($parts[0]) && $parts[0] === 'blog') {
    if (isset($parts[1])) {
        $articleSlug = $parts[1];
        $artPage = findPage($pdo, $websiteId, 'blog/' . $articleSlug)
                ?: findPage($pdo, $websiteId, $articleSlug);
        if ($artPage) {
            $tpl = resolveTemplate($artPage['layout'] ?? 'article')
                ?: $tplDir . 'pages/t5-article.php';
            dispatchTemplate($tpl, $pdo, $website, $artPage, ['article_slug' => $articleSlug]);
        } else {
            dispatchTemplate($tplDir . 'pages/t5-article.php', $pdo, $website, null, ['article_slug' => $articleSlug]);
        }
        exit;
    }
    // /blog sans slug → listing
    $blogPage = findPage($pdo, $websiteId, 'blog');
    $tpl = $blogPage ? (resolveTemplate($blogPage['layout'] ?? 'blog-hub') ?: $tplDir . 'pages/t4-blog-hub.php')
                     : $tplDir . 'pages/t4-blog-hub.php';
    dispatchTemplate($tpl, $pdo, $website, $blogPage);
    exit;
}

/* ── /biens/{bien-slug} ─────────────────────────────── */
if (!empty($parts[0]) && ($parts[0] === 'biens' || $parts[0] === 'biens-immobiliers')) {
    if (isset($parts[1])) {
        $bienSlug = $parts[1];
        $bienPage = findPage($pdo, $websiteId, 'biens/' . $bienSlug);
        $tpl = $bienPage ? (resolveTemplate($bienPage['layout'] ?? 'bien-single') ?: $tplDir . 'pages/t11-bien-single.php')
                         : $tplDir . 'pages/t11-bien-single.php';
        dispatchTemplate($tpl, $pdo, $website, $bienPage, ['bien_slug' => $bienSlug]);
        exit;
    }
    // /biens sans slug → listing
    $biensPage = findPage($pdo, $websiteId, 'biens-immobiliers')
              ?: findPage($pdo, $websiteId, 'biens');
    $tpl = $biensPage ? (resolveTemplate($biensPage['layout'] ?? 'biens-listing') ?: $tplDir . 'pages/t10-biens-listing.php')
                      : $tplDir . 'pages/t10-biens-listing.php';
    dispatchTemplate($tpl, $pdo, $website, $biensPage);
    exit;
}

/* ── /secteurs/{secteur-slug} ───────────────────────── */
if (!empty($parts[0]) && $parts[0] === 'secteurs') {
    if (isset($parts[1])) {
        $secteurSlug = $parts[1];
        $secteurPage = findPage($pdo, $websiteId, 'secteurs/' . $secteurSlug)
                    ?: findPage($pdo, $websiteId, $secteurSlug);
        $tpl = $secteurPage ? (resolveTemplate($secteurPage['layout'] ?? 'secteur') ?: $tplDir . 'pages/t3-secteur.php')
                            : $tplDir . 'pages/t3-secteur.php';
        dispatchTemplate($tpl, $pdo, $website, $secteurPage, ['secteur_slug' => $secteurSlug]);
        exit;
    }
    // /secteurs sans slug → listing
    $secteursPage = findPage($pdo, $websiteId, 'secteurs');
    $tpl = $secteursPage ? (resolveTemplate($secteursPage['layout'] ?? 'secteurs-listing') ?: $tplDir . 'pages/t15-secteurs-listing.php')
                         : $tplDir . 'pages/t15-secteurs-listing.php';
    dispatchTemplate($tpl, $pdo, $website, $secteursPage);
    exit;
}

/* ── /guide-local/{slug} ────────────────────────────── */
if (!empty($parts[0]) && $parts[0] === 'guide-local') {
    if (isset($parts[1])) {
        $guidePage = findPage($pdo, $websiteId, 'guide-local/' . $parts[1])
                  ?: findPage($pdo, $websiteId, $parts[1]);
        $tpl = $guidePage ? (resolveTemplate($guidePage['layout'] ?? 'guide') ?: $tplDir . 'pages/t6-guide.php')
                          : $tplDir . 'pages/t6-guide.php';
        dispatchTemplate($tpl, $pdo, $website, $guidePage, ['guide_slug' => $parts[1]]);
        exit;
    }
}

// ════════════════════════════════════════════════════════
// 6. ROUTE GÉNÉRIQUE → LOOKUP EN DB → DISPATCH TEMPLATE
// ════════════════════════════════════════════════════════

$pageSlug = ($uri === '' || $uri === 'index') ? 'accueil' : $uri;

$page = findPage($pdo, $websiteId, $pageSlug);

/* Fallback : slug = home */
if (!$page && $pageSlug === 'accueil') {
    $page = findPage($pdo, $websiteId, 'home')
         ?: findPage($pdo, $websiteId, '');
    if (!$page) {
        // Première page publiée du site
        try {
            $st = $pdo->prepare("SELECT * FROM pages WHERE website_id=? AND status='published' ORDER BY created_at ASC LIMIT 1");
            $st->execute([$websiteId]);
            $page = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            // Fallback sans website_id
            try {
                $st = $pdo->prepare("SELECT * FROM pages WHERE status='published' ORDER BY created_at ASC LIMIT 1");
                $st->execute();
                $page = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            } catch (Exception $e2) {}
        }
    }
}

/* Page introuvable → 404 */
if (!$page) {
    http_response_code(404);
    $notFound = findPage($pdo, $websiteId, '404');
    if ($notFound) {
        $tpl = resolveTemplate($notFound['layout'] ?? 'edito') ?: $tplDir . 'pages/t2-edito.php';
        dispatchTemplate($tpl, $pdo, $website, $notFound);
    } else {
        require_or_die(__DIR__ . '/renderers/404.php', '<h1>404 – Page non trouvée</h1>');
    }
    exit;
}

/* Résolution du template depuis $page['layout'] */
$layout  = trim($page['layout'] ?? $page['template'] ?? $page['type'] ?? '');
$tplFile = $layout ? resolveTemplate($layout) : null;

/* Fallback ultime : t2-edito */
if (!$tplFile || !file_exists($tplFile)) {
    $tplFile = $tplDir . 'pages/t2-edito.php';
}

dispatchTemplate($tplFile, $pdo, $website, $page);