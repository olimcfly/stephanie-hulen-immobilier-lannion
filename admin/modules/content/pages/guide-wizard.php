<?php
/**
 * /admin/modules/content/pages/guide-wizard.php  v1.1
 * ============================================================
 * Wizard IA — Créer pages/guides avec questions guidées
 * ✅ FIX v1.1: Supprimé DOCTYPE/body (inclus via dashboard)
 * ✅ FIX: API path absolu, lien retour via router
 * ✅ Modal custom (pas système)
 * ✅ Choix template + persona + objectif
 * ✅ Génère contenu avec IA
 * ✅ Crée page avec fields pré-remplis
 * ============================================================
 */
if (!isset($pdo)) {
    require_once dirname(__DIR__, 4).'/includes/init.php';
}

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

// Récupérer contexte conseiller
$advisor = ['name' => 'Conseiller', 'city' => 'Ville'];
try {
    $stmt = $pdo->query("SELECT name, city FROM advisor_context LIMIT 1");
    if ($stmt) $row = $stmt->fetch();
    if (!empty($row)) $advisor = $row;
} catch (Throwable $e) {}

?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
.wiz-wrap { max-width: 900px; margin: 0 auto; font-family: 'Inter', -apple-system, sans-serif; }

.wiz-header { margin-bottom: 28px; }
.wiz-header h1 { font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 10px; margin: 0 0 4px; }
.wiz-header h1 i { color: #764ba2; }
.wiz-header p { color: #6b7280; font-size: 14px; margin: 0; }

.wiz-card { background: white; border: 1px solid #e5e7eb; border-radius: 14px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,.07); margin-bottom: 20px; }

.wiz-step-num { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg,#667eea,#764ba2); color: white; font-size: 14px; font-weight: 700; margin-bottom: 16px; }

.wiz-field-group { margin-bottom: 20px; }
.wiz-field-group:last-child { margin-bottom: 0; }

.wiz-field-group label { display: block; font-size: 13px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 8px; }

.wiz-field-group input, .wiz-field-group select, .wiz-field-group textarea { width: 100%; padding: 10px 14px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; font-family: inherit; color: #111827; transition: all .2s; box-sizing: border-box; }
.wiz-field-group input:focus, .wiz-field-group select:focus, .wiz-field-group textarea:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.1); }

.wiz-field-group textarea { resize: vertical; min-height: 80px; line-height: 1.5; }

.wiz-hint { font-size: 12px; color: #9ca3af; margin-top: 5px; font-style: italic; }

.wiz-templates-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; }
.wiz-template-btn { padding: 12px 14px; border: 2px solid #e5e7eb; border-radius: 10px; background: white; cursor: pointer; transition: all .2s; text-align: center; font-size: 13px; font-weight: 600; font-family: inherit; color: #374151; }
.wiz-template-btn:hover { border-color: #6366f1; background: #f0f4ff; }
.wiz-template-btn.active { border-color: #6366f1; background: #6366f1; color: white; }
.wiz-template-btn i { display: block; font-size: 18px; margin-bottom: 6px; }

.wiz-checkboxes { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.wiz-checkbox-item { display: flex; align-items: center; gap: 8px; }
.wiz-checkbox-item input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1; }
.wiz-checkbox-item label { margin: 0; font-weight: 500; text-transform: none; letter-spacing: normal; cursor: pointer; }

.wiz-actions { display: flex; gap: 10px; margin-top: 28px; }

.wiz-btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all .2s; font-family: inherit; text-decoration: none; }
.wiz-btn-primary { background: linear-gradient(135deg,#667eea,#764ba2); color: white; }
.wiz-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102,126,234,.3); }
.wiz-btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; box-shadow: none; }
.wiz-btn-ghost { background: white; color: #6b7280; border: 1px solid #e5e7eb; }
.wiz-btn-ghost:hover { border-color: #6366f1; color: #6366f1; }

.wiz-loading { display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,.4); backdrop-filter: blur(3px); align-items: center; justify-content: center; }
.wiz-loading.show { display: flex; }
.wiz-spinner-box { background: white; border-radius: 14px; padding: 32px 40px; text-align: center; }
.wiz-spinner-icon { font-size: 40px; color: #764ba2; margin-bottom: 16px; animation: wizSpin 1s linear infinite; }
@keyframes wizSpin { to { transform: rotate(360deg); } }
.wiz-spinner-text { font-size: 14px; font-weight: 500; color: #374151; }

.wiz-msg { padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; display: none; }
.wiz-msg.show { display: block; }
.wiz-msg.ok { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }
.wiz-msg.err { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; }

@media (max-width: 768px) {
    .wiz-templates-grid { grid-template-columns: 1fr; }
    .wiz-checkboxes { grid-template-columns: 1fr; }
    .wiz-actions { flex-direction: column; }
}
</style>

<div class="wiz-wrap">
    
    <!-- Header -->
    <div class="wiz-header">
        <h1><i class="fas fa-sparkles"></i> Créer un Guide IA</h1>
        <p>Répondez à quelques questions, nous générerons le contenu optimisé</p>
    </div>
    
    <!-- Messages -->
    <div class="wiz-msg" id="wizSuccessMsg"></div>
    <div class="wiz-msg" id="wizErrorMsg"></div>
    
    <form id="wizardForm">
        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken ?>">
        
        <!-- Étape 1 : Type de page -->
        <div class="wiz-card">
            <div class="wiz-step-num">1</div>
            <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Quel type de guide ?</h2>
            
            <div class="wiz-field-group">
                <label>Choisir un template</label>
                <div class="wiz-templates-grid">
                    <button type="button" class="wiz-template-btn active" data-template="t6-guide" onclick="wizSelectTpl(this)">
                        <i class="fas fa-book-open"></i> Guide Vendeur
                    </button>
                    <button type="button" class="wiz-template-btn" data-template="t2-edito" onclick="wizSelectTpl(this)">
                        <i class="fas fa-layer-group"></i> Edito Acheteur
                    </button>
                    <button type="button" class="wiz-template-btn" data-template="t3-secteur" onclick="wizSelectTpl(this)">
                        <i class="fas fa-map-pin"></i> Secteur
                    </button>
                    <button type="button" class="wiz-template-btn" data-template="t1-accueil" onclick="wizSelectTpl(this)">
                        <i class="fas fa-home"></i> Accueil
                    </button>
                </div>
                <input type="hidden" id="wizTemplateInput" name="template" value="t6-guide">
            </div>
        </div>
        
        <!-- Étape 2 : Persona cible -->
        <div class="wiz-card">
            <div class="wiz-step-num">2</div>
            <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Qui souhaitez-vous cibler ?</h2>
            
            <div class="wiz-field-group">
                <label>Persona cible</label>
                <select name="persona" required>
                    <option value="">-- Choisir --</option>
                    <option value="vendeur">Vendeur (vendre son bien)</option>
                    <option value="acheteur">Acheteur (acheter un bien)</option>
                    <option value="propriétaire">Propriétaire (investissement)</option>
                    <option value="nouveau_résident">Nouveau résident (découvrir la région)</option>
                </select>
            </div>
        </div>
        
        <!-- Étape 3 : Objectif -->
        <div class="wiz-card">
            <div class="wiz-step-num">3</div>
            <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Quel est l'objectif ?</h2>
            
            <div class="wiz-field-group">
                <label>Objectif principal</label>
                <textarea name="objective" placeholder="Ex: Augmenter les visites de vendeurs potentiels et les convaincre que nous sommes les meilleurs conseillers" required></textarea>
                <div class="wiz-hint">Décrivez ce que vous souhaitez accomplir avec ce guide</div>
            </div>
        </div>
        
        <!-- Étape 4 : Focus SEO -->
        <div class="wiz-card">
            <div class="wiz-step-num">4</div>
            <h2 style="font-size: 16px; font-weight: 700; margin-bottom: 16px;">Points à mettre en avant</h2>
            
            <div class="wiz-field-group">
                <label>Mots-clés / thèmes à couvrir</label>
                <textarea name="focus" placeholder="Ex: marché immobilier dynamique, prix compétitifs, quartiers tendance, transport"></textarea>
                <div class="wiz-hint">Optionnel - laissez vide pour génération auto</div>
            </div>
            
            <div class="wiz-field-group">
                <label>Points forts de votre région</label>
                <div class="wiz-checkboxes">
                    <div class="wiz-checkbox-item">
                        <input type="checkbox" id="wiz_cb_transport" name="specialties" value="Transport">
                        <label for="wiz_cb_transport">Transports en commun</label>
                    </div>
                    <div class="wiz-checkbox-item">
                        <input type="checkbox" id="wiz_cb_schools" name="specialties" value="Écoles">
                        <label for="wiz_cb_schools">Écoles/Éducation</label>
                    </div>
                    <div class="wiz-checkbox-item">
                        <input type="checkbox" id="wiz_cb_leisure" name="specialties" value="Loisirs">
                        <label for="wiz_cb_leisure">Loisirs/Divertissement</label>
                    </div>
                    <div class="wiz-checkbox-item">
                        <input type="checkbox" id="wiz_cb_commerce" name="specialties" value="Commerce">
                        <label for="wiz_cb_commerce">Commerce/Restaurants</label>
                    </div>
                    <div class="wiz-checkbox-item">
                        <input type="checkbox" id="wiz_cb_nature" name="specialties" value="Nature">
                        <label for="wiz_cb_nature">Nature/Espaces verts</label>
                    </div>
                    <div class="wiz-checkbox-item">
                        <input type="checkbox" id="wiz_cb_culture" name="specialties" value="Culture">
                        <label for="wiz_cb_culture">Culture/Patrimoine</label>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="wiz-actions">
            <a href="?page=pages" class="wiz-btn wiz-btn-ghost"><i class="fas fa-arrow-left"></i> Retour</a>
            <button type="submit" class="wiz-btn wiz-btn-primary" id="wizSubmitBtn"><i class="fas fa-sparkles"></i> Générer le guide</button>
        </div>
    </form>
</div>

<!-- Loading spinner -->
<div class="wiz-loading" id="wizLoading">
    <div class="wiz-spinner-box">
        <div class="wiz-spinner-icon"><i class="fas fa-spinner"></i></div>
        <div class="wiz-spinner-text">Génération du guide en cours...</div>
    </div>
</div>

<script>
function wizSelectTpl(btn) {
    document.querySelectorAll('.wiz-template-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('wizTemplateInput').value = btn.dataset.template;
}

function wizMsg(id, msg, type) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.className = 'wiz-msg show ' + type;
}

document.getElementById('wizardForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const specialties = Array.from(document.querySelectorAll('input[name="specialties"]:checked')).map(cb => cb.value);
    
    const payload = {
        action: 'guide_create',
        template: formData.get('template'),
        persona: formData.get('persona'),
        objective: formData.get('objective'),
        focus: formData.get('focus'),
        advisor_name: '<?php echo addslashes($advisor['name'] ?? 'Conseiller') ?>',
        advisor_city: '<?php echo addslashes($advisor['city'] ?? 'Ville') ?>',
        advisor_specialties: specialties,
        csrf_token: formData.get('csrf_token'),
    };
    
    if (!payload.persona || !payload.objective) {
        wizMsg('wizErrorMsg', 'Veuillez remplir tous les champs requis', 'err');
        return;
    }
    
    document.getElementById('wizLoading').classList.add('show');
    const btn = document.getElementById('wizSubmitBtn');
    btn.disabled = true;
    
    try {
        const response = await fetch('/admin/api/content/pages.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (data.success) {
            wizMsg('wizSuccessMsg', 'Guide généré ! Redirection...', 'ok');
            const newId = data.page_id || data.id;
            setTimeout(() => {
                window.location.href = newId ? '?page=pages&action=edit&id=' + newId : '?page=pages';
            }, 1500);
        } else {
            wizMsg('wizErrorMsg', data.error || 'Erreur lors de la génération', 'err');
        }
    } catch (err) {
        wizMsg('wizErrorMsg', 'Erreur réseau: ' + err.message, 'err');
    } finally {
        document.getElementById('wizLoading').classList.remove('show');
        btn.disabled = false;
    }
});
</script>