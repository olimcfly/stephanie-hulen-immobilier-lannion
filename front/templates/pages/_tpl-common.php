<?php
/**
 * /front/templates/_tpl-common.php
 * CSS partagé entre tous les templates tN-*.php
 * Inclus via require_once dans chaque template.
 * Adapté pour t1-t19 (pages + ressources)
 */
?>
<style>
/* ── Variables & reset commun ─────────────────────────── */
:root {
    --tp-primary:    #1B3A4B;
    --tp-primary-l:  #2C5F7C;
    --tp-primary-d:  #122A37;
    --tp-accent:     #C8A96E;
    --tp-accent-l:   #E8D5A8;
    --tp-accent-d:   #A68B4B;
    --tp-white:      #FFFFFF;
    --tp-bg:         #F8F6F3;
    --tp-bg2:        #F0EDE8;
    --tp-text:       #1a1a2e;
    --tp-text2:      #4a5568;
    --tp-text3:      #718096;
    --tp-border:     #E2D9CC;
    --tp-red:        #ef4444;
    --tp-green:      #10b981;
    --tp-ff-display: 'Playfair Display', Georgia, serif;
    --tp-ff-body:    'DM Sans', 'Segoe UI', sans-serif;
    --tp-radius:     16px;
    --tp-shadow:     0 4px 24px rgba(27,58,75,.10);
    --tp-shadow-lg:  0 12px 48px rgba(27,58,75,.16);
}
html, body { margin:0; padding:0; }
.tp-page *, .tp-page *::before, .tp-page *::after { box-sizing:border-box; }
.tp-page { font-family:var(--tp-ff-body); color:var(--tp-text); line-height:1.6; }
.tp-page a { color:inherit; text-decoration:none; }
.tp-page img { max-width:100%; height:auto; display:block; }

