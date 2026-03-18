<?php
/**
 * ============================================================
 *  AiPromptBuilder — Constructeur de prompts centralisé
 *  Fichier : core/ai/AiPromptBuilder.php
 * ============================================================
 *
 *  v2 — Contexte conseiller dynamique depuis la table advisor_context
 *
 *  Centralise :
 *    → Le contexte système DYNAMIQUE depuis la DB (advisor_context)
 *    → Les contextes spécialisés par module
 *    → Les templates de schemas JSON attendus
 *    → Les helpers partagés (extractJson, slug, withMarketData)
 *
 *  Usage :
 *    $system = AiPromptBuilder::context('articles');
 *    $prompt = AiPromptBuilder::json($instructions, $schemaJson);
 *    $parsed = AiPromptBuilder::extractJson($aiResponse);
 *    $slug   = AiPromptBuilder::slug("Acheter à Bordeaux");
 *    $prompt = AiPromptBuilder::withMarketData($prompt, $perplexityContent);
 * ============================================================
 */

declare(strict_types=1);

class AiPromptBuilder
{
    // ─── Cache du contexte conseiller (évite N requêtes DB par request) ───────
    private static ?array $_advisorCache = null;

    // =========================================================================
    //  CONTEXTE CONSEILLER DYNAMIQUE — depuis advisor_context en DB
    // =========================================================================

