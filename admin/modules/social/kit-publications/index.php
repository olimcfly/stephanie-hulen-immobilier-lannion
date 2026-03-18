<?php
// ======================================================
// Module KIT PUBLICATIONS RÉSEAUX SOCIAUX
// /admin/modules/social/kit-publications/index.php
// ======================================================

if (!defined('ADMIN_ROUTER')) {
    die("Accès direct interdit.");
}

// ── Récupération de la ville depuis la DB ──────────────
$ville_defaut = 'Bordeaux';
try {
    // Tente de récupérer depuis settings (clés courantes)
    $db_conn = isset($pdo) ? $pdo : (isset($db) ? $db : null);
    if ($db_conn) {
        foreach (['ville','city','advisor_city','site_city'] as $key) {
            $stmt = $db_conn->prepare("SELECT value FROM settings WHERE name = ? LIMIT 1");
            $stmt->execute([$key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['value'])) { $ville_defaut = $row['value']; break; }
        }
    }
} catch (Exception $e) { /* fallback silencieux */ }

$page_title = "Kit Publications Réseaux Sociaux";
ob_start();
?>

<style>
/* ═══════════════════════════════════════════════════════
   KIT PUBLICATIONS — Styles complets
═══════════════════════════════════════════════════════ */
:root {
    --kp-dark:    #0f172a;
    --kp-dark2:   #1e293b;
    --kp-dark3:   #334155;
    --kp-slate:   #64748b;
    --kp-muted:   #94a3b8;
    --kp-border:  #e2e8f0;
    --kp-bg:      #f8fafc;
    --kp-white:   #ffffff;
    --kp-gold:    #f59e0b;
    --kp-gold-bg: #fffbeb;
    --radius:     10px;
    --shadow:     0 2px 12px rgba(0,0,0,.07);
}

.kp-wrap {
    font-family: 'Segoe UI', system-ui, sans-serif;
    max-width: 960px;
    margin: 0 auto;
    padding: 0 0 60px;
    color: #1e293b;
}

