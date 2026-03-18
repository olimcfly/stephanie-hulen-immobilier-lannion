<?php
/**
 * TAB: Publications Google My Business
 * Calendrier éditorial + Rappels par email
 */

$action = $_GET['action'] ?? null;
$editId = $_GET['id'] ?? null;

// Récupérer les publications
$publications = [];
try {
    $publications = $pdo->query("SELECT * FROM gmb_publications ORDER BY scheduled_date DESC")->fetchAll();
} catch (Exception $e) {}

// Grouper par statut
$scheduled = array_filter($publications, fn($p) => in_array($p['status'], ['draft', 'scheduled']));
$published = array_filter($publications, fn($p) => $p['status'] === 'published');

// Publication à éditer
$editPub = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM gmb_publications WHERE id = ?");
    $stmt->execute([$editId]);
    $editPub = $stmt->fetch();
}
?>

<?php if ($action === 'new' || $editPub): ?>
<!-- FORMULAIRE NOUVELLE PUBLICATION -->
<div class="content-card">
    <div class="card-header">
        <h3>
            <i class="fas fa-<?php echo $editPub ? 'edit' : 'plus'; ?>" style="color: var(--primary);"></i>
            <?php echo $editPub ? 'Modifier la publication' : 'Nouvelle publication GMB'; ?>
        </h3>
        <a href="?page=local-seo&tab=publications" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="api/local-seo/save-publication.php" id="pubForm">
            <input type="hidden" name="id" value="<?php echo $editPub['id'] ?? ''; ?>">
            
            <!-- Type de publication -->
            <div class="form-group">
                <label class="form-label">Type de publication *</label>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                    <label class="pub-type-card" data-type="update">
                        <input type="radio" name="type" value="update" <?php echo (!$editPub || $editPub['type'] === 'update') ? 'checked' : ''; ?> style="display:none;">
                        <div class="pub-type-icon" style="background: #dbeafe; color: #2563eb;">
                            <i class="fas fa-newspaper"></i>
                        </div>
                        <strong>Actualité</strong>
                        <span>Nouveau bien, conseil, témoignage...</span>
                    </label>
                    <label class="pub-type-card" data-type="event">
                        <input type="radio" name="type" value="event" <?php echo ($editPub && $editPub['type'] === 'event') ? 'checked' : ''; ?> style="display:none;">
                        <div class="pub-type-icon" style="background: #fef3c7; color: #d97706;">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <strong>Événement</strong>
                        <span>Portes ouvertes, webinaire...</span>
                    </label>
                    <label class="pub-type-card" data-type="offer">
                        <input type="radio" name="type" value="offer" <?php echo ($editPub && $editPub['type'] === 'offer') ? 'checked' : ''; ?> style="display:none;">
                        <div class="pub-type-icon" style="background: #dcfce7; color: #16a34a;">
                            <i class="fas fa-tags"></i>
                        </div>
                        <strong>Offre</strong>
                        <span>Estimation gratuite, promo...</span>
                    </label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Titre *</label>
                    <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($editPub['title'] ?? ''); ?>" placeholder="Ex: Nouveau bien exclusif à Bordeaux" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Date de publication *</label>
                    <input type="date" name="scheduled_date" class="form-control" value="<?php echo $editPub['scheduled_date'] ?? date('Y-m-d'); ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Contenu de la publication *</label>
                <textarea name="content" class="form-control" rows="5" placeholder="Rédigez votre publication... (1500 caractères max)" required><?php echo htmlspecialchars($editPub['content'] ?? ''); ?></textarea>
                <p class="form-help">Incluez un appel à l'action clair. Les hashtags et emojis sont autorisés.</p>
            </div>

            <!-- Champs événement -->
            <div id="eventFields" style="display: none; background: #fef3c7; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #92400e;">
                    <i class="fas fa-calendar-alt"></i> Détails de l'événement
                </h4>
                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Date de début</label>
                        <input type="date" name="event_start_date" class="form-control" value="<?php echo $editPub['event_start_date'] ?? ''; ?>">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Date de fin</label>
                        <input type="date" name="event_end_date" class="form-control" value="<?php echo $editPub['event_end_date'] ?? ''; ?>">
                    </div>
                </div>
            </div>

            <!-- Champs offre -->
            <div id="offerFields" style="display: none; background: #dcfce7; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 12px; color: #166534;">
                    <i class="fas fa-tags"></i> Détails de l'offre
                </h4>
                <div class="form-row">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Code promo (optionnel)</label>
                        <input type="text" name="offer_code" class="form-control" value="<?php echo htmlspecialchars($editPub['offer_code'] ?? ''); ?>" placeholder="Ex: ESTIM2024">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label">Lien de l'offre</label>
                        <input type="url" name="offer_url" class="form-control" value="<?php echo htmlspecialchars($editPub['offer_url'] ?? ''); ?>" placeholder="https://...">
                    </div>
                </div>
            </div>

            <!-- Bouton d'action -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Bouton d'action (CTA)</label>
                    <select name="cta_type" class="form-control">
                        <option value="none" <?php echo (!$editPub || $editPub['cta_type'] === 'none') ? 'selected' : ''; ?>>Aucun bouton</option>
                        <option value="learn_more" <?php echo ($editPub && $editPub['cta_type'] === 'learn_more') ? 'selected' : ''; ?>>En savoir plus</option>
                        <option value="book" <?php echo ($editPub && $editPub['cta_type'] === 'book') ? 'selected' : ''; ?>>Réserver</option>
                        <option value="call" <?php echo ($editPub && $editPub['cta_type'] === 'call') ? 'selected' : ''; ?>>Appeler</option>
                        <option value="sign_up" <?php echo ($editPub && $editPub['cta_type'] === 'sign_up') ? 'selected' : ''; ?>>S'inscrire</option>
                    </select>
                </div>
                <div class="form-group" id="ctaUrlField" style="display: none;">
                    <label class="form-label">URL du bouton</label>
                    <input type="url" name="cta_url" class="form-control" value="<?php echo htmlspecialchars($editPub['cta_url'] ?? ''); ?>" placeholder="https://...">
                </div>
            </div>

            <!-- Options -->
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="notify_email" value="1" checked>
                    <span>M'envoyer un rappel par email le jour de publication</span>
                </label>
            </div>

            <div class="form-group">
                <label class="form-label">Notes internes (non publiées)</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Rappels, idées, sources..."><?php echo htmlspecialchars($editPub['notes'] ?? ''); ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <a href="?page=local-seo&tab=publications" class="btn btn-secondary">Annuler</a>
                <button type="submit" name="status" value="draft" class="btn btn-secondary">
                    <i class="fas fa-save"></i> Enregistrer brouillon
                </button>
                <button type="submit" name="status" value="scheduled" class="btn btn-primary">
                    <i class="fas fa-calendar-check"></i> Planifier
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.pub-type-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}
.pub-type-card:hover { border-color: var(--primary); }
.pub-type-card:has(input:checked) { border-color: var(--primary); background: rgba(99,102,241,0.05); }
.pub-type-card .pub-type-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}
.pub-type-card strong { font-size: 14px; }
.pub-type-card span { font-size: 11px; color: #64748b; }
</style>

<script>
// Toggle fields based on type
document.querySelectorAll('input[name="type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('eventFields').style.display = this.value === 'event' ? 'block' : 'none';
        document.getElementById('offerFields').style.display = this.value === 'offer' ? 'block' : 'none';
    });
});

