<?php

namespace App\Models\Views;

use App\Application\Projections\RecentSession;
use App\Application\Projections\SessionDetail;
use App\Application\Projections\TimelineEntry;
use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $presentation_id
 * @property int $team_id
 * @property string $presentation_name
 * @property Carbon $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $last_seen_at
 * @property array<string, int> $reaction_counts
 * @property int $reaction_total
 * @property int $viewer_count
 * @property int|null $word_count
 * @property list<array{slide: int, seconds: int}>|null $slide_timings
 * @property list<array{slide: int, reactions: array<string, int>}>|null $reaction_slides
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SessionAnalyticsView extends ReadOnlyModel
{
    protected $table = 'session_analytics';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'reaction_counts' => 'array',
            'slide_timings' => 'array',
            'reaction_slides' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Wall-clock length of the session; live sessions count up to the last
     * heartbeat seen.
     */
    public function durationSeconds(): int
    {
        $end = $this->ended_at ?? $this->last_seen_at ?? $this->started_at;

        return max(0, $end->getTimestamp() - $this->started_at->getTimestamp());
    }

    /**
     * The most-used emoji in a reaction tally, or null when there were none.
     *
     * @param  array<string, int>  $counts
     */
    public static function topEmoji(array $counts): ?string
    {
        if ($counts === []) {
            return null;
        }

        return array_key_first(
            array_filter($counts, fn (int $count): bool => $count === max($counts))
        );
    }

    public function toSessionDetail(): SessionDetail
    {
        return new SessionDetail(
            id: $this->id,
            presentation_id: $this->presentation_id,
            presentation_name: $this->presentation_name,
            started_at: $this->started_at->toISOString(),
            ended_at: $this->ended_at?->toISOString(),
            is_live: $this->ended_at === null,
            duration_seconds: $this->ended_at !== null
                ? (int) $this->started_at->diffInSeconds($this->ended_at, true)
                : (int) $this->started_at->diffInSeconds(now(), true),
            viewer_count: $this->viewer_count,
            reaction_total: $this->reaction_total,
            reaction_counts: $this->reaction_counts ?? [],
            word_count: $this->word_count,
            slide_timings: $this->slide_timings ?? [],
            reaction_slides: $this->reaction_slides ?? [],
        );
    }

    public function toTimelineEntry(): TimelineEntry
    {
        return new TimelineEntry(
            type: 'session',
            id: $this->id,
            presentation_id: $this->presentation_id,
            presentation_name: $this->presentation_name,
            started_at: $this->started_at->toISOString(),
            duration_seconds: $this->durationSeconds(),
            is_live: $this->ended_at === null,
            viewer_count: $this->viewer_count,
            reaction_total: $this->reaction_total,
            reaction_counts: $this->reaction_counts ?? [],
        );
    }

    public function toRecentSession(): RecentSession
    {
        return new RecentSession(
            id: $this->id,
            presentation_id: $this->presentation_id,
            presentation_name: $this->presentation_name,
            started_at: $this->started_at->toISOString(),
            ended_at: $this->ended_at?->toISOString(),
            duration_seconds: $this->durationSeconds(),
            is_live: $this->ended_at === null,
            viewer_count: $this->viewer_count,
            reaction_total: $this->reaction_total,
            reaction_counts: $this->reaction_counts ?? [],
            top_emoji: self::topEmoji($this->reaction_counts ?? []),
        );
    }
}
