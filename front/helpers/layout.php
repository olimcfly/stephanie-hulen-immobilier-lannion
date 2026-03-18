<?php
/**
 * ============================================================
 * helpers/layout.php
 * Header, Footer et chargement depuis la DB
 * ============================================================
 */

if (!function_exists('getHeaderFooter')) {
    /**
     * Charge le header et footer actifs depuis la DB.
     * Fallbacks : status='active' → is_default=1 → premier enregistrement
     */
    function getHeaderFooter(PDO $db, string $pageSlug = ''): array {
        $result = ['header' => null, 'footer' => null];

        // ── HEADER ──
        try {
            $stmt = $db->query("SELECT * FROM headers WHERE status = 'active' ORDER BY is_default DESC, id DESC LIMIT 1");
            $result['header'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$result['header']) {
                $stmt = $db->query("SELECT * FROM headers WHERE is_default = 1 ORDER BY id DESC LIMIT 1");
                $result['header'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
            if (!$result['header']) {
                $stmt = $db->query("SELECT * FROM headers ORDER BY id ASC LIMIT 1");
                $result['header'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        } catch (PDOException $e) { error_log("Header load error: " . $e->getMessage()); }

        // ── FOOTER ──
        try {
            $stmt = $db->query("SELECT * FROM footers WHERE status = 'active' ORDER BY is_default DESC, id DESC LIMIT 1");
            $result['footer'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$result['footer']) {
                $stmt = $db->query("SELECT * FROM footers WHERE is_default = 1 ORDER BY id DESC LIMIT 1");
                $result['footer'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
            if (!$result['footer']) {
                $stmt = $db->query("SELECT * FROM footers ORDER BY id ASC LIMIT 1");
                $result['footer'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        } catch (PDOException $e) { error_log("Footer load error: " . $e->getMessage()); }

        return $result;
    }
}

if (!function_exists('renderHeader')) {
    /**
     * Génère le HTML du header.
     * Priorité : custom_html → génération automatique depuis champs structurés
     */
    function renderHeader(array $h): string {

        // ── 1. custom_html ──
        if (!empty($h['custom_html'])) {
            $vars = buildLayoutVars($h);
            $html = str_replace(array_keys($vars), array_values($vars), $h['custom_html']);
            $out  = '';
            if (!empty($h['custom_css'])) $out .= '<style>' . $h['custom_css'] . '</style>';
            $out .= $html;
            if (!empty($h['custom_js']))  $out .= '<script>' . $h['custom_js'] . '</script>';
            return $out;
        }

        // ── 2. Génération automatique ──
        $name     = htmlspecialchars($h['company_name'] ?? $h['name'] ?? _ss('site_name', 'Eduardo De Sul'));
        $logo     = $h['logo_url'] ?? _ss('logo_url', '');
        $logoType = $h['logo_type'] ?? 'image';
        $logoText = $h['logo_text'] ?? $name;
        $phone    = $h['phone_number'] ?? $h['contact_phone'] ?? _ss('phone', '');
        $height   = intval($h['height'] ?? 80);
        $isSticky = !empty($h['sticky']);
        $breakpt  = intval($h['mobile_breakpoint'] ?? 1024);

        $menuItems = [];
        if (!empty($h['menu_items'])) {
            $decoded = json_decode($h['menu_items'], true);
            if (is_array($decoded)) $menuItems = $decoded;
        }
        if (empty($menuItems)) {
            $menuItems = [
                ['label' => 'Accueil',   'url' => '/'],
                ['label' => 'Acheter',   'url' => '/acheter'],
                ['label' => 'Vendre',    'url' => '/vendre'],
                ['label' => 'Estimer',   'url' => '/estimation'],
                ['label' => 'Secteurs',  'url' => '/secteurs'],
                ['label' => 'Blog',      'url' => '/blog'],
                ['label' => 'Contact',   'url' => '/contact'],
            ];
        }

        $sticky = $isSticky ? 'position:sticky;top:0;' : '';

        $out  = '<style>';
        $out .= '.fh{background:#fff;padding:0 0px;' . $sticky . 'z-index:100}';
        $out .= '.fh__inner{max-width:1200px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;height:' . $height . 'px;gap:20px}';
        $out .= '.fh__logo img{height:' . min(intval($h['logo_width'] ?? 150), $height - 20) . 'px;width:auto}';
        $out .= '.fh__logo-text{font-family:var(--ff-heading);font-size:18px;font-weight:700;color:var(--ed-primary);text-decoration:none}';
        $out .= '.fh__nav{display:flex;align-items:center;gap:4px}';
        $out .= '.fh__link{padding:8px 14px;font-size:14px;font-weight:500;color:var(--ed-text);text-decoration:none;border-radius:6px;transition:all .2s}';
        $out .= '.fh__link:hover{background:rgba(212,165,116,.12);color:var(--ed-primary)}';
        $out .= '.fh__cta{padding:10px 22px;background:var(--ed-accent);color:white;border-radius:var(--ed-radius-pill);font-size:13px;font-weight:700;text-decoration:none;transition:var(--ed-transition);display:flex;align-items:center;gap:6px;box-shadow:0 2px 8px rgba(212,165,116,.3)}';
        $out .= '.fh__cta:hover{background:var(--ed-accent-dk);transform:translateY(-1px)}';
        $out .= '.fh__burger{display:none;background:none;border:none;font-size:22px;color:var(--ed-primary);cursor:pointer;padding:8px}';
        $out .= '@media(max-width:' . $breakpt . 'px){';
        $out .= '.fh__nav{display:none;position:absolute;top:' . $height . 'px;left:0;right:0;background:#fff;flex-direction:column;padding:16px;box-shadow:0 8px 20px rgba(26,77,122,.1);border-top:1px solid var(--ed-border)}';
        $out .= '.fh__nav.open{display:flex}.fh__burger{display:block}}';
        $out .= '</style>';

        $out .= '<header class="fh"><div class="fh__inner">';

        // Logo
        if ($logoType === 'image' && $logo) {
            $out .= '<a href="' . htmlspecialchars($h['logo_link'] ?? '/') . '" class="fh__logo">';
            $out .= '<img src="' . htmlspecialchars($logo) . '" alt="' . htmlspecialchars($h['logo_alt'] ?? $name) . '">';
            $out .= '</a>';
        } else {
            $out .= '<a href="' . htmlspecialchars($h['logo_link'] ?? '/') . '" class="fh__logo-text">' . htmlspecialchars($logoText ?: $name) . '</a>';
        }

        // Burger
        $out .= '<button type="button" class="fh__burger" onclick="document.querySelector(\'.fh__nav\').classList.toggle(\'open\')" aria-label="Menu"><i class="fas fa-bars"></i></button>';

        // Nav
        $out .= '<nav class="fh__nav">';
        foreach ($menuItems as $item) {
            $out .= '<a href="' . htmlspecialchars($item['url'] ?? '#') . '" class="fh__link">' . htmlspecialchars($item['label'] ?? '') . '</a>';
        }

        // CTA
        if (!empty($h['cta_enabled']) && !empty($h['cta_text'])) {
            $out .= '<a href="' . htmlspecialchars($h['cta_link'] ?? '/contact') . '" class="fh__cta"><i class="fas fa-phone-alt"></i> ' . htmlspecialchars($h['cta_text']) . '</a>';
        } elseif (!empty($h['phone_enabled']) && $phone) {
            $cleanPhone = preg_replace('/\s+/', '', $phone);
            $out .= '<a href="tel:' . htmlspecialchars($cleanPhone) . '" class="fh__cta"><i class="fas fa-phone-alt"></i> ' . htmlspecialchars($phone) . '</a>';
        }

        $out .= '</nav></div></header>';
        return $out;
    }
}

if (!function_exists('renderFooter')) {
    /**
     * Génère le HTML du footer.
     * Priorité : custom_html → génération automatique depuis champs structurés
     */
    function renderFooter(array $f): string {

        // ── 1. custom_html ──
        if (!empty($f['custom_html'])) {
            $vars = buildLayoutVars($f);
            $html = str_replace(array_keys($vars), array_values($vars), $f['custom_html']);
            $out  = '';
            if (!empty($f['custom_css'])) $out .= '<style>' . $f['custom_css'] . '</style>';
            $out .= $html;
            if (!empty($f['custom_js']))  $out .= '<script>' . $f['custom_js'] . '</script>';
            return $out;
        }

        // ── 2. Génération automatique ──
        $name          = htmlspecialchars($f['company_name'] ?? $f['name'] ?? _ss('site_name', 'Eduardo De Sul Immobilier'));
        $phone         = htmlspecialchars($f['phone'] ?? $f['contact_phone'] ?? _ss('phone', ''));
        $email         = htmlspecialchars($f['email'] ?? $f['contact_email'] ?? _ss('email', ''));
        $description   = htmlspecialchars($f['description'] ?? 'Votre conseiller immobilier à Bordeaux. Accompagnement personnalisé pour tous vos projets.');
        $bgColor       = $f['bg_color'] ?? '#0e3a5c';
        $copyright     = $f['copyright_text'] ?? '© ' . date('Y') . ' ' . strip_tags($name) . ' — Tous droits réservés';
        $paddingTop    = intval($f['padding_top']    ?? 60);
        $paddingBottom = intval($f['padding_bottom'] ?? 40);

        $socialLinks = [];
        if (!empty($f['social_links'])) { $d = json_decode($f['social_links'], true); if (is_array($d)) $socialLinks = $d; }
        $columns = [];
        if (!empty($f['columns']))      { $d = json_decode($f['columns'], true);      if (is_array($d)) $columns = $d; }
        $legalLinks = [];
        if (!empty($f['legal_links']))  { $d = json_decode($f['legal_links'], true);  if (is_array($d)) $legalLinks = $d; }

        $out  = '<style>';
        $out .= '.ff{background:' . $bgColor . ';color:#c5b8ac;padding:' . $paddingTop . 'px 24px ' . $paddingBottom . 'px}';
        $out .= '.ff__inner{max-width:1200px;margin:0 auto}';
        $out .= '.ff__top{display:flex;justify-content:space-between;gap:40px;flex-wrap:wrap;margin-bottom:32px;padding-bottom:32px;border-bottom:1px solid rgba(255,255,255,.1)}';
        $out .= '.ff__brand{max-width:300px}';
        $out .= '.ff__name{font-family:"Playfair Display",serif;font-size:20px;font-weight:700;color:#fff;margin-bottom:8px}';
        $out .= '.ff__desc{font-size:13px;opacity:.75;line-height:1.6}';
        $out .= '.ff__col h4{color:#d4a574;font-size:15px;font-weight:700;margin-bottom:12px}';
        $out .= '.ff__col ul{list-style:none;padding:0;margin:0}.ff__col li{margin-bottom:8px}';
        $out .= '.ff__col a{color:#c5b8ac;text-decoration:none;font-size:14px;opacity:.85;transition:all .2s}.ff__col a:hover{color:#d4a574;opacity:1}';
        $out .= '.ff__contact p{font-size:14px;margin-bottom:6px;opacity:.85;display:flex;align-items:center;gap:8px}';
        $out .= '.ff__contact a{color:#c5b8ac}.ff__contact a:hover{color:#d4a574}';
        $out .= '.ff__social{display:flex;gap:10px;margin-top:12px}';
        $out .= '.ff__social a{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;color:#c5b8ac;text-decoration:none;transition:all .2s;font-size:14px}';
        $out .= '.ff__social a:hover{background:#d4a574;color:white;transform:translateY(-2px)}';
        $out .= '.ff__bottom{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;font-size:13px}';
        $out .= '.ff__copyright{opacity:.65}';
        $out .= '.ff__legal{display:flex;gap:16px;flex-wrap:wrap}';
        $out .= '.ff__legal a{color:#c5b8ac;text-decoration:none;font-size:13px;opacity:.65;transition:all .2s}.ff__legal a:hover{color:#d4a574;opacity:1}';
        $out .= '@media(max-width:768px){.ff__top{flex-direction:column;gap:32px}.ff__bottom{flex-direction:column;text-align:center}}';
        $out .= '</style>';

        $out .= '<footer class="ff"><div class="ff__inner"><div class="ff__top">';

        // Brand
        $out .= '<div class="ff__brand">';
        $logo = $f['logo_url'] ?? _ss('logo_url', '');
        if ($logo) {
            $out .= '<a href="/"><img src="' . htmlspecialchars($logo) . '" alt="' . htmlspecialchars(strip_tags($name)) . '" style="height:40px;width:auto;margin-bottom:12px"></a>';
        }
        $out .= '<div class="ff__name">' . $name . '</div>';
        $out .= '<p class="ff__desc">' . $description . '</p>';

        if (!empty($socialLinks)) {
            $icons = ['facebook'=>'fab fa-facebook-f','instagram'=>'fab fa-instagram','linkedin'=>'fab fa-linkedin-in','twitter'=>'fab fa-x-twitter','youtube'=>'fab fa-youtube','tiktok'=>'fab fa-tiktok','whatsapp'=>'fab fa-whatsapp'];
            $out .= '<div class="ff__social">';
            foreach ($socialLinks as $s) {
                $sUrl  = $s['url'] ?? $s['link'] ?? '#';
                $sName = strtolower($s['name'] ?? $s['platform'] ?? $s['type'] ?? '');
                $sIcon = $icons[$sName] ?? 'fas fa-link';
                $out .= '<a href="' . htmlspecialchars($sUrl) . '" target="_blank" rel="noopener" aria-label="' . htmlspecialchars(ucfirst($sName)) . '"><i class="' . $sIcon . '"></i></a>';
            }
            $out .= '</div>';
        }
        $out .= '</div>';

        // Colonnes dynamiques ou défaut
        if (!empty($columns)) {
            foreach ($columns as $col) {
                $out .= '<div class="ff__col"><h4>' . htmlspecialchars($col['title'] ?? $col['label'] ?? '') . '</h4>';
                if (!empty($col['links']) && is_array($col['links'])) {
                    $out .= '<ul>';
                    foreach ($col['links'] as $link) {
                        $out .= '<li><a href="' . htmlspecialchars($link['url'] ?? '#') . '">' . htmlspecialchars($link['label'] ?? $link['text'] ?? '') . '</a></li>';
                    }
                    $out .= '</ul>';
                }
                $out .= '</div>';
            }
        } else {
            $out .= '<div class="ff__col"><h4>Navigation</h4><ul>';
            foreach ([['Accueil','/'],['Acheter','/acheter'],['Vendre','/vendre'],['Estimer','/estimation'],['Secteurs','/secteurs'],['Blog','/blog']] as $l) {
                $out .= '<li><a href="' . $l[1] . '">' . $l[0] . '</a></li>';
            }
            $out .= '</ul></div>';
            $out .= '<div class="ff__col"><h4>Informations</h4><ul>';
            $out .= '<li><a href="/mentions-legales">Mentions légales</a></li>';
            $out .= '<li><a href="/politique-confidentialite">Confidentialité</a></li>';
            $out .= '<li><a href="/contact">Contact</a></li>';
            $out .= '</ul></div>';
        }

        // Contact
        $out .= '<div class="ff__col ff__contact"><h4>Contact</h4>';
        if ($phone) $out .= '<p><i class="fas fa-phone-alt" style="color:#d4a574"></i> <a href="tel:' . htmlspecialchars(preg_replace('/\s+/', '', strip_tags($phone))) . '">' . $phone . '</a></p>';
        if ($email) $out .= '<p><i class="fas fa-envelope" style="color:#d4a574"></i> <a href="mailto:' . $email . '">' . $email . '</a></p>';
        $address = htmlspecialchars($f['address'] ?? $f['company_address'] ?? _ss('address', ''));
        if ($address) $out .= '<p><i class="fas fa-map-marker-alt" style="color:#d4a574"></i> ' . $address . '</p>';
        $out .= '</div>';

        $out .= '</div>'; // ff__top

        // Bottom
        $out .= '<div class="ff__bottom">';
        $out .= '<div class="ff__copyright">' . htmlspecialchars(str_replace(['{year}','{{year}}'], date('Y'), $copyright)) . '</div>';
        $out .= '<div class="ff__legal">';
        if (!empty($legalLinks)) {
            foreach ($legalLinks as $ll) {
                $out .= '<a href="' . htmlspecialchars($ll['url'] ?? '#') . '">' . htmlspecialchars($ll['label'] ?? $ll['text'] ?? '') . '</a>';
            }
        } else {
            $out .= '<a href="/mentions-legales">Mentions légales</a>';
            $out .= '<a href="/politique-confidentialite">Confidentialité</a>';
        }
        $out .= '</div></div>'; // ff__legal + ff__bottom
        $out .= '</div></footer>'; // ff__inner + footer

        return $out;
    }
}

if (!function_exists('buildLayoutVars')) {
    /**
     * Construit le tableau de variables pour les remplacements dans custom_html
     */
    function buildLayoutVars(array $data): array {
        $vars = [
            '{{site_name}}' => htmlspecialchars(_ss('site_name', defined('SITE_NAME') ? SITE_NAME : 'Eduardo De Sul Immobilier')),
'{{site_url}}'  => htmlspecialchars(_ss('site_url',  defined('SITE_URL')  ? SITE_URL  : 'https://eduardo-desul-immobilier.fr')),

            '{{logo}}'      => htmlspecialchars($data['logo_url'] ?? _ss('logo_url', '')),
            '{{logo_url}}'  => htmlspecialchars($data['logo_url'] ?? _ss('logo_url', '')),
            '{{phone}}'     => htmlspecialchars($data['phone_number'] ?? $data['phone'] ?? $data['contact_phone'] ?? _ss('phone', '')),
            '{{email}}'     => htmlspecialchars($data['email'] ?? $data['contact_email'] ?? _ss('email', '')),
            '{{address}}'   => htmlspecialchars($data['address'] ?? $data['company_address'] ?? _ss('address', '')),
            '{{year}}'      => date('Y'),
        ];
        if (class_exists('SiteSettings')) {
            foreach (SiteSettings::all() as $key => $val) {
                $vars['{{setting.' . $key . '}}'] = htmlspecialchars($val);
                $vars['{{' . $key . '}}']         = htmlspecialchars($val);
            }
        }
        return $vars;
    }
}

if (!function_exists('renderHfWrapper')) {
    /**
     * Charge header+footer spécifiques à une page si définis (header_id / footer_id)
     * Sinon utilise les actifs par défaut
     */
    function renderHfWrapper(PDO $db, array $page, string $context = ''): array {
        $hf = getHeaderFooter($db, $context);
        if (!empty($page['header_id'])) {
            try {
                $s = $db->prepare("SELECT * FROM headers WHERE id = ? LIMIT 1");
                $s->execute([$page['header_id']]);
                $r = $s->fetch(PDO::FETCH_ASSOC);
                if ($r) $hf['header'] = $r;
            } catch (Exception $e) {}
        }
        if (!empty($page['footer_id'])) {
            try {
                $s = $db->prepare("SELECT * FROM footers WHERE id = ? LIMIT 1");
                $s->execute([$page['footer_id']]);
                $r = $s->fetch(PDO::FETCH_ASSOC);
                if ($r) $hf['footer'] = $r;
            } catch (Exception $e) {}
        }
        return $hf;
    }
}