/* ── Edit mode zones ──────────────────────────────────── */
.ef-zone { outline:2px dashed rgba(99,102,241,.35); outline-offset:3px; border-radius:4px; cursor:pointer; position:relative; transition:outline-color .15s, background .15s; }
.ef-zone:hover { outline-color:rgba(99,102,241,.8); background:rgba(99,102,241,.04); }
.ef-zone::before { content:attr(data-field); position:absolute; top:-22px; left:0; background:#6366f1; color:#fff; font-size:9px; font-family:system-ui,sans-serif; font-weight:700; padding:2px 7px; border-radius:4px; white-space:nowrap; pointer-events:none; z-index:9999; opacity:0; transition:opacity .15s; }
.ef-zone:hover::before { opacity:1; }
.ef-rich { display:block; }
.ef-empty { color:rgba(99,102,241,.5); font-style:italic; font-size:.85em; }

/* ── Layout ───────────────────────────────────────────── */
.tp-container    { max-width:1140px; margin:0 auto; padding:0 24px; }
.tp-container-sm { max-width:760px;  margin:0 auto; padding:0 24px; }

/* ── Hero partagé ─────────────────────────────────────── */
.tp-hero {
    background:linear-gradient(145deg,var(--tp-primary-d) 0%,var(--tp-primary) 55%,var(--tp-primary-l) 100%);
    padding:90px 0 70px; position:relative; overflow:hidden;
}
.tp-hero::before { content:''; position:absolute; inset:0; background:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E"); pointer-events:none; }
.tp-hero-inner { position:relative; z-index:1; max-width:1140px; margin:0 auto; padding:0 24px; }
.tp-eyebrow { display:inline-flex; align-items:center; gap:8px; background:rgba(200,169,110,.15); border:1px solid rgba(200,169,110,.3); color:var(--tp-accent-l); font-size:.75rem; font-weight:700; padding:6px 16px; border-radius:40px; letter-spacing:.06em; text-transform:uppercase; margin-bottom:24px; }
.tp-eyebrow::before { content:'◆'; font-size:.5rem; }
.tp-hero-h1 { font-family:var(--tp-ff-display); font-size:clamp(1.9rem,4vw,3rem); font-weight:800; color:var(--tp-white); line-height:1.15; margin:0 0 20px; max-width:740px; letter-spacing:-.02em; }
.tp-hero-sub { font-size:1rem; color:rgba(255,255,255,.78); max-width:580px; margin:0 0 36px; line-height:1.75; }
.tp-hero-cta { display:inline-flex; align-items:center; gap:10px; background:var(--tp-accent); color:var(--tp-primary-d); font-weight:800; font-size:.95rem; padding:16px 32px; border-radius:50px; box-shadow:0 4px 20px rgba(200,169,110,.35); transition:all .2s; }
.tp-hero-cta:hover { background:var(--tp-accent-l); transform:translateY(-2px); }
.tp-hero-cta::after { content:'→'; }

/* ── Titres de section ────────────────────────────────── */
.tp-section-title { font-family:var(--tp-ff-display); font-size:clamp(1.5rem,3vw,2.2rem); font-weight:800; color:var(--tp-primary); text-align:center; margin:0 0 48px; letter-spacing:-.02em; }
.tp-section-badge { display:inline-flex; align-items:center; gap:8px; background:rgba(200,169,110,.1); border:1px solid rgba(200,169,110,.25); color:var(--tp-accent-d); font-size:.72rem; font-weight:700; padding:5px 14px; border-radius:40px; letter-spacing:.06em; text-transform:uppercase; margin-bottom:16px; }

/* ── Cards génériques ─────────────────────────────────── */
.tp-card { background:var(--tp-white); border-radius:var(--tp-radius); border:1px solid var(--tp-border); padding:28px 24px; box-shadow:var(--tp-shadow); transition:transform .2s,box-shadow .2s; }
.tp-card:hover { transform:translateY(-3px); box-shadow:var(--tp-shadow-lg); }
.tp-grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; }
.tp-grid-2 { display:grid; grid-template-columns:repeat(2,1fr); gap:24px; }

/* ── Guide SEO items (partagé t1/t2/…) ───────────────── */
.tp-guide-item { display:grid; grid-template-columns:72px 1fr; gap:28px; align-items:start; padding:32px; background:var(--tp-bg); border-radius:var(--tp-radius); border:1px solid var(--tp-border); transition:border-color .2s,box-shadow .2s; }
.tp-guide-item:hover { border-color:var(--tp-accent); box-shadow:var(--tp-shadow); }
.tp-guide-num { font-family:var(--tp-ff-display); font-size:2.4rem; font-weight:900; color:var(--tp-accent); line-height:1; letter-spacing:-.04em; padding-top:4px; }
.tp-guide-h3 { font-family:var(--tp-ff-display); font-size:1.1rem; font-weight:800; color:var(--tp-primary); margin:0 0 12px; }
.tp-guide-body { font-size:.88rem; color:var(--tp-text2); line-height:1.8; }
.tp-guide-body p { margin:0 0 12px; }
.tp-guide-body p:last-child { margin-bottom:0; }

/* ── CTA finale (partagé) ─────────────────────────────── */
.tp-cta-section { background:linear-gradient(135deg,var(--tp-primary-d) 0%,var(--tp-primary) 100%); padding:80px 0; text-align:center; position:relative; overflow:hidden; }
.tp-cta-section::before { content:''; position:absolute; bottom:-60px; right:-60px; width:280px; height:280px; background:radial-gradient(circle,rgba(200,169,110,.12),transparent 65%); border-radius:50%; }
.tp-cta-title { font-family:var(--tp-ff-display); font-size:clamp(1.6rem,3.5vw,2.4rem); font-weight:800; color:var(--tp-white); margin:0 0 16px; letter-spacing:-.02em; position:relative; }
.tp-cta-text  { font-size:1rem; color:rgba(255,255,255,.75); max-width:520px; margin:0 auto 36px; line-height:1.7; position:relative; }
.tp-cta-btn   { display:inline-flex; align-items:center; gap:10px; background:var(--tp-accent); color:var(--tp-primary-d); font-weight:800; font-size:1rem; padding:18px 38px; border-radius:50px; box-shadow:0 6px 24px rgba(200,169,110,.4); transition:all .2s; position:relative; }
.tp-cta-btn:hover { background:var(--tp-accent-l); transform:translateY(-2px); }
.tp-cta-btn::after { content:'→'; }
.tp-cta-btn-outline { display:inline-flex; align-items:center; gap:8px; background:transparent; color:rgba(255,255,255,.85); border:1px solid rgba(255,255,255,.35); font-weight:600; font-size:.9rem; padding:14px 26px; border-radius:50px; transition:all .2s; }
.tp-cta-btn-outline:hover { background:rgba(255,255,255,.1); }

/* ── Boutons ──────────────────────────────────────────── */
.tp-btn-primary { display:inline-flex; align-items:center; gap:8px; background:var(--tp-primary); color:var(--tp-white); font-weight:700; font-size:.9rem; padding:14px 28px; border-radius:50px; box-shadow:var(--tp-shadow); transition:all .2s; }
.tp-btn-primary:hover { background:var(--tp-primary-l); transform:translateY(-2px); }
.tp-btn-primary::after { content:'→'; }
.tp-btn-gold { display:inline-flex; align-items:center; gap:8px; background:var(--tp-accent); color:var(--tp-primary-d); font-weight:800; font-size:.9rem; padding:14px 28px; border-radius:50px; box-shadow:0 4px 20px rgba(200,169,110,.3); transition:all .2s; }
.tp-btn-gold:hover { background:var(--tp-accent-l); transform:translateY(-2px); }
.tp-btn-gold::after { content:'→'; }

/* ── Steps (méthode) ──────────────────────────────────── */
.tp-steps { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; margin-bottom:44px; }
.tp-step { background:var(--tp-white); border-radius:var(--tp-radius); border:1px solid var(--tp-border); padding:32px 26px; box-shadow:var(--tp-shadow); transition:transform .2s; }
.tp-step:hover { transform:translateY(-3px); }
.tp-step-num   { font-family:var(--tp-ff-display); font-size:3.5rem; font-weight:900; color:rgba(27,58,75,.07); line-height:1; margin-bottom:16px; letter-spacing:-.04em; }
.tp-step-title { font-weight:800; font-size:1rem; color:var(--tp-primary); margin-bottom:12px; }
.tp-step-text  { font-size:.83rem; color:var(--tp-text2); line-height:1.65; }
.tp-step-text p { margin:0 0 8px; }

/* ── Sections alternées ───────────────────────────────── */
.tp-section-light { background:var(--tp-bg);    padding:80px 0; }
.tp-section-white { background:var(--tp-white); padding:80px 0; }
.tp-section-dark  { background:var(--tp-primary); padding:80px 0; }
.tp-section-dark .tp-section-title { color:var(--tp-white); }

/* ── Rich text body ───────────────────────────────────── */
.tp-rich-body { font-size:.9rem; color:var(--tp-text2); line-height:1.8; }
.tp-rich-body p { margin:0 0 14px; }
.tp-rich-body p:last-child { margin-bottom:0; }
.tp-rich-body strong { color:var(--tp-primary); font-weight:700; }
.tp-rich-body h2,.tp-rich-body h3 { font-family:var(--tp-ff-display); color:var(--tp-primary); font-weight:800; }
.tp-rich-body ul { padding-left:20px; }
.tp-rich-body li { margin-bottom:8px; }

/* ── Stats row ────────────────────────────────────────── */
.tp-stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:0; background:var(--tp-white); border-bottom:1px solid var(--tp-border); }
.tp-stat { text-align:center; padding:24px 16px; border-right:1px solid var(--tp-border); }
.tp-stat:last-child { border-right:none; }
.tp-stat-num { font-family:var(--tp-ff-display); font-size:2.2rem; font-weight:900; color:var(--tp-primary); line-height:1; margin-bottom:4px; }
.tp-stat-lbl { font-size:.75rem; color:var(--tp-text3); text-transform:uppercase; letter-spacing:.05em; font-weight:600; }

