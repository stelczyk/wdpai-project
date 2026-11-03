<!doctype html>
<html lang="pl" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> – Dystans</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="/" aria-label="Dystans">
        <span class="brand-main" aria-hidden="true">DYST</span><span class="brand-accent" aria-hidden="true">ANS</span>
    </a>
    <?php if ($showNav ?? true): ?>
        <nav class="site-nav" aria-label="Menu główne">
            <ul>
                <li><a href="/expeditions">Wyprawy</a></li>
                <li><a href="/activities/new">Dodaj aktywność</a></li>
                <li><a href="/login">Zaloguj</a></li>
            </ul>
        </nav>
    <?php endif; ?>
</header>