// Initial state
const checkedType = document.querySelector('input[name="type"]:checked');
if (checkedType) {
    document.getElementById('eventFields').style.display = checkedType.value === 'event' ? 'block' : 'none';
    document.getElementById('offerFields').style.display = checkedType.value === 'offer' ? 'block' : 'none';
}

// CTA URL field
document.querySelector('select[name="cta_type"]').addEventListener('change', function() {
    document.getElementById('ctaUrlField').style.display = this.value !== 'none' && this.value !== 'call' ? 'block' : 'none';
});
</script>

<?php else: ?>
<!-- LISTE DES PUBLICATIONS -->
<div class="info-box" style="background: linear-gradient(135deg, rgba(66,133,244,0.1), rgba(52,168,83,0.1)); border-color: rgba(66,133,244,0.2);">
    <div class="icon-box"><i class="fab fa-google" style="color: #4285f4;"></i></div>
    <div style="flex: 1;">
        <h4>Comment ça fonctionne ?</h4>
        <p>
            L'API Google ne permet plus de publier automatiquement sur GMB. 
            Planifiez vos publications ici, recevez un <strong>rappel par email</strong>, 
            puis publiez manuellement sur <a href="https://business.google.com" target="_blank" style="color: #4285f4;">Google My Business</a> 
            ou via <a href="https://buffer.com" target="_blank" style="color: #4285f4;">Buffer</a>.
        </p>
    </div>
    <a href="https://business.google.com" target="_blank" class="btn btn-sm" style="background: #4285f4; color: white; flex-shrink: 0;">
        <i class="fab fa-google"></i> Ouvrir GMB
    </a>
</div>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h3 style="font-size: 16px; font-weight: 600; margin: 0;">
        📅 Publications planifiées (<?php echo count($scheduled); ?>)
    </h3>
    <a href="?page=local-seo&tab=publications&action=new" class="btn btn-primary">
        <i class="fas fa-plus"></i> Nouvelle publication
    </a>
</div>

<?php if (empty($scheduled)): ?>
<div class="content-card">
    <div class="card-body" style="text-align: center; padding: 60px;">
        <i class="fas fa-calendar-plus" style="font-size: 48px; color: #e2e8f0; margin-bottom: 16px;"></i>
        <h3 style="color: #1e293b; margin-bottom: 8px;">Aucune publication planifiée</h3>
        <p style="color: #64748b; margin-bottom: 20px;">Créez votre première publication pour être actif sur Google My Business</p>
        <a href="?page=local-seo&tab=publications&action=new" class="btn btn-primary">
            <i class="fas fa-plus"></i> Créer une publication
        </a>
    </div>
