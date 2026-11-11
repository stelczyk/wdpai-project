<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;

final class ExpeditionRepository
{
    /** Id kapitana wyprawy albo null, gdy wyprawa nie istnieje. */
    public function findCaptainId(int $expeditionId): ?int
    {
        $statement = Database::connect()->prepare('SELECT captain_id FROM expeditions WHERE id = :id');
        $statement->execute(['id' => $expeditionId]);
        $captainId = $statement->fetchColumn();

        return $captainId === false ? null : (int) $captainId;
    }

    public function isMember(int $expeditionId, int $userId): bool
    {
        $statement = Database::connect()->prepare(
            'SELECT 1 FROM expedition_members WHERE expedition_id = :expedition AND user_id = :user'
        );
        $statement->execute(['expedition' => $expeditionId, 'user' => $userId]);

        return $statement->fetchColumn() !== false;
    }
}
