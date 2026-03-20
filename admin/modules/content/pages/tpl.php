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
    // T20 — FINANCEMENT
    // ══════════════════════════════════════════════════
    't20-financement' => [
        ['section'=>'Hero',                     'icon'=>'fa-money-bill-wave','color'=>'#10b981','fields'=>[
            ['key'=>'hero_eyebrow',  'label'=>'Eyebrow',                       'type'=>'text',    'hint'=>'Ex : Financement immobilier'],
            ['key'=>'hero_title',    'label'=>'Titre H1',                      'type'=>'text'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                    'type'=>'textarea'],
            ['key'=>'hero_cta_text', 'label'=>'CTA — texte bouton',            'type'=>'text'],
        ]],
        ['section'=>'Simulateur de prêt',       'icon'=>'fa-calculator',    'color'=>'#6366f1','fields'=>[
            ['key'=>'sim_title',     'label'=>'Titre simulateur',              'type'=>'text'],
            ['key'=>'sim_text',      'label'=>'Texte explicatif',              'type'=>'textarea'],
        ]],
        ['section'=>'Avantages courtier',       'icon'=>'fa-handshake',     'color'=>'#f59e0b','fields'=>[
            ['key'=>'av_title',  'label'=>'Titre section',                     'type'=>'text'],
            ['key'=>'av1_icon',  'label'=>'Avantage 1 — emoji',               'type'=>'text'],
            ['key'=>'av1_title', 'label'=>'Avantage 1 — titre',               'type'=>'text'],
            ['key'=>'av1_text',  'label'=>'Avantage 1 — texte',               'type'=>'textarea'],
            ['key'=>'av2_icon',  'label'=>'Avantage 2 — emoji',               'type'=>'text'],
            ['key'=>'av2_title', 'label'=>'Avantage 2 — titre',               'type'=>'text'],
            ['key'=>'av2_text',  'label'=>'Avantage 2 — texte',               'type'=>'textarea'],
            ['key'=>'av3_icon',  'label'=>'Avantage 3 — emoji',               'type'=>'text'],
            ['key'=>'av3_title', 'label'=>'Avantage 3 — titre',               'type'=>'text'],
            ['key'=>'av3_text',  'label'=>'Avantage 3 — texte',               'type'=>'textarea'],
        ]],
        ['section'=>'Courtiers partenaires',    'icon'=>'fa-building-columns','color'=>'#0ea5e9','fields'=>[
            ['key'=>'court_title',  'label'=>'Titre section',                  'type'=>'text'],
            ['key'=>'court_text',   'label'=>'Texte introductif',              'type'=>'textarea'],
            ['key'=>'court1_name',  'label'=>'Courtier 1 — nom',              'type'=>'text'],
            ['key'=>'court1_desc',  'label'=>'Courtier 1 — description',      'type'=>'textarea'],
            ['key'=>'court1_phone', 'label'=>'Courtier 1 — téléphone',        'type'=>'text'],
            ['key'=>'court2_name',  'label'=>'Courtier 2 — nom',              'type'=>'text'],
            ['key'=>'court2_desc',  'label'=>'Courtier 2 — description',      'type'=>'textarea'],
            ['key'=>'court2_phone', 'label'=>'Courtier 2 — téléphone',        'type'=>'text'],
            ['key'=>'court3_name',  'label'=>'Courtier 3 — nom',              'type'=>'text'],
            ['key'=>'court3_desc',  'label'=>'Courtier 3 — description',      'type'=>'textarea'],
            ['key'=>'court3_phone', 'label'=>'Courtier 3 — téléphone',        'type'=>'text'],
        ]],
        ['section'=>'Guide SEO',                'icon'=>'fa-book-open',     'color'=>'#8b5cf6','fields'=>[
            ['key'=>'guide_title', 'label'=>'Titre du guide',                  'type'=>'text'],
            ['key'=>'g1_num',      'label'=>'Article 1 — numéro',             'type'=>'text'],
            ['key'=>'g1_title',    'label'=>'Article 1 — titre',              'type'=>'text'],
            ['key'=>'g1_text',     'label'=>'Article 1 — contenu',            'type'=>'rich'],
            ['key'=>'g2_num',      'label'=>'Article 2 — numéro',             'type'=>'text'],
            ['key'=>'g2_title',    'label'=>'Article 2 — titre',              'type'=>'text'],
            ['key'=>'g2_text',     'label'=>'Article 2 — contenu',            'type'=>'rich'],
            ['key'=>'g3_num',      'label'=>'Article 3 — numéro',             'type'=>'text'],
            ['key'=>'g3_title',    'label'=>'Article 3 — titre',              'type'=>'text'],
            ['key'=>'g3_text',     'label'=>'Article 3 — contenu',            'type'=>'rich'],
        ]],
        ['section'=>'CTA Finale',               'icon'=>'fa-rocket',        'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',    'label'=>'Titre',                          'type'=>'text'],
            ['key'=>'cta_text',     'label'=>'Description',                    'type'=>'textarea'],
            ['key'=>'cta_btn_text', 'label'=>'Texte bouton',                   'type'=>'text'],
            ['key'=>'cta_btn_url',  'label'=>'Lien bouton',                    'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T21 — RDV EN LIGNE
    // ══════════════════════════════════════════════════
    't21-rdv' => [
        ['section'=>'Hero',                     'icon'=>'fa-calendar-check','color'=>'#6366f1','fields'=>[
            ['key'=>'hero_eyebrow',  'label'=>'Eyebrow',                       'type'=>'text',    'hint'=>'Ex : Rendez-vous en ligne'],
            ['key'=>'hero_title',    'label'=>'Titre H1',                      'type'=>'text'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                    'type'=>'textarea'],
        ]],
        ['section'=>'Types de RDV',             'icon'=>'fa-list-check',    'color'=>'#0d9488','fields'=>[
            ['key'=>'types_title',  'label'=>'Titre section',                  'type'=>'text'],
            ['key'=>'type1_icon',   'label'=>'Type 1 — emoji',                'type'=>'text'],
            ['key'=>'type1_title',  'label'=>'Type 1 — titre',                'type'=>'text'],
            ['key'=>'type1_text',   'label'=>'Type 1 — texte',                'type'=>'textarea'],
            ['key'=>'type1_duree',  'label'=>'Type 1 — durée',                'type'=>'text',    'hint'=>'Ex : 45 min'],
            ['key'=>'type2_icon',   'label'=>'Type 2 — emoji',                'type'=>'text'],
            ['key'=>'type2_title',  'label'=>'Type 2 — titre',                'type'=>'text'],
            ['key'=>'type2_text',   'label'=>'Type 2 — texte',                'type'=>'textarea'],
            ['key'=>'type2_duree',  'label'=>'Type 2 — durée',                'type'=>'text'],
            ['key'=>'type3_icon',   'label'=>'Type 3 — emoji',                'type'=>'text'],
            ['key'=>'type3_title',  'label'=>'Type 3 — titre',                'type'=>'text'],
            ['key'=>'type3_text',   'label'=>'Type 3 — texte',                'type'=>'textarea'],
            ['key'=>'type3_duree',  'label'=>'Type 3 — durée',                'type'=>'text'],
            ['key'=>'type4_icon',   'label'=>'Type 4 — emoji',                'type'=>'text'],
            ['key'=>'type4_title',  'label'=>'Type 4 — titre',                'type'=>'text'],
            ['key'=>'type4_text',   'label'=>'Type 4 — texte',                'type'=>'textarea'],
            ['key'=>'type4_duree',  'label'=>'Type 4 — durée',                'type'=>'text'],
        ]],
        ['section'=>'Réservation / Calendrier', 'icon'=>'fa-calendar-days','color'=>'#f59e0b','fields'=>[
            ['key'=>'book_title',    'label'=>'Titre section',                 'type'=>'text'],
            ['key'=>'book_text',     'label'=>'Texte explicatif',              'type'=>'textarea'],
            ['key'=>'book_url',      'label'=>'URL Calendly / Cal.com',        'type'=>'url',     'hint'=>'Lien d\'intégration du calendrier'],
            ['key'=>'book_btn_text', 'label'=>'Texte bouton agenda',           'type'=>'text'],
        ]],
        ['section'=>'Infos pratiques',          'icon'=>'fa-info-circle',   'color'=>'#0ea5e9','fields'=>[
            ['key'=>'info_title',    'label'=>'Titre section',                 'type'=>'text'],
            ['key'=>'info_lieu',     'label'=>'Lieu de RDV',                   'type'=>'text'],
            ['key'=>'info_horaires', 'label'=>'Horaires',                      'type'=>'text'],
            ['key'=>'info_delai',    'label'=>'Délai de confirmation',         'type'=>'text'],
        ]],
        ['section'=>'CTA Finale',               'icon'=>'fa-rocket',        'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',      'label'=>'Titre',                        'type'=>'text'],
            ['key'=>'cta_text',       'label'=>'Description',                  'type'=>'textarea'],
            ['key'=>'cta_phone_text', 'label'=>'Numéro de téléphone',          'type'=>'text'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T22 — ANNUAIRE PARTENAIRES
    // ══════════════════════════════════════════════════
    't22-annuaire-partenaires' => [
        ['section'=>'Hero',                     'icon'=>'fa-address-book',  'color'=>'#8b5cf6','fields'=>[
            ['key'=>'hero_eyebrow',  'label'=>'Eyebrow',                       'type'=>'text',    'hint'=>'Ex : Annuaire partenaires'],
            ['key'=>'hero_title',    'label'=>'Titre H1',                      'type'=>'text'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                    'type'=>'textarea'],
        ]],
        ['section'=>'Introduction',             'icon'=>'fa-pen-nib',       'color'=>'#0ea5e9','fields'=>[
            ['key'=>'intro_title',   'label'=>'Titre intro',                   'type'=>'text'],
            ['key'=>'intro_text',    'label'=>'Texte intro',                   'type'=>'textarea'],
        ]],
        ['section'=>'Notaires',                 'icon'=>'fa-scale-balanced','color'=>'#6366f1','fields'=>[
            ['key'=>'not_title',   'label'=>'Titre catégorie',                 'type'=>'text'],
            ['key'=>'not_icon',    'label'=>'Icône catégorie',                 'type'=>'text'],
            ['key'=>'not1_name',   'label'=>'Notaire 1 — nom',                'type'=>'text'],
            ['key'=>'not1_desc',   'label'=>'Notaire 1 — description',        'type'=>'textarea'],
            ['key'=>'not1_phone',  'label'=>'Notaire 1 — téléphone',          'type'=>'text'],
            ['key'=>'not1_addr',   'label'=>'Notaire 1 — adresse',            'type'=>'text'],
            ['key'=>'not2_name',   'label'=>'Notaire 2 — nom',                'type'=>'text'],
            ['key'=>'not2_desc',   'label'=>'Notaire 2 — description',        'type'=>'textarea'],
            ['key'=>'not2_phone',  'label'=>'Notaire 2 — téléphone',          'type'=>'text'],
            ['key'=>'not2_addr',   'label'=>'Notaire 2 — adresse',            'type'=>'text'],
        ]],
        ['section'=>'Diagnostiqueurs',          'icon'=>'fa-microscope',    'color'=>'#0d9488','fields'=>[
            ['key'=>'diag_title',   'label'=>'Titre catégorie',                'type'=>'text'],
            ['key'=>'diag_icon',    'label'=>'Icône catégorie',                'type'=>'text'],
            ['key'=>'diag1_name',   'label'=>'Diagnostiqueur 1 — nom',        'type'=>'text'],
            ['key'=>'diag1_desc',   'label'=>'Diagnostiqueur 1 — description','type'=>'textarea'],
            ['key'=>'diag1_phone',  'label'=>'Diagnostiqueur 1 — téléphone',  'type'=>'text'],
            ['key'=>'diag2_name',   'label'=>'Diagnostiqueur 2 — nom',        'type'=>'text'],
            ['key'=>'diag2_desc',   'label'=>'Diagnostiqueur 2 — description','type'=>'textarea'],
            ['key'=>'diag2_phone',  'label'=>'Diagnostiqueur 2 — téléphone',  'type'=>'text'],
        ]],
        ['section'=>'Artisans & travaux',       'icon'=>'fa-hammer',        'color'=>'#f59e0b','fields'=>[
            ['key'=>'art_title',    'label'=>'Titre catégorie',                'type'=>'text'],
            ['key'=>'art_icon',     'label'=>'Icône catégorie',                'type'=>'text'],
            ['key'=>'art1_name',    'label'=>'Artisan 1 — nom',               'type'=>'text'],
            ['key'=>'art1_desc',    'label'=>'Artisan 1 — description',       'type'=>'textarea'],
            ['key'=>'art1_metier',  'label'=>'Artisan 1 — métier',            'type'=>'text'],
            ['key'=>'art1_phone',   'label'=>'Artisan 1 — téléphone',         'type'=>'text'],
            ['key'=>'art2_name',    'label'=>'Artisan 2 — nom',               'type'=>'text'],
            ['key'=>'art2_desc',    'label'=>'Artisan 2 — description',       'type'=>'textarea'],
            ['key'=>'art2_metier',  'label'=>'Artisan 2 — métier',            'type'=>'text'],
            ['key'=>'art2_phone',   'label'=>'Artisan 2 — téléphone',         'type'=>'text'],
            ['key'=>'art3_name',    'label'=>'Artisan 3 — nom',               'type'=>'text'],
            ['key'=>'art3_desc',    'label'=>'Artisan 3 — description',       'type'=>'textarea'],
            ['key'=>'art3_metier',  'label'=>'Artisan 3 — métier',            'type'=>'text'],
            ['key'=>'art3_phone',   'label'=>'Artisan 3 — téléphone',         'type'=>'text'],
        ]],
        ['section'=>'Autres professionnels',    'icon'=>'fa-handshake',     'color'=>'#ec4899','fields'=>[
            ['key'=>'autres_title',   'label'=>'Titre catégorie',              'type'=>'text'],
            ['key'=>'autres_icon',    'label'=>'Icône catégorie',              'type'=>'text'],
            ['key'=>'autre1_name',    'label'=>'Pro 1 — nom',                 'type'=>'text'],
            ['key'=>'autre1_desc',    'label'=>'Pro 1 — description',         'type'=>'textarea'],
            ['key'=>'autre1_metier',  'label'=>'Pro 1 — métier',              'type'=>'text'],
            ['key'=>'autre1_phone',   'label'=>'Pro 1 — téléphone',           'type'=>'text'],
            ['key'=>'autre2_name',    'label'=>'Pro 2 — nom',                 'type'=>'text'],
            ['key'=>'autre2_desc',    'label'=>'Pro 2 — description',         'type'=>'textarea'],
            ['key'=>'autre2_metier',  'label'=>'Pro 2 — métier',              'type'=>'text'],
            ['key'=>'autre2_phone',   'label'=>'Pro 2 — téléphone',           'type'=>'text'],
        ]],
        ['section'=>'CTA Finale',               'icon'=>'fa-rocket',        'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',    'label'=>'Titre',                          'type'=>'text'],
            ['key'=>'cta_text',     'label'=>'Description',                    'type'=>'textarea'],
            ['key'=>'cta_btn_text', 'label'=>'Texte bouton',                   'type'=>'text'],
            ['key'=>'cta_btn_url',  'label'=>'Lien bouton',                    'type'=>'url'],
        ]],
    ],

    // ══════════════════════════════════════════════════
    // T23 — FAQ
    // ══════════════════════════════════════════════════
    't23-faq' => [
        ['section'=>'Hero',                     'icon'=>'fa-circle-question','color'=>'#6366f1','fields'=>[
            ['key'=>'hero_eyebrow',  'label'=>'Eyebrow',                       'type'=>'text',    'hint'=>'Ex : FAQ'],
            ['key'=>'hero_title',    'label'=>'Titre H1',                      'type'=>'text'],
            ['key'=>'hero_subtitle', 'label'=>'Sous-titre',                    'type'=>'textarea'],
        ]],
        ['section'=>'Questions — Achat',        'icon'=>'fa-house',         'color'=>'#0d9488','fields'=>[
            ['key'=>'cat_achat_title','label'=>'Titre catégorie Achat',        'type'=>'text'],
            ['key'=>'faq1_q',   'label'=>'Question 1',                         'type'=>'text'],
            ['key'=>'faq1_a',   'label'=>'Réponse 1',                          'type'=>'textarea'],
            ['key'=>'faq2_q',   'label'=>'Question 2',                         'type'=>'text'],
            ['key'=>'faq2_a',   'label'=>'Réponse 2',                          'type'=>'textarea'],
            ['key'=>'faq3_q',   'label'=>'Question 3',                         'type'=>'text'],
            ['key'=>'faq3_a',   'label'=>'Réponse 3',                          'type'=>'textarea'],
        ]],
        ['section'=>'Questions — Vente',        'icon'=>'fa-tag',           'color'=>'#f59e0b','fields'=>[
            ['key'=>'cat_vente_title','label'=>'Titre catégorie Vente',        'type'=>'text'],
            ['key'=>'faq4_q',   'label'=>'Question 4',                         'type'=>'text'],
            ['key'=>'faq4_a',   'label'=>'Réponse 4',                          'type'=>'textarea'],
            ['key'=>'faq5_q',   'label'=>'Question 5',                         'type'=>'text'],
            ['key'=>'faq5_a',   'label'=>'Réponse 5',                          'type'=>'textarea'],
            ['key'=>'faq6_q',   'label'=>'Question 6',                         'type'=>'text'],
            ['key'=>'faq6_a',   'label'=>'Réponse 6',                          'type'=>'textarea'],
        ]],
        ['section'=>'Questions — Financement',  'icon'=>'fa-money-bill',    'color'=>'#10b981','fields'=>[
            ['key'=>'cat_fin_title', 'label'=>'Titre catégorie Financement',   'type'=>'text'],
            ['key'=>'faq7_q',   'label'=>'Question 7',                         'type'=>'text'],
            ['key'=>'faq7_a',   'label'=>'Réponse 7',                          'type'=>'textarea'],
            ['key'=>'faq8_q',   'label'=>'Question 8',                         'type'=>'text'],
            ['key'=>'faq8_a',   'label'=>'Réponse 8',                          'type'=>'textarea'],
        ]],
        ['section'=>'Questions — Général',      'icon'=>'fa-circle-info',   'color'=>'#8b5cf6','fields'=>[
            ['key'=>'cat_gen_title', 'label'=>'Titre catégorie Général',       'type'=>'text'],
            ['key'=>'faq9_q',   'label'=>'Question 9',                         'type'=>'text'],
            ['key'=>'faq9_a',   'label'=>'Réponse 9',                          'type'=>'textarea'],
            ['key'=>'faq10_q',  'label'=>'Question 10',                        'type'=>'text'],
            ['key'=>'faq10_a',  'label'=>'Réponse 10',                         'type'=>'textarea'],
        ]],
        ['section'=>'CTA Finale',               'icon'=>'fa-rocket',        'color'=>'#10b981','fields'=>[
            ['key'=>'cta_title',    'label'=>'Titre',                          'type'=>'text'],
            ['key'=>'cta_text',     'label'=>'Description',                    'type'=>'textarea'],
            ['key'=>'cta_btn_text', 'label'=>'Texte bouton',                   'type'=>'text'],
            ['key'=>'cta_btn_url',  'label'=>'Lien bouton',                    'type'=>'url'],
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
$TPL['financement']        = $TPL['t20-financement'];
$TPL['rdv']                = $TPL['t21-rdv'];
$TPL['rendez-vous']        = $TPL['t21-rdv'];
$TPL['annuaire-partenaires'] = $TPL['t22-annuaire-partenaires'];
$TPL['partenaires']        = $TPL['t22-annuaire-partenaires'];
$TPL['faq']                = $TPL['t23-faq'];
$TPL['default']            = $TPL['standard'];
$TPL['page']               = $TPL['standard'];
$TPL['Landing']            = $TPL['standard'];