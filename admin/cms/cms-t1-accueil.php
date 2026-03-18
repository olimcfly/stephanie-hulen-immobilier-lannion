<?php
/**
 * Éditeur CMS : cms-t1-accueil.php
 * Interface admin pour modifier le contenu de t1-accueil
 * 
 * @usage Dans admin/cms/cms-t1-accueil.php
 * @requires SESSION authentifiée
 * @version 1.0
 */

// Vérifier l'authentification
// if (!isset($_SESSION['admin_id'])) header('Location: /admin/login.php');

require_once __DIR__ . '/../classes/PageContentT1Accueil.php';

// Initialiser la classe
$page_content = new PageContentT1Accueil($db);

// Traiter les soumissions POST
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $data = [];
        
        // Récupérer tous les champs POST
        $allowed_fields = [
            'hero_eyebrow', 'hero_title', 'hero_subtitle', 'hero_cta_text', 'hero_cta_url',
            'hero_stat1_num', 'hero_stat1_lbl', 'hero_stat2_num', 'hero_stat2_lbl',
            'ben_title', 'ben1_title', 'ben1_text', 'ben2_title', 'ben2_text', 'ben3_title', 'ben3_text',
            'method_title', 'step1_title', 'step1_text', 'step2_title', 'step2_text', 'step3_title', 'step3_text',
            'guide_title', 'g1_title', 'g1_text', 'g2_title', 'g2_text', 'g3_title', 'g3_text',
            'cta_title', 'cta_text', 'cta_btn_text'
        ];
        
        foreach ($allowed_fields as $field) {
            if (isset($_POST[$field])) {
                $data[$field] = $_POST[$field];
            }
        }
        
        if ($page_content->updateMultiple($data)) {
            $message = 'Contenu mis à jour avec succès ! ✓';
            $message_type = 'success';
        } else {
            $message = 'Erreur lors de la mise à jour.';
            $message_type = 'error';
        }
    }
}

