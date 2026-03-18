<?php
/**
 * /admin/modules/content/pages/index.php  v7.0
 * ============================================================
 * Gestion des pages — design ARM v2.3
 * ✅ API consolidée (api.php)
 * ✅ Wizard IA "Créer Guide"
 * ✅ Filtres par statut + template
 * ✅ Stats banner premium
 * ✅ Vues liste / grille
 * ✅ FIX v6.1: routing action=edit/create via dashboard router
 * ✅ v7.0: Templates dynamiques depuis design_templates BDD
 * ============================================================
 */
if (!isset($pdo)) {
    require_once dirname(__DIR__, 4).'/includes/init.php';
}

// ─────────────────────────────────────────────────────────
// ROUTING: action=edit → charger edit.php
// ─────────────────────────────────────────────────────────
$action = $_GET['action'] ?? '';

if ($action === 'edit' && isset($_GET['id'])) {
    if (file_exists(__DIR__ . '/edit.php')) {
        require_once __DIR__ . '/edit.php';
        return;
    }
    echo '<div style="background:#fee2e2;color:#991b1b;padding:15px;border-radius:8px;margin:20px;">
        Fichier d\'édition non trouvé (edit.php manquant)
    </div>';
    return;
}

if ($action === 'create') {
    if (file_exists(__DIR__ . '/create.php')) {
        require_once __DIR__ . '/create.php';
        return;
    }
}

if ($action === 'guide-wizard') {
    if (file_exists(__DIR__ . '/guide-wizard.php')) {
        require_once __DIR__ . '/guide-wizard.php';
        return;
    }
}

if ($action === 'diagnostic') {
    if (file_exists(__DIR__ . '/diagnostic-api.php')) {
        require_once __DIR__ . '/diagnostic-api.php';
        return;
    }
}

// ─────────────────────────────────────────────────────────
// VARIABLES & FILTRES
// ─────────────────────────────────────────────────────────
$filterStatus = $_GET['status'] ?? 'all';
$filterTemplate = $_GET['template'] ?? 'all';
$searchQuery = trim($_GET['q'] ?? '');
$currentPage = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$offset = ($currentPage - 1) * $perPage;

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

// ─────────────────────────────────────────────────────────
// CHARGER LES TEMPLATES PAGE DEPUIS BDD
// ─────────────────────────────────────────────────────────
$pageTemplates = [];
try {
    $stmtTpl = $pdo->query("SELECT slug, name, icon, description FROM design_templates WHERE type='page' ORDER BY sort_order ASC, name ASC");
    $pageTemplates = $stmtTpl->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    // Fallback si la table n'a pas encore la colonne slug/type='page'
    error_log('[pages/index] Templates fallback: ' . $e->getMessage());
}

// Fallback hardcodé si aucun template en BDD (migration pas encore faite)
if (empty($pageTemplates)) {
    $pageTemplates = [
        ['slug' => 'standard',    'name' => 'Standard',    'icon' => 'fa-file-lines',    'description' => 'Page standard'],
        ['slug' => 't1-accueil',  'name' => 'Accueil',     'icon' => 'fa-home',          'description' => 'Page d\'accueil'],
        ['slug' => 't2-edito',    'name' => 'Édito',       'icon' => 'fa-pen-nib',       'description' => 'Page éditoriale'],
        ['slug' => 't3-secteur',  'name' => 'Secteur',     'icon' => 'fa-map-pin',       'description' => 'Landing quartier'],
        ['slug' => 't6-guide',    'name' => 'Guide',       'icon' => 'fa-book-open',     'description' => 'Guide complet'],
        ['slug' => 't12-legal',   'name' => 'Légal',       'icon' => 'fa-gavel',         'description' => 'Mentions légales'],
        ['slug' => 't13-merci',   'name' => 'Merci',       'icon' => 'fa-heart',         'description' => 'Page remerciement'],
        ['slug' => 't14-apropos', 'name' => 'À propos',    'icon' => 'fa-user',          'description' => 'Présentation'],
    ];
}

// ─────────────────────────────────────────────────────────
// BUILD QUERY
// ─────────────────────────────────────────────────────────
$where = [];
$params = [];

if ($filterStatus !== 'all') {
    $where[] = "status = ?";
    $params[] = $filterStatus;
}

if ($filterTemplate !== 'all') {
    $where[] = "(template = ? OR layout = ?)";
    $params[] = $filterTemplate;
    $params[] = $filterTemplate;
}

