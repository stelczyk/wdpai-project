<?php require __DIR__ . '/partials/header.html.php'; ?>
<main class="auth">
    <section class="auth-card" aria-labelledby="login-title">
        <p class="eyebrow">Dostęp do platformy</p>
        <h1 id="login-title">Zaloguj się do wyprawy</h1>
        <p class="lead">Wprowadź swoje dane, aby kontynuować wspólną trasę.</p>

        <?php if ($formError !== null): ?>
            <div class="alert" role="alert">
                <p class="alert-title">Błąd logowania</p>
                <p><?= htmlspecialchars($formError) ?></p>
            </div>
        <?php endif; ?>

        <form class="form" method="post" action="/login">
            <div class="field">
                <label for="email">Adres e-mail</label>
                <input type="email" id="email" name="email" required
                       autocomplete="email" placeholder="twoj.email@dystans.app"
                       value="<?= htmlspecialchars($email) ?>"
                       aria-describedby="email-error"
                       <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
                <p class="field-error" id="email-error"><?= htmlspecialchars($errors['email'] ?? '') ?></p>
            </div>

            <div class="field">
                <div class="field-row">
                    <label for="password">Hasło</label>
                    <a href="/forgot-password">Nie pamiętasz hasła?</a>
                </div>
                <input type="password" id="password" name="password" required
                       autocomplete="current-password"
                       aria-describedby="password-error"
                       <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
                <p class="field-error" id="password-error"><?= htmlspecialchars($errors['password'] ?? '') ?></p>
            </div>

            <label class="checkbox">
                <input type="checkbox" name="remember" value="1">
                Zapamiętaj mnie na tym urządzeniu
            </label>

            <button class="button" type="submit">Zaloguj się</button>
        </form>

        <p class="auth-footer">
            Nie masz jeszcze konta?
            <a href="/register">Utwórz konto drużynowe</a>
        </p>
    </section>
</main>
<?php require __DIR__ . '/partials/footer.html.php'; ?>
