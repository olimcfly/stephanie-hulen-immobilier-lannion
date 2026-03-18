<?php
/**
 * API Handler: seo
 * Called via: /admin/api/router.php?module=seo&action=...
 * Operates on: pages table (seo_* columns)
 *
 * JS actions: toggle-noindex, toggle-validation, analyze, analyze-all,
 *             preview-seo, generate-seo, details, list, get, update, validate, stats
 * Note: JS sends all requests as GET, so params come from $_GET.
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

/**
 * Helper: compute a basic SEO score for a page row.
 * Returns ['percentage'=>0-100, 'grade'=>string, 'issues'=>[], 'checks'=>[...]]
 */
function computeSeoScore(array $page): array {
    $checks = [];
    $score = 0;
    $maxScore = 0;
    $issues = [];

    // 1. Title length (10-60 chars ideal)
    $title = $page['seo_title'] ?? $page['title'] ?? '';
    $titleLen = mb_strlen($title);
    $maxScore += 20;
    if ($titleLen >= 10 && $titleLen <= 60) {
        $score += 20;
        $checks['title'] = ['status' => 'success', 'message' => "Titre ({$titleLen} car.) - Longueur ideale"];
    } elseif ($titleLen > 0) {
        $score += 10;
        $tip = $titleLen < 10 ? 'trop court' : 'trop long';
        $checks['title'] = ['status' => 'warning', 'message' => "Titre ({$titleLen} car.) - {$tip}, ideal 10-60"];
        $issues[] = "Titre {$tip} ({$titleLen} car.)";
    } else {
        $checks['title'] = ['status' => 'error', 'message' => 'Titre manquant'];
        $issues[] = 'Titre SEO manquant';
    }

    // 2. Meta title (seo_title)
    $seoTitle = $page['seo_title'] ?? '';
    $seoTitleLen = mb_strlen($seoTitle);
    $maxScore += 15;
    if ($seoTitleLen >= 30 && $seoTitleLen <= 60) {
        $score += 15;
        $checks['seo_title'] = ['status' => 'success', 'message' => "Meta title ({$seoTitleLen} car.) - OK"];
    } elseif ($seoTitleLen > 0) {
        $score += 8;
        $checks['seo_title'] = ['status' => 'warning', 'message' => "Meta title ({$seoTitleLen} car.) - ideal 30-60"];
        $issues[] = "Meta title non optimal ({$seoTitleLen} car.)";
    } else {
        $checks['seo_title'] = ['status' => 'error', 'message' => 'Meta title manquant'];
        $issues[] = 'Meta title manquant';
    }

    // 3. Meta description (120-160 chars ideal)
    $desc = $page['seo_description'] ?? '';
    $descLen = mb_strlen($desc);
    $maxScore += 20;
    if ($descLen >= 120 && $descLen <= 160) {
        $score += 20;
        $checks['seo_description'] = ['status' => 'success', 'message' => "Meta description ({$descLen} car.) - Longueur ideale"];
    } elseif ($descLen > 0) {
        $score += 10;
        $tip = $descLen < 120 ? 'trop courte' : 'trop longue';
        $checks['seo_description'] = ['status' => 'warning', 'message' => "Meta description ({$descLen} car.) - {$tip}, ideal 120-160"];
        $issues[] = "Meta description {$tip} ({$descLen} car.)";
    } else {
        $checks['seo_description'] = ['status' => 'error', 'message' => 'Meta description manquante'];
        $issues[] = 'Meta description manquante';
    }

    // 4. Keywords
    $keywords = $page['seo_keywords'] ?? '';
    $kwCount = $keywords ? count(array_filter(array_map('trim', explode(',', $keywords)))) : 0;
    $maxScore += 15;
    if ($kwCount >= 3 && $kwCount <= 8) {
        $score += 15;
        $checks['keywords'] = ['status' => 'success', 'message' => "{$kwCount} mots-cles - OK"];
    } elseif ($kwCount > 0) {
        $score += 8;
        $checks['keywords'] = ['status' => 'warning', 'message' => "{$kwCount} mots-cles - ideal 3-8"];
        $issues[] = "Nombre de mots-cles non optimal ({$kwCount})";
    } else {
        $checks['keywords'] = ['status' => 'error', 'message' => 'Aucun mot-cle defini'];
        $issues[] = 'Aucun mot-cle SEO';
    }

    // 5. Slug
    $slug = $page['slug'] ?? '';
    $maxScore += 10;
    if (!empty($slug) && mb_strlen($slug) <= 60 && $slug !== '') {
        $score += 10;
        $checks['slug'] = ['status' => 'success', 'message' => "URL propre - OK"];
    } elseif (!empty($slug)) {
        $score += 5;
        $checks['slug'] = ['status' => 'warning', 'message' => "URL trop longue (" . mb_strlen($slug) . " car.)"];
        $issues[] = 'URL trop longue';
    } else {
        $checks['slug'] = ['status' => 'error', 'message' => 'Slug manquant'];
        $issues[] = 'Slug manquant';
    }

    // 6. Content length
    $content = $page['content'] ?? '';
    $contentLen = mb_strlen(strip_tags($content));
    $maxScore += 20;
    if ($contentLen >= 300) {
        $score += 20;
        $checks['content'] = ['status' => 'success', 'message' => "Contenu ({$contentLen} car.) - Suffisant"];
    } elseif ($contentLen >= 100) {
        $score += 10;
        $checks['content'] = ['status' => 'warning', 'message' => "Contenu ({$contentLen} car.) - Un peu court, ideal > 300"];
        $issues[] = "Contenu trop court ({$contentLen} car.)";
    } else {
        $checks['content'] = ['status' => 'error', 'message' => "Contenu ({$contentLen} car.) - Insuffisant"];
        $issues[] = 'Contenu insuffisant';
    }

    $percentage = $maxScore > 0 ? (int)round(($score / $maxScore) * 100) : 0;

    if ($percentage >= 80) $grade = 'excellent';
    elseif ($percentage >= 60) $grade = 'good';
    elseif ($percentage >= 40) $grade = 'warning';
    else $grade = 'error';

    return [
        'percentage' => $percentage,
        'grade' => $grade,
        'issues' => $issues,
        'checks' => $checks,
    ];
}

