<?php
/**
 * TAB: Avis Google My Business
 * Lecture des avis + Réponses (via API GMB si configurée)
 */

// Récupérer les avis
$reviews = [];
try {
    $reviews = $pdo->query("SELECT * FROM gmb_reviews ORDER BY review_date DESC")->fetchAll();
} catch (Exception $e) {}

$pending = array_filter($reviews, fn($r) => $r['reply_status'] === 'pending');
$replied = array_filter($reviews, fn($r) => $r['reply_status'] === 'replied');

// Stats
$totalReviews = count($reviews);
$avgRating = $totalReviews > 0 ? round(array_sum(array_map(fn($r) => $r['rating'], $reviews)) / $totalReviews, 1) : 0;
$ratingCounts = array_count_values(array_column($reviews, 'rating'));
?>

<!-- Stats Avis -->
<div style="display: grid; grid-template-columns: 250px 1fr; gap: 24px; margin-bottom: 24px;">
    <!-- Note globale -->
    <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); border-radius: 16px; padding: 24px; text-align: center;">
        <div style="font-size: 56px; font-weight: 800; color: #92400e;">
            <?php echo $avgRating ?: '-'; ?>
        </div>
        <div class="rating-stars" style="font-size: 24px; margin: 12px 0;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <i class="fas fa-star <?php echo $i <= round($avgRating) ? '' : 'empty'; ?>"></i>
            <?php endfor; ?>
        </div>
        <div style="font-size: 14px; color: #92400e; font-weight: 500;">
            <?php echo $totalReviews; ?> avis au total
        </div>
    </div>

    <!-- Distribution -->
    <div class="content-card">
        <div class="card-body">
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 16px;">Distribution des notes</h4>
            <?php for ($i = 5; $i >= 1; $i--): 
                $count = $ratingCounts[$i] ?? 0;
                $percent = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
            ?>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                <div style="width: 20px; font-size: 14px; font-weight: 600;"><?php echo $i; ?></div>
                <i class="fas fa-star" style="color: #fbbf24;"></i>
                <div style="flex: 1; height: 10px; background: #f1f5f9; border-radius: 5px; overflow: hidden;">
                    <div style="height: 100%; width: <?php echo $percent; ?>%; background: linear-gradient(90deg, #fbbf24, #f59e0b); border-radius: 5px;"></div>
                </div>
                <div style="width: 50px; font-size: 13px; color: #64748b; text-align: right;"><?php echo $count; ?></div>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- Actions -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div style="display: flex; gap: 8px;">
        <button class="btn btn-secondary btn-sm filter-btn active" data-filter="all">
            Tous (<?php echo $totalReviews; ?>)
        </button>
        <button class="btn btn-secondary btn-sm filter-btn" data-filter="pending">
            <i class="fas fa-clock"></i> À répondre (<?php echo count($pending); ?>)
        </button>
        <button class="btn btn-secondary btn-sm filter-btn" data-filter="replied">
            <i class="fas fa-check"></i> Répondus (<?php echo count($replied); ?>)
        </button>
    </div>
    <div style="display: flex; gap: 8px;">
        <button class="btn btn-secondary btn-sm" onclick="syncReviews()">
            <i class="fas fa-sync-alt"></i> Synchroniser
        </button>
        <a href="https://business.google.com" target="_blank" class="btn btn-sm" style="background: #4285f4; color: white;">
            <i class="fab fa-google"></i> Voir sur GMB
        </a>
    </div>
</div>

<?php if (empty($reviews)): ?>
<div class="content-card">
    <div class="card-body" style="text-align: center; padding: 60px;">
        <i class="fas fa-star" style="font-size: 48px; color: #e2e8f0; margin-bottom: 16px;"></i>
        <h3 style="color: #1e293b; margin-bottom: 8px;">Aucun avis synchronisé</h3>
        <p style="color: #64748b; margin-bottom: 20px;">Configurez votre connexion Google My Business pour récupérer vos avis</p>
        <button class="btn btn-primary" onclick="syncReviews()">
            <i class="fas fa-sync-alt"></i> Synchroniser les avis
        </button>
    </div>
</div>
<?php else: ?>

