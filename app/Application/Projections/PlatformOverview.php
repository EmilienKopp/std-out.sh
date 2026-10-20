<?php

declare(strict_types=1);

namespace App\Application\Projections;

class PlatformOverview extends Projection
{
    public function __construct(
        public private(set) int $total_users,
        public private(set) int $total_teams,
        public private(set) int $total_workspaces,
        public private(set) int $total_presentations,
        public private(set) int $total_sessions,
        public private(set) int $live_sessions,
        public private(set) int $total_reactions,
        public private(set) int $total_viewers,
    ) {}

    /** The empty platform, for when the overview view has no row yet. */
    public static function zero(): self
    {
        return new self(0, 0, 0, 0, 0, 0, 0, 0);
    }
}
