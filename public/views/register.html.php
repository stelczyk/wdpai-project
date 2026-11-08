<main class="auth">
    <section class="auth-card" aria-labelledby="register-title">
        <div class="auth-body">
            <div class="auth-intro">
                <h1 id="register-title">Dołącz do wyprawy</h1>
                <p class="lead">Załóż profil uczestnika i zacznij zamieniać treningi na kilometry trasy.</p>
            </div>

            <?php if ($formError !== null): ?>
                <div class="alert" role="alert" data-alert>
                    <span class="alert-icon"><img src="/img/login/alert.svg" alt="" width="15" height="15"></span>
                    <div class="alert-content">
                        <p class="alert-title">Nie udało się utworzyć konta</p>
                        <p class="alert-text"><?= htmlspecialchars($formError) ?></p>
                    </div>
                    <button class="alert-close" type="button" aria-label="Zamknij powiadomienie" data-alert-close>
                        <img src="/img/login/close.svg" alt="" width="9" height="9">
                    </button>
                </div>
            <?php endif; ?>

            <form class="form" method="post" action="/register">
                <div class="field">
                    <label for="name">Imię lub pseudonim <span class="required" aria-hidden="true">*</span></label>
                    <div class="input-wrap">
                        <input type="text" id="name" name="name" required maxlength="50"
                               autocomplete="nickname" placeholder="Jan Kowalski"
                               value="<?= htmlspecialchars($name) ?>"
                               aria-describedby="name-hint name-error"
                               <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>>
                        <img class="input-icon" src="/img/register/user.svg" alt="" width="16" height="16">
                    </div>
                    <p class="field-hint" id="name-hint">Tak zobaczą Cię inni w rankingu</p>
                    <p class="field-error" id="name-error"><?= htmlspecialchars($errors['name'] ?? '') ?></p>
                </div>

                <div class="field">
                    <label for="email">Adres e-mail <span class="required" aria-hidden="true">*</span></label>
                    <div class="input-wrap">
                        <input type="email" id="email" name="email" required
                               autocomplete="email" placeholder="jan.kowalski@dystans.app"
                               value="<?= htmlspecialchars($email) ?>"
                               aria-describedby="email-error"
                               <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
                        <img class="input-icon" src="/img/login/at.svg" alt="" width="17" height="17">
                    </div>
                    <p class="field-error" id="email-error"><?= htmlspecialchars($errors['email'] ?? '') ?></p>
                </div>

                <div class="field">
                    <label for="password">Hasło <span class="required" aria-hidden="true">*</span></label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" required minlength="8"
                               autocomplete="new-password" placeholder="••••••••••••"
                               aria-describedby="password-hint password-error"
                               <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
                        <button class="input-action" type="button" aria-label="Pokaż hasło"
                                aria-pressed="false" data-password-toggle>
                            <img src="/img/login/eye.svg" alt="" width="17" height="11">
                        </button>
                    </div>
                    <p class="field-hint" id="password-hint">Minimum 8 znaków</p>
                    <p class="field-error" id="password-error"><?= htmlspecialchars($errors['password'] ?? '') ?></p>
                </div>

                <div class="field">
                    <label for="password-confirm">Powtórz hasło <span class="required" aria-hidden="true">*</span></label>
                    <div class="input-wrap">
                        <input type="password" id="password-confirm" name="password_confirm" required minlength="8"
                               autocomplete="new-password" placeholder="••••••••••••"
                               aria-describedby="password-confirm-error"
                               <?= isset($errors['password_confirm']) ? 'aria-invalid="true"' : '' ?>>
                        <button class="input-action" type="button" aria-label="Pokaż hasło"
                                aria-pressed="false" data-password-toggle>
                            <img src="/img/login/eye.svg" alt="" width="17" height="11">
                        </button>
                    </div>
                    <p class="field-error" id="password-confirm-error"><?= htmlspecialchars($errors['password_confirm'] ?? '') ?></p>
                </div>

                <label class="checkbox">
                    <input type="checkbox" name="terms" value="1" required>
                    Akceptuję regulamin wypraw
                </label>

                <button class="button" type="submit">
                    Utwórz konto
                    <img src="/img/login/arrow.svg" alt="" width="13" height="13">
                </button>
            </form>
        </div>

        <div class="auth-switch">
            <p>Masz już profil wyprawy?</p>
            <a href="/login">
                Zaloguj się
                <img src="/img/login/chevron.svg" alt="" width="5" height="8">
            </a>
        </div>
    </section>
</main>
