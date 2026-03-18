<?php
/**
 * API Handler: templates
 * Called via: /admin/api/router.php?module=templates&action=...
 * Note: Templates are static/hardcoded in the module, not DB-driven.
 * This handler returns the built-in template library.
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    case 'list':
        $category = $input['category'] ?? $_GET['category'] ?? 'all';
        $templates = [
            ['id' => 'hero-achat', 'category' => 'immobilier', 'name' => 'Hero Achat', 'description' => 'Page d\'accueil pour acheter un bien'],
            ['id' => 'hero-vente', 'category' => 'immobilier', 'name' => 'Hero Vente', 'description' => 'Page pour vendre la maison du client'],
            ['id' => 'hero-location', 'category' => 'immobilier', 'name' => 'Hero Location', 'description' => 'Page pour louer un bien'],
            ['id' => 'about-conseil', 'category' => 'presentation', 'name' => 'A Propos - Conseiller', 'description' => 'Page de presentation avec photo et expertise'],
            ['id' => 'formulaire-contact', 'category' => 'contact', 'name' => 'Formulaire de Contact', 'description' => 'Formulaire simple pour les prises de contact'],
            ['id' => 'appel-action', 'category' => 'contact', 'name' => 'Appel a l\'Action', 'description' => 'CTA visuelle avec numero de telephone'],
            ['id' => 'temoignages', 'category' => 'contenu', 'name' => 'Temoignages Clients', 'description' => 'Grille de temoignages de clients satisfaits'],
            ['id' => 'fiche-quartier', 'category' => 'seo', 'name' => 'Fiche Quartier', 'description' => 'Page optimisee SEO pour un quartier specifique'],
        ];
        if ($category !== 'all') {
            $templates = array_values(array_filter($templates, fn($t) => $t['category'] === $category));
        }
        echo json_encode(['success' => true, 'data' => $templates, 'total' => count($templates)]);
        break;

    case 'get':
        $templateId = $input['id'] ?? $_GET['id'] ?? '';
        echo json_encode(['success' => false, 'message' => 'Templates are static. Use the list action to browse.']);
        break;

    case 'categories':
        echo json_encode(['success' => true, 'data' => [
            'immobilier' => 'Immobilier',
            'presentation' => 'Presentation',
            'contact' => 'Contact',
            'contenu' => 'Contenu',
            'seo' => 'SEO & Quartiers'
        ]]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
