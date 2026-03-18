<?php
/**
 * /admin/modules/seo/guide.php
 * Guide SEO pédagogique — Explications simples pour non-marketeurs
 */
?>

<style>
.seo-guide-wrap { font-family: var(--font, 'Inter', sans-serif); }

.seo-guide-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--border, #e5e7eb); margin-bottom: 24px; }
.seo-guide-tab { padding: 12px 20px; font-size: .85rem; font-weight: 600; color: var(--text-2, #6b7280); cursor: pointer; border-bottom: 3px solid transparent; transition: all .2s; text-decoration: none; display: flex; align-items: center; gap: 6px; }
.seo-guide-tab:hover { color: var(--text, #111827); }
.seo-guide-tab.active { color: #8b5cf6; border-bottom-color: #8b5cf6; }

.seo-guide-content { max-width: 920px; }

.seo-guide-section { margin-bottom: 40px; }
.seo-guide-section h2 { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin: 0 0 12px; display: flex; align-items: center; gap: 12px; }
.seo-guide-section h2 i { font-size: 1.2rem; }
.seo-guide-section p { font-size: .95rem; color: #475569; line-height: 1.7; margin: 0 0 16px; }

.seo-guide-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-top: 20px; }
.seo-guide-card { background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 14px; padding: 20px; transition: all .2s; }
.seo-guide-card:hover { border-color: #8b5cf6; box-shadow: 0 4px 20px rgba(139,92,246,.1); }
.seo-guide-card-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px; }
.seo-guide-card-icon.blue { background: rgba(59,130,246,.1); color: #3b82f6; }
.seo-guide-card-icon.violet { background: rgba(139,92,246,.1); color: #8b5cf6; }
.seo-guide-card-icon.green { background: rgba(16,185,129,.1); color: #10b981; }
.seo-guide-card h3 { font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0 0 8px; }
.seo-guide-card p { font-size: .9rem; color: #64748b; line-height: 1.6; margin: 0; }

.seo-guide-tip { margin: 20px 0; padding: 16px 20px; border-left: 4px solid #3b82f6; background: rgba(59,130,246,.05); border-radius: 8px; }
.seo-guide-tip.success { border-left-color: #10b981; background: rgba(16,185,129,.05); }
.seo-guide-tip.warning { border-left-color: #f59e0b; background: rgba(245,158,11,.05); }
.seo-guide-tip i { margin-right: 8px; font-weight: 700; }
.seo-guide-tip p { margin: 0; font-size: .9rem; color: #475569; }

.seo-guide-list { list-style: none; padding: 0; margin: 0; }
.seo-guide-list li { padding: 10px 0 10px 28px; position: relative; color: #475569; font-size: .95rem; line-height: 1.6; }
.seo-guide-list li::before { content: '→'; position: absolute; left: 0; color: #8b5cf6; font-weight: 700; }

.seo-guide-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
.seo-guide-table thead { background: var(--surface-2, #f8fafc); }
.seo-guide-table th { padding: 12px 16px; text-align: left; font-size: .82rem; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid var(--border, #e2e8f0); }
.seo-guide-table td { padding: 12px 16px; border-bottom: 1px solid var(--border, #e2e8f0); font-size: .9rem; color: #475569; }
.seo-guide-table tbody tr:hover { background: rgba(139,92,246,.02); }

@media (max-width: 768px) {
    .seo-guide-cards { grid-template-columns: 1fr; }
    .seo-guide-section h2 { font-size: 1.2rem; }
}
</style>

<div class="seo-guide-wrap">

    <div class="seo-guide-tabs">
        <a href="?page=seo&tab=overview" class="seo-guide-tab"><i class="fas fa-th-large"></i> Vue d'ensemble</a>
        <a href="?page=seo&tab=guide" class="seo-guide-tab active"><i class="fas fa-book"></i> Guide SEO</a>
    </div>

    <div class="seo-guide-content">

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
        </div>

        <div class="seo-guide-section">
            <h2><i class="fas fa-check-circle"></i> Pourquoi le SEO en immobilier ?</h2>
            <p>Contrairement à d'autres métiers, l'immobilier a des avantages SEO massifs :</p>
            
            <ul class="seo-guide-list">
                <li><strong>Local fort :</strong> "Agent immobilier + VOTRE VILLE" = très peu de concurrence</li>
                <li><strong>Demande permanente :</strong> Les gens cherchent TOUS LES JOURS à vendre/acheter</li>
                <li><strong>Intent clair :</strong> Qui cherche "estimer maison" a 100% besoin d'un agent</li>
                <li><strong>Peu cher :</strong> Contrairement à Facebook Ads, Google c'est gratuit</li>
            </ul>

            <div class="seo-guide-tip warning" style="margin-top:24px">
                <i class="fas fa-chart-bar"></i>
                <p><strong>Chiffres réels :</strong> 70-80% des clients immobiliers commencent par Google.</p>
            </div>
        </div>

        <div class="seo-guide-section">
            <h2><i class="fas fa-star"></i> Les 3 piliers du SEO</h2>
            
            <table class="seo-guide-table">
                <thead>
                    <tr>
                        <th>Pilier</th>
                        <th>Ça veut dire quoi</th>
                        <th>En immobilier</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>On-Page</strong></td>
                        <td>Comment vous écrivez vos pages</td>
                        <td>Meta title, H1, densité mots-clés, photos</td>
                    </tr>
                    <tr>
                        <td><strong>Technique</strong></td>
                        <td>Comment votre site fonctionne</td>
                        <td>Vitesse, mobile-friendly, HTTPS, structure URL</td>
                    </tr>
                    <tr>
                        <td><strong>Off-Page</strong></td>
                        <td>Ce que d'autres sites disent de vous</td>
                        <td>GMB avis ⭐, publications, backlinks locaux</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="seo-guide-section">
            <h2><i class="fas fa-list-check"></i> Checklist SEO rapide</h2>
            
            <div style="background:var(--surface-2,#f8fafc);border:1px solid var(--border,#e5e7eb);border-radius:12px;padding:20px">
                <h3 style="color:#1e293b;margin:0 0 16px;font-size:.95rem;font-weight:700">✅ Avant de publier une page</h3>
                <ul class="seo-guide-list">
                    <li><strong>Meta title :</strong> Unique, 50-60 caractères, mot-clé principal en début</li>
                    <li><strong>Meta description :</strong> 155-160 caractères, incitation au clic</li>
                    <li><strong>H1 :</strong> Une seule fois par page, résume le sujet</li>
                    <li><strong>URLs :</strong> Court, sans accents, avec tirets (exemple: /guide-vendre-maison)</li>
                    <li><strong>Images :</strong> Compressées, noms explicites, alt-text</li>
                    <li><strong>Contenu :</strong> Min 300 mots. Pas de copier-coller</li>
                    <li><strong>CTA clair :</strong> Bouton visible en haut + bas</li>
                </ul>
            </div>
        </div>

        <div class="seo-guide-section">
            <h2><i class="fas fa-star"></i> Google My Business = SEO gratuit</h2>
            <p>
                <strong>C'est votre carte de visite digitale.</strong> Si quelqu'un tape "agent immobilier + VOTRE VILLE", 
                Google montre votre profil GMB. C'est 10x plus puissant que votre site seul.
            </p>

            <div class="seo-guide-tip success">
                <i class="fas fa-bolt"></i>
                <p><strong>Fait :</strong> Avoir 50 avis ⭐ sur GMB = apparaître en position 1-2 sur Google local.</p>
            </div>

            <h3 style="color:#1e293b;font-weight:700;margin-top:20px;margin-bottom:12px;">À faire sur GMB :</h3>
            <ul class="seo-guide-list">
                <li><strong>Photo de profil pro :</strong> Vous en costume / studio</li>
                <li><strong>Description complète :</strong> Spécialités, secteurs</li>
                <li><strong>2-3 publications/semaine :</strong> Articles, estimations, événements</li>
                <li><strong>Répondre à 100% des avis :</strong> Même négatifs. En moins de 24h.</li>
                <li><strong>Photos de qualité :</strong> Bureau, équipe, clients satisfaits</li>
            </ul>
        </div>

        <div class="seo-guide-section">
            <h2><i class="fas fa-rocket"></i> TL;DR — Commencez par ça</h2>
            
            <div style="background:linear-gradient(135deg,rgba(139,92,246,.08),rgba(236,72,153,.06));border:1px solid rgba(139,92,246,.2);border-radius:14px;padding:24px;margin-top:16px">
                <h3 style="color:#1e293b;margin-top:0;margin-bottom:16px;font-size:1.05rem">Les 5 actions à faire cette semaine :</h3>
                
                <div style="display:flex;flex-direction:column;gap:12px">
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <div style="width:30px;height:30px;background:#8b5cf6;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700">1</div>
                        <div><strong>GMB optimisé :</strong> Photo pro, description, catégories, horaires, téléphone</div>
                    </div>
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <div style="width:30px;height:30px;background:#8b5cf6;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700">2</div>
                        <div><strong>Page d'accueil :</strong> Meta title + description + H1 + 500 mots de qualité</div>
                    </div>
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <div style="width:30px;height:30px;background:#8b5cf6;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700">3</div>
                        <div><strong>1 article SEO :</strong> "Guide : comment vendre votre maison [VILLE]" (2000 mots)</div>
                    </div>
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <div style="width:30px;height:30px;background:#8b5cf6;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700">4</div>
                        <div><strong>Google Search Console :</strong> Ajouter site + sitemap + vérifier erreurs</div>
                    </div>
                    <div style="display:flex;gap:10px;align-items:flex-start">
                        <div style="width:30px;height:30px;background:#8b5cf6;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700">5</div>
                        <div><strong>Demander avis :</strong> Système pour récupérer 5-10 avis Google/mois</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>
