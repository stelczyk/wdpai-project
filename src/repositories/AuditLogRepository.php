<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;

/** Historia decyzji kapitana (tabela audit_log): kto, którą aktywność, jaka decyzja, kiedy. */
final class AuditLogRepository
{
    /**
     * Ostatnie decyzje w wyprawie: kto zdecydował, o czyjej aktywności, jakiej dyscypliny i ile km.
     * LEFT JOIN-y, bo klucze audit_log mają ON DELETE SET NULL – wpis zostaje w historii,
     * nawet gdy aktywność albo konto zostanie usunięte (wtedy pola są puste).
     */
    public function findRecent(int $expeditionId, int $limit): array
    {
        $statement = Database::connect()->prepare(
            'SELECT l.id, l.action, l.created_at, actor.name AS actor_name,
                    author.name AS author_name, d.name AS discipline, a.route_km
             FROM audit_log l
             LEFT JOIN users actor ON actor.id = l.actor_id
             LEFT JOIN activities a ON a.id = l.activity_id
             LEFT JOIN users author ON author.id = a.user_id
             LEFT JOIN disciplines d ON d.id = a.discipline_id
             WHERE l.expedition_id = :expedition
             ORDER BY l.created_at DESC, l.id DESC
             LIMIT :limit'
        );
        $statement->bindValue('expedition', $expeditionId, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

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
