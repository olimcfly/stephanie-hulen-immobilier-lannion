<?php
/**
 * /admin/diagnostic.php
 * Diagnostic Admin — Santé système + DB + modules + migrations
 *
 * Objectifs :
 * - Voir vite si l'environnement est OK (PHP, extensions, permissions)
 * - Vérifier la DB et les tables clés
 * - Scanner les modules et leurs assets/api/sql
 * - Vérifier l'état des migrations (schema_migrations)
 */

require_once __DIR__ . '/includes/init.php';

// ─────────────────────────────────────────────────────────────
// Helpers (Claude-style: simple, lisible, robuste)
// ─────────────────────────────────────────────────────────────

function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function badge(bool $ok, string $okText = 'OK', string $noText = 'KO'): string {
    $label = $ok ? $okText : $noText;
    $style = $ok
        ? 'display:inline-block;padding:2px 8px;border-radius:999px;background:#16a34a;color:#fff;font-size:12px;'
        : 'display:inline-block;padding:2px 8px;border-radius:999px;background:#dc2626;color:#fff;font-size:12px;';
    return '<span style="'.$style.'">'.h($label).'</span>';
}

function sectionTitle(string $t): string {
    return '<h2 style="margin:24px 0 8px;font-size:18px;">'.h($t).'</h2>';
}

function row(string $k, string $v): string {
    return '<tr><td style="padding:8px;border-bottom:1px solid #eee;width:280px;color:#111827;"><strong>'.h($k).'</strong></td>'
         . '<td style="padding:8px;border-bottom:1px solid #eee;color:#111827;">'.$v.'</td></tr>';
}

function isWritablePath(string $path): bool {
    if (!file_exists($path)) return false;
    return is_writable($path);
}

// ─────────────────────────────────────────────────────────────
// Collecte infos système
// ─────────────────────────────────────────────────────────────

$phpVersion  = PHP_VERSION;
$sapi        = php_sapi_name();
$serverSoft  = $_SERVER['SERVER_SOFTWARE'] ?? 'unknown';
$docRoot     = $_SERVER['DOCUMENT_ROOT'] ?? '';
$hostname    = gethostname() ?: 'unknown';
$timeNow     = date('Y-m-d H:i:s');
$sessionName = session_name();
$sessionId   = session_id();

$exts = [
    'pdo'       => extension_loaded('pdo'),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'mbstring'  => extension_loaded('mbstring'),
    'curl'      => extension_loaded('curl'),
    'json'      => extension_loaded('json'),
    'openssl'   => extension_loaded('openssl'),
    'zip'       => extension_loaded('zip'),
];

$pathsToCheck = [
    'admin/'                => __DIR__,
    'admin/assets/'         => __DIR__ . '/assets',
    'admin/modules/'        => __DIR__ . '/modules',
    'admin/includes/'       => __DIR__ . '/includes',
    'admin/config/'         => __DIR__ . '/config',
    'root config/config.php'=> dirname(dirname(__DIR__)) . '/config/config.php',
];

// ─────────────────────────────────────────────────────────────
// DB check (via $pdo venant de init.php)
// ─────────────────────────────────────────────────────────────

$dbOk = false;
$dbInfo = [
    'driver'  => 'unknown',
    'version' => 'unknown',
    'db_name' => 'unknown',
];

$dbError = null;

try {
    if ($pdo instanceof PDO) {
        $dbOk = true;
        $dbInfo['driver'] = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        // MySQL/MariaDB
        $dbInfo['version'] = (string)$pdo->query("SELECT VERSION()")->fetchColumn();
        $dbInfo['db_name'] = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();
    } else {
        $dbError = '$pdo indisponible (init.php ne fournit pas PDO?)';
    }
} catch (Throwable $e) {
    $dbOk = false;
    $dbError = $e->getMessage();
}

// ─────────────────────────────────────────────────────────────
// Modules + migrations (si core présent)
// ─────────────────────────────────────────────────────────────

$coreRegistryPath = __DIR__ . '/core/ModuleRegistry.php';
$coreMigratorPath = __DIR__ . '/core/Migrator.php';

$modules = [];
$modulesError = null;

$migrations = [
    'table_exists' => null,
    'count'        => 0,
    'missing'      => 0,
    'missing_list' => [],
];

