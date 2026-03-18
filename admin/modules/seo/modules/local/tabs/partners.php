<?php
/**
 * TAB: Partenaires Locaux
 * Gestion des partenaires + Échanges de liens pour le SEO
 */

$action = $_GET['action'] ?? null;
$editId = $_GET['id'] ?? null;

// Catégories de partenaires
$categories = [
    'notaire' => ['name' => 'Notaire', 'icon' => 'balance-scale', 'color' => '#6366f1'],
    'banque' => ['name' => 'Banque', 'icon' => 'university', 'color' => '#0ea5e9'],
    'courtier' => ['name' => 'Courtier', 'icon' => 'hand-holding-usd', 'color' => '#10b981'],
    'assurance' => ['name' => 'Assurance', 'icon' => 'shield-alt', 'color' => '#ef4444'],
    'diagnostiqueur' => ['name' => 'Diagnostiqueur', 'icon' => 'clipboard-check', 'color' => '#f59e0b'],
    'architecte' => ['name' => 'Architecte', 'icon' => 'drafting-compass', 'color' => '#8b5cf6'],
    'demenageur' => ['name' => 'Déménageur', 'icon' => 'truck-moving', 'color' => '#06b6d4'],
    'artisan_renovation' => ['name' => 'Rénovation', 'icon' => 'hammer', 'color' => '#d97706'],
    'artisan_plomberie' => ['name' => 'Plombier', 'icon' => 'faucet', 'color' => '#2563eb'],
    'artisan_electricite' => ['name' => 'Électricien', 'icon' => 'bolt', 'color' => '#eab308'],
    'artisan_peinture' => ['name' => 'Peintre', 'icon' => 'paint-roller', 'color' => '#ec4899'],
    'jardinier_paysagiste' => ['name' => 'Jardinier', 'icon' => 'leaf', 'color' => '#22c55e'],
    'decoration' => ['name' => 'Décoration', 'icon' => 'couch', 'color' => '#f472b6'],
    'cuisiniste' => ['name' => 'Cuisiniste', 'icon' => 'utensils', 'color' => '#a855f7'],
    'autre' => ['name' => 'Autre', 'icon' => 'star', 'color' => '#64748b']
];

// Récupérer les partenaires
$partners = [];
try {
    $partners = $pdo->query("SELECT * FROM local_partners ORDER BY company_name ASC")->fetchAll();
} catch (Exception $e) {}

// Stats
$totalPartners = count($partners);
$activeLinks = count(array_filter($partners, fn($p) => !empty($p['our_link_on_their_site'])));

// Partenaire à éditer
$editPartner = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM local_partners WHERE id = ?");
    $stmt->execute([$editId]);
    $editPartner = $stmt->fetch();
}
?>

