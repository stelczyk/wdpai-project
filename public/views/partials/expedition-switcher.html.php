<?php
// Przełącznik aktywnej wyprawy w headerze. <details> rozwija listę bez JS i działa z klawiatury;
// expedition-switcher.js tylko domyka panel po kliknięciu obok i klawiszem Escape.
// Wejście: $expeditionSwitcher z ExpeditionService::switcherFor().
use App\Service\ExpeditionService;
use App\Translator;

$switch = $expeditionSwitcher;
$current = $switch['active'];
$percent = static fn (array $e): string => Translator::number($e['progressPercent']) . '%';
?>
<details class="exp-switch" data-exp-switch>
    <summary class="exp-switch-button dash-status--<?= $current['status'] ?>" aria-label="<?= $t('switcher.label') ?>: <?= htmlspecialchars($current['name']) ?>">
        <span class="dot" aria-hidden="true"></span>
        <span class="exp-switch-name"><?= htmlspecialchars($current['name']) ?></span>
        <span class="exp-switch-percent"><?= $percent($current) ?></span>
        <img class="exp-switch-chevron" src="/img/expeditions/chevron-down.svg" alt="" width="10" height="6">
    </summary>

    <div class="exp-switch-panel">
        <p class="exp-switch-head">
            <span><?= $t('switcher.title') ?></span>
            <span class="badge badge--plain"><?= $t('switcher.active_count', ['count' => $switch['activeCount']]) ?></span>
        </p>

        <ul class="exp-switch-list">
            <?php foreach ($switch['items'] as $item): ?>
                <li>
                    <a class="exp-switch-item dash-status--<?= $item['status'] ?>" href="/expeditions/<?= $item['id'] ?>"
                       <?= $item['id'] === $current['id'] ? 'aria-current="true"' : '' ?>>
                        <span class="exp-switch-title">
                            <span class="dot" aria-hidden="true"></span>
                            <?= htmlspecialchars($item['name']) ?>
                            <span class="badge badge--role"><?= $t($item['isCaptain'] ? 'expeditions.role_captain' : 'expeditions.role_participant') ?></span>
                        </span>
                        <span class="exp-switch-meta">
                            <?= $item['status'] === ExpeditionService::PLANNED
                                ? $t('switcher.meta_planned', ['km' => Translator::number($item['totalKm'], 0), 'date' => $item['startDate']->format('d.m.Y')])
                                : $t('switcher.meta', ['km' => Translator::number($item['totalKm'], 0), 'members' => $item['memberCount'], 'percent' => $percent($item)]) ?>
                        </span>
                        <?php if ($item['id'] === $current['id']): ?>
                            <span class="exp-switch-check" aria-hidden="true"></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <p class="exp-switch-foot">
            <?php if ($currentUser->canCreateExpeditions()): ?>
                <a href="/expeditions/new">
                    <img src="/img/expeditions/plus.svg" alt="" width="12" height="12">
                    <?= $t('create.open') ?>
                </a>
            <?php endif; ?>
            <a href="/expeditions"><?= $t('switcher.all') ?> →</a>
        </p>
    </div>
</details>