if (file_exists($coreRegistryPath) && file_exists($coreMigratorPath)) {
    require_once $coreRegistryPath;
    require_once $coreMigratorPath;

    try {
        $registry = new ModuleRegistry(__DIR__ . '/modules');
        $modules  = $registry->listModules();

        // Migrations check
        if ($dbOk) {
            $migrator = new Migrator($pdo);

            // Table exists ?
            $stmt = $pdo->query("SHOW TABLES LIKE 'schema_migrations'");
            $migrations['table_exists'] = (bool)$stmt->fetchColumn();

            if ($migrations['table_exists']) {
                $migrations['count'] = (int)$pdo->query("SELECT COUNT(*) FROM schema_migrations")->fetchColumn();
            }

            // Détecter les SQL non appliqués (approx)
            // Ici on compare via migration_key = 'module:NAME:sql:sha1(fullpath)' (selon ton Migrator::makeKey)
            // Comme makeKey() est private, on fait une clé "best effort": sha1(fullpath)
            $missing = [];

            foreach ($modules as $m) {
                foreach (($m['sql_files'] ?? []) as $sqlFile) {
                    $key = 'module:' . $m['name'] . ':sql:' . sha1($sqlFile);
                    $stmt = $pdo->prepare("SELECT 1 FROM schema_migrations WHERE migration_key = ? LIMIT 1");
                    $stmt->execute([$key]);
                    $exists = (bool)$stmt->fetchColumn();
                    if (!$exists) {
                        $missing[] = [
                            'module' => $m['name'],
                            'file'   => $sqlFile,
                            'key'    => $key,
                        ];
                    }
                }
            }

            $migrations['missing'] = count($missing);
            $migrations['missing_list'] = $missing;
        }
    } catch (Throwable $e) {
        $modulesError = $e->getMessage();
    }
} else {
    $modulesError = "core/ModuleRegistry.php ou core/Migrator.php introuvable. (Ajoute d'abord /admin/core/*)";
}

// ─────────────────────────────────────────────────────────────
// Render HTML
// ─────────────────────────────────────────────────────────────

