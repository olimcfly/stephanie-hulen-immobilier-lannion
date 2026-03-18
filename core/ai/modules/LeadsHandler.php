<?php
/**
 * LeadsHandler — Module IA Leads / CRM
 * Actions : qualify, email_sequence, email_single, sms, whatsapp, summary, next_action, objection
 */
declare(strict_types=1);
require_once __DIR__ . '/BaseHandler.php';

class LeadsHandler extends BaseHandler
{
    protected array $actions = [
        'qualify', 'email_sequence', 'email_single',
        'sms', 'whatsapp', 'summary', 'next_action', 'objection',
    ];

    protected function handle_qualify(array $input): void
    {
        $message = trim($input['message'] ?? '');
        $project = trim($input['project'] ?? '');
        if (empty($message) && empty($project)) $this->fail('Message ou projet requis');

        $info = "Prénom : {$input['first_name']}, Email : {$input['email']}, Source : {$input['source']}
Budget : {$input['budget']}, Timing : {$input['timing']}
Message : " . ($message ?: $project);

        $schema = <<<JSON
{
  "bant_score": {
    "total": 0,
    "budget": {"score":0,"note":"..."},
    "authority": {"score":0,"note":"..."},
    "need": {"score":0,"note":"..."},
    "timeline": {"score":0,"note":"..."}
  },
  "profil": "acheteur_primo|acheteur_secundo|investisseur|vendeur|locataire",
  "temperature": "froid|tiède|chaud|très_chaud",
  "qualification_level": "non_qualifie|a_contacter|qualifie|prioritaire",
  "budget_estime": {"min":0,"max":0},
  "besoins_detectes": ["..."],
  "signaux_positifs": ["..."],
  "signaux_attention": ["..."],
  "action_recommandee": "...",
  "delai_contact_ideal": "maintenant|24h|cette_semaine|ce_mois",
  "notes_crm": "..."
}
JSON;

        $prompt = AiPromptBuilder::json(
            "Qualifie ce prospect immobilier selon le framework BANT.\n**Données** :\n{$info}",
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 1500, 0.3);
        $this->track('qualify', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['qualification' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_email_sequence(array $input): void
    {
        $firstName  = trim($input['first_name'] ?? 'Prénom');
        $profil     = $input['profil'] ?? 'acheteur';
        $budget     = trim($input['budget'] ?? '');
        $project    = trim($input['project'] ?? '');
        $count      = max(3, min(8, (int)($input['count'] ?? 5)));

        $schema = <<<JSON
{
  "sequence": [
    {"email_number":1,"send_delay_days":0,"subject":"...","preheader":"...","body_html":"...","body_text":"...","cta_text":"...","cta_url":"{{LIEN_CALENDRIER}}","goal":"...","type":"bienvenue|conseil|preuve_sociale|urgence|relance"}
  ],
  "sequence_strategy": "...",
  "expected_open_rate": "..."
}
JSON;

        $prompt = AiPromptBuilder::json(
            "Crée une séquence de {$count} emails de nurturing immobilier.
**Prospect** : {$firstName}, {$profil}, budget {$budget}, projet : {$project}
Cadence : J+0, J+3, J+7, J+14...
Chaque email apporte de la valeur (conseil, info marché, outil). Progresser sensibilisation → décision.",
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 4000, 0.7);
        $this->track('email_sequence', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['sequence' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_email_single(array $input): void
    {
        $firstName = trim($input['first_name'] ?? 'Prénom');
        $context   = trim($input['context'] ?? '');
        $purpose   = $input['purpose'] ?? 'relance';
        $tone      = $input['tone'] ?? 'chaleureux';

        $schema = '{"subject":"...","preheader":"...","body_html":"...","body_text":"...","cta_text":"...","ps":"..."}';

        $prompt = AiPromptBuilder::json(
            "Rédige un email immobilier personnalisé.
**Destinataire** : {$firstName} | **Objectif** : {$purpose} | **Ton** : {$tone}
**Contexte** : {$context}
Email 5-10 lignes aérées, 1 seul CTA, signature Eduardo De Sul eXp France Bordeaux.",
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 1200, 0.75);
        $this->track('email_single', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['email' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_sms(array $input): void
    {
        $firstName = trim($input['first_name'] ?? '');
        $purpose   = $input['purpose'] ?? 'relance';
        $context   = trim($input['context'] ?? '');

        $schema = '{"sms_options":[{"text":"...","char_count":0,"tone":"direct"},{"text":"...","char_count":0,"tone":"chaleureux"},{"text":"...","char_count":0,"tone":"curiosité"}]}';

        $prompt = AiPromptBuilder::json(
            "Rédige 3 SMS de relance immobilier (max 160 caractères chacun).
**Prénom** : {$firstName} | **Objectif** : {$purpose} | **Contexte** : {$context}
Naturel, pas robotique. Signature : \"Eduardo | eXp Bordeaux\"",
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 600, 0.8);
        $this->track('sms', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['sms' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_whatsapp(array $input): void
    {
        $firstName = trim($input['first_name'] ?? '');
        $purpose   = $input['purpose'] ?? 'proposition_bien';
        $context   = trim($input['context'] ?? '');
        $bienInfo  = trim($input['bien_info'] ?? '');

        $schema = '{"message":"...","word_count":0,"emojis_used":["..."],"cta_type":"appel|lien|réponse_directe","follow_up_timing":"..."}';

        $prompt = AiPromptBuilder::json(
            "Message WhatsApp immobilier pour Eduardo De Sul.
**Prénom** : {$firstName} | **Objectif** : {$purpose}
**Contexte** : {$context} | **Bien** : {$bienInfo}
Style : conversationnel, 2-3 emojis max, 50-150 mots, CTA WhatsApp.",
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 600, 0.85);
        $this->track('whatsapp', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['whatsapp' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_summary(array $input): void
    {
        $this->require($input, 'lead_data');
        $leadStr = json_encode($input['lead_data'], JSON_UNESCAPED_UNICODE);
        $interStr = json_encode($input['interactions'] ?? [], JSON_UNESCAPED_UNICODE);

        $schema = <<<JSON
{
  "synthese": "... (3-4 phrases)",
  "projet": {"type":"...","budget":"...","criteres":["..."],"timing":"..."},
  "score_confiance": 0,
  "prochaine_etape": "...",
  "alertes": ["..."],
  "biens_a_proposer": ["..."],
  "historique_resume": "..."
}
JSON;

        $prompt = AiPromptBuilder::json(
            "Génère une fiche synthèse CRM pour ce prospect.\n**Données** : {$leadStr}\n**Historique** : {$interStr}",
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 1000, 0.4);
        $this->track('summary', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['summary' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_next_action(array $input): void
    {
        $status      = $input['status'] ?? 'new';
        $temperature = $input['temperature'] ?? 'tiède';
        $bantScore   = (int)($input['bant_score'] ?? 0);
        $notes       = trim($input['notes'] ?? '');

        $schema = <<<JSON
{
  "action_principale": {"type":"appel|email|sms|whatsapp|rdv|relance|archiver","description":"...","timing":"aujourd'hui|48h|cette_semaine","priorite":"urgent|haute|normale|basse"},
  "message_suggere": "...",
  "actions_alternatives": [{"type":"...","si":"..."}],
  "strategie": "...",
  "indicateurs_succes": ["..."]
}
JSON;

        $prompt = AiPromptBuilder::json(
            "Recommande la meilleure prochaine action pour ce prospect.
**Statut** : {$status} | **Score BANT** : {$bantScore}/100 | **Température** : {$temperature}
**Notes** : {$notes}",
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 800, 0.4);
        $this->track('next_action', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['recommendation' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_objection(array $input): void
    {
        $this->require($input, 'objection');
        $objection = trim($input['objection']);
        $context   = trim($input['context'] ?? '');

        $schema = <<<JSON
{
  "objection_type": "prix|timing|confiance|besoin|concurrent|autre",
  "reponses": [
    {"approach":"empathie","response":"...","follow_up":"..."},
    {"approach":"données","response":"...","follow_up":"..."},
    {"approach":"question_retournee","response":"...","follow_up":"..."}
  ],
  "ne_pas_dire": ["..."],
  "conseil_eduardo": "..."
}
JSON;

        $prompt = AiPromptBuilder::json(
            "Un prospect soulève cette objection : \"{$objection}\"\nContexte : {$context}\nGénère 3 réponses professionnelles pour Eduardo De Sul.",
            $schema
        );

        $result = $this->generate($prompt, $this->context(), 1200, 0.6);
        $this->track('objection', $result);

        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');

        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['objection_handling' => $parsed] : ['raw' => $result['content']]);
    }
}