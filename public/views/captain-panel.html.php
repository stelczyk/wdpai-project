<main>
    <h1>Panel kapitana – wyprawa nr <?= (int) $expeditionId ?></h1>
    <section aria-labelledby="pending-heading">
        <h2 id="pending-heading">Aktywności do zatwierdzenia</h2>
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
