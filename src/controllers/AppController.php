<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Translator;

abstract class AppController
{
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
