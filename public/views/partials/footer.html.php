<footer class="site-footer">
    <div class="site-footer-inner">
        <p class="footer-brand">
            <span class="footer-name"><?= $t('footer.brand') ?></span>
            <span class="footer-dot" aria-hidden="true">•</span>
            <span><?= $t('footer.copyright', ['year' => date('Y')]) ?></span>
        </p>
        <?php // ponytail: zwykły tekst zamiast linków – strony regulaminu, prywatności i wsparcia jeszcze nie istnieją. ?>
        <ul class="footer-links">
            <li><?= $t('footer.terms') ?></li>
            <li><?= $t('footer.privacy') ?></li>
            <li><?= $t('footer.support') ?></li>
        </ul>
        <p class="footer-status">
            <span class="status-dot" aria-hidden="true"></span>
            <?= $t('footer.status') ?>
        </p>
    </div>
</footer>
