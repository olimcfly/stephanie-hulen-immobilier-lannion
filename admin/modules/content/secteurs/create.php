<?php
/**
 * Module Secteurs — Creation rapide
 * /admin/modules/content/secteurs/create.php
 *
 * Charge via dashboard.php (?page=secteurs-create)
 * ou lien depuis index.php / edit.php
 *
 * Cree le secteur en DB puis redirige vers edit.php
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }

// ─── Root & DB ───
if (!defined('ROOT_PATH')) {
    $candidates = [
        dirname(dirname(dirname(dirname(__DIR__)))),
        dirname(dirname(dirname(__DIR__))),
        dirname(dirname(__DIR__)),
    ];
    foreach ($candidates as $r) {
        if (file_exists($r . '/includes/classes/Database.php')) { define('ROOT_PATH', $r); break; }
    }
    if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
}
foreach ([ROOT_PATH . '/includes/classes/Database.php', ROOT_PATH . '/includes/Database.php'] as $f) {
    if (file_exists($f)) { require_once $f; break; }
}
foreach ([ROOT_PATH . '/config/config.php', ROOT_PATH . '/config/constants.php'] as $f) {
    if (file_exists($f)) { @require_once $f; }
}

try { $pdo = Database::getInstance(); }
catch (Exception $e) { http_response_code(500); die('DB error : ' . $e->getMessage()); }

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['csrf_token'];

$aiOk  = (defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY) ||
         (defined('OPENAI_API_KEY')    && OPENAI_API_KEY);
$aiLbl = defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY ? 'Claude' :
        (defined('OPENAI_API_KEY')     && OPENAI_API_KEY   ? 'OpenAI' : '');

// ─── Villes existantes pour suggestion ───
$villes = [];
try {
    $villes = $pdo->query(
        "SELECT DISTINCT ville FROM secteurs WHERE ville IS NOT NULL AND ville != '' ORDER BY ville"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $e) {}
$defaultVille = $villes[0] ?? 'Bordeaux';

function slugify(string $str): string {
    $str = mb_strtolower($str);
    $map = ['à'=>'a','â'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c'];
    $str = strtr($str, $map);
    return trim(preg_replace('/[^a-z0-9]+/', '-', $str), '-');
}

// ─── POST : creation ───
$createErr = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, $_POST['csrf_token'] ?? '')) {

    $nom      = trim($_POST['nom']         ?? '');
    $ville    = trim($_POST['ville']       ?? $defaultVille);
    $typeS    = in_array($_POST['type_secteur'] ?? '', ['quartier', 'commune'])
                ? $_POST['type_secteur'] : 'quartier';
    $slug     = trim($_POST['slug']        ?? '');

    if (empty($nom)) {
        $createErr = 'Le nom du secteur est obligatoire.';
    } else {
        // Auto-slug
        if (empty($slug)) {
            $slug = slugify($nom) . '-' . slugify($ville);
        }
        // Unicite slug
        try {
            $cnt = (int)$pdo->prepare("SELECT COUNT(*) FROM secteurs WHERE slug=?")
                            ->execute([$slug]) ? 0 : 0;
            $st = $pdo->prepare("SELECT COUNT(*) FROM secteurs WHERE slug=?");
            $st->execute([$slug]);
            if ((int)$st->fetchColumn() > 0) $slug .= '-' . time();
        } catch (Throwable $e) {}

        // Verifie que la table existe, cree si besoin
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `secteurs` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `nom` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL,
                `ville` VARCHAR(100) DEFAULT 'Bordeaux',
                `type_secteur` ENUM('quartier','commune') DEFAULT 'quartier',
                `status` ENUM('draft','published','archived') DEFAULT 'draft',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_slug` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Throwable $e) {}

        try {
            $ins = $pdo->prepare(
                "INSERT INTO secteurs (nom, slug, ville, type_secteur, status, created_at)
                 VALUES (?, ?, ?, ?, 'draft', NOW())"
            );
            $ins->execute([$nom, $slug, $ville, $typeS]);
            $newId = (int)$pdo->lastInsertId();
            header("Location: /admin/modules/content/secteurs/edit.php?id={$newId}&msg=created");
            exit;
        } catch (PDOException $e) {
            $createErr = 'Erreur creation : ' . $e->getMessage();
        }
    }
}

// ─── Suggestions de secteurs immobilier ───
$suggestions = [
    // Quartiers types
    ['nom'=>'Centre-Ville',       'icon'=>'fa-landmark',         'color'=>'#6366f1', 'type'=>'quartier'],
    ['nom'=>'Hyper-Centre',       'icon'=>'fa-building',         'color'=>'#7c3aed', 'type'=>'quartier'],
    ['nom'=>'Quartier des Quais', 'icon'=>'fa-water',            'color'=>'#0369a1', 'type'=>'quartier'],
    ['nom'=>'Secteur Gare',       'icon'=>'fa-train',            'color'=>'#0d9488', 'type'=>'quartier'],
    ['nom'=>'Zone Pavillonnaire', 'icon'=>'fa-house',            'color'=>'#10b981', 'type'=>'quartier'],
    ['nom'=>'Quartier Etudiant',  'icon'=>'fa-graduation-cap',   'color'=>'#f59e0b', 'type'=>'quartier'],
    ['nom'=>'Secteur Affaires',   'icon'=>'fa-briefcase',        'color'=>'#ef4444', 'type'=>'quartier'],
    ['nom'=>'Bord de Mer',        'icon'=>'fa-umbrella-beach',   'color'=>'#06b6d4', 'type'=>'quartier'],
    // Communes types
    ['nom'=>'Commune Centre',     'icon'=>'fa-city',             'color'=>'#6366f1', 'type'=>'commune'],
    ['nom'=>'Commune Periurbaine','icon'=>'fa-map-location-dot', 'color'=>'#7c3aed', 'type'=>'commune'],
    ['nom'=>'Village Rural',      'icon'=>'fa-tree',             'color'=>'#10b981', 'type'=>'commune'],
    ['nom'=>'Zone Industrielle',  'icon'=>'fa-industry',         'color'=>'#94a3b8', 'type'=>'commune'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>&#10133; Nouveau secteur &mdash; Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --accent:    #0d9488;
  --accent-bg: rgba(13,148,136,.08);
  --surface:   #ffffff;
  --surface-2: #f8fafc;
  --surface-3: #f1f5f9;
  --border:    #e2e8f0;
  --text:      #0f172a;
  --text-2:    #475569;
  --text-3:    #94a3b8;
  --green:     #10b981;
  --green-bg:  #d1fae5;
  --red:       #ef4444;
  --red-bg:    #fee2e2;
  --amber:     #f59e0b;
  --radius:    8px;
  --radius-lg: 14px;
  --shadow-sm: 0 1px 3px rgba(0,0,0,.07);
  --font: 'DM Sans', sans-serif;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:var(--font);background:#f1f5f9;color:var(--text);font-size:14px}

/* Topbar */
.cr-top{
  position:sticky;top:0;z-index:200;height:54px;
  background:var(--surface);border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 24px;box-shadow:0 1px 8px rgba(0,0,0,.05);
}
.cr-top-l{display:flex;align-items:center;gap:10px}
.cr-back{
  display:inline-flex;align-items:center;gap:5px;
  padding:6px 12px;border-radius:var(--radius);
  color:var(--text-2);text-decoration:none;font-size:12.5px;font-weight:600;
  border:1px solid var(--border);transition:all .14s;
}
.cr-back:hover{background:var(--surface-2);color:var(--text)}
.cr-top-title{font-size:15px;font-weight:800;color:var(--text);display:flex;align-items:center;gap:8px}
.cr-top-title i{color:var(--accent)}
.cr-ai-badge{
  display:inline-flex;align-items:center;gap:5px;
  padding:4px 10px;border-radius:20px;
  background:#f5f3ff;color:#6d28d9;border:1px solid #ddd6fe;
  font-size:11px;font-weight:700;
}

