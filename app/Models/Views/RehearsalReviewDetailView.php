<?php

namespace App\Models\Views;

use App\Application\Projections\IncomingReviewRequest;
use App\Application\Projections\OutgoingReviewRequest;
use App\Application\Projections\ReviewComment;
use App\Application\Projections\ReviewSummary;
use App\Application\Projections\RunReview;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Splitstack\Rome\Models\ReadOnlyModel;

/**
 * @property int $id
 * @property int $practice_run_id
 * @property int $requester_user_id
 * @property int $reviewer_user_id
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $team_id
 * @property int $presentation_id
 * @property Carbon $rehearsed_at
 * @property int $duration_seconds
 * @property string $presentation_name
 * @property string $requester_name
 * @property string|null $requester_avatar
 * @property string|null $requester_handle
 * @property string $reviewer_name
 * @property string|null $reviewer_avatar
 * @property string|null $reviewer_handle
 */
class RehearsalReviewDetailView extends ReadOnlyModel
{
    protected $table = 'rehearsal_review_details';

    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // View columns come back untyped (strings on SQLite); the pages
            // format duration_seconds and compare ids numerically.
            'practice_run_id' => 'integer',
            'requester_user_id' => 'integer',
            'reviewer_user_id' => 'integer',
            'team_id' => 'integer',
            'presentation_id' => 'integer',
            'duration_seconds' => 'integer',
            'rehearsed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<RehearsalReviewDetailView>  $query
     * @return Builder<RehearsalReviewDetailView>
     */
    public function scopePendingLast(Builder $query): Builder
    {
        return $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->orderByDesc('created_at');
    }

    public function toIncomingReviewRequest(): IncomingReviewRequest
    {
        return new IncomingReviewRequest(
            id: $this->id,
            practice_run_id: $this->practice_run_id,
            presentation_name: $this->presentation_name,
            requester_name: $this->requester_name,
            requester_avatar: $this->requester_avatar,
            status: $this->status,
            rehearsed_at: $this->rehearsed_at->toISOString(),
            duration_seconds: $this->duration_seconds,
            requested_at: $this->created_at?->toISOString(),
        );
    }

    public function toOutgoingReviewRequest(): OutgoingReviewRequest
    {
        return new OutgoingReviewRequest(
            id: $this->id,
            practice_run_id: $this->practice_run_id,
            presentation_name: $this->presentation_name,
            reviewer_name: $this->reviewer_name,
            reviewer_avatar: $this->reviewer_avatar,
            status: $this->status,
            rehearsed_at: $this->rehearsed_at->toISOString(),
            duration_seconds: $this->duration_seconds,
            requested_at: $this->created_at?->toISOString(),
        );
    }

    public function toReviewSummary(): ReviewSummary
    {
        return new ReviewSummary(
            id: $this->id,
            reviewer_name: $this->reviewer_name,
            reviewer_avatar: $this->reviewer_avatar,
            status: $this->status,
            requested_at: $this->created_at?->toISOString(),
        );
    }

    /** @param array<int, ReviewComment> $comments */
    public function toRunReview(array $comments): RunReview
    {
        return new RunReview(
            id: $this->id,
            reviewer_user_id: $this->reviewer_user_id,
            reviewer_name: $this->reviewer_name,
            reviewer_avatar: $this->reviewer_avatar,
            status: $this->status,
            requested_at: $this->created_at?->toISOString(),
            comments: $comments,
        );
    }
}
