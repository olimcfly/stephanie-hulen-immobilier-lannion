<?php
/**
 * API Handler: strategy
 * Called via: /admin/api/router.php?module=strategy&action=...
 * Strategy hub: neuropersona campaigns and configuration
 * Tables: neuropersona_config, neuropersona_campagnes (if exist), neuropersona_types
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

// Check available tables
$hasCampagnes = false;
$hasConfig = false;
try { $pdo->query("SELECT 1 FROM neuropersona_campagnes LIMIT 1"); $hasCampagnes = true; } catch (PDOException $e) {}
try { $pdo->query("SELECT 1 FROM neuropersona_config LIMIT 1"); $hasConfig = true; } catch (PDOException $e) {}

switch ($action) {
    case 'overview':
    case 'list':
        try {
            $data = [
                'personas' => $pdo->query("SELECT id, nom, code, categorie, color, status FROM neuropersona_types ORDER BY categorie, nom")->fetchAll(PDO::FETCH_ASSOC),
            ];
            if ($hasCampagnes) {
                $data['campagnes'] = $pdo->query("SELECT * FROM neuropersona_campagnes ORDER BY created_at DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
            }
            if ($hasConfig) {
                $data['config'] = $pdo->query("SELECT * FROM neuropersona_config")->fetchAll(PDO::FETCH_ASSOC);
            }
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_config':
        try {
            if (!$hasConfig) { echo json_encode(['success' => true, 'data' => []]); break; }
            $stmt = $pdo->query("SELECT * FROM neuropersona_config");
            $config = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $config[$row['config_key'] ?? $row['key'] ?? $row['id']] = $row['config_value'] ?? $row['value'] ?? '';
            }
            echo json_encode(['success' => true, 'data' => $config]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_config':
        try {
            if (!$hasConfig) { echo json_encode(['success' => false, 'message' => 'Table neuropersona_config non disponible']); break; }
            $updated = 0;
            foreach ($input as $key => $value) {
                if ($key === 'action' || $key === 'csrf_token') continue;
                $stmt = $pdo->prepare("INSERT INTO neuropersona_config (config_key, config_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE config_value = ?");
                $stmt->execute([$key, $value, $value]);
                $updated++;
            }
            echo json_encode(['success' => true, 'message' => "{$updated} parametres mis a jour"]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // --- Campagnes ---
    case 'campagnes':
        try {
            if (!$hasCampagnes) { echo json_encode(['success' => true, 'data' => []]); break; }
            $stmt = $pdo->query("SELECT * FROM neuropersona_campagnes ORDER BY created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_campagne':
        try {
            if (!$hasCampagnes) { echo json_encode(['success' => false, 'message' => 'Table non disponible']); break; }
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM neuropersona_campagnes WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Campagne non trouvee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_campagne':
        try {
            if (!$hasCampagnes) { echo json_encode(['success' => false, 'message' => 'Table non disponible']); break; }
            $stmt = $pdo->prepare("INSERT INTO neuropersona_campagnes (name, description, persona_id, objective, channels, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['name'] ?? '', $input['description'] ?? '', $input['persona_id'] ?? null,
                $input['objective'] ?? '', $input['channels'] ?? '', $input['status'] ?? 'draft'
            ]);
            echo json_encode(['success' => true, 'message' => 'Campagne creee', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_campagne':
        try {
            if (!$hasCampagnes) { echo json_encode(['success' => false, 'message' => 'Table non disponible']); break; }
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['name', 'description', 'persona_id', 'objective', 'channels', 'status'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE neuropersona_campagnes SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Campagne mise a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_campagne':
        try {
            if (!$hasCampagnes) { echo json_encode(['success' => false, 'message' => 'Table non disponible']); break; }
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM neuropersona_campagnes WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Campagne supprimee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = [
                'total_personas' => (int)$pdo->query("SELECT COUNT(*) FROM neuropersona_types")->fetchColumn(),
                'active_personas' => (int)$pdo->query("SELECT COUNT(*) FROM neuropersona_types WHERE status = 'active'")->fetchColumn(),
            ];
            if ($hasCampagnes) {
                $stats['total_campagnes'] = (int)$pdo->query("SELECT COUNT(*) FROM neuropersona_campagnes")->fetchColumn();
                $stats['active_campagnes'] = (int)$pdo->query("SELECT COUNT(*) FROM neuropersona_campagnes WHERE status = 'active'")->fetchColumn();
            }
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
