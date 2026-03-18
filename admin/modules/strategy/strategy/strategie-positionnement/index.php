<?php
/**
 * ══════════════════════════════════════════════════════════════
 * Page 2 : Positionnement (v4 — pré-rempli advisor_context)
 * /admin/modules/strategy/strategie-positionnement/index.php
 * ══════════════════════════════════════════════════════════════
 * Reçoit ?persona=X depuis la page catalogue NeuroPersona
 * Pré-remplit les réponses depuis advisor_context
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

$db       = getDB();
$instance = INSTANCE_ID;
$etape    = 'positionnement';

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

// ══════════════════════════════════════════════════════════════
// NeuroPersonas + Familles
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
// Charger advisor_context → pré-remplir les questions
// ══════════════════════════════════════════════════════════════
$advisorData = [];
try {
    $stmt = $db->query("SELECT field_key, field_value FROM advisor_context");
    $advisorData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
} catch (Exception $e) {}

// Mapping advisor_context → questions
$prefill = [
    'q1' => '', // territoire
    'q2' => '', // différence
    'q3' => '', // promesse
    'q4' => '', // preuves
    'q5' => '', // anti-positionnement
];

// Q1 Territoire : advisor_zone + advisor_city + secteurs
$q1Parts = [];
if (!empty($advisorData['advisor_zone'])) $q1Parts[] = $advisorData['advisor_zone'];
if (!empty($advisorData['advisor_city'])) $q1Parts[] = 'basé(e) à ' . $advisorData['advisor_city'];
// Ajouter les secteurs si disponibles
$secteurNames = [];
if (!empty($advisorData['secteurs'])) {
    $decoded = json_decode($advisorData['secteurs'], true);
    if (is_array($decoded)) $secteurNames = $decoded;
    else foreach (preg_split('/[,;|]+/', $advisorData['secteurs']) as $s) { $s = trim($s); if ($s) $secteurNames[] = $s; }
}
try {
    $stmt = $db->query("SELECT name FROM secteurs WHERE status='published' ORDER BY name");
    while ($r = $stmt->fetch()) { if (!in_array($r['name'], $secteurNames)) $secteurNames[] = $r['name']; }
} catch (Exception $e) {}
if ($secteurNames) $q1Parts[] = 'Secteurs : ' . implode(', ', array_slice($secteurNames, 0, 8));
$prefill['q1'] = implode('. ', array_filter($q1Parts));

// Q2 Différence : advisor_network + advisor_style + expérience
$q2Parts = [];
if (!empty($advisorData['advisor_network'])) $q2Parts[] = 'Conseiller(ère) ' . $advisorData['advisor_network'];
if (!empty($advisorData['advisor_style'])) $q2Parts[] = $advisorData['advisor_style'];
if (!empty($advisorData['advisor_experience'])) $q2Parts[] = $advisorData['advisor_experience'] . ' d\'expérience';
if (!empty($advisorData['advisor_speciality'])) $q2Parts[] = 'Spécialité : ' . $advisorData['advisor_speciality'];
$prefill['q2'] = implode('. ', array_filter($q2Parts));

// Q3 Promesse : advisor_promise ou signature
if (!empty($advisorData['advisor_promise'])) $prefill['q3'] = $advisorData['advisor_promise'];
elseif (!empty($advisorData['signature'])) $prefill['q3'] = $advisorData['signature'];
elseif (!empty($advisorData['metier.signature'])) $prefill['q3'] = $advisorData['metier.signature'];

// Q4 Preuves : advisor_stats, certifications, etc.
$q4Parts = [];
if (!empty($advisorData['advisor_stats'])) $q4Parts[] = $advisorData['advisor_stats'];
if (!empty($advisorData['advisor_certifications'])) $q4Parts[] = 'Certifications : ' . $advisorData['advisor_certifications'];
if (!empty($advisorData['advisor_reviews'])) $q4Parts[] = $advisorData['advisor_reviews'];
$prefill['q4'] = implode('. ', array_filter($q4Parts));

// Q5 Anti-positionnement : si défini
if (!empty($advisorData['advisor_anti_positioning'])) $prefill['q5'] = $advisorData['advisor_anti_positioning'];

// ══════════════════════════════════════════════════════════════
// Questions avec aides
// ══════════════════════════════════════════════════════════════
$questions = [
    'q1' => ['label'=>'Votre territoire','question'=>'Quelle est votre zone géographique principale ?','placeholder'=>'Ex : Lannion et le Trégor, rayon 30 km…','aide'=>'Soyez précis : ville principale, communes limitrophes, rayon couvert. Ça calibre votre message local.','icon'=>'fa-location-dot','color'=>'#6366f1'],
    'q2' => ['label'=>'Votre différence','question'=>'En quoi êtes-vous différent des autres agents ?','placeholder'=>'Ex : Seule conseillère spécialisée marché côtier breton, 12 ans…','aide'=>'Ce que vos concurrents ne font PAS : spécialisation, ancienneté, réseau artisans, technologie, approche humaine…','icon'=>'fa-star','color'=>'#f59e0b'],
    'q3' => ['label'=>'Votre promesse','question'=>'Quelle est votre promesse principale à vos clients ?','placeholder'=>'Ex : Je vends au juste prix en moins de 60 jours, sans stress…','aide'=>'Une bonne promesse est mesurable. Évitez "service de qualité". Préférez un résultat chiffré : délai, prix, visites…','icon'=>'fa-handshake','color'=>'#10b981'],
    'q4' => ['label'=>'Vos preuves','question'=>'Quelles preuves crédibilisent votre positionnement ?','placeholder'=>'Ex : 47 ventes en 2023, 98% avis 5 étoiles, délai moyen 38 jours…','aide'=>'Les chiffres parlent : nombre de ventes, avis Google, délai moyen, certifications, passages médias…','icon'=>'fa-trophy','color'=>'#c9913b'],
    'q5' => ['label'=>'Votre anti-positionnement','question'=>'À qui ne vous adressez-vous PAS ? Que refusez-vous ?','placeholder'=>'Ex : Pas de biens surestimés, ni hors zone exclusive…','aide'=>'Dire NON clarifie votre positionnement. Ça rassure vos vrais clients : vous êtes spécialisé, pas généraliste.','icon'=>'fa-ban','color'=>'#ef4444'],
];

// ── Charger notes sauvegardées (priorité sur prefill) ──
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

// Merge : si la question n'a pas de réponse sauvegardée mais a un prefill → utiliser le prefill
foreach ($prefill as $qid => $val) {
    if (empty($notes_json[$qid]) && !empty($val)) {
        $notes_json[$qid] = $val;
    }
}

// ── API key ──
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
// AJAX (même pattern que avant — copié de v3)
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
    if ($action === 'create-localite') {
        $name = trim($_POST['name']??'');
        if (!$name) { echo json_encode(['success'=>false,'error'=>'Nom vide']); exit; }
        $slug = preg_replace('/[^a-z0-9]+/','-',strtolower(iconv('UTF-8','ASCII//TRANSLIT',$name)));
        $db->prepare("INSERT IGNORE INTO secteurs (name,slug,status,created_at) VALUES(?,?,'published',NOW())")->execute([$name,$slug]);
        echo json_encode(['success'=>true,'name'=>$name]); exit;
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
.pos{--a:#6366f1;--gold:#c9913b;font-family:'DM Sans',sans-serif;max-width:1080px;margin:0 auto;padding:24px 24px 60px}
.pos-hd{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap}
.pos-eye{font-size:.65rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;display:flex;align-items:center;gap:6px}
.pos-h1{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:#111827;margin:0 0 4px}
.pos-sub{font-size:.78rem;color:#6b7280}
/* Persona banner */
.pos-persona-banner{display:flex;align-items:center;gap:14px;padding:14px 18px;background:linear-gradient(135deg,<?= $current_persona['color'] ?>10,<?= $current_persona['color'] ?>05);border:1.5px solid <?= $current_persona['color'] ?>40;border-radius:12px;margin-bottom:16px}
.pos-pb-num{font-size:11px;font-weight:700;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;background:<?= $current_persona['color'] ?>;flex-shrink:0}
.pos-pb-info{flex:1}
.pos-pb-name{font-size:.9rem;font-weight:700;color:#111827}
.pos-pb-meta{font-size:.72rem;color:#6b7280;margin-top:2px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.pos-pb-tag{font-size:9px;font-weight:600;padding:1px 6px;border-radius:3px}
.pos-pb-dots{display:flex;gap:2px}.pos-pb-dot{width:5px;height:5px;border-radius:50%;background:#e5e7eb}.pos-pb-dot.on{background:#f59e0b}
.pos-pb-back{font-size:.72rem;font-weight:600;color:var(--a);text-decoration:none;display:flex;align-items:center;gap:4px}
.pos-pb-back:hover{text-decoration:underline}
/* Prefill notice */
.pos-prefill{display:flex;align-items:center;gap:8px;padding:8px 14px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;margin-bottom:14px;font-size:.72rem;color:#166534}
.pos-prefill i{color:#22c55e}
/* Layout + cards + questions → identique v3 mais compact */
.pos-layout{display:grid;grid-template-columns:1fr 400px;gap:16px;align-items:start}
@media(max-width:900px){.pos-layout{grid-template-columns:1fr}}
.pos-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.06);overflow:hidden}
.pos-card-hd{display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid #e5e7eb;background:#f9fafb}
.pos-card-hd-ic{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;color:#fff}
.pos-card-hd h2{font-family:'Syne',sans-serif;font-size:.85rem;font-weight:800;color:#111827;margin:0;flex:1}
.pos-card-hd span{font-size:.65rem;color:#9ca3af}
.pos-card-body{padding:18px}
.pos-qs{display:flex;flex-direction:column;gap:16px}
.pos-q-hdr{display:flex;align-items:center;gap:8px;margin-bottom:4px}
.pos-q-num{width:22px;height:22px;border-radius:5px;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;color:#fff;flex-shrink:0}
.pos-q-lbl{font-size:.7rem;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:#9ca3af}
.pos-q-saved{margin-left:auto;font-size:.6rem;color:#10b981;opacity:0;transition:opacity .3s;display:flex;align-items:center;gap:3px}
.pos-q-saved.visible{opacity:1}
.pos-q-txt{font-size:.8rem;color:#111827;font-weight:600;margin-bottom:3px;line-height:1.35}
.pos-q-aide{font-size:.68rem;color:#9ca3af;line-height:1.4;margin-bottom:6px;padding:5px 9px;background:#f8f9ff;border-radius:5px;border-left:3px solid #c7d2fe}
.pos-ta{width:100%;min-height:72px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:9px 11px;font-family:'DM Sans',sans-serif;font-size:.8rem;color:#111827;resize:vertical;outline:none;transition:border-color .15s;box-sizing:border-box}
.pos-ta:focus{border-color:var(--a);box-shadow:0 0 0 3px rgba(99,102,241,.08)}
.pos-ta.prefilled{border-color:#86efac;background:#f0fdf4}
.pos-div{height:1px;background:#e5e7eb}
/* Resume */
.pos-resume{width:100%;min-height:120px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;font-family:'DM Sans',sans-serif;font-size:.8rem;color:#111827;resize:vertical;outline:none;box-sizing:border-box;line-height:1.6}
.pos-resume:focus{border-color:var(--gold)}
.pos-ractions{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
.pos-btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:7px;font-size:.75rem;font-weight:700;cursor:pointer;border:none;transition:transform .15s}
.pos-btn-gold{background:linear-gradient(135deg,var(--gold),#a0722a);color:#fff}
.pos-btn-gold:hover{transform:translateY(-1px)}
.pos-btn-ghost{background:#f9fafb;border:1px solid #e5e7eb;color:#6b7280}
.pos-btn-ghost:hover{background:#e5e7eb}
.pos-spinner{width:12px;height:12px;border-radius:50%;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;animation:spin .7s linear infinite;display:none}
@keyframes spin{to{transform:rotate(360deg)}}
/* Chat — compact */
.pos-chat{display:flex;flex-direction:column;height:480px}
.pos-chat-msgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:8px;scrollbar-width:thin}
.pos-msg{display:flex;gap:6px;align-items:flex-end;animation:msgIn .2s ease}
@keyframes msgIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
.pos-msg-av{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;flex-shrink:0}
.pos-msg-bub{max-width:85%;padding:9px 12px;border-radius:11px;font-size:.78rem;line-height:1.5}
.pos-msg.user{flex-direction:row-reverse}
.pos-msg.user .pos-msg-av{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff}
.pos-msg.user .pos-msg-bub{background:linear-gradient(135deg,var(--a),#4f46e5);color:#fff;border-bottom-right-radius:3px}
.pos-msg.assistant .pos-msg-av{background:linear-gradient(135deg,#c9913b,#a0722a);color:#fff}
.pos-msg.assistant .pos-msg-bub{background:#f9fafb;border:1px solid #e5e7eb;color:#111827;border-bottom-left-radius:3px}
.pos-typing{display:none;align-items:center;gap:3px;padding:9px 12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:11px;width:fit-content}
.pos-typing.visible{display:flex}
.pos-typing-dot{width:5px;height:5px;border-radius:50%;background:#9ca3af;animation:td 1.2s infinite}
.pos-typing-dot:nth-child(2){animation-delay:.2s}.pos-typing-dot:nth-child(3){animation-delay:.4s}
@keyframes td{0%,80%,100%{transform:translateY(0);opacity:.4}40%{transform:translateY(-3px);opacity:1}}
.pos-chat-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:18px;text-align:center}
.pos-chat-empty i{font-size:1.6rem;color:#9ca3af}
.pos-chat-empty strong{font-size:.82rem;color:#111827}
.pos-chat-empty p{font-size:.72rem;color:#6b7280;line-height:1.4}
.pos-sugg{display:flex;flex-wrap:wrap;gap:4px;padding:0 12px 8px}
.pos-sugg-btn{padding:4px 10px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:16px;cursor:pointer;font-size:.68rem;color:#6b7280;font-family:inherit;transition:all .13s}
.pos-sugg-btn:hover{border-color:var(--a);color:var(--a)}
.pos-chat-iw{padding:8px 12px 12px;border-top:1px solid #e5e7eb;display:flex;gap:6px;align-items:flex-end}
.pos-chat-in{flex:1;min-height:36px;max-height:100px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:9px;padding:8px 11px;font-family:'DM Sans',sans-serif;font-size:.78rem;color:#111827;outline:none;resize:none;box-sizing:border-box}
.pos-chat-in:focus{border-color:var(--a)}
.pos-chat-send{width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,var(--a),#4f46e5);border:none;cursor:pointer;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
.pos-chat-send:disabled{opacity:.5;cursor:not-allowed}
.pos-toast{position:fixed;bottom:24px;right:24px;z-index:9999;background:#111827;color:#fff;padding:10px 16px;border-radius:9px;font-size:.75rem;font-weight:600;display:flex;align-items:center;gap:6px;box-shadow:0 8px 24px rgba(0,0,0,.2);transform:translateY(20px);opacity:0;transition:all .25s;pointer-events:none}
.pos-toast.show{transform:none;opacity:1}
.anim{animation:fadeUp .25s ease both}.d1{animation-delay:.05s}.d2{animation-delay:.1s}
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
</style>

<div class="pos">
    <div class="pos-hd anim">
        <div>
            <div class="pos-eye"><i class="fas fa-anchor"></i> Méthode ANCRE — Pilier A</div>
            <h1 class="pos-h1">Mon Positionnement</h1>
            <p class="pos-sub">Définissez votre différence, votre promesse et votre territoire pour ce persona.</p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:8px 14px;display:flex;align-items:center;gap:10px">
                <div style="flex:1;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;min-width:100px"><div id="cFill" style="height:100%;border-radius:3px;background:linear-gradient(90deg,var(--a),#818cf8);width:<?=$completion?>%;transition:width .5s"></div></div>
                <span id="cPct" style="font-size:.72rem;font-weight:800;color:var(--a)"><?=$completion?>%</span>
            </div>
            <a href="?page=strategie-offre&persona=<?=$persona_id?>" class="pos-btn pos-btn-ghost" style="font-size:.7rem;padding:5px 12px">Étape suivante <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>

    <!-- Persona banner -->
    <div class="pos-persona-banner anim d1">
        <span class="pos-pb-num"><?= $current_persona['id'] ?></span>
        <div class="pos-pb-info">
            <div class="pos-pb-name"><?= htmlspecialchars($current_persona['nom']) ?></div>
            <div class="pos-pb-meta">
                <?= $familyMeta[$current_persona['type']]['icon'] ?? '' ?> <?= $familyMeta[$current_persona['type']]['label'] ?? '' ?>
                <span>•</span>
                <?php $mc1 = ['Sécurité'=>['c'=>'#1e40af','bg'=>'#dbeafe'],'Liberté'=>['c'=>'#065f46','bg'=>'#d1fae5'],'Reconnaissance'=>['c'=>'#92400e','bg'=>'#fef3c7'],'Contrôle'=>['c'=>'#5b21b6','bg'=>'#ede9fe']]; ?>
                <span class="pos-pb-tag" style="background:<?= $mc1[$current_persona['m1']]['bg'] ?? '#eee' ?>;color:<?= $mc1[$current_persona['m1']]['c'] ?? '#666' ?>"><?= $current_persona['m1'] ?></span>
                <span>+</span>
                <span class="pos-pb-tag" style="background:<?= $mc1[$current_persona['m2']]['bg'] ?? '#eee' ?>;color:<?= $mc1[$current_persona['m2']]['c'] ?? '#666' ?>"><?= $current_persona['m2'] ?></span>
                <span>•</span>
                <span class="pos-pb-dots"><?php for($i=1;$i<=5;$i++):?><span class="pos-pb-dot<?=$i<=$current_persona['conscience']?' on':''?>"></span><?php endfor;?></span> <?=$current_persona['conscience']?>/5
            </div>
        </div>
        <a href="?page=neuropersona" class="pos-pb-back"><i class="fas fa-arrow-left"></i> Changer de persona</a>
    </div>

    <?php
    $hasPrefill = !empty(array_filter($prefill));
    if ($hasPrefill): ?>
    <div class="pos-prefill anim d1">
        <i class="fas fa-magic"></i>
        Les réponses ont été pré-remplies depuis votre profil conseiller (advisor context). Vous pouvez les modifier librement.
    </div>
    <?php endif; ?>

    <?php if(!$has_api_key):?><div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:8px 12px;margin-bottom:12px;font-size:.72rem;color:#92400e;display:flex;align-items:center;gap:6px" class="anim"><i class="fas fa-triangle-exclamation"></i>Clé API non configurée — <a href="?page=api-keys" style="color:#92400e;font-weight:700">Configurer →</a></div><?php endif;?>

    <div class="pos-layout">
        <div style="display:flex;flex-direction:column;gap:14px">

            <div class="pos-card anim d1">
                <div class="pos-card-hd"><div class="pos-card-hd-ic" style="background:linear-gradient(135deg,#6366f1,#4f46e5)"><i class="fas fa-list-check"></i></div><h2>Questions guidées</h2><span>Sauvegarde auto</span></div>
                <div class="pos-card-body">
                    <div class="pos-qs" id="qForm">
                        <?php foreach($questions as $qid=>$q): $i=array_search($qid,array_keys($questions))+1; $val=$notes_json[$qid]??''; $isPrefilled=!empty($val)&&!empty($prefill[$qid])&&$val===$prefill[$qid]; ?>
                        <div class="pos-question">
                            <div class="pos-q-hdr">
                                <div class="pos-q-num" style="background:<?=$q['color']?>"><?=$i?></div>
                                <div class="pos-q-lbl"><?=htmlspecialchars($q['label'])?></div>
                                <div class="pos-q-saved" id="saved-<?=$qid?>"><i class="fas fa-check-circle"></i> Sauvegardé</div>
                            </div>
                            <div class="pos-q-txt"><?=htmlspecialchars($q['question'])?></div>
                            <div class="pos-q-aide"><i class="fas fa-lightbulb" style="color:#f59e0b;margin-right:3px"></i> <?=htmlspecialchars($q['aide'])?></div>
                            <textarea class="pos-ta<?=$isPrefilled?' prefilled':''?>" id="in-<?=$qid?>" data-qid="<?=$qid?>" placeholder="<?=htmlspecialchars($q['placeholder'])?>"><?=htmlspecialchars($val)?></textarea>
                        </div>
                        <?php if($i<count($questions)):?><div class="pos-div"></div><?php endif;?>
                        <?php endforeach;?>
                    </div>
                </div>
            </div>

            <div class="pos-card anim d2">
                <div class="pos-card-hd"><div class="pos-card-hd-ic" style="background:linear-gradient(135deg,#c9913b,#a0722a)"><i class="fas fa-sparkles"></i></div><h2>Résumé de positionnement</h2><span id="rBadge" style="color:#10b981;font-size:.6rem;<?=$resume_ia?'':'display:none'?>"><i class="fas fa-check-circle"></i></span></div>
                <div class="pos-card-body">
                    <textarea class="pos-resume" id="resumeArea" placeholder="Cliquez « Générer » pour un résumé IA…"><?=htmlspecialchars($resume_ia)?></textarea>
                    <div class="pos-ractions">
                        <button class="pos-btn pos-btn-gold" id="genBtn" onclick="genResume()"><i class="fas fa-wand-magic-sparkles"></i> Générer<div class="pos-spinner" id="genSp"></div></button>
                        <button class="pos-btn pos-btn-ghost" onclick="saveR()"><i class="fas fa-save"></i> Sauver</button>
                        <button class="pos-btn pos-btn-ghost" onclick="navigator.clipboard.writeText(document.getElementById('resumeArea').value).then(()=>toast('Copié'))" style="margin-left:auto"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <div class="pos-card anim d2" style="position:sticky;top:20px">
            <div class="pos-card-hd"><div class="pos-card-hd-ic" style="background:linear-gradient(135deg,#6366f1,#4f46e5)"><i class="fas fa-robot"></i></div><h2>Assistant IA</h2><button onclick="clearChat()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:.65rem"><i class="fas fa-trash-can"></i></button></div>
            <div class="pos-chat">
                <div class="pos-chat-msgs" id="chatMsgs">
                    <?php if(empty($chat_hist)):?>
                    <div class="pos-chat-empty" id="chatEmpty"><i class="fas fa-comments"></i><div><strong>Assistant positionnement</strong><p>Je connais votre profil, vos réponses et la psychologie du persona #<?=$current_persona['id']?>. Posez-moi vos questions.</p></div></div>
                    <?php else: foreach($chat_hist as $m):?>
                    <div class="pos-msg <?=htmlspecialchars($m['role'])?>"><div class="pos-msg-av"><?=$m['role']==='user'?'Moi':'IA'?></div><div class="pos-msg-bub"><?=nl2br(htmlspecialchars($m['content']))?></div></div>
                    <?php endforeach; endif;?>
                    <div class="pos-msg assistant" id="typing" style="display:none"><div class="pos-msg-av">IA</div><div class="pos-typing visible"><div class="pos-typing-dot"></div><div class="pos-typing-dot"></div><div class="pos-typing-dot"></div></div></div>
                </div>
                <?php if(empty($chat_hist)):?>
                <div class="pos-sugg" id="chatSugg">
                    <button class="pos-sugg-btn" onclick="sug(this)">Comment me différencier ?</button>
                    <button class="pos-sugg-btn" onclick="sug(this)">Quelle promesse pour ce persona ?</button>
                    <button class="pos-sugg-btn" onclick="sug(this)">Analyse mon positionnement</button>
                </div>
                <?php endif;?>
                <div class="pos-chat-iw">
                    <textarea class="pos-chat-in" id="chatIn" placeholder="Posez votre question…" rows="1" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendChat()}" oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"></textarea>
                    <button class="pos-chat-send" id="chatBtn" onclick="sendChat()"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="pos-toast" id="toast"><i class="fas fa-check-circle" style="color:#10b981"></i><span id="toastMsg"></span></div>

<script>
(function(){
const C=<?=json_encode($csrf)?>,PID=<?=json_encode($persona_id)?>,API=<?=json_encode($has_api_key)?>,
U='?page=strategie-positionnement&persona='+PID,
PN=<?=json_encode($current_persona['nom'])?>,M1=<?=json_encode($current_persona['m1'])?>,
M2=<?=json_encode($current_persona['m2'])?>,PC=<?=json_encode($current_persona['conscience'])?>,
QS=<?=json_encode($questions)?>;
let hist=<?=json_encode($chat_hist)?>,sTO=null,busy=false;

function toast(m){const t=document.getElementById('toast');document.getElementById('toastMsg').textContent=m;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2800)}
function notes(){const n={};document.querySelectorAll('.pos-ta[data-qid]').forEach(t=>{n[t.dataset.qid]=t.value.trim()});return n}
function summary(){const n=notes();let t='';Object.entries(QS).forEach(([k,q])=>{t+=`\n${q.label}: ${n[k]||'(vide)'}`});return t}
async function post(d){const f=new FormData;f.append('csrf',C);Object.entries(d).forEach(([k,v])=>f.append(k,typeof v==='object'?JSON.stringify(v):v));return(await fetch(U,{method:'POST',body:f})).json()}

// Save
function sched(q){clearTimeout(sTO);sTO=setTimeout(()=>save(q),900)}
async function save(q){const d=await post({action:'save-notes',persona_id:PID,notes:JSON.stringify(notes())});if(d.success){document.getElementById('cFill').style.width=d.completion+'%';document.getElementById('cPct').textContent=d.completion+'%';if(q){const b=document.getElementById('saved-'+q);if(b){b.classList.add('visible');setTimeout(()=>b.classList.remove('visible'),2000)}}}}
document.querySelectorAll('.pos-ta[data-qid]').forEach(t=>{t.addEventListener('input',()=>{t.classList.remove('prefilled');sched(t.dataset.qid)});t.addEventListener('blur',()=>save(t.dataset.qid))});

// AI
async function ai(p,m,s){if(!API){toast('Clé API manquante');return null}const f=new FormData;f.append('csrf',C);f.append('action','ai-proxy');if(p)f.append('prompt',p);if(m)f.append('messages',JSON.stringify(m));if(s)f.append('system',s);const d=await(await fetch(U,{method:'POST',body:f})).json();if(!d.success)throw new Error(d.error||'Erreur');return d.text}

// Resume
window.genResume=async function(){const b=document.getElementById('genBtn'),s=document.getElementById('genSp');b.disabled=true;s.style.display='inline-block';
try{const r=await ai(`Expert positionnement immobilier + neuromarketing.\nPersona: ${PN}\nMotivations: ${M1}+${M2}\nConscience: ${PC}/5\n${summary()}\n\nRésumé percutant 5-7 phrases. Territoire, différence, promesse, preuves. Résonner avec ${M1}. Première personne, direct.`);
if(r){document.getElementById('resumeArea').value=r;await saveR()}}catch(e){toast(e.message)}finally{b.disabled=false;s.style.display='none'}};
window.saveR=async function(){const d=await post({action:'save-resume',persona_id:PID,resume:document.getElementById('resumeArea').value});if(d.success){document.getElementById('rBadge').style.display='';toast('Sauvegardé')}};

// Chat
function addMsg(r,c){document.getElementById('chatEmpty')?.remove();document.getElementById('chatSugg')?.remove();const w=document.getElementById('chatMsgs'),d=document.createElement('div');d.className='pos-msg '+r;d.innerHTML=`<div class="pos-msg-av">${r==='user'?'Moi':'IA'}</div><div class="pos-msg-bub">${c.replace(/\n/g,'<br>')}</div>`;w.insertBefore(d,document.getElementById('typing'));w.scrollTop=w.scrollHeight}
function typ(on){document.getElementById('typing').style.display=on?'flex':'none';if(on)document.getElementById('chatMsgs').scrollTop=99999}
window.sug=function(b){document.getElementById('chatIn').value=b.textContent.trim();sendChat()};
window.sendChat=async function(){if(busy)return;const i=document.getElementById('chatIn'),m=i.value.trim();if(!m)return;busy=true;document.getElementById('chatBtn').disabled=true;i.value='';i.style.height='auto';addMsg('user',m);hist.push({role:'user',content:m});typ(true);
const sys=`Expert positionnement immobilier + neuromarketing.\n<?=addslashes($persona_context)?>\nRéponses:\n${summary()}\nConcis, pratique, focalisé positionnement.`;
const msgs=hist.slice(-12).map(x=>({role:x.role,content:x.content}));
try{const a=await ai(null,msgs,sys);hist.push({role:'assistant',content:a});typ(false);addMsg('assistant',a);post({action:'save-chat',persona_id:PID,history:JSON.stringify(hist)})}
catch(e){typ(false);addMsg('assistant','Erreur: '+e.message)}finally{busy=false;document.getElementById('chatBtn').disabled=false}};
window.clearChat=function(){if(!confirm('Effacer ?'))return;hist=[];document.getElementById('chatMsgs').innerHTML='<div class="pos-chat-empty" id="chatEmpty"><i class="fas fa-comments"></i><div><strong>Assistant</strong><p>Posez vos questions.</p></div></div><div class="pos-msg assistant" id="typing" style="display:none"><div class="pos-msg-av">IA</div><div class="pos-typing visible"><div class="pos-typing-dot"></div><div class="pos-typing-dot"></div><div class="pos-typing-dot"></div></div></div>';post({action:'save-chat',persona_id:PID,history:'[]'});toast('Effacé')};
const cw=document.getElementById('chatMsgs');if(cw)cw.scrollTop=cw.scrollHeight;
})();
</script>