<?php
/**
 * API Handler: seo-semantic
 * Called via: /admin/api/router.php?module=seo-semantic&action=...
 * Semantic SEO analysis for pages, articles, blog, GMB content
 *
 * Actions:
 *   analyze        (POST) - Run semantic analysis on a single content item
 *   details        (GET)  - Retrieve stored semantic analysis details
 *   bulk_analyze   (POST) - List items with basic SEO issue summary
 *   keyword_density (POST) - Calculate keyword density for a content item
 */

// Merge input sources: JSON body > POST > GET (for read actions)
$jsonInput = json_decode(file_get_contents('php://input'), true) ?? [];
$input = array_merge($_GET, $_POST, $jsonInput);
$action = CURRENT_ACTION;

// Shared table mapping — includes GMB content types sent by the frontend
$tableMap = [
    'page'         => 'pages',
    'article'      => 'articles',
    'blog'         => 'blog_articles',
    'gmb_post'     => 'gmb_posts',
    'gmb_avis'     => 'gmb_avis',
    'gmb_question' => 'gmb_questions',
];

switch ($action) {

    // ─────────────────────────────────────────────
    // ANALYZE — POST  (mutates: stores score)
    // JS sends: { content_type, id }
    // JS expects: { success, score, analysis: { score_semantic, score_label } }
    //         or: { success: false, error: "..." }
    // ─────────────────────────────────────────────
    case 'analyze':
        try {
            $id   = (int)($input['id'] ?? 0);
            // JS sends "content_type"; fall back to "type" for backward compat
            $type = $input['content_type'] ?? $input['type'] ?? 'page';
            $table = $tableMap[$type] ?? null;

            if (!$table) {
                echo json_encode(['success' => false, 'error' => "Type '$type' non supporte"]);
                break;
            }

            // Verify table exists
            try {
                $pdo->query("SELECT 1 FROM `$table` LIMIT 0");
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => "Table '$table' introuvable"]);
                break;
            }

            $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$item) {
                echo json_encode(['success' => false, 'error' => 'Contenu non trouve']);
                break;
            }

            // Extract text fields (column names differ across tables)
            $title   = $item['title'] ?? $item['titre'] ?? $item['auteur_nom'] ?? '';
            $content = $item['content'] ?? $item['body'] ?? $item['contenu']
                       ?? $item['commentaire'] ?? $item['question_texte'] ?? '';
            $keyword = $item['main_keyword'] ?? $item['seo_keywords'] ?? '';

            // --- Basic semantic analysis ---
            $plainText = strip_tags($content);
            $wordCount = str_word_count($plainText);
            $issues = [];

            if ($wordCount < 300) {
                $issues[] = ['type' => 'warning', 'message' => 'Contenu trop court (moins de 300 mots)', 'field' => 'content'];
            }
            if (empty($keyword)) {
                $issues[] = ['type' => 'error', 'message' => 'Mot-cle principal manquant', 'field' => 'keyword'];
            }
            if (!empty($keyword) && stripos($title, $keyword) === false) {
                $issues[] = ['type' => 'warning', 'message' => 'Mot-cle absent du titre', 'field' => 'title'];
            }

            $seoTitle = $item['seo_title'] ?? '';
            $seoDesc  = $item['seo_description'] ?? '';
            if (empty($seoTitle)) {
                $issues[] = ['type' => 'error', 'message' => 'Titre SEO manquant', 'field' => 'seo_title'];
            } elseif (strlen($seoTitle) > 60) {
                $issues[] = ['type' => 'warning', 'message' => 'Titre SEO trop long (>60 car.)', 'field' => 'seo_title'];
            }
            if (empty($seoDesc)) {
                $issues[] = ['type' => 'error', 'message' => 'Meta description manquante', 'field' => 'seo_description'];
            } elseif (strlen($seoDesc) > 160) {
                $issues[] = ['type' => 'warning', 'message' => 'Meta description trop longue (>160 car.)', 'field' => 'seo_description'];
            }

            // Heading structure
            preg_match_all('/<h([1-6])[^>]*>/i', $content, $headings);
            $hasH2 = in_array('2', $headings[1] ?? []);
            if (!$hasH2) {
                $issues[] = ['type' => 'info', 'message' => 'Aucun sous-titre H2 trouve', 'field' => 'structure'];
            }

            $errorCount   = count(array_filter($issues, fn($i) => $i['type'] === 'error'));
            $warningCount = count(array_filter($issues, fn($i) => $i['type'] === 'warning'));
            $score = max(0, 100 - ($errorCount * 20) - ($warningCount * 10));

            // Determine score label
            $scoreLabel = match (true) {
                $score >= 80 => 'Excellent',
                $score >= 60 => 'Bon',
                $score >= 40 => 'A ameliorer',
                $score > 0   => 'Faible',
                default      => 'Non evalue',
            };

            // Persist score and analysis data
            $analysisData = [
                'score_semantic' => $score,
                'score_label'    => $scoreLabel,
                'word_count'     => $wordCount,
                'heading_count'  => count($headings[1] ?? []),
                'issues'         => $issues,
                'keyword'        => $keyword,
            ];

            // Check which columns exist before updating
            $cols = [];
            $colsResult = $pdo->query("SHOW COLUMNS FROM `$table`");
            while ($c = $colsResult->fetch(PDO::FETCH_ASSOC)) {
                $cols[] = $c['Field'];
            }

            $updates = [];
            $params  = [];
            if (in_array('semantic_score', $cols)) {
                $updates[] = 'semantic_score = ?';
                $params[]  = $score;
            }
            if (in_array('semantic_data', $cols)) {
                $updates[] = 'semantic_data = ?';
                $params[]  = json_encode($analysisData, JSON_UNESCAPED_UNICODE);
            }
            if (in_array('semantic_analyzed_at', $cols)) {
                $updates[] = 'semantic_analyzed_at = NOW()';
            }
            if (in_array('seo_score', $cols)) {
                $updates[] = 'seo_score = ?';
                $params[]  = $score;
            }

            if (!empty($updates)) {
                $params[] = $id;
                $pdo->prepare("UPDATE `$table` SET " . implode(', ', $updates) . " WHERE id = ?")
                    ->execute($params);
            }

            // Response shape matching JS expectations
            echo json_encode([
                'success'  => true,
                'score'    => $score,
                'analysis' => [
                    'score_semantic' => $score,
                    'score_label'    => $scoreLabel,
                    'word_count'     => $wordCount,
                    'heading_count'  => count($headings[1] ?? []),
                    'issues'         => $issues,
                    'keyword'        => $keyword,
                ],
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─────────────────────────────────────────────
    // DETAILS — GET  (read-only)
    // JS sends: ?content_type=...&id=...
    // JS expects: { success, title, analysis: { score_semantic, score_label,
    //              topic_detected, lexical_field, semantic_suggestions,
    //              quick_wins, overall_assessment } }
    // ─────────────────────────────────────────────
    case 'details':
        try {
            $id   = (int)($input['id'] ?? 0);
            $type = $input['content_type'] ?? $input['type'] ?? 'page';
            $table = $tableMap[$type] ?? null;

            if (!$table) {
                echo json_encode(['success' => false, 'error' => "Type '$type' non supporte"]);
                break;
            }

            try {
                $pdo->query("SELECT 1 FROM `$table` LIMIT 0");
            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'error' => "Table '$table' introuvable"]);
                break;
            }

            $stmt = $pdo->prepare("SELECT * FROM `$table` WHERE id = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$item) {
                echo json_encode(['success' => false, 'error' => 'Contenu non trouve']);
                break;
            }

            $title = $item['title'] ?? $item['titre'] ?? $item['auteur_nom'] ?? 'Sans titre';

            // Try to load stored semantic_data
            $analysis = [];
            if (!empty($item['semantic_data'])) {
                $stored = json_decode($item['semantic_data'], true);
                if (is_array($stored)) {
                    $analysis = $stored;
                }
            }

            // If no stored data, build basic analysis from current content
            if (empty($analysis)) {
                $content = $item['content'] ?? $item['body'] ?? $item['contenu']
                           ?? $item['commentaire'] ?? $item['question_texte'] ?? '';
                $keyword = $item['main_keyword'] ?? $item['seo_keywords'] ?? '';
                $plainText = strip_tags($content);
                $wordCount = str_word_count($plainText);

                $issues = [];
                if ($wordCount < 300) {
                    $issues[] = ['type' => 'warning', 'message' => 'Contenu trop court'];
                }
                if (empty($keyword)) {
                    $issues[] = ['type' => 'error', 'message' => 'Mot-cle principal manquant'];
                }

                $seoTitle = $item['seo_title'] ?? '';
                $seoDesc  = $item['seo_description'] ?? '';
                if (empty($seoTitle)) $issues[] = ['type' => 'error', 'message' => 'Titre SEO manquant'];
                if (empty($seoDesc))  $issues[] = ['type' => 'error', 'message' => 'Meta description manquante'];

                $errorCount   = count(array_filter($issues, fn($i) => $i['type'] === 'error'));
                $warningCount = count(array_filter($issues, fn($i) => $i['type'] === 'warning'));
                $score = max(0, 100 - ($errorCount * 20) - ($warningCount * 10));

                $analysis = [
                    'score_semantic' => $score,
                    'score_label' => match (true) {
                        $score >= 80 => 'Excellent',
                        $score >= 60 => 'Bon',
                        $score >= 40 => 'A ameliorer',
                        $score > 0   => 'Faible',
                        default      => 'Non evalue',
                    },
                    'word_count'    => $wordCount,
                    'keyword'       => $keyword,
                    'issues'        => $issues,
                ];
            }

            // Ensure all keys the JS modal reads are present (even if empty)
            $analysis += [
                'score_semantic'       => (int)($item['semantic_score'] ?? 0),
                'score_label'          => 'Non evalue',
                'topic_detected'       => null,
                'lexical_field'        => ['covered' => [], 'missing_critical' => []],
                'semantic_suggestions' => ['words_to_add' => [], 'expressions_to_add' => [], 'questions_to_answer' => []],
                'quick_wins'           => [],
                'overall_assessment'   => null,
                'word_count'           => 0,
                'keyword'              => '',
                'issues'               => [],
            ];

            echo json_encode([
                'success'  => true,
                'title'    => $title,
                'analysis' => $analysis,
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─────────────────────────────────────────────
    // BULK ANALYZE — POST
    // ─────────────────────────────────────────────
    case 'bulk_analyze':
        try {
            $type  = $input['content_type'] ?? $input['type'] ?? 'page';
            $table = $tableMap[$type] ?? null;

            if (!$table) {
                echo json_encode(['success' => false, 'error' => "Type '$type' non supporte"]);
                break;
            }

            // Check which columns exist
            $cols = [];
            $colsResult = $pdo->query("SHOW COLUMNS FROM `$table`");
            while ($c = $colsResult->fetch(PDO::FETCH_ASSOC)) {
                $cols[] = $c['Field'];
            }

            $titleCol = in_array('title', $cols) ? 'title' : (in_array('titre', $cols) ? 'titre AS title' : "CONCAT('Item #', id) AS title");
            $hasSeoTitle = in_array('seo_title', $cols);
            $hasSeoDesc  = in_array('seo_description', $cols);
            $hasSeoScore = in_array('seo_score', $cols);

            $selectParts = ["id", $titleCol];
            if ($hasSeoTitle)  $selectParts[] = 'seo_title';
            if ($hasSeoDesc)   $selectParts[] = 'seo_description';
            if ($hasSeoScore)  $selectParts[] = 'seo_score';

            $orderCol = $hasSeoScore ? 'seo_score ASC' : 'id DESC';
            $sql = "SELECT " . implode(', ', $selectParts) . " FROM `$table` ORDER BY $orderCol";
            $stmt = $pdo->query($sql);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $results = [];
            foreach ($items as $item) {
                $issues = [];
                if ($hasSeoTitle && empty($item['seo_title']))       $issues[] = 'Titre SEO manquant';
                if ($hasSeoDesc && empty($item['seo_description']))  $issues[] = 'Meta description manquante';
                $results[] = [
                    'id'             => $item['id'],
                    'title'          => $item['title'],
                    'seo_score'      => $item['seo_score'] ?? 0,
                    'issues_count'   => count($issues),
                    'issues_summary' => $issues,
                ];
            }
            echo json_encode(['success' => true, 'data' => $results]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─────────────────────────────────────────────
    // KEYWORD DENSITY — POST
    // ─────────────────────────────────────────────
    case 'keyword_density':
        try {
            $id      = (int)($input['id'] ?? 0);
            $type    = $input['content_type'] ?? $input['type'] ?? 'page';
            $keyword = trim($input['keyword'] ?? '');
            $table   = $tableMap[$type] ?? null;

            if (!$table) {
                echo json_encode(['success' => false, 'error' => "Type '$type' non supporte"]);
                break;
            }

            // Detect the correct content column
            $cols = [];
            $colsResult = $pdo->query("SHOW COLUMNS FROM `$table`");
            while ($c = $colsResult->fetch(PDO::FETCH_ASSOC)) {
                $cols[] = $c['Field'];
            }
            $contentCol = 'content';
            if (!in_array('content', $cols)) {
                foreach (['body', 'contenu', 'commentaire', 'question_texte'] as $alt) {
                    if (in_array($alt, $cols)) { $contentCol = $alt; break; }
                }
            }

            $stmt = $pdo->prepare("SELECT `$contentCol` AS content_text FROM `$table` WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                echo json_encode(['success' => false, 'error' => 'Contenu non trouve']);
                break;
            }

            $text = strtolower(strip_tags($row['content_text'] ?? ''));
            $wordCount = str_word_count($text);
            $keywordCount = $keyword ? substr_count($text, strtolower($keyword)) : 0;
            $density = $wordCount > 0 ? round(($keywordCount / $wordCount) * 100, 2) : 0;

            echo json_encode(['success' => true, 'data' => [
                'keyword'     => $keyword,
                'occurrences' => $keywordCount,
                'word_count'  => $wordCount,
                'density'     => $density,
                'optimal'     => $density >= 1 && $density <= 3,
            ]]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    // ─────────────────────────────────────────────
    // LIST — GET  (default action)
    // Returns summary list of all content for the module
    // ─────────────────────────────────────────────
    case 'list':
        echo json_encode(['success' => true, 'data' => [], 'message' => 'Use the module UI for listing.']);
        break;

    default:
        echo json_encode(['success' => false, 'error' => "Action '{$action}' non supportee"]);
}
