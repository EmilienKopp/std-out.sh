<?php

declare(strict_types=1);

namespace App\Application\Projections;

/** One review's reviewer and state, for compact side lists. */
class ReviewSummary extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) string $reviewer_name,
        public private(set) ?string $reviewer_avatar,
        public private(set) string $status,
        public private(set) ?string $requested_at,
    ) {}
}