<?php if ($action === 'new' || $editPartner): ?>
<!-- FORMULAIRE -->
<div class="content-card">
    <div class="card-header">
        <h3>
            <i class="fas fa-<?php echo $editPartner ? 'edit' : 'user-plus'; ?>" style="color: var(--primary);"></i>
            <?php echo $editPartner ? 'Modifier le partenaire' : 'Nouveau partenaire'; ?>
        </h3>
        <a href="?page=local-seo&tab=partners" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="api/local-seo/save-partner.php">
            <input type="hidden" name="id" value="<?php echo $editPartner['id'] ?? ''; ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nom de l'entreprise *</label>
                    <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($editPartner['company_name'] ?? ''); ?>" placeholder="Ex: Maître Dupont - Notaire" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Catégorie *</label>
                    <select name="category" class="form-control" required>
                        <?php foreach ($categories as $key => $cat): ?>
                        <option value="<?php echo $key; ?>" <?php echo ($editPartner && $editPartner['category'] === $key) ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nom du contact</label>
                    <input type="text" name="contact_name" class="form-control" value="<?php echo htmlspecialchars($editPartner['contact_name'] ?? ''); ?>" placeholder="Prénom Nom">
                </div>
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editPartner['email'] ?? ''); ?>" placeholder="contact@exemple.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Téléphone</label>
                    <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($editPartner['phone'] ?? ''); ?>" placeholder="05 56 00 00 00">
                </div>
                <div class="form-group">
                    <label class="form-label">Site web</label>
                    <input type="url" name="website_url" class="form-control" value="<?php echo htmlspecialchars($editPartner['website_url'] ?? ''); ?>" placeholder="https://...">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Ville</label>
                    <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($editPartner['city'] ?? ''); ?>" placeholder="Bordeaux">
                </div>
                <div class="form-group">
                    <label class="form-label">Statut du partenariat</label>
                    <select name="link_status" class="form-control">
                        <option value="prospect" <?php echo ($editPartner && $editPartner['link_status'] === 'prospect') ? 'selected' : ''; ?>>Prospect</option>
                        <option value="contacted" <?php echo ($editPartner && $editPartner['link_status'] === 'contacted') ? 'selected' : ''; ?>>Contacté</option>
                        <option value="agreed" <?php echo ($editPartner && $editPartner['link_status'] === 'agreed') ? 'selected' : ''; ?>>Accord obtenu</option>
                        <option value="active" <?php echo ($editPartner && $editPartner['link_status'] === 'active') ? 'selected' : ''; ?>>Actif</option>
                        <option value="refused" <?php echo ($editPartner && $editPartner['link_status'] === 'refused') ? 'selected' : ''; ?>>Refusé</option>
                    </select>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;">
            <h4 style="font-size: 14px; font-weight: 600; margin-bottom: 16px;">
                <i class="fas fa-link" style="color: var(--primary);"></i> Échange de liens
            </h4>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">URL de leur lien vers vous (backlink)</label>
                    <input type="url" name="our_link_on_their_site" class="form-control" value="<?php echo htmlspecialchars($editPartner['our_link_on_their_site'] ?? ''); ?>" placeholder="https://leur-site.com/partenaires">
                    <p class="form-help">La page où ils ont mis un lien vers votre site</p>
                </div>
                <div class="form-group">
                    <label class="form-label">URL de votre lien vers eux</label>
                    <input type="url" name="their_link_on_our_site" class="form-control" value="<?php echo htmlspecialchars($editPartner['their_link_on_our_site'] ?? ''); ?>" placeholder="https://votre-site.com/partenaires">
                    <p class="form-help">La page de votre site où vous avez mis leur lien</p>
                </div>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="is_visible_in_guide" value="1" <?php echo ($editPartner && $editPartner['is_visible_in_guide']) ? 'checked' : ''; ?>>
                    <span>Afficher dans le Guide Local public</span>
                </label>
            </div>

            <div class="form-group">
                <label class="form-label">Notes internes</label>
                <textarea name="notes" class="form-control" rows="3" placeholder="Historique des échanges, rappels..."><?php echo htmlspecialchars($editPartner['notes'] ?? ''); ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                <a href="?page=local-seo&tab=partners" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<?php else: ?>
<!-- LISTE -->

<!-- Info Box -->
<div class="info-box">
    <div class="icon-box"><i class="fas fa-link" style="color: var(--primary);"></i></div>
    <div style="flex: 1;">
        <h4>🔗 Stratégie d'échange de liens (Netlinking local)</h4>
        <p>
            Les backlinks de partenaires locaux améliorent votre référencement sur Google. 
            Proposez à vos partenaires un échange : vous les mentionnez sur votre site, ils vous mentionnent sur le leur.
        </p>
    </div>
</div>

