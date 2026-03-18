<?php
/**
 * PARAMETRES IA -- Cles API & Prompts systeme
 * /admin/modules/system/settings/ai_settings.php
 * Acces : dashboard.php?page=ai-settings
 * v2.0 : chemin dynamique, Database::getInstance, stat-cards, harmonise layout
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);

ob_start();

$rootPath = dirname(__DIR__, 4); // settings/ -> system/ -> modules/ -> admin/ -> public_html
if (!defined('DB_HOST'))       require_once $rootPath . '/config/config.php';
if (!class_exists('Database')) require_once $rootPath . '/includes/classes/Database.php';

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    ob_end_clean();
    die('<div style="padding:20px;color:#dc2626;font-family:monospace">DB: ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// -- CSRF ------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

// -- Table ai_settings (fallback si absente) --------------------------------
try {
    $db->exec("CREATE TABLE IF NOT EXISTS `ai_settings` (
        `setting_key`   VARCHAR(100) PRIMARY KEY,
        `setting_value` LONGTEXT,
        `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {}

// -- Helpers ---------------------------------------------------------------
function aiGet(PDO $db, string $key, string $default = ''): string {
    try {
        $s = $db->prepare("SELECT setting_value FROM ai_settings WHERE setting_key = ? LIMIT 1");
        $s->execute([$key]);
        $r = $s->fetchColumn();
        return $r !== false ? (string) $r : $default;
    } catch (Throwable $e) { return $default; }
}

function aiSet(PDO $db, string $key, string $value): void {
    $db->prepare("INSERT INTO ai_settings (setting_key, setting_value)
                  VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)")
       ->execute([$key, $value]);
}

function aiMask(string $k): string {
    if (!$k) return '';
    if (strlen($k) <= 8) return str_repeat('*', strlen($k));
    return substr($k, 0, 8) . str_repeat('*', max(0, strlen($k) - 12)) . substr($k, -4);
}

// -- Prompts par defaut ----------------------------------------------------
$defaultPrompts = [
    'generate' => "Tu es un expert en redaction SEO pour l'immobilier francais. Tu rediges des articles professionnels et optimises pour les conseillers immobiliers independants. Tu maitrises le copywriting neuro-emotionnel, les bonnes pratiques SEO on-page et la reglementation immobiliere francaise. Tes articles sont toujours structures (intro accrocheuse, sections H2/H3, conclusion CTA), informatifs et adaptes au persona cible. Tu reponds UNIQUEMENT en JSON valide.",
    'improve'  => "Tu es expert en redaction SEO immobilier France. Tu ameliores les contenus pour maximiser l'engagement, la lisibilite et le positionnement Google. Tu enrichis le texte avec des transitions fluides, des exemples concrets et des mots-cles semantiques. Tu reponds UNIQUEMENT en JSON valide.",
    'meta'     => "Tu es expert SEO immobilier France. Tu generes des meta-titres et meta-descriptions optimises pour Google : concis, accrocheurs, avec le mot-cle en debut de title et un call-to-action dans la description. Tu respectes les limites de caracteres. Tu reponds UNIQUEMENT en JSON valide.",
    'faq'      => "Tu es expert immobilier francais. Tu generes des FAQ Schema.org pertinentes et naturelles. Tes questions refletent les vraies preoccupations des acheteurs, vendeurs et investisseurs. Tes reponses sont completes (2-4 phrases), utiles et encouragent la confiance. Tu reponds UNIQUEMENT en JSON valide.",
    'outline'  => "Tu es strategiste editorial SEO immobilier France. Tu crees des plans d'articles structures : titre principal + 4-6 sections H2 + sous-sections H3 si necessaire. Tu proposes 3 variantes de titre SEO accrocheuses. Tu reponds UNIQUEMENT en JSON valide.",
    'keywords' => "Tu es expert SEO immobilier France. Tu extrais les mots-cles strategiques d'un contenu : mot-cle principal, mots-cles secondaires semantiques, expressions longue traine et mots-cles locaux. Tu classes par pertinence et intention de recherche. Tu reponds UNIQUEMENT en JSON valide.",
    'rewrite'  => "Tu es copywriter immobilier France specialise en adaptation de contenu. Tu recris des articles en adaptant le ton, le vocabulaire et les arguments au persona cible, sans changer les faits. Tu conserves la structure H2/H3 mais reformules le corps du texte. Tu reponds UNIQUEMENT en JSON valide.",
    'excerpt'  => "Tu es copywriter immobilier France. Tu rediges des extraits/chapos accrocheurs de 150-180 caracteres : une promesse forte, le mot-cle naturellement integre, une question ou affirmation qui donne envie de lire. Tu reponds UNIQUEMENT en JSON valide.",
];

$promptMeta = [
    'generate' => ['label' => 'Generation article complet',  'icon' => 'fa-wand-magic-sparkles', 'color' => '#7c3aed'],
    'improve'  => ['label' => 'Amelioration contenu',        'icon' => 'fa-arrow-up-right-dots', 'color' => '#2563eb'],
    'meta'     => ['label' => 'Metas SEO (title + desc)',    'icon' => 'fa-magnifying-glass',    'color' => '#0891b2'],
    'faq'      => ['label' => 'FAQ Schema.org',              'icon' => 'fa-circle-question',     'color' => '#059669'],
    'outline'  => ['label' => 'Plan editorial',              'icon' => 'fa-list-check',          'color' => '#d97706'],
    'keywords' => ['label' => 'Extraction mots-cles',        'icon' => 'fa-tags',                'color' => '#dc2626'],
    'rewrite'  => ['label' => 'Reecriture persona',          'icon' => 'fa-repeat',              'color' => '#7c3aed'],
    'excerpt'  => ['label' => 'Generation extrait/chapo',    'icon' => 'fa-quote-right',         'color' => '#0891b2'],
];

// -- Traitement POST -------------------------------------------------------
$saveMsg = '';
$saveErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $saveErr = 'Token CSRF invalide.';
    } else {
        $action = $_POST['ai_action'] ?? '';

        // Sauvegarde modeles & tokens uniquement (les cles sont dans settings/api)
        if ($action === 'save_keys') {
            $fields = ['ai_model_anthropic', 'ai_model_openai', 'ai_max_tokens'];
            foreach ($fields as $k) {
                $v = trim($_POST[$k] ?? '');
                try { aiSet($db, $k, $v); } catch (Exception $e) {}
            }
            $saveMsg = 'Modeles et parametres sauvegardes.';

        // Sauvegarde prompt
        } elseif ($action === 'save_prompt') {
            $pKey = $_POST['prompt_key'] ?? '';
            $pVal = trim($_POST['prompt_value'] ?? '');
            if (array_key_exists($pKey, $defaultPrompts) && $pVal) {
                try {
                    aiSet($db, 'prompt_' . $pKey, $pVal);
                    $saveMsg = 'Prompt "' . ($promptMeta[$pKey]['label'] ?? $pKey) . '" sauvegarde.';
                } catch (Exception $e) { $saveErr = 'Erreur DB : ' . $e->getMessage(); }
            }

        // Reset prompt
        } elseif ($action === 'reset_prompt') {
            $pKey = $_POST['prompt_key'] ?? '';
            if (array_key_exists($pKey, $defaultPrompts)) {
                try {
                    $db->prepare("DELETE FROM ai_settings WHERE setting_key = ?")->execute(['prompt_' . $pKey]);
                    $saveMsg = 'Prompt "' . ($promptMeta[$pKey]['label'] ?? $pKey) . '" reinitialise.';
                } catch (Exception $e) { $saveErr = 'Erreur DB : ' . $e->getMessage(); }
            }

        // Test de cle via cURL
        } elseif ($action === 'test_key') {
            $provider = $_POST['test_provider'] ?? '';
            $key      = trim(aiGet($db, $provider . '_api_key'));
            if (!$key || str_contains($key, '*')) {
                // Fallback config.php
                if ($provider === 'anthropic' && defined('ANTHROPIC_API_KEY')) $key = ANTHROPIC_API_KEY;
                if ($provider === 'openai'    && defined('OPENAI_API_KEY'))    $key = OPENAI_API_KEY;
            }
            if (!$key) {
                $saveErr = 'Cle API non configuree.';
            } else {
                try {
                    if ($provider === 'anthropic') {
                        $ch = curl_init('https://api.anthropic.com/v1/messages');
                        curl_setopt_array($ch, [
                            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 15,
                            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01'],
                            CURLOPT_POSTFIELDS => json_encode(['model' => 'claude-haiku-4-5-20251001', 'max_tokens' => 10, 'messages' => [['role' => 'user', 'content' => 'OK']]]),
                        ]);
                        curl_exec($ch);
                        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        $saveMsg = $code === 200 ? 'Cle Anthropic valide (HTTP 200)' : 'Erreur Anthropic HTTP ' . $code;
                        if ($code !== 200) $saveErr = $saveMsg; $saveMsg = '';
                    } else {
                        $ch = curl_init('https://api.openai.com/v1/models');
                        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
                            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key]]);
                        curl_exec($ch);
                        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        $saveMsg = $code === 200 ? 'Cle OpenAI valide (HTTP 200)' : 'Erreur OpenAI HTTP ' . $code;
                        if ($code !== 200) { $saveErr = $saveMsg; $saveMsg = ''; }
                    }
                } catch (Exception $e) { $saveErr = 'Erreur cURL : ' . $e->getMessage(); }
            }
        }
    }
}

// -- Valeurs courantes ----------------------------------------------------
$anthropicKey   = aiGet($db, 'anthropic_api_key');
$openaiKey      = aiGet($db, 'openai_api_key');
$modelAnthropic = aiGet($db, 'ai_model_anthropic', 'claude-sonnet-4-6');
$modelOpenai    = aiGet($db, 'ai_model_openai',    'gpt-4o-mini');
$maxTokens      = (int) aiGet($db, 'ai_max_tokens', '3000');

$cfgAnthropic   = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : '';
$cfgOpenai      = defined('OPENAI_API_KEY')     ? OPENAI_API_KEY    : '';
$activeAnthropic = $anthropicKey ?: $cfgAnthropic;
$activeOpenai    = $openaiKey    ?: $cfgOpenai;
$currentProvider = $activeAnthropic ? 'Anthropic Claude' : ($activeOpenai ? 'OpenAI GPT' : '');

// Prompts personnalises
$customPrompts = 0;
foreach (array_keys($defaultPrompts) as $k) {
    if (aiGet($db, 'prompt_' . $k)) $customPrompts++;
}

$activeTab = $_GET['tab'] ?? 'modeles';

ob_end_clean();
?>

<style>
/* == Parametres IA v2.0 -- harmonise layout ======================== */

