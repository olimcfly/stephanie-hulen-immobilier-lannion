<?php
/**
 * /front/templates/pages/layout.php
 * Layout master — charge header + contenu + footer
 */

// $content et $page sont passés par front/router.php
$content = $content ?? '';
$pageTitle = $pageTitle ?? 'Page';
$pageDescription = $pageDescription ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Styles globaux -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Header dynamique -->
    <?php include dirname(__DIR__, 2) . '/includes/header.php'; ?>

    <!-- Contenu de la page -->
    <main class="main-content">
        <div class="page-container">
            <?= $content ?>
        </div>
    </main>

    <!-- Footer dynamique -->
    <?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

    <!-- Scripts globaux -->
    <script src="/assets/js/main.js"></script>
</body>
</html>

<style>
.page-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 20px;
}

.main-content {
    min-height: calc(100vh - 300px);
}
</style>
