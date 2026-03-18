<?php
/**
 * GmbEmailController.php
 * Gestion des séquences email B2B pour le module GMB Scraper
 * 
 * Fonctionnalités :
 * - CRUD séquences et étapes
 * - Envoi emails avec remplacement variables
 * - File d'attente d'envoi
 * - Tracking ouvertures/clics
 * 
 * @package EcosystemeImmo
 */

class GmbEmailController
{
    private $db;
    private $settings = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->loadSettings();
    }

    private function loadSettings(): void
    {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM gmb_settings");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $this->settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    // ─────────────────────────────────────────────
    // SÉQUENCES CRUD
    // ─────────────────────────────────────────────

    public function getSequences(): array
    {
        $sql = "SELECT s.*, 
                (SELECT COUNT(*) FROM gmb_sequence_steps WHERE sequence_id = s.id) as steps_count,
                (SELECT COUNT(*) FROM gmb_email_sends WHERE sequence_id = s.id) as total_sends,
                (SELECT COUNT(*) FROM gmb_email_sends WHERE sequence_id = s.id AND opened_at IS NOT NULL) as total_opens
                FROM gmb_email_sequences s ORDER BY s.created_at ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSequence(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM gmb_email_sequences WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $seq = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$seq) return null;

        // Charger les étapes
        $stepsStmt = $this->db->prepare("SELECT * FROM gmb_sequence_steps WHERE sequence_id = :sid ORDER BY step_order ASC");
        $stepsStmt->execute([':sid' => $id]);
        $seq['steps'] = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);

        return $seq;
    }

    public function createSequence(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO gmb_email_sequences (name, description, sequence_type, is_active) 
             VALUES (:name, :desc, :type, :active)"
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':desc' => $data['description'] ?? '',
            ':type' => $data['sequence_type'] ?? 'custom',
            ':active' => $data['is_active'] ?? 1
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateSequence(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE gmb_email_sequences 
             SET name = :name, description = :desc, sequence_type = :type, is_active = :active
             WHERE id = :id"
        );
        return $stmt->execute([
            ':name' => $data['name'],
            ':desc' => $data['description'] ?? '',
            ':type' => $data['sequence_type'] ?? 'custom',
            ':active' => $data['is_active'] ?? 1,
            ':id' => $id
        ]);
    }

    public function deleteSequence(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM gmb_email_sequences WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    // ─────────────────────────────────────────────
    // ÉTAPES SÉQUENCE
    // ─────────────────────────────────────────────

    public function addStep(int $sequenceId, array $data): int
    {
        // Trouver le prochain order
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(step_order), 0) + 1 FROM gmb_sequence_steps WHERE sequence_id = :sid");
        $stmt->execute([':sid' => $sequenceId]);
        $nextOrder = $stmt->fetchColumn();

        $stmt = $this->db->prepare(
            "INSERT INTO gmb_sequence_steps (sequence_id, step_order, subject, body_html, delay_days, delay_hours) 
             VALUES (:sid, :order, :subject, :body, :days, :hours)"
        );
        $stmt->execute([
            ':sid' => $sequenceId,
            ':order' => $data['step_order'] ?? $nextOrder,
            ':subject' => $data['subject'],
            ':body' => $data['body_html'],
            ':days' => $data['delay_days'] ?? 0,
            ':hours' => $data['delay_hours'] ?? 0
        ]);

        // Mettre à jour le compteur
        $this->db->exec("UPDATE gmb_email_sequences SET total_steps = (SELECT COUNT(*) FROM gmb_sequence_steps WHERE sequence_id = {$sequenceId}) WHERE id = {$sequenceId}");

        return (int)$this->db->lastInsertId();
    }

    public function updateStep(int $stepId, array $data): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE gmb_sequence_steps SET subject = :subject, body_html = :body, delay_days = :days, delay_hours = :hours WHERE id = :id"
        );
        return $stmt->execute([
            ':subject' => $data['subject'],
            ':body' => $data['body_html'],
            ':days' => $data['delay_days'] ?? 0,
            ':hours' => $data['delay_hours'] ?? 0,
            ':id' => $stepId
        ]);
    }

    public function deleteStep(int $stepId): bool
    {
        // Récupérer le sequence_id avant suppression
        $stmt = $this->db->prepare("SELECT sequence_id FROM gmb_sequence_steps WHERE id = :id");
        $stmt->execute([':id' => $stepId]);
        $seqId = $stmt->fetchColumn();

        $stmt = $this->db->prepare("DELETE FROM gmb_sequence_steps WHERE id = :id");
        $result = $stmt->execute([':id' => $stepId]);

        if ($seqId) {
            // Renuméroter les étapes
            $steps = $this->db->query("SELECT id FROM gmb_sequence_steps WHERE sequence_id = {$seqId} ORDER BY step_order ASC")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($steps as $order => $id) {
                $this->db->exec("UPDATE gmb_sequence_steps SET step_order = " . ($order + 1) . " WHERE id = {$id}");
            }
            $this->db->exec("UPDATE gmb_email_sequences SET total_steps = " . count($steps) . " WHERE id = {$seqId}");
        }

        return $result;
    }

    // ─────────────────────────────────────────────
    // ENVOI EMAILS
    // ─────────────────────────────────────────────

    /**
     * Lancer une séquence pour une liste de contacts
     */
    public function startSequenceForList(int $sequenceId, int $listId): array
    {
        $sequence = $this->getSequence($sequenceId);
        if (!$sequence || empty($sequence['steps'])) {
            return ['success' => false, 'error' => 'Séquence invalide ou vide'];
        }

        // Récupérer les contacts de la liste avec email valide
        $stmt = $this->db->prepare(
            "SELECT c.* FROM gmb_contacts c 
             JOIN gmb_contact_list_members m ON c.id = m.contact_id 
             WHERE m.list_id = :lid AND c.email IS NOT NULL AND c.email != '' 
             AND c.email_status IN ('valid', 'catch_all', 'unknown')
             AND c.id NOT IN (SELECT contact_id FROM gmb_email_sends WHERE sequence_id = :sid)"
        );
        $stmt->execute([':lid' => $listId, ':sid' => $sequenceId]);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($contacts)) {
            return ['success' => false, 'error' => 'Aucun contact éligible dans cette liste'];
        }

        $firstStep = $sequence['steps'][0];
        $queued = 0;

        foreach ($contacts as $contact) {
            $this->queueEmail($contact['id'], $sequenceId, $firstStep['id'], $listId);
            $queued++;
        }

        return [
            'success' => true,
            'contacts_queued' => $queued,
            'sequence_name' => $sequence['name']
        ];
    }

    /**
     * Lancer une séquence pour des contacts individuels
     */
    public function startSequenceForContacts(int $sequenceId, array $contactIds): array
    {
        $sequence = $this->getSequence($sequenceId);
        if (!$sequence || empty($sequence['steps'])) {
            return ['success' => false, 'error' => 'Séquence invalide ou vide'];
        }

        $firstStep = $sequence['steps'][0];
        $queued = 0;

        foreach ($contactIds as $contactId) {
            // Vérifier que le contact n'est pas déjà dans cette séquence
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM gmb_email_sends WHERE contact_id = :cid AND sequence_id = :sid"
            );
            $stmt->execute([':cid' => $contactId, ':sid' => $sequenceId]);
            if ($stmt->fetchColumn() > 0) continue;

            $this->queueEmail($contactId, $sequenceId, $firstStep['id']);
            $queued++;
        }

        return ['success' => true, 'contacts_queued' => $queued];
    }

    /**
     * Mettre un email en file d'attente
     */
    private function queueEmail(int $contactId, int $sequenceId, int $stepId, ?int $listId = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO gmb_email_sends (contact_id, sequence_id, step_id, list_id, status) 
             VALUES (:cid, :sid, :step_id, :lid, 'queued')"
        );
        $stmt->execute([
            ':cid' => $contactId,
            ':sid' => $sequenceId,
            ':step_id' => $stepId,
            ':lid' => $listId
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Traiter la file d'attente d'envoi (appelé par CRON)
     */
    public function processQueue(int $batchSize = 10): array
    {
        $dailyLimit = (int)($this->settings['daily_email_limit'] ?? 50);
        $sentToday = (int)($this->settings['emails_sent_today'] ?? 0);
        $remaining = $dailyLimit - $sentToday;

        if ($remaining <= 0) {
            return ['success' => false, 'error' => 'Limite quotidienne atteinte', 'sent' => 0];
        }

        $limit = min($batchSize, $remaining);

        // Récupérer les emails en attente
        $stmt = $this->db->prepare(
            "SELECT es.*, c.email, c.business_name, c.contact_name, c.city, c.rating, c.reviews_count, c.website,
                    ss.subject, ss.body_html
             FROM gmb_email_sends es
             JOIN gmb_contacts c ON es.contact_id = c.id
             JOIN gmb_sequence_steps ss ON es.step_id = ss.id
             WHERE es.status = 'queued'
             ORDER BY es.created_at ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sent = 0;
        $failed = 0;

        foreach ($emails as $emailData) {
            $result = $this->sendEmail($emailData);
            if ($result['success']) {
                $sent++;
            } else {
                $failed++;
            }
        }

        // Mettre à jour le compteur quotidien
        $this->updateSetting('emails_sent_today', (string)($sentToday + $sent));

        return [
            'success' => true,
            'sent' => $sent,
            'failed' => $failed,
            'remaining_today' => $remaining - $sent
        ];
    }

    /**
     * Envoyer un email individuel
     */
    private function sendEmail(array $emailData): array
    {
        // Remplacer les variables dans le sujet et le corps
        $subject = $this->replaceVariables($emailData['subject'], $emailData);
        $body = $this->replaceVariables($emailData['body_html'], $emailData);

        // Ajouter pixel de tracking
        $trackingId = $emailData['id'];
        $trackingPixel = '<img src="' . ($this->settings['tracking_domain'] ?: 'https://votre-domaine.com') . '/admin/api/gmb-tracking.php?action=open&id=' . $trackingId . '" width="1" height="1" style="display:none" />';
        $body .= $trackingPixel;

        // Construire l'email HTML complet
        $htmlEmail = $this->buildHtmlEmail($body);

        // Envoyer via SMTP
        try {
            $result = $this->sendViaSMTP(
                $emailData['email'],
                $subject,
                $htmlEmail
            );

            // Mettre à jour le statut
            $status = $result ? 'sent' : 'failed';
            $updateStmt = $this->db->prepare(
                "UPDATE gmb_email_sends SET 
                 status = :status, subject_sent = :subject, body_sent = :body, 
                 sent_at = IF(:status2 = 'sent', NOW(), NULL),
                 error_message = :error
                 WHERE id = :id"
            );
            $updateStmt->execute([
                ':status' => $status,
                ':subject' => $subject,
                ':body' => $body,
                ':status2' => $status,
                ':error' => $result ? null : 'Échec envoi SMTP',
                ':id' => $emailData['id']
            ]);

            // Mettre à jour le statut du contact
            if ($result) {
                $this->db->prepare("UPDATE gmb_contacts SET prospect_status = 'contacte' WHERE id = :id AND prospect_status IN ('nouveau', 'a_contacter')")
                    ->execute([':id' => $emailData['contact_id']]);
            }

            return ['success' => $result];
        } catch (\Exception $e) {
            error_log("GMB Email send error: " . $e->getMessage());

            $updateStmt = $this->db->prepare(
                "UPDATE gmb_email_sends SET status = 'failed', error_message = :error WHERE id = :id"
            );
            $updateStmt->execute([':error' => $e->getMessage(), ':id' => $emailData['id']]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Remplacer les variables dans le template
     */
    private function replaceVariables(string $template, array $data): string
    {
        $variables = [
            '{{business_name}}' => $data['business_name'] ?? 'votre entreprise',
            '{{contact_name}}' => $data['contact_name'] ?: 'Madame, Monsieur',
            '{{city}}' => $data['city'] ?? 'votre ville',
            '{{rating}}' => $data['rating'] ?? 'N/A',
            '{{reviews_count}}' => $data['reviews_count'] ?? '0',
            '{{website}}' => $data['website'] ?? '',
            '{{default_courtier}}' => $this->settings['default_courtier_partner'] ?? '2L Courtage',
            '{{signature}}' => $this->settings['email_signature'] ?? '',
            '{{sender_name}}' => $this->settings['sender_name'] ?? 'Eduardo De Sul',
            '{{date}}' => date('d/m/Y'),
        ];

        return str_replace(array_keys($variables), array_values($variables), $template);
    }

    /**
     * Construire le HTML complet de l'email
     */
    private function buildHtmlEmail(string $body): string
    {
        return '<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; font-size: 15px; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
' . $body . '
</body></html>';
    }

    /**
     * Envoi SMTP via PHPMailer-like (socket direct)
     */
    private function sendViaSMTP(string $to, string $subject, string $htmlBody): bool
    {
        $host = $this->settings['smtp_host'] ?? '';
        $port = (int)($this->settings['smtp_port'] ?? 587);
        $username = $this->settings['smtp_username'] ?? '';
        $password = $this->settings['smtp_password'] ?? '';
        $fromEmail = $this->settings['sender_email'] ?? '';
        $fromName = $this->settings['sender_name'] ?? 'Eduardo De Sul';

        if (empty($host) || empty($username) || empty($fromEmail)) {
            error_log("GMB SMTP: Configuration SMTP incomplète");
            return false;
        }

        // Utiliser la fonction mail() en fallback ou PHPMailer si disponible
        // Pour la production, il est FORTEMENT recommandé d'utiliser PHPMailer
        $phpmailerPath = __DIR__ . '/../../vendor/PHPMailer/PHPMailer.php';
        
        if (file_exists($phpmailerPath)) {
            require_once $phpmailerPath;
            require_once __DIR__ . '/../../vendor/PHPMailer/SMTP.php';
            require_once __DIR__ . '/../../vendor/PHPMailer/Exception.php';

            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = $host;
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;
            $mail->SMTPSecure = $this->settings['smtp_encryption'] ?? 'tls';
            $mail->Port = $port;
            $mail->CharSet = 'UTF-8';

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

            return $mail->send();
        }

        // Fallback: mail() natif PHP
        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'Content-Type: text/html; charset=UTF-8',
            'MIME-Version: 1.0',
            'X-Mailer: EcosystemeImmo/1.0'
        ];

        return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }

    /**
     * Programmer les prochaines étapes d'une séquence
     * (appelé par CRON après envoi réussi)
     */
    public function scheduleNextSteps(): int
    {
        $scheduled = 0;

        // Trouver les emails envoyés dont la prochaine étape n'est pas encore programmée
        $sql = "SELECT es.id, es.contact_id, es.sequence_id, es.step_id, es.list_id, es.sent_at,
                       ss.step_order, ss.delay_days, ss.delay_hours
                FROM gmb_email_sends es
                JOIN gmb_sequence_steps ss ON es.step_id = ss.id
                WHERE es.status = 'sent'
                AND NOT EXISTS (
                    SELECT 1 FROM gmb_email_sends es2 
                    JOIN gmb_sequence_steps ss2 ON es2.step_id = ss2.id
                    WHERE es2.contact_id = es.contact_id 
                    AND es2.sequence_id = es.sequence_id
                    AND ss2.step_order > ss.step_order
                )";

        $sends = $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sends as $send) {
            // Trouver l'étape suivante
            $nextStmt = $this->db->prepare(
                "SELECT * FROM gmb_sequence_steps 
                 WHERE sequence_id = :sid AND step_order = :next_order"
            );
            $nextStmt->execute([
                ':sid' => $send['sequence_id'],
                ':next_order' => $send['step_order'] + 1
            ]);
            $nextStep = $nextStmt->fetch(PDO::FETCH_ASSOC);

            if (!$nextStep) continue; // Séquence terminée

            // Vérifier que le délai est passé
            $sentAt = new \DateTime($send['sent_at']);
            $delayInterval = new \DateInterval('P' . $nextStep['delay_days'] . 'DT' . $nextStep['delay_hours'] . 'H');
            $nextSendDate = $sentAt->add($delayInterval);

            if (new \DateTime() >= $nextSendDate) {
                // Vérifier que le contact n'a pas répondu ou refusé
                $contactStmt = $this->db->prepare(
                    "SELECT prospect_status FROM gmb_contacts WHERE id = :id"
                );
                $contactStmt->execute([':id' => $send['contact_id']]);
                $status = $contactStmt->fetchColumn();

                if (in_array($status, ['refuse', 'partenaire', 'interesse'])) continue;

                // Programmer l'envoi
                $this->queueEmail($send['contact_id'], $send['sequence_id'], $nextStep['id'], $send['list_id']);
                $scheduled++;
            }
        }

        return $scheduled;
    }

    // ─────────────────────────────────────────────
    // TRACKING
    // ─────────────────────────────────────────────

    public function trackOpen(int $sendId): void
    {
        $this->db->prepare(
            "UPDATE gmb_email_sends SET status = 'opened', opened_at = COALESCE(opened_at, NOW()) WHERE id = :id AND status IN ('sent', 'delivered')"
        )->execute([':id' => $sendId]);
    }

    public function trackClick(int $sendId): void
    {
        $this->db->prepare(
            "UPDATE gmb_email_sends SET status = 'clicked', clicked_at = COALESCE(clicked_at, NOW()) WHERE id = :id"
        )->execute([':id' => $sendId]);
    }

    // ─────────────────────────────────────────────
    // STATS EMAIL
    // ─────────────────────────────────────────────

    public function getEmailStats(?int $sequenceId = null): array
    {
        $where = $sequenceId ? "WHERE sequence_id = {$sequenceId}" : "";

        return [
            'total' => $this->db->query("SELECT COUNT(*) FROM gmb_email_sends {$where}")->fetchColumn(),
            'queued' => $this->db->query("SELECT COUNT(*) FROM gmb_email_sends {$where}" . ($where ? " AND" : " WHERE") . " status = 'queued'")->fetchColumn(),
            'sent' => $this->db->query("SELECT COUNT(*) FROM gmb_email_sends {$where}" . ($where ? " AND" : " WHERE") . " status IN ('sent','delivered','opened','clicked','replied')")->fetchColumn(),
            'opened' => $this->db->query("SELECT COUNT(*) FROM gmb_email_sends {$where}" . ($where ? " AND" : " WHERE") . " opened_at IS NOT NULL")->fetchColumn(),
            'clicked' => $this->db->query("SELECT COUNT(*) FROM gmb_email_sends {$where}" . ($where ? " AND" : " WHERE") . " clicked_at IS NOT NULL")->fetchColumn(),
            'replied' => $this->db->query("SELECT COUNT(*) FROM gmb_email_sends {$where}" . ($where ? " AND" : " WHERE") . " status = 'replied'")->fetchColumn(),
            'bounced' => $this->db->query("SELECT COUNT(*) FROM gmb_email_sends {$where}" . ($where ? " AND" : " WHERE") . " status = 'bounced'")->fetchColumn(),
            'failed' => $this->db->query("SELECT COUNT(*) FROM gmb_email_sends {$where}" . ($where ? " AND" : " WHERE") . " status = 'failed'")->fetchColumn(),
        ];
    }

    private function updateSetting(string $key, string $value): void
    {
        $stmt = $this->db->prepare(
            "UPDATE gmb_settings SET setting_value = :val WHERE setting_key = :key"
        );
        $stmt->execute([':val' => $value, ':key' => $key]);
    }
}