<?php
/**
 * BiensHandler — Module IA Biens Immobiliers
 * Actions : description, highlight, price_analysis, pitch, email_buyer, translate, schema, comparable
 */
declare(strict_types=1);
require_once __DIR__ . '/BaseHandler.php';

class BiensHandler extends BaseHandler
{
    protected array $actions = [
        'description', 'highlight', 'price_analysis',
        'pitch', 'email_buyer', 'translate', 'schema', 'comparable',
    ];

    protected function handle_description(array $input): void
    {
        $type        = $input['type'] ?? 'appartement';
        $surface     = (int)($input['surface'] ?? 0);
        $rooms       = (int)($input['rooms'] ?? 0);
        $bedrooms    = (int)($input['bedrooms'] ?? 0);
        $price       = (float)($input['price'] ?? 0);
        $address     = trim($input['address'] ?? '');
        $features    = is_array($input['features'] ?? null) ? implode(', ', $input['features']) : '';
        $transaction = $input['transaction'] ?? 'vente';
        $style       = $input['style'] ?? 'standard';

        $priceStr = $price > 0 ? number_format($price, 0, ',', ' ') . ' €' : 'Prix sur demande';
        $styleInstr = match($style) {
            'premium'      => 'Ton luxueux et raffiné, vocabulaire haut de gamme',
            'investisseur' => 'Focalise sur rentabilité, rendement locatif, potentiel fiscal',
            default        => 'Ton accessible et chaleureux, large public',
        };

        $schema = <<<JSON
{
  "accroche": "... (1 phrase, max 20 mots)",
  "description_principale": "... (150-200 mots, pièce par pièce)",
  "atouts_quartier": "... (50-70 mots)",
  "infos_pratiques": "...",
  "cta": "...",
  "description_complete": "... (HTML assemblé)",
  "tags_seo": ["...", "..."]
}
JSON;

        $prompt = AiPromptBuilder::json(
            "Rédige une description d'annonce immobilière.
**Bien** : {$type} en {$transaction}, {$surface}m², {$rooms} pièces ({$bedrooms} ch.), {$priceStr}, {$address}
**Prestations** : {$features}
**Style** : {$styleInstr}
Framework MERE : Miroir → Émotion → Réassurance → Exclusivité.",
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 1500, 0.8);
        $this->track('description', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success(['description' => $parsed ?? ['description_complete' => $result['content']]]);
    }

    protected function handle_highlight(array $input): void
    {
        $features    = is_array($input['features'] ?? null) ? implode(', ', $input['features']) : '';
        $type        = $input['type'] ?? 'appartement';
        $address     = trim($input['address'] ?? '');
        $surface     = (int)($input['surface'] ?? 0);
        $description = trim($input['description'] ?? '');
        $source      = $description ?: "{$type} {$surface}m² à {$address}. Prestations : {$features}";

        $schema = <<<JSON
{
  "highlights": [
    {"icon":"🌞","title":"... (max 10 mots)","detail":"... (1 phrase bénéfice client)"},
    {"icon":"🚇","title":"...","detail":"..."},
    {"icon":"🏡","title":"...","detail":"..."},
    {"icon":"💰","title":"...","detail":"..."},
    {"icon":"🎯","title":"...","detail":"..."}
  ],
  "tagline": "... (slogan 1 phrase)"
}
JSON;

        $prompt = AiPromptBuilder::json(
            "Identifie les 5 points forts vendeurs de ce bien. Chaque point = bénéfice client (pas feature technique).\n**Bien** : {$source}",
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 800, 0.7);
        $this->track('highlight', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['highlights' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_price_analysis(array $input): void
    {
        $price     = (float)($input['price'] ?? 0);
        $surface   = (int)($input['surface'] ?? 0);
        $type      = $input['type'] ?? 'appartement';
        $address   = trim($input['address'] ?? '');
        $condition = $input['condition'] ?? 'bon état';

        if ($price === 0.0 || $surface === 0) $this->fail('Prix et surface requis');

        $prixM2 = round($price / $surface, 0);

        // Enrichir avec Perplexity
        $marketNote = '';
        $px = $this->client->perplexity("Prix m² {$type} {$address} Bordeaux {$condition} 2025. Données récentes.");
        if ($px['success']) $marketNote = substr($px['content'], 0, 400);

        $schema = <<<JSON
{
  "prix_m2_bien": {$prixM2},
  "prix_m2_marche_estime": 0,
  "evaluation": "sous-évalué|bien positionné|surévalué",
  "ecart_pourcentage": 0,
  "fourchette_recommandee": {"min": 0, "max": 0},
  "argumentation_vendeur": ["...", "..."],
  "risques": ["...", "..."],
  "strategie_negociation": "...",
  "delai_vente_estime": "... mois",
  "conseil_eduardo": "..."
}
JSON;

        $prompt = AiPromptBuilder::json(
            "Analyse le positionnement prix de ce bien.
**{$type}** {$surface}m² à {$address} — Prix : {$price}€ ({$prixM2}€/m²) — État : {$condition}
" . ($marketNote ? "\n**Données marché** : {$marketNote}" : ''),
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 1500, 0.3);
        $this->track('price_analysis', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['analysis' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_pitch(array $input): void
    {
        $type        = $input['type'] ?? 'appartement';
        $address     = trim($input['address'] ?? '');
        $price       = (float)($input['price'] ?? 0);
        $surface     = (int)($input['surface'] ?? 0);
        $description = trim($input['description'] ?? '');
        $audience    = $input['audience'] ?? 'acheteur particulier';

        $schema = <<<JSON
{
  "pitch_oral": "... (70-80 mots, 30 secondes)",
  "pitch_ecrit": "... (max 160 caractères)",
  "pitch_linkedin": "... (2-3 phrases + hashtags)",
  "objections_courantes": [{"objection":"...","reponse":"..."}],
  "questions_de_qualification": ["...", "..."]
}
JSON;

        $prompt = AiPromptBuilder::json(
            "Rédige un pitch commercial pour ce bien.
**Public** : {$audience} | **Bien** : {$type} {$surface}m² à {$address}, {$price}€
**Contexte** : {$description}",
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 1200, 0.8);
        $this->track('pitch', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['pitch' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_email_buyer(array $input): void
    {
        $buyerName   = trim($input['buyer_name'] ?? 'Madame, Monsieur');
        $criteria    = trim($input['criteria'] ?? '');
        $type        = $input['type'] ?? 'appartement';
        $address     = trim($input['address'] ?? '');
        $price       = (float)($input['price'] ?? 0);
        $surface     = (int)($input['surface'] ?? 0);
        $description = trim($input['description'] ?? '');

        $priceStr = $price > 0 ? number_format($price, 0, ',', ' ') . ' €' : 'Prix sur demande';

        $schema = '{"subject":"...","body":"... (HTML)","text_version":"...","ps":"..."}';

        $prompt = AiPromptBuilder::json(
            "Rédige un email de présentation de bien à un acheteur potentiel.
**Destinataire** : {$buyerName} | **Critères** : {$criteria}
**Bien** : {$type} {$surface}m² à {$address}, {$priceStr}
**Description** : {$description}
Email chaleureux + professionnel, relier au critères, créer urgence sans pression, proposer visite, signature Eduardo De Sul eXp France.",
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 1200, 0.7);
        $this->track('email_buyer', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['email' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_translate(array $input): void
    {
        $this->require($input, 'description');
        $description = trim($input['description']);
        $targetLang  = $input['lang'] ?? 'en';
        $langNames   = ['en'=>'English','es'=>'Spanish','de'=>'German','it'=>'Italian','pt'=>'Portuguese'];
        $langName    = $langNames[$targetLang] ?? 'English';

        $system = "You are a professional real estate translator. Translate French property descriptions into natural, engaging {$langName}. Keep selling tone and local Bordeaux references.";
        $prompt = "Translate to {$langName}:\n\n{$description}";

        $result = $this->generate($prompt, $system, 1500);
        $this->track('translate', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $this->success(['translation' => $result['content'], 'target_language' => $langName]);
    }

    protected function handle_schema(array $input): void
    {
        $type        = $input['type'] ?? 'appartement';
        $address     = trim($input['address'] ?? '');
        $price       = (float)($input['price'] ?? 0);
        $surface     = (int)($input['surface'] ?? 0);
        $rooms       = (int)($input['rooms'] ?? 0);
        $description = trim($input['description'] ?? '');
        $url         = trim($input['url'] ?? '');
        $imageUrl    = trim($input['image_url'] ?? '');

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'RealEstateListing',
            'name'        => "{$type} à {$address}",
            'description' => strip_tags($description),
            'url'         => $url,
            'price'       => $price,
            'priceCurrency' => 'EUR',
            'floorSize'   => ['@type' => 'QuantitativeValue', 'value' => $surface, 'unitCode' => 'MTK'],
            'numberOfRooms' => $rooms,
            'address'     => ['@type' => 'PostalAddress', 'addressLocality' => $address, 'addressCountry' => 'FR'],
            'offers'      => [
                '@type' => 'Offer', 'price' => $price, 'priceCurrency' => 'EUR',
                'availability' => 'https://schema.org/InStock',
                'seller' => ['@type' => 'RealEstateAgent', 'name' => 'Eduardo De Sul'],
            ],
        ];

        if (!empty($imageUrl)) $schema['image'] = $imageUrl;

        $this->success([
            'schema'      => $schema,
            'script_tag'  => '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>',
        ]);
    }

    protected function handle_comparable(array $input): void
    {
        $type    = $input['type'] ?? 'appartement';
        $address = trim($input['address'] ?? '');
        $surface = (int)($input['surface'] ?? 0);
        $price   = (float)($input['price'] ?? 0);

        $query  = "Biens comparables vendus : {$type} {$surface}m² à {$address} Bordeaux. Prix analysé : {$price}€. Fourchettes, délais de vente, tendances 2025.";
        $result = $this->client->perplexity($query);

        if (!$result['success']) {
            $result = $this->generate($query . " Base-toi sur tes connaissances du marché bordelais.", "Expert marché immobilier Bordeaux.", 1000);
        }

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $this->success(['comparable_analysis' => $result['content'], 'citations' => $result['citations'] ?? []]);
    }
}