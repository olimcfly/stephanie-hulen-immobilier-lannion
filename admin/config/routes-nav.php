<?php
/**
 * ════════════════════════════════════════════════════════════
 * admin/config/routes-nav.php — IMMO LOCAL+ v9.0 UPDATED
 * ════════════════════════════════════════════════════════════
 * REFONTE COMPLÈTE : Parcours d'accompagnement
 * 
 * Philosophie : On ne vend pas des outils marketing à des
 * agents immobiliers. On les accompagne étape par étape.
 * 
 * 6 groupes principaux au lieu de 14.
 * L'agent voit ses leads et son agenda, pas ses outils SEO.
 * ════════════════════════════════════════════════════════════
 * 
 * MISE À JOUR : Intégration SEO Hub v2.0 + Guide + Onboarding
 * - seo/index.php → hub central harmonisé Articles v2.3
 * - seo/guide.php → guide pédagogique SEO
 * - seo/onboarding.php → modal première visite
 * - Routes réorganisées : seo/* au lieu de seo/seo/*
 */

// ────────────────────────────────────────────────────────────
//  NAVIGATION GROUPS
// ────────────────────────────────────────────────────────────

function getNavGroups() {
    return [

        // ── 1. COMMENCEZ ICI — Onboarding + Stratégie ─────────
        // Visible tant que l'onboarding n'est pas terminé
        // Puis se replie mais reste accessible
        ['id'=>'grp-start','label'=>'Commencez ici','icon'=>'fa-rocket','color'=>'#c9913b',
         'items'=>[
            ['slug'=>'ancre',                    'icon'=>'fa-anchor',           'label'=>'Ma stratégie ANCRE'],
            ['slug'=>'personas',                 'icon'=>'fa-users-viewfinder', 'label'=>'Mes personas'],
            ['slug'=>'strategie-positionnement', 'icon'=>'fa-crosshairs',       'label'=>'Positionnement'],
            ['slug'=>'strategie-offre',          'icon'=>'fa-gem',              'label'=>'Mon offre'],
            ['slug'=>'advisor-context',          'icon'=>'fa-user-circle',      'label'=>'Mon profil'],
            ['slug'=>'launchpad',                'icon'=>'fa-rocket',           'label'=>'Diagnostic lancement'],
        ]],

        // ── 2. MES LEADS — Le nerf de la guerre ───────────────
        // Tout ce qui touche à l'acquisition de contacts
        // Estimations = leads chauds, pas des biens
        ['id'=>'grp-leads','label'=>'Mes Leads','icon'=>'fa-user-plus','color'=>'#dc2626','sep'=>true,
         'items'=>[
            ['slug'=>'leads',       'icon'=>'fa-user-plus',        'label'=>'Leads entrants'],
            ['slug'=>'estimation',  'icon'=>'fa-calculator',       'label'=>'Estimations reçues',  'badge'=>'HOT'],
            ['slug'=>'messenger',   'icon'=>'fa-comments',         'label'=>'Messagerie'],
            ['slug'=>'scoring',     'icon'=>'fa-star-half-stroke', 'label'=>'Score prospects'],
            ['slug'=>'captures',    'icon'=>'fa-bolt',             'label'=>'Pages de capture'],
            ['slug'=>'sequences',   'icon'=>'fa-list-check',       'label'=>'Séquences email'],
            ['slug'=>'crm',         'icon'=>'fa-address-book',     'label'=>'Tous mes contacts'],
        ]],

        // ── 3. MES BIENS — Le quotidien métier ────────────────
        ['id'=>'grp-biens','label'=>'Mes Biens','icon'=>'fa-house','color'=>'#c9913b',
         'items'=>[
            ['slug'=>'properties',  'icon'=>'fa-house',            'label'=>'Mes annonces'],
            ['slug'=>'rdv',         'icon'=>'fa-calendar-check',   'label'=>'Mes rendez-vous'],
            ['slug'=>'financement', 'icon'=>'fa-piggy-bank',       'label'=>'Financement'],
            ['slug'=>'courtiers',   'icon'=>'fa-briefcase',        'label'=>'Courtiers partenaires'],
        ]],

        // ── 4. MON SITE — Construire sa vitrine ───────────────
        ['id'=>'grp-site','label'=>'Mon Site','icon'=>'fa-globe','color'=>'#6366f1','sep'=>true,
         'items'=>[
            ['slug'=>'pages',             'icon'=>'fa-file-lines',    'label'=>'Mes pages'],
            ['slug'=>'secteurs',          'icon'=>'fa-map-pin',       'label'=>'Mes quartiers'],
            ['slug'=>'articles',          'icon'=>'fa-newspaper',     'label'=>'Mes articles'],
            ['slug'=>'guides',            'icon'=>'fa-book-bookmark', 'label'=>'Hub des guides',       'badge'=>'NEW'],
            ['slug'=>'annuaire',          'icon'=>'fa-book-open',     'label'=>'Annuaire local'],
            ['slug'=>'journal',           'icon'=>'fa-calendar-days', 'label'=>'Planning contenu'],
            ['slug'=>'design/templates',  'icon'=>'fa-swatchbook',    'label'=>'Look du site'],
            ['slug'=>'builder/menus',     'icon'=>'fa-list',          'label'=>'Menus'],
        ]],

        // ── 5. MA VISIBILITÉ — Amener du monde ────────────────
        // SEO + Pub + Réseaux = un seul concept
        // L'agent s'en fiche que ce soit du SEO ou du Facebook
        ['id'=>'grp-visibilite','label'=>'Ma Visibilité','icon'=>'fa-eye','color'=>'#65a30d',
         'items'=>[
            ['slug'=>'seo',              'icon'=>'fa-magnifying-glass', 'label'=>'Référencement'],
            ['slug'=>'seo-pages',        'icon'=>'fa-file-lines',       'label'=>'SEO Pages'],
            ['slug'=>'seo-semantic',     'icon'=>'fa-brain',            'label'=>'Sémantique'],
            ['slug'=>'local-seo',        'icon'=>'fa-location-dot',     'label'=>'SEO Local & GMB'],
            ['slug'=>'analytics',        'icon'=>'fa-chart-line',       'label'=>'Statistiques'],
            ['slug'=>'seo-guide',        'icon'=>'fa-book',             'label'=>'Guide SEO',          'badge'=>'📚'],
            ['slug'=>'facebook-ads',     'icon'=>'fab fa-facebook',     'label'=>'Publicité Facebook'],
            ['slug'=>'google-ads',       'icon'=>'fab fa-google',       'label'=>'Publicité Google',    'badge'=>'SOON'],
            ['slug'=>'reseaux-sociaux',  'icon'=>'fa-share-nodes',      'label'=>'Réseaux sociaux'],
            ['slug'=>'facebook',         'icon'=>'fab fa-facebook',     'label'=>'Facebook'],
            ['slug'=>'instagram',        'icon'=>'fab fa-instagram',    'label'=>'Instagram'],
            ['slug'=>'linkedin',         'icon'=>'fab fa-linkedin',     'label'=>'LinkedIn'],
            ['slug'=>'tiktok',           'icon'=>'fab fa-tiktok',       'label'=>'TikTok'],
            ['slug'=>'gmb',              'icon'=>'fab fa-google',       'label'=>'Google My Business'],
            ['slug'=>'scraper-gmb',      'icon'=>'fa-binoculars',       'label'=>'Trouver des partenaires'],
        ]],

        // ── 6. MON IA — Bras droit digital ────────────────────
        ['id'=>'grp-ia','label'=>'Mon IA','icon'=>'fa-microchip','color'=>'#8b5cf6','sep'=>true,
         'items'=>[
            ['slug'=>'ai',           'icon'=>'fa-robot',         'label'=>'Assistant IA'],
            ['slug'=>'ai-prompts',   'icon'=>'fa-scroll',        'label'=>'Mes prompts'],
            ['slug'=>'agents',       'icon'=>'fa-microchip',     'label'=>'Agents automatiques'],
        ]],

        // ── AIDE — Replié par défaut ──────────────────────────
        ['id'=>'grp-aide','label'=>'Aide','icon'=>'fa-circle-question','color'=>'#0ea5e9','sep'=>true,
         'items'=>[
            ['slug'=>'aide',               'icon'=>'fa-life-ring',        'label'=>"Centre d'aide",        'badge'=>'NEW'],
            ['slug'=>'guide-plateforme',   'icon'=>'fa-map',              'label'=>'Guide plateforme'],
            ['slug'=>'ressources-clients', 'icon'=>'fa-graduation-cap',   'label'=>'Ressources stratégie'],
        ]],

        // ── CONFIG — Replié par défaut ────────────────────────
        ['id'=>'grp-config','label'=>'Configuration','icon'=>'fa-sliders','color'=>'#64748b',
         'items'=>[
            ['slug'=>'settings',        'icon'=>'fa-sliders',             'label'=>'Réglages généraux'],
            ['slug'=>'api-keys',        'icon'=>'fa-key',                 'label'=>'Clés API'],
            ['slug'=>'ai-settings',     'icon'=>'fa-wand-magic-sparkles', 'label'=>'Paramètres IA'],
            ['slug'=>'system/emails',   'icon'=>'fa-envelope-open-text',  'label'=>'Templates emails'],
            ['slug'=>'maintenance',     'icon'=>'fa-wrench',              'label'=>'Maintenance'],
            ['slug'=>'module-health',   'icon'=>'fa-stethoscope',         'label'=>'Santé système'],
            ['slug'=>'logs',            'icon'=>'fa-terminal',            'label'=>'Logs'],
            ['slug'=>'license',         'icon'=>'fa-shield-check',        'label'=>'Licence'],
        ]],
    ];
}

