<?php

declare(strict_types=1);

namespace App\Controller;

use App\HttpException;
use App\Model\User;
use App\Service\ActivityFeedService;
use App\Service\ExpeditionCreationService;
use App\Service\ExpeditionService;
use App\Session;
use DateTimeImmutable;
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

        // Po utworzeniu wyprawy (?created=id) pokazujemy jej kod PIN – tylko jej kapitanowi.
        $createdId = (int) ($_GET['created'] ?? 0);
        $created = null;
        foreach (array_merge(...array_values($overview['sections'])) as $expedition) {
            if ($expedition['id'] === $createdId && $expedition['isCaptain']) {
                $created = $expedition;
            }
        }

        $this->render('expeditions', [
            'created' => $created,
            'pageTitle' => Translator::get('expeditions.title'),
            'scripts' => ['/js/expeditions.js'],
            'user' => $user,
            'sections' => $overview['sections'],
            'counts' => $overview['counts'],
            'stats' => $overview['stats'],
            'filters' => ['status' => $status, 'sort' => $sort, 'q' => $search],
        ]);
    }

    public function create(): void
    {
        $this->requireRole(User::ROLE_CAPTAIN, User::ROLE_ADMIN);
        $creation = new ExpeditionCreationService();

        $this->renderCreateForm($creation->emptyForm(new DateTimeImmutable('today')), [], $creation);
    }

    public function store(): void
    {
        $captain = $this->requireRole(User::ROLE_CAPTAIN, User::ROLE_ADMIN);
        $creation = new ExpeditionCreationService();
        $validation = $creation->validate($_POST, new DateTimeImmutable('today'));

        if ($validation['errors'] !== []) {
            http_response_code(422);
            $this->renderCreateForm($validation['form'], $validation['errors'], $creation);

            return;
        }

        $created = $creation->create($captain, $validation['form']);
        // Lista wypraw pokaże komunikat z kodem PIN do przekazania drużynie.
        $this->redirect('/expeditions?created=' . $created['id']);
    }

    private function renderCreateForm(array $form, array $errors, ExpeditionCreationService $creation): void
    {
        $this->render('expedition-new', [
            'pageTitle' => Translator::get('create.title'),
            'scripts' => ['/js/expedition-form.js'],
            'form' => $form,
            'errors' => $errors,
            'disciplines' => $creation->disciplines(),
        ]);
    }

    // Dołączanie kodem PIN – wywoływane przez fetch() z ekranu „Moje wyprawy”, odpowiada JSON-em.
    public function join(): void
    {
        $user = $this->requireLogin();

        try {
            $body = json_decode((string) file_get_contents('php://input'), true, 4, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $body = null;
        }
        $code = is_array($body) ? trim((string) ($body['code'] ?? '')) : '';

        if (preg_match('/^\d{6}$/', $code) !== 1) {
            $this->json(['error' => Translator::get('expeditions.join_invalid')], 422);
        }

        $join = (new ExpeditionService())->join($user, $code);
        $expedition = $join['expedition'];

        // Wynik serwisu → status HTTP: 404 brak kodu, 409 konflikt ze stanem wyprawy.
        match ($join['result']) {
            ExpeditionService::JOINED => $this->json([
                'message' => Translator::get('expeditions.join_success', ['name' => $expedition['name']]),
                'redirect' => '/expeditions/' . $expedition['id'],
            ]),
            ExpeditionService::JOIN_NOT_FOUND => $this->json(['error' => Translator::get('expeditions.join_not_found')], 404),
            default => $this->json(['error' => Translator::get('expeditions.join_' . $join['result'])], 409),
        };
    }

    // Dashboard wyprawy: postęp trasy z widoku expedition_progress, checkpointy i pozycje uczestników.
    public function show(int $id): void
    {
        $user = $this->requireExpeditionMember($id);
        Session::setActiveExpedition($id);

        $expedition = (new ExpeditionService())->dashboard($user, $id)
            ?? throw new HttpException(404, 'Expedition not found');

        $this->render('expedition', [
            'pageTitle' => $expedition['name'],
            'expedition' => $expedition,
            'feed' => (new ActivityFeedService())->recentForExpedition($user, $id),
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
