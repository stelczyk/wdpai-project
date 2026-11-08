<header class="site-header">
    <a class="brand" href="/" aria-label="Dystans">
        <span class="brand-main" aria-hidden="true">DYST</span><span class="brand-accent" aria-hidden="true">ANS</span>
    </a>
    <div class="header-actions">
        <?php if ($showNav ?? true): ?>
            <nav class="site-nav" aria-label="Menu główne">
                <ul>
                    <li><a href="/expeditions">Wyprawy</a></li>
                    <li><a href="/activities/new">Dodaj aktywność</a></li>
                    <li><a href="/login">Zaloguj</a></li>
                </ul>
            </nav>
        <?php endif; ?>
        <button class="theme-toggle" type="button" aria-label="Przełącz motyw" data-theme-toggle></button>
    </div>
</header>