// ────────────────────────────────────────────────────────────
//  ROUTES & FILES
// ────────────────────────────────────────────────────────────

function getSubRoutes() {
    return [
        // ── SYSTÈME ──────────────────────────────────────
        'dashboard'       => ['file'=>'system/index.php',                     'title'=>'Tableau de bord'],
        'maintenance'     => ['file'=>'system/maintenance/index.php',         'title'=>'Maintenance'],
        'settings'        => ['file'=>'system/settings/index.php',            'title'=>'Configuration'],
        'api-keys'        => ['file'=>'system/settings/api-keys.php',         'title'=>'Clés API'],
        'ai-settings'     => ['file'=>'system/settings/ai_settings.php',      'title'=>'Paramètres IA'],
        'system/emails'   => ['file'=>'system/emails/index.php',              'title'=>'Templates emails'],
        'license'         => ['file'=>'license/index.php',                    'title'=>'Licence'],
        'logs'            => ['file'=>'system/logs/index.php',                'title'=>'Logs système'],
        'module-health'   => ['file'=>'system/diagnostic/index.php',          'title'=>'Santé modules'],

        // ── DESIGN ───────────────────────────────────────
        'design/templates'=> ['file'=>'system/templates/index.php',           'title'=>'Look du site'],
        'builder/menus'   => ['file'=>'builder/menus/index.php',              'title'=>'Menus'],

        // ── CONTENU ──────────────────────────────────────
        'pages'           => ['file'=>'content/pages/index.php',              'title'=>'Pages'],
        'articles'        => ['file'=>'content/articles/index.php',           'title'=>'Articles'],
        'captures'        => ['file'=>'content/capture/index.php',            'title'=>'Pages de capture'],
        'secteurs'        => ['file'=>'content/secteurs/index.php',           'title'=>'Quartiers'],
        'annuaire'        => ['file'=>'content/annuaire/index.php',           'title'=>'Annuaire local'],
        'guides'          => ['file'=>'content/guides/index.php',             'title'=>'Hub des guides'],
        'ressources'      => ['file'=>'content/guides/index.php',             'title'=>'Guides & ressources'],
        'journal'         => ['file'=>'ia/journal/index.php',                 'title'=>'Planning contenu'],

        // ── LEADS & CRM ─────────────────────────────────
        'crm'             => ['file'=>'marketing/crm/index.php',              'title'=>'Mes contacts'],
        'leads'           => ['file'=>'marketing/leads/index.php',            'title'=>'Leads'],
        'sequences'       => ['file'=>'marketing/sequences/index.php',        'title'=>'Séquences'],
        'scoring'         => ['file'=>'marketing/scoring/index.php',          'title'=>'Score prospects'],
        'messenger'       => ['file'=>'marketing/crm/messenger.php',          'title'=>'Messagerie'],
        'emails'          => ['file'=>'marketing/emails/index.php',           'title'=>'Emails auto'],

        // ── IMMOBILIER ───────────────────────────────────
        'properties'      => ['file'=>'immobilier/properties/index.php',      'title'=>'Mes annonces'],
        'estimation'      => ['file'=>'immobilier/estimation/index.php',      'title'=>'Estimations'],
        'rdv'             => ['file'=>'immobilier/rdv/index.php',             'title'=>'Rendez-vous'],
        'financement'     => ['file'=>'immobilier/financement/index.php',     'title'=>'Financement'],
        'courtiers'       => ['file'=>'immobilier/courtiers/index.php',       'title'=>'Courtiers partenaires'],

        // ── VISIBILITÉ (SEO + PUB + SOCIAL) ─────────────
        // SEO Hub v2.0 — Index + Guide + Onboarding
        'seo'             => ['file'=>'seo/index.php',                        'title'=>'Référencement'],
        'seo-pages'       => ['file'=>'seo/modules/seo-pages/index.php',      'title'=>'SEO Pages'],
        'seo-semantic'    => ['file'=>'seo/modules/seo-semantic/index.php',   'title'=>'Mots-clés'],
        'seo-guide'       => ['file'=>'seo/guide.php',                        'title'=>'Guide SEO'],
        'local-seo'       => ['file'=>'seo/modules/local-seo/index.php',      'title'=>'SEO Local & GMB'],
        'analytics'       => ['file'=>'seo/modules/analytics/index.php',      'title'=>'Statistiques'],
        
        // Publicité & Réseaux
        'facebook-ads'    => ['file'=>'marketing/pub-facebook/index.php',     'title'=>'Publicité Facebook'],
        'reseaux-sociaux' => ['file'=>'social/reseaux-sociaux/index.php',     'title'=>'Réseaux sociaux'],
        'facebook'        => ['file'=>'social/facebook/index.php',            'title'=>'Facebook'],
        'instagram'       => ['file'=>'social/instagram/index.php',           'title'=>'Instagram'],
        'linkedin'        => ['file'=>'social/linkedin/index.php',            'title'=>'LinkedIn'],
        'tiktok'          => ['file'=>'social/tiktok/index.php',              'title'=>'TikTok'],
        'gmb'             => ['file'=>'social/gmb/index.php',                 'title'=>'Google My Business'],
        'scraper-gmb'     => ['file'=>'network/scraper-gmb/index.php',        'title'=>'Trouver des partenaires'],

        // ── STRATÉGIE ────────────────────────────────────
        'launchpad'       => ['file'=>'strategy/launchpad/index.php',         'title'=>'Diagnostic lancement'],
        'ancre'           => ['file'=>'strategy/ancre/index.php',             'title'=>'Méthode ANCRE'],
        'personas'        => ['file'=>'strategy/strategy/personas/index.php', 'title'=>'Mes Personas'],
        'strategie-positionnement' => ['file'=>'strategy/strategy/strategie-positionnement/index.php', 'title'=>'Positionnement'],
        'strategie-offre'  => ['file'=>'strategy/ancre/index.php',            'title'=>'Mon offre'],
        'strategie-contenu'=> ['file'=>'content/pages/index.php',             'title'=>'Site & Contenu'],
        'strategie-trafic' => ['file'=>'seo/index.php',                       'title'=>'Trafic'],
        'strategie-conversion' => ['file'=>'content/capture/index.php',       'title'=>'Conversion'],
        'strategie-optimisation' => ['file'=>'seo/modules/analytics/index.php','title'=>'Optimisation'],

        // ── IA ──────────────────────────────────────────
        'ai'              => ['file'=>'ia/ia/index.php',                      'title'=>'Assistant IA'],
        'ai-prompts'      => ['file'=>'ia/prompts/index.php',                 'title'=>'Mes prompts'],
        'agents'          => ['file'=>'ia/agents/index.php',                  'title'=>'Agents automatiques'],
        'advisor-context' => ['file'=>'ia/advisor-context/index.php',         'title'=>'Mon profil'],

        // ── AIDE ─────────────────────────────────────────
        'aide'               => ['file'=>'aide/index.php',                    'title'=>"Centre d'aide"],
        'guide-plateforme'   => ['file'=>'aide/guide-plateforme.php',         'title'=>'Guide plateforme'],
        'ressources-clients' => ['file'=>'aide/ressources.php',               'title'=>'Ressources stratégie'],
    ];
}

