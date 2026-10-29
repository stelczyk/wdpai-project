<?php require __DIR__ . '/partials/header.html.php'; ?>
<main>
    <h1>Wyprawa: <?= htmlspecialchars($routeName) ?></h1>
    <section aria-labelledby="progress-heading">
        <h2 id="progress-heading">Postęp</h2>
        <p>
            <progress value="<?= (int) $progressKm ?>" max="<?= (int) $totalKm ?>"></progress>
            <?= (int) $progressKm ?> / <?= (int) $totalKm ?> km
        </p>
    </section>
    <nav aria-label="Wyprawa nr <?= (int) $expeditionId ?>">
        <ul>
            <li><a href="/expeditions/<?= (int) $expeditionId ?>/ranking">Ranking</a></li>
            <li><a href="/captain/<?= (int) $expeditionId ?>">Panel kapitana</a></li>
            <li><a href="/activities/new">Dodaj aktywność</a></li>
        </ul>
    </nav>
</main>
<?php require __DIR__ . '/partials/footer.html.php'; ?>
