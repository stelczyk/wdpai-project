<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Service\ActivityService;
use App\Service\ExpeditionService;
use App\Session;
use App\Translator;
use DateTimeImmutable;

final class ActivityController extends AppController
{
    // Formularz dla wyprawy z ?expedition=ID (przycisk na dashboardzie) albo aktywnej z headera.
    public function create(): void
    {
        $user = $this->requireLogin();
        $today = new DateTimeImmutable('today');
        $service = new ActivityService();
        $choice = $service->choose($user, self::idFrom($_GET['expedition'] ?? null), Session::activeExpeditionId(), $today);

        $this->renderForm($user, $choice, $choice['expedition'] === null ? [] : $service->emptyForm($choice['expedition'], $today), []);
    }

    public function store(): void
    {
        $user = $this->requireLogin();
        $today = new DateTimeImmutable('today');
        $service = new ActivityService();
        $requestedId = self::idFrom($_POST['expedition_id'] ?? null);
        $choice = $service->choose($user, $requestedId, null, $today);

        // Wyprawa spoza listy (cudza, zakończona, podmienione id) – formularz wraca z błędem.
        if ($choice['expedition'] === null || $choice['expedition']['id'] !== $requestedId) {
            http_response_code(422);
            $form = $choice['expedition'] === null ? [] : $service->emptyForm($choice['expedition'], $today);
            $this->renderForm($user, $choice, $form, ['expedition' => Translator::get('activity.expedition_unavailable')]);

            return;
        }

        $validation = $service->validate($user, $choice['expedition'], $_POST, $today);
        if ($validation['errors'] !== []) {
            http_response_code(422);
            $this->renderForm($user, $choice, $validation['form'], $validation['errors']);

            return;
        }

        $service->save($validation['activity']);
        Session::setActiveExpedition($requestedId);
        // POST → przekierowanie → GET: odświeżenie dashboardu nie wyśle formularza drugi raz.
        $this->redirect('/expeditions/' . $requestedId . '?added=1');
    }

    private function renderForm(User $user, array $choice, array $form, array $errors): void
    {
        $expedition = $choice['expedition'];

        $this->render('activity-new', [
            'pageTitle' => Translator::get('activity.title'),
            'scripts' => ['/js/activity-form.js', '/js/activity-preview.js'],
            'expeditions' => $choice['expeditions'],
            'expedition' => $expedition,
            // Stan trasy i rankingu dla podglądu na żywo – te same dane co dashboard, bez nowego SQL.
            'route' => $expedition === null ? null : (new ExpeditionService())->dashboard($user, $expedition['id']),
            'form' => $form,
            'errors' => $errors,
        ]);
    }

    private static function idFrom(mixed $value): ?int
    {
        return is_string($value) && preg_match('/^[1-9]\d{0,9}$/', $value) === 1 ? (int) $value : null;
    }
}
