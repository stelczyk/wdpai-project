<main class="auth">
    <section class="auth-card" aria-labelledby="forgot-title">
        <div class="auth-body">
            <div class="auth-intro">
                <h1 id="forgot-title"><?= $t('reset.forgot_heading') ?></h1>
                <p class="lead"><?= $t('reset.forgot_lead') ?></p>
            </div>

            <?php if ($sent): ?>
                <div class="alert alert--success" role="status">
                    <div class="alert-content">
                        <p class="alert-title"><?= $t('reset.sent_title') ?></p>
                        <p class="alert-text"><?= $t('reset.sent_text') ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <form class="form" method="post" action="/forgot-password">
                <?= $csrfField ?>
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

                <button class="button" type="submit">
                    <?= $t('reset.send_link') ?>
                    <img src="/img/login/arrow.svg" alt="" width="13" height="13">
                </button>
            </form>
        </div>

        <div class="auth-switch">
            <p><?= $t('reset.remembered') ?></p>
            <a href="/login">
                <?= $t('register.login_link') ?>
                <img src="/img/login/chevron.svg" alt="" width="5" height="8">
            </a>
        </div>
    </section>
</main>
