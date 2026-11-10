<?php
// Dwa warianty z Figmy: pełny header aplikacji oraz uproszczony na ekranach logowania/rejestracji ($showNav = false).
$showNav ??= true;
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$navLinks = [
    '/expeditions' => 'nav.expeditions',
    '/activities/new' => 'nav.add_activity',
];
?>
<header class="site-header<?= $showNav ? '' : ' site-header--auth' ?>">
    <div class="site-header-inner">
        <a class="brand" href="/" aria-label="Dystans">
            <span class="brand-main" aria-hidden="true">DYST</span><span class="brand-accent" aria-hidden="true">ANS</span>
        </a>

        <?php if ($showNav): ?>
            <nav class="site-nav" aria-label="<?= $t('nav.label') ?>">
                <ul>
                    <?php foreach ($navLinks as $href => $label): ?>
                        <li>
                            <a href="<?= $href ?>"<?= $currentPath === $href ? ' aria-current="page"' : '' ?>><?= $t($label) ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        <?php endif; ?>

        <div class="header-actions">
            <nav class="lang-switch" aria-label="<?= $t('lang.label') ?>">
                <?php foreach (\App\Translator::LOCALES as $index => $code): ?>
                    <?php if ($index > 0): ?><span class="lang-separator" aria-hidden="true">/</span><?php endif; ?>
                    <a href="?lang=<?= $code ?>" hreflang="<?= $code ?>" lang="<?= $code ?>"
                       title="<?= $t('lang.' . $code) ?>"
                       <?= $code === $locale ? 'aria-current="true"' : '' ?>><?= strtoupper($code) ?></a>
                <?php endforeach; ?>
            </nav>

            <button class="theme-toggle" type="button" aria-label="<?= $t('theme.toggle') ?>" data-theme-toggle
                    data-label-light="<?= $t('theme.to_light') ?>" data-label-dark="<?= $t('theme.to_dark') ?>">
                <span class="theme-icon" aria-hidden="true"></span>
            </button>

            <?php if ($showNav): ?>
                <span class="header-divider" aria-hidden="true"></span>
                <?php if ($currentUser !== null): ?>
                    <?php // ponytail: samo imię w miejscu avatara z Figmy – menu konta z wylogowaniem w zadaniu 203. ?>
                    <span class="header-user"><?= htmlspecialchars($currentUser->name) ?></span>
                <?php else: ?>
                    <a class="header-login" href="/login"><?= $t('nav.login') ?></a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</header>