// ────────────────────────────────────────────────────────────
//  ALIASES
// ────────────────────────────────────────────────────────────

function getRouteAliases() {
    return [
        // Dashboard
        'home'=>'dashboard', 'accueil'=>'dashboard',
        
        // Contenu
        'blog'=>'articles', 'posts'=>'articles',
        'capture'=>'captures', 'quartiers'=>'secteurs', 'neighborhoods'=>'secteurs',
        'guide-local'=>'annuaire',
        
        // CRM
        'contacts'=>'crm', 'leads-list'=>'leads',
        'messagerie'=>'messenger', 'inbox'=>'messenger', 'emails-crm'=>'messenger', 'mail'=>'messenger',
        
        // Immobilier
        'biens'=>'properties',
        
        // Visibilité & SEO
        'seo-hub'=>'seo',
        'seo-pages'=>'seo',
        'seo-semantic'=>'seo-semantic',
        'guide-seo'=>'seo-guide',
        'local-seo'=>'local-seo',
        'search-analytics'=>'analytics',
        'social-media'=>'reseaux-sociaux', 'social'=>'reseaux-sociaux',
        'gmb-prospects'=>'scraper-gmb',
        'ads-launch'=>'facebook-ads', 'ads-overview'=>'facebook-ads', 'pub-facebook'=>'facebook-ads',
        
        // Design
        'menu'=>'builder/menus', 'menus'=>'builder/menus', 'navigation'=>'builder/menus',
        'templates'=>'design/templates', 'system-templates'=>'design/templates',
        'look'=>'design/templates', 'design'=>'design/templates',
        
        // Stratégie
        'strategy'=>'ancre', 'strategie'=>'ancre', 'methode-ancre'=>'ancre', 'methode'=>'ancre',
        'mes-personas'=>'personas',
        'positionnement'=>'strategie-positionnement',
        'offre'=>'strategie-offre',
        'contenu'=>'strategie-contenu',
        'trafic'=>'strategie-trafic',
        'conversion'=>'strategie-conversion',
        'optimisation'=>'strategie-optimisation',
        
        // IA
        'assistant-ia'=>'ai', 'contexte-ia'=>'advisor-context',
        'module-manager'=>'ai', 'ia-prompts'=>'ai-prompts',
        
        // Aide
        'help'=>'aide', 'support'=>'aide',
        'guide-plateforme-link'=>'guide-plateforme',
        'ressources'=>'guides', 'ressources-strategie'=>'ressources-clients',
    ];
}

// ────────────────────────────────────────────────────────────
//  AUTO-DETECT ACTIVE GROUP
// ────────────────────────────────────────────────────────────

function getActiveGroup($activeModule) {
    foreach (getNavGroups() as $grp) {
        foreach ($grp['items'] as $item) {
            if ($item['slug'] === $activeModule) {
                return $grp['id'];
            }
        }
    }
    return '';
}

/**
 * Helper : Résoudre un slug vers sa vraie route
 * Gère les aliases automatiquement
 */
function resolveRoute($slug) {
    $aliases = getRouteAliases();
    $slug = $aliases[$slug] ?? $slug;
    $routes = getSubRoutes();
    return $routes[$slug] ?? null;
}