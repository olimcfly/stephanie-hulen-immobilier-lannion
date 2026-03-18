<?php
/**
 * ══════════════════════════════════════════════════════════════
 * DiagnosticEngine — IMMO LOCAL+
 * /admin/includes/DiagnosticEngine.php
 *
 * Moteur central de diagnostic technique par module.
 * Utilisé par :
 *   - Chaque module (onglet Diagnostic intégré)
 *   - La page globale ?page=diagnostic
 * ══════════════════════════════════════════════════════════════
 */

class DiagnosticEngine
{
    private PDO $pdo;

    // ─────────────────────────────────────────────────────────
    // DÉFINITIONS PAR MODULE
    // Chaque module déclare ses tables, colonnes et règles métier
    // ─────────────────────────────────────────────────────────
    private static array $modules = [

        'articles' => [
            'label'   => 'Blog / Articles',
            'icon'    => 'fa-pen-fancy',
            'color'   => '#f59e0b',
            'page'    => 'articles',
            'tables'  => [
                [
                    'name'      => 'articles',
                    'alt'       => 'blog_articles',
                    'label'     => 'Articles',
                    'required'  => ['id', 'slug', 'status', 'created_at'],
                    'optional'  => ['focus_keyword', 'seo_score', 'semantic_score', 'word_count',
                                    'google_indexed', 'category', 'is_featured', 'meta_title', 'meta_description'],
                    'rules'     => [
                        ['type' => 'empty_col',  'col' => 'focus_keyword',  'label' => 'Articles publiés sans mot-clé SEO',         'filter' => ['status' => 'published']],
                        ['type' => 'low_int',    'col' => 'word_count',     'label' => 'Articles trop courts (< 400 mots)',          'threshold' => 400, 'filter' => ['status' => 'published']],
                        ['type' => 'zero_int',   'col' => 'seo_score',      'label' => 'Score SEO non calculé',                     'filter' => ['status' => 'published']],
                        ['type' => 'low_int',    'col' => 'seo_score',      'label' => 'Score SEO faible (< 40%)',                   'threshold' => 40,  'min' => 1, 'filter' => ['status' => 'published']],
                        ['type' => 'low_int',    'col' => 'semantic_score', 'label' => 'Score sémantique faible (< 30%)',            'threshold' => 30,  'min' => 1, 'filter' => ['status' => 'published']],
                        ['type' => 'col_value',  'col' => 'google_indexed', 'label' => 'Publiés non indexés Google',                'values' => ['no','unknown'], 'filter' => ['status' => 'published']],
                    ],
                    'count_by'  => 'status',
                ],
            ],
        ],

        'annuaire' => [
            'label'   => 'Annuaire Local',
            'icon'    => 'fa-book-open',
            'color'   => '#10b981',
            'page'    => 'annuaire',
            'tables'  => [
                [
                    'name'      => 'annuaire',
                    'label'     => 'Annuaire',
                    'required'  => ['id', 'nom', 'slug', 'categorie', 'status'],
                    'optional'  => ['adresse', 'ville', 'telephone', 'site_web', 'gmb_url',
                                    'note', 'audience', 'is_featured', 'secteur_id', 'og_image'],
                    'rules'     => [
                        ['type' => 'empty_col', 'col' => 'adresse',   'label' => 'Entrées sans adresse'],
                        ['type' => 'empty_col', 'col' => 'ville',     'label' => 'Entrées sans ville'],
                        ['type' => 'empty_col', 'col' => 'gmb_url',   'label' => 'Entrées sans lien Google My Business'],
                        ['type' => 'empty_col', 'col' => 'telephone', 'label' => 'Entrées sans téléphone'],
                    ],
                    'count_by'  => 'status',
                ],
            ],
        ],

        'secteurs' => [
            'label'   => 'Quartiers / Secteurs',
            'icon'    => 'fa-map',
            'color'   => '#6366f1',
            'page'    => 'secteurs',
            'tables'  => [
                [
                    'name'      => 'secteurs',
                    'label'     => 'Secteurs',
                    'required'  => ['id', 'nom', 'slug'],
                    'optional'  => ['description', 'ville', 'code_postal', 'seo_score',
                                    'status', 'og_image', 'meta_title', 'meta_description'],
                    'rules'     => [
                        ['type' => 'empty_col', 'col' => 'description',      'label' => 'Secteurs sans description'],
                        ['type' => 'empty_col', 'col' => 'meta_title',       'label' => 'Secteurs sans balise title SEO'],
                        ['type' => 'empty_col', 'col' => 'meta_description', 'label' => 'Secteurs sans meta description'],
                        ['type' => 'zero_int',  'col' => 'seo_score',        'label' => 'Score SEO non calculé'],
                    ],
                    'count_by'  => 'status',
                ],
            ],
        ],

        'pages' => [
            'label'   => 'Pages du Site',
            'icon'    => 'fa-file',
            'color'   => '#0891b2',
            'page'    => 'pages',
            'tables'  => [
                [
                    'name'      => 'pages',
                    'label'     => 'Pages',
                    'required'  => ['id', 'slug', 'status'],
                    'optional'  => ['meta_title', 'meta_description', 'seo_score',
                                    'word_count', 'google_indexed', 'og_image'],
                    'rules'     => [
                        ['type' => 'empty_col', 'col' => 'meta_title',       'label' => 'Pages sans balise title SEO',    'filter' => ['status' => 'published']],
                        ['type' => 'empty_col', 'col' => 'meta_description', 'label' => 'Pages sans meta description',    'filter' => ['status' => 'published']],
                        ['type' => 'zero_int',  'col' => 'seo_score',        'label' => 'Score SEO non calculé',          'filter' => ['status' => 'published']],
                        ['type' => 'col_value', 'col' => 'google_indexed',   'label' => 'Pages non indexées Google',      'values' => ['no','unknown'], 'filter' => ['status' => 'published']],
                    ],
                    'count_by'  => 'status',
                ],
            ],
        ],

        'leads' => [
            'label'   => 'Leads',
            'icon'    => 'fa-bolt',
            'color'   => '#dc2626',
            'page'    => 'leads',
            'tables'  => [
                [
                    'name'      => 'leads',
                    'label'     => 'Leads',
                    'required'  => ['id', 'created_at'],
                    'optional'  => ['nom', 'email', 'telephone', 'statut', 'source', 'score', 'notes'],
                    'rules'     => [
                        ['type' => 'empty_col', 'col' => 'email',     'label' => 'Leads sans email'],
                        ['type' => 'empty_col', 'col' => 'telephone', 'label' => 'Leads sans téléphone'],
                        ['type' => 'empty_col', 'col' => 'source',    'label' => 'Leads sans source identifiée'],
                        ['type' => 'col_value', 'col' => 'statut',    'label' => 'Leads non traités (nouveau)',  'values' => ['nouveau','new','']],
                    ],
                    'count_by'  => 'statut',
                ],
            ],
        ],

        'crm' => [
            'label'   => 'CRM Contacts',
            'icon'    => 'fa-users',
            'color'   => '#0891b2',
            'page'    => 'crm',
            'tables'  => [
                [
                    'name'      => 'crm_contacts',
                    'alt'       => 'contacts',
                    'label'     => 'Contacts',
                    'required'  => ['id', 'nom', 'email'],
                    'optional'  => ['telephone', 'statut', 'source', 'score', 'created_at', 'notes'],
                    'rules'     => [
                        ['type' => 'empty_col', 'col' => 'telephone', 'label' => 'Contacts sans téléphone'],
                        ['type' => 'empty_col', 'col' => 'source',    'label' => 'Contacts sans source'],
                        ['type' => 'empty_col', 'col' => 'notes',     'label' => 'Contacts sans notes'],
                    ],
                    'count_by'  => 'statut',
                ],
                [
                    'name'      => 'crm_interactions',
                    'label'     => 'Interactions',
                    'required'  => ['id', 'contact_id', 'type', 'created_at'],
                    'optional'  => ['notes', 'user_id'],
                    'rules'     => [],
                    'count_by'  => 'type',
                ],
            ],
        ],

        'properties' => [
            'label'   => 'Biens Immobiliers',
            'icon'    => 'fa-house',
            'color'   => '#c9913b',
            'page'    => 'properties',
            'tables'  => [
                [
                    'name'      => 'properties',
                    'label'     => 'Biens',
                    'required'  => ['id', 'titre', 'statut'],
                    'optional'  => ['prix', 'surface', 'type_bien', 'ville', 'description',
                                    'photos', 'created_at', 'contact_id'],
                    'rules'     => [
                        ['type' => 'empty_col', 'col' => 'prix',        'label' => 'Biens sans prix'],
                        ['type' => 'empty_col', 'col' => 'description', 'label' => 'Biens sans description'],
                        ['type' => 'empty_col', 'col' => 'photos',      'label' => 'Biens sans photos'],
                        ['type' => 'empty_col', 'col' => 'ville',       'label' => 'Biens sans ville'],
                    ],
                    'count_by'  => 'statut',
                ],
            ],
        ],

        'estimations' => [
            'label'   => 'Estimations',
            'icon'    => 'fa-calculator',
            'color'   => '#8b5cf6',
            'page'    => 'estimations',
            'tables'  => [
                [
                    'name'      => 'estimations',
                    'label'     => 'Estimations reçues',
                    'required'  => ['id', 'created_at'],
                    'optional'  => ['adresse', 'ville', 'surface', 'statut', 'email',
                                    'telephone', 'type_bien', 'valeur_estimee'],
                    'rules'     => [
                        ['type' => 'empty_col', 'col' => 'email',          'label' => 'Estimations sans email contact'],
                        ['type' => 'empty_col', 'col' => 'valeur_estimee', 'label' => 'Estimations sans valeur calculée'],
                        ['type' => 'col_value', 'col' => 'statut',         'label' => 'Estimations non traitées', 'values' => ['new','nouveau','']],
                    ],
                    'count_by'  => 'statut',
                ],
            ],
        ],

        'rdv' => [
            'label'   => 'Rendez-vous',
            'icon'    => 'fa-calendar-check',
            'color'   => '#0891b2',
            'page'    => 'rdv',
            'tables'  => [
                [
                    'name'      => 'rdv',
                    'label'     => 'Rendez-vous',
                    'required'  => ['id', 'rdv_date'],
                    'optional'  => ['nom', 'email', 'telephone', 'statut', 'notes', 'adresse'],
                    'rules'     => [
                        ['type' => 'empty_col', 'col' => 'email',     'label' => 'RDV sans email'],
                        ['type' => 'empty_col', 'col' => 'telephone', 'label' => 'RDV sans téléphone'],
                        ['type' => 'col_value', 'col' => 'statut',    'label' => 'RDV en attente de confirmation', 'values' => ['pending','nouveau','']],
                    ],
                    'count_by'  => 'statut',
                ],
            ],
        ],

        'ai_settings' => [
            'label'   => 'IA & Paramètres',
            'icon'    => 'fa-microchip',
            'color'   => '#6366f1',
            'page'    => 'system/settings/ai',
            'tables'  => [
                [
                    'name'      => 'ai_settings',
                    'label'     => 'Paramètres IA',
                    'required'  => ['id', 'setting_key', 'setting_value'],
                    'optional'  => ['provider', 'model', 'updated_at'],
                    'rules'     => [
                        ['type' => 'empty_col', 'col' => 'setting_value', 'label' => 'Paramètres IA non configurés (valeur vide)'],
                    ],
                    'count_by'  => null,
                ],
                [
                    'name'      => 'advisor_context',
                    'label'     => 'Profil conseiller IA',
                    'required'  => ['id', 'field_key', 'field_value'],
                    'optional'  => ['instance_id', 'updated_at'],
                    'rules'     => [
                        ['type' => 'empty_col', 'col' => 'field_value', 'label' => 'Champs profil IA vides'],
                    ],
                    'count_by'  => null,
                ],
            ],
        ],

        'settings' => [
            'label'   => 'Configuration',
            'icon'    => 'fa-gear',
            'color'   => '#64748b',
            'page'    => 'system/settings',
            'tables'  => [
                [
                    'name'      => 'settings',
                    'label'     => 'Paramètres globaux',
                    'required'  => ['id', 'setting_key', 'setting_value'],
                    'optional'  => ['group', 'updated_at'],
                    'rules'     => [
                        ['type' => 'empty_col', 'col' => 'setting_value', 'label' => 'Paramètres sans valeur définie'],
                    ],
                    'count_by'  => 'group',
                ],
            ],
        ],
    ];

