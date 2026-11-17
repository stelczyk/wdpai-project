<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\ActivityRepository;
use App\Repository\ExpeditionRepository;
use App\Translator;
use DateTimeImmutable;

/** Dane ekranu „Moje wyprawy”: statusy z dat, dzień wyprawy, aktualny etap, statystyki użytkownika. */
final class ExpeditionService
{
    public const IN_PROGRESS = 'in_progress';
    public const PLANNED = 'planned';
    public const COMPLETED = 'completed';

    // Dozwolone wartości filtrów z adresu (?status=, ?sort=) – wszystko inne kontroler odrzuca (400).
    public const STATUS_ALL = 'all';
    public const STATUSES = [self::STATUS_ALL, self::IN_PROGRESS, self::PLANNED, self::COMPLETED];
    public const SORT_START = 'start';
    public const SORTS = [self::SORT_START, 'progress', 'name'];

    // Na kafelku wkładu pokazujemy dwie główne dyscypliny, resztę łączymy w „inne”.
    private const MAIN_DISCIPLINES = 2;

    public function __construct(
        private readonly ExpeditionRepository $expeditions = new ExpeditionRepository(),
        private readonly ActivityRepository $activities = new ActivityRepository(),
    ) {
    }

    /**
     * Kafelki statystyk liczone są zawsze ze wszystkich wypraw użytkownika;
     * wyszukiwanie, zakładka statusu i sortowanie zmieniają tylko listy.
     *
     * @return array{sections: array<string, list<array>>, counts: array<string, int>, stats: array}
     */
    public function overviewFor(
        User $user,
        string $search = '',
        string $status = self::STATUS_ALL,
        string $sort = self::SORT_START,
        ?DateTimeImmutable $today = null,
    ): array {
        $today ??= new DateTimeImmutable('today');
        $all = $this->group($this->expeditions->findForMember($user->id), $user, $today);
        $found = $search === '' ? $all : $this->group($this->expeditions->findForMember($user->id, $search), $user, $today);

        $counts = array_map('count', $found);
        $counts[self::STATUS_ALL] = array_sum($counts);

        $sections = [];
        foreach ($found as $sectionStatus => $expeditions) {
            $visible = $status === self::STATUS_ALL || $status === $sectionStatus;
            $sections[$sectionStatus] = $visible ? $this->sort($expeditions, $sort) : [];
        }

        return [
            'sections' => $sections,
            'counts' => $counts,
            'stats' => $this->stats($all, $user, (int) $today->format('Y')),
        ];
    }

    // Wyniki dołączania kodem PIN – kontroler zamienia je na status HTTP i komunikat.
    public const JOINED = 'joined';
    public const JOIN_NOT_FOUND = 'not_found';
    public const JOIN_ALREADY_MEMBER = 'already_member';
    public const JOIN_FINISHED = 'finished';
    public const JOIN_FULL = 'full';

    /** @return array{result: string, expedition: ?array} */
    public function join(User $user, string $code, ?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today');
        $expedition = $this->expeditions->findByJoinCode($code);

        $result = match (true) {
            $expedition === null => self::JOIN_NOT_FOUND,
            $this->expeditions->isMember($expedition['id'], $user->id) => self::JOIN_ALREADY_MEMBER,
            new DateTimeImmutable($expedition['endDate']) < $today => self::JOIN_FINISHED,
            !$this->expeditions->addMemberIfSpace($expedition['id'], $user->id) => self::JOIN_FULL,
            default => self::JOINED,
        };

        return ['result' => $result, 'expedition' => $expedition];
    }

    // Znaczniki uczestników bliżej niż tyle % trasy trafiają do kolejnego rzędu, żeby się nie nakładały.
    private const MARKER_GAP_PERCENT = 10.0;
    // Podpisy checkpointów („660 km • Zaplanowany”) są szersze, więc potrzebują większego odstępu.
    private const CHECKPOINT_GAP_PERCENT = 15.0;

