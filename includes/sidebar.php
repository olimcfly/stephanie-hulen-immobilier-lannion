<?php
/**
 * Admin Sidebar
 * /admin/includes/sidebar.php
 * 
 * Navigation latérale pour l'interface admin
 */

$currentAdmin = getCurrentAdmin() ?? [];
$adminName = $currentAdmin['name'] ?? 'Administrator';

?>
<aside class="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="logo">
            <i class="fas fa-home"></i>
            <span>Eduardo</span>
        </div>
        <button id="sidebarToggle" class="sidebar-toggle">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <!-- Main Section -->
        <div class="nav-section">
            <div style="padding: 0.75rem 1.5rem; font-size: 0.65rem; font-weight: 700; 
                        color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                Principal
            </div>
            
            <div class="nav-item active">
                <a href="/admin/index.php">
                    <i class="fas fa-home"></i>
                    <span class="nav-label">Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Content Section -->
        <div class="nav-section">
            <div style="padding: 0.75rem 1.5rem; font-size: 0.65rem; font-weight: 700; 
                        color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                Contenu
            </div>

            <!-- Pages -->
            <div class="nav-item">
                <a href="/admin/modules/pages/index.php" onclick="toggleSubmenu(this)">
                    <i class="fas fa-file-alt"></i>
                    <span class="nav-label">Pages</span>
                    <i class="arrow fas fa-chevron-down"></i>
                </a>
                <div class="submenu">
                    <a href="/admin/modules/pages/index.php" class="submenu-item">
                        <i class="fas fa-list"></i> Toutes les pages
                    </a>
                    <a href="/admin/modules/pages/create.php" class="submenu-item">
                        <i class="fas fa-plus"></i> Créer page
                    </a>
                </div>
            </div>

            <!-- Articles/Blog -->
            <div class="nav-item">
                <a href="/admin/modules/articles/index.php" onclick="toggleSubmenu(this)">
                    <i class="fas fa-newspaper"></i>
                    <span class="nav-label">Articles</span>
                    <i class="arrow fas fa-chevron-down"></i>
                </a>
                <div class="submenu">
                    <a href="/admin/modules/articles/index.php" class="submenu-item">
                        <i class="fas fa-list"></i> Tous les articles
                    </a>
                    <a href="/admin/modules/articles/create.php" class="submenu-item">
                        <i class="fas fa-plus"></i> Nouvel article
                    </a>
                    <a href="/admin/modules/articles/index.php?status=draft" class="submenu-item">
                        <i class="fas fa-edit"></i> Brouillons
                    </a>
                    <a href="/admin/modules/articles/index.php?status=published" class="submenu-item">
                        <i class="fas fa-check"></i> Publiés
                    </a>
                </div>
            </div>

            <!-- Capture Pages -->
            <div class="nav-item">
                <a href="/admin/modules/captures/index.php" onclick="toggleSubmenu(this)">
                    <i class="fas fa-bullseye"></i>
                    <span class="nav-label">Captures</span>
                    <i class="arrow fas fa-chevron-down"></i>
                </a>
                <div class="submenu">
                    <a href="/admin/modules/captures/index.php" class="submenu-item">
                        <i class="fas fa-list"></i> Pages de capture
                    </a>
                    <a href="/admin/modules/captures/create.php" class="submenu-item">
                        <i class="fas fa-plus"></i> Nouvelle capture
                    </a>
                    <a href="/admin/modules/captures/performance.php" class="submenu-item">
                        <i class="fas fa-chart-bar"></i> Performance
                    </a>
                </div>
            </div>
        </div>

        <!-- Lead Management Section -->
        <div class="nav-section">
            <div style="padding: 0.75rem 1.5rem; font-size: 0.65rem; font-weight: 700; 
                        color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                CRM
            </div>

            <!-- Leads -->
            <div class="nav-item">
                <a href="/admin/modules/leads/index.php" onclick="toggleSubmenu(this)">
                    <i class="fas fa-users"></i>
                    <span class="nav-label">Prospects</span>
                    <i class="arrow fas fa-chevron-down"></i>
                </a>
                <div class="submenu">
                    <a href="/admin/modules/leads/index.php" class="submenu-item">
                        <i class="fas fa-list"></i> Tous les prospects
                    </a>
                    <a href="/admin/modules/leads/index.php?status=new" class="submenu-item">
                        <i class="fas fa-star"></i> Nouveaux
                    </a>
                    <a href="/admin/modules/leads/index.php?status=qualified" class="submenu-item">
                        <i class="fas fa-check"></i> Qualifiés
                    </a>
                </div>
            </div>

            <!-- Contacts -->
            <div class="nav-item">
                <a href="/admin/modules/contacts/index.php" onclick="toggleSubmenu(this)">
                    <i class="fas fa-address-book"></i>
                    <span class="nav-label">Contacts</span>
                    <i class="arrow fas fa-chevron-down"></i>
                </a>
                <div class="submenu">
                    <a href="/admin/modules/contacts/index.php" class="submenu-item">
                        <i class="fas fa-list"></i> Tous les contacts
                    </a>
                    <a href="/admin/modules/contacts/import.php" class="submenu-item">
                        <i class="fas fa-upload"></i> Importer
                    </a>
                </div>
            </div>
        </div>

        <!-- Property Section -->
        <div class="nav-section">
            <div style="padding: 0.75rem 1.5rem; font-size: 0.65rem; font-weight: 700; 
                        color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                Immobilier
            </div>

            <!-- Properties -->
            <div class="nav-item">
                <a href="/admin/modules/properties/index.php" onclick="toggleSubmenu(this)">
                    <i class="fas fa-building"></i>
                    <span class="nav-label">Biens</span>
                    <i class="arrow fas fa-chevron-down"></i>
                </a>
                <div class="submenu">
                    <a href="/admin/modules/properties/index.php" class="submenu-item">
                        <i class="fas fa-list"></i> Tous les biens
                    </a>
                    <a href="/admin/modules/properties/create.php" class="submenu-item">
                        <i class="fas fa-plus"></i> Ajouter bien
                    </a>
                    <a href="/admin/modules/properties/index.php?status=available" class="submenu-item">
                        <i class="fas fa-check"></i> Disponibles
                    </a>
                </div>
            </div>
        </div>

        <!-- Tools Section -->
        <div class="nav-section">
            <div style="padding: 0.75rem 1.5rem; font-size: 0.65rem; font-weight: 700; 
                        color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                Outils
            </div>

            <!-- SEO Tools -->
            <div class="nav-item">
                <a href="/admin/modules/seo/index.php" onclick="toggleSubmenu(this)">
                    <i class="fas fa-search"></i>
                    <span class="nav-label">SEO</span>
                    <i class="arrow fas fa-chevron-down"></i>
                </a>
                <div class="submenu">
                    <a href="/admin/modules/seo/analyzer.php" class="submenu-item">
                        <i class="fas fa-chart-line"></i> Analyseur
                    </a>
                    <a href="/admin/modules/seo/keywords.php" class="submenu-item">
                        <i class="fas fa-key"></i> Mots-clés
                    </a>
                    <a href="/admin/modules/seo/sitemap.php" class="submenu-item">
                        <i class="fas fa-sitemap"></i> Sitemap
                    </a>
                </div>
            </div>

            <!-- Media Library -->
            <div class="nav-item">
                <a href="/admin/modules/media/index.php">
                    <i class="fas fa-images"></i>
                    <span class="nav-label">Médias</span>
                </a>
            </div>

            <!-- Analytics -->
            <div class="nav-item">
                <a href="/admin/modules/analytics/index.php">
                    <i class="fas fa-chart-bar"></i>
                    <span class="nav-label">Analytics</span>
                </a>
            </div>
        </div>

        <!-- Settings Section -->
        <div class="nav-section">
            <div style="padding: 0.75rem 1.5rem; font-size: 0.65rem; font-weight: 700; 
                        color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 0.5px;">
                Administration
            </div>

            <!-- Integrations -->
            <div class="nav-item">
                <a href="/admin/modules/integrations/index.php">
                    <i class="fas fa-plug"></i>
                    <span class="nav-label">Intégrations</span>
                </a>
            </div>

            <!-- Settings -->
            <div class="nav-item">
                <a href="/admin/modules/settings/index.php" onclick="toggleSubmenu(this)">
                    <i class="fas fa-cog"></i>
                    <span class="nav-label">Paramètres</span>
                    <i class="arrow fas fa-chevron-down"></i>
                </a>
                <div class="submenu">
                    <a href="/admin/modules/settings/general.php" class="submenu-item">
                        <i class="fas fa-sliders-h"></i> Général
                    </a>
                    <a href="/admin/modules/settings/users.php" class="submenu-item">
                        <i class="fas fa-user-cog"></i> Utilisateurs
                    </a>
                    <a href="/admin/modules/settings/security.php" class="submenu-item">
                        <i class="fas fa-shield-alt"></i> Sécurité
                    </a>
                    <a href="/admin/modules/settings/backup.php" class="submenu-item">
                        <i class="fas fa-save"></i> Sauvegarde
                    </a>
                </div>
            </div>

            <!-- Help & Docs -->
            <div class="nav-item">
                <a href="/admin/help/" target="_blank">
                    <i class="fas fa-question-circle"></i>
                    <span class="nav-label">Aide</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar" style="width: 40px; height: 40px; border-radius: 50%; 
                                           background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                           display: flex; align-items: center; justify-content: center;
                                           color: white; font-weight: bold; flex-shrink: 0;">
                <?php echo strtoupper(substr($adminName, 0, 1)); ?>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo escape($adminName); ?></div>
                <div class="user-email"><?php echo escape(substr($_SESSION['admin_email'] ?? '', 0, 20)); ?></div>
            </div>
        </div>
        <a href="/admin/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Déconnexion
        </a>
    </div>
