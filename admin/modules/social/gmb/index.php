<?php
/**
 * Module GMB — Dashboard Prospection B2B
 * /admin/modules/gmb/index.php
 *
 * CORRIGÉ : colonnes adaptées à la vraie structure DB
 * - gmb_contacts.prospect_status (pas 'status')
 * - gmb_email_sequences.is_active (pas 'status')
 * - gmb_email_logs (table créée séparément)
 */

// ─── Guard : inclusion par le routeur uniquement ───
$isEmbedded = defined('ADMIN_ROUTER');

if (!$isEmbedded) {
    define('ROOT_PATH', dirname(dirname(dirname(__DIR__))));
    if (file_exists(ROOT_PATH . '/admin/includes/init.php')) {
        require_once ROOT_PATH . '/admin/includes/init.php';
    }
    if (file_exists(ROOT_PATH . '/includes/Database.php')) {
        require_once ROOT_PATH . '/includes/Database.php';
    } elseif (file_exists(ROOT_PATH . '/admin/includes/Database.php')) {
        require_once ROOT_PATH . '/admin/includes/Database.php';
    }
    if (file_exists(ROOT_PATH . '/core/db.php')) {
        require_once ROOT_PATH . '/core/db.php';
    }
}

// ─── Connexion DB ───
if (!isset($pdo) && !isset($db)) {
    if (function_exists('db')) {
        $pdo = db();
    } elseif (class_exists('Database')) {
        $pdo = Database::getInstance();
    }
} elseif (isset($db) && !isset($pdo)) {
    $pdo = $db;
}

// ─── Variables page ───
$page_title = "Prospection B2B";
$current_module = "gmb";

// ─── Helper : requête sécurisée ───
function gmbSafeQuery($pdo, $sql, $default = 0) {
    try {
        $stmt = $pdo->query($sql);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        return $default;
    }
}

