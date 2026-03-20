<?php
/**
 * /front/templates/pages/t10-biens-listing.php
 * Template Listing Biens Immobiliers
 * Variables attendues depuis renderers/properties-listing.php :
 *   $properties, $totalItems, $totalPages, $currentPage,
 *   $types, $cities, $filterType, $filterTrans, $filterCity,
 *   $priceMin, $priceMax, $search, $perPage
 */

$properties  = $properties  ?? [];
$totalItems  = $totalItems  ?? 0;
$totalPages  = $totalPages  ?? 1;
$currentPage = $currentPage ?? 1;
$types       = $types       ?? [];
$cities      = $cities      ?? [];
$filterType  = $filterType  ?? 'all';
$filterTrans = $filterTrans ?? 'all';
$filterCity  = $filterCity  ?? 'all';
$priceMin    = $priceMin    ?? 0;
$priceMax    = $priceMax    ?? 0;
$search      = $search      ?? '';

ob_start();
?>

<link rel="stylesheet" href="/front/templates/pages/css/t10-biens-listing.css">

<!-- HERO -->
<section class="biens-hero">
    <div class="biens-hero__inner">
        <h1 class="biens-hero__title">Nos biens immobiliers</h1>
        <p class="biens-hero__sub">Découvrez notre sélection de biens à Lannion et ses environs</p>
        <div class="biens-hero__count">
            <?= $totalItems ?> bien<?= $totalItems > 1 ? 's' : '' ?> disponible<?= $totalItems > 1 ? 's' : '' ?>
        </div>
    </div>
</section>

<!-- FILTRES -->
<section class="biens-filters">
    <form class="biens-filters__form" method="get" action="/biens-immobiliers">
        <div class="biens-filters__row">

            <!-- Recherche texte -->
            <div class="biens-filters__field">
                <label for="q">Recherche</label>
                <input type="text" id="q" name="q" placeholder="Ville, référence..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>

            <!-- Type de bien -->
            <div class="biens-filters__field">
                <label for="type">Type de bien</label>
                <select id="type" name="type">
                    <option value="all">Tous les types</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= $filterType === $t ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($t)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Transaction -->
            <div class="biens-filters__field">
                <label for="transaction">Transaction</label>
                <select id="transaction" name="transaction">
                    <option value="all" <?= $filterTrans === 'all' ? 'selected' : '' ?>>Vente & Location</option>
                    <option value="vente" <?= $filterTrans === 'vente' ? 'selected' : '' ?>>Vente</option>
                    <option value="location" <?= $filterTrans === 'location' ? 'selected' : '' ?>>Location</option>
                </select>
            </div>

            <!-- Secteur (ville) -->
            <div class="biens-filters__field">
                <label for="secteur">Secteur</label>
                <select id="secteur" name="secteur">
                    <option value="all">Tous les secteurs</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>" <?= $filterCity === $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Prix min -->
            <div class="biens-filters__field">
                <label for="prix_min">Prix min</label>
                <input type="number" id="prix_min" name="prix_min" placeholder="0 €" min="0" step="10000"
                       value="<?= $priceMin > 0 ? $priceMin : '' ?>">
            </div>

            <!-- Prix max -->
            <div class="biens-filters__field">
                <label for="prix_max">Prix max</label>
                <input type="number" id="prix_max" name="prix_max" placeholder="Illimité" min="0" step="10000"
                       value="<?= $priceMax > 0 ? $priceMax : '' ?>">
            </div>
        </div>

        <div class="biens-filters__actions">
            <button type="submit" class="biens-btn biens-btn--primary">
                <i class="fas fa-search"></i> Rechercher
            </button>
            <a href="/biens-immobiliers" class="biens-btn biens-btn--outline">Réinitialiser</a>
        </div>
    </form>
</section>

