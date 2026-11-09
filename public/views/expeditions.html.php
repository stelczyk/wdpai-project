<main>
    <h1><?= $t('expeditions.title') ?></h1>
    <ul>
        <?php foreach ($expeditions as $expedition): ?>
            <li>
                <a href="/expeditions/<?= (int) $expedition['id'] ?>">
                    <?= htmlspecialchars($expedition['name']) ?>
                </a>
                (<?= (int) $expedition['distanceKm'] ?> km)
            </li>
        <?php endforeach; ?>
    </ul>
</main>