/* ── Tag chips ────────────────────────────────────────── */
.tp-tags-row { display:flex; flex-wrap:wrap; gap:10px; margin:24px 0 32px; }
.tp-tag-chip { background:var(--tp-bg); border:1px solid var(--tp-border); border-radius:50px; padding:6px 16px; font-size:.78rem; font-weight:700; color:var(--tp-primary); }

/* ════════════════════════════════════════════════════════ */
/* NOUVEAUX STYLES POUR RESSOURCES (t17-t19) */
/* ════════════════════════════════════════════════════════ */

/* ── Filtrage & Recherche ─────────────────────────────── */
.guides-filter { 
    background:var(--tp-white); 
    border:1px solid var(--tp-border); 
    border-radius:var(--tp-radius); 
    padding:24px; 
    margin-bottom:40px; 
    display:flex; 
    gap:16px; 
    flex-wrap:wrap; 
    align-items:center; 
}
.guides-filter input,
.guides-filter select { 
    padding:11px 14px; 
    border:1px solid var(--tp-border); 
    border-radius:8px; 
    font-size:.9rem; 
    font-family:inherit; 
    background:var(--tp-white);
    color:var(--tp-text);
}
.guides-filter input { 
    flex:1; 
    min-width:200px; 
}
.guides-filter input:focus,
.guides-filter select:focus {
    outline:none;
    border-color:var(--tp-accent);
    box-shadow:0 0 0 3px rgba(200,169,110,.1);
}
.guides-filter select { 
    cursor:pointer; 
}
.guides-filter button { 
    padding:11px 24px; 
    background:var(--tp-accent); 
    color:var(--tp-primary-d); 
    border:none; 
    border-radius:8px; 
    font-weight:700; 
    cursor:pointer; 
    transition:all .2s;
    font-family:inherit;
}
.guides-filter button:hover { 
    background:var(--tp-accent-d); 
    transform:translateY(-1px);
}

