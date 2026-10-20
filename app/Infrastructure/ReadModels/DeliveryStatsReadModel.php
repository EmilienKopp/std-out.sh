<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Domain\Presentation\ValueObjects\DeliveryStats;
use App\Models\Views\PresentationDeliveryStatsView;
use App\Models\Views\RehearsalHistoryView;
use App\Models\Views\SessionAnalyticsView;

class DeliveryStatsReadModel
{
    /**
     * Measured delivery history for one deck: rehearsal aggregates come from
     * the presentation_delivery_stats view; session lengths, per-slide
     * timings and the words-per-minute pairing need timestamp/JSON
     * arithmetic, which is finished here so the view SQL stays portable.
     */
    public function forPresentation(int $presentationId): DeliveryStats
    {
        return $this->aggregate(['presentation_id' => $presentationId]);
    }

    /**
     * The same numbers across every deck the team rehearsed or presented —
     * the dashboard's "know yourself" read on how the presenter delivers.
     */
    public function forTeam(int $teamId): DeliveryStats
    {
        return $this->aggregate(['team_id' => $teamId]);
    }

    /** @param array<string, int> $where */
    private function aggregate(array $where): DeliveryStats
    {
        $stats = PresentationDeliveryStatsView::query()->where($where)->get();

        $rehearsalCount = (int) $stats->sum('rehearsal_count');
        $sessionCount = (int) $stats->sum('session_count');

        if ($rehearsalCount + $sessionCount === 0) {
            return new DeliveryStats;
        }

        $sessions = SessionAnalyticsView::query()
            ->where($where)
            ->whereNotNull('ended_at')
            ->get()
            ->map(fn (SessionAnalyticsView $session): array => [
                'seconds' => (int) $session->started_at->diffInSeconds($session->ended_at, true),
                'wordCount' => $session->word_count,
            ])
            ->all();

        $rehearsals = RehearsalHistoryView::query()
            ->where($where)
            ->get()
            ->map(fn (RehearsalHistoryView $run): array => [
                'slideTimings' => $run->slideTimings(),
                'wordCount' => $run->word_count,
                'durationSeconds' => (int) $run->duration_seconds,
            ])
            ->all();

        return DeliveryStats::fromMeasuredRuns(
            rehearsalCount: $rehearsalCount,
            sessionCount: $sessionCount,
            sumRehearsedSeconds: (int) $stats->sum('total_rehearsal_seconds'),
            sessions: $sessions,
            rehearsals: $rehearsals,
        );
    }
}
