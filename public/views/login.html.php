<?php require __DIR__ . '/partials/header.html.php'; ?>
<main class="auth">
    <section class="auth-card" aria-labelledby="login-title">
        <div class="auth-body">
            <div class="auth-intro">
                <h1 id="login-title">Zaloguj się do wyprawy</h1>
                <p class="lead">Wprowadź swoje dane telemetryczne, aby kontynuować wspólną trasę.</p>
            </div>

            <?php if ($formError !== null): ?>
                <div class="alert" role="alert" data-alert>
                    <span class="alert-icon"><img src="/img/login/alert.svg" alt="" width="15" height="15"></span>
                    <div class="alert-content">
                        <p class="alert-title">Błąd autoryzacji wyprawy</p>
                        <p class="alert-text"><?= htmlspecialchars($formError) ?></p>
                    </div>
                    <button class="alert-close" type="button" aria-label="Zamknij powiadomienie" data-alert-close>
                        <img src="/img/login/close.svg" alt="" width="9" height="9">
                    </button>
                </div>
            <?php endif; ?>

            <form class="form" method="post" action="/login">
                <div class="field">
                    <label for="email">Adres e-mail <span class="required" aria-hidden="true">*</span></label>
                    <div class="input-wrap">
                        <input type="email" id="email" name="email" required
                               autocomplete="email" placeholder="jan.kowalski@dystans.app"
                               value="<?= htmlspecialchars($email) ?>"
                               aria-describedby="email-hint email-error"
                               <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
                        <img class="input-icon" src="/img/login/at.svg" alt="" width="17" height="17">
                    </div>
                    <p class="field-hint" id="email-hint">Połączone z kontem ekspedycyjnym</p>
                    <p class="field-error" id="email-error"><?= htmlspecialchars($errors['email'] ?? '') ?></p>
                </div>

                <div class="field">
                    <div class="field-row">
                        <label for="password">Hasło <span class="required" aria-hidden="true">*</span></label>
                        <a href="/forgot-password">Nie pamiętasz hasła?</a>
                    </div>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" required
                               autocomplete="current-password" placeholder="••••••••••••"
                               aria-describedby="password-error"
                               <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
                        <button class="input-action" type="button" aria-label="Pokaż hasło"
                                aria-pressed="false" data-password-toggle>
                            <img src="/img/login/eye.svg" alt="" width="17" height="11">
                        </button>
                    </div>
                    <p class="field-error" id="password-error"><?= htmlspecialchars($errors['password'] ?? '') ?></p>
                </div>

                <label class="checkbox">
                    <input type="checkbox" name="remember" value="1">
                    Zapamiętaj mnie na tym urządzeniu
                </label>

                <button class="button" type="submit">
                    Zaloguj się
                    <img src="/img/login/arrow.svg" alt="" width="13" height="13">
                </button>
            </form>

            <p class="divider">Lub</p>

            <div class="social">
                <button class="button-secondary" type="button">
                    <img src="/img/login/google.svg" alt="" width="16" height="16">
                    Kontynuuj z Google
                </button>
                <button class="button-secondary" type="button">
                    <img src="/img/login/strava.svg" alt="" width="16" height="16">
                    Kontynuuj ze Strava
                </button>
            </div>
        </div>

        <div class="auth-switch">
            <p>Nie masz jeszcze profilu wyprawy?</p>
            <a href="/register">
                Utwórz nowe konto drużynowe
                <img src="/img/login/chevron.svg" alt="" width="5" height="8">
            </a>
        </div>
    </section>
</main>
<script type="module" src="/js/login.js"></script>
<?php require __DIR__ . '/partials/footer.html.php'; ?>
