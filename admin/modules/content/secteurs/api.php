<?php
/**
 * MODULE ADMIN — Secteurs — API
 * /admin/modules/content/secteurs/api.php
 * Endpoints AJAX : save, delete, toggle_status, duplicate
 */

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

header('Content-Type: application/json; charset=utf-8');

// Verify POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? '';
$result = ['success' => false, 'error' => 'Unknown action'];

// DB columns detection
$cols = [];
try { $cols = $pdo->query("SHOW COLUMNS FROM secteurs")->fetchAll(PDO::FETCH_COLUMN); } catch(Throwable){}
$has = fn(string $c): bool => in_array($c, $cols);

try {
    switch ($action) {

        // ══════════════════════════════════════════════════════════
        // SAVE — Create or update a secteur
        // ══════════════════════════════════════════════════════════
        case 'save':
            $id = (int)($_POST['id'] ?? 0);
            $isNew = ($id === 0);

            $statusEN = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
            $data = [
                'nom'              => trim($_POST['nom'] ?? ''),
                'slug'             => trim($_POST['slug'] ?? ''),
                'ville'            => trim($_POST['ville'] ?? ''),
                'type_secteur'     => in_array($_POST['type_secteur'] ?? '', ['quartier','commune']) ? $_POST['type_secteur'] : 'quartier',
                'description'      => trim($_POST['description'] ?? ''),
                'content'          => $_POST['content'] ?? '',
                'atouts'           => trim($_POST['atouts'] ?? ''),
                'prix_moyen'       => trim($_POST['prix_moyen'] ?? ''),
                'transport'        => trim($_POST['transport'] ?? ''),
                'ambiance'         => trim($_POST['ambiance'] ?? ''),
                'hero_image'       => trim($_POST['hero_image'] ?? ''),
                'hero_title'       => trim($_POST['hero_title'] ?? ''),
                'hero_subtitle'    => trim($_POST['hero_subtitle'] ?? ''),
                'hero_cta_text'    => trim($_POST['hero_cta_text'] ?? ''),
                'hero_cta_url'     => trim($_POST['hero_cta_url'] ?? ''),
                'meta_title'       => trim($_POST['meta_title'] ?? ''),
                'meta_description' => trim($_POST['meta_description'] ?? ''),
                'meta_keywords'    => trim($_POST['meta_keywords'] ?? ''),
                'status'           => $statusEN,
                'template_id'      => intval($_POST['template_id'] ?? 0) ?: null,
            ];
            if ($has('statut')) $data['statut'] = $statusEN === 'published' ? 'publie' : 'brouillon';

            if (empty($data['nom'])) throw new Exception('Le nom du secteur est obligatoire.');

            // Auto-slug
            if (empty($data['slug'])) {
                $sl = mb_strtolower($data['nom']);
                $sl = strtr($sl, ['à'=>'a','â'=>'a','é'=>'e','è'=>'e','ê'=>'e','î'=>'i','ô'=>'o','ù'=>'u','û'=>'u','ç'=>'c']);
                $data['slug'] = trim(preg_replace('/[^a-z0-9]+/', '-', $sl), '-');
            }

            // Only keep columns that exist in DB
            $safe = array_filter($data, fn($k) => $has($k), ARRAY_FILTER_USE_KEY);

            if ($isNew) {
                // INSERT
                $colNames = array_keys($safe);
                $placeholders = implode(',', array_fill(0, count($colNames), '?'));
                $sql = 'INSERT INTO secteurs (`' . implode('`,`', $colNames) . '`) VALUES (' . $placeholders . ')';
                $pdo->prepare($sql)->execute(array_values($safe));
                $newId = (int)$pdo->lastInsertId();

                if (function_exists('writeLog')) {
                    writeLog('secteurs', "Secteur cree: {$data['nom']}", 'create', ['secteur_id' => $newId]);
                }
                $result = ['success' => true, 'message' => 'Secteur cree', 'id' => $newId];
            } else {
                // Verify exists
                $st = $pdo->prepare("SELECT id FROM secteurs WHERE id=?");
                $st->execute([$id]);
                if (!$st->fetch()) throw new Exception('Secteur introuvable');

                // UPDATE
                $sets = array_map(fn($c) => "`$c`=?", array_keys($safe));
                $vals = array_values($safe);
                $vals[] = $id;
                $pdo->prepare('UPDATE secteurs SET ' . implode(',', $sets) . ' WHERE id=?')->execute($vals);

                if (function_exists('writeLog')) {
                    writeLog('secteurs', "Secteur modifie: {$data['nom']}", 'update', ['secteur_id' => $id]);
                }
                $result = ['success' => true, 'message' => 'Secteur enregistre', 'id' => $id];
            }
            break;

        // ══════════════════════════════════════════════════════════
        // DELETE — Supprimer un secteur
        // ══════════════════════════════════════════════════════════
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID requis');

            $stmt = $pdo->prepare("SELECT nom FROM secteurs WHERE id = ?");
            $stmt->execute([$id]);
            $secteur = $stmt->fetch();
            if (!$secteur) throw new Exception('Secteur introuvable');

            $pdo->prepare("DELETE FROM secteurs WHERE id = ?")->execute([$id]);

            if (function_exists('writeLog')) {
                writeLog('secteurs', "Secteur supprime: {$secteur['nom']}", 'delete', ['secteur_id' => $id]);
            }
            $result = ['success' => true, 'message' => 'Secteur supprime'];
            break;

        // ══════════════════════════════════════════════════════════
        // TOGGLE_STATUS — Publier/Depublier un secteur
        // ══════════════════════════════════════════════════════════
        case 'toggle_status':
            $id = (int)($_POST['id'] ?? 0);
            $status = $_POST['status'] ?? '';
            if (!$id || !in_array($status, ['draft','published','archived'])) {
                throw new Exception('Parametres invalides');
            }

            $stmt = $pdo->prepare("SELECT nom, status FROM secteurs WHERE id = ?");
            $stmt->execute([$id]);
            $secteur = $stmt->fetch();
            if (!$secteur) throw new Exception('Secteur introuvable');

            $pdo->prepare("UPDATE secteurs SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$status, $id]);

            if ($has('statut')) {
                $statutFr = $status === 'published' ? 'publie' : 'brouillon';
                $pdo->prepare("UPDATE secteurs SET statut = ? WHERE id = ?")->execute([$statutFr, $id]);
            }

            if (function_exists('writeLog')) {
                writeLog('secteurs', "Secteur {$status}: {$secteur['nom']}", 'update', ['secteur_id' => $id, 'status' => $status]);
            }
            $result = ['success' => true, 'message' => 'Statut mis a jour', 'status' => $status];
            break;

        // ══════════════════════════════════════════════════════════
        // DUPLICATE — Dupliquer un secteur
        // ══════════════════════════════════════════════════════════
        case 'duplicate':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID requis');

            $stmt = $pdo->prepare("SELECT * FROM secteurs WHERE id = ?");
            $stmt->execute([$id]);
            $original = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$original) throw new Exception('Secteur introuvable');

            unset($original['id'], $original['created_at'], $original['updated_at']);
            $original['nom']    = 'Copie — ' . $original['nom'];
            $original['slug']   = $original['slug'] . '-copie-' . time();
            $original['status'] = 'draft';

            $colNames = array_keys($original);
            $placeholders = implode(',', array_fill(0, count($colNames), '?'));
            $pdo->prepare("INSERT INTO secteurs (`" . implode('`,`', $colNames) . "`) VALUES ($placeholders)")
                ->execute(array_values($original));

            $newId = (int)$pdo->lastInsertId();
            if (function_exists('writeLog')) {
                writeLog('secteurs', "Secteur duplique: {$original['nom']} -> #{$newId}", 'create', ['secteur_id' => $newId, 'source_id' => $id]);
            }
            $result = ['success' => true, 'message' => 'Secteur duplique', 'new_id' => $newId];
            break;

        default:
            throw new Exception('Action inconnue: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    $result = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    error_log("[Secteurs API] Error in action '$action': " . $e->getMessage());
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;
