<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MODULE CAPTURES — Suppression sécurisée  v1.0
 *  /admin/modules/content/captures/delete.php
 *
 *  GET  ?id=X          → page de confirmation visuelle
 *  POST ?id=X confirm=1 → suppression + redirect
 *  POST action=delete (AJAX) → retour JSON (via api.php)
 *
 *  Supprime aussi les captures_stats associées.
 * ══════════════════════════════════════════════════════════════
 */

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_id'])) { header('Location: /admin/login.php'); exit; }

// ─── DB ───
if (!isset($pdo) && !isset($db)) {
    if (!defined('ADMIN_ROUTER')) {
        require_once __DIR__ . '/../../../config/config.php';
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }
}
if (isset($db)  && !isset($pdo)) $pdo = $db;
if (isset($pdo) && !isset($db))  $db  = $pdo;

$pageId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if ($pageId <= 0) { header('Location: ?page=captures&msg=invalid'); exit; }

// ─── Charger la capture ───
$rec = null;
try {
    $stmt = $pdo->prepare("SELECT id, titre, slug, type, status, vues, conversions FROM captures WHERE id = ?");
    $stmt->execute([$pageId]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

if (!$rec) { header('Location: ?page=captures&msg=notfound'); exit; }

// ─── Traitement POST confirmation ───
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === '1') {
    // CSRF check
    if (empty($_POST['_csrf']) || $_POST['_csrf'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = 'Token de sécurité invalide.';
    } else {
        try {
            $pdo->beginTransaction();
            // Supprimer les stats journalières
            try { $pdo->prepare("DELETE FROM captures_stats WHERE capture_id = ?")->execute([$pageId]); }
            catch (PDOException $e) {} // table optionnelle
            // Supprimer la capture
            $pdo->prepare("DELETE FROM captures WHERE id = ?")->execute([$pageId]);
            $pdo->commit();
            header('Location: ?page=captures&msg=deleted');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Erreur SQL : ' . $e->getMessage();
        }
    }
}

// ─── Token CSRF ───
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ─── Stats pour afficher l'impact ───
$statsCount = 0;
try {
    $s = $pdo->prepare("SELECT COUNT(*) FROM captures_stats WHERE capture_id = ?");
    $s->execute([$pageId]);
    $statsCount = (int)$s->fetchColumn();
} catch (PDOException $e) {}

$typeLabels = [
    'estimation' => 'Estimation',
    'contact'    => 'Contact',
    'newsletter' => 'Newsletter',
    'guide'      => 'Guide / Lead Magnet',
];
?>
<style>
/* ══ CAPTURES DELETE — Design unifié ══ */
.cap-del-wrap { max-width: 580px; margin: 0 auto; font-family: var(--font); }

.cap-bc { display:flex; align-items:center; gap:8px; font-size:.78rem; color:var(--text-3); margin-bottom:20px; }
.cap-bc a { color:var(--text-3); text-decoration:none; transition:color .15s; }
.cap-bc a:hover { color:#ef4444; }
.cap-bc i { font-size:.6rem; }

/* Zone de danger */
.cap-del-card {
    background: var(--surface); border-radius: var(--radius-xl);
    border: 2px solid #fecaca; overflow: hidden;
}
.cap-del-card-hd {
    background: linear-gradient(135deg, #fef2f2, #fff1f0);
    padding: 28px 30px; border-bottom: 1px solid #fecaca;
    display: flex; align-items: flex-start; gap: 16px;
}
.cap-del-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: #ef4444; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; box-shadow: 0 4px 12px rgba(239,68,68,.3);
}
.cap-del-icon i { font-size: 22px; color: #fff; }
.cap-del-title {
    font-family: var(--font-display); font-size: 1.25rem;
    font-weight: 800; color: #991b1b; margin: 0 0 4px; letter-spacing: -.02em;
}
.cap-del-sub { font-size: .85rem; color: #b91c1c; margin: 0; }

.cap-del-body { padding: 24px 30px; }

/* Infos de la capture */
.cap-del-recap {
    background: var(--surface-2); border: 1px solid var(--border);
    border-radius: var(--radius-lg); padding: 16px 18px; margin-bottom: 20px;
}
.cap-del-recap-row {
    display: flex; align-items: center; gap: 10px;
    padding: 7px 0; border-bottom: 1px solid var(--border);
    font-size: .83rem;
}
.cap-del-recap-row:last-child { border-bottom: none; padding-bottom: 0; }
.cap-del-recap-row:first-child { padding-top: 0; }
.cap-del-lbl { font-weight: 700; color: var(--text-2); width: 110px; flex-shrink: 0; font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; }
.cap-del-val { color: var(--text); font-size: .83rem; }
.cap-del-val.slug { font-family: var(--mono); font-size: .78rem; color: var(--text-3); }
.cap-del-val .cap-status { padding: 2px 8px; border-radius: 10px; font-size: .62rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
.cap-del-val .cap-status.active   { background: #dcfce7; color: #166534; }
.cap-del-val .cap-status.inactive { background: var(--surface-2); color: var(--text-3); }
.cap-del-val .cap-status.archived { background: #fef9c3; color: #a16207; }

/* Avertissements */
.cap-del-warnings { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
.cap-del-warn {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 11px 14px; border-radius: var(--radius);
    font-size: .82rem; font-weight: 500;
}
.cap-del-warn.red  { background: rgba(239,68,68,.06); border: 1px solid rgba(239,68,68,.2); color: #b91c1c; }
.cap-del-warn.amber{ background: rgba(245,158,11,.06); border: 1px solid rgba(245,158,11,.2); color: #92400e; }
.cap-del-warn i { margin-top: 1px; flex-shrink: 0; }

/* Confirmation input */
.cap-del-confirm-box {
    background: #fff7ed; border: 1px solid #fed7aa;
    border-radius: var(--radius); padding: 14px 18px; margin-bottom: 20px;
}
.cap-del-confirm-box label { font-size: .8rem; font-weight: 700; color: #92400e; display: block; margin-bottom: 8px; }
.cap-del-confirm-box input {
    width: 100%; padding: 9px 12px; border: 1px solid #fed7aa;
    border-radius: var(--radius); background: #fff; color: var(--text);
    font-size: .85rem; font-family: var(--font); box-sizing: border-box;
    transition: border-color .15s;
}
.cap-del-confirm-box input:focus { outline: none; border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.1); }
.cap-del-confirm-hint { font-size: .7rem; color: #b45309; margin-top: 5px; }

/* Boutons */
.cap-del-actions { display: flex; gap: 10px; }
.cap-btn { display:inline-flex; align-items:center; gap:6px; padding:10px 22px; border-radius:var(--radius); font-size:.85rem; font-weight:700; cursor:pointer; border:none; font-family:var(--font); text-decoration:none; line-height:1.3; transition:all .15s var(--ease); }
.cap-btn-danger { background: #ef4444; color: #fff; box-shadow: 0 2px 8px rgba(239,68,68,.3); flex: 1; justify-content: center; }
.cap-btn-danger:hover:not(:disabled) { background: #dc2626; transform: translateY(-1px); }
.cap-btn-danger:disabled { opacity: .45; cursor: not-allowed; transform: none; }
.cap-btn-outline { background: var(--surface); color: var(--text-2); border: 1px solid var(--border); flex: 1; justify-content: center; }
.cap-btn-outline:hover { border-color: var(--border-h); background: var(--surface-2); color: var(--text); }

.cap-error { padding: 12px 16px; background: rgba(220,38,38,.06); border: 1px solid rgba(220,38,38,.2); border-radius: var(--radius); font-size: .83rem; color: #dc2626; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
</style>

<div class="cap-del-wrap">

    <!-- Breadcrumb -->
    <div class="cap-bc">
        <a href="?page=captures"><i class="fas fa-magnet"></i> Pages de capture</a>
        <i class="fas fa-chevron-right"></i>
        <a href="?page=captures&action=edit&id=<?= $pageId ?>"><?= htmlspecialchars($rec['titre'] ?? '') ?></a>
        <i class="fas fa-chevron-right"></i>
        <span style="color:#dc2626">Supprimer</span>
    </div>

    <?php if ($error): ?>
    <div class="cap-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="cap-del-card">

        <!-- Header -->
        <div class="cap-del-card-hd">
            <div class="cap-del-icon"><i class="fas fa-trash-alt"></i></div>
            <div>
                <div class="cap-del-title">Supprimer cette page de capture</div>
                <div class="cap-del-sub">Cette action est irréversible. Toutes les données seront perdues définitivement.</div>
            </div>
        </div>

        <div class="cap-del-body">

            <!-- Récapitulatif -->
            <div class="cap-del-recap">
                <div class="cap-del-recap-row">
                    <span class="cap-del-lbl">Titre</span>
                    <span class="cap-del-val"><strong><?= htmlspecialchars($rec['titre'] ?? '—') ?></strong></span>
                </div>
                <div class="cap-del-recap-row">
                    <span class="cap-del-lbl">URL</span>
                    <span class="cap-del-val slug">/capture/<?= htmlspecialchars($rec['slug'] ?? '') ?></span>
                </div>
                <div class="cap-del-recap-row">
                    <span class="cap-del-lbl">Type</span>
                    <span class="cap-del-val"><?= htmlspecialchars($typeLabels[$rec['type'] ?? ''] ?? ucfirst($rec['type'] ?? '—')) ?></span>
                </div>
                <div class="cap-del-recap-row">
                    <span class="cap-del-lbl">Statut</span>
                    <span class="cap-del-val">
                        <span class="cap-status <?= $rec['status'] ?>"><?php
                            echo match($rec['status']) {
                                'active'   => 'Active',
                                'inactive' => 'Inactive',
                                'archived' => 'Archivée',
                                default    => ucfirst($rec['status']),
                            };
                        ?></span>
                    </span>
                </div>
                <div class="cap-del-recap-row">
                    <span class="cap-del-lbl">Vues</span>
                    <span class="cap-del-val"><?= number_format((int)$rec['vues']) ?></span>
                </div>
                <div class="cap-del-recap-row">
                    <span class="cap-del-lbl">Conversions</span>
                    <span class="cap-del-val"><?= number_format((int)$rec['conversions']) ?></span>
                </div>
            </div>

            <!-- Avertissements -->
            <div class="cap-del-warnings">
                <div class="cap-del-warn red">
                    <i class="fas fa-times-circle"></i>
                    <span>La page de capture et toutes ses configurations seront <strong>définitivement supprimées</strong>.</span>
                </div>
                <?php if ($statsCount > 0): ?>
                <div class="cap-del-warn amber">
                    <i class="fas fa-chart-bar"></i>
                    <span><strong><?= $statsCount ?> entrée(s) de statistiques</strong> seront également supprimées (historique vues/conversions).</span>
                </div>
                <?php endif; ?>
                <?php if ((int)$rec['conversions'] > 0): ?>
                <div class="cap-del-warn amber">
                    <i class="fas fa-users"></i>
                    <span>Cette page a généré <strong><?= number_format((int)$rec['conversions']) ?> lead(s)</strong>. Les leads dans votre CRM ne seront <strong>pas</strong> supprimés.</span>
                </div>
                <?php endif; ?>
                <?php if ($rec['status'] === 'active'): ?>
                <div class="cap-del-warn red">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Cette page est <strong>actuellement active</strong> et accessible au public. Elle sera immédiatement inaccessible après suppression.</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Confirmation manuelle -->
            <div class="cap-del-confirm-box">
                <label for="confirmInput">
                    <i class="fas fa-keyboard"></i>
                    Pour confirmer, tapez exactement : <strong><?= htmlspecialchars($rec['titre'] ?? '') ?></strong>
                </label>
                <input type="text" id="confirmInput"
                       placeholder="Tapez le titre exact pour confirmer…"
                       autocomplete="off">
                <div class="cap-del-confirm-hint">
                    <i class="fas fa-info-circle"></i>
                    La saisie doit correspondre exactement au titre (majuscules et espaces inclus).
                </div>
            </div>

            <!-- Formulaire de suppression -->
            <form method="POST" id="deleteForm">
                <input type="hidden" name="id"      value="<?= $pageId ?>">
                <input type="hidden" name="confirm" value="1">
                <input type="hidden" name="_csrf"   value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

                <div class="cap-del-actions">
                    <a href="?page=captures&action=edit&id=<?= $pageId ?>" class="cap-btn cap-btn-outline">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                    <button type="submit" class="cap-btn cap-btn-danger" id="deleteBtn" disabled>
                        <i class="fas fa-trash-alt"></i> Supprimer définitivement
                    </button>
                </div>
            </form>

        </div><!-- /.cap-del-body -->
    </div><!-- /.cap-del-card -->

</div>

<script>
// ─── Débloquer le bouton quand le titre est tapé exactement ───
const expected = <?= json_encode($rec['titre'] ?? '') ?>;
const input    = document.getElementById('confirmInput');
const btn      = document.getElementById('deleteBtn');

input.addEventListener('input', () => {
    const match = input.value === expected;
    btn.disabled = !match;
    input.style.borderColor = input.value.length === 0
        ? '' : (match ? '#059669' : '#ef4444');
});

// ─── Prévenir double-clic ───
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression…';
});
</script>