<?php

return [

    '__categories' => [
        'main'       => ['label' => '',                'icon' => ''],
        'content'    => ['label' => 'Contenu',         'icon' => 'fa-pen-fancy'],
        'marketing'  => ['label' => 'Marketing & CRM', 'icon' => 'fa-bullhorn'],
        'social'     => ['label' => 'Réseaux Sociaux', 'icon' => 'fa-share-alt'],
        'immobilier' => ['label' => 'Immobilier',      'icon' => 'fa-building'],
        'seo'        => ['label' => 'SEO & Analytics', 'icon' => 'fa-search'],
        'ai'         => ['label' => 'Intelligence IA', 'icon' => 'fa-robot'],
        'network'    => ['label' => 'Réseau Pro',      'icon' => 'fa-handshake'],
        'builder'    => ['label' => 'Construction',    'icon' => 'fa-hammer'],
        'strategy'   => ['label' => 'Stratégie',       'icon' => 'fa-chess'],
        'system'     => ['label' => 'Système',         'icon' => 'fa-cog'],
    ],

    // ═══════════════════════════════════════════
    // DASHBOARD
    // ═══════════════════════════════════════════
    'dashboard' => [
        'file'=>null,'category'=>'main','label'=>'Dashboard','icon'=>'fa-chart-line','order'=>0,'enabled'=>true,
        'show_in_nav'=>false
    ],

    // ═══════════════════════════════════════════
    // MON SITE
    // ═══════════════════════════════════════════
    'pages' => [
        'file'=>'content/pages/index.php','category'=>'content','label'=>'Mes pages','icon'=>'fa-file-lines','order'=>10,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-site'
    ],
    'secteurs' => [
        'file'=>'content/secteurs/index.php','category'=>'content','label'=>'Mes quartiers','icon'=>'fa-map-pin','order'=>11,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-site'
    ],
    'guides' => [
        'file'=>'content/guides/index.php','category'=>'content','label'=>'Mes guides','icon'=>'fa-book-bookmark','order'=>12,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-site','badge'=>'NEW'
    ],
    'annuaire' => [
        'file'=>'content/annuaire/index.php','category'=>'content','label'=>'Annuaire local','icon'=>'fa-book-open','order'=>13,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-site'
    ],
    'menus' => [
        'file'=>'builder/menus/index.php','category'=>'builder','label'=>'Header / Footer','icon'=>'fa-list','order'=>14,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-site'
    ],

    // ═══════════════════════════════════════════
    // CONTENU
    // ═══════════════════════════════════════════
    'articles' => [
        'file'=>'content/articles/index.php','category'=>'content','label'=>'Mes articles','icon'=>'fa-newspaper','order'=>20,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-contenu'
    ],
    'journal-content' => [
        'file'=>'content/journal/index.php','category'=>'content','label'=>'Planning contenu','icon'=>'fa-calendar-days','order'=>21,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-contenu'
    ],
    'ressources' => [
        'file'=>'strategy/strategy/ressources/index.php','category'=>'strategy','label'=>'Guides & ressources','icon'=>'fa-book-open','order'=>22,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-contenu','badge'=>'NEW'
    ],

    // ═══════════════════════════════════════════
    // ACQUISITION
    // ═══════════════════════════════════════════
    'captures' => [
        'file'=>'content/pages-capture/index.php','category'=>'content','label'=>'Pages de capture','icon'=>'fa-bolt','order'=>30,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-acq'
    ],
    'leads' => [
        'file'=>'marketing/leads/index.php','category'=>'marketing','label'=>'Leads entrants','icon'=>'fa-user-plus','order'=>31,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-acq'
    ],
    'scoring' => [
        'file'=>'marketing/scoring/index.php','category'=>'marketing','label'=>'Score prospects','icon'=>'fa-star-half-stroke','order'=>32,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-acq'
    ],
    'sequences' => [
        'file'=>'marketing/sequences/index.php','category'=>'marketing','label'=>'Séquences email','icon'=>'fa-list-check','order'=>33,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-acq'
    ],

    // ═══════════════════════════════════════════
    // CLIENTS
    // ═══════════════════════════════════════════
    'crm' => [
        'file'=>'marketing/crm/index.php','category'=>'marketing','label'=>'Mes contacts','icon'=>'fa-address-book','order'=>40,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-clients'
    ],
    'messenger' => [
        'file'=>'marketing/crm/messenger.php','category'=>'marketing','label'=>'Messagerie','icon'=>'fa-comments','order'=>41,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-clients'
    ],
    'emails' => [
        'file'=>'marketing/emails/index.php','category'=>'marketing','label'=>'Emails automatiques','icon'=>'fa-envelope-open-text','order'=>42,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-clients'
    ],

    // ═══════════════════════════════════════════
    // IMMOBILIER
    // ═══════════════════════════════════════════
    'biens' => [
        'file'=>'immobilier/biens/index.php','category'=>'immobilier','label'=>'Mes biens','icon'=>'fa-house','order'=>50,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-immo'
    ],
    'estimation' => [
        'file'=>'immobilier/estimation/index.php','category'=>'immobilier','label'=>'Estimations reçues','icon'=>'fa-calculator','order'=>51,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-immo'
    ],
    'rdv' => [
        'file'=>'immobilier/rdv/index.php','category'=>'immobilier','label'=>'Mes rendez-vous','icon'=>'fa-calendar-check','order'=>52,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-immo'
    ],
    'financement' => [
        'file'=>'immobilier/financement/index.php','category'=>'immobilier','label'=>'Financement','icon'=>'fa-piggy-bank','order'=>53,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-immo'
    ],

    // ═══════════════════════════════════════════
    // SEO
    // ═══════════════════════════════════════════
    'seo' => [
        'file'=>'seo/seo/index.php','category'=>'seo','label'=>'Référencement','icon'=>'fa-magnifying-glass','order'=>60,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-seo'
    ],
    'seo-semantic' => [
        'file'=>'seo/seo-semantic/index.php','category'=>'seo','label'=>'Sémantique','icon'=>'fa-chart-bar','order'=>61,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-seo'
    ],
    'local-seo' => [
        'file'=>'seo/local-seo/index.php','category'=>'seo','label'=>'SEO Local & GMB','icon'=>'fa-location-dot','order'=>62,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-seo'
    ],
    'analytics' => [
        'file'=>'seo/analytics/index.php','category'=>'seo','label'=>'Statistiques','icon'=>'fa-chart-line','order'=>63,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-seo'
    ],

    // ═══════════════════════════════════════════
    // IA
    // ═══════════════════════════════════════════
    'ai' => [
        'file'=>'ia/ia/index.php','category'=>'ai','label'=>'Assistant IA','icon'=>'fa-robot','order'=>70,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-ia'
    ],
    'ai-prompts' => [
        'file'=>'ia/prompts/index.php','category'=>'ai','label'=>'Mes prompts','icon'=>'fa-scroll','order'=>71,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-ia'
    ],
    'agents' => [
        'file'=>'ia/agents/index.php','category'=>'ai','label'=>'Agents automatiques','icon'=>'fa-microchip','order'=>72,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-ia'
    ],
    'journal-ia' => [
        'file'=>'ia/journal/index.php','category'=>'ai','label'=>'Journal IA','icon'=>'fa-calendar-days','order'=>73,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-ia'
    ],
    'advisor-context' => [
        'file'=>'ia/advisor-context/index.php','category'=>'ai','label'=>'Mon profil IA','icon'=>'fa-user-circle','order'=>74,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-ia','badge'=>'NEW'
    ],

    // ═══════════════════════════════════════════
    // STRATEGIE
    // ═══════════════════════════════════════════
    'ancre' => [
        'file'=>'strategy/ancre/index.php','category'=>'strategy','label'=>'Vue ANCRE','icon'=>'fa-anchor','order'=>80,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-strategie'
    ],
    'personas' => [
        'file'=>'strategy/strategy/personas/index.php','category'=>'strategy','label'=>'Mes Personas','icon'=>'fa-users-viewfinder','order'=>81,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-strategie','badge'=>'NEW'
    ],
    'strategie-positionnement' => [
        'file'=>'strategy/strategy/strategie-positionnement/index.php','category'=>'strategy','label'=>'Positionnement','icon'=>'fa-crosshairs','order'=>82,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-strategie'
    ],

    // ═══════════════════════════════════════════
    // SYSTEM
    // ═══════════════════════════════════════════
    'settings' => [
        'file'=>'system/settings/index.php','category'=>'system','label'=>'Configuration','icon'=>'fa-sliders','order'=>90,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-config'
    ],
    'api-keys' => [
        'file'=>'system/settings/api-keys.php','category'=>'system','label'=>'Clés API','icon'=>'fa-key','order'=>91,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-config'
    ],
    'ai-settings' => [
        'file'=>'system/settings/ai_settings.php','category'=>'system','label'=>'Paramètres IA','icon'=>'fa-wand-magic-sparkles','order'=>92,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-config'
    ],
    'maintenance' => [
        'file'=>'system/maintenance/index.php','category'=>'system','label'=>'Maintenance','icon'=>'fa-wrench','order'=>100,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-maintenance'
    ],
    'logs' => [
        'file'=>'system/logs/index.php','category'=>'system','label'=>'Logs système','icon'=>'fa-terminal','order'=>101,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-maintenance'
    ],
    'license' => [
        'file'=>'license/index.php','category'=>'system','label'=>'Licence','icon'=>'fa-shield-check','order'=>102,'enabled'=>true,
        'show_in_nav'=>true,'nav_group'=>'grp-maintenance'
    ],

];