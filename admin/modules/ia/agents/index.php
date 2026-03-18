<?php
/**
 * Module Agents IA - Générateurs de contenu
 * /admin/modules/agents/index.php
 */

$agents = [
    'immobilier' => [
        'label' => 'Immobilier', 'icon' => 'home', 'color' => '#6366f1',
        'agents' => [
            'annonce-vente' => [
                'name' => 'Générateur d\'annonce de vente',
                'description' => 'Création d\'annonces de vente personnalisées, attractives et optimisées pour la conversion.',
                'icon' => 'file-alt',
                'fields' => [
                    ['name' => 'type_bien', 'label' => 'Type de bien', 'type' => 'select', 'options' => ['Appartement','Maison','Villa','Studio','Loft','Duplex','Terrain','Local commercial','Immeuble']],
                    ['name' => 'surface', 'label' => 'Surface (m²)', 'type' => 'number', 'placeholder' => '85'],
                    ['name' => 'pieces', 'label' => 'Nombre de pièces', 'type' => 'number', 'placeholder' => '4'],
                    ['name' => 'chambres', 'label' => 'Chambres', 'type' => 'number', 'placeholder' => '3'],
                    ['name' => 'prix', 'label' => 'Prix (€)', 'type' => 'number', 'placeholder' => '350000'],
                    ['name' => 'ville', 'label' => 'Ville / Quartier', 'type' => 'text', 'placeholder' => 'Nantes Centre'],
                    ['name' => 'atouts', 'label' => 'Points forts (séparés par des virgules)', 'type' => 'textarea', 'placeholder' => 'Balcon, parking, cave, lumineux, proche transports...'],
                    ['name' => 'ton', 'label' => 'Ton souhaité', 'type' => 'select', 'options' => ['Professionnel','Chaleureux','Luxe','Dynamique','Sobre']],
                ]
            ],
            'enrichir-annonce' => [
                'name' => 'Enrichir une annonce existante',
                'description' => 'Amélioration et réécriture d\'annonces existantes pour les rendre plus percutantes.',
                'icon' => 'magic',
                'fields' => [
                    ['name' => 'annonce_actuelle', 'label' => 'Annonce actuelle', 'type' => 'textarea', 'placeholder' => 'Collez votre annonce ici...', 'rows' => 6],
                    ['name' => 'objectif', 'label' => 'Objectif', 'type' => 'select', 'options' => ['Plus percutante','Plus détaillée','Plus émotionnelle','Plus professionnelle','Optimisée SEO']],
                    ['name' => 'elements_ajouter', 'label' => 'Éléments à ajouter/mettre en avant', 'type' => 'textarea', 'placeholder' => 'Informations supplémentaires...'],
                ]
            ],
            'annonce-location' => [
                'name' => 'Générateur d\'annonce de location',
                'description' => 'Création d\'annonces de location professionnelles en quelques clics.',
                'icon' => 'key',
                'fields' => [
                    ['name' => 'type_bien', 'label' => 'Type de bien', 'type' => 'select', 'options' => ['Appartement','Maison','Studio','Chambre','Colocation','Local commercial']],
                    ['name' => 'surface', 'label' => 'Surface (m²)', 'type' => 'number', 'placeholder' => '45'],
                    ['name' => 'pieces', 'label' => 'Nombre de pièces', 'type' => 'number', 'placeholder' => '2'],
                    ['name' => 'loyer', 'label' => 'Loyer mensuel (€)', 'type' => 'number', 'placeholder' => '850'],
                    ['name' => 'charges', 'label' => 'Charges (€)', 'type' => 'number', 'placeholder' => '50'],
                    ['name' => 'ville', 'label' => 'Ville / Quartier', 'type' => 'text', 'placeholder' => 'Lyon 3ème'],
                    ['name' => 'meuble', 'label' => 'Meublé ?', 'type' => 'select', 'options' => ['Non meublé','Meublé','Partiellement meublé']],
                    ['name' => 'atouts', 'label' => 'Points forts', 'type' => 'textarea', 'placeholder' => 'Balcon, parking, cave...'],
                ]
            ],
        ]
    ],
    'commercial' => [
        'label' => 'Communication commerciale', 'icon' => 'envelope', 'color' => '#10b981',
        'agents' => [
            'email-commercial' => [
                'name' => 'Créer/améliorer un email commercial',
                'description' => 'Optimisation d\'emails de prospection, relance, suivi et closing.',
                'icon' => 'envelope-open-text',
                'fields' => [
                    ['name' => 'type_email', 'label' => 'Type d\'email', 'type' => 'select', 'options' => ['Prospection vendeur','Prospection acheteur','Relance','Suivi après visite','Proposition de mandat','Remerciement','Invitation événement']],
                    ['name' => 'contexte', 'label' => 'Contexte', 'type' => 'textarea', 'placeholder' => 'Décrivez la situation...'],
                    ['name' => 'destinataire', 'label' => 'Profil du destinataire', 'type' => 'text', 'placeholder' => 'Propriétaire de maison, 55 ans...'],
                    ['name' => 'objectif', 'label' => 'Objectif de l\'email', 'type' => 'text', 'placeholder' => 'Obtenir un RDV d\'estimation'],
                    ['name' => 'ton', 'label' => 'Ton', 'type' => 'select', 'options' => ['Professionnel','Chaleureux','Direct','Formel']],
                ]
            ],
            'courrier-postal' => [
                'name' => 'Créer/améliorer un courrier postal',
                'description' => 'Réécriture de courriers de prospection et de communication client.',
                'icon' => 'mail-bulk',
                'fields' => [
                    ['name' => 'type_courrier', 'label' => 'Type de courrier', 'type' => 'select', 'options' => ['Prospection vendeur','Prospection secteur','Carte de visite digitale','Invitation portes ouvertes','Annonce nouveau mandat','Vœux']],
                    ['name' => 'contexte', 'label' => 'Contexte', 'type' => 'textarea', 'placeholder' => 'Décrivez la situation et vos objectifs...'],
                    ['name' => 'zone', 'label' => 'Zone géographique', 'type' => 'text', 'placeholder' => 'Quartier, ville...'],
                    ['name' => 'accroche', 'label' => 'Accroche souhaitée (optionnel)', 'type' => 'text', 'placeholder' => 'Laissez vide pour génération automatique'],
                ]
            ],
        ]
    ],
    'reseaux' => [
        'label' => 'Réseaux sociaux', 'icon' => 'share-alt', 'color' => '#ec4899',
        'agents' => [
            'posts-facebook' => [
                'name' => 'Générateur de posts Facebook',
                'description' => 'Suggestions de publications prêtes à publier sur Facebook.',
                'icon' => 'facebook-f', 'icon_type' => 'fab',
                'fields' => [
                    ['name' => 'type_post', 'label' => 'Type de post', 'type' => 'select', 'options' => ['Nouveau bien','Témoignage client','Conseil immobilier','Actualité marché','Présentation équipe','Événement','Question engagement']],
                    ['name' => 'sujet', 'label' => 'Sujet principal', 'type' => 'text', 'placeholder' => 'Décrivez le sujet du post...'],
                    ['name' => 'cta', 'label' => 'Call-to-action souhaité', 'type' => 'select', 'options' => ['Contactez-nous','Découvrir le bien','Commentez','Partagez','Prenez RDV','En savoir plus']],
                    ['name' => 'nombre', 'label' => 'Nombre de variantes', 'type' => 'select', 'options' => ['1','3','5']],
                ]
            ],
            'posts-instagram' => [
                'name' => 'Générateur de posts Instagram',
                'description' => 'Posts optimisés avec hashtags et angles éditoriaux.',
                'icon' => 'instagram', 'icon_type' => 'fab',
                'fields' => [
                    ['name' => 'type_post', 'label' => 'Type de contenu', 'type' => 'select', 'options' => ['Photo bien','Carrousel','Reel/Vidéo','Story','Citation','Behind the scenes','Avant/Après']],
                    ['name' => 'sujet', 'label' => 'Sujet', 'type' => 'text', 'placeholder' => 'Décrivez le contenu...'],
                    ['name' => 'style', 'label' => 'Style', 'type' => 'select', 'options' => ['Inspirationnel','Informatif','Humoristique','Luxe','Authentique']],
                    ['name' => 'hashtags', 'label' => 'Inclure hashtags ?', 'type' => 'select', 'options' => ['Oui (15-20)','Oui (5-10)','Non']],
                ]
            ],
            'videos-youtube' => [
                'name' => 'Générateur de contenus YouTube',
                'description' => 'Idées de contenus vidéo, titres et scripts.',
                'icon' => 'youtube', 'icon_type' => 'fab',
                'fields' => [
                    ['name' => 'type_video', 'label' => 'Type de vidéo', 'type' => 'select', 'options' => ['Visite virtuelle','Conseil acheteur','Conseil vendeur','Présentation quartier','FAQ immobilier','Vlog agent','Témoignage']],
                    ['name' => 'duree', 'label' => 'Durée cible', 'type' => 'select', 'options' => ['Short (< 1 min)','Courte (2-5 min)','Moyenne (5-10 min)','Longue (10+ min)']],
                    ['name' => 'sujet', 'label' => 'Sujet/Thème', 'type' => 'text', 'placeholder' => 'Décrivez le sujet de la vidéo...'],
                    ['name' => 'output', 'label' => 'Générer', 'type' => 'select', 'options' => ['Titre + Description','Script complet','Plan détaillé','Tout']],
                ]
            ],
        ]
    ],
    'contenu' => [
        'label' => 'Contenu & SEO', 'icon' => 'newspaper', 'color' => '#f59e0b',
        'agents' => [
            'articles-blog' => [
                'name' => 'Générateur d\'articles de blog',
                'description' => 'Idées de sujets + rédaction complète d\'articles optimisés SEO.',
                'icon' => 'blog',
                'fields' => [
                    ['name' => 'type_article', 'label' => 'Type d\'article', 'type' => 'select', 'options' => ['Guide complet','Liste / Top X','Actualité marché','Conseil pratique','Étude de cas','Interview','Comparatif']],
                    ['name' => 'sujet', 'label' => 'Sujet', 'type' => 'text', 'placeholder' => 'Ex: Comment bien préparer sa maison pour la vente'],
                    ['name' => 'mots_cles', 'label' => 'Mots-clés SEO (séparés par virgules)', 'type' => 'text', 'placeholder' => 'vente maison, estimation, agent immobilier'],
                    ['name' => 'longueur', 'label' => 'Longueur', 'type' => 'select', 'options' => ['Court (500 mots)','Moyen (1000 mots)','Long (1500+ mots)']],
                    ['name' => 'ville', 'label' => 'Ville/Zone (pour SEO local)', 'type' => 'text', 'placeholder' => 'Nantes'],
                ]
            ],
        ]
    ],
    'image' => [
        'label' => 'Image professionnelle', 'icon' => 'user-tie', 'color' => '#8b5cf6',
        'agents' => [
            'presentation-pro' => [
                'name' => 'Générateur de présentation pro',
                'description' => 'Création d\'une bio claire et impactante pour pages pro, profils et sites.',
                'icon' => 'id-card',
                'fields' => [
                    ['name' => 'prenom_nom', 'label' => 'Prénom et Nom', 'type' => 'text', 'placeholder' => 'Jean Dupont'],
                    ['name' => 'poste', 'label' => 'Poste / Titre', 'type' => 'text', 'placeholder' => 'Agent immobilier indépendant'],
                    ['name' => 'experience', 'label' => 'Années d\'expérience', 'type' => 'number', 'placeholder' => '10'],
                    ['name' => 'specialites', 'label' => 'Spécialités', 'type' => 'text', 'placeholder' => 'Maisons de caractère, premier achat...'],
                    ['name' => 'zone', 'label' => 'Zone géographique', 'type' => 'text', 'placeholder' => 'Nantes et sa métropole'],
                    ['name' => 'valeurs', 'label' => 'Valeurs / Ce qui vous différencie', 'type' => 'textarea', 'placeholder' => 'Écoute, transparence, disponibilité...'],
                    ['name' => 'format', 'label' => 'Format', 'type' => 'select', 'options' => ['Bio courte (2-3 phrases)','Bio complète','LinkedIn','Site web','Tous formats']],
                ]
            ],
        ]
    ],
    'inspiration' => [
        'label' => 'Inspiration & Engagement', 'icon' => 'lightbulb', 'color' => '#06b6d4',
        'agents' => [
            'citations' => [
                'name' => 'Générateur de citations',
                'description' => 'Citations inspirantes adaptées à l\'univers immobilier et business.',
                'icon' => 'quote-right',
                'fields' => [
                    ['name' => 'theme', 'label' => 'Thème', 'type' => 'select', 'options' => ['Immobilier','Succès / Motivation','Maison / Foyer','Investissement','Entrepreneuriat','Service client','Persévérance']],
                    ['name' => 'style', 'label' => 'Style', 'type' => 'select', 'options' => ['Inspirationnel','Professionnel','Poétique','Humoristique','Motivant']],
                    ['name' => 'usage', 'label' => 'Usage prévu', 'type' => 'select', 'options' => ['Post réseaux sociaux','Signature email','Carte de visite','Site web','Présentation']],
                    ['name' => 'nombre', 'label' => 'Nombre de citations', 'type' => 'select', 'options' => ['3','5','10']],
                ]
            ],
        ]
    ],
];

