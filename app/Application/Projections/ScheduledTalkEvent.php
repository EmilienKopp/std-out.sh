<?php

declare(strict_types=1);

namespace App\Application\Projections;

class ScheduledTalkEvent extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) string $name,
        /** Y-m-d */
        public private(set) string $date,
        /** H:i */
        public private(set) ?string $start_time,
        public private(set) ?int $talk_id,
        public private(set) ?string $talk_title,
    ) {}
}