?><!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Diagnostic — Admin</title>
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f6f7fb;margin:0;padding:24px;color:#111827;}
    .wrap{max-width:1100px;margin:0 auto;}
    .card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:16px 18px;margin:12px 0;box-shadow:0 1px 2px rgba(0,0,0,.04);}
    table{width:100%;border-collapse:collapse;}
    .small{font-size:12px;color:#6b7280;}
    .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:12px;}
    .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    @media(max-width:900px){.grid{grid-template-columns:1fr;}}
    .warn{background:#fff7ed;border:1px solid #fed7aa;}
  </style>
</head>
<body>
<div class="wrap">

  <h1 style="margin:0 0 6px;font-size:22px;">Diagnostic Admin</h1>
  <div class="small">Généré le <?=h($timeNow)?> — Host: <?=h($hostname)?> — SAPI: <?=h($sapi)?></div>

  <div class="card">
    <?=sectionTitle('Système')?>
    <table>
      <?=row('PHP Version', '<span class="mono">'.h($phpVersion).'</span>')?>
      <?=row('Serveur', '<span class="mono">'.h($serverSoft).'</span>')?>
      <?=row('Document Root', '<span class="mono">'.h($docRoot).'</span>')?>
      <?=row('Chemin admin', '<span class="mono">'.h(__DIR__).'</span>')?>
    </table>
  </div>

  <div class="card">
    <?=sectionTitle('Session / Sécurité')?>
    <table>
      <?=row('Session name', '<span class="mono">'.h($sessionName).'</span>')?>
      <?=row('Session id', '<span class="mono">'.h($sessionId).'</span>')?>
      <?=row('CSRF', !empty($_SESSION['csrf_token']) ? badge(true) . ' <span class="mono">'.h(substr($_SESSION['csrf_token'],0,12)).'…</span>' : badge(false))?>
      <?=row('Admin connecté', !empty($_SESSION['admin_id']) ? badge(true) . ' ID: <span class="mono">'.h((string)$_SESSION['admin_id']).'</span>' : badge(false))?>
    </table>
  </div>

  <div class="card">
    <?=sectionTitle('Extensions PHP')?>
    <table>
      <?php foreach ($exts as $k => $ok): ?>
        <?=row($k, badge((bool)$ok))?>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card">
    <?=sectionTitle('Permissions / Fichiers')?>
    <table>
      <?php foreach ($pathsToCheck as $label => $path): ?>
        <?php
          $exists = file_exists($path);
          $w = $exists ? isWritablePath($path) : false;
          $val = ($exists ? badge(true, 'Existe', 'Manquant') : badge(false, 'Existe', 'Manquant'));
          $val .= ' &nbsp; ' . ($exists ? badge($w, 'Writable', 'Read-only') : '');
          $val .= ' <div class="small mono">'.h($path).'</div>';
        ?>
        <?=row($label, $val)?>
      <?php endforeach; ?>
    </table>
  </div>

  <div class="card <?=(!$dbOk ? 'warn' : '')?>">
    <?=sectionTitle('Base de données')?>
    <table>
      <?=row('Connexion DB', $dbOk ? badge(true) : badge(false))?>
      <?=row('Driver', '<span class="mono">'.h($dbInfo['driver']).'</span>')?>
      <?=row('DB Name', '<span class="mono">'.h($dbInfo['db_name']).'</span>')?>
      <?=row('Version', '<span class="mono">'.h($dbInfo['version']).'</span>')?>
      <?=row('Erreur', $dbError ? '<span class="mono">'.h($dbError).'</span>' : '<span class="small">—</span>')?>
    </table>
  </div>

  <div class="card <?=($modulesError ? 'warn' : '')?>">
    <?=sectionTitle('Modules détectés')?>
    <?php if ($modulesError): ?>
      <div><?=badge(false)?> <span class="mono"><?=h($modulesError)?></span></div>
    <?php else: ?>
      <?php
        $count = count($modules);
        $withApi = 0; $withAssets = 0; $withSql = 0; $withIndex = 0; $sqlTotal = 0;

        foreach ($modules as $m) {
            if (!empty($m['has_api'])) $withApi++;
            if (!empty($m['has_assets'])) $withAssets++;
            if (!empty($m['has_index'])) $withIndex++;
            if (!empty($m['sql_files'])) { $withSql++; $sqlTotal += count($m['sql_files']); }
        }
      ?>
      <div class="small">
        Total: <strong><?=$count?></strong> —
        index.php: <strong><?=$withIndex?></strong> —
        api/: <strong><?=$withApi?></strong> —
        assets/: <strong><?=$withAssets?></strong> —
        modules avec SQL: <strong><?=$withSql?></strong> (SQL total: <strong><?=$sqlTotal?></strong>)
      </div>

      <div style="margin-top:10px;overflow:auto;">
        <table>
          <tr>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Module</th>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Index</th>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">API</th>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">Assets</th>
            <th style="text-align:left;padding:8px;border-bottom:1px solid #eee;">SQL</th>
          </tr>
          <?php foreach ($modules as $m): ?>
            <tr>
              <td style="padding:8px;border-bottom:1px solid #eee;"><strong><?=h($m['name'])?></strong></td>
              <td style="padding:8px;border-bottom:1px solid #eee;"><?=badge((bool)$m['has_index'], 'Oui', 'Non')?></td>
              <td style="padding:8px;border-bottom:1px solid #eee;"><?=badge((bool)$m['has_api'], 'Oui', 'Non')?></td>
              <td style="padding:8px;border-bottom:1px solid #eee;"><?=badge((bool)$m['has_assets'], 'Oui', 'Non')?></td>
              <td style="padding:8px;border-bottom:1px solid #eee;">
                <?php
                  $n = count($m['sql_files'] ?? []);
                  echo $n ? badge(true, $n.' fichier(s)', '0') : badge(false, '1+', '0');
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class="card <?=(!$dbOk ? 'warn' : '')?>">
    <?=sectionTitle('Migrations')?>
    <?php if (!$dbOk): ?>
      <div><?=badge(false)?> <span class="mono">DB non disponible → migrations non vérifiables.</span></div>
    <?php else: ?>
      <table>
        <?=row('schema_migrations', $migrations['table_exists'] ? badge(true, 'Présente', 'Absente') : badge(false, 'Présente', 'Absente'))?>
        <?=row('Migrations appliquées', '<span class="mono">'.h((string)$migrations['count']).'</span>')?>
        <?=row('SQL détectés non appliqués', $migrations['missing'] === 0 ? badge(true, '0', '0') : badge(false, (string)$migrations['missing'], '0'))?>
      </table>

      <?php if (!empty($migrations['missing_list'])): ?>
        <div style="margin-top:10px;">
          <div class="small">Liste (best effort) des SQL manquants :</div>
          <div style="max-height:260px;overflow:auto;border:1px solid #eee;border-radius:10px;padding:10px;background:#fafafa;">
            <?php foreach ($migrations['missing_list'] as $it): ?>
              <div class="mono" style="margin:0 0 6px;">
                • <strong><?=h($it['module'])?></strong> — <?=h($it['file'])?>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="small" style="margin-top:8px;">
            Astuce : applique via <span class="mono">/admin/update.php</span> (script d’update dédié).
          </div>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <?=sectionTitle('Infos utiles')?>
    <div class="small">
      - Si tu veux durcir : protège ce fichier derrière un role admin + IP allowlist.<br/>
      - Si tu veux l’export : tu peux ajouter un bouton “Télécharger JSON” (modules + checks).
    </div>
  </div>

</div>
</body>
</html>