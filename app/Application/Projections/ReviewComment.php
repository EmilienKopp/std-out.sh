<?php

declare(strict_types=1);

namespace App\Application\Projections;

class ReviewComment extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) int $slide_number,
        public private(set) string $message,
        public private(set) ?string $created_at,
    ) {}
}
