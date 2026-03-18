<?php
/**
 * Gestion des Prompts IA — /admin/modules/ai-prompts/index.php
 */

if (!isset($pdo) && !isset($db)) {
    try {
        $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    } catch (PDOException $e) {
        echo '<div class="mod-flash mod-flash-error"><i class="fas fa-exclamation-circle"></i> '.$e->getMessage().'</div>';
        return;
    }
}
if (isset($db) && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db)) $db = $pdo;

try { $pdo->query("SELECT 1 FROM ai_prompts LIMIT 1"); } catch (PDOException $e) {
    $sqlFile = __DIR__ . '/sql/install.sql';
    if (file_exists($sqlFile)) {
        $stmts = array_filter(array_map('trim', explode(';', file_get_contents($sqlFile))), fn($s) => !empty($s) && stripos(trim($s),'--') !== 0);
        foreach ($stmts as $s) { try { $pdo->exec($s); } catch (PDOException $ex) {} }
    }
}

$prompts = [];
try {
    $stmt = $pdo->query("SELECT *, LENGTH(system_prompt) AS prompt_length FROM ai_prompts ORDER BY category, is_default DESC, name");
    $prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$categories = [
    'landing' => ['label' => 'Landing Page', 'icon' => 'fa-rocket', 'color' => '#f59e0b'],
    'article' => ['label' => 'Article', 'icon' => 'fa-newspaper', 'color' => '#3b82f6'],
    'secteur' => ['label' => 'Secteur', 'icon' => 'fa-map-marker-alt', 'color' => '#10b981'],
    'capture' => ['label' => 'Capture', 'icon' => 'fa-magnet', 'color' => '#ef4444'],
    'header'  => ['label' => 'Header', 'icon' => 'fa-window-maximize', 'color' => '#8b5cf6'],
    'footer'  => ['label' => 'Footer', 'icon' => 'fa-window-minimize', 'color' => '#6366f1'],
    'email'   => ['label' => 'Email', 'icon' => 'fa-envelope', 'color' => '#ec4899'],
    'general' => ['label' => 'Général', 'icon' => 'fa-wand-magic-sparkles', 'color' => '#64748b'],
];

$totalPrompts = count($prompts);
$activePrompts = count(array_filter($prompts, fn($p) => $p['is_active']));
$totalUsage = array_sum(array_column($prompts, 'usage_count'));
?>

<style>
.prm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(370px,1fr));gap:14px}
.prm-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;transition:all .2s}
.prm-card:hover{box-shadow:var(--shadow);transform:translateY(-2px)}
.prm-card.inactive{opacity:.55}
.prm-top{display:flex;align-items:center;gap:10px;padding:14px 16px;border-bottom:1px solid var(--surface-2)}
.prm-cat{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:13px;flex-shrink:0}
.prm-meta{flex:1;min-width:0}
.prm-meta h3{font-size:.85rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text)}
.prm-meta .prm-slug{font-size:.65rem;color:var(--text-3);font-family:var(--mono)}
.prm-badges{display:flex;gap:4px}
.prm-badge{padding:2px 7px;border-radius:10px;font-size:.6rem;font-weight:700}
.prm-badge-default{background:var(--amber-bg);color:var(--amber)}
.prm-badge-off{background:var(--red-bg);color:var(--red)}
.prm-body{padding:12px 16px}
.prm-desc{font-size:.75rem;color:var(--text-3);line-height:1.5;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.prm-stats{display:flex;gap:14px;font-size:.65rem;color:var(--text-3)}
.prm-stats span{display:flex;align-items:center;gap:3px}
.prm-stats i{font-size:.6rem}
.prm-actions{display:flex;border-top:1px solid var(--surface-2)}
.prm-actions button{flex:1;padding:9px;border:none;background:transparent;cursor:pointer;font-size:.7rem;font-weight:600;color:var(--text-3);font-family:var(--font);transition:all .15s;display:flex;align-items:center;justify-content:center;gap:4px}
.prm-actions button:hover{background:var(--surface-2);color:var(--accent)}
.prm-actions button:not(:last-child){border-right:1px solid var(--surface-2)}
.prm-actions button.del:hover{color:var(--red)}
.prm-filters{display:flex;align-items:center;gap:8px;margin-bottom:18px;flex-wrap:wrap}
.prm-fbtn{padding:6px 14px;border:1px solid var(--border);border-radius:20px;background:var(--surface);font-size:.7rem;font-weight:600;cursor:pointer;color:var(--text-3);display:flex;align-items:center;gap:4px;transition:all .15s;font-family:var(--font)}
.prm-fbtn:hover{border-color:var(--accent);color:var(--accent)}
.prm-fbtn.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.prm-search{flex:1;min-width:180px;padding:7px 12px;border:1px solid var(--border);border-radius:var(--radius);font-size:.78rem;font-family:var(--font);background:var(--surface)}
.prm-search:focus{outline:0;border-color:var(--accent)}
.prm-char{font-size:.65rem;color:var(--text-3);text-align:right}
.prm-hint{font-size:.65rem;color:var(--text-3);margin-top:2px}
@media(max-width:768px){.prm-grid{grid-template-columns:1fr}}
</style>

<div class="mod-hero">
    <div class="mod-hero-content">
        <h1><i class="fas fa-wand-magic-sparkles"></i> Gestion des Prompts IA</h1>
        <p>Configurez les prompts système utilisés par le Builder IA pour générer les designs</p>
    </div>
    <div class="mod-hero-actions">
        <button class="mod-btn mod-btn-hero" onclick="openModal()"><i class="fas fa-plus"></i> Nouveau prompt</button>
    </div>
</div>

<div class="mod-toolbar">
    <div class="mod-toolbar-left mod-flex mod-gap">
        <div class="mod-stat"><div class="mod-stat-value"><?= $totalPrompts ?></div><div class="mod-stat-label">Total</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= $activePrompts ?></div><div class="mod-stat-label">Actifs</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= $totalUsage ?></div><div class="mod-stat-label">Utilisations</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= count($categories) ?></div><div class="mod-stat-label">Catégories</div></div>
    </div>
</div>

<div class="prm-filters">
    <button class="prm-fbtn active" onclick="filterCat('')" data-filter=""><i class="fas fa-th-large"></i> Tous</button>
    <?php foreach ($categories as $ck => $cv): ?>
    <button class="prm-fbtn" onclick="filterCat('<?= $ck ?>')" data-filter="<?= $ck ?>"><i class="fas <?= $cv['icon'] ?>"></i> <?= $cv['label'] ?></button>
    <?php endforeach; ?>
    <input type="text" class="prm-search" id="searchInput" placeholder="Rechercher un prompt..." oninput="filterSearch()">
</div>

<div class="prm-grid" id="promptsGrid">
    <?php foreach ($prompts as $p):
        $cat = $categories[$p['category']] ?? $categories['general'];
        $tags = json_decode($p['tags'] ?? '[]', true) ?: [];
    ?>
    <div class="prm-card <?= !$p['is_active'] ? 'inactive' : '' ?>" data-category="<?= $p['category'] ?>" data-name="<?= strtolower($p['name']) ?>" data-id="<?= $p['id'] ?>">
        <div class="prm-top">
            <div class="prm-cat" style="background:<?= $cat['color'] ?>"><i class="fas <?= $cat['icon'] ?>"></i></div>
            <div class="prm-meta">
                <h3><?= htmlspecialchars($p['name']) ?></h3>
                <div class="prm-slug"><?= htmlspecialchars($p['slug']) ?></div>
            </div>
            <div class="prm-badges">
                <?php if ($p['is_default']): ?><span class="prm-badge prm-badge-default">★ Défaut</span><?php endif; ?>
                <?php if (!$p['is_active']): ?><span class="prm-badge prm-badge-off">Inactif</span><?php endif; ?>
            </div>
        </div>
        <div class="prm-body">
            <div class="prm-desc"><?= htmlspecialchars($p['description'] ?? 'Aucune description') ?></div>
            <div class="prm-stats">
                <span><i class="fas fa-text-width"></i> <?= number_format($p['prompt_length']) ?> car.</span>
                <span><i class="fas fa-bolt"></i> <?= (int)$p['usage_count'] ?> utilisations</span>
                <span><i class="fas fa-microchip"></i> <?= htmlspecialchars($p['model'] ?? 'sonnet') ?></span>
                <span><i class="fas fa-thermometer-half"></i> T=<?= $p['temperature'] ?></span>
            </div>
        </div>
        <div class="prm-actions">
            <button onclick="editPrompt(<?= $p['id'] ?>)"><i class="fas fa-edit"></i> Modifier</button>
            <button onclick="duplicatePrompt(<?= $p['id'] ?>)"><i class="fas fa-copy"></i> Dupliquer</button>
            <button onclick="toggleActive(<?= $p['id'] ?>, <?= $p['is_active'] ? 0 : 1 ?>)">
                <i class="fas <?= $p['is_active'] ? 'fa-eye-slash' : 'fa-eye' ?>"></i> <?= $p['is_active'] ? 'Off' : 'On' ?>
            </button>
            <button class="del" onclick="deletePrompt(<?= $p['id'] ?>, '<?= addslashes($p['name']) ?>')"><i class="fas fa-trash"></i></button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($prompts)): ?>
