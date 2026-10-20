<?php

declare(strict_types=1);

namespace App\Application\Projections;

/**
 * Everything the reviewer's page needs: the review, the frozen run snapshot,
 * and the comments left so far.
 */
class RehearsalReviewDetail extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) int $practice_run_id,
        public private(set) int $presentation_id,
        public private(set) string $status,
        public private(set) string $presentation_name,
        public private(set) string $requester_name,
        public private(set) ?string $requester_avatar,
        public private(set) string $reviewer_name,
        public private(set) ?string $reviewer_avatar,
        public private(set) string $rehearsed_at,
        public private(set) int $duration_seconds,
        /** @var array<string, mixed> */
        public private(set) array $content,
        /** @var array<string, mixed>|null */
        public private(set) ?array $flow,
        /** @var list<array{at_ms: int, slide: int, step: int}> */
        public private(set) array $step_events,
        public private(set) bool $has_recording,
        /** @var array<int, ReviewComment> */
        public private(set) array $comments,
    ) {}
}
