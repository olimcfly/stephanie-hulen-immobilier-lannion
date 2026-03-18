<?php
/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ESTIMATION MAILER — includes/estimation_mailer.php
 * Envoi automatique d'emails lors d'une nouvelle demande d'estimation
 * ═══════════════════════════════════════════════════════════════════════════
 * 
 * Usage :
 *   require_once __DIR__ . '/includes/estimation_mailer.php';
 *   sendEstimationNotifications($pdo, $newEstimationId);
 * 
 * Envoie automatiquement :
 *   1. Email de confirmation au demandeur (template type 'confirmation')
 *   2. Alerte email à l'admin (template type 'rdv')
 * ═══════════════════════════════════════════════════════════════════════════
 */

/**
 * Envoie les notifications automatiques pour une nouvelle estimation
 * 
 * @param PDO $pdo          Instance PDO
 * @param int $estimationId ID de la nouvelle estimation
 * @return array             Résultat ['client' => bool, 'admin' => bool, 'errors' => []]
 */
function sendEstimationNotifications($pdo, $estimationId) {
    $result = ['client' => false, 'admin' => false, 'errors' => []];
    
    try {
        // 1. Charger les données de l'estimation
        $stmt = $pdo->prepare("SELECT * FROM estimations WHERE id = :id");
        $stmt->execute([':id' => (int)$estimationId]);
        $estimation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$estimation) {
            $result['errors'][] = "Estimation #$estimationId introuvable";
            return $result;
        }
        
        // 2. Préparer les variables de remplacement
        $variables = [
            'prenom'           => $estimation['prenom'] ?? '',
            'nom'              => $estimation['nom'] ?? '',
            'email'            => $estimation['email'] ?? '',
            'telephone'        => $estimation['telephone'] ?? '',
            'type_bien'        => ucfirst($estimation['type_bien'] ?? ''),
            'surface'          => $estimation['surface'] ?? '',
            'pieces'           => $estimation['pieces'] ?? '',
            'adresse'          => $estimation['adresse'] ?? '',
            'ville'            => $estimation['ville'] ?? '',
            'code_postal'      => $estimation['code_postal'] ?? '',
            'estimation_basse' => $estimation['estimation_basse'] 
                                  ? number_format((float)$estimation['estimation_basse'], 0, ',', ' ') 
                                  : '—',
            'estimation_haute' => $estimation['estimation_haute'] 
                                  ? number_format((float)$estimation['estimation_haute'], 0, ',', ' ') 
                                  : '—',
            'date_creation'    => $estimation['date_creation'] 
                                  ? date('d/m/Y', strtotime($estimation['date_creation'])) 
                                  : date('d/m/Y'),
        ];
        
        // 3. Récupérer l'email admin
        $adminEmail = getAdminEmail($pdo);
        
        // ───────────────────────────────────────────────
        // 4. EMAIL DE CONFIRMATION AU CLIENT
        // ───────────────────────────────────────────────
        if (!empty($estimation['email'])) {
            $tplClient = getActiveTemplate($pdo, 'confirmation');
            
            if ($tplClient) {
                $subject = replaceVariables($tplClient['subject'], $variables);
                $body = replaceVariables($tplClient['body'], $variables);
                
                $sent = sendHtmlEmail(
                    $estimation['email'], 
                    $subject, 
                    $body,
                    $pdo
                );
                
                $result['client'] = $sent;
                
                if ($sent) {
                    // Logger dans estimation_contacts
                    logEmailContact($pdo, $estimationId, $subject, $body, 'out');
                } else {
                    $result['errors'][] = "Échec envoi email client à " . $estimation['email'];
                }
            } else {
                $result['errors'][] = "Aucun template 'confirmation' actif trouvé";
            }
        } else {
            $result['errors'][] = "Pas d'email pour cette demande";
        }
        
        // ───────────────────────────────────────────────
        // 5. ALERTE EMAIL À L'ADMIN
        // ───────────────────────────────────────────────
        if (!empty($adminEmail)) {
            $tplAdmin = getActiveTemplate($pdo, 'rdv');
            
            if ($tplAdmin) {
                $subject = replaceVariables($tplAdmin['subject'], $variables);
                $body = replaceVariables($tplAdmin['body'], $variables);
                
                $sent = sendHtmlEmail(
                    $adminEmail, 
                    $subject, 
                    $body,
                    $pdo
                );
                
                $result['admin'] = $sent;
                
                if (!$sent) {
                    $result['errors'][] = "Échec envoi alerte admin à $adminEmail";
                }
            } else {
                $result['errors'][] = "Aucun template 'rdv' (alerte admin) actif trouvé";
            }
        } else {
            $result['errors'][] = "Email admin non configuré";
        }
        
    } catch (Exception $e) {
        $result['errors'][] = "Exception : " . $e->getMessage();
    }
    
    // Logger le résultat
    logNotificationResult($estimationId, $result);
    
    return $result;
}

