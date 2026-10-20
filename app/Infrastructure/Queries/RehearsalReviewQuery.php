<?php

declare(strict_types=1);

namespace App\Infrastructure\Queries;

use App\Application\Projections\IncomingReviewRequest;
use App\Application\Projections\OutgoingReviewRequest;
use App\Application\Projections\RehearsalReviewDetail;
use App\Application\Projections\ReviewedRun;
use App\Application\Projections\ReviewSummary;
use App\Application\Projections\RunReview;
use App\Models\Views\RehearsalHistoryView;
use App\Models\Views\RehearsalReviewDetailView;
use App\Models\Views\ReviewCommentDetailView;

class RehearsalReviewQuery
{
    /**
     * Reviews someone has asked this user to do, pending first.
     *
     * @return array<int, IncomingReviewRequest>
     */
    public function listForReviewer(int $userId): array
    {
        return RehearsalReviewDetailView::query()
            ->where('reviewer_user_id', $userId)
            ->pendingLast()
            ->get()
            ->map(fn (RehearsalReviewDetailView $review) => $review->toIncomingReviewRequest())
            ->all();
    }

    /**
     * Reviews this user has asked others to do, pending first.
     *
     * @return array<int, OutgoingReviewRequest>
     */
    public function listForRequester(int $userId): array
    {
        return RehearsalReviewDetailView::query()
            ->where('requester_user_id', $userId)
            ->pendingLast()
            ->get()
            ->map(fn (RehearsalReviewDetailView $review) => $review->toOutgoingReviewRequest())
            ->all();
    }

    /**
     * Everything the reviewer's page needs: the review, the frozen run
     * snapshot, and the comments left so far. Assembled here because it spans
     * three views.
     */
    public function findForReviewPage(int $reviewId): RehearsalReviewDetail
    {
        $review = RehearsalReviewDetailView::query()->findOrFail($reviewId);
        $run = RehearsalHistoryView::query()->findOrFail($review->practice_run_id);

        return new RehearsalReviewDetail(
            id: $review->id,
            practice_run_id: $review->practice_run_id,
            presentation_id: $review->presentation_id,
            status: $review->status,
            presentation_name: $review->presentation_name,
            requester_name: $review->requester_name,
            requester_avatar: $review->requester_avatar,
            reviewer_name: $review->reviewer_name,
            reviewer_avatar: $review->reviewer_avatar,
            rehearsed_at: $review->rehearsed_at->toISOString(),
            duration_seconds: $review->duration_seconds,
            content: $run->content,
            flow: $run->flow,
            step_events: $run->stepEvents(),
            has_recording: $run->has_recording,
            comments: ReviewCommentDetailView::query()
                ->where('rehearsal_review_id', $review->id)
                ->orderBy('slide_number')
                ->orderBy('created_at')
                ->get()
                ->map(fn (ReviewCommentDetailView $comment) => $comment->toReviewComment())
                ->all(),
        );
    }

    /**
     * The requester's side: all reviews on one rehearsal run, each with its
     * reviewer and comments.
     *
     * @return array<int, RunReview>
     */
    public function listForRun(int $runId): array
    {
        $comments = ReviewCommentDetailView::query()
            ->where('practice_run_id', $runId)
            ->orderBy('slide_number')
            ->orderBy('created_at')
            ->get()
            ->groupBy('rehearsal_review_id');

        return RehearsalReviewDetailView::query()
            ->where('practice_run_id', $runId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (RehearsalReviewDetailView $review): RunReview => $review->toRunReview(
                $comments->get($review->id, collect())
                    ->map(fn (ReviewCommentDetailView $comment) => $comment->toReviewComment())
                    ->values()
                    ->all(),
            ))
            ->all();
    }

    /**
     * The other reviews requested on the same rehearsal run.
     *
     * @return array<int, ReviewSummary>
     */
    public function listSiblingReviews(int $runId, int $excludeReviewId): array
    {
        return RehearsalReviewDetailView::query()
            ->where('practice_run_id', $runId)
            ->where('id', '!=', $excludeReviewId)
            ->orderByDesc('created_at')
            ->get(['id', 'reviewer_name', 'reviewer_avatar', 'status', 'created_at'])
            ->map(fn (RehearsalReviewDetailView $review) => $review->toReviewSummary())
            ->all();
    }

    /**
     * Reviews the requester collected on other rehearsal runs of the same
     * presentation, grouped per run, newest rehearsal first.
     *
     * @return array<int, ReviewedRun>
     */
    public function listReviewedRunsForPresentation(int $presentationId, int $requesterUserId, int $excludeRunId): array
    {
        return RehearsalReviewDetailView::query()
            ->where('presentation_id', $presentationId)
            ->where('requester_user_id', $requesterUserId)
            ->where('practice_run_id', '!=', $excludeRunId)
            ->orderByDesc('rehearsed_at')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('practice_run_id')
            ->map(fn ($reviews): ReviewedRun => new ReviewedRun(
                practice_run_id: $reviews->first()->practice_run_id,
                rehearsed_at: $reviews->first()->rehearsed_at->toISOString(),
                duration_seconds: $reviews->first()->duration_seconds,
                reviews: $reviews
                    ->map(fn (RehearsalReviewDetailView $review) => $review->toReviewSummary())
                    ->values()
                    ->all(),
            ))
            ->values()
            ->all();
    }
}
