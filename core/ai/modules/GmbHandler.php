<?php
/**
 * GmbHandler — Module IA Google My Business
 * Actions : post, review_reply, description, b2b_prospect, b2b_sequence, qa, audit, report
 */
declare(strict_types=1);
require_once __DIR__ . '/BaseHandler.php';

class GmbHandler extends BaseHandler
{
    protected array $actions = ['post','review_reply','description','b2b_prospect','b2b_sequence','qa','audit','report'];

    protected function handle_post(array $input): void
    {
        $type     = $input['post_type'] ?? 'actualite';
        $topic    = trim($input['topic'] ?? '');
        $ctaType  = $input['cta_type'] ?? 'en_savoir_plus';
        $ctaLabel = ['en_savoir_plus'=>'En savoir plus','appeler'=>'Appeler','reserver'=>'Réserver','inscription'=>"S'inscrire"][$ctaType] ?? 'En savoir plus';

        $schema = '{"post_text":"...","char_count":0,"preview_100":"...","cta_button":"'.$ctaLabel.'","cta_url":"{{URL}}","image_description":"...","keywords_included":["..."]}';

        $prompt = AiPromptBuilder::json("Post GMB pour Eduardo De Sul.\n**Type** : {$type}\n**Sujet** : {$topic}\n**CTA** : {$ctaLabel}\nMax 1500 car., 100 premiers caractères cruciaux, pas de hashtags, mots-clés locaux naturels.", $schema);

        $result = $this->generate($prompt, $this->context(), 1000, 0.75);
        $this->track('post', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['gmb_post' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_review_reply(array $input): void
    {
        $this->require($input, 'review_text');
        $reviewText    = trim($input['review_text']);
        $rating        = (int)($input['rating'] ?? 5);
        $reviewerName  = trim($input['reviewer_name'] ?? 'ce client');

        $sentiment = $rating >= 4 ? 'positif' : ($rating === 3 ? 'neutre' : 'négatif');

        $schema = '{"reply":"...","char_count":0,"tone":"...","follow_up_action":"...","alternative_reply":"..."}';

        $prompt = AiPromptBuilder::json("Réponse à un avis Google ({$rating}/5 étoiles) de {$reviewerName}.\n**Avis** : \"{$reviewText}\"\n**Sentiment** : {$sentiment}\nRemercier toujours. Jamais défensif. 100-200 mots. Signature : Eduardo De Sul | eXp France Bordeaux.", $schema);

        $result = $this->generate($prompt, $this->context(), 800, 0.7);
        $this->track('review_reply', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['review_reply' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_description(array $input): void
    {
        $services = trim($input['services'] ?? 'achat, vente, estimation, investissement locatif');
        $zones    = trim($input['zones'] ?? 'Bordeaux, Mérignac, Pessac, Talence, Gradignan');

        $schema = '{"description":"...","char_count":0,"keywords_included":["..."],"version_courte":"... (250 car.)"}';

        $prompt = AiPromptBuilder::json("Description Google My Business pour Eduardo De Sul.\n**Services** : {$services}\n**Zones** : {$zones}\nMax 750 car., mots-clés locaux naturels, CTA à la fin.", $schema);

        $result = $this->generate($prompt, $this->context(), 800, 0.6);
        $this->track('description', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['gmb_description' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_b2b_prospect(array $input): void
    {
        $prospectType = $input['prospect_type'] ?? 'notaire';
        $businessName = trim($input['business_name'] ?? '');
        $contactName  = trim($input['contact_name'] ?? '');
        $channel      = $input['channel'] ?? 'email';

        $angles = ['notaire'=>'partenariat apporteur affaires, recommandation mutuelle','architecte'=>'vente post-rénovation, valorisation bien','syndic'=>'mandats copropriétés','promoteur'=>'terrain à acquérir','artisan'=>'réseau de confiance acheteurs'];
        $angle  = $angles[$prospectType] ?? 'partenariat et recommandation mutuelle';

        $schema = '{"subject":"...","message":"...","char_count":0,"hook":"...","value_proposition":"...","cta":"...","follow_up_delay":"..."}';

        $prompt = AiPromptBuilder::json("Message de prospection B2B ({$channel}) d'Eduardo De Sul vers ce {$prospectType}.\n**Entreprise** : {$businessName}\n**Contact** : {$contactName}\n**Angle** : {$angle}\nApproche : apporter de la valeur AVANT de demander. CTA : appel 15 min ou déjeuner découverte.", $schema);

        $result = $this->generate($prompt, $this->context(), 1000, 0.75);
        $this->track('b2b_prospect', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['b2b_message' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_b2b_sequence(array $input): void
    {
        $prospectType = $input['prospect_type'] ?? 'notaire';
        $touchpoints  = (int)($input['touchpoints'] ?? 4);

        $schema = '{"sequence":[{"step":1,"channel":"linkedin|email|appel|gmb_message","timing":"J+0","objective":"...","message_template":"...","value_delivered":"..."}],"b2b_value_props":["..."],"exit_criteria":"..."}';

        $prompt = AiPromptBuilder::json("Séquence prospection B2B multi-canal {$touchpoints} points de contact pour {$prospectType}s bordelais.\nCanaux : LinkedIn, Email, Appel, Message GMB. Chaque touchpoint apporte une valeur différente.", $schema);

        $result = $this->generate($prompt, $this->context(), 2500, 0.7);
        $this->track('b2b_sequence', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['b2b_sequence' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_qa(array $input): void
    {
        $count    = (int)($input['count'] ?? 10);
        $services = trim($input['services'] ?? 'achat vente estimation Bordeaux');

        $schema = '{"qa_pairs":[{"question":"...","answer":"... (50-200 mots, inclure Bordeaux + services + CTA doux)","keywords":["..."],"priority":"haute|moyenne"}]}';

        $prompt = AiPromptBuilder::json("{$count} Q&R pour la section Questions/Réponses GMB de Eduardo De Sul.\n**Services** : {$services}\nOptimisées pour recherches locales.", $schema);

        $result = $this->generate($prompt, $this->context(), 2000, 0.5);
        $this->track('qa', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['gmb_qa' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_audit(array $input): void
    {
        $gmbData = json_encode($input['gmb_data'] ?? [], JSON_UNESCAPED_UNICODE);

        $schema = '{"global_score":0,"dimensions":{"informations_de_base":{"score":0,"missing":["..."],"recommendations":["..."]},"description":{"score":0},"categories":{"score":0},"photos":{"score":0,"count":0},"avis":{"score":0,"count":0,"average_rating":0},"posts":{"score":0},"qa":{"score":0,"count":0}},"priority_actions":[{"action":"...","impact":"élevé|moyen","effort":"15min|1h"}]}';

        $prompt = AiPromptBuilder::json("Audit complet fiche Google My Business immobilière.\n**Données** : {$gmbData}", $schema);

        $result = $this->generate($prompt, $this->context(), 2000, 0.3);
        $this->track('audit', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['gmb_audit' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_report(array $input): void
    {
        $metrics  = json_encode($input['metrics'] ?? [], JSON_UNESCAPED_UNICODE);
        $prevData = json_encode($input['previous_month'] ?? [], JSON_UNESCAPED_UNICODE);
        $month    = trim($input['month'] ?? date('F Y'));

        $schema = '{"report":{"title":"Rapport GMB - '.$month.'","executive_summary":"...","highlights":["..."],"concerns":["..."],"next_month_actions":["...","...","..."]}}';

        $prompt = AiPromptBuilder::json("Rapport mensuel GMB pour Eduardo De Sul.\n**Mois** : {$month}\n**Métriques** : {$metrics}\n**Mois précédent** : {$prevData}", $schema);

        $result = $this->generate($prompt, $this->context(), 2000, 0.4);
        $this->track('report', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['gmb_report' => $parsed] : ['raw' => $result['content']]);
    }
}