/* ── Header ── */
.kp-hero {
    background: linear-gradient(135deg, var(--kp-dark) 0%, var(--kp-dark2) 60%, #0d2137 100%);
    border-radius: 14px;
    padding: 22px 26px;
    margin-bottom: 18px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    box-shadow: var(--shadow);
}
.kp-hero-left .kp-overline {
    color: var(--kp-gold);
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.kp-hero-left h1 {
    color: #fff;
    font-size: 20px;
    font-weight: 800;
    margin: 0 0 4px;
}
.kp-hero-left p {
    color: var(--kp-muted);
    font-size: 12px;
    margin: 0;
}
.kp-hero-stats {
    display: flex;
    gap: 8px;
}
.kp-stat-pill {
    background: rgba(255,255,255,.08);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 10px;
    padding: 8px 14px;
    text-align: center;
}
.kp-stat-pill .num { color: var(--kp-gold); font-size: 20px; font-weight: 800; display: block; }
.kp-stat-pill .lbl { color: var(--kp-muted); font-size: 10px; display: block; }

/* ── Card base ── */
.kp-card {
    background: var(--kp-white);
    border: 1px solid var(--kp-border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
    margin-bottom: 14px;
}
.kp-card-head {
    background: linear-gradient(135deg, var(--kp-dark), var(--kp-dark2));
    padding: 13px 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.kp-card-head .title { color: #fff; font-weight: 700; font-size: 13px; }
.kp-card-head .subtitle { color: var(--kp-muted); font-size: 11px; margin-top: 2px; }
.kp-card-body { padding: 16px; }

/* ── Ville selector ── */
.kp-ville-active {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(245,158,11,.15);
    color: #92400e;
    border-radius: 20px;
    padding: 3px 12px;
    font-size: 12px;
    font-weight: 700;
}
.kp-ville-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    max-height: 100px;
    overflow-y: auto;
    margin-top: 8px;
}
.kp-ville-btn {
    background: var(--kp-bg);
    color: var(--kp-slate);
    border: 1px solid var(--kp-border);
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 11px;
    font-weight: 500;
    cursor: pointer;
    transition: all .15s;
}
.kp-ville-btn:hover { background: var(--kp-border); }
.kp-ville-btn.active {
    background: var(--kp-gold);
    color: #fff;
    border-color: var(--kp-gold);
    font-weight: 700;
}
.kp-ville-search {
    width: 100%;
    border: 1px solid var(--kp-border);
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 12px;
    outline: none;
    margin-bottom: 6px;
    box-sizing: border-box;
}
.kp-ville-search:focus { border-color: #93c5fd; }

/* ── Persona selector ── */
.kp-persona-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 7px;
    margin-bottom: 10px;
}
.kp-persona-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    border-radius: 20px;
    padding: 6px 14px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    border: 1.5px solid var(--kp-border);
    background: var(--kp-bg);
    color: var(--kp-slate);
    transition: all .2s;
}
.kp-persona-info {
    border-radius: 8px;
    padding: 10px 14px;
    border-left: 4px solid;
    display: flex;
    align-items: center;
    gap: 14px;
}
.kp-persona-info .emoji { font-size: 22px; }
.kp-persona-info .name { font-weight: 700; font-size: 12px; }
.kp-persona-info .meta { color: var(--kp-slate); font-size: 11px; margin-top: 2px; }

/* ── View switcher ── */
.kp-views {
    display: flex;
    gap: 8px;
    margin-bottom: 14px;
}
.kp-view-btn {
    flex: 1;
    border: 1.5px solid var(--kp-border);
    border-radius: var(--radius);
    padding: 10px 12px;
    cursor: pointer;
    background: var(--kp-white);
    color: var(--kp-slate);
    transition: all .2s;
    text-align: center;
}
.kp-view-btn.active {
    background: var(--kp-dark);
    color: #fff;
    border-color: var(--kp-dark);
}
.kp-view-btn .vb-label { font-weight: 700; font-size: 12px; display: block; }
.kp-view-btn .vb-sub { font-size: 10px; opacity: .7; margin-top: 2px; display: block; }

/* ── Network tabs ── */
.kp-tabs {
    display: flex;
    background: var(--kp-bg);
    border-bottom: 1px solid var(--kp-border);
}
.kp-tab {
    flex: 1;
    padding: 10px 4px;
    border: none;
    background: transparent;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 11px;
    font-weight: 500;
    color: var(--kp-muted);
    transition: all .2s;
}
.kp-tab.active {
    background: var(--kp-white);
    font-weight: 700;
}

/* ── Copy button ── */
.kp-copy {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--kp-bg);
    color: var(--kp-slate);
    border: 1px solid var(--kp-border);
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 11px;
    font-weight: 600;
    cursor: pointer;
    transition: all .2s;
    white-space: nowrap;
}
.kp-copy:hover { background: var(--kp-border); }
.kp-copy.copied { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }

/* ── Content blocks ── */
.kp-hook-block {
    background: #fff8f0;
    border: 1px solid #fed7aa;
    border-radius: 8px;
    padding: 12px 14px;
    margin-bottom: 10px;
}
.kp-hook-block .block-label {
    font-size: 11px;
    color: #c2410c;
    font-weight: 700;
    margin-bottom: 6px;
}
.kp-hook-text {
    color: #1e293b;
    font-size: 13.5px;
    font-style: italic;
    margin-bottom: 8px;
    line-height: 1.6;
}

.kp-script-block {
    background: var(--kp-bg);
    border: 1px solid var(--kp-border);
    border-radius: 8px;
    padding: 12px 14px;
    margin-bottom: 10px;
}
.kp-script-block .block-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}
.kp-script-block .block-label {
    font-size: 11px;
    color: var(--kp-slate);
    font-weight: 700;
}
.kp-script-pre {
    margin: 0;
    font-family: inherit;
    font-size: 11.5px;
    color: #334155;
    white-space: pre-wrap;
    line-height: 1.75;
}

.kp-tip-block {
    border-radius: 8px;
    padding: 10px 14px;
}
.kp-tip-block .block-label {
    font-size: 11px;
    font-weight: 700;
    margin-bottom: 6px;
}
.kp-tip-block ul {
    margin: 0;
    padding-left: 16px;
    font-size: 11px;
    line-height: 2;
}

.kp-alert {
    display: flex;
    gap: 10px;
    border-radius: 8px;
    padding: 10px 14px;
    margin-bottom: 10px;
    font-size: 11px;
    line-height: 1.6;
}

/* ── Hashtag pyramid ── */
.kp-pyramid { display: flex; flex-direction: column; gap: 8px; align-items: center; }
.kp-pyramid-tier { border-radius: 8px; padding: 10px 14px; border-left: 4px solid; }
.kp-tier-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.kp-tier-badge { color: #fff; border-radius: 4px; padding: 2px 7px; font-size: 10px; font-weight: 700; }
.kp-tier-range { color: var(--kp-slate); font-size: 10px; }
.kp-tier-count { font-size: 10px; font-weight: 600; }
.kp-tier-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 6px; }
.kp-tag-pill { border-radius: 20px; padding: 3px 9px; font-size: 10.5px; font-weight: 600; border: 1px solid; }
.kp-tier-desc { color: var(--kp-slate); font-size: 10px; font-style: italic; }

/* ── Guide grid ── */
.kp-guide-tabs { display: flex; background: var(--kp-bg); border-bottom: 1px solid var(--kp-border); }
.kp-guide-tab {
    flex: 1; padding: 10px 4px; border: none; background: transparent;
    border-bottom: 3px solid transparent; cursor: pointer; font-size: 11px;
    font-weight: 500; color: var(--kp-muted); transition: all .2s;
}
.kp-guide-tab.active { background: var(--kp-white); font-weight: 700; }

.kp-principes-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 14px; }
.kp-principe-card {
    background: var(--kp-bg);
    border-radius: 8px;
    padding: 10px 12px;
    border-left: 3px solid;
}
.kp-principe-icon { font-size: 16px; margin-bottom: 5px; }
.kp-principe-title { color: #1e293b; font-weight: 700; font-size: 11px; margin-bottom: 3px; }
.kp-principe-desc { color: var(--kp-slate); font-size: 10.5px; line-height: 1.6; }

.kp-timing-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.kp-timing-item .t-label { color: #0369a1; font-size: 10px; font-weight: 600; margin-bottom: 2px; }
.kp-timing-item .t-val { color: #1e293b; font-size: 11px; font-weight: 500; }

/* ── Toast ── */
.kp-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #1e293b;
    color: #fff;
    padding: 10px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    box-shadow: 0 4px 20px rgba(0,0,0,.25);
    opacity: 0;
    transform: translateY(10px);
    transition: all .3s;
    z-index: 9999;
    pointer-events: none;
}
.kp-toast.show { opacity: 1; transform: translateY(0); }

@media (max-width: 680px) {
    .kp-hero { flex-direction: column; }
    .kp-hero-stats { flex-wrap: wrap; }
    .kp-principes-grid, .kp-timing-grid { grid-template-columns: 1fr; }
    .kp-views { flex-direction: column; }
}
</style>

<div class="kp-wrap">

  <!-- TOAST -->
  <div class="kp-toast" id="kpToast">✓ Copié !</div>

  <!-- HERO -->
  <div class="kp-hero">
    <div class="kp-hero-left">
      <div class="kp-overline">📱 ÉCOSYSTÈME IMMO LOCAL+</div>
      <h1>Kit Publications Réseaux Sociaux</h1>
      <p>Méthode NeuroPersona · 5 Personas · Stratégie Hashtag · Guide Plateformes</p>
    </div>
    <div class="kp-hero-stats">
      <div class="kp-stat-pill"><span class="num">5</span><span class="lbl">Personas</span></div>
      <div class="kp-stat-pill"><span class="num">4</span><span class="lbl">Réseaux</span></div>
      <div class="kp-stat-pill"><span class="num">20+</span><span class="lbl">Scripts</span></div>
    </div>
  </div>

  <!-- VILLE -->
  <div class="kp-card">
    <div class="kp-card-body">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
        <span style="font-size:16px;">📍</span>
        <span style="font-weight:700;font-size:13px;color:#1e293b;">Ville ciblée</span>
        <span class="kp-ville-active" id="villeActiveLabel">📍 <?= htmlspecialchars($ville_defaut) ?></span>
      </div>
      <input type="text" class="kp-ville-search" id="villeSearch" placeholder="Filtrer les villes…">
      <div class="kp-ville-grid" id="villeGrid"></div>
    </div>
  </div>

  <!-- PERSONA -->
  <div class="kp-card">
    <div class="kp-card-body">
      <div style="font-weight:700;font-size:13px;color:#1e293b;margin-bottom:10px;">👤 Persona actif</div>
      <div class="kp-persona-grid" id="personaGrid"></div>
      <div class="kp-persona-info" id="personaInfo"></div>
    </div>
  </div>

  <!-- VIEW SWITCHER -->
  <div class="kp-views">
    <button class="kp-view-btn active" data-view="scripts">
      <span class="vb-label">📝 Scripts &amp; Posts</span>
      <span class="vb-sub">Contenu prêt à publier</span>
    </button>
    <button class="kp-view-btn" data-view="hashtags">
      <span class="vb-label">🏷️ Stratégie Hashtags</span>
      <span class="vb-sub">Pyramide de volumes</span>
    </button>
    <button class="kp-view-btn" data-view="guide">
      <span class="vb-label">📚 Guide Plateformes</span>
      <span class="vb-sub">Méthode Syndicate</span>
    </button>
  </div>

  <!-- VIEW: SCRIPTS -->
  <div id="view-scripts">
    <div class="kp-card">
      <div class="kp-tabs" id="networkTabs">
        <button class="kp-tab active" data-net="tiktok" style="border-bottom-color:#010101;color:#010101;">🎵 TikTok</button>
        <button class="kp-tab" data-net="facebook">📘 Facebook</button>
        <button class="kp-tab" data-net="linkedin">💼 LinkedIn</button>
        <button class="kp-tab" data-net="gmb">📍 GMB</button>
      </div>
      <div class="kp-card-body" id="scriptContent"></div>
    </div>
  </div>

  <!-- VIEW: HASHTAGS -->
  <div id="view-hashtags" style="display:none;">
    <div class="kp-card">
      <div class="kp-card-head">
        <div>
          <div class="title">🏷️ Stratégie Hashtags</div>
          <div class="subtitle">Pyramide de volumes · 11 hashtags optimisés</div>
        </div>
        <button class="kp-copy" id="copyAllTags" onclick="kpCopyAll()">📋 Tous copier</button>
      </div>
      <div class="kp-card-body">
        <div class="kp-pyramid" id="hashtagPyramid"></div>
        <div style="margin-top:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;">
          <div style="color:#15803d;font-size:11px;"><strong>✅ Mix final :</strong> 3 niche + 3 local + 3 large + 2 marque = <strong>11 hashtags</strong></div>
          <button class="kp-copy" onclick="kpCopyAll()">📋 Copier le mix</button>
        </div>
        <div style="margin-top:8px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:10px 14px;">
          <div style="color:#9a3412;font-size:10.5px;font-weight:600;margin-bottom:4px;">💡 Règle d'or</div>
          <div style="color:#78350f;font-size:10.5px;line-height:1.6;">Utilisez des hashtags <strong>pertinents et dans la langue de votre audience</strong>. Pour TikTok, la description est limitée à 150 car. — choisissez vos 5 meilleurs.</div>
        </div>
      </div>
    </div>
    <div class="kp-card">
      <div class="kp-card-head">
        <div>
          <div class="title">📊 Adaptation par plateforme</div>
          <div class="subtitle">Règles spécifiques de chaque réseau</div>
        </div>
      </div>
      <div class="kp-card-body" id="hashtagPlatformes" style="display:grid;grid-template-columns:1fr 1fr;gap:10px;"></div>
    </div>
  </div>

  <!-- VIEW: GUIDE -->
  <div id="view-guide" style="display:none;">
    <div class="kp-card">
      <div class="kp-card-head">
        <div>
          <div class="title">📚 Guide Réseaux Sociaux</div>
          <div class="subtitle">Stratégie complète par plateforme · Méthode Syndicate</div>
        </div>
      </div>
      <div class="kp-guide-tabs" id="guideTabs">
        <button class="kp-guide-tab active" data-guide="tiktok">🎵 TikTok</button>
        <button class="kp-guide-tab" data-guide="facebook">📘 Facebook</button>
        <button class="kp-guide-tab" data-guide="linkedin">💼 LinkedIn</button>
        <button class="kp-guide-tab" data-guide="gmb">📍 GMB</button>
      </div>
      <div class="kp-card-body" id="guideContent"></div>
    </div>
  </div>

</div><!-- /.kp-wrap -->

<script>
// ══════════════════════════════════════════════════════════
//  KIT PUBLICATIONS — Moteur JS vanilla
// ══════════════════════════════════════════════════════════

const VILLE_DEFAUT = <?= json_encode($ville_defaut) ?>;

const VILLES = ["Bordeaux","Aix-en-Provence","Nantes","Lyon","Toulouse","Montpellier","Rennes","Strasbourg","Lille","Nice","Marseille","Grenoble","Tours","Nîmes","Perpignan","Béziers","Clermont-Ferrand","Dijon","Metz","Reims","Le Havre","Rouen","Angers","Saint-Étienne","Toulon","Pau","Bayonne","La Rochelle","Valence","Poitiers","Limoges","Caen","Orléans","Amiens"];

const PERSONAS = [
  {
    id:"primo", emoji:"🛡️", label:"Primo-Accédant", sublabel:"Jeune Couple",
    levier:"Sécurité", stars:5, color:"#2563eb", bg:"#eff6ff",
    hook: v => `Vous payez un loyer à ${v} depuis 5 ans… et vous n'avez rien en retour. 😔`,
    script: v => `[Caméra face, ton naturel, sourire bienveillant]\n\n"Honnêtement… chaque mois que vous payez votre loyer à ${v}, c'est de l'argent qui part dans la poche de quelqu'un d'autre.\n\nEt je ne dis pas ça pour vous culpabiliser. Parce que personne ne vous a expliqué comment ça marche.\n\nJe travaille avec des jeunes couples à ${v} depuis des années. Et la plupart pensaient que devenir propriétaire, c'était 'pour plus tard'.\n\nMais voilà ce que j'ai appris : le bon moment, c'est souvent maintenant. Même sans gros apport. Même avec un salaire moyen.\n\nJe publie chaque semaine des conseils concrets pour les futurs propriétaires à ${v}. Suivez-moi. 🏠"`,
    fb_post: v => `📍 ${v} — Vous louez depuis plusieurs années ?\n\nJe rencontre chaque semaine des couples à ${v} qui pensaient ne pas pouvoir acheter… et qui sont aujourd'hui propriétaires.\n\nLe frein numéro 1 ? L'information. Pas le budget.\n\nDites-moi en commentaire : vous en êtes où dans votre projet ? 👇`,
    tiktok_tips: ["Publiez entre 18h et 21h","Répondez aux 10 premiers commentaires","Épinglez votre meilleure vidéo","Ajoutez la ville dans les 3 premiers mots"],
    fb_tips: ["Publiez Mar–Jeu en semaine","Posez une question pour engager","Répondez aux commentaires en moins d'1h","Taguez la localisation sur votre post"],
    hashtags: {
      niche: ["#PremierAchat","#PrimoAccédant","#AchatImmobilier"],
      local: v => [`#Immo${v.replace(/[^a-zA-Z]/g,"")}`,`#${v.replace(/[^a-zA-Z]/g,"")}Immobilier`,`#${v.replace(/[^a-zA-Z]/g,"")}`],
      marque: ["#ÉcosystèmeImmo","#ConseillerLocal"],
      large: ["#ImmobilierFrance","#Propriétaire","#ConseilsImmo"]
    }
  },
  {
    id:"famille", emoji:"🎯", label:"Famille en Expansion", sublabel:"Besoin d'espace",
    levier:"Confort & Avenir", stars:5, color:"#16a34a", bg:"#f0fdf4",
    hook: v => `Votre appartement à ${v} est devenu trop petit… mais vous ne savez pas par où commencer ? 🏡`,
    script: v => `[Ton chaleureux, intérieur lumineux]\n\n"Il y a quelques mois, j'ai accompagné une famille à ${v}. Deux enfants, un 3 pièces devenu trop étroit, et la peur de ne pas trouver.\n\nOn a trouvé leur maison en 6 semaines. Dans leur quartier. Dans leur budget.\n\nLa différence ? Ils savaient exactement ce qu'ils cherchaient. Et ils avaient la bonne méthode.\n\nSi votre famille grandit et que vous cherchez plus grand à ${v}, je vous aide à définir votre projet gratuitement. Lien dans ma bio. 🏡"`,
    fb_post: v => `🏡 Cherchez-vous plus grand à ${v} ?\n\nQuand la famille s'agrandit, chaque mètre carré compte. Et le marché à ${v} évolue vite.\n\nJe connais les quartiers, les bons plans, et les pièges à éviter.\n\n👉 Partagez ce post si vous aussi vous cherchez de l'espace ! Et dites-moi : c'est pour quand votre projet ?`,
    tiktok_tips: ["Ciblez les heures 12h et 20h","Montrez des espaces de vie concrets","Utilisez des transitions maison → famille","Intégrez un quiz 'Quel type de maison ?'"],
    fb_tips: ["Publiez le weekend (familles disponibles)","Boostez les posts avec photos de maisons","Créez un groupe local 'Acheteurs [Ville]'","Partagez dans les groupes parentaux locaux"],
    hashtags: {
      niche: ["#VieEnFamille","#MaisonFamiliale","#AchatMaison"],
      local: v => [`#${v.replace(/[^a-zA-Z]/g,"")}Maison`,`#Famille${v.replace(/[^a-zA-Z]/g,"")}`,`#${v.replace(/[^a-zA-Z]/g,"")}`],
      marque: ["#ÉcosystèmeImmo","#ConseillerLocal"],
      large: ["#ImmobilierFrance","#MaisonDeFamille","#ChercheMaison"]
    }
  },
  {
    id:"vendeur", emoji:"🏷️", label:"Vendeur Pressé", sublabel:"Mutation / Divorce",
    levier:"Rapidité & Prix Juste", stars:5, color:"#dc2626", bg:"#fef2f2",
    hook: v => `Vous devez vendre votre bien à ${v} rapidement… sans brader votre prix ? ⏱️`,
    script: v => `[Direct, rassurant, professionnel]\n\n"Vendre vite ne veut pas dire vendre moins cher. C'est une idée reçue que j'entends souvent à ${v}.\n\nJ'ai accompagné des vendeurs qui avaient 3 semaines pour conclure. On a obtenu le bon prix. Parce qu'on a suivi la bonne méthode.\n\nLa clé ? Une estimation juste dès le départ, une mise en valeur du bien, et une diffusion ciblée sur les acheteurs déjà en recherche active à ${v}.\n\nVous avez un délai serré ? Contactez-moi. Le premier échange est gratuit. 📞"`,
    fb_post: v => `⏱️ Vendre rapidement à ${v} — c'est possible !\n\nBeaucoup pensent qu'une vente rapide = une moins bonne offre. Ce n'est pas vrai.\n\nLa vitesse de vente dépend surtout de : l'estimation précise, la mise en valeur du bien, et le réseau d'acheteurs actifs.\n\nVous envisagez de vendre à ${v} ? Dites-moi en MP ou en commentaire. Je vous réponds sous 24h. 👇`,
    tiktok_tips: ["Publiez en semaine 9h-11h","Montrez avant/après d'un bien","Format 'Erreur N°1 des vendeurs'","Story-tell une vente réussie"],
    fb_tips: ["Ciblez les propriétaires 40-60 ans","Utilisez Facebook Marketplace aussi","Publiez des preuves (délai, prix obtenu)","Sponsorisez sur code postal ciblé"],
    hashtags: {
      niche: ["#VendreSonBien","#EstimationGratuite","#MandatVente"],
      local: v => [`#Vente${v.replace(/[^a-zA-Z]/g,"")}`,`#${v.replace(/[^a-zA-Z]/g,"")}Vente`,`#${v.replace(/[^a-zA-Z]/g,"")}`],
      marque: ["#ÉcosystèmeImmo","#ConseillerLocal"],
      large: ["#VenteImmobilière","#ImmobilierFrance","#PrixImmo"]
    }
  },
  {
    id:"investisseur", emoji:"📈", label:"Investisseur Local", sublabel:"Rendement & Patrimoine",
    levier:"Rentabilité", stars:4, color:"#7c3aed", bg:"#f5f3ff",
    hook: v => `Investir à ${v} en 2025 : quels quartiers offrent encore du rendement ? 📊`,
    script: v => `[Ton expert, chiffres concrets]\n\n"Tout le monde parle d'investir. Peu de gens savent où investir à ${v} aujourd'hui.\n\nVoici ce que les données nous disent : certains quartiers de ${v} offrent encore 5 à 7% de rendement brut. Avec une gestion locative optimisée.\n\nLe secret ? Acheter là où la demande locative est structurelle — étudiants, jeunes actifs, familles — et pas là où tout le monde regarde.\n\nJe partage chaque semaine des analyses concrètes du marché à ${v}. Abonnez-vous si vous voulez investir intelligemment. 📈"`,
    fb_post: v => `📊 Analyse marché locatif — ${v}\n\nLe rendement brut moyen sur ${v} se situe entre 4 et 7% selon les secteurs.\n\nCertains quartiers sous-cotés offrent encore de belles opportunités. D'autres sont à éviter.\n\nJe prépare une analyse complète. Commentez "ANALYSE" pour la recevoir en priorité. 👇`,
    tiktok_tips: ["Publiez des chiffres concrets en vignette","Format 'Top 3 quartiers à [Ville]'","Utilisez des graphiques simples","Ciblez les 25-45 ans actifs"],
    fb_tips: ["Groupe 'Investisseurs immobilier [Ville]'","LinkedIn en parallèle (très efficace)","Partagez des études de cas chiffrées","Reciblage publicité sur visiteurs site"],
    hashtags: {
      niche: ["#InvestirImmo","#RendementLocatif","#ImmobilierLocatif"],
      local: v => [`#Investir${v.replace(/[^a-zA-Z]/g,"")}`,`#${v.replace(/[^a-zA-Z]/g,"")}Invest`,`#${v.replace(/[^a-zA-Z]/g,"")}`],
      marque: ["#ÉcosystèmeImmo","#PatrimoineImmo"],
      large: ["#InvestissementImmobilier","#Cashflow","#LibertéFinancière"]
    }
  },
  {
    id:"expatrie", emoji:"✈️", label:"Expatrié / Retour France", sublabel:"Achat à distance",
    levier:"Confiance & Sécurité", stars:3, color:"#0891b2", bg:"#ecfeff",
    hook: v => `Acheter un bien à ${v} depuis l'étranger… sans se faire avoir ? 🌍`,
    script: v => `[Empathique, rassurant]\n\n"Acheter à distance, c'est stressant. Je le comprends.\n\nVous ne pouvez pas visiter 15 biens. Vous n'êtes pas là pour négocier en direct. Et vous avez peur de rater quelque chose d'important.\n\nJ'accompagne des expatriés qui souhaitent acquérir à ${v}. Je suis leurs yeux sur le terrain. Je visite, j'analyse, je négocie pour eux.\n\nSi vous préparez votre retour en France et que ${v} est votre cible, on peut en parler. Tout se fait à distance, en visio. 📱"`,
    fb_post: v => `🌍 Vous êtes expatrié et vous pensez à acheter à ${v} ?\n\nNombre de mes clients ont finalisé leur achat depuis l'étranger. En toute sécurité. Sans mauvaise surprise.\n\nLa clé : un conseiller de confiance sur place, qui connaît ${v} quartier par quartier.\n\nRacontez-moi votre projet en commentaire ou en MP. Je vous réponds. 🏠`,
    tiktok_tips: ["Format 'Guide achat à distance'","Témoignages vidéo courts (15 sec)","Sous-titres obligatoires (visionnage sans son)","Ciblez groupes Français expatriés"],
    fb_tips: ["Rejoignez les groupes 'Français à [pays]'","Publiez dans les groupes de retour en France","Ton rassurant et pédagogique","Témoignages vidéo clients = or massif"],
    hashtags: {
      niche: ["#FrançaisÀLétranger","#AchatÀDistance","#RetourFrance"],
      local: v => [`#${v.replace(/[^a-zA-Z]/g,"")}Immo`,`#Expatrié${v.replace(/[^a-zA-Z]/g,"")}`,`#${v.replace(/[^a-zA-Z]/g,"")}`],
      marque: ["#ÉcosystèmeImmo","#ConseillerLocal"],
      large: ["#ExpatsEnFrance","#ImmobilierFrance","#ProjetImmo"]
    }
  }
];

const GUIDE_DATA = {
  tiktok: {
    color:"#010101", accent:"#ff0050", subtitle:"Portée organique maximale",
    principes:[
      { icon:"🔄", title:"L'algorithme en 4 étapes", desc:"Test 10% → Audience Fidèle → Page Explorer → Ultra-Viralité. Chaque post repart de zéro." },
      { icon:"⚡", title:"Le Hook = tout", desc:"Les 3 premières secondes décident. Question, chiffre choc ou problème direct." },
      { icon:"⏱️", title:"Durée optimale", desc:"45-90 secondes pour l'immobilier. Assez long pour la valeur, assez court pour la complétion." },
      { icon:"🚫", title:"Pas de lien dans le post", desc:"TikTok pénalise les posts avec URL. Renvoyez vers la bio avec 'Lien dans ma bio 👆'" }
    ],
    timing:{ jours:"Tous les jours si possible", heures:"7h-9h · 12h-14h · 18h-21h", frequence:"1x/jour minimum 30 jours", best:"Mer, Jeu, Ven" }
  },
  facebook: {
    color:"#1877f2", accent:"#0d65d8", subtitle:"Engagement communautaire local",
    principes:[
      { icon:"📌", title:"Lien en 1er commentaire", desc:"Un lien dans le texte = -70% de portée. Publiez le texte seul, lien en commentaire épinglé." },
      { icon:"💬", title:"Question = reach", desc:"Poser une question directe déclenche les commentaires. L'algo booste les posts avec interactions rapides." },
      { icon:"📸", title:"Photos locales", desc:"Vos photos (pas de stock) avec la ville en arrière-plan. Authenticité = confiance = partages." },
      { icon:"📍", title:"Localisation obligatoire", desc:"Taguez toujours votre ville sur le post. Visibilité dans les recherches et suggestions locales." }
    ],
    timing:{ jours:"Mar, Mer, Jeu", heures:"9h-10h · 19h-21h", frequence:"4-5x/semaine", best:"Mercredi 19h-20h" }
  },
  linkedin: {
    color:"#0a66c2", accent:"#084fa0", subtitle:"Autorité & réseau professionnel",
    principes:[
      { icon:"✍️", title:"Les 2 premières lignes = tout", desc:"Avant 'Voir plus', le lecteur doit être accroché. Hook fort, chiffre ou question directe." },
      { icon:"📌", title:"Lien en commentaire épinglé", desc:"Même règle que Facebook : lien dans le texte pénalise la portée." },
      { icon:"📏", title:"Longueur idéale : 1 900 car.", desc:"Les posts longs (1 500-2 000 car.) performent mieux. Racontez une histoire complète avec chiffres." },
      { icon:"🎭", title:"Ton storytelling pro", desc:"'J'ai accompagné un client cette semaine…' > 'Conseil immobilier #4'" }
    ],
    timing:{ jours:"Mar, Mer, Jeu", heures:"8h-10h · 12h · 17h-18h", frequence:"3-4x/semaine", best:"Mardi 8h" }
  },
  gmb: {
    color:"#34a853", accent:"#2a8a44", subtitle:"SEO local & visibilité Google",
    principes:[
      { icon:"🔍", title:"100 premiers caractères = aperçu", desc:"Dans la recherche Google, seuls les 100 premiers caractères sont visibles. Commencez par la valeur clé." },
      { icon:"📅", title:"Post visible 7 jours", desc:"Les posts GMB expirent après 7 jours. Républiez chaque semaine pour maintenir la visibilité." },
      { icon:"🚫", title:"Jamais de lien ni de tel", desc:"Google pénalise les posts avec liens ou numéros. Utilisez uniquement le bouton CTA natif." },
      { icon:"📸", title:"Photos originales obligatoires", desc:"Pas de stock. Photos réelles à 1200×900 px. Google favorise les profils avec photos authentiques." }
    ],
    timing:{ jours:"Tous les 7 jours", heures:"Matin (9h-11h)", frequence:"1x/semaine minimum", best:"Lundi matin" }
  }
};

const NET_COLORS = {
  tiktok:"#010101", facebook:"#1877f2", linkedin:"#0a66c2", gmb:"#34a853"
};

// ── State ──
let state = {
  ville: VILLE_DEFAUT,
  persona: PERSONAS[0],
  view: 'scripts',
  net: 'tiktok',
  guide: 'tiktok'
};

// ── Toast ──
function kpToast(msg) {
  const t = document.getElementById('kpToast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2000);
}

// ── Copy ──
function kpCopy(text, btn) {
  navigator.clipboard.writeText(text).then(() => {
    if (btn) { btn.classList.add('copied'); btn.textContent = '✓ Copié !'; setTimeout(() => { btn.classList.remove('copied'); btn.textContent = '📋 Copier'; }, 2000); }
    kpToast('✓ Copié dans le presse-papier !');
  });
}

function kpCopyAll() {
  const p = state.persona;
  const v = state.ville;
  const tags = [...p.hashtags.niche, ...p.hashtags.local(v), ...p.hashtags.large, ...p.hashtags.marque].join(' ');
  navigator.clipboard.writeText(tags).then(() => kpToast('✓ 11 hashtags copiés !'));
}

// ── Render ville ──
function renderVilles() {
  const search = document.getElementById('villeSearch').value.toLowerCase();
  const grid = document.getElementById('villeGrid');
  const filtered = VILLES.filter(v => v.toLowerCase().includes(search));
  grid.innerHTML = filtered.map(v =>
    `<button class="kp-ville-btn${v===state.ville?' active':''}" onclick="setVille('${v}')">${v===state.ville?'✓ ':''}${v}</button>`
  ).join('');
}

function setVille(v) {
  state.ville = v;
  document.getElementById('villeActiveLabel').textContent = '📍 ' + v;
  renderVilles();
  renderPersonaInfo();
  renderContent();
}

// ── Render persona ──
function renderPersonas() {
  const grid = document.getElementById('personaGrid');
  grid.innerHTML = PERSONAS.map(p =>
    `<button class="kp-persona-btn" data-pid="${p.id}" onclick="setPersona('${p.id}')"
      style="background:${state.persona.id===p.id?p.color:'#f8fafc'};color:${state.persona.id===p.id?'#fff':'#475569'};border-color:${state.persona.id===p.id?p.color:'#e2e8f0'};">
      ${p.emoji} ${p.label} ${'★'.repeat(p.stars)}</button>`
  ).join('');
}

function renderPersonaInfo() {
  const p = state.persona;
  document.getElementById('personaInfo').style.cssText = `background:${p.bg};border-left-color:${p.color};`;
  document.getElementById('personaInfo').innerHTML =
    `<span class="emoji">${p.emoji}</span>
     <div>
       <div class="name" style="color:${p.color};">${p.label} — ${p.sublabel}</div>
       <div class="meta">Levier : <strong>${p.levier}</strong> · Priorité : ${'★'.repeat(p.stars)}${'☆'.repeat(5-p.stars)}</div>
     </div>`;
}

function setPersona(id) {
  state.persona = PERSONAS.find(p => p.id === id);
  renderPersonas();
  renderPersonaInfo();
  renderContent();
}

// ── Stars helper ──
function esc(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// ── Render script content ──
function renderContent() {
  if (state.view === 'scripts') renderScripts();
  if (state.view === 'hashtags') renderHashtags();
  if (state.view === 'guide') renderGuide();
}

function makeCopyBtn(text, label) {
  const id = 'cb_' + Math.random().toString(36).slice(2,7);
  // Store text in a safe way
  window._kpCopyTexts = window._kpCopyTexts || {};
  window._kpCopyTexts[id] = text;
  return `<button class="kp-copy" id="${id}" onclick="kpCopyById('${id}')" >📋 ${label||'Copier'}</button>`;
}

function kpCopyById(id) {
  const text = (window._kpCopyTexts||{})[id] || '';
  const btn = document.getElementById(id);
  navigator.clipboard.writeText(text).then(() => {
    if (btn) { btn.classList.add('copied'); btn.textContent = '✓ Copié !'; setTimeout(() => { btn.classList.remove('copied'); btn.innerHTML = '📋 Copier'; }, 2000); }
    kpToast('✓ Copié !');
  });
}

function renderScripts() {
  const p = state.persona;
  const v = state.ville;
  const c = document.getElementById('scriptContent');
  let html = '';

  if (state.net === 'tiktok') {
    const hook = p.hook(v);
    const script = p.script(v);
    html = `
      <div class="kp-hook-block">
        <div class="block-label">⚡ Hook — 1ère seconde</div>
        <div class="kp-hook-text">"${esc(hook)}"</div>
        ${makeCopyBtn(hook, 'Copier le hook')}
      </div>
      <div class="kp-script-block">
        <div class="block-head">
          <div class="block-label">📋 Script complet (60 sec)</div>
          ${makeCopyBtn(script)}
        </div>
        <pre class="kp-script-pre">${esc(script)}</pre>
      </div>
      <div class="kp-tip-block" style="background:#f0f9ff;border:1px solid #bae6fd;">
        <div class="block-label" style="color:#0369a1;">💡 Conseils TikTok — ${esc(v)}</div>
        <ul style="color:#0c4a6e;">${p.tiktok_tips.map(t=>`<li>${esc(t)}</li>`).join('')}</ul>
      </div>`;
  } else if (state.net === 'facebook') {
    const post = p.fb_post(v);
    html = `
      <div class="kp-script-block" style="background:#f0f8ff;border-color:#bfdbfe;">
        <div class="block-head">
          <div class="block-label" style="color:#1d4ed8;">📝 Post Facebook optimisé</div>
          ${makeCopyBtn(post)}
        </div>
        <pre class="kp-script-pre" style="color:#1e3a5f;">${esc(post)}</pre>
      </div>
      <div class="kp-alert" style="background:#fef2f2;border:1px solid #fecaca;">
        <span>⚠️</span>
        <div style="color:#991b1b;"><strong>Règle d'or Facebook :</strong> Ne mettez JAMAIS de lien dans le texte du post. Publiez le texte seul, puis ajoutez le lien en <strong>1er commentaire épinglé</strong> = portée préservée à 100%.</div>
      </div>
      <div class="kp-tip-block" style="background:#f0fdf4;border:1px solid #bbf7d0;">
        <div class="block-label" style="color:#15803d;">💡 Conseils Facebook — ${esc(v)}</div>
        <ul style="color:#14532d;">${p.fb_tips.map(t=>`<li>${esc(t)}</li>`).join('')}</ul>
      </div>`;
  } else if (state.net === 'linkedin') {
    const hook_li = `📍 ${v} — ${p.hook(v).replace(/[?!.].*/,'?')}`;
    const body_li = `J'ai accompagné un(e) ${p.label.toLowerCase()} à ${v} cette semaine.\n\n[Racontez votre histoire : situation initiale → problème → solution → résultat]\n\nLe marché de ${v} évolue vite. Ce que j'observe :\n→ [Insight 1 chiffré]\n→ [Insight 2 local]\n→ [Conseil actionnable]\n\nQu'est-ce qui vous préoccupe le plus dans votre projet immobilier à ${v} ?`;
    const cta_li = `👇 Commentez votre situation et je vous réponds personnellement.\nOu téléchargez mon guide gratuit [en commentaire épinglé].`;
    html = `
      <div style="background:#f8faff;border:1px solid #c7d2fe;border-radius:8px;padding:12px 14px;">
        <div style="color:#4338ca;font-weight:700;font-size:11px;margin-bottom:8px;">✍️ Structure d'un post LinkedIn performant</div>
        ${[{l:'Hook (2 premières lignes)',t:hook_li,c:'#4338ca'},{l:'Corps (1 500-2 000 car.)',t:body_li,c:'#4338ca'},{l:'CTA final',t:cta_li,c:'#4338ca'}].map(s=>`
        <div style="margin-bottom:10px;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <span style="font-size:10.5px;color:${s.c};font-weight:700;">${esc(s.l)}</span>
            ${makeCopyBtn(s.t)}
          </div>
          <pre style="margin:0;background:#fff;border:1px solid #e0e7ff;border-radius:6px;padding:8px 10px;font-family:inherit;font-size:11px;color:#1e293b;white-space:pre-wrap;line-height:1.7;">${esc(s.t)}</pre>
        </div>`).join('')}
      </div>`;
  } else if (state.net === 'gmb') {
    const titre_gmb = `${p.label} à ${v} — ${p.levier} garantie`;
    const body_gmb = `Vous êtes ${p.sublabel.toLowerCase()} à ${v} et vous cherchez ${p.levier.toLowerCase()}.\n\n${p.hook(v)}\n\nJe suis conseiller immobilier local à ${v}. Je connais chaque quartier, chaque opportunité.\n\n✅ Accompagnement personnalisé\n✅ Connaissance du marché local\n✅ Résultats concrets pour mes clients\n\nContactez-moi via le bouton ci-dessous pour un premier échange gratuit.`;
    html = `
      <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 14px;">
        <div style="color:#15803d;font-weight:700;font-size:11px;margin-bottom:8px;">📍 Post Google My Business</div>
        ${[{l:`Titre (58 car. max)`,t:titre_gmb},{l:'Corps (sans lien, sans tel)',t:body_gmb}].map(s=>`
        <div style="margin-bottom:10px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
            <span style="font-size:10.5px;color:#15803d;font-weight:700;">${esc(s.l)}</span>
            ${makeCopyBtn(s.t)}
          </div>
          <pre style="margin:0;background:#fff;border:1px solid #d1fae5;border-radius:6px;padding:8px 10px;font-family:inherit;font-size:11px;color:#1e293b;white-space:pre-wrap;line-height:1.7;">${esc(s.t)}</pre>
        </div>`).join('')}
        <div style="background:#fef3c7;border:1px solid #fde68a;border-radius:6px;padding:8px 12px;">
          <div style="color:#92400e;font-size:10.5px;"><strong>🔔 Rappel :</strong> Post visible 7 jours → républiez chaque semaine | Photo 1200×900 px originale obligatoire | Bouton CTA → lien vers votre site</div>
        </div>
      </div>`;
  }
  c.innerHTML = html;
}

function renderHashtags() {
  const p = state.persona;
  const v = state.ville;
  const tiers = [
    { label:"Niche", range:"1k-150k", color:"#dc2626", bg:"#fef2f2", count:"3 hashtags", tags:p.hashtags.niche, desc:"Très ciblés, forte intention", width:"60%" },
    { label:"Local", range:"100k-500k", color:"#f59e0b", bg:"#fffbeb", count:"3 hashtags", tags:p.hashtags.local(v), desc:"Géolocalisation = leads qualifiés", width:"72%" },
    { label:"Large", range:"500k-1M+", color:"#3b82f6", bg:"#eff6ff", count:"3 hashtags", tags:p.hashtags.large, desc:"Découvrabilité et volume", width:"86%" },
    { label:"Marque", range:"Votre univers", color:"#7c3aed", bg:"#f5f3ff", count:"2 hashtags", tags:p.hashtags.marque, desc:"Identité et cohérence long terme", width:"100%" }
  ];
  const pyr = document.getElementById('hashtagPyramid');
  pyr.innerHTML = tiers.map(t => `
    <div style="width:${t.width};">
      <div class="kp-pyramid-tier" style="background:${t.bg};border-left-color:${t.color};border:1px solid ${t.color}20;border-left:4px solid ${t.color};">
        <div class="kp-tier-head">
          <div style="display:flex;align-items:center;gap:8px;">
            <span class="kp-tier-badge" style="background:${t.color};">${t.label}</span>
            <span class="kp-tier-range">${t.range} publications</span>
          </div>
          <span class="kp-tier-count" style="color:${t.color};">${t.count}</span>
        </div>
        <div class="kp-tier-tags">${t.tags.map(tag=>`<span class="kp-tag-pill" style="background:${t.color}15;color:${t.color};border-color:${t.color}30;">${esc(tag)}</span>`).join('')}</div>
        <div class="kp-tier-desc">${t.desc}</div>
      </div>
    </div>`).join('');

  const platf = document.getElementById('hashtagPlatformes');
  platf.innerHTML = [
    { net:"🎵 TikTok", color:"#010101", rules:["5 hashtags max en description","Limité à 150 caractères (description entière)","Niche + Local + 1 marque","Inclure la ville en hashtag ET dans le titre"] },
    { net:"📘 Facebook", color:"#1877f2", rules:["3-5 hashtags maximum","En fin de post, après le texte","Local + 1 niche ciblé","Taguez aussi la localisation du post"] },
    { net:"💼 LinkedIn", color:"#0a66c2", rules:["3 hashtags idéaux, 5 maximum","En bas du post, après le CTA","Professionnel + Niche (pas trop local)","Créez un hashtag de marque systématique"] },
    { net:"📍 GMB", color:"#34a853", rules:["Pas de hashtags sur GMB","Intégrez les mots-clés dans le texte","La ville dans le titre obligatoirement","Pas de symbole # — Google pénalise"] }
  ].map(p=>`
    <div style="background:#fafafa;border-radius:8px;padding:10px 12px;border-left:3px solid ${p.color};">
      <div style="font-weight:700;font-size:11px;color:${p.color};margin-bottom:6px;">${p.net}</div>
      <ul style="margin:0;padding:0 0 0 14px;color:#475569;font-size:10.5px;line-height:1.9;">${p.rules.map(r=>`<li>${esc(r)}</li>`).join('')}</ul>
    </div>`).join('');
}

function renderGuide() {
  const g = GUIDE_DATA[state.guide];
  const c = document.getElementById('guideContent');
  c.innerHTML = `
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
      <span style="background:${g.color};border-radius:6px;padding:4px 10px;color:#fff;font-size:11px;font-weight:700;">Guide ${state.guide.toUpperCase()}</span>
      <span style="color:#64748b;font-size:11px;">${g.subtitle}</span>
    </div>
    <div class="kp-principes-grid">
      ${g.principes.map(p=>`
      <div class="kp-principe-card" style="border-left-color:${g.accent};">
        <div class="kp-principe-icon">${p.icon}</div>
        <div class="kp-principe-title">${esc(p.title)}</div>
        <div class="kp-principe-desc">${esc(p.desc)}</div>
      </div>`).join('')}
    </div>
    <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 14px;">
      <div style="color:#0369a1;font-weight:700;font-size:11px;margin-bottom:8px;">⏰ Timing de publication optimal</div>
      <div class="kp-timing-grid">
        ${[{l:'Jours recommandés',v:g.timing.jours},{l:'Heures idéales',v:g.timing.heures},{l:'Fréquence',v:g.timing.frequence},{l:'Meilleur créneau',v:g.timing.best}].map(i=>`
        <div class="kp-timing-item">
          <div class="t-label">${esc(i.l)}</div>
          <div class="t-val">${esc(i.v)}</div>
        </div>`).join('')}
      </div>
    </div>`;
}

// ── Init ──
document.addEventListener('DOMContentLoaded', () => {
  // Ville search
  document.getElementById('villeSearch').addEventListener('input', renderVilles);

  // View switcher
  document.querySelectorAll('.kp-view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.kp-view-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      state.view = btn.dataset.view;
      ['scripts','hashtags','guide'].forEach(v => {
        document.getElementById('view-'+v).style.display = v===state.view ? '' : 'none';
      });
      renderContent();
    });
  });

  // Network tabs (scripts)
  document.querySelectorAll('#networkTabs .kp-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('#networkTabs .kp-tab').forEach(t => {
        t.classList.remove('active');
        t.style.borderBottomColor = 'transparent';
        t.style.color = '#94a3b8';
        t.style.background = 'transparent';
      });
      tab.classList.add('active');
      tab.style.borderBottomColor = NET_COLORS[tab.dataset.net] || '#1e293b';
      tab.style.color = NET_COLORS[tab.dataset.net] || '#1e293b';
      tab.style.background = '#fff';
      state.net = tab.dataset.net;
      renderContent();
    });
  });

  // Guide tabs
  document.querySelectorAll('#guideTabs .kp-guide-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('#guideTabs .kp-guide-tab').forEach(t => {
        t.classList.remove('active');
        t.style.borderBottomColor = 'transparent';
        t.style.color = '#94a3b8';
        t.style.background = 'transparent';
      });
      tab.classList.add('active');
      tab.style.borderBottomColor = GUIDE_DATA[tab.dataset.guide].color;
      tab.style.color = GUIDE_DATA[tab.dataset.guide].color;
      tab.style.background = '#fff';
      state.guide = tab.dataset.guide;
      renderGuide();
    });
  });

  // Init render
  renderVilles();
  renderPersonas();
  renderPersonaInfo();
  renderContent();
});
</script>

<?php
$content = ob_get_clean();
// Chemin : /admin/modules/social/kit-publications/ → remonter 4 niveaux → /admin/layout/layout.php
require_once __DIR__ . '/../../../layout/layout.php';
?>