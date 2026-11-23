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
}