if ($searchQuery !== '') {
    $where[] = "(title LIKE ? OR slug LIKE ?)";
    $params[] = "%{$searchQuery}%";
    $params[] = "%{$searchQuery}%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ─────────────────────────────────────────────────────────
// STATS GLOBALES
// ─────────────────────────────────────────────────────────
$stats = ['total' => 0, 'draft' => 0, 'published' => 0];
try {
    $s = $pdo->query("SELECT status, COUNT(*) as cnt FROM pages GROUP BY status");
    while ($row = $s->fetch()) {
        $status = $row['status'] ?? 'draft';
        $stats[$status] = (int)$row['cnt'];
        $stats['total'] += (int)$row['cnt'];
    }
} catch (Throwable $e) {}

// ─────────────────────────────────────────────────────────
// RÉCUPÉRER LES PAGES
// ─────────────────────────────────────────────────────────
$totalFiltered = 0;
$pages = [];
$totalPages = 1;

try {
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM pages {$whereSQL}");
    $stmtCount->execute($params);
    $totalFiltered = (int)$stmtCount->fetchColumn();
    $totalPages = max(1, ceil($totalFiltered / $perPage));

    $stmt = $pdo->prepare("SELECT * FROM pages {$whereSQL} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
    $stmt->execute($params);
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    error_log('[pages/index] '.$e->getMessage());
    $pages = [];
}

// ─────────────────────────────────────────────────────────
// TEMPLATE FILTER DROPDOWN DATA
// ─────────────────────────────────────────────────────────
$tplFilterOptions = [];
try {
    $stmtTplFilter = $pdo->query("SELECT template, COUNT(*) as cnt FROM pages WHERE template IS NOT NULL AND template != '' GROUP BY template ORDER BY cnt DESC");
    $tplFilterOptions = $stmtTplFilter->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {}

?>

<style>
.pgs-wrap { font-family: 'Inter', -apple-system, sans-serif; }
.pgs-banner { background: #fff; border-radius: 16px; padding: 26px 30px; margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #e5e7eb; position: relative; overflow: hidden; }
.pgs-banner::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #6366f1, #8b5cf6, #ec4899); opacity: .75; }
.pgs-banner::after { content: ''; position: absolute; top: -40%; right: -5%; width: 220px; height: 220px; background: radial-gradient(circle, rgba(99,102,241,.05), transparent 70%); border-radius: 50%; pointer-events: none; }
.pgs-banner-left { position: relative; z-index: 1; }
.pgs-banner-left h2 { font-size: 1.35rem; font-weight: 700; color: #111827; margin: 0 0 4px; display: flex; align-items: center; gap: 10px; }
.pgs-banner-left h2 i { color: #6366f1; }
.pgs-banner-left p { color: #6b7280; font-size: .85rem; margin: 0; }
.pgs-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.pgs-stat { text-align: center; padding: 10px 16px; background: #f9fafb; border-radius: 12px; border: 1px solid #e5e7eb; min-width: 72px; }
.pgs-stat .num { font-size: 1.45rem; font-weight: 800; color: #111827; }
.pgs-stat .num.blue { color: #3b82f6; }
.pgs-stat .num.green { color: #10b981; }
.pgs-stat .lbl { font-size: .58rem; color: #9ca3af; text-transform: uppercase; font-weight: 600; margin-top: 3px; }
.pgs-toolbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
.pgs-filters { display: flex; gap: 3px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 3px; flex-wrap: wrap; }
.pgs-fbtn { padding: 7px 15px; border: none; background: transparent; color: #6b7280; font-size: .78rem; font-weight: 600; border-radius: 6px; cursor: pointer; transition: all .15s; font-family: inherit; display: flex; align-items: center; gap: 5px; text-decoration: none; }
.pgs-fbtn:hover { color: #111827; background: #f9fafb; }
.pgs-fbtn.active { background: #6366f1; color: #fff; box-shadow: 0 1px 4px rgba(99,102,241,.25); }
.pgs-fbtn .badge { font-size: .68rem; padding: 1px 7px; border-radius: 10px; background: #f3f4f6; font-weight: 700; color: #9ca3af; }
.pgs-fbtn.active .badge { background: rgba(255,255,255,.22); color: #fff; }
.pgs-toolbar-r { display: flex; align-items: center; gap: 10px; }
.pgs-view-toggle { display: flex; gap: 2px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 3px; }
.pgs-view-btn { width: 30px; height: 28px; border: none; background: transparent; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #9ca3af; transition: all .15s; font-size: .78rem; }
.pgs-view-btn.active { background: #fff; color: #6366f1; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
.pgs-search { position: relative; }
.pgs-search input { padding: 8px 12px 8px 34px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; color: #111827; font-size: .82rem; width: 220px; font-family: inherit; transition: all .2s; }
.pgs-search input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.pgs-search i { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: .75rem; }
.pgs-tpl-filter select { padding: 7px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: .78rem; font-family: inherit; color: #374151; background: #fff; cursor: pointer; }
.pgs-tpl-filter select:focus { outline: none; border-color: #6366f1; }
.pgs-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 10px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; font-family: inherit; text-decoration: none; }
.pgs-btn-primary { background: #6366f1; color: #fff; }
.pgs-btn-primary:hover { background: #4f46e5; }
.pgs-btn-ia { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
.pgs-btn-ia:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102,126,234,.3); }
.pgs-table-wrap { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; overflow: hidden; }
.pgs-table { width: 100%; border-collapse: collapse; }
.pgs-table thead th { padding: 11px 14px; font-size: .65rem; font-weight: 700; text-transform: uppercase; color: #9ca3af; background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
.pgs-table tbody tr { border-bottom: 1px solid #f3f4f6; }
.pgs-table tbody tr:hover { background: rgba(99,102,241,.02); }
.pgs-table td { padding: 11px 14px; font-size: .83rem; color: #111827; }
.pgs-title { display: flex; flex-direction: column; gap: 4px; }
.pgs-title-main { color: #111827; text-decoration: none; font-weight: 600; }
.pgs-title-main:hover { color: #6366f1; }
.pgs-slug { font-family: monospace; font-size: .72rem; color: #9ca3af; }
.pgs-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 10px; font-size: .63rem; font-weight: 700; text-transform: uppercase; }
.pgs-badge-t6 { background: #dbeafe; color: #0c4a6e; }
.pgs-badge-draft { background: #fef3c7; color: #92400e; }
.pgs-badge-published { background: #d1fae5; color: #065f46; }
.pgs-actions { display: flex; gap: 3px; }
.pgs-actions a, .pgs-actions button { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9ca3af; background: transparent; border: none; cursor: pointer; transition: all .12s; text-decoration: none; font-size: .78rem; }
.pgs-actions a:hover, .pgs-actions button:hover { color: #6366f1; background: rgba(99,102,241,.07); }
.pgs-actions button.del:hover { color: #dc2626; background: #fef2f2; }
.pgs-score { position:relative; display:inline-flex; align-items:center; justify-content:center; width:34px; height:34px; }
.pgs-score svg { position:absolute; inset:0; }
.pgs-score-num { position:relative; z-index:1; font-size:.65rem; font-weight:800; }
.pgs-words { font-size:.72rem; font-weight:700; padding:2px 8px; border-radius:8px; }
.pgs-words.good { color:#059669; background:#d1fae5; }
.pgs-words.mid  { color:#d97706; background:#fef3c7; }
.pgs-words.low  { color:#ef4444; background:#fee2e2; }
.pgs-serp { display:inline-flex; align-items:center; justify-content:center; font-size:.68rem; font-weight:800; padding:3px 8px; border-radius:8px; min-width:28px; }
.pgs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; }
.pgs-card { background: #fff; border-radius: 14px; border: 1px solid #e5e7eb; overflow: hidden; transition: all .2s; display: flex; flex-direction: column; }
.pgs-card:hover { border-color: #6366f1; box-shadow: 0 4px 20px rgba(99,102,241,.1); transform: translateY(-2px); }
.pgs-card-header { padding: 16px 16px 12px; }
.pgs-card-title { font-size: .88rem; font-weight: 700; color: #111827; text-decoration: none; display: block; }
.pgs-card-title:hover { color: #6366f1; }
.pgs-card-slug { font-family: monospace; font-size: .65rem; color: #9ca3af; margin-top: 5px; }
.pgs-card-footer { padding: 8px 12px; border-top: 1px solid #f3f4f6; display: flex; align-items: center; justify-content: space-between; }
.pgs-empty { text-align: center; padding: 60px 20px; color: #9ca3af; }
.pgs-empty i { font-size: 2.5rem; opacity: .2; margin-bottom: 12px; }
.pgs-empty h3 { color: #6b7280; font-size: 1rem; font-weight: 600; }
.pgs-pagination { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; border-top: 1px solid #e5e7eb; font-size: .78rem; color: #9ca3af; }
.pgs-pagination a { padding: 6px 12px; border: 1px solid #e5e7eb; border-radius: 8px; color: #6b7280; text-decoration: none; font-weight: 600; transition: all .15s; }
.pgs-pagination a:hover { border-color: #6366f1; color: #6366f1; }
.pgs-pagination a.active { background: #6366f1; color: #fff; border-color: #6366f1; }
.pgs-list-view .pgs-grid-wrap { display: none !important; }
.pgs-grid-view .pgs-list-wrap { display: none !important; }
.pgs-toast { position: fixed; bottom: 24px; right: 24px; z-index: 9999; padding: 12px 20px; border-radius: 12px; color: white; font-size: 13px; font-weight: 500; opacity: 0; transform: translateY(20px); transition: all .3s; pointer-events: none; }
.pgs-toast.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
.pgs-toast.ok { background: #10b981; }
.pgs-toast.err { background: #ef4444; }
@media (max-width: 960px) {
    .pgs-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .pgs-toolbar { flex-direction: column; align-items: flex-start; }
    .pgs-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); }
}
</style>

<div class="pgs-wrap pgs-list-view" id="pgsWrap">

<!-- Banner -->
<div class="pgs-banner">
    <div class="pgs-banner-left">
        <h2><i class="fas fa-file-lines"></i> Pages</h2>
        <p>Créez et gérez vos pages avec l'éditeur visuel <a href="?page=pages&action=diagnostic" style="color:#9ca3af;font-size:.75rem;margin-left:6px;text-decoration:none;opacity:.6;transition:opacity .2s" onmouseover="this.style.opacity='1';this.style.color='#6366f1'" onmouseout="this.style.opacity='.6';this.style.color='#9ca3af'" title="Diagnostic API & IA"><i class="fas fa-stethoscope"></i> diag</a></p>
    </div>
    <div class="pgs-stats">
        <div class="pgs-stat"><div class="num blue"><?= $stats['total'] ?></div><div class="lbl">Total</div></div>
        <div class="pgs-stat"><div class="num green"><?= $stats['published'] ?></div><div class="lbl">Publiées</div></div>
        <div class="pgs-stat"><div class="num" style="color:#f59e0b"><?= $stats['draft'] ?></div><div class="lbl">Brouillons</div></div>
    </div>
</div>

<!-- Toolbar -->
<div class="pgs-toolbar">
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <div class="pgs-filters">
            <?php
            $filters = [
                'all'       => ['icon' => 'fa-layer-group', 'label' => 'Toutes', 'count' => $stats['total']],
                'published' => ['icon' => 'fa-check-circle', 'label' => 'Publiées','count' => $stats['published']],
                'draft'     => ['icon' => 'fa-pencil-alt', 'label' => 'Brouillons','count' => $stats['draft']],
            ];
            foreach ($filters as $key => $f):
                $active = ($filterStatus === $key) ? ' active' : '';
                $url = '?page=pages' . ($key !== 'all' ? '&status=' . $key : '');
                if ($filterTemplate !== 'all') $url .= '&template=' . urlencode($filterTemplate);
                if ($searchQuery) $url .= '&q=' . urlencode($searchQuery);
            ?>
                <a href="<?= $url ?>" class="pgs-fbtn<?= $active ?>">
                    <i class="fas <?= $f['icon'] ?>"></i> <?= $f['label'] ?>
                    <span class="badge"><?= (int)$f['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if (!empty($tplFilterOptions)): ?>
        <div class="pgs-tpl-filter">
            <select onchange="location.href=this.value">
                <option value="?page=pages<?= $filterStatus !== 'all' ? '&status='.$filterStatus : '' ?><?= $searchQuery ? '&q='.urlencode($searchQuery) : '' ?>">Tous les templates</option>
                <?php foreach ($tplFilterOptions as $tf): ?>
                <option value="?page=pages&template=<?= urlencode($tf['template']) ?><?= $filterStatus !== 'all' ? '&status='.$filterStatus : '' ?><?= $searchQuery ? '&q='.urlencode($searchQuery) : '' ?>" <?= $filterTemplate === $tf['template'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($tf['template']) ?> (<?= $tf['cnt'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>
    <div class="pgs-toolbar-r">
        <div class="pgs-view-toggle">
            <button class="pgs-view-btn active" onclick="PGS.setView('list')" title="Liste"><i class="fas fa-list"></i></button>
            <button class="pgs-view-btn" onclick="PGS.setView('grid')" title="Grille"><i class="fas fa-th-large"></i></button>
        </div>
        <form class="pgs-search" method="GET">
            <input type="hidden" name="page" value="pages">
            <?php if ($filterStatus !== 'all'): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
            <?php if ($filterTemplate !== 'all'): ?><input type="hidden" name="template" value="<?= htmlspecialchars($filterTemplate) ?>"><?php endif; ?>
            <i class="fas fa-search"></i>
            <input type="text" name="q" placeholder="Titre, slug..." value="<?= htmlspecialchars($searchQuery) ?>">
        </form>
        <button class="pgs-btn pgs-btn-primary" onclick="PGS.openCreateModal()"><i class="fas fa-plus"></i> Nouvelle page</button>
    </div>
</div>

<?php if (empty($pages)): ?>
<div class="pgs-empty">
    <i class="fas fa-<?= $searchQuery ? 'search' : 'file-lines' ?>"></i>
    <h3><?= $searchQuery ? 'Aucune page trouvée' : 'Aucune page' ?></h3>
    <p><?= $searchQuery ? 'Aucun résultat pour « ' . htmlspecialchars($searchQuery) . ' ». <a href="?page=pages" style="color:#6366f1">Effacer</a>' : 'Créez votre première page.' ?></p>
</div>
<?php else: ?>

<!-- Liste -->
<div class="pgs-list-wrap">
    <div class="pgs-table-wrap">
        <table class="pgs-table">
            <thead>
                <tr>
                    <th>Page</th>
                    <th>Template</th>
                    <th>Statut</th>
                    <th style="text-align:center">SEO</th>
                    <th style="text-align:center">Mots</th>
                    <th style="text-align:center">Séman.</th>
                    <th style="text-align:center">SERP</th>
                    <th>Date</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pages as $p):
                $title = $p['title'] ?? 'Sans titre';
                $slug = $p['slug'] ?? '';
                $status = $p['status'] ?? 'draft';
                $isPub = in_array($status, ['published']);
                $template = $p['template'] ?? 'standard';
                $created = isset($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : 'N/A';
                $pageId = $p['id'] ?? 0;
                $editUrl = '?page=pages&action=edit&id=' . $pageId;
                
                $fieldsRaw = json_decode($p['fields'] ?? '{}', true) ?: [];
                $metaT = $p['meta_title'] ?? $p['seo_title'] ?? $fieldsRaw['seo_title'] ?? '';
                $metaD = $p['meta_description'] ?? $p['seo_description'] ?? $fieldsRaw['seo_description'] ?? '';
                $seoScore = (int)($p['seo_score'] ?? 0);
                $semScore = (int)($p['semantic_score'] ?? 0);
                $serpPos  = (int)($p['serp_position'] ?? 0);
                
                $allText = $title . ' ' . $metaT . ' ' . $metaD;
                foreach ($fieldsRaw as $fv) {
                    if (is_string($fv)) $allText .= ' ' . strip_tags($fv);
                }
                $wordCount = str_word_count($allText, 0, 'àâäéèêëïîôùûüÿçœæÀÂÄÉÈÊËÏÎÔÙÛÜŸÇŒÆ');
                
                if ($seoScore === 0) {
                    $sc = 0;
                    if (!empty($metaT)) $sc += 20;
                    if (mb_strlen($metaT) >= 45 && mb_strlen($metaT) <= 65) $sc += 10;
                    if (!empty($metaD)) $sc += 20;
                    if (mb_strlen($metaD) >= 130 && mb_strlen($metaD) <= 165) $sc += 10;
                    if (!empty($slug) && strlen($slug) > 3) $sc += 10;
                    if ($wordCount > 100) $sc += 15;
                    if ($wordCount > 300) $sc += 15;
                    $seoScore = min(100, $sc);
                }
                
                $seoCol = $seoScore >= 80 ? '#10b981' : ($seoScore >= 50 ? '#f59e0b' : '#ef4444');
                $semCol = $semScore >= 80 ? '#10b981' : ($semScore >= 50 ? '#f59e0b' : ($semScore > 0 ? '#ef4444' : '#d1d5db'));
                $serpCol = $serpPos > 0 && $serpPos <= 3 ? '#10b981' : ($serpPos <= 10 ? '#3b82f6' : ($serpPos <= 30 ? '#f59e0b' : '#ef4444'));
            ?>
            <tr>
                <td>
                    <div class="pgs-title">
                        <a href="<?= $editUrl ?>" class="pgs-title-main"><?= htmlspecialchars($title) ?></a>
                        <span class="pgs-slug">/<?= htmlspecialchars($slug) ?></span>
                    </div>
                </td>
                <td><span class="pgs-badge pgs-badge-t6"><i class="fas fa-layer-group" style="font-size:.55rem"></i> <?= htmlspecialchars($template) ?></span></td>
                <td><span class="pgs-badge <?= $isPub ? 'pgs-badge-published' : 'pgs-badge-draft' ?>"><?php echo $isPub ? 'Publiée' : 'Brouillon' ?></span></td>
                <td style="text-align:center">
                    <div class="pgs-score" style="--sc-col:<?= $seoCol ?>">
                        <svg width="32" height="32" viewBox="0 0 36 36"><circle cx="18" cy="18" r="15.5" fill="none" stroke="#e5e7eb" stroke-width="3"/><circle cx="18" cy="18" r="15.5" fill="none" stroke="<?= $seoCol ?>" stroke-width="3" stroke-dasharray="<?= round($seoScore * 97.4 / 100) ?> 97.4" stroke-linecap="round" transform="rotate(-90 18 18)"/></svg>
                        <span class="pgs-score-num" style="color:<?= $seoCol ?>"><?= $seoScore ?></span>
                    </div>
                </td>
                <td style="text-align:center">
                    <span class="pgs-words <?= $wordCount >= 300 ? 'good' : ($wordCount >= 100 ? 'mid' : 'low') ?>"><?= number_format($wordCount) ?></span>
                </td>
                <td style="text-align:center">
                    <?php if ($semScore > 0): ?>
                    <div class="pgs-score" style="--sc-col:<?= $semCol ?>">
                        <svg width="32" height="32" viewBox="0 0 36 36"><circle cx="18" cy="18" r="15.5" fill="none" stroke="#e5e7eb" stroke-width="3"/><circle cx="18" cy="18" r="15.5" fill="none" stroke="<?= $semCol ?>" stroke-width="3" stroke-dasharray="<?= round($semScore * 97.4 / 100) ?> 97.4" stroke-linecap="round" transform="rotate(-90 18 18)"/></svg>
                        <span class="pgs-score-num" style="color:<?= $semCol ?>"><?= $semScore ?></span>
                    </div>
                    <?php else: ?>
                    <span style="color:#d1d5db;font-size:.7rem">—</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center">
                    <?php if ($serpPos > 0): ?>
                    <span class="pgs-serp" style="background:<?= $serpCol ?>18;color:<?= $serpCol ?>">#<?= $serpPos ?></span>
                    <?php else: ?>
                    <span style="color:#d1d5db;font-size:.7rem">—</span>
                    <?php endif; ?>
                </td>
                <td style="color:#9ca3af;font-size:.78rem"><?= $created ?></td>
                <td>
                    <div class="pgs-actions">
                        <a href="<?= $editUrl ?>"><i class="fas fa-edit"></i></a>
                        <?php if (!empty($slug)): ?><a href="/<?= htmlspecialchars($slug) ?>" target="_blank"><i class="fas fa-external-link-alt"></i></a><?php endif; ?>
                        <button onclick="PGS.deletePage(<?= $pageId ?>, '<?= addslashes(htmlspecialchars($title)) ?>')" class="del"><i class="fas fa-trash"></i></button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($totalPages > 1): ?>
        <div class="pgs-pagination">
            <span>Affichage <?= $offset+1 ?>–<?= min($offset+$perPage,$totalFiltered) ?> sur <?= $totalFiltered ?></span>
            <div style="display:flex;gap:4px">
                <?php for ($i=1; $i<=$totalPages; $i++):
                    $pUrl = '?page=pages&p='.$i;
                    if ($filterStatus!=='all') $pUrl .= '&status='.$filterStatus;
                    if ($filterTemplate!=='all') $pUrl .= '&template='.urlencode($filterTemplate);
                    if ($searchQuery) $pUrl .= '&q='.urlencode($searchQuery);
                ?>
                    <a href="<?= $pUrl ?>" class="<?= $i===$currentPage ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Grille -->
<div class="pgs-grid-wrap">
    <div class="pgs-grid">
    <?php foreach ($pages as $p):
        $title = $p['title'] ?? 'Sans titre';
        $slug = $p['slug'] ?? '';
        $status = $p['status'] ?? 'draft';
        $isPub = in_array($status, ['published']);
        $template = $p['template'] ?? 'standard';
        $created = isset($p['created_at']) ? date('d/m/Y', strtotime($p['created_at'])) : 'N/A';
        $pageId = $p['id'] ?? 0;
        $editUrl = '?page=pages&action=edit&id=' . $pageId;
        
        $fieldsRaw2 = json_decode($p['fields'] ?? '{}', true) ?: [];
        $metaT2 = $p['meta_title'] ?? $p['seo_title'] ?? $fieldsRaw2['seo_title'] ?? '';
        $metaD2 = $p['meta_description'] ?? $p['seo_description'] ?? $fieldsRaw2['seo_description'] ?? '';
        $seoScore2 = (int)($p['seo_score'] ?? 0);
        $allText2 = $title . ' ' . $metaT2 . ' ' . $metaD2;
        foreach ($fieldsRaw2 as $fv2) { if (is_string($fv2)) $allText2 .= ' ' . strip_tags($fv2); }
        $wordCount2 = str_word_count($allText2, 0, 'àâäéèêëïîôùûüÿçœæ');
        if ($seoScore2 === 0) {
            $sc2 = 0;
            if (!empty($metaT2)) $sc2 += 20; if (mb_strlen($metaT2) >= 45 && mb_strlen($metaT2) <= 65) $sc2 += 10;
            if (!empty($metaD2)) $sc2 += 20; if (mb_strlen($metaD2) >= 130 && mb_strlen($metaD2) <= 165) $sc2 += 10;
            if (!empty($slug) && strlen($slug) > 3) $sc2 += 10;
            if ($wordCount2 > 100) $sc2 += 15; if ($wordCount2 > 300) $sc2 += 15;
            $seoScore2 = min(100, $sc2);
        }
        $seoCol2 = $seoScore2 >= 80 ? '#10b981' : ($seoScore2 >= 50 ? '#f59e0b' : '#ef4444');
    ?>
    <div class="pgs-card">
        <div class="pgs-card-header">
            <a href="<?= $editUrl ?>" class="pgs-card-title"><?= htmlspecialchars($title) ?></a>
            <span class="pgs-card-slug">/<?= htmlspecialchars($slug) ?></span>
            <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap;align-items:center">
                <span class="pgs-badge pgs-badge-t6" style="font-size:.55rem;padding:3px 7px"><?= htmlspecialchars($template) ?></span>
                <span class="pgs-badge <?= $isPub ? 'pgs-badge-published' : 'pgs-badge-draft' ?>" style="font-size:.55rem;padding:3px 7px"><?php echo $isPub ? 'Pub.' : 'Brouillon' ?></span>
                <span class="pgs-words <?= $wordCount2 >= 300 ? 'good' : ($wordCount2 >= 100 ? 'mid' : 'low') ?>" style="font-size:.55rem;padding:2px 6px"><?= $wordCount2 ?> mots</span>
            </div>
        </div>
        <div class="pgs-card-footer">
            <div style="display:flex;align-items:center;gap:8px">
                <div class="pgs-score" style="--sc-col:<?= $seoCol2 ?>">
                    <svg width="28" height="28" viewBox="0 0 36 36"><circle cx="18" cy="18" r="15.5" fill="none" stroke="#e5e7eb" stroke-width="3"/><circle cx="18" cy="18" r="15.5" fill="none" stroke="<?= $seoCol2 ?>" stroke-width="3" stroke-dasharray="<?= round($seoScore2 * 97.4 / 100) ?> 97.4" stroke-linecap="round" transform="rotate(-90 18 18)"/></svg>
                    <span class="pgs-score-num" style="color:<?= $seoCol2 ?>;font-size:.55rem"><?= $seoScore2 ?></span>
                </div>
                <span style="font-size:.65rem;color:#9ca3af">SEO</span>
            </div>
            <div class="pgs-actions">
                <a href="<?= $editUrl ?>"><i class="fas fa-edit"></i></a>
                <?php if (!empty($slug)): ?><a href="/<?= htmlspecialchars($slug) ?>" target="_blank"><i class="fas fa-external-link-alt"></i></a><?php endif; ?>
                <button onclick="PGS.deletePage(<?= $pageId ?>, '<?= addslashes(htmlspecialchars($title)) ?>')" class="del"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>

<?php endif; ?>
</div>

<div id="pgsToast" class="pgs-toast"></div>

<!-- ══════════════════════════════════════════════════════════ -->
<!-- MODAL CRÉATION PAGE — Templates dynamiques BDD           -->
<!-- ══════════════════════════════════════════════════════════ -->
<style>
.pgs-modal-overlay { display:none; position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,.45); backdrop-filter:blur(3px); align-items:center; justify-content:center; }
.pgs-modal-overlay.show { display:flex; }
.pgs-modal { background:#fff; border-radius:16px; width:620px; max-width:94vw; max-height:90vh; overflow-y:auto; box-shadow:0 20px 60px rgba(0,0,0,.22); }
.pgs-modal-head { display:flex; align-items:center; justify-content:space-between; padding:20px 24px 16px; }
.pgs-modal-head h3 { font-size:17px; font-weight:700; margin:0; display:flex; align-items:center; gap:10px; }
.pgs-modal-head h3 i { color:#6366f1; }
.pgs-modal-close { width:34px; height:34px; border:none; background:#f3f4f6; border-radius:10px; cursor:pointer; color:#6b7280; font-size:14px; display:flex; align-items:center; justify-content:center; transition:all .15s; }
.pgs-modal-close:hover { background:#fee2e2; color:#dc2626; }
.pgs-modal-body { padding:0 24px 20px; }
.pgs-fg { margin-bottom:16px; }
.pgs-fg:last-child { margin-bottom:0; }
.pgs-fg label { display:block; font-size:12px; font-weight:600; color:#374151; text-transform:uppercase; letter-spacing:.04em; margin-bottom:6px; }
.pgs-fg label i { margin-right:4px; }
.pgs-fg label .opt { font-weight:400; text-transform:none; color:#9ca3af; letter-spacing:normal; }
.pgs-fg input[type="text"], .pgs-fg select, .pgs-fg textarea { width:100%; padding:10px 13px; border:1px solid #e5e7eb; border-radius:10px; font-size:13px; font-family:inherit; color:#111827; transition:all .2s; box-sizing:border-box; background:#fff; }
.pgs-fg input:focus, .pgs-fg select:focus, .pgs-fg textarea:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.pgs-fg textarea { resize:vertical; min-height:60px; line-height:1.5; }
.pgs-fg .hint { font-size:11px; color:#9ca3af; margin-top:4px; font-style:italic; }
.pgs-modal-foot { padding:16px 24px; border-top:1px solid #e5e7eb; display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap; }
.pgs-modal-foot .pgs-btn { padding:10px 20px; font-size:13px; }
.pgs-btn-ia:disabled, .pgs-btn-primary:disabled { opacity:.5; cursor:not-allowed; transform:none; box-shadow:none; }

/* ── Template grid dynamique ── */
.pgs-tpl-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(100px,1fr)); gap:8px; max-height:240px; overflow-y:auto; padding:2px; }
.pgs-tpl-btn { padding:10px 6px; border:2px solid #e5e7eb; border-radius:10px; background:#fff; cursor:pointer; transition:all .2s; text-align:center; font-size:10px; font-weight:600; font-family:inherit; color:#374151; position:relative; }
.pgs-tpl-btn:hover { border-color:#6366f1; background:#f0f4ff; }
.pgs-tpl-btn.active { border-color:#6366f1; background:#6366f1; color:#fff; }
.pgs-tpl-btn i { display:block; font-size:16px; margin-bottom:4px; }
.pgs-tpl-btn .pgs-tpl-desc { display:none; position:absolute; bottom:calc(100% + 6px); left:50%; transform:translateX(-50%); background:#1e293b; color:#e2e8f0; padding:6px 10px; border-radius:8px; font-size:10px; font-weight:400; white-space:nowrap; z-index:10; pointer-events:none; }
.pgs-tpl-btn .pgs-tpl-desc::after { content:''; position:absolute; top:100%; left:50%; transform:translateX(-50%); border:5px solid transparent; border-top-color:#1e293b; }
.pgs-tpl-btn:hover .pgs-tpl-desc { display:block; }
.pgs-tpl-count { font-size:10px; color:#9ca3af; margin-top:6px; }
.pgs-tpl-link { font-size:11px; color:#6366f1; text-decoration:none; font-weight:600; }
.pgs-tpl-link:hover { text-decoration:underline; }

/* ── Slug status ── */
.pgs-slug-ok { color:#059669; }
.pgs-slug-ok i { color:#10b981; }
.pgs-slug-ko { color:#dc2626; }
.pgs-slug-ko i { color:#ef4444; }
.pgs-slug-check { color:#6366f1; }
.pgs-slug-input-ok { border-color:#10b981 !important; background:#f0fdf4 !important; }
.pgs-slug-input-ko { border-color:#ef4444 !important; background:#fef2f2 !important; }
@keyframes spin { to { transform:rotate(360deg); } }
</style>

<div class="pgs-modal-overlay" id="pgsCreateOverlay" onclick="if(event.target===this)PGS.closeModal()">
    <div class="pgs-modal">
        <div class="pgs-modal-head">
            <h3><i class="fas fa-plus-circle"></i> Nouvelle page</h3>
            <button class="pgs-modal-close" onclick="PGS.closeModal()"><i class="fas fa-times"></i></button>
        </div>
        
        <div class="pgs-modal-body">
            <!-- Titre -->
            <div class="pgs-fg">
                <label><i class="fas fa-heading" style="color:#6366f1"></i> Titre de la page</label>
                <input type="text" id="pgsNewTitle" placeholder="Ex: Guide acheteur Bordeaux" oninput="PGS.autoSlug()">
            </div>
            
            <!-- Slug -->
            <div class="pgs-fg">
                <label><i class="fas fa-link" style="color:#6366f1"></i> Slug (URL)</label>
                <div style="display:flex;gap:6px">
                    <input type="text" id="pgsNewSlug" placeholder="guide-acheteur-bordeaux" style="font-family:monospace;font-size:12px;flex:1" oninput="PGS.checkSlug()">
                    <button type="button" class="pgs-btn pgs-btn-ia" style="padding:8px 12px;font-size:11px;white-space:nowrap;border-radius:8px" onclick="PGS.aiSlug()" title="Suggérer un slug SEO optimisé">
                        <i class="fas fa-sparkles"></i> IA
                    </button>
                </div>
                <div id="pgsSlugStatus" style="display:flex;align-items:center;gap:6px;margin-top:6px;font-size:12px;min-height:20px">
                    <span style="color:#9ca3af">URL : <strong style="font-family:monospace">/<span id="pgsSlugPreview">...</span></strong></span>
                </div>
            </div>
            
            <!-- Template — DYNAMIQUE depuis BDD -->
            <div class="pgs-fg">
                <label><i class="fas fa-layer-group" style="color:#8b5cf6"></i> Type de page</label>
                <div class="pgs-tpl-grid">
                    <?php foreach ($pageTemplates as $idx => $tpl): 
                        $tplSlug = htmlspecialchars($tpl['slug']);
                        $tplName = htmlspecialchars($tpl['name']);
                        $tplIcon = htmlspecialchars($tpl['icon'] ?: 'fa-file-lines');
                        $tplDesc = htmlspecialchars($tpl['description'] ?? '');
                        $isFirst = ($idx === 0);
                    ?>
                    <button type="button" class="pgs-tpl-btn<?= $isFirst ? ' active' : '' ?>" data-tpl="<?= $tplSlug ?>" onclick="PGS.selectTpl(this)">
                        <i class="fas <?= $tplIcon ?>"></i>
                        <?= $tplName ?>
                        <?php if ($tplDesc): ?><span class="pgs-tpl-desc"><?= $tplDesc ?></span><?php endif; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px">
                    <span class="pgs-tpl-count"><?= count($pageTemplates) ?> templates disponibles</span>
                    <a href="?page=design/templates" class="pgs-tpl-link"><i class="fas fa-palette" style="font-size:10px"></i> Gérer les templates</a>
                </div>
                <input type="hidden" id="pgsNewTemplate" value="<?= htmlspecialchars($pageTemplates[0]['slug'] ?? 'standard') ?>">
            </div>
            
            <!-- Persona -->
            <div class="pgs-fg">
                <label><i class="fas fa-user-tag" style="color:#d4a574"></i> Persona cible</label>
                <select id="pgsNewPersona">
                    <option value="general">Général (tous publics)</option>
                    <option value="vendeur">Vendeur (vendre son bien)</option>
                    <option value="acheteur">Acheteur (cherche un bien)</option>
                    <option value="proprietaire">Propriétaire / Investisseur</option>
                    <option value="nouveau_resident">Nouveau résident</option>
                </select>
            </div>
            
            <!-- Objectif -->
            <div class="pgs-fg">
                <label><i class="fas fa-bullseye" style="color:#10b981"></i> Objectif <span class="opt">(optionnel)</span></label>
                <textarea id="pgsNewObjective" rows="2" placeholder="Ex: Convaincre les vendeurs de faire appel à nos services, montrer notre expertise locale..."></textarea>
                <div class="hint">Utilisé par l'IA pour générer du contenu adapté</div>
            </div>
        </div>
        
        <div class="pgs-modal-foot">
            <button class="pgs-btn" style="background:#f3f4f6;color:#6b7280" onclick="PGS.closeModal()">Annuler</button>
            <button class="pgs-btn pgs-btn-primary" id="pgsBtnCreateEmpty" onclick="PGS.submitCreate('empty')">
                <i class="fas fa-file-lines"></i> Créer vide
            </button>
            <button class="pgs-btn pgs-btn-ia" id="pgsBtnCreateIA" onclick="PGS.submitCreate('ia')">
                <i class="fas fa-sparkles"></i> Créer avec IA
            </button>
        </div>
    </div>
</div>

<script>
const PGS = {
    apiUrl: '/admin/api/content/pages.php',
    csrf: <?php echo json_encode($csrfToken) ?>,
    selectedTpl: <?= json_encode($pageTemplates[0]['slug'] ?? 'standard') ?>,
    
    setView(v) {
        document.getElementById('pgsWrap').classList.toggle('pgs-list-view', v !== 'grid');
        document.getElementById('pgsWrap').classList.toggle('pgs-grid-view', v === 'grid');
        document.querySelectorAll('.pgs-view-btn').forEach(b => b.classList.remove('active'));
        document.querySelector('.pgs-view-btn[onclick*="'+v+'"]')?.classList.add('active');
        try { sessionStorage.setItem('pgs_view', v); } catch(e) {}
    },
    
    openCreateModal() {
        document.getElementById('pgsNewTitle').value = '';
        document.getElementById('pgsNewSlug').value = '';
        document.getElementById('pgsNewSlug').classList.remove('pgs-slug-input-ok', 'pgs-slug-input-ko');
        this.resetSlugStatus();
        document.getElementById('pgsNewPersona').value = 'general';
        document.getElementById('pgsNewObjective').value = '';
        // Sélectionner le premier template
        const firstTpl = document.querySelector('.pgs-tpl-btn');
        if (firstTpl) {
            this.selectedTpl = firstTpl.dataset.tpl;
            document.querySelectorAll('.pgs-tpl-btn').forEach(b => b.classList.toggle('active', b === firstTpl));
            document.getElementById('pgsNewTemplate').value = this.selectedTpl;
        }
        this._lastCheckedSlug = '';
        document.getElementById('pgsCreateOverlay').classList.add('show');
        setTimeout(() => document.getElementById('pgsNewTitle').focus(), 150);
    },
    
    closeModal() {
        document.getElementById('pgsCreateOverlay').classList.remove('show');
    },
    
    selectTpl(btn) {
        document.querySelectorAll('.pgs-tpl-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        this.selectedTpl = btn.dataset.tpl;
        document.getElementById('pgsNewTemplate').value = btn.dataset.tpl;
    },
    
    autoSlug() {
        const title = document.getElementById('pgsNewTitle').value;
        const stopWords = ['le','la','les','un','une','des','de','du','d','l','au','aux','en','et','ou','à','a','ce','ces','son','sa','ses','mon','ma','mes','ton','ta','tes','notre','nos','votre','vos','leur','leurs','qui','que','quoi','dont','où','pour','par','sur','dans','avec','sans','chez','vers','entre','sous','ne','pas','plus','ni','se','si','y','je','tu','il','elle','on','nous','vous','ils','elles','est','sont','être','avoir','fait','faire','dit','comme','tout','tous','très','bien','aussi','mais','car','donc','or'];
        
        let slug = title.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/['']/g, ' ')
            .replace(/[^a-z0-9\s-]/g, '')
            .trim();
        
        let words = slug.split(/\s+/).filter(w => w.length > 0 && !stopWords.includes(w));
        if (words.length > 6) words = words.slice(0, 6);
        slug = words.join('-').replace(/-+/g, '-').replace(/^-|-$/g, '');
        
        document.getElementById('pgsNewSlug').value = slug;
        document.getElementById('pgsSlugPreview').textContent = slug || '...';
        
        if (slug.length >= 2) {
            this.checkSlug();
        } else {
            this.resetSlugStatus();
        }
    },
    
    _slugCheckTimer: null,
    _lastCheckedSlug: '',
    
    checkSlug() {
        const slug = document.getElementById('pgsNewSlug').value.trim().toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9-]/g, '')
            .replace(/-+/g, '-').replace(/^-|-$/g, '');
        
        document.getElementById('pgsNewSlug').value = slug;
        document.getElementById('pgsSlugPreview').textContent = slug || '...';
        
        if (slug.length < 2) { this.resetSlugStatus(); return; }
        
        clearTimeout(this._slugCheckTimer);
        const statusEl = document.getElementById('pgsSlugStatus');
        statusEl.innerHTML = '<span class="pgs-slug-check"><i class="fas fa-spinner" style="animation:spin .8s linear infinite;font-size:10px"></i> Vérification...</span> <span style="color:#9ca3af;font-family:monospace;font-size:11px">/' + slug + '</span>';
        
        this._slugCheckTimer = setTimeout(async () => {
            if (slug === this._lastCheckedSlug) return;
            this._lastCheckedSlug = slug;
            try {
                const r = await fetch(this.apiUrl + '?action=check_slug&slug=' + encodeURIComponent(slug));
                const d = await r.json();
                const input = document.getElementById('pgsNewSlug');
                if (d.available) {
                    statusEl.innerHTML = '<span class="pgs-slug-ok"><i class="fas fa-check-circle"></i> Disponible</span> <span style="color:#9ca3af;font-family:monospace;font-size:11px">/' + slug + '</span>';
                    input.classList.remove('pgs-slug-input-ko');
                    input.classList.add('pgs-slug-input-ok');
                } else {
                    const suggestion = d.suggestion || (slug + '-2');
                    statusEl.innerHTML = '<span class="pgs-slug-ko"><i class="fas fa-times-circle"></i> Déjà pris</span> '
                        + '<a href="#" onclick="event.preventDefault();PGS.useSlugSuggestion(\'' + suggestion + '\')" style="color:#6366f1;font-size:11px;font-weight:600;margin-left:6px"><i class="fas fa-arrow-right" style="font-size:9px"></i> ' + suggestion + '</a>';
                    input.classList.remove('pgs-slug-input-ok');
                    input.classList.add('pgs-slug-input-ko');
                }
            } catch (e) {
                statusEl.innerHTML = '<span style="color:#9ca3af;font-family:monospace;font-size:11px">/' + slug + '</span>';
                document.getElementById('pgsNewSlug').classList.remove('pgs-slug-input-ok', 'pgs-slug-input-ko');
            }
        }, 400);
    },
    
    useSlugSuggestion(slug) {
        document.getElementById('pgsNewSlug').value = slug;
        document.getElementById('pgsSlugPreview').textContent = slug;
        this._lastCheckedSlug = '';
        this.checkSlug();
    },
    
    resetSlugStatus() {
        document.getElementById('pgsSlugStatus').innerHTML = '<span style="color:#9ca3af">URL : <strong style="font-family:monospace">/<span id="pgsSlugPreview">...</span></strong></span>';
        document.getElementById('pgsNewSlug').classList.remove('pgs-slug-input-ok', 'pgs-slug-input-ko');
    },
    
    async aiSlug() {
        const title = document.getElementById('pgsNewTitle').value.trim();
        if (!title) { this.toast('Remplis d\'abord le titre', 'err'); document.getElementById('pgsNewTitle').focus(); return; }
        const statusEl = document.getElementById('pgsSlugStatus');
        statusEl.innerHTML = '<span class="pgs-slug-check"><i class="fas fa-sparkles" style="animation:spin 1s linear infinite;font-size:10px"></i> IA génère le slug...</span>';
        try {
            const r = await fetch(this.apiUrl, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: 'ai_slug', title, template: this.selectedTpl, persona: document.getElementById('pgsNewPersona').value, csrf_token: this.csrf })
            });
            const d = await r.json();
            if (d.success && d.slug) {
                document.getElementById('pgsNewSlug').value = d.slug;
                this._lastCheckedSlug = '';
                this.checkSlug();
                this.toast('Slug IA généré', 'ok');
            } else { this.autoSlug(); this.toast('IA indisponible — slug auto', 'err'); }
        } catch (e) { this.autoSlug(); }
    },
    
    async submitCreate(mode) {
        const title = document.getElementById('pgsNewTitle').value.trim();
        const slug = document.getElementById('pgsNewSlug').value.trim();
        const persona = document.getElementById('pgsNewPersona').value;
        const objective = document.getElementById('pgsNewObjective').value.trim();
        
        if (!title) { this.toast('Le titre est obligatoire', 'err'); document.getElementById('pgsNewTitle').focus(); return; }
        
        const btnEmpty = document.getElementById('pgsBtnCreateEmpty');
        const btnIA = document.getElementById('pgsBtnCreateIA');
        const activeBtn = mode === 'ia' ? btnIA : btnEmpty;
        const origHTML = activeBtn.innerHTML;
        btnEmpty.disabled = true; btnIA.disabled = true;
        activeBtn.innerHTML = '<i class="fas fa-spinner" style="animation:spin .8s linear infinite"></i> ' + (mode === 'ia' ? 'Génération IA...' : 'Création...');
        
        try {
            const r = await fetch(this.apiUrl, {
                method: 'POST', headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ action: mode === 'ia' ? 'create_with_ai' : 'create', title, slug: slug || null, template: this.selectedTpl, persona, objective, csrf_token: this.csrf })
            });
            const d = await r.json();
            if (d.success) {
                const newId = d.page_id || d.id;
                this.closeModal();
                this.toast(mode === 'ia' ? 'Page créée avec contenu IA !' : 'Page créée !', 'ok');
                setTimeout(() => {
                    if (newId) window.location.href = '?page=pages&action=edit&id=' + newId;
                    else if (d.redirect) window.location.href = d.redirect;
                    else window.location.reload();
                }, 600);
            } else { this.toast(d.error || 'Erreur lors de la création', 'err'); }
        } catch (e) { this.toast('Erreur: ' + e.message, 'err'); }
        finally { btnEmpty.disabled = false; btnIA.disabled = false; activeBtn.innerHTML = origHTML; }
    },
    
    deletePage(id, title) {
        if (!confirm('Supprimer « ' + title + ' » ?')) return;
        if (!confirm('Cette action est définitive.')) return;
        const fd = new FormData();
        fd.append('action', 'delete'); fd.append('page_id', id); fd.append('csrf_token', this.csrf);
        fetch(this.apiUrl, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) { this.toast('Page supprimée', 'ok'); setTimeout(() => window.location.reload(), 800); }
                else { this.toast(d.error || 'Erreur', 'err'); }
            })
            .catch(e => this.toast('Erreur: ' + e.message, 'err'));
    },
    
    toast(msg, type = 'ok') {
        const el = document.getElementById('pgsToast');
        el.textContent = msg; el.className = 'pgs-toast show ' + type;
        setTimeout(() => el.classList.remove('show'), 3500);
    }
};

document.addEventListener('keydown', e => { if (e.key === 'Escape') PGS.closeModal(); });
</script>