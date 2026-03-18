<?php
/**
 * 🎯 DASHBOARD DÉFINITIF
 * /admin/dashboard.php
 * 
 * VERSION STABLE - Pas de dépendances
 * Pas de modifications après!
 */

session_start();

// ═══════════════════════════════════════════════════════════
// 1. VÉRIFIER L'AUTHENTIFICATION
// ═══════════════════════════════════════════════════════════

if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_email'])) {
    header('Location: /admin/login.php');
    exit;
}

$admin_id = $_SESSION['admin_id'];
$admin_email = $_SESSION['admin_email'];

// ═══════════════════════════════════════════════════════════
// 2. CONNEXION BD DIRECTE
// ═══════════════════════════════════════════════════════════

try {
    $db = new PDO(
        "mysql:host=localhost;dbname=mahe6420_cms-site-ed-bordeaux;charset=utf8mb4",
        "mahe6420_edbordeaux",
        "1KX(M3wwBbbW",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("❌ Erreur BD: " . $e->getMessage());
}

// ═══════════════════════════════════════════════════════════
// 3. CHARGER LES DONNÉES DU DASHBOARD
// ═══════════════════════════════════════════════════════════

try {
    // Admin actuel
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    
    // Stats
    $stats = [
        'total_pages' => 0,
        'total_leads' => 0,
        'total_emails' => 0
    ];
    
    // Tenter de charger les stats (si les tables existent)
    try {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM pages WHERE 1");
        $stats['total_pages'] = $stmt->fetch()['cnt'] ?? 0;
    } catch (Exception $e) {}
    
    try {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM leads WHERE 1");
        $stats['total_leads'] = $stmt->fetch()['cnt'] ?? 0;
    } catch (Exception $e) {}
    
    try {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM emails WHERE 1");
        $stats['total_emails'] = $stmt->fetch()['cnt'] ?? 0;
    } catch (Exception $e) {}
    
} catch (Exception $e) {
    $admin = null;
    $stats = ['total_pages' => 0, 'total_leads' => 0, 'total_emails' => 0];
}

// ═══════════════════════════════════════════════════════════
// 4. TRAITER LES ACTIONS
// ═══════════════════════════════════════════════════════════

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Logout
    if ($action === 'logout') {
        session_destroy();
        header('Location: /admin/login.php');
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Eduardo De Sul</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .navbar {
            background: white;
            border-bottom: 1px solid #eee;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        
        .navbar h1 {
            font-size: 24px;
            color: #667eea;
        }
        
        .navbar .user {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .navbar .user-info {
            text-align: right;
        }
        
        .navbar .user-info .name {
            font-weight: 600;
            color: #333;
        }
        
        .navbar .user-info .email {
            font-size: 12px;
            color: #999;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left-color: #28a745;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #dc3545;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .card h2 {
            margin-bottom: 15px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #667eea;
        }
        
        .stat-box .label {
            font-size: 14px;
            color: #999;
            margin-bottom: 10px;
        }
        
        .stat-box .value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        
        .menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .menu-item {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            text-decoration: none;
            color: #333;
            border: 1px solid #eee;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .menu-item:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .menu-item strong {
            display: block;
            margin-bottom: 5px;
        }
        
        .menu-item .icon {
            font-size: 24px;
            margin-bottom: 8px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        table th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: 600;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        table tr:hover {
            background: #f9f9f9;
        }
        
        .welcome {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .welcome h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .welcome p {
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .footer {
            text-align: center;
            padding: 30px;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <div class="navbar">
        <h1>🏡 Eduardo De Sul - Admin</h1>
        <div class="user">
            <div class="user-info">
                <div class="name"><?php echo htmlspecialchars($admin['name'] ?? 'Admin'); ?></div>
                <div class="email"><?php echo htmlspecialchars($admin_email); ?></div>
            </div>
            <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="logout-btn">Déconnexion</button>
            </form>
        </div>
    </div>

    <!-- CONTENU -->
    <div class="container">
        
        <!-- WELCOME -->
        <div class="welcome">
            <h1>Bienvenue, <?php echo htmlspecialchars($admin['name'] ?? 'Admin'); ?> 👋</h1>
            <p>Vous êtes connecté au dashboard administratif d'Eduardo De Sul. Gérez vos pages, leads, et emails.</p>
        </div>

        <!-- MESSAGES -->
        <?php if ($message): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats">
            <div class="stat-box">
                <div class="label">📄 Pages</div>
                <div class="value"><?php echo $stats['total_pages']; ?></div>
            </div>
            <div class="stat-box">
                <div class="label">📋 Leads</div>
                <div class="value"><?php echo $stats['total_leads']; ?></div>
            </div>
            <div class="stat-box">
                <div class="label">📧 Emails</div>
                <div class="value"><?php echo $stats['total_emails']; ?></div>
            </div>
        </div>

        <!-- ACTIONS -->
        <div class="card">
            <h2>🎯 Actions Rapides</h2>
            <div class="menu">
                <a href="/admin/pages/" class="menu-item">
                    <div class="icon">📄</div>
                    <strong>Pages</strong>
                    <small>Gérer les pages</small>
                </a>
                <a href="/admin/leads/" class="menu-item">
                    <div class="icon">📋</div>
                    <strong>Leads</strong>
                    <small>Voir les contacts</small>
                </a>
                <a href="/admin/emails/" class="menu-item">
                    <div class="icon">📧</div>
                    <strong>Emails</strong>
                    <small>Gestion email</small>
                </a>
                <a href="/setup/" class="menu-item">
                    <div class="icon">⚙️</div>
                    <strong>Setup</strong>
                    <small>Configuration</small>
                </a>
            </div>
        </div>

        <!-- INFORMATIONS -->
        <div class="card">
            <h2>ℹ️ Informations</h2>
            <table>
                <tr>
                    <th>Clé</th>
                    <th>Valeur</th>
                </tr>
                <tr>
                    <td><strong>Nom:</strong></td>
                    <td><?php echo htmlspecialchars($admin['name'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td><?php echo htmlspecialchars($admin_email); ?></td>
                </tr>
                <tr>
                    <td><strong>Date connexion:</strong></td>
                    <td><?php echo date('d/m/Y H:i:s'); ?></td>
                </tr>
                <tr>
                    <td><strong>BD:</strong></td>
                    <td>✅ Connectée</td>
                </tr>
                <tr>
                    <td><strong>PHP Version:</strong></td>
                    <td><?php echo phpversion(); ?></td>
                </tr>
            </table>
        </div>

    </div>

    <!-- FOOTER -->
    <div class="footer">
        <p>Eduardo De Sul © 2025 | Admin Dashboard v1.0</p>
    </div>
</body>
</html>