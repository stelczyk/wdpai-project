<main class="error-page">
    <p class="error-code"><?= (int) $statusCode ?></p>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    <p><?= htmlspecialchars($message) ?></p>
    <a href="/"><?= $t('error.back') ?></a>
</main>