    /**
     * Charge les données conseiller depuis la DB avec cache statique.
     * Retourne un tableau associatif field_key => field_value.
     * En cas d'échec DB, retourne les valeurs par défaut hardcodées.
     *
     * @return array<string, string>
     */
    public static function getAdvisorData(): array
    {
        if (self::$_advisorCache !== null) {
            return self::$_advisorCache;
        }

        try {
            if (!class_exists('Database')) {
                return self::$_advisorCache = self::_defaultAdvisorData();
            }

            $db   = Database::getInstance();
            $stmt = $db->query("
                SELECT field_key, field_value
                FROM advisor_context
                WHERE field_value IS NOT NULL
                  AND field_value != ''
                ORDER BY section, sort_order
            ");

            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            if (empty($rows)) {
                return self::$_advisorCache = self::_defaultAdvisorData();
            }

            return self::$_advisorCache = $rows;

        } catch (Throwable $e) {
            // Silencieux — fallback sur les valeurs par défaut
            return self::$_advisorCache = self::_defaultAdvisorData();
        }
    }

    /**
     * Invalide le cache (à appeler après une sauvegarde dans le panneau admin).
     */
    public static function clearCache(): void
    {
        self::$_advisorCache = null;
    }

    /**
     * Construit le bloc "contexte de base" dynamique à partir des données DB.
     * Injecté en tête de TOUS les prompts système.
     *
     * @return string
     */
    public static function buildBaseContext(): string
    {
        $d = self::getAdvisorData();

        $name        = $d['advisor_name']       ?? 'Eduardo De Sul';
        $firstname   = $d['advisor_firstname']  ?? 'Eduardo';
        $network     = $d['advisor_network']    ?? 'eXp France';
        $city        = $d['advisor_city']       ?? 'Bordeaux';
        $zone        = $d['advisor_zone']       ?? 'Bordeaux et agglomération';
        $experience  = $d['advisor_experience'] ?? '';
        $specialties = $d['specialties']        ?? 'Résidentiel, investissement locatif';
        $services    = $d['services']           ?? 'Estimation, accompagnement vente et achat';
        $diff        = $d['differentiators']    ?? 'Conseiller indépendant, disponible 7j/7';
        $style       = $d['advisor_style']      ?? 'Chaleureux, expert, transparent';
        $signature   = $d['signature']          ?? "{$name} | Conseiller Immobilier | {$network} | {$city}";
        $tone        = $d['tone_of_voice']      ?? 'Professionnel et accessible';
        $rules       = $d['writing_rules']      ?? 'Phrases courtes, données chiffrées réelles, CTA clair';
        $forbidden   = $d['forbidden_words']    ?? '';
        $localRefs   = $d['local_references']   ?? $city;
        $market      = $d['market_overview']    ?? '';
        $hotAreas    = $d['hot_neighborhoods']  ?? '';
        $pricAppt    = $d['avg_price_apartment'] ?? '';
        $pricMaison  = $d['avg_price_house']     ?? '';

        $expLine = $experience ? "\nExpérience : {$experience}." : '';

        $marketBlock = '';
        if ($market || $hotAreas || $pricAppt) {
            $marketBlock = "\n\n## MARCHÉ IMMOBILIER LOCAL\n";
            if ($market)    $marketBlock .= "Situation : {$market}\n";
            if ($hotAreas)  $marketBlock .= "Secteurs porteurs : {$hotAreas}\n";
            if ($pricAppt)  $marketBlock .= "Prix appartements : {$pricAppt}\n";
            if ($pricMaison) $marketBlock .= "Prix maisons : {$pricMaison}\n";
        }

        $forbiddenBlock = '';
        if ($forbidden) {
            $forbiddenBlock = "\n\n## MOTS/EXPRESSIONS INTERDITS\nNe jamais utiliser : {$forbidden}";
        }

        return <<<PROMPT
## IDENTITÉ DU CONSEILLER
Tu travailles pour {$name}, conseiller immobilier indépendant avec le réseau {$network}.
Basé à {$city}.{$expLine}
Zone d'intervention : {$zone}

## SPÉCIALITÉS & SERVICES
Spécialités : {$specialties}
Services : {$services}
Ce qui distingue {$firstname} : {$diff}

## TON ET STYLE DE COMMUNICATION
{$tone}
Règles d'écriture : {$rules}
Signature à utiliser : {$signature}
Références locales à intégrer naturellement : {$localRefs}{$marketBlock}{$forbiddenBlock}

## RÈGLE ABSOLUE
Toujours répondre en français impeccable.
Toutes les données chiffrées mentionnées doivent être réelles ou clairement indiquées comme estimatives.
PROMPT;
    }

    // =========================================================================
    //  Contextes système par module
    //  Chaque module = persona spécialisée + contexte de base dynamique
    // =========================================================================
    private static array $_moduleContexts = [

        'articles' =>
            "## RÔLE : RÉDACTEUR EXPERT IMMOBILIER\n"
          . "Tu rédiges des articles de blog SEO pour le site du conseiller.\n"
          . "Framework MERE : Miroir (projection lecteur) → Émotion → Réassurance → Exclusivité.\n"
          . "Richesse sémantique cible : 50-70%. Données chiffrées réelles.\n"
          . "Toujours inclure des références locales : quartiers, transports, prix au m² réels.\n"
          . "Structure : H1 accrocheur → chapeau → H2 thématiques → FAQ → CTA.",

        'biens' =>
            "## RÔLE : EXPERT ANNONCES IMMOBILIÈRES\n"
          . "Tu rédiges des descriptions de biens immobiliers pour le site du conseiller.\n"
          . "Framework MERE : le bien doit faire se projeter l'acheteur dès la 1ère phrase.\n"
          . "Ton vendeur mais factuel : pas de superlatifs vides sans preuve concrète.\n"
          . "Mettre en avant : localisation précise, transports, commodités, vie de quartier.\n"
          . "Chaque phrase = bénéfice client, pas une liste de caractéristiques techniques.",

        'leads' =>
            "## RÔLE : ASSISTANT COMMERCIAL & CRM\n"
          . "Tu aides le conseiller à communiquer avec ses prospects et clients.\n"
          . "Approche : conseil personnalisé, humain, sans pression commerciale.\n"
          . "Toujours signer avec la signature définie dans le profil conseiller.\n"
          . "Objectif : créer de la confiance sur le long terme.",

        'seo' =>
            "## RÔLE : EXPERT SEO IMMOBILIER LOCAL\n"
          . "Tu optimises le référencement naturel du site du conseiller.\n"
          . "Objectif : top 3 Google sur les requêtes immobilières locales.\n"
          . "Tu maîtrises : SEO technique, sémantique TF-IDF, SEO local (GMB, NAP),\n"
          . "Core Web Vitals, Schema.org (RealEstateListing, LocalBusiness, FAQPage).\n"
          . "Recommandations : actionnables, prioritisées impact/effort.",

        'social' =>
            "## RÔLE : CRÉATEUR CONTENU SOCIAL MEDIA\n"
          . "Tu crées du contenu pour les réseaux sociaux du conseiller.\n"
          . "Ligne éditoriale : expertise accessible, conseils pratiques, transparence marché local.\n"
          . "Objectifs : notoriété locale, confiance, incitation au contact.\n"
          . "Adapter le format : longueur, emojis, hashtags, CTA selon la plateforme.",

        'gmb' =>
            "## RÔLE : EXPERT GOOGLE MY BUSINESS & SEO LOCAL\n"
          . "Tu optimises la présence locale du conseiller sur Google.\n"
          . "Objectif : apparaître dans le Local Pack Google (top 3).\n"
          . "Le conseiller prospecte aussi des professionnels B2B (notaires, architectes,\n"
          . "syndics, promoteurs, artisans) pour construire un réseau d'apporteurs.\n"
          . "Chaque contenu GMB = signal local + mot-clé immobilier + CTA clair.",

        'captures' =>
            "## RÔLE : EXPERT CONVERSION & COPYWRITING\n"
          . "Tu crées des pages de capture pour générer des leads qualifiés.\n"
          . "Framework AIDA + MERE pour maximiser les conversions.\n"
          . "Cibles : propriétaires souhaitant vendre, acheteurs primo-accédants, investisseurs.\n"
          . "Règle d'or : 1 page = 1 objectif = 1 CTA principal.\n"
          . "Leviers : preuve sociale, urgence douce, exclusivité, autorité d'expert.",
    ];

    // =========================================================================
    //  Récupérer un contexte système complet
    // =========================================================================
    /**
     * Retourne le system prompt complet pour un module donné.
     * = contexte de base dynamique (DB) + contexte spécialisé du module
     *
     * @param  string $module  ex: 'articles', 'leads', 'seo'
     * @return string
     */
    public static function context(string $module): string
    {
        $base     = self::buildBaseContext();
        $specific = self::$_moduleContexts[$module] ?? '';

        return trim($base . "\n\n" . $specific);
    }

    // =========================================================================
    //  Récupérer uniquement les données persona (pour NuroPersona, journal...)
    // =========================================================================
    /**
     * Retourne un bloc personas formaté pour injection dans un prompt.
     *
     * @return string
     */
    public static function personasContext(): string
    {
        $d = self::getAdvisorData();

        $seller   = $d['persona_seller']   ?? '';
        $buyer    = $d['persona_buyer']    ?? '';
        $investor = $d['persona_investor'] ?? '';

        if (!$seller && !$buyer && !$investor) {
            return '';
        }

        $block = "## PERSONAS CLIENTS DU CONSEILLER\n";
        if ($seller)   $block .= "Vendeur type : {$seller}\n";
        if ($buyer)    $block .= "Acheteur type : {$buyer}\n";
        if ($investor) $block .= "Investisseur type : {$investor}\n";

        return trim($block);
    }

    // =========================================================================
    //  Forcer un format JSON en sortie
    // =========================================================================
    public static function json(string $instructions, string $schemaJson): string
    {
        return $instructions
             . "\n\n---\n"
             . "**IMPORTANT — Format de réponse obligatoire :**\n"
             . "Réponds UNIQUEMENT avec un JSON valide respectant exactement cette structure.\n"
             . "Pas de texte avant, pas de texte après, pas de balises markdown.\n"
             . "```json\n{$schemaJson}\n```";
    }

    // =========================================================================
    //  Extraire le JSON d'une réponse IA
    // =========================================================================
    public static function extractJson(string $text): ?array
    {
        $text = trim($text);

        if (preg_match('/```json\s*([\s\S]*?)\s*```/i', $text, $m)) {
            $decoded = json_decode(trim($m[1]), true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        }

        if (preg_match('/```\s*([\s\S]*?)\s*```/', $text, $m)) {
            $decoded = json_decode(trim($m[1]), true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        }

        if (preg_match('/(\{[\s\S]*\})/u', $text, $m)) {
            $decoded = json_decode($m[1], true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        }

        if (preg_match('/(\[[\s\S]*\])/u', $text, $m)) {
            $decoded = json_decode($m[1], true);
            if (json_last_error() === JSON_ERROR_NONE) return $decoded;
        }

        return null;
    }

    // =========================================================================
    //  Slug SEO
    // =========================================================================
    public static function slug(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $map = [
            'à'=>'a','â'=>'a','ä'=>'a',
            'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
            'î'=>'i','ï'=>'i','ô'=>'o','ö'=>'o',
            'ù'=>'u','û'=>'u','ü'=>'u',
            'ç'=>'c','ñ'=>'n','æ'=>'ae','œ'=>'oe',
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9\s\-]/', '', $text);
        $text = preg_replace('/[\s\-]+/', '-', trim($text));
        return trim($text, '-');
    }

    // =========================================================================
    //  Enrichissement données marché Perplexity
    // =========================================================================
    public static function withMarketData(
        string $basePrompt,
        string $perplexityContent,
        int    $maxChars = 600
    ): string {
        if (empty(trim($perplexityContent))) return $basePrompt;

        $excerpt = substr($perplexityContent, 0, $maxChars);

        return $basePrompt
             . "\n\n---\n"
             . "**Données marché récentes (à intégrer naturellement dans ta réponse) :**\n"
             . $excerpt;
    }

    // =========================================================================
    //  Schémas JSON prédéfinis
    // =========================================================================
    public static function metaSchema(): string
    {
        return <<<JSON
{
  "meta_title": "... (50-60 caractères, mot-clé principal + marque)",
  "meta_description": "... (150-160 caractères, accrocheur + CTA implicite)",
  "slug": "...",
  "og_title": "... (60-70 caractères)",
  "og_description": "... (max 200 caractères)",
  "focus_keyword": "..."
}
JSON;
    }

    public static function faqSchema(int $count = 5): string
    {
        $items = implode(",\n    ", array_map(
            fn($i) => '{"question": "...", "answer": "... (2-4 phrases complètes, réponse directe)"}',
            range(1, $count)
        ));
        return "{\n  \"faq\": [\n    {$items}\n  ]\n}";
    }

    public static function emailSchema(): string
    {
        return <<<JSON
{
  "subject": "...",
  "preheader": "... (max 90 caractères)",
  "body_html": "... (HTML avec <p>, <strong>, <a>)",
  "body_text": "... (version texte brut)",
  "cta_text": "...",
  "cta_url": "{{URL_CALENDRIER}}",
  "ps": "..."
}
JSON;
    }

    // =========================================================================
    //  Valeurs par défaut (fallback si DB indisponible)
    // =========================================================================
    private static function _defaultAdvisorData(): array
    {
        return [
            'advisor_name'       => 'Eduardo De Sul',
            'advisor_firstname'  => 'Eduardo',
            'advisor_network'    => 'eXp France',
            'advisor_city'       => 'Bordeaux',
            'advisor_zone'       => 'Bordeaux et agglomération (Mérignac, Pessac, Talence, Gradignan, Bègles) et le Médoc',
            'specialties'        => 'Résidentiel, investissement locatif, primo-accession',
            'services'           => 'Estimation gratuite, accompagnement vente et achat, négociation',
            'differentiators'    => 'Indépendant, disponible 7j/7, réseau eXp France',
            'advisor_style'      => 'Chaleureux, expert, transparent, sans pression',
            'signature'          => 'Eduardo De Sul | Conseiller Immobilier | eXp France | Bordeaux',
            'tone_of_voice'      => 'Professionnel et accessible, ancré dans le quotidien bordelais',
            'writing_rules'      => 'Phrases courtes, données chiffrées réelles, Framework MERE, CTA clair',
            'local_references'   => 'Bordeaux UNESCO, tramway, gare Saint-Jean TGV, Garonne, Médoc',
        ];
    }
}