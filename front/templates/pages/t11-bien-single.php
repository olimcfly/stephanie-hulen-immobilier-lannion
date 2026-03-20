<?php
/**
 * T11 — Bien Single (fiche détaillée d'un bien immobilier)
 *
 * Features:
 *   1. Galerie photos carousel
 *   2. Caractéristiques (surface, pièces, DPE, etc.)
 *   3. Carte OpenStreetMap (Leaflet)
 *   4. Formulaire de contact pour le bien
 */

$fields     = $fields     ?? [];
$editMode   = $editMode   ?? false;
$advisor    = $advisor    ?? [];
$site       = $site       ?? [];
$pdo        = $pdo        ?? null;

require_once __DIR__ . '/../../helpers/menu-helper.php';
$headerMenu = getMenu('header-main', $pdo ?? null) ?? [];

// ── Charger le bien depuis la BDD ────────────────────────
$bien = null;
$bienSlug = $bien_slug ?? $_GET['slug'] ?? '';

if ($pdo && $bienSlug) {
    try {
        $st = $pdo->prepare("SELECT * FROM properties WHERE slug = ? LIMIT 1");
        $st->execute([$bienSlug]);
        $bien = $st->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Fallback table biens
        try {
            $st = $pdo->prepare("SELECT * FROM biens WHERE slug = ? LIMIT 1");
            $st->execute([$bienSlug]);
            $bien = $st->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e2) {}
    }
}

// ── Extraire les données du bien ─────────────────────────
$bienTitle       = $bien['titre']       ?? $bien['title']       ?? $fields['bien_title']       ?? 'Bien immobilier';
$bienPrice       = $bien['prix']        ?? $bien['price']       ?? $fields['bien_price']       ?? 0;
$bienDescription = $bien['description'] ?? $fields['bien_description'] ?? '';
$bienSurface     = (int)($bien['surface']    ?? $bien['area']   ?? 0);
$bienPieces      = (int)($bien['pieces']     ?? $bien['rooms']  ?? 0);
$bienType        = $bien['type_bien']   ?? $bien['type']        ?? '';
$bienTransaction = $bien['transaction'] ?? $bien['transaction_type'] ?? 'vente';
$bienVille       = $bien['ville']       ?? $bien['city']        ?? '';
$bienCP          = $bien['code_postal'] ?? '';
$bienAdresse     = $bien['adresse']     ?? $bien['address']     ?? '';
$bienRef         = $bien['reference']   ?? $bien['ref']         ?? '';
$bienDpe         = $bien['dpe']         ?? $bien['classe_energie'] ?? '';
$bienMandat      = $bien['mandat']      ?? $bien['type_mandat'] ?? '';
$bienLat         = (float)($bien['latitude']  ?? 0);
$bienLng         = (float)($bien['longitude'] ?? 0);

// Photos : JSON array or comma-separated
$photosRaw = $bien['photos'] ?? $bien['images'] ?? '[]';
$photos = is_string($photosRaw) ? json_decode($photosRaw, true) : (is_array($photosRaw) ? $photosRaw : []);
if (!is_array($photos)) $photos = [];

// Formatted price
$priceFormatted = is_numeric($bienPrice) && $bienPrice > 0
    ? number_format((float)$bienPrice, 0, ',', ' ') . ' €'
    : (is_string($bienPrice) && $bienPrice ? htmlspecialchars($bienPrice) : 'Prix sur demande');

// DPE color mapping
$dpeColors = [
    'A' => '#319834', 'B' => '#33cc31', 'C' => '#cbfc34',
    'D' => '#fcfc02', 'E' => '#fccc06', 'F' => '#fc9935',
    'G' => '#fc0205',
];
$dpeColor = $dpeColors[strtoupper($bienDpe)] ?? '#ccc';

// Advisor info
$advisorName  = $advisor['name']  ?? '';
$advisorPhone = $advisor['phone'] ?? '';
$advisorEmail = $advisor['email'] ?? '';

$pageTitle = htmlspecialchars($bienTitle) . ' — ' . htmlspecialchars($bienVille ?: 'Immobilier');

ob_start();
?>

<!-- Leaflet CSS for map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

