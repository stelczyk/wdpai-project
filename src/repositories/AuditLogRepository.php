<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;

/** Historia decyzji kapitana (tabela audit_log): kto, którą aktywność, jaka decyzja, kiedy. */
final class AuditLogRepository
{
    public function insert(int $expeditionId, int $activityId, int $actorId, string $action): void
    {
        $statement = Database::connect()->prepare(
            'INSERT INTO audit_log (expedition_id, activity_id, actor_id, action)
             VALUES (:expedition, :activity, :actor, :action)'
        );
        $statement->execute([
            'expedition' => $expeditionId,
            'activity' => $activityId,
            'actor' => $actorId,
            'action' => $action,
        ]);
    }

    /**
     * Ta sama decyzja dla wielu aktywności jednym zapytaniem („zatwierdź wszystkie”).
     * Id przekazujemy jako tablicę PostgreSQL i rozwijamy przez unnest – bez sklejania SQL z danych.
     *
     * @param list<int> $activityIds
     */
    public function insertMany(int $expeditionId, array $activityIds, int $actorId, string $action): void
    {
        $statement = Database::connect()->prepare(
            'INSERT INTO audit_log (expedition_id, activity_id, actor_id, action)
             SELECT :expedition, id, :actor, :action FROM unnest(CAST(:ids AS int[])) AS id'
        );
        $statement->execute([
            'expedition' => $expeditionId,
            'actor' => $actorId,
            'action' => $action,
            'ids' => '{' . implode(',', array_map('intval', $activityIds)) . '}',
        ]);
    }
}
