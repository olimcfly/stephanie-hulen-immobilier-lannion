<?php

/**
 * API Maintenance
 */

define('ADMIN_API', true);

require_once dirname(__FILE__, 4) . '/includes/init.php';

header('Content-Type: application/json');

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$action = trim($_POST['action'] ?? '');

try {

    // créer table si absente
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS maintenance (
            id INT PRIMARY KEY AUTO_INCREMENT,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            message TEXT,
            allowed_ips TEXT,
            end_date DATETIME NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // créer ligne par défaut
    $count = $pdo->query("SELECT COUNT(*) FROM maintenance")->fetchColumn();

    if ($count == 0) {
        $pdo->exec("
            INSERT INTO maintenance (id, is_active, message, allowed_ips)
            VALUES (1,0,'','127.0.0.1')
        ");
    }

    switch ($action) {

        case 'toggle':

            $val = (int)($_POST['is_active'] ?? 0);

            $stmt = $pdo->prepare("
                UPDATE maintenance
                SET is_active = ?
                WHERE id = 1
            ");

            $stmt->execute([$val]);

            echo json_encode([
                'success' => true,
                'is_active' => $val
            ]);

        break;


        case 'save_message':

            $msg = trim($_POST['message'] ?? '');

            $stmt = $pdo->prepare("
                UPDATE maintenance
                SET message = ?
                WHERE id = 1
            ");

            $stmt->execute([$msg]);

            echo json_encode(['success' => true]);

        break;


        case 'save_whitelist':

            $ips = trim($_POST['allowed_ips'] ?? '');

            $stmt = $pdo->prepare("
                UPDATE maintenance
                SET allowed_ips = ?
                WHERE id = 1
            ");

            $stmt->execute([$ips]);

            echo json_encode(['success' => true]);

        break;


        case 'diagnostic':

            $result = [];

            // test DB
            try {
                $pdo->query("SELECT 1");
                $result['db_connection'] = "OK";
            } catch (Exception $e) {
                $result['db_connection'] = "ERROR : " . $e->getMessage();
            }

            // test table
            try {
                $pdo->query("SELECT * FROM maintenance LIMIT 1");
                $result['table_maintenance'] = "OK";
            } catch (Exception $e) {
                $result['table_maintenance'] = "TABLE ABSENTE";
            }

            // test update
            try {
                $pdo->exec("UPDATE maintenance SET updated_at = NOW() WHERE id = 1");
                $result['update_test'] = "OK";
            } catch (Exception $e) {
                $result['update_test'] = "UPDATE FAIL : " . $e->getMessage();
            }

            echo json_encode($result, JSON_PRETTY_PRINT);

        break;


        default:

            echo json_encode([
                'success' => false,
                'message' => 'Action inconnue : ' . $action
            ]);

    }

} catch (Exception $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}