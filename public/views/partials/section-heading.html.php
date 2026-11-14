<?php // Nagłówek sekcji listy wypraw: kolorowy znacznik, tytuł, dopisek po prawej. Wymaga $marker, $headingId, $heading, $aside (już przetłumaczone). ?>
<header class="exp-section-head">
    <h2 id="<?= $headingId ?>"><span class="marker marker--<?= $marker ?>" aria-hidden="true"></span><?= $heading ?></h2>
    <p><?= $aside ?></p>
</header>
