<?php
/**
 * ============================================================
 *  ArticleWizard — Composant UI Wizard de génération d'articles
 *  Fichier : core/ai/ui/ArticleWizard.php
 *
 *  Produit : EcosystèmeImmo — Plateforme CRM & Marketing Immobilier
 *
 * ─────────────────────────────────────────────────────────────
 *  USAGE depuis edit.php (ou tout autre module) :
 *
 *    // 1. Inclure le composant (une seule fois par page)
 *    require_once CORE_PATH . '/ai/ui/ArticleWizard.php';
 *    ArticleWizard::render();
 *
 *    // 2. Ouvrir le wizard depuis un bouton JS :
 *    openArticleWizard({
 *        title:   document.getElementById('ae5Titre').value,
 *        focusKw: document.getElementById('ae5FocusKw').value,
 *        persona: 'primo'  // optionnel
 *    });
 *
 * ─────────────────────────────────────────────────────────────
 *  Ce composant émet un événement custom JS :
 *    window.addEventListener('articleWizardResult', (e) => {
 *        const { article, provider } = e.detail;
 *        // article.title, article.content, article.meta_title…
 *    });
 *
 * ─────────────────────────────────────────────────────────────
 *  Appel IA : POST /admin/api/ai/generate.php
 *    { module: 'articles', action: 'wizard_generate', ...params }
 *    → ArticlesHandler::handle_wizard_generate()
 *
 * ─────────────────────────────────────────────────────────────
 *  Dépendances :
 *    - Font Awesome 6.x (icônes)
 *    - CSS variables de l'admin global (--ei-primary, etc.)
 *    - Aucun framework JS requis (vanilla ES2022)
 * ============================================================
 */

declare(strict_types=1);

