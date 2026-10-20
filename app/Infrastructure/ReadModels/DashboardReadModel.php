<?php

declare(strict_types=1);

namespace App\Infrastructure\ReadModels;

use App\Models\Views\CalendarEventView;
use App\Models\Views\RehearsalHistoryView;
use App\Models\Views\SessionAnalyticsView;
use App\Models\Views\TalkOverviewView;
use Illuminate\Support\Carbon;

class DashboardReadModel
{
    /**
     * Live talks and rehearsals interleaved into one history, most recent
     * first, shaped for the dashboard timeline's two lanes. Scheduled talk
     * events within the next week are prepended on top, soonest last, so the
     * timeline reads future-to-past without upcoming entries eating into the
     * history limit.
     *
     * @return array<int, array{
     *     type: 'session'|'rehearsal'|'upcoming',
     *     id: int,
     *     presentation_id: int|null,
     *     presentation_name: string,
     *     started_at: string,
     *     duration_seconds: int,
     *     is_live: bool,
     *     viewer_count: int,
     *     reaction_total: int,
     *     reaction_counts: array<string, int>
     * }>
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
            $entries[] = $this->sessionTimelineEntry($session);
        }

        $rehearsals = RehearsalHistoryView::query()
            ->select(['id', 'presentation_id', 'presentation_name', 'started_at', 'duration_seconds'])
            ->where('team_id', $teamId)
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get();

        foreach ($rehearsals as $run) {
            $entries[] = $this->rehearsalTimelineEntry($run);
        }

        usort($entries, fn (array $a, array $b): int => strcmp($b['started_at'], $a['started_at']));

        return array_merge(
            $this->upcomingTimelineEntries($teamId),
            array_slice($entries, 0, $limit),
        );
    }

    /**
     * Talk events scheduled between today and a week out, furthest first so
     * they stack on top of the reverse-chronological history. Each carries
     * the linked talk's latest finished deck so the UI can offer Edit and
     * Rehearse; presentation_id is null for events without one.
     *
     * @return array<int, array{
     *     type: 'upcoming',
     *     id: int,
     *     presentation_id: int|null,
     *     presentation_name: string,
     *     event_name: string,
     *     started_at: string,
     *     duration_seconds: int,
     *     is_live: bool,
     *     viewer_count: int,
     *     reaction_total: int,
     *     reaction_counts: array<string, int>
     * }>
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
            ->map(fn (CalendarEventView $event): array => [
                'type' => 'upcoming',
                'id' => $event->id,
                'presentation_id' => $event->talk_id !== null
                    ? $latestDeckByTalk[$event->talk_id] ?? null
                    : null,
                'presentation_name' => $event->talk_title ?? $event->name,
                'event_name' => $event->name,
                'started_at' => $event->date
                    ->setTimeFromTimeString($event->start_time ?? '00:00:00')
                    ->toISOString(),
                'duration_seconds' => 0,
                'is_live' => false,
                'viewer_count' => 0,
                'reaction_total' => 0,
                'reaction_counts' => [],
            ])
            ->all();
    }

    /**
     * @return array{
     *     type: 'session',
     *     id: int,
     *     presentation_id: int,
     *     presentation_name: string,
     *     started_at: string,
     *     duration_seconds: int,
     *     is_live: bool,
     *     viewer_count: int,
     *     reaction_total: int,
     *     reaction_counts: array<string, int>
     * }
     */
    private function sessionTimelineEntry(SessionAnalyticsView $session): array
    {
        return [
            'type' => 'session',
            'id' => $session->id,
            'presentation_id' => $session->presentation_id,
            'presentation_name' => $session->presentation_name,
            'started_at' => (string) $session->started_at->toISOString(),
            'duration_seconds' => $this->durationSeconds($session),
            'is_live' => $session->ended_at === null,
            'viewer_count' => $session->viewer_count,
            'reaction_total' => $session->reaction_total,
            'reaction_counts' => $session->reaction_counts ?? [],
        ];
    }

    /**
     * @return array{
     *     type: 'rehearsal',
     *     id: int,
     *     presentation_id: int,
     *     presentation_name: string,
     *     started_at: string,
     *     duration_seconds: int,
     *     is_live: bool,
     *     viewer_count: int,
     *     reaction_total: int,
     *     reaction_counts: array<string, int>
     * }
     */
    private function rehearsalTimelineEntry(RehearsalHistoryView $run): array
    {
        return [
            'type' => 'rehearsal',
            'id' => $run->id,
            'presentation_id' => $run->presentation_id,
            'presentation_name' => $run->presentation_name,
            'started_at' => (string) $run->started_at->toISOString(),
            'duration_seconds' => $run->duration_seconds,
            'is_live' => false,
            'viewer_count' => 0,
            'reaction_total' => 0,
            'reaction_counts' => [],
        ];
    }

    /**
     * Recent talks given by a team, most recent first, shaped for the
     * post-talk analytics feed.
     *
     * @return array<int, array{
     *     id: int,
     *     presentation_id: int,
     *     presentation_name: string,
     *     started_at: string,
     *     ended_at: string|null,
     *     duration_seconds: int,
     *     is_live: bool,
     *     viewer_count: int,
     *     reaction_total: int,
     *     reaction_counts: array<string, int>,
     *     top_emoji: string|null
     * }>
     */
    public function recentSessionsForTeam(int $teamId, int $limit = 8): array
    {
        return SessionAnalyticsView::query()
            ->where('team_id', $teamId)
            ->orderByDesc('started_at')
            ->limit($limit)
            ->get()
            ->map(fn (SessionAnalyticsView $session): array => [
                'id' => $session->id,
                'presentation_id' => $session->presentation_id,
                'presentation_name' => $session->presentation_name,
                'started_at' => $session->started_at->toISOString(),
                'ended_at' => $session->ended_at?->toISOString(),
                'duration_seconds' => $this->durationSeconds($session),
                'is_live' => $session->ended_at === null,
                'viewer_count' => $session->viewer_count,
                'reaction_total' => $session->reaction_total,
                'reaction_counts' => $session->reaction_counts ?? [],
                'top_emoji' => $this->topEmoji($session->reaction_counts ?? []),
            ])
            ->all();
    }

    /**
     * Lifetime engagement roll-up across all of a team's sessions.
     *
     * @return array{
     *     total_sessions: int,
     *     total_reactions: int,
     *     total_viewers: int,
     *     avg_reactions_per_session: int,
     *     top_emoji: string|null
     * }
     */
    public function teamEngagementSummary(int $teamId): array
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

        return [
            'total_sessions' => $totalSessions,
            'total_reactions' => $totalReactions,
            'total_viewers' => $totalViewers,
            'avg_reactions_per_session' => $totalSessions > 0
                ? (int) round($totalReactions / $totalSessions)
                : 0,
            'top_emoji' => $this->topEmoji($combinedCounts),
        ];
    }

    private function durationSeconds(SessionAnalyticsView $session): int
    {
        $end = $session->ended_at ?? $session->last_seen_at ?? $session->started_at;

        return max(0, $end->getTimestamp() - $session->started_at->getTimestamp());
    }

    /**
     * @param  array<string, int>  $counts
     */
    private function topEmoji(array $counts): ?string
    {
        if ($counts === []) {
            return null;
        }

        return array_key_first(
            array_filter($counts, fn (int $count): bool => $count === max($counts))
        );
    }
}