$totalAgents = 0;
$totalCategories = count($agents);
foreach ($agents as $cat) $totalAgents += count($cat['agents']);

$selectedAgent = $_GET['agent'] ?? null;
$selectedAgentData = null;
$selectedCategory = null;
if ($selectedAgent) {
    foreach ($agents as $catKey => $cat) {
        if (isset($cat['agents'][$selectedAgent])) {
            $selectedAgentData = $cat['agents'][$selectedAgent];
            $selectedCategory = $cat;
            break;
        }
    }
}
?>

<style>
.ai-card{background:var(--surface);border:2px solid var(--border);border-radius:var(--radius-lg);padding:20px;transition:all .3s;cursor:pointer;overflow:hidden}
.ai-card:hover{border-color:var(--accent);transform:translateY(-4px);box-shadow:0 12px 24px rgba(79,70,229,.12)}
.ai-card-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0}
.ai-card-name{font-size:.9rem;font-weight:700;color:var(--text);margin-bottom:3px}
.ai-card-model{font-size:.65rem;padding:2px 7px;border-radius:4px;background:var(--accent-bg);color:var(--accent);font-weight:600}
.ai-card-desc{font-size:.8rem;color:var(--text-3);line-height:1.5;margin-bottom:14px}
.ai-detail-body{display:grid;grid-template-columns:1fr 1fr;gap:0}
.ai-detail-left{padding:22px;border-right:1px solid var(--border)}
.ai-detail-right{padding:22px;background:var(--surface-2)}
.ai-result-area{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);min-height:280px;padding:18px;position:relative}
.ai-result-content{white-space:pre-wrap;line-height:1.7;font-size:.85rem;color:var(--text);display:none}
.ai-result-actions{display:flex;gap:8px;margin-top:14px;padding-top:14px;border-top:1px solid var(--border);display:none}
.ai-spinner{display:none;text-align:center;padding:40px}
.ai-spinner.active{display:block}
.ai-spinner-ring{width:36px;height:36px;border:3px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:aispin .8s linear infinite;margin:0 auto 12px}
@keyframes aispin{to{transform:rotate(360deg)}}
.ai-cat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;flex-shrink:0}
.ai-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px}
.ai-stat{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius-lg);padding:22px;text-align:center;transition:all .2s}
.ai-stat:hover{transform:translateY(-2px);box-shadow:var(--shadow)}
.ai-stat-val{font-size:2rem;font-weight:800;color:var(--accent)}
.ai-stat-label{font-size:.78rem;color:var(--text-3);margin-top:2px}
@media(max-width:1024px){.ai-stats{grid-template-columns:repeat(2,1fr)}.ai-detail-body{grid-template-columns:1fr}.ai-detail-left{border-right:0;border-bottom:1px solid var(--border)}}
@media(max-width:768px){.ai-stats{grid-template-columns:1fr}}
</style>

