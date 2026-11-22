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
     * $search (opcjonalnie): fragment nazwy wyprawy albo nazwy któregoś z jej checkpointów.
     */
    public function findForMember(int $userId, string $search = ''): array
    {
        $params = ['user' => $userId];
        $searchCondition = '';

        if ($search !== '') {
            // Znaki %, _ i \ wpisane przez użytkownika traktujemy dosłownie, a nie jak wzorzec LIKE.
            $pattern = '%' . addcslashes($search, '%_\\') . '%';
            $params += ['name_pattern' => $pattern, 'checkpoint_pattern' => $pattern];
            $searchCondition = 'AND (e.name ILIKE :name_pattern OR EXISTS (
                                     SELECT 1 FROM checkpoints c
                                     WHERE c.expedition_id = e.id AND c.name ILIKE :checkpoint_pattern))';
        }

        return $this->selectWithProgress('JOIN', "TRUE $searchCondition", $params);
    }

    /**
     * Jedna wyprawa z postępem i wkładem użytkownika (dashboard). LEFT JOIN, bo admin
     * może oglądać wyprawę, do której nie należy – wtedy jego km i pozycja są puste.
     */
    public function findOneForUser(int $expeditionId, int $userId): ?array
    {
        $rows = $this->selectWithProgress('LEFT JOIN', 'e.id = :expedition', ['user' => $userId, 'expedition' => $expeditionId]);

        return $rows[0] ?? null;
    }

    /** Członkowie wyprawy z sumą zatwierdzonych km, od największego wkładu. */
    public function findMembersProgress(int $expeditionId): array
    {
        $statement = Database::connect()->prepare(
            "SELECT u.id, u.name, COALESCE(sum(a.route_km) FILTER (WHERE a.status = 'APPROVED'), 0) AS km
             FROM expedition_members m
             JOIN users u ON u.id = m.user_id
             LEFT JOIN activities a ON a.expedition_id = m.expedition_id AND a.user_id = m.user_id
             WHERE m.expedition_id = :id
             GROUP BY u.id, u.name
             ORDER BY km DESC, u.name"
        );
        $statement->execute(['id' => $expeditionId]);

        return array_map(
            static fn (array $row): array => ['id' => (int) $row['id'], 'name' => $row['name'], 'km' => (float) $row['km']],
            $statement->fetchAll(),
        );
    }

    /**
     * Wspólne zapytanie listy i dashboardu. $join i $where to stałe fragmenty z tej klasy,
     * nigdy dane od użytkownika – te idą wyłącznie przez $params.
     */
    private function selectWithProgress(string $join, string $where, array $params): array
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
             FROM expeditions e
             $join ranked r ON r.expedition_id = e.id AND r.user_id = :user
             JOIN expedition_progress p ON p.expedition_id = e.id
             JOIN users captain ON captain.id = e.captain_id
             WHERE $where
             ORDER BY e.start_date"
        );
        $statement->execute($params);

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

    /**
     * Ranking uczestników z zatwierdzonych treningów od $fromDate. Ranking etapu liczy tylko km przebyte
     * za punktem $fromRouteKm: suma narastająca drużyny mówi, gdzie na trasie był każdy trening,
     * a trening przekraczający punkt liczy się częściowo (km po przekroczeniu).
     * LEFT JOIN: uczestnik bez treningów w okresie też jest w tabeli, z 0 km. RANK(): remis = to samo miejsce.
     */
    public function findRanking(int $expeditionId, string $fromDate, float $fromRouteKm): array
    {
        $statement = Database::connect()->prepare(
            "WITH approved AS (
                 SELECT a.id, a.user_id, a.discipline_id, a.activity_date,
                        LEAST(a.route_km, GREATEST(0, sum(a.route_km) OVER (ORDER BY a.activity_date, a.id) - :from_km)) AS km
                 FROM activities a
                 WHERE a.expedition_id = :expedition AND a.status = 'APPROVED'
             )
             SELECT u.id, u.name, (e.captain_id = u.id) AS is_captain,
                    COALESCE(sum(ap.km), 0) AS km,
                    count(ap.id) AS workouts,
                    string_agg(DISTINCT d.name, ', ' ORDER BY d.name) AS disciplines,
                    RANK() OVER (ORDER BY COALESCE(sum(ap.km), 0) DESC) AS position
             FROM expedition_members m
             JOIN users u ON u.id = m.user_id
             JOIN expeditions e ON e.id = m.expedition_id
             LEFT JOIN approved ap ON ap.user_id = m.user_id
                                  AND ap.activity_date >= :from_date
                                  AND ap.km > 0
             LEFT JOIN disciplines d ON d.id = ap.discipline_id
             WHERE m.expedition_id = :expedition
             GROUP BY u.id, u.name, e.captain_id
             ORDER BY km DESC, u.name"
        );
        $statement->execute(['expedition' => $expeditionId, 'from_date' => $fromDate, 'from_km' => $fromRouteKm]);

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'isCaptain' => (bool) $row['is_captain'],
            'km' => (float) $row['km'],
            'workouts' => (int) $row['workouts'],
            'disciplines' => $row['disciplines'] === null ? [] : explode(', ', $row['disciplines']),
            'position' => (int) $row['position'],
        ], $statement->fetchAll());
    }

    /** Dyscypliny włączone w wyprawie z jej przelicznikiem (km trasy za 1 km lub 1 minutę treningu). */
    public function findDisciplineFactors(int $expeditionId): array
    {
        $statement = Database::connect()->prepare(
            'SELECT d.id, d.name, d.unit, ed.km_factor
             FROM expedition_disciplines ed JOIN disciplines d ON d.id = ed.discipline_id
             WHERE ed.expedition_id = :expedition ORDER BY d.id'
        );
        $statement->execute(['expedition' => $expeditionId]);

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'unit' => $row['unit'],
            'factor' => (float) $row['km_factor'],
        ], $statement->fetchAll());
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

    /** Wyprawa o podanym kodzie PIN (id, nazwa, data końca) albo null. */
    public function findByJoinCode(string $code): ?array
    {
        $statement = Database::connect()->prepare('SELECT id, name, end_date FROM expeditions WHERE join_code = :code');
        $statement->execute(['code' => $code]);
        $row = $statement->fetch();

        return $row === false ? null : ['id' => (int) $row['id'], 'name' => $row['name'], 'endDate' => $row['end_date']];
    }

    /**
     * Dodaje członka, jeśli wyprawa ma wolne miejsce. Wiersz wyprawy jest blokowany (FOR UPDATE)
     * do końca transakcji, więc dwie osoby dołączające naraz nie przekroczą limitu.
     * Zwraca false, gdy brak miejsc.
     */
    public function addMemberIfSpace(int $expeditionId, int $userId): bool
    {
        $db = Database::connect();
        $db->beginTransaction();

        try {
            $limit = $db->prepare('SELECT max_members FROM expeditions WHERE id = :id FOR UPDATE');
            $limit->execute(['id' => $expeditionId]);
            $maxMembers = (int) $limit->fetchColumn();

            $count = $db->prepare('SELECT count(*) FROM expedition_members WHERE expedition_id = :id');
            $count->execute(['id' => $expeditionId]);

            if ((int) $count->fetchColumn() >= $maxMembers) {
                $db->rollBack();

                return false;
            }

            $insert = $db->prepare('INSERT INTO expedition_members (expedition_id, user_id) VALUES (:expedition, :user)');
            $insert->execute(['expedition' => $expeditionId, 'user' => $userId]);
            $db->commit();

            return true;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    /** Słownik dyscyplin do formularza tworzenia wyprawy. */
    public function findAllDisciplines(): array
    {
        return Database::connect()->query('SELECT id, name, unit FROM disciplines ORDER BY id')->fetchAll();
    }

    public function joinCodeExists(string $code): bool
    {
        $statement = Database::connect()->prepare('SELECT 1 FROM expeditions WHERE join_code = :code');
        $statement->execute(['code' => $code]);

        return $statement->fetchColumn() !== false;
    }

    // Poniższe zapisy wywołuje ExpeditionService wewnątrz jednej transakcji.

    public function insertExpedition(string $name, int $captainId, string $startDate, string $endDate, int $maxMembers, string $joinCode): int
    {
        $statement = Database::connect()->prepare(
            'INSERT INTO expeditions (name, captain_id, start_date, end_date, max_members, join_code)
             VALUES (:name, :captain, :start, :end, :max, :code) RETURNING id'
        );
        $statement->execute([
            'name' => $name, 'captain' => $captainId, 'start' => $startDate,
            'end' => $endDate, 'max' => $maxMembers, 'code' => $joinCode,
        ]);

        return (int) $statement->fetchColumn();
    }

    public function insertCheckpoint(int $expeditionId, string $name, float $km): void
    {
        $statement = Database::connect()->prepare(
            'INSERT INTO checkpoints (expedition_id, name, km_from_start) VALUES (:expedition, :name, :km)'
        );
        $statement->execute(['expedition' => $expeditionId, 'name' => $name, 'km' => $km]);
    }

    public function insertDiscipline(int $expeditionId, int $disciplineId, float $kmFactor): void
    {
        $statement = Database::connect()->prepare(
            'INSERT INTO expedition_disciplines (expedition_id, discipline_id, km_factor) VALUES (:expedition, :discipline, :factor)'
        );
        $statement->execute(['expedition' => $expeditionId, 'discipline' => $disciplineId, 'factor' => $kmFactor]);
    }

    public function insertMember(int $expeditionId, int $userId): void
    {
        $statement = Database::connect()->prepare(
            'INSERT INTO expedition_members (expedition_id, user_id) VALUES (:expedition, :user)'
        );
        $statement->execute(['expedition' => $expeditionId, 'user' => $userId]);
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