<!-- Stats -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
    <div class="stat-card" style="padding: 16px;">
        <div style="font-size: 28px; font-weight: 800; color: var(--primary);"><?php echo $totalPartners; ?></div>
        <div style="font-size: 12px; color: #64748b;">Partenaires</div>
    </div>
    <div class="stat-card" style="padding: 16px;">
        <div style="font-size: 28px; font-weight: 800; color: var(--success);"><?php echo $activeLinks; ?></div>
        <div style="font-size: 12px; color: #64748b;">Backlinks actifs</div>
    </div>
    <div class="stat-card" style="padding: 16px;">
        <div style="font-size: 28px; font-weight: 800; color: var(--warning);"><?php echo count(array_filter($partners, fn($p) => $p['link_status'] === 'contacted')); ?></div>
        <div style="font-size: 12px; color: #64748b;">En cours</div>
    </div>
    <div class="stat-card" style="padding: 16px;">
        <div style="font-size: 28px; font-weight: 800; color: var(--info);"><?php echo count(array_filter($partners, fn($p) => $p['is_visible_in_guide'])); ?></div>
        <div style="font-size: 12px; color: #64748b;">Dans le guide</div>
    </div>
</div>

<!-- Actions -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <button class="btn btn-secondary btn-sm filter-btn active" data-filter="all">Tous</button>
        <?php foreach (['active' => 'Actifs', 'contacted' => 'Contactés', 'prospect' => 'Prospects'] as $status => $label): ?>
        <button class="btn btn-secondary btn-sm filter-btn" data-filter="<?php echo $status; ?>"><?php echo $label; ?></button>
        <?php endforeach; ?>
    </div>
    <a href="?page=local-seo&tab=partners&action=new" class="btn btn-primary">
        <i class="fas fa-plus"></i> Ajouter un partenaire
    </a>
</div>

<?php if (empty($partners)): ?>
<div class="content-card">
    <div class="card-body" style="text-align: center; padding: 60px;">
        <i class="fas fa-handshake" style="font-size: 48px; color: #e2e8f0; margin-bottom: 16px;"></i>
        <h3 style="color: #1e293b; margin-bottom: 8px;">Aucun partenaire enregistré</h3>
        <p style="color: #64748b; margin-bottom: 20px;">Ajoutez vos partenaires locaux pour créer votre réseau</p>
        <a href="?page=local-seo&tab=partners&action=new" class="btn btn-primary">
            <i class="fas fa-plus"></i> Ajouter un partenaire
        </a>
    </div>
</div>
<?php else: ?>
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
    <?php foreach ($partners as $p): 
        $cat = $categories[$p['category']] ?? $categories['autre'];
    ?>
    <div class="partner-card" data-status="<?php echo $p['link_status']; ?>">
        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: <?php echo $cat['color']; ?>22; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-<?php echo $cat['icon']; ?>" style="color: <?php echo $cat['color']; ?>;"></i>
            </div>
            <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 600; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                    <?php echo htmlspecialchars($p['company_name']); ?>
                </div>
                <div style="font-size: 12px; color: #64748b;"><?php echo $cat['name']; ?></div>
            </div>
        </div>
        
        <?php if (!empty($p['city'])): ?>
        <div style="font-size: 12px; color: #64748b; margin-bottom: 8px;">
            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($p['city']); ?>
        </div>
        <?php endif; ?>
        
        <!-- Statut liens -->
        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 12px;">
            <span class="link-badge <?php echo !empty($p['our_link_on_their_site']) ? 'active' : 'none'; ?>">
                <i class="fas fa-arrow-left"></i> 
                <?php echo !empty($p['our_link_on_their_site']) ? 'Backlink ✓' : 'Pas de backlink'; ?>
            </span>
            <span class="link-badge <?php echo !empty($p['their_link_on_our_site']) ? 'active' : 'none'; ?>">
                <i class="fas fa-arrow-right"></i>
                <?php echo !empty($p['their_link_on_our_site']) ? 'Lien créé ✓' : 'Pas de lien'; ?>
            </span>
        </div>
        
        <div style="display: flex; gap: 6px;">
            <?php if (!empty($p['website_url'])): ?>
            <a href="<?php echo htmlspecialchars($p['website_url']); ?>" target="_blank" class="btn btn-icon btn-secondary btn-sm" title="Site web">
                <i class="fas fa-globe"></i>
            </a>
            <?php endif; ?>
            <?php if (!empty($p['email'])): ?>
            <a href="mailto:<?php echo htmlspecialchars($p['email']); ?>" class="btn btn-icon btn-secondary btn-sm" title="Email">
                <i class="fas fa-envelope"></i>
            </a>
            <?php endif; ?>
            <a href="?page=local-seo&tab=partners&id=<?php echo $p['id']; ?>" class="btn btn-icon btn-secondary btn-sm" title="Modifier">
                <i class="fas fa-edit"></i>
            </a>
            <button class="btn btn-icon btn-sm" style="background: #fee2e2; color: #dc2626;" onclick="deletePartner(<?php echo $p['id']; ?>)" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Template email -->