<?php if ($selectedAgent && $selectedAgentData): ?>

<a href="?page=agents" class="mod-btn mod-btn-secondary mod-btn-sm" style="margin-bottom:14px"><i class="fas fa-arrow-left"></i> Retour aux agents</a>

<div class="mod-card">
    <div class="mod-card-header">
        <div class="mod-flex mod-items-center mod-gap">
            <div class="ai-card-icon" style="background:<?= $selectedCategory['color'] ?>;width:54px;height:54px;font-size:22px">
                <i class="<?= ($selectedAgentData['icon_type'] ?? 'fas') === 'fab' ? 'fab' : 'fas' ?> fa-<?= $selectedAgentData['icon'] ?>"></i>
            </div>
            <div>
                <h3 style="margin:0"><?= htmlspecialchars($selectedAgentData['name']) ?></h3>
                <span class="mod-text-sm mod-text-muted"><?= htmlspecialchars($selectedAgentData['description']) ?></span>
            </div>
        </div>
    </div>
    <div class="ai-detail-body">
        <div class="ai-detail-left">
            <div style="font-size:.85rem;font-weight:700;color:var(--text);margin-bottom:14px"><i class="fas fa-cog" style="color:var(--accent);margin-right:6px"></i>Configuration</div>
            <form id="agentForm" data-agent="<?= $selectedAgent ?>">
                <?php foreach ($selectedAgentData['fields'] as $f): ?>
                <div class="mod-form-group">
                    <label><?= htmlspecialchars($f['label']) ?></label>
                    <?php if ($f['type'] === 'select'): ?>
                    <select name="<?= $f['name'] ?>"><?php foreach ($f['options'] as $o): ?><option value="<?= htmlspecialchars($o) ?>"><?= htmlspecialchars($o) ?></option><?php endforeach; ?></select>
                    <?php elseif ($f['type'] === 'textarea'): ?>
                    <textarea name="<?= $f['name'] ?>" placeholder="<?= htmlspecialchars($f['placeholder'] ?? '') ?>" rows="<?= $f['rows'] ?? 3 ?>"></textarea>
                    <?php else: ?>
                    <input type="<?= $f['type'] ?>" name="<?= $f['name'] ?>" placeholder="<?= htmlspecialchars($f['placeholder'] ?? '') ?>">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <button type="submit" class="mod-btn mod-btn-primary" style="width:100%;margin-top:8px"><i class="fas fa-magic"></i> Générer le contenu</button>
            </form>
        </div>
        <div class="ai-detail-right">
            <div style="font-size:.85rem;font-weight:700;color:var(--text);margin-bottom:14px"><i class="fas fa-file-alt" style="color:var(--accent);margin-right:6px"></i>Résultat</div>
            <div class="ai-result-area" id="resultArea">
                <div class="ai-spinner" id="loadingSpinner">
                    <div class="ai-spinner-ring"></div>
                    <p class="mod-text-sm mod-text-muted">Génération en cours...</p>
                </div>
                <div class="mod-empty" id="resultPlaceholder" style="padding:50px 20px">
                    <i class="fas fa-robot"></i>
                    <p>Le contenu généré apparaîtra ici</p>
                </div>
                <div class="ai-result-content" id="resultContent"></div>
                <div class="ai-result-actions" id="resultActions">
                    <button class="mod-btn mod-btn-secondary mod-btn-sm" onclick="copyResult()"><i class="fas fa-copy"></i> Copier</button>
                    <button class="mod-btn mod-btn-secondary mod-btn-sm" onclick="regenerate()"><i class="fas fa-redo"></i> Régénérer</button>
                    <button class="mod-btn mod-btn-primary mod-btn-sm" onclick="saveResult()"><i class="fas fa-save"></i> Sauvegarder</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<div class="mod-hero">
    <div class="mod-hero-content">
        <h1><i class="fas fa-robot"></i> Agents IA</h1>
        <p>Générez des descriptions de biens, articles de blog, emails, posts réseaux sociaux et bien plus grâce à l'IA.</p>
    </div>
    <div class="mod-stats">
        <div class="mod-stat"><div class="mod-stat-value"><?= $totalAgents ?></div><div class="mod-stat-label">Agents</div></div>
        <div class="mod-stat"><div class="mod-stat-value"><?= $totalCategories ?></div><div class="mod-stat-label">Catégories</div></div>
    </div>
