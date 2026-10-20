<?php

namespace App\Application\Projections;

class FollowableProfile extends Projection
{
    public function __construct(
        public private(set) int $id,
        public private(set) string $handle,
        public private(set) string $name,
        public private(set) string $avatar,
        public private(set) int $talks_count,
        public private(set) int $total_viewers,
        public private(set) int $total_reactions,
        public private(set) int $followers_count,
        public private(set) int $following_count,
        public private(set) string $follow_status,
        public private(set) ?string $social_x_handle,
        public private(set) ?string $social_github_handle,
    ) {}
}
