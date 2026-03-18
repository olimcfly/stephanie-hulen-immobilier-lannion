<?php
/**
 * SECTEUR — Éditeur Contenu v3.0
 * /admin/modules/content/secteurs/edit.php
 *
 * Architecture 2 couches :
 *   ① CE fichier  = Contenu pur (texte, champs, IA par champ)
 *   ② editor.php  = Design template ({{variables}} injectées)
 *
 * Champs structurés → DB → variables {{xxx}} disponibles dans le template
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }

define('ROOT_PATH', dirname(dirname(dirname(dirname(__DIR__)))));
require_once ROOT_PATH . '/includes/classes/Database.php';
$db = Database::getInstance();

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

$itemId = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'edit';

function jsGo(string $url): never {
    echo '<script>window.location.href="'.addslashes($url).'";</script>'; exit;
}

// ── Colonnes DB ──
$cols = [];
try { $cols = $db->query("SHOW COLUMNS FROM secteurs")->fetchAll(PDO::FETCH_COLUMN); } catch(Throwable){}
$has = fn(string $c): bool => in_array($c, $cols);

// ── CRÉATION ──
if ($action === 'create') {
    if (!hash_equals($csrf, $_GET['csrf_token'] ?? '')) jsGo('/admin/dashboard.php?page=secteurs');
    try {
        $db->prepare("INSERT INTO secteurs (nom,slug,ville,type_secteur,status,created_at) VALUES (?,?,'Bordeaux','quartier','draft',NOW())")
           ->execute(['Nouveau secteur','nouveau-secteur-'.time()]);
        jsGo("/admin/modules/content/secteurs/edit.php?id=".$db->lastInsertId()."&msg=created");
    } catch(PDOException $e){ die($e->getMessage()); }
}

// ── SUPPRESSION ──
if ($action === 'delete' && $itemId) {
    try { $db->prepare("DELETE FROM secteurs WHERE id=?")->execute([$itemId]); } catch(Throwable){}
    jsGo('/admin/dashboard.php?page=secteurs&msg=deleted');
}

if ($itemId <= 0) { header('Location: /admin/dashboard.php?page=secteurs'); exit; }

// ── CHARGEMENT ──
$secteur = null;
try {
    $st = $db->prepare("SELECT * FROM secteurs WHERE id=?");
    $st->execute([$itemId]);
    $secteur = $st->fetch(PDO::FETCH_ASSOC);
} catch(Throwable){}
if (!$secteur) { header('Location: /admin/dashboard.php?page=secteurs&error=not_found'); exit; }

// ── SAUVEGARDE ──
$saveMsg = null; $saveErr = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_secteur'])) {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $saveErr = 'Token invalide.';
    } else {
        $statusEN = $_POST['status'] === 'published' ? 'published' : 'draft';
        $data = [
            'nom'              => trim($_POST['nom'] ?? ''),
            'slug'             => trim($_POST['slug'] ?? ''),
            'ville'            => trim($_POST['ville'] ?? ''),
            'type_secteur'     => $_POST['type_secteur'] ?? 'quartier',
            'description'      => trim($_POST['description'] ?? ''),
            'content'          => $_POST['content'] ?? '',
            'atouts'           => trim($_POST['atouts'] ?? ''),
            'prix_moyen'       => trim($_POST['prix_moyen'] ?? ''),
            'transport'        => trim($_POST['transport'] ?? ''),
            'ambiance'         => trim($_POST['ambiance'] ?? ''),
            'hero_image'       => trim($_POST['hero_image'] ?? ''),
            'hero_title'       => trim($_POST['hero_title'] ?? ''),
            'hero_subtitle'    => trim($_POST['hero_subtitle'] ?? ''),
            'hero_cta_text'    => trim($_POST['hero_cta_text'] ?? ''),
            'hero_cta_url'     => trim($_POST['hero_cta_url'] ?? ''),
            'meta_title'       => trim($_POST['meta_title'] ?? ''),
            'meta_description' => trim($_POST['meta_description'] ?? ''),
            'meta_keywords'    => trim($_POST['meta_keywords'] ?? ''),
            'status'           => $statusEN,
            'template_id'      => intval($_POST['template_id'] ?? 0) ?: null,
        ];
        if ($has('statut')) $data['statut'] = $statusEN === 'published' ? 'publie' : 'brouillon';

        // Auto-slug
        if (empty($data['slug']) && !empty($data['nom'])) {
            $sl = mb_strtolower($data['nom']);
            $sl = strtr($sl,['à'=>'a','â'=>'a','é'=>'e','è'=>'e','ê'=>'e','î'=>'i','ô'=>'o','ù'=>'u','û'=>'u','ç'=>'c']);
            $data['slug'] = trim(preg_replace('/[^a-z0-9]+/','-',$sl),'-');
        }

        $safe = array_filter($data, fn($k) => $has($k), ARRAY_FILTER_USE_KEY);
        $sets = array_map(fn($c)=>"`$c`=?", array_keys($safe));
        $vals = array_values($safe); $vals[] = $itemId;
        try {
            $db->prepare('UPDATE secteurs SET '.implode(',',$sets).' WHERE id=?')->execute($vals);
            jsGo("/admin/modules/content/secteurs/edit.php?id=$itemId&msg=saved");
        } catch(PDOException $e){ $saveErr = $e->getMessage(); }
    }
}

// ── Données affichage ──
$s   = $secteur;
$e   = fn($k,$d='') => htmlspecialchars((string)($s[$k]??$d));
$nom         = $s['nom']             ?? '';
$slug        = $s['slug']            ?? '';
$ville       = $s['ville']           ?? 'Bordeaux';
$typeS       = $s['type_secteur']    ?? 'quartier';
$status      = ($s['status']??'') === 'published' ? 'published' : 'draft';
$description = $s['description']     ?? '';
$content     = $s['content']         ?? '';
$atouts      = $s['atouts']          ?? '';
$prixMoyen   = $s['prix_moyen']      ?? '';
$transport   = $s['transport']       ?? '';
$ambiance    = $s['ambiance']        ?? '';
$heroImage   = $s['hero_image']      ?? '';
$heroTitle   = $s['hero_title']      ?? '';
$heroSub     = $s['hero_subtitle']   ?? '';
$heroCta     = $s['hero_cta_text']   ?? '';
$heroCtaUrl  = $s['hero_cta_url']    ?? '';
$metaTitle   = $s['meta_title']      ?? '';
$metaDesc    = $s['meta_description']?? '';
$metaKeys    = $s['meta_keywords']   ?? '';
$tplId       = intval($s['template_id'] ?? 0);

// ── Templates disponibles ──
$templates = []; $currentTpl = null;
foreach (['builder_templates','templates'] as $tblTpl) {
    try {
        $tpls = $db->query("SELECT id,name,slug,type,thumbnail FROM $tblTpl ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        if ($tpls) { $templates = $tpls; break; }
    } catch(Throwable){}
}
foreach ($templates as $t) { if ((int)$t['id']===$tplId) { $currentTpl=$t; break; } }

// ── IA disponible ──
$aiOk  = (defined('ANTHROPIC_API_KEY')&&ANTHROPIC_API_KEY)||(defined('OPENAI_API_KEY')&&OPENAI_API_KEY);
$aiLbl = defined('ANTHROPIC_API_KEY')&&ANTHROPIC_API_KEY ? 'Claude' : (defined('OPENAI_API_KEY')&&OPENAI_API_KEY?'OpenAI':'');

// ── Variables disponibles pour le template ──
$variables = [
    '{{nom}}'          => $nom,
    '{{slug}}'         => $slug,
    '{{ville}}'        => $ville,
    '{{description}}'  => $description,
    '{{atouts}}'       => $atouts,
    '{{prix_moyen}}'   => $prixMoyen,
    '{{transport}}'    => $transport,
    '{{ambiance}}'     => $ambiance,
    '{{hero_title}}'   => $heroTitle,
    '{{hero_subtitle}}'=> $heroSub,
    '{{hero_cta}}'     => $heroCta,
    '{{hero_image}}'   => $heroImage,
    '{{meta_title}}'   => $metaTitle,
    '{{content}}'      => $content,
];

$msgParam = $_GET['msg'] ?? '';
$flashOk  = ['saved'=>'✅ Contenu enregistré','created'=>'✅ Secteur créé — remplissez le contenu','deleted'=>'✅ Supprimé'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>✏️ Contenu — <?= htmlspecialchars($nom) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ══════════════════════════════════════════════════════
   SECTEUR ÉDITEUR CONTENU v3.0
   Focus : clarté, simplicité, texte avant tout
══════════════════════════════════════════════════════ */
:root {
  --blue:    #1a4d7a;
  --blue-d:  #143d61;
  --gold:    #d4a574;
  --gold-d:  #b8885a;
  --green:   #10b981;
  --purple:  #7c3aed;
  --red:     #ef4444;
  --amber:   #f59e0b;
  --bg:      #f1f5f9;
  --card:    #ffffff;
  --bdr:     #e2e8f0;
  --t1:      #0f172a;
  --t2:      #475569;
  --t3:      #94a3b8;
  --r:       12px;
  --sh:      0 1px 3px rgba(0,0,0,.07);
  --sh-md:   0 4px 16px rgba(0,0,0,.08);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--t1);font-size:14px;line-height:1.6}

