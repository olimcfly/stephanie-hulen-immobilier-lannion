<?php
/**
 * /admin/modules/pages/edit.php  v5.1
 * ============================================================
 *  Editeur CMS Pages — calque identique articles/edit.php v5.1
 *  ✅ Layout 2 colonnes : sections accordéon | sidebar droite
 *  ✅ Champs : text / textarea / rich (Quill) / url / image
 *  ✅ Score remplissage live + ring SVG
 *  ✅ SERP preview live (meta title/desc)
 *  ✅ Barres de progression par section (mini-barres sidebar)
 *  ✅ Sauvegarde AJAX JSON dans colonne `fields`
 *  ✅ Ctrl+S + dirty bar + toast
 *  ✅ CSRF token, double confirmation suppression
 *  ✅ Chargé via require depuis index.php ($pdo hérité)
 * ============================================================
 */

if (!isset($pdo)) {
    foreach ([__DIR__.'/../../../config/config.php', $_SERVER['DOCUMENT_ROOT'].'/config/config.php'] as $p) {
        if (file_exists($p)) { require_once $p; break; }
    }
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    } catch (Exception $e) {
        echo '<div style="background:#fee2e2;color:#991b1b;padding:16px;border-radius:8px;margin:20px">DB: '.htmlspecialchars($e->getMessage()).'</div>';
        return;
    }
}

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

$pageId = (int)($_GET['id'] ?? 0);
if (!$pageId) { header('Location: ?page=pages'); exit; }

$page = null;
try { $s = $pdo->prepare("SELECT * FROM pages WHERE id=? LIMIT 1"); $s->execute([$pageId]); $page = $s->fetch(); } catch (Throwable $e) {}

if (!$page) {
    echo '<div style="padding:60px;text-align:center;font-family:Inter,sans-serif">
        <div style="font-size:3rem;margin-bottom:16px">&#128270;</div>
        <h3 style="color:#1e293b">Page introuvable (ID '.$pageId.')</h3>
        <a href="?page=pages" style="color:#6366f1;font-weight:600;text-decoration:none">&#8592; Retour</a></div>';
    return;
}

$title    = $page['title']    ?? $page['titre']  ?? 'Sans titre';
$slug     = $page['slug']     ?? '';
$status   = $page['status']   ?? $page['statut'] ?? 'draft';
$template = $page['template'] ?? 'standard';
$isPub    = in_array($status, ['published','publie','active']);
$base_url = defined('SITE_URL') ? rtrim(SITE_URL,'/') : '';
$viewUrl  = $slug ? $base_url.'/'.ltrim($slug,'/') : '';
$fields_data = json_decode($page['fields'] ?? '{}', true) ?: [];
$metaTitle   = $fields_data['seo_title']       ?? $page['meta_title']       ?? $page['seo_title']       ?? '';
$metaDesc    = $fields_data['seo_description'] ?? $page['meta_description'] ?? $page['seo_description'] ?? '';
$aiAvail     = defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY);
$message = ['saved'=>'&#10003; Modifications enregistrees.','published'=>'&#10003; Page publiee.'][$_GET['msg'] ?? ''] ?? '';

