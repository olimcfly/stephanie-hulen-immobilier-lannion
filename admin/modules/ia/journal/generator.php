<?php
/**
 * generator.php — Moteur de generation automatique d'idees
 * Module Journal Editorial V3
 * Fichier : admin/modules/journal/generator.php
 *
 * Lit : neuropersona_types, secteurs, launchpad_diagnostic, editorial_journal_config
 * Ecrit : editorial_journal
 *
 * Usage :
 *   require_once 'generator.php';
 *   $gen = new JournalGenerator($pdo, $journalController);
 *   $count = $gen->generate('facebook', 4);  // 4 semaines pour Facebook
 *   $count = $gen->generate(null, 4);         // 4 semaines tous canaux
 */

class JournalGenerator
{
    private PDO $db;
    private JournalController $journal;

    // ================================================================
    // BANQUE DE TEMPLATES (120+)
    // {secteur} {annee} {type_bien} sont remplaces dynamiquement
    // ================================================================

    private const TEMPLATES = [

        // ─────────────────────────────────────
        // BLOG
        // ─────────────────────────────────────
        'blog' => [
            'vendeur' => [
                'unaware' => [
                    ['t' => 'Vendre en {annee} a {secteur} : le bon moment ?', 'type' => 'article-satellite', 'obj' => 'seo-local'],
                    ['t' => 'Evolution des prix immobiliers a {secteur} : bilan {annee}', 'type' => 'article-pilier', 'obj' => 'seo-local'],
                    ['t' => 'Marche immobilier a {secteur} : ce que les chiffres revelent', 'type' => 'article-satellite', 'obj' => 'notoriete'],
                ],
                'problem' => [
                    ['t' => 'Les 7 erreurs qui font perdre de l\'argent quand on vend a {secteur}', 'type' => 'article-pilier', 'obj' => 'seo-local', 'cta' => 'estimation'],
                    ['t' => 'Pourquoi votre bien a {secteur} ne se vend pas', 'type' => 'article-satellite', 'obj' => 'leads', 'cta' => 'estimation'],
                    ['t' => 'Combien vaut vraiment votre bien a {secteur} en {annee} ?', 'type' => 'article-pilier', 'obj' => 'leads', 'cta' => 'estimation'],
                ],
                'solution' => [
                    ['t' => 'Guide complet : vendre a {secteur} avec un conseiller independant', 'type' => 'article-pilier', 'obj' => 'leads', 'cta' => 'rdv'],
                    ['t' => 'Home staging a {secteur} : comment vendre plus vite et plus cher', 'type' => 'article-satellite', 'obj' => 'leads', 'cta' => 'estimation'],
                    ['t' => 'Diagnostics immobiliers a {secteur} : checklist vendeur complete', 'type' => 'article-satellite', 'obj' => 'seo-local', 'cta' => 'estimation'],
                ],
                'product' => [
                    ['t' => 'Pourquoi choisir Eduardo De Sul pour vendre a {secteur}', 'type' => 'article-pilier', 'obj' => 'conversion', 'cta' => 'rdv'],
                    ['t' => 'Temoignage : vente reussie a {secteur} en moins de 30 jours', 'type' => 'article-satellite', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
                'most-aware' => [
                    ['t' => 'Estimation offerte a {secteur} : prenez RDV en ligne', 'type' => 'article-satellite', 'obj' => 'conversion', 'cta' => 'estimation'],
                    ['t' => 'Vendez votre bien a {secteur} : accompagnement premium 360', 'type' => 'article-satellite', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
            ],
            'acheteur' => [
                'unaware' => [
                    ['t' => 'Vivre a {secteur} : le guide du quartier pour les nouveaux arrivants', 'type' => 'article-pilier', 'obj' => 'seo-local'],
                    ['t' => '{secteur} : pourquoi ce quartier attire autant de nouveaux residents', 'type' => 'article-satellite', 'obj' => 'notoriete'],
                ],
                'problem' => [
                    ['t' => 'Acheter a {secteur} en {annee} : prix, quartiers et conseils', 'type' => 'article-pilier', 'obj' => 'seo-local', 'cta' => 'rdv'],
                    ['t' => 'Budget pour acheter a {secteur} : combien prevoir en {annee} ?', 'type' => 'article-satellite', 'obj' => 'trafic'],
                ],
                'solution' => [
                    ['t' => 'Comment un chasseur immobilier vous fait gagner du temps a {secteur}', 'type' => 'article-satellite', 'obj' => 'leads', 'cta' => 'rdv'],
                    ['t' => 'Les etapes cles pour acheter sereinement a {secteur}', 'type' => 'article-pilier', 'obj' => 'leads', 'cta' => 'rdv'],
                ],
                'product' => [
                    ['t' => 'L\'accompagnement acheteur Eduardo De Sul a {secteur}', 'type' => 'article-satellite', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
                'most-aware' => [
                    ['t' => 'Votre projet d\'achat a {secteur} ? RDV gratuit et sans engagement', 'type' => 'article-satellite', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
            ],
            'investisseur' => [
                'problem' => [
                    ['t' => 'Investir a {secteur} en {annee} : rentabilite et perspectives', 'type' => 'article-pilier', 'obj' => 'seo-local', 'cta' => 'rdv'],
                ],
                'solution' => [
                    ['t' => 'Rendement locatif a {secteur} : les chiffres cles pour investir', 'type' => 'article-satellite', 'obj' => 'leads', 'cta' => 'rdv'],
                ],
            ],
            'primo' => [
                'problem' => [
                    ['t' => 'Premier achat a {secteur} : budget realiste et quartiers recommandes', 'type' => 'article-pilier', 'obj' => 'seo-local', 'cta' => 'guide-pdf'],
                    ['t' => 'Frais de notaire a {secteur} : calcul detaille et astuces', 'type' => 'article-satellite', 'obj' => 'seo-local'],
                ],
                'solution' => [
                    ['t' => 'PTZ {annee} a Bordeaux : etes-vous eligible ? Guide complet', 'type' => 'article-pilier', 'obj' => 'leads', 'cta' => 'guide-pdf'],
                    ['t' => 'Credit immobilier {annee} : taux actuels et simulation pour {secteur}', 'type' => 'article-satellite', 'obj' => 'seo-local', 'cta' => 'guide-pdf'],
                ],
            ],
        ],

        // ─────────────────────────────────────
        // GMB
        // ─────────────────────────────────────
        'gmb' => [
            'vendeur' => [
                'problem' => [
                    ['t' => 'Combien vaut votre bien a {secteur} ? Estimation gratuite', 'type' => 'fiche-gmb', 'obj' => 'leads', 'cta' => 'estimation'],
                ],
                'solution' => [
                    ['t' => 'Estimation offerte a {secteur} : resultat en 24h', 'type' => 'fiche-gmb', 'obj' => 'leads', 'cta' => 'estimation'],
                    ['t' => 'Vendre a {secteur} : votre conseiller local vous accompagne', 'type' => 'fiche-gmb', 'obj' => 'seo-local', 'cta' => 'rdv'],
                ],
                'product' => [
                    ['t' => 'Temoignage : vente reussie a {secteur} avec Eduardo De Sul', 'type' => 'fiche-gmb', 'obj' => 'conversion', 'cta' => 'estimation'],
                ],
                'most-aware' => [
                    ['t' => 'Estimation offerte cette semaine — {secteur} et environs', 'type' => 'fiche-gmb', 'obj' => 'conversion', 'cta' => 'estimation'],
                ],
            ],
            'acheteur' => [
                'problem' => [
                    ['t' => 'Prix au m2 a {secteur} : analyse du marche et opportunites', 'type' => 'fiche-gmb', 'obj' => 'seo-local', 'cta' => 'rdv'],
                ],
                'solution' => [
                    ['t' => 'Biens selectionnes a {secteur} — accompagnement personnalise', 'type' => 'fiche-gmb', 'obj' => 'leads', 'cta' => 'rdv'],
                ],
                'product' => [
                    ['t' => 'Temoignage : ma maison ideale trouvee a {secteur}', 'type' => 'fiche-gmb', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
                'most-aware' => [
                    ['t' => 'Dernieres opportunites : biens exclusifs a {secteur}', 'type' => 'fiche-gmb', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
            ],
            'primo' => [
                'solution' => [
                    ['t' => 'PTZ {annee} a {secteur} : verification gratuite d\'eligibilite', 'type' => 'fiche-gmb', 'obj' => 'leads', 'cta' => 'rdv'],
                ],
            ],
        ],

        // ─────────────────────────────────────
        // FACEBOOK
        // ─────────────────────────────────────
        'facebook' => [
            'vendeur' => [
                'unaware' => [
                    ['t' => 'Les prix au m2 a {secteur} ont evolue : decouvrez les chiffres', 'type' => 'post-court', 'obj' => 'notoriete'],
                    ['t' => 'Saviez-vous que {secteur} est l\'un des secteurs les plus dynamiques ?', 'type' => 'post-court', 'obj' => 'notoriete'],
                ],
                'problem' => [
                    ['t' => 'Pourquoi votre bien a {secteur} ne se vend pas (et comment y remedier)', 'type' => 'post-court', 'obj' => 'leads', 'cta' => 'estimation'],
                    ['t' => 'Les 5 pieges a eviter quand on vend son bien a {secteur}', 'type' => 'post-court', 'obj' => 'leads', 'cta' => 'estimation'],
                ],
                'solution' => [
                    ['t' => 'Avant/Apres : comment le home staging a accelere cette vente a {secteur}', 'type' => 'post-court', 'obj' => 'leads', 'cta' => 'estimation'],
                ],
                'product' => [
                    ['t' => 'Les avantages eXp France : technologie + conseiller local a {secteur}', 'type' => 'post-court', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
                'most-aware' => [
                    ['t' => 'Offre speciale vendeurs {secteur} : accompagnement premium 360', 'type' => 'post-court', 'obj' => 'conversion', 'cta' => 'estimation'],
                ],
            ],
            'acheteur' => [
                'unaware' => [
                    ['t' => '{secteur} en photos : pourquoi ce quartier attire tant de residents', 'type' => 'post-court', 'obj' => 'notoriete'],
                    ['t' => '{secteur} : 5 raisons d\'y vivre en {annee}', 'type' => 'post-court', 'obj' => 'notoriete'],
                ],
                'problem' => [
                    ['t' => 'Acheter a {secteur} : budget realiste et bons plans', 'type' => 'post-court', 'obj' => 'trafic'],
                ],
                'solution' => [
                    ['t' => 'Visite virtuelle : bien selectionne a {secteur}', 'type' => 'post-court', 'obj' => 'trafic', 'cta' => 'rdv'],
                ],
                'most-aware' => [
                    ['t' => 'Vous cherchez a acheter a {secteur} ? On en parle cette semaine', 'type' => 'post-court', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
            ],
            'primo' => [
                'solution' => [
                    ['t' => 'Les aides pour les primo-accedants a {secteur} que personne ne connait', 'type' => 'post-court', 'obj' => 'leads', 'cta' => 'guide-pdf'],
                ],
            ],
            'investisseur' => [
                'problem' => [
                    ['t' => '{secteur} en pleine mutation : pourquoi les investisseurs s\'y interessent', 'type' => 'post-court', 'obj' => 'trafic'],
                ],
            ],
        ],

        // ─────────────────────────────────────
        // INSTAGRAM
        // ─────────────────────────────────────
        'instagram' => [
            'vendeur' => [
                'unaware' => [
                    ['t' => 'Les prix ont evolue a {secteur} : decouvrez combien vaut votre bien', 'type' => 'post-court', 'obj' => 'notoriete'],
                ],
                'problem' => [
                    ['t' => 'Les 5 pieges a eviter quand on vend son appartement', 'type' => 'post-court', 'obj' => 'leads', 'cta' => 'estimation'],
                ],
                'solution' => [
                    ['t' => 'Les etapes d\'une vente reussie en 30 secondes', 'type' => 'reel', 'obj' => 'leads', 'cta' => 'estimation'],
                ],
                'product' => [
                    ['t' => 'Un jour dans la vie d\'un conseiller immobilier a {secteur}', 'type' => 'reel', 'obj' => 'autorite', 'cta' => 'rdv'],
                ],
                'most-aware' => [
                    ['t' => 'Estimation gratuite en 24h — lien en bio', 'type' => 'story', 'obj' => 'conversion', 'cta' => 'estimation'],
                ],
            ],
            'acheteur' => [
                'unaware' => [
                    ['t' => 'Les plus belles rues de {secteur} — balade photo', 'type' => 'story', 'obj' => 'notoriete'],
                    ['t' => '{secteur} : le quartier que tout le monde s\'arrache a Bordeaux', 'type' => 'post-court', 'obj' => 'notoriete'],
                ],
                'solution' => [
                    ['t' => 'Visite : bien selectionne a {secteur} avec vue', 'type' => 'story', 'obj' => 'trafic', 'cta' => 'rdv'],
                ],
            ],
            'primo' => [
                'problem' => [
                    ['t' => 'Budget premier achat a {secteur} : combien prevoir ?', 'type' => 'reel', 'obj' => 'trafic', 'cta' => 'guide-pdf'],
                ],
                'product' => [
                    ['t' => 'Comment Eduardo a accompagne un primo-accedant a {secteur}', 'type' => 'story', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
            ],
            'investisseur' => [
                'unaware' => [
                    ['t' => 'Bordeaux : la ville preferee des Francais pour investir dans l\'immobilier', 'type' => 'post-court', 'obj' => 'notoriete'],
                ],
                'problem' => [
                    ['t' => '{secteur} en mutation : pourquoi les investisseurs s\'y interessent', 'type' => 'post-court', 'obj' => 'trafic'],
                ],
            ],
        ],

        // ─────────────────────────────────────
        // TIKTOK
        // ─────────────────────────────────────
        'tiktok' => [
            'vendeur' => [
                'unaware' => [
                    ['t' => '3 signes que c\'est le bon moment pour vendre a {secteur}', 'type' => 'video-script', 'obj' => 'notoriete'],
                ],
                'problem' => [
                    ['t' => 'Les erreurs qui font perdre des milliers d\'euros aux vendeurs', 'type' => 'video-script', 'obj' => 'notoriete'],
                ],
                'product' => [
                    ['t' => 'Agence classique vs conseiller eXp : la vraie difference', 'type' => 'video-script', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
            ],
            'acheteur' => [
                'unaware' => [
                    ['t' => 'Vivre a {secteur} en 60 secondes', 'type' => 'video-script', 'obj' => 'notoriete'],
                    ['t' => '{secteur} : le quartier que personne ne vous montre a Bordeaux', 'type' => 'video-script', 'obj' => 'notoriete'],
                ],
                'solution' => [
                    ['t' => 'POV : vous visitez un appartement a {secteur}', 'type' => 'video-script', 'obj' => 'trafic'],
                ],
                'most-aware' => [
                    ['t' => 'Votre projet immobilier merite un expert local', 'type' => 'video-script', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
            ],
            'primo' => [
                'problem' => [
                    ['t' => 'Devenir proprietaire a 25 ans a Bordeaux, c\'est possible ?', 'type' => 'video-script', 'obj' => 'notoriete'],
                ],
                'solution' => [
                    ['t' => 'Les aides pour acheter que personne ne connait', 'type' => 'video-script', 'obj' => 'leads', 'cta' => 'guide-pdf'],
                ],
            ],
            'investisseur' => [
                'problem' => [
                    ['t' => '{secteur} : le quartier ou investir a Bordeaux en {annee}', 'type' => 'video-script', 'obj' => 'trafic'],
                ],
            ],
        ],

        // ─────────────────────────────────────
        // LINKEDIN
        // ─────────────────────────────────────
        'linkedin' => [
            'investisseur' => [
                'unaware' => [
                    ['t' => 'Marche immobilier Bordeaux Metropole : chiffres cles {annee}', 'type' => 'post-court', 'obj' => 'autorite'],
                ],
                'problem' => [
                    ['t' => 'Le marche de {secteur} : analyse trimestrielle et perspectives', 'type' => 'post-court', 'obj' => 'autorite'],
                ],
                'solution' => [
                    ['t' => 'Rendement locatif a {secteur} : quel quartier choisir en {annee} ?', 'type' => 'post-court', 'obj' => 'autorite', 'cta' => 'rdv'],
                    ['t' => 'Investir a {secteur} : le pari gagnant de la rive droite', 'type' => 'post-court', 'obj' => 'autorite', 'cta' => 'rdv'],
                ],
                'product' => [
                    ['t' => 'Investissement locatif etudiant a {secteur} : analyse rentabilite', 'type' => 'post-court', 'obj' => 'leads', 'cta' => 'rdv'],
                ],
            ],
            'vendeur' => [
                'solution' => [
                    ['t' => 'Pourquoi le modele eXp France change la donne pour les vendeurs', 'type' => 'post-court', 'obj' => 'autorite', 'cta' => 'rdv'],
                ],
            ],
        ],

        // ─────────────────────────────────────
        // EMAIL
        // ─────────────────────────────────────
        'email' => [
            'vendeur' => [
                'problem' => [
                    ['t' => 'Les erreurs classiques des vendeurs : comment les eviter', 'type' => 'email', 'obj' => 'nurturing', 'cta' => 'estimation'],
                ],
                'solution' => [
                    ['t' => '3 solutions pour vendre rapidement et au meilleur prix', 'type' => 'email', 'obj' => 'leads', 'cta' => 'estimation'],
                ],
                'product' => [
                    ['t' => 'Decouvrez l\'accompagnement premium Eduardo De Sul', 'type' => 'email', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
            ],
            'acheteur' => [
                'unaware' => [
                    ['t' => 'Votre veille immo : les tendances du marche bordelais', 'type' => 'email', 'obj' => 'nurturing', 'cta' => 'rdv'],
                ],
                'problem' => [
                    ['t' => 'Acheter a {secteur} : notre selection de la semaine', 'type' => 'email', 'obj' => 'nurturing', 'cta' => 'rdv'],
                ],
            ],
            'primo' => [
                'most-aware' => [
                    ['t' => 'Votre projet d\'achat a Bordeaux ? Parlons-en cette semaine', 'type' => 'email', 'obj' => 'conversion', 'cta' => 'rdv'],
                ],
                'solution' => [
                    ['t' => 'Guide primo-accedant : toutes les aides disponibles en {annee}', 'type' => 'email', 'obj' => 'leads', 'cta' => 'guide-pdf'],
                ],
            ],
            'investisseur' => [
                'problem' => [
                    ['t' => 'Rendement locatif Bordeaux : les quartiers a surveiller en {annee}', 'type' => 'email', 'obj' => 'nurturing', 'cta' => 'rdv'],
                ],
            ],
        ],
    ];

    // ================================================================
    // CONSTRUCTEUR
    // ================================================================

    public function __construct(PDO $db, JournalController $journal)
    {
        $this->db      = $db;
        $this->journal = $journal;
    }

    // ================================================================
    // GENERATION PRINCIPALE
    // ================================================================

    /**
     * Generer des idees
     * @param string|null $channel  Canal specifique ou null pour tous
     * @param int         $weeks    Nombre de semaines a generer
     * @return int                  Nombre d'idees creees
     */
    public function generate(?string $channel = null, int $weeks = 4): int
    {
        $config   = $this->journal->getConfig();
        $secteurs = $this->journal->getSecteurs();

        // Canaux a traiter
        $channels = $channel
            ? [$channel]
            : ($config['active_channels'] ?? array_keys(JournalController::CHANNELS));

        // Profils actifs
        $profiles = $config['active_profiles'] ?? array_keys(JournalController::PROFILES);

        // Semaine de depart
        $current     = JournalController::getCurrentWeek();
        $startWeek   = $current['week'];
        $currentYear = $current['year'];

        // Charger les titres existants pour eviter les doublons
        $existingTitles = $this->getExistingTitles($currentYear);

        $created = 0;

        for ($w = 0; $w < $weeks; $w++) {
            $weekNum = $startWeek + $w;
            $year    = $currentYear;

            // Gerer le passage d'annee
            if ($weekNum > 52) {
                $weekNum -= 52;
                $year++;
            }

            // Choisir un secteur en rotation (un different chaque semaine)
            $sectorIndex = ($startWeek + $w) % count($secteurs);
            $sector      = $secteurs[$sectorIndex];

            foreach ($channels as $ch) {
                if (!isset(self::TEMPLATES[$ch])) continue;

                foreach ($profiles as $profile) {
                    if (!isset(self::TEMPLATES[$ch][$profile])) continue;

                    // Determiner les niveaux de conscience a cibler cette semaine
                    // Rotation : semaines impaires = conscience basse, paires = haute
                    $awarenessTargets = $this->getAwarenessTargets($ch, $w);

                    foreach ($awarenessTargets as $awareness) {
                        if (!isset(self::TEMPLATES[$ch][$profile][$awareness])) continue;

                        $templates = self::TEMPLATES[$ch][$profile][$awareness];
                        if (empty($templates)) continue;

                        // Choisir un template aleatoire
                        $tpl = $templates[array_rand($templates)];

                        // Remplacer les variables
                        $title = str_replace(
                            ['{secteur}', '{annee}', '{type_bien}'],
                            [$sector['nom'], (string)$year, 'bien'],
                            $tpl['t']
                        );

                        // Verifier doublon
                        $titleClean = mb_strtolower(trim($title));
                        if (in_array($titleClean, $existingTitles)) continue;

                        // Creer l'entree
                        $data = [
                            'title'           => $title,
                            'channel_id'      => $ch,
                            'profile_id'      => $profile,
                            'sector_id'       => $sector['slug'],
                            'awareness_level' => $awareness,
                            'objective_id'    => $tpl['obj'] ?? 'notoriete',
                            'content_type'    => $tpl['type'] ?? 'post-court',
                            'cta_type'        => $tpl['cta'] ?? null,
                            'week_number'     => $weekNum,
                            'year'            => $year,
                            'priority'        => $this->getPriority($awareness),
                            'ai_generated'    => 1,
                            'status'          => 'idea',
                        ];

                        try {
                            $this->journal->create($data);
                            $existingTitles[] = $titleClean;
                            $created++;
                        } catch (\Exception $e) {
                            // Log silencieux, continuer
                            error_log('[JournalGenerator] Erreur creation : ' . $e->getMessage());
                        }
                    }
                }
            }
        }

        return $created;
    }

    // ================================================================
    // METHODES PRIVEES
    // ================================================================

    /**
     * Titres existants pour eviter les doublons
     */
    private function getExistingTitles(int $year): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT LOWER(TRIM(title)) AS t FROM editorial_journal WHERE year = :y"
            );
            $stmt->execute([':y' => $year]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Determiner les niveaux de conscience a cibler selon le canal et la semaine
     * Logique :
     *   - Chaque semaine on fait varier le niveau de conscience
     *   - Cycle de 5 semaines (un niveau par semaine)
     *   - Certains canaux privilegient certains niveaux
     */
    private function getAwarenessTargets(string $channel, int $weekOffset): array
    {
        $allLevels = ['unaware', 'problem', 'solution', 'product', 'most-aware'];

        // Priorites par canal
        $channelWeights = [
            'blog'      => ['problem', 'solution', 'unaware', 'product', 'most-aware'],
            'gmb'       => ['solution', 'product', 'problem', 'most-aware'],
            'facebook'  => ['unaware', 'problem', 'solution', 'most-aware'],
            'instagram' => ['unaware', 'problem', 'solution', 'product'],
            'tiktok'    => ['unaware', 'problem', 'solution'],
            'linkedin'  => ['problem', 'solution', 'product', 'unaware'],
            'email'     => ['problem', 'solution', 'product', 'most-aware'],
        ];

        $weights = $channelWeights[$channel] ?? $allLevels;

        // Retourner 1-2 niveaux par semaine en rotation
        $idx = $weekOffset % count($weights);
        $targets = [$weights[$idx]];

        // Ajouter un 2eme niveau une semaine sur deux
        if ($weekOffset % 2 === 0 && isset($weights[$idx + 1])) {
            $targets[] = $weights[$idx + 1];
        }

        return $targets;
    }

    /**
     * Priorite selon le niveau de conscience
     * Plus le prospect est avance, plus c'est prioritaire
     */
    private function getPriority(string $awareness): int
    {
        $map = [
            'most-aware' => 1,
            'product'    => 2,
            'solution'   => 3,
            'problem'    => 4,
            'unaware'    => 5,
        ];
        return $map[$awareness] ?? 5;
    }
}