<?php

declare(strict_types=1);

namespace App\Controller;

use App\Translator;

final class CaptainController extends AppController
{
    public function panel(int $id): void
    {
        $this->requireExpeditionCaptain($id);

        $this->render('captain-panel', [
            'pageTitle' => Translator::get('captain.title'),
            'expeditionId' => $id,
            'pending' => [
                ['id' => 11, 'user' => 'Piotr', 'type' => 'Rower', 'km' => 42],
                ['id' => 12, 'user' => 'Kasia', 'type' => 'Bieg', 'km' => 10],
            ],
        ]);
    }
}
