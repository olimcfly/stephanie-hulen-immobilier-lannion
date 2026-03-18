<?php
/**
 * API Handler: media
 * Called via: /admin/api/router.php?module=media&action=...
 * Media file management (uploads directory)
 * Table: media (if exists)
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

$uploadsDir = dirname(__DIR__, 3) . '/uploads/';

// Check if media table exists
$mediaTableExists = false;
try { $pdo->query("SELECT 1 FROM media LIMIT 1"); $mediaTableExists = true; } catch (PDOException $e) {}

switch ($action) {
    case 'list':
        try {
            if ($mediaTableExists) {
                $type = $input['type'] ?? $_GET['type'] ?? '';
                $page = max(1, (int)($input['page'] ?? $_GET['page'] ?? 1));
                $perPage = (int)($input['per_page'] ?? $_GET['per_page'] ?? 30);
                $offset = ($page - 1) * $perPage;

                $where = ''; $params = [];
                if ($type) { $where = 'WHERE type = ?'; $params[] = $type; }

                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM media {$where}");
                $countStmt->execute($params);
                $total = (int)$countStmt->fetchColumn();

                $params[] = $perPage; $params[] = $offset;
                $stmt = $pdo->prepare("SELECT * FROM media {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?");
                $stmt->execute($params);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total]);
            } else {
                // List from filesystem
                $files = [];
                if (is_dir($uploadsDir)) {
                    $iterator = new DirectoryIterator($uploadsDir);
                    foreach ($iterator as $file) {
                        if ($file->isDot() || $file->isDir()) continue;
                        $files[] = [
                            'name' => $file->getFilename(),
                            'path' => '/uploads/' . $file->getFilename(),
                            'size' => $file->getSize(),
                            'type' => mime_content_type($file->getPathname()),
                            'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
                        ];
                    }
                    usort($files, fn($a, $b) => strcmp($b['modified_at'], $a['modified_at']));
                }
                echo json_encode(['success' => true, 'data' => $files, 'source' => 'filesystem']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            if ($mediaTableExists) {
                $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
                $stmt = $pdo->prepare("SELECT * FROM media WHERE id = ?");
                $stmt->execute([$id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Media non trouve']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Table media non disponible']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'upload':
        try {
            if (empty($_FILES['file'])) {
                echo json_encode(['success' => false, 'message' => 'Aucun fichier envoye']);
                break;
            }
            $file = $_FILES['file'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'application/pdf', 'video/mp4', 'video/webm'];
            if (!in_array($file['type'], $allowedTypes)) {
                echo json_encode(['success' => false, 'message' => 'Type de fichier non autorise']);
                break;
            }

            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($file['size'] > $maxSize) {
                echo json_encode(['success' => false, 'message' => 'Fichier trop volumineux (max 10MB)']);
                break;
            }

            if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName = uniqid('media_') . '.' . $ext;
            $dest = $uploadsDir . $safeName;

            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                echo json_encode(['success' => false, 'message' => 'Erreur lors du telechargement']);
                break;
            }

            $mediaData = [
                'filename' => $safeName,
                'original_name' => $file['name'],
                'path' => '/uploads/' . $safeName,
                'type' => strpos($file['type'], 'image') !== false ? 'image' : (strpos($file['type'], 'video') !== false ? 'video' : 'document'),
                'mime_type' => $file['type'],
                'size' => $file['size'],
            ];

            if ($mediaTableExists) {
                $stmt = $pdo->prepare("INSERT INTO media (filename, original_name, path, type, mime_type, size, alt_text) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$mediaData['filename'], $mediaData['original_name'], $mediaData['path'], $mediaData['type'], $mediaData['mime_type'], $mediaData['size'], $input['alt_text'] ?? '']);
                $mediaData['id'] = $pdo->lastInsertId();
            }

            echo json_encode(['success' => true, 'message' => 'Fichier telecharge', 'data' => $mediaData]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            if (!$mediaTableExists) { echo json_encode(['success' => false, 'message' => 'Table media non disponible']); break; }
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['alt_text', 'title', 'caption', 'description'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE media SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Media mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            if ($mediaTableExists && $id) {
                $stmt = $pdo->prepare("SELECT path FROM media WHERE id = ?");
                $stmt->execute([$id]);
                $path = $stmt->fetchColumn();
                if ($path) {
                    $fullPath = dirname(__DIR__, 3) . $path;
                    if (file_exists($fullPath)) unlink($fullPath);
                }
                $pdo->prepare("DELETE FROM media WHERE id = ?")->execute([$id]);
            } elseif (!empty($input['filename'])) {
                $filename = basename($input['filename']);
                $fullPath = $uploadsDir . $filename;
                if (file_exists($fullPath)) unlink($fullPath);
            }
            echo json_encode(['success' => true, 'message' => 'Media supprime']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            if ($mediaTableExists) {
                $stats = [
                    'total' => (int)$pdo->query("SELECT COUNT(*) FROM media")->fetchColumn(),
                    'images' => (int)$pdo->query("SELECT COUNT(*) FROM media WHERE type = 'image'")->fetchColumn(),
                    'videos' => (int)$pdo->query("SELECT COUNT(*) FROM media WHERE type = 'video'")->fetchColumn(),
                    'documents' => (int)$pdo->query("SELECT COUNT(*) FROM media WHERE type = 'document'")->fetchColumn(),
                    'total_size' => (int)$pdo->query("SELECT COALESCE(SUM(size), 0) FROM media")->fetchColumn(),
                ];
            } else {
                $totalSize = 0; $count = 0;
                if (is_dir($uploadsDir)) {
                    $iterator = new DirectoryIterator($uploadsDir);
                    foreach ($iterator as $file) {
                        if ($file->isDot() || $file->isDir()) continue;
                        $totalSize += $file->getSize();
                        $count++;
                    }
                }
                $stats = ['total' => $count, 'total_size' => $totalSize];
            }
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
