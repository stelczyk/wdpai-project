<?php

declare(strict_types=1);

namespace App\Controller;

final class ActivityController extends AppController
{
    public function create(): void
    {
        $this->render('activity-new', [
            'pageTitle' => 'Dodaj aktywność',
            'activityTypes' => ['Bieg', 'Rower', 'Pływanie', 'Siłownia'],
        ]);
    }
}
