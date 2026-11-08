<main>
    <h1>Ranking wyprawy nr <?= (int) $expeditionId ?></h1>
    <table>
        <thead>
            <tr>
                <th scope="col">Miejsce</th>
                <th scope="col">Uczestnik</th>
                <th scope="col">Km</th>
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
    <a href="/expeditions/<?= (int) $expeditionId ?>">Wróć do wyprawy</a>
</main>
