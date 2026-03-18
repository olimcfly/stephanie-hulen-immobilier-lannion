<?php
/**
 * ══════════════════════════════════════════════════════════════
 * Page 1 : Catalogue NeuroPersona — Sélection du persona
 * /admin/modules/strategy/neuropersona/index.php
 * ══════════════════════════════════════════════════════════════
 * L'utilisateur choisit son persona ici → redirigé vers
 * ?page=strategie-positionnement&persona=X
 * ══════════════════════════════════════════════════════════════
 */

defined('ADMIN_ROUTER') or define('ADMIN_ROUTER', true);
if (!defined('ROOT_PATH')) require_once dirname(__DIR__, 4) . '/config/config.php';

$db = getDB();

// ── 30 NeuroPersonas ──
$personas = [
    ['id'=>1,'name'=>'Primo-Accédant Jeune Couple','family'=>'acheteurs','age'=>'25-35 ans','desc'=>'CDI récent, locataire, veut arrêter de jeter l\'argent par les fenêtres','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>2],
    ['id'=>2,'name'=>'Primo-Accédant Solo','family'=>'acheteurs','age'=>'28-40 ans','desc'=>'Célibataire ou divorcé, veut son indépendance, budget serré','m1'=>'Liberté','m2'=>'Sécurité','conscience'=>2],
    ['id'=>3,'name'=>'Famille en Expansion','family'=>'acheteurs','age'=>'30-45 ans','desc'=>'Appart trop petit, enfants grandissent, veut maison avec jardin','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>3],
    ['id'=>4,'name'=>'Muté Professionnel','family'=>'acheteurs','age'=>'30-50 ans','desc'=>'Mutation imposée, ne connaît pas la ville, urgence','m1'=>'Contrôle','m2'=>'Sécurité','conscience'=>3],
    ['id'=>5,'name'=>'Retraité Actif — Downsizer','family'=>'acheteurs','age'=>'60-75 ans','desc'=>'Vend la grande maison, cherche plus petit, proche famille','m1'=>'Liberté','m2'=>'Sécurité','conscience'=>3],
    ['id'=>6,'name'=>'Expatrié de Retour','family'=>'acheteurs','age'=>'35-55 ans','desc'=>'Revient en France, ne connaît plus le marché local','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>2],
    ['id'=>7,'name'=>'Divorcé en Reconstruction','family'=>'acheteurs','age'=>'35-55 ans','desc'=>'Séparation récente, doit racheter seul, fragile','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>2],
    ['id'=>8,'name'=>'Acheteur Résidence Secondaire','family'=>'acheteurs','age'=>'45-65 ans','desc'=>'Aisé, cherche maison de vacances, plaisir','m1'=>'Reconnaissance','m2'=>'Liberté','conscience'=>4],
    ['id'=>9,'name'=>'Senior Simplificateur','family'=>'vendeurs','age'=>'65-80 ans','desc'=>'Maison trop grande, peur du changement','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>2],
    ['id'=>10,'name'=>'Héritier — Succession','family'=>'vendeurs','age'=>'40-60 ans','desc'=>'Bien hérité, indivision, veut vendre vite','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>3],
    ['id'=>11,'name'=>'Vendeur Divorce / Séparation','family'=>'vendeurs','age'=>'30-55 ans','desc'=>'Vente imposée, tension, besoin neutralité','m1'=>'Contrôle','m2'=>'Sécurité','conscience'=>3],
    ['id'=>12,'name'=>'Muté — Vente Urgente','family'=>'vendeurs','age'=>'30-50 ans','desc'=>'Mutation pro, deadline serrée','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>3],
    ['id'=>13,'name'=>'Propriétaire Monte en Gamme','family'=>'vendeurs','age'=>'35-50 ans','desc'=>'Crédit-relais, timing crucial','m1'=>'Reconnaissance','m2'=>'Contrôle','conscience'=>4],
    ['id'=>14,'name'=>'Expatrié — Vente à Distance','family'=>'vendeurs','age'=>'35-60 ans','desc'=>'0 déplacement, procuration','m1'=>'Contrôle','m2'=>'Liberté','conscience'=>3],
    ['id'=>15,'name'=>'Investisseur qui Revend','family'=>'vendeurs','age'=>'40-65 ans','desc'=>'Maximiser plus-value, fiscalité','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>4],
    ['id'=>16,'name'=>'Vendeur Première Fois','family'=>'vendeurs','age'=>'30-50 ans','desc'=>'Peur de l\'arnaque, besoin rassuré','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>1],
    ['id'=>17,'name'=>'Locatif Rentabilité Pure','family'=>'investisseurs','age'=>'35-55 ans','desc'=>'Rendement max, sensible aux chiffres','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>4],
    ['id'=>18,'name'=>'Défiscalisation / Patrimoine','family'=>'investisseurs','age'=>'40-60 ans','desc'=>'TMI élevée, Pinel/LMNP','m1'=>'Contrôle','m2'=>'Sécurité','conscience'=>3],
    ['id'=>19,'name'=>'Colocation / Étudiant','family'=>'investisseurs','age'=>'30-50 ans','desc'=>'Multi-locataires, rentabilité 6-10%','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>4],
    ['id'=>20,'name'=>'Location Courte Durée / Airbnb','family'=>'investisseurs','age'=>'30-50 ans','desc'=>'Zone touristique, revenus élevés','m1'=>'Liberté','m2'=>'Reconnaissance','conscience'=>4],
    ['id'=>21,'name'=>'Immeuble de Rapport','family'=>'investisseurs','age'=>'40-60 ans','desc'=>'Achète en bloc, cash-flow positif','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>5],
    ['id'=>22,'name'=>'Primo-Investisseur Prudent','family'=>'investisseurs','age'=>'30-40 ans','desc'=>'Premier invest, peur de se tromper','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>2],
    ['id'=>23,'name'=>'Prépare sa Retraite','family'=>'investisseurs','age'=>'45-58 ans','desc'=>'Patrimoine retraite, 10-15 ans','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>3],
    ['id'=>24,'name'=>'Nouveau Résident','family'=>'niches','age'=>'30-55 ans','desc'=>'Télétravail, qualité de vie, ne connaît rien','m1'=>'Liberté','m2'=>'Sécurité','conscience'=>2],
    ['id'=>25,'name'=>'Bailleur en Difficulté','family'=>'niches','age'=>'40-65 ans','desc'=>'Impayés, DPE F/G, veut sortir du locatif','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>3],
    ['id'=>26,'name'=>'Propriétaire DPE F/G','family'=>'niches','age'=>'Tout âge','desc'=>'Interdit location 2025+, anxieux','m1'=>'Sécurité','m2'=>'Contrôle','conscience'=>2],
    ['id'=>27,'name'=>'Professionnel Libéral','family'=>'niches','age'=>'30-55 ans','desc'=>'Médecin/avocat, local pro + logement, SCI','m1'=>'Reconnaissance','m2'=>'Contrôle','conscience'=>4],
    ['id'=>28,'name'=>'Vendeur en Viager','family'=>'niches','age'=>'70-85 ans','desc'=>'Rester chez soi, compléter retraite','m1'=>'Sécurité','m2'=>'Liberté','conscience'=>2],
    ['id'=>29,'name'=>'Acheteur Luxe / Prestige','family'=>'niches','age'=>'40-65 ans','desc'=>'Budget 500K+, discrétion, sur-mesure','m1'=>'Reconnaissance','m2'=>'Contrôle','conscience'=>5],
    ['id'=>30,'name'=>'Marchand de Biens','family'=>'niches','age'=>'35-55 ans','desc'=>'Pro, décote, négocie tout, volume','m1'=>'Contrôle','m2'=>'Reconnaissance','conscience'=>5],
];

$families = [
    'acheteurs'     =>['label'=>'Acheteurs Résidence Principale','short'=>'Acheteurs RP','icon'=>'🏠','color'=>'#e74c3c','bg'=>'#fdf2f2'],
    'vendeurs'      =>['label'=>'Vendeurs','short'=>'Vendeurs','icon'=>'🔑','color'=>'#d4880f','bg'=>'#fef9f0'],
    'investisseurs' =>['label'=>'Investisseurs','short'=>'Investisseurs','icon'=>'📈','color'=>'#8b5cf6','bg'=>'#f5f3ff'],
    'niches'        =>['label'=>'Profils Spécifiques','short'=>'Niches','icon'=>'🎯','color'=>'#10b981','bg'=>'#f0fdf4'],
];

$motivColors = ['Sécurité'=>['c'=>'#1e40af','bg'=>'#dbeafe'],'Liberté'=>['c'=>'#065f46','bg'=>'#d1fae5'],'Reconnaissance'=>['c'=>'#92400e','bg'=>'#fef3c7'],'Contrôle'=>['c'=>'#5b21b6','bg'=>'#ede9fe']];
$cLabels = ['','Non conscient','Conscient du problème','Cherche activement','Compare les solutions','Prêt à agir'];
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=DM+Sans:wght@300;400;500;700&display=swap');
.npc{font-family:'DM Sans',sans-serif;max-width:1080px;margin:0 auto;padding:24px 24px 60px}
.npc-header{margin-bottom:20px}
.npc-eyebrow{display:inline-flex;align-items:center;gap:6px;font-size:.65rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:#8b5cf6;margin-bottom:6px}
.npc-title{font-family:'Syne',sans-serif;font-size:1.5rem;font-weight:800;color:#111827;letter-spacing:-.02em;margin:0 0 4px}
.npc-subtitle{font-size:.8rem;color:#6b7280;line-height:1.5}
.npc-stats{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap}
.npc-stat{display:flex;align-items:center;gap:6px;padding:6px 14px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;font-size:.72rem;font-weight:600;color:#6b7280}
.npc-stat strong{color:#111827;font-size:.85rem}
.npc-fam-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin-bottom:24px}
@media(max-width:700px){.npc-fam-grid{grid-template-columns:1fr}}
.npc-fam{background:#fff;border:1px solid #e5e7eb;border-radius:14px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.npc-fam-hd{display:flex;align-items:center;gap:8px;padding:12px 16px;color:#fff;font-size:.82rem;font-weight:700}
.npc-fam-hd .cnt{margin-left:auto;font-size:.65rem;background:rgba(255,255,255,.25);padding:2px 8px;border-radius:6px}
.npc-fam-body{padding:10px 12px;display:flex;flex-direction:column;gap:6px}
.npc-pcard{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:10px;cursor:pointer;transition:all .18s;text-decoration:none;color:inherit}
.npc-pcard:hover{border-color:#8b5cf6;box-shadow:0 4px 16px rgba(139,92,246,.1);transform:translateY(-1px)}
.npc-pnum{font-size:9px;font-weight:700;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0}
.npc-pinfo{flex:1;min-width:0}
.npc-pname{font-size:.78rem;font-weight:700;color:#111827;margin-bottom:1px}
.npc-pdesc{font-size:.68rem;color:#9ca3af;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.npc-pmeta{display:flex;align-items:center;gap:6px;flex-shrink:0}
.npc-tag{font-size:7px;font-weight:600;padding:1px 5px;border-radius:3px}
.npc-dots{display:flex;gap:2px}
.npc-dot{width:4px;height:4px;border-radius:50%;background:#e5e7eb}
.npc-dot.on{background:#f59e0b}
.npc-arrow{color:#d1d5db;font-size:10px;transition:all .13s;flex-shrink:0}
.npc-pcard:hover .npc-arrow{color:#8b5cf6;transform:translateX(3px)}
.npc-footer{padding:16px 20px;background:linear-gradient(135deg,#1a1a2e,#2d2b55);border-radius:14px;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.npc-footer-count{font-size:1.2rem;font-weight:800}
.npc-footer-count span{font-size:.72rem;font-weight:400;opacity:.6;margin-left:4px}
.npc-footer-hint{font-size:.7rem;opacity:.5}
.anim{animation:fadeUp .25s ease both}.d1{animation-delay:.05s}.d2{animation-delay:.1s}
@keyframes fadeUp{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}
</style>

<div class="npc">
    <div class="npc-header anim">
        <div class="npc-eyebrow"><i class="fas fa-brain"></i> NeuroPersona</div>
        <h1 class="npc-title">Choisissez votre persona cible</h1>
        <p class="npc-subtitle">Sélectionnez le profil client sur lequel vous voulez travailler votre positionnement. Chaque persona a ses motivations profondes et son niveau de conscience — votre stratégie s'adaptera automatiquement.</p>
    </div>

    <div class="npc-stats anim d1">
        <div class="npc-stat"><strong>30</strong> personas</div>
        <div class="npc-stat"><strong>4</strong> familles</div>
        <div class="npc-stat"><strong>4</strong> motivations : <span style="color:#1e40af">Sécurité</span> · <span style="color:#065f46">Liberté</span> · <span style="color:#92400e">Reconnaissance</span> · <span style="color:#5b21b6">Contrôle</span></div>
    </div>

    <div class="npc-fam-grid anim d2">
        <?php foreach ($families as $fKey => $fam):
            $items = array_filter($personas, fn($p) => $p['family'] === $fKey);
        ?>
        <div class="npc-fam">
            <div class="npc-fam-hd" style="background:<?= $fam['color'] ?>">
                <?= $fam['icon'] ?> <?= $fam['label'] ?>
                <span class="cnt"><?= count($items) ?></span>
            </div>
            <div class="npc-fam-body">
                <?php foreach ($items as $p):
                    $mc1 = $motivColors[$p['m1']] ?? ['c'=>'#666','bg'=>'#eee'];
                    $mc2 = $motivColors[$p['m2']] ?? ['c'=>'#666','bg'=>'#eee'];
                ?>
                <a href="?page=strategie-positionnement&persona=<?= $p['id'] ?>" class="npc-pcard" style="border-left:3px solid <?= $fam['color'] ?>">
                    <span class="npc-pnum" style="background:<?= $fam['color'] ?>"><?= $p['id'] ?></span>
                    <div class="npc-pinfo">
                        <div class="npc-pname"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="npc-pdesc"><?= htmlspecialchars($p['age']) ?> — <?= htmlspecialchars($p['desc']) ?></div>
                    </div>
                    <div class="npc-pmeta">
                        <span class="npc-tag" style="background:<?= $mc1['bg'] ?>;color:<?= $mc1['c'] ?>"><?= $p['m1'] ?></span>
                        <span class="npc-tag" style="background:<?= $mc2['bg'] ?>;color:<?= $mc2['c'] ?>"><?= $p['m2'] ?></span>
                        <span class="npc-dots"><?php for($i=1;$i<=5;$i++): ?><span class="npc-dot<?= $i<=$p['conscience']?' on':'' ?>"></span><?php endfor; ?></span>
                    </div>
                    <span class="npc-arrow"><i class="fas fa-chevron-right"></i></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="npc-footer anim d2">
        <div>
            <span class="npc-footer-count">30<span>personas disponibles</span></span>
        </div>
        <span class="npc-footer-hint">Cliquez sur un persona → Positionnement adapté</span>
    </div>
</div>