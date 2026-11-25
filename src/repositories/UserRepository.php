<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;
use App\Model\User;
use PDO;
use PDOException;

final class UserRepository
{
    private const COLUMNS = 'id, email, name, role, password_hash, is_blocked';

    // lower() po obu stronach – tak samo jak unikalny indeks users_email_unique w bazie.
    public function findByEmail(string $email): ?User
    {
        $statement = Database::connect()->prepare(
            'SELECT ' . self::COLUMNS . ' FROM users WHERE lower(email) = lower(:email)'
        );
        $statement->execute(['email' => $email]);

        return $this->map($statement->fetch());
    }

    public function findById(int $id): ?User
    {
        $statement = Database::connect()->prepare('SELECT ' . self::COLUMNS . ' FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $this->map($statement->fetch());
    }

    // Rola i data utworzenia z wartości domyślnych w bazie (role DEFAULT 'USER').
    public function create(string $email, string $name, string $passwordHash): User
    {
        $statement = Database::connect()->prepare(
            'INSERT INTO users (email, name, password_hash) VALUES (:email, :name, :hash)
             RETURNING ' . self::COLUMNS
        );
        $statement->execute(['email' => $email, 'name' => $name, 'hash' => $passwordHash]);

        return $this->map($statement->fetch());
    }

    public function updatePassword(int $userId, string $passwordHash): void
    {
        $statement = Database::connect()->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $statement->execute(['hash' => $passwordHash, 'id' => $userId]);
    }

    /**
     * Lista dla panelu administratora – tylko pola potrzebne w API, bez hasha hasła.
     * captain = prowadzi jakąś wyprawę, więc nie da się go usunąć (FK ON DELETE RESTRICT).
     *
     * @return list<array{id: int, name: string, email: string, role: string, isBlocked: bool, isCaptain: bool}>
     */
    public function findAllForAdmin(): array
    {
        $rows = Database::connect()->query(
            'SELECT u.id, u.name, u.email, u.role, u.is_blocked,
                    EXISTS (SELECT 1 FROM expeditions e WHERE e.captain_id = u.id) AS is_captain
             FROM users u
             ORDER BY u.id'
        )->fetchAll();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'role' => $row['role'],
            'isBlocked' => (bool) $row['is_blocked'],
            'isCaptain' => (bool) $row['is_captain'],
        ], $rows);
    }

    /** @return bool false, gdy takiego użytkownika nie ma */
    public function setBlocked(int $id, bool $isBlocked): bool
    {
        $statement = Database::connect()->prepare('UPDATE users SET is_blocked = :blocked WHERE id = :id');
        $statement->bindValue('blocked', $isBlocked, PDO::PARAM_BOOL);
        $statement->bindValue('id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() === 1;
    }

    /**
     * Usuwa konto razem z jego członkostwami i aktywnościami (CASCADE w schemacie).
     *
     * @return bool false, gdy takiego użytkownika nie ma
     * @throws PDOException 23503, gdy użytkownik jest kapitanem wyprawy (ON DELETE RESTRICT)
     */
    public function delete(int $id): bool
    {
        $statement = Database::connect()->prepare('DELETE FROM users WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() === 1;
    }

    private function map(array|false $row): ?User
    {
        if ($row === false) {
            return null;
        }

        return new User(
            id: (int) $row['id'],
            email: $row['email'],
            name: $row['name'],
            role: $row['role'],
            passwordHash: $row['password_hash'],
            isBlocked: (bool) $row['is_blocked'],
        );
    }
}
