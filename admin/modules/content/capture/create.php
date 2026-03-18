<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE CAPTURES — Créer / Éditer  v2.2
 *  /admin/modules/content/captures/create.php
 *
 *  Table : captures  (champs réels du dump 2026-02-12)
 *  Champs : titre, slug, description, type ENUM,
 *    template, contenu, headline, sous_titre, image_url,
 *    cta_text, guide_ids(JSON), champs_formulaire(JSON),
 *    page_merci_url, active(tinyint), actif(tinyint),
 *    status ENUM('active','inactive','archived')
 * ══════════════════════════════════════════════════════════════
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }

if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) {
        require_once __DIR__ . '/../../../config/config.php';
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
}
if (isset($db)  && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db))  $db  = $pdo;

$pageId = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'create';
$rec    = null;
$flash  = ['type' => '', 'msg' => ''];

// ─── Charger si édition ───
if ($action === 'edit' && $pageId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM captures WHERE id = ?");
        $stmt->execute([$pageId]);
        $rec = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rec) { header('Location: ?page=captures&msg=notfound'); exit; }
    } catch (PDOException $e) {
        $flash = ['type' => 'e', 'msg' => 'Erreur chargement : ' . $e->getMessage()];
    }
}

// ─── Traitement POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Champs texte simples
    $titre          = trim($_POST['titre']         ?? '');
    $slug           = trim($_POST['slug']          ?? '');
    $description    = trim($_POST['description']   ?? '');
    $headline       = trim($_POST['headline']      ?? '');
    $sous_titre     = trim($_POST['sous_titre']    ?? '');
    $contenu        = trim($_POST['contenu']       ?? '');
    $image_url      = trim($_POST['image_url']     ?? '');
    $cta_text       = trim($_POST['cta_text']      ?? '');
    $page_merci_url = trim($_POST['page_merci_url']?? '');
    $type           = $_POST['type']     ?? 'contact';
    $template       = $_POST['template'] ?? 'simple';
    $status_val     = $_POST['status']   ?? 'active';
    $active         = ($status_val === 'active') ? 1 : 0;
    $actif          = $active;

    // Slug auto depuis titre si vide
    if (empty($slug) && !empty($titre)) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-',
            iconv('UTF-8', 'ASCII//TRANSLIT', $titre)));
        $slug = trim($slug, '-');
    }

    // JSON : champs_formulaire
    $champs_raw = $_POST['champs_formulaire'] ?? '';
    $champs_json = null;
    if (!empty($champs_raw)) {
        $decoded = json_decode($champs_raw, true);
        $champs_json = ($decoded !== null) ? $champs_raw : json_encode(['champs' => $champs_raw]);
    }

    if (empty($titre) || empty($slug)) {
        $flash = ['type' => 'e', 'msg' => 'Le titre et le slug sont obligatoires.'];
    } else {
        try {
            $data = [
                'titre'             => $titre,
                'slug'              => $slug,
                'description'       => $description ?: null,
                'headline'          => $headline     ?: null,
                'sous_titre'        => $sous_titre   ?: null,
                'contenu'           => $contenu      ?: null,
                'image_url'         => $image_url    ?: null,
                'cta_text'          => $cta_text     ?: null,
                'page_merci_url'    => $page_merci_url ?: null,
                'champs_formulaire' => $champs_json,
                'type'              => $type,
                'template'          => $template,
                'status'            => $status_val,
                'active'            => $active,
                'actif'             => $actif,
            ];

            if ($action === 'create') {
                $cols = implode(', ', array_map(fn($k) => "`$k`", array_keys($data)));
                $ph   = implode(', ', array_fill(0, count($data), '?'));
                $pdo->prepare("INSERT INTO captures ($cols, created_at) VALUES ($ph, NOW())")
                    ->execute(array_values($data));
                $pageId = (int)$pdo->lastInsertId();
                $action = 'edit';
                $flash  = ['type' => 's', 'msg' => 'Page de capture créée avec succès !'];
            } else {
                $sets = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
                $pdo->prepare("UPDATE captures SET $sets, updated_at = NOW() WHERE id = ?")
                    ->execute([...array_values($data), $pageId]);
                $flash = ['type' => 's', 'msg' => 'Page mise à jour avec succès !'];
            }

            // Recharger
            $stmt = $pdo->prepare("SELECT * FROM captures WHERE id = ?");
            $stmt->execute([$pageId]);
            $rec = $stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $flash = ['type' => 'e', 'msg' => 'Erreur SQL : ' . $e->getMessage()];
        }
    }
}

