<main class="auth">
    <section class="auth-card" aria-labelledby="register-title">
        <div class="auth-body">
            <div class="auth-intro">
                <h1 id="register-title"><?= $t('register.heading') ?></h1>
                <p class="lead"><?= $t('register.lead') ?></p>
            </div>

            <?php if ($formError !== null): ?>
                <div class="alert" role="alert" data-alert>
                    <span class="alert-icon"><img src="/img/login/alert.svg" alt="" width="15" height="15"></span>
                    <div class="alert-content">
                        <p class="alert-title"><?= $t('register.error_title') ?></p>
                        <p class="alert-text"><?= htmlspecialchars($formError) ?></p>
                    </div>
                    <button class="alert-close" type="button" aria-label="<?= $t('auth.close_alert') ?>" data-alert-close>
                        <img src="/img/login/close.svg" alt="" width="9" height="9">
                    </button>
                </div>
            <?php endif; ?>

            <form class="form" method="post" action="/register">
                <div class="field">
                    <label for="name"><?= $t('register.name') ?> <span class="required" aria-hidden="true">*</span></label>
                    <div class="input-wrap">
                        <input type="text" id="name" name="name" required maxlength="50"
                               autocomplete="nickname" placeholder="<?= $t('register.name_placeholder') ?>"
                               value="<?= htmlspecialchars($name) ?>"
                               aria-describedby="name-hint name-error"
                               <?= isset($errors['name']) ? 'aria-invalid="true"' : '' ?>>
                        <img class="input-icon" src="/img/register/user.svg" alt="" width="16" height="16">
                    </div>
                    <p class="field-hint" id="name-hint"><?= $t('register.name_hint') ?></p>
                    <p class="field-error" id="name-error"><?= htmlspecialchars($errors['name'] ?? '') ?></p>
                </div>

                <div class="field">
                    <label for="email"><?= $t('auth.email') ?> <span class="required" aria-hidden="true">*</span></label>
                    <div class="input-wrap">
                        <input type="email" id="email" name="email" required
                               autocomplete="email" placeholder="<?= $t('auth.email_placeholder') ?>"
                               value="<?= htmlspecialchars($email) ?>"
                               aria-describedby="email-error"
                               <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
                        <img class="input-icon" src="/img/login/at.svg" alt="" width="17" height="17">
                    </div>
                    <p class="field-error" id="email-error"><?= htmlspecialchars($errors['email'] ?? '') ?></p>
                </div>

                <div class="field">
                    <label for="password"><?= $t('auth.password') ?> <span class="required" aria-hidden="true">*</span></label>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" required minlength="8"
                               autocomplete="new-password" placeholder="••••••••••••"
                               aria-describedby="password-hint password-error"
                               <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
                        <button class="input-action" type="button" aria-label="<?= $t('auth.show_password') ?>"
                                aria-pressed="false" data-password-toggle
                                data-label-show="<?= $t('auth.show_password') ?>" data-label-hide="<?= $t('auth.hide_password') ?>">
                            <img src="/img/login/eye.svg" alt="" width="17" height="11">
                        </button>
                    </div>
                    <p class="field-hint" id="password-hint"><?= $t('register.password_hint') ?></p>
                    <p class="field-error" id="password-error"><?= htmlspecialchars($errors['password'] ?? '') ?></p>
                </div>

                <div class="field">
                    <label for="password-confirm"><?= $t('register.password_confirm') ?> <span class="required" aria-hidden="true">*</span></label>
                    <div class="input-wrap">
                        <input type="password" id="password-confirm" name="password_confirm" required minlength="8"
                               autocomplete="new-password" placeholder="••••••••••••"
                               aria-describedby="password-confirm-error"
                               <?= isset($errors['password_confirm']) ? 'aria-invalid="true"' : '' ?>>
                        <button class="input-action" type="button" aria-label="<?= $t('auth.show_password') ?>"
                                aria-pressed="false" data-password-toggle
                                data-label-show="<?= $t('auth.show_password') ?>" data-label-hide="<?= $t('auth.hide_password') ?>">
                            <img src="/img/login/eye.svg" alt="" width="17" height="11">
                        </button>
                    </div>
                    <p class="field-error" id="password-confirm-error"><?= htmlspecialchars($errors['password_confirm'] ?? '') ?></p>
                </div>

                <label class="checkbox">
                    <input type="checkbox" name="terms" value="1" required>
                    <?= $t('register.terms') ?>
                </label>

                <button class="button" type="submit">
                    <?= $t('register.submit') ?>
                    <img src="/img/login/arrow.svg" alt="" width="13" height="13">
                </button>
            </form>
        </div>

        <div class="auth-switch">
            <p><?= $t('register.has_account') ?></p>
            <a href="/login">
                <?= $t('register.login_link') ?>
                <img src="/img/login/chevron.svg" alt="" width="5" height="8">
            </a>
        </div>
    </section>
</main>