/* ── Grille de guides ─────────────────────────────────── */
.guides-grid { 
    display:grid; 
    grid-template-columns:repeat(auto-fill, minmax(300px, 1fr)); 
    gap:32px; 
}

/* ── Cartes de guides ─────────────────────────────────── */
.guide-card { 
    background:var(--tp-white); 
    border:1px solid var(--tp-border); 
    border-radius:var(--tp-radius); 
    overflow:hidden; 
    transition:all .3s; 
    display:flex; 
    flex-direction:column;
    box-shadow:var(--tp-shadow);
}
.guide-card:hover { 
    box-shadow:var(--tp-shadow-lg); 
    border-color:var(--tp-accent); 
    transform:translateY(-4px); 
}
.guide-card-header { 
    padding:24px; 
    background:linear-gradient(135deg, var(--tp-primary) 0%, var(--tp-primary-d) 100%); 
    color:var(--tp-white); 
}
.guide-card-icon { 
    font-size:2.5rem; 
    margin-bottom:12px; 
}
.guide-card-title { 
    font-family:var(--tp-ff-display); 
    font-size:1.2rem; 
    font-weight:800; 
    margin-bottom:8px; 
    line-height:1.3;
}
.guide-card-meta { 
    font-size:.8rem; 
    opacity:.9; 
}
.guide-card-body { 
    padding:24px; 
    flex:1; 
    display:flex; 
    flex-direction:column; 
}
.guide-card-desc { 
    color:var(--tp-text2); 
    line-height:1.6; 
    margin-bottom:16px; 
    flex:1; 
    font-size:.9rem;
}
.guide-card-footer { 
    display:flex; 
    gap:12px; 
    font-size:.8rem; 
    color:var(--tp-text2); 
    text-transform:uppercase; 
    letter-spacing:.05em; 
    margin-bottom:16px; 
}
.guide-card-btn { 
    display:block; 
    text-align:center; 
    padding:12px 20px; 
    background:var(--tp-primary); 
    color:var(--tp-white); 
    text-decoration:none; 
    border-radius:8px; 
    font-weight:700; 
    font-size:.9rem; 
    transition:all .2s;
}
.guide-card-btn:hover { 
    background:var(--tp-primary-l); 
    transform:translateY(-2px);
}

