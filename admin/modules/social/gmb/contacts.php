<?php
/**
 * Contacts GMB - Vue Admin
 * Module : admin/modules/gmb/contacts.php
 * 
 * Fonctionnalités :
 * - Liste paginée avec filtres (type, statut, ville, email)
 * - Recherche fulltext
 * - Sélection multiple + actions en masse
 * - Modale détail/édition contact
 * - Gestion des listes
 * - Export CSV
 */

// ── Guard : compatible dashboard.php ET accès direct ──
$isEmbedded = isset($pdo);

if (!$isEmbedded) {
    require_once __DIR__ . '/../../includes/init.php';
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: /admin/login.php');
        exit;
    }
    try {
        require_once __DIR__ . '/../../../core/db.php';
        $pdo = DB::get();
    } catch (Exception $e) {
        die('Erreur de connexion à la base de données');
    }
}
$db = $pdo;

require_once __DIR__ . '/ContactController.php';
$controller = new ContactController($db);

// Paramètres de filtre
// IMPORTANT : $_GET['page'] est utilisé par le router dashboard (= 'gmb-contacts')
// On utilise $_GET['pg'] pour la pagination contacts
$filters = [
    'page'            => (int)($_GET['pg'] ?? 1),
    'per_page'        => (int)($_GET['per_page'] ?? 25),
    'search'          => $_GET['search'] ?? '',
    'contact_type'    => $_GET['contact_type'] ?? '',
    'prospect_status' => $_GET['prospect_status'] ?? '',
    'email_status'    => $_GET['email_status'] ?? '',
    'city'            => $_GET['city'] ?? '',
    'list_id'         => $_GET['list'] ?? '',
    'sort'            => $_GET['sort'] ?? 'recent',
];
$result = $controller->getContacts($filters);
$contacts = $result['contacts'];
$total = $result['total'];
$totalPages = $result['total_pages'];
$currentPage = $result['page'];

$lists = $controller->getLists();
$stats = $controller->getStats();

// Labels types
$typeLabels = [
    'agent_immobilier' => ['label' => 'Agent Immo', 'color' => '#EF4444', 'icon' => '🏠'],
    'courtier'         => ['label' => 'Courtier', 'color' => '#F59E0B', 'icon' => '💰'],
    'diagnostiqueur'   => ['label' => 'Diagnostiqueur', 'color' => '#8B5CF6', 'icon' => '📋'],
    'notaire'          => ['label' => 'Notaire', 'color' => '#6366F1', 'icon' => '⚖️'],
    'architecte'       => ['label' => 'Architecte', 'color' => '#EC4899', 'icon' => '📐'],
    'decorateur'       => ['label' => 'Décorateur', 'color' => '#F472B6', 'icon' => '🎨'],
    'demenageur'       => ['label' => 'Déménageur', 'color' => '#78716C', 'icon' => '📦'],
    'artisan_renovation' => ['label' => 'Artisan/Réno', 'color' => '#10B981', 'icon' => '🔧'],
    'photographe'      => ['label' => 'Photographe', 'color' => '#0EA5E9', 'icon' => '📸'],
    'home_stager'      => ['label' => 'Home Stager', 'color' => '#D946EF', 'icon' => '✨'],
    'promoteur'        => ['label' => 'Promoteur', 'color' => '#DC2626', 'icon' => '🏗️'],
    'syndic'           => ['label' => 'Syndic', 'color' => '#64748B', 'icon' => '🏢'],
    'assureur'         => ['label' => 'Assureur', 'color' => '#0D9488', 'icon' => '🛡️'],
    'banque'           => ['label' => 'Banque', 'color' => '#1D4ED8', 'icon' => '🏦'],
    'autre'            => ['label' => 'Autre', 'color' => '#9CA3AF', 'icon' => '📌'],
];

$statusLabels = [
    'nouveau'       => ['label' => 'Nouveau', 'color' => '#3B82F6'],
    'a_contacter'   => ['label' => 'À contacter', 'color' => '#F59E0B'],
    'contacte'      => ['label' => 'Contacté', 'color' => '#8B5CF6'],
    'interesse'     => ['label' => 'Intéressé', 'color' => '#10B981'],
    'partenaire'    => ['label' => 'Partenaire', 'color' => '#059669'],
    'pas_interesse' => ['label' => 'Pas intéressé', 'color' => '#6B7280'],
    'blackliste'    => ['label' => 'Blacklisté', 'color' => '#EF4444'],
];

