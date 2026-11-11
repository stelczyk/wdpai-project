<main class="error-page">
    <p class="error-code"><?= (int) $statusCode ?></p>
    <h1><?= htmlspecialchars($pageTitle) ?></h1>
    <p><?= htmlspecialchars($message) ?></p>
    <?php if ($statusCode === 401): ?>
        <a href="/login"><?= $t('error.401.action') ?></a>
    <?php else: ?>
        <a href="/"><?= $t('error.back') ?></a>
    <?php endif; ?>
</main>
