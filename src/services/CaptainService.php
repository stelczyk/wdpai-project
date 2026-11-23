<?php

declare(strict_types=1);

namespace App\Service;

use App\Database;
use App\Model\User;
use App\Repository\ActivityRepository;
use App\Repository\AuditLogRepository;
use App\Translator;
use DateTimeImmutable;

/** Panel kapitana: kolejka aktywności do weryfikacji, decyzje i ich wpływ na postęp wyprawy. */
final class CaptainService
{
    // Zakładki z adresu (?status=…) → status w bazie. Pierwsza jest domyślna.
    public const TABS = ['pending' => 'PENDING', 'approved' => 'APPROVED', 'rejected' => 'REJECTED'];
    public const QUEUE_LIMIT = 50;
    private const LOG_LIMIT = 10;
    // Decyzja z przycisku → status w bazie (i akcja w audit_log).
    public const DECISIONS = ['approve' => 'APPROVED', 'reject' => 'REJECTED'];

    // Wyniki decyzji – kontroler zamienia je na status HTTP.
    public const DECIDED = 'decided';
    public const NOT_FOUND = 'not_found';
    public const ALREADY_DECIDED = 'already_decided';

    public function __construct(
        private readonly ActivityRepository $activities = new ActivityRepository(),
        private readonly ActivityFeedService $feed = new ActivityFeedService(),
        private readonly AuditLogRepository $audit = new AuditLogRepository(),
    ) {
    }

    /**
     * @param array $expedition wynik ExpeditionService::dashboard() – postęp i bieżący etap
     * @return array{tab: string, items: list<array>, total: int, counts: array<string, array{count: int, km: float}>, impact: array}
     */
    public function queue(array $expedition, User $captain, string $tab, ?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today');
        $rows = $this->activities->findByStatus($expedition['id'], self::TABS[$tab], self::QUEUE_LIMIT);

        return $this->feed->cards($rows, $captain, $today) + ['tab' => $tab] + $this->summary($expedition);
    }

    /**
     * Liczniki zakładek i wpływ kolejki – na stronie panelu i w odpowiedzi JSON po decyzji.
     *
     * @return array{counts: array<string, array{count: int, km: float}>, impact: array}
     */
    public function summary(array $expedition): array
    {
        $counts = [];
        $byStatus = $this->activities->countByStatus($expedition['id']);
        foreach (self::TABS as $name => $status) {
            $counts[$name] = $byStatus[$status] ?? ['count' => 0, 'km' => 0.0];
        }

        return [
            'counts' => $counts,
            'impact' => self::impact($expedition, $counts['pending']['km']),
            'log' => $this->recentDecisions($expedition['id']),
        ];
    }

    /**
     * Ostatnie decyzje do panelu – gotowe teksty (te same dla strony i odpowiedzi JSON po decyzji).
     *
     * @return list<array{action: string, label: string, detail: string, by: string}>
     */
    private function recentDecisions(int $expeditionId, ?DateTimeImmutable $now = null): array
    {
        $now ??= new DateTimeImmutable();

        return array_map(static fn (array $row): array => [
            'action' => strtolower($row['action']),
            'label' => Translator::get('captain.log_' . strtolower($row['action'])),
            // Aktywność usunięta (activity_id = NULL po ON DELETE SET NULL) – zostaje sam fakt decyzji.
            'detail' => $row['discipline'] === null
                ? Translator::get('captain.log_deleted')
                : Translator::get('captain.log_detail', [
                    'discipline' => Translator::get('discipline.' . $row['discipline']),
                    'author' => $row['author_name'],
                    'km' => Translator::number((float) $row['route_km']),
                ]),
            'by' => Translator::get('captain.log_by', [
                'actor' => $row['actor_name'] ?? Translator::get('captain.log_unknown_actor'),
                'when' => self::ago(new DateTimeImmutable($row['created_at']), $now),
            ]),
        ], $this->audit->findRecent($expeditionId, self::LOG_LIMIT));
    }

    // „przed chwilą” / „12 min temu” / „3 godz. temu”, a starsze jak w feedzie („Wczoraj”, „5 dni temu”).
    private static function ago(DateTimeImmutable $at, DateTimeImmutable $now): string
    {
        $minutes = intdiv($now->getTimestamp() - $at->getTimestamp(), 60);

        return match (true) {
            $minutes < 1 => Translator::get('captain.time_now'),
            $minutes < 60 => Translator::get('captain.time_minutes', ['count' => $minutes]),
            $minutes < 24 * 60 => Translator::get('captain.time_hours', ['count' => intdiv($minutes, 60)]),
            default => ActivityFeedService::relativeDay($at->setTime(0, 0), $now->setTime(0, 0)),
        };
    }

    /**
     * Zatwierdza albo odrzuca oczekującą aktywność tej wyprawy. Zmiana statusu i wpis w audit_log
     * w jednej transakcji: albo zapisują się oba, albo żaden (np. gdy wpis do logu się nie uda).
     */
    public function decide(User $captain, int $expeditionId, int $activityId, string $decision): string
    {
        $status = self::DECISIONS[$decision];

        return Database::transaction(function () use ($captain, $expeditionId, $activityId, $status): string {
            if (!$this->activities->decide($activityId, $expeditionId, $status)) {
                // UPDATE nic nie zmienił: aktywności nie ma w tej wyprawie albo ktoś już ją rozpatrzył.
                return $this->activities->findStatus($activityId, $expeditionId) === null ? self::NOT_FOUND : self::ALREADY_DECIDED;
            }
            $this->audit->insert($expeditionId, $activityId, $captain->id, $status);

            return self::DECIDED;
        });
    }

    /**
     * Zatwierdza całą kolejkę jednej wyprawy: zmiana statusów i wpisy w audit_log w jednej transakcji –
     * błąd przy zapisie logu cofa też zatwierdzenia. Zwraca liczbę zatwierdzonych aktywności.
     */
    public function approveAll(User $captain, int $expeditionId): int
    {
        return Database::transaction(function () use ($captain, $expeditionId): int {
            $ids = $this->activities->approveAllPending($expeditionId);
            if ($ids !== []) {
                $this->audit->insertMany($expeditionId, $ids, $captain->id, self::DECISIONS['approve']);
            }

            return count($ids);
        });
    }

    /** Ile dałoby zatwierdzenie całej kolejki: postęp trasy po zatwierdzeniu i km do najbliższego punktu. */
    private static function impact(array $expedition, float $pendingKm): array
    {
        $total = $expedition['totalKm'];
        $afterKm = min($total, $expedition['approvedKm'] + $pendingKm);
        $stage = $expedition['stage'];

        return [
            'pendingKm' => $pendingKm,
            'approvedKm' => $expedition['approvedKm'],
            'nowPercent' => $expedition['progressPercent'],
            'afterPercent' => $total > 0 ? $afterKm / $total * 100 : 0.0,
            'nextName' => $stage['to'] ?? null,
            // Ujemne = kolejka przenosi drużynę za ten punkt.
            'nextLeftKm' => $stage === null ? null : $stage['toKm'] - $afterKm,
        ];
    }
}
