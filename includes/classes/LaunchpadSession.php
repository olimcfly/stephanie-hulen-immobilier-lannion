<?php
/**
 * LaunchpadSession.php
 * Gère les sessions et données du launchpad
 */

class LaunchpadSession {
    
    private $pdo;
    private $session_id;
    private $user_id;
    
    public function __construct($pdo, $user_id = null) {
        $this->pdo = $pdo;
        $this->user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
        
        if (!$this->user_id) {
            throw new Exception('User ID required');
        }
    }
    
    /**
     * Crée une nouvelle session launchpad
     */
    public function createSession() {
        $this->session_id = $this->generateUUID();
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad_sessions 
                (id, user_id, status, current_step) 
                VALUES (?, ?, 'active', 1)
            ");
            
            $stmt->execute([$this->session_id, $this->user_id]);
            
            return [
                'success' => true,
                'session_id' => $this->session_id
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Charge une session existante
     */
    public function loadSession($session_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM launchpad_sessions 
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([$session_id, $this->user_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$session) {
                throw new Exception('Session not found or not authorized');
            }
            
            $this->session_id = $session_id;
            return $session;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Récupère toutes les données de l'étape 1
     */
    public function getStep1Data() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM launchpad_step1_profil 
                WHERE session_id = ?
            ");
            
            $stmt->execute([$this->session_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Sauvegarde les données de l'étape 1
     */
    public function saveStep1Data($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad_step1_profil 
                (session_id, metier, zone_geo, zone_rayon_km, experience_level, objectif_principal, secteurs_interets)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                metier = VALUES(metier),
                zone_geo = VALUES(zone_geo),
                zone_rayon_km = VALUES(zone_rayon_km),
                experience_level = VALUES(experience_level),
                objectif_principal = VALUES(objectif_principal),
                secteurs_interets = VALUES(secteurs_interets),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $this->session_id,
                $data['metier'],
                $data['zone_geo'],
                $data['zone_rayon_km'] ?? null,
                $data['experience_level'],
                $data['objectif_principal'],
                isset($data['secteurs_interets']) ? json_encode($data['secteurs_interets']) : null
            ]);
            
            $this->updateSessionStep(2);
            
            return [
                'success' => true,
                'next_step' => 2
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Récupère toutes les données de l'étape 2
     */
    public function getStep2Data() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM launchpad_step2_persona 
                WHERE session_id = ?
            ");
            
            $stmt->execute([$this->session_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            
            if ($data) {
                $data['freins'] = json_decode($data['freins'], true) ?? [];
                $data['desirs'] = json_decode($data['desirs'], true) ?? [];
                $data['declencheurs'] = json_decode($data['declencheurs'], true) ?? [];
                $data['persona_secondaires'] = json_decode($data['persona_secondaires'], true) ?? [];
            }
            
            return $data;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Sauvegarde les données de l'étape 2
     */
    public function saveStep2Data($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad_step2_persona 
                (session_id, persona_choisi, profondeur_conscience, freins, desirs, declencheurs, persona_secondaires, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                persona_choisi = VALUES(persona_choisi),
                profondeur_conscience = VALUES(profondeur_conscience),
                freins = VALUES(freins),
                desirs = VALUES(desirs),
                declencheurs = VALUES(declencheurs),
                persona_secondaires = VALUES(persona_secondaires),
                notes = VALUES(notes),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $this->session_id,
                $data['persona_choisi'],
                $data['profondeur_conscience'],
                isset($data['freins']) ? json_encode($data['freins']) : null,
                isset($data['desirs']) ? json_encode($data['desirs']) : null,
                isset($data['declencheurs']) ? json_encode($data['declencheurs']) : null,
                isset($data['persona_secondaires']) ? json_encode($data['persona_secondaires']) : null,
                $data['notes'] ?? null
            ]);
            
            $this->updateSessionStep(3);
            
            return [
                'success' => true,
                'next_step' => 3
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Récupère toutes les données de l'étape 3
     */
    public function getStep3Data() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM launchpad_step3_offre 
                WHERE session_id = ? 
                ORDER BY version DESC 
                LIMIT 1
            ");
            
            $stmt->execute([$this->session_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            
            if ($data) {
                $data['offre_principale'] = json_decode($data['offre_principale'], true) ?? [];
                $data['offres_complementaires'] = json_decode($data['offres_complementaires'], true) ?? [];
            }
            
            return $data;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Sauvegarde les données de l'étape 3
     */
    public function saveStep3Data($data) {
        try {
            // Récupérer la version courante
            $stmt = $this->pdo->prepare("
                SELECT MAX(version) as max_version 
                FROM launchpad_step3_offre 
                WHERE session_id = ?
            ");
            $stmt->execute([$this->session_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $next_version = ($result['max_version'] ?? 0) + 1;
            
            // Insérer les données
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad_step3_offre 
                (session_id, promesse, offre_principale, offres_complementaires, version, user_modifications)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $this->session_id,
                $data['promesse'],
                json_encode($data['offre_principale']),
                isset($data['offres_complementaires']) ? json_encode($data['offres_complementaires']) : null,
                $next_version,
                $data['user_modifications'] ?? null
            ]);
            
            $this->updateSessionStep(4);
            
            return [
                'success' => true,
                'next_step' => 4
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Récupère toutes les données de l'étape 4
     */
    public function getStep4Data() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM launchpad_step4_strategie 
                WHERE session_id = ?
            ");
            
            $stmt->execute([$this->session_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            
            if ($data) {
                $data['contenus_recommandes'] = json_decode($data['contenus_recommandes'], true) ?? [];
                $data['pages_a_creer'] = json_decode($data['pages_a_creer'], true) ?? [];
            }
            
            return $data;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Sauvegarde les données de l'étape 4
     */
    public function saveStep4Data($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad_step4_strategie 
                (session_id, trafic_channel, justification_canal, contenus_recommandes, pages_a_creer, budget_estimé)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                trafic_channel = VALUES(trafic_channel),
                justification_canal = VALUES(justification_canal),
                contenus_recommandes = VALUES(contenus_recommandes),
                pages_a_creer = VALUES(pages_a_creer),
                budget_estimé = VALUES(budget_estimé),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $this->session_id,
                $data['trafic_channel'],
                $data['justification_canal'] ?? null,
                isset($data['contenus_recommandes']) ? json_encode($data['contenus_recommandes']) : null,
                isset($data['pages_a_creer']) ? json_encode($data['pages_a_creer']) : null,
                $data['budget_estimé'] ?? null
            ]);
            
            $this->updateSessionStep(5);
            
            return [
                'success' => true,
                'next_step' => 5
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Récupère toutes les données de l'étape 5
     */
    public function getStep5Data() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM launchpad_step5_plan 
                WHERE session_id = ?
            ");
            
            $stmt->execute([$this->session_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            
            if ($data) {
                $data['cahier_strategique'] = json_decode($data['cahier_strategique'], true) ?? [];
            }
            
            return $data;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Sauvegarde les données de l'étape 5
     */
    public function saveStep5Data($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO launchpad_step5_plan 
                (session_id, cahier_strategique, pdf_url, next_action_concrete, next_action_date)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                cahier_strategique = VALUES(cahier_strategique),
                pdf_url = VALUES(pdf_url),
                next_action_concrete = VALUES(next_action_concrete),
                next_action_date = VALUES(next_action_date),
                updated_at = NOW()
            ");
            
            $stmt->execute([
                $this->session_id,
                json_encode($data['cahier_strategique']),
                $data['pdf_url'] ?? null,
                $data['next_action_concrete'] ?? null,
                $data['next_action_date'] ?? null
            ]);
            
            $this->completeSession();
            
            return [
                'success' => true,
                'status' => 'completed'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Met à jour l'étape actuelle
     */
    private function updateSessionStep($step) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE launchpad_sessions 
                SET current_step = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$step, $this->session_id]);
        } catch (Exception $e) {
            // Silent fail
        }
    }
    
    /**
     * Marque la session comme complétée
     */
    private function completeSession() {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE launchpad_sessions 
                SET status = 'completed', current_step = 5, completed_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$this->session_id]);
        } catch (Exception $e) {
            // Silent fail
        }
    }
    
    /**
     * Récupère la session actuelle
     */
    public function getSession() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM launchpad_sessions 
                WHERE id = ?
            ");
            $stmt->execute([$this->session_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Génère un UUID v4
     */
    private function generateUUID() {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    public function getSessionId() {
        return $this->session_id;
    }
}