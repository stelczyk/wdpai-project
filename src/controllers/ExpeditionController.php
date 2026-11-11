<?php

declare(strict_types=1);

namespace App\Controller;

use App\Translator;

final class ExpeditionController extends AppController
{
    // TODO: dane na sztywno do czasu podłączenia bazy (repozytorium + serwis)
    public function index(): void
    {
        $this->requireLogin();

        $this->render('expeditions', [
            'pageTitle' => Translator::get('expeditions.title'),
            'expeditions' => [
                ['id' => 1, 'name' => 'Kraków → Monza', 'distanceKm' => 1100],
                ['id' => 2, 'name' => 'Gdańsk → Zakopane', 'distanceKm' => 650],
            ],
        ]);
    }

    public function show(int $id): void
    {
        $this->requireExpeditionMember($id);

        $this->render('expedition', [
            'pageTitle' => Translator::get('expedition.title'),
            'expeditionId' => $id,
            'routeName' => 'Kraków → Monza',
            'progressKm' => 312,
            'totalKm' => 1100,
        ]);
    }

    public function ranking(int $id): void
    {
        $this->requireExpeditionMember($id);

        $this->render('ranking', [
            'pageTitle' => Translator::get('ranking.title'),
            'expeditionId' => $id,
            'ranking' => [
                ['name' => 'Anna', 'km' => 128],
                ['name' => 'Piotr', 'km' => 104],
                ['name' => 'Kasia', 'km' => 80],
            ],
        ]);
    }
}