</aside>

<style>
    .sidebar {
        width: 280px;
        background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        color: white;
        display: flex;
        flex-direction: column;
        box-shadow: 2px 0 8px rgba(0,0,0,0.1);
        position: sticky;
        top: 0;
        height: 100vh;
        overflow-y: auto;
        z-index: 1000;
        transition: all 0.3s;
    }

    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 1.125rem;
        font-weight: 700;
        color: white;
    }

    .logo i {
        font-size: 1.5rem;
        color: var(--primary);
    }

    .sidebar-nav {
        flex: 1;
        overflow-y: auto;
        padding: 1rem 0;
    }

    .nav-section {
        margin-bottom: 0.5rem;
    }

    .nav-item {
        position: relative;
    }

    .nav-item > a {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.875rem 1.5rem;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.3s;
    }

    .nav-item > a:hover {
        background: rgba(102, 126, 234, 0.2);
        color: white;
    }

    .nav-item.active > a {
        background: var(--primary);
        color: white;
        border-right: 3px solid var(--secondary);
    }

    .nav-item i:first-child {
        width: 20px;
        text-align: center;
    }

    .nav-label {
        flex: 1;
    }

    .arrow {
        font-size: 0.75rem;
        transition: transform 0.3s;
    }

    .nav-item.active .arrow {
        transform: rotate(180deg);
    }

    .submenu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .nav-item.active ~ .submenu {
        max-height: 500px;
    }

    .submenu-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1.5rem 0.75rem 3.5rem;
        color: rgba(255,255,255,0.6);
        text-decoration: none;
        font-size: 0.875rem;
        transition: all 0.3s;
    }

    .submenu-item:hover {
        color: white;
        background: rgba(102, 126, 234, 0.1);
        padding-left: 3.75rem;
    }

    .submenu-item i {
        width: 16px;
        font-size: 0.875rem;
    }

    .sidebar-footer {
        padding: 1.5rem;
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .user-details {
        flex: 1;
        min-width: 0;
    }

    .user-name {
        font-size: 0.875rem;
        font-weight: 600;
        color: white;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .user-email {
        color: rgba(255,255,255,0.6);
        font-size: 0.75rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .logout-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        background: rgba(239, 68, 68, 0.1);
        color: #fca5a5;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        width: 100%;
        transition: all 0.3s;
        cursor: pointer;
    }

    .logout-btn:hover {
        background: rgba(239, 68, 68, 0.2);
        color: #fecaca;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            transform: translateX(-100%);
            transition: transform 0.3s;
        }

        .sidebar.active {
            transform: translateX(0);
        }

        .sidebar-toggle {
            display: block;
        }
    }
</style>