<?php

declare(strict_types=1);

namespace App\Controller;

final class ExpeditionController extends AppController
{
    // ponytail: dane na sztywno do czasu podłączenia bazy (repozytorium + serwis)
    public function index(): void
    {
        $this->render('expeditions', [
            'pageTitle' => 'Wyprawy',
            'expeditions' => [
                ['id' => 1, 'name' => 'Kraków → Monza', 'distanceKm' => 1100],
                ['id' => 2, 'name' => 'Gdańsk → Zakopane', 'distanceKm' => 650],
            ],
        ]);
    }

    public function show(int $id): void
    {
        $this->render('expedition', [
            'pageTitle' => 'Dashboard wyprawy',
            'expeditionId' => $id,
            'routeName' => 'Kraków → Monza',
            'progressKm' => 312,
            'totalKm' => 1100,
        ]);
    }

    public function ranking(int $id): void
    {
        $this->render('ranking', [
            'pageTitle' => 'Ranking wyprawy',
            'expeditionId' => $id,
            'ranking' => [
                ['name' => 'Anna', 'km' => 128],
                ['name' => 'Piotr', 'km' => 104],
                ['name' => 'Kasia', 'km' => 80],
            ],
        ]);
    }
}
