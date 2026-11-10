<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Użytkownik zmapowany z wiersza tabeli users. Pola tylko do odczytu –
 * obiekt opisuje stan z bazy, zmiany przechodzą przez repozytorium.
 */
final readonly class User
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public string $role,
        public string $passwordHash,
    ) {
    }
}
