<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE CMS SECTEURS — EDIT / CREATE v1.0
 * /admin/modules/cms/secteurs/edit.php
 * Layout : 2 colonnes
 *   Col. principale : Identité, SEO, Contenu JSON (atouts, FAQ…)
 *   Sidebar : Hero image, Statut, Géo, Prix, Liens
 * ══════════════════════════════════════════════════════════════
 */

// ─── Connexion DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) require_once dirname(dirname(dirname(__DIR__))) . '/includes/init.php';
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db  = $pdo;

// ─── CSRF ───
if (!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// ─── Mode ───
$id       = (int)($_GET['id'] ?? 0);
$isEdit   = $id > 0;
$pageTitle = $isEdit ? 'Modifier le secteur' : 'Nouveau secteur';
$backUrl   = '?page=cms/secteurs';

// ─── Colonnes disponibles ───
$availCols = [];
try {
    $availCols = $pdo->query("SHOW COLUMNS FROM `secteurs`")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $availCols = [];
}

$hasCol = fn(string $c) => in_array($c, $availCols);

// ─── Charger secteur en édition ───
$s = [];
if ($isEdit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM secteurs WHERE id = ?");
        $stmt->execute([$id]);
        $s = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$s) {
            header("Location: {$backUrl}&msg=notfound");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: {$backUrl}&msg=error");
        exit;
    }
}

// ─── Champs JSON — décoder pour l'affichage dans textarea ───
$jsonFields = ['presentation','atouts','marche_description','profils_cibles','galerie','conseils','faq','secteurs_lies'];
$jsonValues = [];
foreach ($jsonFields as $f) {
    $raw = $s[$f] ?? '';
    if ($raw && is_string($raw)) {
        $decoded = json_decode($raw, true);
        $jsonValues[$f] = ($decoded !== null) ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $raw;
    } else {
        $jsonValues[$f] = '';
    }
}

// ─── Valeur safe helper ───
$v = fn(string $key, string $default = '') => htmlspecialchars($s[$key] ?? $default, ENT_QUOTES);
$checked = fn(string $key, string $val) => (($s[$key] ?? '') === $val) ? ' checked' : '';
$selected = fn(string $key, string $val) => (($s[$key] ?? '') === $val) ? ' selected' : '';

