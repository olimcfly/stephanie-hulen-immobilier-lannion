<?php
/**
 * ══════════════════════════════════════════════════════════════
 * COURTIERS — install.php v1.0
 * /admin/modules/courtiers/install.php
 *
 * Appelé par index.php quand ?run=install
 * Ne pas appeler directement en production.
 * ══════════════════════════════════════════════════════════════
 */
if (!defined('ADMIN_ROUTER')) { http_response_code(403); exit('Accès refusé'); }
if (isset($db) && !isset($pdo)) $pdo = $db;

$log     = [];   // messages d'installation
$success = true;

// ── Helper log ────────────────────────────────────────────────
function install_log(array &$log, string $status, string $msg): void {
    $log[] = ['status' => $status, 'msg' => $msg];
}

// ══════════════════════════════════════════════════════════════
// TABLE : courtiers
// ══════════════════════════════════════════════════════════════
$tableExists = false;
try {
    $pdo->query("SELECT 1 FROM courtiers LIMIT 1");
    $tableExists = true;
    install_log($log, 'skip', 'Table <code>courtiers</code> déjà existante — ignorée');
} catch (PDOException $e) {}

if (!$tableExists) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `courtiers` (
              `id`              INT AUTO_INCREMENT PRIMARY KEY,
              `nom`             VARCHAR(100)  NOT NULL,
              `prenom`          VARCHAR(100)  NOT NULL DEFAULT '',
              `email`           VARCHAR(255)  DEFAULT NULL,
              `phone`           VARCHAR(30)   DEFAULT NULL,
              `company`         VARCHAR(150)  DEFAULT NULL,
              `city`            VARCHAR(100)  DEFAULT NULL,
              `zone_geo`        VARCHAR(200)  DEFAULT NULL,
              `type`            ENUM('courtier','mandataire','apporteur','partenaire','notaire')
                                DEFAULT 'courtier',
              `status`          ENUM('actif','prospect','inactif','pause')
                                DEFAULT 'prospect',
              `commission_rate` DECIMAL(5,2)  DEFAULT 0.00,
              `reco_count`      INT           DEFAULT 0,
              `revenu_total`    DECIMAL(10,2) DEFAULT 0.00,
              `lead_id`         INT           DEFAULT NULL,
              `notes`           TEXT          DEFAULT NULL,
              `created_at`      DATETIME      DEFAULT CURRENT_TIMESTAMP,
              `updated_at`      DATETIME      DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX `idx_status`  (`status`),
              INDEX `idx_type`    (`type`),
              INDEX `idx_lead_id` (`lead_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        install_log($log, 'ok', 'Table <code>courtiers</code> créée');
    } catch (PDOException $e) {
        install_log($log, 'error', 'Erreur création <code>courtiers</code> : ' . $e->getMessage());
        $success = false;
    }
}

// ══════════════════════════════════════════════════════════════
// MIGRATIONS : colonnes manquantes sur table existante
// Permet de mettre à jour un module déjà installé
// ══════════════════════════════════════════════════════════════
if ($tableExists) {
    $existingCols = [];
    try {
        $existingCols = $pdo->query("SHOW COLUMNS FROM courtiers")->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {}

    $migrations = [
        'zone_geo'        => "ALTER TABLE `courtiers` ADD COLUMN `zone_geo` VARCHAR(200) DEFAULT NULL AFTER `city`",
        'commission_rate' => "ALTER TABLE `courtiers` ADD COLUMN `commission_rate` DECIMAL(5,2) DEFAULT 0.00 AFTER `type`",
        'reco_count'      => "ALTER TABLE `courtiers` ADD COLUMN `reco_count` INT DEFAULT 0 AFTER `commission_rate`",
        'revenu_total'    => "ALTER TABLE `courtiers` ADD COLUMN `revenu_total` DECIMAL(10,2) DEFAULT 0.00 AFTER `reco_count`",
        'lead_id'         => "ALTER TABLE `courtiers` ADD COLUMN `lead_id` INT DEFAULT NULL AFTER `revenu_total`",
        'notes'           => "ALTER TABLE `courtiers` ADD COLUMN `notes` TEXT DEFAULT NULL AFTER `lead_id`",
        'updated_at'      => "ALTER TABLE `courtiers` ADD COLUMN `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
    ];

    foreach ($migrations as $col => $sql) {
        if (!in_array($col, $existingCols)) {
            try {
                $pdo->exec($sql);
                install_log($log, 'ok', "Colonne <code>$col</code> ajoutée à <code>courtiers</code>");
            } catch (PDOException $e) {
                install_log($log, 'error', "Erreur ajout <code>$col</code> : " . $e->getMessage());
                $success = false;
            }
        }
    }

    // Index manquants
    $indexes = [
        'idx_status'  => "ALTER TABLE `courtiers` ADD INDEX `idx_status`  (`status`)",
        'idx_type'    => "ALTER TABLE `courtiers` ADD INDEX `idx_type`    (`type`)",
        'idx_lead_id' => "ALTER TABLE `courtiers` ADD INDEX `idx_lead_id` (`lead_id`)",
    ];
    try {
        $existingIdx = $pdo->query("SHOW INDEX FROM courtiers")->fetchAll(PDO::FETCH_ASSOC);
        $existingIdxNames = array_column($existingIdx, 'Key_name');
    } catch (PDOException $e) { $existingIdxNames = []; }

    foreach ($indexes as $name => $sql) {
        if (!in_array($name, $existingIdxNames)) {
            try {
                $pdo->exec($sql);
                install_log($log, 'ok', "Index <code>$name</code> ajouté");
            } catch (PDOException $e) {
                // Index peut déjà exister sous un autre nom — non bloquant
                install_log($log, 'skip', "Index <code>$name</code> ignoré (déjà présent ou non nécessaire)");
            }
        } else {
            install_log($log, 'skip', "Index <code>$name</code> déjà présent");
        }
    }
}

