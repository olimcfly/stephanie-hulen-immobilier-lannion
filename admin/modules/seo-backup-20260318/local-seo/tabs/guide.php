<?php
/**
 * TAB: Guide Local
 * Annuaire public des commercants et artisans du quartier
 * Page SEO pour le référencement local
 */

// Catégories du guide (métiers de l'habitat)
$guideCategories = [
    'plombier' => ['name' => 'Plombiers', 'icon' => 'faucet', 'color' => '#0ea5e9'],
    'electricien' => ['name' => 'Électriciens', 'icon' => 'bolt', 'color' => '#f59e0b'],
    'chauffagiste' => ['name' => 'Chauffagistes', 'icon' => 'fire', 'color' => '#ef4444'],
    'peintre' => ['name' => 'Peintres', 'icon' => 'paint-roller', 'color' => '#8b5cf6'],
    'menuisier' => ['name' => 'Menuisiers', 'icon' => 'door-open', 'color' => '#92400e'],
    'serrurier' => ['name' => 'Serruriers', 'icon' => 'key', 'color' => '#64748b'],
    'jardinier' => ['name' => 'Jardiniers', 'icon' => 'leaf', 'color' => '#22c55e'],
    'demenageur' => ['name' => 'Déménageurs', 'icon' => 'truck-moving', 'color' => '#6366f1'],
    'nettoyage' => ['name' => 'Nettoyage', 'icon' => 'broom', 'color' => '#06b6d4'],
    'renovation' => ['name' => 'Rénovation', 'icon' => 'hammer', 'color' => '#d97706'],
    'cuisiniste' => ['name' => 'Cuisinistes', 'icon' => 'utensils', 'color' => '#ec4899'],
    'isolation' => ['name' => 'Isolation', 'icon' => 'temperature-low', 'color' => '#10b981'],
    'notaire' => ['name' => 'Notaires', 'icon' => 'balance-scale', 'color' => '#1e293b'],
    'banque' => ['name' => 'Banques', 'icon' => 'university', 'color' => '#0369a1'],
    'autre' => ['name' => 'Autres', 'icon' => 'star', 'color' => '#94a3b8']
];

// Récupérer les entrées du guide (partenaires visibles)
$guideEntries = [];
try {
    $guideEntries = $pdo->query("SELECT * FROM local_partners WHERE is_visible_in_guide = 1 ORDER BY category, company_name ASC")->fetchAll();
} catch (Exception $e) {}

// Stats
$totalEntries = count($guideEntries);
$categoriesUsed = array_unique(array_column($guideEntries, 'category'));
?>

<!-- Info Box -->
<div class="info-box" style="background: linear-gradient(135deg, rgba(34,197,94,0.1), rgba(16,185,129,0.1)); border-color: rgba(34,197,94,0.2);">
    <div class="icon-box"><i class="fas fa-book-open" style="color: #22c55e;"></i></div>
    <div style="flex: 1;">
        <h4>📖 Votre Guide Local des Artisans</h4>
        <p>
            Créez un annuaire public des artisans et commerçants de votre secteur. 
            Cette page sera publiée sur votre site et vous positionnera comme <strong>référent local</strong> 
            tout en améliorant votre SEO avec du contenu local pertinent.
        </p>
    </div>
    <?php if ($totalEntries > 0): ?>
    <a href="/guide-local" target="_blank" class="btn btn-success btn-sm" style="flex-shrink: 0;">
        <i class="fas fa-external-link-alt"></i> Voir la page publique
    </a>
    <?php endif; ?>
</div>

<!-- Stats -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;">
    <div class="stat-card" style="padding: 16px; display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; background: #dcfce7; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-store" style="color: #16a34a;"></i>
        </div>
        <div>
            <div style="font-size: 24px; font-weight: 700;"><?php echo $totalEntries; ?></div>
            <div style="font-size: 12px; color: #64748b;">Professionnels</div>
        </div>
    </div>
    <div class="stat-card" style="padding: 16px; display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; background: #dbeafe; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-th-large" style="color: #2563eb;"></i>
        </div>
        <div>
            <div style="font-size: 24px; font-weight: 700;"><?php echo count($categoriesUsed); ?></div>
            <div style="font-size: 12px; color: #64748b;">Catégories</div>
        </div>
    </div>
    <div class="stat-card" style="padding: 16px; display: flex; align-items: center; gap: 12px;">
        <div style="width: 40px; height: 40px; background: #f3e8ff; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-link" style="color: #9333ea;"></i>
        </div>
        <div>
            <div style="font-size: 24px; font-weight: 700;"><?php echo count(array_filter($guideEntries, fn($e) => !empty($e['our_link_on_their_site']))); ?></div>
            <div style="font-size: 12px; color: #64748b;">Avec backlink</div>
        </div>
    </div>
