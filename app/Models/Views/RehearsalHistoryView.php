<?php

namespace App\Models\Views;

use App\Application\Projections\RehearsalReplay;
use App\Application\Projections\TeamRehearsalHistory;
use App\Application\Projections\TimelineEntry;
use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $presentation_id
 * @property int $team_id
 * @property string $presentation_name
 * @property Carbon $started_at
 * @property Carbon $ended_at
 * @property int $duration_seconds
 * @property int|null $word_count
 * @property list<array{slide: int, seconds: int}> $slide_timings
 * @property list<array{at_ms: int, slide: int, step: int}>|null $step_events
 * @property array<string, mixed> $content
 * @property array<string, mixed>|null $flow
 * @property int|null $version_major
 * @property int|null $version_minor
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $has_recording
 * @property int $pending_review_count
 * @property int $completed_review_count
 */
class RehearsalHistoryView extends ReadOnlyModel
{
    protected $table = 'practice_run_history';

    public $timestamps = false;

    /**
     * Runs saved from multipart posts stored their JSON numbers as strings;
     * these accessors heal that on read so pages can do arithmetic.
     *
     * @return list<array{slide: int, seconds: int}>
     */
    public function slideTimings(): array
    {
        return array_map(
            fn (array $timing): array => [
                'slide' => (int) $timing['slide'],
                'seconds' => (int) $timing['seconds'],
            ],
            $this->slide_timings ?? [],
        );
    }

    /**
     * @return list<array{at_ms: int, slide: int, step: int}>
     */
    public function stepEvents(): array
    {
        return array_map(
            fn (array $event): array => [
                'at_ms' => (int) $event['at_ms'],
                'slide' => (int) $event['slide'],
                'step' => (int) $event['step'],
            ],
            $this->step_events ?? [],
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'slide_timings' => 'array',
            'step_events' => 'array',
            'content' => 'array',
            'flow' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'has_recording' => 'boolean',
            'pending_review_count' => 'integer',
            'completed_review_count' => 'integer',
        ];
    }

    public function toTeamRehearsalHistory(): TeamRehearsalHistory
    {
        return new TeamRehearsalHistory(
            id: $this->id,
            presentation_id: $this->presentation_id,
            presentation_name: $this->presentation_name,
            started_at: $this->started_at->toISOString(),
            duration_seconds: $this->duration_seconds,
            slide_count: count($this->content['slides'] ?? []),
            slide_timings: $this->slideTimings(),
            version: $this->versionLabel(),
        );
    }

    /** The deck's "2.1"-style version at the moment of the run, or null. */
    public function versionLabel(): ?string
    {
        if ($this->version_major === null || $this->version_minor === null) {
            return null;
        }

        return $this->version_major.'.'.$this->version_minor;
    }

    public function toRehearsalReplay(): RehearsalReplay
    {
        return new RehearsalReplay(
            id: $this->id,
            presentation_id: $this->presentation_id,
            presentation_name: $this->presentation_name,
            started_at: $this->started_at->toISOString(),
            ended_at: $this->ended_at->toISOString(),
            duration_seconds: $this->duration_seconds,
            slide_timings: $this->slideTimings(),
            step_events: $this->stepEvents(),
            has_recording: $this->has_recording,
            content: $this->content,
            flow: $this->flow,
            version: $this->versionLabel(),
        );
    }

    public function toTimelineEntry(): TimelineEntry
    {
        return new TimelineEntry(
            type: 'rehearsal',
            id: $this->id,
            presentation_id: $this->presentation_id,
            presentation_name: $this->presentation_name,
            started_at: $this->started_at->toISOString(),
            duration_seconds: $this->duration_seconds,
            is_live: false,
            viewer_count: 0,
            reaction_total: 0,
            reaction_counts: [],
        );
    }
}
