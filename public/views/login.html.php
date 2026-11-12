<main class="auth">
    <section class="auth-card" aria-labelledby="login-title">
        <div class="auth-body">
            <div class="auth-intro">
                <h1 id="login-title"><?= $t('login.heading') ?></h1>
                <p class="lead"><?= $t('login.lead') ?></p>
            </div>

            <?php if ($passwordChanged): ?>
                <div class="alert alert--success" role="status">
                    <div class="alert-content">
                        <p class="alert-title"><?= $t('reset.changed_title') ?></p>
                        <p class="alert-text"><?= $t('reset.changed_text') ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($formError !== null): ?>
                <div class="alert" role="alert" data-alert>
                    <span class="alert-icon"><img src="/img/login/alert.svg" alt="" width="15" height="15"></span>
                    <div class="alert-content">
                        <p class="alert-title"><?= $t('login.error_title') ?></p>
                        <p class="alert-text"><?= htmlspecialchars($formError) ?></p>
                    </div>
                    <button class="alert-close" type="button" aria-label="<?= $t('auth.close_alert') ?>" data-alert-close>
                        <img src="/img/login/close.svg" alt="" width="9" height="9">
                    </button>
                </div>
            <?php endif; ?>

            <form class="form" method="post" action="/login">
                <?= $csrfField ?>
                <div class="field">
                    <label for="email"><?= $t('auth.email') ?> <span class="required" aria-hidden="true">*</span></label>
                    <div class="input-wrap">
                        <input type="email" id="email" name="email" required
                               autocomplete="email" placeholder="<?= $t('auth.email_placeholder') ?>"
                               value="<?= htmlspecialchars($email) ?>"
                               aria-describedby="email-hint email-error"
                               <?= isset($errors['email']) ? 'aria-invalid="true"' : '' ?>>
                        <img class="input-icon" src="/img/login/at.svg" alt="" width="17" height="17">
                    </div>
                    <p class="field-hint" id="email-hint"><?= $t('login.email_hint') ?></p>
                    <p class="field-error" id="email-error"><?= htmlspecialchars($errors['email'] ?? '') ?></p>
                </div>

                <div class="field">
                    <div class="field-row">
                        <label for="password"><?= $t('auth.password') ?> <span class="required" aria-hidden="true">*</span></label>
                        <a href="/forgot-password"><?= $t('login.forgot') ?></a>
                    </div>
                    <div class="input-wrap">
                        <input type="password" id="password" name="password" required
                               autocomplete="current-password" placeholder="••••••••••••"
                               aria-describedby="password-error"
                               <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
                        <button class="input-action" type="button" aria-label="<?= $t('auth.show_password') ?>"
                                aria-pressed="false" data-password-toggle
                                data-label-show="<?= $t('auth.show_password') ?>" data-label-hide="<?= $t('auth.hide_password') ?>">
                            <img src="/img/login/eye.svg" alt="" width="17" height="11">
                        </button>
                    </div>
                    <p class="field-error" id="password-error"><?= htmlspecialchars($errors['password'] ?? '') ?></p>
                </div>

                <label class="checkbox">
                    <input type="checkbox" name="remember" value="1">
                    <?= $t('login.remember') ?>
                </label>

                <button class="button" type="submit">
                    <?= $t('login.submit') ?>
                    <img src="/img/login/arrow.svg" alt="" width="13" height="13">
                </button>
            </form>

            <p class="divider"><?= $t('login.or') ?></p>

            <div class="social">
                <button class="button-secondary" type="button">
                    <img src="/img/login/google.svg" alt="" width="16" height="16">
                    <?= $t('login.google') ?>
                </button>
                <button class="button-secondary" type="button">
                    <img src="/img/login/strava.svg" alt="" width="16" height="16">
                    <?= $t('login.strava') ?>
                </button>
            </div>
        </div>

        <div class="auth-switch">
            <p><?= $t('login.no_account') ?></p>
            <a href="/register">
                <?= $t('login.register_link') ?>
                <img src="/img/login/chevron.svg" alt="" width="5" height="8">
            </a>
        </div>
    </section>
</main>