    /**
     * Dashboard wyprawy: dane z describe() + pasek trasy (checkpointy i uczestnicy w % długości),
     * pozostały dystans, średnie tempo drużyny i szacowana data dotarcia do mety.
     * Null, gdy wyprawa nie istnieje (kontroler sprawdził już członkostwo).
     */
    public function dashboard(User $user, int $expeditionId, ?DateTimeImmutable $today = null): ?array
    {
        $today ??= new DateTimeImmutable('today');
        $row = $this->expeditions->findOneForUser($expeditionId, $user->id);
        if ($row === null) {
            return null;
        }

        $checkpoints = $this->expeditions->findCheckpoints([$expeditionId])[$expeditionId] ?? [];
        $expedition = $this->describe($row, $checkpoints, $user, $today);
        $members = $this->expeditions->findMembersProgress($expeditionId);
        $expedition['memberCount'] = count($members);

        $totalKm = $expedition['totalKm'];
        $approvedKm = $expedition['approvedKm'];
        $percent = static fn (float $km): float => $totalKm > 0 ? min(100.0, $km / $totalKm * 100) : 0.0;
        $targetIndex = $expedition['stage'] === null ? null : $expedition['stage']['number'];
        $lastIndex = count($checkpoints) - 1;

        $route = [];
        foreach ($checkpoints as $index => $checkpoint) {
            $route[] = $checkpoint + [
                'percent' => $percent($checkpoint['km']),
                'state' => match (true) {
                    $index === 0 => 'start',
                    $index === $targetIndex => 'target',
                    $checkpoint['km'] <= $approvedKm => 'passed',
                    $index === $lastIndex => 'finish',
                    default => 'planned',
                },
                'isFinish' => $index === $lastIndex,
            ];
        }

        // Tempo liczone od startu do dziś (albo do końca wyprawy, gdy już się skończyła).
        $daysElapsed = match ($expedition['status']) {
            self::PLANNED => 0,
            self::COMPLETED => $expedition['durationDays'],
            default => $expedition['day'],
        };
        $pace = $daysElapsed > 0 ? $approvedKm / $daysElapsed : null;
        $remainingKm = max(0.0, $totalKm - $approvedKm);
        $eta = $pace > 0 && $remainingKm > 0 && $expedition['status'] === self::IN_PROGRESS
            ? $today->modify('+' . (int) ceil($remainingKm / $pace) . ' days')
            : null;

        return $expedition + [
            'route' => self::assignLanes($route, self::CHECKPOINT_GAP_PERCENT),
            'members' => $this->placeMarkers($members, $user, $percent),
            'remainingKm' => $remainingKm,
            'paceKmPerDay' => $pace,
            'eta' => $eta,
            'myStats' => self::memberStats($this->activities->findDailyTotals($expeditionId, $user->id), $members, $user->id, $today),
        ];
    }

    /**
     * Kafelki uczestnika: km w tym tygodniu (od poniedziałku) vs poprzedni, miejsce w drużynie
     * z przewagą/stratą i seria kolejnych dni z aktywnością. Null dla nie-członka (admin podgląda).
     *
     * @param list<array{date: string, km: float}> $days dni z aktywnością, od najnowszego
     * @param list<array{id: int, name: string, km: float}> $members od największego wkładu
     */
    public static function memberStats(array $days, array $members, int $userId, DateTimeImmutable $today): ?array
    {
        $myKm = null;
        foreach ($members as $member) {
            if ($member['id'] === $userId) {
                $myKm = $member['km'];
            }
        }
        if ($myKm === null) {
            return null;
        }

        // Miejsce = 1 + liczba osób z większym dystansem (remis = to samo miejsce).
        $above = array_filter($members, static fn (array $m): bool => $m['km'] > $myKm);
        $below = array_filter($members, static fn (array $m): bool => $m['km'] < $myKm);
        $position = count($above) + 1;

        $weekStart = $today->modify('monday this week');
        $previousStart = $weekStart->modify('-7 days');
        $weekKm = $previousKm = 0.0;
        foreach ($days as $day) {
            $date = new DateTimeImmutable($day['date']);
            if ($date >= $weekStart && $date <= $today) {
                $weekKm += $day['km'];
            } elseif ($date >= $previousStart && $date < $weekStart) {
                $previousKm += $day['km'];
            }
        }

        // Seria trwa, jeśli ostatni wpis jest z dziś albo wczoraj; liczymy kolejne dni wstecz.
        $pastDays = array_values(array_filter($days, static fn (array $d): bool => new DateTimeImmutable($d['date']) <= $today));
        $lastDate = $pastDays === [] ? null : new DateTimeImmutable($pastDays[0]['date']);
        $streak = 0;
        if ($lastDate !== null && $lastDate >= $today->modify('-1 day')) {
            $expected = $lastDate;
            foreach ($pastDays as $day) {
                if (new DateTimeImmutable($day['date']) != $expected) {
                    break;
                }
                $streak++;
                $expected = $expected->modify('-1 day');
            }
        }

        return [
            'weekKm' => $weekKm,
            'weekChangePercent' => $previousKm > 0 ? ($weekKm - $previousKm) / $previousKm * 100 : null,
            'position' => $position,
            'memberCount' => count($members),
            // Przewaga nad następną osobą (gdy prowadzę) albo strata do osoby tuż nade mną.
            'leadKm' => $position === 1 && $below !== [] ? $myKm - max(array_column($below, 'km')) : null,
            'behindKm' => $above === [] ? null : min(array_column($above, 'km')) - $myKm,
            'streak' => $streak,
            'lastEntry' => $lastDate === null ? null : ActivityFeedService::relativeDay($lastDate, $today),
        ];
    }