$emailStatusIcons = [
    'unknown'    => ['icon' => '❓', 'label' => 'Non vérifié', 'color' => '#6B7280'],
    'valid'      => ['icon' => '✅', 'label' => 'Valide', 'color' => '#10B981'],
    'invalid'    => ['icon' => '❌', 'label' => 'Invalide', 'color' => '#EF4444'],
    'catch_all'  => ['icon' => '⚠️', 'label' => 'Catch-all', 'color' => '#F59E0B'],
    'disposable' => ['icon' => '🗑️', 'label' => 'Jetable', 'color' => '#EF4444'],
];

// URLs dynamiques
$selfUrl      = $isEmbedded ? 'dashboard.php?page=gmb-contacts' : 'contacts.php';
$apiBase      = $isEmbedded ? 'modules/gmb/api/' : 'api/';

// Helper : construire URL de pagination (utilise 'pg' au lieu de 'page')
function buildContactsUrl($baseUrl, $filters, $overrides = []) {
    $params = array_merge($filters, $overrides);
    // Renommer 'page' en 'pg' pour éviter collision router
    if (isset($params['page'])) {
        $params['pg'] = $params['page'];
        unset($params['page']);
    }
    // Supprimer les valeurs vides
    $params = array_filter($params, fn($v) => $v !== '' && $v !== 0 && $v !== null);
    $qs = http_build_query($params);
    // Si embedded, ajouter après le ?page=gmb-contacts
    if (strpos($baseUrl, '?') !== false) {
        return $baseUrl . ($qs ? '&' . $qs : '');
    }
    return $baseUrl . ($qs ? '?' . $qs : '');
}

$pageTitle = 'Contacts GMB';
?>

<?php if (!$isEmbedded): ?>
<?php require_once __DIR__ . '/../../includes/header.php'; ?>
<?php endif; ?>

