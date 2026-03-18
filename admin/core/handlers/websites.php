<?php
/**
 * API Handler: websites
 * Called via: /admin/api/router.php?module=websites&action=...
 * Table: websites
 *
 * All mutating actions (create, update, delete, bulk-delete, bulk-status,
 * toggle_status, verify_domain) require POST with a valid CSRF token
 * (enforced by the router). Read-only actions (list, get, stats) use GET.
 */

$input  = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {

    /* ── LIST ── */
    case 'list':
        try {
            $stmt = $pdo->query("SELECT w.*, (SELECT COUNT(*) FROM pages WHERE website_id = w.id) AS pages_count FROM websites w ORDER BY w.created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    /* ── GET (single) ── */
    case 'get':
        try {
            $id   = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT w.*, (SELECT COUNT(*) FROM pages WHERE website_id = w.id) AS pages_count FROM websites w WHERE w.id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['settings'])) {
                $row['settings_parsed'] = json_decode($row['settings'], true);
            }
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Site non trouve']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    /* ── CREATE ── */
    case 'create':
        try {
            $name = trim($input['name'] ?? '');
            if (empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Le nom du site est obligatoire']);
                break;
            }
            $slug = trim($input['slug'] ?? '');
            if (empty($slug)) {
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
                $slug = trim($slug, '-');
            }
            $domain          = trim($input['domain'] ?? '') ?: null;
            $logo            = trim($input['logo'] ?? '') ?: null;
            $favicon         = trim($input['favicon'] ?? '') ?: null;
            $primary_color   = $input['primary_color'] ?? '#3B82F6';
            $secondary_color = $input['secondary_color'] ?? '#1E40AF';
            $font_family     = trim($input['font_family'] ?? 'Inter');
            $status          = $input['status'] ?? 'draft';
            $seo_title       = trim($input['seo_title'] ?? '') ?: null;
            $seo_description = trim($input['seo_description'] ?? '') ?: null;
            $tracking_code   = trim($input['tracking_code'] ?? '') ?: null;
            $settings        = isset($input['settings'])
                ? (is_string($input['settings']) ? $input['settings'] : json_encode($input['settings']))
                : '{}';

            if ($domain) {
                $domain = preg_replace('/^https?:\/\//', '', $domain);
                $domain = preg_replace('/\/.*$/', '', $domain);
                $domain = strtolower(trim($domain));
            }

            $allowedStatuses = ['draft', 'published', 'archived'];
            if (!in_array($status, $allowedStatuses, true)) $status = 'draft';

            $stmt = $pdo->prepare("INSERT INTO websites (name, slug, domain, logo, favicon, primary_color, secondary_color, font_family, status, seo_title, seo_description, tracking_code, settings) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name, $slug, $domain, $logo, $favicon, $primary_color, $secondary_color, $font_family, $status, $seo_title, $seo_description, $tracking_code, $settings]);
            echo json_encode(['success' => true, 'message' => 'Site cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    /* ── UPDATE ── */
    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }

            $allowed = ['name', 'slug', 'domain', 'logo', 'favicon', 'primary_color', 'secondary_color', 'font_family', 'status', 'seo_title', 'seo_description', 'tracking_code'];
            $sets    = [];
            $params  = [];
            foreach ($input as $k => $v) {
                if (in_array($k, $allowed, true)) {
                    $sets[]   = "`{$k}` = ?";
                    $params[] = $v;
                }
            }
            if (isset($input['settings'])) {
                $sets[]   = "settings = ?";
                $params[] = is_string($input['settings']) ? $input['settings'] : json_encode($input['settings']);
            }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ a mettre a jour']); break; }

            // If domain changed, reset domain_verified
            if (array_key_exists('domain', $input)) {
                $stmt = $pdo->prepare("SELECT domain FROM websites WHERE id = ?");
                $stmt->execute([$id]);
                $old = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($old && $old['domain'] !== ($input['domain'] ?? null)) {
                    $sets[]   = "domain_verified = 0";
                }
            }

            $sets[]   = "updated_at = NOW()";
            $params[] = $id;
            $pdo->prepare("UPDATE websites SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Site mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    /* ── DELETE (single) ── */
    case 'delete':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $pdo->prepare("UPDATE pages SET website_id = NULL WHERE website_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM websites WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Site supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    /* ── BULK DELETE ── */
    case 'bulk-delete':
        try {
            $ids = array_unique(array_map('intval', (array)($input['ids'] ?? [])));
            $ids = array_filter($ids, fn($id) => $id > 0);
            if (empty($ids)) { echo json_encode(['success' => false, 'message' => 'Aucun ID fourni']); break; }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $pdo->prepare("UPDATE pages SET website_id = NULL WHERE website_id IN ($placeholders)")->execute($ids);
            $pdo->prepare("DELETE FROM websites WHERE id IN ($placeholders)")->execute($ids);
            echo json_encode(['success' => true, 'message' => count($ids) . ' site(s) supprime(s)']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    /* ── BULK STATUS ── */
    case 'bulk-status':
        try {
            $ids = array_unique(array_map('intval', (array)($input['ids'] ?? [])));
            $ids = array_filter($ids, fn($id) => $id > 0);
            $newStatus      = $input['new_status'] ?? '';
            $allowedStatuses = ['published', 'draft', 'archived'];
            if (empty($ids) || !in_array($newStatus, $allowedStatuses, true)) {
                echo json_encode(['success' => false, 'message' => 'Parametres invalides']);
                break;
            }
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $params       = array_merge([$newStatus], $ids);
            $pdo->prepare("UPDATE websites SET status = ?, updated_at = NOW() WHERE id IN ($placeholders)")->execute($params);
            echo json_encode(['success' => true, 'message' => count($ids) . ' site(s) mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    /* ── TOGGLE STATUS ── */
    case 'toggle_status':
        try {
            $id   = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT status FROM websites WHERE id = ?");
            $stmt->execute([$id]);
            $current   = $stmt->fetchColumn();
            $newStatus = ($current === 'published') ? 'draft' : 'published';
            $pdo->prepare("UPDATE websites SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $id]);
            echo json_encode(['success' => true, 'message' => 'Statut mis a jour', 'status' => $newStatus]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    /* ── VERIFY DOMAIN ── */
    case 'verify_domain':
        try {
            $id   = (int)($input['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT domain FROM websites WHERE id = ?");
            $stmt->execute([$id]);
            $site = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$site || empty($site['domain'])) {
                echo json_encode(['success' => false, 'message' => 'Site sans domaine']);
                break;
            }
            if (!defined('SERVER_IP')) define('SERVER_IP', '91.134.XXX.XXX');
            $domain   = preg_replace('/^www\./', '', $site['domain']);
            $aRecords = @dns_get_record($domain, DNS_A);
            $verified = false;
            if ($aRecords) {
                foreach ($aRecords as $r) {
                    if ($r['ip'] === SERVER_IP) { $verified = true; break; }
                }
            }
            if ($verified) {
                $pdo->prepare("UPDATE websites SET domain_verified = 1, domain_verified_at = NOW() WHERE id = ?")->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Domaine verifie', 'verified' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Le domaine ne pointe pas encore vers le serveur', 'verified' => false]);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    /* ── STATS ── */
    case 'stats':
        try {
            $stats = [
                'total'     => (int)$pdo->query("SELECT COUNT(*) FROM websites")->fetchColumn(),
                'published' => (int)$pdo->query("SELECT COUNT(*) FROM websites WHERE status = 'published'")->fetchColumn(),
                'draft'     => (int)$pdo->query("SELECT COUNT(*) FROM websites WHERE status = 'draft'")->fetchColumn(),
                'archived'  => (int)$pdo->query("SELECT COUNT(*) FROM websites WHERE status = 'archived'")->fetchColumn(),
                'verified'  => (int)$pdo->query("SELECT COUNT(*) FROM websites WHERE domain_verified = 1")->fetchColumn(),
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
