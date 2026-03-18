<?php
/**
 * /admin/modules/seo/guide.php
 * Onglet Guide SEO — Explications simples pour non-marketeurs
 * Pas de jargon compliqué, focus sur cas d'usage réels
 */
?>

<style>
.seo-guide-wrap { font-family: var(--font, 'Inter', sans-serif); }

/* ─── Onglets ─── */
.seo-guide-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--border, #e5e7eb); margin-bottom: 24px; }
.seo-guide-tab { padding: 12px 20px; font-size: .85rem; font-weight: 600; color: var(--text-2, #6b7280); cursor: pointer; border-bottom: 3px solid transparent; transition: all .2s; text-decoration: none; display: flex; align-items: center; gap: 6px; }
.seo-guide-tab:hover { color: var(--text, #111827); }
.seo-guide-tab.active { color: #8b5cf6; border-bottom-color: #8b5cf6; }

/* ─── Contenu principal ─── */
.seo-guide-content { max-width: 920px; }

/* ─── Sections ─── */
.seo-guide-section { margin-bottom: 40px; }
.seo-guide-section h2 { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin: 0 0 12px; display: flex; align-items: center; gap: 12px; }
.seo-guide-section h2 i { font-size: 1.2rem; }
.seo-guide-section p { font-size: .95rem; color: #475569; line-height: 1.7; margin: 0 0 16px; }

/* ─── Cards ─── */
.seo-guide-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-top: 20px; }
.seo-guide-card { background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 14px; padding: 20px; transition: all .2s; }
.seo-guide-card:hover { border-color: #8b5cf6; box-shadow: 0 4px 20px rgba(139,92,246,.1); }
.seo-guide-card-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px; }
.seo-guide-card-icon.blue { background: rgba(59,130,246,.1); color: #3b82f6; }
.seo-guide-card-icon.violet { background: rgba(139,92,246,.1); color: #8b5cf6; }
.seo-guide-card-icon.pink { background: rgba(219,39,119,.1); color: #db2777; }
.seo-guide-card-icon.green { background: rgba(16,185,129,.1); color: #10b981; }
.seo-guide-card-icon.teal { background: rgba(13,148,136,.1); color: #0d9488; }
.seo-guide-card h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 8px; }
.seo-guide-card p { font-size: .9rem; color: #64748b; line-height: 1.6; margin: 0; }

/* ─── Tips boxes ─── */
.seo-guide-tip { margin: 20px 0; padding: 16px 20px; border-left: 4px solid #3b82f6; background: rgba(59,130,246,.05); border-radius: 8px; }
.seo-guide-tip.success { border-left-color: #10b981; background: rgba(16,185,129,.05); }
.seo-guide-tip.warning { border-left-color: #f59e0b; background: rgba(245,158,11,.05); }
.seo-guide-tip.danger { border-left-color: #ef4444; background: rgba(239,68,68,.05); }
.seo-guide-tip i { margin-right: 8px; font-weight: 700; }
.seo-guide-tip p { margin: 0; font-size: .9rem; color: #475569; }

/* ─── Listes ─── */
.seo-guide-list { list-style: none; padding: 0; margin: 0; }
.seo-guide-list li { padding: 10px 0 10px 28px; position: relative; color: #475569; font-size: .95rem; line-height: 1.6; }
.seo-guide-list li::before { content: '→'; position: absolute; left: 0; color: #8b5cf6; font-weight: 700; }

/* ─── Table ─── */
.seo-guide-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
.seo-guide-table thead { background: var(--surface-2, #f8fafc); }
.seo-guide-table th { padding: 12px 16px; text-align: left; font-size: .82rem; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid var(--border, #e2e8f0); }
.seo-guide-table td { padding: 12px 16px; border-bottom: 1px solid var(--border, #e2e8f0); font-size: .9rem; color: #475569; }
.seo-guide-table tbody tr:hover { background: rgba(139,92,246,.02); }

/* ─── FAQ ─── */
.seo-guide-faq { margin-top: 24px; }
.seo-guide-faq-item { margin-bottom: 16px; border: 1px solid var(--border, #e5e7eb); border-radius: 10px; overflow: hidden; }
.seo-guide-faq-q { padding: 16px; background: var(--surface-2, #f8fafc); cursor: pointer; display: flex; align-items: center; justify-content: space-between; font-weight: 600; color: #1e293b; user-select: none; transition: all .2s; }
.seo-guide-faq-q:hover { background: rgba(139,92,246,.05); }
.seo-guide-faq-q i { transition: transform .3s; color: #8b5cf6; }
.seo-guide-faq-item.open .seo-guide-faq-q i { transform: rotate(180deg); }
.seo-guide-faq-a { padding: 0 16px; max-height: 0; overflow: hidden; transition: all .3s ease; }
.seo-guide-faq-item.open .seo-guide-faq-a { padding: 0 16px 16px; max-height: 500px; }
.seo-guide-faq-a p { font-size: .9rem; color: #475569; line-height: 1.6; margin: 0; }

/* ─── Responsive ─── */
@media (max-width: 768px) {
    .seo-guide-cards { grid-template-columns: 1fr; }
    .seo-guide-section h2 { font-size: 1.2rem; }
    .seo-guide-table { font-size: .85rem; }
    .seo-guide-table th, .seo-guide-table td { padding: 10px 12px; }
}

/* ─── Code blocks (rares) ─── */
.seo-guide-code { background: #1e293b; color: #e2e8f0; padding: 14px; border-radius: 8px; overflow: auto; margin: 16px 0; font-family: 'Fira Code', monospace; font-size: .78rem; line-height: 1.5; }
</style>

<div class="seo-guide-wrap">

    <!-- ─── Onglets ─── -->
    <div class="seo-guide-tabs">
        <a href="?page=seo&tab=overview" class="seo-guide-tab"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
        <a href="?page=seo&tab=guide" class="seo-guide-tab active"><i class="fas fa-book"></i> Guide SEO</a>
    </div>

    <div class="seo-guide-content">

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- 1. C'EST QUOI LE SEO ?                                        -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <div class="seo-guide-section">
            <h2><i class="fas fa-lightbulb"></i> C'est quoi le SEO ?</h2>
            <p>
                Le <strong>SEO (Search Engine Optimization)</strong> c'est l'art de faire apparaître votre site en première page Google 
                quand quelqu'un tape "vendre appartement Paris" ou "agent immobilier Bordeaux". C'est gratuit contrairement à la pub Google payante.
            </p>
            
            <div class="seo-guide-tip success">
                <i class="fas fa-check-circle"></i>
                <p><strong>Simple :</strong> Plus les gens vous trouvent sur Google, plus vous avez de clients sans dépenser en pub.</p>
            </div>

            <div class="seo-guide-cards" style="margin-top:20px">
                <div class="seo-guide-card">
                    <div class="seo-guide-card-icon blue"><i class="fas fa-search"></i></div>
                    <h3>Google cherche</h3>
                    <p>Quelqu'un tape "estimer ma maison" sur Google</p>
                </div>
                <div class="seo-guide-card">
                    <div class="seo-guide-card-icon violet"><i class="fas fa-rocket"></i></div>
                    <h3>Google propose</h3>
                    <p>Google affiche les meilleurs sites (selon lui) en résultats</p>
                </div>
                <div class="seo-guide-card">
                    <div class="seo-guide-card-icon green"><i class="fas fa-chart-line"></i></div>
                    <h3>Vous gagnez</h3>
                    <p>Si vous êtes en 1ère page → clics gratuits → rendez-vous</p>
                </div>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- 2. POURQUOI C'EST VITAL EN IMMOBILIER                         -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <div class="seo-guide-section">
            <h2><i class="fas fa-check-circle"></i> Pourquoi le SEO en immobilier ?</h2>
            <p>Contrairement à d'autres métiers, l'immobilier a des avantages SEO massifs :</p>
            
            <ul class="seo-guide-list">
                <li><strong>Local fort :</strong> "Agent immobilier + VOTRE VILLE" = très peu de concurrence</li>
                <li><strong>Demande permanente :</strong> Les gens cherchent TOUS LES JOURS à vendre/acheter</li>
                <li><strong>Intent clair :</strong> Qui cherche "estimer maison" a 100% besoin d'un agent</li>
                <li><strong>Peu cher :</strong> Contrairement à Facebook Ads, Google c'est gratuit</li>
                <li><strong>Effet de réseau :</strong> Plus vous publiez d'articles → plus Google vous fait confiance</li>
                <li><strong>Récurrence :</strong> Un client qui vous trouve via Google = client loyal</li>
            </ul>

            <div class="seo-guide-tip warning" style="margin-top:24px">
                <i class="fas fa-chart-bar"></i>
                <p><strong>Chiffres réels :</strong> 70-80% des clients immobiliers commencent par Google. Les réseaux sociaux c'est 10-15%. Ignorer le SEO c'est laisser 80% de votre trafic potentiel à la concurrence.</p>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- 3. LES 3 PILIERS DU SEO                                       -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <div class="seo-guide-section">
            <h2><i class="fas fa-layer-group"></i> Les 3 piliers du SEO</h2>
            <p>Le SEO repose sur 3 piliers. Ignorer l'un = laisser de l'argent sur la table :</p>
            
            <table class="seo-guide-table">
                <thead>
                    <tr>
                        <th>Pilier</th>
                        <th>Ça veut dire quoi</th>
                        <th>En immobilier</th>
                        <th>Impact</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>On-Page</strong></td>
                        <td>Comment vous écrivez vos pages</td>
                        <td>Meta title, titre H1, densité mots-clés, photos optimisées</td>
                        <td style="color:#10b981;font-weight:700;">★★★★★</td>
                    </tr>
                    <tr>
                        <td><strong>Technique</strong></td>
                        <td>Comment votre site fonctionne</td>
                        <td>Vitesse, mobile-friendly, sécurité HTTPS, structure URL</td>
                        <td style="color:#10b981;font-weight:700;">★★★★☆</td>
                    </tr>
                    <tr>
                        <td><strong>Off-Page</strong></td>
                        <td>Ce que d'autres sites disent de vous</td>
                        <td>GMB avis ⭐, publications, backlinks locaux, branding</td>
                        <td style="color:#10b981;font-weight:700;">★★★★★</td>
                    </tr>
                </tbody>
            </table>

            <div style="margin-top:24px; display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:16px;">
                <div class="seo-guide-card">
                    <div class="seo-guide-card-icon blue"><i class="fas fa-file-lines"></i></div>
                    <h3>1️⃣ On-Page SEO</h3>
                    <p><strong>Vous contrôlez</strong> le contenu de vos pages. Meta title, description, H1, images, densité de mots-clés. C'est les <strong>bases</strong>.</p>
                </div>
                <div class="seo-guide-card">
                    <div class="seo-guide-card-icon teal"><i class="fas fa-cogs"></i></div>
                    <h3>2️⃣ Technique</h3>
                    <p><strong>Votre site doit être rapide, sécurisé, mobile-friendly.</strong> Sans ça, même avec du bon contenu, Google vous ignore.</p>
                </div>
                <div class="seo-guide-card">
                    <div class="seo-guide-card-icon green"><i class="fas fa-star"></i></div>
                    <h3>3️⃣ Off-Page (GMB + Avis)</h3>
                    <p><strong>Ce que GOOGLE MY BUSINESS et les avis disent de vous.</strong> Avoir 100 avis ⭐⭐⭐⭐⭐ = signal fort pour Google.</p>
                </div>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- 4. BONNES PRATIQUES PAR TYPE DE PAGE                          -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <div class="seo-guide-section">
            <h2><i class="fas fa-list-check"></i> Bonnes pratiques par type de page</h2>
            
            <h3 style="color:#1e293b;font-weight:700;margin-top:20px;margin-bottom:12px;">📍 Pages Secteur / Quartier</h3>
            <ul class="seo-guide-list">
                <li><strong>Titre unique :</strong> "Agent immobilier Bordeaux - Quartier Bacalan | Vendre & Acheter" (60 car max)</li>
                <li><strong>Description :</strong> Résumé en 155 char de votre offre + localité</li>
                <li><strong>H1 unique :</strong> Une fois par page, le titre principal</li>
                <li><strong>Contenu :</strong> 800-1500 mots minimum. Parlez du quartier, stats marché, transport, écoles</li>
                <li><strong>Photos :</strong> Dénommez-les correctement : "appartement-bacalan-bordeaux.jpg" pas "IMG_1234.jpg"</li>
                <li><strong>Maillage :</strong> Lien vers autres quartiers et page d'accueil</li>
            </ul>

            <h3 style="color:#1e293b;font-weight:700;margin-top:24px;margin-bottom:12px;">📝 Articles / Guides</h3>
            <ul class="seo-guide-list">
                <li><strong>Titre accrocheur :</strong> "Guide complet : comment vendre votre maison 20% plus cher" (meilleur que "Comment vendre")</li>
                <li><strong>Contenu long :</strong> 2000-3000 mots. Google adore les articles profonds</li>
                <li><strong>Structure :</strong> Table des matières, chapitres (H2, H3), conclusion</li>
                <li><strong>Mots-clés :</strong> Incluez variations naturelles (vendre, vente, vendre maison, vendre rapide)</li>
                <li><strong>CTA :</strong> Bouton "Obtenir une estimation" ou "Appeler un expert"</li>
                <li><strong>Fréquence :</strong> 1 article par semaine = croissance SEO rapide</li>
            </ul>

            <h3 style="color:#1e293b;font-weight:700;margin-top:24px;margin-bottom:12px;">🎯 Pages d'Estimation / Capture</h3>
            <ul class="seo-guide-list">
                <li><strong>Titre clair :</strong> "Estimer gratuitement votre bien immobilier"</li>
                <li><strong>Hero image :</strong> Accrocheur + compressé (< 200KB)</li>
                <li><strong>Bénéfices clairs :</strong> "Gratuit. Sans engagement. En 2 min."</li>
                <li><strong>Formulaire court :</strong> 4-6 champs max. Moins = plus de conversions</li>
                <li><strong>Preuve sociale :</strong> "1200 estimations cette année" / "⭐⭐⭐⭐⭐ 4.9 sur 5"</li>
                <li><strong>RGPD clair :</strong> "Votre email ne sera jamais vendu"</li>
            </ul>

            <h3 style="color:#1e293b;font-weight:700;margin-top:24px;margin-bottom:12px;">🏠 Page d'Accueil</h3>
            <ul class="seo-guide-list">
                <li><strong>Meta title :</strong> "Agent immobilier [VOTRE VILLE] - Vendre Acheter Estimer"</li>
                <li><strong>Hero :</strong> Photo qualité + texte clair (une phrase max)</li>
                <li><strong>4 sections clés :</strong> Qui suis-je? / Mes services / Secteurs couverts / Pourquoi moi?</li>
                <li><strong>Trust signals :</strong> Avis Google, année d'expérience, certifications</li>
                <li><strong>CTA principal :</strong> Appel (téléphone) ou formulaire d'estimation</li>
                <li><strong>Contenu minimum :</strong> 500-800 mots. Pas juste du vide avec photos</li>
            </ul>
        </div>

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- 5. GOOGLE MY BUSINESS = SEO LOCAL GRATUIT                     -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <div class="seo-guide-section">
            <h2><i class="fas fa-star"></i> Google My Business (SEO gratuit) ⭐</h2>
            <p>
                <strong>C'est votre carte de visite digitale.</strong> Si quelqu'un tape "agent immobilier + VOTRE VILLE", 
                Google montre votre profil GMB. C'est 10x plus puissant que votre site seul.
            </p>

            <div class="seo-guide-tip success">
                <i class="fas fa-bolt"></i>
                <p><strong>Fait :</strong> Avoir 50 avis ⭐ sur GMB = apparaître en position 1-2 sur Google local, même sans site SEO-optimisé.</p>
            </div>

            <h3 style="color:#1e293b;font-weight:700;margin-top:20px;margin-bottom:12px;">À faire sur GMB :</h3>
            <ul class="seo-guide-list">
                <li><strong>Photo de profil pro :</strong> Vous en costume / studio ≠ selfie avec filtre</li>
                <li><strong>Description complète :</strong> "Agent immobilier spécialisé vente & location [secteurs]"</li>
                <li><strong>Catégories :</strong> "Agent immobilier" + "Consultant en immobilier" (si vous faites estimation)</li>
                <li><strong>2-3 publications/semaine :</strong> Articles, estimations, événements. Pas de silence radio</li>
                <li><strong>Répondre à 100% des avis :</strong> Même négatifs. En moins de 24h. C'est crucial.</li>
                <li><strong>Photos de qualité :</strong> Bureau, équipe, clients satisfaits. Au minimum 10 photos.</li>
                <li><strong>Backlinks locaux :</strong> Lien vers GMB depuis votre site + autres sites locaux</li>
            </ul>

            <div class="seo-guide-tip danger" style="margin-top:20px">
                <i class="fas fa-exclamation-triangle"></i>
                <p><strong>Piège :</strong> Ne mettez JAMAIS le nom de votre ville dans le nom GMB. 
                Google retire 60% de la visibilité sinon. Exemple : ❌ "Agent immobilier Bordeaux DURAND" → ✅ "DURAND - Agent Immobilier"</p>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- 6. CHECKLIST RAPIDE                                           -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <div class="seo-guide-section">
            <h2><i class="fas fa-checkmark-circle"></i> Checklist SEO rapide</h2>
            <p>Avant de publier une page, vérifiez :</p>

            <div style="background:var(--surface-2,#f8fafc);border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:20px">
                <h3 style="color:#1e293b;margin:0 0 16px;font-size:.95rem;font-weight:700">✅ Avant publication</h3>
                <ul class="seo-guide-list">
                    <li><strong>Meta title :</strong> Unique, 50-60 caractères, mot-clé principal en début</li>
                    <li><strong>Meta description :</strong> 155-160 caractères, incitation au clic ("Découvrez...", "Gratuit...")</li>
                    <li><strong>H1 :</strong> Une seule fois par page, résume le sujet</li>
                    <li><strong>URLs :</strong> Court, sans accents, avec tirets (exemple: /guide-vendre-maison, pas /page123)</li>
                    <li><strong>Images :</strong> Compressées (Tinypng), noms explicites, alt-text (accessibilité + SEO)</li>
                    <li><strong>Liens :</strong> Lien vers page d'accueil et autres pages du site</li>
                    <li><strong>Mobile :</strong> Prévisualisez sur téléphone. La plupart des gens utilisent mobile</li>
                    <li><strong>Contenu :</strong> Min 300 mots. Pas de copier-coller (Google détecte et pénalise)</li>
                    <li><strong>CTA clair :</strong> Bouton visible en haut + bas. "Estimer" / "Appeler" / "Formulaire"</li>
                </ul>
            </div>

            <div style="background:rgba(16,185,129,.05);border:1px solid rgba(16,185,129,.2);border-radius:12px;padding:20px;margin-top:16px">
                <h3 style="color:#059669;margin:0 0 16px;font-size:.95rem;font-weight:700">✅ Après publication (récurrent)</h3>
                <ul class="seo-guide-list">
                    <li><strong>Google Search Console :</strong> Vérifier erreurs, ajouter sitemap</li>
                    <li><strong>Google Analytics :</strong> Quel type de pages apporte le plus de trafic?</li>
                    <li><strong>GMB :</strong> Répondre avis, publier 2-3x par semaine</li>
                    <li><strong>Backlinks locaux :</strong> 1 nouveau lien par mois minimum (partenaires, annuaires)</li>
                    <li><strong>Contenu :</strong> Mettre à jour pages > 6 mois. Ajouter infos récentes / stats</li>
                </ul>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- 7. FAQ — LES VRAIES QUESTIONS                                -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <div class="seo-guide-section">
            <h2><i class="fas fa-question-circle"></i> Questions que vous vous posez</h2>
            
            <div class="seo-guide-faq">
                <div class="seo-guide-faq-item">
                    <div class="seo-guide-faq-q">
                        <span>Combien de temps avant de voir des résultats SEO?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="seo-guide-faq-a">
                        <p>
                            <strong>3-6 mois</strong> pour premières positions. Mais c'est progressif :
                            Mois 1-2: rien visible. Mois 3: 5-10 visiteurs/jour. Mois 6: 20-50 visiteurs/jour.
                            <strong>L'avantage :</strong> contrairement à Google Ads, une fois que vous êtes en position 1, 
                            vous avez du trafic gratuit à perpétuité.
                        </p>
                    </div>
                </div>

                <div class="seo-guide-faq-item">
                    <div class="seo-guide-faq-q">
                        <span>Faut-il vraiment écrire des articles? Je vends des biens, pas des livres...</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="seo-guide-faq-a">
                        <p>
                            <strong>OUI, absolument.</strong> Les articles répondent aux questions des clients AVANT qu'ils vous contactent.
                            Exemple : quelqu'un tape "Comment estimer sa maison?" → votre article "Guide : 5 critères pour estimer" 
                            → confiance établie → ils vous contactent. C'est du marketing gratuit.
                            <strong>15 articles de qualité</strong> = trafic stable sans dépendre de Facebook Ads.
                        </p>
                    </div>
                </div>

                <div class="seo-guide-faq-item">
                    <div class="seo-guide-faq-q">
                        <span>Est-ce que Facebook Ads est mieux que SEO?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="seo-guide-faq-a">
                        <p>
                            Non. Facebook Ads c'est comme louer une place au supermarché.
                            Vous arrêtez de payer → plus de clients. SEO c'est acheter votre place définitivement.
                            Idéal : <strong>SEO 70% + Ads 30%.</strong> D'abord construisez votre SEO (3-6 mois), 
                            puis utilisez Ads pour accélérer.
                        </p>
                    </div>
                </div>

                <div class="seo-guide-faq-item">
                    <div class="seo-guide-faq-q">
                        <span>Les avis Google, c'est vraiment important?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="seo-guide-faq-a">
                        <p>
                            <strong>ULTRA important. Vous ne réalisez pas.</strong>
                            50 avis 5⭐ sur GMB = vous apparaissez position 1-2, automatiquement.
                            30 avis 3⭐ = position 5-8. Les avis c'est le signal le plus puissant après contenu.
                            Demandez systématiquement après chaque RDV réussi : "Pourriez-vous laisser un avis Google?" + lien.
                        </p>
                    </div>
                </div>

                <div class="seo-guide-faq-item">
                    <div class="seo-guide-faq-q">
                        <span>Mon concurrent est en position 1. Comment le dépasser?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="seo-guide-faq-a">
                        <p>
                            <strong>3 actions :</strong>
                            1) Écrivez mieux que lui (plus long, plus détaillé, meilleur contenu)
                            2) Accumulez plus d'avis Google que lui (c'est votre arme secrète)
                            3) Construisez des backlinks locaux (partenaires locaux, annuaires)
                            En 6 mois vous le dépasserez. Le SEO c'est une guerre longue, pas un sprint.
                        </p>
                    </div>
                </div>

                <div class="seo-guide-faq-item">
                    <div class="seo-guide-faq-q">
                        <span>Je dois faire SEO ou juste payer des pubs Google?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="seo-guide-faq-a">
                        <p>
                            <strong>Les deux, mais pas au même moment.</strong>
                            Mois 1-3 : SEO seul (gratuit, contenu + GMB)
                            Mois 3-6 : SEO + Ads (booster avec 200€/mois)
                            Mois 6+ : 70% SEO, 30% Ads pour monter à l'échelle
                            L'erreur : Dépenser 500€/mois en Ads quand votre site SEO n'existe pas. C'est gaspiller l'argent.
                        </p>
                    </div>
                </div>

                <div class="seo-guide-faq-item">
                    <div class="seo-guide-faq-q">
                        <span>Je peux copier du contenu de mes concurrents?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="seo-guide-faq-a">
                        <p>
                            <strong>JAMAIS.</strong> Google détecte avec IA et vous pénalise fortement.
                            Vous perdrez positions et trafic en 2 semaines.
                            Écrivez votre propre contenu ou demandez à une IA de générer, puis relisez et personnalisez.
                            Authentique = confiance = Google l'aime.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- 8. RESSOURCES UTILES                                          -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <div class="seo-guide-section">
            <h2><i class="fas fa-book"></i> Ressources gratuites</h2>
            
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px">
                <div class="seo-guide-card">
                    <div class="seo-guide-card-icon blue"><i class="fas fa-tools"></i></div>
                    <h3>Google Search Console</h3>
                    <p>Gratuit. Voir comment Google voit votre site, erreurs, mots-clés que vous classez.</p>
                </div>
                <div class="seo-guide-card">
                    <div class="seo-guide-card-icon violet"><i class="fas fa-chart-line"></i></div>
                    <h3>Google Analytics</h3>
                    <p>Gratuit. Voir d'où vient votre trafic, quel contenu performe, taux de conversion.</p>
                </div>
                <div class="seo-guide-card">
                    <div class="seo-guide-card-icon green"><i class="fas fa-image"></i></div>
                    <h3>TinyPNG</h3>
                    <p>Compresser vos images. Site rapide = meilleur SEO. Gratuit jusqu'à 20 images/mois.</p>
                </div>
                <div class="seo-guide-card">
                    <div class="seo-guide-card-icon pink"><i class="fas fa-star"></i></div>
                    <h3>Google My Business</h3>
                    <p>Gratuit. Votre fiche locale. C'est une mine d'or qu'on ignore.</p>
                </div>
                <div class="seo-guide-card">
                    <div class="seo-guide-card-icon teal"><i class="fas fa-palette"></i></div>
                    <h3>Coolors</h3>
                    <p>Générer palettes de couleurs. Votre branding importe pour confiance.</p>
                </div>
                <div class="seo-guide-card">
                    <div class="seo-guide-card-icon blue"><i class="fas fa-code"></i></div>
                    <h3>Ubersuggest (payant)</h3>
                    <p>Trouver mots-clés, analyser concurrents. 12€/mois. Très bon ROI.</p>
                </div>
            </div>
        </div>

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- 9. CAS D'USAGE RÉELS                                          -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <div class="seo-guide-section">
            <h2><i class="fas fa-case"></i> Cas d'usage réels en immobilier</h2>
            
            <h3 style="color:#1e293b;font-weight:700;margin-top:20px;margin-bottom:12px;">Cas 1 : Agent seul en petite ville</h3>
            <p style="color:#475569">
                <strong>Stratégie :</strong> Créer pages par quartier (5-10 pages) + 10 articles de blog + GMB optimisé.
                <br><strong>Temps :</strong> 8h sur 6 mois (1h par semaine).
                <br><strong>Résultat :</strong> 30-50 visiteurs/jour → 2-3 nouveaux clients/mois = 6000€-9000€/mois revenu supplémentaire.
            </p>

            <h3 style="color:#1e293b;font-weight:700;margin-top:20px;margin-bottom:12px;">Cas 2 : Agence 3-5 agents</h3>
            <p style="color:#475569">
                <strong>Stratégie :</strong> Chaque agent sa page + pages quartier + articles réguliers.
                <br><strong>Temps :</strong> 4h/semaine (1 personne dédiée SEO).
                <br><strong>Résultat :</strong> 150-250 visiteurs/jour → Domination locale → 10-15 nouveaux clients/mois.
            </p>

            <h3 style="color:#1e293b;font-weight:700;margin-top:20px;margin-bottom:12px;">Cas 3 : Agence nationale (8+ agents)</h3>
            <p style="color:#475569">
                <strong>Stratégie :</strong> CMS avec 50+ pages templates + 1 article/semaine + blog continu.
                <br><strong>Temps :</strong> 10h/semaine (équipe marketing).
                <br><strong>Résultat :</strong> 500-1000 visiteurs/jour → Domination nationale + local → 30-50 nouveaux clients/mois.
            </p>
        </div>

        <!-- ═════════════════════════════════════════════════════════════ -->
        <!-- 10. TL;DR — LE MINIMUM À FAIRE                               -->
        <!-- ═════════════════════════════════════════════════════════════ -->
        <div class="seo-guide-section">
            <h2><i class="fas fa-rocket"></i> TL;DR — Commencez par ça</h2>
            
            <div style="background:linear-gradient(135deg,rgba(139,92,246,.08),rgba(236,72,153,.06));border:1px solid rgba(139,92,246,.2);border-radius:14px;padding:24px;margin-top:16px">
                <h3 style="color:#1e293b;margin-top:0;margin-bottom:16px;font-size:1.05rem">Les 5 actions à faire cette semaine :</h3>
                
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <div style="width:30px;height:30px;background:#8b5cf6;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700">1</div>
                        <div>
                            <strong>GMB optimisé :</strong> Photo pro, description, catégories, horaires, téléphone visible
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <div style="width:30px;height:30px;background:#8b5cf6;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700">2</div>
                        <div>
                            <strong>Page d'accueil :</strong> Meta title + description + H1 + 500 mots de qualité
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <div style="width:30px;height:30px;background:#8b5cf6;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700">3</div>
                        <div>
                            <strong>1 article SEO :</strong> "Guide : comment vendre votre maison [VILLE]" (2000 mots)
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <div style="width:30px;height:30px;background:#8b5cf6;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700">4</div>
                        <div>
                            <strong>Google Search Console :</strong> Ajouter site + sitemap + vérifier erreurs
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <div style="width:30px;height:30px;background:#8b5cf6;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700">5</div>
                        <div>
                            <strong>Demander avis :</strong> Système pour récupérer 5-10 avis Google/mois
                        </div>
                    </div>
                </div>

                <p style="margin-top:20px;color:#475569;font-size:.9rem">
                    Voilà. Fait ça → dans 3-6 mois, vous verrez du trafic. C'est long mais gratuit à perpétuité.
                </p>
            </div>
        </div>

    </div><!-- /seo-guide-content -->
</div><!-- /seo-guide-wrap -->

<!-- ─── FAQ Accordion JS ─── -->
<script>
document.querySelectorAll('.seo-guide-faq-item').forEach(item => {
    item.querySelector('.seo-guide-faq-q').addEventListener('click', () => {
        item.classList.toggle('open');
    });
});
</script>