/* ── État vide ────────────────────────────────────────── */
.guides-empty { 
    text-align:center; 
    padding:60px 20px; 
}
.guides-empty h3 { 
    font-size:1.3rem; 
    color:var(--tp-text2); 
    margin-bottom:12px; 
}
.guides-empty p { 
    color:var(--tp-text2); 
    margin-bottom:24px; 
}

/* ── Formulaires (t18) ────────────────────────────────── */
.form-wrapper { 
    background:var(--tp-white); 
    border:1px solid var(--tp-border); 
    border-radius:var(--tp-radius); 
    padding:40px;
    box-shadow:var(--tp-shadow);
}
.form-wrapper h3 { 
    font-family:var(--tp-ff-display); 
    font-size:1.4rem; 
    font-weight:800; 
    color:var(--tp-primary); 
    margin-bottom:8px; 
    display:flex; 
    align-items:center; 
    gap:10px; 
}
.form-group { 
    margin-bottom:20px; 
}
.form-label { 
    display:block; 
    font-size:.75rem; 
    font-weight:700; 
    color:var(--tp-text2); 
    text-transform:uppercase; 
    letter-spacing:.05em; 
    margin-bottom:8px; 
}
.form-input { 
    width:100%; 
    padding:12px 16px; 
    border:1px solid var(--tp-border); 
    border-radius:8px; 
    font-size:.95rem; 
    font-family:inherit;
    color:var(--tp-text);
    transition:all .2s;
}
.form-input:focus { 
    outline:none; 
    border-color:var(--tp-accent); 
    box-shadow:0 0 0 3px rgba(200,169,110,.1); 
}
.form-row { 
    display:grid; 
    grid-template-columns:1fr 1fr; 
    gap:16px; 
}
.form-submit { 
    width:100%; 
    padding:14px 20px; 
    background:var(--tp-accent); 
    color:var(--tp-primary-d); 
    border:none; 
    border-radius:8px; 
    font-weight:700; 
    cursor:pointer; 
    transition:all .2s; 
    margin-top:8px; 
    font-family:inherit;
    font-size:.95rem;
}
.form-submit:hover { 
    background:var(--tp-accent-d); 
    transform:translateY(-1px);
}

/* ── Alertes ──────────────────────────────────────────── */
.alert { 
    padding:14px 16px; 
    border-radius:8px; 
    font-size:.9rem; 
    font-weight:600;
    margin-bottom:20px; 
    display:flex; 
    align-items:center; 
    gap:10px; 
}
.alert.error { 
    background:#fef2f2; 
    color:#dc2626; 
    border:1px solid rgba(220,38,38,.12); 
}
.alert.success { 
    background:#d1fae5; 
    color:#059669; 
    border:1px solid rgba(5,150,105,.12); 
}

/* ── Thank you (t19) ──────────────────────────────────── */
.thankyou-hero { 
    background:linear-gradient(135deg, var(--tp-accent) 0%, var(--tp-accent-d) 100%); 
    color:var(--tp-primary-d); 
    padding:80px 20px; 
    text-align:center; 
    border-radius:var(--tp-radius); 
    margin-bottom:60px; 
}
.thankyou-icon { 
    font-size:4rem; 
    margin-bottom:20px; 
    animation:bounce 2s infinite; 
}
@keyframes bounce { 
    0%, 100% { transform:translateY(0); } 
    50% { transform:translateY(-10px); } 
}
.thankyou-subtitle { 
    font-size:1.3rem; 
    font-weight:600; 
    margin-bottom:12px; 
}
.thankyou-text { 
    font-size:1.05rem; 
    opacity:.95; 
}

