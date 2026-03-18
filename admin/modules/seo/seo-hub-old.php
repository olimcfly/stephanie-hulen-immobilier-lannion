<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE SEO — Hub central / Index v2.0
 * /admin/modules/seo/index.php
 * Pattern : harmonisé sur Articles v2.3 (tableaux + grille)
 * Onglets : Vue d'ensemble | SEO Pages | Sémantique | Local | Analytics | Guide
 * ══════════════════════════════════════════════════════════════
 */
defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);

// ─── DB ───────────────────────────────────────────────────────
if (!isset($pdo)) {
    if (!defined('DB_HOST')) {
        $cfgPath = dirname(dirname(dirname(__DIR__))) . '/config/config.php';
        if (file_exists($cfgPath)) require_once $cfgPath;
    }
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) { $pdo = null; }
}

// ─── Helpers rapides ──────────────────────────────────────────
$qs = fn(string $sql, array $p = []) => (function() use ($pdo, $sql, $p) {
    if (!$pdo) return 0;
    try { $st = $pdo->prepare($sql); $st->execute($p); return (int)$st->fetchColumn(); }
    catch (Throwable) { return 0; }
})();

$cols = fn(string $t) => (function() use ($pdo, $t) {
    if (!$pdo) return [];
    try { return $pdo->query("SHOW COLUMNS FROM `{$t}`")->fetchAll(PDO::FETCH_COLUMN); }
    catch (Throwable) { return []; }
})();

$tableExists = fn(string $t) => (function() use ($pdo, $t) {
    if (!$pdo) return false;
    try { $pdo->query("SELECT 1 FROM `{$t}` LIMIT 1"); return true; }
    catch (Throwable) { return false; }
})();

// ─── Routing onglets ──────────────────────────────────────────
$currentTab = $_GET['tab'] ?? 'overview';
$validTabs  = ['overview', 'pages', 'semantic', 'local', 'analytics', 'guide'];
if (!in_array($currentTab, $validTabs)) $currentTab = 'overview';

// Rediriger vers guide.php si tab=guide
if ($currentTab === 'guide') {
    $guideFile = __DIR__ . '/guide.php';
    if (file_exists($guideFile)) { require $guideFile; return; }
}

// ─── Stats globales ───────────────────────────────────────────
$hasPages    = $tableExists('pages');
$pageCols    = $hasPages ? $cols('pages') : [];
$totalPages  = $hasPages ? $qs("SELECT COUNT(*) FROM pages") : 0;
$hasPageSeo  = in_array('seo_score', $pageCols);
$avgPageSeo  = ($hasPages && $hasPageSeo) ? $qs("SELECT COALESCE(AVG(NULLIF(seo_score,0)),0) FROM pages") : 0;
$pagesOk     = ($hasPages && $hasPageSeo) ? $qs("SELECT COUNT(*) FROM pages WHERE seo_score >= 80") : 0;
$pagesWarn   = ($hasPages && $hasPageSeo) ? $qs("SELECT COUNT(*) FROM pages WHERE seo_score > 0 AND seo_score < 60") : 0;

$hasArticles   = $tableExists('articles');
$articleCols   = $hasArticles ? $cols('articles') : [];
$totalArticles = $hasArticles ? $qs("SELECT COUNT(*) FROM articles") : 0;
$hasArtSeo     = in_array('seo_score', $articleCols);
$avgArtSeo     = ($hasArticles && $hasArtSeo) ? $qs("SELECT COALESCE(AVG(NULLIF(seo_score,0)),0) FROM articles") : 0;

$hasArtSem  = $hasArticles && in_array('semantic_score', $articleCols);
$hasPageSem = $hasPages    && in_array('semantic_score', $pageCols);
$semAnalyzed = 0;
if ($hasArtSem)  $semAnalyzed += $qs("SELECT COUNT(*) FROM articles WHERE semantic_score > 0");
if ($hasPageSem) $semAnalyzed += $qs("SELECT COUNT(*) FROM pages WHERE semantic_score > 0");
$totalContent = $totalPages + $totalArticles;
$semPct = $totalContent > 0 ? round(($semAnalyzed / $totalContent) * 100) : 0;

