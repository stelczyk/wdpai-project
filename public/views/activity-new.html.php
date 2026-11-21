<?php
// Dodaj aktywność (Figma: „Dodaj aktywność — Dystans”): formularz po lewej, podgląd na trasie po prawej.
// Bez JS działa cały formularz; activity-form.js dokłada walidację na bieżąco i chowa dystans dla siłowni,
// activity-preview.js przelicza podgląd przy każdej zmianie.
use App\Service\ActivityService;
use App\Service\PhotoUploadService;

$fieldError = static fn (string $key): string => htmlspecialchars($errors[$key] ?? '');
$invalid = static fn (string $key): string => isset($errors[$key]) ? ' aria-invalid="true"' : '';
$e = $expedition;
?>
<main class="expeditions-page activity-page">
    <section class="exp-hero" aria-labelledby="activity-title">
        <div class="exp-hero-text">
            <?php if ($e !== null): ?>
                <a class="activity-back" href="/expeditions/<?= $e['id'] ?>">← <?= $t('activity.back') ?></a>
            <?php endif; ?>
            <p class="eyebrow">
                <span class="dot dot--accent" aria-hidden="true"></span>
                <span class="eyebrow-accent"><?= $t('activity.eyebrow') ?></span>
            </p>
            <h1 id="activity-title"><?= $t('activity.title') ?></h1>
            <p class="lead"><?= $t('activity.lead') ?></p>
        </div>

        <?php // Zmiana wyprawy to zwykły GET – inne wyprawy mają inne dyscypliny i przeliczniki. ?>
        <?php if (count($expeditions) > 1): ?>
            <form class="activity-switch" method="get" action="/activities/new">
                <label for="expedition-choice"><?= $t('activity.expedition') ?></label>
                <div class="exp-select">
                    <select id="expedition-choice" name="expedition" data-auto-submit>
                        <?php foreach ($expeditions as $option): ?>
                            <option value="<?= $option['id'] ?>" <?= $option['id'] === $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($option['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <img src="/img/expeditions/chevron-down.svg" alt="" width="12" height="7">
                </div>
                <button class="visually-hidden" type="submit"><?= $t('activity.change_expedition') ?></button>
            </form>
        <?php endif; ?>
    </section>

    <?php if (isset($errors['expedition'])): ?>
        <div class="alert" role="alert">
            <span class="alert-icon"><img src="/img/login/alert.svg" alt="" width="15" height="15"></span>
            <div class="alert-content"><p class="alert-text"><?= $fieldError('expedition') ?></p></div>
        </div>
    <?php endif; ?>

    <?php if ($e === null): ?>
        <section class="exp-empty activity-empty">
            <h2><?= $t('activity.no_expeditions_title') ?></h2>
            <p><?= $t('activity.no_expeditions_text') ?></p>
            <a class="exp-button exp-button--primary" href="/expeditions"><?= $t('activity.browse') ?></a>
        </section>
    <?php else: ?>
        <div class="activity-layout">
            <form class="create-form activity-form" method="post" action="/activities" enctype="multipart/form-data" novalidate data-activity-form
                  data-msg-photo-size="<?= $t('photo.too_large', ['max' => PhotoUploadService::MAX_BYTES / 1024 / 1024]) ?>"
                  data-msg-photo-type="<?= $t('photo.invalid_type') ?>"
                  data-msg-discipline="<?= $t('activity.discipline_invalid') ?>"
                  data-msg-distance="<?= $t('activity.distance_range', ['max' => (int) ActivityService::DISTANCE_MAX]) ?>"
                  data-msg-duration="<?= $t('activity.duration_range') ?>"
                  data-msg-date="<?= $t('validation.date') ?>"
                  data-msg-date-future="<?= $t('activity.date_future') ?>"
                  data-msg-date-start="<?= $t('activity.date_before_start', ['date' => (new DateTimeImmutable($e['startDate']))->format('d.m.Y')]) ?>"
                  data-msg-heart-rate="<?= $t('activity.heart_rate_range', ['min' => ActivityService::HEART_RATE_MIN, 'max' => ActivityService::HEART_RATE_MAX]) ?>"
                  data-msg-note="<?= $t('validation.max_length', ['max' => ActivityService::NOTE_MAX_LENGTH]) ?>">
                <?= $csrfField ?>
                <input type="hidden" name="expedition_id" value="<?= $e['id'] ?>">

                <?php if (array_diff_key($errors, ['expedition' => true]) !== []): ?>
                    <div class="alert" role="alert">
                        <span class="alert-icon"><img src="/img/login/alert.svg" alt="" width="15" height="15"></span>
                        <div class="alert-content">
                            <p class="alert-title"><?= $t('activity.errors_title') ?></p>
                            <p class="alert-text"><?= $t('activity.errors_text') ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <fieldset class="create-card discipline-picker" aria-describedby="discipline-error">
                    <legend><?= $t('activity.discipline') ?> <span class="required" aria-hidden="true">*</span></legend>
                    <div class="discipline-options">
                        <?php foreach ($e['disciplines'] as $discipline): ?>
                            <label class="discipline-option discipline-option--<?= $discipline['style'] ?>">
                                <input type="radio" name="discipline" value="<?= $discipline['id'] ?>" data-unit="<?= $discipline['unit'] ?>" data-factor="<?= $discipline['factor'] ?>"
                                       <?= $discipline['id'] === $form['discipline'] ? 'checked' : '' ?>>
                                <span class="dot" aria-hidden="true"></span>
                                <span><?= htmlspecialchars($discipline['label']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="field-error" id="discipline-error"><?= $fieldError('discipline') ?></p>
                </fieldset>

                <section class="create-card" aria-labelledby="metrics-title">
                    <h2 id="metrics-title"><?= $t('activity.section_metrics') ?></h2>
                    <div class="create-grid activity-grid">
                        <div class="field" data-distance-field>
                            <label for="distance"><?= $t('activity.distance_label') ?> (km) <span class="required" aria-hidden="true">*</span></label>
                            <input type="text" id="distance" name="distance" inputmode="decimal" placeholder="0,00"
                                   value="<?= htmlspecialchars($form['distance']) ?>" aria-describedby="distance-hint distance-error"<?= $invalid('distance') ?>>
                            <p class="field-hint" id="distance-hint"><?= $t('activity.distance_hint') ?></p>
                            <p class="field-error" id="distance-error"><?= $fieldError('distance') ?></p>
                        </div>

                        <fieldset class="field duration-field" aria-describedby="duration-hint duration-error">
                            <legend><?= $t('activity.duration_label') ?> <span class="required" aria-hidden="true">*</span></legend>
                            <div class="duration-inputs">
                                <label>
                                    <input type="number" name="hours" min="0" max="24" step="1" inputmode="numeric"
                                           value="<?= htmlspecialchars($form['hours']) ?>"<?= $invalid('duration') ?>>
                                    <span><?= $t('activity.hours') ?></span>
                                </label>
                                <label>
                                    <input type="number" name="minutes" min="0" max="59" step="1" inputmode="numeric"
                                           value="<?= htmlspecialchars($form['minutes']) ?>"<?= $invalid('duration') ?>>
                                    <span><?= $t('activity.minutes') ?></span>
                                </label>
                            </div>
                            <p class="field-hint" id="duration-hint"><?= $t('activity.duration_hint') ?></p>
                            <p class="field-error" id="duration-error"><?= $fieldError('duration') ?></p>
                        </fieldset>

                        <div class="field">
                            <label for="date"><?= $t('activity.date_label') ?> <span class="required" aria-hidden="true">*</span></label>
                            <input type="date" id="date" name="date" required min="<?= htmlspecialchars($e['startDate']) ?>"
                                   max="<?= (new DateTimeImmutable('today'))->format('Y-m-d') ?>"
                                   value="<?= htmlspecialchars($form['date']) ?>" aria-describedby="date-error"<?= $invalid('date') ?>>
                            <p class="field-error" id="date-error"><?= $fieldError('date') ?></p>
                        </div>

                        <div class="field">
                            <label for="heart-rate"><?= $t('activity.heart_rate_label') ?></label>
                            <input type="number" id="heart-rate" name="heart_rate" min="<?= ActivityService::HEART_RATE_MIN ?>"
                                   max="<?= ActivityService::HEART_RATE_MAX ?>" step="1" inputmode="numeric" placeholder="bpm"
                                   value="<?= htmlspecialchars($form['heartRate']) ?>" aria-describedby="heart-rate-error"<?= $invalid('heart_rate') ?>>
                            <p class="field-error" id="heart-rate-error"><?= $fieldError('heart_rate') ?></p>
                        </div>
                    </div>

                    <div class="field">
                        <label for="note"><?= $t('activity.note_label') ?></label>
                        <input type="text" id="note" name="note" maxlength="<?= ActivityService::NOTE_MAX_LENGTH ?>"
                               placeholder="<?= $t('activity.note_placeholder') ?>"
                               value="<?= htmlspecialchars($form['note']) ?>" aria-describedby="note-error"<?= $invalid('note') ?>>
                        <p class="field-error" id="note-error"><?= $fieldError('note') ?></p>
                    </div>
                </section>

                <section class="create-card" aria-labelledby="photo-title">
                    <h2 id="photo-title"><?= $t('photo.title') ?></h2>
                    <?php // Strefa to <label> pola pliku: klik i klawiatura działają bez JS, JS dokłada podgląd. ?>
                    <label class="photo-drop" for="photo">
                        <img src="/img/expeditions/plus.svg" alt="" width="16" height="16">
                        <span class="photo-drop-title"><?= $t('photo.drop') ?></span>
                        <span class="photo-drop-hint" id="photo-hint"><?= $t('photo.hint', ['max' => PhotoUploadService::MAX_BYTES / 1024 / 1024]) ?></span>
                    </label>
                    <input class="visually-hidden" type="file" id="photo" name="photo"
                           accept="<?= implode(',', array_keys(PhotoUploadService::TYPES)) ?>" data-max-bytes="<?= PhotoUploadService::MAX_BYTES ?>"
                           aria-describedby="photo-hint photo-error"<?= $invalid('photo') ?>>
                    <div class="photo-preview" data-photo-preview hidden>
                        <img src="" alt="" width="64" height="64" data-photo-thumb>
                        <span data-photo-name></span>
                        <button class="exp-button exp-button--ghost" type="button" data-photo-remove><?= $t('photo.remove') ?></button>
                    </div>
                    <p class="field-error" id="photo-error"><?= $fieldError('photo') ?></p>
                </section>

                <div class="create-actions">
                    <a class="exp-button exp-button--ghost" href="/expeditions/<?= $e['id'] ?>"><?= $t('activity.cancel') ?></a>
                    <button class="exp-button exp-button--primary" type="submit"><?= $t('activity.submit') ?></button>
                </div>
            </form>

            <?php require __DIR__ . '/partials/route-preview.html.php'; ?>
        </div>
    <?php endif; ?>
</main>
