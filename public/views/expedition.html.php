<?php
use App\Service\ExpeditionService;

// Pełne km bez końcówki „,0”, ułamki z jednym miejscem po przecinku.
$km = static fn (float $value): string => \App\Translator::number($value, fmod(round($value, 1), 1.0) === 0.0 ? 0 : 1);
$e = $expedition;
$lanes = $e['members'] === [] ? 0 : max(array_column($e['members'], 'lane')) + 1;
$checkpointLanes = $e['route'] === [] ? 0 : max(array_column($e['route'], 'lane')) + 1;
?>
<main class="expeditions-page">
    <section class="dash-head" aria-labelledby="dash-title">
        <div class="dash-head-text">
            <p class="eyebrow">
                <span class="badge badge--status dash-status dash-status--<?= $e['status'] ?>">
                    <span class="dot" aria-hidden="true"></span>
                    <?= $t('expedition.status_' . $e['status']) ?>
                </span>
                <span aria-hidden="true">/</span>
                <span><?= $e['startDate']->format('d.m.Y') ?> – <?= $e['endDate']->format('d.m.Y') ?></span>
                <?php if ($e['status'] === ExpeditionService::IN_PROGRESS): ?>
                    <span aria-hidden="true">•</span>
                    <span class="dash-day"><?= $t('expedition.day', ['day' => $e['day'], 'total' => $e['durationDays']]) ?></span>
                <?php elseif ($e['status'] === ExpeditionService::PLANNED): ?>
                    <span aria-hidden="true">•</span>
                    <span class="dash-day"><?= $t('expedition.starts_in', ['days' => $e['startsInDays']]) ?></span>
                <?php endif; ?>
            </p>
            <h1 id="dash-title"><?= htmlspecialchars($e['name']) ?></h1>
        </div>

        <nav class="dash-actions" aria-label="<?= $t('expedition.nav_label') ?>">
            <a class="exp-button exp-button--ghost" href="/expeditions/<?= $e['id'] ?>/ranking"><?= $t('expedition.ranking') ?></a>
            <?php if ($e['isCaptain']): ?>
                <a class="exp-button exp-button--ghost" href="/captain/<?= $e['id'] ?>"><?= $t('expedition.captain_panel') ?></a>
            <?php endif; ?>
            <a class="exp-button exp-button--primary" href="/activities/new">
                <img src="/img/expeditions/plus.svg" alt="" width="14" height="14">
                <?= $t('nav.add_activity') ?>
            </a>
        </nav>
    </section>

    <section class="dash-progress" aria-labelledby="progress-title">
        <div class="dash-progress-top">
            <div>
                <h2 id="progress-title" class="dash-label"><?= $t('expedition.total_distance') ?></h2>
                <p class="dash-total">
                    <strong><?= $km($e['approvedKm']) ?></strong>
                    <span>/ <?= $km($e['totalKm']) ?> km</span>
                    <span class="dash-percent"><?= \App\Translator::number($e['progressPercent']) ?>%</span>
                </p>
            </div>

            <dl class="dash-readouts" aria-label="<?= $t('expedition.readouts_label') ?>">
                <div>
                    <dt><?= $t('expedition.remaining') ?></dt>
                    <dd><?= $km($e['remainingKm']) ?> km</dd>
                </div>
                <div>
                    <dt><?= $t('expedition.pace') ?></dt>
                    <dd><?= $e['paceKmPerDay'] === null
                        ? $t('expedition.no_value')
                        : $t('expedition.pace_value', ['km' => \App\Translator::number($e['paceKmPerDay'])]) ?></dd>
                </div>
                <div>
                    <dt><?= $t('expedition.eta') ?></dt>
                    <dd><?= $e['eta'] === null ? $t('expedition.no_value') : $e['eta']->format('d.m.Y') ?></dd>
                </div>
            </dl>
        </div>

        <?php
        // Pozycje na pasku to dane (procent trasy), więc trafiają do zmiennych CSS --pos/--lane;
        // cały wygląd jest w style.css. To jedyny wyjątek od zasady „bez stylów inline”.
        ?>
        <div class="route" style="--lanes: <?= $lanes ?>; --cp-lanes: <?= $checkpointLanes ?>">
            <div class="route-track">
                <progress class="route-bar" value="<?= round($e['approvedKm'], 2) ?>" max="<?= round($e['totalKm'], 2) ?>"
                          aria-label="<?= $t('expedition.progress_label') ?>"></progress>

                <?php foreach ($e['route'] as $checkpoint): ?>
                    <span class="route-dot route-dot--<?= $checkpoint['state'] ?>" style="--pos: <?= round($checkpoint['percent'], 2) ?>%" aria-hidden="true"></span>
                <?php endforeach; ?>

                <?php if ($e['status'] !== ExpeditionService::PLANNED): ?>
                    <span class="route-group" style="--pos: <?= round($e['progressPercent'], 2) ?>%" aria-hidden="true">
                        <?= $t('expedition.group_marker', ['km' => $km($e['approvedKm'])]) ?>
                    </span>
                <?php endif; ?>
            </div>

            <ul class="route-members" aria-label="<?= $t('expedition.members_label') ?>">
                <?php foreach ($e['members'] as $member): ?>
                    <li class="route-member<?= $member['isMe'] ? ' route-member--me' : '' ?><?= $member['percent'] > 80 ? ' route-member--flip' : '' ?>"
                        style="--pos: <?= round($member['percent'], 2) ?>%; --lane: <?= $member['lane'] ?>">
                        <span class="route-avatar" aria-hidden="true"><?= htmlspecialchars($member['initials']) ?></span>
                        <span class="route-member-label">
                            <?= $member['isMe']
                                ? $t('expedition.me_marker', ['km' => $km($member['km'])])
                                : $t('expedition.member_marker', ['name' => $member['name'], 'km' => $km($member['km'])]) ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>

            <ol class="route-checkpoints" aria-label="<?= $t('expedition.checkpoints_label') ?>">
                <?php foreach ($e['route'] as $checkpoint): ?>
                    <li class="route-cp route-cp--<?= $checkpoint['state'] ?><?= $checkpoint['isFinish'] ? ' route-cp--last' : '' ?>"
                        style="--pos: <?= round($checkpoint['percent'], 2) ?>%; --lane: <?= $checkpoint['lane'] ?>"<?= $checkpoint['state'] === 'target' ? ' aria-current="step"' : '' ?>>
                        <strong><?= htmlspecialchars($checkpoint['name']) ?></strong>
                        <span>
                            <?php if ($checkpoint['state'] === 'target'): ?>
                                <?= $t('expedition.cp_target', ['left' => $km($checkpoint['km'] - $e['approvedKm']), 'km' => $km($checkpoint['km'])]) ?>
                            <?php elseif ($checkpoint['state'] === 'passed' && $checkpoint['isFinish']): ?>
                                <?= $t('expedition.cp_finish', ['km' => $km($checkpoint['km'])]) ?>
                            <?php else: ?>
                                <?= $t('expedition.cp_' . $checkpoint['state'], ['km' => $km($checkpoint['km'])]) ?>
                            <?php endif; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ol>
        </div>
    </section>

    <?php if ($e['myStats'] !== null): ?>
        <?php require __DIR__ . '/partials/member-stats.html.php'; ?>
    <?php endif; ?>

    <div class="dash-columns">
        <section class="feed" aria-labelledby="feed-title">
            <header class="feed-head">
                <h2 id="feed-title"><?= $t('feed.title') ?></h2>
                <span class="badge badge--km"><?= $t('feed.count', ['count' => $feed['total']]) ?></span>
            </header>

            <?php if ($feed['items'] === []): ?>
                <p class="feed-empty"><?= $t('feed.empty') ?></p>
            <?php else: ?>
                <?php foreach ($feed['items'] as $activity): ?>
                    <?php require __DIR__ . '/partials/activity-card.html.php'; ?>
                <?php endforeach; ?>
                <?php if ($feed['total'] > count($feed['items'])): ?>
                    <p class="feed-more"><?= $t('feed.more', ['shown' => count($feed['items']), 'total' => $feed['total']]) ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <aside class="dash-aside">
            <?php $stage = $e['stage']; ?>
            <section class="stat-card stage-card" aria-labelledby="stage-title">
                <?php if ($stage === null): ?>
                    <h2 id="stage-title"><?= $t('stage.finished_title') ?></h2>
                    <p class="stage-route">
                        <img src="/img/dashboard/flag.svg" alt="" width="16" height="16">
                        <?= $t('stage.finished_text', ['km' => $km($e['totalKm'])]) ?>
                    </p>
                <?php else: ?>
                    <header class="stat-card-head">
                        <h2 id="stage-title"><?= $t('stage.title', ['number' => $stage['number']]) ?></h2>
                        <?php if ($e['status'] !== ExpeditionService::COMPLETED): ?>
                            <span class="badge badge--status dash-status dash-status--<?= $e['status'] ?>">
                                <?= $t($e['status'] === ExpeditionService::PLANNED ? 'stage.before_start' : 'stage.on_route') ?>
                            </span>
                        <?php endif; ?>
                    </header>
                    <p class="stage-route"><?= htmlspecialchars($stage['from']) ?> → <?= htmlspecialchars($stage['to']) ?></p>
                    <div class="stage-box">
                        <p class="stage-left">
                            <span><?= $t('stage.left', ['to' => $stage['to']]) ?></span>
                            <strong><?= $km($stage['kmLeft']) ?> km</strong>
                        </p>
                        <progress class="exp-bar" value="<?= round(max(0.0, $e['approvedKm'] - $stage['fromKm']), 2) ?>"
                                  max="<?= round($stage['toKm'] - $stage['fromKm'], 2) ?>" aria-label="<?= $t('stage.progress_label') ?>"></progress>
                        <p class="stage-ends">
                            <span><?= htmlspecialchars($stage['from']) ?> (<?= $km($stage['fromKm']) ?> km)</span>
                            <span><?= htmlspecialchars($stage['to']) ?> (<?= $km($stage['toKm']) ?> km)</span>
                        </p>
                    </div>
                <?php endif; ?>
            </section>
        </aside>
        </div>
    </main>
