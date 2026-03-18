<?php
/**
 * DIAGNOSTIC PAGES MODULE
 * /admin/modules/content/pages/diagnostic-api.php
 * 
 * Teste tous les endpoints et fonctionnalités :
 * ✅ Connexion DB
 * ✅ Table pages (structure, colonnes)
 * ✅ API pages.php (accessible, create, save, delete)
 * ✅ API IA (clé Claude, appel test)
 * ✅ Fichiers requis (tpl-definitions, etc.)
 * 
 * Accès : ?page=pages&action=diagnostic
 */
if (!isset($pdo)) {
    require_once dirname(__DIR__, 4) . '/includes/init.php';
}

$results = [];
$apiBase = '/admin/api/content/pages.php';

// ── Helper ──
function testResult($name, $ok, $detail = '', $fix = '') {
    return [
        'name' => $name,
        'ok' => $ok,
        'detail' => $detail,
        'fix' => $fix,
    ];
}

// ═══════════════════════════════════════════════════
// TEST 1 : Connexion DB
// ═══════════════════════════════════════════════════
try {
    $pdo->query("SELECT 1");
    $results[] = testResult('Connexion DB', true, 'PDO connecté');
} catch (Throwable $e) {
    $results[] = testResult('Connexion DB', false, $e->getMessage(), 'Vérifier config.php et init.php');
}

// ═══════════════════════════════════════════════════
// TEST 2 : Table pages existe
// ═══════════════════════════════════════════════════
try {
    $cols = $pdo->query("SHOW COLUMNS FROM pages")->fetchAll(PDO::FETCH_COLUMN);
    $results[] = testResult('Table pages', true, count($cols) . ' colonnes : ' . implode(', ', $cols));
    
    // Vérifier colonnes critiques
    $required = ['id', 'title', 'slug', 'status', 'fields', 'template'];
    $missing = array_diff($required, $cols);
    if ($missing) {
        $results[] = testResult('Colonnes requises', false, 'Manquantes : ' . implode(', ', $missing), 'ALTER TABLE pages ADD COLUMN ...');
    } else {
        $results[] = testResult('Colonnes requises', true, 'Toutes présentes : ' . implode(', ', $required));
    }
    
    // Colonnes SEO
    $seoRequired = ['meta_title', 'meta_description', 'seo_score', 'semantic_score'];
    $seoMissing = array_diff($seoRequired, $cols);
    if ($seoMissing) {
        $results[] = testResult('Colonnes SEO', false, 'Manquantes : ' . implode(', ', $seoMissing), 'Ces colonnes sont optionnelles mais recommandées');
    } else {
        $results[] = testResult('Colonnes SEO', true, 'Toutes présentes');
    }
    
    // Nombre de pages
    $count = $pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn();
    $results[] = testResult('Données pages', true, $count . ' pages en base');
    
} catch (Throwable $e) {
    $results[] = testResult('Table pages', false, $e->getMessage(), 'La table pages n\'existe pas — créer via migration');
}

// ═══════════════════════════════════════════════════
// TEST 3 : Fichiers module
// ═══════════════════════════════════════════════════
$files = [
    'index.php'          => __DIR__ . '/index.php',
    'edit.php'           => __DIR__ . '/edit.php',
    'tpl-definitions.php'=> __DIR__ . '/tpl-definitions.php',
    'guide-wizard.php'   => __DIR__ . '/guide-wizard.php',
];
foreach ($files as $name => $path) {
    $exists = file_exists($path);
    $size = $exists ? filesize($path) : 0;
    $results[] = testResult("Fichier: {$name}", $exists, $exists ? number_format($size) . ' octets' : 'INTROUVABLE', $exists ? '' : "Uploader {$name} dans " . __DIR__);
}

