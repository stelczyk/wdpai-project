<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;
use App\Model\User;

final class UserRepository
{
    private const COLUMNS = 'id, email, name, role, password_hash';

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
        );
    }
}