class ArticleWizard
{
    /**
     * Render le composant complet (CSS + HTML + JS).
     * Appeler une seule fois par page.
     *
     * @param array $options
     *   - 'ai_endpoint' : URL de l'endpoint IA (défaut : /admin/api/ai/generate.php)
     *   - 'csrf_token'  : token CSRF (défaut : $_SESSION['csrf_token'])
     *   - 'callback_fn' : nom de la fonction JS appelée après génération (défaut : événement custom)
     */
    public static function render(array $options = []): void
    {
        $aiEndpoint = $options['ai_endpoint'] ?? '/admin/api/ai/generate.php';
        $csrfToken  = $options['csrf_token']  ?? ($_SESSION['csrf_token'] ?? '');
        $callbackFn = $options['callback_fn'] ?? '';
        ?>

<!-- ╔══════════════════════════════════════════════════════════════╗
     ║  ARTICLE WIZARD — core/ai/ui/ArticleWizard.php             ║
     ║  EcosystèmeImmo v5.0                                        ║
     ╚══════════════════════════════════════════════════════════════╝ -->

<style>
/* ════════════════════════════════════════════════════════════
   ARTICLE WIZARD — Dark Theme Premium
   Scope : .agw-* (pas de collision avec le reste de l'admin)
════════════════════════════════════════════════════════════ */
.agw-modal*,.agw-modal*::before,.agw-modal*::after{box-sizing:border-box;margin:0;padding:0}
.agw-modal{
    --c-bg:#0d1117;--c-s1:#161b22;--c-s2:#1c2333;--c-bd:#30363d;--c-bd2:#21262d;
    --c-pu:#8b5cf6;--c-pu2:#a78bfa;--c-pud:#6d28d9;
    --c-bl:#3b82f6;--c-cy:#06b6d4;--c-gr:#10b981;--c-am:#f59e0b;--c-rd:#ef4444;
    --c-t1:#e6edf3;--c-t2:#8b949e;--c-t3:#484f58;
    --r:12px;--rs:8px;
    --glow:0 0 0 3px rgba(139,92,246,.22);
    --shadow:0 20px 80px rgba(0,0,0,.7),0 4px 20px rgba(0,0,0,.4);
}

/* Overlay */
.agw-modal{display:none;position:fixed;inset:0;z-index:99999;
    background:rgba(1,4,9,.88);backdrop-filter:blur(10px) saturate(180%);
    align-items:center;justify-content:center;padding:16px}
.agw-modal.open{display:flex;animation:agwFI .2s ease}
@keyframes agwFI{from{opacity:0}to{opacity:1}}

/* Boîte */
.agw-box{background:var(--c-bg);border:1px solid var(--c-bd);border-radius:18px;
    box-shadow:var(--shadow);width:100%;max-width:700px;max-height:92vh;
    display:flex;flex-direction:column;
    animation:agwSU .3s cubic-bezier(.34,1.56,.64,1)}
@keyframes agwSU{from{transform:translateY(28px) scale(.97);opacity:0}to{transform:none;opacity:1}}

/* Header */
.agw-hdr{padding:22px 26px 0;flex-shrink:0}
.agw-hdr-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:18px}
.agw-brand{display:flex;align-items:center;gap:12px}
.agw-brand-ico{width:44px;height:44px;border-radius:12px;flex-shrink:0;
    background:linear-gradient(135deg,var(--c-pu),var(--c-bl));
    display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;
    box-shadow:0 4px 18px rgba(139,92,246,.45)}
.agw-brand h3{font-size:15px;font-weight:700;color:var(--c-t1);
    font-family:'Inter',-apple-system,sans-serif;letter-spacing:-.01em}
.agw-brand p{font-size:11px;color:var(--c-t2);margin-top:2px;
    font-family:'Inter',-apple-system,sans-serif}
.agw-x{width:32px;height:32px;border-radius:8px;background:transparent;
    border:1px solid var(--c-bd);cursor:pointer;display:flex;align-items:center;
    justify-content:center;color:var(--c-t2);font-size:13px;transition:all .15s;flex-shrink:0}
.agw-x:hover{background:var(--c-rd);color:#fff;border-color:var(--c-rd)}

/* Stepper */
.agw-stepper{display:flex;align-items:center;gap:0;position:relative;padding-bottom:18px}
.agw-step{display:flex;align-items:center;gap:7px;flex:1;cursor:pointer;position:relative}
.agw-step:not(:last-child)::after{content:'';position:absolute;left:24px;top:12px;
    width:calc(100% - 24px);height:2px;background:var(--c-bd2);transition:background .4s;z-index:0}
.agw-step.done::after{background:var(--c-pu)}
.agw-step-dot{width:26px;height:26px;border-radius:50%;background:var(--c-s1);
    border:2px solid var(--c-bd);display:flex;align-items:center;justify-content:center;
    font-size:10px;font-weight:700;color:var(--c-t2);transition:all .25s;flex-shrink:0;z-index:1;
    font-family:'Inter',-apple-system,sans-serif}
.agw-step.active .agw-step-dot{background:var(--c-pu);border-color:var(--c-pu);color:#fff;
    box-shadow:0 0 0 4px rgba(139,92,246,.2)}
.agw-step.done .agw-step-dot{background:var(--c-pu);border-color:var(--c-pu);color:#fff}
.agw-step-lbl{font-size:10px;font-weight:600;color:var(--c-t3);transition:color .25s;white-space:nowrap;
    font-family:'Inter',-apple-system,sans-serif;display:none}
.agw-step.active .agw-step-lbl{color:var(--c-pu2)}
.agw-step.done  .agw-step-lbl{color:var(--c-t2)}
@media(min-width:480px){.agw-step-lbl{display:block}}

/* Body */
.agw-body{flex:1;overflow-y:auto;padding:0 26px 22px;
    scrollbar-width:thin;scrollbar-color:var(--c-bd) transparent}
.agw-body::-webkit-scrollbar{width:4px}
.agw-body::-webkit-scrollbar-thumb{background:var(--c-bd);border-radius:2px}

/* Panels */
.agw-panel{display:none;animation:agwPI .2s ease}
.agw-panel.active{display:block}
@keyframes agwPI{from{opacity:0;transform:translateX(10px)}to{opacity:1;transform:none}}
.agw-ptitle{font-size:17px;font-weight:700;color:var(--c-t1);margin-bottom:4px;
    letter-spacing:-.02em;font-family:'Inter',-apple-system,sans-serif}
.agw-psub{font-size:12px;color:var(--c-t2);margin-bottom:22px;line-height:1.5;
    font-family:'Inter',-apple-system,sans-serif}

/* Champs */
.agw-f{margin-bottom:18px}
.agw-f:last-child{margin-bottom:0}
.agw-lbl{display:flex;align-items:center;justify-content:space-between;margin-bottom:7px}
.agw-lbl-t{font-size:11px;font-weight:600;color:var(--c-t2);text-transform:uppercase;
    letter-spacing:.06em;font-family:'Inter',-apple-system,sans-serif;
    display:flex;align-items:center;gap:5px}
.agw-lbl-t i{color:var(--c-pu2);font-size:10px}
.agw-bdg{font-size:10px;font-weight:600;padding:2px 8px;border-radius:20px;
    font-family:'Inter',-apple-system,sans-serif}
.agw-bdg.req{background:rgba(139,92,246,.15);color:var(--c-pu2);border:1px solid rgba(139,92,246,.25)}
.agw-bdg.opt{background:rgba(139,148,158,.1);color:var(--c-t2);border:1px solid var(--c-bd)}

.agw-input,.agw-select,.agw-textarea{width:100%;padding:11px 14px;
    background:var(--c-s1);color:var(--c-t1);border:1px solid var(--c-bd);
    border-radius:var(--rs);font-size:13px;font-family:'Inter',-apple-system,sans-serif;
    transition:border .15s,box-shadow .15s;outline:none;-webkit-appearance:none}
.agw-input::placeholder,.agw-textarea::placeholder{color:var(--c-t3)}
.agw-input:focus,.agw-select:focus,.agw-textarea:focus{border-color:var(--c-pu);box-shadow:var(--glow)}
.agw-select{cursor:pointer;padding-right:38px;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%238b949e'%3E%3Cpath fill-rule='evenodd' d='M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 10px center;background-size:14px}
.agw-select option{background:var(--c-s1)}
.agw-textarea{resize:vertical;min-height:80px;line-height:1.6}
.agw-hint{font-size:11px;color:var(--c-t3);margin-top:5px;font-family:'Inter',-apple-system,sans-serif}

/* Grid 2 col */
.agw-g2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:480px){.agw-g2{grid-template-columns:1fr}}

/* Tags */
.agw-tags-wrap{background:var(--c-s1);border:1px solid var(--c-bd);border-radius:var(--rs);
    padding:8px 10px;display:flex;flex-wrap:wrap;gap:5px;align-items:center;min-height:44px;
    cursor:text;transition:border .15s,box-shadow .15s}
.agw-tags-wrap:focus-within{border-color:var(--c-pu);box-shadow:var(--glow)}
.agw-tag{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;
    background:rgba(139,92,246,.15);color:var(--c-pu2);border:1px solid rgba(139,92,246,.25);
    font-size:11px;font-weight:600;font-family:'Inter',-apple-system,sans-serif;
    animation:agwTP .15s ease}
@keyframes agwTP{from{transform:scale(.85);opacity:0}to{transform:none;opacity:1}}
.agw-tag-rm{background:none;border:none;cursor:pointer;color:var(--c-pu2);
    padding:0;font-size:10px;opacity:.7;transition:opacity .15s;line-height:1}
.agw-tag-rm:hover{opacity:1}
.agw-tags-inp{flex:1;min-width:100px;background:none;border:none;outline:none;
    color:var(--c-t1);font-size:12px;padding:2px 4px;font-family:'Inter',-apple-system,sans-serif}
.agw-tags-inp::placeholder{color:var(--c-t3)}

/* Pills */
.agw-pills{display:flex;flex-wrap:wrap;gap:7px}
.agw-pill{padding:6px 13px;border-radius:20px;background:var(--c-s1);
    border:1px solid var(--c-bd);font-size:11px;font-weight:600;color:var(--c-t2);
    cursor:pointer;transition:all .18s;font-family:'Inter',-apple-system,sans-serif;white-space:nowrap}
.agw-pill:hover{border-color:var(--c-pu);color:var(--c-pu2)}
.agw-pill.sel{background:rgba(139,92,246,.15);border-color:var(--c-pu);color:var(--c-pu2);
    box-shadow:0 0 0 1px rgba(139,92,246,.15)}

/* Slider */
.agw-slider-wrap{display:flex;flex-direction:column;gap:7px}
.agw-slider-val{font-size:22px;font-weight:800;color:var(--c-pu2);text-align:center;
    font-family:'Inter',-apple-system,sans-serif}
.agw-slider-val span{font-size:12px;font-weight:400;color:var(--c-t2);margin-left:4px}
.agw-slider-lbls{display:flex;justify-content:space-between;font-size:10px;color:var(--c-t3);
    font-family:'Inter',-apple-system,sans-serif}
.agw-range{width:100%;-webkit-appearance:none;appearance:none;height:6px;border-radius:3px;
    outline:none;cursor:pointer;
    background:linear-gradient(to right,var(--c-pu) 0%,var(--c-pu) var(--rp,42%),var(--c-bd2) var(--rp,42%),var(--c-bd2) 100%)}
.agw-range::-webkit-slider-thumb{-webkit-appearance:none;width:20px;height:20px;border-radius:50%;
    background:var(--c-pu2);border:3px solid var(--c-bg);box-shadow:0 0 0 2px var(--c-pu);
    cursor:pointer;transition:transform .15s}
.agw-range::-webkit-slider-thumb:hover{transform:scale(1.15)}

/* Toggles */
.agw-toggles{display:flex;flex-direction:column;gap:8px}
.agw-tog{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;
    border-radius:var(--rs);background:var(--c-s1);border:1px solid var(--c-bd);
    cursor:pointer;transition:all .18s;user-select:none}
.agw-tog:hover{background:var(--c-s2)}
.agw-tog.on{border-color:var(--c-pu);background:rgba(139,92,246,.05)}
.agw-tog-l{display:flex;align-items:center;gap:11px}
.agw-tog-ico{font-size:17px;width:22px;text-align:center}
.agw-tog-info h4{font-size:12px;font-weight:600;color:var(--c-t1);
    font-family:'Inter',-apple-system,sans-serif}
.agw-tog-info p{font-size:10px;color:var(--c-t2);margin-top:1px;
    font-family:'Inter',-apple-system,sans-serif}
.agw-sw{width:38px;height:20px;border-radius:10px;background:var(--c-bd2);
    position:relative;transition:background .2s;flex-shrink:0}
.agw-sw::after{content:'';position:absolute;left:3px;top:3px;width:14px;height:14px;
    border-radius:50%;background:var(--c-t2);transition:all .2s}
.agw-tog.on .agw-sw{background:var(--c-pu)}
.agw-tog.on .agw-sw::after{left:21px;background:#fff}
.agw-tog input{display:none}

/* Info box */
.agw-info{display:flex;gap:9px;align-items:flex-start;padding:11px 13px;border-radius:var(--rs);
    background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.18);
    font-size:11px;color:var(--c-t2);line-height:1.5;margin-bottom:14px;
    font-family:'Inter',-apple-system,sans-serif}
.agw-info i{color:var(--c-bl);font-size:13px;flex-shrink:0;margin-top:1px}

/* Résumé */
.agw-sum{background:var(--c-s1);border:1px solid var(--c-bd);border-radius:var(--rs);
    overflow:hidden;margin-bottom:18px}
.agw-sum-hdr{padding:11px 15px;background:var(--c-s2);border-bottom:1px solid var(--c-bd);
    font-size:11px;font-weight:700;color:var(--c-t2);text-transform:uppercase;
    letter-spacing:.06em;font-family:'Inter',-apple-system,sans-serif;
    display:flex;align-items:center;gap:7px}
.agw-sum-body{padding:14px 15px}
.agw-sum-row{display:flex;align-items:flex-start;gap:10px;padding:5px 0;
    border-bottom:1px solid var(--c-bd2);font-family:'Inter',-apple-system,sans-serif}
.agw-sum-row:last-child{border-bottom:none;padding-bottom:0}
.agw-sum-k{font-size:11px;font-weight:600;color:var(--c-t2);min-width:120px;flex-shrink:0}
.agw-sum-v{font-size:12px;color:var(--c-t1);flex:1;display:flex;flex-wrap:wrap;gap:4px}
.agw-stag{display:inline-flex;align-items:center;padding:1px 7px;border-radius:10px;
    background:rgba(139,92,246,.12);color:var(--c-pu2);font-size:10px;font-weight:600;
    border:1px solid rgba(139,92,246,.18)}
.agw-ston{background:rgba(16,185,129,.1);color:#34d399;border:1px solid rgba(16,185,129,.18);
    display:inline-flex;align-items:center;padding:1px 7px;border-radius:10px;font-size:10px;font-weight:600}
.agw-stoff{background:rgba(139,148,158,.08);color:var(--c-t2);border:1px solid var(--c-bd);
    display:inline-flex;align-items:center;padding:1px 7px;border-radius:10px;font-size:10px;font-weight:600}

/* Progress */
.agw-prog{display:none;margin-top:18px}
.agw-prog.on{display:block}
.agw-prog-bar{height:4px;background:var(--c-bd2);border-radius:2px;overflow:hidden;margin-bottom:12px}
.agw-prog-fill{height:100%;border-radius:2px;
    background:linear-gradient(90deg,var(--c-pu),var(--c-bl));width:0;transition:width .5s ease}
.agw-prog-steps{display:flex;flex-direction:column;gap:7px}
.agw-ps{display:flex;align-items:center;gap:11px;padding:9px 13px;border-radius:var(--rs);
    background:var(--c-s1);border:1px solid var(--c-bd);font-size:12px;color:var(--c-t2);
    font-family:'Inter',-apple-system,sans-serif;transition:all .25s}
.agw-ps.run{border-color:var(--c-pu);color:var(--c-t1);background:rgba(139,92,246,.06)}
.agw-ps.ok {border-color:var(--c-gr); color:var(--c-t1);background:rgba(16,185,129,.06)}
.agw-ps.err{border-color:var(--c-rd); color:var(--c-rd)}
.agw-ps-ico{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;
    justify-content:center;font-size:10px;flex-shrink:0;
    background:var(--c-bd2);color:var(--c-t3);transition:all .25s}
.agw-ps.run .agw-ps-ico{background:var(--c-pu);color:#fff}
.agw-ps.ok  .agw-ps-ico{background:var(--c-gr);color:#fff}
.agw-ps.err .agw-ps-ico{background:var(--c-rd);color:#fff}

/* Footer */
.agw-ftr{padding:14px 26px;border-top:1px solid var(--c-bd);
    display:flex;align-items:center;justify-content:space-between;
    background:var(--c-s1);flex-shrink:0;gap:10px}
.agw-ftr-l{display:flex;align-items:center;gap:7px}
.agw-btn{display:inline-flex;align-items:center;gap:7px;padding:9px 18px;
    border-radius:var(--rs);font-size:12px;font-weight:600;cursor:pointer;
    transition:all .18s;border:none;white-space:nowrap;font-family:'Inter',-apple-system,sans-serif}
.agw-btn-ghost{background:transparent;color:var(--c-t2);border:1px solid var(--c-bd)}
.agw-btn-ghost:hover{background:var(--c-s2);color:var(--c-t1)}
.agw-btn-back{background:var(--c-s2);color:var(--c-t2);border:1px solid var(--c-bd)}
.agw-btn-back:hover{background:var(--c-bd);color:var(--c-t1)}
.agw-btn-next{background:var(--c-pu);color:#fff;box-shadow:0 4px 14px rgba(139,92,246,.35)}
.agw-btn-next:hover{background:var(--c-pud);transform:translateY(-1px);box-shadow:0 6px 20px rgba(139,92,246,.45)}
.agw-btn-next:disabled{opacity:.4;cursor:not-allowed;transform:none;box-shadow:none}
.agw-btn-gen{background:linear-gradient(135deg,var(--c-pu),var(--c-bl));color:#fff;
    box-shadow:0 4px 20px rgba(139,92,246,.4);padding:10px 24px;font-size:13px}
.agw-btn-gen:hover{transform:translateY(-1px);box-shadow:0 6px 28px rgba(139,92,246,.55)}
.agw-btn-gen:disabled{opacity:.5;cursor:not-allowed;transform:none}
.agw-step-n{font-size:11px;color:var(--c-t3);font-family:'Inter',-apple-system,sans-serif}

.agw-divider{height:1px;background:var(--c-bd2);margin:18px 0}
@keyframes agwSpin{to{transform:rotate(360deg)}}
.agw-spin{display:inline-block;animation:agwSpin .7s linear infinite}
</style>

<!-- ══ HTML ═══════════════════════════════════════════════════════════════ -->
<div class="agw-modal" id="agwModal">
<div class="agw-box">

    <div class="agw-hdr">
        <div class="agw-hdr-top">
            <div class="agw-brand">
                <div class="agw-brand-ico"><i class="fas fa-robot"></i></div>
                <div>
                    <h3>Assistant IA — Génération d'article</h3>
                    <p>EcosystèmeImmo · Claude + Perplexity</p>
                </div>
            </div>
            <button class="agw-x" onclick="agwClose()"><i class="fas fa-times"></i></button>
        </div>
        <div class="agw-stepper">
            <?php foreach ([
                ['1','Sujet'],['2','Structure'],['3','Audience'],['4','Lancer']
            ] as [$n, $lbl]): ?>
            <div class="agw-step <?= $n === '1' ? 'active' : '' ?>" id="agwSt<?= $n ?>">
                <div class="agw-step-dot" id="agwDot<?= $n ?>"><?= $n ?></div>
                <span class="agw-step-lbl"><?= $lbl ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="agw-body" id="agwBody">

        <!-- ═══ ÉTAPE 1 — Sujet & Mots-clés ══════════════════════════════ -->
        <div class="agw-panel active" id="agwP1">
            <div class="agw-ptitle">Quel article souhaitez-vous créer ?</div>
            <div class="agw-psub">Décrivez le sujet et les mots-clés cibles pour guider la rédaction IA.</div>

            <div class="agw-f">
                <div class="agw-lbl">
                    <span class="agw-lbl-t"><i class="fas fa-lightbulb"></i> Sujet principal</span>
                    <span class="agw-bdg req">Requis</span>
                </div>
                <textarea class="agw-textarea" id="agwSubject" rows="3"
                    placeholder="Ex : Tout savoir pour vendre son appartement à Bordeaux lors d'une succession — guide complet 2025"></textarea>
            </div>

            <div class="agw-f">
                <div class="agw-lbl">
                    <span class="agw-lbl-t"><i class="fas fa-key"></i> Mot-clé focus (principal)</span>
                    <span class="agw-bdg req">Requis</span>
                </div>
                <input type="text" class="agw-input" id="agwFocusKw"
                    placeholder="Ex : vendre appartement bordeaux succession">
                <div class="agw-hint">Mot-clé optimisé dans le H1, l'intro, les métas et les H2.</div>
            </div>

            <div class="agw-f">
                <div class="agw-lbl">
                    <span class="agw-lbl-t"><i class="fas fa-tags"></i> Mots-clés secondaires</span>
                    <span class="agw-bdg opt">Optionnel</span>
                </div>
                <div class="agw-tags-wrap" id="agwTagsWrap"
                    onclick="document.getElementById('agwTagsInp').focus()">
                    <input class="agw-tags-inp" id="agwTagsInp"
                        placeholder="Tapez un mot-clé + Entrée…">
                </div>
                <div class="agw-hint">Entrez chaque mot-clé et appuyez sur Entrée ou virgule.</div>
            </div>

            <div class="agw-f">
                <div class="agw-lbl">
                    <span class="agw-lbl-t"><i class="fas fa-map-marker-alt"></i> Localisation</span>
                    <span class="agw-bdg opt">Optionnel</span>
                </div>
                <input type="text" class="agw-input" id="agwLocation"
                    placeholder="Bordeaux, Mérignac, Chartrons, Gironde…">
            </div>
        </div>

        <!-- ═══ ÉTAPE 2 — Structure & Format ══════════════════════════════ -->
        <div class="agw-panel" id="agwP2">
            <div class="agw-ptitle">Structure &amp; format de l'article</div>
            <div class="agw-psub">Choisissez le format, la longueur et les éléments à inclure.</div>

            <div class="agw-f">
                <div class="agw-lbl"><span class="agw-lbl-t"><i class="fas fa-file-alt"></i> Type de contenu</span></div>
                <div class="agw-pills" id="agwPillsType">
                    <?php foreach ([
                        ['guide','📘 Guide complet'],['conseils','💡 Conseils pratiques'],
                        ['analyse','📊 Analyse marché'],['quartier','🏘 Focus quartier'],
                        ['juridique','⚖️ Juridique'],['temoignage','💬 Témoignage'],
                        ['checklist','✅ Checklist'],['actualite','📰 Actualité'],
                    ] as [$v,$l]): ?>
                    <div class="agw-pill" data-val="<?= $v ?>"
                        onclick="agwPill('agwPillsType','agwTypeVal',this)"><?= $l ?></div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="agwTypeVal" value="guide">
            </div>

            <div class="agw-f">
                <div class="agw-lbl"><span class="agw-lbl-t"><i class="fas fa-ruler-horizontal"></i> Longueur cible</span></div>
                <div class="agw-slider-wrap">
                    <div class="agw-slider-val" id="agwWordsDsp">1 200 <span>mots</span></div>
                    <input type="range" class="agw-range" id="agwWordsR"
                        min="600" max="3000" step="200" value="1200"
                        oninput="agwSlider(this)">
                    <div class="agw-slider-lbls">
                        <span>600</span><span>Court</span><span>Moyen</span><span>Long</span><span>3 000</span>
                    </div>
                </div>
            </div>

            <div class="agw-divider"></div>

            <div class="agw-f">
                <div class="agw-lbl"><span class="agw-lbl-t"><i class="fas fa-layer-group"></i> Éléments à inclure</span></div>
                <div class="agw-toggles">
                    <?php foreach ([
                        ['Sommaire','agwOptS', true,  '📋','Sommaire ancré',    'Table des matières avec ancres #id vers chaque H2'],
                        ['Faq',    'agwOptF', true,  '❓','FAQ Schema.org',    '5 Q/R structurées — featured snippets Google'],
                        ['Cta',    'agwOptC', true,  '🎯','CTA stratégiques',  'Mid-article + final, adaptés au persona'],
                        ['Links',  'agwOptL', true,  '🔗','Liens externes',    'Sources officielles via Perplexity (service-public, notaires.fr…)'],
                        ['Schema', 'agwOptSc',false, '🏗','Schema.org Article','JSON-LD Article complet pour Google Rich Results'],
                        ['Intern', 'agwOptI', false, '↗', 'Maillage interne',  'Suggestions de liens vers vos autres pages'],
                    ] as [$k,$id,$def,$ico,$h,$sub]): ?>
                    <label class="agw-tog <?= $def ? 'on' : '' ?>" id="agwTog<?= $k ?>">
                        <div class="agw-tog-l">
                            <span class="agw-tog-ico"><?= $ico ?></span>
                            <div class="agw-tog-info"><h4><?= $h ?></h4><p><?= $sub ?></p></div>
                        </div>
                        <div class="agw-sw"></div>
                        <input type="checkbox" id="<?= $id ?>" <?= $def ? 'checked' : '' ?>>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ═══ ÉTAPE 3 — Audience & Stratégie ═══════════════════════════ -->
        <div class="agw-panel" id="agwP3">
            <div class="agw-ptitle">Audience &amp; stratégie éditoriale</div>
            <div class="agw-psub">Ces paramètres affinent le ton, l'angle et les arguments de persuasion.</div>

            <div class="agw-f">
                <div class="agw-lbl">
                    <span class="agw-lbl-t"><i class="fas fa-user-circle"></i> Persona cible</span>
                    <span class="agw-bdg opt">Optionnel</span>
                </div>
                <div class="agw-pills" id="agwPillsP">
                    <?php foreach ([
                        ['primo','🏠 Primo-accédant'],['investisseur','📈 Investisseur'],
                        ['vendeur','🔑 Vendeur'],['divorce','💔 En séparation'],
                        ['succession','⚖️ Succession'],['expatrie','✈️ Expatrié'],
                        ['retraite','🌅 Retraité'],['professionnel','💼 Professionnel'],
                    ] as [$v,$l]): ?>
                    <div class="agw-pill" data-val="<?= $v ?>"
                        onclick="agwPill('agwPillsP','agwPersonaVal',this)"><?= $l ?></div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="agwPersonaVal" value="">
            </div>

            <div class="agw-f">
                <div class="agw-lbl">
                    <span class="agw-lbl-t"><i class="fas fa-brain"></i> Niveau de conscience</span>
                    <span class="agw-bdg opt">Optionnel</span>
                </div>
                <div class="agw-info">
                    <i class="fas fa-info-circle"></i>
                    <span>Détermine l'approche persuasive : <strong>Problème</strong> = éducatif,
                    <strong>Solution</strong> = comparatif, <strong>Décision</strong> = CTA fort.</span>
                </div>
                <div class="agw-pills" id="agwPillsC">
                    <?php foreach ([
                        ['probleme','😕 Problème'],['solution','🔍 Solution'],
                        ['produit','🏢 Produit'],['marque','⭐ Marque'],['decision','✅ Décision'],
                    ] as [$v,$l]): ?>
                    <div class="agw-pill" data-val="<?= $v ?>"
                        onclick="agwPill('agwPillsC','agwConsciVal',this)"><?= $l ?></div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="agwConsciVal" value="">
            </div>

            <div class="agw-f">
                <div class="agw-lbl">
                    <span class="agw-lbl-t"><i class="fas fa-bullseye"></i> Objectif de l'article</span>
                    <span class="agw-bdg opt">Optionnel</span>
                </div>
                <div class="agw-pills" id="agwPillsO">
                    <?php foreach ([
                        ['seo','📈 Trafic SEO'],['lead','🎣 Générer leads'],
                        ['confiance','🤝 Confiance'],['conversion','💰 Convertir'],
                        ['education','🎓 Éduquer'],['local','📍 SEO local'],
                    ] as [$v,$l]): ?>
                    <div class="agw-pill" data-val="<?= $v ?>"
                        onclick="agwPill('agwPillsO','agwObjectifVal',this)"><?= $l ?></div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" id="agwObjectifVal" value="">
            </div>

            <div class="agw-f">
                <div class="agw-lbl"><span class="agw-lbl-t"><i class="fas fa-comment-dots"></i> Ton &amp; angle éditorial</span></div>
                <div class="agw-g2">
                    <select class="agw-select" id="agwTone">
                        <option value="professionnel">🎩 Professionnel &amp; expert</option>
                        <option value="pedagogique">🎓 Pédagogique &amp; clair</option>
                        <option value="empathique">💛 Empathique &amp; rassurant</option>
                        <option value="enthousiaste">⚡ Enthousiaste &amp; dynamique</option>
                        <option value="neutre">📋 Neutre &amp; informatif</option>
                    </select>
                    <select class="agw-select" id="agwAngle">
                        <option value="">— Angle (optionnel) —</option>
                        <option value="avantages">Mettre en avant les avantages</option>
                        <option value="pieges">Alerter sur les pièges</option>
                        <option value="etapes">Dérouler les étapes</option>
                        <option value="comparatif">Comparatif des options</option>
                        <option value="chiffres">Basé sur des données chiffrées</option>
                    </select>
                </div>
            </div>

            <div class="agw-f">
                <div class="agw-lbl">
                    <span class="agw-lbl-t"><i class="fas fa-sticky-note"></i> Instructions spéciales</span>
                    <span class="agw-bdg opt">Optionnel</span>
                </div>
                <textarea class="agw-textarea" id="agwInstr" rows="2"
                    placeholder="Ex : Mentionner la loi Alur, insister sur le rôle du notaire, éviter de citer la concurrence…"></textarea>
            </div>
        </div>

        <!-- ═══ ÉTAPE 4 — Récapitulatif & Lancement ═══════════════════════ -->
        <div class="agw-panel" id="agwP4">
            <div class="agw-ptitle">Récapitulatif &amp; lancement</div>
            <div class="agw-psub">Vérifiez les paramètres puis lancez la génération.</div>

            <div class="agw-sum" id="agwSumBox">
                <div class="agw-sum-hdr"><i class="fas fa-clipboard-list"></i> Récapitulatif</div>
                <div class="agw-sum-body" id="agwSumBody"></div>
            </div>

            <div class="agw-prog" id="agwProgress">
                <div class="agw-prog-bar"><div class="agw-prog-fill" id="agwProgFill"></div></div>
                <div class="agw-prog-steps">
                    <?php foreach ([
                        ['1','fa-search',     'Recherche de sources (Perplexity)'],
                        ['2','fa-sitemap',    'Construction du plan éditorial'],
                        ['3','fa-pen-nib',    'Rédaction de l\'article (Claude IA)'],
                        ['4','fa-search-plus','Optimisation SEO &amp; méta-données'],
                        ['5','fa-check-double','Finalisation FAQ, CTA, Schema'],
                    ] as [$n,$ico,$lbl]): ?>
                    <div class="agw-ps" id="agwPS<?= $n ?>">
                        <div class="agw-ps-ico" id="agwPSI<?= $n ?>">
                            <i class="fas <?= $ico ?>" style="font-size:10px;"></i>
                        </div>
                        <span><?= $lbl ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div><!-- /agw-body -->

    <div class="agw-ftr" id="agwFtr">
        <div class="agw-ftr-l">
            <button class="agw-btn agw-btn-ghost" onclick="agwClose()">Annuler</button>
            <button class="agw-btn agw-btn-back" id="agwBtnBack" onclick="agwBack()" style="display:none">
                <i class="fas fa-arrow-left"></i> Retour
            </button>
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="agw-step-n" id="agwStepN">Étape 1 / 4</span>
            <button class="agw-btn agw-btn-next" id="agwBtnNext" onclick="agwNext()">
                Suivant <i class="fas fa-arrow-right"></i>
            </button>
            <button class="agw-btn agw-btn-gen" id="agwBtnGen" onclick="agwLaunch()"
                style="display:none">
                <i class="fas fa-magic"></i> Générer l'article
            </button>
        </div>
    </div>

</div><!-- /agw-box -->
</div><!-- /agw-modal -->

<!-- ══ JAVASCRIPT ════════════════════════════════════════════════════════ -->
<script>
(function(){
'use strict';

/* ── Config ──────────────────────────────────────────────────────────── */
const EP   = <?= json_encode($aiEndpoint) ?>;
const CSRF = <?= json_encode($csrfToken)  ?>;
const CB   = <?= json_encode($callbackFn) ?>;  /* '' = événement custom */

/* ── État ────────────────────────────────────────────────────────────── */
let step = 1;
const STEPS = 4;
const st = {                  /* valeurs collectées */
    subject:'', focusKw:'', secKw:[], location:'',
    type:'guide', wordCount:1200,
    optS:true, optF:true, optC:true, optL:true, optSc:false, optI:false,
    persona:'', consci:'', objectif:'', tone:'professionnel', angle:'', instr:'',
};
let _tags = [];

/* ── Ouvrir ──────────────────────────────────────────────────────────── */
window.openArticleWizard = function(opts = {}) {
    if (opts.title)   g('agwSubject').value  = opts.title;
    if (opts.focusKw) g('agwFocusKw').value  = opts.focusKw;
    if (opts.persona) {
        const p = document.querySelector('#agwPillsP [data-val="'+opts.persona+'"]');
        if (p) agwPill('agwPillsP','agwPersonaVal', p);
    }
    goStep(1);
    resetProg();
    g('agwBtnGen').style.display  = 'none';
    g('agwBtnNext').style.display = 'inline-flex';
    g('agwModal').classList.add('open');
    document.body.style.overflow = 'hidden';
};
window.agwClose = function() {
    g('agwModal').classList.remove('open');
    document.body.style.overflow = '';
};
g('agwModal').addEventListener('click', e => { if(e.target === g('agwModal')) agwClose(); });

/* ── Navigation ──────────────────────────────────────────────────────── */
window.agwNext = function() {
    if (!validate()) return;
    collect();
    if (step < STEPS) goStep(step + 1);
};
window.agwBack = function() { if (step > 1) { collect(); goStep(step - 1); } };

function goStep(n) {
    document.querySelectorAll('.agw-panel').forEach(p => p.classList.remove('active'));
    g('agwP' + n).classList.add('active');

    for (let i = 1; i <= STEPS; i++) {
        const st2 = g('agwSt'+i), dot = g('agwDot'+i);
        st2.className = 'agw-step' + (i < n ? ' done' : i === n ? ' active' : '');
        dot.innerHTML = i < n ? '<i class="fas fa-check" style="font-size:9px;"></i>' : i;
    }

    g('agwBtnBack').style.display  = n > 1     ? 'inline-flex' : 'none';
    g('agwBtnNext').style.display  = n < STEPS ? 'inline-flex' : 'none';
    g('agwBtnGen').style.display   = n === STEPS ? 'inline-flex' : 'none';
    g('agwStepN').textContent = 'Étape ' + n + ' / ' + STEPS;

    if (n === STEPS) buildSummary();
    step = n;
    g('agwBody').scrollTop = 0;
}

/* ── Validation ──────────────────────────────────────────────────────── */
function validate() {
    if (step === 1) {
        if (!g('agwSubject').value.trim()) { shake('agwSubject'); return false; }
        if (!g('agwFocusKw').value.trim()) { shake('agwFocusKw'); return false; }
    }
    return true;
}
function shake(id) {
    const el = g(id); if (!el) return;
    el.style.borderColor = 'var(--c-rd)';
    el.style.boxShadow   = '0 0 0 3px rgba(239,68,68,.2)';
    el.focus();
    setTimeout(() => { el.style.borderColor = ''; el.style.boxShadow = ''; }, 2500);
}

/* ── Collecte ────────────────────────────────────────────────────────── */
function collect() {
    if (step === 1) {
        st.subject  = g('agwSubject').value.trim();
        st.focusKw  = g('agwFocusKw').value.trim();
        st.secKw    = [..._tags];
        st.location = g('agwLocation').value.trim();
    }
    if (step === 2) {
        st.type      = g('agwTypeVal').value;
        st.wordCount = parseInt(g('agwWordsR').value);
        st.optS  = g('agwOptS').checked;
        st.optF  = g('agwOptF').checked;
        st.optC  = g('agwOptC').checked;
        st.optL  = g('agwOptL').checked;
        st.optSc = g('agwOptSc').checked;
        st.optI  = g('agwOptI').checked;
    }
    if (step === 3) {
        st.persona  = g('agwPersonaVal').value;
        st.consci   = g('agwConsciVal').value;
        st.objectif = g('agwObjectifVal').value;
        st.tone     = g('agwTone').value;
        st.angle    = g('agwAngle').value;
        st.instr    = g('agwInstr').value.trim();
    }
}

/* ── Résumé ──────────────────────────────────────────────────────────── */
function buildSummary() {
    collect();
    const opts = [];
    if (st.optS)  opts.push('📋 Sommaire');
    if (st.optF)  opts.push('❓ FAQ Schema.org');
    if (st.optC)  opts.push('🎯 CTA');
    if (st.optL)  opts.push('🔗 Liens ext.');
    if (st.optSc) opts.push('🏗 Schema Article');
    if (st.optI)  opts.push('↗ Maillage');

    const row = (k, v) => `<div class="agw-sum-row">
        <div class="agw-sum-k">${k}</div>
        <div class="agw-sum-v">${v}</div></div>`;
    const tag = v => v ? `<span class="agw-stag">${v}</span>` : '<span style="color:var(--c-t3)">—</span>';
    const on  = v => `<span class="agw-ston">✓ ${v}</span>`;

    const tl = {guide:'Guide',conseils:'Conseils',analyse:'Analyse',quartier:'Quartier',
                 juridique:'Juridique',temoignage:'Témoignage',checklist:'Checklist',actualite:'Actualité'};
    const pl = {primo:'Primo',investisseur:'Investisseur',vendeur:'Vendeur',divorce:'Séparation',
                 succession:'Succession',expatrie:'Expatrié',retraite:'Retraité',professionnel:'Pro'};
    const cl = {probleme:'Problème',solution:'Solution',produit:'Produit',marque:'Marque',decision:'Décision'};
    const ol = {seo:'SEO',lead:'Leads',confiance:'Confiance',conversion:'Conversion',education:'Éducation',local:'Local'};

    g('agwSumBody').innerHTML =
        row('Sujet',       tag(st.subject.substring(0,75) + (st.subject.length>75?'…':'')))
      + row('Mot-clé',     tag(st.focusKw))
      + (st.secKw.length  ? row('Sec. keywords', st.secKw.map(tag).join('')) : '')
      + (st.location      ? row('Localisation',  tag(st.location)) : '')
      + row('Type',        tag(tl[st.type] || st.type))
      + row('Longueur',    tag(st.wordCount.toLocaleString('fr-FR') + ' mots'))
      + row('Éléments',    opts.length ? opts.map(on).join('') : '<span style="color:var(--c-t3)">Aucun</span>')
      + (st.persona  ? row('Persona',   tag(pl[st.persona]  || st.persona))  : '')
      + (st.consci   ? row('Conscience',tag(cl[st.consci]   || st.consci))   : '')
      + (st.objectif ? row('Objectif',  tag(ol[st.objectif] || st.objectif)) : '')
      + row('Ton',         tag(st.tone))
      + (st.angle ? row('Angle', tag(st.angle)) : '')
      + (st.instr ? row('Instructions', tag(st.instr.substring(0,55)+'…')) : '');
}

/* ── Génération ──────────────────────────────────────────────────────── */
window.agwLaunch = async function() {
    collect();
    const btnG = g('agwBtnGen'), btnB = g('agwBtnBack');
    btnG.disabled = true;
    btnG.innerHTML = '<i class="fas fa-spinner agw-spin"></i> Génération…';
    btnB.style.display = 'none';
    g('agwProgress').classList.add('on');
    g('agwSumBox').style.display = 'none';
    resetProg();

    const payload = {
        csrf_token: CSRF, module: 'articles', action: 'wizard_generate',
        subject: st.subject, keyword: st.focusKw,
        secondary_keywords: st.secKw.join(', '),
        location: st.location, type: st.type, word_count: st.wordCount,
        include_sommaire: st.optS, include_faq: st.optF, include_cta: st.optC,
        include_links: st.optL, include_schema: st.optSc, include_internal: st.optI,
        persona: st.persona, consciousness: st.consci, objectif: st.objectif,
        tone: st.tone, angle: st.angle, instructions: st.instr,
    };

    try {
        /* Étape 1 — Sources Perplexity */
        ps(1,'run'); await sleep(300);
        /* (les sources sont gérées côté serveur dans ArticlesHandler) */
        ps(1,'ok'); prog(20);

        /* Étape 2 — Plan */
        ps(2,'run'); await sleep(300);
        ps(2,'ok'); prog(40);

        /* Étape 3 — Rédaction (appel principal) */
        ps(3,'run');
        const r = await fetch(EP, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload),
        });
        if (!r.ok) throw new Error('HTTP ' + r.status);
        const data = await r.json();
        if (!data.success) throw new Error(data.error || 'Erreur IA');
        ps(3,'ok'); prog(75);

        /* Étape 4 — SEO */
        ps(4,'run'); await sleep(200);
        ps(4,'ok'); prog(90);

        /* Étape 5 — Finalisation */
        ps(5,'run'); await sleep(200);
        ps(5,'ok'); prog(100);

        await sleep(500);

        /* ── Appliquer ───────────────────────────────────────────── */
        const art = data.article || data;

        /* Événement custom (éditor peut écouter) */
        window.dispatchEvent(new CustomEvent('articleWizardResult', {
            detail: { article: art, provider: data.provider || '' }
        }));

        /* Callback nommé si défini */
        if (CB && typeof window[CB] === 'function') window[CB](art, data);

        agwClose();
        const toastFn = window.toast || window.showToast;
        if (toastFn) toastFn('✅ Article généré avec succès !', 'ai', 5000);

    } catch(err) {
        console.error('AGW:', err);
        for (let i = 1; i <= 5; i++) {
            if (g('agwPS'+i)?.classList.contains('run')) { ps(i,'err'); break; }
        }
        btnG.disabled = false;
        btnG.innerHTML = '<i class="fas fa-magic"></i> Réessayer';
        btnB.style.display = 'inline-flex';
        const tf = window.toast || window.showToast;
        if (tf) tf('❌ ' + err.message, 'error', 6000);
    }
};

/* ── Tags ────────────────────────────────────────────────────────────── */
g('agwTagsInp').addEventListener('keydown', function(e) {
    if ((e.key==='Enter'||e.key===',') && this.value.trim()) {
        e.preventDefault(); addTag(this.value.replace(',','').trim()); this.value='';
    }
    if (e.key==='Backspace' && !this.value && _tags.length) rmTag(_tags.length-1);
});
function addTag(v) {
    if (!v||_tags.includes(v)) return; _tags.push(v);
    const el = document.createElement('div');
    el.className = 'agw-tag'; el.dataset.idx = _tags.length-1;
    const i = _tags.length-1;
    el.innerHTML = v+'<button class="agw-tag-rm" onclick="rmTag('+i+')"><i class="fas fa-times"></i></button>';
    g('agwTagsWrap').insertBefore(el, g('agwTagsInp'));
}
window.rmTag = function(idx) {
    _tags.splice(idx,1);
    g('agwTagsWrap').querySelectorAll('.agw-tag').forEach(t=>t.remove());
    _tags.forEach((v,i) => {
        const el = document.createElement('div'); el.className='agw-tag';
        el.innerHTML=v+'<button class="agw-tag-rm" onclick="rmTag('+i+')"><i class="fas fa-times"></i></button>';
        g('agwTagsWrap').insertBefore(el,g('agwTagsInp'));
    });
};

/* ── Pills ───────────────────────────────────────────────────────────── */
window.agwPill = function(groupId, hiddenId, pill) {
    document.querySelectorAll('#'+groupId+' .agw-pill').forEach(p=>p.classList.remove('sel'));
    pill.classList.add('sel');
    g(hiddenId).value = pill.dataset.val;
};
/* Activer le premier type par défaut */
document.querySelector('#agwPillsType .agw-pill')?.classList.add('sel');

/* ── Toggles ─────────────────────────────────────────────────────────── */
document.querySelectorAll('.agw-tog').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target.classList.contains('agw-tag-rm')) return;
        const cb = this.querySelector('input[type="checkbox"]');
        if (!cb) return;
        cb.checked = !cb.checked;
        this.classList.toggle('on', cb.checked);
    });
});

/* ── Slider ──────────────────────────────────────────────────────────── */
window.agwSlider = function(el) {
    const v = parseInt(el.value), mn = parseInt(el.min), mx = parseInt(el.max);
    const pct = ((v-mn)/(mx-mn))*100;
    el.style.setProperty('--rp', pct+'%');
    g('agwWordsDsp').innerHTML = v.toLocaleString('fr-FR')+'<span> mots</span>';
};
agwSlider(g('agwWordsR'));

/* ── Progress ────────────────────────────────────────────────────────── */
function prog(p) { g('agwProgFill').style.width = p+'%'; }
function ps(n, s) {
    const el = g('agwPS'+n), ico = g('agwPSI'+n); if(!el||!ico) return;
    el.className = 'agw-ps ' + s;
    const icons = ['fa-search','fa-sitemap','fa-pen-nib','fa-search-plus','fa-check-double'];
    if (s==='run') ico.innerHTML = '<i class="fas fa-spinner agw-spin" style="font-size:9px;"></i>';
    if (s==='ok')  ico.innerHTML = '<i class="fas fa-check" style="font-size:9px;"></i>';
    if (s==='err') ico.innerHTML = '<i class="fas fa-times" style="font-size:9px;"></i>';
}
function resetProg() {
    prog(0);
    ['fa-search','fa-sitemap','fa-pen-nib','fa-search-plus','fa-check-double'].forEach((ico,i) => {
        const el=g('agwPS'+(i+1)), ic=g('agwPSI'+(i+1));
        if(el) el.className='agw-ps';
        if(ic) ic.innerHTML=`<i class="fas ${ico}" style="font-size:10px;"></i>`;
    });
    g('agwProgress').classList.remove('on');
    g('agwSumBox').style.display='';
}

/* ── Utils ───────────────────────────────────────────────────────────── */
function g(id) { return document.getElementById(id); }
const sleep = ms => new Promise(r => setTimeout(r, ms));

/* ── Écoute dans edit.php — appliquer le résultat automatiquement ──── */
window.addEventListener('articleWizardResult', function(e) {
    const art = e.detail.article;
    const sv = (id, v) => { const el = g(id); if(el && v) { el.value=v; el.dispatchEvent(new Event('input')); }};

    sv('ae5Titre',  art.title);
    sv('ae5Slug',   art.slug);
    sv('ae5Extrait',art.excerpt);
    sv('ae5SeoTitle', art.meta_title);
    sv('ae5SeoDesc',  art.meta_description);
    sv('ae5MetaTitle',art.meta_title);
    sv('ae5MetaDesc', art.meta_description);
    sv('ae5FocusKw',  art.focus_keyword || art.primary_keyword);
    sv('ae5SecKw',    art.secondary_keywords);

    if (art.slug) { const p = g('ae5SlugPreview'); if(p) p.textContent = art.slug; }

    if (art.content && typeof quill !== 'undefined') {
        quill.clipboard.dangerouslyPasteHTML(art.content);
        sv('ae5Contenu', art.content);
    }

    if (typeof updateSerp   === 'function') updateSerp();
    if (typeof calcSeoScore === 'function') calcSeoScore();
});

console.log('🧙 ArticleWizard chargé — core/ai/ui/ — EcosystèmeImmo');
})();
</script>

<?php
    }
}