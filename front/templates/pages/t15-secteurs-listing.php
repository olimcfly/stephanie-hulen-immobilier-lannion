<?php
/**
 * T15 — Secteurs Listing Template
 * Affiche: Hero + Intro Trégor + Carte zone + Liste des secteurs
 *
 * Variables attendues du renderer :
 *   $secteurs      — array de secteurs publiés
 *   $totalSecteurs — int nombre total
 *   $villes        — array de villes distinctes
 *   $types         — array de types distincts [{type_secteur, cnt}]
 *   $typeSecteur   — filtre actif type
 *   $ville         — filtre actif ville
 *   $search        — filtre actif recherche
 *   $advisor       — config conseiller
 *   $editMode      — bool mode édition
 *   $fields        — champs éditables
 */

$fields      = $fields      ?? [];
$editMode    = $editMode    ?? false;
$advisor     = $advisor     ?? [];
$site        = $site        ?? [];
$secteurs    = $secteurs    ?? [];
$totalSecteurs = $totalSecteurs ?? 0;
$villes      = $villes      ?? [];
$types       = $types       ?? [];
$typeSecteur = $typeSecteur ?? '';
$ville       = $ville       ?? '';
$search      = $search      ?? '';

$heroTitle    = $fields['hero_title']    ?? 'Nos secteurs d\'intervention';
$heroSubtitle = $fields['hero_subtitle'] ?? 'Le Trégor et la Côte de Granit Rose';
$introText    = $fields['intro_text']    ?? 'Spécialiste de l\'immobilier dans le Trégor, nous vous accompagnons dans vos projets d\'achat, de vente ou d\'estimation sur l\'ensemble de la Côte de Granit Rose et son arrière-pays. De Lannion à Perros-Guirec, de Trébeurden à Pleumeur-Bodou, notre connaissance approfondie du territoire nous permet de vous offrir un accompagnement sur-mesure, au plus près de vos besoins.';

$advisorName = $advisor['advisor_name'] ?? 'Votre conseiller';
$advisorCity = $advisor['advisor_city'] ?? 'Lannion';
?>

<!-- ═══════════════════════════════════════════════════════
     HERO
     ═══════════════════════════════════════════════════════ -->
<section class="sl-hero">
    <div class="sl-hero__inner">
        <nav class="sl-breadcrumb" aria-label="Fil d'Ariane">
            <a href="/">Accueil</a>
            <span>/</span>
            <strong>Secteurs</strong>
        </nav>
        <h1 <?= $editMode ? 'data-field="hero_title" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroTitle) ?>
        </h1>
        <p class="sl-hero__subtitle" <?= $editMode ? 'data-field="hero_subtitle" class="ef-zone"' : '' ?>>
            <?= htmlspecialchars($heroSubtitle) ?>
        </p>
        <?php if ($totalSecteurs > 0): ?>
            <span class="sl-hero__count"><?= $totalSecteurs ?> secteur<?= $totalSecteurs > 1 ? 's' : '' ?></span>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     INTRO TRÉGOR + CARTE
     ═══════════════════════════════════════════════════════ -->
<section class="sl-intro section">
    <div class="container">
        <div class="sl-intro__grid">
            <!-- Texte d'introduction -->
            <div class="sl-intro__text">
                <h2>Notre zone d'intervention</h2>
                <p <?= $editMode ? 'data-field="intro_text" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($introText) ?>
                </p>
                <?php if ($totalSecteurs > 0): ?>
                    <div class="sl-intro__stats">
                        <div class="sl-stat">
                            <span class="sl-stat__number"><?= $totalSecteurs ?></span>
                            <span class="sl-stat__label">Secteurs couverts</span>
                        </div>
                        <?php if (count($villes) > 0): ?>
                        <div class="sl-stat">
                            <span class="sl-stat__number"><?= count($villes) ?></span>
                            <span class="sl-stat__label">Commune<?= count($villes) > 1 ? 's' : '' ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Carte OpenStreetMap du Trégor -->
            <div class="sl-intro__map">
                <div id="sl-map" class="sl-map"></div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     FILTRES
     ═══════════════════════════════════════════════════════ -->
