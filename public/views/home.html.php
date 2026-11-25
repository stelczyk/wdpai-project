<?php
// Strona startowa: czym jest Dystans i jak działa. Przyciski zależą od tego, czy ktoś jest zalogowany
// ($currentUser wstrzykuje AppController::render).
$steps = ['join', 'log', 'ride'];
?>
<main class="expeditions-page home-page">
    <section class="exp-hero" aria-labelledby="home-title">
        <div class="exp-hero-text">
            <p class="eyebrow">
                <span class="dot dot--accent" aria-hidden="true"></span>
                <span class="eyebrow-accent"><?= $t('home.eyebrow') ?></span>
            </p>
            <h1 id="home-title"><?= $t('home.heading') ?></h1>
            <p class="lead"><?= $t('home.lead') ?></p>
            <p class="home-actions">
                <?php if ($currentUser === null): ?>
                    <a class="exp-button exp-button--primary" href="/register"><?= $t('home.cta_register') ?></a>
                    <a class="exp-button exp-button--ghost" href="/login"><?= $t('home.cta_login') ?></a>
                <?php else: ?>
                    <a class="exp-button exp-button--primary" href="/expeditions"><?= $t('home.cta') ?></a>
                    <a class="exp-button exp-button--ghost" href="/activities/new"><?= $t('nav.add_activity') ?></a>
                <?php endif; ?>
            </p>
        </div>
    </section>

    <section class="exp-section" aria-labelledby="home-steps-title">
        <div class="exp-section-head">
            <h2 id="home-steps-title"><?= $t('home.steps_title') ?></h2>
        </div>
        <ol class="exp-stats home-steps">
            <?php foreach ($steps as $number => $step): ?>
                <li class="stat-card home-step">
                    <span class="home-step-number" aria-hidden="true"><?= sprintf('%02d', $number + 1) ?></span>
                    <h3><?= $t('home.step_' . $step . '_title') ?></h3>
                    <p><?= $t('home.step_' . $step . '_text') ?></p>
                </li>
            <?php endforeach; ?>
        </ol>
    </section>
</main>