</div>

<div class="ai-stats">
    <div class="ai-stat"><div class="ai-stat-val"><?= $totalAgents ?></div><div class="ai-stat-label">Agents configurés</div></div>
    <div class="ai-stat"><div class="ai-stat-val"><?= $totalCategories ?></div><div class="ai-stat-label">Catégories</div></div>
    <div class="ai-stat"><div class="ai-stat-val">∞</div><div class="ai-stat-label">Contenus possibles</div></div>
    <div class="ai-stat"><div class="ai-stat-val">24/7</div><div class="ai-stat-label">Disponibilité</div></div>
</div>

<?php foreach ($agents as $catKey => $category): ?>
<div style="margin-bottom:28px">
    <div class="mod-flex mod-items-center mod-gap" style="margin-bottom:14px">
        <div class="ai-cat-icon" style="background:<?= $category['color'] ?>"><i class="fas fa-<?= $category['icon'] ?>"></i></div>
        <strong style="font-size:1.1rem;color:var(--text)"><?= htmlspecialchars($category['label']) ?></strong>
    </div>
    <div class="mod-grid mod-grid-3">
        <?php foreach ($category['agents'] as $agentKey => $agent): ?>
        <div class="ai-card" onclick="window.location.href='?page=agents&agent=<?= $agentKey ?>'">
            <div class="mod-flex mod-items-center mod-gap" style="margin-bottom:10px">
                <div class="ai-card-icon" style="background:<?= $category['color'] ?>">
                    <i class="<?= ($agent['icon_type'] ?? 'fas') === 'fab' ? 'fab' : 'fas' ?> fa-<?= $agent['icon'] ?>"></i>
                </div>
                <div>
                    <div class="ai-card-name"><?= htmlspecialchars($agent['name']) ?></div>
                    <span class="ai-card-model">Claude AI</span>
                </div>
            </div>
            <div class="ai-card-desc"><?= htmlspecialchars($agent['description']) ?></div>
            <div class="mod-flex mod-gap-sm">
                <a href="?page=agents&agent=<?= $agentKey ?>" class="mod-btn mod-btn-primary mod-btn-sm" style="flex:1" onclick="event.stopPropagation()"><i class="fas fa-play"></i> Utiliser</a>
                <button class="mod-btn-icon" onclick="event.stopPropagation()" title="Paramètres"><i class="fas fa-cog"></i></button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