    // ─────────────────────────────────────────────────────────
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ─────────────────────────────────────────────────────────
    //  API PUBLIQUE
    // ─────────────────────────────────────────────────────────

    /** Retourne la liste de tous les modules définis */
    public static function getModuleList(): array
    {
        return self::$modules;
    }

    /** Analyse un seul module */
    public function analyzeModule(string $moduleKey): array
    {
        $def = self::$modules[$moduleKey] ?? null;
        if (!$def) return [];
        return $this->runAnalysis($moduleKey, $def);
    }

    /** Analyse tous les modules — retourne tableau indexé par moduleKey */
    public function analyzeAll(): array
    {
        $results = [];
        foreach (self::$modules as $key => $def) {
            $results[$key] = $this->runAnalysis($key, $def);
        }
        return $results;
    }

    /** Calcule le score global de santé (0–100) à partir du résultat analyzeAll() */
    public static function globalScore(array $allResults): int
    {
        $scores = array_column($allResults, 'score');
        if (empty($scores)) return 0;
        return (int) round(array_sum($scores) / count($scores));
    }

    /** Classe CSS du score : excellent / good / ok / bad */
    public static function scoreClass(int $score): string
    {
        if ($score >= 85) return 'excellent';
        if ($score >= 65) return 'good';
        if ($score >= 40) return 'ok';
        return 'bad';
    }

