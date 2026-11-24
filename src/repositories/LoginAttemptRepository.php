<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;

/** Nieudane logowania – podstawa blokady po serii błędnych haseł. */
final class LoginAttemptRepository
{
    /**
     * Ile minut zostało do końca blokady (0 = brak). Blokada trwa, dopóki w oknie $windowMinutes
     * jest co najmniej $maxAttempts porażek dla tego e-maila LUB tego IP, czyli do chwili, gdy
     * $maxAttempts-ta najnowsza porażka wypadnie z okna.
     */
    public function lockedMinutes(string $email, string $ip, int $maxAttempts, int $windowMinutes): int
    {
        $statement = Database::connect()->prepare(
            'SELECT CEIL(EXTRACT(EPOCH FROM attempted_at + make_interval(mins => :window) - now()) / 60)
             FROM login_attempts
             WHERE (email = :email OR ip = CAST(:ip AS inet))
               AND attempted_at > now() - make_interval(mins => :window)
             ORDER BY attempted_at DESC
             OFFSET :skip LIMIT 1'
        );
        $statement->execute(['email' => $email, 'ip' => $ip, 'window' => $windowMinutes, 'skip' => $maxAttempts - 1]);

        return (int) $statement->fetchColumn();
    }

    /** Zapisuje porażkę i przy okazji usuwa wpisy starsze niż doba – tabela nie rośnie w nieskończoność. */
    public function record(string $email, string $ip): void
    {
        $db = Database::connect();
        $db->exec("DELETE FROM login_attempts WHERE attempted_at < now() - interval '1 day'");
        $db->prepare('INSERT INTO login_attempts (email, ip) VALUES (:email, CAST(:ip AS inet))')
            ->execute(['email' => $email, 'ip' => $ip]);
    }

    /** Po udanym logowaniu licznik dla tego konta startuje od zera. */
    public function clear(string $email): void
    {
        Database::connect()->prepare('DELETE FROM login_attempts WHERE email = :email')->execute(['email' => $email]);
    }
}
