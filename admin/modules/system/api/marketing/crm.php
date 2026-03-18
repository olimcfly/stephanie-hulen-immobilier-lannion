<?php
/**
 *  /admin/api/marketing/crm.php
 *  CRM Contacts CRUD + Pipeline
 *  Miroir de : modules/marketing/crm/
 *  Table : contacts
 *  actions: list, get, save, delete, pipeline, add-tag, remove-tag, merge, export
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];

if ($action==='list') {
    $sql="SELECT * FROM contacts WHERE 1=1"; $params=[];
    if (!empty($p['status'])) { $sql.=" AND status=?"; $params[]=$p['status']; }
    if (!empty($p['tag']))    { $sql.=" AND tags LIKE ?"; $params[]="%{$p['tag']}%"; }
    if (!empty($p['search'])) { $sql.=" AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)"; $s="%{$p['search']}%"; $params=array_merge($params,[$s,$s,$s,$s]); }
    $sql.=" ORDER BY created_at DESC LIMIT ".min((int)($p['limit']??50),200);
    $stmt=$pdo->prepare($sql); $stmt->execute($params);
    $total=(int)$pdo->query("SELECT COUNT(*) FROM contacts")->fetchColumn();
    return ['success'=>true,'contacts'=>$stmt->fetchAll(),'total'=>$total];
}

if ($action==='get') {
    $stmt=$pdo->prepare("SELECT * FROM contacts WHERE id=?"); $stmt->execute([(int)($p['id']??0)]);
    $c=$stmt->fetch();
    if (!$c) return ['success'=>false,'error'=>'Contact non trouvé','_http_code'=>404];
    // Attach related data
    try { $c['emails']=$pdo->prepare("SELECT id,subject,direction,is_read,sent_at FROM crm_emails WHERE contact_id=? ORDER BY created_at DESC LIMIT 10"); $c['emails']->execute([$c['id']]); $c['emails']=$c['emails']->fetchAll(); } catch(Exception $e){ $c['emails']=[]; }
    try { $c['score']=$pdo->prepare("SELECT * FROM lead_scoring WHERE lead_id=?"); $c['score']->execute([$c['id']]); $c['score']=$c['score']->fetch(); } catch(Exception $e){ $c['score']=null; }
    return ['success'=>true,'contact'=>$c];
}

if ($action==='save' && $method==='POST') {
    $id=(int)($p['id']??0);
    $fields=[
        'first_name'=>$p['first_name']??'','last_name'=>$p['last_name']??'','email'=>$p['email']??'',
        'phone'=>$p['phone']??null,'company'=>$p['company']??null,'source'=>$p['source']??null,
        'status'=>$p['status']??'new','pipeline_stage'=>$p['pipeline_stage']??null,
        'tags'=>is_array($p['tags']??null)?json_encode($p['tags']):($p['tags']??null),
        'notes'=>$p['notes']??null,'gdpr_consent'=>(int)($p['gdpr_consent']??0),
    ];
    if ($id>0) { $s=[]; $v=[]; foreach($fields as $c=>$val){$s[]="`{$c}`=?";$v[]=$val;} $v[]=$id; $pdo->prepare("UPDATE contacts SET ".implode(',',$s)." WHERE id=?")->execute($v); return ['success'=>true,'id'=>$id]; }
    $cols=array_keys($fields); $pdo->prepare("INSERT INTO contacts (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")")->execute(array_values($fields));
    return ['success'=>true,'id'=>(int)$pdo->lastInsertId()];
}

if ($action==='delete' && $method==='POST') { $pdo->prepare("DELETE FROM contacts WHERE id=?")->execute([(int)($p['id']??0)]); return ['success'=>true]; }

if ($action==='pipeline') {
    $stages=['new','contacted','qualified','proposition','negotiation','won','lost'];
    $pipeline=[];
    foreach ($stages as $s) {
        $stmt=$pdo->prepare("SELECT id,first_name,last_name,email,company FROM contacts WHERE pipeline_stage=? ORDER BY updated_at DESC");
        $stmt->execute([$s]);
        $pipeline[$s]=$stmt->fetchAll();
    }
    return ['success'=>true,'pipeline'=>$pipeline,'stages'=>$stages];
}

if ($action==='add-tag' && $method==='POST') {
    $id=(int)($p['id']??0); $tag=$p['tag']??'';
    $stmt=$pdo->prepare("SELECT tags FROM contacts WHERE id=?"); $stmt->execute([$id]);
    $tags=json_decode($stmt->fetchColumn()?:'[]',true);
    if (!in_array($tag,$tags)) { $tags[]=$tag; $pdo->prepare("UPDATE contacts SET tags=? WHERE id=?")->execute([json_encode($tags),$id]); }
    return ['success'=>true,'tags'=>$tags];
}

if ($action==='remove-tag' && $method==='POST') {
    $id=(int)($p['id']??0); $tag=$p['tag']??'';
    $stmt=$pdo->prepare("SELECT tags FROM contacts WHERE id=?"); $stmt->execute([$id]);
    $tags=json_decode($stmt->fetchColumn()?:'[]',true);
    $tags=array_values(array_filter($tags,fn($t)=>$t!==$tag));
    $pdo->prepare("UPDATE contacts SET tags=? WHERE id=?")->execute([json_encode($tags),$id]);
    return ['success'=>true,'tags'=>$tags];
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['list','get','save','delete','pipeline','add-tag','remove-tag']];
