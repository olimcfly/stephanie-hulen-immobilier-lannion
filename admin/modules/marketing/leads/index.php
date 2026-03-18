<?php
/**
 * MODULE LEADS — Liste unifiée v4
 * /admin/modules/marketing/leads/index.php
 * Design aligné sur pages/index.php v1.0
 */

// ── DB bootstrap ─────────────────────────────────────────────────────────────
if (!isset($pdo)) {
    if (isset($db)) {
        $pdo = $db;
    } else {
        try {
            $pdo = new PDO(
                'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
            );
        } catch (PDOException $e) {
            die('<p style="color:red;padding:20px">DB error: '.$e->getMessage().'</p>');
        }
    }
}

// ── AJAX — doit sortir AVANT tout HTML ────────────────────────────────────────
if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])==='xmlhttprequest' && isset($_GET['action']))) {
    if (ob_get_level()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {

        case 'get_lead':
            $id  = (int)($_POST['id'] ?? 0);
            $tbl = preg_replace('/[^a-z_]/', '', $_POST['tbl'] ?? 'leads');
            try {
                $s = $pdo->prepare("SELECT * FROM `$tbl` WHERE id = ?");
                $s->execute([$id]);
                $row = $s->fetch();
                echo json_encode(['success' => (bool)$row, 'lead' => $row ?: null]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'add_lead':
            $fn = trim($_POST['firstname'] ?? '');
            $ln = trim($_POST['lastname']  ?? '');
            if (!$fn && !$ln) { echo json_encode(['success'=>false,'error'=>'Prénom ou nom requis']); exit; }
            try {
                $pdo->prepare("INSERT INTO leads
                    (firstname,lastname,email,phone,city,source,notes,status,temperature,next_action,next_action_date,created_at,updated_at)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())")
                ->execute([
                    $fn, $ln,
                    trim($_POST['email'] ?? '') ?: null,
                    trim($_POST['phone'] ?? '') ?: null,
                    trim($_POST['city']  ?? '') ?: null,
                    $_POST['source']      ?? 'manuel',
                    trim($_POST['notes'] ?? '') ?: null,
                    $_POST['status']      ?? 'new',
                    $_POST['temperature'] ?? 'warm',
                    trim($_POST['next_action']      ?? '') ?: null,
                    trim($_POST['next_action_date'] ?? '') ?: null,
                ]);
                echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'update_lead':
            $id  = (int)($_POST['id']  ?? 0);
            $tbl = preg_replace('/[^a-z_]/', '', $_POST['tbl'] ?? 'leads');
            if (!$id) { echo json_encode(['success'=>false,'error'=>'ID manquant']); exit; }
            try {
                switch ($tbl) {
                    case 'leads':
                        $pdo->prepare("UPDATE leads SET
                            firstname=?,lastname=?,email=?,phone=?,city=?,source=?,notes=?,
                            status=?,temperature=?,next_action=?,next_action_date=?,updated_at=NOW()
                            WHERE id=?")
                        ->execute([
                            trim($_POST['firstname']??''), trim($_POST['lastname']??''),
                            trim($_POST['email']??'')           ?: null,
                            trim($_POST['phone']??'')           ?: null,
                            trim($_POST['city'] ??'')           ?: null,
                            $_POST['source']      ?? 'manuel',
                            trim($_POST['notes']??'')           ?: null,
                            $_POST['status']      ?? 'new',
                            $_POST['temperature'] ?? 'warm',
                            trim($_POST['next_action']     ??'') ?: null,
                            trim($_POST['next_action_date']??'') ?: null,
                            $id,
                        ]);
                        break;
                    case 'capture_leads':
                        $pdo->prepare("UPDATE capture_leads SET prenom=?,nom=?,email=?,tel=? WHERE id=?")
                            ->execute([trim($_POST['firstname']??''),trim($_POST['lastname']??''),trim($_POST['email']??''),trim($_POST['phone']??''),$id]);
                        break;
                    case 'demandes_estimation':
                        $pdo->prepare("UPDATE demandes_estimation SET email=?,telephone=?,statut=? WHERE id=?")
                            ->execute([trim($_POST['email']??''),trim($_POST['phone']??''),$_POST['status']??'nouveau',$id]);
                        break;
                    case 'contacts':
                        $pdo->prepare("UPDATE contacts SET firstname=?,lastname=?,email=?,phone=?,city=?,notes=?,status=?,updated_at=NOW() WHERE id=?")
                            ->execute([trim($_POST['firstname']??''),trim($_POST['lastname']??''),trim($_POST['email']??''),trim($_POST['phone']??''),trim($_POST['city']??''),trim($_POST['notes']??''),$_POST['status']??'actif',$id]);
                        break;
                    case 'financement_leads':
                        $pdo->prepare("UPDATE financement_leads SET prenom=?,nom=?,email=?,telephone=?,statut=?,notes=?,updated_at=NOW() WHERE id=?")
                            ->execute([trim($_POST['firstname']??''),trim($_POST['lastname']??''),trim($_POST['email']??''),trim($_POST['phone']??''),$_POST['status']??'nouveau',trim($_POST['notes']??''),$id]);
                        break;
                }
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'delete_lead':
            $id  = (int)($_POST['id'] ?? 0);
            $tbl = preg_replace('/[^a-z_]/', '', $_POST['tbl'] ?? 'leads');
            try {
                $pdo->prepare("DELETE FROM `$tbl` WHERE id=?")->execute([$id]);
                if ($tbl === 'leads') {
                    try { $pdo->prepare("DELETE FROM lead_interactions WHERE lead_id=?")->execute([$id]); } catch(Exception $e){}
                }
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'get_interactions':
            $lid = (int)($_POST['lead_id'] ?? 0);
            try {
                $s = $pdo->prepare("SELECT * FROM lead_interactions WHERE lead_id=? ORDER BY COALESCE(interaction_date,created_at) DESC");
                $s->execute([$lid]);
                echo json_encode(['success'=>true,'interactions'=>$s->fetchAll()]);
            } catch (Exception $e) {
                echo json_encode(['success'=>true,'interactions'=>[]]);
            }
            exit;

        case 'add_interaction':
            $lid  = (int)($_POST['lead_id'] ?? 0);
            $type = in_array($_POST['type']??'',['note','appel','email','rdv','sms','visite']) ? $_POST['type'] : 'note';
            try {
                $pdo->prepare("INSERT INTO lead_interactions (lead_id,type,subject,content,interaction_date,duration_minutes,outcome) VALUES (?,?,?,?,?,?,?)")
                    ->execute([$lid,$type,trim($_POST['subject']??'')?:null,trim($_POST['content']??'')?:null,trim($_POST['interaction_date']??'')?:null,(int)($_POST['duration_minutes']??0)?:null,$_POST['outcome']??null]);
                try { $pdo->prepare("UPDATE leads SET updated_at=NOW() WHERE id=?")->execute([$lid]); } catch(Exception $e){}
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'export':
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="leads-'.date('Y-m-d').'.csv"');
            $out = fopen('php://output','w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Source','Prénom','Nom','Email','Téléphone','Ville','Statut','Date'], ';');
            foreach (getAllLeads($pdo,'','','created_at','DESC','',0,99999)['rows'] as $r)
                fputcsv($out, [$r['_src_label'],$r['_fn'],$r['_ln'],$r['_email']??'',$r['_phone']??'',$r['_city']??'',$r['_status']??'',date('d/m/Y H:i',strtotime($r['created_at']))], ';');
            fclose($out);
            exit;

        default:
            echo json_encode(['success'=>false,'error'=>'Action inconnue: '.$action]);
            exit;
    }
}

// ══════════════════════════════════════════════════════════════════════════════
// FONCTION UNIFIÉE — toutes sources
// ══════════════════════════════════════════════════════════════════════════════
function getAllLeads(PDO $pdo, string $search, string $srcFilter, string $sort, string $order, string $statusFlt, int $offset, int $limit): array {
    $rows = [];

    if (!$srcFilter || in_array($srcFilter,['Manuel','Site web','GMB','Facebook','Google','Téléphone','Recommandation','Flyer','Boîtage','Salon'])) {
        try {
            $w=['1=1'];$p=[];
            if ($search) { $t="%$search%"; $w[]="(firstname LIKE ? OR lastname LIKE ? OR full_name LIKE ? OR email LIKE ? OR phone LIKE ?)"; $p=[$t,$t,$t,$t,$t]; }
            if ($statusFlt) { $w[]="status=?"; $p[]=$statusFlt; }
            $s=$pdo->prepare("SELECT *,'leads' AS _tbl FROM leads WHERE ".implode(' AND ',$w)." ORDER BY created_at DESC");
            $s->execute($p);
            $srcMap=['site_web'=>'Site web','gmb'=>'GMB','pub_facebook'=>'Facebook','pub_google'=>'Google','recommandation'=>'Recommandation','telephone'=>'Téléphone','flyer'=>'Flyer','boitage'=>'Boîtage','salon'=>'Salon','estimation'=>'Estimation','capture'=>'Capture','financement'=>'Financement','manuel'=>'Manuel','autre'=>'Autre'];
            foreach ($s->fetchAll() as $r) {
                $r['_fn']  = trim($r['firstname'] ?? '');
                $r['_ln']  = trim($r['lastname']  ?? '');
                if (!$r['_fn'] && !$r['_ln'] && !empty($r['full_name'])) {
                    $pts = explode(' ', trim($r['full_name']), 2);
                    $r['_fn'] = $pts[0]; $r['_ln'] = $pts[1] ?? '';
                }
                $r['_email']=$r['email']??null; $r['_phone']=$r['phone']??null; $r['_city']=$r['city']??null;
                $r['_status']=$r['status']??''; $r['_score']=(int)($r['score']??0);
                $src = $r['source'] ?? 'manuel';
                $r['_src_label'] = $srcMap[$src] ?? ucfirst($src);
                $r['_src_key']   = 'leads';
                if ($srcFilter && $r['_src_label'] !== $srcFilter) continue;
                $rows[] = $r;
            }
        } catch (Exception $e) {}
    }

    if (!$srcFilter || $srcFilter === 'Capture') {
        try {
            $w=['1=1'];$p=[];
            if ($search) { $t="%$search%"; $w[]="(prenom LIKE ? OR nom LIKE ? OR email LIKE ? OR tel LIKE ?)"; $p=[$t,$t,$t,$t]; }
            $s=$pdo->prepare("SELECT *,'capture_leads' AS _tbl FROM capture_leads WHERE ".implode(' AND ',$w)." ORDER BY created_at DESC");
            $s->execute($p);
            foreach ($s->fetchAll() as $r) {
                $r['_fn']=$r['prenom']??''; $r['_ln']=$r['nom']??''; $r['_email']=$r['email']??null; $r['_phone']=$r['tel']??null;
                $r['_city']=null; $r['_status']=$r['injected_crm']?'contacté':'nouveau'; $r['_score']=0;
                $r['_src_label']='Capture'; $r['_src_key']='capture_leads';
                $r['notes']=$r['message']??null;
                $rows[]=$r;
            }
        } catch (Exception $e) {}
    }

    if (!$srcFilter || $srcFilter === 'Estimation') {
        try {
            $w=['1=1'];$p=[];
            if ($search) { $t="%$search%"; $w[]="(email LIKE ? OR telephone LIKE ? OR ville LIKE ?)"; $p=[$t,$t,$t]; }
            $s=$pdo->prepare("SELECT *,'demandes_estimation' AS _tbl FROM demandes_estimation WHERE ".implode(' AND ',$w)." ORDER BY created_at DESC");
            $s->execute($p);
            foreach ($s->fetchAll() as $r) {
                $r['_fn']=''; $r['_ln']=trim(($r['type_bien']??'Bien').' '.($r['ville']??''));
                $r['_email']=$r['email']??null; $r['_phone']=$r['telephone']??null; $r['_city']=$r['ville']??null;
                $r['_status']=$r['statut']??'nouveau'; $r['_score']=0;
                $r['_src_label']='Estimation'; $r['_src_key']='demandes_estimation';
                $parts=array_filter([$r['type_bien']??'', $r['surface']?($r['surface'].'m²'):'', $r['estimation_moyenne']?('~'.number_format($r['estimation_moyenne'],0,',',' ').'€'):'']);
                $r['notes']=implode(' — ',$parts);
                $rows[]=$r;
            }
        } catch (Exception $e) {}
    }

    if (!$srcFilter || $srcFilter === 'Contact') {
        try {
            $w=['1=1'];$p=[];
            if ($search) { $t="%$search%"; $w[]="(firstname LIKE ? OR lastname LIKE ? OR nom LIKE ? OR prenom LIKE ? OR email LIKE ? OR phone LIKE ?)"; $p=[$t,$t,$t,$t,$t,$t]; }
            $s=$pdo->prepare("SELECT *,'contacts' AS _tbl FROM contacts WHERE ".implode(' AND ',$w)." ORDER BY created_at DESC");
            $s->execute($p);
            foreach ($s->fetchAll() as $r) {
                $r['_fn']=$r['firstname']??$r['prenom']??''; $r['_ln']=$r['lastname']??$r['nom']??'';
                $r['_email']=$r['email']??null; $r['_phone']=$r['phone']??$r['telephone']??null; $r['_city']=$r['city']??null;
                $r['_status']=$r['status']??'actif'; $r['_score']=(int)($r['rating']??0);
                $r['_src_label']='Contact'; $r['_src_key']='contacts';
                $rows[]=$r;
            }
        } catch (Exception $e) {}
    }

    if (!$srcFilter || $srcFilter === 'Financement') {
        try {
            $w=['1=1'];$p=[];
            if ($search) { $t="%$search%"; $w[]="(prenom LIKE ? OR nom LIKE ? OR email LIKE ? OR telephone LIKE ?)"; $p=[$t,$t,$t,$t]; }
            $s=$pdo->prepare("SELECT *,'financement_leads' AS _tbl FROM financement_leads WHERE ".implode(' AND ',$w)." ORDER BY created_at DESC");
            $s->execute($p);
            foreach ($s->fetchAll() as $r) {
                $r['_fn']=$r['prenom']??''; $r['_ln']=$r['nom']??''; $r['_email']=$r['email']??null; $r['_phone']=$r['telephone']??null;
                $r['_city']=null; $r['_status']=$r['statut']??'nouveau'; $r['_score']=0;
                $r['_src_label']='Financement'; $r['_src_key']='financement_leads';
                $r['notes']=trim(($r['type_projet']??'Projet').($r['montant_projet']?' — '.number_format($r['montant_projet'],0,',',' ').'€':'').($r['notes']?' | '.$r['notes']:''));
                $rows[]=$r;
            }
        } catch (Exception $e) {}
    }

    // Dédoublonnage email
    $seen=[]; $deduped=[];
    foreach ($rows as $r) {
        $key = strtolower(trim($r['_email'] ?? ''));
        if ($key && isset($seen[$key])) continue;
        if ($key) $seen[$key]=true;
        $deduped[]=$r;
    }

    usort($deduped, function($a,$b) use ($sort,$order) {
        $va = match($sort) { '_fn'=>strtolower($a['_fn'].$a['_ln']), '_email'=>strtolower($a['_email']??''), '_score'=>(int)$a['_score'], default=>$a['created_at']??'' };
        $vb = match($sort) { '_fn'=>strtolower($b['_fn'].$b['_ln']), '_email'=>strtolower($b['_email']??''), '_score'=>(int)$b['_score'], default=>$b['created_at']??'' };
        $cmp = is_int($va) ? ($va<=>$vb) : strcmp((string)$va,(string)$vb);
        return $order==='DESC' ? -$cmp : $cmp;
    });

    $total = count($deduped);
    return ['rows' => array_slice($deduped,$offset,$limit), 'total' => $total];
}

// ── Paramètres page ───────────────────────────────────────────────────────────
$search    = trim($_GET['search'] ?? '');
$srcFilter = $_GET['src']    ?? '';
$statusFlt = $_GET['status'] ?? '';
$sortBy    = in_array($_GET['sort']??'',['created_at','_fn','_email','_score']) ? $_GET['sort'] : 'created_at';
$sortOrder = ($_GET['order']??'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page      = max(1, (int)($_GET['p'] ?? 1));
$perPage   = 25;
$offset    = ($page-1)*$perPage;

$result     = getAllLeads($pdo, $search, $srcFilter, $sortBy, $sortOrder, $statusFlt, $offset, $perPage);
$leads      = $result['rows'];
$totalLeads = $result['total'];
$totalPages = max(1, ceil($totalLeads/$perPage));

// Stats globales
$statsAll = getAllLeads($pdo,'','','created_at','DESC','',0,99999)['rows'];
$sTotal   = count($statsAll);
$sMonth   = count(array_filter($statsAll, fn($r)=>substr($r['created_at'],0,7)===date('Y-m')));
$sEstim   = count(array_filter($statsAll, fn($r)=>($r['_src_label']??'')==='Estimation'));
$sCapture = count(array_filter($statsAll, fn($r)=>($r['_src_label']??'')==='Capture'));
$sLeads   = count(array_filter($statsAll, fn($r)=>($r['_src_key']??'')==='leads'));
$sNew     = count(array_filter($statsAll, fn($r)=>in_array($r['_status']??'',['new','nouveau'])));

$pqs = http_build_query(array_filter(['search'=>$search,'src'=>$srcFilter,'status'=>$statusFlt,'sort'=>$sortBy,'order'=>$sortOrder]));
function lvSort($col,$cs,$co,$qs){ $o=($cs===$col&&$co==='DESC')?'ASC':'DESC'; return '?page=leads&sort='.$col.'&order='.$o.($qs?'&'.$qs:''); }

$statusLabels=[
    'new'         =>['label'=>'Nouveau',     'bg'=>'#e0e7ff','c'=>'#4f46e5'],
    'contacted'   =>['label'=>'Contacté',    'bg'=>'#cffafe','c'=>'#0e7490'],
    'qualified'   =>['label'=>'Qualifié',    'bg'=>'#ede9fe','c'=>'#7c3aed'],
    'proposal'    =>['label'=>'Proposition', 'bg'=>'#fef3c7','c'=>'#b45309'],
    'negotiation' =>['label'=>'Négociation', 'bg'=>'#fce7f3','c'=>'#be185d'],
    'won'         =>['label'=>'Gagné',       'bg'=>'#d1fae5','c'=>'#065f46'],
    'lost'        =>['label'=>'Perdu',       'bg'=>'#f1f5f9','c'=>'#64748b'],
    'nouveau'     =>['label'=>'Nouveau',     'bg'=>'#e0e7ff','c'=>'#4f46e5'],
    'contacté'    =>['label'=>'Contacté',    'bg'=>'#cffafe','c'=>'#0e7490'],
    'actif'       =>['label'=>'Actif',       'bg'=>'#d1fae5','c'=>'#065f46'],
    'traité'      =>['label'=>'Traité',      'bg'=>'#f0fdf4','c'=>'#15803d'],
    'transmis'    =>['label'=>'Transmis',    'bg'=>'#fef3c7','c'=>'#b45309'],
    'converti'    =>['label'=>'Converti',    'bg'=>'#d1fae5','c'=>'#065f46'],
];
$srcStyles=[
    'Manuel'        =>['bg'=>'#f1f5f9','c'=>'#475569','icon'=>'user-edit'],
    'Capture'       =>['bg'=>'#ede9fe','c'=>'#7c3aed','icon'=>'magnet'],
    'Estimation'    =>['bg'=>'#fef3c7','c'=>'#b45309','icon'=>'chart-bar'],
    'Contact'       =>['bg'=>'#dbeafe','c'=>'#1d4ed8','icon'=>'address-book'],
    'Financement'   =>['bg'=>'#fce7f3','c'=>'#be185d','icon'=>'hand-holding-usd'],
    'Site web'      =>['bg'=>'#dbeafe','c'=>'#1d4ed8','icon'=>'globe'],
    'GMB'           =>['bg'=>'#d1fae5','c'=>'#065f46','icon'=>'map-marker-alt'],
    'Facebook'      =>['bg'=>'#eff6ff','c'=>'#2563eb','icon'=>'facebook-f'],
    'Google'        =>['bg'=>'#fce7f3','c'=>'#be185d','icon'=>'google'],
    'Téléphone'     =>['bg'=>'#ecfdf5','c'=>'#059669','icon'=>'phone'],
    'Recommandation'=>['bg'=>'#fff7ed','c'=>'#c2410c','icon'=>'heart'],
    'Boîtage'       =>['bg'=>'#f0fdf4','c'=>'#15803d','icon'=>'home'],
    'Flyer'         =>['bg'=>'#fefce8','c'=>'#a16207','icon'=>'file-alt'],
    'Salon'         =>['bg'=>'#fdf2f8','c'=>'#9d174d','icon'=>'calendar-alt'],
];
$tempLabels=['cold'=>['label'=>'Froid','c'=>'#0369a1','bg'=>'#e0f2fe','icon'=>'snowflake'],'warm'=>['label'=>'Tiède','c'=>'#b45309','bg'=>'#fef3c7','icon'=>'sun'],'hot'=>['label'=>'Chaud','c'=>'#dc2626','bg'=>'#fee2e2','icon'=>'fire-alt']];

// Filtres pills — sources
$srcFilters = [
    ''            => ['label'=>'Tous',        'icon'=>'fa-users',          'count'=>$sTotal],
    'Manuel'      => ['label'=>'CRM',         'icon'=>'fa-user-edit',      'count'=>$sLeads],
    'Capture'     => ['label'=>'Captures',    'icon'=>'fa-magnet',         'count'=>$sCapture],
    'Estimation'  => ['label'=>'Estimations', 'icon'=>'fa-chart-bar',      'count'=>$sEstim],
    'Contact'     => ['label'=>'Contacts',    'icon'=>'fa-address-book',   'count'=>count(array_filter($statsAll,fn($r)=>($r['_src_label']??'')==='Contact'))],
    'Financement' => ['label'=>'Financement', 'icon'=>'fa-hand-holding-usd','count'=>count(array_filter($statsAll,fn($r)=>($r['_src_label']??'')==='Financement'))],
];
?>

<style>
/* ══════════════════════════════════════════════════════════════
   LEADS MODULE v4 — Design system aligné pages/index.php
══════════════════════════════════════════════════════════════ */
.lv-wrap { font-family: var(--font, 'Inter', sans-serif); }

/* ─── Banner ─── */
.lv-banner {
    background: var(--surface, #fff);
    border-radius: 16px; padding: 26px 30px; margin-bottom: 22px;
    display: flex; align-items: center; justify-content: space-between;
    border: 1px solid var(--border, #e5e7eb); position: relative; overflow: hidden;
}
.lv-banner::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899);
}
.lv-banner::after {
    content: ''; position: absolute; top: -40%; right: -5%;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(99,102,241,.05), transparent 70%);
    border-radius: 50%; pointer-events: none;
}
.lv-banner-left { position: relative; z-index: 1; }
.lv-banner-left h2 { font-size: 1.35rem; font-weight: 700; color: var(--text, #111827); margin: 0 0 4px; display: flex; align-items: center; gap: 10px; letter-spacing: -.02em; }
.lv-banner-left h2 i { font-size: 16px; color: #6366f1; }
.lv-banner-left p { color: var(--text-2, #6b7280); font-size: .85rem; margin: 0; }
.lv-banner-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.lv-bstat { text-align: center; padding: 10px 16px; background: var(--surface-2, #f9fafb); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); min-width: 72px; transition: all .2s; }
.lv-bstat:hover { border-color: var(--border-h, #d1d5db); box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.lv-bstat .num { font-size: 1.45rem; font-weight: 800; line-height: 1; color: var(--text, #111827); letter-spacing: -.03em; }
.lv-bstat .num.indigo { color: #6366f1; }
.lv-bstat .num.green  { color: #10b981; }
.lv-bstat .num.amber  { color: #f59e0b; }
.lv-bstat .num.rose   { color: #ec4899; }
.lv-bstat .num.violet { color: #7c3aed; }
.lv-bstat .num.teal   { color: #0d9488; }
.lv-bstat .lbl { font-size: .58rem; color: var(--text-3, #9ca3af); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }
.lv-banner-actions { display: flex; gap: 8px; flex-wrap: wrap; position: relative; z-index: 1; }

/* ─── Toolbar ─── */
.lv-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.lv-filters { display: flex; gap: 3px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; padding: 3px; flex-wrap: wrap; }
.lv-fbtn { padding: 7px 13px; border: none; background: transparent; color: var(--text-2, #6b7280); font-size: .78rem; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all .15s; font-family: inherit; display: flex; align-items: center; gap: 5px; text-decoration: none; white-space: nowrap; }
.lv-fbtn:hover { color: var(--text, #111827); background: var(--surface-2, #f9fafb); }
.lv-fbtn.active { background: #6366f1; color: #fff; box-shadow: 0 1px 4px rgba(99,102,241,.25); }
.lv-fbtn .badge { font-size: .68rem; padding: 1px 7px; border-radius: 10px; background: var(--surface-2, #f3f4f6); font-weight: 700; color: var(--text-3, #9ca3af); }
.lv-fbtn.active .badge { background: rgba(255,255,255,.22); color: #fff; }

/* ─── Sub-filtres ─── */
.lv-subfilters { display: flex; gap: 8px; margin-bottom: 14px; flex-wrap: wrap; }
.lv-subfilter { display: flex; align-items: center; gap: 5px; font-size: .75rem; color: var(--text-2, #6b7280); }
.lv-subfilter select { padding: 5px 10px; border: 1px solid var(--border, #e5e7eb); border-radius: 6px; background: var(--surface, #fff); color: var(--text, #111827); font-size: .75rem; font-family: inherit; cursor: pointer; }
.lv-subfilter select:focus { outline: none; border-color: #6366f1; }

/* ─── Toolbar right ─── */
.lv-toolbar-r { display: flex; align-items: center; gap: 10px; }
.lv-search { position: relative; }
.lv-search input { padding: 8px 12px 8px 34px; background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 10px; color: var(--text, #111827); font-size: .82rem; width: 220px; font-family: inherit; transition: all .2s; }
.lv-search input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); width: 250px; }
.lv-search input::placeholder { color: var(--text-3, #9ca3af); }
.lv-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: var(--text-3, #9ca3af); font-size: .75rem; }

/* ─── Boutons ─── */
.lv-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 10px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; font-family: inherit; text-decoration: none; line-height: 1.3; white-space: nowrap; }
.lv-btn-primary { background: #6366f1; color: #fff; box-shadow: 0 1px 4px rgba(99,102,241,.22); }
.lv-btn-primary:hover { background: #4f46e5; transform: translateY(-1px); color: #fff; }
.lv-btn-outline { background: var(--surface, #fff); color: var(--text-2, #6b7280); border: 1px solid var(--border, #e5e7eb); }
.lv-btn-outline:hover { border-color: #6366f1; color: #6366f1; }
.lv-btn-sm { padding: 6px 13px; font-size: .77rem; }
.lv-btn-ghost { background: rgba(99,102,241,.08); color: #6366f1; border: 1px solid rgba(99,102,241,.18); }
.lv-btn-ghost:hover { background: rgba(99,102,241,.14); }

/* ─── Table wrap ─── */
.lv-table-wrap { background: var(--surface, #fff); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); overflow: hidden; }

/* ─── Table ─── */
.lv-table { width: 100%; border-collapse: collapse; }
.lv-table thead th { padding: 11px 14px; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: var(--text-3, #9ca3af); background: var(--surface-2, #f9fafb); border-bottom: 1px solid var(--border, #e5e7eb); text-align: left; white-space: nowrap; }
.lv-table thead th a { color: var(--text-3, #9ca3af); text-decoration: none; }
.lv-table thead th a:hover { color: var(--text, #111827); }
.lv-table tbody tr { border-bottom: 1px solid var(--border, #f3f4f6); transition: background .1s; cursor: pointer; }
.lv-table tbody tr:hover { background: rgba(99,102,241,.02); }
.lv-table tbody tr:last-child { border-bottom: none; }
.lv-table td { padding: 11px 14px; font-size: .83rem; color: var(--text, #111827); vertical-align: middle; }

/* ─── Avatar ─── */
.lv-av { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: #fff; flex-shrink: 0; }

/* ─── Badge source ─── */
.lv-src { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 8px; font-size: .68rem; font-weight: 700; white-space: nowrap; }

/* ─── Badge statut ─── */
.lv-status { padding: 3px 10px; border-radius: 12px; font-size: .63rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; display: inline-block; }

/* ─── Actions ─── */
.lv-actions { display: flex; gap: 3px; justify-content: flex-end; }
.lv-actions a, .lv-actions button { width: 30px; height: 30px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--text-3, #9ca3af); background: transparent; border: 1px solid transparent; cursor: pointer; transition: all .12s; text-decoration: none; font-size: .78rem; font-family: inherit; }
.lv-actions a:hover, .lv-actions button:hover { color: #6366f1; border-color: var(--border, #e5e7eb); background: rgba(99,102,241,.07); }
.lv-actions button.del:hover { color: #dc2626; border-color: rgba(220,38,38,.2); background: #fef2f2; }

/* ─── Pagination ─── */
.lv-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-top: 1px solid var(--border, #e5e7eb); font-size: .78rem; color: var(--text-3, #9ca3af); }
.lv-pagination-pages { display: flex; gap: 4px; }
.lv-pagination-pages a { padding: 6px 12px; border: 1px solid var(--border, #e5e7eb); border-radius: 10px; color: var(--text-2, #6b7280); text-decoration: none; font-weight: 600; transition: all .15s; font-size: .78rem; }
.lv-pagination-pages a:hover { border-color: #6366f1; color: #6366f1; }
.lv-pagination-pages a.active { background: #6366f1; color: #fff; border-color: #6366f1; }

/* ─── Empty ─── */
.lv-empty { text-align: center; padding: 60px 20px; color: var(--text-3, #9ca3af); }
.lv-empty i { font-size: 2.5rem; opacity: .2; margin-bottom: 12px; display: block; }
.lv-empty h3 { color: var(--text-2, #6b7280); font-size: 1rem; font-weight: 600; margin-bottom: 6px; }

/* ══ SLIDE-OVER ══════════════════════════════════════════════════════════════ */
.lv-ov { position: fixed; inset: 0; background: rgba(15,23,42,.45); z-index: 1000; display: none; backdrop-filter: blur(3px); }
.lv-ov.on { display: block; }
.lv-sh { position: fixed; top: 0; right: 0; height: 100vh; width: 720px; max-width: 96vw; background: var(--surface-2, #f8fafc); z-index: 1001; box-shadow: -8px 0 40px rgba(0,0,0,.12); transform: translateX(100%); transition: transform .32s cubic-bezier(.16,1,.3,1); display: flex; flex-direction: column; }
.lv-sh.on { transform: translateX(0); }

/* Sheet header */
.lv-sh-hd { background: var(--surface, #fff); border-bottom: 1px solid var(--border, #e5e7eb); padding: 20px 22px; flex-shrink: 0; position: relative; }
.lv-sh-hd::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899); }
.lv-sh-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; margin-bottom: 12px; }
.lv-sh-av { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 17px; font-weight: 700; color: #fff; flex-shrink: 0; background: #6366f1; }
.lv-sh-name { font-size: 1.05rem; font-weight: 700; margin: 0; line-height: 1.2; color: var(--text, #111827); }
.lv-sh-sub { font-size: .77rem; color: var(--text-3, #9ca3af); margin: 3px 0 0; }
.lv-sh-x { width: 30px; height: 30px; border: 1px solid var(--border, #e5e7eb); background: var(--surface-2, #f9fafb); border-radius: 7px; color: var(--text-2, #6b7280); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all .2s; flex-shrink: 0; }
.lv-sh-x:hover { border-color: #6366f1; color: #6366f1; transform: rotate(90deg); }
.lv-sh-tags { display: flex; gap: 5px; flex-wrap: wrap; }
.lv-sh-tag { padding: 2px 9px; border-radius: 20px; font-size: .67rem; font-weight: 700; }

/* Quick actions */
.lv-qrow { display: flex; gap: 6px; padding: 10px 16px; background: var(--surface, #fff); border-bottom: 1px solid var(--border, #e5e7eb); flex-shrink: 0; }
.lv-qa { flex: 1; display: flex; align-items: center; justify-content: center; gap: 5px; padding: 8px 6px; border-radius: 8px; border: 1px solid var(--border, #e5e7eb); background: var(--surface, #fff); font-size: .74rem; font-weight: 600; cursor: pointer; transition: all .2s; color: var(--text-2, #374151); font-family: inherit; }
.lv-qa:hover { transform: translateY(-1px); box-shadow: 0 3px 8px rgba(0,0,0,.07); }
.lv-qa.q-blue   { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.lv-qa.q-green  { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
.lv-qa.q-amber  { background: #fffbeb; border-color: #fde68a; color: #b45309; }
.lv-qa.q-purple { background: #faf5ff; border-color: #e9d5ff; color: #7c3aed; }

/* Tabs */
.lv-tabs { display: flex; border-bottom: 1px solid var(--border, #e5e7eb); background: var(--surface, #fff); flex-shrink: 0; overflow-x: auto; }
.lv-tab { padding: 9px 16px; font-size: .79rem; font-weight: 500; border: none; background: none; cursor: pointer; color: var(--text-3, #6b7280); border-bottom: 2px solid transparent; transition: .15s; white-space: nowrap; display: flex; align-items: center; gap: 5px; flex-shrink: 0; font-family: inherit; }
.lv-tab.on { color: #6366f1; border-bottom-color: #6366f1; font-weight: 700; }
.lv-tab:hover:not(.on) { color: var(--text, #374151); }

.lv-sh-body { flex: 1; overflow-y: auto; padding: 18px; }

/* Info grid */
.lv-ig { display: grid; grid-template-columns: 1fr 1fr; gap: 9px; margin-bottom: 12px; }
.lv-ic { background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 8px; padding: 10px 12px; }
.lv-ic-l { font-size: .67rem; font-weight: 700; color: var(--text-3, #94a3b8); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 2px; }
.lv-ic-v { font-size: .81rem; color: var(--text, #374151); font-weight: 500; }
.lv-ic-v a { color: #6366f1; text-decoration: none; }

/* Notes */
.lv-note-bloc { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 11px 13px; margin-bottom: 10px; }
.lv-note-title { font-size: .67rem; font-weight: 700; color: #92400e; margin-bottom: 4px; }
.lv-note-txt { font-size: .79rem; color: #78350f; white-space: pre-wrap; line-height: 1.5; }

/* Next action */
.lv-na-bloc { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 11px 13px; margin-bottom: 10px; }
.lv-na-title { font-size: .67rem; font-weight: 700; color: #1e40af; margin-bottom: 3px; }
.lv-na-txt { font-size: .81rem; font-weight: 600; color: #1e3a8a; }

/* Formulaire edit */
.lv-ef { display: flex; flex-direction: column; gap: 12px; }
.lv-ef-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.lv-ef-grp { display: flex; flex-direction: column; gap: 4px; }
.lv-ef-grp label { font-size: .74rem; font-weight: 600; color: var(--text-2, #374151); }
.lv-ef-sec { font-size: .77rem; font-weight: 700; color: var(--text, #1e293b); padding: 6px 0; border-bottom: 1px solid var(--border, #e2e8f0); margin-top: 4px; display: flex; align-items: center; gap: 5px; }
.lv-ef-sec i { color: #6366f1; }
.lv-in { width: 100%; padding: 8px 11px; border: 1px solid var(--border, #e5e7eb); border-radius: 8px; font-size: .81rem; color: var(--text, #374151); outline: none; transition: .15s; font-family: inherit; background: var(--surface, #fff); }
.lv-in:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.lv-ta { resize: vertical; min-height: 72px; }

/* Timeline */
.lv-tl { position: relative; padding-left: 26px; }
.lv-tl::before { content: ''; position: absolute; left: 9px; top: 0; bottom: 0; width: 2px; background: var(--border, #e2e8f0); }
.lv-tl-item { position: relative; margin-bottom: 12px; }
.lv-tl-dot { position: absolute; left: -26px; top: 2px; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .55rem; color: #fff; }
.lv-tl-card { background: var(--surface, #fff); border: 1px solid var(--border, #e2e8f0); border-radius: 8px; padding: 10px 12px; }
.lv-tl-head { display: flex; justify-content: space-between; margin-bottom: 3px; }
.lv-tl-type { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.lv-tl-date { font-size: .67rem; color: var(--text-3, #94a3b8); }
.lv-tl-subj { font-size: .81rem; font-weight: 600; color: var(--text, #1e293b); margin-bottom: 2px; }
.lv-tl-txt { font-size: .77rem; color: var(--text-2, #475569); white-space: pre-wrap; line-height: 1.4; }
.lv-tl-empty { padding: 32px 16px; text-align: center; color: var(--text-3, #94a3b8); font-size: .8rem; }
.lv-tl-empty i { font-size: 1.6rem; display: block; margin-bottom: 7px; opacity: .2; }

/* Log form */
.lv-lf { display: flex; flex-direction: column; gap: 10px; }
.lv-type-row { display: flex; gap: 6px; flex-wrap: wrap; }
.lv-tb { padding: 6px 11px; border: 2px solid var(--border, #e5e7eb); border-radius: 7px; background: var(--surface, #fff); font-size: .75rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: .15s; color: var(--text, #374151); font-family: inherit; }
.lv-tb.on { border-color: #6366f1; background: #eff6ff; color: #4f46e5; }
.lv-flbl { font-size: .75rem; font-weight: 600; color: var(--text-2, #374151); margin-bottom: 3px; }

/* Email composer */
.lv-em-wrap { background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 11px; overflow: hidden; }
.lv-em-head { padding: 9px 14px; background: var(--surface-2, #f8fafc); border-bottom: 1px solid var(--border, #e5e7eb); font-size: .79rem; font-weight: 600; display: flex; align-items: center; gap: 6px; color: var(--text, #374151); }
.lv-em-row { display: flex; align-items: center; border-bottom: 1px solid var(--border, #f1f5f9); padding: 6px 14px; gap: 8px; }
.lv-em-row label { font-size: .74rem; color: var(--text-3, #94a3b8); min-width: 38px; font-weight: 500; }
.lv-em-row input { flex: 1; border: none; outline: none; font-size: .81rem; color: var(--text, #374151); background: none; }
.lv-em-body { min-height: 120px; padding: 11px 14px; font-size: .81rem; color: var(--text, #374151); outline: none; line-height: 1.6; }
.lv-em-footer { display: flex; justify-content: flex-end; padding: 8px 14px; border-top: 1px solid var(--border, #f1f5f9); background: var(--surface-2, #f8fafc); }

/* ═══ MODAL (aligné pages/index.php) ═══ */
#lvModal { display: none; position: fixed; inset: 0; z-index: 9999; align-items: center; justify-content: center; }
#lvModalBox { position: relative; z-index: 1; background: #fff; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,.18); width: 100%; max-width: 520px; margin: 16px; overflow: hidden; transform: scale(.94) translateY(8px); transition: transform .2s cubic-bezier(.34,1.56,.64,1), opacity .15s; opacity: 0; }
#lvModalBox.in { transform: scale(1) translateY(0); opacity: 1; }

/* ─── Media ─── */
@media (max-width: 900px) {
    .lv-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .lv-toolbar { flex-direction: column; align-items: flex-start; }
    .lv-table-wrap { overflow-x: auto; }
}
@media (max-width: 640px) {
    .lv-banner-actions { flex-direction: column; }
    .lv-bstat { min-width: 60px; padding: 8px 10px; }
    .lv-bstat .num { font-size: 1.2rem; }
}
</style>

<div class="lv-wrap">

<!-- ─── BANNER ─── -->
<div class="lv-banner">
    <div class="lv-banner-left">
        <h2><i class="fas fa-address-book"></i> Tous les contacts</h2>
        <p>Leads CRM, captures, estimations, contacts, financement — vue unifiée</p>
    </div>
    <div style="display:flex;flex-direction:column;gap:12px;align-items:flex-end;position:relative;z-index:1">
        <div class="lv-banner-stats">
            <div class="lv-bstat"><div class="num indigo"><?=$sTotal?></div><div class="lbl">Total</div></div>
            <div class="lv-bstat"><div class="num green"><?=$sLeads?></div><div class="lbl">CRM</div></div>
            <div class="lv-bstat"><div class="num violet"><?=$sCapture?></div><div class="lbl">Captures</div></div>
            <div class="lv-bstat"><div class="num amber"><?=$sEstim?></div><div class="lbl">Estim.</div></div>
            <div class="lv-bstat"><div class="num teal"><?=$sMonth?></div><div class="lbl">Ce mois</div></div>
            <div class="lv-bstat"><div class="num rose"><?=$sNew?></div><div class="lbl">Nouveaux</div></div>
        </div>
        <div class="lv-banner-actions">
            <a href="?page=leads&ajax=1&action=export" class="lv-btn lv-btn-outline lv-btn-sm"><i class="fas fa-download"></i> Export CSV</a>
            <button class="lv-btn lv-btn-primary lv-btn-sm" onclick="lvOpenModal()"><i class="fas fa-plus"></i> Nouveau lead</button>
        </div>
    </div>
</div>

<!-- ─── TOOLBAR ─── -->
<div class="lv-toolbar">
    <div class="lv-filters">
        <?php foreach($srcFilters as $key => $f):
            $active = ($srcFilter === $key) ? ' active' : '';
        ?>
        <button class="lv-fbtn<?=$active?>" onclick="lvFilterSrc('<?=addslashes($key)?>')">
            <i class="fas <?=$f['icon']?>"></i> <?=$f['label']?>
            <span class="badge"><?=(int)$f['count']?></span>
        </button>
        <?php endforeach; ?>
    </div>
    <div class="lv-toolbar-r">
        <form method="GET" style="display:contents">
            <input type="hidden" name="page" value="leads">
            <?php if($sortBy!=='created_at'):?><input type="hidden" name="sort" value="<?=htmlspecialchars($sortBy)?>"><?php endif;?>
            <?php if($sortOrder!=='DESC'):?><input type="hidden" name="order" value="<?=$sortOrder?>"><?php endif;?>
            <?php if($srcFilter):?><input type="hidden" name="src" value="<?=htmlspecialchars($srcFilter)?>"><?php endif;?>
            <div class="lv-search">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Nom, email, téléphone…" value="<?=htmlspecialchars($search)?>" onchange="this.form.submit()">
            </div>
        </form>
    </div>
</div>

<!-- ─── SUB-FILTRES ─── -->
<div class="lv-subfilters">
    <div class="lv-subfilter">
        <i class="fas fa-filter"></i>
        <select onchange="lvFilterStatus(this.value)">
            <option value="">Tous les statuts</option>
            <?php foreach($statusLabels as $k=>$v):?>
            <option value="<?=$k?>" <?=$statusFlt===$k?'selected':''?>><?=$v['label']?></option>
            <?php endforeach;?>
        </select>
    </div>
    <?php if($search||$srcFilter||$statusFlt):?>
    <div class="lv-subfilter">
        <a href="?page=leads" class="lv-btn lv-btn-outline lv-btn-sm"><i class="fas fa-times"></i> Réinitialiser</a>
    </div>
    <?php endif;?>
</div>

<!-- ─── TABLE ─── -->
<?php if(empty($leads)):?>
<div class="lv-table-wrap">
    <div class="lv-empty">
        <i class="fas fa-user-slash"></i>
        <h3>Aucun contact trouvé</h3>
        <p>Modifiez vos filtres ou <a href="#" onclick="lvOpenModal();return false">ajoutez un nouveau lead</a></p>
    </div>
</div>
<?php else:?>
<div class="lv-table-wrap">
    <table class="lv-table">
        <thead>
        <tr>
            <th><a href="<?=lvSort('_fn',$sortBy,$sortOrder,$pqs)?>">Nom <?=$sortBy==='_fn'?($sortOrder==='ASC'?'↑':'↓'):''?></a></th>
            <th>Contact</th>
            <th>Source</th>
            <th>Statut</th>
            <th>Notes</th>
            <th><a href="<?=lvSort('created_at',$sortBy,$sortOrder,$pqs)?>">Date <?=$sortBy==='created_at'?($sortOrder==='ASC'?'↑':'↓'):''?></a></th>
            <th style="text-align:right">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($leads as $l):
            $fn   = trim($l['_fn'].' '.$l['_ln']) ?: '—';
            $ini  = strtoupper(mb_substr($l['_fn'],0,1).mb_substr($l['_ln'],0,1)) ?: '?';
            $src  = $srcStyles[$l['_src_label']] ?? ['bg'=>'#f1f5f9','c'=>'#475569','icon'=>'tag'];
            $st   = $statusLabels[$l['_status']??''] ?? null;
            $avBg = match($l['_src_label']??''){'Estimation'=>'#b45309','Capture'=>'#7c3aed','Financement'=>'#be185d','Contact'=>'#1d4ed8','GMB'=>'#065f46','Facebook'=>'#2563eb',default=>'#6366f1'};
            $note = trim($l['notes']??$l['next_action']??'');
        ?>
        <tr onclick="lvSheet(<?=$l['id']?>,'<?=$l['_tbl']?>')">
            <td>
                <div style="display:flex;align-items:center;gap:9px">
                    <div class="lv-av" style="background:<?=$avBg?>"><?=htmlspecialchars($ini)?></div>
                    <div>
                        <div style="font-weight:600;color:var(--text,#111827);font-size:.83rem"><?=htmlspecialchars($fn)?></div>
                        <?php if($l['_city']??''):?><div style="font-size:.71rem;color:var(--text-3,#9ca3af)"><?=htmlspecialchars($l['_city'])?></div><?php endif;?>
                    </div>
                </div>
            </td>
            <td>
                <?php if($l['_email']??''):?><div><a href="mailto:<?=htmlspecialchars($l['_email'])?>" onclick="event.stopPropagation()" style="color:#6366f1;font-size:.79rem;text-decoration:none"><?=htmlspecialchars($l['_email'])?></a></div><?php endif;?>
                <?php if($l['_phone']??''):?><div style="font-size:.75rem;color:var(--text-3,#64748b);margin-top:1px"><i class="fas fa-phone" style="font-size:.58rem;margin-right:3px"></i><?=htmlspecialchars($l['_phone'])?></div><?php endif;?>
            </td>
            <td><span class="lv-src" style="background:<?=$src['bg']?>;color:<?=$src['c']?>"><i class="fas fa-<?=$src['icon']?>"></i> <?=htmlspecialchars($l['_src_label'])?></span></td>
            <td>
                <?php if($st):?><span class="lv-status" style="background:<?=$st['bg']?>;color:<?=$st['c']?>"><?=$st['label']?></span>
                <?php elseif($l['_status']??''):?><span class="lv-status" style="background:#f1f5f9;color:#475569"><?=htmlspecialchars($l['_status'])?></span>
                <?php else:?><span style="color:var(--text-3,#cbd5e1);font-size:.72rem">—</span><?php endif;?>
            </td>
            <td>
                <?php if($note):?>
                <div style="font-size:.75rem;color:var(--text-3,#64748b);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?=htmlspecialchars($note)?>"><?=htmlspecialchars(mb_substr($note,0,55)).(mb_strlen($note)>55?'…':'')?></div>
                <?php else:?><span style="color:var(--text-3,#cbd5e1);font-size:.72rem">—</span><?php endif;?>
            </td>
            <td>
                <div style="font-size:.76rem;color:var(--text,#374151)"><?=date('d/m/Y',strtotime($l['created_at']))?></div>
                <div style="font-size:.69rem;color:var(--text-3,#9ca3af)"><?=date('H:i',strtotime($l['created_at']))?></div>
            </td>
            <td onclick="event.stopPropagation()">
                <div class="lv-actions">
                    <button onclick="lvSheet(<?=$l['id']?>,'<?=$l['_tbl']?>')" title="Voir fiche"><i class="fas fa-eye"></i></button>
                    <?php if($l['_phone']??''):?><a href="tel:<?=htmlspecialchars($l['_phone'])?>" title="Appeler"><i class="fas fa-phone"></i></a><?php endif;?>
                    <button class="del" onclick="lvDelete(<?=$l['id']?>,'<?=$l['_tbl']?>')" title="Supprimer"><i class="fas fa-trash"></i></button>
                </div>
            </td>
        </tr>
        <?php endforeach;?>
        </tbody>
    </table>

    <div class="lv-pagination">
        <span><?=min($offset+1,$totalLeads)?>–<?=min($offset+$perPage,$totalLeads)?> sur <strong><?=$totalLeads?></strong> contacts</span>
        <div class="lv-pagination-pages">
            <?php if($page>1):?>
                <a href="?page=leads&p=1&<?=$pqs?>"><i class="fas fa-angle-double-left"></i></a>
                <a href="?page=leads&p=<?=$page-1?>&<?=$pqs?>"><i class="fas fa-angle-left"></i></a>
            <?php endif;?>
            <?php for($i=max(1,$page-2);$i<=min($totalPages,$page+2);$i++):?>
                <a href="?page=leads&p=<?=$i?>&<?=$pqs?>" class="<?=$i===$page?'active':''?>"><?=$i?></a>
            <?php endfor;?>
            <?php if($page<$totalPages):?>
                <a href="?page=leads&p=<?=$page+1?>&<?=$pqs?>"><i class="fas fa-angle-right"></i></a>
                <a href="?page=leads&p=<?=$totalPages?>&<?=$pqs?>"><i class="fas fa-angle-double-right"></i></a>
            <?php endif;?>
        </div>
    </div>
</div>
<?php endif;?>

</div><!-- /.lv-wrap -->

<!-- ══ SLIDE-OVER ════════════════════════════════════════════════════════════ -->
<div class="lv-ov" id="lvOv" onclick="lvCloseSheet()"></div>
<div class="lv-sh" id="lvSh">
    <div class="lv-sh-hd">
        <div class="lv-sh-top">
            <div style="display:flex;align-items:center;gap:12px">
                <div class="lv-sh-av" id="shAv">?</div>
                <div><p class="lv-sh-name" id="shName">—</p><p class="lv-sh-sub" id="shSub"></p></div>
            </div>
            <button class="lv-sh-x" onclick="lvCloseSheet()"><i class="fas fa-times"></i></button>
        </div>
        <div class="lv-sh-tags" id="shTags"></div>
    </div>
    <div class="lv-qrow">
        <button class="lv-qa q-blue"   onclick="lvShTab('log');lvSetLT('appel')"><i class="fas fa-phone"></i> Appel</button>
        <button class="lv-qa q-green"  onclick="lvShTab('email')"><i class="fas fa-envelope"></i> Email</button>
        <button class="lv-qa q-amber"  onclick="lvShTab('log');lvSetLT('rdv')"><i class="fas fa-calendar"></i> RDV</button>
        <button class="lv-qa q-purple" onclick="lvShTab('log');lvSetLT('note')"><i class="fas fa-sticky-note"></i> Note</button>
    </div>
    <div class="lv-tabs">
        <button class="lv-tab on" data-tab="info"  onclick="lvShTab('info')"><i class="fas fa-user"></i> Infos</button>
        <button class="lv-tab"    data-tab="edit"  onclick="lvShTab('edit')"><i class="fas fa-edit"></i> Modifier</button>
        <button class="lv-tab"    data-tab="hist"  onclick="lvShTab('hist')"><i class="fas fa-history"></i> Historique <span id="shHistN" style="background:#6366f1;color:#fff;border-radius:10px;padding:1px 6px;font-size:.63rem;margin-left:2px">0</span></button>
        <button class="lv-tab"    data-tab="email" onclick="lvShTab('email')"><i class="fas fa-envelope"></i> Email</button>
        <button class="lv-tab"    data-tab="log"   onclick="lvShTab('log')"><i class="fas fa-pencil-alt"></i> Ajouter</button>
    </div>
    <div class="lv-sh-body">
        <!-- Onglet INFOS -->
        <div id="tab-info">
            <div class="lv-ig" id="shGrid"></div>
            <div id="shNotes"></div>
            <div id="shNextAction"></div>
            <div style="margin-top:14px;display:flex;gap:8px">
                <button class="lv-btn lv-btn-primary" style="flex:1" onclick="lvShTab('edit')"><i class="fas fa-edit"></i> Modifier</button>
                <button class="lv-btn" style="background:#fef2f2;color:#dc2626;border:1px solid rgba(220,38,38,.2)" onclick="lvDelete(shId,shTbl)"><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <!-- Onglet MODIFIER -->
        <div id="tab-edit" style="display:none">
            <div class="lv-ef" id="shEditForm"></div>
            <div style="display:flex;gap:8px;margin-top:16px">
                <button class="lv-btn lv-btn-primary" style="flex:1" onclick="lvSaveEdit()"><i class="fas fa-save"></i> Enregistrer</button>
                <button class="lv-btn lv-btn-outline" onclick="lvShTab('info')">Annuler</button>
            </div>
        </div>
        <!-- Onglet HISTORIQUE -->
        <div id="tab-hist" style="display:none">
            <div class="lv-tl" id="shTl"><div class="lv-tl-empty"><i class="fas fa-history"></i>Aucune interaction</div></div>
        </div>
        <!-- Onglet EMAIL -->
        <div id="tab-email" style="display:none">
            <div class="lv-em-wrap">
                <div class="lv-em-head"><i class="fas fa-envelope" style="color:#6366f1"></i> Nouveau message</div>
                <div class="lv-em-row"><label>À :</label><input type="email" id="emTo" style="font-weight:500"></div>
                <div class="lv-em-row"><label>Objet :</label><input type="text" id="emSubj" placeholder="Objet..."></div>
                <div id="emBody" class="lv-em-body" contenteditable="true"></div>
                <div class="lv-em-footer">
                    <button id="emBtn" onclick="lvSendEmail()" class="lv-btn lv-btn-primary lv-btn-sm"><i class="fas fa-paper-plane"></i> Envoyer</button>
                </div>
            </div>
            <div style="margin-top:14px">
                <div style="font-size:.77rem;font-weight:700;color:var(--text,#374151);margin-bottom:9px"><i class="fas fa-history" style="color:#6366f1;margin-right:4px"></i>Emails envoyés</div>
                <div class="lv-tl" id="shEmailTl"></div>
            </div>
        </div>
        <!-- Onglet LOG -->
        <div id="tab-log" style="display:none">
            <div class="lv-lf">
                <div>
                    <div class="lv-flbl">Type d'interaction</div>
                    <div class="lv-type-row">
                        <button class="lv-tb on" data-t="appel"  onclick="lvSetLT('appel',this)"><i class="fas fa-phone"       style="color:#2563eb"></i> Appel</button>
                        <button class="lv-tb"    data-t="email"  onclick="lvSetLT('email',this)"><i class="fas fa-envelope"    style="color:#6366f1"></i> Email</button>
                        <button class="lv-tb"    data-t="rdv"    onclick="lvSetLT('rdv',this)"><i class="fas fa-calendar"      style="color:#10b981"></i> RDV</button>
                        <button class="lv-tb"    data-t="sms"    onclick="lvSetLT('sms',this)"><i class="fas fa-sms"           style="color:#f59e0b"></i> SMS</button>
                        <button class="lv-tb"    data-t="note"   onclick="lvSetLT('note',this)"><i class="fas fa-sticky-note"  style="color:#8b5cf6"></i> Note</button>
                        <button class="lv-tb"    data-t="visite" onclick="lvSetLT('visite',this)"><i class="fas fa-home"       style="color:#ef4444"></i> Visite</button>
                    </div>
                </div>
                <div><div class="lv-flbl">Sujet</div><input type="text" class="lv-in" id="logSubj" placeholder="Sujet de l'échange..."></div>
                <div><div class="lv-flbl">Notes</div><textarea class="lv-in lv-ta" id="logCont" placeholder="Ce qui a été dit, décidé..."></textarea></div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                    <div><div class="lv-flbl">Date</div><input type="datetime-local" class="lv-in" id="logDate"></div>
                    <div><div class="lv-flbl">Durée (min)</div><input type="number" class="lv-in" id="logDur" placeholder="15"></div>
                </div>
                <div>
                    <div class="lv-flbl">Résultat</div>
                    <div class="lv-type-row">
                        <button class="lv-tb on" data-o="positif" onclick="lvSetOT('positif',this)" style="border-color:#10b981;background:#d1fae5;color:#065f46">✓ Positif</button>
                        <button class="lv-tb" data-o="neutre"     onclick="lvSetOT('neutre',this)">○ Neutre</button>
                        <button class="lv-tb" data-o="negatif"    onclick="lvSetOT('negatif',this)">✗ Négatif</button>
                    </div>
                </div>
                <button class="lv-btn lv-btn-primary" style="justify-content:center" onclick="lvSaveLog()"><i class="fas fa-save"></i> Enregistrer l'interaction</button>
            </div>
        </div>
    </div>
</div>

<!-- ══ MODAL NOUVEAU LEAD — aligné pages/index.php ══════════════════════════ -->
<div id="lvModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    <div onclick="lvCloseModal()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px);"></div>
    <div id="lvModalBox" style="position:relative;z-index:1;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:560px;margin:16px;overflow:hidden;transform:scale(.94) translateY(8px);transition:transform .22s cubic-bezier(.34,1.56,.64,1),opacity .15s;opacity:0;max-height:90vh;display:flex;flex-direction:column;">
        <div style="padding:20px 22px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0">
            <div style="display:flex;align-items:center;gap:12px">
                <div style="width:40px;height:40px;background:#eff6ff;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#6366f1;font-size:1rem"><i class="fas fa-user-plus"></i></div>
                <div>
                    <div style="font-size:.95rem;font-weight:700;color:#111827">Nouveau lead</div>
                    <div style="font-size:.78rem;color:#6b7280">Ajout manuel dans le CRM</div>
                </div>
            </div>
            <button onclick="lvCloseModal()" style="width:30px;height:30px;border:1px solid #e5e7eb;background:#f9fafb;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#6b7280;transition:all .15s;font-family:inherit" onmouseover="this.style.borderColor='#6366f1';this.style.color='#6366f1';this.style.transform='rotate(90deg)'" onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#6b7280';this.style.transform='rotate(0)'"><i class="fas fa-times"></i></button>
        </div>
        <div style="overflow-y:auto;padding:20px 22px;flex:1">
            <div class="lv-ef">
                <div class="lv-ef-row">
                    <div class="lv-ef-grp"><label>Prénom *</label><input type="text" class="lv-in" id="nFn" placeholder="Jean"></div>
                    <div class="lv-ef-grp"><label>Nom *</label><input type="text" class="lv-in" id="nLn" placeholder="Dupont"></div>
                </div>
                <div class="lv-ef-row">
                    <div class="lv-ef-grp"><label>Email</label><input type="email" class="lv-in" id="nEmail" placeholder="jean@email.com"></div>
                    <div class="lv-ef-grp"><label>Téléphone</label><input type="tel" class="lv-in" id="nPhone" placeholder="06 00 00 00 00"></div>
                </div>
                <div class="lv-ef-row">
                    <div class="lv-ef-grp"><label>Ville</label><input type="text" class="lv-in" id="nCity" placeholder="Bordeaux"></div>
                    <div class="lv-ef-grp"><label>Source</label>
                        <select class="lv-in" id="nSrc">
                            <option value="manuel">Manuel</option><option value="site_web">Site web</option><option value="gmb">GMB</option>
                            <option value="pub_facebook">Facebook</option><option value="pub_google">Google</option>
                            <option value="telephone">Téléphone</option><option value="recommandation">Recommandation</option><option value="autre">Autre</option>
                        </select>
                    </div>
                </div>
                <div class="lv-ef-row">
                    <div class="lv-ef-grp"><label>Statut</label>
                        <select class="lv-in" id="nStatus">
                            <?php foreach($statusLabels as $k=>$v):?><option value="<?=$k?>"><?=$v['label']?></option><?php endforeach;?>
                        </select>
                    </div>
                    <div class="lv-ef-grp"><label>Température</label>
                        <select class="lv-in" id="nTemp">
                            <?php foreach($tempLabels as $k=>$v):?><option value="<?=$k?>"><?=$v['label']?></option><?php endforeach;?>
                        </select>
                    </div>
                </div>
                <div class="lv-ef-row">
                    <div class="lv-ef-grp"><label>Prochaine action</label><input type="text" class="lv-in" id="nNext" placeholder="Rappeler pour RDV"></div>
                    <div class="lv-ef-grp"><label>Date action</label><input type="date" class="lv-in" id="nNextDate"></div>
                </div>
                <div class="lv-ef-grp"><label>Notes</label><textarea class="lv-in lv-ta" id="nNotes" rows="3" placeholder="Infos sur le projet..."></textarea></div>
            </div>
        </div>
        <div style="padding:14px 22px;border-top:1px solid #f3f4f6;display:flex;gap:8px;justify-content:flex-end;flex-shrink:0">
            <button onclick="lvCloseModal()" style="padding:9px 20px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:.83rem;font-weight:600;cursor:pointer;font-family:inherit" onmouseover="this.style.borderColor='#6366f1';this.style.color='#6366f1'" onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#374151'">Annuler</button>
            <button id="nSaveBtn" onclick="lvCreateLead()" style="padding:9px 20px;border-radius:10px;border:none;background:#6366f1;color:#fff;font-size:.83rem;font-weight:700;cursor:pointer;font-family:inherit;display:flex;align-items:center;gap:6px" onmouseover="this.style.background='#4f46e5'" onmouseout="this.style.background='#6366f1'"><i class="fas fa-save"></i> Créer le lead</button>
        </div>
    </div>
</div>

<!-- ══ MODAL CONFIRMATION (delete) ══════════════════════════════════════════ -->
<div id="lvConfirmModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    <div onclick="lvConfirmClose()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px);"></div>
    <div id="lvConfirmBox" style="position:relative;z-index:1;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:420px;margin:16px;overflow:hidden;transform:scale(.94) translateY(8px);transition:transform .22s cubic-bezier(.34,1.56,.64,1),opacity .15s;opacity:0;">
        <div id="lvConfirmHeader" style="padding:20px 22px 16px;display:flex;align-items:flex-start;gap:14px;">
            <div style="width:42px;height:42px;border-radius:12px;background:#fef2f2;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;color:#dc2626"><i class="fas fa-trash"></i></div>
            <div>
                <div style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:5px">Supprimer ce contact ?</div>
                <div id="lvConfirmMsg" style="font-size:.82rem;color:#6b7280;line-height:1.5;"></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding:12px 20px 18px;border-top:1px solid #f3f4f6;">
            <button onclick="lvConfirmClose()" style="padding:9px 20px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:.83rem;font-weight:600;cursor:pointer;font-family:inherit" onmouseover="this.style.borderColor='#6366f1';this.style.color='#6366f1'" onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#374151'">Annuler</button>
            <button id="lvConfirmBtn" style="padding:9px 20px;border-radius:10px;border:none;background:#dc2626;color:#fff;font-size:.83rem;font-weight:700;cursor:pointer;font-family:inherit" onmouseover="this.style.filter='brightness(.88)'" onmouseout="this.style.filter=''">Supprimer</button>
        </div>
    </div>
</div>

<script>
// ─────────────────────────────────────────────────────────────────────────────
// Config
// ─────────────────────────────────────────────────────────────────────────────
const LV_BASE = window.location.pathname + '?page=leads&ajax=1';

let shId = null, shTbl = 'leads', logType = 'appel', logOutcome = 'positif';
let shData = null;

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────
async function lvApi(params) {
    const fd = new FormData();
    for (const [k,v] of Object.entries(params))
        if (v !== null && v !== undefined && String(v).length > 0) fd.append(k, String(v));
    const resp = await fetch(LV_BASE, { method:'POST', body:fd });
    if (!resp.ok) throw new Error('HTTP '+resp.status);
    const text = await resp.text();
    try { return JSON.parse(text); }
    catch(e) { console.error('Response non-JSON:', text); throw new Error('Réponse invalide du serveur'); }
}

// Toast aligné pages/index.php
function lvToast(msg, type = 'success') {
    const colors = { success:'#059669', error:'#dc2626', warning:'#d97706', info:'#3b82f6' };
    const icons  = { success:'✓', error:'✕', warning:'⚠', info:'ℹ' };
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:10000;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:10px;font-size:.83rem;font-weight:600;color:#111827;box-shadow:0 8px 24px rgba(0,0,0,.12);transform:translateY(20px);opacity:0;transition:all .25s;';
    t.innerHTML = `<span style="width:22px;height:22px;border-radius:50%;background:${colors[type] || colors.info}22;color:${colors[type] || colors.info};display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800">${icons[type] || icons.info}</span>${msg}`;
    document.body.appendChild(t);
    requestAnimationFrame(() => { t.style.opacity='1'; t.style.transform='translateY(0)'; });
    setTimeout(() => { t.style.opacity='0'; t.style.transform='translateY(10px)'; setTimeout(()=>t.remove(),250); }, 3500);
}

function lvFilterSrc(src) {
    const url = new URL(window.location.href);
    src ? url.searchParams.set('src', src) : url.searchParams.delete('src');
    url.searchParams.delete('p');
    window.location.href = url.toString();
}

function lvFilterStatus(val) {
    const url = new URL(window.location.href);
    val ? url.searchParams.set('status', val) : url.searchParams.delete('status');
    url.searchParams.delete('p');
    window.location.href = url.toString();
}

// ─────────────────────────────────────────────────────────────────────────────
// Modal nouveau lead — aligné pages/index.php
// ─────────────────────────────────────────────────────────────────────────────
function lvOpenModal() {
    ['nFn','nLn','nEmail','nPhone','nCity','nNext','nNextDate','nNotes'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    document.getElementById('nStatus').value = 'new';
    document.getElementById('nTemp').value   = 'warm';
    document.getElementById('nSrc').value    = 'manuel';
    const wrap = document.getElementById('lvModal');
    const box  = document.getElementById('lvModalBox');
    wrap.style.display = 'flex';
    requestAnimationFrame(() => { box.style.opacity='1'; box.style.transform='scale(1) translateY(0)'; });
    document.getElementById('nFn').focus();
    document.addEventListener('keydown', _lvModalEsc);
}

function lvCloseModal() {
    const wrap = document.getElementById('lvModal');
    const box  = document.getElementById('lvModalBox');
    box.style.opacity='0'; box.style.transform='scale(.94) translateY(8px)';
    setTimeout(() => wrap.style.display='none', 160);
    document.removeEventListener('keydown', _lvModalEsc);
}

function _lvModalEsc(e) { if (e.key === 'Escape') { lvCloseModal(); lvConfirmClose(); lvCloseSheet(); } }

async function lvCreateLead() {
    const fn = document.getElementById('nFn').value.trim();
    const ln = document.getElementById('nLn').value.trim();
    if (!fn && !ln) { lvToast('Prénom ou nom requis','warning'); document.getElementById('nFn').focus(); return; }
    const btn = document.getElementById('nSaveBtn');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Création...';
    try {
        const res = await lvApi({
            action: 'add_lead', firstname: fn, lastname: ln,
            email:       document.getElementById('nEmail').value,
            phone:       document.getElementById('nPhone').value,
            city:        document.getElementById('nCity').value,
            source:      document.getElementById('nSrc').value,
            status:      document.getElementById('nStatus').value,
            temperature: document.getElementById('nTemp').value,
            next_action:      document.getElementById('nNext').value,
            next_action_date: document.getElementById('nNextDate').value,
            notes:       document.getElementById('nNotes').value,
        });
        if (res.success) {
            lvToast('Lead créé avec succès ✓');
            lvCloseModal();
            setTimeout(() => location.reload(), 600);
        } else {
            lvToast(res.error || 'Erreur lors de la création', 'error');
        }
    } catch(e) { lvToast('Erreur: '+e.message, 'error'); }
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-save"></i> Créer le lead';
}

// ─────────────────────────────────────────────────────────────────────────────
// Confirm modal (delete) — aligné pages/index.php
// ─────────────────────────────────────────────────────────────────────────────
let _lvConfirmCb = null;

function lvConfirmOpen(msg, onConfirm) {
    document.getElementById('lvConfirmMsg').innerHTML = msg;
    _lvConfirmCb = onConfirm;
    document.getElementById('lvConfirmBtn').onclick = () => { lvConfirmClose(); if (_lvConfirmCb) _lvConfirmCb(); };
    const wrap = document.getElementById('lvConfirmModal');
    const box  = document.getElementById('lvConfirmBox');
    wrap.style.display = 'flex';
    requestAnimationFrame(() => { box.style.opacity='1'; box.style.transform='scale(1) translateY(0)'; });
}

function lvConfirmClose() {
    const wrap = document.getElementById('lvConfirmModal');
    const box  = document.getElementById('lvConfirmBox');
    if (!wrap || wrap.style.display === 'none') return;
    box.style.opacity='0'; box.style.transform='scale(.94) translateY(8px)';
    setTimeout(() => wrap.style.display='none', 160);
}

// ─────────────────────────────────────────────────────────────────────────────
// Supprimer
// ─────────────────────────────────────────────────────────────────────────────
function lvDelete(id, tbl) {
    lvConfirmOpen(
        'Ce contact sera supprimé définitivement.<br><span style="font-size:.78rem;color:#9ca3af">Cette action est irréversible.</span>',
        async () => {
            try {
                const res = await lvApi({ action:'delete_lead', id, tbl: tbl||shTbl||'leads' });
                if (res.success) {
                    lvToast('Contact supprimé');
                    lvCloseSheet();
                    document.querySelectorAll(`tr[onclick*="lvSheet(${id},"]`).forEach(el => {
                        el.style.cssText = 'opacity:0;transform:scale(.98);transition:all .3s';
                        setTimeout(() => el.remove(), 300);
                    });
                } else { lvToast(res.error || 'Erreur', 'error'); }
            } catch(e) { lvToast('Erreur: '+e.message, 'error'); }
        }
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Slide-over
// ─────────────────────────────────────────────────────────────────────────────
async function lvSheet(id, tbl) {
    shId = id; shTbl = tbl || 'leads';
    document.getElementById('lvOv').classList.add('on');
    document.getElementById('lvSh').classList.add('on');
    lvShTab('info');
    await lvLoadSheet();
    if (tbl === 'leads') await lvLoadHistory();
}

function lvCloseSheet() {
    document.getElementById('lvOv').classList.remove('on');
    document.getElementById('lvSh').classList.remove('on');
    shId = null; shData = null;
}

async function lvLoadSheet() {
    try {
        const res = await lvApi({ action:'get_lead', id:shId, tbl:shTbl });
        if (!res.success || !res.lead) { lvToast('Impossible de charger la fiche','error'); return; }
        shData = res.lead;
        lvRenderSheetHeader(shData);
        lvRenderSheetInfo(shData);
        lvRenderEditForm(shData);
    } catch(e) { lvToast('Erreur: '+e.message,'error'); }
}

function lvRenderSheetHeader(l) {
    let fn='',ln='',email='',phone='',city='',status='',score=0,temp='';
    const srcLabel = { leads:'Manuel', capture_leads:'Capture', demandes_estimation:'Estimation', contacts:'Contact', financement_leads:'Financement' }[shTbl] || shTbl;
    if (shTbl==='leads') {
        fn=l.firstname||(l.full_name?l.full_name.split(' ')[0]:''); ln=l.lastname||(l.full_name?l.full_name.split(' ').slice(1).join(' '):'');
        email=l.email||''; phone=l.phone||''; city=l.city||''; status=l.status||''; score=l.score||0; temp=l.temperature||'';
    } else if (shTbl==='capture_leads') {
        fn=l.prenom||''; ln=l.nom||''; email=l.email||''; phone=l.tel||''; status=l.injected_crm?'contacté':'nouveau';
    } else if (shTbl==='demandes_estimation') {
        ln=(l.type_bien||'Estimation')+' '+(l.ville||''); email=l.email||''; phone=l.telephone||''; city=l.ville||''; status=l.statut||'nouveau';
    } else if (shTbl==='contacts') {
        fn=l.firstname||l.prenom||''; ln=l.lastname||l.nom||''; email=l.email||''; phone=l.phone||l.telephone||''; city=l.city||''; status=l.status||'actif'; score=l.rating||0;
    } else if (shTbl==='financement_leads') {
        fn=l.prenom||''; ln=l.nom||''; email=l.email||''; phone=l.telephone||''; status=l.statut||'nouveau';
    }
    const ini = ((fn||'?').charAt(0)+(ln||'?').charAt(0)).toUpperCase();
    const srcSt = <?=json_encode($srcStyles)?>;
    const ss = srcSt[srcLabel]||{bg:'#f1f5f9',c:'#475569'};
    const stMap = <?=json_encode($statusLabels)?>;
    const stI = stMap[status]||null;
    const tpMap = <?=json_encode($tempLabels)?>;
    const tpI = tpMap[temp]||null;

    document.getElementById('shAv').textContent = ini;
    document.getElementById('shAv').style.background = ss.c;
    document.getElementById('shName').textContent = (fn+' '+ln).trim()||'—';
    document.getElementById('shSub').textContent  = [city,srcLabel].filter(Boolean).join(' · ');
    document.getElementById('emTo').value = email;

    let tags = `<span class="lv-sh-tag" style="background:${ss.bg};color:${ss.c}">${srcLabel}</span>`;
    if (stI) tags += `<span class="lv-sh-tag" style="background:${stI.bg};color:${stI.c}">${stI.label}</span>`;
    if (tpI) tags += `<span class="lv-sh-tag" style="background:${tpI.bg};color:${tpI.c}"><i class="fas fa-${tpI.icon}" style="font-size:.58rem"></i> ${tpI.label}</span>`;
    if (score>0) tags += `<span class="lv-sh-tag" style="background:#e0e7ff;color:#4f46e5">Score ${score}</span>`;
    document.getElementById('shTags').innerHTML = tags;
}

function lvRenderSheetInfo(l) {
    let email='',phone='',city='',notes='',nextAction='',nextDate='';
    if (shTbl==='leads') {
        email=l.email||''; phone=l.phone||''; city=l.city||''; notes=l.notes||''; nextAction=l.next_action||''; nextDate=l.next_action_date||'';
    } else if (shTbl==='capture_leads') {
        email=l.email||''; phone=l.tel||''; notes=l.message||'';
    } else if (shTbl==='demandes_estimation') {
        email=l.email||''; phone=l.telephone||''; city=l.ville||'';
        const parts=[l.type_bien,l.surface?l.surface+'m²':'',l.estimation_moyenne?'~'+Number(l.estimation_moyenne).toLocaleString('fr-FR')+'€':''].filter(Boolean);
        notes=parts.join(' — ');
    } else if (shTbl==='contacts') {
        email=l.email||''; phone=l.phone||l.telephone||''; city=l.city||''; notes=l.notes||'';
    } else if (shTbl==='financement_leads') {
        email=l.email||''; phone=l.telephone||'';
        notes=[(l.type_projet||'Projet'),l.montant_projet?Number(l.montant_projet).toLocaleString('fr-FR')+'€':'',l.notes||''].filter(Boolean).join(' — ');
    }
    const c = (lbl,val) => `<div class="lv-ic"><div class="lv-ic-l">${lbl}</div><div class="lv-ic-v">${val||'—'}</div></div>`;
    document.getElementById('shGrid').innerHTML = [
        c('<i class="fas fa-envelope"></i> Email', email?`<a href="mailto:${email}">${email}</a>`:''),
        c('<i class="fas fa-phone"></i> Téléphone', phone?`<a href="tel:${phone}">${phone}</a>`:''),
        city ? c('<i class="fas fa-map-marker-alt"></i> Ville', city) : '',
        c('<i class="fas fa-calendar-plus"></i> Créé le', new Date(l.created_at).toLocaleDateString('fr-FR',{day:'2-digit',month:'long',year:'numeric'})),
    ].join('');
    document.getElementById('shNotes').innerHTML = notes
        ? `<div class="lv-note-bloc"><div class="lv-note-title"><i class="fas fa-sticky-note"></i> Notes / Projet</div><div class="lv-note-txt">${lvEsc(notes)}</div></div>` : '';
    document.getElementById('shNextAction').innerHTML = nextAction
        ? `<div class="lv-na-bloc"><div class="lv-na-title"><i class="fas fa-tasks"></i> Prochaine action</div><div class="lv-na-txt">${lvEsc(nextAction)}${nextDate?' — '+new Date(nextDate+'T00:00').toLocaleDateString('fr-FR'):''}</div></div>` : '';
}

function lvRenderEditForm(l) {
    const form = document.getElementById('shEditForm');
    let html = '';
    if (shTbl === 'leads') {
        const fn = l.firstname || (l.full_name?l.full_name.split(' ')[0]:'');
        const ln = l.lastname  || (l.full_name?l.full_name.split(' ').slice(1).join(' '):'');
        const stOpts = <?=json_encode(array_map(fn($v)=>$v['label'], $statusLabels))?>;
        const stKeys = <?=json_encode(array_keys($statusLabels))?>;
        const tpOpts = <?=json_encode(array_map(fn($v)=>$v['label'], $tempLabels))?>;
        const tpKeys = <?=json_encode(array_keys($tempLabels))?>;
        const srcOpts = { manuel:'Manuel', site_web:'Site web', gmb:'GMB', pub_facebook:'Facebook', pub_google:'Google', telephone:'Téléphone', recommandation:'Recommandation', autre:'Autre' };
        html += `<div class="lv-ef-sec"><i class="fas fa-user"></i> Identité</div>`;
        html += `<div class="lv-ef-row"><div class="lv-ef-grp"><label>Prénom *</label><input class="lv-in" id="ef_fn" value="${lvEsc(fn)}"></div><div class="lv-ef-grp"><label>Nom *</label><input class="lv-in" id="ef_ln" value="${lvEsc(ln)}"></div></div>`;
        html += `<div class="lv-ef-row"><div class="lv-ef-grp"><label>Email</label><input type="email" class="lv-in" id="ef_email" value="${lvEsc(l.email||'')}"></div><div class="lv-ef-grp"><label>Téléphone</label><input type="tel" class="lv-in" id="ef_phone" value="${lvEsc(l.phone||'')}"></div></div>`;
        html += `<div class="lv-ef-grp"><label>Ville</label><input class="lv-in" id="ef_city" value="${lvEsc(l.city||'')}"></div>`;
        html += `<div class="lv-ef-sec"><i class="fas fa-tasks"></i> Suivi</div>`;
        html += `<div class="lv-ef-row"><div class="lv-ef-grp"><label>Statut</label><select class="lv-in" id="ef_status">${stKeys.map((k,i)=>`<option value="${k}" ${l.status===k?'selected':''}>${stOpts[i]}</option>`).join('')}</select></div><div class="lv-ef-grp"><label>Température</label><select class="lv-in" id="ef_temp">${tpKeys.map((k,i)=>`<option value="${k}" ${l.temperature===k?'selected':''}>${tpOpts[i]}</option>`).join('')}</select></div></div>`;
        html += `<div class="lv-ef-grp"><label>Source</label><select class="lv-in" id="ef_source">${Object.entries(srcOpts).map(([k,v])=>`<option value="${k}" ${l.source===k?'selected':''}>${v}</option>`).join('')}</select></div>`;
        html += `<div class="lv-ef-sec"><i class="fas fa-clock"></i> Prochaine action</div>`;
        html += `<div class="lv-ef-row"><div class="lv-ef-grp"><label>Action</label><input class="lv-in" id="ef_next" value="${lvEsc(l.next_action||'')}" placeholder="Ex: Rappeler"></div><div class="lv-ef-grp"><label>Date</label><input type="date" class="lv-in" id="ef_next_date" value="${lvEsc(l.next_action_date||'')}"></div></div>`;
        html += `<div class="lv-ef-grp"><label>Notes</label><textarea class="lv-in lv-ta" id="ef_notes" rows="3">${lvEsc(l.notes||'')}</textarea></div>`;
    } else if (shTbl === 'capture_leads') {
        html += `<div class="lv-ef-row"><div class="lv-ef-grp"><label>Prénom</label><input class="lv-in" id="ef_fn" value="${lvEsc(l.prenom||'')}"></div><div class="lv-ef-grp"><label>Nom</label><input class="lv-in" id="ef_ln" value="${lvEsc(l.nom||'')}"></div></div>`;
        html += `<div class="lv-ef-row"><div class="lv-ef-grp"><label>Email</label><input type="email" class="lv-in" id="ef_email" value="${lvEsc(l.email||'')}"></div><div class="lv-ef-grp"><label>Téléphone</label><input type="tel" class="lv-in" id="ef_phone" value="${lvEsc(l.tel||'')}"></div></div>`;
    } else if (shTbl === 'demandes_estimation') {
        html += `<div class="lv-ef-row"><div class="lv-ef-grp"><label>Email</label><input type="email" class="lv-in" id="ef_email" value="${lvEsc(l.email||'')}"></div><div class="lv-ef-grp"><label>Téléphone</label><input type="tel" class="lv-in" id="ef_phone" value="${lvEsc(l.telephone||'')}"></div></div>`;
        html += `<div class="lv-ef-grp"><label>Statut</label><select class="lv-in" id="ef_status"><option value="nouveau" ${l.statut==='nouveau'?'selected':''}>Nouveau</option><option value="contacté" ${l.statut==='contacté'?'selected':''}>Contacté</option><option value="traité" ${l.statut==='traité'?'selected':''}>Traité</option></select></div>`;
    } else if (shTbl === 'contacts') {
        html += `<div class="lv-ef-row"><div class="lv-ef-grp"><label>Prénom</label><input class="lv-in" id="ef_fn" value="${lvEsc(l.firstname||l.prenom||'')}"></div><div class="lv-ef-grp"><label>Nom</label><input class="lv-in" id="ef_ln" value="${lvEsc(l.lastname||l.nom||'')}"></div></div>`;
        html += `<div class="lv-ef-row"><div class="lv-ef-grp"><label>Email</label><input type="email" class="lv-in" id="ef_email" value="${lvEsc(l.email||'')}"></div><div class="lv-ef-grp"><label>Téléphone</label><input type="tel" class="lv-in" id="ef_phone" value="${lvEsc(l.phone||l.telephone||'')}"></div></div>`;
        html += `<div class="lv-ef-grp"><label>Ville</label><input class="lv-in" id="ef_city" value="${lvEsc(l.city||'')}"></div>`;
        html += `<div class="lv-ef-grp"><label>Notes</label><textarea class="lv-in lv-ta" id="ef_notes" rows="3">${lvEsc(l.notes||'')}</textarea></div>`;
    } else if (shTbl === 'financement_leads') {
        html += `<div class="lv-ef-row"><div class="lv-ef-grp"><label>Prénom</label><input class="lv-in" id="ef_fn" value="${lvEsc(l.prenom||'')}"></div><div class="lv-ef-grp"><label>Nom</label><input class="lv-in" id="ef_ln" value="${lvEsc(l.nom||'')}"></div></div>`;
        html += `<div class="lv-ef-row"><div class="lv-ef-grp"><label>Email</label><input type="email" class="lv-in" id="ef_email" value="${lvEsc(l.email||'')}"></div><div class="lv-ef-grp"><label>Téléphone</label><input type="tel" class="lv-in" id="ef_phone" value="${lvEsc(l.telephone||'')}"></div></div>`;
        html += `<div class="lv-ef-grp"><label>Notes</label><textarea class="lv-in lv-ta" id="ef_notes" rows="3">${lvEsc(l.notes||'')}</textarea></div>`;
    }
    form.innerHTML = html;
}

async function lvSaveEdit() {
    if (!shId) return;
    const g = id => document.getElementById(id)?.value?.trim() ?? '';
    const payload = {
        action: 'update_lead', id: shId, tbl: shTbl,
        firstname:   g('ef_fn'), lastname:    g('ef_ln'),
        email:       g('ef_email'), phone:    g('ef_phone'),
        city:        g('ef_city'), source:    g('ef_source'),
        status:      g('ef_status'), temperature: g('ef_temp'),
        notes:       document.getElementById('ef_notes')?.value ?? '',
        next_action:       g('ef_next'),
        next_action_date:  g('ef_next_date'),
    };
    try {
        const res = await lvApi(payload);
        if (res.success) {
            lvToast('Modifications enregistrées ✓');
            await lvLoadSheet();
            lvShTab('info');
        } else { lvToast(res.error || 'Erreur lors de la sauvegarde', 'error'); }
    } catch(e) { lvToast('Erreur: '+e.message, 'error'); }
}

// ─────────────────────────────────────────────────────────────────────────────
// Historique
// ─────────────────────────────────────────────────────────────────────────────
async function lvLoadHistory() {
    try {
        const res = await lvApi({ action:'get_interactions', lead_id:shId });
        const items = res.interactions || [];
        document.getElementById('shHistN').textContent = items.length;
        const tConf = { appel:{icon:'phone',c:'#2563eb'}, email:{icon:'envelope',c:'#6366f1'}, rdv:{icon:'calendar',c:'#10b981'}, sms:{icon:'sms',c:'#f59e0b'}, note:{icon:'sticky-note',c:'#8b5cf6'}, visite:{icon:'home',c:'#ef4444'} };
        const mkTl = list => !list.length
            ? `<div class="lv-tl-empty"><i class="fas fa-history"></i>Aucune interaction</div>`
            : list.map(i => {
                const tc = tConf[i.type]||tConf.note;
                const d  = new Date(i.interaction_date||i.created_at);
                return `<div class="lv-tl-item"><div class="lv-tl-dot" style="background:${tc.c}"><i class="fas fa-${tc.icon}"></i></div><div class="lv-tl-card"><div class="lv-tl-head"><span class="lv-tl-type" style="color:${tc.c}">${i.type}</span><span class="lv-tl-date">${d.toLocaleDateString('fr-FR')} ${d.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'})}</span></div>${i.subject?`<div class="lv-tl-subj">${lvEsc(i.subject)}</div>`:''} ${i.content?`<div class="lv-tl-txt">${lvEsc(i.content)}</div>`:''}</div></div>`;
            }).join('');
        document.getElementById('shTl').innerHTML      = mkTl(items);
        document.getElementById('shEmailTl').innerHTML = mkTl(items.filter(i=>i.type==='email'));
    } catch(e) {}
}

async function lvSaveLog() {
    if (!shId) return;
    const subj = document.getElementById('logSubj').value.trim();
    const cont = document.getElementById('logCont').value.trim();
    if (!subj && !cont) { lvToast('Ajoutez un sujet ou des notes','warning'); return; }
    try {
        const res = await lvApi({
            action:'add_interaction', lead_id:shId, type:logType,
            subject:subj, content:cont,
            interaction_date: document.getElementById('logDate').value || new Date().toISOString().slice(0,16),
            duration_minutes: document.getElementById('logDur').value || 0,
            outcome: logOutcome,
        });
        if (res.success) {
            lvToast('Interaction enregistrée ✓');
            document.getElementById('logSubj').value=''; document.getElementById('logCont').value='';
            await lvLoadHistory();
            lvShTab('hist');
        } else lvToast(res.error||'Erreur','error');
    } catch(e) { lvToast('Erreur: '+e.message,'error'); }
}

// ─────────────────────────────────────────────────────────────────────────────
// Email
// ─────────────────────────────────────────────────────────────────────────────
async function lvSendEmail() {
    const to   = document.getElementById('emTo').value.trim();
    const subj = document.getElementById('emSubj').value.trim();
    const body = document.getElementById('emBody').innerHTML.trim();
    if (!to||!subj||!body) { lvToast('Remplissez tous les champs','warning'); return; }
    const btn = document.getElementById('emBtn');
    btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
    try {
        const res = await lvApi({ action:'send_email', lead_id:shId, to, subject:subj, body });
        if (res.success) {
            lvToast('Email envoyé ✓');
            document.getElementById('emSubj').value=''; document.getElementById('emBody').innerHTML='';
            await lvLoadHistory();
        } else lvToast(res.error||'Erreur envoi','error');
    } catch(e) { lvToast('Erreur: '+e.message,'error'); }
    btn.disabled=false; btn.innerHTML='<i class="fas fa-paper-plane"></i> Envoyer';
}

// ─────────────────────────────────────────────────────────────────────────────
// Tabs & utils
// ─────────────────────────────────────────────────────────────────────────────
function lvShTab(name) {
    document.querySelectorAll('.lv-tab').forEach(t => t.classList.toggle('on', t.dataset.tab===name));
    ['info','edit','hist','email','log'].forEach(t => {
        const el = document.getElementById('tab-'+t);
        if (el) el.style.display = (t===name) ? '' : 'none';
    });
}

function lvSetLT(t, btn) {
    logType = t;
    document.querySelectorAll('[data-t]').forEach(b => b.classList.remove('on'));
    const b = btn || document.querySelector(`[data-t="${t}"]`);
    if (b) b.classList.add('on');
}

function lvSetOT(o, btn) {
    logOutcome = o;
    const c = { positif:['#10b981','#d1fae5','#065f46'], neutre:['#0ea5e9','#e0f2fe','#0c4a6e'], negatif:['#ef4444','#fee2e2','#7f1d1d'] };
    document.querySelectorAll('[data-o]').forEach(b => { b.classList.remove('on'); b.style.cssText=''; });
    btn.classList.add('on'); const [bc,bg,tc]=c[o]; btn.style.borderColor=bc; btn.style.background=bg; btn.style.color=tc;
}

function lvEsc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ─────────────────────────────────────────────────────────────────────────────
// Init
// ─────────────────────────────────────────────────────────────────────────────
document.getElementById('logDate').value = new Date().toISOString().slice(0,16);
document.addEventListener('keydown', e => { if(e.key==='Escape'){ lvCloseSheet(); lvCloseModal(); lvConfirmClose(); } });
document.getElementById('lvModal').addEventListener('click', e => { if(e.target===e.currentTarget) lvCloseModal(); });
</script>