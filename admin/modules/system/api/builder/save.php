<?php
/**
 * /admin/api/builder/save.php
 * Sauvegarde le contenu HTML/CSS/JS/PHP + meta depuis le Builder Pro
 * Contextes : page, secteur, article, guide, header, footer, capture,
 *             template, menu, design, layout, section
 */

if (!defined('ADMIN_ROUTER')) define('ADMIN_ROUTER', true);

require_once dirname(__DIR__, 3) . '/config/config.php';

header('Content-Type: application/json; charset=utf-8');

// ── Auth ──────────────────────────────────────────────────────────────────────
$isAuth = !empty($_SESSION['admin_logged_in'])
       || !empty($_SESSION['user_id'])
       || !empty($_SESSION['admin_id'])
       || !empty($_SESSION['logged_in'])
       || !empty($_SESSION['is_admin']);

if (!$isAuth) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorise']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Methode non autorisee']);
    exit;
}

// ── Params ────────────────────────────────────────────────────────────────────
$context  = trim($_POST['context']   ?? 'page');
$entityId = (int)($_POST['entity_id'] ?? 0);

if ($entityId <= 0) {
    echo json_encode(['success' => false, 'error' => 'entity_id manquant ou invalide']);
    exit;
}

// ── Map contextes ─────────────────────────────────────────────────────────────
// col_php = null  → pas d'onglet PHP pour ce contexte
// col_slug = null → pas de slug (ex: design, layout)
$CTX = [
    // CONTENT
    'page'     => ['table' => 'pages',          'col_content' => 'content',      'col_css' => 'custom_css', 'col_js' => 'custom_js', 'col_php' => null,           'col_title' => 'title', 'col_slug' => 'slug', 'col_status' => 'status'],
    'secteur'  => ['table' => 'secteurs',       'col_content' => 'content',      'col_css' => 'custom_css', 'col_js' => 'custom_js', 'col_php' => 'php_content',  'col_title' => 'nom',   'col_slug' => 'slug', 'col_status' => 'status'],
    'article'  => ['table' => 'articles',       'col_content' => 'content',      'col_css' => 'custom_css', 'col_js' => 'custom_js', 'col_php' => 'php_content',  'col_title' => 'title', 'col_slug' => 'slug', 'col_status' => 'status'],
    'guide'    => ['table' => 'guide_local',    'col_content' => 'contenu',      'col_css' => 'custom_css', 'col_js' => 'custom_js', 'col_php' => 'php_content',  'col_title' => 'titre', 'col_slug' => 'slug', 'col_status' => 'statut'],
    'capture'  => ['table' => 'capture_pages',  'col_content' => 'content',      'col_css' => 'custom_css', 'col_js' => 'custom_js', 'col_php' => 'php_content',  'col_title' => 'name',  'col_slug' => 'slug', 'col_status' => 'status'],

    // BUILDER
    'header'   => ['table' => 'headers',        'col_content' => 'custom_html',  'col_css' => 'custom_css', 'col_js' => 'custom_js', 'col_php' => null,           'col_title' => 'name',  'col_slug' => 'name', 'col_status' => 'status'],
    'footer'   => ['table' => 'footers',        'col_content' => 'custom_html',  'col_css' => 'custom_css', 'col_js' => 'custom_js', 'col_php' => null,           'col_title' => 'name',  'col_slug' => 'name', 'col_status' => 'status'],
    'template' => ['table' => 'templates',      'col_content' => 'html_content', 'col_css' => 'custom_css', 'col_js' => 'custom_js', 'col_php' => null,           'col_title' => 'name',  'col_slug' => 'slug', 'col_status' => 'status'],
    'menu'     => ['table' => 'menus',          'col_content' => 'items',        'col_css' => 'custom_css', 'col_js' => 'custom_js', 'col_php' => null,           'col_title' => 'name',  'col_slug' => 'slug', 'col_status' => 'status'],
    'section'  => ['table' => 'sections',       'col_content' => 'html_content', 'col_css' => 'custom_css', 'col_js' => 'custom_js', 'col_php' => null,           'col_title' => 'name',  'col_slug' => 'slug', 'col_status' => 'status'],
    'layout'   => ['table' => 'layouts',        'col_content' => 'html_content', 'col_css' => 'custom_css', 'col_js' => 'custom_js', 'col_php' => null,           'col_title' => 'name',  'col_slug' => null,   'col_status' => 'status'],
    'design'   => ['table' => 'design_settings','col_content' => 'custom_css',   'col_css' => 'custom_css', 'col_js' => 'custom_js', 'col_php' => null,           'col_title' => 'name',  'col_slug' => null,   'col_status' => 'status'],
    'media'    => ['table' => 'media',          'col_content' => null,           'col_css' => null,         'col_js' => null,        'col_php' => null,           'col_title' => 'name',  'col_slug' => 'slug', 'col_status' => 'status'],
];

if (!isset($CTX[$context])) {
    echo json_encode(['success' => false, 'error' => "Contexte invalide: $context. Contextes valides: " . implode(', ', array_keys($CTX))]);
    exit;
}