/* ── TOPBAR ── */
.ec-top{
  position:sticky;top:0;z-index:200;
  background:var(--card);border-bottom:1px solid var(--bdr);
  padding:0 24px;height:58px;
  display:flex;align-items:center;justify-content:space-between;
  box-shadow:0 1px 8px rgba(0,0,0,.06);
}
.ec-top-left{display:flex;align-items:center;gap:12px}
.ec-back{
  display:inline-flex;align-items:center;gap:6px;
  padding:7px 12px;border-radius:8px;
  color:var(--t2);text-decoration:none;font-size:13px;font-weight:600;
  border:1px solid var(--bdr);transition:all .15s;
}
.ec-back:hover{background:#f8fafc;color:var(--t1)}
.ec-title{
  display:flex;align-items:center;gap:8px;
  font-size:15px;font-weight:700;color:var(--t1);
}
.ec-title-badge{
  padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;
  background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;
}
.ec-status-badge{
  padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;
}
.ec-status-badge.published{background:#d1fae5;color:#059669}
.ec-status-badge.draft{background:#fef3c7;color:#b45309}

.ec-top-right{display:flex;align-items:center;gap:8px}
.ec-btn{
  display:inline-flex;align-items:center;gap:6px;
  padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;
  cursor:pointer;border:none;font-family:'Inter',sans-serif;
  text-decoration:none;transition:all .15s;white-space:nowrap;
}
.ec-btn-ghost{background:transparent;color:var(--t2);border:1px solid var(--bdr)}
.ec-btn-ghost:hover{background:#f8fafc;color:var(--t1)}
.ec-btn-view{background:#e0f2fe;color:#0369a1;border:1px solid #bae6fd}
.ec-btn-view:hover{background:#0ea5e9;color:#fff;border-color:#0ea5e9}
.ec-btn-view.draft{background:#f1f5f9;color:var(--t3);border:1px dashed var(--bdr)}
.ec-btn-tpl{background:#ede9fe;color:#6d28d9;border:1px solid #ddd6fe}
.ec-btn-tpl:hover{background:#7c3aed;color:#fff;border-color:#7c3aed}
.ec-btn-draft{background:#f8fafc;color:var(--t2);border:1px solid var(--bdr)}
.ec-btn-draft:hover{background:#e2e8f0;color:var(--t1)}
.ec-btn-save{background:var(--blue);color:#fff}
.ec-btn-save:hover{background:var(--blue-d);transform:translateY(-1px);box-shadow:0 3px 12px rgba(26,77,122,.25)}

/* ── MODE TABS ── */
.ec-mode-tabs{
  display:flex;gap:2px;padding:3px;
  background:var(--bg);border-radius:10px;border:1px solid var(--bdr);
}
.ec-mode-tab{
  padding:6px 14px;border-radius:8px;font-size:12px;font-weight:600;
  cursor:pointer;border:none;background:transparent;
  color:var(--t2);font-family:'Inter',sans-serif;transition:all .15s;
  display:flex;align-items:center;gap:5px;text-decoration:none;
}
.ec-mode-tab.active{background:var(--card);color:var(--t1);box-shadow:var(--sh)}
.ec-mode-tab:hover:not(.active){color:var(--t1)}

/* ── LAYOUT ── */
.ec-wrap{max-width:1100px;margin:0 auto;padding:24px;display:grid;grid-template-columns:1fr 320px;gap:20px}
@media(max-width:900px){.ec-wrap{grid-template-columns:1fr;padding:16px}}

/* ── FLASH ── */
.ec-flash{
  margin:16px 24px 0;padding:12px 18px;border-radius:10px;
  display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;
  animation:ecFade .3s ease;
}
.ec-flash.ok{background:#d1fae5;color:#059669;border:1px solid rgba(5,150,105,.15)}
.ec-flash.err{background:#fee2e2;color:#dc2626;border:1px solid rgba(220,38,38,.15)}
@keyframes ecFade{from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)}}

/* ── CARDS ── */
.ec-card{
  background:var(--card);border:1px solid var(--bdr);
  border-radius:var(--r);box-shadow:var(--sh);overflow:hidden;
  margin-bottom:16px;
}
.ec-card:last-child{margin-bottom:0}
.ec-card-hdr{
  padding:14px 18px;border-bottom:1px solid var(--bdr);
  display:flex;align-items:center;justify-content:space-between;
  background:#fafbfc;
}
.ec-card-title{
  font-size:13px;font-weight:700;color:var(--t1);
  display:flex;align-items:center;gap:8px;
}
.ec-card-title i{color:var(--blue);font-size:13px}
.ec-card-body{padding:18px}

/* ── CHAMPS ── */
.ec-field{margin-bottom:16px}
.ec-field:last-child{margin-bottom:0}
.ec-label{
  display:flex;align-items:center;justify-content:space-between;
  margin-bottom:6px;
}
.ec-label-text{
  font-size:12px;font-weight:700;color:var(--t2);
  display:flex;align-items:center;gap:5px;text-transform:uppercase;letter-spacing:.04em;
}
.ec-label-text i{color:var(--t3);font-size:11px}
.ec-label-actions{display:flex;align-items:center;gap:6px}

.ec-input,.ec-textarea,.ec-select{
  width:100%;padding:10px 13px;
  border:1.5px solid var(--bdr);border-radius:9px;
  background:#fafbfc;color:var(--t1);font-size:13px;
  font-family:'Inter',sans-serif;transition:all .15s;resize:vertical;
}
.ec-input:focus,.ec-textarea:focus,.ec-select:focus{
  outline:none;border-color:var(--blue);background:#fff;
  box-shadow:0 0 0 3px rgba(26,77,122,.08);
}
.ec-input::placeholder,.ec-textarea::placeholder{color:var(--t3)}
.ec-textarea{min-height:80px;line-height:1.6}
.ec-textarea.tall{min-height:130px}
.ec-input-xl{font-size:16px;font-weight:600;padding:12px 14px}

.ec-row2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:600px){.ec-row2{grid-template-columns:1fr}}

/* Compteur caractères */
.ec-counter{font-size:11px;color:var(--t3);font-weight:500}
.ec-counter.warn{color:var(--amber)}
.ec-counter.ok{color:var(--green)}
.ec-counter.err{color:var(--red)}

/* ── BOUTON IA ── */
.btn-ai{
  display:inline-flex;align-items:center;gap:5px;
  padding:5px 11px;border-radius:7px;font-size:11px;font-weight:700;
  cursor:pointer;border:1.5px solid #c4b5fd;
  background:#f5f3ff;color:#6d28d9;
  font-family:'Inter',sans-serif;transition:all .15s;
  white-space:nowrap;
}
.btn-ai:hover{background:#7c3aed;color:#fff;border-color:#7c3aed}
.btn-ai i{font-size:10px}
.btn-ai.loading{opacity:.6;pointer-events:none}

/* ── SLUG ROW ── */
.ec-slug-row{display:flex;align-items:center;gap:8px}
.ec-slug-prefix{
  font-size:12px;color:var(--t3);padding:10px 8px 10px 13px;
  border:1.5px solid var(--bdr);border-right:none;border-radius:9px 0 0 9px;
  background:#f8fafc;white-space:nowrap;
}
.ec-slug-row .ec-input{border-radius:0 9px 9px 0;flex:1}
.ec-slug-gen{
  padding:8px 12px;border-radius:8px;font-size:11px;font-weight:700;
  cursor:pointer;border:1px solid var(--bdr);background:#f8fafc;color:var(--t2);
  font-family:'Inter',sans-serif;transition:all .15s;white-space:nowrap;
}
.ec-slug-gen:hover{background:var(--blue);color:#fff;border-color:var(--blue)}

/* ── QUILL EDITOR ── */
.ec-quill-wrap{border:1.5px solid var(--bdr);border-radius:9px;overflow:hidden;background:#fff}
.ec-quill-wrap:focus-within{border-color:var(--blue);box-shadow:0 0 0 3px rgba(26,77,122,.08)}
.ec-quill-wrap .ql-toolbar{border:none;border-bottom:1px solid var(--bdr);background:#fafbfc;padding:8px 10px}
.ec-quill-wrap .ql-container{border:none;font-family:'Inter',sans-serif;font-size:14px}
.ec-quill-wrap .ql-editor{min-height:280px;padding:16px;line-height:1.75;color:var(--t1)}
.ec-quill-wrap .ql-editor.ql-blank::before{color:var(--t3);font-style:normal}
.ec-quill-stats{
  display:flex;gap:16px;padding:8px 14px;
  border-top:1px solid var(--bdr);background:#fafbfc;
  font-size:11px;color:var(--t3);
}
.ec-quill-stat{display:flex;align-items:center;gap:4px}
.ec-quill-stat strong{color:var(--t2);font-weight:700}

/* ── HERO PREVIEW ── */
.ec-hero-preview{
  border-radius:10px;overflow:hidden;margin-bottom:14px;
  min-height:120px;position:relative;
  background:linear-gradient(135deg,#1a4d7a,#0f2d4a);
}
.ec-hero-preview-bg{
  position:absolute;inset:0;background-size:cover;background-position:center;
  transition:all .3s;
}
.ec-hero-preview-overlay{
  position:absolute;inset:0;
  background:linear-gradient(135deg,rgba(15,45,74,.75),rgba(0,0,0,.4));
}
.ec-hero-preview-content{
  position:relative;z-index:1;padding:20px;
}
.ec-hero-preview-title{
  font-size:18px;font-weight:800;color:#fff;
  text-shadow:0 1px 4px rgba(0,0,0,.4);margin-bottom:6px;
  font-family:'Inter',sans-serif;
}
.ec-hero-preview-sub{font-size:12px;color:rgba(255,255,255,.8);margin-bottom:12px}
.ec-hero-preview-cta{
  display:inline-flex;align-items:center;gap:6px;
  padding:7px 14px;background:var(--gold);color:#fff;
  border-radius:7px;font-size:12px;font-weight:700;text-decoration:none;
}
.ec-hero-empty{
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  min-height:120px;color:rgba(255,255,255,.3);text-align:center;gap:6px;
}
.ec-hero-empty i{font-size:24px}
.ec-hero-empty span{font-size:11px}

/* ── SEO SCORE ── */
.ec-seo-score{
  display:flex;align-items:center;gap:10px;padding:12px 14px;
  background:var(--bg);border-radius:8px;margin-bottom:14px;
}
.ec-seo-ring{
  width:44px;height:44px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:13px;font-weight:800;
  border:3px solid var(--bdr);color:var(--t2);
}
.ec-seo-ring.good{border-color:var(--green);color:var(--green)}
.ec-seo-ring.ok{border-color:var(--amber);color:var(--amber)}
.ec-seo-ring.bad{border-color:var(--red);color:var(--red)}
.ec-seo-info{flex:1}
.ec-seo-label{font-size:12px;font-weight:700;color:var(--t1)}
.ec-seo-sub{font-size:11px;color:var(--t3)}

/* ── VARIABLES PANEL ── */
.ec-vars{background:var(--card);border:1px solid var(--bdr);border-radius:var(--r);overflow:hidden;margin-bottom:16px}
.ec-vars-hdr{
  padding:12px 16px;background:linear-gradient(135deg,#1a4d7a,#143d61);
  display:flex;align-items:center;justify-content:space-between;cursor:pointer;
}
.ec-vars-hdr-left{display:flex;align-items:center;gap:8px;color:#fff;font-size:12px;font-weight:700}
.ec-vars-hdr-right{font-size:10px;color:rgba(255,255,255,.6)}
.ec-vars-body{padding:12px;display:flex;flex-direction:column;gap:4px}
.ec-var-item{
  display:flex;align-items:center;justify-content:space-between;
  padding:6px 10px;border-radius:6px;border:1px solid var(--bdr);
  cursor:pointer;transition:background .12s;
}
.ec-var-item:hover{background:#f0f9ff;border-color:#bae6fd}
.ec-var-code{font-family:monospace;font-size:11px;color:#0369a1;font-weight:700}
.ec-var-val{font-size:11px;color:var(--t3);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ec-var-empty{color:var(--red);font-style:italic}
.ec-var-copy{font-size:10px;color:var(--t3)}

/* ── SIDEBAR CARDS ── */
.ec-side-card{background:var(--card);border:1px solid var(--bdr);border-radius:var(--r);box-shadow:var(--sh);margin-bottom:16px;overflow:hidden}
.ec-side-hdr{padding:12px 16px;border-bottom:1px solid var(--bdr);display:flex;align-items:center;justify-content:space-between;background:#fafbfc}
.ec-side-title{font-size:12px;font-weight:700;color:var(--t1);display:flex;align-items:center;gap:7px}
.ec-side-title i{color:var(--blue);font-size:12px}
.ec-side-body{padding:14px}

/* Publication */
.ec-pub-radios{display:flex;flex-direction:column;gap:8px;margin-bottom:14px}
.ec-pub-radio{
  display:flex;align-items:center;gap:10px;
  padding:10px 12px;border-radius:9px;border:1.5px solid var(--bdr);
  cursor:pointer;transition:all .15s;
}
.ec-pub-radio:hover{border-color:#d1d5db}
.ec-pub-radio.selected.draft{border-color:var(--amber);background:#fffbeb}
.ec-pub-radio.selected.published{border-color:var(--green);background:#f0fdf4}
.ec-pub-radio input{width:14px;height:14px;accent-color:var(--blue)}
.ec-pub-radio-label{flex:1}
.ec-pub-radio-label strong{display:block;font-size:12px;font-weight:700}
.ec-pub-radio-label span{font-size:11px;color:var(--t3)}
.ec-pub-dates{font-size:11px;color:var(--t3);border-top:1px solid var(--bdr);padding-top:10px;margin-top:2px;display:flex;flex-direction:column;gap:3px}

/* Template card */
.ec-tpl-current{
  display:flex;align-items:center;gap:10px;padding:10px;
  background:#fdf8f4;border:1px solid #f5dfc0;border-radius:9px;margin-bottom:10px;
}
.ec-tpl-thumb{
  width:40px;height:30px;border-radius:6px;flex-shrink:0;
  background:var(--bg);border:1px solid var(--bdr);
  display:flex;align-items:center;justify-content:center;
  overflow:hidden;color:var(--t3);font-size:12px;
}
.ec-tpl-thumb img{width:100%;height:100%;object-fit:cover}
.ec-tpl-info-name{font-size:12px;font-weight:700;color:var(--t1)}
.ec-tpl-info-type{font-size:10px;color:var(--t3)}

/* Quick actions */
.ec-quick{display:flex;flex-direction:column;gap:4px}
.ec-quick-item{
  display:flex;align-items:center;gap:9px;
  padding:9px 11px;border-radius:8px;
  color:var(--t2);text-decoration:none;font-size:12px;font-weight:600;
  border:1px solid transparent;transition:all .12s;
}
.ec-quick-item:hover{background:#f0f9ff;color:var(--blue);border-color:#bae6fd}
.ec-quick-item i{width:14px;text-align:center;color:var(--t3);font-size:11px}
.ec-quick-item:hover i{color:var(--blue)}
.ec-quick-item.design-link{background:#f5f3ff;color:#6d28d9;border-color:#ddd6fe}
.ec-quick-item.design-link i{color:#7c3aed}
.ec-quick-item.design-link:hover{background:#7c3aed;color:#fff;border-color:#7c3aed}
.ec-quick-item.design-link:hover i{color:#fff}

/* Danger */
.ec-danger{padding:10px;background:#fff5f5;border:1px solid #fecaca;border-radius:8px}
.ec-btn-danger{
  width:100%;padding:9px;border-radius:7px;
  background:transparent;color:var(--red);border:1px solid #fca5a5;
  font-size:12px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;
  transition:all .15s;display:flex;align-items:center;justify-content:center;gap:6px;
}
.ec-btn-danger:hover{background:var(--red);color:#fff;border-color:var(--red)}

/* ── MODAL IA ── */
.ec-modal-bg{
  display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);
  z-index:900;align-items:center;justify-content:center;padding:20px;
}
.ec-modal-bg.open{display:flex}
.ec-modal{
  background:var(--card);border-radius:14px;
  width:100%;max-width:520px;max-height:90vh;overflow-y:auto;
  box-shadow:0 20px 60px rgba(0,0,0,.2);animation:ecFade .2s ease;
}
.ec-modal-hdr{
  padding:18px 20px;border-bottom:1px solid var(--bdr);
  display:flex;align-items:center;justify-content:space-between;
  background:linear-gradient(135deg,#1a4d7a,#143d61);border-radius:14px 14px 0 0;
}
.ec-modal-hdr-title{display:flex;align-items:center;gap:8px;color:#fff;font-size:14px;font-weight:700}
.ec-modal-close{
  width:28px;height:28px;border-radius:6px;border:none;
  background:rgba(255,255,255,.15);color:#fff;cursor:pointer;
  display:flex;align-items:center;justify-content:center;font-size:14px;
  transition:background .15s;
}
.ec-modal-close:hover{background:rgba(255,255,255,.25)}
.ec-modal-body{padding:20px}
.ec-modal-field{margin-bottom:14px}
.ec-modal-label{font-size:12px;font-weight:700;color:var(--t2);margin-bottom:5px;display:block;text-transform:uppercase;letter-spacing:.04em}
.ec-modal-result{
  background:#f8fafc;border:1px solid var(--bdr);border-radius:9px;
  padding:14px;font-size:13px;color:var(--t1);line-height:1.7;
  min-height:60px;white-space:pre-wrap;word-break:break-word;
  margin-top:14px;display:none;
}
.ec-modal-result.show{display:block}
.ec-modal-actions{display:flex;gap:8px;margin-top:14px}

/* ── TOAST ── */
.ec-toast-wrap{position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column-reverse;gap:8px}
.ec-toast{
  padding:11px 16px;border-radius:10px;font-size:13px;font-weight:600;
  box-shadow:var(--sh-md);max-width:320px;
  animation:ecToastIn .3s ease;display:flex;align-items:center;gap:8px;
}
.ec-toast.ok{background:#d1fae5;color:#059669;border:1px solid rgba(5,150,105,.2)}
.ec-toast.err{background:#fee2e2;color:#dc2626;border:1px solid rgba(220,38,38,.2)}
.ec-toast.info{background:#e0f2fe;color:#0369a1;border:1px solid rgba(3,105,161,.15)}
@keyframes ecToastIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}

/* ── CHAR BAR ── */
.ec-char-bar{height:3px;border-radius:2px;background:var(--bdr);margin-top:4px;overflow:hidden}
.ec-char-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;background:var(--green)}
.ec-char-fill.warn{background:var(--amber)}
.ec-char-fill.err{background:var(--red)}
</style>
</head>
<body>

<!-- ══ FORM ══ -->
<form method="POST" id="ecForm">
<input type="hidden" name="csrf_token"  value="<?= htmlspecialchars($csrf) ?>">
<input type="hidden" name="save_secteur" value="1">
<input type="hidden" name="status"      id="ecStatusInput" value="<?= $status ?>">
<input type="hidden" name="content"     id="ecContentInput" value="">
<input type="hidden" name="template_id" id="ecTplIdInput"  value="<?= $tplId ?>">

<!-- ══ TOPBAR ══ -->
<div class="ec-top">
  <div class="ec-top-left">
    <a href="/admin/dashboard.php?page=secteurs" class="ec-back">
      <i class="fas fa-arrow-left"></i> Secteurs
    </a>
    <div class="ec-title">
      <i class="fas fa-pencil-alt" style="color:var(--blue)"></i>
      <?= htmlspecialchars(mb_substr($nom,0,32) ?: 'Nouveau secteur') ?>
      <span class="ec-title-badge"><?= $typeS==='commune'?'🏙️ Commune':'🏘️ Quartier' ?></span>
      <span class="ec-status-badge <?= $status ?>" id="ecStatusBadge">
        <?= $status==='published'?'🟢 Publié':'🟡 Brouillon' ?>
      </span>
    </div>
  </div>
  <div class="ec-top-right">
    <!-- Mode tabs : Contenu / Design -->
    <div class="ec-mode-tabs">
      <span class="ec-mode-tab active"><i class="fas fa-pencil-alt"></i> Contenu</span>
      <a href="/admin/modules/builder/builder/editor.php?type=secteur&id=<?= $itemId ?>"
         class="ec-mode-tab"><i class="fas fa-paint-brush"></i> Design</a>
    </div>

    <!-- Voir la page -->
    <?php if ($slug): ?>
    <a href="/<?= htmlspecialchars($slug) ?>" target="_blank"
       class="ec-btn ec-btn-view <?= $status!=='published'?'draft':'' ?>">
      <i class="fas fa-<?= $status==='published'?'external-link-alt':'eye' ?>"></i>
      <?= $status==='published'?'Voir':'Aperçu' ?>
    </a>
    <?php endif; ?>

    <button type="button" class="ec-btn ec-btn-draft" onclick="ecSave('draft')">
      <i class="fas fa-save"></i> Brouillon
    </button>
    <button type="button" class="ec-btn ec-btn-save" onclick="ecSave('published')">
      <i class="fas fa-check"></i> Publier
    </button>
  </div>
</div>

<!-- Flash -->
<?php if (isset($flashOk[$msgParam])): ?>
<div class="ec-flash ok"><i class="fas fa-check-circle"></i> <?= $flashOk[$msgParam] ?></div>
<?php endif; ?>
<?php if ($saveErr): ?>
<div class="ec-flash err"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($saveErr) ?></div>
<?php endif; ?>

<!-- ══ LAYOUT ══ -->
<div class="ec-wrap">

  <!-- ════ COLONNE PRINCIPALE ════ -->
  <div>

    <!-- ① IDENTITÉ DU SECTEUR -->
    <div class="ec-card">
      <div class="ec-card-hdr">
        <span class="ec-card-title"><i class="fas fa-map-marker-alt"></i> Identité du secteur</span>
      </div>
      <div class="ec-card-body">
        <!-- Nom -->
        <div class="ec-field">
          <div class="ec-label">
            <span class="ec-label-text"><i class="fas fa-tag"></i> Nom du secteur <span style="color:var(--red)">*</span></span>
            <?php if($aiOk): ?>
            <div class="ec-label-actions">
              <button type="button" class="btn-ai" onclick="aiField('nom','Génère un nom optimisé SEO pour ce secteur immobilier')">
                <i class="fas fa-robot"></i> IA
              </button>
            </div>
            <?php endif; ?>
          </div>
          <input type="text" name="nom" id="ecNom" class="ec-input ec-input-xl"
                 value="<?= $e('nom') ?>" required placeholder="Ex : Bacalan, Les Chartrons, Talence Centre...">
        </div>

        <div class="ec-row2">
          <!-- Ville -->
          <div class="ec-field">
            <div class="ec-label"><span class="ec-label-text"><i class="fas fa-city"></i> Ville</span></div>
            <input type="text" name="ville" id="ecVille" class="ec-input"
                   value="<?= $e('ville','Bordeaux') ?>" placeholder="Bordeaux, Mérignac...">
          </div>
          <!-- Type -->
          <div class="ec-field">
            <div class="ec-label"><span class="ec-label-text"><i class="fas fa-home"></i> Type</span></div>
            <select name="type_secteur" class="ec-select">
              <option value="quartier" <?= $typeS==='quartier'?'selected':'' ?>>🏘️ Quartier</option>
              <option value="commune"  <?= $typeS==='commune'?'selected':'' ?>>🏙️ Commune</option>
            </select>
          </div>
        </div>

        <!-- Slug -->
        <div class="ec-field">
          <div class="ec-label"><span class="ec-label-text"><i class="fas fa-link"></i> Slug URL</span></div>
          <div class="ec-slug-row">
            <span class="ec-slug-prefix">votresite.fr/</span>
            <input type="text" name="slug" id="ecSlug" class="ec-input"
                   value="<?= $e('slug') ?>" placeholder="bacalan-bordeaux">
            <button type="button" class="ec-slug-gen" onclick="ecGenSlug()">
              <i class="fas fa-magic"></i> Auto
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ② DESCRIPTION COURTE -->
    <div class="ec-card">
      <div class="ec-card-hdr">
        <span class="ec-card-title"><i class="fas fa-align-left"></i> Description courte</span>
        <span style="font-size:11px;color:var(--t3)">Variable : <code style="background:#f1f5f9;padding:2px 5px;border-radius:4px;font-size:10px">{{description}}</code></span>
      </div>
      <div class="ec-card-body">
        <div class="ec-field">
          <div class="ec-label">
            <span class="ec-label-text"><i class="fas fa-file-alt"></i> Résumé du secteur <span id="ecDescCount" class="ec-counter">0 / 300</span></span>
            <?php if($aiOk): ?>
            <div class="ec-label-actions">
              <button type="button" class="btn-ai" onclick="aiField('description','Génère une description courte et engageante pour ce secteur immobilier')">
                <i class="fas fa-robot"></i> Générer
              </button>
            </div>
            <?php endif; ?>
          </div>
          <textarea name="description" id="ecDesc" class="ec-textarea tall"
                    oninput="ecCount(this,'ecDescCount',300)"
                    placeholder="Présentez le secteur en 2-3 phrases : ambiance générale, public cible, atouts principaux..."><?= $e('description') ?></textarea>
          <div class="ec-char-bar"><div class="ec-char-fill" id="ecDescBar" style="width:0%"></div></div>
        </div>
      </div>
    </div>

    <!-- ③ DONNÉES DU QUARTIER -->
    <div class="ec-card">
      <div class="ec-card-hdr">
        <span class="ec-card-title"><i class="fas fa-map"></i> Données du quartier</span>
        <span style="font-size:11px;color:var(--t3)">Injectées comme variables dans le template</span>
      </div>
      <div class="ec-card-body">

        <!-- Atouts -->
        <div class="ec-field">
          <div class="ec-label">
            <span class="ec-label-text"><i class="fas fa-star"></i> Atouts / Points forts <code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;font-size:10px;font-weight:400">{{atouts}}</code></span>
            <?php if($aiOk): ?>
            <button type="button" class="btn-ai" onclick="aiField('atouts','Liste les 5 principaux atouts immobiliers de ce quartier')">
              <i class="fas fa-robot"></i> IA
            </button>
            <?php endif; ?>
          </div>
          <textarea name="atouts" id="ecAtouts" class="ec-textarea"
                    placeholder="Ex : Proche métro, commerces de proximité, écoles réputées, parc à 5 min, quartier calme..."><?= $e('atouts') ?></textarea>
        </div>

        <div class="ec-row2">
          <!-- Prix -->
          <div class="ec-field">
            <div class="ec-label">
              <span class="ec-label-text"><i class="fas fa-euro-sign"></i> Prix moyen m² <code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;font-size:10px;font-weight:400">{{prix_moyen}}</code></span>
              <?php if($aiOk): ?>
              <button type="button" class="btn-ai" onclick="aiField('prix_moyen','Donne le prix moyen au m² et la tendance pour ce secteur')">
                <i class="fas fa-robot"></i> IA
              </button>
              <?php endif; ?>
            </div>
            <input type="text" name="prix_moyen" id="ecPrix" class="ec-input"
                   value="<?= $e('prix_moyen') ?>" placeholder="Ex : 4 200 €/m²">
          </div>
          <!-- Ambiance -->
          <div class="ec-field">
            <div class="ec-label">
              <span class="ec-label-text"><i class="fas fa-palette"></i> Ambiance <code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;font-size:10px;font-weight:400">{{ambiance}}</code></span>
              <?php if($aiOk): ?>
              <button type="button" class="btn-ai" onclick="aiField('ambiance','Décris l\'ambiance et le caractère de ce quartier en 1-2 phrases')">
                <i class="fas fa-robot"></i> IA
              </button>
              <?php endif; ?>
            </div>
            <input type="text" name="ambiance" id="ecAmbiance" class="ec-input"
                   value="<?= $e('ambiance') ?>" placeholder="Ex : Bourgeois-bohème, familial, branché...">
          </div>
        </div>

        <!-- Transport -->
        <div class="ec-field">
          <div class="ec-label">
            <span class="ec-label-text"><i class="fas fa-train"></i> Transports <code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;font-size:10px;font-weight:400">{{transport}}</code></span>
            <?php if($aiOk): ?>
            <button type="button" class="btn-ai" onclick="aiField('transport','Décris l\'accès et les transports en commun disponibles dans ce quartier')">
              <i class="fas fa-robot"></i> IA
            </button>
            <?php endif; ?>
          </div>
          <input type="text" name="transport" id="ecTransport" class="ec-input"
                 value="<?= $e('transport') ?>" placeholder="Ex : Tram A à 200m, bus C3, rocade à 5 min...">
        </div>

      </div>
    </div>

    <!-- ④ CONTENU PRINCIPAL -->
    <div class="ec-card">
      <div class="ec-card-hdr">
        <span class="ec-card-title"><i class="fas fa-file-alt"></i> Contenu principal</span>
        <span style="font-size:11px;color:var(--t3)">Variable : <code style="background:#f1f5f9;padding:2px 5px;border-radius:4px;font-size:10px">{{content}}</code></span>
      </div>
      <div class="ec-card-body">
        <?php if($aiOk): ?>
        <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
          <button type="button" class="btn-ai" onclick="aiContent('article')">
            <i class="fas fa-robot"></i> Générer un article complet
          </button>
          <button type="button" class="btn-ai" onclick="aiContent('seo')">
            <i class="fas fa-search"></i> Optimiser pour SEO
          </button>
          <button type="button" class="btn-ai" onclick="aiContent('buyers')">
            <i class="fas fa-home"></i> Angle acheteurs
          </button>
          <button type="button" class="btn-ai" onclick="aiContent('sellers')">
            <i class="fas fa-key"></i> Angle vendeurs
          </button>
        </div>
        <?php endif; ?>
        <div class="ec-quill-wrap">
          <div id="ecQuill"><?= $content ?></div>
        </div>
        <div class="ec-quill-stats">
          <span class="ec-quill-stat"><i class="fas fa-font"></i> <strong id="ecWordCount">0</strong> mots</span>
          <span class="ec-quill-stat"><i class="fas fa-clock"></i> ~<strong id="ecReadTime">0</strong> min de lecture</span>
          <span class="ec-quill-stat"><i class="fas fa-text-width"></i> <strong id="ecCharCount">0</strong> caractères</span>
        </div>
      </div>
    </div>

    <!-- ⑤ SECTION HERO -->
    <div class="ec-card">
      <div class="ec-card-hdr">
        <span class="ec-card-title"><i class="fas fa-image"></i> Section Hero</span>
        <span style="font-size:11px;color:var(--t3)">Variables : {{hero_title}}, {{hero_subtitle}}, {{hero_image}}, {{hero_cta}}</span>
      </div>
      <div class="ec-card-body">
        <!-- Preview live -->
        <div class="ec-hero-preview" id="ecHeroPreview">
          <div class="ec-hero-preview-bg" id="ecHeroBg"></div>
          <div class="ec-hero-preview-overlay"></div>
          <div class="ec-hero-preview-content" id="ecHeroContent">
            <div class="ec-hero-empty" id="ecHeroEmpty">
              <i class="fas fa-image"></i>
              <span>Remplissez les champs pour voir l'aperçu</span>
            </div>
          </div>
        </div>

        <div class="ec-row2">
          <div class="ec-field">
            <div class="ec-label">
              <span class="ec-label-text"><i class="fas fa-heading"></i> Titre hero</span>
              <?php if($aiOk): ?><button type="button" class="btn-ai" onclick="aiField('hero_title','Génère un titre accrocheur pour ce secteur immobilier')"><i class="fas fa-robot"></i> IA</button><?php endif; ?>
            </div>
            <input type="text" name="hero_title" id="ecHeroTitle" class="ec-input"
                   value="<?= $e('hero_title') ?>" oninput="ecUpdateHero()"
                   placeholder="Immobilier à Bacalan — votre conseiller local">
          </div>
          <div class="ec-field">
            <div class="ec-label">
              <span class="ec-label-text"><i class="fas fa-align-left"></i> Sous-titre</span>
              <?php if($aiOk): ?><button type="button" class="btn-ai" onclick="aiField('hero_subtitle','Génère un sous-titre rassurant pour ce secteur immobilier')"><i class="fas fa-robot"></i> IA</button><?php endif; ?>
            </div>
            <input type="text" name="hero_subtitle" id="ecHeroSub" class="ec-input"
                   value="<?= $e('hero_subtitle') ?>" oninput="ecUpdateHero()"
                   placeholder="Expertise locale, résultats concrets">
          </div>
        </div>

        <div class="ec-row2">
          <div class="ec-field">
            <div class="ec-label"><span class="ec-label-text"><i class="fas fa-mouse-pointer"></i> Texte CTA</span></div>
            <input type="text" name="hero_cta_text" id="ecHeroCta" class="ec-input"
                   value="<?= $e('hero_cta_text') ?>" oninput="ecUpdateHero()"
                   placeholder="Estimer mon bien">
          </div>
          <div class="ec-field">
            <div class="ec-label"><span class="ec-label-text"><i class="fas fa-link"></i> URL CTA</span></div>
            <input type="text" name="hero_cta_url" id="ecHeroCtaUrl" class="ec-input"
                   value="<?= $e('hero_cta_url') ?>" placeholder="/estimation">
          </div>
        </div>

        <div class="ec-field">
          <div class="ec-label"><span class="ec-label-text"><i class="fas fa-image"></i> Image de fond (URL)</span></div>
          <input type="text" name="hero_image" id="ecHeroImg" class="ec-input"
                 value="<?= $e('hero_image') ?>" oninput="ecUpdateHero()"
                 placeholder="https://...jpg ou /uploads/...">
        </div>
      </div>
    </div>

    <!-- ⑥ SEO -->
    <div class="ec-card">
      <div class="ec-card-hdr">
        <span class="ec-card-title"><i class="fas fa-search"></i> SEO</span>
        <span style="font-size:11px;color:var(--t3)">Variables : {{meta_title}}, {{meta_description}}</span>
      </div>
      <div class="ec-card-body">
        <!-- Meta title -->
        <div class="ec-field">
          <div class="ec-label">
            <span class="ec-label-text"><i class="fas fa-heading"></i> Meta title <span id="ecMetaTitleCount" class="ec-counter">0 / 65</span></span>
            <?php if($aiOk): ?><button type="button" class="btn-ai" onclick="aiField('meta_title','Génère un meta title SEO optimisé pour ce secteur immobilier, 55-65 caractères')"><i class="fas fa-robot"></i> IA</button><?php endif; ?>
          </div>
          <input type="text" name="meta_title" id="ecMetaTitle" class="ec-input"
                 value="<?= $e('meta_title') ?>" oninput="ecCount(this,'ecMetaTitleCount',65)"
                 placeholder="Immobilier à Bacalan Bordeaux — Eduardo De Sul Conseiller">
          <div class="ec-char-bar"><div class="ec-char-fill" id="ecMetaTitleBar" style="width:0%"></div></div>
        </div>
        <!-- Meta description -->
        <div class="ec-field">
          <div class="ec-label">
            <span class="ec-label-text"><i class="fas fa-align-left"></i> Meta description <span id="ecMetaDescCount" class="ec-counter">0 / 160</span></span>
            <?php if($aiOk): ?><button type="button" class="btn-ai" onclick="aiField('meta_description','Génère une meta description SEO pour ce secteur immobilier, 140-160 caractères')"><i class="fas fa-robot"></i> IA</button><?php endif; ?>
          </div>
          <textarea name="meta_description" id="ecMetaDesc" class="ec-textarea"
                    oninput="ecCount(this,'ecMetaDescCount',160)"
                    placeholder="Découvrez l'immobilier à Bacalan avec Eduardo De Sul, votre conseiller local. Estimation gratuite, accompagnement personnalisé."><?= $e('meta_description') ?></textarea>
          <div class="ec-char-bar"><div class="ec-char-fill" id="ecMetaDescBar" style="width:0%"></div></div>
        </div>
        <!-- Keywords -->
        <div class="ec-field">
          <div class="ec-label"><span class="ec-label-text"><i class="fas fa-tags"></i> Mots-clés</span></div>
          <input type="text" name="meta_keywords" class="ec-input" value="<?= $e('meta_keywords') ?>"
                 placeholder="immobilier bacalan bordeaux, appartement bacalan, maison bacalan...">
        </div>

        <!-- SERP Preview -->
        <div style="background:#fff;border:1px solid var(--bdr);border-radius:9px;padding:14px;margin-top:4px">
          <div style="font-size:10px;color:var(--t3);font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">
            <i class="fab fa-google" style="color:#4285f4"></i> Aperçu Google
          </div>
          <div id="ecSerpTitle" style="font-size:14px;color:#1a0dab;font-weight:500;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
            <?= htmlspecialchars($metaTitle ?: $nom ?: 'Titre de la page') ?>
          </div>
          <div style="font-size:12px;color:#006621;margin-bottom:3px">
            votresite.fr › <span id="ecSerpSlug"><?= htmlspecialchars($slug) ?></span>
          </div>
          <div id="ecSerpDesc" style="font-size:12px;color:#545454;line-height:1.5">
            <?= htmlspecialchars($metaDesc ?: 'Description de la page...') ?>
          </div>
        </div>
      </div>
    </div>

  </div><!-- /colonne principale -->

  <!-- ════ SIDEBAR ════ -->
  <div>

    <!-- Publication -->
    <div class="ec-side-card">
      <div class="ec-side-hdr">
        <span class="ec-side-title"><i class="fas fa-paper-plane"></i> Publication</span>
      </div>
      <div class="ec-side-body">
        <div class="ec-pub-radios">
          <label class="ec-pub-radio <?= $status==='draft'?'selected draft':'' ?>" id="ecRadioDraft" onclick="ecSetStatus('draft')">
            <input type="radio" name="_status_ui" value="draft" <?= $status==='draft'?'checked':'' ?>>
            <div class="ec-pub-radio-label">
              <strong>🟡 Brouillon</strong>
              <span>Non visible sur le site</span>
            </div>
          </label>
          <label class="ec-pub-radio <?= $status==='published'?'selected published':'' ?>" id="ecRadioPublished" onclick="ecSetStatus('published')">
            <input type="radio" name="_status_ui" value="published" <?= $status==='published'?'checked':'' ?>>
            <div class="ec-pub-radio-label">
              <strong>🟢 Publié</strong>
              <span>Visible sur le site</span>
            </div>
          </label>
        </div>
        <?php if (!empty($s['created_at'])): ?>
        <div class="ec-pub-dates">
          <span><i class="fas fa-plus" style="width:12px"></i> Créé le <?= date('d/m/Y', strtotime($s['created_at'])) ?></span>
          <?php if (!empty($s['updated_at'])): ?>
          <span><i class="fas fa-edit" style="width:12px"></i> Modifié le <?= date('d/m/Y H:i', strtotime($s['updated_at'])) ?></span>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <div style="display:flex;flex-direction:column;gap:6px;margin-top:12px">
          <button type="button" class="ec-btn ec-btn-draft" style="width:100%;justify-content:center" onclick="ecSave('draft')">
            <i class="fas fa-save"></i> Enregistrer brouillon
          </button>
          <button type="button" class="ec-btn ec-btn-save" style="width:100%;justify-content:center" onclick="ecSave('published')">
            <i class="fas fa-check"></i> Publier le secteur
          </button>
        </div>
      </div>
    </div>

    <!-- Template assigné -->
    <div class="ec-side-card">
      <div class="ec-side-hdr">
        <span class="ec-side-title"><i class="fas fa-palette"></i> Template design</span>
        <?php if($currentTpl): ?>
        <a href="/admin/modules/builder/builder/editor.php?type=template&id=<?= $tplId ?>"
           style="font-size:11px;color:var(--blue);font-weight:700;text-decoration:none">
          <i class="fas fa-edit"></i> Modifier
        </a>
        <?php endif; ?>
      </div>
      <div class="ec-side-body">
        <?php if ($currentTpl): ?>
        <div class="ec-tpl-current">
          <div class="ec-tpl-thumb">
            <?php if(!empty($currentTpl['thumbnail'])): ?><img src="<?= htmlspecialchars($currentTpl['thumbnail']) ?>"><?php else: ?><i class="fas fa-file-alt"></i><?php endif; ?>
          </div>
          <div>
            <div class="ec-tpl-info-name"><?= htmlspecialchars($currentTpl['name']) ?></div>
            <div class="ec-tpl-info-type"><?= htmlspecialchars($currentTpl['type'] ?? 'template') ?></div>
          </div>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:12px;color:var(--t3);font-size:12px;background:var(--bg);border:1px dashed var(--bdr);border-radius:8px;margin-bottom:10px">
          <i class="fas fa-palette" style="display:block;font-size:20px;margin-bottom:5px;opacity:.3"></i>
          Aucun template
        </div>
        <?php endif; ?>

        <!-- Select template -->
        <div class="ec-field">
          <select class="ec-select" id="ecTplSelect" onchange="ecChangeTpl(this)">
            <option value="0">— Aucun template —</option>
            <?php foreach($templates as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= (int)$t['id']===$tplId?'selected':'' ?>>
              <?= htmlspecialchars($t['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div style="display:flex;flex-direction:column;gap:5px;margin-top:6px">
          <a href="/admin/modules/builder/builder/editor.php?type=secteur&id=<?= $itemId ?>"
             class="ec-quick-item design-link">
            <i class="fas fa-paint-brush"></i> Ouvrir l'éditeur design
          </a>
          <?php if($currentTpl): ?>
          <a href="/admin/modules/builder/builder/editor.php?type=template&id=<?= $tplId ?>"
             class="ec-quick-item design-link">
            <i class="fas fa-palette"></i> Modifier le template
          </a>
          <?php endif; ?>
          <a href="/admin/modules/builder/builder/templates.php" class="ec-quick-item">
            <i class="fas fa-list"></i> Tous les templates
          </a>
        </div>
      </div>
    </div>

    <!-- Variables disponibles -->
    <div class="ec-vars">
      <div class="ec-vars-hdr" onclick="ecToggleVars()">
        <div class="ec-vars-hdr-left">
          <i class="fas fa-code"></i> Variables pour le template
        </div>
        <span class="ec-vars-hdr-right" id="ecVarsChevron">▾ Voir</span>
      </div>
      <div class="ec-vars-body" id="ecVarsBody" style="display:none">
        <?php foreach($variables as $varCode => $varVal): ?>
        <div class="ec-var-item" onclick="ecCopyVar('<?= htmlspecialchars($varCode) ?>')" title="Cliquer pour copier">
          <span class="ec-var-code"><?= htmlspecialchars($varCode) ?></span>
          <span class="ec-var-val <?= empty($varVal)?'ec-var-empty':'' ?>">
            <?= empty($varVal) ? 'vide' : htmlspecialchars(mb_substr(strip_tags($varVal),0,30)) ?>
          </span>
          <i class="fas fa-copy ec-var-copy"></i>
        </div>
        <?php endforeach; ?>
        <div style="padding:8px;background:#f0f9ff;border-radius:6px;font-size:11px;color:#0369a1;margin-top:4px">
          <i class="fas fa-info-circle"></i>
          Utilisez ces variables dans votre template HTML.<br>
          Ex : <code>&lt;h1&gt;{{nom}}&lt;/h1&gt;</code>
        </div>
      </div>
    </div>

    <!-- Actions rapides -->
    <div class="ec-side-card">
      <div class="ec-side-hdr">
        <span class="ec-side-title"><i class="fas fa-bolt"></i> Actions rapides</span>
      </div>
      <div class="ec-side-body">
        <div class="ec-quick">
          <a href="/admin/modules/builder/builder/editor.php?type=secteur&id=<?= $itemId ?>" class="ec-quick-item design-link">
            <i class="fas fa-paint-brush"></i> Éditeur design (Builder Pro)
          </a>
          <?php if($slug): ?>
          <a href="/<?= htmlspecialchars($slug) ?>" target="_blank" class="ec-quick-item"
             style="<?= $status!=='published'?'border-style:dashed;color:var(--t3)':'' ?>">
            <i class="fas fa-<?= $status==='published'?'external-link-alt':'eye' ?>"></i>
            <?= $status==='published'?'Voir la page publiée':'Aperçu (brouillon)' ?>
          </a>
          <?php endif; ?>
          <a href="/admin/dashboard.php?page=secteurs" class="ec-quick-item">
            <i class="fas fa-list"></i> Tous les secteurs
          </a>
          <a href="/admin/modules/content/secteurs/edit.php?action=create&csrf_token=<?= $csrf ?>" class="ec-quick-item">
            <i class="fas fa-plus"></i> Nouveau secteur
          </a>
        </div>
      </div>
    </div>

    <!-- Danger zone -->
    <div class="ec-side-card">
      <div class="ec-side-hdr">
        <span class="ec-side-title" style="color:var(--red)"><i class="fas fa-exclamation-triangle"></i> Zone dangereuse</span>
      </div>
      <div class="ec-side-body">
        <div class="ec-danger">
          <button type="button" class="ec-btn-danger" onclick="ecDelete()">
            <i class="fas fa-trash"></i> Supprimer ce secteur
          </button>
        </div>
      </div>
    </div>

  </div><!-- /sidebar -->
</div><!-- /layout -->
</form>

<!-- ══ MODAL IA PAR CHAMP ══ -->
<div class="ec-modal-bg" id="ecAiModal">
  <div class="ec-modal">
    <div class="ec-modal-hdr">
      <div class="ec-modal-hdr-title">
        <i class="fas fa-robot"></i>
        <span id="ecAiModalTitle">Génération IA</span>
        <?php if($aiLbl): ?>
        <span style="background:rgba(255,255,255,.15);padding:2px 8px;border-radius:12px;font-size:10px"><?= htmlspecialchars($aiLbl) ?></span>
        <?php endif; ?>
      </div>
      <button class="ec-modal-close" onclick="ecCloseModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="ec-modal-body">
      <div class="ec-modal-field">
        <label class="ec-modal-label">Contexte supplémentaire (optionnel)</label>
        <textarea id="ecAiContext" class="ec-textarea"
                  placeholder="Précisions sur le quartier, audience cible, éléments spécifiques à mentionner..."></textarea>
      </div>
      <div style="display:flex;gap:8px">
        <button type="button" class="ec-btn ec-btn-save" style="flex:1;justify-content:center" onclick="ecRunAi()">
          <i class="fas fa-robot"></i> Générer
        </button>
        <button type="button" class="ec-btn ec-btn-ghost" onclick="ecCloseModal()">Annuler</button>
      </div>
      <!-- Résultat -->
      <div class="ec-modal-result" id="ecAiResult"></div>
      <div class="ec-modal-actions" id="ecAiActions" style="display:none">
        <button type="button" class="ec-btn ec-btn-save" style="flex:1;justify-content:center" onclick="ecApplyAi()">
          <i class="fas fa-check"></i> Appliquer
        </button>
        <button type="button" class="ec-btn ec-btn-ghost" onclick="ecRunAi()">
          <i class="fas fa-redo"></i> Regénérer
        </button>
        <button type="button" class="ec-btn ec-btn-ghost" onclick="ecCloseModal()">Fermer</button>
      </div>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="ec-toast-wrap" id="ecToasts"></div>

<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
// ══════════════════════════════════════════════════════════
//  SECTEUR ÉDITEUR CONTENU v3.0 — JS
// ══════════════════════════════════════════════════════════

const EC = {
  csrf:    '<?= addslashes($csrf) ?>',
  itemId:  <?= $itemId ?>,
  nom:     <?= json_encode($nom) ?>,
  ville:   <?= json_encode($ville) ?>,
  typeS:   <?= json_encode($typeS) ?>,
  aiEndpoint: '/admin/api/ai/generate.php',
};

// ── Quill ──
const quill = new Quill('#ecQuill', {
  theme: 'snow',
  placeholder: 'Rédigez ici le contenu principal du secteur : présentation détaillée, histoire, vie de quartier, conseils immobiliers...',
  modules: {
    toolbar: [
      [{ header: [2, 3, false] }],
      ['bold', 'italic', 'underline'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      ['link', 'blockquote'],
      ['clean']
    ]
  }
});
quill.on('text-change', () => {
  const text = quill.getText();
  const words = text.trim().split(/\s+/).filter(w => w.length > 0).length;
  const chars = text.length;
  g('ecWordCount').textContent  = words;
  g('ecReadTime').textContent   = Math.max(1, Math.round(words / 200));
  g('ecCharCount').textContent  = chars;
  // Sync SERP slug
  syncSerp();
});

// ── Helpers ──
const g = id => document.getElementById(id);
const sv = (id, v) => { if(g(id)) g(id).value = v; };

// ── Compteurs ──
function ecCount(el, counterId, max) {
  const len = el.value.length;
  const pct = Math.min(100, (len/max)*100);
  const counter = g(counterId);
  const bar = g(counterId.replace('Count','Bar'));
  if (counter) {
    counter.textContent = `${len} / ${max}`;
    counter.className = 'ec-counter ' + (len > max ? 'err' : len > max*0.85 ? 'warn' : len > 0 ? 'ok' : '');
  }
  if (bar) {
    bar.style.width = pct + '%';
    bar.className = 'ec-char-fill ' + (len > max ? 'err' : len > max*0.85 ? 'warn' : '');
  }
  syncSerp();
}

// ── SERP sync ──
function syncSerp() {
  const title = (g('ecMetaTitle')?.value || g('ecNom')?.value || 'Titre');
  const slug  = g('ecSlug')?.value || '';
  const desc  = g('ecMetaDesc')?.value || 'Description...';
  if(g('ecSerpTitle')) g('ecSerpTitle').textContent = title;
  if(g('ecSerpSlug'))  g('ecSerpSlug').textContent  = slug;
  if(g('ecSerpDesc'))  g('ecSerpDesc').textContent  = desc;
}

// ── Hero preview ──
function ecUpdateHero() {
  const title  = g('ecHeroTitle')?.value || '';
  const sub    = g('ecHeroSub')?.value   || '';
  const cta    = g('ecHeroCta')?.value   || '';
  const img    = g('ecHeroImg')?.value   || '';
  const empty  = g('ecHeroEmpty');
  const bg     = g('ecHeroBg');
  const cnt    = g('ecHeroContent');

  if (img && bg) bg.style.backgroundImage = `url('${img}')`;
  else if (bg)   bg.style.backgroundImage = '';

  if (!title && !sub && !cta) {
    if(empty) empty.style.display = 'flex';
    return;
  }
  if(empty) empty.style.display = 'none';
  if(cnt) cnt.innerHTML = `
    ${title ? `<div class="ec-hero-preview-title">${title}</div>` : ''}
    ${sub   ? `<div class="ec-hero-preview-sub">${sub}</div>` : ''}
    ${cta   ? `<span class="ec-hero-preview-cta"><i class="fas fa-arrow-right"></i>${cta}</span>` : ''}
  `;
}

// ── Slug ──
function ecGenSlug() {
  const nom   = g('ecNom')?.value   || '';
  const ville = g('ecVille')?.value || '';
  let sl = nom.toLowerCase()
    .replace(/[àâ]/g,'a').replace(/[éèêë]/g,'e').replace(/[îï]/g,'i')
    .replace(/[ôö]/g,'o').replace(/[ùûü]/g,'u').replace(/ç/g,'c')
    .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
  if (ville) {
    const v = ville.toLowerCase()
      .replace(/[àâ]/g,'a').replace(/[éèêë]/g,'e').replace(/ç/g,'c')
      .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
    sl += '-' + v;
  }
  if(g('ecSlug')) g('ecSlug').value = sl;
  toast('Slug généré', 'ok');
}

// ── Status ──
function ecSetStatus(val) {
  sv('ecStatusInput', val);
  ['draft','published'].forEach(s => {
    const el = g('ecRadio'+s.charAt(0).toUpperCase()+s.slice(1));
    if(el) el.className = `ec-pub-radio ${val===s?'selected '+s:''}`;
  });
  const badge = g('ecStatusBadge');
  if(badge) {
    badge.className = `ec-status-badge ${val}`;
    badge.textContent = val==='published'?'🟢 Publié':'🟡 Brouillon';
  }
}

// ── Template ──
function ecChangeTpl(sel) {
  sv('ecTplIdInput', sel.value);
  toast(sel.value > 0 ? `Template sélectionné — sauvegardez pour appliquer` : 'Template retiré', 'info');
}

// ── Save ──
function ecSave(status) {
  ecSetStatus(status);
  // Sync Quill content
  sv('ecContentInput', quill.root.innerHTML);
  g('ecForm').submit();
}

// ── Delete ──
function ecDelete() {
  if (!confirm('Supprimer définitivement ce secteur ?')) return;
  if (!confirm('Confirmer la suppression — cette action est irréversible.')) return;
  window.location.href = `/admin/modules/content/secteurs/edit.php?action=delete&id=${EC.itemId}&csrf_token=${EC.csrf}`;
}

// ── Variables panel ──
function ecToggleVars() {
  const body = g('ecVarsBody');
  const chevron = g('ecVarsChevron');
  if(!body) return;
  const open = body.style.display !== 'none';
  body.style.display = open ? 'none' : 'flex';
  if(chevron) chevron.textContent = open ? '▾ Voir' : '▴ Masquer';
}

function ecCopyVar(code) {
  navigator.clipboard.writeText(code).then(() => toast(`${code} copié !`, 'info'));
}

// ══════════════════════════════════════════════════════
//  IA PAR CHAMP
// ══════════════════════════════════════════════════════

let _aiField = null;
let _aiTargetEl = null;
let _aiPrompt = '';
let _aiResult = '';

const AI_FIELD_MAP = {
  'nom':           { label: 'Nom du secteur',     el: 'ecNom',        type: 'input' },
  'description':   { label: 'Description courte', el: 'ecDesc',       type: 'textarea' },
  'atouts':        { label: 'Atouts / Points forts', el: 'ecAtouts',  type: 'textarea' },
  'prix_moyen':    { label: 'Prix moyen m²',       el: 'ecPrix',       type: 'input' },
  'ambiance':      { label: 'Ambiance',            el: 'ecAmbiance',   type: 'input' },
  'transport':     { label: 'Transports',          el: 'ecTransport',  type: 'input' },
  'hero_title':    { label: 'Titre hero',          el: 'ecHeroTitle',  type: 'input', cb: ecUpdateHero },
  'hero_subtitle': { label: 'Sous-titre hero',     el: 'ecHeroSub',    type: 'input', cb: ecUpdateHero },
  'meta_title':    { label: 'Meta title SEO',      el: 'ecMetaTitle',  type: 'input', cb: syncSerp },
  'meta_description':{ label: 'Meta description', el: 'ecMetaDesc',   type: 'textarea', cb: syncSerp },
};

function aiField(fieldName, prompt) {
  _aiField  = fieldName;
  _aiPrompt = prompt;
  _aiResult = '';

  const cfg = AI_FIELD_MAP[fieldName];
  const title = g('ecAiModalTitle');
  if(title) title.textContent = `IA — ${cfg?.label || fieldName}`;

  // Pré-remplir le contexte
  const ctx = g('ecAiContext');
  if(ctx) ctx.value = '';

  const res = g('ecAiResult');
  const acts = g('ecAiActions');
  if(res)  { res.textContent = ''; res.className = 'ec-modal-result'; }
  if(acts) acts.style.display = 'none';

  g('ecAiModal').classList.add('open');
}

function aiContent(angle) {
  const angles = {
    article: 'Rédige un article de blog complet et SEO-optimisé sur ce secteur immobilier',
    seo:     'Rédige un contenu SEO-optimisé pour bien référencer ce secteur sur Google',
    buyers:  'Rédige un contenu pour attirer des acheteurs intéressés par ce secteur',
    sellers: 'Rédige un contenu pour attirer des vendeurs souhaitant vendre dans ce secteur',
  };
  _aiField  = '__content__';
  _aiPrompt = angles[angle] || angles.article;
  _aiResult = '';
  const title = g('ecAiModalTitle');
  if(title) title.textContent = 'IA — Contenu principal';
  if(g('ecAiContext')) g('ecAiContext').value = '';
  if(g('ecAiResult'))  { g('ecAiResult').textContent=''; g('ecAiResult').className='ec-modal-result'; }
  if(g('ecAiActions')) g('ecAiActions').style.display='none';
  g('ecAiModal').classList.add('open');
}

async function ecRunAi() {
  const nom   = g('ecNom')?.value   || EC.nom;
  const ville = g('ecVille')?.value || EC.ville;
  const ctx   = g('ecAiContext')?.value || '';
  const res   = g('ecAiResult');
  const acts  = g('ecAiActions');
  if(res)  { res.textContent = '⏳ Génération en cours...'; res.className = 'ec-modal-result show'; }
  if(acts) acts.style.display = 'none';

  const isContent = (_aiField === '__content__');
  const suffix = isContent
    ? '\n\nRédige en HTML simple (h2, h3, p, ul, li). Longueur : 600-1000 mots. Commence directement.'
    : '\n\nRéponds directement avec le contenu demandé, sans introduction ni explication.';

  const prompt = `${_aiPrompt}\n\nSecteur : ${nom}\nVille : ${ville}\nType : ${EC.typeS}\n${ctx?'Contexte : '+ctx:''}${suffix}`;

  try {
    const fd = new FormData();
    fd.append('module','secteurs');
    fd.append('action', isContent ? 'content' : _aiField);
    fd.append('prompt', prompt);
    fd.append('nom', nom);
    fd.append('ville', ville);
    fd.append('csrf_token', EC.csrf);
    const r = await fetch(EC.aiEndpoint, {method:'POST', body:fd});
    const d = await r.json();
    _aiResult = d.content || d.text || d.result || '';
    if(_aiResult) {
      if(res)  { res.textContent = _aiResult; res.className = 'ec-modal-result show'; }
      if(acts) acts.style.display = 'flex';
    } else {
      if(res) res.textContent = '❌ ' + (d.error || 'Réponse vide');
    }
  } catch(err) {
    if(res) res.textContent = '❌ Erreur réseau : ' + err.message;
  }
}

function ecApplyAi() {
  if (!_aiResult) return;
  if (_aiField === '__content__') {
    quill.root.innerHTML = _aiResult;
    quill.update();
    ecCloseModal();
    toast('Contenu principal mis à jour', 'ok');
    return;
  }
  const cfg = AI_FIELD_MAP[_aiField];
  if (!cfg) return;
  const el = g(cfg.el);
  if (!el) return;
  el.value = _aiResult;
  if (cfg.cb) cfg.cb();
  if (_aiField === 'meta_title')       ecCount(el,'ecMetaTitleCount',65);
  if (_aiField === 'meta_description') ecCount(el,'ecMetaDescCount',160);
  if (_aiField === 'description')      ecCount(el,'ecDescCount',300);
  ecCloseModal();
  toast(`${cfg.label} mis à jour`, 'ok');
}

function ecCloseModal() { g('ecAiModal').classList.remove('open'); }

// ── Toast ──
function toast(msg, type='ok', dur=3500) {
  const wrap = g('ecToasts');
  if(!wrap) return;
  const t = document.createElement('div');
  t.className = `ec-toast ${type}`;
  const icons = {ok:'fa-check-circle',err:'fa-exclamation-triangle',info:'fa-info-circle'};
  t.innerHTML = `<i class="fas ${icons[type]||icons.ok}"></i> ${msg}`;
  wrap.appendChild(t);
  setTimeout(() => { t.style.opacity='0'; t.style.transform='translateX(20px)'; t.style.transition='all .3s'; setTimeout(()=>t.remove(),320); }, dur);
}

// ── Ctrl+S ──
document.addEventListener('keydown', e => {
  if ((e.ctrlKey||e.metaKey) && e.key==='s') { e.preventDefault(); ecSave('draft'); toast('Sauvegarde...','info',1500); }
});

// ── Init ──
document.addEventListener('DOMContentLoaded', () => {
  // Compteurs initiaux
  const mt = g('ecMetaTitle');
  const md = g('ecMetaDesc');
  const dc = g('ecDesc');
  if(mt) ecCount(mt,'ecMetaTitleCount',65);
  if(md) ecCount(md,'ecMetaDescCount',160);
  if(dc) ecCount(dc,'ecDescCount',300);
  // Hero
  ecUpdateHero();
  // Serp
  syncSerp();
});
</script>
</body>
</html>