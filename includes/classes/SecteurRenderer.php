<?php
/**
 * SecteurRenderer — Moteur de rendu Secteurs/Quartiers
 * 
 * Fusionne les templates Builder (HTML) avec les données secteurs (DB)
 * Miroir exact de BlogRenderer.php pour les articles
 * 
 * ═══════════════════════════════════════════════════════════════
 * PLACEMENT : /includes/classes/SecteurRenderer.php
 * ═══════════════════════════════════════════════════════════════
 * 
 * TEMPLATES UTILISÉS (créés dans le Builder) :
 *   - Listing  : table `pages`, type = 'template', slug = 'template-secteurs'
 *   - Single   : table `pages`, type = 'template', slug = 'template-secteur-single'
 *                OU table `builder_templates`, slug = 'secteur-single'
 * 
 * VARIABLES LISTING :
 *   {{total_secteurs}}, {{listing_title}}, {{listing_subtitle}}
 *   + Boucle : <!-- SECTEUR_LOOP_START --> ... <!-- SECTEUR_LOOP_END -->
 *   + Alt    : {{#secteurs}} ... {{/secteurs}}
 *   
 *   Dans la boucle :
 *   {{secteur_nom}}, {{secteur_url}}, {{secteur_image}}, {{secteur_description}},
 *   {{secteur_ville}}, {{secteur_type}}, {{secteur_prix}}, {{secteur_transport}},
 *   {{secteur_ambiance}}, {{secteur_slug}}
 * 
 * VARIABLES SINGLE :
 *   {{quartier_name}}, {{sector_name}}, {{nom}}, {{ville}}, {{slug}},
 *   {{type_secteur}}, {{hero_image}}, {{hero_title}}, {{hero_subtitle}},
 *   {{description}}, {{content}}, {{prix_moyen}}, {{prix_m2}},
 *   {{transport}}, {{transports}}, {{atouts}}, {{ambiance}},
 *   {{meta_title}}, {{meta_description}}, {{code_postal}}, {{population}},
 *   {{hero_cta_text}}, {{hero_cta_url}}, {{url}},
 *   {{quartier_images}}, {{biens}}, {{faq}},
 *   {{related_secteurs}} (secteurs voisins auto-générés)
 */

class SecteurRenderer {
    
    private PDO $db;
    private array $settings = [];
    
