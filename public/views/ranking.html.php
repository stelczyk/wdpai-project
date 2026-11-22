<?php
// Ranking wyprawy (Figma: „Ranking Wyprawy — Dystans”): kafelki podsumowania, zakres, tabela i bilans uczestnika.
// Zakres to zwykłe linki (?range=…) – działa bez JS; ranking.js pobiera je przez fetch i podmienia tabelę.
use App\Service\RankingService;
use App\Translator;

$e = $expedition;
$r = $ranking;
$num = static fn (float $value): string => Translator::number($value);
$stage = $e['stage'];
// Pusty wiersz dla <template> – ten sam partial co wiersze tabeli.
$blankRow = ['position' => 0, 'name' => '', 'initials' => '', 'isMe' => false, 'isCaptain' => false,
    'km' => 0.0, 'share' => 0.0, 'workouts' => 0, 'disciplineLabels' => []];
$rangeLabel = static fn (string $range): string => $range === 'stage' && $stage !== null
    ? $t('ranking.range_stage_number', ['number' => $stage['number']])
    : $t('ranking.range_' . $range);
?>
<main class="expeditions-page ranking-page" data-ranking
      data-msg-share="<?= $t('ranking.share') ?>" data-msg-position="<?= $t('stats.position') ?>"
      data-msg-lead="<?= $t('stats.lead') ?>" data-msg-behind="<?= $t('stats.behind') ?>"
      data-msg-leader-none="<?= $t('ranking.leader_none') ?>" data-msg-table-title="<?= $t('ranking.table_title') ?>"
      data-msg-error="<?= $t('ranking.load_error') ?>">
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
            <p class="stat-value ranking-leader" data-out="leader"><?= $r['leader'] === null ? '—' : htmlspecialchars($r['leader']['name']) ?></p>
            <p class="stat-card-foot" data-out="leader-km"><?= $r['leader'] === null ? $t('ranking.leader_none') : $num($r['leader']['km']) . ' km' ?></p>
        </article>
        <article class="stat-card">
            <h2><?= $t('ranking.average') ?></h2>
            <p class="stat-value"><span class="stat-number" data-out="average"><?= $num($r['averageKm']) ?></span> <span>km</span></p>
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
                    <a data-range="<?= $range ?>" href="/expeditions/<?= $e['id'] ?>/ranking<?= $range === RankingService::RANGE_ALL ? '' : '?range=' . $range ?>"
                       <?= $range === $r['range'] ? 'aria-current="page"' : '' ?>><?= $rangeLabel($range) ?></a>
                </li>
            <?php endforeach; ?>
        </ul>
        <button class="exp-button exp-button--ghost ranking-refresh" type="button" data-ranking-refresh hidden><?= $t('ranking.refresh') ?></button>
    </nav>
    <p class="form-error" role="alert" data-ranking-error></p>

    <div class="activity-layout">
        <section class="ranking-board" aria-labelledby="ranking-table-title" data-ranking-board>
            <h2 id="ranking-table-title" class="visually-hidden" data-out="table-title"><?= $t('ranking.table_title', ['range' => $rangeLabel($r['range'])]) ?></h2>
            <?php if ($stage !== null): ?>
                <p class="ranking-hint" data-stage-hint<?= $r['range'] === 'stage' ? '' : ' hidden' ?>><?= $t('ranking.stage_hint', ['from' => $stage['from'], 'to' => $stage['to']]) ?></p>
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
                        <?php require __DIR__ . '/partials/ranking-row.html.php'; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <template data-row-template><?php $row = $blankRow; require __DIR__ . '/partials/ranking-row.html.php'; ?></template>

            <p class="feed-empty" data-ranking-empty<?= $r['leader'] === null ? '' : ' hidden' ?>><?= $t('ranking.empty') ?></p>
        </section>

        <aside class="activity-aside">
            <section class="stat-card preview-card" aria-labelledby="balance-title">
                <h2 id="balance-title"><?= $t('ranking.balance_title') ?></h2>
                <?php if ($r['me'] !== null): ?>
                    <p class="stat-value" data-out="my-position"><?= $t('stats.position', ['position' => $r['me']['position']]) ?></p>
                    <p class="stat-card-foot">
                        <span><?= $t('stats.of_members', ['count' => count($r['rows'])]) ?></span>
                        <span class="stat-gap" data-out="my-gap"><?= $r['me']['leadKm'] !== null
                            ? $t('stats.lead', ['km' => $num($r['me']['leadKm'])])
                            : ($r['me']['behindKm'] !== null ? $t('stats.behind', ['km' => $num($r['me']['behindKm'])]) : '') ?></span>
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
