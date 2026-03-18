<?php
/**
 * API Handler: local-seo
 * Called via: /admin/api/router.php?module=local-seo&action=...
 * Tables: gmb_publications, gmb_reviews, gmb_questions, local_partners
 */

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = CURRENT_ACTION;

switch ($action) {
    // --- Publications GMB ---
    case 'list_publications':
    case 'list':
        try {
            $status = $input['status'] ?? $_GET['status'] ?? '';
            $where = ''; $params = [];
            if ($status) { $where = 'WHERE status = ?'; $params[] = $status; }
            $stmt = $pdo->prepare("SELECT * FROM gmb_publications {$where} ORDER BY scheduled_date DESC, created_at DESC");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_publication':
        try {
            $id = (int)($input['id'] ?? $_GET['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT * FROM gmb_publications WHERE id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode($row ? ['success' => true, 'data' => $row] : ['success' => false, 'message' => 'Publication non trouvee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_publication':
        try {
            $stmt = $pdo->prepare("INSERT INTO gmb_publications (title, content, post_type, cta_type, cta_url, image_url, scheduled_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['title'] ?? '', $input['content'] ?? '', $input['post_type'] ?? 'standard',
                $input['cta_type'] ?? null, $input['cta_url'] ?? null, $input['image_url'] ?? null,
                $input['scheduled_date'] ?? null, $input['status'] ?? 'draft'
            ]);
            echo json_encode(['success' => true, 'message' => 'Publication creee', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_publication':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['title', 'content', 'post_type', 'cta_type', 'cta_url', 'image_url', 'scheduled_date', 'status'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE gmb_publications SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Publication mise a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_publication':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM gmb_publications WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Publication supprimee']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // --- Reviews GMB ---
    case 'list_reviews':
        try {
            $stmt = $pdo->query("SELECT * FROM gmb_reviews ORDER BY created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'reply_review':
        try {
            $id = (int)($input['id'] ?? 0);
            $reply = $input['reply'] ?? '';
            $pdo->prepare("UPDATE gmb_reviews SET reply_text = ?, reply_status = 'replied', replied_at = NOW() WHERE id = ?")->execute([$reply, $id]);
            echo json_encode(['success' => true, 'message' => 'Reponse enregistree']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // --- Questions GMB ---
    case 'list_questions':
        try {
            $stmt = $pdo->query("SELECT * FROM gmb_questions ORDER BY created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'answer_question':
        try {
            $id = (int)($input['id'] ?? 0);
            $answer = $input['answer'] ?? '';
            $pdo->prepare("UPDATE gmb_questions SET answer_text = ?, answer_status = 'answered', answered_at = NOW() WHERE id = ?")->execute([$answer, $id]);
            echo json_encode(['success' => true, 'message' => 'Reponse enregistree']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // --- Local Partners ---
    case 'list_partners':
        try {
            $stmt = $pdo->query("SELECT * FROM local_partners ORDER BY created_at DESC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'create_partner':
        try {
            $stmt = $pdo->prepare("INSERT INTO local_partners (name, website, contact_email, contact_name, category, link_url, link_status, our_link_on_their_site, is_visible_in_guide, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $input['name'] ?? '', $input['website'] ?? '', $input['contact_email'] ?? '',
                $input['contact_name'] ?? '', $input['category'] ?? '', $input['link_url'] ?? '',
                $input['link_status'] ?? 'pending', (int)($input['our_link_on_their_site'] ?? 0),
                (int)($input['is_visible_in_guide'] ?? 0), $input['notes'] ?? ''
            ]);
            echo json_encode(['success' => true, 'message' => 'Partenaire cree', 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_partner':
        try {
            $id = (int)($input['id'] ?? 0);
            if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
            $allowed = ['name', 'website', 'contact_email', 'contact_name', 'category', 'link_url', 'link_status', 'our_link_on_their_site', 'is_visible_in_guide', 'notes'];
            $sets = []; $params = [];
            foreach ($input as $k => $v) { if (in_array($k, $allowed)) { $sets[] = "{$k} = ?"; $params[] = $v; } }
            if (empty($sets)) { echo json_encode(['success' => false, 'message' => 'Aucun champ']); break; }
            $params[] = $id;
            $pdo->prepare("UPDATE local_partners SET " . implode(', ', $sets) . " WHERE id = ?")->execute($params);
            echo json_encode(['success' => true, 'message' => 'Partenaire mis a jour']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_partner':
        try {
            $id = (int)($input['id'] ?? 0);
            $pdo->prepare("DELETE FROM local_partners WHERE id = ?")->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Partenaire supprime']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    // --- Stats ---
    case 'stats':
        try {
            $stats = [
                'publications_total' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_publications")->fetchColumn(),
                'publications_published' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_publications WHERE status = 'published'")->fetchColumn(),
                'publications_scheduled' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_publications WHERE status = 'scheduled'")->fetchColumn(),
                'reviews_total' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_reviews")->fetchColumn(),
                'reviews_pending' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_reviews WHERE reply_status = 'pending'")->fetchColumn(),
                'avg_rating' => round((float)$pdo->query("SELECT AVG(rating) FROM gmb_reviews")->fetchColumn(), 1),
                'questions_total' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_questions")->fetchColumn(),
                'questions_unanswered' => (int)$pdo->query("SELECT COUNT(*) FROM gmb_questions WHERE answer_status = 'pending'")->fetchColumn(),
                'partners_total' => (int)$pdo->query("SELECT COUNT(*) FROM local_partners")->fetchColumn(),
                'partners_active' => (int)$pdo->query("SELECT COUNT(*) FROM local_partners WHERE link_status = 'active'")->fetchColumn(),
            ];
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => "Action '{$action}' non supportee"]);
}