// ══════════════════════════════════════════════════════════════
// RÉSULTAT — affiché dans la page (index.php l'intercepte)
// ══════════════════════════════════════════════════════════════
?>
<style>
.install-wrap { max-width:680px; margin:0 auto; }
.install-card {
    background:#fff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden;
    box-shadow:0 4px 20px rgba(0,0,0,.06);
}
.install-head {
    padding:20px 24px; border-bottom:1px solid #e5e7eb;
    display:flex; align-items:center; gap:12px;
    background:<?= $success ? 'linear-gradient(135deg,#d1fae5,#ecfdf5)' : 'linear-gradient(135deg,#fef2f2,#fff1f2)' ?>;
}
.install-head-icon {
    width:48px; height:48px; border-radius:12px;
    background:<?= $success ? '#10b981' : '#ef4444' ?>;
    display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.2rem;
}
.install-head h3 { font-size:1rem; font-weight:700; margin:0; color:#111827; }
.install-head p  { font-size:.8rem; color:#6b7280; margin:4px 0 0; }
.install-body { padding:20px 24px; }
.install-log  { display:flex; flex-direction:column; gap:8px; }
.install-row  { display:flex; align-items:flex-start; gap:10px; padding:10px 14px; border-radius:10px; font-size:.82rem; }
.install-row.ok    { background:#f0fdf4; border:1px solid #bbf7d0; }
.install-row.error { background:#fef2f2; border:1px solid #fecaca; }
.install-row.skip  { background:#f9fafb; border:1px solid #e5e7eb; }
.install-row .ico  { font-size:.9rem; flex-shrink:0; margin-top:1px; }
.install-row.ok    .ico { color:#10b981; }
.install-row.error .ico { color:#ef4444; }
.install-row.skip  .ico { color:#9ca3af; }
.install-foot { padding:16px 24px; border-top:1px solid #e5e7eb; display:flex; justify-content:flex-end; gap:10px; }
.inst-btn { display:inline-flex; align-items:center; gap:6px; padding:10px 20px; border-radius:10px; font-size:.83rem; font-weight:600; cursor:pointer; border:none; font-family:inherit; text-decoration:none; }
.inst-btn-primary { background:#14b8a6; color:#fff; }
.inst-btn-primary:hover { background:#0d9488; color:#fff; }
.inst-btn-outline { background:#fff; color:#374151; border:1px solid #e5e7eb; }
.inst-btn-outline:hover { border-color:#14b8a6; color:#14b8a6; }
</style>

<div class="install-wrap">
    <div class="install-card">
        <div class="install-head">
            <div class="install-head-icon">
                <i class="fas <?= $success ? 'fa-check' : 'fa-times' ?>"></i>
            </div>
            <div>
                <h3><?= $success ? 'Installation réussie' : 'Installation avec erreurs' ?></h3>
                <p>Module Courtiers Partenaires — <?= date('d/m/Y H:i') ?></p>
            </div>
        </div>
        <div class="install-body">
            <div class="install-log">
                <?php foreach ($log as $entry): ?>
                <div class="install-row <?= $entry['status'] ?>">
                    <span class="ico">
                        <i class="fas <?= $entry['status']==='ok' ? 'fa-check-circle' : ($entry['status']==='error' ? 'fa-times-circle' : 'fa-minus-circle') ?>"></i>
                    </span>
                    <span><?= $entry['msg'] ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($log)): ?>
                <div class="install-row skip">
                    <span class="ico"><i class="fas fa-info-circle"></i></span>
                    <span>Rien à faire — tout est déjà en place.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="install-foot">
            <?php if (!$success): ?>
            <a href="?page=courtiers&run=install" class="inst-btn inst-btn-outline">
                <i class="fas fa-redo"></i> Réessayer
            </a>
            <?php endif; ?>
            <a href="?page=courtiers" class="inst-btn inst-btn-primary">
                <i class="fas fa-arrow-right"></i>
                <?= $success ? 'Accéder au module' : 'Retour' ?>
            </a>
        </div>
    </div>
</div>