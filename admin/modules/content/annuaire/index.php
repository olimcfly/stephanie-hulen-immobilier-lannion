<?php
/**
 * MODULE ANNUAIRE LOCAL — Partenaires & Points d'intérêt
 * /admin/modules/content/annuaire/index.php
 * v1.0 — Design unifié IMMO LOCAL+
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}

// ─── Connexion DB ───
if (!isset($pdo) && !isset($db)) {
    $inits = [
        dirname(dirname(dirname(dirname(__DIR__)))) . '/includes/init.php',
        dirname(dirname(dirname(__DIR__)))           . '/includes/init.php',
        dirname(dirname(__DIR__))                    . '/includes/init.php',
    ];
    foreach ($inits as $f) { if (file_exists($f)) { require_once $f; break; } }
}
if (isset($db)  && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db))  $db  = $pdo;

// ─── CATÉGORIES ───
$categories = [
    'ecole'      => ['icon' => 'fa-school',         'label' => 'Écoles & Crèches',     'color' => '#3b82f6', 'bg' => '#eff6ff'],
    'sante'      => ['icon' => 'fa-heartbeat',      'label' => 'Santé & Médecins',      'color' => '#ef4444', 'bg' => '#fef2f2'],
    'transport'  => ['icon' => 'fa-bus',            'label' => 'Transports',            'color' => '#8b5cf6', 'bg' => '#f5f3ff'],
    'commerce'   => ['icon' => 'fa-shopping-bag',   'label' => 'Commerces & Marchés',   'color' => '#f59e0b', 'bg' => '#fffbeb'],
    'restaurant' => ['icon' => 'fa-utensils',       'label' => 'Restaurants & Cafés',   'color' => '#f97316', 'bg' => '#fff7ed'],
    'sport'      => ['icon' => 'fa-dumbbell',       'label' => 'Sport & Loisirs',       'color' => '#10b981', 'bg' => '#ecfdf5'],
    'culture'    => ['icon' => 'fa-landmark',       'label' => 'Culture & Patrimoine',  'color' => '#6366f1', 'bg' => '#eef2ff'],
    'nature'     => ['icon' => 'fa-tree',           'label' => 'Parcs & Nature',        'color' => '#22c55e', 'bg' => '#f0fdf4'],
    'services'   => ['icon' => 'fa-concierge-bell', 'label' => 'Services de proximité', 'color' => '#0ea5e9', 'bg' => '#f0f9ff'],
    'securite'   => ['icon' => 'fa-shield-alt',     'label' => 'Sécurité & Mairie',     'color' => '#64748b', 'bg' => '#f8fafc'],
    'immobilier' => ['icon' => 'fa-home',           'label' => 'Acteurs immobiliers',   'color' => '#ec4899', 'bg' => '#fdf2f8'],
    'autre'      => ['icon' => 'fa-map-pin',        'label' => 'Autres',                'color' => '#94a3b8', 'bg' => '#f8fafc'],
];

// ─── Vérifier table ───
$tableExists = false;
try {
    $pdo->query("SELECT 1 FROM annuaire LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {}

// ─── ACTIONS POST ───
$error = null;
if ($tableExists && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    $itemId     = (int)($_POST['id'] ?? 0);

    if ($postAction === 'delete' && $itemId > 0) {
        try {
            $pdo->prepare("DELETE FROM annuaire WHERE id = ?")->execute([$itemId]);
            header("Location: /admin/dashboard.php?page=annuaire&msg=deleted"); exit;
        } catch (PDOException $e) { $error = $e->getMessage(); }
    }
    if ($postAction === 'toggle_status' && $itemId > 0) {
        try {
            $pdo->prepare("UPDATE annuaire SET status = IF(status='published','draft','published') WHERE id = ?")->execute([$itemId]);
            header("Location: /admin/dashboard.php?page=annuaire&msg=updated"); exit;
        } catch (PDOException $e) { $error = $e->getMessage(); }
    }
    if ($postAction === 'toggle_featured' && $itemId > 0) {
        try {
            $pdo->prepare("UPDATE annuaire SET is_featured = IF(is_featured=1,0,1) WHERE id = ?")->execute([$itemId]);
            header("Location: /admin/dashboard.php?page=annuaire&msg=updated"); exit;
        } catch (PDOException $e) { $error = $e->getMessage(); }
    }
}

// ─── FILTRES ───
$filterStatus   = $_GET['status']    ?? 'all';
$filterCat      = $_GET['categorie'] ?? 'all';
$filterVille    = $_GET['ville']     ?? 'all';
$filterSecteur  = $_GET['secteur']   ?? 'all';
$filterAudience = $_GET['audience']  ?? 'all';
$searchQuery    = trim($_GET['q']    ?? '');
$currentPage    = max(1, (int)($_GET['p'] ?? 1));
$perPage        = 30;
$offset         = ($currentPage - 1) * $perPage;

// ─── WHERE ───
$where = []; $params = [];
if ($filterStatus   !== 'all') { $where[] = "a.status = ?";     $params[] = $filterStatus; }
if ($filterCat      !== 'all') { $where[] = "a.categorie = ?";  $params[] = $filterCat; }
if ($filterVille    !== 'all') { $where[] = "a.ville = ?";      $params[] = $filterVille; }
if ($filterSecteur  !== 'all') { $where[] = "a.secteur_id = ?"; $params[] = $filterSecteur; }
if ($filterAudience !== 'all') { $where[] = "(a.audience = ? OR a.audience = 'tous')"; $params[] = $filterAudience; }
if ($searchQuery !== '') {
    $where[] = "(a.nom LIKE ? OR a.adresse LIKE ? OR a.description LIKE ? OR a.ville LIKE ?)";
    $t = "%$searchQuery%";
    $params = array_merge($params, [$t, $t, $t, $t]);
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ─── DATA ───
$stats     = ['total'=>0,'published'=>0,'draft'=>0,'featured'=>0,'with_gmb'=>0,'acheteurs'=>0,'habitants'=>0];
$partners  = [];
$totalFiltered = 0;
$totalPages    = 1;
$villes        = [];
$secteurs      = [];
$catCounts     = [];

if ($tableExists) {
    try {
        $s = $pdo->query("SELECT
            COUNT(*) as total,
            SUM(status='published') as published,
            SUM(status='draft') as draft,
            SUM(is_featured=1) as featured,
            SUM(gmb_url IS NOT NULL AND gmb_url != '') as with_gmb,
            SUM(audience='acheteur') as acheteurs,
            SUM(audience='habitant') as habitants
            FROM annuaire")->fetch(PDO::FETCH_ASSOC);
        $stats = array_map('intval', $s);

        $villes = $pdo->query(
            "SELECT DISTINCT ville FROM annuaire WHERE ville IS NOT NULL AND ville != '' ORDER BY ville"
        )->fetchAll(PDO::FETCH_COLUMN);

        // Secteurs si table existe
        try {
            $secteurs = $pdo->query("SELECT id, nom FROM secteurs ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {}

        $catRows = $pdo->query("SELECT categorie, COUNT(*) as cnt FROM annuaire GROUP BY categorie")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($catRows as $r) $catCounts[$r['categorie']] = (int)$r['cnt'];

        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM annuaire a $whereSQL");
        $stmtCount->execute($params);
        $totalFiltered = (int)$stmtCount->fetchColumn();
        $totalPages    = max(1, ceil($totalFiltered / $perPage));

        // Jointure secteurs optionnelle
        $joinSQL = !empty($secteurs)
            ? "LEFT JOIN secteurs s ON s.id = a.secteur_id"
            : "";
        $secteurCol = !empty($secteurs) ? ", s.nom as secteur_nom" : ", NULL as secteur_nom";

        $stmtList = $pdo->prepare("
            SELECT a.* {$secteurCol}
            FROM annuaire a
            {$joinSQL}
            {$whereSQL}
            ORDER BY a.is_featured DESC, a.nom ASC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmtList->execute($params);
        $partners = $stmtList->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) { $error = $e->getMessage(); }
}

$flash = $_GET['msg'] ?? '';
$flashMessages = [
    'deleted' => ['type'=>'ok',  'text'=>'Entrée supprimée'],
    'updated' => ['type'=>'ok',  'text'=>'Mis à jour avec succès'],
    'created' => ['type'=>'ok',  'text'=>'Entrée ajoutée à l\'annuaire'],
    'error'   => ['type'=>'err', 'text'=>'Une erreur est survenue'],
];
?>

<style>
/* ════════════════════════════════════════════════════
   MODULE ANNUAIRE — design system IMMO LOCAL+
   ════════════════════════════════════════════════════ */

