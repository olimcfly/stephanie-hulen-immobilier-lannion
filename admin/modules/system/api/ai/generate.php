<?php
/**
 * API IA — Endpoint universel de generation de contenu
 * /admin/api/ai/generate.php
 *
 * Supporte : Anthropic (Claude) en priorite, fallback OpenAI
 * Appele par tous les modules (secteurs, pages, articles, etc.)
 * via POST avec les parametres : module, action, prompt, [champs contexte]
 *
 * Reponse JSON : { success: true, content: "..." }
 *             ou { success: false, error: "..." }
 */

// ─── Auth stricte ───
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorise']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// ─── Root & config ───
if (!defined('ROOT_PATH')) {
    $candidates = [
        dirname(dirname(dirname(dirname(__DIR__)))),
        dirname(dirname(dirname(__DIR__))),
        dirname(dirname(__DIR__)),
    ];
    foreach ($candidates as $r) {
        if (file_exists($r . '/config/config.php')) { define('ROOT_PATH', $r); break; }
    }
    if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}
foreach ([ROOT_PATH . '/config/config.php', ROOT_PATH . '/config/constants.php'] as $f) {
    if (file_exists($f)) { @require_once $f; }
}

// ─── Cles API ───
$anthropicKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
$openaiKey    = defined('OPENAI_API_KEY')     ? OPENAI_API_KEY    : '';

if (empty($anthropicKey) && empty($openaiKey)) {
    echo json_encode(['success' => false, 'error' => 'Aucune cle API configuree. Ajoutez ANTHROPIC_API_KEY ou OPENAI_API_KEY dans config.php']);
    exit;
}

// ─── Lecture POST ───
$module  = trim($_POST['module']  ?? $_GET['module']  ?? 'general');
$action  = trim($_POST['action']  ?? $_GET['action']  ?? 'generate');
$prompt  = trim($_POST['prompt']  ?? '');
$context = trim($_POST['context'] ?? '');

// Champs contextuels optionnels (pour les modules)
$nom     = trim($_POST['nom']    ?? $_POST['title']   ?? '');
$ville   = trim($_POST['ville']  ?? '');
$typeS   = trim($_POST['type']   ?? '');

// ─── Validation ───
if (empty($prompt)) {
    echo json_encode(['success' => false, 'error' => 'Prompt manquant']);
    exit;
}

// ─── Systeme prompt selon module ───
function buildSystemPrompt(string $module, string $action): string {
    $base = "Tu es un expert en immobilier et en marketing digital pour les conseillers immobiliers independants en France. "
          . "Tu generes des contenus professionnels, percutants et optimises SEO. "
          . "Tu reponds UNIQUEMENT avec le contenu demande, sans introduction, sans explication, sans balises markdown ni backticks. "
          . "Ecris en francais courant, style professionnel mais accessible.";

    $modules = [
        'secteurs' => " Tu specialises dans la redaction de contenus de pages de quartiers et secteurs immobiliers locaux. "
                    . "Mets en valeur le cadre de vie, les atouts immobiliers, les transports, les prix du marche. "
                    . "Ton objectif : attirer des vendeurs et acheteurs de ce secteur specifique.",

        'pages'    => " Tu specialises dans la redaction de pages de site immobilier. "
                    . "Pages de service, pages de conversion, pages a propos. "
                    . "Style persuasif mais authentique, avec des appels a l'action clairs.",

        'articles' => " Tu specialises dans la redaction d'articles de blog immobilier SEO. "
                    . "Articles informatifs, conseils pratiques, actualite du marche. "
                    . "Integre naturellement les mots-cles pour le referencement.",

        'seo'      => " Tu es expert SEO immobilier local. "
                    . "Tu optimises les meta titles (55-65 car.) et meta descriptions (140-160 car.) "
                    . "pour maximiser le taux de clic dans Google.",
    ];

    $actions = [
        'nom'          => " Reponds avec UNIQUEMENT le texte du nom demande. Pas de ponctuation finale.",
        'meta_title'   => " Reponds avec UNIQUEMENT le meta title, 55-65 caracteres maximum. Pas de guillemets.",
        'meta_desc'    => " Reponds avec UNIQUEMENT la meta description, 140-160 caracteres. Pas de guillemets.",
        'description'  => " Reponds avec UNIQUEMENT le texte de description, 2-3 phrases maximum.",
        'hero_title'   => " Reponds avec UNIQUEMENT le titre hero, une ligne, percutant.",
        'hero_subtitle'=> " Reponds avec UNIQUEMENT le sous-titre, une ligne courte.",
        'atouts'       => " Reponds avec une liste des atouts, un par ligne, sans puces ni numeros.",
        'prix_moyen'   => " Reponds avec UNIQUEMENT la fourchette de prix au m2, format court.",
        'ambiance'     => " Reponds avec UNIQUEMENT 2-3 mots ou une courte phrase.",
        'transport'    => " Reponds avec UNIQUEMENT les infos transports, une phrase.",
        'content'      => " Reponds avec du HTML propre (h2, h3, p, ul, li). Pas de balise html/body/head.",
        'excerpt'      => " Reponds avec UNIQUEMENT l'extrait, 1-2 phrases.",
        'title'        => " Reponds avec UNIQUEMENT le titre, sans ponctuation finale.",
    ];

    $sys = $base;
    $sys .= $modules[$module] ?? '';
    $sys .= $actions[$action]  ?? '';
    return $sys;
}

