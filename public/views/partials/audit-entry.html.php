<?php
// Jeden wpis logu decyzji. Serwer rysuje nim listę, a ten sam partial z pustym $entry trafia do <template>,
// który captain.js klonuje po decyzji (textContent). Wejście: $entry z CaptainService::summary()['log'].
?>
<li class="audit-entry audit-entry--<?= htmlspecialchars($entry['action']) ?>">
    <span class="dot" aria-hidden="true"></span>
    <span>
        <strong data-field="label"><?= htmlspecialchars($entry['label']) ?></strong>
        <span class="audit-detail" data-field="detail"><?= htmlspecialchars($entry['detail']) ?></span>
        <span class="audit-by" data-field="by"><?= htmlspecialchars($entry['by']) ?></span>
    </span>
</li>