/* Banner */
.an-banner {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 22px 26px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
}
.an-banner::before {
    content: '';
    position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #10b981, #3b82f6, #f59e0b, #ef4444);
}
.an-banner-left h2 {
    font-size: 16px; font-weight: 800; color: var(--text);
    margin: 0 0 4px; display: flex; align-items: center; gap: 9px;
    letter-spacing: -.02em;
}
.an-banner-left h2 i { color: #10b981; font-size: 14px; }
.an-banner-left p { font-size: 12px; color: var(--text-3); margin: 0 0 8px; }
.an-seo-hint {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; background: rgba(16,185,129,.08);
    border: 1px solid rgba(16,185,129,.2); border-radius: 20px;
    font-size: 10px; font-weight: 600; color: #059669;
}
.an-stats { display: flex; gap: 8px; flex-wrap: wrap; }
.an-stat {
    text-align: center; padding: 10px 14px;
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: var(--radius); min-width: 68px;
    transition: box-shadow .15s;
}
.an-stat:hover { box-shadow: var(--shadow-sm); border-color: #cbd5e1; }
.an-stat .num {
    font-size: 20px; font-weight: 900; line-height: 1;
    color: var(--text); letter-spacing: -.03em;
}
.an-stat .num.c-blue   { color: var(--accent); }
.an-stat .num.c-green  { color: var(--green); }
.an-stat .num.c-amber  { color: var(--amber); }
.an-stat .num.c-teal   { color: #0d9488; }
.an-stat .num.c-violet { color: #7c3aed; }
.an-stat .lbl {
    font-size: 9px; color: var(--text-3); text-transform: uppercase;
    letter-spacing: .07em; font-weight: 700; margin-top: 3px;
}

/* Cat pills */
.an-cat-pills { display: flex; gap: 7px; flex-wrap: wrap; margin-bottom: 14px; }
.an-cat-pill {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 20px; font-size: 11px;
    font-weight: 600; cursor: pointer; border: 1px solid var(--border);
    background: var(--surface); color: var(--text-2); text-decoration: none;
    transition: all .14s;
}
.an-cat-pill:hover { border-color: #cbd5e1; color: var(--text); box-shadow: var(--shadow-sm); }
.an-cat-pill.active { color: #fff; border-color: transparent; }
.an-cat-pill .cnt {
    font-size: 9px; padding: 1px 5px; border-radius: 10px;
    background: rgba(255,255,255,.25); font-weight: 700;
}
.an-cat-pill:not(.active) .cnt { background: var(--surface-2); color: var(--text-3); }

/* Toolbar */
.an-toolbar {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px; flex-wrap: wrap; gap: 10px;
}
.an-filters {
    display: flex; gap: 2px; background: var(--surface);
    border: 1px solid var(--border); border-radius: var(--radius);
    padding: 3px; flex-wrap: wrap;
}
.an-fbtn {
    padding: 6px 14px; border: none; background: transparent;
    color: var(--text-2); font-size: 12px; font-weight: 600;
    border-radius: 6px; cursor: pointer; transition: all .14s;
    font-family: var(--font); display: flex; align-items: center;
    gap: 5px; text-decoration: none; white-space: nowrap;
}
.an-fbtn:hover { color: var(--text); background: var(--surface-2); }
.an-fbtn.active { background: #10b981; color: #fff; }
.an-fbtn .cnt {
    font-size: 10px; padding: 1px 6px; border-radius: 10px;
    background: rgba(0,0,0,.06); font-weight: 700;
}
.an-fbtn.active .cnt { background: rgba(255,255,255,.22); color: #fff; }

.an-audience {
    display: flex; gap: 2px; background: var(--surface);
    border: 1px solid var(--border); border-radius: var(--radius); padding: 3px;
}
.an-aud-btn {
    padding: 5px 12px; border: none; background: transparent;
    color: var(--text-2); font-size: 11px; font-weight: 600;
    border-radius: 5px; cursor: pointer; transition: all .14s;
    font-family: var(--font); display: flex; align-items: center;
    gap: 5px; text-decoration: none;
}
.an-aud-btn:hover { color: var(--text); background: var(--surface-2); }
.an-aud-btn.a-ach { background: #3b82f6; color: #fff; }
.an-aud-btn.a-hab { background: #8b5cf6; color: #fff; }
.an-aud-btn.a-all { background: #475569; color: #fff; }

/* Sub-filters */
.an-subfilters { display: flex; gap: 8px; margin-bottom: 12px; flex-wrap: wrap; align-items: center; }
.an-subfilter { display: flex; align-items: center; gap: 5px; font-size: 12px; color: var(--text-3); }
.an-subfilter i { font-size: 10px; }
.an-subfilter select {
    padding: 5px 10px; border: 1px solid var(--border); border-radius: var(--radius);
    background: var(--surface); color: var(--text); font-size: 12px;
    font-family: var(--font); cursor: pointer; outline: none; transition: border-color .14s;
}
.an-subfilter select:focus { border-color: #10b981; }

.an-toolbar-r { display: flex; align-items: center; gap: 8px; }
.an-search { position: relative; display: flex; align-items: center; }
.an-search i { position: absolute; left: 10px; color: var(--text-3); font-size: 11px; pointer-events: none; }
.an-search input {
    padding: 7px 12px 7px 30px; background: var(--surface);
    border: 1px solid var(--border); border-radius: var(--radius);
    color: var(--text); font-size: 12.5px; width: 200px;
    font-family: var(--font); transition: all .18s; outline: none;
}
.an-search input:focus {
    border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.1); width: 240px;
}
.an-search input::placeholder { color: var(--text-3); }

/* Bulk */
.an-bulk {
    display: none; align-items: center; gap: 12px; padding: 10px 14px;
    background: rgba(16,185,129,.06); border: 1px solid rgba(16,185,129,.15);
    border-radius: var(--radius); margin-bottom: 10px;
    font-size: 12px; color: #059669; font-weight: 600;
}
.an-bulk.active { display: flex; }
.an-bulk select {
    padding: 5px 10px; border: 1px solid var(--border); border-radius: var(--radius);
    background: var(--surface); color: var(--text); font-size: 12px; font-family: var(--font);
}

/* Table */
.an-table-wrap {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-sm);
}

/* Cat badge */
.an-cat-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 9px; border-radius: 20px; font-size: 10px;
    font-weight: 600; white-space: nowrap; border: 1px solid transparent;
}

/* Secteur / Ville */
.an-secteur {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; background: #f0f9ff; color: #0369a1;
    border-radius: 20px; font-size: 10px; font-weight: 600;
    border: 1px solid #bae6fd; max-width: 130px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.an-ville {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; background: var(--surface-2);
    border-radius: 20px; font-size: 10px; font-weight: 600;
    color: var(--text-2); border: 1px solid var(--border);
}

/* Audience */
.an-audience-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 20px; font-size: 9px;
    font-weight: 700; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap;
}
.an-audience-badge.acheteur { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.an-audience-badge.habitant { background: #f5f3ff; color: #7c3aed; border: 1px solid #ddd6fe; }
.an-audience-badge.tous     { background: var(--surface-2); color: var(--text-2); border: 1px solid var(--border); }

/* GMB */
.an-gmb {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 700; text-transform: uppercase;
}
.an-gmb.yes { background: #ecfdf5; color: #059669; }
.an-gmb.no  { background: var(--surface-2); color: var(--text-3); }

/* Statut */
.an-status {
    padding: 3px 9px; border-radius: 12px; font-size: 9px;
    font-weight: 700; text-transform: uppercase; letter-spacing: .04em; display: inline-block;
}
.an-status.published { background: var(--green-bg); color: var(--green); }
.an-status.draft     { background: var(--amber-bg); color: var(--amber); }

/* Note */
.an-note { display: flex; align-items: center; gap: 4px; }
.an-stars { color: #f59e0b; font-size: 10px; }
.an-note-val { font-size: 11px; font-weight: 700; color: var(--text-2); font-family: var(--mono); }

/* Featured star */
.an-star {
    color: var(--border); font-size: 13px;
    background: none; border: none; cursor: pointer;
    transition: transform .15s; padding: 0;
}
.an-star:hover { transform: scale(1.3); }
.an-star.on { color: #f59e0b; }

/* Partner name */
.an-name { font-weight: 600; color: var(--text); display: flex; align-items: center; gap: 7px; }
.an-name a { color: var(--text); text-decoration: none; transition: color .14s; }
.an-name a:hover { color: #10b981; }
.an-addr { font-size: 11px; color: var(--text-3); margin-top: 2px; display: flex; align-items: center; gap: 4px; }
.an-addr i { font-size: 9px; flex-shrink: 0; }
.an-desc {
    font-size: 11px; color: var(--text-2); margin-top: 2px;
    max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

/* Actions */
.an-acts { display: flex; gap: 2px; justify-content: flex-end; }
.an-act {
    width: 30px; height: 30px; border-radius: var(--radius);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-3); background: transparent; border: 1px solid transparent;
    cursor: pointer; transition: all .12s; text-decoration: none; font-size: 12px;
}
.an-act:hover     { color: #10b981; border-color: var(--border); background: rgba(16,185,129,.07); }
.an-act.del:hover { color: var(--red); border-color: rgba(239,68,68,.2); background: var(--red-bg); }

/* Pagination */
.an-pagination {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; border-top: 1px solid var(--border);
    font-size: 12px; color: var(--text-3);
}
.an-pages { display: flex; gap: 3px; }
.an-page-btn {
    padding: 5px 11px; border: 1px solid var(--border);
    border-radius: var(--radius); color: var(--text-2); text-decoration: none;
    font-size: 12px; font-weight: 600; transition: all .14s;
}
.an-page-btn:hover  { border-color: #10b981; color: #10b981; background: rgba(16,185,129,.06); }
.an-page-btn.active { background: #10b981; color: #fff; border-color: #10b981; }

/* Flash */
.an-flash {
    display: flex; align-items: center; gap: 8px;
    padding: 11px 16px; border-radius: var(--radius);
    font-size: 13px; font-weight: 600; margin-bottom: 14px;
    animation: anFlash .3s ease;
}
.an-flash.ok  { background: var(--green-bg); color: var(--green); border: 1px solid rgba(16,185,129,.12); }
.an-flash.err { background: var(--red-bg);   color: var(--red);   border: 1px solid rgba(239,68,68,.12); }
@keyframes anFlash { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:none; } }

/* Empty / Install */
.an-empty { text-align: center; padding: 60px 20px; color: var(--text-3); }
.an-empty i { font-size: 32px; opacity: .2; margin-bottom: 12px; display: block; }
.an-empty h3 { font-size: 14px; font-weight: 700; color: var(--text-2); margin-bottom: 6px; }
.an-empty a { color: #10b981; }

.an-install {
    text-align: center; padding: 60px 30px;
    background: var(--surface); border-radius: var(--radius-lg);
    border: 2px dashed var(--border);
}
.an-install i { font-size: 40px; color: #10b981; opacity: .3; margin-bottom: 16px; display: block; }
.an-install h3 { font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.an-install p  { font-size: 13px; color: var(--text-2); margin-bottom: 20px; }

@media(max-width:1100px) { .col-desc, .col-gmb { display: none !important; } }
@media(max-width:900px)  { .col-secteur, .col-note { display: none !important; } }
@media(max-width:768px)  {
    .an-banner { flex-direction: column; align-items: flex-start; }
    .an-toolbar { flex-direction: column; align-items: flex-start; }
}
</style>

<div class="module-wrap anim">

<!-- ── FLASH ─────────────────────────────────────────────── -->
<?php if ($flash && isset($flashMessages[$flash])): ?>
<div class="an-flash <?= $flashMessages[$flash]['type'] ?>">
    <i class="fas fa-<?= $flashMessages[$flash]['type']==='ok' ? 'check-circle' : 'exclamation-circle' ?>"></i>
    <?= $flashMessages[$flash]['text'] ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="an-flash err"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- ── BANNER ─────────────────────────────────────────────── -->
<div class="an-banner">
    <div class="an-banner-left">
        <h2><i class="fas fa-book-open"></i> Annuaire Local</h2>
        <p>Partenaires &amp; points d&rsquo;int&eacute;r&ecirc;t par secteur — maillage SEO local</p>
        <span class="an-seo-hint">
            <i class="fas fa-search"></i>
            Chaque partenaire = signal E-E-A-T de proximit&eacute; + maillage local
        </span>
    </div>
    <div class="an-stats">
        <div class="an-stat"><div class="num c-blue"><?= $stats['total'] ?></div><div class="lbl">Total</div></div>
        <div class="an-stat"><div class="num c-green"><?= $stats['published'] ?></div><div class="lbl">Publi&eacute;s</div></div>
        <div class="an-stat"><div class="num c-amber"><?= $stats['draft'] ?></div><div class="lbl">Brouillons</div></div>
        <div class="an-stat" title="Mis en avant"><div class="num c-amber"><?= $stats['featured'] ?></div><div class="lbl">&#9733; Top</div></div>
        <div class="an-stat" title="Avec fiche Google My Business"><div class="num c-teal"><?= $stats['with_gmb'] ?></div><div class="lbl">GMB</div></div>
        <div class="an-stat"><div class="num c-blue"><?= $stats['acheteurs'] ?></div><div class="lbl">Acheteurs</div></div>
        <div class="an-stat"><div class="num c-violet"><?= $stats['habitants'] ?></div><div class="lbl">R&eacute;sidents</div></div>
    </div>
</div>

<?php if (!$tableExists): ?>
<!-- ── TABLE MANQUANTE ─────────────────────────────────────── -->
<div class="an-install">
    <i class="fas fa-database"></i>
    <h3>Table <code>annuaire</code> &agrave; cr&eacute;er</h3>
    <p>Ex&eacute;cutez le script <code>install.sql</code> fourni dans le dossier du module pour activer l&rsquo;annuaire.</p>
    <a href="/admin/modules/content/annuaire/install.php" class="btn btn-p">
        <i class="fas fa-magic"></i> Installer automatiquement
    </a>
</div>

<?php else: ?>

<!-- ── CATÉGORIES PILLS ──────────────────────────────────── -->
<div class="an-cat-pills">
    <?php
    $baseUrl = '?page=annuaire';
    if ($filterStatus   !== 'all') $baseUrl .= '&status='.$filterStatus;
    if ($filterVille    !== 'all') $baseUrl .= '&ville='.urlencode($filterVille);
    if ($filterAudience !== 'all') $baseUrl .= '&audience='.$filterAudience;
    ?>
    <a href="<?= $baseUrl ?>" class="an-cat-pill <?= $filterCat==='all'?'active':'' ?>"
       style="<?= $filterCat==='all'?'background:#475569;':'' ?>">
        <i class="fas fa-layer-group"></i> Toutes
        <span class="cnt"><?= $stats['total'] ?></span>
    </a>
    <?php foreach ($categories as $key => $cat):
        $cnt = $catCounts[$key] ?? 0;
        if ($cnt === 0 && $filterCat !== $key) continue;
        $isActive = $filterCat === $key;
        $url = $baseUrl . '&categorie=' . $key;
    ?>
    <a href="<?= $url ?>" class="an-cat-pill <?= $isActive?'active':'' ?>"
       style="<?= $isActive?"background:{$cat['color']};":'' ?>">
        <i class="fas <?= $cat['icon'] ?>"></i> <?= $cat['label'] ?>
        <span class="cnt"><?= $cnt ?></span>
    </a>
    <?php endforeach; ?>
</div>

<!-- ── TOOLBAR ────────────────────────────────────────────── -->
<div class="an-toolbar">
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">

        <!-- Statut -->
        <div class="an-filters">
            <?php
            $statusTabs = [
                'all'       => ['fa-layer-group',  'Tous',       $stats['total']],
                'published' => ['fa-check-circle', 'Publi&eacute;s',    $stats['published']],
                'draft'     => ['fa-pencil-alt',   'Brouillons', $stats['draft']],
            ];
            foreach ($statusTabs as $key => [$icon, $label, $count]):
                $active = $filterStatus === $key ? ' active' : '';
                $url = '?page=annuaire' . ($key !== 'all' ? '&status='.$key : '');
                if ($filterCat     !== 'all') $url .= '&categorie='.$filterCat;
                if ($filterVille   !== 'all') $url .= '&ville='.urlencode($filterVille);
                if ($filterAudience !== 'all') $url .= '&audience='.$filterAudience;
                if ($searchQuery)              $url .= '&q='.urlencode($searchQuery);
            ?>
            <a href="<?= $url ?>" class="an-fbtn<?= $active ?>">
                <i class="fas <?= $icon ?>"></i> <?= $label ?>
                <span class="cnt"><?= (int)$count ?></span>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Audience -->
        <div class="an-audience">
            <?php
            $audTabs = [
                'all'      => ['fa-users',          'Tous',      'a-all'],
                'acheteur' => ['fa-home',           'Acheteurs', 'a-ach'],
                'habitant' => ['fa-map-marker-alt', 'R&eacute;sidents', 'a-hab'],
            ];
            foreach ($audTabs as $key => [$icon, $label, $cls]):
                $isAud = $filterAudience === $key;
                $audUrl = '?page=annuaire';
                if ($key !== 'all') $audUrl .= '&audience='.$key;
                if ($filterStatus !== 'all') $audUrl .= '&status='.$filterStatus;
                if ($filterCat    !== 'all') $audUrl .= '&categorie='.$filterCat;
                if ($filterVille  !== 'all') $audUrl .= '&ville='.urlencode($filterVille);
            ?>
            <a href="<?= $audUrl ?>" class="an-aud-btn <?= $isAud ? $cls : '' ?>">
                <i class="fas <?= $icon ?>"></i> <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="an-toolbar-r">
        <form class="an-search" method="GET">
            <input type="hidden" name="page" value="annuaire">
            <?php if ($filterStatus !== 'all'): ?>
            <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
            <?php endif; ?>
            <?php if ($filterCat !== 'all'): ?>
            <input type="hidden" name="categorie" value="<?= htmlspecialchars($filterCat) ?>">
            <?php endif; ?>
            <i class="fas fa-magnifying-glass"></i>
            <input type="text" name="q" placeholder="Nom, adresse, ville&hellip;"
                   value="<?= htmlspecialchars($searchQuery) ?>">
        </form>
        <a href="/admin/modules/content/annuaire/edit.php?action=create" class="btn btn-p">
            <i class="fas fa-plus"></i> Ajouter
        </a>
    </div>
</div>

<!-- ── SOUS-FILTRES ───────────────────────────────────────── -->
<div class="an-subfilters">
    <?php if (!empty($villes)): ?>
    <div class="an-subfilter">
        <i class="fas fa-city"></i>
        <select onchange="AN.filterBy('ville', this.value)">
            <option value="all" <?= $filterVille==='all'?'selected':'' ?>>Toutes les villes</option>
            <?php foreach ($villes as $v): ?>
            <option value="<?= htmlspecialchars($v) ?>" <?= $filterVille===$v?'selected':'' ?>>
                <?= htmlspecialchars($v) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <?php if (!empty($secteurs)): ?>
    <div class="an-subfilter">
        <i class="fas fa-map-pin"></i>
        <select onchange="AN.filterBy('secteur', this.value)">
            <option value="all" <?= $filterSecteur==='all'?'selected':'' ?>>Tous les secteurs</option>
            <?php foreach ($secteurs as $sec): ?>
            <option value="<?= (int)$sec['id'] ?>" <?= $filterSecteur==(string)$sec['id']?'selected':'' ?>>
                <?= htmlspecialchars($sec['nom']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <?php if ($filterCat !== 'all' || $filterVille !== 'all' || $filterSecteur !== 'all' || $filterAudience !== 'all' || $searchQuery): ?>
    <a href="?page=annuaire"
       style="display:inline-flex;align-items:center;gap:5px;padding:5px 11px;
              background:var(--red-bg);border:1px solid rgba(239,68,68,.2);
              border-radius:var(--radius);font-size:11px;font-weight:600;
              color:var(--red);text-decoration:none;">
        <i class="fas fa-times"></i> R&eacute;initialiser
    </a>
    <?php endif; ?>
</div>

<!-- ── BULK ───────────────────────────────────────────────── -->
<div class="an-bulk" id="anBulkBar">
    <input type="checkbox" id="anSelectAll" onchange="AN.toggleAll(this.checked)">
    <span id="anBulkCount">0</span> s&eacute;lectionn&eacute;(s)
    <select id="anBulkAction">
        <option value="">&mdash; Action group&eacute;e &mdash;</option>
        <option value="publish">Publier</option>
        <option value="draft">Brouillon</option>
        <option value="feature">Mettre en avant</option>
        <option value="delete">Supprimer</option>
    </select>
    <button class="btn btn-s btn-sm" onclick="AN.bulkExecute()">
        <i class="fas fa-check"></i> Appliquer
    </button>
</div>

<!-- ── TABLE ──────────────────────────────────────────────── -->
<div class="an-table-wrap">
    <?php if (empty($partners)): ?>
    <div class="an-empty">
        <i class="fas fa-book-open"></i>
        <h3><?= $searchQuery || $filterCat !== 'all' || $filterStatus !== 'all' ? 'Aucun r&eacute;sultat' : 'Annuaire vide' ?></h3>
        <p>
            <?php if ($searchQuery): ?>
                Aucun r&eacute;sultat pour &laquo;&nbsp;<?= htmlspecialchars($searchQuery) ?>&nbsp;&raquo;.
                <a href="?page=annuaire">Effacer</a>
            <?php else: ?>
                Ajoutez vos premiers partenaires locaux pour enrichir l&rsquo;annuaire.
            <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
    <table class="tbl">
        <thead>
            <tr>
                <th style="width:32px">
                    <input type="checkbox" onchange="AN.toggleAll(this.checked)">
                </th>
                <th>Partenaire</th>
                <th>Cat&eacute;gorie</th>
                <th class="col-secteur">Secteur / Ville</th>
                <th class="col-note">Note</th>
                <th class="col-gmb">GMB</th>
                <th>Audience</th>
                <th>Statut</th>
                <th style="text-align:right">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($partners as $p):
            $cat      = $p['categorie'] ?? 'autre';
            $catInfo  = $categories[$cat] ?? $categories['autre'];
            $status   = $p['status']   ?? 'draft';
            $audience = $p['audience'] ?? 'tous';
            $note     = (float)($p['note'] ?? 0);
            $hasGmb   = !empty($p['gmb_url']);
            $isFeat   = !empty($p['is_featured']);
            $editUrl  = "/admin/modules/content/annuaire/edit.php?id={$p['id']}";

            // Étoiles
            $stars = '';
            if ($note > 0) {
                $full  = floor($note);
                $half  = ($note - $full) >= 0.5 ? 1 : 0;
                $stars = str_repeat('★', $full) . ($half ? '½' : '') . str_repeat('☆', 5 - $full - $half);
            }
            $audLabels = [
                'acheteur' => ['Acheteur',  'fa-home'],
                'habitant' => ['R&eacute;sident',  'fa-map-marker-alt'],
                'tous'     => ['Universel', 'fa-users'],
            ];
            [$audLabel, $audIcon] = $audLabels[$audience] ?? $audLabels['tous'];
        ?>
        <tr data-id="<?= (int)$p['id'] ?>">
            <td>
                <input type="checkbox" class="an-cb" value="<?= (int)$p['id'] ?>" onchange="AN.updateBulk()">
            </td>
            <td>
                <div class="an-name">
                    <button class="an-star <?= $isFeat?'on':'' ?>"
                            onclick="AN.toggleFeatured(<?= (int)$p['id'] ?>)"
                            title="<?= $isFeat?'Retirer du top':'Mettre en avant' ?>">
                        <i class="fas fa-star"></i>
                    </button>
                    <a href="<?= htmlspecialchars($editUrl) ?>"><?= htmlspecialchars($p['nom'] ?? 'Sans nom') ?></a>
                </div>
                <?php if (!empty($p['adresse'])): ?>
                <div class="an-addr">
                    <i class="fas fa-map-pin"></i>
                    <?= htmlspecialchars($p['adresse']) ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($p['description'])): ?>
                <div class="an-desc col-desc"><?= htmlspecialchars($p['description']) ?></div>
                <?php endif; ?>
            </td>
            <td>
                <span class="an-cat-badge"
                      style="background:<?= $catInfo['bg'] ?>;color:<?= $catInfo['color'] ?>;border-color:<?= $catInfo['color'] ?>33;">
                    <i class="fas <?= $catInfo['icon'] ?>"></i>
                    <?= $catInfo['label'] ?>
                </span>
            </td>
            <td class="col-secteur">
                <div style="display:flex;flex-direction:column;gap:3px;">
                    <?php if (!empty($p['secteur_nom'])): ?>
                    <span class="an-secteur"><i class="fas fa-map-pin"></i><?= htmlspecialchars($p['secteur_nom']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($p['ville'])): ?>
                    <span class="an-ville"><?= htmlspecialchars($p['ville']) ?><?= !empty($p['code_postal'])?' '.$p['code_postal']:'' ?></span>
                    <?php endif; ?>
                </div>
            </td>
            <td class="col-note">
                <?php if ($note > 0): ?>
                <div class="an-note">
                    <span class="an-stars"><?= $stars ?></span>
                    <span class="an-note-val"><?= number_format($note, 1) ?></span>
                </div>
                <?php else: ?>
                <span style="color:var(--text-3);font-size:12px">&mdash;</span>
                <?php endif; ?>
            </td>
            <td class="col-gmb">
                <span class="an-gmb <?= $hasGmb?'yes':'no' ?>">
                    <i class="fab fa-google"></i>
                    <?= $hasGmb?'Li&eacute;':'Non' ?>
                </span>
            </td>
            <td>
                <span class="an-audience-badge <?= $audience ?>">
                    <i class="fas <?= $audIcon ?>"></i>
                    <?= $audLabel ?>
                </span>
            </td>
            <td>
                <span class="an-status <?= $status ?>">
                    <?= $status==='published'?'Publi&eacute;':'Brouillon' ?>
                </span>
            </td>
            <td>
                <div class="an-acts">
                    <?php if (!empty($p['site_web'])): ?>
                    <a href="<?= htmlspecialchars($p['site_web']) ?>" target="_blank" class="an-act" title="Voir le site">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                    <?php endif; ?>
                    <?php if ($hasGmb): ?>
                    <a href="<?= htmlspecialchars($p['gmb_url']) ?>" target="_blank" class="an-act" title="Fiche Google">
                        <i class="fab fa-google"></i>
                    </a>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars($editUrl) ?>" class="an-act" title="Modifier">
                        <i class="fas fa-pen"></i>
                    </a>
                    <button class="an-act"
                            onclick="AN.toggleStatus(<?= (int)$p['id'] ?>, '<?= $status ?>')"
                            title="<?= $status==='published'?'D&eacute;publier':'Publier' ?>">
                        <i class="fas <?= $status==='published'?'fa-eye-slash':'fa-eye' ?>"></i>
                    </button>
                    <button class="an-act del"
                            onclick="AN.deleteEntry(<?= (int)$p['id'] ?>, '<?= addslashes(htmlspecialchars($p['nom']??'')) ?>')"
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
    <div class="an-pagination">
        <span>Affichage <?= $offset+1 ?>&ndash;<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?></span>
        <div class="an-pages">
            <?php for ($i=1; $i<=$totalPages; $i++):
                $pUrl = '?page=annuaire&p='.$i;
                if ($filterStatus   !== 'all') $pUrl .= '&status='.$filterStatus;
                if ($filterCat      !== 'all') $pUrl .= '&categorie='.$filterCat;
                if ($filterVille    !== 'all') $pUrl .= '&ville='.urlencode($filterVille);
                if ($filterAudience !== 'all') $pUrl .= '&audience='.$filterAudience;
                if ($searchQuery)              $pUrl .= '&q='.urlencode($searchQuery);
            ?>
            <a href="<?= $pUrl ?>" class="an-page-btn<?= $i===$currentPage?' active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php endif; // tableExists ?>
</div><!-- /.module-wrap -->

<script>
const AN = {
    apiUrl: '/admin/api/content/annuaire.php',

    filterBy(key, value) {
        const url = new URL(window.location.href);
        value === 'all' ? url.searchParams.delete(key) : url.searchParams.set(key, value);
        url.searchParams.delete('p');
        window.location.href = url.toString();
    },

    toggleAll(checked) {
        document.querySelectorAll('.an-cb').forEach(cb => cb.checked = checked);
        this.updateBulk();
    },

    updateBulk() {
        const checked = document.querySelectorAll('.an-cb:checked');
        document.getElementById('anBulkCount').textContent = checked.length;
        document.getElementById('anBulkBar').classList.toggle('active', checked.length > 0);
    },

    async bulkExecute() {
        const action = document.getElementById('anBulkAction').value;
        if (!action) return;
        const ids = [...document.querySelectorAll('.an-cb:checked')].map(cb => parseInt(cb.value));
        if (!ids.length) return;
        if (action === 'delete' && !confirm(`Supprimer ${ids.length} entrée(s) ?`)) return;
        const fd = new FormData();
        const actionMap = { publish: 'published', draft: 'draft' };
        if (action === 'delete')   { fd.append('action', 'bulk_delete'); }
        else if (action === 'feature') { fd.append('action', 'bulk_feature'); }
        else { fd.append('action', 'bulk_status'); fd.append('status', actionMap[action]); }
        fd.append('ids', JSON.stringify(ids));
        const r = await fetch(this.apiUrl, { method: 'POST', body: fd });
        const d = await r.json();
        d.success ? location.reload() : alert(d.error || 'Erreur');
    },

    async deleteEntry(id, nom) {
        if (!confirm(`Supprimer « ${nom} » de l'annuaire ?\nCette action est irréversible.`)) return;
        const fd = new FormData();
        fd.append('action', 'delete');
        fd.append('id', id);
        const r = await fetch(this.apiUrl, { method: 'POST', body: fd });
        const d = await r.json();
        if (d.success) {
            const row = document.querySelector(`tr[data-id="${id}"]`);
            if (row) { row.style.cssText = 'opacity:0;transform:translateX(20px);transition:all .3s'; setTimeout(() => row.remove(), 300); }
        } else { alert(d.error || 'Erreur'); }
    },

    async toggleStatus(id, currentStatus) {
        const fd = new FormData();
        fd.append('action', 'toggle_status');
        fd.append('id', id);
        fd.append('status', currentStatus === 'published' ? 'draft' : 'published');
        const r = await fetch(this.apiUrl, { method: 'POST', body: fd });
        const d = await r.json();
        d.success ? location.reload() : alert(d.error || 'Erreur');
    },

    async toggleFeatured(id) {
        const fd = new FormData();
        fd.append('action', 'toggle_featured');
        fd.append('id', id);
        const r = await fetch(this.apiUrl, { method: 'POST', body: fd });
        const d = await r.json();
        d.success ? location.reload() : alert(d.error || 'Erreur');
    }
};

// Auto-dismiss flash après 4s
document.querySelectorAll('.an-flash').forEach(el => {
    setTimeout(() => { el.style.cssText = 'opacity:0;transition:opacity .3s'; setTimeout(() => el.remove(), 300); }, 4000);
});
</script>