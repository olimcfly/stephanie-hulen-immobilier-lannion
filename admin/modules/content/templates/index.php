<?php
/**
 * MODULE TEMPLATES
 * /admin/modules/builder/builder/templates.php   ← route corrigée v8.4.3
 * Route : dashboard.php?page=templates
 */
if (!defined('ADMIN_ROUTER')) { http_response_code(403); exit('Accès refusé'); }

// ── Templates pré-construits ──
$templates = [
    // IMMOBILIER
    [
        'id' => 'hero-achat',
        'category' => 'immobilier',
        'name' => 'Héro Achat',
        'description' => 'Page d\'accueil complète pour l\'achat d\'un bien',
        'icon' => '🏠',
        'html' => '<section style="background:linear-gradient(135deg,#1a4d7a,#2d7dd2);color:white;padding:80px 20px;text-align:center;"><div style="max-width:900px;margin:0 auto;"><h1 style="font-size:48px;margin-bottom:20px;font-weight:700;">Trouvez votre bien de rêve à Bordeaux</h1><p style="font-size:18px;margin-bottom:40px;opacity:0.9;">Découvrez nos annonces exclusives dans les meilleurs quartiers</p><a href="#contact" style="background:white;color:#1a4d7a;padding:14px 32px;border-radius:8px;font-weight:600;display:inline-block;text-decoration:none;">Consulter les biens</a></div></section>',
    ],
    [
        'id' => 'hero-vente',
        'category' => 'immobilier',
        'name' => 'Héro Vente',
        'description' => 'Bannière avec CTA pour estimation gratuite',
        'icon' => '📈',
        'html' => '<section style="background:linear-gradient(135deg,#d4a574,#c9913b);color:white;padding:80px 20px;text-align:center;"><div style="max-width:900px;margin:0 auto;"><h1 style="font-size:48px;margin-bottom:20px;font-weight:700;">Vendez votre bien au meilleur prix</h1><p style="font-size:18px;margin-bottom:40px;opacity:0.9;">Estimez gratuitement votre propriété en 2 minutes</p><a href="#contact" style="background:white;color:#c9913b;padding:14px 32px;border-radius:8px;font-weight:600;display:inline-block;text-decoration:none;">Obtenir une estimation</a></div></section>',
    ],
    [
        'id' => 'hero-location',
        'category' => 'immobilier',
        'name' => 'Héro Location',
        'description' => 'Bannière dédiée aux locations',
        'icon' => '🔑',
        'html' => '<section style="background:linear-gradient(135deg,#4facfe,#00f2fe);color:white;padding:80px 20px;text-align:center;"><div style="max-width:900px;margin:0 auto;"><h1 style="font-size:48px;margin-bottom:20px;font-weight:700;">Trouvez votre appartement à louer</h1><p style="font-size:18px;margin-bottom:40px;opacity:0.9;">Accès rapide à nos meilleures locations</p><a href="#contact" style="background:white;color:#00b4d8;padding:14px 32px;border-radius:8px;font-weight:600;display:inline-block;text-decoration:none;">Voir les annonces</a></div></section>',
    ],
    // PRÉSENTATION
    [
        'id' => 'about-conseil',
        'category' => 'presentation',
        'name' => 'À Propos Conseiller',
        'description' => 'Section biographie avec expertise',
        'icon' => '👤',
        'html' => '<section style="padding:60px 20px;background:#f9f6f3;"><div style="max-width:900px;margin:0 auto;"><h2 style="font-size:36px;margin-bottom:30px;text-align:center;font-weight:700;font-family:\'Playfair Display\',serif;">À propos de moi</h2><div style="display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center;"><div><div style="background:#e2e8f0;border-radius:12px;height:400px;display:flex;align-items:center;justify-content:center;font-size:48px;">📸</div></div><div><h3 style="font-size:24px;margin-bottom:15px;font-weight:600;">Conseiller immobilier spécialisé</h3><p style="font-size:16px;line-height:1.8;color:#555;margin-bottom:20px;">Avec plus de 10 ans d\'expérience dans l\'immobilier bordelais, je vous accompagne dans tous vos projets.</p><ul style="list-style:none;padding:0;"><li style="padding:10px 0;border-bottom:1px solid #ddd;">✅ 500+ transactions réussies</li><li style="padding:10px 0;border-bottom:1px solid #ddd;">✅ Expert des quartiers bordelais</li><li style="padding:10px 0;">✅ Conseils gratuits et sans engagement</li></ul></div></div></div></section>',
    ],
    // CONTACT
    [
        'id' => 'formulaire-contact',
        'category' => 'contact',
        'name' => 'Formulaire de Contact',
        'description' => 'Formulaire simple avec tous les champs essentiels',
        'icon' => '📧',
        'html' => '<section style="padding:60px 20px;background:white;"><div style="max-width:600px;margin:0 auto;"><h2 style="font-size:32px;margin-bottom:10px;text-align:center;font-weight:700;font-family:\'Playfair Display\',serif;">Me contacter</h2><p style="text-align:center;color:#666;margin-bottom:40px;">Une question ? Je vous réponds rapidement</p><form><div style="margin-bottom:20px;"><label style="display:block;margin-bottom:8px;font-weight:600;">Votre nom</label><input type="text" placeholder="Votre nom" style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-size:14px;box-sizing:border-box;"></div><div style="margin-bottom:20px;"><label style="display:block;margin-bottom:8px;font-weight:600;">Email</label><input type="email" placeholder="votre@email.com" style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-size:14px;box-sizing:border-box;"></div><div style="margin-bottom:30px;"><label style="display:block;margin-bottom:8px;font-weight:600;">Message</label><textarea rows="5" placeholder="Votre message..." style="width:100%;padding:12px;border:1px solid #ddd;border-radius:8px;font-size:14px;font-family:inherit;box-sizing:border-box;"></textarea></div><button type="submit" style="width:100%;padding:14px;background:linear-gradient(135deg,#1a4d7a,#2d7dd2);color:white;border:none;border-radius:8px;font-weight:600;cursor:pointer;font-size:16px;">Envoyer</button></form></div></section>',
    ],
    [
        'id' => 'appel-action',
        'category' => 'contact',
        'name' => 'Appel à l\'Action',
        'description' => 'CTA avec numéro de téléphone prominent',
        'icon' => '☎️',
        'html' => '<section style="background:linear-gradient(135deg,#1a4d7a,#2d7dd2);color:white;padding:80px 20px;text-align:center;"><div style="max-width:900px;margin:0 auto;"><p style="font-size:18px;margin-bottom:20px;opacity:0.9;">Vous avez une question ?</p><h2 style="font-size:52px;margin-bottom:30px;font-weight:700;">Appelez-moi directement</h2><a href="tel:+33624105816" style="display:inline-block;font-size:32px;font-weight:700;color:white;text-decoration:none;padding:20px 40px;background:rgba(255,255,255,0.2);border-radius:12px;">06 24 10 58 16</a><p style="margin-top:30px;opacity:0.8;">Disponible du lundi au vendredi, 9h-19h</p></div></section>',
    ],
    // TÉMOIGNAGES
    [
        'id' => 'temoignages',
        'category' => 'contenu',
        'name' => 'Témoignages Clients',
        'description' => 'Grille 3 colonnes avec avis clients',
        'icon' => '⭐',
        'html' => '<section style="padding:60px 20px;background:#f9f6f3;"><div style="max-width:1200px;margin:0 auto;"><h2 style="font-size:36px;margin-bottom:50px;text-align:center;font-weight:700;font-family:\'Playfair Display\',serif;">Ce que disent mes clients</h2><div style="display:grid;grid-template-columns:repeat(3,1fr);gap:30px;"><div style="background:white;padding:30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);"><div style="margin-bottom:15px;color:#d4a574;font-size:18px;">⭐⭐⭐⭐⭐</div><p style="margin-bottom:20px;color:#555;line-height:1.6;">"Un professionnel très attentif. Il a vendu ma maison en 3 mois au prix souhaité !"</p><p style="font-weight:700;">Marie Dupont</p><p style="color:#999;font-size:13px;">Vente - 2024</p></div><div style="background:white;padding:30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);"><div style="margin-bottom:15px;color:#d4a574;font-size:18px;">⭐⭐⭐⭐⭐</div><p style="margin-bottom:20px;color:#555;line-height:1.6;">"Excellent service du début à la fin. Je recommande vivement !"</p><p style="font-weight:700;">Jean Martin</p><p style="color:#999;font-size:13px;">Achat - 2024</p></div><div style="background:white;padding:30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,0.08);"><div style="margin-bottom:15px;color:#d4a574;font-size:18px;">⭐⭐⭐⭐⭐</div><p style="margin-bottom:20px;color:#555;line-height:1.6;">"Très à l\'écoute et de bons conseils pour ma recherche."</p><p style="font-weight:700;">Sophie Laurent</p><p style="color:#999;font-size:13px;">Location - 2024</p></div></div></div></section>',
    ],
    // SEO
    [
        'id' => 'fiche-quartier',
        'category' => 'seo',
        'name' => 'Fiche Quartier SEO',
        'description' => 'Page optimisée SEO pour un quartier',
        'icon' => '🗺️',
        'html' => '<section style="padding:60px 20px;"><div style="max-width:900px;margin:0 auto;"><h1 style="font-size:42px;margin-bottom:20px;font-weight:700;font-family:\'Playfair Display\',serif;">Immobilier à [Quartier]</h1><p style="font-size:18px;color:#666;margin-bottom:40px;line-height:1.8;">[Description du quartier : histoire, atouts, vie locale...]</p><h2 style="font-size:28px;margin:40px 0 20px;font-weight:700;">Caractéristiques du quartier</h2><ul style="list-style:none;padding:0;"><li style="padding:12px 0;border-bottom:1px solid #eee;">✅ Point fort 1</li><li style="padding:12px 0;border-bottom:1px solid #eee;">✅ Point fort 2</li><li style="padding:12px 0;border-bottom:1px solid #eee;">✅ Point fort 3</li><li style="padding:12px 0;">✅ Point fort 4</li></ul><h2 style="font-size:28px;margin:40px 0 20px;font-weight:700;">Nos annonces dans ce quartier</h2><p style="color:#666;">Consultez ci-dessous les biens actuellement disponibles.</p></div></section>',
    ],
];

