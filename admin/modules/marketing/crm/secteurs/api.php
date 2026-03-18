<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE CMS SECTEURS — API v1.0
 * /admin/modules/cms/secteurs/api.php
 * Actions : delete, bulk_delete, bulk_status, toggle_status,
 *           duplicate, export (CSV)
 * Réponse : JSON {success, message|error, data?}
 * ══════════════════════════════════════════════════════════════
 */

// ─── CORS / Headers ───
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ─── Export CSV → header direct, avant toute sortie JSON ───
$isExport = ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'export');

// ─── Connexion DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) {
        $initPath = dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
        if (file_exists($initPath)) require_once $initPath;
    }
}
if (isset($db)  && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db))  $db  = $pdo;

if (!isset($pdo)) {
    if (!$isExport) {
        echo json_encode(['success' => false, 'error' => 'Connexion DB impossible']);
        exit;
    }
}

// ─── Helper réponse ───
function sectResp(bool $ok, string $msg = '', array $data = []): void {
    $out = ['success' => $ok, ($ok ? 'message' : 'error') => $msg];
    if ($data) $out['data'] = $data;
    echo json_encode($out);
    exit;
}

// ─── Valider ID ───
function sectValidId(mixed $val): int {
    $id = (int)$val;
    if ($id <= 0) sectResp(false, 'ID invalide');
    return $id;
}

// ─── Générer slug unique ───
function sectUniqueSlug(PDO $pdo, string $base, int $excludeId = 0): string {
    $slug    = $base;
    $counter = 1;
    while (true) {
        $sql = "SELECT COUNT(*) FROM secteurs WHERE slug = ?";
        $params = [$slug];
        if ($excludeId > 0) { $sql .= " AND id != ?"; $params[] = $excludeId; }
        $count = (int)$pdo->prepare($sql) && false ? 0
               : (function() use ($pdo,$sql,$params){ $s=$pdo->prepare($sql);$s->execute($params);return (int)$s->fetchColumn();})();
        if ($count === 0) return $slug;
        $slug = $base . '-' . $counter++;
    }
}

