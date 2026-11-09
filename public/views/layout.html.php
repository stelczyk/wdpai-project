<!doctype html>
<html lang="<?= htmlspecialchars($locale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> – Dystans</title>
    <!-- Bez defer: musi ustawić data-theme, zanim przeglądarka narysuje stronę (brak mignięcia motywu). -->
    <script src="/js/theme-init.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="/css/style.css">
    <!-- defer: pobieranie równolegle, wykonanie po sparsowaniu całego HTML, w kolejności tagów. -->
    <script src="/js/theme.js" defer></script>
    <?php foreach ($scripts ?? [] as $script): ?>
        <script src="<?= htmlspecialchars($script) ?>" defer></script>
    <?php endforeach; ?>
</head>
<body>
<?php require __DIR__ . '/partials/header.html.php'; ?>
<?= $content ?>
<?php require __DIR__ . '/partials/footer.html.php'; ?>
</body>
</html>