<style>
/* ===== GMB CONTACTS — Scoped styles ===== */
.gmb-contacts-page { padding: 24px; font-family: 'DM Sans', sans-serif; color: #f1f5f9; }
.gmb-contacts-page .gc-toolbar { display: flex; flex-wrap: wrap; gap: 12px; align-items: center; margin-bottom: 20px; }
.gmb-contacts-page .gc-search-box { flex: 1; min-width: 250px; position: relative; }
.gmb-contacts-page .gc-search-box input { width: 100%; padding: 10px 14px 10px 40px; border-radius: 8px; border: 1px solid #334155; background: #334155; color: #f1f5f9; font-size: 14px; }
.gmb-contacts-page .gc-search-box::before { content: '🔍'; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 16px; }
.gmb-contacts-page .gc-filter { padding: 10px 14px; border-radius: 8px; border: 1px solid #334155; background: #334155; color: #f1f5f9; font-size: 13px; cursor: pointer; }
.gmb-contacts-page .gc-btn { padding: 10px 16px; border-radius: 8px; border: none; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
.gmb-contacts-page .gc-btn-primary { background: #3b82f6; color: white; }
.gmb-contacts-page .gc-btn-primary:hover { background: #2563eb; }
.gmb-contacts-page .gc-btn-outline { background: transparent; color: #94a3b8; border: 1px solid #334155; }
.gmb-contacts-page .gc-btn-outline:hover { border-color: #3b82f6; color: #3b82f6; }
.gmb-contacts-page .gc-btn-danger { background: #dc2626; color: white; }
.gmb-contacts-page .gc-btn-sm { padding: 6px 12px; font-size: 12px; }

/* Stats bar */
.gmb-contacts-page .gc-stats-bar { display: flex; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.gmb-contacts-page .gc-stat-chip { background: #1e293b; border: 1px solid #334155; border-radius: 8px; padding: 8px 16px; font-size: 13px; display: flex; align-items: center; gap: 8px; }
.gmb-contacts-page .gc-stat-chip strong { color: #f1f5f9; font-size: 16px; }

/* Bulk actions */
.gmb-contacts-page .gc-bulk-bar { display: none; background: #3b82f6; padding: 10px 16px; border-radius: 8px; margin-bottom: 12px; align-items: center; gap: 12px; }
.gmb-contacts-page .gc-bulk-bar.active { display: flex; }
.gmb-contacts-page .gc-bulk-bar span { color: white; font-weight: 600; font-size: 14px; }
.gmb-contacts-page .gc-bulk-bar .gc-btn { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); }
.gmb-contacts-page .gc-bulk-bar .gc-btn:hover { background: rgba(255,255,255,0.3); }

/* Table */
.gmb-contacts-page .gc-table { width: 100%; border-collapse: collapse; }
.gmb-contacts-page .gc-table th { text-align: left; padding: 12px 14px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; border-bottom: 1px solid #334155; white-space: nowrap; }
.gmb-contacts-page .gc-table td { padding: 10px 14px; font-size: 13px; border-bottom: 1px solid rgba(51,65,85,0.5); vertical-align: middle; }
.gmb-contacts-page .gc-table tr:hover { background: rgba(59,130,246,0.05); }
.gmb-contacts-page .gc-table input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; accent-color: #3b82f6; }
.gmb-contacts-page .gc-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.gmb-contacts-page .gc-rating { color: #FBBF24; font-weight: 600; }
.gmb-contacts-page .gc-email-cell { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

/* Pagination */
.gmb-contacts-page .gc-pagination { display: flex; justify-content: center; gap: 4px; margin-top: 20px; }
.gmb-contacts-page .gc-pagination a, .gmb-contacts-page .gc-pagination span { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 6px; font-size: 13px; font-weight: 500; text-decoration: none; }
.gmb-contacts-page .gc-pagination a { background: #1e293b; color: #94a3b8; border: 1px solid #334155; }
.gmb-contacts-page .gc-pagination a:hover { border-color: #3b82f6; color: #3b82f6; }
.gmb-contacts-page .gc-pagination .current { background: #3b82f6; color: white; }
.gmb-contacts-page .gc-pagination .dots { color: #94a3b8; }

/* Modal */
.gmb-contacts-page .gc-modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; }
.gmb-contacts-page .gc-modal-overlay.active { display: flex; }
.gmb-contacts-page .gc-modal { background: #1e293b; border-radius: 16px; width: 90%; max-width: 700px; max-height: 85vh; overflow-y: auto; border: 1px solid #334155; }
.gmb-contacts-page .gc-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #334155; }
.gmb-contacts-page .gc-modal-header h3 { font-size: 18px; margin: 0; }
.gmb-contacts-page .gc-modal-close { background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer; }
.gmb-contacts-page .gc-modal-body { padding: 24px; }
.gmb-contacts-page .gc-modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 16px 24px; border-top: 1px solid #334155; }

/* Form */
.gmb-contacts-page .gc-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.gmb-contacts-page .gc-form-group { display: flex; flex-direction: column; gap: 6px; }
.gmb-contacts-page .gc-form-group.full { grid-column: 1 / -1; }
.gmb-contacts-page .gc-form-group label { font-size: 12px; color: #94a3b8; font-weight: 500; }
.gmb-contacts-page .gc-form-group input,
.gmb-contacts-page .gc-form-group select,
.gmb-contacts-page .gc-form-group textarea {
    padding: 10px 12px; border-radius: 8px; border: 1px solid #334155;
    background: #334155; color: #f1f5f9; font-size: 14px;
}
.gmb-contacts-page .gc-form-group textarea { resize: vertical; min-height: 80px; }

@media (max-width: 768px) {
    .gmb-contacts-page .gc-toolbar { flex-direction: column; }
    .gmb-contacts-page .gc-form-grid { grid-template-columns: 1fr; }
    .gmb-contacts-page .gc-table { font-size: 12px; }
    .gmb-contacts-page .gc-table th:nth-child(n+5), .gmb-contacts-page .gc-table td:nth-child(n+5) { display: none; }
}
</style>

<div class="gmb-contacts-page">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
        <div>
            <h2 style="margin:0">📇 Contacts GMB</h2>
            <p style="margin:4px 0 0;color:#94a3b8;font-size:14px"><?= number_format($total) ?> contact(s) au total</p>
        </div>
        <div style="display:flex;gap:8px">
            <?php
            $exportFilters = array_filter($filters, fn($v) => $v !== '' && $v !== 1, ARRAY_FILTER_USE_BOTH);
            unset($exportFilters['page']);
            ?>
            <a href="<?= $selfUrl ?>&action=export&<?= http_build_query($exportFilters) ?>" class="gc-btn gc-btn-outline">📥 Export CSV</a>
            <button class="gc-btn gc-btn-primary" onclick="gcOpenValidateModal()">✉️ Valider Emails</button>
        </div>
    </div>

    <!-- Stats -->
    <div class="gc-stats-bar">
        <div class="gc-stat-chip"><strong><?= $stats['total'] ?></strong> Total</div>
        <div class="gc-stat-chip"><strong><?= $stats['with_email'] ?></strong> Avec email</div>
        <div class="gc-stat-chip" style="border-color:#10B981"><strong style="color:#10B981"><?= $stats['valid_email'] ?></strong> Emails valides</div>
        <div class="gc-stat-chip"><strong><?= $stats['pending_validation'] ?></strong> À valider</div>
        <div class="gc-stat-chip" style="border-color:#059669"><strong style="color:#059669"><?= $stats['by_status']['partenaire'] ?? 0 ?></strong> Partenaires</div>
    </div>

    <!-- Filtres -->
    <form class="gc-toolbar" method="GET" action="<?= $isEmbedded ? 'dashboard.php' : 'contacts.php' ?>">
        <?php if ($isEmbedded): ?>
            <input type="hidden" name="page" value="gmb-contacts">
        <?php endif; ?>
        <div class="gc-search-box">
            <input type="text" name="search" placeholder="Rechercher entreprise, email, ville..." value="<?= htmlspecialchars($filters['search']) ?>">
        </div>
        <select name="contact_type" class="gc-filter" onchange="this.form.submit()">
            <option value="">Tous les types</option>
            <?php foreach ($typeLabels as $key => $t): ?>
                <option value="<?= $key ?>" <?= $filters['contact_type'] === $key ? 'selected' : '' ?>><?= $t['icon'] ?> <?= $t['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <select name="prospect_status" class="gc-filter" onchange="this.form.submit()">
            <option value="">Tous les statuts</option>
            <?php foreach ($statusLabels as $key => $s): ?>
                <option value="<?= $key ?>" <?= $filters['prospect_status'] === $key ? 'selected' : '' ?>><?= $s['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <select name="email_status" class="gc-filter" onchange="this.form.submit()">
            <option value="">Statut email</option>
            <option value="valid" <?= $filters['email_status'] === 'valid' ? 'selected' : '' ?>>✅ Valide</option>
            <option value="invalid" <?= $filters['email_status'] === 'invalid' ? 'selected' : '' ?>>❌ Invalide</option>
            <option value="unknown" <?= $filters['email_status'] === 'unknown' ? 'selected' : '' ?>>❓ Non vérifié</option>
            <option value="catch_all" <?= $filters['email_status'] === 'catch_all' ? 'selected' : '' ?>>⚠️ Catch-all</option>
        </select>
        <select name="list" class="gc-filter" onchange="this.form.submit()">
            <option value="">Toutes les listes</option>
            <?php foreach ($lists as $list): ?>
                <option value="<?= $list['id'] ?>" <?= $filters['list_id'] == $list['id'] ? 'selected' : '' ?>><?= htmlspecialchars($list['name']) ?> (<?= $list['real_count'] ?>)</option>
            <?php endforeach; ?>
        </select>
        <select name="sort" class="gc-filter" onchange="this.form.submit()">
            <option value="recent" <?= $filters['sort'] === 'recent' ? 'selected' : '' ?>>Plus récents</option>
            <option value="name" <?= $filters['sort'] === 'name' ? 'selected' : '' ?>>Nom A-Z</option>
            <option value="rating" <?= $filters['sort'] === 'rating' ? 'selected' : '' ?>>Meilleure note</option>
            <option value="city" <?= $filters['sort'] === 'city' ? 'selected' : '' ?>>Ville</option>
        </select>
        <button type="submit" class="gc-btn gc-btn-primary gc-btn-sm">Filtrer</button>
        <?php if (!empty($filters['search']) || !empty($filters['contact_type']) || !empty($filters['prospect_status']) || !empty($filters['email_status']) || !empty($filters['list_id'])): ?>
            <a href="<?= $selfUrl ?>" class="gc-btn gc-btn-outline gc-btn-sm">✕ Reset</a>
        <?php endif; ?>
    </form>

    <!-- Actions en masse -->
    <div class="gc-bulk-bar" id="gcBulkBar">
        <span id="gcSelectedCount">0 sélectionné(s)</span>
        <select id="gcBulkStatusSelect" class="gc-filter" style="padding:6px 10px">
            <option value="">→ Changer statut</option>
            <?php foreach ($statusLabels as $key => $s): ?>
                <option value="<?= $key ?>"><?= $s['label'] ?></option>
            <?php endforeach; ?>
        </select>
        <button class="gc-btn gc-btn-sm" onclick="gcBulkChangeStatus()">Appliquer</button>
        <select id="gcBulkListSelect" class="gc-filter" style="padding:6px 10px">
            <option value="">→ Ajouter à liste</option>
            <?php foreach ($lists as $list): ?>
                <option value="<?= $list['id'] ?>"><?= htmlspecialchars($list['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="gc-btn gc-btn-sm" onclick="gcBulkAddToList()">Ajouter</button>
        <button class="gc-btn gc-btn-sm" onclick="gcBulkDelete()" style="margin-left:auto;background:rgba(220,38,38,0.3);border-color:rgba(220,38,38,0.5)">🗑️ Supprimer</button>
    </div>

    <!-- Tableau -->
    <div style="overflow-x:auto;background:#1e293b;border-radius:12px;border:1px solid #334155">
        <table class="gc-table">
            <thead>
                <tr>
                    <th><input type="checkbox" id="gcSelectAll" onchange="gcToggleSelectAll()"></th>
                    <th>Entreprise</th>
                    <th>Type</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Ville</th>
                    <th>Note</th>
                    <th>Statut</th>
                    <th>Partenariat</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contacts)): ?>
                    <tr><td colspan="10" style="text-align:center;padding:40px;color:#94a3b8">Aucun contact trouvé</td></tr>
                <?php endif; ?>
                <?php foreach ($contacts as $c):
                    $type = $typeLabels[$c['contact_type'] ?? 'autre'] ?? $typeLabels['autre'];
                    $status = $statusLabels[$c['prospect_status'] ?? 'nouveau'] ?? $statusLabels['nouveau'];
                    $emailSt = $emailStatusIcons[$c['email_status'] ?? 'unknown'] ?? $emailStatusIcons['unknown'];
                ?>
                <tr data-id="<?= $c['id'] ?>">
                    <td><input type="checkbox" class="gc-contact-check" value="<?= $c['id'] ?>" onchange="gcUpdateBulkBar()"></td>
                    <td>
                        <div style="font-weight:600"><?= htmlspecialchars($c['business_name'] ?? '—') ?></div>
                        <div style="font-size:11px;color:#94a3b8"><?= htmlspecialchars($c['business_category'] ?? '') ?></div>
                    </td>
                    <td>
                        <span class="gc-badge" style="background:<?= $type['color'] ?>20;color:<?= $type['color'] ?>"><?= $type['icon'] ?> <?= $type['label'] ?></span>
                    </td>
                    <td><?= htmlspecialchars($c['contact_name'] ?? '—') ?></td>
                    <td class="gc-email-cell" title="<?= htmlspecialchars($c['email'] ?? '') ?>">
                        <?php if ($c['email']): ?>
                            <span title="<?= $emailSt['label'] ?>"><?= $emailSt['icon'] ?></span>
                            <?= htmlspecialchars($c['email']) ?>
                        <?php else: ?>
                            <span style="color:#94a3b8">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($c['city'] ?? '—') ?></td>
                    <td>
                        <?php if ($c['rating']): ?>
                            <span class="gc-rating">⭐ <?= number_format($c['rating'], 1) ?></span>
                            <span style="font-size:11px;color:#94a3b8">(<?= $c['reviews_count'] ?? 0 ?>)</span>
                        <?php else: ?>
                            <span style="color:#94a3b8">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="gc-badge" style="background:<?= $status['color'] ?>20;color:<?= $status['color'] ?>"><?= $status['label'] ?></span>
                    </td>
                    <td>
                        <?php 
                        $pLabels = [
                            'aucun' => '—',
                            'echange_liens' => '🔗 Liens',
                            'guide_local' => '📖 Guide',
                            'courtier_partenaire' => '💰 Courtier',
                            'partenariat_local' => '🤝 Local',
                        ];
                        echo $pLabels[$c['partnership_type'] ?? 'aucun'] ?? '—';
                        ?>
                    </td>
                    <td>
                        <button class="gc-btn gc-btn-outline gc-btn-sm" onclick="gcViewContact(<?= $c['id'] ?>)" title="Voir/Modifier">✏️</button>
                        <?php if ($c['email'] && $c['email_status'] === 'unknown'): ?>
                            <button class="gc-btn gc-btn-outline gc-btn-sm" onclick="gcValidateEmail(<?= $c['id'] ?>)" title="Valider email">✉️</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="gc-pagination">
        <?php if ($currentPage > 1): ?>
            <a href="<?= buildContactsUrl($selfUrl, $filters, ['page' => $currentPage - 1]) ?>">←</a>
        <?php endif; ?>
        <?php
        $start = max(1, $currentPage - 3);
        $end = min($totalPages, $currentPage + 3);
        if ($start > 1) {
            echo '<a href="'. buildContactsUrl($selfUrl, $filters, ['page' => 1]) .'">1</a>';
            if ($start > 2) echo '<span class="dots">…</span>';
        }
        for ($i = $start; $i <= $end; $i++):
        ?>
            <?php if ($i == $currentPage): ?>
                <span class="current"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= buildContactsUrl($selfUrl, $filters, ['page' => $i]) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor;
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) echo '<span class="dots">…</span>';
            echo '<a href="'. buildContactsUrl($selfUrl, $filters, ['page' => $totalPages]) .'">' . $totalPages . '</a>';
        }
        ?>
        <?php if ($currentPage < $totalPages): ?>
            <a href="<?= buildContactsUrl($selfUrl, $filters, ['page' => $currentPage + 1]) ?>">→</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Détail Contact -->
<div class="gmb-contacts-page">
<div class="gc-modal-overlay" id="gcContactModal">
    <div class="gc-modal">
        <div class="gc-modal-header">
            <h3 id="gcModalTitle">Détail du contact</h3>
            <button class="gc-modal-close" onclick="gcCloseModal('gcContactModal')">×</button>
        </div>
        <div class="gc-modal-body" id="gcModalBody">
            <div style="text-align:center;padding:40px;color:#94a3b8">Chargement...</div>
        </div>
        <div class="gc-modal-footer">
            <button class="gc-btn gc-btn-outline" onclick="gcCloseModal('gcContactModal')">Fermer</button>
            <button class="gc-btn gc-btn-primary" onclick="gcSaveContact()">💾 Enregistrer</button>
        </div>
    </div>
</div>

<!-- Modal Validation Emails -->
<div class="gc-modal-overlay" id="gcValidateModal">
    <div class="gc-modal">
        <div class="gc-modal-header">
            <h3>✉️ Validation des Emails</h3>
            <button class="gc-modal-close" onclick="gcCloseModal('gcValidateModal')">×</button>
        </div>
        <div class="gc-modal-body">
            <p>Valider les emails non vérifiés par vérification MX et SMTP.</p>
            <div class="gc-form-group">
                <label>Nombre de contacts à valider</label>
                <input type="number" id="gcValidateLimit" value="50" min="1" max="200">
            </div>
            <div id="gcValidateProgress" style="display:none;margin-top:16px">
                <div style="background:#334155;border-radius:8px;overflow:hidden;height:8px">
                    <div id="gcValidateBar" style="background:#3b82f6;height:100%;width:0%;transition:width 0.3s"></div>
                </div>
                <p id="gcValidateStatus" style="margin-top:8px;font-size:13px;color:#94a3b8"></p>
            </div>
            <div id="gcValidateResults" style="display:none;margin-top:16px"></div>
        </div>
        <div class="gc-modal-footer">
            <button class="gc-btn gc-btn-outline" onclick="gcCloseModal('gcValidateModal')">Fermer</button>
            <button class="gc-btn gc-btn-primary" id="gcValidateBtn" onclick="gcRunValidation()">🚀 Lancer la validation</button>
        </div>
    </div>
</div>
</div>

<script>
const GC_API_CONTACTS = '<?= $apiBase ?>contacts.php';
const GC_API_VALIDATOR = '<?= $apiBase ?>email-validator.php';
let gcCurrentContactId = null;

// ===== Sélection en masse =====
function gcToggleSelectAll() {
    const checked = document.getElementById('gcSelectAll').checked;
    document.querySelectorAll('.gc-contact-check').forEach(cb => cb.checked = checked);
    gcUpdateBulkBar();
}

function gcUpdateBulkBar() {
    const checked = document.querySelectorAll('.gc-contact-check:checked');
    const bar = document.getElementById('gcBulkBar');
    const count = document.getElementById('gcSelectedCount');
    if (checked.length > 0) {
        bar.classList.add('active');
        count.textContent = checked.length + ' sélectionné(s)';
    } else {
        bar.classList.remove('active');
    }
}

function gcGetSelectedIds() {
    return Array.from(document.querySelectorAll('.gc-contact-check:checked')).map(cb => parseInt(cb.value));
}

// ===== Actions en masse =====
async function gcBulkChangeStatus() {
    const ids = gcGetSelectedIds();
    const status = document.getElementById('gcBulkStatusSelect').value;
    if (!ids.length || !status) return;
    const res = await fetch(GC_API_CONTACTS, {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'bulk_status', ids, status })
    });
    const data = await res.json();
    if (data.success) { gcShowToast(data.updated + ' contact(s) mis à jour', 'success'); setTimeout(() => location.reload(), 800); }
}

async function gcBulkAddToList() {
    const ids = gcGetSelectedIds();
    const listId = parseInt(document.getElementById('gcBulkListSelect').value);
    if (!ids.length || !listId) return;
    const res = await fetch(GC_API_CONTACTS, {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'add_to_list', list_id: listId, contact_ids: ids })
    });
    const data = await res.json();
    if (data.success) { gcShowToast(data.added + ' contact(s) ajouté(s) à la liste', 'success'); }
}

async function gcBulkDelete() {
    const ids = gcGetSelectedIds();
    if (!ids.length) return;
    if (!confirm('Supprimer ' + ids.length + ' contact(s) ? Cette action est irréversible.')) return;
    const res = await fetch(GC_API_CONTACTS, {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'delete', ids })
    });
    const data = await res.json();
    if (data.success) { gcShowToast(data.deleted + ' contact(s) supprimé(s)', 'success'); setTimeout(() => location.reload(), 800); }
}

// ===== Contact Modal =====
async function gcViewContact(id) {
    gcCurrentContactId = id;
    document.getElementById('gcContactModal').classList.add('active');
    document.getElementById('gcModalBody').innerHTML = '<div style="text-align:center;padding:40px;color:#94a3b8">Chargement...</div>';
    
    const res = await fetch(GC_API_CONTACTS + '?action=get&id=' + id);
    const data = await res.json();
    
    if (!data.success) {
        document.getElementById('gcModalBody').innerHTML = '<p style="color:#EF4444">Erreur: ' + data.message + '</p>';
        return;
    }
    
    const c = data.contact;
    document.getElementById('gcModalTitle').textContent = c.business_name || 'Contact #' + c.id;
    
    const typeOptions = <?= json_encode(array_map(fn($t) => $t['label'], $typeLabels)) ?>;
    const statusOptions = <?= json_encode(array_map(fn($s) => $s['label'], $statusLabels)) ?>;
    const partnerOptions = {
        'aucun': 'Aucun', 'echange_liens': 'Échange de liens', 'guide_local': 'Guide local',
        'courtier_partenaire': 'Courtier partenaire', 'partenariat_local': 'Partenariat local'
    };
    
    let html = '<div class="gc-form-grid">';
    html += gcFormGroup('business_name', 'Entreprise', c.business_name, 'text');
    html += gcFormGroup('contact_name', 'Contact', c.contact_name, 'text');
    html += gcFormGroup('email', 'Email', c.email, 'email');
    html += gcFormGroup('phone', 'Téléphone', c.phone, 'text');
    html += gcFormGroup('city', 'Ville', c.city, 'text');
    html += gcFormGroup('postal_code', 'Code postal', c.postal_code, 'text');
    html += gcFormGroup('website', 'Site web', c.website, 'url');
    html += gcFormGroup('address', 'Adresse', c.address, 'text');
    html += gcFormSelect('contact_type', 'Type de contact', c.contact_type || '', typeOptions);
    html += gcFormSelect('prospect_status', 'Statut prospect', c.prospect_status || '', statusOptions);
    html += gcFormSelect('partnership_type', 'Partenariat', c.partnership_type || '', partnerOptions);
    html += gcFormGroup('partner_reference', 'Réf. partenaire', c.partner_reference, 'text');
    html += '<div class="gc-form-group full"><label>Notes</label><textarea id="gc_edit_notes" rows="3">' + (c.notes || '') + '</textarea></div>';
    html += '</div>';
    
    if (c.rating) {
        html += '<div style="margin-top:16px;padding:12px;background:#334155;border-radius:8px;font-size:13px">';
        html += '⭐ <strong>' + parseFloat(c.rating).toFixed(1) + '</strong>/5 (' + (c.reviews_count || 0) + ' avis)';
        if (c.google_maps_url) html += ' — <a href="' + c.google_maps_url + '" target="_blank" style="color:#3b82f6">Voir sur Google Maps</a>';
        html += '</div>';
    }
    
    if (c.lists && c.lists.length > 0) {
        html += '<div style="margin-top:16px"><strong style="font-size:13px">Listes :</strong> ';
        c.lists.forEach(l => { html += '<span class="gc-badge" style="background:' + (l.color || '#3B82F6') + '20;color:' + (l.color || '#3B82F6') + '">' + l.name + '</span> '; });
        html += '</div>';
    }
    
    if (c.email_history && c.email_history.length > 0) {
        html += '<div style="margin-top:16px"><strong style="font-size:13px">Historique Emails :</strong>';
        html += '<div style="max-height:150px;overflow-y:auto;margin-top:8px">';
        c.email_history.forEach(e => {
            const st = e.status || 'queued';
            const icon = st === 'sent' ? '📤' : (st === 'opened' ? '👁️' : (st === 'replied' ? '✅' : '⏳'));
            html += '<div style="padding:6px 0;border-bottom:1px solid #334155;font-size:12px">' + icon + ' ' + (e.sequence_name || '—') + ' — ' + st + ' — ' + (e.sent_at || e.created_at || '') + '</div>';
        });
        html += '</div></div>';
    }
    
    document.getElementById('gcModalBody').innerHTML = html;
}

async function gcSaveContact() {
    if (!gcCurrentContactId) return;
    const fields = ['business_name', 'contact_name', 'email', 'phone', 'city', 'postal_code', 'website', 'address', 'contact_type', 'prospect_status', 'partnership_type', 'partner_reference'];
    const data = { action: 'update', id: gcCurrentContactId };
    fields.forEach(f => { const el = document.getElementById('gc_edit_' + f); if (el) data[f] = el.value; });
    data.notes = document.getElementById('gc_edit_notes')?.value || '';
    const res = await fetch(GC_API_CONTACTS, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) });
    const result = await res.json();
    if (result.success) { gcShowToast('Contact mis à jour', 'success'); gcCloseModal('gcContactModal'); setTimeout(() => location.reload(), 800); }
    else { gcShowToast('Erreur: ' + result.message, 'error'); }
}

// ===== Email Validation =====
function gcOpenValidateModal() {
    document.getElementById('gcValidateModal').classList.add('active');
    document.getElementById('gcValidateProgress').style.display = 'none';
    document.getElementById('gcValidateResults').style.display = 'none';
    document.getElementById('gcValidateBtn').disabled = false;
}

async function gcRunValidation() {
    const limit = parseInt(document.getElementById('gcValidateLimit').value) || 50;
    document.getElementById('gcValidateBtn').disabled = true;
    document.getElementById('gcValidateProgress').style.display = 'block';
    document.getElementById('gcValidateStatus').textContent = 'Validation en cours...';
    document.getElementById('gcValidateBar').style.width = '30%';
    const res = await fetch(GC_API_VALIDATOR, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ action: 'validate_pending', limit }) });
    const data = await res.json();
    document.getElementById('gcValidateBar').style.width = '100%';
    if (data.success) {
        document.getElementById('gcValidateStatus').textContent = 'Terminé !';
        let html = '<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">';
        html += '<div style="background:#10B98120;padding:12px;border-radius:8px;text-align:center"><div style="font-size:24px;font-weight:700;color:#10B981">' + data.valid + '</div><div style="font-size:12px;color:#94a3b8">Valides</div></div>';
        html += '<div style="background:#EF444420;padding:12px;border-radius:8px;text-align:center"><div style="font-size:24px;font-weight:700;color:#EF4444">' + data.invalid + '</div><div style="font-size:12px;color:#94a3b8">Invalides</div></div>';
        html += '<div style="background:#F59E0B20;padding:12px;border-radius:8px;text-align:center"><div style="font-size:24px;font-weight:700;color:#F59E0B">' + data.catch_all + '</div><div style="font-size:12px;color:#94a3b8">Catch-all</div></div>';
        html += '</div>';
        document.getElementById('gcValidateResults').innerHTML = html;
        document.getElementById('gcValidateResults').style.display = 'block';
    } else {
        document.getElementById('gcValidateStatus').textContent = 'Erreur: ' + data.message;
    }
    document.getElementById('gcValidateBtn').disabled = false;
}

async function gcValidateEmail(contactId) {
    const btn = event.target;
    btn.textContent = '⏳'; btn.disabled = true;
    const res = await fetch(GC_API_VALIDATOR, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ action: 'validate_contact', contact_id: contactId }) });
    const data = await res.json();
    if (data.success) { gcShowToast('Email: ' + data.result.status, data.result.status === 'valid' ? 'success' : 'error'); setTimeout(() => location.reload(), 1000); }
    else { btn.textContent = '✉️'; btn.disabled = false; }
}

// ===== Helpers =====
function gcFormGroup(name, label, value, type) {
    return '<div class="gc-form-group"><label>' + label + '</label><input type="' + type + '" id="gc_edit_' + name + '" value="' + (value || '').replace(/"/g, '&quot;') + '"></div>';
}
function gcFormSelect(name, label, selected, options) {
    let html = '<div class="gc-form-group"><label>' + label + '</label><select id="gc_edit_' + name + '">';
    for (const [key, val] of Object.entries(options)) { html += '<option value="' + key + '"' + (selected === key ? ' selected' : '') + '>' + val + '</option>'; }
    html += '</select></div>'; return html;
}
function gcCloseModal(id) { document.getElementById(id).classList.remove('active'); gcCurrentContactId = null; }
function gcShowToast(msg, type) {
    const t = document.createElement('div');
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;padding:12px 20px;border-radius:8px;font-size:14px;font-weight:500;z-index:9999;color:white;background:' + (type === 'success' ? '#059669' : '#dc2626');
    t.textContent = msg; document.body.appendChild(t); setTimeout(() => t.remove(), 3000);
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') document.querySelectorAll('.gc-modal-overlay.active').forEach(m => m.classList.remove('active')); });
</script>

<?php if (!$isEmbedded): ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
<?php endif; ?>