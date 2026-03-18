<?php
/**
 * Admin Module: Gestion des Menus v3
 * Chemin : /admin/modules/builder/menus/index.php
 * 
 * Select dynamique pages/articles/secteurs/guides + mode URL libre
 * Inclus par dashboard.php — pas de ob_start/layout.php
 */
?>

<style>
.menus-wrap { max-width: 960px; margin: 0 auto; padding: 24px 20px 60px; }
.menus-header { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; }
.menus-header-icon {
    width: 46px; height: 46px; border-radius: 13px;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff; flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(99,102,241,.3);
}
.menus-header h2 { font-size: 18px; font-weight: 800; color: var(--text, #1e293b); margin: 0; }
.menus-header p { font-size: 12px; color: var(--text-3, #64748b); margin: 2px 0 0; }

.menus-toast {
    padding: 12px 16px; border-radius: 10px; margin-bottom: 16px;
    font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;
    animation: mSlide .25s ease;
}
.menus-toast-success { background: rgba(16,185,129,.1); color: #059669; border: 1px solid rgba(16,185,129,.2); }
.menus-toast-error { background: rgba(239,68,68,.1); color: #dc2626; border: 1px solid rgba(239,68,68,.2); }
@keyframes mSlide { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:none; } }

.menu-card {
    background: var(--surface, #fff); border: 1px solid var(--border, #e2e8f0);
    border-radius: 14px; margin-bottom: 20px; overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,.04); transition: box-shadow .2s;
}
.menu-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); }
.menu-card-hd {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 20px; border-bottom: 1px solid var(--border, #e2e8f0);
    background: var(--surface-2, #f8fafc);
}
.menu-card-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0;
}
.menu-card-label { font-size: 14px; font-weight: 700; color: var(--text, #1e293b); }
.menu-card-count {
    font-size: 11px; font-weight: 600; color: var(--text-3, #94a3b8);
    background: var(--surface, #fff); border: 1px solid var(--border, #e2e8f0);
    padding: 2px 8px; border-radius: 20px; margin-left: auto;
}

.menu-card-items { min-height: 48px; }
.menu-card-empty { padding: 24px 20px; text-align: center; color: var(--text-3, #94a3b8); font-size: 13px; font-style: italic; }

.menu-row {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 20px; border-bottom: 1px solid var(--border, #f1f5f9); transition: background .12s;
}
.menu-row:last-child { border-bottom: none; }
.menu-row:hover { background: rgba(99,102,241,.03); }
.menu-row-drag { color: var(--text-3, #cbd5e1); font-size: 10px; flex-shrink: 0; opacity: .5; }
.menu-row:hover .menu-row-drag { opacity: 1; }
.menu-row-title {
    font-size: 13px; font-weight: 600; color: var(--text, #1e293b);
    flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.menu-row-url {
    font-size: 11.5px; color: var(--text-3, #94a3b8); font-family: 'SF Mono','Fira Code',monospace;
    background: var(--surface-2, #f1f5f9); padding: 3px 8px; border-radius: 5px;
    max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex-shrink: 0;
}
.menu-row-del {
    width: 28px; height: 28px; border-radius: 7px; border: 1px solid transparent;
    background: none; color: var(--text-3, #cbd5e1); font-size: 12px; cursor: pointer;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .15s;
}
.menu-row-del:hover { background: rgba(239,68,68,.08); border-color: rgba(239,68,68,.2); color: #ef4444; }

.menu-card-add {
    padding: 14px 20px; background: var(--surface-2, #f8fafc);
    border-top: 1px solid var(--border, #e2e8f0);
}
.menu-mode-toggle {
    display: inline-flex; gap: 2px; margin-bottom: 10px;
    background: var(--surface, #fff); border: 1px solid var(--border, #e2e8f0);
    border-radius: 8px; padding: 3px;
}
.menu-mode-btn {
    padding: 5px 14px; border: none; border-radius: 6px;
    font-size: 11.5px; font-weight: 600; cursor: pointer;
    background: transparent; color: var(--text-3, #94a3b8);
    transition: all .15s; display: flex; align-items: center; gap: 5px;
}
.menu-mode-btn.active { background: #6366f1; color: #fff; box-shadow: 0 1px 4px rgba(99,102,241,.3); }
.menu-mode-btn:not(.active):hover { color: var(--text, #1e293b); background: var(--surface-2, #f1f5f9); }

.menu-add-row { display: flex; gap: 8px; align-items: center; }
.menu-input, .menu-select {
    flex: 1; padding: 9px 12px; border: 1px solid var(--border, #e2e8f0);
    border-radius: 8px; font-size: 13px; color: var(--text, #1e293b);
    background: var(--surface, #fff); transition: border-color .15s, box-shadow .15s;
    font-family: inherit; min-width: 0;
}
.menu-input:focus, .menu-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }
.menu-input::placeholder { color: var(--text-3, #cbd5e1); }
.menu-select {
    flex: 2; cursor: pointer; appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M2 4l4 4 4-4' stroke='%2394a3b8' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px;
}
.menu-select optgroup { font-weight: 700; color: var(--text, #1e293b); font-size: 12px; }
.menu-input-url { flex: 2; }
.menu-input-icon { flex: 0.7; }
.menu-input-title { flex: 1.2; }

.menu-add-btn {
    width: 38px; height: 38px; border-radius: 10px; border: none;
    background: linear-gradient(135deg, #10b981, #059669); color: #fff;
    font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: transform .15s, box-shadow .15s;
    box-shadow: 0 2px 8px rgba(16,185,129,.3);
}
.menu-add-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(16,185,129,.35); }
.menu-add-btn:active { transform: scale(.95); }

.menu-mode-page, .menu-mode-url { display: none; }
.menu-mode-page.active, .menu-mode-url.active { display: flex; }

/* Loading state */
.menu-select-loading { opacity: .5; pointer-events: none; }

@media(max-width: 640px) {
    .menus-wrap { padding: 16px 0 60px; }
    .menus-header { padding: 0 16px; margin-bottom: 16px; }
    .menu-card { border-radius: 0; border-left: none; border-right: none; margin-bottom: 8px; }
    .menu-card-hd { padding: 14px 16px; }
    .menu-card-add { padding: 12px 16px; }
    .menu-add-row { flex-wrap: wrap; }
    .menu-select, .menu-input { min-width: calc(50% - 8px); }
    .menu-select { flex: 1 1 100%; }
    .menu-row { padding: 12px 16px; gap: 10px; }
    .menu-row-url { display: none; }
}
</style>

<div class="menus-wrap">

    <div class="menus-header">
        <div class="menus-header-icon"><i class="fas fa-list"></i></div>
        <div>
            <h2>Gestion des Menus</h2>
            <p>Sélectionnez une page existante ou saisissez une URL personnalisée</p>
        </div>
    </div>

    <div id="menu-message-container"></div>

<?php
$menuCards = [
    ['slug'=>'header-main',     'menuId'=>1, 'icon'=>'fa-bars',           'color'=>'#6366f1', 'label'=>'Menu Principal (Header)'],
    ['slug'=>'footer-services', 'menuId'=>2, 'icon'=>'fa-cogs',           'color'=>'#0ea5e9', 'label'=>'Services (Footer Col. 1)'],
    ['slug'=>'footer-col2',     'menuId'=>3, 'icon'=>'fa-book',           'color'=>'#10b981', 'label'=>'Ressources (Footer Col. 2)'],
    ['slug'=>'footer-col3',     'menuId'=>4, 'icon'=>'fa-scale-balanced', 'color'=>'#c9913b', 'label'=>'Légal (Footer Bottom)'],
];

foreach ($menuCards as $mc):
    $s = $mc['slug'];
    $p = str_replace('-', '_', $s);
?>
    <div class="menu-card" data-slug="<?= $s ?>" data-mid="<?= $mc['menuId'] ?>">
        <div class="menu-card-hd">
            <div class="menu-card-icon" style="background:<?= $mc['color'] ?>1a;color:<?= $mc['color'] ?>">
                <i class="fas <?= $mc['icon'] ?>"></i>
            </div>
            <span class="menu-card-label"><?= $mc['label'] ?></span>
            <span class="menu-card-count" id="cnt_<?= $p ?>">0</span>
        </div>
        <div class="menu-card-items" id="lst_<?= $p ?>">
            <div class="menu-card-empty">Aucun lien configuré</div>
        </div>
        <div class="menu-card-add">
            <div class="menu-mode-toggle">
                <button class="menu-mode-btn active" onclick="mToggle(this,'page')"><i class="fas fa-file-lines"></i> Page existante</button>
                <button class="menu-mode-btn" onclick="mToggle(this,'url')"><i class="fas fa-link"></i> URL libre</button>
            </div>
            <div class="menu-add-row menu-mode-page active">
                <select class="menu-select" id="sel_<?= $p ?>" onchange="mPick(this)">
                    <option value="">Chargement...</option>
                </select>
                <input type="text" class="menu-input menu-input-title" id="tit_<?= $p ?>" placeholder="Titre (auto-rempli)" />
                <input type="hidden" id="url_<?= $p ?>" />
                <input type="text" class="menu-input menu-input-icon" id="ico_<?= $p ?>" placeholder="Icône" />
                <button class="menu-add-btn" onclick="mAddPage('<?= $s ?>')"><i class="fas fa-plus"></i></button>
            </div>
            <div class="menu-add-row menu-mode-url">
                <input type="text" class="menu-input menu-input-title" id="ftit_<?= $p ?>" placeholder="Titre" />
                <input type="text" class="menu-input menu-input-url" id="furl_<?= $p ?>" placeholder="URL (ex: /acheter)" />
                <input type="text" class="menu-input menu-input-icon" id="fico_<?= $p ?>" placeholder="Icône" />
                <button class="menu-add-btn" onclick="mAddFree('<?= $s ?>')"><i class="fas fa-plus"></i></button>
            </div>
        </div>
    </div>
<?php endforeach; ?>

</div>

<script>
(function() {

    const M = {};
    document.querySelectorAll('.menu-card[data-slug]').forEach(c => {
        const s = c.dataset.slug, p = s.replace(/-/g,'_');
        M[s] = { mid:+c.dataset.mid, sel:'sel_'+p, tit:'tit_'+p, url:'url_'+p, ico:'ico_'+p,
                 ftit:'ftit_'+p, furl:'furl_'+p, fico:'fico_'+p, lst:'lst_'+p, cnt:'cnt_'+p };
    });

    let pages = [];

    /* ── Load pages — via /api/ subfolder (autorisé par htaccess) ── */
    function loadPages() {
        fetch('/admin/modules/builder/menus/api/pages.php')
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(d => {
                if (d.success) { pages = d.data; fillSelects(); }
                else { console.error('API error:', d.message); fallbackSelects(); }
            })
            .catch(e => { console.error('Pages load error:', e); fallbackSelects(); });
    }

    function fillSelects() {
        const groups = {};
        pages.forEach((p,i) => { (groups[p.group] = groups[p.group]||[]).push({...p, idx:i}); });

        let html = '<option value="">— Choisir une page —</option>';
        Object.entries(groups).forEach(([g, items]) => {
            html += `<optgroup label="${h(g)}">`;
            items.forEach(it => { html += `<option value="${it.idx}" data-u="${h(it.url)}" data-l="${h(it.label)}">${h(it.label)}</option>`; });
            html += '</optgroup>';
        });

        Object.values(M).forEach(c => {
            const el = document.getElementById(c.sel);
            if (el) { el.innerHTML = html; el.classList.remove('menu-select-loading'); }
        });
    }

    function fallbackSelects() {
        // Si l'API échoue, mettre un message dans les selects
        const html = '<option value="">⚠ Erreur chargement pages — utilisez URL libre</option>';
        Object.values(M).forEach(c => {
            const el = document.getElementById(c.sel);
            if (el) { el.innerHTML = html; el.classList.remove('menu-select-loading'); }
        });
    }

    window.mPick = function(sel) {
        const card = sel.closest('.menu-card'), s = card.dataset.slug, c = M[s];
        const opt = sel.selectedOptions[0];
        document.getElementById(c.tit).value = opt?.dataset?.l || '';
        document.getElementById(c.url).value = opt?.dataset?.u || '';
    };

    window.mToggle = function(btn, mode) {
        const card = btn.closest('.menu-card');
        card.querySelectorAll('.menu-mode-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        card.querySelector('.menu-mode-page').classList.toggle('active', mode==='page');
        card.querySelector('.menu-mode-url').classList.toggle('active', mode==='url');
    };

    function loadMenus() {
        Object.entries(M).forEach(([s, c]) => {
            fetch(`/admin/api/router.php?module=menus&action=get&id=${c.mid}`)
                .then(r => r.json())
                .then(d => { if(d.success && d.data) render(s, d.data); })
                .catch(e => console.error(e));
        });
    }

    function render(s, menu) {
        const c = M[s], items = menu.items||[], el = document.getElementById(c.lst);
        if (!items.length) {
            el.innerHTML = '<div class="menu-card-empty">Aucun lien configuré</div>';
        } else {
            el.innerHTML = items.map(it => `
                <div class="menu-row">
                    <span class="menu-row-drag"><i class="fas fa-grip-vertical"></i></span>
                    <span class="menu-row-title">${h(it.title)}</span>
                    <span class="menu-row-url">${h(it.url)}</span>
                    <button class="menu-row-del" onclick="mDel(${it.id})"><i class="fas fa-trash-can"></i></button>
                </div>`).join('');
        }
        document.getElementById(c.cnt).textContent = items.length;
    }

    window.mAddPage = function(s) {
        const c = M[s];
        const title = document.getElementById(c.tit).value.trim();
        const url   = document.getElementById(c.url).value.trim();
        const icon  = document.getElementById(c.ico).value.trim();
        if (!title || !url) { toast('Sélectionnez une page','error'); return; }
        doAdd(c.mid, title, url, icon, () => {
            document.getElementById(c.sel).value = '';
            document.getElementById(c.tit).value = '';
            document.getElementById(c.url).value = '';
            document.getElementById(c.ico).value = '';
        });
    };

    window.mAddFree = function(s) {
        const c = M[s];
        const title = document.getElementById(c.ftit).value.trim();
        const url   = document.getElementById(c.furl).value.trim();
        const icon  = document.getElementById(c.fico).value.trim();
        if (!title || !url) { toast('Remplissez titre et URL','error'); return; }
        doAdd(c.mid, title, url, icon, () => {
            document.getElementById(c.ftit).value = '';
            document.getElementById(c.furl).value = '';
            document.getElementById(c.fico).value = '';
        });
    };

    function doAdd(mid, title, url, icon, cb) {
        fetch('/admin/api/router.php?module=menus&action=add_item', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({menu_id:mid, title, url, icon, is_active:1, target:'_self'})
        })
        .then(r=>r.json())
        .then(d => { if(d.success){cb();loadMenus();toast('Lien ajouté','success');} else toast('Erreur: '+(d.message||'?'),'error'); })
        .catch(()=>toast('Erreur réseau','error'));
    }

    window.mDel = function(id) {
        if(!confirm('Supprimer ce lien ?')) return;
        fetch('/admin/api/router.php?module=menus&action=delete_item', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({id})
        })
        .then(r=>r.json())
        .then(d => { if(d.success){loadMenus();toast('Supprimé','success');} })
        .catch(()=>toast('Erreur','error'));
    };

    function toast(msg,type) {
        const c = document.getElementById('menu-message-container');
        c.innerHTML = `<div class="menus-toast menus-toast-${type}"><i class="fas fa-${type==='success'?'check-circle':'exclamation-circle'}"></i> ${h(msg)}</div>`;
        setTimeout(()=>c.innerHTML='',4000);
    }

    function h(t) { const d=document.createElement('div'); d.textContent=t||''; return d.innerHTML; }

    document.querySelectorAll('.menu-input').forEach(i => {
        i.addEventListener('keydown', e => { if(e.key==='Enter') i.closest('.menu-add-row')?.querySelector('.menu-add-btn')?.click(); });
    });

    loadPages();
    loadMenus();

})();
</script>