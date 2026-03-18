<?php
/**
 * EmailValidator - Validation d'emails par MX et SMTP
 * Module : admin/modules/gmb/EmailValidator.php
 * 
 * Méthodes :
 * - validate($email) : validation complète
 * - checkMX($domain) : vérification enregistrement MX
 * - checkSMTP($email, $mxHost) : vérification SMTP (RCPT TO)
 * - isDisposable($domain) : détection emails jetables
 * - isCatchAll($domain, $mxHost) : détection catch-all
 * - bulkValidate($emails) : validation en masse
 */

class EmailValidator
{
    private $db;
    private $timeout = 10;
    private $fromEmail = 'verify@ecosystemeimmo.fr';
    private $fromDomain = 'ecosystemeimmo.fr';
    
    // Domaines jetables connus
    private $disposableDomains = [
        'mailinator.com', 'guerrillamail.com', 'tempmail.com', 'throwaway.email',
        'yopmail.com', 'sharklasers.com', 'guerrillamailblock.com', 'grr.la',
        'dispostable.com', 'trashmail.com', 'mailnesia.com', 'maildrop.cc',
        'temp-mail.org', 'fakeinbox.com', 'tempail.com', 'tempr.email',
        'jetable.org', 'trashinbox.com', 'getairmail.com', 'mailcatch.com'
    ];
    
    // Providers connus (pas besoin de SMTP check)
    private $knownProviders = [
        'gmail.com' => 'Gmail',
        'yahoo.fr' => 'Yahoo',
        'yahoo.com' => 'Yahoo',
        'outlook.com' => 'Outlook',
        'outlook.fr' => 'Outlook',
        'hotmail.com' => 'Hotmail',
        'hotmail.fr' => 'Hotmail',
        'orange.fr' => 'Orange',
        'free.fr' => 'Free',
        'sfr.fr' => 'SFR',
        'laposte.net' => 'La Poste',
        'wanadoo.fr' => 'Wanadoo',
        'icloud.com' => 'iCloud',
        'protonmail.com' => 'ProtonMail',
    ];

