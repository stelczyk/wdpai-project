<?php
// Ranking wyprawy (Figma: „Ranking Wyprawy — Dystans”): kafelki podsumowania, zakres, tabela i bilans uczestnika.
// Zakres to zwykłe linki (?range=…) – działa bez JS.
use App\Service\RankingService;
use App\Translator;

$e = $expedition;
$r = $ranking;
$num = static fn (float $value): string => Translator::number($value);
$stage = $e['stage'];
$rangeLabel = static fn (string $range): string => $range === 'stage' && $stage !== null
    ? $t('ranking.range_stage_number', ['number' => $stage['number']])
    : $t('ranking.range_' . $range);
?>
<main class="expeditions-page ranking-page">
    <section class="exp-hero" aria-labelledby="ranking-title">
        <div class="exp-hero-text">
            <a class="activity-back" href="/expeditions/<?= $e['id'] ?>">← <?= $t('ranking.back') ?></a>
            <p class="eyebrow">
                <span class="dot dot--accent" aria-hidden="true"></span>
                <span class="eyebrow-accent"><?= $t('ranking.title') ?></span>
            </p>
            <h1 id="ranking-title"><?= htmlspecialchars($e['name']) ?></h1>
            <p class="lead"><?= $t('ranking.lead') ?></p>
        </div>
    </section>

    <section class="exp-stats dash-stats" aria-label="<?= $t('ranking.kpi_label') ?>">
        <article class="stat-card">
            <h2><?= $t('ranking.members') ?></h2>
            <p class="stat-value"><?= count($r['rows']) ?></p>
        </article>
        <article class="stat-card">
            <h2><?= $t('ranking.leader') ?></h2>
            <p class="stat-value ranking-leader"><?= $r['leader'] === null ? '—' : htmlspecialchars($r['leader']['name']) ?></p>
            <p class="stat-card-foot"><?= $r['leader'] === null ? $t('ranking.leader_none') : $num($r['leader']['km']) . ' km' ?></p>
        </article>
        <article class="stat-card">
            <h2><?= $t('ranking.average') ?></h2>
            <p class="stat-value"><?= $num($r['averageKm']) ?> <span>km</span></p>
        </article>
        <article class="stat-card">
            <h2><?= $t('ranking.remaining') ?></h2>
            <p class="stat-value"><?= $num($e['remainingKm']) ?> <span>km</span></p>
        </article>
    </section>

    <nav class="ranking-ranges" aria-label="<?= $t('ranking.range_label') ?>">
        <ul class="exp-tabs">
            <?php foreach (RankingService::RANGES as $range): ?>
                <li>
                    <a href="/expeditions/<?= $e['id'] ?>/ranking<?= $range === RankingService::RANGE_ALL ? '' : '?range=' . $range ?>"
                       <?= $range === $r['range'] ? 'aria-current="page"' : '' ?>><?= $rangeLabel($range) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <div class="activity-layout">
        <section class="ranking-board" aria-labelledby="ranking-table-title">
            <h2 id="ranking-table-title" class="visually-hidden"><?= $t('ranking.table_title', ['range' => $rangeLabel($r['range'])]) ?></h2>
            <?php if ($r['range'] === 'stage' && $stage !== null): ?>
                <p class="ranking-hint"><?= $t('ranking.stage_hint', ['from' => $stage['from'], 'to' => $stage['to']]) ?></p>
            <?php endif; ?>

            <table class="ranking-table">
                <thead>
                    <tr>
                        <th scope="col"><?= $t('ranking.position') ?></th>
                        <th scope="col"><?= $t('ranking.participant') ?></th>
                        <th scope="col"><?= $t('ranking.disciplines') ?></th>
                        <th scope="col"><?= $t('ranking.workouts') ?></th>
                        <th scope="col"><?= $t('ranking.km') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($r['rows'] as $row): ?>
                        <tr class="<?= $row['isMe'] ? 'ranking-row--me' : '' ?>"<?= $row['isMe'] ? ' aria-current="true"' : '' ?>>
                            <td class="ranking-position">
                                <span class="ranking-medal<?= $row['km'] > 0 && $row['position'] <= 3 ? ' ranking-medal--' . $row['position'] : '' ?>">
                                    <?= $row['position'] ?>
                                </span>
                            </td>
                            <th scope="row">
                                <span class="ranking-person">
                                    <span class="activity-avatar<?= $row['isMe'] ? ' activity-avatar--me' : '' ?>" aria-hidden="true"><?= htmlspecialchars($row['initials']) ?></span>
                                    <span>
                                        <?= htmlspecialchars($row['name']) ?>
                                        <?php if ($row['isMe']): ?><span class="activity-you"><?= $t('activity.you') ?></span><?php endif; ?>
                                        <?php if ($row['isCaptain']): ?>
                                            <span class="badge badge--role activity-captain"><?= $t('expeditions.role_captain') ?></span>
                                        <?php endif; ?>
                                    </span>
                                </span>
                            </th>
                            <td data-label="<?= $t('ranking.disciplines') ?>">
                                <ul class="ranking-disciplines">
                                    <?php foreach ($row['disciplineLabels'] as $discipline): ?>
                                        <li class="discipline-option--<?= $discipline['style'] ?>"><span class="dot" aria-hidden="true"></span><?= htmlspecialchars($discipline['label']) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            <td data-label="<?= $t('ranking.workouts') ?>"><?= $row['workouts'] ?></td>
                            <td class="ranking-km" data-label="<?= $t('ranking.km') ?>">
                                <strong><?= $num($row['km']) ?> km</strong>
                                <span><?= $t('ranking.share', ['percent' => Translator::number($row['share'], 0)]) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($r['leader'] === null): ?>
                <p class="feed-empty"><?= $t('ranking.empty') ?></p>
            <?php endif; ?>
        </section>

        <aside class="activity-aside">
            <section class="stat-card preview-card" aria-labelledby="balance-title">
                <h2 id="balance-title"><?= $t('ranking.balance_title') ?></h2>
                <?php if ($r['me'] !== null): ?>
                    <p class="stat-value"><?= $t('stats.position', ['position' => $r['me']['position']]) ?></p>
                    <p class="stat-card-foot">
                        <span><?= $t('stats.of_members', ['count' => count($r['rows'])]) ?></span>
                        <?php if ($r['me']['leadKm'] !== null): ?>
                            <span class="stat-gap"><?= $t('stats.lead', ['km' => $num($r['me']['leadKm'])]) ?></span>
                        <?php elseif ($r['me']['behindKm'] !== null): ?>
                            <span class="stat-gap"><?= $t('stats.behind', ['km' => $num($r['me']['behindKm'])]) ?></span>
                        <?php endif; ?>
                    </p>
                    <a class="exp-button exp-button--primary" href="/activities/new?expedition=<?= $e['id'] ?>"><?= $t('nav.add_activity') ?></a>
                <?php else: ?>
                    <p class="activity-review"><?= $t('ranking.not_member') ?></p>
                <?php endif; ?>

                <?php require __DIR__ . '/partials/discipline-factors.html.php'; ?>
            </section>
        </aside>
    </div>
</main>
