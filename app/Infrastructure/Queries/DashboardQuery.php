<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Application\Projections\RecentSession;
use App\Application\Projections\TeamEngagementSummary;
use App\Application\Projections\TimelineEntry;
use App\Models\Views\CalendarEventView;
use App\Models\Views\RehearsalHistoryView;
use App\Models\Views\SessionAnalyticsView;
use App\Models\Views\TalkOverviewView;
use Illuminate\Support\Carbon;

class DashboardQuery
{
    /**
     * Live talks and rehearsals interleaved into one history, most recent
     * first, shaped for the dashboard timeline's two lanes. Scheduled talk
     * events within the next week are prepended on top, soonest last, so the
     * timeline reads future-to-past without upcoming entries eating into the
     * history limit.
     *
     * @return array<int, TimelineEntry>
     */
    public function timelineForTeam(int $teamId, int $limit = 30): array
    {
        $entries = [];

        $sessions = SessionAnalyticsView::query()
            ->where('team_id', $teamId)
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get();

        foreach ($sessions as $session) {
            $entries[] = $session->toTimelineEntry();
        }

        $rehearsals = RehearsalHistoryView::query()
            ->select(['id', 'presentation_id', 'presentation_name', 'started_at', 'duration_seconds'])
            ->where('team_id', $teamId)
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get();

        foreach ($rehearsals as $run) {
            $entries[] = $run->toTimelineEntry();
        }

        usort($entries, fn (TimelineEntry $a, TimelineEntry $b): int => strcmp($b->started_at, $a->started_at));

        return array_merge(
            $this->upcomingTimelineEntries($teamId),
            array_slice($entries, 0, $limit),
        );
    }

    /**
     * Talk events scheduled between today and a week out, furthest first so
     * they stack on top of the reverse-chronological history. Each carries
     * the linked talk's latest finished deck so the UI can offer Edit and
     * Rehearse; presentation_id is null for events without one. Built here
     * rather than by a view mapper because the latest deck comes from a
     * second view.
     *
     * @return array<int, TimelineEntry>
     */
    private function upcomingTimelineEntries(int $teamId): array
    {
        $events = CalendarEventView::query()
            ->where('team_id', $teamId)
            ->whereBetween('date', [
                Carbon::today()->toDateString(),
                Carbon::today()->addWeek()->toDateString(),
            ])
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->get();

        $latestDeckByTalk = TalkOverviewView::query()
            ->whereIn('id', $events->pluck('talk_id')->filter()->unique())
            ->pluck('latest_presentation_id', 'id');

        return $events
            ->map(fn (CalendarEventView $event): TimelineEntry => new TimelineEntry(
                type: 'upcoming',
                id: $event->id,
                presentation_id: $event->talk_id !== null
                    ? $latestDeckByTalk[$event->talk_id] ?? null
                    : null,
                presentation_name: $event->talk_title ?? $event->name,
                started_at: $event->date
                    ->setTimeFromTimeString($event->start_time ?? '00:00:00')
                    ->toISOString(),
                duration_seconds: 0,
                is_live: false,
                viewer_count: 0,
                reaction_total: 0,
                reaction_counts: [],
                event_name: $event->name,
            ))
            ->all();
    }

    /**
     * Recent talks given by a team, most recent first, shaped for the
     * post-talk analytics feed.
     *
     * @return array<int, RecentSession>
     */
    public function recentSessionsForTeam(int $teamId, int $limit = 8): array
    {
        return SessionAnalyticsView::query()
            ->where('team_id', $teamId)
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get()
            ->map(fn (SessionAnalyticsView $session) => $session->toRecentSession())
            ->all();
    }

    /** Lifetime engagement roll-up across all of a team's sessions. */
    public function teamEngagementSummary(int $teamId): TeamEngagementSummary
    {
        $sessions = SessionAnalyticsView::query()
            ->where('team_id', $teamId)
            ->get();

        $totalSessions = $sessions->count();
        $totalReactions = (int) $sessions->sum('reaction_total');
        $totalViewers = (int) $sessions->sum('viewer_count');

        $combinedCounts = [];

        foreach ($sessions as $session) {
            foreach ($session->reaction_counts ?? [] as $emoji => $count) {
                $combinedCounts[$emoji] = ($combinedCounts[$emoji] ?? 0) + $count;
            }
        }

        return new TeamEngagementSummary(
            total_sessions: $totalSessions,
            total_reactions: $totalReactions,
            total_viewers: $totalViewers,
            avg_reactions_per_session: $totalSessions > 0
                ? (int) round($totalReactions / $totalSessions)
                : 0,
            top_emoji: SessionAnalyticsView::topEmoji($combinedCounts),
        );
    }
}