</div>
<?php else: ?>
<div class="content-card">
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 100px;">Date</th>
                <th style="width: 100px;">Type</th>
                <th>Publication</th>
                <th style="width: 100px;">Statut</th>
                <th style="width: 150px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($scheduled as $pub): 
                $isOverdue = strtotime($pub['scheduled_date']) < strtotime('today');
            ?>
            <tr style="<?php echo $isOverdue ? 'background: #fef2f2;' : ''; ?>">
                <td>
                    <div style="font-weight: 600; <?php echo $isOverdue ? 'color: #dc2626;' : ''; ?>">
                        <?php echo date('d/m/Y', strtotime($pub['scheduled_date'])); ?>
                    </div>
                    <?php if ($isOverdue): ?>
                    <div style="font-size: 10px; color: #dc2626;">En retard !</div>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="pub-type <?php echo $pub['type']; ?>">
                        <i class="fas fa-<?php echo $pub['type'] === 'update' ? 'newspaper' : ($pub['type'] === 'event' ? 'calendar-alt' : 'tags'); ?>"></i>
                        <?php echo $pub['type'] === 'update' ? 'Actu' : ($pub['type'] === 'event' ? 'Event' : 'Offre'); ?>
                    </span>
                </td>
                <td>
                    <div style="font-weight: 500;"><?php echo htmlspecialchars($pub['title']); ?></div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 4px;">
                        <?php echo htmlspecialchars(substr($pub['content'], 0, 80)); ?>...
                    </div>
                </td>
                <td>
                    <span class="status-badge <?php echo $pub['status']; ?>">
                        <?php echo $pub['status'] === 'scheduled' ? 'Planifié' : 'Brouillon'; ?>
                    </span>
                </td>
                <td>
                    <div style="display: flex; gap: 6px;">
                        <a href="?page=local-seo&tab=publications&id=<?php echo $pub['id']; ?>" class="btn btn-icon btn-secondary btn-sm" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-icon btn-success btn-sm" onclick="markPublished(<?php echo $pub['id']; ?>)" title="Marquer comme publié">
                            <i class="fas fa-check"></i>
                        </button>
                        <a href="https://business.google.com" target="_blank" class="btn btn-icon btn-sm" style="background: #4285f4; color: white;" title="Publier sur GMB">
                            <i class="fab fa-google"></i>
                        </a>
                        <button class="btn btn-icon btn-sm" style="background: #fee2e2; color: #dc2626;" onclick="deletePub(<?php echo $pub['id']; ?>)" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Historique -->
<?php if (!empty($published)): ?>
<div style="margin-top: 40px;">
    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">
        ✅ Historique des publications (<?php echo count($published); ?>)
    </h3>
    <div class="content-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Publication</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_slice($published, 0, 10) as $pub): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($pub['scheduled_date'])); ?></td>
                    <td>
                        <span class="pub-type <?php echo $pub['type']; ?>">
                            <?php echo $pub['type'] === 'update' ? 'Actu' : ($pub['type'] === 'event' ? 'Event' : 'Offre'); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($pub['title']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Idées -->
<div style="margin-top: 40px; background: #f8fafc; border-radius: 12px; padding: 24px;">
    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">
        💡 Idées de publications pour agents immobiliers
    </h3>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
        <div style="background: white; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
            <div class="pub-type update" style="margin-bottom: 10px;">Actualité</div>
            <ul style="font-size: 12px; color: #64748b; padding-left: 16px; margin: 0; line-height: 1.8;">
                <li>Nouveau bien à la vente</li>
                <li>Vente réalisée + témoignage</li>
                <li>Conseil immobilier de la semaine</li>
                <li>Analyse du marché local</li>
                <li>Présentation de votre équipe</li>
            </ul>
        </div>
        <div style="background: white; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
            <div class="pub-type event" style="margin-bottom: 10px;">Événement</div>
            <ul style="font-size: 12px; color: #64748b; padding-left: 16px; margin: 0; line-height: 1.8;">
                <li>Journée portes ouvertes</li>
                <li>Webinaire acheteurs/vendeurs</li>
                <li>Présence sur un salon</li>
                <li>Visite virtuelle live</li>
                <li>Permanence en agence</li>
            </ul>
        </div>
        <div style="background: white; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
            <div class="pub-type offer" style="margin-bottom: 10px;">Offre</div>
            <ul style="font-size: 12px; color: #64748b; padding-left: 16px; margin: 0; line-height: 1.8;">
                <li>Estimation gratuite offerte</li>
                <li>Frais d'agence réduits</li>
                <li>Pack home staging offert</li>
                <li>Consultation financement gratuite</li>
                <li>Bonus parrainage</li>
            </ul>
        </div>
    </div>
</div>

<script>
function markPublished(id) {
    if (confirm('Marquer cette publication comme effectuée ?')) {
        fetch('api/local-seo/mark-published.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id})
        }).then(() => location.reload());
    }
}

function deletePub(id) {
    if (confirm('Supprimer cette publication ?')) {
        fetch('api/local-seo/delete-publication.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id})
        }).then(() => location.reload());
    }
}
</script>
<?php endif; ?>