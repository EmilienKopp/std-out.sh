<?php

declare(strict_types=1);

namespace App\Application\Projections;

class TeamRehearsalHistory extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) int $presentation_id,
        public private(set) string $presentation_name,
        public private(set) string $started_at,
        public private(set) int $duration_seconds,
        public private(set) int $slide_count,
        /** @var list<array{slide: int, seconds: int}> */
        public private(set) array $slide_timings,
        public private(set) ?string $version,
    ) {}
}
