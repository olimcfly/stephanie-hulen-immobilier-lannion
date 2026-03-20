<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE PAGES — API unifiée  v1.0
 *  /admin/modules/content/pages/api.php
 *
 *  Point d'entrée AJAX unique pour toutes les opérations
 *  sur les pages. Centralise les actions auparavant dispersées
 *  entre admin/api/content/pages.php et admin/core/handlers/pages.php.
 *
 *  Actions supportées :
 *    save           — Créer ou mettre à jour une page
 *    delete         — Supprimer une page
 *    get            — Récupérer une page par ID
 *    list           — Liste avec pagination + filtres
 *    toggle_status  — Publier / dépublier / archiver
 *    duplicate      — Dupliquer une page
 *    check_slug     — Vérifier unicité du slug
 *    ai_generate    — Génération IA (create_with_ai)
 *    bulk_delete    — Suppression en masse
 *    reorder        — Réordonner les pages
 *    autosave       — Sauvegarde automatique
 *    ai_slug        — Générer un slug depuis un titre
 *
 *  Input  : POST (FormData ou JSON)  |  GET pour lecture
 *  Output : JSON { success, ... }
 * ══════════════════════════════════════════════════════════════
 */

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    _pagesApiRespond(false, 'Non authentifié', 401);
}

// ─── DB ───────────────────────────────────────────────────────
if (!isset($pdo) && !isset($db)) {
    $initFile = __DIR__ . '/../../../includes/init.php';
    if (file_exists($initFile)) {
        require_once $initFile;
        try { $pdo = getDB(); } catch (Exception $e) {
            _pagesApiRespond(false, 'Erreur BD', 500);
        }
    }
}
if (isset($db)  && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db))  $db  = $pdo;

// ─── Table detection ──────────────────────────────────────────
$tableName = 'pages';
$tableFound = false;
foreach (['pages', 'cms_pages'] as $candidate) {
    try {
        $pdo->query("SELECT 1 FROM `{$candidate}` LIMIT 1");
        $tableName = $candidate;
        $tableFound = true;
        break;
    } catch (PDOException $e) { continue; }
}
if (!$tableFound) {
    _pagesApiRespond(false, 'Table pages introuvable', 500);
}

// ─── Available columns ───────────────────────────────────────
$existingCols = [];
try {
    $existingCols = array_map('strtolower', $pdo->query("SHOW COLUMNS FROM `{$tableName}`")->fetchAll(PDO::FETCH_COLUMN));
} catch (PDOException $e) {}

// ─── Input ────────────────────────────────────────────────────
$input = $_POST;
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (str_contains($contentType, 'application/json') || empty($_POST['action']))) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $input = array_merge($input, $json);
    }
}

$action = $input['action'] ?? $_GET['action'] ?? '';
if (!$action) {
    _pagesApiRespond(false, 'Action manquante', 400);
}

// ─── Helpers ──────────────────────────────────────────────────

function _pagesSlug($title) {
    $slug = mb_strtolower($title, 'UTF-8');
    $slug = preg_replace('/[àáâãäå]/u', 'a', $slug);
    $slug = preg_replace('/[èéêë]/u', 'e', $slug);
    $slug = preg_replace('/[ìíîï]/u', 'i', $slug);
    $slug = preg_replace('/[òóôõö]/u', 'o', $slug);
    $slug = preg_replace('/[ùúûü]/u', 'u', $slug);
    $slug = preg_replace('/[ýÿ]/u', 'y', $slug);
    $slug = preg_replace('/[ç]/u', 'c', $slug);
    $slug = preg_replace('/[œ]/u', 'oe', $slug);
    $slug = preg_replace('/[æ]/u', 'ae', $slug);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', trim($slug));
    return substr($slug, 0, 80);
}

function _pagesUniqueSlug($pdo, $table, $slug, $excludeId = null) {
    $base = $slug;
    $n = 1;
    while (true) {
        $sql = "SELECT id FROM `{$table}` WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) return $slug;
        $slug = $base . '-' . $n++;
        if ($n > 50) return $base . '-' . bin2hex(random_bytes(3));
    }
}

