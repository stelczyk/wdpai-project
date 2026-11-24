<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\LoginAttemptRepository;
use App\Repository\UserRepository;
use App\Session;

final class AuthService
{
    // Hash losowego hasła. Gdy konta nie ma, i tak wykonujemy password_verify,
    // żeby czas odpowiedzi nie zdradzał, czy podany e-mail istnieje w bazie.
    private const DUMMY_HASH = '$2y$10$OceXrzjwifJ1ODU1O817RulR04wGr4ZpHm9LfaHq.p3RLSBHYe/Ma';

    // Jeden odczyt zalogowanego użytkownika na żądanie (header, kontroler).
    private static ?User $currentUser = null;

    // 5 nieudanych prób w ciągu 15 minut (na ten e-mail albo z tego IP) blokuje logowanie.
    private const MAX_ATTEMPTS = 5;
    private const WINDOW_MINUTES = 15;

    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly LoginAttemptRepository $attempts = new LoginAttemptRepository(),
    ) {
    }

    /** Minuty do końca blokady logowania; 0 = można próbować. Sprawdzane PRZED weryfikacją hasła. */
    public function lockedMinutes(string $email, string $ip): int
    {
        return $this->attempts->lockedMinutes(mb_strtolower($email), $ip, self::MAX_ATTEMPTS, self::WINDOW_MINUTES);
    }

    /** Zwraca użytkownika, gdy e-mail i hasło są poprawne; w przeciwnym razie null i zapis porażki. */
    public function attempt(string $email, string $password, string $ip): ?User
    {
        $user = $this->users->findByEmail($email);
        $passwordMatches = password_verify($password, $user?->passwordHash ?? self::DUMMY_HASH);

        if ($user !== null && $passwordMatches) {
            $this->attempts->clear(mb_strtolower($email));

            return $user;
        }

        $this->attempts->record(mb_strtolower($email), $ip);
        // Log bezpieczeństwa: kto i skąd – nigdy hasło.
        error_log(sprintf('Failed login for %s from %s', $email, $ip));

        return null;
    }

    public function login(User $user): void
    {
        Session::setUserId($user->id);
        self::$currentUser = $user;
    }

    public function logout(): void
    {
        Session::destroy();
        self::$currentUser = null;
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