    // ── Identifiants des templates Builder ──
    // Listing
    private string $listingSlug = 'template-secteurs';
    // Single
    private string $singleSlug = 'template-secteur-single';
    // Fallback : chercher dans builder_templates
    private string $singleTemplateFallback = 'secteur-single';
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->loadSettings();
    }
    
    // ╔══════════════════════════════════════════════════════════════╗
    // ║  PUBLIC : RENDU LISTING                                      ║
    // ╚══════════════════════════════════════════════════════════════╝
    
    /**
     * Rendu complet de la page listing secteurs
     * 
     * @param array $options [type_secteur, ville, search, page, per_page]
     * @return string HTML complet
     */
    public function renderListing(array $options = []): string {
        $typeSecteur = $options['type_secteur'] ?? '';
        $ville       = $options['ville'] ?? '';
        $search      = $options['search'] ?? '';
        $page        = max(1, intval($options['page'] ?? 1));
        $perPage     = intval($options['per_page'] ?? 50);
        
        // 1. Charger le template listing depuis la DB
        $template = $this->loadTemplate('listing');
        if (!$template) {
            return $this->fallbackListingHTML($options);
        }
        
        $html = $template['content'] ?? '';
        $css  = $template['custom_css'] ?? '';
        
        // 2. Charger les secteurs publiés
        $secteurs = $this->getSecteurs($typeSecteur, $ville, $search);
        $total = count($secteurs);
        
        // 3. Remplacer les variables globales
        $html = str_replace('{{total_secteurs}}', $total, $html);
        $html = str_replace('{{listing_title}}', $this->getSetting('secteurs_listing_title', 'Quartiers & Secteurs de Bordeaux'), $html);
        $html = str_replace('{{listing_subtitle}}', $this->getSetting('secteurs_listing_subtitle', 'Découvrez les quartiers bordelais'), $html);
        
        // 4. Remplacer les variables agent
        $html = $this->replaceAgentVars($html);
        
        // 5. Générer la boucle de cards secteurs
        $html = $this->processLoop($html, $secteurs);
        
        // 6. Générer les filtres de types
        $html = $this->replaceTypeFilters($html);
        
        return $html;
    }
    
    // ╔══════════════════════════════════════════════════════════════╗
    // ║  PUBLIC : RENDU SINGLE                                       ║
    // ╚══════════════════════════════════════════════════════════════╝
    
    /**
     * Rendu d'une page secteur individuelle
     * 
     * @param string $slug Slug du secteur
     * @return string|null HTML complet ou null si pas trouvé
     */
    public function renderSingle(string $slug): ?string {
        
        // 1. Charger le secteur depuis la DB
        $secteur = $this->getSecteurBySlug($slug);
        if (!$secteur) return null;
        
        // 2. Charger le template single
        $template = $this->loadTemplate('single');
        
        // 3. Déterminer le HTML source
        if ($template) {
            $html = $template['content'] ?? '';
        } elseif (!empty($secteur['content'])) {
            // Utiliser le contenu propre du secteur (édité dans le Builder)
            $html = $secteur['content'];
        } else {
            return $this->fallbackSingleHTML($secteur);
        }
        
        // 4. Remplacer toutes les variables
        $html = $this->replaceSingleVars($html, $secteur);
        
        // 5. Générer les secteurs liés
        $html = $this->replaceRelatedSecteurs($html, $secteur);
        
        // 6. Injecter les biens disponibles
        $html = $this->replaceBiens($html, $secteur);
        
        // 7. Traiter les FAQ
        $html = $this->replaceFAQ($html, $secteur);
        
        // 8. Agent vars
        $html = $this->replaceAgentVars($html);
        
        return $html;
    }
    
    /**
     * Récupère les métadonnées SEO d'un secteur
     */
    public function getSeoData(string $slug): ?array {
        $secteur = $this->getSecteurBySlug($slug);
        if (!$secteur) return null;
        
        return [
            'title'       => $secteur['meta_title'] ?: $secteur['nom'] . ' - Immobilier ' . ($secteur['ville'] ?: 'Bordeaux'),
            'description' => $secteur['meta_description'] ?: mb_substr(strip_tags($secteur['description'] ?? ''), 0, 160),
            'keywords'    => $secteur['meta_keywords'] ?? '',
            'og_image'    => $secteur['og_image'] ?: ($secteur['hero_image'] ?? ''),
            'canonical'   => $secteur['canonical_url'] ?: '/quartiers/' . $secteur['slug'],
            'robots'      => $secteur['meta_robots'] ?? 'index, follow',
        ];
    }
    
    /**
     * Récupère le CSS custom du template
     */
    public function getCustomCSS(string $mode = 'listing'): string {
        $template = $this->loadTemplate($mode);
        return $template['custom_css'] ?? '';
    }
    
    // ╔══════════════════════════════════════════════════════════════╗
    // ║  PRIVÉ : CHARGEMENT                                          ║
    // ╚══════════════════════════════════════════════════════════════╝
    
    private function loadTemplate(string $mode): ?array {
        $slug = ($mode === 'listing') ? $this->listingSlug : $this->singleSlug;
        
        // 1. Chercher dans `pages` (templates du Builder)
        $stmt = $this->db->prepare(
            "SELECT content, custom_css, custom_js, header_id, footer_id 
             FROM pages WHERE slug = ? AND (type = 'template' OR type = 'secteur') LIMIT 1"
        );
        $stmt->execute([$slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) return $result;
        
        // 2. Fallback : chercher dans `builder_templates`
        if ($mode === 'single') {
            try {
                $stmt2 = $this->db->prepare(
                    "SELECT blocks_data as content, '' as custom_css, '' as custom_js 
                     FROM builder_templates WHERE slug = ? LIMIT 1"
                );
                $stmt2->execute([$this->singleTemplateFallback]);
                $result2 = $stmt2->fetch(PDO::FETCH_ASSOC);
                if ($result2) return $result2;
            } catch (PDOException $e) {
                // Table n'existe pas, OK
            }
        }
        
        return null;
    }
    
    private function getSecteurs(string $type = '', string $ville = '', string $search = ''): array {
        $sql = "SELECT * FROM secteurs WHERE status = 'published'";
        $params = [];
        
        if ($type) {
            $sql .= " AND type_secteur = ?";
            $params[] = $type;
        }
        if ($ville) {
            $sql .= " AND ville = ?";
            $params[] = $ville;
        }
        if ($search) {
            $sql .= " AND (nom LIKE ? OR description LIKE ? OR ville LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY ville ASC, nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function getSecteurBySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM secteurs WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    private function loadSettings(): void {
        try {
            $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            // Table settings peut ne pas exister
        }
    }
    
    private function getSetting(string $key, string $default = ''): string {
        return $this->settings[$key] ?? $default;
    }
    
    // ╔══════════════════════════════════════════════════════════════╗
    // ║  PRIVÉ : REMPLACEMENT DE VARIABLES                           ║
    // ╚══════════════════════════════════════════════════════════════╝
    
    private function replaceSingleVars(string $html, array $s): string {
        $vars = [
            // Noms multiples pour flexibilité dans le template
            'quartier_name'    => $s['nom'] ?? '',
            'sector_name'      => $s['nom'] ?? '',
            'secteur_nom'      => $s['nom'] ?? '',
            'nom'              => $s['nom'] ?? '',
            'ville'            => $s['ville'] ?? 'Bordeaux',
            'slug'             => $s['slug'] ?? '',
            'type_secteur'     => $s['type_secteur'] ?? 'quartier',
            
            // Hero
            'hero_image'       => $s['hero_image'] ?? '',
            'hero_title'       => $s['hero_title'] ?? $s['nom'] ?? '',
            'hero_subtitle'    => $s['hero_subtitle'] ?? '',
            'hero_cta_text'    => $s['hero_cta_text'] ?? 'Voir les biens',
            'hero_cta_url'     => $s['hero_cta_url'] ?? '#biens',
            
            // Contenu
            'description'      => $s['description'] ?? '',
            'content'          => $s['content'] ?? '',
            
            // Données quartier
            'prix_moyen'       => $s['prix_moyen'] ?? '—',
            'prix_m2'          => $s['prix_moyen'] ?? '—',
            'transport'        => $s['transport'] ?? '',
            'transports'       => $s['transport'] ?? '',
            'atouts'           => $s['atouts'] ?? '',
            'ambiance'         => $s['ambiance'] ?? '',
            'population'       => $s['population'] ?? '',
            'code_postal'      => $s['code_postal'] ?? '',
            
            // SEO
            'meta_title'       => $s['meta_title'] ?? ($s['nom'] . ' - Immobilier ' . ($s['ville'] ?? 'Bordeaux')),
            'meta_description' => $s['meta_description'] ?? mb_substr(strip_tags($s['description'] ?? ''), 0, 160),
            
            // URLs
            'url'              => '/quartiers/' . ($s['slug'] ?? ''),
            'canonical_url'    => $s['canonical_url'] ?? ('/quartiers/' . ($s['slug'] ?? '')),
        ];
        
        foreach ($vars as $key => $value) {
            $html = str_replace('{{' . $key . '}}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $html);
        }
        
        // Variables brutes (pas d'échappement pour le HTML content)
        $rawVars = ['content', 'description', 'atouts'];
        foreach ($rawVars as $key) {
            // Remettre le HTML brut pour ces champs
            $escaped = htmlspecialchars($s[$key] ?? '', ENT_QUOTES, 'UTF-8');
            $html = str_replace($escaped, $s[$key] ?? '', $html);
        }
        
        return $html;
    }
    
    /**
     * Traiter la boucle de cards secteurs
     */
    private function processLoop(string $html, array $secteurs): string {
        // Syntaxe 1 : <!-- SECTEUR_LOOP_START --> ... <!-- SECTEUR_LOOP_END -->
        $loopStart = strpos($html, '<!-- SECTEUR_LOOP_START -->');
        $loopEnd   = strpos($html, '<!-- SECTEUR_LOOP_END -->');
        
        if ($loopStart !== false && $loopEnd !== false) {
            $before   = substr($html, 0, $loopStart);
            $template = substr($html, $loopStart + strlen('<!-- SECTEUR_LOOP_START -->'), $loopEnd - $loopStart - strlen('<!-- SECTEUR_LOOP_START -->'));
            $after    = substr($html, $loopEnd + strlen('<!-- SECTEUR_LOOP_END -->'));
            
            $cards = $this->generateCards($template, $secteurs);
            return $before . $cards . $after;
        }
        
        // Syntaxe 2 : {{#secteurs}} ... {{/secteurs}}
        $mStart = strpos($html, '{{#secteurs}}');
        $mEnd   = strpos($html, '{{/secteurs}}');
        
        if ($mStart !== false && $mEnd !== false) {
            $before   = substr($html, 0, $mStart);
            $template = substr($html, $mStart + strlen('{{#secteurs}}'), $mEnd - $mStart - strlen('{{#secteurs}}'));
            $after    = substr($html, $mEnd + strlen('{{/secteurs}}'));
            
            $cards = $this->generateCards($template, $secteurs);
            return $before . $cards . $after;
        }
        
        return $html;
    }
    
    private function generateCards(string $template, array $secteurs): string {
        $cards = '';
        foreach ($secteurs as $s) {
            $card = $template;
            $vars = [
                'secteur_nom'         => $s['nom'] ?? '',
                'secteur_name'        => $s['nom'] ?? '',
                'secteur_url'         => '/quartiers/' . ($s['slug'] ?? ''),
                'secteur_image'       => $s['hero_image'] ?? '/assets/images/placeholder-quartier.jpg',
                'secteur_description' => mb_substr(strip_tags($s['description'] ?? ''), 0, 150),
                'secteur_ville'       => $s['ville'] ?? 'Bordeaux',
                'secteur_type'        => $s['type_secteur'] ?? 'quartier',
                'secteur_prix'        => $s['prix_moyen'] ?? '—',
                'secteur_transport'   => $s['transport'] ?? '',
                'secteur_ambiance'    => $s['ambiance'] ?? '',
                'secteur_slug'        => $s['slug'] ?? '',
                'secteur_atouts'      => $s['atouts'] ?? '',
            ];
            
            foreach ($vars as $key => $value) {
                $card = str_replace('{{' . $key . '}}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $card);
            }
            $cards .= $card;
        }
        return $cards;
    }
    
    /**
     * Remplacer les filtres de types de secteurs
     */
    private function replaceTypeFilters(string $html): string {
        if (strpos($html, '{{type_filters}}') === false) return $html;
        
        $types = $this->db->query(
            "SELECT DISTINCT type_secteur, COUNT(*) as count 
             FROM secteurs WHERE status = 'published' 
             GROUP BY type_secteur ORDER BY count DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
        
        $filtersHtml = '<div class="secteur-filters">';
        $filtersHtml .= '<a href="/quartiers" class="secteur-filter active">Tous</a>';
        foreach ($types as $t) {
            $label = ucfirst($t['type_secteur']);
            $filtersHtml .= '<a href="/quartiers?type=' . urlencode($t['type_secteur']) . '" class="secteur-filter">';
            $filtersHtml .= $label . ' <span class="count">' . $t['count'] . '</span></a>';
        }
        $filtersHtml .= '</div>';
        
        return str_replace('{{type_filters}}', $filtersHtml, $html);
    }
    
    /**
     * Injecter les secteurs liés (voisins)
     */
    private function replaceRelatedSecteurs(string $html, array $secteur): string {
        if (strpos($html, '{{related_secteurs}}') === false 
            && strpos($html, '{{quartiers_voisins}}') === false) {
            return $html;
        }
        
        // Prendre les secteurs de la même ville (sauf celui-ci)
        $stmt = $this->db->prepare(
            "SELECT id, nom, slug, hero_image, description, prix_moyen, type_secteur 
             FROM secteurs 
             WHERE status = 'published' AND id != ? AND ville = ?
             ORDER BY RAND() LIMIT 4"
        );
        $stmt->execute([$secteur['id'], $secteur['ville'] ?? 'Bordeaux']);
        $related = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $relatedHtml = '<div class="related-secteurs-grid">';
        foreach ($related as $r) {
            $img = $r['hero_image'] ?: '/assets/images/placeholder-quartier.jpg';
            $desc = mb_substr(strip_tags($r['description'] ?? ''), 0, 100);
            $relatedHtml .= '
            <a href="/quartiers/' . htmlspecialchars($r['slug']) . '" class="related-secteur-card">
                <img src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($r['nom']) . '" loading="lazy" />
                <div class="related-secteur-info">
                    <h3>' . htmlspecialchars($r['nom']) . '</h3>
                    <p>' . htmlspecialchars($desc) . '</p>
                    ' . ($r['prix_moyen'] ? '<span class="prix">' . htmlspecialchars($r['prix_moyen']) . '/m²</span>' : '') . '
                </div>
            </a>';
        }
        $relatedHtml .= '</div>';
        
        $html = str_replace('{{related_secteurs}}', $relatedHtml, $html);
        $html = str_replace('{{quartiers_voisins}}', $relatedHtml, $html);
        
        return $html;
    }
    
    /**
     * Injecter les biens disponibles dans le quartier
     */
    private function replaceBiens(string $html, array $secteur): string {
        if (strpos($html, '{{biens}}') === false 
            && strpos($html, '{{properties}}') === false) {
            return $html;
        }
        
        try {
            $stmt = $this->db->prepare(
                "SELECT id, title, slug, type, price, surface, rooms, bedrooms, images, city
                 FROM properties 
                 WHERE status = 'available' 
                   AND (city LIKE ? OR address LIKE ?)
                 ORDER BY featured DESC, created_at DESC 
                 LIMIT 6"
            );
            $search = '%' . ($secteur['nom'] ?? '') . '%';
            $stmt->execute([$search, $search]);
            $biens = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $biens = [];
        }
        
        $biensHtml = '';
        if (!empty($biens)) {
            $biensHtml = '<div class="biens-grid">';
            foreach ($biens as $b) {
                $images = json_decode($b['images'] ?? '[]', true);
                $img = !empty($images) ? $images[0] : '/assets/images/placeholder-bien.jpg';
                $price = number_format($b['price'], 0, ',', ' ');
                
                $biensHtml .= '
                <a href="/biens/' . htmlspecialchars($b['slug']) . '" class="bien-card">
                    <img src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($b['title']) . '" loading="lazy" />
                    <div class="bien-card-info">
                        <span class="bien-type">' . htmlspecialchars(ucfirst($b['type'])) . '</span>
                        <h3>' . htmlspecialchars($b['title']) . '</h3>
                        <div class="bien-details">
                            <span>' . $b['surface'] . ' m²</span>
                            <span>' . $b['rooms'] . ' pièces</span>
                        </div>
                        <span class="bien-price">' . $price . ' €</span>
                    </div>
                </a>';
            }
            $biensHtml .= '</div>';
        } else {
            $biensHtml = '<p class="no-biens">Aucun bien disponible dans ce quartier pour le moment.</p>';
        }
        
        $html = str_replace('{{biens}}', $biensHtml, $html);
        $html = str_replace('{{properties}}', $biensHtml, $html);
        
        return $html;
    }
    
    /**
     * Traiter les FAQ
     */
    private function replaceFAQ(string $html, array $secteur): string {
        if (strpos($html, '{{faq}}') === false) return $html;
        
        // Générer des FAQ automatiques si pas de FAQ custom
        $nom = $secteur['nom'] ?? '';
        $ville = $secteur['ville'] ?? 'Bordeaux';
        $prix = $secteur['prix_moyen'] ?? '';
        
        $faqs = [
            [
                'q' => "Quel est le prix moyen de l'immobilier à $nom ?",
                'a' => $prix 
                    ? "Le prix moyen au m² à $nom est d'environ $prix. Ce prix varie selon le type de bien et son état." 
                    : "Les prix varient selon le type de bien. Contactez-nous pour une estimation personnalisée."
            ],
            [
                'q' => "Quels sont les transports en commun à $nom ?",
                'a' => $secteur['transport'] ?: "Le quartier $nom bénéficie d'un bon réseau de transports en commun."
            ],
            [
                'q' => "Pourquoi investir à $nom, $ville ?",
                'a' => $secteur['atouts'] ?: "$nom est un quartier attractif de $ville avec un fort potentiel immobilier."
            ],
        ];
        
        $faqHtml = '<div class="faq-section" itemscope itemtype="https://schema.org/FAQPage">';
        foreach ($faqs as $faq) {
            $faqHtml .= '
            <div class="faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                <button class="faq-question" itemprop="name" onclick="this.parentElement.classList.toggle(\'faq-open\')">' 
                    . htmlspecialchars($faq['q']) . '
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="faq-answer" itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                    <p itemprop="text">' . htmlspecialchars($faq['a']) . '</p>
                </div>
            </div>';
        }
        $faqHtml .= '</div>';
        
        return str_replace('{{faq}}', $faqHtml, $html);
    }
    
    /**
     * Remplacer les variables agent/conseiller
     */
    private function replaceAgentVars(string $html): string {
        $vars = [
            'agent_name'  => $this->getSetting('agent_name', 'Eduardo De Sul'),
            'agent_phone' => $this->getSetting('agent_phone', ''),
            'agent_email' => $this->getSetting('agent_email', ''),
            'agent_photo' => $this->getSetting('agent_photo', ''),
            'agent_bio'   => $this->getSetting('agent_bio', ''),
            'site_name'   => $this->getSetting('site_name', 'Eduardo De Sul Immobilier'),
            'site_url'    => $this->getSetting('site_url', 'https://eduardo-desul-immobilier.fr'),
        ];
        
        foreach ($vars as $key => $value) {
            $html = str_replace('{{' . $key . '}}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $html);
        }
        
        return $html;
    }
    
    // ╔══════════════════════════════════════════════════════════════╗
    // ║  FALLBACK : HTML PAR DÉFAUT                                  ║
    // ╚══════════════════════════════════════════════════════════════╝
    
    private function fallbackListingHTML(array $options): string {
        $secteurs = $this->getSecteurs($options['type_secteur'] ?? '', $options['ville'] ?? '', $options['search'] ?? '');
        
        $html = '<div class="secteurs-listing">
            <div class="container">
                <h1>Quartiers & Secteurs de Bordeaux</h1>
                <p class="listing-subtitle">Découvrez nos guides complets des quartiers bordelais</p>
                <div class="secteurs-grid">';
        
        foreach ($secteurs as $s) {
            $img = $s['hero_image'] ?: '/assets/images/placeholder-quartier.jpg';
            $desc = mb_substr(strip_tags($s['description'] ?? ''), 0, 120);
            $html .= '
                <a href="/quartiers/' . htmlspecialchars($s['slug']) . '" class="secteur-card">
                    <img src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($s['nom']) . '" loading="lazy" />
                    <div class="secteur-card-content">
                        <span class="secteur-badge">' . htmlspecialchars(ucfirst($s['type_secteur'] ?? 'quartier')) . '</span>
                        <h2>' . htmlspecialchars($s['nom']) . '</h2>
                        <p>' . htmlspecialchars($desc) . '</p>
                        ' . ($s['prix_moyen'] ? '<span class="prix">' . htmlspecialchars($s['prix_moyen']) . '/m²</span>' : '') . '
                    </div>
                </a>';
        }
        
        $html .= '</div></div></div>';
        return $html;
    }
    
    private function fallbackSingleHTML(array $s): string {
        return '<div class="quartier-page">
            <section class="qp-hero">
                <div class="qp-hero__bg">
                    <img src="' . htmlspecialchars($s['hero_image'] ?? '') . '" alt="' . htmlspecialchars($s['nom']) . '" />
                </div>
                <div class="qp-hero__inner">
                    <h1>' . htmlspecialchars($s['hero_title'] ?? $s['nom']) . '</h1>
                    <p>' . htmlspecialchars($s['hero_subtitle'] ?? '') . '</p>
                </div>
            </section>
            <section class="qp-content">
                <div class="container">
                    ' . ($s['description'] ?? '') . '
                    ' . ($s['content'] ?? '') . '
                </div>
            </section>
        </div>';
    }
}