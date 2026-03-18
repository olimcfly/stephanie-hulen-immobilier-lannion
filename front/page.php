<?php
/**
 * ROUTER FRONTEND - front/page.php
 * v3.4 : ajout statut 'available' dans la détection des biens
 */
if (!defined('FRONT_ROUTER')) define('FRONT_ROUTER', true);
$root = dirname(__DIR__);

require_once $root . '/config/config.php';

if (file_exists($root . '/includes/maintenance-check.php')) {
    require_once $root . '/includes/maintenance-check.php';
}

$db = getDB();
$renderers = __DIR__ . '/renderers/';

// ─── Helper config conseiller ───────────────────────────
$helperPath = __DIR__ . '/helpers/site-config.php';
if (file_exists($helperPath)) require_once $helperPath;

// ─────────────────────────────────────────────────────────
// Helpers globaux
// ─────────────────────────────────────────────────────────
if (!function_exists('siteUrl'))  { function siteUrl(): string  { return SITE_URL; } }
if (!function_exists('siteName')) { function siteName(): string { return SITE_TITLE; } }

if (!function_exists('_ss')) {
    function _ss(string $key, string $default = ''): string {
        try {
            $db = getDB();
            $s = $db->prepare("SELECT value FROM settings WHERE key_name=? LIMIT 1");
            $s->execute([$key]);
            $v = $s->fetchColumn();
            return ($v !== false && $v !== '') ? (string)$v : $default;
        } catch (Exception $e) { return $default; }
    }
}

if (!function_exists('_ac')) {
    /** Lire une valeur dans advisor_context par field_key */
    function _ac(string $key, string $default = ''): string {
        try {
            $db = getDB();
            $s = $db->prepare("SELECT field_value FROM advisor_context WHERE field_key=? LIMIT 1");
            $s->execute([$key]);
            $v = $s->fetchColumn();
            return ($v !== false && $v !== '') ? (string)$v : $default;
        } catch (Exception $e) { return $default; }
    }
}

