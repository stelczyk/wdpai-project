<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\ExpeditionRepository;
use App\Translator;
use DateTimeImmutable;

/** Ranking wyprawy: cała wyprawa, bieżący etap, ten tydzień albo ostatnie 30 dni. */
final class RankingService
{
    public const RANGE_ALL = 'all';
    public const RANGES = [self::RANGE_ALL, 'stage', 'week', 'month'];
    private const MONTH_DAYS = 30;

    public function __construct(private readonly ExpeditionRepository $expeditions = new ExpeditionRepository())
    {
    }

    /**
     * @param array $expedition wynik ExpeditionService::dashboard() – daty, etap i trasa wyprawy
     * @return array{range: string, rows: list<array>, totalKm: float, leader: ?array, averageKm: float, me: ?array}
     */
    public function table(array $expedition, User $viewer, string $range, ?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today');
        [$fromDate, $fromRouteKm] = $this->window($expedition, $range, $today);
        $rows = $this->expeditions->findRanking($expedition['id'], $fromDate, $fromRouteKm);

        $totalKm = array_sum(array_column($rows, 'km'));
        $rows = array_map(static fn (array $row): array => $row + [
            'initials' => User::initialsOf($row['name']),
            'isMe' => $row['id'] === $viewer->id,
            'share' => $totalKm > 0 ? $row['km'] / $totalKm * 100 : 0.0,
            'disciplineLabels' => array_map(static fn (string $name): array => [
                'label' => Translator::get('discipline.' . $name),
                'style' => ActivityFeedService::DISCIPLINE_STYLES[$name] ?? 'other',
            ], $row['disciplines']),
        ], $rows);

        return [
            'range' => $range,
            'rows' => $rows,
            'totalKm' => $totalKm,
            'leader' => $totalKm > 0 ? $rows[0] : null,
            'averageKm' => $rows === [] ? 0.0 : $totalKm / count($rows),
            'me' => self::balance($rows),
        ];
    }

    /** Ta sama tabela jako JSON dla ranking.js – tylko pola potrzebne do odświeżenia widoku, surowe liczby. */
    public static function toJson(array $table): array
    {
        return [
            'range' => $table['range'],
            'rows' => array_map(static fn (array $row): array => [
                'position' => $row['position'],
                'name' => $row['name'],
                'initials' => $row['initials'],
                'isMe' => $row['isMe'],
                'isCaptain' => $row['isCaptain'],
                'km' => $row['km'],
                'share' => $row['share'],
                'workouts' => $row['workouts'],
                'disciplines' => $row['disciplineLabels'],
            ], $table['rows']),
            'leader' => $table['leader'] === null ? null : ['name' => $table['leader']['name'], 'km' => $table['leader']['km']],
            'averageKm' => $table['averageKm'],
            'me' => $table['me'] === null ? null : [
                'position' => $table['me']['position'],
                'leadKm' => $table['me']['leadKm'],
                'behindKm' => $table['me']['behindKm'],
            ],
        ];
    }

    /**
     * Od kiedy liczymy: [data od, km trasy od]. Etap nie ma daty – zaczyna się, gdy drużyna
     * przekroczyła km jego pierwszego punktu (po dotarciu do mety: ostatni odcinek).
     */
    private function window(array $expedition, string $range, DateTimeImmutable $today): array
    {
        $start = $expedition['startDate']->format('Y-m-d');

        return match ($range) {
            'week' => [$today->modify('monday this week')->format('Y-m-d'), 0.0],
            'month' => [$today->modify('-' . (self::MONTH_DAYS - 1) . ' days')->format('Y-m-d'), 0.0],
            'stage' => [$start, $expedition['stage']['fromKm'] ?? ($expedition['route'][count($expedition['route']) - 2]['km'] ?? 0.0)],
            default => [$start, 0.0],
        };
    }

    /** Mój wiersz z przewagą nad następną osobą albo stratą do osoby wyżej. Null dla nie-członka. */
    private static function balance(array $rows): ?array
    {
        foreach ($rows as $row) {
            if (!$row['isMe']) {
                continue;
            }
            return $row + ExpeditionService::standing($row['km'], array_column($rows, 'km'));
        }

        return null;
    }
}
