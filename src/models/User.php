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
        public bool $isBlocked = false,
    ) {
    }

    public const ROLE_CAPTAIN = 'CAPTAIN';
    public const ROLE_ADMIN = 'ADMIN';

    // ADMIN ma wgląd w każdą wyprawę i każdy panel kapitana bez członkostwa.
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Inicjały do awatara: „Jan Kowalski” → „JK” (pasek trasy, feed aktywności). */
    public static function initialsOf(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: ['?'];

        return mb_strtoupper(implode('', array_map(
            static fn (string $part): string => mb_substr($part, 0, 1),
            array_slice($parts, 0, 2),
        )));
    }

    // Zakładać wyprawy mogą kapitanowie i administrator.
    public function canCreateExpeditions(): bool
    {
        return in_array($this->role, [self::ROLE_CAPTAIN, self::ROLE_ADMIN], true);
    }
}