    /** Pozycja każdego uczestnika na pasku (% trasy) i rząd, w którym rysujemy jego znacznik. */
    private function placeMarkers(array $members, User $user, callable $percent): array
    {
        $members = array_map(static fn (array $member): array => $member + [
            'percent' => $percent($member['km']),
            'initials' => User::initialsOf($member['name']),
            'isMe' => $member['id'] === $user->id,
        ], $members);
        usort($members, static fn (array $a, array $b): int => $a['percent'] <=> $b['percent']);

        return self::assignLanes($members, self::MARKER_GAP_PERCENT);
    }

    /**
     * Elementy posortowane po 'percent' dostają 'lane' – zachłannie pierwszy rząd,
     * w którym poprzedni element jest dalej niż $gap % trasy (inaczej podpisy by się nakładały).
     */
    private static function assignLanes(array $items, float $gap): array
    {
        $laneEnds = [];
        foreach ($items as &$item) {
            $lane = 0;
            while (isset($laneEnds[$lane]) && $item['percent'] - $laneEnds[$lane] < $gap) {
                $lane++;
            }
            $laneEnds[$lane] = $item['percent'];
            $item['lane'] = $lane;
        }
        unset($item);

        return $items;
    }

    /** Wiersze z bazy → wyprawy pogrupowane w sekcje według statusu. */
    private function group(array $rows, User $user, DateTimeImmutable $today): array
    {
        $checkpoints = $this->expeditions->findCheckpoints(array_column($rows, 'id'));

        $sections = [self::IN_PROGRESS => [], self::PLANNED => [], self::COMPLETED => []];
        foreach ($rows as $row) {
            $expedition = $this->describe($row, $checkpoints[(int) $row['id']] ?? [], $user, $today);
            $sections[$expedition['status']][] = $expedition;
        }

        // Archiwum od najświeższej wyprawy.
        $sections[self::COMPLETED] = array_reverse($sections[self::COMPLETED]);

        return $sections;
    }

    // Domyślnie kolejność z bazy (data startu); poza tym postęp trasy malejąco albo nazwa A–Z.
    private function sort(array $expeditions, string $sort): array
    {
        match ($sort) {
            'progress' => usort($expeditions, static fn (array $a, array $b): int => $b['progressPercent'] <=> $a['progressPercent']),
            'name' => usort($expeditions, static fn (array $a, array $b): int => mb_strtolower($a['name']) <=> mb_strtolower($b['name'])),
            default => null,
        };

        return $expeditions;
    }

