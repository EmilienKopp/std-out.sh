<?php

declare(strict_types=1);

namespace App\Application\Projections;

class FollowedTalk extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) string $name,
        public private(set) ?string $updated_at,
        public private(set) ?string $last_presented_at,
        public private(set) int $viewer_count,
        public private(set) int $reaction_total,
        public private(set) ContactCard $user,
    ) {}
}
