<?php
/**
 * ============================================================
 *  ArticlesHandler — Module IA pour les articles de blog
 *  Fichier : core/ai/modules/ArticlesHandler.php
 *
 *  Produit : EcosystèmeImmo — Plateforme CRM & Marketing Immobilier
 *
 * ─────────────────────────────────────────────────────────────
 *  Héritage : BaseHandler
 *  Routage   : AiDispatcher → 'articles' → ArticlesHandler
 *
 *  Actions disponibles :
 * ─────────────────────────────────────────────────────────────
 *  wizard_generate  ← Nouvelle action — Wizard de génération complet
 *                     (sujet, mots-clés, persona, structure, options)
 *
 *  generate         ← Génération rapide (rétrocompatibilité)
 *  improve          ← Amélioration contenu existant
 *  meta             ← SEO title + meta description + slug
 *  faq              ← FAQ Schema.org (N questions)
 *  outline          ← Plan éditorial H2/H3
 *  keywords         ← Extraction mots-clés SEO
 *  rewrite          ← Réécriture avec nouvel angle
 * ─────────────────────────────────────────────────────────────
 *
 *  Architecture du wizard_generate :
 *    1. $this->marketData()  → recherche Perplexity (sources externes)
 *    2. Prompt enrichi construit avec AiPromptBuilder
 *    3. Appel $this->generate() (Claude avec fallback OpenAI)
 *    4. Parsing JSON de la réponse
 *    5. $this->success([article => [...]])
 *
 * ============================================================
 */

declare(strict_types=1);

class ArticlesHandler extends BaseHandler
{
    // ─── Actions déclarées ────────────────────────────────────────────────────
    protected array $actions = [
        'wizard_generate',
        'generate',
        'improve',
        'meta',
        'faq',
        'outline',
        'keywords',
        'rewrite',
    ];

