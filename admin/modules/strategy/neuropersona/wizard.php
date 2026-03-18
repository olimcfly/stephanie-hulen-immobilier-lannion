<?php
/**
 * ══════════════════════════════════════════════════════════════
 * STRATÉGIE — Wizard Campagne NeuroPersona
 * /admin/modules/strategy/neuropersona/wizard.php
 * ══════════════════════════════════════════════════════════════
 * Parcours guidé 7 étapes pour créer une campagne complète
 * par persona : offre, contenus ×5, localisation, emails, form
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

$db       = getDB();
$instance = INSTANCE_ID;
$user_id  = (int)($_SESSION['admin_id'] ?? 0);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// ── Réutiliser getAnthropicKey de positionnement ──
if (!function_exists('getAnthropicKey')) {
    function getAnthropicKey(PDO $db): string {
        try {
            $stmt = $db->prepare("SELECT api_key_encrypted FROM api_keys WHERE service_key='claude' LIMIT 1");
            $stmt->execute();
            $enc = $stmt->fetchColumn();
            if ($enc) {
                $encKey = defined('APP_KEY') ? APP_KEY : (defined('SECRET_KEY') ? SECRET_KEY : 'immolocal_aes_key_2024_secure!!');
                $raw = base64_decode($enc);
                if (strlen($raw) >= 17) {
                    $dec = openssl_decrypt(substr($raw,16),'aes-256-cbc',$encKey,0,substr($raw,0,16));
                    if ($dec) return $dec;
                }
            }
        } catch (Exception $e) {}
        try {
            $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key='anthropic_api_key' LIMIT 1");
            $stmt->execute(); $v = $stmt->fetchColumn(); if ($v) return $v;
        } catch (Exception $e) {}
        try {
            $stmt = $db->prepare("SELECT setting_value FROM ai_settings WHERE setting_key='anthropic_api_key' LIMIT 1");
            $stmt->execute(); $v = $stmt->fetchColumn(); if ($v) return $v;
        } catch (Exception $e) {}
        if (defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY) return ANTHROPIC_API_KEY;
        return '';
    }
}

$has_api_key = !empty(getAnthropicKey($db));

// ── 30 NeuroPersonas (référentiel statique) ──
$neuropersonas = [
    ['id'=>1,'name'=>'Primo-Accédant Jeune Couple','family'=>'acheteurs','age'=>'25-35','desc'=>'CDI récent, locataire','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>2],
    ['id'=>2,'name'=>'Primo-Accédant Solo','family'=>'acheteurs','age'=>'28-40','desc'=>'Célibataire ou divorcé, budget serré','m1'=>'Liberté','m2'=>'Sécurité','conscience'=>2],
    ['id'=>3,'name'=>'Famille en Expansion','family'=>'acheteurs','age'=>'30-45','desc'=>'Enfants grandissent, veut maison avec jardin','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>3],
    ['id'=>4,'name'=>'Muté Professionnel','family'=>'acheteurs','age'=>'30-50','desc'=>'Mutation imposée, urgence','m1'=>'Contrôle','m2'=>'Sécurité','conscience'=>3],
    ['id'=>5,'name'=>'Retraité Actif — Downsizer','family'=>'acheteurs','age'=>'60-75','desc'=>'Cherche plus petit, proche famille','m1'=>'Liberté','m2'=>'Sécurité','conscience'=>3],
    ['id'=>6,'name'=>'Expatrié de Retour','family'=>'acheteurs','age'=>'35-55','desc'=>'Ne connaît plus le marché local','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>2],
    ['id'=>7,'name'=>'Divorcé en Reconstruction','family'=>'acheteurs','age'=>'35-55','desc'=>'Doit racheter seul, fragile','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>2],
    ['id'=>8,'name'=>'Acheteur Résidence Secondaire','family'=>'acheteurs','age'=>'45-65','desc'=>'Aisé, plaisir, coup de cœur','m1'=>'Reconnaissance','m2'=>'Liberté','conscience'=>4],
    ['id'=>9,'name'=>'Senior Simplificateur','family'=>'vendeurs','age'=>'65-80','desc'=>'Maison trop grande, peur du changement','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>2],
    ['id'=>10,'name'=>'Héritier — Succession','family'=>'vendeurs','age'=>'40-60','desc'=>'Indivision, veut vendre vite','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>3],
    ['id'=>11,'name'=>'Vendeur Divorce / Séparation','family'=>'vendeurs','age'=>'30-55','desc'=>'Tension, besoin de neutralité','m1'=>'Contrôle','m2'=>'Sécurité','conscience'=>3],
    ['id'=>12,'name'=>'Muté — Vente Urgente','family'=>'vendeurs','age'=>'30-50','desc'=>'Deadline serrée','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>3],
    ['id'=>13,'name'=>'Propriétaire Monte en Gamme','family'=>'vendeurs','age'=>'35-50','desc'=>'Crédit-relais, timing crucial','m1'=>'Reconnaissance','m2'=>'Contrôle','conscience'=>4],
    ['id'=>14,'name'=>'Expatrié — Vente à Distance','family'=>'vendeurs','age'=>'35-60','desc'=>'0 déplacement, procuration','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>3],
    ['id'=>15,'name'=>'Investisseur qui Revend','family'=>'vendeurs','age'=>'40-65','desc'=>'Maximiser plus-value, fiscalité','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>4],
    ['id'=>16,'name'=>'Vendeur Première Fois','family'=>'vendeurs','age'=>'30-50','desc'=>'Peur de l\'arnaque, besoin rassuré','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>1],
    ['id'=>17,'name'=>'Locatif Rentabilité Pure','family'=>'investisseurs','age'=>'35-55','desc'=>'Rendement max, chiffres','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>4],
    ['id'=>18,'name'=>'Défiscalisation / Patrimoine','family'=>'investisseurs','age'=>'40-60','desc'=>'TMI élevée, Pinel/LMNP','m1'=>'Contrôle','m2'=>'Sécurité','conscience'=>3],
    ['id'=>19,'name'=>'Colocation / Étudiant','family'=>'investisseurs','age'=>'30-50','desc'=>'Multi-locataires, 6-10%','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>4],
    ['id'=>20,'name'=>'Location Courte Durée / Airbnb','family'=>'investisseurs','age'=>'30-50','desc'=>'Zone touristique, revenus élevés','m1'=>'Liberté','m2'=>'Reconnaissance','conscience'=>4],
    ['id'=>21,'name'=>'Immeuble de Rapport','family'=>'investisseurs','age'=>'40-60','desc'=>'Achète en bloc, cash-flow','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>5],
    ['id'=>22,'name'=>'Primo-Investisseur Prudent','family'=>'investisseurs','age'=>'30-40','desc'=>'Premier invest, peur de se tromper','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>2],
    ['id'=>23,'name'=>'Prépare sa Retraite','family'=>'investisseurs','age'=>'45-58','desc'=>'Patrimoine retraite, 10-15 ans','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>3],
    ['id'=>24,'name'=>'Nouveau Résident','family'=>'niches','age'=>'30-55','desc'=>'Télétravail, ne connaît rien','m1'=>'Liberté','m2'=>'Sécurité','conscience'=>2],
    ['id'=>25,'name'=>'Bailleur en Difficulté','family'=>'niches','age'=>'40-65','desc'=>'Impayés, DPE F/G','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>3],
    ['id'=>26,'name'=>'Propriétaire DPE F/G','family'=>'niches','age'=>'Tout âge','desc'=>'Interdit location 2025+','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>2],
    ['id'=>27,'name'=>'Professionnel Libéral','family'=>'niches','age'=>'30-55','desc'=>'Local pro + logement, SCI','m1'=>'Reconnaissance','m2'=>'Contrôle','conscience'=>4],
    ['id'=>28,'name'=>'Vendeur en Viager','family'=>'niches','age'=>'70-85','desc'=>'Rester chez soi, compléter retraite','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>2],
    ['id'=>29,'name'=>'Acheteur Luxe / Prestige','family'=>'niches','age'=>'40-65','desc'=>'Budget 500K+, discrétion','m1'=>'Reconnaissance','m2'=>'Contrôle','conscience'=>5],
    ['id'=>30,'name'=>'Marchand de Biens','family'=>'niches','age'=>'35-55','desc'=>'Pro, décote, volume','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>5],
];

$families = [
    'acheteurs'=>['label'=>'Acheteurs RP','color'=>'#e74c3c','icon'=>'🏠'],
    'vendeurs'=>['label'=>'Vendeurs','color'=>'#d4880f','icon'=>'🔑'],
    'investisseurs'=>['label'=>'Investisseurs','color'=>'#8b5cf6','icon'=>'📈'],
    'niches'=>['label'=>'Niches','color'=>'#10b981','icon'=>'🎯'],
];

$motivColors = [
    'Sécurité'=>['c'=>'#1e40af','bg'=>'#dbeafe'],
    'Liberté'=>['c'=>'#065f46','bg'=>'#d1fae5'],
    'Reconnaissance'=>['c'=>'#92400e','bg'=>'#fef3c7'],
    'Contrôle'=>['c'=>'#5b21b6','bg'=>'#ede9fe'],
];

$wizardSteps = [
    1=>['label'=>'Persona','icon'=>'fa-user','color'=>'#8b5cf6'],
    2=>['label'=>'Offre','icon'=>'fa-gift','color'=>'#10b981'],
    3=>['label'=>'Contenus','icon'=>'fa-file-alt','color'=>'#ea580c'],
    4=>['label'=>'Localiser','icon'=>'fa-map-marker-alt','color'=>'#f59e0b'],
    5=>['label'=>'Emails','icon'=>'fa-envelope','color'=>'#3b82f6'],
    6=>['label'=>'Formulaire','icon'=>'fa-wpforms','color'=>'#ec4899'],
    7=>['label'=>'Campagne','icon'=>'fa-rocket','color'=>'#dc2626'],
];

$conscienceLabels = ['','Non conscient','Conscient du problème','Cherche activement','Compare les solutions','Prêt à agir'];

// ── Charger secteurs ──
$secteurs = [];
try {
    $stmt = $db->prepare("SELECT id, name FROM secteurs WHERE status = 'published' ORDER BY name ASC");
    $stmt->execute();
    $secteurs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    try {
        $stmt = $db->query("SELECT id, title as name FROM pages WHERE template = 'secteur' AND status = 'published' ORDER BY title ASC");
        $secteurs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e2) {}
}

// ── Charger campagnes existantes ──
$existingCampaigns = [];
try {
    $stmt = $db->query("SELECT persona_id, id, current_step, status, steps_completed FROM np_campaigns ORDER BY id ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingCampaigns[$row['persona_id']] = $row;
    }
} catch (Throwable $e) {}

// ── Params URL ──
$campaignId  = intval($_GET['campaign_id'] ?? 0);
$currentStep = intval($_GET['step'] ?? 0);
$campaign    = null;
$selectedPersona = null;

if ($campaignId > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM np_campaigns WHERE id = ?");
        $stmt->execute([$campaignId]);
        $campaign = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($campaign) {
            foreach ($neuropersonas as $p) {
                if ($p['id'] == $campaign['persona_id']) { $selectedPersona = $p; break; }
            }
            if ($currentStep === 0) $currentStep = (int)$campaign['current_step'];
        }
    } catch (Throwable $e) {}
}
if ($currentStep === 0) $currentStep = 1;

// ══════════════════════════════════════════════════════════════
// AJAX HANDLERS (POST sur la même page)
// ══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {
    header('Content-Type: application/json');

    if (($_POST['csrf'] ?? '') !== $csrf) {
        echo json_encode(['success'=>false,'error'=>'CSRF invalide']); exit;
    }

    $action = $_POST['action'];

    // ── Créer campagne ──
    if ($action === 'create-campaign') {
        $pid = intval($_POST['persona_id'] ?? 0);
        if ($pid < 1 || $pid > 30) { echo json_encode(['success'=>false,'error'=>'Persona invalide']); exit; }

        // Check existing
        $stmt = $db->prepare("SELECT id FROM np_campaigns WHERE persona_id = ?");
        $stmt->execute([$pid]);
        $ex = $stmt->fetch();
        if ($ex) { echo json_encode(['success'=>true,'campaign_id'=>(int)$ex['id'],'existing'=>true]); exit; }

        $stmt = $db->prepare("INSERT INTO np_campaigns (persona_id, persona_name, persona_family, motivation_1, motivation_2, conscience, current_step, steps_completed, status) VALUES (?,?,?,?,?,?,1,'[1]','in_progress')");
        $stmt->execute([
            $pid,
            $_POST['persona_name'] ?? '',
            $_POST['persona_family'] ?? '',
            $_POST['m1'] ?? '',
            $_POST['m2'] ?? '',
            intval($_POST['conscience'] ?? 1),
        ]);
        echo json_encode(['success'=>true,'campaign_id'=>(int)$db->lastInsertId()]); exit;
    }

    // ── Update progress ──
    if ($action === 'update-progress') {
        $id = intval($_POST['campaign_id'] ?? 0);
        $stmt = $db->prepare("UPDATE np_campaigns SET current_step = ?, steps_completed = ?, status = 'in_progress' WHERE id = ?");
        $stmt->execute([intval($_POST['current_step'] ?? 1), $_POST['steps_completed'] ?? '[]', $id]);
        echo json_encode(['success'=>true]); exit;
    }

    // ── Save step data ──
    if ($action === 'save-step-data') {
        $cid  = intval($_POST['campaign_id'] ?? 0);
        $step = intval($_POST['step'] ?? 0);
        $html = $_POST['html'] ?? '';

        try {
            switch ($step) {
                case 2:
                    $stmt = $db->prepare("INSERT INTO np_offers (campaign_id, title, full_html, ai_generated) VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE full_html=VALUES(full_html), updated_at=NOW()");
                    $stmt->execute([$cid, $_POST['title'] ?? '', $html]);
                    break;
                case 3:
                    for ($lvl = 1; $lvl <= 5; $lvl++) {
                        $stmt = $db->prepare("INSERT INTO np_contents (campaign_id, conscience_level, content_type, title, content_html, ai_generated) VALUES (?,?,'article',?,?,1) ON DUPLICATE KEY UPDATE content_html=VALUES(content_html), updated_at=NOW()");
                        $stmt->execute([$cid, $lvl, "Contenu N{$lvl}", $html]);
                    }
                    break;
                case 5:
                    for ($lvl = 1; $lvl <= 5; $lvl++) {
                        $stmt = $db->prepare("INSERT INTO np_sequences (campaign_id, conscience_level, sequence_name, emails_json, ai_generated) VALUES (?,?,?,?,1) ON DUPLICATE KEY UPDATE emails_json=VALUES(emails_json), updated_at=NOW()");
                        $stmt->execute([$cid, $lvl, "Séquence N{$lvl}", $html]);
                    }
                    break;
                case 6:
                    $stmt = $db->prepare("INSERT INTO np_forms (campaign_id, form_name, form_html, ai_generated) VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE form_html=VALUES(form_html), updated_at=NOW()");
                    $stmt->execute([$cid, $_POST['title'] ?? '', $html]);
                    break;
            }
            echo json_encode(['success'=>true]); exit;
        } catch (Throwable $e) {
            echo json_encode(['success'=>false,'error'=>$e->getMessage()]); exit;
        }
    }

    // ── Finalize ──
    if ($action === 'finalize') {
        $id = intval($_POST['campaign_id'] ?? 0);
        $stmt = $db->prepare("UPDATE np_campaigns SET status='complete', current_step=7 WHERE id=?");
        $stmt->execute([$id]);
        echo json_encode(['success'=>true]); exit;
    }

    // ── Proxy IA (même pattern que positionnement) ──
    if ($action === 'ai-proxy') {
        $prompt = trim($_POST['prompt'] ?? '');
        $system = trim($_POST['system'] ?? '');

        if (!$prompt) { echo json_encode(['success'=>false,'error'=>'Prompt vide']); exit; }

        $apiKey = getAnthropicKey($db);
        if (!$apiKey) { echo json_encode(['success'=>false,'error'=>'Clé API non configurée']); exit; }

        $messages = [['role'=>'user','content'=>$prompt]];
        $payload = ['model'=>'claude-sonnet-4-20250514','max_tokens'=>4096,'messages'=>$messages];
        if ($system) $payload['system'] = $system;

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: '.$apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) { echo json_encode(['success'=>false,'error'=>'cURL: '.$curlErr]); exit; }

        $decoded = json_decode($response, true);
        $text = $decoded['content'][0]['text'] ?? '';
        if (!$text) { echo json_encode(['success'=>false,'error'=>'Réponse vide','raw'=>$decoded]); exit; }

        // Clean markdown fences
        $text = preg_replace('/^```html?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);

        echo json_encode(['success'=>true,'text'=>$text]); exit;
    }

    echo json_encode(['success'=>false,'error'=>'Action inconnue']); exit;
}

// ── JSON pour JS ──
$personasJson  = json_encode($neuropersonas, JSON_UNESCAPED_UNICODE);
$familiesJson  = json_encode($families, JSON_UNESCAPED_UNICODE);
$motivJson     = json_encode($motivColors, JSON_UNESCAPED_UNICODE);
$stepsJson     = json_encode($wizardSteps, JSON_UNESCAPED_UNICODE);
$conscienceJson = json_encode($conscienceLabels, JSON_UNESCAPED_UNICODE);
$secteursJson  = json_encode($secteurs, JSON_UNESCAPED_UNICODE);
$existingJson  = json_encode($existingCampaigns, JSON_UNESCAPED_UNICODE);
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500;700&display=swap');

.npw {
    --accent: #8b5cf6;
    --gold: #c9913b;
    --surface: var(--surface, #fff);
    --surface2: var(--surface-2, #f9fafb);
    --border: var(--border, #e5e7eb);
    --text: var(--text, #111827);
    --text2: var(--text-2, #6b7280);
    --text3: var(--text-3, #9ca3af);
    --radius: 12px;
    font-family: 'DM Sans', sans-serif;
    max-width: 1080px; margin: 0 auto; padding: 24px 24px 60px;
}

/* Header */
.npw-header { display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:20px;flex-wrap:wrap; }
.npw-eyebrow { display:inline-flex;align-items:center;gap:6px;font-size:.65rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--gold);margin-bottom:6px; }
.npw-title { font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:var(--text);letter-spacing:-.02em;margin:0 0 4px; }
.npw-subtitle { font-size:.8rem;color:var(--text2); }

