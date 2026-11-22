<?php
// Podgląd treningu na trasie (Figma: „Live Telemetry Projection Panel”). Serwer rysuje stan „przed treningiem”,
// activity-preview.js przelicza go na żywo. Wzory i szablony tekstów JS czyta z atrybutów data-*.
// Wejście: $e (wybrana wyprawa z przelicznikami), $route (ExpeditionService::dashboard()), $t.
use App\Translator;

$num = static fn (float $value): string => Translator::number($value);
$stats = $route['myStats'];
$stage = $route['stage'];
$percent = $route['progressPercent'];
// Dla JS: tylko km zatwierdzonych uczestników i znacznik „to ja” – bez nazwisk.
$members = array_map(static fn (array $m): array => ['km' => $m['km'], 'me' => $m['isMe']], $route['members']);
?>
<aside class="activity-aside" data-route-preview
       data-locale="<?= htmlspecialchars($locale) ?>"
       data-approved="<?= $route['approvedKm'] ?>" data-total="<?= $route['totalKm'] ?>"
       data-next-name="<?= htmlspecialchars($stage['to'] ?? '') ?>" data-next-km="<?= $stage['toKm'] ?? '' ?>"
       data-members="<?= htmlspecialchars(json_encode($members, JSON_THROW_ON_ERROR)) ?>"
       data-position="<?= $stats['position'] ?? '' ?>"
       data-msg-formula-km="<?= $t('preview.formula_km') ?>" data-msg-formula-min="<?= $t('preview.formula_min') ?>"
       data-msg-now="<?= $t('preview.now') ?>" data-msg-after="<?= $t('preview.after') ?>"
       data-msg-next="<?= $t('preview.next') ?>" data-msg-passes="<?= $t('preview.passes') ?>"
       data-msg-rank-same="<?= $t('preview.rank_same') ?>" data-msg-rank-up="<?= $t('preview.rank_up') ?>"
       data-msg-lead="<?= $t('stats.lead') ?>" data-msg-behind="<?= $t('stats.behind') ?>">
    <section class="stat-card preview-card" aria-labelledby="preview-title">
        <header class="stat-card-head">
            <h2 id="preview-title"><?= $t('preview.title') ?></h2>
            <span class="badge badge--status dash-status"><span class="dot" aria-hidden="true"></span><?= $t('preview.live') ?></span>
        </header>

        <div class="preview-highlight">
            <p class="preview-label"><?= $t('preview.added_label', ['name' => $e['name']]) ?></p>
            <p class="preview-km"><output data-out="added" aria-live="polite">+<?= $num(0.0) ?></output> <span>km</span></p>
            <p class="preview-formula"><output data-out="formula"></output></p>
        </div>

        <div class="preview-progress">
            <p class="preview-row">
                <span><?= $t('preview.progress') ?></span>
                <output data-out="percent"><?= $num($percent) ?>%</output>
            </p>
            <?php // Szerokości segmentów jako atrybuty SVG (procent trasy) – bez stylów inline. ?>
            <svg class="preview-bar" viewBox="0 0 100 6" preserveAspectRatio="none" aria-hidden="true">
                <rect class="preview-bar-now" width="<?= round($percent, 2) ?>" height="6" data-bar-now></rect>
                <rect class="preview-bar-delta" x="<?= round($percent, 2) ?>" width="0" height="6" data-bar-delta></rect>
            </svg>
            <p class="preview-row preview-row--muted">
                <output data-out="now"><?= $t('preview.now', ['km' => $num($route['approvedKm'])]) ?></output>
                <output data-out="after"><?= $t('preview.after', ['km' => $num($route['approvedKm'])]) ?></output>
            </p>
        </div>

        <?php if ($stage !== null): ?>
            <p class="preview-next">
                <img src="/img/dashboard/flag.svg" alt="" width="14" height="14">
                <output data-out="next"><?= $t('preview.next', ['name' => $stage['to'], 'km' => $num($stage['kmLeft'])]) ?></output>
            </p>
        <?php endif; ?>

        <?php if ($stats !== null): ?>
            <div class="preview-rank">
                <h3><?= $t('preview.rank_title') ?></h3>
                <p><output data-out="rank" aria-live="polite"><?= $t('preview.rank_same', ['position' => $stats['position']]) ?></output></p>
                <p class="preview-gap"><output data-out="gap"><?= $stats['leadKm'] !== null
                    ? $t('stats.lead', ['km' => $num($stats['leadKm'])])
                    : ($stats['behindKm'] !== null ? $t('stats.behind', ['km' => $num($stats['behindKm'])]) : '') ?></output></p>
            </div>
        <?php endif; ?>

        <?php $disciplines = $e['disciplines']; require __DIR__ . '/discipline-factors.html.php'; ?>
        <p class="activity-review"><?= $t('activity.review_note') ?></p>
    </section>
</aside>
