<?php
/**
 * Migration : Chiffrement des données PII existantes dans la table leads
 *
 * Usage CLI : php setup/migrate-encrypt-leads.php
 * Usage web : setup/migrate-encrypt-leads.php?confirm=yes (admin requis)
 *
 * Cette migration :
 * 1. Ajoute la colonne email_hash si absente
 * 2. Chiffre les colonnes email et phone existantes
 * 3. Génère les hash pour la recherche par email
 */

// Sécurité : CLI ou admin connecté
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    require_once dirname(__DIR__) . '/includes/autoload.php';
    if (empty($_SESSION['admin_id'])) {
        http_response_code(403);
        die('Accès refusé — session admin requise');
    }
    if (($_GET['confirm'] ?? '') !== 'yes') {
        die('Ajoutez ?confirm=yes pour lancer la migration');
    }
} else {
    require_once dirname(__DIR__) . '/config/config.php';
}

$pdo = getDB();

function output(string $msg, bool $isCli): void {
    if ($isCli) {
        echo $msg . PHP_EOL;
    } else {
        echo htmlspecialchars($msg) . "<br>\n";
        flush();
    }
}

// Vérifier que l'extension sodium est disponible
if (!function_exists('sodium_crypto_secretbox')) {
    output('ERREUR : Extension sodium non disponible', $isCli);
    exit(1);
}

// Charger la classe Encryption
require_once dirname(__DIR__) . '/includes/classes/Encryption.php';

try {
    $encryption = Encryption::getInstance();
} catch (RuntimeException $e) {
    output('ERREUR : ' . $e->getMessage(), $isCli);
    output('Assurez-vous que ENCRYPTION_KEY est définie dans .env', $isCli);
    output('Générer une clé : php -r "echo sodium_bin2hex(sodium_crypto_secretbox_keygen());"', $isCli);
    exit(1);
}

output('=== Migration : Chiffrement des données PII ===', $isCli);

// Étape 1 : Ajouter la colonne email_hash si absente
try {
    $cols = $pdo->query("SHOW COLUMNS FROM leads LIKE 'email_hash'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE leads ADD COLUMN email_hash VARCHAR(64) DEFAULT NULL AFTER phone");
        $pdo->exec("CREATE INDEX idx_leads_email_hash ON leads (email_hash)");
        output('Colonne email_hash ajoutée avec index', $isCli);
    } else {
        output('Colonne email_hash déjà présente', $isCli);
    }
} catch (PDOException $e) {
    output('Erreur ajout colonne : ' . $e->getMessage(), $isCli);
    exit(1);
}

// Étape 2 : Chiffrer les données existantes
$stmt = $pdo->query("SELECT id, email, phone, email_hash FROM leads");
$leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($leads);
$encrypted = 0;
$skipped = 0;
$errors = 0;

output("Traitement de {$total} leads...", $isCli);

foreach ($leads as $lead) {
    // Si email_hash existe déjà, la ligne est probablement déjà chiffrée
    if (!empty($lead['email_hash'])) {
        $skipped++;
        continue;
    }

    $email = $lead['email'];
    $phone = $lead['phone'];

    // Vérifier que l'email n'est pas déjà chiffré (base64 avec longueur > email normal)
    if ($email && base64_decode($email, true) !== false) {
        $decoded = base64_decode($email, true);
        $nonceSize = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;
        if (mb_strlen($decoded, '8bit') >= $nonceSize + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            // Probablement déjà chiffré, juste ajouter le hash
            // On ne peut pas hasher sans l'email en clair, on skip
            $skipped++;
            continue;
        }
    }

    try {
        $emailHash = $email ? $encryption->hash($email) : null;
        $encEmail = $encryption->encrypt($email);
        $encPhone = $encryption->encrypt($phone);

        $update = $pdo->prepare("UPDATE leads SET email = ?, phone = ?, email_hash = ? WHERE id = ?");
        $update->execute([$encEmail, $encPhone, $emailHash, $lead['id']]);
        $encrypted++;

        if ($encrypted % 100 === 0) {
            output("  ... {$encrypted}/{$total} chiffrés", $isCli);
        }
    } catch (Exception $e) {
        $errors++;
        output("  Erreur lead #{$lead['id']} : " . $e->getMessage(), $isCli);
    }
}

output('', $isCli);
output('=== Résultat ===', $isCli);
output("Total leads     : {$total}", $isCli);
output("Chiffrés        : {$encrypted}", $isCli);
output("Déjà chiffrés   : {$skipped}", $isCli);
output("Erreurs         : {$errors}", $isCli);
output('', $isCli);
output('Migration terminée.', $isCli);