$hasGmbPubs   = $tableExists('gmb_publications');
$hasGmbReviews= $tableExists('gmb_reviews');
$hasGmbAvis   = $tableExists('gmb_avis');
$gmbPubs      = $hasGmbPubs ? $qs("SELECT COUNT(*) FROM gmb_publications") : 0;
$gmbPending   = $hasGmbPubs ? $qs("SELECT COUNT(*) FROM gmb_publications WHERE status IN ('draft','scheduled')") : 0;
$gmbReviews   = $hasGmbReviews ? $qs("SELECT COUNT(*) FROM gmb_reviews WHERE reply_status='pending'") : ($hasGmbAvis ? $qs("SELECT COUNT(*) FROM gmb_avis WHERE repondu=0") : 0);

$hasAnalytics  = $tableExists('page_views');
$analyticsViews= $hasAnalytics ? $qs("SELECT COUNT(*) FROM page_views WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)") : 0;
$analyticsConv = $tableExists('conversion_events') ? $qs("SELECT COUNT(*) FROM conversion_events WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)") : 0;

$avgGlobal = 0;
$cntGlobal = 0;
if ($avgPageSeo > 0)  { $avgGlobal += $avgPageSeo;  $cntGlobal++; }
if ($avgArtSeo  > 0)  { $avgGlobal += $avgArtSeo;   $cntGlobal++; }
$avgGlobal = $cntGlobal > 0 ? (int)round($avgGlobal / $cntGlobal) : 0;

$aiProvider = '';
if (defined('ANTHROPIC_API_KEY') && !empty(ANTHROPIC_API_KEY)) $aiProvider = 'Claude';
elseif (defined('OPENAI_API_KEY') && !empty(OPENAI_API_KEY))   $aiProvider = 'OpenAI';

$scoreColor = fn(int $s) => $s >= 80 ? '#10b981' : ($s >= 60 ? '#65a30d' : ($s >= 40 ? '#f59e0b' : ($s > 0 ? '#ef4444' : '#94a3b8')));
$scoreGrade = fn(int $s) => $s >= 80 ? 'Excellent' : ($s >= 60 ? 'Bon' : ($s >= 40 ? 'À améliorer' : ($s > 0 ? 'Critique' : 'Non analysé')));
?>

<style>
/* ══════════════════════════════════════════════════════════════
   SEO HUB v2.0 — Harmonisé Articles v2.3
   Namespace : .seo-*  (pas de collision)
   ══════════════════════════════════════════════════════════════ */

.seo-wrap { font-family: var(--font, 'Inter', sans-serif); }