// ─── Helpers ───
$v      = fn(string $k, mixed $d = '') => htmlspecialchars($rec[$k] ?? $d);
$isEdit = ($action === 'edit' && $rec);

// Types et templates réels
$captureTypes = [
    'estimation' => ['icon' => 'fa-calculator', 'label' => 'Estimation'],
    'contact'    => ['icon' => 'fa-envelope',   'label' => 'Contact'],
    'newsletter' => ['icon' => 'fa-newspaper',  'label' => 'Newsletter'],
    'guide'      => ['icon' => 'fa-book-open',  'label' => 'Guide / Lead Magnet'],
];
$captureTemplates = [
    'simple'  => ['label' => 'Simple',   'desc' => 'Formulaire épuré, 1 colonne'],
    'hero'    => ['label' => 'Hero',     'desc' => 'Visuel plein écran + form'],
    'split'   => ['label' => 'Split',    'desc' => 'Texte gauche, form droite'],
    'minimal' => ['label' => 'Minimal',  'desc' => 'Minimaliste, focus CTA'],
];
?>
<style>
/* ══ CAPTURES CREATE/EDIT v2.2 — Design unifié ÉCOSYSTÈME IMMO LOCAL+ ══ */
.cap-editor { max-width: 960px; }

.cap-bc { display:flex; align-items:center; gap:8px; font-size:.78rem; color:var(--text-3); margin-bottom:20px; }
.cap-bc a { color:var(--text-3); text-decoration:none; transition:color .15s; }
.cap-bc a:hover { color:#ef4444; }
.cap-bc i { font-size:.6rem; }

.cap-hd { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:14px; }
.cap-hd h1 { font-family:var(--font-display); font-size:1.4rem; font-weight:700; letter-spacing:-.02em; color:var(--text); margin:0 0 4px; display:flex; align-items:center; gap:10px; }
.cap-hd h1 i { color:#ef4444; font-size:1rem; }
.cap-hd p { font-size:.82rem; color:var(--text-3); margin:0; }
.cap-hd-r { display:flex; gap:8px; }

.cap-flash { padding:12px 18px; border-radius:var(--radius); font-size:.85rem; font-weight:600; margin-bottom:20px; display:flex; align-items:center; gap:8px; animation:capFI .3s var(--ease); }
.cap-flash.s { background:var(--green-bg,#d1fae5); color:var(--green,#059669); border:1px solid rgba(5,150,105,.12); }
.cap-flash.e { background:rgba(220,38,38,.06); color:#dc2626; border:1px solid rgba(220,38,38,.12); }
@keyframes capFI { from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:translateY(0)} }

.cap-grid { display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; }

.cap-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; margin-bottom:16px; }
.cap-card-hd { padding:14px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:10px; }
.cap-card-ico { width:30px; height:30px; border-radius:8px; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:12px; color:#fff; }
.cap-card-hd h3 { font-family:var(--font-display); font-size:.95rem; font-weight:700; color:var(--text); margin:0 0 1px; }
.cap-card-hd p { font-size:.72rem; color:var(--text-3); margin:0; }
.cap-card-body { padding:20px; }

.cap-field { margin-bottom:18px; }
.cap-field:last-child { margin-bottom:0; }
.cap-label { display:flex; align-items:center; gap:5px; font-size:.78rem; font-weight:700; color:var(--text-2); margin-bottom:6px; text-transform:uppercase; letter-spacing:.04em; }
.cap-label .req { color:#ef4444; }
.cap-label .hint { font-size:.68rem; font-weight:500; color:var(--text-3); text-transform:none; letter-spacing:0; margin-left:auto; }
.cap-input,.cap-textarea,.cap-select { width:100%; padding:9px 12px; border:1px solid var(--border); border-radius:var(--radius); background:var(--surface); color:var(--text); font-size:.85rem; font-family:var(--font); transition:border-color .15s,box-shadow .15s; box-sizing:border-box; }
.cap-input:focus,.cap-textarea:focus,.cap-select:focus { outline:none; border-color:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.1); }
.cap-textarea { resize:vertical; min-height:120px; line-height:1.6; }
.cap-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.cap-hint { font-size:.7rem; color:var(--text-3); margin-top:5px; display:flex; align-items:center; gap:4px; }
.cap-hint i { font-size:.6rem; }

.cap-slug-wrap { position:relative; }
.cap-slug-pfx { position:absolute; left:10px; top:50%; transform:translateY(-50%); font-size:.72rem; color:var(--text-3); font-family:var(--mono); pointer-events:none; }
.cap-slug-inp { padding-left:72px!important; font-family:var(--mono); font-size:.82rem; }

/* Type selector */
.cap-type-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.cap-type-opt { display:none; }
.cap-type-lbl { display:flex; flex-direction:column; align-items:center; gap:5px; padding:10px 8px; border-radius:var(--radius); border:1.5px solid var(--border); background:var(--surface); cursor:pointer; text-align:center; transition:all .15s; font-size:.7rem; font-weight:600; color:var(--text-2); }
.cap-type-lbl i { font-size:1.1rem; color:var(--text-3); transition:color .15s; }
.cap-type-lbl:hover { border-color:#ef4444; color:#ef4444; background:rgba(239,68,68,.04); }
.cap-type-lbl:hover i { color:#ef4444; }
.cap-type-opt:checked + .cap-type-lbl { border-color:#ef4444; background:rgba(239,68,68,.06); color:#ef4444; box-shadow:0 0 0 3px rgba(239,68,68,.1); }
.cap-type-opt:checked + .cap-type-lbl i { color:#ef4444; }

/* Template selector */
.cap-tpl-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.cap-tpl-opt { display:none; }
.cap-tpl-lbl { display:flex; flex-direction:column; gap:2px; padding:10px 12px; border-radius:var(--radius); border:1.5px solid var(--border); background:var(--surface); cursor:pointer; transition:all .15s; }
.cap-tpl-name { font-size:.78rem; font-weight:700; color:var(--text); }
.cap-tpl-desc { font-size:.65rem; color:var(--text-3); }
.cap-tpl-opt:checked + .cap-tpl-lbl { border-color:#ef4444; background:rgba(239,68,68,.05); }
.cap-tpl-opt:checked + .cap-tpl-lbl .cap-tpl-name { color:#ef4444; }

/* Status */
.cap-status-group { display:flex; gap:6px; }
.cap-status-opt { display:none; }
.cap-status-lbl { flex:1; display:flex; align-items:center; justify-content:center; gap:6px; padding:9px 10px; border-radius:var(--radius); border:1.5px solid var(--border); background:var(--surface); cursor:pointer; font-size:.78rem; font-weight:600; color:var(--text-2); transition:all .15s; }
.cap-status-opt:checked + .cap-status-lbl.lbl-active   { border-color:#059669; background:#d1fae5; color:#065f46; }
.cap-status-opt:checked + .cap-status-lbl.lbl-inactive { border-color:var(--border-h); background:var(--surface-2); color:var(--text-2); }
.cap-status-opt:checked + .cap-status-lbl.lbl-archived { border-color:#d97706; background:#fffbeb; color:#92400e; }

/* Live preview */
.cap-preview { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; margin-bottom:16px; position:sticky; top:20px; }
.cap-preview-hd { padding:11px 16px; background:var(--surface-2); border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; font-size:.78rem; font-weight:700; color:var(--text-2); }
.cap-preview-hd i { color:var(--text-3); }
.cap-pv { padding:16px 14px; background:#fff; border-bottom:3px solid #ef4444; min-height:180px; transition:border-color .3s; }
.cap-pv-headline { font-weight:800; font-size:.95rem; color:#111; line-height:1.3; margin-bottom:3px; }
.cap-pv-sous { font-size:.72rem; color:#555; margin-bottom:8px; line-height:1.4; }
.cap-pv-desc { font-size:.68rem; color:#888; text-align:left; background:#f9f9f9; border-radius:5px; padding:7px 9px; margin-bottom:12px; line-height:1.5; }
.cap-pv-form { background:#f3f4f6; border-radius:7px; padding:11px; border:1px solid #e5e7eb; }
.cap-pv-form input { width:100%; padding:6px 9px; border:1px solid #d1d5db; border-radius:5px; margin-bottom:5px; font-size:.68rem; background:#fff; box-sizing:border-box; color:#111; }
.cap-pv-cta { width:100%; padding:8px; border:none; border-radius:5px; font-size:.72rem; font-weight:700; color:#fff; cursor:default; background:#ef4444; transition:background .3s; }

/* Stats recap */
.cap-stats-recap { display:flex; gap:14px; padding:10px 12px; background:var(--surface-2); border-radius:var(--radius); font-size:.78rem; color:var(--text-2); }
.cap-stats-recap strong { color:var(--accent); }

/* Submit bar */
.cap-submit { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); margin-top:4px; gap:12px; flex-wrap:wrap; }
.cap-submit-l,.cap-submit-r { display:flex; gap:8px; }

.cap-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; border-radius:var(--radius); font-size:.82rem; font-weight:600; cursor:pointer; border:none; font-family:var(--font); text-decoration:none; line-height:1.3; transition:all .15s var(--ease); }
.cap-btn-primary { background:#ef4444; color:#fff; box-shadow:0 1px 4px rgba(239,68,68,.22); }
.cap-btn-primary:hover { background:#dc2626; transform:translateY(-1px); color:#fff; }
.cap-btn-green { background:#059669; color:#fff; }
.cap-btn-green:hover { background:#047857; transform:translateY(-1px); color:#fff; }
.cap-btn-outline { background:var(--surface); color:var(--text-2); border:1px solid var(--border); }
.cap-btn-outline:hover { border-color:var(--border-h); background:var(--surface-2); }
.cap-btn-ghost { background:transparent; color:var(--text-3); border:1px solid transparent; }
.cap-btn-ghost:hover { color:#ef4444; border-color:rgba(239,68,68,.2); background:rgba(239,68,68,.04); }

@media(max-width:1050px){ .cap-grid{grid-template-columns:1fr} .cap-preview{position:static} }
@media(max-width:640px) { .cap-row{grid-template-columns:1fr} .cap-submit{flex-direction:column} }
</style>

<div class="cap-editor">

<!-- Breadcrumb -->
<div class="cap-bc">
    <a href="?page=captures"><i class="fas fa-magnet"></i> Pages de capture</a>
    <i class="fas fa-chevron-right"></i>
    <span><?= $isEdit ? htmlspecialchars($rec['titre'] ?? 'Édition') : 'Nouvelle page' ?></span>
</div>

<!-- Header -->
<div class="cap-hd">
    <div>
        <h1><i class="fas fa-<?= $isEdit?'edit':'plus-circle' ?>"></i>
            <?= $isEdit ? 'Modifier la page' : 'Nouvelle page de capture' ?></h1>
        <p><?= $isEdit ? 'Modifiez les paramètres et le contenu de cette page' : 'Créez une page pour capturer des leads qualifiés' ?></p>
    </div>
    <div class="cap-hd-r">
        <?php if ($isEdit && ($rec['status']??'')==='active' && !empty($rec['slug'])): ?>
        <a href="/capture/<?= htmlspecialchars($rec['slug']) ?>" target="_blank" class="cap-btn cap-btn-outline">
            <i class="fas fa-eye"></i> Voir
        </a>
        <?php endif; ?>
        <a href="?page=captures" class="cap-btn cap-btn-outline"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>
</div>

<!-- Flash -->
<?php if ($flash['msg']): ?>
<div class="cap-flash <?= $flash['type'] ?>">
    <i class="fas fa-<?= $flash['type']==='s'?'check':'exclamation' ?>-circle"></i>
    <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

<form method="POST" id="capForm">
<div class="cap-grid">

    <!-- ════ GAUCHE ════ -->
    <div>

        <!-- Contenu principal -->
        <div class="cap-card">
            <div class="cap-card-hd">
                <div class="cap-card-ico" style="background:#ef4444"><i class="fas fa-file-alt"></i></div>
                <div><h3>Contenu principal</h3><p>Titre, accroche, description, CTA</p></div>
            </div>
            <div class="cap-card-body">
                <div class="cap-row">
                    <div class="cap-field">
                        <div class="cap-label">Titre <span class="req">*</span></div>
                        <input class="cap-input" type="text" name="titre" id="f_titre" required
                               placeholder="Ex: Estimez votre bien" value="<?= $v('titre') ?>" maxlength="255">
                    </div>
                    <div class="cap-field">
                        <div class="cap-label">Slug URL <span class="req">*</span></div>
                        <div class="cap-slug-wrap">
                            <span class="cap-slug-pfx">/capture/</span>
                            <input class="cap-input cap-slug-inp" type="text" name="slug" id="f_slug"
                                   required placeholder="estimation-gratuite"
                                   value="<?= $v('slug') ?>" maxlength="255">
                        </div>
                        <div class="cap-hint"><i class="fas fa-link"></i> URL publique de la page</div>
                    </div>
                </div>

                <div class="cap-field">
                    <div class="cap-label">Headline <span class="cap-label"><span class="hint">Titre affiché sur la page</span></span></div>
                    <input class="cap-input" type="text" name="headline" id="f_headline"
                           placeholder="Ex: Titre principal (Headline)"
                           value="<?= $v('headline') ?>" maxlength="255">
                </div>

                <div class="cap-field">
                    <div class="cap-label">Sous-titre <span class="cap-label"><span class="hint">Accroche</span></span></div>
                    <textarea class="cap-textarea" name="sous_titre" id="f_sous"
                              style="min-height:70px"
                              placeholder="Ex: Votre estimation personnalisée en moins de 2 min"><?= $v('sous_titre') ?></textarea>
                </div>

                <div class="cap-field">
                    <div class="cap-label">Description <span class="cap-label"><span class="hint">Texte principal / proposition de valeur</span></span></div>
                    <textarea class="cap-textarea" name="description" id="f_desc"
                              placeholder="Décrivez les bénéfices concrets pour le prospect…"><?= $v('description') ?></textarea>
                </div>
            </div>
        </div>

        <!-- Formulaire & CTA -->
        <div class="cap-card">
            <div class="cap-card-hd">
                <div class="cap-card-ico" style="background:#f97316"><i class="fas fa-bullseye"></i></div>
                <div><h3>Formulaire & CTA</h3><p>Bouton d'action et redirection</p></div>
            </div>
            <div class="cap-card-body">
                <div class="cap-row">
                    <div class="cap-field">
                        <div class="cap-label">Texte du bouton CTA</div>
                        <input class="cap-input" type="text" name="cta_text" id="f_cta"
                               placeholder="Ex: Demander mon estimation"
                               value="<?= $v('cta_text','Demander mon estimation') ?>" maxlength="100">
                    </div>
                    <div class="cap-field">
                        <div class="cap-label">URL page de remerciement</div>
                        <input class="cap-input" type="text" name="page_merci_url"
                               placeholder="Ex: /merci"
                               value="<?= $v('page_merci_url') ?>">
                    </div>
                </div>
                <div class="cap-field">
                    <div class="cap-label">Image URL <span class="cap-label"><span class="hint">Image hero / visuel</span></span></div>
                    <input class="cap-input" type="text" name="image_url"
                           placeholder="https://…/image.jpg"
                           value="<?= $v('image_url') ?>">
                </div>
            </div>
        </div>

        <!-- Contenu libre -->
        <div class="cap-card">
            <div class="cap-card-hd">
                <div class="cap-card-ico" style="background:#6366f1"><i class="fas fa-code"></i></div>
                <div><h3>Contenu HTML libre</h3><p>Section optionnelle de la page (HTML)</p></div>
            </div>
            <div class="cap-card-body">
                <div class="cap-field">
                    <textarea class="cap-textarea" name="contenu" style="min-height:120px;font-family:var(--mono);font-size:.8rem"
                              placeholder="<!-- HTML personnalisé pour cette page -->"><?= $v('contenu') ?></textarea>
                    <div class="cap-hint"><i class="fas fa-info-circle"></i> HTML injecté dans le corps de la page publique</div>
                </div>
            </div>
        </div>

    </div><!-- fin gauche -->

    <!-- ════ DROITE ════ -->
    <div>

        <!-- Live preview -->
        <div class="cap-preview">
            <div class="cap-preview-hd">
                <span><i class="fas fa-eye"></i> Aperçu</span>
                <i class="fas fa-mobile-alt"></i>
            </div>
            <div class="cap-pv" id="pvPage">
                <div class="cap-pv-headline" id="pvHeadline"><?= $v('headline', $v('titre','Titre principal')) ?></div>
                <div class="cap-pv-sous"     id="pvSous"><?= $v('sous_titre','Sous-titre accrocheur') ?></div>
                <div class="cap-pv-desc"     id="pvDesc"><?= $v('description','Description de la proposition de valeur…') ?></div>
                <div class="cap-pv-form">
                    <input type="text" placeholder="Prénom" disabled>
                    <input type="text" placeholder="Email"  disabled>
                    <button class="cap-pv-cta" id="pvCta"><?= $v('cta_text','Demander mon estimation') ?></button>
                </div>
            </div>
        </div>

        <!-- Type -->
        <div class="cap-card">
            <div class="cap-card-hd">
                <div class="cap-card-ico" style="background:#6366f1"><i class="fas fa-tag"></i></div>
                <div><h3>Type de capture</h3></div>
            </div>
            <div class="cap-card-body">
                <div class="cap-type-grid">
                    <?php foreach ($captureTypes as $key => $ct): ?>
                    <div>
                        <input type="radio" class="cap-type-opt" name="type" id="t_<?= $key ?>"
                               value="<?= $key ?>" <?= ($rec['type']??'contact')===$key?'checked':'' ?>>
                        <label class="cap-type-lbl" for="t_<?= $key ?>">
                            <i class="fas <?= $ct['icon'] ?>"></i> <?= $ct['label'] ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Template -->
        <div class="cap-card">
            <div class="cap-card-hd">
                <div class="cap-card-ico" style="background:#8b5cf6"><i class="fas fa-palette"></i></div>
                <div><h3>Template visuel</h3></div>
            </div>
            <div class="cap-card-body">
                <div class="cap-tpl-grid">
                    <?php foreach ($captureTemplates as $key => $tpl): ?>
                    <div>
                        <input type="radio" class="cap-tpl-opt" name="template" id="tpl_<?= $key ?>"
                               value="<?= $key ?>" <?= ($rec['template']??'simple')===$key?'checked':'' ?>>
                        <label class="cap-tpl-lbl" for="tpl_<?= $key ?>">
                            <span class="cap-tpl-name"><?= $tpl['label'] ?></span>
                            <span class="cap-tpl-desc"><?= $tpl['desc'] ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Statut -->
        <div class="cap-card">
            <div class="cap-card-hd">
                <div class="cap-card-ico" style="background:#059669"><i class="fas fa-toggle-on"></i></div>
                <div><h3>Statut de publication</h3></div>
            </div>
            <div class="cap-card-body">
                <div class="cap-status-group">
                    <?php foreach ([
                        'active'   => ['fa-check-circle','lbl-active',  'Active'],
                        'inactive' => ['fa-pause-circle','lbl-inactive', 'Inactive'],
                        'archived' => ['fa-archive',     'lbl-archived', 'Archivée'],
                    ] as $skey => [$sico,$scls,$slbl]):
                        $checked = ($rec['status'] ?? 'active') === $skey; ?>
                    <input type="radio" class="cap-status-opt" name="status" id="s_<?= $skey ?>"
                           value="<?= $skey ?>" <?= $checked?'checked':'' ?>>
                    <label class="cap-status-lbl <?= $scls ?>" for="s_<?= $skey ?>">
                        <i class="fas <?= $sico ?>"></i> <?= $slbl ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <?php if ($isEdit && ((int)($rec['vues']??0) > 0 || (int)($rec['conversions']??0) > 0)): ?>
                <div class="cap-stats-recap" style="margin-top:12px">
                    <span><strong><?= number_format($rec['vues']??0) ?></strong> vues</span>
                    <span><strong><?= number_format($rec['conversions']??0) ?></strong> conversions</span>
                    <?php if ((float)($rec['taux_conversion']??0) > 0): ?>
                    <span><strong><?= $rec['taux_conversion'] ?>%</strong></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- fin droite -->
</div><!-- fin grid -->

<!-- Submit bar -->
<div class="cap-submit">
    <div class="cap-submit-l">
        <button type="submit" class="cap-btn cap-btn-primary">
            <i class="fas fa-save"></i> Enregistrer
        </button>
        <?php if ($isEdit && ($rec['status']??'')==='inactive'): ?>
        <button type="submit" class="cap-btn cap-btn-green"
                onclick="document.getElementById('s_active').checked=true">
            <i class="fas fa-rocket"></i> Enregistrer &amp; Activer
        </button>
        <?php endif; ?>
    </div>
    <div class="cap-submit-r">
        <?php if ($isEdit): ?>
        <button type="button" class="cap-btn cap-btn-ghost"
                onclick="if(confirm('Dupliquer cette page ?')) CAP_dup(<?= $pageId ?>)">
            <i class="fas fa-copy"></i> Dupliquer
        </button>
        <?php endif; ?>
        <a href="?page=captures" class="cap-btn cap-btn-outline">
            <i class="fas fa-times"></i> Annuler
        </a>
    </div>
</div>

</form>
</div>

<script>
// ─── Live preview ───
[
    ['f_headline','pvHeadline'],
    ['f_sous',    'pvSous'],
    ['f_desc',    'pvDesc'],
    ['f_cta',     'pvCta'],
].forEach(([inp,pv]) => {
    const el=document.getElementById(inp), pEl=document.getElementById(pv);
    if(el&&pEl) el.addEventListener('input', ()=>{ pEl.textContent=el.value.trim()||'…'; });
});

// headline suit le titre si vide
document.getElementById('f_titre').addEventListener('input', function(){
    const h=document.getElementById('f_headline');
    if(!h.value.trim()) document.getElementById('pvHeadline').textContent=this.value||'Titre';
});

// Slug auto
const fTitre=document.getElementById('f_titre');
const fSlug =document.getElementById('f_slug');
fTitre.addEventListener('input', ()=>{
    if(fSlug.dataset.manual) return;
    fSlug.value = fTitre.value.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
        .replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
});
fSlug.addEventListener('input', ()=>{ fSlug.dataset.manual='1'; });

// Dupliquer
async function CAP_dup(id){
    const fd=new FormData(); fd.append('action','duplicate'); fd.append('id',id);
    const d=await (await fetch(window.location.href,{method:'POST',body:fd})).json();
    if(d.success) window.location.href='?page=captures&msg=duplicated';
    else alert(d.error||'Erreur');
}

// Auto-dismiss flash
document.querySelectorAll('.cap-flash').forEach(el=>{
    setTimeout(()=>{ el.style.transition='opacity .4s'; el.style.opacity='0'; setTimeout(()=>el.remove(),400); },5000);
});
</script>