<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\UserRepository;
use App\Session;

final class AuthService
{
    // Hash losowego hasła. Gdy konta nie ma, i tak wykonujemy password_verify,
    // żeby czas odpowiedzi nie zdradzał, czy podany e-mail istnieje w bazie.
    private const DUMMY_HASH = '$2y$10$OceXrzjwifJ1ODU1O817RulR04wGr4ZpHm9LfaHq.p3RLSBHYe/Ma';

    // Jeden odczyt zalogowanego użytkownika na żądanie (header, kontroler).
    private static ?User $currentUser = null;

    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    /** Zwraca użytkownika, gdy e-mail i hasło są poprawne; w przeciwnym razie null. */
    public function attempt(string $email, string $password): ?User
    {
        $user = $this->users->findByEmail($email);
        $passwordMatches = password_verify($password, $user?->passwordHash ?? self::DUMMY_HASH);

        return $user !== null && $passwordMatches ? $user : null;
    }

    public function login(User $user): void
    {
        Session::setUserId($user->id);
        self::$currentUser = $user;
    }

    // Tożsamość odtwarzana z sesji przy każdym żądaniu – dane i rola zawsze świeże z bazy.
    public function currentUser(): ?User
    {
        if (self::$currentUser === null && Session::userId() !== null) {
            self::$currentUser = $this->users->findById(Session::userId());
        }

        return self::$currentUser;
    }
}