/* ─── Onglets (style articles) ─── */
.seo-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--border, #e5e7eb); margin-bottom: 24px; }
.seo-tab { padding: 12px 20px; font-size: .85rem; font-weight: 600; color: var(--text-2, #6b7280); cursor: pointer; border-bottom: 3px solid transparent; transition: all .2s; text-decoration: none; display: flex; align-items: center; gap: 6px; }
.seo-tab:hover { color: var(--text, #111827); }
.seo-tab.active { color: #8b5cf6; border-bottom-color: #8b5cf6; }

/* ─── Banner hero ─── */
.seo-banner {
    background: var(--surface, #fff);
    border-radius: 16px; padding: 26px 30px; margin-bottom: 22px;
    display: flex; align-items: center; justify-content: space-between;
    border: 1px solid var(--border, #e5e7eb); position: relative; overflow: hidden;
}
.seo-banner::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, #8b5cf6, #3b82f6, #0891b2); opacity: .75;
}
.seo-banner::after {
    content: ''; position: absolute; top: -40%; right: -5%;
    width: 220px; height: 220px;
    background: radial-gradient(circle, rgba(139,92,246,.05), transparent 70%);
    border-radius: 50%; pointer-events: none;
}

.seo-banner-left { position: relative; z-index: 1; }
.seo-banner-left h2 { font-size: 1.35rem; font-weight: 700; color: var(--text, #111827); margin: 0 0 4px; display: flex; align-items: center; gap: 10px; }
.seo-banner-left h2 i { font-size: 16px; color: #8b5cf6; }
.seo-banner-left p { color: var(--text-2, #6b7280); font-size: .85rem; margin: 0; }

.seo-stats { display: flex; gap: 8px; position: relative; z-index: 1; flex-wrap: wrap; }
.seo-stat { text-align: center; padding: 10px 16px; background: var(--surface-2, #f9fafb); border-radius: 12px; border: 1px solid var(--border, #e5e7eb); min-width: 72px; transition: all .2s; }
.seo-stat .num { font-size: 1.45rem; font-weight: 800; line-height: 1; color: var(--text, #111827); }
.seo-stat .num.green  { color: #10b981; }
.seo-stat .num.amber  { color: #f59e0b; }
.seo-stat .num.violet { color: #7c3aed; }
.seo-stat .lbl { font-size: .58rem; color: var(--text-3, #9ca3af); text-transform: uppercase; letter-spacing: .06em; font-weight: 600; margin-top: 3px; }

/* ─── Quick actions grid ─── */
.seo-quick {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px; margin-bottom: 24px;
}
.seo-quick-item {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 11px;
    padding: 14px;
    text-decoration: none; color: var(--text, #111827);
    display: flex; align-items: center; gap: 11px;
    transition: all .17s;
}
.seo-quick-item:hover {
    border-color: var(--qi-color, #8b5cf6);
    box-shadow: 0 4px 14px rgba(0,0,0,.06);
    transform: translateY(-1px);
}
.seo-quick-icon {
    width: 36px; height: 36px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; flex-shrink: 0; color: #fff;
}
.seo-quick-label { font-size: 12px; font-weight: 700; line-height: 1.3; }
.seo-quick-sub   { font-size: 10.5px; color: var(--text-3, #9ca3af); margin-top: 1px; }

/* ─── Module Cards (4 colonnes) ─── */
.seo-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 900px) { .seo-grid { grid-template-columns: 1fr; } }

.seo-card {
    background: var(--surface, #fff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 14px;
    overflow: hidden;
    transition: all .2s;
    display: flex; flex-direction: column;
    position: relative;
}
.seo-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0,0,0,.08);
    border-color: var(--card-color, #8b5cf6);
}
.seo-card-accent {
    height: 4px;
    background: linear-gradient(90deg, var(--card-color, #8b5cf6), color-mix(in srgb, var(--card-color, #8b5cf6) 60%, #fff));
}
.seo-card-body { padding: 20px; flex: 1; }
.seo-card-head {
    display: flex; align-items: flex-start; gap: 14px; margin-bottom: 14px;
}
.seo-card-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 19px; flex-shrink: 0; color: #fff;
}
.seo-card-title { font-size: 15px; font-weight: 800; color: var(--text, #111827); margin-bottom: 3px; }
.seo-card-desc  { font-size: 12px; color: var(--text-2, #6b7280); line-height: 1.5; }

.seo-card-stats {
    display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap;
}
.seo-mini-stat {
    flex: 1; min-width: 70px;
    background: var(--surface-2, #f8fafc);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 9px; padding: 10px 12px;
    text-align: center;
}
.seo-mini-stat-val {
    font-size: 18px; font-weight: 900; color: var(--text, #111827);
    line-height: 1; margin-bottom: 2px;
}
.seo-mini-stat-label {
    font-size: 10px; color: var(--text-3, #9ca3af); font-weight: 500;
}

.seo-score-ring {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 6px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 700; margin-bottom: 10px;
}
.seo-score-ring.green  { background: #d1fae5; color: #059669; }
.seo-score-ring.amber  { background: #fef3c7; color: #b45309; }
.seo-score-ring.red    { background: #fee2e2; color: #b91c1c; }

.seo-check {
    display: flex; align-items: center; gap: 9px;
    padding: 6px 0; font-size: 12px; color: var(--text, #111827);
    border-bottom: 1px solid var(--border, #e5e7eb);
}
.seo-check:last-child { border: 0; }
.seo-check-dot {
    width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
}

.seo-card-actions {
    padding: 12px 20px;
    border-top: 1px solid var(--border, #e5e7eb);
    display: flex; align-items: center; justify-content: space-between;
    background: var(--surface-2, #f8fafc);
}
.seo-card-cta {
    font-size: 12px; font-weight: 700; color: var(--card-color, #8b5cf6);
    display: flex; align-items: center; gap: 5px;
    text-decoration: none; transition: gap .15s;
}
.seo-card-cta:hover { gap: 8px; }

/* ─── Section title ─── */
.seo-section-title {
    font-size: 10.5px; font-weight: 800; text-transform: uppercase;
    letter-spacing: .09em; color: var(--text-3, #9ca3af);
    margin-bottom: 12px;
    display: flex; align-items: center; gap: 8px;
}
.seo-section-title::after {
    content: ''; flex: 1; height: 1px;
    background: var(--border, #e5e7eb);
}

/* ─── AI Banner ─── */
.seo-ai-banner {
    background: linear-gradient(135deg, rgba(139,92,246,.08), rgba(236,72,153,.06));
    border: 1px solid rgba(139,92,246,.2);
    border-radius: 12px; padding: 14px 18px;
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 24px; font-size: 12.5px;
}
.seo-ai-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: linear-gradient(135deg, #8b5cf6, #ec4899);
    flex-shrink: 0; box-shadow: 0 0 0 3px rgba(139,92,246,.2);
    animation: seo-pulse 2s infinite;
}
@keyframes seo-pulse {
    0%,100% { box-shadow: 0 0 0 3px rgba(139,92,246,.2); }
    50%      { box-shadow: 0 0 0 6px rgba(139,92,246,.08); }
}

/* ─── Tips boxes ─── */
.seo-tips {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 8px;
}
@media (max-width: 900px) { .seo-tips { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 600px) { .seo-tips { grid-template-columns: 1fr; } }

.seo-tip { background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 11px; padding: 14px; display: flex; gap: 11px; align-items: flex-start; }
.seo-tip-icon { width: 34px; height: 34px; border-radius: 9px; background: var(--tip-bg, #f3f4f6); color: var(--tip-color, #6b7280); display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
.seo-tip h4 { font-size: 12.5px; font-weight: 700; color: var(--text, #111827); margin: 0 0 3px; }
.seo-tip p { font-size: 11.5px; color: var(--text-3, #6b7280); line-height: 1.5; margin: 0; }

/* Animations */
.seo-card { animation: seoFadeUp .3s ease backwards; }
.seo-card:nth-child(1) { animation-delay: 0s; }
.seo-card:nth-child(2) { animation-delay: .05s; }
.seo-card:nth-child(3) { animation-delay: .1s; }
.seo-card:nth-child(4) { animation-delay: .15s; }
@keyframes seoFadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}

@media (max-width: 768px) {
    .seo-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .seo-stats { justify-content: flex-start; }
    .seo-quick { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="seo-wrap">

    <!-- ─── Onglets ─── -->
    <div class="seo-tabs">
        <a href="?page=seo&tab=overview" class="seo-tab <?= $currentTab === 'overview' ? 'active' : '' ?>">
            <i class="fas fa-th-large"></i> Vue d'ensemble
        </a>
        <a href="?page=seo&tab=pages" class="seo-tab <?= $currentTab === 'pages' ? 'active' : '' ?>">
            <i class="fas fa-file-lines"></i> SEO Pages
            <?php if ($pagesWarn > 0): ?><span style="font-size:.7rem;background:#ef4444;color:#fff;padding:1px 6px;border-radius:10px;font-weight:700;"><?= $pagesWarn ?></span><?php endif; ?>
        </a>
        <a href="?page=seo&tab=semantic" class="seo-tab <?= $currentTab === 'semantic' ? 'active' : '' ?>">
            <i class="fas fa-brain"></i> Sémantique
        </a>
        <a href="?page=seo&tab=local" class="seo-tab <?= $currentTab === 'local' ? 'active' : '' ?>">
            <i class="fas fa-location-dot"></i> SEO Local & GMB
            <?php if ($gmbReviews > 0): ?><span style="font-size:.7rem;background:#ef4444;color:#fff;padding:1px 6px;border-radius:10px;font-weight:700;"><?= $gmbReviews ?></span><?php endif; ?>
        </a>
        <a href="?page=seo&tab=analytics" class="seo-tab <?= $currentTab === 'analytics' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Analytics
        </a>
        <a href="?page=seo&tab=guide" class="seo-tab <?= $currentTab === 'guide' ? 'active' : '' ?>">
            <i class="fas fa-book"></i> Guide SEO
        </a>
    </div>

    <?php if ($currentTab === 'overview'): ?>

    <!-- ─── BANNER ─── -->
    <div class="seo-banner">
        <div class="seo-banner-left">
            <h2><i class="fas fa-magnifying-glass"></i> Référencement SEO</h2>
            <p><?= $totalContent ?> contenus · <?= $totalPages ?> pages · <?= $totalArticles ?> articles</p>
        </div>
        <div class="seo-stats">
            <?php if ($avgGlobal > 0): ?>
            <div class="seo-stat">
                <div class="num <?= $avgGlobal >= 80 ? 'green' : ($avgGlobal >= 60 ? '' : ($avgGlobal >= 40 ? 'amber' : 'red')) ?>"><?= $avgGlobal ?>%</div>
                <div class="lbl">Score moyen</div>
            </div>
            <?php endif; ?>
            <div class="seo-stat">
                <div class="num green"><?= $semPct ?>%</div>
                <div class="lbl">Sémantique</div>
            </div>
            <?php if ($analyticsViews): ?>
            <div class="seo-stat">
                <div class="num green"><?= number_format($analyticsViews) ?></div>
                <div class="lbl">Vues / 30j</div>
            </div>
            <?php endif; ?>
            <?php if ($gmbReviews > 0): ?>
            <div class="seo-stat">
                <div class="num amber"><?= $gmbReviews ?></div>
                <div class="lbl">Avis à répondre</div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ─── IA Banner ─── -->
    <?php if ($aiProvider): ?>
    <div class="seo-ai-banner">
        <div class="seo-ai-dot"></div>
        <div>
            <strong><?= $aiProvider ?> connecté</strong> — Optimisation SEO et sémantique disponibles.
        </div>
        <div style="margin-left:auto;font-size:11px;color:#8b5cf6;font-weight:700;"><?= $aiProvider ?> IA</div>
    </div>
    <?php endif; ?>

    <!-- ─── QUICK ACTIONS ─── -->
    <div class="seo-section-title"><i class="fas fa-bolt"></i> Actions rapides</div>
    <div class="seo-quick">
        <a href="?page=seo&tab=pages" class="seo-quick-item" style="--qi-color:#6366f1">
            <div class="seo-quick-icon" style="background:#6366f11a;color:#6366f1;"><i class="fas fa-sync-alt"></i></div>
            <div>
                <div class="seo-quick-label">Analyser pages</div>
                <div class="seo-quick-sub">Score SEO</div>
            </div>
        </a>
        <a href="?page=seo&tab=semantic" class="seo-quick-item" style="--qi-color:#8b5cf6">
            <div class="seo-quick-icon" style="background:#8b5cf61a;color:#8b5cf6;"><i class="fas fa-brain"></i></div>
            <div>
                <div class="seo-quick-label">Sémantique</div>
                <div class="seo-quick-sub"><?= $semAnalyzed ?> analysés</div>
            </div>
        </a>
        <a href="?page=seo&tab=local" class="seo-quick-item" style="--qi-color:#0891b2">
            <div class="seo-quick-icon" style="background:#0891b21a;color:#0891b2;"><i class="fas fa-bullhorn"></i></div>
            <div>
                <div class="seo-quick-label">Pubs GMB</div>
                <div class="seo-quick-sub"><?= $gmbPending ?> en attente</div>
            </div>
        </a>
        <a href="?page=seo&tab=local" class="seo-quick-item" style="--qi-color:#f59e0b">
            <div class="seo-quick-icon" style="background:#f59e0b1a;color:#f59e0b;"><i class="fas fa-star"></i></div>
            <div>
                <div class="seo-quick-label">Répondre avis</div>
                <div class="seo-quick-sub"><?= $gmbReviews ?> avis</div>
            </div>
        </a>
        <a href="?page=seo&tab=analytics" class="seo-quick-item" style="--qi-color:#10b981">
            <div class="seo-quick-icon" style="background:#10b9811a;color:#10b981;"><i class="fas fa-chart-area"></i></div>
            <div>
                <div class="seo-quick-label">Statistiques</div>
                <div class="seo-quick-sub"><?= $analyticsViews ?> vues</div>
            </div>
        </a>
        <a href="?page=seo&tab=guide" class="seo-quick-item" style="--qi-color:#6366f1">
            <div class="seo-quick-icon" style="background:#6366f11a;color:#6366f1;"><i class="fas fa-book"></i></div>
            <div>
                <div class="seo-quick-label">Apprendre SEO</div>
                <div class="seo-quick-sub">Guide complet</div>
            </div>
        </a>
    </div>

    <!-- ─── MODULES GRID ─── -->
    <div class="seo-section-title"><i class="fas fa-layer-group"></i> Modules SEO</div>
    <div class="seo-grid">

        <!-- Card 1: SEO Pages -->
        <div class="seo-card" style="--card-color:#6366f1;">
            <div class="seo-card-accent"></div>
            <div class="seo-card-body">
                <div class="seo-card-head">
                    <div class="seo-card-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="fas fa-magnifying-glass"></i></div>
                    <div>
                        <div class="seo-card-title">SEO des Pages</div>
                        <div class="seo-card-desc">Score technique, meta, indexation par page</div>
                    </div>
                </div>
                <div class="seo-card-stats">
                    <div class="seo-mini-stat">
                        <div class="seo-mini-stat-val"><?= $totalPages ?></div>
                        <div class="seo-mini-stat-label">Pages</div>
                    </div>
                    <div class="seo-mini-stat">
                        <div class="seo-mini-stat-val" style="color:#10b981;"><?= $pagesOk ?></div>
                        <div class="seo-mini-stat-label">Excellentes</div>
                    </div>
                    <div class="seo-mini-stat">
                        <div class="seo-mini-stat-val" style="color:#ef4444;"><?= $pagesWarn ?></div>
                        <div class="seo-mini-stat-label">À optimiser</div>
                    </div>
                </div>
                <div>
                    <div class="seo-check"><div class="seo-check-dot" style="background:#10b981;"></div><span>Meta titles OK</span></div>
                    <div class="seo-check"><div class="seo-check-dot" style="background:#10b981;"></div><span>Indexation active</span></div>
                    <div class="seo-check"><div class="seo-check-dot" style="background:<?= $aiProvider ? '#10b981' : '#94a3b8' ?>;"></div><span>IA disponible</span></div>
                </div>
            </div>
            <div class="seo-card-actions">
                <a href="?page=seo&tab=pages" class="seo-card-cta">Ouvrir <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <!-- Card 2: Sémantique -->
        <div class="seo-card" style="--card-color:#8b5cf6;">
            <div class="seo-card-accent"></div>
            <div class="seo-card-body">
                <div class="seo-card-head">
                    <div class="seo-card-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);"><i class="fas fa-brain"></i></div>
                    <div>
                        <div class="seo-card-title">Analyse Sémantique</div>
                        <div class="seo-card-desc">Champ lexical, mots-clés manquants, questions</div>
                    </div>
                </div>
                <div class="seo-card-stats">
                    <div class="seo-mini-stat">
                        <div class="seo-mini-stat-val"><?= $totalContent ?></div>
                        <div class="seo-mini-stat-label">Contenus</div>
                    </div>
                    <div class="seo-mini-stat">
                        <div class="seo-mini-stat-val" style="color:#8b5cf6;"><?= $semAnalyzed ?></div>
                        <div class="seo-mini-stat-label">Analysés</div>
                    </div>
                    <div class="seo-mini-stat">
                        <div class="seo-mini-stat-val" style="color:#94a3b8;"><?= $totalContent - $semAnalyzed ?></div>
                        <div class="seo-mini-stat-label">Restants</div>
                    </div>
                </div>
                <div>
                    <div class="seo-check"><div class="seo-check-dot" style="background:#8b5cf6;"></div><span>Pages et articles</span></div>
                    <div class="seo-check"><div class="seo-check-dot" style="background:#8b5cf6;"></div><span>GMB intégré</span></div>
                    <div class="seo-check"><div class="seo-check-dot" style="background:<?= $aiProvider ? '#8b5cf6' : '#94a3b8' ?>;"></div><span><?= $aiProvider ?: 'IA' ?> disponible</span></div>
                </div>
            </div>
            <div class="seo-card-actions">
                <a href="?page=seo&tab=semantic" class="seo-card-cta">Ouvrir <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <!-- Card 3: Local & GMB -->
        <div class="seo-card" style="--card-color:#0891b2;">
            <div class="seo-card-accent"></div>
            <div class="seo-card-body">
                <div class="seo-card-head">
                    <div class="seo-card-icon" style="background:linear-gradient(135deg,#0891b2,#0e7490);"><i class="fas fa-location-dot"></i></div>
                    <div>
                        <div class="seo-card-title">SEO Local & GMB</div>
                        <div class="seo-card-desc">Publications, avis clients, partenaires locaux</div>
                    </div>
                </div>
                <div class="seo-card-stats">
                    <div class="seo-mini-stat">
                        <div class="seo-mini-stat-val"><?= $gmbPubs ?></div>
                        <div class="seo-mini-stat-label">Publications</div>
                    </div>
                    <div class="seo-mini-stat">
                        <div class="seo-mini-stat-val" style="color:<?= $gmbPending > 0 ? '#f59e0b' : '#10b981' ?>;"><?= $gmbPending ?></div>
                        <div class="seo-mini-stat-label">En attente</div>
                    </div>
                    <div class="seo-mini-stat">
                        <div class="seo-mini-stat-val" style="color:<?= $gmbReviews > 0 ? '#ef4444' : '#10b981' ?>;"><?= $gmbReviews ?></div>
                        <div class="seo-mini-stat-label">Avis à répondre</div>
                    </div>
                </div>
                <div>
                    <div class="seo-check"><div class="seo-check-dot" style="background:#0891b2;"></div><span>Publications GMB</span></div>
                    <div class="seo-check"><div class="seo-check-dot" style="background:#0891b2;"></div><span>Avis clients</span></div>
                    <div class="seo-check"><div class="seo-check-dot" style="background:#0891b2;"></div><span>Partenaires locaux</span></div>
                </div>
                <?php if ($gmbReviews > 0): ?>
                <div style="margin-top:12px;background:#fef3c7;border-radius:8px;padding:10px 12px;font-size:11px;color:#b45309;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong><?= $gmbReviews ?> avis</strong> sans réponse
                </div>
                <?php endif; ?>
            </div>
            <div class="seo-card-actions">
                <a href="?page=seo&tab=local" class="seo-card-cta">Ouvrir <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>

        <!-- Card 4: Analytics -->
        <div class="seo-card" style="--card-color:#10b981;">
            <div class="seo-card-accent"></div>
            <div class="seo-card-body">
                <div class="seo-card-head">
                    <div class="seo-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fas fa-chart-line"></i></div>
                    <div>
                        <div class="seo-card-title">Analytics</div>
                        <div class="seo-card-desc">Trafic, sources, conversions sur 30 jours</div>
                    </div>
                </div>
                <div class="seo-card-stats">
                    <div class="seo-mini-stat">
                        <div class="seo-mini-stat-val"><?= number_format($analyticsViews) ?></div>
                        <div class="seo-mini-stat-label">Vues</div>
                    </div>
                    <div class="seo-mini-stat">
                        <div class="seo-mini-stat-val" style="color:#10b981;"><?= $analyticsConv ?></div>
                        <div class="seo-mini-stat-label">Conversions</div>
                    </div>
                    <div class="seo-mini-stat">
                        <div class="seo-mini-stat-val" style="color:#6366f1;">
                            <?= $analyticsViews > 0 && $analyticsConv > 0 ? round($analyticsConv / $analyticsViews * 100, 1) : '—' ?>%
                        </div>
                        <div class="seo-mini-stat-label">Taux conv.</div>
                    </div>
                </div>
                <div>
                    <div class="seo-check"><div class="seo-check-dot" style="background:#10b981;"></div><span>Suivi de pages actif</span></div>
                    <div class="seo-check"><div class="seo-check-dot" style="background:#10b981;"></div><span>Conversions suivies</span></div>
                    <div class="seo-check"><div class="seo-check-dot" style="background:#10b981;"></div><span>Périodes : 7j/30j/90j/1an</span></div>
                </div>
            </div>
            <div class="seo-card-actions">
                <a href="?page=seo&tab=analytics" class="seo-card-cta">Ouvrir <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
    </div>

    <!-- ─── TIPS ─── -->
    <div class="seo-section-title" style="margin-top:8px;"><i class="fas fa-lightbulb"></i> Bonnes pratiques SEO immobilier</div>
    <div class="seo-tips">
        <div class="seo-tip" style="--tip-bg:#e0e7ff;--tip-color:#4f46e5;">
            <div class="seo-tip-icon"><i class="fas fa-file-lines"></i></div>
            <div>
                <h4>SEO On-Page</h4>
                <p>Meta title unique (50-60 car) avec ville + mot-clé principal</p>
            </div>
        </div>
        <div class="seo-tip" style="--tip-bg:#faf5ff;--tip-color:#7c3aed;">
            <div class="seo-tip-icon"><i class="fas fa-brain"></i></div>
            <div>
                <h4>Sémantique</h4>
                <p>Couvrir le champ lexical : estimation, vente, notaire, diagnostic</p>
            </div>
        </div>
        <div class="seo-tip" style="--tip-bg:#ecf0ff;--tip-color:#0891b2;">
            <div class="seo-tip-icon"><i class="fas fa-location-dot"></i></div>
            <div>
                <h4>GMB Local</h4>
                <p>2-3 publications/semaine. Répondre à 100% des avis en &lt;24h</p>
            </div>
        </div>
        <div class="seo-tip" style="--tip-bg:#f0fdf4;--tip-color:#10b981;">
            <div class="seo-tip-icon"><i class="fas fa-link"></i></div>
            <div>
                <h4>Backlinks Locaux</h4>
                <p>Échanger avec artisans, notaires, diagnostiqueurs du secteur</p>
            </div>
        </div>
        <div class="seo-tip" style="--tip-bg:#fff7ed;--tip-color:#f59e0b;">
            <div class="seo-tip-icon"><i class="fas fa-chart-line"></i></div>
            <div>
                <h4>Suivi Analytics</h4>
                <p>Taux rebond, pages/session, taux conversion formulaire</p>
            </div>
        </div>
        <div class="seo-tip" style="--tip-bg:#fdf2f8;--tip-color:#ec4899;">
            <div class="seo-tip-icon"><i class="fas fa-map-pin"></i></div>
            <div>
                <h4>Maillage Local</h4>
                <p>Lier pages quartier entre elles et vers page principale</p>
            </div>
        </div>
    </div>

    <?php endif; // tab overview ?>

</div><!-- /seo-wrap -->