<?php
// Karta wyprawy w trakcie. Kolor akcentu zależy od roli: kapitan – pomarańczowy, uczestnik – niebieski (jak w Figmie).
$variant = $expedition['isCaptain'] ? 'captain' : 'member';
$stage = $expedition['stage'];
?>
<article class="exp-card exp-card--<?= $variant ?>">
    <div class="exp-card-top">
        <div class="badges">
            <span class="badge badge--status">
                <span class="dot" aria-hidden="true"></span>
                <?= $t('expeditions.status_day', ['day' => $expedition['day'], 'total' => $expedition['durationDays']]) ?>
            </span>
            <span class="badge badge--role">
                <?php if ($expedition['isCaptain']): ?>
                    <img src="/img/expeditions/captain.svg" alt="" width="16" height="20">
                <?php endif; ?>
                <?= $t($expedition['isCaptain'] ? 'expeditions.role_captain' : 'expeditions.role_participant') ?>
            </span>
        </div>
        <span class="exp-code"><?= $t('expeditions.code', ['code' => $expedition['joinCode']]) ?></span>
    </div>

    <h3 class="exp-card-title"><?= htmlspecialchars($expedition['name']) ?></h3>

    <div class="exp-panel exp-progress">
        <div class="exp-row">
            <p class="exp-km"><strong><?= $num($expedition['approvedKm']) ?></strong> / <?= $num($expedition['totalKm']) ?> km</p>
            <p class="exp-percent"><?= $t('expeditions.route_percent', ['percent' => $num($expedition['progressPercent'])]) ?></p>
        </div>
        <progress class="exp-bar" value="<?= round($expedition['approvedKm'], 2) ?>" max="<?= round($expedition['totalKm'], 2) ?>"
                  aria-label="<?= $t('expeditions.progress_label') ?>"></progress>
        <div class="exp-row exp-row--small">
            <?php if ($stage !== null): ?>
                <p class="exp-stage">
                    <img src="/img/expeditions/stage.svg" alt="" width="18" height="18">
                    <?= $t('expeditions.stage', ['number' => $stage['number'], 'from' => $stage['from'], 'to' => $stage['to']]) ?>
                </p>
                <p class="exp-strong"><?= $t('expeditions.km_to_checkpoint', ['km' => $num($stage['kmLeft'])]) ?></p>
            <?php else: ?>
                <p class="exp-strong"><?= $t('expeditions.finish_reached') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="exp-panel exp-team">
        <p class="exp-team-size">
            <img src="/img/expeditions/team.svg" alt="" width="22" height="16">
            <?= $t('expeditions.team_size', ['count' => $expedition['memberCount']]) ?>
            <?php if (!$expedition['isCaptain']): ?>
                <span class="exp-captain"><?= $t('expeditions.captain_name', ['name' => $expedition['captainName']]) ?></span>
            <?php endif; ?>
        </p>
        <p class="chip">
            <img src="/img/expeditions/podium.svg" alt="" width="20" height="18">
            <?= $t('expeditions.your_position', ['position' => $expedition['myPosition'], 'km' => $num($expedition['myKm'])]) ?>
        </p>
    </div>

    <div class="exp-card-actions exp-card-actions--end">
        <a class="exp-button exp-button--<?= $expedition['isCaptain'] ? 'primary' : 'secondary' ?>" href="/expeditions/<?= $expedition['id'] ?>">
            <?= $t('expeditions.open') ?>
            <img src="/img/expeditions/<?= $expedition['isCaptain'] ? 'arrow-dark' : 'arrow-light' ?>.svg" alt="" width="16" height="16">
        </a>
    </div>
</article>
