<?php
/**
 * /admin/modules/content/guides/index.php
 * Listing des guides ressources
 */

session_start();
if (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['user_id'])) {
    header('Location: /admin/login.php');
    exit;
}

require_once dirname(__DIR__, 3) . '/includes/init.php';

try {
    $pdo = Database::getInstance()->getConnection();
} catch (Exception $e) {
    echo '<div style="padding:40px;text-align:center;color:#dc2626">Erreur BD: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

// Récupérer tous les guides
$guides = [];
try {
    $stmt = $pdo->query("SELECT * FROM ressources ORDER BY created_at DESC");
    $guides = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    error_log("[Guides Index] " . $e->getMessage());
    $guides = [];
}

// Traiter suppression AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM ressources WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Guide supprimé']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    exit;
}

$personaLabels = [
    'vendeur' => '🏷️ Vendeur',
    'acheteur' => '🛒 Acheteur',
    'proprietaire' => '🏠 Propriétaire',
];

?>
<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:32px;">
    <h1 style="font-size:1.8rem; font-weight:800; color:#1a1a2e; margin:0;">📚 Guides & Ressources</h1>
    <a href="?page=guides&action=create" style="padding:12px 24px; background:#1B3A4B; color:white; text-decoration:none; border-radius:8px; font-weight:600; transition:all .2s; display:inline-flex; align-items:center; gap:8px;">
        ➕ Nouveau guide
    </a>
</div>

<!-- TABLEAU -->
<div style="background:white; border:1px solid #e2d9cc; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.04);">
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#f8f6f3; border-bottom:2px solid #e2d9cc;">
                <th style="padding:16px; text-align:left; font-size:.75rem; font-weight:700; text-transform:uppercase; color:#4a5568; letter-spacing:.05em;">Nom</th>
                <th style="padding:16px; text-align:center; font-size:.75rem; font-weight:700; text-transform:uppercase; color:#4a5568; letter-spacing:.05em;">Persona</th>
                <th style="padding:16px; text-align:center; font-size:.75rem; font-weight:700; text-transform:uppercase; color:#4a5568; letter-spacing:.05em;">Format</th>
                <th style="padding:16px; text-align:center; font-size:.75rem; font-weight:700; text-transform:uppercase; color:#4a5568; letter-spacing:.05em;">Pages</th>
                <th style="padding:16px; text-align:center; font-size:.75rem; font-weight:700; text-transform:uppercase; color:#4a5568; letter-spacing:.05em;">Statut</th>
                <th style="padding:16px; text-align:center; font-size:.75rem; font-weight:700; text-transform:uppercase; color:#4a5568; letter-spacing:.05em;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($guides)): ?>
            <tr>
                <td colspan="6" style="padding:40px; text-align:center; color:#4a5568;">
                    <p style="margin:0; font-size:.95rem;">Aucun guide créé. <a href="?page=guides&action=create" style="color:#C8A96E; font-weight:600;">Créer le premier →</a></p>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($guides as $guide): ?>
                <tr style="border-bottom:1px solid #e2d9cc; transition:background .2s;">
                    <td style="padding:16px; color:#1a1a2e; font-weight:600;">
                        <div style="display:flex; align-items:center; gap:12px;">
                            <span style="font-size:1.2rem;">📖</span>
                            <div>
                                <div><?= htmlspecialchars($guide['name']) ?></div>
                                <div style="font-size:.75rem; color:#718096; margin-top:4px;">/ <?= htmlspecialchars($guide['slug']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="padding:16px; text-align:center; color:#4a5568; font-size:.85rem;">
                        <?= $personaLabels[$guide['persona']] ?? '—' ?>
                    </td>
                    <td style="padding:16px; text-align:center; color:#4a5568; font-size:.85rem;">
                        <?= htmlspecialchars($guide['format'] ?? '—') ?>
                    </td>
                    <td style="padding:16px; text-align:center; color:#4a5568; font-size:.85rem;">
                        <?= htmlspecialchars($guide['pages'] ?? '—') ?>
                    </td>
                    <td style="padding:16px; text-align:center;">
                        <span style="display:inline-block; padding:6px 12px; border-radius:20px; font-size:.75rem; font-weight:700; <?= $guide['status'] === 'active' 
                            ? 'background:#d1fae5; color:#065f46;' 
                            : 'background:#fef3c7; color:#92400e;' ?>">
                            <?= $guide['status'] === 'active' ? '✓ Publié' : '📝 Brouillon' ?>
                        </span>
                    </td>
                    <td style="padding:16px; text-align:center;">
                        <a href="?page=guides&id=<?= $guide['id'] ?>" style="padding:8px 16px; background:#f0ede8; color:#1B3A4B; text-decoration:none; border-radius:6px; font-weight:600; font-size:.8rem; transition:all .2s; display:inline-block; margin-right:8px;">✎ Éditer</a>
                        <button onclick="deleteGuide(<?= $guide['id'] ?>, '<?= addslashes($guide['name']) ?>')" style="padding:8px 16px; background:#fef2f2; color:#dc2626; border:none; border-radius:6px; font-weight:600; font-size:.8rem; cursor:pointer; transition:all .2s;">🗑 Supprimer</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
tr:hover { background:#f8f6f3; }
</style>

<script>
function deleteGuide(id, name) {
    if (!confirm(`Confirmer la suppression de "${name}" ?`)) return;
    
    const data = new FormData();
    data.append('action', 'delete');
    data.append('id', id);

    fetch('', { method: 'POST', body: data })
        .then(r => r.json())
        .then(d => {
            if (d.success) {
                alert('Guide supprimé');
                location.reload();
            } else {
                alert('Erreur: ' + d.message);
            }
        })
        .catch(e => alert('Erreur réseau: ' + e.message));
}
</script>