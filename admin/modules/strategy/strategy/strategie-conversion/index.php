<?php
/**
 * ══════════════════════════════════════════════════════════════
 * Page 6 : Ma Conversion (Méthode ANCRE — Pilier E)
 * /admin/modules/strategy/strategie-conversion/index.php
 * ══════════════════════════════════════════════════════════════
 * 4 blocs entonnoir : Capture → Qualification → Nurturing → Closing
 * Charge le contexte chaîné positionnement + offre + contenu + trafic
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

$db       = getDB();
$instance = INSTANCE_ID;
$etape    = 'conversion';

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
// 4 étapes de l'entonnoir
// ══════════════════════════════════════════════════════════════
$funnel = [
    'capture' => [
        'label' => 'Points de capture',
        'icon'  => 'fa-magnet',
        'color' => '#6366f1',
        'num'   => '1',
        'desc'  => 'Comment captez-vous les coordonnées de ce persona ? Formulaires, landing pages, lead magnets.',
        'fields' => [
            'lead_magnets' => ['label'=>'Lead magnets / offres gratuites','placeholder'=>'Ex : guide "Les 7 erreurs du primo-accédant", checklist visite, estimation en ligne…','hint'=>'Que donnez-vous en échange de l\'email ? Le lead magnet doit répondre au problème du persona à son niveau de conscience.','rows'=>3],
            'landing_pages'=> ['label'=>'Landing pages et formulaires','placeholder'=>'Ex : page estimation gratuite, page guide à télécharger, popup exit intent sur blog…','hint'=>'Où sont vos formulaires de capture ? Pages dédiées, popups, formulaires en fin d\'article, chatbot.','rows'=>3],
            'cta_strategy' => ['label'=>'Stratégie CTA par canal','placeholder'=>'Ex : CTA blog → guide gratuit, CTA Facebook → estimation, CTA GMB → appel direct…','hint'=>'Chaque canal de trafic doit avoir un CTA adapté. Mappez vos CTAs par source de trafic.','rows'=>2],
        ],
    ],
    'qualification' => [
        'label' => 'Qualification des leads',
        'icon'  => 'fa-filter',
        'color' => '#f59e0b',
        'num'   => '2',
        'desc'  => 'Comment triez-vous les leads ? Scoring, critères de qualification, segmentation.',
        'fields' => [
            'scoring'   => ['label'=>'Critères de scoring','placeholder'=>'Ex : +10 pts estimation demandée, +5 pts article lu, +20 pts RDV pris, -10 pts inactif 30j…','hint'=>'Quels comportements indiquent un lead chaud ? Définissez vos critères de scoring pour prioriser vos efforts.','rows'=>3],
            'segments'  => ['label'=>'Segments de leads','placeholder'=>'Ex : Froid (téléchargement guide), Tiède (2+ visites site), Chaud (estimation demandée), Brûlant (RDV confirmé)…','hint'=>'Catégorisez vos leads par température. Chaque segment recevra un traitement différent.','rows'=>3],
            'questions' => ['label'=>'Questions de qualification','placeholder'=>'Ex : projet dans les 3 mois ?, budget défini ?, financement validé ?, zone recherchée ?…','hint'=>'Les questions clés pour qualifier rapidement ce persona lors du premier contact.','rows'=>2],
        ],
    ],
    'nurturing' => [
        'label' => 'Nurturing & relances',
        'icon'  => 'fa-envelope-open-text',
        'color' => '#8b5cf6',
        'num'   => '3',
        'desc'  => 'Comment réchauffez-vous les leads froids ? Séquences email, contenu de réassurance, relances.',
        'fields' => [
            'sequence'  => ['label'=>'Séquence email principale','placeholder'=>'Ex : J+0 guide bienvenue, J+3 témoignage client similaire, J+7 article conseil, J+14 offre RDV gratuit…','hint'=>'La séquence automatique après capture. Adaptez le contenu au niveau de conscience et aux motivations du persona.','rows'=>4],
            'content'   => ['label'=>'Contenu de réassurance','placeholder'=>'Ex : témoignages vidéo clients, FAQ détaillée, étude de cas, garanties, certifications…','hint'=>'Quels contenus lèvent les objections et renforcent la confiance de ce persona spécifique ?','rows'=>3],
            'relance'   => ['label'=>'Processus de relance','placeholder'=>'Ex : lead inactif 7j → email relance, 14j → SMS, 30j → appel, 60j → contenu re-engagement…','hint'=>'Comment relancez-vous les leads qui ne convertissent pas immédiatement ? Cadence et canaux.','rows'=>2],
        ],
    ],
    'closing' => [
        'label' => 'Closing & signature',
        'icon'  => 'fa-handshake',
        'color' => '#10b981',
        'num'   => '4',
        'desc'  => 'Comment transformez-vous un lead chaud en client signé ? Processus RDV, objections, signature.',
        'fields' => [
            'process'    => ['label'=>'Processus de vente','placeholder'=>'Ex : 1er appel 15min → RDV physique estimation → Proposition mandat → Suivi 48h → Signature…','hint'=>'Décrivez votre tunnel de vente étape par étape. Du premier contact au mandat signé.','rows'=>3],
            'objections' => ['label'=>'Objections principales et réponses','placeholder'=>'Ex : "Trop cher" → comparatif valeur ajoutée, "Je veux réfléchir" → deadline offre limitée…','hint'=>'Les 3-5 objections les plus fréquentes de ce persona + votre réponse préparée pour chacune.','rows'=>4],
            'garanties'  => ['label'=>'Garanties et engagement','placeholder'=>'Ex : garantie vente 90 jours, estimation offerte, aucun frais si pas de vente, engagement qualité écrit…','hint'=>'Quelles garanties éliminent le risque perçu par ce persona ? Adaptez à sa motivation principale.','rows'=>2],
        ],
    ],
];

// ══════════════════════════════════════════════════════════════
// Charger notes sauvegardées
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

foreach ($funnel as $fk => $step) {
    if (!isset($notes_json[$fk]) || !is_array($notes_json[$fk])) $notes_json[$fk] = [];
    foreach ($step['fields'] as $ffk => $field) {
        if (!isset($notes_json[$fk][$ffk])) $notes_json[$fk][$ffk] = '';
    }
}

// ══════════════════════════════════════════════════════════════
// Charger contexte chaîné complet
// ══════════════════════════════════════════════════════════════
$chainContext = '';

// Positionnement + Offre (questions q1-q5)
$prevQSteps = [
    'positionnement' => ['q1'=>'Territoire','q2'=>'Différence','q3'=>'Promesse','q4'=>'Preuves','q5'=>'Anti-positionnement'],
    'offre'          => ['q1'=>'Offre principale','q2'=>'Problème résolu','q3'=>'Solution unique','q4'=>'Résultat promis','q5'=>'Preuve sociale'],
];
foreach ($prevQSteps as $prevEtape => $labels) {
    try {
        $stmt = $db->prepare("SELECT notes_json, resume_ia FROM strategy_notes WHERE instance_id=:i AND persona_id=:p AND etape=:e LIMIT 1");
        $stmt->execute([':i'=>$instance,':p'=>$persona_id,':e'=>$prevEtape]);
        $pNote = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pNote) {
            $pNotes = json_decode($pNote['notes_json'] ?? '{}', true) ?: [];
            $parts = [];
            foreach ($labels as $k => $l) { if (!empty($pNotes[$k])) $parts[] = "$l: {$pNotes[$k]}"; }
            if ($parts) $chainContext .= strtoupper($prevEtape) . ":\n" . implode("\n", $parts) . "\n";
            if (!empty($pNote['resume_ia'])) $chainContext .= "Résumé: " . $pNote['resume_ia'] . "\n\n";
        }
    } catch (Exception $e) {}
}

// Contenu (5 niveaux)
try {
    $stmt = $db->prepare("SELECT notes_json, resume_ia FROM strategy_notes WHERE instance_id=:i AND persona_id=:p AND etape='contenu' LIMIT 1");
    $stmt->execute([':i'=>$instance,':p'=>$persona_id]);
    $cNote = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cNote) {
        $cNotes = json_decode($cNote['notes_json'] ?? '{}', true) ?: [];
        $cParts = [];
        for ($n = 1; $n <= 5; $n++) {
            $nk = 'n' . $n;
            if (!empty($cNotes[$nk]) && is_array($cNotes[$nk])) {
                $t = $cNotes[$nk]['titre'] ?? ''; $tp = $cNotes[$nk]['type'] ?? '';
                if ($t || $tp) $cParts[] = "Niveau $n: $tp — $t";
            }
        }
        if ($cParts) $chainContext .= "CONTENU:\n" . implode("\n", $cParts) . "\n";
        if (!empty($cNote['resume_ia'])) $chainContext .= "Résumé contenu: " . $cNote['resume_ia'] . "\n\n";
    }
} catch (Exception $e) {}

// Trafic (4 canaux)
try {
    $stmt = $db->prepare("SELECT notes_json, resume_ia FROM strategy_notes WHERE instance_id=:i AND persona_id=:p AND etape='trafic' LIMIT 1");
    $stmt->execute([':i'=>$instance,':p'=>$persona_id]);
    $tNote = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($tNote) {
        $tNotes = json_decode($tNote['notes_json'] ?? '{}', true) ?: [];
        $tParts = [];
        foreach (['seo'=>'SEO Local','social'=>'Réseaux sociaux','ads'=>'Pub payante','network'=>'Réseau'] as $ck => $cl) {
            if (!empty($tNotes[$ck]) && is_array($tNotes[$ck])) {
                $vals = array_filter($tNotes[$ck], fn($v) => trim((string)$v) !== '');
                if ($vals) $tParts[] = "$cl: " . implode(' | ', $vals);
            }
        }
        if ($tParts) $chainContext .= "TRAFIC:\n" . implode("\n", $tParts) . "\n";
        if (!empty($tNote['resume_ia'])) $chainContext .= "Résumé trafic: " . $tNote['resume_ia'] . "\n\n";
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
$currentStepIdx = 4;

$stepsWithData = [];
try {
    $stmt = $db->prepare("SELECT etape, completion FROM strategy_notes WHERE instance_id=:i AND persona_id=:p");
    $stmt->execute([':i'=>$instance,':p'=>$persona_id]);
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) { $stepsWithData[$r['etape']] = (int)$r['completion']; }
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
        $filled = 0;
        foreach ($funnel as $fk => $step) {
            if (isset($nn[$fk]) && is_array($nn[$fk])) {
                $fc = count(array_filter($nn[$fk], fn($v) => trim((string)$v) !== ''));
                if ($fc >= 2) $filled++;
            }
        }
        $c = (int)round(($filled / count($funnel)) * 100);
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
.cvr{--a:#6366f1;--gold:#c9913b;font-family:'DM Sans',sans-serif;max-width:1080px;margin:0 auto;padding:24px 24px 60px}
.cvr-hd{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap}
.cvr-eye{font-size:.65rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;display:flex;align-items:center;gap:6px}
.cvr-h1{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:#111827;margin:0 0 4px}
.cvr-sub{font-size:.78rem;color:#6b7280}
/* Nav ANCRE */
.cvr-nav{display:flex;gap:4px;margin-bottom:16px;flex-wrap:wrap}
.cvr-nav-step{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:.72rem;font-weight:600;text-decoration:none;border:1.5px solid #e5e7eb;color:#9ca3af;background:#fff;transition:all .15s}
.cvr-nav-step.active{border-color:var(--a);background:linear-gradient(135deg,#6366f110,#6366f105);color:var(--a);font-weight:800}
.cvr-nav-step.done{border-color:#86efac;color:#166534;background:#f0fdf4}
.cvr-nav-step.done:hover{border-color:#22c55e}
.cvr-nav-step.locked{opacity:.45;pointer-events:none}
.cvr-nav-step i{font-size:.6rem}
.cvr-nav-letter{width:18px;height:18px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.55rem;font-weight:800;color:#fff;background:#d1d5db;flex-shrink:0}
.cvr-nav-step.active .cvr-nav-letter{background:var(--a)}
.cvr-nav-step.done .cvr-nav-letter{background:#22c55e}
.cvr-nav-chevron{color:#d1d5db;font-size:.55rem;margin:0 2px}
/* Persona banner */
.cvr-pb{display:flex;align-items:center;gap:14px;padding:14px 18px;background:linear-gradient(135deg,<?=$current_persona['color']?>10,<?=$current_persona['color']?>05);border:1.5px solid <?=$current_persona['color']?>40;border-radius:12px;margin-bottom:16px}
.cvr-pb-num{font-size:11px;font-weight:700;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;background:<?=$current_persona['color']?>;flex-shrink:0}
.cvr-pb-info{flex:1}
.cvr-pb-name{font-size:.9rem;font-weight:700;color:#111827}
.cvr-pb-meta{font-size:.72rem;color:#6b7280;margin-top:2px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.cvr-pb-tag{font-size:9px;font-weight:600;padding:1px 6px;border-radius:3px}
.cvr-pb-dots{display:flex;gap:2px}.cvr-pb-dot{width:5px;height:5px;border-radius:50%;background:#e5e7eb}.cvr-pb-dot.on{background:#f59e0b}
.cvr-pb-back{font-size:.72rem;font-weight:600;color:var(--a);text-decoration:none;display:flex;align-items:center;gap:4px}
.cvr-pb-back:hover{text-decoration:underline}
/* Context */
.cvr-ctx{display:flex;align-items:flex-start;gap:8px;padding:10px 14px;background:#f8f9ff;border:1px solid #c7d2fe;border-radius:8px;margin-bottom:14px;font-size:.72rem;color:#4338ca;line-height:1.4}
.cvr-ctx i{color:#6366f1;margin-top:2px;flex-shrink:0}
.cvr-ctx-toggle{background:none;border:none;color:#6366f1;font-weight:700;cursor:pointer;font-size:.68rem;text-decoration:underline;padding:0;margin-top:4px}
.cvr-ctx-detail{display:none;margin-top:6px;padding:8px 10px;background:#fff;border:1px solid #e0e7ff;border-radius:6px;font-size:.7rem;color:#374151;white-space:pre-wrap;line-height:1.5;max-height:200px;overflow-y:auto}
.cvr-ctx-detail.visible{display:block}
/* Layout */
.cvr-layout{display:grid;grid-template-columns:1fr 400px;gap:16px;align-items:start}
@media(max-width:900px){.cvr-layout{grid-template-columns:1fr}}
.cvr-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.06);overflow:hidden}
.cvr-card-hd{display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid #e5e7eb;background:#f9fafb}
.cvr-card-hd-ic{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;color:#fff}
.cvr-card-hd h2{font-family:'Syne',sans-serif;font-size:.85rem;font-weight:800;color:#111827;margin:0;flex:1}
.cvr-card-hd span{font-size:.65rem;color:#9ca3af}
.cvr-card-body{padding:18px}
/* Funnel step */
.cvr-step{border:1.5px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:14px;position:relative}
.cvr-step:last-child{margin-bottom:0}
/* Funnel connector line */
.cvr-step:not(:last-child)::after{content:'';position:absolute;bottom:-14px;left:22px;width:2px;height:14px;background:linear-gradient(to bottom,#e5e7eb,transparent)}
.cvr-step-hd{display:flex;align-items:center;gap:10px;padding:10px 14px;cursor:pointer;transition:background .15s}
.cvr-step-hd:hover{background:#f9fafb}
.cvr-step-num{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;color:#fff;flex-shrink:0}
.cvr-step-info{flex:1}
.cvr-step-label{font-size:.8rem;font-weight:700;color:#111827}
.cvr-step-desc{font-size:.68rem;color:#9ca3af;margin-top:1px}
.cvr-step-dot{width:7px;height:7px;border-radius:50%;background:#e5e7eb;flex-shrink:0}
.cvr-step-dot.filled{background:#22c55e}
.cvr-step-chevron{color:#d1d5db;font-size:.6rem;transition:transform .2s;flex-shrink:0}
.cvr-step.open .cvr-step-chevron{transform:rotate(90deg)}
.cvr-step-body{display:none;padding:12px 14px;border-top:1px solid #e5e7eb;background:#fafbfc}
.cvr-step.open .cvr-step-body{display:block}
.cvr-field{margin-bottom:12px}
.cvr-field:last-child{margin-bottom:0}
.cvr-field-label{font-size:.7rem;font-weight:700;color:#374151;margin-bottom:3px;display:flex;align-items:center;gap:6px}
.cvr-field-label i{font-size:.6rem;color:#9ca3af}
.cvr-field-hint{font-size:.65rem;color:#9ca3af;margin-bottom:4px;line-height:1.3}
.cvr-ta{width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:9px 11px;font-family:'DM Sans',sans-serif;font-size:.8rem;color:#111827;resize:vertical;outline:none;transition:border-color .15s;box-sizing:border-box}
.cvr-ta:focus{border-color:var(--a);box-shadow:0 0 0 3px rgba(99,102,241,.08)}
.cvr-gen-row{display:flex;gap:6px;margin-top:6px}
.cvr-btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:7px;font-size:.72rem;font-weight:700;cursor:pointer;border:none;transition:transform .15s}
.cvr-btn-sm{padding:5px 10px;font-size:.68rem}
.cvr-btn-accent{background:linear-gradient(135deg,var(--a),#4f46e5);color:#fff}
.cvr-btn-accent:hover{transform:translateY(-1px)}
.cvr-btn-gold{background:linear-gradient(135deg,var(--gold),#a0722a);color:#fff}
.cvr-btn-gold:hover{transform:translateY(-1px)}
.cvr-btn-ghost{background:#f9fafb;border:1px solid #e5e7eb;color:#6b7280}
.cvr-btn-ghost:hover{background:#e5e7eb}
.cvr-spinner{width:12px;height:12px;border-radius:50%;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;animation:spin .7s linear infinite;display:none}
@keyframes spin{to{transform:rotate(360deg)}}
.cvr-saved{font-size:.6rem;color:#10b981;opacity:0;transition:opacity .3s;display:flex;align-items:center;gap:3px;margin-left:auto}
.cvr-saved.visible{opacity:1}
/* Resume */
.cvr-resume{width:100%;min-height:120px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;font-family:'DM Sans',sans-serif;font-size:.8rem;color:#111827;resize:vertical;outline:none;box-sizing:border-box;line-height:1.6}
.cvr-resume:focus{border-color:var(--gold)}
.cvr-ractions{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
/* Chat */
.cvr-chat{display:flex;flex-direction:column;height:520px}
.cvr-chat-msgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:8px;scrollbar-width:thin}
.cvr-msg{display:flex;gap:6px;align-items:flex-end;animation:msgIn .2s ease}
@keyframes msgIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
.cvr-msg-av{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;flex-shrink:0}
.cvr-msg-bub{max-width:85%;padding:9px 12px;border-radius:11px;font-size:.78rem;line-height:1.5}
.cvr-msg.user{flex-direction:row-reverse}
.cvr-msg.user .cvr-msg-av{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff}
.cvr-msg.user .cvr-msg-bub{background:linear-gradient(135deg,var(--a),#4f46e5);color:#fff;border-bottom-right-radius:3px}
.cvr-msg.assistant .cvr-msg-av{background:linear-gradient(135deg,#c9913b,#a0722a);color:#fff}
.cvr-msg.assistant .cvr-msg-bub{background:#f9fafb;border:1px solid #e5e7eb;color:#111827;border-bottom-left-radius:3px}
.cvr-typing{display:none;align-items:center;gap:3px;padding:9px 12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:11px;width:fit-content}
.cvr-typing.visible{display:flex}
.cvr-typing-dot{width:5px;height:5px;border-radius:50%;background:#9ca3af;animation:td 1.2s infinite}
.cvr-typing-dot:nth-child(2){animation-delay:.2s}.cvr-typing-dot:nth-child(3){animation-delay:.4s}
@keyframes td{0%,80%,100%{transform:translateY(0);opacity:.4}40%{transform:translateY(-3px);opacity:1}}
.cvr-chat-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:18px;text-align:center}
.cvr-chat-empty i{font-size:1.6rem;color:#9ca3af}
.cvr-chat-empty strong{font-size:.82rem;color:#111827}
.cvr-chat-empty p{font-size:.72rem;color:#6b7280;line-height:1.4}
.cvr-sugg{display:flex;flex-wrap:wrap;gap:4px;padding:0 12px 8px}
.cvr-sugg-btn{padding:4px 10px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:16px;cursor:pointer;font-size:.68rem;color:#6b7280;font-family:inherit;transition:all .13s}
.cvr-sugg-btn:hover{border-color:var(--a);color:var(--a)}
.cvr-chat-iw{padding:8px 12px 12px;border-top:1px solid #e5e7eb;display:flex;gap:6px;align-items:flex-end}
.cvr-chat-in{flex:1;min-height:36px;max-height:100px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:9px;padding:8px 11px;font-family:'DM Sans',sans-serif;font-size:.78rem;color:#111827;outline:none;resize:none;box-sizing:border-box}
.cvr-chat-in:focus{border-color:var(--a)}
.cvr-chat-send{width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,var(--a),#4f46e5);border:none;cursor:pointer;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
.cvr-chat-send:disabled{opacity:.5;cursor:not-allowed}
.cvr-toast{position:fixed;bottom:24px;right:24px;z-index:9999;background:#111827;color:#fff;padding:10px 16px;border-radius:9px;font-size:.75rem;font-weight:600;display:flex;align-items:center;gap:6px;box-shadow:0 8px 24px rgba(0,0,0,.2);transform:translateY(20px);opacity:0;transition:all .25s;pointer-events:none}
.cvr-toast.show{transform:none;opacity:1}
.anim{animation:fadeUp .25s ease both}.d1{animation-delay:.05s}.d2{animation-delay:.1s}
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
</style>

<div class="cvr">
    <div class="cvr-hd anim">
        <div>
            <div class="cvr-eye"><i class="fas fa-funnel-dollar"></i> Méthode ANCRE — Pilier E</div>
            <h1 class="cvr-h1">Ma Conversion</h1>
            <p class="cvr-sub">Transformez votre trafic en leads puis en clients : capture, qualification, nurturing, closing.</p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:8px 14px;display:flex;align-items:center;gap:10px">
                <div style="flex:1;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;min-width:100px"><div id="cFill" style="height:100%;border-radius:3px;background:linear-gradient(90deg,var(--a),#818cf8);width:<?=$completion?>%;transition:width .5s"></div></div>
                <span id="cPct" style="font-size:.72rem;font-weight:800;color:var(--a)"><?=$completion?>%</span>
            </div>
        </div>
    </div>

    <!-- Navigation ANCRE -->
    <div class="cvr-nav anim d1">
        <?php foreach ($ancreSteps as $idx => $step):
            $stepEtape = str_replace('strategie-', '', $step['slug']);
            $isDone = isset($stepsWithData[$stepEtape]) && $stepsWithData[$stepEtape] > 0;
            $isActive = $idx === $currentStepIdx;
            $isLocked = !$isActive && !$isDone && $idx > $currentStepIdx;
            $cls = $isActive ? 'active' : ($isDone ? 'done' : ($isLocked ? 'locked' : ''));
        ?>
            <?php if ($idx > 0): ?><span class="cvr-nav-chevron"><i class="fas fa-chevron-right"></i></span><?php endif; ?>
            <a href="?page=<?=$step['slug']?>&persona=<?=$persona_id?>" class="cvr-nav-step <?=$cls?>">
                <span class="cvr-nav-letter"><?=$step['letter']?></span>
                <i class="fas <?=$step['icon']?>"></i> <?=$step['label']?>
                <?php if ($isDone && !$isActive): ?><i class="fas fa-check" style="font-size:.55rem;color:#22c55e"></i><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Persona banner -->
    <div class="cvr-pb anim d1">
        <span class="cvr-pb-num"><?=$current_persona['id']?></span>
        <div class="cvr-pb-info">
            <div class="cvr-pb-name"><?=htmlspecialchars($current_persona['nom'])?></div>
            <div class="cvr-pb-meta">
                <?=$familyMeta[$current_persona['type']]['icon']??''?> <?=$familyMeta[$current_persona['type']]['label']??''?>
                <span>•</span>
                <?php $mc1=['Sécurité'=>['c'=>'#1e40af','bg'=>'#dbeafe'],'Liberté'=>['c'=>'#065f46','bg'=>'#d1fae5'],'Reconnaissance'=>['c'=>'#92400e','bg'=>'#fef3c7'],'Contrôle'=>['c'=>'#5b21b6','bg'=>'#ede9fe']]; ?>
                <span class="cvr-pb-tag" style="background:<?=$mc1[$current_persona['m1']]['bg']??'#eee'?>;color:<?=$mc1[$current_persona['m1']]['c']??'#666'?>"><?=$current_persona['m1']?></span>
                <span>+</span>
                <span class="cvr-pb-tag" style="background:<?=$mc1[$current_persona['m2']]['bg']??'#eee'?>;color:<?=$mc1[$current_persona['m2']]['c']??'#666'?>"><?=$current_persona['m2']?></span>
                <span>•</span>
                <span class="cvr-pb-dots"><?php for($i=1;$i<=5;$i++):?><span class="cvr-pb-dot<?=$i<=$current_persona['conscience']?' on':''?>"></span><?php endfor;?></span> <?=$current_persona['conscience']?>/5
            </div>
        </div>
        <a href="?page=neuropersona" class="cvr-pb-back"><i class="fas fa-arrow-left"></i> Changer</a>
    </div>

    <!-- Contexte chaîné -->
    <?php if ($hasChainContext): ?>
    <div class="cvr-ctx anim d1">
        <i class="fas fa-link"></i>
        <div>
            <strong>Contexte chargé :</strong> positionnement + offre + contenu + trafic intégrés.
            <br><button class="cvr-ctx-toggle" onclick="document.getElementById('ctxDetail').classList.toggle('visible')">Voir le contexte</button>
            <div class="cvr-ctx-detail" id="ctxDetail"><?=nl2br(htmlspecialchars($chainContext))?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(!$has_api_key):?><div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:8px 12px;margin-bottom:12px;font-size:.72rem;color:#92400e;display:flex;align-items:center;gap:6px" class="anim"><i class="fas fa-triangle-exclamation"></i>Clé API non configurée — <a href="?page=api-keys" style="color:#92400e;font-weight:700">Configurer →</a></div><?php endif;?>

    <div class="cvr-layout">
        <div style="display:flex;flex-direction:column;gap:14px">

            <!-- Entonnoir de conversion -->
            <div class="cvr-card anim d1">
                <div class="cvr-card-hd">
                    <div class="cvr-card-hd-ic" style="background:linear-gradient(135deg,#6366f1,#4f46e5)"><i class="fas fa-funnel-dollar"></i></div>
                    <h2>Entonnoir de conversion</h2>
                    <span>Sauvegarde auto</span>
                    <div class="cvr-saved" id="globalSaved"><i class="fas fa-check-circle"></i> Sauvegardé</div>
                </div>
                <div class="cvr-card-body">
                    <?php foreach ($funnel as $fk => $step):
                        $fFields = $notes_json[$fk] ?? [];
                        $isFilled = count(array_filter($fFields, fn($v) => trim((string)$v) !== '')) >= 2;
                    ?>
                    <div class="cvr-step open" id="step-<?=$fk?>">
                        <div class="cvr-step-hd" onclick="toggleStep('<?=$fk?>')">
                            <div class="cvr-step-num" style="background:<?=$step['color']?>"><?=$step['num']?></div>
                            <div class="cvr-step-info">
                                <div class="cvr-step-label"><i class="fas <?=$step['icon']?>" style="color:<?=$step['color']?>;margin-right:4px;font-size:.65rem"></i> <?=$step['label']?></div>
                                <div class="cvr-step-desc"><?=htmlspecialchars($step['desc'])?></div>
                            </div>
                            <span class="cvr-step-dot<?=$isFilled?' filled':''?>" id="dot-<?=$fk?>"></span>
                            <span class="cvr-step-chevron"><i class="fas fa-chevron-right"></i></span>
                        </div>
                        <div class="cvr-step-body">
                            <?php foreach ($step['fields'] as $ffk => $field): ?>
                            <div class="cvr-field">
                                <div class="cvr-field-label"><i class="fas fa-pen"></i> <?=htmlspecialchars($field['label'])?></div>
                                <div class="cvr-field-hint"><?=htmlspecialchars($field['hint'])?></div>
                                <textarea class="cvr-ta" data-step="<?=$fk?>" data-field="<?=$ffk?>" rows="<?=$field['rows']??2?>" placeholder="<?=htmlspecialchars($field['placeholder'])?>" style="min-height:<?=($field['rows']??2)*24?>px"><?=htmlspecialchars($fFields[$ffk]??'')?></textarea>
                            </div>
                            <?php endforeach; ?>
                            <div class="cvr-gen-row">
                                <button class="cvr-btn cvr-btn-sm cvr-btn-accent" onclick="genStep('<?=$fk?>')"><i class="fas fa-wand-magic-sparkles"></i> Suggestions IA<div class="cvr-spinner" id="sp-<?=$fk?>"></div></button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Résumé tunnel -->
            <div class="cvr-card anim d2">
                <div class="cvr-card-hd"><div class="cvr-card-hd-ic" style="background:linear-gradient(135deg,#c9913b,#a0722a)"><i class="fas fa-sparkles"></i></div><h2>Plan de conversion</h2><span id="rBadge" style="color:#10b981;font-size:.6rem;<?=$resume_ia?'':'display:none'?>"><i class="fas fa-check-circle"></i></span></div>
                <div class="cvr-card-body">
                    <textarea class="cvr-resume" id="resumeArea" placeholder="Cliquez « Générer » pour un plan de conversion IA complet…"><?=htmlspecialchars($resume_ia)?></textarea>
                    <div class="cvr-ractions">
                        <button class="cvr-btn cvr-btn-gold" id="genResumeBtn" onclick="genResume()"><i class="fas fa-wand-magic-sparkles"></i> Générer le plan<div class="cvr-spinner" id="genResumeSp"></div></button>
                        <button class="cvr-btn cvr-btn-ghost" onclick="saveR()"><i class="fas fa-save"></i> Sauver</button>
                        <button class="cvr-btn cvr-btn-ghost" onclick="navigator.clipboard.writeText(document.getElementById('resumeArea').value).then(()=>toast('Copié'))" style="margin-left:auto"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assistant IA -->
        <div class="cvr-card anim d2" style="position:sticky;top:20px">
            <div class="cvr-card-hd"><div class="cvr-card-hd-ic" style="background:linear-gradient(135deg,#6366f1,#4f46e5)"><i class="fas fa-robot"></i></div><h2>Assistant IA — Conversion</h2><button onclick="clearChat()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:.65rem"><i class="fas fa-trash-can"></i></button></div>
            <div class="cvr-chat">
                <div class="cvr-chat-msgs" id="chatMsgs">
                    <?php if(empty($chat_hist)):?>
                    <div class="cvr-chat-empty" id="chatEmpty"><i class="fas fa-comments"></i><div><strong>Assistant tunnel de conversion</strong><p>Je connais toute votre stratégie ANCRE et la psychologie du persona #<?=$current_persona['id']?>. Optimisons votre conversion.</p></div></div>
                    <?php else: foreach($chat_hist as $m):?>
                    <div class="cvr-msg <?=htmlspecialchars($m['role'])?>"><div class="cvr-msg-av"><?=$m['role']==='user'?'Moi':'IA'?></div><div class="cvr-msg-bub"><?=nl2br(htmlspecialchars($m['content']))?></div></div>
                    <?php endforeach; endif;?>
                    <div class="cvr-msg assistant" id="typing" style="display:none"><div class="cvr-msg-av">IA</div><div class="cvr-typing visible"><div class="cvr-typing-dot"></div><div class="cvr-typing-dot"></div><div class="cvr-typing-dot"></div></div></div>
                </div>
                <?php if(empty($chat_hist)):?>
                <div class="cvr-sugg" id="chatSugg">
                    <button class="cvr-sugg-btn" onclick="sug(this)">Quel lead magnet pour ce persona ?</button>
                    <button class="cvr-sugg-btn" onclick="sug(this)">Séquence email nurturing</button>
                    <button class="cvr-sugg-btn" onclick="sug(this)">Objections principales</button>
                </div>
                <?php endif;?>
                <div class="cvr-chat-iw">
                    <textarea class="cvr-chat-in" id="chatIn" placeholder="Posez votre question sur la conversion…" rows="1" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendChat()}" oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"></textarea>
                    <button class="cvr-chat-send" id="chatBtn" onclick="sendChat()"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="cvr-toast" id="toast"><i class="fas fa-check-circle" style="color:#10b981"></i><span id="toastMsg"></span></div>

<script>
(function(){
const C=<?=json_encode($csrf)?>,PID=<?=json_encode($persona_id)?>,API=<?=json_encode($has_api_key)?>,
U='?page=strategie-conversion&persona='+PID,
PN=<?=json_encode($current_persona['nom'])?>,M1=<?=json_encode($current_persona['m1'])?>,
M2=<?=json_encode($current_persona['m2'])?>,PC=<?=json_encode($current_persona['conscience'])?>,
CHAIN=<?=json_encode($chainContext)?>,
FUNNEL=<?=json_encode($funnel, JSON_UNESCAPED_UNICODE)?>;
let hist=<?=json_encode($chat_hist)?>,sTO=null,busy=false;

function toast(m){const t=document.getElementById('toast');document.getElementById('toastMsg').textContent=m;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2800)}

// ── Accordion ──
window.toggleStep=function(fk){document.getElementById('step-'+fk).classList.toggle('open')};

// ── Notes ──
function allNotes(){
    const n={};
    document.querySelectorAll('.cvr-ta[data-step]').forEach(t=>{
        const sk=t.dataset.step,fk=t.dataset.field;
        if(!n[sk])n[sk]={};
        n[sk][fk]=t.value.trim();
    });
    return n;
}
function notesSummary(){
    const n=allNotes();let s='';
    Object.entries(FUNNEL).forEach(([fk,step])=>{
        s+=`\n${step.label}:`;
        Object.entries(step.fields).forEach(([ffk,f])=>{
            s+=`\n  ${f.label}: ${n[fk]?.[ffk]||'(vide)'}`;
        });
    });
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
        const n=allNotes();
        Object.keys(FUNNEL).forEach(fk=>{
            const fc=Object.values(n[fk]||{}).filter(v=>v).length;
            const dot=document.getElementById('dot-'+fk);
            if(dot)dot.classList.toggle('filled',fc>=2);
        });
    }
}
document.querySelectorAll('.cvr-ta[data-step]').forEach(t=>{t.addEventListener('input',()=>sched());t.addEventListener('blur',()=>saveNotes())});

// ── AI ──
async function ai(p,m,s){if(!API){toast('Clé API manquante');return null}const f=new FormData;f.append('csrf',C);f.append('action','ai-proxy');if(p)f.append('prompt',p);if(m)f.append('messages',JSON.stringify(m));if(s)f.append('system',s);const d=await(await fetch(U,{method:'POST',body:f})).json();if(!d.success)throw new Error(d.error||'Erreur');return d.text}

function sysPrompt(){
    let s=`Expert en tunnel de conversion immobilier, copywriting de conversion et psychologie d'achat.\nPersona: ${PN}\nMotivations: ${M1}+${M2}\nConscience: ${PC}/5`;
    if(CHAIN) s+=`\n\n${CHAIN}`;
    s+=`\n\nTunnel conversion actuel:\n${notesSummary()}`;
    s+=`\n\nTon rôle: aider à construire un tunnel de conversion optimisé pour ce persona. Concis, actionnable, basé sur la psychologie d'achat et les motivations ${M1}/${M2}. Adapté à un conseiller immobilier indépendant.`;
    return s;
}

// ── Gen step ──
window.genStep=async function(fk){
    const sp=document.getElementById('sp-'+fk);sp.style.display='inline-block';sp.parentElement.disabled=true;
    try{
        const step=FUNNEL[fk],sys=sysPrompt();
        const fieldNames=Object.entries(step.fields).map(([k,f])=>f.label).join(', ');
        const prompt=`Génère des suggestions concrètes pour l'étape "${step.label}" du tunnel de conversion pour le persona ${PN} (motivation ${M1}, conscience ${PC}/5).\nChamps: ${fieldNames}\n\nPour chaque champ, donne des suggestions spécifiques et actionnables. Format: un bloc par champ séparé par une ligne vide.`;
        const r=await ai(null,[{role:'user',content:prompt}],sys);
        if(r){
            const fields=Object.entries(step.fields);
            const parts=r.split(/\n\n+/);
            fields.forEach(([ffk,f],i)=>{
                const ta=document.querySelector(`[data-step="${fk}"][data-field="${ffk}"]`);
                if(ta && !ta.value.trim() && parts[i]){
                    ta.value=parts[i].replace(/^\*\*[^*]+\*\*\s*:?\s*/,'').trim();
                }
            });
            sched();toast('Suggestions ajoutées');
        }
    }catch(e){toast(e.message)}
    finally{sp.style.display='none';sp.parentElement.disabled=false}
};

// ── Resume ──
window.genResume=async function(){
    const b=document.getElementById('genResumeBtn'),sp=document.getElementById('genResumeSp');b.disabled=true;sp.style.display='inline-block';
    try{
        const sys=sysPrompt();
        const prompt=`Génère un plan de conversion complet pour le persona ${PN}.\nStructure:\n1. Parcours client en 4 étapes (1 ligne chacune)\n2. Lead magnet principal + pourquoi il fonctionne (2 lignes)\n3. Séquence nurturing résumée (5 emails, 1 ligne chacun)\n4. Top 3 objections + réponses (1 ligne chacune)\n5. KPIs à suivre (3-4 métriques)\n\nConcis, 15-18 lignes max.`;
        const r=await ai(null,[{role:'user',content:prompt}],sys);
        if(r){document.getElementById('resumeArea').value=r;await saveR()}
    }catch(e){toast(e.message)}
    finally{b.disabled=false;sp.style.display='none'}
};
window.saveR=async function(){const d=await post({action:'save-resume',persona_id:PID,resume:document.getElementById('resumeArea').value});if(d.success){document.getElementById('rBadge').style.display='';toast('Sauvegardé')}};

// ── Chat ──
function addMsg(r,c){document.getElementById('chatEmpty')?.remove();document.getElementById('chatSugg')?.remove();const w=document.getElementById('chatMsgs'),d=document.createElement('div');d.className='cvr-msg '+r;d.innerHTML=`<div class="cvr-msg-av">${r==='user'?'Moi':'IA'}</div><div class="cvr-msg-bub">${c.replace(/\n/g,'<br>')}</div>`;w.insertBefore(d,document.getElementById('typing'));w.scrollTop=w.scrollHeight}
function typ(on){document.getElementById('typing').style.display=on?'flex':'none';if(on)document.getElementById('chatMsgs').scrollTop=99999}
window.sug=function(b){document.getElementById('chatIn').value=b.textContent.trim();sendChat()};
window.sendChat=async function(){if(busy)return;const i=document.getElementById('chatIn'),m=i.value.trim();if(!m)return;busy=true;document.getElementById('chatBtn').disabled=true;i.value='';i.style.height='auto';addMsg('user',m);hist.push({role:'user',content:m});typ(true);
const sys=sysPrompt();
const msgs=hist.slice(-12).map(x=>({role:x.role,content:x.content}));
try{const a=await ai(null,msgs,sys);hist.push({role:'assistant',content:a});typ(false);addMsg('assistant',a);post({action:'save-chat',persona_id:PID,history:JSON.stringify(hist)})}
catch(e){typ(false);addMsg('assistant','Erreur: '+e.message)}finally{busy=false;document.getElementById('chatBtn').disabled=false}};
window.clearChat=function(){if(!confirm('Effacer le chat ?'))return;hist=[];document.getElementById('chatMsgs').innerHTML='<div class="cvr-chat-empty" id="chatEmpty"><i class="fas fa-comments"></i><div><strong>Assistant conversion</strong><p>Posez vos questions.</p></div></div><div class="cvr-msg assistant" id="typing" style="display:none"><div class="cvr-msg-av">IA</div><div class="cvr-typing visible"><div class="cvr-typing-dot"></div><div class="cvr-typing-dot"></div><div class="cvr-typing-dot"></div></div></div>';post({action:'save-chat',persona_id:PID,history:'[]'});toast('Chat effacé')};
const cw=document.getElementById('chatMsgs');if(cw)cw.scrollTop=cw.scrollHeight;
})();
</script>