function _pagesWordCount($html) {
    $text = strip_tags($html ?? '');
    $text = preg_replace('/\s+/', ' ', trim($text));
    return $text ? str_word_count($text) : 0;
}

function _pagesSafeUpdate($pdo, $table, $id, $data, $existingCols) {
    $sets = []; $vals = [];
    foreach ($data as $col => $val) {
        if (in_array(strtolower($col), $existingCols) && strtolower($col) !== 'id') {
            $sets[] = "`{$col}` = ?";
            $vals[] = $val;
        }
    }
    if (empty($sets)) return false;
    $vals[] = $id;
    return $pdo->prepare("UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE id = ?")->execute($vals);
}

function _pagesSafeInsert($pdo, $table, $data, $existingCols) {
    $cols = []; $vals = []; $phs = [];
    foreach ($data as $col => $val) {
        if (in_array(strtolower($col), $existingCols)) {
            $cols[] = "`{$col}`";
            $vals[] = $val;
            $phs[] = '?';
        }
    }
    if (empty($cols)) return false;
    $pdo->prepare("INSERT INTO `{$table}` (" . implode(',', $cols) . ") VALUES (" . implode(',', $phs) . ")")
        ->execute($vals);
    return (int)$pdo->lastInsertId();
}

function _pagesParseIds($raw) {
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) return array_filter(array_map('intval', $decoded), fn($id) => $id > 0);
    }
    if (is_array($raw)) return array_filter(array_map('intval', $raw), fn($id) => $id > 0);
    return [];
}