<div style="margin-top: 40px; background: #f8fafc; border-radius: 12px; padding: 24px;">
    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">
        📧 Modèle d'email pour proposer un échange de liens
    </h3>
    <div style="background: white; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0; font-size: 13px; line-height: 1.7;">
        <p><strong>Objet :</strong> Proposition de partenariat local - [Votre Agence]</p>
        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 12px 0;">
        <p>Bonjour [Prénom],</p>
        <p>Je me permets de vous contacter car nous intervenons tous les deux auprès d'une clientèle commune sur [Ville].</p>
        <p>Je suis [Votre nom], agent immobilier indépendant, et je recommande régulièrement vos services à mes clients.</p>
        <p>Je vous propose un partenariat gagnant-gagnant :</p>
        <ul style="padding-left: 20px;">
            <li>Je vous mentionne sur ma page "Partenaires" avec un lien vers votre site</li>
            <li>Vous me mentionnez sur votre site (si vous avez une page partenaires)</li>
        </ul>
        <p>C'est bon pour notre visibilité mutuelle sur Google !</p>
        <p>Bien cordialement,<br>[Votre signature]</p>
    </div>
    <button class="btn btn-secondary btn-sm" style="margin-top: 12px;" onclick="copyEmailTemplate()">
        <i class="fas fa-copy"></i> Copier le modèle
    </button>
</div>
<?php endif; ?>

<style>
.partner-card {
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    transition: all 0.2s;
}
.partner-card:hover { border-color: var(--primary); box-shadow: 0 4px 12px rgba(99,102,241,0.1); }
.link-badge {
    font-size: 10px;
    padding: 4px 8px;
    border-radius: 4px;
}
.link-badge.active { background: #dcfce7; color: #16a34a; }
.link-badge.none { background: #f1f5f9; color: #64748b; }
.filter-btn.active { background: var(--primary); color: white; border-color: var(--primary); }
</style>

<script>
// Filtres
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const filter = this.dataset.filter;
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        document.querySelectorAll('.partner-card').forEach(card => {
            if (filter === 'all' || card.dataset.status === filter) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    });
});

function deletePartner(id) {
    if (confirm('Supprimer ce partenaire ?')) {
        fetch('api/local-seo/delete-partner.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id})
        }).then(() => location.reload());
    }
}

function copyEmailTemplate() {
    const template = `Objet : Proposition de partenariat local - [Votre Agence]

Bonjour [Prénom],

Je me permets de vous contacter car nous intervenons tous les deux auprès d'une clientèle commune sur [Ville].

Je suis [Votre nom], agent immobilier indépendant, et je recommande régulièrement vos services à mes clients.

Je vous propose un partenariat gagnant-gagnant :
• Je vous mentionne sur ma page "Partenaires" avec un lien vers votre site
• Vous me mentionnez sur votre site (si vous avez une page partenaires)

C'est bon pour notre visibilité mutuelle sur Google !

Bien cordialement,
[Votre signature]`;
    
    navigator.clipboard.writeText(template).then(() => {
        alert('Modèle copié !');
    });
}
</script>