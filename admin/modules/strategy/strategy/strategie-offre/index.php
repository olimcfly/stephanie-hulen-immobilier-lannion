<?php
/**
 * ══════════════════════════════════════════════════════════════
 * Page 3 : Mon Offre (Méthode ANCRE — Pilier N)
 * /admin/modules/strategy/strategie-offre/index.php
 * ══════════════════════════════════════════════════════════════
 * Reçoit ?persona=X depuis positionnement
 * Charge le contexte positionnement pour enrichir l'IA
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

$db       = getDB();
$instance = INSTANCE_ID;
$etape    = 'offre';

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

// ══════════════════════════════════════════════════════════════
// NeuroPersonas + Familles (même référentiel)
// ══════════════════════════════════════════════════════════════
$neuropersonas = [
    ['id'=>1,'nom'=>'Primo-Accédant Jeune Couple','type'=>'acheteurs','color'=>'#e74c3c','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>2,'desc'=>'CDI récent, locataire'],
    ['id'=>2,'nom'=>'Primo-Accédant Solo','type'=>'acheteurs','color'=>'#e74c3c','m1'=>'Liberté','m2'=>'Sécurité','conscience'=>2,'desc'=>'Célibataire, budget serré'],
    ['id'=>3,'nom'=>'Famille en Expansion','type'=>'acheteurs','color'=>'#e74c3c','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>3,'desc'=>'Veut jardin'],
    ['id'=>4,'nom'=>'Muté Professionnel','type'=>'acheteurs','color'=>'#e74c3c','m1'=>'Contrôle','m2'=>'Sécurité','conscience'=>3,'desc'=>'Urgence mutation'],
    ['id'=>5,'nom'=>'Retraité Actif','type'=>'acheteurs','color'=>'#e74c3c','m1'=>'Liberté','m2'=>'Sécurité','conscience'=>3,'desc'=>'Downsizer'],
    ['id'=>6,'nom'=>'Expatrié de Retour','type'=>'acheteurs','color'=>'#e74c3c','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>2,'desc'=>'Marché inconnu'],
    ['id'=>7,'nom'=>'Divorcé en Reconstruction','type'=>'acheteurs','color'=>'#e74c3c','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>2,'desc'=>'Fragile'],
    ['id'=>8,'nom'=>'Acheteur Résidence Secondaire','type'=>'acheteurs','color'=>'#e74c3c','m1'=>'Reconnaissance','m2'=>'Liberté','conscience'=>4,'desc'=>'Plaisir'],
    ['id'=>9,'nom'=>'Senior Simplificateur','type'=>'vendeurs','color'=>'#d4880f','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>2,'desc'=>'Peur du changement'],
    ['id'=>10,'nom'=>'Héritier — Succession','type'=>'vendeurs','color'=>'#d4880f','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>3,'desc'=>'Vendre vite'],
    ['id'=>11,'nom'=>'Vendeur Divorce','type'=>'vendeurs','color'=>'#d4880f','m1'=>'Contrôle','m2'=>'Sécurité','conscience'=>3,'desc'=>'Neutralité'],
    ['id'=>12,'nom'=>'Muté — Vente Urgente','type'=>'vendeurs','color'=>'#d4880f','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>3,'desc'=>'Deadline'],
    ['id'=>13,'nom'=>'Monte en Gamme','type'=>'vendeurs','color'=>'#d4880f','m1'=>'Reconnaissance','m2'=>'Contrôle','conscience'=>4,'desc'=>'Crédit-relais'],
    ['id'=>14,'nom'=>'Expatrié Vente Distance','type'=>'vendeurs','color'=>'#d4880f','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>3,'desc'=>'Procuration'],
    ['id'=>15,'nom'=>'Investisseur Revend','type'=>'vendeurs','color'=>'#d4880f','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>4,'desc'=>'Plus-value'],
    ['id'=>16,'nom'=>'Vendeur Première Fois','type'=>'vendeurs','color'=>'#d4880f','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>1,'desc'=>'Peur arnaque'],
    ['id'=>17,'nom'=>'Locatif Rentabilité','type'=>'investisseurs','color'=>'#8b5cf6','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>4,'desc'=>'Chiffres'],
    ['id'=>18,'nom'=>'Défiscalisation','type'=>'investisseurs','color'=>'#8b5cf6','m1'=>'Contrôle','m2'=>'Sécurité','conscience'=>3,'desc'=>'TMI élevée'],
    ['id'=>19,'nom'=>'Colocation Étudiant','type'=>'investisseurs','color'=>'#8b5cf6','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>4,'desc'=>'Multi-locataires'],
    ['id'=>20,'nom'=>'Airbnb','type'=>'investisseurs','color'=>'#8b5cf6','m1'=>'Liberté','m2'=>'Reconnaissance','conscience'=>4,'desc'=>'Touristique'],
    ['id'=>21,'nom'=>'Immeuble de Rapport','type'=>'investisseurs','color'=>'#8b5cf6','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>5,'desc'=>'En bloc'],
    ['id'=>22,'nom'=>'Primo-Investisseur','type'=>'investisseurs','color'=>'#8b5cf6','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>2,'desc'=>'Prudent'],
    ['id'=>23,'nom'=>'Prépare Retraite','type'=>'investisseurs','color'=>'#8b5cf6','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>3,'desc'=>'10-15 ans'],
    ['id'=>24,'nom'=>'Nouveau Résident','type'=>'niches','color'=>'#10b981','m1'=>'Liberté','m2'=>'Sécurité','conscience'=>2,'desc'=>'Télétravail'],
    ['id'=>25,'nom'=>'Bailleur Difficulté','type'=>'niches','color'=>'#10b981','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>3,'desc'=>'Impayés DPE'],
    ['id'=>26,'nom'=>'Propriétaire DPE F/G','type'=>'niches','color'=>'#10b981','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>2,'desc'=>'Interdit 2025'],
    ['id'=>27,'nom'=>'Professionnel Libéral','type'=>'niches','color'=>'#10b981','m1'=>'Reconnaissance','m2'=>'Contrôle','conscience'=>4,'desc'=>'SCI'],
    ['id'=>28,'nom'=>'Vendeur Viager','type'=>'niches','color'=>'#10b981','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>2,'desc'=>'Rester chez soi'],
    ['id'=>29,'nom'=>'Luxe / Prestige','type'=>'niches','color'=>'#10b981','m1'=>'Reconnaissance','m2'=>'Contrôle','conscience'=>5,'desc'=>'500K+'],
    ['id'=>30,'nom'=>'Marchand de Biens','type'=>'niches','color'=>'#10b981','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>5,'desc'=>'Volume'],
];
$personas = $neuropersonas;
$familyMeta = [
    'acheteurs'=>['icon'=>'🏠','label'=>'Acheteurs RP','color'=>'#e74c3c'],
    'vendeurs'=>['icon'=>'🔑','label'=>'Vendeurs','color'=>'#d4880f'],
    'investisseurs'=>['icon'=>'📈','label'=>'Investisseurs','color'=>'#8b5cf6'],
    'niches'=>['icon'=>'🎯','label'=>'Niches','color'=>'#10b981'],
];
$cLabels = ['','Non conscient','Conscient du problème','Cherche activement','Compare les solutions','Prêt à agir'];

$persona_id = isset($_GET['persona']) && is_numeric($_GET['persona']) ? (int)$_GET['persona'] : 1;
$current_persona = null;
foreach ($personas as $p) { if ((int)$p['id'] === $persona_id) { $current_persona = $p; break; } }
if (!$current_persona) $current_persona = $personas[0];

// ══════════════════════════════════════════════════════════════
// Charger advisor_context → pré-remplir
// ══════════════════════════════════════════════════════════════
$advisorData = [];
try {
    $stmt = $db->query("SELECT field_key, field_value FROM advisor_context");
    $advisorData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
} catch (Exception $e) {}

// Pré-remplissage adapté aux questions "Offre"
$prefill = [
    'q1' => '', // offre principale
    'q2' => '', // problème résolu
    'q3' => '', // solution unique
    'q4' => '', // résultat promis
    'q5' => '', // preuve sociale
];

// Q1 Offre principale : advisor_speciality + advisor_network
$q1Parts = [];
if (!empty($advisorData['advisor_speciality'])) $q1Parts[] = $advisorData['advisor_speciality'];
if (!empty($advisorData['advisor_network'])) $q1Parts[] = 'Conseiller(ère) ' . $advisorData['advisor_network'];
if (!empty($advisorData['advisor_style'])) $q1Parts[] = $advisorData['advisor_style'];
$prefill['q1'] = implode('. ', array_filter($q1Parts));

// Q3 Solution unique : advisor_style + advisor_experience
$q3Parts = [];
if (!empty($advisorData['advisor_experience'])) $q3Parts[] = $advisorData['advisor_experience'] . ' d\'expérience';
if (!empty($advisorData['advisor_certifications'])) $q3Parts[] = 'Certifications : ' . $advisorData['advisor_certifications'];
$prefill['q3'] = implode('. ', array_filter($q3Parts));

// Q4 Résultat promis : advisor_promise
if (!empty($advisorData['advisor_promise'])) $prefill['q4'] = $advisorData['advisor_promise'];
elseif (!empty($advisorData['signature'])) $prefill['q4'] = $advisorData['signature'];

// Q5 Preuve sociale : advisor_reviews + advisor_stats
$q5Parts = [];
if (!empty($advisorData['advisor_reviews'])) $q5Parts[] = $advisorData['advisor_reviews'];
if (!empty($advisorData['advisor_stats'])) $q5Parts[] = $advisorData['advisor_stats'];
$prefill['q5'] = implode('. ', array_filter($q5Parts));

// ══════════════════════════════════════════════════════════════
// Questions guidées — Offre
// ══════════════════════════════════════════════════════════════
$questions = [
    'q1' => ['label'=>'Votre offre principale','question'=>'Quel service proposez-vous concrètement à ce persona ?','placeholder'=>'Ex : Accompagnement clé-en-main pour primo-accédants, de la recherche au notaire…','aide'=>'Décrivez votre service principal pour CE persona. Pas votre offre générique — ce que vous faites spécifiquement pour ce profil client.','icon'=>'fa-gift','color'=>'#6366f1'],
    'q2' => ['label'=>'Le problème résolu','question'=>'Quel problème spécifique ce persona essaie-t-il de résoudre ?','placeholder'=>'Ex : Peur de se tromper sur le prix, de rater la bonne affaire, d\'être arnaqué…','aide'=>'Formulez le problème avec les MOTS du client. Ce n\'est pas "vendre un bien", c\'est "sortir d\'une situation bloquée" ou "ne pas perdre d\'argent".','icon'=>'fa-bullseye','color'=>'#ef4444'],
    'q3' => ['label'=>'Votre solution unique','question'=>'En quoi votre approche est-elle différente pour résoudre ce problème ?','placeholder'=>'Ex : Méthode en 5 étapes avec estimation certifiée, visite virtuelle 3D, garantie de vente…','aide'=>'Votre méthode, votre processus, vos outils. Ce qui fait que vous résolvez ce problème MIEUX que les autres.','icon'=>'fa-lightbulb','color'=>'#f59e0b'],
    'q4' => ['label'=>'Le résultat promis','question'=>'Quel résultat concret le client peut-il attendre ?','placeholder'=>'Ex : Vente au prix estimé en moins de 60 jours, sans baisse de prix…','aide'=>'Chiffrez quand c\'est possible : délai, prix obtenu, nombre de visites, satisfaction. Le résultat doit être mesurable et vérifiable.','icon'=>'fa-chart-line','color'=>'#10b981'],
    'q5' => ['label'=>'La preuve sociale','question'=>'Quelle histoire de client similaire pouvez-vous raconter ?','placeholder'=>'Ex : Marie et Pierre, primo Lannion, trouvé en 3 semaines, 15K€ sous le marché…','aide'=>'Un témoignage ou une histoire vraie avec ce persona. Nom (ou prénom), situation, résultat obtenu. Le storytelling crédibilise votre offre.','icon'=>'fa-quote-left','color'=>'#c9913b'],
];

// ══════════════════════════════════════════════════════════════
// Charger notes sauvegardées (étape offre)
// ══════════════════════════════════════════════════════════════
$notes_json = []; $chat_hist = []; $resume_ia = ''; $completion = 0;
try {
    $stmt = $db->prepare("SELECT notes_json, chat_history, resume_ia, completion FROM strategy_notes WHERE instance_id=:i AND (persona_id=:p OR (persona_id IS NULL AND :p2 IS NULL)) AND etape=:e LIMIT 1");
    $stmt->execute([':i'=>$instance,':p'=>$persona_id,':p2'=>$persona_id,':e'=>$etape]);
    $note = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($note) {
        $notes_json = json_decode($note['notes_json'] ?? '{}', true) ?: [];
        $chat_hist  = json_decode($note['chat_history'] ?? '[]', true) ?: [];
        $resume_ia  = $note['resume_ia'] ?? '';
        $completion = (int)($note['completion'] ?? 0);
    }
} catch (Exception $e) {}

// Merge prefill si pas de sauvegarde
foreach ($prefill as $qid => $val) {
    if (empty($notes_json[$qid]) && !empty($val)) {
        $notes_json[$qid] = $val;
    }
}

// ══════════════════════════════════════════════════════════════
// Charger le contexte de l'étape précédente (positionnement)
// ══════════════════════════════════════════════════════════════
$positionContext = '';
try {
    $stmt = $db->prepare("SELECT notes_json, resume_ia FROM strategy_notes WHERE instance_id=:i AND persona_id=:p AND etape='positionnement' LIMIT 1");
    $stmt->execute([':i'=>$instance,':p'=>$persona_id]);
    $posNote = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($posNote) {
        $posNotes = json_decode($posNote['notes_json'] ?? '{}', true) ?: [];
        $posLabels = ['q1'=>'Territoire','q2'=>'Différence','q3'=>'Promesse','q4'=>'Preuves','q5'=>'Anti-positionnement'];
        $parts = [];
        foreach ($posLabels as $k => $l) {
            if (!empty($posNotes[$k])) $parts[] = "$l: {$posNotes[$k]}";
        }
        if ($parts) $positionContext = "POSITIONNEMENT (étape précédente):\n" . implode("\n", $parts);
        if (!empty($posNote['resume_ia'])) $positionContext .= "\n\nRésumé positionnement: " . $posNote['resume_ia'];
    }
} catch (Exception $e) {}

// ══════════════════════════════════════════════════════════════
// API key
// ══════════════════════════════════════════════════════════════
if (!function_exists('getAnthropicKey')) {
    function getAnthropicKey(PDO $db): string {
        try{$s=$db->prepare("SELECT api_key_encrypted FROM api_keys WHERE service_key='claude' LIMIT 1");$s->execute();$e=$s->fetchColumn();if($e){$k=defined('APP_KEY')?APP_KEY:(defined('SECRET_KEY')?SECRET_KEY:'immolocal_aes_key_2024_secure!!');$r=base64_decode($e);if(strlen($r)>=17){$d=openssl_decrypt(substr($r,16),'aes-256-cbc',$k,0,substr($r,0,16));if($d)return $d;}}}catch(Exception $e){}
        try{$s=$db->prepare("SELECT setting_value FROM settings WHERE setting_key='anthropic_api_key' LIMIT 1");$s->execute();$v=$s->fetchColumn();if($v)return $v;}catch(Exception $e){}
        try{$s=$db->prepare("SELECT setting_value FROM ai_settings WHERE setting_key='anthropic_api_key' LIMIT 1");$s->execute();$v=$s->fetchColumn();if($v)return $v;}catch(Exception $e){}
        if(defined('ANTHROPIC_API_KEY')&&ANTHROPIC_API_KEY)return ANTHROPIC_API_KEY; return '';
    }
}
$has_api_key = !empty(getAnthropicKey($db));

$persona_context = "Persona: {$current_persona['nom']} ({$current_persona['type']})\nMotivations: {$current_persona['m1']} + {$current_persona['m2']}\nConscience: {$current_persona['conscience']}/5 — " . ($cLabels[$current_persona['conscience']] ?? '');

// ══════════════════════════════════════════════════════════════
// Étapes navigation ANCRE
// ══════════════════════════════════════════════════════════════
$ancreSteps = [
    ['slug'=>'strategie-positionnement','label'=>'Positionnement','icon'=>'fa-anchor','letter'=>'A'],
    ['slug'=>'strategie-offre','label'=>'Offre','icon'=>'fa-gift','letter'=>'N'],
    ['slug'=>'strategie-contenu','label'=>'Contenu','icon'=>'fa-pen-nib','letter'=>'C'],
    ['slug'=>'strategie-trafic','label'=>'Trafic','icon'=>'fa-bullhorn','letter'=>'R'],
    ['slug'=>'strategie-conversion','label'=>'Conversion','icon'=>'fa-funnel-dollar','letter'=>'E'],
    ['slug'=>'strategie-optimisation','label'=>'Optimisation','icon'=>'fa-chart-line','letter'=>'+'],
];
$currentStepIdx = 1; // offre = index 1

// Vérifier quelles étapes ont des données
$stepsWithData = [];
try {
    $stmt = $db->prepare("SELECT etape, completion FROM strategy_notes WHERE instance_id=:i AND persona_id=:p");
    $stmt->execute([':i'=>$instance,':p'=>$persona_id]);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stepsWithData[$r['etape']] = (int)$r['completion'];
    }
} catch (Exception $e) {}
$etapeSlugMap = ['positionnement'=>0,'offre'=>1,'contenu'=>2,'trafic'=>3,'conversion'=>4,'optimisation'=>5];

// ══════════════════════════════════════════════════════════════
// AJAX handlers
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');
    if (($_POST['csrf']??'') !== $csrf) { echo json_encode(['success'=>false,'error'=>'CSRF']); exit; }
    $action = $_POST['action'];
    $pid = isset($_POST['persona_id']) && is_numeric($_POST['persona_id']) ? (int)$_POST['persona_id'] : null;

    if ($action === 'save-notes') {
        $nn = json_decode($_POST['notes']??'{}',true)?:[];
        $f = count(array_filter($nn, fn($v)=>trim((string)$v)!==''));
        $c = (int)round(($f/count($questions))*100);
        $stmt = $db->prepare("INSERT INTO strategy_notes (instance_id,persona_id,etape,notes_json,completion,updated_at) VALUES(:i,:p,:e,:n,:c,NOW()) ON DUPLICATE KEY UPDATE notes_json=VALUES(notes_json),completion=VALUES(completion),updated_at=NOW()");
        $stmt->execute([':i'=>$instance,':p'=>$pid,':e'=>$etape,':n'=>json_encode($nn,JSON_UNESCAPED_UNICODE),':c'=>$c]);
        echo json_encode(['success'=>true,'completion'=>$c]); exit;
    }
    if ($action === 'save-chat') {
        $h = json_decode($_POST['history']??'[]',true)?:[];
        $stmt = $db->prepare("INSERT INTO strategy_notes (instance_id,persona_id,etape,chat_history,updated_at) VALUES(:i,:p,:e,:h,NOW()) ON DUPLICATE KEY UPDATE chat_history=VALUES(chat_history),updated_at=NOW()");
        $stmt->execute([':i'=>$instance,':p'=>$pid,':e'=>$etape,':h'=>json_encode($h,JSON_UNESCAPED_UNICODE)]);
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'save-resume') {
        $stmt = $db->prepare("INSERT INTO strategy_notes (instance_id,persona_id,etape,resume_ia,updated_at) VALUES(:i,:p,:e,:r,NOW()) ON DUPLICATE KEY UPDATE resume_ia=VALUES(resume_ia),updated_at=NOW()");
        $stmt->execute([':i'=>$instance,':p'=>$pid,':e'=>$etape,':r'=>trim($_POST['resume']??'')]);
        echo json_encode(['success'=>true]); exit;
    }
    if ($action === 'ai-proxy') {
        $prompt = trim($_POST['prompt']??'');
        $messages = json_decode($_POST['messages']??'null',true);
        $system = trim($_POST['system']??'');
        if (!$prompt && empty($messages)) { echo json_encode(['success'=>false,'error'=>'Prompt vide']); exit; }
        $apiKey = getAnthropicKey($db);
        if (!$apiKey) { echo json_encode(['success'=>false,'error'=>'Clé API non configurée']); exit; }
        if (!$messages) $messages = [['role'=>'user','content'=>$prompt]];
        $payload = ['model'=>'claude-sonnet-4-20250514','max_tokens'=>1500,'messages'=>$messages];
        if ($system) $payload['system'] = $system;
        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_TIMEOUT=>45,CURLOPT_HTTPHEADER=>['Content-Type: application/json','x-api-key: '.$apiKey,'anthropic-version: 2023-06-01'],CURLOPT_POSTFIELDS=>json_encode($payload)]);
        $r=curl_exec($ch);curl_close($ch);
        $d=json_decode($r,true);$t=$d['content'][0]['text']??'';
        echo json_encode($t?['success'=>true,'text'=>$t]:['success'=>false,'error'=>'Réponse vide']); exit;
    }
    echo json_encode(['success'=>false]); exit;
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500;700&display=swap');
.off{--a:#6366f1;--gold:#c9913b;font-family:'DM Sans',sans-serif;max-width:1080px;margin:0 auto;padding:24px 24px 60px}
.off-hd{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap}
.off-eye{font-size:.65rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;display:flex;align-items:center;gap:6px}
.off-h1{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:#111827;margin:0 0 4px}
.off-sub{font-size:.78rem;color:#6b7280}
/* Navigation ANCRE */
.off-nav{display:flex;gap:4px;margin-bottom:16px;flex-wrap:wrap}
.off-nav-step{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:.72rem;font-weight:600;text-decoration:none;border:1.5px solid #e5e7eb;color:#9ca3af;background:#fff;transition:all .15s}
.off-nav-step.active{border-color:var(--a);background:linear-gradient(135deg,#6366f110,#6366f105);color:var(--a);font-weight:800}
.off-nav-step.done{border-color:#86efac;color:#166534;background:#f0fdf4}
.off-nav-step.done:hover{border-color:#22c55e}
.off-nav-step.locked{opacity:.45;pointer-events:none}
.off-nav-step i{font-size:.6rem}
.off-nav-letter{width:18px;height:18px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.55rem;font-weight:800;color:#fff;background:#d1d5db;flex-shrink:0}
.off-nav-step.active .off-nav-letter{background:var(--a)}
.off-nav-step.done .off-nav-letter{background:#22c55e}
.off-nav-chevron{color:#d1d5db;font-size:.55rem;margin:0 2px}
/* Persona banner */
.off-persona-banner{display:flex;align-items:center;gap:14px;padding:14px 18px;background:linear-gradient(135deg,<?= $current_persona['color'] ?>10,<?= $current_persona['color'] ?>05);border:1.5px solid <?= $current_persona['color'] ?>40;border-radius:12px;margin-bottom:16px}
.off-pb-num{font-size:11px;font-weight:700;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;background:<?= $current_persona['color'] ?>;flex-shrink:0}
.off-pb-info{flex:1}
.off-pb-name{font-size:.9rem;font-weight:700;color:#111827}
.off-pb-meta{font-size:.72rem;color:#6b7280;margin-top:2px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.off-pb-tag{font-size:9px;font-weight:600;padding:1px 6px;border-radius:3px}
.off-pb-dots{display:flex;gap:2px}.off-pb-dot{width:5px;height:5px;border-radius:50%;background:#e5e7eb}.off-pb-dot.on{background:#f59e0b}
.off-pb-back{font-size:.72rem;font-weight:600;color:var(--a);text-decoration:none;display:flex;align-items:center;gap:4px}
.off-pb-back:hover{text-decoration:underline}
/* Context banner positionnement */
.off-ctx{display:flex;align-items:flex-start;gap:8px;padding:10px 14px;background:#f8f9ff;border:1px solid #c7d2fe;border-radius:8px;margin-bottom:14px;font-size:.72rem;color:#4338ca;line-height:1.4}
.off-ctx i{color:#6366f1;margin-top:2px;flex-shrink:0}
.off-ctx-toggle{background:none;border:none;color:#6366f1;font-weight:700;cursor:pointer;font-size:.68rem;text-decoration:underline;padding:0;margin-top:4px}
.off-ctx-detail{display:none;margin-top:6px;padding:8px 10px;background:#fff;border:1px solid #e0e7ff;border-radius:6px;font-size:.7rem;color:#374151;white-space:pre-wrap;line-height:1.5}
.off-ctx-detail.visible{display:block}
/* Prefill notice */
.off-prefill{display:flex;align-items:center;gap:8px;padding:8px 14px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;margin-bottom:14px;font-size:.72rem;color:#166534}
.off-prefill i{color:#22c55e}
/* Layout */
.off-layout{display:grid;grid-template-columns:1fr 400px;gap:16px;align-items:start}
@media(max-width:900px){.off-layout{grid-template-columns:1fr}}
.off-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.06);overflow:hidden}
.off-card-hd{display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid #e5e7eb;background:#f9fafb}
.off-card-hd-ic{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;color:#fff}
.off-card-hd h2{font-family:'Syne',sans-serif;font-size:.85rem;font-weight:800;color:#111827;margin:0;flex:1}
.off-card-hd span{font-size:.65rem;color:#9ca3af}
.off-card-body{padding:18px}
.off-qs{display:flex;flex-direction:column;gap:16px}
.off-q-hdr{display:flex;align-items:center;gap:8px;margin-bottom:4px}
.off-q-num{width:22px;height:22px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;color:#fff;flex-shrink:0}
.off-q-lbl{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af}
.off-q-saved{margin-left:auto;font-size:.6rem;color:#10b981;opacity:0;transition:opacity .3s;display:flex;align-items:center;gap:3px}
.off-q-saved.visible{opacity:1}
.off-q-txt{font-size:.8rem;color:#111827;font-weight:600;margin-bottom:3px;line-height:1.35}
.off-q-aide{font-size:.68rem;color:#9ca3af;line-height:1.4;margin-bottom:6px;padding:5px 9px;background:#f8f9ff;border-radius:5px;border-left:3px solid #c7d2fe}
.off-ta{width:100%;min-height:72px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:9px 11px;font-family:'DM Sans',sans-serif;font-size:.8rem;color:#111827;resize:vertical;outline:none;transition:border-color .15s;box-sizing:border-box}
.off-ta:focus{border-color:var(--a);box-shadow:0 0 0 3px rgba(99,102,241,.08)}
.off-ta.prefilled{border-color:#86efac;background:#f0fdf4}
.off-div{height:1px;background:#e5e7eb}
/* Resume */
.off-resume{width:100%;min-height:120px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;font-family:'DM Sans',sans-serif;font-size:.8rem;color:#111827;resize:vertical;outline:none;box-sizing:border-box;line-height:1.6}
.off-resume:focus{border-color:var(--gold)}
.off-ractions{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
.off-btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:7px;font-size:.75rem;font-weight:700;cursor:pointer;border:none;transition:transform .15s}
.off-btn-gold{background:linear-gradient(135deg,var(--gold),#a0722a);color:#fff}
.off-btn-gold:hover{transform:translateY(-1px)}
.off-btn-ghost{background:#f9fafb;border:1px solid #e5e7eb;color:#6b7280}
.off-btn-ghost:hover{background:#e5e7eb}
.off-spinner{width:12px;height:12px;border-radius:50%;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;animation:spin .7s linear infinite;display:none}
@keyframes spin{to{transform:rotate(360deg)}}
/* Chat */
.off-chat{display:flex;flex-direction:column;height:480px}
.off-chat-msgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:8px;scrollbar-width:thin}
.off-msg{display:flex;gap:6px;align-items:flex-end;animation:msgIn .2s ease}
@keyframes msgIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
.off-msg-av{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;flex-shrink:0}
.off-msg-bub{max-width:85%;padding:9px 12px;border-radius:11px;font-size:.78rem;line-height:1.5}
.off-msg.user{flex-direction:row-reverse}
.off-msg.user .off-msg-av{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff}
.off-msg.user .off-msg-bub{background:linear-gradient(135deg,var(--a),#4f46e5);color:#fff;border-bottom-right-radius:3px}
.off-msg.assistant .off-msg-av{background:linear-gradient(135deg,#c9913b,#a0722a);color:#fff}
.off-msg.assistant .off-msg-bub{background:#f9fafb;border:1px solid #e5e7eb;color:#111827;border-bottom-left-radius:3px}
.off-typing{display:none;align-items:center;gap:3px;padding:9px 12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:11px;width:fit-content}
.off-typing.visible{display:flex}
.off-typing-dot{width:5px;height:5px;border-radius:50%;background:#9ca3af;animation:td 1.2s infinite}
.off-typing-dot:nth-child(2){animation-delay:.2s}.off-typing-dot:nth-child(3){animation-delay:.4s}
@keyframes td{0%,80%,100%{transform:translateY(0);opacity:.4}40%{transform:translateY(-3px);opacity:1}}
.off-chat-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:18px;text-align:center}
.off-chat-empty i{font-size:1.6rem;color:#9ca3af}
.off-chat-empty strong{font-size:.82rem;color:#111827}
.off-chat-empty p{font-size:.72rem;color:#6b7280;line-height:1.4}
.off-sugg{display:flex;flex-wrap:wrap;gap:4px;padding:0 12px 8px}
.off-sugg-btn{padding:4px 10px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:16px;cursor:pointer;font-size:.68rem;color:#6b7280;font-family:inherit;transition:all .13s}
.off-sugg-btn:hover{border-color:var(--a);color:var(--a)}
.off-chat-iw{padding:8px 12px 12px;border-top:1px solid #e5e7eb;display:flex;gap:6px;align-items:flex-end}
.off-chat-in{flex:1;min-height:36px;max-height:100px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:9px;padding:8px 11px;font-family:'DM Sans',sans-serif;font-size:.78rem;color:#111827;outline:none;resize:none;box-sizing:border-box}
.off-chat-in:focus{border-color:var(--a)}
.off-chat-send{width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,var(--a),#4f46e5);border:none;cursor:pointer;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
.off-chat-send:disabled{opacity:.5;cursor:not-allowed}
.off-toast{position:fixed;bottom:24px;right:24px;z-index:9999;background:#111827;color:#fff;padding:10px 16px;border-radius:9px;font-size:.75rem;font-weight:600;display:flex;align-items:center;gap:6px;box-shadow:0 8px 24px rgba(0,0,0,.2);transform:translateY(20px);opacity:0;transition:all .25s;pointer-events:none}
.off-toast.show{transform:none;opacity:1}
.anim{animation:fadeUp .25s ease both}.d1{animation-delay:.05s}.d2{animation-delay:.1s}
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
</style>

<div class="off">
    <div class="off-hd anim">
        <div>
            <div class="off-eye"><i class="fas fa-gift"></i> Méthode ANCRE — Pilier N</div>
            <h1 class="off-h1">Mon Offre</h1>
            <p class="off-sub">Construisez une offre irrésistible pour ce persona : problème, solution, résultat, preuve.</p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:8px 14px;display:flex;align-items:center;gap:10px">
                <div style="flex:1;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;min-width:100px"><div id="cFill" style="height:100%;border-radius:3px;background:linear-gradient(90deg,var(--a),#818cf8);width:<?=$completion?>%;transition:width .5s"></div></div>
                <span id="cPct" style="font-size:.72rem;font-weight:800;color:var(--a)"><?=$completion?>%</span>
            </div>
        </div>
    </div>

    <!-- Navigation ANCRE -->
    <div class="off-nav anim d1">
        <?php foreach ($ancreSteps as $idx => $step):
            $stepEtape = str_replace('strategie-', '', $step['slug']);
            $isDone = isset($stepsWithData[$stepEtape]) && $stepsWithData[$stepEtape] > 0;
            $isActive = $idx === $currentStepIdx;
            $isLocked = !$isActive && !$isDone && $idx > $currentStepIdx;
            $cls = $isActive ? 'active' : ($isDone ? 'done' : ($isLocked ? 'locked' : ''));
        ?>
            <?php if ($idx > 0): ?><span class="off-nav-chevron"><i class="fas fa-chevron-right"></i></span><?php endif; ?>
            <a href="?page=<?=$step['slug']?>&persona=<?=$persona_id?>" class="off-nav-step <?=$cls?>">
                <span class="off-nav-letter"><?=$step['letter']?></span>
                <i class="fas <?=$step['icon']?>"></i> <?=$step['label']?>
                <?php if ($isDone && !$isActive): ?><i class="fas fa-check" style="font-size:.55rem;color:#22c55e"></i><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Persona banner -->
    <div class="off-persona-banner anim d1">
        <span class="off-pb-num"><?= $current_persona['id'] ?></span>
        <div class="off-pb-info">
            <div class="off-pb-name"><?= htmlspecialchars($current_persona['nom']) ?></div>
            <div class="off-pb-meta">
                <?= $familyMeta[$current_persona['type']]['icon'] ?? '' ?> <?= $familyMeta[$current_persona['type']]['label'] ?? '' ?>
                <span>•</span>
                <?php $mc1 = ['Sécurité'=>['c'=>'#1e40af','bg'=>'#dbeafe'],'Liberté'=>['c'=>'#065f46','bg'=>'#d1fae5'],'Reconnaissance'=>['c'=>'#92400e','bg'=>'#fef3c7'],'Contrôle'=>['c'=>'#5b21b6','bg'=>'#ede9fe']]; ?>
                <span class="off-pb-tag" style="background:<?= $mc1[$current_persona['m1']]['bg'] ?? '#eee' ?>;color:<?= $mc1[$current_persona['m1']]['c'] ?? '#666' ?>"><?= $current_persona['m1'] ?></span>
                <span>+</span>
                <span class="off-pb-tag" style="background:<?= $mc1[$current_persona['m2']]['bg'] ?? '#eee' ?>;color:<?= $mc1[$current_persona['m2']]['c'] ?? '#666' ?>"><?= $current_persona['m2'] ?></span>
                <span>•</span>
                <span class="off-pb-dots"><?php for($i=1;$i<=5;$i++):?><span class="off-pb-dot<?=$i<=$current_persona['conscience']?' on':''?>"></span><?php endfor;?></span> <?=$current_persona['conscience']?>/5 — <?=$cLabels[$current_persona['conscience']]??''?>
            </div>
        </div>
        <a href="?page=neuropersona" class="off-pb-back"><i class="fas fa-arrow-left"></i> Changer</a>
    </div>

    <!-- Contexte positionnement chargé -->
    <?php if ($positionContext): ?>
    <div class="off-ctx anim d1">
        <i class="fas fa-link"></i>
        <div>
            <strong>Contexte chargé :</strong> votre positionnement pour ce persona est intégré. L'IA s'en sert pour personnaliser ses conseils.
            <br><button class="off-ctx-toggle" onclick="document.getElementById('ctxDetail').classList.toggle('visible')">Voir le positionnement chargé</button>
            <div class="off-ctx-detail" id="ctxDetail"><?= nl2br(htmlspecialchars($positionContext)) ?></div>
        </div>
    </div>
    <?php else: ?>
    <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:8px 12px;margin-bottom:14px;font-size:.72rem;color:#92400e;display:flex;align-items:center;gap:6px" class="anim d1">
        <i class="fas fa-info-circle"></i>
        Aucun positionnement trouvé pour ce persona. <a href="?page=strategie-positionnement&persona=<?=$persona_id?>" style="color:#92400e;font-weight:700">Remplir le positionnement d'abord →</a>
    </div>
    <?php endif; ?>

    <?php
    $hasPrefill = !empty(array_filter($prefill));
    if ($hasPrefill): ?>
    <div class="off-prefill anim d1">
        <i class="fas fa-magic"></i>
        Certaines réponses ont été pré-remplies depuis votre profil conseiller. Modifiez-les librement.
    </div>
    <?php endif; ?>

    <?php if(!$has_api_key):?><div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:8px 12px;margin-bottom:12px;font-size:.72rem;color:#92400e;display:flex;align-items:center;gap:6px" class="anim"><i class="fas fa-triangle-exclamation"></i>Clé API non configurée — <a href="?page=api-keys" style="color:#92400e;font-weight:700">Configurer →</a></div><?php endif;?>

    <div class="off-layout">
        <div style="display:flex;flex-direction:column;gap:14px">

            <div class="off-card anim d1">
                <div class="off-card-hd"><div class="off-card-hd-ic" style="background:linear-gradient(135deg,#6366f1,#4f46e5)"><i class="fas fa-list-check"></i></div><h2>Questions guidées — Offre</h2><span>Sauvegarde auto</span></div>
                <div class="off-card-body">
                    <div class="off-qs" id="qForm">
                        <?php foreach($questions as $qid=>$q): $i=array_search($qid,array_keys($questions))+1; $val=$notes_json[$qid]??''; $isPrefilled=!empty($val)&&!empty($prefill[$qid])&&$val===$prefill[$qid]; ?>
                        <div class="off-question">
                            <div class="off-q-hdr">
                                <div class="off-q-num" style="background:<?=$q['color']?>"><?=$i?></div>
                                <div class="off-q-lbl"><?=htmlspecialchars($q['label'])?></div>
                                <div class="off-q-saved" id="saved-<?=$qid?>"><i class="fas fa-check-circle"></i> Sauvegardé</div>
                            </div>
                            <div class="off-q-txt"><?=htmlspecialchars($q['question'])?></div>
                            <div class="off-q-aide"><i class="fas fa-lightbulb" style="color:#f59e0b;margin-right:3px"></i> <?=htmlspecialchars($q['aide'])?></div>
                            <textarea class="off-ta<?=$isPrefilled?' prefilled':''?>" id="in-<?=$qid?>" data-qid="<?=$qid?>" placeholder="<?=htmlspecialchars($q['placeholder'])?>"><?=htmlspecialchars($val)?></textarea>
                        </div>
                        <?php if($i<count($questions)):?><div class="off-div"></div><?php endif;?>
                        <?php endforeach;?>
                    </div>
                </div>
            </div>

            <div class="off-card anim d2">
                <div class="off-card-hd"><div class="off-card-hd-ic" style="background:linear-gradient(135deg,#c9913b,#a0722a)"><i class="fas fa-sparkles"></i></div><h2>Résumé de l'offre</h2><span id="rBadge" style="color:#10b981;font-size:.6rem;<?=$resume_ia?'':'display:none'?>"><i class="fas fa-check-circle"></i></span></div>
                <div class="off-card-body">
                    <textarea class="off-resume" id="resumeArea" placeholder="Cliquez « Générer » pour un résumé IA de votre offre…"><?=htmlspecialchars($resume_ia)?></textarea>
                    <div class="off-ractions">
                        <button class="off-btn off-btn-gold" id="genBtn" onclick="genResume()"><i class="fas fa-wand-magic-sparkles"></i> Générer<div class="off-spinner" id="genSp"></div></button>
                        <button class="off-btn off-btn-ghost" onclick="saveR()"><i class="fas fa-save"></i> Sauver</button>
                        <button class="off-btn off-btn-ghost" onclick="navigator.clipboard.writeText(document.getElementById('resumeArea').value).then(()=>toast('Copié'))" style="margin-left:auto"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="off-card anim d2" style="position:sticky;top:20px">
            <div class="off-card-hd"><div class="off-card-hd-ic" style="background:linear-gradient(135deg,#6366f1,#4f46e5)"><i class="fas fa-robot"></i></div><h2>Assistant IA — Offre</h2><button onclick="clearChat()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:.65rem"><i class="fas fa-trash-can"></i></button></div>
            <div class="off-chat">
                <div class="off-chat-msgs" id="chatMsgs">
                    <?php if(empty($chat_hist)):?>
                    <div class="off-chat-empty" id="chatEmpty"><i class="fas fa-comments"></i><div><strong>Assistant construction d'offre</strong><p>Je connais votre positionnement, votre profil et la psychologie du persona #<?=$current_persona['id']?>. Construisons votre offre irrésistible.</p></div></div>
                    <?php else: foreach($chat_hist as $m):?>
                    <div class="off-msg <?=htmlspecialchars($m['role'])?>"><div class="off-msg-av"><?=$m['role']==='user'?'Moi':'IA'?></div><div class="off-msg-bub"><?=nl2br(htmlspecialchars($m['content']))?></div></div>
                    <?php endforeach; endif;?>
                    <div class="off-msg assistant" id="typing" style="display:none"><div class="off-msg-av">IA</div><div class="off-typing visible"><div class="off-typing-dot"></div><div class="off-typing-dot"></div><div class="off-typing-dot"></div></div></div>
                </div>
                <?php if(empty($chat_hist)):?>
                <div class="off-sugg" id="chatSugg">
                    <button class="off-sugg-btn" onclick="sug(this)">Quel problème résoudre pour ce persona ?</button>
                    <button class="off-sugg-btn" onclick="sug(this)">Comment formuler mon offre ?</button>
                    <button class="off-sugg-btn" onclick="sug(this)">Analyse mon offre actuelle</button>
                </div>
                <?php endif;?>
                <div class="off-chat-iw">
                    <textarea class="off-chat-in" id="chatIn" placeholder="Posez votre question sur l'offre…" rows="1" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendChat()}" oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"></textarea>
                    <button class="off-chat-send" id="chatBtn" onclick="sendChat()"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="off-toast" id="toast"><i class="fas fa-check-circle" style="color:#10b981"></i><span id="toastMsg"></span></div>

<script>
(function(){
const C=<?=json_encode($csrf)?>,PID=<?=json_encode($persona_id)?>,API=<?=json_encode($has_api_key)?>,
U='?page=strategie-offre&persona='+PID,
PN=<?=json_encode($current_persona['nom'])?>,M1=<?=json_encode($current_persona['m1'])?>,
M2=<?=json_encode($current_persona['m2'])?>,PC=<?=json_encode($current_persona['conscience'])?>,
QS=<?=json_encode($questions)?>,
POS_CTX=<?=json_encode($positionContext)?>;
let hist=<?=json_encode($chat_hist)?>,sTO=null,busy=false;

function toast(m){const t=document.getElementById('toast');document.getElementById('toastMsg').textContent=m;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2800)}
function notes(){const n={};document.querySelectorAll('.off-ta[data-qid]').forEach(t=>{n[t.dataset.qid]=t.value.trim()});return n}
function summary(){const n=notes();let t='';Object.entries(QS).forEach(([k,q])=>{t+=`\n${q.label}: ${n[k]||'(vide)'}`});return t}
async function post(d){const f=new FormData;f.append('csrf',C);Object.entries(d).forEach(([k,v])=>f.append(k,typeof v==='object'?JSON.stringify(v):v));return(await fetch(U,{method:'POST',body:f})).json()}

// Save
function sched(q){clearTimeout(sTO);sTO=setTimeout(()=>save(q),900)}
async function save(q){const d=await post({action:'save-notes',persona_id:PID,notes:JSON.stringify(notes())});if(d.success){document.getElementById('cFill').style.width=d.completion+'%';document.getElementById('cPct').textContent=d.completion+'%';if(q){const b=document.getElementById('saved-'+q);if(b){b.classList.add('visible');setTimeout(()=>b.classList.remove('visible'),2000)}}}}
document.querySelectorAll('.off-ta[data-qid]').forEach(t=>{t.addEventListener('input',()=>{t.classList.remove('prefilled');sched(t.dataset.qid)});t.addEventListener('blur',()=>save(t.dataset.qid))});

// AI
async function ai(p,m,s){if(!API){toast('Clé API manquante');return null}const f=new FormData;f.append('csrf',C);f.append('action','ai-proxy');if(p)f.append('prompt',p);if(m)f.append('messages',JSON.stringify(m));if(s)f.append('system',s);const d=await(await fetch(U,{method:'POST',body:f})).json();if(!d.success)throw new Error(d.error||'Erreur');return d.text}

// System prompt enrichi avec positionnement
function sysPrompt(){
    let s=`Expert en construction d'offre immobilière et neuromarketing.\nPersona: ${PN}\nMotivations: ${M1}+${M2}\nConscience: ${PC}/5`;
    if(POS_CTX) s+=`\n\n${POS_CTX}`;
    s+=`\n\nRéponses OFFRE actuelles:\n${summary()}`;
    s+=`\n\nTon rôle: aider à construire une offre irrésistible pour ce persona. Concis, pratique, actionnable. Utilise les leviers psychologiques adaptés aux motivations ${M1} et ${M2}.`;
    return s;
}

// Resume
window.genResume=async function(){const b=document.getElementById('genBtn'),s=document.getElementById('genSp');b.disabled=true;s.style.display='inline-block';
try{const sys=sysPrompt();const prompt=`Génère un résumé percutant de l'offre en 5-7 phrases.\nStructure: problème client → solution unique → résultat promis → preuve sociale.\nÉcris à la première personne, style direct et convaincant.\nFais résonner avec la motivation ${M1} du persona.`;
const r=await ai(null,[{role:'user',content:prompt}],sys);
if(r){document.getElementById('resumeArea').value=r;await saveR()}}catch(e){toast(e.message)}finally{b.disabled=false;s.style.display='none'}};
window.saveR=async function(){const d=await post({action:'save-resume',persona_id:PID,resume:document.getElementById('resumeArea').value});if(d.success){document.getElementById('rBadge').style.display='';toast('Sauvegardé')}};

// Chat
function addMsg(r,c){document.getElementById('chatEmpty')?.remove();document.getElementById('chatSugg')?.remove();const w=document.getElementById('chatMsgs'),d=document.createElement('div');d.className='off-msg '+r;d.innerHTML=`<div class="off-msg-av">${r==='user'?'Moi':'IA'}</div><div class="off-msg-bub">${c.replace(/\n/g,'<br>')}</div>`;w.insertBefore(d,document.getElementById('typing'));w.scrollTop=w.scrollHeight}
function typ(on){document.getElementById('typing').style.display=on?'flex':'none';if(on)document.getElementById('chatMsgs').scrollTop=99999}
window.sug=function(b){document.getElementById('chatIn').value=b.textContent.trim();sendChat()};
window.sendChat=async function(){if(busy)return;const i=document.getElementById('chatIn'),m=i.value.trim();if(!m)return;busy=true;document.getElementById('chatBtn').disabled=true;i.value='';i.style.height='auto';addMsg('user',m);hist.push({role:'user',content:m});typ(true);
const sys=sysPrompt();
const msgs=hist.slice(-12).map(x=>({role:x.role,content:x.content}));
try{const a=await ai(null,msgs,sys);hist.push({role:'assistant',content:a});typ(false);addMsg('assistant',a);post({action:'save-chat',persona_id:PID,history:JSON.stringify(hist)})}
catch(e){typ(false);addMsg('assistant','Erreur: '+e.message)}finally{busy=false;document.getElementById('chatBtn').disabled=false}};
window.clearChat=function(){if(!confirm('Effacer le chat ?'))return;hist=[];document.getElementById('chatMsgs').innerHTML='<div class="off-chat-empty" id="chatEmpty"><i class="fas fa-comments"></i><div><strong>Assistant offre</strong><p>Posez vos questions.</p></div></div><div class="off-msg assistant" id="typing" style="display:none"><div class="off-msg-av">IA</div><div class="off-typing visible"><div class="off-typing-dot"></div><div class="off-typing-dot"></div><div class="off-typing-dot"></div></div></div>';post({action:'save-chat',persona_id:PID,history:'[]'});toast('Chat effacé')};
const cw=document.getElementById('chatMsgs');if(cw)cw.scrollTop=cw.scrollHeight;
})();
</script>