// ═══════════════════════════════════════════════════════════════
// POST — SAVE
// ═══════════════════════════════════════════════════════════════
$errors = [];
$saved  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token CSRF invalide. Rechargez la page.';
    }

    if (empty($errors)) {
        // ─── Champs scalaires ───
        $nom            = trim($_POST['nom']               ?? '');
        $rawSlug        = trim($_POST['slug']              ?? '');
        $ville          = trim($_POST['ville']             ?? 'Bordeaux');
        $type_secteur   = in_array($_POST['type_secteur'] ?? '', ['quartier','commune','zone']) ? $_POST['type_secteur'] : 'quartier';
        $status         = in_array($_POST['status']       ?? '', ['draft','published','archived']) ? $_POST['status'] : 'draft';
        $code_postal    = trim($_POST['code_postal']       ?? '');
        $meta_title     = trim($_POST['meta_title']        ?? '');
        $meta_description = trim($_POST['meta_description'] ?? '');
        $meta_keywords  = trim($_POST['meta_keywords']     ?? '');
        $canonical_url  = trim($_POST['canonical_url']     ?? '');
        $meta_robots    = trim($_POST['meta_robots']       ?? 'index, follow');
        $og_image       = trim($_POST['og_image']          ?? '');
        $hero_image     = trim($_POST['hero_image']        ?? '');
        $hero_title     = trim($_POST['hero_title']        ?? '');
        $hero_subtitle  = trim($_POST['hero_subtitle']     ?? '');
        $hero_cta1_text = trim($_POST['hero_cta1_text']    ?? '');
        $hero_cta1_link = trim($_POST['hero_cta1_link']    ?? '');
        $hero_cta2_text = trim($_POST['hero_cta2_text']    ?? '');
        $hero_cta2_link = trim($_POST['hero_cta2_link']    ?? '');
        $prix_min       = (int)($_POST['prix_min']         ?? 0);
        $prix_max       = (int)($_POST['prix_max']         ?? 0);
        $rendement_min  = !empty($_POST['rendement_min'])  ? (float)$_POST['rendement_min'] : null;
        $rendement_max  = !empty($_POST['rendement_max'])  ? (float)$_POST['rendement_max'] : null;
        $evolution_prix = trim($_POST['evolution_prix']    ?? '');
        $delai_vente    = trim($_POST['delai_vente']       ?? '');
        $latitude       = !empty($_POST['latitude'])       ? (float)$_POST['latitude']  : null;
        $longitude      = !empty($_POST['longitude'])      ? (float)$_POST['longitude'] : null;
        $actif          = isset($_POST['actif'])           ? 1 : 0;
        $site_id        = !empty($_POST['site_id'])        ? (int)$_POST['site_id'] : null;

        // Validation
        if (empty($nom)) $errors[] = 'Le nom du secteur est obligatoire.';
        if (mb_strlen($meta_title) > 70) $errors[] = 'Le meta title dépasse 70 caractères.';
        if (mb_strlen($meta_description) > 160) $errors[] = 'La meta description dépasse 160 caractères.';

        // Slug
        if (empty($rawSlug)) $rawSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', iconv('UTF-8','ASCII//TRANSLIT', $nom)));
        $slug = strtolower(preg_replace('/[^a-z0-9\-]/', '', str_replace(' ', '-', iconv('UTF-8','ASCII//TRANSLIT', $rawSlug))));
        $slug = trim($slug, '-');
        if (empty($slug)) $slug = 'secteur-' . time();

        // Vérifier unicité du slug
        if (empty($errors)) {
            try {
                $sql = "SELECT COUNT(*) FROM secteurs WHERE slug = ?";
                $params = [$slug];
                if ($isEdit) { $sql .= " AND id != ?"; $params[] = $id; }
                $cnt = (int)$pdo->prepare($sql) ? (function() use ($pdo,$sql,$params){ $s=$pdo->prepare($sql);$s->execute($params);return (int)$s->fetchColumn();})() : 0;
                if ($cnt > 0) {
                    $suffix = 2;
                    $baseSlug = $slug;
                    do {
                        $slug = $baseSlug . '-' . $suffix++;
                        $stmtC = $pdo->prepare("SELECT COUNT(*) FROM secteurs WHERE slug = ?" . ($isEdit ? " AND id != ?" : ""));
                        $pC = [$slug]; if ($isEdit) $pC[] = $id;
                        $stmtC->execute($pC);
                    } while ((int)$stmtC->fetchColumn() > 0);
                }
            } catch (PDOException $e) {
                $errors[] = 'Erreur vérification slug.';
            }
        }

        // ─── JSON fields : valider et ré-encoder ───
        $jsonSaved = [];
        foreach ($jsonFields as $f) {
            $rawJson = trim($_POST[$f] ?? '');
            if ($rawJson === '') {
                $jsonSaved[$f] = null;
                continue;
            }
            $decoded = json_decode($rawJson, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                $errors[] = "Contenu JSON invalide dans le champ « {$f} ».";
                $jsonSaved[$f] = $rawJson; // garder la saisie pour renvoi
            } else {
                $jsonSaved[$f] = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        if (empty($errors)) {
            // ─── Colonnes présentes pour l'INSERT/UPDATE ───
            $data = [];
            $data['nom']              = $nom;
            $data['slug']             = $slug;
            $data['ville']            = $ville;
            $data['status']           = $status;
            if ($hasCol('type_secteur'))    $data['type_secteur']    = $type_secteur;
            elseif ($hasCol('type'))        $data['type']            = $type_secteur;
            if ($hasCol('code_postal'))     $data['code_postal']     = $code_postal ?: null;
            if ($hasCol('meta_title'))      $data['meta_title']      = $meta_title ?: null;
            if ($hasCol('meta_description'))$data['meta_description']= $meta_description ?: null;
            if ($hasCol('meta_keywords'))   $data['meta_keywords']   = $meta_keywords ?: null;
            if ($hasCol('canonical_url'))   $data['canonical_url']   = $canonical_url ?: null;
            if ($hasCol('meta_robots'))     $data['meta_robots']     = $meta_robots ?: null;
            if ($hasCol('og_image'))        $data['og_image']        = $og_image ?: null;
            if ($hasCol('hero_image'))      $data['hero_image']      = $hero_image ?: null;
            if ($hasCol('hero_title'))      $data['hero_title']      = $hero_title ?: null;
            if ($hasCol('hero_subtitle'))   $data['hero_subtitle']   = $hero_subtitle ?: null;
            if ($hasCol('hero_cta1_text'))  $data['hero_cta1_text']  = $hero_cta1_text ?: null;
            if ($hasCol('hero_cta1_link'))  $data['hero_cta1_link']  = $hero_cta1_link ?: null;
            if ($hasCol('hero_cta2_text'))  $data['hero_cta2_text']  = $hero_cta2_text ?: null;
            if ($hasCol('hero_cta2_link'))  $data['hero_cta2_link']  = $hero_cta2_link ?: null;
            if ($hasCol('prix_min'))        $data['prix_min']        = $prix_min ?: null;
            if ($hasCol('prix_max'))        $data['prix_max']        = $prix_max ?: null;
            if ($hasCol('rendement_min'))   $data['rendement_min']   = $rendement_min;
            if ($hasCol('rendement_max'))   $data['rendement_max']   = $rendement_max;
            if ($hasCol('evolution_prix'))  $data['evolution_prix']  = $evolution_prix ?: null;
            if ($hasCol('delai_vente'))     $data['delai_vente']     = $delai_vente ?: null;
            if ($hasCol('latitude'))        $data['latitude']        = $latitude;
            if ($hasCol('longitude'))       $data['longitude']       = $longitude;
            if ($hasCol('actif'))           $data['actif']           = $actif;
            if ($hasCol('site_id'))         $data['site_id']         = $site_id;
            $data['updated_at'] = date('Y-m-d H:i:s');

            // JSON fields
            foreach ($jsonFields as $f) {
                if ($hasCol($f)) $data[$f] = $jsonSaved[$f] ?? null;
            }

            try {
                if ($isEdit) {
                    // UPDATE
                    $sets = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($data)));
                    $vals = array_values($data);
                    $vals[] = $id;
                    $pdo->prepare("UPDATE secteurs SET {$sets} WHERE id = ?")->execute($vals);
                    header("Location: {$backUrl}&msg=updated");
                } else {
                    // INSERT
                    $data['created_at'] = date('Y-m-d H:i:s');
                    $cols   = array_keys($data);
                    $ph     = array_fill(0, count($cols), '?');
                    $pdo->prepare('INSERT INTO secteurs (' . implode(',', array_map(fn($c) => "`{$c}`", $cols)) . ') VALUES (' . implode(',', $ph) . ')')->execute(array_values($data));
                    header("Location: {$backUrl}&msg=created");
                }
                exit;
            } catch (PDOException $e) {
                error_log('[CMS Secteurs Edit] save error: ' . $e->getMessage());
                $errors[] = 'Erreur enregistrement : ' . $e->getMessage();
            }
        }

        // En cas d'erreur : renvoyer les valeurs saisies
        $s = array_merge($s, $_POST);
        foreach ($jsonFields as $f) {
            if (isset($jsonSaved[$f])) {
                $decoded = json_decode($jsonSaved[$f] ?? '', true);
                $jsonValues[$f] = $decoded ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : ($jsonSaved[$f] ?? '');
            }
        }
    }
}

// ─── Compteurs pour la barre SEO live ───
$mtLen  = mb_strlen($s['meta_title'] ?? '');
$mdLen  = mb_strlen($s['meta_description'] ?? '');

$sectType = $s['type_secteur'] ?? $s['type'] ?? 'quartier';
?>

<style>
/* ══════════════════════════════════════════════════════════════
   SECTEURS EDIT v1.0
   CSS prefix : .sece-
══════════════════════════════════════════════════════════════ */
.sece-wrap { font-family: var(--font,'Inter',sans-serif); max-width: 1200px; }

