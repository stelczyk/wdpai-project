<?php
use App\Service\ExpeditionService;

$num = \App\Translator::number(...);
$inProgress = $sections[ExpeditionService::IN_PROGRESS];
$planned = $sections[ExpeditionService::PLANNED];
$completed = $sections[ExpeditionService::COMPLETED];
$contribution = $stats['contribution'];
?>
<main class="expeditions-page">
    <section class="exp-hero" aria-labelledby="exp-title">
        <p class="eyebrow">
            <span class="dot dot--accent" aria-hidden="true"></span>
            <span class="eyebrow-accent"><?= $t('expeditions.eyebrow') ?></span>
            <span aria-hidden="true">/</span>
            <span><?= $t('expeditions.season', ['year' => $stats['year']]) ?></span>
        </p>
        <h1 id="exp-title"><?= $t('expeditions.welcome', ['name' => $user->name]) ?></h1>
        <p class="lead"><?= $t('expeditions.lead') ?></p>
    </section>

    <section class="exp-stats" aria-label="<?= $t('expeditions.stats_label') ?>">
        <article class="stat-card">
            <header class="stat-card-head">
                <h2><?= $t('expeditions.stat_active') ?></h2>
                <img src="/img/expeditions/active.svg" alt="" width="20" height="20">
            </header>
            <p class="stat-value"><?= (int) $stats['activeCount'] ?> <span><?= $t('expeditions.stat_active_unit') ?></span></p>
            <footer class="stat-card-foot">
                <span><span class="dot dot--accent dot--small" aria-hidden="true"></span><?= $t('expeditions.as_captain', ['count' => $stats['activeAsCaptain']]) ?></span>
                <span class="stat-strong"><?= $t('expeditions.as_participant', ['count' => $stats['activeAsParticipant']]) ?></span>
            </footer>
        </article>

        <article class="stat-card">
            <header class="stat-card-head">
                <h2><?= $t('expeditions.stat_contribution', ['year' => $stats['year']]) ?></h2>
                <img src="/img/expeditions/contribution.svg" alt="" width="14" height="20">
            </header>
            <p class="stat-value"><?= $num($contribution['totalKm']) ?> <span>km</span></p>
            <?php if ($contribution['shares'] !== []): ?>
                <?php // Szerokości segmentów jako atrybuty SVG – bez stylów inline. ?>
                <svg class="share-bar" viewBox="0 0 100 6" preserveAspectRatio="none" aria-hidden="true">
                    <?php $x = 0.0; foreach ($contribution['shares'] as $index => $share): ?>
                        <rect class="share-<?= $index ?>" x="<?= round($x, 2) ?>" width="<?= round($share['percent'], 2) ?>" height="6"></rect>
                        <?php $x += $share['percent']; endforeach; ?>
                </svg>
                <ul class="share-legend">
                    <?php foreach ($contribution['shares'] as $index => $share): ?>
                        <li class="share-<?= $index ?>"><?= htmlspecialchars($share['label']) ?> <?= $num($share['percent'], 0) ?>%</li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="stat-card-foot"><?= $t('expeditions.no_contribution') ?></p>
            <?php endif; ?>
        </article>

        <article class="stat-card">
            <header class="stat-card-head">
                <h2><?= $t('expeditions.stat_completed') ?></h2>
                <img src="/img/expeditions/completed.svg" alt="" width="10" height="20">
            </header>
            <p class="stat-value"><?= (int) $stats['completedCount'] ?> <span><?= $t('expeditions.stat_completed_unit') ?></span></p>
            <footer class="stat-card-foot">
                <span><?= $t('expeditions.goal_reached') ?></span>
                <span class="chip chip--accent"><?= $stats['successPercent'] === null ? '—' : $num($stats['successPercent'], 0) . '%' ?></span>
            </footer>
        </article>
    </section>

    <?php if ($inProgress === [] && $planned === [] && $completed === []): ?>
        <section class="exp-empty">
            <h2><?= $t('expeditions.empty_title') ?></h2>
            <p><?= $t('expeditions.empty_text') ?></p>
        </section>
    <?php endif; ?>

    <?php if ($inProgress !== []): ?>
        <section class="exp-section" aria-labelledby="in-progress-title">
            <?php $marker = 'accent'; $headingId = 'in-progress-title'; $heading = $t('expeditions.section_in_progress'); $aside = $t('expeditions.section_in_progress_aside', ['count' => count($inProgress)]); ?>
            <?php require __DIR__ . '/partials/section-heading.html.php'; ?>
            <div class="exp-grid">
                <?php foreach ($inProgress as $expedition): ?>
                    <?php require __DIR__ . '/partials/expedition-card.html.php'; ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($planned !== []): ?>
        <section class="exp-section" aria-labelledby="planned-title">
            <?php $marker = 'info'; $headingId = 'planned-title'; $heading = $t('expeditions.section_planned'); $aside = $t('expeditions.section_planned_aside'); ?>
            <?php require __DIR__ . '/partials/section-heading.html.php'; ?>
            <div class="exp-grid">
                <?php foreach ($planned as $expedition): ?>
                    <article class="exp-card exp-card--planned">
                        <div class="exp-card-top">
                            <div class="badges">
                                <span class="badge badge--plain badge--info">
                                    <img src="/img/expeditions/calendar.svg" alt="" width="18" height="20">
                                    <?= $t('expeditions.starts_in', ['days' => $expedition['startsInDays'], 'date' => $expedition['startDate']->format('d.m.Y')]) ?>
                                </span>
                                <span class="badge badge--role"><?= $t($expedition['isCaptain'] ? 'expeditions.role_organizer' : 'expeditions.role_participant') ?></span>
                            </div>
                            <span class="exp-code"><?= $t('expeditions.code', ['code' => $expedition['joinCode']]) ?></span>
                        </div>
                        <div class="exp-planned-head">
                            <h3 class="exp-card-title exp-card-title--small"><?= htmlspecialchars($expedition['name']) ?></h3>
                            <p class="exp-planned-km">
                                <strong><?= $num($expedition['totalKm']) ?> km</strong>
                                <span><?= $t('expeditions.route_length') ?></span>
                            </p>
                        </div>
                        <dl class="exp-panel exp-facts">
                            <div><dt><?= $t('expeditions.team') ?></dt><dd><?= $t('expeditions.team_value', ['count' => $expedition['memberCount'], 'max' => $expedition['maxMembers']]) ?></dd></div>
                            <div><dt><?= $t('expeditions.duration') ?></dt><dd><?= $t('expeditions.duration_value', ['days' => $expedition['durationDays']]) ?></dd></div>
                            <div><dt><?= $t('expeditions.disciplines') ?></dt><dd><?= htmlspecialchars(implode(', ', $expedition['disciplines'])) ?></dd></div>
                        </dl>
                        <div class="exp-card-actions">
                            <p class="exp-note">
                                <span class="dot dot--accent" aria-hidden="true"></span>
                                <?= $t('expeditions.free_spots', ['count' => max(0, $expedition['maxMembers'] - $expedition['memberCount'])]) ?>
                            </p>
                            <a class="exp-button exp-button--secondary" href="/expeditions/<?= $expedition['id'] ?>">
                                <?= $t('expeditions.details') ?>
                                <img src="/img/expeditions/arrow-light.svg" alt="" width="16" height="16">
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($completed !== []): ?>
        <section class="exp-section" aria-labelledby="completed-title">
            <?php $marker = 'neutral'; $headingId = 'completed-title'; $heading = $t('expeditions.section_completed'); $aside = $t('expeditions.section_completed_aside'); ?>
            <?php require __DIR__ . '/partials/section-heading.html.php'; ?>
            <ul class="exp-archive">
                <?php foreach ($completed as $expedition): ?>
                    <li class="exp-archive-row">
                        <div class="exp-archive-main">
                            <span class="exp-archive-icon"><img src="/img/expeditions/medal.svg" alt="" width="16" height="21"></span>
                            <div>
                                <h3>
                                    <?= htmlspecialchars($expedition['name']) ?>
                                    <span class="badge badge--km"><?= $num($expedition['totalKm']) ?> km</span>
                                </h3>
                                <p><?= $t('expeditions.archive_meta', [
                                    'date' => $expedition['endDate']->format('d.m.Y'),
                                    'days' => $expedition['durationDays'],
                                    'km' => $num($expedition['myKm']),
                                ]) ?></p>
                            </div>
                        </div>
                        <div class="exp-archive-side">
                            <p>
                                <span><?= $t('expeditions.team_size', ['count' => $expedition['memberCount']]) ?></span>
                                <span class="exp-archive-goal"><?= $t('expeditions.goal_percent', ['percent' => $num($expedition['progressPercent'], 0)]) ?></span>
                            </p>
                            <a class="exp-button exp-button--ghost" href="/expeditions/<?= $expedition['id'] ?>/ranking"><?= $t('expeditions.stats_link') ?></a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
</main>
