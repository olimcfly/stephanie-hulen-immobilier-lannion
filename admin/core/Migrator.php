<?php
/**
 * /admin/core/Migrator.php
 * Migrations SQL ultra simples (install/update client)
 *
 * - Applique chaque fichier SQL une seule fois.
 * - Stocke l'historique dans schema_migrations.
 */

class Migrator
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->ensureMigrationsTable();
    }

    public function ensureMigrationsTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS schema_migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration_key VARCHAR(255) NOT NULL UNIQUE,
                applied_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $this->pdo->exec($sql);
    }

    public function has(string $migrationKey): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM schema_migrations WHERE migration_key = ? LIMIT 1");
        $stmt->execute([$migrationKey]);
        return (bool) $stmt->fetchColumn();
    }

    public function mark(string $migrationKey): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO schema_migrations (migration_key, applied_at) VALUES (?, NOW())");
        $stmt->execute([$migrationKey]);
    }

    /**
     * Applique un fichier .sql si pas encore appliqué.
     * $migrationKey doit être stable (ex: module:filename.sql hash ou chemin relatif).
     */
    public function applyFile(string $sqlFilePath, string $migrationKey): array
    {
        if (!is_file($sqlFilePath)) {
            return ['ok' => false, 'skipped' => false, 'message' => "SQL introuvable: $sqlFilePath"];
        }

        if ($this->has($migrationKey)) {
            return ['ok' => true, 'skipped' => true, 'message' => "Déjà appliqué: $migrationKey"];
        }

        $sql = file_get_contents($sqlFilePath);
        if ($sql === false || trim($sql) === '') {
            // On le marque quand même pour éviter boucle infinie sur fichier vide
            $this->mark($migrationKey);
            return ['ok' => true, 'skipped' => false, 'message' => "Fichier vide, marqué appliqué: $migrationKey"];
        }

        try {
            $this->pdo->beginTransaction();

            // Simple : on exécute le contenu tel quel.
            // Attention : si ton SQL contient plusieurs statements, PDO->exec les supporte généralement.
            $this->pdo->exec($sql);

            $this->mark($migrationKey);
            $this->pdo->commit();

            return ['ok' => true, 'skipped' => false, 'message' => "Appliqué: $migrationKey"];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'skipped' => false, 'message' => "Erreur migration $migrationKey: " . $e->getMessage()];
        }
    }

    /**
     * Applique une liste de fichiers SQL (dans l'ordre).
     * $keyPrefix permet d'éviter les collisions.
     */
    public function applyMany(array $sqlFiles, string $keyPrefix = ''): array
    {
        $results = [];

        foreach ($sqlFiles as $file) {
            $key = $keyPrefix . $this->makeKey($file);
            $results[] = $this->applyFile($file, $key);
        }

        return $results;
    }

    private function makeKey(string $sqlFilePath): string
    {
        // Clé stable : hash du chemin complet (OK en install client)
        // Si tu veux mieux : utilise un chemin relatif modules/... + basename
        return 'sql:' . sha1($sqlFilePath);
    }
}