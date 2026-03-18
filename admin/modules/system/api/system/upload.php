<?php
/**
 *  /admin/api/system/upload.php
 *  Upload de fichiers (images, documents)
 *  actions: image, document, delete
 */
$pdo=$ctx['pdo']; $action=$ctx['action']; $method=$ctx['method']; $p=$ctx['params'];

$uploadsDir = realpath(__DIR__ . '/../../../uploads');
$allowedImages = ['jpg','jpeg','png','gif','webp','svg'];
$allowedDocs   = ['pdf','doc','docx','xls','xlsx','csv'];
$maxSize = 10 * 1024 * 1024; // 10MB

if (($action==='image' || $action==='document') && $method==='POST') {
    if (empty($ctx['files']['file'])) return ['success'=>false,'error'=>'Aucun fichier envoyé'];
    $file = $ctx['files']['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) return ['success'=>false,'error'=>'Erreur upload: '.$file['error']];
    if ($file['size'] > $maxSize) return ['success'=>false,'error'=>'Fichier trop volumineux (max 10MB)'];

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = $action === 'image' ? $allowedImages : $allowedDocs;
    if (!in_array($ext, $allowed)) return ['success'=>false,'error'=>"Extension .{$ext} non autorisée"];

    $subDir = $action === 'image' ? 'images' : 'documents';
    $destDir = $uploadsDir . '/' . $subDir . '/' . date('Y/m');
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);

    $filename = uniqid() . '_' . preg_replace('/[^a-z0-9._-]/i', '', $file['name']);
    $destPath = $destDir . '/' . $filename;
    move_uploaded_file($file['tmp_name'], $destPath);

    $relPath = "/uploads/{$subDir}/" . date('Y/m') . "/{$filename}";

    // Log in media table if exists
    try { $pdo->prepare("INSERT INTO media (filename,path,type,size,uploaded_by,created_at) VALUES (?,?,?,?,?,NOW())")
        ->execute([$file['name'],$relPath,$file['type'],$file['size'],$ctx['admin_id']]); } catch(Exception $e) {}

    return ['success'=>true,'url'=>$relPath,'filename'=>$filename,'size'=>$file['size'],'type'=>$file['type']];
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