    /** Couleur hex du score */
    public static function scoreColor(int $score): string
    {
        $map = ['excellent' => '#10b981', 'good' => '#3b82f6', 'ok' => '#f59e0b', 'bad' => '#ef4444'];
        return $map[self::scoreClass($score)];
    }

    // ─────────────────────────────────────────────────────────
    //  ANALYSE INTERNE
    // ─────────────────────────────────────────────────────────
    private function runAnalysis(string $key, array $def): array
    {
        $result = [
            'key'          => $key,
            'label'        => $def['label'],
            'icon'         => $def['icon'],
            'color'        => $def['color'],
            'page'         => $def['page'],
            'tables'       => [],
            'issues'       => [],   // liste plate de tous les problèmes
            'score'        => 100,
            'total_rows'   => 0,
            'missing_tables' => 0,
            'warn_tables'    => 0,
            'ok_tables'      => 0,
        ];

        foreach ($def['tables'] as $tableDef) {
            $tableResult = $this->analyzeTable($tableDef);
            $result['tables'][]    = $tableResult;
            $result['total_rows'] += $tableResult['row_count'];

            if (!$tableResult['exists'])              $result['missing_tables']++;
            elseif (!empty($tableResult['col_missing'])) $result['warn_tables']++;
            else                                       $result['ok_tables']++;

            // Agréger les issues
            foreach ($tableResult['issues'] as $issue) {
                $result['issues'][] = array_merge($issue, ['table' => $tableDef['name']]);
            }
        }

        // Score : pénalités
        $totalTables = count($def['tables']);
        $penaltyMissing  = $result['missing_tables'] * 30;
        $penaltyWarn     = $result['warn_tables']    * 15;
        $issueCount      = count($result['issues']);
        $penaltyIssues   = min(40, $issueCount * 5);

        $result['score'] = max(0, 100 - $penaltyMissing - $penaltyWarn - $penaltyIssues);

        return $result;
    }