    // ═════════════════════════════════════════════════════════════════════════
    //  ACTION : wizard_generate
    //  Appelé depuis ArticleWizard.php (JS) via generate.php
    //  Payload attendu : voir ArticleWizard.php — _buildPayload()
    // ═════════════════════════════════════════════════════════════════════════
    protected function handle_wizard_generate(array $input): void
    {
        // ── 1. Paramètres requis ──────────────────────────────────────────────
        $this->need($input, 'subject', 'keyword');

        $subject      = $this->str($input, 'subject');
        $keyword      = $this->str($input, 'keyword');
        $secKeywords  = $this->str($input, 'secondary_keywords');
        $location     = $this->str($input, 'location');
        $wordCount    = $this->int($input, 'word_count', 1200, 600, 3000);
        $type         = $this->str($input, 'type', 'guide');
        $tone         = $this->str($input, 'tone', 'professionnel');
        $angle        = $this->str($input, 'angle');
        $persona      = $this->str($input, 'persona');
        $consciousness= $this->str($input, 'consciousness');
        $objectif     = $this->str($input, 'objectif');
        $instructions = $this->str($input, 'instructions');

        // Options structurelles (booléens)
        $inclSommaire = !empty($input['include_sommaire']);
        $inclFaq      = !empty($input['include_faq']);
        $inclCta      = !empty($input['include_cta']);
        $inclLinks    = !empty($input['include_links']);
        $inclSchema   = !empty($input['include_schema']);
        $inclInternal = !empty($input['include_internal']);

        // ── 2. Sources Perplexity (si demandées) ─────────────────────────────
        $sourcesBlock = '';
        if ($inclLinks) {
            $perplexityQuery = $keyword
                . ($location ? ' ' . $location : '')
                . ' immobilier france';
            $marketRaw = $this->marketData($perplexityQuery);
            if ($marketRaw) {
                $sourcesBlock = AiPromptBuilder::withMarketData('', $marketRaw, 800);
            }
        }

        // ── 3. Plan outline (si fourni depuis le wizard JS) ───────────────────
        $outlineBlock = '';
        if (!empty($input['outline']) && is_array($input['outline'])) {
            $outlineBlock = "\n\nPlan éditorial à respecter :\n";
            foreach ($input['outline'] as $section) {
                $level = strtoupper($section['level'] ?? 'H2');
                $title = $section['title'] ?? '';
                $outlineBlock .= "  [{$level}] {$title}\n";
            }
        }

        // ── 4. Contexte persona adapté ────────────────────────────────────────
        $personaLabel     = self::PERSONAS[$persona]           ?? $persona;
        $consciousLabel   = self::CONSCIOUSNESS[$consciousness] ?? $consciousness;
        $objectifLabel    = self::OBJECTIFS[$objectif]          ?? $objectif;
        $typeLabel        = self::CONTENT_TYPES[$type]          ?? $type;

        $audienceBlock = '';
        if ($persona)      $audienceBlock .= "Persona cible : {$personaLabel}.\n";
        if ($consciousness) $audienceBlock .= "Niveau de conscience : {$consciousLabel} — "
                                             . self::CONSCIOUSNESS_HINT[$consciousness] . "\n";
        if ($objectif)     $audienceBlock .= "Objectif de l'article : {$objectifLabel}.\n";
        if ($angle)        $audienceBlock .= "Angle éditorial : {$angle}.\n";
        if ($instructions) $audienceBlock .= "Instructions spéciales : {$instructions}\n";

        // ── 5. Directives structurelles ───────────────────────────────────────
        $structureDirectives = "Structure requise :\n";
        $structureDirectives .= $inclSommaire
            ? "- SOMMAIRE : Inclure une table des matières avec ancres HTML (id=) avant le premier H2.\n"
            : "- SOMMAIRE : Ne pas inclure de sommaire.\n";
        $structureDirectives .= $inclFaq
            ? "- FAQ : Inclure une section FAQ avec 5 questions/réponses à la fin de l'article (avant la conclusion).\n"
            : "- FAQ : Ne pas inclure de section FAQ.\n";
        $structureDirectives .= $inclCta
            ? "- CTA : Inclure 1 CTA mid-article (après le 3e H2) + 1 CTA final (après la conclusion), "
              . "personnalisés selon le persona.\n"
            : "- CTA : Ne pas inclure de CTA.\n";
        $structureDirectives .= $inclLinks
            ? "- LIENS EXTERNES : Intégrer 2-3 liens vers des sources officielles pertinentes "
              . "(notaires.fr, service-public.fr, legifrance.gouv.fr, bofip.impots.gouv.fr, etc.). "
              . "Format : <a href=\"URL\" target=\"_blank\" rel=\"noopener\">texte du lien</a>\n"
            : "- LIENS EXTERNES : Ne pas inclure de liens externes.\n";
        if ($inclSchema) {
            $structureDirectives .= "- SCHEMA : Inclure un bloc JSON-LD Schema.org Article complet "
                                  . "à la toute fin du contenu.\n";
        }
        if ($inclInternal) {
            $structureDirectives .= "- MAILLAGE INTERNE : Inclure des suggestions de liens internes "
                                  . "sous forme de commentaire HTML à la fin : <!-- LIENS INTERNES SUGGÉRÉS: ... -->\n";
        }

        // ── 6. Prompt principal ───────────────────────────────────────────────
        $prompt = <<<PROMPT
Rédige un article de blog SEO complet pour le site d'un conseiller immobilier.

PARAMÈTRES :
- Sujet        : {$subject}
- Mot-clé focus: {$keyword}
- Mots-clés sec: {$secKeywords}
- Localisation : {$location}
- Type         : {$typeLabel}
- Ton          : {$tone}
- Longueur     : ~{$wordCount} mots (respecter ±10%)

{$audienceBlock}
{$structureDirectives}
{$outlineBlock}
{$sourcesBlock}

RÈGLES SEO OBLIGATOIRES :
1. Balise title (H1) : contient le mot-clé focus, 50-60 caractères, accrocheur.
2. Introduction (150-200 mots) : pose le problème, contient le mot-clé focus dans les 100 premiers mots.
3. H2 (4-6 sections selon la longueur) : variantes sémantiques du mot-clé focus.
4. H3 (2-3 par H2 si pertinent) : longue traîne.
5. Densité mot-clé : 1-2% (ni plus, ni moins).
6. Synonymes et champ lexical enrichi : au moins 8-10 termes sémantiquement liés.
7. Paragraphes courts : 3-4 lignes max, espacement visuel.
8. Conclusion : synthèse + appel à l'action naturel.
9. Le contenu doit être en HTML propre (h2, h3, p, ul, li, strong, a).

Réponds UNIQUEMENT avec le JSON suivant, sans texte autour, sans markdown :
PROMPT;

        $schema = <<<JSON
{
  "title": "Titre H1 SEO optimisé (50-60 car.)",
  "slug": "slug-url-seo",
  "meta_title": "Balise title SEO (50-60 car.)",
  "meta_description": "Meta description accrocheuse (150-160 car.)",
  "excerpt": "Résumé pour les listings (120-180 car.)",
  "primary_keyword": "mot-clé focus final",
  "secondary_keywords": "terme1, terme2, terme3",
  "focus_keyword": "mot-clé focus",
  "content": "<h2>...</h2><p>...</p>... (HTML complet de l'article)",
  "reading_time": 6,
  "word_count": 1200,
  "faq": [
    {"question": "...", "answer": "..."},
    {"question": "...", "answer": "..."}
  ],
  "cta_mid": "Texte du CTA mid-article",
  "cta_final": "Texte du CTA de fin d'article",
  "internal_links_suggestions": ["titre article suggéré 1", "titre article suggéré 2"],
  "seo_score_estimate": 85
}
JSON;

        $fullPrompt = AiPromptBuilder::json($prompt, $schema);
        $system     = $this->context();

        // ── 7. Appel IA ───────────────────────────────────────────────────────
        // Tokens estimés : ~250 mots/1000 tokens + marge structure
        $maxTokens = min(8000, (int)ceil($wordCount * 1.8) + 1500);
        $result    = $this->generate($fullPrompt, $system, $maxTokens, 0.75);
        $this->track('wizard_generate', $result);

        if (!$result['success']) {
            $this->fail($result['error'] ?? 'Erreur lors de la génération de l\'article');
        }

        // ── 8. Parser la réponse ──────────────────────────────────────────────
        $parsed = $this->parseJson($result['content']);

        if (!$parsed || empty($parsed['content'])) {
            // Fallback : retourner le texte brut dans le champ content
            $this->success([
                'article' => [
                    'title'           => $subject,
                    'slug'            => AiPromptBuilder::slug($subject),
                    'meta_title'      => $keyword . ' — Guide complet',
                    'meta_description'=> 'Découvrez nos conseils sur : ' . $subject,
                    'excerpt'         => substr(strip_tags($result['content']), 0, 160),
                    'content'         => '<p>' . nl2br(htmlspecialchars($result['content'])) . '</p>',
                    'primary_keyword' => $keyword,
                    'secondary_keywords' => $secKeywords,
                ],
                'raw'     => $result['content'],
                'provider'=> $result['provider'] ?? 'unknown',
                '_notice' => 'Fallback : JSON non parsé — contenu brut retourné',
            ]);
        }

        // ── 9. Normaliser et sécuriser ────────────────────────────────────────
        $article = [
            'title'            => $parsed['title']              ?? $subject,
            'slug'             => $parsed['slug']               ?? AiPromptBuilder::slug($subject),
            'meta_title'       => $parsed['meta_title']         ?? $parsed['title'] ?? '',
            'meta_description' => $parsed['meta_description']   ?? '',
            'excerpt'          => $parsed['excerpt']            ?? '',
            'primary_keyword'  => $parsed['primary_keyword']    ?? $keyword,
            'focus_keyword'    => $parsed['focus_keyword']      ?? $keyword,
            'secondary_keywords'=> $parsed['secondary_keywords'] ?? $secKeywords,
            'content'          => $parsed['content']            ?? '',
            'reading_time'     => (int)($parsed['reading_time'] ?? max(1, (int)ceil($wordCount / 200))),
            'word_count'       => (int)($parsed['word_count']   ?? $wordCount),
            'faq'              => $parsed['faq']                ?? [],
            'cta_mid'          => $parsed['cta_mid']            ?? '',
            'cta_final'        => $parsed['cta_final']          ?? '',
            'internal_links_suggestions' => $parsed['internal_links_suggestions'] ?? [],
            'seo_score_estimate' => (int)($parsed['seo_score_estimate'] ?? 0),
        ];

        // ── 10. Assembler le contenu final (FAQ + CTA si séparés) ─────────────
        if ($inclFaq && !empty($article['faq']) && !str_contains($article['content'], 'FAQ')) {
            $article['content'] .= $this->buildFaqHtml($article['faq']);
        }
        if ($inclSchema) {
            $article['content'] .= $this->buildSchemaJsonLd($article);
        }

        $this->success([
            'article'  => $article,
            'provider' => $result['provider'] ?? 'unknown',
            'usage'    => $result['usage']    ?? [],
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  ACTION : generate (rétrocompatibilité — appel rapide sans wizard)
    // ═════════════════════════════════════════════════════════════════════════
    protected function handle_generate(array $input): void
    {
        // Déléguer à wizard_generate avec les paramètres de base
        $input['include_sommaire'] = $input['include_sommaire'] ?? false;
        $input['include_faq']      = $input['include_faq']      ?? true;
        $input['include_cta']      = $input['include_cta']      ?? true;
        $input['include_links']    = $input['include_links']     ?? false;
        $this->handle_wizard_generate($input);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  ACTION : improve
    // ═════════════════════════════════════════════════════════════════════════
    protected function handle_improve(array $input): void
    {
        $this->need($input, 'content');
        $content    = $this->str($input, 'content');
        $title      = $this->str($input, 'title');
        $objectives = $this->str($input, 'objectives', 'SEO, lisibilité, engagement');

        $prompt = <<<PROMPT
Améliore cet article de blog immobilier selon ces objectifs : {$objectives}.

Titre : {$title}

Contenu actuel :
{$content}

Améliorations attendues :
- Enrichir le champ lexical sémantique (synonymes, LSI keywords)
- Améliorer la lisibilité (phrases courtes, paragraphes aérés)
- Renforcer les transitions entre sections
- Optimiser les appels à l'action
- Corriger les répétitions excessives du mot-clé
- Ajouter de la valeur (exemples, données, conseils concrets)

Réponds UNIQUEMENT avec ce JSON :
PROMPT;

        $schema = <<<JSON
{
  "improved_content": "HTML complet de l'article amélioré",
  "changes_summary": [
    "Description du changement 1",
    "Description du changement 2",
    "Description du changement 3"
  ],
  "seo_improvements": ["amélioration SEO 1", "amélioration SEO 2"]
}
JSON;

        $result = $this->generate(AiPromptBuilder::json($prompt, $schema), $this->context(), 4000, 0.7);
        $this->track('improve', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur amélioration');

        $parsed = $this->parseJson($result['content']);
        $this->success([
            'improved_content' => $parsed['improved_content'] ?? $result['content'],
            'changes_summary'  => $parsed['changes_summary']  ?? [],
            'seo_improvements' => $parsed['seo_improvements'] ?? [],
        ]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  ACTION : meta
    // ═════════════════════════════════════════════════════════════════════════
    protected function handle_meta(array $input): void
    {
        $title   = $this->str($input, 'title');
        $keyword = $this->str($input, 'keyword');
        $content = $this->str($input, 'content');
        $content = substr(strip_tags($content), 0, 600);

        $prompt = "Génère les métadonnées SEO optimisées pour cet article immobilier.\n\n"
                . "Titre : {$title}\n"
                . "Mot-clé focus : {$keyword}\n"
                . "Extrait du contenu : {$content}\n\n"
                . "Le meta_title doit contenir le mot-clé et ne pas dépasser 60 caractères.\n"
                . "La meta_description doit être accrocheuse, contenir le mot-clé, et faire 150-160 caractères.\n"
                . "Le slug doit être en minuscules, tirets, sans accents, 4-6 mots max.";

        $result = $this->generate(
            AiPromptBuilder::json($prompt, AiPromptBuilder::metaSchema()),
            $this->context(), 800, 0.5
        );
        $this->track('meta', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur méta');

        $parsed = $this->parseJson($result['content']);
        $this->success(array_merge([
            'meta_title'       => '',
            'meta_description' => '',
            'slug'             => AiPromptBuilder::slug($title),
            'focus_keyword'    => $keyword,
        ], $parsed ?? []));
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  ACTION : faq
    // ═════════════════════════════════════════════════════════════════════════
    protected function handle_faq(array $input): void
    {
        $title   = $this->str($input, 'title');
        $content = substr(strip_tags($this->str($input, 'content')), 0, 800);
        $count   = $this->int($input, 'count', 5, 3, 10);

        $prompt = "Génère {$count} questions/réponses FAQ pour un article immobilier.\n\n"
                . "Titre : {$title}\n"
                . "Contexte : {$content}\n\n"
                . "Les questions doivent être celles que les prospects se posent réellement.\n"
                . "Les réponses doivent être complètes (2-4 phrases), directes, en français impeccable.\n"
                . "Format adapté aux featured snippets Google (réponses courtes et précises).";

        $result = $this->generate(
            AiPromptBuilder::json($prompt, AiPromptBuilder::faqSchema($count)),
            $this->context(), 1500, 0.6
        );
        $this->track('faq', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur FAQ');

        $parsed = $this->parseJson($result['content']);
        $this->success(['faq' => $parsed['faq'] ?? []]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  ACTION : outline
    // ═════════════════════════════════════════════════════════════════════════
    protected function handle_outline(array $input): void
    {
        $subject   = $this->str($input, 'subject');
        $keyword   = $this->str($input, 'keyword');
        $type      = $this->str($input, 'type', 'guide');
        $wordCount = $this->int($input, 'word_count', 1200, 600, 3000);

        $prompt = "Génère un plan éditorial SEO pour un article de blog immobilier.\n\n"
                . "Sujet : {$subject}\n"
                . "Mot-clé focus : {$keyword}\n"
                . "Type : {$type}\n"
                . "Longueur cible : ~{$wordCount} mots\n\n"
                . "Le plan doit optimiser la couverture sémantique et le maillage des H2/H3.\n"
                . "Chaque section doit cibler une intention de recherche spécifique.\n"
                . "Inclure des estimations de mots par section.";

        $schema = <<<JSON
{
  "title_suggestions": ["Titre option 1", "Titre option 2", "Titre option 3"],
  "outline": [
    {
      "level": "H2",
      "title": "...",
      "description": "Ce que cette section couvrira",
      "estimated_words": 200,
      "keywords": ["kw1", "kw2"]
    }
  ],
  "estimated_total_words": 1200,
  "semantic_keywords": ["terme1", "terme2", "terme3"]
}
JSON;

        $result = $this->generate(AiPromptBuilder::json($prompt, $schema), $this->context(), 1500, 0.65);
        $this->track('outline', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur outline');

        $parsed = $this->parseJson($result['content']);
        $this->success(['outline' => $parsed ?? []]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  ACTION : keywords
    // ═════════════════════════════════════════════════════════════════════════
    protected function handle_keywords(array $input): void
    {
        $subject = $this->str($input, 'subject');
        $content = substr($this->str($input, 'content'), 0, 1500);

        $prompt = "Analyse le sujet et extrait les mots-clés SEO pour un article immobilier français.\n\n"
                . "Sujet : {$subject}\n"
                . "Contenu : {$content}";

        $schema = <<<JSON
{
  "primary_keyword": "mot-clé principal (longue traîne, 3-5 mots)",
  "secondary_keywords": [
    {"keyword": "...", "search_intent": "informational|transactional|local"},
    {"keyword": "...", "search_intent": "..."}
  ],
  "long_tail_keywords": ["requête longue traîne 1", "requête longue traîne 2"],
  "local_keywords": ["terme local 1 (ville, quartier)", "terme local 2"],
  "semantic_field": ["champ lexical 1", "champ lexical 2", "champ lexical 3"]
}
JSON;

        $result = $this->generate(AiPromptBuilder::json($prompt, $schema), $this->context(), 1000, 0.5);
        $this->track('keywords', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur keywords');

        $parsed = $this->parseJson($result['content']);
        $this->success(['keywords' => $parsed ?? []]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  ACTION : rewrite
    // ═════════════════════════════════════════════════════════════════════════
    protected function handle_rewrite(array $input): void
    {
        $this->need($input, 'content', 'angle');
        $content = $this->str($input, 'content');
        $angle   = $this->str($input, 'angle');

        $prompt = "Réécris cet article immobilier avec le nouvel angle éditorial : \"{$angle}\".\n\n"
                . "Contenu original :\n{$content}\n\n"
                . "Conserve la structure HTML (h2, h3, p, ul) et les mots-clés SEO.\n"
                . "Adapte le ton, les arguments et les exemples au nouvel angle.\n"
                . "Longueur équivalente à l'original.";

        $schema = '{"rewritten_content": "HTML complet réécrit"}';

        $result = $this->generate(AiPromptBuilder::json($prompt, $schema), $this->context(), 4000, 0.75);
        $this->track('rewrite', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur réécriture');

        $parsed = $this->parseJson($result['content']);
        $this->success(['rewritten_content' => $parsed['rewritten_content'] ?? $result['content']]);
    }

    // ═════════════════════════════════════════════════════════════════════════
    //  HELPERS PRIVÉS
    // ═════════════════════════════════════════════════════════════════════════

    /**
     * Génère le HTML d'une section FAQ depuis un tableau de Q/R.
     */
    private function buildFaqHtml(array $faq): string
    {
        if (empty($faq)) return '';
        $html = "\n<section class=\"article-faq\">\n<h2>Questions fréquentes</h2>\n";
        foreach ($faq as $item) {
            $q = htmlspecialchars($item['question'] ?? '', ENT_QUOTES);
            $a = $item['answer'] ?? '';
            $html .= "<div class=\"faq-item\">\n"
                   . "  <h3>{$q}</h3>\n"
                   . "  <p>{$a}</p>\n"
                   . "</div>\n";
        }
        $html .= "</section>\n";
        return $html;
    }

    /**
     * Génère le JSON-LD Schema.org Article.
     */
    private function buildSchemaJsonLd(array $article): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Article',
            'headline' => $article['title']   ?? '',
            'description' => $article['meta_description'] ?? $article['excerpt'] ?? '',
            'keywords' => $article['primary_keyword'] ?? '',
        ];

        $json = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        return "\n<script type=\"application/ld+json\">\n{$json}\n</script>\n";
    }

    // ─── Constantes de mapping (labels) ──────────────────────────────────────
    private const PERSONAS = [
        'primo'          => 'Primo-accédant',
        'investisseur'   => 'Investisseur locatif',
        'vendeur'        => 'Propriétaire vendeur',
        'divorce'        => 'Propriétaire en cours de séparation',
        'succession'     => 'Héritier en succession',
        'expatrie'       => 'Expatrié souhaitant vendre',
        'retraite'       => 'Retraité',
        'professionnel'  => 'Professionnel (cadre, chef d\'entreprise)',
    ];

    private const CONTENT_TYPES = [
        'guide'      => 'Guide pratique complet',
        'conseils'   => 'Article conseils pratiques',
        'analyse'    => 'Analyse du marché immobilier',
        'quartier'   => 'Focus sur un quartier',
        'juridique'  => 'Décryptage juridique',
        'temoignage' => 'Témoignage / cas client',
        'checklist'  => 'Checklist actionnable',
        'actualite'  => 'Actualité & tendances',
    ];

    private const CONSCIOUSNESS = [
        'probleme'  => 'Niveau Problème',
        'solution'  => 'Niveau Solution',
        'produit'   => 'Niveau Produit',
        'marque'    => 'Niveau Marque',
        'decision'  => 'Niveau Décision',
    ];

    private const CONSCIOUSNESS_HINT = [
        'probleme'  => 'Le lecteur sait qu\'il a un problème mais ne cherche pas encore à le résoudre. '
                     . 'Ton : éducatif, empathique. Ne pas vendre, créer la prise de conscience.',
        'solution'  => 'Le lecteur cherche activement des solutions. '
                     . 'Ton : comparatif, factuel, rassurant. Montrer les options.',
        'produit'   => 'Le lecteur compare les professionnels immobiliers. '
                     . 'Ton : preuves sociales, différenciateur, expertise démontrée.',
        'marque'    => 'Le lecteur connaît déjà le conseiller. '
                     . 'Ton : fidélisation, valeur ajoutée exclusive, avant-première.',
        'decision'  => 'Le lecteur est prêt à prendre contact. '
                     . 'Ton : urgence douce, levée des dernières objections, CTA fort.',
    ];

    private const OBJECTIFS = [
        'seo'        => 'Générer du trafic organique SEO',
        'lead'       => 'Capturer des leads qualifiés',
        'confiance'  => 'Bâtir la confiance et la crédibilité',
        'conversion' => 'Convertir en mandat ou estimation',
        'education'  => 'Éduquer le prospect sur le processus',
        'local'      => 'Renforcer la visibilité SEO locale',
    ];
}