// Charger les données actuelles
$content = $page_content->getAll();
$meta = $page_content->getMetadata();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditeur CMS - t1-accueil | IMMO LOCAL+ v8.6</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DM Sans', sans-serif;
            background: #f5f5f5;
            color: #2d2d2d;
            line-height: 1.6;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header {
            background: linear-gradient(135deg, #1B3A4B, #2C5F7C);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        .header h1 { font-size: 1.8rem; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 0.95rem; }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #e8e4dc;
        }
        .form-group:last-of-type {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .form-section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1B3A4B;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #C8A96E;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        .form-row.full {
            grid-template-columns: 1fr;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #2d2d2d;
            font-size: 0.95rem;
        }
        input[type="text"],
        input[type="url"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e8e4dc;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        input[type="text"]:focus,
        input[type="url"]:focus,
        textarea:focus {
            outline: none;
            border-color: #C8A96E;
            box-shadow: 0 0 0 3px rgba(200, 169, 110, 0.1);
        }
        textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.5;
        }
        .form-hint {
            font-size: 0.85rem;
            color: #5A5A5A;
            margin-top: 5px;
            font-style: italic;
        }
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e8e4dc;
        }
        button {
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-save {
            background: linear-gradient(135deg, #1B3A4B, #2C5F7C);
            color: white;
            flex: 1;
        }
        .btn-save:hover {
            box-shadow: 0 4px 20px rgba(27, 58, 75, 0.3);
            transform: translateY(-2px);
        }
        .btn-preview {
            background: #C8A96E;
            color: #1B3A4B;
        }
        .btn-preview:hover {
            background: #A68B4B;
            color: white;
        }
        .metadata {
            font-size: 0.85rem;
            color: #8A8A8A;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e8e4dc;
        }
        .metadata strong { color: #5A5A5A; }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .header h1 { font-size: 1.4rem; }
            .form-container { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="container">
    
    <div class="header">
        <h1>✏️ Éditeur CMS — t1-accueil</h1>
        <p>Modifiez le contenu de la page d'accueil de Stéphanie Hulen (Lannion)</p>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-container">
        <input type="hidden" name="action" value="update">

        <!-- ============================================================
             SECTION HERO
             ============================================================ -->
        <div class="form-group">
            <h2 class="form-section-title">🎯 Section HERO</h2>
            
            <div class="form-row full">
                <div>
                    <label for="hero_eyebrow">Eyebrow (petit texte au-dessus du titre)</label>
                    <input type="text" id="hero_eyebrow" name="hero_eyebrow" value="<?php echo htmlspecialchars($content['hero_eyebrow'] ?? ''); ?>">
                    <p class="form-hint">Ex: "Lannion & Trégor · Conseillère immobilière indépendante eXp France"</p>
                </div>
            </div>

            <div class="form-row full">
                <div>
                    <label for="hero_title">Titre principal HERO</label>
                    <textarea id="hero_title" name="hero_title"><?php echo htmlspecialchars($content['hero_title'] ?? ''); ?></textarea>
                    <p class="form-hint">Le grand titre au-dessus de la pliure</p>
                </div>
            </div>

            <div class="form-row full">
                <div>
                    <label for="hero_subtitle">Sous-titre descriptif</label>
                    <textarea id="hero_subtitle" name="hero_subtitle"><?php echo htmlspecialchars($content['hero_subtitle'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="hero_cta_text">Texte du bouton CTA</label>
                    <input type="text" id="hero_cta_text" name="hero_cta_text" value="<?php echo htmlspecialchars($content['hero_cta_text'] ?? ''); ?>">
                </div>
                <div>
                    <label for="hero_cta_url">URL du CTA</label>
                    <input type="url" id="hero_cta_url" name="hero_cta_url" value="<?php echo htmlspecialchars($content['hero_cta_url'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="hero_stat1_num">Stat 1 — Nombre</label>
                    <input type="text" id="hero_stat1_num" name="hero_stat1_num" value="<?php echo htmlspecialchars($content['hero_stat1_num'] ?? ''); ?>">
                    <p class="form-hint">Ex: "150+"</p>
                </div>
                <div>
                    <label for="hero_stat1_lbl">Stat 1 — Label</label>
                    <input type="text" id="hero_stat1_lbl" name="hero_stat1_lbl" value="<?php echo htmlspecialchars($content['hero_stat1_lbl'] ?? ''); ?>">
                    <p class="form-hint">Ex: "biens vendus"</p>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="hero_stat2_num">Stat 2 — Nombre</label>
                    <input type="text" id="hero_stat2_num" name="hero_stat2_num" value="<?php echo htmlspecialchars($content['hero_stat2_num'] ?? ''); ?>">
                </div>
                <div>
                    <label for="hero_stat2_lbl">Stat 2 — Label</label>
                    <input type="text" id="hero_stat2_lbl" name="hero_stat2_lbl" value="<?php echo htmlspecialchars($content['hero_stat2_lbl'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- ============================================================
             SECTION BÉNÉFICES
             ============================================================ -->
        <div class="form-group">
            <h2 class="form-section-title">💎 Section BÉNÉFICES</h2>
            
            <div class="form-row full">
                <div>
                    <label for="ben_title">Titre section bénéfices</label>
                    <textarea id="ben_title" name="ben_title"><?php echo htmlspecialchars($content['ben_title'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="ben1_title">Bénéfice 1 — Titre</label>
                    <input type="text" id="ben1_title" name="ben1_title" value="<?php echo htmlspecialchars($content['ben1_title'] ?? ''); ?>">
                </div>
                <div>
                    <label for="ben1_text">Bénéfice 1 — Texte</label>
                    <textarea id="ben1_text" name="ben1_text" style="min-height: 80px;"><?php echo htmlspecialchars($content['ben1_text'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="ben2_title">Bénéfice 2 — Titre</label>
                    <input type="text" id="ben2_title" name="ben2_title" value="<?php echo htmlspecialchars($content['ben2_title'] ?? ''); ?>">
                </div>
                <div>
                    <label for="ben2_text">Bénéfice 2 — Texte</label>
                    <textarea id="ben2_text" name="ben2_text" style="min-height: 80px;"><?php echo htmlspecialchars($content['ben2_text'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="ben3_title">Bénéfice 3 — Titre</label>
                    <input type="text" id="ben3_title" name="ben3_title" value="<?php echo htmlspecialchars($content['ben3_title'] ?? ''); ?>">
                </div>
                <div>
                    <label for="ben3_text">Bénéfice 3 — Texte</label>
                    <textarea id="ben3_text" name="ben3_text" style="min-height: 80px;"><?php echo htmlspecialchars($content['ben3_text'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- ============================================================
             SECTION MÉTHODE (4 ÉTAPES)
             ============================================================ -->
        <div class="form-group">
            <h2 class="form-section-title">🎬 Section MÉTHODE</h2>
            
            <div class="form-row full">
                <div>
                    <label for="method_title">Titre section méthode</label>
                    <textarea id="method_title" name="method_title"><?php echo htmlspecialchars($content['method_title'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="step1_title">Étape 1 — Titre</label>
                    <input type="text" id="step1_title" name="step1_title" value="<?php echo htmlspecialchars($content['step1_title'] ?? ''); ?>">
                </div>
                <div>
                    <label for="step1_text">Étape 1 — Description</label>
                    <textarea id="step1_text" name="step1_text" style="min-height: 80px;"><?php echo htmlspecialchars($content['step1_text'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="step2_title">Étape 2 — Titre</label>
                    <input type="text" id="step2_title" name="step2_title" value="<?php echo htmlspecialchars($content['step2_title'] ?? ''); ?>">
                </div>
                <div>
                    <label for="step2_text">Étape 2 — Description</label>
                    <textarea id="step2_text" name="step2_text" style="min-height: 80px;"><?php echo htmlspecialchars($content['step2_text'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="step3_title">Étape 3 — Titre</label>
                    <input type="text" id="step3_title" name="step3_title" value="<?php echo htmlspecialchars($content['step3_title'] ?? ''); ?>">
                </div>
                <div>
                    <label for="step3_text">Étape 3 — Description</label>
                    <textarea id="step3_text" name="step3_text" style="min-height: 80px;"><?php echo htmlspecialchars($content['step3_text'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- ============================================================
             SECTION GUIDE
             ============================================================ -->
        <div class="form-group">
            <h2 class="form-section-title">📚 Section GUIDE / RESSOURCES</h2>
            
            <div class="form-row full">
                <div>
                    <label for="guide_title">Titre section guide</label>
                    <textarea id="guide_title" name="guide_title"><?php echo htmlspecialchars($content['guide_title'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="g1_title">Guide 1 — Titre</label>
                    <input type="text" id="g1_title" name="g1_title" value="<?php echo htmlspecialchars($content['g1_title'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row full">
                <div>
                    <label for="g1_text">Guide 1 — Contenu (peut contenir du HTML)</label>
                    <textarea id="g1_text" name="g1_text"><?php echo htmlspecialchars($content['g1_text'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="g2_title">Guide 2 — Titre</label>
                    <input type="text" id="g2_title" name="g2_title" value="<?php echo htmlspecialchars($content['g2_title'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row full">
                <div>
                    <label for="g2_text">Guide 2 — Contenu</label>
                    <textarea id="g2_text" name="g2_text"><?php echo htmlspecialchars($content['g2_text'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="g3_title">Guide 3 — Titre</label>
                    <input type="text" id="g3_title" name="g3_title" value="<?php echo htmlspecialchars($content['g3_title'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row full">
                <div>
                    <label for="g3_text">Guide 3 — Contenu</label>
                    <textarea id="g3_text" name="g3_text"><?php echo htmlspecialchars($content['g3_text'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- ============================================================
             SECTION CTA FINALE
             ============================================================ -->
        <div class="form-group">
            <h2 class="form-section-title">🎯 CTA FINALE</h2>
            
            <div class="form-row full">
                <div>
                    <label for="cta_title">Titre CTA finale</label>
                    <textarea id="cta_title" name="cta_title"><?php echo htmlspecialchars($content['cta_title'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row full">
                <div>
                    <label for="cta_text">Description CTA finale</label>
                    <textarea id="cta_text" name="cta_text"><?php echo htmlspecialchars($content['cta_text'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="cta_btn_text">Texte du bouton</label>
                    <input type="text" id="cta_btn_text" name="cta_btn_text" value="<?php echo htmlspecialchars($content['cta_btn_text'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <!-- BOUTONS D'ACTION -->
        <div class="button-group">
            <button type="submit" class="btn-save">💾 Enregistrer les modifications</button>
            <a href="/front/t1-accueil" target="_blank" class="btn-preview" style="display: flex; align-items: center; justify-content: center; text-decoration: none;">👁️ Prévisualiser</a>
        </div>

        <!-- MÉTADONNÉES -->
        <?php if ($meta): ?>
            <div class="metadata">
                Créé le : <strong><?php echo date('d/m/Y à H:i', strtotime($meta['created_at'])); ?></strong><br>
                Dernière modification : <strong><?php echo date('d/m/Y à H:i', strtotime($meta['updated_at'])); ?></strong>
            </div>
        <?php endif; ?>

    </form>

</div>

</body>
</html>