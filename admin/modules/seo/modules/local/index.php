<?php
/**
 * MODULE: Mon Référencement Local
 * ===============================
 * - Publications GMB (calendrier + notifications email)
 * - Avis Google (lecture + réponses)
 * - Questions/Réponses GMB
 * - Partenaires Locaux (échange liens)
 * - Guide Local (annuaire public artisans)
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once(__DIR__ . '/../../../../config/config.php');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (Exception $e) {
    die('<div class="alert alert-danger">Erreur de connexion à la base de données</div>');
}

$tab = $_GET['tab'] ?? 'overview';
$action = $_GET['action'] ?? null;

// Stats
$stats = [
    'publications_pending' => 0,
    'publications_this_month' => 0,
    'reviews_total' => 0,
    'reviews_pending' => 0,
    'reviews_avg' => 0,
    'questions_pending' => 0,
    'partners_active' => 0,
    'partners_with_link' => 0,
    'guide_entries' => 0
];

try {
    $stats['publications_pending'] = $pdo->query("SELECT COUNT(*) FROM gmb_publications WHERE status IN ('draft', 'scheduled')")->fetchColumn() ?: 0;
    $stats['publications_this_month'] = $pdo->query("SELECT COUNT(*) FROM gmb_publications WHERE MONTH(scheduled_date) = MONTH(CURRENT_DATE())")->fetchColumn() ?: 0;
    $stats['reviews_total'] = $pdo->query("SELECT COUNT(*) FROM gmb_reviews")->fetchColumn() ?: 0;
    $stats['reviews_pending'] = $pdo->query("SELECT COUNT(*) FROM gmb_reviews WHERE reply_status = 'pending'")->fetchColumn() ?: 0;
    $stats['reviews_avg'] = round($pdo->query("SELECT AVG(rating) FROM gmb_reviews")->fetchColumn() ?: 0, 1);
    $stats['questions_pending'] = $pdo->query("SELECT COUNT(*) FROM gmb_questions WHERE answer_status = 'pending'")->fetchColumn() ?: 0;
    $stats['partners_active'] = $pdo->query("SELECT COUNT(*) FROM local_partners WHERE link_status = 'active'")->fetchColumn() ?: 0;
    $stats['partners_with_link'] = $pdo->query("SELECT COUNT(*) FROM local_partners WHERE our_link_on_their_site IS NOT NULL AND our_link_on_their_site != ''")->fetchColumn() ?: 0;
    $stats['guide_entries'] = $pdo->query("SELECT COUNT(*) FROM local_partners WHERE is_visible_in_guide = 1")->fetchColumn() ?: 0;
} catch (Exception $e) {
    // Tables pas encore créées
}
?>

<style>
.local-module { --primary: #6366f1; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --info: #0ea5e9; }

.local-tabs {
    display: flex;
    gap: 4px;
    background: #f1f5f9;
    padding: 6px;
    border-radius: 12px;
    margin-bottom: 24px;
    overflow-x: auto;
}

.local-tab {
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    transition: all 0.2s;
}

.local-tab:hover { background: white; color: #1e293b; }
.local-tab.active { background: white; color: var(--primary); box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
.local-tab .badge { background: var(--danger); color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; }

.stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s;
}

.stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }

.stat-card .icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 20px;
}

.stat-card .icon.blue { background: #dbeafe; color: #2563eb; }
.stat-card .icon.green { background: #dcfce7; color: #16a34a; }
.stat-card .icon.yellow { background: #fef3c7; color: #d97706; }
.stat-card .icon.purple { background: #f3e8ff; color: #9333ea; }
.stat-card .icon.cyan { background: #cffafe; color: #0891b2; }

.stat-card .value { font-size: 28px; font-weight: 800; color: #1e293b; }
.stat-card .label { font-size: 12px; color: #64748b; margin-top: 4px; }

.content-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
}

.content-card .card-header {
    padding: 16px 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.content-card .card-header h3 {
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.content-card .card-body { padding: 20px; }

.btn {
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
    text-decoration: none;
}

.btn-primary { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99,102,241,0.3); }
.btn-secondary { background: #f1f5f9; color: #1e293b; border: 1px solid #e2e8f0; }
.btn-success { background: var(--success); color: white; }
.btn-warning { background: var(--warning); color: white; }
.btn-danger { background: var(--danger); color: white; }
.btn-sm { padding: 6px 12px; font-size: 12px; }
.btn-icon { width: 34px; height: 34px; padding: 0; display: flex; align-items: center; justify-content: center; }

.info-box {
    background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(139,92,246,0.1));
    border: 1px solid rgba(99,102,241,0.2);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    display: flex;
    gap: 16px;
    align-items: flex-start;
}

.info-box .icon-box {
    width: 48px;
    height: 48px;
    background: white;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.info-box h4 { font-size: 15px; font-weight: 600; margin-bottom: 6px; }
.info-box p { font-size: 13px; color: #64748b; margin: 0; }

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 12px 16px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    color: #64748b;
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
}

.data-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: middle;
}

.data-table tr:hover { background: rgba(99,102,241,0.02); }

.form-group { margin-bottom: 20px; }
.form-label { display: block; font-weight: 600; font-size: 13px; margin-bottom: 8px; color: #1e293b; }
.form-control {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.2s;
}
.form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
textarea.form-control { min-height: 100px; resize: vertical; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-help { font-size: 12px; color: #64748b; margin-top: 6px; }

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.scheduled { background: #dbeafe; color: #2563eb; }
.status-badge.published { background: #dcfce7; color: #16a34a; }
.status-badge.draft { background: #f1f5f9; color: #64748b; }
.status-badge.pending { background: #fef3c7; color: #d97706; }

.pub-type {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.pub-type.update { background: #dbeafe; color: #2563eb; }
.pub-type.event { background: #fef3c7; color: #d97706; }
.pub-type.offer { background: #dcfce7; color: #16a34a; }

.rating-stars { color: #fbbf24; }
.rating-stars .empty { color: #e2e8f0; }

@media (max-width: 1200px) {
    .stats-grid { grid-template-columns: repeat(3, 1fr); }
    .form-row { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>

<div class="local-module">
    <!-- Tabs Navigation -->
    <div class="local-tabs">
        <a href="?page=local-seo&tab=overview" class="local-tab <?php echo $tab === 'overview' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Vue d'ensemble
        </a>
        <a href="?page=local-seo&tab=publications" class="local-tab <?php echo $tab === 'publications' ? 'active' : ''; ?>">
            <i class="fas fa-bullhorn"></i> Publications GMB
            <?php if ($stats['publications_pending'] > 0): ?>
            <span class="badge"><?php echo $stats['publications_pending']; ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=local-seo&tab=reviews" class="local-tab <?php echo $tab === 'reviews' ? 'active' : ''; ?>">
            <i class="fas fa-star"></i> Avis Google
            <?php if ($stats['reviews_pending'] > 0): ?>
            <span class="badge"><?php echo $stats['reviews_pending']; ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=local-seo&tab=questions" class="local-tab <?php echo $tab === 'questions' ? 'active' : ''; ?>">
            <i class="fas fa-question-circle"></i> Questions
            <?php if ($stats['questions_pending'] > 0): ?>
            <span class="badge"><?php echo $stats['questions_pending']; ?></span>
            <?php endif; ?>
        </a>
        <a href="?page=local-seo&tab=partners" class="local-tab <?php echo $tab === 'partners' ? 'active' : ''; ?>">
            <i class="fas fa-handshake"></i> Partenaires
        </a>
        <a href="?page=local-seo&tab=guide" class="local-tab <?php echo $tab === 'guide' ? 'active' : ''; ?>">
            <i class="fas fa-book"></i> Guide Local
        </a>
    </div>

    <?php if ($tab === 'overview'): ?>
    <!-- VUE D'ENSEMBLE -->
    <div class="info-box">
        <div class="icon-box">📍</div>
        <div>
            <h4>Référencement Local pour Agents Immobiliers</h4>
            <p>
                Optimisez votre présence locale sur Google : publications GMB régulières, 
                gestion des avis clients, réseau de partenaires locaux et guide des artisans. 
                Un référencement local fort = plus de mandats dans votre secteur.
            </p>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon blue"><i class="fas fa-bullhorn"></i></div>
            <div class="value"><?php echo $stats['publications_pending']; ?></div>
            <div class="label">Publications à faire</div>
        </div>
        <div class="stat-card">
            <div class="icon yellow"><i class="fas fa-star"></i></div>
            <div class="value"><?php echo $stats['reviews_avg'] ?: '-'; ?></div>
            <div class="label">Note moyenne (<?php echo $stats['reviews_total']; ?> avis)</div>
        </div>
        <div class="stat-card">
            <div class="icon green"><i class="fas fa-comment-dots"></i></div>
            <div class="value"><?php echo $stats['reviews_pending']; ?></div>
            <div class="label">Avis sans réponse</div>
        </div>
        <div class="stat-card">
            <div class="icon purple"><i class="fas fa-handshake"></i></div>
            <div class="value"><?php echo $stats['partners_active']; ?></div>
            <div class="label">Partenaires actifs</div>
        </div>
        <div class="stat-card">
            <div class="icon cyan"><i class="fas fa-link"></i></div>
            <div class="value"><?php echo $stats['partners_with_link']; ?></div>
            <div class="label">Backlinks obtenus</div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Actions rapides -->
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fas fa-bolt" style="color: #f59e0b;"></i> Actions rapides</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="?page=local-seo&tab=publications&action=new" class="btn btn-primary" style="justify-content: center;">
                        <i class="fas fa-plus"></i> Planifier une publication GMB
                    </a>
                    <a href="?page=local-seo&tab=partners&action=new" class="btn btn-secondary" style="justify-content: center;">
                        <i class="fas fa-user-plus"></i> Ajouter un partenaire local
                    </a>
                    <a href="?page=local-seo&tab=reviews" class="btn btn-secondary" style="justify-content: center;">
                        <i class="fas fa-reply"></i> Répondre aux avis (<?php echo $stats['reviews_pending']; ?>)
                    </a>
                    <a href="/guide-local" target="_blank" class="btn btn-secondary" style="justify-content: center;">
                        <i class="fas fa-external-link-alt"></i> Voir mon guide local public
                    </a>
                </div>
            </div>
        </div>

        <!-- Types de publications GMB -->
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fab fa-google" style="color: #4285f4;"></i> Les 3 types de publications GMB</h3>
            </div>
            <div class="card-body">
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; gap: 12px; padding: 12px; background: #f0f9ff; border-radius: 8px;">
                        <div style="width: 40px; height: 40px; background: #0ea5e9; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <div>
                            <strong style="font-size: 13px;">Actualité / Nouveauté</strong>
                            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0;">
                                Nouveau bien, vente réalisée, conseil immo, témoignage client...
                            </p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; padding: 12px; background: #fef3c7; border-radius: 8px;">
                        <div style="width: 40px; height: 40px; background: #f59e0b; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div>
                            <strong style="font-size: 13px;">Événement</strong>
                            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0;">
                                Portes ouvertes, webinaire, salon immo, permanence agence...
                            </p>
                        </div>
                    </div>
                    <div style="display: flex; gap: 12px; padding: 12px; background: #dcfce7; border-radius: 8px;">
                        <div style="width: 40px; height: 40px; background: #10b981; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div>
                            <strong style="font-size: 13px;">Offre promotionnelle</strong>
                            <p style="font-size: 12px; color: #64748b; margin: 4px 0 0;">
                                Estimation gratuite, frais offerts, bonus parrainage...
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Conseils -->
    <div class="content-card" style="margin-top: 20px;">
        <div class="card-header">
            <h3><i class="fas fa-lightbulb" style="color: #f59e0b;"></i> Bonnes pratiques référencement local</h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                <div>
                    <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #0ea5e9;">
                        <i class="fas fa-bullhorn"></i> Publications GMB
                    </h4>
                    <ul style="font-size: 13px; color: #64748b; padding-left: 20px; margin: 0; line-height: 1.8;">
                        <li>Publier <strong>2-3 fois par semaine</strong></li>
                        <li>Varier les types (actu, event, offre)</li>
                        <li>Ajouter des <strong>photos de qualité</strong></li>
                        <li>Inclure un <strong>appel à l'action</strong></li>
                    </ul>
                </div>
                <div>
                    <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #f59e0b;">
                        <i class="fas fa-star"></i> Avis clients
                    </h4>
                    <ul style="font-size: 13px; color: #64748b; padding-left: 20px; margin: 0; line-height: 1.8;">
                        <li>Répondre à <strong>100% des avis</strong></li>
                        <li>Répondre en <strong>moins de 24h</strong></li>
                        <li>Personnaliser chaque réponse</li>
                        <li>Solliciter les avis après chaque vente</li>
                    </ul>
                </div>
                <div>
                    <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 10px; color: #10b981;">
                        <i class="fas fa-link"></i> Échange de liens
                    </h4>
                    <ul style="font-size: 13px; color: #64748b; padding-left: 20px; margin: 0; line-height: 1.8;">
                        <li>Cibler les <strong>partenaires locaux</strong></li>
                        <li>Proposer un <strong>échange gagnant-gagnant</strong></li>
                        <li>Créer une page partenaires dédiée</li>
                        <li>Vérifier régulièrement les backlinks</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($tab === 'publications'): ?>
        <?php include __DIR__ . '/tabs/publications.php'; ?>
    
    <?php elseif ($tab === 'reviews'): ?>
        <?php include __DIR__ . '/tabs/reviews.php'; ?>
    
    <?php elseif ($tab === 'questions'): ?>
        <?php include __DIR__ . '/tabs/questions.php'; ?>
    
    <?php elseif ($tab === 'partners'): ?>
        <?php include __DIR__ . '/tabs/partners.php'; ?>
    
    <?php elseif ($tab === 'guide'): ?>
        <?php include __DIR__ . '/tabs/guide.php'; ?>
    
    <?php endif; ?>
</div>