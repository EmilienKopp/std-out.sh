<?php

declare(strict_types=1);

namespace App\Application\Projections;

class LibraryImage extends Projection
{
    public function __construct(
        public private(set) string $url,
        public private(set) string $name,
        public private(set) int $presentation_id,
        public private(set) string $presentation_name,
        public private(set) ?string $created_at,
    ) {}
}
