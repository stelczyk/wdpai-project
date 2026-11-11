<?php

declare(strict_types=1);

namespace App\Controller;

use App\HttpException;
use App\Model\User;
use App\Repository\ExpeditionRepository;
use App\Service\AuthService;
use App\Translator;

abstract class AppController
{
    // Kontrola dostępu: wywoływana na początku akcji, zanim kontroler cokolwiek odczyta lub zmieni.
    // Ukrycie linku w widoku to tylko wygoda – o dostępie decydują te metody.

    /** @throws HttpException 401, gdy nikt nie jest zalogowany */
    protected function requireLogin(): User
    {
        return (new AuthService())->currentUser() ?? throw new HttpException(401);
    }

    /** @throws HttpException 401 / 404 (brak wyprawy) / 403 (nie należy do wyprawy) */
    protected function requireExpeditionMember(int $expeditionId): User
    {
        $user = $this->requireLogin();
        $expeditions = new ExpeditionRepository();

        if ($expeditions->findCaptainId($expeditionId) === null) {
            throw new HttpException(404);
        }

        if (!$user->isAdmin() && !$expeditions->isMember($expeditionId, $user->id)) {
            throw new HttpException(403);
        }

        return $user;
    }

    /** @throws HttpException 401 / 404 (brak wyprawy) / 403 (nie jest kapitanem tej wyprawy) */
    protected function requireExpeditionCaptain(int $expeditionId): User
    {
        $user = $this->requireLogin();
        $captainId = (new ExpeditionRepository())->findCaptainId($expeditionId) ?? throw new HttpException(404);

        if (!$user->isAdmin() && $captainId !== $user->id) {
            throw new HttpException(403);
        }

        return $user;
    }

    protected function render(string $template, array $variables = []): void
    {
        $templatePath = dirname(__DIR__, 2) . '/public/views/' . $template . '.html.php';

        if (!is_file($templatePath)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        // Dostępne w każdym widoku i w layoucie: $t('klucz') zwraca przetłumaczony i już zabezpieczony tekst.
        $t = static fn (string $key, array $params = []): string => htmlspecialchars(Translator::get($key, $params));
        $locale = Translator::locale();
        $currentUser = (new AuthService())->currentUser();

        extract($variables, EXTR_SKIP);

        // Widok renderujemy do bufora, a potem wstawiamy go do wspólnego layoutu jako $content.
        // Gdy widok rzuci wyjątek, porzucamy bufor – inaczej połowa strony trafiłaby przed stronę błędu.
        ob_start();
        try {
            require $templatePath;
        } catch (\Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
        $content = ob_get_clean();

        require dirname(__DIR__, 2) . '/public/views/layout.html.php';
    }

    // 303 See Other: po POST przeglądarka wykona GET pod nowy adres (odświeżenie nie wyśle formularza ponownie).
    protected function redirect(string $path): never
    {
        header('Location: ' . $path, true, 303);
        exit;
    }
}
