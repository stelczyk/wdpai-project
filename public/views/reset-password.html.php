<main class="auth">
    <section class="auth-card" aria-labelledby="reset-title">
        <div class="auth-body">
            <div class="auth-intro">
                <h1 id="reset-title"><?= $t('reset.new_heading') ?></h1>
                <?php if ($tokenValid): ?>
                    <p class="lead"><?= $t('reset.new_lead') ?></p>
                <?php endif; ?>
            </div>

            <?php if (!$tokenValid): ?>
                <div class="alert" role="alert">
                    <span class="alert-icon"><img src="/img/login/alert.svg" alt="" width="15" height="15"></span>
                    <div class="alert-content">
                        <p class="alert-title"><?= $t('reset.invalid_title') ?></p>
                        <p class="alert-text"><?= $t('reset.invalid_text') ?></p>
                    </div>
                </div>
                <a class="button" href="/forgot-password"><?= $t('reset.request_again') ?></a>
            <?php else: ?>
                <form class="form" method="post" action="/reset-password">
                    <?= $csrfField ?>
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                    <div class="field">
                        <label for="password"><?= $t('reset.new_password') ?> <span class="required" aria-hidden="true">*</span></label>
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

                    <button class="button" type="submit">
                        <?= $t('reset.save_password') ?>
                        <img src="/img/login/arrow.svg" alt="" width="13" height="13">
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</main>
