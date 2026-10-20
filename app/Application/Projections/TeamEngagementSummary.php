<?php

declare(strict_types=1);

namespace App\Application\Projections;

class TeamEngagementSummary extends Projection
{
    public function __construct(
        public private(set) int $total_sessions,
        public private(set) int $total_reactions,
        public private(set) int $total_viewers,
        public private(set) int $avg_reactions_per_session,
        public private(set) ?string $top_emoji,
    ) {}
}
