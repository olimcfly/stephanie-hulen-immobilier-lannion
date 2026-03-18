<?php
/**
 * ════════════════════════════════════════════════════════════
 * admin/modules/system/design/header/api.php
 * ════════════════════════════════════════════════════════════
 * API Sauvegarder Header
 */

define('ADMIN_ROUTER', true);
$_initPath = dirname(__DIR__, 5) . '/includes/init.php';
if (!file_exists($_initPath)) $_initPath = $_SERVER['DOCUMENT_ROOT'] . '/admin/includes/init.php';
require_once $_initPath;

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// ── Sécurité ──────────────────────────────────────────────────
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Non authentifié']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Méthode non autorisée']));
}

// ── CSRF ──────────────────────────────────────────────────────
$csrf = $_POST['csrf_token'] ?? '';
if (empty($_SESSION['csrf_token']) || $csrf !== $_SESSION['csrf_token']) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Token CSRF invalide']));
}

// ── DB ────────────────────────────────────────────────────────
try {
    $pdo = getDB();
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'error' => 'Erreur BD: ' . $e->getMessage()]));
}

// ── Récupérer l'action ────────────────────────────────────────
$action = $_POST['action'] ?? '';

if ($action === 'save_header') {
    try {
        $headerId = (int)($_POST['header_id'] ?? 0);
        if ($headerId <= 0) throw new Exception('Header ID invalide');

        // ── Récupérer le header actuel ────────────────────────
        $stmt = $pdo->prepare("SELECT * FROM headers WHERE id=? LIMIT 1");
        $stmt->execute([$headerId]);
        $header = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$header) throw new Exception('Header introuvable');

        // ── Traiter les données ───────────────────────────────
        $logo_url      = trim($_POST['logo_url'] ?? $header['logo_url'] ?? '');
        $logo_width    = (int)($_POST['logo_width'] ?? $header['logo_width'] ?? 150);
        $phone_number  = trim($_POST['phone_number'] ?? $header['phone_number'] ?? '');
        
        $cta_text      = trim($_POST['cta_text'] ?? $header['cta_text'] ?? 'Contact');
        $cta_link      = trim($_POST['cta_link'] ?? $header['cta_link'] ?? '/contact');
        
        $bg_color      = trim($_POST['bg_color'] ?? $header['bg_color'] ?? '#ffffff');
        $text_color    = trim($_POST['text_color'] ?? $header['text_color'] ?? '#1e293b');
        $hover_color   = trim($_POST['hover_color'] ?? $header['hover_color'] ?? '#3b82f6');
        
        // Menu items (JSON)
        $menuItems = [];
        if (!empty($_POST['menu_items_json'])) {
            $menuItems = json_decode($_POST['menu_items_json'], true);
            if (!is_array($menuItems)) $menuItems = [];
        }
        $menu_items_json = json_encode($menuItems, JSON_UNESCAPED_UNICODE);

        // ── Valider couleurs ──────────────────────────────────
        if (!preg_match('/^#[0-9a-f]{6}$/i', $bg_color)) $bg_color = $header['bg_color'] ?? '#ffffff';
        if (!preg_match('/^#[0-9a-f]{6}$/i', $text_color)) $text_color = $header['text_color'] ?? '#1e293b';
        if (!preg_match('/^#[0-9a-f]{6}$/i', $hover_color)) $hover_color = $header['hover_color'] ?? '#3b82f6';

        // ── UPDATE ────────────────────────────────────────────
        $stmt = $pdo->prepare(
            "UPDATE headers 
             SET logo_url=?, logo_width=?, phone_number=?,
                 cta_text=?, cta_link=?,
                 bg_color=?, text_color=?, hover_color=?,
                 menu_items=?, updated_at=NOW()
             WHERE id=?"
        );
        $stmt->execute([
            $logo_url, $logo_width, $phone_number,
            $cta_text, $cta_link,
            $bg_color, $text_color, $hover_color,
            $menu_items_json, $headerId
        ]);

        http_response_code(200);
        die(json_encode([
            'success' => true,
            'message' => 'Header sauvegardé ✅',
            'header_id' => $headerId,
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

// ── Action non reconnue ───────────────────────────────────────
http_response_code(400);
die(json_encode(['success' => false, 'error' => 'Action non reconnue: ' . $action]));
?>