// ═══════════════════════════════════════════════════
// TEST 4 : Fichier API pages.php
// ═══════════════════════════════════════════════════
$apiPaths = [
    '/admin/api/content/pages.php' => dirname(__DIR__, 3) . '/api/content/pages.php',
    '/admin/api/pages.php'         => dirname(__DIR__, 3) . '/api/pages.php',
    'Module api.php'               => __DIR__ . '/api.php',
];
$apiFound = false;
foreach ($apiPaths as $label => $path) {
    $exists = file_exists($path);
    if ($exists) {
        $content = file_get_contents($path);
        $hasCreate = strpos($content, "'create'") !== false || strpos($content, '"create"') !== false;
        $hasCreateAI = strpos($content, 'create_with_ai') !== false;
        $hasSaveFields = strpos($content, 'save_fields') !== false;
        $hasFieldGenerate = strpos($content, 'field_generate') !== false;
        
        $actions = [];
        if ($hasCreate) $actions[] = 'create';
        if ($hasCreateAI) $actions[] = 'create_with_ai';
        if ($hasSaveFields) $actions[] = 'save_fields';
        if ($hasFieldGenerate) $actions[] = 'field_generate';
        
        $results[] = testResult("API: {$label}", true, 
            filesize($path) . ' octets — Actions: ' . ($actions ? implode(', ', $actions) : 'AUCUNE DÉTECTÉE'),
            !$actions ? 'Le fichier API ne contient pas les actions nécessaires' : ''
        );
        $apiFound = true;
    } else {
        $results[] = testResult("API: {$label}", false, 'Fichier introuvable : ' . $path);
    }
}
if (!$apiFound) {
    $results[] = testResult('API Pages (global)', false, 'Aucun fichier API trouvé !', 'Uploader pages-api.php → pages.php dans /admin/api/content/');
}

// ═══════════════════════════════════════════════════
// TEST 5 : API accessible via HTTP
// ═══════════════════════════════════════════════════
$testApiUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $apiBase . '?action=list';
$ch = curl_init($testApiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIE => 'PHPSESSID=' . session_id(),
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($httpCode === 200) {
    $json = json_decode($response, true);
    if ($json && isset($json['success'])) {
        $results[] = testResult('API HTTP (list)', true, "HTTP {$httpCode} — success=" . ($json['success'] ? 'true' : 'false'));
    } else {
        $results[] = testResult('API HTTP (list)', false, "HTTP {$httpCode} — Réponse non-JSON : " . mb_substr($response, 0, 200), 'L\'API retourne du HTML au lieu de JSON');
    }
} elseif ($httpCode === 404) {
    $results[] = testResult('API HTTP (list)', false, "HTTP 404 — {$apiBase} introuvable", 'Le fichier pages.php n\'existe pas à cet emplacement');
} elseif ($httpCode === 500) {
    $results[] = testResult('API HTTP (list)', false, "HTTP 500 — Erreur serveur", 'Vérifier error_log pour les détails PHP');
} else {
    $results[] = testResult('API HTTP (list)', false, "HTTP {$httpCode} — " . ($curlErr ?: mb_substr($response, 0, 200)));
}

// ═══════════════════════════════════════════════════
// TEST 6 : API POST create (test sec)
// ═══════════════════════════════════════════════════
$testCreateUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $apiBase;
$ch2 = curl_init($testCreateUrl);
$testPayload = json_encode([
    'action' => 'create',
    'title' => '__DIAG_TEST_' . time(),
    'slug' => '__diag-test-' . time(),
    'template' => 'standard',
    'csrf_token' => $_SESSION['csrf_token'] ?? '',
]);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $testPayload,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
    CURLOPT_COOKIE => 'PHPSESSID=' . session_id(),
    CURLOPT_TIMEOUT => 10,
]);
$resp2 = curl_exec($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

$json2 = json_decode($resp2, true);
if ($code2 === 200 && $json2 && !empty($json2['success'])) {
    $testPageId = $json2['page_id'] ?? $json2['id'] ?? 0;
    $results[] = testResult('API POST create', true, "Page test créée ID #{$testPageId}");
    
    // Nettoyer la page test
    if ($testPageId) {
        try {
            $pdo->prepare("DELETE FROM pages WHERE id = ?")->execute([$testPageId]);
            $results[] = testResult('Nettoyage page test', true, "Page #{$testPageId} supprimée");
        } catch (Throwable $e) {
            $results[] = testResult('Nettoyage page test', false, $e->getMessage());
        }
    }
} else {
    $detail2 = "HTTP {$code2}";
    if ($json2 && isset($json2['error'])) $detail2 .= " — " . $json2['error'];
    elseif ($resp2) $detail2 .= " — " . mb_substr($resp2, 0, 300);
    $results[] = testResult('API POST create', false, $detail2, 'Vérifier que pages.php gère action=create avec JSON body');
}

// ═══════════════════════════════════════════════════
// TEST 7 : Clé Claude / IA
// ═══════════════════════════════════════════════════
$claudeKey = null;
try {
    // Méthode 1 : table api_keys
    $stmt = $pdo->prepare("SELECT api_key_encrypted, service_key FROM api_keys WHERE service_key IN ('claude', 'anthropic') AND is_active = 1 LIMIT 1");
    $stmt->execute();
    $keyRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($keyRow) {
        $rawKey = $keyRow['api_key_encrypted'];
        if (!empty($rawKey)) {
            // Tenter décryptage AES-256
            $encKey = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : ($_ENV['ENCRYPTION_KEY'] ?? null);
            if ($encKey) {
                $data = base64_decode($rawKey);
                if ($data && strlen($data) > 16) {
                    $iv = substr($data, 0, 16);
                    $encrypted = substr($data, 16);
                    $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $encKey, OPENSSL_RAW_DATA, $iv);
                    if ($decrypted) {
                        $claudeKey = $decrypted;
                        $results[] = testResult('Clé Claude (décryptée)', true, 'Préfixe: ' . substr($claudeKey, 0, 15) . '... (' . strlen($claudeKey) . ' car)');
                    } else {
                        // Tester si c'est en clair
                        if (str_starts_with($rawKey, 'sk-ant-')) {
                            $claudeKey = $rawKey;
                            $results[] = testResult('Clé Claude (clair)', true, 'Préfixe: ' . substr($claudeKey, 0, 15) . '...');
                        } else {
                            $results[] = testResult('Clé Claude (décryptage)', false, 'Décryptage échoué et pas en clair', 'Vérifier ENCRYPTION_KEY ou re-saisir la clé');
                        }
                    }
                } else {
                    if (str_starts_with($rawKey, 'sk-ant-')) {
                        $claudeKey = $rawKey;
                        $results[] = testResult('Clé Claude (clair)', true, 'Préfixe: ' . substr($claudeKey, 0, 15) . '...');
                    } else {
                        $results[] = testResult('Clé Claude (format)', false, 'Valeur non reconnue', 'Re-saisir la clé');
                    }
                }
            } else {
                // Pas de ENCRYPTION_KEY, tester en clair
                if (str_starts_with($rawKey, 'sk-ant-')) {
                    $claudeKey = $rawKey;
                    $results[] = testResult('Clé Claude (clair, pas de ENCRYPTION_KEY)', true, 'Préfixe: ' . substr($claudeKey, 0, 15) . '...');
                } else {
                    $results[] = testResult('Clé Claude', false, 'ENCRYPTION_KEY non définie et clé chiffrée', 'Définir ENCRYPTION_KEY dans config.php');
                }
            }
        } else {
            $results[] = testResult('Clé Claude', false, 'Colonne api_key_encrypted vide', 'Saisir la clé via admin');
        }
    } else {
        $results[] = testResult('Clé Claude', false, 'Aucune entrée service=claude/anthropic dans api_keys', 'Ajouter la clé via Système > Clés API');
    }
} catch (Throwable $e) {
    $results[] = testResult('Table api_keys', false, $e->getMessage(), 'La table api_keys n\'existe peut-être pas');
}

