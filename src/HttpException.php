<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Kontrolowany błąd HTTP (400, 401, 403, 404…). Rzucany w routerze lub kontrolerze,
 * łapany w public/index.php, który ustawia status i renderuje stronę błędu.
 */
final class HttpException extends RuntimeException
{
    public function __construct(private readonly int $statusCode, string $message = '')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