/* ─── Top bar ─── */
.sece-topbar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; gap: 12px; flex-wrap: wrap; }
.sece-topbar-left { display: flex; align-items: center; gap: 12px; }
.sece-breadcrumb { display: flex; align-items: center; gap: 8px; font-size: .82rem; color: var(--text-3,#9ca3af); }
.sece-breadcrumb a { color: #6366f1; text-decoration: none; font-weight: 600; }
.sece-breadcrumb a:hover { text-decoration: underline; }
.sece-breadcrumb span { opacity: .4; }
.sece-title { font-size: 1.2rem; font-weight: 800; color: var(--text,#111827); margin: 0; letter-spacing: -.02em; }
.sece-topbar-right { display: flex; gap: 8px; align-items: center; }

/* ─── Layout ─── */
.sece-layout { display: grid; grid-template-columns: 1fr 320px; gap: 18px; align-items: start; }
@media (max-width: 960px) { .sece-layout { grid-template-columns: 1fr; } }

/* ─── Card ─── */
.sece-card { background: var(--surface,#fff); border-radius: 14px; border: 1px solid var(--border,#e5e7eb); margin-bottom: 16px; overflow: hidden; }
.sece-card-head { padding: 16px 20px; border-bottom: 1px solid var(--border,#e5e7eb); display: flex; align-items: center; gap: 10px; background: var(--surface-2,#f9fafb); }
.sece-card-head i { color: #6366f1; font-size: .88rem; width: 18px; text-align: center; }
.sece-card-head h3 { font-size: .88rem; font-weight: 700; color: var(--text,#111827); margin: 0; }
.sece-card-body { padding: 20px; }

/* ─── Grille champs ─── */
.sece-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.sece-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.sece-grid-4 { display: grid; grid-template-columns: repeat(4,1fr); gap: 12px; }
.sece-span-2 { grid-column: span 2; }
@media (max-width: 600px) { .sece-grid-2, .sece-grid-3, .sece-grid-4 { grid-template-columns: 1fr; } .sece-span-2 { grid-column: span 1; } }

/* ─── Form elements ─── */
.sece-group { display: flex; flex-direction: column; gap: 5px; }
.sece-label { font-size: .72rem; font-weight: 700; color: var(--text-2,#6b7280); text-transform: uppercase; letter-spacing: .05em; display: flex; align-items: center; gap: 6px; }
.sece-label .req { color: #ef4444; }
.sece-label .hint { font-size: .65rem; color: var(--text-3,#9ca3af); text-transform: none; letter-spacing: 0; font-weight: 400; margin-left: auto; }
.sece-input, .sece-select, .sece-textarea {
    padding: 9px 12px; border: 1px solid var(--border,#e5e7eb); border-radius: 10px;
    background: var(--surface,#fff); color: var(--text,#111827); font-family: inherit; font-size: .85rem;
    transition: border-color .15s, box-shadow .15s; width: 100%; box-sizing: border-box;
}
.sece-input:focus, .sece-select:focus, .sece-textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.sece-textarea { resize: vertical; min-height: 90px; line-height: 1.5; }
.sece-textarea.mono { font-family: 'Menlo','Fira Code',monospace; font-size: .75rem; min-height: 140px; background: #1e1e2e; color: #cdd6f4; border-color: #3b3f5c; }
.sece-textarea.mono:focus { border-color: #6366f1; }
.sece-input-prefix { display: flex; align-items: stretch; border: 1px solid var(--border,#e5e7eb); border-radius: 10px; overflow: hidden; transition: border-color .15s, box-shadow .15s; }
.sece-input-prefix:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.sece-input-prefix span { padding: 9px 11px; background: var(--surface-2,#f3f4f6); color: var(--text-3,#9ca3af); font-size: .78rem; white-space: nowrap; font-family: monospace; border-right: 1px solid var(--border,#e5e7eb); display: flex; align-items: center; }
.sece-input-prefix input { border: none; padding: 9px 12px; background: transparent; color: var(--text,#111827); font-size: .85rem; font-family: inherit; flex: 1; outline: none; min-width: 0; }
.sece-input-prefix input:focus { border: none; box-shadow: none; }
.sece-range { display: flex; align-items: center; gap: 8px; }
.sece-range input { flex: 1; }
.sece-range span { color: var(--text-3,#9ca3af); font-size: .75rem; flex-shrink: 0; }
.sece-char-bar { height: 3px; border-radius: 2px; margin-top: 4px; background: #e5e7eb; overflow: hidden; }
.sece-char-bar-fill { height: 100%; border-radius: 2px; transition: width .2s, background .2s; }
.sece-char-count { font-size: .62rem; color: var(--text-3,#9ca3af); text-align: right; margin-top: 2px; transition: color .2s; }
.sece-char-count.warn  { color: #f59e0b; }
.sece-char-count.over  { color: #ef4444; }

/* ─── Toggle switch ─── */
.sece-toggle { display: flex; align-items: center; gap: 10px; cursor: pointer; }
.sece-toggle input { display: none; }
.sece-toggle-track { width: 40px; height: 22px; border-radius: 11px; background: var(--border,#e5e7eb); transition: background .2s; position: relative; flex-shrink: 0; }
.sece-toggle input:checked ~ .sece-toggle-track { background: #6366f1; }
.sece-toggle-track::after { content:''; position:absolute; width:16px; height:16px; border-radius:50%; background:#fff; top:3px; left:3px; transition:transform .2s; box-shadow:0 1px 3px rgba(0,0,0,.15); }
.sece-toggle input:checked ~ .sece-toggle-track::after { transform:translateX(18px); }
.sece-toggle-label { font-size:.83rem; font-weight:600; color:var(--text,#111827); }

/* ─── Image preview ─── */
.sece-img-preview { width: 100%; aspect-ratio: 16/9; border-radius: 10px; background: var(--surface-2,#f3f4f6); overflow: hidden; margin-bottom: 10px; position: relative; display: flex; align-items: center; justify-content: center; color: var(--text-3,#9ca3af); font-size: 2rem; }
.sece-img-preview img { width: 100%; height: 100%; object-fit: cover; display: none; }

/* ─── Status select ─── */
.sece-status-published { background: #d1fae5; color: #059669; }
.sece-status-draft     { background: #fef3c7; color: #d97706; }
.sece-status-archived  { background: #f3f4f6; color: #9ca3af; }

/* ─── Sidebar sections ─── */
.sece-publish-btn { width: 100%; padding: 12px; background: #6366f1; color: #fff; border: none; border-radius: 12px; font-size: .88rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-family: inherit; transition: all .15s; }
.sece-publish-btn:hover { background: #4f46e5; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,.25); }
.sece-save-btn { width: 100%; padding: 10px; background: var(--surface-2,#f3f4f6); color: var(--text-2,#6b7280); border: 1px solid var(--border,#e5e7eb); border-radius: 12px; font-size: .82rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; font-family: inherit; transition: all .15s; margin-top: 8px; }
.sece-save-btn:hover { border-color: #6366f1; color: #6366f1; }

/* ─── SEO preview ─── */
.sece-seo-preview { background: var(--surface-2,#f9fafb); border-radius: 10px; padding: 12px 14px; font-family: Arial, sans-serif; }
.sece-seo-preview .url  { font-size: .73rem; color: #0d652d; margin-bottom: 3px; word-break: break-all; }
.sece-seo-preview .title{ font-size: .95rem; color: #1a0dab; font-weight: 400; margin-bottom: 3px; line-height: 1.3; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; }
.sece-seo-preview .desc { font-size: .78rem; color: #545454; line-height: 1.5; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

/* ─── Boutons ─── */
.sece-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 18px; border-radius: 10px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all .15s; font-family: inherit; text-decoration: none; }
.sece-btn-primary { background: #6366f1; color: #fff; }
.sece-btn-primary:hover { background: #4f46e5; transform: translateY(-1px); color: #fff; }
.sece-btn-outline { background: var(--surface,#fff); color: var(--text-2,#6b7280); border: 1px solid var(--border,#e5e7eb); }
.sece-btn-outline:hover { border-color: #6366f1; color: #6366f1; }
.sece-btn-danger  { background: #fef2f2; color: #dc2626; border: 1px solid rgba(220,38,38,.15); }
.sece-btn-danger:hover { background: #dc2626; color: #fff; }

/* ─── Errors ─── */
.sece-errors { background: #fef2f2; border: 1px solid rgba(220,38,38,.15); border-radius: 12px; padding: 14px 18px; margin-bottom: 18px; }
.sece-errors h4 { color: #dc2626; font-size: .85rem; font-weight: 700; margin: 0 0 8px; display: flex; align-items: center; gap: 6px; }
.sece-errors ul { margin: 0; padding-left: 20px; color: #dc2626; font-size: .82rem; line-height: 1.8; }

/* ─── JSON hint ─── */
.sece-json-hint { font-size: .63rem; color: var(--text-3,#9ca3af); margin-top: 4px; line-height: 1.5; }
.sece-json-hint code { background: rgba(99,102,241,.08); color: #6366f1; padding: 1px 4px; border-radius: 3px; font-family: monospace; font-size: .68rem; }
</style>

<div class="sece-wrap">

<!-- ─── Top bar ─── -->
<div class="sece-topbar">
    <div class="sece-topbar-left">
        <nav class="sece-breadcrumb">
            <a href="<?= $backUrl ?>"><i class="fas fa-map-marked-alt"></i> Secteurs</a>
            <span>/</span>
            <span><?= $isEdit ? htmlspecialchars($s['nom'] ?? 'Secteur') : 'Nouveau secteur' ?></span>
        </nav>
    </div>
    <div class="sece-topbar-right">
        <?php if ($isEdit && !empty($s['slug'])): ?>
        <a href="/<?= htmlspecialchars($s['slug']) ?>" target="_blank" class="sece-btn sece-btn-outline" style="font-size:.75rem;padding:6px 12px">
            <i class="fas fa-external-link-alt"></i> Voir en ligne
        </a>
        <?php endif; ?>
        <a href="<?= $backUrl ?>" class="sece-btn sece-btn-outline"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>
</div>

<h2 class="sece-title" style="margin-bottom:20px"><?= $pageTitle ?></h2>

<!-- ─── Erreurs ─── -->
<?php if (!empty($errors)): ?>
<div class="sece-errors">
    <h4><i class="fas fa-exclamation-circle"></i> <?= count($errors) ?> erreur(s) à corriger</h4>
    <ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" id="secteursForm">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

<div class="sece-layout">

    <!-- ══ COLONNE PRINCIPALE ══════════════════════════════════ -->
    <div class="sece-main">

        <!-- ─── Identité ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-map-marker-alt"></i><h3>Identité du secteur</h3></div>
            <div class="sece-card-body">
                <div class="sece-grid-2" style="margin-bottom:14px">
                    <div class="sece-group sece-span-2">
                        <label class="sece-label">Nom <span class="req">*</span></label>
                        <input class="sece-input" type="text" name="nom" value="<?= $v('nom') ?>" placeholder="Ex : Les Chartrons" required oninput="SECE.syncSlug(this.value)">
                    </div>
                    <div class="sece-group">
                        <label class="sece-label">Slug <span class="hint">URL</span></label>
                        <div class="sece-input-prefix">
                            <span>/</span>
                            <input type="text" name="slug" id="seceSlug" value="<?= $v('slug') ?>" placeholder="les-chartrons-bordeaux">
                        </div>
                    </div>
                    <div class="sece-group">
                        <label class="sece-label">Ville</label>
                        <input class="sece-input" type="text" name="ville" value="<?= $v('ville','Bordeaux') ?>" placeholder="Bordeaux">
                    </div>
                    <div class="sece-group">
                        <label class="sece-label">Type de secteur</label>
                        <select class="sece-select" name="type_secteur">
                            <option value="quartier"<?= $selected('type_secteur','quartier') ?>>🏘 Quartier</option>
                            <option value="commune"<?= $selected('type_secteur','commune') ?>>🏙 Commune</option>
                            <option value="zone"<?= $selected('type_secteur','zone') ?>>📐 Zone</option>
                        </select>
                    </div>
                    <div class="sece-group">
                        <label class="sece-label">Code postal</label>
                        <input class="sece-input" type="text" name="code_postal" value="<?= $v('code_postal') ?>" placeholder="33000" maxlength="10">
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Hero ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-image"></i><h3>Section Hero</h3></div>
            <div class="sece-card-body">
                <div style="margin-bottom:14px">
                    <label class="sece-label" style="margin-bottom:6px">Image hero (URL)</label>
                    <input class="sece-input" type="url" name="hero_image" id="seceHeroImg" value="<?= $v('hero_image') ?>" placeholder="https://images.unsplash.com/…" oninput="SECE.previewImg('heroPreview','seceHeroImg')">
                </div>
                <div class="sece-grid-2" style="margin-bottom:14px">
                    <div class="sece-group sece-span-2">
                        <label class="sece-label">Titre hero</label>
                        <input class="sece-input" type="text" name="hero_title" value="<?= $v('hero_title') ?>" placeholder="Les Chartrons : le quartier branché de Bordeaux">
                    </div>
                    <div class="sece-group sece-span-2">
                        <label class="sece-label">Sous-titre</label>
                        <input class="sece-input" type="text" name="hero_subtitle" value="<?= $v('hero_subtitle') ?>" placeholder="Ancien quartier des négociants, devenu le plus tendance…">
                    </div>
                    <div class="sece-group">
                        <label class="sece-label">CTA 1 — Texte</label>
                        <input class="sece-input" type="text" name="hero_cta1_text" value="<?= $v('hero_cta1_text','Voir les prix du marché') ?>">
                    </div>
                    <div class="sece-group">
                        <label class="sece-label">CTA 1 — Lien</label>
                        <input class="sece-input" type="text" name="hero_cta1_link" value="<?= $v('hero_cta1_link','#prix-marche') ?>">
                    </div>
                    <div class="sece-group">
                        <label class="sece-label">CTA 2 — Texte</label>
                        <input class="sece-input" type="text" name="hero_cta2_text" value="<?= $v('hero_cta2_text','Estimer mon bien') ?>">
                    </div>
                    <div class="sece-group">
                        <label class="sece-label">CTA 2 — Lien</label>
                        <input class="sece-input" type="text" name="hero_cta2_link" value="<?= $v('hero_cta2_link','/estimation') ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── SEO ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-search"></i><h3>SEO</h3></div>
            <div class="sece-card-body">
                <!-- Aperçu SERP -->
                <div class="sece-seo-preview" style="margin-bottom:16px">
                    <div class="url" id="secePreviewUrl"><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'monsite.fr') ?>/<span id="secePreviewSlug"><?= htmlspecialchars($s['slug'] ?? '') ?></span></div>
                    <div class="title" id="secePreviewTitle"><?= htmlspecialchars($s['meta_title'] ?? $s['nom'] ?? 'Titre de la page…') ?></div>
                    <div class="desc"  id="secePreviewDesc"><?= htmlspecialchars($s['meta_description'] ?? 'Description de la page dans les résultats Google…') ?></div>
                </div>

                <div class="sece-group" style="margin-bottom:14px">
                    <label class="sece-label">Meta title <span class="hint" id="seceMtCount"><?= $mtLen ?>/70</span></label>
                    <input class="sece-input" type="text" name="meta_title" id="seceMetaTitle" value="<?= $v('meta_title') ?>" maxlength="100" placeholder="Titre optimisé pour Google (≤ 70 caractères)" oninput="SECE.countMeta('meta_title','seceMtCount','secePreviewTitle',70)">
                    <div class="sece-char-bar"><div class="sece-char-bar-fill" id="seceMtBar" style="width:<?= min(100, round($mtLen/70*100)) ?>%;background:<?= $mtLen>70?'#ef4444':($mtLen>55?'#f59e0b':'#10b981') ?>"></div></div>
                </div>
                <div class="sece-group" style="margin-bottom:14px">
                    <label class="sece-label">Meta description <span class="hint" id="seceMdCount"><?= $mdLen ?>/160</span></label>
                    <textarea class="sece-textarea" name="meta_description" id="seceMetaDesc" maxlength="200" rows="3" placeholder="Description courte affichée dans Google (≤ 160 caractères)" oninput="SECE.countMeta('meta_description','seceMdCount','secePreviewDesc',160)"><?= htmlspecialchars($s['meta_description'] ?? '') ?></textarea>
                    <div class="sece-char-bar"><div class="sece-char-bar-fill" id="seceMdBar" style="width:<?= min(100, round($mdLen/160*100)) ?>%;background:<?= $mdLen>160?'#ef4444':($mdLen>130?'#f59e0b':'#10b981') ?>"></div></div>
                </div>
                <div class="sece-grid-2">
                    <div class="sece-group">
                        <label class="sece-label">Mots-clés SEO</label>
                        <input class="sece-input" type="text" name="meta_keywords" value="<?= $v('meta_keywords') ?>" placeholder="chartrons bordeaux, immobilier chartrons">
                    </div>
                    <div class="sece-group">
                        <label class="sece-label">Meta robots</label>
                        <select class="sece-select" name="meta_robots">
                            <option value="index, follow"  <?= $selected('meta_robots','index, follow')  ?>>index, follow</option>
                            <option value="noindex, follow"<?= $selected('meta_robots','noindex, follow')?>  >noindex, follow</option>
                            <option value="noindex, nofollow"<?= $selected('meta_robots','noindex, nofollow')  ?>>noindex, nofollow</option>
                        </select>
                    </div>
                    <div class="sece-group sece-span-2">
                        <label class="sece-label">URL canonique</label>
                        <input class="sece-input" type="url" name="canonical_url" value="<?= $v('canonical_url') ?>" placeholder="https://monsite.fr/chartrons-bordeaux">
                    </div>
                    <div class="sece-group sece-span-2">
                        <label class="sece-label">Image OG (réseaux sociaux)</label>
                        <input class="sece-input" type="url" name="og_image" value="<?= $v('og_image') ?>" placeholder="https://…">
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Présentation ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-align-left"></i><h3>Présentation (JSON array de paragraphes)</h3></div>
            <div class="sece-card-body">
                <textarea class="sece-textarea mono" name="presentation" rows="7" placeholder='["Premier paragraphe de présentation.", "Deuxième paragraphe."]'><?= htmlspecialchars($jsonValues['presentation']) ?></textarea>
                <p class="sece-json-hint">Array JSON de strings. Ex : <code>["Paragraphe 1.", "Paragraphe 2."]</code></p>
            </div>
        </div>

        <!-- ─── Atouts ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-star"></i><h3>Atouts du secteur (JSON)</h3></div>
            <div class="sece-card-body">
                <textarea class="sece-textarea mono" name="atouts" rows="10" placeholder='[{"icon":"🏛️","title":"Patrimoine","description":"Architecture XVIIIe."}]'><?= htmlspecialchars($jsonValues['atouts']) ?></textarea>
                <p class="sece-json-hint">Array d'objets : <code>[{"icon":"🏛️","title":"Titre","description":"…"}]</code></p>
            </div>
        </div>

        <!-- ─── Marché + Profils ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-chart-bar"></i><h3>Marché & Profils cibles</h3></div>
            <div class="sece-card-body">
                <div class="sece-group" style="margin-bottom:14px">
                    <label class="sece-label">Description du marché (JSON array)</label>
                    <textarea class="sece-textarea mono" name="marche_description" rows="5" placeholder='["Le marché est…","La demande est…"]'><?= htmlspecialchars($jsonValues['marche_description']) ?></textarea>
                    <p class="sece-json-hint">Array de paragraphes : <code>["Texte 1.", "Texte 2."]</code></p>
                </div>
                <div class="sece-group">
                    <label class="sece-label">Profils cibles (JSON)</label>
                    <textarea class="sece-textarea mono" name="profils_cibles" rows="8" placeholder='[{"icon":"👨‍👩‍👧","title":"Familles","description":"…"}]'><?= htmlspecialchars($jsonValues['profils_cibles']) ?></textarea>
                    <p class="sece-json-hint">Array : <code>[{"icon":"🎯","title":"Profil","description":"…"}]</code></p>
                </div>
            </div>
        </div>

        <!-- ─── Galerie ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-images"></i><h3>Galerie photos (JSON)</h3></div>
            <div class="sece-card-body">
                <textarea class="sece-textarea mono" name="galerie" rows="8" placeholder='[{"url":"/assets/images/quartiers/photo.jpg","alt":"Description photo"}]'><?= htmlspecialchars($jsonValues['galerie']) ?></textarea>
                <p class="sece-json-hint">Array : <code>[{"url":"/chemin/photo.jpg","alt":"Texte alternatif"}]</code></p>
            </div>
        </div>

        <!-- ─── Conseils ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-lightbulb"></i><h3>Conseils d'achat (JSON)</h3></div>
            <div class="sece-card-body">
                <textarea class="sece-textarea mono" name="conseils" rows="8" placeholder='[{"icon":"🔍","text":"Vérifiez les parties communes."}]'><?= htmlspecialchars($jsonValues['conseils']) ?></textarea>
                <p class="sece-json-hint">Array : <code>[{"icon":"💡","text":"Conseil d'achat."}]</code></p>
            </div>
        </div>

        <!-- ─── FAQ ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-question-circle"></i><h3>FAQ (JSON)</h3></div>
            <div class="sece-card-body">
                <textarea class="sece-textarea mono" name="faq" rows="12" placeholder='[{"question":"Quel est le prix au m² ?","answer":"Entre 4600€ et 6500€/m²."}]'><?= htmlspecialchars($jsonValues['faq']) ?></textarea>
                <p class="sece-json-hint">Array : <code>[{"question":"…","answer":"…"}]</code></p>
            </div>
        </div>

        <!-- ─── Secteurs liés ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-link"></i><h3>Secteurs liés</h3></div>
            <div class="sece-card-body">
                <textarea class="sece-textarea mono" name="secteurs_lies" rows="4" placeholder='["bacalan-bordeaux","chartrons-bordeaux","saint-seurin-bordeaux"]'><?= htmlspecialchars($jsonValues['secteurs_lies']) ?></textarea>
                <p class="sece-json-hint">Array de slugs : <code>["slug-secteur-1","slug-secteur-2"]</code></p>
            </div>
        </div>

    </div>
    <!-- /colonne principale -->

    <!-- ══ SIDEBAR ══════════════════════════════════════════════ -->
    <div class="sece-sidebar">

        <!-- ─── Publication ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-rocket"></i><h3>Publication</h3></div>
            <div class="sece-card-body">
                <div class="sece-group" style="margin-bottom:14px">
                    <label class="sece-label">Statut</label>
                    <select class="sece-select" name="status" id="seceStatus">
                        <option value="draft"     <?= $selected('status','draft')     ?>>✏️ Brouillon</option>
                        <option value="published" <?= $selected('status','published') ?>>✅ Publié</option>
                        <option value="archived"  <?= $selected('status','archived')  ?>>📦 Archivé</option>
                    </select>
                </div>
                <?php if ($hasCol('actif')): ?>
                <div style="margin-bottom:16px">
                    <label class="sece-toggle">
                        <input type="checkbox" name="actif" value="1" <?= !empty($s['actif']) ? 'checked' : '' ?>>
                        <div class="sece-toggle-track"></div>
                        <span class="sece-toggle-label">Page active (visible)</span>
                    </label>
                </div>
                <?php endif; ?>
                <button type="submit" name="status_override" value="published" class="sece-publish-btn">
                    <i class="fas fa-rocket"></i>
                    <?= $isEdit && ($s['status']??'')!=='published' ? 'Publier' : ($isEdit ? 'Enregistrer' : 'Créer et publier') ?>
                </button>
                <button type="submit" class="sece-save-btn">
                    <i class="fas fa-save"></i> Enregistrer brouillon
                </button>
                <?php if ($isEdit): ?>
                <div style="margin-top:12px;text-align:center">
                    <button type="button" onclick="SECE.deleteSecteur(<?= $id ?>, '<?= addslashes(htmlspecialchars($s['nom'] ?? '')) ?>')"
                            class="sece-btn sece-btn-danger" style="width:100%;justify-content:center;font-size:.78rem;padding:7px">
                        <i class="fas fa-trash"></i> Supprimer ce secteur
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─── Aperçu hero ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-eye"></i><h3>Aperçu hero</h3></div>
            <div class="sece-card-body" style="padding:12px">
                <div class="sece-img-preview" id="seceHeroPreview">
                    <i class="fas fa-map-marked-alt"></i>
                    <?php $hImg = $s['hero_image'] ?? ''; ?>
                    <img id="heroPreview" src="<?= htmlspecialchars($hImg) ?>" alt="" style="<?= $hImg?'display:block':'' ?>">
                </div>
                <p style="font-size:.65rem;color:var(--text-3,#9ca3af);margin:0;text-align:center">Aperçu de l'image hero (ratio 16:9)</p>
            </div>
        </div>

        <!-- ─── Prix & Marché ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-euro-sign"></i><h3>Prix & Marché</h3></div>
            <div class="sece-card-body">
                <div class="sece-group" style="margin-bottom:12px">
                    <label class="sece-label">Prix €/m² (fourchette)</label>
                    <div class="sece-range">
                        <input class="sece-input" type="number" name="prix_min" value="<?= (int)($s['prix_min']??0) ?>" min="0" max="99999" placeholder="Min">
                        <span>→</span>
                        <input class="sece-input" type="number" name="prix_max" value="<?= (int)($s['prix_max']??0) ?>" min="0" max="99999" placeholder="Max">
                    </div>
                </div>
                <div class="sece-group" style="margin-bottom:12px">
                    <label class="sece-label">Rendement locatif % (fourchette)</label>
                    <div class="sece-range">
                        <input class="sece-input" type="number" name="rendement_min" value="<?= $s['rendement_min']??'' ?>" step="0.1" min="0" max="20" placeholder="Min %">
                        <span>→</span>
                        <input class="sece-input" type="number" name="rendement_max" value="<?= $s['rendement_max']??'' ?>" step="0.1" min="0" max="20" placeholder="Max %">
                    </div>
                </div>
                <div class="sece-group" style="margin-bottom:12px">
                    <label class="sece-label">Évolution des prix</label>
                    <input class="sece-input" type="text" name="evolution_prix" value="<?= $v('evolution_prix') ?>" placeholder="+5% sur 2 ans">
                </div>
                <div class="sece-group">
                    <label class="sece-label">Délai de vente moyen</label>
                    <input class="sece-input" type="text" name="delai_vente" value="<?= $v('delai_vente') ?>" placeholder="2 à 3 mois">
                </div>
            </div>
        </div>

        <!-- ─── Géolocalisation ─── -->
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-map-pin"></i><h3>Géolocalisation</h3></div>
            <div class="sece-card-body">
                <div class="sece-group" style="margin-bottom:12px">
                    <label class="sece-label">Latitude</label>
                    <input class="sece-input" type="number" name="latitude" value="<?= $s['latitude']??'' ?>" step="0.000001" placeholder="44.841000">
                </div>
                <div class="sece-group">
                    <label class="sece-label">Longitude</label>
                    <input class="sece-input" type="number" name="longitude" value="<?= $s['longitude']??'' ?>" step="0.000001" placeholder="-0.580000">
                </div>
                <?php if ($s['latitude']??''): ?>
                <a href="https://www.google.com/maps?q=<?= $s['latitude'] ?>,<?= $s['longitude'] ?>" target="_blank" style="display:inline-flex;align-items:center;gap:5px;margin-top:10px;font-size:.72rem;color:#6366f1;text-decoration:none">
                    <i class="fas fa-map"></i> Voir sur Google Maps
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─── Site / Template ─── -->
        <?php if ($hasCol('site_id') || $hasCol('template_id')): ?>
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-cog"></i><h3>Configuration</h3></div>
            <div class="sece-card-body">
                <?php if ($hasCol('site_id')): ?>
                <div class="sece-group" style="margin-bottom:12px">
                    <label class="sece-label">Site ID</label>
                    <input class="sece-input" type="number" name="site_id" value="<?= $s['site_id']??'' ?>" placeholder="null = tous les sites">
                </div>
                <?php endif; ?>
                <?php if ($hasCol('template_id')): ?>
                <div class="sece-group">
                    <label class="sece-label">Template ID</label>
                    <input class="sece-input" type="number" name="template_id" value="<?= $s['template_id']??'' ?>" placeholder="Laisser vide = défaut">
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ─── Infos ─── -->
        <?php if ($isEdit): ?>
        <div class="sece-card">
            <div class="sece-card-head"><i class="fas fa-info-circle"></i><h3>Informations</h3></div>
            <div class="sece-card-body">
                <div style="display:flex;flex-direction:column;gap:8px;font-size:.75rem;color:var(--text-2,#6b7280)">
                    <div><span style="color:var(--text-3,#9ca3af)">ID :</span> <strong><?= (int)$id ?></strong></div>
                    <?php if (!empty($s['created_at'])): ?>
                    <div><span style="color:var(--text-3,#9ca3af)">Créé le :</span> <?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($s['updated_at'])): ?>
                    <div><span style="color:var(--text-3,#9ca3af)">Modifié :</span> <?= date('d/m/Y H:i', strtotime($s['updated_at'])) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($s['slug'])): ?>
                    <div style="word-break:break-all"><span style="color:var(--text-3,#9ca3af)">Slug :</span> <code style="font-size:.68rem;color:#6366f1"><?= htmlspecialchars($s['slug']) ?></code></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <!-- /sidebar -->

</div><!-- /sece-layout -->
</form>

</div><!-- /sece-wrap -->

<!-- ══ MODAL DELETE ════════════════════════════════════════════ -->
<div id="seceModal" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;">
    <div onclick="SECE.modalClose()" style="position:absolute;inset:0;background:rgba(0,0,0,.45);backdrop-filter:blur(3px)"></div>
    <div id="seceModalBox" style="position:relative;z-index:1;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);width:100%;max-width:420px;margin:16px;overflow:hidden;transform:scale(.94) translateY(8px);transition:transform .2s cubic-bezier(.34,1.56,.64,1),opacity .15s;opacity:0;">
        <div style="padding:20px 22px 16px;display:flex;align-items:flex-start;gap:14px;background:#fef2f233">
            <div style="width:42px;height:42px;border-radius:12px;background:#fef2f2;color:#dc2626;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0"><i class="fas fa-trash"></i></div>
            <div style="flex:1">
                <div style="font-size:.95rem;font-weight:700;color:#111827;margin-bottom:5px">Supprimer ce secteur ?</div>
                <div id="seceModalMsg" style="font-size:.82rem;color:#6b7280;line-height:1.5"></div>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;padding:12px 20px 18px;border-top:1px solid #f3f4f6;">
            <button onclick="SECE.modalClose()" style="padding:9px 20px;border-radius:10px;border:1px solid #e5e7eb;background:#fff;color:#374151;font-size:.83rem;font-weight:600;cursor:pointer;font-family:inherit;">Annuler</button>
            <button id="seceModalConfirm" style="padding:9px 20px;border-radius:10px;border:none;background:#dc2626;color:#fff;font-size:.83rem;font-weight:700;cursor:pointer;font-family:inherit;">Supprimer</button>
        </div>
    </div>
</div>

<script>
const SECE = {
    apiUrl: '/admin/modules/cms/secteurs/api.php',
    _slugLocked: <?= $isEdit && !empty($s['slug']) ? 'true' : 'false' ?>,

    syncSlug(nom) {
        if (this._slugLocked) return;
        const slug = nom.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '').trim()
            .replace(/\s+/g, '-').replace(/-+/g, '-');
        const input = document.getElementById('seceSlug');
        if (input) { input.value = slug; this.updatePreviewSlug(slug); }
    },

    updatePreviewSlug(slug) {
        const el = document.getElementById('secePreviewSlug');
        if (el) el.textContent = slug;
    },

    countMeta(field, counterId, previewId, max) {
        const input = document.querySelector(`[name="${field}"]`);
        const counter = document.getElementById(counterId);
        const preview = document.getElementById(previewId);
        const barId   = field === 'meta_title' ? 'seceMtBar' : 'seceMdBar';
        const bar     = document.getElementById(barId);
        if (!input || !counter) return;
        const len = input.value.length;
        counter.textContent = len + '/' + max;
        counter.className = len > max ? 'warn over' : (len > max*.85 ? 'warn' : '');
        if (bar) {
            const pct = Math.min(100, Math.round(len / max * 100));
            bar.style.width = pct + '%';
            bar.style.background = len > max ? '#ef4444' : (len > max*.85 ? '#f59e0b' : '#10b981');
        }
        if (preview) preview.textContent = input.value || (field === 'meta_title' ? 'Titre de la page…' : 'Description…');
    },

    previewImg(imgId, inputId) {
        const input = document.getElementById(inputId);
        const img   = document.getElementById(imgId);
        if (!input || !img) return;
        const url = input.value.trim();
        if (!url) { img.style.display = 'none'; return; }
        img.style.display = 'block';
        img.src = url;
        img.onerror = () => img.style.display = 'none';
    },

    deleteSecteur(id, nom) {
        document.getElementById('seceModalMsg').innerHTML = `<strong>${nom}</strong> sera supprimé définitivement.<br><span style="font-size:.78rem;color:#9ca3af">Cette action est irréversible.</span>`;
        const box = document.getElementById('seceModalBox');
        document.getElementById('seceModal').style.display = 'flex';
        requestAnimationFrame(() => { box.style.opacity = '1'; box.style.transform = 'scale(1) translateY(0)'; });
        document.getElementById('seceModalConfirm').onclick = async () => {
            this.modalClose();
            const fd = new FormData();
            fd.append('action','delete'); fd.append('id', id);
            try {
                const r = await fetch(this.apiUrl, {method:'POST',body:fd});
                const d = await r.json();
                if (d.success) { window.location.href = '?page=cms/secteurs&msg=deleted'; }
                else { alert('Erreur : ' + (d.error || 'Inconnue')); }
            } catch(e) { alert('Erreur réseau'); }
        };
    },
    modalClose() {
        const box = document.getElementById('seceModalBox');
        if (!box) return;
        box.style.opacity = '0'; box.style.transform = 'scale(.94) translateY(8px)';
        setTimeout(() => document.getElementById('seceModal').style.display = 'none', 160);
    }
};

// Init
document.addEventListener('DOMContentLoaded', () => {
    // Verrouiller slug en mode édition
    const slugInput = document.getElementById('seceSlug');
    if (slugInput) {
        slugInput.addEventListener('input', () => SECE._slugLocked = slugInput.value.trim() !== '');
        slugInput.addEventListener('focus', () => SECE._slugLocked = true);
    }

    // Aperçu slug initial
    const slug = slugInput?.value;
    if (slug) SECE.updatePreviewSlug(slug);

    // Aperçu hero image si déjà renseignée
    const heroInput = document.getElementById('seceHeroImg');
    if (heroInput?.value) SECE.previewImg('heroPreview', 'seceHeroImg');

    // Validation JSON avant submit
    document.getElementById('secteursForm')?.addEventListener('submit', function(e) {
        const jsonFields = ['presentation','atouts','marche_description','profils_cibles','galerie','conseils','faq','secteurs_lies'];
        const errors = [];
        jsonFields.forEach(f => {
            const el = this.querySelector(`[name="${f}"]`);
            if (!el || !el.value.trim()) return;
            try { JSON.parse(el.value); }
            catch(err) { errors.push(`JSON invalide dans « ${f} » : ${err.message}`); }
        });
        if (errors.length) {
            e.preventDefault();
            alert('Erreurs JSON :\n\n' + errors.join('\n\n'));
        }
    });

    // Sync initial des compteurs meta
    SECE.countMeta('meta_title','seceMtCount','secePreviewTitle',70);
    SECE.countMeta('meta_description','seceMdCount','secePreviewDesc',160);
});
</script>