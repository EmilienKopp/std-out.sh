<?php

declare(strict_types=1);

namespace App\Application\Projections;

class DeckSummary extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) string $name,
        public private(set) bool $is_private,
        public private(set) int $slide_count,
        /** 'ready'|'generating'|'failed' */
        public private(set) string $status,
        public private(set) ?string $draft_error,
        public private(set) ?string $updated_at,
        public private(set) ?int $talk_id,
        public private(set) ?string $version,
        /** First slide's CSS background (color or gradient), for the list thumbnail. */
        public private(set) ?string $background,
        public private(set) ?string $background_image,
    ) {}
}
