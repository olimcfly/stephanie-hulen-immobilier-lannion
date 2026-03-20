<?php
/**
 *  /admin/api/system/upload.php
 *  Upload de fichiers (images, documents)
 *  actions: image, document, delete
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];

require_once __DIR__ . '/../../../../../includes/functions/security.php';

$uploadsDir = realpath(__DIR__ . '/../../../uploads');

if (($action==='image' || $action==='document') && $method==='POST') {
    if (empty($ctx['files']['file'])) return ['success'=>false,'error'=>'Aucun fichier envoyé'];
    $file = $ctx['files']['file'];

    // Validation centralisée (MIME réel, taille, renommage sécurisé)
    $category = $action === 'image' ? 'image' : 'document';
    $validation = validateUpload($file, $category);
    if (!$validation['valid']) return ['success'=>false,'error'=>$validation['error']];

    $subDir = $action === 'image' ? 'images' : 'documents';
    $destDir = $uploadsDir . '/' . $subDir . '/' . date('Y/m');
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $filename = $validation['safe_name'];
    $destPath = $destDir . '/' . $filename;
    move_uploaded_file($file['tmp_name'], $destPath);

    $relPath = "/uploads/{$subDir}/" . date('Y/m') . "/{$filename}";

    // Log in media table if exists
    try { $pdo->prepare("INSERT INTO media (filename,path,type,size,uploaded_by,created_at) VALUES (?,?,?,?,?,NOW())")
        ->execute([$file['name'],$relPath,$validation['mime'],$file['size'],$ctx['admin_id']]); } catch(Exception $e) {}

    return ['success'=>true,'url'=>$relPath,'filename'=>$filename,'size'=>$file['size'],'type'=>$validation['mime']];
}

if ($action==='delete' && $method==='POST') {
    $path = $p['path'] ?? '';
    $fullPath = realpath($uploadsDir . '/' . ltrim(str_replace('/uploads/', '', $path), '/'));
    if (!$fullPath || strpos($fullPath, $uploadsDir) !== 0) return ['success'=>false,'error'=>'Chemin invalide'];
    if (file_exists($fullPath)) unlink($fullPath);
    try { $pdo->prepare("DELETE FROM media WHERE path=?")->execute([$path]); } catch(Exception $e) {}
    return ['success'=>true,'message'=>'Fichier supprimé'];
}

return ['success'=>false,'error'=>"Action '{$action}' non reconnue",'_http_code'=>404,
    'actions'=>['image','document','delete']];
