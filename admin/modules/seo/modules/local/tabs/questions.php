<?php
/**
 * TAB: Questions/Réponses Google My Business
 * Gestion des Q&A de la fiche GMB
 */

// Récupérer les questions
$questions = [];
try {
    $questions = $pdo->query("SELECT * FROM gmb_questions ORDER BY question_date DESC")->fetchAll();
} catch (Exception $e) {}

$pending = array_filter($questions, fn($q) => $q['answer_status'] === 'pending');
$answered = array_filter($questions, fn($q) => $q['answer_status'] === 'answered');
?>

<!-- Info -->
<div class="info-box" style="background: linear-gradient(135deg, rgba(14,165,233,0.1), rgba(6,182,212,0.1)); border-color: rgba(14,165,233,0.2);">
    <div class="icon-box"><i class="fas fa-question-circle" style="color: #0ea5e9;"></i></div>
    <div>
        <h4>Questions & Réponses sur votre fiche Google</h4>
        <p>
            Les internautes peuvent poser des questions sur votre fiche Google My Business. 
            Répondez rapidement pour améliorer votre image et votre référencement local.
            <strong>Astuce :</strong> Vous pouvez aussi poser vous-même des questions fréquentes et y répondre !
        </p>
    </div>
</div>

<!-- Actions -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h3 style="font-size: 16px; font-weight: 600; margin: 0;">
        <i class="fas fa-comments" style="color: var(--info);"></i>
        Questions reçues
        <?php if (count($pending) > 0): ?>
        <span style="background: var(--danger); color: white; font-size: 11px; padding: 2px 8px; border-radius: 10px; margin-left: 8px;">
            <?php echo count($pending); ?> sans réponse
        </span>
        <?php endif; ?>
    </h3>
    <div style="display: flex; gap: 8px;">
        <button class="btn btn-secondary btn-sm" onclick="syncQuestions()">
            <i class="fas fa-sync-alt"></i> Synchroniser
        </button>
        <button class="btn btn-primary btn-sm" onclick="showAddFaq()">
            <i class="fas fa-plus"></i> Ajouter une FAQ
        </button>
    </div>
</div>

<?php if (empty($questions)): ?>
<div class="content-card">
    <div class="card-body" style="text-align: center; padding: 60px;">
        <i class="fas fa-question-circle" style="font-size: 48px; color: #e2e8f0; margin-bottom: 16px;"></i>
        <h3 style="color: #1e293b; margin-bottom: 8px;">Aucune question pour le moment</h3>
        <p style="color: #64748b; margin-bottom: 20px;">Les questions posées sur votre fiche Google apparaîtront ici</p>
        <div style="margin-top: 20px;">
            <p style="font-size: 13px; color: #64748b; margin-bottom: 12px;">
                💡 Conseil : Créez vous-même des questions fréquentes pour aider vos prospects
            </p>
            <button class="btn btn-primary" onclick="showAddFaq()">
                <i class="fas fa-plus"></i> Créer une FAQ
            </button>
        </div>
    </div>
</div>
<?php else: ?>

<!-- Questions sans réponse -->
<?php if (!empty($pending)): ?>
<div style="margin-bottom: 32px;">
    <h4 style="font-size: 14px; font-weight: 600; color: #d97706; margin-bottom: 16px;">
        <i class="fas fa-exclamation-triangle"></i> À répondre en priorité
    </h4>
    <?php foreach ($pending as $q): ?>
    <div class="question-card pending">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
            <div style="font-size: 12px; color: #64748b;">
                <i class="fas fa-user-circle"></i>
                <?php echo htmlspecialchars($q['asker_name'] ?? 'Internaute'); ?> • 
                <?php echo date('d/m/Y', strtotime($q['question_date'])); ?>
            </div>
        </div>
        <div style="font-size: 15px; font-weight: 500; color: #1e293b; margin-bottom: 16px; padding: 12px; background: #f8fafc; border-radius: 8px;">
            <strong style="color: var(--primary);">Q:</strong> <?php echo htmlspecialchars($q['question_text']); ?>
        </div>
        <form onsubmit="submitAnswer(event, <?php echo $q['id']; ?>)">
            <textarea name="answer" class="form-control" style="min-height: 80px;" placeholder="Rédigez votre réponse..." required></textarea>
            <div style="display: flex; justify-content: flex-end; margin-top: 12px;">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="fas fa-reply"></i> Répondre
                </button>
            </div>
        </form>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Questions avec réponse -->
