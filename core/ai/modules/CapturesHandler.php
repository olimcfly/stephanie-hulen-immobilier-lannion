<?php
/**
 * CapturesHandler — Module IA Pages de Capture
 * Actions : generate, headline, cta, thank_you, lead_magnet, popup, form_fields, ab_test
 */
declare(strict_types=1);
require_once __DIR__ . '/BaseHandler.php';

class CapturesHandler extends BaseHandler
{
    protected array $actions = ['generate','headline','cta','thank_you','lead_magnet','popup','form_fields','ab_test'];

    protected function handle_generate(array $input): void
    {
        $type     = $input['capture_type'] ?? 'estimation';
        $target   = $input['target'] ?? 'propriétaire souhaitant vendre';
        $offer    = trim($input['offer'] ?? '');
        $urgency  = $input['urgency'] ?? 'douce';
        $template = $input['template'] ?? 'moderne';

        $defaultOffers = ['estimation'=>'Estimation GRATUITE en 48h','achat'=>'Accédez aux biens AVANT leur mise en ligne','investissement'=>'Guide Investissement Bordeaux 2025 OFFERT','vendeur'=>'Vendez au meilleur prix en 90 jours'];
        $mainOffer = $offer ?: ($defaultOffers[$type] ?? $defaultOffers['estimation']);
        $urgencyPhrase = ['forte'=>'Offre valable jusqu\'au [DATE] · Places limitées','douce'=>'Réponse garantie sous 48h ouvrées','aucune'=>''][$urgency] ?? '';

        $schema = <<<JSON
{
  "meta": {"title":"...","description":"...","slug":"..."},
  "hero": {"headline":"...","subheadline":"...","cta_text":"...","trust_badge":"...","image_suggestion":"..."},
  "problem_section": {"title":"...","pain_points":["...","...","..."]},
  "solution_section": {"title":"...","benefits":[{"icon":"✅","title":"...","description":"..."}]},
  "social_proof": {"testimonials":[{"name":"Prénom N.","city":"Bordeaux","quote":"...","rating":5}],"stats":[{"number":"...","label":"..."}]},
  "form_section": {"title":"...","subtitle":"...","fields":[{"name":"first_name","label":"Prénom","type":"text","required":true,"placeholder":"..."},{"name":"email","label":"Email","type":"email","required":true,"placeholder":"..."}],"submit_text":"...","gdpr_text":"...","micro_commitment":"..."},
  "faq": [{"question":"...","answer":"..."}],
  "urgency_bar": "{$urgencyPhrase}"
}
JSON;

        $prompt = AiPromptBuilder::json("Page de capture immobilière complète.\n**Type** : {$type} | **Cible** : {$target}\n**Offre** : {$mainOffer} | **Template** : {$template}\nFramework MERE : Miroir → Émotion → Réassurance → Exclusivité.", $schema);

        $result = $this->generate($prompt, $this->context(), 4000, 0.8);
        $this->track('generate', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['capture_page' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_headline(array $input): void
    {
        $type   = $input['capture_type'] ?? 'estimation';
        $target = $input['target'] ?? 'propriétaire';
        $count  = (int)($input['count'] ?? 6);

        $schema = '{"headlines":[{"text":"...","formula":"question|affirmation|promesse|fomo|benefice_delai","emotional_trigger":"...","estimated_conversion":"haute|moyenne|faible"}],"best_pick":"...","testing_recommendation":"..."}';

        $prompt = AiPromptBuilder::json("{$count} accroches page de capture immobilière.\n**Type** : {$type} | **Cible** : {$target}\nFormules variées : question directe, affirmation chiffrée, promesse résultat, FOMO, bénéfice+délai.", $schema);

        $result = $this->generate($prompt, $this->context(), 1500, 0.9);
        $this->track('headline', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['headlines' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_cta(array $input): void
    {
        $objective = trim($input['objective'] ?? 'obtenir une estimation');
        $stage     = $input['funnel_stage'] ?? 'consideration';
        $count     = (int)($input['count'] ?? 8);

        $schema = '{"ctas":[{"text":"...","micro_commitment_level":"faible|moyen|fort","color_suggestion":"vert|rouge|orange|bleu|or","sub_text":"...","best_for":"..."}],"recommended_primary":"...","a_b_test_pair":["...","..."]}';

        $prompt = AiPromptBuilder::json("{$count} boutons CTA pour : \"{$objective}\"\nStade funnel : {$stage}\nRègles : verbe 1ère personne (\"Je veux...\"), spécifique, 3-7 mots, mentionner gratuité si applicable.", $schema);

        $result = $this->generate($prompt, $this->context(), 1200, 0.85);
        $this->track('cta', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['ctas' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_thank_you(array $input): void
    {
        $type      = $input['capture_type'] ?? 'estimation';
        $firstName = trim($input['first_name'] ?? '{{PRENOM}}');
        $defaults  = ['estimation'=>'Eduardo vous contacte sous 48h ouvrées','achat'=>'Eduardo vous envoie une sélection de biens','guide'=>'Votre guide PDF est dans votre email'];
        $nextStep  = trim($input['next_step'] ?? ($defaults[$type] ?? $defaults['estimation']));

        $schema = '{"headline":"...","confirmation_message":"...","next_steps":[{"step":1,"icon":"📋","title":"...","description":"..."},{"step":2,"icon":"📞","title":"...","description":"..."},{"step":3,"icon":"🏡","title":"...","description":"..."}],"upsell_offer":{"title":"...","description":"...","cta":"..."},"eduardo_bio":"..."}';

        $prompt = AiPromptBuilder::json("Page de remerciement post-formulaire pour Eduardo De Sul.\n**Prénom** : {$firstName} | **Type** : {$type}\n**Prochaine étape** : {$nextStep}\nRéduire l'anxiété post-soumission. Expliquer la suite en 3 étapes. Proposer une action complémentaire.", $schema);

        $result = $this->generate($prompt, $this->context(), 2000, 0.75);
        $this->track('thank_you', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['thank_you_page' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_lead_magnet(array $input): void
    {
        $type  = $input['magnet_type'] ?? 'guide';
        $topic = trim($input['topic'] ?? 'Vendre son bien immobilier à Bordeaux');
        $pages = (int)($input['pages'] ?? 10);

        $schema = '{"title":"...","subtitle":"...","table_of_contents":[{"chapter":1,"title":"...","content_summary":"..."}],"key_takeaways":["...","..."],"content_sections":[{"title":"...","content":"... (200-300 mots)","expert_tip":"..."}],"conclusion_cta":"...","landing_page_pitch":"..."}';

        $prompt = AiPromptBuilder::json("Lead magnet immobilier premium.\n**Type** : {$type} | **Sujet** : {$topic} | **Longueur** : ~{$pages} pages\nValeur immédiate + positionner Eduardo comme expert + préparer naturellement à faire appel à ses services.", $schema);

        $result = $this->generate($prompt, $this->context(), 4000, 0.75);
        $this->track('lead_magnet', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['lead_magnet' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_popup(array $input): void
    {
        $trigger   = $input['trigger'] ?? 'exit_intent';
        $pageType  = $input['page_type'] ?? 'article';
        $offer     = trim($input['offer'] ?? '');
        $defaults  = ['article'=>'Guide PDF GRATUIT : 7 erreurs à éviter','bien'=>'Recevez les nouvelles annonces en avant-première','accueil'=>'Estimation GRATUITE en 48h'];
        $mainOffer = $offer ?: ($defaults[$pageType] ?? $defaults['accueil']);

        $schema = '{"headline":"...","subheadline":"...","offer_description":"...","form_fields":["email","prénom"],"cta_text":"...","dismiss_text":"... (lien fermeture avec coût psychologique)","urgency_element":"...","mobile_version":{"headline":"...","cta_text":"..."},"a_b_variants":[{"element":"headline","variant_b":"..."}]}';

        $prompt = AiPromptBuilder::json("Popup {$trigger} pour page {$pageType} immobilière.\n**Offre** : {$mainOffer}", $schema);

        $result = $this->generate($prompt, $this->context(), 1200, 0.85);
        $this->track('popup', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['popup' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_form_fields(array $input): void
    {
        $type      = $input['capture_type'] ?? 'estimation';
        $objective = trim($input['objective'] ?? '');
        $maxFields = (int)($input['max_fields'] ?? 5);

        $schema = '{"fields":[{"order":1,"name":"first_name","label":"...","type":"text|email|tel|select|radio|checkbox","required":true,"placeholder":"...","micro_copy":"...","conversion_impact":"critique|important|utile"}],"progressive_profiling":{"step1_fields":["..."],"step2_fields":["..."]},"gdpr_text":"...","form_title":"...","submit_button":"...","conversion_tips":["..."]}';

        $prompt = AiPromptBuilder::json("Optimise les champs formulaire pour max de conversions.\n**Type** : {$type} | **Objectif** : {$objective} | **Max champs** : {$maxFields}\nRègles : moins de champs = plus de conversions. Ordre psychologiquement optimal. Micro-copy rassurant.", $schema);

        $result = $this->generate($prompt, $this->context(), 1200, 0.5);
        $this->track('form_fields', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['form_optimization' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_ab_test(array $input): void
    {
        $this->require($input, 'current_content');
        $current    = trim($input['current_content']);
        $element    = $input['element'] ?? 'headline';
        $hypothesis = trim($input['hypothesis'] ?? '');

        $schema = '{"control":{"version":"A","element":"'.$element.'","content":"..."},"variants":[{"version":"B","content":"...","change":"...","hypothesis":"...","expected_impact":"..."},{"version":"C","content":"...","change":"...","hypothesis":"...","expected_impact":"..."}],"test_setup":{"minimum_sample_size":500,"test_duration_days":14,"primary_metric":"taux_de_conversion","statistical_significance":"95%"},"implementation_notes":"..."}';

        $prompt = AiPromptBuilder::json("Variantes A/B pour tester l'élément \"{$element}\".\n**Contrôle (A)** : {$current}\n**Hypothèse** : {$hypothesis}", $schema);

        $result = $this->generate($prompt, $this->context(), 1500, 0.8);
        $this->track('ab_test', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['ab_test' => $parsed] : ['raw' => $result['content']]);
    }
}