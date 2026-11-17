<?php
// Kafelki uczestnika na dashboardzie (wygląd jak kafelki listy wypraw – klasy stat-card).
// Wejście: $s = $expedition['myStats'] z ExpeditionService::memberStats(), $km – formatowanie km z widoku.
$s = $e['myStats'];
$change = $s['weekChangePercent'];
?>
<section class="exp-stats dash-stats" aria-label="<?= $t('stats.label') ?>">
    <article class="stat-card">
        <header class="stat-card-head">
            <h2><?= $t('stats.week_title') ?></h2>
            <img src="/img/dashboard/trend.svg" alt="" width="20" height="20">
        </header>
        <p class="stat-value"><?= $km($s['weekKm']) ?> <span>km</span></p>
        <p class="stat-card-foot">
            <?php if ($change === null): ?>
                <?= $t('stats.week_none') ?>
            <?php else: ?>
                <span class="<?= $change >= 0 ? 'stat-up' : 'stat-down' ?>">
                    <?= $t($change >= 0 ? 'stats.week_up' : 'stats.week_down', ['percent' => \App\Translator::number(abs($change), 0)]) ?>
                </span>
            <?php endif; ?>
        </p>
    </article>

    <article class="stat-card">
        <header class="stat-card-head">
            <h2><?= $t('stats.position_title') ?></h2>
            <img src="/img/expeditions/podium.svg" alt="" width="20" height="18">
        </header>
        <p class="stat-value"><?= $t('stats.position', ['position' => $s['position']]) ?></p>
        <footer class="stat-card-foot">
            <span><?= $t('stats.of_members', ['count' => $s['memberCount']]) ?></span>
            <?php if ($s['leadKm'] !== null): ?>
                <span class="stat-gap"><?= $t('stats.lead', ['km' => $km($s['leadKm'])]) ?></span>
            <?php elseif ($s['behindKm'] !== null): ?>
                <span class="stat-gap"><?= $t('stats.behind', ['km' => $km($s['behindKm'])]) ?></span>
            <?php endif; ?>
        </footer>
    </article>

    <article class="stat-card">
        <header class="stat-card-head">
            <h2><?= $t('stats.streak_title') ?></h2>
            <img src="/img/dashboard/streak.svg" alt="" width="20" height="20">
        </header>
        <p class="stat-value"><?= (int) $s['streak'] ?> <span><?= $t($s['streak'] === 1 ? 'stats.streak_unit_one' : 'stats.streak_unit') ?></span></p>
        <footer class="stat-card-foot">
            <span><?= $s['lastEntry'] === null ? $t('stats.no_entries') : $t('stats.last_entry', ['when' => $s['lastEntry']]) ?></span>
            <?php if ($s['streak'] > 0): ?>
                <span class="dot dot--accent dot--small" aria-hidden="true"></span>
            <?php endif; ?>
        </footer>
    </article>
</section>
