<?php
/**
 * /front/api/estimation-submit.php
 * Endpoint public pour soumettre une demande d'estimation
 * Insère dans la table `estimations` et déclenche les notifications email
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Seul POST autorisé
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Config & DB
require_once dirname(__DIR__, 2) . '/config/config.php';

// Vérification CSRF
$csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token de sécurité invalide. Veuillez recharger la page.']);
    exit;
}

// Récupérer et valider les données
$nom       = sanitize($_POST['nom'] ?? '');
$prenom    = sanitize($_POST['prenom'] ?? '');
$email     = sanitize($_POST['email'] ?? '', 'email');
$telephone = sanitize($_POST['telephone'] ?? '');
$adresse   = sanitize($_POST['adresse'] ?? '');
$ville     = sanitize($_POST['ville'] ?? 'Lannion');
$codePostal = sanitize($_POST['code_postal'] ?? '');
$surface   = (float)($_POST['surface'] ?? 0);
$pieces    = (int)($_POST['pieces'] ?? 0);
$typeBien  = sanitize($_POST['type_bien'] ?? '');
$etatBien  = sanitize($_POST['etat_bien'] ?? '');
$consent   = isset($_POST['rgpd_consent']) ? 1 : 0;

// Validation
$errors = [];

if (empty($nom)) {
    $errors[] = 'Le nom est obligatoire';
}
if (empty($email) || !isValidEmail($email)) {
    $errors[] = 'Une adresse email valide est obligatoire';
}
if (empty($telephone)) {
    $errors[] = 'Le numéro de téléphone est obligatoire';
}
if (empty($adresse)) {
    $errors[] = 'L\'adresse du bien est obligatoire';
}
if ($surface <= 0) {
    $errors[] = 'La surface doit être supérieure à 0';
}
if ($pieces <= 0) {
    $errors[] = 'Le nombre de pièces doit être supérieur à 0';
}
if (empty($typeBien)) {
    $errors[] = 'Le type de bien est obligatoire';
}
if (!$consent) {
    $errors[] = 'Vous devez accepter la politique de confidentialité';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => implode('. ', $errors), 'errors' => $errors]);
    exit;
}

// Connexion BDD
try {
    $pdo = getDB();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
    exit;
}

// Insertion dans la table estimations
try {
    $stmt = $pdo->prepare("
        INSERT INTO estimations (
            nom, prenom, email, telephone, adresse, ville, code_postal,
            surface, pieces, type_bien, etat_bien, statut, rgpd_consent, created_at
        ) VALUES (
            :nom, :prenom, :email, :telephone, :adresse, :ville, :code_postal,
            :surface, :pieces, :type_bien, :etat_bien, 'en_attente', :consent, NOW()
        )
    ");

    $stmt->execute([
        ':nom'         => $nom,
        ':prenom'      => $prenom,
        ':email'       => $email,
        ':telephone'   => $telephone,
        ':adresse'     => $adresse,
        ':ville'       => $ville,
        ':code_postal' => $codePostal,
        ':surface'     => $surface,
        ':pieces'      => $pieces,
        ':type_bien'   => $typeBien,
        ':etat_bien'   => $etatBien,
        ':consent'     => $consent,
    ]);

    $estimationId = $pdo->lastInsertId();

    // Envoyer les notifications email (confirmation client + alerte admin)
    $mailerPath = dirname(__DIR__, 2) . '/includes/estimation_mailer.php';
    if (file_exists($mailerPath)) {
        require_once $mailerPath;
        sendEstimationNotifications($pdo, $estimationId);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Votre demande d\'estimation a bien été enregistrée. Nous vous recontacterons rapidement.',
        'estimation_id' => $estimationId,
    ]);

} catch (PDOException $e) {
    error_log('Estimation submit error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.']);
}
