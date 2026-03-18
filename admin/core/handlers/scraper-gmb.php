<?php
/**
 * API Handler: scraper-gmb
 * Called via: /admin/api/router.php?module=scraper-gmb&action=...
 * Tables: gmb_searches, gmb_results, gmb_contacts
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    // ── JS action: 'search' (from doSearch) ──
    case 'search':
    case 'create_search':
        try {
            $query    = trim($input['query'] ?? '');
            $location = trim($input['location'] ?? '');
            $radius   = (int)($input['radius'] ?? 5000);
            $category = trim($input['category'] ?? '');
            $limit    = (int)($input['limit'] ?? 50);
            $status   = $input['status'] ?? 'completed';

            if (!$query || !$location) {
                echo json_encode(['success' => false, 'message' => 'Requete et localisation requises']);
                break;
            }

            $stmt = $pdo->prepare("INSERT INTO gmb_searches (query, location, radius, results_count, created_at) VALUES (?, ?, ?, 0, NOW())");
            $stmt->execute([$query, $location, $radius]);
            $searchId = $pdo->lastInsertId();

            echo json_encode([
                'success'   => true,
                'message'   => 'Recherche creee',
                'count'     => 0,
                'search_id' => (int)$searchId,
                'id'        => (int)$searchId
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'list':
    case 'searches':
        try {
            $stmt = $pdo->query("SELECT s.*, (SELECT COUNT(*) FROM gmb_results WHERE search_id = s.id) as results_count FROM gmb_searches s ORDER BY s.created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_search':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM gmb_searches WHERE id = ?");
            $stmt->execute([$id]);
            $search = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($search) {
                $resultsStmt = $pdo->prepare("SELECT * FROM gmb_results WHERE search_id = ? ORDER BY rating DESC, reviews_count DESC");
                $resultsStmt->execute([$id]);
                $search['results'] = $resultsStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            echo json_encode($search ? ['success' => true, 'data' => $search] : ['success' => false, 'message' => 'Recherche non trouvee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_search':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM gmb_results WHERE search_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM gmb_searches WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Recherche et resultats supprimes']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'results':
        try {
            $searchId = (int)($input['search_id'] ?? $_GET['search_id'] ?? 0);
            $where = ''; $params = [];
            if ($searchId) { $where = 'WHERE search_id = ?'; $params[] = $searchId; }
            $stmt = $pdo->prepare("SELECT * FROM gmb_results {$where} ORDER BY rating DESC, reviews_count DESC");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'save_results':
        try {
            $searchId = (int)($input['search_id'] ?? 0);
            $results = $input['results'] ?? [];
            $inserted = 0;
            $stmt = $pdo->prepare("INSERT INTO gmb_results (search_id, name, category, address, phone, website, email, rating, reviews_count, place_id, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($results as $r) {
                $stmt->execute([
                    $searchId, $r['name'] ?? '', $r['category'] ?? '', $r['address'] ?? '',
                    $r['phone'] ?? '', $r['website'] ?? '', $r['email'] ?? '',
                    (float)($r['rating'] ?? 0), (int)($r['reviews_count'] ?? 0),
                    $r['place_id'] ?? '', $r['latitude'] ?? null, $r['longitude'] ?? null
                ]);
                $inserted++;
            }
            $pdo->prepare("UPDATE gmb_searches SET results_count = ? WHERE id = ?")->execute([$inserted, $searchId]);
            echo json_encode(['success' => true, 'message' => "{$inserted} resultats sauvegardes"]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ── JS action: 'get' (from viewDetails) ──
    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM gmb_results WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                echo json_encode(['success' => true, 'result' => $row, 'data' => $row]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Resultat non trouve']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ── JS action: 'convert' (from convertOne) ──
    case 'convert':
    case 'convert_to_contact':
        try {
            $resultId = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM gmb_results WHERE id = ?");
            $stmt->execute([$resultId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$result) { echo json_encode(['success' => false, 'message' => 'Resultat non trouve']); break; }

            // Ensure gmb_contacts table exists
            try {
                $pdo->query("SELECT 1 FROM gmb_contacts LIMIT 1");
            } catch (Exception $e) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS gmb_contacts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255),
                    email VARCHAR(255) DEFAULT '',
                    phone VARCHAR(50) DEFAULT '',
                    website VARCHAR(500) DEFAULT '',
                    category VARCHAR(255) DEFAULT '',
                    address TEXT,
                    city VARCHAR(255) DEFAULT '',
                    rating DECIMAL(2,1) DEFAULT 0,
                    reviews_count INT DEFAULT 0,
                    source_search_id INT DEFAULT NULL,
                    source_result_id INT DEFAULT NULL,
                    status VARCHAR(50) DEFAULT 'new',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }

            $ins = $pdo->prepare("INSERT INTO gmb_contacts (name, email, phone, website, category, address, city, rating, reviews_count, source_search_id, source_result_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')");
            $ins->execute([
                $result['name'], $result['email'] ?? '', $result['phone'] ?? '',
                $result['website'] ?? '', $result['category'] ?? '', $result['address'] ?? '',
                '', (float)($result['rating'] ?? 0), (int)($result['reviews_count'] ?? 0),
                $result['search_id'], $resultId
            ]);

            $pdo->prepare("UPDATE gmb_results SET is_converted = 1 WHERE id = ?")->execute([$resultId]);
            echo json_encode(['success' => true, 'message' => 'Converti en contact', 'contact_id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ── JS action: 'convert_bulk' (from convertSelected) ──
    case 'convert_bulk':
    case 'bulk_convert':
        try {
            $ids = $input['ids'] ?? [];

            // Handle JSON-stringified ids (JS sends JSON.stringify(ids))
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                if (is_array($decoded)) {
                    $ids = $decoded;
                } else {
                    // Try comma-separated
                    $ids = array_filter(explode(',', $ids));
                }
            }

            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'Aucun ID fourni']);
                break;
            }

            // Ensure gmb_contacts table exists
            try {
                $pdo->query("SELECT 1 FROM gmb_contacts LIMIT 1");
            } catch (Exception $e) {
                $pdo->exec("CREATE TABLE IF NOT EXISTS gmb_contacts (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255),
                    email VARCHAR(255) DEFAULT '',
                    phone VARCHAR(50) DEFAULT '',
                    website VARCHAR(500) DEFAULT '',
                    category VARCHAR(255) DEFAULT '',
                    address TEXT,
                    city VARCHAR(255) DEFAULT '',
                    rating DECIMAL(2,1) DEFAULT 0,
                    reviews_count INT DEFAULT 0,
                    source_search_id INT DEFAULT NULL,
                    source_result_id INT DEFAULT NULL,
                    status VARCHAR(50) DEFAULT 'new',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_status (status)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            }

            $converted = 0;
            foreach ($ids as $resultId) {
                $resultId = (int)$resultId;
                $stmt = $pdo->prepare("SELECT * FROM gmb_results WHERE id = ? AND is_converted = 0");
                $stmt->execute([$resultId]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$result) continue;

                $ins = $pdo->prepare("INSERT INTO gmb_contacts (name, email, phone, website, category, address, rating, reviews_count, source_search_id, source_result_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new')");
                $ins->execute([$result['name'], $result['email'] ?? '', $result['phone'] ?? '', $result['website'] ?? '', $result['category'] ?? '', $result['address'] ?? '', (float)($result['rating'] ?? 0), (int)($result['reviews_count'] ?? 0), $result['search_id'], $resultId]);
                $pdo->prepare("UPDATE gmb_results SET is_converted = 1 WHERE id = ?")->execute([$resultId]);
                $converted++;
            }
            echo json_encode(['success' => true, 'message' => "{$converted} resultats convertis en contacts", 'converted' => $converted]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ── JS action: 'export' (from exportCSV) ──
    case 'export':
        try {
            $idsStr = $_GET['ids'] ?? $input['ids'] ?? '';
            if (is_string($idsStr)) {
                $ids = array_filter(array_map('intval', explode(',', $idsStr)));
            } else {
                $ids = array_map('intval', (array)$idsStr);
            }

            if (empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'Aucun ID fourni']);
                break;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT * FROM gmb_results WHERE id IN ({$placeholders})");
            $stmt->execute($ids);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Output CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="gmb-export-' . date('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Remove JSON content-type set by router
            $out = fopen('php://output', 'w');
            // BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");
            // Header
            fputcsv($out, ['Nom', 'Categorie', 'Adresse', 'Telephone', 'Site web', 'Email', 'Note', 'Avis', 'Latitude', 'Longitude', 'Converti'], ';');
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r['name'] ?? '',
                    $r['category'] ?? '',
                    $r['address'] ?? '',
                    $r['phone'] ?? '',
                    $r['website'] ?? '',
                    $r['email'] ?? '',
                    $r['rating'] ?? '',
                    $r['reviews_count'] ?? '',
                    $r['latitude'] ?? '',
                    $r['longitude'] ?? '',
                    $r['is_converted'] ? 'Oui' : 'Non'
                ], ';');
            }
            fclose($out);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = [
                'total_searches' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_searches")->fetchColumn(),
                'total_results' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_results")->fetchColumn(),
                'converted' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_results WHERE is_converted = 1")->fetchColumn(),
                'pending_searches' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_searches WHERE status = 'pending'")->fetchColumn(),
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