function gmbSafeFetchAll($pdo, $sql) {
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// ─── Récupération des stats (avec try/catch individuel) ───
$stats = [
    'contacts'          => 0,
    'emails_valides'    => 0,
    'partenaires'       => 0,
    'sequences_active'  => 0,
    'emails_envoyes'    => 0,
    'emails_ouverts'    => 0,
    'taux_ouverture'    => 0,
    'scrapes_today'     => 0,
];

$recent_contacts = [];
$recent_sequences = [];
$db_errors = [];

if (isset($pdo)) {
    // ─── Contacts : chaque requête isolée ───
    $stats['contacts'] = gmbSafeQuery($pdo, "SELECT COUNT(*) FROM gmb_contacts");
    
    $stats['emails_valides'] = gmbSafeQuery($pdo, "SELECT COUNT(*) FROM gmb_contacts WHERE email_status = 'valid'");
    
    // CORRIGÉ : prospect_status au lieu de status
    $stats['partenaires'] = gmbSafeQuery($pdo, "SELECT COUNT(*) FROM gmb_contacts WHERE prospect_status = 'partenaire'");
    
    $stats['sequences_active'] = gmbSafeQuery($pdo, "SELECT COUNT(*) FROM gmb_email_sequences WHERE is_active = 1");
    
    // gmb_email_logs — peut ne pas exister encore
    $stats['emails_envoyes'] = gmbSafeQuery($pdo, "SELECT COUNT(*) FROM gmb_email_logs WHERE status = 'sent'");
    $stats['emails_ouverts'] = gmbSafeQuery($pdo, "SELECT COUNT(*) FROM gmb_email_logs WHERE opened_at IS NOT NULL");
    
    if ($stats['emails_envoyes'] > 0) {
        $stats['taux_ouverture'] = round(($stats['emails_ouverts'] / $stats['emails_envoyes']) * 100, 1);
    }
    
    $stats['scrapes_today'] = gmbSafeQuery($pdo, "SELECT COUNT(*) FROM gmb_contacts WHERE DATE(created_at) = CURDATE()");
    
    // ─── Derniers contacts ───
    $recent_contacts = gmbSafeFetchAll($pdo, "SELECT * FROM gmb_contacts ORDER BY created_at DESC LIMIT 5");
    
    // ─── Séquences avec stats (LEFT JOIN pour éviter erreur si table manquante) ───
    try {
        $stmt = $pdo->query("
            SELECT s.*, 
                   COALESCE((SELECT COUNT(*) FROM gmb_email_sequence_steps WHERE sequence_id = s.id), 0) as nb_steps,
                   COALESCE((SELECT COUNT(*) FROM gmb_email_logs WHERE sequence_id = s.id AND status = 'sent'), 0) as nb_sent
            FROM gmb_email_sequences s
            ORDER BY s.created_at DESC LIMIT 5
        ");
        $recent_sequences = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback sans les sous-requêtes sur gmb_email_logs
        try {
            $stmt = $pdo->query("
                SELECT s.*, 0 as nb_steps, 0 as nb_sent
                FROM gmb_email_sequences s
                ORDER BY s.created_at DESC LIMIT 5
            ");
            $recent_sequences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e2) {
            $recent_sequences = [];
        }
    }
}

// ─── Début du contenu ───
if (!$isEmbedded) {
    ob_start();
}
?>

<style>
.gmb-dash { padding: 0; }
.gmb-dash h1 { font-size: 24px; font-weight: 700; color: #1a1a2e; margin: 0 0 8px 0; }
.gmb-dash .subtitle { color: #6b7280; font-size: 14px; margin-bottom: 24px; }

.gmb-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 32px;
}
.gmb-stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    border: 1px solid #e5e7eb;
    transition: all 0.2s;
}
.gmb-stat-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transform: translateY(-2px);
}
.gmb-stat-card .stat-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6b7280;
    margin-bottom: 8px;
}
.gmb-stat-card .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #1a1a2e;
}
.gmb-stat-card .stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    margin-bottom: 12px;
}
.gmb-stat-card .stat-icon.blue   { background: #dbeafe; color: #2563eb; }
.gmb-stat-card .stat-icon.green  { background: #d1fae5; color: #059669; }
.gmb-stat-card .stat-icon.purple { background: #ede9fe; color: #7c3aed; }
.gmb-stat-card .stat-icon.orange { background: #ffedd5; color: #ea580c; }
.gmb-stat-card .stat-sub {
    font-size: 12px;
    color: #9ca3af;
    margin-top: 4px;
}

.gmb-actions {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 32px;
}
.gmb-action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 18px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    cursor: pointer;
    text-decoration: none;
    color: #374151;
    font-weight: 500;
    font-size: 14px;
    transition: all 0.2s;
}
.gmb-action-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.gmb-action-btn i.ico {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}
.gmb-action-btn i.bg-blue   { background: #dbeafe; color: #2563eb; }
.gmb-action-btn i.bg-green  { background: #d1fae5; color: #059669; }
.gmb-action-btn i.bg-purple { background: #ede9fe; color: #7c3aed; }
.gmb-action-btn i.bg-orange { background: #ffedd5; color: #ea580c; }

.gmb-section {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    margin-bottom: 24px;
    overflow: hidden;
}
.gmb-section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
}
.gmb-section-header h3 {
    font-size: 16px;
    font-weight: 600;
    color: #1a1a2e;
    margin: 0;
}
.gmb-section-header a {
    font-size: 13px;
    color: #2563eb;
    text-decoration: none;
    font-weight: 500;
}
.gmb-section-header a:hover { text-decoration: underline; }

.gmb-table {
    width: 100%;
    border-collapse: collapse;
}
.gmb-table th {
    text-align: left;
    padding: 10px 16px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #9ca3af;
    background: #f9fafb;
    border-bottom: 1px solid #f3f4f6;
}
.gmb-table td {
    padding: 12px 16px;
    font-size: 14px;
    color: #374151;
    border-bottom: 1px solid #f3f4f6;
}
.gmb-table tr:last-child td { border-bottom: none; }
.gmb-table tr:hover td { background: #f9fafb; }

.gmb-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}
.gmb-badge.valid      { background: #d1fae5; color: #059669; }
.gmb-badge.invalid    { background: #fee2e2; color: #dc2626; }
.gmb-badge.pending    { background: #fef3c7; color: #d97706; }
.gmb-badge.active     { background: #dbeafe; color: #2563eb; }
.gmb-badge.partenaire { background: #ede9fe; color: #7c3aed; }
.gmb-badge.nouveau    { background: #e0f2fe; color: #0284c7; }
.gmb-badge.contacte   { background: #fef3c7; color: #d97706; }
.gmb-badge.qualifie   { background: #d1fae5; color: #059669; }
.gmb-badge.perdu      { background: #f3f4f6; color: #6b7280; }

.gmb-grid-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.gmb-empty {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
}
.gmb-empty i { font-size: 40px; margin-bottom: 12px; display: block; }
.gmb-empty p { font-size: 14px; }

@media (max-width: 1024px) {
    .gmb-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .gmb-actions { grid-template-columns: repeat(2, 1fr); }
    .gmb-grid-2 { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
    .gmb-stats-grid { grid-template-columns: 1fr; }
    .gmb-actions { grid-template-columns: 1fr; }
}
</style>

<div class="gmb-dash">
    <h1><i class="fas fa-crosshairs" style="color:#7c3aed;margin-right:8px;"></i> Prospection B2B</h1>
    <p class="subtitle">Scraper Google My Business · Contacts · Séquences Email</p>

    <!-- Stats -->
    <div class="gmb-stats-grid">
        <div class="gmb-stat-card">
            <div class="stat-icon blue"><i class="fas fa-users"></i></div>
            <div class="stat-label">Contacts scrapés</div>
            <div class="stat-value"><?= number_format($stats['contacts']) ?></div>
            <div class="stat-sub">+<?= $stats['scrapes_today'] ?> aujourd'hui</div>
        </div>
        <div class="gmb-stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Emails validés</div>
            <div class="stat-value"><?= number_format($stats['emails_valides']) ?></div>
            <div class="stat-sub"><?= $stats['contacts'] > 0 ? round(($stats['emails_valides'] / $stats['contacts']) * 100) : 0 ?>% du total</div>
        </div>
        <div class="gmb-stat-card">
            <div class="stat-icon purple"><i class="fas fa-handshake"></i></div>
            <div class="stat-label">Partenaires</div>
            <div class="stat-value"><?= number_format($stats['partenaires']) ?></div>
            <div class="stat-sub">Actifs</div>
        </div>
        <div class="gmb-stat-card">
            <div class="stat-icon orange"><i class="fas fa-paper-plane"></i></div>
            <div class="stat-label">Emails envoyés</div>
            <div class="stat-value"><?= number_format($stats['emails_envoyes']) ?></div>
            <div class="stat-sub"><?= $stats['taux_ouverture'] ?>% taux d'ouverture</div>
        </div>
    </div>

    <!-- Actions Rapides -->
    <div class="gmb-actions">
        <a href="javascript:void(0)" class="gmb-action-btn" onclick="openScraperModal()">
            <i class="fas fa-search ico bg-blue"></i>
            Scraper GMB
        </a>
        <a href="?page=gmb-contacts" class="gmb-action-btn">
            <i class="fas fa-address-book ico bg-green"></i>
            Contacts B2B
        </a>
        <a href="?page=gmb-sequences" class="gmb-action-btn">
            <i class="fas fa-envelope ico bg-purple"></i>
            Séquences Email
        </a>
        <a href="javascript:void(0)" class="gmb-action-btn" onclick="openValidationModal()">
            <i class="fas fa-shield-alt ico bg-orange"></i>
            Valider Emails
        </a>
    </div>

    <!-- 2 colonnes -->
    <div class="gmb-grid-2">
        
        <!-- Derniers contacts -->
        <div class="gmb-section">
            <div class="gmb-section-header">
                <h3><i class="fas fa-users" style="color:#2563eb;margin-right:6px;"></i> Derniers contacts</h3>
                <a href="?page=gmb-contacts">Voir tout →</a>
            </div>
            <?php if (empty($recent_contacts)): ?>
                <div class="gmb-empty">
                    <i class="fas fa-search"></i>
                    <p>Aucun contact scrapé.<br>Lancez votre premier scrape GMB !</p>
                </div>
            <?php else: ?>
                <table class="gmb-table">
                    <thead>
                        <tr>
                            <th>Entreprise</th>
                            <th>Email</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_contacts as $c): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($c['business_name'] ?? $c['name'] ?? '—') ?></strong>
                                    <?php if (!empty($c['category'])): ?>
                                        <br><small style="color:#9ca3af;"><?= htmlspecialchars($c['category']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($c['email'] ?? '—') ?>
                                    <?php if (!empty($c['email_status'])): ?>
                                        <br><span class="gmb-badge <?= htmlspecialchars($c['email_status']) ?>"><?= htmlspecialchars($c['email_status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $ps = $c['prospect_status'] ?? $c['status'] ?? 'nouveau';
                                    ?>
                                    <span class="gmb-badge <?= htmlspecialchars($ps) ?>">
                                        <?= htmlspecialchars(ucfirst($ps)) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Séquences -->
        <div class="gmb-section">
            <div class="gmb-section-header">
                <h3><i class="fas fa-envelope" style="color:#7c3aed;margin-right:6px;"></i> Séquences email</h3>
                <a href="?page=gmb-sequences">Gérer →</a>
            </div>
            <?php if (empty($recent_sequences)): ?>
                <div class="gmb-empty">
                    <i class="fas fa-envelope-open-text"></i>
                    <p>Aucune séquence créée.<br>Créez votre première campagne B2B !</p>
                </div>
            <?php else: ?>
                <table class="gmb-table">
                    <thead>
                        <tr>
                            <th>Séquence</th>
                            <th>Étapes</th>
                            <th>Envoyés</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_sequences as $s): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($s['name'] ?? $s['title'] ?? '—') ?></strong></td>
                                <td><?= (int)($s['nb_steps'] ?? $s['total_steps'] ?? 0) ?></td>
                                <td><?= (int)($s['nb_sent'] ?? 0) ?></td>
                                <td>
                                    <span class="gmb-badge <?= ($s['is_active'] ?? 0) ? 'active' : 'pending' ?>">
                                        <?= ($s['is_active'] ?? 0) ? 'Active' : 'Pause' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pipeline -->
    <div class="gmb-section">
        <div class="gmb-section-header">
            <h3><i class="fas fa-chart-bar" style="color:#059669;margin-right:6px;"></i> Pipeline de conversion B2B</h3>
        </div>
        <div style="padding:20px;">
            <div style="display:flex;gap:8px;align-items:stretch;height:60px;">
                <?php
                $pipeline = [
                    ['label' => 'Scrapés',     'count' => $stats['contacts'],       'color' => '#3b82f6'],
                    ['label' => 'Email validé', 'count' => $stats['emails_valides'], 'color' => '#10b981'],
                    ['label' => 'Contactés',   'count' => $stats['emails_envoyes'], 'color' => '#f59e0b'],
                    ['label' => 'Ouverts',     'count' => $stats['emails_ouverts'], 'color' => '#8b5cf6'],
                    ['label' => 'Partenaires', 'count' => $stats['partenaires'],    'color' => '#059669'],
                ];
                $max = max(1, $stats['contacts']);
                foreach ($pipeline as $step):
                    $pct = max(5, round(($step['count'] / $max) * 100));
                ?>
                <div style="flex:1;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:4px;">
                    <span style="font-size:18px;font-weight:700;color:<?= $step['color'] ?>;"><?= $step['count'] ?></span>
                    <div style="width:100%;height:<?= $pct ?>%;min-height:8px;background:<?= $step['color'] ?>;border-radius:6px 6px 0 0;opacity:0.85;"></div>
                    <span style="font-size:11px;color:#6b7280;font-weight:500;"><?= $step['label'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Scraper -->
<div id="scraperModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;max-width:600px;width:90%;padding:30px;position:relative;">
        <button onclick="document.getElementById('scraperModal').style.display='none'" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;color:#9ca3af;">×</button>
        <h2 style="margin:0 0 20px;font-size:20px;color:#1a1a2e;"><i class="fas fa-search" style="color:#2563eb;"></i> Scraper Google My Business</h2>
        <div style="margin-bottom:16px;">
            <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Recherche</label>
            <input type="text" id="scraperQuery" placeholder="ex: courtier immobilier Bordeaux" style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
            <div>
                <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Localisation</label>
                <input type="text" id="scraperLocation" value="Bordeaux, France" style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Rayon (km)</label>
                <input type="number" id="scraperRadius" value="30" min="1" max="100" style="width:100%;padding:10px 14px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;box-sizing:border-box;">
            </div>
        </div>
        <div id="scraperResults" style="display:none;margin-bottom:16px;max-height:200px;overflow-y:auto;"></div>
        <button onclick="launchScrape()" style="width:100%;padding:12px;background:#2563eb;color:white;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;">
            <i class="fas fa-rocket"></i> Lancer le scrape
        </button>
    </div>
</div>

<!-- Modal Validation -->
<div id="validationModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:16px;max-width:500px;width:90%;padding:30px;position:relative;">
        <button onclick="document.getElementById('validationModal').style.display='none'" style="position:absolute;top:12px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;color:#9ca3af;">×</button>
        <h2 style="margin:0 0 20px;font-size:20px;color:#1a1a2e;"><i class="fas fa-shield-alt" style="color:#ea580c;"></i> Validation d'emails</h2>
        <p style="color:#6b7280;font-size:14px;margin-bottom:20px;">Valider les emails en attente pour améliorer la délivrabilité.</p>
        <div id="validationProgress" style="display:none;margin-bottom:16px;">
            <div style="background:#e5e7eb;border-radius:8px;height:8px;overflow:hidden;">
                <div id="validationBar" style="background:#059669;height:100%;width:0%;transition:width 0.3s;"></div>
            </div>
            <p id="validationText" style="font-size:13px;color:#6b7280;margin-top:6px;"></p>
        </div>
        <button onclick="launchValidation()" style="width:100%;padding:12px;background:#ea580c;color:white;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;">
            <i class="fas fa-play"></i> Valider les emails en attente
        </button>
    </div>
</div>

<script>
function openScraperModal() {
    const m = document.getElementById('scraperModal');
    m.style.display = 'flex';
}
function openValidationModal() {
    const m = document.getElementById('validationModal');
    m.style.display = 'flex';
}

function launchScrape() {
    const query = document.getElementById('scraperQuery').value;
    const loc = document.getElementById('scraperLocation').value;
    const radius = document.getElementById('scraperRadius').value;
    if (!query) { alert('Entrez une recherche'); return; }
    const r = document.getElementById('scraperResults');
    r.style.display = 'block';
    r.innerHTML = '<p style="color:#2563eb;"><i class="fas fa-spinner fa-spin"></i> Scrape en cours...</p>';
    fetch('modules/gmb/api/gmb-scraper.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ query, location: loc, radius })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            r.innerHTML = '<p style="color:#059669;"><i class="fas fa-check-circle"></i> ' + (data.count || 0) + ' contacts trouvés !</p>';
            setTimeout(() => window.location.reload(), 2000);
        } else {
            r.innerHTML = '<p style="color:#dc2626;"><i class="fas fa-times-circle"></i> ' + (data.error || 'Erreur') + '</p>';
        }
    })
    .catch(err => { r.innerHTML = '<p style="color:#dc2626;">Erreur réseau</p>'; });
}

function launchValidation() {
    const prog = document.getElementById('validationProgress');
    const bar = document.getElementById('validationBar');
    const txt = document.getElementById('validationText');
    prog.style.display = 'block';
    bar.style.width = '10%';
    txt.textContent = 'Validation en cours...';
    fetch('modules/gmb/api/email-validator.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ action: 'validate_pending' })
    })
    .then(res => res.json())
    .then(data => {
        bar.style.width = '100%';
        txt.textContent = data.success 
            ? (data.validated || 0) + ' validés, ' + (data.invalid || 0) + ' invalides.'
            : 'Erreur: ' + (data.error || '?');
        if (data.success) setTimeout(() => window.location.reload(), 2000);
    })
    .catch(err => { bar.style.width = '100%'; bar.style.background = '#dc2626'; txt.textContent = 'Erreur réseau'; });
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('scraperModal').style.display = 'none';
        document.getElementById('validationModal').style.display = 'none';
    }
});
</script>

<?php
if (!$isEmbedded) {
    $content = ob_get_clean();
    if (file_exists(__DIR__ . '/../../includes/layout.php')) {
        require __DIR__ . '/../../includes/layout.php';
    } else {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . $page_title . '</title>';
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
        echo '</head><body style="padding:40px;">' . $content . '</body></html>';
    }
}
?>