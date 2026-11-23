<?php

declare(strict_types=1);

namespace App\Controller;

use App\HttpException;
use App\Service\CaptainService;
use App\Service\ExpeditionService;
use App\Translator;

final class CaptainController extends AppController
{
    // Kolejka weryfikacji z zakładką w adresie (?status=pending|approved|rejected) – tylko kapitan tej wyprawy.
    public function panel(int $id): void
    {
        $captain = $this->requireExpeditionCaptain($id);
        $tab = (string) ($_GET['status'] ?? array_key_first(CaptainService::TABS));
        if (!isset(CaptainService::TABS[$tab])) {
            throw new HttpException(400, 'Invalid captain tab');
        }

        $expedition = (new ExpeditionService())->dashboard($captain, $id)
            ?? throw new HttpException(404, 'Expedition not found');

        $this->render('captain-panel', [
            'pageTitle' => Translator::get('captain.title'),
            'scripts' => ['/js/format.js', '/js/captain.js'],
            'expedition' => $expedition,
            'queue' => (new CaptainService())->queue($expedition, $captain, $tab),
            // ?decided=approve|reject po decyzji bez JS (formularz → przekierowanie) – komunikat nad kolejką.
            'decided' => isset(CaptainService::DECISIONS[$_GET['decided'] ?? '']) ? $_GET['decided'] : null,
        ]);
    }

    /**
     * Zatwierdź / odrzuć aktywność. captain.js wysyła fetch POST z JSON-em {decision} i nagłówkiem X-CSRF-Token
     * (dostaje JSON z nowymi licznikami), bez JS przycisk wysyła zwykły formularz i wraca przekierowaniem.
     */
    public function decide(int $id, int $activityId): void
    {
        $captain = $this->requireExpeditionCaptain($id);
        $decision = $this->decisionFromRequest();
        if (!isset(CaptainService::DECISIONS[$decision])) {
            throw new HttpException(400, 'Invalid decision');
        }

        $service = new CaptainService();
        $result = $service->decide($captain, $id, $activityId, $decision);
        match ($result) {
            CaptainService::NOT_FOUND => throw new HttpException(404, 'Activity not found in this expedition'),
            // Ktoś rozpatrzył ją wcześniej (np. druga karta przeglądarki). Bez JS po prostu wracamy do kolejki.
            CaptainService::ALREADY_DECIDED => $this->wantsJson()
                ? $this->json(['error' => Translator::get('captain.already_decided')], 409)
                : $this->redirect('/captain/' . $id),
            default => null,
        };

        if (!$this->wantsJson()) {
            $this->redirect('/captain/' . $id . '?decided=' . $decision);
        }

        // Po zmianie statusu postęp trasy jest inny – liczniki i wpływ kolejki liczymy od nowa.
        $expedition = (new ExpeditionService())->dashboard($captain, $id);
        $this->json([
            'status' => CaptainService::DECISIONS[$decision],
            'message' => Translator::get('captain.decided_' . $decision),
        ] + $service->summary($expedition));
    }

    /** Decyzja z JSON-a (fetch) albo z pola formularza (bez JS). */
    private function decisionFromRequest(): string
    {
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            try {
                $body = json_decode((string) file_get_contents('php://input'), true, 4, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return '';
            }

            return is_array($body) && is_string($body['decision'] ?? null) ? $body['decision'] : '';
        }

        return (string) ($_POST['decision'] ?? '');
    }
}
