<?php require __DIR__ . '/partials/header.html.php'; ?>
<main>
    <h1>Logowanie</h1>
    <form method="post" action="/login">
        <p>
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" required autocomplete="email">
        </p>
        <p>
            <label for="password">Hasło</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </p>
        <button type="submit">Zaloguj się</button>
    </form>
</main>
<?php require __DIR__ . '/partials/footer.html.php'; ?>
