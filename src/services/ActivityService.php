<?php

declare(strict_types=1);

namespace App\Service;

use App\FormInput;
use App\Model\User;
use App\Repository\ActivityRepository;
use App\Repository\ExpeditionRepository;
use App\Translator;
use DateTimeImmutable;

/**
 * Dodawanie aktywności: wybór trwającej wyprawy, walidacja formularza i przeliczenie na km trasy.
 * Zapisana aktywność czeka na akceptację kapitana (status PENDING z bazy).
 */
final class ActivityService
{
    // Limity zgodne z CHECK-ami i typami w 01-schema.sql.
    public const DISTANCE_MAX = 300.0;
    public const DURATION_MAX = 1440;        // minuty – cała doba
    public const HEART_RATE_MIN = 30;
    public const HEART_RATE_MAX = 250;
    public const NOTE_MAX_LENGTH = 500;
    private const ROUTE_KM_MAX = 99999.99;   // NUMERIC(7,2)

    public function __construct(
        private readonly ExpeditionRepository $expeditions = new ExpeditionRepository(),
        private readonly ActivityRepository $activities = new ActivityRepository(),
    ) {
    }

    /**
     * Wyprawy, do których można dziś dodać trening (członek + wyprawa trwa), i wybrana z nich:
     * żądana (?expedition / pole formularza), w przeciwnym razie aktywna z headera, a na końcu pierwsza.
     *
     * @return array{expeditions: list<array>, expedition: ?array}
     */
    public function choose(User $user, ?int $requestedId, ?int $activeId, DateTimeImmutable $today): array
    {
        $today = $today->format('Y-m-d');
        $open = [];
        foreach ($this->expeditions->findForMember($user->id) as $row) {
            if ($row['start_date'] <= $today && $row['end_date'] >= $today) {
                $open[(int) $row['id']] = [
                    'id' => (int) $row['id'],
                    'name' => $row['name'],
                    'startDate' => $row['start_date'],
                    'endDate' => $row['end_date'],
                ];
            }
        }

        $selected = $open[$requestedId ?? 0] ?? $open[$activeId ?? 0] ?? (reset($open) ?: null);
        if ($selected !== null) {
            $selected['disciplines'] = array_map(static fn (array $d): array => $d + [
                'label' => Translator::get('discipline.' . $d['name']),
                'style' => ActivityFeedService::DISCIPLINE_STYLES[$d['name']] ?? 'other',
            ], $this->expeditions->findDisciplineFactors($selected['id']));
        }

        return ['expeditions' => array_values($open), 'expedition' => $selected];
    }

    /** Pusty formularz: pierwsza dyscyplina wyprawy i dzisiejsza data. */
    public function emptyForm(array $expedition, DateTimeImmutable $today): array
    {
        return [
            'discipline' => $expedition['disciplines'][0]['id'] ?? 0,
            'distance' => '',
            'hours' => '',
            'minutes' => '',
            'date' => $today->format('Y-m-d'),
            'heartRate' => '',
            'note' => '',
        ];
    }

    /**
     * Sprawdza dane z $_POST dla wybranej wyprawy. Zwraca błędy pól, dane do ponownego wyświetlenia
     * formularza oraz – gdy brak błędów – gotowy wiersz do zapisu (z przeliczonymi km trasy).
     *
     * @return array{errors: array<string, string>, form: array, activity: ?array}
     */
    public function validate(User $user, array $expedition, array $input, DateTimeImmutable $today): array
    {
        $errors = [];
        $form = [
            'discipline' => (int) ($input['discipline'] ?? 0),
            'distance' => trim((string) ($input['distance'] ?? '')),
            'hours' => trim((string) ($input['hours'] ?? '')),
            'minutes' => trim((string) ($input['minutes'] ?? '')),
            'date' => trim((string) ($input['date'] ?? '')),
            'heartRate' => trim((string) ($input['heart_rate'] ?? '')),
            'note' => trim((string) ($input['note'] ?? '')),
        ];

        $discipline = array_values(array_filter(
            $expedition['disciplines'],
            static fn (array $d): bool => $d['id'] === $form['discipline'],
        ))[0] ?? null;
        if ($discipline === null) {
            $errors['discipline'] = Translator::get('activity.discipline_invalid');
        }

        // Siłownia liczy się z minut – dystans nie jest wtedy potrzebny ani zapisywany.
        $needsDistance = $discipline === null || $discipline['unit'] === 'km';
        $distance = FormInput::number($form['distance']);
        if ($needsDistance && ($distance === null || $distance <= 0 || $distance > self::DISTANCE_MAX)) {
            $errors['distance'] = Translator::get('activity.distance_range', ['max' => (int) self::DISTANCE_MAX]);
        }

        $hours = $form['hours'] === '' ? 0 : filter_var($form['hours'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 24]]);
        $minutes = $form['minutes'] === '' ? 0 : filter_var($form['minutes'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 59]]);
        $duration = $hours === false || $minutes === false ? 0 : $hours * 60 + $minutes;
        if ($duration < 1 || $duration > self::DURATION_MAX) {
            $errors['duration'] = Translator::get('activity.duration_range');
        }

        $date = FormInput::date($form['date']);
        if ($date === null) {
            $errors['date'] = Translator::get('validation.date');
        } elseif ($date > $today) {
            $errors['date'] = Translator::get('activity.date_future');
        } elseif ($form['date'] < $expedition['startDate']) {
            $errors['date'] = Translator::get('activity.date_before_start', ['date' => (new DateTimeImmutable($expedition['startDate']))->format('d.m.Y')]);
        }

        $heartRate = null;
        if ($form['heartRate'] !== '') {
            $heartRate = filter_var($form['heartRate'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => self::HEART_RATE_MIN, 'max_range' => self::HEART_RATE_MAX],
            ]);
            if ($heartRate === false) {
                $errors['heart_rate'] = Translator::get('activity.heart_rate_range', ['min' => self::HEART_RATE_MIN, 'max' => self::HEART_RATE_MAX]);
            }
        }

        if (mb_strlen($form['note']) > self::NOTE_MAX_LENGTH) {
            $errors['note'] = Translator::get('validation.max_length', ['max' => self::NOTE_MAX_LENGTH]);
        }

        if ($errors !== []) {
            return ['errors' => $errors, 'form' => $form, 'activity' => null];
        }

        $routeKm = self::routeKm($discipline, $distance, $duration);
        if ($routeKm > self::ROUTE_KM_MAX) {
            return ['errors' => ['distance' => Translator::get('activity.route_too_long')], 'form' => $form, 'activity' => null];
        }

        return ['errors' => [], 'form' => $form, 'activity' => [
            'expeditionId' => $expedition['id'],
            'userId' => $user->id,
            'disciplineId' => $discipline['id'],
            'distanceKm' => $needsDistance ? $distance : null,
            'durationMin' => $duration,
            'date' => $form['date'],
            'heartRate' => $heartRate,
            'note' => $form['note'] === '' ? null : $form['note'],
            'routeKm' => $routeKm,
        ]];
    }

    public function save(array $activity): int
    {
        return $this->activities->insert($activity);
    }

    /** Km trasy = dystans (albo minuty dla siłowni) × przelicznik ustawiony przez kapitana. */
    public static function routeKm(array $discipline, ?float $distanceKm, int $durationMin): float
    {
        $amount = $discipline['unit'] === 'min' ? $durationMin : (float) $distanceKm;

        return round($amount * $discipline['factor'], 2);
    }
}
