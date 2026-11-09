<main>
    <h1><?= $t('ranking.heading', ['id' => $expeditionId]) ?></h1>
    <table>
        <thead>
            <tr>
                <th scope="col"><?= $t('ranking.position') ?></th>
                <th scope="col"><?= $t('ranking.participant') ?></th>
                <th scope="col"><?= $t('ranking.km') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ranking as $position => $row): ?>
                <tr>
                    <td><?= $position + 1 ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= (int) $row['km'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="/expeditions/<?= (int) $expeditionId ?>"><?= $t('ranking.back') ?></a>
</main>
