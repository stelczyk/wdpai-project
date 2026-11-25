<?php

declare(strict_types=1);

namespace App\Controller;

use App\HttpException;
use App\Model\User;
use App\Service\CaptainService;
use App\Service\ExpeditionService;
use App\Translator;

final class CaptainController extends AppController
{
    // Wartości ?decided=…, dla których panel pokazuje komunikat (klucze captain.decided_*).
    private const DECIDED_MESSAGES = ['approve', 'reject', 'approve_all'];

    // Kolejka weryfikacji z zakładką w adresie (?status=pending|approved|rejected) – tylko kapitan tej wyprawy.
    public function panel(int $id): void
    {
        $captain = $this->requireExpeditionCaptain($id);
        $tab = (string) ($_GET['status'] ?? array_key_first(CaptainService::TABS));
        if (!isset(CaptainService::TABS[$tab])) {
            throw new HttpException(400, 'Invalid captain tab');
        }

        $expedition = $this->expeditionDashboard($captain, $id);

        $this->render('captain-panel', [
            'pageTitle' => Translator::get('captain.title'),
            'scripts' => ['/js/api.js', '/js/format.js', '/js/captain.js'],
            'expedition' => $expedition,
            'queue' => (new CaptainService())->queue($expedition, $captain, $tab),
            // ?decided=… po decyzji bez JS (formularz → przekierowanie) – komunikat nad kolejką.
            'decided' => in_array($_GET['decided'] ?? '', self::DECIDED_MESSAGES, true) ? $_GET['decided'] : null,
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

        $result = (new CaptainService())->decide($captain, $id, $activityId, $decision);
        match ($result) {
            CaptainService::NOT_FOUND => throw new HttpException(404, 'Activity not found in this expedition'),
            // Ktoś rozpatrzył ją wcześniej (np. druga karta przeglądarki). Bez JS po prostu wracamy do kolejki.
            CaptainService::ALREADY_DECIDED => $this->wantsJson()
                ? $this->json(['error' => Translator::get('captain.already_decided')], 409)
                : $this->redirect('/captain/' . $id),
            default => null,
        };

        $this->respondDecided($captain, $id, $decision, ['status' => CaptainService::DECISIONS[$decision]]);
    }

    // Zatwierdź całą kolejkę jedną transakcją (CaptainService::approveAll) – fetch albo formularz bez JS.
    public function approveAll(int $id): void
    {
        $captain = $this->requireExpeditionCaptain($id);
        $approved = (new CaptainService())->approveAll($captain, $id);

        $this->respondDecided($captain, $id, 'approve_all', ['approved' => $approved]);
    }

    /**
     * Po decyzji: bez JS przekierowanie do panelu z komunikatem, dla fetch JSON z komunikatem
     * oraz licznikami i wpływem kolejki policzonymi od nowa (postęp trasy już jest inny).
     */
    private function respondDecided(User $captain, int $id, string $decided, array $payload): never
    {
        if (!$this->wantsJson()) {
            $this->redirect('/captain/' . $id . '?decided=' . $decided);
        }

        $expedition = $this->expeditionDashboard($captain, $id);
        $this->json($payload + ['message' => Translator::get('captain.decided_' . $decided)]
            + (new CaptainService())->summary($expedition));
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