<div class="mod-empty"><i class="fas fa-wand-magic-sparkles"></i><h3>Aucun prompt</h3><p>Créez votre premier prompt IA</p>
<button class="mod-btn mod-btn-primary" onclick="openModal()"><i class="fas fa-plus"></i> Nouveau prompt</button></div>
<?php endif; ?>

<div class="mod-overlay" id="modalOverlay">
    <div class="mod-modal" style="max-width:800px">
        <div class="mod-modal-header">
            <h3><i class="fas fa-wand-magic-sparkles" style="color:var(--accent)"></i> <span id="modalTitle">Nouveau Prompt</span></h3>
            <button class="mod-modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="mod-modal-body">
            <input type="hidden" id="editId" value="0">
            <div class="mod-form-grid">
                <div class="mod-form-group"><label>Nom du prompt</label><input type="text" id="fName" placeholder="Ex: Landing Page Premium"></div>
                <div class="mod-form-group"><label>Slug (auto si vide)</label><input type="text" id="fSlug" placeholder="landing-page-premium"></div>
                <div class="mod-form-group"><label>Catégorie</label><select id="fCategory"><?php foreach ($categories as $k => $c): ?><option value="<?= $k ?>"><?= $c['label'] ?></option><?php endforeach; ?></select></div>
                <div class="mod-form-group"><label>Modèle Claude</label><select id="fModel"><option value="claude-sonnet-4-20250514">Sonnet 4 (rapide)</option><option value="claude-opus-4-20250514">Opus 4 (puissant)</option></select></div>
                <div class="mod-form-group"><label>Max Tokens</label><input type="number" id="fMaxTokens" value="4096" min="1000" max="8192"></div>
                <div class="mod-form-group"><label>Température (0.0 - 1.0)</label><input type="number" id="fTemperature" value="0.7" min="0" max="1" step="0.1"></div>
                <div class="mod-form-group full"><label>Description</label><input type="text" id="fDescription" placeholder="Description courte pour l'admin"></div>
                <div class="mod-form-group full"><label>System Prompt <span class="prm-char" id="systemCharCount">0 car.</span></label><textarea id="fSystemPrompt" rows="12" placeholder="Tu es un expert en design web..." oninput="document.getElementById('systemCharCount').textContent=this.value.length+' car.'" style="font-family:var(--mono);font-size:.78rem;line-height:1.6"></textarea><div class="prm-hint">Le prompt système envoyé à Claude. Inclure les règles de format, style, contraintes.</div></div>
                <div class="mod-form-group full"><label>Template du User Prompt</label><textarea id="fUserTemplate" rows="3" placeholder="{{input}} — Contexte : {{context}} — {{entity_title}}"></textarea><div class="prm-hint">Variables : <code>{{input}}</code>, <code>{{context}}</code>, <code>{{entity_title}}</code></div></div>
                <div class="mod-form-group"><label>Tags (JSON)</label><input type="text" id="fTags" placeholder='["immobilier", "premium"]'></div>
                <div class="mod-form-group"><label style="display:flex;align-items:center;gap:6px;margin-top:16px"><input type="checkbox" id="fIsDefault" style="width:auto;accent-color:var(--accent)"> Prompt par défaut pour cette catégorie</label></div>
            </div>
        </div>
        <div class="mod-modal-footer">
            <button class="mod-btn mod-btn-secondary" onclick="closeModal()">Annuler</button>
            <button class="mod-btn mod-btn-primary" onclick="savePrompt()" id="btnSavePrompt"><i class="fas fa-save"></i> Enregistrer</button>
        </div>
    </div>
