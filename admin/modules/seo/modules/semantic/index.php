<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE SÉMANTIQUE SEO — v4.1
 * /admin/modules/seo-semantic/index.php
 * Aligné hub SEO · Pattern IMMO LOCAL+
 * ══════════════════════════════════════════════════════════════
 */
defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);

if (!isset($pdo)) {
    if (!defined('DB_HOST')) {
        $c = dirname(dirname(dirname(__DIR__))) . '/config/config.php';
        if (file_exists($c)) require_once $c;
    }
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    } catch (PDOException $e) { $pdo = null; }
}

$tbl  = fn(string $t) => (function() use ($pdo,$t){ try{ $pdo->query("SELECT 1 FROM `{$t}` LIMIT 1"); return true; } catch(Throwable){ return false; } })();
$cols = fn(string $t) => (function() use ($pdo,$t){ try{ return $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_COLUMN); } catch(Throwable){ return []; } })();
$q    = fn(string $sql, array $p=[]) => (function() use ($pdo,$sql,$p){ try{ $s=$pdo->prepare($sql); $s->execute($p); return (int)$s->fetchColumn(); } catch(Throwable){ return 0; } })();

$hasPg   = $pdo && $tbl('pages');
$hasArt  = $pdo && ($tbl('articles') || $tbl('blog_posts'));
$artTable= $tbl('articles') ? 'articles' : ($tbl('blog_posts') ? 'blog_posts' : null);
$hasGmb  = $pdo && $tbl('gmb_posts');
$hasAvis = $pdo && $tbl('gmb_reviews');

$aiProvider = '';
if (defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY)) $aiProvider = 'Claude';
elseif (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY))   $aiProvider = 'OpenAI';

$tab     = in_array($_GET['sem_tab'] ?? '', ['pages','articles','gmb']) ? $_GET['sem_tab'] : 'pages';
$perPage = 25;
$pageNum = max(1, (int)($_GET['sp'] ?? 1));
$searchSem   = trim($_GET['q_sem']  ?? '');
$filterScore = $_GET['fscore'] ?? '';

$totalPages = $hasPg  ? $q("SELECT COUNT(*) FROM pages") : 0;
$totalArts  = ($hasArt && $artTable) ? $q("SELECT COUNT(*) FROM `{$artTable}`") : 0;
$totalGmb   = $hasGmb  ? $q("SELECT COUNT(*) FROM gmb_posts")   : 0;
$totalAvis  = $hasAvis ? $q("SELECT COUNT(*) FROM gmb_reviews")  : 0;

$semPgPct  = ($hasPg && $totalPages) ? round($q("SELECT COUNT(*) FROM pages WHERE seo_score > 0") / $totalPages * 100) : 0;
$semArtPct = ($artTable && $totalArts) ? (function() use ($pdo,$q,$artTable,$cols){
    $c = $cols($artTable);
    return in_array('seo_score',$c) ? round($q("SELECT COUNT(*) FROM `{$artTable}` WHERE seo_score > 0") / max(1,$q("SELECT COUNT(*) FROM `{$artTable}`")) * 100) : 0;
})() : 0;

