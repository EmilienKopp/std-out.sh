<?php

declare(strict_types=1);

namespace App\Application\Projections;

/** A review this user asked someone else to do. */
class OutgoingReviewRequest extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) int $practice_run_id,
        public private(set) string $presentation_name,
        public private(set) string $reviewer_name,
        public private(set) ?string $reviewer_avatar,
        public private(set) string $status,
        public private(set) string $rehearsed_at,
        public private(set) int $duration_seconds,
        public private(set) ?string $requested_at,
    ) {}
}
