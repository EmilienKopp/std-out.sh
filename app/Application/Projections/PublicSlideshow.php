<?php

declare(strict_types=1);

namespace App\Application\Projections;

/**
 * Public subset for the anonymous slideshow page: the deck itself and nothing
 * live — no remote/session secrets, no translation session.
 */
class PublicSlideshow extends Projection
{
    public function __construct(
        public private(set) string $name,
        /** @var array<string, mixed> */
        public private(set) array $content,
        /** @var array<string, mixed>|null */
        public private(set) ?array $flow,
        /** @var array<string, mixed> */
        public private(set) array $source,
    ) {}
}
