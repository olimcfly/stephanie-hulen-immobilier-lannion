<?php
/**
 * ══════════════════════════════════════════════════════════════
 * Page 4 : Mon Contenu (Méthode ANCRE — Pilier C)
 * /admin/modules/strategy/strategie-contenu/index.php
 * ══════════════════════════════════════════════════════════════
 * 5 onglets par niveau de conscience Schwartz
 * Charge le contexte positionnement + offre
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

$db       = getDB();
$instance = INSTANCE_ID;
$etape    = 'contenu';

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

// ══════════════════════════════════════════════════════════════
// NeuroPersonas
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
// 5 niveaux de conscience — config contenu
// ══════════════════════════════════════════════════════════════
$niveaux = [
    1 => [
        'label' => 'Non conscient',
        'short' => 'Niveau 1',
        'color' => '#ef4444',
        'icon'  => 'fa-eye-slash',
        'type_recommande' => 'Article inspiration / lifestyle / éducatif',
        'desc'  => 'Le prospect ne sait pas encore qu\'il a un besoin. Contenu de sensibilisation, lifestyle, tendances.',
        'cta_exemple' => 'Guide gratuit, quiz, checklist à télécharger',
        'exemple_titre' => '5 signes que vous êtes prêt à changer de vie (sans le savoir)',
    ],
    2 => [
        'label' => 'Conscient du problème',
        'short' => 'Niveau 2',
        'color' => '#f59e0b',
        'icon'  => 'fa-lightbulb',
        'type_recommande' => 'Article problème/solution, vidéo éducative',
        'desc'  => 'Il sait qu\'il a un problème mais ne cherche pas encore de solution. Nommer le problème, valider ses craintes.',
        'cta_exemple' => 'Diagnostic gratuit, auto-évaluation, guide erreurs à éviter',
        'exemple_titre' => 'Pourquoi votre loyer vous coûte plus cher que vous ne le pensez',
    ],
    3 => [
        'label' => 'Cherche activement',
        'short' => 'Niveau 3',
        'color' => '#6366f1',
        'icon'  => 'fa-search',
        'type_recommande' => 'Guide pratique, comparatif, FAQ détaillée',
        'desc'  => 'Il cherche des solutions activement. Montrer votre expertise, donner de la valeur concrète.',
        'cta_exemple' => 'Consultation gratuite, estimation offerte, appel découverte',
        'exemple_titre' => 'Acheter vs louer à [ville] : le guide complet 2025',
    ],
    4 => [
        'label' => 'Compare les solutions',
        'short' => 'Niveau 4',
        'color' => '#8b5cf6',
        'icon'  => 'fa-balance-scale',
        'type_recommande' => 'Étude de cas, témoignage client, comparatif agents',
        'desc'  => 'Il compare les options. Prouver votre valeur par les résultats, témoignages, différence.',
        'cta_exemple' => 'Rendez-vous personnalisé, étude de cas similaire, garantie',
        'exemple_titre' => 'Comment Marie a trouvé sa maison en 3 semaines (et pourquoi seule elle n\'y arrivait pas)',
    ],
    5 => [
        'label' => 'Prêt à agir',
        'short' => 'Niveau 5',
        'color' => '#10b981',
        'icon'  => 'fa-rocket',
        'type_recommande' => 'Landing page, page offre, séquence urgence',
        'desc'  => 'Il est prêt à passer à l\'action. Offre claire, CTA direct, urgence ou exclusivité.',
        'cta_exemple' => 'Estimation immédiate, prise de mandat, signature en ligne',
        'exemple_titre' => 'Votre estimation gratuite en 24h — [zone] exclusivement',
    ],
];

// ══════════════════════════════════════════════════════════════
// Charger notes sauvegardées (étape contenu)
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

// Structure notes : {n1: {type, titre, plan, cta}, n2: {...}, ...}
// Initialiser si vide
for ($n = 1; $n <= 5; $n++) {
    $key = 'n' . $n;
    if (!isset($notes_json[$key]) || !is_array($notes_json[$key])) {
        $notes_json[$key] = ['type' => '', 'titre' => '', 'plan' => '', 'cta' => ''];
    }
}

// ══════════════════════════════════════════════════════════════
// Charger contexte chaîné : positionnement + offre
// ══════════════════════════════════════════════════════════════
$chainContext = '';

// Positionnement
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
        if ($parts) $chainContext .= "POSITIONNEMENT:\n" . implode("\n", $parts) . "\n";
        if (!empty($posNote['resume_ia'])) $chainContext .= "Résumé positionnement: " . $posNote['resume_ia'] . "\n";
    }
} catch (Exception $e) {}

// Offre
try {
    $stmt = $db->prepare("SELECT notes_json, resume_ia FROM strategy_notes WHERE instance_id=:i AND persona_id=:p AND etape='offre' LIMIT 1");
    $stmt->execute([':i'=>$instance,':p'=>$persona_id]);
    $offNote = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($offNote) {
        $offNotes = json_decode($offNote['notes_json'] ?? '{}', true) ?: [];
        $offLabels = ['q1'=>'Offre principale','q2'=>'Problème résolu','q3'=>'Solution unique','q4'=>'Résultat promis','q5'=>'Preuve sociale'];
        $parts = [];
        foreach ($offLabels as $k => $l) {
            if (!empty($offNotes[$k])) $parts[] = "$l: {$offNotes[$k]}";
        }
        if ($parts) $chainContext .= "\nOFFRE:\n" . implode("\n", $parts) . "\n";
        if (!empty($offNote['resume_ia'])) $chainContext .= "Résumé offre: " . $offNote['resume_ia'] . "\n";
    }
} catch (Exception $e) {}

$hasChainContext = !empty(trim($chainContext));

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
// Navigation ANCRE
// ══════════════════════════════════════════════════════════════
$ancreSteps = [
    ['slug'=>'strategie-positionnement','label'=>'Positionnement','icon'=>'fa-anchor','letter'=>'A'],
    ['slug'=>'strategie-offre','label'=>'Offre','icon'=>'fa-gift','letter'=>'N'],
    ['slug'=>'strategie-contenu','label'=>'Contenu','icon'=>'fa-pen-nib','letter'=>'C'],
    ['slug'=>'strategie-trafic','label'=>'Trafic','icon'=>'fa-bullhorn','letter'=>'R'],
    ['slug'=>'strategie-conversion','label'=>'Conversion','icon'=>'fa-funnel-dollar','letter'=>'E'],
    ['slug'=>'strategie-optimisation','label'=>'Optimisation','icon'=>'fa-chart-line','letter'=>'+'],
];
$currentStepIdx = 2; // contenu = index 2

$stepsWithData = [];
try {
    $stmt = $db->prepare("SELECT etape, completion FROM strategy_notes WHERE instance_id=:i AND persona_id=:p");
    $stmt->execute([':i'=>$instance,':p'=>$persona_id]);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stepsWithData[$r['etape']] = (int)$r['completion'];
    }
} catch (Exception $e) {}

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
        // Calcul completion : combien de niveaux ont au moins 2 champs remplis sur 4
        $filled = 0;
        for ($n = 1; $n <= 5; $n++) {
            $nk = 'n' . $n;
            if (isset($nn[$nk]) && is_array($nn[$nk])) {
                $fc = count(array_filter($nn[$nk], fn($v) => trim((string)$v) !== ''));
                if ($fc >= 2) $filled++;
            }
        }
        $c = (int)round(($filled / 5) * 100);
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
.cnt{--a:#6366f1;--gold:#c9913b;font-family:'DM Sans',sans-serif;max-width:1080px;margin:0 auto;padding:24px 24px 60px}
.cnt-hd{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap}
.cnt-eye{font-size:.65rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;display:flex;align-items:center;gap:6px}
.cnt-h1{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:#111827;margin:0 0 4px}
.cnt-sub{font-size:.78rem;color:#6b7280}
/* Nav ANCRE */
.cnt-nav{display:flex;gap:4px;margin-bottom:16px;flex-wrap:wrap}
.cnt-nav-step{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:.72rem;font-weight:600;text-decoration:none;border:1.5px solid #e5e7eb;color:#9ca3af;background:#fff;transition:all .15s}
.cnt-nav-step.active{border-color:var(--a);background:linear-gradient(135deg,#6366f110,#6366f105);color:var(--a);font-weight:800}
.cnt-nav-step.done{border-color:#86efac;color:#166534;background:#f0fdf4}
.cnt-nav-step.done:hover{border-color:#22c55e}
.cnt-nav-step.locked{opacity:.45;pointer-events:none}
.cnt-nav-step i{font-size:.6rem}
.cnt-nav-letter{width:18px;height:18px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.55rem;font-weight:800;color:#fff;background:#d1d5db;flex-shrink:0}
.cnt-nav-step.active .cnt-nav-letter{background:var(--a)}
.cnt-nav-step.done .cnt-nav-letter{background:#22c55e}
.cnt-nav-chevron{color:#d1d5db;font-size:.55rem;margin:0 2px}
/* Persona banner */
.cnt-pb{display:flex;align-items:center;gap:14px;padding:14px 18px;background:linear-gradient(135deg,<?=$current_persona['color']?>10,<?=$current_persona['color']?>05);border:1.5px solid <?=$current_persona['color']?>40;border-radius:12px;margin-bottom:16px}
.cnt-pb-num{font-size:11px;font-weight:700;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;background:<?=$current_persona['color']?>;flex-shrink:0}
.cnt-pb-info{flex:1}
.cnt-pb-name{font-size:.9rem;font-weight:700;color:#111827}
.cnt-pb-meta{font-size:.72rem;color:#6b7280;margin-top:2px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.cnt-pb-tag{font-size:9px;font-weight:600;padding:1px 6px;border-radius:3px}
.cnt-pb-dots{display:flex;gap:2px}.cnt-pb-dot{width:5px;height:5px;border-radius:50%;background:#e5e7eb}.cnt-pb-dot.on{background:#f59e0b}
.cnt-pb-back{font-size:.72rem;font-weight:600;color:var(--a);text-decoration:none;display:flex;align-items:center;gap:4px}
.cnt-pb-back:hover{text-decoration:underline}
/* Context banner */
.cnt-ctx{display:flex;align-items:flex-start;gap:8px;padding:10px 14px;background:#f8f9ff;border:1px solid #c7d2fe;border-radius:8px;margin-bottom:14px;font-size:.72rem;color:#4338ca;line-height:1.4}
.cnt-ctx i{color:#6366f1;margin-top:2px;flex-shrink:0}
.cnt-ctx-toggle{background:none;border:none;color:#6366f1;font-weight:700;cursor:pointer;font-size:.68rem;text-decoration:underline;padding:0;margin-top:4px}
.cnt-ctx-detail{display:none;margin-top:6px;padding:8px 10px;background:#fff;border:1px solid #e0e7ff;border-radius:6px;font-size:.7rem;color:#374151;white-space:pre-wrap;line-height:1.5;max-height:200px;overflow-y:auto}
.cnt-ctx-detail.visible{display:block}
/* Layout */
.cnt-layout{display:grid;grid-template-columns:1fr 400px;gap:16px;align-items:start}
@media(max-width:900px){.cnt-layout{grid-template-columns:1fr}}
.cnt-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.06);overflow:hidden}
.cnt-card-hd{display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid #e5e7eb;background:#f9fafb}
.cnt-card-hd-ic{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;color:#fff}
.cnt-card-hd h2{font-family:'Syne',sans-serif;font-size:.85rem;font-weight:800;color:#111827;margin:0;flex:1}
.cnt-card-hd span{font-size:.65rem;color:#9ca3af}
.cnt-card-body{padding:18px}
/* Onglets Schwartz */
.cnt-tabs{display:flex;gap:3px;margin-bottom:16px;flex-wrap:wrap}
.cnt-tab{display:flex;align-items:center;gap:5px;padding:8px 14px;border-radius:8px 8px 0 0;font-size:.72rem;font-weight:600;cursor:pointer;border:1.5px solid #e5e7eb;border-bottom:none;color:#9ca3af;background:#f9fafb;transition:all .15s;position:relative}
.cnt-tab:hover{color:#374151;background:#fff}
.cnt-tab.active{color:#fff;font-weight:800;border-color:transparent}
.cnt-tab i{font-size:.6rem}
.cnt-tab-dot{width:6px;height:6px;border-radius:50%;background:#d1d5db;flex-shrink:0}
.cnt-tab-dot.filled{background:#22c55e}
.cnt-tab-persona{position:absolute;top:-8px;right:-4px;font-size:7px;font-weight:800;background:#111827;color:#fff;padding:1px 4px;border-radius:3px;display:none}
.cnt-tab-persona.visible{display:block}
/* Panel contenu par niveau */
.cnt-panel{display:none}
.cnt-panel.active{display:block;animation:fadeUp .2s ease}
.cnt-panel-header{display:flex;align-items:center;gap:10px;padding:12px 14px;border-radius:8px;margin-bottom:14px}
.cnt-panel-header i{font-size:1rem}
.cnt-panel-header-info{flex:1}
.cnt-panel-header-title{font-size:.82rem;font-weight:800;color:#111827}
.cnt-panel-header-desc{font-size:.7rem;color:#6b7280;margin-top:2px;line-height:1.35}
.cnt-field{margin-bottom:14px}
.cnt-field-label{font-size:.7rem;font-weight:700;color:#374151;margin-bottom:4px;display:flex;align-items:center;gap:6px}
.cnt-field-label i{font-size:.6rem;color:#9ca3af}
.cnt-field-hint{font-size:.65rem;color:#9ca3af;margin-bottom:4px}
.cnt-ta{width:100%;min-height:52px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:9px 11px;font-family:'DM Sans',sans-serif;font-size:.8rem;color:#111827;resize:vertical;outline:none;transition:border-color .15s;box-sizing:border-box}
.cnt-ta:focus{border-color:var(--a);box-shadow:0 0 0 3px rgba(99,102,241,.08)}
.cnt-ta-sm{min-height:38px}
.cnt-gen-row{display:flex;gap:6px;align-items:center;margin-bottom:14px}
.cnt-btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:7px;font-size:.72rem;font-weight:700;cursor:pointer;border:none;transition:transform .15s}
.cnt-btn-sm{padding:5px 10px;font-size:.68rem}
.cnt-btn-accent{background:linear-gradient(135deg,var(--a),#4f46e5);color:#fff}
.cnt-btn-accent:hover{transform:translateY(-1px)}
.cnt-btn-gold{background:linear-gradient(135deg,var(--gold),#a0722a);color:#fff}
.cnt-btn-gold:hover{transform:translateY(-1px)}
.cnt-btn-ghost{background:#f9fafb;border:1px solid #e5e7eb;color:#6b7280}
.cnt-btn-ghost:hover{background:#e5e7eb}
.cnt-spinner{width:12px;height:12px;border-radius:50%;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;animation:spin .7s linear infinite;display:none}
@keyframes spin{to{transform:rotate(360deg)}}
.cnt-saved{font-size:.6rem;color:#10b981;opacity:0;transition:opacity .3s;display:flex;align-items:center;gap:3px;margin-left:auto}
.cnt-saved.visible{opacity:1}
/* Resume */
.cnt-resume{width:100%;min-height:120px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;font-family:'DM Sans',sans-serif;font-size:.8rem;color:#111827;resize:vertical;outline:none;box-sizing:border-box;line-height:1.6}
.cnt-resume:focus{border-color:var(--gold)}
.cnt-ractions{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
/* Chat */
.cnt-chat{display:flex;flex-direction:column;height:520px}
.cnt-chat-msgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:8px;scrollbar-width:thin}
.cnt-msg{display:flex;gap:6px;align-items:flex-end;animation:msgIn .2s ease}
@keyframes msgIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
.cnt-msg-av{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;flex-shrink:0}
.cnt-msg-bub{max-width:85%;padding:9px 12px;border-radius:11px;font-size:.78rem;line-height:1.5}
.cnt-msg.user{flex-direction:row-reverse}
.cnt-msg.user .cnt-msg-av{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff}
.cnt-msg.user .cnt-msg-bub{background:linear-gradient(135deg,var(--a),#4f46e5);color:#fff;border-bottom-right-radius:3px}
.cnt-msg.assistant .cnt-msg-av{background:linear-gradient(135deg,#c9913b,#a0722a);color:#fff}
.cnt-msg.assistant .cnt-msg-bub{background:#f9fafb;border:1px solid #e5e7eb;color:#111827;border-bottom-left-radius:3px}
.cnt-typing{display:none;align-items:center;gap:3px;padding:9px 12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:11px;width:fit-content}
.cnt-typing.visible{display:flex}
.cnt-typing-dot{width:5px;height:5px;border-radius:50%;background:#9ca3af;animation:td 1.2s infinite}
.cnt-typing-dot:nth-child(2){animation-delay:.2s}.cnt-typing-dot:nth-child(3){animation-delay:.4s}
@keyframes td{0%,80%,100%{transform:translateY(0);opacity:.4}40%{transform:translateY(-3px);opacity:1}}
.cnt-chat-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:18px;text-align:center}
.cnt-chat-empty i{font-size:1.6rem;color:#9ca3af}
.cnt-chat-empty strong{font-size:.82rem;color:#111827}
.cnt-chat-empty p{font-size:.72rem;color:#6b7280;line-height:1.4}
.cnt-sugg{display:flex;flex-wrap:wrap;gap:4px;padding:0 12px 8px}
.cnt-sugg-btn{padding:4px 10px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:16px;cursor:pointer;font-size:.68rem;color:#6b7280;font-family:inherit;transition:all .13s}
.cnt-sugg-btn:hover{border-color:var(--a);color:var(--a)}
.cnt-chat-iw{padding:8px 12px 12px;border-top:1px solid #e5e7eb;display:flex;gap:6px;align-items:flex-end}
.cnt-chat-in{flex:1;min-height:36px;max-height:100px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:9px;padding:8px 11px;font-family:'DM Sans',sans-serif;font-size:.78rem;color:#111827;outline:none;resize:none;box-sizing:border-box}
.cnt-chat-in:focus{border-color:var(--a)}
.cnt-chat-send{width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,var(--a),#4f46e5);border:none;cursor:pointer;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
.cnt-chat-send:disabled{opacity:.5;cursor:not-allowed}
.cnt-toast{position:fixed;bottom:24px;right:24px;z-index:9999;background:#111827;color:#fff;padding:10px 16px;border-radius:9px;font-size:.75rem;font-weight:600;display:flex;align-items:center;gap:6px;box-shadow:0 8px 24px rgba(0,0,0,.2);transform:translateY(20px);opacity:0;transition:all .25s;pointer-events:none}
.cnt-toast.show{transform:none;opacity:1}
.anim{animation:fadeUp .25s ease both}.d1{animation-delay:.05s}.d2{animation-delay:.1s}
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
</style>

<div class="cnt">
    <div class="cnt-hd anim">
        <div>
            <div class="cnt-eye"><i class="fas fa-pen-nib"></i> Méthode ANCRE — Pilier C</div>
            <h1 class="cnt-h1">Mon Contenu</h1>
            <p class="cnt-sub">Stratégie de contenu par niveau de conscience. 5 niveaux, 5 types de contenu, 5 CTA.</p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:8px 14px;display:flex;align-items:center;gap:10px">
                <div style="flex:1;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;min-width:100px"><div id="cFill" style="height:100%;border-radius:3px;background:linear-gradient(90deg,var(--a),#818cf8);width:<?=$completion?>%;transition:width .5s"></div></div>
                <span id="cPct" style="font-size:.72rem;font-weight:800;color:var(--a)"><?=$completion?>%</span>
            </div>
        </div>
    </div>

    <!-- Navigation ANCRE -->
    <div class="cnt-nav anim d1">
        <?php foreach ($ancreSteps as $idx => $step):
            $stepEtape = str_replace('strategie-', '', $step['slug']);
            $isDone = isset($stepsWithData[$stepEtape]) && $stepsWithData[$stepEtape] > 0;
            $isActive = $idx === $currentStepIdx;
            $isLocked = !$isActive && !$isDone && $idx > $currentStepIdx;
            $cls = $isActive ? 'active' : ($isDone ? 'done' : ($isLocked ? 'locked' : ''));
        ?>
            <?php if ($idx > 0): ?><span class="cnt-nav-chevron"><i class="fas fa-chevron-right"></i></span><?php endif; ?>
            <a href="?page=<?=$step['slug']?>&persona=<?=$persona_id?>" class="cnt-nav-step <?=$cls?>">
                <span class="cnt-nav-letter"><?=$step['letter']?></span>
                <i class="fas <?=$step['icon']?>"></i> <?=$step['label']?>
                <?php if ($isDone && !$isActive): ?><i class="fas fa-check" style="font-size:.55rem;color:#22c55e"></i><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Persona banner -->
    <div class="cnt-pb anim d1">
        <span class="cnt-pb-num"><?=$current_persona['id']?></span>
        <div class="cnt-pb-info">
            <div class="cnt-pb-name"><?=htmlspecialchars($current_persona['nom'])?></div>
            <div class="cnt-pb-meta">
                <?=$familyMeta[$current_persona['type']]['icon']??''?> <?=$familyMeta[$current_persona['type']]['label']??''?>
                <span>•</span>
                <?php $mc1=['Sécurité'=>['c'=>'#1e40af','bg'=>'#dbeafe'],'Liberté'=>['c'=>'#065f46','bg'=>'#d1fae5'],'Reconnaissance'=>['c'=>'#92400e','bg'=>'#fef3c7'],'Contrôle'=>['c'=>'#5b21b6','bg'=>'#ede9fe']]; ?>
                <span class="cnt-pb-tag" style="background:<?=$mc1[$current_persona['m1']]['bg']??'#eee'?>;color:<?=$mc1[$current_persona['m1']]['c']??'#666'?>"><?=$current_persona['m1']?></span>
                <span>+</span>
                <span class="cnt-pb-tag" style="background:<?=$mc1[$current_persona['m2']]['bg']??'#eee'?>;color:<?=$mc1[$current_persona['m2']]['c']??'#666'?>"><?=$current_persona['m2']?></span>
                <span>•</span>
                <span class="cnt-pb-dots"><?php for($i=1;$i<=5;$i++):?><span class="cnt-pb-dot<?=$i<=$current_persona['conscience']?' on':''?>"></span><?php endfor;?></span> <?=$current_persona['conscience']?>/5 — <?=$cLabels[$current_persona['conscience']]??''?>
            </div>
        </div>
        <a href="?page=neuropersona" class="cnt-pb-back"><i class="fas fa-arrow-left"></i> Changer</a>
    </div>

    <!-- Contexte chaîné -->
    <?php if ($hasChainContext): ?>
    <div class="cnt-ctx anim d1">
        <i class="fas fa-link"></i>
        <div>
            <strong>Contexte chargé :</strong> positionnement + offre intégrés. L'IA adapte ses suggestions à votre stratégie complète.
            <br><button class="cnt-ctx-toggle" onclick="document.getElementById('ctxDetail').classList.toggle('visible')">Voir le contexte</button>
            <div class="cnt-ctx-detail" id="ctxDetail"><?=nl2br(htmlspecialchars($chainContext))?></div>
        </div>
    </div>
    <?php else: ?>
    <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:8px 12px;margin-bottom:14px;font-size:.72rem;color:#92400e;display:flex;align-items:center;gap:6px" class="anim d1">
        <i class="fas fa-info-circle"></i>
        Étapes précédentes incomplètes. <a href="?page=strategie-positionnement&persona=<?=$persona_id?>" style="color:#92400e;font-weight:700">Positionnement →</a> · <a href="?page=strategie-offre&persona=<?=$persona_id?>" style="color:#92400e;font-weight:700">Offre →</a>
    </div>
    <?php endif; ?>

    <?php if(!$has_api_key):?><div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:8px 12px;margin-bottom:12px;font-size:.72rem;color:#92400e;display:flex;align-items:center;gap:6px" class="anim"><i class="fas fa-triangle-exclamation"></i>Clé API non configurée — <a href="?page=api-keys" style="color:#92400e;font-weight:700">Configurer →</a></div><?php endif;?>

    <div class="cnt-layout">
        <div style="display:flex;flex-direction:column;gap:14px">

            <!-- Onglets Schwartz -->
            <div class="cnt-card anim d1">
                <div class="cnt-card-hd">
                    <div class="cnt-card-hd-ic" style="background:linear-gradient(135deg,#6366f1,#4f46e5)"><i class="fas fa-layer-group"></i></div>
                    <h2>Stratégie de contenu par niveau</h2>
                    <span>Sauvegarde auto</span>
                    <div class="cnt-saved" id="globalSaved"><i class="fas fa-check-circle"></i> Sauvegardé</div>
                </div>

                <div style="padding:16px 16px 0">
                    <div class="cnt-tabs">
                        <?php foreach ($niveaux as $n => $niv):
                            $nk = 'n' . $n;
                            $isFilled = !empty($notes_json[$nk]['titre']) || !empty($notes_json[$nk]['plan']);
                            $isPersonaLevel = $n === $current_persona['conscience'];
                        ?>
                        <div class="cnt-tab<?=$n===1?' active':''?>" data-tab="<?=$n?>" style="<?=$n===1?'background:'.$niv['color'].';border-color:transparent':''?>" onclick="switchTab(<?=$n?>)">
                            <i class="fas <?=$niv['icon']?>"></i>
                            <?=$niv['short']?>
                            <span class="cnt-tab-dot<?=$isFilled?' filled':''?>" id="dot-<?=$n?>"></span>
                            <span class="cnt-tab-persona<?=$isPersonaLevel?' visible':''?>">★ Persona</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="cnt-card-body" style="padding-top:0">
                    <?php foreach ($niveaux as $n => $niv):
                        $nk = 'n' . $n;
                        $nd = $notes_json[$nk];
                        $isPersonaLevel = $n === $current_persona['conscience'];
                    ?>
                    <div class="cnt-panel<?=$n===1?' active':''?>" id="panel-<?=$n?>">

                        <div class="cnt-panel-header" style="background:<?=$niv['color']?>10;border:1px solid <?=$niv['color']?>30">
                            <i class="fas <?=$niv['icon']?>" style="color:<?=$niv['color']?>"></i>
                            <div class="cnt-panel-header-info">
                                <div class="cnt-panel-header-title" style="color:<?=$niv['color']?>"><?=$niv['label']?><?=$isPersonaLevel?' — <span style="font-size:.7rem;background:#111827;color:#fff;padding:1px 6px;border-radius:3px">Niveau de votre persona</span>':''?></div>
                                <div class="cnt-panel-header-desc"><?=htmlspecialchars($niv['desc'])?></div>
                            </div>
                        </div>

                        <!-- Type de contenu -->
                        <div class="cnt-field">
                            <div class="cnt-field-label"><i class="fas fa-tag"></i> Type de contenu recommandé</div>
                            <div class="cnt-field-hint">Suggestion : <?=htmlspecialchars($niv['type_recommande'])?></div>
                            <textarea class="cnt-ta cnt-ta-sm" data-niv="<?=$n?>" data-field="type" placeholder="<?=htmlspecialchars($niv['type_recommande'])?>"><?=htmlspecialchars($nd['type'] ?: $niv['type_recommande'])?></textarea>
                        </div>

                        <!-- Titre d'article -->
                        <div class="cnt-field">
                            <div class="cnt-field-label"><i class="fas fa-heading"></i> Titre d'article / contenu</div>
                            <div class="cnt-gen-row">
                                <textarea class="cnt-ta cnt-ta-sm" id="titre-<?=$n?>" data-niv="<?=$n?>" data-field="titre" placeholder="Ex : <?=htmlspecialchars($niv['exemple_titre'])?>" style="flex:1"><?=htmlspecialchars($nd['titre'])?></textarea>
                                <button class="cnt-btn cnt-btn-sm cnt-btn-accent" onclick="genTitre(<?=$n?>)" title="Générer avec l'IA"><i class="fas fa-wand-magic-sparkles"></i><div class="cnt-spinner" id="sp-titre-<?=$n?>"></div></button>
                            </div>
                        </div>

                        <!-- Plan de contenu -->
                        <div class="cnt-field">
                            <div class="cnt-field-label"><i class="fas fa-list-ol"></i> Plan / structure du contenu</div>
                            <div class="cnt-gen-row">
                                <textarea class="cnt-ta" id="plan-<?=$n?>" data-niv="<?=$n?>" data-field="plan" placeholder="Introduction, sections principales, conclusion… L'IA peut générer un plan structuré." style="flex:1;min-height:90px"><?=htmlspecialchars($nd['plan'])?></textarea>
                                <button class="cnt-btn cnt-btn-sm cnt-btn-accent" onclick="genPlan(<?=$n?>)" title="Générer un plan" style="align-self:flex-start;margin-top:4px"><i class="fas fa-wand-magic-sparkles"></i><div class="cnt-spinner" id="sp-plan-<?=$n?>"></div></button>
                            </div>
                        </div>

                        <!-- CTA -->
                        <div class="cnt-field">
                            <div class="cnt-field-label"><i class="fas fa-mouse-pointer"></i> Call-to-Action adapté</div>
                            <div class="cnt-field-hint">Suggestion : <?=htmlspecialchars($niv['cta_exemple'])?></div>
                            <textarea class="cnt-ta cnt-ta-sm" data-niv="<?=$n?>" data-field="cta" placeholder="<?=htmlspecialchars($niv['cta_exemple'])?>"><?=htmlspecialchars($nd['cta'] ?: $niv['cta_exemple'])?></textarea>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Résumé stratégie contenu -->
            <div class="cnt-card anim d2">
                <div class="cnt-card-hd"><div class="cnt-card-hd-ic" style="background:linear-gradient(135deg,#c9913b,#a0722a)"><i class="fas fa-sparkles"></i></div><h2>Résumé stratégie contenu</h2><span id="rBadge" style="color:#10b981;font-size:.6rem;<?=$resume_ia?'':'display:none'?>"><i class="fas fa-check-circle"></i></span></div>
                <div class="cnt-card-body">
                    <textarea class="cnt-resume" id="resumeArea" placeholder="Cliquez « Générer » pour un résumé IA de votre stratégie contenu complète…"><?=htmlspecialchars($resume_ia)?></textarea>
                    <div class="cnt-ractions">
                        <button class="cnt-btn cnt-btn-gold" id="genResumeBtn" onclick="genResume()"><i class="fas fa-wand-magic-sparkles"></i> Générer le résumé<div class="cnt-spinner" id="genResumeSp"></div></button>
                        <button class="cnt-btn cnt-btn-ghost" onclick="saveR()"><i class="fas fa-save"></i> Sauver</button>
                        <button class="cnt-btn cnt-btn-ghost" onclick="navigator.clipboard.writeText(document.getElementById('resumeArea').value).then(()=>toast('Copié'))" style="margin-left:auto"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assistant IA -->
        <div class="cnt-card anim d2" style="position:sticky;top:20px">
            <div class="cnt-card-hd"><div class="cnt-card-hd-ic" style="background:linear-gradient(135deg,#6366f1,#4f46e5)"><i class="fas fa-robot"></i></div><h2>Assistant IA — Contenu</h2><button onclick="clearChat()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:.65rem"><i class="fas fa-trash-can"></i></button></div>
            <div class="cnt-chat">
                <div class="cnt-chat-msgs" id="chatMsgs">
                    <?php if(empty($chat_hist)):?>
                    <div class="cnt-chat-empty" id="chatEmpty"><i class="fas fa-comments"></i><div><strong>Assistant stratégie contenu</strong><p>Je connais votre positionnement, votre offre et la psychologie du persona #<?=$current_persona['id']?>. Construisons votre plan de contenu par niveau de conscience.</p></div></div>
                    <?php else: foreach($chat_hist as $m):?>
                    <div class="cnt-msg <?=htmlspecialchars($m['role'])?>"><div class="cnt-msg-av"><?=$m['role']==='user'?'Moi':'IA'?></div><div class="cnt-msg-bub"><?=nl2br(htmlspecialchars($m['content']))?></div></div>
                    <?php endforeach; endif;?>
                    <div class="cnt-msg assistant" id="typing" style="display:none"><div class="cnt-msg-av">IA</div><div class="cnt-typing visible"><div class="cnt-typing-dot"></div><div class="cnt-typing-dot"></div><div class="cnt-typing-dot"></div></div></div>
                </div>
                <?php if(empty($chat_hist)):?>
                <div class="cnt-sugg" id="chatSugg">
                    <button class="cnt-sugg-btn" onclick="sug(this)">Quel contenu pour ce persona ?</button>
                    <button class="cnt-sugg-btn" onclick="sug(this)">Idées d'articles niveau 2</button>
                    <button class="cnt-sugg-btn" onclick="sug(this)">Stratégie complète 5 niveaux</button>
                </div>
                <?php endif;?>
                <div class="cnt-chat-iw">
                    <textarea class="cnt-chat-in" id="chatIn" placeholder="Posez votre question sur le contenu…" rows="1" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendChat()}" oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"></textarea>
                    <button class="cnt-chat-send" id="chatBtn" onclick="sendChat()"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="cnt-toast" id="toast"><i class="fas fa-check-circle" style="color:#10b981"></i><span id="toastMsg"></span></div>

<script>
(function(){
const C=<?=json_encode($csrf)?>,PID=<?=json_encode($persona_id)?>,API=<?=json_encode($has_api_key)?>,
U='?page=strategie-contenu&persona='+PID,
PN=<?=json_encode($current_persona['nom'])?>,M1=<?=json_encode($current_persona['m1'])?>,
M2=<?=json_encode($current_persona['m2'])?>,PC=<?=json_encode($current_persona['conscience'])?>,
CHAIN=<?=json_encode($chainContext)?>,
NIV=<?=json_encode($niveaux, JSON_UNESCAPED_UNICODE)?>,
NKEYS=['n1','n2','n3','n4','n5'];
const TAB_COLORS=<?=json_encode(array_combine(array_keys($niveaux), array_column($niveaux, 'color')))?>;
let hist=<?=json_encode($chat_hist)?>,sTO=null,busy=false,activeTab=1;

function toast(m){const t=document.getElementById('toast');document.getElementById('toastMsg').textContent=m;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2800)}

// ── Tabs ──
window.switchTab=function(n){
    activeTab=n;
    document.querySelectorAll('.cnt-tab').forEach(t=>{
        const tn=parseInt(t.dataset.tab);
        t.classList.toggle('active',tn===n);
        t.style.background=tn===n?TAB_COLORS[tn]:'#f9fafb';
        t.style.color=tn===n?'#fff':'#9ca3af';
        t.style.borderColor=tn===n?'transparent':'#e5e7eb';
    });
    document.querySelectorAll('.cnt-panel').forEach(p=>p.classList.remove('active'));
    document.getElementById('panel-'+n).classList.add('active');
};

// ── Notes collect ──
function allNotes(){
    const n={};
    for(let i=1;i<=5;i++){
        const nk='n'+i;
        n[nk]={type:'',titre:'',plan:'',cta:''};
        document.querySelectorAll(`[data-niv="${i}"]`).forEach(t=>{
            n[nk][t.dataset.field]=t.value.trim();
        });
    }
    return n;
}
function currentNotesSummary(){
    const n=allNotes();let s='';
    for(let i=1;i<=5;i++){
        const nk='n'+i,d=n[nk],lbl=NIV[i]?.label||'Niveau '+i;
        s+=`\n${lbl}:\n  Type: ${d.type||'(vide)'}\n  Titre: ${d.titre||'(vide)'}\n  Plan: ${d.plan||'(vide)'}\n  CTA: ${d.cta||'(vide)'}`;
    }
    return s;
}
async function post(d){const f=new FormData;f.append('csrf',C);Object.entries(d).forEach(([k,v])=>f.append(k,typeof v==='object'?JSON.stringify(v):v));return(await fetch(U,{method:'POST',body:f})).json()}

// ── Save ──
function sched(){clearTimeout(sTO);sTO=setTimeout(()=>saveNotes(),900)}
async function saveNotes(){
    const d=await post({action:'save-notes',persona_id:PID,notes:JSON.stringify(allNotes())});
    if(d.success){
        document.getElementById('cFill').style.width=d.completion+'%';
        document.getElementById('cPct').textContent=d.completion+'%';
        const gs=document.getElementById('globalSaved');gs.classList.add('visible');setTimeout(()=>gs.classList.remove('visible'),2000);
        // Update dots
        const n=allNotes();
        for(let i=1;i<=5;i++){
            const nk='n'+i,dd=n[nk],filled=!!(dd.titre||dd.plan);
            const dot=document.getElementById('dot-'+i);
            if(dot)dot.classList.toggle('filled',filled);
        }
    }
}
document.querySelectorAll('.cnt-ta').forEach(t=>{t.addEventListener('input',()=>sched());t.addEventListener('blur',()=>saveNotes())});

// ── AI ──
async function ai(p,m,s){if(!API){toast('Clé API manquante');return null}const f=new FormData;f.append('csrf',C);f.append('action','ai-proxy');if(p)f.append('prompt',p);if(m)f.append('messages',JSON.stringify(m));if(s)f.append('system',s);const d=await(await fetch(U,{method:'POST',body:f})).json();if(!d.success)throw new Error(d.error||'Erreur');return d.text}

function sysPrompt(){
    let s=`Expert en stratégie de contenu immobilier et neuromarketing (modèle Schwartz 5 niveaux de conscience).\nPersona: ${PN}\nMotivations: ${M1}+${M2}\nConscience actuelle: ${PC}/5`;
    if(CHAIN) s+=`\n\n${CHAIN}`;
    s+=`\n\nContenu actuel:\n${currentNotesSummary()}`;
    s+=`\n\nTon rôle: aider à créer du contenu adapté à chaque niveau de conscience pour ce persona. Concis, pratique, actionnable. Utilise les leviers ${M1} et ${M2}.`;
    return s;
}

// ── Generate titre ──
window.genTitre=async function(n){
    const sp=document.getElementById('sp-titre-'+n);sp.style.display='inline-block';sp.parentElement.disabled=true;
    try{
        const niv=NIV[n],sys=sysPrompt();
        const prompt=`Génère 3 titres d'articles percutants pour le niveau ${n} (${niv.label}) de conscience Schwartz.\nType recommandé: ${niv.type_recommande}\nPersona: ${PN} (motivation ${M1})\nFormat: un titre par ligne, numérotés 1. 2. 3.\nPas d'introduction, juste les titres.`;
        const r=await ai(null,[{role:'user',content:prompt}],sys);
        if(r)document.getElementById('titre-'+n).value=r;
        sched();
    }catch(e){toast(e.message)}
    finally{sp.style.display='none';sp.parentElement.disabled=false}
};

// ── Generate plan ──
window.genPlan=async function(n){
    const sp=document.getElementById('sp-plan-'+n);sp.style.display='inline-block';sp.parentElement.disabled=true;
    try{
        const niv=NIV[n],titre=document.getElementById('titre-'+n).value||niv.exemple_titre,sys=sysPrompt();
        const prompt=`Génère un plan de contenu structuré pour:\nNiveau: ${n} — ${niv.label}\nTitre: ${titre}\nPersona: ${PN}\n\nFormat:\n- Introduction (2 lignes d'accroche)\n- 3-5 sections avec sous-points\n- Conclusion + CTA\n\nAdapté à la motivation ${M1} du persona. Concis, chaque section = 1 ligne.`;
        const r=await ai(null,[{role:'user',content:prompt}],sys);
        if(r)document.getElementById('plan-'+n).value=r;
        sched();
    }catch(e){toast(e.message)}
    finally{sp.style.display='none';sp.parentElement.disabled=false}
};

// ── Resume ──
window.genResume=async function(){
    const b=document.getElementById('genResumeBtn'),sp=document.getElementById('genResumeSp');b.disabled=true;sp.style.display='inline-block';
    try{
        const sys=sysPrompt();
        const prompt=`Génère un résumé de la stratégie de contenu complète pour le persona ${PN}.\nStructure:\n1. Vue d'ensemble (2 phrases)\n2. Pour chaque niveau de conscience: type de contenu + titre + CTA (1 ligne par niveau)\n3. Recommandation prioritaire: par quel niveau commencer et pourquoi (basé sur conscience ${PC}/5)\n\nFormat concis, actionnable. 10-15 lignes max.`;
        const r=await ai(null,[{role:'user',content:prompt}],sys);
        if(r){document.getElementById('resumeArea').value=r;await saveR()}
    }catch(e){toast(e.message)}
    finally{b.disabled=false;sp.style.display='none'}
};
window.saveR=async function(){const d=await post({action:'save-resume',persona_id:PID,resume:document.getElementById('resumeArea').value});if(d.success){document.getElementById('rBadge').style.display='';toast('Sauvegardé')}};

// ── Chat ──
function addMsg(r,c){document.getElementById('chatEmpty')?.remove();document.getElementById('chatSugg')?.remove();const w=document.getElementById('chatMsgs'),d=document.createElement('div');d.className='cnt-msg '+r;d.innerHTML=`<div class="cnt-msg-av">${r==='user'?'Moi':'IA'}</div><div class="cnt-msg-bub">${c.replace(/\n/g,'<br>')}</div>`;w.insertBefore(d,document.getElementById('typing'));w.scrollTop=w.scrollHeight}
function typ(on){document.getElementById('typing').style.display=on?'flex':'none';if(on)document.getElementById('chatMsgs').scrollTop=99999}
window.sug=function(b){document.getElementById('chatIn').value=b.textContent.trim();sendChat()};
window.sendChat=async function(){if(busy)return;const i=document.getElementById('chatIn'),m=i.value.trim();if(!m)return;busy=true;document.getElementById('chatBtn').disabled=true;i.value='';i.style.height='auto';addMsg('user',m);hist.push({role:'user',content:m});typ(true);
const sys=sysPrompt();
const msgs=hist.slice(-12).map(x=>({role:x.role,content:x.content}));
try{const a=await ai(null,msgs,sys);hist.push({role:'assistant',content:a});typ(false);addMsg('assistant',a);post({action:'save-chat',persona_id:PID,history:JSON.stringify(hist)})}
catch(e){typ(false);addMsg('assistant','Erreur: '+e.message)}finally{busy=false;document.getElementById('chatBtn').disabled=false}};
window.clearChat=function(){if(!confirm('Effacer le chat ?'))return;hist=[];document.getElementById('chatMsgs').innerHTML='<div class="cnt-chat-empty" id="chatEmpty"><i class="fas fa-comments"></i><div><strong>Assistant contenu</strong><p>Posez vos questions.</p></div></div><div class="cnt-msg assistant" id="typing" style="display:none"><div class="cnt-msg-av">IA</div><div class="cnt-typing visible"><div class="cnt-typing-dot"></div><div class="cnt-typing-dot"></div><div class="cnt-typing-dot"></div></div></div>';post({action:'save-chat',persona_id:PID,history:'[]'});toast('Chat effacé')};
const cw=document.getElementById('chatMsgs');if(cw)cw.scrollTop=cw.scrollHeight;

// Auto-switch to persona conscience level tab
<?php if ($current_persona['conscience'] >= 1 && $current_persona['conscience'] <= 5): ?>
switchTab(<?=$current_persona['conscience']?>);
<?php endif; ?>
})();
</script>