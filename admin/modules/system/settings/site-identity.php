<?php
/**
 * Gestion Logo & Favicon
 * /admin/modules/settings/site-identity.php
 */

require_once __DIR__ . '/../../includes/init.php';

if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('Location: /admin/login.php');
    exit;
}

$db = Database::getInstance();
$message = '';
$messageType = '';

// ── Fonctions ──
function getSetting($db, $key, $default = '') {
    try {
        $stmt = $db->prepare("SELECT value FROM settings WHERE key_name = ?");
        $stmt->execute([$key]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($r && $r['value'] !== null && $r['value'] !== '') ? $r['value'] : $default;
    } catch (PDOException $e) { return $default; }
}

function updateSetting($db, $key, $value) {
    try {
        $stmt = $db->prepare("INSERT INTO settings (key_name, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?");
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (PDOException $e) { return false; }
}

function handleImageUpload($file, $uploadDir, $prefix, $allowedTypes, $maxSize) {
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => "Fichier trop lourd (max " . round($maxSize / 1024) . " Ko)."];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => "Type non autorisé ($mimeType)."];
    }
    $extensions = [
        'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/svg+xml' => 'svg',
        'image/webp' => 'webp', 'image/x-icon' => 'ico', 'image/vnd.microsoft.icon' => 'ico'
    ];
    $ext = $extensions[$mimeType] ?? 'png';
    $filename = $prefix . '_' . time() . '.' . $ext;
    
    // Supprimer anciens fichiers du même préfixe
    foreach (glob($uploadDir . $prefix . '_*') as $old) { unlink($old); }
    
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['success' => true, 'filename' => $filename];
    }
    return ['success' => false, 'error' => "Erreur lors de l'upload."];
}

// ── Traitement POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $message = 'Token de sécurité invalide.';
        $messageType = 'error';
    } else {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/site/';
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
        
        // Upload Logo
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
            $r = handleImageUpload($_FILES['site_logo'], $uploadDir, 'logo', 
                ['image/png','image/jpeg','image/svg+xml','image/webp'], 2*1024*1024);
            if ($r['success']) {
                updateSetting($db, 'site_logo', '/uploads/site/' . $r['filename']);
                $message = 'Logo mis à jour !';
                $messageType = 'success';
            } else { $message = $r['error']; $messageType = 'error'; }
        }
        
        // Upload Favicon
        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
            $r = handleImageUpload($_FILES['site_favicon'], $uploadDir, 'favicon',
                ['image/png','image/x-icon','image/svg+xml','image/vnd.microsoft.icon'], 512*1024);
            if ($r['success']) {
                updateSetting($db, 'site_favicon', '/uploads/site/' . $r['filename']);
                $message .= ($message ? ' ' : '') . 'Favicon mis à jour !';
                $messageType = 'success';
            } else { $message = $r['error']; $messageType = 'error'; }
        }
        
        // Supprimer Logo
        if (isset($_POST['delete_logo']) && $_POST['delete_logo'] === '1') {
            $cur = getSetting($db, 'site_logo');
            if ($cur) {
                $f = $_SERVER['DOCUMENT_ROOT'] . $cur;
                if (file_exists($f)) unlink($f);
                updateSetting($db, 'site_logo', '');
                $message = 'Logo supprimé.'; $messageType = 'success';
            }
        }
        
        // Supprimer Favicon
        if (isset($_POST['delete_favicon']) && $_POST['delete_favicon'] === '1') {
            $cur = getSetting($db, 'site_favicon');
            if ($cur) {
                $f = $_SERVER['DOCUMENT_ROOT'] . $cur;
                if (file_exists($f)) unlink($f);
                updateSetting($db, 'site_favicon', '');
                $message = 'Favicon supprimé.'; $messageType = 'success';
            }
        }
        
        // Nom du site
        if (isset($_POST['site_name']) && trim($_POST['site_name'])) {
            updateSetting($db, 'site_name', trim($_POST['site_name']));
        }
        
        // Largeur logo
        if (isset($_POST['site_logo_width'])) {
            updateSetting($db, 'site_logo_width', trim($_POST['site_logo_width']));
        }
    }
}

// ── Récupérer valeurs ──
$siteLogo   = getSetting($db, 'site_logo');
$siteFavicon = getSetting($db, 'site_favicon');
$siteName   = getSetting($db, 'site_name', 'Eduardo De Sul');
$logoWidth  = getSetting($db, 'site_logo_width', '180');

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$pageTitle = 'Identité du site';
include __DIR__ . '/../../includes/header.php';
?>

