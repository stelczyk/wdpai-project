<?php

declare(strict_types=1);

namespace App\Controller;

use App\HttpException;
use App\Service\ExpeditionService;
use App\Translator;

final class ExpeditionController extends AppController
{
    private const SEARCH_MAX_LENGTH = 100;

    // Filtry z adresu: ?status=…&q=…&sort=… – stan listy można skopiować lub dodać do zakładek.
    public function index(): void
    {
        $user = $this->requireLogin();

        $status = (string) ($_GET['status'] ?? ExpeditionService::STATUS_ALL);
        $sort = (string) ($_GET['sort'] ?? ExpeditionService::SORT_START);
        $search = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, self::SEARCH_MAX_LENGTH);

        // Nieznana wartość filtra to błąd żądania, a nie powód do zgadywania.
        if (!in_array($status, ExpeditionService::STATUSES, true) || !in_array($sort, ExpeditionService::SORTS, true)) {
            throw new HttpException(400, 'Invalid expedition filter');
        }

        $overview = (new ExpeditionService())->overviewFor($user, $search, $status, $sort);

        $this->render('expeditions', [
            'pageTitle' => Translator::get('expeditions.title'),
            'scripts' => ['/js/expeditions.js'],
            'user' => $user,
            'sections' => $overview['sections'],
            'counts' => $overview['counts'],
            'stats' => $overview['stats'],
            'filters' => ['status' => $status, 'sort' => $sort, 'q' => $search],
        ]);
    }

    // TODO: dane na sztywno do czasu dashboardu wyprawy (epik 4)
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