    private function analyzeTable(array $def): array
    {
        $tName  = $def['name'];
        $altName = $def['alt'] ?? null;

        // Résoudre le nom réel
        $actual = $this->tableExists($tName) ? $tName
                : ($altName && $this->tableExists($altName) ? $altName : null);

        $entry = [
            'name'            => $tName,
            'actual_name'     => $actual,
            'label'           => $def['label'],
            'exists'          => $actual !== null,
            'col_ok'          => [],
            'col_missing'     => [],
            'col_optional_ok' => [],
            'col_optional_miss'=> [],
            'row_count'       => 0,
            'count_by'        => [],
            'issues'          => [],
            'warnings'        => [],
        ];

        if (!$actual) return $entry;

        // Colonnes
        try {
            $cols   = $this->pdo->query("SHOW COLUMNS FROM `{$actual}`")->fetchAll(PDO::FETCH_COLUMN);
            $colSet = array_flip($cols);

            foreach ($def['required'] as $c) {
                if (isset($colSet[$c])) $entry['col_ok'][]      = $c;
                else                   $entry['col_missing'][]  = $c;
            }
            foreach ($def['optional'] as $c) {
                if (isset($colSet[$c])) $entry['col_optional_ok'][]   = $c;
                else                   $entry['col_optional_miss'][]  = $c;
            }
        } catch (PDOException $e) {
            $entry['warnings'][] = 'Impossible de lire les colonnes';
        }

        // Nombre de lignes
        try {
            $entry['row_count'] = (int)$this->pdo->query("SELECT COUNT(*) FROM `{$actual}`")->fetchColumn();
        } catch (PDOException $e) {}

        // Répartition
        if (!empty($def['count_by'])) {
            $cb = $def['count_by'];
            if (in_array($cb, $entry['col_ok']) || in_array($cb, $entry['col_optional_ok'])) {
                try {
                    $rows = $this->pdo->query(
                        "SELECT COALESCE(`{$cb}`, '(null)') as v, COUNT(*) as c FROM `{$actual}` GROUP BY `{$cb}` ORDER BY c DESC LIMIT 10"
                    )->fetchAll(PDO::FETCH_KEY_PAIR);
                    $entry['count_by'] = $rows;
                } catch (PDOException $e) {}
            }
        }

        // Avertissement table vide
        if ($entry['row_count'] === 0) {
            $entry['warnings'][] = 'Table vide — aucune donnée';
        }

        // Règles métier
        foreach ($def['rules'] as $rule) {
            $col = $rule['col'];
            // Vérifier que la colonne existe
            if (!in_array($col, $entry['col_ok']) && !in_array($col, $entry['col_optional_ok'])) continue;
            if ($entry['row_count'] === 0) continue;

            $affected = $this->applyRule($actual, $rule, $entry['col_ok'] + $entry['col_optional_ok']);
            if ($affected !== null && count($affected) > 0) {
                $entry['issues'][] = [
                    'rule'     => $rule['type'],
                    'col'      => $col,
                    'label'    => $rule['label'],
                    'count'    => count($affected),
                    'rows'     => array_slice($affected, 0, 10), // max 10 exemples
                ];
            }
        }

        return $entry;
    }