switch ($action) {
    // ──────────────────────────────────────────────
    // Existing actions
    // ──────────────────────────────────────────────
    case 'list':
        try {
            $stmt = $pdo->query("SELECT id, title, slug, status, seo_score, seo_title, seo_description, seo_keywords, seo_analyzed_at, noindex FROM pages ORDER BY seo_score ASC, title ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Page non trouvee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['seo_title', 'seo_description', 'seo_keywords', 'seo_score', 'seo_issues', 'noindex'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ SEO']); break; }
            $sets[] = "seo_analyzed_at = NOW()";
            $params[] = $id;
            $pdo->prepare("UPDATE pages SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'SEO mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'validate':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("UPDATE pages SET seo_validated = 1, seo_validated_at = NOW() WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'SEO valide']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'stats':
        try {
            $stats = [
                'total_pages' => (int)$pdo->query("SELECT COUNT(*) FROM pages")->fetchColumn(),
                'avg_score' => round((float)$pdo->query("SELECT AVG(seo_score) FROM pages WHERE seo_score > 0")->fetchColumn(), 1),
                'analyzed' => (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE seo_analyzed_at IS NOT NULL")->fetchColumn(),
                'noindex' => (int)$pdo->query("SELECT COUNT(*) FROM pages WHERE noindex = 1")->fetchColumn(),
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // ──────────────────────────────────────────────
    // NEW: actions called by the JS front-end
    // ──────────────────────────────────────────────

    /**
     * toggle-noindex
     * JS: fetch(...&action=toggle-noindex&id=X&noindex=0|1)
     * Expects: {success: true}
     */
    case 'toggle-noindex':
        try {
            $id = (int)($_GET['id'] ?? 0);
            $noindex = (int)($_GET['noindex'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID requis']); break; }
            $pdo->prepare("UPDATE pages SET noindex = ? WHERE id = ?")->execute([$noindex, $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    /**
     * toggle-validation
     * JS: fetch(...&action=toggle-validation&id=X&validated=0|1)
     * Expects: {success: true}
     */
    case 'toggle-validation':
        try {
            $id = (int)($_GET['id'] ?? 0);
            $validated = (int)($_GET['validated'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID requis']); break; }
            if ($validated) {
                $pdo->prepare("UPDATE pages SET seo_validated = 1, seo_validated_at = NOW() WHERE id = ?")->execute([$id]);
            } else {
                $pdo->prepare("UPDATE pages SET seo_validated = 0, seo_validated_at = NULL WHERE id = ?")->execute([$id]);
            }
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    /**
     * analyze
     * JS: fetch(...&action=analyze&id=X)
     * Expects: {success: true, result: {percentage: N, issues: [...]}}
     */
    case 'analyze':
        try {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID requis']); break; }
            $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
            $stmt->execute([$id]);
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$page) { echo json_encode(['success' => false, 'error' => 'Page non trouvee']); break; }

            $result = computeSeoScore($page);

            // Persist score and issues
            $issuesJson = json_encode($result['issues'], JSON_UNESCAPED_UNICODE);
            $pdo->prepare("UPDATE pages SET seo_score = ?, seo_issues = ?, seo_analyzed_at = NOW() WHERE id = ?")
                ->execute([$result['percentage'], $issuesJson, $id]);

            echo json_encode(['success' => true, 'result' => $result]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    /**
     * analyze-all
     * JS: fetch(...&action=analyze-all)
     * Expects: {success: true, analyzed: N}
     */
    case 'analyze-all':
        try {
            $rows = $pdo->query("SELECT * FROM pages")->fetchAll(PDO::FETCH_ASSOC);
            $count = 0;
            $updateStmt = $pdo->prepare("UPDATE pages SET seo_score = ?, seo_issues = ?, seo_analyzed_at = NOW() WHERE id = ?");
            foreach ($rows as $page) {
                $result = computeSeoScore($page);
                $issuesJson = json_encode($result['issues'], JSON_UNESCAPED_UNICODE);
                $updateStmt->execute([$result['percentage'], $issuesJson, $page['id']]);
                $count++;
            }
            echo json_encode(['success' => true, 'analyzed' => $count]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    /**
     * preview-seo
     * JS: fetch(...&action=preview-seo&id=X)
     * Expects: {success: true, current: {seo_title, seo_description, seo_keywords, description},
     *           preview: {seo_title, seo_description, seo_keywords, description, suggestions: []},
     *           ai_provider: '...'}
     */
    case 'preview-seo':
        try {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID requis']); break; }
            $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
            $stmt->execute([$id]);
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$page) { echo json_encode(['success' => false, 'error' => 'Page non trouvee']); break; }

            $current = [
                'seo_title' => $page['seo_title'] ?? '',
                'seo_description' => $page['seo_description'] ?? '',
                'seo_keywords' => $page['seo_keywords'] ?? '',
                'description' => $page['description'] ?? '',
            ];

            // Generate improved suggestions based on current data
            $pageTitle = $page['title'] ?? '';
            $contentSnippet = mb_substr(strip_tags($page['content'] ?? ''), 0, 200);

            $previewTitle = $current['seo_title'];
            if (empty($previewTitle) || mb_strlen($previewTitle) < 20) {
                $previewTitle = mb_strlen($pageTitle) > 60 ? mb_substr($pageTitle, 0, 57) . '...' : $pageTitle;
            }

            $previewDesc = $current['seo_description'];
            if (empty($previewDesc) || mb_strlen($previewDesc) < 80) {
                $previewDesc = mb_strlen($contentSnippet) > 155 ? mb_substr($contentSnippet, 0, 152) . '...' : $contentSnippet;
            }

            $previewKeywords = $current['seo_keywords'];
            if (empty($previewKeywords)) {
                // Extract a few words from title as basic keywords
                $words = array_filter(explode(' ', strtolower($pageTitle)), function($w) { return mb_strlen($w) > 3; });
                $previewKeywords = implode(', ', array_slice($words, 0, 5));
            }

            $previewDescription = $current['description'];
            if (empty($previewDescription)) {
                $previewDescription = mb_strlen($contentSnippet) > 150 ? mb_substr($contentSnippet, 0, 147) . '...' : $contentSnippet;
            }

            $suggestions = [];
            if (mb_strlen($previewTitle) < 30) $suggestions[] = 'Le meta title devrait faire entre 30 et 60 caracteres.';
            if (mb_strlen($previewDesc) < 120) $suggestions[] = 'La meta description devrait faire entre 120 et 160 caracteres.';
            if (empty($previewKeywords)) $suggestions[] = 'Ajoutez 3 a 8 mots-cles pertinents.';
            if (mb_strlen(strip_tags($page['content'] ?? '')) < 300) $suggestions[] = 'Le contenu est trop court. Visez au moins 300 caracteres.';

            $preview = [
                'seo_title' => $previewTitle,
                'seo_description' => $previewDesc,
                'seo_keywords' => $previewKeywords,
                'description' => $previewDescription,
                'suggestions' => $suggestions,
            ];

            echo json_encode([
                'success' => true,
                'current' => $current,
                'preview' => $preview,
                'ai_provider' => 'Analyse automatique',
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    /**
     * generate-seo
     * JS: fetch(...&action=generate-seo&id=X)
     * Expects: {success: true, new_analysis: {percentage: N, issues: [...]}, new_score: N}
     */
    case 'generate-seo':
        try {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID requis']); break; }
            $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
            $stmt->execute([$id]);
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$page) { echo json_encode(['success' => false, 'error' => 'Page non trouvee']); break; }

            // Generate basic SEO fields if missing
            $pageTitle = $page['title'] ?? '';
            $contentSnippet = mb_substr(strip_tags($page['content'] ?? ''), 0, 200);

            $newTitle = $page['seo_title'] ?? '';
            if (empty($newTitle) || mb_strlen($newTitle) < 20) {
                $newTitle = mb_strlen($pageTitle) > 60 ? mb_substr($pageTitle, 0, 57) . '...' : $pageTitle;
            }

            $newDesc = $page['seo_description'] ?? '';
            if (empty($newDesc) || mb_strlen($newDesc) < 80) {
                $newDesc = mb_strlen($contentSnippet) > 155 ? mb_substr($contentSnippet, 0, 152) . '...' : $contentSnippet;
            }

            $newKeywords = $page['seo_keywords'] ?? '';
            if (empty($newKeywords)) {
                $words = array_filter(explode(' ', strtolower($pageTitle)), function($w) { return mb_strlen($w) > 3; });
                $newKeywords = implode(', ', array_slice($words, 0, 5));
            }

            // Save the generated SEO fields
            $pdo->prepare("UPDATE pages SET seo_title = ?, seo_description = ?, seo_keywords = ? WHERE id = ?")
                ->execute([$newTitle, $newDesc, $newKeywords, $id]);

            // Re-fetch and re-compute score with updated fields
            $stmt2 = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
            $stmt2->execute([$id]);
            $updatedPage = $stmt2->fetch(PDO::FETCH_ASSOC);
            $result = computeSeoScore($updatedPage);

            // Persist new score
            $issuesJson = json_encode($result['issues'], JSON_UNESCAPED_UNICODE);
            $pdo->prepare("UPDATE pages SET seo_score = ?, seo_issues = ?, seo_analyzed_at = NOW() WHERE id = ?")
                ->execute([$result['percentage'], $issuesJson, $id]);

            echo json_encode([
                'success' => true,
                'new_analysis' => $result,
                'new_score' => $result['percentage'],
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    /**
     * details
     * JS: fetch(...&action=details&id=X)
     * Expects: {success: true, page: {id, title}, seo: {percentage, grade, checks: {...}}}
     */
    case 'details':
        try {
            $id = (int)($_GET['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'error' => 'ID requis']); break; }
            $stmt = $pdo->prepare("SELECT * FROM pages WHERE id = ?");
            $stmt->execute([$id]);
            $page = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$page) { echo json_encode(['success' => false, 'error' => 'Page non trouvee']); break; }

            $seo = computeSeoScore($page);

            echo json_encode([
                'success' => true,
                'page' => [
                    'id' => (int)$page['id'],
                    'title' => $page['title'] ?? '',
                    'slug' => $page['slug'] ?? '',
                    'status' => $page['status'] ?? '',
                ],
                'seo' => $seo,
            ]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        break;


    case 'save_score':
        try {
            $data     = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $ctx      = preg_replace('/[^a-z]/', '', $data['context'] ?? '');
            $entityId = (int)($data['entity_id'] ?? 0);
            $scores   = $data['scores'] ?? [];
            if (!$ctx || !$entityId) { echo json_encode(['success'=>false,'error'=>'context et entity_id requis']); break; }
            $gl  = (int)($scores['global']     ?? $scores['global']     ?? 0);
            $tch = (int)($scores['technique']  ?? 0);
            $cnt = (int)($scores['contenu']    ?? 0);
            $sem = (int)($scores['semantique'] ?? 0);
            $kw  = trim($data['focus_keyword'] ?? '');
            try {
                $chk = $pdo->prepare("SELECT id FROM seo_scores WHERE context=? AND entity_id=? LIMIT 1");
                $chk->execute([$ctx, $entityId]);
                if ($chk->fetchColumn()) {
                    $pdo->prepare("UPDATE seo_scores SET score_global=?,score_technique=?,score_contenu=?,score_semantique=?,focus_keyword=?,updated_at=NOW() WHERE context=? AND entity_id=?")
                        ->execute([$gl,$tch,$cnt,$sem,$kw,$ctx,$entityId]);
                } else {
                    $pdo->prepare("INSERT INTO seo_scores(context,entity_id,score_global,score_technique,score_contenu,score_semantique,focus_keyword,created_at) VALUES(?,?,?,?,?,?,?,NOW())")
                        ->execute([$ctx,$entityId,$gl,$tch,$cnt,$sem,$kw]);
                }
                echo json_encode(['success'=>true,'scores'=>['global'=>$gl,'technique'=>$tch,'contenu'=>$cnt,'semantique'=>$sem]]);
            } catch(PDOException $e2) {
                echo json_encode(['success'=>true,'warning'=>'seo_scores table missing: '.$e2->getMessage()]);
            }
        } catch(PDOException $e) {
            echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
