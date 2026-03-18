<?php
/**
 * ══════════════════════════════════════════════════════════════
 * MODULE SEO — Hub central / Index v2.1 COMPLETE
 * /admin/modules/seo/index.php
 * Tous les onglets fonctionnels
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
?>

<style>
/* ══════════════════════════════════════════════════════════════
   SEO HUB v2.1 — Harmonisé Articles v2.3
   Namespace : .seo-*  (pas de collision)
   ══════════════════════════════════════════════════════════════ */

.seo-wrap { font-family: var(--font, 'Inter', sans-serif); }

/* ─── Onglets (style articles) ─── */
.seo-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--border, #e5e7eb); margin-bottom: 24px; flex-wrap: wrap; }
.seo-tab { padding: 12px 20px; font-size: .85rem; font-weight: 600; color: var(--text-2, #6b7280); cursor: pointer; border-bottom: 3px solid transparent; transition: all .2s; text-decoration: none; display: flex; align-items: center; gap: 6px; white-space: nowrap; }
.seo-tab:hover { color: var(--text, #111827); }
.seo-tab.active { color: #8b5cf6; border-bottom-color: #8b5cf6; }
.seo-tab .badge { font-size: .7rem; background: #ef4444; color: #fff; padding: 1px 6px; border-radius: 10px; font-weight: 700; }

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

/* ─── Message pour onglets vides ─── */
.seo-empty-state {
    text-align: center; padding: 60px 20px; color: var(--text-3, #9ca3af);
}
.seo-empty-state i { font-size: 2.5rem; opacity: .2; margin-bottom: 12px; display: block; }
.seo-empty-state h3 { color: var(--text-2, #6b7280); font-size: 1rem; font-weight: 600; margin-bottom: 6px; }
.seo-empty-state p { font-size: .9rem; color: var(--text-3, #9ca3af); }

/* ─── Responsive ─── */
@media (max-width: 768px) {
    .seo-banner { flex-direction: column; gap: 16px; align-items: flex-start; }
    .seo-stats { justify-content: flex-start; }
    .seo-tabs { gap: 4px; }
    .seo-tab { padding: 10px 12px; font-size: .75rem; }
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
            <?php if ($pagesWarn > 0): ?><span class="badge"><?= $pagesWarn ?></span><?php endif; ?>
        </a>
        <a href="?page=seo&tab=semantic" class="seo-tab <?= $currentTab === 'semantic' ? 'active' : '' ?>">
            <i class="fas fa-brain"></i> Sémantique
        </a>
        <a href="?page=seo&tab=local" class="seo-tab <?= $currentTab === 'local' ? 'active' : '' ?>">
            <i class="fas fa-location-dot"></i> SEO Local & GMB
            <?php if ($gmbReviews > 0): ?><span class="badge"><?= $gmbReviews ?></span><?php endif; ?>
        </a>
        <a href="?page=seo&tab=analytics" class="seo-tab <?= $currentTab === 'analytics' ? 'active' : '' ?>">
            <i class="fas fa-chart-line"></i> Analytics
        </a>
        <a href="?page=seo&tab=guide" class="seo-tab <?= $currentTab === 'guide' ? 'active' : '' ?>">
            <i class="fas fa-book"></i> Guide SEO
        </a>
    </div>

    <!-- ════════════════════════════════════════════════════════ -->
    <!-- TAB: OVERVIEW                                             -->
    <!-- ════════════════════════════════════════════════════════ -->
    <?php if ($currentTab === 'overview'): ?>

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

    <div style="padding: 40px; text-align: center; background: var(--surface, #fff); border-radius: 14px; border: 1px solid var(--border, #e5e7eb);">
        <p style="color: var(--text-2, #6b7280); font-size: .95rem; line-height: 1.6;">
            <strong>Vue d'ensemble du SEO.</strong> Cliquez sur les onglets ci-dessus pour accéder aux modules détaillés.
        </p>
        <div style="margin-top: 20px; display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
            <a href="?page=seo&tab=pages" style="padding: 9px 18px; background: #6366f1; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: .85rem;">SEO Pages</a>
            <a href="?page=seo&tab=semantic" style="padding: 9px 18px; background: #8b5cf6; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: .85rem;">Sémantique</a>
            <a href="?page=seo&tab=local" style="padding: 9px 18px; background: #0891b2; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: .85rem;">GMB</a>
            <a href="?page=seo&tab=analytics" style="padding: 9px 18px; background: #10b981; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: .85rem;">Analytics</a>
            <a href="?page=seo&tab=guide" style="padding: 9px 18px; background: #64748b; color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: .85rem;">Guide</a>
        </div>
    </div>

    <!-- ════════════════════════════════════════════════════════ -->
    <!-- TAB: SEO PAGES                                            -->
    <!-- ════════════════════════════════════════════════════════ -->
    <?php elseif ($currentTab === 'pages'): ?>

    <div class="seo-banner">
        <div class="seo-banner-left">
            <h2><i class="fas fa-file-lines"></i> SEO Pages</h2>
            <p>Optimisation technique et on-page</p>
        </div>
        <div class="seo-stats">
            <div class="seo-stat">
                <div class="num"><?= $totalPages ?></div>
                <div class="lbl">Pages</div>
            </div>
            <div class="seo-stat">
                <div class="num green"><?= $pagesOk ?></div>
                <div class="lbl">Excellentes</div>
            </div>
            <div class="seo-stat">
                <div class="num amber"><?= $pagesWarn ?></div>
                <div class="lbl">À optimiser</div>
            </div>
        </div>
    </div>

    <div class="seo-empty-state">
        <i class="fas fa-file-lines"></i>
        <h3>Module SEO Pages</h3>
        <p>Chargement du module détaillé...</p>
        <?php 
            $seoPages = __DIR__ . '/modules/seo-pages/index.php';
            if (file_exists($seoPages)) {
                include $seoPages;
            } else {
                echo '<p style="color: #ef4444; margin-top: 12px;">⚠️ Module non trouvé : ' . htmlspecialchars($seoPages) . '</p>';
            }
        ?>
    </div>

    <!-- ════════════════════════════════════════════════════════ -->
    <!-- TAB: SÉMANTIQUE                                           -->
    <!-- ════════════════════════════════════════════════════════ -->
    <?php elseif ($currentTab === 'semantic'): ?>

    <div class="seo-banner">
        <div class="seo-banner-left">
            <h2><i class="fas fa-brain"></i> Analyse Sémantique</h2>
            <p>Champ lexical, mots-clés manquants, questions à couvrir</p>
        </div>
        <div class="seo-stats">
            <div class="seo-stat">
                <div class="num"><?= $totalContent ?></div>
                <div class="lbl">Contenus</div>
            </div>
            <div class="seo-stat">
                <div class="num violet"><?= $semAnalyzed ?></div>
                <div class="lbl">Analysés</div>
            </div>
            <div class="seo-stat">
                <div class="num"><?= $semPct ?>%</div>
                <div class="lbl">Couverture</div>
            </div>
        </div>
    </div>

    <div class="seo-empty-state">
        <i class="fas fa-brain"></i>
        <h3>Module Sémantique</h3>
        <p>Chargement du module détaillé...</p>
        <?php 
            $seoSemantic = __DIR__ . '/modules/seo-semantic/index.php';
            if (file_exists($seoSemantic)) {
                include $seoSemantic;
            } else {
                echo '<p style="color: #ef4444; margin-top: 12px;">⚠️ Module non trouvé : ' . htmlspecialchars($seoSemantic) . '</p>';
            }
        ?>
    </div>

    <!-- ════════════════════════════════════════════════════════ -->
    <!-- TAB: SEO LOCAL & GMB                                      -->
    <!-- ════════════════════════════════════════════════════════ -->
    <?php elseif ($currentTab === 'local'): ?>

    <div class="seo-banner">
        <div class="seo-banner-left">
            <h2><i class="fas fa-location-dot"></i> SEO Local & GMB</h2>
            <p>Publications, avis, partenaires locaux</p>
        </div>
        <div class="seo-stats">
            <div class="seo-stat">
                <div class="num"><?= $gmbPubs ?></div>
                <div class="lbl">Publications</div>
            </div>
            <div class="seo-stat">
                <div class="num" style="color: <?= $gmbPending > 0 ? '#f59e0b' : '#10b981' ?>;"><?= $gmbPending ?></div>
                <div class="lbl">En attente</div>
            </div>
            <div class="seo-stat">
                <div class="num" style="color: <?= $gmbReviews > 0 ? '#ef4444' : '#10b981' ?>;"><?= $gmbReviews ?></div>
                <div class="lbl">Avis à répondre</div>
            </div>
        </div>
    </div>

    <div class="seo-empty-state">
        <i class="fas fa-location-dot"></i>
        <h3>Module SEO Local & GMB</h3>
        <p>Chargement du module détaillé...</p>
        <?php 
            $seoLocal = __DIR__ . '/modules/local-seo/index.php';
            if (file_exists($seoLocal)) {
                include $seoLocal;
            } else {
                echo '<p style="color: #ef4444; margin-top: 12px;">⚠️ Module non trouvé : ' . htmlspecialchars($seoLocal) . '</p>';
            }
        ?>
    </div>

    <!-- ════════════════════════════════════════════════════════ -->
    <!-- TAB: ANALYTICS                                            -->
    <!-- ════════════════════════════════════════════════════════ -->
    <?php elseif ($currentTab === 'analytics'): ?>

    <div class="seo-banner">
        <div class="seo-banner-left">
            <h2><i class="fas fa-chart-line"></i> Analytics & Statistiques</h2>
            <p>Trafic, sources, conversions sur 30 jours</p>
        </div>
        <div class="seo-stats">
            <div class="seo-stat">
                <div class="num"><?= number_format($analyticsViews) ?></div>
                <div class="lbl">Vues</div>
            </div>
            <div class="seo-stat">
                <div class="num green"><?= $analyticsConv ?></div>
                <div class="lbl">Conversions</div>
            </div>
            <div class="seo-stat">
                <div class="num" style="color: #6366f1;">
                    <?= $analyticsViews > 0 && $analyticsConv > 0 ? round($analyticsConv / $analyticsViews * 100, 1) : '—' ?>%
                </div>
                <div class="lbl">Taux conv.</div>
            </div>
        </div>
    </div>

    <div class="seo-empty-state">
        <i class="fas fa-chart-line"></i>
        <h3>Module Analytics</h3>
        <p>Chargement du module détaillé...</p>
        <?php 
            $analytics = __DIR__ . '/modules/analytics/index.php';
            if (file_exists($analytics)) {
                include $analytics;
            } else {
                echo '<p style="color: #ef4444; margin-top: 12px;">⚠️ Module non trouvé : ' . htmlspecialchars($analytics) . '</p>';
            }
        ?>
    </div>

    <?php endif; // currentTab ?>

</div><!-- /seo-wrap -->