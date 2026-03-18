<?php
/**
 * Module Secteurs / Zones / Quartiers — Liste & Gestion
 * /admin/modules/content/secteurs/index.php
 *
 * Design system IMMO LOCAL+ (variables CSS globales header.php)
 * Charg&eacute; via dashboard.php : ?page=secteurs
 */

// ─── Connexion DB ───
if (!isset($pdo) && !isset($db)) {
    $inits = [
        dirname(dirname(dirname(dirname(__DIR__)))) . '/includes/init.php',
        dirname(dirname(dirname(__DIR__)))           . '/includes/init.php',
        dirname(dirname(__DIR__))                    . '/includes/init.php',
    ];
    foreach ($inits as $f) { if (file_exists($f)) { require_once $f; break; } }
}
if (!isset($pdo) && isset($db))  $pdo = $db;
if (!isset($db)  && isset($pdo)) $db  = $pdo;

// CSRF
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

// ─── CREATE TABLE si absente ───
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `secteurs` (
        `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `nom`              VARCHAR(255) NOT NULL,
        `slug`             VARCHAR(255) NOT NULL,
        `ville`            VARCHAR(100) DEFAULT 'Bordeaux',
        `type_secteur`     ENUM('quartier','commune') DEFAULT 'quartier',
        `description`      TEXT DEFAULT NULL,
        `content`          LONGTEXT DEFAULT NULL,
        `atouts`           TEXT DEFAULT NULL,
        `prix_moyen`       VARCHAR(50) DEFAULT NULL,
        `transport`        TEXT DEFAULT NULL,
        `ambiance`         VARCHAR(255) DEFAULT NULL,
        `hero_image`       VARCHAR(500) DEFAULT NULL,
        `hero_title`       VARCHAR(255) DEFAULT NULL,
        `hero_subtitle`    VARCHAR(255) DEFAULT NULL,
        `hero_cta_text`    VARCHAR(100) DEFAULT NULL,
        `hero_cta_url`     VARCHAR(255) DEFAULT NULL,
        `meta_title`       VARCHAR(160) DEFAULT NULL,
        `meta_description` VARCHAR(320) DEFAULT NULL,
        `meta_keywords`    VARCHAR(255) DEFAULT NULL,
        `seo_score`        INT DEFAULT 0,
        `word_count`       INT DEFAULT 0,
        `template_id`      INT UNSIGNED DEFAULT NULL,
        `status`           ENUM('draft','published','archived') DEFAULT 'draft',
        `created_at`       DATETIME DEFAULT CURRENT_TIMESTAMP,
        `updated_at`       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `uk_slug` (`slug`),
        KEY `idx_status` (`status`),
        KEY `idx_ville`  (`ville`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (PDOException $e) {}

// ─── Colonnes disponibles ───
$cols = [];
try { $cols = $pdo->query("SHOW COLUMNS FROM secteurs")->fetchAll(PDO::FETCH_COLUMN); } catch (Throwable $e) {}
$has = fn(string $c): bool => in_array($c, $cols);

// ─── ACTION : cr&eacute;ation rapide ───
if (($_GET['action'] ?? '') === 'create' && hash_equals($csrf, $_GET['csrf_token'] ?? '')) {
    try {
        $pdo->prepare("INSERT INTO secteurs (nom,slug,ville,type_secteur,status,created_at)
                       VALUES (?,?,?,?,'draft',NOW())")
            ->execute(['Nouveau secteur', 'nouveau-secteur-'.time(), 'Bordeaux', 'quartier']);
        $newId = (int)$pdo->lastInsertId();
        header("Location: /admin/modules/content/secteurs/edit.php?id={$newId}&msg=created");
        exit;
    } catch (PDOException $e) {}
}

// ─── AJAX delete ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    try {
        $pdo->prepare("DELETE FROM secteurs WHERE id=?")->execute([$id]);
        echo json_encode(['success' => true]); exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]); exit;
    }
}

// ─── AJAX toggle status ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'toggle_status') {
    header('Content-Type: application/json');
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] === 'published' ? 'published' : 'draft';
    try {
        $pdo->prepare("UPDATE secteurs SET status=? WHERE id=?")->execute([$status, $id]);
        echo json_encode(['success' => true]); exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]); exit;
    }
}

// ─── AJAX duplicate ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'duplicate') {
    header('Content-Type: application/json');
    $id = (int)($_POST['id'] ?? 0);
    try {
        $src = $pdo->prepare("SELECT * FROM secteurs WHERE id=?");
        $src->execute([$id]);
        $row = $src->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            unset($row['id'], $row['created_at'], $row['updated_at']);
            $row['nom']    = 'Copie — ' . $row['nom'];
            $row['slug']   = $row['slug'] . '-copie-' . time();
            $row['status'] = 'draft';
            $cols2 = array_keys($row);
            $pdo->prepare("INSERT INTO secteurs (`".implode('`,`',$cols2)."`) VALUES (".implode(',',array_fill(0,count($cols2),'?')).")")
                ->execute(array_values($row));
            echo json_encode(['success' => true, 'new_id' => (int)$pdo->lastInsertId()]); exit;
        }
        echo json_encode(['success' => false, 'error' => 'Not found']); exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]); exit;
    }
}

// ─── Filtres ───
$filterStatus = $_GET['status']      ?? 'all';
$filterType   = $_GET['type_secteur']?? 'all';
$filterVille  = $_GET['ville']       ?? '';
$searchQuery  = trim($_GET['q']      ?? '');
$curPage      = max(1, (int)($_GET['p'] ?? 1));
$perPage      = 25;
$offset       = ($curPage - 1) * $perPage;

$where = []; $params = [];
if ($filterStatus !== 'all' && in_array($filterStatus, ['draft','published','archived'])) {
    $where[] = "s.status = ?"; $params[] = $filterStatus;
}
if ($filterType !== 'all' && in_array($filterType, ['quartier','commune'])) {
    $where[] = "s.type_secteur = ?"; $params[] = $filterType;
}
if ($filterVille !== '') {
    $where[] = "s.ville = ?"; $params[] = $filterVille;
}
if ($searchQuery !== '') {
    $where[] = "(s.nom LIKE ? OR s.slug LIKE ? OR s.ville LIKE ?)";
    $params[] = "%{$searchQuery}%"; $params[] = "%{$searchQuery}%"; $params[] = "%{$searchQuery}%";
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ─── Stats globales ───
$stats = ['total'=>0,'published'=>0,'draft'=>0,'archived'=>0,'quartiers'=>0,'communes'=>0];
try {
    $r = $pdo->query("SELECT
        COUNT(*) AS total,
        SUM(status='published') AS published,
        SUM(status='draft') AS draft,
        SUM(status='archived') AS archived,
        SUM(type_secteur='quartier') AS quartiers,
        SUM(type_secteur='commune') AS communes
        FROM secteurs");
    $stats = $r->fetch(PDO::FETCH_ASSOC) ?: $stats;
} catch (PDOException $e) {}

// ─── Villes disponibles pour filtre ───
$villes = [];
try {
    $villes = $pdo->query("SELECT DISTINCT ville FROM secteurs WHERE ville IS NOT NULL AND ville != '' ORDER BY ville")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {}

// ─── Compte filtr&eacute; ───
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM secteurs s {$whereSQL}");
$stmtCount->execute($params);
$totalFiltered = (int)$stmtCount->fetchColumn();
$totalPages    = max(1, ceil($totalFiltered / $perPage));

// ─── SELECT colonnes ───
$selectCols = ['s.id','s.nom','s.slug','s.ville','s.type_secteur','s.status','s.created_at','s.updated_at'];
if ($has('description'))  $selectCols[] = 's.description';
if ($has('hero_image'))   $selectCols[] = 's.hero_image';
if ($has('prix_moyen'))   $selectCols[] = 's.prix_moyen';
if ($has('seo_score'))    $selectCols[] = 's.seo_score';
if ($has('word_count'))   $selectCols[] = 's.word_count';
if ($has('ambiance'))     $selectCols[] = 's.ambiance';
$colsSQL = implode(', ', $selectCols);

$stmtList = $pdo->prepare("SELECT {$colsSQL} FROM secteurs s {$whereSQL} ORDER BY s.updated_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmtList->execute($params);
$secteurs = $stmtList->fetchAll(PDO::FETCH_ASSOC);

$flash = $_GET['msg'] ?? '';
?>

<style>
/* ── Module Secteurs — design system IMMO LOCAL+ ── */

/* Banner */
.sc-banner {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 20px 24px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
    flex-wrap: wrap;
}
.sc-banner::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, #0d9488, var(--accent), #7c3aed);
}
.sc-banner-left h2 {
    font-size: 16px;
    font-weight: 800;
    color: var(--text);
    margin: 0 0 4px;
    display: flex;
    align-items: center;
    gap: 9px;
    letter-spacing: -.02em;
}
.sc-banner-left h2 i { color: #0d9488; font-size: 14px; }
.sc-banner-left p { font-size: 12px; color: var(--text-3); margin: 0; }
.sc-stats { display: flex; gap: 8px; flex-wrap: wrap; }
.sc-stat {
    text-align: center;
    padding: 10px 14px;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    min-width: 68px;
    transition: box-shadow .15s;
    cursor: default;
}
.sc-stat:hover { box-shadow: var(--shadow-sm); border-color: #cbd5e1; }
.sc-stat .num {
    font-size: 20px;
    font-weight: 900;
    line-height: 1;
    letter-spacing: -.03em;
}
.sc-stat .num.c-teal   { color: #0d9488; }
.sc-stat .num.c-green  { color: var(--green); }
.sc-stat .num.c-amber  { color: var(--amber); }
.sc-stat .num.c-accent { color: var(--accent); }
.sc-stat .num.c-violet { color: #7c3aed; }
.sc-stat .num.c-text   { color: var(--text); }
.sc-stat .lbl {
    font-size: 9px;
    color: var(--text-3);
    text-transform: uppercase;
    letter-spacing: .07em;
    font-weight: 700;
    margin-top: 3px;
}

/* Toolbar */
.sc-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 14px;
    flex-wrap: wrap;
    gap: 10px;
}
.sc-filters {
    display: flex;
    gap: 2px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 3px;
    flex-wrap: wrap;
}
.sc-fbtn {
    padding: 6px 14px;
    border: none;
    background: transparent;
    color: var(--text-2);
    font-size: 12px;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: all .14s;
    font-family: var(--font);
    display: flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    white-space: nowrap;
}
.sc-fbtn:hover  { color: var(--text); background: var(--surface-2); }
.sc-fbtn.active { background: #0d9488; color: #fff; }
.sc-fbtn .cnt {
    font-size: 10px;
    padding: 1px 6px;
    border-radius: 10px;
    background: rgba(0,0,0,.07);
    font-weight: 700;
}
.sc-fbtn.active .cnt { background: rgba(255,255,255,.22); color: #fff; }

.sc-toolbar-r { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.sc-search { position: relative; display: flex; align-items: center; }
.sc-search i { position: absolute; left: 10px; color: var(--text-3); font-size: 11px; pointer-events: none; }
.sc-search input {
    padding: 7px 12px 7px 30px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text);
    font-size: 12.5px;
    width: 200px;
    font-family: var(--font);
    transition: all .18s;
    outline: none;
}
.sc-search input:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,.1); width: 240px; }
.sc-search input::placeholder { color: var(--text-3); }

/* Sub-filtres */
.sc-subfilters {
    display: flex;
    gap: 8px;
    margin-bottom: 12px;
    flex-wrap: wrap;
    align-items: center;
}
.sc-subfilter { display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-3); }
.sc-subfilter select {
    padding: 5px 10px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--surface);
    color: var(--text);
    font-size: 12px;
    font-family: var(--font);
    cursor: pointer;
    outline: none;
    transition: border-color .14s;
}
.sc-subfilter select:focus { border-color: #0d9488; }

/* Bulk */
.sc-bulk {
    display: none;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    background: rgba(13,148,136,.07);
    border: 1px solid rgba(13,148,136,.15);
    border-radius: var(--radius);
    margin-bottom: 10px;
    font-size: 12px;
    color: #0d9488;
    font-weight: 600;
}
.sc-bulk.active { display: flex; }
.sc-bulk select {
    padding: 5px 10px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    background: var(--surface);
    color: var(--text);
    font-size: 12px;
    font-family: var(--font);
    outline: none;
}

/* Table wrapper */
.sc-table-wrap {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

/* Type badge */
.sc-type {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 5px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
}
.sc-type.quartier { background: rgba(13,148,136,.1); color: #0d9488; }
.sc-type.commune  { background: rgba(124,58,237,.1);  color: #7c3aed; }

/* Statut */
.sc-status {
    display: inline-flex;
    align-items: center;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .04em;
    white-space: nowrap;
}
.sc-status.published { background: var(--green-bg);  color: var(--green); }
.sc-status.draft     { background: var(--amber-bg);  color: var(--amber); }
.sc-status.archived  { background: var(--surface-2); color: var(--text-3); }

/* Miniature hero */
.sc-thumb {
    width: 52px;
    height: 38px;
    border-radius: 6px;
    background-size: cover;
    background-position: center;
    background-color: var(--surface-3);
    border: 1px solid var(--border);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-3);
    font-size: 14px;
}

/* SEO score */
.sc-score {
    font-size: 12.5px;
    font-weight: 800;
    font-family: var(--mono);
}
.sc-score.good { color: var(--green); }
.sc-score.ok   { color: var(--amber); }
.sc-score.bad  { color: var(--red); }
.sc-score.none { color: var(--text-3); }

/* Prix pill */
.sc-prix {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 7px;
    background: var(--amber-bg);
    color: var(--amber);
    border-radius: 5px;
    font-size: 10px;
    font-weight: 700;
    white-space: nowrap;
}

/* Slug */
.sc-slug {
    font-family: var(--mono);
    font-size: 11px;
    color: var(--text-3);
    display: block;
    margin-top: 2px;
}
.sc-nom-link {
    color: var(--text);
    text-decoration: none;
    font-weight: 600;
    transition: color .14s;
    line-height: 1.3;
}
.sc-nom-link:hover { color: #0d9488; }

/* Actions */
.sc-acts {
    display: flex;
    gap: 2px;
    justify-content: flex-end;
}
.sc-act {
    width: 30px;
    height: 30px;
    border-radius: var(--radius);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-3);
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all .12s;
    text-decoration: none;
    font-size: 12px;
}
.sc-act:hover       { color: #0d9488; border-color: var(--border); background: rgba(13,148,136,.08); }
.sc-act.del:hover   { color: var(--red); border-color: rgba(239,68,68,.2); background: var(--red-bg); }
.sc-act.design:hover { color: #7c3aed; border-color: rgba(124,58,237,.2); background: rgba(124,58,237,.07); }

/* Pagination */
.sc-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-top: 1px solid var(--border);
    font-size: 12px;
    color: var(--text-3);
}
.sc-pages { display: flex; gap: 3px; }
.sc-page-btn {
    padding: 5px 11px;
    border: 1px solid var(--border);
    border-radius: var(--radius);
    color: var(--text-2);
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    transition: all .14s;
}
.sc-page-btn:hover  { border-color: #0d9488; color: #0d9488; background: rgba(13,148,136,.07); }
.sc-page-btn.active { background: #0d9488; color: #fff; border-color: #0d9488; }

/* Flash */
.sc-flash {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 11px 16px;
    border-radius: var(--radius);
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 14px;
    animation: scFlash .3s ease;
}
.sc-flash.ok  { background: var(--green-bg); color: var(--green); border: 1px solid rgba(16,185,129,.12); }
.sc-flash.err { background: var(--red-bg);   color: var(--red);   border: 1px solid rgba(239,68,68,.12); }
@keyframes scFlash { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

/* Empty */
.sc-empty { text-align: center; padding: 60px 20px; color: var(--text-3); }
.sc-empty i { font-size: 32px; opacity: .2; margin-bottom: 12px; display: block; }
.sc-empty h3 { font-size: 14px; font-weight: 700; color: var(--text-2); margin-bottom: 6px; }
.sc-empty p  { font-size: 13px; }
.sc-empty a  { color: #0d9488; text-decoration: none; }

/* Modal cr&eacute;ation */
.sc-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,.4);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
}
.sc-modal-overlay.open { display: flex; }
.sc-modal {
    background: var(--surface);
    border-radius: var(--radius-lg);
    width: 100%;
    max-width: 460px;
    margin: 16px;
    box-shadow: 0 24px 60px rgba(0,0,0,.16);
    overflow: hidden;
    animation: scModalIn .22s cubic-bezier(.34,1.56,.64,1);
}
@keyframes scModalIn { from { opacity:0; transform:scale(.9); } to { opacity:1; transform:scale(1); } }
.sc-modal-head {
    padding: 18px 22px 14px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.sc-modal-head h3 {
    font-size: 15px;
    font-weight: 800;
    color: var(--text);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}
.sc-modal-head h3 i { color: #0d9488; }
.sc-modal-close {
    width: 28px; height: 28px;
    border-radius: var(--radius);
    border: none;
    background: var(--surface-2);
    cursor: pointer;
    font-size: 12px;
    color: var(--text-3);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .14s;
}
.sc-modal-close:hover { background: var(--red-bg); color: var(--red); }
.sc-modal-body { padding: 20px 22px; }
.sc-modal-label {
    display: block;
    font-size: 11px;
    font-weight: 700;
    color: var(--text-2);
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 7px;
}
.sc-modal-input {
    width: 100%;
    padding: 10px 13px;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    background: var(--surface);
    color: var(--text);
    font-size: 14px;
    font-family: var(--font);
    transition: all .16s;
    box-sizing: border-box;
    outline: none;
    margin-bottom: 10px;
}
.sc-modal-input:focus { border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,.1); }
.sc-modal-row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
.sc-modal-select {
    width: 100%;
    padding: 10px 13px;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    background: var(--surface);
    color: var(--text);
    font-size: 13px;
    font-family: var(--font);
    outline: none;
    transition: border-color .16s;
    cursor: pointer;
}
.sc-modal-select:focus { border-color: #0d9488; }
.sc-modal-hint {
    font-size: 11.5px;
    color: var(--text-3);
    display: flex;
    align-items: flex-start;
    gap: 5px;
    line-height: 1.5;
}
.sc-modal-hint i { color: #0d9488; flex-shrink: 0; margin-top: 1px; }
.sc-modal-foot {
    padding: 12px 22px 18px;
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

/* btn teal sp&eacute;cifique secteurs */
.btn-teal {
    background: #0d9488;
    color: #fff;
    box-shadow: 0 2px 8px rgba(13,148,136,.2);
}
.btn-teal:hover { background: #0f766e; transform: translateY(-1px); color: #fff; }

/* Responsive */
@media(max-width:1100px) { .col-seo, .col-prix { display: none !important; } }
@media(max-width:900px) {
    .sc-banner { flex-direction: column; align-items: flex-start; }
    .sc-toolbar { flex-direction: column; align-items: flex-start; }
    .sc-table-wrap { overflow-x: auto; }
    .col-desc { display: none !important; }
}
</style>

<div class="module-wrap anim">

    <?php if (in_array($flash, ['saved','created'])): ?>
    <div class="sc-flash ok"><i class="fas fa-check-circle"></i>
        <?= $flash === 'created' ? 'Secteur cr&eacute;&eacute; &mdash; compl&eacute;tez le contenu.' : 'Modifications enregistr&eacute;es.' ?>
    </div>
    <?php elseif ($flash === 'deleted'): ?>
    <div class="sc-flash ok"><i class="fas fa-check-circle"></i> Secteur supprim&eacute;.</div>
    <?php elseif ($flash === 'error'): ?>
    <div class="sc-flash err"><i class="fas fa-exclamation-circle"></i> Une erreur est survenue.</div>
    <?php endif; ?>

    <!-- ── BANNER ───────────────────────────────────────────── -->
    <div class="sc-banner">
        <div class="sc-banner-left">
            <h2><i class="fas fa-map-location-dot"></i> Secteurs &amp; Quartiers</h2>
            <p>G&eacute;rez vos zones de couverture &mdash; quartiers, communes, secteurs immobiliers locaux</p>
        </div>
        <div class="sc-stats">
            <div class="sc-stat">
                <div class="num c-teal"><?= (int)($stats['total']??0) ?></div>
                <div class="lbl">Total</div>
            </div>
            <div class="sc-stat">
                <div class="num c-green"><?= (int)($stats['published']??0) ?></div>
                <div class="lbl">Publi&eacute;s</div>
            </div>
            <div class="sc-stat">
                <div class="num c-amber"><?= (int)($stats['draft']??0) ?></div>
                <div class="lbl">Brouillons</div>
            </div>
            <div class="sc-stat" title="Quartiers">
                <div class="num c-teal"><?= (int)($stats['quartiers']??0) ?></div>
                <div class="lbl">Quartiers</div>
            </div>
            <div class="sc-stat" title="Communes">
                <div class="num c-violet"><?= (int)($stats['communes']??0) ?></div>
                <div class="lbl">Communes</div>
            </div>
        </div>
    </div>

    <!-- ── TOOLBAR ──────────────────────────────────────────── -->
    <div class="sc-toolbar">
        <div class="sc-filters">
            <?php
            $filterDefs = [
                'all'       => ['fa-layer-group',  'Tous',        $stats['total']??0],
                'published' => ['fa-check-circle', 'Publi&eacute;s', $stats['published']??0],
                'draft'     => ['fa-pen',          'Brouillons',  $stats['draft']??0],
                'archived'  => ['fa-archive',      'Archiv&eacute;s', $stats['archived']??0],
            ];
            foreach ($filterDefs as $key => [$icon,$label,$count]):
                $active = $filterStatus === $key ? ' active' : '';
                $url    = '?page=secteurs' . ($key !== 'all' ? '&status='.$key : '');
                if ($searchQuery)        $url .= '&q='.urlencode($searchQuery);
                if ($filterType !== 'all') $url .= '&type_secteur='.$filterType;
                if ($filterVille !== '') $url .= '&ville='.urlencode($filterVille);
            ?>
            <a href="<?= $url ?>" class="sc-fbtn<?= $active ?>">
                <i class="fas <?= $icon ?>"></i>
                <?= $label ?>
                <span class="cnt"><?= (int)$count ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="sc-toolbar-r">
            <form class="sc-search" method="GET">
                <input type="hidden" name="page" value="secteurs">
                <?php if ($filterStatus !== 'all'): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
                <?php endif; ?>
                <?php if ($filterType !== 'all'): ?>
                <input type="hidden" name="type_secteur" value="<?= htmlspecialchars($filterType) ?>">
                <?php endif; ?>
                <?php if ($filterVille !== ''): ?>
                <input type="hidden" name="ville" value="<?= htmlspecialchars($filterVille) ?>">
                <?php endif; ?>
                <i class="fas fa-magnifying-glass"></i>
                <input type="text" name="q" placeholder="Rechercher un secteur&hellip;"
                       value="<?= htmlspecialchars($searchQuery) ?>">
            </form>
            <button type="button" class="btn btn-teal" onclick="SC.openModal()">
                <i class="fas fa-plus"></i> Nouveau secteur
            </button>
        </div>
    </div>

    <!-- ── SOUS-FILTRES ─────────────────────────────────────── -->
    <div class="sc-subfilters">
        <div class="sc-subfilter">
            <i class="fas fa-home"></i>
            <select onchange="SC.filterBy('type_secteur',this.value)">
                <option value="all"      <?= $filterType==='all'     ?'selected':''?>>Tous types</option>
                <option value="quartier" <?= $filterType==='quartier'?'selected':''?>>&#127968; Quartiers</option>
                <option value="commune"  <?= $filterType==='commune' ?'selected':''?>>&#127755; Communes</option>
            </select>
        </div>
        <?php if (!empty($villes)): ?>
        <div class="sc-subfilter">
            <i class="fas fa-city"></i>
            <select onchange="SC.filterBy('ville',this.value)">
                <option value="">Toutes les villes</option>
                <?php foreach ($villes as $v): ?>
                <option value="<?= htmlspecialchars($v) ?>" <?= $filterVille===$v?'selected':''?>><?= htmlspecialchars($v) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── BULK BAR ─────────────────────────────────────────── -->
    <div class="sc-bulk" id="scBulkBar">
        <input type="checkbox" id="scBulkAll" onchange="SC.toggleAll(this.checked)">
        <span id="scBulkCount">0</span> s&eacute;lectionn&eacute;(s)
        <select id="scBulkAction">
            <option value="">&mdash; Action group&eacute;e &mdash;</option>
            <option value="publish">Publier</option>
            <option value="draft">Mettre en brouillon</option>
            <option value="archive">Archiver</option>
            <option value="delete">Supprimer</option>
        </select>
        <button class="btn btn-s btn-sm" onclick="SC.bulkExecute()">
            <i class="fas fa-check"></i> Appliquer
        </button>
    </div>

    <!-- ── TABLE ────────────────────────────────────────────── -->
    <div class="sc-table-wrap">
        <?php if (empty($secteurs)): ?>
        <div class="sc-empty">
            <i class="fas fa-map-location-dot"></i>
            <h3>Aucun secteur trouv&eacute;</h3>
            <p>
                <?php if ($searchQuery): ?>
                    Aucun r&eacute;sultat pour &laquo;&nbsp;<?= htmlspecialchars($searchQuery) ?>&nbsp;&raquo;.
                    <a href="?page=secteurs">Effacer la recherche</a>
                <?php else: ?>
                    Cr&eacute;ez votre premier secteur pour commencer.
                <?php endif; ?>
            </p>
        </div>
        <?php else: ?>
        <table class="tbl">
            <thead>
                <tr>
                    <th style="width:34px">
                        <input type="checkbox" onchange="SC.toggleAll(this.checked)">
                    </th>
                    <th style="width:60px">&nbsp;</th>
                    <th>Secteur</th>
                    <th>Type</th>
                    <th>Ville</th>
                    <th>Statut</th>
                    <th class="col-prix">Prix m&sup2;</th>
                    <th class="col-seo">SEO</th>
                    <th>Modifi&eacute;</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($secteurs as $sc):
                $seo     = (int)($sc['seo_score']??0);
                $seoC    = $seo>=70?'good':($seo>=40?'ok':($seo>0?'bad':'none'));
                $typeS   = $sc['type_secteur'] ?? 'quartier';
                $heroImg = $sc['hero_image'] ?? '';
                $modDate = !empty($sc['updated_at'])
                    ? date('d/m/Y', strtotime($sc['updated_at']))
                    : (!empty($sc['created_at']) ? date('d/m/Y', strtotime($sc['created_at'])) : '&mdash;');
                $editUrl = "/admin/modules/content/secteurs/edit.php?id={$sc['id']}";
                $viewUrl = "/{$sc['slug']}";
                $prix    = $sc['prix_moyen'] ?? '';
            ?>
            <tr data-id="<?= (int)$sc['id'] ?>">
                <td>
                    <input type="checkbox" class="sc-cb" value="<?= (int)$sc['id'] ?>" onchange="SC.updateBulk()">
                </td>
                <td>
                    <div class="sc-thumb"
                         style="<?= $heroImg ? "background-image:url('".htmlspecialchars($heroImg)."')" : '' ?>">
                        <?php if (!$heroImg): ?><i class="fas fa-map-pin"></i><?php endif; ?>
                    </div>
                </td>
                <td>
                    <a href="<?= htmlspecialchars($editUrl) ?>" class="sc-nom-link">
                        <?= htmlspecialchars($sc['nom']) ?>
                    </a>
                    <span class="sc-slug">/<?= htmlspecialchars($sc['slug']) ?></span>
                </td>
                <td>
                    <span class="sc-type <?= $typeS ?>">
                        <?= $typeS === 'commune' ? '&#127755; Commune' : '&#127968; Quartier' ?>
                    </span>
                </td>
                <td style="font-size:12.5px;color:var(--text-2);font-weight:500">
                    <?= htmlspecialchars($sc['ville'] ?? '&mdash;') ?>
                </td>
                <td>
                    <span class="sc-status <?= $sc['status'] ?>">
                        <?= $sc['status']==='published' ? 'Publi&eacute;' : ($sc['status']==='draft' ? 'Brouillon' : 'Archiv&eacute;') ?>
                    </span>
                </td>
                <td class="col-prix">
                    <?php if ($prix): ?>
                    <span class="sc-prix"><i class="fas fa-euro-sign"></i><?= htmlspecialchars($prix) ?></span>
                    <?php else: ?>
                    <span style="color:var(--text-3);font-size:12px">&mdash;</span>
                    <?php endif; ?>
                </td>
                <td class="col-seo">
                    <span class="sc-score <?= $seoC ?>"><?= $seo > 0 ? $seo.'%' : '&mdash;' ?></span>
                </td>
                <td style="font-size:12px;color:var(--text-3);white-space:nowrap"><?= $modDate ?></td>
                <td>
                    <div class="sc-acts">
                        <!-- &Eacute;diter contenu -->
                        <a href="<?= htmlspecialchars($editUrl) ?>" class="sc-act" title="&Eacute;diter le contenu">
                            <i class="fas fa-pen"></i>
                        </a>
                        <!-- &Eacute;diter design -->
                        <a href="/admin/modules/builder/builder/editor.php?type=secteur&id=<?= (int)$sc['id'] ?>"
                           class="sc-act design" title="&Eacute;diteur design">
                            <i class="fas fa-paint-brush"></i>
                        </a>
                        <!-- Dupliquer -->
                        <button class="sc-act" onclick="SC.duplicate(<?= (int)$sc['id'] ?>)" title="Dupliquer">
                            <i class="fas fa-copy"></i>
                        </button>
                        <!-- Toggle statut -->
                        <button class="sc-act"
                                onclick="SC.toggleStatus(<?= (int)$sc['id'] ?>,'<?= $sc['status'] ?>')"
                                title="<?= $sc['status']==='published' ? 'D&eacute;publier' : 'Publier' ?>">
                            <i class="fas <?= $sc['status']==='published' ? 'fa-eye-slash' : 'fa-eye' ?>"></i>
                        </button>
                        <!-- Voir live -->
                        <?php if ($sc['status'] === 'published'): ?>
                        <a href="<?= htmlspecialchars($viewUrl) ?>" target="_blank" class="sc-act" title="Voir la page">
                            <i class="fas fa-arrow-up-right-from-square"></i>
                        </a>
                        <?php endif; ?>
                        <!-- Supprimer -->
                        <button class="sc-act del"
                                onclick="SC.deleteSecteur(<?= (int)$sc['id'] ?>,'<?= addslashes(htmlspecialchars($sc['nom'])) ?>')"
                                title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="sc-pagination">
            <span>Affichage <?= $offset+1 ?>&ndash;<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?></span>
            <div class="sc-pages">
                <?php for ($i=1; $i<=$totalPages; $i++):
                    $pUrl = '?page=secteurs&p='.$i;
                    if ($filterStatus !== 'all') $pUrl .= '&status='.$filterStatus;
                    if ($filterType   !== 'all') $pUrl .= '&type_secteur='.$filterType;
                    if ($filterVille  !== '')    $pUrl .= '&ville='.urlencode($filterVille);
                    if ($searchQuery)            $pUrl .= '&q='.urlencode($searchQuery);
                ?>
                <a href="<?= $pUrl ?>" class="sc-page-btn<?= $i===$curPage?' active':'' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

</div><!-- /.module-wrap -->

<!-- ── MODAL NOUVEAU SECTEUR ────────────────────────────────── -->
<div class="sc-modal-overlay" id="scModal" onclick="if(event.target===this)SC.closeModal()">
    <div class="sc-modal">
        <div class="sc-modal-head">
            <h3><i class="fas fa-plus-circle"></i> Nouveau secteur</h3>
            <button class="sc-modal-close" onclick="SC.closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="sc-modal-body">
            <label class="sc-modal-label">Nom du secteur / quartier</label>
            <input type="text" id="scNewNom" class="sc-modal-input"
                   placeholder="Ex&nbsp;: Bacalan, Les Chartrons, Talence Centre&hellip;"
                   autocomplete="off">

            <div class="sc-modal-row">
                <div>
                    <label class="sc-modal-label" style="margin-bottom:5px">Ville</label>
                    <input type="text" id="scNewVille" class="sc-modal-input" style="margin-bottom:0"
                           placeholder="Bordeaux, M&eacute;rignac&hellip;"
                           value="<?= htmlspecialchars($villes[0] ?? 'Bordeaux') ?>">
                </div>
                <div>
                    <label class="sc-modal-label" style="margin-bottom:5px">Type</label>
                    <select id="scNewType" class="sc-modal-select">
                        <option value="quartier">&#127968; Quartier</option>
                        <option value="commune">&#127755; Commune</option>
                    </select>
                </div>
            </div>

            <p class="sc-modal-hint">
                <i class="fas fa-info-circle"></i>
                Le secteur est cr&eacute;&eacute; en brouillon et vous &ecirc;tes redirig&eacute; vers l&rsquo;&eacute;diteur de contenu.
            </p>
        </div>
        <div class="sc-modal-foot">
            <button type="button" class="btn btn-s" onclick="SC.closeModal()">Annuler</button>
            <button type="button" class="btn btn-teal" onclick="SC.createSecteur()">
                <i class="fas fa-map-location-dot"></i> Cr&eacute;er le secteur
            </button>
        </div>
    </div>
</div>

<script>
const SC = {
    apiUrl: window.location.href.split('?')[0],
    csrf:   '<?= addslashes($csrf) ?>',

    // ── Modal ──
    openModal() {
        document.getElementById('scModal').classList.add('open');
        setTimeout(() => document.getElementById('scNewNom').focus(), 80);
    },
    closeModal() {
        document.getElementById('scModal').classList.remove('open');
    },

    // ── Cr&eacute;er secteur (via redirect GET) ──
    createSecteur() {
        const nom   = document.getElementById('scNewNom').value.trim();
        const ville = document.getElementById('scNewVille').value.trim() || 'Bordeaux';
        const type  = document.getElementById('scNewType').value;
        if (!nom) { document.getElementById('scNewNom').focus(); return; }
        // POST form vers action=create (redirect edit.php)
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/admin/modules/content/secteurs/edit.php?action=create&csrf_token=' + this.csrf;
        const fields = { nom, ville, type_secteur: type };
        for (const [k,v] of Object.entries(fields)) {
            const i = document.createElement('input');
            i.type = 'hidden'; i.name = k; i.value = v;
            form.appendChild(i);
        }
        document.body.appendChild(form);
        form.submit();
    },

    // ── Filtres ──
    filterBy(key, value) {
        const url = new URL(window.location.href);
        value === 'all' || value === '' ? url.searchParams.delete(key) : url.searchParams.set(key, value);
        url.searchParams.delete('p');
        window.location.href = url.toString();
    },

    // ── Checkboxes ──
    toggleAll(checked) {
        document.querySelectorAll('.sc-cb').forEach(cb => cb.checked = checked);
        this.updateBulk();
    },
    updateBulk() {
        const checked = document.querySelectorAll('.sc-cb:checked');
        const bar = document.getElementById('scBulkBar');
        const cnt = document.getElementById('scBulkCount');
        bar.classList.toggle('active', checked.length > 0);
        if (cnt) cnt.textContent = checked.length;
    },

    // ── Bulk ──
    async bulkExecute() {
        const action = document.getElementById('scBulkAction').value;
        if (!action) return;
        const ids = [...document.querySelectorAll('.sc-cb:checked')].map(cb => parseInt(cb.value));
        if (!ids.length) return;
        if (action === 'delete' && !confirm(`Supprimer ${ids.length} secteur(s) ?`)) return;
        for (const id of ids) {
            if (action === 'delete') {
                await this._post({ _action: 'delete', id });
            } else {
                const statusMap = { publish: 'published', draft: 'draft', archive: 'archived' };
                await this._post({ _action: 'toggle_status', id, status: statusMap[action] });
            }
        }
        location.reload();
    },

    // ── Delete ──
    async deleteSecteur(id, nom) {
        if (!confirm(`Supprimer &laquo; ${nom} &raquo; ?\nCette action est irr&eacute;versible.`)) return;
        const d = await this._post({ _action: 'delete', id });
        if (d.success) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) { row.style.cssText = 'opacity:0;transform:translateX(20px);transition:all .3s'; setTimeout(() => row.remove(), 300); }
        } else alert(d.error || 'Erreur');
    },

    // ── Toggle statut ──
    async toggleStatus(id, current) {
        const newStatus = current === 'published' ? 'draft' : 'published';
        const d = await this._post({ _action: 'toggle_status', id, status: newStatus });
        d.success ? location.reload() : alert(d.error || 'Erreur');
    },

    // ── Duplicate ──
    async duplicate(id) {
        if (!confirm('Dupliquer ce secteur ?')) return;
        const d = await this._post({ _action: 'duplicate', id });
        if (d.success) {
            window.location.href = `/admin/modules/content/secteurs/edit.php?id=${d.new_id}&msg=created`;
        } else alert(d.error || 'Erreur');
    },

    // ── Helper POST ──
    async _post(data) {
        try {
            const fd = new FormData();
            for (const [k,v] of Object.entries(data)) fd.append(k, v);
            const r = await fetch(this.apiUrl, { method: 'POST', body: fd });
            return await r.json();
        } catch(e) { return { success: false, error: 'Erreur r&eacute;seau' }; }
    }
};

// Raccourcis
document.addEventListener('keydown', e => { if (e.key === 'Escape') SC.closeModal(); });
document.getElementById('scNewNom')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') SC.createSecteur();
});
</script>