$C = $CTX[$context];

// ── Valeurs POST ──────────────────────────────────────────────────────────────
$html      = $_POST['html_content']       ?? '';
$css       = $_POST['custom_css']         ?? '';
$js        = $_POST['custom_js']          ?? '';
$php       = $_POST['php_content']        ?? '';
$title     = trim($_POST['title']         ?? '');
$slug      = trim($_POST['slug']          ?? '');
$status    = trim($_POST['status']        ?? 'draft');
$metaTitle = trim($_POST['meta_title']    ?? '');
$metaDesc  = trim($_POST['meta_description'] ?? '');
$metaKw    = trim($_POST['meta_keywords'] ?? '');

// Validation statut
$validStatuses = ['published', 'draft', 'brouillon', 'publie', 'active', 'inactive', 'archived'];
if (!in_array($status, $validStatuses)) {
    $status = 'draft';
}

// Slug auto si vide
if ($slug === '' && $title !== '' && $C['col_slug']) {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', $title)), '-'));
}

// ── Contexte media = pas de contenu à sauver ──────────────────────────────────
if ($context === 'media') {
    echo json_encode(['success' => false, 'error' => 'Le contexte media ne supporte pas la sauvegarde de contenu HTML']);
    exit;
}

// ── DB ────────────────────────────────────────────────────────────────────────
try {
    $pdo = getDB();

    // Vérifier existence table
    $tableCheck = $pdo->query("SHOW TABLES LIKE '{$C['table']}'")->fetchColumn();
    if (!$tableCheck) {
        echo json_encode(['success' => false, 'error' => "Table `{$C['table']}` introuvable pour contexte '$context'. A créer ou contexte incorrect."]);
        exit;
    }

    // Vérifier entité
    $check = $pdo->prepare("SELECT id FROM `{$C['table']}` WHERE id = ? LIMIT 1");
    $check->execute([$entityId]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => "Entite #$entityId introuvable dans `{$C['table']}`"]);
        exit;
    }

    // ── Construction SET dynamique ────────────────────────────────────────────
    $sets   = [];
    $params = [];

    // Colonne contenu principale
    if ($C['col_content']) {
        // Pour 'design', col_content = custom_css (même colonne) — éviter doublon
        if ($context === 'design') {
            $sets[] = "`{$C['col_content']}` = ?"; $params[] = $css ?: $html;
        } else {
            $sets[] = "`{$C['col_content']}` = ?"; $params[] = $html;
        }
    }

    // CSS
    if ($C['col_css'] && $C['col_css'] !== $C['col_content']) {
        $sets[] = "`{$C['col_css']}` = ?"; $params[] = $css;
    }

    // JS
    if ($C['col_js']) {
        $sets[] = "`{$C['col_js']}` = ?"; $params[] = $js;
    }

    // PHP
    if ($C['col_php']) {
        $sets[] = "`{$C['col_php']}` = ?"; $params[] = $php;
    }

    // Titre
    if ($title !== '' && $C['col_title']) {
        $sets[] = "`{$C['col_title']}` = ?"; $params[] = $title;
    }

    // Slug (seulement si différent du titre-colonne)
    if ($slug !== '' && $C['col_slug'] && $C['col_slug'] !== $C['col_title']) {
        $sets[] = "`{$C['col_slug']}` = ?"; $params[] = $slug;
    }

    // Statut
    if ($C['col_status']) {
        $sets[] = "`{$C['col_status']}` = ?"; $params[] = $status;
    }

    // Meta SEO (best-effort — ignore si colonne absente)
    $metaCols = ['meta_title' => $metaTitle, 'meta_description' => $metaDesc, 'meta_keywords' => $metaKw];
    foreach ($metaCols as $col => $val) {
        try {
            $t = $pdo->prepare("SELECT `$col` FROM `{$C['table']}` LIMIT 1");
            $t->execute();
            $sets[]   = "`$col` = ?";
            $params[] = $val;
        } catch (Exception $e) { /* colonne absente — silencieux */ }
    }

    // updated_at (best-effort)
    try {
        $pdo->prepare("SELECT `updated_at` FROM `{$C['table']}` LIMIT 1")->execute();
        $sets[] = "`updated_at` = NOW()";
    } catch (Exception $e) {}

    if (empty($sets)) {
        echo json_encode(['success' => false, 'error' => 'Aucun champ à sauvegarder']);
        exit;
    }

    $params[] = $entityId;
    $sql  = "UPDATE `{$C['table']}` SET " . implode(', ', $sets) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode([
        'success'   => true,
        'message'   => $status === 'published' ? 'Publie avec succes' : 'Sauvegarde avec succes',
        'rows'      => $stmt->rowCount(),
        'context'   => $context,
        'table'     => $C['table'],
        'entity_id' => $entityId,
        'status'    => $status,
    ]);

} catch (Exception $e) {
    error_log('[Builder Save] ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage(),
    ]);
}