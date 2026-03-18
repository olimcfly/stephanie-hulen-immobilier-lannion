<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE ANALYTICS — v2.1
 * /admin/modules/analytics/index.php
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
            DB_USER, DB_PASS, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    } catch (PDOException $e) { $pdo = null; }
}
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$tblExists = fn(string $t) => (function() use ($pdo,$t){ try{ $pdo->query("SELECT 1 FROM `{$t}` LIMIT 1"); return true; }catch(Throwable){ return false; } })();
$q  = fn(string $sql, array $p=[]) => (function() use ($pdo,$sql,$p){ try{ $s=$pdo->prepare($sql); $s->execute($p); return (int)$s->fetchColumn(); }catch(Throwable){ return 0; } })();
$qA = fn(string $sql, array $p=[]) => (function() use ($pdo,$sql,$p){ try{ $s=$pdo->prepare($sql); $s->execute($p); return $s->fetchAll(); }catch(Throwable){ return []; } })();

$hasViews = $pdo && $tblExists('page_views');
$hasConv  = $pdo && $tblExists('conversion_events');
$viewCols = $hasViews ? (function() use ($pdo){ try{ return $pdo->query("SHOW COLUMNS FROM page_views")->fetchAll(PDO::FETCH_COLUMN); }catch(Throwable){ return []; } })() : [];
$hasDevice= in_array('device', $viewCols);
$hasRef   = in_array('referrer',$viewCols);
$hasDur   = in_array('duration',$viewCols);