// ═══════════════════════════════════════════════════════════════
// GET — EXPORT CSV
// ═══════════════════════════════════════════════════════════════
if ($isExport) {
    try {
        $stmt = $pdo->query("SELECT * FROM secteurs ORDER BY nom ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erreur DB: ' . $e->getMessage()]);
        exit;
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="secteurs_' . date('Ymd_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    // BOM UTF-8 pour Excel
    fputs($out, "\xEF\xBB\xBF");

    if (!empty($rows)) {
        fputcsv($out, array_keys($rows[0]), ';');
        foreach ($rows as $row) {
            // Simplifier les champs JSON pour la lisibilité CSV
            foreach ($row as $k => &$v) {
                if (is_string($v) && strlen($v) > 0 && in_array($v[0], ['{','['])) {
                    // JSON → juste marquer [JSON]
                    $decoded = json_decode($v, true);
                    if ($decoded !== null) $v = '[JSON:' . count((array)$decoded) . ' entrées]';
                }
            }
            unset($v);
            fputcsv($out, $row, ';');
        }
    }
    fclose($out);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// POST — ACTIONS
// ═══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sectResp(false, 'Méthode non autorisée');
}

$action = trim($_POST['action'] ?? '');
if (!$action) sectResp(false, 'Action manquante');

// ─── Statuts autorisés ───
$allowedStatuses = ['published', 'draft', 'archived'];

// ───────────────────────────────────────────────────────────────
// ACTION : delete
// ───────────────────────────────────────────────────────────────
if ($action === 'delete') {
    $id = sectValidId($_POST['id'] ?? 0);
    try {
        $stmt = $pdo->prepare("DELETE FROM secteurs WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() === 0) sectResp(false, 'Secteur introuvable');
        sectResp(true, 'Secteur supprimé');
    } catch (PDOException $e) {
        error_log('[CMS Secteurs API] delete error: ' . $e->getMessage());
        sectResp(false, 'Erreur lors de la suppression');
    }
}

// ───────────────────────────────────────────────────────────────
// ACTION : bulk_delete
// ───────────────────────────────────────────────────────────────
if ($action === 'bulk_delete') {
    $raw = $_POST['ids'] ?? '[]';
    $ids = json_decode($raw, true);
    if (!is_array($ids) || empty($ids)) sectResp(false, 'Aucun ID fourni');
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, fn($i) => $i > 0);
    if (empty($ids)) sectResp(false, 'IDs invalides');

    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM secteurs WHERE id IN ({$placeholders})");
        $stmt->execute(array_values($ids));
        sectResp(true, $stmt->rowCount() . ' secteur(s) supprimé(s)', ['deleted' => $stmt->rowCount()]);
    } catch (PDOException $e) {
        error_log('[CMS Secteurs API] bulk_delete error: ' . $e->getMessage());
        sectResp(false, 'Erreur lors de la suppression groupée');
    }
}

// ───────────────────────────────────────────────────────────────
// ACTION : bulk_status
// ───────────────────────────────────────────────────────────────
if ($action === 'bulk_status') {
    $newStatus = $_POST['status'] ?? '';
    if (!in_array($newStatus, $allowedStatuses)) sectResp(false, 'Statut invalide');

    $raw = $_POST['ids'] ?? '[]';
    $ids = json_decode($raw, true);
    if (!is_array($ids) || empty($ids)) sectResp(false, 'Aucun ID fourni');
    $ids = array_map('intval', $ids);
    $ids = array_filter($ids, fn($i) => $i > 0);
    if (empty($ids)) sectResp(false, 'IDs invalides');

    try {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $params = array_values($ids);
        array_unshift($params, $newStatus);
        $stmt = $pdo->prepare("UPDATE secteurs SET status = ?, updated_at = NOW() WHERE id IN ({$placeholders})");
        $stmt->execute($params);
        sectResp(true, $stmt->rowCount() . ' secteur(s) mis à jour', ['updated' => $stmt->rowCount()]);
    } catch (PDOException $e) {
        error_log('[CMS Secteurs API] bulk_status error: ' . $e->getMessage());
        sectResp(false, 'Erreur lors de la mise à jour groupée');
    }
}

// ───────────────────────────────────────────────────────────────
// ACTION : toggle_status
// ───────────────────────────────────────────────────────────────
if ($action === 'toggle_status') {
    $id        = sectValidId($_POST['id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    if (!in_array($newStatus, $allowedStatuses)) sectResp(false, 'Statut invalide');

    try {
        $stmt = $pdo->prepare("UPDATE secteurs SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $id]);
        if ($stmt->rowCount() === 0) sectResp(false, 'Secteur introuvable ou inchangé');
        sectResp(true, 'Statut mis à jour', ['status' => $newStatus]);
    } catch (PDOException $e) {
        error_log('[CMS Secteurs API] toggle_status error: ' . $e->getMessage());
        sectResp(false, 'Erreur lors de la mise à jour du statut');
    }
}

// ───────────────────────────────────────────────────────────────
// ACTION : duplicate
// ───────────────────────────────────────────────────────────────
if ($action === 'duplicate') {
    $id = sectValidId($_POST['id'] ?? 0);

    try {
        $stmt = $pdo->prepare("SELECT * FROM secteurs WHERE id = ?");
        $stmt->execute([$id]);
        $orig = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$orig) sectResp(false, 'Secteur introuvable');

        // Préparer la copie
        unset($orig['id']);
        $orig['nom']        = 'Copie - ' . $orig['nom'];
        $orig['status']     = 'draft';
        $orig['created_at'] = date('Y-m-d H:i:s');
        $orig['updated_at'] = date('Y-m-d H:i:s');

        // Slug unique
        $baseSlug       = 'copie-' . ($orig['slug'] ?? strtolower(str_replace(' ', '-', $orig['nom'])));
        $orig['slug']   = sectUniqueSlug($pdo, $baseSlug);

        $cols        = array_keys($orig);
        $placeholders = array_fill(0, count($cols), '?');
        $sql = 'INSERT INTO secteurs (' . implode(',', array_map(fn($c) => "`{$c}`", $cols)) . ') VALUES (' . implode(',', $placeholders) . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($orig));
        $newId = (int)$pdo->lastInsertId();

        sectResp(true, 'Secteur dupliqué', ['new_id' => $newId]);
    } catch (PDOException $e) {
        error_log('[CMS Secteurs API] duplicate error: ' . $e->getMessage());
        sectResp(false, 'Erreur lors de la duplication');
    }
}

// ───────────────────────────────────────────────────────────────
// ACTION inconnue
// ───────────────────────────────────────────────────────────────
sectResp(false, "Action inconnue : {$action}");