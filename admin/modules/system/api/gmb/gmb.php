<?php
/**
 * API GMB — Google My Business Scraper
 * Actions : search, get, export, convert, convert_bulk, delete_result, bulk_delete, delete_search
 *           list_campaigns, create_campaign, update_campaign, delete_campaign, get_campaign
 *           add_to_campaign, remove_from_campaign
 *           scrape_email, scrape_emails_bulk, verify_email, verify_emails_bulk, update_contact
 * v5.0 — campagnes + scraping email auto + vérification SMTP/MX/regex
 */

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// ── Connexion DB ────────────────────────────────────────────────────────────
$configPath = dirname(__DIR__, 3) . '/config/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'config.php introuvable']);
    exit;
}
require_once $configPath;

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB: ' . $e->getMessage()]);
    exit;
}

// ── Création / migration des tables ─────────────────────────────────────────
$pdo->exec("
    CREATE TABLE IF NOT EXISTS gmb_searches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        query VARCHAR(255) NOT NULL,
        location VARCHAR(255),
        radius INT DEFAULT 5000,
        results_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS gmb_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        search_id INT,
        name VARCHAR(255),
        category VARCHAR(255),
        address TEXT,
        phone VARCHAR(50),
        website VARCHAR(500),
        email VARCHAR(255),
        email_verified TINYINT(1) DEFAULT 0,
        email_verification_status ENUM('unverified','valid','invalid','generic','smtp_fail','mx_fail') DEFAULT 'unverified',
        email_verified_at TIMESTAMP NULL,
        rating DECIMAL(3,2),
        reviews_count INT DEFAULT 0,
        latitude DECIMAL(10,8),
        longitude DECIMAL(11,8),
        place_id VARCHAR(255),
        is_converted TINYINT(1) DEFAULT 0,
        converted_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Migration colonnes manquantes
$cols = $pdo->query("SHOW COLUMNS FROM gmb_results")->fetchAll(PDO::FETCH_COLUMN);
$migrations = [
    'email'                      => "ALTER TABLE gmb_results ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER website",
    'email_verified'             => "ALTER TABLE gmb_results ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER email",
    'email_verification_status'  => "ALTER TABLE gmb_results ADD COLUMN email_verification_status ENUM('unverified','valid','invalid','generic','smtp_fail','mx_fail') DEFAULT 'unverified' AFTER email_verified",
    'email_verified_at'          => "ALTER TABLE gmb_results ADD COLUMN email_verified_at TIMESTAMP NULL AFTER email_verification_status",
    'converted_at'               => "ALTER TABLE gmb_results ADD COLUMN converted_at TIMESTAMP NULL AFTER is_converted",
];
foreach ($migrations as $col => $sql) {
    if (!in_array($col, $cols)) {
        try { $pdo->exec($sql); } catch (Exception $e) {}
    }
}

$pdo->exec("
    CREATE TABLE IF NOT EXISTS gmb_campaigns (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        target VARCHAR(255),
        city VARCHAR(255),
        status ENUM('draft','active','paused','done') DEFAULT 'draft',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$pdo->exec("
    CREATE TABLE IF NOT EXISTS gmb_campaign_contacts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        campaign_id INT NOT NULL,
        result_id INT NOT NULL,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_contact (campaign_id, result_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Lecture requête ──────────────────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input  = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $input = $raw ? (json_decode($raw, true) ?? []) : [];
    if (empty($input)) $input = $_POST;
    if (empty($action) && isset($input['action'])) $action = $input['action'];
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function getGoogleApiKey(PDO $pdo): string {
    $stmt = $pdo->prepare("SELECT api_key_encrypted FROM api_keys WHERE service_key='google_places' AND is_active=1 LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ? trim($row['api_key_encrypted']) : '';
}

/**
 * Vérifie un email : regex + blacklist génériques + MX record + SMTP ping
 */
function verifyEmail(string $email): array {
    $email = trim(strtolower($email));

    // 1. Regex
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'status' => 'invalid', 'details' => 'Format invalide'];
    }

    [$local, $domain] = explode('@', $email, 2);

    // 2. Génériques
    $genericPrefixes = ['info', 'contact', 'admin', 'hello', 'bonjour', 'noreply', 'no-reply',
                        'support', 'service', 'accueil', 'mairie', 'communication', 'secretariat',
                        'direction', 'rh', 'comptabilite', 'facturation', 'webmaster', 'web'];
    foreach ($genericPrefixes as $prefix) {
        if ($local === $prefix || str_starts_with($local, $prefix . '.') || str_starts_with($local, $prefix . '+')) {
            return ['valid' => false, 'status' => 'generic', 'details' => "Email générique ($local@...)"];
        }
    }

    // 3. MX record
    $mxRecords = [];
    if (!getmxrr($domain, $mxRecords) || empty($mxRecords)) {
        return ['valid' => false, 'status' => 'mx_fail', 'details' => "Aucun MX record pour $domain"];
    }

    // 4. SMTP ping (port 25, fallback 587)
    sort($mxRecords);
    $mxHost = $mxRecords[0];

    $sock = @fsockopen($mxHost, 25, $errno, $errstr, 8);
    if ($sock) {
        $banner = fgets($sock, 1024);
        if (str_starts_with(trim($banner), '220')) {
            fputs($sock, "HELO eduardo-desul-immobilier.fr\r\n"); fgets($sock, 1024);
            fputs($sock, "MAIL FROM:<noreply@eduardo-desul-immobilier.fr>\r\n"); fgets($sock, 1024);
            fputs($sock, "RCPT TO:<$email>\r\n");
            $resp = fgets($sock, 1024);
            $code = (int) substr(trim($resp), 0, 3);
            fputs($sock, "QUIT\r\n");
            fclose($sock);
            if ($code === 550 || $code === 551 || $code === 553) {
                return ['valid' => false, 'status' => 'smtp_fail', 'details' => "Boîte inexistante (code $code)"];
            }
        } else {
            fclose($sock);
        }
        return ['valid' => true, 'status' => 'valid', 'details' => "SMTP OK via $mxHost"];
    }

    // Port 25 bloqué (courant sur hébergements mutualisés) — valider sur MX seul
    $sock587 = @fsockopen($mxHost, 587, $e2, $es2, 5);
    if ($sock587) fclose($sock587);
    return ['valid' => true, 'status' => 'valid', 'details' => "MX OK ($mxHost) — ports bloqués par hébergeur"];
}

/**
 * Scrape l'email depuis un site web
 */
function scrapeEmailFromWebsite(string $url): ?string {
    if (empty($url)) return null;
    if (!preg_match('#^https?://#', $url)) $url = 'https://' . $url;

    $ctx = stream_context_create([
        'http' => ['timeout' => 8, 'method' => 'GET',
                   'header' => "User-Agent: Mozilla/5.0 (compatible; Bot/1.0)\r\n",
                   'follow_location' => true, 'max_redirects' => 3],
        'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);

    $genericPrefixes = ['info', 'contact', 'admin', 'hello', 'noreply', 'no-reply',
                        'support', 'service', 'accueil', 'webmaster', 'web'];
    $blacklistDomains = ['example.com', 'sentry.io', 'wixpress.com', 'squarespace.com',
                         'wordpress.com', 'shopify.com', 'amazonaws.com', 'cloudflare.com'];

    $extractEmails = function(string $html) use ($genericPrefixes, $blacklistDomains): array {
        $emails = [];
        preg_match_all('/href=["\']mailto:([^"\'?\s]+)/i', $html, $m1);
        preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', strip_tags($html), $m2);
        foreach (array_merge($m1[1], $m2[0]) as $e) {
            $e = strtolower(trim($e));
            if (!filter_var($e, FILTER_VALIDATE_EMAIL)) continue;
            [$local, $domain] = explode('@', $e, 2);
            if (in_array($domain, $blacklistDomains)) continue;
            $emails[$e] = $e;
        }
        return array_values($emails);
    };

    $html = @file_get_contents($url, false, $ctx);
    $emails = $html ? $extractEmails($html) : [];

    if (empty($emails)) {
        $html2 = @file_get_contents(rtrim($url, '/') . '/contact', false, $ctx);
        if ($html2) $emails = $extractEmails($html2);
    }

    if (empty($emails)) return null;

    // Préférer emails non-génériques
    $nonGeneric = array_filter($emails, function($e) use ($genericPrefixes) {
        $local = explode('@', $e)[0];
        foreach ($genericPrefixes as $p) {
            if (str_starts_with($local, $p)) return false;
        }
        return true;
    });

    return !empty($nonGeneric) ? reset($nonGeneric) : reset($emails);
}

// ── Router ───────────────────────────────────────────────────────────────────
switch ($action) {

    // ── Secteurs (depuis table secteurs du CMS) ──────────────────────────────
    case 'get_secteurs':
        try {
            $stmt = $pdo->query("
                SELECT id, nom, ville, slug, type_secteur
                FROM secteurs
                WHERE status = 'published' OR status = 'active' OR status = 'publié'
                ORDER BY ville ASC, nom ASC
            ");
            $secteurs = $stmt->fetchAll();
            // Fallback si aucun publié : prendre tous
            if (empty($secteurs)) {
                $secteurs = $pdo->query("SELECT id, nom, ville, slug, type_secteur FROM secteurs ORDER BY ville ASC, nom ASC")->fetchAll();
            }
        } catch (Exception $e) {
            $secteurs = [];
        }
        echo json_encode(['success' => true, 'secteurs' => $secteurs]);
        break;

    // ── Clé API ─────────────────────────────────────────────────────────────
    case 'get_api_key':
        $key = getGoogleApiKey($pdo);
        echo json_encode(['success' => true, 'api_key' => $key ? substr($key, 0, 8) . '...' : '']);
        break;

    case 'save_api_key':
        $key = trim($input['api_key'] ?? '');
        if (empty($key)) { echo json_encode(['success' => false, 'error' => 'Clé vide']); break; }
        $existing = $pdo->prepare("SELECT id FROM api_keys WHERE service_key='google_places' LIMIT 1");
        $existing->execute();
        if ($existing->fetch()) {
            $pdo->prepare("UPDATE api_keys SET api_key_encrypted=?, is_active=1, updated_at=NOW() WHERE service_key='google_places'")->execute([$key]);
        } else {
            $pdo->prepare("INSERT INTO api_keys (service_key, service_name, api_key_encrypted, category, is_active, created_at, updated_at) VALUES ('google_places','Google Places API',?,'search',1,NOW(),NOW())")->execute([$key]);
        }
        echo json_encode(['success' => true]);
        break;

    // ── Recherche ────────────────────────────────────────────────────────────
    case 'search':
        // Mode relance : charger résultats d'une recherche existante
        $reuseSearchId = (int)($input['reuse_search_id'] ?? 0);
        if ($reuseSearchId) {
            $stmt = $pdo->prepare("
                SELECT r.*,
                       (SELECT COUNT(*) FROM gmb_campaign_contacts cc WHERE cc.result_id = r.id) AS campaign_count
                FROM gmb_results r WHERE r.search_id = ? ORDER BY r.id ASC
            ");
            $stmt->execute([$reuseSearchId]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$row) {
                $row['in_campaign']    = (int)$row['campaign_count'] > 0;
                $row['campaign_count'] = (int)$row['campaign_count'];
            }
            $search = $pdo->prepare("SELECT * FROM gmb_searches WHERE id=?");
            $search->execute([$reuseSearchId]);
            $searchRow = $search->fetch();
            echo json_encode(['success' => true, 'count' => count($rows), 'search_id' => $reuseSearchId, 'results' => $rows, 'reused' => true, 'original_query' => $searchRow['query'] ?? '']);
            break;
        }

        $query    = trim($input['query'] ?? '');
        $location = trim($input['location'] ?? '');
        $radius   = (int)($input['radius'] ?? 5000);
        if (empty($query)) { echo json_encode(['success' => false, 'error' => 'Requête vide']); break; }

        $apiKey = getGoogleApiKey($pdo);
        if (empty($apiKey)) { echo json_encode(['success' => false, 'error' => 'Clé API Google non configurée']); break; }

        $fullQuery  = $query . ($location ? ' ' . $location : '');
        $allResults = [];
        $pageToken  = null;

        for ($page = 0; $page < 3; $page++) {
            $params = ['query' => $fullQuery, 'key' => $apiKey];
            if ($pageToken) $params['pagetoken'] = $pageToken;
            $resp = @file_get_contents('https://maps.googleapis.com/maps/api/place/textsearch/json?' . http_build_query($params));
            if (!$resp) break;
            $data = json_decode($resp, true);
            if (empty($data['results'])) break;
            foreach ($data['results'] as $place) {
                $allResults[] = [
                    'name'          => $place['name'] ?? '',
                    'category'      => implode(', ', array_slice($place['types'] ?? [], 0, 3)),
                    'address'       => $place['formatted_address'] ?? '',
                    'phone'         => '',
                    'website'       => '',
                    'email'         => null,
                    'rating'        => $place['rating'] ?? 0,
                    'reviews_count' => $place['user_ratings_total'] ?? 0,
                    'latitude'      => $place['geometry']['location']['lat'] ?? 0,
                    'longitude'     => $place['geometry']['location']['lng'] ?? 0,
                    'place_id'      => $place['place_id'] ?? '',
                ];
            }
            $pageToken = $data['next_page_token'] ?? null;
            if (!$pageToken) break;
            sleep(2);
        }

        // Enrichir phone/website via Place Details (max 20)
        for ($i = 0; $i < min(20, count($allResults)); $i++) {
            if (!empty($allResults[$i]['place_id'])) {
                $detailResp = @file_get_contents('https://maps.googleapis.com/maps/api/place/details/json?' . http_build_query([
                    'place_id' => $allResults[$i]['place_id'],
                    'fields'   => 'formatted_phone_number,website',
                    'key'      => $apiKey,
                ]));
                if ($detailResp) {
                    $d = json_decode($detailResp, true);
                    $allResults[$i]['phone']   = $d['result']['formatted_phone_number'] ?? '';
                    $allResults[$i]['website'] = $d['result']['website'] ?? '';
                }
            }
        }

        $stmt = $pdo->prepare("INSERT INTO gmb_searches (query, location, radius, results_count) VALUES (?,?,?,?)");
        $stmt->execute([$query, $location, $radius, count($allResults)]);
        $searchId = (int)$pdo->lastInsertId();

        $ins = $pdo->prepare("INSERT INTO gmb_results (search_id, name, category, address, phone, website, rating, reviews_count, latitude, longitude, place_id) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        foreach ($allResults as &$r) {
            $ins->execute([$searchId, $r['name'], $r['category'], $r['address'], $r['phone'], $r['website'], $r['rating'], $r['reviews_count'], $r['latitude'], $r['longitude'], $r['place_id']]);
            $r['id'] = (int)$pdo->lastInsertId();
            $r['search_id'] = $searchId;
            $r['is_converted'] = 0;
            $r['email_verified'] = 0;
            $r['email_verification_status'] = 'unverified';
            $r['in_campaign'] = false;
            $r['campaign_count'] = 0;
        }

        echo json_encode(['success' => true, 'count' => count($allResults), 'search_id' => $searchId, 'results' => $allResults]);
        break;

    // ── Historique des recherches ────────────────────────────────────────────
    case 'get_searches':
        $searches = $pdo->query("SELECT * FROM gmb_searches ORDER BY created_at DESC LIMIT 50")->fetchAll();
        $total = $pdo->query("SELECT COUNT(*) FROM gmb_results")->fetchColumn();
        $converted = $pdo->query("SELECT COUNT(*) FROM gmb_results WHERE is_converted=1")->fetchColumn();
        $inCamp = $pdo->query("SELECT COUNT(DISTINCT result_id) FROM gmb_campaign_contacts")->fetchColumn();
        $emailVerif = $pdo->query("SELECT COUNT(*) FROM gmb_results WHERE email_verification_status='valid'")->fetchColumn();
        $highRating = $pdo->query("SELECT COUNT(*) FROM gmb_results WHERE rating>=4")->fetchColumn();
        echo json_encode([
            'success' => true,
            'searches' => $searches,
            'total_searches' => count($searches),
            'total_results' => (int)$total,
            'converted' => (int)$converted,
            'in_campaign' => (int)$inCamp,
            'email_verified' => (int)$emailVerif,
            'high_rating' => (int)$highRating,
        ]);
        break;

    // ── Récupérer résultats d'une recherche ──────────────────────────────────
    case 'get':
        $searchId = (int)($_GET['search_id'] ?? $input['search_id'] ?? 0);
        if (!$searchId) {
            $searches = $pdo->query("SELECT * FROM gmb_searches ORDER BY created_at DESC")->fetchAll();
            echo json_encode(['success' => true, 'searches' => $searches]);
            break;
        }
        $stmt = $pdo->prepare("
            SELECT r.*,
                   (SELECT COUNT(*) FROM gmb_campaign_contacts cc WHERE cc.result_id = r.id) AS campaign_count
            FROM gmb_results r
            WHERE r.search_id = ?
            ORDER BY r.id ASC
        ");
        $stmt->execute([$searchId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['in_campaign']    = (int)$row['campaign_count'] > 0;
            $row['campaign_count'] = (int)$row['campaign_count'];
        }
        echo json_encode(['success' => true, 'results' => $rows]);
        break;

    // ── Export CSV ───────────────────────────────────────────────────────────
    case 'export':
        $searchId = (int)($_GET['search_id'] ?? 0);
        if ($searchId) {
            $stmt = $pdo->prepare("SELECT * FROM gmb_results WHERE search_id=?");
            $stmt->execute([$searchId]);
        } else {
            $stmt = $pdo->query("SELECT * FROM gmb_results ORDER BY created_at DESC");
        }
        $rows = $stmt->fetchAll();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="gmb-export-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($out, ['Nom', 'Catégorie', 'Adresse', 'Téléphone', 'Site web', 'Email', 'Email statut', 'Note', 'Avis', 'En campagne', 'Converti'], ';');
        foreach ($rows as $r) {
            fputcsv($out, [$r['name'], $r['category'], $r['address'], $r['phone'], $r['website'] ?? '',
                $r['email'] ?? '', $r['email_verification_status'] ?? 'unverified',
                $r['rating'], $r['reviews_count'], '', $r['is_converted'] ? 'Oui' : 'Non'], ';');
        }
        fclose($out); exit;

    // ── Conversion CRM ───────────────────────────────────────────────────────
    case 'convert':
        $resultId = (int)($input['result_id'] ?? $input['id'] ?? 0);
        if (!$resultId) { echo json_encode(['success' => false, 'error' => 'ID manquant']); break; }
        $r = $pdo->prepare("SELECT * FROM gmb_results WHERE id=?"); $r->execute([$resultId]);
        $result = $r->fetch();
        if (!$result) { echo json_encode(['success' => false, 'error' => 'Introuvable']); break; }
        try {
            $tables = $pdo->query("SHOW TABLES LIKE 'crm_contacts'")->fetchAll();
            if (!empty($tables)) {
                $pdo->prepare("INSERT IGNORE INTO crm_contacts (nom, telephone, email, source, created_at) VALUES (?,?,?,?,NOW())")
                    ->execute([$result['name'], $result['phone'], $result['email'] ?? '', 'gmb_scraper']);
            }
        } catch (Exception $e) {}
        $pdo->prepare("UPDATE gmb_results SET is_converted=1, converted_at=NOW() WHERE id=?")->execute([$resultId]);
        echo json_encode(['success' => true]);
        break;

    case 'convert_bulk':
        $ids = $input['ids'] ?? [];
        if (empty($ids)) { echo json_encode(['success' => false, 'error' => 'IDs manquants']); break; }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("UPDATE gmb_results SET is_converted=1, converted_at=NOW() WHERE id IN ($ph)")->execute($ids);
        echo json_encode(['success' => true, 'updated' => count($ids)]);
        break;

    // ── Suppression ──────────────────────────────────────────────────────────
    case 'delete_result':
        $id = (int)($input['result_id'] ?? 0);
        $pdo->prepare("DELETE FROM gmb_results WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'bulk_delete':
        $ids = $input['ids'] ?? [];
        if (empty($ids)) { echo json_encode(['success' => false]); break; }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM gmb_results WHERE id IN ($ph)")->execute($ids);
        echo json_encode(['success' => true]);
        break;

    case 'delete_search':
        $searchId = (int)($input['search_id'] ?? $_GET['search_id'] ?? 0);
        $pdo->prepare("DELETE FROM gmb_results WHERE search_id=?")->execute([$searchId]);
        $pdo->prepare("DELETE FROM gmb_searches WHERE id=?")->execute([$searchId]);
        echo json_encode(['success' => true]);
        break;

    // ── Campagnes ────────────────────────────────────────────────────────────
    case 'list_campaigns':
        $campaigns = $pdo->query("
            SELECT c.*, COUNT(cc.id) AS contact_count
            FROM gmb_campaigns c
            LEFT JOIN gmb_campaign_contacts cc ON cc.campaign_id = c.id
            GROUP BY c.id ORDER BY c.created_at DESC
        ")->fetchAll();
        echo json_encode(['success' => true, 'campaigns' => $campaigns]);
        break;

    case 'create_campaign':
        $name = trim($input['name'] ?? '');
        if (empty($name)) { echo json_encode(['success' => false, 'error' => 'Nom requis']); break; }
        $pdo->prepare("INSERT INTO gmb_campaigns (name, target, city, status, notes) VALUES (?,?,?,?,?)")
            ->execute([$name, $input['target'] ?? '', $input['city'] ?? '', $input['status'] ?? 'draft', $input['notes'] ?? '']);
        echo json_encode(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
        break;

    case 'update_campaign':
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'ID requis']); break; }
        $pdo->prepare("UPDATE gmb_campaigns SET name=?, target=?, city=?, status=?, notes=?, updated_at=NOW() WHERE id=?")
            ->execute([$input['name'] ?? '', $input['target'] ?? '', $input['city'] ?? '', $input['status'] ?? 'draft', $input['notes'] ?? '', $id]);
        echo json_encode(['success' => true]);
        break;

    case 'delete_campaign':
        $id = (int)($input['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'ID requis']); break; }
        $pdo->prepare("DELETE FROM gmb_campaign_contacts WHERE campaign_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM gmb_campaigns WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'get_campaign':
        $id = (int)($_GET['id'] ?? $input['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'ID requis']); break; }
        $stmt = $pdo->prepare("SELECT * FROM gmb_campaigns WHERE id=?"); $stmt->execute([$id]);
        $c = $stmt->fetch();
        if (!$c) { echo json_encode(['success' => false, 'error' => 'Introuvable']); break; }
        $cstmt = $pdo->prepare("
            SELECT r.*, cc.added_at AS added_to_campaign_at
            FROM gmb_results r
            JOIN gmb_campaign_contacts cc ON cc.result_id = r.id
            WHERE cc.campaign_id = ?
            ORDER BY cc.added_at DESC
        ");
        $cstmt->execute([$id]);
        $contacts = $cstmt->fetchAll();
        $c['contacts'] = $contacts;
        echo json_encode(['success' => true, 'campaign' => $c, 'contacts' => $contacts]);
        break;

    case 'add_to_campaign':
        $campaignId = (int)($input['campaign_id'] ?? 0);
        $resultIds  = $input['result_ids'] ?? (isset($input['result_id']) ? [(int)$input['result_id']] : []);
        if (!$campaignId || empty($resultIds)) {
            echo json_encode(['success' => false, 'error' => 'campaign_id et result_ids requis']); break;
        }
        $added = 0;
        $stmt = $pdo->prepare("INSERT IGNORE INTO gmb_campaign_contacts (campaign_id, result_id) VALUES (?,?)");
        foreach ($resultIds as $rid) {
            $stmt->execute([$campaignId, (int)$rid]);
            $added += $stmt->rowCount();
        }
        echo json_encode(['success' => true, 'added' => $added]);
        break;

    case 'remove_from_campaign':
        $campaignId = (int)($input['campaign_id'] ?? 0);
        $resultId   = (int)($input['result_id'] ?? 0);
        if (!$campaignId || !$resultId) {
            echo json_encode(['success' => false, 'error' => 'campaign_id et result_id requis']); break;
        }
        $pdo->prepare("DELETE FROM gmb_campaign_contacts WHERE campaign_id=? AND result_id=?")->execute([$campaignId, $resultId]);
        echo json_encode(['success' => true]);
        break;

    // ── Scraping email ───────────────────────────────────────────────────────
    case 'scrape_email':
        $resultId = (int)($input['result_id'] ?? 0);
        if (!$resultId) { echo json_encode(['success' => false, 'error' => 'result_id requis']); break; }
        $r = $pdo->prepare("SELECT website, email FROM gmb_results WHERE id=?"); $r->execute([$resultId]);
        $row = $r->fetch();
        if (!$row) { echo json_encode(['success' => false, 'error' => 'Introuvable']); break; }
        if (!empty($row['email'])) {
            echo json_encode(['success' => true, 'email' => $row['email'], 'source' => 'existing']); break;
        }
        if (empty($row['website'])) {
            echo json_encode(['success' => false, 'error' => 'Pas de site web']); break;
        }
        $email = scrapeEmailFromWebsite($row['website']);
        if ($email) {
            $pdo->prepare("UPDATE gmb_results SET email=?, email_verification_status='unverified', email_verified=0 WHERE id=?")->execute([$email, $resultId]);
            echo json_encode(['success' => true, 'email' => $email, 'source' => 'scraped']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Aucun email trouvé']);
        }
        break;

    case 'scrape_emails_bulk':
        $resultIds = $input['result_ids'] ?? [];
        $searchId  = (int)($input['search_id'] ?? 0);
        if (!empty($resultIds)) {
            $ph   = implode(',', array_fill(0, count($resultIds), '?'));
            $stmt = $pdo->prepare("SELECT id, website FROM gmb_results WHERE id IN ($ph) AND website != '' AND (email IS NULL OR email = '')");
            $stmt->execute(array_map('intval', $resultIds));
        } elseif ($searchId) {
            $stmt = $pdo->prepare("SELECT id, website FROM gmb_results WHERE search_id=? AND website != '' AND (email IS NULL OR email = '')");
            $stmt->execute([$searchId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'result_ids ou search_id requis']); break;
        }
        $rows = $stmt->fetchAll();
        $found = 0;
        $upd = $pdo->prepare("UPDATE gmb_results SET email=?, email_verification_status='unverified', email_verified=0 WHERE id=?");
        foreach ($rows as $row) {
            $email = scrapeEmailFromWebsite($row['website']);
            if ($email) { $upd->execute([$email, $row['id']]); $found++; }
            usleep(500000);
        }
        echo json_encode(['success' => true, 'scraped' => count($rows), 'found' => $found]);
        break;

    // ── Vérification email ───────────────────────────────────────────────────
    case 'verify_email':
        $resultId = (int)($input['result_id'] ?? 0);
        $email    = trim($input['email'] ?? '');
        if ($resultId && empty($email)) {
            $r = $pdo->prepare("SELECT email FROM gmb_results WHERE id=?"); $r->execute([$resultId]);
            $email = $r->fetch()['email'] ?? '';
        }
        if (empty($email)) { echo json_encode(['success' => false, 'error' => "Pas d'email à vérifier"]); break; }
        $result = verifyEmail($email);
        if ($resultId) {
            $pdo->prepare("UPDATE gmb_results SET email_verified=?, email_verification_status=?, email_verified_at=NOW() WHERE id=?")
                ->execute([$result['valid'] ? 1 : 0, $result['status'], $resultId]);
        }
        echo json_encode(array_merge(['success' => true], $result));
        break;

    case 'verify_emails_bulk':
        $campaignId = (int)($input['campaign_id'] ?? 0);
        $searchId   = (int)($input['search_id'] ?? 0);
        $resultIds  = $input['result_ids'] ?? [];
        if (!empty($resultIds)) {
            $ph   = implode(',', array_fill(0, count($resultIds), '?'));
            $stmt = $pdo->prepare("SELECT id, email FROM gmb_results WHERE id IN ($ph) AND email != '' AND email_verification_status='unverified'");
            $stmt->execute(array_map('intval', $resultIds));
        } elseif ($campaignId) {
            $stmt = $pdo->prepare("SELECT r.id, r.email FROM gmb_results r JOIN gmb_campaign_contacts cc ON cc.result_id=r.id WHERE cc.campaign_id=? AND r.email != '' AND r.email_verification_status='unverified'");
            $stmt->execute([$campaignId]);
        } elseif ($searchId) {
            $stmt = $pdo->prepare("SELECT id, email FROM gmb_results WHERE search_id=? AND email != '' AND email_verification_status='unverified'");
            $stmt->execute([$searchId]);
        } else {
            echo json_encode(['success' => false, 'error' => 'result_ids, campaign_id ou search_id requis']); break;
        }
        $rows = $stmt->fetchAll();
        $stats = ['verified' => 0, 'valid' => 0, 'invalid' => 0, 'generic' => 0];
        $upd = $pdo->prepare("UPDATE gmb_results SET email_verified=?, email_verification_status=?, email_verified_at=NOW() WHERE id=?");
        foreach ($rows as $row) {
            if (empty($row['email'])) continue;
            $v = verifyEmail($row['email']);
            $upd->execute([$v['valid'] ? 1 : 0, $v['status'], $row['id']]);
            $stats['verified']++;
            if ($v['valid']) $stats['valid']++; else $stats['invalid']++;
            if ($v['status'] === 'generic') $stats['generic']++;
            usleep(300000);
        }
        echo json_encode(array_merge(['success' => true], $stats));
        break;

    // ── Mise à jour contact (email manuel, phone) ────────────────────────────
    case 'update_contact':
        $resultId = (int)($input['result_id'] ?? 0);
        if (!$resultId) { echo json_encode(['success' => false, 'error' => 'result_id requis']); break; }
        $fields = []; $params = [];
        if (isset($input['email'])) {
            $fields[] = "email=?";
            $fields[] = "email_verification_status='unverified'";
            $fields[] = "email_verified=0";
            $params[]  = trim($input['email']);
        }
        if (isset($input['phone'])) { $fields[] = "phone=?"; $params[] = trim($input['phone']); }
        if (empty($fields)) { echo json_encode(['success' => false, 'error' => 'Rien à mettre à jour']); break; }
        $params[] = $resultId;
        $pdo->prepare("UPDATE gmb_results SET " . implode(', ', $fields) . " WHERE id=?")->execute($params);
        echo json_encode(['success' => true]);
        break;

    // ── Récupérer un contact seul ────────────────────────────────────────────
    case 'get_result':
        $resultId = (int)($input['id'] ?? $_GET['id'] ?? 0);
        if (!$resultId) { echo json_encode(['success' => false, 'error' => 'id requis']); break; }
        $row = $pdo->prepare("SELECT * FROM gmb_results WHERE id=?");
        $row->execute([$resultId]);
        $result = $row->fetch();
        echo json_encode(['success' => (bool)$result, 'result' => $result ?: null]);
        break;

    // ── Envoi email à un contact ─────────────────────────────────────────────
    case 'send_email':
        $resultId = (int)($input['result_id'] ?? 0);
        $to       = trim($input['to']      ?? '');
        $subject  = trim($input['subject'] ?? '');
        $body     = trim($input['body']    ?? '');

        if (!$to || !$subject || !$body) {
            echo json_encode(['success' => false, 'error' => 'to, subject et body requis']);
            break;
        }
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Email destinataire invalide']);
            break;
        }

        // Charger SMTP depuis config
        $smtpConfig = dirname(__DIR__, 3) . '/config/smtp.php';
        if (!file_exists($smtpConfig)) {
            echo json_encode(['success' => false, 'error' => 'smtp.php introuvable']);
            break;
        }
        require_once $smtpConfig;

        // Construire email HTML complet
        $emailHtml = '<!DOCTYPE html><html><head><meta charset="UTF-8">
        <style>
            body { font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.7; }
            .wrap { max-width: 600px; margin: 0 auto; padding: 30px 20px; }
            p { margin: 0 0 12px; }
            ul { margin: 10px 0 14px; padding-left: 20px; }
            li { margin-bottom: 6px; }
            .footer { margin-top: 30px; padding-top: 16px; border-top: 1px solid #eee; font-size: 12px; color: #888; }
        </style>
        </head><body><div class="wrap">' . $body . '</div></body></html>';

        // Envoi via PHPMailer ou mail() natif
        $sent = false;
        $errorMsg = '';

        // Tenter PHPMailer si disponible
        $phpmailerPath = dirname(__DIR__, 3) . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
        if (file_exists($phpmailerPath)) {
            require_once $phpmailerPath;
            require_once dirname($phpmailerPath) . '/SMTP.php';
            require_once dirname($phpmailerPath) . '/Exception.php';
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : '';
                $mail->SMTPAuth   = true;
                $mail->Username   = defined('SMTP_USER') ? SMTP_USER : '';
                $mail->Password   = defined('SMTP_PASS') ? SMTP_PASS : '';
                $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
                $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;
                $mail->CharSet    = 'UTF-8';
                $mail->setFrom(
                    defined('SMTP_FROM') ? SMTP_FROM : $mail->Username,
                    defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Eduardo De Sul'
                );
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $emailHtml;
                $mail->AltBody = strip_tags($body);
                $mail->send();
                $sent = true;
            } catch (Exception $e) {
                $errorMsg = $e->getMessage();
            }
        } else {
            // Fallback : mail() natif
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: Eduardo De Sul <contact@eduardo-desul-immobilier.fr>\r\n";
            $headers .= "Reply-To: contact@eduardo-desul-immobilier.fr\r\n";
            $sent = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $emailHtml, $headers);
            if (!$sent) $errorMsg = 'Échec mail() natif — vérifiez la config SMTP';
        }

        if ($sent) {
            // Logger l'envoi si table existe
            try {
                $pdo->prepare("
                    CREATE TABLE IF NOT EXISTS gmb_email_sent (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        result_id INT,
                        to_email VARCHAR(255),
                        subject VARCHAR(500),
                        sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX(result_id)
                    )
                ")->execute();
                $pdo->prepare("INSERT INTO gmb_email_sent (result_id, to_email, subject) VALUES (?,?,?)")
                   ->execute([$resultId ?: null, $to, $subject]);
            } catch (Exception $e) { /* silent */ }

            echo json_encode(['success' => true, 'message' => "Email envoyé à $to"]);
        } else {
            echo json_encode(['success' => false, 'error' => $errorMsg ?: 'Échec envoi email']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "Action inconnue: $action"]);
}