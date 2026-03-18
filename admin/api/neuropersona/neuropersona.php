<?php
/**
 * ÉCOSYSTÈME IMMO LOCAL+ — API NeuroPersona v2
 * admin/api/neuropersona/neuropersona.php
 * Gère : fiches AI + CRUD campagnes + génération wizard
 */

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'error'=>'Non authentifié']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'error'=>'POST uniquement']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// Config
$configPaths = [
    dirname(__DIR__, 3) . '/config.php',
    dirname(__DIR__, 2) . '/config.php',
    dirname(__DIR__, 1) . '/config.php',
];
foreach ($configPaths as $path) {
    if (file_exists($path)) { require_once $path; break; }
}

// DB
$pdo = null;
if (function_exists('getDB')) {
    $pdo = getDB();
} elseif (isset($GLOBALS['pdo'])) {
    $pdo = $GLOBALS['pdo'];
}

// API Key
$anthropicKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : (getenv('ANTHROPIC_API_KEY') ?: '');

// Input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'generate_fiche';

// ══════════════════════════════════════════════
// ROUTING
// ══════════════════════════════════════════════

switch ($action) {

    // ── Fiche AI (ancien comportement) ──
    case 'generate_fiche':
        handleGenerateFiche($input, $anthropicKey);
        break;

    // ── Créer campagne ──
    case 'create_campaign':
        handleCreateCampaign($input, $pdo);
        break;

    // ── Mettre à jour progression ──
    case 'update_progress':
        handleUpdateProgress($input, $pdo);
        break;

    // ── Générer une étape ──
    case 'generate_step':
        handleGenerateStep($input, $pdo, $anthropicKey);
        break;

    // ── Finaliser ──
    case 'finalize':
        handleFinalize($input, $pdo);
        break;

    default:
        echo json_encode(['success'=>false,'error'=>'Action inconnue: '.$action]);
}


// ══════════════════════════════════════════════
// HANDLERS
// ══════════════════════════════════════════════

function handleGenerateFiche($input, $apiKey) {
    if (empty($apiKey)) {
        echo json_encode(['success'=>false,'error'=>'Clé API Anthropic non configurée']);
        return;
    }
    $prompt = trim($input['prompt'] ?? '');
    if (empty($prompt)) {
        echo json_encode(['success'=>false,'error'=>'Prompt manquant']);
        return;
    }

    $personaId = intval($input['persona_id'] ?? 0);
    $cacheDir = dirname(__DIR__, 2) . '/cache/neuropersona';
    $cacheFile = $cacheDir . '/persona_' . $personaId . '.html';

    if ($personaId > 0 && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 86400) {
        echo json_encode(['success'=>true,'html'=>file_get_contents($cacheFile),'cached'=>true]);
        return;
    }

    $html = callAnthropic($apiKey, $prompt, 4096);
    if ($html === false) {
        echo json_encode(['success'=>false,'error'=>'Erreur API Anthropic']);
        return;
    }

    if ($personaId > 0) {
        if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
        file_put_contents($cacheFile, $html);
    }

    echo json_encode(['success'=>true,'html'=>$html,'cached'=>false]);
}