/* ── Steps Cards ──────────────────────────────────────── */
.steps-grid { 
    display:grid; 
    grid-template-columns:repeat(auto-fit, minmax(300px, 1fr)); 
    gap:32px; 
    margin-bottom:60px; 
}
.step-card { 
    background:var(--tp-white); 
    border:1px solid var(--tp-border); 
    border-radius:var(--tp-radius); 
    padding:32px; 
    text-align:center;
    box-shadow:var(--tp-shadow);
    transition:all .2s;
}
.step-card:hover {
    transform:translateY(-3px);
    box-shadow:var(--tp-shadow-lg);
}
.step-icon { 
    font-size:2.5rem; 
    margin-bottom:16px; 
}
.step-title { 
    font-family:var(--tp-ff-display); 
    font-size:1.2rem; 
    font-weight:800; 
    color:var(--tp-primary); 
    margin-bottom:12px; 
}
.step-text { 
    color:var(--tp-text2); 
    line-height:1.6; 
    font-size:.95rem;
}

/* ── Action Buttons ───────────────────────────────────── */
.action-buttons { 
    display:flex; 
    gap:16px; 
    justify-content:center; 
    flex-wrap:wrap; 
    margin-top:40px; 
}
.btn { 
    padding:14px 32px; 
    border-radius:8px; 
    font-weight:700; 
    text-decoration:none; 
    transition:all .2s; 
    display:inline-flex; 
    align-items:center; 
    gap:8px;
    font-family:inherit;
}
.btn-primary { 
    background:var(--tp-accent); 
    color:var(--tp-primary-d); 
}
.btn-primary:hover { 
    background:var(--tp-accent-d); 
    transform:translateY(-2px);
}
.btn-secondary { 
    background:var(--tp-primary); 
    color:var(--tp-white); 
}
.btn-secondary:hover { 
    background:var(--tp-primary-l); 
    transform:translateY(-2px);
}

/* ── Chapitre/Items list ──────────────────────────────── */
.guide-chapters { 
    background:var(--tp-white); 
    border:1px solid var(--tp-border); 
    border-radius:var(--tp-radius); 
    padding:24px;
    box-shadow:var(--tp-shadow);
}
.guide-chapters h4 { 
    font-family:var(--tp-ff-display); 
    font-size:1.1rem; 
    font-weight:800; 
    color:var(--tp-primary); 
    margin-bottom:20px; 
}
.guide-chapters ul { 
    list-style:none; 
    padding:0;
    margin:0;
}
.guide-chapters li { 
    padding:12px 0; 
    border-bottom:1px solid var(--tp-border); 
    color:var(--tp-text2); 
    display:flex; 
    align-items:center; 
    gap:10px;
    font-size:.9rem;
}
.guide-chapters li:last-child { 
    border-bottom:none; 
}
.guide-chapters li:before { 
    content:'✓'; 
    color:var(--tp-accent); 
    font-weight:bold;
    flex-shrink:0;
}

/* ── Responsive ───────────────────────────────────────── */
@media (max-width:960px) {
    .tp-grid-3,.tp-steps,.tp-stats-row { grid-template-columns:1fr; }
    .tp-grid-2 { grid-template-columns:1fr; }
    .tp-stats-row .tp-stat { border-right:none; border-bottom:1px solid var(--tp-border); }
    .tp-stats-row .tp-stat:last-child { border-bottom:none; }
    .tp-guide-item { grid-template-columns:1fr; gap:8px; }
    .guides-grid { grid-template-columns:repeat(auto-fill, minmax(280px, 1fr)); }
    .form-row { grid-template-columns:1fr; }
    .steps-grid { grid-template-columns:1fr; }
    .action-buttons { flex-direction:column; }
    .action-buttons .btn { width:100%; justify-content:center; }
}
@media (max-width:600px) {
    .tp-hero { padding:60px 0 50px; }
    .tp-section-light,.tp-section-white,.tp-section-dark,.tp-cta-section { padding:52px 0; }
    .guides-filter { flex-direction:column; }
    .guides-filter input,
    .guides-filter select,
    .guides-filter button { width:100%; }
    .guide-card-header { padding:20px; }
    .guide-card-body { padding:20px; }
    .form-wrapper { padding:24px; }
    .thankyou-hero { padding:60px 20px; }
    .tp-hero-h1 { font-size:1.5rem; }
}
</style>