// ─── Action builder_ia : system + user prompt fournis directement par le builder ───
if ($action === 'builder_ia') {
    $customSystem = trim($_POST['system_prompt'] ?? '');
    $userPrompt   = trim($_POST['user_prompt']   ?? $prompt);
    $maxTok       = min((int)($_POST['max_tokens'] ?? 2500), 4000);

    if (empty($customSystem) || empty($userPrompt)) {
        echo json_encode(['success' => false, 'error' => 'system_prompt et user_prompt requis']);
        exit;
    }

    $result = null;
    if (!empty($anthropicKey)) {
        $payload = json_encode([
            'model'      => 'claude-sonnet-4-20250514',
            'max_tokens' => $maxTok,
            'system'     => $customSystem,
            'messages'   => [['role' => 'user', 'content' => $userPrompt]],
        ]);
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $anthropicKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 60,
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($raw && $code === 200) {
            $decoded = json_decode($raw, true);
            $html    = trim($decoded['content'][0]['text'] ?? '');
            // Nettoyer backticks markdown
            $html = preg_replace('/^```(?:html)?\s*/i', '', $html);
            $html = preg_replace('/\s*```$/', '', $html);
            echo json_encode([
                'success' => true,
                'html'    => $html,
                'content' => $html,
                'text'    => $html,
            ]);
        } else {
            $err = json_decode($raw, true)['error']['message'] ?? ('HTTP ' . $code);
            echo json_encode(['success' => false, 'error' => $err]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ANTHROPIC_API_KEY non configure']);
    }
    exit;
}

$systemPrompt = buildSystemPrompt($module, $action);

// ─── Appel Anthropic (Claude) ───
function callAnthropic(string $apiKey, string $systemPrompt, string $userPrompt): array {
    $payload = json_encode([
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 1500,
        'system'     => $systemPrompt,
        'messages'   => [
            ['role' => 'user', 'content' => $userPrompt]
        ],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['success' => false, 'error' => 'Erreur cURL : ' . $curlErr];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $msg = $data['error']['message'] ?? $response;
        return ['success' => false, 'error' => 'Anthropic ' . $httpCode . ' : ' . $msg];
    }

    $content = $data['content'][0]['text'] ?? '';
    if (empty($content)) {
        return ['success' => false, 'error' => 'Reponse Claude vide'];
    }

    return ['success' => true, 'content' => trim($content), 'provider' => 'claude'];
}

// ─── Appel OpenAI ───
function callOpenAI(string $apiKey, string $systemPrompt, string $userPrompt): array {
    $payload = json_encode([
        'model'       => 'gpt-4o-mini',
        'max_tokens'  => 1500,
        'temperature' => 0.7,
        'messages'    => [
            ['role' => 'system',  'content' => $systemPrompt],
            ['role' => 'user',    'content' => $userPrompt],
        ],
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return ['success' => false, 'error' => 'Erreur cURL : ' . $curlErr];
    }

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        $msg = $data['error']['message'] ?? $response;
        return ['success' => false, 'error' => 'OpenAI ' . $httpCode . ' : ' . $msg];
    }

    $content = $data['choices'][0]['message']['content'] ?? '';
    if (empty($content)) {
        return ['success' => false, 'error' => 'Reponse OpenAI vide'];
    }

    return ['success' => true, 'content' => trim($content), 'provider' => 'openai'];
}

// ─── Nettoyage du contenu retourne ───
function cleanAiOutput(string $content, string $action): string {
    // Supprimer backticks markdown si present
    $content = preg_replace('/^```(?:html|php|markdown|json)?\s*/i', '', $content);
    $content = preg_replace('/\s*```$/', '', $content);

    // Pour les champs courts : enlever guillemets enveloppants
    $shortFields = ['nom','hero_title','hero_subtitle','ambiance','prix_moyen','transport','title','excerpt'];
    if (in_array($action, $shortFields)) {
        $content = trim($content, '"\'«»');
    }

    return trim($content);
}

// ─── Execution ───
// Priorite Anthropic, fallback OpenAI
$result = null;

if (!empty($anthropicKey)) {
    $result = callAnthropic($anthropicKey, $systemPrompt, $prompt);
    // Fallback si erreur Anthropic et OpenAI disponible
    if (!$result['success'] && !empty($openaiKey)) {
        $result = callOpenAI($openaiKey, $systemPrompt, $prompt);
    }
} elseif (!empty($openaiKey)) {
    $result = callOpenAI($openaiKey, $systemPrompt, $prompt);
}

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Impossible d\'appeler l\'API IA']);
    exit;
}

if (!$result['success']) {
    // Log l'erreur
    $logFile = ROOT_PATH . '/logs/ai_errors.log';
    if (is_writable(dirname($logFile))) {
        error_log(date('[Y-m-d H:i:s]') . ' AI ERROR [' . $module . '/' . $action . '] ' . $result['error'] . "\n", 3, $logFile);
    }
    echo json_encode(['success' => false, 'error' => $result['error']]);
    exit;
}

// Nettoyage final
$result['content'] = cleanAiOutput($result['content'], $action);

// ─── Reponse ───
echo json_encode([
    'success'  => true,
    'content'  => $result['content'],
    // Alias pour compatibilite avec differents modules
    'text'     => $result['content'],
    'result'   => $result['content'],
    'response' => $result['content'],
    'provider' => $result['provider'] ?? 'unknown',
    'module'   => $module,
    'action'   => $action,
]);