<script>
const API_URL = '/admin/modules/agents/api.php';

<?php if ($selectedAgent): ?>
document.getElementById('agentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'generate');
    fd.append('agent', this.dataset.agent);
    document.getElementById('loadingSpinner').classList.add('active');
    document.getElementById('resultPlaceholder').style.display = 'none';
    document.getElementById('resultContent').style.display = 'none';
    document.getElementById('resultActions').style.display = 'none';
    try {
        const r = await fetch(API_URL, {method:'POST', body:fd});
        const d = await r.json();
        document.getElementById('loadingSpinner').classList.remove('active');
        if (d.success) {
            document.getElementById('resultContent').textContent = d.content;
            document.getElementById('resultContent').style.display = 'block';
            document.getElementById('resultActions').style.display = 'flex';
        } else {
            showNotif('Erreur: '+(d.error||'Génération échouée'), 'error');
            document.getElementById('resultPlaceholder').style.display = 'block';
        }
    } catch(err) {
        document.getElementById('loadingSpinner').classList.remove('active');
        document.getElementById('resultPlaceholder').style.display = 'block';
        showNotif('Erreur de connexion', 'error');
    }
});
function copyResult() {
    navigator.clipboard.writeText(document.getElementById('resultContent').textContent).then(() => showNotif('Copié !','success'));
}
function regenerate() { document.getElementById('agentForm').dispatchEvent(new Event('submit')); }
function saveResult() { showNotif('Fonctionnalité à venir','info'); }
<?php endif; ?>

function showNotif(msg, type='info') {
    const c = {success:'var(--green)', error:'var(--red)', info:'var(--accent)'};
    const n = document.createElement('div');
    n.style.cssText = `position:fixed;top:20px;right:20px;padding:14px 20px;background:${c[type]};color:#fff;border-radius:var(--radius);font-size:.85rem;font-weight:500;z-index:99999;box-shadow:var(--shadow-lg);transition:opacity .3s`;
    n.textContent = msg;
    document.body.appendChild(n);
    setTimeout(() => { n.style.opacity='0'; setTimeout(() => n.remove(), 300); }, 2500);
}
</script>