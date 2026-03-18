<?php
/**
 * /admin/api/content/pages.php
 * API Pages — create, save_page, create_with_ai, delete, check_slug, ai_slug
 */

header('Content-Type: application/json; charset=utf-8');

// ── Init ──
require_once dirname(__DIR__, 3) . '/includes/init.php';

try {
    $pdo = getDB();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur BD']);
    exit;
}

// ── Helpers ──
function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function makeSlug($str) {
    $str = mb_strtolower($str);
    $str = preg_replace('/[àáâãäå]/u', 'a', $str);
    $str = preg_replace('/[èéêë]/u', 'e', $str);
    $str = preg_replace('/[ìíîï]/u', 'i', $str);
    $str = preg_replace('/[òóôõö]/u', 'o', $str);
    $str = preg_replace('/[ùúûü]/u', 'u', $str);
    $str = preg_replace('/[ýÿ]/u', 'y', $str);
    $str = preg_replace('/[ç]/u', 'c', $str);
    $str = preg_replace('/[œ]/u', 'oe', $str);
    $str = preg_replace('/[æ]/u', 'ae', $str);
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', trim($str));
    return substr($str, 0, 80);
}

function getAdvisor() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM advisor_context LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function callClaude($prompt, $maxTokens = 2000) {
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

// ── Lire le body (JSON ou FormData) ──
$body = json_decode(file_get_contents('php://input'), true) ?? [];
if (empty($body)) $body = $_POST;
$action = $body['action'] ?? $_GET['action'] ?? '';

if (!$action) {
    respond(['success' => false, 'error' => 'Action manquante'], 400);
}

// ── Router ──
switch ($action) {

case 'check_slug':
    $slug = trim($_GET['slug'] ?? $body['slug'] ?? '');
    if (!$slug) respond(['available' => false, 'error' => 'Slug vide']);
    $stmt = $pdo->prepare("SELECT id FROM pages WHERE slug = ?");
    $stmt->execute([$slug]);
    if ($stmt->fetch()) {
        respond(['available' => false, 'suggestion' => $slug . '-2']);
    }
    respond(['available' => true]);
    break;

case 'ai_slug':
    $title = trim($body['title'] ?? '');
    if (!$title) respond(['success' => false, 'error' => 'Titre requis']);
    $slug = makeSlug($title);
    respond(['success' => true, 'slug' => $slug]);
    break;

case 'create':
    $title = trim($body['title'] ?? '');
    $slug = trim($body['slug'] ?? '');
    $template = trim($body['template'] ?? 'standard');
    $persona = trim($body['persona'] ?? 'general');
    $objective = trim($body['objective'] ?? '');

    if (!$title) respond(['success' => false, 'error' => 'Titre obligatoire'], 400);
    if (!$slug) $slug = makeSlug($title);

    $chk = $pdo->prepare("SELECT id FROM pages WHERE slug=?");
    $chk->execute([$slug]);
    if ($chk->fetch()) $slug .= '-' . time();

    $tplStmt = $pdo->prepare("SELECT id FROM design_templates WHERE name=? OR slug=? LIMIT 1");
    $tplStmt->execute([$template, $template]);
    $tplRow = $tplStmt->fetch(PDO::FETCH_ASSOC);
    $templateId = $tplRow['id'] ?? null;

    $fieldsData = ['persona' => $persona, 'objective' => $objective];
    $fieldsJSON = json_encode($fieldsData, JSON_UNESCAPED_UNICODE);

    $pdo->prepare(
        "INSERT INTO pages (title, slug, template, layout, status, fields, sections_json, template_id, created_at, updated_at)
         VALUES (?, ?, ?, ?, 'draft', ?, '{}', ?, NOW(), NOW())"
    )->execute([$title, $slug, $template, $template, $fieldsJSON, $templateId]);

    $newId = (int)$pdo->lastInsertId();
    respond(['success' => true, 'page_id' => $newId, 'id' => $newId, 'message' => 'Page créée']);
    break;

case 'save_page':
    $id = !empty($body['id']) ? (int)$body['id'] : null;
    $title = trim($body['title'] ?? '');
    $slug = trim($body['slug'] ?? '');
    $template = trim($body['template'] ?? 'standard');
    $status = trim($body['status'] ?? 'draft');
    $metaTitle = trim($body['meta_title'] ?? '');
    $metaDesc = trim($body['meta_description'] ?? '');
    $sectionsJson = $body['sections_json'] ?? '{}';

    if (!$title) respond(['success' => false, 'error' => 'Titre obligatoire'], 400);
    if (!$slug) $slug = makeSlug($title);
    if (!in_array($status, ['draft', 'published'])) $status = 'draft';

    // Slug unique
    $chkSql = $id
        ? "SELECT id FROM pages WHERE slug = ? AND id != ?"
        : "SELECT id FROM pages WHERE slug = ?";
    $chk = $pdo->prepare($chkSql);
    $id ? $chk->execute([$slug, $id]) : $chk->execute([$slug]);
    if ($chk->fetch()) $slug .= '-' . time();

    // Template ID
    $tplStmt = $pdo->prepare("SELECT id FROM design_templates WHERE name = ? OR slug = ? LIMIT 1");
    $tplStmt->execute([$template, $template]);
    $tplRow = $tplStmt->fetch(PDO::FETCH_ASSOC);
    $templateId = $tplRow['id'] ?? null;

    // Merger sections dans fields
    $existingFields = [];
    if ($id) {
        $ef = $pdo->prepare("SELECT fields FROM pages WHERE id = ?");
        $ef->execute([$id]);
        $row = $ef->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['fields'])) {
            $existingFields = json_decode($row['fields'], true) ?: [];
        }
    }
    $newSections = json_decode($sectionsJson, true) ?: [];
    $mergedFields = array_merge($existingFields, $newSections);
    $fieldsJSON = json_encode($mergedFields, JSON_UNESCAPED_UNICODE);

    if ($id) {
        $pdo->prepare(
            "UPDATE pages SET
                title=?, slug=?, template=?, layout=?, status=?,
                fields=?, sections_json=?, template_id=?,
                meta_title=?, meta_description=?, updated_at=NOW()
             WHERE id=?"
        )->execute([
            $title, $slug, $template, $template, $status,
            $fieldsJSON, $sectionsJson, $templateId,
            $metaTitle, $metaDesc, $id
        ]);
        respond(['success' => true, 'id' => $id, 'slug' => $slug, 'message' => 'Page mise à jour']);
    } else {
        $pdo->prepare(
            "INSERT INTO pages
            (title, slug, template, layout, status, fields, sections_json, template_id, meta_title, meta_description, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        )->execute([
            $title, $slug, $template, $template, $status,
            $fieldsJSON, $sectionsJson, $templateId,
            $metaTitle, $metaDesc
        ]);
        $newId = (int)$pdo->lastInsertId();
        respond(['success' => true, 'id' => $newId, 'slug' => $slug, 'message' => 'Page créée']);
    }
    break;

case 'create_with_ai':
    $title = trim($body['title'] ?? '');
    $slug = trim($body['slug'] ?? '');
    $template = trim($body['template'] ?? 'standard');
    $persona = trim($body['persona'] ?? 'general');
    $objective = trim($body['objective'] ?? '');

    if (!$title) respond(['success' => false, 'error' => 'Titre obligatoire'], 400);
    if (!$slug) $slug = makeSlug($title);

    $chk = $pdo->prepare("SELECT id FROM pages WHERE slug=?");
    $chk->execute([$slug]);
    if ($chk->fetch()) $slug .= '-' . time();

    $tplStmt = $pdo->prepare("SELECT id FROM design_templates WHERE name=? OR slug=? LIMIT 1");
    $tplStmt->execute([$template, $template]);
    $tplRow = $tplStmt->fetch(PDO::FETCH_ASSOC);
    $templateId = $tplRow['id'] ?? null;

    $adv = getAdvisor();
    $advName = $adv['name'] ?? $adv['nom'] ?? 'Conseiller';
    $advCity = $adv['city'] ?? $adv['ville'] ?? 'Ville';

    $tplPath = dirname(__DIR__, 2) . '/modules/content/pages/tpl.php';
    $TPL = [];
    if (file_exists($tplPath)) require $tplPath;
    $sections = $TPL[$template] ?? $TPL['standard'] ?? [];

    $fieldKeys = [];
    foreach ($sections as $sec) {
        foreach ($sec['fields'] as $f) {
            if (in_array($f['type'], ['text', 'textarea', 'rich'])) {
                $fieldKeys[] = $f['key'] . ' (' . $f['label'] . ', ' . $f['type'] . ')';
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

    $ai = callClaude($prompt, 3000);

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

    $pdo->prepare(
        "INSERT INTO pages
        (title, slug, template, layout, status, fields, sections_json, template_id, meta_title, meta_description, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'draft', ?, '{}', ?, ?, ?, NOW(), NOW())"
    )->execute([$title, $slug, $template, $template, $fieldsJSON, $templateId, $seoT, $seoD]);

    $newId = (int)$pdo->lastInsertId();
    respond([
        'success' => true, 'page_id' => $newId, 'id' => $newId,
        'ai_generated' => $ai['success'],
        'message' => $ai['success'] ? 'Page créée avec IA' : 'Page créée (IA indisponible)'
    ]);
    break;

case 'delete':
    $pageId = (int)($body['page_id'] ?? $_POST['page_id'] ?? 0);
    if (!$pageId) respond(['success' => false, 'error' => 'ID manquant'], 400);
    $pdo->prepare("DELETE FROM pages WHERE id = ?")->execute([$pageId]);
    respond(['success' => true, 'message' => 'Page supprimée']);
    break;

default:
    respond(['success' => false, 'error' => 'Action inconnue: ' . $action], 400);
}