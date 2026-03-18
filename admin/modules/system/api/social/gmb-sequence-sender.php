<?php
/**
 * CRON — GMB : Envoi des séquences email planifiées
 * admin/api/social/gmb-sequence-sender.php
 *
 * Ancienne position : modules/social/gmb/cron/sequence-sender.php
 * Appel crontab : php /home/mahe6420/public_html/admin/api/social/gmb-sequence-sender.php
 */
if (PHP_SAPI !== 'cli' && empty($_SERVER['HTTP_X_CRON_SECRET'])) {
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
    error_log("[GMB-sequence-sender] $msg");
};

try {
    // Séquences actives avec étapes à envoyer
    $steps = $pdo->query(
        "SELECT ss.*, s.nom AS sequence_nom,
                e.body_html, e.subject,
                c.email AS contact_email, c.nom AS contact_nom
         FROM gmb_sequence_sends ss
         JOIN gmb_sequences s       ON s.id = ss.sequence_id
         JOIN gmb_sequence_steps e  ON e.id = ss.step_id
         JOIN gmb_contacts c        ON c.id = ss.contact_id
         WHERE ss.statut = 'pending'
           AND ss.scheduled_at <= NOW()
         ORDER BY ss.scheduled_at ASC
         LIMIT 30"
    )->fetchAll(PDO::FETCH_ASSOC);

    $log("Étapes à envoyer : " . count($steps));

    foreach ($steps as $step) {
        try {
            $sent = EmailService::send([
                'to'      => $step['contact_email'],
                'name'    => $step['contact_nom'],
                'subject' => $step['subject'],
                'body'    => $step['body_html'],
            ]);

            $statut = $sent ? 'sent' : 'failed';
            $pdo->prepare("UPDATE gmb_sequence_sends SET statut=?, sent_at=NOW() WHERE id=?")
                ->execute([$statut, $step['id']]);

            $log("Séquence [{$step['sequence_nom']}] step #{$step['step_id']} → {$step['contact_email']} : $statut");

        } catch (Exception $inner) {
            $pdo->prepare("UPDATE gmb_sequence_sends SET statut='failed', error=? WHERE id=?")
                ->execute([$inner->getMessage(), $step['id']]);
            $log("ERREUR step #{$step['id']} : " . $inner->getMessage());
        }
    }

    $log("Traitement terminé.");

} catch (Exception $e) {
    $log("ERREUR FATALE : " . $e->getMessage());
    exit(1);
}