<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\ActivityRepository;
use DateTimeImmutable;

/** Panel kapitana: kolejka aktywności do weryfikacji i jej wpływ na postęp wyprawy. */
final class CaptainService
{
    // Zakładki z adresu (?status=…) → status w bazie. Pierwsza jest domyślna.
    public const TABS = ['pending' => 'PENDING', 'approved' => 'APPROVED', 'rejected' => 'REJECTED'];
    public const QUEUE_LIMIT = 50;

    public function __construct(
        private readonly ActivityRepository $activities = new ActivityRepository(),
        private readonly ActivityFeedService $feed = new ActivityFeedService(),
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

        $counts = [];
        $byStatus = $this->activities->countByStatus($expedition['id']);
        foreach (self::TABS as $name => $status) {
            $counts[$name] = $byStatus[$status] ?? ['count' => 0, 'km' => 0.0];
        }

        return $this->feed->cards($rows, $captain, $today) + [
            'tab' => $tab,
            'counts' => $counts,
            'impact' => self::impact($expedition, $counts['pending']['km']),
        ];
    }

    /** Ile dałoby zatwierdzenie całej kolejki: postęp trasy po zatwierdzeniu i km do najbliższego punktu. */
    private static function impact(array $expedition, float $pendingKm): array
    {
        $total = $expedition['totalKm'];
        $afterKm = min($total, $expedition['approvedKm'] + $pendingKm);
        $stage = $expedition['stage'];

        return [
            'pendingKm' => $pendingKm,
            'nowPercent' => $expedition['progressPercent'],
            'afterPercent' => $total > 0 ? $afterKm / $total * 100 : 0.0,
            'nextName' => $stage['to'] ?? null,
            // Ujemne = kolejka przenosi drużynę za ten punkt.
            'nextLeftKm' => $stage === null ? null : $stage['toKm'] - $afterKm,
        ];
    }
}
