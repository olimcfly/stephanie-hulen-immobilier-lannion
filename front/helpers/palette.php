<?php
if (!function_exists('eduardoCssVars')) {
    function eduardoCssVars(): string {
        return '<style>
:root {
    --ed-primary:      #1a4d7a;
    --ed-primary-dk:   #0e3a5c;
    --ed-primary-lt:   #1e5f8c;
    --ed-accent:       #d4a574;
    --ed-accent-dk:    #b8864a;
    --ed-accent-lt:    #e8c9a0;
    --ed-bg:           #f9f6f3;
    --ed-card-bg:      #fdf5ec;
    --ed-white:        #ffffff;
    --ed-text:         #2c3e50;
    --ed-text-light:   #6b7280;
    --ed-text-xlight:  #9ca3af;
    --ed-border:       #e8ddd4;
    --ed-border-lt:    #f0e8df;
    --ed-shadow:       0 4px 20px rgba(26,77,122,.08);
    --ed-shadow-lg:    0 12px 40px rgba(26,77,122,.14);
    --ed-shadow-xl:    0 20px 60px rgba(26,77,122,.18);
    --ff-heading:      "Playfair Display", Georgia, serif;
    --ff-body:         "DM Sans", -apple-system, BlinkMacSystemFont, sans-serif;
    --ed-radius:       12px;
    --ed-radius-lg:    16px;
    --ed-radius-xl:    24px;
    --ed-radius-pill:  50px;
    --ed-transition:   all .3s cubic-bezier(.25,.46,.45,.94);
}
</style>';
    }
}

if (!function_exists('eduardoBaseStyles')) {
    function eduardoBaseStyles(): string {
        return '<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:var(--ff-body);line-height:1.6;color:var(--ed-text);background:var(--ed-bg);-webkit-font-smoothing:antialiased}
img{max-width:100%;height:auto;display:block}
a{text-decoration:none;color:inherit}
button{font-family:var(--ff-body);cursor:pointer}
.ed-container{max-width:1200px;margin:0 auto;padding:0 24px}
.ed-section{padding:64px 0}
.ed-btn{display:inline-flex;align-items:center;gap:8px;padding:14px 28px;border-radius:var(--ed-radius-pill);font-family:var(--ff-body);font-size:15px;font-weight:700;transition:var(--ed-transition);cursor:pointer;border:none;text-decoration:none}
.ed-btn--primary{background:var(--ed-accent);color:white}
.ed-btn--primary:hover{background:var(--ed-accent-dk);transform:translateY(-2px)}
.ed-btn--secondary{background:transparent;color:var(--ed-primary);border:2px solid var(--ed-primary)}
.ed-btn--secondary:hover{background:var(--ed-primary);color:white}
.ed-btn--ghost{background:rgba(255,255,255,.15);color:white;border:2px solid rgba(255,255,255,.4)}
.ed-btn--ghost:hover{background:rgba(255,255,255,.25)}
.ed-btn--sm{padding:10px 20px;font-size:13px}
.ed-btn--lg{padding:18px 40px;font-size:17px}
.ed-card{background:var(--ed-white);border-radius:var(--ed-radius-lg);border:1px solid var(--ed-border);box-shadow:var(--ed-shadow);transition:var(--ed-transition);overflow:hidden}
.ed-card:hover{transform:translateY(-5px);box-shadow:var(--ed-shadow-lg);border-color:var(--ed-accent)}
.ed-section__title{font-family:var(--ff-heading);font-size:clamp(24px,3vw,32px);font-weight:700;color:var(--ed-primary);margin-bottom:10px;line-height:1.25}
.ed-section__subtitle{font-size:16px;color:var(--ed-text-light);max-width:600px;line-height:1.6}
.ed-section__header{margin-bottom:40px}
.ed-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.ed-badge--accent{background:rgba(212,165,116,.9);color:white}
.ed-badge--primary{background:rgba(26,77,122,.85);color:white}
.ed-stat{display:flex;align-items:center;gap:12px;padding:12px 16px;background:var(--ed-bg);border-radius:var(--ed-radius)}
.ed-stat__icon{width:40px;height:40px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0}
.ed-stat__value{font-size:16px;font-weight:700;color:var(--ed-primary);line-height:1.2}
.ed-stat__label{font-size:11px;color:var(--ed-text-light);text-transform:uppercase;letter-spacing:.5px}
.ed-empty{text-align:center;padding:80px 20px;color:var(--ed-text-light)}
.ed-empty__icon{font-size:48px;margin-bottom:16px;opacity:.3}
.ed-empty__title{font-family:var(--ff-heading);font-size:24px;font-weight:700;color:var(--ed-primary);margin-bottom:8px}
.ed-empty__text{font-size:15px;margin-bottom:24px}
.ed-text-accent{color:var(--ed-accent)}
.ed-text-primary{color:var(--ed-primary)}
.ed-font-heading{font-family:var(--ff-heading)}
</style>';
    }
}

if (!function_exists('eduardoFonts')) {
    function eduardoFonts(): string {
        return '<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">';
    }
}

if (!function_exists('eduardoHead')) {
    function eduardoHead(?string $extraCss = null): string {
        $out  = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
        $out .= eduardoFonts();
        if (class_exists('SiteSettings')) {
            $out .= SiteSettings::cssVars();
            $out .= SiteSettings::googleFonts();
        }
        $out .= eduardoCssVars();
        $out .= eduardoBaseStyles();
        if ($extraCss) $out .= '<style>' . $extraCss . '</style>';
        return $out;
    }
}
