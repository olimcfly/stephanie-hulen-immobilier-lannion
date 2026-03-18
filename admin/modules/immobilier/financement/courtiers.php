<?php
/**
 * API Courtiers Partenaires
 * Gestion des courtiers pour le module financement
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../../../config/config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getCourtiers($pdo);
        break;
    case 'POST':
        saveCourtier($pdo);
        break;
    case 'DELETE':
        deleteCourtier($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}

/**
 * Récupérer tous les courtiers
 */
function getCourtiers($pdo) {
    try {
        // Récupérer un courtier spécifique si ID fourni
        if (!empty($_GET['id'])) {
            $sql = "SELECT * FROM financement_courtiers WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $_GET['id']]);
            $courtier = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'courtier' => $courtier
            ]);
            return;
        }
        
        // Récupérer tous les courtiers avec stats
        $sql = "SELECT c.*, 
                (SELECT COUNT(*) FROM financement_leads WHERE courtier_id = c.id) as nb_leads,
                (SELECT COUNT(*) FROM financement_leads WHERE courtier_id = c.id AND (statut = 'finance' OR statut = 'commission_percue')) as nb_finances,
                (SELECT COALESCE(SUM(commission_montant), 0) FROM financement_leads WHERE courtier_id = c.id AND (statut = 'finance' OR statut = 'commission_percue')) as total_commissions
                FROM financement_courtiers c 
                ORDER BY c.nom ASC";
        
        $stmt = $pdo->query($sql);
        $courtiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'courtiers' => $courtiers
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la récupération des courtiers: ' . $e->getMessage()
        ]);
    }
}

/**
 * Créer ou mettre à jour un courtier
 */
function saveCourtier($pdo) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Données invalides']);
        return;
    }
    
    // Validation
    if (empty($data['nom'])) {
        echo json_encode(['success' => false, 'message' => 'Le nom du courtier est requis']);
        return;
    }
    
    try {
        if (!empty($data['id'])) {
            // Mise à jour
            $sql = "UPDATE financement_courtiers SET 
                    nom = :nom,
                    contact_nom = :contact_nom,
                    email = :email,
                    telephone = :telephone,
                    adresse = :adresse,
                    taux_commission = :taux_commission,
                    notes = :notes,
                    updated_at = NOW()
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $data['id'],
                ':nom' => $data['nom'],
                ':contact_nom' => $data['contact_nom'] ?? null,
                ':email' => $data['email'] ?? null,
                ':telephone' => $data['telephone'] ?? null,
                ':adresse' => $data['adresse'] ?? null,
                ':taux_commission' => $data['taux_commission'] ?? 1,
                ':notes' => $data['notes'] ?? ''
            ]);
            
            $courtierId = $data['id'];
            $message = 'Courtier mis à jour avec succès';
        } else {
            // Création
            $sql = "INSERT INTO financement_courtiers 
                    (nom, contact_nom, email, telephone, adresse, taux_commission, notes, actif, created_at, updated_at) 
                    VALUES 
                    (:nom, :contact_nom, :email, :telephone, :adresse, :taux_commission, :notes, 1, NOW(), NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $data['nom'],
                ':contact_nom' => $data['contact_nom'] ?? null,
                ':email' => $data['email'] ?? null,
                ':telephone' => $data['telephone'] ?? null,
                ':adresse' => $data['adresse'] ?? null,
                ':taux_commission' => $data['taux_commission'] ?? 1,
                ':notes' => $data['notes'] ?? ''
            ]);
            
            $courtierId = $pdo->lastInsertId();
            $message = 'Courtier créé avec succès';
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'id' => $courtierId
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()
        ]);
    }
}

/**
 * Supprimer un courtier
 */
function deleteCourtier($pdo) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID requis']);
        return;
    }
    
    try {
        // Vérifier si des leads sont liés
        $sql = "SELECT COUNT(*) as count FROM financement_leads WHERE courtier_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            // Désactiver au lieu de supprimer si des leads sont liés
            $sql = "UPDATE financement_courtiers SET actif = 0, updated_at = NOW() WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Courtier désactivé (leads liés existants)'
            ]);
            return;
        }
        
        // Suppression si aucun lead lié
        $sql = "DELETE FROM financement_courtiers WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Courtier supprimé avec succès'
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
        ]);
    }
}