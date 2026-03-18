<?php
/**
 * ════════════════════════════════════════════════════════════
 * admin/modules/system/design/footer/api.php
 * ════════════════════════════════════════════════════════════
 * API Sauvegarder Footer
 */

define('ADMIN_ROUTER', true);
$_initPath = dirname(__DIR__, 5) . '/includes/init.php';
if (!file_exists($_initPath)) $_initPath = $_SERVER['DOCUMENT_ROOT'] . '/admin/includes/init.php';
require_once $_initPath;

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Non authentifié']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Méthode non autorisée']));
}

$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || $csrf !== $_SESSION['csrf_token']) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Token CSRF invalide']));
}

try {
    $pdo = getDB();
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Erreur BD: ' . $e->getMessage()]));
}

$action = $_POST['action'] ?? '';

if ($action === 'save_footer') {
    try {
        $footerId = (int)($_POST['footer_id'] ?? 0);
        if ($footerId <= 0) throw new Exception('Footer ID invalide');

        $stmt = $pdo->prepare("SELECT * FROM footers WHERE id=? LIMIT 1");
        $stmt->execute([$footerId]);
        $footer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$footer) throw new Exception('Footer introuvable');

        // ── Traiter données ───────────────────────────────────
        $logo_url      = trim($_POST['logo_url'] ?? $footer['logo_url'] ?? '');
        $logo_width    = (int)($_POST['logo_width'] ?? $footer['logo_width'] ?? 120);
        $phone         = trim($_POST['phone'] ?? $footer['phone'] ?? '');
        $email         = trim($_POST['email'] ?? $footer['email'] ?? '');
        $address       = trim($_POST['address'] ?? $footer['address'] ?? '');
        
        $bg_color      = trim($_POST['bg_color'] ?? $footer['bg_color'] ?? '#1e293b');
        $text_color    = trim($_POST['text_color'] ?? $footer['text_color'] ?? '#94a3b8');
        $link_color    = trim($_POST['link_color'] ?? $footer['link_color'] ?? '#cbd5e1');
        $link_hover_color = trim($_POST['link_hover_color'] ?? $footer['link_hover_color'] ?? '#3b82f6');

        // Colonnes (JSON)
        $columns = [];
        if (!empty($_POST['columns_json'])) {
            $columns = json_decode($_POST['columns_json'], true);
            if (!is_array($columns)) $columns = [];
        }
        $columns_json = json_encode($columns, JSON_UNESCAPED_UNICODE);

        // Réseaux (JSON)
        $socialLinks = [];
        if (!empty($_POST['social_links_json'])) {
            $socialLinks = json_decode($_POST['social_links_json'], true);
            if (!is_array($socialLinks)) $socialLinks = [];
        }
        $social_links_json = json_encode($socialLinks, JSON_UNESCAPED_UNICODE);

        // ── Valider couleurs ──────────────────────────────────
        if (!preg_match('/^#[0-9a-f]{6}$/i', $bg_color)) $bg_color = $footer['bg_color'] ?? '#1e293b';
        if (!preg_match('/^#[0-9a-f]{6}$/i', $text_color)) $text_color = $footer['text_color'] ?? '#94a3b8';
        if (!preg_match('/^#[0-9a-f]{6}$/i', $link_color)) $link_color = $footer['link_color'] ?? '#cbd5e1';
        if (!preg_match('/^#[0-9a-f]{6}$/i', $link_hover_color)) $link_hover_color = $footer['link_hover_color'] ?? '#3b82f6';

        // ── UPDATE ────────────────────────────────────────────
        $stmt = $pdo->prepare(
            "UPDATE footers 
             SET logo_url=?, logo_width=?,
                 phone=?, email=?, address=?,
                 bg_color=?, text_color=?, link_color=?, link_hover_color=?,
                 columns=?, social_links=?, updated_at=NOW()
             WHERE id=?"
        );
        $stmt->execute([
            $logo_url, $logo_width,
            $phone, $email, $address,
            $bg_color, $text_color, $link_color, $link_hover_color,
            $columns_json, $social_links_json, $footerId
        ]);

        http_response_code(200);
        die(json_encode([
            'success' => true,
            'message' => 'Footer sauvegardé ✅',
            'footer_id' => $footerId,
            'timestamp' => date('Y-m-d H:i:s')
        ]));

    } catch (Exception $e) {
        http_response_code(400);
        die(json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]));
    }
}

http_response_code(400);
die(json_encode(['success' => false, 'error' => 'Action non reconnue: ' . $action]));
?>