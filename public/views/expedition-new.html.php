<?php
// Formularz tworzenia wyprawy. Bez ramki w Figmie – w stylu kart listy wypraw i pól z ekranu logowania.
$fieldError = static fn (string $key): string => htmlspecialchars($errors[$key] ?? '');
$lastIndex = count($form['checkpoints']) - 1;
?>
<main class="expeditions-page">
    <section class="exp-hero" aria-labelledby="create-title">
        <div class="exp-hero-text">
            <p class="eyebrow">
                <span class="dot dot--accent" aria-hidden="true"></span>
                <span class="eyebrow-accent"><?= $t('expeditions.eyebrow') ?></span>
                <span aria-hidden="true">/</span>
                <span><?= $t('create.eyebrow') ?></span>
            </p>
            <h1 id="create-title"><?= $t('create.heading') ?></h1>
            <p class="lead"><?= $t('create.lead') ?></p>
        </div>
    </section>

    <form class="create-form" method="post" action="/expeditions" novalidate data-expedition-form
          data-msg-checkpoint-name="<?= $t('create.checkpoint_name') ?>"
          data-msg-checkpoint-order="<?= $t('create.checkpoint_order') ?>"
          data-msg-dates="<?= $t('create.end_before_start') ?>"
          data-msg-disciplines="<?= $t('create.disciplines_min') ?>">
        <?= $csrfField ?>

        <?php if ($errors !== []): ?>
            <div class="alert" role="alert">
                <span class="alert-icon"><img src="/img/login/alert.svg" alt="" width="15" height="15"></span>
                <div class="alert-content">
                    <p class="alert-title"><?= $t('create.errors_title') ?></p>
                    <p class="alert-text"><?= $t('create.errors_text') ?></p>
                </div>
            </div>
        <?php endif; ?>
        <p class="form-error" role="alert" data-form-error></p>

        <section class="create-card" aria-labelledby="basics-title">
            <h2 id="basics-title"><?= $t('create.section_basics') ?></h2>
            <div class="field">
                <label for="name"><?= $t('create.name') ?> <span class="required" aria-hidden="true">*</span></label>
                <input type="text" id="name" name="name" required maxlength="100"
                       placeholder="<?= $t('create.name_placeholder') ?>" value="<?= htmlspecialchars($form['name']) ?>"
                       aria-describedby="name-error" <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>>
                <p class="field-error" id="name-error"><?= $fieldError('name') ?></p>
            </div>
            <div class="create-grid">
                <div class="field">
                    <label for="start-date"><?= $t('create.start_date') ?> <span class="required" aria-hidden="true">*</span></label>
                    <input type="date" id="start-date" name="start_date" required value="<?= htmlspecialchars($form['startDate']) ?>"
                           aria-describedby="start-date-error" <?= isset($errors['start_date']) ? 'aria-invalid="true"' : '' ?>>
                    <p class="field-error" id="start-date-error"><?= $fieldError('start_date') ?></p>
                </div>
                <div class="field">
                    <label for="end-date"><?= $t('create.end_date') ?> <span class="required" aria-hidden="true">*</span></label>
                    <input type="date" id="end-date" name="end_date" required value="<?= htmlspecialchars($form['endDate']) ?>"
                           aria-describedby="end-date-error" <?= isset($errors['end_date']) ? 'aria-invalid="true"' : '' ?>>
                    <p class="field-error" id="end-date-error"><?= $fieldError('end_date') ?></p>
                </div>
                <div class="field">
                    <label for="max-members"><?= $t('create.max_members') ?> <span class="required" aria-hidden="true">*</span></label>
                    <input type="number" id="max-members" name="max_members" required min="2" max="100" step="1"
                           value="<?= htmlspecialchars($form['maxMembers']) ?>"
                           aria-describedby="max-members-error" <?= isset($errors['max_members']) ? 'aria-invalid="true"' : '' ?>>
                    <p class="field-error" id="max-members-error"><?= $fieldError('max_members') ?></p>
                </div>
            </div>
        </section>

        <section class="create-card" aria-labelledby="route-title">
            <h2 id="route-title"><?= $t('create.section_route') ?></h2>
            <p class="create-hint"><?= $t('create.route_hint') ?></p>
            <div class="checkpoint-head" aria-hidden="true">
                <span><?= $t('create.checkpoint_name_label') ?></span>
                <span><?= $t('create.checkpoint_km_label') ?></span>
            </div>
            <ol class="checkpoint-list" data-checkpoints aria-describedby="checkpoints-error">
                <?php foreach ($form['checkpoints'] as $index => $checkpoint): ?>
                    <?php $isStart = $index === 0; ?>
                    <li class="checkpoint-row">
                        <input type="text" name="checkpoint_name[]" maxlength="100" required
                               aria-label="<?= $t('create.checkpoint_name_label') ?>"
                               placeholder="<?= $t($isStart ? 'create.start_placeholder' : ($index === $lastIndex ? 'create.finish_placeholder' : 'create.checkpoint_placeholder')) ?>"
                               value="<?= htmlspecialchars($checkpoint['name']) ?>">
                        <input type="number" name="checkpoint_km[]" min="0" step="0.01" required
                               aria-label="<?= $t('create.checkpoint_km_label') ?>"
                               value="<?= htmlspecialchars($isStart ? '0' : $checkpoint['km']) ?>" <?= $isStart ? 'readonly' : '' ?> data-checkpoint-km>
                        <?php if ($isStart): ?>
                            <span class="checkpoint-tag"><?= $t('create.start_tag') ?></span>
                        <?php else: ?>
                            <button class="checkpoint-remove" type="button" data-remove-checkpoint><?= $t('create.remove') ?></button>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
            <template data-checkpoint-template>
                <li class="checkpoint-row">
                    <input type="text" name="checkpoint_name[]" maxlength="100" required
                           aria-label="<?= $t('create.checkpoint_name_label') ?>" placeholder="<?= $t('create.checkpoint_placeholder') ?>">
                    <input type="number" name="checkpoint_km[]" min="0" step="0.01" required
                           aria-label="<?= $t('create.checkpoint_km_label') ?>" data-checkpoint-km>
                    <button class="checkpoint-remove" type="button" data-remove-checkpoint><?= $t('create.remove') ?></button>
                </li>
            </template>
            <div class="create-row">
                <button class="exp-button exp-button--ghost" type="button" data-add-checkpoint><?= $t('create.add_checkpoint') ?></button>
                <p class="route-total"><?= $t('create.route_total') ?> <output data-route-total>0</output> km</p>
            </div>
            <p class="field-error" id="checkpoints-error"><?= $fieldError('checkpoints') ?></p>
        </section>

        <section class="create-card" aria-labelledby="disciplines-title">
            <h2 id="disciplines-title"><?= $t('create.section_disciplines') ?></h2>
            <p class="create-hint"><?= $t('create.disciplines_hint') ?></p>
            <ul class="discipline-list" aria-describedby="disciplines-error">
                <?php foreach ($disciplines as $discipline): ?>
                    <?php $enabled = in_array($discipline['id'], $form['enabled'], true); ?>
                    <li class="discipline-row">
                        <label class="checkbox">
                            <input type="checkbox" name="discipline[]" value="<?= $discipline['id'] ?>" <?= $enabled ? 'checked' : '' ?> data-discipline-toggle>
                            <?= htmlspecialchars($discipline['label']) ?>
                        </label>
                        <label class="factor">
                            <input type="number" name="factor[<?= $discipline['id'] ?>]" min="0.01" max="999.99" step="0.01"
                                   value="<?= htmlspecialchars((string) ($form['factors'][$discipline['id']] ?? $discipline['defaultFactor'])) ?>"
                                   <?= $enabled ? '' : 'disabled' ?>>
                            <span><?= $t($discipline['unit'] === 'min' ? 'create.factor_per_min' : 'create.factor_per_km') ?></span>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="field-error" id="disciplines-error"><?= $fieldError('disciplines') ?></p>
        </section>

        <div class="create-actions">
            <a class="exp-button exp-button--ghost" href="/expeditions"><?= $t('create.cancel') ?></a>
            <button class="exp-button exp-button--primary" type="submit"><?= $t('create.submit') ?></button>
        </div>
    </form>
</main>