</div>

<script>
const API = '/admin/modules/ai-prompts/api.php';

function filterCat(cat) {
    document.querySelectorAll('.prm-fbtn').forEach(b => b.classList.toggle('active', b.dataset.filter === cat));
    document.querySelectorAll('.prm-card').forEach(c => c.style.display = (!cat || c.dataset.category === cat) ? '' : 'none');
}
function filterSearch() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.prm-card').forEach(c => c.style.display = c.dataset.name.includes(q) ? '' : 'none');
}

function openModal(title = 'Nouveau Prompt') {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalOverlay').classList.add('show');
}
function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    resetForm();
}
function resetForm() {
    document.getElementById('editId').value = 0;
    ['fName','fSlug','fDescription','fSystemPrompt','fUserTemplate','fTags'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('fCategory').value = 'general';
    document.getElementById('fModel').value = 'claude-sonnet-4-20250514';
    document.getElementById('fMaxTokens').value = 4096;
    document.getElementById('fTemperature').value = 0.7;
    document.getElementById('fIsDefault').checked = false;
}

async function editPrompt(id) {
    try {
        const r = await fetch(`${API}?action=get&id=${id}`);
        const d = await r.json();
        if (!d.success) return alert(d.error);
        const p = d.prompt;
        document.getElementById('editId').value = p.id;
        document.getElementById('fName').value = p.name;
        document.getElementById('fSlug').value = p.slug;
        document.getElementById('fCategory').value = p.category;
        document.getElementById('fDescription').value = p.description || '';
        document.getElementById('fSystemPrompt').value = p.system_prompt;
        document.getElementById('fUserTemplate').value = p.user_prompt_template || '';
        document.getElementById('fModel').value = p.model || 'claude-sonnet-4-20250514';
        document.getElementById('fMaxTokens').value = p.max_tokens || 4096;
        document.getElementById('fTemperature').value = p.temperature || 0.7;
        document.getElementById('fIsDefault').checked = !!p.is_default;
        document.getElementById('fTags').value = JSON.stringify(p.tags || []);
        document.getElementById('systemCharCount').textContent = (p.system_prompt||'').length+' car.';
        openModal('Modifier : ' + p.name);
    } catch(e) { alert(e.message); }
}

async function savePrompt() {
    const id = document.getElementById('editId').value;
    const fd = new FormData();
    fd.append('action', id > 0 ? 'update' : 'create');
    if (id > 0) fd.append('id', id);
    ['fName:name','fSlug:slug','fCategory:category','fDescription:description','fSystemPrompt:system_prompt','fUserTemplate:user_prompt_template','fModel:model','fMaxTokens:max_tokens','fTemperature:temperature','fTags:tags'].forEach(m => {
        const [elId, key] = m.split(':');
        fd.append(key, document.getElementById(elId).value);
    });
    fd.append('is_default', document.getElementById('fIsDefault').checked ? 1 : 0);
    const btn = document.getElementById('btnSavePrompt');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ...'; btn.disabled = true;
    try {
        const r = await fetch(API, {method:'POST', body:fd});
        const d = await r.json();
        if (d.success) { closeModal(); location.reload(); } else alert(d.error);
    } catch(e) { alert(e.message); }
    btn.innerHTML = '<i class="fas fa-save"></i> Enregistrer'; btn.disabled = false;
}

async function duplicatePrompt(id) {
    if (!confirm('Dupliquer ce prompt ?')) return;
    const fd = new FormData(); fd.append('action','duplicate'); fd.append('id', id);
    const d = await (await fetch(API, {method:'POST', body:fd})).json();
    d.success ? location.reload() : alert(d.error);
}
async function toggleActive(id, ns) {
    const fd = new FormData(); fd.append('action','update'); fd.append('id', id); fd.append('is_active', ns);
    const d = await (await fetch(API, {method:'POST', body:fd})).json();
    d.success ? location.reload() : alert(d.error);
}
async function deletePrompt(id, name) {
    if (!confirm('Supprimer "'+name+'" ?')) return;
    const fd = new FormData(); fd.append('action','delete'); fd.append('id', id);
    const d = await (await fetch(API, {method:'POST', body:fd})).json();
    d.success ? location.reload() : alert(d.error);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
document.getElementById('modalOverlay').addEventListener('click', e => { if (e.target === e.currentTarget) closeModal(); });
</script>