if ($pdo && !$hasViews) {
    try { $pdo->exec("CREATE TABLE IF NOT EXISTS page_views (id BIGINT AUTO_INCREMENT PRIMARY KEY, page_url VARCHAR(255), referrer VARCHAR(255) DEFAULT NULL, device ENUM('desktop','mobile','tablet') DEFAULT 'desktop', utm_source VARCHAR(100) DEFAULT NULL, duration INT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_c (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); $hasViews=true; } catch(Throwable){}
}
if ($pdo && !$hasConv) {
    try { $pdo->exec("CREATE TABLE IF NOT EXISTS conversion_events (id INT AUTO_INCREMENT PRIMARY KEY, event_type VARCHAR(80), page_url VARCHAR(255) DEFAULT NULL, value DECIMAL(10,2) DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX idx_c (created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); $hasConv=true; } catch(Throwable){}
}

$period = in_array($_GET['period']??'',['7','30','90','365']) ? $_GET['period'] : '30';
$dateFrom = date('Y-m-d', strtotime("-{$period} days"));
$datePrev = date('Y-m-d', strtotime("-".($period*2)." days"));

$pvTotal   = $hasViews ? $q("SELECT COUNT(*) FROM page_views WHERE created_at >= ?", [$dateFrom.' 00:00:00']) : 0;
$pvPrev    = $hasViews ? $q("SELECT COUNT(*) FROM page_views WHERE created_at < ? AND created_at >= ?", [$dateFrom.' 00:00:00',$datePrev.' 00:00:00']) : 0;
$convTotal = $hasConv  ? $q("SELECT COUNT(*) FROM conversion_events WHERE created_at >= ?", [$dateFrom.' 00:00:00']) : 0;
$convPrev  = $hasConv  ? $q("SELECT COUNT(*) FROM conversion_events WHERE created_at < ? AND created_at >= ?", [$dateFrom.' 00:00:00',$datePrev.' 00:00:00']) : 0;
$avgDur    = ($hasViews&&$hasDur) ? $q("SELECT COALESCE(AVG(duration),0) FROM page_views WHERE created_at >= ? AND duration > 0", [$dateFrom.' 00:00:00']) : 0;
$mobileV   = ($hasViews&&$hasDevice) ? $q("SELECT COUNT(*) FROM page_views WHERE created_at >= ? AND device='mobile'",  [$dateFrom.' 00:00:00']) : 0;
$desktopV  = ($hasViews&&$hasDevice) ? $q("SELECT COUNT(*) FROM page_views WHERE created_at >= ? AND device='desktop'", [$dateFrom.' 00:00:00']) : 0;
$tabletV   = ($hasViews&&$hasDevice) ? $q("SELECT COUNT(*) FROM page_views WHERE created_at >= ? AND device='tablet'",  [$dateFrom.' 00:00:00']) : 0;

$pvGrowth   = $pvPrev  >0 ? round(($pvTotal  -$pvPrev  )/$pvPrev  *100,1) : null;
$convGrowth = $convPrev>0 ? round(($convTotal -$convPrev)/$convPrev*100,1) : null;
$convRate   = $pvTotal >0 ? round($convTotal/$pvTotal*100,2) : 0;
$totalDev   = max(1,$mobileV+$desktopV+$tabletV);

$topPages   = $hasViews ? $qA("SELECT page_url, COUNT(*) as views FROM page_views WHERE created_at >= ? GROUP BY page_url ORDER BY views DESC LIMIT 10", [$dateFrom.' 00:00:00']) : [];
$topSources = ($hasViews&&$hasRef) ? $qA("SELECT COALESCE(utm_source, IF(referrer IS NULL OR referrer='','Direct',SUBSTRING_INDEX(REPLACE(REPLACE(referrer,'https://',''),'http://',''),'/',1))) AS source, COUNT(*) AS visits FROM page_views WHERE created_at >= ? GROUP BY source ORDER BY visits DESC LIMIT 8", [$dateFrom.' 00:00:00']) : [];
$topConv    = $hasConv  ? $qA("SELECT event_type, COUNT(*) AS cnt FROM conversion_events WHERE created_at >= ? GROUP BY event_type ORDER BY cnt DESC LIMIT 6", [$dateFrom.' 00:00:00']) : [];

$chartData = [];
if ($hasViews) {
    $weeks = min(12,(int)ceil($period/7));
    for ($i=$weeks-1;$i>=0;$i--) {
        $from=date('Y-m-d',strtotime("-".(($i+1)*7)." days"));
        $to  =date('Y-m-d',strtotime("-".($i*7)." days"));
        $chartData[]=['label'=>date('d/m',strtotime($to)),'views'=>$q("SELECT COUNT(*) FROM page_views WHERE created_at >= ? AND created_at < ?",[$from.' 00:00:00',$to.' 00:00:00']),'conv'=>$hasConv?$q("SELECT COUNT(*) FROM conversion_events WHERE created_at >= ? AND created_at < ?",[$from.' 00:00:00',$to.' 00:00:00']):0];
    }
}
?>
<style>
.an-back{display:inline-flex;align-items:center;gap:7px;padding:7px 14px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:12px;font-weight:600;color:var(--text-3,#6b7280);text-decoration:none;background:var(--surface,#fff);transition:all .15s;margin-bottom:18px;}
.an-back:hover{border-color:#10b981;color:#10b981;}
.an-hero{background:linear-gradient(135deg,#052e16,#14532d 60%,#052e16);border-radius:14px;padding:24px 28px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;position:relative;overflow:hidden;}
.an-hero::before{content:'';position:absolute;right:-40px;top:-40px;width:200px;height:200px;background:radial-gradient(circle,rgba(16,185,129,.2),transparent 70%);pointer-events:none;}
.an-hero-title{font-size:20px;font-weight:900;color:#fff;letter-spacing:-.02em;margin-bottom:4px;}
.an-hero-sub{font-size:12px;color:rgba(255,255,255,.5);}
.an-hero-kpis{display:flex;gap:20px;flex-wrap:wrap;}
.an-kpi{text-align:right;}
.an-kpi-val{font-size:22px;font-weight:900;line-height:1;}
.an-kpi-val.gr{background:linear-gradient(135deg,#10b981,#34d399);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.an-kpi-val.wh{color:rgba(255,255,255,.9);}
.an-kpi-val.warn{background:linear-gradient(135deg,#f59e0b,#fbbf24);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.an-kpi-lbl{font-size:10px;color:rgba(255,255,255,.45);margin-top:2px;}
.an-nav{display:flex;gap:5px;margin-bottom:20px;background:var(--surface-2,#f8fafc);border:1px solid var(--border,#e5e7eb);border-radius:11px;padding:5px;flex-wrap:wrap;}
.an-nav a{display:flex;align-items:center;gap:7px;padding:8px 14px;border-radius:7px;font-size:12px;font-weight:600;color:var(--text-3,#6b7280);text-decoration:none;transition:all .15s;flex:1;justify-content:center;min-width:100px;white-space:nowrap;}
.an-nav a:hover{background:var(--surface,#fff);}
.an-nav a.active{background:var(--surface,#fff);color:#10b981;box-shadow:0 2px 8px rgba(0,0,0,.07);}
.an-nav .ic{width:24px;height:24px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:10px;flex-shrink:0;}
.an-period{display:flex;gap:4px;margin-bottom:18px;align-items:center;flex-wrap:wrap;}
.an-period-lbl{font-size:11px;font-weight:700;color:var(--text-3,#6b7280);margin-right:6px;}
.an-period a{padding:6px 14px;border:1px solid var(--border,#e5e7eb);border-radius:8px;font-size:12px;font-weight:600;color:var(--text-3,#6b7280);text-decoration:none;background:var(--surface,#fff);transition:all .15s;}
.an-period a.active,.an-period a:hover{background:#10b981;color:#fff;border-color:#10b981;}
.an-kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px;}
.an-kpi-card{background:var(--surface,#fff);border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:18px 16px;}
.an-kpi-card-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;margin-bottom:12px;}
.an-kpi-card-val{font-size:24px;font-weight:900;color:var(--text,#111);margin-bottom:4px;}
.an-kpi-card-lbl{font-size:11px;color:var(--text-3,#6b7280);margin-bottom:6px;}
.an-growth{display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;}
.an-growth.up{background:#d1fae5;color:#059669;}
.an-growth.down{background:#fee2e2;color:#b91c1c;}
.an-chart-wrap{background:var(--surface,#fff);border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:18px;margin-bottom:16px;}
.an-chart-hd{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
.an-chart-title{font-size:13px;font-weight:800;color:var(--text,#111);}
.an-chart-legend{display:flex;gap:14px;}
.an-chart-leg-item{display:flex;align-items:center;gap:6px;font-size:11px;color:var(--text-3,#6b7280);}
.an-chart-leg-dot{width:10px;height:10px;border-radius:50%;}
.an-bar-chart{display:flex;align-items:flex-end;gap:3px;height:160px;padding-top:10px;}
.an-bar-col{display:flex;flex-direction:column;align-items:center;gap:4px;flex:1;}
.an-bar{width:100%;border-radius:4px 4px 0 0;min-height:3px;transition:all .3s;}
.an-bar.views{background:linear-gradient(180deg,#10b981,#059669);}
.an-bar.conv{background:linear-gradient(180deg,#6366f1,#4f46e5);opacity:.7;}
.an-bar-lbl{font-size:8.5px;color:var(--text-3,#6b7280);white-space:nowrap;transform:rotate(-40deg);margin-top:4px;}
.an-bar-val{font-size:8.5px;font-weight:700;color:var(--text-3,#6b7280);}
.an-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
.an-panel{background:var(--surface,#fff);border:1px solid var(--border,#e5e7eb);border-radius:12px;overflow:hidden;}
.an-panel-hd{padding:13px 16px;background:var(--surface-2,#f8fafc);border-bottom:1px solid var(--border,#e5e7eb);font-size:12px;font-weight:800;color:var(--text,#111);display:flex;justify-content:space-between;align-items:center;}
.an-panel-hd-sub{font-size:10.5px;color:var(--text-3,#6b7280);font-weight:400;}
.an-panel-row{display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid var(--border,#e5e7eb);font-size:12px;}
.an-panel-row:last-child{border-bottom:0;}
.an-panel-row:hover{background:rgba(16,185,129,.03);}
.an-panel-rank{width:22px;height:22px;border-radius:6px;background:var(--surface-2,#f8fafc);display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800;color:var(--text-3,#6b7280);flex-shrink:0;}
.an-panel-rank.top{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;}
.an-panel-name{flex:1;font-weight:600;color:var(--text,#111);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.an-panel-val{font-size:12px;font-weight:800;color:var(--text,#111);}
.an-panel-bar{height:4px;background:var(--border,#e5e7eb);border-radius:2px;margin-top:3px;overflow:hidden;}
.an-panel-bar-fill{height:100%;border-radius:2px;background:linear-gradient(90deg,#10b981,#059669);}
.an-dev-list{display:flex;flex-direction:column;gap:10px;padding:18px;}
.an-dev-item{display:flex;align-items:center;gap:10px;font-size:12.5px;}
.an-dev-dot{width:12px;height:12px;border-radius:50%;flex-shrink:0;}
.an-dev-name{flex:1;color:var(--text-3,#6b7280);}
.an-dev-pct{font-weight:900;color:var(--text,#111);}
.an-dev-bar{flex:2;height:6px;background:var(--border,#e5e7eb);border-radius:3px;overflow:hidden;}
.an-dev-bar-fill{height:100%;border-radius:3px;}
.an-conv-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:10px;padding:14px;}
.an-conv-item{background:var(--surface-2,#f8fafc);border-radius:9px;padding:12px;text-align:center;}
.an-conv-type{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--text-3,#6b7280);margin-bottom:5px;}
.an-conv-val{font-size:20px;font-weight:900;color:var(--text,#111);}
.an-empty{text-align:center;padding:32px;color:var(--text-3,#6b7280);font-size:12px;}
.an-empty i{font-size:32px;opacity:.15;display:block;margin-bottom:10px;}
.an-setup{background:linear-gradient(135deg,rgba(16,185,129,.07),rgba(5,150,105,.03));border:2px dashed rgba(16,185,129,.3);border-radius:14px;padding:28px;text-align:center;margin-bottom:20px;}
@media(max-width:768px){.an-kpis{grid-template-columns:repeat(2,1fr);}.an-row{grid-template-columns:1fr;}.an-bar-lbl{display:none;}}
</style>

<div class="anim">
<a href="?page=seo" class="an-back"><i class="fas fa-arrow-left"></i> Référencement SEO</a>

<div class="an-hero">
    <div>
        <div style="font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#6ee7b7;margin-bottom:6px;"><i class="fas fa-seedling"></i> &nbsp;SEO / Analytics</div>
        <div class="an-hero-title">Statistiques & Analytics</div>
        <div class="an-hero-sub"><?=(int)$period?> derniers jours · <?=number_format($pvTotal)?> vues · <?=$convTotal?> conversions</div>
    </div>
    <div class="an-hero-kpis">
        <div class="an-kpi"><div class="an-kpi-val gr"><?=number_format($pvTotal)?></div><div class="an-kpi-lbl">Pages vues</div></div>
        <div class="an-kpi"><div class="an-kpi-val <?=$convRate>2?'gr':'warn'?>"><?=$convRate?>%</div><div class="an-kpi-lbl">Taux conversion</div></div>
        <div class="an-kpi"><div class="an-kpi-val wh"><?=$convTotal?></div><div class="an-kpi-lbl">Conversions</div></div>
        <?php if($avgDur>0):?><div class="an-kpi"><div class="an-kpi-val wh"><?=gmdate('i:s',$avgDur)?></div><div class="an-kpi-lbl">Durée moy.</div></div><?php endif;?>
    </div>
</div>

<div class="an-nav">
    <a href="?page=seo"><div class="ic" style="background:#65a30d1a;color:#65a30d;"><i class="fas fa-th-large"></i></div>Vue d'ensemble</a>
    <a href="?page=seo-pages"><div class="ic" style="background:#6366f11a;color:#6366f1;"><i class="fas fa-file-lines"></i></div>SEO Pages</a>
    <a href="?page=seo-semantic"><div class="ic" style="background:#8b5cf61a;color:#8b5cf6;"><i class="fas fa-brain"></i></div>Sémantique</a>
    <a href="?page=local-seo"><div class="ic" style="background:#0891b21a;color:#0891b2;"><i class="fas fa-location-dot"></i></div>SEO Local & GMB</a>
    <a href="?page=analytics" class="active"><div class="ic" style="background:#10b9811a;color:#10b981;"><i class="fas fa-chart-line"></i></div>Analytics</a>
</div>

<div class="an-period">
    <span class="an-period-lbl"><i class="fas fa-calendar"></i> Période :</span>
    <?php foreach(['7'=>'7 jours','30'=>'30 jours','90'=>'90 jours','365'=>'1 an'] as $p=>$l):?>
    <a href="?page=analytics&period=<?=$p?>" class="<?=$period==$p?'active':''?>"><?=$l?></a>
    <?php endforeach;?>
</div>

<?php if(!$hasViews&&!$hasConv):?>
<div class="an-setup">
    <h3 style="font-size:16px;font-weight:900;margin-bottom:8px;">📊 Tables créées — tracking à configurer</h3>
    <p style="font-size:13px;color:var(--text-3,#6b7280);margin-bottom:14px;">Intégrez le tracking sur votre site pour voir apparaître les données.</p>
    <code style="display:block;font-size:12px;text-align:left;max-width:480px;margin:0 auto;background:var(--surface,#fff);padding:14px;border-radius:8px;line-height:1.8;color:#10b981;">&lt;script&gt;fetch('/track.php',{method:'POST',body:JSON.stringify({url:location.pathname,ref:document.referrer})});&lt;/script&gt;</code>
</div>
<?php endif;?>

<div class="an-kpis">
<?php
$kpis=[
    ['fa-eye','linear-gradient(135deg,#10b981,#059669)',number_format($pvTotal),'Pages vues',$pvGrowth,$pvGrowth!==null?($pvGrowth>=0?'up':'down'):''],
    ['fa-bullseye','linear-gradient(135deg,#6366f1,#4f46e5)',number_format($convTotal),'Conversions',$convGrowth,$convGrowth!==null?($convGrowth>=0?'up':'down'):''],
    ['fa-percent','linear-gradient(135deg,#0891b2,#0e7490)',$convRate.'%','Taux de conv.',null,''],
    ['fa-mobile-alt','linear-gradient(135deg,#f59e0b,#d97706)',$totalDev>1?round($mobileV/$totalDev*100).'%':'—','Mobile',null,''],
    ['fa-clock','linear-gradient(135deg,#8b5cf6,#7c3aed)',$avgDur>0?gmdate('i:s',$avgDur):'—','Durée moy.',null,''],
    ['fa-file-lines','linear-gradient(135deg,#10b981,#065f46)',count($topPages),'Pages actives',null,''],
];
foreach($kpis as [$ic,$gr,$val,$lbl,$growth,$gc]):?>
<div class="an-kpi-card">
    <div class="an-kpi-card-icon" style="background:<?=$gr?>;"><i class="fas <?=$ic?>"></i></div>
    <div class="an-kpi-card-val"><?=$val?></div>
    <div class="an-kpi-card-lbl"><?=$lbl?></div>
    <?php if($growth!==null):?><span class="an-growth <?=$gc?>"><i class="fas fa-<?=$gc==='up'?'arrow-up':'arrow-down'?>"></i> <?=abs($growth)?>%</span><?php endif;?>
</div>
<?php endforeach;?>
</div>

<?php if(!empty($chartData)):
$maxV=max(1,max(array_column($chartData,'views')));
$maxC=$hasConv?max(1,max(array_map(fn($d)=>$d['conv']??0,$chartData))):1;
?>
<div class="an-chart-wrap">
    <div class="an-chart-hd">
        <div class="an-chart-title"><i class="fas fa-chart-area" style="color:#10b981;"></i> Évolution du trafic</div>
        <div class="an-chart-legend">
            <div class="an-chart-leg-item"><div class="an-chart-leg-dot" style="background:#10b981;"></div>Vues</div>
            <?php if($hasConv):?><div class="an-chart-leg-item"><div class="an-chart-leg-dot" style="background:#6366f1;"></div>Conv.</div><?php endif;?>
        </div>
    </div>
    <div class="an-bar-chart">
        <?php foreach($chartData as $d):
            $hV=round(($d['views']/$maxV)*140);
            $hC=isset($d['conv'])?round(($d['conv']/$maxC)*140):0;
        ?>
        <div class="an-bar-col">
            <span class="an-bar-val"><?=$d['views']?></span>
            <div style="display:flex;gap:2px;align-items:flex-end;height:140px;width:100%;">
                <div class="an-bar views" style="height:<?=$hV?>px;flex:2;" title="<?=$d['views']?> vues"></div>
                <?php if($hasConv&&isset($d['conv'])):?><div class="an-bar conv" style="height:<?=$hC?>px;flex:1;" title="<?=$d['conv']?> conv."></div><?php endif;?>
            </div>
            <div class="an-bar-lbl"><?=$d['label']?></div>
        </div>
        <?php endforeach;?>
    </div>
</div>
<?php endif;?>

<div class="an-row">
    <div class="an-panel">
        <div class="an-panel-hd"><span><i class="fas fa-fire" style="color:#f59e0b;"></i> Pages populaires</span><span class="an-panel-hd-sub"><?=(int)$period?> jours</span></div>
        <?php if(!empty($topPages)):
            $mx=max(1,max(array_column($topPages,'views')));
            foreach($topPages as $i=>$pg):
                $url=$pg['page_url']??'—';
                $pct=round($pg['views']/$mx*100);
        ?>
        <div class="an-panel-row">
            <div class="an-panel-rank <?=$i<3?'top':''?>"><?=$i+1?></div>
            <div style="flex:1;min-width:0;">
                <div class="an-panel-name" title="<?=htmlspecialchars($url)?>"><?=htmlspecialchars(mb_substr($url,-32))?></div>
                <div class="an-panel-bar"><div class="an-panel-bar-fill" style="width:<?=$pct?>%;"></div></div>
            </div>
            <div class="an-panel-val"><?=number_format($pg['views'])?></div>
        </div>
        <?php endforeach;else:?><div class="an-empty"><i class="fas fa-file"></i>Aucune donnée</div><?php endif;?>
    </div>

    <div class="an-panel">
        <div class="an-panel-hd"><span><i class="fas fa-share-alt" style="color:#6366f1;"></i> Sources de trafic</span></div>
        <?php if(!empty($topSources)):
            $mx=max(1,max(array_column($topSources,'visits')));
            $srcIcons=['google'=>'fab fa-google','facebook'=>'fab fa-facebook','direct'=>'fas fa-arrow-right','instagram'=>'fab fa-instagram'];
            foreach($topSources as $i=>$src):
                $name=$src['source']??'Direct';
                $icon=''; foreach($srcIcons as $k=>$v){ if(stripos($name,$k)!==false){ $icon="<i class='{$v}'></i> "; break; } }
                $pct=round($src['visits']/$mx*100);
        ?>
        <div class="an-panel-row">
            <div class="an-panel-rank <?=$i<3?'top':''?>"><?=$i+1?></div>
            <div style="flex:1;min-width:0;">
                <div class="an-panel-name"><?=$icon?><?=htmlspecialchars($name)?></div>
                <div class="an-panel-bar"><div class="an-panel-bar-fill" style="width:<?=$pct?>%;background:linear-gradient(90deg,#6366f1,#4f46e5);"></div></div>
            </div>
            <div class="an-panel-val"><?=number_format($src['visits'])?></div>
        </div>
        <?php endforeach;else:?><div class="an-empty"><i class="fas fa-share-alt"></i>Aucune donnée</div><?php endif;?>
    </div>
</div>

<div class="an-row">
    <div class="an-panel">
        <div class="an-panel-hd"><span><i class="fas fa-mobile-alt" style="color:#0891b2;"></i> Appareils</span></div>
        <?php if($hasDevice&&$totalDev>1):
            $devs=[['Desktop',$desktopV,'#6366f1'],['Mobile',$mobileV,'#10b981'],['Tablet',$tabletV,'#f59e0b']];
        ?>
        <div class="an-dev-list">
            <?php foreach($devs as [$dn,$dv,$dc]): $dp=$totalDev>0?round($dv/$totalDev*100):0;?>
            <div class="an-dev-item">
                <div class="an-dev-dot" style="background:<?=$dc?>;"></div>
                <span class="an-dev-name"><?=$dn?></span>
                <div class="an-dev-bar"><div class="an-dev-bar-fill" style="width:<?=$dp?>%;background:<?=$dc?>;"></div></div>
                <span class="an-dev-pct"><?=$dp?>%</span>
            </div>
            <?php endforeach;?>
        </div>
        <?php else:?><div class="an-empty"><i class="fas fa-mobile-alt"></i>Données devices non disponibles</div><?php endif;?>
    </div>

    <div class="an-panel">
        <div class="an-panel-hd"><span><i class="fas fa-bullseye" style="color:#6366f1;"></i> Conversions</span><span class="an-panel-hd-sub"><?=$convRate?>% taux</span></div>
        <?php if(!empty($topConv)):?>
        <div class="an-conv-grid">
            <?php foreach($topConv as $ct):?>
            <div class="an-conv-item"><div class="an-conv-type"><?=htmlspecialchars($ct['event_type']??'—')?></div><div class="an-conv-val"><?=number_format($ct['cnt'])?></div></div>
            <?php endforeach;?>
        </div>
        <?php else:?><div class="an-empty"><i class="fas fa-bullseye"></i>Aucune conversion trackée</div><?php endif;?>
    </div>
</div>

</div>