<?php
/**
 * ══════════════════════════════════════════════════════════════
 * API BIENS IMMOBILIERS  v1.0
 * /admin/api/immobilier/properties-api.php
 * ÉCOSYSTÈME IMMO LOCAL+
 * Actions : delete, bulk_delete, bulk_status, toggle_status,
 *           toggle_featured, duplicate, upload_photo, create_table
 * ══════════════════════════════════════════════════════════════
 */

if (!defined('ADMIN_ROUTER')) {
    require_once dirname(dirname(__DIR__)) . '/includes/init.php';
}

header('Content-Type: application/json; charset=utf-8');

function jsonOk(array $data = []): void {
    echo json_encode(array_merge(['success' => true], $data));
    exit;
}
function jsonErr(string $msg, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonErr('Méthode non autorisée', 405);

global $pdo, $db;
if (!isset($pdo) && isset($db)) $pdo = $db;
if (!isset($pdo)) jsonErr('Connexion DB absente', 500);

$action = trim($_POST['action'] ?? '');
if (!$action) jsonErr('Action manquante');

// ─── Récupérer colonnes réelles ───
$availCols = [];
try { $availCols = $pdo->query("SHOW COLUMNS FROM properties")->fetchAll(PDO::FETCH_COLUMN); } catch (PDOException $e) {}
$colStatus  = in_array('statut', $availCols) ? 'statut' : 'status';
$colFeat    = in_array('is_featured', $availCols) ? 'is_featured' : 'featured';

// ───────────────────────────────────────────────────────
switch ($action) {

    // ── Suppression unique ──────────────────────────────
    case 'delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonErr('ID manquant');
        try {
            $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
            $stmt->execute([$id]);
            if ($stmt->rowCount() === 0) jsonErr('Bien introuvable', 404);
            jsonOk(['deleted_id' => $id]);
        } catch (PDOException $e) {
            jsonErr('Erreur DB : ' . $e->getMessage(), 500);
        }
        break;

    // ── Suppression groupée ─────────────────────────────
    case 'bulk_delete':
        $ids = json_decode($_POST['ids'] ?? '[]', true);
        if (!is_array($ids) || empty($ids)) jsonErr('IDs manquants');
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        try {
            $stmt = $pdo->prepare("DELETE FROM properties WHERE id IN ({$placeholders})");
            $stmt->execute($ids);
            jsonOk(['deleted_count' => $stmt->rowCount()]);
        } catch (PDOException $e) {
            jsonErr('Erreur DB : ' . $e->getMessage(), 500);
        }
        break;

    // ── Changement statut groupé ────────────────────────
    case 'bulk_status':
        $ids    = json_decode($_POST['ids'] ?? '[]', true);
        $status = trim($_POST['status'] ?? '');
        if (!is_array($ids) || empty($ids)) jsonErr('IDs manquants');
        $allowed = ['actif','active','brouillon','draft','vendu','sold','loue','rented','archive','archived'];
        if (!in_array(strtolower($status), $allowed)) jsonErr('Statut invalide');
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        try {
            $stmt = $pdo->prepare("UPDATE properties SET `{$colStatus}` = ?, updated_at = NOW() WHERE id IN ({$placeholders})");
            $stmt->execute([$status, ...$ids]);
            jsonOk(['updated_count' => $stmt->rowCount()]);
        } catch (PDOException $e) {
            jsonErr('Erreur DB : ' . $e->getMessage(), 500);
        }
        break;

    // ── Toggle statut individuel ────────────────────────
    case 'toggle_status':
        $id     = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if (!$id) jsonErr('ID manquant');
        $allowed = ['actif','active','brouillon','draft','vendu','archive','archived'];
        if (!in_array(strtolower($status), $allowed)) jsonErr('Statut invalide');
        try {
            $stmt = $pdo->prepare("UPDATE properties SET `{$colStatus}` = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$status, $id]);
            jsonOk(['new_status' => $status]);
        } catch (PDOException $e) {
            jsonErr('Erreur DB : ' . $e->getMessage(), 500);
        }
        break;

    // ── Toggle featured ──────────────────────────────────
    case 'toggle_featured':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonErr('ID manquant');
        try {
            $stmt = $pdo->prepare("SELECT `{$colFeat}` FROM properties WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $current = (int)$stmt->fetchColumn();
            $new = $current ? 0 : 1;
            $pdo->prepare("UPDATE properties SET `{$colFeat}` = ?, updated_at = NOW() WHERE id = ?")->execute([$new, $id]);
            jsonOk(['featured' => $new]);
        } catch (PDOException $e) {
            jsonErr('Erreur DB : ' . $e->getMessage(), 500);
        }
        break;

    // ── Duplication ──────────────────────────────────────
    case 'duplicate':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) jsonErr('ID manquant');
        try {
            $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $orig = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orig) jsonErr('Bien introuvable', 404);

            unset($orig['id']);
            // Modifier le titre + slug
            $colTitle = in_array('titre', $availCols) ? 'titre' : 'title';
            $colSlug  = 'slug';
            $orig[$colTitle]  = ($orig[$colTitle] ?? 'Copie') . ' (copie)';
            if (in_array('slug', $availCols)) {
                $orig[$colSlug] = ($orig[$colSlug] ?? 'bien') . '-copie-' . time();
            }
            // Statut brouillon
            $orig[$colStatus] = 'brouillon';
            $orig['created_at'] = date('Y-m-d H:i:s');
            $orig['updated_at'] = date('Y-m-d H:i:s');
            // Filtrer colonnes existantes
            $orig = array_intersect_key($orig, array_flip($availCols));
            $cols = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($orig)));
            $vals = implode(', ', array_fill(0, count($orig), '?'));
            $pdo->prepare("INSERT INTO properties ({$cols}) VALUES ({$vals})")->execute(array_values($orig));
            $newId = $pdo->lastInsertId();
            jsonOk(['new_id' => $newId]);
        } catch (PDOException $e) {
            jsonErr('Erreur DB : ' . $e->getMessage(), 500);
        }
        break;

    // ── Upload photo ─────────────────────────────────────
    case 'upload_photo':
        if (empty($_FILES['photo'])) jsonErr('Aucun fichier reçu');
        $file = $_FILES['photo'];
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($file['type'], $allowed)) jsonErr('Type de fichier non autorisé');
        if ($file['size'] > 5 * 1024 * 1024) jsonErr('Fichier trop volumineux (max 5 MB)');

        $uploadDir = dirname(dirname(dirname(__DIR__))) . '/uploads/properties/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prop_', true) . '.' . strtolower($ext);
        $dest     = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) jsonErr('Erreur lors du déplacement du fichier');

        $url = '/uploads/properties/' . $filename;
        jsonOk(['url' => $url, 'filename' => $filename]);
        break;

    // ── Création automatique table ───────────────────────
    case 'create_table':
        $sql = "
