<?php
/**
 * ══════════════════════════════════════════════════════════════
 * API LEADS — Endpoint AJAX standalone
 * /admin/modules/marketing/leads/api.php
 * ÉCOSYSTÈME IMMO LOCAL+
 *
 * Appelé en XHR depuis index.php du module leads
 * Actions : list, get, save, delete, convert-to-contact, assign-score
 * ══════════════════════════════════════════════════════════════
 */

header('Content-Type: application/json; charset=utf-8');

// ── Bootstrap ─────────────────────────────────────────────
$rootPath = '/home/mahe6420/public_html';
if (!defined('DB_HOST'))       require_once $rootPath . '/config/config.php';
if (!class_exists('Database')) require_once $rootPath . '/includes/classes/Database.php';

// ── Session & auth ────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// ── PDO ───────────────────────────────────────────────────
try {
    $pdo = Database::getInstance()->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB: ' . $e->getMessage()]);
    exit;
}

// ── Routing ───────────────────────────────────────────────
$method = $_SERVER['REQUEST_METHOD'];
$p      = $method === 'POST' ? array_merge($_GET, $_POST) : $_GET;
$action = trim($p['action'] ?? '');

// ── Helper réponse ────────────────────────────────────────
function apiJson(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ══════════════════════════════════════════════════════════
// ACTION : list
// ══════════════════════════════════════════════════════════
if ($action === 'list') {
    try {
        $sql    = "SELECT l.*, cp.name AS capture_name
                   FROM leads l
                   LEFT JOIN captures cp ON l.capture_page_id = cp.id
                   WHERE 1=1";
        $params = [];

        if (!empty($p['status'])) {
            $sql .= " AND l.status = ?";
            $params[] = $p['status'];
        }
        if (!empty($p['source'])) {
            $sql .= " AND l.source = ?";
            $params[] = $p['source'];
        }
        if (!empty($p['search'])) {
            $s       = '%' . $p['search'] . '%';
            $sql    .= " AND (l.first_name LIKE ? OR l.last_name LIKE ? OR l.email LIKE ?)";
            $params  = array_merge($params, [$s, $s, $s]);
        }

        $limit  = min((int)($p['limit'] ?? 50), 200);
        $offset = max((int)($p['offset'] ?? 0), 0);
        $sql   .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Comptage total (sans limit)
        $countSql    = "SELECT COUNT(*) FROM leads WHERE 1=1";
        $countParams = [];
        if (!empty($p['status'])) { $countSql .= " AND status = ?"; $countParams[] = $p['status']; }
        if (!empty($p['source'])) { $countSql .= " AND source = ?"; $countParams[] = $p['source']; }
        if (!empty($p['search'])) {
            $s           = '%' . $p['search'] . '%';
            $countSql   .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $countParams = array_merge($countParams, [$s, $s, $s]);
        }
        $total = (int)$pdo->prepare($countSql)->execute($countParams)
            ? (int)$pdo->query($countSql)->fetchColumn()
            : 0;

        // Comptage total propre
        $cStmt = $pdo->prepare($countSql);
        $cStmt->execute($countParams);
        $total = (int)$cStmt->fetchColumn();

        apiJson(['success' => true, 'leads' => $leads, 'total' => $total, 'limit' => $limit, 'offset' => $offset]);
    } catch (Exception $e) {
        apiJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// ══════════════════════════════════════════════════════════
// ACTION : get
// ══════════════════════════════════════════════════════════
if ($action === 'get') {
    $id = (int)($p['id'] ?? 0);
    if (!$id) apiJson(['success' => false, 'error' => 'id requis'], 400);
    try {
        $stmt = $pdo->prepare("SELECT l.*, ls.score_total, ls.grade
                               FROM leads l
                               LEFT JOIN lead_scoring ls ON ls.lead_id = l.id
                               WHERE l.id = ?");
        $stmt->execute([$id]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$lead) apiJson(['success' => false, 'error' => 'Lead non trouvé'], 404);
        apiJson(['success' => true, 'lead' => $lead]);
    } catch (Exception $e) {
        apiJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// ══════════════════════════════════════════════════════════
// ACTION : save (create ou update)
// ══════════════════════════════════════════════════════════
if ($action === 'save' && $method === 'POST') {
    $id = (int)($p['id'] ?? 0);

    $fields = [
        'email'           => trim($p['email']           ?? ''),
        'phone'           => trim($p['phone']           ?? '') ?: null,
        'first_name'      => trim($p['first_name']      ?? ''),
        'last_name'       => trim($p['last_name']       ?? ''),
        'source'          => trim($p['source']          ?? '') ?: null,
        'capture_page_id' => ($p['capture_page_id'] ?? '') ? (int)$p['capture_page_id'] : null,
        'status'          => trim($p['status']          ?? 'new'),
        'notes'           => trim($p['notes']           ?? '') ?: null,
        'gdpr_consent'    => (int)($p['gdpr_consent']   ?? 0),
    ];

    if (empty($fields['email'])) apiJson(['success' => false, 'error' => 'Email requis'], 400);

    try {
        if ($id > 0) {
            $sets   = [];
            $values = [];
            foreach ($fields as $col => $val) { $sets[] = "`{$col}` = ?"; $values[] = $val; }
            $values[] = $id;
            $pdo->prepare("UPDATE leads SET " . implode(', ', $sets) . " WHERE id = ?")->execute($values);
            apiJson(['success' => true, 'id' => $id, 'message' => 'Lead mis à jour']);
        } else {
            $cols = array_keys($fields);
            $pdo->prepare(
                "INSERT INTO leads (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', array_fill(0, count($cols), '?')) . ")"
            )->execute(array_values($fields));
            apiJson(['success' => true, 'id' => (int)$pdo->lastInsertId(), 'message' => 'Lead créé']);
        }
    } catch (Exception $e) {
        apiJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// ══════════════════════════════════════════════════════════
// ACTION : delete
// ══════════════════════════════════════════════════════════
if ($action === 'delete' && $method === 'POST') {
    $id = (int)($p['id'] ?? 0);
    if (!$id) apiJson(['success' => false, 'error' => 'id requis'], 400);
    try {
        $pdo->prepare("DELETE FROM lead_scoring WHERE lead_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM leads WHERE id = ?")->execute([$id]);
        apiJson(['success' => true, 'message' => 'Lead supprimé']);
    } catch (Exception $e) {
        apiJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// ══════════════════════════════════════════════════════════
// ACTION : convert-to-contact
// ══════════════════════════════════════════════════════════
if ($action === 'convert-to-contact' && $method === 'POST') {
    $id = (int)($p['id'] ?? 0);
    if (!$id) apiJson(['success' => false, 'error' => 'id requis'], 400);
    try {
        $stmt = $pdo->prepare("SELECT * FROM leads WHERE id = ?");
        $stmt->execute([$id]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$lead) apiJson(['success' => false, 'error' => 'Lead non trouvé'], 404);

        // Vérifier doublon email dans contacts
        $dup = $pdo->prepare("SELECT id FROM contacts WHERE email = ?");
        $dup->execute([$lead['email']]);
        if ($existing = $dup->fetchColumn()) {
            // Marquer converti sans recréer
            $pdo->prepare("UPDATE leads SET status = 'converted' WHERE id = ?")->execute([$id]);
            apiJson(['success' => true, 'contact_id' => (int)$existing, 'message' => 'Contact existant — lead marqué converti']);
        }

        $pdo->prepare(
            "INSERT INTO contacts (first_name, last_name, email, phone, source, notes, gdpr_consent, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        )->execute([$lead['first_name'], $lead['last_name'], $lead['email'], $lead['phone'], $lead['source'], $lead['notes'], $lead['gdpr_consent']]);

        $contactId = (int)$pdo->lastInsertId();
        $pdo->prepare("UPDATE leads SET status = 'converted' WHERE id = ?")->execute([$id]);

        apiJson(['success' => true, 'contact_id' => $contactId, 'message' => 'Lead converti en contact']);
    } catch (Exception $e) {
        apiJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// ══════════════════════════════════════════════════════════
// ACTION : assign-score
// ══════════════════════════════════════════════════════════
if ($action === 'assign-score' && $method === 'POST') {
    $leadId = (int)($p['lead_id'] ?? $p['id'] ?? 0);
    if (!$leadId) apiJson(['success' => false, 'error' => 'lead_id requis'], 400);
    try {
        $pdo->prepare(
            "INSERT INTO lead_scoring
             (lead_id, score_total, score_budget, score_authority, score_need, score_timing, grade, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
             score_total     = VALUES(score_total),
             score_budget    = VALUES(score_budget),
             score_authority = VALUES(score_authority),
             score_need      = VALUES(score_need),
             score_timing    = VALUES(score_timing),
             grade           = VALUES(grade),
             notes           = VALUES(notes)"
        )->execute([
            $leadId,
            (int)($p['score_total']     ?? 0),
            (int)($p['score_budget']    ?? 0),
            (int)($p['score_authority'] ?? 0),
            (int)($p['score_need']      ?? 0),
            (int)($p['score_timing']    ?? 0),
            $p['grade'] ?? 'F',
            $p['notes'] ?? null,
        ]);
        apiJson(['success' => true, 'message' => 'Score assigné']);
    } catch (Exception $e) {
        apiJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

// ══════════════════════════════════════════════════════════
// ACTION inconnue
// ══════════════════════════════════════════════════════════
apiJson([
    'success' => false,
    'error'   => "Action '{$action}' non reconnue",
    'actions' => ['list', 'get', 'save', 'delete', 'convert-to-contact', 'assign-score'],
], 404);