<!-- Avis à répondre en priorité -->
<?php if (!empty($pending)): ?>
<div style="margin-bottom: 32px;">
    <h3 style="font-size: 15px; font-weight: 600; color: #d97706; margin-bottom: 16px;">
        <i class="fas fa-exclamation-circle"></i> Avis en attente de réponse (<?php echo count($pending); ?>)
    </h3>
    <?php foreach ($pending as $review): ?>
    <div class="review-card pending" data-status="pending">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px;">
                    <?php echo strtoupper(substr($review['reviewer_name'] ?? 'A', 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($review['reviewer_name'] ?? 'Anonyme'); ?></div>
                    <div style="font-size: 12px; color: #64748b;"><?php echo date('d/m/Y', strtotime($review['review_date'])); ?></div>
                </div>
            </div>
            <div class="rating-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'empty'; ?>"></i>
                <?php endfor; ?>
            </div>
        </div>
        
        <div style="font-size: 14px; line-height: 1.6; color: #1e293b; margin-bottom: 16px; padding: 12px; background: #f8fafc; border-radius: 8px;">
            <?php echo nl2br(htmlspecialchars($review['comment'] ?? 'Aucun commentaire')); ?>
        </div>

        <!-- Formulaire réponse -->
        <form onsubmit="submitReply(event, <?php echo $review['id']; ?>)" style="background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
            <label style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 8px;">
                <i class="fas fa-reply"></i> Votre réponse
            </label>
            <textarea name="reply" class="form-control" style="min-height: 80px; margin-bottom: 12px;" placeholder="Rédigez une réponse professionnelle et personnalisée..." required></textarea>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; gap: 8px;">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="insertTemplate(this, <?php echo $review['rating']; ?>)">
                        <i class="fas fa-magic"></i> Modèle
                    </button>
                </div>
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fas fa-paper-plane"></i> Envoyer
                </button>
            </div>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Avis avec réponse -->
<?php if (!empty($replied)): ?>
<div>
    <h3 style="font-size: 15px; font-weight: 600; color: #16a34a; margin-bottom: 16px;">
        <i class="fas fa-check-circle"></i> Avis avec réponse (<?php echo count($replied); ?>)
    </h3>
    <?php foreach ($replied as $review): ?>
    <div class="review-card replied" data-status="replied">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, var(--primary), #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 18px;">
                    <?php echo strtoupper(substr($review['reviewer_name'] ?? 'A', 0, 1)); ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($review['reviewer_name'] ?? 'Anonyme'); ?></div>
                    <div style="font-size: 12px; color: #64748b;"><?php echo date('d/m/Y', strtotime($review['review_date'])); ?></div>
                </div>
            </div>
            <div class="rating-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'empty'; ?>"></i>
                <?php endfor; ?>
            </div>
        </div>
        
        <div style="font-size: 14px; line-height: 1.6; color: #1e293b; margin-bottom: 12px;">
            <?php echo nl2br(htmlspecialchars($review['comment'] ?? 'Aucun commentaire')); ?>
        </div>

        <!-- Réponse -->
        <div style="background: #dcfce7; border-radius: 8px; padding: 12px 16px; border-left: 3px solid #16a34a;">
            <div style="font-size: 11px; font-weight: 600; color: #16a34a; margin-bottom: 6px;">
                <i class="fas fa-store"></i> Votre réponse • <?php echo $review['reply_date'] ? date('d/m/Y', strtotime($review['reply_date'])) : ''; ?>
            </div>
            <div style="font-size: 13px; color: #1e293b;">
                <?php echo nl2br(htmlspecialchars($review['reply_text'])); ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<style>
.review-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
}
.review-card.pending { border-left: 4px solid #f59e0b; }
.review-card.replied { border-left: 4px solid #10b981; }
.filter-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
</style>

<script>
// Filtres
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const filter = this.dataset.filter;
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        document.querySelectorAll('.review-card').forEach(card => {
            if (filter === 'all' || card.dataset.status === filter) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
});

// Templates de réponse
const templates = {
    positive: `Merci beaucoup pour votre avis positif ! Nous sommes ravis d'avoir pu vous accompagner dans votre projet immobilier. Votre satisfaction est notre priorité. N'hésitez pas à nous recommander à votre entourage. À très bientôt !`,
    negative: `Nous vous remercions pour votre retour et sommes sincèrement désolés que votre expérience n'ait pas été à la hauteur de vos attentes. Nous prenons vos remarques très au sérieux et souhaitons comprendre ce qui s'est passé. N'hésitez pas à nous contacter directement pour en discuter.`
};

function insertTemplate(btn, rating) {
    const form = btn.closest('form');
    const textarea = form.querySelector('textarea');
    textarea.value = rating >= 4 ? templates.positive : templates.negative;
    textarea.focus();
}

async function submitReply(event, reviewId) {
    event.preventDefault();
    const form = event.target;
    const reply = form.querySelector('textarea').value;
    
    try {
        const result = await fetch('api/local-seo/reply-review.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: reviewId, reply: reply})
        }).then(r => r.json());
        
        if (result.success) {
            location.reload();
        } else {
            alert('Erreur: ' + result.error);
        }
    } catch (error) {
        alert('Erreur lors de l\'envoi');
    }
}

function syncReviews() {
    alert('La synchronisation avec Google My Business nécessite une configuration API. Allez dans Paramètres pour configurer.');
}
</script>