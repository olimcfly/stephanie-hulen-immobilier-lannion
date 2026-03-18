<?php
/**
 * CRON — GMB : Traitement des emails en file
 * admin/api/social/gmb-email-processor.php
 *
 * Ancienne position : modules/social/gmb/cron/gmb-email-processor.php
 * Appel crontab : php /home/mahe6420/public_html/admin/api/social/gmb-email-processor.php
 */
if (PHP_SAPI !== 'cli' && empty($_SERVER['HTTP_X_CRON_SECRET'])) {
    // Permettre aussi un appel HTTP sécurisé si besoin
    $secret = defined('CRON_SECRET') ? CRON_SECRET : ($_ENV['CRON_SECRET'] ?? '');
    if ($secret && ($_SERVER['HTTP_X_CRON_SECRET'] ?? '') !== $secret) {
        http_response_code(403);
        exit('Forbidden');
    }
}

define('ADMIN_ROUTER', true);
require_once dirname(__DIR__, 3) . '/config/config.php';
if (!class_exists('Database')) {
    require_once dirname(__DIR__, 3) . '/includes/classes/Database.php';
}
if (!class_exists('EmailService')) {
    require_once dirname(__DIR__, 3) . '/includes/classes/EmailService.php';
}

$pdo = Database::getInstance();
$log = function(string $msg) {
    $ts = date('Y-m-d H:i:s');
    echo "[$ts] $msg\n";
    error_log("[GMB-email-processor] $msg");
};

try {
    // Récupérer les emails en attente
    $emails = $pdo->query(
        "SELECT e.*, c.email AS contact_email, c.nom AS contact_nom
         FROM gmb_email_queue e
         LEFT JOIN gmb_contacts c ON c.id = e.contact_id
         WHERE e.statut = 'pending'
           AND e.scheduled_at <= NOW()
         ORDER BY e.scheduled_at ASC
         LIMIT 20"
    )->fetchAll(PDO::FETCH_ASSOC);

    $log("Emails en file : " . count($emails));

    foreach ($emails as $email) {
        try {
            $sent = EmailService::send([
                'to'      => $email['contact_email'],
                'name'    => $email['contact_nom'],
                'subject' => $email['subject'],
                'body'    => $email['body_html'],
            ]);

            $statut = $sent ? 'sent' : 'failed';
            $pdo->prepare("UPDATE gmb_email_queue SET statut=?, sent_at=NOW() WHERE id=?")
                ->execute([$statut, $email['id']]);

            $log("Email #{$email['id']} → {$email['contact_email']} : $statut");

        } catch (Exception $inner) {
            $pdo->prepare("UPDATE gmb_email_queue SET statut='failed', error=? WHERE id=?")
                ->execute([$inner->getMessage(), $email['id']]);
            $log("ERREUR email #{$email['id']} : " . $inner->getMessage());
        }
    }

    $log("Traitement terminé.");

} catch (Exception $e) {
    $log("ERREUR FATALE : " . $e->getMessage());
    exit(1);
}