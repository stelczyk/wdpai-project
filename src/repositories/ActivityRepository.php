<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;

final class ActivityRepository
{
    /**
     * Ostatnie aktywności drużyny: zatwierdzone wszystkich + własne (także oczekujące i odrzucone) oglądającego.
     * JOIN-y: autor (users), dyscyplina ze słownika (disciplines), kapitan wyprawy (expeditions).
     * total = liczba wszystkich widocznych wpisów (funkcja okna liczona przed LIMIT).
     */
    public function findRecentForExpedition(int $expeditionId, int $viewerId, int $limit): array
    {
        $statement = Database::connect()->prepare(
            "SELECT a.id, a.user_id, u.name AS user_name, (e.captain_id = a.user_id) AS is_captain,
                    d.name AS discipline, d.unit, a.distance_km, a.duration_min, a.avg_heart_rate,
                    a.activity_date, a.note, a.route_km, a.status,
                    count(*) OVER () AS total
             FROM activities a
             JOIN users u ON u.id = a.user_id
             JOIN disciplines d ON d.id = a.discipline_id
             JOIN expeditions e ON e.id = a.expedition_id
             WHERE a.expedition_id = :expedition AND (a.status = 'APPROVED' OR a.user_id = :viewer)
             ORDER BY a.activity_date DESC, a.created_at DESC
             LIMIT :limit"
        );
        $statement->bindValue('expedition', $expeditionId, \PDO::PARAM_INT);
        $statement->bindValue('viewer', $viewerId, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