    // ─────────────────────────────────────────────────────────
    //  RÈGLES
    // ─────────────────────────────────────────────────────────
    private function applyRule(string $table, array $rule, array $availCols): ?array
    {
        try {
            $col    = $rule['col'];
            $where  = [];
            $params = [];

            // Filtre pré-condition (ex: status = published)
            if (!empty($rule['filter'])) {
                foreach ($rule['filter'] as $fc => $fv) {
                    if (in_array($fc, $availCols)) {
                        $where[]  = "`{$fc}` = ?";
                        $params[] = $fv;
                    }
                }
            }

            switch ($rule['type']) {
                case 'empty_col':
                    $where[]  = "(`{$col}` IS NULL OR `{$col}` = '' OR `{$col}` = '0')";
                    break;

                case 'zero_int':
                    $where[]  = "(`{$col}` IS NULL OR `{$col}` = 0)";
                    break;

                case 'low_int':
                    $threshold = (int)($rule['threshold'] ?? 0);
                    $min       = (int)($rule['min']       ?? 0);
                    if ($min > 0) {
                        $where[]  = "(`{$col}` IS NOT NULL AND `{$col}` >= ? AND `{$col}` < ?)";
                        $params[] = $min;
                        $params[] = $threshold;
                    } else {
                        $where[]  = "(`{$col}` IS NOT NULL AND `{$col}` > 0 AND `{$col}` < ?)";
                        $params[] = $threshold;
                    }
                    break;

                case 'col_value':
                    $values = $rule['values'] ?? [];
                    if (empty($values)) return null;
                    $placeholders = implode(',', array_fill(0, count($values), '?'));
                    $where[]  = "(`{$col}` IN ({$placeholders}) OR `{$col}` IS NULL)";
                    $params   = array_merge($params, $values);
                    break;

                default:
                    return null;
            }

            // Détecter la colonne titre
            $titleCols  = ['titre','title','nom','name','display_title','subject'];
            $titleCol   = 'id';
            foreach ($titleCols as $tc) {
                if (in_array($tc, $availCols)) { $titleCol = $tc; break; }
            }

            $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $sql      = "SELECT id, `{$titleCol}` AS row_label FROM `{$table}` {$whereSQL} LIMIT 20";
            $stmt     = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            return null;
        }
    }

    private function tableExists(string $name): bool
    {
        try {
            $this->pdo->query("SELECT 1 FROM `{$name}` LIMIT 1");
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}