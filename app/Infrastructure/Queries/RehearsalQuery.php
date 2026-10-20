<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Application\Projections\RehearsalReplay;
use App\Application\Projections\TeamRehearsalHistory;
use App\Models\Views\RehearsalHistoryView;

class RehearsalQuery
{
    /**
     * A team's rehearsal history, most recent first, without the heavy deck
     * snapshots — those load one at a time on the replay page.
     *
     * @return array<int, TeamRehearsalHistory>
     */
    public function listForTeam(int $teamId): array
    {
        return RehearsalHistoryView::query()
            ->where('team_id', $teamId)
            ->orderByDesc('started_at')
            ->get()
            ->map(fn (RehearsalHistoryView $run) => $run->toTeamRehearsalHistory())
            ->all();
    }

    /** A single rehearsal with its frozen deck, ready for read-only replay. */
    public function findForReplay(int $runId): RehearsalReplay
    {
        return RehearsalHistoryView::query()->findOrFail($runId)->toRehearsalReplay();
    }
}