/**
 * Récupère un template actif par type
 */
function getActiveTemplate($pdo, $type) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM estimation_templates WHERE type = :type AND status = 'actif' ORDER BY id ASC LIMIT 1");
        $stmt->execute([':type' => $type]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Remplace les {{variables}} dans un texte
 */
function replaceVariables($text, $variables) {
    foreach ($variables as $key => $value) {
        $text = str_replace('{{' . $key . '}}', $value, $text);
    }
    return $text;
}

/**
 * Récupère l'email admin depuis les settings
 */
function getAdminEmail($pdo) {
    $tables = ['admin_settings', 'ai_settings'];
    foreach ($tables as $table) {
        try {
            $check = $pdo->query("SHOW TABLES LIKE '$table'")->rowCount();
            if ($check > 0) {
                $stmt = $pdo->query("SELECT setting_value FROM $table WHERE setting_key IN ('admin_email', 'smtp_from_email', 'email_from') LIMIT 1");
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['setting_value'])) {
                    return $row['setting_value'];
                }
            }
        } catch (Exception $e) {
            continue;
        }
    }
    // Fallback
    return '';
}

/**
 * Envoie un email HTML via SMTP (EmailService) avec fallback mail()
 */
function sendHtmlEmail($to, $subject, $htmlBody, $pdo = null) {
    require_once __DIR__ . '/classes/EmailService.php';

    try {
        $service = new EmailService($pdo);
        $config = $service->getConfig();

        if (!empty($config['smtp_host'])) {
            $result = $service->sendEmail($to, $subject, $htmlBody);
            return $result['success'] ?? false;
        }
    } catch (Exception $e) {
        error_log("sendHtmlEmail SMTP error: " . $e->getMessage());
    }

    // Fallback : mail() natif
    $fromEmail = 'ne-pas-repondre@eduardo-desul-immobilier.fr';
    $fromName = 'Eduardo De Sul Immobilier';

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $fromName <$fromEmail>\r\n";
    $headers .= "Reply-To: $fromEmail\r\n";
    $headers .= "X-Mailer: EcosystemeImmo-CRM/1.0\r\n";

    return @mail($to, $subject, $htmlBody, $headers);
}

/**
 * Log un email envoyé dans estimation_contacts
 */
function logEmailContact($pdo, $estimationId, $subject, $body, $direction = 'out') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO estimation_contacts (request_id, contact_type, direction, subject, message, created_at) 
            VALUES (:rid, 'email', :dir, :subject, :message, NOW())
        ");
        $stmt->execute([
            ':rid'     => (int)$estimationId,
            ':dir'     => $direction,
            ':subject' => $subject,
            ':message' => $body,
        ]);
    } catch (Exception $e) {
        // Silencieux - ne pas bloquer l'envoi
        error_log("estimation_mailer: Erreur log contact - " . $e->getMessage());
    }
}

/**
 * Log le résultat des notifications dans error.log
 */
function logNotificationResult($estimationId, $result) {
    $logMsg = date('Y-m-d H:i:s') . " | Estimation #$estimationId | ";
    $logMsg .= "Client: " . ($result['client'] ? 'OK' : 'FAIL') . " | ";
    $logMsg .= "Admin: " . ($result['admin'] ? 'OK' : 'FAIL');
    if (!empty($result['errors'])) {
        $logMsg .= " | Erreurs: " . implode('; ', $result['errors']);
    }
    
    $logFile = dirname(__DIR__) . '/logs/estimation_emails.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    
    @file_put_contents($logFile, $logMsg . "\n", FILE_APPEND | LOCK_EX);
}