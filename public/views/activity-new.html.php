<?php require __DIR__ . '/partials/header.html.php'; ?>
<main>
    <h1>Dodaj aktywność</h1>
    <form method="post" action="/activities" enctype="multipart/form-data">
        <p>
            <label for="type">Rodzaj</label>
            <select id="type" name="type" required>
                <?php foreach ($activityTypes as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="distance">Dystans (km)</label>
            <input type="number" id="distance" name="distance" min="0" step="0.1" required>
        </p>
        <p>
            <label for="date">Data</label>
            <input type="date" id="date" name="date" required>
        </p>
        <p>
            <label for="photo">Zdjęcie</label>
            <input type="file" id="photo" name="photo" accept="image/*">
        </p>
        <button type="submit">Zapisz</button>
    </form>
</main>
<?php require __DIR__ . '/partials/footer.html.php'; ?>