CREATE TABLE IF NOT EXISTS `properties` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `titre`               VARCHAR(255) NOT NULL,
  `slug`                VARCHAR(255) NOT NULL UNIQUE,
  `description`         LONGTEXT,
  `prix`                DECIMAL(12,2) DEFAULT 0,
  `surface`             DECIMAL(8,2) DEFAULT 0,
  `type_bien`           VARCHAR(100),
  `transaction`         ENUM('vente','location') DEFAULT 'vente',
  `statut`              ENUM('actif','brouillon','vendu','loue','archive') DEFAULT 'brouillon',
  `pieces`              TINYINT UNSIGNED DEFAULT 0,
  `chambres`            TINYINT UNSIGNED DEFAULT 0,
  `salles_bain`         TINYINT UNSIGNED DEFAULT 0,
  `annee_construction`  YEAR,
  `ville`               VARCHAR(100),
  `code_postal`         VARCHAR(10),
  `adresse`             VARCHAR(255),
  `latitude`            DECIMAL(10,7),
  `longitude`           DECIMAL(10,7),
  `reference`           VARCHAR(100),
  `mandat`              ENUM('simple','exclusif','co-exclusif') DEFAULT 'simple',
  `dpe`                 CHAR(1),
  `ges`                 CHAR(1),
  `charges`             INT UNSIGNED DEFAULT 0,
  `honoraires`          DECIMAL(5,2) DEFAULT 0,
  `photos`              JSON,
  `is_featured`         TINYINT(1) DEFAULT 0,
  `focus_keyword`       VARCHAR(255),
  `meta_title`          VARCHAR(255),
  `meta_description`    TEXT,
  `created_at`          DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_statut (`statut`),
  INDEX idx_transaction (`transaction`),
  INDEX idx_ville (`ville`),
  INDEX idx_type_bien (`type_bien`),
  INDEX idx_featured (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        try {
            $pdo->exec($sql);
            jsonOk(['message' => 'Table properties créée avec succès']);
        } catch (PDOException $e) {
            jsonErr('Erreur création table : ' . $e->getMessage(), 500);
        }
        break;

    default:
        jsonErr('Action inconnue : ' . htmlspecialchars($action));
}