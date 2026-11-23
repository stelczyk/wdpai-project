<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;

final class ActivityRepository
{
    /** Feed: zatwierdzone wszystkich + własne (także oczekujące i odrzucone) oglądającego, od najnowszych. */
    public function findRecentForExpedition(int $expeditionId, int $viewerId, int $limit): array
    {
        return $this->findCards(
            "(a.status = 'APPROVED' OR a.user_id = :viewer)",
            ['viewer' => $viewerId],
            'a.activity_date DESC, a.created_at DESC',
            $expeditionId,
            $limit,
        );
    }

    /**
     * Kolejka kapitana: aktywności o danym statusie. Oczekujące od najstarszej (rozpatrywane po kolei),
     * już rozpatrzone od najnowszej.
     */
    public function findByStatus(int $expeditionId, string $status, int $limit): array
    {
        return $this->findCards(
            'a.status = :status',
            ['status' => $status],
            $status === 'PENDING' ? 'a.activity_date, a.created_at' : 'a.activity_date DESC, a.created_at DESC',
            $expeditionId,
            $limit,
        );
    }

    /** Status aktywności tej wyprawy albo null, gdy takiej aktywności w tej wyprawie nie ma. */
    public function findStatus(int $activityId, int $expeditionId): ?string
    {
        $statement = Database::connect()->prepare(
            'SELECT status FROM activities WHERE id = :activity AND expedition_id = :expedition'
        );
        $statement->execute(['activity' => $activityId, 'expedition' => $expeditionId]);
        $status = $statement->fetchColumn();

        return $status === false ? null : $status;
    }

    /**
     * Decyzja kapitana. Warunek status = 'PENDING' w samym UPDATE: przy dwóch równoczesnych decyzjach
     * tylko pierwsza zmieni wiersz, druga dostanie 0 i nie nadpisze wyniku. Zwraca, czy wiersz zmieniono.
     */
    public function decide(int $activityId, int $expeditionId, string $status): bool
    {
        $statement = Database::connect()->prepare(
            "UPDATE activities SET status = :status
             WHERE id = :activity AND expedition_id = :expedition AND status = 'PENDING'"
        );
        $statement->execute(['status' => $status, 'activity' => $activityId, 'expedition' => $expeditionId]);

        return $statement->rowCount() === 1;
    }

    /** Liczba aktywności i suma km trasy w każdym statusie – liczniki zakładek panelu kapitana. */
    public function countByStatus(int $expeditionId): array
    {
        $statement = Database::connect()->prepare(
            'SELECT status, count(*) AS total, sum(route_km) AS km FROM activities
             WHERE expedition_id = :expedition GROUP BY status'
        );
        $statement->execute(['expedition' => $expeditionId]);

        $counts = [];
        foreach ($statement->fetchAll() as $row) {
            $counts[$row['status']] = ['count' => (int) $row['total'], 'km' => (float) $row['km']];
        }

        return $counts;
    }

    /**
     * Wspólne zapytanie kart aktywności (feed i panel kapitana). JOIN-y: autor (users), dyscyplina ze słownika
     * (disciplines), kapitan wyprawy (expeditions). total = liczba pasujących wpisów (funkcja okna przed LIMIT).
     * $where i $orderBy to stałe fragmenty z tej klasy – dane od użytkownika idą tylko przez $params.
     */
    private function findCards(string $where, array $params, string $orderBy, int $expeditionId, int $limit): array
    {
        $statement = Database::connect()->prepare(
            "SELECT a.id, a.user_id, u.name AS user_name, (e.captain_id = a.user_id) AS is_captain,
                    d.name AS discipline, d.unit, a.distance_km, a.duration_min, a.avg_heart_rate,
                    a.activity_date, a.note, a.photo_path, a.route_km, a.status,
                    count(*) OVER () AS total
             FROM activities a
             JOIN users u ON u.id = a.user_id
             JOIN disciplines d ON d.id = a.discipline_id
             JOIN expeditions e ON e.id = a.expedition_id
             WHERE a.expedition_id = :expedition AND $where
             ORDER BY $orderBy
             LIMIT :limit"
        );
        $statement->bindValue('expedition', $expeditionId, \PDO::PARAM_INT);
        $statement->bindValue('limit', $limit, \PDO::PARAM_INT);
        foreach ($params as $name => $value) {
            $statement->bindValue($name, $value, is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $statement->execute();

        return $statement->fetchAll();
    }

    /** Nowa aktywność – status PENDING nadaje baza (DEFAULT), do akceptacji kapitana. */
    public function insert(array $activity): int
    {
        $statement = Database::connect()->prepare(
            'INSERT INTO activities (expedition_id, user_id, discipline_id, distance_km, duration_min,
                                     activity_date, avg_heart_rate, note, photo_path, route_km)
             VALUES (:expedition, :user, :discipline, :distance, :duration, :date, :heart_rate, :note, :photo, :route_km)
             RETURNING id'
        );
        $statement->execute([
            'expedition' => $activity['expeditionId'],
            'user' => $activity['userId'],
            'discipline' => $activity['disciplineId'],
            'distance' => $activity['distanceKm'],
            'duration' => $activity['durationMin'],
            'date' => $activity['date'],
            'heart_rate' => $activity['heartRate'],
            'note' => $activity['note'],
            'photo' => $activity['photoPath'] ?? null,
            'route_km' => $activity['routeKm'],
        ]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Dni, w których użytkownik dodał aktywność na tej wyprawie (bez odrzuconych), od najnowszego,
     * z sumą zatwierdzonych km danego dnia – z tego serwis liczy km tygodnia i serię dni.
     */
    public function findDailyTotals(int $expeditionId, int $userId): array
    {
        $statement = Database::connect()->prepare(
            "SELECT activity_date, COALESCE(sum(route_km) FILTER (WHERE status = 'APPROVED'), 0) AS approved_km
             FROM activities
             WHERE expedition_id = :expedition AND user_id = :user AND status <> 'REJECTED'
             GROUP BY activity_date
             ORDER BY activity_date DESC"
        );
        $statement->execute(['expedition' => $expeditionId, 'user' => $userId]);

        return array_map(
            static fn (array $row): array => ['date' => $row['activity_date'], 'km' => (float) $row['approved_km']],
            $statement->fetchAll(),
        );
    }
}
