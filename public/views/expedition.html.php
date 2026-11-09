<main>
    <h1><?= $t('expedition.heading', ['name' => $routeName]) ?></h1>
    <section aria-labelledby="progress-heading">
        <h2 id="progress-heading"><?= $t('expedition.progress') ?></h2>
        <p>
            <progress value="<?= (int) $progressKm ?>" max="<?= (int) $totalKm ?>"></progress>
            <?= (int) $progressKm ?> / <?= (int) $totalKm ?> km
        </p>
    </section>
    <nav aria-label="<?= $t('expedition.nav_label', ['id' => $expeditionId]) ?>">
        <ul>
            <li><a href="/expeditions/<?= (int) $expeditionId ?>/ranking"><?= $t('expedition.ranking') ?></a></li>
            <li><a href="/captain/<?= (int) $expeditionId ?>"><?= $t('expedition.captain_panel') ?></a></li>
            <li><a href="/activities/new"><?= $t('nav.add_activity') ?></a></li>
        </ul>
    </nav>
</main>