// ═══════════════════════════════════════════════════
// TEST 8 : Appel Claude API (si clé disponible)
// ═══════════════════════════════════════════════════
if ($claudeKey) {
    $ch3 = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch3, [
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $claudeKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'claude-sonnet-4-20250514',
            'max_tokens' => 50,
            'messages' => [['role' => 'user', 'content' => 'Réponds uniquement "OK_TEST_DIAG". Rien d\'autre.']],
        ]),
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp3 = curl_exec($ch3);
    $code3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
    $err3 = curl_error($ch3);
    curl_close($ch3);
    
    if ($code3 === 200) {
        $json3 = json_decode($resp3, true);
        $text3 = $json3['content'][0]['text'] ?? '';
        $results[] = testResult('Appel Claude API', true, "HTTP 200 — Réponse: \"{$text3}\"");
    } elseif ($code3 === 401) {
        $results[] = testResult('Appel Claude API', false, 'HTTP 401 — Clé API invalide ou expirée', 'Mettre à jour la clé Claude');
    } elseif ($code3 === 429) {
        $results[] = testResult('Appel Claude API', false, 'HTTP 429 — Rate limit atteint', 'Attendre quelques minutes');
    } else {
        $errDetail = $err3 ?: mb_substr($resp3, 0, 200);
        $results[] = testResult('Appel Claude API', false, "HTTP {$code3} — {$errDetail}");
    }
} else {
    $results[] = testResult('Appel Claude API', false, 'Skipped — pas de clé disponible', 'Configurer la clé Claude d\'abord');
}

// ═══════════════════════════════════════════════════
// TEST 9 : advisor_context
// ═══════════════════════════════════════════════════
try {
    $stmt = $pdo->query("SELECT * FROM advisor_context LIMIT 1");
    $adv = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($adv) {
        $name = $adv['name'] ?? $adv['nom'] ?? '?';
        $city = $adv['city'] ?? $adv['ville'] ?? '?';
        $results[] = testResult('Contexte conseiller', true, "{$name} — {$city}");
    } else {
        $results[] = testResult('Contexte conseiller', false, 'Table vide', 'Remplir via IA > Contexte conseiller');
    }
} catch (Throwable $e) {
    $results[] = testResult('Contexte conseiller', false, 'Table advisor_context inexistante : ' . $e->getMessage());
}

