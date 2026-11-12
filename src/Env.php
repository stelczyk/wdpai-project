<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/** Konfiguracja ze zmiennych środowiskowych (.env przekazywany do kontenera przez docker-compose). */
final class Env
{
    public static function get(string $name): string
    {
        $value = getenv($name);

        if ($value === false || $value === '') {
            throw new RuntimeException('Missing environment variable: ' . $name);
        }

        return $value;
    }

    // Dla ustawień, które mogą być puste, np. login SMTP w lokalnym Mailpicie.
    public static function optional(string $name): string
    {
        return (string) getenv($name);
    }
}
