<?php
/**
 * ModuleDiagnostic.php
 * Diagnostic complet des modules ÉCOSYSTÈME IMMO LOCAL+
 */

class ModuleDiagnostic
{
    private PDO $db;
    private string $modulesBasePath;

    // Catalogue des modules connus
    private array $catalog = [
        'crm/contacts'      => ['label' => 'Contacts',        'category' => 'CRM',        'icon' => 'fas fa-address-book', 'tables' => ['leads']],
        'crm/mandats'       => ['label' => 'Mandats',         'category' => 'CRM',        'icon' => 'fas fa-file-signature','tables' => []],
        'cms/articles'      => ['label' => 'Articles',        'category' => 'CMS',        'icon' => 'fas fa-newspaper',    'tables' => ['articles']],
        'cms/builder'       => ['label' => 'Builder Pro',     'category' => 'CMS',        'icon' => 'fas fa-tools',        'tables' => ['builder_pages','builder_sections','builder_templates']],
        'cms/secteurs'      => ['label' => 'Secteurs',        'category' => 'CMS',        'icon' => 'fas fa-map-marked-alt','tables' => ['secteurs']],
        'immo/properties'   => ['label' => 'Biens',           'category' => 'Immobilier', 'icon' => 'fas fa-home',         'tables' => ['properties']],
        'marketing/captures'=> ['label' => 'Captures',        'category' => 'Marketing',  'icon' => 'fas fa-magnet',       'tables' => ['capture_pages']],
        'marketing/campaigns'=> ['label' => 'Campagnes',      'category' => 'Marketing',  'icon' => 'fas fa-bullhorn',     'tables' => []],
        'seo/local'         => ['label' => 'SEO Local',       'category' => 'SEO',        'icon' => 'fas fa-search-location','tables' => []],
        'ia/content'        => ['label' => 'Contenu IA',      'category' => 'IA',         'icon' => 'fas fa-brain',        'tables' => ['api_keys']],
        'system/settings'   => ['label' => 'Paramètres',      'category' => 'Système',    'icon' => 'fas fa-cog',          'tables' => ['settings','admins']],
        'system/modules'    => ['label' => 'Modules',         'category' => 'Système',    'icon' => 'fas fa-puzzle-piece', 'tables' => []],
        'network/gmb'       => ['label' => 'GMB Scraper',     'category' => 'Network',    'icon' => 'fab fa-google',       'tables' => ['gmb_contacts','gmb_sequences']],
    ];

    public function __construct(PDO $db, string $modulesBasePath)
    {
        $this->db              = $db;
        $this->modulesBasePath = rtrim($modulesBasePath, '/');
    }

    public function runFullDiagnostic(): array
    {
        $existingTables = $this->getExistingTables();
        $modules        = [];

        foreach ($this->catalog as $slug => $def) {
            $checks = [];
            $path   = $this->modulesBasePath . '/' . $slug;

            // Check dossier
            if (is_dir($path)) {
                $checks[] = ['type' => 'dir', 'status' => 'ok',    'message' => "Dossier `{$slug}` présent"];
            } else {
                $checks[] = ['type' => 'dir', 'status' => 'error', 'message' => "Dossier `{$slug}` MANQUANT"];
            }

            // Check fichier principal
            $mainFile = $path . '/' . basename($slug) . '.php';
            // Essai alternatif : index.php
            if (!file_exists($mainFile)) {
                $mainFile = $path . '/index.php';
            }
            if (file_exists($mainFile)) {
                $size     = filesize($mainFile);
                $checks[] = ['type' => 'file', 'status' => $size > 0 ? 'ok' : 'warning',
                             'message' => $size > 0 ? "Fichier principal présent ({$size} o)" : "Fichier principal vide (0 o)"];
            } else {
                $checks[] = ['type' => 'file', 'status' => 'warning', 'message' => "Fichier principal introuvable"];
            }

            // Check tables DB
            foreach ($def['tables'] as $table) {
                if (in_array($table, $existingTables, true)) {
                    $checks[] = ['type' => 'table', 'status' => 'ok',    'message' => "Table `{$table}` présente"];
                } else {
                    $checks[] = ['type' => 'table', 'status' => 'error', 'message' => "Table `{$table}` ABSENTE"];
                }
            }

            // Statut global
            $hasError   = (bool) array_filter($checks, fn($c) => $c['status'] === 'error');
            $hasWarning = (bool) array_filter($checks, fn($c) => $c['status'] === 'warning');
            $status     = $hasError ? 'error' : ($hasWarning ? 'warning' : 'ok');

            // Comptage fichiers du dossier
            $fileCount = 0;
            if (is_dir($path)) {
                $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS));
                foreach ($it as $f) { if ($f->isFile()) $fileCount++; }
            }

