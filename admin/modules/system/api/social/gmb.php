<?php
/**
 *  /admin/api/social/gmb.php
 *  Prospection GMB — prospects, sequences B2B
 *  Miroir de : modules/social/gmb/ + modules/network/scraper-gmb/
 *  Table : gmb_prospects
 *  actions: list, get, save, delete, update-status, stats, import
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];

if ($action==='list') {
    $sql="SELECT * FROM gmb_prospects WHERE 1=1"; $params=[];
    if (!empty($p['status']))   { $sql.=" AND status=?"; $params[]=$p['status']; }
    if (!empty($p['category'])) { $sql.=" AND category LIKE ?"; $params[]="%{$p['category']}%"; }
    if (!empty($p['search']))   { $sql.=" AND (business_name LIKE ? OR email LIKE ? OR phone LIKE ?)"; $s="%{$p['search']}%"; $params=array_merge($params,[$s,$s,$s]); }
    $sql.=" ORDER BY created_at DESC LIMIT ".min((int)($p['limit']??50),200);
    $stmt=$pdo->prepare($sql); $stmt->execute($params);
    $total=(int)$pdo->query("SELECT COUNT(*) FROM gmb_prospects")->fetchColumn();
    return ['success'=>true,'prospects'=>$stmt->fetchAll(),'total'=>$total];
}

if ($action==='get') {
    $stmt=$pdo->prepare("SELECT * FROM gmb_prospects WHERE id=?"); $stmt->execute([(int)($p['id']??0)]);
    $row=$stmt->fetch();
    if (!$row) return ['success'=>false,'error'=>'Prospect non trouvé','_http_code'=>404];
    return ['success'=>true,'prospect'=>$row];
}

if ($action==='save' && $method==='POST') {
    $id=(int)($p['id']??0);
    $fields=['business_name'=>$p['business_name']??'','category'=>$p['category']??null,'address'=>$p['address']??null,
        'phone'=>$p['phone']??null,'email'=>$p['email']??null,'website'=>$p['website']??null,
        'rating'=>$p['rating']??null,'reviews_count'=>(int)($p['reviews_count']??0),'gmb_url'=>$p['gmb_url']??null,
        'status'=>$p['status']??'new','notes'=>$p['notes']??null,'sequence_id'=>$p['sequence_id']??null];
    if ($id>0) { $s=[]; $v=[]; foreach($fields as $c=>$val){$s[]="`{$c}`=?";$v[]=$val;} $v[]=$id; $pdo->prepare("UPDATE gmb_prospects SET ".implode(',',$s)." WHERE id=?")->execute($v); return ['success'=>true,'id'=>$id]; }
    $cols=array_keys($fields); $pdo->prepare("INSERT INTO gmb_prospects (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")")->execute(array_values($fields));
    return ['success'=>true,'id'=>(int)$pdo->lastInsertId()];
}

if ($action==='delete' && $method==='POST') { $pdo->prepare("DELETE FROM gmb_prospects WHERE id=?")->execute([(int)($p['id']??0)]); return ['success'=>true]; }

if ($action==='update-status' && $method==='POST') {
    $pdo->prepare("UPDATE gmb_prospects SET status=?, last_contacted=NOW() WHERE id=?")->execute([$p['status']??'contacted',(int)($p['id']??0)]);
    return ['success'=>true];
}

if ($action==='stats') {
    $total=(int)$pdo->query("SELECT COUNT(*) FROM gmb_prospects")->fetchColumn();
    $byStatus=[]; $stmt=$pdo->query("SELECT status, COUNT(*) as cnt FROM gmb_prospects GROUP BY status");
    while($r=$stmt->fetch()) $byStatus[$r['status']]=(int)$r['cnt'];
    return ['success'=>true,'stats'=>['total'=>$total,'by_status'=>$byStatus]];
}

if ($action==='import' && $method==='POST') {
    $items=$p['prospects']??[];
    if (!is_array($items)) return ['success'=>false,'error'=>'prospects[] requis'];
    $imported=0;
    foreach ($items as $item) {
        $pdo->prepare("INSERT INTO gmb_prospects (business_name,category,address,phone,email,website,rating,reviews_count,gmb_url,status) VALUES (?,?,?,?,?,?,?,?,?,'new')")
            ->execute([$item['business_name']??'',$item['category']??null,$item['address']??null,$item['phone']??null,$item['email']??null,$item['website']??null,$item['rating']??null,(int)($item['reviews_count']??0),$item['gmb_url']??null]);
        $imported++;
    }
    return ['success'=>true,'imported'=>$imported];
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['list','get','save','delete','update-status','stats','import']];
