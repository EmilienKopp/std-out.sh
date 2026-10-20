<?php

declare(strict_types=1);

namespace App\Application\Projections;

/** A rehearsal run and the reviews collected on it. */
class ReviewedRun extends Projection
{
    public function __construct(
        public private(set) int $practice_run_id,
        public private(set) string $rehearsed_at,
        public private(set) int $duration_seconds,
        /** @var array<int, ReviewSummary> */
        public private(set) array $reviews,
    ) {}
}
