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

    public const ROLE_CAPTAIN = 'CAPTAIN';
    public const ROLE_ADMIN = 'ADMIN';

    // ADMIN ma wgląd w każdą wyprawę i każdy panel kapitana bez członkostwa.
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    // Zakładać wyprawy mogą kapitanowie i administrator.
    public function canCreateExpeditions(): bool
    {
        return in_array($this->role, [self::ROLE_CAPTAIN, self::ROLE_ADMIN], true);
    }
}