// ══════════════════════════════════════════════════════
//  SECTIONS PAR TEMPLATE
// ══════════════════════════════════════════════════════
$TPL = [

    't1-accueil' => [
        ['section'=>'Hero',                   'icon'=>'fa-star',                'color'=>'#6366f1', 'fields'=>[
            ['key'=>'hero_badge',    'label'=>'Badge / eyebrow',         'type'=>'text',     'hint'=>'Ex : Bordeaux &middot; Conseiller eXp France'],
            ['key'=>'hero_title',    'label'=>'Titre H1',                 'type'=>'text',     'hint'=>'Votre promesse principale'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre / accroche',    'type'=>'textarea'],
            ['key'=>'hero_cta_text', 'label'=>'CTA &mdash; texte bouton', 'type'=>'text'],
            ['key'=>'hero_cta_link', 'label'=>'CTA &mdash; lien',         'type'=>'url'],
        ]],
        ['section'=>'Benefices (3 colonnes)', 'icon'=>'fa-check-double',        'color'=>'#10b981', 'fields'=>[
            ['key'=>'benefits_section_title', 'label'=>'Titre section',          'type'=>'text',    'hint'=>'Ex : Pourquoi travailler avec moi'],
            ['key'=>'benefit_1_icon',  'label'=>'Benefice 1 &mdash; icone',      'type'=>'text',    'hint'=>'chart / megaphone / handshake'],
            ['key'=>'benefit_1_title', 'label'=>'Benefice 1 &mdash; titre',      'type'=>'text'],
            ['key'=>'benefit_1_text',  'label'=>'Benefice 1 &mdash; texte',      'type'=>'textarea'],
            ['key'=>'benefit_2_icon',  'label'=>'Benefice 2 &mdash; icone',      'type'=>'text'],
            ['key'=>'benefit_2_title', 'label'=>'Benefice 2 &mdash; titre',      'type'=>'text'],
            ['key'=>'benefit_2_text',  'label'=>'Benefice 2 &mdash; texte',      'type'=>'textarea'],
            ['key'=>'benefit_3_icon',  'label'=>'Benefice 3 &mdash; icone',      'type'=>'text'],
            ['key'=>'benefit_3_title', 'label'=>'Benefice 3 &mdash; titre',      'type'=>'text'],
            ['key'=>'benefit_3_text',  'label'=>'Benefice 3 &mdash; texte',      'type'=>'textarea'],
        ]],
        ['section'=>'Presentation conseiller','icon'=>'fa-user-tie',            'color'=>'#0d9488', 'fields'=>[
            ['key'=>'about_badge','label'=>'Badge',                              'type'=>'text',    'hint'=>'Ex : Votre allie'],
            ['key'=>'about_title','label'=>'Titre section',                      'type'=>'text'],
            ['key'=>'about_text', 'label'=>'Texte de presentation',              'type'=>'rich'],
            ['key'=>'about_photo','label'=>'Photo (URL ou chemin)',               'type'=>'image'],
        ]],
        ['section'=>'Expertise (3 piliers)',  'icon'=>'fa-trophy',               'color'=>'#f59e0b', 'fields'=>[
            ['key'=>'expertise_1_title','label'=>'Pilier 1 &mdash; titre',       'type'=>'text'],
            ['key'=>'expertise_1_text', 'label'=>'Pilier 1 &mdash; texte',       'type'=>'textarea'],
            ['key'=>'expertise_2_title','label'=>'Pilier 2 &mdash; titre',       'type'=>'text'],
            ['key'=>'expertise_2_text', 'label'=>'Pilier 2 &mdash; texte',       'type'=>'textarea'],
            ['key'=>'expertise_3_title','label'=>'Pilier 3 &mdash; titre',       'type'=>'text'],
            ['key'=>'expertise_3_text', 'label'=>'Pilier 3 &mdash; texte',       'type'=>'textarea'],
        ]],
        ['section'=>'Methode en 3 etapes',   'icon'=>'fa-list-ol',              'color'=>'#8b5cf6', 'fields'=>[
            ['key'=>'method_title',       'label'=>'Titre section',              'type'=>'text'],
            ['key'=>'method_subtitle',    'label'=>'Sous-titre',                 'type'=>'text'],
            ['key'=>'method_step1_title', 'label'=>'Etape 1 &mdash; titre',      'type'=>'text'],
            ['key'=>'method_step1_text',  'label'=>'Etape 1 &mdash; texte',      'type'=>'textarea'],
            ['key'=>'method_step2_title', 'label'=>'Etape 2 &mdash; titre',      'type'=>'text'],
            ['key'=>'method_step2_text',  'label'=>'Etape 2 &mdash; texte',      'type'=>'textarea'],
            ['key'=>'method_step3_title', 'label'=>'Etape 3 &mdash; titre',      'type'=>'text'],
            ['key'=>'method_step3_text',  'label'=>'Etape 3 &mdash; texte',      'type'=>'textarea'],
            ['key'=>'method_result_text', 'label'=>'Resultat (texte encadre)',   'type'=>'text'],
            ['key'=>'method_cta_text',    'label'=>'CTA &mdash; texte',          'type'=>'text'],
            ['key'=>'method_cta_link',    'label'=>'CTA &mdash; lien',           'type'=>'url'],
        ]],
        ['section'=>'Guide vendeur (SEO)',    'icon'=>'fa-book-open',            'color'=>'#0ea5e9', 'fields'=>[
            ['key'=>'guide_title',   'label'=>'Titre du guide',                 'type'=>'text'],
            ['key'=>'guide_1_title', 'label'=>'Question 1 &mdash; titre',        'type'=>'text'],
            ['key'=>'guide_1_text',  'label'=>'Question 1 &mdash; reponse',      'type'=>'rich'],
            ['key'=>'guide_2_title', 'label'=>'Question 2 &mdash; titre',        'type'=>'text'],
            ['key'=>'guide_2_text',  'label'=>'Question 2 &mdash; reponse',      'type'=>'rich'],
            ['key'=>'guide_3_title', 'label'=>'Question 3 &mdash; titre',        'type'=>'text'],
            ['key'=>'guide_3_text',  'label'=>'Question 3 &mdash; reponse',      'type'=>'rich'],
        ]],
        ['section'=>'CTA Finale',            'icon'=>'fa-rocket',               'color'=>'#10b981', 'fields'=>[
            ['key'=>'final_cta_title',  'label'=>'Titre',                        'type'=>'text'],
            ['key'=>'final_cta_text',   'label'=>'Description',                  'type'=>'textarea'],
            ['key'=>'final_cta_button', 'label'=>'Texte bouton',                 'type'=>'text'],
            ['key'=>'final_cta_small',  'label'=>'Mention rassurance',           'type'=>'text',    'hint'=>'Ex : Reponse sous 24h &middot; 100% gratuit'],
        ]],
    ],

    't2-edito' => [
        ['section'=>'Hero',                  'icon'=>'fa-star',                 'color'=>'#6366f1', 'fields'=>[
            ['key'=>'hero_eyebrow', 'label'=>'Eyebrow (au-dessus du titre)',     'type'=>'text',    'hint'=>'Ex : Conseiller Bordeaux &mdash; eXp France'],
            ['key'=>'hero_title',   'label'=>'Titre H1',                         'type'=>'text'],
            ['key'=>'hero_subtitle','label'=>'Sous-titre',                        'type'=>'textarea'],
            ['key'=>'hero_cta_text','label'=>'CTA &mdash; texte bouton',          'type'=>'text'],
            ['key'=>'hero_cta_url', 'label'=>'CTA &mdash; lien',                 'type'=>'url'],
        ]],
        ['section'=>'3 Arguments (boxes)',   'icon'=>'fa-table-cells',          'color'=>'#0d9488', 'fields'=>[
            ['key'=>'box1_icon',  'label'=>'Box 1 &mdash; icone / emoji',        'type'=>'text',    'hint'=>'Ex : &#x2728; ou shield'],
            ['key'=>'box1_title', 'label'=>'Box 1 &mdash; titre',                'type'=>'text'],
            ['key'=>'box1_text',  'label'=>'Box 1 &mdash; texte court',          'type'=>'text'],
            ['key'=>'box2_icon',  'label'=>'Box 2 &mdash; icone / emoji',        'type'=>'text'],
            ['key'=>'box2_title', 'label'=>'Box 2 &mdash; titre',                'type'=>'text'],
            ['key'=>'box2_text',  'label'=>'Box 2 &mdash; texte court',          'type'=>'text'],
            ['key'=>'box3_icon',  'label'=>'Box 3 &mdash; icone / emoji',        'type'=>'text'],
            ['key'=>'box3_title', 'label'=>'Box 3 &mdash; titre',                'type'=>'text'],
            ['key'=>'box3_text',  'label'=>'Box 3 &mdash; texte court',          'type'=>'text'],
        ]],
        ['section'=>'Problemes / Douleurs',  'icon'=>'fa-exclamation-triangle', 'color'=>'#ef4444', 'fields'=>[
            ['key'=>'pain_title',   'label'=>'Titre section',                   'type'=>'text'],
            ['key'=>'pain_subtitle','label'=>'Sous-titre',                       'type'=>'textarea'],
            ['key'=>'pain_1',       'label'=>'Probleme 1',                       'type'=>'text'],
            ['key'=>'pain_2',       'label'=>'Probleme 2',                       'type'=>'text'],
            ['key'=>'pain_3',       'label'=>'Probleme 3',                       'type'=>'text'],
            ['key'=>'pain_4',       'label'=>'Probleme 4',                       'type'=>'text'],
        ]],
        ['section'=>'Autorite / Chiffres',   'icon'=>'fa-certificate',          'color'=>'#f59e0b', 'fields'=>[
            ['key'=>'authority_badge', 'label'=>'Badge',                         'type'=>'text'],
            ['key'=>'authority_title', 'label'=>'Titre',                         'type'=>'text'],
            ['key'=>'authority_text',  'label'=>'Presentation',                  'type'=>'rich'],
            ['key'=>'stat_1_number',   'label'=>'Stat 1 &mdash; chiffre',        'type'=>'text',    'hint'=>'Ex : 150+'],
            ['key'=>'stat_1_label',    'label'=>'Stat 1 &mdash; libelle',        'type'=>'text'],
            ['key'=>'stat_2_number',   'label'=>'Stat 2 &mdash; chiffre',        'type'=>'text'],
            ['key'=>'stat_2_label',    'label'=>'Stat 2 &mdash; libelle',        'type'=>'text'],
            ['key'=>'stat_3_number',   'label'=>'Stat 3 &mdash; chiffre',        'type'=>'text'],
            ['key'=>'stat_3_label',    'label'=>'Stat 3 &mdash; libelle',        'type'=>'text'],
        ]],
        ['section'=>'Methode / Process',     'icon'=>'fa-list-ol',              'color'=>'#8b5cf6', 'fields'=>[
            ['key'=>'method_title',    'label'=>'Titre section',                 'type'=>'text'],
            ['key'=>'method_subtitle', 'label'=>'Sous-titre',                   'type'=>'text'],
            ['key'=>'step1_title',     'label'=>'Etape 1 &mdash; titre',         'type'=>'text'],
            ['key'=>'step1_text',      'label'=>'Etape 1 &mdash; texte',         'type'=>'textarea'],
            ['key'=>'step2_title',     'label'=>'Etape 2 &mdash; titre',         'type'=>'text'],
            ['key'=>'step2_text',      'label'=>'Etape 2 &mdash; texte',         'type'=>'textarea'],
            ['key'=>'step3_title',     'label'=>'Etape 3 &mdash; titre',         'type'=>'text'],
            ['key'=>'step3_text',      'label'=>'Etape 3 &mdash; texte',         'type'=>'textarea'],
            ['key'=>'method_cta_text', 'label'=>'CTA &mdash; texte',             'type'=>'text'],
            ['key'=>'method_cta_url',  'label'=>'CTA &mdash; lien',             'type'=>'url'],
        ]],
        ['section'=>'Guide SEO (FAQ)',        'icon'=>'fa-book-open',            'color'=>'#0ea5e9', 'fields'=>[
            ['key'=>'guide_intro',   'label'=>'Titre du guide',                 'type'=>'text'],
            ['key'=>'guide_1_title', 'label'=>'Article 1 &mdash; titre',         'type'=>'text'],
            ['key'=>'guide_1_text',  'label'=>'Article 1 &mdash; contenu',       'type'=>'rich'],
            ['key'=>'guide_2_title', 'label'=>'Article 2 &mdash; titre',         'type'=>'text'],
            ['key'=>'guide_2_text',  'label'=>'Article 2 &mdash; contenu',       'type'=>'rich'],
            ['key'=>'guide_3_title', 'label'=>'Article 3 &mdash; titre',         'type'=>'text'],
            ['key'=>'guide_3_text',  'label'=>'Article 3 &mdash; contenu',       'type'=>'rich'],
        ]],
        ['section'=>'CTA Finale',            'icon'=>'fa-rocket',               'color'=>'#10b981', 'fields'=>[
            ['key'=>'final_title',  'label'=>'Titre',                            'type'=>'text'],
            ['key'=>'final_text',   'label'=>'Texte',                            'type'=>'textarea'],
            ['key'=>'final_button', 'label'=>'Texte du bouton',                  'type'=>'text'],
            ['key'=>'final_small',  'label'=>'Mention rassurance',               'type'=>'text'],
        ]],
    ],

    't3-secteur' => [
        ['section'=>'Introduction secteur',  'icon'=>'fa-map-pin',              'color'=>'#0d9488', 'fields'=>[
            ['key'=>'hero_title',    'label'=>'Nom du secteur (H1)',             'type'=>'text'],
            ['key'=>'hero_subtitle', 'label'=>'Description courte',              'type'=>'textarea'],
            ['key'=>'hero_image',    'label'=>'Image principale',                'type'=>'image'],
        ]],
        ['section'=>'Marche immobilier',     'icon'=>'fa-chart-line',           'color'=>'#6366f1', 'fields'=>[
            ['key'=>'prix_moyen',  'label'=>'Prix moyen au m2',                 'type'=>'text',    'hint'=>'Ex : 3 800 EUR/m2'],
            ['key'=>'prix_maison', 'label'=>'Prix moyen maison',                 'type'=>'text'],
            ['key'=>'prix_appart', 'label'=>'Prix moyen appartement',            'type'=>'text'],
            ['key'=>'evolution',   'label'=>'Evolution sur 1 an',               'type'=>'text',    'hint'=>'Ex : +4,2%'],
        ]],
        ['section'=>'Contenu principal',     'icon'=>'fa-pen-nib',              'color'=>'#8b5cf6', 'fields'=>[
            ['key'=>'body_intro',   'label'=>'Introduction',                     'type'=>'rich'],
            ['key'=>'body_content', 'label'=>'Corps de texte',                   'type'=>'rich'],
            ['key'=>'atout_1',      'label'=>'Point fort 1',                    'type'=>'text'],
            ['key'=>'atout_2',      'label'=>'Point fort 2',                    'type'=>'text'],
            ['key'=>'atout_3',      'label'=>'Point fort 3',                    'type'=>'text'],
        ]],
        ['section'=>'CTA',                   'icon'=>'fa-rocket',               'color'=>'#f59e0b', 'fields'=>[
            ['key'=>'cta_text', 'label'=>'Texte du CTA',                        'type'=>'text'],
            ['key'=>'cta_url',  'label'=>'Lien',                                'type'=>'url'],
        ]],
    ],

    'standard' => [
        ['section'=>'Entete de page',        'icon'=>'fa-heading',              'color'=>'#6366f1', 'fields'=>[
            ['key'=>'page_title',    'label'=>'Titre (H1)',                      'type'=>'text'],
            ['key'=>'page_subtitle', 'label'=>'Sous-titre',                      'type'=>'textarea'],
        ]],
        ['section'=>'Contenu',               'icon'=>'fa-pen-nib',              'color'=>'#8b5cf6', 'fields'=>[
            ['key'=>'body_content', 'label'=>'Corps de texte',                   'type'=>'rich'],
        ]],
        ['section'=>'CTA',                   'icon'=>'fa-rocket',               'color'=>'#10b981', 'fields'=>[
            ['key'=>'cta_title',  'label'=>'Titre CTA',                         'type'=>'text'],
            ['key'=>'cta_text',   'label'=>'Texte CTA',                         'type'=>'textarea'],
            ['key'=>'cta_button', 'label'=>'Texte du bouton',                   'type'=>'text'],
            ['key'=>'cta_url',    'label'=>'Lien',                              'type'=>'url'],
        ]],
    ],
];

$TPL['secteur'] = $TPL['t3-secteur'];
foreach (['default','page','Legal','Landing','t14-apropos'] as $k) $TPL[$k] = $TPL['standard'];

$tk = $template;
if (!isset($TPL[$tk])) {
    if (in_array($slug, ['vendre','acheter','financer','investir'])) $tk = 't2-edito';
    elseif ($slug === 'accueil' || !empty($page['is_home']))         $tk = 't1-accueil';
    elseif (str_starts_with($slug, 'secteurs/'))                     $tk = 't3-secteur';
    else                                                              $tk = 'standard';
}
$sections = $TPL[$tk];

$totalF  = 0; $filledF = 0;
foreach ($sections as $sec) {
    foreach ($sec['fields'] as $f) {
        $totalF++;
        if (!empty(trim(strip_tags((string)($fields_data[$f['key']] ?? ''))))) $filledF++;
    }
}
$fillPct = $totalF > 0 ? round($filledF / $totalF * 100) : 0;

$tplLabels = ['t1-accueil'=>'Accueil','t2-edito'=>'Edito','t3-secteur'=>'Secteur','secteur'=>'Secteur','standard'=>'Standard','Landing'=>'Landing','Legal'=>'Legal','default'=>'Standard','page'=>'Page','t14-apropos'=>'A propos'];
$tplColors = ['t1-accueil'=>'#6366f1','t2-edito'=>'#8b5cf6','t3-secteur'=>'#0d9488','secteur'=>'#0d9488','standard'=>'#6366f1','Landing'=>'#ef4444','Legal'=>'#64748b','default'=>'#94a3b8','page'=>'#94a3b8','t14-apropos'=>'#3b82f6'];
$tplCol   = $tplColors[$tk] ?? '#6366f1';
$tplLabel = $tplLabels[$tk] ?? $tk;
?>
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
/* ═══════════════════════════════════════════════════════════
   PAGE EDITOR v5.1 — calque identique articles/edit.php v5.1
═══════════════════════════════════════════════════════════ */
:root {
    --pe-primary:#6366f1; --pe-primary-d:#4f46e5;
    --pe-success:#10b981; --pe-warning:#f59e0b;
    --pe-danger:#ef4444;  --pe-ai:#8b5cf6;
    --bg-card:#ffffff; --bg-page:#f8fafc;
    --bdr:#e2e8f0; --t1:#0f172a; --t2:#374151; --t3:#94a3b8;
    --r:14px; --r-sm:10px;
    --sh:0 1px 3px rgba(0,0,0,.07); --sh-md:0 4px 16px rgba(0,0,0,.09); --sh-lg:0 10px 40px rgba(0,0,0,.14);
}
.pe5 { font-family:'Inter',-apple-system,sans-serif; color:var(--t1); }

/* ─── Header ─── */
.pe5-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.pe5-header h2 { font-size:20px; font-weight:700; display:flex; align-items:center; gap:10px; margin:0; }
.pe5-header h2 .id-tag { font-size:13px; color:var(--t3); font-weight:400; }
.pe5-btns { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
.pe5-btn { display:inline-flex; align-items:center; gap:7px; padding:9px 18px; border:none; border-radius:var(--r-sm); font-size:13px; font-weight:600; cursor:pointer; transition:all .2s; text-decoration:none; white-space:nowrap; font-family:inherit; }
.pe5-btn-ghost   { background:var(--bg-card); color:#64748b; border:1px solid var(--bdr); }
.pe5-btn-ghost:hover   { border-color:var(--pe-primary); color:var(--pe-primary); }
.pe5-btn-view    { background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; }
.pe5-btn-view:hover    { background:#0ea5e9; color:#fff; border-color:#0ea5e9; }
.pe5-btn-draft   { background:#fef3c7; color:#92400e; border:1px solid #fde68a; }
.pe5-btn-draft:hover   { background:#f59e0b; color:#fff; border-color:#f59e0b; }
.pe5-btn-publish { background:var(--pe-success); color:#fff; }
.pe5-btn-publish:hover { background:#059669; box-shadow:0 4px 12px rgba(16,185,129,.35); }

/* ─── Messages ─── */
.pe5-msg { padding:13px 18px; border-radius:var(--r-sm); margin-bottom:20px; font-size:14px; font-weight:500; display:flex; align-items:center; gap:10px; }
.pe5-msg.ok  { background:#d1fae5; color:#065f46; border:1px solid #a7f3d0; }
.pe5-msg.err { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }

/* ─── Grid 2 colonnes ─── */
.pe5-grid { display:grid; grid-template-columns:1fr 360px; gap:24px; align-items:start; }
@media(max-width:1180px){ .pe5-grid { grid-template-columns:1fr; } }

/* ─── Cards génériques ─── */
.pe5-card { background:var(--bg-card); border:1px solid var(--bdr); border-radius:var(--r); box-shadow:var(--sh); overflow:hidden; margin-bottom:20px; }
.pe5-card-header { padding:14px 20px; border-bottom:1px solid var(--bdr); background:#fafbfc; display:flex; align-items:center; justify-content:space-between; }
.pe5-card-title { font-size:13px; font-weight:700; color:var(--t1); display:flex; align-items:center; gap:8px; text-transform:uppercase; letter-spacing:.04em; }
.pe5-card-title i { color:var(--pe-primary); }
.pe5-card-body { padding:20px; }

/* Banner page ─── */
.pe5-page-banner { position:relative; overflow:hidden; }
.pe5-page-banner::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:linear-gradient(90deg,<?php echo $tplCol ?>,#8b5cf6); }
.pe5-pb-row { display:flex; align-items:center; gap:14px; margin-bottom:12px; }
.pe5-pb-ico { width:40px; height:40px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:1rem; flex-shrink:0; }
.pe5-pb-title { font-size:1.05rem; font-weight:800; color:var(--t1); margin-bottom:3px; }
.pe5-pb-slug  { font-family:monospace; font-size:.72rem; color:var(--t3); }
.pe5-pb-badges { display:flex; gap:7px; flex-wrap:wrap; }
.pe5-pb-badge  { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:8px; font-size:.68rem; font-weight:700; }
.pe5-pb-prog   { display:flex; align-items:center; gap:10px; margin-top:12px; }
.pe5-pb-prog-bar  { flex:1; height:5px; background:var(--bdr); border-radius:3px; overflow:hidden; }
.pe5-pb-prog-fill { height:100%; border-radius:3px; background:linear-gradient(90deg,var(--pe-primary),#8b5cf6); transition:width .5s; }
.pe5-pb-prog-lbl  { font-size:.7rem; color:var(--t3); white-space:nowrap; font-weight:600; }

/* ─── Sections accordéon ─── */
.pe5-section { background:var(--bg-card); border:1px solid var(--bdr); border-radius:var(--r); margin-bottom:14px; overflow:hidden; box-shadow:var(--sh); transition:box-shadow .2s; }
.pe5-section:focus-within { box-shadow:var(--sh-md); }
.pe5-sec-head { display:flex; align-items:center; gap:12px; padding:14px 20px; cursor:pointer; user-select:none; border-bottom:1px solid transparent; transition:border-color .15s; }
.pe5-section.open .pe5-sec-head { border-bottom-color:var(--bdr); }
.pe5-sec-ico  { width:32px; height:32px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:.8rem; flex-shrink:0; }
.pe5-sec-title { font-weight:700; font-size:.88rem; color:var(--t1); flex:1; }
.pe5-sec-meta  { display:flex; align-items:center; gap:8px; }
.pe5-sec-stats { font-size:.7rem; color:var(--t3); font-weight:600; }
.pe5-sec-pill  { font-size:.62rem; font-weight:700; padding:2px 7px; border-radius:6px; background:var(--bg-page); color:var(--t3); border:1px solid var(--bdr); }
.pe5-sec-pill.complete { background:#d1fae5; color:#059669; border-color:#a7f3d0; }
.pe5-sec-chev  { font-size:.7rem; color:var(--t3); transition:transform .2s; flex-shrink:0; }
.pe5-section.open .pe5-sec-chev { transform:rotate(90deg); }
.pe5-sec-body  { display:none; padding:22px; }
.pe5-section.open .pe5-sec-body { display:block; }

/* ─── Champs ─── */
.pe5-field { margin-bottom:16px; }
.pe5-field:last-child { margin-bottom:0; }
.pe5-field-label { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
.pe5-field-label-txt { font-size:12px; font-weight:600; color:var(--t2); text-transform:uppercase; letter-spacing:.04em; display:flex; align-items:center; gap:6px; }
.pe5-field-label-txt i { color:var(--pe-primary); font-size:11px; }
.pe5-char-cnt { font-size:11px; font-weight:600; padding:2px 8px; border-radius:6px; color:var(--t3); background:var(--bg-page); }
.pe5-char-cnt.ok { color:#059669; background:#d1fae5; }
.pe5-char-cnt.w  { color:#d97706; background:#fef3c7; }
.pe5-hint { font-size:11px; color:var(--t3); margin-top:4px; font-style:italic; display:flex; align-items:flex-start; gap:4px; line-height:1.4; }
.pe5-hint i { color:#f59e0b; font-size:.6rem; margin-top:2px; flex-shrink:0; }

.pe5-input, .pe5-textarea { width:100%; padding:10px 14px; border:1px solid var(--bdr); border-radius:var(--r-sm); font-size:14px; color:var(--t1); background:var(--bg-card); transition:border .15s,box-shadow .15s; box-sizing:border-box; font-family:inherit; outline:none; line-height:1.5; }
.pe5-input:focus, .pe5-textarea:focus { border-color:var(--pe-primary); box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.pe5-input.filled, .pe5-textarea.filled { background:#fafbff; border-color:rgba(99,102,241,.25); }
.pe5-textarea { resize:vertical; line-height:1.65; }
.pe5-url-wrap { position:relative; }
.pe5-url-wrap i { position:absolute; left:11px; top:50%; transform:translateY(-50%); color:var(--t3); font-size:.75rem; pointer-events:none; }
.pe5-url-in { padding-left:30px; font-family:monospace; font-size:.82rem; }

/* Image zone ─── */
.pe5-img-zone { width:100%; min-height:120px; border:2px dashed var(--bdr); border-radius:12px; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; transition:all .2s; position:relative; overflow:hidden; background:var(--bg-page); }
.pe5-img-zone:hover { border-color:var(--pe-primary); background:#eef2ff; }
.pe5-img-zone.has-img { border-style:solid; }
.pe5-img-zone img { width:100%; height:100%; object-fit:cover; display:block; }
.pe5-img-ph { text-align:center; color:var(--t3); font-size:12px; padding:16px; }
.pe5-img-ph i { font-size:22px; display:block; margin-bottom:6px; opacity:.5; }
.pe5-img-del { position:absolute; top:6px; right:6px; width:26px; height:26px; border-radius:50%; background:rgba(239,68,68,.9); color:#fff; border:none; cursor:pointer; font-size:10px; display:flex; align-items:center; justify-content:center; }

/* Quill compact ─── */
.pe5-quill-wrap { border:1px solid var(--bdr); border-radius:var(--r-sm); overflow:hidden; }
.pe5-quill-wrap .ql-toolbar   { border:none!important; border-bottom:1px solid var(--bdr)!important; background:#fafbfc; padding:8px 12px; }
.pe5-quill-wrap .ql-container { border:none!important; font-size:.9rem; }
.pe5-quill-wrap .ql-editor    { min-height:160px; padding:14px 16px; line-height:1.75; }
.pe5-quill-wrap.focused       { border-color:var(--pe-primary); box-shadow:0 0 0 3px rgba(99,102,241,.1); }

/* ─── SIDEBAR ─── */
.pe5-side-card { background:var(--bg-card); border:1px solid var(--bdr); border-radius:var(--r); box-shadow:var(--sh); overflow:hidden; margin-bottom:16px; }
.pe5-side-header { padding:13px 18px; border-bottom:1px solid var(--bdr); background:#fafbfc; display:flex; align-items:center; justify-content:space-between; }
.pe5-side-title { font-size:12px; font-weight:700; color:var(--t1); display:flex; align-items:center; gap:7px; text-transform:uppercase; letter-spacing:.04em; }
.pe5-side-title i { color:var(--pe-primary); }
.pe5-side-body { padding:16px 18px; }

/* Statut radios ─── */
.pe5-status-opts { display:flex; gap:10px; }
.pe5-status-opt { flex:1; }
.pe5-status-opt input { display:none; }
.pe5-status-opt label { display:flex; align-items:center; justify-content:center; gap:6px; padding:10px; border:2px solid var(--bdr); border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; transition:all .2s; }
.pe5-status-opt input:checked + .lbl-draft     { border-color:#f59e0b; background:#fffbeb; color:#92400e; }
.pe5-status-opt input:checked + .lbl-published { border-color:#10b981; background:#ecfdf5; color:#065f46; }

/* Ring progression ─── */
.pe5-ring-wrap { display:flex; flex-direction:column; align-items:center; padding:4px 0 8px; }
.pe5-ring-num  { font-size:1.2rem; font-weight:800; color:var(--t1); margin-top:-54px; margin-bottom:32px; text-align:center; }
.pe5-ring-sub  { font-size:.68rem; color:var(--t3); font-weight:600; }

/* Mini-barres sections ─── */
.pe5-spi { display:flex; align-items:center; gap:9px; margin-bottom:6px; cursor:pointer; border-radius:7px; padding:4px 6px; transition:background .15s; }
.pe5-spi:hover { background:#f0f0ff; }
.pe5-spi-ico   { font-size:.65rem; width:12px; text-align:center; flex-shrink:0; }
.pe5-spi-label { font-size:.7rem; font-weight:600; color:var(--t2); flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.pe5-spi-bar   { flex:0 0 50px; height:3px; background:var(--bdr); border-radius:2px; overflow:hidden; }
.pe5-spi-fill  { height:100%; border-radius:2px; transition:width .4s; }
.pe5-spi-score { font-size:.65rem; color:var(--t3); white-space:nowrap; }

/* Stats mini ─── */
.pe5-stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; margin-top:12px; }
.pe5-stat-mini { background:var(--bg-page); border:1px solid var(--bdr); border-radius:9px; padding:9px 6px; text-align:center; }
.pe5-stat-mini-val { font-size:17px; font-weight:800; color:var(--t1); }
.pe5-stat-mini-lbl { font-size:9px; color:var(--t3); text-transform:uppercase; letter-spacing:.04em; margin-top:1px; }

/* SERP sidebar ─── */
.pe5-serp { background:var(--bg-page); border:1px solid var(--bdr); border-radius:10px; padding:12px 14px; margin-top:10px; }
.pe5-serp-label { font-size:10px; color:var(--t3); font-weight:700; text-transform:uppercase; letter-spacing:.06em; margin-bottom:8px; display:flex; align-items:center; gap:5px; }
.pe5-serp-t { color:#1a0dab; font-size:14px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-bottom:2px; font-family:Arial,sans-serif; }
.pe5-serp-u { color:#006621; font-size:11px; margin-bottom:3px; font-family:Arial,sans-serif; }
.pe5-serp-d { color:#545454; font-size:11px; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; font-family:Arial,sans-serif; }
.pe5-serp-inds { display:flex; gap:5px; margin-top:8px; flex-wrap:wrap; }
.pe5-serp-ind { font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; }
.pe5-serp-ind.ok { background:#d1fae5; color:#059669; }
.pe5-serp-ind.w  { background:#fef3c7; color:#d97706; }
.pe5-serp-ind.e  { background:#fee2e2; color:#dc2626; }
.pe5-serp-ind.n  { background:var(--bg-page); color:var(--t3); border:1px solid var(--bdr); }

/* SEO champs ─── */
.pe5-seo-lbl { display:flex; justify-content:space-between; margin-bottom:5px; }
.pe5-seo-lbl-txt { font-size:11px; font-weight:600; color:var(--t3); text-transform:uppercase; letter-spacing:.04em; }
.pe5-seo-cnt     { font-size:11px; font-weight:600; color:var(--t3); }
.pe5-seo-in, .pe5-seo-ta { width:100%; padding:8px 11px; border:1px solid var(--bdr); border-radius:9px; font-size:13px; color:var(--t1); background:var(--bg-card); font-family:inherit; outline:none; transition:border .15s; margin-bottom:10px; box-sizing:border-box; }
.pe5-seo-in:focus, .pe5-seo-ta:focus { border-color:var(--pe-primary); }
.pe5-seo-ta { resize:vertical; min-height:70px; line-height:1.6; }

/* Infos page ─── */
.pe5-info-row { display:flex; justify-content:space-between; padding:6px 0; border-bottom:1px solid var(--bdr); font-size:13px; }
.pe5-info-row:last-child { border-bottom:none; }
.pe5-info-row .lbl { color:var(--t3); font-weight:500; }
.pe5-info-row .val { font-weight:600; color:var(--t1); font-family:monospace; font-size:12px; }

/* Quick actions ─── */
.pe5-quick { display:flex; flex-direction:column; gap:7px; }
.pe5-quick-a { display:flex; align-items:center; gap:10px; padding:10px 14px; background:var(--bg-page); border:1px solid var(--bdr); border-radius:10px; text-decoration:none; color:var(--t2); font-size:13px; font-weight:500; transition:all .2s; }
.pe5-quick-a:hover { background:#eef2ff; border-color:#c7d2fe; color:var(--pe-primary); }
.pe5-quick-a i { color:var(--pe-primary); width:18px; text-align:center; }

/* Danger ─── */
.pe5-side-card.danger { border-color:#fca5a5; }
.pe5-side-card.danger .pe5-side-header { background:#fff1f1; border-color:#fca5a5; }
.pe5-side-card.danger .pe5-side-title,
.pe5-side-card.danger .pe5-side-title i { color:#dc2626; }
.pe5-btn-del { width:100%; padding:11px; background:var(--bg-card); border:1px solid #fca5a5; border-radius:10px; color:#dc2626; font-weight:600; font-size:13px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; font-family:inherit; transition:all .2s; }
.pe5-btn-del:hover { background:#ef4444; color:#fff; border-color:#ef4444; }

/* Toast ─── */
.pe5-toast { position:fixed; bottom:24px; right:24px; z-index:9999; display:flex; align-items:center; gap:10px; padding:12px 20px; border-radius:12px; box-shadow:var(--sh-lg); font-size:14px; font-weight:500; color:#fff; opacity:0; transform:translateY(16px); transition:all .3s cubic-bezier(.34,1.56,.64,1); pointer-events:none; max-width:380px; font-family:'Inter',-apple-system,sans-serif; }
.pe5-toast.show { opacity:1; transform:translateY(0); pointer-events:auto; }
.pe5-toast.ok   { background:var(--pe-success); }
.pe5-toast.err  { background:var(--pe-danger); }

/* Dirty bar ─── */
.pe5-dirty { display:none; position:fixed; bottom:0; left:0; right:0; height:50px; background:rgba(99,102,241,.97); backdrop-filter:blur(8px); align-items:center; justify-content:space-between; padding:0 28px; z-index:500; box-shadow:0 -4px 16px rgba(99,102,241,.2); }
.pe5-dirty.show { display:flex; }
.pe5-dirty-txt  { font-size:13px; color:rgba(255,255,255,.8); font-weight:500; }
.pe5-dirty-acts { display:flex; gap:8px; }
.pe5-dirty-save { display:flex; align-items:center; gap:6px; padding:8px 18px; background:#fff; color:var(--pe-primary); border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; }
.pe5-dirty-pub  { display:flex; align-items:center; gap:6px; padding:8px 18px; background:var(--pe-success); color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; font-family:inherit; }

@keyframes pe5Spin { to { transform:rotate(360deg); } }
.pe5-spin { display:inline-block; animation:pe5Spin .8s linear infinite; }
</style>

<div class="pe5-toast" id="pe5Toast"><i class="fas fa-check-circle" id="pe5ToastIco"></i><span id="pe5ToastMsg"></span></div>
<div class="pe5-dirty" id="pe5Dirty">
    <span class="pe5-dirty-txt"><i class="fas fa-circle-dot" style="margin-right:6px;font-size:.6rem"></i>Modifications non sauvegardees</span>
    <div class="pe5-dirty-acts">
        <button class="pe5-dirty-save" onclick="pe5Save('draft')"><i class="fas fa-floppy-disk"></i> Enregistrer</button>
        <button class="pe5-dirty-pub"  onclick="pe5Save('published')"><i class="fas fa-rocket"></i> Publier</button>
    </div>
</div>

<div class="pe5">

<!-- Header -->
<div class="pe5-header">
    <h2><i class="fas fa-pen-to-square"></i> Editeur de page <span class="id-tag">#<?php echo $pageId ?></span></h2>
    <div class="pe5-btns">
        <a href="?page=pages" class="pe5-btn pe5-btn-ghost"><i class="fas fa-arrow-left"></i> Retour</a>
        <?php if ($viewUrl): ?>
        <a href="<?php echo htmlspecialchars($viewUrl) ?>" target="_blank" class="pe5-btn pe5-btn-view">
            <i class="fas fa-arrow-up-right-from-square"></i> Voir
        </a>
        <?php endif; ?>
        <button type="button" class="pe5-btn pe5-btn-draft"   onclick="pe5Save('draft')"     id="pe5BtnSave">
            <i class="fas fa-floppy-disk"></i> Enregistrer
        </button>
        <button type="button" class="pe5-btn pe5-btn-publish" onclick="pe5Save('published')" id="pe5BtnPub">
            <i class="fas fa-rocket"></i> Publier
        </button>
    </div>
</div>

<?php if ($message): ?>
<div class="pe5-msg ok"><i class="fas fa-check-circle"></i> <?php echo $message ?></div>
<?php endif; ?>

<div class="pe5-grid">

<!-- ═══════════════ COLONNE PRINCIPALE ═══════════════ -->
<div>

<!-- Banner page -->
<div class="pe5-card pe5-page-banner">
    <div class="pe5-card-body" style="padding:18px 22px">
        <div class="pe5-pb-row">
            <div class="pe5-pb-ico" style="background:<?php echo $tplCol ?>18;color:<?php echo $tplCol ?>">
                <i class="fas fa-file-lines"></i>
            </div>
            <div>
                <div class="pe5-pb-title"><?php echo htmlspecialchars($title) ?></div>
                <div class="pe5-pb-slug">/<?php echo htmlspecialchars($slug) ?></div>
            </div>
        </div>
        <div class="pe5-pb-badges">
            <span class="pe5-pb-badge" style="background:<?php echo $tplCol ?>18;color:<?php echo $tplCol ?>">
                <i class="fas fa-layer-group" style="font-size:.58rem"></i> <?php echo htmlspecialchars($tplLabel) ?>
            </span>
            <span class="pe5-pb-badge" style="background:<?php echo $isPub?'#d1fae5':'#fef3c7' ?>;color:<?php echo $isPub?'#059669':'#d97706' ?>">
                <?php echo $isPub ? 'Publie' : 'Brouillon' ?>
            </span>
            <span class="pe5-pb-badge" style="background:#f0f0ff;color:#6366f1" id="pe5FillBdg">
                <?php echo $filledF ?>/<?php echo $totalF ?> champs remplis
            </span>
        </div>
        <div class="pe5-pb-prog">
            <div class="pe5-pb-prog-bar">
                <div class="pe5-pb-prog-fill" id="pe5ProgFill" style="width:<?php echo $fillPct ?>%"></div>
            </div>
            <div class="pe5-pb-prog-lbl" id="pe5ProgLbl"><?php echo $fillPct ?>% complet</div>
        </div>
    </div>
</div>

<!-- SECTIONS accordeon -->
<?php foreach ($sections as $si => $sec):
    $sc = $sec['color'] ?? '#6366f1';
    $sf = array_reduce($sec['fields'], fn($c,$f)=>$c+(!empty(trim(strip_tags((string)($fields_data[$f['key']]??'')))) ? 1:0), 0);
    $st = count($sec['fields']);
    $secOk = ($sf===$st && $st>0);
?>
<div class="pe5-section <?php echo $si===0?'open':'' ?>" id="pe5sec<?php echo $si ?>">
    <div class="pe5-sec-head" onclick="pe5ToggleSec(<?php echo $si ?>)">
        <div class="pe5-sec-ico" style="background:<?php echo $sc ?>18;color:<?php echo $sc ?>">
            <i class="fas <?php echo $sec['icon'] ?>"></i>
        </div>
        <span class="pe5-sec-title"><?php echo htmlspecialchars($sec['section']) ?></span>
        <div class="pe5-sec-meta">
            <span class="pe5-sec-stats" id="pe5ss<?php echo $si ?>"><?php echo $sf ?>/<?php echo $st ?></span>
            <span class="pe5-sec-pill <?php echo $secOk?'complete':'' ?>" id="pe5sp<?php echo $si ?>">
                <?php echo $secOk ? '&#10003; Complet' : ($sf>0 ? 'En cours' : 'Vide') ?>
            </span>
        </div>
        <i class="fas fa-chevron-right pe5-sec-chev"></i>
    </div>
    <div class="pe5-sec-body">
<?php foreach ($sec['fields'] as $fi => $field):
    $key  = $field['key'];
    $lbl  = $field['label'];
    $type = $field['type'];
    $hint = $field['hint'] ?? '';
    $val  = (string)($fields_data[$key] ?? '');
    $ok   = !empty(trim(strip_tags($val)));
    $fid  = 'pe5f'.$si.'_'.$fi;
    $ico  = ['rich'=>'fa-code','textarea'=>'fa-align-left','url'=>'fa-link','image'=>'fa-image'][$type] ?? 'fa-font';
?>
        <div class="pe5-field" data-key="<?php echo htmlspecialchars($key) ?>" data-si="<?php echo $si ?>">
            <div class="pe5-field-label">
                <span class="pe5-field-label-txt">
                    <i class="fas <?php echo $ico ?>"></i><?php echo $lbl ?>
                </span>
                <?php if (in_array($type, ['text','textarea'])): ?>
                <span class="pe5-char-cnt" id="pe5cnt<?php echo $si ?>_<?php echo $fi ?>"><?php echo mb_strlen(strip_tags($val)) ?></span>
                <?php endif; ?>
            </div>

<?php if ($type === 'text'): ?>
            <input type="text" id="<?php echo $fid ?>" class="pe5-input <?php echo $ok?'filled':'' ?>"
                   data-key="<?php echo htmlspecialchars($key) ?>"
                   value="<?php echo htmlspecialchars($val) ?>"
                   placeholder="<?php echo htmlspecialchars(strip_tags($lbl)) ?>..."
                   oninput="pe5OnInput(this,<?php echo $si ?>,<?php echo $fi ?>)">

<?php elseif ($type === 'textarea'): ?>
            <textarea id="<?php echo $fid ?>" class="pe5-textarea <?php echo $ok?'filled':'' ?>"
                      data-key="<?php echo htmlspecialchars($key) ?>"
                      rows="3"
                      placeholder="<?php echo htmlspecialchars(strip_tags($lbl)) ?>..."
                      oninput="pe5OnInput(this,<?php echo $si ?>,<?php echo $fi ?>)"><?php echo htmlspecialchars($val) ?></textarea>

<?php elseif ($type === 'url'): ?>
            <div class="pe5-url-wrap">
                <i class="fas fa-link"></i>
                <input type="text" id="<?php echo $fid ?>" class="pe5-input pe5-url-in <?php echo $ok?'filled':'' ?>"
                       data-key="<?php echo htmlspecialchars($key) ?>"
                       value="<?php echo htmlspecialchars($val) ?>"
                       placeholder="/estimation ou https://..."
                       oninput="pe5OnInput(this,<?php echo $si ?>,<?php echo $fi ?>)">
            </div>

<?php elseif ($type === 'rich'): ?>
            <div class="pe5-quill-wrap" id="pe5qw<?php echo $si ?>_<?php echo $fi ?>">
                <div id="<?php echo $fid ?>"><?php echo $val ?></div>
            </div>
            <input type="hidden" data-key="<?php echo htmlspecialchars($key) ?>" data-rich="1"
                   id="pe5qh<?php echo $si ?>_<?php echo $fi ?>" value="">

<?php elseif ($type === 'image'): ?>
            <div class="pe5-img-zone <?php echo $ok?'has-img':'' ?>"
                 id="pe5img<?php echo $si ?>_<?php echo $fi ?>"
                 onclick="document.getElementById('pe5imgf<?php echo $si ?>_<?php echo $fi ?>').click()">
                <?php if ($ok): ?>
                    <img src="<?php echo htmlspecialchars($val) ?>" alt="">
                    <button type="button" class="pe5-img-del"
                            onclick="event.stopPropagation();pe5RmImg(<?php echo $si ?>,<?php echo $fi ?>)">
                        <i class="fas fa-times"></i>
                    </button>
                <?php else: ?>
                    <div class="pe5-img-ph" id="pe5imgph<?php echo $si ?>_<?php echo $fi ?>">
                        <i class="fas fa-cloud-upload-alt"></i><span>Cliquer pour ajouter</span>
                    </div>
                <?php endif; ?>
            </div>
            <input type="file" id="pe5imgf<?php echo $si ?>_<?php echo $fi ?>" accept="image/*"
                   style="display:none" onchange="pe5SetImg(this,<?php echo $si ?>,<?php echo $fi ?>)">
            <input type="hidden" data-key="<?php echo htmlspecialchars($key) ?>"
                   id="pe5imgv<?php echo $si ?>_<?php echo $fi ?>" value="<?php echo htmlspecialchars($val) ?>">
<?php endif; ?>

<?php if ($hint): ?>
            <div class="pe5-hint"><i class="fas fa-lightbulb"></i><?php echo $hint ?></div>
<?php endif; ?>
        </div>
<?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

</div><!-- /main -->

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<div>

<!-- Publication -->
<div class="pe5-side-card">
    <div class="pe5-side-header"><div class="pe5-side-title"><i class="fas fa-paper-plane"></i> Publication</div></div>
    <div class="pe5-side-body">
        <div class="pe5-status-opts">
            <div class="pe5-status-opt">
                <input type="radio" name="pe5status" id="pe5StDraft" value="draft" <?php echo !$isPub?'checked':'' ?>>
                <label for="pe5StDraft" class="lbl-draft"><i class="fas fa-pencil-alt"></i> Brouillon</label>
            </div>
            <div class="pe5-status-opt">
                <input type="radio" name="pe5status" id="pe5StPub" value="published" <?php echo $isPub?'checked':'' ?>>
                <label for="pe5StPub" class="lbl-published"><i class="fas fa-check"></i> Publie</label>
            </div>
        </div>
        <div style="display:flex;gap:7px;margin-top:14px">
            <button class="pe5-btn pe5-btn-draft"   style="flex:1;justify-content:center" onclick="pe5Save('draft')">
                <i class="fas fa-floppy-disk"></i> Sauver
            </button>
            <button class="pe5-btn pe5-btn-publish" style="flex:1;justify-content:center" onclick="pe5Save('published')">
                <i class="fas fa-rocket"></i> Publier
            </button>
        </div>
        <?php if (!empty($page['updated_at'])): ?>
        <div style="margin-top:10px;font-size:11px;color:var(--t3);line-height:1.9">
            <?php if (!empty($page['created_at'])): ?>Cree : <?php echo date('d/m/Y H:i',strtotime($page['created_at'])) ?><br><?php endif; ?>
            Modifie : <?php echo date('d/m/Y H:i',strtotime($page['updated_at'])) ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Ring remplissage + mini-barres sections -->
<div class="pe5-side-card">
    <div class="pe5-side-header"><div class="pe5-side-title"><i class="fas fa-chart-pie"></i> Remplissage</div></div>
    <div class="pe5-side-body">
        <div class="pe5-ring-wrap">
            <svg width="90" height="90" viewBox="0 0 90 90">
                <circle cx="45" cy="45" r="36" fill="none" stroke="#e2e8f0" stroke-width="8"/>
                <circle cx="45" cy="45" r="36" fill="none" stroke="#6366f1" stroke-width="8"
                        stroke-linecap="round"
                        stroke-dasharray="<?php echo round(226.2*$fillPct/100) ?> 226.2"
                        id="pe5Ring"
                        style="transform:rotate(-90deg);transform-origin:50% 50%;transition:stroke-dasharray .5s ease"/>
            </svg>
            <div class="pe5-ring-num" id="pe5RingPct"><?php echo $fillPct ?>%</div>
            <div class="pe5-ring-sub" id="pe5RingLbl"><?php echo $filledF ?>/<?php echo $totalF ?> champs</div>
        </div>
        <!-- Barres par section -->
        <div style="margin-top:6px">
<?php foreach ($sections as $si => $sec):
    $sc2 = $sec['color'] ?? '#6366f1';
    $sf2 = array_reduce($sec['fields'],fn($c,$f)=>$c+(!empty(trim(strip_tags((string)($fields_data[$f['key']]??'')))) ? 1:0),0);
    $st2 = count($sec['fields']);
    $p2  = $st2>0 ? round($sf2/$st2*100) : 0;
?>
            <div class="pe5-spi" onclick="pe5JumpSec(<?php echo $si ?>)">
                <i class="fas <?php echo $sec['icon'] ?> pe5-spi-ico" style="color:<?php echo $sc2 ?>"></i>
                <span class="pe5-spi-label"><?php echo htmlspecialchars($sec['section']) ?></span>
                <div class="pe5-spi-bar">
                    <div class="pe5-spi-fill" id="pe5spif<?php echo $si ?>"
                         style="width:<?php echo $p2 ?>%;background:<?php echo $sc2 ?>"></div>
                </div>
                <span class="pe5-spi-score" id="pe5spis<?php echo $si ?>"><?php echo $sf2 ?>/<?php echo $st2 ?></span>
            </div>
<?php endforeach; ?>
        </div>
        <!-- Stats -->
        <div class="pe5-stats-row">
            <div class="pe5-stat-mini">
                <div class="pe5-stat-mini-val"><?php echo count($sections) ?></div>
                <div class="pe5-stat-mini-lbl">Sections</div>
            </div>
            <div class="pe5-stat-mini">
                <div class="pe5-stat-mini-val" id="pe5StatF"><?php echo $filledF ?></div>
                <div class="pe5-stat-mini-lbl">Remplis</div>
            </div>
            <div class="pe5-stat-mini">
                <div class="pe5-stat-mini-val" id="pe5StatPct"><?php echo $fillPct ?>%</div>
                <div class="pe5-stat-mini-lbl">Complet</div>
            </div>
        </div>
    </div>
</div>

<!-- SEO -->
<div class="pe5-side-card">
    <div class="pe5-side-header"><div class="pe5-side-title"><i class="fas fa-search"></i> SEO</div></div>
    <div class="pe5-side-body">
        <div class="pe5-seo-lbl">
            <span class="pe5-seo-lbl-txt">Meta Title</span>
            <span class="pe5-seo-cnt" id="pe5MTCnt"><?php echo mb_strlen($metaTitle) ?>/60</span>
        </div>
        <input type="text" class="pe5-seo-in" id="pe5MetaTitle"
               value="<?php echo htmlspecialchars($metaTitle) ?>"
               placeholder="Titre SEO (50-60 car.)"
               oninput="pe5SeoUpdate()">

        <div class="pe5-seo-lbl">
            <span class="pe5-seo-lbl-txt">Meta Description</span>
            <span class="pe5-seo-cnt" id="pe5MDCnt"><?php echo mb_strlen($metaDesc) ?>/160</span>
        </div>
        <textarea class="pe5-seo-ta" id="pe5MetaDesc"
                  rows="3"
                  placeholder="Description (140-155 car.)"
                  oninput="pe5SeoUpdate()"><?php echo htmlspecialchars($metaDesc) ?></textarea>

        <div class="pe5-serp">
            <div class="pe5-serp-label"><i class="fab fa-google" style="color:#4285f4"></i> Apercu Google SERP</div>
            <div class="pe5-serp-t" id="pe5SerpT"><?php echo htmlspecialchars($metaTitle ?: $title) ?></div>
            <div class="pe5-serp-u"><?php echo htmlspecialchars($base_url.'/'.ltrim($slug,'/')) ?></div>
            <div class="pe5-serp-d" id="pe5SerpD"><?php echo htmlspecialchars($metaDesc ?: 'Votre description...') ?></div>
            <div class="pe5-serp-inds">
                <span class="pe5-serp-ind n" id="pe5IndT">Title: <?php echo mb_strlen($metaTitle) ?>/60</span>
                <span class="pe5-serp-ind n" id="pe5IndD">Desc: <?php echo mb_strlen($metaDesc) ?>/160</span>
            </div>
        </div>
    </div>
</div>

<!-- Infos page -->
<div class="pe5-side-card">
    <div class="pe5-side-header"><div class="pe5-side-title"><i class="fas fa-info-circle"></i> Informations</div></div>
    <div class="pe5-side-body">
        <div class="pe5-info-row"><span class="lbl">ID</span><span class="val">#<?php echo $pageId ?></span></div>
        <div class="pe5-info-row"><span class="lbl">Slug</span><span class="val">/<?php echo htmlspecialchars($slug) ?></span></div>
        <div class="pe5-info-row">
            <span class="lbl">Template</span>
            <span class="val" style="color:<?php echo $tplCol ?>;background:<?php echo $tplCol ?>12;padding:1px 6px;border-radius:5px">
                <?php echo htmlspecialchars($tplLabel) ?>
            </span>
        </div>
        <div class="pe5-info-row">
            <span class="lbl">Statut</span>
            <span class="val" style="color:<?php echo $isPub?'#059669':'#d97706' ?>"><?php echo $isPub?'Publie':'Brouillon' ?></span>
        </div>
    </div>
</div>

<!-- Actions rapides -->
<div class="pe5-side-card">
    <div class="pe5-side-header"><div class="pe5-side-title"><i class="fas fa-bolt"></i> Actions rapides</div></div>
    <div class="pe5-side-body">
        <div class="pe5-quick">
            <?php if ($viewUrl): ?>
            <a href="<?php echo htmlspecialchars($viewUrl) ?>" target="_blank" class="pe5-quick-a">
                <i class="fas fa-arrow-up-right-from-square"></i> Voir en ligne
            </a>
            <?php endif; ?>
            <a href="?page=pages" class="pe5-quick-a"><i class="fas fa-list"></i> Toutes les pages</a>
            <a href="?page=pages&action=create" class="pe5-quick-a"><i class="fas fa-plus"></i> Nouvelle page</a>
            <a href="?page=seo" class="pe5-quick-a"><i class="fas fa-chart-line"></i> Suivi SEO</a>
        </div>
    </div>
</div>

<!-- Danger zone -->
<div class="pe5-side-card danger">
    <div class="pe5-side-header"><div class="pe5-side-title"><i class="fas fa-exclamation-triangle"></i> Zone dangereuse</div></div>
    <div class="pe5-side-body">
        <p style="font-size:12px;color:#7f1d1d;margin:0 0 10px">Suppression definitive et irreversible.</p>
        <button type="button" class="pe5-btn-del" onclick="pe5DelPage()">
            <i class="fas fa-trash"></i> Supprimer cette page
        </button>
    </div>
</div>

</div><!-- /sidebar -->
</div><!-- /grid -->
</div><!-- /pe5 -->

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
(function(){
'use strict';

const PAGE_ID = <?php echo $pageId ?>;
const CSRF    = '<?php echo $csrfToken ?>';
const TOTAL_F = <?php echo $totalF ?>;
const API_URL = '/admin/api/content/pages.php';

let fieldsData = <?php echo json_encode($fields_data, JSON_UNESCAPED_UNICODE) ?>;
let isDirty    = false;
let filledCnt  = <?php echo $filledF ?>;

const SECTIONS = <?php
    echo json_encode(array_map(fn($s) => [
        'section' => $s['section'],
        'color'   => $s['color'],
        'fields'  => array_map(fn($f) => ['key'=>$f['key'],'type'=>$f['type']], $s['fields'])
    ], $sections), JSON_UNESCAPED_UNICODE);
?>;

// ── Init Quill pour champs rich ──────────────────────
const quills = {};
document.querySelectorAll('.pe5-quill-wrap').forEach(wrap => {
    const m = wrap.id.match(/pe5qw(\d+)_(\d+)/);
    if (!m) return;
    const [, si, fi] = m;
    const q = new Quill('#pe5f' + si + '_' + fi, {
        theme: 'snow',
        placeholder: 'Saisissez le contenu...',
        modules: { toolbar: [[{header:[2,3,false]}],['bold','italic','underline'],[{list:'ordered'},{list:'bullet'}],['blockquote','link'],['clean']] }
    });
    quills[si + '_' + fi] = q;
    wrap.addEventListener('click', () => wrap.classList.add('focused'));
    q.root.addEventListener('blur',  () => wrap.classList.remove('focused'));
    q.on('text-change', () => {
        const fld = wrap.closest('.pe5-field');
        const fkey = fld ? fld.dataset.key : null;
        const html = q.root.innerHTML;
        const hid  = g('pe5qh' + si + '_' + fi);
        if (hid) hid.value = html;
        if (fkey) pe5UpdateData(fkey, html, parseInt(si));
        pe5SetDirty(true);
    });
});

// ── Input handler ────────────────────────────────────
window.pe5OnInput = function(el, si, fi) {
    const key = el.dataset.key;
    const v   = el.value;
    const cnt = g('pe5cnt' + si + '_' + fi);
    if (cnt) { const n = v.length; cnt.textContent = n; cnt.className = 'pe5-char-cnt' + (n > 200 ? ' w' : ''); }
    pe5UpdateData(key, v, si);
    el.classList.toggle('filled', v.trim().length > 0);
    pe5SetDirty(true);
};

function pe5UpdateData(key, val, si) {
    const was = !fieldsData[key] || String(fieldsData[key]).trim() === '';
    fieldsData[key] = val;
    const now = String(val).trim().length > 0;
    if (was && now) filledCnt++;
    else if (!was && !now) filledCnt--;
    pe5UpdateProgress();
    pe5UpdateSecPills(si);
}

// ── Progression ──────────────────────────────────────
function pe5UpdateProgress() {
    const pct = TOTAL_F > 0 ? Math.round(filledCnt / TOTAL_F * 100) : 0;
    if(g('pe5ProgFill')) g('pe5ProgFill').style.width = pct + '%';
    if(g('pe5ProgLbl'))  g('pe5ProgLbl').textContent  = pct + '% complet';
    if(g('pe5FillBdg'))  g('pe5FillBdg').textContent  = filledCnt + '/' + TOTAL_F + ' champs remplis';
    if(g('pe5RingPct'))  g('pe5RingPct').textContent  = pct + '%';
    if(g('pe5RingLbl'))  g('pe5RingLbl').textContent  = filledCnt + '/' + TOTAL_F + ' champs';
    if(g('pe5StatF'))    g('pe5StatF').textContent    = filledCnt;
    if(g('pe5StatPct'))  g('pe5StatPct').textContent  = pct + '%';
    const ring = g('pe5Ring');
    if (ring) ring.setAttribute('stroke-dasharray', Math.round(226.2 * pct / 100) + ' 226.2');
}

function pe5UpdateSecPills(si) {
    const sec = SECTIONS[si]; if (!sec) return;
    let sf = 0; sec.fields.forEach(f => { if ((fieldsData[f.key]||'').trim()) sf++; });
    const st = sec.fields.length, ok = sf === st && st > 0;
    if(g('pe5ss'+si)) g('pe5ss'+si).textContent = sf + '/' + st;
    if(g('pe5sp'+si)) { g('pe5sp'+si).textContent = ok ? '&#10003; Complet' : (sf>0?'En cours':'Vide'); g('pe5sp'+si).className = 'pe5-sec-pill' + (ok?' complete':''); }
    const fill = g('pe5spif'+si);
    if (fill) fill.style.width = (st > 0 ? Math.round(sf/st*100) : 0) + '%';
    if(g('pe5spis'+si)) g('pe5spis'+si).textContent = sf + '/' + st;
}

// ── Accordeon ────────────────────────────────────────
window.pe5ToggleSec = i => g('pe5sec'+i)?.classList.toggle('open');
window.pe5JumpSec   = i => { const el=g('pe5sec'+i); if(el){ el.classList.add('open'); el.scrollIntoView({behavior:'smooth',block:'start'}); } };

// ── Dirty bar ────────────────────────────────────────
function pe5SetDirty(v) { isDirty = v; g('pe5Dirty')?.classList.toggle('show', v); }

// ── SEO live ─────────────────────────────────────────
window.pe5SeoUpdate = function() {
    const t = (g('pe5MetaTitle')?.value || '').trim();
    const d = (g('pe5MetaDesc')?.value  || '').trim();
    if(g('pe5SerpT')) g('pe5SerpT').textContent = t || '<?php echo htmlspecialchars($title) ?>';
    if(g('pe5SerpD')) g('pe5SerpD').textContent = d || 'Votre description...';
    if(g('pe5MTCnt')) g('pe5MTCnt').textContent = t.length + '/60';
    if(g('pe5MDCnt')) g('pe5MDCnt').textContent = d.length + '/160';
    const iT = g('pe5IndT'); if(iT){ iT.textContent='Title: '+t.length+'/60'; iT.className='pe5-serp-ind '+(t.length>=50&&t.length<=65?'ok':t.length>0?'w':'n'); }
    const iD = g('pe5IndD'); if(iD){ iD.textContent='Desc: '+d.length+'/160';  iD.className='pe5-serp-ind '+(d.length>=140&&d.length<=160?'ok':d.length>0?'w':'n'); }
};

// ── Images ────────────────────────────────────────────
window.pe5SetImg = function(input, si, fi) {
    const f = input.files[0]; if (!f) return;
    const r = new FileReader(); r.onload = e => pe5ApplyImg(e.target.result, si, fi); r.readAsDataURL(f);
};
function pe5ApplyImg(url, si, fi) {
    const zone = g('pe5img'+si+'_'+fi), hid = g('pe5imgv'+si+'_'+fi);
    if (zone) { zone.classList.add('has-img'); zone.innerHTML = `<img src="${url}" alt=""><button type="button" class="pe5-img-del" onclick="event.stopPropagation();pe5RmImg(${si},${fi})"><i class="fas fa-times"></i></button>`; zone.onclick=()=>{}; }
    if (hid)  { pe5UpdateData(hid.dataset.key, url, si); hid.value = url; }
    pe5SetDirty(true); pe5Toast('Image ajoutee', 'ok');
}
window.pe5RmImg = function(si, fi) {
    const zone=g('pe5img'+si+'_'+fi), hid=g('pe5imgv'+si+'_'+fi);
    if(zone){ zone.classList.remove('has-img'); zone.innerHTML=`<div class="pe5-img-ph" id="pe5imgph${si}_${fi}"><i class="fas fa-cloud-upload-alt"></i><span>Cliquer pour ajouter</span></div>`; zone.onclick=()=>document.getElementById('pe5imgf'+si+'_'+fi)?.click(); }
    if(hid) { pe5UpdateData(hid.dataset.key,'',si); hid.value=''; }
    pe5SetDirty(true);
};

// ── Save AJAX ─────────────────────────────────────────
window.pe5Save = async function(status) {
    const btn = g('pe5BtnSave');
    if (btn) { btn.disabled=true; btn.innerHTML='<i class="fas pe5-spin fa-spinner"></i> Sauvegarde...'; }

    // Sync Quill
    Object.entries(quills).forEach(([k, q]) => {
        const wrap = g('pe5qw' + k); if (!wrap) return;
        const fkey = wrap.closest('.pe5-field')?.dataset?.key;
        if (fkey) fieldsData[fkey] = q.root.innerHTML;
    });

    // SEO
    const mt = (g('pe5MetaTitle')?.value||'').trim();
    const md = (g('pe5MetaDesc')?.value ||'').trim();
    if (mt) fieldsData['seo_title']       = mt;
    if (md) fieldsData['seo_description'] = md;

    const radioStatus = document.querySelector('input[name="pe5status"]:checked')?.value || status;

    try {
        const r = await fetch(API_URL, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action:'save_fields', page_id:PAGE_ID, fields:fieldsData, status:radioStatus, csrf_token:CSRF }),
        });
        const d = await r.json();
        if (!d.success) throw new Error(d.error || 'Erreur serveur');
        pe5SetDirty(false);
        pe5Toast(radioStatus==='published' ? 'Page publiee !' : 'Modifications enregistrees', 'ok');
    } catch(err) {
        pe5Toast('Erreur : ' + err.message, 'err');
    }
    if (btn) { btn.disabled=false; btn.innerHTML='<i class="fas fa-floppy-disk"></i> Enregistrer'; }
};

// ── Supprimer ─────────────────────────────────────────
window.pe5DelPage = function() {
    if (!confirm('Supprimer cette page ?\nCette action est definitive.')) return;
    if (!confirm('Derniere confirmation — supprimer "<?php echo htmlspecialchars($title) ?>" ?')) return;
    window.location.href = '?page=pages&action=delete&id='+PAGE_ID+'&csrf_token='+encodeURIComponent(CSRF);
};

// ── Toast ─────────────────────────────────────────────
let _tt;
window.pe5Toast = function(msg, type='ok', dur=3500) {
    const el=g('pe5Toast'), ico=g('pe5ToastIco'), txt=g('pe5ToastMsg');
    const icons={ok:'fa-check-circle',err:'fa-exclamation-circle'};
    if(ico) ico.className='fas '+(icons[type]||'fa-info-circle');
    if(txt) txt.textContent=msg;
    if(el)  { el.className='pe5-toast show '+type; clearTimeout(_tt); _tt=setTimeout(()=>el.classList.remove('show'),dur); }
};

// ── Ctrl+S ────────────────────────────────────────────
document.addEventListener('keydown', e => { if((e.ctrlKey||e.metaKey)&&e.key==='s'){ e.preventDefault(); pe5Save('draft'); } });
window.addEventListener('beforeunload', e => { if(isDirty){ e.preventDefault(); e.returnValue=''; } });

function g(id) { return document.getElementById(id); }
console.log('Pages Editor v5.1 — #<?php echo $pageId ?>');
})();
</script>