/* Wrap */
.cr-wrap{max-width:860px;margin:0 auto;padding:28px 20px}

/* Section label */
.cr-sec-label{
  font-size:11px;font-weight:700;color:var(--text-2);
  text-transform:uppercase;letter-spacing:.07em;
  margin-bottom:12px;display:flex;align-items:center;gap:7px;
}
.cr-sec-label i{color:var(--accent)}

/* Suggestions */
.cr-sugg-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:9px;margin-bottom:24px}
.cr-sugg{
  display:flex;align-items:center;gap:10px;
  padding:11px 13px;border:1.5px solid var(--border);
  border-radius:var(--radius);cursor:pointer;transition:all .14s;
  background:var(--surface);
}
.cr-sugg:hover{border-color:var(--accent);background:var(--accent-bg);transform:translateY(-2px);box-shadow:var(--shadow-sm)}
.cr-sugg-icon{
  width:32px;height:32px;border-radius:7px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:13px;color:#fff;
}
.cr-sugg-nom{font-size:12.5px;font-weight:700;color:var(--text)}
.cr-sugg-type{font-size:10px;color:var(--text-3)}

/* Carte principale */
.cr-card{
  background:var(--surface);border:1px solid var(--border);
  border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);overflow:hidden;
}
.cr-card-hdr{
  padding:20px 24px;background:linear-gradient(135deg,#0d9488,#0f766e);
  display:flex;align-items:center;gap:12px;
}
.cr-card-hdr-icon{
  width:42px;height:42px;border-radius:var(--radius);
  background:rgba(255,255,255,.18);display:flex;
  align-items:center;justify-content:center;font-size:18px;color:#fff;
}
.cr-card-hdr-text h2{font-size:16px;font-weight:800;color:#fff;margin-bottom:2px}
.cr-card-hdr-text p{font-size:12px;color:rgba(255,255,255,.75)}
.cr-card-body{padding:24px}

/* Champs */
.cr-field{margin-bottom:18px}
.cr-field:last-child{margin-bottom:0}
.cr-label{
  display:block;font-size:11px;font-weight:700;color:var(--text-2);
  text-transform:uppercase;letter-spacing:.06em;margin-bottom:7px;
}
.cr-input{
  width:100%;padding:11px 14px;
  border:2px solid var(--border);border-radius:var(--radius);
  background:var(--surface-2);color:var(--text);
  font-size:14px;font-family:var(--font);transition:all .16s;outline:none;
}
.cr-input:focus{border-color:var(--accent);background:var(--surface);box-shadow:0 0 0 3px var(--accent-bg)}
.cr-input::placeholder{color:var(--text-3)}

/* Slug row */
.cr-slug-row{display:flex;align-items:center}
.cr-slug-pfx{
  padding:11px 10px 11px 13px;
  border:2px solid var(--border);border-right:none;
  border-radius:var(--radius) 0 0 var(--radius);
  background:var(--surface-3);color:var(--text-3);
  font-size:12px;white-space:nowrap;
}
.cr-slug-row .cr-input{border-radius:0 var(--radius) var(--radius) 0;flex:1;font-size:13px}
.cr-slug-gen{
  margin-left:8px;padding:11px 14px;border-radius:var(--radius);
  border:1px solid var(--border);background:var(--surface-2);
  color:var(--text-2);font-size:12px;font-weight:700;cursor:pointer;
  font-family:var(--font);transition:all .14s;white-space:nowrap;
}
.cr-slug-gen:hover{background:var(--accent);color:#fff;border-color:var(--accent)}

/* Row 2 */
.cr-row2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
@media(max-width:600px){.cr-row2{grid-template-columns:1fr}}

/* Type toggle */
.cr-type-toggle{display:flex;gap:8px}
.cr-type-btn{
  flex:1;padding:12px;border-radius:var(--radius);
  border:2px solid var(--border);background:var(--surface);
  cursor:pointer;transition:all .14s;text-align:center;
}
.cr-type-btn:hover{border-color:var(--accent);background:var(--accent-bg)}
.cr-type-btn.active{border-color:var(--accent);background:var(--accent);color:#fff}
.cr-type-btn.active i,.cr-type-btn.active span{color:#fff}
.cr-type-btn i{font-size:20px;color:var(--accent);display:block;margin-bottom:5px}
.cr-type-btn span{font-size:12px;font-weight:700;color:var(--text)}
.cr-type-btn small{display:block;font-size:10px;color:var(--text-3);margin-top:2px}
input[name="type_secteur"]{display:none}

/* Submit */
.cr-submit{
  width:100%;padding:14px;border-radius:var(--radius);
  background:var(--accent);color:#fff;border:none;
  font-size:15px;font-weight:700;cursor:pointer;
  font-family:var(--font);transition:all .16s;
  display:flex;align-items:center;justify-content:center;gap:8px;
  margin-top:20px;
}
.cr-submit:hover{background:#0f766e;transform:translateY(-2px);box-shadow:0 6px 20px rgba(13,148,136,.3)}

/* Error / hint */
.cr-err{
  display:flex;align-items:center;gap:7px;
  padding:10px 14px;border-radius:var(--radius);margin-bottom:16px;
  background:var(--red-bg);color:var(--red);
  font-size:13px;font-weight:600;border:1px solid rgba(239,68,68,.12);
}
.cr-hint{
  display:flex;align-items:flex-start;gap:6px;
  font-size:12px;color:var(--text-3);line-height:1.5;margin-top:8px;
}
.cr-hint i{color:var(--accent);flex-shrink:0;margin-top:1px}

/* Divider */
.cr-divider{
  border:none;border-top:1px solid var(--border);
  margin:22px 0;
}
</style>
</head>
<body>

<!-- Topbar -->
<div class="cr-top">
  <div class="cr-top-l">
    <a href="/admin/dashboard.php?page=secteurs" class="cr-back">
      <i class="fas fa-arrow-left"></i> Secteurs
    </a>
    <div class="cr-top-title">
      <i class="fas fa-plus-circle"></i> Nouveau secteur
    </div>
  </div>
  <?php if ($aiOk): ?>
  <span class="cr-ai-badge"><i class="fas fa-robot"></i> <?= $aiLbl ?> disponible</span>
  <?php endif; ?>
</div>

<div class="cr-wrap">

  <?php if ($createErr): ?>
  <div class="cr-err"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($createErr) ?></div>
  <?php endif; ?>

  <!-- ── Suggestions rapides ── -->
  <div class="cr-sec-label"><i class="fas fa-bolt"></i> Creation rapide</div>
  <div class="cr-sugg-grid">
    <?php foreach ($suggestions as $sg): ?>
    <div class="cr-sugg" onclick="CR.useSugg('<?= addslashes($sg['nom']) ?>','<?= $sg['type'] ?>')">
      <div class="cr-sugg-icon" style="background:<?= $sg['color'] ?>">
        <i class="fas <?= $sg['icon'] ?>"></i>
      </div>
      <div>
        <div class="cr-sugg-nom"><?= htmlspecialchars($sg['nom']) ?></div>
        <div class="cr-sugg-type"><?= $sg['type'] === 'commune' ? '&#127755; Commune' : '&#127968; Quartier' ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <hr class="cr-divider">

  <!-- ── Formulaire ── -->
  <form method="POST" id="crForm">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="type_secteur" id="crTypeInput" value="quartier">

    <div class="cr-card">
      <div class="cr-card-hdr">
        <div class="cr-card-hdr-icon"><i class="fas fa-map-location-dot"></i></div>
        <div class="cr-card-hdr-text">
          <h2>Definir le secteur</h2>
          <p>Nom, type et ville &mdash;<?= $aiOk ? " l'IA generera le contenu complet juste apres" : " completez le contenu dans l'editeur" ?></p>
        </div>
      </div>
      <div class="cr-card-body">

        <!-- Nom -->
        <div class="cr-field">
          <label class="cr-label" for="crNom">Nom du secteur / quartier <span style="color:var(--red)">*</span></label>
          <input type="text" name="nom" id="crNom" class="cr-input"
                 placeholder="Ex : Bacalan, Les Chartrons, Talence Centre, Bordeaux Nord..."
                 autocomplete="off" required oninput="CR.onNomChange()">
        </div>

        <!-- Ville + Type -->
        <div class="cr-row2">
          <div class="cr-field">
            <label class="cr-label" for="crVille">Ville</label>
            <input type="text" name="ville" id="crVille" class="cr-input"
                   placeholder="Bordeaux, Merignac..."
                   value="<?= htmlspecialchars($defaultVille) ?>"
                   oninput="CR.onVilleChange()"
                   list="crVilleList">
            <?php if (!empty($villes)): ?>
            <datalist id="crVilleList">
              <?php foreach ($villes as $v): ?>
              <option value="<?= htmlspecialchars($v) ?>">
              <?php endforeach; ?>
            </datalist>
            <?php endif; ?>
          </div>
          <div class="cr-field">
            <label class="cr-label">Code postal (optionnel)</label>
            <input type="text" name="code_postal" class="cr-input"
                   placeholder="Ex : 33000" maxlength="10">
          </div>
        </div>

        <!-- Type -->
        <div class="cr-field">
          <label class="cr-label">Type de zone</label>
          <div class="cr-type-toggle">
            <div class="cr-type-btn active" id="crBtnQuartier" onclick="CR.setType('quartier',this)">
              <i class="fas fa-map-pin"></i>
              <span>Quartier</span>
              <small>Zone intra-ville</small>
            </div>
            <div class="cr-type-btn" id="crBtnCommune" onclick="CR.setType('commune',this)">
              <i class="fas fa-city"></i>
              <span>Commune</span>
              <small>Ville entiere</small>
            </div>
          </div>
        </div>

        <!-- Slug -->
        <div class="cr-field">
          <label class="cr-label">Slug URL <span style="font-weight:400;color:var(--text-3)">(auto-genere)</span></label>
          <div class="cr-slug-row">
            <span class="cr-slug-pfx">votresite.fr/</span>
            <input type="text" name="slug" id="crSlug" class="cr-input"
                   placeholder="bacalan-bordeaux">
            <button type="button" class="cr-slug-gen" onclick="CR.genSlug()">
              <i class="fas fa-magic"></i> Auto
            </button>
          </div>
          <p class="cr-hint">
            <i class="fas fa-info-circle"></i>
            Le slug est l'adresse URL de la page. Il sera genere automatiquement depuis le nom + ville.
          </p>
        </div>

        <!-- Submit -->
        <button type="submit" class="cr-submit">
          <i class="fas fa-<?= $aiOk ? 'wand-magic-sparkles' : 'map-location-dot' ?>"></i>
          <?= $aiOk ? 'Creer et generer le contenu avec l\'IA' : 'Creer le secteur' ?>
        </button>

        <?php if ($aiOk): ?>
        <p style="text-align:center;font-size:11.5px;color:var(--text-3);margin-top:10px">
          <i class="fas fa-robot" style="color:#7c3aed"></i>
          <?= $aiLbl ?> va generer description, atouts, hero et metas SEO automatiquement
        </p>
        <?php endif; ?>

      </div>
    </div>
  </form>

</div>

<script>
const CR = {
  _slugDirty: false,

  useSugg(nom, type) {
    const n = document.getElementById('crNom');
    if(n) { n.value = nom; n.dispatchEvent(new Event('input')); }
    this.setType(type, document.getElementById('crBtn'+type.charAt(0).toUpperCase()+type.slice(1)));
  },

  onNomChange() {
    if (!this._slugDirty) this._autoSlug();
  },

  onVilleChange() {
    if (!this._slugDirty) this._autoSlug();
  },

  _autoSlug() {
    const nom   = document.getElementById('crNom')?.value   || '';
    const ville = document.getElementById('crVille')?.value || '';
    const sl    = document.getElementById('crSlug');
    if(sl) sl.value = this._slugify(nom) + (ville ? '-' + this._slugify(ville) : '');
  },

  genSlug() {
    this._slugDirty = false;
    this._autoSlug();
    this._slugDirty = true;
  },

  _slugify(str) {
    return str.toLowerCase()
      .replace(/[àâ]/g,'a').replace(/[éèêë]/g,'e').replace(/[îï]/g,'i')
      .replace(/[ôö]/g,'o').replace(/[ùûü]/g,'u').replace(/ç/g,'c')
      .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
  },

  setType(val, el) {
    ['quartier','commune'].forEach(t => {
      const btn = document.getElementById('crBtn' + t.charAt(0).toUpperCase() + t.slice(1));
      if(btn) btn.classList.toggle('active', t === val);
    });
    const inp = document.getElementById('crTypeInput');
    if(inp) inp.value = val;
  },
};

// Marquer slug comme modifie si l'utilisateur tape dedans
document.getElementById('crSlug')?.addEventListener('input', () => CR._slugDirty = true);

// Validation
document.getElementById('crForm')?.addEventListener('submit', function(e) {
  const nom = document.getElementById('crNom')?.value.trim();
  if(!nom) { e.preventDefault(); document.getElementById('crNom')?.focus(); }
});

// Auto-focus
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('crNom')?.focus();
});
</script>

</body>
</html>