<?php
/**
 * Encryption Class
 * /includes/classes/Encryption.php
 *
 * Chiffrement/déchiffrement des données PII via sodium_crypto_secretbox
 */

class Encryption
{
    private static ?self $instance = null;
    private string $key;

    private function __construct()
    {
        $keyHex = defined('ENCRYPTION_KEY') ? ENCRYPTION_KEY : ($_ENV['ENCRYPTION_KEY'] ?? '');

        if (empty($keyHex)) {
            throw new RuntimeException('ENCRYPTION_KEY non définie. Ajoutez-la dans .env ou config.php');
        }

        $key = sodium_hex2bin($keyHex);

        if (mb_strlen($key, '8bit') !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('ENCRYPTION_KEY invalide : doit être ' . (SODIUM_CRYPTO_SECRETBOX_KEYBYTES * 2) . ' caractères hex');
        }

        $this->key = $key;
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Chiffrer une donnée
     * @return string Base64(nonce + ciphertext)
     */
    public function encrypt(?string $data): ?string
    {
        if ($data === null || $data === '') {
            return $data;
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($data, $nonce, $this->key);

        return base64_encode($nonce . $ciphertext);
    }

    /**
     * Déchiffrer une donnée
     */
    public function decrypt(?string $encrypted): ?string
    {
        if ($encrypted === null || $encrypted === '') {
            return $encrypted;
        }

        $decoded = base64_decode($encrypted, true);
        if ($decoded === false) {
            // Donnée non chiffrée (legacy), retourner telle quelle
            return $encrypted;
        }

        $nonceSize = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

        if (mb_strlen($decoded, '8bit') < $nonceSize + SODIUM_CRYPTO_SECRETBOX_MACBYTES) {
            // Trop court pour être chiffré, retourner tel quel (legacy)
            return $encrypted;
        }

        $nonce = mb_substr($decoded, 0, $nonceSize, '8bit');
        $ciphertext = mb_substr($decoded, $nonceSize, null, '8bit');

        $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $this->key);

        if ($plaintext === false) {
            // Échec du déchiffrement — donnée legacy non chiffrée
            return $encrypted;
        }

        return $plaintext;
    }

    /**
     * Générer un hash déterministe pour lookup (recherche par email)
     */
    public function hash(string $data): string
    {
        return hash('sha256', strtolower(trim($data)));
    }

    /**
     * Générer une nouvelle clé de chiffrement (helper pour setup)
     */
    public static function generateKey(): string
    {
        return sodium_bin2hex(sodium_crypto_secretbox_keygen());
    }
}
