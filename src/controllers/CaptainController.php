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
            'expedition' => $expedition,
            'queue' => (new CaptainService())->queue($expedition, $captain, $tab),
        ]);
    }
}