<?php if (count($types) > 1 || count($villes) > 1 || $search): ?>
<section class="sl-filters">
    <div class="container">
        <form method="get" action="/secteurs" class="sl-filters__form">
            <?php if (count($types) > 1): ?>
            <div class="sl-filters__group">
                <label for="sl-type">Type</label>
                <select name="type" id="sl-type" onchange="this.form.submit()">
                    <option value="">Tous les types</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= htmlspecialchars($t['type_secteur']) ?>"
                            <?= $typeSecteur === $t['type_secteur'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($t['type_secteur'])) ?> (<?= $t['cnt'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (count($villes) > 1): ?>
            <div class="sl-filters__group">
                <label for="sl-ville">Commune</label>
                <select name="ville" id="sl-ville" onchange="this.form.submit()">
                    <option value="">Toutes les communes</option>
                    <?php foreach ($villes as $v): ?>
                        <option value="<?= htmlspecialchars($v) ?>"
                            <?= $ville === $v ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="sl-filters__group sl-filters__group--search">
                <label for="sl-search">Recherche</label>
                <input type="text" name="q" id="sl-search"
                       placeholder="Rechercher un secteur..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>

            <?php if ($typeSecteur || $ville || $search): ?>
                <a href="/secteurs" class="sl-filters__reset">Effacer les filtres</a>
            <?php endif; ?>
        </form>
    </div>
</section>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════
     GRILLE DES SECTEURS
     ═══════════════════════════════════════════════════════ -->
<section class="sl-listing section">
    <div class="container">
        <?php if (empty($secteurs)): ?>
            <div class="sl-empty">
                <i class="fas fa-map-marked-alt"></i>
                <h3>Aucun secteur trouvé</h3>
                <p>Aucun secteur ne correspond à vos critères de recherche.</p>
                <?php if ($typeSecteur || $ville || $search): ?>
                    <a href="/secteurs" class="btn btn-outline btn-sm">Voir tous les secteurs</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="sl-grid">
                <?php foreach ($secteurs as $s):
                    $img  = $s['hero_image'] ?? '';
                    $nom  = $s['nom'] ?? 'Secteur';
                    $slug = $s['slug'] ?? '';
                    $desc = mb_substr(strip_tags($s['description'] ?? ''), 0, 140);
                    $type = $s['type_secteur'] ?? 'quartier';
                    $villeS = $s['ville'] ?? '';
                    $prix = $s['prix_moyen'] ?? '';
                ?>
                <a href="/<?= htmlspecialchars($slug) ?>" class="sl-card">
                    <div class="sl-card__image"
                         style="background-image: url('<?= htmlspecialchars($img ?: '/front/assets/images/placeholder-secteur.jpg') ?>')">
                        <span class="sl-card__badge"><?= htmlspecialchars(ucfirst($type)) ?></span>
                    </div>
                    <div class="sl-card__body">
                        <h3 class="sl-card__title"><?= htmlspecialchars($nom) ?></h3>
                        <?php if ($villeS): ?>
                            <span class="sl-card__ville"><i class="fas fa-map-pin"></i> <?= htmlspecialchars($villeS) ?></span>
                        <?php endif; ?>
                        <?php if ($desc): ?>
                            <p class="sl-card__desc"><?= htmlspecialchars($desc) ?></p>
                        <?php endif; ?>
                        <?php if ($prix): ?>
                            <div class="sl-card__prix">
                                <i class="fas fa-euro-sign"></i>
                                <?= htmlspecialchars($prix) ?> /m&sup2;
                            </div>
                        <?php endif; ?>
                        <span class="sl-card__link">Voir le secteur <i class="fas fa-arrow-right"></i></span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     CTA
     ═══════════════════════════════════════════════════════ -->
<section class="sl-cta">
    <div class="container">
        <h2>Vous avez un projet immobilier dans le Trégor ?</h2>
        <p>Contactez-nous pour un accompagnement personnalisé dans le secteur de votre choix.</p>
        <a href="/contact" class="btn btn-primary">Nous contacter</a>
    </div>
</section>

<!-- ═══════════════════════════════════════════════════════
     CARTE LEAFLET (OpenStreetMap)
     ═══════════════════════════════════════════════════════ -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var mapEl = document.getElementById('sl-map');
    if (!mapEl) return;

    // Centre sur Lannion / Trégor
    var map = L.map('sl-map', {
        scrollWheelZoom: false
    }).setView([48.7307, -3.4597], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 18
    }).addTo(map);

    // Marqueurs des secteurs depuis PHP
    var secteurs = <?= json_encode(array_map(function($s) {
        return [
            'nom'  => $s['nom'] ?? '',
            'slug' => $s['slug'] ?? '',
            'lat'  => floatval($s['latitude'] ?? 0),
            'lng'  => floatval($s['longitude'] ?? 0),
            'type' => $s['type_secteur'] ?? 'quartier',
        ];
    }, $secteurs), JSON_HEX_TAG | JSON_HEX_AMP) ?>;

    var bounds = [];
    var primaryColor = '#1B3A4B';

    secteurs.forEach(function(s) {
        if (s.lat && s.lng) {
            var marker = L.circleMarker([s.lat, s.lng], {
                radius: 8,
                fillColor: primaryColor,
                color: '#fff',
                weight: 2,
                fillOpacity: 0.85
            }).addTo(map);

            marker.bindPopup(
                '<strong>' + s.nom + '</strong><br>' +
                '<a href="/' + s.slug + '">Voir le secteur</a>'
            );
            bounds.push([s.lat, s.lng]);
        }
    });

    // Si des marqueurs existent, ajuster la vue
    if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [40, 40] });
    } else if (bounds.length === 1) {
        map.setView(bounds[0], 13);
    }
    // Sinon on reste centré sur Lannion

    // Zone approximative du Trégor (polygone indicatif)
    var tregorZone = [
        [48.84, -3.60],
        [48.85, -3.40],
        [48.83, -3.20],
        [48.78, -3.10],
        [48.70, -3.05],
        [48.62, -3.10],
        [48.58, -3.25],
        [48.60, -3.50],
        [48.65, -3.65],
        [48.75, -3.70],
        [48.84, -3.60]
    ];

    L.polygon(tregorZone, {
        color: primaryColor,
        weight: 2,
        fillColor: primaryColor,
        fillOpacity: 0.08,
        dashArray: '6 4'
    }).addTo(map);
});
</script>
