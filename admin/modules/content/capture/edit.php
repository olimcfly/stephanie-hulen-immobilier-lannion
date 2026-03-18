<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  ÉDITEUR DE PAGE DE CAPTURE
 *  /admin/modules/content/pages-capture/edit.php
 *  Chargé depuis index.php quand ?action=edit&id=X ou ?action=create
 * ══════════════════════════════════════════════════════════════
 */

// ── Données d'initialisation ──────────────────────────────────
$captureId = (int)($_GET['id'] ?? 0);
$isNew     = ($captureId === 0);
$capture   = [];
$saveError = null;
$saveOk    = false;

// ── Types et templates ────────────────────────────────────────
$captureTypes = [
    'estimation' => ['icon' => 'fa-calculator', 'label' => 'Estimation',         'color' => '#3b82f6'],
    'contact'    => ['icon' => 'fa-envelope',   'label' => 'Contact',             'color' => '#10b981'],
    'newsletter' => ['icon' => 'fa-newspaper',  'label' => 'Newsletter',          'color' => '#ec4899'],
    'guide'      => ['icon' => 'fa-book-open',  'label' => 'Guide / Lead Magnet', 'color' => '#8b5cf6'],
];
$captureTemplates = [
    'simple'  => ['label' => 'Simple',  'desc' => 'Formulaire centré épuré'],
    'hero'    => ['label' => 'Hero',    'desc' => 'Grande image + formulaire'],
    'split'   => ['label' => 'Split',   'desc' => 'Contenu gauche + formulaire droite (recommandé pour guides)'],
    'minimal' => ['label' => 'Minimal', 'desc' => 'Ultra-compact, sans distraction'],
];

// ── Charger la capture existante ─────────────────────────────
if (!$isNew && $captureId > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM captures WHERE id = ? LIMIT 1");
        $stmt->execute([$captureId]);
        $capture = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (Exception $e) { $saveError = 'Capture introuvable : ' . $e->getMessage(); }
}

