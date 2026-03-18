<?php
/**
 * /admin/core/ModuleRegistry.php
 * Registre des modules (auto-détection)
 *
 * Philosophie : ne touche pas aux modules, juste les "décrit".
 */

class ModuleRegistry
{
    private string $modulesPath;

    public function __construct(string $modulesPath)
    {
        $this->modulesPath = rtrim($modulesPath, '/');
    }

    /**
     * Retourne une liste normalisée des modules détectés.
     * - Ne charge rien, ne require rien.
     */
    public function listModules(): array
    {
        if (!is_dir($this->modulesPath)) {
            return [];
        }

        $items = scandir($this->modulesPath);
        if ($items === false) {
            return [];
        }

        $modules = [];

        foreach ($items as $name) {
            if ($name === '.' || $name === '..') continue;

            $path = $this->modulesPath . '/' . $name;
            if (!is_dir($path)) continue;

            // Ignorer dossiers cachés
            if (str_starts_with($name, '.')) continue;

            $module = $this->describeModule($name, $path);
            $modules[$name] = $module;
        }

        ksort($modules);
        return $modules;
    }

    public function get(string $moduleName): ?array
    {
        $all = $this->listModules();
        return $all[$moduleName] ?? null;
    }

    private function describeModule(string $name, string $path): array
    {
        $indexPhp = $path . '/index.php';
        $apiDir   = $path . '/api';
        $assetsDir= $path . '/assets';

        return [
            'name'        => $name,
            'path'        => $path,
            'has_index'   => is_file($indexPhp),
            'has_api'     => is_dir($apiDir),
            'has_assets'  => is_dir($assetsDir),
            'sql_files'   => $this->findSqlFiles($path),
            'detected_at' => date('c'),
        ];
    }

    /**
     * Retourne tous les *.sql sous le module (max profondeur raisonnable).
     * NB: on reste simple, pas de dépendance externe.
     */
    private function findSqlFiles(string $modulePath): array
    {
        $results = [];

        $rii = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($modulePath, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($rii as $file) {
            /** @var SplFileInfo $file */
            if (!$file->isFile()) continue;

            $filename = $file->getFilename();
            if (!str_ends_with(strtolower($filename), '.sql')) continue;

            $results[] = $file->getPathname();
        }

        sort($results);
        return $results;
    }
}