    private function describe(array $row, array $checkpoints, User $user, DateTimeImmutable $today): array
    {
        $start = new DateTimeImmutable($row['start_date']);
        $end = new DateTimeImmutable($row['end_date']);
        $totalKm = (float) $row['total_km'];
        $approvedKm = (float) $row['approved_km'];

        // Status nie jest kolumną w bazie – wynika z dat (brak rozjazdu danych).
        $status = match (true) {
            $today < $start => self::PLANNED,
            $today > $end => self::COMPLETED,
            default => self::IN_PROGRESS,
        };

        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'status' => $status,
            'isCaptain' => (int) $row['captain_id'] === $user->id,
            'captainName' => $row['captain_name'],
            'joinCode' => $row['join_code'],
            'startDate' => $start,
            'endDate' => $end,
            'durationDays' => $start->diff($end)->days + 1,
            'day' => $start->diff($today)->days + 1,
            'startsInDays' => $today->diff($start)->days,
            'totalKm' => $totalKm,
            'approvedKm' => $approvedKm,
            'progressPercent' => $totalKm > 0 ? min(100.0, $approvedKm / $totalKm * 100) : 0.0,
            'myKm' => (float) $row['my_km'],
            'myPosition' => (int) $row['my_position'],
            'memberCount' => (int) $row['member_count'],
            'maxMembers' => (int) $row['max_members'],
            'disciplines' => array_map(self::disciplineLabel(...), explode(', ', (string) $row['disciplines'])),
            'stage' => $this->currentStage($checkpoints, $approvedKm),
        ];
    }

    /**
     * Etap = odcinek między ostatnim minionym a najbliższym checkpointem.
     * Null, gdy trasa nie ma checkpointów albo drużyna dotarła do mety.
     */
    private function currentStage(array $checkpoints, float $approvedKm): ?array
    {
        foreach ($checkpoints as $index => $checkpoint) {
            if ($checkpoint['km'] > $approvedKm && $index > 0) {
                return [
                    'number' => $index,
                    'from' => $checkpoints[$index - 1]['name'],
                    'to' => $checkpoint['name'],
                    'fromKm' => $checkpoints[$index - 1]['km'],
                    'toKm' => $checkpoint['km'],
                    'kmLeft' => $checkpoint['km'] - $approvedKm,
                ];
            }
        }

        return null;
    }

    private function stats(array $sections, User $user, int $year): array
    {
        $active = $sections[self::IN_PROGRESS];
        $completed = $sections[self::COMPLETED];
        $captainActive = count(array_filter($active, static fn (array $e): bool => $e['isCaptain']));
        $reachedGoal = count(array_filter($completed, static fn (array $e): bool => $e['progressPercent'] >= 100));

        return [
            'totalCount' => array_sum(array_map('count', $sections)),
            'activeCount' => count($active),
            'activeAsCaptain' => $captainActive,
            'activeAsParticipant' => count($active) - $captainActive,
            'completedCount' => count($completed),
            'successPercent' => $completed === [] ? null : $reachedGoal / count($completed) * 100,
            'year' => $year,
            'contribution' => $this->contribution($user, $year),
        ];
    }

    /** Suma km w roku i udziały dyscyplin: dwie największe osobno, pozostałe jako „inne”. */
    private function contribution(User $user, int $year): array
    {
        $byDiscipline = $this->expeditions->findContributionByDiscipline($user->id, $year);
        $totalKm = array_sum(array_column($byDiscipline, 'km'));

        $shares = [];
        foreach (array_slice($byDiscipline, 0, self::MAIN_DISCIPLINES) as $discipline) {
            $shares[] = ['label' => self::disciplineLabel($discipline['name']), 'km' => $discipline['km']];
        }

        $otherKm = array_sum(array_column(array_slice($byDiscipline, self::MAIN_DISCIPLINES), 'km'));
        if ($otherKm > 0) {
            $shares[] = ['label' => Translator::get('expeditions.other'), 'km' => $otherKm];
        }

        foreach ($shares as &$share) {
            $share['percent'] = $share['km'] / $totalKm * 100;
        }
        unset($share);

        return ['totalKm' => $totalKm, 'shares' => $shares];
    }

    // Nazwy dyscyplin są w bazie po polsku – w interfejsie tłumaczymy je przez katalog.
    private static function disciplineLabel(string $name): string
    {
        return Translator::get('discipline.' . $name);
    }
}
