<?php

declare(strict_types=1);

namespace App\Application\Projections;

/** One review on a rehearsal run, with its comments — the requester's side. */
class RunReview extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) int $reviewer_user_id,
        public private(set) string $reviewer_name,
        public private(set) ?string $reviewer_avatar,
        public private(set) string $status,
        public private(set) ?string $requested_at,
        /** @var array<int, ReviewComment> */
        public private(set) array $comments,
    ) {}
}