// ── Traitement POST (sauvegarde) ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_edit_submit'] ?? '') === '1') {
    $d = [
        'titre'        => trim($_POST['titre']        ?? ''),
        'slug'         => trim($_POST['slug']         ?? ''),
        'type'         => $_POST['type']              ?? 'guide',
        'template'     => $_POST['template']          ?? 'split',
        'headline'     => trim($_POST['headline']     ?? ''),
        'sous_titre'   => trim($_POST['sous_titre']   ?? ''),
        'description'  => trim($_POST['description']  ?? ''),
        'cta_text'     => trim($_POST['cta_text']     ?? ''),
        'page_merci_url'=> trim($_POST['page_merci_url'] ?? '/merci'),
        'status'       => ($_POST['status'] ?? 'inactive') === 'active' ? 'active' : 'inactive',
        'active'       => ($_POST['status'] ?? 'inactive') === 'active' ? 1 : 0,
        'actif'        => ($_POST['status'] ?? 'inactive') === 'active' ? 1 : 0,
    ];

    // Slug auto si vide
    if (!$d['slug'] && $d['titre']) {
        $d['slug'] = strtolower(trim(preg_replace('/[^a-z0-9]+/', '-', iconv('UTF-8','ASCII//TRANSLIT', $d['titre'])), '-'));
    }
    $d['slug'] = preg_replace('/[^a-z0-9-]/', '', strtolower($d['slug']));

    if (!$d['titre'] || !$d['slug']) {
        $saveError = 'Le titre et le slug sont obligatoires.';
    } else {
        try {
            if ($isNew) {
                $stmt = $pdo->prepare("INSERT INTO captures
                    (titre, slug, type, template, headline, sous_titre, description, cta_text, page_merci_url, status, active, actif, vues, conversions, taux_conversion)
                    VALUES
                    (:titre,:slug,:type,:template,:headline,:sous_titre,:description,:cta_text,:page_merci_url,:status,:active,:actif,0,0,0.00)");
                $stmt->execute($d);
                $newId = (int)$pdo->lastInsertId();
                header('Location: ?page=captures&action=edit&id=' . $newId . '&msg=created');
                exit;
            } else {
                $stmt = $pdo->prepare("UPDATE captures SET
                    titre=:titre, slug=:slug, type=:type, template=:template,
                    headline=:headline, sous_titre=:sous_titre, description=:description,
                    cta_text=:cta_text, page_merci_url=:page_merci_url,
                    status=:status, active=:active, actif=:actif
                    WHERE id=:id");
                $d[':id'] = $captureId;
                $stmt->execute(array_merge($d, ['id' => $captureId]));
                // Recharger
                $capture = $pdo->prepare("SELECT * FROM captures WHERE id = ?")->execute([$captureId])
                    ? $pdo->prepare("SELECT * FROM captures WHERE id = ?")->execute([$captureId]) && false ?: null : null;
                $stmt2 = $pdo->prepare("SELECT * FROM captures WHERE id = ?");
                $stmt2->execute([$captureId]);
                $capture = $stmt2->fetch(PDO::FETCH_ASSOC) ?: $d;
                $saveOk = true;
            }
        } catch (Exception $e) {
            $saveError = $e->getMessage();
        }
    }
}

// Valeurs courantes (fusion POST pour re-affichage en cas d'erreur)
$v = [
    'titre'         => $_POST['titre']         ?? ($capture['titre']         ?? ''),
    'slug'          => $_POST['slug']          ?? ($capture['slug']          ?? ''),
    'type'          => $_POST['type']          ?? ($capture['type']          ?? 'guide'),
    'template'      => $_POST['template']      ?? ($capture['template']      ?? 'split'),
    'headline'      => $_POST['headline']      ?? ($capture['headline']      ?? ''),
    'sous_titre'    => $_POST['sous_titre']    ?? ($capture['sous_titre']    ?? ''),
    'description'   => $_POST['description']  ?? ($capture['description']   ?? ''),
    'cta_text'      => $_POST['cta_text']      ?? ($capture['cta_text']      ?? '📥 Recevoir mon guide gratuitement'),
    'page_merci_url'=> $_POST['page_merci_url']?? ($capture['page_merci_url']?? '/merci'),
    'status'        => $_POST['status']        ?? ($capture['status']        ?? 'inactive'),
    'vues'          => (int)($capture['vues']        ?? 0),
    'conversions'   => (int)($capture['conversions'] ?? 0),
    'taux'          => (float)($capture['taux_conversion'] ?? 0),
];

$capUrl = '/capture/' . ($v['slug'] ?: 'draft');
?>

<style>
/* ══ ÉDITEUR CAPTURE ══ */
.capedit-wrap { font-family: var(--font); max-width: 1100px; }
.capedit-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; flex-wrap:wrap; gap:12px; }
.capedit-header-l h2 { font-family:var(--font-display); font-size:1.3rem; font-weight:700; color:var(--text); display:flex; align-items:center; gap:8px; margin:0 0 4px; }
.capedit-header-l p { color:var(--text-2); font-size:.82rem; margin:0; }
.capedit-header-r { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.capedit-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; border-radius:var(--radius); font-size:.82rem; font-weight:600; cursor:pointer; border:none; transition:all .15s; font-family:var(--font); text-decoration:none; }
.capedit-btn-primary { background:#8b5cf6; color:#fff; }
.capedit-btn-primary:hover { background:#7c3aed; color:#fff; transform:translateY(-1px); }
.capedit-btn-outline { background:var(--surface); color:var(--text-2); border:1px solid var(--border); }
.capedit-btn-outline:hover { border-color:var(--border-h); background:var(--surface-2); }
.capedit-btn-preview { background:linear-gradient(135deg,#0ea5e9,#2563eb); color:#fff; }
.capedit-btn-preview:hover { transform:translateY(-1px); color:#fff; }
.capedit-btn-save { background:linear-gradient(135deg,#8b5cf6,#6366f1); color:#fff; box-shadow:0 2px 8px rgba(99,102,241,.3); }
.capedit-btn-save:hover { transform:translateY(-1px); box-shadow:0 4px 16px rgba(99,102,241,.35); color:#fff; }
.capedit-flash { padding:11px 16px; border-radius:var(--radius); font-size:.83rem; font-weight:600; margin-bottom:18px; display:flex; align-items:center; gap:8px; animation:capFI .3s ease; }
.capedit-flash.ok { background:#d1fae5; color:#065f46; border:1px solid rgba(5,150,105,.12); }
.capedit-flash.err{ background:rgba(220,38,38,.06); color:#dc2626; border:1px solid rgba(220,38,38,.12); }
@keyframes capFI { from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)} }
.capedit-grid { display:grid; grid-template-columns:1fr 340px; gap:22px; align-items:start; }
.capedit-main {}
.capedit-side {}

/* ── Cards ── */
.capedit-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; margin-bottom:18px; }
.capedit-card-hd { padding:14px 18px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:8px; background:var(--surface-2); }
.capedit-card-hd h3 { font-size:.85rem; font-weight:700; color:var(--text); margin:0; }
.capedit-card-hd i { color:#8b5cf6; font-size:.8rem; }
.capedit-card-body { padding:20px; }

/* ── Champs ── */
.capedit-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.capedit-field { margin-bottom:16px; }
.capedit-field:last-child { margin-bottom:0; }
.capedit-label { display:block; font-size:.75rem; font-weight:700; color:var(--text-2); margin-bottom:5px; text-transform:uppercase; letter-spacing:.04em; }
.capedit-input, .capedit-textarea, .capedit-select {
    width:100%; padding:9px 12px; background:var(--surface); border:1px solid var(--border);
    border-radius:var(--radius); color:var(--text); font-size:.85rem; font-family:var(--font);
    transition:border-color .15s, box-shadow .15s;
}
.capedit-input:focus, .capedit-textarea:focus, .capedit-select:focus {
    outline:none; border-color:#8b5cf6; box-shadow:0 0 0 3px rgba(139,92,246,.1);
}
.capedit-textarea { resize:vertical; min-height:80px; }
.capedit-input::placeholder, .capedit-textarea::placeholder { color:var(--text-3); }
.capedit-hint { font-size:.7rem; color:var(--text-3); margin-top:4px; }
.capedit-slug-preview { display:inline-flex; align-items:center; gap:5px; background:var(--surface-2); border:1px solid var(--border); border-radius:6px; padding:4px 10px; font-size:.72rem; color:var(--text-2); font-family:var(--mono); margin-top:5px; }

/* Template selector */
.capedit-tpl-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.capedit-tpl-item { padding:10px 12px; border:2px solid var(--border); border-radius:10px; cursor:pointer; transition:all .15s; }
.capedit-tpl-item:hover { border-color:#8b5cf6; background:rgba(139,92,246,.04); }
.capedit-tpl-item.selected { border-color:#8b5cf6; background:rgba(139,92,246,.06); }
.capedit-tpl-item input { display:none; }
.capedit-tpl-name { font-size:.82rem; font-weight:700; color:var(--text); }
.capedit-tpl-desc { font-size:.7rem; color:var(--text-3); margin-top:2px; }
.capedit-tpl-badge { display:inline-block; font-size:.6rem; font-weight:700; padding:1px 7px; border-radius:10px; background:#8b5cf622; color:#8b5cf6; margin-top:3px; }

/* Status toggle */
.capedit-status-row { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-radius:10px; border:1px solid var(--border); background:var(--surface); }
.capedit-status-row.active-bg { background:rgba(5,150,69,.04); border-color:rgba(5,150,69,.2); }
.capedit-toggle { position:relative; display:inline-block; width:40px; height:22px; }
.capedit-toggle input { opacity:0; width:0; height:0; }
.capedit-slider { position:absolute; cursor:pointer; inset:0; background:#cbd5e1; border-radius:22px; transition:.2s; }
.capedit-slider:before { content:''; position:absolute; height:16px; width:16px; left:3px; bottom:3px; background:white; border-radius:50%; transition:.2s; }
.capedit-toggle input:checked + .capedit-slider { background:#059669; }
.capedit-toggle input:checked + .capedit-slider:before { transform:translateX(18px); }
.capedit-status-label { font-size:.82rem; font-weight:700; }
.capedit-status-label.on  { color:#059669; }
.capedit-status-label.off { color:var(--text-3); }

/* Stats mini */
.capedit-stats-mini { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-bottom:18px; }
.capedit-stat-mini { text-align:center; padding:10px 8px; background:var(--surface-2); border-radius:var(--radius); border:1px solid var(--border); }
.capedit-stat-mini .num { font-family:var(--font-display); font-size:1.2rem; font-weight:800; }
.capedit-stat-mini .num.blue { color:var(--accent); }
.capedit-stat-mini .num.green { color:var(--green); }
.capedit-stat-mini .num.amber { color:#f59e0b; }
.capedit-stat-mini .lbl { font-size:.6rem; color:var(--text-3); text-transform:uppercase; letter-spacing:.05em; font-weight:600; margin-top:2px; }

/* Preview iframe */
.capedit-preview-box { border-radius:12px; overflow:hidden; border:1px solid var(--border); background:var(--surface); }
.capedit-preview-bar { display:flex; align-items:center; gap:8px; padding:10px 14px; background:var(--surface-2); border-bottom:1px solid var(--border); }
.capedit-preview-dots { display:flex; gap:4px; }
.capedit-preview-dot { width:10px; height:10px; border-radius:50%; }
.capedit-preview-url { flex:1; padding:5px 10px; background:var(--surface); border:1px solid var(--border); border-radius:6px; font-size:.72rem; font-family:var(--mono); color:var(--text-2); }
.capedit-preview-frame { display:block; width:100%; height:400px; border:none; }

/* Leads récents */
.capedit-lead-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--border); }
.capedit-lead-row:last-child { border-bottom:none; }
.capedit-lead-avatar { width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg,#8b5cf6,#6366f1); color:#fff; display:flex; align-items:center; justify-content:center; font-size:.75rem; font-weight:700; flex-shrink:0; }
.capedit-lead-info { flex:1; min-width:0; }
.capedit-lead-name { font-size:.8rem; font-weight:700; color:var(--text); }
.capedit-lead-email { font-size:.7rem; color:var(--text-3); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.capedit-lead-date { font-size:.65rem; color:var(--text-3); flex-shrink:0; }

@media(max-width:900px) { .capedit-grid { grid-template-columns:1fr; } }
@media(max-width:600px) { .capedit-row { grid-template-columns:1fr; } .capedit-tpl-grid { grid-template-columns:1fr; } }
</style>

<div class="capedit-wrap">

<?php if ($saveOk): ?>
<div class="capedit-flash ok"><i class="fas fa-check-circle"></i> Capture enregistrée avec succès !</div>
<?php endif; ?>
<?php if ($saveError): ?>
<div class="capedit-flash err"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($saveError) ?></div>
<?php endif; ?>

<!-- Header -->
<div class="capedit-header">
    <div class="capedit-header-l">
        <h2>
            <i class="fas fa-magnet" style="color:#8b5cf6"></i>
            <?= $isNew ? 'Nouvelle page de capture' : 'Éditer la capture' ?>
        </h2>
        <p>
            <?php if (!$isNew && $v['slug']): ?>
                URL : <code>/capture/<?= htmlspecialchars($v['slug']) ?></code>
                · <?= $v['vues'] ?> vues · <?= $v['conversions'] ?> leads
            <?php else: ?>
                Configurez et activez votre page de capture
            <?php endif; ?>
        </p>
    </div>
    <div class="capedit-header-r">
        <a href="?page=captures" class="capedit-btn capedit-btn-outline">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
        <?php if (!$isNew && $v['slug']): ?>
        <a href="/capture/<?= htmlspecialchars($v['slug']) ?>" target="_blank" class="capedit-btn capedit-btn-preview">
            <i class="fas fa-eye"></i> Voir la page
        </a>
        <?php endif; ?>
        <button type="submit" form="capeditForm" class="capedit-btn capedit-btn-save">
            <i class="fas fa-save"></i> Enregistrer
        </button>
    </div>
</div>

<form id="capeditForm" method="POST">
<input type="hidden" name="_edit_submit" value="1">

<div class="capedit-grid">

    <!-- ══ COLONNE PRINCIPALE ══ -->
    <div class="capedit-main">

        <!-- Identité -->
        <div class="capedit-card">
            <div class="capedit-card-hd"><i class="fas fa-id-card"></i><h3>Identité de la capture</h3></div>
            <div class="capedit-card-body">
                <div class="capedit-row">
                    <div class="capedit-field">
                        <label class="capedit-label">Titre interne <span style="color:#dc2626">*</span></label>
                        <input type="text" name="titre" class="capedit-input"
                               value="<?= htmlspecialchars($v['titre']) ?>"
                               placeholder="Ex : 💰 Comment fixer le juste prix de vente"
                               oninput="capeditAutoSlug(this.value)" required>
                        <div class="capedit-hint">Nom affiché dans l'admin uniquement</div>
                    </div>
                    <div class="capedit-field">
                        <label class="capedit-label">Slug URL <span style="color:#dc2626">*</span></label>
                        <input type="text" name="slug" id="capeditSlug" class="capedit-input"
                               value="<?= htmlspecialchars($v['slug']) ?>"
                               placeholder="guide-vente-prix"
                               pattern="[a-z0-9-]+" required>
                        <div class="capedit-slug-preview">🔗 /capture/<span id="capeditSlugPreview"><?= htmlspecialchars($v['slug'] ?: '…') ?></span></div>
                    </div>
                </div>
                <div class="capedit-row">
                    <div class="capedit-field">
                        <label class="capedit-label">Type</label>
                        <select name="type" class="capedit-select">
                            <?php foreach ($captureTypes as $key => $ct): ?>
                            <option value="<?= $key ?>" <?= $v['type'] === $key ? 'selected' : '' ?>><?= $ct['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="capedit-field">
                        <label class="capedit-label">URL de remerciement</label>
                        <input type="text" name="page_merci_url" class="capedit-input"
                               value="<?= htmlspecialchars($v['page_merci_url']) ?>"
                               placeholder="/merci?guide=guide-vente-prix">
                        <div class="capedit-hint">Redirect après soumission du formulaire</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenu visible -->
        <div class="capedit-card">
            <div class="capedit-card-hd"><i class="fas fa-heading"></i><h3>Contenu de la page</h3></div>
            <div class="capedit-card-body">
                <div class="capedit-field">
                    <label class="capedit-label">Headline (titre principal) <span style="color:#dc2626">*</span></label>
                    <input type="text" name="headline" class="capedit-input"
                           value="<?= htmlspecialchars($v['headline']) ?>"
                           placeholder="Ex : 📥 Téléchargez gratuitement : Comment fixer le juste prix de vente"
                           oninput="capeditUpdatePreview()">
                    <div class="capedit-hint">Titre H1 visible par le visiteur</div>
                </div>
                <div class="capedit-field">
                    <label class="capedit-label">Sous-titre / accroche</label>
                    <textarea name="sous_titre" class="capedit-textarea"
                              placeholder="Ex : Méthode complète pour ne pas brûler votre bien sur le marché ni laisser d'argent sur la table."
                              oninput="capeditUpdatePreview()"><?= htmlspecialchars($v['sous_titre']) ?></textarea>
                </div>
                <div class="capedit-field">
                    <label class="capedit-label">Description interne (SEO / admin)</label>
                    <textarea name="description" class="capedit-textarea" style="min-height:60px"
                              placeholder="Description courte pour l'admin et les meta-données"><?= htmlspecialchars($v['description']) ?></textarea>
                </div>
                <div class="capedit-field">
                    <label class="capedit-label">Texte du bouton CTA</label>
                    <input type="text" name="cta_text" class="capedit-input"
                           value="<?= htmlspecialchars($v['cta_text']) ?>"
                           placeholder="📥 Recevoir mon guide gratuitement"
                           oninput="capeditUpdatePreview()">
                </div>
            </div>
        </div>

        <!-- Template -->
        <div class="capedit-card">
            <div class="capedit-card-hd"><i class="fas fa-layout"></i><h3>Template d'affichage</h3></div>
            <div class="capedit-card-body">
                <div class="capedit-tpl-grid">
                    <?php foreach ($captureTemplates as $key => $tpl): ?>
                    <label class="capedit-tpl-item <?= $v['template'] === $key ? 'selected' : '' ?>"
                           onclick="document.querySelectorAll('.capedit-tpl-item').forEach(e=>e.classList.remove('selected'));this.classList.add('selected')">
                        <input type="radio" name="template" value="<?= $key ?>" <?= $v['template'] === $key ? 'checked' : '' ?>>
                        <div class="capedit-tpl-name"><?= $tpl['label'] ?></div>
                        <div class="capedit-tpl-desc"><?= $tpl['desc'] ?></div>
                        <?php if ($key === 'split'): ?>
                        <span class="capedit-tpl-badge">★ Recommandé guides</span>
                        <?php endif; ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- ══ COLONNE LATÉRALE ══ -->
    <div class="capedit-side">

        <!-- Statut -->
        <div class="capedit-card">
            <div class="capedit-card-hd"><i class="fas fa-toggle-on"></i><h3>Publication</h3></div>
            <div class="capedit-card-body" style="padding:16px">
                <div class="capedit-status-row <?= $v['status'] === 'active' ? 'active-bg' : '' ?>" id="capeditStatusRow">
                    <div>
                        <div class="capedit-status-label <?= $v['status'] === 'active' ? 'on' : 'off' ?>" id="capeditStatusLabel">
                            <?= $v['status'] === 'active' ? '🟢 Active — page visible' : '🔴 Inactive — page masquée' ?>
                        </div>
                        <div style="font-size:.7rem;color:var(--text-3);margin-top:2px">
                            <?= $v['status'] === 'active' ? 'Les visiteurs peuvent accéder à cette page' : 'La page n\'est pas accessible aux visiteurs' ?>
                        </div>
                    </div>
                    <label class="capedit-toggle">
                        <input type="checkbox" name="status" value="active"
                               id="capeditStatusToggle"
                               <?= $v['status'] === 'active' ? 'checked' : '' ?>
                               onchange="capeditToggleStatus(this)">
                        <span class="capedit-slider"></span>
                    </label>
                </div>

                <?php if (!$isNew && $v['slug']): ?>
                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
                    <div style="font-size:.72rem;color:var(--text-3);margin-bottom:6px;font-weight:600">URL de la page :</div>
                    <div style="display:flex;align-items:center;gap:6px">
                        <code style="font-size:.72rem;color:var(--accent);background:var(--surface-2);padding:4px 8px;border-radius:6px;flex:1;word-break:break-all">/capture/<?= htmlspecialchars($v['slug']) ?></code>
                        <button type="button" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($capUrl) ?>');this.textContent='✅'" style="padding:5px 10px;border:1px solid var(--border);border-radius:6px;background:var(--surface);font-size:.7rem;cursor:pointer;white-space:nowrap">📋 Copier</button>
                    </div>
                    <a href="<?= htmlspecialchars($capUrl) ?>" target="_blank"
                       style="display:flex;align-items:center;gap:5px;margin-top:8px;font-size:.75rem;color:#3b82f6;text-decoration:none;font-weight:600">
                        <i class="fas fa-external-link-alt" style="font-size:.65rem"></i> Ouvrir la page de capture
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats -->
        <?php if (!$isNew): ?>
        <div class="capedit-card">
            <div class="capedit-card-hd"><i class="fas fa-chart-bar"></i><h3>Statistiques</h3></div>
            <div class="capedit-card-body" style="padding:14px">
                <div class="capedit-stats-mini">
                    <div class="capedit-stat-mini">
                        <div class="num blue"><?= number_format($v['vues']) ?></div>
                        <div class="lbl">Vues</div>
                    </div>
                    <div class="capedit-stat-mini">
                        <div class="num green"><?= number_format($v['conversions']) ?></div>
                        <div class="lbl">Leads</div>
                    </div>
                    <div class="capedit-stat-mini">
                        <div class="num amber"><?= $v['taux'] > 0 ? number_format($v['taux'], 1) . '%' : '—' ?></div>
                        <div class="lbl">Conv.</div>
                    </div>
                </div>

                <!-- Derniers leads -->
                <?php
                $recentLeads = [];
                try {
                    $sl = $pdo->prepare("SELECT prenom, email, tel, created_at FROM capture_leads WHERE capture_id = ? ORDER BY created_at DESC LIMIT 5");
                    $sl->execute([$captureId]);
                    $recentLeads = $sl->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {}
                ?>
                <?php if (!empty($recentLeads)): ?>
                <div style="font-size:.7rem;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.05em;margin-bottom:8px">Derniers leads :</div>
                <?php foreach ($recentLeads as $lead): ?>
                <div class="capedit-lead-row">
                    <div class="capedit-lead-avatar"><?= strtoupper(mb_substr($lead['prenom'] ?? '?', 0, 1)) ?></div>
                    <div class="capedit-lead-info">
                        <div class="capedit-lead-name"><?= htmlspecialchars($lead['prenom'] ?? '—') ?></div>
                        <div class="capedit-lead-email"><?= htmlspecialchars($lead['email']) ?></div>
                    </div>
                    <div class="capedit-lead-date"><?= date('d/m', strtotime($lead['created_at'])) ?></div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div style="text-align:center;padding:16px;font-size:.8rem;color:var(--text-3)">
                    <i class="fas fa-inbox" style="font-size:1.5rem;opacity:.2;display:block;margin-bottom:6px"></i>
                    Pas encore de leads
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="capedit-card">
            <div class="capedit-card-hd"><i class="fas fa-bolt"></i><h3>Actions rapides</h3></div>
            <div class="capedit-card-body" style="padding:14px;display:flex;flex-direction:column;gap:8px">
                <button type="submit" form="capeditForm" class="capedit-btn capedit-btn-save" style="justify-content:center">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
                <?php if (!$isNew && $v['slug']): ?>
                <a href="/capture/<?= htmlspecialchars($v['slug']) ?>" target="_blank"
                   class="capedit-btn capedit-btn-preview" style="justify-content:center">
                    <i class="fas fa-eye"></i> Voir la page publique
                </a>
                <?php endif; ?>
                <a href="?page=captures" class="capedit-btn capedit-btn-outline" style="justify-content:center;color:var(--text-2)">
                    <i class="fas fa-list"></i> Toutes les captures
                </a>
                <a href="?page=ressources" class="capedit-btn capedit-btn-outline" style="justify-content:center;color:#8b5cf6">
                    <i class="fas fa-book"></i> Retour aux Ressources
                </a>
            </div>
        </div>

    </div>

</div>
</form>

</div>

<script>
// ── Slug auto depuis titre ──
let slugManuallyEdited = <?= (!$isNew && $v['slug']) ? 'true' : 'false' ?>;

function capeditAutoSlug(titre) {
    if (slugManuallyEdited) return;
    const slug = titre
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .trim()
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .substring(0, 80);
    document.getElementById('capeditSlug').value = slug;
    document.getElementById('capeditSlugPreview').textContent = slug || '…';
}

document.getElementById('capeditSlug').addEventListener('input', function() {
    slugManuallyEdited = true;
    const v = this.value.toLowerCase().replace(/[^a-z0-9-]/g, '');
    this.value = v;
    document.getElementById('capeditSlugPreview').textContent = v || '…';
});

// ── Toggle statut ──
function capeditToggleStatus(checkbox) {
    const row   = document.getElementById('capeditStatusRow');
    const label = document.getElementById('capeditStatusLabel');
    if (checkbox.checked) {
        row.classList.add('active-bg');
        label.textContent   = '🟢 Active — page visible';
        label.className = 'capedit-status-label on';
    } else {
        row.classList.remove('active-bg');
        label.textContent   = '🔴 Inactive — page masquée';
        label.className = 'capedit-status-label off';
    }
}

// ── Flash auto-disparition ──
document.querySelectorAll('.capedit-flash').forEach(el => {
    setTimeout(() => { el.style.transition='opacity .4s'; el.style.opacity='0'; setTimeout(()=>el.remove(),400); }, 5000);
});
</script>