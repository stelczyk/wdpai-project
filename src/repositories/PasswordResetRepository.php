<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;

final class PasswordResetRepository
{
    public function create(int $userId, string $tokenHash, int $validForSeconds): void
    {
        $statement = Database::connect()->prepare(
            "INSERT INTO password_resets (user_id, token_hash, expires_at)
             VALUES (:user, :hash, now() + make_interval(secs => :seconds))"
        );
        $statement->execute(['user' => $userId, 'hash' => $tokenHash, 'seconds' => $validForSeconds]);
    }

    /** Id tokenu i użytkownika, gdy token istnieje, nie wygasł i nie był użyty; inaczej null. */
    public function findValid(string $tokenHash): ?array
    {
        $statement = Database::connect()->prepare(
            'SELECT id, user_id FROM password_resets
             WHERE token_hash = :hash AND used_at IS NULL AND expires_at > now()'
        );
        $statement->execute(['hash' => $tokenHash]);
        $row = $statement->fetch();

        return $row === false ? null : ['id' => (int) $row['id'], 'userId' => (int) $row['user_id']];
    }

    // Warunek used_at IS NULL: gdy dwa żądania użyją tokenu naraz, tylko jedno go „zużyje”.
    public function markUsed(int $id): bool
    {
        $statement = Database::connect()->prepare(
            'UPDATE password_resets SET used_at = now() WHERE id = :id AND used_at IS NULL'
        );
        $statement->execute(['id' => $id]);

        return $statement->rowCount() === 1;
    }
}
