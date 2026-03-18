/**
 * DesignCloner — Builder Pro
 * Clone les couleurs, typographies et styles d'une page existante
 * et les applique au CSS de la page en cours d'édition.
 */

const DesignCloner = (() => {

  // ── État ──────────────────────────────────────────────────
  let _modal = null;
  let _step  = 1; // 1=url, 2=analyse, 3=résultat, 4=application

  // ── Helpers ───────────────────────────────────────────────
  const esc = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

  function hexToRgb(hex) {
    const r = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return r ? [parseInt(r[1],16), parseInt(r[2],16), parseInt(r[3],16)] : null;
  }

  function rgbToHex(r,g,b) {
    return '#' + [r,g,b].map(v => v.toString(16).padStart(2,'0')).join('');
  }

  function luminance(r,g,b) {
    const a = [r,g,b].map(v => { v/=255; return v<=0.03928 ? v/12.92 : Math.pow((v+0.055)/1.055,2.4); });
    return 0.2126*a[0] + 0.7152*a[1] + 0.0722*a[2];
  }

  function colorDistance(c1, c2) {
    return Math.sqrt([0,1,2].reduce((sum,i) => sum + Math.pow(c1[i]-c2[i],2), 0));
  }

  function isDark(hex) {
    const rgb = hexToRgb(hex);
    if (!rgb) return false;
    return luminance(...rgb) < 0.3;
  }

  function isLight(hex) {
    const rgb = hexToRgb(hex);
    if (!rgb) return true;
    return luminance(...rgb) > 0.7;
  }

  // Extraire couleurs depuis une chaîne CSS/HTML
  function extractColors(text) {
    const colors = new Map();

    // Hex colors
    const hexRe = /#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b/g;
    let m;
    while ((m = hexRe.exec(text)) !== null) {
      let hex = m[0];
      if (hex.length === 4) {
        hex = '#' + hex[1]+hex[1]+hex[2]+hex[2]+hex[3]+hex[3];
      }
      hex = hex.toLowerCase();
      // Ignorer blanc, noir purs et gris neutres
      if (['#ffffff','#000000','#fff','#000'].includes(hex)) continue;
      const rgb = hexToRgb(hex);
      if (!rgb) continue;
      const [r,g,b] = rgb;
      // Ignorer gris quasi-neutres
      const maxDiff = Math.max(Math.abs(r-g),Math.abs(g-b),Math.abs(r-b));
      if (maxDiff < 20) continue;
      colors.set(hex, (colors.get(hex) || 0) + 1);
    }

    // rgb(r,g,b)
    const rgbRe = /rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/g;
    while ((m = rgbRe.exec(text)) !== null) {
      const [r,g,b] = [+m[1],+m[2],+m[3]];
      if (r===255&&g===255&&b===255) continue;
      if (r===0&&g===0&&b===0) continue;
      const maxDiff = Math.max(Math.abs(r-g),Math.abs(g-b),Math.abs(r-b));
      if (maxDiff < 20) continue;
      const hex = rgbToHex(r,g,b);
      colors.set(hex, (colors.get(hex) || 0) + 1);
    }

    // Trier par fréquence
    return [...colors.entries()]
      .sort((a,b) => b[1]-a[1])
      .map(([hex]) => hex);
  }

  // Cluster les couleurs similaires et retourner les représentants
  function clusterColors(colors, threshold = 50) {
    const clusters = [];
    for (const hex of colors) {
      const rgb = hexToRgb(hex);
      if (!rgb) continue;
      let found = false;
      for (const cluster of clusters) {
        if (colorDistance(rgb, cluster.center) < threshold) {
          cluster.members.push(hex);
          cluster.count++;
          found = true;
          break;
        }
      }
      if (!found) {
        clusters.push({ center: rgb, members: [hex], count: 1, representative: hex });
      }
    }
    return clusters
      .sort((a,b) => b.count - a.count)
      .map(c => c.representative);
  }

  // Extraire les familles de polices
  function extractFonts(text) {
    const fonts = new Set();
    const re = /font-family\s*:\s*([^;}"']+)/gi;
    let m;
    while ((m = re.exec(text)) !== null) {
      const raw = m[1].trim().split(',')[0].replace(/['"]/g,'').trim();
      if (raw && raw.length > 2 && raw.toLowerCase() !== 'inherit' && raw.toLowerCase() !== 'initial') {
        fonts.add(raw);
      }
    }
    // Google Fonts dans les link href
    const gfRe = /fonts\.googleapis\.com\/css[^"']*family=([^"'&]+)/g;
    while ((m = gfRe.exec(text)) !== null) {
      const families = decodeURIComponent(m[1]).split('|');
      families.forEach(f => {
        const name = f.split(':')[0].replace(/\+/g,' ').trim();
        if (name) fonts.add(name);
      });
    }
    return [...fonts].slice(0, 5);
  }

  // Détecter la palette : primary, secondary, accent, bg, text
  function detectPalette(colors) {
    const dark  = colors.filter(c => isDark(c));
    const light = colors.filter(c => isLight(c));
    const mid   = colors.filter(c => !isDark(c) && !isLight(c));

    const palette = {
      primary:   dark[0]  || colors[0] || '#1a4d7a',
      secondary: dark[1]  || mid[0]    || colors[1] || '#2d6ba3',
      accent:    mid[0]   || colors[2] || '#d4a574',
      bg:        light[0] || '#f9f6f3',
      text:      dark[0]  || '#2d3748',
    };

    // Vérifier que accent est différent de primary
    if (palette.accent === palette.primary) {
      palette.accent = mid[1] || colors[3] || '#d4a574';
    }

    return palette;
  }

  // Générer le CSS des variables à injecter
  function generateCssVariables(palette, fonts) {
    const headingFont = fonts[0] || 'Playfair Display';
    const bodyFont    = fonts[1] || fonts[0] || 'DM Sans';

    return `/* ── Design cloné automatiquement ── */
:root {
  --ed-primary:    ${palette.primary};
  --ed-primary-dk: ${darken(palette.primary, 15)};
  --ed-accent:     ${palette.accent};
  --ed-accent-lt:  ${lighten(palette.accent, 20)};
  --ed-card-bg:    ${palette.bg};
  --ed-text:       ${palette.text};
  --ed-border:     ${lighten(palette.primary, 60)};
  --ff-heading:    '${headingFont}', serif;
  --ff-body:       '${bodyFont}', sans-serif;
}`;
  }

  function darken(hex, amount) {
    const rgb = hexToRgb(hex);
    if (!rgb) return hex;
    return rgbToHex(
      Math.max(0, rgb[0] - amount),
      Math.max(0, rgb[1] - amount),
      Math.max(0, rgb[2] - amount)
    );
  }

  function lighten(hex, amount) {
    const rgb = hexToRgb(hex);
    if (!rgb) return hex;
    return rgbToHex(
      Math.min(255, rgb[0] + amount),
      Math.min(255, rgb[1] + amount),
      Math.min(255, rgb[2] + amount)
    );
  }

  // ── Modal HTML ────────────────────────────────────────────
  function createModal() {
    const el = document.createElement('div');
    el.id = 'designClonerModal';
    el.innerHTML = `
<div style="
  position:fixed;top:0;left:0;right:0;bottom:0;
  background:rgba(15,23,42,.65);
  z-index:9999;
  display:flex;align-items:center;justify-content:center;
  backdrop-filter:blur(4px);
  animation:dcFadeIn .2s ease
">
  <div id="dcBox" style="
    background:#fff;border-radius:16px;
    width:90%;max-width:560px;
    box-shadow:0 24px 60px rgba(0,0,0,.25);
    overflow:hidden;
    animation:dcSlideUp .25s ease
  ">
    <!-- Header -->
    <div style="background:linear-gradient(135deg,#8b5cf6,#ec4899);padding:20px 24px;display:flex;align-items:center;justify-content:space-between">
      <div>
        <div style="color:#fff;font-size:18px;font-weight:800;display:flex;align-items:center;gap:8px">
          <i class="fas fa-palette"></i> Cloner le design
        </div>
        <div style="color:rgba(255,255,255,.75);font-size:12px;margin-top:2px">Extrait couleurs & typographies d'une page</div>
      </div>
      <button onclick="DesignCloner.close()" style="
        width:32px;height:32px;border:none;background:rgba(255,255,255,.2);
        border-radius:8px;color:#fff;cursor:pointer;font-size:16px;
        display:flex;align-items:center;justify-content:center
      ">✕</button>
    </div>

    <!-- Body -->
    <div id="dcBody" style="padding:24px">

      <!-- ÉTAPE 1 : Saisie URL -->
      <div id="dcStep1">
        <p style="font-size:13px;color:#64748b;margin-bottom:16px">
          Entrez l'URL d'une page dont vous voulez reproduire les couleurs et la typographie.
          Le système analysera automatiquement son design.
        </p>
        <div style="margin-bottom:12px">
          <label style="display:block;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px">
            URL de la page à cloner
          </label>
          <input id="dcUrl" type="url"
            placeholder="https://exemple.com/ma-page"
            style="width:100%;padding:12px 16px;border:2px solid #e2e8f0;border-radius:10px;font-size:14px;font-family:inherit;transition:border-color .15s"
            onfocus="this.style.borderColor='#8b5cf6'"
            onblur="this.style.borderColor='#e2e8f0'"
          >
        </div>

        <!-- Suggestions rapides -->
        <div style="margin-bottom:20px">
          <div style="font-size:11px;font-weight:600;color:#94a3b8;margin-bottom:8px;text-transform:uppercase;letter-spacing:.5px">Exemples rapides</div>
          <div style="display:flex;flex-wrap:wrap;gap:6px" id="dcSuggestions">
            <button class="dc-quick" data-url="https://eduardo-desul-immobilier.fr/estimation">🏠 Estimation</button>
            <button class="dc-quick" data-url="https://eduardo-desul-immobilier.fr/contact">📞 Contact</button>
            <button class="dc-quick" data-url="https://eduardo-desul-immobilier.fr">🏡 Accueil</button>
          </div>
        </div>

        <div style="display:flex;gap:10px">
          <button onclick="DesignCloner.close()" style="flex:1;padding:12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;background:#fff;font-family:inherit;color:#64748b">
            Annuler
          </button>
          <button onclick="DesignCloner.analyze()" style="flex:2;padding:12px;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;display:flex;align-items:center;justify-content:center;gap:8px">
            <i class="fas fa-search"></i> Analyser le design
          </button>
        </div>
      </div>

      <!-- ÉTAPE 2 : Chargement -->
      <div id="dcStep2" style="display:none;text-align:center;padding:32px 0">
        <div style="font-size:48px;margin-bottom:16px;animation:dcSpin 1s linear infinite;display:inline-block">🎨</div>
        <div style="font-size:16px;font-weight:700;color:#1e293b;margin-bottom:8px">Analyse en cours...</div>
        <div id="dcProgress" style="font-size:13px;color:#64748b">Récupération de la page...</div>
        <div style="margin-top:16px;height:4px;background:#f1f5f9;border-radius:2px;overflow:hidden">
          <div id="dcProgressBar" style="height:100%;width:0%;background:linear-gradient(90deg,#8b5cf6,#ec4899);border-radius:2px;transition:width .4s ease"></div>
        </div>
      </div>

      <!-- ÉTAPE 3 : Résultats -->
      <div id="dcStep3" style="display:none">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px">
          <div style="width:32px;height:32px;background:#dcfce7;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px">✅</div>
          <div>
            <div style="font-size:14px;font-weight:700;color:#1e293b">Design analysé avec succès</div>
            <div id="dcSourceUrl" style="font-size:11px;color:#64748b"></div>
          </div>
        </div>

        <!-- Palette de couleurs -->
        <div style="margin-bottom:20px">
          <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">Palette de couleurs détectée</div>
          <div id="dcPalette" style="display:grid;grid-template-columns:repeat(5,1fr);gap:8px"></div>
        </div>

        <!-- Typographies -->
        <div style="margin-bottom:20px">
          <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Typographies détectées</div>
          <div id="dcFonts" style="display:flex;flex-wrap:wrap;gap:6px"></div>
        </div>

        <!-- Preview CSS -->
        <div style="margin-bottom:20px">
          <div style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Variables CSS générées</div>
          <div style="background:#1e1e2e;border-radius:8px;padding:14px;overflow:auto;max-height:140px">
            <pre id="dcCssPreview" style="color:#cdd6f4;font-size:11px;font-family:'JetBrains Mono',monospace;white-space:pre-wrap;margin:0"></pre>
          </div>
        </div>

        <!-- Options d'application -->
        <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:16px">
          <div style="font-size:12px;font-weight:700;color:#1e293b;margin-bottom:10px">Options d'application</div>
          <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#475569;cursor:pointer;margin-bottom:6px">
            <input type="checkbox" id="dcApplyVars" checked style="accent-color:#8b5cf6">
            Injecter les variables CSS dans la page
          </label>
          <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#475569;cursor:pointer;margin-bottom:6px">
            <input type="checkbox" id="dcApplyFonts" checked style="accent-color:#8b5cf6">
            Ajouter les imports Google Fonts
          </label>
          <label style="display:flex;align-items:center;gap:8px;font-size:12px;color:#475569;cursor:pointer">
            <input type="checkbox" id="dcReplaceExisting" style="accent-color:#8b5cf6">
            Remplacer les couleurs hardcodées existantes
          </label>
        </div>

        <div style="display:flex;gap:10px">
          <button onclick="DesignCloner.reset()" style="flex:1;padding:12px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;background:#fff;font-family:inherit;color:#64748b">
            <i class="fas fa-arrow-left"></i> Retour
          </button>
          <button onclick="DesignCloner.apply()" style="flex:2;padding:12px;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;display:flex;align-items:center;justify-content:center;gap:8px">
            <i class="fas fa-magic"></i> Appliquer le design
          </button>
        </div>
      </div>

      <!-- ÉTAPE 4 : Succès -->
      <div id="dcStep4" style="display:none;text-align:center;padding:24px 0">
        <div style="font-size:52px;margin-bottom:16px">🎉</div>
        <div style="font-size:18px;font-weight:800;color:#1e293b;margin-bottom:8px">Design appliqué !</div>
        <div style="font-size:13px;color:#64748b;margin-bottom:24px">
          Les variables CSS ont été injectées dans votre page.
          Vérifiez le résultat dans l'aperçu.
        </div>
        <div style="display:flex;gap:10px;justify-content:center">
          <button onclick="DesignCloner.close()" style="padding:12px 24px;border:1px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;background:#fff;font-family:inherit;color:#64748b">
            Fermer
          </button>
          <button onclick="BP.switchMode('preview');DesignCloner.close()" style="padding:12px 24px;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;background:linear-gradient(135deg,#8b5cf6,#ec4899);color:#fff;display:flex;align-items:center;gap:8px">
            <i class="fas fa-eye"></i> Voir le résultat
          </button>
        </div>
      </div>

    </div>
  </div>
</div>
<style>
@keyframes dcFadeIn  { from { opacity:0 }            to { opacity:1 } }
@keyframes dcSlideUp { from { transform:translateY(20px);opacity:0 } to { transform:translateY(0);opacity:1 } }
@keyframes dcSpin    { from { transform:rotate(0deg) } to { transform:rotate(360deg) } }
.dc-quick {
  padding:6px 12px;border:1px solid #e2e8f0;border-radius:20px;
  font-size:11px;font-weight:600;cursor:pointer;background:#fff;
  font-family:inherit;color:#475569;transition:all .15s
}
.dc-quick:hover { border-color:#8b5cf6;color:#8b5cf6;background:#faf5ff }
</style>`;
    document.body.appendChild(el);

    // Boutons suggestions rapides
    el.querySelectorAll('.dc-quick').forEach(btn => {
      btn.addEventListener('click', () => {
        document.getElementById('dcUrl').value = btn.dataset.url;
      });
    });

    // Enter sur l'input
    el.querySelector('#dcUrl').addEventListener('keydown', e => {
      if (e.key === 'Enter') DesignCloner.analyze();
    });

    return el;
  }

  // ── Afficher une étape ───────────────────────────────────
  function showStep(n) {
    [1,2,3,4].forEach(i => {
      const el = document.getElementById('dcStep'+i);
      if (el) el.style.display = i === n ? 'block' : 'none';
    });
    _step = n;
  }

  function setProgress(text, pct) {
    const p = document.getElementById('dcProgress');
    const b = document.getElementById('dcProgressBar');
    if (p) p.textContent = text;
    if (b) b.style.width = pct + '%';
  }

  // ── Analyse ───────────────────────────────────────────────
  let _lastPalette = null;
  let _lastFonts   = [];
  let _lastCss     = '';

  async function analyzeUrl(url) {
    showStep(2);
    setProgress('Récupération de la page...', 10);

    let pageText = '';

    // Tentative 1 : fetch direct (CORS possible)
    try {
      const resp = await fetch(url, { mode: 'no-cors' });
      // no-cors → opaque response, on ne peut pas lire le body
      // Utiliser le proxy PHP
      throw new Error('Utilisation du proxy');
    } catch (_) {}

    setProgress('Appel du proxy serveur...', 30);

    // Tentative 2 : proxy PHP
    try {
      const resp = await fetch('/admin/api/builder/design.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'fetch_page', url })
      });
      const data = await resp.json();
      if (data.success && data.html) {
        pageText = data.html;
      } else {
        throw new Error(data.error || 'Proxy indisponible');
      }
    } catch (e) {
      // Tentative 3 : fetch direct même-domaine
      try {
        const u = new URL(url);
        const isSameDomain = window.location.hostname === u.hostname;
        if (isSameDomain) {
          const resp = await fetch(url);
          pageText = await resp.text();
        } else {
          throw new Error('Domaine différent sans proxy');
        }
      } catch (e2) {
        // Tentative 4 : API Anthropic pour décrire le design via URL (si clé dispo)
        if (typeof BP !== 'undefined' && BP.config.hasClaudeKey && BP.config.claudeKey) {
          setProgress('Analyse IA du design...', 50);
          return await analyzeViaAI(url);
        }
        throw new Error('Impossible de récupérer la page. Vérifiez l\'URL ou utilisez une page du même domaine.');
      }
    }

    setProgress('Extraction des couleurs...', 60);
    await sleep(200);

    const colors = clusterColors(extractColors(pageText), 40);
    setProgress('Détection de la palette...', 75);
    await sleep(200);

    const fonts   = extractFonts(pageText);
    const palette = detectPalette(colors);
    setProgress('Génération des variables CSS...', 90);
    await sleep(200);

    const cssVars = generateCssVariables(palette, fonts);
    setProgress('Terminé !', 100);
    await sleep(300);

    return { palette, fonts, cssVars, colors: colors.slice(0, 10) };
  }

  // Analyse via Claude AI quand le fetch direct échoue
  async function analyzeViaAI(url) {
    setProgress('Analyse IA en cours...', 55);

    const resp = await fetch('https://api.anthropic.com/v1/messages', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'x-api-key': BP.config.claudeKey,
        'anthropic-version': '2023-06-01',
        'anthropic-dangerous-direct-browser-access': 'true'
      },
      body: JSON.stringify({
        model: 'claude-sonnet-4-20250514',
        max_tokens: 800,
        messages: [{
          role: 'user',
          content: `Analyse visuellement le design du site : ${url}

Retourne UNIQUEMENT ce JSON (sans markdown) :
{
  "primary": "#hexcode",
  "secondary": "#hexcode", 
  "accent": "#hexcode",
  "bg": "#hexcode",
  "text": "#hexcode",
  "heading_font": "Nom de la police titres",
  "body_font": "Nom de la police corps",
  "all_colors": ["#hex1","#hex2","#hex3","#hex4","#hex5"]
}

Si tu ne connais pas le site, déduis un design plausible basé sur le domaine.`
        }]
      })
    });

    const data = await resp.json();
    const raw = data.content?.[0]?.text || '{}';

    let parsed;
    try {
      const cleaned = raw.replace(/```json?/g,'').replace(/```/g,'').trim();
      parsed = JSON.parse(cleaned);
    } catch(e) {
      throw new Error('Réponse IA invalide');
    }

    setProgress('Génération des variables CSS...', 90);
    await sleep(300);

    const palette = {
      primary:   parsed.primary   || '#1a4d7a',
      secondary: parsed.secondary || '#2d6ba3',
      accent:    parsed.accent    || '#d4a574',
      bg:        parsed.bg        || '#f9f6f3',
      text:      parsed.text      || '#2d3748',
    };
    const fonts   = [parsed.heading_font, parsed.body_font].filter(Boolean);
    const colors  = parsed.all_colors || Object.values(palette);
    const cssVars = generateCssVariables(palette, fonts);

    setProgress('Terminé (via IA) !', 100);
    await sleep(300);

    return { palette, fonts, cssVars, colors, viaAI: true };
  }

  function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

  // ── Rendu des résultats ───────────────────────────────────
  function renderResults(result, url) {
    // Source URL
    const srcEl = document.getElementById('dcSourceUrl');
    if (srcEl) {
      srcEl.textContent = (result.viaAI ? '✨ Analysé via IA · ' : '') + url;
    }

    // Palette
    const paletteEl = document.getElementById('dcPalette');
    if (paletteEl) {
      const labels = ['Primaire','Secondaire','Accent','Fond','Texte'];
      const vals   = [result.palette.primary, result.palette.secondary, result.palette.accent, result.palette.bg, result.palette.text];
      paletteEl.innerHTML = vals.map((c, i) => `
        <div style="text-align:center">
          <div style="
            width:100%;height:48px;border-radius:8px;
            background:${esc(c)};
            border:1px solid rgba(0,0,0,.08);
            margin-bottom:4px;
            cursor:pointer;
            transition:transform .15s
          " title="${esc(c)}" onclick="navigator.clipboard?.writeText('${esc(c)}')"></div>
          <div style="font-size:9px;color:#94a3b8;font-weight:600;text-transform:uppercase">${labels[i]}</div>
          <div style="font-size:10px;color:#475569;font-family:monospace">${esc(c)}</div>
        </div>
      `).join('');
    }

    // Toutes les couleurs extraites
    if (result.colors && result.colors.length > 5) {
      const allColorsDiv = document.createElement('div');
      allColorsDiv.style.cssText = 'display:flex;flex-wrap:wrap;gap:4px;margin-top:8px';
      result.colors.slice(5).forEach(c => {
        const sp = document.createElement('div');
        sp.style.cssText = `width:24px;height:24px;border-radius:4px;background:${c};border:1px solid rgba(0,0,0,.08);cursor:pointer;title:${c}`;
        sp.title = c;
        sp.onclick = () => navigator.clipboard?.writeText(c);
        allColorsDiv.appendChild(sp);
      });
      document.getElementById('dcPalette')?.after(allColorsDiv);
    }

    // Fonts
    const fontsEl = document.getElementById('dcFonts');
    if (fontsEl) {
      if (result.fonts.length) {
        fontsEl.innerHTML = result.fonts.map(f =>
          `<span style="padding:4px 12px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:20px;font-size:12px;color:#475569">${esc(f)}</span>`
        ).join('');
      } else {
        fontsEl.innerHTML = '<span style="font-size:12px;color:#94a3b8">Aucune police spécifique détectée</span>';
      }
    }

    // CSS Preview
    const cssEl = document.getElementById('dcCssPreview');
    if (cssEl) cssEl.textContent = result.cssVars;

    _lastPalette = result.palette;
    _lastFonts   = result.fonts;
    _lastCss     = result.cssVars;

    showStep(3);
  }

  // ── API Publique ──────────────────────────────────────────
  return {

    open() {
      if (!_modal) {
        _modal = createModal();
      }
      _modal.style.display = 'block';
      showStep(1);
      setTimeout(() => document.getElementById('dcUrl')?.focus(), 100);
    },

    close() {
      if (_modal) {
        _modal.style.display = 'none';
      }
    },

    reset() {
      showStep(1);
      const urlEl = document.getElementById('dcUrl');
      if (urlEl) urlEl.value = '';
    },

    async analyze() {
      const urlEl = document.getElementById('dcUrl');
      const url   = urlEl?.value?.trim();

      if (!url) {
        urlEl.style.borderColor = '#ef4444';
        urlEl.placeholder = 'URL requise !';
        setTimeout(() => {
          urlEl.style.borderColor = '';
          urlEl.placeholder = 'https://exemple.com/ma-page';
        }, 2000);
        return;
      }

      // Valider URL
      let validUrl;
      try {
        validUrl = new URL(url);
      } catch(_) {
        // Essayer avec https://
        try { validUrl = new URL('https://' + url); }
        catch(_) {
          urlEl.style.borderColor = '#ef4444';
          return;
        }
      }

      try {
        const result = await analyzeUrl(validUrl.href);
        renderResults(result, validUrl.href);
      } catch (err) {
        showStep(1);
        if (typeof BP !== 'undefined') {
          BP.toast('❌ ' + err.message, 'error');
        } else {
          alert('Erreur : ' + err.message);
        }
        console.error('[DesignCloner]', err);
      }
    },

    apply() {
      if (!_lastCss) return;

      const applyVars    = document.getElementById('dcApplyVars')?.checked    ?? true;
      const applyFonts   = document.getElementById('dcApplyFonts')?.checked   ?? true;
      const replaceExist = document.getElementById('dcReplaceExisting')?.checked ?? false;

      let cssToAdd = '';

      if (applyVars) {
        cssToAdd += _lastCss + '\n\n';
      }

      if (applyFonts && _lastFonts.length) {
        const googleFontsUrl = 'https://fonts.googleapis.com/css2?family=' +
          _lastFonts.map(f => f.replace(/ /g, '+')).join('&family=') +
          '&display=swap';
        cssToAdd = `@import url('${googleFontsUrl}');\n\n` + cssToAdd;
      }

      // Récupérer le CSS actuel
      const cssTextarea = document.getElementById('codeCss');
      if (!cssTextarea) {
        if (typeof BP !== 'undefined') BP.toast('❌ Éditeur CSS non trouvé', 'error');
        return;
      }

      let currentCss = cssTextarea.value;

      if (replaceExist && _lastPalette) {
        // Remplacer les couleurs les plus communes dans le CSS existant
        // par les nouvelles valeurs de palette
        // Stratégie simple : remplacer les hex les plus fréquents
        const commonColors = extractColors(currentCss).slice(0, 3);
        const newColors    = [_lastPalette.primary, _lastPalette.accent, _lastPalette.secondary];
        commonColors.forEach((old, i) => {
          if (newColors[i]) {
            const re = new RegExp(old.replace('#','#'), 'gi');
            currentCss = currentCss.replace(re, newColors[i]);
          }
        });
      }

      // Ajouter les nouvelles variables en tête du CSS
      cssTextarea.value = cssToAdd + currentCss;

      // Mettre à jour l'aperçu
      if (typeof BP !== 'undefined') {
        BP.refreshPreview();
        BP.toast('✅ Design appliqué !', 'success');
      }

      showStep(4);
    }
  };

})();

// Assurer que le module est disponible globalement
window.DesignCloner = DesignCloner;
console.log('✅ DesignCloner chargé');