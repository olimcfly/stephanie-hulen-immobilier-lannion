<?php
/**
 * SocialHandler — Module IA Social Media
 * Actions : post, facebook, instagram, linkedin, tiktok, story, hashtags, calendar, video_script
 */
declare(strict_types=1);
require_once __DIR__ . '/BaseHandler.php';

class SocialHandler extends BaseHandler
{
    protected array $actions = ['post','facebook','instagram','linkedin','tiktok','story','hashtags','calendar','video_script'];

    protected function handle_post(array $input): void
    {
        $platform = $input['platform'] ?? 'facebook';
        $topic    = trim($input['topic'] ?? '');
        $type     = $input['type'] ?? 'conseil';
        $bienInfo = trim($input['bien_info'] ?? '');
        if (empty($topic) && empty($bienInfo)) $this->fail('Sujet ou info bien requis');

        $specs = ['facebook'=>'max 500 car., narratif, emojis modérés','instagram'=>'max 300 car., visuel, 20-30 hashtags','linkedin'=>'max 700 car., professionnel, insights','tiktok'=>'max 200 car., hook 3 premières secondes'];
        $spec  = $specs[$platform] ?? $specs['facebook'];

        $schema = '{"post_text":"...","char_count":0,"hook":"...","cta":"...","hashtags":["..."],"best_time_to_post":"...","image_prompt":"..."}';

        $prompt = AiPromptBuilder::json("Post {$platform} pour Eduardo De Sul.\n**Sujet** : {$topic}\n**Type** : {$type}\n**Spécs {$platform}** : {$spec}\n**Bien** : {$bienInfo}", $schema);

        $result = $this->generate($prompt, $this->context(), 1000, 0.85);
        $this->track('post', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['post' => $parsed, 'platform' => $platform] : ['raw' => $result['content']]);
    }

