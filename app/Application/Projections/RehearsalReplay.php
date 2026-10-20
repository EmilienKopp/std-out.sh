<?php

declare(strict_types=1);

namespace App\Application\Projections;

class RehearsalReplay extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) int $presentation_id,
        public private(set) string $presentation_name,
        public private(set) string $started_at,
        public private(set) string $ended_at,
        public private(set) int $duration_seconds,
        /** @var list<array{slide: int, seconds: int}> */
        public private(set) array $slide_timings,
        /** @var list<array{at_ms: int, slide: int, step: int}> */
        public private(set) array $step_events,
        public private(set) bool $has_recording,
        /** @var array<string, mixed> */
        public private(set) array $content,
        /** @var array<string, mixed>|null */
        public private(set) ?array $flow,
        public private(set) ?string $version,
    ) {}
}
