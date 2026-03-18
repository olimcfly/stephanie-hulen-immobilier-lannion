<?php
/**
 * ══════════════════════════════════════════════════════════════
 * Page 5 : Mon Trafic (Méthode ANCRE — Pilier R)
 * /admin/modules/strategy/strategie-trafic/index.php
 * ══════════════════════════════════════════════════════════════
 * 4 blocs canaux : SEO Local, Réseaux sociaux, Pub payante, Réseau
 * Charge le contexte positionnement + offre + contenu
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

$db       = getDB();
$instance = INSTANCE_ID;
$etape    = 'trafic';

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
// 4 canaux de trafic — config
// ══════════════════════════════════════════════════════════════
$canaux = [
    'seo' => [
        'label' => 'SEO Local',
        'icon'  => 'fa-search-location',
        'color' => '#10b981',
        'desc'  => 'Référencement naturel local : Google, fiche GMB, pages locales.',
        'fields' => [
            'keywords'  => ['label'=>'Mots-clés cibles','placeholder'=>'Ex : achat maison Lannion, estimation appartement Trégor, immobilier côte de granit rose…','hint'=>'Mots-clés que ce persona taperait sur Google. Pensez intentions de recherche, pas juste termes génériques.','rows'=>3],
            'pages'     => ['label'=>'Pages à optimiser / créer','placeholder'=>'Ex : page secteur Lannion, article "acheter sa première maison à Lannion", FAQ primo-accédant…','hint'=>'Listez les pages existantes à améliorer et les nouvelles pages à créer pour capter ce trafic.','rows'=>3],
            'gmb'       => ['label'=>'Actions Google Business','placeholder'=>'Ex : poster 2 actus/sem, répondre aux avis sous 24h, ajouter photos mensuelles…','hint'=>'Fiche Google Business Profile : posts, avis, photos, Q&A, catégories.','rows'=>2],
        ],
    ],
    'social' => [
        'label' => 'Réseaux sociaux',
        'icon'  => 'fa-share-nodes',
        'color' => '#6366f1',
        'desc'  => 'Présence et contenu sur les réseaux adaptés à ce persona.',
        'fields' => [
            'platforms' => ['label'=>'Plateformes prioritaires','placeholder'=>'Ex : Facebook (groupe local), Instagram (stories visites), LinkedIn (investisseurs)…','hint'=>'Choisissez 2-3 plateformes max. Où est ce persona ? Facebook pour 40+, Insta pour 25-40, LinkedIn pour investisseurs/pros.','rows'=>2],
            'frequency' => ['label'=>'Fréquence et types de posts','placeholder'=>'Ex : 3 posts/sem — 1 témoignage, 1 conseil marché local, 1 nouveau bien…','hint'=>'Calendrier éditorial simplifié. Mieux vaut 3 posts réguliers que 10 irréguliers.','rows'=>3],
            'engagement' => ['label'=>'Stratégie d\'engagement','placeholder'=>'Ex : répondre à tous les commentaires, sondages en stories, live mensuel visite…','hint'=>'Comment créez-vous de l\'interaction ? Commentaires, DM, lives, groupes, communauté.','rows'=>2],
        ],
    ],
    'ads' => [
        'label' => 'Publicité payante',
        'icon'  => 'fa-bullseye',
        'color' => '#ef4444',
        'desc'  => 'Facebook Ads, Google Ads, retargeting — budget et audiences.',
        'fields' => [
            'budget'    => ['label'=>'Budget mensuel et répartition','placeholder'=>'Ex : 300€/mois — 200€ Facebook Ads, 100€ Google Ads local…','hint'=>'Même un petit budget bien ciblé produit des résultats. L\'important c\'est le ciblage, pas le montant.','rows'=>2],
            'audiences' => ['label'=>'Audiences cibles','placeholder'=>'Ex : Facebook lookalike de mes clients vendeurs, Google "estimation maison + ville", retargeting visiteurs site…','hint'=>'Décrivez vos audiences : démographie, intérêts, comportements, lookalike, retargeting.','rows'=>3],
            'campaigns' => ['label'=>'Types de campagnes prévues','placeholder'=>'Ex : campagne estimation gratuite (vendeurs), guide primo-accédant (acheteurs), retargeting blog…','hint'=>'Froid (notoriété), tiède (engagement), chaud (conversion). Adaptez au niveau de conscience du persona.','rows'=>3],
        ],
    ],
    'network' => [
        'label' => 'Réseau & Partenariats',
        'icon'  => 'fa-handshake',
        'color' => '#c9913b',
        'desc'  => 'Prescripteurs, partenaires, bouche-à-oreille, événements.',
        'fields' => [
            'partners'  => ['label'=>'Partenaires et prescripteurs','placeholder'=>'Ex : notaires locaux, courtiers, artisans rénovation, déménageurs, diagnostiqueurs…','hint'=>'Qui peut vous recommander des clients ? Listez vos partenaires actuels et ceux à démarcher.','rows'=>3],
            'events'    => ['label'=>'Événements et terrain','placeholder'=>'Ex : portes ouvertes mensuelles, salon habitat, petit-déjeuner investisseurs, café des commerçants…','hint'=>'Événements physiques ou en ligne pour créer du contact direct avec ce persona.','rows'=>2],
            'referral'  => ['label'=>'Programme de recommandation','placeholder'=>'Ex : carte ambassadeur 200€ par recommandation aboutie, email de suivi post-vente à M+1 M+6 M+12…','hint'=>'Transformez vos clients satisfaits en prescripteurs. Quel est votre système de parrainage ?','rows'=>2],
        ],
    ],
];

// ══════════════════════════════════════════════════════════════
// Charger advisor_context
// ══════════════════════════════════════════════════════════════
$advisorData = [];
try {
    $stmt = $db->query("SELECT field_key, field_value FROM advisor_context");
    $advisorData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
} catch (Exception $e) {}

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

// Structure : {seo: {keywords, pages, gmb}, social: {platforms, frequency, engagement}, ads: {...}, network: {...}}
foreach ($canaux as $ck => $canal) {
    if (!isset($notes_json[$ck]) || !is_array($notes_json[$ck])) {
        $notes_json[$ck] = [];
    }
    foreach ($canal['fields'] as $fk => $field) {
        if (!isset($notes_json[$ck][$fk])) $notes_json[$ck][$fk] = '';
    }
}

// ══════════════════════════════════════════════════════════════
// Charger contexte chaîné : positionnement + offre + contenu
// ══════════════════════════════════════════════════════════════
$chainContext = '';
$prevSteps = [
    'positionnement' => ['q1'=>'Territoire','q2'=>'Différence','q3'=>'Promesse','q4'=>'Preuves','q5'=>'Anti-positionnement'],
    'offre'          => ['q1'=>'Offre principale','q2'=>'Problème résolu','q3'=>'Solution unique','q4'=>'Résultat promis','q5'=>'Preuve sociale'],
];
foreach ($prevSteps as $prevEtape => $labels) {
    try {
        $stmt = $db->prepare("SELECT notes_json, resume_ia FROM strategy_notes WHERE instance_id=:i AND persona_id=:p AND etape=:e LIMIT 1");
        $stmt->execute([':i'=>$instance,':p'=>$persona_id,':e'=>$prevEtape]);
        $pNote = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($pNote) {
            $pNotes = json_decode($pNote['notes_json'] ?? '{}', true) ?: [];
            $parts = [];
            foreach ($labels as $k => $l) {
                if (!empty($pNotes[$k])) $parts[] = "$l: {$pNotes[$k]}";
            }
            if ($parts) $chainContext .= strtoupper($prevEtape) . ":\n" . implode("\n", $parts) . "\n";
            if (!empty($pNote['resume_ia'])) $chainContext .= "Résumé $prevEtape: " . $pNote['resume_ia'] . "\n\n";
        }
    } catch (Exception $e) {}
}
// Contenu (structure à 5 niveaux)
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
                $titre = $cNotes[$nk]['titre'] ?? '';
                $type  = $cNotes[$nk]['type'] ?? '';
                if ($titre || $type) $cParts[] = "Niveau $n: $type — $titre";
            }
        }
        if ($cParts) $chainContext .= "CONTENU:\n" . implode("\n", $cParts) . "\n";
        if (!empty($cNote['resume_ia'])) $chainContext .= "Résumé contenu: " . $cNote['resume_ia'] . "\n\n";
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
$currentStepIdx = 3;

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
        // Completion : combien de canaux ont au moins 2 champs remplis sur 3
        $filled = 0;
        foreach ($canaux as $ck => $canal) {
            if (isset($nn[$ck]) && is_array($nn[$ck])) {
                $fc = count(array_filter($nn[$ck], fn($v) => trim((string)$v) !== ''));
                if ($fc >= 2) $filled++;
            }
        }
        $c = (int)round(($filled / count($canaux)) * 100);
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
.trf{--a:#6366f1;--gold:#c9913b;font-family:'DM Sans',sans-serif;max-width:1080px;margin:0 auto;padding:24px 24px 60px}
.trf-hd{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap}
.trf-eye{font-size:.65rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:6px;display:flex;align-items:center;gap:6px}
.trf-h1{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:#111827;margin:0 0 4px}
.trf-sub{font-size:.78rem;color:#6b7280}
/* Nav ANCRE */
.trf-nav{display:flex;gap:4px;margin-bottom:16px;flex-wrap:wrap}
.trf-nav-step{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;font-size:.72rem;font-weight:600;text-decoration:none;border:1.5px solid #e5e7eb;color:#9ca3af;background:#fff;transition:all .15s}
.trf-nav-step.active{border-color:var(--a);background:linear-gradient(135deg,#6366f110,#6366f105);color:var(--a);font-weight:800}
.trf-nav-step.done{border-color:#86efac;color:#166534;background:#f0fdf4}
.trf-nav-step.done:hover{border-color:#22c55e}
.trf-nav-step.locked{opacity:.45;pointer-events:none}
.trf-nav-step i{font-size:.6rem}
.trf-nav-letter{width:18px;height:18px;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.55rem;font-weight:800;color:#fff;background:#d1d5db;flex-shrink:0}
.trf-nav-step.active .trf-nav-letter{background:var(--a)}
.trf-nav-step.done .trf-nav-letter{background:#22c55e}
.trf-nav-chevron{color:#d1d5db;font-size:.55rem;margin:0 2px}
/* Persona banner */
.trf-pb{display:flex;align-items:center;gap:14px;padding:14px 18px;background:linear-gradient(135deg,<?=$current_persona['color']?>10,<?=$current_persona['color']?>05);border:1.5px solid <?=$current_persona['color']?>40;border-radius:12px;margin-bottom:16px}
.trf-pb-num{font-size:11px;font-weight:700;width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;background:<?=$current_persona['color']?>;flex-shrink:0}
.trf-pb-info{flex:1}
.trf-pb-name{font-size:.9rem;font-weight:700;color:#111827}
.trf-pb-meta{font-size:.72rem;color:#6b7280;margin-top:2px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.trf-pb-tag{font-size:9px;font-weight:600;padding:1px 6px;border-radius:3px}
.trf-pb-dots{display:flex;gap:2px}.trf-pb-dot{width:5px;height:5px;border-radius:50%;background:#e5e7eb}.trf-pb-dot.on{background:#f59e0b}
.trf-pb-back{font-size:.72rem;font-weight:600;color:var(--a);text-decoration:none;display:flex;align-items:center;gap:4px}
.trf-pb-back:hover{text-decoration:underline}
/* Context */
.trf-ctx{display:flex;align-items:flex-start;gap:8px;padding:10px 14px;background:#f8f9ff;border:1px solid #c7d2fe;border-radius:8px;margin-bottom:14px;font-size:.72rem;color:#4338ca;line-height:1.4}
.trf-ctx i{color:#6366f1;margin-top:2px;flex-shrink:0}
.trf-ctx-toggle{background:none;border:none;color:#6366f1;font-weight:700;cursor:pointer;font-size:.68rem;text-decoration:underline;padding:0;margin-top:4px}
.trf-ctx-detail{display:none;margin-top:6px;padding:8px 10px;background:#fff;border:1px solid #e0e7ff;border-radius:6px;font-size:.7rem;color:#374151;white-space:pre-wrap;line-height:1.5;max-height:200px;overflow-y:auto}
.trf-ctx-detail.visible{display:block}
/* Layout */
.trf-layout{display:grid;grid-template-columns:1fr 400px;gap:16px;align-items:start}
@media(max-width:900px){.trf-layout{grid-template-columns:1fr}}
.trf-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.06);overflow:hidden}
.trf-card-hd{display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid #e5e7eb;background:#f9fafb}
.trf-card-hd-ic{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:11px;flex-shrink:0;color:#fff}
.trf-card-hd h2{font-family:'Syne',sans-serif;font-size:.85rem;font-weight:800;color:#111827;margin:0;flex:1}
.trf-card-hd span{font-size:.65rem;color:#9ca3af}
.trf-card-body{padding:18px}
/* Canal block */
.trf-canal{border:1.5px solid #e5e7eb;border-radius:10px;overflow:hidden;margin-bottom:14px}
.trf-canal:last-child{margin-bottom:0}
.trf-canal-hd{display:flex;align-items:center;gap:8px;padding:10px 14px;cursor:pointer;transition:background .15s}
.trf-canal-hd:hover{background:#f9fafb}
.trf-canal-ic{width:26px;height:26px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:.65rem;color:#fff;flex-shrink:0}
.trf-canal-label{font-size:.8rem;font-weight:700;color:#111827;flex:1}
.trf-canal-desc{font-size:.68rem;color:#9ca3af}
.trf-canal-dot{width:7px;height:7px;border-radius:50%;background:#e5e7eb;flex-shrink:0}
.trf-canal-dot.filled{background:#22c55e}
.trf-canal-chevron{color:#d1d5db;font-size:.6rem;transition:transform .2s;flex-shrink:0}
.trf-canal.open .trf-canal-chevron{transform:rotate(90deg)}
.trf-canal-body{display:none;padding:12px 14px;border-top:1px solid #e5e7eb;background:#fafbfc}
.trf-canal.open .trf-canal-body{display:block}
.trf-field{margin-bottom:12px}
.trf-field:last-child{margin-bottom:0}
.trf-field-label{font-size:.7rem;font-weight:700;color:#374151;margin-bottom:3px;display:flex;align-items:center;gap:6px}
.trf-field-label i{font-size:.6rem;color:#9ca3af}
.trf-field-hint{font-size:.65rem;color:#9ca3af;margin-bottom:4px;line-height:1.3}
.trf-ta{width:100%;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:9px 11px;font-family:'DM Sans',sans-serif;font-size:.8rem;color:#111827;resize:vertical;outline:none;transition:border-color .15s;box-sizing:border-box}
.trf-ta:focus{border-color:var(--a);box-shadow:0 0 0 3px rgba(99,102,241,.08)}
.trf-gen-row{display:flex;gap:6px;margin-top:6px}
.trf-btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:7px;font-size:.72rem;font-weight:700;cursor:pointer;border:none;transition:transform .15s}
.trf-btn-sm{padding:5px 10px;font-size:.68rem}
.trf-btn-accent{background:linear-gradient(135deg,var(--a),#4f46e5);color:#fff}
.trf-btn-accent:hover{transform:translateY(-1px)}
.trf-btn-gold{background:linear-gradient(135deg,var(--gold),#a0722a);color:#fff}
.trf-btn-gold:hover{transform:translateY(-1px)}
.trf-btn-ghost{background:#f9fafb;border:1px solid #e5e7eb;color:#6b7280}
.trf-btn-ghost:hover{background:#e5e7eb}
.trf-spinner{width:12px;height:12px;border-radius:50%;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;animation:spin .7s linear infinite;display:none}
@keyframes spin{to{transform:rotate(360deg)}}
.trf-saved{font-size:.6rem;color:#10b981;opacity:0;transition:opacity .3s;display:flex;align-items:center;gap:3px;margin-left:auto}
.trf-saved.visible{opacity:1}
/* Resume */
.trf-resume{width:100%;min-height:120px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;font-family:'DM Sans',sans-serif;font-size:.8rem;color:#111827;resize:vertical;outline:none;box-sizing:border-box;line-height:1.6}
.trf-resume:focus{border-color:var(--gold)}
.trf-ractions{display:flex;gap:6px;margin-top:8px;flex-wrap:wrap}
/* Chat */
.trf-chat{display:flex;flex-direction:column;height:520px}
.trf-chat-msgs{flex:1;overflow-y:auto;padding:14px;display:flex;flex-direction:column;gap:8px;scrollbar-width:thin}
.trf-msg{display:flex;gap:6px;align-items:flex-end;animation:msgIn .2s ease}
@keyframes msgIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}
.trf-msg-av{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.6rem;font-weight:800;flex-shrink:0}
.trf-msg-bub{max-width:85%;padding:9px 12px;border-radius:11px;font-size:.78rem;line-height:1.5}
.trf-msg.user{flex-direction:row-reverse}
.trf-msg.user .trf-msg-av{background:linear-gradient(135deg,#6366f1,#4f46e5);color:#fff}
.trf-msg.user .trf-msg-bub{background:linear-gradient(135deg,var(--a),#4f46e5);color:#fff;border-bottom-right-radius:3px}
.trf-msg.assistant .trf-msg-av{background:linear-gradient(135deg,#c9913b,#a0722a);color:#fff}
.trf-msg.assistant .trf-msg-bub{background:#f9fafb;border:1px solid #e5e7eb;color:#111827;border-bottom-left-radius:3px}
.trf-typing{display:none;align-items:center;gap:3px;padding:9px 12px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:11px;width:fit-content}
.trf-typing.visible{display:flex}
.trf-typing-dot{width:5px;height:5px;border-radius:50%;background:#9ca3af;animation:td 1.2s infinite}
.trf-typing-dot:nth-child(2){animation-delay:.2s}.trf-typing-dot:nth-child(3){animation-delay:.4s}
@keyframes td{0%,80%,100%{transform:translateY(0);opacity:.4}40%{transform:translateY(-3px);opacity:1}}
.trf-chat-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:6px;padding:18px;text-align:center}
.trf-chat-empty i{font-size:1.6rem;color:#9ca3af}
.trf-chat-empty strong{font-size:.82rem;color:#111827}
.trf-chat-empty p{font-size:.72rem;color:#6b7280;line-height:1.4}
.trf-sugg{display:flex;flex-wrap:wrap;gap:4px;padding:0 12px 8px}
.trf-sugg-btn{padding:4px 10px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:16px;cursor:pointer;font-size:.68rem;color:#6b7280;font-family:inherit;transition:all .13s}
.trf-sugg-btn:hover{border-color:var(--a);color:var(--a)}
.trf-chat-iw{padding:8px 12px 12px;border-top:1px solid #e5e7eb;display:flex;gap:6px;align-items:flex-end}
.trf-chat-in{flex:1;min-height:36px;max-height:100px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:9px;padding:8px 11px;font-family:'DM Sans',sans-serif;font-size:.78rem;color:#111827;outline:none;resize:none;box-sizing:border-box}
.trf-chat-in:focus{border-color:var(--a)}
.trf-chat-send{width:36px;height:36px;border-radius:8px;background:linear-gradient(135deg,var(--a),#4f46e5);border:none;cursor:pointer;color:#fff;display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0}
.trf-chat-send:disabled{opacity:.5;cursor:not-allowed}
.trf-toast{position:fixed;bottom:24px;right:24px;z-index:9999;background:#111827;color:#fff;padding:10px 16px;border-radius:9px;font-size:.75rem;font-weight:600;display:flex;align-items:center;gap:6px;box-shadow:0 8px 24px rgba(0,0,0,.2);transform:translateY(20px);opacity:0;transition:all .25s;pointer-events:none}
.trf-toast.show{transform:none;opacity:1}
.anim{animation:fadeUp .25s ease both}.d1{animation-delay:.05s}.d2{animation-delay:.1s}
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
</style>

<div class="trf">
    <div class="trf-hd anim">
        <div>
            <div class="trf-eye"><i class="fas fa-bullhorn"></i> Méthode ANCRE — Pilier R</div>
            <h1 class="trf-h1">Mon Trafic</h1>
            <p class="trf-sub">Définissez vos canaux d'acquisition pour amener ce persona vers votre offre et vos contenus.</p>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px">
            <div style="background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:8px 14px;display:flex;align-items:center;gap:10px">
                <div style="flex:1;height:6px;background:#e5e7eb;border-radius:3px;overflow:hidden;min-width:100px"><div id="cFill" style="height:100%;border-radius:3px;background:linear-gradient(90deg,var(--a),#818cf8);width:<?=$completion?>%;transition:width .5s"></div></div>
                <span id="cPct" style="font-size:.72rem;font-weight:800;color:var(--a)"><?=$completion?>%</span>
            </div>
        </div>
    </div>

    <!-- Navigation ANCRE -->
    <div class="trf-nav anim d1">
        <?php foreach ($ancreSteps as $idx => $step):
            $stepEtape = str_replace('strategie-', '', $step['slug']);
            $isDone = isset($stepsWithData[$stepEtape]) && $stepsWithData[$stepEtape] > 0;
            $isActive = $idx === $currentStepIdx;
            $isLocked = !$isActive && !$isDone && $idx > $currentStepIdx;
            $cls = $isActive ? 'active' : ($isDone ? 'done' : ($isLocked ? 'locked' : ''));
        ?>
            <?php if ($idx > 0): ?><span class="trf-nav-chevron"><i class="fas fa-chevron-right"></i></span><?php endif; ?>
            <a href="?page=<?=$step['slug']?>&persona=<?=$persona_id?>" class="trf-nav-step <?=$cls?>">
                <span class="trf-nav-letter"><?=$step['letter']?></span>
                <i class="fas <?=$step['icon']?>"></i> <?=$step['label']?>
                <?php if ($isDone && !$isActive): ?><i class="fas fa-check" style="font-size:.55rem;color:#22c55e"></i><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Persona banner -->
    <div class="trf-pb anim d1">
        <span class="trf-pb-num"><?=$current_persona['id']?></span>
        <div class="trf-pb-info">
            <div class="trf-pb-name"><?=htmlspecialchars($current_persona['nom'])?></div>
            <div class="trf-pb-meta">
                <?=$familyMeta[$current_persona['type']]['icon']??''?> <?=$familyMeta[$current_persona['type']]['label']??''?>
                <span>•</span>
                <?php $mc1=['Sécurité'=>['c'=>'#1e40af','bg'=>'#dbeafe'],'Liberté'=>['c'=>'#065f46','bg'=>'#d1fae5'],'Reconnaissance'=>['c'=>'#92400e','bg'=>'#fef3c7'],'Contrôle'=>['c'=>'#5b21b6','bg'=>'#ede9fe']]; ?>
                <span class="trf-pb-tag" style="background:<?=$mc1[$current_persona['m1']]['bg']??'#eee'?>;color:<?=$mc1[$current_persona['m1']]['c']??'#666'?>"><?=$current_persona['m1']?></span>
                <span>+</span>
                <span class="trf-pb-tag" style="background:<?=$mc1[$current_persona['m2']]['bg']??'#eee'?>;color:<?=$mc1[$current_persona['m2']]['c']??'#666'?>"><?=$current_persona['m2']?></span>
                <span>•</span>
                <span class="trf-pb-dots"><?php for($i=1;$i<=5;$i++):?><span class="trf-pb-dot<?=$i<=$current_persona['conscience']?' on':''?>"></span><?php endfor;?></span> <?=$current_persona['conscience']?>/5
            </div>
        </div>
        <a href="?page=neuropersona" class="trf-pb-back"><i class="fas fa-arrow-left"></i> Changer</a>
    </div>

    <!-- Contexte chaîné -->
    <?php if ($hasChainContext): ?>
    <div class="trf-ctx anim d1">
        <i class="fas fa-link"></i>
        <div>
            <strong>Contexte chargé :</strong> positionnement + offre + contenu intégrés.
            <br><button class="trf-ctx-toggle" onclick="document.getElementById('ctxDetail').classList.toggle('visible')">Voir le contexte</button>
            <div class="trf-ctx-detail" id="ctxDetail"><?=nl2br(htmlspecialchars($chainContext))?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if(!$has_api_key):?><div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;padding:8px 12px;margin-bottom:12px;font-size:.72rem;color:#92400e;display:flex;align-items:center;gap:6px" class="anim"><i class="fas fa-triangle-exclamation"></i>Clé API non configurée — <a href="?page=api-keys" style="color:#92400e;font-weight:700">Configurer →</a></div><?php endif;?>

    <div class="trf-layout">
        <div style="display:flex;flex-direction:column;gap:14px">

            <!-- 4 canaux en accordéon -->
            <div class="trf-card anim d1">
                <div class="trf-card-hd">
                    <div class="trf-card-hd-ic" style="background:linear-gradient(135deg,#6366f1,#4f46e5)"><i class="fas fa-bullhorn"></i></div>
                    <h2>Canaux d'acquisition</h2>
                    <span>Sauvegarde auto</span>
                    <div class="trf-saved" id="globalSaved"><i class="fas fa-check-circle"></i> Sauvegardé</div>
                </div>
                <div class="trf-card-body">
                    <?php foreach ($canaux as $ck => $canal):
                        $cFields = $notes_json[$ck] ?? [];
                        $isFilled = count(array_filter($cFields, fn($v) => trim((string)$v) !== '')) >= 2;
                    ?>
                    <div class="trf-canal open" id="canal-<?=$ck?>">
                        <div class="trf-canal-hd" onclick="toggleCanal('<?=$ck?>')">
                            <div class="trf-canal-ic" style="background:<?=$canal['color']?>"><i class="fas <?=$canal['icon']?>"></i></div>
                            <div style="flex:1">
                                <div class="trf-canal-label"><?=$canal['label']?></div>
                                <div class="trf-canal-desc"><?=htmlspecialchars($canal['desc'])?></div>
                            </div>
                            <span class="trf-canal-dot<?=$isFilled?' filled':''?>" id="dot-<?=$ck?>"></span>
                            <span class="trf-canal-chevron"><i class="fas fa-chevron-right"></i></span>
                        </div>
                        <div class="trf-canal-body">
                            <?php foreach ($canal['fields'] as $fk => $field): ?>
                            <div class="trf-field">
                                <div class="trf-field-label"><i class="fas fa-pen"></i> <?=htmlspecialchars($field['label'])?></div>
                                <div class="trf-field-hint"><?=htmlspecialchars($field['hint'])?></div>
                                <textarea class="trf-ta" data-canal="<?=$ck?>" data-field="<?=$fk?>" rows="<?=$field['rows']??2?>" placeholder="<?=htmlspecialchars($field['placeholder'])?>" style="min-height:<?=($field['rows']??2)*24?>px"><?=htmlspecialchars($cFields[$fk]??'')?></textarea>
                            </div>
                            <?php endforeach; ?>
                            <div class="trf-gen-row">
                                <button class="trf-btn trf-btn-sm trf-btn-accent" onclick="genCanal('<?=$ck?>')"><i class="fas fa-wand-magic-sparkles"></i> Suggestions IA<div class="trf-spinner" id="sp-<?=$ck?>"></div></button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Résumé stratégie trafic -->
            <div class="trf-card anim d2">
                <div class="trf-card-hd"><div class="trf-card-hd-ic" style="background:linear-gradient(135deg,#c9913b,#a0722a)"><i class="fas fa-sparkles"></i></div><h2>Plan de trafic</h2><span id="rBadge" style="color:#10b981;font-size:.6rem;<?=$resume_ia?'':'display:none'?>"><i class="fas fa-check-circle"></i></span></div>
                <div class="trf-card-body">
                    <textarea class="trf-resume" id="resumeArea" placeholder="Cliquez « Générer » pour un plan de trafic IA complet…"><?=htmlspecialchars($resume_ia)?></textarea>
                    <div class="trf-ractions">
                        <button class="trf-btn trf-btn-gold" id="genResumeBtn" onclick="genResume()"><i class="fas fa-wand-magic-sparkles"></i> Générer le plan<div class="trf-spinner" id="genResumeSp"></div></button>
                        <button class="trf-btn trf-btn-ghost" onclick="saveR()"><i class="fas fa-save"></i> Sauver</button>
                        <button class="trf-btn trf-btn-ghost" onclick="navigator.clipboard.writeText(document.getElementById('resumeArea').value).then(()=>toast('Copié'))" style="margin-left:auto"><i class="fas fa-copy"></i></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assistant IA -->
        <div class="trf-card anim d2" style="position:sticky;top:20px">
            <div class="trf-card-hd"><div class="trf-card-hd-ic" style="background:linear-gradient(135deg,#6366f1,#4f46e5)"><i class="fas fa-robot"></i></div><h2>Assistant IA — Trafic</h2><button onclick="clearChat()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:.65rem"><i class="fas fa-trash-can"></i></button></div>
            <div class="trf-chat">
                <div class="trf-chat-msgs" id="chatMsgs">
                    <?php if(empty($chat_hist)):?>
                    <div class="trf-chat-empty" id="chatEmpty"><i class="fas fa-comments"></i><div><strong>Assistant acquisition de trafic</strong><p>Je connais votre stratégie complète (positionnement, offre, contenu) et la psychologie du persona #<?=$current_persona['id']?>. Optimisons vos canaux d'acquisition.</p></div></div>
                    <?php else: foreach($chat_hist as $m):?>
                    <div class="trf-msg <?=htmlspecialchars($m['role'])?>"><div class="trf-msg-av"><?=$m['role']==='user'?'Moi':'IA'?></div><div class="trf-msg-bub"><?=nl2br(htmlspecialchars($m['content']))?></div></div>
                    <?php endforeach; endif;?>
                    <div class="trf-msg assistant" id="typing" style="display:none"><div class="trf-msg-av">IA</div><div class="trf-typing visible"><div class="trf-typing-dot"></div><div class="trf-typing-dot"></div><div class="trf-typing-dot"></div></div></div>
                </div>
                <?php if(empty($chat_hist)):?>
                <div class="trf-sugg" id="chatSugg">
                    <button class="trf-sugg-btn" onclick="sug(this)">Par quel canal commencer ?</button>
                    <button class="trf-sugg-btn" onclick="sug(this)">Budget pub optimal ?</button>
                    <button class="trf-sugg-btn" onclick="sug(this)">Stratégie SEO locale</button>
                </div>
                <?php endif;?>
                <div class="trf-chat-iw">
                    <textarea class="trf-chat-in" id="chatIn" placeholder="Posez votre question sur le trafic…" rows="1" onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendChat()}" oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,100)+'px'"></textarea>
                    <button class="trf-chat-send" id="chatBtn" onclick="sendChat()"><i class="fas fa-paper-plane"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="trf-toast" id="toast"><i class="fas fa-check-circle" style="color:#10b981"></i><span id="toastMsg"></span></div>

<script>
(function(){
const C=<?=json_encode($csrf)?>,PID=<?=json_encode($persona_id)?>,API=<?=json_encode($has_api_key)?>,
U='?page=strategie-trafic&persona='+PID,
PN=<?=json_encode($current_persona['nom'])?>,M1=<?=json_encode($current_persona['m1'])?>,
M2=<?=json_encode($current_persona['m2'])?>,PC=<?=json_encode($current_persona['conscience'])?>,
CHAIN=<?=json_encode($chainContext)?>,
CANAUX=<?=json_encode($canaux, JSON_UNESCAPED_UNICODE)?>;
let hist=<?=json_encode($chat_hist)?>,sTO=null,busy=false;

function toast(m){const t=document.getElementById('toast');document.getElementById('toastMsg').textContent=m;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),2800)}

// ── Accordion ──
window.toggleCanal=function(ck){document.getElementById('canal-'+ck).classList.toggle('open')};

// ── Notes collect ──
function allNotes(){
    const n={};
    document.querySelectorAll('.trf-ta[data-canal]').forEach(t=>{
        const ck=t.dataset.canal,fk=t.dataset.field;
        if(!n[ck])n[ck]={};
        n[ck][fk]=t.value.trim();
    });
    return n;
}
function notesSummary(){
    const n=allNotes();let s='';
    Object.entries(CANAUX).forEach(([ck,c])=>{
        s+=`\n${c.label}:`;
        Object.entries(c.fields).forEach(([fk,f])=>{
            s+=`\n  ${f.label}: ${n[ck]?.[fk]||'(vide)'}`;
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
        // Update dots
        const n=allNotes();
        Object.keys(CANAUX).forEach(ck=>{
            const fc=Object.values(n[ck]||{}).filter(v=>v).length;
            const dot=document.getElementById('dot-'+ck);
            if(dot)dot.classList.toggle('filled',fc>=2);
        });
    }
}
document.querySelectorAll('.trf-ta[data-canal]').forEach(t=>{t.addEventListener('input',()=>sched());t.addEventListener('blur',()=>saveNotes())});

// ── AI ──
async function ai(p,m,s){if(!API){toast('Clé API manquante');return null}const f=new FormData;f.append('csrf',C);f.append('action','ai-proxy');if(p)f.append('prompt',p);if(m)f.append('messages',JSON.stringify(m));if(s)f.append('system',s);const d=await(await fetch(U,{method:'POST',body:f})).json();if(!d.success)throw new Error(d.error||'Erreur');return d.text}

function sysPrompt(){
    let s=`Expert en acquisition de trafic immobilier, marketing digital et SEO local.\nPersona: ${PN}\nMotivations: ${M1}+${M2}\nConscience: ${PC}/5`;
    if(CHAIN) s+=`\n\n${CHAIN}`;
    s+=`\n\nStratégie trafic actuelle:\n${notesSummary()}`;
    s+=`\n\nTon rôle: aider à définir les meilleurs canaux d'acquisition pour ce persona. Concis, actionnable, adapté au budget d'un conseiller immobilier indépendant.`;
    return s;
}

// ── Gen canal ──
window.genCanal=async function(ck){
    const sp=document.getElementById('sp-'+ck);sp.style.display='inline-block';sp.parentElement.disabled=true;
    try{
        const canal=CANAUX[ck],sys=sysPrompt();
        const fieldNames=Object.entries(canal.fields).map(([k,f])=>f.label).join(', ');
        const prompt=`Génère des suggestions concrètes pour le canal "${canal.label}" pour le persona ${PN}.\nChamps à remplir: ${fieldNames}\n\nPour chaque champ, donne 2-3 suggestions actionables et spécifiques. Format: un bloc par champ séparé par une ligne vide, avec le nom du champ en gras. Pas d'introduction.`;
        const r=await ai(null,[{role:'user',content:prompt}],sys);
        if(r){
            // Parse et remplir les champs
            const fields=Object.entries(canal.fields);
            const parts=r.split(/\n\n+/);
            fields.forEach(([fk,f],i)=>{
                const ta=document.querySelector(`[data-canal="${ck}"][data-field="${fk}"]`);
                if(ta && !ta.value.trim() && parts[i]){
                    // Nettoyer le label du champ s'il est en préfixe
                    let val=parts[i].replace(/^\*\*[^*]+\*\*\s*:?\s*/,'').trim();
                    ta.value=val;
                }
            });
            sched();
            toast('Suggestions ajoutées');
        }
    }catch(e){toast(e.message)}
    finally{sp.style.display='none';sp.parentElement.disabled=false}
};

// ── Resume ──
window.genResume=async function(){
    const b=document.getElementById('genResumeBtn'),sp=document.getElementById('genResumeSp');b.disabled=true;sp.style.display='inline-block';
    try{
        const sys=sysPrompt();
        const prompt=`Génère un plan de trafic actionnable pour le persona ${PN}.\nStructure:\n1. Canal prioritaire n°1 + pourquoi (2 lignes)\n2. Canal n°2 (2 lignes)\n3. Canal n°3 (2 lignes)\n4. Quick wins (3 actions cette semaine)\n5. Budget recommandé\n\nBasé sur les canaux remplis. Concis, 12-15 lignes max. Adapté à un conseiller immobilier indépendant.`;
        const r=await ai(null,[{role:'user',content:prompt}],sys);
        if(r){document.getElementById('resumeArea').value=r;await saveR()}
    }catch(e){toast(e.message)}
    finally{b.disabled=false;sp.style.display='none'}
};
window.saveR=async function(){const d=await post({action:'save-resume',persona_id:PID,resume:document.getElementById('resumeArea').value});if(d.success){document.getElementById('rBadge').style.display='';toast('Sauvegardé')}};

// ── Chat ──
function addMsg(r,c){document.getElementById('chatEmpty')?.remove();document.getElementById('chatSugg')?.remove();const w=document.getElementById('chatMsgs'),d=document.createElement('div');d.className='trf-msg '+r;d.innerHTML=`<div class="trf-msg-av">${r==='user'?'Moi':'IA'}</div><div class="trf-msg-bub">${c.replace(/\n/g,'<br>')}</div>`;w.insertBefore(d,document.getElementById('typing'));w.scrollTop=w.scrollHeight}
function typ(on){document.getElementById('typing').style.display=on?'flex':'none';if(on)document.getElementById('chatMsgs').scrollTop=99999}
window.sug=function(b){document.getElementById('chatIn').value=b.textContent.trim();sendChat()};
window.sendChat=async function(){if(busy)return;const i=document.getElementById('chatIn'),m=i.value.trim();if(!m)return;busy=true;document.getElementById('chatBtn').disabled=true;i.value='';i.style.height='auto';addMsg('user',m);hist.push({role:'user',content:m});typ(true);
const sys=sysPrompt();
const msgs=hist.slice(-12).map(x=>({role:x.role,content:x.content}));
try{const a=await ai(null,msgs,sys);hist.push({role:'assistant',content:a});typ(false);addMsg('assistant',a);post({action:'save-chat',persona_id:PID,history:JSON.stringify(hist)})}
catch(e){typ(false);addMsg('assistant','Erreur: '+e.message)}finally{busy=false;document.getElementById('chatBtn').disabled=false}};
window.clearChat=function(){if(!confirm('Effacer le chat ?'))return;hist=[];document.getElementById('chatMsgs').innerHTML='<div class="trf-chat-empty" id="chatEmpty"><i class="fas fa-comments"></i><div><strong>Assistant trafic</strong><p>Posez vos questions.</p></div></div><div class="trf-msg assistant" id="typing" style="display:none"><div class="trf-msg-av">IA</div><div class="trf-typing visible"><div class="trf-typing-dot"></div><div class="trf-typing-dot"></div><div class="trf-typing-dot"></div></div></div>';post({action:'save-chat',persona_id:PID,history:'[]'});toast('Chat effacé')};
const cw=document.getElementById('chatMsgs');if(cw)cw.scrollTop=cw.scrollHeight;
})();
</script>