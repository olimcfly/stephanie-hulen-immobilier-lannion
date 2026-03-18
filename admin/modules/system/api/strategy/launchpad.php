<?php
/**
 *  /admin/api/strategy/launchpad.php
 *  Launchpad — diagnostic, parcours, progression
 *  Miroir de : modules/strategy/launchpad/
 *  Tables : launchpad_diagnostic, parcours_progression
 *  actions: diagnostic-get, diagnostic-save, parcours-list, step-save, step-get, progress
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];

if ($action==='diagnostic-get') {
    try { $stmt=$pdo->query("SELECT * FROM launchpad_diagnostic ORDER BY created_at DESC LIMIT 1"); $row=$stmt->fetch();
        return ['success'=>true,'diagnostic'=>$row]; } catch(Exception $e) { return ['success'=>true,'diagnostic'=>null]; }
}

if ($action==='diagnostic-save' && $method==='POST') {
    $fields=['admin_id'=>$ctx['admin_id'],'parcours_principal'=>$p['route']??$p['parcours_principal']??'A',
        'answers'=>is_array($p['answers']??null)?json_encode($p['answers']):($p['answers']??'{}'),
        'score'=>(int)($p['score']??0),'notes'=>$p['notes']??null];
    $cols=array_keys($fields);
    $pdo->prepare("INSERT INTO launchpad_diagnostic (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")")->execute(array_values($fields));
    return ['success'=>true,'id'=>(int)$pdo->lastInsertId()];
}

if ($action==='parcours-list') {
    $parcours=['A'=>'Vendeurs','B'=>'Acheteurs','C'=>'Conversion','D'=>'Organisation','E'=>'Scale'];
    $progress=[];
    try {
        $stmt=$pdo->query("SELECT parcours, COUNT(*) as total, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed FROM parcours_progression GROUP BY parcours");
        while ($r=$stmt->fetch()) $progress[$r['parcours']]=['total'=>(int)$r['total'],'completed'=>(int)$r['completed']];
    } catch(Exception $e) {}
    return ['success'=>true,'parcours'=>$parcours,'progress'=>$progress];
}

if ($action==='step-save' && $method==='POST') {
    $pdo->prepare("INSERT INTO parcours_progression (admin_id,parcours,step_id,status,data,created_at) VALUES (?,?,?,?,?,NOW())
        ON DUPLICATE KEY UPDATE status=VALUES(status),data=VALUES(data)")
        ->execute([$ctx['admin_id'],$p['parcours']??'A',$p['step_id']??'',$p['status']??'completed',
            is_array($p['data']??null)?json_encode($p['data']):($p['data']??null)]);
    return ['success'=>true];
}

if ($action==='progress') {
    $parcours=$p['parcours']??'A';
    try { $stmt=$pdo->prepare("SELECT * FROM parcours_progression WHERE admin_id=? AND parcours=? ORDER BY step_id ASC");
        $stmt->execute([$ctx['admin_id'],$parcours]);
        return ['success'=>true,'steps'=>$stmt->fetchAll()]; } catch(Exception $e) { return ['success'=>true,'steps'=>[]]; }
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['diagnostic-get','diagnostic-save','parcours-list','step-save','progress']];