</div>

<!-- Filtres catégories -->
<div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px;">
    <button class="btn btn-secondary btn-sm filter-cat active" data-cat="all">Tous</button>
    <?php foreach ($guideCategories as $key => $cat): 
        $count = count(array_filter($guideEntries, fn($e) => $e['category'] === $key));
        if ($count > 0):
    ?>
    <button class="btn btn-secondary btn-sm filter-cat" data-cat="<?php echo $key; ?>">
        <i class="fas fa-<?php echo $cat['icon']; ?>"></i> <?php echo $cat['name']; ?> (<?php echo $count; ?>)
    </button>
    <?php endif; endforeach; ?>
</div>

<!-- Actions -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h3 style="font-size: 16px; font-weight: 600; margin: 0;">
        <i class="fas fa-list" style="color: var(--primary);"></i>
        Professionnels du guide
    </h3>
    <a href="?page=local-seo&tab=partners&action=new" class="btn btn-primary btn-sm">
        <i class="fas fa-plus"></i> Ajouter un professionnel
    </a>
</div>

<?php if (empty($guideEntries)): ?>
<div class="content-card">
    <div class="card-body" style="text-align: center; padding: 60px;">
        <i class="fas fa-store" style="font-size: 48px; color: #e2e8f0; margin-bottom: 16px;"></i>
        <h3 style="color: #1e293b; margin-bottom: 8px;">Votre guide local est vide</h3>
        <p style="color: #64748b; margin-bottom: 20px;">
            Ajoutez des partenaires et cochez "Afficher dans le Guide Local" pour les faire apparaître ici
        </p>
        <a href="?page=local-seo&tab=partners&action=new" class="btn btn-primary">
            <i class="fas fa-plus"></i> Ajouter un partenaire
        </a>
    </div>