$categories = [
    'immobilier'   => '🏠 Immobilier',
    'presentation' => '👤 Présentation',
    'contact'      => '📞 Contact',
    'contenu'      => '📝 Contenu',
    'seo'          => '🔍 SEO & Quartiers',
];

$selected_category = $_GET['category'] ?? 'immobilier';
if (!isset($categories[$selected_category])) $selected_category = 'immobilier';

$filtered = array_filter($templates, fn($t) => $t['category'] === $selected_category);
?>

<style>
.tpl-wrap { max-width:1200px; }
.tpl-cats { display:flex; gap:10px; margin-bottom:32px; flex-wrap:wrap; }
.tpl-cat {
    padding:8px 18px; border:2px solid #e2e8f0; background:white;
    border-radius:8px; cursor:pointer; font-weight:600; font-size:13px;
    color:#64748b; transition:.15s; text-decoration:none;
}
.tpl-cat:hover { border-color:#6366f1; color:#6366f1; }
.tpl-cat.active { background:linear-gradient(135deg,#6366f1,#a855f7); color:white; border-color:transparent; }
.tpl-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:20px; }
.tpl-card {
    background:white; border:1px solid #e2e8f0; border-radius:12px;
    overflow:hidden; transition:.2s;
}
.tpl-card:hover { border-color:#6366f1; transform:translateY(-4px); box-shadow:0 8px 20px rgba(99,102,241,.12); }
.tpl-icon { background:linear-gradient(135deg,#f0f4f8,#dde6f0); padding:40px 20px; text-align:center; font-size:52px; min-height:140px; display:flex; align-items:center; justify-content:center; }
.tpl-body { padding:18px; }
.tpl-name { font-size:15px; font-weight:700; color:#1e293b; margin-bottom:6px; }
.tpl-desc { font-size:12px; color:#64748b; margin-bottom:16px; line-height:1.5; min-height:34px; }
.tpl-btns { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.tpl-btn { padding:9px 12px; border:none; border-radius:7px; font-weight:700; font-size:12px; cursor:pointer; transition:.15s; text-align:center; }
.tpl-btn-p { background:linear-gradient(135deg,#1a4d7a,#2d7dd2); color:white; }
.tpl-btn-p:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(26,77,122,.3); }
.tpl-btn-s { background:#f1f5f9; color:#64748b; border:1px solid #e2e8f0; }
.tpl-btn-s:hover { background:#e2e8f0; }
.tpl-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:2000; align-items:center; justify-content:center; }
.tpl-modal.open { display:flex; }
.tpl-modal-box { background:white; border-radius:12px; max-width:520px; width:92%; padding:32px; box-shadow:0 10px 40px rgba(0,0,0,.3); }
.tpl-modal-preview { background:white; border-radius:12px; max-width:920px; width:96%; max-height:90vh; overflow-y:auto; box-shadow:0 10px 40px rgba(0,0,0,.3); }
.tpl-modal-hd { display:flex; justify-content:space-between; align-items:center; padding:18px 24px; border-bottom:1px solid #e2e8f0; }
.tpl-modal-hd h3 { font-size:17px; font-weight:700; }
.tpl-close { background:none; border:none; font-size:26px; cursor:pointer; color:#94a3b8; line-height:1; }
.tpl-form-group { margin-bottom:18px; }
.tpl-form-group label { display:block; margin-bottom:7px; font-weight:700; font-size:12px; color:#374151; }
.tpl-form-group input, .tpl-form-group textarea { width:100%; padding:10px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:13px; font-family:inherit; box-sizing:border-box; }
.tpl-form-group input:focus, .tpl-form-group textarea:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 3px rgba(99,102,241,.1); }
.tpl-form-actions { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:24px; }
.tpl-form-actions button { padding:11px; border:none; border-radius:8px; font-weight:700; font-size:13px; cursor:pointer; }
.tpl-cancel { background:#f1f5f9; color:#64748b; }
.tpl-submit { background:linear-gradient(135deg,#1a4d7a,#2d7dd2); color:white; }
</style>

<div class="tpl-wrap">

  <div class="tpl-cats">
    <?php foreach ($categories as $key => $label): ?>
    <a href="?page=templates&category=<?= $key ?>"
       class="tpl-cat <?= $selected_category === $key ? 'active' : '' ?>">
      <?= $label ?>
    </a>
    <?php endforeach; ?>
  </div>

  <?php if (!empty($filtered)): ?>
  <div class="tpl-grid">
    <?php foreach ($filtered as $t): ?>
    <div class="tpl-card">
      <div class="tpl-icon"><?= $t['icon'] ?></div>
      <div class="tpl-body">
        <div class="tpl-name"><?= htmlspecialchars($t['name']) ?></div>
        <div class="tpl-desc"><?= htmlspecialchars($t['description']) ?></div>
        <div class="tpl-btns">
          <button class="tpl-btn tpl-btn-p"
            onclick="tplOpenCreate('<?= htmlspecialchars(addslashes($t['name'])) ?>','<?= $t['id'] ?>','<?= base64_encode($t['html']) ?>')">
            ✨ Utiliser
          </button>
          <button class="tpl-btn tpl-btn-s"
            onclick="tplPreview('<?= base64_encode($t['html']) ?>')">
            👁️ Aperçu
          </button>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php else: ?>
  <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
    <div style="font-size:48px;margin-bottom:12px">📭</div>
    <h3 style="font-size:16px;font-weight:700;color:#64748b">Aucun template dans cette catégorie</h3>
  </div>
  <?php endif; ?>

</div>

<!-- Modal Aperçu -->
<div id="tplModalPreview" class="tpl-modal">
  <div class="tpl-modal-preview">
    <div class="tpl-modal-hd">
      <h3>Aperçu</h3>
      <button class="tpl-close" onclick="tplClosePreview()">×</button>
    </div>
    <div id="tplPreviewContent" style="background:#f9f6f3;padding:0;"></div>
  </div>
</div>

<!-- Modal Créer page -->
<div id="tplModalCreate" class="tpl-modal">
  <div class="tpl-modal-box">
    <div style="font-size:18px;font-weight:700;margin-bottom:20px">Créer une page depuis ce template</div>
    <div class="tpl-form-group">
      <label>Titre de la page *</label>
      <input type="text" id="tplTitle" placeholder="Ex: Vendre ma maison à Bordeaux">
    </div>
    <div class="tpl-form-group">
      <label>URL (slug) *</label>
      <input type="text" id="tplSlug" placeholder="vendre-maison-bordeaux">
    </div>
    <div class="tpl-form-group">
      <label>Titre SEO</label>
      <input type="text" id="tplMetaTitle" placeholder="Titre pour Google...">
    </div>
    <div class="tpl-form-group">
      <label>Description SEO</label>
      <textarea id="tplMetaDesc" rows="3" placeholder="Description courte..."></textarea>
    </div>
    <input type="hidden" id="tplHtml">
    <div class="tpl-form-actions">
      <button type="button" class="tpl-cancel" onclick="tplCloseCreate()">Annuler</button>
      <button type="button" class="tpl-submit" onclick="tplSubmit()">✨ Créer la page</button>
    </div>
  </div>
</div>

<script>
function tplPreview(b64) {
    document.getElementById('tplPreviewContent').innerHTML = atob(b64);
    document.getElementById('tplModalPreview').classList.add('open');
}
function tplClosePreview() {
    document.getElementById('tplModalPreview').classList.remove('open');
    document.getElementById('tplPreviewContent').innerHTML = '';
}
function tplOpenCreate(name, id, b64) {
    document.getElementById('tplHtml').value = atob(b64);
    document.getElementById('tplTitle').value = name;
    document.getElementById('tplSlug').value = id;
    document.getElementById('tplMetaTitle').value = name;
    document.getElementById('tplMetaDesc').value = '';
    document.getElementById('tplModalCreate').classList.add('open');
}
function tplCloseCreate() {
    document.getElementById('tplModalCreate').classList.remove('open');
}
document.getElementById('tplTitle').addEventListener('input', function() {
    document.getElementById('tplSlug').value = this.value
        .toLowerCase().trim()
        .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
        .replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
});
['tplModalPreview','tplModalCreate'].forEach(id => {
    document.getElementById(id).addEventListener('click', e => {
        if (e.target.id === id) document.getElementById(id).classList.remove('open');
    });
});
async function tplSubmit() {
    const title = document.getElementById('tplTitle').value.trim();
    const slug  = document.getElementById('tplSlug').value.trim();
    if (!title || !slug) { alert('Titre et slug requis'); return; }
    const btn = document.querySelector('.tpl-submit');
    btn.textContent = '⏳ Création...'; btn.disabled = true;
    try {
        const res = await fetch('/admin/api/content/pages.php', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({
                action:'create', title, slug,
                content: document.getElementById('tplHtml').value,
                meta_title: document.getElementById('tplMetaTitle').value,
                meta_description: document.getElementById('tplMetaDesc').value,
                status:'draft'
            })
        });
        const data = await res.json();
        if (data.success) {
            alert('✅ Page créée ! Redirection...');
            window.location.href = '/admin/dashboard.php?page=pages';
        } else {
            alert('❌ ' + (data.error || 'Erreur création'));
            btn.textContent = '✨ Créer la page'; btn.disabled = false;
        }
    } catch(e) {
        alert('❌ Erreur réseau');
        btn.textContent = '✨ Créer la page'; btn.disabled = false;
    }
}
</script>