    protected function handle_facebook(array $input): void
    {
        $topic    = trim($input['topic'] ?? '');
        $type     = $input['type'] ?? 'conseil';
        $audience = $input['audience'] ?? 'acheteurs vendeurs bordeaux';

        $schema = '{"variants":[{"angle":"informatif","post":"...","char_count":0,"cta":"...","hashtags":["..."],"image_suggestion":"..."},{"angle":"émotionnel","post":"...","char_count":0,"cta":"...","hashtags":["..."],"image_suggestion":"..."},{"angle":"interactif","post":"...","char_count":0,"cta":"...","hashtags":["..."],"image_suggestion":"..."}],"best_posting_times":["Mardi 19h","Jeudi 12h"],"facebook_algorithm_tips":["..."]}';

        $prompt = AiPromptBuilder::json("3 variantes posts Facebook pour Eduardo De Sul.\n**Sujet** : {$topic}\n**Type** : {$type}\n**Audience** : {$audience}\nAngles différents : informatif, émotionnel, interactif.", $schema);

        $result = $this->generate($prompt, $this->context(), 2000, 0.85);
        $this->track('facebook', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['facebook' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_instagram(array $input): void
    {
        $topic    = trim($input['topic'] ?? '');
        $type     = $input['type'] ?? 'conseil';
        $bienInfo = trim($input['bien_info'] ?? '');

        $schema = '{"caption":"...","first_line":"...","hashtags_post":["..."],"hashtags_comment":["..."],"stories_sequence":[{"slide":1,"content":"...","sticker":"question|sondage|none"}],"image_description":"...","carousel_ideas":["slide 1: ..."],"reels_hook":"...","location_tag":"Bordeaux, Nouvelle-Aquitaine"}';

        $prompt = AiPromptBuilder::json("Post Instagram complet pour Eduardo De Sul.\n**Sujet** : {$topic}\n**Type** : {$type}\n**Bien** : {$bienInfo}", $schema);

        $result = $this->generate($prompt, $this->context(), 1500, 0.85);
        $this->track('instagram', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['instagram' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_linkedin(array $input): void
    {
        $topic     = trim($input['topic'] ?? '');
        $type      = $input['type'] ?? 'insight_marche';
        $dataPoint = trim($input['data_point'] ?? '');

        $schema = '{"post":"...","char_count":0,"hook":"...","key_insight":"...","closing_question":"...","hashtags":["..."],"best_post_time":"..."}';

        $prompt = AiPromptBuilder::json("Post LinkedIn professionnel pour Eduardo De Sul, conseiller immobilier eXp France.\n**Sujet** : {$topic}\n**Type** : {$type}\n**Donnée clé** : {$dataPoint}\nHook fort 2 premières lignes. Paragraphes courts. Chiffres. Question finale. Max 5 hashtags.", $schema);

        $result = $this->generate($prompt, $this->context(), 1000, 0.8);
        $this->track('linkedin', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['linkedin' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_tiktok(array $input): void
    {
        $topic    = trim($input['topic'] ?? '');
        $duration = (int)($input['duration_seconds'] ?? 60);

        $schema = '{"hook_seconds":"0-3","script":[{"time":"0-3s","action":"...","dialogue":"...","visual":"..."},{"time":"3-15s","action":"...","dialogue":"...","visual":"..."}],"caption":"...","hashtags":["#immo","#bordeaux"],"text_overlay":["..."],"cta_end":"...","filming_tips":["..."]}';

        $prompt = AiPromptBuilder::json("Script TikTok/Reels pour Eduardo De Sul.\n**Sujet** : {$topic}\n**Durée** : {$duration} secondes\nHook percutant dans les 3 premières secondes.", $schema);

        $result = $this->generate($prompt, $this->context(), 1500, 0.85);
        $this->track('tiktok', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['tiktok_script' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_story(array $input): void
    {
        $topic    = trim($input['topic'] ?? '');
        $platform = $input['platform'] ?? 'instagram';
        $slides   = (int)($input['slides'] ?? 5);

        $schema = '{"stories":[{"slide":1,"type":"intro|info|conseil|question|cta","headline":"...","body_text":"...","background_color":"#...","sticker":"question|sondage|lien|none","sticker_content":"...","image_suggestion":"..."}],"story_arc":"...","best_time":"..."}';

        $prompt = AiPromptBuilder::json("Séquence {$slides} Stories {$platform} pour Eduardo De Sul.\n**Sujet** : {$topic}", $schema);

        $result = $this->generate($prompt, $this->context(), 1500, 0.8);
        $this->track('story', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['stories' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_hashtags(array $input): void
    {
        $topic    = trim($input['topic'] ?? '');
        $platform = $input['platform'] ?? 'instagram';
        $count    = (int)($input['count'] ?? 25);

        $schema = '{"hashtags":{"mega":["#immo"],"medium":["#immobilierbordeaux"],"niche":["..."],"local":["#bordeaux","#gironde"],"branded":["#eduardodesul"]},"recommended_set":"... (30 hashtags prêts à coller)","hashtag_strategy":"..."}';

        $prompt = AiPromptBuilder::json("{$count} hashtags optimisés {$platform} pour : {$topic}\nMix : 20% mega (>1M), 40% medium (100K-1M), 40% niche (<100K).", $schema);

        $result = $this->generate($prompt, $this->context(), 1000, 0.5);
        $this->track('hashtags', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['hashtags' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_calendar(array $input): void
    {
        $weeks     = (int)($input['weeks'] ?? 4);
        $platforms = is_array($input['platforms'] ?? null) ? implode(', ', $input['platforms']) : 'facebook, instagram, linkedin';
        $perWeek   = (int)($input['posts_per_week'] ?? 5);
        $focus     = trim($input['focus'] ?? 'marché immobilier Bordeaux');

        $schema = '{"calendar":[{"week":1,"theme":"...","posts":[{"day":"Lundi","date":"...","platform":"...","type":"...","topic":"...","format":"photo|carousel|reels|story|texte","status":"à_créer"}]}],"content_pillars":["..."],"repurposing_tips":["..."]}';

        $prompt = AiPromptBuilder::json("Calendrier éditorial social media {$weeks} semaines.\n**Plateformes** : {$platforms}\n**Fréquence** : {$perWeek} posts/semaine\n**Focus** : {$focus}", $schema);

        $result = $this->generate($prompt, $this->context(), 3000, 0.7);
        $this->track('calendar', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['social_calendar' => $parsed] : ['raw' => $result['content']]);
    }

    protected function handle_video_script(array $input): void
    {
        $topic    = trim($input['topic'] ?? '');
        $format   = $input['format'] ?? 'youtube';
        $duration = trim($input['duration'] ?? '3-5 minutes');
        $style    = $input['style'] ?? 'éducatif';

        $schema = '{"title":"...","thumbnail_text":"...","description_seo":"...","tags":["..."],"script":{"intro":{"time":"0-30s","content":"...","b_roll":"..."},"body":[{"section":1,"time":"30s-2min","title":"...","content":"..."}],"outro":{"time":"...","content":"...","cta":"..."}},"filming_checklist":["..."],"editing_notes":["..."]}';

        $prompt = AiPromptBuilder::json("Script vidéo immobilier pour Eduardo De Sul.\n**Sujet** : {$topic}\n**Format** : {$format} | **Durée** : {$duration} | **Style** : {$style}", $schema);

        $result = $this->generate($prompt, $this->context(), 2000, 0.8);
        $this->track('video_script', $result);
        if (!$result['success']) $this->fail($result['error'] ?? 'Erreur IA');
        $parsed = $this->parseJson($result['content']);
        $this->success($parsed ? ['video_script' => $parsed] : ['raw' => $result['content']]);
    }
}