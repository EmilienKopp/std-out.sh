<?php

declare(strict_types=1);

namespace App\Application\Projections;

class ReviewableBetaRequest extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) string $name,
        public private(set) string $email,
        public private(set) ?string $message,
        public private(set) string $status,
        public private(set) ?string $created_at,
        public private(set) bool $registered,
    ) {}
}