function _pagesCallClaude($prompt, $maxTokens = 2000) {
    try {
        $stmt = (getDB())->query("SELECT api_key FROM ai_providers WHERE name='anthropic' AND active=1 LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $apiKey = $row['api_key'] ?? '';
        if (!$apiKey) return ['success' => false, 'text' => ''];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => $maxTokens,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($resp, true);
        $text = $data['content'][0]['text'] ?? '';
        return ['success' => !empty($text), 'text' => $text];
    } catch (Throwable $e) {
        return ['success' => false, 'text' => ''];
    }
}

function _pagesGetAdvisor() {
    try {
        $stmt = (getDB())->query("SELECT * FROM advisor_context LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

// ════════════════════════════════════════════════════════════
// DISPATCH
// ════════════════════════════════════════════════════════════

try {

switch ($action) {

    // ─── SAVE (Create or Update — unifié) ─────────────────
    case 'save':
    case 'save_page':
        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $title = trim(strip_tags($input['title'] ?? ''));
        if (!$title) _pagesApiRespond(false, 'Titre obligatoire', 400);

        $slug = $input['slug'] ?? '';
        $slug = $slug ? _pagesSlug($slug) : _pagesSlug($title);
        $slug = _pagesUniqueSlug($pdo, $tableName, $slug, $id);

        $template = trim($input['template'] ?? 'standard');
        $status   = in_array($input['status'] ?? '', ['draft','published','archived']) ? $input['status'] : 'draft';
        $metaTitle = trim(strip_tags($input['meta_title'] ?? ''));
        $metaDesc  = trim(strip_tags($input['meta_description'] ?? ''));

        $content = $input['content'] ?? $input['html_content'] ?? '';
        $sectionsJson = $input['sections_json'] ?? '';

        // Template ID
        $templateId = null;
        try {
            $tplStmt = $pdo->prepare("SELECT id FROM design_templates WHERE name = ? OR slug = ? LIMIT 1");
            $tplStmt->execute([$template, $template]);
            $tplRow = $tplStmt->fetch(PDO::FETCH_ASSOC);
            $templateId = $tplRow['id'] ?? null;
        } catch (PDOException $e) {}

        // Fields / sections merging
        $existingFields = [];
        if ($id) {
            try {
                $ef = $pdo->prepare("SELECT fields FROM `{$tableName}` WHERE id = ?");
                $ef->execute([$id]);
                $row = $ef->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['fields'])) {
                    $existingFields = json_decode($row['fields'], true) ?: [];
                }
            } catch (PDOException $e) {}
        }

        $newSections = [];
        if (!empty($sectionsJson)) {
            $newSections = is_string($sectionsJson) ? (json_decode($sectionsJson, true) ?: []) : $sectionsJson;
        }
        $mergedFields = !empty($newSections) ? array_merge($existingFields, $newSections) : $existingFields;
        $fieldsJSON = json_encode($mergedFields, JSON_UNESCAPED_UNICODE);

        $sectionsJsonStr = is_string($sectionsJson) ? $sectionsJson : json_encode($sectionsJson, JSON_UNESCAPED_UNICODE);

        $data = [
            'title'        => $title,
            'slug'         => $slug,
            'template'     => $template,
            'layout'       => $template,
            'status'       => $status,
            'fields'       => $fieldsJSON,
            'sections_json'=> $sectionsJsonStr ?: $fieldsJSON,
            'template_id'  => $templateId,
            'meta_title'   => $metaTitle,
            'meta_description' => $metaDesc,
            'word_count'   => _pagesWordCount($content ?: strip_tags($fieldsJSON)),
        ];

        if (!empty($content)) {
            $data['content'] = $content;
            $data['html_content'] = $content;
        }

        if ($id) {
            // Vérifier existence
            $check = $pdo->prepare("SELECT id, status FROM `{$tableName}` WHERE id = ?");
            $check->execute([$id]);
            $existing = $check->fetch(PDO::FETCH_ASSOC);
            if (!$existing) _pagesApiRespond(false, 'Page introuvable', 404);

            if ($status === 'published' && ($existing['status'] ?? '') !== 'published') {
                $data['published_at'] = date('Y-m-d H:i:s');
            }

            _pagesSafeUpdate($pdo, $tableName, $id, $data, $existingCols);
            _pagesApiRespond(true, 'Page mise à jour', 200, ['id' => $id, 'slug' => $slug]);
        } else {
            if ($status === 'published') {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
            $newId = _pagesSafeInsert($pdo, $tableName, $data, $existingCols);
            _pagesApiRespond(true, 'Page créée', 201, ['id' => $newId, 'page_id' => $newId, 'slug' => $slug]);
        }
        break;

    // ─── DELETE ───────────────────────────────────────────
    case 'delete':
        $id = (int)($input['id'] ?? $input['page_id'] ?? 0);
        if ($id <= 0) _pagesApiRespond(false, 'ID invalide', 400);

        $check = $pdo->prepare("SELECT id, title FROM `{$tableName}` WHERE id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) _pagesApiRespond(false, 'Page introuvable', 404);

        $pdo->prepare("DELETE FROM `{$tableName}` WHERE id = ?")->execute([$id]);

        try {
            $pdo->prepare("DELETE FROM seo_scores WHERE context = 'landing' AND entity_id = ?")->execute([$id]);
        } catch (PDOException $e) {}

        _pagesApiRespond(true, 'Page supprimée', 200, ['deleted_id' => $id]);
        break;

    // ─── GET ──────────────────────────────────────────────
    case 'get':
        $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
        if ($id <= 0) _pagesApiRespond(false, 'ID invalide', 400);

        $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE id = ?");
        $stmt->execute([$id]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$page) _pagesApiRespond(false, 'Page introuvable', 404);

        _pagesApiRespond(true, 'OK', 200, ['page' => $page]);
        break;

    // ─── LIST ─────────────────────────────────────────────
    case 'list':
        $status  = $_GET['status'] ?? $input['status'] ?? 'all';
        $search  = $_GET['search'] ?? $_GET['q'] ?? $input['q'] ?? '';
        $pg      = max(1, (int)($_GET['pg'] ?? $_GET['page_num'] ?? $input['pg'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? $input['per_page'] ?? 50)));

        $where = []; $params = [];
        if ($status !== 'all' && in_array($status, ['draft','published','archived'])) {
            $where[] = "status = ?"; $params[] = $status;
        }
        if ($search) {
            $where[] = "(title LIKE ? OR slug LIKE ?)";
            $params = array_merge($params, ["%{$search}%", "%{$search}%"]);
        }
        $wClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$tableName}` {$wClause}");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($pg - 1) * $perPage;
        $selectCols = ['id','title','slug','status','created_at','updated_at'];
        foreach (['seo_score','semantic_score','is_file_based','file_path','template','published_at','word_count','visibility','google_indexed'] as $oc) {
            if (in_array($oc, $existingCols)) $selectCols[] = $oc;
        }
        $selStr = implode(',', array_map(fn($c) => "`{$c}`", $selectCols));

        $stmt = $pdo->prepare("SELECT {$selStr} FROM `{$tableName}` {$wClause} ORDER BY updated_at DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        _pagesApiRespond(true, 'OK', 200, [
            'pages' => $pages,
            'pagination' => ['total' => $total, 'page' => $pg, 'per_page' => $perPage, 'total_pages' => max(1, ceil($total / $perPage))],
        ]);
        break;

    // ─── TOGGLE STATUS ────────────────────────────────────
    case 'toggle_status':
        $id = (int)($input['id'] ?? 0);
        $newStatus = $input['status'] ?? '';
        if ($id <= 0 || !in_array($newStatus, ['draft','published','archived'])) {
            _pagesApiRespond(false, 'Paramètres invalides', 400);
        }

        $sql = "UPDATE `{$tableName}` SET status = ?";
        $params = [$newStatus];
        if ($newStatus === 'published' && in_array('published_at', $existingCols)) {
            $sql .= ", published_at = COALESCE(published_at, NOW())";
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $pdo->prepare($sql)->execute($params);

        _pagesApiRespond(true, 'Statut mis à jour', 200, ['status' => $newStatus]);
        break;

    // ─── DUPLICATE ────────────────────────────────────────
    case 'duplicate':
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) _pagesApiRespond(false, 'ID invalide', 400);

        $stmt = $pdo->prepare("SELECT * FROM `{$tableName}` WHERE id = ?");
        $stmt->execute([$id]);
        $orig = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orig) _pagesApiRespond(false, 'Page introuvable', 404);

        $newTitle = $orig['title'] . ' (copie)';
        $newSlug = _pagesUniqueSlug($pdo, $tableName, ($orig['slug'] ?? '') . '-copie');

        $skip = ['id','created_at','updated_at','published_at'];
        $copyData = [];
        foreach ($orig as $col => $val) {
            if (in_array(strtolower($col), $skip)) continue;
            $copyData[$col] = $val;
        }
        $copyData['title'] = $newTitle;
        $copyData['slug'] = $newSlug;
        $copyData['status'] = 'draft';

        $newId = _pagesSafeInsert($pdo, $tableName, $copyData, $existingCols);
        _pagesApiRespond(true, 'Page dupliquée', 201, ['new_id' => $newId]);
        break;

    // ─── CHECK SLUG ───────────────────────────────────────
    case 'check_slug':
        $slug = _pagesSlug($_GET['slug'] ?? $input['slug'] ?? '');
        $excludeId = (int)($_GET['exclude_id'] ?? $input['exclude_id'] ?? 0);
        if (!$slug) _pagesApiRespond(false, 'Slug vide', 400);

        $sql = "SELECT id FROM `{$tableName}` WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $exists = (bool)$stmt->fetch();

        _pagesApiRespond(true, 'OK', 200, [
            'slug' => $slug,
            'available' => !$exists,
            'suggestion' => $exists ? _pagesUniqueSlug($pdo, $tableName, $slug, $excludeId) : $slug
        ]);
        break;

    // ─── AI SLUG ──────────────────────────────────────────
    case 'ai_slug':
        $title = trim($input['title'] ?? '');
        if (!$title) _pagesApiRespond(false, 'Titre requis', 400);
        $slug = _pagesSlug($title);
        _pagesApiRespond(true, 'OK', 200, ['slug' => $slug]);
        break;

    // ─── AI GENERATE (create_with_ai) ─────────────────────
    case 'ai_generate':
    case 'create_with_ai':
        $title    = trim($input['title'] ?? '');
        $slug     = trim($input['slug'] ?? '');
        $template = trim($input['template'] ?? 'standard');
        $persona  = trim($input['persona'] ?? 'general');
        $objective = trim($input['objective'] ?? '');

        if (!$title) _pagesApiRespond(false, 'Titre obligatoire', 400);
        if (!$slug) $slug = _pagesSlug($title);

        $slug = _pagesUniqueSlug($pdo, $tableName, $slug);

        $templateId = null;
        try {
            $tplStmt = $pdo->prepare("SELECT id FROM design_templates WHERE name=? OR slug=? LIMIT 1");
            $tplStmt->execute([$template, $template]);
            $tplRow = $tplStmt->fetch(PDO::FETCH_ASSOC);
            $templateId = $tplRow['id'] ?? null;
        } catch (PDOException $e) {}

        $adv = _pagesGetAdvisor();
        $advName = $adv['name'] ?? $adv['nom'] ?? 'Conseiller';
        $advCity = $adv['city'] ?? $adv['ville'] ?? 'Ville';

        // Load TPL for field keys
        $tplPath = __DIR__ . '/tpl.php';
        $TPL = [];
        if (file_exists($tplPath)) require $tplPath;
        $sections = $TPL[$template] ?? $TPL['standard'] ?? [];

        $fieldKeys = [];
        foreach ($sections as $sec) {
            foreach (($sec['fields'] ?? []) as $f) {
                if (in_array($f['type'] ?? '', ['text', 'textarea', 'rich'])) {
                    $fieldKeys[] = ($f['key'] ?? '') . ' (' . ($f['label'] ?? '') . ', ' . ($f['type'] ?? '') . ')';
                }
            }
        }

        $prompt = "Expert contenu immobilier pour {$advName} à {$advCity}.\n"
            . "Page: \"{$title}\" (template: {$template})\n"
            . "Persona: " . match ($persona) { 'vendeur' => 'vendeur', 'acheteur' => 'acheteur', 'proprietaire' => 'investisseur', 'nouveau_resident' => 'nouveau résident', default => 'visiteur' } . "\n"
            . "Objectif: " . ($objective ?: 'informer') . "\n\n"
            . "Champs à remplir:\n" . implode("\n", $fieldKeys) . "\n\n"
            . "+ seo_title (50-60 car) et seo_description (140-160 car)\n"
            . "Réponds JSON uniquement, sans backticks.";

        $ai = _pagesCallClaude($prompt, 3000);

        $fieldsData = ['persona' => $persona, 'objective' => $objective];
        $seoT = $seoD = '';

        if ($ai['success'] && !empty($ai['text'])) {
            $txt = preg_replace('/^```(?:json)?\s*/i', '', trim($ai['text']));
            $txt = preg_replace('/\s*```$/i', '', $txt);
            $parsed = json_decode($txt, true);
            if ($parsed && is_array($parsed)) {
                $seoT = $parsed['seo_title'] ?? '';
                $seoD = $parsed['seo_description'] ?? '';
                unset($parsed['seo_title'], $parsed['seo_description']);
                $fieldsData = array_merge($fieldsData, $parsed);
            }
        }

        $fieldsJSON = json_encode($fieldsData, JSON_UNESCAPED_UNICODE);

        $data = [
            'title'            => $title,
            'slug'             => $slug,
            'template'         => $template,
            'layout'           => $template,
            'status'           => 'draft',
            'fields'           => $fieldsJSON,
            'sections_json'    => $fieldsJSON,
            'template_id'      => $templateId,
            'meta_title'       => $seoT,
            'meta_description' => $seoD,
        ];

        $newId = _pagesSafeInsert($pdo, $tableName, $data, $existingCols);

        _pagesApiRespond(true, $ai['success'] ? 'Page créée avec IA' : 'Page créée (IA indisponible)', 201, [
            'id' => $newId,
            'page_id' => $newId,
            'ai_generated' => $ai['success'],
        ]);
        break;

    // ─── BULK DELETE ──────────────────────────────────────
    case 'bulk_delete':
        $ids = _pagesParseIds($input['ids'] ?? []);
        if (empty($ids)) _pagesApiRespond(false, 'Aucune sélection', 400);

        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM `{$tableName}` WHERE id IN ({$ph})");
        $stmt->execute($ids);
        $affected = $stmt->rowCount();

        try {
            $pdo->prepare("DELETE FROM seo_scores WHERE context = 'landing' AND entity_id IN ({$ph})")->execute($ids);
        } catch (PDOException $e) {}

        _pagesApiRespond(true, "{$affected} page(s) supprimée(s)", 200, ['affected' => $affected]);
        break;

    // ─── REORDER ──────────────────────────────────────────
    case 'reorder':
        $order = $input['order'] ?? [];
        if (!is_array($order)) _pagesApiRespond(false, 'Format invalide', 400);
        $stmt = $pdo->prepare("UPDATE `{$tableName}` SET sort_order = ? WHERE id = ?");
        foreach ($order as $pos => $id) { $stmt->execute([(int)$pos, (int)$id]); }
        _pagesApiRespond(true, 'Ordre mis à jour');
        break;

    // ─── AUTOSAVE ─────────────────────────────────────────
    case 'autosave':
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) _pagesApiRespond(false, 'ID invalide', 400);

        $content = $input['content'] ?? $input['html_content'] ?? '';
        $title   = trim(strip_tags($input['title'] ?? ''));
        $wc = _pagesWordCount($content);

        $data = ['word_count' => $wc];
        if ($title) $data['title'] = $title;
        if (in_array('content', $existingCols)) $data['content'] = $content;
        if (in_array('html_content', $existingCols)) $data['html_content'] = $content;

        _pagesSafeUpdate($pdo, $tableName, $id, $data, $existingCols);
        _pagesApiRespond(true, 'Autosave OK', 200, ['saved_at' => date('H:i:s')]);
        break;

    // ─── BULK STATUS ──────────────────────────────────────
    case 'bulk_status':
        $ids = _pagesParseIds($input['ids'] ?? []);
        $newStatus = $input['status'] ?? '';
        if (empty($ids)) _pagesApiRespond(false, 'Aucune sélection', 400);
        if (!in_array($newStatus, ['draft','published','archived'])) {
            _pagesApiRespond(false, 'Statut invalide', 400);
        }

        $ph = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE `{$tableName}` SET status = ?";
        $params = [$newStatus];
        if ($newStatus === 'published' && in_array('published_at', $existingCols)) {
            $sql .= ", published_at = COALESCE(published_at, NOW())";
        }
        $sql .= " WHERE id IN ({$ph})";
        $params = array_merge($params, $ids);
        $pdo->prepare($sql)->execute($params);

        _pagesApiRespond(true, count($ids) . ' page(s) mise(s) à jour', 200, ['affected' => count($ids)]);
        break;

    // ─── DEFAULT ──────────────────────────────────────────
    default:
        _pagesApiRespond(false, "Action '{$action}' non supportée", 400);
}

} catch (PDOException $e) {
    error_log("Pages API [PDO]: " . $e->getMessage());
    _pagesApiRespond(false, 'Erreur base de données', 500);
} catch (Exception $e) {
    error_log("Pages API: " . $e->getMessage());
    _pagesApiRespond(false, 'Erreur serveur', 500);
}

// ─── Helper réponse ───────────────────────────────────────────
function _pagesApiRespond(bool $success, string $message = '', int $code = 200, array $extra = []): void {
    http_response_code($code);
    $out = array_merge(['success' => $success, 'message' => $message], $extra);
    if (!$success && !isset($out['error'])) $out['error'] = $message;
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
