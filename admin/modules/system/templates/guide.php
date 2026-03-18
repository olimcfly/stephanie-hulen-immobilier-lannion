<?php
/**
 * /admin/modules/system/templates/guide.php
 * Onglet guide détaillé — "Comment ça marche"
 * Affiche des explications, cas d'usage, bonnes pratiques
 */
?>

<style>
.stpl-guide-wrap { font-family: 'DM Sans', sans-serif; }

/* ── Onglets ── */
.stpl-tabs { display: flex; gap: 0; border-bottom: 2px solid var(--border, #e5e7eb); margin-bottom: 24px; }
.stpl-tab { padding: 12px 20px; font-size: .85rem; font-weight: 600; color: var(--text-2, #6b7280); cursor: pointer; border-bottom: 3px solid transparent; transition: all .2s; text-decoration: none; display: flex; align-items: center; gap: 6px; }
.stpl-tab:hover { color: var(--text, #111827); }
.stpl-tab.active { color: #8b5cf6; border-bottom-color: #8b5cf6; }

/* ── Contenu ── */
.stpl-guide-content { max-width: 1000px; }

/* ── Section ── */
.stpl-section-guide { margin-bottom: 40px; }
.stpl-section-guide h2 { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin: 0 0 12px; display: flex; align-items: center; gap: 12px; }
.stpl-section-guide h2 i { font-size: 1.2rem; }
.stpl-section-guide p { font-size: .95rem; color: #475569; line-height: 1.7; margin: 0 0 16px; }

/* ── Card guide ── */
.stpl-guide-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
.stpl-guide-card { background: var(--surface, #fff); border: 1px solid var(--border, #e5e7eb); border-radius: 14px; padding: 24px; transition: all .2s; }
.stpl-guide-card:hover { border-color: #8b5cf6; box-shadow: 0 4px 20px rgba(139,92,246,.1); }
.stpl-guide-card-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 16px; }
.stpl-guide-card-icon.blue { background: rgba(59,130,246,.1); color: #3b82f6; }
.stpl-guide-card-icon.violet { background: rgba(139,92,246,.1); color: #8b5cf6; }
.stpl-guide-card-icon.pink { background: rgba(219,39,119,.1); color: #db2777; }
.stpl-guide-card-icon.teal { background: rgba(13,148,136,.1); color: #0d9488; }
.stpl-guide-card h3 { font-size: 1.05rem; font-weight: 700; color: #1e293b; margin: 0 0 10px; }
.stpl-guide-card p { font-size: .9rem; color: #64748b; line-height: 1.6; margin: 0; }

/* ── Encadrés ── */
.stpl-tip { margin: 20px 0; padding: 16px 20px; border-left: 4px solid #3b82f6; background: rgba(59,130,246,.05); border-radius: 8px; }
.stpl-tip.success { border-left-color: #10b981; background: rgba(16,185,129,.05); }
.stpl-tip.warning { border-left-color: #f59e0b; background: rgba(245,158,11,.05); }
.stpl-tip i { margin-right: 8px; font-weight: 700; }
.stpl-tip p { margin: 0; font-size: .9rem; color: #475569; }

/* ── Code ── */
.stpl-code { background: #1e293b; color: #e2e8f0; padding: 16px; border-radius: 10px; overflow: auto; margin: 16px 0; font-family: 'Fira Code', monospace; font-size: .82rem; line-height: 1.5; }
.stpl-code-label { font-size: .75rem; color: #94a3b8; margin-bottom: 8px; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }

/* ── Listes ── */
.stpl-guide-list { list-style: none; padding: 0; }
.stpl-guide-list li { padding: 10px 0 10px 28px; position: relative; color: #475569; font-size: .95rem; line-height: 1.6; }
.stpl-guide-list li::before { content: '→'; position: absolute; left: 0; color: #8b5cf6; font-weight: 700; }

/* ── Tableau ── */
.stpl-guide-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
.stpl-guide-table thead { background: var(--surface-2, #f8fafc); }
.stpl-guide-table th { padding: 12px 16px; text-align: left; font-size: .82rem; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: .04em; border-bottom: 2px solid var(--border, #e2e8f0); }
.stpl-guide-table td { padding: 12px 16px; border-bottom: 1px solid var(--border, #e2e8f0); font-size: .9rem; color: #475569; }
.stpl-guide-table tbody tr:hover { background: rgba(139,92,246,.02); }

/* ── Responsive ── */
@media (max-width: 768px) {
    .stpl-guide-cards { grid-template-columns: 1fr; }
    .stpl-section-guide h2 { font-size: 1.2rem; }
}
</style>

<div class="stpl-guide-wrap">

<!-- ─── Onglets ─── -->
<div class="stpl-tabs">
    <a href="?page=system/templates&tab=templates" class="stpl-tab"><i class="fas fa-palette"></i> Templates</a>
    <a href="?page=system/templates&tab=guide" class="stpl-tab active"><i class="fas fa-lightbulb"></i> Guide</a>
</div>

<div class="stpl-guide-content">

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- 1. C'EST QUOI UN TEMPLATE ?                                 -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="stpl-section-guide">
    <h2><i class="fas fa-puzzle-piece"></i> C'est quoi un template ?</h2>
    <p>Un template est un <strong>modèle de mise en page réutilisable</strong>. Au lieu de créer le code HTML/CSS de zéro pour chaque page, vous choisissez un template prédéfini et vous remplissez simplement le contenu spécifique (titre, images, texte, etc.).</p>
    
    <div class="stpl-guide-cards">
        <div class="stpl-guide-card">
            <div class="stpl-guide-card-icon violet"><i class="fas fa-file-lines"></i></div>
            <h3>Templates de Pages</h3>
            <p>Le design et la structure de vos pages : accueil, secteurs, guides, estimations, captures, etc.</p>
        </div>
        <div class="stpl-guide-card">
            <div class="stpl-guide-card-icon blue"><i class="fas fa-window-maximize"></i></div>
            <h3>Templates Header</h3>
            <p>La barre de navigation qui apparaît <strong>en haut de chaque page</strong>. Partagée par tout le site.</p>
        </div>
        <div class="stpl-guide-card">
            <div class="stpl-guide-card-icon pink"><i class="fas fa-window-minimize"></i></div>
            <h3>Templates Footer</h3>
            <p>Le pied de page avec vos infos de contact, liens utiles, etc. Partagée par tout le site.</p>
        </div>
    </div>

    <div class="stpl-tip warning">
        <i class="fas fa-lightbulb"></i>
        <p><strong>En pratique :</strong> Vous créez 1 header et 1 footer que vous utilisez partout. Mais pour les pages, vous pouvez créer plusieurs templates différents selon le besoin (accueil ≠ secteur ≠ guide).</p>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- 2. POURQUOI UTILISER DES TEMPLATES ?                        -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="stpl-section-guide">
    <h2><i class="fas fa-check-circle"></i> Pourquoi utiliser des templates ?</h2>
    <p>Les templates vous font gagner du temps et évitent les erreurs :</p>
    
    <ul class="stpl-guide-list">
        <li><strong>Rapidité :</strong> Au lieu de coder 30 min par page, vous remplissez 2 min de contenu</li>
        <li><strong>Cohérence visuelle :</strong> Toutes vos pages ont le même look professionnel</li>
        <li><strong>Pas de code :</strong> Vous n'avez jamais besoin de toucher du code HTML/CSS</li>
        <li><strong>Modification facile :</strong> Changez la couleur une fois dans le template → ça s'applique partout</li>
        <li><strong>Mobile-friendly :</strong> Les templates sont déjà testés sur téléphone/tablet</li>
        <li><strong>SEO optimisé :</strong> Les templates incluent déjà les bonnes pratiques SEO</li>
    </ul>

    <div class="stpl-tip success">
        <i class="fas fa-bolt"></i>
        <p><strong>Exemple :</strong> Vous avez 5 quartiers à couvrir. Créez un template "Secteur", puis créez 5 pages en 15 minutes (remplir le nom du quartier, la description, l'image). Sans template, c'aurait pris 2h!</p>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- 3. LES TEMPLATES DISPONIBLES                                -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="stpl-section-guide">
    <h2><i class="fas fa-layer-group"></i> Les templates disponibles</h2>
    <p>Voici les types de templates que vous trouverez chez IMMO LOCAL+ :</p>
    
    <table class="stpl-guide-table">
        <thead>
            <tr>
                <th>Template</th>
                <th>Cas d'usage</th>
                <th>Exemple</th>
                <th>Éléments clés</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Accueil</strong></td>
                <td>Page d'entrée du site</td>
                <td>yoursite.com/</td>
                <td>Hero, bénéfices, 4 étapes, CTA</td>
            </tr>
            <tr>
                <td><strong>Secteur</strong></td>
                <td>Page par quartier/zone</td>
                <td>yoursite.com/quartier-xyz</td>
                <td>Présentation, stats marché, photos, CTA</td>
            </tr>
            <tr>
                <td><strong>Guide</strong></td>
                <td>Contenu long pour SEO</td>
                <td>yoursite.com/guide-vendre</td>
                <td>Titre, table des matières, chapitres, CTA</td>
            </tr>
            <tr>
                <td><strong>Estimation</strong></td>
                <td>Votre outil de capture principal</td>
                <td>yoursite.com/estimation</td>
                <td>Formulaire, calcul, résultats</td>
            </tr>
            <tr>
                <td><strong>Capture</strong></td>
                <td>Pages avec formulaire</td>
                <td>yoursite.com/telecharger-guide</td>
                <td>Titre accrocheur, formulaire, PDF/email</td>
            </tr>
            <tr>
                <td><strong>Header</strong></td>
                <td>Navigation (partagée)</td>
                <td>En haut de chaque page</td>
                <td>Logo, menu, recherche</td>
            </tr>
            <tr>
                <td><strong>Footer</strong></td>
                <td>Pied de page (partagée)</td>
                <td>En bas de chaque page</td>
                <td>Contact, social, liens, CGV</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- 4. COMMENT CRÉER UNE PAGE AVEC UN TEMPLATE                  -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="stpl-section-guide">
    <h2><i class="fas fa-wand-magic-sparkles"></i> Comment créer une page avec un template</h2>
    <p>Voici le processus complet :</p>
    
    <ol class="stpl-guide-list" style="list-style:decimal;padding-left:24px;margin-top:20px">
        <li>Allez dans l'onglet <strong>Pages</strong> du module Pages/Contenus</li>
        <li>Cliquez sur <strong>"Nouvelle page"</strong></li>
        <li>Sélectionnez un <strong>template</strong> dans la liste (ex: Secteur)</li>
        <li>Remplissez les champs : titre, slug, description, contenu spécifique</li>
        <li>Chargez les images si nécessaire</li>
        <li>Publiez la page</li>
        <li>✅ Votre page est en ligne!</li>
    </ol>

    <div class="stpl-tip">
        <i class="fas fa-info-circle"></i>
        <p><strong>Note :</strong> Le template vous fournit la structure et le design. Vous ne changez jamais le template lui-même. Si vous voulez adapter la présentation générale, vous modifiez le template une seule fois, et ça s'applique à toutes les pages utilisant ce template.</p>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- 5. QUAND MODIFIER UN TEMPLATE ?                             -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="stpl-section-guide">
    <h2><i class="fas fa-tools"></i> Quand modifier un template ?</h2>
    <p>Vous n'avez généralement <strong>pas besoin de modifier les templates</strong>. Mais voici les rares cas où vous pourriez vouloir le faire :</p>
    
    <div class="stpl-guide-cards">
        <div class="stpl-guide-card">
            <div class="stpl-guide-card-icon teal"><i class="fas fa-palette"></i></div>
            <h3>Changer les couleurs</h3>
            <p>Pour adapter à vos couleurs de brand ou au marché local.</p>
        </div>
        <div class="stpl-guide-card">
            <div class="stpl-guide-card-icon teal"><i class="fas fa-text-height"></i></div>
            <h3>Modifier les fonts</h3>
            <p>Utiliser une police différente selon votre image.</p>
        </div>
        <div class="stpl-guide-card">
            <div class="stpl-guide-card-icon teal"><i class="fas fa-shapes"></i></div>
            <h3>Réorganiser les sections</h3>
            <p>Changer l'ordre des éléments (ex: déplacer CTA plus haut).</p>
        </div>
        <div class="stpl-guide-card">
            <div class="stpl-guide-card-icon teal"><i class="fas fa-cube"></i></div>
            <h3>Ajouter des éléments</h3>
            <p>Un formulaire, une vidéo, une section supplémentaire.</p>
        </div>
    </div>

    <div class="stpl-tip warning">
        <i class="fas fa-exclamation-triangle"></i>
        <p><strong>Attention :</strong> Si vous modifiez un template, les changements s'appliquent à <strong>toutes les pages</strong> utilisant ce template. Soyez prudent!</p>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- 6. CRÉER UN NOUVEAU TEMPLATE                                -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="stpl-section-guide">
    <h2><i class="fas fa-plus-circle"></i> Créer un nouveau template (avancé)</h2>
    <p>Si aucun template ne correspond à votre besoin, vous pouvez en créer un nouveau :</p>
    
    <ol class="stpl-guide-list" style="list-style:decimal;padding-left:24px;margin-top:20px">
        <li>Allez dans <strong>System → Design → Templates</strong></li>
        <li>Cliquez sur <strong>"Nouveau template"</strong></li>
        <li>Donnez-lui un nom (ex: "Promotion été")</li>
        <li>Choisissez le type : Page, Header ou Footer</li>
        <li>Écrivez le HTML/CSS (ou demandez à un développeur)</li>
        <li>Testez sur desktop et mobile</li>
        <li>Sauvegardez</li>
    </ol>

    <div class="stpl-tip warning">
        <i class="fas fa-code"></i>
        <p><strong>Conseil :</strong> Ne créez un template custom que si vous êtes à l'aise avec le HTML/CSS. Sinon, utiliser les templates existants est largement suffisant pour 95% des cas!</p>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- 7. BONNES PRATIQUES                                         -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="stpl-section-guide">
    <h2><i class="fas fa-star"></i> Bonnes pratiques</h2>
    
    <div class="stpl-guide-cards">
        <div class="stpl-guide-card">
            <div class="stpl-guide-card-icon blue"><i class="fas fa-check"></i></div>
            <h3>✅ À faire</h3>
            <ul class="stpl-guide-list">
                <li>Utiliser les templates fournis</li>
                <li>Créer 5-8 templates max</li>
                <li>Tester sur mobile</li>
                <li>Duplication avant modif</li>
                <li>Documenter vos templates</li>
            </ul>
        </div>
        <div class="stpl-guide-card">
            <div class="stpl-guide-card-icon pink"><i class="fas fa-times"></i></div>
            <h3>❌ À éviter</h3>
            <ul class="stpl-guide-list">
                <li>Créer trop de templates</li>
                <li>Templates identiques</li>
                <li>Oublier de tester</li>
                <li>Modifier un template en prod</li>
                <li>Laisser templates inutilisés</li>
            </ul>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- 8. FAQ                                                      -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="stpl-section-guide">
    <h2><i class="fas fa-question-circle"></i> Questions fréquentes</h2>
    
    <div style="margin-top:20px">
        <h3 style="color:#1e293b;font-weight:700;margin-top:20px">Est-ce que je dois modifier les templates ?</h3>
        <p style="color:#475569">Non, pas nécessaire. Les templates sont déjà prêts à l'emploi et optimisés pour le mobile et le SEO. Utilisez-les tels quels et concentrez-vous sur votre contenu.</p>
        
        <h3 style="color:#1e293b;font-weight:700;margin-top:20px">Puis-je dupliquer un template ?</h3>
        <p style="color:#475569">Oui! C'est même recommandé si vous voulez faire une version légèrement différente (ex: une version "promo" du template Accueil). Cliquez sur l'icône "copier" dans la liste.</p>
        
        <h3 style="color:#1e293b;font-weight:700;margin-top:20px">Que se passe-t-il si je supprime un template ?</h3>
        <p style="color:#475569">Les pages déjà créées avec ce template restent en place. Mais vous ne pourrez plus créer de nouvelles pages avec ce template. Soyez prudent!</p>
        
        <h3 style="color:#1e293b;font-weight:700;margin-top:20px">Peux-je utiliser le même template pour plusieurs pages ?</h3>
        <p style="color:#475569">Absolument! C'est l'idée des templates. Créez un template "Secteur", puis utilisez-le pour 5 pages différentes (Paris, Lyon, Marseille, etc.)</p>
        
        <h3 style="color:#1e293b;font-weight:700;margin-top:20px">Comment rendre un template mobile-friendly ?</h3>
        <p style="color:#475569">Les templates fournis le sont déjà! Si vous en créez un custom, utilisez les media queries CSS et testez sur téléphone.</p>
        
        <h3 style="color:#1e293b;font-weight:700;margin-top:20px">Où puis-je apprendre le HTML/CSS pour créer mes templates ?</h3>
        <p style="color:#475569">Sur <strong>MDN Web Docs</strong>, <strong>W3Schools</strong> ou <strong>Codecademy</strong>. Mais honnêtement, les templates existants suffisent pour la plupart des projets!</p>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════ -->
<!-- 9. RESSOURCES UTILES                                        -->
<!-- ═══════════════════════════════════════════════════════════ -->
<div class="stpl-section-guide">
    <h2><i class="fas fa-book"></i> Ressources utiles</h2>
    
    <div class="stpl-tip">
        <i class="fas fa-link"></i>
        <p><strong>Documentation HTML/CSS :</strong> <a href="https://developer.mozilla.org" target="_blank" style="color:#3b82f6;font-weight:700">MDN Web Docs</a></p>
    </div>
    
    <div class="stpl-tip">
        <i class="fas fa-link"></i>
        <p><strong>Tester le responsive :</strong> <a href="https://responsivedesignchecker.com" target="_blank" style="color:#3b82f6;font-weight:700">Responsive Design Checker</a></p>
    </div>
    
    <div class="stpl-tip">
        <i class="fas fa-link"></i>
        <p><strong>Optimiser les images :</strong> <a href="https://tinypng.com" target="_blank" style="color:#3b82f6;font-weight:700">TinyPNG</a></p>
    </div>
    
    <div class="stpl-tip">
        <i class="fas fa-link"></i>
        <p><strong>Générer des couleurs :</strong> <a href="https://coolors.co" target="_blank" style="color:#3b82f6;font-weight:700">Coolors</a></p>
    </div>
</div>

</div>
</div>