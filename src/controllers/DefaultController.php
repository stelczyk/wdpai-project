<?php

declare(strict_types=1);

namespace App\Controller;

final class DefaultController extends AppController
{
    public function index(): void
    {
        $this->render('home', [
            'pageTitle' => 'Dystans',
            'tagline' => 'Wirtualne wyprawy ze znajomymi: każdy trening to kilometry na wspólnej trasie.',
        ]);
    }

    private const ERRORS = [
        400 => ['Nieprawidłowe żądanie', 'Adres zawiera nieprawidłowe dane, np. błędny identyfikator.'],
        401 => ['Wymagane logowanie', 'Zaloguj się, aby zobaczyć tę stronę.'],
        403 => ['Brak dostępu', 'Nie masz uprawnień do tej strony.'],
        404 => ['Nie znaleziono strony', 'Taka strona nie istnieje albo została usunięta.'],
        500 => ['Błąd serwera', 'Coś poszło nie tak po naszej stronie. Spróbuj ponownie za chwilę.'],
    ];

    // Status HTTP ustawia index.php; tutaj tylko dobieramy treść strony błędu.
    public function error(int $statusCode): void
    {
        [$title, $message] = self::ERRORS[$statusCode] ?? self::ERRORS[500];

        $this->render('error', [
            'pageTitle' => $title,
            'statusCode' => $statusCode,
            'message' => $message,
        ]);
    }
}
