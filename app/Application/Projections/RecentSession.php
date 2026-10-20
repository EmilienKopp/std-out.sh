<?php

declare(strict_types=1);

namespace App\Application\Projections;

class RecentSession extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) int $presentation_id,
        public private(set) string $presentation_name,
        public private(set) string $started_at,
        public private(set) ?string $ended_at,
        public private(set) int $duration_seconds,
        public private(set) bool $is_live,
        public private(set) int $viewer_count,
        public private(set) int $reaction_total,
        /** @var array<string, int> */
        public private(set) array $reaction_counts,
        public private(set) ?string $top_emoji,
    ) {}
}
