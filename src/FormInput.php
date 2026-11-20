<?php

declare(strict_types=1);

namespace App;

use DateTimeImmutable;

/** Parsowanie pól formularzy wspólne dla serwisów (tworzenie wyprawy, dodawanie aktywności). */
final class FormInput
{
    /** Data w formacie Y-m-d z <input type="date">; null, gdy pusta albo nieistniejąca (np. 2026-02-30). */
    public static function date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }

    // Akceptujemy przecinek dziesiętny („0,35”), bo tak piszą użytkownicy w PL.
    public static function number(string $value): ?float
    {
        $number = filter_var(str_replace(',', '.', trim($value)), FILTER_VALIDATE_FLOAT);

        return $number === false ? null : $number;
    }
}
