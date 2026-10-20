<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Application\Projections\ScheduledTalkEvent;
use App\Models\Views\CalendarEventView;

class TalkEventQuery
{
    /**
     * Every scheduled event for the team, soonest first. The calendar page
     * filters client-side per displayed month, so no date window is applied.
     *
     * @return array<int, ScheduledTalkEvent>
     */
    public function listForTeam(int $teamId): array
    {
        return CalendarEventView::query()
            ->where('team_id', $teamId)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->map(fn (CalendarEventView $event) => $event->toScheduledTalkEvent())
            ->all();
    }
}
