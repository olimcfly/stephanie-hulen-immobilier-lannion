<?php
/**
 * Dashboard IA - Générateur de contenu
 * /admin/modules/ia/index.php
 */

if (!isset($pdo) && !isset($db)) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    } catch (PDOException $e) {
        echo '<div class="mod-flash mod-flash-error"><i class="fas fa-exclamation-circle"></i> Erreur DB: ' . $e->getMessage() . '</div>';
        return;
    }
}
if (isset($pdo) && !isset($db)) $db = $pdo;
if (isset($db) && !isset($pdo)) $pdo = $db;

$aiClassFile = __DIR__ . '/../../../includes/classes/AIContentGenerator.php';
$aiHelperFile = __DIR__ . '/../../../includes/classes/AIContentHelper.php';
if (file_exists($aiClassFile)) require_once $aiClassFile;
if (file_exists($aiHelperFile)) require_once $aiHelperFile;

$message = '';
$generatedContent = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $action = htmlspecialchars($_POST['action'] ?? '');
        $pageName = htmlspecialchars($_POST['page_name'] ?? '');
        $contentType = htmlspecialchars($_POST['content_type'] ?? 'page_intro');

        if (!$pageName) throw new Exception('Veuillez sélectionner une page');

        if (class_exists('AIContentGenerator')) {
            $ai = new AIContentGenerator($db);

            if ($action === 'generate') {
                $customContext = [
                    'page_description' => htmlspecialchars($_POST['page_description'] ?? ''),
                    'target_audience' => htmlspecialchars($_POST['target_audience'] ?? 'Conseillers immobiliers indépendants'),
                    'tone' => htmlspecialchars($_POST['tone'] ?? 'professionnel')
                ];
                $generatedContent = $ai->generatePageContent($pageName, $contentType, $customContext);
                if (isset($_POST['save_to_db']) && $_POST['save_to_db'] === 'on') {
                    $ai->saveContentToDB($pageName, $contentType, $generatedContent);
                    $message = 'success|Contenu généré et sauvegardé';
                } else {
                    $message = 'info|Contenu généré (non sauvegardé)';
                }
            } elseif ($action === 'generate_full') {
                $customContext = [
                    'page_description' => htmlspecialchars($_POST['page_description'] ?? ''),
                    'target_audience' => htmlspecialchars($_POST['target_audience'] ?? 'Conseillers immobiliers indépendants')
                ];
                $generatedContent = $ai->generateFullPageContent($pageName, $customContext);
                $message = 'success|Contenu complet généré';
            }
        } else {
            throw new Exception('Classe AIContentGenerator non trouvée');
        }
    } catch (Exception $e) {
        $message = 'error|' . $e->getMessage();
    }
}

$pages = [
    'acheter-un-bien' => 'Acheter un bien immobilier',
    'vendre-mon-bien' => 'Vendre mon bien immobilier',
    'investir-a-bordeaux' => 'Investir à Bordeaux',
    'estimation' => 'Estimation immobilière',
    'simulation' => 'Simulation de financement',
    'financer-mon-projet' => 'Financer mon projet',
    'prix-immobilier-bordeaux' => 'Prix immobilier à Bordeaux',
    'rapport-marche' => 'Rapport de marché',
    'strategie-vente' => 'Stratégie de vente',
    'secteurs' => 'Secteurs d\'activité',
    'contact' => 'Contact',
    'a-propos' => 'À propos'
];
?>

