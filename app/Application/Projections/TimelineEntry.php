<?php

declare(strict_types=1);

namespace App\Application\Projections;

class TimelineEntry extends Projection
{
    public function __construct(
        /** 'session'|'rehearsal'|'upcoming' */
        public private(set) string $type,
        public private(set) int $id,
        public private(set) ?int $presentation_id,
        public private(set) string $presentation_name,
        public private(set) string $started_at,
        public private(set) int $duration_seconds,
        public private(set) bool $is_live,
        public private(set) int $viewer_count,
        public private(set) int $reaction_total,
        /** @var array<string, int> */
        public private(set) array $reaction_counts,
        /** Only set on 'upcoming' entries. */
        public private(set) ?string $event_name = null,
    ) {}
}
