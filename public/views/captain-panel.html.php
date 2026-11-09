<main>
    <h1><?= $t('captain.heading', ['id' => $expeditionId]) ?></h1>
    <section aria-labelledby="pending-heading">
        <h2 id="pending-heading"><?= $t('captain.pending') ?></h2>
        <ul>
            <?php foreach ($pending as $activity): ?>
                <li>
                    <?= htmlspecialchars($activity['user']) ?>:
                    <?= htmlspecialchars($activity['type']) ?>,
                    <?= (int) $activity['km'] ?> km
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
</main>