<style>
.ia-split{display:grid;grid-template-columns:1fr 1fr;gap:24px}
.ia-preview{max-height:550px;overflow-y:auto;background:var(--surface-2);padding:18px;border-radius:var(--radius);font-size:.85rem;line-height:1.7}
.ia-preview h2{color:var(--accent);margin:14px 0 8px;font-size:1.05rem}
.ia-preview h3{color:#7c3aed;margin:10px 0 6px;font-size:.95rem}
.ia-preview p{color:var(--text-2);margin-bottom:8px}
.ia-preview pre{background:var(--surface);padding:10px;border-radius:var(--radius);overflow-x:auto;font-size:.8rem;border:1px solid var(--border)}
.ia-spinner{display:none;text-align:center;padding:30px;color:var(--accent);font-weight:600;font-size:.85rem}
.ia-spinner.active{display:block}
.ia-spinner-ring{display:inline-block;width:20px;height:20px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:iaspin 1s linear infinite;margin-right:8px}
@keyframes iaspin{to{transform:rotate(360deg)}}
@media(max-width:1024px){.ia-split{grid-template-columns:1fr}}
</style>

<div class="mod-hero">
    <div class="mod-hero-content">
        <h1><i class="fas fa-brain"></i> Générateur IA de Contenu</h1>
        <p>Générez du contenu de haute qualité avec Claude pour vos pages du site Eduardo De Sul</p>
    </div>
    <div class="mod-stats">
        <div class="mod-stat"><div class="mod-stat-value"><?= count($pages) ?></div><div class="mod-stat-label">Pages</div></div>
        <div class="mod-stat"><div class="mod-stat-value">6</div><div class="mod-stat-label">Types</div></div>
    </div>
</div>

<?php if ($message):
    $parts = explode('|', $message, 2);
    $type = $parts[0]; $text = $parts[1] ?? '';
    $flashMap = ['success' => 'mod-flash-success', 'error' => 'mod-flash-error', 'info' => 'mod-flash-info'];
    $iconMap = ['success' => 'check-circle', 'error' => 'exclamation-circle', 'info' => 'info-circle'];
?>
<div class="mod-flash <?= $flashMap[$type] ?? 'mod-flash-info' ?>"><i class="fas fa-<?= $iconMap[$type] ?? 'info-circle' ?>"></i> <?= htmlspecialchars($text) ?></div>
<?php endif; ?>

<div class="ia-split">
    <div class="mod-card">
        <div class="mod-card-header"><h3><i class="fas fa-cog" style="color:var(--accent);margin-right:6px"></i> Configuration</h3></div>
        <div class="mod-card-body">
            <form method="POST" id="contentForm">
                <div class="mod-form-grid">
                    <div class="mod-form-group full">
                        <label>Sélectionner une page *</label>
                        <select name="page_name" id="page_name" required>
                            <option value="">-- Choisir une page --</option>
                            <?php foreach ($pages as $k => $t): ?>
                            <option value="<?= htmlspecialchars($k) ?>" <?= (isset($_POST['page_name']) && $_POST['page_name'] === $k) ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mod-form-group">
                        <label>Type de contenu *</label>
                        <select name="content_type" id="content_type">
                            <option value="page_intro">Introduction de page</option>
                            <option value="page_hero">Section Héro</option>
                            <option value="page_features">Fonctionnalités</option>
                            <option value="page_cta">Appels à l'action</option>
                            <option value="faq_content">Questions-Réponses</option>
                            <option value="seo_meta">Metas SEO</option>
                        </select>
                    </div>
                    <div class="mod-form-group">
                        <label>Public cible</label>
                        <select name="target_audience" id="target_audience">
                            <option value="Conseillers immobiliers indépendants">Conseillers immobiliers indépendants</option>
                            <option value="Acheteurs immobiliers">Acheteurs immobiliers</option>
                            <option value="Vendeurs immobiliers">Vendeurs immobiliers</option>
                            <option value="Investisseurs immobiliers">Investisseurs immobiliers</option>
                            <option value="Tous">Tous</option>
                        </select>
                    </div>
                    <div class="mod-form-group">
                        <label>Ton du contenu</label>
                        <select name="tone" id="tone">
                            <option value="professionnel">Professionnel</option>
                            <option value="bienveillant">Bienveillant</option>
                            <option value="solution-oriented">Solution-oriented</option>
                            <option value="ludique">Ludique</option>
                        </select>
                    </div>
                    <div class="mod-form-group full">
                        <label>Description (optionnel)</label>
                        <textarea name="page_description" id="page_description" rows="3" placeholder="Détails spécifiques pour cette page..."><?= htmlspecialchars($_POST['page_description'] ?? '') ?></textarea>
                    </div>
                    <div class="mod-form-group full">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-weight:400">
                            <input type="checkbox" name="save_to_db" value="on" checked style="width:auto;accent-color:var(--accent)">
                            Sauvegarder en base de données
                        </label>
                    </div>
                </div>
                <div class="mod-flex mod-gap" style="margin-top:16px">
                    <button type="submit" name="action" value="generate" class="mod-btn mod-btn-primary" style="flex:1"><i class="fas fa-pen"></i> Générer le contenu</button>
                    <button type="submit" name="action" value="generate_full" class="mod-btn mod-btn-success" style="flex:1"><i class="fas fa-rocket"></i> Page complète</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mod-card">
        <div class="mod-card-header"><h3><i class="fas fa-eye" style="color:var(--accent);margin-right:6px"></i> Prévisualisation</h3></div>
        <div class="mod-card-body">
            <div class="ia-spinner" id="loading"><span class="ia-spinner-ring"></span> Génération en cours...</div>

            <?php if ($generatedContent): ?>
            <div class="ia-preview" id="previewContent">
                <?php
                if (is_array($generatedContent)) {
                    echo '<h2>Contenu généré</h2>';
                    foreach ($generatedContent as $section => $content) {
                        echo '<h3>' . ucfirst(htmlspecialchars($section)) . '</h3>';
                        echo '<pre>' . htmlspecialchars($content) . '</pre>';
                    }
                } else {
                    echo nl2br(htmlspecialchars($generatedContent));
                }
                ?>
            </div>
            <div class="mod-flex mod-gap" style="margin-top:12px">
                <button class="mod-btn mod-btn-secondary mod-btn-sm" onclick="copyIA()" style="flex:1"><i class="fas fa-copy"></i> Copier</button>
            </div>
            <?php else: ?>
            <div class="mod-empty" style="padding:50px 20px">
                <i class="fas fa-file-alt"></i>
                <p>Générez du contenu pour le voir ici</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('contentForm').addEventListener('submit', function() {
    document.getElementById('loading').classList.add('active');
});
function copyIA() {
    const el = document.getElementById('previewContent');
    if (!el) return;
    navigator.clipboard.writeText(el.innerText).then(() => {
        const b = event.target.closest('button');
        if (b) { b.innerHTML = '<i class="fas fa-check"></i> Copié !'; setTimeout(() => b.innerHTML = '<i class="fas fa-copy"></i> Copier', 1500); }
    });
}
</script>