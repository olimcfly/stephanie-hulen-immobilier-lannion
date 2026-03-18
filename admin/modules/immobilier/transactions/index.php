<?php
/**
 * transactions-index.php — Transactions
 * IMMO LOCAL+
 */
defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
ob_start();

$rootPath = '/home/mahe6420/public_html';
if (!defined('DB_HOST'))       require_once $rootPath . '/config/config.php';
if (!class_exists('Database')) require_once $rootPath . '/includes/classes/Database.php';

try { $db = Database::getInstance(); } catch (Exception $e) { $db = null; }

$pageTitle = 'Transactions';
$content   = ob_get_clean();
?>
<style>
.mod-wrap{display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:64px 20px;text-align:center;background:var(--surface,#fff);
  border:1px solid var(--border,#e5e7eb);border-radius:12px;}
.mod-ico{width:64px;height:64px;border-radius:12px;background:#10b98118;color:#10b981;
  display:flex;align-items:center;justify-content:center;font-size:26px;margin:0 auto 16px;}
.mod-wrap h2{font-size:16px;font-weight:700;margin-bottom:6px;}
.mod-wrap p{font-size:13px;color:var(--text-3,#9ca3af);max-width:380px;line-height:1.6;}
</style>
<div class="page-hd">
  <div>
    <h1><i class="fas fa-file-signature" style="color:#10b981;margin-right:8px"></i>Transactions</h1>
    <div class="page-hd-sub">Immobilier</div>
  </div>
</div>
<div class="mod-wrap anim">
  <div class="mod-ico"><i class="fas fa-file-signature"></i></div>
  <h2>Transactions</h2>
  <p>Ce module est en cours de configuration. Revenez bientôt.</p>
</div>
<?php require_once $rootPath . '/admin/layout/layout.php'; ?>