<?php
/**
 *  /admin/api/content/secteurs.php
 *  CRUD Quartiers / Secteurs
 *  Miroir de : modules/content/secteurs/
 *  Table : secteurs
 *
 *  actions: list, get, save, delete
 */
$pdo = $ctx['pdo']; $action = $ctx['action']; $method = $ctx['method']; $p = $ctx['params'];

$table = 'secteurs';
$col = ($table === 'secteurs') ? 'nom' : (($table === 'captures' || $table === 'capture_pages') ? 'name' : 'title');

if ($action === 'list') {
    $stmt = $pdo->query("SELECT * FROM secteurs ORDER BY {$col} ASC");
    return ['success'=>true,'secteurs'=>$stmt->fetchAll()];
}

if ($action === 'get') {
    $stmt = $pdo->prepare("SELECT * FROM secteurs WHERE id=?");
    $stmt->execute([(int)($p['id']??0)]);
    $row = $stmt->fetch();
    if (!$row) return ['success'=>false,'error'=>'Secteur non trouvé','_http_code'=>404];
    return ['success'=>true,'secteur'=>$row];
}

if ($action === 'save' && $method === 'POST') {
    $id = (int)($p['id']??0);
    $fields = [
        'nom'              => $p['nom'] ?? '',
        'slug'             => $p['slug'] ?? strtolower(trim(preg_replace('/[^a-z0-9]+/i','-',$p['nom']??''),'-')),
        'ville'            => $p['ville'] ?? 'Bordeaux',
        'description'      => $p['description'] ?? '',
        'content'          => $p['content'] ?? '',
        'meta_title'       => $p['meta_title'] ?? '',
        'meta_description' => $p['meta_description'] ?? '',
        'hero_image'       => $p['hero_image'] ?? null,
        'latitude'         => $p['latitude'] ?? null,
        'longitude'        => $p['longitude'] ?? null,
        'status'           => $p['status'] ?? 'published',
    ];
    if ($id > 0) {
        $sets = []; $vals = [];
        foreach ($fields as $c => $v) { $sets[] = "`{$c}`=?"; $vals[] = $v; }
        $vals[] = $id;
        $pdo->prepare("UPDATE secteurs SET ".implode(',',$sets)." WHERE id=?")->execute($vals);
        return ['success'=>true,'message'=>'Secteur mis à jour','id'=>$id];
    }
    $cols = array_keys($fields);
    $pdo->prepare(
        "INSERT INTO secteurs (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")"
    )->execute(array_values($fields));
    return ['success'=>true,'message'=>'Secteur créé','id'=>(int)$pdo->lastInsertId()];
}

if ($action === 'delete' && $method === 'POST') {
    $pdo->prepare("DELETE FROM secteurs WHERE id=?")->execute([(int)($p['id']??0)]);
    return ['success'=>true,'message'=>'Supprimé'];
}

return [
    'success'    => false,
    'error'      => "Action '{$action}' non reconnue",
    '_http_code' => 404,
    'actions'    => ['list','get','save','delete']
];