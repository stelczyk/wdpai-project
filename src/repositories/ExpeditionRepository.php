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

    /**
     * Wyprawy, do których należy użytkownik, z postępem drużyny, jego wkładem i pozycją w drużynie.
     * Pozycja liczona funkcją okna rank() po sumie zatwierdzonych km każdego członka.
     */
    public function findForMember(int $userId): array
    {
        $statement = Database::connect()->prepare(
            "WITH contributions AS (
                 SELECT m.expedition_id, m.user_id,
                        COALESCE(sum(a.route_km) FILTER (WHERE a.status = 'APPROVED'), 0) AS km
                 FROM expedition_members m
                 LEFT JOIN activities a ON a.expedition_id = m.expedition_id AND a.user_id = m.user_id
                 GROUP BY m.expedition_id, m.user_id
             ),
             ranked AS (
                 SELECT expedition_id, user_id, km,
                        rank() OVER (PARTITION BY expedition_id ORDER BY km DESC) AS position,
                        count(*) OVER (PARTITION BY expedition_id) AS member_count
                 FROM contributions
             )
             SELECT e.id, e.name, e.start_date, e.end_date, e.max_members, e.join_code, e.captain_id,
                    captain.name AS captain_name,
                    p.total_km, p.approved_km,
                    r.km AS my_km, r.position AS my_position, r.member_count,
                    (SELECT string_agg(d.name, ', ' ORDER BY d.id)
                     FROM expedition_disciplines ed JOIN disciplines d ON d.id = ed.discipline_id
                     WHERE ed.expedition_id = e.id) AS disciplines
             FROM ranked r
             JOIN expeditions e ON e.id = r.expedition_id
             JOIN expedition_progress p ON p.expedition_id = e.id
             JOIN users captain ON captain.id = e.captain_id
             WHERE r.user_id = :user
             ORDER BY e.start_date"
        );
        $statement->execute(['user' => $userId]);

        return $statement->fetchAll();
    }

    /** Checkpointy podanych wypraw, pogrupowane po id wyprawy i posortowane wzdłuż trasy. */
    public function findCheckpoints(array $expeditionIds): array
    {
        if ($expeditionIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($expeditionIds), '?'));
        $statement = Database::connect()->prepare(
            "SELECT expedition_id, name, km_from_start FROM checkpoints
             WHERE expedition_id IN ($placeholders) ORDER BY expedition_id, km_from_start"
        );
        $statement->execute(array_values($expeditionIds));

        $grouped = [];
        foreach ($statement->fetchAll() as $row) {
            $grouped[(int) $row['expedition_id']][] = ['name' => $row['name'], 'km' => (float) $row['km_from_start']];
        }

        return $grouped;
    }

    /** Zatwierdzone km użytkownika w danym roku, z podziałem na dyscypliny (od największego udziału). */
    public function findContributionByDiscipline(int $userId, int $year): array
    {
        $statement = Database::connect()->prepare(
            "SELECT d.name, sum(a.route_km) AS km
             FROM activities a JOIN disciplines d ON d.id = a.discipline_id
             WHERE a.user_id = :user AND a.status = 'APPROVED' AND extract(YEAR FROM a.activity_date) = :year
             GROUP BY d.name ORDER BY km DESC"
        );
        $statement->execute(['user' => $userId, 'year' => $year]);

        return array_map(
            static fn (array $row): array => ['name' => $row['name'], 'km' => (float) $row['km']],
            $statement->fetchAll(),
        );
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
