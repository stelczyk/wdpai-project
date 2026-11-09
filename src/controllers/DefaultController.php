<?php

declare(strict_types=1);

namespace App\Controller;

use App\Translator;

final class DefaultController extends AppController
{
    // Kody, dla których katalog tłumaczeń ma treść strony błędu (error.<kod>.title / .message).
    private const ERROR_CODES = [400, 401, 403, 404, 500];

    public function index(): void
    {
        $this->render('home', [
            'pageTitle' => Translator::get('home.title'),
        ]);
    }

    // Status HTTP ustawia index.php; tutaj tylko dobieramy treść strony błędu.
    public function error(int $statusCode): void
    {
        $code = in_array($statusCode, self::ERROR_CODES, true) ? $statusCode : 500;

        $this->render('error', [
            'pageTitle' => Translator::get("error.$code.title"),
            'statusCode' => $statusCode,
            'message' => Translator::get("error.$code.message"),
        ]);
    }
}
