<main>
    <h1><?= $t('activity.title') ?></h1>
    <form method="post" action="/activities" enctype="multipart/form-data">
        <?= $csrfField ?>
        <p>
            <label for="type"><?= $t('activity.type') ?></label>
            <select id="type" name="type" required>
                <?php foreach ($activityTypes as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="distance"><?= $t('activity.distance') ?></label>
            <input type="number" id="distance" name="distance" min="0" step="0.1" required>
        </p>
        <p>
            <label for="date"><?= $t('activity.date') ?></label>
            <input type="date" id="date" name="date" required>
        </p>
        <p>
            <label for="photo"><?= $t('activity.photo') ?></label>
            <input type="file" id="photo" name="photo" accept="image/*">
        </p>
        <button type="submit"><?= $t('activity.submit') ?></button>
    </form>
</main>
