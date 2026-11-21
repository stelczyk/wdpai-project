<?php
// Karta jednej aktywności – wspólna dla feedu na dashboardzie i (później) panelu kapitana.
// Wejście: $activity z ActivityFeedService. Teksty z bazy (imię, notatka) zawsze przez htmlspecialchars.
$routeKm = \App\Translator::number($activity['routeKm']);
?>
<article class="activity-card activity-card--<?= htmlspecialchars(strtolower($activity['status'])) ?>">
    <header class="activity-head">
        <span class="activity-avatar<?= $activity['isMe'] ? ' activity-avatar--me' : '' ?>" aria-hidden="true"><?= htmlspecialchars($activity['initials']) ?></span>
        <div class="activity-who">
            <h3>
                <?= htmlspecialchars($activity['userName']) ?>
                <?php if ($activity['isMe']): ?><span class="activity-you"><?= $t('activity.you') ?></span><?php endif; ?>
                <?php if ($activity['isCaptain']): ?>
                    <span class="badge badge--role activity-captain"><?= $t('expeditions.role_captain') ?></span>
                <?php endif; ?>
            </h3>
            <p class="activity-meta">
                <?= htmlspecialchars($activity['when']) ?>
                <?php if ($activity['note'] !== null): ?>
                    <span aria-hidden="true">•</span> <?= htmlspecialchars($activity['note']) ?>
                <?php endif; ?>
            </p>
        </div>
        <span class="activity-discipline activity-discipline--<?= $activity['style'] ?>">
            <span class="dot" aria-hidden="true"></span>
            <?= htmlspecialchars($activity['discipline']) ?>
        </span>
    </header>

    <?php if ($activity['photo'] !== null): ?>
        <img class="activity-photo" src="/<?= htmlspecialchars($activity['photo']) ?>" loading="lazy"
             alt="<?= $t('photo.alt', ['name' => $activity['userName']]) ?>">
    <?php endif; ?>

    <dl class="activity-metrics" aria-label="<?= $t('activity.metrics_label') ?>">
        <?php foreach ($activity['metrics'] as $metric): ?>
            <div>
                <dt><?= $t($metric['label']) ?></dt>
                <dd><?= htmlspecialchars($metric['value']) ?> <?php if ($metric['unit'] !== ''): ?><span><?= htmlspecialchars($metric['unit']) ?></span><?php endif; ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>

    <footer class="activity-foot">
        <?php if ($activity['status'] === 'APPROVED'): ?>
            <p class="activity-km">
                <img src="/img/dashboard/route-plus.svg" alt="" width="15" height="15">
                <?= $t('activity.route_km', ['km' => $routeKm]) ?>
            </p>
        <?php else: ?>
            <p class="activity-km activity-km--muted">
                <?= $t($activity['status'] === 'PENDING' ? 'activity.pending_km' : 'activity.rejected_km', ['km' => $routeKm]) ?>
            </p>
            <span class="activity-status"><?= $t('activity.status_' . $activity['status']) ?></span>
        <?php endif; ?>
    </footer>
</article>
