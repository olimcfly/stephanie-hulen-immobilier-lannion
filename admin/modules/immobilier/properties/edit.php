<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE BIENS IMMOBILIERS — Edit/Create  v1.0
 * /admin/modules/immobilier/properties/edit.php
 * ÉCOSYSTÈME IMMO LOCAL+
 * ══════════════════════════════════════════════════════════════
 */

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

$isCreate = ($_GET['action'] ?? '') === 'create';
$propId   = (int)($_GET['id'] ?? 0);
$property = [];
$errors   = [];

// ─── Colonnes réelles ───
$availCols = [];
try { $availCols = $pdo->query("SHOW COLUMNS FROM properties")->fetchAll(PDO::FETCH_COLUMN); } catch (PDOException $e) {}
$colTitle   = in_array('titre',       $availCols) ? 'titre'       : 'title';
$colContent = in_array('description', $availCols) ? 'description' : (in_array('contenu', $availCols) ? 'contenu' : 'description');
$colPrice   = in_array('prix',        $availCols) ? 'prix'        : 'price';
$colSurface = in_array('surface',     $availCols) ? 'surface'     : 'area';
$colType    = in_array('type_bien',   $availCols) ? 'type_bien'   : 'type';
$colStatus  = in_array('statut',      $availCols) ? 'statut'      : 'status';
$colTrans   = in_array('transaction', $availCols) ? 'transaction' : 'transaction_type';
$colCity    = in_array('ville',       $availCols) ? 'ville'       : 'city';
$colZip     = in_array('code_postal', $availCols) ? 'code_postal' : (in_array('zip', $availCols) ? 'zip' : 'code_postal');
$colRooms   = in_array('pieces',      $availCols) ? 'pieces'      : 'rooms';
$colBed     = in_array('chambres',    $availCols) ? 'chambres'    : (in_array('bedrooms', $availCols) ? 'bedrooms' : null);
$colBath    = in_array('salles_bain', $availCols) ? 'salles_bain' : (in_array('bathrooms', $availCols) ? 'bathrooms' : null);
$colRef     = in_array('reference',   $availCols) ? 'reference'   : 'ref';
$colSlug    = 'slug';
$colPhotos  = in_array('photos',      $availCols) ? 'photos'      : 'images';
$colFeat    = in_array('is_featured', $availCols) ? 'is_featured' : 'featured';
$colMandat  = in_array('mandat',      $availCols) ? 'mandat'      : 'type_mandat';
$colDpe     = in_array('dpe',         $availCols) ? 'dpe'         : 'classe_energie';
$colGes     = in_array('ges',         $availCols) ? 'ges'         : null;
$colLat     = in_array('latitude',    $availCols) ? 'latitude'    : null;
$colLng     = in_array('longitude',   $availCols) ? 'longitude'   : null;
$colAddress = in_array('adresse',     $availCols) ? 'adresse'     : (in_array('address', $availCols) ? 'address' : null);
$colYear    = in_array('annee_construction', $availCols) ? 'annee_construction' : (in_array('year_built', $availCols) ? 'year_built' : null);
$colCharges = in_array('charges',     $availCols) ? 'charges'     : null;
$colHonos   = in_array('honoraires',  $availCols) ? 'honoraires'  : null;
$colSeoDesc = in_array('meta_description', $availCols) ? 'meta_description' : null;
$colSeoTitle= in_array('meta_title',  $availCols) ? 'meta_title'  : null;
$colKeyword = in_array('focus_keyword', $availCols) ? 'focus_keyword' : null;

// ─── Charger bien existant ───
if (!$isCreate && $propId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? LIMIT 1");
        $stmt->execute([$propId]);
        $property = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$property) { header("Location: ?page=properties"); exit; }
    } catch (PDOException $e) {
        $errors[] = "Impossible de charger le bien : " . $e->getMessage();
    }
}