$items = []; $totalItems = 0;
if ($tab === 'pages' && $hasPg) {
    $pgCols  = $cols('pages');
    $titleCl = in_array('title',$pgCols)?'title':(in_array('titre',$pgCols)?'titre':'slug');
    $sel = "id, slug, `{$titleCl}` AS item_title";
    foreach (['seo_score','seo_title','seo_description','seo_keywords','seo_issues','seo_analyzed_at'] as $c2)
        if (in_array($c2,$pgCols)) $sel .= ", {$c2}";
    $where=['1=1']; $params=[];
    if ($searchSem) { $where[]="(`{$titleCl}` LIKE ? OR slug LIKE ?)"; $params[]="%{$searchSem}%"; $params[]="%{$searchSem}%"; }
    if ($filterScore==='analyzed')     { try{$where[]="seo_score > 0";}catch(Throwable){} }
    if ($filterScore==='not_analyzed') { try{$where[]="(seo_score = 0 OR seo_score IS NULL)";}catch(Throwable){} }
    if ($filterScore==='good')         { try{$where[]="seo_score >= 60";}catch(Throwable){} }
    if ($filterScore==='warning')      { try{$where[]="seo_score > 0 AND seo_score < 60";}catch(Throwable){} }
    $ws = implode(' AND ',$where);
    try {
        $totalItems = $q("SELECT COUNT(*) FROM pages WHERE {$ws}",$params);
        $st = $pdo->prepare("SELECT {$sel} FROM pages WHERE {$ws} ORDER BY seo_score DESC LIMIT {$perPage} OFFSET ".(($pageNum-1)*$perPage));
        $st->execute($params); $items = $st->fetchAll();
    } catch(Throwable){}
} elseif ($tab==='articles' && $artTable) {
    $aCols   = $cols($artTable);
    $titleCl = in_array('title',$aCols)?'title':(in_array('titre',$aCols)?'titre':'slug');
    $sel = "id, slug, `{$titleCl}` AS item_title";
    foreach (['seo_score','seo_title','seo_description','seo_keywords','seo_issues','seo_analyzed_at'] as $c2)
        if (in_array($c2,$aCols)) $sel .= ", {$c2}";
    $where=['1=1']; $params=[];
    if ($searchSem) { $where[]="`{$titleCl}` LIKE ?"; $params[]="%{$searchSem}%"; }
    $ws = implode(' AND ',$where);
    try {
        $totalItems = $q("SELECT COUNT(*) FROM `{$artTable}` WHERE {$ws}",$params);
        $st = $pdo->prepare("SELECT {$sel} FROM `{$artTable}` WHERE {$ws} ORDER BY id DESC LIMIT {$perPage} OFFSET ".(($pageNum-1)*$perPage));
        $st->execute($params); $items = $st->fetchAll();
    } catch(Throwable){}
} elseif ($tab==='gmb' && $hasGmb) {
    try {
        $gmbCols = $cols('gmb_posts');
        $cntCol  = in_array('content',$gmbCols)?'content':(in_array('description',$gmbCols)?'description':null);
        $sel = "id, 'publication' as item_type, ".($cntCol?"LEFT({$cntCol},60)":'id')." as item_title";
        if (in_array('status',$gmbCols)) $sel .= ", status";
        if (in_array('created_at',$gmbCols)) $sel .= ", created_at as seo_analyzed_at";
        $totalItems = $q("SELECT COUNT(*) FROM gmb_posts");
        $st = $pdo->prepare("SELECT {$sel} FROM gmb_posts ORDER BY id DESC LIMIT {$perPage} OFFSET ".(($pageNum-1)*$perPage));
        $st->execute(); $items = $st->fetchAll();
    } catch(Throwable){}
}
$totalPgSem = ceil($totalItems / $perPage);
?>
<style>
.sm-wrap{--sm-purple:#8b5cf6;}
.sm-back{display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:12px;font-weight:600;color:var(--text-3,#6b7280);text-decoration:none;background:var(--surface,#fff);transition:all .15s;margin-bottom:18px;}
.sm-back:hover{border-color:#8b5cf6;color:#8b5cf6;}
.sm-hero{background:linear-gradient(135deg,#1e0936 0%,#3b0764 60%,#1e0936 100%);border-radius:14px;padding:24px 28px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;overflow:hidden;}
.sm-hero::before{content:'';position:absolute;right:-40px;top:-40px;width:200px;height:200px;background:radial-gradient(circle,rgba(139,92,246,.3) 0%,transparent 70%);pointer-events:none;}
.sm-hero-lbl{font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#a78bfa;margin-bottom:6px;}
.sm-hero-title{font-size:20px;font-weight:900;color:#fff;margin-bottom:4px;}
.sm-hero-sub{font-size:12px;color:rgba(255,255,255,.4);}
.sm-hero-kpis{display:flex;gap:20px;flex-wrap:wrap;position:relative;z-index:1;}
.sm-kv{font-size:22px;font-weight:900;line-height:1;}
.sm-kv.p{background:linear-gradient(135deg,#a78bfa,#c4b5fd);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.sm-kv.w{background:linear-gradient(135deg,#fff,rgba(255,255,255,.7));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.sm-kl{font-size:10px;color:rgba(255,255,255,.4);}
.sm-nav{display:flex;gap:5px;margin-bottom:20px;background:var(--surface-2,#f8fafc);border:1px solid var(--border,#e5e7eb);border-radius:11px;padding:5px;flex-wrap:wrap;}
.sm-nav a{display:flex;align-items:center;gap:7px;padding:8px 14px;border-radius:7px;font-size:12px;font-weight:600;color:var(--text-3,#6b7280);text-decoration:none;transition:all .15s;flex:1;justify-content:center;min-width:100px;white-space:nowrap;}
.sm-nav a:hover{background:var(--surface,#fff);color:var(--text,#111);}
.sm-nav a.active{background:var(--surface,#fff);color:#8b5cf6;box-shadow:0 2px 8px rgba(0,0,0,.07);}
.sm-nav-ico{width:24px;height:24px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0;}
.sm-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:18px;}
.sm-stat{background:var(--surface,#fff);border:1px solid var(--border,#e5e7eb);border-radius:11px;padding:14px 12px;display:flex;align-items:center;gap:10px;}
.sm-stat-ico{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:14px;color:#fff;flex-shrink:0;}
.sm-stat-v{font-size:18px;font-weight:900;color:var(--text,#111);line-height:1;}
.sm-stat-l{font-size:10px;color:var(--text-3,#6b7280);margin-top:2px;}
.sm-tabs{display:flex;gap:4px;margin-bottom:16px;}
.sm-tab{padding:9px 16px;border:1px solid var(--border,#e5e7eb);border-radius:9px;font-size:12px;font-weight:700;text-decoration:none;color:var(--text-3,#6b7280);background:var(--surface,#fff);display:flex;align-items:center;gap:7px;transition:all .15s;}
.sm-tab:hover{border-color:#8b5cf6;color:#8b5cf6;}
.sm-tab.active{background:#8b5cf6;color:#fff;border-color:#8b5cf6;}
.sm-tab-badge{background:rgba(255,255,255,.25);padding:1px 6px;border-radius:8px;font-size:10px;}
.sm-toolbar{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center;}
.sm-search{position:relative;flex:1;max-width:260px;}
.sm-search i{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text-3,#6b7280);font-size:11px;}
.sm-search input{width:100%;padding:9px 12px 9px 32px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:13px;background:var(--surface,#fff);color:var(--text,#111);}
.sm-search input:focus{outline:none;border-color:#8b5cf6;}
.sm-sel{padding:9px 10px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:12px;background:var(--surface,#fff);color:var(--text,#111);}
.sm-btn{padding:9px 16px;border:none;border-radius:8px;cursor:pointer;font-weight:700;font-size:12px;display:inline-flex;align-items:center;gap:7px;transition:all .18s;}
.sm-btn-ai{background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;}
.sm-btn-ai:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(139,92,246,.35);}
.sm-btn-ghost{background:var(--surface,#fff);border:1px solid var(--border,#e5e7eb);color:var(--text,#111);}
.sm-btn-sm{padding:7px 12px;font-size:11px;}
.sm-ai-bar{background:linear-gradient(135deg,rgba(139,92,246,.08),rgba(236,72,153,.06));border:1px solid rgba(139,92,246,.2);border-radius:10px;padding:11px 16px;display:flex;align-items:center;gap:10px;margin-bottom:16px;font-size:12px;}
.sm-ai-dot{width:7px;height:7px;border-radius:50%;background:linear-gradient(135deg,#8b5cf6,#ec4899);flex-shrink:0;animation:smPulse 2s infinite;}
@keyframes smPulse{0%,100%{box-shadow:0 0 0 3px rgba(139,92,246,.2);}50%{box-shadow:0 0 0 6px rgba(139,92,246,.08);}}
.sm-table-wrap{background:var(--surface,#fff);border:1px solid var(--border,#e5e7eb);border-radius:12px;overflow:hidden;}
.sm-table{width:100%;border-collapse:collapse;}
.sm-table th{padding:10px 14px;text-align:left;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-3,#6b7280);background:var(--surface-2,#f8fafc);border-bottom:2px solid var(--border,#e5e7eb);}
.sm-table td{padding:10px 14px;border-bottom:1px solid var(--border,#e5e7eb);vertical-align:middle;}
.sm-table tr:last-child td{border-bottom:0;}
.sm-table tr:hover td{background:rgba(139,92,246,.02);}
.sm-score{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-weight:700;font-size:11px;}
.sm-score.exc{background:#d1fae5;color:#059669;}.sm-score.goo{background:#dcfce7;color:#16a34a;}
.sm-score.war{background:#fef3c7;color:#b45309;}.sm-score.err{background:#fee2e2;color:#b91c1c;}
.sm-score.non{background:#f1f5f9;color:#94a3b8;}
.sm-meta-val.missing{color:#ef4444;font-style:italic;}
.sm-kw{display:flex;flex-wrap:wrap;gap:4px;max-width:200px;}
.sm-kw span{padding:2px 8px;background:#ede9fe;color:#7c3aed;border-radius:10px;font-size:10.5px;font-weight:600;}
.sm-acts{display:flex;gap:4px;}
.sm-act{width:28px;height:28px;border-radius:7px;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;transition:all .18s;}
.sm-act:hover{transform:scale(1.12);}
.sm-act.ai{background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;}
.sm-act.view{background:#d1fae5;color:#059669;text-decoration:none;}
.sm-act.edit{background:#fef3c7;color:#d97706;text-decoration:none;}
.sm-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:3000;align-items:center;justify-content:center;backdrop-filter:blur(3px);}
.sm-modal-overlay.on{display:flex;}
.sm-modal{background:var(--surface,#fff);border-radius:14px;width:95%;max-width:700px;max-height:90vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 24px 48px rgba(0,0,0,.22);}
.sm-modal-hd{padding:15px 20px;border-bottom:1px solid var(--border,#e5e7eb);display:flex;justify-content:space-between;align-items:center;}
.sm-modal-hd h3{margin:0;font-size:15px;font-weight:800;}
.sm-modal-close{width:30px;height:30px;border:none;background:var(--surface-2,#f8fafc);border-radius:7px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--text-3,#6b7280);font-size:14px;}
.sm-modal-body{padding:20px;overflow-y:auto;flex:1;}
.sm-modal-ft{padding:13px 20px;background:var(--surface-2,#f8fafc);border-top:1px solid var(--border,#e5e7eb);display:flex;gap:10px;justify-content:flex-end;}
.sm-an-block{background:var(--surface-2,#f8fafc);border-radius:10px;padding:14px;margin-bottom:12px;}
.sm-an-lbl{font-size:10px;font-weight:700;text-transform:uppercase;color:var(--text-3,#6b7280);margin-bottom:6px;}
.sm-an-val{font-size:13px;color:var(--text,#111);}
.sm-kw-full{display:flex;flex-wrap:wrap;gap:5px;margin-top:6px;}
.sm-kw-full span{padding:3px 10px;background:#ede9fe;color:#7c3aed;border-radius:10px;font-size:11.5px;font-weight:600;}
.sm-loading{display:none;position:fixed;inset:0;background:rgba(255,255,255,.93);z-index:2000;align-items:center;justify-content:center;flex-direction:column;gap:14px;}
.sm-loading.on{display:flex;}
.sm-spinner{width:44px;height:44px;border:4px solid var(--border,#e5e7eb);border-top-color:#8b5cf6;border-radius:50%;animation:smSpin 1s linear infinite;}
@keyframes smSpin{to{transform:rotate(360deg);}}
.sm-pager{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--border,#e5e7eb);font-size:12px;color:var(--text-3,#6b7280);}
.sm-pager-links{display:flex;gap:4px;}
.sm-pager-link{width:30px;height:30px;border-radius:7px;border:1px solid var(--border,#e5e7eb);background:var(--surface,#fff);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;text-decoration:none;color:var(--text,#111);}
.sm-pager-link:hover,.sm-pager-link.active{background:#8b5cf6;color:#fff;border-color:#8b5cf6;}
.sm-notif{position:fixed;top:20px;right:20px;padding:12px 18px;border-radius:10px;color:#fff;font-weight:600;font-size:12.5px;z-index:5000;box-shadow:0 8px 24px rgba(0,0,0,.18);animation:smSlideIn .3s ease;}
@keyframes smSlideIn{from{transform:translateX(100px);opacity:0;}to{transform:translateX(0);opacity:1;}}
.sm-empty{text-align:center;padding:52px 24px;}
.sm-empty i{font-size:40px;opacity:.15;display:block;margin-bottom:12px;}
</style>

<div class="sm-wrap anim">
<div class="sm-loading" id="smLoading"><div class="sm-spinner"></div><span id="smLoadTxt" style="font-size:13px;font-weight:600;">Analyse…</span></div>

<div class="sm-modal-overlay" id="smModal">
    <div class="sm-modal">
        <div class="sm-modal-hd"><h3 id="smModalTitle"><i class="fas fa-brain" style="color:#8b5cf6;"></i> Analyse Sémantique</h3><button class="sm-modal-close" onclick="smClose()"><i class="fas fa-times"></i></button></div>
        <div class="sm-modal-body" id="smModalBody"></div>
        <div class="sm-modal-ft"><button class="sm-btn sm-btn-ghost sm-btn-sm" onclick="smClose()">Fermer</button><button class="sm-btn sm-btn-ai sm-btn-sm" id="smApplyBtn" style="display:none;"><i class="fas fa-check"></i> Appliquer</button></div>
    </div>
</div>

<a href="?page=seo" class="sm-back"><i class="fas fa-arrow-left"></i> Référencement SEO</a>

<div class="sm-hero">
    <div style="position:relative;z-index:1;">
        <div class="sm-hero-lbl"><i class="fas fa-brain"></i> &nbsp;SEO / Sémantique</div>
        <div class="sm-hero-title">Analyse Sémantique</div>
        <div class="sm-hero-sub">Pages · Articles · Publications GMB · IA</div>
    </div>
    <div class="sm-hero-kpis">
        <div style="text-align:right;"><div class="sm-kv p"><?=$semPgPct?>%</div><div class="sm-kl">Pages analysées</div></div>
        <div style="text-align:right;"><div class="sm-kv w"><?=$semArtPct?>%</div><div class="sm-kl">Articles analysés</div></div>
        <div style="text-align:right;"><div class="sm-kv w"><?=$aiProvider?:'-'?></div><div class="sm-kl">IA active</div></div>
    </div>
</div>

<div class="sm-nav">
    <a href="?page=seo"><div class="sm-nav-ico" style="background:#65a30d1a;color:#65a30d;"><i class="fas fa-th-large"></i></div>Vue d'ensemble</a>
    <a href="?page=seo-pages"><div class="sm-nav-ico" style="background:#6366f11a;color:#6366f1;"><i class="fas fa-file-lines"></i></div>SEO Pages</a>
    <a href="?page=seo-semantic" class="active"><div class="sm-nav-ico" style="background:#8b5cf61a;color:#8b5cf6;"><i class="fas fa-brain"></i></div>Sémantique</a>
    <a href="?page=local-seo"><div class="sm-nav-ico" style="background:#0891b21a;color:#0891b2;"><i class="fas fa-location-dot"></i></div>SEO Local & GMB</a>
    <a href="?page=analytics"><div class="sm-nav-ico" style="background:#10b9811a;color:#10b981;"><i class="fas fa-chart-line"></i></div>Analytics</a>
</div>

<div class="sm-stats">
    <?php foreach([['fa-file-lines','linear-gradient(135deg,#6366f1,#4f46e5)',$totalPages,'Pages'],['fa-newspaper','linear-gradient(135deg,#8b5cf6,#7c3aed)',$totalArts,'Articles'],['fa-google','linear-gradient(135deg,#0891b2,#0e7490)',$totalGmb,'Publications GMB'],['fa-star','linear-gradient(135deg,#f59e0b,#d97706)',$totalAvis,'Avis GMB'],['fa-robot','linear-gradient(135deg,#8b5cf6,#ec4899)',$aiProvider?:'Non','IA']] as [$i,$g,$v,$l]): ?>
    <div class="sm-stat"><div class="sm-stat-ico" style="background:<?=$g?>;"><i class="fas <?=$i?>"></i></div><div><div class="sm-stat-v"><?=$v?></div><div class="sm-stat-l"><?=$l?></div></div></div>
    <?php endforeach; ?>
</div>

<?php if ($aiProvider): ?>
<div class="sm-ai-bar">
    <div class="sm-ai-dot"></div>
    <strong><?=$aiProvider?> actif</strong> — Cliquez <i class="fas fa-brain" style="color:#8b5cf6;"></i> pour analyser le champ sémantique.
    <button class="sm-btn sm-btn-ai sm-btn-sm" style="margin-left:auto;" onclick="smBatchAll()"><i class="fas fa-sync-alt"></i> Analyser tout IA</button>
</div>
<?php endif; ?>

<div class="sm-tabs">
    <a href="?page=seo-semantic&sem_tab=pages" class="sm-tab <?=$tab==='pages'?'active':''?>"><i class="fas fa-file-lines"></i> Pages <span class="sm-tab-badge"><?=$totalPages?></span></a>
    <?php if ($artTable): ?><a href="?page=seo-semantic&sem_tab=articles" class="sm-tab <?=$tab==='articles'?'active':''?>"><i class="fas fa-newspaper"></i> Articles <span class="sm-tab-badge"><?=$totalArts?></span></a><?php endif; ?>
    <?php if ($hasGmb||$hasAvis): ?><a href="?page=seo-semantic&sem_tab=gmb" class="sm-tab <?=$tab==='gmb'?'active':''?>"><i class="fas fa-location-dot"></i> GMB <span class="sm-tab-badge"><?=$totalGmb+$totalAvis?></span></a><?php endif; ?>
</div>

<form method="GET" style="display:contents;">
    <input type="hidden" name="page" value="seo-semantic">
    <input type="hidden" name="sem_tab" value="<?=$tab?>">
    <div class="sm-toolbar">
        <div class="sm-search"><i class="fas fa-search"></i><input type="text" name="q_sem" placeholder="Rechercher…" value="<?=htmlspecialchars($searchSem)?>"></div>
        <?php if ($tab!=='gmb'): ?>
        <select name="fscore" class="sm-sel" onchange="this.form.submit()">
            <option value="">Tous</option>
            <option value="analyzed" <?=$filterScore==='analyzed'?'selected':''?>>Analysés</option>
            <option value="not_analyzed" <?=$filterScore==='not_analyzed'?'selected':''?>>Non analysés</option>
            <option value="good" <?=$filterScore==='good'?'selected':''?>>Score ≥60%</option>
            <option value="warning" <?=$filterScore==='warning'?'selected':''?>>À améliorer</option>
        </select>
        <?php endif; ?>
        <button type="submit" class="sm-btn sm-btn-ghost sm-btn-sm"><i class="fas fa-filter"></i></button>
    </div>
</form>

<div class="sm-table-wrap">
<?php if (!empty($items)): ?>
<table class="sm-table">
    <thead><tr><th>Titre</th><th>Score</th><th>Méta SEO</th><th>Mots-clés</th><th>Analysé</th><th style="width:90px;">Actions</th></tr></thead>
    <tbody>
    <?php foreach ($items as $item):
        $sc  = (int)($item['seo_score'] ?? 0);
        $gr  = $sc>=80?'exc':($sc>=60?'goo':($sc>=40?'war':($sc>0?'err':'non')));
        $kws = array_filter(array_map('trim', explode(',', $item['seo_keywords'] ?? '')));
        $t   = $item['item_title'] ?? 'Sans titre';
        $slug= $item['slug'] ?? '';
        $type= $item['item_type'] ?? $tab;
        $hasT= !empty(trim($item['seo_title']??''));
        $hasD= !empty(trim($item['seo_description']??''));
    ?>
    <tr>
        <td><div style="display:flex;flex-direction:column;gap:2px;"><span style="font-size:13px;font-weight:700;color:var(--text,#111);max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?=htmlspecialchars($t)?>"><?=htmlspecialchars($t)?></span><?php if($slug):?><span style="font-size:10.5px;color:var(--text-3,#6b7280);">/<?=htmlspecialchars($slug)?></span><?php endif;?></div></td>
        <td><span class="sm-score <?=$gr?>"><?=$sc>0?$sc.'%':'—'?></span></td>
        <td><div style="display:flex;flex-direction:column;gap:2px;">
            <div style="display:flex;gap:5px;font-size:11px;"><span style="width:40px;font-weight:700;color:var(--text-3,#6b7280);">Titre</span><span class="<?=$hasT?'':'sm-meta-val missing'?>"><?=$hasT?htmlspecialchars(substr($item['seo_title'],0,42)).'…':'manquant'?></span></div>
            <div style="display:flex;gap:5px;font-size:11px;"><span style="width:40px;font-weight:700;color:var(--text-3,#6b7280);">Desc.</span><span class="<?=$hasD?'':'sm-meta-val missing'?>"><?=$hasD?htmlspecialchars(substr($item['seo_description'],0,42)).'…':'manquante'?></span></div>
        </div></td>
        <td><?php if(!empty($kws)):?><div class="sm-kw"><?php foreach(array_slice($kws,0,3) as $k): ?><span><?=htmlspecialchars($k)?></span><?php endforeach; ?><?php if(count($kws)>3):?><span style="background:#f1f5f9;color:#94a3b8;">+<?=count($kws)-3?></span><?php endif;?></div><?php else:?>—<?php endif;?></td>
        <td style="font-size:11px;color:var(--text-3,#6b7280);"><?=!empty($item['seo_analyzed_at'])?date('d/m H:i',strtotime($item['seo_analyzed_at'])):'—'?></td>
        <td><div class="sm-acts">
            <?php if ($aiProvider): ?><button class="sm-act ai" onclick="smAnalyze(<?=$item['id']?>,'<?=$type?>')" title="IA"><i class="fas fa-brain"></i></button><?php endif; ?>
            <?php if ($slug): ?><a href="/<?=htmlspecialchars($slug)?>" target="_blank" class="sm-act view"><i class="fas fa-external-link-alt"></i></a><?php endif; ?>
            <?php if ($type==='pages'): ?><a href="?page=pages&action=edit&id=<?=$item['id']?>" class="sm-act edit"><i class="fas fa-edit"></i></a>
            <?php elseif ($artTable && $type==='articles'): ?><a href="?page=articles&action=edit&id=<?=$item['id']?>" class="sm-act edit"><i class="fas fa-edit"></i></a><?php endif; ?>
        </div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php if ($totalPgSem > 1): ?>
<div class="sm-pager">
    <span><?=$totalItems?> résultats · page <?=$pageNum?>/<?=$totalPgSem?></span>
    <div class="sm-pager-links"><?php
    $base = '?page=seo-semantic&sem_tab='.$tab.'&q_sem='.urlencode($searchSem).'&fscore='.$filterScore;
    for ($i=1;$i<=$totalPgSem;$i++): ?><a href="<?=$base?>&sp=<?=$i?>" class="sm-pager-link <?=$i==$pageNum?'active':''?>"><?=$i?></a><?php endfor; ?></div>
</div>
<?php endif; ?>
<?php else: ?>
<div class="sm-empty"><i class="fas fa-brain"></i><h3 style="font-size:15px;font-weight:700;color:var(--text,#111);">Aucun contenu</h3><p style="font-size:13px;color:var(--text-3,#6b7280);">Modifiez vos filtres.</p></div>
<?php endif; ?>
</div>

</div>
<script>
const SM_API = 'modules/seo-semantic/api.php';
function smAnalyze(id,type){smLoad('🧠 Analyse…');fetch(`${SM_API}?action=analyze&id=${id}&type=${type}`).then(r=>r.json()).then(d=>{smUnload();if(d.success)smShowResult(d);else smNotif('err',d.error);}).catch(e=>{smUnload();smNotif('err',e.message);});}
function smBatchAll(){if(!confirm('Analyser tout ?'))return;smLoad('🤖 Batch…');fetch(SM_API+'?action=analyze-all').then(r=>r.json()).then(d=>{smUnload();smNotif('ok',(d.analyzed||0)+' analysés');setTimeout(()=>location.reload(),1200);}).catch(e=>{smUnload();smNotif('err',e.message);});}
function smShowResult(d){const r=d.result||{};document.getElementById('smModalTitle').innerHTML='<i class="fas fa-brain" style="color:#8b5cf6;"></i> '+smE(d.title||'');let h=`<div style="text-align:center;padding:14px;border-radius:10px;background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;margin-bottom:16px;"><div style="font-size:2rem;font-weight:900;">${r.score||0}%</div><div style="font-size:12px;opacity:.85;">Score sémantique</div></div>`;if(r.primary_keyword)h+=`<div class="sm-an-block"><div class="sm-an-lbl">Mot-clé principal</div><div class="sm-an-val" style="font-size:15px;font-weight:800;color:#8b5cf6;">${smE(r.primary_keyword)}</div></div>`;if(r.keywords?.length)h+=`<div class="sm-an-block"><div class="sm-an-lbl">Champ sémantique (${r.keywords.length})</div><div class="sm-kw-full">${r.keywords.map(k=>`<span>${smE(k)}</span>`).join('')}</div></div>`;if(r.seo_title)h+=`<div class="sm-an-block"><div class="sm-an-lbl">Meta Title (${r.seo_title.length} car.)</div><div class="sm-an-val">${smE(r.seo_title)}</div></div>`;if(r.seo_description)h+=`<div class="sm-an-block"><div class="sm-an-lbl">Meta Description</div><div class="sm-an-val">${smE(r.seo_description)}</div></div>`;if(r.recommendations?.length)h+=`<div class="sm-an-block"><div class="sm-an-lbl">Recommandations</div><ul style="margin:6px 0 0 16px;font-size:12px;line-height:1.8;">${r.recommendations.map(rec=>`<li>${smE(rec)}</li>`).join('')}</ul></div>`;document.getElementById('smModalBody').innerHTML=h;if(d.item_id){const b=document.getElementById('smApplyBtn');b.style.display='flex';b.onclick=()=>smApply(d.item_id,d.type||'pages',r);}document.getElementById('smModal').classList.add('on');}
function smApply(id,type,result){smClose();smLoad('Application…');fetch(SM_API+'?action=apply&id='+id+'&type='+type,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(result)}).then(r=>r.json()).then(d=>{smUnload();smNotif(d.success?'ok':'err',d.success?'✅ Appliqué':d.error);if(d.success)setTimeout(()=>location.reload(),1000);}).catch(e=>{smUnload();smNotif('err',e.message);});}
function smLoad(txt){document.getElementById('smLoadTxt').textContent=txt||'…';document.getElementById('smLoading').classList.add('on');}
function smUnload(){document.getElementById('smLoading').classList.remove('on');}
function smClose(){document.getElementById('smModal').classList.remove('on');}
function smE(s){return s?String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'):'';}
function smNotif(type,msg){document.querySelectorAll('.sm-notif').forEach(n=>n.remove());const n=document.createElement('div');n.className='sm-notif';n.style.background=type==='ok'?'#8b5cf6':'#ef4444';n.innerHTML='<i class="fas fa-'+(type==='ok'?'check-circle':'exclamation-circle')+'"></i> '+msg;document.body.appendChild(n);setTimeout(()=>{n.style.opacity='0';setTimeout(()=>n.remove(),300);},3500);}
document.getElementById('smModal')?.addEventListener('click',e=>{if(e.target.id==='smModal')smClose();});
document.addEventListener('keydown',e=>{if(e.key==='Escape')smClose();});
</script>