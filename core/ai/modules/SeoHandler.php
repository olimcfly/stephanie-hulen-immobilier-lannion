<?php
/**
 * SeoHandler — Module IA SEO
 * Actions : analyze, semantic, schema, local_seo, audit, internal_links, serp_snippet, competitor
 */
declare(strict_types=1);
require_once __DIR__ . '/BaseHandler.php';

class SeoHandler extends BaseHandler
{
    protected array $actions = ['analyze','semantic','schema','local_seo','audit','internal_links','serp_snippet','competitor'];

    protected function handle_analyze(array $input): void
    {
        $title     = trim($input['title'] ?? '');
        $content   = trim($input['content'] ?? '');
        $metaTitle = trim($input['meta_title'] ?? '');
        $metaDesc  = trim($input['meta_description'] ?? '');
        $targetKw  = trim($input['target_keyword'] ?? '');
        if (empty($content) && empty($title)) $this->fail('Contenu ou titre requis');
        $preview   = substr(strip_tags($content), 0, 2000);
        $wordCount = str_word_count(strip_tags($content));

        $schema = <<<JSON
{"global_score":0,"dimensions":{"title_tag":{"score":0,"issues":["..."],"recommendations":["..."]},"meta_description":{"score":0,"issues":[],"recommendations":[]},"content_quality":{"score":0,"word_count":{$wordCount},"keyword_density":"0%","recommendations":[]},"semantic_richness":{"score":0,"missing_semantic_fields":["..."],"recommendations":[]},"structure":{"score":0,"recommendations":[]},"local_seo":{"score":0,"geo_signals_present":["..."],"missing_signals":["..."]},"schema_markup":{"score":0,"schema_recommended":[]}},"quick_wins":[{"action":"...","impact":"élevé|moyen|faible","effort":"faible|moyen|élevé"}],"priority_actions":["...","...","..."]}
JSON;

        $prompt = AiPromptBuilder::json("Analyse SEO complète.\n**H1**: {$title}\n**Meta title**: {$metaTitle}\n**Meta desc**: {$metaDesc}\n**Mot-clé cible**: {$targetKw}\n**Mots**: {$wordCount}\n**Contenu**: {$preview}", $schema);

        $result = $this->generate($prompt, $this->context(), 2000, 0.3);
        $this->track('analyze', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['seo_analysis' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_semantic(array $input): void
    {
        $content  = trim($input['content'] ?? '');
        $topic    = trim($input['topic'] ?? '');
        $targetKw = trim($input['target_keyword'] ?? '');
        if (empty($content) && empty($topic)) $this->fail('Contenu ou thématique requis');
        $source = !empty($content) ? substr(strip_tags($content), 0, 2000) : $topic;

        $schema = <<<JSON
{"semantic_richness_score":0,"semantic_field":{"core_terms":["..."],"missing_terms":["..."],"overused_terms":["..."]},"named_entities":{"places":["Bordeaux","..."],"organizations":["eXp France","..."],"concepts":["DPE","..."]},"people_also_ask":[{"question":"...","answer_hint":"..."}],"tfidf_important":["..."],"content_gaps":[{"gap":"...","suggested_content":"...","section":"..."}],"enriched_intro":"..."}
JSON;

        $prompt = AiPromptBuilder::json("Analyse sémantique pour optimiser ce contenu immobilier.\n**Mot-clé cible**: {$targetKw}\n**Contenu**: {$source}", $schema);

        $result = $this->generate($prompt, $this->context(), 2000, 0.4);
        $this->track('semantic', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['semantic_analysis' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_schema(array $input): void
    {
        $type = $input['schema_type'] ?? 'Article';
        $data = $input['data'] ?? [];
        $url  = trim($input['url'] ?? '');
        $siteUrl = 'https://www.immolocal-bordeaux.fr';

        $schema = match($type) {
            'LocalBusiness' => ['@context'=>'https://schema.org','@type'=>'RealEstateAgent','name'=>'Eduardo De Sul | Conseiller Immobilier Bordeaux','url'=>$siteUrl,'address'=>['@type'=>'PostalAddress','addressLocality'=>'Bordeaux','postalCode'=>'33000','addressCountry'=>'FR'],'areaServed'=>['Bordeaux','Mérignac','Pessac','Talence']],
            'FAQPage' => ['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>array_map(fn($f)=>['@type'=>'Question','name'=>$f['question']??'','acceptedAnswer'=>['@type'=>'Answer','text'=>$f['answer']??'']],$data['faq']??[])],
            default => ['@context'=>'https://schema.org','@type'=>'Article','headline'=>$data['title']??'','url'=>$url,'author'=>['@type'=>'Person','name'=>'Eduardo De Sul'],'publisher'=>['@type'=>'Organization','name'=>'Eduardo De Sul Immobilier','url'=>$siteUrl]],
        };

        $this->success(['schema'=>$schema,'script_tag'=>'<script type="application/ld+json">'.json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>']);
    }

    protected function handle_local_seo(array $input): void
    {
        $zone     = trim($input['zone'] ?? 'Bordeaux');
        $services = trim($input['services'] ?? 'achat vente estimation');

        $schema = <<<JSON
{"gmb_optimization":{"categories":["Agence immobilière"],"posts_frequency":"...","photos_strategy":"..."},"local_keywords":[{"keyword":"...","volume_estimated":"...","competition":"faible|moyenne|élevée","priority":1}],"citation_building":{"priority_directories":["Pages Jaunes","Logic-Immo","SeLoger Pro"]},"review_strategy":{"platforms":["Google"],"request_timing":"...","response_template":"..."},"monthly_actions":["...","...","..."]}
JSON;

        $prompt = AiPromptBuilder::json("Stratégie SEO local pour Eduardo De Sul, conseiller immobilier à {$zone}.\n**Services** : {$services}", $schema);

        $result = $this->generate($prompt, $this->context(), 2500, 0.4);
        $this->track('local_seo', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['local_seo_strategy' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_audit(array $input): void
    {
        $this->require($input, 'url');
        $url = trim($input['url']);
        if (!filter_var($url, FILTER_VALIDATE_URL)) $this->fail('URL invalide');

        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>15,CURLOPT_HEADER=>true,CURLOPT_USERAGENT=>'Mozilla/5.0']);
        $response  = curl_exec($ch);
        $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $loadTime  = round((float)curl_getinfo($ch, CURLINFO_TOTAL_TIME), 2);
        $pageSize  = round((int)curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD) / 1024, 1);
        curl_close($ch);

        if ($httpCode !== 200) $this->fail("Page inaccessible (HTTP {$httpCode})");

        $html = substr($response, strpos($response, "\r\n\r\n") + 4);
        preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $tM);
        preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/si', $html, $dM);
        preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $h1M);
        preg_match_all('/<h2/si', $html, $h2M);
        preg_match_all('/<img[^>]+>/si', $html, $imgM);

        $title     = strip_tags($tM[1] ?? '');
        $desc      = $dM[1] ?? '';
        $wordCount = str_word_count(strip_tags($html));
        $score     = 100;
        $issues    = [];

        if (mb_strlen($title) < 30 || mb_strlen($title) > 65) { $issues[] = "Title hors normes (".mb_strlen($title)." car.)"; $score -= 10; }
        if (mb_strlen($desc) < 120 || mb_strlen($desc) > 165)  { $issues[] = "Meta description hors normes"; $score -= 10; }
        if (empty($h1M[1]))    { $issues[] = 'H1 manquant'; $score -= 15; }
        if ($wordCount < 500)  { $issues[] = "Contenu mince ({$wordCount} mots)"; $score -= 15; }
        if (!str_contains($html, 'application/ld+json')) { $issues[] = 'Aucun Schema.org'; $score -= 10; }
        if ($loadTime > 3)     { $issues[] = "Temps de chargement lent ({$loadTime}s)"; $score -= 10; }

        $this->success(['audit' => compact('url','httpCode','loadTime','pageSize','title','desc','wordCount','issues') + ['score' => max(0,$score), 'h2_count' => count($h2M[0]??[]), 'total_images' => count($imgM[0]??[])]]);
    }

    protected function handle_internal_links(array $input): void
    {
        $this->require($input, 'current_page');
        $currentPage = trim($input['current_page']);
        $allPages    = $input['all_pages'] ?? [];
        $pagesStr    = implode("\n", array_map(fn($p) => "- {$p['title']} → /{$p['slug']}", array_slice($allPages, 0, 30)));

        $schema = '{"outgoing_links":[{"target_page":"...","anchor_text":"...","context_sentence":"...","relevance":"haute|moyenne|faible","link_type":"contextuel|CTA"}],"link_count_recommended":3,"anchor_diversity_tips":["..."]}';

        $prompt = AiPromptBuilder::json("Suggestions maillage interne pour cette page immobilière.\n**Pages disponibles** :\n{$pagesStr}\n**Contenu** : ".substr($currentPage,0,1000), $schema);

        $result = $this->generate($prompt, $this->context(), 1500, 0.4);
        $this->track('internal_links', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['internal_links' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_serp_snippet(array $input): void
    {
        $question = trim($input['question'] ?? $input['keyword'] ?? '');
        if (empty($question)) $this->fail('Question ou mot-clé requis');
        $content  = trim($input['content'] ?? '');

        $schema = '{"snippet_type":"paragraph|list|table","optimized_answer":"... (40-60 mots, réponse directe)","optimized_list":["..."],"h_tag_suggestion":"...","content_to_add":"..."}';

        $prompt = AiPromptBuilder::json("Optimise pour Featured Snippet (position zéro).\n**Requête** : {$question}\n**Contenu** : {$content}", $schema);

        $result = $this->generate($prompt, $this->context(), 1000, 0.4);
        $this->track('serp_snippet', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['serp_snippet' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_competitor(array $input): void
    {
        $this->require($input, 'keyword');
        $keyword  = trim($input['keyword']);
        $location = trim($input['location'] ?? 'Bordeaux');

        $result = $this->client->perplexity("Analyse SEO concurrents immobiliers sur '{$keyword} {$location}'. Quels sites dominent ? Stratégie contenu ? Comment Eduardo De Sul les surpasser ?");
        if (!$result['success']) $result = $this->generate("Analyse concurrentielle SEO '{$keyword} {$location}'. Acteurs dominants, stratégies gagnantes, opportunités conseiller indépendant.", "Expert SEO immobilier France.", 1200);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $this->success(['competitor_analysis' => $result['content'], 'keyword' => $keyword, 'location' => $location]);
    }
}