<?php
/**
 * BlogRenderer - Moteur de rendu Blog
 * Fusionne les templates Builder (HTML) avec les données articles (DB)
 * 
 * PLACEMENT : /includes/classes/BlogRenderer.php
 * 
 * TEMPLATES UTILISÉS (créés dans le Builder) :
 *   - Listing  : table pages, slug = 'blog'
 *   - Single   : table builder_templates, slug = 'article-blog-classique'
 */

class BlogRenderer {
    
    private PDO $db;
    private array $settings = [];
    
    private string $listingSlug = 'blog';
    private string $singleSlug  = 'article-blog-classique';
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->loadSettings();
    }
    
    // ═══════════════════════════════════════════════════════════
    //  PUBLIC : RENDU LISTING
    // ═══════════════════════════════════════════════════════════
    
    public function renderListing(array $options = []): string {
        $page      = max(1, intval($options['page'] ?? 1));
        $perPage   = intval($options['per_page'] ?? 12);
        $categorie = trim($options['categorie'] ?? '');
        $ville     = trim($options['ville'] ?? '');
        $search    = trim($options['search'] ?? '');
        
        $template = $this->loadListingTemplate();
        if (!$template) {
            error_log("BlogRenderer: Template listing '$this->listingSlug' non trouvé");
            return $this->fallbackListing($options);
        }
        
        $html = $template['html'] ?? '';
        $css  = $template['css'] ?? '';
        
        $filters = ['status' => 'published', 'categorie' => $categorie, 'ville' => $ville, 'search' => $search];
        $totalArticles = $this->countArticles($filters);
        $totalPages    = max(1, ceil($totalArticles / $perPage));
        $page          = min($page, $totalPages);
        $articles      = $this->getArticles(array_merge($filters, ['page' => $page, 'per_page' => $perPage]));
        
        $featured = null;
        if ($page === 1 && !empty($articles)) {
            $featured = array_shift($articles);
        }
        
        $cardTemplate     = $this->extractCardTemplate($html);
        $featuredTemplate = $this->extractFeaturedCardTemplate($html);
        
        $cardsHtml = '';
        if ($featured) {
            $tpl = $featuredTemplate ?: $cardTemplate;
            if ($tpl) $cardsHtml .= $this->replaceArticleVars($tpl, $featured);
        }
        if ($cardTemplate) {
            foreach ($articles as $article) {
                $cardsHtml .= $this->replaceArticleVars($cardTemplate, $article);
            }
        }
        
        if (empty($cardsHtml)) {
            $cardsHtml = '<div style="text-align:center;padding:60px 20px;grid-column:1/-1;"><p style="font-size:1.2rem;color:#64748b;">Aucun article trouvé</p></div>';
        }
        
        $html = $this->injectCards($html, $cardsHtml);
        
        $html = $this->replaceVars($html, [
            'blog_title'     => $this->s('blog_title', 'Blog Immobilier Bordeaux'),
            'blog_subtitle'  => $this->s('blog_subtitle', 'Conseils, tendances et actualités du marché immobilier bordelais'),
            'total_articles' => $totalArticles,
            'author_name'    => $this->s('author_name', 'Eduardo De Sul'),
            'author_photo'   => $this->s('author_photo', '/assets/images/eduardo.jpg'),
            'cta_title'      => $this->s('blog_cta_title', 'Besoin d\'un conseil personnalisé ?'),
            'cta_text'       => $this->s('blog_cta_text', 'Prendre rendez-vous'),
            'cta_link'       => $this->s('blog_cta_link', '/contact'),
            'current_year'   => date('Y'),
            'site_name'      => $this->s('site_name', defined('SITE_NAME') ? SITE_NAME : 'Eduardo De Sul Immobilier'),

        ]);
        
        $html = str_replace('{{pagination}}', $this->buildPagination($page, $totalPages, $categorie), $html);
        
        return $html;
    }
    
    // ═══════════════════════════════════════════════════════════
    //  PUBLIC : RENDU SINGLE ARTICLE
    // ═══════════════════════════════════════════════════════════
    
    public function renderSingle(string $slug): ?array {
        $article = $this->getArticleBySlug($slug);
        if (!$article) return null;
        
        $this->incrementViews($article['id']);
        
        $template = $this->loadSingleTemplate();
        $html = $template ? ($template['html'] ?? '') : $this->defaultSingleHtml();
        $css  = $template ? ($template['css'] ?? '') : '';
        
        $contenu = $article['contenu'] ?? '';
        $toc     = $this->generateTOC($contenu);
        $contenu = $this->addHeadingIds($contenu);
        
        $tags        = $this->buildTags($article);
        $related     = $this->getRelatedArticles($article, 3);
        $relatedHtml = $this->buildRelatedCards($related);
        
        $createdTs = strtotime($article['created_at'] ?? 'now');
        $updatedTs = !empty($article['updated_at']) ? strtotime($article['updated_at']) : $createdTs;
        
        $readTime = intval($article['reading_time'] ?? 0);
        if ($readTime < 1) $readTime = max(1, ceil(str_word_count(strip_tags($contenu)) / 200));
        
        $image = $article['featured_image'] ?? '';
        if (empty($image)) $image = '/assets/images/blog/default-article.jpg';
        
        $catRaw  = $article['raison_vente'] ?? ($article['type_article'] ?? '');
        $catLabel = ucfirst(str_replace(['-','_'], ' ', $catRaw));
        $catSlug  = $this->slugify($catRaw);
        
$siteUrl    = $this->s('site_url', defined('SITE_URL') ? SITE_URL : 'https://eduardo-desul-immobilier.fr');
        $articleUrl = $siteUrl . '/blog/' . $article['slug'];
        
        // ══════════════════════════════════════════════════════
        // VARIABLES ALIGNÉES SUR LE TEMPLATE BUILDER
        // ══════════════════════════════════════════════════════
        $html = $this->replaceVars($html, [
            // Core
            'title'           => $article['titre'] ?? '',
            'article_title'   => $article['titre'] ?? '',
            'content'         => $contenu,
            'article_content' => $contenu,
            'excerpt'         => $article['extrait'] ?? '',
            'slug'            => $article['slug'],
            
            // URLs
            'url'             => $articleUrl,
            'article_url'     => '/blog/' . $article['slug'],
            
            // Image
            'featured_image'         => $image,
            'featured_image_alt'     => $article['titre'] ?? '',
            'featured_image_caption' => $article['featured_image_caption'] ?? '',
            
            // Auteur
            'author'        => $this->s('author_name', 'Eduardo De Sul'),
            'author_name'   => $this->s('author_name', 'Eduardo De Sul'),
            'author_bio'    => $this->s('author_bio', 'Conseiller immobilier indépendant chez eXp France, spécialiste du marché bordelais depuis plus de 10 ans.'),
            'author_photo'  => $this->s('author_photo', '/assets/images/eduardo.jpg'),
            'author_title'  => $this->s('author_title', 'Conseiller immobilier indépendant'),
            
            // Catégorie (template utilise "category" anglais)
            'category'       => $catLabel,
            'categorie'      => $catLabel,
            'category_slug'  => $catSlug,
            
            // Dates (tous les formats)
            'date'           => date('d/m/Y', $createdTs),
            'date_long'      => $this->dateFR($createdTs),
            'date_display'   => $this->dateFR($createdTs),
            'date_short'     => $this->dateShort($createdTs),
            'date_iso'       => date('Y-m-d', $createdTs),
            'date_updated'   => $this->dateFR($updatedTs),
            
            // Stats
            'reading_time' => $readTime,
            'word_count'   => number_format(intval($article['word_count'] ?? 0)),
            'views'        => number_format(intval($article['views'] ?? 0)),
            
            // SEO
            'meta_title'       => $article['meta_title'] ?: ($article['titre'] ?? ''),
            'meta_description' => $article['meta_description'] ?: ($article['extrait'] ?? ''),
            'keyword'          => $article['main_keyword'] ?? ($article['focus_keyword'] ?? ''),
            
            // Catégorisation
            'ville'        => $article['ville'] ?? '',
            'type_article' => $article['type_article'] ?? '',
            'raison_vente' => $article['raison_vente'] ?? '',
            'persona'      => $article['persona'] ?? '',
            
            // Blocs générés (HTML)
            'toc'              => $toc,
            'tags'             => $tags,
            'related_articles' => $relatedHtml,
            'related_posts'    => $relatedHtml,
            
            // Globaux
            'site_name'    => $this->s('site_name', defined('SITE_NAME') ? SITE_NAME : 'Eduardo De Sul Immobilier'),

            'site_url'     => $siteUrl,
            'cta_title'    => $this->s('blog_cta_title', 'Besoin d\'un conseil ?'),
            'cta_text'     => $this->s('blog_cta_text', 'Me contacter'),
            'cta_link'     => $this->s('blog_cta_link', '/contact'),
            'current_year' => date('Y'),
        ]);
        
        if (!empty($css)) $html = '<style>' . $css . '</style>' . "\n" . $html;
        
        return [
            'html' => $html,
            'css'  => $css,
            'meta' => [
                'title'       => ($article['meta_title'] ?: $article['titre']) . ' | ' . $this->s('site_name', ''),
                'description' => $article['meta_description'] ?: mb_substr(strip_tags($article['extrait'] ?? $contenu), 0, 155),
                'og_image'    => $image,
                'canonical'   => '/blog/' . $article['slug'],
                'keywords'    => $article['main_keyword'] ?? '',
                'article'     => $article,
            ],
        ];
    }
    
    // ═══════════════════════════════════════════════════════════
    //  CHARGEMENT TEMPLATES BUILDER
    // ═══════════════════════════════════════════════════════════
    
    private function loadListingTemplate(): ?array {
        try {
            foreach (['pages', 'builder_templates'] as $table) {
                $cols = $this->getColumns($table);
                if (!$cols) continue;
                $htmlCol = in_array('html_content', $cols) ? 'html_content' : 'content';
                $cssCol  = in_array('custom_css', $cols) ? 'custom_css' : (in_array('css_content', $cols) ? 'css_content' : null);
                $statusCheck = in_array('status', $cols) ? "AND status IN ('published','active')" : '';
                $sql = "SELECT $htmlCol as html" . ($cssCol ? ", $cssCol as css" : ", '' as css") . " FROM $table WHERE slug = ? $statusCheck LIMIT 1";
                $stmt = $this->db->prepare($sql); $stmt->execute([$this->listingSlug]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['html'])) return $row;
            }
        } catch (PDOException $e) { error_log("BlogRenderer::loadListingTemplate: " . $e->getMessage()); }
        return null;
    }
    
    private function loadSingleTemplate(): ?array {
        try {
            foreach (['builder_templates', 'pages'] as $table) {
                $cols = $this->getColumns($table);
                if (!$cols) continue;
                $htmlCol = in_array('html_content', $cols) ? 'html_content' : 'content';
                $cssCol  = in_array('custom_css', $cols) ? 'custom_css' : (in_array('css_content', $cols) ? 'css_content' : null);
                $statusCheck = in_array('status', $cols) ? "AND status IN ('published','active')" : '';
                $sql = "SELECT $htmlCol as html" . ($cssCol ? ", $cssCol as css" : ", '' as css") . " FROM $table WHERE slug = ? $statusCheck LIMIT 1";
                $stmt = $this->db->prepare($sql); $stmt->execute([$this->singleSlug]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['html'])) return $row;
            }
        } catch (PDOException $e) { error_log("BlogRenderer::loadSingleTemplate: " . $e->getMessage()); }
        return null;
    }
    
    private function getColumns(string $table): ?array {
        try { $stmt = $this->db->query("SHOW COLUMNS FROM `$table`"); return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        } catch (PDOException $e) { return null; }
    }
    
    // ═══════════════════════════════════════════════════════════
    //  EXTRACTION CARDS (LISTING)
    // ═══════════════════════════════════════════════════════════
    
    private function extractCardTemplate(string $html): ?string {
        if (preg_match('/<!-- ?ARTICLE_CARD_START ?-->(.*?)<!-- ?ARTICLE_CARD_END ?-->/s', $html, $m)) return trim($m[1]);
        if (preg_match('/\{\{#articles\}\}(.*?)\{\{\/articles\}\}/s', $html, $m)) return trim($m[1]);
        if (preg_match('/<(?:article|div)[^>]*class="[^"]*(?:bl-card|blog-card|article-card)[^"]*"[^>]*>.*?<\/(?:article|div)>/s', $html, $m)) {
            if (strpos($m[0], '{{article_') !== false) return $m[0];
        }
        return null;
    }
    
    private function extractFeaturedCardTemplate(string $html): ?string {
        if (preg_match('/<!-- ?FEATURED_START ?-->(.*?)<!-- ?FEATURED_END ?-->/s', $html, $m)) return trim($m[1]);
        if (preg_match('/\{\{#featured\}\}(.*?)\{\{\/featured\}\}/s', $html, $m)) return trim($m[1]);
        return null;
    }
    
    private function injectCards(string $html, string $cards): string {
        $html = preg_replace('/<!-- ?ARTICLE_LOOP_START ?-->.*?<!-- ?ARTICLE_LOOP_END ?-->/s', $cards, $html, 1, $c);
        if ($c > 0) return $html;
        $html = preg_replace('/\{\{#articles\}\}.*?\{\{\/articles\}\}/s', $cards, $html, 1, $c);
        if ($c > 0) return $html;
        if (strpos($html, '{{articles_loop}}') !== false) return str_replace('{{articles_loop}}', $cards, $html);
        if (preg_match('/(<(?:div|section)[^>]*class="[^"]*(?:bl-grid|blog-grid|articles-grid|cards-grid|bl-cards)[^"]*"[^>]*>)(.*?)(<\/(?:div|section)>)/s', $html, $m))
            return str_replace($m[0], $m[1] . "\n" . $cards . "\n" . $m[3], $html);
        return $html;
    }
    
    // ═══════════════════════════════════════════════════════════
    //  REMPLACEMENT DE VARIABLES
    // ═══════════════════════════════════════════════════════════
    
    private function replaceVars(string $html, array $vars): string {
        $noEscape = ['content','article_content','toc','tags','related_articles','related_posts','pagination','articles_loop','contenu'];
        foreach ($vars as $key => $val) {
            $safe = in_array($key, $noEscape) ? (string)$val : htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
            $html = str_replace('{{' . $key . '}}', $safe, $html);
        }
        return $html;
    }
    
    private function replaceArticleVars(string $html, array $a): string {
        $dateTs = strtotime($a['created_at'] ?? 'now');
        $readTime = intval($a['reading_time'] ?? 0);
        if ($readTime < 1) $readTime = max(1, ceil(str_word_count(strip_tags($a['contenu'] ?? '')) / 200));
        $image = $a['featured_image'] ?? '';
        if (empty($image)) $image = '/assets/images/blog/default-article.jpg';
        $cat = $a['raison_vente'] ?? ($a['type_article'] ?? '');
        
        return $this->replaceVars($html, [
            'article_title'          => $a['titre'] ?? '',
            'article_titre'          => $a['titre'] ?? '',
            'article_url'            => '/blog/' . ($a['slug'] ?? ''),
            'article_link'           => '/blog/' . ($a['slug'] ?? ''),
            'article_slug'           => $a['slug'] ?? '',
            'article_image'          => $image,
            'article_featured_image' => $image,
            'article_excerpt'        => $a['extrait'] ?? '',
            'article_extrait'        => $a['extrait'] ?? '',
            'article_content'        => $a['contenu'] ?? '',
            'article_categorie'      => ucfirst(str_replace(['-','_'], ' ', $cat)),
            'article_category'       => ucfirst(str_replace(['-','_'], ' ', $cat)),
            'article_ville'          => $a['ville'] ?? '',
            'article_type'           => $a['type_article'] ?? '',
            'article_date'           => date('d/m/Y', $dateTs),
            'article_date_long'      => $this->dateFR($dateTs),
            'article_date_display'   => $this->dateFR($dateTs),
            'article_date_iso'       => date('Y-m-d', $dateTs),
            'article_date_short'     => $this->dateShort($dateTs),
            'article_reading_time'   => $readTime,
            'article_word_count'     => number_format(intval($a['word_count'] ?? 0)),
            'article_views'          => number_format(intval($a['views'] ?? 0)),
            'article_id'             => $a['id'] ?? '',
            'article_keyword'        => $a['main_keyword'] ?? ($a['focus_keyword'] ?? ''),
            'author_name'            => $this->s('author_name', 'Eduardo De Sul'),
            'author'                 => $this->s('author_name', 'Eduardo De Sul'),
        ]);
    }
    
    // ═══════════════════════════════════════════════════════════
    //  QUERIES DB
    // ═══════════════════════════════════════════════════════════
    
    private function getArticles(array $o = []): array {
        $w = ['status = ?']; $p = [$o['status'] ?? 'published'];
        if (!empty($o['categorie'])) { $w[] = "(raison_vente = ? OR type_article = ?)"; $p[] = $o['categorie']; $p[] = $o['categorie']; }
        if (!empty($o['ville']))     { $w[] = "ville = ?"; $p[] = $o['ville']; }
        if (!empty($o['search']))    { $w[] = "(titre LIKE ? OR extrait LIKE ? OR contenu LIKE ?)"; $s = '%'.$o['search'].'%'; array_push($p,$s,$s,$s); }
        $pg = max(1,intval($o['page']??1)); $pp = intval($o['per_page']??12); $off = ($pg-1)*$pp;
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE ".implode(' AND ',$w)." ORDER BY created_at DESC LIMIT $pp OFFSET $off");
        $stmt->execute($p); return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function countArticles(array $o = []): int {
        $w = ['status = ?']; $p = [$o['status'] ?? 'published'];
        if (!empty($o['categorie'])) { $w[] = "(raison_vente = ? OR type_article = ?)"; $p[] = $o['categorie']; $p[] = $o['categorie']; }
        if (!empty($o['ville']))     { $w[] = "ville = ?"; $p[] = $o['ville']; }
        if (!empty($o['search']))    { $w[] = "(titre LIKE ? OR extrait LIKE ? OR contenu LIKE ?)"; $s = '%'.$o['search'].'%'; array_push($p,$s,$s,$s); }
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM articles WHERE ".implode(' AND ',$w));
        $stmt->execute($p); return intval($stmt->fetchColumn());
    }
    
    private function getArticleBySlug(string $slug): ?array {
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]); return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    private function getRelatedArticles(array $a, int $limit = 3): array {
        $w = ["status='published'","id!=?"]; $p = [$a['id']]; $or = [];
        if (!empty($a['ville']))        { $or[] = "ville=?"; $p[] = $a['ville']; }
        if (!empty($a['raison_vente'])) { $or[] = "raison_vente=?"; $p[] = $a['raison_vente']; }
        if (!empty($a['type_article'])) { $or[] = "type_article=?"; $p[] = $a['type_article']; }
        if ($or) $w[] = '('.implode(' OR ',$or).')';
        $stmt = $this->db->prepare("SELECT * FROM articles WHERE ".implode(' AND ',$w)." ORDER BY created_at DESC LIMIT $limit");
        $stmt->execute($p); $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($res) < $limit) {
            $exc = array_merge([$a['id']], array_column($res,'id'));
            $ph = implode(',',array_fill(0,count($exc),'?'));
            $stmt = $this->db->prepare("SELECT * FROM articles WHERE status='published' AND id NOT IN ($ph) ORDER BY created_at DESC LIMIT ".($limit-count($res)));
            $stmt->execute($exc); $res = array_merge($res, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        return $res;
    }
    
    private function incrementViews(int $id): void {
        try { $this->db->prepare("UPDATE articles SET views=COALESCE(views,0)+1 WHERE id=?")->execute([$id]); } catch(\Exception $e) {}
    }
    
    // ═══════════════════════════════════════════════════════════
    //  TOC, TAGS, PAGINATION, RELATED
    // ═══════════════════════════════════════════════════════════
    
    private function generateTOC(string $content): string {
        if (!preg_match_all('/<h([23])[^>]*>(.*?)<\/h[23]>/i', $content, $matches, PREG_SET_ORDER) || count($matches) < 2) return '';
        $toc = '';
        foreach ($matches as $i => $m) {
            $cls = intval($m[1]) === 3 ? ' class="toc-h3"' : '';
            $toc .= '<li><a href="#section-'.($i+1).'"'.$cls.'>'.htmlspecialchars(strip_tags($m[2])).'</a></li>';
        }
        return $toc;
    }
    
    private function addHeadingIds(string $content): string {
        $c = 0;
        return preg_replace_callback('/<(h[23])([^>]*)>(.*?)<\/(h[23])>/i', function($m) use (&$c) {
            $c++; if (strpos($m[2],'id=') !== false) return $m[0];
            return '<'.$m[1].' id="section-'.$c.'"'.$m[2].'>'.$m[3].'</'.$m[4].'>';
        }, $content);
    }
    
    private function buildTags(array $a): string {
        $tags = array_filter([$a['ville'] ?? '',
            !empty($a['raison_vente']) ? ucfirst(str_replace(['-','_'],' ',$a['raison_vente'])) : '',
            !empty($a['type_article']) ? ucfirst(str_replace(['-','_'],' ',$a['type_article'])) : '',
            $a['main_keyword'] ?? '']);
        if (!$tags) return '';
        $h = '';
        foreach ($tags as $t) { $h .= '<a href="/blog?cat='.htmlspecialchars($this->slugify($t)).'" class="blog-tag">'.htmlspecialchars($t).'</a> '; }
        return $h;
    }
    
    private function buildRelatedCards(array $articles): string {
        if (!$articles) return '';
        $h = '';
        foreach ($articles as $a) {
            $img = $a['featured_image'] ?? '/assets/images/blog/default-article.jpg';
            $dt = strtotime($a['created_at'] ?? 'now');
            $cat = $a['raison_vente'] ?? ($a['type_article'] ?? '');
            $h .= '<a href="/blog/'.htmlspecialchars($a['slug']).'" class="related-card">'
                 . '<div class="related-card__image"><img src="'.htmlspecialchars($img).'" alt="'.htmlspecialchars($a['titre']).'" loading="lazy"></div>'
                 . '<div class="related-card__body">'
                 . '<span class="related-card__category">'.htmlspecialchars(ucfirst(str_replace(['-','_'],' ',$cat))).'</span>'
                 . '<span class="related-card__date">'.$this->dateShort($dt).'</span>'
                 . '<h3 class="related-card__title">'.htmlspecialchars($a['titre']).'</h3>'
                 . '</div></a>';
        }
        return $h;
    }
    
    private function buildPagination(int $cur, int $total, string $cat = ''): string {
        if ($total <= 1) return '';
        $base = '/blog'; $cp = $cat ? '&cat='.urlencode($cat) : '';
        $h = '<nav class="blog-pagination">';
        if ($cur > 1) $h .= '<a href="'.$base.'?page='.($cur-1).$cp.'" class="page-btn prev">← Précédent</a>';
        $s = max(1,$cur-2); $e = min($total,$cur+2);
        if ($s > 1) { $h .= '<a href="'.$base.'?page=1'.$cp.'" class="page-num">1</a>'; if ($s>2) $h .= '<span class="page-dots">…</span>'; }
        for ($i=$s; $i<=$e; $i++) $h .= '<a href="'.$base.'?page='.$i.$cp.'" class="page-num'.($i===$cur?' active':'').'">'.$i.'</a>';
        if ($e < $total) { if ($e<$total-1) $h .= '<span class="page-dots">…</span>'; $h .= '<a href="'.$base.'?page='.$total.$cp.'" class="page-num">'.$total.'</a>'; }
        if ($cur < $total) $h .= '<a href="'.$base.'?page='.($cur+1).$cp.'" class="page-btn next">Suivant →</a>';
        return $h . '</nav>';
    }
    
    // ═══════════════════════════════════════════════════════════
    //  UTILITAIRES
    // ═══════════════════════════════════════════════════════════
    
    private function loadSettings(): void {
        try { $st = $this->db->query("SELECT setting_key, setting_value FROM settings");
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) $this->settings[$r['setting_key']] = $r['setting_value'];
        } catch (\Exception $e) {}
    }
    
    private function s(string $k, string $d = ''): string { return $this->settings[$k] ?? $d; }
    
    private function dateFR(int $ts): string {
        $m = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
        return intval(date('d',$ts)).' '.$m[intval(date('m',$ts))-1].' '.date('Y',$ts);
    }
    private function dateShort(int $ts): string {
        $m = ['janv.','févr.','mars','avr.','mai','juin','juil.','août','sept.','oct.','nov.','déc.'];
        return intval(date('d',$ts)).' '.$m[intval(date('m',$ts))-1].' '.date('Y',$ts);
    }
    private function slugify(string $t): string {
        $t = mb_strtolower($t,'UTF-8');
        $t = preg_replace('/[àáâãäå]/u','a',$t); $t = preg_replace('/[èéêë]/u','e',$t);
        $t = preg_replace('/[ìíîï]/u','i',$t); $t = preg_replace('/[òóôõö]/u','o',$t);
        $t = preg_replace('/[ùúûü]/u','u',$t); $t = preg_replace('/[ç]/u','c',$t);
        return trim(preg_replace('/[^a-z0-9]+/','-',$t),'-');
    }
    
    private function fallbackListing(array $o): string {
        $arts = $this->getArticles(array_merge($o,['status'=>'published','page'=>$o['page']??1,'per_page'=>12]));
        $h = '<div style="max-width:1200px;margin:40px auto;padding:0 20px;"><h1>Blog Immobilier Bordeaux</h1>'
           . '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:24px;margin-top:32px;">';
        foreach ($arts as $a) {
            $img = $a['featured_image'] ?? '/assets/images/blog/default-article.jpg';
            $h .= '<article style="border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;">'
                 . '<img src="'.htmlspecialchars($img).'" style="width:100%;height:200px;object-fit:cover;">'
                 . '<div style="padding:20px;"><h2 style="font-size:1.15rem;"><a href="/blog/'.htmlspecialchars($a['slug']).'">'
                 . htmlspecialchars($a['titre']).'</a></h2><p style="color:#64748b;font-size:0.9rem;">'
                 . htmlspecialchars($a['extrait']??'').'</p></div></article>';
        }
        return $h.'</div></div>';
    }
    
    private function defaultSingleHtml(): string {
        return '<article class="blog-article"><header><span>{{category}}</span><h1>{{title}}</h1>'
             . '<div>{{date_display}} · {{reading_time}} min</div></header>'
             . '<figure><img src="{{featured_image}}" alt="{{featured_image_alt}}"><figcaption>{{featured_image_caption}}</figcaption></figure>'
             . '<div class="blog-content">{{content}}</div>'
             . '<div>TAGS : {{tags}}</div><section><h3>Articles liés</h3>{{related_posts}}</section></article>';
    }
}