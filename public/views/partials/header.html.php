<header class="site-header">
    <a class="brand" href="/" aria-label="Dystans">
        <span class="brand-main" aria-hidden="true">DYST</span><span class="brand-accent" aria-hidden="true">ANS</span>
    </a>
    <div class="header-actions">
        <?php if ($showNav ?? true): ?>
            <nav class="site-nav" aria-label="<?= $t('nav.label') ?>">
                <ul>
                    <li><a href="/expeditions"><?= $t('nav.expeditions') ?></a></li>
                    <li><a href="/activities/new"><?= $t('nav.add_activity') ?></a></li>
                    <li><a href="/login"><?= $t('nav.login') ?></a></li>
                </ul>
            </nav>
        <?php endif; ?>
        <nav class="lang-switch" aria-label="<?= $t('lang.label') ?>">
            <?php foreach (\App\Translator::LOCALES as $code): ?>
                <a href="?lang=<?= $code ?>" hreflang="<?= $code ?>" lang="<?= $code ?>"
                   title="<?= $t('lang.' . $code) ?>"
                   <?= $code === $locale ? 'aria-current="true"' : '' ?>><?= strtoupper($code) ?></a>
            <?php endforeach; ?>
        </nav>
        <button class="theme-toggle" type="button" aria-label="<?= $t('theme.toggle') ?>" data-theme-toggle
                data-label-light="<?= $t('theme.to_light') ?>" data-label-dark="<?= $t('theme.to_dark') ?>"></button>
    </div>
</header>
