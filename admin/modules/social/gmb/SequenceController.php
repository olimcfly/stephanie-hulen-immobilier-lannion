<?php
/**
 * SequenceController - Gestion des séquences email B2B
 * Module : admin/modules/gmb/SequenceController.php
 * 
 * DB réelle :
 * - gmb_email_sequences : name, sequence_type, is_active, total_steps
 * - gmb_sequence_steps : sequence_id, step_order, subject, body_html, delay_days, delay_hours
 * - gmb_email_sends : contact_id, sequence_id, step_id, list_id, status, sent_at, opened_at...
 */

class SequenceController
{
    private $db;

    // Variables disponibles dans les templates
    private $templateVars = [
        '{{business_name}}'    => 'Nom de l\'entreprise',
        '{{contact_name}}'     => 'Nom du contact',
        '{{email}}'            => 'Email du contact',
        '{{phone}}'            => 'Téléphone',
        '{{city}}'             => 'Ville',
        '{{rating}}'           => 'Note Google',
        '{{reviews_count}}'    => 'Nombre d\'avis',
        '{{website}}'          => 'Site web',
        '{{business_category}}' => 'Catégorie',
        '{{sender_name}}'      => 'Nom de l\'expéditeur',
    ];

    public function __construct($db)
    {
        $this->db = $db;
    }

    // ============================================================
    // SÉQUENCES CRUD
    // ============================================================

