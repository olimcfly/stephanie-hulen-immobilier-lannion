<?php
/**
 * ============================================================
 *  Panneau Admin — Contexte IA Conseiller
 *  Fichier : admin/modules/ai/advisor-context/index.php
 *  Route   : ?page=advisor-context
 * ============================================================
 */
if (!defined('ADMIN_ROUTER')) { http_response_code(403); exit; }

// ── Handler AJAX ────────────────────────────────────────────────────────────
if (!empty($_POST['_ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save') {
            $fields = json_decode($_POST['fields'] ?? '[]', true);
            if (empty($fields) || !is_array($fields)) {
                echo json_encode(['success' => false, 'error' => 'Données invalides']); exit;
            }

            $stmt = $pdo->prepare("
                UPDATE advisor_context
                SET field_value = ?, updated_at = NOW()
                WHERE field_key = ?
            ");
            $count = 0;
            foreach ($fields as $key => $value) {
                $stmt->execute([trim($value), trim($key)]);
                $count += $stmt->rowCount();
            }

            // Invalider le cache AiPromptBuilder
            if (class_exists('AiPromptBuilder')) {
                AiPromptBuilder::clearCache();
            }

            echo json_encode(['success' => true, 'saved' => $count]);
            exit;
        }

        if ($action === 'preview') {
            // Forcer rechargement depuis DB pour la preview
            if (class_exists('AiPromptBuilder')) {
                AiPromptBuilder::clearCache();
            }
            $system = class_exists('AiPromptBuilder')
                ? AiPromptBuilder::context($_POST['module'] ?? 'articles')
                : 'AiPromptBuilder non disponible';

            echo json_encode(['success' => true, 'preview' => $system]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => "Action '$action' inconnue"]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ── Chargement données ───────────────────────────────────────────────────────
$sections = [];
try {
    $stmt = $pdo->query("
        SELECT section, field_key, field_label, field_value, field_type, field_placeholder, sort_order
        FROM advisor_context
        ORDER BY section, sort_order
    ");
    foreach ($stmt->fetchAll() as $row) {
        $sections[$row['section']][] = $row;
    }
} catch (\Throwable $e) {
    $dbError = $e->getMessage();
}

$sectionMeta = [
    'identite'      => ['label' => 'Identité & Coordonnées',     'icon' => 'fas fa-user-circle',  'color' => '#1a4d7a'],
    'metier'        => ['label' => 'Métier & Différenciation',   'icon' => 'fas fa-briefcase',    'color' => '#7c3aed'],
    'marche'        => ['label' => 'Marché Immobilier Local',     'icon' => 'fas fa-chart-line',   'color' => '#059669'],
    'communication' => ['label' => 'Communication & Rédaction',  'icon' => 'fas fa-pen-fancy',    'color' => '#d97706'],
    'personas'      => ['label' => 'Personas & Clients Types',   'icon' => 'fas fa-users',        'color' => '#db2777'],
];

$previewModules = ['articles', 'biens', 'leads', 'seo', 'social', 'gmb', 'captures'];
?>
<style>
/* ── Variables ─────────────────────────────────────────── */
:root{--ac-blue:#1a4d7a;--ac-gold:#d4a574;--ac-bg:#f9f6f3;--radius:10px;--radius-lg:14px;--radius-xl:18px;--border:#e8e4df;--surface:#fff;--surface-2:#f5f2ef;--text:#1a1a2e;--text-2:#4a4a6a;--text-3:#8888aa;--mono:'JetBrains Mono',monospace}

/* ── Layout ─────────────────────────────────────────────── */
.ac-wrap{max-width:960px;margin:0 auto;padding:0 0 60px}
.ac-hero{background:linear-gradient(135deg,#1a4d7a 0%,#2563ab 100%);border-radius:var(--radius-xl);padding:28px 32px;margin-bottom:24px;display:flex;align-items:center;gap:20px;position:relative;overflow:hidden}
.ac-hero::after{content:'';position:absolute;right:-20px;top:-20px;width:200px;height:200px;background:rgba(255,255,255,.04);border-radius:50%;pointer-events:none}
.ac-hero-icon{width:52px;height:52px;background:rgba(255,255,255,.15);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;flex-shrink:0}
.ac-hero-text h1{font-family:var(--font-display,'Playfair Display'),serif;font-size:22px;font-weight:700;color:#fff;margin-bottom:4px}
.ac-hero-text p{font-size:12px;color:rgba(255,255,255,.7);line-height:1.5;max-width:600px}
.ac-hero-badge{margin-left:auto;background:rgba(212,165,116,.25);border:1px solid rgba(212,165,116,.4);border-radius:20px;padding:6px 14px;font-size:10.5px;font-weight:700;color:#d4a574;white-space:nowrap;flex-shrink:0}

/* ── Alert DB error ─────────────────────────────────────── */
.ac-alert{padding:14px 18px;border-radius:var(--radius-lg);margin-bottom:18px;font-size:12px;display:flex;align-items:center;gap:10px}
.ac-alert.err{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
.ac-alert.warn{background:#fef9c3;border:1px solid #fde047;color:#92400e}

/* ── Tabs sections ──────────────────────────────────────── */
.ac-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px}
.ac-tab{padding:7px 16px;border-radius:20px;font-size:11px;font-weight:700;cursor:pointer;border:1px solid var(--border);background:var(--surface);color:var(--text-3);transition:all .15s;display:flex;align-items:center;gap:6px}
.ac-tab:hover,.ac-tab.on{background:var(--ac-blue);color:#fff;border-color:var(--ac-blue)}
.ac-tab i{font-size:10px}

/* ── Section card ──────────────────────────────────────── */
.ac-section{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-xl);margin-bottom:20px;overflow:hidden;display:none}
.ac-section.on{display:block}
.ac-section-hd{padding:16px 24px;display:flex;align-items:center;gap:12px;border-bottom:1px solid var(--border);position:relative;overflow:hidden}
.ac-section-hd::before{content:'';position:absolute;left:0;top:0;bottom:0;width:4px;background:var(--sec-color)}
.ac-section-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:14px;color:#fff;background:var(--sec-color);flex-shrink:0}
.ac-section-title{font-family:var(--font-display,'Playfair Display'),serif;font-size:14px;font-weight:700;color:var(--text)}
.ac-section-sub{font-size:10.5px;color:var(--text-3);margin-top:1px}
.ac-save-btn{margin-left:auto;padding:7px 18px;background:var(--sec-color);color:#fff;border:none;border-radius:var(--radius);font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s;display:flex;align-items:center;gap:6px}
.ac-save-btn:hover{opacity:.88;transform:translateY(-1px)}
.ac-save-btn.saving{opacity:.6;pointer-events:none}

/* ── Fields ─────────────────────────────────────────────── */
.ac-fields{padding:20px 24px;display:grid;gap:16px}
.ac-field{display:grid;gap:6px}
.ac-field label{font-size:10.5px;font-weight:700;color:var(--text-2);text-transform:uppercase;letter-spacing:.05em;display:flex;align-items:center;gap:6px}
.ac-field label span.key{font-family:var(--mono);font-size:9px;padding:1px 6px;background:var(--surface-2);border:1px solid var(--border);border-radius:4px;color:var(--text-3);font-weight:400;text-transform:none;letter-spacing:0}
.ac-field input,.ac-field textarea{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:12px;font-family:inherit;background:var(--surface-2);color:var(--text);transition:all .2s;resize:vertical}
.ac-field input:focus,.ac-field textarea:focus{outline:0;border-color:var(--sec-color,var(--ac-blue));background:#fff;box-shadow:0 0 0 3px rgba(26,77,122,.08)}
.ac-field textarea{min-height:80px}
.ac-field .placeholder-hint{font-size:9.5px;color:var(--text-3);font-style:italic}

/* ── Quality indicator ──────────────────────────────────── */
.ac-quality{display:flex;gap:8px;align-items:center;margin-bottom:20px;padding:14px 20px;background:var(--surface-2);border-radius:var(--radius-lg);border:1px solid var(--border)}
.ac-q-label{font-size:11px;font-weight:700;color:var(--text-2);flex:1}
.ac-q-bar{flex:2;height:6px;background:var(--border);border-radius:3px;overflow:hidden}
.ac-q-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,#f59e0b,#10b981);transition:width .4s}
.ac-q-pct{font-size:11px;font-weight:700;color:var(--text-2);width:35px;text-align:right}
.ac-q-tip{font-size:10px;color:var(--text-3)}

/* ── Preview panel ──────────────────────────────────────── */
.ac-preview-wrap{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-xl);margin-bottom:20px;overflow:hidden}
.ac-preview-hd{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px}
.ac-preview-hd h3{font-size:13px;font-weight:700;flex:1}
.ac-mod-sel{padding:6px 10px;border:1px solid var(--border);border-radius:var(--radius);font-size:11px;font-family:inherit;background:var(--surface-2)}
.ac-prev-btn{padding:6px 14px;background:var(--ac-blue);color:#fff;border:none;border-radius:var(--radius);font-size:11px;font-weight:700;cursor:pointer;font-family:inherit;transition:all .15s}
.ac-prev-btn:hover{opacity:.88}
.ac-preview-body{padding:20px;max-height:400px;overflow-y:auto}
.ac-preview-body pre{font-family:var(--mono);font-size:11px;line-height:1.7;color:#334155;white-space:pre-wrap;word-break:break-word;margin:0}
.ac-preview-empty{text-align:center;padding:30px;color:var(--text-3);font-size:12px}

/* ── Toast ──────────────────────────────────────────────── */
.ac-toast{position:fixed;bottom:20px;right:20px;padding:12px 20px;border-radius:var(--radius-lg);font-size:12px;font-weight:700;z-index:9999;opacity:0;transform:translateY(8px);transition:all .2s;pointer-events:none;color:#fff;display:flex;align-items:center;gap:8px}
.ac-toast.on{opacity:1;transform:translateY(0)}
.ac-toast.ok{background:#059669}.ac-toast.err{background:#dc2626}

/* ── Responsive ─────────────────────────────────────────── */
@media(max-width:600px){.ac-hero{flex-wrap:wrap}.ac-hero-badge{margin-left:0}.ac-fields{padding:14px}}
</style>

<div class="ac-wrap">

<!-- Hero -->
<div class="ac-hero">
    <div class="ac-hero-icon"><i class="fas fa-brain"></i></div>
    <div class="ac-hero-text">
        <h1>Contexte IA Conseiller</h1>
        <p>Ces informations sont injectées automatiquement dans <strong>tous les appels IA</strong> (articles, social media, SEO, emails, CRM…). Plus le profil est complet, plus les contenus générés seront personnalisés et pertinents.</p>
    </div>
    <div class="ac-hero-badge"><i class="fas fa-plug"></i> Actif sur tous les modules</div>
</div>

<?php if (isset($dbError)): ?>
<div class="ac-alert err">
    <i class="fas fa-exclamation-triangle"></i>
    <div><strong>Erreur base de données :</strong> <?= htmlspecialchars($dbError) ?><br>
    <small>Exécutez <code>advisor_context.sql</code> dans phpMyAdmin pour créer la table.</small></div>
</div>
<?php endif; ?>

<!-- Indicateur qualité profil -->
<?php
$totalFields = 0; $filledFields = 0;
foreach ($sections as $secFields) {
    foreach ($secFields as $f) {
        $totalFields++;
        if (!empty(trim($f['field_value'] ?? ''))) $filledFields++;
    }
}
$quality = $totalFields > 0 ? round(($filledFields / $totalFields) * 100) : 0;
$qColor = $quality >= 80 ? '#10b981' : ($quality >= 50 ? '#f59e0b' : '#ef4444');
$qTip = $quality >= 80 ? '✅ Profil complet — l\'IA est bien contextualisée' : ($quality >= 50 ? '⚠️ Profil partiel — complétez pour de meilleurs résultats' : '❌ Profil incomplet — les contenus IA seront génériques');
?>
<div class="ac-quality">
    <span class="ac-q-label">Complétude du profil IA</span>
    <div class="ac-q-bar"><div class="ac-q-fill" style="width:<?= $quality ?>%;background:<?= $qColor ?>"></div></div>
    <span class="ac-q-pct" style="color:<?= $qColor ?>"><?= $quality ?>%</span>
    <span class="ac-q-tip"><?= $qTip ?> (<?= $filledFields ?>/<?= $totalFields ?> champs)</span>
</div>

<!-- Onglets -->
<div class="ac-tabs" id="acTabs">
    <?php foreach ($sectionMeta as $secKey => $secInfo): if (empty($sections[$secKey])) continue; ?>
    <button class="ac-tab<?= $secKey==='identite'?' on':'' ?>" data-sec="<?= $secKey ?>"
            onclick="ACAdmin.showSec('<?= $secKey ?>')">
        <i class="<?= $secInfo['icon'] ?>"></i> <?= $secInfo['label'] ?>
    </button>
    <?php endforeach; ?>
    <button class="ac-tab" data-sec="preview" onclick="ACAdmin.showSec('preview')">
        <i class="fas fa-eye"></i> Aperçu prompt
    </button>
</div>

<!-- Sections formulaires -->
<?php foreach ($sectionMeta as $secKey => $secInfo):
    if (empty($sections[$secKey])) continue;
    $isFirst = ($secKey === 'identite');
?>
<div class="ac-section<?= $isFirst?' on':'' ?>" id="acSec-<?= $secKey ?>"
     style="--sec-color:<?= $secInfo['color'] ?>">
    <div class="ac-section-hd">
        <div class="ac-section-icon"><i class="<?= $secInfo['icon'] ?>"></i></div>
        <div>
            <div class="ac-section-title"><?= $secInfo['label'] ?></div>
            <div class="ac-section-sub"><?= count($sections[$secKey]) ?> champ(s) — modifiez et sauvegardez</div>
        </div>
        <button class="ac-save-btn" id="saveBtn-<?= $secKey ?>"
                onclick="ACAdmin.save('<?= $secKey ?>')">
            <i class="fas fa-save"></i> Sauvegarder
        </button>
    </div>
    <div class="ac-fields" id="acFields-<?= $secKey ?>">
        <?php foreach ($sections[$secKey] as $field): ?>
        <div class="ac-field">
            <label>
                <?= htmlspecialchars($field['field_label']) ?>
                <span class="key"><?= htmlspecialchars($field['field_key']) ?></span>
            </label>
            <?php if ($field['field_type'] === 'textarea'): ?>
            <textarea id="field-<?= htmlspecialchars($field['field_key']) ?>"
                      data-key="<?= htmlspecialchars($field['field_key']) ?>"
                      data-sec="<?= $secKey ?>"
                      placeholder="<?= htmlspecialchars($field['field_placeholder'] ?? '') ?>"><?= htmlspecialchars($field['field_value'] ?? '') ?></textarea>
            <?php else: ?>
            <input type="text"
                   id="field-<?= htmlspecialchars($field['field_key']) ?>"
                   data-key="<?= htmlspecialchars($field['field_key']) ?>"
                   data-sec="<?= $secKey ?>"
                   value="<?= htmlspecialchars($field['field_value'] ?? '') ?>"
                   placeholder="<?= htmlspecialchars($field['field_placeholder'] ?? '') ?>">
            <?php endif; ?>
            <?php if ($field['field_placeholder']): ?>
            <span class="placeholder-hint"><?= htmlspecialchars($field['field_placeholder']) ?></span>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<!-- Aperçu prompt -->
<div class="ac-preview-wrap ac-section" id="acSec-preview">
    <div class="ac-preview-hd">
        <i class="fas fa-eye" style="color:var(--ac-blue)"></i>
        <h3>Aperçu du prompt système généré</h3>
        <select class="ac-mod-sel" id="previewModule">
            <?php foreach ($previewModules as $m): ?>
            <option value="<?= $m ?>"><?= ucfirst($m) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="ac-prev-btn" onclick="ACAdmin.preview()">
            <i class="fas fa-sync"></i> Générer l'aperçu
        </button>
    </div>
    <div class="ac-preview-body" id="previewBody">
        <div class="ac-preview-empty">
            <i class="fas fa-code" style="font-size:28px;opacity:.2;display:block;margin-bottom:8px"></i>
            Cliquez sur "Générer l'aperçu" pour voir le prompt système envoyé à l'IA
        </div>
    </div>
</div>

</div><!-- .ac-wrap -->

<div class="ac-toast" id="acToast"></div>

<script>
const ACAdmin = {
    toast(msg, type='ok') {
        const t = document.getElementById('acToast');
        t.innerHTML = (type === 'ok' ? '<i class="fas fa-check"></i> ' : '<i class="fas fa-times"></i> ') + msg;
        t.className = 'ac-toast on ' + type;
        clearTimeout(t._t);
        t._t = setTimeout(() => t.classList.remove('on'), 3000);
    },

    showSec(key) {
        document.querySelectorAll('.ac-tab').forEach(t => t.classList.toggle('on', t.dataset.sec === key));
        document.querySelectorAll('.ac-section').forEach(s => {
            const id = s.id.replace('acSec-', '');
            s.classList.toggle('on', id === key);
        });
    },

    async save(section) {
        const btn = document.getElementById('saveBtn-' + section);
        const inputs = document.querySelectorAll(`[data-sec="${section}"]`);
        const fields = {};

        inputs.forEach(el => {
            if (el.dataset.key) fields[el.dataset.key] = el.value;
        });

        if (Object.keys(fields).length === 0) {
            this.toast('Aucun champ à sauvegarder', 'err');
            return;
        }

        btn.classList.add('saving');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sauvegarde…';

        try {
            const fd = new FormData();
            fd.append('_ajax', '1');
            fd.append('action', 'save');
            fd.append('fields', JSON.stringify(fields));

            const r = await fetch(location.href, {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: fd
            });
            const d = await r.json();

            if (d.success) {
                this.toast(`✅ ${d.saved || Object.keys(fields).length} champ(s) sauvegardé(s) — contexte IA mis à jour`);
                this._updateQuality();
            } else {
                this.toast(d.error || 'Erreur', 'err');
            }
        } catch(e) {
            this.toast('Erreur réseau : ' + e.message, 'err');
        }

        btn.classList.remove('saving');
        btn.innerHTML = '<i class="fas fa-save"></i> Sauvegarder';
    },

    async preview() {
        const module = document.getElementById('previewModule').value;
        const body = document.getElementById('previewBody');
        body.innerHTML = '<div class="ac-preview-empty"><i class="fas fa-spinner fa-spin" style="font-size:20px;display:block;margin-bottom:8px;opacity:.4"></i>Génération…</div>';

        try {
            const fd = new FormData();
            fd.append('_ajax', '1');
            fd.append('action', 'preview');
            fd.append('module', module);

            const r = await fetch(location.href, {
                method: 'POST',
                headers: {'X-Requested-With': 'XMLHttpRequest'},
                body: fd
            });
            const d = await r.json();

            if (d.success) {
                body.innerHTML = '<pre>' + this._escHtml(d.preview) + '</pre>';
            } else {
                body.innerHTML = '<div class="ac-alert err" style="margin:0"><i class="fas fa-exclamation-triangle"></i>' + this._escHtml(d.error) + '</div>';
            }
        } catch(e) {
            body.innerHTML = '<div class="ac-alert err" style="margin:0"><i class="fas fa-exclamation-triangle"></i>' + e.message + '</div>';
        }
    },

    _escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    },

    _updateQuality() {
        // Recalcule % de champs remplis côté client
        const all = document.querySelectorAll('.ac-fields input, .ac-fields textarea');
        let total = all.length, filled = 0;
        all.forEach(el => { if (el.value.trim()) filled++; });
        const pct = total > 0 ? Math.round((filled / total) * 100) : 0;
        const fill = document.querySelector('.ac-q-fill');
        const pctEl = document.querySelector('.ac-q-pct');
        if (fill) fill.style.width = pct + '%';
        if (pctEl) pctEl.textContent = pct + '%';
    }
};
</script>