<?php if (!empty($answered)): ?>
<div>
    <h4 style="font-size: 14px; font-weight: 600; color: #16a34a; margin-bottom: 16px;">
        <i class="fas fa-check-circle"></i> Questions avec réponse (<?php echo count($answered); ?>)
    </h4>
    <?php foreach ($answered as $q): ?>
    <div class="question-card answered">
        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
            <i class="fas fa-user-circle"></i>
            <?php echo htmlspecialchars($q['asker_name'] ?? 'Internaute'); ?> • 
            <?php echo date('d/m/Y', strtotime($q['question_date'])); ?>
        </div>
        <div style="font-size: 15px; font-weight: 500; color: #1e293b; margin-bottom: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
            <strong style="color: var(--primary);">Q:</strong> <?php echo htmlspecialchars($q['question_text']); ?>
        </div>
        <div style="font-size: 14px; color: #1e293b; padding: 12px; background: #dcfce7; border-radius: 8px; border-left: 3px solid #16a34a;">
            <strong style="color: #16a34a;">R:</strong> <?php echo nl2br(htmlspecialchars($q['answer_text'])); ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- FAQ suggérées -->
<div style="margin-top: 40px; background: #f8fafc; border-radius: 12px; padding: 24px;">
    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">
        💡 Questions fréquentes à ajouter sur votre fiche
    </h3>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
        <?php 
        $suggestedFaqs = [
            "Quels sont vos horaires d'ouverture ?",
            "Proposez-vous des estimations gratuites ?",
            "Travaillez-vous avec tous les notaires ?",
            "Comment se passe une visite de bien ?",
            "Quels sont vos honoraires ?",
            "Acceptez-vous les mandats exclusifs uniquement ?",
            "Pouvez-vous m'aider pour le financement ?",
            "Quelle est votre zone d'intervention ?"
        ];
        foreach ($suggestedFaqs as $faq): 
        ?>
        <div style="background: white; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-size: 13px;"><?php echo $faq; ?></span>
            <button class="btn btn-icon btn-secondary btn-sm" onclick="useSuggestedFaq('<?php echo htmlspecialchars(addslashes($faq)); ?>')" title="Utiliser">
                <i class="fas fa-plus"></i>
            </button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal FAQ -->
<div id="faqModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 500px; max-height: 90vh; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-size: 16px; font-weight: 600; margin: 0;">
                <i class="fas fa-question-circle" style="color: var(--primary);"></i> Ajouter une FAQ
            </h3>
            <button onclick="hideFaqModal()" style="background: none; border: none; cursor: pointer; font-size: 20px; color: #64748b;">×</button>
        </div>
        <form id="faqForm" onsubmit="submitFaq(event)" style="padding: 20px;">
            <div class="form-group">
                <label class="form-label">Question</label>
                <input type="text" name="question" id="faqQuestion" class="form-control" placeholder="Ex: Quels sont vos horaires ?" required>
            </div>
            <div class="form-group">
                <label class="form-label">Votre réponse</label>
                <textarea name="answer" class="form-control" placeholder="Rédigez une réponse claire et complète..." required></textarea>
            </div>
            <p style="font-size: 12px; color: #64748b; margin-bottom: 20px;">
                <i class="fas fa-info-circle"></i>
                Cette FAQ sera ajoutée à votre base locale. Pour l'afficher sur Google, vous devrez la poster manuellement sur votre fiche.
            </p>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" onclick="hideFaqModal()">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.question-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 12px;
}
.question-card.pending { border-left: 4px solid #f59e0b; }
.question-card.answered { border-left: 4px solid #10b981; }
</style>

<script>
function showAddFaq() {
    document.getElementById('faqModal').style.display = 'flex';
}

function hideFaqModal() {
    document.getElementById('faqModal').style.display = 'none';
}

function useSuggestedFaq(question) {
    document.getElementById('faqQuestion').value = question;
    showAddFaq();
}

async function submitAnswer(event, questionId) {
    event.preventDefault();
    const form = event.target;
    const answer = form.querySelector('textarea').value;
    
    try {
        const result = await fetch('api/local-seo/answer-question.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: questionId, answer: answer})
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

async function submitFaq(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
        const result = await fetch('api/local-seo/add-faq.php', {
            method: 'POST',
            body: formData
        }).then(r => r.json());
        
        if (result.success) {
            hideFaqModal();
            location.reload();
        } else {
            alert('Erreur: ' + result.error);
        }
    } catch (error) {
        alert('Erreur lors de l\'enregistrement');
    }
}

function syncQuestions() {
    alert('La synchronisation avec Google My Business nécessite une configuration API.');
}
</script>