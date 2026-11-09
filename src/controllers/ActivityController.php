<?php

declare(strict_types=1);

namespace App\Controller;

use App\Translator;

final class ActivityController extends AppController
{
    public function create(): void
    {
        $this->render('activity-new', [
            'pageTitle' => Translator::get('activity.title'),
            'activityTypes' => ['Bieg', 'Rower', 'Pływanie', 'Siłownia'],
        ]);
    }
}
