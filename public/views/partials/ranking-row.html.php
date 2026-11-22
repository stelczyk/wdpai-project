<?php
// Jeden wiersz rankingu. Serwer rysuje nim tabelę, a ten sam partial z pustym $row trafia do <template>,
// który ranking.js klonuje i wypełnia (textContent) – znacznik wiersza jest w jednym miejscu.
// Wejście: $row z RankingService::table() (albo pusty wiersz dla szablonu), $t, $num.
use App\Translator;
?>
<tr class="<?= $row['isMe'] ? 'ranking-row--me' : '' ?>"<?= $row['isMe'] ? ' aria-current="true"' : '' ?>>
    <td class="ranking-position">
        <span class="ranking-medal<?= $row['km'] > 0 && $row['position'] <= 3 ? ' ranking-medal--' . $row['position'] : '' ?>" data-field="position"><?= $row['position'] ?></span>
    </td>
    <th scope="row">
        <span class="ranking-person">
            <span class="activity-avatar<?= $row['isMe'] ? ' activity-avatar--me' : '' ?>" aria-hidden="true" data-field="initials"><?= htmlspecialchars($row['initials']) ?></span>
            <span>
                <span data-field="name"><?= htmlspecialchars($row['name']) ?></span>
                <span class="activity-you" data-field="you"<?= $row['isMe'] ? '' : ' hidden' ?>><?= $t('activity.you') ?></span>
                <span class="badge badge--role activity-captain" data-field="captain"<?= $row['isCaptain'] ? '' : ' hidden' ?>><?= $t('expeditions.role_captain') ?></span>
            </span>
        </span>
    </th>
    <td data-label="<?= $t('ranking.disciplines') ?>">
        <ul class="ranking-disciplines" data-field="disciplines">
            <?php foreach ($row['disciplineLabels'] as $discipline): ?>
                <li class="discipline-option--<?= $discipline['style'] ?>"><span class="dot" aria-hidden="true"></span><?= htmlspecialchars($discipline['label']) ?></li>
            <?php endforeach; ?>
        </ul>
    </td>
    <td data-label="<?= $t('ranking.workouts') ?>" data-field="workouts"><?= $row['workouts'] ?></td>
    <td class="ranking-km" data-label="<?= $t('ranking.km') ?>">
        <strong data-field="km"><?= $num($row['km']) ?> km</strong>
        <span data-field="share"><?= $t('ranking.share', ['percent' => Translator::number($row['share'], 0)]) ?></span>
    </td>
</tr>
