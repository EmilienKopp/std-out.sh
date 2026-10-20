<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Application\Projections\SessionDetail;
use App\Models\Views\RehearsalHistoryView;
use App\Models\Views\SessionAnalyticsView;

class SessionQuery
{
    /**
     * One live session in full, plus the rehearsal baseline of the same deck
     * so the detail page can compare live per-slide time against practice.
     *
     * @return array{
     *     session: SessionDetail,
     *     rehearsedSeconds: array<int, int>,
     *     rehearsalCount: int
     * }|null
     */
    public function detail(int $sessionId): ?array
    {
        $session = SessionAnalyticsView::query()->find($sessionId);

        if ($session === null) {
            return null;
        }

        [$rehearsedSeconds, $rehearsalCount] = $this->rehearsedSecondsPerSlide($session->presentation_id);

        return [
            'session' => $session->toSessionDetail(),
            'rehearsedSeconds' => $rehearsedSeconds,
            'rehearsalCount' => $rehearsalCount,
        ];
    }

    /**
     * Average rehearsed seconds per slide index across every rehearsal of the
     * deck — the baseline the live timings are held against.
     *
     * @return array{0: array<int, int>, 1: int}
     */
    private function rehearsedSecondsPerSlide(int $presentationId): array
    {
        $totals = [];
        $counts = [];

        $runs = RehearsalHistoryView::query()
            ->where('presentation_id', $presentationId)
            ->get();

        $runs->each(function (RehearsalHistoryView $run) use (&$totals, &$counts): void {
            foreach ($run->slideTimings() as $timing) {
                $totals[$timing['slide']] = ($totals[$timing['slide']] ?? 0) + $timing['seconds'];
                $counts[$timing['slide']] = ($counts[$timing['slide']] ?? 0) + 1;
            }
        });

        $averages = [];

        foreach ($totals as $slide => $seconds) {
            $averages[$slide] = (int) round($seconds / $counts[$slide]);
        }

        return [$averages, $runs->count()];
    }
}
