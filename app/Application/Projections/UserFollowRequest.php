<?php

namespace App\Application\Projections;

class UserFollowRequest extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) string $name,
        public private(set) string $avatar,
        public private(set) ?string $handle,
        public private(set) ?string $social_x_handle,
        public private(set) ?string $social_github_handle,
        public private(set) string $requested_at,
    ) {}
}
