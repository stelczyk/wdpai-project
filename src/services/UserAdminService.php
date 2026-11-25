<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\UserRepository;
use PDOException;

/** Zarządzanie kontami przez administratora: lista, blokada, usunięcie (API /api/users). */
final class UserAdminService
{
    public const OK = 'ok';
    public const NOT_FOUND = 'not_found';
    public const SELF = 'self';          // administrator nie może zablokować ani usunąć samego siebie
    public const CAPTAIN = 'captain';    // kapitan prowadzi wyprawę – najpierw trzeba ją przekazać lub usunąć
    private const FK_VIOLATION = '23503'; // kod błędu PostgreSQL: naruszenie klucza obcego

    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    public function list(): array
    {
        return $this->users->findAllForAdmin();
    }

    public function setBlocked(User $admin, int $userId, bool $isBlocked): string
    {
        if ($userId === $admin->id) {
            return self::SELF;
        }

        return $this->users->setBlocked($userId, $isBlocked) ? self::OK : self::NOT_FOUND;
    }

    public function delete(User $admin, int $userId): string
    {
        if ($userId === $admin->id) {
            return self::SELF;
        }

        try {
            return $this->users->delete($userId) ? self::OK : self::NOT_FOUND;
        } catch (PDOException $exception) {
            // Baza pilnuje spójności (expeditions.captain_id ON DELETE RESTRICT) – tłumaczymy to na czytelny błąd.
            if ($exception->getCode() === self::FK_VIOLATION) {
                return self::CAPTAIN;
            }
            throw $exception;
        }
    }
}