    public function __construct($db)
    {
        $this->db = $db;
        
        // Charger email from settings si disponible
        try {
            $stmt = $this->db->prepare("SELECT setting_value FROM gmb_scraper_settings WHERE setting_key = ?");
            $stmt->execute(['smtp_from_email']);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && !empty($row['setting_value'])) {
                $this->fromEmail = $row['setting_value'];
                $this->fromDomain = substr($this->fromEmail, strpos($this->fromEmail, '@') + 1);
            }
        } catch (PDOException $e) {
            // Utiliser les valeurs par défaut
        }
    }

    /**
     * Validation complète d'un email
     */
    public function validate(string $email): array
    {
        $email = strtolower(trim($email));
        
        $result = [
            'email' => $email,
            'is_valid' => null,
            'status' => 'unknown',
            'method' => 'mx_check',
            'mx_found' => false,
            'smtp_connectable' => null,
            'is_catch_all' => null,
            'is_disposable' => false,
            'provider' => null,
            'message' => '',
        ];

        // 1. Vérifier le format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result['is_valid'] = false;
            $result['status'] = 'invalid';
            $result['message'] = 'Format email invalide';
            $this->saveValidation($result);
            return $result;
        }

        // 2. Vérifier le cache
        $cached = $this->getCached($email);
        if ($cached) {
            return $cached;
        }

        $domain = substr($email, strpos($email, '@') + 1);

        // 3. Provider connu ?
        if (isset($this->knownProviders[$domain])) {
            $result['provider'] = $this->knownProviders[$domain];
        }

        // 4. Domaine jetable ?
        if ($this->isDisposable($domain)) {
            $result['is_valid'] = false;
            $result['is_disposable'] = true;
            $result['status'] = 'disposable';
            $result['message'] = 'Email jetable détecté';
            $this->saveValidation($result);
            return $result;
        }

        // 5. Vérifier MX
        $mxHosts = $this->checkMX($domain);
        if (empty($mxHosts)) {
            $result['is_valid'] = false;
            $result['mx_found'] = false;
            $result['status'] = 'invalid';
            $result['message'] = 'Aucun enregistrement MX trouvé pour ' . $domain;
            $this->saveValidation($result);
            return $result;
        }

        $result['mx_found'] = true;
        $mxHost = $mxHosts[0]; // Premier MX (priorité la plus haute)

        // 6. Vérifier SMTP (RCPT TO)
        $smtpResult = $this->checkSMTP($email, $mxHost);
        $result['smtp_connectable'] = $smtpResult['connectable'];
        $result['method'] = 'smtp_verify';

        if ($smtpResult['connectable'] === false) {
            // Pas de connexion SMTP possible, on se fie au MX
            $result['is_valid'] = true; // MX ok = probablement valide
            $result['status'] = 'valid';
            $result['message'] = 'MX valide, SMTP non vérifiable';
        } elseif ($smtpResult['accepted']) {
            $result['is_valid'] = true;
            $result['status'] = 'valid';
            $result['message'] = 'Email vérifié par SMTP';
        } else {
            $result['is_valid'] = false;
            $result['status'] = 'invalid';
            $result['message'] = 'Email rejeté par le serveur SMTP';
        }

        // 7. Vérifier catch-all
        if ($result['is_valid']) {
            $catchAll = $this->isCatchAll($domain, $mxHost);
            $result['is_catch_all'] = $catchAll;
            if ($catchAll) {
                $result['status'] = 'catch_all';
                $result['message'] = 'Serveur catch-all (accepte tout)';
            }
        }

        // 8. Sauvegarder
        $this->saveValidation($result);

        return $result;
    }

    /**
     * Vérifier les enregistrements MX
     */
    public function checkMX(string $domain): array
    {
        $mxHosts = [];
        $mxWeights = [];
        
        if (getmxrr($domain, $mxHosts, $mxWeights)) {
            // Trier par poids (priorité)
            array_multisort($mxWeights, SORT_ASC, $mxHosts);
            return $mxHosts;
        }
        
        // Fallback : vérifier A record
        $ip = gethostbyname($domain);
        if ($ip !== $domain) {
            return [$domain];
        }
        
        return [];
    }

    /**
     * Vérification SMTP via RCPT TO
     */
    public function checkSMTP(string $email, string $mxHost): array
    {
        $result = [
            'connectable' => false,
            'accepted' => false,
            'response' => '',
        ];

        try {
            $socket = @fsockopen($mxHost, 25, $errno, $errstr, $this->timeout);
            
            if (!$socket) {
                // Essayer port 587
                $socket = @fsockopen($mxHost, 587, $errno, $errstr, $this->timeout);
            }
            
            if (!$socket) {
                return $result;
            }

            $result['connectable'] = true;
            stream_set_timeout($socket, $this->timeout);

            // Lire le banner
            $response = $this->readSMTP($socket);
            if (substr($response, 0, 3) !== '220') {
                fclose($socket);
                return $result;
            }

            // EHLO
            $this->writeSMTP($socket, "EHLO {$this->fromDomain}");
            $response = $this->readSMTP($socket);

            // MAIL FROM
            $this->writeSMTP($socket, "MAIL FROM:<{$this->fromEmail}>");
            $response = $this->readSMTP($socket);
            if (substr($response, 0, 3) !== '250') {
                $this->writeSMTP($socket, "QUIT");
                fclose($socket);
                return $result;
            }

            // RCPT TO (la vérification clé)
            $this->writeSMTP($socket, "RCPT TO:<{$email}>");
            $response = $this->readSMTP($socket);
            $result['response'] = $response;
            
            $code = substr($response, 0, 3);
            $result['accepted'] = ($code === '250' || $code === '251');

            // QUIT
            $this->writeSMTP($socket, "QUIT");
            fclose($socket);

        } catch (\Exception $e) {
            if (isset($socket) && is_resource($socket)) {
                fclose($socket);
            }
        }

        return $result;
    }

    /**
     * Vérifier si le domaine est catch-all
     */
    public function isCatchAll(string $domain, string $mxHost): bool
    {
        $fakeEmail = 'zzzz_test_fake_' . rand(100000, 999999) . '@' . $domain;
        $smtpResult = $this->checkSMTP($fakeEmail, $mxHost);
        return $smtpResult['accepted'];
    }

    /**
     * Vérifier si c'est un domaine jetable
     */
    public function isDisposable(string $domain): bool
    {
        return in_array(strtolower($domain), $this->disposableDomains);
    }

    /**
     * Validation en masse
     */
    public function bulkValidate(array $emails, callable $progressCallback = null): array
    {
        $results = [];
        $total = count($emails);
        
        foreach ($emails as $i => $email) {
            $results[] = $this->validate($email);
            
            if ($progressCallback) {
                $progressCallback($i + 1, $total, $email);
            }
            
            // Pause entre chaque validation pour ne pas se faire bloquer
            usleep(500000); // 0.5s
        }
        
        return $results;
    }

    /**
     * Valider et mettre à jour un contact dans gmb_contacts
     */
    public function validateContact(int $contactId): ?array
    {
        $stmt = $this->db->prepare("SELECT email FROM gmb_contacts WHERE id = ? AND email IS NOT NULL AND email != ''");
        $stmt->execute([$contactId]);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$contact) return null;
        
        $result = $this->validate($contact['email']);
        
        // Mettre à jour le contact
        $stmt = $this->db->prepare("
            UPDATE gmb_contacts 
            SET email_status = ?, email_validated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$result['status'], $contactId]);
        
        return $result;
    }

    /**
     * Valider tous les contacts avec email_status = 'unknown'
     */
    public function validateAllPending(int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT id, email FROM gmb_contacts 
            WHERE email IS NOT NULL AND email != '' AND email_status = 'unknown'
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $results = [];
        foreach ($contacts as $contact) {
            $result = $this->validate($contact['email']);
            
            $updateStmt = $this->db->prepare("
                UPDATE gmb_contacts 
                SET email_status = ?, email_validated_at = NOW() 
                WHERE id = ?
            ");
            $updateStmt->execute([$result['status'], $contact['id']]);
            
            $results[] = array_merge($result, ['contact_id' => $contact['id']]);
            
            usleep(500000);
        }
        
        return $results;
    }

    // ============================================================
    // PRIVATE HELPERS
    // ============================================================

    private function readSMTP($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') break;
        }
        return trim($response);
    }

    private function writeSMTP($socket, string $command): void
    {
        fwrite($socket, $command . "\r\n");
    }

    private function getCached(string $email): ?array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM gmb_email_validations 
                WHERE email = ? AND validated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                return [
                    'email' => $row['email'],
                    'is_valid' => (bool)$row['is_valid'],
                    'status' => $row['is_valid'] ? 'valid' : 'invalid',
                    'method' => $row['validation_method'],
                    'mx_found' => (bool)$row['mx_found'],
                    'smtp_connectable' => $row['smtp_connectable'] !== null ? (bool)$row['smtp_connectable'] : null,
                    'is_catch_all' => $row['is_catch_all'] !== null ? (bool)$row['is_catch_all'] : null,
                    'is_disposable' => (bool)$row['is_disposable'],
                    'provider' => $row['provider'],
                    'message' => 'Résultat en cache',
                    'cached' => true,
                ];
            }
        } catch (PDOException $e) {
            // Table n'existe peut-être pas encore
        }
        
        return null;
    }

    private function saveValidation(array $result): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO gmb_email_validations 
                (email, is_valid, validation_method, mx_found, smtp_connectable, is_catch_all, is_disposable, provider, validated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                    is_valid = VALUES(is_valid),
                    validation_method = VALUES(validation_method),
                    mx_found = VALUES(mx_found),
                    smtp_connectable = VALUES(smtp_connectable),
                    is_catch_all = VALUES(is_catch_all),
                    is_disposable = VALUES(is_disposable),
                    provider = VALUES(provider),
                    validated_at = NOW()
            ");
            $stmt->execute([
                $result['email'],
                $result['is_valid'] === null ? null : ($result['is_valid'] ? 1 : 0),
                $result['method'],
                $result['mx_found'] ? 1 : 0,
                $result['smtp_connectable'] === null ? null : ($result['smtp_connectable'] ? 1 : 0),
                $result['is_catch_all'] === null ? null : ($result['is_catch_all'] ? 1 : 0),
                $result['is_disposable'] ? 1 : 0,
                $result['provider'],
            ]);
        } catch (PDOException $e) {
            // Silently fail
        }
    }
}