    /**
     * Toutes les séquences avec stats
     */
    public function getSequences(): array
    {
        $stmt = $this->db->query("
            SELECT es.*,
                (SELECT COUNT(*) FROM gmb_sequence_steps ss WHERE ss.sequence_id = es.id) as steps_count,
                (SELECT COUNT(*) FROM gmb_email_sends ems WHERE ems.sequence_id = es.id) as total_queued,
                (SELECT COUNT(*) FROM gmb_email_sends ems WHERE ems.sequence_id = es.id AND ems.status IN ('sent','delivered','opened','clicked','replied')) as total_sent,
                (SELECT COUNT(*) FROM gmb_email_sends ems WHERE ems.sequence_id = es.id AND ems.opened_at IS NOT NULL) as total_opened,
                (SELECT COUNT(*) FROM gmb_email_sends ems WHERE ems.sequence_id = es.id AND ems.replied_at IS NOT NULL) as total_replied,
                (SELECT COUNT(*) FROM gmb_email_sends ems WHERE ems.sequence_id = es.id AND ems.bounced_at IS NOT NULL) as total_bounced
            FROM gmb_email_sequences es 
            ORDER BY es.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Une séquence avec ses étapes
     */
    public function getSequence(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM gmb_email_sequences WHERE id = ?");
        $stmt->execute([$id]);
        $sequence = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sequence) return null;

        // Étapes
        $stepsStmt = $this->db->prepare("
            SELECT * FROM gmb_sequence_steps 
            WHERE sequence_id = ? 
            ORDER BY step_order ASC
        ");
        $stepsStmt->execute([$id]);
        $sequence['steps'] = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Stats par étape
        foreach ($sequence['steps'] as &$step) {
            $statsStmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('sent','delivered','opened','clicked','replied') THEN 1 ELSE 0 END) as sent,
                    SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as opened,
                    SUM(CASE WHEN replied_at IS NOT NULL THEN 1 ELSE 0 END) as replied,
                    SUM(CASE WHEN bounced_at IS NOT NULL THEN 1 ELSE 0 END) as bounced
                FROM gmb_email_sends WHERE step_id = ?
            ");
            $statsStmt->execute([$step['id']]);
            $step['stats'] = $statsStmt->fetch(PDO::FETCH_ASSOC);
        }

        return $sequence;
    }

    /**
     * Créer une séquence
     */
    public function createSequence(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO gmb_email_sequences (name, description, sequence_type, is_active) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['sequence_type'] ?? 'echange_liens',
            (int)($data['is_active'] ?? 0),
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Modifier une séquence
     */
    public function updateSequence(int $id, array $data): bool
    {
        $allowed = ['name', 'description', 'sequence_type', 'is_active'];
        $sets = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $sets[] = "{$key} = ?";
                $params[] = $key === 'is_active' ? (int)$value : $value;
            }
        }

        if (empty($sets)) return false;

        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE gmb_email_sequences SET " . implode(', ', $sets) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    /**
     * Supprimer une séquence
     */
    public function deleteSequence(int $id): bool
    {
        // Les steps et sends seront supprimés par CASCADE si FK existe, sinon manuellement
        $this->db->prepare("DELETE FROM gmb_email_sends WHERE sequence_id = ?")->execute([$id]);
        $this->db->prepare("DELETE FROM gmb_sequence_steps WHERE sequence_id = ?")->execute([$id]);
        $stmt = $this->db->prepare("DELETE FROM gmb_email_sequences WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Activer/Désactiver une séquence
     */
    public function toggleSequence(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE gmb_email_sequences SET is_active = NOT is_active WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // ============================================================
    // ÉTAPES (STEPS) CRUD
    // ============================================================

    /**
     * Ajouter une étape
     */
    public function addStep(int $sequenceId, array $data): int
    {
        // Déterminer le prochain order
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(step_order), 0) + 1 FROM gmb_sequence_steps WHERE sequence_id = ?");
        $stmt->execute([$sequenceId]);
        $nextOrder = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("
            INSERT INTO gmb_sequence_steps (sequence_id, step_order, subject, body_html, delay_days, delay_hours) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $sequenceId,
            $data['step_order'] ?? $nextOrder,
            $data['subject'],
            $data['body_html'],
            (int)($data['delay_days'] ?? 0),
            (int)($data['delay_hours'] ?? 0),
        ]);

        // Mettre à jour total_steps
        $this->updateStepCount($sequenceId);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Modifier une étape
     */
    public function updateStep(int $stepId, array $data): bool
    {
        $allowed = ['subject', 'body_html', 'delay_days', 'delay_hours', 'step_order'];
        $sets = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed)) {
                $sets[] = "{$key} = ?";
                $params[] = in_array($key, ['delay_days', 'delay_hours', 'step_order']) ? (int)$value : $value;
            }
        }

        if (empty($sets)) return false;

        $params[] = $stepId;
        $stmt = $this->db->prepare("UPDATE gmb_sequence_steps SET " . implode(', ', $sets) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    /**
     * Supprimer une étape
     */
    public function deleteStep(int $stepId): bool
    {
        // Récupérer le sequence_id avant suppression
        $stmt = $this->db->prepare("SELECT sequence_id FROM gmb_sequence_steps WHERE id = ?");
        $stmt->execute([$stepId]);
        $sequenceId = (int)$stmt->fetchColumn();

        $stmt = $this->db->prepare("DELETE FROM gmb_sequence_steps WHERE id = ?");
        $result = $stmt->execute([$stepId]);

        if ($sequenceId) {
            $this->updateStepCount($sequenceId);
            $this->reorderSteps($sequenceId);
        }

        return $result;
    }

    private function updateStepCount(int $sequenceId): void
    {
        $stmt = $this->db->prepare("
            UPDATE gmb_email_sequences 
            SET total_steps = (SELECT COUNT(*) FROM gmb_sequence_steps WHERE sequence_id = ?)
            WHERE id = ?
        ");
        $stmt->execute([$sequenceId, $sequenceId]);
    }

    private function reorderSteps(int $sequenceId): void
    {
        $stmt = $this->db->prepare("SELECT id FROM gmb_sequence_steps WHERE sequence_id = ? ORDER BY step_order ASC");
        $stmt->execute([$sequenceId]);
        $steps = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $update = $this->db->prepare("UPDATE gmb_sequence_steps SET step_order = ? WHERE id = ?");
        foreach ($steps as $i => $stepId) {
            $update->execute([$i + 1, $stepId]);
        }
    }

    // ============================================================
    // ENROLLMENT (Inscrire des contacts dans une séquence)
    // ============================================================

    /**
     * Inscrire des contacts dans une séquence
     */
    public function enrollContacts(int $sequenceId, array $contactIds, ?int $listId = null): array
    {
        $sequence = $this->getSequence($sequenceId);
        if (!$sequence || empty($sequence['steps'])) {
            return ['success' => false, 'message' => 'Séquence sans étapes'];
        }

        if (!$sequence['is_active']) {
            return ['success' => false, 'message' => 'Séquence inactive'];
        }

        $firstStep = $sequence['steps'][0];
        $enrolled = 0;
        $skipped = 0;

        foreach ($contactIds as $contactId) {
            // Vérifier si déjà inscrit dans cette séquence
            $checkStmt = $this->db->prepare("
                SELECT COUNT(*) FROM gmb_email_sends 
                WHERE contact_id = ? AND sequence_id = ?
            ");
            $checkStmt->execute([$contactId, $sequenceId]);
            
            if ((int)$checkStmt->fetchColumn() > 0) {
                $skipped++;
                continue;
            }

            // Vérifier que le contact a un email valide
            $contactStmt = $this->db->prepare("
                SELECT email, email_status FROM gmb_contacts WHERE id = ?
            ");
            $contactStmt->execute([$contactId]);
            $contact = $contactStmt->fetch(PDO::FETCH_ASSOC);

            if (empty($contact['email']) || $contact['email_status'] === 'invalid') {
                $skipped++;
                continue;
            }

            // Créer l'envoi pour la première étape
            $insertStmt = $this->db->prepare("
                INSERT INTO gmb_email_sends (contact_id, sequence_id, step_id, list_id, status) 
                VALUES (?, ?, ?, ?, 'queued')
            ");
            $insertStmt->execute([$contactId, $sequenceId, $firstStep['id'], $listId]);
            $enrolled++;
        }

        return [
            'success' => true,
            'enrolled' => $enrolled,
            'skipped' => $skipped,
            'message' => "{$enrolled} contact(s) inscrit(s), {$skipped} ignoré(s)",
        ];
    }

    /**
     * Inscrire tous les contacts d'une liste
     */
    public function enrollList(int $sequenceId, int $listId): array
    {
        $stmt = $this->db->prepare("SELECT contact_id FROM gmb_contact_list_members WHERE list_id = ?");
        $stmt->execute([$listId]);
        $contactIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return $this->enrollContacts($sequenceId, $contactIds, $listId);
    }

    // ============================================================
    // TEMPLATE VARIABLES
    // ============================================================

    /**
     * Remplacer les variables dans un template
     */
    public function renderTemplate(string $template, array $contact, array $settings = []): string
    {
        $replacements = [
            '{{business_name}}'     => $contact['business_name'] ?? '',
            '{{contact_name}}'      => $contact['contact_name'] ?? '',
            '{{email}}'             => $contact['email'] ?? '',
            '{{phone}}'             => $contact['phone'] ?? '',
            '{{city}}'              => $contact['city'] ?? '',
            '{{rating}}'            => $contact['rating'] ?? '',
            '{{reviews_count}}'     => $contact['reviews_count'] ?? '',
            '{{website}}'           => $contact['website'] ?? '',
            '{{business_category}}' => $contact['business_category'] ?? '',
            '{{sender_name}}'       => $settings['smtp_from_name'] ?? 'Eduardo De Sul',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Retourner la liste des variables disponibles
     */
    public function getTemplateVars(): array
    {
        return $this->templateVars;
    }
}