<!-- LISTING -->
<section class="biens-listing">

    <?php if (empty($properties)): ?>
        <div class="biens-empty">
            <i class="fas fa-home"></i>
            <h2>Aucun bien trouvé</h2>
            <p>Aucun bien ne correspond à vos critères. Essayez de modifier vos filtres.</p>
            <a href="/biens-immobiliers" class="biens-btn biens-btn--primary">Voir tous les biens</a>
        </div>
    <?php else: ?>

        <div class="biens-grid">
            <?php foreach ($properties as $bien): ?>
                <?php
                    $titre   = $bien['titre'] ?? 'Bien immobilier';
                    $prix    = (float)($bien['prix'] ?? 0);
                    $surface = (int)($bien['surface'] ?? 0);
                    $pieces  = (int)($bien['pieces'] ?? 0);
                    $ville   = $bien['ville'] ?? '';
                    $type    = $bien['type_bien'] ?? '';
                    $trans   = $bien['transaction'] ?? 'vente';
                    $dpe     = $bien['dpe'] ?? null;
                    $ref     = $bien['reference'] ?? '';
                    $slug    = $bien['slug'] ?? '';

                    // Photo principale
                    $photos  = [];
                    if (!empty($bien['photos'])) {
                        $decoded = is_string($bien['photos']) ? json_decode($bien['photos'], true) : $bien['photos'];
                        if (is_array($decoded)) $photos = $decoded;
                    }
                    $mainPhoto = $photos[0] ?? null;

                    $linkUrl = $slug ? "/biens/{$slug}" : '#';
                    $prixLabel = $trans === 'location'
                        ? number_format($prix, 0, ',', ' ') . ' €/mois'
                        : number_format($prix, 0, ',', ' ') . ' €';
                ?>
                <a href="<?= htmlspecialchars($linkUrl) ?>" class="bien-card">
                    <!-- Photo -->
                    <div class="bien-card__photo">
                        <?php if ($mainPhoto): ?>
                            <img src="<?= htmlspecialchars($mainPhoto) ?>" alt="<?= htmlspecialchars($titre) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="bien-card__no-photo">
                                <i class="fas fa-camera"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Badges -->
                        <div class="bien-card__badges">
                            <?php if ($trans === 'location'): ?>
                                <span class="bien-badge bien-badge--location">Location</span>
                            <?php else: ?>
                                <span class="bien-badge bien-badge--vente">Vente</span>
                            <?php endif; ?>
                            <?php if ($type): ?>
                                <span class="bien-badge bien-badge--type"><?= htmlspecialchars(ucfirst($type)) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if (count($photos) > 1): ?>
                            <span class="bien-card__photo-count">
                                <i class="fas fa-images"></i> <?= count($photos) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Infos -->
                    <div class="bien-card__body">
                        <div class="bien-card__price"><?= $prixLabel ?></div>
                        <h3 class="bien-card__title"><?= htmlspecialchars($titre) ?></h3>
                        <div class="bien-card__location">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($ville) ?>
                        </div>
                        <div class="bien-card__features">
                            <?php if ($surface > 0): ?>
                                <span><i class="fas fa-ruler-combined"></i> <?= $surface ?> m²</span>
                            <?php endif; ?>
                            <?php if ($pieces > 0): ?>
                                <span><i class="fas fa-door-open"></i> <?= $pieces ?> pièce<?= $pieces > 1 ? 's' : '' ?></span>
                            <?php endif; ?>
                            <?php if ($dpe): ?>
                                <span class="bien-dpe bien-dpe--<?= strtolower($dpe) ?>">DPE <?= strtoupper($dpe) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($ref): ?>
                            <div class="bien-card__ref">Réf. <?= htmlspecialchars($ref) ?></div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- PAGINATION -->
        <?php if ($totalPages > 1): ?>
            <nav class="biens-pagination">
                <?php
                    // Build base URL with current filters
                    $baseParams = [];
                    if ($filterType !== 'all')  $baseParams['type']        = $filterType;
                    if ($filterTrans !== 'all') $baseParams['transaction'] = $filterTrans;
                    if ($filterCity !== 'all')  $baseParams['secteur']     = $filterCity;
                    if ($priceMin > 0)          $baseParams['prix_min']    = $priceMin;
                    if ($priceMax > 0)          $baseParams['prix_max']    = $priceMax;
                    if ($search !== '')          $baseParams['q']           = $search;

                    function biensPaginationUrl(int $page, array $base): string {
                        $base['p'] = $page;
                        return '/biens-immobiliers?' . http_build_query($base);
                    }
                ?>

                <?php if ($currentPage > 1): ?>
                    <a href="<?= biensPaginationUrl($currentPage - 1, $baseParams) ?>" class="biens-pagination__link">
                        <i class="fas fa-chevron-left"></i> Précédent
                    </a>
                <?php endif; ?>

                <?php
                    $start = max(1, $currentPage - 2);
                    $end   = min($totalPages, $currentPage + 2);
                ?>
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a href="<?= biensPaginationUrl($i, $baseParams) ?>"
                       class="biens-pagination__link <?= $i === $currentPage ? 'biens-pagination__link--active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?= biensPaginationUrl($currentPage + 1, $baseParams) ?>" class="biens-pagination__link">
                        Suivant <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>

    <?php endif; ?>
</section>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>
