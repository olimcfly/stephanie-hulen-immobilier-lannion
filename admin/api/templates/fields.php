<?php
/**
 * /admin/api/templates/fields.php
 * 
 * Retourne la liste des champs éditables pour un template donné.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

$input = json_decode(file_get_contents('php://input'), true);
$templateId = $input['template'] ?? '';

// Schéma des templates
$schemas = [
    't1-accueil' => [
        // HERO
        'hero_eyebrow' => [
            'label' => '🎯 Hero - Accroche',
            'type' => 'text',
            'default' => 'Conseiller immobilier à votre ville',
            'hint' => 'Petit texte au-dessus du titre principal'
        ],
        'hero_title' => [
            'label' => '🎯 Hero - Titre principal',
            'type' => 'text',
            'default' => 'Votre expert immobilier de confiance',
            'hint' => 'H1 principal (SEO important)'
        ],
        'hero_subtitle' => [
            'label' => '🎯 Hero - Sous-titre',
            'type' => 'text',
            'default' => 'Estimation gratuite, accompagnement personnalisé, résultats concrets.',
            'hint' => 'Description courte sous le H1'
        ],
        'hero_cta_text' => [
            'label' => '🎯 Hero - Bouton 1',
            'type' => 'text',
            'default' => 'Estimer mon bien gratuitement',
            'hint' => 'Texte du CTA principal'
        ],
        'hero_cta_url' => [
            'label' => '🎯 Hero - URL Bouton 1',
            'type' => 'text',
            'default' => '/estimation',
            'hint' => 'Lien vers estimation'
        ],
        'hero_cta2_text' => [
            'label' => '🎯 Hero - Bouton 2',
            'type' => 'text',
            'default' => 'Découvrir mes services',
            'hint' => 'Texte du CTA secondaire'
        ],
        'hero_cta2_url' => [
            'label' => '🎯 Hero - URL Bouton 2',
            'type' => 'text',
            'default' => '/vendre',
            'hint' => 'Lien vers services'
        ],
        'hero_stat1_num' => [
            'label' => '📊 Stat 1 - Nombre',
            'type' => 'text',
            'default' => '98%',
            'hint' => 'Ex: 98%, 500+, 12ans'
        ],
        'hero_stat1_lbl' => [
            'label' => '📊 Stat 1 - Label',
            'type' => 'text',
            'default' => 'clients satisfaits',
            'hint' => 'Description de la stat'
        ],
        'hero_stat2_num' => [
            'label' => '📊 Stat 2 - Nombre',
            'type' => 'text',
            'default' => '45j',
            'hint' => ''
        ],
        'hero_stat2_lbl' => [
            'label' => '📊 Stat 2 - Label',
            'type' => 'text',
            'default' => 'délai moyen de vente',
            'hint' => ''
        ],
        'hero_stat3_num' => [
            'label' => '📊 Stat 3 - Nombre',
            'type' => 'text',
            'default' => '12+',
            'hint' => ''
        ],
        'hero_stat3_lbl' => [
            'label' => '📊 Stat 3 - Label',
            'type' => 'text',
            'default' => 'années d\'expérience',
            'hint' => ''
        ],

        // BÉNÉFICES
        'ben_title' => [
            'label' => '✨ Section Bénéfices - Titre',
            'type' => 'text',
            'default' => 'Pourquoi choisir un conseiller local ?',
            'hint' => ''
        ],
        'ben1_icon' => [
            'label' => '✨ Bénéfice 1 - Emoji/Icône',
            'type' => 'text',
            'default' => '📍',
            'hint' => 'Emoji ou texte court'
        ],
        'ben1_title' => [
            'label' => '✨ Bénéfice 1 - Titre',
            'type' => 'text',
            'default' => 'Expertise locale',
            'hint' => ''
        ],
        'ben1_text' => [
            'label' => '✨ Bénéfice 1 - Texte',
            'type' => 'text',
            'default' => 'Connaissance approfondie des quartiers...',
            'hint' => ''
        ],
        'ben2_icon' => [
            'label' => '✨ Bénéfice 2 - Emoji/Icône',
            'type' => 'text',
            'default' => '🤝',
            'hint' => ''
        ],
        'ben2_title' => [
            'label' => '✨ Bénéfice 2 - Titre',
            'type' => 'text',
            'default' => 'Accompagnement personnalisé',
            'hint' => ''
        ],
        'ben2_text' => [
            'label' => '✨ Bénéfice 2 - Texte',
            'type' => 'text',
            'default' => 'Un seul interlocuteur...',
            'hint' => ''
        ],
        'ben3_icon' => [
            'label' => '✨ Bénéfice 3 - Emoji/Icône',
            'type' => 'text',
            'default' => '🏆',
            'hint' => ''
        ],
        'ben3_title' => [
            'label' => '✨ Bénéfice 3 - Titre',
            'type' => 'text',
            'default' => 'Réseau eXp France',
            'hint' => ''
        ],
        'ben3_text' => [
            'label' => '✨ Bénéfice 3 - Texte',
            'type' => 'text',
            'default' => 'Accès à des outils digitaux...',
            'hint' => ''
        ],

        // PRÉSENTATION
        'pres_title' => [
            'label' => '👤 Présentation - Titre',
            'type' => 'text',
            'default' => 'Votre conseiller',
            'hint' => ''
        ],
        'pres_sub' => [
            'label' => '👤 Présentation - Sous-titre',
            'type' => 'text',
            'default' => 'Conseiller indépendant eXp France',
            'hint' => ''
        ],
        'pres_text' => [
            'label' => '👤 Présentation - Texte (HTML)',
            'type' => 'richtext',
            'default' => '<p>Passionné par l\'immobilier...</p>',
            'hint' => 'Peut contenir du HTML'
        ],
        'pres_cta_text' => [
            'label' => '👤 Présentation - Bouton',
            'type' => 'text',
            'default' => 'En savoir plus sur moi',
            'hint' => ''
        ],
        'pres_cta_url' => [
            'label' => '👤 Présentation - URL Bouton',
            'type' => 'text',
            'default' => '/a-propos',
            'hint' => ''
        ],
        'pres_tag1' => [
            'label' => '👤 Présentation - Tag 1',
            'type' => 'text',
            'default' => '✓ Conseiller certifié',
            'hint' => ''
        ],
        'pres_tag2' => [
            'label' => '👤 Présentation - Tag 2',
            'type' => 'text',
            'default' => '✓ 100% local',
            'hint' => ''
        ],
        'pres_tag3' => [
            'label' => '👤 Présentation - Tag 3',
            'type' => 'text',
            'default' => '✓ eXp France',
            'hint' => ''
        ],

        // EXPERTISE
        'exp_title' => [
            'label' => '🎯 Expertise - Titre',
            'type' => 'text',
            'default' => 'Mon expertise à votre service',
            'hint' => ''
        ],
        'exp1_icon' => [
            'label' => '🎯 Expertise 1 - Icône',
            'type' => 'text',
            'default' => '🏠',
            'hint' => ''
        ],
        'exp1_title' => [
            'label' => '🎯 Expertise 1 - Titre',
            'type' => 'text',
            'default' => 'Vente immobilière',
            'hint' => ''
        ],
        'exp1_text' => [
            'label' => '🎯 Expertise 1 - Texte',
            'type' => 'text',
            'default' => 'Estimation, stratégie, photos pro...',
            'hint' => ''
        ],
        'exp1_link' => [
            'label' => '🎯 Expertise 1 - Lien',
            'type' => 'text',
            'default' => '/vendre',
            'hint' => ''
        ],
        'exp2_icon' => [
            'label' => '🎯 Expertise 2 - Icône',
            'type' => 'text',
            'default' => '🔑',
            'hint' => ''
        ],
        'exp2_title' => [
            'label' => '🎯 Expertise 2 - Titre',
            'type' => 'text',
            'default' => 'Achat immobilier',
            'hint' => ''
        ],
        'exp2_text' => [
            'label' => '🎯 Expertise 2 - Texte',
            'type' => 'text',
            'default' => 'Sélection, visites, négociation...',
            'hint' => ''
        ],
        'exp2_link' => [
            'label' => '🎯 Expertise 2 - Lien',
            'type' => 'text',
            'default' => '/acheter',
            'hint' => ''
        ],
        'exp3_icon' => [
            'label' => '🎯 Expertise 3 - Icône',
            'type' => 'text',
            'default' => '📈',
            'hint' => ''
        ],
        'exp3_title' => [
            'label' => '🎯 Expertise 3 - Titre',
            'type' => 'text',
            'default' => 'Investissement locatif',
            'hint' => ''
        ],
        'exp3_text' => [
            'label' => '🎯 Expertise 3 - Texte',
            'type' => 'text',
            'default' => 'Analyse de rentabilité...',
            'hint' => ''
        ],
        'exp3_link' => [
            'label' => '🎯 Expertise 3 - Lien',
            'type' => 'text',
            'default' => '/investir',
            'hint' => ''
        ],

        // MÉTHODE
        'method_title' => [
            'label' => '📋 Méthode - Titre',
            'type' => 'text',
            'default' => 'Comment je travaille',
            'hint' => ''
        ],
        'step1_num' => [
            'label' => '📋 Étape 1 - Numéro',
            'type' => 'text',
            'default' => '01',
            'hint' => ''
        ],
        'step1_title' => [
            'label' => '📋 Étape 1 - Titre',
            'type' => 'text',
            'default' => 'Premier contact gratuit',
            'hint' => ''
        ],
        'step1_text' => [
            'label' => '📋 Étape 1 - Texte',
            'type' => 'text',
            'default' => 'Échange de 30 minutes...',
            'hint' => ''
        ],
        'step2_num' => [
            'label' => '📋 Étape 2 - Numéro',
            'type' => 'text',
            'default' => '02',
            'hint' => ''
        ],
        'step2_title' => [
            'label' => '📋 Étape 2 - Titre',
            'type' => 'text',
            'default' => 'Stratégie sur-mesure',
            'hint' => ''
        ],
        'step2_text' => [
            'label' => '📋 Étape 2 - Texte',
            'type' => 'text',
            'default' => 'Analyse du marché local...',
            'hint' => ''
        ],
        'step3_num' => [
            'label' => '📋 Étape 3 - Numéro',
            'type' => 'text',
            'default' => '03',
            'hint' => ''
        ],
        'step3_title' => [
            'label' => '📋 Étape 3 - Titre',
            'type' => 'text',
            'default' => 'Accompagnement jusqu\'au bout',
            'hint' => ''
        ],
        'step3_text' => [
            'label' => '📋 Étape 3 - Texte',
            'type' => 'text',
            'default' => 'Suivi personnalisé jusqu\'à la signature...',
            'hint' => ''
        ],
        'method_cta_text' => [
            'label' => '📋 Méthode - Bouton',
            'type' => 'text',
            'default' => 'Prendre rendez-vous',
            'hint' => ''
        ],
        'method_cta_url' => [
            'label' => '📋 Méthode - URL Bouton',
            'type' => 'text',
            'default' => '/contact',
            'hint' => ''
        ],

        // GUIDE
        'guide_title' => [
            'label' => '📖 Guide - Titre',
            'type' => 'text',
            'default' => 'Tout savoir sur l\'immobilier',
            'hint' => ''
        ],
        'g1_num' => [
            'label' => '📖 Guide 1 - Numéro',
            'type' => 'text',
            'default' => '01',
            'hint' => ''
        ],
        'g1_title' => [
            'label' => '📖 Guide 1 - Titre',
            'type' => 'text',
            'default' => 'Le marché immobilier',
            'hint' => ''
        ],
        'g1_text' => [
            'label' => '📖 Guide 1 - Texte (HTML)',
            'type' => 'richtext',
            'default' => '<p>Le marché immobilier...</p>',
            'hint' => 'Peut contenir du HTML'
        ],
        'g2_num' => [
            'label' => '📖 Guide 2 - Numéro',
            'type' => 'text',
            'default' => '02',
            'hint' => ''
        ],
        'g2_title' => [
            'label' => '📖 Guide 2 - Titre',
            'type' => 'text',
            'default' => 'Vendre ou acheter',
            'hint' => ''
        ],
        'g2_text' => [
            'label' => '📖 Guide 2 - Texte (HTML)',
            'type' => 'richtext',
            'default' => '<p>La première étape...</p>',
            'hint' => 'Peut contenir du HTML'
        ],
        'g3_num' => [
            'label' => '📖 Guide 3 - Numéro',
            'type' => 'text',
            'default' => '03',
            'hint' => ''
        ],
        'g3_title' => [
            'label' => '📖 Guide 3 - Titre',
            'type' => 'text',
            'default' => 'Conseiller indépendant',
            'hint' => ''
        ],
        'g3_text' => [
            'label' => '📖 Guide 3 - Texte (HTML)',
            'type' => 'richtext',
            'default' => '<p>Contrairement aux agences...</p>',
            'hint' => 'Peut contenir du HTML'
        ],

        // CTA FINALE
        'cta_title' => [
            'label' => '🎬 CTA - Titre',
            'type' => 'text',
            'default' => 'Votre projet immobilier commence ici',
            'hint' => ''
        ],
        'cta_text' => [
            'label' => '🎬 CTA - Texte',
            'type' => 'text',
            'default' => 'Estimation gratuite...',
            'hint' => ''
        ],
        'cta_btn_text' => [
            'label' => '🎬 CTA - Bouton',
            'type' => 'text',
            'default' => 'Je veux une estimation gratuite',
            'hint' => ''
        ],
        'cta_btn_url' => [
            'label' => '🎬 CTA - URL Bouton',
            'type' => 'text',
            'default' => '/estimation',
            'hint' => ''
        ],
        'cta_phone_text' => [
            'label' => '🎬 CTA - Texte téléphone',
            'type' => 'text',
            'default' => 'Ou appelez-moi directement',
            'hint' => ''
        ],
    ],

    't2-vendre' => [],
    't3-acheter' => [],
    't4-investir' => [],
];

if (!isset($schemas[$templateId])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Unknown template: ' . $templateId]));
}

echo json_encode([
    'success' => true,
    'template' => $templateId,
    'fields' => $schemas[$templateId]
]);
?>