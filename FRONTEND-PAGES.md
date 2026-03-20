# Pages Frontend - Stephanie Hulen Immobilier Lannion

## Vue d'ensemble

Site vitrine immobilier pour Stephanie Hulen, conseillere immobiliere a Lannion.
Le systeme utilise un CMS maison avec des templates PHP dans `/front/templates/`.
Le routing est gere par `/front/router.php` (URL rewriting) et `/front/page.php` (renderers).

---

## Liste des pages frontend

### Pages principales (templates existants)

| # | Template | URL | Description | CSS dedié |
|---|----------|-----|-------------|-----------|
| T1 | `t1-accueil.php` | `/` | Page d'accueil — hero, offre signature, services | t1-accueil.css |
| T2 | `t2-vendre.php` | `/vendre` | Page Vendre — argumentaire vendeurs | t2-vendre.css |
| T3 | `t3-acheter.php` | `/acheter` | Page Acheter — guide acheteurs | t3-acheter.css |
| T4 | `t4-investir.php` | `/investir` | Page Investir — investissement immobilier | t4-investir.css |
| T7 | `t7-estimation.php` | `/estimation` | Estimation gratuite — formulaire d'estimation | t7-estimation.css |
| T8 | `t8-contact.php` | `/contact` | Contact — formulaire + coordonnees | t8-contact.css |
| T9 | `t9-honoraires.php` | `/honoraires` | Barème d'honoraires | t9-honoraires.css |
| T14 | `t14-apropos.php` | `/a-propos` | A propos — presentation conseillere | t14-apropos.css |

### Pages Contenu dynamique

| # | Template | URL | Description | CSS dedié |
|---|----------|-----|-------------|-----------|
| T4b | `t4-blog-hub.php` | `/blog` | Blog — listing des articles | — |
| T5 | `t5-article.php` | `/blog/{slug}` | Article blog — page single | t5-article.css |
| T6 | `t6-guide.php` | `/guide-local/{slug}` | Guide local — contenu territorial | t6-guide.css |
| T2b | `t2-edito.php` | `/{slug}` | Page editoriale generique (CMS) | — |

### Pages Immobilier

| # | Template | URL | Description | CSS dedié |
|---|----------|-----|-------------|-----------|
| T10 | `t10-biens-listing.php` | `/biens-immobiliers` | Catalogue biens — listing avec filtres | t10-biens-listing.css |
| T11 | `t11-bien-single.php` | `/biens/{slug}` | Fiche bien — detail d'un bien immobilier | t11-bien-single.css |

### Pages Secteurs geographiques

| # | Template | URL | Description | CSS dedié |
|---|----------|-----|-------------|-----------|
| T15 | `t15-secteurs-listing.php` | `/secteurs` | Listing des secteurs (Lannion, Perros-Guirec, etc.) | t15-secteurs-listing.css |
| T3b | `t3-secteur.php` | `/secteurs/{slug}` | Fiche secteur — detail d'une commune/quartier | — |
| T16 | `t16-rapport-marche.php` | `/rapport-marche` | Rapport de marche immobilier local | t16-rapport-marche.css |

### Pages Conversion (Captures / Lead gen)

| # | Template | URL | Description | CSS dedié |
|---|----------|-----|-------------|-----------|
| T5c | `t5-capture-guide.php` | `/capture/{slug}` | Landing page capture — telechargement guide | — |
| T6c | `t6-capture-merci.php` | `/merci` | Page merci apres capture | — |
| T13 | `t13-merci.php` | `/merci` | Page de confirmation generique | t13-merci.css |

### Pages Legales

| # | Template | URL | Description | CSS dedié |
|---|----------|-----|-------------|-----------|
| T12 | `t12-legal.php` | `/mentions-legales` | Mentions legales, CGU, politique de confidentialite | t12-legal.css |

### Pages Ressources