function handleCreateCampaign($input, $pdo) {
    if (!$pdo) {
        echo json_encode(['success'=>false,'error'=>'Base de données non disponible']);
        return;
    }

    $personaId = intval($input['persona_id'] ?? 0);
    if ($personaId < 1 || $personaId > 30) {
        echo json_encode(['success'=>false,'error'=>'Persona ID invalide']);
        return;
    }

    // Check if already exists
    $stmt = $pdo->prepare("SELECT id FROM np_campaigns WHERE persona_id = ?");
    $stmt->execute([$personaId]);
    $existing = $stmt->fetch();
    if ($existing) {
        echo json_encode(['success'=>true,'campaign_id'=>(int)$existing['id'],'existing'=>true]);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO np_campaigns (persona_id, persona_name, persona_family, motivation_1, motivation_2, conscience, current_step, steps_completed, status) VALUES (?,?,?,?,?,?,1,'[1]','in_progress')");
    $stmt->execute([
        $personaId,
        $input['persona_name'] ?? '',
        $input['persona_family'] ?? '',
        $input['motivation_1'] ?? '',
        $input['motivation_2'] ?? '',
        intval($input['conscience'] ?? 1),
    ]);

    echo json_encode(['success'=>true,'campaign_id'=>(int)$pdo->lastInsertId()]);
}


function handleUpdateProgress($input, $pdo) {
    if (!$pdo) { echo json_encode(['success'=>false]); return; }
    $id = intval($input['campaign_id'] ?? 0);
    if ($id < 1) { echo json_encode(['success'=>false]); return; }

    $stmt = $pdo->prepare("UPDATE np_campaigns SET current_step = ?, steps_completed = ?, status = 'in_progress' WHERE id = ?");
    $stmt->execute([
        intval($input['current_step'] ?? 1),
        $input['steps_completed'] ?? '[]',
        $id
    ]);
    echo json_encode(['success'=>true]);
}


function handleGenerateStep($input, $pdo, $apiKey) {
    if (empty($apiKey)) {
        echo json_encode(['success'=>false,'error'=>'Clé API non configurée']);
        return;
    }

    $step = intval($input['step'] ?? 0);
    $persona = $input['persona'] ?? null;
    $secteurs = $input['secteurs'] ?? [];
    $campaignId = intval($input['campaign_id'] ?? 0);

    if (!$persona || $step < 2 || $step > 7) {
        echo json_encode(['success'=>false,'error'=>'Paramètres invalides']);
        return;
    }

    $conscienceLabels = ['','Non conscient','Conscient du problème','Cherche activement','Compare les solutions','Prêt à agir'];
    $pName = $persona['name'];
    $pM1 = $persona['m1'];
    $pM2 = $persona['m2'];
    $pConsc = $persona['conscience'];
    $pDesc = $persona['desc'];
    $pAge = $persona['age'];
    $secteursList = !empty($secteurs) ? implode(', ', array_column($secteurs, 'name')) : 'Non configurés';
    $nbSecteurs = max(count($secteurs), 1);

    // Build prompt based on step
    $prompt = '';
    $maxTokens = 4096;

    switch ($step) {
        case 2: // Offre
            $prompt = "Tu es un expert en neuromarketing immobilier. Génère une offre complète pour le persona suivant. Réponds en HTML structuré (h3, p, ul, li, strong, em).

PERSONA: {$pName}
ÂGE: {$pAge} ans
DESCRIPTION: {$pDesc}
MOTIVATION PRIMAIRE: {$pM1}
MOTIVATION SECONDAIRE: {$pM2}
CONSCIENCE: {$pConsc}/5 — {$conscienceLabels[$pConsc]}

Structure :
1. **Titre de l'offre** — accroche qui parle directement à ce persona
2. **Promesse principale** — le bénéfice n°1 qu'il recherche
3. **3 preuves** — éléments de crédibilité adaptés à sa motivation
4. **Détail de l'offre** — ce qu'il obtient concrètement
5. **CTA** — appel à l'action formulé selon sa motivation ({$pM1})
6. **Réponses aux 3 objections principales** — basées sur ses peurs

Sois concret et actionnable. HTML uniquement, pas de markdown.";
            break;

        case 3: // 5 contenus
            $prompt = "Tu es un expert en content marketing immobilier basé sur les neurosciences. Génère 5 contenus (titre + plan détaillé + extrait SEO) pour le persona suivant, UN par niveau de conscience de Schwartz.

PERSONA: {$pName} ({$pAge} ans)
DESCRIPTION: {$pDesc}
MOTIVATIONS: {$pM1} + {$pM2}

Pour chaque niveau (1 à 5), génère :
- Un titre d'article SEO-friendly
- Un meta description (150 chars)
- Un plan en 5 sections avec sous-titres
- 3 mots-clés cibles
- Le type de CTA adapté au niveau

NIVEAU 1 (Non conscient) — Contenu éducatif large
NIVEAU 2 (Conscient du problème) — Guide pratique ciblé
NIVEAU 3 (Cherche activement) — Comparatif / étude de cas
NIVEAU 4 (Compare les solutions) — Différenciation / preuve
NIVEAU 5 (Prêt à agir) — Page de conversion directe

Réponds en HTML (h3, h4, p, ul, li, strong, em). Pas de markdown.";
            $maxTokens = 6000;
            break;

        case 4: // Localiser
            $prompt = "Tu es un expert en SEO local immobilier. Pour chaque secteur géographique ci-dessous, génère les adaptations nécessaires pour localiser les 5 contenus du persona.

PERSONA: {$pName}
SECTEURS: {$secteursList}
NOMBRE: {$nbSecteurs} secteurs × 5 niveaux = " . ($nbSecteurs * 5) . " contenus localisés

Pour chaque secteur, génère :
1. Titre localisé (avec nom de ville/quartier)
2. Hook d'introduction locale (2 phrases)
3. 3 mots-clés locaux
4. 1 statistique locale à insérer
5. Nom de quartier/rue à mentionner

Génère un tableau HTML récapitulatif puis le détail par secteur. HTML uniquement.";
            $maxTokens = 6000;
            break;

        case 5: // Séquences email
            $prompt = "Tu es un expert en email marketing automation immobilier. Génère 5 séquences email pour le persona suivant (une par niveau de conscience de Schwartz).

PERSONA: {$pName} ({$pAge} ans)
MOTIVATIONS: {$pM1} + {$pM2}
CONSCIENCE: {$pConsc}/5

Pour chaque séquence (niveau 1 à 5), génère :
- Nom de la séquence
- Événement déclencheur
- 3 à 5 emails avec : objet, aperçu du contenu (2-3 lignes), délai entre chaque
- Le CTA final

Adapte le ton et l'urgence au niveau de conscience :
- Niveau 1 : éducatif, doux, aucune pression
- Niveau 5 : direct, orienté action, offre limitée

Réponds en HTML structuré. Pas de markdown.";
            $maxTokens = 6000;
            break;

        case 6: // Formulaire
            $prompt = "Tu es un expert en conversion et capture de leads immobilier. Génère un formulaire de capture optimisé pour le persona suivant.

PERSONA: {$pName} ({$pAge} ans)
DESCRIPTION: {$pDesc}
MOTIVATIONS: {$pM1} + {$pM2}
CONSCIENCE: {$pConsc}/5

Génère :
1. **Headline** — accroche principale du formulaire (max 10 mots)
2. **Sous-titre** — 1 phrase de renfort
3. **Champs** — liste des champs nécessaires (prénom, email, téléphone, + 1-2 champs spécifiques au persona)
4. **CTA** — texte du bouton (adapté à la motivation {$pM1})
5. **Lead magnet** — ce qu'il reçoit en échange (guide, estimation, checklist...)
6. **Message de remerciement** — adapté au persona
7. **Éléments de réassurance** — 3 badges/textes sous le formulaire

Réponds en HTML. Pas de markdown.";
            break;

        case 7: // Récapitulatif
            $prompt = "Tu es un stratège marketing immobilier. Génère un récapitulatif exécutif de la campagne complète pour le persona suivant.

PERSONA: {$pName} (#" . ($persona['id'] ?? '') . ")
FAMILLE: {$persona['family']}
ÂGE: {$pAge} ans
MOTIVATIONS: {$pM1} + {$pM2}
CONSCIENCE: {$pConsc}/5
SECTEURS: {$secteursList} ({$nbSecteurs})

Génère un dashboard récapitulatif en HTML avec :
1. **Vue d'ensemble** — persona, objectif de la campagne
2. **Offre** — résumé en 3 lignes
3. **Contenus** — tableau des 5 niveaux × {$nbSecteurs} secteurs avec titres
4. **Séquences email** — 5 séquences listées avec nb d'emails
5. **Formulaire** — headline + CTA
6. **KPIs cibles** — objectifs de taux de conversion estimés
7. **Planning de déploiement** — ordre de mise en ligne recommandé

Utilise des tableaux HTML pour les récapitulatifs. Pas de markdown.";
            $maxTokens = 6000;
            break;
    }

    $html = callAnthropic($apiKey, $prompt, $maxTokens);
    if ($html === false) {
        echo json_encode(['success'=>false,'error'=>'Erreur API Anthropic']);
        return;
    }

    // Save to DB if possible
    if ($pdo && $campaignId > 0) {
        saveStepData($pdo, $campaignId, $step, $html, $persona, $secteurs);
    }

    echo json_encode(['success'=>true,'html'=>$html,'step'=>$step]);
}


function handleFinalize($input, $pdo) {
    if (!$pdo) { echo json_encode(['success'=>false]); return; }
    $id = intval($input['campaign_id'] ?? 0);
    $stmt = $pdo->prepare("UPDATE np_campaigns SET status = 'complete', current_step = 7 WHERE id = ?");
    $stmt->execute([$id]);
    echo json_encode(['success'=>true]);
}


// ══════════════════════════════════════════════
// SAVE STEP DATA
// ══════════════════════════════════════════════
function saveStepData($pdo, $campaignId, $step, $html, $persona, $secteurs) {
    try {
        switch ($step) {
            case 2: // Offre
                $stmt = $pdo->prepare("INSERT INTO np_offers (campaign_id, title, full_html, ai_generated) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE full_html = VALUES(full_html), updated_at = NOW()");
                $stmt->execute([$campaignId, 'Offre — ' . ($persona['name'] ?? ''), $html]);
                break;

            case 3: // Contenus (5 niveaux)
                for ($level = 1; $level <= 5; $level++) {
                    $stmt = $pdo->prepare("INSERT INTO np_contents (campaign_id, conscience_level, content_type, title, content_html, ai_generated) VALUES (?, ?, 'article', ?, ?, 1) ON DUPLICATE KEY UPDATE content_html = VALUES(content_html), updated_at = NOW()");
                    $stmt->execute([
                        $campaignId,
                        $level,
                        "Contenu N{$level} — " . ($persona['name'] ?? ''),
                        $html // For now, store full response; later parse per-level
                    ]);
                }
                break;

            case 5: // Séquences
                for ($level = 1; $level <= 5; $level++) {
                    $stmt = $pdo->prepare("INSERT INTO np_sequences (campaign_id, conscience_level, sequence_name, emails_json, ai_generated) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE emails_json = VALUES(emails_json), updated_at = NOW()");
                    $stmt->execute([
                        $campaignId,
                        $level,
                        "Séquence N{$level} — " . ($persona['name'] ?? ''),
                        $html
                    ]);
                }
                break;

            case 6: // Formulaire
                $stmt = $pdo->prepare("INSERT INTO np_forms (campaign_id, form_name, form_html, ai_generated) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE form_html = VALUES(form_html), updated_at = NOW()");
                $stmt->execute([$campaignId, 'Form — ' . ($persona['name'] ?? ''), $html]);
                break;
        }
    } catch (Throwable $e) {
        error_log('[NP API] Save error step ' . $step . ': ' . $e->getMessage());
    }
}


// ══════════════════════════════════════════════
// ANTHROPIC API CALL
// ══════════════════════════════════════════════
function callAnthropic($apiKey, $prompt, $maxTokens = 4096) {
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
            'messages' => [['role'=>'user','content'=>$prompt]],
        ]),
        CURLOPT_TIMEOUT => 90,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err || $httpCode !== 200) {
        error_log("[NP API] Anthropic error: HTTP {$httpCode} / {$err}");
        return false;
    }

    $data = json_decode($response, true);
    $html = $data['content'][0]['text'] ?? '';

    // Clean markdown fences
    $html = preg_replace('/^```html?\s*/i', '', $html);
    $html = preg_replace('/\s*```$/', '', $html);

    $allowedTags = '<h1><h2><h3><h4><h5><p><ul><ol><li><strong><em><b><i><br><span><div><table><tr><td><th><thead><tbody><a>';
    return strip_tags($html, $allowedTags);
}