<?php

declare(strict_types=1);

namespace App\Repository;

use App\Database;

/** Dane dla crona przypomnień: kto jest nieaktywny, log wysyłek i blokada przed równoległym uruchomieniem. */
final class ReminderRepository
{
    // Dowolna, stała liczba – identyfikator blokady doradczej PostgreSQL dla tego jednego zadania.
    private const LOCK_KEY = 705;

    /**
     * Uczestnicy trwających wypraw bez treningu od $hours godzin (odrzucony się nie liczy), którzy należą
     * do wyprawy dłużej niż $hours i nie dostali w tym czasie przypomnienia o niej (status SENT).
     * Od razu z postępem drużyny (widok expedition_progress) i najbliższym punktem trasy – do treści maila.
     */
    public function findInactiveMembers(int $hours): array
    {
        $statement = Database::connect()->prepare(
            "SELECT u.id AS user_id, u.email, u.name, e.id AS expedition_id, e.name AS expedition_name,
                    p.approved_km, p.total_km,
                    next_cp.name AS next_name, next_cp.km_from_start AS next_km
             FROM expedition_members m
             JOIN users u ON u.id = m.user_id
             JOIN expeditions e ON e.id = m.expedition_id
             JOIN expedition_progress p ON p.expedition_id = e.id
             LEFT JOIN LATERAL (
                 SELECT c.name, c.km_from_start FROM checkpoints c
                 WHERE c.expedition_id = e.id AND c.km_from_start > p.approved_km
                 ORDER BY c.km_from_start LIMIT 1
             ) next_cp ON TRUE
             WHERE current_date BETWEEN e.start_date AND e.end_date
               AND m.joined_at < now() - make_interval(hours => :hours)
               AND NOT EXISTS (
                   SELECT 1 FROM activities a
                   WHERE a.expedition_id = e.id AND a.user_id = u.id AND a.status <> 'REJECTED'
                     AND a.activity_date >= (now() - make_interval(hours => :hours))::date
               )
               AND NOT EXISTS (
                   SELECT 1 FROM reminder_log r
                   WHERE r.expedition_id = e.id AND r.user_id = u.id AND r.status = 'SENT'
                     AND r.created_at > now() - make_interval(hours => :hours)
               )
             ORDER BY e.id, u.name"
        );
        $statement->execute(['hours' => $hours]);

        return $statement->fetchAll();
    }

    public function log(int $userId, int $expeditionId, string $status, ?string $error): void
    {
        $statement = Database::connect()->prepare(
            'INSERT INTO reminder_log (user_id, expedition_id, status, error) VALUES (:user, :expedition, :status, :error)'
        );
        $statement->execute(['user' => $userId, 'expedition' => $expeditionId, 'status' => $status, 'error' => $error]);
    }

    /**
     * Blokada doradcza: true tylko dla jednego procesu naraz. Drugi cron uruchomiony w trakcie pracy
     * pierwszego dostaje false i kończy pracę, zamiast wysłać te same maile drugi raz.
     */
    public function tryLock(): bool
    {
        return (bool) Database::connect()->query('SELECT pg_try_advisory_lock(' . self::LOCK_KEY . ')')->fetchColumn();
    }

    public function unlock(): void
    {
        Database::connect()->query('SELECT pg_advisory_unlock(' . self::LOCK_KEY . ')');
    }
}
