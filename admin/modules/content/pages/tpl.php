<?php
/**
 * Bloc $TPL complet — à insérer dans admin/modules/content/pages/edit.php
 * Remplace le bloc existant $TPL = [ ... ] et les aliases en fin de bloc
 *
 * Clés 'key' = data-field des templates front /front/templates/pages/tN-xxx.php
 */

$TPL = [

    // ══════════════════════════════════════════════════
    // T1 — ACCUEIL
    // ══════════════════════════════════════════════════
    't1-accueil' => [
        ['section'=>'Hero',                    'icon'=>'fa-star',         'color'=>'#6366f1','fields'=>[
            ['key'=>'hero_eyebrow',   'label'=>'Eyebrow / badge',         'type'=>'text',    'hint'=>'Ex : Conseiller immobilier à Bordeaux'],
            ['key'=>'hero_title',     'label'=>'Titre H1',                'type'=>'text'],
            ['key'=>'hero_subtitle',  'label'=>'Sous-titre',              'type'=>'textarea'],
            ['key'=>'hero_cta_text',  'label'=>'CTA 1 — texte',           'type'=>'text'],
            ['key'=>'hero_cta_url',   'label'=>'CTA 1 — lien',            'type'=>'url'],
            ['key'=>'hero_cta2_text', 'label'=>'CTA 2 — texte',           'type'=>'text'],
            ['key'=>'hero_cta2_url',  'label'=>'CTA 2 — lien',            'type'=>'url'],
        ]],
        ['section'=>'Stats (3 chiffres)',      'icon'=>'fa-chart-bar',    'color'=>'#0ea5e9','fields'=>[
            ['key'=>'hero_stat1_num', 'label'=>'Stat 1 — chiffre',        'type'=>'text', 'hint'=>'Ex : 98%'],
            ['key'=>'hero_stat1_lbl', 'label'=>'Stat 1 — libellé',        'type'=>'text', 'hint'=>'Ex : clients satisfaits'],
            ['key'=>'hero_stat2_num', 'label'=>'Stat 2 — chiffre',        'type'=>'text'],
            ['key'=>'hero_stat2_lbl', 'label'=>'Stat 2 — libellé',        'type'=>'text'],
            ['key'=>'hero_stat3_num', 'label'=>'Stat 3 — chiffre',        'type'=>'text'],
            ['key'=>'hero_stat3_lbl', 'label'=>'Stat 3 — libellé',        'type'=>'text'],
        ]],
        ['section'=>'Bénéfices (3 colonnes)',  'icon'=>'fa-check-double', 'color'=>'#10b981','fields'=>[
            ['key'=>'ben_title',  'label'=>'Titre section',               'type'=>'text'],
            ['key'=>'ben1_icon',  'label'=>'Bénéfice 1 — emoji',          'type'=>'text', 'hint'=>'Ex : 📍'],
            ['key'=>'ben1_title', 'label'=>'Bénéfice 1 — titre',          'type'=>'text'],
            ['key'=>'ben1_text',  'label'=>'Bénéfice 1 — texte',          'type'=>'textarea'],
            ['key'=>'ben2_icon',  'label'=>'Bénéfice 2 — emoji',          'type'=>'text'],
            ['key'=>'ben2_title', 'label'=>'Bénéfice 2 — titre',          'type'=>'text'],
            ['key'=>'ben2_text',  'label'=>'Bénéfice 2 — texte',          'type'=>'textarea'],
            ['key'=>'ben3_icon',  'label'=>'Bénéfice 3 — emoji',          'type'=>'text'],
            ['key'=>'ben3_title', 'label'=>'Bénéfice 3 — titre',          'type'=>'text'],
            ['key'=>'ben3_text',  'label'=>'Bénéfice 3 — texte',          'type'=>'textarea'],
        ]],
        ['section'=>'Présentation conseiller', 'icon'=>'fa-user-tie',    'color'=>'#0d9488','fields'=>[
            ['key'=>'pres_title',    'label'=>'Titre section',            'type'=>'text'],
            ['key'=>'pres_sub',      'label'=>'Sous-titre',               'type'=>'text'],
            ['key'=>'pres_text',     'label'=>'Texte présentation',       'type'=>'rich'],
            ['key'=>'pres_tag1',     'label'=>'Tag 1',                    'type'=>'text', 'hint'=>'Ex : ✓ Conseiller certifié'],
            ['key'=>'pres_tag2',     'label'=>'Tag 2',                    'type'=>'text'],
            ['key'=>'pres_tag3',     'label'=>'Tag 3',                    'type'=>'text'],
            ['key'=>'pres_cta_text', 'label'=>'CTA — texte',              'type'=>'text'],
            ['key'=>'pres_cta_url',  'label'=>'CTA — lien',               'type'=>'url'],
        ]],
        ['section'=>'Expertise (3 piliers)',   'icon'=>'fa-trophy',       'color'=>'#f59e0b','fields'=>[
            ['key'=>'exp_title',  'label'=>'Titre section',               'type'=>'text'],
            ['key'=>'exp1_icon',  'label'=>'Pilier 1 — emoji',            'type'=>'text'],
            ['key'=>'exp1_title', 'label'=>'Pilier 1 — titre',            'type'=>'text'],
            ['key'=>'exp1_text',  'label'=>'Pilier 1 — texte',            'type'=>'textarea'],
            ['key'=>'exp1_link',  'label'=>'Pilier 1 — lien',             'type'=>'url'],
            ['key'=>'exp2_icon',  'label'=>'Pilier 2 — emoji',            'type'=>'text'],
            ['key'=>'exp2_title', 'label'=>'Pilier 2 — titre',            'type'=>'text'],
            ['key'=>'exp2_text',  'label'=>'Pilier 2 — texte',            'type'=>'textarea'],
            ['key'=>'exp2_link',  'label'=>'Pilier 2 — lien',             'type'=>'url'],
            ['key'=>'exp3_icon',  'label'=>'Pilier 3 — emoji',            'type'=>'text'],
            ['key'=>'exp3_title', 'label'=>'Pilier 3 — titre',            'type'=>'text'],
            ['key'=>'exp3_text',  'label'=>'Pilier 3 — texte',            'type'=>'textarea'],
            ['key'=>'exp3_link',  'label'=>'Pilier 3 — lien',             'type'=>'url'],
        ]],
        ['section'=>'Méthode en 3 étapes',    'icon'=>'fa-list-ol',      'color'=>'#8b5cf6','fields'=>[
            ['key'=>'method_title',    'label'=>'Titre section',          'type'=>'text'],
            ['key'=>'step1_num',       'label'=>'Étape 1 — numéro',       'type'=>'text', 'hint'=>'Ex : 01'],
            ['key'=>'step1_title',     'label'=>'Étape 1 — titre',        'type'=>'text'],
            ['key'=>'step1_text',      'label'=>'Étape 1 — texte',        'type'=>'textarea'],
            ['key'=>'step2_num',       'label'=>'Étape 2 — numéro',       'type'=>'text'],
            ['key'=>'step2_title',     'label'=>'Étape 2 — titre',        'type'=>'text'],
            ['key'=>'step2_text',      'label'=>'Étape 2 — texte',        'type'=>'textarea'],
            ['key'=>'step3_num',       'label'=>'Étape 3 — numéro',       'type'=>'text'],
            ['key'=>'step3_title',     'label'=>'Étape 3 — titre',        'type'=>'text'],
            ['key'=>'step3_text',      'label'=>'Étape 3 — texte',        'type'=>'textarea'],
            ['key'=>'method_cta_text', 'label'=>'CTA — texte',            'type'=>'text'],
            ['key'=>'method_cta_url',  'label'=>'CTA — lien',             'type'=>'url'],
        ]],
        ['section'=>'Guide SEO',              'icon'=>'fa-book-open',    'color'=>'#0ea5e9','fields'=>[
            ['key'=>'guide_title', 'label'=>'Titre du guide',             'type'=>'text'],
            ['key'=>'g1_num',      'label'=>'Article 1 — numéro',         'type'=>'text'],
            ['key'=>'g1_title',    'label'=>'Article 1 — titre',          'type'=>'text'],
            ['key'=>'g1_text',     'label'=>'Article 1 — contenu',        'type'=>'rich'],
            ['key'=>'g2_num',      'label'=>'Article 2 — numéro',         'type'=>'text'],
            ['key'=>'g2_title',    'label'=>'Article 2 — titre',          'type'=>'text'],
            ['key'=>'g2_text',     'label'=>'Article 2 — contenu',        'type'=>'rich'],
            ['key'=>'g3_num',      'label'=>'Article 3 — numéro',         'type'=>'text'],
            ['key'=>'g3_title',    'label'=>'Article 3 — titre',          'type'=>'text'],
            ['key'=>'g3_text',     'label'=>'Article 3 — contenu',        'type'=>'rich'],
        ]],
        ['section'=>'CTA Finale',             'icon'=>'fa-rocket',       'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',      'label'=>'Titre',                   'type'=>'text'],
            ['key'=>'cta_text',       'label'=>'Description',             'type'=>'textarea'],
            ['key'=>'cta_btn_text',   'label'=>'Texte bouton',            'type'=>'text'],
            ['key'=>'cta_btn_url',    'label'=>'Lien bouton',             'type'=>'url'],
            ['key'=>'cta_phone_text', 'label'=>'Texte téléphone',         'type'=>'text'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T2 — EDITO (Vendre / Acheter / Financer)
    // ══════════════════════════════════════════════════
    't2-edito' => [
        ['section'=>'Hero',                  'icon'=>'fa-star',                'color'=>'#6366f1','fields'=>[
            ['key'=>'hero_eyebrow',  'label'=>'Eyebrow',                       'type'=>'text',    'hint'=>'Ex : Conseiller eXp France'],
            ['key'=>'hero_title',    'label'=>'Titre H1',                      'type'=>'text'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                    'type'=>'textarea'],
            ['key'=>'hero_cta_text', 'label'=>'CTA — texte bouton',            'type'=>'text'],
            ['key'=>'hero_cta_url',  'label'=>'CTA — lien',                    'type'=>'url'],
        ]],
        ['section'=>'3 Arguments (boxes)',   'icon'=>'fa-table-cells',         'color'=>'#0d9488','fields'=>[
            ['key'=>'box1_icon',  'label'=>'Box 1 — emoji',                    'type'=>'text', 'hint'=>'Ex : 🏡'],
            ['key'=>'box1_title', 'label'=>'Box 1 — titre',                    'type'=>'text'],
            ['key'=>'box1_text',  'label'=>'Box 1 — texte',                    'type'=>'text'],
            ['key'=>'box2_icon',  'label'=>'Box 2 — emoji',                    'type'=>'text'],
            ['key'=>'box2_title', 'label'=>'Box 2 — titre',                    'type'=>'text'],
            ['key'=>'box2_text',  'label'=>'Box 2 — texte',                    'type'=>'text'],
            ['key'=>'box3_icon',  'label'=>'Box 3 — emoji',                    'type'=>'text'],
            ['key'=>'box3_title', 'label'=>'Box 3 — titre',                    'type'=>'text'],
            ['key'=>'box3_text',  'label'=>'Box 3 — texte',                    'type'=>'text'],
        ]],
        ['section'=>'Problèmes / Douleurs',  'icon'=>'fa-exclamation-triangle','color'=>'#ef4444','fields'=>[
            ['key'=>'pb_title',  'label'=>'Titre section',                     'type'=>'text'],
            ['key'=>'pb1_title', 'label'=>'Problème 1 — titre',                'type'=>'text'],
            ['key'=>'pb1_text',  'label'=>'Problème 1 — texte',                'type'=>'textarea'],
            ['key'=>'pb2_title', 'label'=>'Problème 2 — titre',                'type'=>'text'],
            ['key'=>'pb2_text',  'label'=>'Problème 2 — texte',                'type'=>'textarea'],
            ['key'=>'pb3_title', 'label'=>'Problème 3 — titre',                'type'=>'text'],
            ['key'=>'pb3_text',  'label'=>'Problème 3 — texte',                'type'=>'textarea'],
        ]],
        ['section'=>'Autorité / Chiffres',   'icon'=>'fa-certificate',         'color'=>'#f59e0b','fields'=>[
            ['key'=>'auth_badge',  'label'=>'Badge',                           'type'=>'text'],
            ['key'=>'auth_title',  'label'=>'Titre',                           'type'=>'text'],
            ['key'=>'auth_sub',    'label'=>'Sous-titre',                      'type'=>'text'],
            ['key'=>'auth1_icon',  'label'=>'Autorité 1 — emoji',              'type'=>'text'],
            ['key'=>'auth1_title', 'label'=>'Autorité 1 — titre',              'type'=>'text'],
            ['key'=>'auth1_text',  'label'=>'Autorité 1 — texte',              'type'=>'textarea'],
            ['key'=>'auth2_icon',  'label'=>'Autorité 2 — emoji',              'type'=>'text'],
            ['key'=>'auth2_title', 'label'=>'Autorité 2 — titre',              'type'=>'text'],
            ['key'=>'auth2_text',  'label'=>'Autorité 2 — texte',              'type'=>'textarea'],
            ['key'=>'auth3_icon',  'label'=>'Autorité 3 — emoji',              'type'=>'text'],
            ['key'=>'auth3_title', 'label'=>'Autorité 3 — titre',              'type'=>'text'],
            ['key'=>'auth3_text',  'label'=>'Autorité 3 — texte',              'type'=>'textarea'],
        ]],
        ['section'=>'Méthode / Process',     'icon'=>'fa-list-ol',             'color'=>'#8b5cf6','fields'=>[
            ['key'=>'method_title',    'label'=>'Titre section',               'type'=>'text'],
            ['key'=>'step1_num',       'label'=>'Étape 1 — numéro',            'type'=>'text'],
            ['key'=>'step1_title',     'label'=>'Étape 1 — titre',             'type'=>'text'],
            ['key'=>'step1_text',      'label'=>'Étape 1 — texte',             'type'=>'textarea'],
            ['key'=>'step2_num',       'label'=>'Étape 2 — numéro',            'type'=>'text'],
            ['key'=>'step2_title',     'label'=>'Étape 2 — titre',             'type'=>'text'],
            ['key'=>'step2_text',      'label'=>'Étape 2 — texte',             'type'=>'textarea'],
            ['key'=>'step3_num',       'label'=>'Étape 3 — numéro',            'type'=>'text'],
            ['key'=>'step3_title',     'label'=>'Étape 3 — titre',             'type'=>'text'],
            ['key'=>'step3_text',      'label'=>'Étape 3 — texte',             'type'=>'textarea'],
            ['key'=>'method_cta_text', 'label'=>'CTA — texte',                 'type'=>'text'],
            ['key'=>'method_cta_url',  'label'=>'CTA — lien',                  'type'=>'url'],
        ]],
        ['section'=>'Guide SEO',             'icon'=>'fa-book-open',           'color'=>'#0ea5e9','fields'=>[
            ['key'=>'guide_title', 'label'=>'Titre du guide',                  'type'=>'text'],
            ['key'=>'g1_num',      'label'=>'Article 1 — numéro',              'type'=>'text'],
            ['key'=>'g1_title',    'label'=>'Article 1 — titre',               'type'=>'text'],
            ['key'=>'g1_text',     'label'=>'Article 1 — contenu',             'type'=>'rich'],
            ['key'=>'g2_num',      'label'=>'Article 2 — numéro',              'type'=>'text'],
            ['key'=>'g2_title',    'label'=>'Article 2 — titre',               'type'=>'text'],
            ['key'=>'g2_text',     'label'=>'Article 2 — contenu',             'type'=>'rich'],
            ['key'=>'g3_num',      'label'=>'Article 3 — numéro',              'type'=>'text'],
            ['key'=>'g3_title',    'label'=>'Article 3 — titre',               'type'=>'text'],
            ['key'=>'g3_text',     'label'=>'Article 3 — contenu',             'type'=>'rich'],
        ]],
        ['section'=>'CTA Finale',            'icon'=>'fa-rocket',              'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',    'label'=>'Titre',                          'type'=>'text'],
            ['key'=>'cta_text',     'label'=>'Description',                    'type'=>'textarea'],
            ['key'=>'cta_btn_text', 'label'=>'Texte bouton',                   'type'=>'text'],
            ['key'=>'cta_btn_url',  'label'=>'Lien bouton',                    'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T3 — SECTEUR
    // ══════════════════════════════════════════════════
    't3-secteur' => [
        ['section'=>'Intro secteur',         'icon'=>'fa-map-pin',        'color'=>'#0d9488','fields'=>[
            ['key'=>'hero_title',    'label'=>'Nom du secteur (H1)',       'type'=>'text'],
            ['key'=>'hero_subtitle', 'label'=>'Description courte',        'type'=>'textarea'],
            ['key'=>'hero_image',    'label'=>'Image principale',           'type'=>'image'],
        ]],
        ['section'=>'Marché immobilier',     'icon'=>'fa-chart-line',     'color'=>'#6366f1','fields'=>[
            ['key'=>'prix_moyen',    'label'=>'Prix moyen au m²',          'type'=>'text', 'hint'=>'Ex : 3 800 €/m²'],
            ['key'=>'prix_maison',   'label'=>'Prix moyen maison',         'type'=>'text'],
            ['key'=>'prix_appart',   'label'=>'Prix moyen appartement',    'type'=>'text'],
            ['key'=>'evolution',     'label'=>'Évolution sur 1 an',        'type'=>'text', 'hint'=>'Ex : +4,2%'],
            ['key'=>'nb_ventes',     'label'=>'Nb ventes / an',            'type'=>'text'],
        ]],
        ['section'=>'Contenu principal',     'icon'=>'fa-pen-nib',        'color'=>'#8b5cf6','fields'=>[
            ['key'=>'body_intro',    'label'=>'Introduction',              'type'=>'rich'],
            ['key'=>'body_content',  'label'=>'Corps de texte',            'type'=>'rich'],
            ['key'=>'atout_1',       'label'=>'Point fort 1',              'type'=>'text'],
            ['key'=>'atout_2',       'label'=>'Point fort 2',              'type'=>'text'],
            ['key'=>'atout_3',       'label'=>'Point fort 3',              'type'=>'text'],
        ]],
        ['section'=>'Infos pratiques',       'icon'=>'fa-info-circle',    'color'=>'#f59e0b','fields'=>[
            ['key'=>'transport',     'label'=>'Transports',                'type'=>'text'],
            ['key'=>'ecoles',        'label'=>'Écoles',                    'type'=>'text'],
            ['key'=>'commerces',     'label'=>'Commerces',                 'type'=>'text'],
            ['key'=>'cadre_vie',     'label'=>'Cadre de vie',              'type'=>'textarea'],
        ]],
        ['section'=>'CTA',                   'icon'=>'fa-rocket',         'color'=>'#10b981','fields'=>[
            ['key'=>'cta_btn_text',  'label'=>'Texte du CTA',              'type'=>'text'],
            ['key'=>'cta_btn_url',   'label'=>'Lien',                      'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T4 — BLOG HUB
    // ══════════════════════════════════════════════════
    't4-blog-hub' => [
        ['section'=>'Hero blog',             'icon'=>'fa-newspaper',      'color'=>'#0ea5e9','fields'=>[
            ['key'=>'hero_title',    'label'=>'Titre principal',           'type'=>'text',    'hint'=>'Ex : Le blog immobilier de Bordeaux'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                'type'=>'textarea'],
        ]],
        ['section'=>'CTA newsletter / guide','icon'=>'fa-rocket',         'color'=>'#6366f1','fields'=>[
            ['key'=>'cta_title',     'label'=>'Titre CTA',                 'type'=>'text'],
            ['key'=>'cta_btn_text',  'label'=>'Texte bouton',              'type'=>'text'],
            ['key'=>'cta_btn_url',   'label'=>'Lien bouton',               'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T5 — ARTICLE
    // ══════════════════════════════════════════════════
    't5-article' => [
        ['section'=>'En-tête article',       'icon'=>'fa-pen-nib',        'color'=>'#0ea5e9','fields'=>[
            ['key'=>'article_title',   'label'=>'Titre de l\'article',     'type'=>'text'],
            ['key'=>'article_excerpt', 'label'=>'Résumé / chapô',          'type'=>'textarea'],
            ['key'=>'article_image',   'label'=>'Image à la une',          'type'=>'image'],
            ['key'=>'article_category','label'=>'Catégorie',               'type'=>'text'],
            ['key'=>'read_time',       'label'=>'Temps de lecture',        'type'=>'text', 'hint'=>'Ex : 5 min'],
        ]],
        ['section'=>'Contenu',               'icon'=>'fa-align-left',     'color'=>'#8b5cf6','fields'=>[
            ['key'=>'article_content', 'label'=>'Corps de l\'article',     'type'=>'rich'],
        ]],
        ['section'=>'CTA fin d\'article',    'icon'=>'fa-rocket',         'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',     'label'=>'Titre CTA',                 'type'=>'text'],
            ['key'=>'cta_text',      'label'=>'Description',               'type'=>'textarea'],
            ['key'=>'cta_btn_text',  'label'=>'Texte bouton',              'type'=>'text'],
            ['key'=>'cta_btn_url',   'label'=>'Lien bouton',               'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T6 — GUIDE LOCAL
    // ══════════════════════════════════════════════════
    't6-guide' => [
        ['section'=>'En-tête guide',         'icon'=>'fa-book-open',      'color'=>'#10b981','fields'=>[
            ['key'=>'guide_title',   'label'=>'Titre du guide',            'type'=>'text'],
            ['key'=>'guide_subtitle','label'=>'Sous-titre',                'type'=>'textarea'],
            ['key'=>'guide_image',   'label'=>'Image couverture',          'type'=>'image'],
        ]],
        ['section'=>'Contenu du guide',      'icon'=>'fa-list-ol',        'color'=>'#8b5cf6','fields'=>[
            ['key'=>'step1_title',   'label'=>'Étape 1 — titre',           'type'=>'text'],
            ['key'=>'step1_text',    'label'=>'Étape 1 — contenu',         'type'=>'rich'],
            ['key'=>'step2_title',   'label'=>'Étape 2 — titre',           'type'=>'text'],
            ['key'=>'step2_text',    'label'=>'Étape 2 — contenu',         'type'=>'rich'],
            ['key'=>'step3_title',   'label'=>'Étape 3 — titre',           'type'=>'text'],
            ['key'=>'step3_text',    'label'=>'Étape 3 — contenu',         'type'=>'rich'],
            ['key'=>'step4_title',   'label'=>'Étape 4 — titre',           'type'=>'text'],
            ['key'=>'step4_text',    'label'=>'Étape 4 — contenu',         'type'=>'rich'],
            ['key'=>'step5_title',   'label'=>'Étape 5 — titre',           'type'=>'text'],
            ['key'=>'step5_text',    'label'=>'Étape 5 — contenu',         'type'=>'rich'],
        ]],
        ['section'=>'CTA',                   'icon'=>'fa-rocket',         'color'=>'#6366f1','fields'=>[
            ['key'=>'cta_title',     'label'=>'Titre CTA',                 'type'=>'text'],
            ['key'=>'cta_btn_text',  'label'=>'Texte bouton',              'type'=>'text'],
            ['key'=>'cta_btn_url',   'label'=>'Lien bouton',               'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T7 — ESTIMATION
    // ══════════════════════════════════════════════════
    't7-estimation' => [
        ['section'=>'Hero estimation',       'icon'=>'fa-calculator',     'color'=>'#f59e0b','fields'=>[
            ['key'=>'hero_title',    'label'=>'Titre H1',                  'type'=>'text',    'hint'=>'Ex : Estimez votre bien gratuitement'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                'type'=>'textarea'],
            ['key'=>'hero_eyebrow',  'label'=>'Eyebrow / badge',           'type'=>'text'],
        ]],
        ['section'=>'Arguments confiance',   'icon'=>'fa-shield-halved',  'color'=>'#6366f1','fields'=>[
            ['key'=>'trust1_icon',   'label'=>'Argument 1 — emoji',        'type'=>'text'],
            ['key'=>'trust1_title',  'label'=>'Argument 1 — titre',        'type'=>'text'],
            ['key'=>'trust1_text',   'label'=>'Argument 1 — texte',        'type'=>'text'],
            ['key'=>'trust2_icon',   'label'=>'Argument 2 — emoji',        'type'=>'text'],
            ['key'=>'trust2_title',  'label'=>'Argument 2 — titre',        'type'=>'text'],
            ['key'=>'trust2_text',   'label'=>'Argument 2 — texte',        'type'=>'text'],
            ['key'=>'trust3_icon',   'label'=>'Argument 3 — emoji',        'type'=>'text'],
            ['key'=>'trust3_title',  'label'=>'Argument 3 — titre',        'type'=>'text'],
            ['key'=>'trust3_text',   'label'=>'Argument 3 — texte',        'type'=>'text'],
        ]],
        ['section'=>'Texte SEO',             'icon'=>'fa-pen-nib',        'color'=>'#8b5cf6','fields'=>[
            ['key'=>'seo_intro',     'label'=>'Introduction SEO',          'type'=>'rich'],
            ['key'=>'seo_content',   'label'=>'Corps de texte SEO',        'type'=>'rich'],
        ]],
        ['section'=>'CTA finale',            'icon'=>'fa-rocket',         'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',     'label'=>'Titre CTA',                 'type'=>'text'],
            ['key'=>'cta_text',      'label'=>'Texte',                     'type'=>'textarea'],
            ['key'=>'cta_btn_text',  'label'=>'Texte bouton',              'type'=>'text'],
            ['key'=>'cta_btn_url',   'label'=>'Lien bouton',               'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T8 — CONTACT
    // ══════════════════════════════════════════════════
    't8-contact' => [
        ['section'=>'En-tête contact',       'icon'=>'fa-envelope',       'color'=>'#ec4899','fields'=>[
            ['key'=>'hero_title',    'label'=>'Titre H1',                  'type'=>'text',    'hint'=>'Ex : Contactez-moi'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                'type'=>'textarea'],
            ['key'=>'hero_eyebrow',  'label'=>'Eyebrow',                   'type'=>'text'],
        ]],
        ['section'=>'Informations contact',  'icon'=>'fa-address-card',   'color'=>'#6366f1','fields'=>[
            ['key'=>'contact_intro', 'label'=>'Texte introductif',         'type'=>'rich'],
            ['key'=>'contact_phone', 'label'=>'Téléphone affiché',         'type'=>'text'],
            ['key'=>'contact_email', 'label'=>'Email affiché',             'type'=>'text'],
            ['key'=>'contact_hours', 'label'=>'Horaires',                  'type'=>'text', 'hint'=>'Ex : Lun-Sam 9h-19h'],
            ['key'=>'contact_addr',  'label'=>'Adresse',                   'type'=>'text'],
        ]],
        ['section'=>'Formulaire',            'icon'=>'fa-wpforms',        'color'=>'#0d9488','fields'=>[
            ['key'=>'form_title',    'label'=>'Titre formulaire',          'type'=>'text'],
            ['key'=>'form_subtitle', 'label'=>'Sous-titre formulaire',     'type'=>'text'],
            ['key'=>'form_cta',      'label'=>'Texte bouton envoi',        'type'=>'text'],
            ['key'=>'form_success',  'label'=>'Message de confirmation',   'type'=>'textarea'],
        ]],
        ['section'=>'Disponibilités / RDV',  'icon'=>'fa-calendar-check', 'color'=>'#f59e0b','fields'=>[
            ['key'=>'rdv_title',     'label'=>'Titre RDV',                 'type'=>'text'],
            ['key'=>'rdv_text',      'label'=>'Texte RDV',                 'type'=>'textarea'],
            ['key'=>'rdv_btn_text',  'label'=>'Bouton RDV — texte',        'type'=>'text'],
            ['key'=>'rdv_btn_url',   'label'=>'Bouton RDV — lien',         'type'=>'url', 'hint'=>'Lien Calendly ou autre'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T9 — HONORAIRES
    // ══════════════════════════════════════════════════
    't9-honoraires' => [
        ['section'=>'En-tête',               'icon'=>'fa-file-invoice',   'color'=>'#64748b','fields'=>[
            ['key'=>'hero_title',    'label'=>'Titre H1',                  'type'=>'text',    'hint'=>'Ex : Honoraires et tarifs'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                'type'=>'textarea'],
        ]],
        ['section'=>'Grille tarifaire',      'icon'=>'fa-table',          'color'=>'#6366f1','fields'=>[
            ['key'=>'tarif_intro',   'label'=>'Texte introductif',         'type'=>'rich'],
            ['key'=>'tarif1_label',  'label'=>'Tarif 1 — libellé',         'type'=>'text', 'hint'=>'Ex : Mandat simple'],
            ['key'=>'tarif1_value',  'label'=>'Tarif 1 — valeur',          'type'=>'text', 'hint'=>'Ex : 3% TTC'],
            ['key'=>'tarif2_label',  'label'=>'Tarif 2 — libellé',         'type'=>'text'],
            ['key'=>'tarif2_value',  'label'=>'Tarif 2 — valeur',          'type'=>'text'],
            ['key'=>'tarif3_label',  'label'=>'Tarif 3 — libellé',         'type'=>'text'],
            ['key'=>'tarif3_value',  'label'=>'Tarif 3 — valeur',          'type'=>'text'],
            ['key'=>'tarif_note',    'label'=>'Note légale',               'type'=>'textarea', 'hint'=>'Barème affiché conformément à la loi Alur'],
        ]],
        ['section'=>'Texte légal',           'icon'=>'fa-scale-balanced', 'color'=>'#94a3b8','fields'=>[
            ['key'=>'legal_content', 'label'=>'Contenu légal complet',     'type'=>'rich'],
        ]],
        ['section'=>'CTA',                   'icon'=>'fa-rocket',         'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',     'label'=>'Titre CTA',                 'type'=>'text'],
            ['key'=>'cta_btn_text',  'label'=>'Texte bouton',              'type'=>'text'],
            ['key'=>'cta_btn_url',   'label'=>'Lien bouton',               'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T10 — BIENS LISTING
    // ══════════════════════════════════════════════════
    't10-biens-listing' => [
        ['section'=>'En-tête biens',         'icon'=>'fa-house',          'color'=>'#14b8a6','fields'=>[
            ['key'=>'hero_title',    'label'=>'Titre H1',                  'type'=>'text',    'hint'=>'Ex : Nos biens immobiliers'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                'type'=>'textarea'],
        ]],
        ['section'=>'Texte SEO',             'icon'=>'fa-pen-nib',        'color'=>'#8b5cf6','fields'=>[
            ['key'=>'seo_intro',     'label'=>'Introduction SEO',          'type'=>'rich'],
        ]],
        ['section'=>'CTA si vide',           'icon'=>'fa-rocket',         'color'=>'#6366f1','fields'=>[
            ['key'=>'empty_title',   'label'=>'Titre si aucun bien',       'type'=>'text'],
            ['key'=>'empty_text',    'label'=>'Texte si aucun bien',       'type'=>'textarea'],
            ['key'=>'cta_btn_text',  'label'=>'Texte bouton',              'type'=>'text'],
            ['key'=>'cta_btn_url',   'label'=>'Lien bouton',               'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T11 — BIEN SINGLE
    // ══════════════════════════════════════════════════
    't11-bien-single' => [
        ['section'=>'Fiche bien',            'icon'=>'fa-house-chimney',  'color'=>'#14b8a6','fields'=>[
            ['key'=>'bien_title',    'label'=>'Titre du bien',             'type'=>'text'],
            ['key'=>'bien_intro',    'label'=>'Description courte',        'type'=>'textarea'],
            ['key'=>'bien_content',  'label'=>'Description complète',      'type'=>'rich'],
        ]],
        ['section'=>'CTA contact',           'icon'=>'fa-phone',          'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',     'label'=>'Titre CTA',                 'type'=>'text'],
            ['key'=>'cta_btn_text',  'label'=>'Texte bouton',              'type'=>'text'],
            ['key'=>'cta_btn_url',   'label'=>'Lien bouton',               'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T12 — LEGAL (Mentions légales / CGU / Politique)
    // ══════════════════════════════════════════════════
    't12-legal' => [
        ['section'=>'En-tête',               'icon'=>'fa-scale-balanced', 'color'=>'#64748b','fields'=>[
            ['key'=>'hero_title',    'label'=>'Titre de la page',          'type'=>'text',    'hint'=>'Ex : Mentions légales'],
            ['key'=>'last_update',   'label'=>'Date de mise à jour',       'type'=>'text',    'hint'=>'Ex : 1er janvier 2025'],
        ]],
        ['section'=>'Contenu légal',         'icon'=>'fa-file-lines',     'color'=>'#94a3b8','fields'=>[
            ['key'=>'legal_content', 'label'=>'Contenu complet',           'type'=>'rich'],
        ]],
        ['section'=>'Éditeur du site',       'icon'=>'fa-building',       'color'=>'#6366f1','fields'=>[
            ['key'=>'editor_name',   'label'=>'Nom éditeur',               'type'=>'text'],
            ['key'=>'editor_status', 'label'=>'Statut juridique',          'type'=>'text', 'hint'=>'Ex : Auto-entrepreneur'],
            ['key'=>'editor_siret',  'label'=>'SIRET',                     'type'=>'text'],
            ['key'=>'editor_rsac',   'label'=>'Carte professionnelle',     'type'=>'text'],
            ['key'=>'editor_addr',   'label'=>'Adresse',                   'type'=>'text'],
            ['key'=>'editor_email',  'label'=>'Email',                     'type'=>'text'],
            ['key'=>'editor_phone',  'label'=>'Téléphone',                 'type'=>'text'],
            ['key'=>'host_name',     'label'=>'Hébergeur — nom',           'type'=>'text'],
            ['key'=>'host_addr',     'label'=>'Hébergeur — adresse',       'type'=>'text'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T13 — MERCI
    // ══════════════════════════════════════════════════
    't13-merci' => [
        ['section'=>'Message de confirmation','icon'=>'fa-circle-check',  'color'=>'#10b981','fields'=>[
            ['key'=>'merci_title',   'label'=>'Titre principal',           'type'=>'text',    'hint'=>'Ex : Merci pour votre demande !'],
            ['key'=>'merci_text',    'label'=>'Texte confirmation',        'type'=>'textarea'],
        ]],
        ['section'=>'Étapes suivantes',      'icon'=>'fa-list-ol',        'color'=>'#6366f1','fields'=>[
            ['key'=>'next_step_2',   'label'=>'Étape 2 — texte',           'type'=>'text'],
            ['key'=>'next_step_3',   'label'=>'Étape 3 — texte',           'type'=>'text'],
        ]],
        ['section'=>'CTA retour',            'icon'=>'fa-rocket',         'color'=>'#0d9488','fields'=>[
            ['key'=>'cta_title',     'label'=>'Titre bloc CTA',            'type'=>'text'],
            ['key'=>'cta_desc',      'label'=>'Description',               'type'=>'textarea'],
            ['key'=>'cta_btn_primary',  'label'=>'Bouton 1 — texte',       'type'=>'text'],
            ['key'=>'cta_btn_url',      'label'=>'Bouton 1 — lien',        'type'=>'url'],
            ['key'=>'cta_btn_secondary','label'=>'Bouton 2 — texte',       'type'=>'text'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T14 — À PROPOS
    // ══════════════════════════════════════════════════
    't14-apropos' => [
        ['section'=>'Hero à propos',         'icon'=>'fa-user-tie',       'color'=>'#3b82f6','fields'=>[
            ['key'=>'hero_title',    'label'=>'Titre H1',                  'type'=>'text',    'hint'=>'Ex : À propos de Stéphanie Hulen'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre / accroche',     'type'=>'textarea'],
            ['key'=>'hero_eyebrow',  'label'=>'Eyebrow',                   'type'=>'text',    'hint'=>'Ex : Conseillère immobilière à Lannion'],
        ]],
        ['section'=>'Mon histoire',          'icon'=>'fa-heart',          'color'=>'#ec4899','fields'=>[
            ['key'=>'story_title',   'label'=>'Titre section',             'type'=>'text'],
            ['key'=>'story_text',    'label'=>'Mon histoire / parcours',   'type'=>'rich'],
            ['key'=>'story_image',   'label'=>'Photo conseiller',          'type'=>'image'],
        ]],
        ['section'=>'Mes valeurs',           'icon'=>'fa-star',           'color'=>'#f59e0b','fields'=>[
            ['key'=>'values_title',  'label'=>'Titre section',             'type'=>'text'],
            ['key'=>'val1_icon',     'label'=>'Valeur 1 — emoji',          'type'=>'text'],
            ['key'=>'val1_title',    'label'=>'Valeur 1 — titre',          'type'=>'text'],
            ['key'=>'val1_text',     'label'=>'Valeur 1 — texte',          'type'=>'textarea'],
            ['key'=>'val2_icon',     'label'=>'Valeur 2 — emoji',          'type'=>'text'],
            ['key'=>'val2_title',    'label'=>'Valeur 2 — titre',          'type'=>'text'],
            ['key'=>'val2_text',     'label'=>'Valeur 2 — texte',          'type'=>'textarea'],
            ['key'=>'val3_icon',     'label'=>'Valeur 3 — emoji',          'type'=>'text'],
            ['key'=>'val3_title',    'label'=>'Valeur 3 — titre',          'type'=>'text'],
            ['key'=>'val3_text',     'label'=>'Valeur 3 — texte',          'type'=>'textarea'],
        ]],
        ['section'=>'Certifications / réseau','icon'=>'fa-certificate',   'color'=>'#8b5cf6','fields'=>[
            ['key'=>'network_title', 'label'=>'Titre section',             'type'=>'text'],
            ['key'=>'network_text',  'label'=>'Présentation réseau',       'type'=>'rich'],
            ['key'=>'cert1',         'label'=>'Certification 1',           'type'=>'text'],
            ['key'=>'cert2',         'label'=>'Certification 2',           'type'=>'text'],
            ['key'=>'cert3',         'label'=>'Certification 3',           'type'=>'text'],
        ]],
        ['section'=>'CTA finale',            'icon'=>'fa-rocket',         'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',     'label'=>'Titre CTA',                 'type'=>'text'],
            ['key'=>'cta_text',      'label'=>'Texte',                     'type'=>'textarea'],
            ['key'=>'cta_btn_text',  'label'=>'Texte bouton',              'type'=>'text'],
            ['key'=>'cta_btn_url',   'label'=>'Lien bouton',               'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T15 — SECTEURS LISTING
    // ══════════════════════════════════════════════════
    't15-secteurs-listing' => [
        ['section'=>'En-tête secteurs',      'icon'=>'fa-map',            'color'=>'#0d9488','fields'=>[
            ['key'=>'hero_title',    'label'=>'Titre H1',                  'type'=>'text',    'hint'=>'Ex : Les quartiers de Bordeaux'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                'type'=>'textarea'],
        ]],
        ['section'=>'Texte SEO',             'icon'=>'fa-pen-nib',        'color'=>'#8b5cf6','fields'=>[
            ['key'=>'seo_intro',     'label'=>'Introduction SEO',          'type'=>'rich'],
            ['key'=>'seo_content',   'label'=>'Corps de texte',            'type'=>'rich'],
        ]],
        ['section'=>'CTA',                   'icon'=>'fa-rocket',         'color'=>'#6366f1','fields'=>[
            ['key'=>'cta_title',     'label'=>'Titre CTA',                 'type'=>'text'],
            ['key'=>'cta_btn_text',  'label'=>'Texte bouton',              'type'=>'text'],
            ['key'=>'cta_btn_url',   'label'=>'Lien bouton',               'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T16 — RAPPORT DE MARCHÉ
    // ══════════════════════════════════════════════════
    't16-rapport-marche' => [
        ['section'=>'En-tête rapport',       'icon'=>'fa-chart-bar',      'color'=>'#8b5cf6','fields'=>[
            ['key'=>'hero_title',    'label'=>'Titre H1',                  'type'=>'text',    'hint'=>'Ex : Rapport marché immobilier 2025'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                'type'=>'textarea'],
            ['key'=>'hero_period',   'label'=>'Période couverte',          'type'=>'text',    'hint'=>'Ex : 1er semestre 2025'],
        ]],
        ['section'=>'Chiffres clés',         'icon'=>'fa-hashtag',        'color'=>'#6366f1','fields'=>[
            ['key'=>'stat1_num',     'label'=>'Stat 1 — chiffre',          'type'=>'text'],
            ['key'=>'stat1_lbl',     'label'=>'Stat 1 — libellé',          'type'=>'text'],
            ['key'=>'stat2_num',     'label'=>'Stat 2 — chiffre',          'type'=>'text'],
            ['key'=>'stat2_lbl',     'label'=>'Stat 2 — libellé',          'type'=>'text'],
            ['key'=>'stat3_num',     'label'=>'Stat 3 — chiffre',          'type'=>'text'],
            ['key'=>'stat3_lbl',     'label'=>'Stat 3 — libellé',          'type'=>'text'],
            ['key'=>'stat4_num',     'label'=>'Stat 4 — chiffre',          'type'=>'text'],
            ['key'=>'stat4_lbl',     'label'=>'Stat 4 — libellé',          'type'=>'text'],
        ]],
        ['section'=>'Analyse du marché',     'icon'=>'fa-pen-nib',        'color'=>'#0ea5e9','fields'=>[
            ['key'=>'analyse_title', 'label'=>'Titre section',             'type'=>'text'],
            ['key'=>'analyse_text',  'label'=>'Analyse détaillée',         'type'=>'rich'],
        ]],
        ['section'=>'Tendances',             'icon'=>'fa-arrow-trend-up', 'color'=>'#f59e0b','fields'=>[
            ['key'=>'trend1_title',  'label'=>'Tendance 1 — titre',        'type'=>'text'],
            ['key'=>'trend1_text',   'label'=>'Tendance 1 — texte',        'type'=>'textarea'],
            ['key'=>'trend2_title',  'label'=>'Tendance 2 — titre',        'type'=>'text'],
            ['key'=>'trend2_text',   'label'=>'Tendance 2 — texte',        'type'=>'textarea'],
            ['key'=>'trend3_title',  'label'=>'Tendance 3 — titre',        'type'=>'text'],
            ['key'=>'trend3_text',   'label'=>'Tendance 3 — texte',        'type'=>'textarea'],
        ]],
        ['section'=>'CTA téléchargement',    'icon'=>'fa-download',       'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',     'label'=>'Titre CTA',                 'type'=>'text'],
            ['key'=>'cta_text',      'label'=>'Texte',                     'type'=>'textarea'],
            ['key'=>'cta_btn_text',  'label'=>'Texte bouton',              'type'=>'text'],
            ['key'=>'cta_btn_url',   'label'=>'Lien bouton / PDF',         'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // STANDARD — Fallback générique
    // ══════════════════════════════════════════════════
    'standard' => [
        ['section'=>'En-tête de page',       'icon'=>'fa-heading',        'color'=>'#6366f1','fields'=>[
            ['key'=>'hero_title',    'label'=>'Titre (H1)',                'type'=>'text'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                'type'=>'textarea'],
            ['key'=>'hero_eyebrow',  'label'=>'Eyebrow',                   'type'=>'text'],
        ]],
        ['section'=>'Contenu',               'icon'=>'fa-pen-nib',        'color'=>'#8b5cf6','fields'=>[
            ['key'=>'body_content',  'label'=>'Corps de texte',            'type'=>'rich'],
        ]],
        ['section'=>'CTA',                   'icon'=>'fa-rocket',         'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',     'label'=>'Titre CTA',                 'type'=>'text'],
            ['key'=>'cta_text',      'label'=>'Texte CTA',                 'type'=>'textarea'],
            ['key'=>'cta_btn_text',  'label'=>'Texte bouton',              'type'=>'text'],
            ['key'=>'cta_btn_url',   'label'=>'Lien',                      'type'=>'url'],
        ]],
    ],

];

// ── Aliases ─────────────────────────────────────────────────
$TPL['secteur']            = $TPL['t3-secteur'];
$TPL['blog']               = $TPL['t4-blog-hub'];
$TPL['blog-hub']           = $TPL['t4-blog-hub'];
$TPL['article']            = $TPL['t5-article'];
$TPL['guide']              = $TPL['t6-guide'];
$TPL['estimation']         = $TPL['t7-estimation'];
$TPL['contact']            = $TPL['t8-contact'];
$TPL['honoraires']         = $TPL['t9-honoraires'];
$TPL['biens-listing']      = $TPL['t10-biens-listing'];
$TPL['biens']              = $TPL['t10-biens-listing'];
$TPL['bien-single']        = $TPL['t11-bien-single'];
$TPL['legal']              = $TPL['t12-legal'];
$TPL['Legal']              = $TPL['t12-legal'];
$TPL['merci']              = $TPL['t13-merci'];
$TPL['apropos']            = $TPL['t14-apropos'];
$TPL['secteurs-listing']   = $TPL['t15-secteurs-listing'];
$TPL['secteurs']           = $TPL['t15-secteurs-listing'];
$TPL['rapport-marche']     = $TPL['t16-rapport-marche'];
$TPL['default']            = $TPL['standard'];
$TPL['page']               = $TPL['standard'];
$TPL['Landing']            = $TPL['standard'];