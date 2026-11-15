<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\ExpeditionRepository;
use App\Translator;
use DateTimeImmutable;

/** Dane ekranu „Moje wyprawy”: statusy z dat, dzień wyprawy, aktualny etap, statystyki użytkownika. */
final class ExpeditionService
{
    public const IN_PROGRESS = 'in_progress';
    public const PLANNED = 'planned';
    public const COMPLETED = 'completed';

    // Na kafelku wkładu pokazujemy dwie główne dyscypliny, resztę łączymy w „inne”.
    private const MAIN_DISCIPLINES = 2;

    public function __construct(private readonly ExpeditionRepository $expeditions = new ExpeditionRepository())
    {
    }

    /** @return array{sections: array<string, list<array>>, stats: array} */
    public function overviewFor(User $user, ?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today');
        $rows = $this->expeditions->findForMember($user->id);
        $checkpoints = $this->expeditions->findCheckpoints(array_column($rows, 'id'));

        $sections = [self::IN_PROGRESS => [], self::PLANNED => [], self::COMPLETED => []];
        foreach ($rows as $row) {
            $expedition = $this->describe($row, $checkpoints[(int) $row['id']] ?? [], $user, $today);
            $sections[$expedition['status']][] = $expedition;
        }

        // Archiwum od najświeższej wyprawy.
        $sections[self::COMPLETED] = array_reverse($sections[self::COMPLETED]);

        return [
            'sections' => $sections,
            'stats' => $this->stats($sections, $user, (int) $today->format('Y')),
        ];
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