            $modules[$slug] = [
                'label'      => $def['label'],
                'category'   => $def['category'],
                'icon'       => $def['icon'],
                'status'     => $status,
                'checks'     => $checks,
                'file_count' => $fileCount,
            ];
        }

        // Modules détectés sur disque mais hors catalogue
        $this->scanUncataloged($modules);

        // Résumé
        $ok   = count(array_filter($modules, fn($m) => $m['status'] === 'ok'));
        $warn = count(array_filter($modules, fn($m) => $m['status'] === 'warning'));
        $err  = count(array_filter($modules, fn($m) => $m['status'] === 'error'));

        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'summary'   => ['total' => count($modules), 'ok' => $ok, 'warning' => $warn, 'error' => $err],
            'db_health' => $this->checkDbHealth($existingTables),
            'modules'   => $modules,
        ];
    }

    // ── Privées ───────────────────────────────────────────────────

    private function getExistingTables(): array
    {
        try {
            return $this->db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    private function checkDbHealth(array $existingTables): array
    {
        $coreTables = ['leads','builder_pages','builder_sections','builder_templates',
                       'properties','capture_pages','articles','secteurs',
                       'settings','admins','api_keys','gmb_contacts','gmb_sequences'];
        $checks = [];

        // Connexion DB
        $checks[] = ['check' => 'Connexion MySQL', 'status' => 'ok', 'value' => 'PDO connecté'];

        // Chaque table core
        foreach ($coreTables as $t) {
            if (in_array($t, $existingTables, true)) {
                try {
                    $rows = (int) $this->db->query("SELECT COUNT(*) FROM `{$t}`")->fetchColumn();
                    $checks[] = ['check' => "Table `{$t}`", 'status' => 'ok', 'value' => "{$rows} ligne(s)"];
                } catch (Exception $e) {
                    $checks[] = ['check' => "Table `{$t}`", 'status' => 'warning', 'value' => 'Lecture impossible'];
                }
            } else {
                $checks[] = ['check' => "Table `{$t}`", 'status' => 'error', 'value' => 'ABSENTE'];
            }
        }

        return $checks;
    }

    private function scanUncataloged(array &$modules): void
    {
        $knownSlugs = array_keys($this->catalog);
        $depth1     = glob($this->modulesBasePath . '/*/*', GLOB_ONLYDIR) ?: [];

        foreach ($depth1 as $fullPath) {
            $rel = str_replace($this->modulesBasePath . '/', '', $fullPath);
            if (in_array($rel, $knownSlugs, true)) continue;
            if (str_contains($rel, 'diagnostic')) continue;

            $parts    = explode('/', $rel);
            $category = ucfirst($parts[0] ?? 'Autre');
            $label    = ucfirst($parts[1] ?? $rel);
            $fileCount = 0;
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPath, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($it as $f) { if ($f->isFile()) $fileCount++; }

            $modules[$rel] = [
                'label'      => $label . ' *',
                'category'   => 'Non référencé',
                'icon'       => 'fas fa-question-circle',
                'status'     => 'warning',
                'checks'     => [['type' => 'catalog', 'status' => 'warning', 'message' => "Module hors catalogue détecté : {$rel}"]],
                'file_count' => $fileCount,
            ];
        }
    }
}