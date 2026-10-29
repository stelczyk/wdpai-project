<?php

declare(strict_types=1);

namespace App\Controller;

final class CaptainController extends AppController
{
    public function panel(int $id): void
    {
        $this->render('captain-panel', [
            'pageTitle' => 'Panel kapitana',
            'expeditionId' => $id,
            'pending' => [
                ['id' => 11, 'user' => 'Piotr', 'type' => 'Rower', 'km' => 42],
                ['id' => 12, 'user' => 'Kasia', 'type' => 'Bieg', 'km' => 10],
            ],
        ]);
    }
}
