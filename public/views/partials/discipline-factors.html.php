<?php
// Przeliczniki dyscyplin wyprawy – wspólne dla podglądu w formularzu aktywności i rankingu.
// Wejście: $disciplines z ActivityService::disciplines() (id, unit, factor, label, style).
use App\Translator;
?>
<div class="activity-factors">
    <h3><?= $t('activity.factors_title') ?></h3>
    <ul>
        <?php foreach ($disciplines as $discipline): ?>
            <li class="discipline-option--<?= $discipline['style'] ?>" data-factor-for="<?= $discipline['id'] ?>">
                <span><span class="dot" aria-hidden="true"></span><?= htmlspecialchars($discipline['label']) ?></span>
                <strong><?= $t($discipline['unit'] === 'min' ? 'activity.factor_min' : 'activity.factor_km', ['factor' => Translator::number($discipline['factor'], 2)]) ?></strong>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