</div>
<?php else: ?>
<div class="content-card">
    <table class="data-table">
        <thead>
            <tr>
                <th>Professionnel</th>
                <th>Catégorie</th>
                <th>Ville</th>
                <th>Contact</th>
                <th>Backlink</th>
                <th style="width: 100px;">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($guideEntries as $entry): 
                $cat = $guideCategories[$entry['category']] ?? $guideCategories['autre'];
            ?>
            <tr data-category="<?php echo $entry['category']; ?>">
                <td>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 36px; height: 36px; background: <?php echo $cat['color']; ?>22; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-<?php echo $cat['icon']; ?>" style="color: <?php echo $cat['color']; ?>; font-size: 14px;"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600;"><?php echo htmlspecialchars($entry['company_name']); ?></div>
                            <?php if (!empty($entry['contact_name'])): ?>
                            <div style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($entry['contact_name']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td>
                    <span style="font-size: 12px; padding: 4px 10px; background: <?php echo $cat['color']; ?>22; color: <?php echo $cat['color']; ?>; border-radius: 20px;">
                        <?php echo $cat['name']; ?>
                    </span>
                </td>
                <td style="font-size: 13px; color: #64748b;">
                    <?php echo htmlspecialchars($entry['city'] ?? '-'); ?>
                </td>
                <td style="font-size: 12px;">
                    <?php if (!empty($entry['phone'])): ?>
                    <div><i class="fas fa-phone" style="width: 14px; color: #64748b;"></i> <?php echo htmlspecialchars($entry['phone']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($entry['website_url'])): ?>
                    <div><a href="<?php echo htmlspecialchars($entry['website_url']); ?>" target="_blank" style="color: var(--primary);"><i class="fas fa-globe" style="width: 14px;"></i> Site web</a></div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (!empty($entry['our_link_on_their_site'])): ?>
                    <span style="color: #16a34a; font-size: 12px;"><i class="fas fa-check-circle"></i> Actif</span>
                    <?php else: ?>
                    <span style="color: #64748b; font-size: 12px;"><i class="fas fa-minus-circle"></i> Non</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="display: flex; gap: 6px;">
                        <a href="?page=local-seo&tab=partners&id=<?php echo $entry['id']; ?>" class="btn btn-icon btn-secondary btn-sm" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button class="btn btn-icon btn-sm" style="background: #fee2e2; color: #dc2626;" onclick="removeFromGuide(<?php echo $entry['id']; ?>)" title="Retirer du guide">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Conseils -->
<div style="margin-top: 40px; background: #f8fafc; border-radius: 12px; padding: 24px;">
    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">
        💡 Conseils pour un guide local efficace
    </h3>
    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
        <div style="background: white; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
            <h4 style="font-size: 14px; font-weight: 600; color: #16a34a; margin-bottom: 8px;">
                <i class="fas fa-check-circle"></i> À faire
            </h4>
            <ul style="font-size: 13px; color: #64748b; padding-left: 16px; margin: 0; line-height: 1.8;">
                <li>N'incluez que des professionnels de <strong>confiance</strong></li>
                <li>Ajoutez une description personnalisée</li>
                <li>Mentionnez les zones d'intervention</li>
                <li>Mettez à jour régulièrement</li>
                <li>Demandez-leur un backlink en retour !</li>
            </ul>
        </div>
        <div style="background: white; padding: 16px; border-radius: 10px; border: 1px solid #e2e8f0;">
            <h4 style="font-size: 14px; font-weight: 600; color: #dc2626; margin-bottom: 8px;">
                <i class="fas fa-times-circle"></i> À éviter
            </h4>
            <ul style="font-size: 13px; color: #64748b; padding-left: 16px; margin: 0; line-height: 1.8;">
                <li>Trop d'entrées (qualité > quantité)</li>
                <li>Des professionnels non testés</li>
                <li>Des informations obsolètes</li>
                <li>Copier d'autres annuaires</li>
                <li>Oublier de vérifier les coordonnées</li>
            </ul>
        </div>
    </div>
</div>

<!-- Avantages SEO -->
<div style="margin-top: 24px; background: linear-gradient(135deg, rgba(99,102,241,0.05), rgba(139,92,246,0.05)); border-radius: 12px; padding: 24px; border: 1px solid rgba(99,102,241,0.1);">
    <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 16px;">
        🚀 Pourquoi un guide local booste votre SEO ?
    </h3>
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
        <div style="text-align: center; padding: 16px;">
            <div style="font-size: 32px; margin-bottom: 8px;">📍</div>
            <strong style="font-size: 14px;">Contenu local</strong>
            <p style="font-size: 12px; color: #64748b; margin: 8px 0 0;">Google favorise les sites avec du contenu local pertinent</p>
        </div>
        <div style="text-align: center; padding: 16px;">
            <div style="font-size: 32px; margin-bottom: 8px;">🔗</div>
            <strong style="font-size: 14px;">Backlinks</strong>
            <p style="font-size: 12px; color: #64748b; margin: 8px 0 0;">Les partenaires peuvent vous mentionner sur leur site</p>
        </div>
        <div style="text-align: center; padding: 16px;">
            <div style="font-size: 32px; margin-bottom: 8px;">🤝</div>
            <strong style="font-size: 14px;">Confiance</strong>
            <p style="font-size: 12px; color: #64748b; margin: 8px 0 0;">Vous devenez le référent local pour vos clients</p>
        </div>
    </div>
</div>

<style>
.filter-cat.active { background: var(--primary); color: white; border-color: var(--primary); }
</style>

<script>
// Filtres catégories
document.querySelectorAll('.filter-cat').forEach(btn => {
    btn.addEventListener('click', function() {
        const cat = this.dataset.cat;
        document.querySelectorAll('.filter-cat').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        document.querySelectorAll('.data-table tbody tr').forEach(row => {
            if (cat === 'all' || row.dataset.category === cat) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
});

function removeFromGuide(id) {
    if (confirm('Retirer ce professionnel du guide public ?')) {
        fetch('api/local-seo/toggle-guide-visibility.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id, visible: 0})
        }).then(() => location.reload());
    }
}
</script>