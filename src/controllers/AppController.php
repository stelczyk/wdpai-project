<?php

declare(strict_types=1);

namespace App\Controller;

abstract class AppController
{
    protected function render(string $template, array $variables = []): void
    {
        $templatePath = dirname(__DIR__, 2) . '/public/views/' . $template . '.html.php';

        if (!is_file($templatePath)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

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
}