// ─── Traitement formulaire ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        $colTitle   => trim($_POST['titre']       ?? ''),
        $colContent => trim($_POST['description'] ?? ''),
        $colPrice   => (int)($_POST['prix']       ?? 0),
        $colSurface => (float)($_POST['surface']  ?? 0),
        $colType    => trim($_POST['type_bien']   ?? ''),
        $colStatus  => trim($_POST['statut']      ?? 'brouillon'),
        $colTrans   => trim($_POST['transaction'] ?? 'vente'),
        $colCity    => trim($_POST['ville']       ?? ''),
        $colRooms   => (int)($_POST['pieces']     ?? 0),
        $colRef     => trim($_POST['reference']   ?? ''),
        $colSlug    => trim($_POST['slug']        ?? ''),
        $colPhotos  => $_POST['photos_json']      ?? '[]',
        $colFeat    => !empty($_POST['is_featured']) ? 1 : 0,
        $colMandat  => trim($_POST['mandat']      ?? 'simple'),
        $colDpe     => strtoupper(trim($_POST['dpe'] ?? '')),
        'updated_at'=> date('Y-m-d H:i:s'),
    ];
    if (in_array($colZip,     $availCols)) $data[$colZip]     = trim($_POST['code_postal'] ?? '');
    if ($colBed && in_array($colBed, $availCols)) $data[$colBed] = (int)($_POST['chambres'] ?? 0);
    if ($colBath && in_array($colBath, $availCols)) $data[$colBath] = (int)($_POST['salles_bain'] ?? 0);
    if ($colGes && in_array($colGes, $availCols)) $data[$colGes] = strtoupper(trim($_POST['ges'] ?? ''));
    if ($colAddress && in_array($colAddress, $availCols)) $data[$colAddress] = trim($_POST['adresse'] ?? '');
    if ($colLat && in_array($colLat, $availCols)) $data[$colLat] = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    if ($colLng && in_array($colLng, $availCols)) $data[$colLng] = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    if ($colYear && in_array($colYear, $availCols)) $data[$colYear] = !empty($_POST['annee_construction']) ? (int)$_POST['annee_construction'] : null;
    if ($colCharges && in_array($colCharges, $availCols)) $data[$colCharges] = !empty($_POST['charges']) ? (int)$_POST['charges'] : null;
    if ($colHonos && in_array($colHonos, $availCols)) $data[$colHonos] = !empty($_POST['honoraires']) ? (float)$_POST['honoraires'] : null;
    if ($colSeoTitle && in_array($colSeoTitle, $availCols)) $data[$colSeoTitle] = trim($_POST['meta_title'] ?? '');
    if ($colSeoDesc && in_array($colSeoDesc, $availCols)) $data[$colSeoDesc] = trim($_POST['meta_description'] ?? '');
    if ($colKeyword && in_array($colKeyword, $availCols)) $data[$colKeyword] = trim($_POST['focus_keyword'] ?? '');

    // Génération slug auto
    if (empty($data[$colSlug]) && !empty($data[$colTitle])) {
        $slug = strtolower($data[$colTitle]);
        $slug = iconv('UTF-8','ASCII//TRANSLIT',$slug);
        $slug = preg_replace('/[^a-z0-9]+/','-',$slug);
        $slug = trim($slug,'-');
        $data[$colSlug] = $slug . '-' . date('ymd');
    }

    // Validation
    if (empty($data[$colTitle])) $errors[] = "Le titre est obligatoire";

    if (empty($errors)) {
        try {
            if ($isCreate) {
                $data['created_at'] = date('Y-m-d H:i:s');
                // Filtrer colonnes existantes uniquement
                $dataFiltered = array_intersect_key($data, array_flip(array_merge($availCols, ['updated_at','created_at'])));
                $cols = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($dataFiltered)));
                $vals = implode(', ', array_fill(0, count($dataFiltered), '?'));
                $stmt = $pdo->prepare("INSERT INTO properties ({$cols}) VALUES ({$vals})");
                $stmt->execute(array_values($dataFiltered));
                $newId = $pdo->lastInsertId();
                header("Location: ?page=properties&action=edit&id={$newId}&msg=created"); exit;
            } else {
                $dataFiltered = array_intersect_key($data, array_flip(array_merge($availCols, ['updated_at'])));
                $sets = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($dataFiltered)));
                $stmt = $pdo->prepare("UPDATE properties SET {$sets} WHERE id = ?");
                $stmt->execute([...array_values($dataFiltered), $propId]);
                header("Location: ?page=properties&action=edit&id={$propId}&msg=updated"); exit;
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur DB : " . $e->getMessage();
        }
    }
    // En cas d'erreur, pré-remplir les champs
    $property = array_merge($property, [
        'titre' => $_POST['titre'] ?? '',
        'description' => $_POST['description'] ?? '',
        'prix'    => $_POST['prix'] ?? '',
        'surface' => $_POST['surface'] ?? '',
        'ville'   => $_POST['ville'] ?? '',
        // etc.
    ]);
}

// ─── Helpers ───
function prop(array $p, ...$keys): string {
    foreach ($keys as $k) {
        if (isset($p[$k]) && $p[$k] !== '' && $p[$k] !== null) return (string)$p[$k];
    }
    return '';
}

$flash = $_GET['msg'] ?? '';
$photos = [];
$photosJson = prop($property, 'photos', 'images');
if ($photosJson) {
    $decoded = json_decode($photosJson, true);
    if (is_array($decoded)) $photos = $decoded;
    elseif (is_string($photosJson) && !empty($photosJson)) $photos = [$photosJson];
}
?>
<style>
/* ── Edit Form — Biens Immobiliers ── */
.bef-wrap { font-family: var(--font, 'Inter', sans-serif); }

