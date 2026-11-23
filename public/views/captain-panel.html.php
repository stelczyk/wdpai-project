<?php
// Panel kapitana (Figma: „Panel Kapitana — Dystans”): kafelki, zakładki statusów, kolejka kart aktywności
// (ten sam partial co feed na dashboardzie) i wpływ kolejki na postęp wyprawy.
use App\Service\CaptainService;
use App\Translator;

$e = $expedition;
$q = $queue;
$num = static fn (float $value): string => Translator::number($value);
$impact = $q['impact'];
?>
<main class="expeditions-page captain-page">
    <section class="exp-hero" aria-labelledby="captain-title">
        <div class="exp-hero-text">
            <a class="activity-back" href="/expeditions/<?= $e['id'] ?>">← <?= $t('ranking.back') ?></a>
            <p class="eyebrow">
                <span class="dot dot--accent" aria-hidden="true"></span>
                <span class="eyebrow-accent"><?= $t('captain.title') ?></span>
            </p>
            <h1 id="captain-title"><?= htmlspecialchars($e['name']) ?></h1>
            <p class="lead"><?= $t('captain.lead') ?></p>
        </div>
    </section>

    <section class="exp-stats dash-stats" aria-label="<?= $t('captain.kpi_label') ?>">
        <article class="stat-card">
            <h2><?= $t('captain.kpi_pending') ?></h2>
            <p class="stat-value"><?= $q['counts']['pending']['count'] ?></p>
            <p class="stat-card-foot"><?= $t('captain.kpi_pending_km', ['km' => $num($q['counts']['pending']['km'])]) ?></p>
        </article>
        <article class="stat-card">
            <h2><?= $t('captain.kpi_approved') ?></h2>
            <p class="stat-value"><?= $num($e['approvedKm']) ?> <span>km</span></p>
            <p class="stat-card-foot"><?= $t('captain.kpi_approved_count', ['count' => $q['counts']['approved']['count']]) ?></p>
        </article>
        <article class="stat-card">
            <h2><?= $t('captain.kpi_rejected') ?></h2>
            <p class="stat-value"><?= $q['counts']['rejected']['count'] ?></p>
        </article>
        <article class="stat-card">
            <h2><?= $t('expedition.eta') ?></h2>
            <p class="stat-value captain-eta"><?= $e['eta'] === null ? $t('expedition.no_value') : $e['eta']->format('d.m.Y') ?></p>
        </article>
    </section>

    <div class="activity-layout">
        <section class="feed captain-queue" aria-labelledby="queue-title">
            <h2 id="queue-title" class="visually-hidden"><?= $t('captain.tab_' . $q['tab']) ?></h2>
            <nav class="ranking-ranges" aria-label="<?= $t('captain.tabs_label') ?>">
                <ul class="exp-tabs">
                    <?php foreach (array_keys(CaptainService::TABS) as $tab): ?>
                        <li>
                            <a href="/captain/<?= $e['id'] ?><?= $tab === array_key_first(CaptainService::TABS) ? '' : '?status=' . $tab ?>"
                               <?= $tab === $q['tab'] ? 'aria-current="page"' : '' ?>>
                                <?= $t('captain.tab_' . $tab) ?> (<?= $q['counts'][$tab]['count'] ?>)
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <?php if ($q['items'] === []): ?>
                <p class="feed-empty"><?= $t($q['tab'] === 'pending' ? 'captain.empty_pending' : 'captain.empty') ?></p>
            <?php else: ?>
                <?php foreach ($q['items'] as $activity): ?>
                    <?php require __DIR__ . '/partials/activity-card.html.php'; ?>
                <?php endforeach; ?>
                <?php if ($q['total'] > count($q['items'])): ?>
                    <p class="feed-more"><?= $t('captain.more', ['shown' => count($q['items']), 'total' => $q['total']]) ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <aside class="activity-aside">
            <section class="stat-card preview-card" aria-labelledby="impact-title">
                <h2 id="impact-title"><?= $t('captain.impact_title') ?></h2>
                <?php if ($impact['pendingKm'] > 0): ?>
                    <div class="preview-progress">
                        <p class="preview-row">
                            <span><?= $t('captain.impact_progress') ?></span>
                            <span><?= $num($impact['nowPercent']) ?>% → <?= $num($impact['afterPercent']) ?>%</span>
                        </p>
                        <?php // Szerokości segmentów jako atrybuty SVG (procent trasy) – bez stylów inline. ?>
                        <svg class="preview-bar" viewBox="0 0 100 6" preserveAspectRatio="none" aria-hidden="true">
                            <rect class="preview-bar-now" width="<?= round($impact['nowPercent'], 2) ?>" height="6"></rect>
                            <rect class="preview-bar-delta" x="<?= round($impact['nowPercent'], 2) ?>"
                                  width="<?= round($impact['afterPercent'] - $impact['nowPercent'], 2) ?>" height="6"></rect>
                        </svg>
                        <p class="preview-row preview-row--muted">
                            <span><?= $t('preview.now', ['km' => $num($e['approvedKm'])]) ?></span>
                            <span><?= $t('captain.impact_pending', ['km' => $num($impact['pendingKm'])]) ?></span>
                        </p>
                    </div>
                    <?php if ($impact['nextName'] !== null): ?>
                        <p class="preview-next">
                            <img src="/img/dashboard/flag.svg" alt="" width="14" height="14">
                            <?= $impact['nextLeftKm'] <= 0
                                ? $t('captain.impact_passes', ['name' => $impact['nextName']])
                                : $t('captain.impact_next', ['name' => $impact['nextName'], 'km' => $num($impact['nextLeftKm'])]) ?>
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="activity-review"><?= $t('captain.impact_none') ?></p>
                <?php endif; ?>
            </section>
        </aside>
    </div>
</main>
