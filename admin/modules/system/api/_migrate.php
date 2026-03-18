<?php
/**
 * ══════════════════════════════════════════════════════════════
 *  MIGRATION — Réorganisation /admin/api/
 *  /admin/api/_migrate.php
 *
 *  Exécuter UNE SEULE FOIS pour :
 *  1. Créer la structure de dossiers
 *  2. Déplacer les anciens fichiers plats vers _legacy/
 *  3. Créer des stubs de redirection pour backward compat
 *
 *  Usage : php _migrate.php  (en SSH)
 *  Ou via browser : /admin/api/_migrate.php?confirm=yes
 * ══════════════════════════════════════════════════════════════
 */

// Sécurité : vérifier confirmation
if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['admin_id'])) die('Auth required');
    if (($_GET['confirm'] ?? '') !== 'yes') {
        die('<h2>Migration API</h2>
        <p>Cette opération va réorganiser /admin/api/ en sous-dossiers.</p>
        <p>Les anciens fichiers seront déplacés vers <code>_legacy/</code> avec des redirections.</p>
        <p><a href="?confirm=yes" style="background:#4f46e5;color:#fff;padding:10px 20px;border-radius:6px;text-decoration:none">Confirmer la migration</a></p>');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

$apiDir = __DIR__;
$log = [];

function out($msg) {
    global $log;
    $log[] = $msg;
    echo $msg . "\n";
}

out("═══ Migration API — IMMO LOCAL+ ═══");
out("Répertoire : {$apiDir}");
out("");

// ============================================
// 1. Créer les sous-dossiers
// ============================================
$folders = [
    '_legacy',          // Anciens fichiers plats
    'content',          // pages, articles, captures, secteurs
    'builder',          // builder, design, menus
    'marketing',        // crm, leads, emails, scoring, sequences, ads
    'immobilier',       // biens, estimation, financement, rdv
    'seo',              // seo, semantic, local
    'social',           // facebook, instagram, tiktok, linkedin, gmb
    'ai',               // generate, journal, prompts, agents
    'network',          // contact, scraper
    'strategy',         // launchpad
    'system',           // modules, settings, upload, maintenance
];

foreach ($folders as $f) {
    $path = $apiDir . '/' . $f;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        out("✅ Créé : api/{$f}/");
    } else {
        out("⏭  Existe : api/{$f}/");
    }
}

// ============================================
// 2. Mapper les anciens fichiers → nouveau dossier
// ============================================
// Format : 'ancien-fichier.php' => 'nouveau-dossier/nouveau-nom.php'
// Si le fichier n'a pas de mapping, il va dans _legacy/
$mapping = [
    // Content
    'pages.php'           => 'content/pages.php',
    'create-page.php'     => 'content/pages.php',       // merged
    'list-pages.php'      => 'content/pages.php',       // merged
    'blog-articles.php'   => 'content/articles.php',
    'check-slug.php'      => 'content/slugs.php',

    // Builder
    'save.php'            => 'builder/save.php',
    'save-direct.php'     => 'builder/save-direct.php',
    'save-header.php'     => 'builder/headers.php',
    'save-template.php'   => 'builder/templates.php',
    'load-template.php'   => 'builder/templates.php',   // merged
    'list-templates.php'  => 'builder/templates.php',   // merged
    'delete-template.php' => 'builder/templates.php',   // merged
    'apply-template.php'  => 'builder/templates.php',   // merged
    'design-clone.php'    => 'builder/design-clone.php',
    'update-menu-links.php' => 'builder/menus.php',
    'set-default.php'     => 'builder/defaults.php',
    'preview.php'         => 'builder/preview.php',
    'variables.php'       => 'builder/variables.php',

    // Marketing / CRM
    'crm-api.php'         => 'marketing/crm.php',
    'leads.php'           => 'marketing/leads.php',
    'lead-api.php'        => 'marketing/leads.php',     // merged
    'contact-api.php'     => 'marketing/contacts.php',
    'campaigns.php'       => 'marketing/campaigns.php',
    'audiences.php'       => 'marketing/audiences.php',
    'accounts.php'        => 'marketing/accounts.php',

    // Immobilier
    'estimation.php'      => 'immobilier/estimation.php',
    'estimation-submit.php' => 'immobilier/estimation.php', // merged
    'courtiers.php'       => 'immobilier/courtiers.php',

    // SEO
    'seo-score.php'       => 'seo/score.php',
    'seo-semantic-api.php' => 'seo/semantic.php',
    'performance.php'     => 'seo/performance.php',

    // AI
    'ai-generate.php'         => 'ai/generate.php',
    'ai-content-generate.php' => 'ai/content.php',
    'ai-field-assist.php'     => 'ai/field-assist.php',
    'ai-image-generate.php'   => 'ai/image.php',
    'ai-prompts-api.php'      => 'ai/prompts.php',
    'agents-api.php'          => 'ai/agents.php',

    // Journal
    'journal.php'         => 'ai/journal.php',
    'prerequisites.php'   => 'ai/prerequisites.php',

    // Social
    'website.php'         => 'network/website.php',

    // System
    'module-diagnostic.php' => 'system/modules.php',
    'delete.php'            => 'system/delete.php',
    'upload.php'            => 'system/upload.php',
    'theme-extract.php'     => 'builder/theme.php',
    'universal-save.php'    => 'system/universal-save.php',
];

// ============================================
// 3. Déplacer les fichiers
// ============================================
out("");
out("─── Déplacement des fichiers ───");

// Fichiers à ne PAS toucher
$protected = ['router.php', '_migrate.php'];

$files = glob($apiDir . '/*.php');
$moved = 0;
$skipped = 0;

foreach ($files as $filePath) {
    $filename = basename($filePath);

    // Skip protected files
    if (in_array($filename, $protected)) {
        out("🔒 Protégé : {$filename}");
        continue;
    }

    // Skip if it's already in a subdirectory
    if (strpos($filePath, $apiDir . '/') !== 0) continue;

    $dest = $mapping[$filename] ?? null;

    if ($dest) {
        // On ne déplace PAS si le fichier destination existe déjà
        // (cas des fichiers "merged" — on met l'original dans _legacy)
        $destPath = $apiDir . '/' . $dest;
        if (file_exists($destPath)) {
            // Move to legacy instead
            $legacyPath = $apiDir . '/_legacy/' . $filename;
            rename($filePath, $legacyPath);
            out("📦 → _legacy/{$filename} (destination {$dest} existe déjà)");
        } else {
            rename($filePath, $destPath);
            out("✅ {$filename} → {$dest}");
        }
    } else {
        // Pas de mapping → _legacy
        $legacyPath = $apiDir . '/_legacy/' . $filename;
        rename($filePath, $legacyPath);
        out("📦 {$filename} → _legacy/{$filename}");
    }

    // Créer un stub de redirection pour backward compat
    $stubContent = "<?php\n// Backward compat redirect — fichier déplacé\n";
    if ($dest) {
        $stubContent .= "// Nouvelle localisation : {$dest}\n";
        $stubContent .= "require __DIR__ . '/{$dest}';\n";
    } else {
        $stubContent .= "// Déplacé vers _legacy/\n";
        $stubContent .= "require __DIR__ . '/_legacy/{$filename}';\n";
    }
    file_put_contents($filePath, $stubContent);

    $moved++;
}

// ============================================
// 4. Résumé
// ============================================
out("");
out("═══ Résumé ═══");
out("Dossiers créés : " . count($folders));
out("Fichiers déplacés : {$moved}");
out("");
out("Structure finale :");

// List new structure
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($apiDir, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);
foreach ($iterator as $item) {
    if ($item->isDir()) continue;
    $rel = str_replace($apiDir . '/', '', $item->getPathname());
    if (strpos($rel, '.') === 0) continue; // skip hidden
    out("  {$rel}");
}

out("");
out("✅ Migration terminée !");
out("Les anciens URLs continueront de fonctionner grâce aux stubs de redirection.");
out("Pour utiliser le nouveau routeur : /admin/api/router.php?route=dossier.fichier&action=xxx");

// Save log
file_put_contents($apiDir . '/_migration.log', implode("\n", $log));
out("\nLog sauvegardé dans _migration.log");