.bef-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 24px; flex-wrap: wrap; gap: 12px;
}
.bef-header-left { display: flex; align-items: center; gap: 14px; }
.bef-back {
    width: 36px; height: 36px; border-radius: 10px;
    background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-2, #6b7280); text-decoration: none; transition: all .15s; font-size: .85rem;
}
.bef-back:hover { border-color: #1a4d7a; color: #1a4d7a; }
.bef-header h2 {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.3rem; font-weight: 700; color: var(--text, #111827); margin: 0;
}
.bef-header p { color: var(--text-3, #9ca3af); font-size: .8rem; margin: 2px 0 0; }

/* Layout 2 cols */
.bef-layout { display: grid; grid-template-columns: 1fr 340px; gap: 18px; align-items: start; }
@media (max-width: 1100px) { .bef-layout { grid-template-columns: 1fr; } }

/* Cartes */
.bef-card {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 14px; overflow: hidden; margin-bottom: 16px;
}
.bef-card-header {
    padding: 14px 18px; border-bottom: 1px solid var(--border, #e5e7eb);
    display: flex; align-items: center; gap: 10px;
    font-size: .78rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .05em; color: var(--text-2, #6b7280);
    background: var(--surface-2, #f9fafb);
}
.bef-card-header i { color: #1a4d7a; font-size: .85rem; }
.bef-card-body { padding: 18px; }

/* Champs */
.bef-row { display: grid; gap: 14px; margin-bottom: 14px; }
.bef-row-2 { grid-template-columns: 1fr 1fr; }
.bef-row-3 { grid-template-columns: 1fr 1fr 1fr; }
.bef-row-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
@media (max-width: 700px) { .bef-row-2,.bef-row-3,.bef-row-4 { grid-template-columns: 1fr; } }

.bef-field { display: flex; flex-direction: column; gap: 5px; }
.bef-label {
    font-size: .72rem; font-weight: 700; color: var(--text-2, #6b7280);
    text-transform: uppercase; letter-spacing: .04em; display: flex; align-items: center; gap: 5px;
}
.bef-label span.req { color: #ef4444; }
.bef-input, .bef-select, .bef-textarea {
    padding: 9px 12px; border: 1.5px solid var(--border, #e5e7eb);
    border-radius: 8px; background: var(--surface, #fff);
    color: var(--text, #111827); font-size: .83rem;
    font-family: var(--font, 'Inter', sans-serif);
    transition: border-color .15s, box-shadow .15s;
    width: 100%; box-sizing: border-box;
}
.bef-input:focus, .bef-select:focus, .bef-textarea:focus {
    outline: none; border-color: #1a4d7a; box-shadow: 0 0 0 3px rgba(26,77,122,.08);
}
.bef-textarea { min-height: 120px; resize: vertical; line-height: 1.55; }
.bef-hint { font-size: .68rem; color: var(--text-3, #9ca3af); }

/* Price input spécial */
.bef-price-wrap { position: relative; }
.bef-price-wrap .bef-input { padding-right: 36px; font-weight: 700; font-size: .95rem; }
.bef-price-wrap::after { content:'€'; position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--text-3,#9ca3af); font-size:.85rem; pointer-events:none; }

/* Photos */
.bef-photos-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 8px; margin-bottom: 10px;
}
.bef-photo-item {
    position: relative; border-radius: 8px; overflow: hidden;
    aspect-ratio: 4/3; background: var(--surface-2, #f9fafb);
    border: 1px solid var(--border, #e5e7eb); cursor: grab;
}
.bef-photo-item img { width: 100%; height: 100%; object-fit: cover; }
.bef-photo-del {
    position: absolute; top: 4px; right: 4px;
    width: 22px; height: 22px; border-radius: 50%;
    background: rgba(220,38,38,.85); color: #fff; border: none;
    display: flex; align-items: center; justify-content: center;
    font-size: .6rem; cursor: pointer; transition: all .15s;
}
.bef-photo-del:hover { background: #dc2626; transform: scale(1.1); }
.bef-photo-main-badge {
    position: absolute; bottom: 4px; left: 4px;
    background: #1a4d7a; color: #fff; font-size: .55rem;
    padding: 1px 5px; border-radius: 4px; font-weight: 700; letter-spacing: .03em;
}

.bef-upload-zone {
    border: 2px dashed var(--border, #e5e7eb); border-radius: 10px;
    padding: 20px; text-align: center; cursor: pointer;
    color: var(--text-3, #9ca3af); transition: all .2s;
    background: var(--surface-2, #f9fafb);
}
.bef-upload-zone:hover { border-color: #1a4d7a; color: #1a4d7a; background: rgba(26,77,122,.02); }
.bef-upload-zone i { font-size: 1.5rem; margin-bottom: 6px; display: block; }
.bef-upload-zone span { font-size: .78rem; display: block; }

/* DPE selector */
.bef-dpe-grid { display: flex; gap: 6px; flex-wrap: wrap; }
.bef-dpe-btn {
    width: 36px; height: 36px; border-radius: 6px; border: 2px solid transparent;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 800; cursor: pointer; transition: all .15s; letter-spacing: .02em;
}
.bef-dpe-btn:hover { transform: scale(1.08); }
.bef-dpe-btn[data-d="A"] { background: #059669; color: #fff; }
.bef-dpe-btn[data-d="B"] { background: #34d399; color: #fff; }
.bef-dpe-btn[data-d="C"] { background: #86efac; color: #111; }
.bef-dpe-btn[data-d="D"] { background: #fde68a; color: #92400e; }
.bef-dpe-btn[data-d="E"] { background: #fed7aa; color: #92400e; }
.bef-dpe-btn[data-d="F"] { background: #fca5a5; color: #991b1b; }
.bef-dpe-btn[data-d="G"] { background: #ef4444; color: #fff; }
.bef-dpe-btn.selected { box-shadow: 0 0 0 3px rgba(26,77,122,.3); border-color: #1a4d7a; transform: scale(1.12); }

/* Toggle */
.bef-toggle-wrap { display: flex; align-items: center; gap: 10px; }
.bef-toggle {
    width: 42px; height: 22px; border-radius: 11px;
    background: var(--border, #e5e7eb); cursor: pointer;
    transition: background .2s; position: relative; flex-shrink: 0;
    border: none;
}
.bef-toggle::after {
    content: ''; position: absolute; top: 2px; left: 2px;
    width: 18px; height: 18px; border-radius: 50%; background: #fff;
    transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.2);
}
.bef-toggle.on { background: #1a4d7a; }
.bef-toggle.on::after { transform: translateX(20px); }
.bef-toggle-label { font-size: .82rem; color: var(--text, #111827); font-weight: 500; }

/* Statut selector cards */
.bef-status-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.bef-status-card {
    padding: 10px 12px; border-radius: 8px;
    border: 1.5px solid var(--border, #e5e7eb);
    cursor: pointer; transition: all .15s; text-align: center;
}
.bef-status-card:hover { border-color: #1a4d7a; }
.bef-status-card.selected { border-color: #1a4d7a; background: rgba(26,77,122,.05); }
.bef-status-card .sc-icon { font-size: 1rem; margin-bottom: 4px; }
.bef-status-card .sc-label { font-size: .7rem; font-weight: 700; color: var(--text-2, #6b7280); }
.bef-status-card.selected .sc-label { color: #1a4d7a; }

/* Boutons action */
.bef-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.bef-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 20px; border-radius: 10px; font-size: .83rem; font-weight: 600;
    cursor: pointer; border: none; transition: all .15s;
    font-family: var(--font, 'Inter', sans-serif); text-decoration: none;
}
.bef-btn-primary { background: #1a4d7a; color: #fff; flex: 1; justify-content: center; }
.bef-btn-primary:hover { background: #0f3356; }
.bef-btn-gold { background: #d4a574; color: #fff; }
.bef-btn-gold:hover { background: #c0936a; color: #fff; }
.bef-btn-outline { background: var(--surface, #fff); color: var(--text-2, #6b7280); border: 1px solid var(--border, #e5e7eb); }
.bef-btn-outline:hover { border-color: #1a4d7a; color: #1a4d7a; }

/* Erreurs */
.bef-errors { background: #fef2f2; border: 1px solid rgba(220,38,38,.15); border-radius: 10px; padding: 14px 18px; margin-bottom: 18px; }
.bef-errors ul { margin: 0; padding: 0 0 0 16px; }
.bef-errors li { color: #dc2626; font-size: .83rem; margin-bottom: 3px; }

/* Flash */
.bef-flash { padding: 12px 18px; border-radius: 10px; font-size: .85rem; font-weight: 600; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; animation: befFlash .3s; }
.bef-flash.success { background: #d1fae5; color: #059669; border: 1px solid rgba(5,150,105,.12); }
@keyframes befFlash { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }

/* SEO score mini */
.bef-seo-meter { margin-top: 8px; }
.bef-seo-bar { height: 5px; background: var(--border, #e5e7eb); border-radius: 3px; overflow: hidden; }
.bef-seo-fill { height: 100%; border-radius: 3px; background: #10b981; transition: width .5s; }
.bef-seo-label { font-size: .68rem; color: var(--text-3, #9ca3af); display: flex; justify-content: space-between; margin-top: 3px; }
</style>

<div class="bef-wrap">

<?php if ($flash === 'created'): ?><div class="bef-flash success"><i class="fas fa-check-circle"></i> Bien créé avec succès !</div><?php endif; ?>
<?php if ($flash === 'updated'): ?><div class="bef-flash success"><i class="fas fa-check-circle"></i> Bien mis à jour avec succès !</div><?php endif; ?>

<?php if (!empty($errors)): ?>
<div class="bef-errors"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<!-- Header -->
<div class="bef-header">
    <div class="bef-header-left">
        <a href="?page=properties" class="bef-back"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h2><?= $isCreate ? 'Nouveau bien' : 'Modifier le bien' ?></h2>
            <p><?= $isCreate ? 'Remplissez les informations du bien immobilier' : 'Réf. ' . htmlspecialchars(prop($property, 'reference', 'ref', 'id')) ?></p>
        </div>
    </div>
    <div class="bef-actions">
        <?php if (!$isCreate && !empty($property['slug'] ?? '')): ?>
        <a href="/biens/<?= htmlspecialchars($property['slug'] ?? '') ?>" target="_blank" class="bef-btn bef-btn-outline"><i class="fas fa-external-link-alt"></i> Voir</a>
        <?php endif; ?>
        <button form="bef-form" type="submit" name="statut" value="brouillon" class="bef-btn bef-btn-outline"><i class="fas fa-save"></i> Brouillon</button>
        <button form="bef-form" type="submit" name="submit_publish" value="1" class="bef-btn bef-btn-primary"><i class="fas fa-check"></i> <?= $isCreate ? 'Créer &amp; publier' : 'Enregistrer' ?></button>
    </div>
</div>

<form id="bef-form" method="POST" action="">

<div class="bef-layout">
<!-- ══ Colonne principale ══════════════════════════ -->
<div>

    <!-- Infos principales -->
    <div class="bef-card">
        <div class="bef-card-header"><i class="fas fa-home"></i> Informations principales</div>
        <div class="bef-card-body">
            <div class="bef-field" style="margin-bottom:14px">
                <label class="bef-label">Titre du bien <span class="req">*</span></label>
                <input type="text" name="titre" class="bef-input" style="font-size:.95rem;font-weight:600"
                       placeholder="Ex : Maison 5 pièces avec jardin – Blanquefort"
                       value="<?= htmlspecialchars(prop($property, 'titre', 'title')) ?>" required>
            </div>
            <div class="bef-row bef-row-2">
                <div class="bef-field">
                    <label class="bef-label">Type de bien</label>
                    <select name="type_bien" class="bef-select">
                        <?php
                        $typesList = ['Maison','Appartement','Villa','Studio','Terrain','Local commercial','Immeuble','Parking','Garage'];
                        $currentType = prop($property, 'type_bien', 'type');
                        foreach ($typesList as $t):
                        ?>
                        <option value="<?= $t ?>" <?= $currentType === $t ? 'selected' : '' ?>><?= $t ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="bef-field">
                    <label class="bef-label">Transaction</label>
                    <select name="transaction" class="bef-select">
                        <option value="vente"    <?= prop($property,'transaction','transaction_type') === 'vente'    ? 'selected':'' ?>>Vente</option>
                        <option value="location" <?= prop($property,'transaction','transaction_type') === 'location' ? 'selected':'' ?>>Location</option>
                    </select>
                </div>
            </div>
            <div class="bef-field" style="margin-bottom:14px">
                <label class="bef-label">Description / Annonce</label>
                <textarea name="description" class="bef-textarea" placeholder="Décrivez le bien avec soin : atouts, environnement, points forts…"><?= htmlspecialchars(prop($property,'description','contenu')) ?></textarea>
            </div>
        </div>
    </div>

    <!-- Caractéristiques -->
    <div class="bef-card">
        <div class="bef-card-header"><i class="fas fa-ruler-combined"></i> Caractéristiques</div>
        <div class="bef-card-body">
            <div class="bef-row bef-row-4">
                <div class="bef-field">
                    <label class="bef-label">Prix (€) <span class="req">*</span></label>
                    <div class="bef-price-wrap">
                        <input type="number" name="prix" class="bef-input" placeholder="350000"
                               value="<?= htmlspecialchars(prop($property,'prix','price')) ?>" min="0">
                    </div>
                </div>
                <div class="bef-field">
                    <label class="bef-label">Surface (m²)</label>
                    <input type="number" name="surface" class="bef-input" placeholder="120"
                           value="<?= htmlspecialchars(prop($property,'surface','area')) ?>" min="0" step="0.5">
                </div>
                <div class="bef-field">
                    <label class="bef-label">Pièces</label>
                    <input type="number" name="pieces" class="bef-input" placeholder="5"
                           value="<?= htmlspecialchars(prop($property,'pieces','rooms')) ?>" min="0">
                </div>
                <div class="bef-field">
                    <label class="bef-label">Chambres</label>
                    <input type="number" name="chambres" class="bef-input" placeholder="3"
                           value="<?= htmlspecialchars(prop($property,'chambres','bedrooms')) ?>" min="0">
                </div>
            </div>
            <div class="bef-row bef-row-3">
                <div class="bef-field">
                    <label class="bef-label">Salles de bain</label>
                    <input type="number" name="salles_bain" class="bef-input" placeholder="2"
                           value="<?= htmlspecialchars(prop($property,'salles_bain','bathrooms')) ?>" min="0">
                </div>
                <div class="bef-field">
                    <label class="bef-label">Année construction</label>
                    <input type="number" name="annee_construction" class="bef-input" placeholder="1998"
                           value="<?= htmlspecialchars(prop($property,'annee_construction','year_built')) ?>" min="1800" max="<?= date('Y') ?>">
                </div>
                <div class="bef-field">
                    <label class="bef-label">Référence interne</label>
                    <input type="text" name="reference" class="bef-input" placeholder="EDU-2024-001"
                           value="<?= htmlspecialchars(prop($property,'reference','ref')) ?>">
                </div>
            </div>
            <?php if ($colCharges): ?>
            <div class="bef-row bef-row-2">
                <div class="bef-field">
                    <label class="bef-label">Charges (€/mois)</label>
                    <input type="number" name="charges" class="bef-input" placeholder="200"
                           value="<?= htmlspecialchars(prop($property,'charges')) ?>" min="0">
                </div>
                <div class="bef-field">
                    <label class="bef-label">Honoraires (%)</label>
                    <input type="number" name="honoraires" class="bef-input" placeholder="5.5"
                           value="<?= htmlspecialchars(prop($property,'honoraires')) ?>" min="0" step="0.1">
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Localisation -->
    <div class="bef-card">
        <div class="bef-card-header"><i class="fas fa-map-marker-alt"></i> Localisation</div>
        <div class="bef-card-body">
            <?php if ($colAddress): ?>
            <div class="bef-field" style="margin-bottom:14px">
                <label class="bef-label">Adresse</label>
                <input type="text" name="adresse" class="bef-input" placeholder="15 rue des Lilas"
                       value="<?= htmlspecialchars(prop($property,'adresse','address')) ?>">
            </div>
            <?php endif; ?>
            <div class="bef-row bef-row-3">
                <div class="bef-field">
                    <label class="bef-label">Ville <span class="req">*</span></label>
                    <input type="text" name="ville" class="bef-input" placeholder="Bordeaux"
                           value="<?= htmlspecialchars(prop($property,'ville','city')) ?>" required>
                </div>
                <div class="bef-field">
                    <label class="bef-label">Code postal</label>
                    <input type="text" name="code_postal" class="bef-input" placeholder="33000"
                           value="<?= htmlspecialchars(prop($property,'code_postal','zip')) ?>">
                </div>
                <?php if ($colLat && $colLng): ?>
                <div class="bef-field">
                    <label class="bef-label">Coordonnées GPS</label>
                    <div style="display:flex;gap:6px">
                        <input type="number" name="latitude" class="bef-input" placeholder="44.84" step="any" value="<?= htmlspecialchars(prop($property,'latitude')) ?>">
                        <input type="number" name="longitude" class="bef-input" placeholder="-0.58" step="any" value="<?= htmlspecialchars(prop($property,'longitude')) ?>">
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Photos -->
    <div class="bef-card">
        <div class="bef-card-header"><i class="fas fa-images"></i> Photos du bien</div>
        <div class="bef-card-body">
            <div class="bef-photos-grid" id="befPhotosGrid">
                <?php foreach ($photos as $i => $photo): ?>
                <div class="bef-photo-item" data-idx="<?= $i ?>">
                    <img src="<?= htmlspecialchars($photo) ?>" alt="Photo <?= $i+1 ?>">
                    <?php if ($i === 0): ?><span class="bef-photo-main-badge">Principale</span><?php endif; ?>
                    <button type="button" class="bef-photo-del" onclick="BEF.removePhoto(<?= $i ?>)"><i class="fas fa-times"></i></button>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Upload zone -->
            <div class="bef-upload-zone" id="befUploadZone" onclick="document.getElementById('befFileInput').click()">
                <i class="fas fa-cloud-upload-alt"></i>
                <span><strong>Cliquer pour ajouter des photos</strong> ou glisser-déposer</span>
                <span style="font-size:.7rem;margin-top:4px;display:block;opacity:.7">JPG, PNG, WebP — Max 5 MB par photo</span>
            </div>
            <input type="file" id="befFileInput" multiple accept="image/*" style="display:none" onchange="BEF.handleFiles(this.files)">
            <input type="hidden" name="photos_json" id="befPhotosJson" value="<?= htmlspecialchars(json_encode($photos)) ?>">
            <p class="bef-hint" style="margin-top:8px"><i class="fas fa-grip-vertical"></i> Glissez les photos pour réorganiser. La première sera la photo principale.</p>
        </div>
    </div>

    <!-- DPE & Énergie -->
    <div class="bef-card">
        <div class="bef-card-header"><i class="fas fa-leaf"></i> DPE &amp; Performances énergétiques</div>
        <div class="bef-card-body">
            <div class="bef-row bef-row-2">
                <div class="bef-field">
                    <label class="bef-label">Classe énergie (DPE)</label>
                    <div class="bef-dpe-grid" id="befDpeGrid">
                        <?php foreach (['A','B','C','D','E','F','G'] as $d): ?>
                        <button type="button" class="bef-dpe-btn <?= prop($property,'dpe','classe_energie') === $d ? 'selected' : '' ?>"
                                data-d="<?= $d ?>" onclick="BEF.selectDpe('<?= $d ?>')"><?= $d ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="dpe" id="befDpeInput" value="<?= htmlspecialchars(prop($property,'dpe','classe_energie')) ?>">
                </div>
                <?php if ($colGes): ?>
                <div class="bef-field">
                    <label class="bef-label">Classe GES</label>
                    <div class="bef-dpe-grid" id="befGesGrid">
                        <?php foreach (['A','B','C','D','E','F','G'] as $d): ?>
                        <button type="button" class="bef-dpe-btn <?= prop($property,'ges') === $d ? 'selected' : '' ?>"
                                data-d="<?= $d ?>" onclick="BEF.selectGes('<?= $d ?>')"><?= $d ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="ges" id="befGesInput" value="<?= htmlspecialchars(prop($property,'ges')) ?>">
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- SEO -->
    <div class="bef-card">
        <div class="bef-card-header"><i class="fas fa-search"></i> SEO &amp; Référencement</div>
        <div class="bef-card-body">
            <div class="bef-field" style="margin-bottom:12px">
                <label class="bef-label">Slug URL</label>
                <input type="text" name="slug" class="bef-input" style="font-family:monospace;font-size:.8rem"
                       placeholder="maison-5-pieces-bordeaux-merignac"
                       value="<?= htmlspecialchars(prop($property,'slug')) ?>">
                <span class="bef-hint">Laissez vide pour génération automatique</span>
            </div>
            <?php if ($colKeyword): ?>
            <div class="bef-field" style="margin-bottom:12px">
                <label class="bef-label">Mot-clé principal</label>
                <input type="text" name="focus_keyword" class="bef-input" placeholder="maison à vendre Bordeaux"
                       value="<?= htmlspecialchars(prop($property,'focus_keyword')) ?>">
            </div>
            <?php endif; ?>
            <?php if ($colSeoTitle): ?>
            <div class="bef-field" style="margin-bottom:12px">
                <label class="bef-label">Title SEO <span class="bef-hint" style="text-transform:none;font-weight:400">(max 60 car.)</span></label>
                <input type="text" name="meta_title" class="bef-input" id="befMetaTitle" maxlength="70"
                       placeholder="Maison 5 pièces – Bordeaux Mérignac | Eduardo De Sul"
                       value="<?= htmlspecialchars(prop($property,'meta_title')) ?>"
                       oninput="BEF.updateSeoMeter()">
                <div class="bef-seo-meter">
                    <div class="bef-seo-bar"><div class="bef-seo-fill" id="befTitleBar" style="width:0"></div></div>
                    <div class="bef-seo-label"><span id="befTitleLen">0/60</span><span id="befTitleTip">Trop court</span></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($colSeoDesc): ?>
            <div class="bef-field">
                <label class="bef-label">Meta description <span class="bef-hint" style="text-transform:none;font-weight:400">(max 160 car.)</span></label>
                <textarea name="meta_description" class="bef-textarea" style="min-height:80px" id="befMetaDesc" maxlength="180"
                          placeholder="Découvrez cette magnifique maison..."
                          oninput="BEF.updateSeoMeter()"><?= htmlspecialchars(prop($property,'meta_description')) ?></textarea>
                <div class="bef-seo-meter">
                    <div class="bef-seo-bar"><div class="bef-seo-fill" id="befDescBar" style="width:0"></div></div>
                    <div class="bef-seo-label"><span id="befDescLen">0/160</span><span id="befDescTip">Trop court</span></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /col main -->

<!-- ══ Colonne latérale ══════════════════════════ -->
<div>

    <!-- Statut publication -->
    <div class="bef-card">
        <div class="bef-card-header"><i class="fas fa-toggle-on"></i> Statut &amp; Publication</div>
        <div class="bef-card-body">
            <div class="bef-status-grid" id="befStatusGrid">
                <?php
                $currentStatus = prop($property,'statut','status') ?: 'brouillon';
                $statusOpts = [
                    'actif'      => ['fa-eye',        'Disponible', 'green'],
                    'brouillon'  => ['fa-pencil-alt',  'Brouillon',  'gray'],
                    'vendu'      => ['fa-handshake',   'Vendu',      'red'],
                    'archive'    => ['fa-archive',     'Archivé',    'gray'],
                ];
                foreach ($statusOpts as $val => [$icon, $label, $color]):
                    $active = (normPropertyStatus($currentStatus) === normPropertyStatus($val)) ? ' selected' : '';
                ?>
                <div class="bef-status-card<?= $active ?>" onclick="BEF.selectStatus('<?= $val ?>')" data-status="<?= $val ?>">
                    <div class="sc-icon"><i class="fas <?= $icon ?>"></i></div>
                    <div class="sc-label"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" name="statut" id="befStatusInput" value="<?= htmlspecialchars($currentStatus) ?>">
        </div>
    </div>

    <!-- Mandat -->
    <div class="bef-card">
        <div class="bef-card-header"><i class="fas fa-file-signature"></i> Mandat</div>
        <div class="bef-card-body">
            <div class="bef-field">
                <label class="bef-label">Type de mandat</label>
                <select name="mandat" class="bef-select">
                    <option value="simple"   <?= prop($property,'mandat','type_mandat') === 'simple'   ? 'selected' : '' ?>>Simple</option>
                    <option value="exclusif" <?= prop($property,'mandat','type_mandat') === 'exclusif' ? 'selected' : '' ?>>Exclusif</option>
                    <option value="co-exclusif" <?= prop($property,'mandat','type_mandat') === 'co-exclusif' ? 'selected' : '' ?>>Co-exclusif</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Mise en avant -->
    <div class="bef-card">
        <div class="bef-card-header"><i class="fas fa-star"></i> Mise en avant</div>
        <div class="bef-card-body">
            <div class="bef-toggle-wrap" style="padding:4px 0">
                <button type="button" class="bef-toggle <?= !empty($property[$colFeat]) ? 'on' : '' ?>"
                        id="befFeaturedToggle" onclick="BEF.toggleFeatured()"></button>
                <span class="bef-toggle-label">Afficher en page d'accueil &amp; à la une</span>
            </div>
            <input type="hidden" name="is_featured" id="befFeaturedInput" value="<?= !empty($property[$colFeat]) ? '1' : '0' ?>">
            <p class="bef-hint" style="margin-top:10px"><i class="fas fa-info-circle"></i> Les biens à la une apparaissent en priorité sur votre site.</p>
        </div>
    </div>

    <!-- Actions rapides -->
    <div class="bef-card">
        <div class="bef-card-header"><i class="fas fa-bolt"></i> Actions</div>
        <div class="bef-card-body" style="display:flex;flex-direction:column;gap:8px">
            <button type="submit" class="bef-btn bef-btn-primary" style="width:100%;justify-content:center">
                <i class="fas fa-save"></i> <?= $isCreate ? 'Créer le bien' : 'Enregistrer' ?>
            </button>
            <?php if (!$isCreate): ?>
            <button type="button" class="bef-btn bef-btn-gold" style="width:100%;justify-content:center" onclick="BEF.duplicate()">
                <i class="fas fa-copy"></i> Dupliquer ce bien
            </button>
            <?php endif; ?>
            <a href="?page=properties" class="bef-btn bef-btn-outline" style="width:100%;justify-content:center;box-sizing:border-box">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
            <?php if (!$isCreate): ?>
            <button type="button" class="bef-btn" style="background:#fef2f2;color:#dc2626;border:1px solid rgba(220,38,38,.15);width:100%;justify-content:center"
                    onclick="BEF.deleteProp(<?= $propId ?>)">
                <i class="fas fa-trash"></i> Supprimer ce bien
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Infos rapides -->
    <?php if (!$isCreate): ?>
    <div class="bef-card">
        <div class="bef-card-header"><i class="fas fa-info-circle"></i> Infos</div>
        <div class="bef-card-body" style="font-size:.75rem;color:var(--text-2,#6b7280);line-height:1.8">
            <div>ID : <strong>#<?= $propId ?></strong></div>
            <?php if (!empty($property['created_at'])): ?>
            <div>Créé le : <strong><?= date('d/m/Y H:i', strtotime($property['created_at'])) ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($property['updated_at'])): ?>
            <div>Modifié le : <strong><?= date('d/m/Y H:i', strtotime($property['updated_at'])) ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($property['slug'])): ?>
            <div style="margin-top:6px">
                <span style="font-family:monospace;font-size:.7rem;word-break:break-all;color:var(--text-3,#9ca3af)">/biens/<?= htmlspecialchars($property['slug']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /col sidebar -->
</div><!-- /bef-layout -->
</form>
</div><!-- /bef-wrap -->

<script>
const BEF = {
    photos: <?= json_encode($photos) ?>,
    propId: <?= $propId ?: 'null' ?>,
    apiUrl: '/admin/api/immobilier/properties.php',

    // ── DPE ──
    selectDpe(d) {
        document.querySelectorAll('#befDpeGrid .bef-dpe-btn').forEach(b => b.classList.toggle('selected', b.dataset.d === d));
        document.getElementById('befDpeInput').value = d;
    },
    selectGes(d) {
        document.querySelectorAll('#befGesGrid .bef-dpe-btn').forEach(b => b.classList.toggle('selected', b.dataset.d === d));
        document.getElementById('befGesInput').value = d;
    },

    // ── Statut ──
    selectStatus(val) {
        document.querySelectorAll('#befStatusGrid .bef-status-card').forEach(c => c.classList.toggle('selected', c.dataset.status === val));
        document.getElementById('befStatusInput').value = val;
    },

    // ── Featured ──
    toggleFeatured() {
        const tog = document.getElementById('befFeaturedToggle');
        const inp = document.getElementById('befFeaturedInput');
        const on  = !tog.classList.contains('on');
        tog.classList.toggle('on', on);
        inp.value = on ? '1' : '0';
    },

    // ── Photos ──
    handleFiles(files) {
        [...files].forEach(file => {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = e => {
                this.uploadPhoto(file, e.target.result);
            };
            reader.readAsDataURL(file);
        });
    },

    async uploadPhoto(file, preview) {
        // Preview immédiat
        const idx = this.photos.length;
        this.photos.push(preview);
        this.renderPhotos();

        // Upload réel
        const fd = new FormData();
        fd.append('action', 'upload_photo');
        fd.append('photo', file);
        if (this.propId) fd.append('property_id', this.propId);
        try {
            const r = await fetch(this.apiUrl, {method:'POST', body:fd});
            const d = await r.json();
            if (d.success && d.url) {
                this.photos[idx] = d.url;
                this.renderPhotos();
            }
        } catch (e) {
            // Garde le base64 comme fallback temporaire
        }
    },

    removePhoto(idx) {
        this.photos.splice(idx, 1);
        this.renderPhotos();
    },

    renderPhotos() {
        const grid = document.getElementById('befPhotosGrid');
        grid.innerHTML = this.photos.map((src, i) => `
            <div class="bef-photo-item" data-idx="${i}">
                <img src="${src}" alt="Photo ${i+1}">
                ${i === 0 ? '<span class="bef-photo-main-badge">Principale</span>' : ''}
                <button type="button" class="bef-photo-del" onclick="BEF.removePhoto(${i})"><i class="fas fa-times"></i></button>
            </div>
        `).join('');
        document.getElementById('befPhotosJson').value = JSON.stringify(this.photos);
    },

    // ── Upload zone drag & drop ──
    initUploadZone() {
        const zone = document.getElementById('befUploadZone');
        zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.borderColor='#1a4d7a'; });
        zone.addEventListener('dragleave', () => { zone.style.borderColor=''; });
        zone.addEventListener('drop', e => {
            e.preventDefault(); zone.style.borderColor='';
            this.handleFiles(e.dataTransfer.files);
        });
    },

    // ── SEO meters ──
    updateSeoMeter() {
        const titleEl = document.getElementById('befMetaTitle');
        const descEl  = document.getElementById('befMetaDesc');
        if (titleEl) {
            const l = titleEl.value.length;
            document.getElementById('befTitleLen').textContent = `${l}/60`;
            document.getElementById('befTitleBar').style.width = `${Math.min(100, l/60*100)}%`;
            document.getElementById('befTitleBar').style.background = l<30?'#ef4444':l<50?'#f59e0b':'#10b981';
            document.getElementById('befTitleTip').textContent = l<30?'Trop court':l<=60?'Parfait !':'Trop long';
        }
        if (descEl) {
            const l = descEl.value.length;
            document.getElementById('befDescLen').textContent = `${l}/160`;
            document.getElementById('befDescBar').style.width = `${Math.min(100, l/160*100)}%`;
            document.getElementById('befDescBar').style.background = l<80?'#ef4444':l<130?'#f59e0b':'#10b981';
            document.getElementById('befDescTip').textContent = l<80?'Trop court':l<=160?'Parfait !':'Trop long';
        }
    },

    // ── Dupliquer ──
    async duplicate() {
        if (!confirm('Dupliquer ce bien ?')) return;
        const fd = new FormData();
        fd.append('action', 'duplicate'); fd.append('id', this.propId);
        const r = await fetch(this.apiUrl, {method:'POST', body:fd});
        const d = await r.json();
        if (d.success && d.new_id) window.location.href = `?page=properties&action=edit&id=${d.new_id}&msg=created`;
        else alert(d.error || 'Erreur');
    },

    // ── Supprimer ──
    async deleteProp(id) {
        if (!confirm('Supprimer définitivement ce bien ?')) return;
        const fd = new FormData();
        fd.append('action', 'delete'); fd.append('id', id);
        const r = await fetch(this.apiUrl, {method:'POST', body:fd});
        const d = await r.json();
        d.success ? window.location.href = '?page=properties&msg=deleted' : alert(d.error || 'Erreur');
    },

    init() {
        this.initUploadZone();
        this.updateSeoMeter();
    }
};

document.addEventListener('DOMContentLoaded', () => BEF.init());
</script>