<style>
/* ── Carousel ──────────────────────────────────────────── */
.bien-carousel {
    position: relative;
    width: 100%;
    border-radius: 12px;
    overflow: hidden;
    background: #f9f6f3;
    aspect-ratio: 4/3;
}
.bien-carousel__track {
    display: flex;
    transition: transform .4s ease;
    height: 100%;
}
.bien-carousel__slide {
    min-width: 100%;
    height: 100%;
}
.bien-carousel__slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.bien-carousel__btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,.9);
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 18px;
    color: var(--primary, #1B3A4B);
    box-shadow: 0 2px 8px rgba(0,0,0,.15);
    z-index: 2;
    transition: all .2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.bien-carousel__btn:hover {
    background: #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,.2);
}
.bien-carousel__btn--prev { left: 12px; }
.bien-carousel__btn--next { right: 12px; }
.bien-carousel__dots {
    position: absolute;
    bottom: 14px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
    z-index: 2;
}
.bien-carousel__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(255,255,255,.5);
    border: 2px solid rgba(255,255,255,.8);
    cursor: pointer;
    transition: all .2s;
    padding: 0;
}
.bien-carousel__dot--active {
    background: #fff;
    transform: scale(1.2);
}
.bien-carousel__counter {
    position: absolute;
    top: 14px;
    right: 14px;
    background: rgba(0,0,0,.55);
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    padding: 4px 12px;
    border-radius: 20px;
    z-index: 2;
}
.bien-carousel__placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #999;
    font-size: 15px;
}

/* ── Lightbox ──────────────────────────────────────────── */
.bien-lightbox {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.92);
    z-index: 10000;
    align-items: center;
    justify-content: center;
}
.bien-lightbox.open { display: flex; }
.bien-lightbox img {
    max-width: 90vw;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 8px;
}
.bien-lightbox__close {
    position: absolute;
    top: 20px;
    right: 24px;
    background: none;
    border: none;
    color: #fff;
    font-size: 32px;
    cursor: pointer;
    z-index: 10001;
}
.bien-lightbox__nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,.15);
    border: none;
    color: #fff;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    font-size: 22px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.bien-lightbox__nav:hover { background: rgba(255,255,255,.3); }
.bien-lightbox__nav--prev { left: 20px; }
.bien-lightbox__nav--next { right: 20px; }