/* Stepper */
.npw-stepper { display:flex;align-items:center;gap:0;margin-bottom:22px;padding:12px 16px;background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow-x:auto; }
.npw-step { display:flex;align-items:center;gap:7px;padding:7px 12px;border-radius:10px;cursor:pointer;transition:all .15s;flex-shrink:0; }
.npw-step:hover { background:var(--surface2); }
.npw-step.active { background:rgba(139,92,246,.08); }
.npw-step.done { opacity:.7; }
.npw-step-num { width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;background:#cbd5e1;flex-shrink:0;transition:background .15s; }
.npw-step.active .npw-step-num { background:var(--accent); }
.npw-step.done .npw-step-num { background:#10b981; }
.npw-step-label { font-size:.75rem;font-weight:700;color:var(--text); }
.npw-step-line { width:16px;height:2px;background:var(--border);flex-shrink:0; }

/* Main card */
.npw-card { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:0 1px 3px rgba(0,0,0,.08);overflow:hidden; }
.npw-card-hd { display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border);background:var(--surface2); }
.npw-card-hd-icon { width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;color:#fff; }
.npw-card-hd h2 { font-family:'Syne',sans-serif;font-size:.88rem;font-weight:800;color:var(--text);margin:0;flex:1; }
.npw-card-body { padding:20px; }

/* Layout 2 cols */
.npw-layout { display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start; }
@media(max-width:900px) { .npw-layout { grid-template-columns:1fr; } }

/* Persona grid */
.npw-filter { display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap; }
.npw-filter-btn { padding:5px 12px;font-size:.72rem;font-weight:600;border-radius:20px;border:2px solid transparent;cursor:pointer;font-family:inherit;transition:all .15s;background:var(--surface2);color:var(--text2); }
.npw-filter-btn.active { border-color:currentColor;font-weight:700; }
.npw-pgrid { display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:8px; }
.npw-pcard { padding:12px 14px;border:2px solid var(--border);border-radius:10px;cursor:pointer;transition:all .18s;position:relative; }
.npw-pcard:hover { border-color:var(--accent);box-shadow:0 4px 16px rgba(139,92,246,.1);transform:translateY(-1px); }
.npw-pcard.exists { opacity:.55; }
.npw-pcard.exists::after { content:'En cours';position:absolute;top:6px;right:6px;font-size:8px;font-weight:700;padding:2px 7px;border-radius:4px;background:#d1fae5;color:#065f46; }
.npw-pcard-top { display:flex;align-items:center;gap:7px;margin-bottom:4px; }
.npw-pcard-num { font-size:9px;font-weight:700;width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0; }
.npw-pcard-name { font-size:.78rem;font-weight:700;color:var(--text); }
.npw-pcard-desc { font-size:.7rem;color:var(--text2);line-height:1.4;margin-bottom:5px; }
.npw-pcard-meta { display:flex;align-items:center;justify-content:space-between; }
.npw-tag { font-size:8px;font-weight:600;padding:1px 5px;border-radius:3px; }
.npw-dots { display:flex;gap:2px; }
.npw-dot { width:5px;height:5px;border-radius:50%;background:#e5e7eb; }
.npw-dot.on { background:#f59e0b; }

/* Gen panel */
.npw-gen-wrap { display:flex;flex-direction:column;gap:14px; }
.npw-gen-info { background:var(--surface2);border:1px solid var(--border);border-radius:10px;padding:16px; }
.npw-gen-info h3 { font-size:.85rem;font-weight:700;color:var(--text);margin:0 0 8px; }
.npw-gen-info p { font-size:.78rem;color:var(--text2);line-height:1.6;margin:0 0 12px; }
.npw-gen-btn { display:inline-flex;align-items:center;gap:7px;padding:9px 22px;background:linear-gradient(135deg,var(--accent),#6d28d9);color:#fff;border:none;border-radius:9px;font-size:.8rem;font-weight:700;cursor:pointer;font-family:inherit;transition:transform .15s,box-shadow .15s; }
.npw-gen-btn:hover { transform:translateY(-1px);box-shadow:0 4px 16px rgba(139,92,246,.3); }
.npw-gen-btn:disabled { opacity:.5;cursor:not-allowed;transform:none; }
.npw-preview { background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:18px;min-height:200px; }
.npw-preview h4 { font-size:.82rem;font-weight:700;color:var(--text);margin:0 0 10px; }
.npw-preview-body { font-size:.78rem;line-height:1.7;color:#334155; }
.npw-preview-body h3 { font-size:.82rem;font-weight:700;margin:12px 0 6px;color:var(--text); }
.npw-preview-body h4 { font-size:.78rem;font-weight:700;margin:10px 0 4px; }
.npw-preview-body ul { padding-left:16px;margin:4px 0; }
.npw-preview-body li { margin-bottom:2px; }
.npw-preview-body table { width:100%;border-collapse:collapse;font-size:.75rem;margin:8px 0; }
.npw-preview-body th,.npw-preview-body td { padding:6px 8px;border:1px solid var(--border);text-align:left; }
.npw-preview-body th { background:var(--surface2);font-weight:700; }

/* Sidebar persona summary */
.npw-sidebar { display:flex;flex-direction:column;gap:12px; }
.npw-ps { background:#1a1a2e;border-radius:var(--radius);padding:16px;color:#fff; }
.npw-ps h4 { font-size:.6rem;text-transform:uppercase;letter-spacing:.06em;opacity:.5;margin:0 0 6px; }
.npw-ps-name { font-size:1rem;font-weight:800;margin-bottom:2px; }
.npw-ps-family { font-size:.7rem;opacity:.6;margin-bottom:10px; }
.npw-ps-row { display:flex;justify-content:space-between;padding:5px 0;border-top:1px solid rgba(255,255,255,.08);font-size:.7rem; }
.npw-ps-row .lbl { opacity:.5; }
.npw-progress { background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:14px; }
.npw-progress h4 { font-size:.75rem;font-weight:700;color:var(--text);margin:0 0 8px; }
.npw-prog-item { display:flex;align-items:center;gap:7px;padding:4px 0;font-size:.7rem;color:var(--text2); }
.npw-prog-item i { font-size:9px;width:14px;text-align:center; }
.npw-prog-item.done { color:#10b981; }
.npw-prog-item.active { color:var(--accent);font-weight:700; }

/* Footer nav */
.npw-footer { display:flex;justify-content:space-between;align-items:center;margin-top:18px;padding-top:14px;border-top:1px solid var(--border); }
.npw-fbtn { padding:9px 20px;border-radius:9px;font-size:.8rem;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;display:inline-flex;align-items:center;gap:6px;text-decoration:none; }
.npw-fbtn-prev { background:var(--surface2);border:1px solid var(--border);color:var(--text2); }
.npw-fbtn-prev:hover { background:var(--border); }
.npw-fbtn-next { background:var(--accent);border:none;color:#fff; }
.npw-fbtn-next:hover { background:#7c3aed; }

/* Spinner */
.npw-spinner { width:14px;height:14px;border-radius:50%;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;animation:npwSpin .6s linear infinite;display:inline-block; }
@keyframes npwSpin { to { transform:rotate(360deg); } }

/* Toast */
.npw-toast { position:fixed;bottom:24px;right:24px;z-index:9999;background:#111827;color:#fff;padding:10px 16px;border-radius:9px;font-size:.78rem;font-weight:600;display:flex;align-items:center;gap:8px;box-shadow:0 8px 24px rgba(0,0,0,.2);transform:translateY(20px);opacity:0;transition:transform .25s,opacity .25s;pointer-events:none; }
.npw-toast.show { transform:none;opacity:1; }

/* Anim */
.anim { animation:fadeUp .25s ease both; }
.d1{animation-delay:.05s}.d2{animation-delay:.10s}.d3{animation-delay:.15s}
@keyframes fadeUp{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:none;}}
</style>

<div class="npw">

    <!-- Header -->
    <div class="npw-header anim">
        <div>
            <div class="npw-eyebrow"><i class="fas fa-rocket"></i> Campagnes NeuroPersona</div>
            <h1 class="npw-title">Wizard Campagne</h1>
            <p class="npw-subtitle">Créez une campagne de communication complète pour chaque persona — guidé étape par étape</p>
        </div>
        <a href="?page=neuropersona" class="npw-fbtn npw-fbtn-prev" style="font-size:.72rem">
            <i class="fas fa-th"></i> Cartographie
        </a>
    </div>

    <?php if (!$has_api_key): ?>
    <div style="background:#fef3c7;border:1px solid #fcd34d;border-radius:9px;padding:10px 14px;margin-bottom:14px;font-size:.78rem;color:#92400e;display:flex;align-items:center;gap:8px" class="anim">
        <i class="fas fa-triangle-exclamation"></i>
        Clé API non configurée — <a href="?page=api-keys" style="color:#92400e;font-weight:700">Configurer →</a>
    </div>
    <?php endif; ?>

    <!-- Stepper -->
    <div class="npw-stepper anim d1" id="npwStepper"></div>

    <!-- Body -->
    <div id="npwBody" class="anim d2"></div>

    <!-- Footer -->
    <div class="npw-footer" id="npwFooter"></div>

</div>
<div class="npw-toast" id="npwToast"><i class="fas fa-check-circle" style="color:#10b981"></i><span id="npwToastMsg"></span></div>

<script>
(function() {
'use strict';

const CSRF = <?= json_encode($csrf) ?>;
const PAGE_URL = '?page=neuropersona-wizard';
const HAS_API  = <?= json_encode($has_api_key) ?>;
const personas = <?= $personasJson ?>;
const families = <?= $familiesJson ?>;
const motivC   = <?= $motivJson ?>;
const STEPS    = <?= $stepsJson ?>;
const cLabels  = <?= $conscienceJson ?>;
const secteurs = <?= $secteursJson ?>;
const existing = <?= $existingJson ?>;

let S = {
    step: <?= $currentStep ?>,
    cid: <?= $campaignId ?>,
    persona: <?= $selectedPersona ? json_encode($selectedPersona, JSON_UNESCAPED_UNICODE) : 'null' ?>,
    done: <?= $campaign ? ($campaign['steps_completed'] ?: '[]') : '[]' ?>,
    famFilter: 'all',
    busy: false,
    data: {},
};

const $stepper = document.getElementById('npwStepper');
const $body    = document.getElementById('npwBody');
const $footer  = document.getElementById('npwFooter');

function toast(msg) {
    const t = document.getElementById('npwToast');
    document.getElementById('npwToastMsg').textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2800);
}

function tag(m) { const c = motivC[m]; return c ? `<span class="npw-tag" style="background:${c.bg};color:${c.c}">${m}</span>` : ''; }
function dots(n) { let h='<span class="npw-dots">'; for(let i=1;i<=5;i++) h+=`<span class="npw-dot${i<=n?' on':''}"></span>`; return h+'</span>'; }

// ── POST helper ──
async function post(data) {
    const fd = new FormData();
    fd.append('csrf', CSRF);
    Object.entries(data).forEach(([k,v]) => fd.append(k, typeof v === 'object' ? JSON.stringify(v) : v));
    const r = await fetch(PAGE_URL, { method:'POST', body:fd });
    return r.json();
}

// ── Stepper ──
function renderStepper() {
    let h = '';
    Object.entries(STEPS).forEach(([n, s], i) => {
        n = parseInt(n);
        const isDone = S.done.includes(n);
        const isAct  = S.step === n;
        const cls = isAct ? 'active' : (isDone ? 'done' : '');
        if (i > 0) h += '<div class="npw-step-line"></div>';
        h += `<div class="npw-step ${cls}" onclick="W.go(${n})">
            <span class="npw-step-num" style="${isAct?'background:'+s.color:''}">${isDone && !isAct ? '<i class="fas fa-check" style="font-size:9px"></i>' : n}</span>
            <span class="npw-step-label">${s.label}</span>
        </div>`;
    });
    $stepper.innerHTML = h;
}

function renderFooter() {
    const prev = S.step > 1;
    const next = S.step < 7 && S.persona;
    let h = prev ? `<a class="npw-fbtn npw-fbtn-prev" onclick="W.go(${S.step-1})"><i class="fas fa-arrow-left"></i> Précédent</a>` : '<div></div>';
    if (S.step === 1 && !S.persona) h += '<span style="font-size:.72rem;color:var(--text3)">Sélectionnez un persona</span>';
    else if (next) h += `<a class="npw-fbtn npw-fbtn-next" onclick="W.go(${S.step+1})">Suivant <i class="fas fa-arrow-right"></i></a>`;
    else if (S.step === 7) h += `<a class="npw-fbtn npw-fbtn-next" style="background:#10b981" onclick="W.finalize()"><i class="fas fa-check"></i> Finaliser</a>`;
    $footer.innerHTML = h;
}

function sidebarHTML() {
    const p = S.persona; if (!p) return '';
    const f = families[p.family];
    return `<div class="npw-sidebar">
        <div class="npw-ps">
            <h4>Persona sélectionné</h4>
            <div class="npw-ps-name">#${p.id} ${p.name}</div>
            <div class="npw-ps-family">${f.icon} ${f.label} · ${p.age} ans</div>
            <div class="npw-ps-row"><span class="lbl">Motivation 1</span>${p.m1}</div>
            <div class="npw-ps-row"><span class="lbl">Motivation 2</span>${p.m2}</div>
            <div class="npw-ps-row"><span class="lbl">Conscience</span>${p.conscience}/5 — ${cLabels[p.conscience]}</div>
            <div class="npw-ps-row"><span class="lbl">Secteurs</span>${secteurs.length}</div>
        </div>
        <div class="npw-progress">
            <h4>Progression</h4>
            ${Object.entries(STEPS).map(([n,s])=>{
                n=parseInt(n); const d=S.done.includes(n); const a=S.step===n;
                return `<div class="npw-prog-item ${d?'done':a?'active':''}"><i class="fas ${d?'fa-check-circle':a?'fa-arrow-circle-right':'fa-circle'}"></i> ${s.label}</div>`;
            }).join('')}
        </div>
    </div>`;
}

// ── Step 1 ──
function renderStep1() {
    let fh = '<div class="npw-filter">';
    fh += `<button class="npw-filter-btn ${S.famFilter==='all'?'active':''}" onclick="W.filter('all')" style="color:var(--text2)">Tous (30)</button>`;
    Object.entries(families).forEach(([k,f]) => {
        const cnt = personas.filter(p=>p.family===k).length;
        fh += `<button class="npw-filter-btn ${S.famFilter===k?'active':''}" onclick="W.filter('${k}')" style="color:${f.color}">${f.icon} ${f.label} (${cnt})</button>`;
    });
    fh += '</div>';

    const list = S.famFilter === 'all' ? personas : personas.filter(p => p.family === S.famFilter);
    let gh = '<div class="npw-pgrid">';
    list.forEach(p => {
        const f = families[p.family];
        const ex = existing[p.id];
        gh += `<div class="npw-pcard ${ex?'exists':''}" style="border-left:3px solid ${f.color}" onclick="W.pick(${p.id})">
            <div class="npw-pcard-top"><span class="npw-pcard-num" style="background:${f.color}">${p.id}</span><span class="npw-pcard-name">${p.name}</span></div>
            <div class="npw-pcard-desc"><strong>${p.age} ans</strong> — ${p.desc}</div>
            <div class="npw-pcard-meta"><div>${tag(p.m1)}${tag(p.m2)}</div>${dots(p.conscience)}</div>
        </div>`;
    });
    gh += '</div>';

    $body.innerHTML = `<div class="npw-card">
        <div class="npw-card-hd"><div class="npw-card-hd-icon" style="background:var(--accent)"><i class="fas fa-user"></i></div><h2>Étape 1 — Choisir le persona cible</h2></div>
        <div class="npw-card-body">
            <p style="font-size:.78rem;color:var(--text2);margin:0 0 14px">Sélectionnez le persona pour lequel créer une campagne complète. Personas déjà activés marqués « En cours ».</p>
            ${fh}${gh}
        </div>
    </div>`;
}

// ── Steps 2-7 ──
function renderGenStep(n) {
    const p = S.persona;
    const si = STEPS[n];
    const configs = {
        2:{t:"Générer l'offre",d:`Offre irrésistible pour "${p.name}" : accroche, promesse, preuves, CTA, objections.`,btn:"Générer l'offre"},
        3:{t:"5 contenus par conscience",d:`5 articles (1/niveau Schwartz 1→5) adaptés à ${p.m1} + ${p.m2}.`,btn:"Générer les 5 contenus"},
        4:{t:"Localiser × secteurs",d:`Adapter les 5 contenus pour ${secteurs.length} secteur(s) = ${5*Math.max(secteurs.length,1)} contenus localisés.`,btn:"Localiser"},
        5:{t:"Séquences email",d:`5 séquences (1/niveau) avec 3-5 emails progressifs pour "${p.name}".`,btn:"Générer les séquences"},
        6:{t:"Formulaire de capture",d:`Formulaire optimisé : headline, champs, CTA, lead magnet pour "${p.name}".`,btn:"Générer le formulaire"},
        7:{t:"Campagne complète",d:`Récapitulatif : offre + contenus + emails + formulaire + KPIs + planning.`,btn:"Générer le récapitulatif"},
    };
    const c = configs[n];
    const hasData = !!S.data[n];

    $body.innerHTML = `<div class="npw-layout">
        <div class="npw-gen-wrap">
            <div class="npw-card">
                <div class="npw-card-hd"><div class="npw-card-hd-icon" style="background:${si.color}"><i class="fas ${si.icon}"></i></div><h2>Étape ${n} — ${c.t}</h2></div>
                <div class="npw-card-body">
                    <div class="npw-gen-info">
                        <h3>${c.t}</h3>
                        <p>${c.d}</p>
                        <button class="npw-gen-btn" id="genBtn" onclick="W.gen(${n})" ${S.busy?'disabled':''}>
                            ${S.busy ? '<span class="npw-spinner"></span> Génération…' : '<i class="fas fa-wand-magic-sparkles"></i> ' + c.btn}
                        </button>
                    </div>
                    <div class="npw-preview" style="margin-top:14px">
                        <h4>${hasData ? '✅ Contenu généré' : '⏳ En attente'}</h4>
                        <div class="npw-preview-body" id="previewBody">
                            ${hasData ? S.data[n] : '<p style="color:var(--text3);font-style:italic">Cliquez sur le bouton pour générer avec l\'IA.</p>'}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        ${sidebarHTML()}
    </div>`;
}

// ── Actions ──
const W = {};

W.filter = f => { S.famFilter = f; render(); };

W.pick = async (id) => {
    const p = personas.find(x => x.id === id);
    if (!p) return;
    const ex = existing[id];
    if (ex) {
        if (!confirm(`Campagne existante pour "${p.name}" (étape ${ex.current_step}/7). Reprendre ?`)) return;
        S.cid = parseInt(ex.id); S.persona = p;
        S.done = JSON.parse(ex.steps_completed || '[]');
        S.step = parseInt(ex.current_step);
        render(); return;
    }
    S.persona = p;
    try {
        const data = await post({action:'create-campaign',persona_id:p.id,persona_name:p.name,persona_family:p.family,m1:p.m1,m2:p.m2,conscience:p.conscience});
        if (data.success) {
            S.cid = data.campaign_id;
            existing[p.id] = {id:data.campaign_id,current_step:1,status:'draft',steps_completed:'[1]'};
            S.done = [1]; S.step = 2;
            toast('Campagne créée — '+p.name);
        } else { alert(data.error || 'Erreur'); }
    } catch(e) { console.error(e); S.step = 2; }
    render();
};

W.gen = async (n) => {
    if (S.busy || !HAS_API) return;
    S.busy = true; render();

    const p = S.persona;
    const sNames = secteurs.map(s=>s.name).join(', ') || 'Non configurés';
    const prompts = {
        2: `Génère une offre complète pour le persona immobilier "${p.name}" (${p.age} ans, ${p.desc}). Motivations: ${p.m1} + ${p.m2}. Conscience: ${p.conscience}/5.
Structure en HTML (h3, p, ul, li, strong, em):
1. Titre accrocheur 2. Promesse principale 3. 3 preuves 4. Détail offre 5. CTA (motivation ${p.m1}) 6. Réponses aux 3 objections`,
        3: `Génère 5 contenus (titre + plan détaillé + meta SEO) pour "${p.name}" (${p.m1}+${p.m2}), UN par niveau de conscience Schwartz (1→5).
Niveau 1=éducatif, 2=guide pratique, 3=comparatif, 4=différenciation, 5=conversion.
Pour chaque: titre SEO, meta 150 chars, plan 5 sections, 3 mots-clés, type CTA. HTML.`,
        4: `Pour le persona "${p.name}", adapte les 5 contenus pour ces secteurs: ${sNames}.
Par secteur: titre localisé, hook local 2 phrases, 3 mots-clés locaux, 1 stat locale. Tableau HTML récapitulatif.`,
        5: `Génère 5 séquences email pour "${p.name}" (${p.m1}+${p.m2}), une par niveau de conscience.
Par séquence: nom, déclencheur, 3-5 emails (objet + aperçu + délai), CTA final. Niveau 1=doux, 5=urgent. HTML.`,
        6: `Génère un formulaire de capture pour "${p.name}" (${p.m1}+${p.m2}).
1. Headline (max 10 mots) 2. Sous-titre 3. Champs (prénom, email, tel + 1-2 spécifiques) 4. CTA bouton 5. Lead magnet 6. Merci message 7. 3 réassurances. HTML.`,
        7: `Récapitulatif campagne pour "${p.name}" (#${p.id}, ${families[p.family].label}).
Motivations: ${p.m1}+${p.m2}. Conscience: ${p.conscience}/5. Secteurs: ${sNames} (${secteurs.length}).
Dashboard HTML: vue d'ensemble, offre résumé, tableau 5 niveaux × secteurs, séquences, formulaire, KPIs, planning.`,
    };

    try {
        const data = await post({action:'ai-proxy', prompt:prompts[n], system:'Tu es un expert en neuromarketing immobilier. Réponds en HTML structuré (h3,h4,p,ul,li,strong,em,table). Pas de markdown.'});
        S.busy = false;
        if (data.success) {
            S.data[n] = data.text;
            if (!S.done.includes(n)) S.done.push(n);
            // Save to DB
            post({action:'save-step-data', campaign_id:S.cid, step:n, html:data.text, title:p.name});
            post({action:'update-progress', campaign_id:S.cid, current_step:Math.min(n+1,7), steps_completed:JSON.stringify(S.done)});
            toast('Étape '+n+' générée');
        } else {
            S.data[n] = '<p style="color:#dc2626">Erreur: '+(data.error||'Échec')+'</p>';
        }
    } catch(e) {
        S.busy = false;
        S.data[n] = '<p style="color:#dc2626">Erreur réseau</p>';
    }
    render();
};

W.go = n => { if (n > 1 && !S.persona) return; S.step = n; render(); };

W.finalize = async () => {
    if (!S.cid) return;
    await post({action:'finalize', campaign_id:S.cid});
    toast('Campagne finalisée !');
    setTimeout(() => window.location.href = '?page=neuropersona', 1000);
};

function render() { renderStepper(); S.step===1 ? renderStep1() : renderGenStep(S.step); renderFooter(); }
render();

window.W = W;
})();
</script>