// ═══════════════════════════════════════════════════
// RENDU
// ═══════════════════════════════════════════════════
$passed = count(array_filter($results, fn($r) => $r['ok']));
$failed = count(array_filter($results, fn($r) => !$r['ok']));
$total = count($results);
?>

<style>
.diag-wrap { font-family: 'Inter', -apple-system, sans-serif; max-width: 960px; }
.diag-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
.diag-header h2 { font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 10px; margin: 0; }
.diag-header h2 i { color: #6366f1; }
.diag-summary { display: flex; gap: 12px; }
.diag-pill { padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 700; }
.diag-pill.ok { background: #d1fae5; color: #065f46; }
.diag-pill.ko { background: #fee2e2; color: #991b1b; }
.diag-pill.total { background: #e0e7ff; color: #3730a3; }
.diag-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; overflow: hidden; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
.diag-row { display: flex; align-items: flex-start; gap: 12px; padding: 14px 18px; border-bottom: 1px solid #f3f4f6; transition: background .15s; }
.diag-row:last-child { border-bottom: none; }
.diag-row:hover { background: #fafbfc; }
.diag-ico { width: 28px; height: 28px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; margin-top: 2px; }
.diag-ico.ok { background: #d1fae5; color: #059669; }
.diag-ico.ko { background: #fee2e2; color: #dc2626; }
.diag-info { flex: 1; min-width: 0; }
.diag-name { font-size: 13px; font-weight: 600; color: #111827; margin-bottom: 3px; }
.diag-detail { font-size: 12px; color: #6b7280; line-height: 1.5; word-break: break-all; }
.diag-fix { font-size: 11px; color: #dc2626; background: #fff5f5; padding: 6px 10px; border-radius: 6px; margin-top: 6px; border-left: 3px solid #fca5a5; }
.diag-fix i { margin-right: 4px; }
.diag-back { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; color: #6b7280; text-decoration: none; font-size: 13px; font-weight: 600; transition: all .15s; }
.diag-back:hover { border-color: #6366f1; color: #6366f1; }
.diag-section-title { padding: 10px 18px; background: #f9fafb; font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .06em; border-bottom: 1px solid #e5e7eb; }
</style>

<div class="diag-wrap">
    <div class="diag-header">
        <h2><i class="fas fa-stethoscope"></i> Diagnostic Module Pages</h2>
        <div class="diag-summary">
            <span class="diag-pill total"><i class="fas fa-list"></i> <?= $total ?> tests</span>
            <span class="diag-pill ok"><i class="fas fa-check"></i> <?= $passed ?> OK</span>
            <?php if ($failed): ?><span class="diag-pill ko"><i class="fas fa-times"></i> <?= $failed ?> KO</span><?php endif; ?>
        </div>
    </div>
    
    <a href="?page=pages" class="diag-back"><i class="fas fa-arrow-left"></i> Retour aux pages</a>
    
    <div style="margin-top:16px">
    <div class="diag-card">
        <?php 
        $sections = [
            0 => 'Base de données',
            4 => 'Fichiers module',
            4 + count($files) => 'API Endpoints',
        ];
        foreach ($results as $i => $r): 
        ?>
        <div class="diag-row">
            <div class="diag-ico <?= $r['ok'] ? 'ok' : 'ko' ?>">
                <i class="fas fa-<?= $r['ok'] ? 'check' : 'times' ?>"></i>
            </div>
            <div class="diag-info">
                <div class="diag-name"><?= htmlspecialchars($r['name']) ?></div>
                <div class="diag-detail"><?= htmlspecialchars($r['detail']) ?></div>
                <?php if (!$r['ok'] && $r['fix']): ?>
                <div class="diag-fix"><i class="fas fa-wrench"></i> <?= htmlspecialchars($r['fix']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    </div>
    
    <div style="margin-top:20px;padding:16px;background:#f0f4ff;border:1px solid #c7d2fe;border-radius:12px;font-size:12px;color:#4338ca;line-height:1.6">
        <strong><i class="fas fa-info-circle"></i> Chemin API configuré dans les JS :</strong><br>
        <code style="background:#e0e7ff;padding:2px 6px;border-radius:4px"><?= $apiBase ?></code><br>
        <strong>URL résolue :</strong> <code style="background:#e0e7ff;padding:2px 6px;border-radius:4px"><?= (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $apiBase ?></code>
    </div>
</div>