if (!function_exists('jsonDecode')) {
    function jsonDecode($val): array {
        if (empty($val)) return [];
        if (is_array($val)) return $val;
        $r = json_decode($val, true);
        return is_array($r) ? $r : [];
    }
}
if (!function_exists('truncate')) {
    function truncate(string $str, int $len = 160): string {
        return mb_strlen($str) > $len ? mb_substr($str, 0, $len).'…' : $str;
    }
}
if (!function_exists('eduardoHead')) {
    function eduardoHead(): string {
        return '<link rel="stylesheet" href="/front/assets/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
:root{
  --ed-primary:#1a4d7a;--ed-primary-dk:#0e3a5c;--ed-accent:#d4a574;--ed-accent-lt:#e8c49a;
  --ed-text:#2d3748;--ed-text-light:#718096;--ed-card-bg:#f9f6f3;--ed-border:#e2d9ce;
  --ff-heading:"Playfair Display",serif;--ff-body:"DM Sans",sans-serif;
  --ed-radius:8px;--ed-radius-lg:12px;
  --ed-shadow:0 2px 8px rgba(0,0,0,.07);--ed-shadow-lg:0 8px 30px rgba(0,0,0,.12);
  --ed-transition:all .2s ease;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:var(--ff-body);color:var(--ed-text);background:#fff;line-height:1.6}
.container{max-width:1200px;margin:0 auto;padding:0 24px}
img{max-width:100%;height:auto}a{text-decoration:none;color:inherit}
.ed-btn{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:var(--ed-radius);font-weight:600;font-size:14px;cursor:pointer;transition:var(--ed-transition);border:2px solid transparent}
.ed-btn--primary{background:var(--ed-primary);color:#fff;border-color:var(--ed-primary)}
.ed-btn--primary:hover{background:var(--ed-primary-dk);border-color:var(--ed-primary-dk)}
.ed-btn--secondary{background:var(--ed-accent);color:#fff;border-color:var(--ed-accent)}
.ed-btn--secondary:hover{background:#c49060}
.ed-btn--ghost{background:transparent;color:#fff;border-color:rgba(255,255,255,.4)}
.ed-btn--ghost:hover{background:rgba(255,255,255,.1)}
</style>';
    }
}

// ─────────────────────────────────────────────────────────
// getHeaderFooter
// ─────────────────────────────────────────────────────────
if (!function_exists('getHeaderFooter')) {
    function getHeaderFooter(PDO $db, string $slug = ''): array {
        $result = ['header' => null, 'footer' => null];
        foreach (['headers', 'site_headers'] as $tbl) {
            try {
                $h = $db->query("SELECT * FROM `$tbl` WHERE status='active' ORDER BY is_default DESC, id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if ($h) { $result['header'] = $h; break; }
            } catch (Exception $e) {}
        }
        foreach (['footers', 'site_footers'] as $tbl) {
            try {
                $f = $db->query("SELECT * FROM `$tbl` WHERE status='active' ORDER BY is_default DESC, id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if ($f) { $result['footer'] = $f; break; }
            } catch (Exception $e) {}
        }
        return $result;
    }
}

// ─────────────────────────────────────────────────────────
// renderHeader — supporte nav_align : left / center / right
// ─────────────────────────────────────────────────────────
if (!function_exists('renderHeader')) {
    function renderHeader(?array $header): string {
        if (!$header) $header = [];

        $css = '';
        if (!empty($header['custom_css'])) {
            $css = '<style>'."\n".$header['custom_css']."\n".'</style>'."\n";
        }

        if (!empty($header['custom_html'])) {
            $ch = trim($header['custom_html']);
            if (!($ch[0] === '[' && isset($ch[1]) && $ch[1] === '{')) {
                return $css . $ch;
            }
        }

        $bg     = htmlspecialchars($header['bg_color']    ?? '#ffffff');
        $tc     = htmlspecialchars($header['text_color']  ?? '#1e293b');
        $hv     = htmlspecialchars($header['hover_color'] ?? '#1a4d7a');
        $ht     = max(50, (int)($header['height'] ?? 80));
        $sticky = !empty($header['sticky']) ? 'position:sticky;top:0;z-index:1000;' : 'position:relative;z-index:100;';
        $shadow = !empty($header['shadow'])  ? 'box-shadow:0 2px 12px rgba(0,0,0,.10);' : '';
        $border = !empty($header['border_bottom']) ? "border-bottom:2px solid $hv;" : 'border-bottom:1px solid #e2d9ce;';
        $bp     = (int)($header['mobile_breakpoint'] ?? 768);

        $navAlign = $header['nav_align'] ?? 'center';
        if (!in_array($navAlign, ['left','center','right'])) $navAlign = 'center';

        // Logo
        $logoLink = htmlspecialchars($header['logo_link'] ?? '/');
        if (($header['logo_type'] ?? 'text') === 'image' && !empty($header['logo_url'])) {
            $lw = (int)($header['logo_width'] ?? 150);
            $logoHtml = '<a href="'.$logoLink.'" style="flex-shrink:0;text-decoration:none">
              <img src="'.htmlspecialchars($header['logo_url']).'" alt="'.htmlspecialchars($header['logo_alt'] ?? '').'" style="height:'.min($ht-20,52).'px;width:auto;max-width:'.$lw.'px">
            </a>';
        } else {
            $lt = htmlspecialchars($header['logo_text'] ?? ($header['name'] ?? _ac('advisor_name', _ss('site_name', 'Mon Site'))));
            $logoHtml = '<a href="'.$logoLink.'" style="flex-shrink:0;font-family:\'Playfair Display\',serif;font-size:22px;font-weight:800;color:'.$hv.';text-decoration:none;white-space:nowrap">'.$lt.'</a>';
        }

        // Menu — depuis DB ou fallback générique
        $menuItems = [];
        foreach (['nav_links','menu_items'] as $col) {
            $raw = $header[$col] ?? '';
            if (!$raw) continue;
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && !empty($decoded) && isset($decoded[0]['label'])) {
                $menuItems = $decoded;
                break;
            }
        }
        if (empty($menuItems)) {
            $menuItems = [
                ['label' => 'Accueil',      'url' => '/'],
                ['label' => 'Acheter',      'url' => '/acheter'],
                ['label' => 'Vendre',       'url' => '/vendre'],
                ['label' => 'Nos biens',    'url' => '/biens-immobiliers'],
                ['label' => 'Estimation',   'url' => '/estimation'],
                ['label' => 'Secteurs',     'url' => '/secteurs'],
                ['label' => 'Financement',  'url' => '/financer'],
                ['label' => 'Blog',         'url' => '/blog'],
            ];
        }

        $currentUri = '/' . trim(strtok($_SERVER['REQUEST_URI'] ?? '/', '?'), '/');
        $navLinks = '';
        foreach ($menuItems as $item) {
            $url      = htmlspecialchars($item['url'] ?? '#');
            $label    = htmlspecialchars($item['label'] ?? '');
            $target   = ($item['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener"' : '';
            $isActive = ($currentUri === $item['url']);
            $activeStyle = $isActive ? "font-weight:700;color:{$hv};" : '';
            $navLinks .= '<a href="'.$url.'"'.$target.' class="fh-link" style="color:'.$tc.';text-decoration:none;font-size:14px;font-weight:500;padding:0 14px;white-space:nowrap;transition:color .2s;'.$activeStyle.'">'.$label.'</a>';
        }

        // Téléphone
        $phoneHtml = '';
        if (!empty($header['phone_enabled']) && !empty($header['phone_number'])) {
            $ph = htmlspecialchars($header['phone_number']);
            $phoneHtml = '<a href="tel:'.preg_replace('/\s+/', '', $ph).'" style="color:'.$tc.';text-decoration:none;font-size:13px;font-weight:600;white-space:nowrap;display:flex;align-items:center;gap:5px">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13 19.79 19.79 0 0 1 1.58 4.26 2 2 0 0 1 3.55 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 9.91a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
              '.$ph.'
            </a>';
        }

        // CTA
        $ctaStyles = [
            'primary'  => "background:#1a4d7a;color:#fff;border:none;",
            'secondary'=> "background:transparent;color:{$hv};border:2px solid {$hv};",
            'outline'  => "background:transparent;color:{$tc};border:2px solid {$tc};",
            'gradient' => "background:linear-gradient(135deg,#1a4d7a,#2563a8);color:#fff;border:none;",
        ];
        $ctaHtml = '';
        if (!empty($header['cta_enabled']) && !empty($header['cta_text'])) {
            $cs = $ctaStyles[$header['cta_style'] ?? 'primary'] ?? $ctaStyles['primary'];
            $ctaHtml .= '<a href="'.htmlspecialchars($header['cta_link'] ?? '/contact').'" style="'.$cs.'padding:9px 20px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap;display:inline-block">'.htmlspecialchars($header['cta_text']).'</a>';
        }
        if (!empty($header['cta2_enabled']) && !empty($header['cta2_text'])) {
            $cs = $ctaStyles[$header['cta2_style'] ?? 'secondary'] ?? $ctaStyles['secondary'];
            $ctaHtml .= '<a href="'.htmlspecialchars($header['cta2_link'] ?? '#').'" style="'.$cs.'padding:9px 20px;border-radius:9px;font-size:13px;font-weight:700;text-decoration:none;margin-left:8px;white-space:nowrap;display:inline-block">'.htmlspecialchars($header['cta2_text']).'</a>';
        }

        $uid = 'fh'.substr(md5($header['id'] ?? '1'), 0, 6);

        $rightBlock = trim($phoneHtml.$ctaHtml)
            ? '<div class="fh-desktop fh-right" style="display:flex;align-items:center;gap:16px;flex-shrink:0">'.$phoneHtml.$ctaHtml.'</div>'
            : '';

        $burger = '<button class="fh-burger" onclick="this.closest(\'.'.$uid.'\').querySelector(\'.fh-mobile-nav\').classList.toggle(\'open\')"
            style="display:none;flex-direction:column;gap:5px;background:none;border:none;cursor:pointer;padding:6px;flex-shrink:0;margin-left:auto">
            <span style="width:22px;height:2px;background:'.$tc.';display:block;border-radius:2px"></span>
            <span style="width:22px;height:2px;background:'.$tc.';display:block;border-radius:2px"></span>
            <span style="width:22px;height:2px;background:'.$tc.';display:block;border-radius:2px"></span>
          </button>';

        switch ($navAlign) {
            case 'left':
                $desktopLayout = '<div style="max-width:1260px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:auto 1fr auto;align-items:center;height:'.$ht.'px;gap:24px;position:relative">
                  '.$logoHtml.'
                  <nav class="fh-desktop" style="display:flex;align-items:center;justify-self:start">'.$navLinks.'</nav>
                  '.($rightBlock ?: '<div></div>').'
                  '.$burger.'
                </div>';
                break;
            case 'right':
                $desktopLayout = '<div style="max-width:1260px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:auto 1fr;align-items:center;height:'.$ht.'px;gap:24px;position:relative">
                  '.$logoHtml.'
                  <div class="fh-desktop" style="display:flex;align-items:center;justify-self:end;gap:0">
                    <nav style="display:flex;align-items:center">'.$navLinks.'</nav>
                    '.(!empty(trim($phoneHtml.$ctaHtml)) ? '<div style="display:flex;align-items:center;gap:12px;margin-left:16px;flex-shrink:0">'.$phoneHtml.$ctaHtml.'</div>' : '').'
                  </div>
                  '.$burger.'
                </div>';
                break;
            case 'center':
            default:
                $desktopLayout = '<div style="max-width:1260px;margin:0 auto;padding:0 24px;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;height:'.$ht.'px;gap:16px;position:relative">
                  '.$logoHtml.'
                  <nav class="fh-desktop" style="display:flex;align-items:center;justify-self:center">'.$navLinks.'</nav>
                  <div class="fh-desktop fh-right" style="display:flex;align-items:center;gap:16px;flex-shrink:0;justify-self:end">'.$phoneHtml.$ctaHtml.'</div>
                  '.$burger.'
                </div>';
                break;
        }

        $responsiveCss = '<style>
.'.$uid.' .fh-link:hover{color:'.$hv.' !important}
@media(max-width:'.$bp.'px){
  .'.$uid.' .fh-desktop{display:none !important}
  .'.$uid.' .fh-burger{display:flex !important}
  .'.$uid.' .fh-mobile-nav{display:none;flex-direction:column;position:absolute;top:100%;left:0;right:0;background:'.$bg.';padding:16px;box-shadow:0 8px 24px rgba(0,0,0,.12);border-top:1px solid #e2d9ce;z-index:999}
  .'.$uid.' .fh-mobile-nav.open{display:flex}
  .'.$uid.' .fh-mobile-nav a{padding:10px 16px;border-radius:8px;font-size:14px}
  .'.$uid.' .fh-mobile-nav a:hover{background:rgba(26,77,122,.06)}
}
@media(min-width:'.($bp+1).'px){
  .'.$uid.' .fh-burger{display:none !important}
  .'.$uid.' .fh-mobile-nav{display:none !important}
}
</style>';

        $mobileNav = '<nav class="fh-mobile-nav">
          '.$navLinks.'
          <div style="padding:10px 16px;display:flex;flex-direction:column;gap:8px;margin-top:8px;border-top:1px solid #e2d9ce">
            '.$phoneHtml.$ctaHtml.'
          </div>
        </nav>';

        return $css.$responsiveCss.'
<header class="'.$uid.'" style="background:'.$bg.';'.$sticky.$shadow.$border.'">
  '.$desktopLayout.'
  '.$mobileNav.'
</header>';
    }
}

// ─────────────────────────────────────────────────────────
// renderFooter — 100% dynamique via DB
// ─────────────────────────────────────────────────────────
if (!function_exists('renderFooter')) {
    function renderFooter(?array $footer): string {
        if (!$footer) $footer = [];

        $css = '';
        if (!empty($footer['custom_css'])) $css = '<style>'."\n".$footer['custom_css']."\n".'</style>'."\n";

        if (!empty($footer['custom_html'])) {
            $ch = trim($footer['custom_html']);
            if (!($ch[0] === '[' && isset($ch[1]) && $ch[1] === '{')) {
                return $css . $ch;
            }
        }

        $bg = htmlspecialchars($footer['bg_color']     ?? '#1e293b');
        $tc = htmlspecialchars($footer['text_color']   ?? '#94a3b8');
        $ac = htmlspecialchars($footer['accent_color'] ?? $footer['hover_color'] ?? '#d4a574');

        $cols = json_decode($footer['columns_json'] ?? '[]', true) ?: [];
        $colsHtml = '';
        foreach ($cols as $col) {
            if (empty($col['title']) && empty($col['links'])) continue;
            $colsHtml .= '<div style="min-width:140px">';
            $colsHtml .= '<div style="color:#fff;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px">'.htmlspecialchars($col['title'] ?? '').'</div>';
            foreach ($col['links'] ?? [] as $lnk) {
                $colsHtml .= '<a href="'.htmlspecialchars($lnk['url'] ?? '#').'" style="display:block;color:'.$tc.';text-decoration:none;font-size:13px;margin-bottom:7px;transition:color .2s" onmouseover="this.style.color=\''.$ac.'\'" onmouseout="this.style.color=\''.$tc.'\'">'.htmlspecialchars($lnk['label'] ?? '').'</a>';
            }
            $colsHtml .= '</div>';
        }

        $phone   = htmlspecialchars($footer['phone']   ?? _ss('phone',   _ac('advisor_phone',   '')));
        $email   = htmlspecialchars($footer['email']   ?? _ss('email_support', _ac('advisor_email', '')));
        $address = htmlspecialchars($footer['address'] ?? _ss('address', _ac('advisor_address',  '')));

        $contactHtml = '';
        if ($phone || $email || $address) {
            $contactHtml = '<div style="min-width:180px">
              <div style="color:#fff;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;margin-bottom:12px">Contact</div>
              '.($phone   ? '<a href="tel:'.preg_replace('/\s+/','',$phone).'" style="display:block;color:'.$tc.';text-decoration:none;font-size:13px;margin-bottom:7px">📞 '.$phone.'</a>' : '').'
              '.($email   ? '<a href="mailto:'.$email.'" style="display:block;color:'.$tc.';text-decoration:none;font-size:13px;margin-bottom:7px">✉️ '.$email.'</a>' : '').'
              '.($address ? '<div style="color:'.$tc.';font-size:13px;margin-bottom:7px">📍 '.$address.'</div>' : '').'
            </div>';
        }

        $socIcons = ['facebook'=>'fab fa-facebook-f','instagram'=>'fab fa-instagram','linkedin'=>'fab fa-linkedin-in','youtube'=>'fab fa-youtube','tiktok'=>'fab fa-tiktok','twitter'=>'fab fa-x-twitter'];
        $socialLinks = json_decode($footer['social_links'] ?? '[]', true) ?: [];
        $socHtml = '';
        foreach ($socialLinks as $sl) {
            if (empty($sl['url'])) continue;
            $ico = $socIcons[$sl['network'] ?? ''] ?? 'fas fa-link';
            $socHtml .= '<a href="'.htmlspecialchars($sl['url']).'" target="_blank" rel="noopener" style="width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.08);display:inline-flex;align-items:center;justify-content:center;color:'.$tc.';text-decoration:none;transition:.2s;margin-right:6px" onmouseover="this.style.background=\''.$ac.'\';this.style.color=\'#fff\'" onmouseout="this.style.background=\'rgba(255,255,255,.08)\';this.style.color=\''.$tc.'\'"><i class="'.$ico.'" style="font-size:13px"></i></a>';
        }

        $siteName  = _ss('site_name', _ac('advisor_name', 'Immobilier'));
        $advisorCard = _ac('advisor_card', _ss('advisor_card', ''));
        $copy  = htmlspecialchars($footer['copyright_text'] ?? '© '.date('Y').' '.trim($siteName).'. Tous droits réservés.');
        $badge = htmlspecialchars($footer['badge_text']     ?? $advisorCard);

        $colsSection = ($colsHtml || $contactHtml)
            ? '<div style="display:flex;gap:32px;flex-wrap:wrap;margin-bottom:32px">'.$colsHtml.$contactHtml.'</div>'
            : '';

        return $css.'
<footer style="background:'.$bg.';color:'.$tc.';padding:48px 24px 24px">
  <div style="max-width:1260px;margin:0 auto">
    '.$colsSection.'
    '.($socHtml ? '<div style="margin-bottom:24px">'.$socHtml.'</div>' : '').'
    <div style="border-top:1px solid rgba(255,255,255,.08);padding-top:20px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;font-size:12px">
      <span>'.$copy.'</span>
      '.($badge ? '<span style="color:rgba(255,255,255,.4)">'.$badge.'</span>' : '').'
    </div>
  </div>
</footer>';
    }
}

// ─────────────────────────────────────────────────────────
// Routing
// ─────────────────────────────────────────────────────────
$type = $_GET['type'] ?? null;
$slug = $_GET['slug'] ?? '';

if ($type === 'capture') {
    require_once $renderers . 'capture.php'; exit;
}

$uri   = trim(strtok($_SERVER['REQUEST_URI'], '?'), '/');
$parts = explode('/', $uri);

if ($type) {
    switch ($type) {
        case 'blog':            require_once $renderers . 'blog-listing.php';       exit;
        case 'article':         require_once $renderers . 'article.php';            exit;
        case 'secteur-listing': require_once $renderers . 'secteur-listing.php';   exit;
        case 'secteur':         require_once $renderers . 'secteur.php';            exit;
        case 'guide':           require_once $renderers . 'guide.php';              exit;
        case 'cms':             require_once $renderers . 'cms.php';                exit;
        case 'properties':
        case 'biens':           require_once $renderers . 'properties-listing.php'; exit;
        case 'property':
        case 'bien':            require_once $renderers . 'property-single.php';    exit;
    }
}

if ($uri === '' || $uri === 'index' || $uri === 'index.php') {
    $_GET['slug'] = 'accueil';
    require_once $renderers . 'cms.php'; exit;
}

if ($uri === 'blog') { require_once $renderers . 'blog-listing.php'; exit; }

if ($parts[0] === 'blog' && !empty($parts[1])) {
    $_GET['slug'] = $parts[1]; $slug = $parts[1];
    require_once $renderers . 'article.php'; exit;
}

if ($uri === 'secteurs') { require_once $renderers . 'secteur-listing.php'; exit; }

if ($uri === 'biens-immobiliers' || $uri === 'biens') {
    require_once $renderers . 'properties-listing.php'; exit;
}

if ($parts[0] === 'biens' && !empty($parts[1])) {
    $slug = $parts[1]; $_GET['slug'] = $slug;
    $propFound = false;
    try {
        // ✅ CORRECTION : ajout de 'available' dans les statuts acceptés
        $stProp = $db->prepare(
            "SELECT id FROM properties
             WHERE slug = ?
             AND (statut IN ('actif','active','disponible','available')
                  OR status IN ('actif','active','disponible','available'))
             LIMIT 1"
        );
        $stProp->execute([$slug]);
        $propFound = (bool)$stProp->fetchColumn();
    } catch (Exception $e) {
        // Fallback table biens
        try {
            $stProp = $db->prepare(
                "SELECT id FROM biens
                 WHERE slug = ?
                 AND statut IN ('actif','active','disponible','available')
                 LIMIT 1"
            );
            $stProp->execute([$slug]);
            $propFound = (bool)$stProp->fetchColumn();
        } catch (Exception $e2) {}
    }
    if ($propFound) { require_once $renderers . 'property-single.php'; exit; }
    http_response_code(404);
    if (file_exists($renderers . '404.php')) require_once $renderers . '404.php';
    exit;
}

if ($slug !== '') {
    try {
        $st = $db->prepare("SELECT id FROM secteurs WHERE slug=? AND status='published' LIMIT 1");
        $st->execute([$slug]);
        if ($st->fetchColumn()) { require_once $renderers . 'secteur.php'; exit; }
    } catch (Exception $e) {}
    try {
        $st = $db->prepare("SELECT id FROM articles WHERE slug=? AND status='published' LIMIT 1");
        $st->execute([$slug]);
        if ($st->fetchColumn()) { require_once $renderers . 'article.php'; exit; }
    } catch (Exception $e) {}
    require_once $renderers . 'cms.php'; exit;
}

if ($uri !== '') {
    $slug = $uri;
    try {
        $st = $db->prepare("SELECT id FROM secteurs WHERE slug=? AND status='published' LIMIT 1");
        $st->execute([$slug]);
        if ($st->fetchColumn()) { require_once $renderers . 'secteur.php'; exit; }
    } catch (Exception $e) {}
    try {
        $st = $db->prepare("SELECT id FROM articles WHERE slug=? AND status='published' LIMIT 1");
        $st->execute([$slug]);
        if ($st->fetchColumn()) { require_once $renderers . 'article.php'; exit; }
    } catch (Exception $e) {}
    try {
        $st = $db->prepare("SELECT id FROM pages WHERE slug=? AND (status='published' OR statut='publie') LIMIT 1");
        $st->execute([$slug]);
        if ($st->fetchColumn()) { $_GET['slug'] = $slug; require_once $renderers . 'cms.php'; exit; }
    } catch (Exception $e) {}
}

http_response_code(404);
if (file_exists($renderers . '404.php')) require_once $renderers . '404.php';
else echo '<h1>404 - Page non trouvée</h1>';
