<?php
/**
 * /front/renderers/properties-listing.php
 * Renderer pour la page listing des biens immobiliers
 * Charge le PropertyController, récupère les données, passe au template
 */

$root = dirname(__DIR__, 2);
require_once $root . '/config/config.php';
require_once $root . '/admin/modules/immobilier/properties/PropertyController.php';

$pdo = getDB();
$ctrl = new PropertyController($pdo);

// ─── Filtres depuis GET ──────────────────────────────────
$filterType   = trim($_GET['type']        ?? 'all');
$filterTrans  = trim($_GET['transaction'] ?? 'all');
$filterCity   = trim($_GET['secteur']     ?? 'all');
$priceMin     = (int)($_GET['prix_min']   ?? 0);
$priceMax     = (int)($_GET['prix_max']   ?? 0);
$search       = trim($_GET['q']           ?? '');
$currentPage  = max(1, (int)($_GET['p']   ?? 1));
$perPage      = 12;

// ─── Construction WHERE custom (front = actifs seulement) ──
$where  = [];
$params = [];

// Uniquement les biens actifs côté front
$colStatus = $ctrl->colStatus;
$where[] = "`{$colStatus}` IN ('actif','active','disponible','available')";

// Filtre type
if ($filterType !== 'all') {
    $where[] = "`{$ctrl->colType}` = ?";
    $params[] = $filterType;
}

// Filtre transaction (vente/location)
if ($filterTrans !== 'all') {
    $where[] = "`{$ctrl->colTrans}` = ?";
    $params[] = $filterTrans;
}

// Filtre secteur (ville)
if ($filterCity !== 'all') {
    $where[] = "`{$ctrl->colCity}` = ?";
    $params[] = $filterCity;
}

// Filtre prix
if ($priceMin > 0) {
    $where[] = "`{$ctrl->colPrice}` >= ?";
    $params[] = $priceMin;
}
if ($priceMax > 0) {
    $where[] = "`{$ctrl->colPrice}` <= ?";
    $params[] = $priceMax;
}

// Recherche texte
if ($search !== '') {
    $w = "(`{$ctrl->colTitle}` LIKE ? OR `{$ctrl->colCity}` LIKE ?";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    if (in_array($ctrl->colRef, $ctrl->availCols)) {
        $w .= " OR `{$ctrl->colRef}` LIKE ?";
        $params[] = "%{$search}%";
    }
    $w .= ")";
    $where[] = $w;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$offset   = ($currentPage - 1) * $perPage;

// ─── Requêtes DB ─────────────────────────────────────────
$properties = [];
$totalItems = 0;
$totalPages = 1;
$types      = [];
$cities     = [];

if ($ctrl->tableExists) {
    try {
        // Total
        $stCount = $pdo->prepare("SELECT COUNT(*) FROM properties {$whereSQL}");
        $stCount->execute($params);
        $totalItems = (int)$stCount->fetchColumn();
        $totalPages = max(1, ceil($totalItems / $perPage));

        // Listing
        $select = "id, `{$ctrl->colTitle}` AS titre, `{$ctrl->colPrice}` AS prix,
                   `{$ctrl->colSurface}` AS surface, `{$ctrl->colType}` AS type_bien,
                   `{$ctrl->colTrans}` AS transaction, `{$ctrl->colCity}` AS ville,
                   `{$ctrl->colRooms}` AS pieces, created_at";
        if ($ctrl->hasSlug)     $select .= ", slug";
        if ($ctrl->hasPhotos)   $select .= ", `{$ctrl->colPhotos}` AS photos";
        if ($ctrl->hasDpe)      $select .= ", `{$ctrl->colDpe}` AS dpe";
        if (in_array($ctrl->colRef, $ctrl->availCols)) $select .= ", `{$ctrl->colRef}` AS reference";

        $stList = $pdo->prepare("SELECT {$select} FROM properties {$whereSQL} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
        $stList->execute($params);
        $properties = $stList->fetchAll(PDO::FETCH_ASSOC);

        // Types distincts (pour filtre)
        $types = $ctrl->getTypes();

        // Villes distinctes (pour filtre secteur)
        $cities = $pdo->query(
            "SELECT DISTINCT `{$ctrl->colCity}` FROM properties
             WHERE `{$ctrl->colCity}` IS NOT NULL AND `{$ctrl->colCity}` != ''
             AND `{$colStatus}` IN ('actif','active','disponible','available')
             ORDER BY `{$ctrl->colCity}`"
        )->fetchAll(PDO::FETCH_COLUMN);

    } catch (PDOException $e) {
        error_log('[properties-listing] ' . $e->getMessage());
    }
}

// ─── Passer au template ──────────────────────────────────
$pageTitle       = 'Nos biens immobiliers — ' . SITE_TITLE;
$pageDescription = 'Découvrez tous nos biens immobiliers à Lannion et ses environs : maisons, appartements, terrains.';

require __DIR__ . '/../templates/pages/t10-biens-listing.php';