| # | Template | URL | Description | CSS dedié |
|---|----------|-----|-------------|-----------|
| T17 | `t17-ressources-listing.php` | `/ressources` | Listing des ressources (guides, outils) | — |
| T18 | `t18-ressources-single.php` | `/ressources/{slug}` | Fiche ressource single | — |
| T19 | `t19-ressources-merci.php` | `/ressources/merci` | Merci apres telechargement ressource | — |

### Pages Erreur

| # | Template | URL | Description |
|---|----------|-----|-------------|
| — | `front/404.php` | — | Page 404 Not Found |
| — | `front/500.php` | — | Page 500 Erreur serveur |
| — | `front/renderers/404.php` | — | Renderer 404 alternatif |

---

## Pages a creer / manquantes identifiees

Le menu par defaut du header inclut une page **Financement** (`/financer`) qui n'a pas de template dedie.
L'admin possede un module `immobilier/financement` et des images `financement-bordeaux.png`.

### Pages recommandees a ajouter

| Priorite | Page | URL suggeree | Template | Raison |
|----------|------|-------------|----------|--------|
| **HAUTE** | Financement | `/financer-mon-projet` | `t20-financement.php` | Presente dans le menu par defaut, module admin existant, page cle pour la conversion |
| **HAUTE** | Page RDV / Prise de rendez-vous | `/rendez-vous` | `t21-rdv.php` | Module admin `immobilier/rdv` existant, manque la page front |
| MOYENNE | Annuaire partenaires | `/partenaires` | `t22-partenaires.php` | Module admin `content/annuaire` existant |
| MOYENNE | FAQ / Questions frequentes | `/faq` | `t23-faq.php` | Ameliore le SEO local, repond aux questions vendeurs/acheteurs |
| BASSE | Temoignages / Avis clients | `/temoignages` | `t24-temoignages.php` | Preuve sociale, confiance — donnees potentiellement dans GMB scraper |
| BASSE | Simulateur pret immobilier | `/simulateur-pret` | Integre dans t20 | Outil interactif pour les acheteurs |

---

## Architecture technique

### Systeme de routing

```
.htaccess → RewriteRule → /front/router.php?_uri={slug}
                        → /front/page.php (renderers alternatifs)
```

### Variables injectees dans chaque template

| Variable | Type | Description |
|----------|------|-------------|
| `$website` | array | Infos site (id, slug, domain) |
| `$page` | array|null | Donnees page depuis table `pages` |
| `$fields` | array | Champs editables (JSON depuis `fields_json`) |
| `$advisor` | array | Infos conseillere (nom, tel, email, ville) |
| `$site` | array | Alias de $website |
| `$pdo` | PDO | Connexion base de donnees |
| `$headerData` | array|null | Configuration header depuis DB |
| `$footerData` | array|null | Configuration footer depuis DB |
| `$editMode` | bool | Mode edition active (preview admin) |

### Structure des fichiers

```
front/
├── templates/
│   ├── pages/           ← Templates principaux (t1 a t16)
│   │   ├── css/         ← CSS dedie par template
│   │   └── layout.php   ← Layout commun
│   ├── captures/        ← Templates landing pages (t5c, t6c)
│   ├── ressources/      ← Templates ressources (t17-t19)
│   └── preview-template.php
├── renderers/           ← Renderers alternatifs (page.php)
├── helpers/             ← Fonctions utilitaires
├── includes/            ← Header/footer + fonctions
└── assets/              ← CSS, JS, images
```

---

## Resume

- **22 templates** frontend existants au total
- **6 pages recommandees** a ajouter (2 haute priorite)
- Le site couvre : accueil, acheter, vendre, investir, estimation, biens, secteurs, blog, guides, contact, honoraires, a propos, mentions legales, ressources
- **Priorite #1** : creer la page Financement (deja dans le menu, module admin pret)
- **Priorite #2** : creer la page RDV (module admin existant, aucune page front)
