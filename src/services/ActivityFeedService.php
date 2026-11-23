<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\ActivityRepository;
use App\Translator;
use DateTimeImmutable;

/** Karty aktywności: kto, kiedy, dyscyplina i metryki dopasowane do dyscypliny (tempo, prędkość, tętno). */
final class ActivityFeedService
{
    public const FEED_LIMIT = 10;

    // Nazwy dyscyplin w bazie → klasa CSS z kolorem odznaki (jak w Figmie).
    public const DISCIPLINE_STYLES = ['Bieg' => 'run', 'Rower' => 'bike', 'Pływanie' => 'swim', 'Siłownia' => 'gym'];

    public function __construct(private readonly ActivityRepository $activities = new ActivityRepository())
    {
    }

    /** @return array{items: list<array>, total: int} */
    public function recentForExpedition(User $viewer, int $expeditionId, ?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today');
        return $this->cards($this->activities->findRecentForExpedition($expeditionId, $viewer->id, self::FEED_LIMIT), $viewer, $today);
    }

    /**
     * Wiersze z ActivityRepository → karty (partial activity-card) + liczba wszystkich pasujących wpisów.
     * Wspólne dla feedu na dashboardzie i kolejki w panelu kapitana.
     *
     * @return array{items: list<array>, total: int}
     */
    public function cards(array $rows, User $viewer, DateTimeImmutable $today): array
    {
        return [
            'items' => array_map(fn (array $row): array => $this->card($row, $viewer, $today), $rows),
            'total' => (int) ($rows[0]['total'] ?? 0),
        ];
    }

    /** Wiersz z bazy → dane karty aktywności (partial activity-card). */
    private function card(array $row, User $viewer, DateTimeImmutable $today): array
    {
        return [
            'id' => (int) $row['id'],
            'userName' => $row['user_name'],
            'initials' => User::initialsOf($row['user_name']),
            'isMe' => (int) $row['user_id'] === $viewer->id,
            'isCaptain' => (bool) $row['is_captain'],
            'when' => self::relativeDay(new DateTimeImmutable($row['activity_date']), $today),
            'discipline' => Translator::get('discipline.' . $row['discipline']),
            'style' => self::DISCIPLINE_STYLES[$row['discipline']] ?? 'other',
            'note' => $row['note'],
            // Ścieżka względna z bazy (uploads/activities/…) – w widoku poprzedzona „/”.
            'photo' => $row['photo_path'],
            'metrics' => self::metrics($row),
            'routeKm' => (float) $row['route_km'],
            'status' => $row['status'],
        ];
    }

    /** „Dziś” / „Wczoraj” / „N dni temu” – wspólne dla kart feedu i kafelka serii. */
    public static function relativeDay(DateTimeImmutable $date, DateTimeImmutable $today): string
    {
        $daysAgo = (int) $date->diff($today)->format('%r%a');

        return match (true) {
            $daysAgo <= 0 => Translator::get('activity.today'),
            $daysAgo === 1 => Translator::get('activity.yesterday'),
            default => Translator::get('activity.days_ago', ['days' => $daysAgo]),
        };
    }

    /**
     * Trzy metryki karty. Dyscypliny „na km”: dystans, czas i tempo/prędkość;
     * siłownia (liczona w minutach): czas, średnie tętno i przeliczone km.
     *
     * @return list<array{label: string, value: string, unit: string}>
     */
    private static function metrics(array $row): array
    {
        $minutes = (int) $row['duration_min'];
        $duration = ['label' => 'metric.duration', 'value' => self::duration($minutes), 'unit' => ''];

        if ($row['unit'] !== 'km' || $row['distance_km'] === null) {
            return [
                $duration,
                [
                    'label' => 'metric.heart_rate',
                    'value' => $row['avg_heart_rate'] === null ? '—' : (string) $row['avg_heart_rate'],
                    'unit' => $row['avg_heart_rate'] === null ? '' : 'bpm',
                ],
                ['label' => 'metric.route_equivalent', 'value' => Translator::number((float) $row['route_km']), 'unit' => 'km'],
            ];
        }

        $km = (float) $row['distance_km'];
        $third = match ($row['discipline']) {
            // Tempo biegowe w min/km, pływackie na 100 m; reszta (rower) jako prędkość.
            'Bieg' => ['label' => 'metric.pace', 'value' => self::pace($minutes / $km), 'unit' => '/km'],
            'Pływanie' => ['label' => 'metric.pace', 'value' => self::pace($minutes / ($km * 10)), 'unit' => '/100m'],
            default => ['label' => 'metric.speed', 'value' => Translator::number($km / ($minutes / 60)), 'unit' => 'km/h'],
        };

        return [
            ['label' => 'metric.distance', 'value' => Translator::number($km, 2), 'unit' => 'km'],
            $duration,
            $third,
        ];
    }

    // 89 → „1h 29m”, 52 → „52m”
    private static function duration(int $minutes): string
    {
        return $minutes >= 60 ? sprintf('%dh %02dm', intdiv($minutes, 60), $minutes % 60) : $minutes . 'm';
    }

    // 4.8 min → „4:48”
    private static function pace(float $minutes): string
    {
        $seconds = (int) round($minutes * 60);

        return sprintf('%d:%02d', intdiv($seconds, 60), $seconds % 60);
    }
}
