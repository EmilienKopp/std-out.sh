<?php

declare(strict_types=1);

namespace App\Application\Projections;

class DeckVersion extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) ?string $version,
        public private(set) string $name,
        public private(set) ?string $updated_at,
        public private(set) bool $current,
    ) {}
}
