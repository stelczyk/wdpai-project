<?php

declare(strict_types=1);

namespace App\Service;

use App\Database;
use App\Model\User;
use App\Repository\ExpeditionRepository;
use App\Translator;
use DateTimeImmutable;
use RuntimeException;
use Throwable;

/** Tworzenie wyprawy przez kapitana: walidacja formularza i zapis w jednej transakcji. */
final class ExpeditionCreationService
{
    // Limity zgodne z typami i CHECK-ami w bazie (01-schema.sql).
    private const NAME_MAX_LENGTH = 100;
    private const MEMBERS_MIN = 2;
    private const MEMBERS_MAX = 100;
    private const KM_MAX = 99999.99;       // NUMERIC(7,2)
    private const FACTOR_MAX = 999.99;     // NUMERIC(5,2)
    private const JOIN_CODE_ATTEMPTS = 5;

    // Podpowiedzi przeliczników w formularzu (km trasy za 1 km / 1 minutę) – kapitan może je zmienić.
    private const DEFAULT_FACTORS = ['Bieg' => 1.0, 'Rower' => 0.35, 'Pływanie' => 4.0, 'Siłownia' => 0.1];

    public function __construct(private readonly ExpeditionRepository $expeditions = new ExpeditionRepository())
    {
    }

    /** Dyscypliny ze słownika z domyślnym przelicznikiem – do pustego formularza. */
    public function disciplines(): array
    {
        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'label' => Translator::get('discipline.' . $row['name']),
            'unit' => $row['unit'],
            'defaultFactor' => self::DEFAULT_FACTORS[$row['name']] ?? 1.0,
        ], $this->expeditions->findAllDisciplines());
    }

    /** Wartości startowe formularza: start jutro, 30 dni, start i meta trasy, wszystkie dyscypliny włączone. */
    public function emptyForm(DateTimeImmutable $today): array
    {
        $disciplines = $this->disciplines();

        return [
            'name' => '',
            'startDate' => $today->modify('+1 day')->format('Y-m-d'),
            'endDate' => $today->modify('+30 days')->format('Y-m-d'),
            'maxMembers' => '8',
            'checkpoints' => [['name' => '', 'km' => '0'], ['name' => '', 'km' => '']],
            'enabled' => array_column($disciplines, 'id'),
            'factors' => array_column($disciplines, 'defaultFactor', 'id'),
        ];
    }

    /**
     * Sprawdza dane z formularza ($_POST). Zwraca błędy pól oraz dane w postaci do ponownego
     * wyświetlenia formularza i do zapisu.
     *
     * @return array{errors: array<string, string>, form: array}
     */
    public function validate(array $input, DateTimeImmutable $today): array
    {
        $errors = [];
        $form = [
            'name' => trim((string) ($input['name'] ?? '')),
            'startDate' => (string) ($input['start_date'] ?? ''),
            'endDate' => (string) ($input['end_date'] ?? ''),
            'maxMembers' => trim((string) ($input['max_members'] ?? '')),
            'checkpoints' => $this->readCheckpoints($input),
            'enabled' => array_map('intval', (array) ($input['discipline'] ?? [])),
            'factors' => array_map(static fn ($value): string => trim((string) $value), (array) ($input['factor'] ?? [])),
        ];

        if ($form['name'] === '') {
            $errors['name'] = Translator::get('validation.required');
        } elseif (mb_strlen($form['name']) > self::NAME_MAX_LENGTH) {
            $errors['name'] = Translator::get('validation.max_length', ['max' => self::NAME_MAX_LENGTH]);
        }

        $start = $this->parseDate($form['startDate']);
        $end = $this->parseDate($form['endDate']);
        if ($start === null) {
            $errors['start_date'] = Translator::get('validation.date');
        } elseif ($start < $today) {
            $errors['start_date'] = Translator::get('create.start_in_past');
        }
        if ($end === null) {
            $errors['end_date'] = Translator::get('validation.date');
        } elseif ($start !== null && $end < $start) {
            $errors['end_date'] = Translator::get('create.end_before_start');
        }

        $members = filter_var($form['maxMembers'], FILTER_VALIDATE_INT, [
            'options' => ['min_range' => self::MEMBERS_MIN, 'max_range' => self::MEMBERS_MAX],
        ]);
        if ($members === false) {
            $errors['max_members'] = Translator::get('create.members_range', ['min' => self::MEMBERS_MIN, 'max' => self::MEMBERS_MAX]);
        }

        $checkpointError = $this->checkpointsError($form['checkpoints']);
        if ($checkpointError !== null) {
            $errors['checkpoints'] = $checkpointError;
        }

        $disciplineError = $this->disciplinesError($form['enabled'], $form['factors']);
        if ($disciplineError !== null) {
            $errors['disciplines'] = $disciplineError;
        }

        return ['errors' => $errors, 'form' => $form];
    }

    /**
     * Zapisuje wyprawę z trasą, dyscyplinami i kapitanem jako pierwszym członkiem.
     * Wszystko w jednej transakcji – błąd w dowolnym kroku cofa całość.
     *
     * @return array{id: int, joinCode: string}
     */
    public function create(User $captain, array $form): array
    {
        $joinCode = $this->uniqueJoinCode();
        $db = Database::connect();
        $db->beginTransaction();

        try {
            $id = $this->expeditions->insertExpedition(
                $form['name'], $captain->id, $form['startDate'], $form['endDate'], (int) $form['maxMembers'], $joinCode,
            );

            foreach ($form['checkpoints'] as $checkpoint) {
                $this->expeditions->insertCheckpoint($id, $checkpoint['name'], self::toNumber($checkpoint['km']));
            }

            foreach ($form['enabled'] as $disciplineId) {
                $this->expeditions->insertDiscipline($id, $disciplineId, self::toNumber($form['factors'][$disciplineId]));
            }

            $this->expeditions->insertMember($id, $captain->id);
            $db->commit();
        } catch (Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }

        return ['id' => $id, 'joinCode' => $joinCode];
    }

    // Wiersze trasy z dwóch równoległych tablic pól; całkiem puste wiersze pośrednie pomijamy.
    private function readCheckpoints(array $input): array
    {
        $names = array_values((array) ($input['checkpoint_name'] ?? []));
        $kms = array_values((array) ($input['checkpoint_km'] ?? []));
        $checkpoints = [];

        foreach ($names as $index => $name) {
            $checkpoint = ['name' => trim((string) $name), 'km' => trim((string) ($kms[$index] ?? ''))];
            if ($checkpoint['name'] !== '' || $checkpoint['km'] !== '') {
                $checkpoints[] = $checkpoint;
            }
        }

        return $checkpoints;
    }

    // Start (0 km) i meta obowiązkowe, nazwy wymagane, km rosnące i w zakresie kolumny.
    private function checkpointsError(array $checkpoints): ?string
    {
        if (count($checkpoints) < 2) {
            return Translator::get('create.checkpoints_min');
        }

        $previousKm = null;
        foreach ($checkpoints as $index => $checkpoint) {
            $km = self::toNumber($checkpoint['km']);

            if ($checkpoint['name'] === '' || mb_strlen($checkpoint['name']) > self::NAME_MAX_LENGTH) {
                return Translator::get('create.checkpoint_name');
            }
            if ($km === null || $km < 0 || $km > self::KM_MAX) {
                return Translator::get('create.checkpoint_km');
            }
            if ($index === 0 && $km !== 0.0) {
                return Translator::get('create.checkpoint_start');
            }
            if ($previousKm !== null && $km <= $previousKm) {
                return Translator::get('create.checkpoint_order');
            }
            $previousKm = $km;
        }

        return null;
    }

    private function disciplinesError(array $enabled, array $factors): ?string
    {
        $knownIds = array_column($this->expeditions->findAllDisciplines(), 'id');
        $enabled = array_intersect($enabled, array_map('intval', $knownIds));

        if ($enabled === []) {
            return Translator::get('create.disciplines_min');
        }

        foreach ($enabled as $id) {
            $factor = self::toNumber($factors[$id] ?? '');
            if ($factor === null || $factor <= 0 || $factor > self::FACTOR_MAX) {
                return Translator::get('create.factor_invalid');
            }
        }

        return null;
    }

    // 6 cyfr z kryptograficznego generatora; unikalność sprawdzamy przed zapisem
    // (a unikalny indeks w bazie i tak odrzuci duplikat, gdyby ktoś zajął kod w międzyczasie).
    private function uniqueJoinCode(): string
    {
        for ($attempt = 0; $attempt < self::JOIN_CODE_ATTEMPTS; $attempt++) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            if (!$this->expeditions->joinCodeExists($code)) {
                return $code;
            }
        }

        throw new RuntimeException('Could not generate a unique join code');
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $date : null;
    }

    // Akceptujemy przecinek dziesiętny („0,35”), bo tak piszą użytkownicy w PL.
    private static function toNumber(string $value): ?float
    {
        $number = filter_var(str_replace(',', '.', $value), FILTER_VALIDATE_FLOAT);

        return $number === false ? null : $number;
    }
}