<div class="admin-content">
    <div class="content-header">
        <div>
            <h1><i class="fas fa-palette"></i> Identité du site</h1>
            <p style="color:#64748b;margin-top:4px;">Logo, favicon et nom du site</p>
        </div>
    </div>

    <?php if ($message): ?>
    <div style="padding:14px 20px;border-radius:12px;margin-bottom:20px;display:flex;align-items:center;gap:10px;font-weight:500;
        <?= $messageType === 'success' ? 'background:#d1fae5;color:#047857;border:1px solid #a7f3d0;' : 'background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;' ?>">
        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
        <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="max-width:900px;display:flex;flex-direction:column;gap:24px;">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- ═══ Nom du site ═══ -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            <div style="padding:18px 24px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
                <h3 style="font-size:1rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:10px;margin:0;">
                    <i class="fas fa-heading" style="color:#6C5CE7;"></i> Nom du site
                </h3>
                <span style="font-size:0.75rem;padding:4px 12px;border-radius:20px;background:#ede9fe;color:#6C5CE7;font-weight:500;">Affiché si pas de logo</span>
            </div>
            <div style="padding:24px;">
                <label style="display:block;font-size:0.85rem;font-weight:600;color:#334155;margin-bottom:8px;">Nom affiché</label>
                <input type="text" name="site_name" value="<?= htmlspecialchars($siteName) ?>" 
                       style="width:100%;padding:12px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:0.95rem;font-family:inherit;">
                <small style="display:block;color:#94a3b8;font-size:0.8rem;margin-top:6px;">Ce nom remplace le logo si aucune image n'est uploadée</small>
            </div>
        </div>

        <!-- ═══ Logo ═══ -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            <div style="padding:18px 24px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
                <h3 style="font-size:1rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:10px;margin:0;">
                    <i class="fas fa-image" style="color:#6C5CE7;"></i> Logo du site
                </h3>
                <span style="font-size:0.75rem;padding:4px 12px;border-radius:20px;background:#dbeafe;color:#1d4ed8;font-weight:500;">PNG, JPG, SVG, WebP - Max 2 Mo</span>
            </div>
            <div style="padding:24px;">
                <?php if ($siteLogo && file_exists($_SERVER['DOCUMENT_ROOT'] . $siteLogo)): ?>
                    <div style="display:flex;align-items:center;gap:20px;padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
                        <div style="background:#f1f5f9;padding:20px;border-radius:12px;">
                            <img src="<?= htmlspecialchars($siteLogo) ?>" alt="Logo" style="max-width:300px;max-height:120px;object-fit:contain;">
                        </div>
                        <div style="flex:1;display:flex;flex-direction:column;gap:10px;">
                            <code style="font-size:0.8rem;color:#64748b;background:#fff;padding:4px 10px;border-radius:6px;border:1px solid #e2e8f0;"><?= htmlspecialchars($siteLogo) ?></code>
                            <div style="display:flex;gap:8px;">
                                <label for="site_logo" style="padding:6px 14px;font-size:0.8rem;border-radius:8px;background:#6C5CE7;color:#fff;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                                    <i class="fas fa-sync-alt"></i> Remplacer
                                </label>
                                <button type="submit" name="delete_logo" value="1" onclick="return confirm('Supprimer le logo ?')"
                                    style="padding:6px 14px;font-size:0.8rem;border-radius:8px;background:#ef4444;color:#fff;font-weight:600;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <label for="site_logo" style="display:flex;flex-direction:column;align-items:center;padding:40px 24px;border:2px dashed #cbd5e1;border-radius:12px;background:#f8fafc;cursor:pointer;text-align:center;transition:all 0.3s;">
                        <i class="fas fa-cloud-upload-alt" style="font-size:2.5rem;color:#94a3b8;margin-bottom:12px;"></i>
                        <span style="font-size:1rem;font-weight:600;color:#334155;">Cliquer pour uploader un logo</span>
                        <span style="font-size:0.8rem;color:#94a3b8;margin-top:4px;">PNG, JPG, SVG ou WebP — Max 2 Mo</span>
                        <span style="margin-top:12px;font-size:0.8rem;color:#6C5CE7;background:#ede9fe;padding:6px 14px;border-radius:8px;">
                            <i class="fas fa-info-circle"></i> Sans logo, le nom du site sera affiché en texte
                        </span>
                    </label>
                <?php endif; ?>
                <input type="file" id="site_logo" name="site_logo" accept="image/*" style="position:absolute;width:0;height:0;opacity:0;">
                
                <div style="margin-top:16px;">
                    <label style="display:block;font-size:0.85rem;font-weight:600;color:#334155;margin-bottom:8px;">Largeur du logo (px)</label>
                    <input type="number" name="site_logo_width" value="<?= htmlspecialchars($logoWidth) ?>" min="50" max="400" step="10"
                           style="width:200px;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:0.9rem;">
                </div>
            </div>
        </div>

        <!-- ═══ Favicon ═══ -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            <div style="padding:18px 24px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
                <h3 style="font-size:1rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:10px;margin:0;">
                    <i class="fas fa-star" style="color:#6C5CE7;"></i> Favicon
                </h3>
                <span style="font-size:0.75rem;padding:4px 12px;border-radius:20px;background:#fef3c7;color:#b45309;font-weight:500;">ICO, PNG, SVG - Max 512 Ko</span>
            </div>
            <div style="padding:24px;">
                <?php if ($siteFavicon && file_exists($_SERVER['DOCUMENT_ROOT'] . $siteFavicon)): ?>
                    <div style="display:flex;align-items:center;gap:20px;padding:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
                        <div style="background:repeating-conic-gradient(#e2e8f0 0% 25%,#fff 0% 50%) 50%/16px 16px;padding:16px;border-radius:12px;border:1px solid #e2e8f0;">
                            <img src="<?= htmlspecialchars($siteFavicon) ?>" alt="Favicon" style="width:64px;height:64px;object-fit:contain;">
                        </div>
                        <div style="flex:1;display:flex;flex-direction:column;gap:10px;">
                            <code style="font-size:0.8rem;color:#64748b;background:#fff;padding:4px 10px;border-radius:6px;border:1px solid #e2e8f0;"><?= htmlspecialchars($siteFavicon) ?></code>
                            <div style="display:flex;gap:8px;">
                                <label for="site_favicon" style="padding:6px 14px;font-size:0.8rem;border-radius:8px;background:#6C5CE7;color:#fff;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                                    <i class="fas fa-sync-alt"></i> Remplacer
                                </label>
                                <button type="submit" name="delete_favicon" value="1" onclick="return confirm('Supprimer le favicon ?')"
                                    style="padding:6px 14px;font-size:0.8rem;border-radius:8px;background:#ef4444;color:#fff;font-weight:600;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                                    <i class="fas fa-trash"></i> Supprimer
                                </button>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <label for="site_favicon" style="display:flex;flex-direction:column;align-items:center;padding:40px 24px;border:2px dashed #cbd5e1;border-radius:12px;background:#f8fafc;cursor:pointer;text-align:center;">
                        <i class="fas fa-star" style="font-size:2.5rem;color:#94a3b8;margin-bottom:12px;"></i>
                        <span style="font-size:1rem;font-weight:600;color:#334155;">Cliquer pour uploader un favicon</span>
                        <span style="font-size:0.8rem;color:#94a3b8;margin-top:4px;">ICO, PNG ou SVG — Max 512 Ko — Idéal : 32×32 ou 64×64 px</span>
                        <span style="margin-top:12px;font-size:0.8rem;color:#6C5CE7;background:#ede9fe;padding:6px 14px;border-radius:8px;">
                            <i class="fas fa-info-circle"></i> Sans favicon, aucune icône dans l'onglet du navigateur
                        </span>
                    </label>
                <?php endif; ?>
                <input type="file" id="site_favicon" name="site_favicon" accept=".ico,.png,.svg" style="position:absolute;width:0;height:0;opacity:0;">
            </div>
        </div>

        <!-- ═══ Aperçu Header ═══ -->
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            <div style="padding:18px 24px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border-bottom:1px solid #e2e8f0;">
                <h3 style="font-size:1rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:10px;margin:0;">
                    <i class="fas fa-eye" style="color:#6C5CE7;"></i> Aperçu du header
                </h3>
            </div>
            <div style="padding:24px;">
                <div style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;background:#fff;border-bottom:3px solid #e67e22;">
                        <div>
                            <?php if ($siteLogo && file_exists($_SERVER['DOCUMENT_ROOT'] . $siteLogo)): ?>
                                <img src="<?= htmlspecialchars($siteLogo) ?>" alt="<?= htmlspecialchars($siteName) ?>" 
                                     style="max-width:<?= intval($logoWidth) ?>px;height:auto;max-height:50px;">
                            <?php else: ?>
                                <span style="font-size:1.2rem;font-weight:700;color:#1e293b;"><?= htmlspecialchars($siteName) ?></span>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex;gap:20px;align-items:center;font-size:0.9rem;color:#475569;">
                            <span>Accueil</span>
                            <span>Acheter</span>
                            <span>Vendre</span>
                            <span>Estimer</span>
                            <span>Secteurs</span>
                            <span style="background:#e67e22;color:#fff;padding:8px 20px;border-radius:6px;font-weight:600;">Contact</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══ Sauvegarder ═══ -->
        <div style="display:flex;justify-content:flex-end;padding:8px 0;">
            <button type="submit" style="padding:14px 28px;font-size:1rem;border-radius:12px;border:none;cursor:pointer;font-weight:600;display:inline-flex;align-items:center;gap:8px;background:#10b981;color:#fff;box-shadow:0 4px 15px rgba(16,185,129,0.3);transition:all 0.2s;">
                <i class="fas fa-save"></i> Sauvegarder les modifications
            </button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>