/* Banner IA */
.ais-banner {
    background:linear-gradient(135deg,#7c3aed,#6d28d9);
    border-radius:var(--radius-lg); padding:20px 24px; margin-bottom:16px;
    display:flex; align-items:center; justify-content:space-between;
    position:relative; overflow:hidden; flex-wrap:wrap; gap:12px;
}
.ais-banner::before {
    content:''; position:absolute; top:-40%; right:-5%;
    width:220px; height:220px;
    background:radial-gradient(circle,rgba(255,255,255,.07),transparent 70%);
    border-radius:50%; pointer-events:none;
}
.ais-banner-title { font-size:1.1rem; font-weight:800; color:#fff; display:flex; align-items:center; gap:9px; margin:0 0 3px; position:relative; z-index:1; }
.ais-banner-sub   { color:rgba(255,255,255,.7); font-size:11px; margin:0; position:relative; z-index:1; }
.ais-provider-pill {
    display:inline-flex; align-items:center; gap:7px;
    padding:5px 13px; border-radius:99px; font-size:11px; font-weight:700;
    background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.25);
    position:relative; z-index:1;
}
.ais-provider-pill .dot { width:7px; height:7px; border-radius:50%; background:#10b981; }
.ais-provider-pill .dot.off { background:rgba(255,255,255,.4); }

/* Onglets */
.ais-tabs { display:flex; gap:3px; margin-bottom:16px; background:var(--surface); padding:4px; border-radius:var(--radius-lg); border:1px solid var(--border); width:fit-content; }
.ais-tab  { display:inline-flex; align-items:center; gap:6px; padding:7px 16px; border-radius:var(--radius); border:none; font-size:11px; font-weight:700; cursor:pointer; transition:all .14s; background:transparent; color:var(--text-3); font-family:var(--font); }
.ais-tab:hover  { color:#7c3aed; background:#faf5ff; }
.ais-tab.active { background:#7c3aed; color:#fff; box-shadow:0 2px 8px rgba(124,58,237,.25); }
.ais-tab .cnt   { background:rgba(255,255,255,.25); padding:1px 6px; border-radius:6px; font-size:9px; }

/* Cards contenu */
.ais-card        { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); margin-bottom:14px; box-shadow:var(--shadow-sm); overflow:hidden; }
.ais-card-hd     { padding:12px 18px; border-bottom:1px solid var(--border); background:var(--surface-2); display:flex; align-items:center; justify-content:space-between; gap:10px; }
.ais-card-title  { display:flex; align-items:center; gap:8px; font-size:12px; font-weight:700; color:var(--text); }
.ais-card-body   { padding:18px; }
.ais-card-ft     { padding:10px 18px; border-top:1px solid var(--border); background:var(--surface-2); display:flex; justify-content:flex-end; gap:6px; }

/* Champs */
.ais-field     { margin-bottom:16px; }
.ais-field:last-child { margin-bottom:0; }
.ais-lbl       { font-size:9px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:var(--text-3); margin-bottom:6px; display:flex; align-items:center; justify-content:space-between; }
.ais-lbl-sub   { font-size:9px; color:var(--text-3); font-weight:400; text-transform:none; letter-spacing:0; }
.ais-input-wr  { position:relative; }
.ais-input, .ais-select, .ais-textarea {
    width:100%; padding:9px 12px; border:1.5px solid var(--border);
    border-radius:var(--radius); font-size:11px; color:var(--text);
    background:var(--surface); transition:border .14s,box-shadow .14s;
    outline:none; font-family:var(--font); box-sizing:border-box;
}
.ais-input:focus, .ais-select:focus, .ais-textarea:focus {
    border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.1);
}
.ais-input.key  { padding-right:40px; font-family:var(--mono); font-size:11px; letter-spacing:.03em; }
.ais-textarea   { resize:vertical; line-height:1.7; min-height:150px; font-size:11px; }
.ais-eye        { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-3); font-size:12px; transition:color .13s; }
.ais-eye:hover  { color:#7c3aed; }

/* Statut cle */
.ais-key-status { display:inline-flex; align-items:center; gap:5px; font-size:9px; font-weight:700; padding:2px 8px; border-radius:4px; margin-top:5px; }
.ais-key-status.db      { background:var(--green-bg);  color:var(--green); }
.ais-key-status.cfg     { background:var(--amber-bg);  color:var(--amber); }
.ais-key-status.missing { background:var(--red-bg);    color:var(--red); }

/* Slider tokens */
.ais-slider-row { display:flex; align-items:center; gap:12px; }
.ais-slider     { flex:1; accent-color:#7c3aed; }
.ais-slider-val { font-size:16px; font-weight:900; color:#7c3aed; min-width:55px; text-align:right; }

/* Prompts */
.ais-prompt     { border:1px solid var(--border); border-radius:var(--radius-lg); margin-bottom:8px; overflow:hidden; transition:border-color .15s; }
.ais-prompt:hover { border-color:#c4b5fd; }
.ais-prompt.editing { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.07); }
.ais-prompt-hd  { display:flex; align-items:center; justify-content:space-between; padding:11px 14px; background:var(--surface-2); cursor:pointer; gap:10px; user-select:none; }
.ais-prompt-lbl { display:flex; align-items:center; gap:10px; }
.ais-prompt-ic  { width:30px; height:30px; border-radius:var(--radius); display:flex; align-items:center; justify-content:center; font-size:11px; color:#fff; flex-shrink:0; }
.ais-prompt-name{ font-size:12px; font-weight:700; color:var(--text); }
.ais-prompt-name small { display:block; font-size:9px; font-weight:400; color:var(--text-3); margin-top:1px; font-family:var(--mono); }
.ais-prompt-acts{ display:flex; align-items:center; gap:7px; flex-shrink:0; }
.ais-custom-tag { font-size:9px; font-weight:800; padding:2px 7px; border-radius:4px; background:#7c3aed; color:#fff; text-transform:uppercase; letter-spacing:.04em; }
.ais-default-tag{ font-size:9px; color:var(--text-3); font-weight:600; }
.ais-prompt-bd  { display:none; padding:14px; border-top:1px solid var(--border); }
.ais-prompt-bd.open { display:block; }
.ais-prompt-hint{ font-size:9px; color:var(--text-3); margin-top:6px; }
.ais-prompt-hint code { background:var(--surface-2); padding:1px 5px; border-radius:3px; font-size:9px; color:#7c3aed; font-family:var(--mono); }

/* Alertes */
.ais-alert { padding:10px 14px; border-radius:var(--radius); margin-bottom:14px; display:flex; align-items:center; gap:9px; font-size:11px; font-weight:600; }
.ais-alert.ok  { background:var(--green-bg); color:var(--green); border:1px solid var(--green); }
.ais-alert.err { background:var(--red-bg);   color:var(--red);   border:1px solid var(--red); }
.ais-info-box  { background:var(--accent-bg); border:1px solid var(--accent); border-radius:var(--radius); padding:10px 14px; margin-bottom:14px; font-size:11px; color:var(--accent-2); display:flex; gap:8px; }

/* Panel */
.ais-panel        { display:none; }
.ais-panel.active { display:block; }

@media(max-width:760px) { .ais-tabs { flex-wrap:wrap; } }
</style>

<!-- HEADER -->
<div class="page-hd">
    <div>
        <h1>Parametres IA</h1>
        <div class="page-hd-sub">
            Cles API, modeles et prompts systeme &middot;
            Provider actif : <?= $currentProvider ?: 'Non configure' ?>
        </div>
    </div>
    <a href="?page=settings" class="btn btn-s btn-sm">
        <i class="fas fa-arrow-left"></i> Configuration
    </a>
</div>

<!-- Banner + stat-cards -->
<div class="ais-banner anim">
    <div>
        <p class="ais-banner-title"><i class="fas fa-robot"></i> Intelligence Artificielle</p>
        <p class="ais-banner-sub">Cles API, modeles et prompts pour la generation de contenu immobilier</p>
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;position:relative;z-index:1">
        <div class="ais-provider-pill">
            <span class="dot<?= $currentProvider ? '' : ' off' ?>"></span>
            <?= $currentProvider ? htmlspecialchars($currentProvider) . ' actif' : 'Aucun provider' ?>
        </div>
    </div>
</div>

<div class="syshub-scores anim" style="margin-bottom:16px">
    <div class="stat-card">
        <div class="stat-icon" style="background:<?= $activeAnthropic ? 'var(--green-bg)' : 'var(--red-bg)' ?>;color:<?= $activeAnthropic ? 'var(--green)' : 'var(--red)' ?>">
            <i class="fas fa-brain"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val" style="font-size:12px"><?= $activeAnthropic ? 'Actif' : 'Non config.' ?></div>
            <div class="stat-label">Anthropic Claude</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:<?= $activeOpenai ? 'var(--green-bg)' : 'var(--surface-3)' ?>;color:<?= $activeOpenai ? 'var(--green)' : 'var(--text-3)' ?>">
            <i class="fas fa-robot"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val" style="font-size:12px"><?= $activeOpenai ? 'Actif' : 'Non config.' ?></div>
            <div class="stat-label">OpenAI GPT</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--accent-bg);color:var(--accent)">
            <i class="fas fa-microchip"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val" style="font-size:11px"><?= htmlspecialchars($activeAnthropic ? $modelAnthropic : $modelOpenai) ?></div>
            <div class="stat-label">Modele actif</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--amber-bg);color:var(--amber)">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val"><?= $maxTokens ?></div>
            <div class="stat-label">Tokens max</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#faf5ff;color:#7c3aed">
            <i class="fas fa-terminal"></i>
        </div>
        <div class="stat-info">
            <div class="stat-val"><?= $customPrompts ?>/<?= count($defaultPrompts) ?></div>
            <div class="stat-label">Prompts perso</div>
        </div>
    </div>
</div>

<!-- Alertes -->
<?php if ($saveMsg): ?>
<div class="ais-alert ok anim"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($saveMsg) ?></div>
<?php endif; ?>
<?php if ($saveErr): ?>
<div class="ais-alert err anim"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($saveErr) ?></div>
<?php endif; ?>

<!-- Onglets -->
<div class="ais-tabs anim">
    <button class="ais-tab <?= $activeTab !== 'prompts' ? 'active' : '' ?>" onclick="aisTab('modeles')">
        <i class="fas fa-microchip"></i> Modeles & Parametres
    </button>
    <button class="ais-tab <?= $activeTab === 'prompts' ? 'active' : '' ?>" onclick="aisTab('prompts')">
        <i class="fas fa-terminal"></i> Prompts systeme
        <span class="cnt"><?= count($defaultPrompts) ?></span>
    </button>
</div>

<!-- ======== PANEL : Modeles & Parametres ======== -->
<div class="ais-panel <?= $activeTab !== 'prompts' ? 'active' : '' ?>" id="ais-panel-modeles">

    <!-- Encart redirection clés API -->
    <?php if (!$activeAnthropic && !$activeOpenai): ?>
    <div class="ais-alert err anim">
        <i class="fas fa-triangle-exclamation"></i>
        <span>Aucune cle API configuree. Rendez-vous dans
            <a href="?page=settings&tab=api" style="color:var(--red);font-weight:700">
                Parametres → API & Integrations
            </a> pour ajouter vos cles Anthropic ou OpenAI.
        </span>
    </div>
    <?php else: ?>
    <div class="ais-info-box anim" style="background:var(--green-bg);border-color:rgba(5,150,105,.15)">
        <i class="fas fa-check-circle" style="color:var(--green)"></i>
        <span style="color:var(--green)">
            Provider actif : <strong><?= htmlspecialchars($currentProvider) ?></strong>
            · Cles gerees dans
            <a href="?page=settings&tab=api" style="color:var(--green);font-weight:700">
                Parametres → API & Integrations
            </a>
        </span>
    </div>
    <?php endif; ?>

    <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <input type="hidden" name="ai_action"  value="save_keys">

    <!-- Anthropic modele uniquement -->
    <div class="ais-card anim">
        <div class="ais-card-hd">
            <div class="ais-card-title">
                <i class="fas fa-brain" style="color:#7c3aed"></i>
                Anthropic — Modele
                <?php if ($activeAnthropic): ?>
                <span class="syshub-badge" style="background:var(--green-bg);color:var(--green)">Cle active</span>
                <?php else: ?>
                <span class="syshub-badge" style="background:var(--red-bg);color:var(--red)">Cle manquante</span>
                <?php endif; ?>
            </div>
            <?php if ($activeAnthropic): ?>
            <form method="POST" style="margin:0">
                <input type="hidden" name="csrf_token"    value="<?= $csrf ?>">
                <input type="hidden" name="ai_action"     value="test_key">
                <input type="hidden" name="test_provider" value="anthropic">
                <button type="submit" class="btn btn-s btn-sm" style="border-color:var(--green);color:var(--green)">
                    <i class="fas fa-plug"></i> Tester la connexion
                </button>
            </form>
            <?php endif; ?>
        </div>
        <div class="ais-card-body">
            <div class="ais-field">
                <div class="ais-lbl"><i class="fas fa-microchip"></i> Modele actif</div>
                <select name="ai_model_anthropic" class="ais-select">
                    <?php foreach ([
                        'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 — recommande (equilibre)',
                        'claude-opus-4-6'           => 'Claude Opus 4.6 — plus puissant (lent/couteux)',
                        'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5 — rapide et economique',
                    ] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $modelAnthropic === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- OpenAI modele uniquement -->
    <div class="ais-card anim">
        <div class="ais-card-hd">
            <div class="ais-card-title">
                <i class="fas fa-robot" style="color:#10b981"></i>
                OpenAI — Modele
                <?php if ($activeOpenai): ?>
                <span class="syshub-badge" style="background:var(--green-bg);color:var(--green)">Cle active</span>
                <?php else: ?>
                <span class="syshub-badge" style="background:var(--surface-3);color:var(--text-3)">Optionnel</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="ais-card-body">
            <div class="ais-info-box" style="margin-bottom:12px">
                <i class="fas fa-info-circle"></i>
                <span>OpenAI est utilise pour DALL-E (images) et en fallback si Anthropic n'est pas configure.</span>
            </div>
            <div class="ais-field">
                <div class="ais-lbl"><i class="fas fa-microchip"></i> Modele actif</div>
                <select name="ai_model_openai" class="ais-select">
                    <?php foreach ([
                        'gpt-4o-mini'   => 'GPT-4o mini — recommande (rapide/eco)',
                        'gpt-4o'        => 'GPT-4o — plus puissant',
                        'gpt-3.5-turbo' => 'GPT-3.5 Turbo — tres economique',
                    ] as $v => $l): ?>
                    <option value="<?= $v ?>" <?= $modelOpenai === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Parametres globaux -->
    <div class="ais-card anim">
        <div class="ais-card-hd">
            <div class="ais-card-title"><i class="fas fa-sliders"></i> Parametres globaux</div>
        </div>
        <div class="ais-card-body">
            <div class="ais-field">
                <div class="ais-lbl">
                    <span><i class="fas fa-coins"></i> Tokens max par requete</span>
                    <span class="ais-lbl-sub">Longueur des reponses et cout</span>
                </div>
                <div class="ais-slider-row">
                    <input type="range" name="ai_max_tokens" id="ais-slider"
                           class="ais-slider" min="500" max="6000" step="100"
                           value="<?= $maxTokens ?>"
                           oninput="document.getElementById('ais-slval').textContent=this.value">
                    <span class="ais-slider-val" id="ais-slval"><?= $maxTokens ?></span>
                </div>
                <div class="ais-prompt-hint" style="margin-top:8px">
                    Recommande : <code>2000-3000</code> articles &middot; <code>400-800</code> metas &middot; <code>4000-5000</code> articles longs
                </div>
            </div>
        </div>
        <div class="ais-card-ft">
            <button type="submit" class="btn btn-p btn-sm">
                <i class="fas fa-save"></i> Enregistrer tout
            </button>
        </div>
    </div>
    </form>
</div>

<!-- ======== PANEL : Prompts ======== -->
<div class="ais-panel <?= $activeTab === 'prompts' ? 'active' : '' ?>" id="ais-panel-prompts">

    <div class="ais-info-box anim">
        <i class="fas fa-info-circle"></i>
        <span>Personnalisez les prompts pour votre positionnement. Terminez toujours par <code>Tu reponds UNIQUEMENT en JSON valide.</code></span>
    </div>

    <?php foreach ($defaultPrompts as $key => $defaultVal):
        $meta       = $promptMeta[$key];
        $customVal  = aiGet($db, 'prompt_' . $key);
        $isCustom   = !empty($customVal);
        $displayVal = $isCustom ? $customVal : $defaultVal;
    ?>
    <div class="ais-prompt anim" id="ais-pi-<?= $key ?>">
        <div class="ais-prompt-hd" onclick="aisPr('<?= $key ?>')">
            <div class="ais-prompt-lbl">
                <div class="ais-prompt-ic" style="background:<?= $meta['color'] ?>">
                    <i class="fas <?= $meta['icon'] ?>"></i>
                </div>
                <div class="ais-prompt-name">
                    <?= htmlspecialchars($meta['label']) ?>
                    <small><?= $key ?></small>
                </div>
            </div>
            <div class="ais-prompt-acts">
                <?php if ($isCustom): ?>
                <span class="ais-custom-tag">Perso</span>
                <?php else: ?>
                <span class="ais-default-tag">Defaut</span>
                <?php endif; ?>
                <i class="fas fa-chevron-right" id="ais-ch-<?= $key ?>" style="color:var(--text-3);font-size:10px;transition:transform .2s"></i>
            </div>
        </div>
        <div class="ais-prompt-bd" id="ais-pb-<?= $key ?>">
            <form method="POST" id="ais-pf-<?= $key ?>">
                <input type="hidden" name="csrf_token"   value="<?= $csrf ?>">
                <input type="hidden" name="ai_action"    value="save_prompt">
                <input type="hidden" name="prompt_key"   value="<?= $key ?>">
                <div class="ais-field">
                    <div class="ais-lbl">
                        <span><i class="fas fa-terminal"></i> Prompt systeme</span>
                        <span class="ais-lbl-sub" id="ais-cc-<?= $key ?>"><?= mb_strlen($displayVal) ?> car.</span>
                    </div>
                    <textarea name="prompt_value" class="ais-textarea"
                        oninput="document.getElementById('ais-cc-<?= $key ?>').textContent=this.value.length+' car.'"
                    ><?= htmlspecialchars($displayVal) ?></textarea>
                    <div class="ais-prompt-hint">
                        Toujours terminer par : <code>Tu reponds UNIQUEMENT en JSON valide.</code>
                    </div>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;flex-wrap:wrap;gap:8px">
                    <?php if ($isCustom): ?>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="csrf_token"  value="<?= $csrf ?>">
                        <input type="hidden" name="ai_action"   value="reset_prompt">
                        <input type="hidden" name="prompt_key"  value="<?= $key ?>">
                        <button type="submit" class="btn btn-s btn-sm"
                                style="border-color:var(--red);color:var(--red)"
                                onclick="return confirm('Remettre le prompt par defaut ?')">
                            <i class="fas fa-undo"></i> Reinitialiser
                        </button>
                    </form>
                    <?php else: ?>
                    <span style="font-size:10px;color:var(--text-3);font-style:italic">Prompt par defaut — non modifie</span>
                    <?php endif; ?>
                    <button type="submit" form="ais-pf-<?= $key ?>" class="btn btn-p btn-sm">
                        <i class="fas fa-save"></i> Sauvegarder
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<script>
function aisTab(tab) {
    document.querySelectorAll('.ais-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.ais-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('ais-panel-' + tab).classList.add('active');
    document.querySelectorAll('.ais-tab').forEach(b => {
        if (b.getAttribute('onclick')?.includes("'" + tab + "'")) b.classList.add('active');
    });
    history.replaceState(null, '', '?page=ai-settings&tab=' + tab);
}

function aisEye(id, btn) {
    const inp = document.getElementById(id);
    inp.type  = inp.type === 'password' ? 'text' : 'password';
    btn.innerHTML = inp.type === 'password'
        ? '<i class="fas fa-eye"></i>'
        : '<i class="fas fa-eye-slash"></i>';
}

function aisPr(key) {
    const bd  = document.getElementById('ais-pb-' + key);
    const ch  = document.getElementById('ais-ch-' + key);
    const it  = document.getElementById('ais-pi-' + key);
    const was = bd.classList.contains('open');
    document.querySelectorAll('.ais-prompt-bd').forEach(b => b.classList.remove('open'));
    document.querySelectorAll('[id^="ais-ch-"]').forEach(c => c.style.transform = '');
    document.querySelectorAll('.ais-prompt').forEach(i => i.classList.remove('editing'));
    if (!was) { bd.classList.add('open'); ch.style.transform = 'rotate(90deg)'; it.classList.add('editing'); }
}

// Ouvrir prompt depuis URL ?prompt=generate
const _up = new URLSearchParams(location.search);
if (_up.get('prompt')) aisPr(_up.get('prompt'));
</script>