/* ── Page layout ───────────────────────────────────────── */
.bien-single { background: #fff; padding: 40px 20px 80px; }
.bien-single__container { max-width: 1100px; margin: 0 auto; }
.bien-single__grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    align-items: start;
}
.bien-single__badge {
    display: inline-block;
    background: var(--accent, #d4a574);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .5px;
    padding: 4px 14px;
    border-radius: 20px;
    margin-bottom: 16px;
}
.bien-single__title {
    font-size: clamp(1.6rem, 3vw, 2.4rem);
    color: var(--primary, #1B3A4B);
    margin-bottom: 8px;
    line-height: 1.2;
}
.bien-single__location {
    color: var(--text-2, #4a5568);
    font-size: 15px;
    margin-bottom: 16px;
}
.bien-single__location i { margin-right: 4px; color: var(--accent, #d4a574); }
.bien-single__price {
    font-family: 'Playfair Display', serif;
    font-size: 2rem;
    color: var(--accent, #d4a574);
    font-weight: 700;
    margin-bottom: 8px;
}
.bien-single__ref {
    font-size: 13px;
    color: var(--text-3, #718096);
    margin-bottom: 32px;
}

/* ── Caractéristiques ──────────────────────────────────── */
.bien-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}
.bien-feature {
    background: var(--bg, #f9f6f3);
    border-radius: 10px;
    padding: 18px 16px;
    text-align: center;
}
.bien-feature__icon {
    font-size: 22px;
    color: var(--primary, #1B3A4B);
    margin-bottom: 6px;
}
.bien-feature__value {
    font-size: 18px;
    font-weight: 700;
    color: var(--text, #1a1a2e);
}
.bien-feature__label {
    font-size: 12px;
    color: var(--text-3, #718096);
    text-transform: uppercase;
    letter-spacing: .3px;
}

/* ── DPE badge ─────────────────────────────────────────── */
.bien-dpe {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 32px;
}
.bien-dpe__badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 42px;
    height: 42px;
    border-radius: 8px;
    color: #fff;
    font-size: 20px;
    font-weight: 800;
}
.bien-dpe__label {
    font-size: 13px;
    color: var(--text-2, #4a5568);
}

/* ── Description ───────────────────────────────────────── */
.bien-description {
    color: var(--text-2, #4a5568);
    line-height: 1.85;
    margin-bottom: 40px;
}
.bien-description h3 {
    font-size: 1.2rem;
    color: var(--primary, #1B3A4B);
    margin-bottom: 12px;
}

/* ── Map ───────────────────────────────────────────────── */
.bien-map-section {
    margin-bottom: 40px;
}
.bien-map-section h3 {
    font-size: 1.2rem;
    color: var(--primary, #1B3A4B);
    margin-bottom: 14px;
}
#bien-map {
    width: 100%;
    height: 320px;
    border-radius: 12px;
    border: 1px solid var(--border, #e8e0d8);
    z-index: 1;
}

/* ── Contact form ──────────────────────────────────────── */
.bien-contact {
    background: var(--bg, #f9f6f3);
    border-radius: 16px;
    padding: 32px;
    margin-top: 8px;
}
.bien-contact h3 {
    font-size: 1.2rem;
    color: var(--primary, #1B3A4B);
    margin-bottom: 6px;
}
.bien-contact__subtitle {
    font-size: 14px;
    color: var(--text-3, #718096);
    margin-bottom: 20px;
}
.bien-contact .form-group { margin-bottom: 14px; }
.bien-contact .form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: var(--text-2, #4a5568);
    margin-bottom: 5px;
}
.bien-contact .form-group input,
.bien-contact .form-group textarea,
.bien-contact .form-group select {
    width: 100%;
    padding: 11px 14px;
    border: 1.5px solid var(--border, #e8e0d8);
    border-radius: 8px;
    font-family: 'DM Sans', sans-serif;
    font-size: 14px;
    color: var(--text, #1a1a2e);
    background: #fff;
    transition: border-color .2s, box-shadow .2s;
}
.bien-contact .form-group input:focus,
.bien-contact .form-group textarea:focus {
    outline: none;
    border-color: var(--primary, #1B3A4B);
    box-shadow: 0 0 0 3px rgba(27,58,75,.1);
}
.bien-contact .form-group textarea { min-height: 90px; resize: vertical; }
.bien-contact__row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.bien-contact__btn {
    width: 100%;
    padding: 14px;
    background: var(--primary, #1B3A4B);
    color: #fff;
    border: none;
    border-radius: 10px;
    font-family: 'DM Sans', sans-serif;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    transition: background .2s;
    margin-top: 4px;
}
.bien-contact__btn:hover { background: var(--primary-d, #122A37); }
.bien-contact__success {
    display: none;
    text-align: center;
    padding: 24px;
    color: #059669;
    font-weight: 600;
}
.bien-contact__advisor {
    margin-top: 20px;
    padding-top: 16px;
    border-top: 1px solid var(--border, #e8e0d8);
    font-size: 14px;
    color: var(--text-2, #4a5568);
}
.bien-contact__advisor a {
    color: var(--primary, #1B3A4B);
    font-weight: 600;
}

/* ── Bottom grid (map + contact) ───────────────────────── */
.bien-single__bottom-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 48px;
    margin-top: 48px;
}

/* ── Responsive ────────────────────────────────────────── */
@media (max-width: 768px) {
    .bien-single__grid {
        grid-template-columns: 1fr;
        gap: 32px;
    }
    .bien-single__bottom-grid {
        grid-template-columns: 1fr;
        gap: 32px;
    }
    .bien-contact__row {
        grid-template-columns: 1fr;
    }
    .bien-features {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- LIGHTBOX -->
<!-- ═══════════════════════════════════════════════════════ -->
<?php if (!empty($photos)): ?>
<div class="bien-lightbox" id="bienLightbox">
    <button class="bien-lightbox__close" onclick="closeLightbox()" aria-label="Fermer">&times;</button>
    <button class="bien-lightbox__nav bien-lightbox__nav--prev" onclick="lightboxNav(-1)" aria-label="Pr&eacute;c&eacute;dent"><i class="fas fa-chevron-left"></i></button>
    <img id="lightboxImg" src="" alt="Photo du bien">
    <button class="bien-lightbox__nav bien-lightbox__nav--next" onclick="lightboxNav(1)" aria-label="Suivant"><i class="fas fa-chevron-right"></i></button>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- MAIN CONTENT -->
<!-- ═══════════════════════════════════════════════════════ -->
<section class="bien-single">
    <div class="bien-single__container">

        <div class="bien-single__grid">

            <!-- ── LEFT: Carousel ──────────────────────── -->
            <div>
                <div class="bien-carousel" id="bienCarousel">
                    <?php if (!empty($photos)): ?>
                        <div class="bien-carousel__track" id="carouselTrack">
                            <?php foreach ($photos as $i => $photo): ?>
                                <?php $src = is_array($photo) ? ($photo['url'] ?? $photo['src'] ?? '') : $photo; ?>
                                <?php if ($src): ?>
                                <div class="bien-carousel__slide">
                                    <img src="<?= htmlspecialchars($src) ?>"
                                         alt="<?= htmlspecialchars($bienTitle) ?> — photo <?= $i + 1 ?>"
                                         loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
                                         onclick="openLightbox(<?= $i ?>)"
                                         style="cursor:zoom-in">
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <button class="bien-carousel__btn bien-carousel__btn--prev" onclick="carouselNav(-1)" aria-label="Pr&eacute;c&eacute;dent"><i class="fas fa-chevron-left"></i></button>
                        <button class="bien-carousel__btn bien-carousel__btn--next" onclick="carouselNav(1)" aria-label="Suivant"><i class="fas fa-chevron-right"></i></button>
                        <div class="bien-carousel__counter" id="carouselCounter">1 / <?= count($photos) ?></div>
                        <div class="bien-carousel__dots" id="carouselDots">
                            <?php foreach ($photos as $i => $photo): ?>
                                <button class="bien-carousel__dot<?= $i === 0 ? ' bien-carousel__dot--active' : '' ?>"
                                        onclick="carouselGo(<?= $i ?>)" aria-label="Photo <?= $i + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="bien-carousel__placeholder">
                            <span><i class="fas fa-camera" style="margin-right:8px"></i>Aucune photo disponible</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── RIGHT: Details ──────────────────────── -->
            <div>
                <?php if ($bienTransaction): ?>
                    <span class="bien-single__badge"><?= htmlspecialchars(ucfirst($bienTransaction)) ?></span>
                <?php endif; ?>

                <h1 class="bien-single__title" <?= $editMode ? 'data-field="bien_title" class="ef-zone"' : '' ?>>
                    <?= htmlspecialchars($bienTitle) ?>
                </h1>

                <?php if ($bienVille || $bienCP): ?>
                    <div class="bien-single__location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars(trim($bienVille . ($bienCP ? ' (' . $bienCP . ')' : ''))) ?>
                    </div>
                <?php endif; ?>

                <div class="bien-single__price"><?= $priceFormatted ?></div>

                <?php if ($bienRef): ?>
                    <div class="bien-single__ref">R&eacute;f. <?= htmlspecialchars($bienRef) ?></div>
                <?php endif; ?>

                <!-- ── Caractéristiques ──────────────── -->
                <div class="bien-features">
                    <?php if ($bienSurface > 0): ?>
                    <div class="bien-feature">
                        <div class="bien-feature__icon"><i class="fas fa-ruler-combined"></i></div>
                        <div class="bien-feature__value"><?= $bienSurface ?> m&sup2;</div>
                        <div class="bien-feature__label">Surface</div>
                    </div>
                    <?php endif; ?>
                    <?php if ($bienPieces > 0): ?>
                    <div class="bien-feature">
                        <div class="bien-feature__icon"><i class="fas fa-door-open"></i></div>
                        <div class="bien-feature__value"><?= $bienPieces ?></div>
                        <div class="bien-feature__label">Pi&egrave;ces</div>
                    </div>
                    <?php endif; ?>
                    <?php if ($bienType): ?>
                    <div class="bien-feature">
                        <div class="bien-feature__icon"><i class="fas fa-home"></i></div>
                        <div class="bien-feature__value"><?= htmlspecialchars(ucfirst($bienType)) ?></div>
                        <div class="bien-feature__label">Type</div>
                    </div>
                    <?php endif; ?>
                    <?php if ($bienMandat): ?>
                    <div class="bien-feature">
                        <div class="bien-feature__icon"><i class="fas fa-file-signature"></i></div>
                        <div class="bien-feature__value"><?= htmlspecialchars(ucfirst($bienMandat)) ?></div>
                        <div class="bien-feature__label">Mandat</div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ── DPE ───────────────────────────── -->
                <?php if ($bienDpe): ?>
                <div class="bien-dpe">
                    <div class="bien-dpe__badge" style="background:<?= $dpeColor ?>">
                        <?= htmlspecialchars(strtoupper($bienDpe)) ?>
                    </div>
                    <div class="bien-dpe__label">Diagnostic de<br>performance &eacute;nerg&eacute;tique</div>
                </div>
                <?php endif; ?>

                <!-- ── Description ───────────────────── -->
                <?php if ($bienDescription): ?>
                <div class="bien-description">
                    <h3>Description</h3>
                    <div <?= $editMode ? 'data-field="bien_description" class="ef-zone"' : '' ?>>
                        <?= nl2br(htmlspecialchars($bienDescription)) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- MAP + CONTACT — full width below the grid      -->
        <!-- ═══════════════════════════════════════════════ -->
        <div class="bien-single__bottom-grid">

            <!-- ── Carte ─────────────────────────────── -->
            <div>
                <?php if ($bienLat != 0 && $bienLng != 0): ?>
                <div class="bien-map-section">
                    <h3><i class="fas fa-map-marker-alt" style="color:var(--accent,#d4a574);margin-right:6px"></i>Localisation</h3>
                    <div id="bien-map"></div>
                </div>
                <?php elseif ($bienVille): ?>
                <div class="bien-map-section">
                    <h3><i class="fas fa-map-marker-alt" style="color:var(--accent,#d4a574);margin-right:6px"></i>Localisation</h3>
                    <div id="bien-map"></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ── Formulaire de contact ─────────────── -->
            <div>
                <div class="bien-contact">
                    <h3><i class="fas fa-envelope" style="color:var(--accent,#d4a574);margin-right:6px"></i>Int&eacute;ress&eacute; par ce bien ?</h3>
                    <p class="bien-contact__subtitle">Remplissez le formulaire et nous vous recontacterons rapidement.</p>

                    <form id="bienContactForm" method="POST">
                        <input type="hidden" name="property_id" value="<?= (int)($bien['id'] ?? 0) ?>">
                        <input type="hidden" name="property_ref" value="<?= htmlspecialchars($bienRef) ?>">
                        <input type="hidden" name="property_title" value="<?= htmlspecialchars($bienTitle) ?>">

                        <div class="bien-contact__row">
                            <div class="form-group">
                                <label for="contact_nom">Nom *</label>
                                <input type="text" id="contact_nom" name="nom" required placeholder="Votre nom">
                            </div>
                            <div class="form-group">
                                <label for="contact_prenom">Pr&eacute;nom *</label>
                                <input type="text" id="contact_prenom" name="prenom" required placeholder="Votre pr&eacute;nom">
                            </div>
                        </div>
                        <div class="bien-contact__row">
                            <div class="form-group">
                                <label for="contact_email">Email *</label>
                                <input type="email" id="contact_email" name="email" required placeholder="votre@email.fr">
                            </div>
                            <div class="form-group">
                                <label for="contact_tel">T&eacute;l&eacute;phone</label>
                                <input type="tel" id="contact_tel" name="telephone" placeholder="06 12 34 56 78">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="contact_message">Message</label>
                            <textarea id="contact_message" name="message" placeholder="Bonjour, je suis int&eacute;ress&eacute;(e) par ce bien (R&eacute;f. <?= htmlspecialchars($bienRef) ?>). Merci de me recontacter."><?= "Bonjour, je suis intéressé(e) par ce bien" . ($bienRef ? " (Réf. " . htmlspecialchars($bienRef) . ")" : "") . ". Merci de me recontacter." ?></textarea>
                        </div>

                        <button type="submit" class="bien-contact__btn">
                            <i class="fas fa-paper-plane" style="margin-right:6px"></i>Envoyer ma demande
                        </button>
                    </form>

                    <div class="bien-contact__success" id="contactSuccess">
                        <i class="fas fa-check-circle" style="font-size:28px;margin-bottom:8px;display:block"></i>
                        Votre demande a bien &eacute;t&eacute; envoy&eacute;e !<br>
                        <small style="font-weight:400;color:var(--text-3,#718096)">Nous vous recontacterons dans les plus brefs d&eacute;lais.</small>
                    </div>

                    <?php if ($advisorPhone || $advisorEmail): ?>
                    <div class="bien-contact__advisor">
                        <strong>Contact direct :</strong><br>
                        <?php if ($advisorPhone): ?>
                            <a href="tel:<?= preg_replace('/\s+/', '', $advisorPhone) ?>"><i class="fas fa-phone" style="margin-right:4px"></i><?= htmlspecialchars($advisorPhone) ?></a><br>
                        <?php endif; ?>
                        <?php if ($advisorEmail): ?>
                            <a href="mailto:<?= htmlspecialchars($advisorEmail) ?>"><i class="fas fa-envelope" style="margin-right:4px"></i><?= htmlspecialchars($advisorEmail) ?></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- ═══════════════════════════════════════════════════════ -->
<!-- SCRIPTS -->
<!-- ═══════════════════════════════════════════════════════ -->

<?php if ($bienLat != 0 && $bienLng != 0): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(function(){
    var lat = <?= $bienLat ?>, lng = <?= $bienLng ?>;
    var map = L.map('bien-map', {scrollWheelZoom: false}).setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 18
    }).addTo(map);
    L.marker([lat, lng]).addTo(map)
     .bindPopup('<?= addslashes(htmlspecialchars($bienTitle)) ?>');
})();
</script>
<?php elseif ($bienVille): ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(function(){
    var query = <?= json_encode($bienVille . ($bienCP ? ' ' . $bienCP : '') . ', France') ?>;
    var mapEl = document.getElementById('bien-map');
    if (!mapEl) return;
    fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(query) + '&limit=1')
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (!data.length) { mapEl.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#999;font-size:14px">Localisation non disponible</div>'; return; }
            var lat = parseFloat(data[0].lat), lng = parseFloat(data[0].lon);
            var map = L.map('bien-map', {scrollWheelZoom: false}).setView([lat, lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap',
                maxZoom: 18
            }).addTo(map);
            L.marker([lat, lng]).addTo(map)
             .bindPopup(<?= json_encode(htmlspecialchars($bienVille)) ?>);
        })
        .catch(function(){
            mapEl.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#999;font-size:14px">Carte indisponible</div>';
        });
})();
</script>
<?php endif; ?>

<script>
(function(){
    /* ── Carousel ────────────────────────────────── */
    var currentSlide = 0;
    var totalSlides = <?= max(1, count($photos)) ?>;
    var track = document.getElementById('carouselTrack');
    var dots = document.querySelectorAll('.bien-carousel__dot');
    var counter = document.getElementById('carouselCounter');

    function updateCarousel() {
        if (!track) return;
        track.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
        dots.forEach(function(d, i) {
            d.classList.toggle('bien-carousel__dot--active', i === currentSlide);
        });
        if (counter) counter.textContent = (currentSlide + 1) + ' / ' + totalSlides;
    }

    window.carouselNav = function(dir) {
        currentSlide = (currentSlide + dir + totalSlides) % totalSlides;
        updateCarousel();
    };

    window.carouselGo = function(idx) {
        currentSlide = idx;
        updateCarousel();
    };

    /* Touch / swipe support */
    var carousel = document.getElementById('bienCarousel');
    if (carousel) {
        var startX = 0;
        carousel.addEventListener('touchstart', function(e) { startX = e.touches[0].clientX; }, {passive: true});
        carousel.addEventListener('touchend', function(e) {
            var diff = startX - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 50) carouselNav(diff > 0 ? 1 : -1);
        }, {passive: true});
    }

    /* ── Lightbox ────────────────────────────────── */
    var photos = <?= json_encode(array_map(function($p) { return is_array($p) ? ($p['url'] ?? $p['src'] ?? '') : $p; }, $photos)) ?>;
    var lbIdx = 0;
    var lightbox = document.getElementById('bienLightbox');
    var lbImg = document.getElementById('lightboxImg');

    window.openLightbox = function(idx) {
        if (!lightbox || !photos.length) return;
        lbIdx = idx;
        lbImg.src = photos[lbIdx];
        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeLightbox = function() {
        if (!lightbox) return;
        lightbox.classList.remove('open');
        document.body.style.overflow = '';
    };

    window.lightboxNav = function(dir) {
        lbIdx = (lbIdx + dir + photos.length) % photos.length;
        lbImg.src = photos[lbIdx];
    };

    if (lightbox) {
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) closeLightbox();
        });
        document.addEventListener('keydown', function(e) {
            if (!lightbox.classList.contains('open')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') lightboxNav(-1);
            if (e.key === 'ArrowRight') lightboxNav(1);
        });
    }

    /* ── Contact form ───────────────────────────── */
    var form = document.getElementById('bienContactForm');
    var success = document.getElementById('contactSuccess');

    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            var origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px"></i>Envoi en cours...';

            var formData = new FormData(form);
            formData.append('source', 'bien-single');

            fetch('/admin/core/handlers/contact.php?action=create', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                form.style.display = 'none';
                success.style.display = 'block';
            })
            .catch(function() {
                form.style.display = 'none';
                success.style.display = 'block';
            })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = origText;
            });
        });
    }
})();
</script>

<?php
$content = ob_get_clean();
$headerData = $headerData ?? null;
$footerData = $footerData ?? null;
require __DIR__ . '/layout.php';
?>
